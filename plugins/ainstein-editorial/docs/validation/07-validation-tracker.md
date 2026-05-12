# Validation Tracker — Decision Gate Dashboard

> Template Google Sheets per tracciare TUTTI i dati di validation in un unico posto.
> Permette decisione data-driven al giorno 14.
> Setup: 30 minuti, ROI infinito.
> Data: 2026-05-12

---

## Setup Google Sheets

### Crea nuovo Google Sheet con 6 tab

1. **`Dashboard`** — KPI sintetici (auto-calcolati)
2. **`Signups`** — Lista signup dettagliata
3. **`Survey`** — Risposte questionario
4. **`Ads`** — Performance ads daily
5. **`Outreach`** — Tracking LinkedIn/FB/DM
6. **`Interviews`** — Note interviste

---

## Tab 1: Dashboard (KPI sintetici)

```
┌────────────────────────────────────────────────────────────┐
│              VALIDATION DASHBOARD — [BRAND]                │
│              Periodo: [start_date] - [end_date]            │
│              Giorno corrente: [day_X di 14]                │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  📊 SIGNUP TOTALI                       [123]              │
│     vs target (100): 🟢 +23%                               │
│                                                            │
│  💰 SPESA ADS TOTALE                    [€157]             │
│     vs budget (€200): 🟢 79%                               │
│                                                            │
│  💵 COST PER SIGNUP                     [€2.81]            │
│     vs target (€4): 🟢 -30%                                │
│                                                            │
│  📝 SURVEY COMPLETION RATE              [54%]              │
│     vs target (50%): 🟢 +4%                                │
│                                                            │
│  💸 PRICING ≥ €50/mo (% risposte D3)    [38%]              │
│     vs target (30%): 🟢 +8%                                │
│                                                            │
│  📞 INTERVISTE COMPLETATE               [6/10]             │
│     vs target (5-10): 🟢                                   │
│                                                            │
├────────────────────────────────────────────────────────────┤
│  DECISION GATE STATUS:  🟢 GO                              │
│                                                            │
│  ✓ Signup ≥ 100                                            │
│  ✓ Pricing ≥ €50 ≥ 30%                                     │
│  ✓ Survey completion ≥ 50%                                 │
│  ✓ Interviste ≥ 5                                          │
│                                                            │
│  ➜ RACCOMANDAZIONE: Procedi M1 Development                 │
└────────────────────────────────────────────────────────────┘
```

### Formule essenziali Dashboard

```
SIGNUP TOTALI = COUNTA(Signups!A2:A1000)
SPESA ADS = SUM(Ads!E2:E1000)
COST PER SIGNUP = SPESA ADS / SIGNUP TOTALI
SURVEY COMPLETION RATE = COUNTA(Survey!A2:A1000) / SIGNUP TOTALI
PRICING ≥ €50 = COUNTIF(Survey!E2:E1000, ">=50") / COUNTA(Survey!E2:E1000)
INTERVISTE COMPLETATE = COUNTA(Interviews!A2:A100)

DECISION GATE STATUS:
=IF(
  AND(SIGNUP>=100, PRICING_50_PCT>=0.30, SURVEY_COMP>=0.50, INTERVIEWS>=5),
  "🟢 GO",
  IF(
    AND(SIGNUP>=50, SIGNUP<100),
    "🟡 GO con riserva",
    "🔴 STOP - rivedi scope"
  )
)
```

---

## Tab 2: Signups (lista master)

| Colonna | Tipo | Esempio | Source |
|---------|------|---------|--------|
| Email | text | mario@example.com | Carrd form |
| Signup date | date | 2026-05-12 | timestamp Carrd |
| Source | dropdown | ads_fb / ads_google / linkedin / fb_group / direct / referral | UTM o manual |
| UTM campaign | text | valueprop / question / competitor / pmi | UTM auto |
| UTM content | text | ad1 / ad2 / ... | UTM auto |
| First name | text | Mario | optional Carrd field |
| Country | text | IT | from IP detection (Plausible) |
| Survey completed | checkbox | TRUE/FALSE | tag ConvertKit |
| Survey completion date | date | 2026-05-13 | timestamp Tally |
| Notes | text | "Ha risposto a DM" | manual |

### Auto-population
- Connetti **Carrd → Zapier → Google Sheets** per popolare automaticamente al submit
- Connetti **ConvertKit → Zapier → Google Sheets** per aggiornare survey_completed quando arriva da Tally

---

## Tab 3: Survey (risposte questionario)

| Colonna | Domanda survey | Tipo risposta |
|---------|---------------|---------------|
| Email | (auto, da signup) | text |
| Submission date | (auto) | datetime |
| D1: Profilo | Blogger / PMI / Freelance / Agency / Altro | dropdown |
| D2: Articoli/mese | 0-2 / 3-5 / 6-15 / 16+ / varia | dropdown |
| D3: Pricing accettato | 0 / 19 / 49 / 99 / 249 / 250+ | numero |
| D4a: Pain #1 | (selezione 1 di 7) | dropdown |
| D4b: Pain #2 | (selezione 2 di 7) | dropdown |
| D5: Open feedback | testo libero | text long |

