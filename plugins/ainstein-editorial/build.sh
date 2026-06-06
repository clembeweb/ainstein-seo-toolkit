#!/usr/bin/env bash
#
# Ainstein Editorial — Build script (M1.7)
#
# Produce uno zip installabile su WordPress in dist/ainstein-editorial-v{X.Y.Z}.zip.
# Il root dello zip è la cartella `ainstein-editorial/` con:
#   - contenuto di src/ (plugin.php + classi + vendor autoload)
#   - assets/ (CSS/JS compilati)
#   - languages/ (i18n, da M5)
#   - readme.txt (da M6)
#
# Esclude: tests/, docs/, node_modules/, .env*, build.sh, *.log, .git*
#
# Uso:  ./build.sh
#
set -euo pipefail

PLUGIN_SLUG="ainstein-editorial"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC_DIR="${ROOT_DIR}/src"
ASSETS_DIR="${ROOT_DIR}/assets"
LANG_DIR="${ROOT_DIR}/languages"
DIST_DIR="${ROOT_DIR}/dist"

# --- Leggi versione dall'header di plugin.php ---
VERSION="$(grep -i '^\s*\*\?\s*Version:' "${SRC_DIR}/plugin.php" | head -1 | sed -E 's/.*Version:\s*//I' | tr -d '[:space:]')"
if [[ -z "${VERSION}" ]]; then
    echo "ERRORE: impossibile leggere la versione da src/plugin.php" >&2
    exit 1
fi
echo "→ Build Ainstein Editorial v${VERSION}"

# --- Staging dir pulita ---
STAGING="$(mktemp -d)"
PKG_DIR="${STAGING}/${PLUGIN_SLUG}"
mkdir -p "${PKG_DIR}"

# --- Copia src/ nel root del pacchetto ---
echo "→ Copia sorgenti"
cp -R "${SRC_DIR}/." "${PKG_DIR}/"

# --- Copia assets/ e languages/ se esistono ---
if [[ -d "${ASSETS_DIR}" ]]; then
    cp -R "${ASSETS_DIR}" "${PKG_DIR}/assets"
fi
if [[ -d "${LANG_DIR}" ]]; then
    cp -R "${LANG_DIR}" "${PKG_DIR}/languages"
fi
if [[ -f "${ROOT_DIR}/readme.txt" ]]; then
    cp "${ROOT_DIR}/readme.txt" "${PKG_DIR}/readme.txt"
fi

# --- Composer install (no-dev) per generare l'autoloader ottimizzato ---
if command -v composer >/dev/null 2>&1; then
    echo "→ composer install (no-dev)"
    (cd "${PKG_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --quiet 2>/dev/null) \
        || echo "  (composer non eseguito: si userà l'autoloader fallback)"
fi

# --- Rimuovi file non distribuibili ---
echo "→ Pulizia file esclusi"
find "${PKG_DIR}" -type d -name 'tests' -prune -exec rm -rf {} + 2>/dev/null || true
find "${PKG_DIR}" -type d -name 'docs' -prune -exec rm -rf {} + 2>/dev/null || true
find "${PKG_DIR}" -type d -name 'node_modules' -prune -exec rm -rf {} + 2>/dev/null || true
find "${PKG_DIR}" -type f \( -name '.env' -o -name '.env.*' -o -name '*.log' -o -name 'build.sh' \) -delete 2>/dev/null || true
find "${PKG_DIR}" -name '.git*' -exec rm -rf {} + 2>/dev/null || true
rm -f "${PKG_DIR}/composer.lock" 2>/dev/null || true

# --- Crea lo zip ---
mkdir -p "${DIST_DIR}"
ZIP_PATH="${DIST_DIR}/${PLUGIN_SLUG}-v${VERSION}.zip"
rm -f "${ZIP_PATH}"
echo "→ Creazione zip"
(cd "${STAGING}" && zip -rq "${ZIP_PATH}" "${PLUGIN_SLUG}")

# --- Cleanup ---
rm -rf "${STAGING}"

SIZE="$(du -h "${ZIP_PATH}" | cut -f1)"
echo "✓ Pacchetto creato: ${ZIP_PATH} (${SIZE})"
