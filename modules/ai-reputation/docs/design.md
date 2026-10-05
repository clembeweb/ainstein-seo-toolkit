# AI Reputation Radar — Design (analisi rifatta)

> Versione 0.1 del 2026-10-05. Stato: **bozza, da validare con i test empirici** (vedi
> `test-empirici/`). Le righe marcate ⚠️ dipendono da una verifica online non ancora fatta.
> Principio guida (Clemente, 2026-10-05): **semplice e funzionante, niente complicazioni;
> verificare sempre online cosa è disponibile prima di costruire.**
> Decisioni in `decisions.md`; stato in `TASKS.md`.

## 0. In una frase

Un modulo Ainstein che, dato solo un nome, costruisce il profilo del soggetto, genera le domande
che la gente fa alle AI su di lui, le pone davvero a più motori via API, giudica le risposte e
restituisce metriche + **piano d'azione** (cosa far rimuovere, cosa far pubblicare).

## 1. Chi lo usa e perché

| Utente | Cosa vuole vedere | Cosa ci guadagna |
|---|---|---|
| Tutela Digitale (Gabriele + "il ragazzo") | contenuti negativi e le **fonti** che li alimentano; storico; più clienti in un cruscotto | lavoro di rimozione da vendere |
| Clemente | gap di visibilità, fonti mancanti, competitor citati al posto del cliente | articoli + testate da vendere (digital PR) |
| Cliente finale (Marcaccini) | report white-label leggibile | capire se le AI lo trattano da esperto |

Il valore che **non** si fa a mano: riproducibilità, storico e diff tra run, scala multi-cliente,
piano d'azione automatico. La demo va puntata lì, non sulla singola risposta.

## 2. Cosa Ainstein dà già (riuso, zero sviluppo)

| Blocco brief | Cosa c'è | Dove |
|---|---|---|
| 1 Multi-tenant | Global Projects, un progetto per cliente, condivisione Owner/Editor/Viewer | `core/Models/GlobalProject.php`, `ProjectAccessService` |
| 1 White-label | logo/colori nei report ed email | `core/BrandingHelper.php` |
| 7 Alert | notifiche in-app + email | `services/NotificationService.php` |
| 7 Report email | template modificabili dall'admin | `services/EmailService.php` |
| 7 Scheduling | cron dispatcher pattern | `modules/seo-tracking/cron/*-dispatcher.php` |
| Fetcher proprio | fetch con UA/retry/bot-protection | `services/ScraperService.php` (`fetchRaw()`) |
| Job lunghi | SSE + job queue | `modules/seo-tracking/controllers/RankCheckController.php` |
| Log e costi | log API esterne, crediti | `ApiLoggerService`, `core/Credits.php` |
| UI | tabelle, paginazione, KPI card | `shared/views/components/` |

## 3. Cosa va costruito

