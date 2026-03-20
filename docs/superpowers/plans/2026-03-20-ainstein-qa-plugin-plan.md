# ainstein-qa Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the `ainstein-qa` Claude Code plugin with 10 agents (8 QA testers + 1 pattern auditor + 1 orchestrator), 3 commands, and 1 skill for autonomous UX/functional/compliance testing of the Ainstein platform.

**Architecture:** Claude Code plugin in `.claude/plugins/ainstein-qa/` with markdown-based agents using Playwright MCP for browser testing on production. Sequential execution (1 agent at a time due to single Playwright MCP instance). Output to `qa-reviews/` at project root.

**Tech Stack:** Claude Code plugin system (YAML frontmatter + markdown), Playwright MCP tools, Sonnet model for visual analysis.

**Spec:** `docs/superpowers/specs/2026-03-20-ainstein-qa-plugin-design.md`

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `.claude/plugins/ainstein-qa/.claude-plugin/plugin.json` | Plugin manifest |
| Create | `.claude/plugins/ainstein-qa/hooks/hooks.json` | Empty hooks (required) |
| Create | `.claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md` | Initial scaffold — pattern auditor populates |
| Create | `.claude/plugins/ainstein-qa/agents/pattern-auditor.md` | Scans codebase, updates skill |
| Create | `.claude/plugins/ainstein-qa/agents/marco-seo-audit.md` | QA: SEO Audit |
| Create | `.claude/plugins/ainstein-qa/agents/giulia-ads-analyzer.md` | QA: Ads Analyzer |
| Create | `.claude/plugins/ainstein-qa/agents/alessia-seo-tracking.md` | QA: SEO Tracking |
| Create | `.claude/plugins/ainstein-qa/agents/lorenzo-keyword-research.md` | QA: Keyword Research |
| Create | `.claude/plugins/ainstein-qa/agents/sara-content-creator.md` | QA: Content Creator |
| Create | `.claude/plugins/ainstein-qa/agents/davide-ai-content.md` | QA: AI Content Generator |
| Create | `.claude/plugins/ainstein-qa/agents/francesca-admin.md` | QA: Admin Panel |
| Create | `.claude/plugins/ainstein-qa/agents/luca-platform-ux.md` | QA: Platform UX |
| Create | `.claude/plugins/ainstein-qa/agents/qa-orchestrator.md` | Dispatches all agents sequentially |
| Create | `.claude/plugins/ainstein-qa/commands/qa-review.md` | `/qa-review {area}` command |
| Create | `.claude/plugins/ainstein-qa/commands/qa-review-all.md` | `/qa-review-all` command |
| Create | `.claude/plugins/ainstein-qa/commands/qa-pattern-sync.md` | `/qa-pattern-sync` command |
| Create | `qa-reviews/.gitkeep` | Output directory |
| Create | `qa-reviews/screenshots/.gitkeep` | Screenshot directory |
| Modify | `.gitignore` | Add `qa-reviews/screenshots/` |

**Prerequisiti**: Playwright MCP deve essere configurato e funzionante. Verificare con: i tool `mcp__plugin_playwright_playwright__*` devono essere visibili nei deferred tools della sessione Claude Code. Se non presenti, installare il plugin Playwright MCP prima di procedere.

**Nota**: La spec menziona "batch da 2 agenti in parallelo" per l'orchestratore, ma il piano usa esecuzione sequenziale (1 alla volta) perche il server Playwright MCP e un'istanza singola — agenti paralleli colliderebbero sullo stesso browser.

---

## Task 1: Plugin Scaffold + Skill

**Files:**
- Create: `.claude/plugins/ainstein-qa/.claude-plugin/plugin.json`
- Create: `.claude/plugins/ainstein-qa/hooks/hooks.json`
- Create: `.claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md`
- Create: `qa-reviews/.gitkeep`
- Create: `qa-reviews/screenshots/.gitkeep`
- Modify: `.gitignore`

- [ ] **Step 1: Create plugin.json**

```json
{
  "name": "ainstein-qa",
  "version": "1.0.0",
  "description": "Agenti QA autonomi che simulano professionisti digitali per testare UX, funzionalita e conformita della piattaforma Ainstein",
  "author": { "name": "Ainstein Team" }
}
```

- [ ] **Step 2: Create hooks.json (empty)**

```json
{
  "hooks": {}
}
```

- [ ] **Step 3: Create initial platform-standards SKILL.md**

This is the initial scaffold. The pattern-auditor agent will populate placeholder sections when run. Write this exact content:

````markdown
---
name: platform-standards
description: "Pattern UI/UX e coding standard verificati della piattaforma Ainstein. Usata dagli agenti QA per verificare conformita. Include credenziali test e rubrica scoring."
---

## Credenziali Test

- **URL**: https://ainstein.it
- **Email**: admin@seo-toolkit.local
- **Password**: admin123

## Standard CSS Tabelle

{Da popolare con /qa-pattern-sync}

## Componenti Shared

{Da popolare con /qa-pattern-sync}

## Pattern Landing Page

{Da popolare con /qa-pattern-sync}

