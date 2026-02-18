# Dashboard Redesign - "Smart Actions"

**Data**: 2026-02-18
**Stato**: Approvato

---

## Problema

La dashboard attuale e caotica e non guida l'utente. Presenta 8 sezioni che competono per l'attenzione, le statistiche occupano il 70% dello spazio, e l'onboarding/suggerimenti sono sepolti in fondo alla pagina.

## Soluzione

Dashboard a due modalita:
- **Launchpad** (utenti nuovi, 0 progetti): guida task-oriented per iniziare
- **Task Manager** (utenti attivi, 1+ progetti): azioni contestuali + moduli compatti

## Logica di Switch

```php
$isNewUser = ($projectsCount ?? 0) === 0;
```

## Header (comune)

Una riga compatta:
- Sinistra: "Buongiorno, Nome!"
- Destra: Badge crediti con icona (`50.0 crediti`)
  - Utente attivo: aggiunge `| Usati oggi: 3.2 | Mese: 28.5`
  - Crediti < 10: badge arancione. Crediti < 3: badge rosso.

Elimina il banner warning crediti attuale (troppo ingombrante).

## Modalita 1: Launchpad (0 progetti)

### Sezione "Cosa vuoi fare?"

Heading + sottotitolo "Scegli uno strumento per iniziare"

Griglia 5 card (1 col mobile, 2 tablet, 3 desktop):

| Ordine | Task Title | Modulo | Descrizione |
|--------|-----------|--------|-------------|
| 1 | Trovare keyword strategiche | keyword-research | L'AI analizza il tuo settore, espande le keyword e le raggruppa per intento |
| 2 | Scrivere articoli SEO per il tuo blog | ai-content | Dai la keyword, Ainstein studia i top Google, scrive e pubblica su WordPress |
| 3 | Scoprire cosa migliorare nel tuo sito | seo-audit | Audit completo con piano d'azione ordinato per impatto |
| 4 | Monitorare le posizioni su Google | seo-tracking | Tracking keyword con click reali da Google Search Console |
| 5 | Analizzare o creare campagne Google Ads | ads-analyzer | Trova sprechi di budget o crea campagne complete da zero |

Ogni card: icona modulo colorata, titolo task-oriented, descrizione 2 righe, costo indicativo, CTA "Inizia".

## Modalita 2: Task Manager (1+ progetti)

### Sezione "Da fare" (prominente, in alto)

Card azioni contestuali con bordo sinistro colorato (colore modulo). Max 5, ordinate per priorita.

Regole di generazione:

| Priorita | Condizione | Testo | CTA | Modulo |
|----------|-----------|-------|-----|--------|
| 1 | `credits < 3` | "Crediti quasi esauriti (X.X)" | Ricarica | - |
| 2 | `aic_ready > 0` | "X articoli pronti da pubblicare" | Pubblica | ai-content |
| 3 | `!wp_connected && aic_keywords > 0` | "Collega WordPress per la pubblicazione automatica" | Collega | ai-content |
| 4 | `aic_keywords > 0 && aic_articles == 0` | "Keyword in coda, genera gli articoli" | Genera | ai-content |
| 5 | `sa_issues > 0` | "X problemi trovati nell'ultimo audit" | Vedi piano | seo-audit |
| 6 | `st_keywords > 0 && !gsc_connected` | "Collega Google Search Console per dati reali" | Collega | seo-tracking |
| 7 | `credits < 10 && credits >= 3` | "Crediti in esaurimento (X.X)" | Ricarica | - |

Se nessuna azione: "Tutto in ordine! Cosa vuoi fare?" con link rapidi ai moduli.

### Sezione "I tuoi strumenti" (compatta)

Griglia moduli (2 col mobile, 3 desktop). Card mini:
- Icona modulo colorata + Nome modulo + 1 metrica chiave + freccia link
- No grafici, no progress bar, no dettagli estesi

Metriche per modulo:
- ai-content: "X articoli"
- seo-tracking: "X keyword"
- keyword-research: "X progetti"
- seo-audit: "Score: XX" (o "X progetti" se nessun audit completato)
- ads-analyzer: "X campagne"
- internal-links: "X progetti"

## Cosa viene eliminato

- 4 KPI cards con sparkline (crediti disponibili, usati oggi, mese, operazioni)
- Chart linea 30 giorni
- Chart doughnut crediti per modulo
- Feed attivita recente (10 items)
- Pipeline KR -> AI Content -> WP
- Sezione onboarding in fondo (sostituita da modalita Launchpad)
- Banner warning crediti (integrato nel badge header)

## Cosa viene mantenuto (rivisto)

- Saluto nell'header (compattato a una riga)
- Crediti disponibili (badge compatto nell'header)
- Smart suggestions -> diventano sezione "Da fare" prominente
- Card moduli -> versione ultra-compatta

## File coinvolti

- `shared/views/dashboard.php` - Riscrittura completa della view
- `public/index.php` (route `/dashboard`) - Semplificare query (meno dati necessari)

## Note implementative

- Rimuovere Chart.js dalla dashboard (non servono piu grafici)
- Le query per pipeline e widget data restano, ma servono solo per le "smart actions"
- Le query per sparklines, daily usage 30d, credits by module possono essere eliminate
- La logica smart actions riusa i dati gia fetchati (pipelineData + widgetData)
