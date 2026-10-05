# Test empirico — Perplexity Agent API `/v1/agent` + `web_search` (2026-10-05)

Script: `scripts/test-engine-perplexity.php fast medium` e `… perplexity/sonar medium`
JSON grezzi: `2026-10-05-perplexity-fast.json` · `2026-10-05-perplexity-perplexity_sonar.json`
Docs verificate: https://docs.perplexity.ai/docs/agent-api/migrate-from-sonar/overview.md ·
https://docs.perplexity.ai/api-reference/agent-post.md · https://docs.perplexity.ai/docs/agent-api/tools/web-search.md

## Premessa: le Chat Completions Sonar non esistono più
"Sonar Chat Completions support ended on September 27, 2026". Le vecchie chiamate vengono riscritte
come Agent API. Mappa ufficiale: `sonar` e `sonar-pro` → preset `fast`, `sonar-reasoning-pro` → `low`,
`sonar-deep-research` → `high`. Endpoint unico `POST https://api.perplexity.ai/v1/agent`, body
`input` + (`preset` | `model`) + `tools: [{type: web_search, …}]`.

## Esito tecnico: ✅ funziona in entrambe le varianti, ma le citazioni cambiano

| Variante | Modello effettivo | Prompt | Latenza | Token in/out | Query | Risultati letti | Citazioni inline `[n]` | Costo reale (`usage.cost`) |
|---|---|---|---|---|---|---|---|---|
| preset `fast` | `openai/gpt-6-luna` | nav | 2,9 s | 3.341 / 70 | 1 | 10 | 2 | 0,0012 $ |
| preset `fast` | `openai/gpt-6-luna` | rep | 4,2 s | 4.222 / 340 | 1 | 10 | 4 | 0,0015 $ |
| preset `fast` | `openai/gpt-6-luna` | comm | 4,5 s | 2.985 / 462 | 1 | 10 | 6 | 0,0014 $ |
| `perplexity/sonar` | `perplexity/sonar` | nav | 6,2 s | 3.952 / 219 | 3 | 15 | **0** | 0,0040 $ |
| `perplexity/sonar` | `perplexity/sonar` | rep | 10,1 s | 4.894 / 435 | 3 | 15 | **0** | 0,0048 $ |
| `perplexity/sonar` | `perplexity/sonar` | comm | 8,4 s | 4.296 / 581 | 3 | 15 | **0** | 0,0050 $ |

- **Il preset `fast` (ex Sonar) oggi gira su un modello OpenAI** (`openai/gpt-6-luna`) con la ricerca
  Perplexity. Quindi "misurare Perplexity" via API = misurare *l'indice Perplexity*, non un modello
  Perplexity. Da dichiarare nel metodo (coerente con ADR-005: la misura è "via API").
- **Fonti**: output item `search_results` con `id`, `url`, `title`, `snippet` (lungo, utile), `date`.
  Nessuna annotazione `url_citation` nel messaggio in nessuna delle due varianti: le citazioni sono
  **solo i marker inline `[n]`** risolti sugli `id` dei `search_results`. Con `fast` ci sono (il preset
  le include); con `perplexity/sonar` esplicito **non ci sono** → servirebbe chiederle in `instructions`
  (le docs lo prevedono). **→ Decisione: il collector usa `preset: fast`.**
- Il tool nell'usage si chiama `search_web` (non `web_search`): `usage.tool_calls_details.search_web`.
  Una sola invocazione per risposta anche con 3 query (sonar riformula da solo).
- `usage.cost.total_cost` è il costo reale per chiamata: niente stime, si logga direttamente.
- `user_location: {country: "IT"}` accettato. Zero errori HTTP su 6 chiamate.

## Costi reali (listino 2026-10-05: web_search 2,50 $/1k invocazioni, `perplexity/sonar` 0,25/2,50 $ per M token)
- preset `fast`: **≈ 0,0014 $ a prompt** → 50 prompt ≈ 0,07 $ a run. Di gran lunga l'engine più economico
  (OpenAI gpt-5-mini 0,03-0,16 $ · Anthropic Opus 0,20 $).
