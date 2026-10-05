# AI Reputation Radar — Design

> Versione 0.2 del 2026-10-05. Stato: **validato dai test empirici sui 4 engine** (note e JSON in
> `test-empirici/`). Le scelte per engine sono misurate, non ipotizzate. Nessuna riga ⚠️ aperta.
> Principio guida (Clemente, 2026-10-05): **semplice e funzionante, niente complicazioni;
> verificare sempre online cosa è disponibile prima di costruire.**
> Decisioni in `decisions.md` (ADR-001..008); stato in `TASKS.md`.

## 0. In una frase

Un modulo Ainstein che, dato solo un nome, costruisce il profilo del soggetto, genera le domande
che la gente fa alle AI su di lui, le pone davvero a più motori via API, giudica le risposte e
restituisce metriche + **piano d'azione** (cosa far rimuovere, cosa far pubblicare).

Perché serve, misurato il 2026-10-05 su Marcaccini, stessa domanda "è affidabile?", stesso giorno:
Perplexity gli attribuisce una confisca antimafia del 2013, Gemini dice "nessun problema noto",
OpenAI è nel mezzo con dubbio di omonimia, Claude non si pronuncia ma smaschera gli articoli
sponsorizzati. **Quattro verità diverse sulla stessa persona: il prodotto è vederle insieme.**

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
| API key dei 4 engine | campi in admin settings, righe `settings` | `/admin/settings` (OpenAI, Anthropic, Gemini, Perplexity) |

## 3. Cosa va costruito

