# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-05

## Dove siamo
**Fette 1, 2 e 3 chiuse** (2026-10-05): il flusso "metti un nome e parte" è completo. Onboarding agent
(2 ricerche Perplexity + 5 pagine lette + 1 chiamata AI → 27 righe profilo in 22 s, rischi inclusi), conferma riga
per riga, prompt engine (39 domande in 4 cluster dal profilo confermato), collector, judge, report.
Su Marcaccini: 6 righe confermate, 48 domande attive. **Manca solo M1.8**: run completo sulle 48 domande
(≈144 risposte, ≈2,5 $ API + ≈150 crediti judge, ≈25 min) e revisione del report per la call.
Prima: modulo, collector, judge, metriche, piano d'azione e report completo.
Progetto "Federico Marcaccini" (`ar_projects.id = 1`), run 1: 27/27 risposte (0,44 $, 5 min) + 27 giudizi
(judge su `claude-sonnet-4` via AiService, ~4 s l'uno) → rischio Alto 65/100, 6 negative, 4 fonti negative,
5 domande con divergenza tra engine, 8 azioni, 2 omonimi da confermare. Report: `/ai-reputation/project/1/runs/1`.
"Avvia run" dalla dashboard fa collector + judge + report in un solo stream SSE. Branch
`claude/ai-reputation-radar-dd9004` nel checkout principale `C:\xampp\htdocs\seo-toolkit`.

## Prossimo passo (uno solo)
**M1.8 — Run completo su Marcaccini e revisione per la call.** Clemente conferma/corregge le righe del profilo in
`/ai-reputation/project/1/profile` (bastano 5 minuti: ✅ vero, ❌ falso, "È un altro" sullo sciatore), disattiva le
domande deboli tra le 48, poi "Avvia run" dalla dashboard (collector + judge + report in un colpo). Rivedere il
report con occhio da demo: verdetti, fonti, azioni, "Da confermare". Annotare qui cosa non torna. Poi M2 (vedi roadmap).

## M0 — Test empirici
- [x] M0.1 Prerequisiti: MySQL on, `.env` nel worktree, key verificate (OpenAI ✅ Anthropic ✅ Gemini ✅ Perplexity ✅ — le ultime due incollate il 2026-10-05)
- [x] M0.2 OpenAI Responses `web_search`: funziona, citazioni ok, costi reali misurati → `docs/test-empirici/2026-10-05-openai.md`. ⚠️ trovato contenuto negativo (rapporto mafie Lazio, possibile omonimo)
- [x] M0.3 Perplexity Agent API (`/v1/agent`): preset `fast` (= `openai/gpt-6-luna` + indice Perplexity) dà citazioni inline `[n]` su `search_results`; `perplexity/sonar` esplicito NON cita → collector usa `fast`. ≈0,0014 $/prompt → `docs/test-empirici/2026-10-05-perplexity.md`. ⚠️ attribuisce a Marcaccini la confisca 2013 (Il Tempo, dirittiglobali)
- [x] M0.4 Gemini `gemini-3.8-flash` grounding: funziona in Interactions API e `generateContent` → collector usa `generateContent` (ha `domain`, query, letto/citato). Redirect risolti con HEAD. ≈0,005-0,02 $/prompt, 5.000 ricerche/mese gratis → `docs/test-empirici/2026-10-05-gemini.md`. ⚠️ Gemini dice "nessun problema noto": l'opposto di Perplexity
- [x] M0.5 Anthropic web_search_20260318: funziona solo in modalità `direct` per le citazioni; costo ~0,20 $/prompt con Opus → `docs/test-empirici/2026-10-05-anthropic.md`. Claude NON trova il rapporto mafie ma smaschera gli articoli sponsorizzati
- [x] M0.6 `design.md` v0.2: formati citazioni e modalità per engine (§3.3), costi reali (§5), `search_count`/`sources_read`/`citations_noise` nel data model, judge con verifica fonte e omonimia `uncertain`, metrica "divergenza tra engine"

## M1 — MVP call (ordine per fette verticali, ADR-009: 1 = M1.1+M1.4+M1.7 · 2 = M1.5+M1.6 · 3 = M1.2+M1.3)
- [x] M1.1 Migrazione `ar_*` + `module.json` + registrazione modulo + attivazione da Global Projects (2026-10-05, testato in locale)
- [x] M1.2 Onboarding agent (`OnboardingService`: 2 ricerche Perplexity + scraping + AiService → `ar_profile_facts`) + pagina Profilo con conferma ✅/✏️/❌ e omonimi "È lui/È un altro" + campo omonimi in Impostazioni (2026-10-05)
- [x] M1.5b Omonimia non dichiarata → riga `homonym` proposed (solo da risposte nel merito) + sezione "Da confermare" nel report con Sì/No che aggiorna le note di disambiguazione (2026-10-05)
- [x] M1.3 Prompt engine (`PromptEngineService`: 40 domande per cluster/persona/lingua dal profilo confermato, dedup) — pulsante "Genera domande con AI" (2026-10-05)
- [x] M1.4 Collector OpenAI + Gemini + Perplexity come job SSE (`EngineCollectorService`, adapter Anthropic pronto), prompt manuali (2026-10-05, run reale 27/27)
- [x] M1.5 Judge (`JudgeService`, AiService, JSON rigido con outcome/verdict/noise) + metriche pesate (share, sentiment, rischio, divergenza) in `ReportBuilderService` (2026-10-05)
- [x] M1.6 Piano d'azione a regole: removal per URL negativo, counter_content per domanda rep negativa/ambigua, gap_article per comm/comp senza menzione; testata suggerita = dominio ok più citato (2026-10-05)
- [x] M1.7 Pagina report run completa: KPI, divergenza, griglia con verdetti e riassunto per cella, fonti ok/negative/rumore, piano d'azione, competitor, "Da confermare", Rianalizza (2026-10-05)
- [ ] M1.8 Run completo su Marcaccini (48 domande × 3 engine) + revisione report per la call

## Decisioni in sospeso (di Clemente)
- Conferma riga per riga della bozza profilo Marcaccini (brief §3) → si fa nella UI in M1.2
- Data e ora della call con Gabriele (settimana del 2026-10-06, mattina)
- ~~Omonimia del "Marcaccini Federico" della confisca 2013~~ → **risolta 2026-10-05: Clemente conferma, nessun
  omonimo, dovrebbe essere lui.** La demo è il caso "fonte negativa reale" (ADR-007 counter_content + removal TD)
- Key Perplexity e Gemini da creare (Clemente)
- ~~Instagram @fedemarcaccini~~ → risolto 2026-10-05: lo sciatore è un omonimo (Clemente). Scritto nelle note di disambiguazione del progetto; il soggetto è quello della confisca 2013
- Modello Anthropic da misurare: Opus 5.5 (fatto) e/o Sonnet 5.5 (metà costo, più diffuso su claude.ai)

## Fatto
- 2026-10-05 Brief ricevuto, salvato in `docs/brief-2026-10-05.md`
- 2026-10-05 Analisi e design v0.1, ADR-001..006, roadmap, struttura cartella modulo
