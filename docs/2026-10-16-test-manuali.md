# Test manuali di Clemente — 2026-10-16

> Test fatti da Clemente a mano in **produzione** (https://ainstein.it), con utenti e scenari diversi.
> Guida e raccolta: sessione "Test manuali". Il codice lo corregge la sessione "P2 della prova da utente"
> (o una sessione apposta): questo file raccoglie solo cosa si è trovato.
>
> Priorità: **P1** = blocca il lancio · **P2** = da sistemare presto · **P3** = rifinitura.

## Scenari

| # | Scenario | Stato |
|---|----------|-------|
| 1 | Utente nuovo da zero (iscrizione, mail, 100 crediti, tour, primo progetto, primo tool) | da fare |
| 2 | Crediti che finiscono | da fare |
| 3 | Progetto condiviso (Owner + Editor + Viewer) | da fare |
| 4 | Agenzia con più clienti (niente dati mescolati) | da fare |
| 5 | Telefono e tema scuro | da fare |
| 6 | Admin: aggiungere crediti a un utente | da fare |

## Ricontrollo dei 5 P1 corretti il 2026-10-15

| P1 | Dove si vede | Esito |
|----|--------------|-------|
| AI Audit legge le pagine vere (non i redirect) | scenario 1 o 4 | da fare |
| AI Writer usa solo il WordPress del progetto | scenario 4 | da fare |
| "?" del tour funziona dentro un progetto | scenario 1 | da fare |
| GEO "Genera il profilo" non si tronca | scenario 1 o 4 | da fare |
| AI Audit prima della scansione dice "Nessuna scansione ancora" | scenario 1 | da fare |

## Problemi trovati

<!-- Formato: ### [P1/P2/P3] titolo corto
- Scenario / passo:
- Atteso:
- Successo:
- Note (screenshot, utente, URL):
-->

### [P2] Il tour di benvenuto non parte mai
- Scenario / passo: 1A, iscrizione con Google (utente 4, bewebagencysrls@gmail.com).
- Atteso: al primo accesso parte da solo il tour "Benvenuto su Ainstein".
- Successo: non compare. In prod `user_onboarding_global` ha 0 righe (nessun utente l'ha mai visto), mentre i tour
  dei moduli si salvano (`user_onboarding` 5 righe).
- Causa: `shared/views/layout.php:541` lo mostra solo se esiste `$onboardingWelcomeCompleted`, che nessun
  controller passa mai. Gli step ci sono (`config/onboarding.php`, chiave `welcome`).
- Note: Clemente: "non fa niente per adesso". Resto dell'iscrizione OK: 100 crediti con movimento
  "Crediti benvenuto (registrazione Google)", mail di benvenuto arrivata.

### [P3] Icone dei tool diverse tra menu laterale e dashboard
- Scenario / passo: 1A, prima occhiata alla dashboard dopo l'iscrizione.
- Atteso: stesso tool = stessa icona ovunque.
- Successo: le icone del menu laterale (rese più sobrie il 2026-10-15, `d3e98cf`) non sono quelle delle card in dashboard.
- Note: da allineare in un verso o nell'altro (decide Clemente quali tenere).

### [P2] AI Audit, Panoramica prima della prima scansione piena di riquadri vuoti
- Scenario / passo: 1B, progetto nuovo "Madcrumbs" (madcrumbs.it, utente 4), AI Audit aperto prima di scansionare.
- Atteso: una pagina che dice solo cosa fare (avvia la scansione) e come funziona.
- Successo: "Nessuna scansione ancora" c'è (P1 del 15 OK), ma intorno: anello "/100" con un pallino rosso, 4 riquadri
  a 0 (Pagine, Problemi critici, Indicizzabili, "0ms" Tempo medio), e "Come funziona" (4 passi) in fondo alla pagina.
- Proposta di Clemente: senza scansione nascondere riquadri e anello; "Come funziona" in alto.
- Note: nel "Come funziona" c'è ancora "Crawl Sito" (inglese).

### [P3] AI Audit, "Report AI" e "Piano d'azione" sembrano la stessa cosa
- Scenario / passo: 1B, Clemente guardando le schede chiede in cosa differiscono.
- Cosa sono: Report AI (15 cr) = documento da leggere o dare al cliente (sintesi, problemi critici/importanti/minori,
  aspetti positivi, tempi settimana 1 / 2-3 / 4+). Piano d'azione (10 cr) = lista di lavoro pagina per pagina in ordine
  di impatto, con la correzione da fare e la spunta, punteggio atteso.
- Successo: dai nomi non si capisce; in più il Report AI ha una sezione che si chiama "Piano d'azione consigliato".
- Note: basta una riga sotto ogni scheda che dica a cosa serve (e magari rinominare la sezione del report in "Tempi").

## Lista corta prima del post

_Si compila alla fine._
