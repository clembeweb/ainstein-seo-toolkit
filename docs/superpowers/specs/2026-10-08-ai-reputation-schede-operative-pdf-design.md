# AI Reputation Radar — Export PDF degli interventi + scheda operativa per intervento

> Design approvato a blocchi con Clemente il 2026-10-08 (v2: semplificato, generazione per singolo intervento). Modulo `ai-reputation`, branch `claude/ai-reputation-radar-dd9004`.
> Fase B della richiesta ("PDF operativo per chi esegue gli interventi"). La fase C (PDF per il cliente finale) è fuori scope e riuserà lo stesso motore.

## 1. Obiettivo

Chi esegue gli interventi del piano d'azione (Tutela Digitale, il copywriter, chi scrive ai siti) deve poter ricevere un **PDF con gli interventi del run**. Ogni intervento può essere arricchito, su richiesta e uno per volta, con una **scheda operativa generata dall'AI**: dove pubblicare (sito proprietario del soggetto, siti esterni o entrambi), perché, e un brief completo del contenuto; per i siti da contattare, la traccia della richiesta.

Regola unica: **il PDF mostra esattamente ciò che si vede nel report web in quel momento**. Senza schede generate contiene gli interventi come sono oggi; con le schede, le mostra nello stesso posto.

Due consegne, in quest'ordine:
1. **Export PDF degli interventi** così come sono (nessuna AI).
2. **Scheda operativa per singolo intervento** (Claude Opus 5.5), visibile nell'app e nel PDF.

Regole commerciali del modulo che valgono anche qui: mai esporre il metodo, mai costi delle testate, tutto in italiano.

## 2. Dati

Migrazione `modules/ai-reputation/database/2026-10-08-actions-brief.sql` su `ar_actions` (serve dalla consegna 2, ma si applica subito):

| Colonna | Tipo | Significato |
|---|---|---|
| `channel` | ENUM('own_site','external','both') NULL | Canale deciso dall'AI per i contenuti. Sempre NULL per le rimozioni (il canale non si applica) |
| `channel_rationale` | TEXT NULL | Perché quel canale (1-3 frasi) |
| `suggested_outlets` | JSON NULL | 1-3 domini esterni suggeriti, es. `["ilsole24ore.com","wired.it"]` |
| `brief` | JSON NULL | Scheda operativa (shape in §4.3). NULL = scheda non generata |
| `brief_model` | VARCHAR(80) NULL | Modello che ha generato la scheda (quello realmente usato) |
| `brief_generated_at` | DATETIME NULL | Quando |
| `brief_error` | VARCHAR(500) NULL | Ultimo errore di generazione (vuoto se ok) |

Nessuna tabella nuova, nessuna modifica a `ar_runs`.

Nuove impostazioni in `module.json`:
- `brief_model` (gruppo `ai_config`; select, default `claude-opus-5-5`; opzioni `claude-opus-5-5`, `claude-sonnet-5-5`, `global`) — il judge **non** cambia (regola 6 del modulo).
- `cost_action_brief` (gruppo `costs`; number, default 1) — crediti per scheda generata.

Modifica a `services/AiService.php`:
- `MODELS['anthropic']`: aggiunti `claude-opus-5-5` (name "Claude Opus 5.5", input 0.004, output 0.020 per 1K token), `claude-sonnet-5-5` ("Claude Sonnet 5.5", 0.002 / 0.010), `claude-haiku-5-5` ("Claude Haiku 5.5", 0.0001 / 0.0005).
- `complete()` accetta l'opzione `effort` (`low|medium|high|xhigh|max`); `callAnthropic()` la invia come `output_config: {effort}` solo se presente. Opus 5.5 altrimenti lavora a `medium`; le schede usano `high`. AiService manda già solo `model`, `max_tokens`, `messages`, `system`: compatibile con Opus 5.5 (niente `temperature`, niente prefill).

## 3. Consegna 1 — Export PDF degli interventi

