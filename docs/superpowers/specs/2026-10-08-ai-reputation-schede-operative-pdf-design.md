# AI Reputation Radar — Schede operative degli interventi + export PDF

> Design approvato a blocchi con Clemente il 2026-10-08. Modulo `ai-reputation`, branch `claude/ai-reputation-radar-dd9004`.
> Fase B della richiesta ("PDF operativo per chi esegue gli interventi"). La fase C (PDF per il cliente finale) è fuori scope e riuserà lo stesso motore.

## 1. Obiettivo

Chi esegue gli interventi del piano d'azione (Tutela Digitale, il copywriter, chi scrive ai siti) deve ricevere un **PDF con una scheda operativa per ogni intervento**: dove pubblicare (sito proprietario del soggetto, siti esterni o entrambi), perché, e un brief completo del contenuto; per i siti da contattare, la traccia della richiesta.

Le schede sono generate **su richiesta** dall'AI (pulsante nel report), salvate nel DB, visibili nel report web ed esportate in PDF. Il PDF è la stampa di ciò che si vede nel report.

Regole commerciali del modulo che valgono anche qui: mai esporre il metodo, mai costi delle testate, tutto in italiano.

## 2. Dati

Migrazione `modules/ai-reputation/database/2026-10-08-actions-brief.sql` su `ar_actions`:

| Colonna | Tipo | Significato |
|---|---|---|
| `channel` | ENUM('own_site','external','both') NULL | Canale deciso dall'AI per i contenuti. Sempre NULL per le rimozioni (il canale non si applica) |
| `channel_rationale` | TEXT NULL | Perché quel canale (1-3 frasi) |
| `suggested_outlets` | JSON NULL | 1-3 domini esterni suggeriti, es. `["ilsole24ore.com","wired.it"]` |
| `brief` | JSON NULL | Scheda operativa (shape in §3.3) |
| `brief_model` | VARCHAR(80) NULL | Modello che ha generato la scheda |
| `brief_generated_at` | DATETIME NULL | Quando |
| `brief_error` | VARCHAR(500) NULL | Ultimo errore di generazione (vuoto se ok) |

Nessuna tabella nuova. Su `ar_runs` due colonne dedicate al lock della generazione schede: `briefs_lock_token` VARCHAR(40) NULL, `briefs_locked_at` TIMESTAMP NULL (separate dal lease della raccolta, vedi §3.5).

Nuove impostazioni in `module.json` (gruppo `ai_config` / `costs`):
- `brief_model` (select, default `claude-opus-5-5`; opzioni: `claude-opus-5-5`, `claude-sonnet-5-5`, `global`) — il judge **non** cambia (regola 6 del modulo).
- `cost_action_brief` (number, default 1) — crediti per scheda generata.

Modifica a `services/AiService.php::MODELS['anthropic']`: aggiunti `claude-opus-5-5` (4 / 20 $ per M token), `claude-sonnet-5-5` (2 / 10), `claude-haiku-5-5` (0,10 / 0,50); nomi "Claude Opus 5.5", ecc. I prezzi in `MODELS` sono per 1K token (0.004 / 0.020, 0.002 / 0.010, 0.0001 / 0.0005). `AiService` manda solo `model`, `max_tokens`, `messages`, `system`: compatibile con Opus 5.5 (niente `temperature`, niente prefill). Unica aggiunta: `complete()` accetta l'opzione `effort` e, solo per Anthropic, la invia come `output_config: {effort}` (Opus 5.5 altrimenti lavora a `medium`); le schede usano `high`.

## 3. Generazione delle schede

### 3.1 Servizio
`modules/ai-reputation/services/ActionBriefService.php`
- `__construct()` — `new AiService('ai-reputation')`; il modello usato è `brief_model` (se `global`, quello di AiService). La chiamata è `AiService::complete($userId, $messages, ['model' => $briefModel, 'max_tokens' => 8192, 'system' => $systemPrompt], 'ai-reputation')`: `complete()` accetta già `model`, `max_tokens` e `system` nelle opzioni (verificato).
- `generateBatch(int $runId, int $userId, int $limit = 4): array` — processa al massimo `$limit` azioni del run con `status <> 'dismissed'` e `brief IS NULL` (le fallite vengono riprovate solo se `brief_error` è vuoto o se il chiamante passa `retryFailed`), nell'ordine del piano; per ognuna costruisce il dossier, chiama l'AI, valida, salva. Ritorna `['total', 'done', 'failed', 'pending']`. `Database::reconnect()` dopo ogni chiamata (GR 10). Crediti: `Credits::consume` solo a scheda salvata. Il batch da 4 tiene ogni richiesta HTTP sotto i 300 s anche con Opus 5.5 (30-40 s a scheda).
- `reset(int $runId): void` — azzera `brief*` di tutte le azioni del run (usato da "Rigenera le schede", `force=1`), poi si riparte a batch.
- Il modello salvato in `brief_model` è quello restituito da AiService (`model_used`): se il fallback su OpenAI scatta, viene registrato quello reale.
- `generateOne(int $actionId, int $userId): bool` — per il link "Rigenera questa scheda".
- `status(int $runId): array` — `['total', 'done', 'failed', 'pending']` leggendo `ar_actions` (done = `brief IS NOT NULL`, failed = `brief_error <> ''`).