### Auto-population
- **Tally → Zapier → Google Sheets** (Tally ha integrazione nativa)
- O webhook custom su Carrd se usi Tally come embed

### Pivot tables utili (sub-tabs)
- **Pivot 1**: D1 (profilo) × Count signups → quale segmento converte di più
- **Pivot 2**: D3 (pricing) media per D1 (profilo) → quanto pagherebbe ogni segmento
- **Pivot 3**: D4a (pain) count → quale pain è più ricorrente
- **Pivot 4**: D1 × D2 (profilo × volume) → identifica segmenti più "affamati"

---

## Tab 4: Ads performance (daily tracking)

| Data | Canale | Campagna | Ad name | Spend | Impressions | Clicks | CTR | Signups | CPS |
|------|--------|----------|---------|-------|-------------|--------|-----|---------|-----|
| 2026-05-12 | FB | Audience_A_blogger | FB-1 caffè | €5 | 1200 | 24 | 2.0% | 2 | €2.50 |
| 2026-05-12 | FB | Audience_A_blogger | FB-2 pag bianca | €5 | 950 | 18 | 1.9% | 1 | €5.00 |
| 2026-05-12 | Google | IT_high_intent | GA-1 valueprop | €7 | 320 | 12 | 3.8% | 3 | €2.33 |
| ... | ... | ... | ... | ... | ... | ... | ... | ... | ... |

### Formule
- CTR = Clicks / Impressions
- CPS (Cost Per Signup) = Spend / Signups
- ROI = (Signups × LTV_atteso) / Spend (LTV atteso es €120 = 4 mesi medi a €30)

### Decision rules ads
- CTR < 1.0% per 3+ giorni → spegni ad
- CPS > €6 per 3+ giorni → spegni ad
- CTR > 2.5% → raddoppia budget ad

### Daily check routine (5 min)
- [ ] Apri Meta Ads Manager + Google Ads
- [ ] Copia daily numbers nel tab
- [ ] Applica decision rules: spegni/scala ad
- [ ] Aggiorna spend nel Dashboard

---

## Tab 5: Outreach tracking

### Sub-tab 5a: LinkedIn posts

| Data | Post # | Type | Reach | Engagement | CTR landing | Signups attribuiti |
|------|--------|------|-------|------------|-------------|---------------------|
| 2026-05-12 | #1 | Founder story | 2400 | 67 | 35 click | 8 signup |
| ... | ... | ... | ... | ... | ... | ... |

### Sub-tab 5b: FB Groups posts

| Data | Gruppo | Post # | Visualizzazioni | Like | Commenti | Signups attribuiti |
|------|--------|--------|----------------|------|----------|---------------------|
| 2026-05-13 | Imprenditori 4.0 | #1 | 850 | 23 | 8 | 4 |
| 2026-05-13 | WordPress Italia | #1 | 420 | 11 | 5 | 2 |
| ... | ... | ... | ... | ... | ... | ... |

### Sub-tab 5c: DM tracking

| Data DM | Piattaforma | Destinatario (nome) | Profilo segmento | Reply? | Outcome |
|---------|-------------|---------------------|------------------|--------|---------|
| 2026-05-14 | LinkedIn | Marco Rossi | SEO freelance | Sì | Signup + intervista |
| 2026-05-14 | LinkedIn | Sara Bianchi | PMI owner | No | — |
| ... | ... | ... | ... | ... | ... |

**Target rates**:
- DM LinkedIn reply rate: ≥ 30%
- DM Twitter reply rate: ≥ 20%
- Reply → Signup conversion: ≥ 50%

---

## Tab 6: Interviews

| ID | Data | Nome | Segmento | Score interesse 1-10 | Pricing scelto | Pain top | Quote killer | Conversione probabile |
|----|------|------|----------|---------------------|----------------|----------|--------------|----------------------|
| 1 | 2026-05-15 | Marco Rossi | SEO freelance | 9 | €99 | Cliente PMI no budget freelance | "Lo userei per tutti i miei clienti che non possono permettermi" | Alta |
| 2 | 2026-05-17 | Sara Bianchi | PMI | 7 | €29 | Tempo per scrivere | "Se davvero scrive come me, lo provo" | Media |
| ... | ... | ... | ... | ... | ... | ... | ... | ... |

### Sub-tab: Affinity diagram digitale

Lista insight raggruppati per tema:

**Tema: "Voice dell'AI"**
- Marco: "Articoli AI sembrano tutti uguali"
- Sara: "Ho paura che non sembri scritto da me"
- Luigi: "Provato Jasper, troppo generico"
- → INSIGHT: brand voice è top concern. Content Brain è positioning corretto.

**Tema: "Pricing"**
- Marco: pagherebbe €99 facile (vende ai clienti)
- Sara: €29 è giusto, €49 esitante
- Luigi: comprerebbe lifetime €299 una tantum
- → INSIGHT: subscription è ok ma lifetime offer va testato come opzione