### 3.1 Servizio
`modules/ai-reputation/services/ActionPlanPdfService.php`, con mPDF (`mpdf/mpdf` già in `composer.json`, mai usato finora).
- `render(array $project, array $run, array $actions, array $metrics): string` — ritorna il PDF binario. `$actions` sono le azioni del run con `status <> 'dismissed'` (stessa query del report), già arricchite con le colonne della scheda (NULL nella consegna 1).
- `filename(array $project, array $run): string` — `piano-interventi-<slug soggetto>-run<id>-<YYYY-MM-DD>.pdf`.
- Template `modules/ai-reputation/views/pdf/action-plan.php`: HTML semplice con CSS inline (niente Tailwind: mPDF non lo rende), font DejaVu Sans, colore indigo `#4f46e5` per intestazioni e badge, link cliccabili. Riusa `ReportBuilderService::siteTitle()` e le etichette `$actionLabel` del report per i tipi.

### 3.2 Struttura del PDF
1. **Intestazione** (prima pagina): nome soggetto, data del run, rischio reputazione (etichetta + percentuale come nel report), conteggio interventi: "N contenuti da pubblicare · M siti da contattare".
2. **Interventi**, prima i contenuti (`counter_content`, `gap_article`, `correction`) poi le rimozioni (`removal`), nello stesso ordine del report. Per ognuno:
   - badge tipo (stesse etichette del report: contro-contenuto, articolo gap, correzione, rimozione) + badge stato (proposto / accettato / fatto);
   - titolo;
   - motivazione (`rationale`);
   - elenco pagine (`target_urls`, link cliccabili, titolo leggibile via `siteTitle`);
   - **se la scheda esiste** (consegna 2): blocco "Dove" (canale, motivazione, testate) e blocco "Brief" o "Richiesta"; **se non esiste**: riga grigia "Scheda operativa non generata".
   Interventi `dismissed` esclusi.
3. Piè di pagina: "Ainstein · AI Reputation Radar · pagina X di Y".

Niente metodo, niente costi, niente sezione "Perché" con le domande (le domande restano nel report web; si valuta per la fase C).

### 3.3 Route e UI
- `GET /ai-reputation/project/{id}/runs/{runId}/export/plan.pdf` → `RunController::exportPlanPdf(int $id, int $runId): void`. `Middleware::auth()`, progetto via `findAccessible`, run del progetto, altrimenti 404. Header `Content-Type: application/pdf`, `Content-Disposition: attachment; filename="…"`. Errore mPDF → log (`storage/logs`) e redirect al report con flash "Export non riuscito, riprova"; mai pagina bianca.
- Nel report (`views/runs/show.php`), testata accanto a "Rianalizza": link **"Esporta PDF"** con icona Heroicons `arrow-down-tray`, stile bordo (classi di "Rianalizza"), visibile quando il run ha analisi (`$hasAnalyses`) e almeno un intervento. Sempre attivo.

## 4. Consegna 2 — Scheda operativa per singolo intervento

### 4.1 Flusso
Nel report, dentro la riga dell'intervento (sezione a scomparsa esistente), pulsante **"Genera scheda"** (1 credito). Al clic: conferma ("1 credito · 20-40 secondi"), spinner "Genero la scheda…", una chiamata AJAX lunga; a risposta ricevuta la riga si aggiorna con la scheda (senza ricaricare tutta la pagina: il controller risponde con l'HTML della scheda renderizzato via `View::partial`). Se esiste già: link "Rigenera scheda" con conferma (sovrascrive, spende 1 credito).

Nessun "genera tutte", nessun batch, nessun lock sul run: una richiesta alla volta per intervento. Un doppio clic sullo stesso intervento è impedito lato client (pulsante disabilitato durante la chiamata); lato server, se arriva comunque, la seconda chiamata trova la scheda già salvata e ritorna quella senza richiamare l'AI (controllo `brief_generated_at` più recente di 2 minuti → ritorna l'esistente).

