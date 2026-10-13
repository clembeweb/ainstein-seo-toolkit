# Lancio Ainstein su LinkedIn

> File di stato del lancio. Aggiornato: 2026-10-13 pomeriggio (13:30).
>
> **PASSAGGIO DI CONSEGNE 2026-10-13 sera (ripartire da qui):** fatto e online oggi: AI GEO Audit completo (dubbi delle AI con causa tecnica, audit d'acquisto/reputazione, judge Sonnet low, prezzo unico, controllo tecnico gratis, tabella domande con azioni multiple, cambio audit in pagina); conferme con la finestra dell'app in tutta la piattaforma; "Attiva su un progetto" in tutti i moduli; prezzi in pagina dai costi veri; dashboard utente con card GEO; pulizia admin; **nomi nuovi: AI Writer · AI Keywords · AI Audit · AI Rank · AI GEO Audit**; pagina docs `/docs/ai-reputation`. Run d'acquisto su Stabia (run 4, 44 risposte, 0,87 $) **finito ma non ancora letto**: Clemente ha detto "lo vediamo dopo".
> **Prossimi passi, in ordine** (dettaglio in `docs/2026-10-13-check-ui-ux-moduli-admin.md`, sezione "Ordine di lavoro"): 1) leggere il report del run 4 di Stabia (audit d'acquisto); 2) AI GEO Audit: hero "Come funziona" + sezione "Scopri cosa puoi fare" nella pagina iniziale (unico modulo senza); 3) sottotitoli dei moduli e bottone "Nuovo progetto" uniforme; 4) piano Free 30 vs 100 crediti (decisione di Clemente); 5) /docs e /pricing ai 5 moduli (togliere Internal Links, Ads, Content Creator); 6) tour guidati riscritti (GEO compreso); 7) valutazione Recap SEO GA4+GSC (Amevista); 8) landing e post.

> **DOVE SIAMO:** test delle 7 funzioni AI, fatte 6 su 7 (Architettura, Piano, Articoli Automatici, Meta Tags, Quick Wins, Page Analyzer). **Architettura Sito: OK** (2026-10-12). **Piano Editoriale: OK** (2026-10-13, Stabia, piano 17: 12 articoli su 3 mesi, 5 crediti, ogni articolo indica la pagina dell'architettura a cui rimanda; screenshot in `_INBOX/2026-10-13-screenshot-piano-editoriale-stabia.jpg`). La catena **Architettura → Piano → AI Content** ora è collegata in prod (commit `39d23b8`, `7cb4105`, `8170db3`, `8cc4d81`): ponte con wizard precompilato (categorie = keyword principali delle pagine), budget token del piano alzato (si troncava a 4200), bottone "Attiva AI Content" che torna al piano, mesi coerenti (mese 1 = mese dopo la creazione, sia nel prompt sia nella pagina), intent in italiano in tutto Keyword Research. AI Content (Articoli Automatici) è stato attivato sul progetto Stabia.
> **Articoli Automatici (2026-10-13): tempo 1 FATTO e verificato in prod** (commit `2d7e7bb`, `b521b84`, `f875faa`): log scrivibili da web e cron (prima il logger rompeva la catena SERP), job cron bloccati chiusi in automatico, **addebito crediti come il wizard** (era gratis dal 2026-10-09; verificato: fonti 2 + brief 3 + articolo 10 + copertina 3 = 18), date dal piano (4/mese: giorni 1, 8, 15, 22 alle 9; verificato 08/11/2026 09:00), config automazione creata all'attivazione. Testo degli articoli: buono (2 articoli Stabia, id 46 e 47).
> **Articoli Automatici tempo 2: FATTO e verificato in prod** (commit `6622f05`): la keyword in coda ritrova la sua riga del piano (nessuna migrazione) e l'articolo scrive per il cliente. Verificato su articolo 48 "Giro isola Capri in barca": link a https://www.stabiaboatrental.com/giro-in-barca-capri e /grotta-azzurra-faraglioni (le pagine citate nel piano), "Stabia Boat Rental" citato 2 volte come invito a prenotare, nessun concorrente, copertina, ~2.700 parole. Cron con copertina e link dal WordPress; pannello "Processa Ora" aggiornato in diretta (padding SSE); conferma "Ferma elaborazione". Serper ora primo provider SERP di AI Content (cambiato 2026-10-13).
> **Link rotti risolti** (commit `9a04b54`): verificato che /giro-in-barca-capri era 404 (le pagine vere di Stabia sono /capri/, /positano/...). Ora l'articolo linka solo pagine che rispondono davvero (URL finale dopo redirect), altrimenti la homepage; il piano mostra l'avviso "N pagine dell'Architettura non esistono ancora sul sito".
> **SEO Meta Tags (2026-10-13): testato su sito vero Stabia** (20 pagine da sitemap, title/description ottimi, nella lingua della pagina). Sistemato e in prod (`c3c6f8d`, `9a04b54`): sitemap bloccata da SiteGround (UA "bot" → firma browser), errore import col motivo vero, **doppio addebito tolto e prezzo a 2 crediti** (deciso da Clemente; prima 3 + 1 di AiService = 4 contro i 2 promessi), costi modificabili da Admin, selezione pagine + conferma "N pagine: X crediti, ne hai Y", avanzamento in diretta, import sitemap chiaro ("undefined" tolto, sitemap già spuntate). **Verificato nel browser** (commit `5ad89af`): conferma "Generi i meta tag di 1 pagina: 2 crediti", storico 1 scrape + 2 generazione e nessun ai_analysis in più; avviso pagine mancanti nel piano 17 (7 pagine). Nel farlo sistemati anche: `config/app.php` meta_generate 3→2, il salvataggio dei costi da Admin svuota la cache (prima il prezzo nuovo arrivava dopo 1 ora), i costi decimali non vengono più azzerati dal salvataggio. Pubblicazione su WordPress non testabile (Stabia senza WP collegato).
> **Quick Wins + Page Analyzer (2026-10-13): rifatti e verificati in prod** (commit `96f1954`, `4c91ce4`). Page Analyzer in produzione NON era mai partito (fetch su `/seo-toolkit/...` → 404 muto): sistemato, analisi ottime (Stabia: 46/100, "keyword inglese su pagina italiana → crea la pagina EN con hreflang"; 54/100 "keyword generica su pagina solo senza patente"). Decisione Clemente: Quick Wins diventa **lista gratuita senza AI** (con GSC: pos 4-20 per click guadagnabili; senza GSC: pos tracciate 4-30 per volume) con "Analizza pagina (2 cr)" per riga; tolto il report AI generico (vedeva solo numeri). Page Analyzer addebita 2 crediti al proprietario (prima 1 variabile di AiService a chi cliccava). Schede del modulo in italiano.
> **Report settimanale AI (2026-10-13): NON si lancia** (decisione Clemente, strada A). Trovato inutilizzabile: pagina report senza link, "Genera Report" → "Report non trovato", nessuna notifica/email, addebito automatico a tutti con un solo interruttore admin, dati poveri, solo con GSC, "0 crediti" mostrati. Fatto: tolti bottone rotto e riquadro crediti, nota "in lavorazione". Resta SPENTO (`ai_reports_enabled` = off). Non citarlo in landing/post. Rifacimento in "Dopo il post".
> **Giro delle 7 funzioni AI: CHIUSO** (6 testate e sistemate + report rimandato).
> **GEO Audit (2026-10-13 pomeriggio): testato, problemi da presentare — ripartire da qui.** Fatto e online (commit `0317ca5`, `c540af3`): rinominato "GEO Audit" ovunque (anche nome DB del modulo da Admin, modulo id 11), chiave Gemini verificata funzionante in prod (manca solo Perplexity: viene saltato, non blocca), bottone "Conferma tutte" nel profilo (omonimi esclusi). Prova vera lanciata alle 12:56 su progetto globale 17 → GEO Audit `https://ainstein.it/ai-reputation/project/1` (Stabia Boat Rental, azienda, Castellammare di Stabia): profilo 20 righe confermate, 40 domande AI, **run 1 da 104 risposte** (ChatGPT + Gemini), alle 13:20 era a 52/104, un solo 503 temporaneo di Gemini. Costo stimato approvato da Clemente: ~1-2 $ API, ~15-20 crediti.
> **Run 1 FINITO alle 13:52** (98 risposte, 97 giudicate, 7 interventi, 6 errori Gemini, 2,87 $ API). Report `/ai-reputation/project/1/runs/1`: share of voice 79%, rischio Basso 2%, articoli gap sensati, "Chi citano al posto suo" utile. "Genera scheda" OK (dati veri dal sito, 1 credito), "Esporta PDF" OK (60 KB). Deploy fatto: prod a `0eb3c40`.
> **GEO Audit SISTEMATO e online (2026-10-13, ~16:00, ADR-015 del modulo):** il judge separa i "dubbi delle AI" (P.IVA non trovata, telefono condiviso, recensioni non verificabili) dalle fonti negative vere → niente più bewebagency.com da "rimuovere"; nuovo intervento in cima "Le AI hanno dubbi su X: cosa chiarire sul sito" con scheda di testi pronti (footer, Chi siamo, Sicurezza, Contatti, Prenotazione) + JSON-LD LocalBusiness + checklist; per le aziende articoli sul sito proprio (prima clickandboat.com); prezzo unico 1 credito a risposta (prima 1,2 di nascosto), Rianalizza 0,5, stima e conferma prima di "Avvia run"; crediti al posto del costo API; conteggi coerenti; PDF "GEO AUDIT". Judge su Sonnet 5.5 low (97/97 come Opus sulle negative, 0,016 $ a risposta → run da ~100 risposte ≈ 4,5 $ di costo reale, 100 crediti). Run 1 rigiudicato. PDF in `_INBOX/2026-10-13-geo-audit-stabia-piano.pdf`. Ancora aperto: nome "(test)" e tipo soggetto alla creazione, guida utente `shared/views/docs/ai-reputation.php` (manca), costo di un run vs 100 crediti di benvenuto (un audit li consuma tutti: valutare un "audit rapido" più piccolo).
> **Problemi trovati, da presentare a Clemente (blocco "cosa non va"):** 1) "Fonti negative da far rimuovere" sbagliate: sorrentoboatrental.com (concorrente), **bewebagency.com (l'agenzia di Clemente)**, informazione-aziende.it (registro aziende) → il judge scambia concorrenti/neutri per negativi; 2) **doppio addebito**: ogni giudizio scala 1 credito `ai_analysis_medium` via AiService (97 crediti) oltre a 0,2/risposta → run costato ~118 crediti invece di ~20; 3) il report mostra "costo API 2.866 $" all'utente; 4) conteggi incoerenti (testata "104 risposte · 90 analizzate", messaggio finale "98 / 97"); 5) costo reale 2,87 $ contro stima 1-2 $; 6) note già raccolte sotto (nome "(test)", tipo Persona, "Avvia run" senza costo).
> **Proposta da fare a Clemente (una domanda):** l'avanzamento in diretta si blocca su ~21 pagine del sito (Apache + PHP-FPM trattengono gli eventi SSE piccoli). Finora corretto con un riempitivo da 8 KB in Articoli Automatici, Meta Tags e GEO Audit. Soluzione unica lato server: file `/etc/apache2/conf-available/ainstein-fcgi-flush.conf` con `<Proxy "fcgi://localhost/"> ProxySet flushpackets=on </Proxy>` (SetHandler già su `proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost`), `a2enconf` + `apache2ctl configtest` + reload. Cambia la configurazione del server: chiedere prima.
> **Poi:** landing (la fa Clemente con Claude Design, materiale in `_INBOX/2026-10-13-per-claude-design/`), poi il post. A fine sessione proporre di spostare in `_INBOX/_superati/` il file `_INBOX/2026-10-13-prompt-claude-design-landing.md` (doppione della copia nella cartella per Claude Design).
> Decisioni di oggi: audit gratis (costo 0/pagina), 100 crediti ai nuovi iscritti, pulsante "Posizioni" a tutti con addebito, principio "gratis ciò che non costa" (sezione Crediti).
> Landing attuale bocciata: promette in generale, dice "7 moduli" e vende Google Ads (spento), non mostra il GEO Audit.
> Produzione è al commit `0eb3c40` (+ docs).
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
- **2026-10-13 sera — nomi unici dei moduli (decisione Clemente):** **AI Keywords** (keyword-research), **AI Writer** (ai-content), **AI Audit** (seo-audit), **AI Rank** (seo-tracking), **AI GEO Audit** (ai-reputation). Da usare ovunque: menu (DB `modules.name`), titoli, dashboard, docs, prezzi, tour, post.


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
- [x] 5. SEO Position Tracking — testato 2026-10-12 in PRODUZIONE (Chrome di Clemente, progetto "Stabia Boat Rental (test)" → tracking #6, 5 keyword). Aggiunta keyword, volumi via Keyword Planner (gratis), rank check via Serper fino a 100 risultati, Landscape e Storico coerenti. Decisione Clemente: pulsante "Posizioni" resta a tutti gli utenti, con addebito.
  - Fix: i rank check manuali non scalavano crediti (il cron li trattava come automatici) → 1 cr/kw all'owner; cron usciva se "Auto Rank Check" era spento e i job manuali restavano in coda per sempre; cascata inutile DataForSEO+SerpAPI dopo un "non trovato" di Serper (DataForSEO risponde 402 = account senza fondi, SerpAPI 429 = ricerche finite) → ora Serper è definitivo; keyword verificata ma non in top 100 mostrava "-"/"Mai" come una mai verificata → ">100" + data; cron ogni minuto (era 5) con lock su file e log silenzioso quando non c'è nulla da fare.
  - Costo reale: 0 $ (Serper gratis 2.500/mese: 1 chiamata se in top 10, fino a 10 se non trovata; Keyword Planner 0 $). Crediti: 1/kw volumi + 1/kw posizioni. Job di 5 keyword: parte entro 60 s, dura 65-75 s.
  - Da sapere: Google oscilla (stessa keyword 32 → non trovata → 22 in tre giri da 15 min); DataForSEO a 0 fondi e SerpAPI esaurito, i fallback oggi sono morti (decidere se ricaricare DataForSEO, per il lancio Serper basta).
  - Da sistemare (non bloccante): tab in inglese (Landscape, Overview, Pages, Tags); due filtri "Tutte le posizioni" affiancati; SSE in tempo reale esiste ma la pagina usa il cron (ok con partenza entro 60 s); `gsc_client_id`/`gsc_client_secret` spazzatura nei settings modulo in produzione (non usati, OAuth centralizzato); keyword non trovata non entra nello snapshot giornaliero del trend.
- [x] Giro completo da utente nuovo — fatto 2026-10-12 (account "Luca Test" creato da Clemente, 30 crediti; progetto Stabia Boat Rental; audit → AI Content fino alla copertina → crediti a 0). Mail di benvenuto arrivata subito. Problemi trovati e risolti lo stesso giorno, tutti in produzione:
  1. Crediti finiti non portavano ai prezzi → toast "Crediti insufficienti" con pulsante "Vedi piani e ricarica" (15 s), alert globale con lo stesso link, badge crediti nell'header cliccabile verso /pricing con "Ricarica" sotto i 10 (c380a5d, 4f0a492).
  2. SEO Audit partiva col 10% dei crediti e a zero continuava gratis → serve il saldo per la stima intera (messaggio con le pagine analizzabili), a crediti esauriti si ferma con notifica; costo per pagina ora impostazione admin del modulo, default 0 = gratis (4f0a492, 1994d58).
  3. "Guida 2025" nel 2026 → data di oggi iniettata nel system prompt di OGNI chiamata AI, in AiService (000dadd, 6d1991f).
  4. Wizard AI Content perdeva la selezione fonti al reload e cancellava brief/articolo se "Rigenera" falliva → ripristino da localStorage, salvataggio a ogni click, flag ripristinati su errore (3a3ad1a).
  5. Entità del brief con spazzatura ("nbsp nbsp") → testo ripulito prima della conta + stopword italiane; l'AI restituisce key_terms e recommended_word_count che hanno la precedenza (58991a2). Riscrittura "alla scheda" rimandata a dopo il post.
  6. SEO Audit: critici "noindex + canonical" e titoli duplicati su pagine 404 → le 4xx/5xx generano solo "Link interno rotto"; duplicati e orfane solo su pagine 2xx, homepage mai orfana. Score Stabia 43 → 58 (ffdac16, c3f2cae).
  7. Log di debug (token CSRF, risposte) in console nel wizard → rimossi; chiave SerpAPI letta dalle impostazioni globali, la copia nei settings del modulo AI Content è identica e si può cancellare da admin (081f1b6).
  - Restano (non bloccanti, segnati sotto): wizard "Indietro" dallo step Articolo mostra "Genera articolo" invece dell'articolo; dashboard dice "Articoli da 3 crediti" (sono 20) e "Audit da 2 crediti" (è gratis); AI Reputation Radar manca dalle 4 card della dashboard; riga articolo in DB con credits_used 0 (cosmetico); SEO Tracking pagina keyword ha un toast proprio senza link ai piani.
- [x] Spegnere in produzione Google Ads Analyzer e Content Creator (fatto da Clemente, 2026-10-09)

### Punti di forza veri (analisi di mercato 2026-10-12, prima della landing)

Richiesta di Clemente: "prima verificherei bene cosa gli altri non hanno davvero". Fatto modulo per modulo,
con ricerche sul mercato (Semrush, Ahrefs, SEOZoom, SE Ranking, Koala, SEOwriting, Nightwatch, Peec, Profound, Otterly...).
Fonti nel messaggio di sessione; prezzi indicativi, cambiano spesso.

### 1. Keyword Research — il pezzo davvero raro (intuizione di Clemente confermata, con una precisazione)
- **Cosa non è raro:** il clustering AI delle keyword. Lo fanno Semrush (Keyword Strategy Builder: pillar/subpage da 5 seed, incluso nei piani da ~139 $/mese), Keyword Insights (58 $+), SE Ranking, SEOZoom (Editorial Assistant, 59 €+).
- **Cosa non abbiamo trovato in nessun prodotto:** la catena **"descrizione del business in italiano → keyword con volumi veri di Google → architettura del sito (pagine, URL, H1 in ordine di priorità) → piano editoriale a mesi con gap SERP → articolo con un click"**. Sul mercato questa catena esiste solo come workflow di prompt o skill per Claude Code, non come strumento. Semrush dà pillar/subpage ma non parte da un brief, non filtra per pertinenza col business, non fa il calendario a mesi né scrive l'articolo nello stesso flusso.
- **Dati:** usiamo Google Keyword Planner diretto (gratis, 0 $): i volumi che gli altri rivendono.
- **Debolezze da non nascondere:** niente keyword difficulty né backlink/SERP features; max 5 seed; Architettura e Piano Editoriale mai testati nel giro; qualità dipendente dal modello (oggi Opus 5.5 ≈ 0,13 $ a ricerca).
- **Per il post/landing:** NON "clustering AI" (commodity). SÌ: "descrivi il tuo business, ti restituisce struttura del sito e piano editoriale con i numeri veri di Google, e da lì scrivi l'articolo".

### 2. GEO Audit (ex AI Reputation Radar) — mercato affollato e caro, noi diversi su una cosa
- **Mercato 2026:** Semrush AI Toolkit 99 $/mese per dominio, Ahrefs Brand Radar 199 $/mese, Peec ~95 $, Profound 99-499 $, Otterly 29 $, Nightwatch add-on 99 $. Tutti misurano visibilità/menzioni ri-ponendo prompt alle AI.
- **Cosa facciamo di diverso:** non solo "quanto ti citano" ma **"cosa dicono di te, quali rischi, e cosa fare"**: giudizio per domanda (judge), piano d'azione, scheda operativa per intervento, PDF. Orientato a reputazione e piccole realtà italiane, non a brand enterprise. Prezzo a crediti invece di 99 $/mese.
- **Onestà:** "nessun concorrente" è falso; "l'unico che ti dice cosa fare, in italiano, senza abbonamento da 99 $" è vero. Attenzione: mai testato in questo giro, chiavi Gemini mancanti in produzione (vedi punto 2.2).

### 3. SEO Audit — commodity, vale come porta d'ingresso
- Screaming Frog gratis (500 URL), Sitebulb da 18 $, Semrush/SE Ranking nelle suite. Nessuno dei grandi ha un piano d'azione AI verificato dalle fonti, ma Sitebulb ha "Hints" prioritizzati.
- **Nostro:** gratis, senza installare nulla, priorità in italiano semplice, piano d'azione AI (10 cr), collegato a tracking e contenuti. È il modulo con cui si entra, non quello con cui ci si distingue. Obbligo: ainstein.it stesso deve avere uno score decente (oggi 47).

### 4. AI Content Generator — mercato pieno e a basso prezzo
- Koala 9-49 $/mese, SEOwriting 19 $, NeuronWriter 23 $, Frase 49 $+, Surfer 49-179 $: SERP, brief, articolo, pubblicazione WordPress ce l'hanno tutti.
- **Nostro (3 varianti, non una):**
  - *Articoli Manuali*: stessa categoria dei concorrenti; differenze: scegli tu le fonti, brief con strategia da consulente (Opus), copertina, link interni dal pool del tuo sito, e **riceve il piano editoriale dal modulo 1**. Da solo non è un argomento di vendita. Non dire "scrive articoli SEO" come se fosse nuovo.
  - *Articoli Automatici*: coda di keyword con data, il cron (ogni minuto) fa SERP → brief → articolo → copertina → pubblicazione su WordPress (bozza o pubblicato) senza nessuno davanti; il Piano Editoriale di Keyword Research ci entra con un click. Koala e SEOwriting hanno il bulk con auto-post, quindi non è unico; è però **l'anello che chiude la catena** (piano a 6 mesi → articoli che escono da soli). Mai testato nel giro: provarlo prima di citarlo.
  - *SEO Meta Tags*: importa le pagine esistenti (sitemap, WordPress, CSV, a mano), le legge, l'AI scrive title e description nella lingua della pagina, anteprima/approvazione, **invio a WordPress dal tool** (plugin Ainstein, singolo o bulk). Sul mercato esistono plugin WP che lo fanno dentro WordPress (Rank Math Content AI a crediti, Bavarian Rank Engine, RankBotAI, Mass Meta Creator); da fuori WordPress con anteprima e approvazione è raro, e per chi gestisce più siti clienti è comodo. Argomento da "seconda riga", non da titolo. Mai testato nel giro.

### 5. SEO Position Tracking — commodity con un dettaglio raro
- Wincher 41-49 €, SE Ranking 52-65 $, AccuRanker 116 $+, Nightwatch 32-79 $. Quasi tutti con GSC.
- **Raro (nessuna fonte lo ha trovato):** il **report settimanale scritto dall'AI** (keyword in salita/discesa, anomalie, URL che cambiano). Ma in produzione i report AI sono SPENTI e mai testati. O si accendono e si provano prima del post, o non si cita.
- Posizioni via Serper fino a 100 risultati: Google oscilla (visto: 32 → non trovata → 22 in 45 minuti), lo storico conta, il singolo check no.
- **Le altre parti del modulo (verificate nel codice/spec, non tutte provate):** Search Console con import keyword e click/CTR reali incrociati con le posizioni (commodity, ce l'hanno quasi tutti); volumi Keyword Planner gratis (gli altri li fanno pagare); 20 paesi; gruppi/tag; **Confronto periodi** stile Semrush; **Quick Wins** (keyword in posizione 11-20 con azioni suggerite, AI, 2-3 cr) e **Page Analyzer** (analisi AI di una pagina con competitor, 2-3 cr): questi due sono "consigli", non solo numeri, e i tracker da 49 € non li hanno; **Alert**: UI presente ma backend incompleto (spec: "in corso"), **non citare**; **Report mensile executive**: non implementato; **Report settimanale AI**: implementato ma spento in produzione e mai provato.
- **Per landing/post:** "posizioni + click reali di Search Console + l'AI ti dice quali keyword spingere (Quick Wins)". Il report settimanale solo se lo accendiamo e lo proviamo prima.

### Il punto di forza vero, trasversale
Non un modulo: **la catena intera, in italiano, allo stesso posto, a crediti**: descrivi il business → struttura e piano con i numeri veri di Google → articolo e pubblicazione → audit del sito → posizioni e click reali → cosa dicono di te le AI. Per un freelance SEO italiano oggi questa catena costa Semrush (139 $) + un writer (20-50 $) + un tool GEO (29-99 $). E il secondo argomento, quello del post: **l'ha costruita un SEO per usarla sui propri clienti**, non una software house per venderla.

## Punto 3. Landing (ESSENZIALE, alla fine)

### Brief per Claude Design (scritto 2026-10-12 sera, canvas: https://claude.ai/artifact/NedS1Rk1t6GNjCtm6fAeFu)
1. **Cos'è:** Ainstein, piattaforma SEO con l'AI dentro, costruita da un SEO (Clemente, 20 anni di SEO e Google Ads) per usarla sui propri clienti al posto di Semrush. Non da una software house per venderla.
2. **Tono:** prima persona, onesto, diretto. "L'ho fatta da solo, con tutti i difetti. Se ti è utile, miglioriamola insieme." Niente copy da agenzia, niente "rivoluziona", niente superlativi.
3. **Una promessa sola (la catena):** descrivi il tuo business in italiano → keyword con i volumi veri di Google → struttura del sito (pagine, URL, H1) → piano editoriale a mesi → articolo con un click → audit → posizioni e click reali → cosa dicono di te le AI. Tutto allo stesso posto, in italiano, a crediti.
4. **5 moduli accesi, nell'ordine:** Keyword Research (il pezzo raro), GEO Audit ("cosa dicono di te ChatGPT e le altre AI, e cosa fare"), SEO Audit (gratis, senza installare nulla), AI Content (brief da consulente, copertina, WordPress), Position Tracking (posizioni + Search Console + Quick Wins).
5. **Cosa NON dire:** "7 moduli", Google Ads, Content Creator, "nessun concorrente", "clustering AI", "scrive articoli SEO" come fosse nuovo, report settimanale AI (finché non è acceso e provato).
6. **Prezzo:** 100 crediti all'iscrizione, senza carta. Audit gratis. Link a /pricing per il resto.
7. **Brand:** logo Ainstein (orizzontale), blu #006e96, scuro #004d69, accento #00a3d9, font DM Sans. Niente gradient wash, niente emoji, icone Heroicons.
8. **Struttura:** hero con la frase di Clemente e uno screenshot vero → la catena in 6 passi → i 5 moduli (screenshot vero + 2 righe oneste ciascuno, debolezze incluse dove servono) → "chi l'ha fatta e perché" (la storia, 6 righe) → CTA "Provalo, è gratis" + "Se ti sembra inutile, dimmelo".
9. **Formato:** una pagina, desktop 1280 e mobile 390. Poi la porto in `public/landing4.php` con Tailwind compilato.
10. **Screenshot:** per ora quelli di febbraio in `public/assets/images/screenshots/` (output-keyword-research = Architettura Sito vera). Da rifare freschi prima di pubblicare: Architettura Sito, GEO Audit, SEO Audit, articolo con copertina.

- [ ] Brief semplice per Claude Design, con il pensiero di Clemente come tono
- [ ] Allineare i moduli mostrati a quelli accesi (oggi manca AI Reputation Radar, c'è Content Creator)
- [ ] Pagina /pricing (2026-10-12): mostra l'header pubblico ("Accedi", "Prova gratis") anche a chi è già loggato, dice "tutti i 7 moduli" ed elenca Google Ads Analyzer e Content Creator (spenti). Allinearla insieme alla landing nuova. Da /pricing ora ci si arriva dal badge crediti e dal toast "Crediti insufficienti" (fix c380a5d).

### Punto 4. Post LinkedIn

**REGOLA (Clemente, 2026-10-09 sera, arrabbiato):** il post NON ha scopo commerciale. Niente "prova gratis",
niente crediti, niente elenco di funzioni da brochure. Serve a lui: fa gioco nelle nuove candidature e nel cercare
lavoro, e per lui "è come far nascere un figlio". Voce in prima persona, storia personale, il prodotto è il figlio,
non la merce. Il link ci sta solo come "se vi va di vederlo". La v0 qui sotto è BOCCIATA per questo motivo.

**Decisione finale 2026-10-09: il post si scrive ALLA FINE di tutto (dopo test e landing), da zero, con Clemente. Le bozze qui sotto non contano: restano solo come memoria di cosa NON fare.**

**Bozza v1 (scartata, da rifare alla fine):**

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
- [x] **GEO Audit (ex AI Reputation Radar), punto 2.2:** fatto 2026-10-13 (rinomina, prova su Stabia, dubbi delle AI, prezzo unico, diagnosi tecnica: ADR-015/016 del modulo).
- [x] **Dashboard di ogni modulo: "Attiva su un progetto esistente" accanto a "Crea progetto"** — FATTO 2026-10-13 sera: componente `shared/views/components/module-activate-existing.php` nei 5 moduli accesi (elenca solo i progetti dove il modulo, o un tipo, non è ancora attivo; per i moduli tipizzati sceglie il tipo). Richiesta originale (Clemente 2026-10-13, prima del post): oggi "Nuovo progetto" porta sempre a `/projects/create`; serve anche un selettore dei progetti globali dell'utente che attiva il modulo lì (stesso flusso di POST `/projects/{id}/activate-module`, col modal dei tipi per i moduli tipizzati).
- [ ] **Conferme con la finestra dell'app, non con l'avviso del browser** (Clemente 2026-10-13, prima del post: "così lato UI/UX non si può vedere"). Esiste già `window.ainstein.confirm(msg, {buttonText, destructive})` in `shared/views/components/global-modals.php`. GEO Audit FATTO (helper `arConfirm` + `data-confirm` sui form, in `modules/ai-reputation/views/partials/project-nav.php`). FATTO anche per il resto (2026-10-13 sera): helper globale `window.ainstein.ask()` + form `data-confirm` in `global-modals.php`; sostituiti i `confirm()` di progetti (elimina progetto, membri, inviti, sito WP, scollega WP), SEO Audit (annulla crawl), admin cache. AI Content aveva già le sue finestre. Restano solo nei moduli spenti/backup.
- [ ] **Check approfondito UI/UX tra i moduli** — GIRO FATTO 2026-10-13 sera, esiti e ordine di lavoro in `docs/2026-10-13-check-ui-ux-moduli-admin.md` (copre anche le aree admin). Correzioni da fare in un giro solo, poi il tour. (Clemente 2026-10-13, prima del post): coerenza grafica e di layout, funzionalità, copy e istruzioni, partendo dalle dashboard dei moduli. Metodo proposto: giro pagina per pagina dei 5 moduli accesi con screenshot, griglia di confronto (header, KPI, tabelle, bottoni, empty state, testi), poi correzioni in un solo giro. Si lega al tour guidato (punto sotto) e ad "Attiva su progetto esistente".
- [ ] **Check approfondito delle aree admin** (Clemente 2026-10-13, prima del post): ogni pagina `/admin/*` (dashboard, utenti, piani, moduli e loro impostazioni, impostazioni globali, template email, finance, AI logs, API logs, jobs, cache) allineata agli ultimi sviluppi (es. costi GEO Audit, judge_model, moduli spenti), funzionante, e valutare pulizia di voci inutili o nel posto sbagliato. Metodo: giro pagina per pagina con elenco "tieni / sposta / togli".
- [ ] **Tour guidato da rivedere per TUTTI i moduli** (segnato da Clemente 2026-10-13): `core/OnboardingService.php` + `shared/views/components/onboarding-spotlight.php`. Allinearlo ai moduli accesi e ai flussi di oggi (es. GEO Audit: "Chi stai monitorando?", Controllo tecnico, prezzo prima di "Avvia run").
- [ ] ainstein.it stesso: SEO Audit dà score 47/100 (3 critici, 64 warning). Sistemare i critici.
- [ ] Landing: togliere Content Creator, aggiungere GEO Audit, allineare ai 5 moduli accesi.
- [ ] Tailwind caricato da CDN (`cdn.tailwindcss.com`) in produzione: warning in console, lento. Build locale del CSS.
- [x] Email in produzione: benvenuto arrivata subito (registrazione Luca Test, 2026-10-12). Reset password non provato.
- [x] Verifiche a mano: "Avvia Scansione" + avanzamento live ok; "Avanti" dopo il brief ok; step "Pubblica" senza WordPress mostra "Configura sito" + "Torna alla dashboard" (tutto verificato 2026-10-12 con click veri).

### Crediti (ragionamento da fare con i numeri veri)
- **Principio di Clemente (2026-10-12): gratis tutto ciò che non gli costa niente di vivo, con un limite d'uso.** Candidati: SEO Audit (crawl dal server, 0 $), volumi keyword via Keyword Planner (0 $), rank check via Serper entro i 2.500/mese gratis, sync GSC. A pagamento (crediti): tutto ciò che chiama Claude/OpenAI (brief, articoli, clustering, report AI, copertine). Serve un limite per il gratuito (es. pagine/scansione e scansioni/giorno per il piano Free, keyword tracciate per progetto) perché Serper e il server non sono infiniti. Il costo per pagina dell'audit si imposta da admin (`cost_crawl_per_page`): con 0 la scansione passa senza controlli (fix 4f0a492).
- Costi API misurati: audit 100 pagine ≈ 0 $; articolo completo ≈ 0,25 $; keyword research ≈ 0,13 $; copertina 0,04 $.
- ~~In produzione il modulo AI Content ha ancora scraping 12 cr/URL e brief 5~~ → allineato da Clemente il 2026-10-12 a 1 e 3 (quello che le pagine promettono). Con 12/URL un utente nuovo non riusciva a fare nemmeno un articolo.
- **Decisione Clemente 2026-10-12: 100 crediti gratis ai nuovi iscritti, non 30** (`config/app.php` → `free_credits`, commit del 2026-10-12). Da aggiornare anche su /pricing e landing ("30 crediti/mese"). Con audit a costo 0 e AI Content allineato (3+1/url+3+10+3), 100 crediti = ~4 articoli completi o ~30 ricerche keyword.
- SEO Tracking: le pagine stimano i volumi a 0,5 cr/kw (0,3 da 10 kw) ma il server addebita 1 cr/kw; rank check 1 cr/kw (ora scalato davvero, fix 2026-10-12). Decidere la cifra e allineare stima e addebito insieme (rimandato da Clemente alla decisione finale sui crediti).

### UX da utente nuovo (non bloccanti)
- Keyword Research: brief e seed si perdono dopo errore/reload; ricerca precedente in "Error" non riprendibile; gergo ("seed", "clustering", "intent"); docs dicono 5-10 seed, form max 5; stato "Collecting" in inglese.
- ~~AI Content: step "Pubblica" senza sito WordPress mostra solo "Indietro"; wizard non aggiorna dopo il brief~~ → verificato 2026-10-12: entrambi ok.
- SEO Tracking: tab in inglese (Landscape, Overview, Pages, Tags), doppio filtro "Tutte le posizioni".
- Dashboard utente: "Articoli da 3 crediti" (reali: 3+1/url+3+10+3), "Audit da 2 crediti" (gratis), manca la card AI Reputation Radar / GEO Audit.
- AI Content wizard: "Indietro" dallo step Articolo mostra il pannello "Genera articolo" invece dell'articolo esistente (l'articolo c'è, basta ricaricare).
- Modal "Attiva modulo" di Keyword Research: descrizioni in gergo, nessun "consigliato per iniziare".
- SEO Audit: lo stesso canonical viene segnalato due volte (warning Indicizzabilità + notice Tecnico).
- Console: una risorsa 404 nella pagina wizard AI Content (da individuare).

### Tecnico
- SEO Tracking: ricaricare DataForSEO o toglierlo dalla cascata; SerpAPI esaurito. Snapshot giornaliero trend anche per "non trovata" (oggi la keyword sparita dal top 100 non si vede nel grafico).
- Crawler: pagina pubblica del bot + IP fisso per le liste bianche degli hosting (come SemrushBot). SiteGround blocca Chrome 120/122/131 e i bot sconosciuti dal server; dopo molte richieste mette il captcha sull'IP.
- Link interni automatici (AI Content): 0 inseriti perché il WordPress collegato (SiteGround) ha risposto con la sfida anti-bot. Riprovare dal server e gestire il 202.
- AiService: quando il primario fallisce e scatta il fallback, il fallimento non viene loggato in ai_logs.
- config/app.php: modello di default `claude-sonnet-4-20250514` ritirato → mettere `claude-sonnet-5-5`; stesso per gli ambienti locali (Clemente usa opus-5-5 in prod).
- DataForSEO come riserva SERP automatica per AI Content/Keyword Research (oggi solo Serper → SerpAPI).
- gpt-image-1-mini in ritiro il 2026-12-01: la copertina usa gpt-image-2, ok; tenere d'occhio prezzi.
- Google Ads API: Google ritira una versione ogni ~3 mesi (v25 oggi). Controllare `docs/sunset-dates` ogni trimestre o lo stesso 404 tornerà.
- Architettura Sito e Piano Editoriale (Keyword Research) non testati.

## Dopo il post (non prima)
- **Idea di Clemente (2026-10-13): "Recap SEO" GA4 + GSC con lettura AI.** Collegamento API a GA4 e Search Console, metriche SEO unite (click e impressioni GSC accanto a sessioni organiche, conversioni e ricavi GA4 per pagina/periodo) e l'AI che spiega l'andamento. Modello: il "Recap SEO" di Amevista Analysis, https://seo.amevista.com/reports/recap.php?metric=revenue&period_preset=last_quarter&include_extended=1 (Clemente autorizza a guardarlo). Decisione di Clemente: prima si chiude il GEO Audit, poi si valuta se svilupparlo PRIMA di andare online col post. Può essere il rifacimento del report settimanale qui sotto. Prima di costruire: verificare online i concorrenti (Databox, AgencyAnalytics, Looker Studio con AI, SEOTesting): Clemente pensa che nessuno lo faccia, va controllato.
- **Report settimanale AI di SEO Tracking, da rifare (strada B, 3-4 h, decisa la direzione 2026-10-13):** attivabile per progetto (non interruttore unico admin) con prezzo mostrato prima; funziona anche senza Search Console (posizioni tracciate: salite/scese, entrate/uscite top 10, lista Quick Wins della settimana + breve commento AI); consegna con notifica + email il lunedì; link alla pagina report nelle schede del modulo; addebito con `charge_credits=false` + `Credits::consume('weekly_digest')` al proprietario; `max_tokens` adeguato a Opus 5.5 (1500 tronca). Oggi la route `/reports/create` non esiste (la cattura `/reports/{reportId}`).
- **AI Content, brief "alla scheda" (richiesta Clemente 2026-10-12):** rifare il brief come il "Genera scheda" di AI Reputation: una sola chiamata AI con dossier in ingresso (SERP, testi fonti, PAA, conteggi), JSON a schema fisso validato (intent, 4 titoli, scaletta H2/H3, termini, lunghezza, gap, angoli, FAQ), effort alto. Oggi il brief è metà statistico (intent/entità contate dal codice) e metà AI. Stima 2-3 h + test perché l'articolo si appoggia al brief. Intanto fatta la pulizia (2026-10-12): entità senza spazzatura HTML, termini e lunghezza suggeriti dall'AI.
- AI Optimizer e SEO On-Page: completare e accendere
- Google Ads Analyzer: test OAuth e riaccensione
- Email in produzione, onboarding, miglioramenti da feedback