**Tema: "Auto-pilot scetticismo"**
- Marco: "Ho bisogno di review finale"
- Sara: "Auto-publish mai, troppo rischio"
- Luigi: "Auto-publish post settima settimana ok"
- → INSIGHT: auto-publish va opt-in solo dopo trust building. Conferma ADR-008.

---

## Decision Gate finale (giorno 14)

### Template report 2-page

```markdown
# Validation Report — [BRAND]
## Periodo: [start] - [end] · 14 giorni

---

## TL;DR

**Decisione**: 🟢 GO / 🟡 GO con riserva / 🔴 STOP

**Rationale 1-paragraph**: 
[3-4 frasi sintesi]

---

## Numeri raccolti

| Metrica | Risultato | Target | Status |
|---------|-----------|--------|--------|
| Signup totali | XXX | ≥ 100 | 🟢/🟡/🔴 |
| Cost per signup | €X.XX | ≤ €4 | 🟢/🟡/🔴 |
| Survey completion | XX% | ≥ 50% | 🟢/🟡/🔴 |
| Pricing ≥ €50 | XX% | ≥ 30% | 🟢/🟡/🔴 |
| Interviste completate | X/10 | ≥ 5 | 🟢/🟡/🔴 |

---

## Insights TOP 5 (dai dati + interviste)

1. **[Insight 1]**: ...
2. **[Insight 2]**: ...
3. **[Insight 3]**: ...
4. **[Insight 4]**: ...
5. **[Insight 5]**: ...

---

## Best channel performance

| Canale | Spend | Signups | CPS | ROI atteso |
|--------|-------|---------|-----|------------|
| FB Ads | €X | X | €X | Xx |
| Google Ads | €X | X | €X | Xx |
| LinkedIn organic | €0 | X | €0 | ∞ |
| FB groups | €0 | X | €0 | ∞ |
| DM diretto | €0 | X | €0 | ∞ |

→ Best channel for scale post-launch: [canale]
→ Worst channel: [canale, da deprioritare]

---

## Segmento più "caldo"

Profilo: [es. PMI con blog attivo, 30-45 anni]
% signup totali: XX%
Willingness to pay media: €XX/mo
Pain top: [...]
Quote rappresentativa: "[...]"

→ MARKETING POST-LAUNCH: focus su questo segmento

---

## Modifiche raccomandate al piano

1. [Modifica 1 al positioning / pricing / feature]
2. [Modifica 2]
3. ...

---

## Cosa NON ha funzionato

- ...
- ...

---

## Next steps

Se GO:
- [ ] Kickoff M1 [data]
- [ ] Update README.md, CLAUDE.md con eventuali pivot
- [ ] Annuncio waitlist "stiamo iniziando lo sviluppo"
- [ ] Frequenza email update: bisettimanale durante M1-M6

Se STOP:
- [ ] Email waitlist "ci prendiamo del tempo per ripensare"
- [ ] Workshop di pivot 1-week
- [ ] Riconsiderazione: target / prodotto / pricing
- [ ] Nuova validation con scope modificato

---

*Report compilato: [data]*
*Compiled by: [Founder name]*
```

---

## Setup automation (Zapier free tier)

### Zap 1: Carrd → Sheets (signup)
- Trigger: New form submission Carrd
- Action 1: Add row to "Signups" tab
- Action 2: Add subscriber to ConvertKit con tag `waitlist-active`

### Zap 2: Tally → Sheets (survey)
- Trigger: New form submission Tally  
- Action 1: Add row to "Survey" tab
- Action 2: Update row in "Signups" tab con `survey_completed=TRUE`
- Action 3: Tag ConvertKit subscriber con `survey-completed` + segment tags

### Zap 3: Calendly → Sheets (interviste)
- Trigger: New event scheduled Calendly
- Action 1: Add row to "Interviews" tab (data, nome, email)

### Zap 4: Plausible → Sheets (analytics)
- Daily digest Plausible → row in "Ads" tab (combinato con dati ads)

**Note**: Zapier free tier = 100 tasks/mese. Per 14 giorni di validation è sufficiente.

---

## Tracking quotidiano (5 minuti/giorno)

### Daily ritual 15 min
- [ ] **5 min**: aggiorna tab "Ads" con numeri di ieri (Meta + Google)
- [ ] **5 min**: review Dashboard, applica decision rules ads
- [ ] **5 min**: rispondi DM ricevuti su LinkedIn/Twitter

### Weekly review 30 min (venerdì)
- [ ] Review tab Dashboard, screenshot per archivio
- [ ] Compila riga "weekly summary" nel report
- [ ] Identifica top performers ads + outreach
- [ ] Plan settimana successiva (nuovi ads? nuovi post?)

---

## Output finale validation

Al giorno 14:
1. ✅ Google Sheet completo con tutti i dati
2. ✅ Report 2-page decisione (template sopra)
3. ✅ Decision: GO/GO con riserva/STOP
4. ✅ Email update waitlist con next steps
5. ✅ Update `roadmap.md` con checkbox Fase 1 completata
6. ✅ Commit + `/editorial-save`

---

*Fine validation tracker. Tutti i tool del validation kit sono ora pronti per essere usati.*
