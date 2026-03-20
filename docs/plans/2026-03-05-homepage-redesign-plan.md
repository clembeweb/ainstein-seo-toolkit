# Homepage Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the Ainstein homepage from a "coming soon" page to a full marketing landing page that showcases the platform's AI automation capabilities.

**Architecture:** Evolve the existing `public/landing4.php` file. Replace the screenshot-based module tabs with HTML/CSS mockups following the "scopri cosa puoi fare" pattern from module dashboards. Update routes so `/` serves the new homepage. Clean up obsolete landing files.

**Tech Stack:** PHP 8+, vanilla CSS (custom design system: DM Sans + amber), vanilla JS (scroll reveal, tabs, counters). No Tailwind on public pages — landing4 uses its own CSS.

**Design doc:** `docs/plans/2026-03-05-homepage-redesign-design.md`

---

## Reference Files

- **Current homepage:** `public/landing4.php` (DM Sans + amber design system, ~1140 lines)
- **Coming soon:** `public/coming-soon.php` (currently served at `/`)
- **Routes:** `public/index.php:104-138` (public routes)
- **Header/footer:** `public/includes/site-header.php`, `public/includes/site-footer.php`
- **Mockup pattern reference:** `modules/keyword-research/views/dashboard.php:167-274` (browser frame + cluster visualization)
- **Design system vars:** `public/landing4.php:46-66` (CSS custom properties)

---

### Task 1: Route & Cleanup — Wire `/` to homepage

**Files:**
- Modify: `public/index.php:104-138`

**Step 1: Update `/` route to serve landing4.php instead of coming-soon.php**

In `public/index.php`, change the `/` route handler:

```php
// BEFORE (line 104-110):
Router::get('/', function () {
    if (Auth::check()) {
        Router::redirect('/dashboard');
    }
    require BASE_PATH . '/public/coming-soon.php';
    exit;
});

// AFTER:
Router::get('/', function () {
    if (Auth::check()) {
        Router::redirect('/dashboard');
    }
    require BASE_PATH . '/public/landing4.php';
    exit;
});
```

**Step 2: Remove `/home` route (now redundant) and old landing routes**

Remove these route blocks from `public/index.php`:
- `/home` route (lines 112-118)
- `/landing` route (lines 125-128)
- `/landing2` route (lines 130-133)
- `/landing3` route (lines 135-138)

**Step 3: Verify PHP syntax**

Run: `php -l public/index.php`
Expected: No syntax errors

**Step 4: Test in browser**

- Visit `http://localhost/seo-toolkit/` — should show the landing page (currently landing4 content)
- Visit `http://localhost/seo-toolkit/pricing` — should still work
- Visit `http://localhost/seo-toolkit/landing` — should 404

**Step 5: Commit**

```bash
git add public/index.php
git commit -m "feat(landing): wire / to homepage, remove old landing routes"
```

---

### Task 2: Hero Section — New copy & mockup visual

**Files:**
- Modify: `public/landing4.php:610-653` (hero section HTML)
- Modify: `public/landing4.php:169-250` (hero CSS)

**Step 1: Update hero copy**

Replace the hero section content (lines 610-653) with:

- Badge: "Creata da professionisti SEO e ADV" (instead of "Potenziato da Claude AI")
- H1: "Dai la keyword.<br><span class="accent">Ainstein fa il resto.</span>"
- Sub: "Ricerca, piano editoriale, articoli SEO, pubblicazione. Processi che richiedono giorni, completati in minuti. Non AI generica — automazione costruita da chi fa SEO ogni giorno."
- CTA primary: "Prova gratis — 30 crediti" → `/register`
- CTA secondary: "Guarda come funziona ↓" → `#come-funziona`

**Step 2: Replace screenshot with HTML mockup**

Replace the `hero-screenshot` div (static `<img>` of dashboard) with an HTML/CSS mockup showing a mini workflow:
- Browser frame (dots bar like in module dashboards)
- Left panel: input field with "scarpe running" keyword
- Right panel: output preview showing article title, structure, SEO score badge
- Use CSS-only, no images

