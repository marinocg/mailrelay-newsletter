#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="uve-mailrelay-newsletter"
OUT_DIR="$ROOT_DIR/dist"
ZIP_PATH="$OUT_DIR/$PLUGIN_SLUG.zip"

rm -f "$ZIP_PATH"

mkdir -p "$OUT_DIR"

if ! command -v msgfmt >/dev/null 2>&1; then
  echo "msgfmt is required to build translation files." >&2
  exit 1
fi

echo "Compiling translation files..."
for po_file in "$ROOT_DIR"/languages/*.po; do
  [ -f "$po_file" ] || continue
  mo_file="${po_file%.po}.mo"
  msgfmt -o "$mo_file" "$po_file"
done

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

DEST_DIR="$TMP_DIR/$PLUGIN_SLUG"
mkdir -p "$DEST_DIR"

echo "Copying files to temporary directory..."
rsync -a "$ROOT_DIR/" "$DEST_DIR/" \
  --exclude ".git" \
  --exclude ".github" \
  --exclude "dist" \
  --exclude "tests" \
  --exclude "stubs" \
  --exclude ".idea" \
  --exclude ".vscode" \
  --exclude ".phpunit.cache" \
  --exclude "node_modules" \
  --exclude "vendor" \
  --exclude "phpunit.xml.dist" \
  --exclude "phpstan.neon" \
  --exclude "phpcs.xml.dist" \
  --exclude "composer.json" \
  --exclude "composer.lock" \
  --exclude ".githooks" \
  --exclude "build-plugin-zip.sh"

echo "Creating ZIP archive..."
(cd "$TMP_DIR" && zip -r "$ZIP_PATH" "$PLUGIN_SLUG")

echo "Created $ZIP_PATH"
