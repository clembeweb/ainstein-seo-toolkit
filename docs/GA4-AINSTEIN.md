# Google Analytics 4 del sito Ainstein

> Tracciamento della piattaforma ainstein.it (non dei siti dei clienti: quello è AI Rank). Creato il 2026-10-16
> per avere numeri veri dopo il post di lancio su LinkedIn: visite, iscrizioni, progetti, moduli attivati, crediti spesi.

## Dove sta

| Cosa | Dove |
|------|------|
| Account Google | bewebsolution@gmail.com |
| Account Analytics | **Gestione Beweb** (id `65295352`), lo stesso dei clienti |
| Proprietà | **Ainstein** (id `559342254`), fuso Europa/Roma, valuta EUR, settore "Computer ed elettronica" |
| Stream web | "Ainstein sito", https://ainstein.it, id stream `16144349187` |
| **Measurement ID** | **`G-VFS16XJYRQ`** |
| Link diretto | https://analytics.google.com/analytics/web/#/a65295352p559342254/reports/intelligenthome |
| DebugView | Amministrazione → Visualizzazione dei dati → DebugView |

L'ID **non sta nei file**: è in Admin → Impostazioni → scheda Avanzate → "Google Analytics 4 — ID misurazione"
(setting `ga4_measurement_id`, Golden Rule 5). Campo vuoto = nessun tag, nessun banner. In produzione va inserito a mano
dopo il deploy (il DB di prod non ha la riga).

## Regole

- **Admin esclusi**: con ruolo `admin` non viene stampato nessun tag (`Analytics::enabled()`), così le prove nostre
  non sporcano i numeri. Per vedere gli eventi serve un utente normale.
- **Consenso (Consent Mode v2)**: `analytics_storage` parte `denied`. Il banner in basso ("Accetta" / "Solo necessari")
  salva la scelta in `localStorage.ainstein_consent` e chiama `gtag('consent','update')`. Con "Solo necessari" GA4 riceve
  solo ping senza cookie (conteggi modellati, nessun identificativo). Cookie Policy aggiornata (`docs/cookies`).
- **Niente dati personali** nei parametri: mai email, nomi, id utente.
- **In locale** (`localhost` o `APP_DEBUG`) il tag parte con `debug_mode: true`: gli eventi si vedono in DebugView.
  Gli eventi di prova finiscono comunque nella proprietà vera: pochi, ma esistono.

## Cosa si traccia

| Evento | Quando | Parametri | Dove nel codice |
|--------|--------|-----------|-----------------|
| `page_view` | ogni pagina (app, docs, landing, prezzi, login/registrazione) | automatici | `Analytics::headScript()` nei layout |
| `sign_up` | iscrizione completata | `method` = email / google | `public/index.php` (POST /register), `controllers/OAuthController.php` |
| `login` | accesso riuscito | `method` = email / google | `public/index.php` (POST /login), `controllers/OAuthController.php` |
| `project_created` | nuovo progetto globale | `has_domain` 0/1 | `controllers/GlobalProjectController::store()` |
| `module_activated` | modulo attivato su un progetto | `module` (slug), `type` se tipizzato | `GlobalProjectController::activateModule()` |
| `credits_spent` | ogni riga di `usage_log` | `module`, `operation`, `credits` | `GET /credits/balance?since=ID` + JS in `shared/views/layout.php` |
| `free_check` | controllo AI gratuito della home finito | `score`, `total`, `result` good/partial/poor/site_error | JS in `public/landing5.php` |
| `pricing_view` | apertura di /pricing | — | `public/pricing.php` |

Più gli eventi automatici di GA4 (scroll, form_start, form_submit, user_engagement, click esterni).

### Come arrivano gli eventi lato server

Un evento PHP non può parlare con GA4 (il tag sta nel browser). Quindi:

- **Coda in sessione**: `\Core\Analytics::event('nome', [...])` mette l'evento in `$_SESSION['_ga_events']`;
  la pagina successiva (dopo il redirect) lo stampa come `gtag('event', ...)` dentro `headScript()`. Vale per
  sign_up, login, project_created, module_activated.
