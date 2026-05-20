# M2 — Content Brain + Onboarding

> **Status**: 📋 Spec scritta, attende approvazione cliente
> **Effort stimato**: 30-42 ore (~1-1.5 settimane full-time)
> **Dipendenze**: M1 Foundation chiusa (✅ 2026-05-20)
> **Sblocca**: M3 (Article Generation usa Content Brain come constraint AI), M4 (Editorial Plan usa topic/audience), M5 (Internal linking usa glossary)
> **Reference design**: `docs/design.md` §7.2 (onboarding flow), §8 (Content Brain spec), §4 `aied_content_brain` schema

---

## 1. Overview

### Cosa costruiamo in M2

Dare al sistema la **conoscenza del sito utente** — tono, audience, vocabolario, regole editoriali — prima che venga generato un singolo articolo. È il differenziatore vs i competitor "AI scrive di tutto": noi facciamo scrivere all'AI con la **voce specifica** del cliente.

In concreto:

- **Backend `ContentBrainService`**: scansiona N articoli esistenti del sito, manda batch a Claude, estrae brand voice (tono, sentence style, vocabolario, esempi), salva in `aied_content_brain`.
- **3 endpoint REST**: `POST /content-brain/scan` (SSE), `GET /content-brain`, `PUT /content-brain`.
- **Plugin admin: onboarding wizard 5-step** che cattura site_topic + audience + tone + posts_per_month + guidelines, poi triggera lo scan con feedback SSE in tempo reale.
- **Plugin admin: pagina Content Brain** per visualizzare risultato scan + editare manualmente glossario + guidelines + ri-scansionare.
- **Polish CSS**: Tailwind compilato, componenti riusabili (`.aied-card`, `.aied-btn-primary`, `.aied-input`, `.aied-progress`, `.aied-wizard-step`), Heroicons SVG sprite.

### Perché M2 (non M3 direttamente)

Senza Content Brain l'AI generation produce contenuti **generici**: stesso output che farebbero ChatGPT/Copilot consumer. Il valore di Ainstein Editorial = "scrive come te" → Content Brain è la fondazione tecnica di quella promessa. Skippare M2 e andare a M3 = lanciare un prodotto "yet another AI writer" indifferenziato.

### Cosa M2 NON include

- ❌ Generazione articoli AI (M3)
- ❌ Keyword research / topic suggestion (M4)
- ❌ Calendario editoriale + auto-pilot (M4)
- ❌ Internal linking automatico (M5)
- ❌ Email notifications (M6)
- ❌ Quota enforcement strict (passa azioni gratis in M2; enforcement in M3)
- ❌ Auto-rescan ogni 6 mesi (cron lo aggiungeremo in M6 polish; in M2 solo manual rescan)
- ❌ Compilazione frontend CSS in build pipeline complessa (ok anche file CSS statico in M2, ottimizzazione in M6)

L'utente che completa M2 vede: "Plugin attivato + onboarding completato + Content Brain popolato + pagina di editing". Non può ancora generare articoli (M3) ma il sistema "lo conosce". Demo dimostrabile: scan reale di un sito italiano + visualizzazione brand voice estratta.

---

## 2. Spec tecnica

### 2.1 Backend `ContentBrainService`

Posizione: `seo-toolkit/api/editorial/Services/ContentBrainService.php`.

**Pattern obbligatorio** (CLAUDE.md GR #1, #14, #18):
- Riusa `AiService` (singletono Ainstein, niente curl diretto)
- Riusa `ScraperService` (niente DOMDocument custom)
- Riusa `ApiLoggerService` per log chiamate AI/scraping
- `Database::reconnect()` dopo chiamate AI lunghe
- `ignore_user_abort(true)` + `set_time_limit(300)` per SSE flow

**API interna del servizio**:

```php
class ContentBrainService
{
    /**
     * Esegue scan completo per un sito. Streaming via callback (event SSE).
     *
     * @param int       $siteId       FK aied_sites.id
     * @param string[]  $articleUrls  URL da scansionare (subset; max ~30)
     * @param callable  $emit         function($event, $data): void  per eventi SSE
     * @return array{success: bool, brain: array, errors: array}
     */
    public function scan(int $siteId, array $articleUrls, callable $emit): array;

    /**
     * Lettura completa Content Brain di un sito.
     */
    public function get(int $siteId): ?array;

    /**
     * Update manuale fields editabili dall'utente.
     * Validazione: tone in enum, glossary/guidelines JSON shape.
     */
    public function update(int $siteId, array $patch): array;

    /**
     * Discovery URL dalla sitemap del sito (se presente) o dai wp_posts via WP REST API.
     * Fallback: ritorna [] se nessuna fonte affidabile.
     */
    public function discoverArticleUrls(string $siteUrl, int $maxCount = 30): array;
}
```

**Flow `scan()` interno**:

```
1. emit('started', { total: N, sample_size: min(N, 20) })
2. Per ogni URL del sample (max 20):
     a. ScraperService::scrape(url) → {title, content, headings, word_count}
        - emit('article_scraped', { url, title, word_count })
        - se fail: emit('article_error', { url, reason }); continue
3. emit('aggregating', { scraped_count })
4. Costruisce prompt bulk con tutti i 20 articoli concatenati (truncate content a ~500 char/articolo per stare nel budget Claude)
5. AiService->analyze(userId, $prompt, $articlesPayload, 'editorial-content-brain')
     - Modello: Sonnet 4.6 (sweet spot qualità/costo)
     - emit('analyzing', { status: 'AI sta leggendo i tuoi articoli...' })
6. Parsing JSON response (con cleanup ```json wrapper, vedi pattern keyword-research)
7. Database::reconnect()
8. INSERT/UPDATE aied_content_brain con brand_voice_summary, brand_voice_examples (JSON), 
   tone (enum, fallback 'friendly' se AI non confident), glossary (JSON, merge con eventuale esistente), 
   editorial_guidelines (JSON), language, last_scan_at = NOW(), scanned_articles_count