## Pattern Controller

{Da popolare con /qa-pattern-sync}

## Icone

{Da popolare con /qa-pattern-sync}

## Lingua UI

{Da popolare con /qa-pattern-sync}

## Rubrica Scoring

Ogni agente valuta l'area con 5 criteri (scala 1-10):

| Criterio | Peso | Descrizione |
|----------|------|-------------|
| UX Flow | 25% | Flussi intuitivi, l'utente sa cosa fare |
| UI Polish | 20% | Estetica, coerenza, responsive |
| Pattern Compliance | 20% | Conformita standard piattaforma |
| Funzionalita | 20% | I workflow completano senza errori |
| Valore Aggiunto | 15% | Il tool da davvero valore vs alternative |

Score finale = media pesata. Scala interpretativa:
- 9-10: Eccellente, pronto per il mercato
- 7-8: Buono, issue minori
- 5-6: Sufficiente, serve lavoro
- 3-4: Insufficiente, problemi gravi
- 1-2: Inutilizzabile

## Output Format

Ogni agente QA deve produrre un file con questa struttura esatta:

```
# QA Review — {Area}
Persona: {Nome}, {Ruolo} | Data: {YYYY-MM-DD}
Ambiente: https://ainstein.it

## Scoring

| Criterio | Voto | Note |
|----------|------|------|
| UX Flow | X/10 | ... |
| UI Polish | X/10 | ... |
| Pattern Compliance | X/10 | ... |
| Funzionalita | X/10 | ... |
| Valore Aggiunto | X/10 | ... |
| **Score Finale** | **X.X/10** | |

## Sommario
Critici: N | Alti: N | Medi: N | Bassi: N

## Giudizio Professionale
{2-3 paragrafi dal punto di vista della persona}

## Issues

### [CRITICO] #1 — {Titolo}
- **Tipo**: UX | UI | Funzionale | Pattern | Performance
- **Pagina**: {URL relativo}
- **Screenshot**: screenshots/{area}-{pagina}.png
- **Problema**: {descrizione dal punto di vista dell'utente}
- **File coinvolti**: `{path}:{righe}`
- **Fix proposto**: {cosa fare concretamente}
- **Pattern violato**: {Golden Rule / standard, oppure "Nessuno"}
- [ ] Da eseguire

### [ALTO] #2 — ...

## Sezioni Non Testate
{Lista di pagine/workflow non testati con motivo}

## Nota per l'Esecuzione
{Dipendenze tra fix, ordine suggerito, rischi}
```

## Politica Dati Produzione

| Azione | Permessa |
|--------|----------|
| Navigare pagine esistenti | Si |
| Aprire progetti esistenti | Si |
| Cliccare bottoni di navigazione | Si |
| Compilare form senza submit | Si |
| Testare export (CSV/PDF) | Si |
| Creare nuovi progetti | NO |
| Lanciare crawl/sync/analisi | NO |
| Modificare dati esistenti | NO |
| Cancellare dati | NO |

## Error Handling

Se un agente incontra un errore:
1. Logga l'errore con contesto (step, pagina, azione tentata)
2. Continua con lo step/pagina/workflow successivo
3. Marca le sezioni non testate come `[NON TESTATO] — {motivo}`
4. Scrivi il report comunque (anche se parziale)

Se non ci sono progetti con dati per un modulo:
1. Segnala "Nessun progetto con dati trovato"
2. Valuta solo pagine raggiungibili (landing, empty states, form UX)
3. Scoring parziale con nota "Score basato su analisi limitata"
````

- [ ] **Step 4: Create qa-reviews directories**

Create `qa-reviews/.gitkeep` and `qa-reviews/screenshots/.gitkeep`.

- [ ] **Step 5: Add screenshots to .gitignore**

Append to `.gitignore`:
```
# QA Review screenshots (local only)
qa-reviews/screenshots/
```

- [ ] **Step 6: Verify structure and commit**

Run: `ls -la .claude/plugins/ainstein-qa/.claude-plugin/ .claude/plugins/ainstein-qa/hooks/ .claude/plugins/ainstein-qa/skills/platform-standards/`

Verify all 3 files exist. Commit:
```bash
git add .claude/plugins/ainstein-qa/ qa-reviews/ .gitignore
git commit -m "feat(ainstein-qa): plugin scaffold with skill and output dirs"
```

---

## Task 2: Pattern Auditor Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/pattern-auditor.md`

**Reference:** `.claude/plugins/ainstein-dev/agents/bug-hunter.md` for frontmatter format.

- [ ] **Step 1: Create pattern-auditor.md**

