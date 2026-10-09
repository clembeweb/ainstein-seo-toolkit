# Lancio Ainstein su LinkedIn

> File di stato del lancio. Aggiornato: 2026-10-09.
> Regola: ogni sessione riparte da qui. Un modulo alla volta. Veloce, non perfetto.

## Il pensiero di Clemente (2026-10-09, testo originale, non toccare)

> Essere sognato di fare qualcosa di grande. Ma per tanti motivi tendo sempre a rinunciare, a pubblicare, a divulgare certe cose per perfezionismo più che insicurezza. Senza troppe chiacchiere questo era un progetto che stava andando nell'oblio e non mi sembrava giusto. Quindi ho deciso di renderlo pubblico, anche fosse solo per utilizzarlo io. So che probabilmente è l'ennesimo tool, so che c'è tanta concorrenza con soluzioni anche migliori, ma non per questo non posso mettermi in gioco anche io. Piuttosto che tenerlo come l'ennesima cosa che non ha visto la luce.
>
> Me ne frego dei soldi o di farci soldi. So che non è perfetto ma se (nella mia testa) cerco di renderlo tale finirà come sempre nell'oblio. Basta, sticazzi: tanto sono sicuro che non ci sarà nessun boom di registrazioni e quindi non devo preoccuparmi ora dei problemi. Fanculo al "ti bruci" che mi ridico nel cervello! Ecco Ainstein!

> **Secondo pensiero (stesso giorno, "da amico"):** parlando proprio rivolto ad un amico, poi inizio a chiedermi: e ma la parte di gestione, amministrativa, burocratica, e questo e quello e bla bla bla. E allora quello si vedrà dopo e sti cazzi, capito che dico? 2000 problemi che, visto che tanto non lo cagherà nessuno, mi interesseranno poco; poi se faccio il boom, allora sti cazzi. E ma i costi, i piani e mille altri cazzi. L'ennesima piattaforma a crediti? Non è l'ennesima piattaforma, è LA MIA piattaforma! :) Cioè è da 4 anni che provavo a dargli vita in qualche modo e alla fine (grazie all'AI) l'ho messa in piedi da solo, con tutti gli evidentissimi difetti.

Questo testo è la voce del post LinkedIn e del brief per la landing. Tono: onesto, diretto, niente marketing gonfiato.

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
- [ ] Spegnere in produzione Google Ads Analyzer e Content Creator (script pronto, da lanciare dal terminale)

### Punto 3. Landing (ESSENZIALE, alla fine)
- [ ] Brief semplice per Claude Design, con il pensiero di Clemente come tono
- [ ] Allineare i moduli mostrati a quelli accesi (oggi manca AI Reputation Radar, c'è Content Creator)

### Punto 4. Post LinkedIn
- [ ] Bozza dal pensiero di Clemente + 2-3 screenshot veri
- [ ] Pubblicazione

## Dopo il post (non prima)
- AI Optimizer e SEO On-Page: completare e accendere
- Google Ads Analyzer: test OAuth e riaccensione
- Email in produzione, onboarding, miglioramenti da feedback
