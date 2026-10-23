#!/usr/bin/env bash

# Sync every component of src/ into its own laraxgram/<component> repository.
#
# Usage: bin/split.sh [--push] [--tag=v4.6.0] [Component ...]

set -euo pipefail

ROOT=$(cd "$(dirname "$0")/.." && pwd)
SPLIT_DIR=${LARAGRAM_SPLIT_DIR:-"$ROOT/../components"}
BRANCH=master
PUSH=0
TAG=""
COMPONENTS=()

for arg in "$@"; do
    case $arg in
        --push) PUSH=1 ;;
        --tag=*) TAG=${arg#--tag=} ;;
        -*) echo "Unknown option: $arg" >&2; exit 1 ;;
        *) COMPONENTS+=("$arg") ;;
    esac
done

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
    remote="https://github.com/laraxgram/$package.git"
    repo="$SPLIT_DIR/$package"

    if [ ! -d "$repo/.git" ]; then
        if git ls-remote --exit-code --heads "$remote" "$BRANCH" > /dev/null 2>&1; then
            git clone -q --branch "$BRANCH" "$remote" "$repo"
        else
            git init -q -b "$BRANCH" "$repo"
            git -C "$repo" remote add origin "$remote"
        fi
    fi

    rsync -a --delete --exclude=.git "$ROOT/src/$component/" "$repo/"

    git -C "$repo" add -A

    if git -C "$repo" diff --cached --quiet; then
        echo "[$package] up to date"
    else
        git -C "$repo" commit -q -m "$MESSAGE"
        echo "[$package] synced"
    fi

    if [ -n "$TAG" ] && ! git -C "$repo" rev-parse -q --verify "refs/tags/$TAG" > /dev/null; then
        git -C "$repo" tag "$TAG"
    fi

    if [ $PUSH -eq 1 ]; then
        git -C "$repo" push -q origin "$BRANCH"
        [ -n "$TAG" ] && git -C "$repo" push -q origin "$TAG"
    fi
done
