# Design: Redesign Report AI Google Ads

> Data: 2026-03-02 | Modulo: ads-analyzer | Stato: Approvato

## Contesto

Il report delle analisi AI sulle campagne Google Ads ha problemi di UX:
- Tutto su una singola colonna con scroll lungo
- Informazioni importanti nascoste dentro accordion
- Nessun feedback immediato sullo stato delle campagne
- Nessun selettore di periodo temporale (solo ultimi 30gg dallo script)

## Decisioni di Design

| Decisione | Scelta |
|-----------|--------|
| Layout | Dashboard + Tabs |
| Periodo | Selettore UI 7g/14g/30g da run esistenti in DB |
| Campagne | Card Grid + Drawer laterale |
| AI Response JSON | Invariato (nessuna modifica prompt/struttura) |

## Architettura UI

### 1. Header Fisso

Sempre visibile in alto. Contiene:
- Score complessivo (cerchio colorato) + trend indicator
- Summary text (2-3 frasi)
- Metadata: campagne/ad groups/landing/crediti contati
- Selettore periodo: pill buttons 7g/14g/30g
- Label run date
- Azioni: PDF export, Rigenera

**Selettore periodo logica**: cerca run in DB il cui `date_range_end - date_range_start` corrisponde al periodo. Bottone disabilitato se nessuna run per quel periodo. Cambio periodo → redirect a `?run_id=Y`. Se nessuna evaluazione per quella run → prompt "Analizza ora".

### 2. KPI Cards

Griglia 6 card sotto header:
- Clicks, Impressions, Costo, Conversioni, CTR, CPC
- Valore assoluto + delta percentuale colorato
- Colori: verde = miglioramento, rosso = peggioramento (invertito per CPC/costo)
- Dati da `metric_deltas` JSON esistente

### 3. Tab Navigation (5 tabs)

Alpine.js `x-data="{ activeTab: 'panoramica' }"`. No reload pagina.

```
[● Panoramica] [ Campagne (N) ] [ Estensioni ] [ Landing Pages ] [ Azioni ]
```

### 4. Tab Panoramica

Primo tab visibile, quadro d'insieme:
- **Trend/Cambiamenti** (solo auto-eval): box con `changes_summary` + new_issues/resolved_issues badges
- **Top Recommendations**: lista numerata 1-7 con priority badges
- **Issues Heatmap**: mini-griglia campagna × severity (high/medium/low), celle colorate
- **Score per campagna**: barre orizzontali con score colorato, cliccabili → tab Campagne

### 5. Tab Campagne

**Card Grid** (responsive: 3col desktop, 2col tablet, 1col mobile):
- Nome campagna (troncato)
- Score badge circolare colorato
- Tipo badge (SEARCH/SHOPPING/PMAX/DISPLAY/VIDEO)
- Contatore issues per severity
- Bordo sinistro colorato per score

**Click → Drawer laterale** (slide-in destra, ~60% width, backdrop overlay):
- Header: nome + score + tipo + X chiudi
- Type-specific insights (callout blu)
- Strengths (lista emerald)
- Issues (severity badge + area + recommendation)
- Ad Groups (accordion se multipli)
- Mini-metriche per ad group (keyword coherence, ad relevance, landing coherence, quality score)
- Bottoni "Genera con AI" (copy/extensions/keywords)

Implementazione: Alpine.js `x-show` + `x-transition`, backdrop click chiude.

### 6. Tab Estensioni

- Score estensioni in alto
- Due colonne: "Presenti" (emerald checkmarks) | "Mancanti" (red x-marks)
- Suggerimenti come callout cards

### 7. Tab Landing Pages

- Score landing in alto
- Tabella: URL | Problema | Raccomandazione
- CSS standard Ainstein (rounded-xl, px-4 py-3, dark:bg-slate-700/50)

### 8. Tab Azioni

Tutte le azioni concrete centralizzate:
- **Suggerimenti Campagne**: tabella (Priorità, Area, Suggerimento, Impatto Atteso)
- **Suggerimenti Landing**: tabella analoga
- **Genera con AI**: 3 bottoni (copy, estensioni, keyword)

## Backend Changes

### Nuovo endpoint

`GET /ads-analyzer/projects/{id}/campaigns/available-runs`

Ritorna JSON:
```json
{
  "runs": [
    {"id": 123, "period_days": 30, "date_start": "2026-02-01", "date_end": "2026-03-02", "has_evaluation": true, "evaluation_id": 456},
    {"id": 122, "period_days": 7, "date_start": "2026-02-23", "date_end": "2026-03-02", "has_evaluation": false, "evaluation_id": null}
  ],
  "periods": {
    "7": {"available": true, "run_id": 122, "has_evaluation": false},
    "14": {"available": false, "run_id": null, "has_evaluation": false},
    "30": {"available": true, "run_id": 123, "has_evaluation": true}
  }
}
```

### Modifica evaluationShow()

- Accetta parametro GET `?run_id=X`
- Se specificato, carica l'evaluazione per quella run (o mostra "non analizzato")
- Passa `available_runs` alla view per popolare il selettore periodo

### Modifica CampaignController

- Nuovo metodo `availableRuns()` per l'endpoint
- `evaluationShow()` esteso con logica periodo

## Cosa NON Cambia

- Struttura JSON AI response (invariata)
- Controller `evaluate()` (invariato)
- Template PDF export (`evaluation-pdf.php` — separato)
- Sistema auto-eval (invariato)
- `generateFix()` (invariato)
- Database schema (nessuna nuova tabella/colonna)

## File Coinvolti

| File | Modifica |
|------|----------|
| `modules/ads-analyzer/views/campaigns/evaluation.php` | Riscrittura completa della view |
| `modules/ads-analyzer/controllers/CampaignController.php` | Nuovo metodo `availableRuns()`, modifica `evaluationShow()` |
| `public/index.php` | Nuova route per `available-runs` |
| `modules/ads-analyzer/models/ScriptRun.php` | Eventuale helper per raggruppare run per periodo |