- `perplexity/sonar`: ≈ 0,0045 $ a prompt (1 invocazione a 2,5 $/1k, non 1 $ perché `search_type` default `web`).
- Il grosso del costo è la ricerca, i token sono irrisori. Il `fast` costa 1 $/1k perché usa `search_type: fast`.

## Esito di contenuto (Marcaccini) — ⚠️ il più pesante dei tre engine

1. **nav**: `fast` risponde solo dal suo sito (imprenditore romano, sviluppo immobiliare, "oltre vent'anni").
   `sonar` invece **apre subito con l'ambiguità**: "fonti riferite a persone diverse… esiste anche un
   Federico Marcaccini citato in cronache giudiziarie (inchiesta Overloading, confisca 2013)".
2. **rep — ⚠️ CONTENUTO NEGATIVO ATTRIBUITO DIRETTAMENTE**: `fast` risponde "Le fonti riportano **un grave
   precedente giudiziario e patrimoniale** riferito a Federico Marcaccini, imprenditore immobiliare romano"
   e cita Il Tempo 29/10/2013 "Sequestrati beni per 120 milioni a Marcaccini" (confisca definitiva in
   Cassazione, procedimento di prevenzione antimafia, fermo 2010 nell'inchiesta "Overloading" sul traffico
   di cocaina, ruolo di finanziatore). `sonar` è più cauto: "due profili potenzialmente diversi… verificare
   l'identità prima di attribuire quei precedenti".
   Fonti negative lette da entrambi: **iltempo.it (2013)** e **dirittiglobali.it (2012, "Il romanzo
   criminale della 'ndrangheta a Roma")**. Sono fonti diverse dal rapporto mafie Lazio trovato da OpenAI:
   la storia è la stessa, le fonti sono almeno tre.
3. **comm**: Marcaccini **non citato** in nessuna variante. `fast`: Cushman & Wakefield, PwC, Castello SGR,
   IVH/mr investar, Hospitality Law Lab, Rinascimento Valori. `sonar`: Colliers, Cushman, WANT Advisors,
   AA+G Hospitality, Corsini, PwC. Un suo articolo (genova24) compare tra i risultati letti ma non nel testo.
   Anche qui la risposta parte con "non esiste una classifica indipendente".

### Sull'omonimia (decisione aperta di Clemente, ora più urgente)
Gli snippet letti dagli engine descrivono il Marcaccini del 2010-2013 come **"43enne noto imprenditore
ed immobiliarista romano"** (Il Tempo, 2013) con 33 società nei settori **immobiliare, edilizio**, auto e
servizi aeroportuali; dirittiglobali lo chiama "palazzinaro", soprannome "er pupone". Il soggetto del
brief è un imprenditore romano del real estate con "oltre vent'anni di esperienza". **Le AI non hanno
bisogno di stabilire se sia la stessa persona: gliela attribuiscono lo stesso** (`fast`) o la mettono
accanto al suo nome (`sonar`, OpenAI). Per la demo questo è il caso d'uso, qualunque sia la verità:
o disambiguazione (se omonimo) o gestione di una fonte negativa consolidata (se non lo è). Da chiarire
con Gabriele prima della call, non in call.

**Implicazioni per il design**
- Perplexity è l'engine più **economico e veloce**: può girare a `repeats: 3` senza pesare.
- Il campo `snippet` dei `search_results` è ricco: l'analyzer può usarlo per classificare le fonti lette
  (non solo citate) senza scraping aggiuntivo.
- Tre engine su quattro testati (OpenAI, Perplexity fast, Perplexity sonar) portano la storia negativa;
  Anthropic no. La metrica "quante AI la raccontano" è già un numero da mostrare in demo.
