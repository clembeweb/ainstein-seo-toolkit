# tests/wp-compat — M1.9.7 WP Compatibility Matrix

Suite di test compatibilità: verifica che **Ainstein Editorial v0.1.0** non vada
in fatal né collida con i plugin più diffusi nei siti WP italiani (Yoast,
RankMath, Elementor, Polylang) e sul floor dichiarato (WP 6.0 / PHP 8.x).

## Prerequisiti

- Laragon attivo (MySQL su `127.0.0.1:3306`, root senza password)
- PHP 8.3 + 8.1 in `C:\laragon\bin\php\` (default Laragon)
- ZIP plugin: `dist/ainstein-editorial-v0.1.0.zip` (prodotto da `build.sh`)
- `bin/wp-cli.phar` — scaricato automaticamente la prima volta:
  ```bash
  curl -sSL -o bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  ```

`bin/` è gitignored (binario runtime). Tutti i path sono override-abili via env
(vedi `config.sh`).

## Uso

```bash
cd plugins/ainstein-editorial/tests/wp-compat

bash setup.sh            # provisiona le 5 install WP in C:\laragon\www\wp-compat-*
bash smoke.sh            # smoke headless + report in docs/milestones/m1-9-7-evidence/
bash teardown.sh         # rimuove install + DB (il codice plugin non viene toccato)

FRESH=1 bash setup.sh    # ricrea tutte da zero
bash setup.sh 3          # solo install #3
bash smoke.sh 3          # smoke solo #3
```

## Matrice

| # | Dir | WP | PHP | Stack | Scopo |
|---|-----|----|----|-------|-------|
| 1 | wp-compat-1-yoast | latest | 8.3 | Yoast SEO | collisione meta-tag SEO #1 |
| 2 | wp-compat-2-rankmath | latest | 8.3 | RankMath | collisione meta-tag SEO #2 |
| 3 | wp-compat-3-elementor | latest | 8.3 | Elementor + Hello | page builder, content output |
| 4 | wp-compat-4-wp60 | 6.0 | 8.1 | nudo | backward compat floor |
| 5 | wp-compat-5-polylang | latest | 8.3 | Polylang | multilingua (proxy WPML) |

Deviazioni vs piano originale (PHP 8.0→8.1, WPML→Polylang) loggate in **ADR-029**
e in `_summary.md` generato.

## Cosa rileva lo smoke headless

- Fatal PHP a bootstrap WP con plugin AE + stack attivi insieme
- Mancata autoload classe `Ainstein\Editorial\Includes\Plugin`
- `aied_plugin_version` non settata (activation hook non eseguito)
- Fatal/errori su `admin_init` + `admin_menu` (hook condivisi con Yoast/RankMath/Elementor)
- Collisione `save_post` (crea/elimina draft)
- Righe in `wp-content/debug.log` attribuibili al plugin (vs deprecation core)

Il rendering UI admin (License page, dashboard) si verifica con browser
spot-check separato; gli screenshot vanno in `m1-9-7-evidence/`.