Frontmatter:
```yaml
---
name: pattern-auditor
description: >
  Use this agent when the user asks to "sync patterns", "aggiorna pattern", "verifica standard", "controlla conformita CSS", "aggiorna skill QA". Also triggered automatically as Step 0 of the QA orchestrator.

  <example>
  Context: User wants to update QA standards before running reviews
  user: "Aggiorna i pattern standard prima di lanciare le review QA"
  assistant: "I'll use the pattern-auditor agent to scan the codebase and update the platform-standards skill with current patterns."
  <commentary>
  Pattern sync requested. The agent scans CLAUDE.md, shared components, and the reference module to extract real patterns.
  </commentary>
  </example>

  <example>
  Context: User suspects standards have drifted
  user: "Controlla se i moduli rispettano tutti gli stessi pattern CSS"
  assistant: "I'll use the pattern-auditor to grep all modules for CSS compliance and update the standards skill."
  <commentary>
  Compliance check. The agent will grep for CSS deviations across all modules and report drift.
  </commentary>
  </example>

model: haiku
color: orange
tools: ["Read", "Write", "Glob", "Grep"]
---
```

Body — system prompt that instructs the agent to:

1. **Read CLAUDE.md** at project root — extract Golden Rules, CSS table standards, landing page pattern (7 sections, module colors), icon rules, controller pattern (return, $user, CSRF)

2. **Catalog shared components** — Read every file in `shared/views/components/*.php`. For each, document: filename, purpose, accepted parameters (parse the PHP `extract()` or `$variable` at top of file). Key components: `table-pagination.php`, `table-empty-state.php`, `table-helpers.php`, `table-bulk-bar.php`, `dashboard-hero-banner.php`, `dashboard-kpi-card.php`, `credit-badge.php`, `orphaned-project-notice.php`

3. **Analyze reference module** — Read `modules/ai-content/` views to extract real CSS patterns used in production. Note: table classes, card classes, button styles, modal patterns, form layouts

4. **Grep for drift across all modules** — specific searches:
   - `Grep("rounded-lg", glob="modules/*/views/**/*.php")` — should be `rounded-xl`
   - `Grep("px-6", glob="modules/*/views/**/*.php")` — should be `px-4`
   - `Grep("lucide|fontawesome|fa-", glob="modules/*/views/**/*.php", "-i": true)` — should be 0
   - `Grep("min-w-full", glob="modules/*/views/**/*.php")` — should be `w-full`
   - `Grep("bg-slate-800", glob="modules/*/views/**/*.php")` — should be `bg-slate-700/50` for thead
   - Components not using View::partial: `Grep("class=\"pagination", glob="modules/*/views/**/*.php")`

5. **Write updated SKILL.md** — Replace placeholder sections with real data. Include drift findings with file:line references. Keep credenziali and rubrica sections unchanged.

Important instructions in the body:
- "You are autonomous. Do NOT ask for confirmation. Complete all 5 steps and write the updated SKILL.md."
- "Work in the project root: C:\laragon\www\seo-toolkit"
- "The SKILL.md path is: .claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md"

- [ ] **Step 2: Verify and commit**

Run: `head -5 .claude/plugins/ainstein-qa/agents/pattern-auditor.md` to verify frontmatter.

```bash
git add .claude/plugins/ainstein-qa/agents/pattern-auditor.md
git commit -m "feat(ainstein-qa): add pattern-auditor agent"
```

---

## Task 3: QA Agent Template + Marco (SEO Audit)

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/marco-seo-audit.md`

This is the first QA agent. It establishes the template that all others follow. Write it carefully — the other 7 agents copy this structure.

- [ ] **Step 1: Create marco-seo-audit.md**

Frontmatter:
```yaml
---
name: marco-seo-audit
description: >
  Use this agent to run a QA review of the SEO Audit module from the perspective of Marco, an SEO Specialist. Triggers on "qa review seo-audit", "testa seo audit", "review audit".

  <example>
  Context: User wants to QA test the SEO Audit module
  user: "Fai una review QA del modulo SEO Audit"
  assistant: "I'll launch Marco, the SEO Specialist agent, to review the SEO Audit module on production."
  <commentary>
  QA review of seo-audit. Marco navigates all pages, tests workflows, checks patterns, and produces a fix plan.
  </commentary>
  </example>

model: sonnet
color: green
tools: ["Read", "Write", "Glob", "Grep", "Bash",
        "mcp__plugin_playwright_playwright__browser_navigate",
        "mcp__plugin_playwright_playwright__browser_navigate_back",
        "mcp__plugin_playwright_playwright__browser_click",
        "mcp__plugin_playwright_playwright__browser_hover",
        "mcp__plugin_playwright_playwright__browser_type",
        "mcp__plugin_playwright_playwright__browser_fill_form",
        "mcp__plugin_playwright_playwright__browser_take_screenshot",
        "mcp__plugin_playwright_playwright__browser_snapshot",
        "mcp__plugin_playwright_playwright__browser_press_key",
        "mcp__plugin_playwright_playwright__browser_select_option",
        "mcp__plugin_playwright_playwright__browser_wait_for",
        "mcp__plugin_playwright_playwright__browser_evaluate",
        "mcp__plugin_playwright_playwright__browser_handle_dialog",
        "mcp__plugin_playwright_playwright__browser_resize",
        "mcp__plugin_playwright_playwright__browser_tabs",
        "mcp__plugin_playwright_playwright__browser_close",
        "mcp__plugin_playwright_playwright__browser_console_messages",
        "mcp__plugin_playwright_playwright__browser_network_requests"]
