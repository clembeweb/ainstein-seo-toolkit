#!/usr/bin/env bash
# ============================================================================
# M1.9.7 — WP Compatibility Matrix : provisioning 5 install WP
# ----------------------------------------------------------------------------
# Crea 5 install WP locali in $WWW_ROOT con stack diversi + plugin AE installato.
# Idempotente: salta install gia' presenti. FRESH=1 per ricrearle da zero.
#
#   bash setup.sh            # provisiona quelle mancanti
#   FRESH=1 bash setup.sh    # drop + ricrea tutte
#   bash setup.sh 3          # solo install #3
# ============================================================================

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/config.sh"
ensure_env

ONLY="${1:-}"            # se passato, provisiona solo quell'indice
FRESH="${FRESH:-0}"

provision_one() {
  local row="$1"
  IFS='|' read -r N DIR WPVER PHPKEY EXTRA_PLUGINS EXTRA_THEME NOTE <<< "$row"

  WP_PATH_DIR="$WWW_ROOT/$DIR"
  WP_PHP_BIN="$(php_bin_for "$PHPKEY")"
  local db="wpcompat$N"
  local url="http://localhost/$DIR"
  local logf="$LOG_DIR/setup-$N-$DIR.log"
  : > "$logf"

  log "Install #$N — $DIR (WP=$WPVER PHP=$PHPKEY) — $NOTE"

  if [ -d "$WP_PATH_DIR" ] && [ "$FRESH" != "1" ]; then
    if wp core is-installed >>"$logf" 2>&1; then
      ok "gia' provisionata (skip — usa FRESH=1 per ricreare)"
      return 0
    fi
    warn "dir presente ma WP non installato → ricreo"
    FRESH=1
  fi

  if [ "$FRESH" = "1" ]; then
    log "  cleanup precedente (db $db + dir)"
    mysql_q "DROP DATABASE IF EXISTS \`$db\`;" >>"$logf" 2>&1 || true
    rm -rf "$WP_PATH_DIR" 2>>"$logf" || true
  fi

  mkdir -p "$WP_PATH_DIR"
  mysql_q "CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >>"$logf" 2>&1

  log "  wp core download (WP=$WPVER)"
  local dlargs=(core download --locale=en_US --force)
  [ "$WPVER" != "latest" ] && dlargs+=(--version="$WPVER")
  wp "${dlargs[@]}" >>"$logf" 2>&1 || { err "core download FAILED (vedi $logf)"; return 1; }

  log "  wp config create (+ WP_DEBUG log)"
  wp config create \
    --dbname="$db" --dbuser="$DB_USER" --dbpass="$DB_PASS" \
    --dbhost="$DB_HOST" --locale=en_US --skip-check \
    --extra-php <<'PHP' >>"$logf" 2>&1 || { err "config create FAILED"; return 1; }
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', '0' );
// Pin versione: blocca auto-update core/plugin/theme. Senza questo WP 6.0
// (install #4) si auto-aggiorna a latest e il test backward-compat è nullo.
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );
PHP

  log "  wp core install ($url)"
  wp core install \
    --url="$url" --title="WP Compat $N — $DIR" \
    --admin_user="$WP_ADMIN_USER" --admin_password="$WP_ADMIN_PASS" \
    --admin_email="$WP_ADMIN_EMAIL" --skip-email >>"$logf" 2>&1 \
    || { err "core install FAILED (vedi $logf)"; return 1; }

  if [ "$EXTRA_THEME" != "-" ]; then
    log "  wp theme install $EXTRA_THEME --activate"
    wp theme install "$EXTRA_THEME" --activate >>"$logf" 2>&1 \
      || warn "theme $EXTRA_THEME install fallito (continuo)"
  fi

  if [ "$EXTRA_PLUGINS" != "-" ]; then
    IFS=',' read -ra plist <<< "$EXTRA_PLUGINS"
    for p in "${plist[@]}"; do
      log "  wp plugin install $p --activate"
      wp plugin install "$p" --activate >>"$logf" 2>&1 \
        || { err "plugin $p install/activate FAILED (vedi $logf)"; return 1; }
    done
  fi

  log "  wp plugin install <AE zip> --activate"
  # Path Windows nativo per wp-cli (converte /c/... → C:\...)
  local zip_win
  zip_win="$(cygpath -w "$PLUGIN_ZIP" 2>/dev/null || echo "$PLUGIN_ZIP")"
  wp plugin install "$zip_win" --activate >>"$logf" 2>&1 \
    || { err "Ainstein Editorial install/activate FAILED (vedi $logf)"; return 1; }

  ok "provisionata. Attivi: $(wp plugin list --status=active --field=name 2>/dev/null | tr '\n' ' ')"
  return 0
}

# ---- Loop matrice ----------------------------------------------------------
ensure_env
set +e
declare -a RESULTS
for row in "${MATRIX[@]}"; do
  N="${row%%|*}"
  if [ -n "$ONLY" ] && [ "$ONLY" != "$N" ]; then continue; fi
  if provision_one "$row"; then RESULTS+=("#$N OK"); else RESULTS+=("#$N FAIL"); fi
done

echo
log "=== Riepilogo provisioning ==="
for r in "${RESULTS[@]}"; do echo "  $r"; done
echo
log "Prossimo: bash smoke.sh   (esegue la matrix di smoke test + report evidence)"