Add these CSS classes for the mockup:
- `.hero-mockup` — container with browser frame
- `.mockup-bar` — top bar with colored dots (reuse `.screen-bar` pattern)
- `.mockup-content` — inner layout (grid 2 cols)
- `.mockup-input` — left: keyword input simulation
- `.mockup-output` — right: article output simulation

**Step 3: Verify PHP syntax**

Run: `php -l public/landing4.php`

**Step 4: Test in browser**

Visit `/` — hero should show new copy, new mockup visual, no broken layout.

**Step 5: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): redesign hero with new copy and HTML mockup"
```

---

### Task 3: Pipeline Section — New visual workflow

**Files:**
- Modify: `public/landing4.php` — add new section between Hero and existing "Come funziona"

**Step 1: Add CSS for pipeline section**

Add after the hero CSS block (~line 250):

- `.pipeline-section` — light bg (`var(--bg)`)
- `.pipeline-flow` — flex row, centered, gap between steps
- `.pipeline-step` — card with icon top, mini-mockup bottom
- `.pipeline-arrow` — SVG arrow connector between steps
- `.pipeline-mini` — mini mockup frame (browser dots + content)

Responsive: on mobile (`max-width: 768px`), pipeline becomes vertical (flex-direction: column).

**Step 2: Add HTML for 5-step pipeline**

Insert new `<section>` between the Hero closing (`</section>` after line 653) and "Come funziona" section (line 656):

5 steps:
1. **Keyword seed** — Input field mockup: "scarpe running"
2. **Ricerca AI** — Mini table: "120+ keyword con volumi"
3. **Piano editoriale** — Mini calendar: "12 mesi di contenuti"
4. **Articolo SEO** — Mini doc: "2.800 parole, SEO score 92"
5. **Pubblicato su WP** — Mini browser: WordPress post live

Each step: icon top (Heroicons SVG), label, mini-mockup card below.
Steps connected by arrow SVGs.

Section heading: "Dall'idea alla pubblicazione" + sub: "Zero copia-incolla tra tool diversi. Un flusso unico dalla ricerca alla pubblicazione."

**Step 3: Verify PHP syntax + test in browser**

**Step 4: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): add pipeline visual workflow section"
```

---

### Task 4: Feature WOW Spotlight — 3 blocks with HTML mockups

**Files:**
- Modify: `public/landing4.php` — replace the toolkit tabs section (lines 694-871)

This is the biggest task. Replace the 6 tabs+screenshot layout with 3 large alternating feature blocks.

**Step 1: Add CSS for feature blocks**

Add to the CSS:
- `.feature-block` — full-width section, alternating bg (white / `var(--bg)`)
- `.feature-inner` — max-width, grid 2 cols, gap 64px, items center
- `.feature-inner.reverse` — reverses order for alternating layout
- `.feature-badge` — colored pill badge (reuse `.toolkit-badge` styles)
- `.feature-headline` — large heading (reuse `.toolkit-headline`)
- `.feature-bullets` — list with check icons (reuse `.toolkit-bullets`)
- `.feature-mockup` — browser frame mockup container (reuse `.toolkit-screenshot` frame)

Responsive: on tablet, single column; mockup goes above text.

**Step 2: Build Block 1 — AI Keyword Research + Piano Editoriale (purple)**

HTML mockup (right side): Cluster tree visualization adapted from `modules/keyword-research/views/dashboard.php:194-271`. Simplified version with:
- Browser frame (dots bar)
- Seed keyword "scarpe running" in center bubble
- 3 cluster branches (blue, emerald, amber) with keyword + volume
- Bottom stats bar: "127 keyword • 5 cluster • Volume totale: 48.200/mese"

Text (left side):
- Badge: "AI Keyword Research" (purple)
- Headline: "Da 3 keyword a un piano editoriale completo"
- Bullets: 3 items (expansion AI, clustering automatico, piano editoriale 12 mesi)
- CTA: "Prova gratis" → `/register`

**Step 3: Build Block 2 — AI Content Generator (amber)**

HTML mockup (left side — reversed): Article generation preview with:
- Browser frame
- Top: keyword badge "scarpe running principianti"
- Middle: article structure (H2s listed)
- Bottom: stats row: "2.800 parole • SEO Score: 92/100 • Tempo: 8 min"