9. emit('completed', { brain: $newContentBrain })
```

**Prompt template** (italiano, ~1500 token base + articoli):

```
Sei un analista di brand voice esperto. Ti fornirò 20 articoli del blog "{site_topic}"
del cliente. Devi estrarre la "voce" del brand per permettere a un assistente AI
di scrivere nuovi articoli nello stesso stile.

Analizza ed estrai (output JSON):

{
  "tone": "professional|friendly|fun|authoritative|casual",  // dominante
  "tone_confidence": 0.0-1.0,
  "sentence_style": "short|mixed|long",
  "voice_traits": ["uso prima persona", "domande retoriche", ...],   // 3-5 traits
  "vocabulary": ["parole ricorrenti specifiche", ...],                // 10-15
  "brand_names_detected": [],
  "structure_notes": "tipicamente 3-5 H2, paragrafi corti, liste numerate frequenti",
  "examples": ["paragrafo 1...", "paragrafo 2...", "paragrafo 3..."],  // 3 estratti rappresentativi
  "language": "it",
  "summary": "Una sintesi 2-3 frasi della voce del brand"
}

Articoli:
[1] {title}
{content[:500]}...

[2] {title}
{content[:500]}...

(... 20 articoli ...)

Rispondi SOLO con JSON valido. No prefacing, no markdown.
```

**Costo stimato per scan**: 20 articoli × ~500 char = ~10k char input + ~1-2k output = ~3500 token Claude Sonnet 4.6 = ~$0.015 = ~€0.014. Margine ampio anche su Starter (10 articoli @ €19/mese → scan è 0.07% del revenue).

**Errore handling**:
- Se < 5 articoli scrapeable → fallback: scan con quello che c'è + flag `partial_scan: true` in summary
- Se AI response non parsabile come JSON → 1 retry con prompt più strict + se fallisce ancora → ritorna error generico ("non sono riuscito a leggere il tuo sito, prova a inserire manualmente il tono")
- Se site_url non raggiungibile → error early con suggerimento "il tuo sito è online?"

### 2.2 SSE endpoint `POST /content-brain/scan`

Pattern: riusa `seo-tracking/RankCheckController::processStream()` come reference.

Flow:
1. `POST /api/editorial/v1/content-brain/scan` con body `{ article_urls?: [] }`. Se `article_urls` assente, il backend chiama `discoverArticleUrls` con `site_url` dal context (X-Site-Domain header validato da `LicenseAuthMiddleware`).
2. Controller risponde con `text/event-stream`, headers `Cache-Control: no-cache`, `X-Accel-Buffering: no`.
3. `ignore_user_abort(true)` + `set_time_limit(300)` + `session_write_close()`.
4. Loop: `ContentBrainService::scan()` con callback che fa `echo "event: $name\ndata: " . json_encode($data) . "\n\n"; @ob_flush(); flush();`
5. Su exception → `event: error` + close.
6. Su completion → `event: completed` con payload finale + close.

**Eventi SSE standard**:
- `started`: `{ total_urls, sample_size }`
- `article_scraped`: `{ index, total, url, title }`
- `article_error`: `{ index, total, url, reason }` (continue, non blocca)
- `aggregating`: `{ scraped_count }`
- `analyzing`: `{ status: "AI sta leggendo..." }`
- `completed`: `{ brain: {...} }`
- `error`: `{ message, retry_possible: bool }`

**No table `aied_scan_jobs`**: pattern seo-tracking/keyword-research scelto è SSE-only senza tabella job (vedi memoria progetto). Lo stato deriva da `aied_content_brain.last_scan_at`.

### 2.3 Endpoint `GET /content-brain` e `PUT /content-brain`

```
GET /api/editorial/v1/content-brain
  Response 200: {
    site_id, site_topic, target_audience, tone, brand_voice_summary,
    brand_voice_examples: [...], glossary: {...}, editorial_guidelines: {...},
    language, last_scan_at, scanned_articles_count
  }
  Response 404: { error: "Content Brain non ancora creato. Completa l'onboarding." }

