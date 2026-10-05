# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-05

## Dove siamo
Progetto appena nato. Brief ricevuto e analizzato, design v0.1 in bozza, decisioni ADR-001..006.
Nessuna riga di codice del modulo ancora scritta. Branch: `claude/ai-reputation-radar-dd9004` nel worktree;
XAMPP serve il checkout principale sul branch `serve-ai-reputation` (vedi CLAUDE.md del modulo per il sync).

## Prossimo passo (uno solo)
**M0.3 / M0.4** — Test Perplexity e Gemini. Clemente incolla le key in `http://localhost/seo-toolkit/admin/settings`
(campi pronti), poi si lanciano gli script. Prima: verifica online docs Perplexity (Sonar chat
completions con citations vs Agent API vs Search API) e Gemini grounding.
Aperto per Clemente: il "Marcaccini Federico" del rapporto mafie Lazio è un omonimo? (vedi test OpenAI)

## M0 — Test empirici
- [x] M0.1 Prerequisiti: MySQL on, `.env` nel worktree, key verificate (OpenAI ✅ Anthropic ✅ Gemini ❌ Perplexity ❌)
- [x] M0.2 OpenAI Responses `web_search`: funziona, citazioni ok, costi reali misurati → `docs/test-empirici/2026-10-05-openai.md`. ⚠️ trovato contenuto negativo (rapporto mafie Lazio, possibile omonimo)
- [ ] M0.3 Idem Perplexity Sonar
- [ ] M0.4 Idem Gemini grounding (se chiave disponibile)
- [x] M0.5 Anthropic web_search_20260318: funziona solo in modalità `direct` per le citazioni; costo ~0,20 $/prompt con Opus → `docs/test-empirici/2026-10-05-anthropic.md`. Claude NON trova il rapporto mafie ma smaschera gli articoli sponsorizzati
- [ ] M0.6 Aggiornare `design.md` §3.3 e §5 con formato citazioni, modelli e costi reali

## M1 — MVP call
- [ ] M1.1 Migrazione `ar_*` + `module.json` + registrazione modulo + attivazione da Global Projects
- [ ] M1.2 Onboarding agent + UI conferma righe (profilo Marcaccini da confermare: brief §3)
- [ ] M1.3 Prompt engine
- [ ] M1.4 Collector OpenAI + Perplexity (job SSE)
- [ ] M1.5 Analyzer + metriche base
- [ ] M1.6 Piano d'azione
- [ ] M1.7 Pagina report run
- [ ] M1.8 Run reale su Marcaccini + revisione per la call

## Decisioni in sospeso (di Clemente)
- Conferma riga per riga della bozza profilo Marcaccini (brief §3) → si fa nella UI in M1.2
- Data e ora della call con Gabriele (settimana del 2026-10-06, mattina)
- Omonimia o no del "Marcaccini Federico" citato nel III Rapporto Mafie Lazio (jemolo.it) → cambia la demo
- Key Perplexity e Gemini da creare (Clemente)
- Instagram @fedemarcaccini: Claude dice che è un maestro di sci argentino, il brief lo dava come suo → verificare
- Modello Anthropic da misurare: Opus 5.5 (fatto) e/o Sonnet 5.5 (metà costo, più diffuso su claude.ai)

## Fatto
- 2026-10-05 Brief ricevuto, salvato in `docs/brief-2026-10-05.md`
- 2026-10-05 Analisi e design v0.1, ADR-001..006, roadmap, struttura cartella modulo
