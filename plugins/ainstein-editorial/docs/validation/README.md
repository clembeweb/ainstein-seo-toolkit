# Validation Kit — Fase 1

> Tutti i deliverable necessari per eseguire la Fase 1 di validation pre-development.
> Obiettivo: validare willingness-to-pay del target prima di investire 14 settimane di sviluppo MVP.
> Output: decisione GO/GO-con-riserva/STOP al giorno 14.

---

## Cosa c'è in questa cartella

| # | File | Cosa contiene | Quando usarlo |
|---|------|---------------|---------------|
| 01 | [`01-naming-research.md`](01-naming-research.md) | Analisi 12 nomi brand con scoring, top 3 raccomandati, action plan verifica domini + trademark | Prima cosa da fare: 1 ora per scegliere nome finale |
| 02 | [`02-landing-page-copy.md`](02-landing-page-copy.md) | Copy completo landing page (hero, sezioni, FAQ, CTA), design system, A/B variants | Dopo nome scelto: 2-3 ore per setup landing su Carrd |
| 03 | [`03-survey-questions.md`](03-survey-questions.md) | 5 domande survey ottimizzate + 5 email automation (welcome, follow-up, update, beta, launch) | Setup Tally + ConvertKit con tutti i copy pronti |
| 04 | [`04-ads-copy.md`](04-ads-copy.md) | 6 ads Facebook + 4 Google Ads, targeting, budget €200, monitoring rules | Setup Meta Ads + Google Ads, launch giorno 1 |
| 05 | [`05-outreach-templates.md`](05-outreach-templates.md) | 3 post LinkedIn + 3 post FB groups + DM templates (LinkedIn, Twitter), calendar 14 giorni | Outreach organico parallelo agli ads |
| 06 | [`06-interview-script.md`](06-interview-script.md) | Canovaccio interviste 15-20 min, recruiting, note template, anti-pattern | Interviste 5-10 utenti target dopo prime signup |
| 07 | [`07-validation-tracker.md`](07-validation-tracker.md) | Template Google Sheets 6-tab, formule, Zapier setup, decision gate report | Setup tracking giorno 1, monitoring quotidiano |

---

## Sequenza di esecuzione (giorno per giorno)

> ⚡ **Update ADR-022 (2026-05-13)**: il naming finale e l'acquisto domini sono spostati a **post-validation gate**. Durante Fase 1 si usa sottodominio Ainstein (es. `editorial.ainstein.it`) → setup 5 min, costo €0. Naming verrà scelto a giorno 14 con dati reali survey.

### Pre-launch (giorno -1)

- [ ] **Giorno -1** (mezza giornata):
  - Setup sottodominio Ainstein: DNS CNAME `editorial.ainstein.it` → Carrd (5 min)
  - Setup landing page Carrd con copy pronto — `02-landing-page-copy.md` (2-3 ore, sostituisci `[BRAND]` con "Editorial by Ainstein" temporary placeholder)
  - Setup Tally survey con 5+1 domande (aggiungi D6: "Quale nome preferisci tra Scribo / Penna / Inkly?") — `03-survey-questions.md`
  - Setup ConvertKit con 5 email automation
  - Setup Google Sheets tracker — `07-validation-tracker.md`
  - Setup Zapier (Carrd + Tally → Sheets + ConvertKit)

**Costo Pre-launch**: €19 Carrd Pro (tutto il resto free tier). Niente domini.

### Settimana 1

- [ ] **Lunedì (G1)**:
  - Launch Meta Ads (6 varianti, €100 budget 14gg) — `04-ads-copy.md`
  - Launch Google Ads (4 varianti, €100 budget 14gg)
  - Pubblica LinkedIn Post #1 (founder story) — `05-outreach-templates.md`
- [ ] **Martedì-Mercoledì**: 
  - Joining 6 gruppi FB target
  - Pubblica in 2 gruppi FB (Post #1)
- [ ] **Giovedì-Venerdì**: 
  - DM LinkedIn a 20 freelance/SEO + 10 PMI/blogger
- [ ] **Daily**: 5 min check ads + applica decision rules

### Settimana 2

- [ ] **Lunedì (G8)**:
  - Pubblica LinkedIn Post #2
  - Analisi mid-campaign: scala ad winners, spegni losers
- [ ] **Martedì-Mercoledì**: 
  - Post in altri gruppi FB (Post #2)
  - DM Twitter a 10 founder italiani
- [ ] **Giovedì**:
  - LinkedIn Post #3 (behind the scenes)
  - Inizia inviti interviste a top signup engaged
- [ ] **Venerdì-Domenica**: 
  - Esegui 5-10 interviste (15-20 min cad) — `06-interview-script.md`

### Giorno 14 (decision day)

- [ ] Compila decision gate report — template in `07-validation-tracker.md`
- [ ] Email update waitlist con next steps
- [ ] Update `../roadmap.md` con esito Fase 1
- [ ] Commit con `/editorial-save`

---

## Decision gate criteri (giorno 14)

| Scenario | Soglia | Decisione |
|----------|--------|-----------|
| 🟢 **GO** | ≥ 100 signup + ≥ 30% pricing €50+ + ≥ 50% survey completion + ≥ 5 interviste | Procedi M1 development full speed |
| 🟡 **GO con riserva** | 50-99 signup + 15-30% pricing €50+ | Procedi M1 ma rivedi pricing/messaging |
| 🔴 **STOP** | < 50 signup nonostante €200 ads OR < 15% pricing €50+ | Pivot scope: target/prodotto/pricing |

---

## Budget totale Fase 1

| Voce | Costo |
|------|-------|
| ~~Domini~~ → sottodominio Ainstein (ADR-022) | **€0** |
| Carrd Pro | €19/anno |
| ConvertKit free tier | €0 |
| Tally free tier | €0 |
| Plausible.io (opzionale) | €9/mese |
| Zapier free tier | €0 |
| Meta Ads | €100 |
| Google Ads | €100 |
| Founder time (40 ore distribuite) | — |
| **TOTALE OUT-OF-POCKET FASE 1** | **~€220** |

**Post-gate (solo se GO)**:
| Voce | Costo |
|------|-------|
| Dominio dedicato (.com + .ai + .it + .io × 5 anni) | €200-300 |
| **TOTALE OUT-OF-POCKET POST-GATE** | **€200-300** |

ROI atteso: se validation è positiva, ROI risparmiato = 4 mesi sviluppo + server + tool = €5000+. Se negativa, costo evitato = 16 settimane di sviluppo errato + €300 domini non spesi = priceless + €300.

---

## Output finale Fase 1

1. ✅ Nome brand finale scelto e domini acquistati
2. ✅ Landing page live su dominio finale con ~100+ signup
3. ✅ Survey con 50+ risposte segmentate
4. ✅ 5-10 interviste completate con insights documentati
5. ✅ Ads performance report (CPS reale, channel ranking)
6. ✅ 2-page decision report con raccomandazione
7. ✅ Audience pre-launch caldo per beta invite (M5)

---

## Riferimenti

- [README.md prodotto](../../README.md) — Vision strategica
- [docs/design.md](../design.md) — Architettura tecnica MVP
- [docs/roadmap.md](../roadmap.md) — Milestone development
- [docs/decisions.md](../decisions.md) — ADR storici (vedi ADR-018 per rationale validation gate)