PUT /api/editorial/v1/content-brain
  Body (tutti opzionali, partial update):
  {
    site_topic?, target_audience?, tone?, glossary?, editorial_guidelines?
  }
  Validazione:
    - tone in enum('professional','friendly','fun','authoritative','casual')
    - glossary JSON con keys: brand_names[], products[], people[], avoid_terms[], preferred_terms{}
    - editorial_guidelines JSON con keys: do[], dont[], emphasize[]
  Response 200: { updated_at, content_brain: {...refreshed...} }
  Response 422: { errors: [...] } su validation fail
```

Le campiate `brand_voice_summary` + `brand_voice_examples` + `scanned_articles_count` + `last_scan_at` sono **read-only via PUT** — si aggiornano solo via scan. UI plugin nasconde quei campi nel form di edit.

### 2.4 Discovery URL articoli (auto-suggestion onboarding)

Strategia in ordine (primo che ritorna ≥ 5 URL vince):

1. **WP REST API utente** (preferito): `GET {site_url}/wp-json/wp/v2/posts?per_page=30&status=publish&_fields=id,title,link,date` (non richiede auth se public). Dati strutturati, veloce.
2. **Sitemap XML**: parse `{site_url}/sitemap.xml` o `{site_url}/sitemap_index.xml` (riusa logica già presente in `SitemapService` di Ainstein).
3. **Spider light**: scrape `{site_url}` homepage, estrai link interni `<a href>`, filtra solo path con pattern blog (`/blog/`, `/articles/`, `/posts/`, `/[year]/[month]/`). Last resort, max 20 URL.

In tutti i casi: ritorna max 30 URL, mescolati random per evitare bias temporale (se utente ha solo articoli vecchi nel sample → voice obsoleta).

### 2.5 Plugin admin: struttura post-M2

```
src/Admin/
├── AdminPages.php                  # Estende M1.6 con submenu Content Brain + Dashboard stub
├── Pages/
│   ├── License.php                 # M1, immutato
│   ├── Onboarding.php              # M2 NEW: wizard 5-step (full-screen takeover)
│   └── ContentBrain.php            # M2 NEW: view + edit page
└── Assets/
    ├── wizard.js                   # Alpine.js logic + EventSource SSE consumer
    └── content-brain.js            # Edit form + rescan trigger
```

**Menu**:
- "Ainstein Editorial" (top-level)
  - "Dashboard" (stub M2 → completo M3)
  - "Content Brain" (M2 NEW)
  - "License" (M1)

**Logica redirect onboarding** (in `Plugin::handleAdminInit`):
```php
if (LicenseManager::isActive() 
    && !get_option('aied_onboarding_completed')
    && !str_contains($_GET['page'] ?? '', 'aied-onboarding')
    && !str_contains($_GET['page'] ?? '', 'aied-license')) {
    wp_safe_redirect(admin_url('admin.php?page=aied-onboarding'));
    exit;
}
```
Plus guard `defined('WP_CLI') && WP_CLI` (M2.0 fix) per evitare ripetere il bug M1.9.7.

### 2.6 Onboarding Wizard 5-step (UX)

Single-page application stile, no page reload tra step. Alpine.js per state, fetch API per submit.

**Step 1 — Site Topic**:
```
[Heroicon: chat-bubble-left]   Di cosa parla il tuo sito?

Aiutaci a capire il tuo blog/sito in poche parole.