- **Crediti spesi**: le operazioni che scalano crediti sono AJAX/SSE/cron, spesso con la sessione chiusa, quindi niente
  coda. Il badge dei crediti già rilegge il saldo dopo ogni POST fetch/XHR, alla chiusura degli stream SSE e all'apertura
  di ogni pagina: la stessa chiamata `GET /credits/balance?since=ID` torna anche le righe di `usage_log` con `id > since`
  e il JS le manda come `credits_spent`. `since` sta in `localStorage.ainstein_usage_since`; alla prima visita il server lo
  semina con l'ultimo id dell'utente (`Analytics::usageSeed()`), così la storia passata non viene rimandata.
  Conseguenza: i crediti spesi dai cron (articoli automatici, rank check notturno) arrivano in GA4 alla prima pagina
  che l'utente apre dopo, non nel momento della spesa.

## File toccati

| File | Cosa fa |
|------|---------|
| `core/Analytics.php` | tutto il cervello: ID, abilitazione, coda eventi, snippet, banner, usage |
| `shared/views/components/cookie-banner.php` | banner consenso (CSS inline, funziona anche fuori Tailwind) |
| `shared/views/layout.php` | snippet in `<head>`, banner prima di `</body>`, sync `credits_spent` dentro `refreshCredits()` |
| `shared/views/docs/layout.php`, `public/landing5.php`, `public/landing4.php`, `public/pricing.php` | snippet + banner |
| `admin/views/settings.php`, `admin/controllers/AdminController.php` | campo e whitelist del setting |
| `public/index.php` | eventi login/sign_up, endpoint `/credits/balance?since=` |
| `controllers/OAuthController.php`, `controllers/GlobalProjectController.php` | eventi Google, progetto, modulo |
| `shared/views/docs/cookies.php` | Cookie Policy: GA4 con consenso, `_ga` 13 mesi |

## Come leggere i report

Nella proprietà sono già definite (Amministrazione → Definizioni personalizzate):
dimensioni evento **Modulo** (`module`), **Operazione** (`operation`), **Metodo accesso** (`method`);
metrica evento **Crediti spesi** (`credits`, unità standard). Da quel momento compaiono in Esplora e nei report.

| Domanda | Dove guardare |
|---------|---------------|
| Quante visite oggi, da dove (LinkedIn?) | Report → Acquisizione → Acquisizione traffico (sorgente/mezzo `linkedin.com / referral`) |
| Quanti si sono iscritti | Report → Coinvolgimento → Eventi → `sign_up` (dimensione Metodo accesso: email vs Google) |
| Quanti hanno fatto il controllo gratuito e con che esito | Eventi → `free_check`, parametro `result` |
| Quanti progetti e quali moduli | Eventi → `project_created`, `module_activated` per Modulo |
| Quanti crediti spesi e dove | Esplora → tabella libera: dimensione Modulo (e Operazione), metrica Crediti spesi; conteggio eventi `credits_spent` |
| Funnel visita → controllo → iscrizione → progetto | Esplora → Esplorazione canalizzazione con page_view, free_check, sign_up, project_created |
| In tempo reale (giorno del post) | Report → Tempo reale |

**Da fare a mano in GA4 quando gli eventi compaiono nell'elenco (entro 24 h dal primo invio):** Amministrazione → Eventi →
scheda "Eventi chiave" / "Eventi recenti" → stella su `sign_up` e `project_created`. Facoltativo: Amministrazione →
Raccolta dati → "Definisci traffico interno" con l'IP dell'ufficio per escludere le visite di Clemente non admin.

## Verifica fatta (2026-10-16, locale con DebugView)

Con un utente non admin creato apposta (`ga4-test@seo-toolkit.local`, solo DB locale): `page_view`, `sign_up` (email),
`project_created`, `module_activated` (ai-reputation), `credits_spent` (2 righe simulate in usage_log),
`free_check` (ainstein.it, 5/5 good), `pricing_view`, `login` (email) visti in DebugView / dataLayer. Admin: nessun tag.
Pagine /docs: una sola volta lo snippet anche se oggi quelle pagine hanno due `<head>` (layout.php annidato in
docs/layout.php, bug preesistente, segnalato).

## In produzione (dal 2026-10-16 sera)

Online con il deploy della sessione "Ainstein Online": l'ID è stato inserito nella tabella `settings` di prod via SQL e la
cache file svuotata (`storage/cache/ainstein`, di www-data: serve sudo). **Nota:** dopo un INSERT/UPDATE diretto in
`settings` la cache va svuotata a mano (Admin → Cache, o cancellare la cartella); salvando da Admin → Impostazioni lo fa da sola.

Restano a Clemente: 1) aprire ainstein.it in una finestra anonima, accettare il banner e guardare GA4 → Tempo reale;
2) entro 24 h la stella su `sign_up` e `project_created` in Amministrazione → Eventi.
