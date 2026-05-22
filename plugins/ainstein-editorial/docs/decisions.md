# Decision Log — Ainstein Editorial

> Cronologia di tutte le decisioni architetturali e strategiche prese durante lo sviluppo del prodotto.
> Format: ADR-like (Architecture Decision Record) semplificato.
> Ogni nuova decisione importante va appenduta qui con `/editorial-decision <testo>` o manualmente.

---

## Convenzioni

- **Status**: Proposed · Accepted · Deprecated · Superseded
- **Context**: situazione / problema che ha portato alla decisione
- **Decision**: cosa abbiamo deciso
- **Alternatives considered**: opzioni scartate (e perché)
- **Consequences**: pro/contro e implicazioni
- **Superseded by**: link a ADR successiva che cambia questa (se applicabile)

---

## ADR-001: Modello business ibrido (subscription + top-up)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Serve definire come monetizzare il prodotto. Le opzioni principali per un SaaS plugin sono: BYOK (utente paga licenza, usa sue API key), subscription puro a fasce, pay-as-you-go a crediti, ibrido.

**Decision**: Modello ibrido — abbonamento mensile a fasce + boost pack top-up a consumo. UX user-facing che parla di "azioni" (articoli/meta/immagini) non di "crediti" per accessibility a target non-tech.

**Alternatives considered**:
- BYOK (escluso esplicitamente dal proprietario: "non vorrei che l'utente usasse le sue chiavi API")
- Solo subscription: lascia per terra margine sui power user
- Solo PAYG: revenue volatile, niente MRR, multipli aziendali bassi

**Consequences**:
- ✅ MRR ricorrente (valore aziendale alto, multipli 5-10x su exit)
- ✅ Riuso 100% di `Credits::*` infrastructure di Ainstein
- ✅ Cattura sotto-utilizzatori (base bassa, non escono) E heavy user (top-up margine)
- ⚠️ UX leggermente più complessa da spiegare (mitigato chiamando "azioni" non "crediti")

---

## ADR-002: CMS scope WP-first, multi-CMS dopo

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: I 4 connettori CMS (`WordPressConnector`, `ShopifyConnector`, `PrestaShopConnector`, `MagentoConnector`) sono già in `modules/content-creator/services/connectors/`. Tentazione di partire multi-CMS dal giorno 1.

**Decision**: MVP solo WordPress. Multi-CMS (Shopify/PrestaShop/Magento) come fast-follow successivo, ognuno come prodotto separato della famiglia Ainstein.

**Alternatives considered**:
- Multi-CMS dal giorno 1: complessità di marketplace diversi, stack diversi, ciclo di rilascio diversi → triplica manutenzione per solo dev
- Solo SaaS standalone (no plugin): perde distribuzione massiva del marketplace WP

**Consequences**:
- ✅ Focus profondo su 1 mercato (WP = 43% del web)
- ✅ Time-to-market drasticamente più veloce
- ✅ Pattern testato → poi replicato facilmente su altri CMS (i connettori sono già pronti, fa solo da SDK adapter)

---

## ADR-003: MVP scope = Core (articolo + meta + immagine + auto-pilot + content brain + internal linking)

**Date**: 2026-05-12 · **Status**: Accepted (superseded da ADR-010 per inclusione auto-pilot)

**Context**: Definire feature set MVP. Tre opzioni iniziali: Lean (solo articolo), Core (+ meta + immagini), Full (+ auto-pilot).

**Decision iniziale**: Core MVP — articolo + meta-tag bulk + cover image AI. Auto-pilot come v1.1.

**Decision finale (dopo discussione positioning)**: Core MVP **include** auto-pilot perché è la promessa centrale del prodotto. Vedi ADR-010.

**Alternatives considered**:
- Lean MVP (solo articolo): differenziazione bassa vs competitor
- Full con auto-pilot: time-to-launch ~14 settimane → ok dato il valore

**Consequences**:
- ✅ Tier di pricing chiari giustificati dal valore (Starter/Pro/Business/Agency)
- ✅ Auto-pilot diventa core selling point invece di "feature aggiuntiva"
- ⚠️ Sviluppo MVP più lungo (~16 settimane stimate)

---

## ADR-004: Architettura Setup 3 — Multi-tenant shared backend

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Come strutturare backend e brand. Tre opzioni: (1) "Ainstein for WordPress" riuso totale, (2) brand nuovo + backend separato fork, (3) brand nuovo + backend condiviso multi-tenant.

**Decision**: Setup 3 — brand consumer-facing nuovo ("Ainstein Editorial" o nome finale TBD) + backend condiviso con Ainstein in namespace separato (tabelle `aied_*`, endpoint `/api/editorial/*`, utenti `aied_users` separati da `users` Ainstein).