[ textarea, 3 righe, placeholder: "Vendo vino artigianale italiano dal 1985. 
                                    Il blog racconta storie di tradizione enologica..." ]

🪄 Auto-suggest da scan: clicca per analizzare i tuoi articoli e suggerire una descrizione
   [bottone secondario; triggera mini-scan rapido (3-5 articoli) → AI suggerisce topic in 
   inline-edit textarea]

[Avanti →]   Skip suggestion
```

**Step 2 — Target Audience**:
```
[Heroicon: users]   Chi sono i tuoi clienti?

[ textarea, 3 righe, placeholder: "Appassionati di vino, età 30-60, italiani, 
                                    spendono €30-80 a bottiglia, amano la tradizione" ]

← Indietro   [Avanti →]
```

**Step 3 — Tone**:
```
[Heroicon: musical-note]   Che tono usi?

Scegli quello che si avvicina di più alla voce del tuo brand.

[5 card grid 2x3 (1 in seconda riga centrata)]
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Professionale│ │  Amichevole  │ │  Divertente  │
│              │ │   ⭐ scelto  │ │              │
│ "Il vino     │ │ "Ciao!       │ │ "Ehi sai     │
│  italiano è  │ │  Oggi ti     │ │  che il vino │
│  storia..."  │ │  parlo di..."│ │  parla?..."  │
└──────────────┘ └──────────────┘ └──────────────┘
┌──────────────┐ ┌──────────────┐
│ Autorevole   │ │   Casual     │
│              │ │              │
│ "Studi       │ │ "Tipo, hai   │
│  enologici   │ │  presente    │
│  dimostrano  │ │  quel vino?" │
└──────────────┘ └──────────────┘

← Indietro   [Avanti →]
```

**Step 4 — Volume**:
```
[Heroicon: calendar-days]   Quanti articoli al mese?

[slider 1-30, default = tier-limit corrente]

→ "12 articoli al mese (circa 3 a settimana)"

ℹ️  Il tuo piano Pro include 50 articoli/mese. Sei sotto la quota: ottimo!
   (oppure: ⚠️ Sei sopra la quota Starter 10/mese, upgrade necessario per scriverli tutti)

← Indietro   [Avanti →]
```

**Step 5 — Editorial Guidelines (Don't)**:
```
[Heroicon: no-symbol]   Cosa NON pubblicare mai sul tuo blog?

Lista le cose che il tuo brand evita: argomenti, parole, competitor, opinioni.

[ textarea, 4 righe, placeholder: "Mai parlare di politica.
                                    Non recensire negativamente competitor X o Y.
                                    Niente contenuti volgari.
                                    Evita ricette di carne (siamo vegetariani)." ]

ℹ️  Potrai sempre modificare e aggiungere altre regole nella pagina Content Brain.

← Indietro   [✨ Inizia magia →]
```

**Step 6 — Scanning (auto-progress)**:
```
[Heroicon animato: sparkles]   Sto leggendo il tuo sito...

⠹ Trovati 18 articoli recenti

[██████░░░░░░░░░░░░░░] 32%

✓ Articolo 6 di 20 letto: "Storia del Chianti Classico"
✓ Articolo 5 di 20 letto: "Come abbinare vino e cibo"
...

(eventi SSE in lista, max 5 visibili, auto-scroll)
```

→ Su completed:
```
✨ Fatto! Conosco il tuo sito.

Tono rilevato: Amichevole (95% confidence)
Articoli analizzati: 18
Brand names trovati: 2 (Vinicola Rossi, Cantina XYZ)

[→ Vai al tuo Content Brain]
```

State persistence: `wp_options` `aied_onboarding_state` (JSON con step + values), refresh-safe. Su completion → `aied_onboarding_completed = 1` + delete state.

### 2.7 Pagina Content Brain — view + edit

```
┌─────────────────────────────────────────────────────────────┐
│  Content Brain                                              │
│  Ultimo scan: 18/05/2026 (2 giorni fa)  [🔄 Ri-scansiona]   │
└─────────────────────────────────────────────────────────────┘

📝 Identità del sito
   Topic    : [Vendo vino artigianale italiano dal 1985...] ✎
   Audience : [Appassionati di vino 30-60, italiani...]      ✎

🎵 Tono
   [Professionale] [● Amichevole] [Divertente] [Autorevole] [Casual]

📖 Brand Voice (rilevato dall'AI)
   Sintesi  : "Tono amichevole con uso prima persona, frasi corte e dirette,
              storytelling familiare. Domande retoriche frequenti per coinvolgere."
   
   Esempi (3 paragrafi)        [▾ Mostra]

📚 Glossario
   Brand names     : [Vinicola Rossi] [Cantina XYZ]  [+ Aggiungi]
   Prodotti        : [Chianti Riserva] [Brunello 2020]  [+]
   Persone         : [Giovanni Rossi (founder)]  [+]
   Termini da evitare  : [competitor X]  [+]
   Sostituzioni    : "vino italiano" → "vino artigianale italiano"  [+]

🚧 Linee guida editoriali
   ✅ Da fare      : 1. Enfatizza la tradizione familiare
                     2. Cita esempi concreti di abbinamenti
                     [+ Aggiungi]
   ❌ Da evitare   : 1. Non parlare di politica
                     2. Non recensire negativamente competitor
                     [+ Aggiungi]
   ⭐ Da enfatizzare: 1. Sostenibilità
                     2. Made in Italy
                     [+ Aggiungi]

[💾 Salva modifiche]
```

Tutti i campi sono `contenteditable` (Alpine.js) con debounce 500ms → autosalva via `PUT /content-brain`. Glossario + guidelines = list editor con add/remove inline.

Re-scan: bottone "🔄 Ri-scansiona" → riapre il flusso SSE M2.2 (con stessa UI progress dello step 6 wizard, ma in modal full-screen anzichè take-over).

### 2.8 Componenti CSS riusabili

Posizione: `src/assets/css/admin.css` (estende quello minimo di M1).

Componenti da definire:
- `.aied-card` — container con `bg-white dark:bg-slate-800 rounded-xl shadow-sm border`
- `.aied-btn-primary` — gradient amber per primary CTA (palette Ainstein)
- `.aied-btn-secondary` — outline neutro
- `.aied-input` / `.aied-textarea` — focus ring slate
- `.aied-progress` — animated stripe bar
- `.aied-wizard-step` — full-height step container con transition slide
- `.aied-tone-card` — 5 card tone selection con selected state
- `.aied-tag` — pills per glossario items con × per remove
- `.aied-modal` — overlay full-screen per rescan SSE

Heroicons SVG sprite (~20 icone usate in M2) inline in `src/assets/images/icons.svg` + helper PHP `aied_icon($name)`.

Tailwind: per M2 va bene **CSS statico hand-written** (~600 righe). Build pipeline Tailwind→PostCSS rinviata a M6 polish. Riusiamo classi Tailwind direttamente nei template PHP (utility-first), il file `admin.css` contiene solo i componenti custom.

---

## 3. Out of scope M2 (esplicitato)

| Feature | Quando |
|---------|--------|
| Generazione articoli AI | M3 |
| Keyword research | M4 |
| Editorial calendar | M4 |
| Auto-pilot cron | M4 |
| Internal linking automatico | M5 |
| Meta-tag bulk generation | M5 (opzionale) |
| Image generation cover | M5 |
| Email notifications | M6 |
| Billing UI / portal | M6 |
| Quota enforcement strict | M3 (passa azioni gratis in M2) |
| Auto-rescan cron 6 mesi | M6 polish |
| WPML/Polylang translation di Content Brain | Non in MVP, v1.1 |
| Multi-site per user (Content Brain × N siti) | Già supportato a schema (M1); UI plugin in M2 mostra solo il **current site**, lo switch site arriverà in M6 |

---

## 4. Piano di lavoro

> Task ID format `M2.{T}.{a-z}`. Stima effort in ore (1 dev full-time, Claude assistito).

### M2.0 — Fix backlog M1.9.7 (CLI guard) — 1h

- [ ] M2.0.a Aggiungere `if (defined('WP_CLI') && WP_CLI) return;` come prima riga di `Plugin::handleActivationRedirect()` (`src/Includes/Plugin.php`)
- [ ] M2.0.b Aggiornare `tests/wp-compat/smoke.sh` probe rimuovendo il `delete_transient` workaround (non più necessario) e ri-testare
- [ ] M2.0.c Rebuild zip → `dist/ainstein-editorial-v0.1.1.zip`
- [ ] M2.0.d Re-run `bash setup.sh && bash smoke.sh` per regression (atteso: identico esito M1.9.7)

**DoD**: smoke wp-compat 5/5 PASS senza workaround.

---

### M2.1 — `ContentBrainService` core — 6h

Posizione: `seo-toolkit/api/editorial/Services/ContentBrainService.php`.

- [ ] M2.1.a Class skeleton con metodi pubblici `scan()`, `get()`, `update()`, `discoverArticleUrls()` come da spec §2.1
- [ ] M2.1.b Implementare `discoverArticleUrls()`: WP REST API → sitemap fallback → spider fallback. Riusa `ScraperService` per fetch.
- [ ] M2.1.c Implementare `scan()` flow completo: scrape loop + bulk prompt + AiService->analyze + parsing JSON + INSERT/UPDATE `aied_content_brain`
- [ ] M2.1.d Helper `parseAiJson($content)` che gestisce ` ```json ... ``` ` wrapper (pattern da keyword-research)
- [ ] M2.1.e Implementare `get()` con join `aied_sites` per site validation
- [ ] M2.1.f Implementare `update()` con validazione: tone enum, glossary shape, guidelines shape. Partial update via JSON merge.
- [ ] M2.1.g `ApiLoggerService::log()` per ogni chiamata AI + scrape (provider: `claude_anthropic`, `editorial_scraper`)
- [ ] M2.1.h `Database::reconnect()` dopo AiService call

**DoD**: chiamata isolata da PHPUnit `(new ContentBrainService())->scan(siteId, [3 URL hardcoded], $mockEmit)` produce un record valido in `aied_content_brain` su DB locale.

---

### M2.2 — Endpoint SSE `/content-brain/scan` — 4h

Posizione: `seo-toolkit/api/editorial/Controllers/ContentBrainController.php`.

- [ ] M2.2.a Implementare metodo `scan(Request $req)` che monta SSE response (headers + ignore_user_abort + set_time_limit + session_write_close)
- [ ] M2.2.b Adattare `Services/ContentBrainService::scan()` per ricevere `$emit` callback con eventi tipizzati (vedi spec §2.2)
- [ ] M2.2.c Implementare emissione eventi SSE format-compliant: `event: name\ndata: json\n\n` + `@ob_flush(); flush()`
- [ ] M2.2.d Exception handling: catch + emit `event: error` + close stream
- [ ] M2.2.e Aggiungere route `POST /api/editorial/v1/content-brain/scan` in `routes.php` (rimpiazza stub 501 M1.3)
- [ ] M2.2.f Test E2E via curl: `curl -N -X POST -H "X-License-Key: TEST-..." -H "X-Site-Domain: ..." /api/editorial/v1/content-brain/scan` → vedere stream eventi

**DoD**: curl SSE stream produce sequenza eventi corretta su sito test reale (es. blog WordPress italiano sample).

---

### M2.3 — Endpoint `GET` + `PUT /content-brain` — 3h

- [ ] M2.3.a Implementare `ContentBrainController::get(Request $req)` → ritorna content_brain corrente o 404
- [ ] M2.3.b Implementare `ContentBrainController::update(Request $req)` → validate + delegate a service
- [ ] M2.3.c Validation helper: tone enum, glossary shape, guidelines shape. Ritorna `[errors]` strutturato per UI.
- [ ] M2.3.d Aggiungere routes in `routes.php` (rimpiazza stub 501)
- [ ] M2.3.e Test via curl: GET prima dello scan → 404. POST scan completa. GET dopo → 200 con payload. PUT partial → 200 con merged. PUT con tone invalido → 422.

**DoD**: 4 test curl tutti corretti.

---

### M2.4 — Plugin admin pages + menu — 3h

- [ ] M2.4.a Estendere `Admin/AdminPages.php` con submenu Dashboard (stub), Content Brain
- [ ] M2.4.b Aggiungere logic redirect onboarding in `Plugin::handleAdminInit()` (con `WP_CLI` guard M2.0)
- [ ] M2.4.c Implementare `Admin/Pages/Onboarding.php` come page handler (registra page slug, capability check, nonce setup)
- [ ] M2.4.d Implementare `Admin/Pages/ContentBrain.php` con stessi pattern
- [ ] M2.4.e Verifica menu su WP browser: ordine, icone, page rendering vuota (UI in M2.5+M2.7)

**DoD**: menu visibile, 3 sottopagine accessibili (placeholder content), redirect post-activation funziona.

---

### M2.5 — Onboarding Wizard UI — 8h

- [ ] M2.5.a HTML template `Admin/Pages/Onboarding.php` con 5 step + step6 (scanning). Layout full-screen (`admin_init` rimuove WP admin chrome con CSS).
- [ ] M2.5.b Alpine.js state machine: `currentStep`, `formData`, `goNext()`, `goPrev()`. Persist su `wp_options` `aied_onboarding_state` via `admin-ajax.php` debounced.
- [ ] M2.5.c Step 1: site_topic textarea + bottone "Auto-suggest" (triggera ricerca rapida 3 articoli + AI suggest). Backend endpoint helper `/content-brain/suggest-topic` da aggiungere.
- [ ] M2.5.d Step 2: audience textarea
- [ ] M2.5.e Step 3: tone selection 5 card visive con preview testi
- [ ] M2.5.f Step 4: posts_per_month slider con hint contestuale tier (lettura da `aied_tier` wp_option)
- [ ] M2.5.g Step 5: dont textarea
- [ ] M2.5.h Step 6: scanning UI con EventSource SSE consumer, lista eventi, progress bar derivata da `index/total`. Su completion: redirect a Content Brain.
- [ ] M2.5.i Submit step 5 → `aied_save_onboarding` admin-ajax handler che chiama `PUT /content-brain` con i 4 field (topic, audience, tone, guidelines) + setta `aied_onboarding_pending_scan` + triggera SSE in step 6
- [ ] M2.5.j Resume logic: se utente chiude wizard a metà, prossimo login riapre allo step in cui era

**DoD**: utente nuovo dopo activation vede onboarding, completa 5 step, vede scan SSE, atterra su Content Brain con dati salvati. Testato su 1 sito WP locale `ai-tester`.

---

### M2.6 — Content Brain view + edit page — 6h

- [ ] M2.6.a HTML template `Admin/Pages/ContentBrain.php` come da spec §2.7
- [ ] M2.6.b Alpine.js state: load via `GET /content-brain` su pageload. Debounced autosave (500ms) via `PUT /content-brain` per ogni campo modificato.
- [ ] M2.6.c Inline editing per topic, audience: textarea expandable on focus
- [ ] M2.6.d Tone selector con 5 button visivi
- [ ] M2.6.e Glossary editor: list editor per ogni categoria (brand_names, products, people, avoid_terms). Add via input + Enter, remove via × su tag.
- [ ] M2.6.f Editorial guidelines editor: list editor per do/dont/emphasize
- [ ] M2.6.g Bottone "Ri-scansiona" → apre modal full-screen con SSE consumer (riusa step6 onboarding component)
- [ ] M2.6.h Notice toast su autosave success/fail (`wp.notices` API)
- [ ] M2.6.i Brand voice readonly section: collapse/expand 3 examples

**DoD**: utente con Content Brain popolato può vedere + editare tutti i campi + ri-scansionare. Modifiche persistono su DB.

---

### M2.7 — CSS componenti riusabili — 4h

Posizione: `src/assets/css/admin.css` (espandere il file minimal M1).

- [ ] M2.7.a `.aied-card`, `.aied-btn-primary`, `.aied-btn-secondary`
- [ ] M2.7.b `.aied-input`, `.aied-textarea` con focus ring + dark mode
- [ ] M2.7.c `.aied-tone-card` con stato selected + hover
- [ ] M2.7.d `.aied-progress` con animazione gradient sweep
- [ ] M2.7.e `.aied-wizard-step` con transition slide + height auto
- [ ] M2.7.f `.aied-tag` con × button per remove
- [ ] M2.7.g `.aied-modal` full-screen overlay
- [ ] M2.7.h Heroicons SVG sprite in `assets/images/icons.svg` + PHP helper `aied_icon($name, $class)` per inline use

**DoD**: tutti i componenti rendering corretto su Chrome+Firefox+Safari, light+dark mode. Bonus: spot check responsive mobile.

---

### M2.8 — Testing M2 — 5h

- [ ] M2.8.a PHPUnit unit test per `ContentBrainService::parseAiJson()` con 5 fixture (JSON pulito, con ```json wrapper, malformed, partial, vuoto)
- [ ] M2.8.b PHPUnit unit test per `ContentBrainService::update()` validation (tone invalido, glossary shape errata, etc.)
- [ ] M2.8.c Integration test SSE: mock AiService che ritorna JSON fittizio, verifica che `scan()` emetta gli eventi attesi in sequenza
- [ ] M2.8.d Manual smoke su 3 siti WP italiani reali (es. blog vino, blog cucina, blog fitness — selezione founder) → screenshot brand_voice estratto + valutazione qualitativa "rispecchia il sito?"
- [ ] M2.8.e wp-compat regression: ri-eseguire `tests/wp-compat/setup.sh && smoke.sh` con plugin v0.2.0 (built da M2.0+M2.X+M2.Y) — verifica che M2 non rompa compat Yoast/RankMath/Elementor/Polylang

**DoD**: PHPUnit verde su nuovi test + report manuale 3 siti italiani salvato in `docs/milestones/M2-test-report.md`.

---

### M2.9 — Documentation + commit progressivo — 2h

- [ ] M2.9.a Aggiornare `docs/decisions.md` con eventuali ADR scoperti durante M2 (es. scelta Alpine.js vs Vue, persistenza wizard state, etc.)
- [ ] M2.9.b Aggiornare `docs/roadmap.md` checkboxes M2 a `[x]`
- [ ] M2.9.c Aggiornare `MEMORY.md` con stato M2 chiuso + prossimo M3
- [ ] M2.9.d Commit atomici per task (M2.1 → M2.9) con prefisso `feat(editorial): M2.X — ...` o `fix(editorial): M2.X — ...`

**DoD**: branch `feat/editorial-m2` (nuovo, da `feat/editorial-m1` chiuso) con N commit ordinati + tag `v0.2.0`.

---

## 5. Definition of Done globale M2

- ✅ M2.0 fix CLI guard applicato, smoke wp-compat regression 5/5 PASS
- ✅ `ContentBrainService` testabile in isolamento (PHPUnit verde)
- ✅ 3 endpoint REST funzionanti via curl: scan SSE, get, put
- ✅ Plugin v0.2.0 zip prodotto e installabile
- ✅ Onboarding wizard completabile end-to-end su WP locale (5 step + SSE + redirect)
- ✅ Content Brain page mostra dati e permette edit con autosave funzionante
- ✅ Scan reale su almeno 3 siti italiani diversi produce brand voice **qualitativamente sensata** (giudizio founder)
- ✅ wp-compat regression v0.2.0: nessuna nuova collisione con stack testato in M1.9.7
- ✅ Documentazione aggiornata (roadmap checkbox + ADR nuovi + MEMORY.md)

---

## 6. Risk register M2

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| Brand voice estratto generico / non utile | Media | Alto | Iterare prompt su 3 siti diversi durante M2.1+M2.8. Se output basso, raffinare prompt o aumentare `sample_size`. Fallback: skip auto-scan, partire da manual input |
| Siti con < 5 articoli (nuovi blog) | Media | Medio | `partial_scan: true` flag + onboarding mostra warning "Hai pochi articoli, il Content Brain sarà basato sui tuoi input manuali. Puoi ri-scansionare quando avrai 10+ articoli" |
| Sito non raggiungibile via WP REST API (security plugin blocca) | Alta | Medio | Fallback sitemap → spider → manual URL input |
| Scan timeout su connessioni lente | Media | Medio | `set_time_limit(300)` + max 20 articoli (non 30) + sitewise rate limit 1 req/sec |
| Alpine.js conflitto con altro plugin che lo include | Bassa | Basso | Includere Alpine.js solo nelle pagine plugin (`enqueue_admin_scripts` con hook screen check) |
| Wizard state perso al refresh | Media | Basso | Persist su `wp_options` `aied_onboarding_state` JSON debounced |
| AI cost > tier margine | Bassa | Alto | Scan = ~€0.014, ben sotto margine Starter. Monitorare in produzione, aggiungere quota se necessario (M3) |
| Plugin WP attiva > 10 redirect loop (onboarding) | Bassa | Alto | Guard `aied_onboarding_completed` + skip se admin su admin-ajax.php / WP-CLI / cron |
| SSE blocked da hosting cliente | Bassa | Alto | Fallback polling `/content-brain` ogni 3s con `last_scan_at` check (no scan_jobs table — basta polling read) |
| Compatibilità con Polylang multilingua | Media | Medio | Schema `aied_content_brain.language` esiste già. M2 testa solo 'it'. Multi-language brain → v1.1 |

---

## 7. Test plan M2

### Unit tests (PHPUnit)
- `tests/Unit/ContentBrainServiceTest.php`:
  - `testParseAiJson_clean()`
  - `testParseAiJson_withMarkdownWrapper()`
  - `testParseAiJson_malformed_returnsNull()`
  - `testUpdate_toneEnumValid()`
  - `testUpdate_toneEnumInvalid_returnsError()`
  - `testUpdate_glossaryShape_validates()`
  - `testDiscoverArticleUrls_wpRestPrimary()`
  - `testDiscoverArticleUrls_sitemapFallback()`

### Integration tests
- `tests/Integration/ContentBrainScanFlowTest.php`:
  - Mock AiService con fixture JSON realistico
  - Mock ScraperService con 5 articoli fittizi
  - Verifica sequenza eventi emit: started → scraped×5 → aggregating → analyzing → completed
  - Verifica record `aied_content_brain` post-scan ha campi corretti

### Manual smoke (founder)
- 3 siti WP italiani diversi (vino, food, fitness)
- Per ogni sito: completare onboarding + scan + valutazione brand voice
- Output: `docs/milestones/M2-test-report.md` con 3 sezioni + verdetto GO/no-GO M3

### wp-compat regression (automatizzato)
- `cd tests/wp-compat && bash setup.sh && bash smoke.sh` con plugin v0.2.0
- Atteso: 5/5 PASS, nessuna regressione vs M1.9.7

### Coverage target
- Unit + Integration: ≥ 70% lines su `Services/ContentBrainService.php`
- wp-compat: 5/5 install PASS (uguale M1.9.7)

---

## 8. Effort totale stimato

| Task | Effort |
|------|--------|
| M2.0 — Fix CLI guard | 1h |
| M2.1 — ContentBrainService core | 6h |
| M2.2 — SSE endpoint scan | 4h |
| M2.3 — GET/PUT endpoints | 3h |
| M2.4 — Plugin admin pages + menu | 3h |
| M2.5 — Onboarding wizard UI | 8h |
| M2.6 — Content Brain view+edit page | 6h |
| M2.7 — CSS componenti | 4h |
| M2.8 — Testing | 5h |
| M2.9 — Docs + commit | 2h |
| **Totale** | **~42h** (range 35-50h con incertezza wizard UX) |

Roadmap originale dava 25-35h per M2 → ho rivisto al rialzo per il dettaglio wizard (8h M2.5) e i CSS componenti (4h M2.7), che il roadmap top-level non aveva dettagliato. Se serve compressione, M2.7 CSS può ridursi a 2h usando solo Tailwind utility e rinunciando ai componenti astratti (riusabili in M3+).

---

## 9. Cosa otteniamo a fine M2

**Demo dimostrabile** (`/editorial-demo` di fine M2):
1. Apri WP locale → installa plugin v0.2.0
2. Attiva con license test Starter
3. Auto-redirect a Onboarding wizard
4. Completa 5 step in ~3 minuti
5. Vedi scan SSE in tempo reale (eventi che scorrono)
6. Atterra su Content Brain con brand voice estratto reale
7. Edita un campo → vedi autosave funzionare
8. Click "Ri-scansiona" → modal SSE riparte

**Valore strategico**:
- Differenziatore vs competitor "yet another AI writer" → AE conosce il sito specifico
- Lock-in psicologico: utente ha investito 5 minuti a "configurarci" → switching cost > 0
- Foundation per M3: ogni generation AI sarà constrainted dal Content Brain → output di qualità immediatamente superiore vs generic ChatGPT

**Valore tecnico**:
- Pattern SSE Editorial validato (riuso per M3 article generation, M4 keyword research)
- Service layer rifattorizzabile come blueprint per M3 `ArticleService` + M4 `KeywordResearchService`
- CSS componenti pronti per M3 UI articoli
- Plugin admin UX skeleton (wizard + page edit pattern) riusato in M4 (editorial plan)

---

## 10. Approvazione

> **Cliente, per favore valida**:
> 
> 1. ✅ / ✏️ Spec tecnica sezione §2 sembra coerente con design.md §8?
> 2. ✅ / ✏️ Onboarding wizard 5 step + step 6 scanning va bene così o vogliamo più/meno step?
> 3. ✅ / ✏️ Effort stimato 42h è accettabile (vs 25-35h roadmap originale)?
> 4. ✅ / ✏️ Out of scope §3 chiaro? Manca qualcosa che vorresti vedere in M2?
> 5. ✅ / ✏️ Risk register §6 catturato i rischi principali?
> 6. ✅ / ✏️ M2.0 fix CLI guard appropriato qui (vs sessione separata)?
> 
> Una volta approvata: parto con M2.0 → M2.1 in ordine. Ogni commit visibile in `git log`.

---

*M2 spec scritta 2026-05-20 da Claude. Aggiornare con `/editorial-save` a fine sessione di esecuzione.*
