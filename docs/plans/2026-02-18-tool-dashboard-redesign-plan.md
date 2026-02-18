# Tool Dashboard Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Semrush-style educational sections (hero, features, steps, use-cases, FAQ, CTA) below the existing operational stats on AI Content and Keyword Research dashboards.

**Architecture:** Each dashboard file gets new HTML appended after the existing stats/projects content. No new PHP files or shared components — each module has unique content. Alpine.js handles FAQ accordion. All visuals are CSS-only mockups with Tailwind.

**Tech Stack:** PHP views, Tailwind CSS, Alpine.js, Heroicons SVG

**Design doc:** `docs/plans/2026-02-18-tool-dashboard-redesign.md`

---

## Task 1: AI Content — Remove old dismissible hero, add separator

**Files:**
- Modify: `modules/ai-content/views/dashboard.php` (lines 27-82)

**Step 1: Remove the old dismissible hero block**

Delete lines 27-82 (the `x-data="{ show: !localStorage.getItem('ainstein_hero_ai_content') }"` block). This was a dismissible "come funziona" card — we're replacing it with permanent educational content below the stats.

**Step 2: Add visual separator after the credits info bar**

After the closing `</div>` of the Credits Info bar (line ~387, the last `</div>` before the file ends), add:

```html
<!-- ═══════════ EDUCATIONAL CONTENT ═══════════ -->
<div class="relative my-12">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full border-t border-slate-200 dark:border-slate-700"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-slate-50 dark:bg-slate-900 px-4 text-sm text-slate-500 dark:text-slate-400">Scopri cosa puoi fare</span>
    </div>
</div>
```

**Step 3: Verify PHP syntax**

Run: `php -l modules/ai-content/views/dashboard.php`
Expected: `No syntax errors detected`

**Step 4: Commit**

```bash
git add modules/ai-content/views/dashboard.php
git commit -m "refactor(ai-content): remove dismissible hero, add separator for educational content"
```

---

## Task 2: AI Content — Hero educativo

**Files:**
- Modify: `modules/ai-content/views/dashboard.php`

**Step 1: Add hero section after the separator**

Append after the separator div from Task 1. The hero has:
- Left side: title, subtitle, CTA button
- Right side: CSS mock of an article with SEO score bar and heading structure
- Background: subtle amber gradient
- Full dark mode support
- Responsive: 2 cols desktop, 1 col mobile

The CSS mock (right side) should show a mini article preview with:
- Title bar with "come scegliere un materasso"
- SEO score circle (92/100)
- Fake heading structure (H1, H2, H2, H3)
- Word count indicator
- All built with divs and Tailwind

**Step 2: Verify PHP syntax**

Run: `php -l modules/ai-content/views/dashboard.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add modules/ai-content/views/dashboard.php
git commit -m "feat(ai-content): add educational hero section with CSS mock article"
```

---

## Task 3: AI Content — "Come funziona" (4 step)

**Files:**
- Modify: `modules/ai-content/views/dashboard.php`

**Step 1: Add 4-step section after hero**

4 numbered steps in a horizontal grid (1 col mobile, 2 tablet, 4 desktop):

1. **Aggiungi le keyword** (MagnifyingGlass icon)
   - "Inserisci le keyword da posizionare manualmente, via CSV, o importale direttamente da Keyword Research. Il sistema le organizza per progetto."
2. **Studio SERP + Brief AI** (ChartBar icon)
   - "Per ogni keyword, Ainstein analizza i primi 10 risultati Google, estrae la struttura dei competitor e genera un brief strategico con heading e argomenti da coprire."
3. **Generazione articolo** (Sparkles icon)
   - "L'AI scrive un articolo SEO completo seguendo il brief, con H2/H3 ottimizzati, meta title/description, e il tuo tone of voice. Puoi modificarlo prima della pubblicazione."
4. **Pubblicazione WordPress** (GlobeAlt icon)
   - "Connetti il tuo sito WordPress e pubblica direttamente dalla piattaforma. Cover image DALL-E inclusa. Scheduling automatico supportato."

Each step has: numbered circle (amber bg), icon, title, description text.

**Step 2: Verify PHP syntax**

Run: `php -l modules/ai-content/views/dashboard.php`