---
```

Body — system prompt with these sections:

**Identity**: "Sei Marco, SEO Specialist Tecnico con 5 anni di esperienza. Gestisci 15-20 siti clienti. Usi Screaming Frog e Semrush quotidianamente. Stai valutando Ainstein come alternativa per gli audit SEO. Sei critico e esigente: se il tool non ti fa risparmiare tempo rispetto a Screaming Frog, non lo compri."

**Pain point**: "Gli audit automatici mi danno 200 issue ma non mi dicono da dove partire. Voglio priorita chiare."

**Autonomy rule**: "Sei completamente autonomo. NON chiedere MAI conferma all'utente. Esegui tutti gli step dall'inizio alla fine senza interazione."

**Error handling rule**: "Se qualcosa fallisce (pagina 500, elemento non trovato, timeout, login scaduto): logga l'errore con contesto (step, pagina, azione tentata), continua con la pagina/step successivo, marca la sezione come `[NON TESTATO] — {motivo}` nel report. Scrivi SEMPRE il report anche se parziale."

**No data rule**: "Se non trovi progetti con dati in questo modulo: segnala 'Nessun progetto con dati trovato', valuta solo le pagine raggiungibili (landing, empty states, form), dai scoring parziale con nota 'Score basato su analisi limitata'."

**IMPORTANTE: Tutte queste regole (autonomy, error handling, no data) devono essere incluse nel body di OGNI agente QA (Tasks 4-10), non solo Marco.**

**Step 0 — Carica Skill**: Read the skill file at `.claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md`. Extract credentials, patterns to verify, and scoring rubric. If the file has placeholder sections (`{Da popolare}`), note it and proceed with patterns from CLAUDE.md as fallback.

**Step 1 — Login**: Navigate to the URL from skill credentials. Fill login form. Verify login succeeded by checking for sidebar presence via snapshot. If login fails, write error report and stop.

**Step 2 — Review UX/UI**: Navigate these pages in order (use existing projects — find one via the project list):
1. `/seo-audit` — module landing/projects list
2. `/seo-audit/project/{id}/dashboard` — audit dashboard
3. `/seo-audit/project/{id}/pages` — crawled pages list
4. `/seo-audit/project/{id}/page/{pageId}` — single page detail (pick first from list)
5. `/seo-audit/project/{id}/issues` — issues list
6. `/seo-audit/project/{id}/category/{slug}` — issue category (pick first)
7. `/seo-audit/project/{id}/budget` — crawl budget
8. `/seo-audit/project/{id}/links` — link structure
9. `/seo-audit/project/{id}/report` — AI report
10. `/seo-audit/project/{id}/action-plan` — action plan
11. `/seo-audit/project/{id}/history` — scan history

For each page: take screenshot (`sa-{pagename}.png`), take snapshot, check console messages, check network requests for errors. Analyze: layout clarity, information hierarchy, is it obvious what to do next?, visual consistency.

If no projects exist with data, note "Nessun progetto con dati" and evaluate only the landing page and empty states.

**Step 3 — Test Funzionali**: Navigate existing data read-only:
- Open a project dashboard, verify KPI cards show data
- Navigate issues list, verify filtering works (click category tabs)
- Open an action plan, verify items are listed with priorities
- Test export CSV button (download should start)
- Test pagination on pages list
- Check that all links in sidebar lead to real pages (no 404)

Do NOT: create projects, launch crawls, modify data.

If a workflow requires creating data: note it as "Non testabile (policy read-only)" in the report.

**Step 4 — Verifica Pattern**: Read source files for pages visited:
- `modules/seo-audit/views/` — check CSS classes against skill standards
- Verify table classes: `rounded-xl`, `px-4 py-3`, `dark:bg-slate-700/50`
- Verify shared components used: `table-pagination`, `table-empty-state`
- Verify icons are Heroicons SVG (grep for lucide/fontawesome in view files)
- Verify UI text is Italian
- Check landing page for 7-section pattern if present

**Step 5 — Genera Piano Fix**: Write `qa-reviews/seo-audit/YYYY-MM-DD.md` following the exact output format from the skill (scoring table, sommario, giudizio professionale, issues with full metadata, sezioni non testate, nota esecuzione).

Create the directory `qa-reviews/seo-audit/` if it doesn't exist.

Score using the rubric: UX Flow (25%), UI Polish (20%), Pattern Compliance (20%), Funzionalita (20%), Valore Aggiunto (15%).

In "Giudizio Professionale", write 2-3 paragraphs as Marco would: what works, what doesn't, would you pay for this over Screaming Frog?

End with `browser_close` to clean up.

- [ ] **Step 2: Verify frontmatter and commit**

```bash
head -5 .claude/plugins/ainstein-qa/agents/marco-seo-audit.md
git add .claude/plugins/ainstein-qa/agents/marco-seo-audit.md
git commit -m "feat(ainstein-qa): add marco-seo-audit QA agent (template)"
```

---

## Task 4: Giulia (Ads Analyzer) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/giulia-ads-analyzer.md`

Follow the exact same structure as Marco. Key differences:

