# Test AI Keywords in produzione — 2026-10-16

> Prova "da cliente" del modulo AI Keywords (`keyword-research`, `kr_`) su https://ainstein.it, col Chrome di Clemente
> (account admin). Progetto usato: Stabia Boat Rental (test), `kr_projects.id = 9`.
> Saldo iniziale: 7.226,3 crediti.
> Non segnalati qui (già corretti in locale dalla sessione P2, `364a1cb`, non ancora online): costi "10 crediti" sulla
> landing, card "Analisi competitor" e "Keyword gap analysis".
>
> Priorità: **P1** = blocca il lancio · **P2** = da fare presto · **P3** = rifinitura.

## 1. Controllo veloce (gratis)

Ricerche fatte: "noleggio barche sorrento" (Italia), "scarpe running donna" (Italia), "boat rental amalfi coast" (UK).

**Funziona bene:** risposta in 2-3 secondi; volumi sensati (170, 8.100, 170); 39-49 ricerche correlate pertinenti;
filtri e "Invia a AI Rank" (finestra con scelta progetto e gruppo) ok; "Nessun credito richiesto" scritto chiaro.

| # | Passo | Cosa mi aspettavo | Cosa è successo | Priorità |
|---|---|---|---|---|
| 1.1 | Pagina | Tutto in italiano | Sottotitolo "Controlla volume, CPC e **competition**"; etichetta "**Location**"; concorrenza "HIGH / MEDIUM / LOW / N/A" in inglese (sia nel riquadro sia in tabella) | P3 |
| 1.2 | Numeri | Formato italiano ovunque | Riquadro in alto "8,100" (virgola inglese) e "0.04 - 0.23" (punto); in tabella "40.500" ma "8100" e "1.42 EUR" | P3 |

## 2. Ricerca keyword (progetto Stabia)

Brief: descrizione noleggio barche/gommoni a Castellammare, B2C, SEO, Italia; parole di partenza "noleggio barche
castellammare di stabia", "escursioni in barca costiera amalfitana", "noleggio gommone senza patente".
Raccolta (gratis): ~10 s, 138 raccolte, 137 dopo il filtro, 1 esclusa. Il costo dell'analisi AI (3 crediti) è mostrato
accanto al bottone prima di cliccare.

| # | Passo | Cosa mi aspettavo | Cosa è successo | Priorità |
|---|---|---|---|---|
| 2.1 | Pagina | Parole semplici | Sottotitolo "Keyword research con clustering AI automatico"; passi "Brief" e "AI Clustering"; "141 kw filtrate" nello storico | P3 — **corretto** (`ae064b5`) |
| 2.2 | Brief | Ritrovare il brief della ricerca precedente | Il modulo riparte vuoto (descrizione da riscrivere) e il target di default è B2B anche per un noleggio barche | P3 — **superato** dal nuovo ingresso |

## Decisione di Clemente a metà test: nuovo ingresso dei 3 strumenti

Clemente: "partire da una kw iniziale non va bene, e se l'utente non ne ha idea?". Rifatto l'ingresso (design in
`docs/plans/2026-10-16-ai-keywords-ingresso-design.md`), più i 2 P2 del giro manuale (keyword visibili prima dei 3
crediti, "E adesso?" verso AI Rank / AI Writer). Online dal 2026-10-16 sera (`fabccca`, `05fcf05`, migrazione 004).

Prova in prod su Stabia, nuovo ingresso (tutto gratis, saldo invariato 7.226,3):
- "Usa il profilo del GEO Audit": ~15 s, profilo giusto e completo (tour, barche, prezzi, imbarchi, clienti).
- Prima versione delle parole proposte troppo specifiche ("noleggio barca senza patente castellammare",
  "addio al celibato in barca"): 78 ricerche, mancavano le principali. **Corretto** (`05fcf05`: ricerche corte e
  principali, al massimo una di nicchia). Ora: "tour in barca capri", "noleggio barche sorrento", "noleggio barca senza
  patente", "tour costiera amalfitana in barca", "noleggio barche castellammare di stabia" → **200 ricerche**, in cima
  "barche senza patente" 1.000, "giro in barca capri" 720, "noleggio barche castellammare di stabia" 260.
- Elenco delle keyword con le spunte prima dell'AI: c'è, tutte spuntate, numeri all'italiana.
- "Draft" in inglese nello storico per una raccolta non finita: **corretto** ("Non finita", `05fcf05`).
- Google "partendo dal sito" provato e scartato (ricerche generiche e in inglese): vedi il design.

| # | Passo | Cosa mi aspettavo | Cosa è successo | Priorità |
|---|---|---|---|---|
| 2.3 | Parole proposte | Le ricerche principali del settore | Troppo specifiche, mancavano le più cercate | P1 — **corretto** (`05fcf05`) |
| 2.4 | Storico | Stato in italiano | "Draft" | P3 — **corretto** (`05fcf05`) |

### Raggruppamento AI su Stabia (3 crediti, ok di Clemente)

- Costo mostrato 3 = addebitato 3 (saldo 7.226,3 → 7.223,3). Tempo ~2 minuti con 200 keyword.
- 8 gruppi giusti per il business: senza patente generico (1.000) e per mezzo (40 cv, gommone), Castellammare (locale),
  Sorrento, giro in barca Capri (720), tour privato Capri da Sorrento/Napoli, Grotta Azzurra e Faraglioni, Costiera
  Amalfitana e Positano. 179 keyword nei gruppi, 9.040 ricerche al mese.
- Note per gruppo concrete e coerenti col profilo (es. "il noleggio senza patente parte solo da Castellammare, Sorrento
  è imbarco solo per i tour"; "il sito è solo in inglese: serve /it/").
- 22 escluse, tutte giuste (altre località, barche a vela, un concorrente, partenze da Amalfi o Ischia). Il dubbio del
  giro manuale ("escluse keyword in target") qui non si presenta: il profilo vero aiuta.
- "E adesso?": AI Rank attivo su Stabia → "Manda ad AI Rank"; AI Writer non attivo → "Attiva AI Writer".

| # | Passo | Cosa mi aspettavo | Cosa è successo | Priorità |
|---|---|---|---|---|
| 2.5 | Attesa del raggruppamento | Tempo indicato vero | "Di solito meno di un minuto", ma con 200 keyword ci vogliono ~2 minuti | P3 |
| 2.6 | Storico ricerche | Solo le ricerche utili | Ogni raccolta non portata all'AI resta come "Non finita" (oggi 3 righe su Stabia) | P3 |
| 2.7 | Note dei gruppi | Parole semplici | L'AI scrive ancora "cluster", "pillar page", "CTA" nelle note | P3 |
