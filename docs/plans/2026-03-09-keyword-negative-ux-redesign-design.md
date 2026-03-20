# Keyword Negative UX Redesign - Design Document

> Data: 2026-03-09

## Problema

La sezione Keyword Negative attuale è inutilizzabile:

1. **Dati disconnessi**: il controller legge `ga_ad_groups` (vuota) ma i dati reali sono in `ga_campaign_ad_groups`. I search terms hanno `ad_group_name = NULL` e `campaign_name = NULL`.
2. **Flusso troppo frammentato**: 7 step per arrivare ad applicare le negative (sync → expand → context → AI → select → modal → apply).
3. **Nessun ciclo iterativo**: manca il concetto di "ho applicato le negative, ora verifico se funzionano e ne trovo di nuove".
4. **Nessun feedback** quando i dati mancano (ad groups vuoti, nessuna analisi).

## Soluzione: Ciclo di Pulizia Iterativo

Una pagina orientata all'azione con un ciclo chiaro: **Analizza → Applica → Verifica → Ripeti**.

### Principi UX

- **L'AI fa il lavoro pesante**: l'utente non deve leggere 800+ termini, l'AI li analizza e presenta risultati organizzati.
- **Contesto completo**: ogni keyword negativa suggerita mostra impressioni sprecate, costo, campagna/ad group — il "perché".
- **Ciclo visibile**: dopo ogni applicazione, la sync successiva mostra progresso (termini eliminati, ancora presenti, nuovi).
- **L'AI decide il livello**: in base al contesto (campagna multi-ad group con landing/annunci diversi), l'AI suggerisce se applicare a livello campagna o ad group.
- **Minimo attrito**: un bottone per analizzare, checkbox per confermare, un bottone per applicare.

---

## Architettura Pagina

### Header

KPI sintetici sempre visibili:
- **Termini totali** (dalla sync corrente)
- **Spreco stimato** (impression zero-CTR × costo medio)
- **Negative applicate** (totale storico)
- **Ultima sync**: data + link discreto "Usa sync precedente"

Il selettore sync NON è esposto in primo piano — il sistema usa l'ultima sync. Un dropdown secondario permette di cambiare se necessario.

### Stato 1: Nessuna analisi

CTA grande: "Analizza termini di ricerca con AI"

Sotto: breve spiegazione del ciclo ("L'AI analizzerà i tuoi termini di ricerca, identificherà quelli fuori target e ti suggerirà le keyword negative da aggiungere su Google Ads").

Il contesto business non è più un form separato: il sistema lo estrae automaticamente dalle landing page degli annunci (già disponibile nei dati sync). Se l'utente vuole aggiungere contesto manuale, c'è un campo opzionale collassato "Aggiungi contesto business" sotto il bottone.

### Stato 2: Analisi completata — Risultati

Card organizzata in sezioni:

#### 2a. Riepilogo

Box riassuntivo: "L'AI ha trovato **45 keyword negative** in **6 categorie**. Spreco stimato: **€234.50** su **1.512 impression**."

Se è una analisi successiva alla prima, aggiungere confronto:
- "✓ 38 termini sporchi eliminati rispetto alla scorsa analisi"
- "⚠ 4 termini ancora presenti (match type da verificare)"
- "🔍 12 nuove keyword negative trovate"

#### 2b. Categorie di keyword negative

