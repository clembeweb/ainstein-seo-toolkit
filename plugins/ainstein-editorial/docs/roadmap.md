# Roadmap & Implementation Plan — Ainstein Editorial

> Piano di esecuzione fase per fase. Da seguire in ordine. Ogni milestone ha task espliciti + Definition of Done.
> Per il contesto strategico vedi `README.md`. Per dettagli tecnici vedi `design.md`. Per decisioni storiche vedi `decisions.md`.
>
> **Stato corrente**: 2026-05-12 → fine fase 0 (design completato), pronto per fase 1 (validation).

---

## Visione complessiva

```
Fase 0: Design & Documentazione                ✅ COMPLETATA (2026-05-12)
Fase 1: Validation pre-development             ⏳ 2 settimane
─── DECISION GATE ───
Fase 2: MVP Development                        ⏳ 14 settimane
   M1: Foundation backend + plugin shell       ⏳ 3 settimane
   M2: Content Brain + onboarding              ⏳ 2 settimane
   M3: Article generation flow + UX            ⏳ 3 settimane
   M4: Editorial Plan + Auto-pilot             ⏳ 3 settimane
   M5: Internal linking + meta + image         ⏳ 2 settimane
   M6: Polish + email + billing integration    ⏳ 1 settimana
Fase 3: Closed beta                            ⏳ 2 settimane
Fase 4: Public launch                          ⏳ 1 settimana
Fase 5: Post-launch optimization               ⏳ Continuo
```

**Timeline totale stimata**: 20 settimane (5 mesi) dal kickoff effettivo al public launch.

---

## Fase 0 — Design & Documentazione ✅ COMPLETATA

**Data completamento**: 2026-05-12

**Output prodotti:**
- [x] `README.md` — Vision strategica
- [x] `CLAUDE.md` — Istruzioni Claude per continuità sessioni + 6 comandi utente
- [x] `docs/decisions.md` — 21 ADR cronologici della sessione brainstorming
- [x] `docs/design.md` — Design tecnico completo (architettura, DB, API, UX)
- [x] `docs/roadmap.md` — Questo documento

---

## Fase 1 — Validation pre-development (2 settimane)

**Obiettivo**: validare willingness-to-pay del target prima di investire 14 settimane di sviluppo.

### 📦 Validation Kit pronto

Tutti i deliverable per eseguire la Fase 1 sono già scritti in `docs/validation/`:
- ✅ `01-naming-research.md` — 12 nomi analizzati + top 3 raccomandati
- ✅ `02-landing-page-copy.md` — Copy completo per Carrd/Framer
- ✅ `03-survey-questions.md` — 5 domande + 5 email automation
- ✅ `04-ads-copy.md` — 6 FB ads + 4 Google ads + targeting
- ✅ `05-outreach-templates.md` — LinkedIn + FB + DM templates
- ✅ `06-interview-script.md` — Canovaccio interviste 15-20 min
- ✅ `07-validation-tracker.md` — Google Sheets template + decision gate
- ✅ `validation/README.md` — Indice navigabile + sequenza esecuzione

**Budget out-of-pocket Fase 1**: ~€450 (domini + Carrd Pro + €200 ads).
**Effort founder**: ~40 ore distribuite su 14 giorni.

### Settimana 1 — Setup validation

- [ ] **Scelta nome brand finale** — vedi `docs/validation/01-naming-research.md`
  - Top 3 raccomandati: **Scribo** (87/100), **Penna** (83/100), **Inkly** (83/100)
  - Output: nome finale + dominio acquistato (es. `.com`)
- [ ] **Landing page** (Carrd / Framer / Webflow — no dev)
  - Hero: tagline + value prop + screenshot mockup (anche fittizio)
  - 3 sezioni: "Come funziona", "Per chi è", "Pricing"
  - CTA: email signup waitlist "Voglio l'accesso anticipato"
  - Footer: "An Ainstein product"
