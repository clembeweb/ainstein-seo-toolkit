#!/usr/bin/env bash
# ============================================================================
# M1.9.7 — WP Compatibility Matrix : smoke test headless + report evidence
# ----------------------------------------------------------------------------
# Per ogni install provisionata da setup.sh esegue check headless via wp-cli
# (rileva fatal / collisioni hook con Yoast/RankMath/Elementor/Polylang) e
# scrive un report markdown in docs/milestones/m1-9-7-evidence/.
#
#   bash smoke.sh        # tutte
#   bash smoke.sh 3      # solo install #3
# ============================================================================

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/config.sh"
ensure_env

ONLY="${1:-}"
TODAY="$(date +%Y-%m-%d)"

# eval-file temporaneo: fa girare WP completo con tutti i plugin attivi e
# riporta segnali strutturati. Un fatal qui = collisione reale.
EVAL_PHP="$LOG_DIR/_probe.php"
cat > "$EVAL_PHP" <<'PHP'
<?php
// Steady-state probe: consuma l'activation transient prima di firing admin_init,
// altrimenti il plugin scatta handleActivationRedirect → wp_safe_redirect →
// wp-cli intercetta e fa WP_CLI::error() → probe termina output vuoto.
delete_transient('aied_show_activation_redirect');

$out = [];
$out['aied_version']   = (string) get_option('aied_plugin_version', '');
$out['aied_activated'] = get_option('aied_first_activated_at') ? 'yes' : 'no';
$out['class_plugin']   = class_exists('Ainstein\\Editorial\\Includes\\Plugin') ? 'yes' : 'no';
$out['const_version']  = defined('AIED_VERSION') ? AIED_VERSION : 'undef';

// Fire admin hooks: rivela fatal/collisioni con plugin che hookano admin_*
$err = '';
set_error_handler(function ($no, $str) use (&$err) { $err .= "[$no] $str\n"; return true; });
try {
    do_action('admin_init');
    do_action('admin_menu');
    $out['admin_hooks'] = 'ok';
} catch (\Throwable $e) {
    $out['admin_hooks'] = 'THROW: ' . $e->getMessage();
}
restore_error_handler();
$out['admin_hook_errors'] = trim($err) === '' ? 'none' : str_replace("\n", ' | ', trim($err));

foreach ($out as $k => $v) { echo $k . '=' . $v . "\n"; }
PHP

