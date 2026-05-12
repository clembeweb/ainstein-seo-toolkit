# Ainstein Editorial — Istruzioni Claude

> File caricato all'inizio di ogni sessione di lavoro sul plugin. Continuità tra sessioni.
> Ultimo aggiornamento: 2026-05-12

---

## CONTESTO PROGETTO

| Aspetto | Dettaglio |
|---------|-----------|
| **Prodotto** | Ainstein Editorial — plugin WordPress |
| **Posizionamento** | "Il tuo SEO Pro autonomo per WordPress" (target: blogger/PMI non-tech) |
| **Brand** | "Ainstein Editorial" (consumer-facing) / "An Ainstein product" (parent brand) |
| **Repo** | Monorepo dentro `seo-toolkit/`, sub-progetto in `plugins/ainstein-editorial/` |
| **Backend** | Condiviso con Ainstein (server Hetzner, namespace `aied_*`) |
| **Stack plugin** | PHP 8.0+, WordPress 6.0+, Tailwind CSS, Alpine.js |
| **Stack backend** | Riusa stack Ainstein (PHP custom framework, MySQL) |
| **Lingua UI** | Italiano (target IT-first, expansion EN successiva) |
| **Pagamenti** | Lemon Squeezy (Merchant of Record + License Keys API) |

## OBIETTIVO PROGETTO

Lanciare un prodotto SaaS recurrent revenue da €50-200K ARR nei primi 12 mesi sfruttando il riuso massimo del codice Ainstein esistente. Vendita diretta dal sito proprio (`xxx.com` — nome finale TBD), distribuzione plugin via download dal sito.

**NON è**: SaaS interno di Ainstein, lead-gen per Ainstein, modulo di Ainstein. È un prodotto autonomo con UX/brand propri, che internamente riusa l'infra Ainstein.

## GOLDEN RULES (INVIOLABILI)