- [ ] **Email capture** integrato (Mailchimp/ConvertKit free tier)
- [ ] **Survey** post-signup (5 domande):
  1. Hai un blog/magazine WordPress? (Sì/No)
  2. Quanti articoli al mese pubblichi attualmente?
  3. Quanto pagheresti per un plugin che fa tutto al posto tuo? (€0-19 / €20-49 / €50-99 / €100+ / nulla)
  4. Quale problema ti darebbe più sollievo? (mancanza tempo / non so cosa scrivere / SEO / qualità)
  5. Useresti GSC integration se presente? (Sì / No / Non so cos'è)
- [ ] **3-5 interviste qualitative** con utenti target trovati su LinkedIn/Twitter italiani
  - 15 minuti, video call, Q&A semi-strutturato
  - Domande chiave: workflow attuale, frustrazioni, willingness-to-pay, deal-breaker

### Settimana 2 — Traffic & ads

- [ ] **Ads test budget €100-200**:
  - Facebook Ads: target piccoli imprenditori italiani 30-60 con interesse WordPress/marketing/SEO
  - Google Ads: keyword "AI scrittore articoli SEO", "plugin SEO WordPress automatico"
  - Budget €5/giorno per 14 giorni su entrambi
- [ ] **Outreach organico**:
  - Post su LinkedIn (profilo proprietario) — 2 post sul "perché stiamo costruendo X"
  - Post in 5-10 gruppi Facebook (Imprenditori WP italiani, blog/magazine owner)
  - DM a 20 freelance SEO italiani LinkedIn con domanda diretta su interesse
- [ ] **Monitoring**:
  - Tracking signup waitlist daily
  - Survey responses → Google Sheets
  - Interviste → note + insights estratti

### Decision Gate (fine settimana 2)

**Criteri di approvazione**:
- ✅ ≥ 100 signup waitlist → **GO**, procedi con MVP full speed
- ⚠️ 50-99 signup waitlist → **GO con riserva**, re-evaluation scope (forse riduci feature set, rivedi pricing)
- ❌ < 50 signup waitlist → **STOP**, ripensa target/positioning/idea. Possibili pivot:
  - Cambiare target (es. agency-only)
  - Cambiare prodotto (es. tool standalone web, non plugin)
  - Cambiare market (es. EN-first)

**Definition of Done Fase 1**: report 2 pagine con numeri raccolti + raccomandazione GO/STOP + eventuali modifiche scope.

---

## Fase 2 — MVP Development (14 settimane)

> NOTA: la stima è per **1 dev full-time** (40h/settimana). Se part-time o multi-dev, scalare.

### Milestone M1 — Foundation (settimane 1-3)

**Obiettivo**: scheletro backend + plugin attivabile + sistema licenze funzionante.

#### Backend setup
- [ ] **Database migration** `aied_*` tables (vedi `design.md` §4)
  - Crea tutte le 9 tabelle
  - Indexes + foreign keys
  - Migration eseguibile + rollback script
- [ ] **API skeleton** `seo-toolkit/api/editorial/`
  - `routes.php` con tutti gli endpoint definiti (anche placeholder)
  - Middleware `LicenseAuthMiddleware`, `QuotaMiddleware`, `RateLimitMiddleware`
  - Base controller con response helpers
- [ ] **Lemon Squeezy integration** setup
  - Account creato su Lemon Squeezy
  - Store configurato con 4 tier (Starter, Pro, Business, Agency) + 3 top-up packs
  - License Keys variants attivate
  - Webhook endpoint configurato + signature secret
  - Test mode validato
- [ ] **Activation endpoint** `/activate` funzionante
  - Validazione license key via LS API
  - Creazione `aied_users` + `aied_sites`
  - Generazione JWT api_token

#### Plugin shell
- [ ] **Plugin skeleton** `plugins/ainstein-editorial/src/`
  - Plugin header WP compliant
  - Autoloader composer + namespace `Ainstein\Editorial`
  - Activation/Deactivation hooks
  - Settings page minimale: "License key" + bottone "Attiva"
  - ApiClient class (wp_remote_* wrapper)
  - LicenseManager class
- [ ] **Build script** `build.sh` funzionante
  - Compila Tailwind (anche minimal per ora)
  - Esclude `tests/`, `docs/`, `node_modules/`
  - Output zip versionato

#### Testing M1
- [ ] Test attivazione plugin su sito WP locale (Laragon)
- [ ] Test activation con license key Lemon Squeezy test mode
- [ ] Verifica creazione record DB `aied_users` + `aied_sites`
- [ ] Test renew api_token automatico

**Definition of Done M1**:
- ✅ Plugin installa correttamente su WP 6.0+
- ✅ License attivazione end-to-end funziona (Lemon Squeezy → backend → plugin)
- ✅ DB tabelle create con dati di test
- ✅ Almeno 1 endpoint backend testabile via curl
- ✅ Build .zip prodotto installabile

---

### Milestone M2 — Content Brain + Onboarding (settimane 4-5)

**Obiettivo**: utente attivato può completare onboarding e plugin "impara" il suo sito.

#### Backend
- [ ] **`ContentBrainService`** in `api/editorial/services/`
  - Metodo `scanSite(siteId, articleUrls)`
  - Riusa `ScraperService` per fetch articoli
  - Riusa `AiService` per analisi brand voice
  - Salva risultati in `aied_content_brain`
- [ ] **Endpoint `POST /content-brain/scan`** con SSE stream
- [ ] **Endpoint `GET/PUT /content-brain`** per editing manuale

#### Plugin
- [ ] **Admin page "Ainstein Editorial"** registrata in WP menu
- [ ] **Onboarding wizard** (5 step come da design §7.2)
  - Step 1: textarea topic sito
  - Step 2: textarea audience
  - Step 3: 5 card visive selezione tono
  - Step 4: slider posts_per_month
  - Step 5: textarea editorial guidelines
  - "Inizia magia" button → trigger Content Brain scan via SSE
- [ ] **SSE consumer** in admin page con progress bar visuale
- [ ] **Admin page "Content Brain"** per edit successivo dei dati salvati

#### UX/Design polish
- [ ] **Tailwind compilato** con palette finale
- [ ] **Heroicons SVG** sprite caricato
- [ ] Componenti riusabili: `.aied-card`, `.aied-btn-primary`, `.aied-input`, `.aied-modal`

**Definition of Done M2**:
- ✅ Utente attivato vede onboarding al primo accesso
- ✅ Scan brand voice funziona su sito reale (test su 3 siti italiani diversi)
- ✅ Dati Content Brain visualizzati e editabili
- ✅ UX wizard fluida e gradevole (test con persona non-tech)

---

### Milestone M3 — Article generation flow (settimane 6-8)

**Obiettivo**: utente può generare articolo manualmente con tutti gli step "WOW" (streaming, SERP preview, brief approval, quality score).

#### Backend
- [ ] **`ArticleController::generate`** endpoint completo
  - Riusa `SerpApiService` per SERP analysis
  - Riusa `ScraperService` per fonti
  - Riusa `BriefBuilderService` (con `$contentBrain` injection)
  - Riusa `ArticleGeneratorService` (con `$contentBrain` injection)
  - Genera meta-tag via AI
  - Genera cover image via Gemini
  - Salva in `aied_articles`
  - Aggiorna `aied_actions_log`
- [ ] **SSE stream** con eventi: `serp_extracted`, `sources_scraped`, `brief_built`, `article_writing`, `article_completed`, `meta_generated`, `image_generated`
- [ ] **Endpoint `GET /articles`** lista paginata
- [ ] **Endpoint `GET /articles/{id}`** dettaglio
- [ ] **Endpoint `POST /articles/{id}/publish-to-wp`** crea draft via WP REST API utente

#### Plugin
- [ ] **Admin page "Articoli"** con lista (Tailwind table)
- [ ] **Form "Genera ora"** con keyword input + advanced options
- [ ] **Modal full-screen "Generazione"** con:
  - Steps animati con check marks progressivi
  - **Streaming text con cursor blinking** (effetto WOW)
  - SERP preview cards (clickable per tooltip)
  - Brief approval inline editor (Alpine.js)
- [ ] **Pagina dettaglio articolo** con preview + bottoni "Pubblica come bozza" / "Rigenera" / "Modifica brief"
- [ ] **WP draft creation** via wp_insert_post

#### Quality
- [ ] **Quality score gauge** animato post-generation
- [ ] **Pulsanti share preview** (per beta utenti, share preview link)

**Definition of Done M3**:
- ✅ Generation end-to-end funziona su 3 siti reali (test diverse keyword)
- ✅ Streaming text fluido senza lag percepibile
- ✅ Articolo pubblicato come draft visibile in WP editor
- ✅ Cover image generata e attached al draft
- ✅ Meta-tag inseriti correttamente (compatibili con Yoast/RankMath se installati)
- ✅ Costo medio articolo trackato in `aied_articles.ai_cost_eur` < €0.15

---

### Milestone M4 — Editorial Plan + Auto-pilot (settimane 9-11)

**Obiettivo**: utente può generare piano editoriale completo e attivare auto-pilot settimanale.

#### Backend
- [ ] **`KeywordResearchController::research`** endpoint
  - Riusa `KeywordInsightService` per volumes
  - Riusa `SerpApiService` per intent classification
  - AI scoring opportunity (low-hanging fruit detection basato su difficulty + volume)
  - SSE stream con progress
- [ ] **`EditorialPlanController`** CRUD endpoints
  - POST crea plan + items
  - PUT update items (reschedule, skip, modify keyword)
  - POST activate → marca plan active
- [ ] **Cron dispatcher backend** `cron/editorial-autopilot-dispatcher.php`
  - Per ogni sito con plan attivo
  - Trova prossimo item pending
  - Esegue generation in background
  - Crea WP draft via REST API utente
  - Email weekly digest

#### Plugin
- [ ] **Admin page "Idee keyword"** — lista keyword research history + bottone "Genera nuove"
- [ ] **Modal SSE keyword research** con animazioni visuali (globe spinning, etc.)
- [ ] **Lista keyword visuali** con badge difficulty + volume linguaggio umano + toggle includi/escludi
- [ ] **Admin page "Calendario editoriale"** — calendario mensile visuale (drag-drop?)
- [ ] **Bottone "Approva e attiva"** → activate plan + setup WP cron
- [ ] **Bottone "Pausa piano"** → suspend cron senza cancellare items

#### Cron setup
- [ ] **WP Cron event `aied_weekly_autopilot`** registrato all'activation
- [ ] **Custom WP-Cron handler** che chiama backend dispatcher
- [ ] **Fallback**: documentazione per setup server-side cron per WP low-traffic

**Definition of Done M4**:
- ✅ Keyword research funziona su 3 topic diversi (sport, food, tech) → output sensato
- ✅ Plan creato + items scheduled correttamente
- ✅ WP Cron triggera autopilot → articolo generato e bozza in WP automaticamente
- ✅ Email weekly digest ricevuta correttamente

---

### Milestone M5 — Internal linking + polish features (settimane 12-13)

**Obiettivo**: completare features differenzianti (internal linking bidirezionale) + polish features minori (meta-tag bulk, image regeneration).

#### Internal linking
- [ ] **`InternalLinkerService`** in `api/editorial/services/`
  - Algoritmo §10 design.md
  - Candidate selection con scoring
  - Forward links insertion
  - Backward links con snapshot + tag `data-aied-link`
- [ ] **Endpoint `POST /links/suggest`** + **`POST /links/apply`**
- [ ] **UI plugin "Articoli modificati"** con audit log + bottone undo per ogni link
- [ ] **wp_postmeta `_aied_no_modify`** opt-out per articoli

#### Meta-tag bulk
- [ ] **Endpoint `/meta/generate-bulk`** (opzionale v1.0, se time permits)
- [ ] **Plugin UI** "Genera meta-tag mancanti su tutti gli articoli"

#### Image regeneration
- [ ] **Endpoint `/articles/{id}/image/regenerate`** con prompt custom
- [ ] **UI plugin** "Rigenera cover image" sull'articolo

**Definition of Done M5**:
- ✅ Internal linking inserito correttamente in 5 articoli test (forward + backward)
- ✅ Audit log mostra link inseriti con context snippet
- ✅ Undo link funziona correttamente
- ✅ Opt-out `_aied_no_modify` rispettato

---

### Milestone M6 — Polish + Email + Billing (settimana 14)

**Obiettivo**: finalizzare tutti i touchpoint utente (email, fatturazione, dashboard).

#### Email infrastructure
- [ ] **Template email** in `shared/views/emails/editorial/`:
  - `welcome.php` — Post-attivazione
  - `weekly-digest.php` — "Ho scritto X questa settimana"
  - `monthly-report.php` — Stats + suggerimenti
  - `quota-warning.php` — "90% quota mensile usata"
  - `license-recovery.php` — Re-send license key
- [ ] **Cron settimanale + mensile** per invio
- [ ] **Logging `aied_email_log`**
- [ ] **Unsubscribe** link in ogni email (preferenze per tipo)

#### Billing integration
- [ ] **Admin page "Impostazioni → Billing"** con bottone "Gestisci abbonamento"
- [ ] **Endpoint `/subscription/portal-url`** → ritorna URL Lemon Squeezy customer portal
- [ ] **Webhook handlers** per: subscription_updated (tier change), subscription_cancelled, order_created (top-up)
- [ ] **Top-up balance** visualizzato in dashboard
- [ ] **Quota warning email** automatica a 80% e 95% usage

#### Dashboard finale
- [ ] **Admin page "Dashboard"** main:
  - KPI cards: articoli generati, articoli pubblicati, internal links inseriti, quota residua
  - Grafico articoli generati ultimi 30gg
  - Prossimi 3 articoli scheduled
  - Quick action "Genera ora"
- [ ] **Empty states** illustrati custom (SVG)

#### Polish finale
- [ ] **Test cross-browser** (Chrome, Firefox, Safari, Edge)
- [ ] **Test responsive** (admin mobile, tablet)
- [ ] **Test compatibility** con: Yoast SEO, RankMath, WP Rocket, Elementor (top plugin)
- [ ] **i18n** completato per IT (file .po + .mo generati)
- [ ] **Plugin Check** ufficiale WordPress eseguito + issues risolti

**Definition of Done M6**:
- ✅ Tutte le 4 email triggered correttamente
- ✅ Billing portal accessibile e funzionante
- ✅ Dashboard mostra dati real-time
- ✅ Plugin passa WP Plugin Check senza warning critici
- ✅ Build finale prodotta (ainstein-editorial-v1.0.0.zip)

---

## Fase 3 — Closed Beta (2 settimane)

**Obiettivo**: 30 utenti reali testano il prodotto, raccogliamo feedback, fixiamo bug critici.

- [ ] **Selezione 30 beta tester** dalla waitlist (preferenza per profili diversi: blogger + small business + freelancer)
- [ ] **Onboarding email** con istruzioni + license key beta (gratis 30gg)
- [ ] **Setup canale feedback**: Discord/Slack/Email — opzioni:
  - Discord server con #general, #bug-reports, #suggestions
  - Form Typeform per bug structured
- [ ] **Daily monitoring**:
  - Verifica error rates `aied_api_logs`
  - Verifica costi AI vs revenue (se sostenibile)
  - Verifica utilizzo features (quale è più usata?)
- [ ] **Settimanale survey email** ai beta tester (3 domande): cosa funziona, cosa no, would-you-pay
- [ ] **Bug fix sprint**: priorità issues bloccanti
- [ ] **Iteration su UX** in base feedback (es. modifiche micro su onboarding wizard)

**Decision Gate fine beta**:
- ≥ 50% beta utenti darebbero NPS ≥ 7 → GO public launch
- 30-49% NPS ≥ 7 → 1-2 settimane fix extra prima del launch
- < 30% NPS ≥ 7 → ripensare scope (feature mancante? prezzo? UX broken?)

**Definition of Done Fase 3**: report beta 3 pagine con metriche + insights + raccomandazione launch.

---

## Fase 4 — Public Launch (1 settimana)

**Obiettivo**: lancio pubblico con strategie acquisizione organica + paid.

- [ ] **Sito prodotto finalizzato** con copy a regime, screenshot reali, video demo 60s
- [ ] **Pricing page** dettagliata con FAQ
- [ ] **Affiliate program setup** (anche se v2, getta basi: tier Agency menziona "earn 20% recurring")
- [ ] **Press kit** (logo, screenshot HD, descrizioni breve/lunga, founder bio)
- [ ] **Launch checklist**:
  - [ ] Email waitlist (100+ contatti) — "Siamo live!"
  - [ ] **ProductHunt launch** (preparato 1 settimana prima con hunters)
  - [ ] Post LinkedIn personale founder
  - [ ] 5 post in gruppi Facebook target
  - [ ] DM a 20 micro-influencer settore WP italiano (gratis per articolo recensione)
  - [ ] Reddit posts in r/Wordpress, r/SEO, r/ItalyHasNotInternet (con link nofollow)
  - [ ] Ads Facebook/Google scale-up (budget €500-1000 prima settimana)
- [ ] **Monitoring 24/7** prima settimana: error rates, signup rate, churn

**Definition of Done Fase 4**:
- ✅ Sito live con CTA funzionante
- ✅ 50+ signup primo giorno
- ✅ Almeno 5 paying customer prima settimana
- ✅ Sistema operations stabile (error rate < 1%)

---

## Fase 5 — Post-launch optimization (continuo)

**Obiettivo**: ottimizzare conversion, ridurre churn, espandere features.

### Continuo (settimanale)
- [ ] **Weekly review**: signup, paying customers, MRR, churn, NPS
- [ ] **Bug triage**: issues utenti
- [ ] **Content marketing**: 1 post blog SEO/settimana sul sito prodotto
- [ ] **Social proof**: case study con clienti che hanno avuto buoni risultati

### v1.1 (mese 2-3 post-launch)
Vedi `design.md` §17. Priorità:
1. **GSC integration** (sblocco tier Business value reale)
2. **Auto-publish opt-in** (richiesta probabile da beta feedback)
3. **Editor inline** (smooth UX)

### v1.2+ vedi `design.md` §17

---

## Risk Register

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| Validation fallisce (<50 signup) | Media | Alto | Pivot scope/target. Costo: 2 settimane perse, no codice scritto |
| WP Cron unreliable su siti low-traffic | Alta | Medio | Server-side cron fallback (backend chiama plugin) |
| AI cost > revenue su power user heavy | Bassa | Alto | Quota strict + top-up obbligatorio + analytics per soglia |
| Competitor copia features in 6 mesi | Alta | Medio | Velocità execution + Content Brain è lock-in difficile da replicare |
| Lemon Squeezy downtime → plugin spento | Bassa | Alto | Cache license validation 1h locale + grace period 24h |
| WP marketplace listing rifiutato (premium plugin) | Alta | Basso | Distribuzione dal sito proprio, no marketplace WP.org |
| Bug critico dopo launch → refund massivo | Media | Alto | Closed beta 2 settimane + monitoring 24/7 prima settimana |
| Brand confusion utente "chi è Ainstein?" | Bassa | Medio | "By Ainstein" stamp discreto, brand prodotto domina UI |

---

## Definition of Done globale

Il progetto si considera "Pronto per public launch" quando:
- ✅ Tutte le milestone M1-M6 completate
- ✅ Closed beta superata con NPS ≥ 7 nel 50%+ dei beta tester
- ✅ Documentazione utente completa (help docs + video tutorial 5-10 min)
- ✅ Sistema operations testato in produzione (deploy, rollback, backup, monitoring)
- ✅ 30+ articoli generati end-to-end senza intervento umano critico
- ✅ Cost-per-article < €0.20 sostenibile per tutti i tier
- ✅ Customer support process definito (email/chat + SLA risposta)
- ✅ Termini di servizio + privacy policy + cookie policy in italiano + inglese
- ✅ Pagamenti testati in produzione (transaction reale, refund testato)

---

*Fine roadmap. Aggiornare con `/editorial-save` a fine di ogni sessione produttiva.*