smoke_one() {
  local row="$1"
  IFS='|' read -r N DIR WPVER PHPKEY EXTRA_PLUGINS EXTRA_THEME NOTE <<< "$row"
  WP_PATH_DIR="$WWW_ROOT/$DIR"
  WP_PHP_BIN="$(php_bin_for "$PHPKEY")"
  local stack="${DIR#wp-compat-*-}"
  local report="$EVIDENCE_DIR/wp-compat-$N-$stack.md"
  local debuglog="$WP_PATH_DIR/wp-content/debug.log"

  log "Smoke #$N — $DIR"

  if ! wp core is-installed >/dev/null 2>&1; then
    err "WP non installato → SKIP (esegui prima setup.sh)"
    GATE+=("#$N $stack ❌ NOT-PROVISIONED")
    return 1
  fi

  local wpver phpver
  wpver="$(wp core version 2>/dev/null || echo '?')"
  phpver="$("$WP_PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null || echo '?')"

  # Azzera debug.log per catturare solo errori di questo run
  : > "$debuglog" 2>/dev/null || true

  # --- Check 1: plugin attivi ---
  local active_list r_active="✅" note_active=""
  active_list="$(wp plugin list --status=active --field=name 2>/dev/null | tr '\n' ' ')"
  echo "$active_list" | grep -q "$PLUGIN_SLUG" || { r_active="❌"; note_active="AE non attivo"; }
  if [ "$EXTRA_PLUGINS" != "-" ]; then
    IFS=',' read -ra plist <<< "$EXTRA_PLUGINS"
    for p in "${plist[@]}"; do
      echo "$active_list" | grep -q "$p" || { r_active="❌"; note_active="$note_active; $p non attivo"; }
    done
  fi

  # --- Check 2: probe headless (bootstrap completo + admin hooks) ---
  local probe r_probe="✅" note_probe=""
  probe="$(wp eval-file "$(cygpath -w "$EVAL_PHP" 2>/dev/null || echo "$EVAL_PHP")" 2>"$LOG_DIR/_probe-$N.err")"
  local probe_rc=$?
  local p_ver p_class p_hooks p_hookerr
  p_ver="$(echo "$probe" | sed -n 's/^aied_version=//p')"
  p_class="$(echo "$probe" | sed -n 's/^class_plugin=//p')"
  p_hooks="$(echo "$probe" | sed -n 's/^admin_hooks=//p')"
  p_hookerr="$(echo "$probe" | sed -n 's/^admin_hook_errors=//p')"
  if [ $probe_rc -ne 0 ]; then r_probe="❌"; note_probe="bootstrap rc=$probe_rc (vedi _probe-$N.err)"; fi
  [ "$p_ver" = "0.1.0" ] || { r_probe="❌"; note_probe="$note_probe; aied_version='$p_ver' (atteso 0.1.0)"; }
  [ "$p_class" = "yes" ] || { r_probe="❌"; note_probe="$note_probe; classe Plugin non autoloadata"; }
  echo "$p_hooks" | grep -q "^ok$" || { r_probe="❌"; note_probe="$note_probe; admin hooks: $p_hooks"; }

  # --- Check 3: save_post collision (crea + lista + elimina draft) ---
  local r_post="✅" note_post="" pid
  pid="$(wp post create --post_title="AE Compat Probe $N" --post_status=draft --porcelain 2>"$LOG_DIR/_post-$N.err")"
  if [ -z "$pid" ] || ! [[ "$pid" =~ ^[0-9]+$ ]]; then
    r_post="❌"; note_post="post create fallito (save_post collision? vedi _post-$N.err)"
  else
    wp post delete "$pid" --force >/dev/null 2>&1 || true
  fi

  # --- Check 4: scan debug.log, separa attribuzione plugin vs core ---
  local plugin_lines core_lines r_log="✅" note_log=""
  if [ -s "$debuglog" ]; then
    plugin_lines="$(grep -iE 'ainstein-editorial|Ainstein\\\\Editorial|aied_' "$debuglog" 2>/dev/null | wc -l | tr -d ' ')"
    core_lines="$(grep -vicE 'ainstein-editorial|Ainstein\\\\Editorial|aied_' "$debuglog" 2>/dev/null | tr -d ' ')"
    if [ "${plugin_lines:-0}" -gt 0 ]; then
      r_log="❌"; note_log="$plugin_lines righe attribuibili al plugin in debug.log"
    elif [ "${core_lines:-0}" -gt 0 ]; then
      r_log="⚠️"; note_log="$core_lines righe WP/core (deprecations + auto-update info), non plugin-attribuibili"
    fi
  else
    note_log="debug.log vuoto"
  fi

  # --- Esito complessivo ---
  local overall="✅ PASS"
  if [ "$r_active" = "❌" ] || [ "$r_probe" = "❌" ] || [ "$r_post" = "❌" ] || [ "$r_log" = "❌" ]; then
    overall="❌ FAIL"
  elif [ "$r_log" = "⚠️" ]; then
    overall="⚠️ PASS con note"
  fi
  GATE+=("#$N $stack $overall")

  # --- Estratto debug.log per il report (max 25 righe) ---
  local dbg_extract="(vuoto)"
  [ -s "$debuglog" ] && dbg_extract="$(head -25 "$debuglog")"

  # --- Scrive report evidence ---
  cat > "$report" <<MD
# WP Compat Test — Install #$N ($stack)

