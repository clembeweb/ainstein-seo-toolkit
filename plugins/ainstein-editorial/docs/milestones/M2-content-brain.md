# M2 — Content Brain + Onboarding

> ⚠️ **UI/UX via Claude Design (ADR-025)**: le task UI (M2.4 onboarding, M2.5 pagina Content Brain)
> sono **bloccate** finché non ci sono i mockup approvati da Claude Design (brief in `docs/design-brief-mockups.md`).
> Il backend (M2.1 ContentBrainService, M2.2 endpoint/SSE, M2.3 proxy) è indipendente e implementabile.
> M2.6 (design system Tailwind) è già fatto ma **provvisorio**: verrà sostituito dall'output dei mockup.
>
> **Status**: 📋 Spec scritta, attende approvazione cliente
> **Effort stimato**: 25-35 ore (~1 settimana full-time)
> **Dipendenze**: M1 completata (plugin attivabile + autenticato col backend)
> **Sblocca**: M3 (Article Generation) — gli articoli usano il Content Brain come contesto

---

## 1. Overview

### Cosa costruiamo in M2
Il primo pezzo di prodotto **visibile e utile** per l'utente:
- **Onboarding wizard** a 5 step che parte al primo accesso post-attivazione
- **Content Brain**: il backend "legge" gli articoli esistenti del sito e ne estrae tono, vocabolario, struttura (brand voice scanner via AI)
- **SSE "WOW moment"**: durante lo scan, una progress bar mostra "Sto leggendo i tuoi articoli…" in tempo reale
- **Pagina Content Brain** per rivedere/modificare a mano i dati appresi (tono, glossario, editorial guidelines)
- Le **basi UX riusabili** (design system Tailwind + Heroicons) su cui poggeranno M3-M6

