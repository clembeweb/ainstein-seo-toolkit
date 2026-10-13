# GEO Audit — Diagnosi tecnica (le cause dei dubbi delle AI)

> 2026-10-13 · modulo `ai-reputation` · decisione di Claude su delega di Clemente ("se ti convince procediamo")

## Perché

Il controllo tecnico GEO da solo è merce comune (attori Apify, estensioni Chrome, toolkit open source: robots.txt,
llms.txt, schema, punteggio 0-100). Nessuno lo collega a quello che le AI dicono davvero. Noi sì: il run sa già che
ChatGPT "non trova la P.IVA di Stabia". La diagnosi tecnica va sul sito e dice **perché**:

- il dato **non c'è** (contenuto mancante → scheda clarify con il testo pronto);
- il dato c'è nel testo ma **non nei dati strutturati** (→ JSON-LD);
- il dato c'è ma **le AI non entrano** (crawler bloccato dal server/CDN, non solo da robots.txt).

Test empirico 2026-10-13 dal server di produzione (UA dei crawler): corriere.it risponde 403 a GPTBot e ClaudeBot e
200 a OAI-SearchBot/PerplexityBot (blocco invisibile dal solo robots.txt); nytimes.com risponde 403 anche al browser
dal nostro IP → "non verificabile", mai "bloccato". Stabia: tutti 200; LocalBusiness senza legalName/vatID/via;
nessuna P.IVA nel testo; due telefoni diversi in home.

## Cosa controlla (nessuna chiamata AI: gratis)

1. **Accesso crawler AI** — robots.txt (gruppi user-agent, `Disallow: /`) + richiesta vera della home con lo UA di:
   GPTBot, OAI-SearchBot, ChatGPT-User (OpenAI), ClaudeBot, Claude-SearchBot (Anthropic), PerplexityBot,
   Google-Extended (solo robots: è un token, non un crawler), Bingbot (ChatGPT search usa Bing).
   Verdetto per bot: `ok` · `blocked_robots` · `blocked_server` (403/429/challenge con il bot, 200 col browser) ·
   `unverifiable` (anche il browser non entra dal nostro server).
2. **Leggibilità** — parole nel testo della home senza JavaScript (< 100 → "il testo arriva solo con JavaScript").
3. **Chi sei per le macchine** — JSON-LD della home + pagine Chi siamo / Contatti (trovate dai link): tipo, name,
   legalName, vatID/taxID, address (via, CAP, città), telephone, sameAs.
4. **Fatti visibili** — nel testo di home, Chi siamo, Contatti: P.IVA (11 cifre vicino a "IVA"), via, telefoni
   distinti, link a Google Maps / TripAdvisor / Trustpilot / Facebook.
5. **File** — sitemap (da robots.txt o /sitemap.xml, /sitemap_index.xml, /wp-sitemap.xml), llms.txt (valido solo se
   testo e non HTML; peso basso: Google dice che non serve, quasi nessun crawler lo legge).

## Dove si vede

- **Fine run**: la diagnosi gira prima del piano (10-20 s), si salva col run (`ar_tech_checks.run_id`), il piano la
  usa: la motivazione dell'intervento `clarify` aggiunge per ogni tema "Sul sito oggi: …"; il dossier della scheda la
  riceve (rimedi precisi: "aggiungi vatID al LocalBusiness che hai già"). Un crawler bloccato diventa il primo
  avviso del piano.
- **Pagina "Controllo tecnico"** (nuova scheda del progetto): ultimo esito + "Esegui controllo" (gratis, anche senza
  run). È la porta d'ingresso: si vede subito cosa non va, poi il GEO Audit a crediti dice cosa ne pensano le AI.

## Dati

`ar_tech_checks` (id, project_id, run_id NULL, url, result JSON, created_at). Nessun dato personale oltre a quello
pubblico del sito. Le richieste rispettano il controllo SSRF esistente (`ProjectController::isPublicWebUrl`).

## Fuori da questo passo

Verifica "prima/dopo" (rilancio delle sole domande coinvolte da un intervento): passo successivo. Indicizzazione Bing
(serve API/Webmaster Tools). Controllo per tutte le pagine del sito (qui: home + 2 pagine).
