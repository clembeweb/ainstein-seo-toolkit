# Piano Lavori Ainstein — Priorità e Criticità

> Stato al 2026-03-23. Task completati rimossi, solo lavoro realmente pendente.

## Contesto

Dopo l'audit QA e i fix CSS, serve una roadmap prioritizzata di tutto il lavoro pendente. Diversi task in memoria risultano già completati (Eval Report v3, Ads Dashboard UX). Il lavoro reale si riduce a 5 item.

---

## Priorità 1: BUG — Ads Sync Timeout (94 campagne)

**Criticità: ALTA** — Blocca utenti con account Google Ads grandi

**Problema**: Il sync di account con molte campagne (es. Amevista, 94) crasha/va in timeout durante la fase `search_terms`. `set_time_limit(600)` non basta.

**Soluzione proposta**:
- Batch search terms per campagna (non tutte insieme)
- Skip search terms se tempo residuo < soglia
- Oppure: sync search terms asincrono (job separato)

**File chiave**: `modules/ads-analyzer/services/GoogleAdsService.php`, `modules/ads-analyzer/controllers/SyncController.php`

**Effort stimato**: 1 sessione

---

## Priorità 2: FEATURE PRONTA — Content Creator Images (test + deploy)

**Criticità: MEDIA-ALTA** — Feature 95% completa, genera revenue (crediti)

**Cosa manca**:
- [ ] Configurare API key Gemini in admin settings
- [ ] Creare directory `/storage/images/` su Hetzner
- [ ] Verificare PHP GD extension su Hetzner
- [ ] Eseguire migration SQL (`cc_images` + `cc_image_variants` + ENUM `cc_jobs`)
- [ ] Aggiungere cron `image-cleanup.php` al crontab
- [ ] Test locale completo (import, SSE generation, approve/reject, export ZIP)
- [ ] Deploy + test produzione

**File chiave**: `modules/content-creator/` (controllers, models, services, views), `docs/plans/2026-03-12-image-generation-plan-index.md`

**Effort stimato**: 1-2 sessioni (test + fix eventuali + deploy)

---

## Priorità 3: FEATURE — KW Research AI Improvements (3 miglioramenti)

**Criticità: MEDIA** — Migliora qualità output, non blocca nulla

### 3a. Research Guidata — Cluster Visibility + Scoring
- Cluster scoring (volume totale, opportunity), visual ranking, highlight top clusters
- UX: da lista piatta a gerarchia visiva

### 3b. Architettura Sito — Proposta Sito Completo
- Proporre sito completo (pagine istituzionali, blog, categorie)
- Analisi competitor SERP per informare struttura
- Prompt AI più ricco

### 3c. Piano Editoriale — Vista Calendario
- Vista calendario drag & drop (mesi/griglia, card articoli)
- Mantenere vista tabella come alternativa
- UI reference: CoSchedule, Notion calendar

**Effort stimato**: 1 sessione per ciascuno (3 totali)

---

## Priorità 4: TECH DEBT — CSS rounded-lg → rounded-xl

**Criticità: BASSA** — Solo estetica, nessun impatto funzionale

- ~2322 occorrenze `rounded-lg` in tutto il codebase
- La maggior parte su input/button/badge (corretto) — solo container div dovrebbero essere `rounded-xl`
- Serve review manuale file per file, non grep-replace cieco
- Approccio: 1 modulo alla volta, partendo dai più visibili (admin, shared views)

**Effort stimato**: 2-3 sessioni dedicate

---

## Priorità 5: CLEANUP — File e debt vari

**Criticità: BASSA** — Housekeeping

- [ ] Rimuovere mockup files eval v3 (`public/mockup-eval-v3.html`, `public/mockup-eval-comparison.html`)
- [ ] Verificare Shopping evaluation (non testata, nessun account con Merchant Center)
- [ ] Verificare extension metrics/audience signals dopo nuovo sync (fix Task 1-2 del v3)

**Effort stimato**: 30 min

---

## Ordine di esecuzione consigliato

| Sessione | Task | Motivo |
|----------|------|--------|
| **Ora** | P1: Ads Sync Timeout | Bug critico, blocca utenti reali |
| **Dopo P1** | P2: CC Images test+deploy | Feature pronta, revenue immediata |
| **Sessione 3** | P3a: KR Cluster Visibility | Miglioramento più impattante dei 3 |
| **Sessione 4** | P3b: KR Architettura Sito | Secondo miglioramento KR |
| **Sessione 5** | P3c: KR Vista Calendario | Terzo miglioramento KR |
| **Quando capita** | P4: CSS rounded-lg | Background, 1 modulo alla volta |
| **Quando capita** | P5: Cleanup | Quick wins tra una sessione e l'altra |
