# Piano Implementativo — Ads Dashboard UX Redesign

> Data: 2026-03-20
> Design: `2026-03-20-ads-dashboard-ux-redesign-design.md`
> Modulo: ads-analyzer
> Stima: 8 task, ~2-3 sessioni

---

## Task 1: Controller — Raggruppare campagne per tipo

**File**: `modules/ads-analyzer/controllers/CampaignController.php` (metodo `dashboard()`)

**Cosa fare**:
1. Dopo il fetch di `$campaignsPerformance`, raggruppare per `type`:
   ```php
   $campaignsByType = [];
   foreach ($campaignsPerformance as $camp) {
       $type = $camp['type'] ?? 'SEARCH';
       $campaignsByType[$type][] = $camp;
   }
   ```
2. Per ogni tipo, limitare a top 6 campagne ordinate per `cost` DESC
3. Calcolare `$alertsByType` — array di stringhe alert per tipo (logica threshold dalla design spec)
4. Passare `$campaignsByType` e `$alertsByType` alla view (mantenere anche `$campaignsPerformance` per backward compat temporanea)

**Verifica**: `php -l` sul controller

---

## Task 2: Dashboard view — Grid tabelle per tipo

**File**: `modules/ads-analyzer/views/campaigns/dashboard.php`

**Cosa fare**:
1. Rimuovere la SEZIONE 4 esistente (righe 324-533 circa — tabella espandibile unica)
2. Sostituire con grid `lg:grid-cols-2 gap-6` contenente:
   - Una card/tabella per ogni tipo presente in `$campaignsByType`
   - Colonne specifiche per tipo (dalla design spec)
   - Alert inline sotto ogni tabella
   - Link "Vedi tutte →" con `?type=TYPE`
3. Ordine card: Search, PMax, Shopping, Display, Video (tipi assenti non mostrati)
4. Se un solo tipo presente: la card occupa `col-span-2` (full width)
5. NO expand/collapse — tabelle flat, solo panoramica

**Regole CSS**: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`, status dot per campagna

**SEZIONE 5 (grid widget)**: rimane ma si integra nel flow. Il widget Spreco Budget e il trend chart restano nelle celle del grid sotto le tabelle tipo.

---

## Task 3: Bugfix link Spreco Budget

**File**: `modules/ads-analyzer/views/campaigns/dashboard.php`

**Cosa fare**: Riga ~550, cambiare il link da `/negative-keywords` a `/search-term-analysis`

**Verifica**: controllare che la route esista in `routes.php`

---

## Task 4: Rimuovere tab "Keyword Negative" dalla nav

**File**: `modules/ads-analyzer/views/partials/project-nav.php`

**Cosa fare**:
1. Rimuovere entry `'search-term-analysis'` dall'array `$tabs` (riga 21)
2. Rimuovere alias corrispondente da `$aliases` nella funzione `isActiveTabGa()` (riga 33)

**NON fare**: non rimuovere route ne controller — backward compat

---

## Task 5: Filtro pills nella pagina Campagne

**File**: `modules/ads-analyzer/views/campaigns/index.php` (o equivalente pagina lista campagne)

**Cosa fare**:
1. Leggere query param `?type=` dalla URL
2. Aggiungere pills Alpine.js sopra la tabella:
   ```
   [Tutte (N)] [Search (N)] [PMax (N)] [Shopping (N)]
   ```
3. Click su pill → filtra righe client-side (show/hide con `x-show`)
4. Se `?type=SEARCH` presente da URL → pre-selezionare il pill corrispondente
5. Conteggi per tipo calcolati client-side dal DOM o passati dal controller

**Stile pills**: stesse classi del date range selector in dashboard (inline-flex, rounded-lg, border)

---

## Task 6: Evaluation-v2 — Potenziare sezione KW Negative inline

**File**: `modules/ads-analyzer/views/campaigns/evaluation-v2.php`

**Cosa fare**:
1. Cambiare condizione visibilita da `!empty($negativeSummary)` a `$hasSearchCampaigns`
2. Se nessuna analisi precedente: mostrare box con bottone "Analizza Termini (1 credito)"
3. Se analisi completata: mostrare risultati inline con:
   - Categorie per priorita (alta/media/da valutare), espandibili con Alpine
   - Lista keyword con checkbox, match type badge
   - Azioni: Seleziona tutto, Applica su Google Ads, Copia, CSV
4. Le chiamate AJAX usano gli endpoint elencati nella design spec
5. Link "Analisi avanzata →" rimanda a `/search-term-analysis` (per scraping landing page)

**Componente Alpine**: nuovo oggetto `negativeKeywords()` dentro la pagina, con:
- `analyze()` → POST `/analyze`
- `loadResults()` → GET `/results`
- `toggleKeyword(id)` → POST `/keywords/{id}/toggle`
- `applyNegatives()` → POST `/apply-negatives`
- State: `categories[]`, `loading`, `analyzing`, `hasResults`

**Nota**: il controller `CampaignController::evaluationShow()` deve passare `$hasSearchCampaigns` alla view.

---

## Task 7: Controller evaluation — Passare $hasSearchCampaigns

**File**: `modules/ads-analyzer/controllers/CampaignController.php` (metodo `evaluationShow()`)

**Cosa fare**:
1. Calcolare `$hasSearchCampaigns` dai dati sync:
   ```php
   $hasSearchCampaigns = !empty(array_filter(
       $syncMetrics['campaigns'] ?? [],
       fn($c) => strtoupper($c['campaign_type'] ?? '') === 'SEARCH'
   ));
   ```
2. Passare alla view `evaluation-v2.php`

---

## Task 8: Test e verifica finale

**Cosa fare**:
1. `php -l` su tutti i file PHP modificati
2. Test manuale browser:
   - Dashboard con account misto (Search + PMax) → tabelle separate
   - Dashboard con solo Search → full width
   - Dashboard con 0 campagne → empty state invariato
   - Link "Vedi tutte" → pagina Campagne con filtro preselezionato
   - Tab nav → 4 tab (no KW Negative)
   - Evaluation Search → sezione KW negative con bottone Analizza
   - Evaluation solo PMax → sezione KW negative non visibile
   - Widget Spreco Budget → link corretto
3. Test dark mode su tutte le view modificate

---

## Ordine di esecuzione

```
Task 1 (controller dashboard) → Task 2 (view dashboard) → Task 3 (bugfix link)
    ↓
Task 4 (rimuovi tab nav) — indipendente
    ↓
Task 5 (filtro pills Campagne) — dipende da Task 2 per link "Vedi tutte"
    ↓
Task 7 (controller evaluation) → Task 6 (view evaluation KW negative)
    ↓
Task 8 (test finale)
```

Task 1-3 e Task 4 possono essere fatti in parallelo.
Task 7 e Task 6 possono essere fatti in parallelo con Task 5.

---

## Checklist pre-commit (per ogni task)

- [ ] `php -l` su file modificati
- [ ] Icone solo Heroicons SVG
- [ ] Testi UI in italiano
- [ ] `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50` per tabelle
- [ ] `response.ok` check prima di `response.json()` nel JS
- [ ] `'user' => $user` in ogni `View::render()`
- [ ] Link project-scoped (mai percorsi legacy)
