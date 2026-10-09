# Lancio Ainstein su LinkedIn

> File di stato del lancio. Aggiornato: 2026-10-09 sera.
>
> **DOVE SIAMO:** testati e sistemati SEO Audit, AI Content Generator, Keyword Research (tutti online).
> **PROSSIMO PASSO (lunedì 2026-10-12, deciso da Clemente):** prima si chiude il giro di test
> (5. SEO Position Tracking da utente in produzione, poi giro completo da utente nuovo), SUBITO DOPO la
> landing nuova (2-3 h, direttamente nel codice, voce dei pensieri di Clemente, screenshot veri), poi il post.
> Landing attuale bocciata: promette in generale, dice "7 moduli" e vende Google Ads (spento), non mostra il GEO Audit.
> Produzione è al commit `d787ae3` (+ docs).
> Moduli Google Ads e Content Creator disattivati in produzione da Clemente il 2026-10-09 sera. Accesi: SEO Audit, AI Content, Keyword Research, SEO Tracking, AI Reputation (→ GEO Audit).
> Regola: ogni sessione riparte da qui. Un modulo alla volta. Veloce, non perfetto.

## Il pensiero di Clemente (2026-10-09, testo originale, non toccare)

> Essere sognato di fare qualcosa di grande. Ma per tanti motivi tendo sempre a rinunciare, a pubblicare, a divulgare certe cose per perfezionismo più che insicurezza. Senza troppe chiacchiere questo era un progetto che stava andando nell'oblio e non mi sembrava giusto. Quindi ho deciso di renderlo pubblico, anche fosse solo per utilizzarlo io. So che probabilmente è l'ennesimo tool, so che c'è tanta concorrenza con soluzioni anche migliori, ma non per questo non posso mettermi in gioco anche io. Piuttosto che tenerlo come l'ennesima cosa che non ha visto la luce.
>
> Me ne frego dei soldi o di farci soldi. So che non è perfetto ma se (nella mia testa) cerco di renderlo tale finirà come sempre nell'oblio. Basta, sticazzi: tanto sono sicuro che non ci sarà nessun boom di registrazioni e quindi non devo preoccuparmi ora dei problemi. Fanculo al "ti bruci" che mi ridico nel cervello! Ecco Ainstein!