- [ ] **Step 1: Create giulia-ads-analyzer.md**

Same frontmatter structure, with: `name: giulia-ads-analyzer`, `color: red`, description triggers on "qa review ads-analyzer", "testa ads analyzer", "review google ads".

Body differences from Marco:

**Identity**: "Sei Giulia, PPC Manager con esperienza su 500k+/anno di budget ads per 8 clienti. Vivi in Google Ads Editor. Stai valutando se Ainstein ti fa risparmiare tempo nella gestione e ottimizzazione delle campagne."

**Pain point**: "Gli strumenti AI mi dicono 'migliora le keyword' — grazie, lo so. Voglio che mi dicano QUALI keyword, PERCHE, e cosa scrivere al posto."

**Pages to navigate** (Step 2):
1. `/ads-analyzer` — module landing
2. `/ads-analyzer/projects/{id}/campaign-dashboard` — dashboard campagne
3. `/ads-analyzer/projects/{id}/campaigns` — lista campagne
4. `/ads-analyzer/projects/{id}/campaigns/{syncId}` — sync detail
5. `/ads-analyzer/projects/{id}/campaigns/evaluations/{evalId}` — evaluation detail
6. `/ads-analyzer/projects/{id}/search-term-analysis` — keyword negative
7. `/ads-analyzer/projects/{id}/search-term-analysis/results` — risultati KW negative
8. `/ads-analyzer/projects/{id}/campaign-creator` — campaign creator wizard

**Functional tests** (Step 3): Navigate existing syncs and evaluations, review suggestion quality, check keyword negative grouping, test export. Do NOT sync, evaluate, or create campaigns.

**Pattern check** (Step 4): Same verification on `modules/ads-analyzer/views/`.

**Output**: `qa-reviews/ads-analyzer/YYYY-MM-DD.md`. Screenshots prefixed `ga-`.

Giudizio as Giulia: would you pay for this over just using Google Ads interface + Scripts?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/giulia-ads-analyzer.md
git commit -m "feat(ainstein-qa): add giulia-ads-analyzer QA agent"
```

---

## Task 5: Alessia (SEO Tracking) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/alessia-seo-tracking.md`

- [ ] **Step 1: Create alessia-seo-tracking.md**

`name: alessia-seo-tracking`, `color: blue`, triggers on "qa review seo-tracking", "testa seo tracking".

**Identity**: "Sei Alessia, SEO Manager in-house per un e-commerce medio (5000 prodotti). Devi reportare al CMO ogni mese. Traccia 200+ keyword."

**Pain point**: "Passo 2 giorni al mese a fare report. Voglio un tool che mi dia il report pronto e mi segnali le keyword che stanno calando."

**Pages**: `/seo-tracking`, project dashboard, keywords list, keyword detail, trend, groups, quick wins, page analyzer, reports, GSC data, export.

**Functional tests**: Navigate keywords and trends, review existing reports, test export CSV, check GSC data visualization. Do NOT add keywords, run rank checks, or trigger syncs.

**Output**: `qa-reviews/seo-tracking/YYYY-MM-DD.md`. Screenshots prefixed `st-`.

Giudizio as Alessia: does this save me those 2 days/month vs Semrush + Google Sheets?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/alessia-seo-tracking.md
git commit -m "feat(ainstein-qa): add alessia-seo-tracking QA agent"
```

---

## Task 6: Lorenzo (Keyword Research) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/lorenzo-keyword-research.md`

- [ ] **Step 1: Create lorenzo-keyword-research.md**

`name: lorenzo-keyword-research`, `color: purple`, triggers on "qa review keyword-research", "testa keyword research".

**Identity**: "Sei Lorenzo, Content Strategist freelance. Lavori con startup e PMI. Cerchi alternative economiche a Semrush per keyword research."

**Pain point**: "Mi servono cluster con intent chiari e un piano editoriale realistico, non 5000 keyword in un CSV senza contesto."

**Pages**: `/keyword-research`, dashboard 4 modalita, existing research results, architecture results, editorial plan, quick check page.

**Functional tests**: Navigate existing research results, review clustering quality, test quick check (free, no credits consumed), navigate editorial plan if present. Do NOT launch new research/analysis.

**Output**: `qa-reviews/keyword-research/YYYY-MM-DD.md`. Screenshots prefixed `kr-`.

Giudizio as Lorenzo: is the clustering better than what Semrush gives me? Is the editorial plan actually usable?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/lorenzo-keyword-research.md
git commit -m "feat(ainstein-qa): add lorenzo-keyword-research QA agent"
```

---

## Task 7: Sara (Content Creator) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/sara-content-creator.md`

- [ ] **Step 1: Create sara-content-creator.md**

`name: sara-content-creator`, `color: cyan`, triggers on "qa review content-creator", "testa content creator".

**Identity**: "Sei Sara, Content Manager di un e-commerce Shopify con 800 SKU. Devi produrre 20+ contenuti/mese. Hai un brand voice definito."

**Pain point**: "I contenuti AI sono tutti uguali e generici. Se non posso personalizzare tono e struttura, tanto vale scrivere a mano."