### 3.2 Dossier passato all'AI (per intervento)
Tutto da dati già nel DB, nessuna chiamata esterna:
- profilo confermato del soggetto (`ar_profile_facts` status confermato), nome, tipo, città, `website`, note di disambiguazione;
- l'intervento: tipo, titolo, rationale, pagine (`target_urls`), dominio;
- le domande collegate: per `counter_content`/`gap_article` le domande rep/comm/comp citate nel rationale; per `removal` le domande in cui le pagine sono state citate (da `ar_analyses.negative_urls`/`cited_domains`);
- per quelle domande: verdetto per engine, riassunto, domini citati "ok" e "negativi" con conteggi (Source Map del run, `ar_sources`);
- se `website` del soggetto compare mai tra i domini citati del run (sì/no, quante volte);
- competitor nominati (`ar_competitors`);
- riga di contesto: "Le testate che seguono sono citate dalle AI come fonti affidabili: …" (top 5 domini ok per numero di citazioni).

### 3.3 Output JSON richiesto (shape di `brief`)
Prompt di sistema con le regole (italiano, niente costi, niente metodo, non inventare fatti: ogni fatto citato deve venire dal dossier con la sua fonte). Risposta solo JSON, pulizia ``` come in `JudgeService::parseJson`.

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
  "channel_rationale": "…",
  "suggested_outlets": [],
  "brief": {
    "kind": "removal",
    "recipient": "a chi scrivere (redazione, webmaster, ufficio stampa, PEC se noto dal dossier)",
    "request": "removal|deindex|update",
    "basis": "su quale base (es. notizia superata, esito del procedimento, diritto all'oblio)",
    "pages": ["url", "…"],
    "fallback": "cosa fare se rifiutano (contro-contenuto, aggiornamento)"
  }
}
```
Validazione: `channel` nell'enum (NULL per le rimozioni), `brief.kind` coerente col tipo, `points` ≥ 3 per i contenuti, `pages` ⊆ `target_urls`, `suggested_outlets` filtrate ai domini "ok" passati nel dossier (le testate non presenti vengono scartate: l'AI non può inventare fonti). Se non valida → `brief_error`, nessun salvataggio, nessun credito.

### 3.4 Route e controller (`RunController` o nuovo `BriefController`)
Pattern route del modulo: `/ai-reputation/project/{id}/runs/{runId}/…`, tutte con `Middleware::auth()`, progetto via `findAccessible`, CSRF `_csrf_token` sulle POST.

| Route | Cosa |
|---|---|
| `POST …/briefs/generate` (body: `force=0/1`, `retry_failed=0/1`) | AJAX lungo (GR 15/17/23): `ignore_user_abort`, `set_time_limit(300)`, `ob_start`, `session_write_close`; con `force=1` prima `reset()`; controllo crediti su tutte le schede ancora da fare (`pending × cost_action_brief`); poi `generateBatch` (max 4). Risposta JSON `{success, total, done, failed, pending}`; il frontend richiama finché `pending > 0`. `ob_end_clean()` prima di ogni `echo`, inclusi gli early return |
| `GET …/briefs/status` | JSON `{total, done, failed, pending, running}` per il contatore (polling ogni 2 s) |
| `POST …/actions/{actionId}/brief/regenerate` | rigenera una sola scheda (AJAX breve, stessa cura su `ob_*`) |
| `GET …/export/plan.pdf` | PDF (§4) |

### 3.5 Concorrenza e ripresa
- Il generatore salva ogni scheda appena pronta: una pagina chiusa a metà non perde lavoro; il clic successivo processa solo le azioni con `brief IS NULL` (o tutte se `force`).
- Doppio clic: ogni batch prende un lock su `ar_runs.briefs_lock_token`/`briefs_locked_at` (UPDATE condizionale: token NULL o `briefs_locked_at` più vecchio di 5 minuti); se non lo ottiene risponde `{success:false, error:'Generazione già in corso'}`. Il lock si libera a fine batch (anche su errore, in `finally`). Colonne separate dal lease della raccolta (`locked_by`/`locked_at`), così il resto del modulo non scambia la generazione schede per un run in corso.
- `status` espone `running` leggendo quel lock.

### 3.6 Errori
- Chiamata fallita / JSON non valido → `brief_error` con messaggio corto, si prosegue; a fine lavoro il report mostra "N schede non riuscite — riprova".
- Crediti insufficienti → 402 con messaggio, nessuna chiamata.
- Errori imprevisti → log in `storage/logs` (Logger esistente) e JSON di errore; mai pagina bianca.