Text (right side):
- Badge: "AI Content Generator" (amber)
- Headline: "Da keyword ad articolo pubblicato in 10 minuti"
- Bullets: analisi SERP top 10, brief + articolo completo, pubblicazione WP automatica
- CTA: "Prova gratis"

**Step 4: Build Block 3 — Google Ads Analyzer (rose)**

HTML mockup (right side): Campaign preview with:
- Browser frame
- Campaign name header
- 2-3 ad group cards with keyword count badges
- Bottom: "14 keyword negative • 3 gruppi annunci • Pronto per Ads Editor"

Text (left side):
- Badge: "Google Ads Analyzer" (rose)
- Headline: "Campagne Google Ads complete, generate dall'AI"
- Bullets: analisi competitor, keyword negative + struttura, pronto per Ads Editor
- CTA: "Prova gratis"

**Step 5: Remove old toolkit tabs section**

Delete the entire old section from `<section class="section-pad" id="funzionalita">` through its closing `</section>` (lines 694-871) and the associated JS tab logic (lines 1090-1097).

Also delete the old `.toolkit-*` CSS classes that are no longer used.

**Step 6: Verify PHP syntax + test in browser**

All 3 blocks should render with proper mockups, correct alternating layout, responsive on mobile.

**Step 7: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): replace module tabs with 3 feature spotlight blocks with HTML mockups"
```

---

### Task 5: Come Funziona — Concrete examples

**Files:**
- Modify: `public/landing4.php:656-691` (come funziona section)

**Step 1: Update the 3 step cards with concrete copy**

Replace the current generic text:

Card 01:
- Title: "Inserisci la keyword"
- Text: "Scrivi 'scarpe running' e scegli il modulo. Ainstein si occupa del resto."

Card 02:
- Title: "L'AI analizza e crea"
- Text: "Studia la SERP di Google, analizza i competitor, genera output strategici — non contenuti generici."

Card 03:
- Title: "Risultati pronti all'uso"
- Text: "Articoli SEO, piani editoriali, campagne Ads. Output professionali, non bozze da rifare."

Also update the section subtitle: "Ainstein non è l'ennesimo tool AI generico. È un sistema costruito da professionisti SEO e ADV."

**Step 2: Verify + test**

**Step 3: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): update come-funziona with concrete examples"
```

---

### Task 6: Toolkit Grid — Compact 6-module overview

**Files:**
- Modify: `public/landing4.php` — add new section after the 3 feature blocks

**Step 1: Add CSS for module grid**

- `.modules-grid` — grid 3 cols (2 on tablet, 1 on mobile), gap 24px
- `.module-card` — white card, rounded-20px, border, padding 32px, hover lift
- `.module-icon` — 48px square, rounded, colored bg + icon
- `.module-card h4` — 18px bold
- `.module-card p` — 14px muted
- `.module-link` — text link with arrow, colored by module

**Step 2: Add HTML for 6 module cards**

Section heading: "La suite completa" + sub: "7 moduli integrati. Un'unica piattaforma."

Cards (with module colors):
1. **AI Content Generator** (amber) — "Da keyword ad articolo SEO pubblicato"
2. **AI Keyword Research** (purple) — "120+ keyword clusterizzate con AI"
3. **SEO Audit** (emerald) — "Audit tecnico + action plan AI"
4. **Position Tracking** (blue) — "Monitora posizioni con report AI"
5. **Google Ads Analyzer** (rose) — "Campagne Ads generate dall'AI"
6. **Content Creator** (cyan) — "Contenuti HTML per 4 CMS"

