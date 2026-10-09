# AI Reputation Radar — Raggruppamento AI degli "articoli gap"

> Design approvato a blocchi con Clemente il 2026-10-09 e rivisto criticamente prima dell'implementazione. Modulo `ai-reputation`, branch `claude/ai-reputation-radar-dd9004`. ADR-014.

## 1. Problema

Oggi `ReportBuilderService::actions()` crea **un intervento `gap_article` per ogni domanda** commerciale/di settore in cui il soggetto non è citato (regola fissa, nessuna AI). Sul run 4 di Marcaccini escono 7 righe "Non citato: …" quasi uguali, e le schede operative generate su di esse sono quasi identiche. In realtà sono **2 articoli** (real estate a Roma; imprenditori immobiliari in Italia) più **2 domande che nessun fatto confermato sostiene** (hotel di lusso).

## 2. Obiettivo

A fine run, **una sola chiamata AI** trasforma le domande scoperte in:
- **pochi articoli veri** (di norma 2-4), ognuno con titolo, perché serve e le domande che copre (italiano e inglese sullo stesso tema insieme);
- **domande in sospeso**: quelle che nessun fatto confermato sostiene, ognuna con la prova che servirebbe dal cliente. Restano visibili ("Da valutare: serve una prova dal cliente"), senza pulsante "Genera scheda".

Rimozioni, contro-contenuto e disambiguazione non cambiano. Il PDF stampa ciò che si vede nel report (regola della spec 2026-10-08).

## 3. Dati

Migrazione `modules/ai-reputation/database/2026-10-09-actions-gap-grouping.sql`:

| Modifica | Significato |
|---|---|
| `ar_actions.type` ENUM + `'gap_pending'` | Domanda scoperta non sostenuta dai fatti, in attesa di una prova dal cliente |
| `ar_actions.covered_prompts` JSON NULL | Per `gap_article` raggruppati e `gap_pending`: `[{"id": <prompt_id>, "text": "<domanda>"}, …]`. NULL per gli altri tipi |

Nessuna impostazione nuova: il modello è `brief_model` (descrizione aggiornata: "Modello per 'Genera scheda' e per il raggruppamento degli articoli"). Il judge non cambia (regola 6).

## 4. Servizio `GapGroupingService`

`modules/ai-reputation/services/GapGroupingService.php`.

### 4.1 Input
- `$gapByPrompt` (da `ReportBuilderService::gapQuestions()`, estratto da `actions()`): per `prompt_id` → testo, competitor citati, engine.
- Fatti del profilo via `ActionBriefService::factBlocks()` (citabili + omonimi; i fatti negativi non servono qui).
- Progetto (nome, tipo, città, note di disambiguazione).

Nel prompt ogni domanda ha un **numero corto 1..N** (mai il `prompt_id` reale): l'AI risponde coi numeri, il codice li rimappa.

### 4.2 Prompt
System: consulente di contenuti; rispondi SOLO JSON; italiano; usa solo i fatti confermati; non attribuire fatti degli omonimi; niente costi di testate; niente spiegazione del metodo.

User: soggetto, fatti confermati, omonimi, elenco numerato delle domande con competitor; poi il compito:
- raggruppa per tema; di norma 2-4 articoli; stesso tema in lingue diverse → stesso articolo;
- una domanda finisce in un solo posto;
- una domanda va in `pending` se i fatti confermati non sostengono una competenza/attività credibile su quel tema; indica la prova che servirebbe;
- JSON: `{"articles":[{"title":"…","why":"1-2 frasi","questions":[n,…]}], "pending":[{"question":n,"needed":"quale prova serve"}]}`.

Chiamata: `AiService::complete($ownerId, …, ['max_tokens' => 4000, 'effort' => 'medium', 'timeout' => 120, 'system' => …, 'charge_credits' => false], 'ai-reputation')` con `model = brief_model`. Costo stimato 0,03-0,05 $ a run, incluso nella run (nessun credito a parte). `Database::reconnect()` dopo.

### 4.3 Validazione (`validate(array $data, array $ids): array`, pura, testabile senza AI)
- numeri non presenti → scartati; numero già usato → scartato (prima occorrenza vince);
- domande non assegnate → aggiunte a `pending` con `needed = 'da rivedere'`;
- articoli oltre `MAX_ARTICLES = 6` → le loro domande vanno in `pending` ('da rivedere');
- articoli senza domande → scartati; `title`/`why` vuoti → articolo scartato (domande → pending);
- stringhe troncate: title 500, why 2000, needed 500.
Ritorna `['articles' => [...], 'pending' => [...]]` con `prompt_id` reali.

