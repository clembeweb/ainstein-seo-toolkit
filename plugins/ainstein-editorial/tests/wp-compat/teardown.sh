#!/usr/bin/env bash
# ============================================================================
# M1.9.7 — WP Compatibility Matrix : teardown (drop DB + rm install dirs)
# ----------------------------------------------------------------------------
#   bash teardown.sh         # rimuove TUTTE le install wp-compat-*
#   bash teardown.sh 3       # solo install #3
# Le install sono fuori dal repo ($WWW_ROOT), questo non tocca il codice plugin.
# ============================================================================

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/config.sh"
ONLY="${1:-}"
set +e

for row in "${MATRIX[@]}"; do
  IFS='|' read -r N DIR _ _ _ _ _ <<< "$row"
  if [ -n "$ONLY" ] && [ "$ONLY" != "$N" ]; then continue; fi
  log "Teardown #$N — $DIR"
  mysql_q "DROP DATABASE IF EXISTS \`wpcompat$N\`;" >/dev/null 2>&1 && ok "DB wpcompat$N drop"
  if [ -d "$WWW_ROOT/$DIR" ]; then
    rm -rf "$WWW_ROOT/$DIR" 2>/dev/null && ok "dir $DIR rimossa" || warn "dir $DIR: rimozione parziale (file in lock?)"
  else
    ok "dir $DIR già assente"
  fi
done
log "Teardown completato."