> **Secondo pensiero (stesso giorno, "da amico"):** parlando proprio rivolto ad un amico, poi inizio a chiedermi: e ma la parte di gestione, amministrativa, burocratica, e questo e quello e bla bla bla. E allora quello si vedrà dopo e sti cazzi, capito che dico? 2000 problemi che, visto che tanto non lo cagherà nessuno, mi interesseranno poco; poi se faccio il boom, allora sti cazzi. E ma i costi, i piani e mille altri cazzi. L'ennesima piattaforma a crediti? Non è l'ennesima piattaforma, è LA MIA piattaforma! :) Cioè è da 4 anni che provavo a dargli vita in qualche modo e alla fine (grazie all'AI) l'ho messa in piedi da solo, con tutti gli evidentissimi difetti.

> **Terzo pensiero (2026-10-09 sera, "pippe mentali al volo"):** sti cazzi "il lancio del prodotto": eccolo, il lancio del prodotto. Se veramente lo ritenete utile e migliorabile, lo miglioriamo insieme. L'ho fatto da solo: nessun team IT, design e bla bla. Da solo, con le mie (non pochissime, detto con umiltà) competenze. E questo non è una buona pubblicità per il prodotto, sicuramente, ma lo è per me! E in questo momento preferisco così.
>
> **Idea per l'apertura del post:** una frase che invogli a cliccare "altro", tipo **"Dopo 2 anni ho detto BASTA!"** (gancio in prima riga, prima del taglio "…altro" di LinkedIn).

Questo testo è la voce del post LinkedIn e del brief per la landing. Tono: onesto, diretto, niente marketing gonfiato. Il post è pubblicità per Clemente prima che per il prodotto: "l'ho fatto da solo, miglioriamolo insieme". Prima riga = gancio ("Dopo 2 anni ho detto BASTA!").

## Obiettivo

Pubblicare un post su LinkedIn che presenta Ainstein (https://ainstein.it) con i soli moduli affidabili.
Clemente lo usa per i suoi clienti al posto di SEMrush. Il feedback farà il resto.

## Decisioni prese (2026-10-09)

| Modulo | Lancio | Motivo |
|---|---|---|
| SEO Audit | ACCESO | Il più usato, completo, nessuna API a pagamento. Pezzo forte del post. |
| AI Reputation Radar → **GEO Audit** | ACCESO | Il gancio del post, nessun concorrente. Ci si sta lavorando in un'altra sessione: **non testarlo qui**. Decisione 2026-10-09: rinominarlo "GEO Audit" e allargare il focus da "reputazione" a "cosa pensano di te le AI" (piccoli aggiustamenti di testi/UI, non rifacimento). |
| AI Content Generator | ACCESO | Da dove nasce tutto. Deve funzionare, si migliora dopo. |
| AI Keyword Research | ACCESO | Sostituto SEMrush. Mai usato da Clemente: rischio alto, testare. |
| SEO Position Tracking | ACCESO | Sostituto SEMrush. Valore dopo giorni, basta che non si rompa. |
| Google Ads Analyzer | SPENTO | Decisione Clemente. |
| Content Creator | SPENTO | Mai usato, 4 connettori CMS, troppo da far reggere. |
| AI Optimizer, SEO On-Page | SPENTO | Tabelle DB assenti in produzione, docs "in sviluppo". Da rivalutare DOPO il post: sono attraenti per un utente nuovo. |
| Internal Links | SPENTO | Manca la parte AI, in attesa di rifacimento. |
| Crawl Budget | SPENTO | Già dentro SEO Audit. |

## Checklist

### Punto 1. Registrazione / login / profilo — FATTO (2026-10-09)
- [x] Registrazione, login, logout, profilo, cambio password: testati a mano
- [x] Login Google: funziona (aggiunto URI con www in Google Cloud Console)
- [x] Redirect www → senza www (commit f7717c2)
- [x] Pagine Privacy, Termini, Cookie con dati Beweb Agency S.r.l.s. (commit fdf62ac)
- [x] Nome "SEO Toolkit" → "Ainstein"
- [ ] Verificare che le email (benvenuto, reset password) arrivino davvero in produzione

### Punto 2. Test modulo per modulo (utilità / funzionamento / UI-UX / codice)
Per ciascuno: lo provo io da browser su un sito vero, riporto "cosa funziona" → "cosa non va" → "cosa facciamo".
- [x] 1. SEO Audit — testato 2026-10-09. Fix: crawler si presenta come Chrome 121 (SiteGround blocca Chrome 120/122/131 e i bot sconosciuti dal server), riconosce la pagina-sfida SiteGround (202 sgcaptcha) invece di contarla come pagina, costo 0.1 cr/pagina (era 1), primo audit 100 pagine con ritmo 300ms. Verificato: 21 pagine su ainstein.it, 2.2 crediti. Da rifare a mano: pulsante "Avvia Scansione" e avanzamento live (pannello browser bloccato).
  - Dopo il post: pagina pubblica del bot + IP fisso per le liste bianche degli hosting (come SemrushBot/AhrefsBot); ainstein.it stesso ha score 47/100 (3 critici, 64 warning): sistemare prima del post.
- [ ] 2. AI Reputation Radar → GEO Audit — SALTATO per ora (in lavorazione altrove). Da fare: rinomina in "GEO Audit", testi/UI orientati a "cosa pensano di te le AI", poi test come gli altri.
- [x] 3. AI Content Generator — testato 2026-10-09 (SERP → brief → articolo → copertina). Fix: articoli lunghi troncati (max_tokens 4096 → fino a 16k), doppio addebito crediti (ai_analysis_* + brief/article), copertina (DALL-E 3 ritirato → gpt-image-2, prompt troncato a 200 token), pagina admin che perdeva le chiavi non ancora in DB. Chiave Serper nuova (account nuovo, 2.500 ricerche gratis).
  - Costo reale per 1 articolo (Claude opus 5.5): brief ~0,03 $, articolo 1.921 parole 0,17 $ in 68 s, copertina 0,04 $, SERP 1 credito Serper. Totale ≈ 0,25 $. Crediti utente: 3 SERP + 3 scraping + 3 brief + 10 articolo + 3 copertina = 22 (con i default di config; in produzione il modulo ha ancora scraping=12/url e brief=5: da decidere nel ragionamento sui crediti).
  - Da verificare a mano: passaggio Brief → Articolo con click vero su "Avanti" (via script non aggiornava la pagina finché non ricaricavo); step 4 "Pubblica" senza sito WordPress collegato mostra solo "Indietro".
  - Link interni: 0 inseriti perché il sito WordPress collegato (SiteGround) ha risposto con la sfida anti-bot al mio IP. Da riprovare dal server.
- [x] 4. AI Keyword Research — testato 2026-10-09 in PRODUZIONE (dal Chrome di Clemente, progetto "Stabia Boat Rental (test)" #17). Research Guidata: brief → raccolta con Keyword Planner (77 kw in <20 s, gratis) → clustering AI (8 cluster, nota strategica di qualità da consulente) → risultati con export CSV.
  - Fix: Google Ads API v20 ritirata → v25 (Keyword Planner e Ads Analyzer erano morti con 404); collegato l'OAuth MCC in produzione (mancava: nessun token); clustering troncato a 4096 token → tetto AI centrale 16000 e risposta troncata = errore senza addebito; doppio addebito crediti (ai_analysis_* + kr_*) tolto in research/architettura/editoriale; errore SSE mostrato al posto di "Connessione persa"; mese dello storico volumi (era sempre 0).
  - Costo reale: clustering 0,13 $ (opus 5.5, 47 s), Keyword Planner 0 $. Crediti: 3 (prima 4-5 col doppio addebito).
  - Da sistemare (UX, non bloccante): dopo un errore o un reload il brief e le seed si perdono e la ricerca precedente resta "Error" non riprendibile; etichette in gergo ("seed keyword", "clustering", "intent") per chi non è SEO; docs dicono 5-10 seed, form max 5; stato "Collecting" in inglese nella lista.
  - Non testati: Architettura Sito e Piano Editoriale (stesso motore, stesse correzioni applicate).
- [ ] 5. SEO Position Tracking
- [ ] Giro completo da utente nuovo: iscrizione → dashboard → prima operazione con 30 crediti → crediti finiti (deve portare ai prezzi, non a un errore)
- [x] Spegnere in produzione Google Ads Analyzer e Content Creator (fatto da Clemente, 2026-10-09)

### Punto 3. Landing (ESSENZIALE, alla fine)
- [ ] Brief semplice per Claude Design, con il pensiero di Clemente come tono
- [ ] Allineare i moduli mostrati a quelli accesi (oggi manca AI Reputation Radar, c'è Content Creator)

### Punto 4. Post LinkedIn

**REGOLA (Clemente, 2026-10-09 sera, arrabbiato):** il post NON ha scopo commerciale. Niente "prova gratis",
niente crediti, niente elenco di funzioni da brochure. Serve a lui: fa gioco nelle nuove candidature e nel cercare
lavoro, e per lui "è come far nascere un figlio". Voce in prima persona, storia personale, il prodotto è il figlio,
non la merce. Il link ci sta solo come "se vi va di vederlo". La v0 qui sotto è BOCCIATA per questo motivo.

**Bozza v1 (dopo la correzione):**

> Dopo 4 anni ho detto BASTA.
>
> Basta tenerlo nel cassetto perché "non è ancora perfetto". Basta la vocina che dice "ti bruci".
>
> Quattro anni fa ho iniziato a costruire una piattaforma SEO con l'AI dentro. L'ho lasciata e ripresa non so quante volte. Ogni volta mi fermavo per perfezionismo, mai per mancanza di idee.
>
> Oggi è online. Si chiama Ainstein.
>
> L'ho fatta da solo. Nessun team, nessun designer, nessun investitore. Io, vent'anni di SEO e Google Ads, e l'intelligenza artificiale che mi ha dato le mani che non avevo: quelle di chi sa programmare.
>
> Non è perfetta. Ha difetti evidentissimi, e me ne frego. Non la pubblico per venderla e non mi aspetto nessun boom. La pubblico perché tenerla nascosta era l'ennesima cosa che non vedeva la luce, e non mi sembrava giusto. Per me è come far nascere un figlio.
>
> Se vi va di vederla, è su ainstein.it. Se la provate e vi sembra utile, miglioriamola insieme. Se vi sembra inutile, ditemelo: è il regalo più grande.
>
> Questa non è una buona pubblicità per il prodotto. Lo è per me. E oggi preferisco così.

**Bozza v0 (BOCCIATA: troppo commerciale):**

> Dopo 4 anni ho detto BASTA.
>
> Basta tenere nel cassetto una cosa perché "non è ancora perfetta".
>
> Si chiama Ainstein. È una piattaforma SEO con l'AI dentro: audit del sito, ricerca keyword con i volumi veri di Google, articoli pronti da pubblicare, monitoraggio posizioni. E una cosa che non ho visto altrove: ti dice cosa pensano di te ChatGPT e le altre AI.
>
> L'ho fatta da solo. Nessun team IT, nessun designer, nessun investitore. Io, le mie competenze (non pochissime, lo dico con umiltà) e l'AI che mi ha dato le mani che non avevo.
>
> Non è perfetta. Lo so. Se cercavo di renderla perfetta finiva come sempre: nell'oblio. Quindi la pubblico così.
>
> Me ne frego dei soldi. Me ne frego se è "l'ennesimo tool". Non è l'ennesimo tool: è il MIO.
>
> Se lo provi e ti sembra utile, miglioriamolo insieme. Se ti sembra inutile, dimmelo lo stesso: è la cosa più utile che puoi fare per me.
>
> Si prova gratis, 30 crediti senza carta: ainstein.it
>
> Questa non è una buona pubblicità per il prodotto. Lo è per me. E oggi preferisco così.

Note per la revisione: prima riga sotto i 150 caratteri (resta sopra il taglio "…altro"); "4 anni" viene dal suo testo (lui aveva scritto "2 anni" come esempio di gancio: scegliere); verificare che il GEO Audit sia presentabile prima di citarlo; 2-3 screenshot veri (cluster Stabia Boat Rental, audit, articolo con copertina).

- [ ] Bozza dal pensiero di Clemente + 2-3 screenshot veri
- [ ] Pubblicazione

## Segnato per dopo (raccolta completa del 2026-10-09)

### Prima del post (piccoli, ma visibili)
- [ ] ainstein.it stesso: SEO Audit dà score 47/100 (3 critici, 64 warning). Sistemare i critici.
- [ ] Landing: togliere Content Creator, aggiungere GEO Audit, allineare ai 5 moduli accesi.
- [ ] Tailwind caricato da CDN (`cdn.tailwindcss.com`) in produzione: warning in console, lento. Build locale del CSS.
- [ ] Email in produzione (benvenuto, reset password) mai verificate: registrare un account vero e controllare.
- [ ] Verifiche a mano con Clemente: pulsante "Avvia Scansione" + avanzamento live (SEO Audit); "Avanti" dopo il brief e step "Pubblica" senza WordPress (AI Content).

### Crediti (ragionamento da fare con i numeri veri)
- Costi API misurati: audit 100 pagine ≈ 0 $; articolo completo ≈ 0,25 $; keyword research ≈ 0,13 $; copertina 0,04 $.
- In produzione il modulo AI Content ha ancora scraping 12 cr/URL e brief 5 (config dice 1 e 3). Decidere e allineare.
- 30 crediti iniziali: con i default attuali bastano per 1 audit + 1 articolo + 1 ricerca keyword.

### UX da utente nuovo (non bloccanti)
- Keyword Research: brief e seed si perdono dopo errore/reload; ricerca precedente in "Error" non riprendibile; gergo ("seed", "clustering", "intent"); docs dicono 5-10 seed, form max 5; stato "Collecting" in inglese.
- AI Content: step "Pubblica" senza sito WordPress mostra solo "Indietro"; wizard a volte non aggiorna la pagina dopo il brief (verificare con click vero).
- Modal "Attiva modulo" di Keyword Research: descrizioni in gergo, nessun "consigliato per iniziare".
- SEO Audit: lo stesso canonical viene segnalato due volte (warning Indicizzabilità + notice Tecnico).
- Console: una risorsa 404 nella pagina wizard AI Content (da individuare).

### Tecnico
- Crawler: pagina pubblica del bot + IP fisso per le liste bianche degli hosting (come SemrushBot). SiteGround blocca Chrome 120/122/131 e i bot sconosciuti dal server; dopo molte richieste mette il captcha sull'IP.
- Link interni automatici (AI Content): 0 inseriti perché il WordPress collegato (SiteGround) ha risposto con la sfida anti-bot. Riprovare dal server e gestire il 202.
- AiService: quando il primario fallisce e scatta il fallback, il fallimento non viene loggato in ai_logs.
- config/app.php: modello di default `claude-sonnet-4-20250514` ritirato → mettere `claude-sonnet-5-5`; stesso per gli ambienti locali (Clemente usa opus-5-5 in prod).
- DataForSEO come riserva SERP automatica per AI Content/Keyword Research (oggi solo Serper → SerpAPI).
- gpt-image-1-mini in ritiro il 2026-12-01: la copertina usa gpt-image-2, ok; tenere d'occhio prezzi.
- Google Ads API: Google ritira una versione ogni ~3 mesi (v25 oggi). Controllare `docs/sunset-dates` ogni trimestre o lo stesso 404 tornerà.
- Architettura Sito e Piano Editoriale (Keyword Research) non testati.

## Dopo il post (non prima)
- AI Optimizer e SEO On-Page: completare e accendere
- Google Ads Analyzer: test OAuth e riaccensione
- Email in produzione, onboarding, miglioramenti da feedback
