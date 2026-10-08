# Roadmap — AI Reputation Radar

> Aggiornata 2026-10-05. Lo stato puntuale è in `TASKS.md`.

## M0 — Test empirici (ora)
- Verifica online docs e listini di ogni engine.
- Script CLI per engine (OpenAI Responses + web_search, Perplexity Sonar; poi Gemini grounding,
  Anthropic web search): 3 prompt su Marcaccini, JSON grezzo salvato in `docs/test-empirici/`.
- Output: formato citazioni confermato, modelli scelti, costo reale per call, `design.md` aggiornato.

## M1 — MVP per la call Tutela Digitale (settimana del 2026-10-06)
- Tabelle `ar_*`, `module.json`, attivazione da Global Projects.
- Onboarding agent + conferma righe.
- Prompt engine (40-60 prompt, 4 cluster, IT+EN, persone).
- Collector OpenAI + Perplexity, 1 repeat, job SSE.
- Analyzer judge + metriche base (SoV, Citation Share, Sentiment, Source Map).
- Piano d'azione (removal / gap_article / correction).
- Pagina report run. Run reale su Marcaccini.
- **Definition of done**: report Marcaccini mostrabile in call senza spiegare il metodo.
- Export PDF del piano + scheda operativa per intervento (Opus 5.5) — fatto 2026-10-08.

## M2 — v1 vendibile (dopo la call, se Gabriele conferma)
- Gemini + Anthropic come engine; repeats 3 + score di stabilità.
- Run schedulato settimanale (cron dispatcher) + diff vs run precedente.
- Alert su nuova negativa / nuova fonte (NotificationService, email).
- Dashboard con trend; report PDF white-label mensile.
- Crediti e costi per run; cap per progetto.
- Docs utente (`shared/views/docs/ai-reputation.php`) + data model.

## M3 — Fase 2
- Google AI Overviews via SERP API.
- Reputation Risk Score raffinato; alert WhatsApp.

## M4 — Fase 3 (futuristico)
- Grafo di influenza fonti → risposte.
- Simulazione "se pubblico su X cosa cambia".
- Rilancio automatico dei prompt dopo ogni pubblicazione (misura dell'effetto digital PR).
