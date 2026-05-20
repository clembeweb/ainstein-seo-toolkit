# M1.9.7 — WP Compatibility Matrix : Summary & Decision Gate

**Data esecuzione**: 2026-05-20
**Plugin**: Ainstein Editorial v0.1.0 (`dist/ainstein-editorial-v0.1.0.zip`)
**Metodo**: smoke headless via wp-cli 2.12 — rileva fatal/collisioni a bootstrap, admin_init/admin_menu, save_post + scan debug.log con attribuzione plugin-vs-core.

## Risultati

| # | Stack | Esito |
|---|-------|-------|
| #1 | yoast | ✅ PASS |
| #2 | rankmath | ✅ PASS |
| #3 | elementor | ✅ PASS |
| #4 | wp60 | ⚠️ PASS con note |
| #5 | polylang | ✅ PASS |

**Totale**: 4 PASS · 1 PASS-con-note · 0 FAIL (su 5)

## Decision gate

⚠️ **GO M2** — 5/5 PASS (4 strict, 1 con note WP/core non-bloccanti). Nessuna issue attribuibile al plugin. M1.9.7 chiuso.

| Risultato | Azione (da piano M1-9-7) |
|-----------|--------------------------|
| 5/5 PASS | ✅ M1.9.7 chiuso. Procedi M2. |
| 4-5/5 PASS con minor issue | ⚠️ Documenta, task M2.X incrementale. Procedi M2. |
| 1+ FAIL critical | ❌ Stop M2. Fix prima. |

## Deviazioni dal piano originale (loggate in ADR-029)

- **Install #4 PHP 8.0 → 8.1**: PHP 8.0 (EOL nov 2023) non disponibile su ambiente Laragon locale; minimo presente 8.1.10. Testato su 8.1 + WP 6.0 (floor dichiarato nell'header plugin). Caveat: una regressione PHP-8.0-only non sarebbe catturata; rischio basso (8.0 EOL, share residuale).
- **Install #5 WPML → Polylang**: WPML richiede licenza commerciale assente. Polylang (free, ~700k install attive, dominante nel mercato WP italiano) usato come proxy per il test di collisione multilingua.

## Note metodo

Lo smoke è **headless** (wp-cli): copre il rischio primario — fatal PHP e collisioni hook (save_post, admin_init, admin_menu) che generano le recensioni "rompe Yoast/Elementor". Il rendering UI admin (License page, dashboard) è verificato con browser spot-check separato su install rappresentative (screenshot in questa cartella se presenti: `*-license.png`, `*-dashboard.png`).

---
*Generato da `tests/wp-compat/smoke.sh`. Rigenerabile: `bash setup.sh && bash smoke.sh`.*
