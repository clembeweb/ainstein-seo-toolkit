#!/usr/bin/env bash
# =============================================================================
# Ainstein Editorial — Version bumper (M1.7 fix)
#
# Aggiorna in un colpo solo tutti i 4 punti dove vive la version del plugin:
#   1. ainstein-editorial.php       header  "Version: X.Y.Z"
#   2. ainstein-editorial.php       define  "AIED_VERSION", 'X.Y.Z'
#   3. readme.txt                   "Stable tag: X.Y.Z"
#   4. readme.txt                   prepend changelog stub  "= X.Y.Z ="
#
# Uso:
#   ./bump-version.sh 0.2.0
#
# Validazione: accetta solo semver X.Y.Z (o X.Y.Z-suffix per pre-release).
# =============================================================================

set -euo pipefail

NEW_VERSION="${1:-}"

if [[ -z "$NEW_VERSION" ]]; then
    echo "Uso: $0 <nuova-versione>"
    echo "Esempio: $0 0.2.0"
    exit 1
fi

# Validazione semver (X.Y.Z opzionalmente con suffix tipo -beta1, -rc2)
if ! [[ "$NEW_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9]+)?$ ]]; then
    echo "ERROR: versione non valida '$NEW_VERSION'"
    echo "Formato richiesto: X.Y.Z oppure X.Y.Z-suffix (es. 0.2.0, 1.0.0-beta1)"
    exit 1
fi

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_FILE="$PLUGIN_DIR/ainstein-editorial.php"
README="$PLUGIN_DIR/readme.txt"

[[ -f "$MAIN_FILE" ]] || { echo "ERROR: $MAIN_FILE non trovato"; exit 1; }
[[ -f "$README" ]]    || { echo "ERROR: $README non trovato"; exit 1; }

# Estrai versione corrente (per il diff log)
CURRENT=$(grep -E "^\s*\*\s*Version:" "$MAIN_FILE" | head -1 | sed -E 's/^[^:]*:\s*//' | tr -d '[:space:]')
echo "Versione corrente: $CURRENT"
echo "Nuova versione:    $NEW_VERSION"
echo

if [[ "$CURRENT" == "$NEW_VERSION" ]]; then
    echo "WARNING: nuova versione identica alla corrente. Niente da fare."
    exit 0
fi

# Differenze sed: usa | come delimiter per evitare conflitti con eventuali path
# Bash sed cross-platform (GNU + BSD): -i'' richiede backup file su BSD/macOS.
# Workaround: scriviamo su tmp e poi mv.

sed_replace() {
    local file="$1"
    local pattern="$2"
    local replacement="$3"
    local tmp="${file}.tmp"
    sed -E "s|${pattern}|${replacement}|" "$file" > "$tmp"
    mv "$tmp" "$file"
}

# 1. Header "Version: X.Y.Z"
sed_replace "$MAIN_FILE" \
    "^(\s*\*\s*Version:\s*)[^[:space:]]+" \
    "\\1${NEW_VERSION}"
echo "[OK] header Version → $NEW_VERSION"

# 2. define('AIED_VERSION', 'X.Y.Z')
sed_replace "$MAIN_FILE" \
    "(define\(\s*'AIED_VERSION'\s*,\s*')[^']+(')" \
    "\\1${NEW_VERSION}\\2"
echo "[OK] define AIED_VERSION → $NEW_VERSION"

# 3. readme.txt "Stable tag: X.Y.Z"
sed_replace "$README" \
    "^(Stable tag:\s*)[^[:space:]]+" \
    "\\1${NEW_VERSION}"
echo "[OK] readme Stable tag → $NEW_VERSION"

# 4. Prepend changelog entry (sotto la riga "== Changelog ==")
if grep -q "^= ${NEW_VERSION} =" "$README"; then
    echo "[SKIP] changelog entry per $NEW_VERSION già presente"
else
    TODAY=$(date -I 2>/dev/null || date +%Y-%m-%d)
    # Inserisce dopo la riga "== Changelog =="
    awk -v ver="$NEW_VERSION" -v today="$TODAY" '
        /^== Changelog ==/ {
            print
            print ""
            print "= " ver " ="
            print "* TODO: descrivi cambiamenti (rilascio " today ")."
            next
        }
        { print }
    ' "$README" > "${README}.tmp" && mv "${README}.tmp" "$README"
    echo "[OK] readme changelog: aggiunto stub per $NEW_VERSION"
fi

# Verifica finale: confronto post-update
echo
echo "Verifica post-bump:"
echo "  Header:     $(grep -E '^\s*\*\s*Version:' "$MAIN_FILE" | head -1 | xargs)"
echo "  Define:     $(grep -E "AIED_VERSION'\s*," "$MAIN_FILE" | head -1 | xargs)"
echo "  Stable tag: $(grep -E '^Stable tag:' "$README" | head -1 | xargs)"
echo
echo "BUMP OK → $NEW_VERSION"
echo "Prossimo step: edita la nota changelog in readme.txt, poi ./build.sh"