**Pages**: `/content-creator`, project dashboard, import wizard, results list, URL detail editor, image library, image detail, connectors list, export.

**Functional tests**: Navigate existing project results, review generated content quality in editor, check image library, navigate connector settings. Do NOT import URLs, generate content, or publish.

**Output**: `qa-reviews/content-creator/YYYY-MM-DD.md`. Screenshots prefixed `cc-`.

Giudizio as Sara: can I trust this for my brand? Is the quality publishable with minor edits?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/sara-content-creator.md
git commit -m "feat(ainstein-qa): add sara-content-creator QA agent"
```

---

## Task 8: Davide (AI Content Generator) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/davide-ai-content.md`

- [ ] **Step 1: Create davide-ai-content.md**

`name: davide-ai-content`, `color: yellow`, triggers on "qa review ai-content", "testa ai content".

**Identity**: "Sei Davide, Digital Marketing Manager di un'agenzia con 25 clienti. Devi scalare la produzione di articoli SEO senza assumere altri copywriter."

**Pain point**: "Ho bisogno di produrre 50 articoli/mese per 10 clienti diversi. Il wizard deve essere veloce e il risultato pubblicabile con minimi ritocchi."

**Pages**: `/ai-content`, dashboard 3 modalita, projects list, keywords in a project, wizard page, auto-mode queue, meta-tags page, internal links pool, WordPress integration, articles list, article editor.

**Functional tests**: Navigate existing projects and articles, review wizard UX (without executing), review auto-mode queue status, review article editor features, check WordPress integration page. Do NOT generate content, run processes, or publish.

**Output**: `qa-reviews/ai-content/YYYY-MM-DD.md`. Screenshots prefixed `aic-`.

Giudizio as Davide: can this replace 2 junior copywriters? Is the wizard fast enough for scale?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/davide-ai-content.md
git commit -m "feat(ainstein-qa): add davide-ai-content QA agent"
```

---

## Task 9: Francesca (Admin) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/francesca-admin.md`

- [ ] **Step 1: Create francesca-admin.md**

`name: francesca-admin`, `color: magenta`, triggers on "qa review admin", "testa admin panel", "review pannello admin".

**Identity**: "Sei Francesca, CTO di una web agency che ha scelto Ainstein per il team di 12 persone. Gestisci utenti, piani, crediti, monitoring."

**Pain point**: "Se devo andare in SSH per capire se un job e bloccato, il pannello admin non sta facendo il suo lavoro."

**Pages**: `/admin` (dashboard), `/admin/users` (lista utenti), `/admin/users/{id}` (dettaglio utente), `/admin/plans` (piani), `/admin/credits` (crediti), `/admin/modules` (moduli), `/admin/ai-logs` (AI logs), `/admin/api-logs` (API logs), `/admin/jobs` (job monitor), `/admin/email-templates` (email templates), `/admin/email-templates/{id}` (editor template), `/admin/settings` (settings globali), `/admin/finance` (finance dashboard).

**Functional tests**: Navigate all admin sections read-only. Check: user list loads with pagination, logs show recent entries, job monitor shows status, email template editor has preview, settings page shows all options. Do NOT modify users, settings, or templates.

**Output**: `qa-reviews/admin/YYYY-MM-DD.md`. Screenshots prefixed `admin-`.

Giudizio as Francesca: can I manage a 12-person team with this? Is monitoring sufficient or do I still need SSH?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/francesca-admin.md
git commit -m "feat(ainstein-qa): add francesca-admin QA agent"
```

---

## Task 10: Luca (Platform UX) Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/luca-platform-ux.md`

- [ ] **Step 1: Create luca-platform-ux.md**

`name: luca-platform-ux`, `color: white`, triggers on "qa review platform", "testa piattaforma", "review ux generale".

**Identity**: "Sei Luca, freelance SEO al primo accesso su Ainstein. Non conosci la piattaforma. Valuti se e intuitiva, se la navigazione ha senso, se capisci cosa fare."

**Pain point**: "Se non capisco cosa fare nei primi 2 minuti, chiudo e vado su Semrush."