## 4. PDF

- `modules/ai-reputation/services/ActionPlanPdfService.php` con mPDF (`mpdf/mpdf` già in `composer.json`). Metodo `render(int $runId): string` (binario PDF). Il controller manda `Content-Type: application/pdf` e `Content-Disposition: attachment; filename="piano-interventi-<slug soggetto>-run<id>-<data>.pdf"`.
- Template `modules/ai-reputation/views/pdf/action-plan.php`: HTML semplice con CSS inline (niente Tailwind: mPDF non lo rende), font DejaVu Sans, colore indigo `#4f46e5` per intestazioni e badge, link cliccabili.
- Struttura: (1) copertina — soggetto, data run, rischio, conteggio interventi per tipo; (2) indice — tabella tipo / titolo / canale / stato; (3) una scheda per intervento, prima i contenuti poi le rimozioni, con sezioni **Dove**, **Brief** o **Richiesta**, **Perché** (domande ed engine coinvolti). Interventi `dismissed` esclusi; stato in badge.
- Scheda mancante → riquadro "Scheda da generare" al posto del brief; l'export non si blocca. Il pulsante nel report è comunque attivo solo a schede complete (§5).
- Errore mPDF → log + messaggio "Export non riuscito, riprova" (nessuna pagina bianca).

## 5. Interfaccia (report del run, `views/runs/show.php`)

Testata, accanto a "Rianalizza" (solo `canEdit` e run non attivo):
- **Prepara le schede** — pulsante pieno indigo (classi di "Analizza le risposte"). Al clic: conferma con costo ("7 schede · 7 crediti, saldo N"), poi stato "Preparo le schede… 3 di 7" con spinner: il JS chiama `briefs/generate` in sequenza (ogni risposta aggiorna il contatore) finché `pending = 0`, poi reload. `briefs/status` serve al caricamento pagina per sapere se una generazione è in corso da un'altra scheda del browser. Se tutte le schede esistono: diventa **Rigenera le schede** (bordo) e chiede conferma perché sovrascrive e spende crediti (`force=1`).
- **Esporta PDF** — pulsante con bordo; `disabled` + tooltip "Prima prepara le schede" finché `pending > 0`.
- Avviso rosso sotto la testata se `failed > 0`: "N schede non riuscite — riprova".

Riga intervento (dentro `$actionRow`, sezione a scomparsa già esistente):
- badge canale (`inline-flex … rounded-full text-xs font-medium`): Sito proprietario / Esterno / Entrambi, colori indigo/teal/amber; testate suggerite come link (solo per i contenuti: le rimozioni non hanno canale);
- `channel_rationale` in una riga;
- brief o richiesta con le stesse voci del PDF (liste puntate compatte);
- riga finale piccola "Scheda generata il … con <modello>" + link "Rigenera questa scheda" (POST con conferma);
- se manca: riquadro tratteggiato "Scheda non ancora generata" (o l'errore, se `brief_error`).

Tutto italiano, dark mode, Heroicons, frontend con `response.ok` prima di `response.json()` (GR 24), CSRF `_csrf_token`.

## 6. Crediti e costi
- 1 credito a scheda (`cost_action_brief`), scalato solo a scheda salvata; costo API reale nei log AI come oggi (stima 0,02-0,04 $ a scheda con Opus 5.5, 5-10 schede a run).
- Il judge resta sul modello del modulo/globale (riproducibilità tra run). Cambiarlo è una decisione separata.

## 7. Test
- `php -l` su ogni file; migrazione applicata al DB locale `seo_toolkit`.
- Prova end-to-end in locale su un run analizzato di un progetto di test (4-5 domande): generazione schede, controllo di canale/brief/richiesta, PDF aperto in Chrome (copertina, indice, schede, link).
- Caso d'errore: chiave Anthropic sbagliata → "N schede non riuscite", nessun credito scalato; secondo clic riprende solo le mancanti.
- Doppio clic: il secondo POST risponde "già in corso".
- Deploy: `git pull` su ainstein.it + migrazione; verifica su un run reale.

## 8. Documentazione
- `modules/ai-reputation/docs/decisions.md`: ADR-012 (canale deciso dall'AI; il sito proprietario del soggetto diventa un canale possibile, con il segnale "sito ufficiale mai citato dalle AI"), ADR-013 (schede su Opus 5.5 via `brief_model`, judge invariato; modelli 5.5 aggiunti ad AiService).
- `TASKS.md` e `roadmap.md` del modulo.
- Guida utente `shared/views/docs/ai-reputation.php` (sezione "Schede operative e PDF") e `docs/data-model.html` (colonne nuove di `ar_actions`).

## 9. Fuori scope
PDF cliente (fase C), export Word/Excel, tracciamento dello stato degli interventi nel PDF, scelta del canale con regole fisse (scartata: lo decide l'AI), cambio modello del judge.
