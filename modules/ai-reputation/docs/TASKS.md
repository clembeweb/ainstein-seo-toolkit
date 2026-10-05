# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-05

## Dove siamo
**M0 chiuso** (2026-10-05): 4 engine testati e `design.md` v0.2 consolidato con i dati reali, note in
`docs/test-empirici/`. Dato chiave per la call: alla domanda "è affidabile?" Perplexity attribuisce a Marcaccini
la confisca antimafia 2013, Gemini dice "nessun problema noto", OpenAI è nel mezzo, Claude smaschera gli
sponsorizzati. Branch `claude/ai-reputation-radar-dd9004`
nel checkout principale `C:\xampp\htdocs\seo-toolkit`.

## Prossimo passo (uno solo)
**M1.1 — Migrazione `ar_*` + `module.json` + registrazione modulo + attivazione da Global Projects.**
Data model in `design.md` §4 (v0.2). Pattern: `modules/ai-content/` (module.json, routes, controller base),
`core/Models/GlobalProject.php` (MODULE_CONFIG). Migrazione in `database/2026-10-06-create-ar-tables.sql`.
Prima però una decisione di Clemente (sotto): engine dell'MVP.

## M0 — Test empirici
- [x] M0.1 Prerequisiti: MySQL on, `.env` nel worktree, key verificate (OpenAI ✅ Anthropic ✅ Gemini ✅ Perplexity ✅ — le ultime due incollate il 2026-10-05)
- [x] M0.2 OpenAI Responses `web_search`: funziona, citazioni ok, costi reali misurati → `docs/test-empirici/2026-10-05-openai.md`. ⚠️ trovato contenuto negativo (rapporto mafie Lazio, possibile omonimo)
- [x] M0.3 Perplexity Agent API (`/v1/agent`): preset `fast` (= `openai/gpt-6-luna` + indice Perplexity) dà citazioni inline `[n]` su `search_results`; `perplexity/sonar` esplicito NON cita → collector usa `fast`. ≈0,0014 $/prompt → `docs/test-empirici/2026-10-05-perplexity.md`. ⚠️ attribuisce a Marcaccini la confisca 2013 (Il Tempo, dirittiglobali)
- [x] M0.4 Gemini `gemini-3.8-flash` grounding: funziona in Interactions API e `generateContent` → collector usa `generateContent` (ha `domain`, query, letto/citato). Redirect risolti con HEAD. ≈0,005-0,02 $/prompt, 5.000 ricerche/mese gratis → `docs/test-empirici/2026-10-05-gemini.md`. ⚠️ Gemini dice "nessun problema noto": l'opposto di Perplexity
- [x] M0.5 Anthropic web_search_20260318: funziona solo in modalità `direct` per le citazioni; costo ~0,20 $/prompt con Opus → `docs/test-empirici/2026-10-05-anthropic.md`. Claude NON trova il rapporto mafie ma smaschera gli articoli sponsorizzati
- [x] M0.6 `design.md` v0.2: formati citazioni e modalità per engine (§3.3), costi reali (§5), `search_count`/`sources_read`/`citations_noise` nel data model, judge con verifica fonte e omonimia `uncertain`, metrica "divergenza tra engine"

## M1 — MVP call
- [ ] M1.1 Migrazione `ar_*` + `module.json` + registrazione modulo + attivazione da Global Projects
- [ ] M1.2 Onboarding agent + UI conferma righe (profilo Marcaccini da confermare: brief §3) + campo libero "Omonimi e soggetti da non confondere" (persone e aziende, ADR-008)
- [ ] M1.5b Analyzer: omonimia non dichiarata → riga `homonym` proposed + sezione "Da confermare" nel report (ADR-008; il blocco del run è v1)
- [ ] M1.3 Prompt engine
- [ ] M1.4 Collector OpenAI + Perplexity (job SSE)
- [ ] M1.5 Analyzer + metriche base
- [ ] M1.6 Piano d'azione
- [ ] M1.7 Pagina report run
- [ ] M1.8 Run reale su Marcaccini + revisione per la call

## Decisioni in sospeso (di Clemente)
- **Engine nell'MVP per la call**: solo OpenAI + Perplexity (ADR-004, demo ≈ 3-4 $ a run) oppure tutti e 4
  (adapter già pronti dagli script; con Anthropic Sonnet ≈ 8-10 $ a run). La divergenza tra engine è
  l'argomento forte della demo: con 4 si vede meglio, con 2 si vede comunque (Perplexity vs OpenAI)
- Conferma riga per riga della bozza profilo Marcaccini (brief §3) → si fa nella UI in M1.2
- Data e ora della call con Gabriele (settimana del 2026-10-06, mattina)
- ~~Omonimia del "Marcaccini Federico" della confisca 2013~~ → **risolta 2026-10-05: Clemente conferma, nessun
  omonimo, dovrebbe essere lui.** La demo è il caso "fonte negativa reale" (ADR-007 counter_content + removal TD)
- Key Perplexity e Gemini da creare (Clemente)
- Instagram @fedemarcaccini: Claude dice che è un maestro di sci argentino, il brief lo dava come suo → verificare
- Modello Anthropic da misurare: Opus 5.5 (fatto) e/o Sonnet 5.5 (metà costo, più diffuso su claude.ai)

## Fatto
- 2026-10-05 Brief ricevuto, salvato in `docs/brief-2026-10-05.md`
- 2026-10-05 Analisi e design v0.1, ADR-001..006, roadmap, struttura cartella modulo