### 4.2 Servizio
`modules/ai-reputation/services/ActionBriefService.php`
- `__construct()` — `new AiService('ai-reputation')`.
- `model(): string` — `ModuleLoader::getSetting('ai-reputation', 'brief_model', 'claude-opus-5-5')`; se `global`, non passa `model` a `complete()` (usa quello di AiService).
- `generate(int $actionId, int $userId): array` — carica azione, run e progetto; costruisce il dossier (§4.4); chiama `AiService::complete($userId, [['role' => 'user', 'content' => $dossierPrompt]], ['model' => $model, 'max_tokens' => 8192, 'effort' => 'high', 'system' => $systemPrompt], 'ai-reputation')`; `Database::reconnect()`; valida (§4.3); salva `channel`, `channel_rationale`, `suggested_outlets`, `brief`, `brief_model` (= `model` restituito da AiService, così se scatta il fallback OpenAI è registrato quello reale), `brief_generated_at`, `brief_error = ''`. Ritorna `['success' => true, 'action' => $actionAggiornata]` oppure `['success' => false, 'error' => 'messaggio corto']` dopo aver scritto `brief_error`. I crediti li consuma il controller solo su `success`.
- `parseJson(string $text): ?array` — stessa pulizia di `JudgeService::parseJson` (rimozione ``` e ritaglio al primo `{` / ultimo `}`); duplicata in forma di metodo statico in `ActionBriefService` per non accoppiare i due servizi.

### 4.3 Output JSON richiesto (shape di `brief`)
System prompt con le regole: italiano; niente costi di testate; niente spiegazione del metodo; non inventare fatti: ogni fatto citato deve venire dal dossier con la sua fonte; rispondere solo con JSON.

Contenuti (`counter_content`, `gap_article`, `correction`):
```json
{
  "channel": "own_site|external|both",
  "channel_rationale": "…",
  "suggested_outlets": ["dominio1", "dominio2"],
  "brief": {
    "kind": "content",
    "title": "titolo proposto",
    "angle": "taglio in 1-2 frasi",
    "points": ["punto 1", "…"],
    "facts": [{"fact": "…", "source": "url o dominio dal dossier"}],
    "avoid": ["…"],
    "length_words": 900,
    "language": "it",
    "own_site_note": "cosa pubblicare sul sito ufficiale se channel è own_site o both (può essere vuoto)"
  }
}
```
Rimozioni (`removal`):
```json
{
  "channel": null,
  "channel_rationale": "",
  "suggested_outlets": [],
  "brief": {
    "kind": "removal",
    "recipient": "a chi scrivere (redazione, webmaster, ufficio stampa)",
    "request": "removal|deindex|update",
    "basis": "su quale base (es. notizia superata, esito del procedimento, diritto all'oblio)",
    "pages": ["url", "…"],
    "fallback": "cosa fare se rifiutano (contro-contenuto, aggiornamento)"
  }
}
```
Validazione (`ActionBriefService::validate(array $data, array $action, array $okDomains): array|string`): `channel` nell'enum (NULL per le rimozioni), `brief.kind` coerente col tipo, `points` ≥ 3 per i contenuti, `pages` ⊆ `target_urls`, `suggested_outlets` filtrate ai domini "ok" del dossier (le testate non presenti vengono scartate: l'AI non può inventare fonti). Ritorna i dati normalizzati oppure una stringa di errore → `brief_error`, nessun salvataggio, nessun credito.

### 4.4 Dossier passato all'AI (per intervento)
Tutto da dati già nel DB, nessuna chiamata esterna:
- profilo confermato del soggetto (`ar_profile_facts` con status confermato), nome, tipo, città, `website`, note di disambiguazione;
- l'intervento: tipo, titolo, rationale, pagine (`target_urls`), dominio;
- le domande collegate: per `counter_content`/`gap_article` le domande rep/comm/comp del run con verdetto negativo/misto/non citato; per `removal` le domande (`ar_analyses`) in cui le pagine del sito sono state citate (`negative_urls`/`cited_domains`);
- per quelle domande: verdetto per engine e riassunto (`ar_analyses.summary`);
- Source Map del run (`ar_sources`): domini "ok" e "negativi" con conteggi; i top 5 "ok" per citazioni vengono dichiarati come "testate che le AI citano come fonti affidabili" (lista usata anche per validare `suggested_outlets`);
- se `website` del soggetto compare tra i domini citati del run (sì/no, quante volte);
- competitor nominati (`ar_competitors`).

### 4.5 Route e controller
In `RunController` (pattern route del modulo, `Middleware::auth()`, `findAccessible`, CSRF `_csrf_token`):

| Route | Cosa |
|---|---|
| `POST /ai-reputation/project/{id}/runs/{runId}/actions/{actionId}/brief` | AJAX lungo (GR 15/17/23): `ignore_user_abort(true)`, `set_time_limit(300)`, `ob_start()`, `session_write_close()`; controllo `Credits::hasEnough($ownerId, $cost)` (402 se no); `ActionBriefService::generate`; su success `Credits::consume($ownerId, $cost, 'action_brief', 'ai-reputation')`; risposta JSON `{success, html}` dove `html` è il partial `views/partials/action-brief.php` renderizzato per l'azione aggiornata; su errore `{success:false, error}`. `ob_end_clean()` prima di ogni `echo`, inclusi gli early return |

Il partial `views/partials/action-brief.php` è usato sia dal report (render iniziale) sia dalla risposta AJAX, così la scheda è identica nei due casi; la stessa struttura è ripresa nel template PDF (`views/pdf/action-plan.php`) con markup proprio.

### 4.6 UI nella riga intervento (`$actionRow` in `views/runs/show.php`)
Sotto motivazione e pagine, il partial `action-brief.php`:
- se `brief` NULL: pulsante **"Genera scheda"** (pieno indigo, piccolo, icona Heroicons `sparkles`) + testo "1 credito · 20-40 s"; se `brief_error` non vuoto: riga rossa "Generazione non riuscita: <errore>" e pulsante "Riprova";
- se `brief` presente: per i contenuti, badge canale (`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium`, indigo = Sito proprietario, teal = Esterno, amber = Entrambi), testate suggerite come link, `channel_rationale`, poi il brief con le voci: titolo proposto, taglio, punti da coprire, fatti da citare (con fonte), cosa evitare, lunghezza e lingua, nota per il sito ufficiale; per le rimozioni: destinatario, cosa chiedere, su quale base, pagine, se rifiutano;
- riga finale piccola "Scheda generata il … con <modello>" + link "Rigenera scheda" (conferma).
Tutto italiano, dark mode, Heroicons; frontend con `response.ok` prima di `response.json()` (GR 24), CSRF `_csrf_token` nel body del POST; pulsante disabilitato durante la chiamata.

### 4.7 Errori
- Chiamata AI fallita, JSON non valido o validazione fallita → `brief_error`, messaggio nella riga, nessun credito.
- Crediti insufficienti → 402 con messaggio, nessuna chiamata.
- Errori imprevisti → log (`Logger` esistente) e JSON di errore; mai pagina bianca.

## 5. Crediti e costi
- 1 credito a scheda (`cost_action_brief`), scalato solo a scheda salvata; costo API reale nei log AI come oggi (stima 0,02-0,04 $ a scheda con Opus 5.5).
- L'export PDF è gratuito.
- Il judge resta sul modello del modulo/globale (riproducibilità tra run). Cambiarlo è una decisione separata.

## 6. Test
- `php -l` su ogni file; migrazione applicata al DB locale `seo_toolkit`.
- Consegna 1: su un run analizzato in locale, scarico il PDF via browser e lo apro in Chrome: intestazione, badge, titoli, pagine con link, interventi scartati assenti, piè di pagina.
- Consegna 2: su 2-3 interventi (un contenuto, una rimozione) genero la scheda, controllo canale/testate/brief/richiesta, poi riesporto il PDF e verifico che le schede compaiano. Caso d'errore: chiave Anthropic sbagliata → messaggio nella riga, nessun credito scalato; "Riprova" funziona.
- Deploy: `git pull` su ainstein.it + migrazione; verifica su un run reale.

## 7. Documentazione
- `modules/ai-reputation/docs/decisions.md`: ADR-012 (canale deciso dall'AI; il sito proprietario del soggetto diventa un canale possibile, con il segnale "sito ufficiale mai citato dalle AI"), ADR-013 (schede su Opus 5.5 via `brief_model`, generazione per singolo intervento su richiesta, judge invariato; modelli 5.5 aggiunti ad AiService).
- `TASKS.md` e `roadmap.md` del modulo.
- Guida utente `shared/views/docs/ai-reputation.php` (sezione "Schede operative e PDF") e `docs/data-model.html` (colonne nuove di `ar_actions`).

## 8. Fuori scope
PDF cliente (fase C), export Word/Excel, "genera tutte le schede" in un colpo, tracciamento dello stato degli interventi nel PDF, scelta del canale con regole fisse (lo decide l'AI), cambio modello del judge, sezione "Perché" (domande ed engine) nel PDF.
