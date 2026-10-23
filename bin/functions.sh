#!/usr/bin/env bash

# Shared helpers of the split and release scripts.
#
# GITHUB_TOKEN       A token with the "repo" scope, used to create repositories,
#                    releases, and to push. Pushing falls back to your own git
#                    credentials when it is not set.
# PACKAGIST_USERNAME Your Packagist username.
# PACKAGIST_TOKEN    Your main Packagist API token (required to submit packages).
# DRY_RUN=1          Print what would be changed remotely instead of doing it.

ORG=laraxgram
HOMEPAGE=https://laraxgram.github.io
DRY_RUN=${DRY_RUN:-0}

RESPONSE=$(mktemp)
trap 'rm -f "$RESPONSE"' EXIT

fail() {
    echo "Error: $*" >&2
    exit 1
}

dry_run() {
    [ "$DRY_RUN" = 1 ] && echo "  (dry run) $*"
}

require_env() {
    [ "$DRY_RUN" = 1 ] && return

    for name in "$@"; do
        [ -n "${!name:-}" ] || fail "The $name environment variable is required."
    done
}

# Call the GitHub API and print the HTTP status code; the body is in $RESPONSE.
github() {
    local method=$1 path=$2 data=${3:-}
    local headers=(-H "Accept: application/vnd.github+json" -H "X-GitHub-Api-Version: 2022-11-28")

    [ -n "${GITHUB_TOKEN:-}" ] && headers+=(-H "Authorization: Bearer $GITHUB_TOKEN")

    curl -sS -X "$method" "${headers[@]}" ${data:+--data "$data"} \
        -o "$RESPONSE" -w '%{http_code}' "https://api.github.com$path"
}

github_repository_exists() {
    case $(github GET "/repos/$ORG/$1") in
        200) return 0 ;;
        404) return 1 ;;
        *) fail "Unable to check github.com/$ORG/$1: $(cat "$RESPONSE")" ;;
    esac
}

github_create_repository() {
    local name=$1 description=$2

    dry_run "create github.com/$ORG/$name" && return

    local status
    status=$(github POST "/orgs/$ORG/repos" "{\"name\":\"$name\",\"description\":\"$description\",\"homepage\":\"$HOMEPAGE\",\"has_issues\":false,\"has_projects\":false,\"has_wiki\":false}")

    [ "$status" = 201 ] || fail "Unable to create $ORG/$name: $(cat "$RESPONSE")"

    echo "  created github.com/$ORG/$name"
}

# Make the given version branch (e.g. 4.x.x) the default one, unless a newer major already is.
github_set_default_branch() {
    local name=$1 branch=$2

    [[ $branch =~ ^([0-9]+)\.x\.x$ ]] || return 0

    local major=${BASH_REMATCH[1]}

    dry_run "make $branch the default branch of $ORG/$name" && return

    [ "$(github GET "/repos/$ORG/$name")" = 200 ] || fail "Unable to read $ORG/$name: $(cat "$RESPONSE")"

    local current
    current=$(sed -n 's/.*"default_branch": *"\([^"]*\)".*/\1/p' "$RESPONSE" | head -n 1)

    [ "$current" = "$branch" ] && return

    if [[ $current =~ ^([0-9]+)\.x\.x$ ]] && [ "${BASH_REMATCH[1]}" -gt "$major" ]; then
        return
    fi

    [ "$(github PATCH "/repos/$ORG/$name" "{\"default_branch\":\"$branch\"}")" = 200 ] \
        || fail "Unable to change the default branch of $ORG/$name: $(cat "$RESPONSE")"

    echo "  default branch is now $branch"
}

github_create_release() {
    local name=$1 tag=$2

    dry_run "create the $tag release of $ORG/$name" && return

    local status
    status=$(github POST "/repos/$ORG/$name/releases" "{\"tag_name\":\"$tag\",\"name\":\"$tag\",\"generate_release_notes\":true}")

    case $status in
        201) echo "  released $ORG/$name $tag" ;;
        422) echo "  the $tag release of $ORG/$name already exists" ;;
        *) fail "Unable to create the $tag release of $ORG/$name: $(cat "$RESPONSE")" ;;
    esac
}

# Push the given refs of the repository at the given path.
git_push() {
    local path=$1
    shift

    dry_run "push $* of $(basename "$path")" && return

    local auth=()

    if [ -n "${GITHUB_TOKEN:-}" ]; then
        auth=(-c "http.https://github.com/.extraheader=AUTHORIZATION: basic $(printf 'x-access-token:%s' "$GITHUB_TOKEN" | base64 | tr -d '\n')")
    fi

    git "${auth[@]}" -C "$path" push -q origin "$@"

    echo "  pushed $*"
}

packagist_package_exists() {
    [ "$(curl -sS -o /dev/null -w '%{http_code}' "https://packagist.org/packages/$ORG/$1.json")" = 200 ]
}

# Submit the package to Packagist, or ask Packagist to crawl it again.
packagist_publish() {
    local name=$1 endpoint=update-package repository="https://packagist.org/packages/$ORG/$1"

    if ! packagist_package_exists "$name"; then
        endpoint=create-package
        repository="https://github.com/$ORG/$name"
    fi

    dry_run "packagist $endpoint $ORG/$name" && return

    local status
    status=$(curl -sS -X POST -o "$RESPONSE" -w '%{http_code}' \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $PACKAGIST_USERNAME:$PACKAGIST_TOKEN" \
        --data "{\"repository\":\"$repository\"}" \
        "https://packagist.org/api/$endpoint")

    [[ $status == 2* ]] || fail "Packagist $endpoint failed for $ORG/$name: $(cat "$RESPONSE")"

    echo "  packagist $endpoint $ORG/$name"
}
