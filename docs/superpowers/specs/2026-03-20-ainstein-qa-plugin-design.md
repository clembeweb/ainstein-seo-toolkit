# Design Spec: Plugin ainstein-qa — QA Tester Agents

> Data: 2026-03-20 | Stato: Approvato | Rev: 2 (post code review)

## Obiettivo

Creare un plugin Claude Code separato (`ainstein-qa`) con agenti autonomi che simulano professionisti digitali reali. Ogni agente testa un'area della piattaforma Ainstein dal punto di vista del suo utente tipo, navigando via Playwright su produzione, producendo piani di fix/redesign eseguibili da Claude.

## Requisiti

- **Autonomia totale**: nessuna conferma utente durante l'esecuzione. Fire-and-forget.
- **Test su produzione**: `https://ainstein.it` (credenziali centralizzate nella skill, non duplicate negli agenti)
- **3 livelli di analisi**: review UX/UI visiva, test funzionali dei workflow, verifica conformita pattern piattaforma
- **Output operativo**: piani fix/redesign con issue categorizzate, file coinvolti, fix proposti, checkbox per tracking esecuzione
- **Scoring consistente**: rubrica condivisa tra tutti gli agenti

## Architettura Plugin

### Struttura Directory

```
.claude/plugins/ainstein-qa/
├── .claude-plugin/
│   └── plugin.json
├── agents/
│   ├── qa-orchestrator.md              # orchestra: pattern sync → batch 2 alla volta
│   ├── pattern-auditor.md              # scansiona codebase, aggiorna skill
│   │
│   │   ── Moduli (6 personas) ──
│   ├── marco-seo-audit.md              # SEO Specialist Tecnico
│   ├── giulia-ads-analyzer.md          # PPC Manager
│   ├── alessia-seo-tracking.md         # SEO Manager in-house
│   ├── lorenzo-keyword-research.md     # Content Strategist
│   ├── sara-content-creator.md         # Content Manager E-commerce
│   ├── davide-ai-content.md            # Digital Marketing Manager
│   │
│   │   ── Piattaforma (2 personas) ──
│   ├── francesca-admin.md              # Platform Admin / CTO Agency
│   └── luca-platform-ux.md             # Nuovo Utente / Platform UX
│
├── commands/
│   ├── qa-review.md                    # /qa-review {modulo|admin|platform}
│   ├── qa-review-all.md                # /qa-review-all
│   └── qa-pattern-sync.md              # /qa-pattern-sync
├── skills/
│   └── platform-standards/
│       └── SKILL.md                    # pattern verificati + rubrica scoring + credenziali
└── hooks/
    └── hooks.json                      # (vuoto)
```

### Output Directory

Posizione: `C:\laragon\www\seo-toolkit\qa-reviews\` (project root, non dentro il plugin).

```
qa-reviews/
├── _index.md                    # classifica globale + priorita esecuzione
├── screenshots/                 # .gitignore'd, solo locale
├── platform/
│   └── {data}.md
├── admin/
│   └── {data}.md
├── seo-audit/
│   └── {data}.md
├── ads-analyzer/
│   └── {data}.md
├── seo-tracking/
│   └── {data}.md
├── keyword-research/
│   └── {data}.md
├── content-creator/
│   └── {data}.md
└── ai-content/
    └── {data}.md
```

`qa-reviews/screenshots/` va aggiunto a `.gitignore`.

## Componenti

### 1. plugin.json

```json
{
  "name": "ainstein-qa",
  "version": "1.0.0",
  "description": "Agenti QA autonomi che simulano professionisti digitali per testare UX, funzionalita e conformita della piattaforma Ainstein",
  "author": { "name": "Ainstein Team" }
}
```

### 2. Agenti — Specifiche

#### Frontmatter comune a tutti gli agenti QA (8 tester)

```yaml
model: sonnet
color: "{colore specifico per agente}"
tools: ["Read", "Write", "Glob", "Grep", "Bash",
        "mcp__plugin_playwright_playwright__browser_navigate",
        "mcp__plugin_playwright_playwright__browser_click",
        "mcp__plugin_playwright_playwright__browser_fill_form",
        "mcp__plugin_playwright_playwright__browser_take_screenshot",
        "mcp__plugin_playwright_playwright__browser_snapshot",
        "mcp__plugin_playwright_playwright__browser_press_key",
        "mcp__plugin_playwright_playwright__browser_select_option",
        "mcp__plugin_playwright_playwright__browser_wait_for",
        "mcp__plugin_playwright_playwright__browser_evaluate",
        "mcp__plugin_playwright_playwright__browser_tabs",
        "mcp__plugin_playwright_playwright__browser_console_messages",
        "mcp__plugin_playwright_playwright__browser_network_requests"]