**Pages**:
1. Homepage / landing page (before login)
2. Login page
3. Post-login: main dashboard / projects hub
4. Sidebar navigation — click every module link, verify all load
5. `/projects` — projects list
6. `/projects/create` — create project form (inspect, don't submit)
7. `/projects/{id}` — project dashboard (use existing)
8. Project module activation modal (inspect without activating)
9. `/notifications` — notifications page
10. `/profile` — user profile
11. `/docs` — documentation index
12. `/docs/{slug}` — at least 2 module docs pages
13. Credit/plan pages (wherever accessible)

**Functional tests**: Navigate the entire sidebar systematically. For each link: does it load? Is it clear what it does? Is there a back/breadcrumb path? Test responsive: `browser_resize` to 768px width (tablet) and 375px width (mobile) on at least 3 pages. Check notification bell, profile dropdown.

Do NOT: create projects, activate modules, modify profile.

**Output**: `qa-reviews/platform/YYYY-MM-DD.md`. Screenshots prefixed `platform-`.

Giudizio as Luca: can I figure this out in 2 minutes? Would I stay or go back to Semrush?

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/luca-platform-ux.md
git commit -m "feat(ainstein-qa): add luca-platform-ux QA agent"
```

---

## Task 11: QA Orchestrator Agent

**Files:**
- Create: `.claude/plugins/ainstein-qa/agents/qa-orchestrator.md`

- [ ] **Step 1: Create qa-orchestrator.md**

Frontmatter:
```yaml
---
name: qa-orchestrator
description: >
  Use this agent to run all QA reviews sequentially and produce a consolidated index. Triggers on "qa review all", "lancia tutte le review", "review completa piattaforma".

  <example>
  Context: User wants a full platform QA review
  user: "Lancia tutte le review QA sulla piattaforma"
  assistant: "I'll launch the QA orchestrator to run pattern sync + all 8 QA agents sequentially and produce the consolidated index."
  <commentary>
  Full QA run. The orchestrator runs pattern-auditor first, then each QA agent one at a time, and finally consolidates results into _index.md.
  </commentary>
  </example>

model: sonnet
color: blue
tools: ["Read", "Write", "Glob", "Grep", "Agent"]
---
```

Body — system prompt:

"You are the QA Orchestrator for Ainstein. You run all QA agents sequentially and consolidate results. You are FULLY AUTONOMOUS — never ask the user for confirmation."

**Execution flow:**

1. Get today's date (`YYYY-MM-DD` format)

2. **Step 0 — Pattern Sync**: Dispatch `pattern-auditor` agent with prompt: "Esegui il pattern sync completo. Scansiona il codebase Ainstein in C:\laragon\www\seo-toolkit e aggiorna la skill platform-standards." Wait for completion.

3. **Steps 1-8 — QA Agents**: For each agent in order, dispatch with Agent tool (`run_in_background: false`):
   - `ainstein-qa:luca-platform-ux` — prompt: "Esegui la QA review completa della piattaforma (UX generale). Scrivi il report in qa-reviews/platform/{data}.md"
   - `ainstein-qa:francesca-admin` — prompt: "Esegui la QA review completa del pannello admin. Scrivi il report in qa-reviews/admin/{data}.md"
   - `ainstein-qa:marco-seo-audit` — prompt: same pattern for seo-audit
   - `ainstein-qa:giulia-ads-analyzer` — prompt: same pattern for ads-analyzer
   - `ainstein-qa:alessia-seo-tracking` — prompt: same pattern for seo-tracking
   - `ainstein-qa:lorenzo-keyword-research` — prompt: same pattern for keyword-research
   - `ainstein-qa:sara-content-creator` — prompt: same pattern for content-creator
   - `ainstein-qa:davide-ai-content` — prompt: same pattern for ai-content

4. **Step 9 — Consolidate**: Read all `qa-reviews/*/YYYY-MM-DD.md` files. For each:
   - If file missing: mark as "NON COMPLETATO"
   - If file exists but no `## Scoring` section: mark as "PARZIALE"
   - Otherwise: extract Score Finale and issue counts

5. Write `qa-reviews/_index.md` with:
   - Header with date and completion count
   - Table ranked by score (worst to best)
   - "Agenti Non Completati" section if any
   - "Priorita Esecuzione Suggerita" — ordered by: critical count desc, then score asc
   - "Issue Critiche Cross-Area" — all CRITICO issues from all reports

- [ ] **Step 2: Commit**

```bash
git add .claude/plugins/ainstein-qa/agents/qa-orchestrator.md
git commit -m "feat(ainstein-qa): add qa-orchestrator agent"
```

---

## Task 12: Commands

**Files:**
- Create: `.claude/plugins/ainstein-qa/commands/qa-review.md`
- Create: `.claude/plugins/ainstein-qa/commands/qa-review-all.md`
- Create: `.claude/plugins/ainstein-qa/commands/qa-pattern-sync.md`

- [ ] **Step 1: Create qa-review.md**

```yaml
---
description: "Lancia un agente QA su un'area specifica: seo-audit, ads-analyzer, seo-tracking, keyword-research, content-creator, ai-content, admin, platform"
allowed-tools: Read, Glob, Grep, Bash, Write, Agent
---
```

Body:

```
Lancia un singolo agente QA sull'area specificata.

## Usage

`/qa-review {area}`

Aree valide: `seo-audit`, `ads-analyzer`, `seo-tracking`, `keyword-research`, `content-creator`, `ai-content`, `admin`, `platform`

## Mapping area → agente

| Area | Agente |
|------|--------|
| seo-audit | ainstein-qa:marco-seo-audit |
| ads-analyzer | ainstein-qa:giulia-ads-analyzer |
| seo-tracking | ainstein-qa:alessia-seo-tracking |
| keyword-research | ainstein-qa:lorenzo-keyword-research |
| content-creator | ainstein-qa:sara-content-creator |
| ai-content | ainstein-qa:davide-ai-content |
| admin | ainstein-qa:francesca-admin |
| platform | ainstein-qa:luca-platform-ux |

## Esecuzione

1. Verifica che la skill `platform-standards` esista: Read `.claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md`. Se contiene `{Da popolare}` nei placeholder, avvisa: "Attenzione: pattern non sincronizzati. Esegui /qa-pattern-sync prima per risultati piu accurati."
2. Risolvi l'area al nome agente dalla tabella sopra
3. Dispatcha l'agente con Agent tool (subagent_type corrispondente) con prompt: "Esegui la QA review completa di {area}. Scrivi il report in qa-reviews/{area}/{data-oggi}.md"
4. L'agente e completamente autonomo — non chiedere conferma all'utente
```

- [ ] **Step 2: Create qa-review-all.md**

```yaml
---
description: "Lancia tutti gli agenti QA in sequenza e produce indice globale. Zero interazione richiesta."
allowed-tools: Read, Glob, Grep, Bash, Write, Agent
---
```

Body:

```
Lancia l'orchestratore QA che esegue tutte le review.

## Usage

`/qa-review-all`

## Esecuzione

Dispatcha l'agente `ainstein-qa:qa-orchestrator` con Agent tool con prompt:
"Esegui il QA completo della piattaforma Ainstein. Pattern sync + 8 review sequenziali + consolidamento _index.md. Data di oggi: {data}."

L'orchestratore e completamente autonomo — zero interazione.
```

- [ ] **Step 3: Create qa-pattern-sync.md**

```yaml
---
description: "Sincronizza la skill platform-standards scansionando il codebase corrente"
allowed-tools: Read, Glob, Grep, Write, Agent
---
```

Body:

```
Aggiorna la skill platform-standards con i pattern reali dal codebase.

## Usage

`/qa-pattern-sync`

## Esecuzione

Dispatcha l'agente `ainstein-qa:pattern-auditor` con Agent tool con prompt:
"Esegui il pattern sync completo. Scansiona il codebase Ainstein in C:\laragon\www\seo-toolkit e aggiorna la skill platform-standards in .claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md"
```

- [ ] **Step 4: Commit all commands**

```bash
git add .claude/plugins/ainstein-qa/commands/
git commit -m "feat(ainstein-qa): add /qa-review, /qa-review-all, /qa-pattern-sync commands"
```

---

## Task 13: Verification + Final Commit

- [ ] **Step 1: Verify complete plugin structure**

Run:
```bash
find .claude/plugins/ainstein-qa/ -type f | sort
```

Expected output (15 files):
```
.claude/plugins/ainstein-qa/.claude-plugin/plugin.json
.claude/plugins/ainstein-qa/agents/alessia-seo-tracking.md
.claude/plugins/ainstein-qa/agents/davide-ai-content.md
.claude/plugins/ainstein-qa/agents/francesca-admin.md
.claude/plugins/ainstein-qa/agents/giulia-ads-analyzer.md
.claude/plugins/ainstein-qa/agents/lorenzo-keyword-research.md
.claude/plugins/ainstein-qa/agents/luca-platform-ux.md
.claude/plugins/ainstein-qa/agents/marco-seo-audit.md
.claude/plugins/ainstein-qa/agents/pattern-auditor.md
.claude/plugins/ainstein-qa/agents/qa-orchestrator.md
.claude/plugins/ainstein-qa/commands/qa-pattern-sync.md
.claude/plugins/ainstein-qa/commands/qa-review-all.md
.claude/plugins/ainstein-qa/commands/qa-review.md
.claude/plugins/ainstein-qa/hooks/hooks.json
.claude/plugins/ainstein-qa/skills/platform-standards/SKILL.md
```

- [ ] **Step 2: Verify output directories**

```bash
ls qa-reviews/.gitkeep qa-reviews/screenshots/.gitkeep
```

- [ ] **Step 3: Verify .gitignore entry**

```bash
grep "qa-reviews/screenshots" .gitignore
```

Expected: `qa-reviews/screenshots/`

- [ ] **Step 4: Verify all agents have correct frontmatter**

```bash
for f in .claude/plugins/ainstein-qa/agents/*.md; do echo "=== $f ==="; head -3 "$f"; echo; done
```

Verify each shows `---`, `name:`, and has the right name.

- [ ] **Step 5: Quick test — dry run command loading**

Restart Claude Code session (or `/clear`) and verify:
- `/qa-review` appears in slash commands
- `/qa-review-all` appears in slash commands
- `/qa-pattern-sync` appears in slash commands
- The ainstein-qa agents appear in the Agent tool list

If commands don't appear, check plugin.json path and file structure.

- [ ] **Step 6: Smoke test — run /qa-pattern-sync**

Run `/qa-pattern-sync` to verify the pattern-auditor agent works. This is il test piu sicuro perche non usa Playwright (solo Read/Grep sul codebase). Verifica che:
- L'agente viene dispatchiato correttamente
- La skill `platform-standards/SKILL.md` viene aggiornata (i placeholder `{Da popolare}` vengono sostituiti con contenuto reale)
- Nessun errore nel processo

Se funziona, il plugin e pronto per il primo `/qa-review {area}`.
