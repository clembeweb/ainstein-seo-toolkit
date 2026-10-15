# AI Rank — "Andamento" (ex "Punto della situazione": GA4 + Search Console + AI)

> Spec v1, 2026-10-13. Decisioni di Clemente: scheda dentro AI Rank (non modulo a sé), nome "Punto della situazione"
> (non "Recap"), prendere spunto da come è fatto il Recap di Amevista (`C:\xampp\htdocs\amevista-analysis`) per fare
> prima, ma niente copia: un'analisi a sé su sorgenti di traffico, andamento e criticità.

## Cosa fa
L'utente collega GA4 (Search Console c'è già in AI Rank), sceglie un periodo e clicca "Fai il punto". Il sistema legge
i dati dal vivo, li confronta col periodo precedente e con lo stesso periodo dell'anno prima, e l'AI scrive 5 paragrafi
in italiano semplice: 1) punto della situazione, 2) da dove arriva il traffico, 3) Google: cosa cresce e cosa cala,
4) criticità, 5) cosa fare adesso (3 azioni). Ogni paragrafo è collegato ai riquadri numerici che cita.

## Dati (letti dal vivo, nessuna sync storica)
- **Periodi** (come Amevista `resolveRecapPeriods`): ultimi 28 giorni (default), ultimi 90, mese scorso, trimestre
  scorso. B finisce 2 giorni fa (ritardo GSC). Confronti: A = periodo precedente di pari durata, Y = stesso periodo -1 anno.
- **GA4** (`analyticsdata v1beta runReport`):
  - per canale (`sessionDefaultChannelGroup`): `sessions`, `keyEvents`, `totalRevenue` → B, A, Y;
  - totale sito: stesse metriche;
  - pagine di atterraggio organiche (`landingPage`, filtro canale "Organic Search", via `gclid`/`fbclid`): top 15 per
    sessioni con delta B vs A.
- **GSC** (`searchAnalytics/query`, token della connessione AI Rank): totali clic/impressioni/CTR/posizione B, A, Y;
  quota brand (regex dal nome del progetto, modificabile); query e pagine che guadagnano/perdono più clic B vs A (top 8).
- Se GA4 o GSC mancano, il punto si fa con quello che c'è e l'AI lo dice (caveat).
- **Coppie ricerca↔pagina** (dal 2026-10-13): una chiamata GSC in più per periodo (`query`+`page`, 1000 righe) dà a ogni
  ricerca che sale/scende la pagina che Google mostra (`page`) e a ogni pagina la ricerca principale (`query`). Se fallisce,
  il report resta valido: mancano solo i bottoni per riga.

## Azioni per riga (dal 2026-10-13)
Il Punto non si limita a raccontare: su ogni ricerca e pagina che sale o scende ci sono due bottoni.
- **Analizza** (costo dell'Analisi pagina, `page_analysis`): stessa `POST analyze-page` della scheda "Analisi pagina",
  conferma con il costo, poi apre il risultato in `page-analyzer?analysis=ID` (la scheda legge il parametro e apre la finestra).
- **Monitora** (gratis, solo ricerche): `POST situation/track` aggiunge la ricerca a `st_keywords` con `is_tracked=1`,
  `source='situation'`, paese = quello più usato dal progetto (IT se nessuno); se c'era già, la riattiva. Torna alla
  stessa pagina con un flash. Se è già monitorata la riga mostra "Monitorata" con il link allo storico.
- **"Parti da qui"** sotto "Cosa fare adesso": le ricerche e pagine delle tabelle citate nel testo dell'AI (max 4, le
  pagine si riconoscono dal percorso) con gli stessi bottoni.
- **Numeri piccoli** (`SituationService::SMALL_BASE` = 50): sotto soglia sia ora sia prima la variazione è grigia con
  spiegazione al passaggio del mouse, e il prompt vieta percentuali e parole forti ("8 visite invece di 24").
- Canale GA4 "AI Assistant" → "Assistenti AI (ChatGPT, Gemini…)".

## Visite dalle AI (dal 2026-10-15)
Riquadro "Visite dalle AI" sotto "Da dove arriva il traffico": il canale GA4 **"AI Assistant"** (chatgpt.com, perplexity.ai,
gemini.google.com, copilot, claude.ai...) è traffico vero, non simulato — nessun tool lo mostra, il GEO Audit simula.
- Dati (`SituationService::aiTrafficBlock`, chiave `ga4.ai_traffic`): totale B/A/Y + quota sul totale (dal canale già
  letto); una chiamata GA4 in più solo se c'è almeno una visita: `sessionSource` + `landingPage` filtrate sul canale,
  tre periodi → per sorgente (nomi leggibili con `aiSourceName`: ChatGPT, Perplexity, Gemini, Copilot, Claude...) e top 5
  pagine di atterraggio del periodo. Se la chiamata fallisce resta il totale. 0 crediti in più.