```
1.  Riuso PRIMA di duplicazione      → Se Ainstein ha già il servizio (AiService, ScraperService,
                                       SerpApiService, KeywordInsightService, GoogleOAuthService,
                                       InternalLinksPool), si usa quello via API interna
2.  Namespace separato in DB         → Tutte le tabelle del plugin prefisso `aied_` (NON `aic_`,
                                       NON `kr_`, NON `users` di Ainstein)
3.  Plugin NON conosce Ainstein      → L'utente WP non vede mai "Ainstein" come URL operativo.
                                       Tutte le API esposte sotto dominio prodotto (xxx.com/api/v1/*)
4.  Lemon Squeezy = single source    → License/billing/subscription stato sempre verificato via LS API.
    of truth per billing               Cache locale 24h max, mai DB autorevole su stato pagamenti
5.  Zero BYOK                         → L'utente paga noi, noi paghiamo Anthropic/OpenAI/Gemini.
                                       Nessuna API key di terze parti chiesta all'utente
6.  CSS standard Ainstein             → Stesso Tailwind + Heroicons SVG. NO Lucide, NO FontAwesome.
                                       Riusa Golden Rules CSS di Ainstein (rounded-xl, px-4 py-3, etc.)
7.  Auto-pilot è feature core         → NON opzionale, NON v1.1. È il "quid" del prodotto.
                                       Va costruito da subito anche se richiede più tempo
8.  Content Brain è feature core      → Differenziatore vs competitor. Brand voice scanner,
                                       glossario, editorial guidelines. Non saltare
9.  Internal linking BIDIREZIONALE    → Quando AI scrive articolo nuovo, modifica anche articoli
                                       vecchi per inserire link verso il nuovo. NON solo da
                                       nuovo→vecchio. È differenziatore vs competitor
10. Output sempre come DRAFT WP       → Mai auto-publish nel MVP. Auto-publish solo come opt-in
                                       esplicito v1.1 con disclaimer
11. Email = canale primario          → Email settimanale "ho scritto X" + email mensile report.
                                       L'utente NON deve aprire il plugin per sapere cosa succede
12. Decisioni nuove → decisions.md    → Ogni decisione architetturale presa in sessione va
                                       loggata in formato ADR-like in docs/decisions.md
13. Tutti i copy in italiano          → Inclusi error messages, email, tooltip. EN solo per
                                       i18n files (.po/.mo) preparati per espansione futura
14. Plugin REST API solo verso        → Plugin non chiama mai DB Ainstein direttamente. Sempre
    backend nostro                      via HTTP REST verso `xxx.com/api/v1/*`. Lui ha solo
                                       wp_options per config locale
15. Test su sito WP reale prima       → Mai claim "feature pronta" senza averla testata su
    di claim completamento              installazione WP locale + 1 sito staging reale
```

## STRUTTURA REPO

```
plugins/ainstein-editorial/
├── README.md                          # Vision + overview prodotto
├── CLAUDE.md                          # Questo file
├── docs/
│   ├── design.md                      # Design tecnico completo
│   ├── roadmap.md                     # Milestone + validation + DoD
│   └── decisions.md                   # ADR cronologici
├── src/                               # Codice plugin PHP (popolato in milestone M2)
│   ├── plugin.php                     # Entry point WP
│   ├── Includes/                      # Classi core (namespace Ainstein\Editorial\)
│   ├── Admin/                         # Pagine admin WP
│   ├── Api/                           # Client REST verso backend
│   └── ...
├── assets/                            # Tailwind compilato, JS, SVG (popolato in M2-M3)
│   ├── css/
│   ├── js/
│   └── images/
├── languages/                         # i18n .po/.mo (popolato in M5)
├── tests/                             # Test PHPUnit + integration (popolato durante M2-M5)
└── build.sh                           # Script zip per distribuzione marketplace-style
```

Backend Ainstein lato API editorial:
```
seo-toolkit/
├── api/editorial/                     # Nuova cartella per API plugin (M1)
│   ├── routes.php                     # Endpoint definitions
│   ├── controllers/                   # Controller per ogni feature
│   └── middleware/                    # Auth via License Key, rate limit
├── core/editorial/                    # Logica plugin-specific (se necessaria)
└── database/migrations/               # Migration tabelle aied_* (M1)
```

## TABELLE DATABASE (namespace aied_*)

Vedi `docs/design.md` per schema completo. Riassunto:

| Tabella | Scopo |
|---------|-------|
| `aied_users` | Account plugin (creati silenziosamente all'attivazione license, separati da `users` Ainstein) |
| `aied_sites` | Siti WP collegati a un user (1 user può avere N siti a seconda del tier) |
| `aied_subscriptions` | Cache stato abbonamento Lemon Squeezy (verifica via LS API) |
| `aied_actions_log` | Consumo "azioni" (articoli/meta/immagini) per quota tracking |
| `aied_content_brain` | Brand voice + glossario + editorial guidelines per sito |
| `aied_editorial_plans` | Calendari editoriali per sito |
| `aied_editorial_items` | Singoli articoli pianificati nel calendario |
| `aied_articles` | Output AI (articoli generati con metadata) |
| `aied_internal_links` | Link inseriti automaticamente (per tracking + undo) |
| `aied_api_logs` | Log chiamate AI/SERP per debugging e costi |

## PATTERN DI SVILUPPO

### Plugin → Backend communication

```php
// Plugin chiama sempre via HTTP REST con License Key in header
$response = wp_remote_post('https://xxx.com/api/v1/articles/generate', [
    'headers' => [
        'Content-Type' => 'application/json',
        'X-License-Key' => get_option('aied_license_key'),
        'X-Site-Domain' => home_url(),
    ],
    'body' => json_encode($payload),
    'timeout' => 300,  // Long timeout per generation
]);
```

### Backend riuso servizi Ainstein

```php
// Endpoint backend riusa servizi Ainstein esistenti
namespace Ainstein\Editorial\Controllers;

class ArticleController {
    public function generate(Request $req) {
        // Riuso AiService Ainstein (ZERO duplicazione)
        $ai = new \Ainstein\Services\AiService('editorial');
        // Riuso SerpApiService Ainstein
        $serp = new \Ainstein\Services\SerpApiService();
        // ...
    }
}
```

### Streaming SSE per "WOW moment"

```php
// Backend invia eventi SSE durante generazione articolo
// Plugin admin page consuma EventSource in browser
// Pattern: riuso da seo-tracking RankCheckController
```

## CHECKLIST PRE-COMMIT (plugin-specific)

```
[ ] Tutti i testi UI in italiano
[ ] Namespace PHP corretto: Ainstein\Editorial\*
[ ] Prefisso DB aied_ rispettato
[ ] Prefisso wp_options aied_ rispettato (no collisioni con altri plugin)
[ ] Sanitize input + escape output (WP security standards)
[ ] Capability check su admin pages (current_user_can('manage_options'))
[ ] Nonce su tutti i form admin (wp_nonce_field + check_admin_referer)
[ ] No call diretta a database Ainstein dal plugin → sempre via API REST
[ ] License key validato prima di ogni operazione (cache 1h)
[ ] Error messages user-friendly in italiano (non stacktrace)
[ ] Tested su WP 6.0+ con PHP 8.0+
[ ] Decisioni architetturali nuove → loggate in docs/decisions.md
```

## COMANDI FREQUENTI

```bash
# Sviluppo locale (Laragon)
# Plugin path simbolico verso WP locale:
ln -s C:\laragon\www\seo-toolkit\plugins\ainstein-editorial C:\laragon\www\wp-test\wp-content\plugins\

# Test login WP locale
# URL: http://localhost/wp-test/wp-admin
# User: admin / Pass: admin (configurazione test)

# Backend API endpoints test
curl -X POST http://localhost/seo-toolkit/api/editorial/articles/generate \
  -H "X-License-Key: TEST_KEY" \
  -H "X-Site-Domain: http://localhost/wp-test" \
  -d '{"keyword":"vino rosso italiano"}'

# Build plugin per distribuzione
cd plugins/ainstein-editorial
./build.sh  # produce ainstein-editorial-vX.Y.Z.zip

# Sintassi check
php -l src/plugin.php
```

## CREDENZIALI TEST (in sessioni dev)

| Ambiente | URL | Credenziali |
|----------|-----|-------------|
| WP Local | `http://localhost/wp-test` | admin / admin |
| Backend Local | `http://localhost/seo-toolkit/api/editorial/*` | License Key test in `wp_options` |
| Lemon Squeezy | dashboard.lemonsqueezy.com (account TBD) | Da creare in M1 |

## CONTINUITÀ TRA SESSIONI

**All'inizio di ogni nuova sessione di lavoro su questo progetto**:

1. Leggi `README.md` per overview
2. Leggi `docs/roadmap.md` → trova milestone corrente
3. Leggi ultime 3 entries di `docs/decisions.md`
4. Verifica stato git (`git log --oneline plugins/ainstein-editorial`)
5. Procedi su task milestone corrente

**Al termine di ogni sessione produttiva**:

1. Update `docs/decisions.md` se hai preso decisioni architetturali
2. Update `docs/roadmap.md` con progresso milestone (checkbox completati)
3. Commit con messaggio formato: `feat(editorial): <descrizione>` o `docs(editorial): <descrizione>`
4. Eventuale memory update nel sistema persistente

## WORKFLOW DEL CLIENTE (proprietario)

Il proprietario del progetto **chiederà solo**:
- "Come va il lavoro su Editorial?"
- "Procedi con la milestone successiva"
- "Mostrami una demo"
- Eventuali decisioni di alto livello

**NON chiederà istruzioni tecniche**. Claude prende decisioni tecniche autonomamente seguendo le regole sopra. Solo decisioni che impattano vision/pricing/posizionamento richiedono conferma.

Quando in dubbio se una decisione è "tecnica" o "strategica":
- Tecnica = scelta tra librerie, struttura file, pattern implementativo → decidi tu
- Strategica = cambia pricing, posizionamento, feature scope, target → chiedi

---

## COMANDI UTENTE (trigger linguistici espliciti)

Il proprietario può usare questi **trigger linguistici** in qualsiasi sessione per attivare azioni precise di documentazione e continuità. Claude DEVE riconoscere e eseguire ognuno di questi comandi senza richiedere conferme intermedie (procedi e mostra il risultato).

### `/editorial-status` — "stato editorial" · "come va editorial?" · "a che punto siamo?"

**Cosa fa Claude:**
1. Legge `plugins/ainstein-editorial/docs/roadmap.md`
2. Identifica milestone corrente (prima non completata)
3. Mostra:
   - Milestone corrente + % completamento (task checkbox)
   - Task completati in questa milestone
   - Task aperti (prossimi 3)
   - Ultimi 3 commit con prefisso `editorial`
   - Eventuali blocker noti
4. Output sintetico (~15 righe), no fluff

**Uso tipico**: inizio sessione, per riprendere dove avevamo lasciato.

### `/editorial-save` — "salva sessione" · "fine sessione editorial" · "chiudi sessione"

**Cosa fa Claude (in ordine):**
1. **Aggiorna `docs/roadmap.md`**:
   - Checkbox `[x]` su task completati in questa sessione
   - Aggiunge sotto-task scoperti durante il lavoro
   - Aggiorna stato milestone se completata
2. **Aggiorna `docs/decisions.md`** se sono state prese decisioni architetturali nuove:
   - Append nuova entry ADR con format standard
3. **Aggiorna `CLAUDE.md`** SE ci sono pattern nuovi da preservare:
   - Update sezione "Pattern di sviluppo" o "Golden Rules" se emerso un anti-pattern
4. **Git commit** con messaggio strutturato:
   - `feat(editorial): <descrizione>` per nuove feature
   - `docs(editorial): <descrizione>` per solo docs
   - `fix(editorial): <descrizione>` per bug fix
   - `refactor(editorial): <descrizione>` per refactor senza behavior change
5. **Mostra summary** all'utente:
   - File modificati
   - Decisioni loggate (se ci sono)
   - Hash commit
   - Milestone status update
   - Prossimo task suggerito

**Uso tipico**: fine sessione, prima di chiudere il terminale.

### `/editorial-decision <testo>` — "logga decisione: <testo>" · "decisione presa: <testo>"

**Cosa fa Claude:**
1. Apre `docs/decisions.md`
2. Append nuova entry ADR con format:
   ```markdown
   ## ADR-NNN: <Titolo derivato dal testo>
   **Date**: YYYY-MM-DD · **Status**: Accepted
   
   **Context**: <chiede a Claude di estrarre dal contesto sessione>
   **Decision**: <il testo della decisione>
   **Alternatives considered**: <da contesto>
   **Consequences**: <da contesto>
   ```
3. Conferma con riga "✅ ADR-NNN loggato in decisions.md"

**Uso tipico**: dopo discussione che porta a decisione architetturale rapida da fissare.

### `/editorial-next` — "prossimo task" · "cosa devo fare ora?" · "next"

**Cosa fa Claude:**
1. Legge `docs/roadmap.md` → trova primo task `[ ]` aperto in milestone corrente
2. Carica eventuali file di contesto necessari (specs, design.md sezioni rilevanti)
3. Inizia ad eseguire il task (NON chiede conferma, procede)
4. Mostra cosa sta facendo in tempo reale

**Uso tipico**: quando l'utente vuole semplicemente "continua il lavoro", senza specifiche.

### `/editorial-demo` — "mostrami demo" · "fai vedere come va"

**Cosa fa Claude:**
1. Identifica cosa è "demonstrabile" nello stato attuale (codice, mockup, design)
2. Se c'è plugin runnable → istruzioni per avviarlo + URL
3. Se ci sono solo mockup/design → screenshot/preview tramite tool
4. Se siamo ancora in fase pre-code → mostra design+roadmap visuale
5. Output: cosa è pronto da vedere, cosa manca, cosa è il prossimo "wow moment" da costruire

### `/editorial-help` — "aiuto" · "che comandi posso usare?"

Mostra l'elenco di questi comandi con descrizione 1-line ciascuno.

---

### Regole sui comandi

- **Trigger fuzzy**: i trigger linguistici sopra sono indicativi. Claude riconosce equivalenti semantici in italiano (es. "stato" = "come va?" = "punto situazione" → tutti `/editorial-status`).
- **Comandi NON bloccanti**: dopo aver eseguito un comando, Claude continua naturalmente la conversazione. Nessun "premi enter per continuare".
- **Comandi cumulabili**: l'utente può chiedere "stato editorial, poi proseguiamo con next task" → Claude esegue prima `/editorial-status`, mostra output, poi esegue `/editorial-next`.
- **Comandi auto-trigger**: se Claude rileva che la sessione sta per finire (utente dice "ci vediamo", "buona giornata", "ho finito") → suggerisce proattivamente `/editorial-save` prima di chiudere. NON salva automaticamente senza conferma.

---

*File per Claude Code — Aggiornare la data ad ogni modifica strutturale*