**Step 3: Commit**

```bash
git add modules/ai-content/views/dashboard.php
git commit -m "feat(ai-content): add 4-step 'come funziona' section"
```

---

## Task 4: AI Content — Feature sections (4 alternating sections)

**Files:**
- Modify: `modules/ai-content/views/dashboard.php`

**Step 1: Add 4 feature sections with alternating layout**

Each section is a 2-column grid (text + CSS mock visual), alternating sides. Odd sections have light/white bg, even have slate-50 bg for visual rhythm.

**Feature 1: "Analisi SERP che studia i tuoi competitor"** (text LEFT, visual RIGHT)
- Text: describes SERP analysis with concrete example (keyword "come scegliere un materasso")
- CTA link: "Scopri l'analisi SERP" → links to keywords section
- CSS Mock: mini SERP table with 3 rows showing position, truncated URL, word count, heading count

**Feature 2: "Brief strategici generati dall'AI"** (visual LEFT, text RIGHT)
- Text: describes AI brief generation with example ("ricette pasta al forno")
- CSS Mock: heading tree (H1 → H2 → H3) + checklist of topics to cover

**Feature 3: "Articoli SEO scritti dal tuo assistente AI"** (text LEFT, visual RIGHT)
- Text: describes article generation (1500-3000 words, meta tags, internal linking)
- CSS Mock: mini editor with fake text lines, SEO score sidebar, meta tag pills

**Feature 4: "Pubblica su WordPress con un click"** (visual LEFT, text RIGHT)
- Text: describes WP publishing with example (5 articles/week, Monday 9am)
- CSS Mock: WordPress card with "Programmato" status badge, date, cover image placeholder

Each section is wrapped in alternating background colors. Between sections add `py-16` spacing.

**Step 2: Verify PHP syntax**

Run: `php -l modules/ai-content/views/dashboard.php`

**Step 3: Commit**

```bash
git add modules/ai-content/views/dashboard.php
git commit -m "feat(ai-content): add 4 alternating feature sections with CSS mocks"
```

---

## Task 5: AI Content — Use-case grid + FAQ + CTA finale

**Files:**
- Modify: `modules/ai-content/views/dashboard.php`

**Step 1: Add "Cosa puoi fare" grid (6 cards, 3x2)**

Grid with `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6`. Each card has:
- Heroicon SVG (amber accent)
- Title (bold)
- 1-line description
- Subtle border, rounded-xl, hover effect

Cards:
1. Contenuti per blog (DocumentText icon)
2. Pagine prodotto (ShoppingBag icon)
3. Guide complete (AcademicCap icon)
4. Cluster tematici (Squares2x2 icon)
5. Refresh contenuti (ArrowPath icon)
6. Meta tags in bulk (Tag icon)

**Step 2: Add FAQ accordion**

Alpine.js `x-data="{ open: null }"` with 6 questions. Each FAQ item:
- Click toggles `open === N`
- Chevron rotates on open
- Answer slides down with `x-show` + `x-transition`

FAQ content:
1. "Quanti crediti costa generare un articolo?" → "Il costo varia in base alle operazioni: estrazione SERP (X cr), scraping contenuti (X cr), generazione AI (X cr). La pubblicazione su WordPress è gratuita." (use `$creditCosts` PHP variable)
2. "Posso modificare l'articolo prima della pubblicazione?" → "Si, ogni articolo generato può essere editato..."
3. "Come funziona la connessione WordPress?" → "Installa il plugin Ainstein WP Connect..."
4. "Che differenza c'è tra brief AI e generazione diretta?" → "Il brief è un piano strategico..."
5. "Posso importare keyword da altri moduli?" → "Si, dal modulo Keyword Research..."
6. "L'AI supporta più lingue?" → "Si, Ainstein genera contenuti in italiano, inglese..."

**Step 3: Add CTA finale**

Full-width amber gradient bar with title + button:
- "Pronto a generare il tuo primo articolo SEO?"
- Button: "Crea progetto" → links to `/ai-content/projects`

**Step 4: Close the outer `</div>` properly**

Make sure the closing `</div>` from the original `<div class="space-y-6">` (line 84) wraps everything correctly, OR close it before the educational section and open a new wrapper.