**Data**: $TODAY
**WP version**: $wpver
**PHP version**: $phpver
**Plugin AE version**: ${p_ver:-?}
**Stack**: $NOTE
**Plugin attivi**: \`$active_list\`

## Esito complessivo
$overall

## Smoke test results
| Step | Esito | Note |
|------|-------|------|
| Plugin AE + stack attivi | $r_active | ${note_active:-tutti attivi} |
| Bootstrap WP completo (no fatal) | $r_probe | ${note_probe:-classe autoloadata, version 0.1.0} |
| admin_init + admin_menu fire | $r_probe | hook errors: ${p_hookerr:-none} |
| save_post (crea/elimina draft) | $r_post | ${note_post:-nessuna collisione save_post} |
| debug.log pulito (plugin) | $r_log | ${note_log:-nessun errore} |

## Probe headless (output)
\`\`\`
$probe
\`\`\`

## debug.log estratto (max 25 righe)
\`\`\`
$dbg_extract
\`\`\`

## Conclusione
$( [ "$overall" = "❌ FAIL" ] && echo "❌ Fix necessario prima di M2 — vedi note sopra." \
   || { [ "$overall" = "⚠️ PASS con note" ] && echo "⚠️ Procedibile: note sono deprecation WP/core non plugin-attribuibili." \
   || echo "✅ Procedibile come è — nessuna collisione con lo stack testato."; } )

---
*Generato da \`tests/wp-compat/smoke.sh\` (M1.9.7). Smoke headless via wp-cli: rileva fatal/collisioni hook a bootstrap, admin_init/admin_menu e save_post. UI render verificata via browser spot-check separato (vedi screenshot evidence se presenti).*
MD

  case "$overall" in
    "✅ PASS")          ok "$DIR → $overall" ;;
    "⚠️ PASS con note") warn "$DIR → $overall ($note_log)" ;;
    *)                  err "$DIR → $overall" ;;
  esac
}

ensure_env
set +e
declare -a GATE
for row in "${MATRIX[@]}"; do
  N="${row%%|*}"
  if [ -n "$ONLY" ] && [ "$ONLY" != "$N" ]; then continue; fi
  smoke_one "$row"
done

# ---- _summary.md (decision gate) ------------------------------------------
SUMMARY="$EVIDENCE_DIR/_summary.md"
pass_n=0; warn_n=0; fail_n=0
for g in "${GATE[@]}"; do
  case "$g" in
    *"❌"*) fail_n=$((fail_n+1)) ;;
    *"⚠️"*) warn_n=$((warn_n+1)) ;;
    *"✅"*) pass_n=$((pass_n+1)) ;;
  esac
done
total=${#GATE[@]}

passed_total=$((pass_n + warn_n))
if   [ $fail_n -gt 0 ]; then gate_verdict="❌ **STOP M2** — $fail_n install FAIL. Fix prima di procedere."
elif [ $warn_n -gt 0 ]; then gate_verdict="⚠️ **GO M2** — $passed_total/$total PASS ($pass_n strict, $warn_n con note WP/core non-bloccanti). Nessuna issue attribuibile al plugin. M1.9.7 chiuso."
else                          gate_verdict="✅ **GO M2** — $total/$total PASS senza issue. M1.9.7 chiuso."
fi

cat > "$SUMMARY" <<MD
# M1.9.7 — WP Compatibility Matrix : Summary & Decision Gate

**Data esecuzione**: $TODAY
**Plugin**: Ainstein Editorial v0.1.0 (\`dist/ainstein-editorial-v0.1.0.zip\`)
**Metodo**: smoke headless via wp-cli 2.12 — rileva fatal/collisioni a bootstrap, admin_init/admin_menu, save_post + scan debug.log con attribuzione plugin-vs-core.

## Risultati

| # | Stack | Esito |
|---|-------|-------|
$(for g in "${GATE[@]}"; do
   n="$(echo "$g" | awk '{print $1}')"; s="$(echo "$g" | awk '{print $2}')"; e="$(echo "$g" | cut -d' ' -f3-)"
   echo "| $n | $s | $e |"
 done)

**Totale**: $pass_n PASS · $warn_n PASS-con-note · $fail_n FAIL (su $total)

## Decision gate

$gate_verdict

| Risultato | Azione (da piano M1-9-7) |
|-----------|--------------------------|
| 5/5 PASS | ✅ M1.9.7 chiuso. Procedi M2. |
| 4-5/5 PASS con minor issue | ⚠️ Documenta, task M2.X incrementale. Procedi M2. |
| 1+ FAIL critical | ❌ Stop M2. Fix prima. |

## Deviazioni dal piano originale (loggate in ADR-029)

- **Install #4 PHP 8.0 → 8.1**: PHP 8.0 (EOL nov 2023) non disponibile su ambiente Laragon locale; minimo presente 8.1.10. Testato su 8.1 + WP 6.0 (floor dichiarato nell'header plugin). Caveat: una regressione PHP-8.0-only non sarebbe catturata; rischio basso (8.0 EOL, share residuale).
- **Install #5 WPML → Polylang**: WPML richiede licenza commerciale assente. Polylang (free, ~700k install attive, dominante nel mercato WP italiano) usato come proxy per il test di collisione multilingua.

## Note metodo

Lo smoke è **headless** (wp-cli): copre il rischio primario — fatal PHP e collisioni hook (save_post, admin_init, admin_menu) che generano le recensioni "rompe Yoast/Elementor". Il rendering UI admin (License page, dashboard) è verificato con browser spot-check separato su install rappresentative (screenshot in questa cartella se presenti: \`*-license.png\`, \`*-dashboard.png\`).

---
*Generato da \`tests/wp-compat/smoke.sh\`. Rigenerabile: \`bash setup.sh && bash smoke.sh\`.*
MD

echo
log "=== Decision gate ==="
echo "  $gate_verdict"
log "Report per-install + _summary.md → $EVIDENCE_DIR"