A fine M2 l'utente attivato completa l'onboarding e il plugin "ha imparato" il suo sito. Non genera ancora articoli (M3), ma il differenziatore core (Content Brain, Golden Rule #8) è in piedi.

### Perché ora
Ogni generazione di M3 inietta il Content Brain nei prompt AI per output coerente con la voce del brand. Senza M2, M3 produrrebbe articoli generici. Inoltre l'onboarding è il primo contatto reale dell'utente col prodotto: ne determina la prima impressione.

### Cosa M2 NON include
- ❌ Generazione articoli (M3)
- ❌ Keyword research / piano editoriale (M4)
- ❌ Auto-pilot / cron (M4)
- ❌ Internal linking (M5)
- ❌ Email notifiche (M6)
- ❌ Re-scan automatico ogni 6 mesi (post-MVP; M2 fa solo scan manuale on-demand)

---

## 2. Spec tecnica

### 2.1 Riuso servizi Ainstein (Golden Rule #1)

Il backend editorial gira in uno stack separato ma, essendo incluso da `public/index.php` **dopo** l'autoloader principale, può istanziare i servizi Ainstein esistenti senza duplicazione:

| Servizio Ainstein | Uso in M2 |
|-------------------|-----------|
| `Services\AiService` | Analisi brand voice (`new AiService('editorial')`) |
| `Services\ScraperService` | Fetch articoli esistenti (`scrape($url)` → title/content/headings/word_count) |
| `Services\SitemapService` | Discovery URL articoli se l'utente non passa una lista |
| `Core\Database` | Persistenza `aied_content_brain` |

⚠️ **Reconnect dopo AI** (Golden Rule #10 Ainstein): `Database::reconnect()` dopo ogni chiamata `AiService` lunga, prima di salvare.

### 2.2 `ContentBrainService` (backend)

File: `api/editorial/Services/ContentBrainService.php`

```php
class ContentBrainService {
    // Discovery: se non arrivano article_urls, prova sitemap + WP REST del sito
    public function resolveArticleUrls(array $site, ?array $urls, int $sampleSize): array;

    // Scan completo (usato dall'SSE handler, uno step per volta per emettere progress)
    public function scrapeArticle(string $url): array;          // via ScraperService
    public function analyzeVoice(array $scrapedArticles, string $topic): array; // via AiService
    public function save(int $siteId, array $analysis, array $manualInputs): void;

    // Read/update per la pagina di editing
    public function get(int $siteId): ?array;
    public function update(int $siteId, array $fields): void;
}
```

**Prompt brand voice**: come da `design.md` §8.1 — output JSON `{ tone, sentence_style, voice_traits, vocabulary, structure, brand_names, examples }`. Salvato in `aied_content_brain.brand_voice_summary` (testo riassuntivo) + `brand_voice_examples` (JSON) + `tone` (enum) + `glossary` (auto-suggestion brand_names/products) .

**Costo**: registrato in `aied_actions_log` con `action_type='keyword_research'`? No — serve un tipo dedicato. **Decisione**: lo scan Content Brain costa **0 azioni** in M2 (è onboarding, non deve scoraggiare). Logghiamo comunque la chiamata AI in `aied_api_logs` per tracking costi.

### 2.3 Endpoint API (3 nuovi, sostituiscono gli stub M2)

| Metodo | Path | Auth | Descrizione |
|--------|------|------|-------------|
| `GET` | `/content-brain` | LicenseAuth | Ritorna i dati salvati per il sito |
| `POST` | `/content-brain/scan` | LicenseAuth | Avvia lo scan; **SSE stream** |
| `PUT` | `/content-brain` | LicenseAuth | Salva editing manuale (tono, audience, topic, glossary, guidelines) |

**SSE** (`POST /content-brain/scan`): il `Router` attuale è sincrono e i controller chiudono con `Response::json()`. Per l'SSE serve un percorso che **non** emetta JSON ma uno stream `text/event-stream`. Aggiungiamo a `BaseController` un metodo `sse(callable $producer)` e al plugin un consumer `EventSource`. Pattern e regole critiche da `seo-tracking/RankCheckController` (vedi CLAUDE.md Ainstein):
- `ignore_user_abort(true)` + `set_time_limit(300)` + `session_write_close()`
- `Database::reconnect()` dopo ogni chiamata AI
- `if (ob_get_level()) ob_flush(); flush();` per inviare eventi
- Salvare i risultati nel DB **prima** dell'evento `completed`

Eventi SSE emessi: `started`, `scanning` (con `progress: i/N` per articolo letto), `analyzing`, `item_error`, `completed` (payload finale: summary + tone + examples + glossary).

> Nota architetturale: l'SSE bypassa l'helper `Response::json`. Va gestito un `RateLimit` più permissivo (lo scan è una sola richiesta lunga). Decisione implementativa da loggare in `decisions.md` durante M2.

#### 2.3bis Transport SSE — vincolo critico (esito analisi)

**Problema**: il browser non può collegare un `EventSource` direttamente al backend con l'`X-Api-Token` in header (EventSource fa solo `GET` senza header custom). E il proxy WP **non può** usare `ApiClient` (`wp_remote_*`) per fare passthrough dello stream: `wp_remote_*` **bufferizza** l'intera risposta → niente streaming, l'effetto "WOW" sparisce.

**Soluzione adottata** (proxy cURL streaming, token server-side):
- Endpoint plugin dedicato (non `admin-ajax` standard, ma un handler che fa flush): apre una connessione cURL verso il backend SSE con `CURLOPT_WRITEFUNCTION` che fa `echo $chunk; flush();` per ogni chunk ricevuto.
- Header di auth (`X-Api-Token`, `X-License-Key`, `X-Site-Domain`) iniettati lato server dal proxy → **mai nel browser**.
- Il proxy disabilita il buffering (`ignore_user_abort(true)`, `@ini_set('output_buffering','0')`, `header('X-Accel-Buffering: no')` per nginx).
- Lato JS: `EventSource('<admin-ajax-url>?action=aied_cb_scan_stream&_wpnonce=...')`.

**Fallback** se lo streaming cURL non funziona sull'hosting WP dell'utente (alcuni FastCGI bufferizzano): polling `GET /content-brain` + un endpoint `GET /content-brain/scan/status` con stato/percentuale, interrogato ogni 2s. La progress bar resta, perde solo la fluidità per-token.

**Alternativa scartata**: EventSource diretto browser→backend con token JWT in query string. Più semplice ma espone il token nell'URL (log server, history) e richiede CORS. Riconsiderabile solo se il proxy cURL si rivela problematico in produzione.

### 2.4 Plugin — Onboarding wizard

File: `src/Admin/Pages/Onboarding.php` + JS Alpine in `assets/js/onboarding.js`

- Mostrato **una sola volta** dopo l'attivazione, se `aied_onboarding_completed` (wp_option) è falso. `AdminPages` redirige lì invece che a License quando attivo ma onboarding non fatto.
- 5 step (design §7.2):
  1. Topic sito (textarea; auto-suggestion popolata da una prima chiamata leggera o lasciata vuota in M2)
  2. Audience (textarea)
  3. Tono (5 card visive: Professionale/Amichevole/Divertente/Autorevole/Casual)
  4. Articoli/mese (slider 1-30, con hint quota del tier corrente da `aied_tier`)
  5. Cosa NON pubblicare (textarea → confluisce in `editorial_guidelines.dont`)
- Bottone **"Inizia magia"** → salva gli input (PUT /content-brain con i campi manuali) e lancia `POST /content-brain/scan` consumando l'SSE con progress bar.
- A scan completato: `aied_onboarding_completed = true` + schermata "Fatto! Ecco cosa ho imparato sul tuo sito" con riepilogo.

Stato wizard gestito client-side con Alpine.js; submit via `fetch` agli endpoint backend attraverso un piccolo proxy AJAX WP (`wp_ajax_aied_*`) che inoltra con `ApiClient` (così la license/token restano server-side, mai esposti nel browser).

### 2.5 Plugin — Pagina "Content Brain"

File: `src/Admin/Pages/ContentBrain.php`

- Submenu sotto "Ainstein Editorial".
- `GET /content-brain` → mostra tono (editabile), audience, topic, glossario (liste editabili: brand_names, products, avoid_terms, preferred_terms), editorial guidelines (do/dont/emphasize), data ultimo scan + n. articoli analizzati.
- Bottone "Ri-analizza il sito" → rilancia lo scan SSE.
- Salvataggio → `PUT /content-brain` via proxy AJAX.

### 2.6 Design system (UX polish, prime basi)

- **Tailwind compilato**: introdurre toolchain in `plugins/ainstein-editorial/` (package.json + tailwind.config.js + input.css) e step in `build.sh` (`npm ci && npx tailwindcss -o assets/css/app.css --minify`). Output sostituisce il CSS minimale M1.
- **Heroicons SVG** inline (Golden Rule #6): sprite/partial PHP riusabile.
- Componenti riusabili (classi): `.aied-card`, `.aied-btn-primary`, `.aied-input`, `.aied-modal`, `.aied-progress`.
- Dark mode non obbligatoria in admin WP (M2): rimandata se non banale.

---

## 3. Out of scope M2

- Generazione articoli, keyword research, piano editoriale, auto-pilot, internal linking, email (M3-M6).
- Re-scan automatico schedulato (post-MVP).
- Editing inline ricco del glossario con drag-drop (M2: liste semplici add/remove).
- i18n .po/.mo (M5).
- Multi-lingua del Content Brain oltre IT (campo `language` esiste già, ma UI solo IT).

---

## 4. Piano di lavoro

### M2.1 — `ContentBrainService` + prompt brand voice
**Effort**: 5-7h · **Dipendenze**: M1 · **Deliverable**: servizio backend + prompt testato

- [ ] M2.1.a Creare `api/editorial/Services/ContentBrainService.php` (resolveArticleUrls, scrapeArticle, analyzeVoice, save, get, update)
- [ ] M2.1.b Integrare `ScraperService` per il fetch articoli + `SitemapService` per discovery URL
- [ ] M2.1.c Prompt brand voice (design §8.1) + parsing JSON robusto (gestire output AI malformato)
- [ ] M2.1.d `Database::reconnect()` dopo la chiamata AI, poi `save()` in `aied_content_brain`
- [ ] M2.1.e Log chiamata AI in `aied_api_logs` (provider, durata, costo stimato)

### M2.2 — Endpoint Content Brain + SSE
**Effort**: 5-7h · **Dipendenze**: M2.1 · **Deliverable**: 3 endpoint funzionanti, scan via SSE

- [ ] M2.2.a Aggiungere `BaseController::sse(callable $producer)` (header text/event-stream, ignore_user_abort, flush helper)
- [ ] M2.2.b Implementare `ContentBrainController::get/scan/update`, sostituendo gli stub M2 in `routes.php`
- [ ] M2.2.c Scan SSE: eventi started/scanning(i/N)/analyzing/completed; salvataggio DB prima di `completed`
- [ ] M2.2.d Gestire RateLimit/timeout per la richiesta SSE lunga
- [ ] M2.2.e Test curl: `GET /content-brain` (vuoto → 200 con null fields), `PUT` (salva), `scan` (stream eventi)

### M2.3 — Proxy AJAX plugin + ApiClient SSE
**Effort**: 3-4h · **Dipendenze**: M1.6 ApiClient · **Deliverable**: handler wp_ajax che inoltrano al backend

- [ ] M2.3.a Handler `wp_ajax_aied_content_brain_get/update` (nonce + capability, inoltro via ApiClient)
- [ ] M2.3.b Proxy SSE **streaming** via cURL `CURLOPT_WRITEFUNCTION` (NON `ApiClient`/wp_remote — bufferizza), token iniettato server-side, no-buffering headers (vedi §2.3bis)
- [ ] M2.3.c Fallback polling `GET /content-brain/scan/status` se lo streaming non passa l'hosting
- [ ] M2.3.d Gestione errori → messaggi italiani user-friendly nel frontend

### M2.4 — Onboarding wizard (UI)
**Effort**: 6-8h · **Dipendenze**: M2.3 · **Deliverable**: wizard 5 step funzionante con SSE progress

- [ ] M2.4.a `Admin/Pages/Onboarding.php` + redirect logic in `AdminPages` (option `aied_onboarding_completed`)
- [ ] M2.4.b `assets/js/onboarding.js` (Alpine.js): stato step, validazione, slider con hint quota tier
- [ ] M2.4.c 5 card tono con Heroicons + esempi
- [ ] M2.4.d "Inizia magia" → PUT campi manuali + EventSource scan + progress bar visuale
- [ ] M2.4.e Schermata finale "Ecco cosa ho imparato" + set `aied_onboarding_completed`

### M2.5 — Pagina Content Brain (editing)
**Effort**: 4-5h · **Dipendenze**: M2.3 · **Deliverable**: pagina view/edit dei dati appresi

- [ ] M2.5.a `Admin/Pages/ContentBrain.php` + submenu
- [ ] M2.5.b Form editabile: tono, audience, topic, glossario (liste add/remove), guidelines (do/dont/emphasize)
- [ ] M2.5.c Bottone "Ri-analizza il sito" (rilancia scan SSE)
- [ ] M2.5.d Salvataggio via proxy AJAX → PUT /content-brain

### M2.6 — Design system Tailwind + build
**Effort**: 3-4h · **Dipendenze**: nessuna (parallelo) · **Deliverable**: Tailwind compilato + componenti

- [x] M2.6.a package.json + tailwind.config.js + assets/src/input.css
- [x] M2.6.b Componenti `.aied-*` + partial Heroicons
- [x] M2.6.c Step Tailwind in `build.sh` (npm ci + build minify) con fallback se npm assente
- [x] M2.6.d Sostituire CSS minimale M1 in pagina License con il design system

### M2.7 — QA su siti reali
**Effort**: 2-3h · **Dipendenze**: tutti i precedenti · **Deliverable**: report test

- [ ] M2.7.a Scan brand voice su 3 siti italiani diversi (es. food, tech, servizi) → output sensato
- [ ] M2.7.b Onboarding completo end-to-end con persona non-tech
- [ ] M2.7.c Verifica dati editabili e persistiti correttamente
- [ ] M2.7.d Report in `M2-content-brain-test-report.md`

---

## 5. Definition of Done M2

- ✅ Utente attivato vede l'onboarding al primo accesso (una sola volta)
- ✅ Scan brand voice funziona su sito reale (test su 3 siti italiani)
- ✅ SSE mostra progress fluido senza lag percepibile; nessun JSON corrotto
- ✅ Dati Content Brain visualizzati e editabili, persistiti in `aied_content_brain`
- ✅ License/api_token mai esposti nel browser (sempre via proxy AJAX server-side)
- ✅ Tailwind compilato + Heroicons; build.sh produce zip con CSS app
- ✅ `decisions.md` aggiornato (SSE nel router, costo scan = 0 azioni)
- ✅ Commit atomici per task M2.x; roadmap + spec aggiornate

---

## 6. Risk register M2

| Rischio | Prob. | Impatto | Mitigazione |
|---------|-------|---------|-------------|
| Output AI brand voice malformato (JSON) | Media | Medio | Parsing difensivo + retry 1x + fallback a tono default 'friendly' |
| Sito senza articoli pubblicati (nuovo blog) | Media | Medio | Skip scan, usa solo input manuali onboarding; messaggio "Analizzeremo quando avrai articoli" |
| SSE bloccato da proxy/hosting WP utente | Media | Alto | Fallback polling `GET /content-brain/scan/status` se EventSource fallisce |
| ScraperService lento su molti URL | Media | Medio | Cap sampleSize a 20, timeout per articolo, progress per-item |
| Costi AI scan non sostenibili | Bassa | Medio | 1 sola chiamata bulk per scan, log costi in aied_api_logs, scan on-demand non automatico |
| Token license esposto via JS | Bassa | Alto | Proxy AJAX server-side obbligatorio, mai token nel DOM |

---

## 7. Test plan M2

### Unit/integration
- `ContentBrainServiceTest`: mock AiService/ScraperService, verifica parsing JSON + save/get/update
- SSE: test che gli eventi siano well-formed e che il DB sia salvato prima di `completed`

### Manuale
- Onboarding E2E su WP locale con sito che ha 20+ articoli
- Sito vuoto (0 articoli) → degradazione graziosa
- Editing Content Brain + persistenza

### Coverage target
60%+ su `ContentBrainService`. UI verificata manualmente + screenshot.

---

## 8. Effort totale stimato

| Task | Effort |
|------|--------|
| M2.1 ContentBrainService | 5-7h |
| M2.2 Endpoint + SSE | 5-7h |
| M2.3 Proxy AJAX | 3-4h |
| M2.4 Onboarding wizard | 6-8h |
| M2.5 Pagina Content Brain | 4-5h |
| M2.6 Design system | 3-4h |
| M2.7 QA siti reali | 2-3h |
| **TOTALE M2** | **28-38 ore** |

---

## 9. Cosa otteniamo a fine M2

Demo: installi e attivi il plugin → parte l'onboarding → rispondi a 5 domande → click "Inizia magia" → guardi la progress bar mentre l'AI legge i tuoi articoli → schermata "Ecco cosa ho imparato: tono Amichevole, parli spesso di X, eviti Y". Apri "Content Brain" e modifichi a mano. **Per la prima volta il prodotto fa qualcosa di visibilmente utile e differenziante.**

---

## 10. Approvazione

> ☐ APPROVATA — `[firma + data]`
> ☐ MODIFICHE RICHIESTE — `[lista modifiche]`

---

*Spec scritta: 2026-06-06 · Architetto: Claude · Dipende da: M1 (validazione live in corso)*
