# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-05

## Dove siamo
Progetto appena nato. Brief ricevuto e analizzato, design v0.1 in bozza, decisioni ADR-001..006.
Nessuna riga di codice del modulo ancora scritta. Branch: `claude/ai-reputation-radar-dd9004`
(worktree in `.claude/worktrees/ai-reputation-radar-dd9004/`).

## Prossimo passo (uno solo)
**M0.5** — Test Anthropic web search (key presente). Poi Perplexity e Gemini quando Clemente
fornisce le key (deciso 2026-10-05: tutti gli engine, test empirici prima).
Aperto per Clemente: il "Marcaccini Federico" del rapporto mafie Lazio è un omonimo? (vedi test OpenAI)

## M0 — Test empirici
- [x] M0.1 Prerequisiti: MySQL on, `.env` nel worktree, key verificate (OpenAI ✅ Anthropic ✅ Gemini ❌ Perplexity ❌)
- [x] M0.2 OpenAI Responses `web_search`: funziona, citazioni ok, costi reali misurati → `docs/test-empirici/2026-10-05-openai.md`. ⚠️ trovato contenuto negativo (rapporto mafie Lazio, possibile omonimo)
- [ ] M0.3 Idem Perplexity Sonar
- [ ] M0.4 Idem Gemini grounding (se chiave disponibile)
- [ ] M0.5 Idem Anthropic web search (se chiave disponibile)
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

## Fatto
- 2026-10-05 Brief ricevuto, salvato in `docs/brief-2026-10-05.md`
- 2026-10-05 Analisi e design v0.1, ADR-001..006, roadmap, struttura cartella modulo
