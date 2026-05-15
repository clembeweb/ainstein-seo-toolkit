# M1.9.7 — WP Compatibility Matrix (piano di esecuzione)

> **Stato**: piano scritto, esecuzione richiede ~30 min utente con wp-cli installato.
> **Goal**: validare che il plugin Ainstein Editorial v0.1.0 non rompa i 5 plugin più comuni nei siti WP italiani.
> **Trigger fail**: una recensione 1★ "rompe Yoast" al lancio affossa la conversione per settimane.

---

## 1. Setup ambiente (one-time)

### Pre-requisiti

- Laragon attivo con MySQL su `127.0.0.1:3306`
- WP-CLI installato globalmente (verifica: `wp --version` — se manca: scarica da `https://wp-cli.org/`)
- ZIP plugin pronto: `plugins/ainstein-editorial/dist/ainstein-editorial-v0.1.0.zip`

### Script automatico

Posizione: `plugins/ainstein-editorial/tests/wp-compat/setup.sh`.

Esegui da Git Bash:

```bash
bash plugins/ainstein-editorial/tests/wp-compat/setup.sh
```

Lo script crea 5 install WP locali in `C:\laragon\www\wp-compat-{N}\`:

| # | Install | Stack | Scopo |
|---|---------|-------|-------|
| 1 | `wp-compat-1-yoast` | WP latest + Yoast SEO | Test meta-tag collision con SEO plugin #1 |
| 2 | `wp-compat-2-rankmath` | WP latest + RankMath | Test con SEO plugin #2 |
| 3 | `wp-compat-3-elementor` | WP latest + Elementor + Hello theme | Test content output con page builder |
| 4 | `wp-compat-4-wp60` | WP 6.0 + PHP 8.0 minimum | Test backward compat target dichiarato |
| 5 | `wp-compat-5-wpml` | WP latest + WPML (trial) | Test multilingua (skip se WPML license assente) |

Per ogni install lo script:
1. Crea DB `wpcompat{N}` su MySQL
2. Scarica WP (versione specifica per #4)
3. Lancia `wp core install` con admin `admin_aied / AiedTest_2026!`
4. Installa plugin SEO/builder specifico
5. Installa Ainstein Editorial via `wp plugin install <zip>`
6. Attiva tutti i plugin
7. Esegue smoke test: `wp eval "var_dump(get_option('aied_plugin_version'));"`

### Manual: cosa testare per ogni install (5-7 min ognuno)

Login a `http://localhost/wp-compat-{N}/wp-admin/` con `admin_aied / AiedTest_2026!`.

| Step | Atteso | Note se fallisce |
|------|--------|------------------|
| Plugin admin sidebar visibile "Ainstein Editorial" | ✅ | Conflitto menu? screenshot |
| License page rendering | ✅ no PHP warning visibili | Conflitto CSS? screenshot |
| Inserisci `TEST-STARTER-XXXX-1111` + email + attiva | ✅ "License attivata correttamente" | DB conflict? `wp db query "SELECT * FROM wp_options WHERE option_name LIKE 'aied_%'"` |
| Crea nuovo post WP > Salva bozza | ✅ no fatal | Conflitto save_post hook? |
| Apri post esistente (con Yoast/RankMath/Elementor) | ✅ editor carica normalmente | Plugin rompe il page builder? |
| Dashboard WP > nessun PHP warning/notice | ✅ | Logga in `wp-content/debug.log` |

### Report template

Posizione: `plugins/ainstein-editorial/docs/milestones/m1-9-7-evidence/`.

Per ogni install crea file `wp-compat-{N}-{stack}.md`:

```markdown
# WP Compat Test — Install #{N} ({stack})

**Data**: 2026-MM-DD
**WP version**: X.Y.Z
**PHP version**: X.Y.Z
**Plugin AE version**: 0.1.0

## Esito complessivo
✅ PASS / ⚠️ PASS con issues / ❌ FAIL

## Smoke test results
| Step | Esito | Note |
|------|-------|------|
| Sidebar visibile | | |
| License page render | | |
| Activation | | |
| Crea post | | |
| Apri post esistente | | |
| Dashboard no warning | | |

## Issue riscontrate
(elenca con severity: critical/important/minor + screenshot)

## debug.log estratto
(incolla righe rilevanti se presenti)

## Conclusione
- Procedibile come è / fix necessario prima di M2
```

---

## 2. Decision gate

Esito complessivo da consolidare in `m1-9-7-evidence/_summary.md`:

| Risultato | Azione |
|-----------|--------|
| 5/5 PASS senza issues | ✅ M1.9.7 chiuso. Procedi M2. |
| 4-5/5 PASS con minor issues | ⚠️ Documenta, apri task M2.X per fix incrementale. Procedi M2. |
| 1+ FAIL critical (fatal/rompe plugin altrui) | ❌ Stop M2. Fix prima. |
| 1+ FAIL important (warning/UX broken ma non fatal) | ⚠️ Valuta caso per caso. Fix immediato se è regressione, defer altrimenti. |

---

## 3. Quando eseguire

**Pre-requisito M2 — settimana 1**. M2 inizia con Content Brain scan, che scrive `aied_content_brain` e tocca `wp_postmeta`. Senza compat test, una collision con SEO plugin può emergere tardi e essere costosa.

Lo script `setup.sh` viene generato in una sessione dedicata: richiede ~2-3h di lavoro per scriverlo correttamente con wp-cli (download WP, install, activate, smoke). Posto fattibile in sessione dedicata "M1.9.7 setup" prima di iniziare M2.

---

## 4. Risk se skipped

- **Reputational**: review "non funziona con Yoast" / "rompe Elementor" affossano conversion.
- **Support cost**: ticket "non funziona sul mio sito" prima settimana = 80% del support load. La compat matrix prevenibile.
- **Refund risk**: Lemon Squeezy refund automatico = costo diretto.

---

*M1.9.7 pianificato, esecuzione schedulabile. Decisione cliente: tu scegli se eseguirlo prima di M2.*