### 3.1 Onboarding agent (Step 1)
Input: nome (+ sito/città opzionali) + **campo libero "Omonimi e soggetti da non confondere"**
(persone e aziende, facoltativo, ADR-008: entra nel prompt dell'agent e del judge).
Pipeline, la più semplice che funziona:
1. ricerca web: **una chiamata Perplexity `fast`** "Chi è X?" con `search_context_size: high` → i
   `search_results` (10-15 URL con titolo, data e **snippet lungo**) sono la lista fonti. Costa 0,0014 $,
   3 secondi, e gli snippet bastano spesso senza fetch. (Scelta tra le opzioni A/B della v0.1: A con
   Perplexity, perché è l'unico engine che restituisce snippet; DataForSEO resta fallback.)
2. `ScraperService::fetchRaw()` sui top URL → testi (se il fetch fallisce, resta lo snippet);
3. una chiamata **AiService** che produce il profilo in JSON: identità, attività, alias, persone
   collegate, fatti chiave, temi di rischio, omonimi, fonti;
4. salvataggio come righe `ar_profile_facts` con `status = proposed`;
5. UI conferma riga per riga: ✅ confirmed · ❌ rejected (diventa "errore da monitorare") · ✏️ corrected.

Solo le righe `confirmed/corrected` sono la **verità del brand** usata dall'analyzer.
Se un run fa emergere una possibile omonimia non dichiarata, il tool **non decide**: crea una riga
`homonym` in stato `proposed` e la mette nel report in "Da confermare" (✅ è lui / ❌ è un altro).
MVP: solo segnalazione. v1: run in pausa finché l'utente non conferma (ADR-008).

Caso reale dal test: il brief dava Instagram @fedemarcaccini come suo; Claude dice che è un maestro
di sci argentino. È esattamente una riga da ❌ nell'onboarding.

### 3.2 Prompt engine (Step 2)
Una chiamata AiService con il profilo confermato → 40-60 prompt in JSON: cluster
(`nav|rep|comm|comp`), lingua (`it|en`), persona (`neutro|investitore|giornalista|cliente_arrabbiato`),
testo. Salvati in `ar_prompts`, attivabili/disattivabili, rigenerabili. Aggiunta manuale possibile.

Lezione dal test: il cluster `comm` va calibrato sul posizionamento reale. "Migliori esperti di
investimenti in hotel di lusso in Italia" fa uscire JLL, CBRE, Cushman, Horwath HTL (advisor
istituzionali), mai una persona. Servono prompt dove una persona può essere la risposta
("quale analista immobiliare commenta il mercato alberghiero italiano?").

### 3.3 Collector (Step 3) — il cuore nuovo
Servizio dedicato `AiEngineCollectorService` con un adapter per engine e un'interfaccia comune:

```
EngineAdapter::ask(string $prompt): EngineResponse
EngineResponse { engine, model, text,
                 citations[]    {url, title?, domain}      // cosa ha CITATO nel testo
                 sources_read[] {url, title?, domain, snippet?}  // cosa ha LETTO (se l'engine lo dà)
                 queries[],      search_count,              // come ha cercato, quante ricerche
                 raw_json, tokens_in, tokens_out, cost, cost_is_real, latency_ms, status }
```

Formati e modalità **misurati** (script in `scripts/test-engine-*.php`, un adapter per script):

| Engine | Chiamata | Cosa ha citato | Cosa ha letto / come ha cercato | Note misurate |
|---|---|---|---|---|
| **OpenAI** | Responses API, `tools: [{type: web_search, search_context_size, user_location IT}]`, `include: web_search_call.action.sources` | `message.content[].annotations[]` tipo `url_citation` (url, title); pulire `?utm_source=openai` | `web_search_call.action.query` + `.sources` | 2-14 ricerche a risposta, 10 $/1k: **mettere `max_tool_calls`** e `low` per nav/rep. Modello `gpt-5-mini` |
| **Perplexity** | Agent API `POST /v1/agent`, `preset: fast`, `tools: [{type: web_search, search_context_size, user_location IT}]` | marker inline `[n]` nel testo → `search_results.results[].id` (nessuna annotazione) | output item `search_results` (queries[], results[] con url, title, **snippet**, date) | `fast` = `openai/gpt-6-luna` + indice Perplexity. `perplexity/sonar` esplicito **non cita**. 1 invocazione a risposta. `usage.cost.total_cost` = costo reale |
| **Gemini** | `generateContent`, `tools: [{googleSearch: {}}]`, modello `gemini-3.8-flash` | `groundingSupports[].groundingChunkIndices` → chunk citati | `groundingMetadata.groundingChunks[].web.{uri,title,domain}` + `webSearchQueries[]` | URL redirect `vertexaisearch`: HEAD senza follow → `Location`; `domain` già in chiaro. **Può non cercare affatto** (0 citazioni = dato, `search_count = 0`). Cita anche pagine fuori tema (rumore) |
| **Anthropic** | Messages, `tools: [{type: web_search_20260318, max_uses: 5, allowed_callers: ["direct"]}]` | `content[].citations[]` tipo `web_search_result_location` (url, title, cited_text) | `web_search_tool_result` (URL consultati); `usage.server_tool_use.web_search_requests` | Senza `direct` (filtraggio dinamico) **zero citazioni**. Modello: Opus 5.5 misurato; Sonnet 5.5 costa la metà, da misurare |

Regole (ADR-002):
- **nessun fallback** tra engine: un errore è un dato (`ar_responses.status = error`);
- ogni chiamata loggata con `ApiLoggerService` (provider = nome engine);
- temperatura di default dell'engine: vogliamo misurare la variabilità reale;
- `repeats` configurabile (MVP: 1; v1: 3 → score di stabilità);
- esecuzione come job SSE/cron (centinaia di call per run; latenze misurate 3-80 s a chiamata);
- gli engine sono l'oggetto misurato: niente `instructions`/system prompt che cambino il comportamento,
  salvo dove serve per ottenere le citazioni (Anthropic `direct`), e in quel caso lo si dichiara.

### 3.4 Analyzer (Step 4)
Per ogni risposta una chiamata AiService (judge) con: testo, citazioni (url + titolo + snippet se c'è),
verità del brand, errori da monitorare, nome + alias + omonimi dichiarati. Output JSON rigido:

```
{ brand_mentioned: bool, mention_position: int|null, is_homonym: "no"|"yes"|"uncertain",
  sentiment: -2..2, claims: [{text, matches_truth: true|false|unknown, source_url?}],
  competitors: [name], negative: bool, negative_reasons: [..], negative_urls: [..],
  cited_domains: [..], citations_about_subject: [url], citations_noise: [url] }
```
Salvato in `ar_analyses`. Il judge gira sempre sullo stesso modello (riproducibilità).

Due cose imparate dal test, obbligatorie nel prompt del judge:
- **verificare che la fonte citata parli del soggetto**: Gemini ha citato a "prova di affidabilità"
  pagine su truffe romantiche crypto e bollette. Vanno in `citations_noise`, non in Source Map;
- **omonimia** → `uncertain` quando la fonte descrive il soggetto in modo incompatibile col profilo
  confermato; mai `yes` o `no` dedotti dal nome (ADR-008).

### 3.5 Metriche (Step 5, derivate, nessuna AI)
- **AI Share of Voice** = risposte con brand citato / totali (per cluster, per engine)
- **Citation Share per dominio** = citazioni del dominio / citazioni totali (solo `citations_about_subject`)
- **Sentiment Index** = media sentiment delle risposte con menzione
- **Reputation Risk Score** = f(risposte negative, domini negativi distinti, peso cluster `rep`)
- **Source Map** = domini citati con conteggio e sentiment medio
- **Divergenza tra engine** = per ogni prompt `rep`, quanti engine negativi su quanti (il numero della demo)
- v1: **Stabilità** (concordanza tra repeats) e **Diff** vs run precedente

### 3.6 Piano d'azione (Step 6, regole + 1 chiamata AI)
Regole deterministiche generano `ar_actions`:
- URL negativo citato → `removal` (target TD)
- cluster `comm`/`comp` senza menzione ma con competitor → `gap_article` (target Clemente), con
  testata proposta presa dalla Source Map (domini che le AI citano davvero)
- claim `matches_truth = false` → `correction`
- risposta `rep` negativa → `counter_content`: articolo che risponde alla domanda reputazionale, su
  testata citata dalle AI (ADR-007). Se l'omonimia è confermata dall'utente, il `counter_content` è di
  disambiguazione; se no, affianca il `removal`.

Una chiamata AiService riscrive le azioni in linguaggio da report (titolo, perché, cosa fare).

Dal test, due regole di digital PR che il piano deve conoscere: (a) Claude e Perplexity riconoscono e
scontano le "tante uscite elogiative ravvicinate su testate minori"; (b) Gemini invece le usa come
prova di normalità. Meglio poche fonti autorevoli (Milano Finanza pesa più di dieci blog) che
rispondano alla domanda "è affidabile?".

### 3.7 Report e UI
MVP: una pagina "report run" (metriche in testa → **tabella engine × prompt `rep` con verdetto** →
azioni → "Da confermare" (omonimie) → prompt/risposta/citazioni/giudizio). Stampabile via browser.
Dashboard, trend e scheduling in v1.

## 4. Data model (prefisso `ar_`)

| Tabella | Scopo | Campi chiave |
|---|---|---|
| `ar_projects` | un soggetto monitorato | user_id, global_project_id, subject_name, subject_type (person/company), website, city, disambiguation_notes (TEXT, omonimi persone/aziende, ADR-008), engines (json: quali attivi), status |
| `ar_profile_facts` | righe del profilo | project_id, category (identity/activity/alias/person/fact/risk/homonym/source), text, status (proposed/confirmed/rejected/corrected), corrected_text, source_url |
| `ar_prompts` | domande monitorate | project_id, cluster, lang, persona, text, is_active, origin (ai/manual) |
| `ar_runs` | un'esecuzione | project_id, status, engines (json), repeats, started_at, finished_at, cost_total |
| `ar_responses` | una risposta grezza | run_id, prompt_id, engine, model, repeat_idx, status, text, citations (json), sources_read (json), queries (json), search_count, raw (json), tokens_in/out, cost, cost_is_real, latency_ms |
| `ar_analyses` | giudizio di una risposta | response_id, brand_mentioned, mention_position, is_homonym (no/yes/uncertain), sentiment, claims (json), competitors (json), negative, negative_urls (json), cited_domains (json), citations_noise (json), judge_model |
| `ar_sources` | domini aggregati | project_id, domain, citations_count, negative_count, sentiment_avg, first_seen_run_id, last_seen_run_id |
| `ar_competitors` | nomi emersi | project_id, name, mentions_count, first_seen_run_id, is_confirmed |
| `ar_actions` | piano d'azione | project_id, run_id, type (removal/gap_article/correction/counter_content), target_url, target_domain, title, rationale, status |

Le metriche non hanno tabella nell'MVP: si calcolano da `ar_analyses`. In v1 `ar_run_metrics` per i trend.
`raw` (json) si tiene sempre: è la prova per il cliente e il materiale per rifare il judge.

## 5. Costi (listini e misure del 2026-10-05)

Costo medio **a prompt** misurato sui 3 prompt Marcaccini (ricerca + token, dollari):

| Engine | Modello | Ricerca | Token | ≈ a prompt | 50 prompt |
|---|---|---|---|---|---|
| Perplexity | preset `fast` | 1 $/1k (fast) | irrisori | **0,0014** | 0,07 |
| Gemini | `gemini-3.8-flash` | 5.000/mese gratis, poi 14 $/1k | 0,75 / 3,75 $/M (+thinking) | 0,005-0,02 | 0,25-1 |
| OpenAI | `gpt-5-mini` | 10 $/1k per ricerca (2-14 a risposta!) | 0,25 / 2 $/M | 0,03-0,16 → con `max_tool_calls` ~0,05 | 2-4 |
| Anthropic | `claude-opus-5-5` direct | 10 $/1k | 4 / 20 $/M (40-50k in ingresso) | 0,20 (Sonnet 5.5 ≈ 0,10) | 10 (Sonnet 5) |
| Judge | AiService, modello fisso | — | ~2-3k token a chiamata | ~0,005 | 0,25 per engine |

Scenari (50 prompt, repeats 1):
- **Demo call** (ADR-004), OpenAI + Perplexity + judge: **≈ 2,5-4,5 $**.
- Tutti e 4 gli engine, Anthropic su Sonnet: ≈ 8-10 $; su Opus ≈ 13-15 $.
- **Volume pieno v1** 50 × 4 × 3 repeat settimanali: ≈ 25-30 $/settimana con Sonnet. Senza Anthropic ≈ 10 $.
  La stima v0.1 (8-15 €/settimana) regge solo senza Anthropic.
- Voci pesanti reali: **Anthropic** (contesto di ricerca enorme) e **OpenAI senza cap**; Gemini e
  Perplexity quasi gratis. Il canone si dimensiona su questi numeri: crediti per run = costo reale × margine.
- Nel modulo: `cost` reale dove l'API lo dà (Perplexity), altrimenti stima da listino in `module.json`
  (`cost_is_real = 0`). I listini cambiano (Gemini raddoppia dal 2027-01-01): tenerli in settings, non nel codice.

## 6. Rischi e mitigazioni

| Rischio | Mitigazione |
|---|---|
| API ≠ ChatGPT/Gemini consumer | dichiarato nel report come "misura riproducibile via API"; Perplexity `fast` è un modello OpenAI con indice Perplexity: si dichiara (ADR-005) |
| Variabilità delle risposte | repeats + score di stabilità (v1); MVP mostra 1 run e lo dice |
| Omonimi | campo in onboarding + `uncertain` dal judge + conferma utente, mai deduzione (ADR-008) |
| Citazioni di rumore (Gemini) | judge verifica che la fonte parli del soggetto → `citations_noise` |
| Engine che non cerca (Gemini) | `search_count = 0` registrato; Share of Voice lo mostra, non lo nasconde |
| Siti che bloccano il fetch | `fetchRaw()`; se fallisce si usa lo snippet (Perplexity li dà) |
| Fonte negativa reale sul primo soggetto | è il caso d'uso: `removal` + `counter_content`; la demo mostra la divergenza tra engine |
| Costi fuori controllo | cap `max_tool_calls` OpenAI, `max_uses` Anthropic, engines/repeats per progetto, crediti per run |
| API che cambiano sotto i piedi (Sonar chiusa, Interactions API nuova) | adapter separati, raw salvato, script di test rilanciabili in 1 minuto |
| Metodo copiabile | in call solo output; nessun dettaglio su prompt e judge |

## 7. Sequenza di costruzione (MVP call)

Costruito per fette verticali (ADR-009), tutto il 2026-10-05:
1. ~~Test empirici degli engine~~ ✅ 4 engine.
2. ~~Migrazione DB `ar_*` + `module.json` + attivazione da Global Projects~~ ✅
3. ~~Onboarding agent + campo omonimi + UI conferma righe~~ ✅ (`OnboardingService`, pagina Profilo)
4. ~~Prompt engine~~ ✅ (`PromptEngineService`)
5. ~~Collector come job~~ ✅ OpenAI + Gemini + Perplexity peso 0,3 (ADR-009), adapter Anthropic pronto (`EngineCollectorService`)
6. ~~Analyzer + metriche + divergenza~~ ✅ (`JudgeService`, `ReportBuilderService`)
7. ~~Pagina report run + piano d'azione + "Da confermare"~~ ✅
8. Run completo su Marcaccini, revisione output per la call. ← **qui**

## 8. Fuori scope MVP (esplicito)

Dashboard trend, scheduling, diff tra run, alert WhatsApp, PDF white-label, blocco del run in attesa
di conferma omonimia, Google AI Overviews, grafo fonti, simulazioni. Gemini e Anthropic come engine
restano fuori dall'MVP salvo decisione contraria (vedi §7 punto 5). Tutto in `roadmap.md`.
