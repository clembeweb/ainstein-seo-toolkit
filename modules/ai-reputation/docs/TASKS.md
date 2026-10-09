# TASKS — AI Reputation Radar

> Stato del lavoro. Aggiornare a ogni sessione: fatto, in corso, prossimo passo.
> Ultimo aggiornamento: 2026-10-09 (sera: ADR-014 raggruppamento articoli gap)

## Dove siamo
**MVP completo e funzionante in locale.** Flusso: progetto da Global Projects → Profilo (onboarding + conferma
righe) → domande (base / AI / manuali) → "Avvia run" (raccolta + judge + report in un solo stream SSE) → report.
Engine: ChatGPT (API) + Gemini + Perplexity peso 0,3 (Claude pronto, spento). ChatGPT ripete 3 volte le domande rep.
**Run di riferimento per la demo: run 4** (`/ai-reputation/project/1/runs/4`): 138 risposte, rischio Alto 47%,
5 smentite di Gemini, 15 siti negativi (3 istituzionali), report corto con interventi in cima e schede.
**Export PDF del piano e "Genera scheda" (Opus 5.5) per intervento: fatti il 2026-10-08** (spec in `docs/superpowers/specs/2026-10-08-ai-reputation-schede-operative-pdf-design.md`, ADR-012/013). **Online su ainstein.it dal 2026-10-09.**
**Raggruppamento AI degli articoli gap (ADR-014): fatto il 2026-10-09, online su ainstein.it la sera stessa.** A fine run una chiamata (`brief_model`, ~0,03 $) raggruppa le domande scoperte in pochi articoli veri e mette in sospeso quelle senza fatti confermati (blocco "Da valutare: serve una prova dal cliente", tipo `gap_pending`). Run 4 ricalcolato con `scripts/rebuild-plan.php 4`: 7 righe "Non citato" → 2 articoli + 2 domande in sospeso (hotel di lusso); scheda "è affidabile?" conservata. Spec `docs/superpowers/specs/2026-10-09-ai-reputation-raggruppamento-articoli-gap-design.md`, piano `docs/superpowers/plans/2026-10-09-ai-reputation-raggruppamento-articoli-gap.md`. Migrazione `database/2026-10-09-actions-gap-grouping.sql` applicata in locale e in produzione.
Branch `claude/ai-reputation-radar-dd9004` unito in `main` = produzione (deploy 2026-10-09 sera, commit 87a9c8c, che include anche le pagine legali dall'altra postazione). Il branch resta sul remoto ma si lavora su `main`. Chiave SSH su questa postazione: `~/.ssh/ainstein_contabo`.
Demo per Gabriele: **in locale** (decisione di Clemente). Prima della call riavviare MySQL pulito da XAMPP e non
lanciare run in diretta.

## Prossimo passo (uno solo)
Revisione del prompt della scheda sul canale (oggi spinge quasi sempre verso le testate esterne; il blog del soggetto riceve solo `own_site_note`): per Marcaccini servono anche schede "blog" per il team interno, da regalare come assaggio (il resto a pagamento). Fuori scope dell'ADR-014, da fare ora che il piano è pulito. Brainstorming prima del codice.
Passo precedente (ancora valido): prova online del Radar con un progetto di test (Overview → "Come si usa"), incluse "Genera scheda" ed "Esporta PDF". Su ainstein.it mancano ancora le key Gemini e Perplexity in `/admin/settings` (le incolla Clemente). I dati del run 4 restano solo in locale.
Già provato il 2026-10-09 in locale (progetto fittizio "TEST E2E schede", Studio Bianchi Consulenze): 3 schede Opus 5.5 corrette, "Genera scheda" dal browser ok (1 credito), PDF ok; in produzione: deploy + migrazione applicati, pagine dei moduli 200, test CLI verdi.
Produzione dal 2026-10-07 su Contabo VPS `184.174.32.213` (Hetzner perso: account disabilitato, DB di produzione perso; DB ricreato dallo schema locale; HTTPS Let's Encrypt fino al 2027-01-05 con rinnovo automatico; dettagli in `docs/DEPLOY.md`). Mail a Hetzner inviata il 2026-10-07: se restituiscono il DB, si reimporta.
Già fatto: guida "Come si usa" in 4 passi nell'Overview (commit 1e9804d).

## Dopo (in ordine)
0. Fase C: PDF per il cliente finale (sintesi, meno dettaglio operativo), stesso motore `ActionPlanPdfService`.
1. Decidere se accendere Claude come quarto engine per la demo (~4 $ e ~50 min a run).
2. Domanda aperta a Clemente: Andrea Marcaccini che rapporto ha con Federico? (se coinvolto nelle stesse
   vicende, il judge deve trattare le sue notizie come rischio per Federico).
3. Revisione report run 4 con occhio da demo per Gabriele.
4. Golden Rule 18: guida utente `shared/views/docs/ai-reputation.php` + `docs/data-model.html` + landing
   "Scopri cosa puoi fare" (la guida deve includere la sezione "Schede operative e PDF").
5. Minori: judge_model registrato anche se AiService usa il fallback; titoli delle rimozioni ricavati dall'URL poco
   leggibili (es. "liberoquotidiano.it — Ndrangheta dia roma e reggio calabria confiscano beni per 120 mln a imprenditore 2");
   sezioni C (costi/tenuta) e D (prodotto/demo) dell'analisi critica da presentare.
6. Pulizia: cancellare il worktree `.claude/worktrees/ai-reputation-radar-dd9004/`; nella root del repo
   `public/landing3.php` e `token-form-filled.png` non tracciati, estranei al modulo (da decidere con Clemente).

## Analisi critica 2026-10-06 (fatta, vedi ADR-010, ADR-011)
- Metodo: 14 negative su 18 venivano da domande "mirate" → ADR-011, rischio ora = % negative a domande neutre (run 2: Alto 38%).
  ChatGPT (API) stabile solo 4 volte su 9 tra run 1 e run 2 → da fare: 3 ripetizioni sulle domande rep (non ancora fatto).
  Judge mai validato a campione → da fare: Clemente rivede 20 verdetti.
- Codice (revisione indipendente): corretti viewer che avviava lo stream, lease non applicato + item non prenotati
  (stato `processing`), `persist()` non idempotente (aggregati ricalcolati da tutti i run, stato azioni conservato),
  crediti ignorati, run che restava "in corso" su errore o finiva "completato" senza risposte, cancellazione domande che
  riscriveva lo storico (ora si disattiva), link non http(s), sito non pubblico (SSRF), redirect `back`, doppio clic su
  Avvia run. Testato con un progetto di prova: 2 stream insieme → 4 risposte, 4 chiamate API, nessun doppione.
- Fatto 2026-10-06 sera: controllo a campione 20 verdetti (negativo sì/no 20/20; rumore sbagliato 8/20 → corretto;
  contraddizioni coi fatti → nuovo campo) in `docs/2026-10-06-controllo-verdetti-judge.md`. Run 4 con 3 ripetizioni
  ChatGPT sulle rep (138 risposte, 0 errori, 2,70 $, 50 min): rischio Alto 47%, stabilità ChatGPT 3 celle su 6,
  Gemini smentisce i fatti confermati in 5 risposte ("non risultano procedimenti"), 17 fonti negative, sito ufficiale
  mai più nel rumore (6 → 0). Report: `/ai-reputation/project/1/runs/4`. Da valutare: 23 azioni di rimozione sono
  tante, raggrupparle per dominio.
- Aperto: judge può girare sul modello di fallback (judge_model registrato non è quello reale); sezioni C (costi/tenuta)
  e D (prodotto/demo) dell'analisi ancora da presentare a Clemente.

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
- [x] M1.8 Run 2 completato il 2026-10-06 (38 domande × 3 engine = 114 risposte, 0 errori, 1,97 $ API): rischio Alto 100/100, 18 negative (Perplexity 9, ChatGPT 5, Gemini 4), 10 fonti negative, 9 domande con divergenza, 29 azioni, 4 omonimi da confermare. Report `/ai-reputation/project/1/runs/2`. **Manca la revisione di Clemente.**
  Ritocchi fatti il 2026-10-06: contro-contenuti raggruppati in 1 azione (+1 di disambiguazione se serve), piano run 2 da 29 a 21 azioni; titoli fonti leggibili (Gemini: ricavati dall'URL; caratteri rotti riparati). MySQL XAMPP bloccato a metà run (ripreso senza perdite); bug doppio stream corretto (lease). Demo per Gabriele: **in locale** (decisione di Clemente).

## Decisioni in sospeso (di Clemente)
- Conferma riga per riga della bozza profilo Marcaccini (brief §3) → si fa nella UI in M1.2
- Data e ora della call con Gabriele (settimana del 2026-10-06, mattina)
- ~~Omonimia del "Marcaccini Federico" della confisca 2013~~ → **risolta 2026-10-05: Clemente conferma, nessun
  omonimo, dovrebbe essere lui.** La demo è il caso "fonte negativa reale" (ADR-007 counter_content + removal TD)
- Key Perplexity e Gemini da creare (Clemente)
- ~~Instagram @fedemarcaccini~~ → risolto 2026-10-05: lo sciatore è un omonimo (Clemente). Scritto nelle note di disambiguazione del progetto; il soggetto è quello della confisca 2013
- Modello Anthropic da misurare: Opus 5.5 (fatto) e/o Sonnet 5.5 (metà costo, più diffuso su claude.ai)

## Fatto
- 2026-10-08 Export PDF del piano, "Genera scheda" per intervento su Opus 5.5 (ADR-012/013), scheda stampata nel PDF
- 2026-10-05 Brief ricevuto, salvato in `docs/brief-2026-10-05.md`
- 2026-10-05 Analisi e design v0.1, ADR-001..006, roadmap, struttura cartella modulo
