# Homepage Redesign — Design Document

**Data:** 2026-03-05
**Stato:** Approvato
**Scope:** Solo homepage (pagine feature moduli in fase successiva)

---

## Contesto

La homepage attuale (`/`) mostra una pagina "coming soon" minimale. La landing completa (`landing4.php`) esiste su `/home` ma:
- Usa screenshot statiche invece del pattern "scopri cosa puoi fare" con mockup HTML/CSS
- Non valorizza i moduli wow (Piano Editoriale, Keyword Clustering, Ads Creator)
- Manca il flusso pipeline end-to-end (keyword → pubblicazione)
- Il posizionamento è generico ("automatizza marketing") invece di evidenziare l'automazione professionale

## Posizionamento

> "Ainstein non è un tool AI generico. È automazione di qualità costruita da professionisti SEO e ADV."

Value prop: **"Dai la keyword. Ainstein fa il resto."**

Differenziante: output pronti all'uso (non dati da interpretare), creata da chi fa SEO ogni giorno, prezzo accessibile vs competitor.

---

## Struttura Homepage (9 sezioni)

### 1. HERO

- **Badge:** "Creata da professionisti SEO e ADV"
- **H1:** "Dai la keyword. Ainstein fa il resto."
- **Sub:** "Ricerca, piano editoriale, articoli SEO, pubblicazione. Processi che richiedono giorni, completati in minuti. Non AI generica — automazione costruita da chi fa SEO ogni giorno."
- **CTA:** [Prova gratis — 30 crediti] + [Guarda come funziona ↓]
- **Visual:** Mockup HTML animato del flusso (input keyword → output articolo con score) — NO screenshot statica
- **Stats:** Counter animati (articoli generati, keyword analizzate, moduli AI)

### 2. PIPELINE VISUALE (nuova)

Flusso orizzontale 5 step con mini-mockup sotto ciascuno:

```
[Keyword seed] → [Ricerca AI] → [Piano editoriale] → [Articolo SEO] → [Pubblicato su WP]
```

Ogni step mostra un mini-mockup dell'output reale. Su mobile: verticale.
Messaggio: "Dall'idea alla pubblicazione. Zero copia-incolla tra tool diversi."

### 3. FEATURE WOW SPOTLIGHT (3 blocchi full-width)

Tre blocchi grandi con mockup HTML/CSS stile "scopri cosa puoi fare":

**Blocco 1 — AI Keyword Research + Piano Editoriale** (purple)
- 2 col: testo sx, mockup dx (tabella keyword clusterizzate con volumi/intent/difficulty)
- Bullet: 3 seed → 120+ keyword, piano editoriale 12 mesi, export in AI Content

**Blocco 2 — AI Content Generator** (amber)
- 2 col: mockup sx, testo dx (alternanza)
- Mockup: wizard con brief AI, struttura articolo
- Bullet: analisi SERP top 10, articolo in 10 min, publish WP 1 click

**Blocco 3 — Google Ads Analyzer** (rose)
- 2 col: testo sx, mockup dx (campagna generata)
- Bullet: analisi competitor, campagna completa, pronto per Ads Editor

### 4. COME FUNZIONA (3 step concreti)

Stesso layout card attuale ma con esempio concreto ("scarpe running"):

```
01 — Inserisci la keyword: "Scrivi 'scarpe running', scegli il modulo"
02 — L'AI analizza e crea: "Studia SERP, analizza competitor, genera output strategici"
03 — Risultati pronti all'uso: "Articoli, piani, campagne — output professionali, non bozze"
```

### 5. TOOLKIT COMPLETO (griglia 3x2)

6 moduli in griglia compatta. Ogni card: icona colorata, nome, tagline 1 riga, "Scopri di più →".
Sostituisce i tabs con screenshot — più leggero, più pulito.

### 6. PAIN/SOLUTION

Manteniamo la struttura attuale (confronto "Senza/Con Ainstein"), snellito a 4 righe.

### 7. SOCIAL PROOF + POSIZIONAMENTO

Counter animati + sezione "Chi c'è dietro":
"Costruita da professionisti SEO e ADV che automatizzano i processi che fanno ogni giorno."
Badge "Powered by Claude AI (Anthropic)" opzionale.

### 8. PRICING PREVIEW

3 card (Free, Pro, Agency) + link "Confronta tutti i piani →" verso /pricing.

### 9. CTA FINALE

"Inizia ad automatizzare il tuo SEO" + "30 crediti gratuiti. Nessuna carta di credito." + [Crea account gratis →]

---

## Note Tecniche

- **Route:** `/` → homepage (rimuovere coming-soon redirect)
- **File:** Evoluzione di `public/landing4.php` (non file nuovo)
- **Design system:** DM Sans + amber + light (invariato)
- **Mockup:** Pattern "scopri cosa puoi fare" con browser frame (dots), dati finti realistici, NO screenshot
- **Header/footer:** `public/includes/site-header.php` + `site-footer.php` (invariati)
- **Cleanup:** Rimuovere landing.php, landing2.php, landing3.php (obsoleti) + coming-soon.php
- **Social proof:** Counter da DB live (come oggi in landing4)

## Fase successiva (non in scope)

- Pagine feature dedicate: `/features/{slug}` per ogni modulo
- Pattern 7 sezioni "scopri cosa puoi fare" adattato per visitatori non registrati
- Priorità: keyword-research, ai-content, ads-analyzer
