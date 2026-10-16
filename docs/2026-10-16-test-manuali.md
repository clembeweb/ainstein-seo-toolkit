# Test manuali di Clemente — 2026-10-16

> Test fatti da Clemente a mano in **produzione** (https://ainstein.it), con utenti e scenari diversi.
> Guida e raccolta: sessione "Test manuali". Il codice lo corregge la sessione "P2 della prova da utente"
> (o una sessione apposta): questo file raccoglie solo cosa si è trovato.
>
> Priorità: **P1** = blocca il lancio · **P2** = da sistemare presto · **P3** = rifinitura.

## Scenari

| # | Scenario | Stato |
|---|----------|-------|
| 1 | Utente nuovo da zero (iscrizione, mail, 100 crediti, tour, primo progetto, primo tool) | fatto: iscrizione Google OK, scansione OK; tour benvenuto assente (P2) |
| 2 | Crediti che finiscono | da fare |
| 3 | Progetto condiviso (Owner + Editor + Viewer) | da fare |
| 4 | Agenzia con più clienti (niente dati mescolati) | da fare |
| 5 | Telefono e tema scuro | da fare |
| 6 | Admin: aggiungere crediti a un utente | OK: +50 due volte ("test pre post", "test"), saldo aggiornato lato admin e utente |

## Ricontrollo dei 5 P1 corretti il 2026-10-15

| P1 | Dove si vede | Esito |
|----|--------------|-------|
| AI Audit legge le pagine vere (non i redirect) | scenario 1 | OK: Madcrumbs 12 pagine, 10 a 200, 2 redirect veri del sito (/adobe-analytics/ → /adobe-analytics-agency/), score 69, 0 crediti |
| AI Writer usa solo il WordPress del progetto | scenario 4 | da fare |
| "?" del tour funziona dentro un progetto | scenario 1 | da ricontrollare (non riportato) |
| GEO "Genera il profilo" non si tronca | scenario 1 o 4 | da fare |
| AI Audit prima della scansione dice "Nessuna scansione ancora" | scenario 1 | OK (ma riquadri vuoti intorno: P2 sotto) |

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

### [P3] AI Audit, Panoramica: aggiungere gli highlights di Struttura link e Crawl Budget
- Scenario / passo: 1B, Madcrumbs dopo la scansione.
- Proposta di Clemente: dopo l'analisi la Panoramica mostra anche i punti salienti di Struttura link e Crawl Budget
  (solo i numeri chiave, es. pagine orfane, profondità, spreco di crawl budget), con il link alla scheda per i dettagli.
- Note: miglioria, non un errore. Quando farla lo decide Clemente.

### [P1 proposto] AI Audit, Piano d'azione: una chiamata AI per pagina, senza tetto e senza avanzamento
- Scenario / passo: 1B, Madcrumbs (12 pagine con problemi), "Genera" Piano d'azione.
- Atteso: piano pronto in poco tempo, o almeno una barra "pagina X di Y".
- Successo: **4 min 53 s** di "Generazione in corso…" senza nient'altro; Clemente ha pensato che fosse bloccato. Alla fine
  ha funzionato: 72 correzioni, punteggio 69 → 100 atteso, 10 crediti scalati (100 → 90), piano id 5.
- Causa: `ActionPlanService::generatePlan()` fa **una chiamata Opus per ogni pagina con problemi** (12 chiamate,
  ~25 s l'una; 19k token in, 33k out) in un'unica richiesta HTTP. Nessun tetto: la scansione di default arriva a
  100 pagine → ~100 chiamate, **~45 minuti** di attesa per lo stesso prezzo fisso di 10 crediti (costo API ~8 volte
  quello di Madcrumbs). Con siti veri dei clienti dal post, è facile che l'utente chiuda o che la richiesta muoia.
- **Decisione di Clemente (2026-10-16): far scegliere le pagine da analizzare** prima di generare ("se un sito ne ha
  a centinaia succede un casino"). Clemente: "mi sembra ottimo" sulla qualità; "procedi con i fix che servono" →
  passato alla sessione p2-fix.
- Altre strade complementari: tetto alle N pagine più importanti; più pagine per chiamata (BATCH_SIZE = 5 c'è
  ma non viene usato per raggruppare le chiamate); lavoro in background con avanzamento "pagina X di Y".
- Note: il Report AI (15 cr) non è ancora stato provato; mai generato in prod né l'uno né l'altro prima di oggi.

### [P2] Piano d'azione: scambia le versioni IT/EN per duplicati e consiglia canonical/301
- Scenario / passo: 1B, Madcrumbs, piano id 5 (prima pagina: /adobe-data-collection-launch/).
- Atteso: su un sito bilingue le pagine /x/ e /en/x/ sono traduzioni; il consiglio giusto è tradurre title e description
  della versione inglese (oggi su Madcrumbs sono in italiano anche in /en/) e controllare gli hreflang.
- Successo: "title duplicato" e "description duplicata" su 6 coppie IT/EN; la correzione proposta è un canonical verso
  la pagina italiana e un redirect 301 in .htaccess ("se sono varianti URL della stessa pagina…"). Se l'utente lo
  applica, la versione inglese sparisce da Google.
- Il resto del piano è buono: title e description riscritti con le lunghezze giuste, codice pronto da incollare,
  indica il campo di Yoast/Rank Math, un solo H1 con il secondo in H2, alt da adattare alle immagini vere.
- Note: serve che il prompt (o il rilevatore dei duplicati) riconosca le versioni in lingua (/en/, hreflang).

### [P1 proposto] AI Audit dice "Sitemap XML assente" anche quando c'è (tutti i progetti)
- Scenario / passo: 1B, Madcrumbs, Report AI (id 1, 15 cr, 78 s, una chiamata).
- Atteso: Madcrumbs ha la sitemap di Yoast (`/sitemap_index.xml`, 200, dichiarata nel robots.txt) e i tag hreflang
  it/en nell'head della home.
- Successo: il report ha come problema #3 "Sitemap XML assente e probabile mancanza di hreflang", con "da creare
  sitemap.xml" e il codice di una sitemap da zero; anche il riepilogo dice "la mancanza di una sitemap XML".
  Un consulente che lo gira al cliente fa una figuraccia.
- Causa: `sa_site_config.has_sitemap = 0` su **tutti** i progetti in prod (Madcrumbs, clementeteodonno.it e Stabia,
  tutti WordPress con la sitemap dichiarata nel robots.txt). Il profilo passato all'AI (`UnifiedReportService` riga ~290,
  `SiteProfileDetector`) dice "Sitemap: No". In più il robots.txt viene letto male: la riga "Sitemap: …" finisce in
  `robots_disallows`. Gli hreflang non vengono letti dal crawler, quindi l'AI li tira a indovinare.
- Note: `ssl_valid = 0` su tutti i progetti, anche su siti https validi (oggi non sembra mostrato all'utente: da verificare).

### [OK] Report AI: cosa va bene
- Il Report AI su Madcrumbs **riconosce il sito bilingue** (consiglia title/description "unici e tradotti" e hreflang),
  a differenza del Piano d'azione; trova anche un refuso vero ("Analyics" nell'H1 della pagina GTM). Riepilogo chiaro,
  15 crediti esatti (173 → 156 contando anche il GEO in corso), una sola chiamata AI in 78 s con la pagina in attesa.

### [P3] Piano d'azione: numeri che non tornano ed etichette inglesi
- Scenario / passo: 1B, Madcrumbs, scheda Piano d'azione.
- Successo: in alto "Punti guadagno +31", ma la prima pagina da sola dice "+31 punti" e la seconda "+30" (la somma
  per pagina fa 259). "Tempo stimato 9h 33m" per 12 pagine. Etichette: "Score Attuale/Atteso", "0/72 fix",
  "Esporta To-Do List".
- Nella stessa sessione, Impostazioni del progetto: "Issues Totali", "URL Base", "puo" senza accento; saldo in alto "90.0".

### [P3] AI Audit, scheda "Impostazioni" del progetto: non ha impostazioni del modulo
- Scenario / passo: 1B, Madcrumbs, scheda Impostazioni. Domanda di Clemente: "non dovrebbe stare nei singoli moduli?"
- Cosa c'è: Nome progetto (rinomina il progetto del modulo, diverso dal nome del progetto globale), URL base in sola
  lettura, Statistiche (le stesse della Panoramica), "Zona pericolosa" con Elimina progetto. Nessuna impostazione
  propria di AI Audit (le opzioni della scansione stanno nel riquadro Scansione).
- In più è doppia: icona ingranaggio in alto a destra + scheda Impostazioni; icona orologio + scheda Storico.
- Proposta: nome/URL/elimina vivono nel progetto globale (/projects/{id}); nel modulo una scheda Impostazioni solo se
  ha impostazioni vere (es. pagine massime, frequenza). Da controllare se gli altri 4 moduli hanno la stessa pagina.

### [P3] Storico crediti: codici tecnici come descrizione
- Scenario / passo: 1B e 6.
- Successo: in `credit_transactions` le righe sono "action_plan_generate", "collect_response" (GEO, 1 cr a risposta).
  Da verificare se è questo il testo che l'utente vede nel suo storico crediti.

### [P3] Notifica "Analisi SEO completata per Progetto #19"
- Scenario / passo: 1B, campanella dopo la scansione di Madcrumbs.
- Atteso: il nome del progetto ("Madcrumbs") e il nome del tool (AI Audit).
- Successo: numero interno del progetto al posto del nome.

## Idee per DOPO il post

- **Landing: scansione AI Audit insieme al controllo AI gratuito** (Clemente, 2026-10-16). La scansione di AI Audit è
  gratis: quando un visitatore analizza il suo sito dalla home, oltre all'analisi AI far partire una scansione come la
  farebbe il tool e mostrare il riassunto senza dettagli (quante criticità/avvisi trovati, punteggio, pagine lette).
  I dettagli si vedono dopo l'iscrizione. Da fare alla fine, dopo il post.

## Lista corta prima del post

_Si compila alla fine._
