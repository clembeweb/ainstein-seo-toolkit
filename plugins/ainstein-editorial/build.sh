#!/usr/bin/env bash
# =============================================================================
# Ainstein Editorial — Build script (M1.7)
#
# Produce un .zip installabile su WordPress (Plugins -> Add New -> Upload Plugin).
#
# Uso:
#   cd plugins/ainstein-editorial
#   ./build.sh
#
# Output:
#   dist/ainstein-editorial-v<VERSION>.zip
#
# Variabili env opzionali:
#   PHP             path al binario PHP (default: 'php' nel PATH)
#   COMPOSER        path a composer/composer.phar (default: tenta 'composer' o
#                   /c/laragon/bin/composer/composer.phar)
#
# Esclusioni dal pacchetto:
#   tests/, docs/, node_modules/, .env*, build.sh, *.log, dist/, .git*,
#   composer.lock, CLAUDE.md, README.md (resta solo readme.txt formato WP)
#
# Cross-platform: usa rsync+zip se disponibili (Linux/Mac), altrimenti
# fallback cp + PowerShell Compress-Archive (Windows Git Bash).
# =============================================================================

set -euo pipefail

# Flags
CLEAN_DIST=0
for arg in "$@"; do
    case "$arg" in
        --clean) CLEAN_DIST=1 ;;
        -h|--help)
            cat <<EOF
Uso: $0 [--clean]

  --clean   Rimuove tutti i .zip esistenti in dist/ prima del build
EOF
            exit 0
            ;;
    esac
done

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="ainstein-editorial"
MAIN_FILE="$PLUGIN_DIR/$PLUGIN_SLUG.php"
DIST_DIR="$PLUGIN_DIR/dist"

# --- Validazioni preflight ---
[[ -f "$MAIN_FILE" ]] || { echo "ERROR: main plugin file not found: $MAIN_FILE"; exit 1; }
[[ -f "$PLUGIN_DIR/composer.json" ]] || { echo "ERROR: composer.json not found"; exit 1; }

# --- Estrai version dall'header ---
VERSION=$(grep -E "^\s*\*\s*Version:" "$MAIN_FILE" | head -1 | sed -E 's/^[^:]*:\s*//' | tr -d '[:space:]')
[[ -n "$VERSION" ]] || { echo "ERROR: cannot extract Version from $MAIN_FILE"; exit 1; }

# --- Risoluzione tool ---
PHP="${PHP:-}"
if [[ -z "$PHP" ]]; then
    if command -v php >/dev/null 2>&1; then
        PHP="php"
    elif [[ -x "/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe" ]]; then
        PHP="/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe"
    else
        echo "ERROR: php not found (set env PHP=/path/to/php)"; exit 1
    fi
fi

COMPOSER="${COMPOSER:-}"
if [[ -z "$COMPOSER" ]]; then
    if command -v composer >/dev/null 2>&1; then
        COMPOSER="composer"
    elif [[ -f "/c/laragon/bin/composer/composer.phar" ]]; then
        COMPOSER="$PHP /c/laragon/bin/composer/composer.phar"
    else
        echo "ERROR: composer not found (set env COMPOSER=/path/to/composer)"; exit 1
    fi
fi

echo "Plugin:   $PLUGIN_SLUG"
echo "Version:  $VERSION"
echo "PHP:      $PHP"
echo "Composer: $COMPOSER"
echo "Output:   $DIST_DIR/${PLUGIN_SLUG}-v${VERSION}.zip"
echo

# --- Cleanup precedenti build ---
BUILD_DIR="$DIST_DIR/_build_tmp"
TARGET_DIR="$BUILD_DIR/$PLUGIN_SLUG"
ZIP_PATH="$DIST_DIR/${PLUGIN_SLUG}-v${VERSION}.zip"

mkdir -p "$DIST_DIR"
if [[ "$CLEAN_DIST" == "1" ]]; then
    echo "[clean] Rimozione zip esistenti in dist/"
    find "$DIST_DIR" -maxdepth 1 -name "*.zip" -type f -delete 2>/dev/null || true
fi
rm -rf "$BUILD_DIR" "$ZIP_PATH"
mkdir -p "$TARGET_DIR"

# --- Copia file (esclusioni) ---
echo "[1/4] Copying files..."
# Lista whitelist directory/file da includere
INCLUDE=(
    "$PLUGIN_SLUG.php"
    "composer.json"
    "readme.txt"
    "src"
    "assets"
    "languages"
)
for item in "${INCLUDE[@]}"; do
    src="$PLUGIN_DIR/$item"
    if [[ -e "$src" ]]; then
        cp -r "$src" "$TARGET_DIR/"
    fi
done

# --- Composer install --no-dev nel target ---
echo "[2/4] Running composer install --no-dev..."
(
    cd "$TARGET_DIR"
    # shellcheck disable=SC2086
    $COMPOSER install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1 | tail -5 || {
        echo "ERROR: composer install failed"; exit 1
    }
)

# --- Pulizia ulteriore dentro vendor (se serve) ---
echo "[3/4] Cleaning vendor (removing tests/docs/CI files)..."
if [[ -d "$TARGET_DIR/vendor" ]]; then
    find "$TARGET_DIR/vendor" -type d \( -name 'tests' -o -name 'test' -o -name 'docs' -o -name '.github' \) -prune -exec rm -rf {} +
    find "$TARGET_DIR/vendor" -type f \( -name '.gitignore' -o -name '.gitattributes' -o -name 'phpunit.xml*' -o -name '*.md' \) -delete 2>/dev/null || true
fi

# --- BUILD_INFO.txt (debug / support) ---
BUILD_TS=$(date -Iseconds 2>/dev/null || date -u +"%Y-%m-%dT%H:%M:%SZ")
GIT_SHA=""
if command -v git >/dev/null 2>&1; then
    GIT_SHA=$(git -C "$PLUGIN_DIR" rev-parse --short HEAD 2>/dev/null || echo "unknown")
fi
cat > "$TARGET_DIR/BUILD_INFO.txt" <<EOF
plugin:    $PLUGIN_SLUG
version:   $VERSION
built_at:  $BUILD_TS
git_sha:   ${GIT_SHA:-unknown}
host:      $(hostname 2>/dev/null || echo "unknown")
EOF

# --- Creazione zip ---
echo "[4/4] Creating zip..."
cd "$BUILD_DIR"
if command -v zip >/dev/null 2>&1; then
    zip -rq "$ZIP_PATH" "$PLUGIN_SLUG"
elif command -v powershell.exe >/dev/null 2>&1; then
    # Su Windows: PowerShell Compress-Archive (richiede path Windows-style)
    win_build_dir="$(cygpath -w "$BUILD_DIR" 2>/dev/null || echo "$BUILD_DIR")"
    win_zip_path="$(cygpath -w "$ZIP_PATH" 2>/dev/null || echo "$ZIP_PATH")"
    powershell.exe -NoProfile -NonInteractive -Command "Compress-Archive -Path '${win_build_dir}\\${PLUGIN_SLUG}' -DestinationPath '${win_zip_path}' -Force" 2>&1 | tail -3
else
    echo "ERROR: no zip tool available (install 'zip' or run from Windows with PowerShell)"; exit 1
fi

# --- Cleanup tempdir ---
rm -rf "$BUILD_DIR"

# --- Verifica output ---
if [[ ! -f "$ZIP_PATH" ]]; then
    echo "ERROR: zip not created"; exit 1
fi
SIZE=$(du -h "$ZIP_PATH" | awk '{print $1}')
echo
echo "BUILD OK"
echo "  File: $ZIP_PATH"
echo "  Size: $SIZE"
