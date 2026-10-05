# AI Reputation Radar (working title)

Modulo Ainstein che misura **come le AI parlano di un soggetto** (persona o azienda) e dice
**cosa fare**.

1. **Onboarding**: dai solo il nome. L'agente costruisce la bozza di profilo, tu confermi riga per riga.
2. **Prompt**: 40-60 domande reali, in 4 cluster (chi è / reputazione / commerciale / confronto), IT+EN.
3. **Collector**: le domande vengono poste davvero a ChatGPT, Perplexity (poi Gemini, Claude) via API, con fonti.
4. **Analyzer**: un giudice AI legge ogni risposta: citato? dove? sentiment? fonti? claim falsi? competitor?
5. **Metriche**: Share of Voice, Citation Share per dominio, Sentiment, Risk Score, Source Map.
6. **Piano d'azione**: fonte negativa → da far rimuovere; gap → articolo + testata su cui pubblicare.
7. **Nel tempo** (v1): run settimanale, diff, alert, report white-label.

Primo cliente: Tutela Digitale (reputazione online), primo soggetto: Federico Marcaccini.

Per lavorarci: `CLAUDE.md` → `docs/TASKS.md`.
