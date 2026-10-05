# Test empirico — Anthropic Messages API + `web_search_20260318` (2026-10-05)

Script: `scripts/test-engine-anthropic.php claude-opus-5-5 5 [direct|dynamic]`
JSON grezzi: `2026-10-05-anthropic-claude-opus-5-5-dynamic.json` (primo run, default del tool) e
`…-direct.json` (secondo run, `allowed_callers: ["direct"]`).
Docs verificate: https://platform.claude.com/docs/en/agents-and-tools/tool-use/web-search-tool

## Esito tecnico: ✅ funziona, ma SOLO in modalità `direct` abbiamo le citazioni

| Modalità | Prompt | Latenza | Token in/out | Ricerche | Risultati | Citazioni nel testo |
|---|---|---|---|---|---|---|
| dynamic | nav | 27 s | 32.462 / 1.233 | 3 | 28 | **0** |
| dynamic | rep | 40 s | 47.928 / 2.510 | 3 | 28 | **0** |
| dynamic | comm | 48 s | 53.417 / 3.394 | 5 | 45 | **0** |
| direct | nav | 15 s | 12.762 / 998 | 1 | 9 | 6 |
| direct | rep | 26 s | 50.926 / 1.683 | 3 | 27 | 5 |
| direct | comm | 35 s | 47.853 / 2.730 | 3 | 27 | 19 |

- **Filtraggio dinamico (default di `web_search_20260209+`)**: le ricerche passano da code execution, il
  testo finale **non ha blocchi `citations`**. Restano solo i `web_search_tool_result` (URL consultati,
  non URL citati). Per noi è inutile: ci serve "cosa ha citato", non "cosa ha letto".
- **Modalità `direct`**: citazioni `web_search_result_location` con `url`, `title`, `cited_text` in ogni
  blocco `text`. Meno token, più veloce. **→ Decisione: il collector usa `allowed_callers: ["direct"]`.**
- `usage.server_tool_use.web_search_requests` dà il conteggio ricerche esatto (billing).
- `max_uses: 5` ha retto; nel run dynamic il prompt comm ha toccato il limite ("uno strumento di
  ricerca ha smesso di funzionare") e il modello ha dichiarato quali parti venivano dalla memoria.
- Modello: `claude-opus-5-5` (effort default medium). Da decidere se misurare anche `claude-sonnet-5-5`
  (è il modello che la maggior parte degli utenti claude.ai usa; costa la metà).

## Costi reali (listino 2026-10-05: 10 $/1.000 ricerche + token; Opus 5.5 = 4 $/M in, 20 $/M out)
- direct: nav ≈ 0,08 $ · rep ≈ 0,27 $ · comm ≈ 0,28 $. Media ≈ 0,20 $ a prompt → 50 prompt ≈ 10 $ a run
  con Opus. Con Sonnet 5.5 ≈ 5 $. **Più caro di OpenAI gpt-5-mini** (≈ 0,03-0,16 $ a prompt).
- Il grosso è il contesto di ricerca (40-50k token in ingresso per i prompt rep/comm).

## Esito di contenuto (Marcaccini) — confronto con OpenAI

1. **nav**: imprenditore romano, real estate investor; cita sito, Cronache Picene, Instagram. Segnala
   che @fedemarcaccini su Instagram **sembra un'altra persona (maestro di sci in Argentina)** → il brief
   lo elencava come suo: da verificare, è un caso da ❌ nell'onboarding.
2. **rep**: Claude ha cercato anche "Marcaccini Federico Roma inchiesta OR condanna OR truffa" e
   **NON ha trovato il rapporto mafie Lazio** che OpenAI ha trovato. Però dice una cosa pesante per la
   digital PR: *"articoli su siti minori, stesso tono elogiativo, escono a pochi giorni l'uno
   dall'altro, raccolti nella sua Press review → probabilmente comunicati promozionali o personal
   branding, non giornalismo indipendente; non vanno presi come prova di affidabilità"*. Nel run
   dynamic aveva anche notato la sezione "extra-guest-post" di Taxidrivers.
   Conclusione di Claude: "non posso dire se sia affidabile" + checklist visura/CONSOB/referenze.
3. **comm**: Marcaccini **non citato** (il suo sito compare tra i risultati letti, ma non nel testo).
   Citati: Colliers, JLL, Cushman & Wakefield, Italy Access Advisory, Investimenti Alberghieri,
   Hospitality Forum, Il Sole 24 Ore, Jesse.

**Implicazioni per la call**
- Gli engine **divergono**: OpenAI pesca la fonte negativa, Claude no ma smaschera gli articoli
  sponsorizzati. Due rischi reputazionali diversi sullo stesso soggetto → è l'argomento per monitorare
  più engine, non uno.
- Le AI riconoscono il pattern "tante uscite elogiative ravvicinate su testate minori" e lo
  **scontano**. Per la digital PR di Clemente vuol dire: meno volume, più fonti indipendenti/autorevoli
  (Milano Finanza pesa più di dieci blog), e contenuti che rispondano alla domanda "è affidabile?"
  (ADR-007).
