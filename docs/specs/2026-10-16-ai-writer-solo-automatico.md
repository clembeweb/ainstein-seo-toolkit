# AI Writer solo automatico — proposta

> 2026-10-16. Stato: **proposta, niente codice finché Clemente non decide.**
> Origine: giro manuale di Clemente in prod il 16/10 (articolo troncato su Madcrumbs, corretto in `f95f6a8`) e sua idea:
> "togliere la modalità manuale, lasciare solo l'automatica, togliere i meta tag (li genera il generatore automatico),
> briefing più intelligente con l'AI, contenuti SEO e GEO oriented, link interni messi dall'AI in generazione".

## 1. Cosa funziona oggi (e si tiene)

- **Il flusso dal Piano editoriale.** AI Keywords → "Invia ad AI Writer" (`keyword-research/EditorialController::sendToContent`)
  mette le keyword in `aic_queue` con la data del mese del piano; il cron (`cron/dispatcher.php`, ogni minuto) ne fa una per
  progetto alla volta: Google → lettura delle prime 3 pagine → brief → articolo → copertina → bozza su WordPress.
  `PlanContextService` passa già all'articolo il cliente, l'idea di titolo, le note, il "cosa manca su Google" e le pagine da linkare.
- **Copertina, editor, anteprima, pubblicazione su WordPress, notifiche, crediti al proprietario del progetto.**
- **Il generatore**, ora che gli articoli lunghi non vengono più tagliati (`f95f6a8`, provato con il prompt di Madcrumbs: completo in 115 s).

Cosa non va:
- Il **brief** guarda solo titoli e H2 dei concorrenti: niente profilo dell'attività, niente piano editoriale (arriva dopo),
  niente GEO (niente risposte dirette, niente dati citabili, niente FAQ strutturate). La lunghezza è "media dei concorrenti × 1,1".
- I **link interni** sono una fase a parte: si importa la sitemap, si paga 0,1 crediti a pagina per leggere i titoli,
  e poi all'AI arriva una lista di 50-80 pagine (le più recenti, non le più pertinenti) da cui "sceglie 2-4 link".
- **Tre tipi di progetto** (manuale, automatico, meta tag) per un solo lavoro: ~13.700 righe di codice, tre menu, tre landing.
- In WordPress la **copertina non arriva** (resta solo in Ainstein) e l'articolo va sempre in bozza senza categoria.

## 2. Cosa sparisce

| Cosa | Perché | Dove sta oggi |
|---|---|---|
| Tipo **Manuale** (wizard in 4 passi) | Fa gli stessi passi dell'automatico ma chiede all'utente scelte (fonti, H2) che il nuovo brief fa meglio. Per un articolo singolo resta "Scrivi adesso" nell'automatico. | `WizardController`, `KeywordController`, `SerpController`, `DashboardController`, `views/keywords/*`, `views/dashboard.php` (~3.500 righe) |
| Tipo **Meta tag** | Title e meta description li scrive il generatore con ogni articolo. Riscrivere i meta delle pagine **già esistenti** di un sito è un altro lavoro (ottimizzazione on-page): se servirà, rinasce in AI Audit. | `MetaTagController`, `MetaTag`, `ScrapeJob`, `views/meta-tags/*` (~5.300 righe) |
| Fase **Link interni** a parte | La mappa del sito si costruisce da sola (punto 3) e i link li sceglie il brief. | `InternalLinksController`, `aic_internal_links_pool`, `views/internal-links/*` |
| Codice morto | `cron/process_queue.php`, `ContentScraperService`, colonne non usate di `aic_auto_config` (articles_per_day, publish_times, …). | |

I dati nel DB non si cancellano: si smette di mostrarli.

## 3. Come diventa

Un solo tipo di progetto, **"Articoli"**. Le keyword entrano in coda in tre modi: dal Piano editoriale (principale),
incollando una lista, oppure con **"Scrivi adesso"** (una keyword, parte subito: è il vecchio "Processa ora").

Per ogni keyword il cron fa:

0. **Contesto del progetto** (una volta, poi si aggiorna ogni 7 giorni; 0 crediti, nessuna AI):
   - **profilo dell'attività**: `projects.description` (quello di AI Keywords / GEO, `BusinessProfileService::getProfile`)
     più i fatti confermati del GEO Audit (`ar_profile_facts`) se c'è un progetto GEO;
   - **mappa del sito** (nuova tabella `aic_site_pages`: url, titolo, descrizione, tipo pagina/articolo), dalla prima fonte
     disponibile: WordPress collegato (`/seo-toolkit/v1/all-content`, tutte le pagine con titolo in una chiamata) →
     ultima scansione di AI Audit (`sa_pages`) → sitemap del sito (titoli letti col solito `ScraperService`, max 150 pagine).
1. **Google**: primi 10 risultati + "Le persone chiedono anche" (come oggi).
2. **Lettura delle prime 3 pagine** (come oggi).
3. **Briefing AI (nuovo)**, una chiamata che vede tutto insieme: keyword, intento, profilo dell'attività, riga del piano
   editoriale, risultati Google, domande, struttura e testo delle 3 pagine lette, mappa del sito. Restituisce:
   - angolo dell'articolo (cosa promette, cosa manca ai concorrenti), **title SEO e meta description**;
   - **lunghezza giusta per l'intento** (non "media dei concorrenti"), da 800 a 2.500 parole;
   - **scaletta SEO + GEO**: ogni H2 è una domanda o un sottotema vero, aperto da una **risposta diretta di 1-2 frasi**
     (quella che le AI citano); per ogni sezione i **dati e fatti** da mettere (numeri, definizioni, passaggi);
   - **FAQ** (3-5, dalle domande vere di Google);
   - **link interni scelti**: 3-5 pagine della mappa del sito, con anchor e sezione in cui metterli, più le pagine
     obbligatorie del piano; invito finale verso l'attività del cliente (mai verso concorrenti o portali).
