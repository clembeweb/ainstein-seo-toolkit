# Prossimi Sviluppi — Ainstein SEO Toolkit

> Backlog di miglioramenti pianificati. Aggiornato: 2026-03-12

---

## Keyword Research — Miglioramento Output AI

Feedback post-test Keyword Planner integration. Tutte e 3 le modalita necessitano di output AI piu ricco e UX risultati piu efficace.

### 1. Research Guidata — Cluster Visibility & Scoring

**Problema**: I cluster sono presentati come lista piatta, tutti sullo stesso livello. Non e immediato capire quali sono importanti.

**Miglioramenti richiesti**:
- Rendere subito visibile quali cluster sono strategicamente importanti
- Cluster scoring (volume totale, opportunity score, competition gap)
- Visual ranking / gerarchia visiva (top clusters evidenziati)
- Possibile: heatmap volume, priority badges, sorting automatico per importanza

**Impatto**: Prompt AI (aggiungere scoring/priorita) + UI risultati (cluster cards con gerarchia)

### 2. Architettura Sito — Proposta Sito Completo

**Problema**: Attualmente propone solo pagine basate su keyword clusters. Manca la visione d'insieme di un sito reale.

**Miglioramenti richiesti**:
- Proporre un sito completo con pagine istituzionali (chi siamo, contatti, servizi, privacy, etc.)
- Suggerire eventuale blog con categorie
- Analizzare competitor in SERP per informare la struttura proposta
- Distinguere pagine "SEO" (keyword-driven) da pagine "istituzionali" (necessarie per UX/trust)

**Impatto**: Prompt AI (espandere scope a sito completo + analisi SERP) + possibile integrazione SerpApiService + UI risultati (sezioni separate per tipo pagina)

### 3. Piano Editoriale — Vista Calendario

**Problema**: Attualmente i risultati sono in formato tabella/elenco per mese. Poco visual, poco "engaging".

**Miglioramenti richiesti**:
- Vista calendario come UI primaria (griglia mesi/settimane, card articoli)
- UX drag & drop per riordinare articoli tra mesi
- Riferimenti: CoSchedule, Notion calendar view, Trello board
- Mantenere vista tabella come alternativa (toggle)
- Card articoli con: titolo, keyword, volume, intent badge, difficulty badge

**Impatto**: Principalmente frontend (Alpine.js calendar component) + possibile aggiunta campo `week_number` o `publish_date` nel prompt AI

---

## Note Tecniche dall'Analisi del Codice (2026-03-12)

Dall'esplorazione del codice attuale, note utili per l'implementazione:

| Modalita | Controller | Prompt (linee) | Risultati View | DB |
|----------|-----------|----------------|----------------|-----|
| Research Guidata | `ResearchController.php` | L418-458 | `research/results.php` | `kr_clusters` + `kr_keywords` |
| Architettura Sito | `ArchitectureController.php` | L375-411 | `architecture/results.php` | `kr_clusters` + `kr_keywords` |
| Piano Editoriale | `EditorialController.php` | L822-939 (`buildAiPrompt()`) | `editorial/results.php` | `kr_editorial_items` |

**Pattern comune**: Wizard Step 1 (brief) → SSE Collection Step 2 → AI Analysis AJAX Step 3 → Results View Step 4

**Suggerimento implementazione**: Procedere una modalita alla volta, partendo dalla piu visibile (Piano Editoriale calendario) o dalla piu semplice (Research Guidata cluster scoring).
