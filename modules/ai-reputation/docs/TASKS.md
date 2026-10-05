# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-05

## Dove siamo
M0 test empirici: OpenAI ✅, Anthropic ✅, Perplexity ✅ (2026-10-05). Perplexity via Agent API `/v1/agent`
preset `fast` (le Chat Completions Sonar sono chiuse dal 2026-09-27): engine più economico (0,0014 $/prompt),
ma anche il più duro su Marcaccini → `docs/test-empirici/2026-10-05-perplexity.md`. Key Gemini presente in DB.
Branch `claude/ai-reputation-radar-dd9004` nel checkout principale `C:\xampp\htdocs\seo-toolkit`.

## Prossimo passo (uno solo)
**M0.4 — Test Gemini grounding.** Verifica online docs `generateContent` + tool `google_search`
(`groundingMetadata.groundingChunks`, URL redirect `vertexaisearch…` da risolvere) e listino → script
`scripts/test-engine-gemini.php` sul modello degli altri (3 prompt Marcaccini, JSON in `docs/test-empirici/`)
→ nota `2026-10-05-gemini.md`. Poi M0.6 (aggiornare `design.md` §3.3 e §5 con i dati reali di tutti e 4).

## M0 — Test empirici
- [x] M0.1 Prerequisiti: MySQL on, `.env` nel worktree, key verificate (OpenAI ✅ Anthropic ✅ Gemini ✅ Perplexity ✅ — le ultime due incollate il 2026-10-05)
- [x] M0.2 OpenAI Responses `web_search`: funziona, citazioni ok, costi reali misurati → `docs/test-empirici/2026-10-05-openai.md`. ⚠️ trovato contenuto negativo (rapporto mafie Lazio, possibile omonimo)
- [x] M0.3 Perplexity Agent API (`/v1/agent`): preset `fast` (= `openai/gpt-6-luna` + indice Perplexity) dà citazioni inline `[n]` su `search_results`; `perplexity/sonar` esplicito NON cita → collector usa `fast`. ≈0,0014 $/prompt → `docs/test-empirici/2026-10-05-perplexity.md`. ⚠️ attribuisce a Marcaccini la confisca 2013 (Il Tempo, dirittiglobali)
- [ ] M0.4 Idem Gemini grounding (se chiave disponibile)
- [x] M0.5 Anthropic web_search_20260318: funziona solo in modalità `direct` per le citazioni; costo ~0,20 $/prompt con Opus → `docs/test-empirici/2026-10-05-anthropic.md`. Claude NON trova il rapporto mafie ma smaschera gli articoli sponsorizzati
- [ ] M0.6 Aggiornare `design.md` §3.3 e §5 con formato citazioni, modelli e costi reali

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
