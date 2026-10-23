#!/usr/bin/env bash

# Sync every component of src/ into its own laraxgram/<component> repository.
#
# Usage: bin/split.sh [--push] [--tag=v4.6.0] [--branch=4.x.x] [--dry-run] [Component ...]
#
# Components are synced into the branch core is on, unless --branch is given.

set -euo pipefail

ROOT=$(cd "$(dirname "$0")/.." && pwd)
SPLIT_DIR=${LARAGRAM_SPLIT_DIR:-"$ROOT/../components"}
BRANCH=$(git -C "$ROOT" branch --show-current)
PUSH=0
TAG=""
COMPONENTS=()

for arg in "$@"; do
    case $arg in
        --push) PUSH=1 ;;
        --tag=*) TAG=${arg#--tag=} ;;
        --branch=*) BRANCH=${arg#--branch=} ;;
        --dry-run) export DRY_RUN=1 ;;
        -*) echo "Unknown option: $arg" >&2; exit 1 ;;
        *) COMPONENTS+=("$arg") ;;
    esac
done

source "$ROOT/bin/functions.sh"

[ $PUSH -eq 1 ] && require_env GITHUB_TOKEN

if [ ${#COMPONENTS[@]} -eq 0 ]; then
    for dir in "$ROOT"/src/*/; do
        [ -f "$dir/composer.json" ] && COMPONENTS+=("$(basename "$dir")")
    done
fi

MESSAGE="Sync with laraxgram/core"

if [ -z "$(git -C "$ROOT" status --porcelain -- src)" ]; then
    MESSAGE="$MESSAGE@$(git -C "$ROOT" rev-parse --short HEAD)"
else
    echo "Warning: src/ has uncommitted changes, they are synced as well." >&2
fi

mkdir -p "$SPLIT_DIR"

for component in "${COMPONENTS[@]}"; do
    if [ ! -f "$ROOT/src/$component/composer.json" ]; then
        echo "[$component] is not a component, skipping." >&2
        continue
    fi

    package=$(echo "$component" | tr '[:upper:]' '[:lower:]')
    remote="https://github.com/$ORG/$package.git"
    repo="$SPLIT_DIR/$package"

    echo "[$package]"

    if ! github_repository_exists "$package"; then
        if [ -n "${GITHUB_TOKEN:-}" ] || [ "$DRY_RUN" = 1 ]; then
            github_create_repository "$package" "The LaraGram $component package."
        else
            echo "  $ORG/$package does not exist on GitHub yet, set GITHUB_TOKEN to create it" >&2
        fi
    fi

    if [ ! -d "$repo/.git" ]; then
        if git ls-remote --exit-code --heads "$remote" > /dev/null 2>&1; then
            git clone -q "$remote" "$repo"
        else
            git init -q -b "$BRANCH" "$repo"
            git -C "$repo" remote add origin "$remote"
        fi
    fi

    # Switch to the branch, creating it from the current one when it doesn't exist anywhere...
    if [ "$(git -C "$repo" branch --show-current)" != "$BRANCH" ]; then
        if git -C "$repo" rev-parse -q --verify "refs/heads/$BRANCH" > /dev/null; then
            git -C "$repo" checkout -q "$BRANCH"
        elif git -C "$repo" ls-remote --exit-code --heads origin "$BRANCH" > /dev/null 2>&1; then
            git -C "$repo" fetch -q origin "$BRANCH:refs/remotes/origin/$BRANCH"
            git -C "$repo" checkout -q -b "$BRANCH" --track "origin/$BRANCH"
        else
            git -C "$repo" checkout -q -b "$BRANCH"
            echo "  created the $BRANCH branch"
        fi
    fi

    rsync -a --delete --exclude=.git "$ROOT/src/$component/" "$repo/"

    git -C "$repo" add -A

    if git -C "$repo" diff --cached --quiet; then
        echo "  up to date"
    else
        git -C "$repo" commit -q -m "$MESSAGE"
        echo "  synced"
    fi

    if [ -n "$TAG" ] && ! git -C "$repo" rev-parse -q --verify "refs/tags/$TAG" > /dev/null; then
        dry_run "tag $TAG" || { git -C "$repo" tag "$TAG" && echo "  tagged $TAG"; }
    fi

    if [ $PUSH -eq 1 ]; then
        git_push "$repo" "$BRANCH" ${TAG:+"$TAG"}
        github_set_default_branch "$package" "$BRANCH"
    fi
done