- Ref AI `ai_traffic`; il prompt chiede di citare numero e quota nel paragrafo 2.
- Link al GEO Audit dello stesso progetto globale ("perché ti citano?") o ad attivarlo; i report salvati prima del
  2026-10-15 non hanno il blocco e non mostrano il riquadro.
- Nome del brand per la quota: dal nome del progetto senza note tra parentesi/quadre, senza coda dopo " - " o " | " e
  senza le parole test/prova/demo/copia (`brandRegex`, test in `scripts/test-situation.php`).

## AI
`AiService('seo-tracking')` (GR #1), modello del modulo, una chiamata, output JSON
`{"paragrafi":[{"titolo","testo","refs":[...]}]}` con refs in whitelist (pattern Amevista `lib/ai.php`). Numeri solo dal
JSON, niente ricalcoli, niente bullet, tono diretto, citare i caveat. `Database::reconnect()` dopo.

## Crediti
Setting `cost_situation_report` in module.json (gruppo costs, default 5). Addebito al proprietario dopo il successo.
Conferma con il costo prima di partire.

## Dati salvati
Tabella `st_situation_reports`: id, project_id, user_id, period_key, b_start, b_end, data (JSON), narrative (JSON),
credits_used, model, created_at. Storico nella scheda; si riapre senza costi.

## Pezzi
1. `Ga4Service` (nuovo, snello): token via `GoogleOAuthService::refreshToken`, `listProperties` (Admin API
   accountSummaries), `runReport` con retry e `ApiLoggerService` (provider `google_ga4`).
2. Connessione GA4 in AI Rank: route `ga4/connect` → `GoogleOAuthService::getAuthUrl('seo-tracking', id, SCOPE_GA4, 'ga4')`
   → `/seo-tracking/ga4/connected` → scelta property → `st_ga4_connections`.
3. `SituationService`: periodi, chiamate, delta (`deltaPack` come Amevista), JSON dati.
4. Controller + vista scheda "Punto della situazione" (Alpine, AJAX lungo GR #15/17/23/24).
5. Docs utente, tour, data-model.

## Rischio noto
Gli scope `analytics.readonly` e `webmasters.readonly` sono "sensibili": senza verifica dell'app Google di Ainstein gli
utenti vedono "app non verificata" e c'è un tetto di 100 utenti. Va chiesta la verifica in Google Cloud Console.

## v2 — "Andamento" (2026-10-16, richiesta di Clemente: "troppo testo, voglio grafici")
La pagina mostra prima i numeri, i grafici e le tabelle; l'AI aggiunge solo la lettura. Nome della scheda: **Andamento**.
- **Dati in più** (0 crediti): `ga4.daily` (per giorno: visite, conversioni; periodo B e A allineati per indice) e
  `gsc.daily` (clic, impressioni, posizione per giorno, B e A). Una chiamata GA4 e due GSC in più.
- **Lettura AI** (`narrative`, formato nuovo riconosciuto da `isNewNarrative`): `sintesi` (una frase),
  `letture` {numeri, andamento, canali, ai, google, pagine} (1-2 frasi ciascuna, chiave omessa se il blocco è vuoto),
  `azioni` (3: titolo, testo, ref). I report vecchi (5 paragrafi) restano leggibili: stessa pagina, senza grafico
  giorno per giorno, col commento vecchio in fondo e l'invito ad aggiornare.
- **Pagina** (`views/situation/index.php`, Chart.js del layout): testata compatta con periodo, bottone "Leggi i dati" /
  "Aggiorna" e chip dei collegamenti; striscia blu con la sintesi; 6 numeri grandi con variazione e mini-linea
  (visite, conversioni, clic, impressioni, posizione, quota brand); "Giorno per giorno" (linee piene = periodo,
  tratteggiate = periodo prima); "Da dove arriva il traffico" (barre orizzontali per quota, AI in indaco + tabellina);
  "Visite dalle AI" (numero, barre per sorgente ora/prima, pagine, link al GEO Audit); tabelle ricerche/pagine che
  salgono o scendono (Analizza/Monitora come prima); "Cosa fare adesso" in 3 schede con "Parti da qui" per riga e
  evidenziazione del riquadro al passaggio. Letture AI sotto ogni blocco.
- **Demo senza Google**: `scripts/demo-situation.php <st_project_id> [--no-ai]` salva un report con dati di prova
  (serie giornaliere, AI, tabelle) e lettura vera dell'AI (o fissa con --no-ai): serve a vedere la pagina in locale.
- Test offline aggiornati in `scripts/test-situation.php` (formato nuovo della lettura).