Each card: icon (Heroicons SVG), name, tagline, "Scopri di più →" link (anchor to #funzionalita or future feature pages).

**Step 3: Verify + test**

**Step 4: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): add compact 6-module toolkit grid"
```

---

### Task 7: Pain/Solution — Snellire + Social Proof update

**Files:**
- Modify: `public/landing4.php:874-970` (pain section + social proof)

**Step 1: Reduce pain/solution to 4 rows**

Keep the 4 strongest rows, remove "Creare campagne Ads richiede esperienza" (less universal). Keep:
1. Articoli SEO (4-6 ore → 10 minuti)
2. Keyword research (tool costosi → 1 click)
3. Audit tecnici (manuali → AI action plan)
4. Monitoraggio posizioni (tedioso → report AI automatici)

**Step 2: Update social proof section**

After the counter grid, add a positioning statement:

```html
<p style="...">
    Ainstein è costruita da professionisti SEO e ADV che automatizzano
    i processi che fanno ogni giorno. Non AI generica — automazione di qualità.
</p>
```

Center it, use `color: #94a3b8`, `font-size: 17px`, `max-width: 600px`.

**Step 3: Verify + test**

**Step 4: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): refine pain-solution and add positioning statement"
```

---

### Task 8: Pricing — Simplify to 3 visible plans

**Files:**
- Modify: `public/landing4.php:972-1053` (pricing section)

**Step 1: Filter plans to show only 3**

In the PHP loop, skip the "starter" plan (show only free, pro, agency). Or alternatively filter to show the 3 most distinct plans. Update the grid to `grid-template-columns: repeat(3, 1fr)`.

**Step 2: Add "Confronta tutti i piani" link**

After the pricing grid, add a prominent link:

```html
<div class="text-center" style="margin-top:32px;">
    <a href="/pricing" class="pricing-more">
        Confronta tutti i piani nel dettaglio
        <svg>→</svg>
    </a>
</div>
```

**Step 3: Verify + test**

**Step 4: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): simplify pricing to 3 plans with link to full comparison"
```

---

### Task 9: CTA Finale — Updated messaging

**Files:**
- Modify: `public/landing4.php:1055-1065` (CTA section)

**Step 1: Update CTA copy**

```html
<h2>Inizia ad automatizzare il tuo SEO</h2>
<p>30 crediti gratuiti alla registrazione. Nessuna carta di credito richiesta.</p>
<a href="/register" class="btn-cta-large">
    Crea account gratis
    <svg>→</svg>
</a>
```

**Step 2: Commit**

```bash
git add public/landing4.php
git commit -m "feat(landing): update CTA finale copy"
```

---

### Task 10: Cleanup — Remove obsolete files

**Files:**
- Delete: `public/coming-soon.php`
- Delete: `public/landing.php`
- Delete: `public/landing2.php`
- Delete: `public/landing3.php`

**Step 1: Verify no other references to these files**

Search codebase for references to `coming-soon.php`, `landing.php`, `landing2.php`, `landing3.php`.

**Step 2: Delete the files**

```bash
git rm public/coming-soon.php public/landing.php public/landing2.php public/landing3.php
```

**Step 3: Commit**

```bash
git commit -m "chore: remove obsolete landing page files"
```

---

### Task 11: Final QA — Cross-browser, mobile, performance

**Step 1: Test all pages in browser**

- `/` — full homepage with all new sections
- `/pricing` — still works
- `/login`, `/register` — still work
- `/docs` — still works

**Step 2: Test responsive**

- Desktop (1200px+): all grids correct
- Tablet (768-1024px): feature blocks stack, pricing 2-col
- Mobile (<768px): everything single column, pipeline vertical, hamburger menu works

**Step 3: Check page weight**

Ensure no broken images, no console errors, smooth scroll reveal animations.

**Step 4: Final commit if any fixes needed**

---

## Summary

| Task | Description | Estimated effort |
|------|-------------|-----------------|
| 1 | Route & cleanup wiring | Small |
| 2 | Hero section redesign | Medium |
| 3 | Pipeline visual workflow (new) | Medium-Large |
| 4 | Feature WOW spotlight (3 blocks) | Large (biggest task) |
| 5 | Come funziona update | Small |
| 6 | Toolkit grid (6 modules) | Medium |
| 7 | Pain/Solution + Social proof | Small |
| 8 | Pricing simplification | Small |
| 9 | CTA finale | Small |
| 10 | File cleanup | Small |
| 11 | Final QA | Medium |

Total: 11 tasks, ~8 commits.