4. **Articolo**: scrive seguendo la scaletta e i link già decisi (prompt più corto e lunghezze più prevedibili).
   Poi, da codice e non dall'AI:
   - ogni link viene **controllato** contro la mappa del sito: quelli inventati si tolgono;
   - dalle FAQ si genera il **JSON-LD FAQPage** (e Article) in fondo all'articolo: dati strutturati sempre validi.
5. **Copertina** (come oggi).
6. **WordPress**: copertina caricata come **immagine in evidenza** (il plugin lo sa già fare: `/media` + `featured_image_id`),
   meta description in Yoast/RankMath (già), stato bozza o pubblicato come da impostazioni.

Nella pagina dell'articolo un riquadro **"Perché è scritto così"** mostra il brief (angolo, scaletta, link scelti): si legge,
non si deve approvare. Chi vuole cambiare usa l'editor o "Rigenera con istruzioni" (c'è già).

## 4. Progetti esistenti (prod, 16/10)

5 progetti in tutto: 2 manuali (Madcrumbs, Stabia Boat Rental), 2 automatici (Madcrumbs, admin), 1 meta tag (admin, 22 righe).
- **Manuali → automatici**: Madcrumbs ha già entrambi nello stesso progetto globale → articoli e keyword del manuale
  (ID 19) passano all'automatico (ID 18). Stabia: il progetto cambia tipo e basta. Gli articoli restano tutti.
- **Meta tag** (solo admin): non più creabile e non più mostrato; righe lasciate nel DB.
- Migrazione con uno script SQL in `modules/ai-content/database/migrations/`, provato prima in locale.

## 5. Costi (crediti) e tempi

| Passo | Oggi automatico | Nuovo |
|---|---|---|
| Google | 0 (il manuale ne paga 3) | 0 |
| Lettura 3 pagine | 3 | 3 |
| Brief | 3 | 3 |
| Articolo (con title, meta, link, FAQ, dati strutturati) | 10 | 10 |
| Copertina | 3 | 3 |
| Mappa del sito | 0,1 a pagina (fase a parte) | 0 |
| **Totale articolo** | **19** | **19** |

(Valori di prod verificati il 16/10, uguali ai default di `module.json`; il manuale oggi costa 22 perché paga anche Google.)
Prezzo per l'utente: **"1 articolo = 19 crediti, copertina compresa"** (16 senza copertina): niente da cambiare nei listini
dei piani. Costo AI reale (Opus 5.5, misurato il 16/10): brief ~0,10 $, articolo ~0,27 $, copertina a parte.
Il brief nuovo legge più testo (pagine dei concorrenti + mappa del sito): ~0,05 $ in più per articolo.

Tempi: ~3-4 minuti per articolo nel cron (Google 5 s, pagine 15 s, brief 40-60 s, articolo ~110 s, copertina 20 s).
L'utente non aspetta: arriva la notifica.

## 6. Rischi

- **Link sbagliati**: l'AI potrebbe scegliere una pagina poco pertinente → massimo 5, controllo da codice sulla mappa,
  riquadro "Perché è scritto così" per vederli.
- **Sito senza WordPress, senza AI Audit e senza sitemap** → niente link interni: l'articolo esce lo stesso, con un avviso
  "collega WordPress o fai una scansione con AI Audit per avere i link interni".
- **Chi voleva scegliere fonti e H2 a mano** perde il wizard. Mitigazione: brief visibile + editor + rigenera.
  In prod oggi lo usa solo un utente esterno (Stabia), con 1 articolo.
- **Testi da aggiornare** (Golden Rule 18): landing del modulo, tour, docs utente, home, modal "Attiva modulo",
  `GlobalProject::MODULE_TYPES`. La sessione UX tocca `views/dashboard.php` ed `entry-dashboard.php` e il menu:
  quei file si toccano dopo, d'accordo con lei.

## 7. Lavoro, a tappe piccole (una alla volta, ognuna provata e committata)

| # | Tappa | Stima |
|---|---|---|
| 1 | Mappa del sito del progetto (`aic_site_pages`, fonti WordPress → AI Audit → sitemap) | 2 h |
| 2 | Briefing AI nuovo (profilo + piano + concorrenti + mappa → JSON), provato su 3 keyword vere | 3 h |
| 3 | Generatore che segue la scaletta, link controllati, JSON-LD FAQ/Article | 2 h |
| 4 | Riquadro "Perché è scritto così" nell'articolo | 1 h |
| 5 | "Scrivi adesso" (una keyword) nella coda | 1 h |
| 6 | WordPress: copertina in evidenza | 1 h |
| 7 | Togliere manuale e meta tag (route, menu, tipi, landing, tour, docs, home) + migrazione dei progetti | 3 h |
| 8 | Prova in prod su Madcrumbs e Stabia con Clemente | 1 h |

Totale ~14 ore, circa due giornate di lavoro.

## 8. Da decidere (una alla volta)

1. Il disegno va bene così (punti 2 e 3)?
2. Meta tag delle pagine esistenti: spariscono del tutto adesso (proposta), da rifare semmai in AI Audit.
3. Prezzo: 19 crediti per articolo, copertina compresa (proposta: invariato).