Lista categorie con accordion (espandibili). Ogni categoria mostra:
- **Nome categoria** (generato dall'AI, es. "Contatti Segreteria")
- **Priorità**: badge colorato (Alta = rossa, Media = ambra, Da valutare = grigia)
- **Conteggio**: "11 keyword" + checkbox "Seleziona tutte"
- **Livello suggerito**: icona campagna o ad group (deciso dall'AI)
- **Impatto stimato**: impression/costo totale dei termini collegati

Espandendo la categoria:
- Lista keyword con checkbox individuale
- Per ogni keyword: `keyword | match type | impressioni | costo | campagna > ad group`
- Status: "nuova" (badge blue) / "ricorrente" (badge amber) / "risolta" (badge green, solo nel confronto)

#### 2c. Azioni

Barra fissa in basso (sticky):
- Conteggio selezionate: "32/45 selezionate"
- **Applica su Google Ads** (bottone primario rose) — apre modale conferma
- **Esporta CSV** (bottone secondario)
- **Esporta Google Ads Editor** (bottone secondario)

#### 2d. Modale conferma applicazione

Modale semplificata:
- Riepilogo: "Stai per aggiungere 32 negative keywords su Google Ads"
- Dettaglio per livello:
  - "24 a livello campagna (BC-Search, [BW]_Search_Lead_Mercatorum)"
  - "8 a livello ad group (Pegaso, San Raffaele)"
- Warning amber: "Questa azione applicherà le keyword direttamente sul tuo account. L'operazione non è reversibile automaticamente."
- Bottoni: Annulla / Applica 32 keyword

L'utente NON sceglie più manualmente il livello campagna/ad group — **l'AI lo ha già deciso** per ogni keyword e il modale mostra il riepilogo.

### Stato 3: Post-applicazione

Dopo aver applicato, la sezione risultati mostra:
- Banner verde: "✓ 32 negative keywords applicate su Google Ads il 09/03/2026"
- "La prossima sincronizzazione mostrerà l'effetto delle negative applicate. Consigliamo di attendere 2-3 giorni."
- Bottone: "Nuova sincronizzazione" (link a Connessione)

### Stato 4: Nuova analisi (ciclo iterativo)

Quando arriva una nuova sync dopo l'applicazione:
- L'header mostra automaticamente il confronto con la precedente
- Il bottone "Analizza" è di nuovo disponibile
- L'analisi AI questa volta:
  1. Sa quali negative sono state applicate (da `applied_at` in `ga_negative_keywords`)
  2. Verifica se i termini sporchi sono spariti → segnala come "risolte"
  3. Identifica termini ancora presenti → segnala come "ricorrenti" con suggerimento match type
  4. Trova nuovi termini sporchi → segnala come "nuove"

### Storico analisi

Accordion "Analisi precedenti" in fondo alla pagina:
- Lista cronologica delle analisi con data, keyword trovate, keyword applicate
- Espandibile per vedere il dettaglio di ogni analisi passata

---

## Modifiche Dati

### Fix critici (bug attuali)

1. **Popolare `ad_group_name` e `campaign_name` in `ga_search_terms`** durante la sync. Il `CampaignSyncService::syncSearchTerms()` deve mappare i nomi da `ga_campaign_ad_groups`.

2. **`ga_ad_groups` vs `ga_campaign_ad_groups`**: il controller `index()` usa `AdGroup::getByRunWithStats()` che legge da `ga_ad_groups` (tabella vuota). Deve leggere da `ga_campaign_ad_groups` oppure il sync deve popolare `ga_ad_groups`.

   **Decisione**: modificare `AdGroup::getByRunWithStats()` per leggere da `ga_campaign_ad_groups` e le query correlate. La tabella `ga_ad_groups` è legacy del vecchio sistema script-based.

### Nuova colonna

- `ga_negative_keywords.suggested_level` ENUM('campaign', 'ad_group') DEFAULT 'campaign' — dove l'AI suggerisce di applicare la keyword.
- `ga_negative_keywords.suggested_campaign_resource` VARCHAR(255) NULL — resource name campagna target
- `ga_negative_keywords.suggested_ad_group_resource` VARCHAR(255) NULL — resource name ad group target

### Prompt AI aggiornato

Il prompt per `KeywordAnalyzerService` deve essere arricchito per:
1. Ricevere la struttura completa campagna → ad groups (con nomi e landing URL)
2. Per ogni keyword negativa suggerita, specificare il `level` (campaign/ad_group) e il `target` (nome campagna o ad group)
3. Fornire `match_type` ragionato (EXACT per termini molto specifici, PHRASE per pattern, BROAD per categorie ampie)

### Contesto automatico

Il business context viene estratto automaticamente da:
1. Landing URL degli annunci (già in `ga_ads.final_url`)
2. Nomi campagne e ad group (indicano il targeting)
3. Keyword attive (da `ga_ad_group_keywords`)
4. Se disponibile, contesto landing page già estratto (`ga_ad_groups.extracted_context`)

L'utente può aggiungere contesto manuale opzionalmente, ma non è obbligatorio.

---

## Flusso Tecnico

### 1. Caricamento pagina

```
GET /ads-analyzer/projects/{id}/search-term-analysis

Controller::index():
  1. Carica ultima sync con search_terms_synced > 0
  2. Carica stats aggregate (total_terms, zero_ctr, wasted_impressions, total_cost)
  3. Carica ultima analisi completata (se esiste) con risultati
  4. Carica storico negative applicate (count + date)
  5. Se esiste analisi precedente + nuova sync: calcola confronto
  6. Passa tutto alla view come config JSON per Alpine.js
```

### 2. Analisi AI

```
POST /ads-analyzer/projects/{id}/search-term-analysis/analyze (AJAX lungo)

Controller::analyze():
  1. Carica TUTTI i search terms della sync corrente (non solo zero-CTR)
  2. Costruisci contesto automatico:
     - Struttura campagne → ad groups con nomi
     - Landing URL per ad group
     - Keyword attive per ad group
  3. Chiama AI con prompt arricchito
  4. AI ritorna: categorie → keyword → level + target + match_type
  5. Salva risultati con suggested_level, suggested_campaign_resource, suggested_ad_group_resource
  6. Calcola confronto con analisi precedente
  7. Ritorna risultati completi
```

### 3. Applicazione

```
POST /ads-analyzer/projects/{id}/search-term-analysis/apply-negatives (AJAX lungo)

Controller::applyNegativeKeywords():
  1. Carica keyword selezionate con suggested_level
  2. Raggruppa per livello: campaign vs ad_group
  3. Per ogni gruppo, costruisci operazioni Google Ads API
  4. Chiama GoogleAdsService::mutateCampaignCriteria() e/o mutateAdGroupCriteria()
  5. markAsApplied() con timestamp
  6. Ritorna conteggio applicati per livello
```

---

## File da modificare

| File | Modifica |
|------|----------|
| `views/campaigns/search-terms.php` | Riscrittura completa della view |
| `controllers/SearchTermAnalysisController.php` | Refactor index(), analyze(), applyNegativeKeywords() |
| `models/AdGroup.php` | Fix query per usare `ga_campaign_ad_groups` |
| `services/CampaignSyncService.php` | Popolare ad_group_name/campaign_name in search terms |
| `services/KeywordAnalyzerService.php` | Prompt arricchito con struttura campagne e livello suggerito |
| `database/migrations/` | Nuova migrazione per colonne suggested_level, etc. |
| `models/NegativeKeyword.php` | Nuovi metodi per suggested_level |

## Crediti

Nessuna modifica al costo crediti — rimane per ad group analizzato (tier 3).

## Non incluso (YAGNI)

- Scheduling automatico delle analisi (il ciclo è manuale)
- Negative keyword list shared (Google Ads ha questo concetto ma complessifica)
- Bulk undo delle negative applicate (Google Ads non lo supporta facilmente via API)
- Dashboard trend storici (lo storico accordion è sufficiente)