### 4.4 Fallback
Errore AI, JSON rotto, timeout, risposta vuota → `group()` ritorna `null`; `actions()` produce le righe per domanda come oggi. `Logger::channel('ai-reputation')->warning('Raggruppamento gap fallito', …)`. La run si chiude comunque.

## 5. `ReportBuilderService`

- `gapQuestions(array $responses, array $analyses, array $engineLabels): array` — estratto dall'attuale loop di `actions()`.
- `actions(..., ?array $gapGroups = null)`: se `$gapGroups` è null → comportamento attuale; altrimenti:
  - per ogni articolo: `type = gap_article`, `title` = titolo AI, `rationale` = `why` + " Copre N domande: …" (prime 3, poi "e altre K"), `target_domain = $suggested`, `covered_prompts` = domande coperte;
  - per ogni pending: `type = gap_pending`, `title` = testo domanda, `rationale` = "Serve una prova dal cliente: " + `needed`, `covered_prompts` = quella domanda.
- `persist()`: calcola `gapQuestions`, chiama `GapGroupingService::group()` (solo se ci sono domande scoperte), passa il risultato ad `actions()`.
- **Conservazione al ricalcolo**: oggi si conserva solo `status` con chiave `type|target_url|title`. Nuovo: si conservano anche `channel, channel_rationale, suggested_outlets, brief, brief_model, brief_generated_at, brief_error`. Per `gap_article`/`gap_pending` la chiave è `type|` + ids ordinati di `covered_prompts` (il titolo lo inventa l'AI e cambia); per gli altri tipi resta `type|target_url|title`.

## 6. Scheda operativa (`ActionBriefService`)

- `gap_pending`: il controller `generateBrief` risponde 422 "Questa domanda è in sospeso: serve prima una prova dal cliente". Nessun pulsante nel report.
- `gap_article` raggruppato: nel dossier le domande di `covered_prompts` vanno **per prime** (oggi `orderQuestions` mette prime quelle il cui testo compare nel titolo; con titolo AI non succede più). `orderQuestions()` riceve anche gli ids coperti.

## 7. Report web e PDF

- `views/runs/show.php`: `$actionLabel['gap_pending'] = 'da valutare'`, classe grigia; sotto la colonna "Da pubblicare", blocco "**Da valutare: serve una prova dal cliente**" con le righe `gap_pending` (titolo = domanda, rationale = prova richiesta, nessun "Genera scheda"). Per gli articoli raggruppati, nel dettaglio l'elenco delle domande coperte.
- `ActionPlanPdfService` + `views/pdf/action-plan.php`: stesso blocco dopo i contenuti e prima delle rimozioni; `TYPE_LABELS['gap_pending'] = 'Da valutare'`; i `gap_pending` non contano tra i "contenuti da pubblicare" dell'intestazione.
- `RunController` query `ORDER BY FIELD(type, …)`: aggiunto `gap_pending` in coda.
- SSE: prima del raggruppamento `$sendEvent('progress', ['engine' => 'piano', 'prompt' => 'Raggruppamento degli articoli…'])` così la UI non sembra ferma per 20-40 s.

## 8. Ricalcolo del piano senza judge

`modules/ai-reputation/scripts/rebuild-plan.php <runId>`: carica run, progetto, risposte e verdetti già salvati e chiama `persist()`. Non rifà il judge (i verdetti della demo restano), costa solo la chiamata di raggruppamento. Da usare sul run 4. Pulsante nel report: non ora.

## 9. Test

- `scripts/test-gap-grouping-validate.php` (senza AI): numeri inventati, doppioni, domande dimenticate, >6 articoli, articolo senza domande, JSON rotto → fallback null.
- Prova reale: `php modules/ai-reputation/scripts/rebuild-plan.php 4` → attesi ~2 articoli (Roma; Italia) e 2 pending (hotel); poi report e PDF nel browser; la scheda "è affidabile?" deve essere ancora lì.
- `php -l` sui file toccati.

## 10. Fuori scope
Prompt del canale della scheda (sito proprio vs testate) e brief vero per il blog: fase successiva, su un piano già pulito.
