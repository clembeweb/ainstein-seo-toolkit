#!/usr/bin/env bash
# ============================================================================
# M1.9.7 — WP Compatibility Matrix : configurazione condivisa
# ----------------------------------------------------------------------------
# Source-ato da setup.sh / smoke.sh / teardown.sh. Non eseguire direttamente.
# Tutto override-abile via env per ambienti diversi da Laragon/Windows.
# ============================================================================

set -euo pipefail

# --- Percorsi ambiente (default Laragon/Windows) ----------------------------
PHP83_BIN="${PHP83_BIN:-/c/laragon/bin/php/php-8.3.22-nts-Win32-vs16-x64/php.exe}"
PHP81_BIN="${PHP81_BIN:-/c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe}"
MYSQL_BIN="${MYSQL_BIN:-/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe}"
WWW_ROOT="${WWW_ROOT:-/c/laragon/www}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"

# --- Percorsi progetto ------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_CLI_PHAR="${WP_CLI_PHAR:-$SCRIPT_DIR/bin/wp-cli.phar}"
PLUGIN_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PLUGIN_ZIP="${PLUGIN_ZIP:-$PLUGIN_ROOT/dist/ainstein-editorial-v0.1.0.zip}"
PLUGIN_SLUG="ainstein-editorial"
EVIDENCE_DIR="${EVIDENCE_DIR:-$PLUGIN_ROOT/docs/milestones/m1-9-7-evidence}"
LOG_DIR="$SCRIPT_DIR/logs"

# --- Credenziali install WP -------------------------------------------------
WP_ADMIN_USER="admin_aied"
WP_ADMIN_PASS="AiedTest_2026!"
WP_ADMIN_EMAIL="admin@aied.test"

# --- Matrice install --------------------------------------------------------
# Formato riga: "N|slug-dir|wp_version|php_key|extra_plugins|extra_theme|note"
#   wp_version : 'latest' oppure versione specifica (es. 6.0)
#   php_key    : 83 | 81  (quale binario PHP usare per l'intero ciclo)
#   extra_plugins : slug wp.org separati da virgola, '-' se nessuno
#   extra_theme   : slug tema wp.org da attivare, '-' se default
MATRIX=(
  "1|wp-compat-1-yoast|latest|83|wordpress-seo|-|Yoast SEO — collisione meta-tag SEO #1"
  "2|wp-compat-2-rankmath|latest|83|seo-by-rank-math|-|RankMath — collisione meta-tag SEO #2"
  "3|wp-compat-3-elementor|latest|83|elementor|hello-elementor|Elementor — page builder, content output"
  "4|wp-compat-4-wp60|6.0|81|-|-|WP 6.0 + PHP 8.1 — backward compat (floor dichiarato 6.0/PHP8.0; PHP 8.0 non disponibile localmente)"
  "5|wp-compat-5-polylang|latest|83|polylang|-|Polylang — multilingua (sostituisce WPML: licenza a pagamento, ADR-029)"
)

# --- Helpers ----------------------------------------------------------------
php_bin_for() { # $1 = php_key
  case "$1" in
    81) echo "$PHP81_BIN" ;;
    *)  echo "$PHP83_BIN" ;;
  esac
}

# wp <args...> : invoca wp-cli con PHP/path correnti ($WP_PHP_BIN, $WP_PATH_DIR)
wp() {
  "$WP_PHP_BIN" "$WP_CLI_PHAR" --path="$WP_PATH_DIR" "$@"
}

mysql_q() { # $1 = SQL
  "$MYSQL_BIN" -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "$1"
}

log()  { printf '\033[0;36m[%s]\033[0m %s\n' "$(date +%H:%M:%S)" "$*"; }
ok()   { printf '\033[0;32m  ✓ %s\033[0m\n' "$*"; }
warn() { printf '\033[0;33m  ⚠ %s\033[0m\n' "$*"; }
err()  { printf '\033[0;31m  ✗ %s\033[0m\n' "$*"; }

ensure_env() {
  [ -f "$WP_CLI_PHAR" ]  || { err "wp-cli.phar mancante: $WP_CLI_PHAR"; exit 1; }
  [ -x "$PHP83_BIN" ] || [ -f "$PHP83_BIN" ] || { err "PHP 8.3 mancante: $PHP83_BIN"; exit 1; }
  [ -f "$PLUGIN_ZIP" ]   || { err "ZIP plugin mancante: $PLUGIN_ZIP"; exit 1; }
  mkdir -p "$EVIDENCE_DIR" "$LOG_DIR"
}