**Step 5: Verify PHP syntax**

Run: `php -l modules/ai-content/views/dashboard.php`

**Step 6: Visual check in browser**

Open: `http://localhost/seo-toolkit/ai-content`
Verify: stats appear first, then separator, then all educational sections, dark mode works, responsive on mobile.

**Step 7: Commit**

```bash
git add modules/ai-content/views/dashboard.php
git commit -m "feat(ai-content): add use-case grid, FAQ accordion, and final CTA"
```

---

## Task 6: Keyword Research — Remove old dismissible hero, add separator

**Files:**
- Modify: `modules/keyword-research/views/dashboard.php` (lines 17-67)

**Step 1: Remove the old dismissible hero block**

Delete lines 17-67 (the `x-data="{ show: !localStorage.getItem('ainstein_hero_keyword_research') }"` block).

**Step 2: Add separator after stats section**

After the closing `<?php endif; ?>` of the stats grid (line ~265), add the same separator pattern used in AI Content but with purple accent.

**Step 3: Verify PHP syntax**

Run: `php -l modules/keyword-research/views/dashboard.php`

**Step 4: Commit**

```bash
git add modules/keyword-research/views/dashboard.php
git commit -m "refactor(keyword-research): remove dismissible hero, add separator"
```

---

## Task 7: Keyword Research — Hero educativo

**Files:**
- Modify: `modules/keyword-research/views/dashboard.php`

**Step 1: Add hero section after separator**

Same pattern as AI Content hero but with purple color scheme:
- Title: "Trova le keyword giuste e trasformale in un piano editoriale"
- Subtitle: "Dalla ricerca seed alla strategia contenuti completa..."
- CTA: "Inizia la tua prima ricerca" (purple button) → `/keyword-research/projects?type=research`
- CSS Mock (right side): cluster tree visualization
  - Central node "scarpe running"
  - 3-4 branch nodes: "migliori scarpe running principianti", "scarpe running pronazione", "scarpe running donna offerte"
  - Connected with CSS lines/borders
  - Volume badges on each node

**Step 2: Verify PHP syntax**

Run: `php -l modules/keyword-research/views/dashboard.php`

**Step 3: Commit**

```bash
git add modules/keyword-research/views/dashboard.php
git commit -m "feat(keyword-research): add educational hero with CSS cluster tree mock"
```

---

## Task 8: Keyword Research — "Come funziona" (3 step)

**Files:**
- Modify: `modules/keyword-research/views/dashboard.php`

**Step 1: Add 3-step section**

3 steps in horizontal grid (1 col mobile, 3 desktop):

1. **Parti da una keyword seed** (LightBulb icon)
   - "Inserisci una keyword di partenza e il settore. L'AI genera centinaia di varianti: long-tail, domande degli utenti, keyword correlate. Esempio: da 'scarpe running' ottieni 'migliori scarpe running principianti', 'scarpe running pronazione', 'scarpe running donna offerte'."
2. **Organizza in cluster** (Squares2x2 icon)
   - "L'AI raggruppa le keyword per intento di ricerca e topic, creando cluster tematici. Ogni cluster diventa un potenziale articolo o pagina. Esempio: 'materassi memory foam' raggruppa keyword su vantaggi, prezzi, manutenzione, confronto con lattice."
3. **Genera il Piano Editoriale** (DocumentText icon)
   - "Trasforma i cluster in un piano editoriale completo: titoli articolo, priorità, difficoltà stimata, volume di ricerca. Esporta direttamente in AI Content per la generazione automatica."

Purple numbered circles, purple accent icons.

**Step 2: Verify PHP syntax**

Run: `php -l modules/keyword-research/views/dashboard.php`

**Step 3: Commit**

```bash
git add modules/keyword-research/views/dashboard.php
git commit -m "feat(keyword-research): add 3-step 'come funziona' section"
```

---

## Task 9: Keyword Research — Feature sections (3 alternating)

**Files:**
- Modify: `modules/keyword-research/views/dashboard.php`

**Step 1: Add 3 feature sections with alternating layout**

Same alternating pattern as AI Content but with purple accents:

**Feature 1: "4 modalità per ogni esigenza di ricerca"** (text LEFT, visual RIGHT)
- Text: describes 4 modes (Research Guidata, Architettura Sito, Piano Editoriale, Quick Check)
- CSS Mock: 4 mini-cards in 2x2 grid, each with colored gradient icon, name, and credit cost badge

**Feature 2: "Clustering AI che organizza il caos"** (visual LEFT, text RIGHT)
- Text: clustering example (e-commerce mobili → same cluster for related keywords)
- CSS Mock: cluster visualization with central topic node and surrounding keyword nodes, connected by lines. Show 2 clusters side by side.

**Feature 3: "Dal piano editoriale alla produzione in un click"** (text LEFT, visual RIGHT)
- Text: describes export to AI Content (10 keywords → 10 articles in queue)
- CSS Mock: mini table with 3 rows (checkbox, keyword, volume, difficulty), bottom bar with "Esporta in AI Content" button

**Step 2: Verify PHP syntax**

Run: `php -l modules/keyword-research/views/dashboard.php`

**Step 3: Commit**

```bash
git add modules/keyword-research/views/dashboard.php
git commit -m "feat(keyword-research): add 3 alternating feature sections with CSS mocks"
```

---

## Task 10: Keyword Research — Use-case grid + FAQ + CTA finale

**Files:**
- Modify: `modules/keyword-research/views/dashboard.php`

**Step 1: Add "Cosa puoi fare" grid (6 cards)**

Same grid pattern, purple accents:
1. Ricerca keyword di nicchia (MagnifyingGlass icon)
2. Analisi competitor (UserGroup icon)
3. Struttura sito web (BuildingOffice icon)
4. Calendario contenuti (Calendar icon)
5. Keyword gap analysis (ArrowsRightLeft icon)
6. Export automatico (ArrowTopRightOnSquare icon)

**Step 2: Add FAQ accordion**

6 KR-specific questions with Alpine.js accordion:
1. "Quanti crediti costa una ricerca keyword?" → "Research Guidata costa 3 crediti. Architettura Sito e Piano Editoriale costano 10 crediti ciascuno. Il Quick Check è completamente gratuito."
2. "Che differenza c'è tra le 4 modalità?" → "Research Guidata: parti da keyword seed... Architettura Sito: struttura URL... Piano Editoriale: calendario contenuti... Quick Check: verifica rapida..."
3. "Posso esportare le keyword in AI Content?" → "Sì, dal Piano Editoriale puoi selezionare le keyword e inviarle direttamente..."
4. "Da dove vengono i dati di volume e difficoltà?" → "I dati provengono da provider professionali (Google Keyword Insight via RapidAPI)..."
5. "Il Quick Check è davvero gratuito?" → "Sì, il Quick Check non consuma crediti..."
6. "Posso fare ricerche in lingue diverse dall'italiano?" → "Sì, puoi specificare lingua e paese target..."

**Step 3: Add CTA finale**

Purple gradient bar:
- "Scopri le keyword che i tuoi competitor non stanno sfruttando"
- Button: "Crea progetto" → `/keyword-research/projects`

**Step 4: Close outer div properly**

**Step 5: Verify PHP syntax**

Run: `php -l modules/keyword-research/views/dashboard.php`

**Step 6: Visual check in browser**

Open: `http://localhost/seo-toolkit/keyword-research`
Verify: mode cards appear first, then recent projects, stats, then separator, then all educational sections.

**Step 7: Commit**

```bash
git add modules/keyword-research/views/dashboard.php
git commit -m "feat(keyword-research): add use-case grid, FAQ accordion, and final CTA"
```

---

## Task 11: Final visual review + commit

**Step 1: Check both dashboards in browser**

- `http://localhost/seo-toolkit/ai-content` — verify full flow: stats → separator → hero → steps → features → use-cases → FAQ → CTA
- `http://localhost/seo-toolkit/keyword-research` — same verification
- Test dark mode toggle on both
- Test mobile responsive (resize browser to 375px)
- Verify all links work (CTA buttons, feature section links)

**Step 2: Fix any visual issues found**

**Step 3: Final commit if any fixes were needed**

```bash
git add -A
git commit -m "fix(dashboard): visual refinements for educational sections"
```