**Alternatives considered**:
- Setup 1: confusione brand per utente WP (non sa cos'è Ainstein), lock-in dei due prodotti
- Setup 2: 2 codebase che divergono, 2 deploy, 2 monitoring → insostenibile per solo dev

**Consequences**:
- ✅ Branding pulito + zero overhead infra
- ✅ Riuso 100% di backend (AiService, ScraperService, SerpApiService, etc.)
- ✅ Pattern replicabile per futuri plugin della famiglia "by Ainstein"
- ⚠️ Complessità leggera in feature flagging cosa appare dove (gestibile)

---

## ADR-005: Brand strategy "House of Brands"

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Posizionamento del brand. Il proprietario ha visione di Ainstein come parent che produce una famiglia di prodotti verticali, ognuno con suo brand consumer-friendly.

**Decision**: Pattern "House of Brands" come Automattic (WordPress/WooCommerce/Tumblr/Akismet), 37signals (Basecamp/Hey/Once). Plugin nome consumer-friendly (es. "Ainstein Editorial", "Ainstein Magazine" o nome final TBD) + "An Ainstein product" come parent stamp.

**Alternatives considered**:
- Branded house ("Ainstein X" ovunque): brand recognition migliore quando parent è già noto, ma Ainstein non lo è ancora → confusion per utente WP
- Brand totalmente disconnesso: perde leverage cross-prodotto

**Consequences**:
- ✅ Permette lancio futuro di altri prodotti verticali con stessa kitchen
- ✅ Brand del singolo prodotto può evolvere autonomo
- ✅ Quando Ainstein crescerà come parent, retroattivamente i prodotti beneficiano del brand awareness
- ⚠️ All'inizio "by Ainstein" non aggiunge valore percepito (Ainstein non è ancora noto)

---

## ADR-006: Pagamenti via Lemon Squeezy (Merchant of Record)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Vendere worldwide a basso ticket (€19-149/mo) richiede gestione fiscale internazionale (MOSS UE, US sales tax stato per stato). Opzioni: Stripe direct (compliance fai-da-te) o MoR (Paddle, Lemon Squeezy).

**Decision**: Lemon Squeezy come Merchant of Record. Loro incassano, pagano IVA/tax in ogni paese al posto nostro, versano netto.

**Alternatives considered**:
- Stripe direct: fee bassa (~2.9% vs 5%) ma incubo compliance multi-paese, necessità commercialista internazionale o Quaderno (~$50/mo), non scalabile oltre 100 clienti senza burnout
- Paddle: alternativa a Lemon Squeezy, più maturo per scale ma UX dashboard meno indie-friendly

**Consequences**:
- ✅ Zero pensieri tax/compliance internazionale
- ✅ License Keys API built-in (gestione attivazione per dominio)
- ✅ Customer portal incluso (utente gestisce billing da solo)
- ✅ Setup in 1 giorno vs 2-3 mesi di configurazione legal/contabile
- ⚠️ Fee 5% + $0.50/transaction vs ~2.9% Stripe (≈ €0.60 extra su €20/mo = trascurabile)
- ⚠️ Vendor lock-in (mitigabile esportando customer data periodicamente)

---

## ADR-007: Plugin UX = Admin page dedicata (no Gutenberg block nel MVP)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Dove vive il plugin nell'admin WP? Opzioni: admin page wizard, Gutenberg block dentro editor, sidebar Gutenberg + admin page combinati.

**Decision**: Admin page dedicata con wizard step-by-step + dashboard. Pattern replicato da `ai-content/views/wizard/`. Gutenberg sidebar/block valutati per v1.2 (post-MVP).

**Alternatives considered**:
- Gutenberg block puro: Gutenberg API rompe in continuazione (ogni release WP), bulk impossibile, devo imparare React+JSX
- Sidebar Gutenberg + admin page: best of both ma 2 UI da mantenere, raddoppia sviluppo

**Consequences**:
- ✅ Riuso massimo view di `ai-content` (wizard, brief approval, sources display)
- ✅ Stack semplice (PHP + Tailwind + Alpine.js, no React)
- ✅ Agency-friendly (dashboard per gestire più siti, no context-switch in editor)
- ✅ Stabilità multi-anno (admin pages in WP cambiano raramente, Gutenberg si)
- ⚠️ Meno "moderno" come UX rispetto a generation in-editor (mitigato da streaming SSE + design "WOW")

---

## ADR-008: Output mode = Draft-only nel MVP

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Articoli generati dall'AI: salvati come draft o auto-publish? Confidence dell'utente è bassa all'inizio.

**Decision**: Sempre DRAFT in `wp_posts` (`post_status = 'draft'`) nel MVP. Auto-publish solo come opt-in esplicito in v1.1 con disclaimer chiaro + safety net (review obbligatoria primi 5 articoli, poi sblocco).

**Alternatives considered**:
- Auto-publish di default: catastrofico in caso di hallucination AI o errore brand voice
- Schedule pubblicazione (calendar-based): troppo complesso per MVP, può venire in v1.1

**Consequences**:
- ✅ Sicurezza massima utente, zero rischio "AI ha pubblicato cazzata sul mio sito live"
- ✅ Feedback loop: utente vede draft, edita, capisce limiti AI → migliora setup Content Brain
- ⚠️ Auto-pilot perde un po' di "magia" (utente DEVE comunque cliccare publish) — accettabile

---

## ADR-009: Quid feature = Content Brain (NON GSC integration nel MVP)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Tra le idee differenziatrici proposte (Content Brain = brand voice scanner + glossario + editorial guidelines; GSC Auto-Pilot = low-hanging fruit detection + closed-loop ROI), quale scegliere come quid principale del MVP?

**Decision**: **Content Brain** nel MVP, GSC integration in v1.1.

**Reasoning critico (post analisi imprenditoriale)**:
- Content Brain: zero friction onboarding (no OAuth), "wow moment" immediato, ~1 settimana dev, risuona con TUTTI i profili (A+B+C+D)
- GSC integration: friction alta (OAuth Google + verification GSC), parla solo a profilo C (SEO pro), ~3 settimane dev, closed-loop ROI statisticamente fragile
- Target principale (Mario il commerciante di vino) non sa cos'è GSC

**Alternatives considered**:
- Solo GSC: parla solo a 30% del mercato (SEO pro + agency), exclude 70%
- Entrambi nel MVP: ritarda launch di 3+ settimane

**Consequences**:
- ✅ MVP più snello, accessibile a tutto lo spettro target
- ✅ Content Brain = lock-in puro (utente "addestra" il plugin sul suo sito, switching cost alto)
- ✅ GSC arriva in v1.1 come "upgrade Pro+" → giustifica tier alti
- ⚠️ Differenziazione vs competitor leggermente più sottile all'inizio (recuperata da auto-pilot full)

---

## ADR-010: Auto-pilot integrato nel MVP (NON in v1.1)

**Date**: 2026-05-12 · **Status**: Accepted · **Supersedes**: ADR-003 (parziale)

**Context**: Inizialmente proposto auto-pilot come v1.1 fast-follow. Dopo discussione sul positioning del prodotto ("il tuo SEO Pro autonomo"), riconsiderazione.

**Decision**: Auto-pilot è feature CORE del MVP, non opzionale. La promessa centrale del prodotto è "fa tutto da solo". Senza auto-pilot, il positioning crolla.

**Alternatives considered**:
- Auto-pilot in v1.1: incoerente con posizionamento "autonomo"; competitor potrebbero coprire il gap in 6 mesi

**Consequences**:
- ✅ Coerenza tra promessa marketing e prodotto reale
- ✅ Lock-in massimo (utente carica calendario 3 mesi, churn quasi impossibile)
- ⚠️ +3-4 settimane di sviluppo MVP
- ⚠️ Infra cron robusta richiesta dal giorno 1 (gestibile, Ainstein lo fa già)

---

## ADR-011: Internal linking automatico bidirezionale

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Internal linking è componente critico di SEO. Competitor (RankMath, AIOSEO) lo fanno limitatamente (solo suggerimenti manuali). Domanda: nostro plugin scrive solo link da articolo nuovo → vecchi, o anche da vecchi → nuovo?

**Decision**: Internal linking BIDIREZIONALE. Quando AI genera articolo nuovo:
1. Analizza articoli esistenti del sito (sitemap + post pubblicati)
2. Inserisce link contestuali nel nuovo articolo verso articoli vecchi rilevanti (forward links)
3. **Modifica anche articoli vecchi** inserendo link contestuali al nuovo (backward links), max 1-2 link per articolo vecchio, solo dove la rilevanza è alta

**Alternatives considered**:
- Solo forward links: standard nei competitor, mezza soluzione, SEO suboptimale
- Solo suggerimenti manuali: non rispetta la promessa "auto-pilot"
- Update articoli vecchi opzionale: aggiunge complessità UX

**Consequences**:
- ✅ Differenziatore forte (nessun plugin fa questo automaticamente)
- ✅ SEO impact reale (articoli vecchi ricevono link juice dal nuovo)
- ⚠️ Modifica contenuti esistenti dell'utente → richiede:
  - Backup automatico pre-modifica
  - UI "ho modificato 3 articoli vecchi per aggiungere link al nuovo" con review/undo
  - Rispetto del filter "non modificare questi articoli" (opt-out per articolo specifico)

---

## ADR-012: Registrazione invisibile (utente non vede mai Ainstein.it)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Per usare il plugin, l'utente deve essere autenticato al backend. Opzioni: registrazione esplicita su ainstein.it, registrazione su sito prodotto, registrazione invisibile via license key.

**Decision**: Registrazione **silenziosa e invisibile**. L'utente compra plugin, riceve license key via email, incolla nel plugin, e il backend crea automaticamente l'account `aied_users` namespacato. Utente non crea mai password, non vede mai URL "ainstein.it" né "lemonsqueezy.com" (custom domain).

**Alternatives considered**:
- Registrazione esplicita: +3 step nel funnel = drop rate massiccio
- Login OAuth (Google): meno friction ma comunque scelta consapevole

**Consequences**:
- ✅ Friction zero post-acquisto
- ✅ Brand consistency: utente vede solo "Ainstein Editorial" (nome plugin)
- ✅ Account in tabella `aied_users` SEPARATA da `users` Ainstein → permette futura estrazione del prodotto
- ⚠️ Recupero account: se utente perde license key, recovery via email + verifica con Lemon Squeezy
- ⚠️ Dashboard web (opzionale, in v1.1) richiederà magic-link login

---

## ADR-013: Monorepo — plugin in `plugins/ainstein-editorial/`

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Dove sviluppare il plugin. Opzioni: stesso repo `seo-toolkit/`, repo separato, sub-cartella self-contained.

**Decision**: Monorepo. Cartella `plugins/ainstein-editorial/` dentro `seo-toolkit/`. Self-contained (docs, src, tests, build script tutti dentro). Backend changes in `seo-toolkit/api/editorial/` separati.

**Alternatives considered**:
- Repo separato: 2 git remote da gestire, refactor cross-cutting complesso
- Sub-cartella dentro `docs/`: rifiutato dal proprietario (giustamente) per evitare mischio con docs Ainstein

**Consequences**:
- ✅ Refactor backend ↔ plugin facile (stesso editor, stesso git)
- ✅ Single git history per la feature
- ✅ Deploy backend = normale push, deploy plugin = `build.sh` zip
- ✅ Estrazione futura in repo standalone = mezza giornata se necessario
- ⚠️ Repo cresce di dimensione (mitigato: build esclude vendor/, node_modules/, test/)

---

## ADR-014: Pricing tier €29/€69/€149/€349

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Definire pricing concreto. Target ICP small business + blogger non-tech. Competitor: RankMath AI ($5-30/mo), AIOSEO AI ($99-300/anno bundled), Jasper ($39-99/mo), Surfer ($89-219/mo).

**Decision**:

| Tier | Mensile | Yearly (-20%) | Articoli/mese | Siti |
|------|---------|---------------|---------------|------|
| Starter | €29 | €23 | 4 | 1 |
| Pro | €69 | €55 | 12 | 3 |
| Business | €149 | €119 | 30 + GSC (v1.1) | 10 |
| Agency | €349 | €279 | 100 + white-label | 50 |

Top-up packs: +20 articoli €15 · +50 €30 · +100 €50. Articoli inclusi azzerano ogni mese, top-up no scadenza.

**Alternatives considered**:
- Tier più bassi (€9-19): race to bottom, taglia fuori valore percepito
- Tier più alti (€49-249): hook entry-level perso

**Consequences**:
- ✅ Range €29-349 cattura tutto lo spettro target (blogger → agency)
- ✅ "€29 = meno di un caffè al giorno" come tagline acquisizione
- ✅ Margine lordo Starter ~85% (costo reale ~€4 vs €29 revenue)
- ⚠️ Validation pre-launch necessaria per confermare willingness-to-pay (vedi ADR-018)

---

## ADR-015: Target audience principale = small business + blogger (NON SEO pro)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Inizialmente analizzato target come SEO pro + agency (profilo C+D, ~30% del mercato WP). Dopo discussione strategica con proprietario, riconsiderazione.

**Decision**: Target primario = **Mario il commerciante di vino** (profilo A+B = 70% del mercato WP). Piccolo imprenditore o blogger che NON sa SEO, NON pagherebbe consulente, vuole "fa-tutto-da-solo". SEO pro + agency = secondari, catturati da tier Business/Agency.

**Alternatives considered**:
- Target SEO pro: parla a niche già coperta da Surfer/Frase, switching cost alto
- Target generico: messaggio diluito, conversion bassa

**Consequences**:
- ✅ Messaging chiaro: "fa tutto da solo, non devi capire niente"
- ✅ Volume di acquisizione potenziale alto (70% del mercato WP)
- ✅ Onboarding deve essere DESIGN-FIRST (zero terminologia tecnica, esempi visuali, default sensati)
- ⚠️ Support più impegnativo (utenti non-tech fanno domande basic)

---

## ADR-016: Positioning = "Content Marketer AI" NON "SEO Replacement"

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Tentazione di posizionare il prodotto come "il tuo SEO consultant in plugin a 1/7 del prezzo". Analisi critica ha mostrato che SEO consultant fa molto più di content (technical SEO, link building, audit, etc.). Vendere "sostituzione totale" porta a refund + reputation hit.

**Decision**: Positioning = **"Il tuo content marketer AI autonomo per WordPress"**. Onesti su cosa fa il prodotto (content production end-to-end SEO-optimized) e cosa NON fa (technical SEO, link building, schema avanzato, audit).

**Alternatives considered**:
- "Sostituisce il tuo SEO": iperbolico, rischio reputation
- "Tool di content generation": generico, non differenzia

**Consequences**:
- ✅ Promesse mantenibili → review positive → word-of-mouth
- ✅ Tagline "Il tuo SEO Pro autonomo" funziona se "SEO Pro" è interpretato come "professionista del content SEO" (non SEO consultant generale)
- ✅ Differenzia in marketing: "non sostituisce un SEO, sostituisce IL TUO TEMPO a scrivere"

---

## ADR-017: Tagline marketing "meno di un caffè al giorno"

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Tagline accattivante per acquisizione entry-level.

**Decision**: Tagline principale: _"Il tuo SEO Pro autonomo, a meno di un caffè al giorno"_ — riferita al tier Starter €29/mo = €0.97/giorno. Per tier alti, tagline alternative basate su valore (es. "12 articoli al mese, niente fatica" per Pro).

**Alternatives considered**:
- Tagline tecnica ("SEO-optimized AI content"): non risuona con target non-tech
- Tagline aggressiva ("Licenzia il tuo SEO consultant"): controversa, rischio reputation (vedi ADR-016)

**Consequences**:
- ✅ Familiar metric (caffè) per target italiano, conversione mentale immediata
- ✅ Funziona per landing page hero, ads Facebook/Google
- ⚠️ Riferimento solo a Starter — per tier alti tagline diverse (gestibile con segmentazione)

---

## ADR-018: Validation pre-development obbligatoria (gate decisionale)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Sviluppo MVP = ~16 settimane di lavoro full-time. Investimento alto. Rischio: costruire prodotto che non risuona con target reale.

**Decision**: Prima di scrivere una riga di codice plugin, fase di **validation** di 2 settimane:
1. Landing page con waitlist signup (Carrd o simile, no dev)
2. Survey 5 domande ai signup
3. 10 interviste utenti target da LinkedIn/Twitter (15 min cad)
4. Ads test €100-200 (Facebook/Google) per traffico landing

**Gate decisionale dopo 14 giorni**:
- > 100 signup waitlist → procedo full speed con MVP
- 50-100 signup → procedo ma con scope ridotto (re-evaluation)
- < 50 signup → **STOP**, riconsidero target/positioning/prodotto

**Alternatives considered**:
- Saltare validation, partire dritto: rischio 4 mesi sprecati su prodotto che non vende
- Validation solo dopo MVP: troppo tardi, costo opportunity enorme

**Consequences**:
- ✅ Riduce rischio fallimento prodotto del ~60-70%
- ✅ Email list pre-launch (100+ contatti caldi al lancio)
- ✅ Insight reali su feature priority dai survey/interviste
- ⚠️ +2 settimane di "ritardo" su sviluppo (largamente compensato dal rischio mitigato)

---

## ADR-019: Stack plugin = PHP + Tailwind + Alpine.js (NO React)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Scelta stack frontend del plugin admin. WordPress moderno usa React per Gutenberg, ma il plugin sarà admin pages (non block editor).

**Decision**: PHP 8.0+ (server-side rendering) + **Tailwind CSS** (compilato) + **Alpine.js** per interattività + **SSE** per streaming AI. NO React, NO Vue, NO build complesso.

**Alternatives considered**:
- React: bundle pesante (~150KB+), ecosystem WP-React frammentato, dev complesso
- Vue: alternativa decente, ma stack di Ainstein è già Alpine → coerenza

**Consequences**:
- ✅ Bundle JS plugin minuscolo (~30KB compilato vs ~150KB React)
- ✅ Stesso stack di Ainstein → riuso completo di componenti UI (modal, dropdown, accordion, etc.)
- ✅ Zero learning curve per dev (Alpine = HTML standard + 15 directives)
- ✅ Performance: page load istantaneo, no JS hydration delay
- ⚠️ Per UX "WOW" alcune animazioni richiedono CSS smart (gestibile)

---

## ADR-020: Documenti progetto in `plugins/ainstein-editorial/docs/` (NON `seo-toolkit/docs/`)

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Dove ospitare la documentazione del progetto. Proposta iniziale era `seo-toolkit/docs/plans/`.

**Decision**: Tutti i docs in `plugins/ainstein-editorial/docs/` per evitare mischio con docs Ainstein. 5 file totali (no bloat): `README.md`, `CLAUDE.md`, `docs/design.md`, `docs/roadmap.md`, `docs/decisions.md`.

**Alternatives considered**:
- `seo-toolkit/docs/plans/`: si mischia con docs Ainstein → confusione futura
- 20+ file frammentati: bloat documentale, manutenzione decade
- Solo 1 mega-doc: illeggibile, non navigabile

**Consequences**:
- ✅ Prodotto self-contained
- ✅ Estrazione futura in repo standalone = banale
- ✅ Documentazione concisa (5 file focused) vs sprawl

---

## ADR-023: Pivot build-first — scope completo MVP, no validation pre-dev, naming postponed

**Date**: 2026-05-13 · **Status**: Accepted · **Supersedes**: ADR-018 (validation pre-dev obbligatoria), parti di ADR-022

**Context**: Dopo aver creato un validation kit corposo (8 file, 2.737 righe) e proposto in seguito di ridurre lo scope MVP per accelerare time-to-market, riconsiderazione completa con il proprietario ha portato a riconoscere due errori:

1. **Validation kit over-engineered per profilo founder**: assume skill marketing (Carrd, Meta Ads, Google Ads, ConvertKit, Tally, Zapier setup) e 40+ ore di esecuzione. Per maker-personality (chi costruisce da solo, motivazione "build first"), questo crea attrito letale al progetto.

2. **Riduzione scope MVP era falsa economia**: tagliare Content Brain, auto-pilot, internal linking bidirezionale, KW research integrato e UX "WOW" significa eliminare esattamente il quid che differenzia da RankMath AI / AIOSEO AI. Senza differenziazione, race-to-bottom su prezzo (€5/mo). Con differenziazione, sustained €69-149/mo.

**Decision**:

1. **MVP scope COMPLETO mantenuto** come da `design.md` originale e ADR-009/010/011/016. Tutto: Content Brain, auto-pilot, internal linking bidirezionale, AI Keyword Research integrato, UX WOW (streaming AI, gauge animati, onboarding wizard animato, illustrations custom), email automation. Niente compromessi sulla differenziazione.

2. **Validation pre-dev (Fase 1)**: **POSTPONED**. Il validation kit (`docs/validation/`) resta nel repo come reference per il futuro. Non è blocker. Il prodotto si valida costruendolo + testandolo su 5 siti reali (proprio + amici/contatti) a fine MVP, poi launch.

3. **Naming + dominio**: POSTPONED a fine MVP. Codename interno "Ainstein Editorial" usato durante development. Decisione naming presa quando prodotto funziona, basata su preferenza founder + (eventualmente) feedback dei primi 5 utenti test reali.

4. **Workflow di lavoro**: per ogni milestone (M1-M6), Claude scrive **spec tecnica dettagliata + piano di lavoro task-by-task + Definition of Done** in file dedicato (`docs/milestones/M{N}-{nome}.md`). Il proprietario approva la spec prima dell'esecuzione. Claude esegue come architetto + dev, il proprietario è il cliente che approva e dà direzione strategica.

**Alternatives considered**:
- Validation classica pre-dev (ADR-018 originale): bocciata per profilo founder + spreco momentum
- Lean MVP scope ridotto: bocciata per perdita differenziazione
- Build-first SENZA spec dettagliata: bocciata, no traceability + risk creep scope

**Consequences**:
- ✅ Time-to-first-paying-customer: ~5 mesi (4 sviluppo + 1 test/launch) — invariato vs piano originale
- ✅ Differenziazione preservata → pricing power €69-149/mo sustainable
- ✅ Momentum founder mantenuto (build → test → launch lineare)
- ✅ Spec milestone-by-milestone garantisce traceability + qualità
- ✅ Validation kit non sprecato (riusato a launch time per acquisizione)
- ⚠️ Rischio "costruire prodotto che non vende" mitigato da test su 5 utenti reali fine MVP (validation tardiva ma vera)
- ⚠️ Decisione naming senza dati survey → scelta intuitiva founder + verifica disponibilità domini

**Implications operative**:
- `roadmap.md`: Fase 1 Validation rimossa dalla sequenza primaria. Spostata in "Optional / Post-MVP" come "Pre-launch validation accelerata se serve".
- `validation/`: cartella mantiene contenuti, README.md aggiornato per riflettere "use this when ready to launch, not before".
- Workflow: ogni milestone ha file `docs/milestones/M{N}-{nome}.md` con spec + plan completi.
- Comando `/editorial-next`: ora cerca in `docs/milestones/` la prossima milestone aperta + esegue task list.

---

## ADR-022: Sottodominio Ainstein per validation, dominio dedicato solo post-gate

**Date**: 2026-05-13 · **Status**: Accepted · **Supersedes**: parte di ADR-018 (validation budget)

**Context**: Roadmap originale prevedeva acquisto domini (~€200-300) PRIMA della validation come step 1. Riconsiderazione costi: durante validation l'utente visita landing 2 min, lascia email, niente checkout, niente trust deep — brand confusion ha basso impatto. Spendere €300 prima di sapere se l'idea sta in piedi è prematuro.

**Decision**:
- **Durante Fase 1 (validation, 14gg)**: usare sottodominio di `ainstein.it` come `editorial.ainstein.it` (placeholder) o `<nome>.ainstein.it` (se nome scelto subito). DNS setup in 5 minuti su Cloudflare/Hetzner con CNAME verso Carrd.
- **Decision gate giorno 14**: se validation passa (≥100 signup), allora acquistare dominio dedicato + setup 301 redirect dal sottodominio.
- **Naming finale**: scegliere a giorno 14 con dati REALI di survey (D5 può includere domanda "preferisci Scribo, Penna o Inkly?") invece di scelta a-priori.

**Alternatives considered**:
- Comprare domini upfront: €200-300 prematuri, scelta nome senza dati
- Sottodominio per sempre: blocca branding consumer per public launch, ostacola Lemon Squeezy custom domain, mix B2B/consumer email reputation Ainstein

**Consequences**:
- ✅ Risparmio €300 immediato
- ✅ Naming finale data-driven (survey insight)
- ✅ Setup landing in 5 min invece di attendere DNS propagation 24h
- ✅ Reversibile a costo zero (301 redirect)
- ⚠️ Brand confusion lieve durante validation (mitigato: utente non interagisce in profondità, è solo waitlist signup)
- ⚠️ Quando passa gate: lavoro extra setup dominio nuovo + 301 redirect (~1h)

**Implications su altri docs**:
- `validation/01-naming-research.md`: scelta nome NON è pre-validation step, ma post-gate
- `validation/02-landing-page-copy.md`: URL placeholder = sottodominio Ainstein
- `validation/04-ads-copy.md`: URL ads → sottodominio
- `validation/03-survey-questions.md`: aggiungere D6 opzionale "preferisci nome A/B/C?"
- `roadmap.md`: rimuovere "Scelta nome brand finale" dalla settimana 1 Fase 1, spostarla a decision gate

---

## ADR-021: Comandi utente per continuità sessioni Claude

**Date**: 2026-05-12 · **Status**: Accepted

**Context**: Sessioni AI hanno memoria limitata. Necessità di formalizzare riprese sessione + chiusure pulite con docs aggiornati.

**Decision**: Definiti 6 trigger linguistici espliciti in `CLAUDE.md` del plugin:
- `/editorial-status` (riprendi sessione)
- `/editorial-save` (chiudi sessione, aggiorna docs, commit)
- `/editorial-decision <X>` (logga ADR)
- `/editorial-next` (esegui prossimo task)
- `/editorial-demo` (mostra cosa è dimostrabile)
- `/editorial-help` (elenco comandi)

Riconoscimento fuzzy (variazioni in italiano accettate).

**Consequences**:
- ✅ Continuità garantita tra sessioni
- ✅ Documentazione sempre allineata al codice
- ✅ Proprietario può lavorare in modalità "solo obiettivi, non micro-management"

---

## ADR-024: Schema additions M1.5 (topup_balance_articles, last_payment_failed_at, external_event_id)

**Date**: 2026-05-14 · **Status**: Accepted · **Migration**: `database/migrations/2026_05_14_aied_webhook_columns.sql`

**Context**: durante l'implementazione M1.5 (webhook handler LemonSqueezy) emersi 3 campi mancanti rispetto allo schema definito in `design.md` §4:
1. `aied_users.topup_balance_articles` — **menzionato nel testo del design** (sezione 7 "Quota model" e sezione 11 "Webhook events": *"order_created (top-up) — credita azioni in `aied_users.topup_balance_articles`"*) ma **assente dalla CREATE TABLE** alle righe 165-180.
2. `aied_users.last_payment_failed_at` — NON menzionato nel design. Mia decisione: serve per implementare warning email in M6 dopo un `subscription_payment_failed` LS.
3. `aied_api_logs.external_event_id` + UNIQUE INDEX `(provider, external_event_id)` — NON menzionato nel design. Mia decisione: necessario per idempotency atomica dei webhook (LS retries possono duplicare lo stesso evento).

**Decision**:
- Aggiunte tutte e 3 le colonne via migration **separata** dalla M1.1 (non riapro la migration originale): `2026_05_14_aied_webhook_columns.sql` + rollback dedicato.
- Per gap design (#1): considerato un fix del design, non un'aggiunta. Il design diceva di averlo, lo schema no — bug del design.
- Per #2 e #3 (mie aggiunte): vincolate al solo modulo webhook M1.5/M6, non hanno effetti su altre milestone.

**Alternatives considered**:
- **Estendere la migration M1.1** invece che crearne una nuova: scartato perché M1.1 è già committata e applicata su DB locale; conviene mantenere ogni migration immutabile.
- **Tabella `aied_webhook_events` dedicata** per idempotency invece di riusare `aied_api_logs.external_event_id`: scartato perché aggiunge una tabella per scopo strettamente correlato al log API esistente. UNIQUE INDEX su `(provider, external_event_id)` ottiene lo stesso risultato con meno entropia.
- **NO `last_payment_failed_at`** (dedurlo da `subscription_status='past_due'`): scartato perché serve il *timestamp* per email warning (es. "ti ricordiamo che 3 giorni fa..." ).

**Consequences**:
- ✅ Migration pulita, rollback chiaro, nessuna corruzione di M1.1.
- ✅ Idempotency webhook robusta (UNIQUE + INSERT IGNORE = claim atomico).
- ⚠️ Nuovi cliente devono eseguire **due** migration in ordine (M1.1 poi M1.5). Da automatizzare in M1.7 (build script) o successivamente con un migration runner.
- ⚠️ `design.md` §4 va aggiornato per riflettere il vero schema (ovvero: includere le 3 colonne nella CREATE TABLE di riferimento).

---

## ADR-025: Idempotency webhook claim-then-process + handling variant_id sconosciuto

**Date**: 2026-05-14 · **Status**: Accepted · **Riferimento**: M1.5 fix critici post-review

**Context**: prima review critica del codice M1.5 ha evidenziato 5 bug seri:
1. `tierFromVariant()` con default `'starter'` → **degrade silenzioso** di utenti Business/Pro a Starter se variant_id arriva con id non in `config/editorial.php` (es. nuovo prodotto LS dimenticato in config).
2. `last_payment_failed_at` non viene resettato al recovery → email warning M6 a utenti già recuperati.
3. Idempotency originale: log row scritto **dopo** l'UPDATE → se crash tra UPDATE e log, replay LS riprocessa e **doppia il top-up balance**.
4. Test runner inquinava DB con dati orfani da test HTTP.
5. `WEBHOOK_SECRET_MISSING` → HTTP 500 → LS retry esponenziale infinito su bug operativo.

**Decision**:
1. **Tier preservation su variant sconosciuto**: `tierFromVariant()` ritorna `?string` (null = sconosciuto). Handler `subscription_created/updated` con variant null applicano comunque `status`, `renews_at`, `customer_id` ma **lasciano `tier` invariato**. Messaggio `"unknown_variant_id=...tier_preserved_check_config"` finisce in `aied_api_logs.response_summary` per audit. HTTP 200 (no retry LS necessario, è errore di config).
2. **Recovery clear**: `subscription_updated` con LS status `active|on_trial` esegue `last_payment_failed_at = NULL`.
3. **Claim-then-process pattern**:
   - INSERT IGNORE log row in `aied_api_logs` con `response_status=0` PRIMA del processing (claim atomico via UNIQUE).
   - Se `rowCount=0` → row già esistente → `duplicate` skip (200 OK).
   - Altrimenti `BEGIN TRANSACTION` → handler → `COMMIT` → UPDATE log con `response_status=200`.
   - Su exception: `ROLLBACK` (annulla side-effect su `aied_users`) → UPDATE log con `response_status=500`.
   - **Limite noto**: se il process viene killed tra claim INSERT e UPDATE finale, il log resta `response_status=0`. LS retry vedrà `rowCount=0` e ritornerà duplicate. **Evento perso** — l'admin deve riprocessarlo manualmente o un retry-job in M6 lo recupera.
4. **secret missing → 200 OK** + log critical su `aied_api_logs` (provider=`lemonsqueezy_webhook`, endpoint=`config_error`, summary=`WEBHOOK_SECRET_MISSING`). LS smette di retry, l'admin trova traccia in tabella + Logger.

**Alternatives considered**:
- **Throw + 500 su variant sconosciuto** invece di preserve+200: scartato perché farebbe retry esponenziale su tutti i webhook fino al fix config → 503 per ore. Preserve è "fail soft, alert loud" (response_summary visibile).
- **Tabella `aied_webhook_events` con status enum (pending|processed|failed)** + retry-job: design migliore ma fuori scope M1.5. Va aggiunto in M6 quando si fanno gli email warning.
- **Secret missing → 503 Service Unavailable** (transient): LS comunque retry, anche se più rado. Skipped: vogliamo zero retry su bug di config.

**Consequences**:
- ✅ Zero degrade silenzioso utenti.
- ✅ Zero double credit su top-up dopo crash.
- ✅ Zero retry loop LS su misconfigurazione server.
- ✅ Tutti i side-effect su `aied_users` sono transazionali.
- ⚠️ Eventi orfani (crash post-claim, pre-finalize) sono persi finché non c'è un retry-job (TODO M6).
- ⚠️ "Tier preservato" può essere invisibile all'utente: serve un alert admin (TODO M6: cron che cerca log con `response_summary LIKE 'unknown_variant%'`).

---

## ADR-026: Internal linking — pivot da "auto-modify" a "suggest + approve"

**Date**: 2026-05-15 · **Status**: Superseded (vedi pivot sotto, 2026-05-15 pomeriggio) · **Riferimento**: M1.9 hardening, applicato in M5

### Pivot (2026-05-15, post competitor matrix)

Risultato competitor analysis: **nessuno** dei 13 competitor analizzati implementa auto-modifica di articoli vecchi senza approval. KoalaWriter, GetGenie, AI Engine, NeuronWriter — tutti fanno solo forward links (nuovo → vecchio) o suggerimenti manuali. Il segnale di mercato è: "nessuno lo fa = nessuno lo vuole davvero", più che "killer feature inesplorata".

**Decisione aggiornata**:
1. **M3 (Article generation)**: include forward links automatici (nuovo articolo → articoli vecchi rilevanti). Low risk, pattern standard di mercato. Default ON.
2. **M5 (Internal linking polish)**: aggiunge backward links MA con UI **suggest + approve**:
   - AI analizza articoli vecchi, propone N link da inserire (max 5 per articolo)
   - UI plugin "Suggerimenti link interni" mostra ogni suggerimento con context snippet (frase prima/dopo)
   - Utente approva uno per uno, può modificare ancor text, può rifiutare
   - Solo dopo approve esplicito → modifica articolo vecchio
   - Audit log + undo + `data-aied-link` + `_aied_no_modify` come da piano originale
3. **Marketing**: il differenziatore comunicato è "AI suggerisce internal linking strategici, tu approvi" — value prop più forte di "AI modifica i tuoi articoli" (che spaventa target non-tech).

**Consequences vs versione superseded**:
- ✅ Zero rischio "AI ha rotto il mio sito" → eliminato il primo motivo di churn/reputation hit
- ✅ Differenziatore comunicato meglio (suggest+approve è feature attiva, non opt-in nascosto)
- ✅ Onboarding più semplice (no disclaimer pesante in v1.0)
- ✅ Audit log + undo restano in piano ma sono safety net, non meccanismo primario di recovery
- ⚠️ Minore "automation feel" rispetto auto-modify: ma target non-tech preferisce controllo a magia rischiosa
- ⚠️ Aggiunge UI in M5 (modal approve + lista suggerimenti) — incrementa effort M5 di ~1 giorno

### Storica decisione (superseded, mantenuta per audit trail)

**Context**: Il differenziatore #3 dichiarato ("internal linking bidirezionale" — quando AI scrive articolo nuovo, modifica anche articoli vecchi per inserire link verso il nuovo) è anche il pezzo di codice più rischioso del prodotto. Modificare articoli WP esistenti è un'operazione *distruttiva*:

**Context**: Il differenziatore #3 dichiarato ("internal linking bidirezionale" — quando AI scrive articolo nuovo, modifica anche articoli vecchi per inserire link verso il nuovo) è anche il pezzo di codice più rischioso del prodotto. Modificare articoli WP esistenti è un'operazione *distruttiva*:
- Algoritmo buggato → inserisce link tossici in siti reali (impatto SEO immediato, recovery via undo richiede che l'utente se ne accorga in tempo)
- Snapshot per undo è stale se l'utente edita manualmente l'articolo DOPO il nostro insert → conflict resolution non triviale
- WordPress ha hook `the_content` filter cache, plugin di caching (W3TC, WP Rocket), CDN: il "link inserito" può non apparire o apparire stale
- Causa legale potenziale: cliente con sito DR40+ ha bug AI → traffic crash → cita per danno commerciale

**Decision**: 
1. Internal linking è feature-flagged via WP option `aied_internal_links_enabled`, default **OFF** al rilascio v1.0.
2. UI plugin in v1.0 mostra "Internal linking bidirezionale" come **beta opt-in** con disclaimer esplicito ("modifica articoli esistenti, leggi i rischi") + checkbox conferma utente.
3. Default ON solo dopo che **almeno 100 articoli reali** sono stati linkati senza issue (audit via `aied_internal_links` + zero ticket support su link errati).
4. Internal links sempre con `data-aied-link="<inserted_at_iso>"` per identificare + undo bulk facile.
5. WP postmeta `_aied_no_modify` (già pianificato) accetta opt-out per singolo articolo.
6. Edge case "utente ha editato dopo nostro insert": snapshot scaduto → undo non rimuove il nostro link, mostra warning admin ("L'articolo è stato modificato dopo l'inserimento del link. Rimuovi manualmente."). NON tentiamo merge automatico.

**Alternatives considered**:
- **Default ON sin da v1.0** (con disclaimer): più feature value percepita ma rischio reputazionale altissimo al primo bug viral su Twitter/Reddit. Scartato.
- **Rimuovere internal linking del tutto da v1.0** (lasciarlo a v1.1): perdiamo il differenziatore principale vs KoalaWriter. Scartato.
- **Solo forward links (nuovo → vecchio), no backward**: meno rischio ma anche meno differenziazione, è quello che fa già metà del mercato.

**Consequences**:
- ✅ Rischio reputazionale contenuto al lancio (zero "il plugin mi ha rotto il sito" possibile come default behavior)
- ✅ Beta tester possono attivare e validare per noi prima della massa
- ✅ Audit log + undo + flag = recovery path completo
- ⚠️ Conversion marketing inferiore al lancio (differenziatore "nascosto" dietro opt-in)
- ⚠️ Decisione "default ON dopo 100 articoli puliti" va monitorata attivamente, serve dashboard interna (TODO M6)

---

## ADR-027: Backend Editorial in monorepo Ainstein — accoppiamento accettato per MVP

**Date**: 2026-05-15 · **Status**: Accepted · **Riferimento**: M1.9 risk register

**Context**: il backend del plugin Editorial vive in `seo-toolkit/api/editorial/` e usa direttamente le classi `Core\Database`, `Core\Logger`, `Core\Router` di Ainstein. Patterns condivisi tipo `AiService`, `ScraperService`, `SerpApiService` sono riusati senza version-pinning. Tre rischi documentati:
1. Modifica a `Core\*` in Ainstein può rompere Editorial senza che il team se ne accorga
2. Deploy Ainstein con bug → Editorial backend down → clienti paying impattati
3. Test plugin Editorial in CI dovrebbe testare l'intera codebase Ainstein → tempi lunghi

**Decision**: per MVP (fino a ~100 paying users), accettiamo l'accoppiamento. Mitigations parziali:
1. Tutti i deploy Ainstein che toccano `Core\*` o servizi condivisi (`AiService`, `ScraperService`, ecc.) richiedono smoke test manuale Editorial post-deploy (~5 min).
2. `composer.lock` committato (M1.9 task) per Editorial-specific deps (Firebase\JWT) — almeno quelle non sono volatili.
3. Logging di tutti gli error 5xx Editorial in tabella separata `aied_api_logs` con alert se rate > 1% (TODO M6).
4. **Decisione di re-evaluation**: a 100 paying users o se Editorial revenue > €5K MRR, fare hard-isolation in repo separato (1-2 settimane di lavoro). Threshold scritta esplicitamente per non dimenticare.

**Alternatives considered**:
- **Soft-isolation immediata** (4-6 ore: subdir specifico con bootstrap proprio + lista classi Core whitelistate version-pinned): più sicuro ma overhead in M1.9 mentre il prodotto deve ancora dimostrare di vendere.
- **Repo separato immediato** (1 settimana): pulito ma sospende M2 per una settimana → impatto roadmap inaccettabile pre-validation.
- **Nessuna mitigation** (status quo M1.7 e precedenti): rischio non documentato → futuro deploy Ainstein rompe Editorial senza nessuno saperlo → cliente paying down.

**Consequences**:
- ✅ Velocità M2-M6 mantenuta
- ✅ Riuso 100% servizi Ainstein (zero duplicazione)
- ✅ Smoke test post-deploy mitiga il rischio principale
- ⚠️ Accoppiamento aumenta blast-radius dei bug Ainstein
- ⚠️ Threshold di re-evaluation (100 paying users, €5K MRR) va verificata mensilmente — TODO M6 (cron dashboard interna)
- ⚠️ Se Editorial cresce molto più veloce di Ainstein, l'isolamento diventa urgente prima dei 100 utenti

---

## ADR-028: Pricing data-driven post-competitor matrix

**Date**: 2026-05-15 · **Status**: Accepted · **Riferimento**: M1.9.1 competitor matrix

**Context**: Pricing iniziale era placeholder ("Starter €19/mo?"). Competitor matrix (13 competitor analizzati) ha fornito dati di mercato per fissarlo data-driven.

Mercato 2026 rilevante per target italiano blogger/PMI non-tech:
- **KoalaWriter**: $9-49/mo (entry low, ma BYOK su tier alti)
- **GetGenie** (WP-native, supporta italiano): $0-99/mo
- **Cuppa**: $25-150/mo + BYOK
- **NeuronWriter** (LTD $109-327): $23-117/mo
- **AISEO**: $19-34/mo (26 lingue)
- **ZimmWriter** (lifetime concorrenza): $497 lifetime BYOK
- **Surfer SEO** (declino, prezzo aumentato 2-4x): $99-219/mo
- **Jasper**: $39-69/mo (positioning premium)

**Decision**: 4 tier subscription + top-up packs. Pricing italiano in EUR (no conversion):

| Tier | Prezzo/mese | Articoli inclusi | Siti | Differenziatore |
|------|-------------|-------------------|------|-----------------|
| **Starter** | **€19** | 10 articoli + meta + image | 1 | Entry blogger singolo |
| **Pro** | **€49** | 50 articoli + meta + image | 3 | Sweet spot PMI singola |
| **Business** | **€99** | 150 articoli + meta + image | 10 | Multi-sito + GSC integration (M+) |
| **Agency** | **€149** | 500 articoli + meta + image | 50 | Agency con clienti multipli |

Top-up packs (acquistabili in qualsiasi momento, no scadenza):
- **+10 articoli**: €15
- **+50 articoli**: €60 (sconto 20%)
- **+100 articoli**: €100 (sconto 33%)

**Alternatives considered**:
- **Pricing aggressivo entry €9**: rischio race-to-bottom italiano + margine Starter < soglia AI cost coverage. Scartato.
- **Pricing premium entry €29**: positioning "qualità italiana" interessante ma friction conversione su Starter alta. Possibile per fascia Pro+ in M6 dopo dati validation.
- **Lifetime opzione anti-ZimmWriter**: tabled per ora. Da considerare con offerta limitata "Founder Lifetime" per primi 50 customer ai limiti del costo CAC. Decisione separata in ADR futuro se conversione bassa.

**Pricing differenziatori sul mercato italiano**:
1. **Fatturazione IVA + P.IVA italiana** (vs SaaS US senza fattura italiana → friction per PMI)
2. **Pricing in EUR** (no fluttuazioni USD)
3. **No BYOK** (target non-tech non sa cosa siano API keys)
4. **Top-up flessibile** (heavy user mese punta non deve fare upgrade tier)

**Consequences**:
- ✅ Allineato a competitor diretti (KoalaWriter, GetGenie) → pricing non è friction di conversione
- ✅ Margini sostenibili su Starter (€19 - €0.15×10 articoli AI cost = €17.5 lordo, viable)
- ✅ Multi-sito tier (Pro 3, Business 10, Agency 50) cattura segmento agency naturalmente
- ⚠️ Pricing va RIVALIDATO dopo 30 giorni post-launch con dati reali conversion + churn
- ⚠️ "Articoli inclusi" potrebbe essere troppo o troppo poco — heavy user può saturare Pro in 1 settimana, churn senza top-up. Monitoring fondamentale


---

## ADR-029: M1.9.7 — WP Compatibility Matrix eseguita, deviazioni e harness fix

**Date**: 2026-05-20 · **Status**: Accepted · **Riferimento**: M1.9.7 plan in `docs/milestones/M1-9-7-wp-compat-matrix.md`

**Context**: M1.9.7 prevedeva 5 install WP locali per validare che Ainstein Editorial v0.1.0 non vada in fatal/collisione con Yoast, RankMath, Elementor, WP 6.0+PHP 8.0, e WPML. Eseguendolo su Laragon Windows abbiamo incontrato 3 deviazioni e 2 bug del harness da documentare.

**Decision**:

1. **Eseguito M1.9.7 il 2026-05-19/20** con suite headless wp-cli in `tests/wp-compat/` (setup.sh + smoke.sh + teardown.sh, ~530 righe bash). Evidence in `docs/milestones/m1-9-7-evidence/` (5 report per-install + `_summary.md`).

2. **Esito**: ⚠️ **GO M2** — 5/5 PASS (4 strict + 1 con note WP/core su install #4). **Nessuna issue attribuibile al plugin**. Decision gate del piano soddisfatto ("4-5/5 PASS con minor issue → procedi M2").

3. **Deviazioni dal piano accettate**:
   - **Install #4: PHP 8.0 → PHP 8.1** — PHP 8.0 (EOL nov 2023) non installato su Laragon locale; floor disponibile 8.1.10. Testato WP 6.0 + PHP 8.1.10. Caveat: una regressione PHP-8.0-only non sarebbe catturata. Rischio basso (8.0 EOL, share residuale; il vero rischio backward-compat è 8.1+ che è il floor reale del mercato installato).
   - **Install #5: WPML → Polylang** — WPML richiede licenza commerciale assente. Polylang (free, ~700k installazioni attive, dominante nel mercato WP italiano) usato come proxy per il test di collisione multilingua. Polylang è il competitor diretto free di WPML e replica gli stessi pattern di hook (post translation, language switching) → copertura adeguata.

4. **Bug ambiente scoperti & risolti durante esecuzione**:
   - **Auto-updater core attivo**: prima run di install #4 (WP 6.0) si è auto-aggiornata silenziosamente a 6.9.4 → test backward-compat nullificato. Fix: aggiunto `define('AUTOMATIC_UPDATER_DISABLED', true)` e `define('WP_AUTO_UPDATE_CORE', false)` in `wp config create --extra-php` (setup.sh). Verificato: dopo fix `version.php` resta a 6.0 attraverso tutto il ciclo (setup → install → smoke).
   - **Probe headless intercettato da activation transient**: `Plugin::handleActivationRedirect` chiama `wp_safe_redirect()` durante `admin_init` se il transient `aied_show_activation_redirect` è settato (TTL 30s post-activation). wp-cli intercetta il redirect con `WP_CLI::error()` → exit 255 → probe output vuoto. Fix harness: `delete_transient('aied_show_activation_redirect')` come prima istruzione del probe per testare steady-state (smoke.sh `_probe.php`).

5. **Plugin enhancement identificato (deferito a M2.0)**: `Plugin::handleActivationRedirect` non controlla `defined('WP_CLI') && WP_CLI`. Aggiungere questo guard rende il plugin sicuro per qualsiasi workflow CLI (test, deploy script, automazione futura). Non-bloccante per v0.1.0 (in produzione l'attivazione avviene via browser admin); aperto come task M2.0.

**Alternatives considered**:

- **Installare PHP 8.0 standalone**: ~30 min setup, ma 8.0 è EOL → low value per cattura regression. Scartato in favore di 8.1.
- **Acquistare licenza WPML trial**: $39 per testare 1 cosa. Polylang è proxy adeguato (stesso hook surface area) → no spesa.
- **Saltare M1.9.7 e procedere a M2**: rischio reputazionale (review "rompe Yoast" al lancio). Mantenuto in piano. Esecuzione costata ~3.5h totali (1h script + 2.5h debug+rerun) — molto meno del costo di una review 1★.
- **Non fixare auto-updater (accettare WP 6.9.4 per #4)**: avrebbe lasciato il floor non testato. Inaccettabile per il valore primario di M1.9.7.

**Consequences**:

- ✅ Confermato: AE 0.1.0 non collide con Yoast/RankMath/Elementor/Polylang su latest WP+PHP, né su floor WP 6.0/PHP 8.1
- ✅ Suite wp-compat riusabile (`bash setup.sh && bash smoke.sh`) per regression test pre-release di ogni futura versione
- ✅ Setup.sh pina deterministicamente la versione WP (`AUTOMATIC_UPDATER_DISABLED`) → riproducibilità garantita
- ✅ Probe steady-state → smoke deterministico indipendente da timing transient
- ⚠️ Coverage gap: PHP 8.0 strict non testato (acceptable, rischio basso)
- ⚠️ Coverage gap: WPML non testato (Polylang è proxy, ma WPML ha edge case proprietari su hook `wpml_*`); da rivalutare se beta riceve segnali utenti WPML
- 📝 Task M2.0: aggiungere `defined('WP_CLI')` guard a `Plugin::handleActivationRedirect`

---

## ADR-030: Editorial usa "service user" Ainstein per AiService.Credits

**Date**: 2026-05-22 · **Status**: Accepted · **Riferimento**: M2.1 ContentBrainService

**Context**: `AiService::analyze()` (singleton Ainstein) consuma crediti dalla tabella `users` di Ainstein via `Credits::hasEnough()` + `Credits::consume()`. Editorial vive in namespace separato `aied_*` con `aied_users` distinte da `users` Ainstein (CLAUDE.md Editorial GR #2: "Namespace separato in DB"). Quando Editorial chiama `AiService` deve passare un `$userId` valido nella tabella `users` di Ainstein, altrimenti il check Credits fallisce o consuma su user sbagliato.

Tre pattern possibili:
1. **Service user dedicato**: 1 utente Ainstein "editorial-service" con crediti illimitati. `AiService::analyze($serviceUserId, ...)` traccia consumo qui.
2. **Modificare AiService con bypass flag**: aggiungere `skipCredits=true` ad analyze. Pulito ma mutua AiService → rischio rotture cross-modulo.
3. **Chiamare API Claude direttamente bypassando AiService**: viola GR Editorial #1 (no duplicazione).

**Decision**: Pattern 1 (service user). Implementato in M2.1 con setting `editorial_service_user_id` (chiave in tabella `settings` Ainstein, default `1` = admin locale per dev). In produzione l'admin può creare un utente sistema dedicato (`editorial-system@ainstein.it`, tier illimitato) e popolare il setting.

```php
$this->serviceUserId = (int) Settings::get('editorial_service_user_id', 1);
$ai->analyze($this->serviceUserId, $prompt, $payload, 'editorial-content-brain');
```

**Alternatives considered**:
- **Pattern 2 (bypass flag in AiService)**: scartato per non mutare la API di un servizio condiviso usato da 9 moduli — rischio di side-effect non intuitivi. Editorial dovrebbe adattarsi al contratto esistente, non viceversa.
- **Pattern 3 (curl diretto)**: scartato per GR Editorial #1. Avrebbe duplicato la logica di provider fallback (Claude→OpenAI) e logging.
- **Linkare `aied_users.id` a `users.id` con shared schema**: violerebbe GR #2 (separazione namespace). Inoltre `aied_users` non hanno controparte 1:1 in `users` Ainstein (utenti Editorial creati silenziosamente all'attivazione license, vedi ADR-012).

**Consequences**:

- ✅ AiService inalterato — zero rischio cross-modulo
- ✅ Editorial conforme a GR #1 (riuso) e GR #2 (namespace separato)
- ✅ Traceability: tutte le chiamate AI Editorial finiscono in `api_logs` Ainstein sotto `module=editorial-content-brain`, attribuite al service user → filtro pulito per cost analysis
- ✅ Override locale via Settings → flessibilità dev/staging/prod senza modifiche codice
- ⚠️ Quota Editorial tracking rimane in `aied_actions_log` (separato da Ainstein Credits). I crediti AiService Ainstein consumati dal service user sono solo "costi tecnici interni", non quota utente
- 📝 Task M6 polish: documentare in `docs/DEPLOY.md` come creare l'utente service in produzione e popolare il setting
- 📝 Considerare in M3 generation: stesso pattern verrà esteso a `ArticleService` (riuso Settings con la stessa chiave)
