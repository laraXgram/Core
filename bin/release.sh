#!/usr/bin/env bash

# Release laraxgram/core and all of its components.
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

[ "$(git -C "$ROOT" rev-parse HEAD)" = "$(git -C "$ROOT" rev-parse "origin/$BRANCH")" ] \
    || fail "The $BRANCH branch is not in sync with origin/$BRANCH."

if git -C "$ROOT" rev-parse -q --verify "refs/tags/$VERSION" > /dev/null \
    && [ "$(git -C "$ROOT" rev-list -n 1 "$VERSION")" != "$(git -C "$ROOT" rev-parse HEAD)" ]; then
    fail "The $VERSION tag already exists on another commit."
fi

COMPONENTS=()

for dir in "$ROOT"/src/*/; do
    [ -f "$dir/composer.json" ] && COMPONENTS+=("$(basename "$dir")")
done

if [ $CONFIRMED -eq 0 ]; then
    read -r -p "Release laraxgram/core and ${#COMPONENTS[@]} components as $VERSION from $BRANCH? [y/N] " answer
    [[ $answer =~ ^[Yy]$ ]] || exit 1
fi

echo "[core]"

if ! git -C "$ROOT" rev-parse -q --verify "refs/tags/$VERSION" > /dev/null; then
    dry_run "tag core $VERSION" || { git -C "$ROOT" tag "$VERSION" && echo "  tagged $VERSION"; }
fi

git_push "$ROOT" "$VERSION"
github_create_release core "$VERSION"

"$ROOT/bin/split.sh" --push --tag="$VERSION" --branch="$BRANCH" "${COMPONENTS[@]}"

echo "[packagist]"

packagist_publish core

for component in "${COMPONENTS[@]}"; do
    packagist_publish "$(echo "$component" | tr '[:upper:]' '[:lower:]')"
done

echo "Released $VERSION."
