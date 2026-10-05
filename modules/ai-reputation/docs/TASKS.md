# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-05

## Dove siamo
M0 test empirici: OpenAI ✅ e Anthropic ✅ fatti il 2026-10-05. Perplexity: docs verificate online e script
pronto (`scripts/test-engine-perplexity.php`), **manca solo la key**. Scoperta: Perplexity ha chiuso le Chat
Completions Sonar il 2026-09-27 → si usa l'Agent API `/v1/agent` con `preset: fast` (sostituto ufficiale di
`sonar`). Branch `claude/ai-reputation-radar-dd9004` nel checkout principale `C:\xampp\htdocs\seo-toolkit`.

## Prossimo passo (uno solo)
**M0.3 — lanciare il test Perplexity.** Clemente incolla la key in `http://localhost/seo-toolkit/admin/settings`
(campo "Perplexity", riga DB già creata). Poi dalla root del repo:
`php modules/ai-reputation/scripts/test-engine-perplexity.php` (default `fast medium`; variante
`perplexity/sonar medium` per il modello esplicito). Scrivere la nota `docs/test-empirici/2026-10-05-perplexity.md`
sul modello di quella OpenAI. Poi M0.4 Gemini grounding (serve anche quella key).
Aperto per Clemente: il "Marcaccini Federico" del rapporto mafie Lazio è un omonimo? (vedi test OpenAI)

## M0 — Test empirici
- [x] M0.1 Prerequisiti: MySQL on, `.env` nel worktree, key verificate (OpenAI ✅ Anthropic ✅ Gemini ❌ Perplexity ❌)
- [x] M0.2 OpenAI Responses `web_search`: funziona, citazioni ok, costi reali misurati → `docs/test-empirici/2026-10-05-openai.md`. ⚠️ trovato contenuto negativo (rapporto mafie Lazio, possibile omonimo)
- [ ] M0.3 Perplexity Agent API (`/v1/agent`, preset `fast`): docs verificate + script pronto, in attesa key
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