### 3.1 Onboarding agent (Step 1)
Input: nome (+ sito/città opzionali) + **campo libero "Omonimi e soggetti da non confondere"**
(persone e aziende, facoltativo, ADR-008: entra nel prompt dell'agent e del judge).
Pipeline, la più semplice che funziona:
1. ricerca web ⚠️ (opzione A: chiedere a un engine del collector "chi è X" con web search e usare
   le sue citazioni come lista URL; opzione B: SERP API DataForSEO già integrata. Si sceglie nel test);
2. `ScraperService::fetchRaw()` sui top URL → testi;
3. una chiamata **AiService** che produce il profilo in JSON: identità, attività, alias, persone
   collegate, fatti chiave, temi di rischio, omonimi, fonti;
4. salvataggio come righe `ar_profile_facts` con `status = proposed`;
5. UI conferma riga per riga: ✅ confirmed · ❌ rejected (diventa "errore da monitorare") · ✏️ corrected.

Solo le righe `confirmed/corrected` sono la **verità del brand** usata dall'analyzer.
Se un run fa emergere una possibile omonimia non dichiarata, il tool **non decide**: crea una riga
`homonym` in stato `proposed` e la mette nel report in "Da confermare" (✅ è lui / ❌ è un altro).
MVP: solo segnalazione. v1: run in pausa finché l'utente non conferma (ADR-008).

### 3.2 Prompt engine (Step 2)
Una chiamata AiService con il profilo confermato → 40-60 prompt in JSON: cluster
(`nav|rep|comm|comp`), lingua (`it|en`), persona (`neutro|investitore|giornalista|cliente_arrabbiato`),
testo. Salvati in `ar_prompts`, attivabili/disattivabili, rigenerabili. Aggiunta manuale possibile.

### 3.3 Collector (Step 3) — il cuore nuovo
Servizio dedicato `AiEngineCollectorService` con un adapter per engine e un'interfaccia comune:

```
EngineAdapter::ask(string $prompt): EngineResponse
EngineResponse { engine, model, text, citations[] {url, title?, domain}, raw_json,
                 tokens_in, tokens_out, cost_estimate, latency_ms }
```

| Engine | API | Da dove escono le fonti | ⚠️ da verificare online |
|---|---|---|---|
| OpenAI | Responses API + tool `web_search` | annotazioni `url_citation` (url, title) + `web_search_call.action.query` | ✅ testato 2026-10-05: 10 $/1k ricerche; cap ricerche da aggiungere (`max_tool_calls`) |
| Perplexity | **Agent API `POST /v1/agent`** con `preset: fast` (sostituto ufficiale di `sonar`; le Chat Completions Sonar sono chiuse dal 2026-09-27) + tool `web_search` | output item `search_results` (url, title, snippet, date, id) + annotazioni `url_citation` nel messaggio + marker inline `[n]` risolti su `id` | ✅ testato 2026-10-05: `fast` gira su `openai/gpt-6-luna` e cita inline; `perplexity/sonar` esplicito NON cita. ≈0,0014 $/prompt, costo reale in `usage.cost.total_cost` |
| Gemini | `generateContent` + tool `google_search` | `groundingMetadata.groundingChunks` | URL redirect `vertexaisearch…` da risolvere |
| Anthropic | Messages + `web_search_20260318` **con `allowed_callers: ["direct"]`** | `citations[]` tipo `web_search_result_location` (url, title, cited_text) | ✅ testato 2026-10-05: senza `direct` zero citazioni; 10 $/1k ricerche |

Regole (ADR-002):
- **nessun fallback** tra engine: un errore è un dato (`ar_responses.status = error`);
- ogni chiamata loggata con `ApiLoggerService` (provider = nome engine);
- temperatura di default dell'engine: vogliamo misurare la variabilità reale;
- `repeats` configurabile (MVP: 1; v1: 3 → score di stabilità);
- esecuzione come job SSE/cron (centinaia di call per run).

### 3.4 Analyzer (Step 4)
Per ogni risposta una chiamata AiService (judge) con: testo, citazioni, verità del brand, errori da
monitorare, nome + alias + omonimi. Output JSON rigido:

```
{ brand_mentioned: bool, mention_position: int|null, is_homonym: bool,
  sentiment: -2..2, claims: [{text, matches_truth: true|false|unknown, source_url?}],
  competitors: [name], negative: bool, negative_reasons: [..], negative_urls: [..],
  cited_domains: [..] }
```
Salvato in `ar_analyses`. Il judge gira sempre sullo stesso modello (riproducibilità).

### 3.5 Metriche (Step 5, derivate, nessuna AI)
- **AI Share of Voice** = risposte con brand citato / totali (per cluster, per engine)
- **Citation Share per dominio** = citazioni del dominio / citazioni totali
- **Sentiment Index** = media sentiment delle risposte con menzione
- **Reputation Risk Score** = f(risposte negative, domini negativi distinti, peso cluster `rep`)
- **Source Map** = domini citati con conteggio e sentiment medio
- v1: **Stabilità** (concordanza tra repeats) e **Diff** vs run precedente

### 3.6 Piano d'azione (Step 6, regole + 1 chiamata AI)
Regole deterministiche generano `ar_actions`:
- URL negativo citato → `removal` (target TD)
- cluster `comm`/`comp` senza menzione ma con competitor → `gap_article` (target Clemente), con
  testata proposta presa dalla Source Map (domini che le AI citano davvero)
- claim `matches_truth = false` → `correction`
- risposta `rep` negativa o ambigua (omonimo) → `counter_content`: articolo che risponde alla domanda reputazionale, su testata citata dalle AI (ADR-007)

Una chiamata AiService riscrive le azioni in linguaggio da report (titolo, perché, cosa fare).

### 3.7 Report e UI
MVP: una pagina "report run" (metriche in testa → azioni → prompt/risposta/citazioni/giudizio).
Stampabile via browser. Dashboard, trend e scheduling in v1.

## 4. Data model (prefisso `ar_`)

| Tabella | Scopo | Campi chiave |
|---|---|---|
| `ar_projects` | un soggetto monitorato | user_id, global_project_id, subject_name, subject_type (person/company), website, city, **disambiguation_notes** (TEXT, omonimi persone/aziende, ADR-008), status |
| `ar_profile_facts` | righe del profilo | project_id, category (identity/activity/alias/person/fact/risk/homonym/source), text, status (proposed/confirmed/rejected/corrected), corrected_text, source_url |
| `ar_prompts` | domande monitorate | project_id, cluster, lang, persona, text, is_active, origin (ai/manual) |
| `ar_runs` | un'esecuzione | project_id, status, engines (json), repeats, started_at, finished_at, cost_total |
| `ar_responses` | una risposta grezza | run_id, prompt_id, engine, model, repeat_idx, status, text, citations (json), raw (json), tokens_in/out, cost, latency_ms |
| `ar_analyses` | giudizio di una risposta | response_id, brand_mentioned, mention_position, is_homonym (no/yes/uncertain), sentiment, claims (json), competitors (json), negative, negative_urls (json), cited_domains (json), judge_model |
| `ar_sources` | domini aggregati | project_id, domain, citations_count, negative_count, sentiment_avg, first_seen_run_id, last_seen_run_id |
| `ar_competitors` | nomi emersi | project_id, name, mentions_count, first_seen_run_id, is_confirmed |
| `ar_actions` | piano d'azione | project_id, run_id, type (removal/gap_article/correction/counter_content), target_url, target_domain, title, rationale, status |

Le metriche non hanno tabella nell'MVP: si calcolano da `ar_analyses`. In v1 `ar_run_metrics` per i trend.

## 5. Costi (⚠️ listini da verificare online nel test)

Volume pieno: 50 prompt × 4 engine × 3 repeat = **600 call/settimana/cliente** + 600 judge.
Stima grossolana: 8-15 €/settimana/cliente. Voci pesanti: Gemini grounding e OpenAI web search.
Perplexity la più economica. Demo: 50 × 2 engine × 1 = 100 call, ~1-2 €.
Il canone si dimensiona dopo il test, non prima.

## 6. Rischi e mitigazioni

| Rischio | Mitigazione |
|---|---|
| API ≠ ChatGPT consumer | dichiarato nel report come "misura riproducibile via API"; pregio per i trend (ADR-005) |
| Variabilità delle risposte | repeats + score di stabilità (v1); MVP mostra 1 run e lo dice |
| Omonimi (Marcaccini calciatore ecc.) | lista omonimi nel profilo → judge → flag `is_homonym` |
| Siti che bloccano il fetch | `fetchRaw()`; se fallisce si usa lo snippet della citazione |
| Marcaccini "pulito": demo senza negativi | puntare su storico/diff/scala (ADR-004) |
| Costi fuori controllo su più clienti | crediti per run + cap engines/repeats per progetto |
| Metodo copiabile | in call solo output; nessun dettaglio su prompt e judge |

## 7. Sequenza di costruzione (MVP call)

1. **Test empirici** degli engine (ADR-006): verifica online docs e listini, poi 1 script per
   engine, 3 prompt su Marcaccini, JSON grezzo salvato. **Blocca tutto il resto.**
2. Migrazione DB `ar_*` + `module.json` + attivazione da Global Projects.
3. Onboarding agent + UI conferma righe.
4. Prompt engine.
5. Collector (OpenAI + Perplexity) come job.
6. Analyzer + metriche base.
7. Pagina report run + piano d'azione.
8. Run completo su Marcaccini, revisione output per la call.

## 8. Fuori scope MVP (esplicito)

Dashboard trend, scheduling, diff tra run, alert WhatsApp, PDF white-label, Gemini e Anthropic
come engine, Google AI Overviews, grafo fonti, simulazioni. Tutto in `roadmap.md`.
