#!/usr/bin/env bash

# Release laraxgram/core and all of its components.
#
# The components are synced, committed, pushed and published first, so core is
# pushed and released last, once every package it replaces is available.
#
# Usage: bin/release.sh <version> [--dry-run] [--yes]

set -euo pipefail

ROOT=$(cd "$(dirname "$0")/.." && pwd)
VERSION=""
CONFIRMED=0

for arg in "$@"; do
    case $arg in
        --dry-run) export DRY_RUN=1 ;;
        --yes|-y) CONFIRMED=1 ;;
        -*) echo "Unknown option: $arg" >&2; exit 1 ;;
        *) VERSION=$arg ;;
    esac
done

source "$ROOT/bin/functions.sh"

[[ $VERSION =~ ^v?[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.]+)?$ ]] || fail "Usage: bin/release.sh <version>, e.g. bin/release.sh 4.6.0"

VERSION="v${VERSION#v}"
MAJOR=${VERSION#v}
BRANCH="${MAJOR%%.*}.x.x"

require_env GITHUB_TOKEN PACKAGIST_USERNAME PACKAGIST_TOKEN

[ -z "$(git -C "$ROOT" status --porcelain)" ] || fail "Commit or stash your changes first."

[ "$(git -C "$ROOT" branch --show-current)" = "$BRANCH" ] || fail "$VERSION must be released from the $BRANCH branch."

git -C "$ROOT" fetch -q origin "$BRANCH" --tags

# The branch may be ahead of origin, since core is pushed at the end of the release...
git -C "$ROOT" merge-base --is-ancestor "origin/$BRANCH" HEAD \
    || fail "The $BRANCH branch is behind or has diverged from origin/$BRANCH, pull first."

RELEASE_CORE=1

# When core was already released on an older commit, only the components are released,
# as long as their code didn't change since then (the split files and renames aside)...
if git -C "$ROOT" rev-parse -q --verify "refs/tags/$VERSION" > /dev/null \
    && [ "$(git -C "$ROOT" rev-list -n 1 "$VERSION")" != "$(git -C "$ROOT" rev-parse HEAD)" ]; then
    CHANGES=$(git -C "$ROOT" diff --name-status -M100% "$VERSION" HEAD -- src \
        ':!src/*/composer.json' ':!src/*/README.md' ':!src/*/LICENSE.md' | grep -v '^R100' || true)

    [ -z "$CHANGES" ] || fail "The $VERSION tag already exists and src/ changed since then, release a new version instead."

    RELEASE_CORE=0
fi

COMPONENTS=()

for dir in "$ROOT"/src/*/; do
    [ -f "$dir/composer.json" ] && COMPONENTS+=("$(basename "$dir")")
done

TARGET="laraxgram/core and ${#COMPONENTS[@]} components"
[ $RELEASE_CORE -eq 1 ] || TARGET="${#COMPONENTS[@]} components (core already has $VERSION)"

if [ $CONFIRMED -eq 0 ]; then
    read -r -p "Release $TARGET as $VERSION from $BRANCH? [y/N] " answer
    [[ $answer =~ ^[Yy]$ ]] || exit 1
fi

# 1. The components: sync, commit, tag and push each one, then publish them...
"$ROOT/bin/split.sh" --push --tag="$VERSION" --branch="$BRANCH" "${COMPONENTS[@]}"

echo "[packagist]"

for component in "${COMPONENTS[@]}"; do
    packagist_publish "$(echo "$component" | tr '[:upper:]' '[:lower:]')"
done

# 2. Core itself, last...
echo "[core]"

REFS=()

[ "$(git -C "$ROOT" rev-parse HEAD)" = "$(git -C "$ROOT" rev-parse "origin/$BRANCH")" ] || REFS+=("$BRANCH")

if [ $RELEASE_CORE -eq 1 ]; then
    if ! git -C "$ROOT" rev-parse -q --verify "refs/tags/$VERSION" > /dev/null; then
        dry_run "tag core $VERSION" || { git -C "$ROOT" tag "$VERSION" && echo "  tagged $VERSION"; }
    fi

    REFS+=("$VERSION")
fi

if [ ${#REFS[@]} -gt 0 ]; then
    git_push "$ROOT" "${REFS[@]}"
else
    echo "  up to date"
fi

if [ $RELEASE_CORE -eq 1 ]; then
    github_create_release core "$VERSION"
    packagist_publish core
fi

echo "Released $VERSION."