```

Colori per agente:

| Agente | Colore |
|--------|--------|
| marco-seo-audit | green |
| giulia-ads-analyzer | red |
| alessia-seo-tracking | blue |
| lorenzo-keyword-research | purple |
| sara-content-creator | cyan |
| davide-ai-content | yellow |
| francesca-admin | magenta |
| luca-platform-ux | white |

#### Pattern Auditor

```yaml
model: haiku
color: orange
tools: ["Read", "Write", "Glob", "Grep"]
```

Non usa Playwright. Scansiona solo codice.

#### Orchestratore

```yaml
model: sonnet
color: blue
tools: ["Read", "Write", "Glob", "Grep", "Agent"]
```

Non usa Playwright direttamente. Dispatcha gli altri agenti.

#### Campo `description` — Obbligatorio per ogni agente

Ogni agente deve avere un campo `description` con trigger conditions e almeno un `<example>` block per il routing. Formato:

```yaml
description: >
  Use this agent when {trigger condition}.

  <example>
  Context: {scenario}
  user: "{richiesta tipica}"
  assistant: "I'll use the {nome} agent to {azione}."
  </example>
```

---

#### 2a. qa-orchestrator

**Ruolo**: Lancia il pattern auditor, poi dispatcha gli 8 agenti QA in batch da 2, infine consolida `_index.md`.

**Meccanismo di dispatch**: L'orchestratore usa il tool `Agent` con parametro `run_in_background: true` per lanciare 2 agenti in parallelo. Attende la notifica di completamento di entrambi prima di lanciare il batch successivo. Ogni agente e invocato con `subagent_type` corrispondente al nome dell'agente nel plugin.

**Flusso**:
1. Lancia `pattern-auditor` in foreground (Agent tool, `run_in_background: false`), attende completamento
2. Batch 1: `luca-platform-ux` + `francesca-admin` — 2 Agent tool calls con `run_in_background: true` nello stesso messaggio
3. Attende notifica completamento di entrambi
4. Batch 2: `marco-seo-audit` + `giulia-ads-analyzer` — stesso pattern
5. Batch 3: `alessia-seo-tracking` + `lorenzo-keyword-research`
6. Batch 4: `sara-content-creator` + `davide-ai-content`
7. Legge tutti i file `qa-reviews/*/YYYY-MM-DD.md`, gestendo:
   - File mancanti: agente crashato → segna "NON COMPLETATO" nell'indice
   - File parziali (senza sezione `## Scoring`): agente interrotto → segna "PARZIALE"
   - File di date precedenti: ignora, usa solo la data corrente
8. Genera `qa-reviews/_index.md` con:
   - Classifica per score (dal peggiore al migliore)
   - Conteggio issue per severita per area
   - Priorita esecuzione suggerita
   - Sezione "Agenti falliti" se qualcuno non ha completato

**Requisito hard**: Zero interazione utente. L'orchestratore non chiede mai conferme, approvazioni, o input. Fire-and-forget dall'inizio alla fine.

#### 2b. pattern-auditor

**Ruolo**: Scansiona il codebase per estrarre i pattern reali e aggiornare la skill `platform-standards`.

**Flusso**:
1. Legge `CLAUDE.md` — estrae Golden Rules, standard tabelle CSS, pattern landing page, icone
2. Legge `shared/views/components/*.php` — cataloga componenti shared e loro API (parametri accettati)
3. Analizza `modules/ai-content/` (modulo reference) — estrae pattern reali: struttura controller, view, CSS, landing page 7 sezioni
4. Grep di conformita su tutti i moduli:
   - Classi CSS non standard (rounded-lg vs rounded-xl, px-6 vs px-4, ecc.)
   - Icone non-Heroicons (lucide, fontawesome, emoji)
   - Testi UI non in italiano
   - Componenti tabella non-shared (paginazione custom, empty state custom)
   - Mancato uso di View::partial per componenti standard
5. Riscrive `skills/platform-standards/SKILL.md` con:
   - Pattern CSS verificati (con esempi reali dal codebase)
   - Lista componenti shared con parametri
   - Pattern landing page (7 sezioni in ordine)
   - Pattern controller (return, $user, ecc.)
   - Drift trovati (moduli non conformi con file:riga)
   - Rubrica scoring aggiornata
   - Credenziali test centralizzate (URL, email, password)

#### 2c. Agenti QA Tester — Struttura Comune

Ogni agente segue lo stesso flusso in 5 step:

**Step 1 — Login**
- Legge credenziali dalla skill `platform-standards` (non hardcoded nell'agente)
- Naviga a `https://ainstein.it/login`
- Compila email/password
- Verifica login riuscito (presenza sidebar)
- **Se login fallisce**: scrive report con errore "Login fallito: {motivo}", score 0/10, zero issue. Non prosegue.

**Step 2 — Review UX/UI**
- Naviga ogni pagina principale dell'area assegnata
- Per ogni pagina: screenshot + snapshot DOM
- Analizza anche `browser_network_requests` per richieste fallite, endpoint lenti, errori console
- Analisi critica dal punto di vista della persona:
  - Layout e gerarchia visiva
  - Leggibilita e chiarezza informazioni
  - Coerenza con il resto della piattaforma
  - Primo impatto: l'utente capisce cosa fare?
  - Valore aggiunto: questa pagina serve davvero?
- **Se una pagina restituisce errore (500, 404, timeout)**: logga come issue CRITICO tipo "Funzionale", continua con la pagina successiva. Non interrompere il flusso.

**Step 3 — Test Funzionali**
- Esegue i workflow critici del modulo (specifici per agente)
- Verifica che i flussi completino senza errori
- Controlla messaggi di errore, loading states, feedback utente
- Controlla `browser_console_messages` per errori JS
- **Politica dati produzione** (vedi sezione dedicata sotto)
- **Se un workflow fallisce a meta**: logga come issue CRITICO con screenshot dello stato al momento del fallimento, continua con il workflow successivo.

**Step 4 — Verifica Pattern**
- Legge la skill `platform-standards` per i pattern correnti
- Per ogni pagina visitata verifica:
  - Classi CSS tabelle (rounded-xl, px-4 py-3, dark:bg-slate-700/50)
  - Componenti shared usati (paginazione, empty state, bulk bar)
  - Icone Heroicons SVG (no lucide, no fontawesome)
  - Lingua UI italiano
  - Landing page pattern (7 sezioni, ordine, colori modulo)
  - Controller pattern (return, $user)
- Legge i file sorgente PHP/HTML per verificare (non solo visual)

**Step 5 — Genera Piano Fix**
- Crea directory `qa-reviews/{area}/` se non esiste
- Scrive `qa-reviews/{area}/YYYY-MM-DD.md` con formato standard (sotto)
- Salva screenshot in `qa-reviews/screenshots/`

#### Error Handling Globale

Se un agente incontra un errore imprevisto a qualsiasi step:
1. Logga l'errore con contesto (step, pagina, azione tentata)
2. Continua con lo step/pagina/workflow successivo
3. Nel report finale, marca le sezioni non testate come `[NON TESTATO] — {motivo}`
4. Il report viene scritto comunque (anche se parziale) — un report parziale e meglio di nessun report

#### Politica Dati Produzione

| Azione | Permessa | Note |
|--------|----------|------|
| Navigare pagine esistenti | Si | Azione primaria |
| Aprire progetti esistenti | Si | Usare dati gia presenti |
| Cliccare bottoni di navigazione | Si | Tab, accordion, filtri |
| Compilare form senza submit | Si | Per testare validazione |
| Creare nuovi progetti | NO | Inquina dati prod |
| Lanciare crawl/sync/analisi | NO | Consuma crediti/risorse |
| Modificare dati esistenti | NO | Rischio corruzione |
| Cancellare dati | NO | Ovvio |
| Testare export (CSV/PDF) | Si | Read-only, non modifica nulla |

Se un workflow critico richiede creazione dati per essere testato, l'agente deve:
1. Documentare che il test e stato **saltato** per policy dati prod
2. Valutare il workflow dalla UX visibile (form, layout, feedback) senza eseguirlo
3. Segnalare come issue se il workflow non e testabile senza creare dati

#### 2d. Personas e Aree Specifiche

**Marco — SEO Audit** (`marco-seo-audit.md`)
- Persona: SEO Specialist Tecnico, 5 anni exp, gestisce 15-20 siti
- Pain point: "Gli audit mi danno 200 issue ma non mi dicono da dove partire"
- Pagine: dashboard audit, import URLs, crawl progress, pages list, page detail, issues list, issue category, crawl budget, links/orphans, AI report, action plan, export, history
- Workflow critici: navigazione completa progetto esistente; review issues e categorizzazione; action plan; export CSV

**Giulia — Ads Analyzer** (`giulia-ads-analyzer.md`)
- Persona: PPC Manager, gestisce 500k+/anno budget, vive in Google Ads Editor
- Pain point: "Voglio sapere QUALI keyword, PERCHE, e cosa scrivere al posto"
- Pagine: dashboard campagne, lista campagne, sync detail, evaluation detail, search term analysis, keyword negative results, campaign creator wizard, export
- Workflow critici: navigazione campagne e sync esistenti; review evaluation e suggerimenti; review keyword negative; navigazione campaign creator

**Alessia — SEO Tracking** (`alessia-seo-tracking.md`)
- Persona: SEO Manager in-house, e-commerce 5000 prodotti, reporta al CMO
- Pain point: "Passo 2 giorni al mese a fare report"
- Pagine: dashboard, keywords list, keyword detail, trend, groups, quick wins, page analyzer, reports, GSC data, export
- Workflow critici: navigazione keywords e trend; review report esistenti; navigazione GSC data; export

**Lorenzo — Keyword Research** (`lorenzo-keyword-research.md`)
- Persona: Content Strategist freelance, cerca alternative economiche a Semrush
- Pain point: "Mi servono cluster con intent chiari, non 5000 keyword in un CSV"
- Pagine: dashboard 4 modalita, research wizard (collection → analysis → results), architecture wizard, editorial wizard, quick check
- Workflow critici: navigazione risultati ricerche esistenti; review clustering; quick check (free, no crediti); navigazione piano editoriale

**Sara — Content Creator** (`sara-content-creator.md`)
- Persona: Content Manager e-commerce Shopify, 800 SKU, 20+ contenuti/mese
- Pain point: "I contenuti AI sono tutti uguali e generici"
- Pagine: project dashboard, import (CSV/sitemap/CMS/manual), results, URL detail editor, image library, image detail, connectors, push/publish progress, export
- Workflow critici: navigazione progetto e risultati esistenti; review editor contenuti; navigazione connettori; export

**Davide — AI Content Generator** (`davide-ai-content.md`)
- Persona: Digital Marketing Manager agenzia, 25 clienti, scala produzione articoli
- Pain point: "Ho bisogno di 50 articoli/mese per 10 clienti"
- Pagine: dashboard 3 modalita, projects list, manual keywords → wizard, auto queue, meta-tags, internal links pool, WordPress integration, articles list/editor
- Workflow critici: navigazione progetti e articoli esistenti; review wizard UX; review auto-mode queue; review editor articolo

**Francesca — Admin** (`francesca-admin.md`)
- Persona: CTO web agency, gestisce il team su Ainstein
- Pain point: "Se devo andare in SSH per capire se un job e bloccato, il pannello admin non sta facendo il suo lavoro"
- Pagine: admin dashboard, utenti (lista, dettaglio, piani), gestione piani/pricing, crediti, moduli (attiva/disattiva, settings per modulo), AI logs, API logs, jobs monitor, email templates (lista, editor, preview, test), settings globali (branding, SMTP), finance dashboard
- Workflow critici: navigazione completa admin; review logs (AI + API); review jobs monitor; navigazione email templates; review finance dashboard

**Luca — Platform UX** (`luca-platform-ux.md`)
- Persona: Freelance SEO al primo accesso, non conosce la piattaforma
- Pain point: "Se non capisco cosa fare nei primi 2 minuti, chiudo e vado su Semrush"
- Pagine: homepage/landing, login/register, onboarding primo accesso, sidebar navigazione (responsive, accordion, stati attivi), hub progetti (lista, crea, dashboard progetto), attivazione moduli da progetto, condivisione progetti (inviti, ruoli, accettazione), notifiche (bell, lista, preferenze), profilo utente + preferenze email, pagina crediti/piani, documentazione utente (/docs), connettori CMS
- Workflow critici: navigazione completa sidebar; hub progetti; navigazione notifiche; review documentazione; navigazione profilo e preferenze

### 3. Comandi

#### /qa-review {area}

```yaml
---
description: "Lancia un agente QA su un'area specifica della piattaforma Ainstein"
allowed-tools: Read, Glob, Grep, Bash, Write, Agent
---
```

Valori accettati per `{area}`: `seo-audit`, `ads-analyzer`, `seo-tracking`, `keyword-research`, `content-creator`, `ai-content`, `admin`, `platform`

Il comando:
1. Verifica che la skill `platform-standards` esista e non sia vuota. Se mancante o vuota, avvisa nel report: "Attenzione: pattern non sincronizzati. Eseguire /qa-pattern-sync prima per risultati migliori."
2. Risolve l'area al nome agente corrispondente
3. Dispatcha l'agente con Agent tool
4. L'agente esegue autonomamente e produce il fix-plan

Mapping area → agente:

| Area | Agente |
|------|--------|
| seo-audit | marco-seo-audit |
| ads-analyzer | giulia-ads-analyzer |
| seo-tracking | alessia-seo-tracking |
| keyword-research | lorenzo-keyword-research |
| content-creator | sara-content-creator |
| ai-content | davide-ai-content |
| admin | francesca-admin |
| platform | luca-platform-ux |

#### /qa-review-all

```yaml
---
description: "Lancia tutti gli agenti QA in sequenza batch e produce indice globale"
allowed-tools: Read, Glob, Grep, Bash, Write, Agent
---
```

Lancia l'orchestratore che esegue tutto il flusso:
1. Pattern sync
2. 4 batch da 2 agenti
3. Consolidamento `_index.md`

Zero interazione richiesta.

#### /qa-pattern-sync

```yaml
---
description: "Sincronizza la skill platform-standards con lo stato attuale del codebase"
allowed-tools: Read, Glob, Grep, Write
---
```

Lancia solo il pattern auditor per aggiornare la skill `platform-standards` senza fare review.

### 4. Skill: platform-standards

Contenuto gestito dal pattern-auditor. Struttura:

```markdown
---
name: platform-standards
description: "Pattern UI/UX e coding standard verificati della piattaforma Ainstein.
  Usata dagli agenti QA per verificare conformita. Include credenziali test e rubrica scoring."
---

## Credenziali Test
- URL: https://ainstein.it
- Email: admin@seo-toolkit.local
- Password: admin123

## Standard CSS Tabelle
{classi verificate dal codebase con esempi}

## Componenti Shared
{lista componenti con parametri accettati}

## Pattern Landing Page
{7 sezioni in ordine, colori per modulo}

## Pattern Controller
{return, $user, CSRF, ecc.}

## Icone
{solo Heroicons SVG, esempi}

## Lingua UI
{italiano, eccezioni ammesse}

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
```

### 5. Formato Output Fix Plan

Ogni agente produce un file con questa struttura:

```markdown
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
{2-3 paragrafi dal punto di vista della persona: impressione generale,
cosa funziona bene, cosa non funziona, confronto con alternative di mercato,
comprerebbe il tool? perche si/no?}

## Issues

### [CRITICO] #1 — {Titolo}
- **Tipo**: UX | UI | Funzionale | Pattern | Performance
- **Pagina**: {URL relativo}
- **Screenshot**: screenshots/{nome}.png
- **Problema**: {descrizione dal punto di vista dell'utente}
- **File coinvolti**: `{path}:{righe}`
- **Fix proposto**: {cosa fare concretamente}
- **Pattern violato**: {Golden Rule / standard, oppure "Nessuno"}
- [ ] Da eseguire

### [ALTO] #2 — ...
...

## Sezioni Non Testate
{Lista di pagine/workflow non testati con motivo: errore, policy dati prod, timeout}

## Nota per l'Esecuzione
{Eventuali dipendenze tra fix, ordine suggerito, rischi}
```

### 6. Formato _index.md

```markdown
# QA Reviews — Indice Globale
Data: {YYYY-MM-DD} | Agenti: {N}/8 completati

## Classifica per Score (dal peggiore al migliore)

| # | Area | Score | Critici | Alti | Medi | Bassi | Totale |
|---|------|-------|---------|------|------|-------|--------|
| 1 | seo-tracking | 5.8/10 | 4 | 6 | 10 | 3 | 23 |
| 2 | ... | ... | ... | ... | ... | ... | ... |

## Agenti Non Completati
{Lista agenti falliti/parziali con motivo, se presenti}

## Priorita Esecuzione Suggerita
1. **seo-tracking** — 4 critici, score piu basso
2. **admin** — 3 critici, impatta tutti gli utenti
3. ...

## Issue Critiche Cross-Area
{Lista delle issue CRITICHE da tutti gli agenti, per avere una vista unificata}
```

## Vincoli Tecnici

- **Modello agenti QA**: Sonnet (necessario per analisi visiva screenshot)
- **Modello pattern auditor**: Haiku (solo analisi codice, no visual)
- **Modello orchestratore**: Sonnet (coordina, legge, scrive)
- **Concorrenza**: max 2 agenti Playwright in parallelo (limiti VPS CPX22: 2 vCPU, 4GB RAM)
- **Dati produzione**: politica esplicita read-only (vedi tabella in sezione 2c)
- **Screenshot**: salvati in `qa-reviews/screenshots/`, directory in `.gitignore`
- **Autonomia**: nessun agente chiede mai conferma all'utente. Tutti fire-and-forget.
- **Error recovery**: ogni agente continua al prossimo step/pagina su errore, report scritto comunque
