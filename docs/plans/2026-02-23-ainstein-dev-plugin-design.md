# Design: Plugin Claude Code `ainstein-dev`

> Data: 2026-02-23
> Stato: Approvato
> Approccio: Plugin Monolitico (Approccio A)

## Contesto

Il progetto Ainstein ha 8 file markdown in `.claude/agents/` e 5 in `.claude/prompts/` che NON sono agenti Claude Code eseguibili (mancano di frontmatter YAML). Questo design li sostituisce con un plugin Claude Code completo.

## Obiettivi

1. **Eliminare ripetitivita** - Scaffolding automatico con tutti i pattern Ainstein
2. **Recuperare contesto** - SessionStart hook carica automaticamente stato progetto
3. **Debug rapido** - Bug hunter con pattern reali del progetto
4. **Compliance automatica** - Pre-commit hook verifica Golden Rules
5. **Specializzazione per modulo** - Un agente per ogni modulo che conosce tabelle, file, pattern

## Struttura

```
.claude/plugins/ainstein-dev/
├── plugin.json
├── SKILL.md
│
├── agents/                          # 18 agenti
│   ├── orchestrator.md              # Router intelligente
│   ├── scaffolder.md                # Genera boilerplate
│   ├── bug-hunter.md                # Debug sistematico
│   ├── compliance-guard.md          # Verifica Golden Rules
│   ├── deploy-ops.md                # Deploy produzione
│   ├── database-ops.md              # Migration e schema
│   ├── codebase-navigator.md        # Esplora architettura
│   ├── docs-updater.md              # Aggiorna docs + data model
│   ├── session-briefer.md           # Briefing ad inizio sessione
│   ├── ai-content-specialist.md     # Modulo ai-content
│   ├── seo-audit-specialist.md      # Modulo seo-audit
│   ├── seo-tracking-specialist.md   # Modulo seo-tracking
│   ├── keyword-research-specialist.md # Modulo keyword-research
│   ├── internal-links-specialist.md # Modulo internal-links
│   ├── ads-analyzer-specialist.md   # Modulo ads-analyzer
│   ├── content-creator-specialist.md # Modulo content-creator
│   ├── test-runner.md               # Genera e esegue test
│   └── performance-analyzer.md      # Analisi performance
│
├── commands/                        # 7 slash commands
│   ├── scaffold.md                  # /scaffold tipo modulo nome
│   ├── audit.md                     # /audit modulo
│   ├── deploy.md                    # /deploy
│   ├── check.md                     # /check
│   ├── status.md                    # /status
│   ├── test.md                      # /test endpoint
│   └── docs-update.md              # /docs-update modulo
│
├── hooks/                           # 3 hooks
│   ├── pre-commit-check.md         # Compliance su git commit
│   ├── post-edit-lint.md           # php -l automatico
│   └── session-context.md          # Carica contesto
│
└── skills/                          # 2 skills
    ├── ainstein-patterns.md         # Pattern SSE, AJAX, CSRF
    └── module-knowledge.md          # Knowledge base moduli
```

## Dettaglio Agenti

### Trasversali (9)

| Agente | Trigger | Tools | Responsabilita |
|--------|---------|-------|----------------|
| orchestrator | Sempre attivo | Read, Glob, Grep | Analizza richiesta, suggerisce agente corretto |
| scaffolder | "crea", "genera", "nuovo" | Read, Write, Edit, Bash | Boilerplate controller/model/view/route/migration |
| bug-hunter | "bug", "errore", "non funziona" | Glob, Grep, Read, Edit, Bash | Debug 6-step con pattern reali |
| compliance-guard | "verifica", "check", "Golden Rules" | Glob, Grep, Read, Bash | Scansiona compliance 21 Golden Rules |
| deploy-ops | "deploy", "produzione" | Read, Bash | Push, SSH, migration, verifica, rollback |
| database-ops | "migration", "schema", "tabella" | Read, Write, Bash | SQL generation, diagnosi, ENUM check |
| codebase-navigator | "dove", "come funziona", "mostrami" | Glob, Grep, Read | Esplora e spiega architettura |
| docs-updater | "aggiorna docs", "documentazione" | Read, Write, Edit | Aggiorna /docs e data-model.html |
| session-briefer | SessionStart | Read, Bash, Glob | Branch, file modificati, stato moduli |

### Per-Modulo (7)

| Agente | Modulo | Prefisso DB | File chiave |
|--------|--------|-------------|-------------|
| ai-content-specialist | ai-content | aic_ | dispatcher.php, WizardController, ArticleGenerator |
| seo-audit-specialist | seo-audit | sa_ | IssueDetector, ActionPlanService, CrawlerService |
| seo-tracking-specialist | seo-tracking | st_ | RankCheckController, GscService, AiReportService, 3 dispatchers |
| keyword-research-specialist | keyword-research | kr_ | KeywordInsightService, EditorialController, CollectionController |
| internal-links-specialist | internal-links | il_ | LinkAnalyzer, OpportunityFinder |
| ads-analyzer-specialist | ads-analyzer | ga_ | CampaignController, auto-evaluate.php, MetricComparisonService |
| content-creator-specialist | content-creator | cc_ | PageGenerator, CmsConnector (WP/Shopify/Presta/Magento) |

### Qualita (2)

| Agente | Trigger | Cosa genera |
|--------|---------|-------------|
| test-runner | "testa", "verifica endpoint" | cURL commands, scenario browser, test CSRF/SSE/AJAX |
| performance-analyzer | "lento", "performance", "ottimizza" | Query analysis, N+1 detection, indici suggeriti |

## Dettaglio Comandi

| Comando | Sintassi | Output |
|---------|----------|--------|
| /scaffold | `/scaffold controller ai-content NomeController` | File PHP completo con namespace, use, pattern |
| /audit | `/audit seo-tracking` | Report compliance, gap analysis, priorita fix |
| /deploy | `/deploy` | Wizard: lint → status → push → SSH → verify |
| /check | `/check` | Golden Rules check su git diff files |
| /status | `/status` | Tabella: moduli, cron, API health, errori |
| /test | `/test /ai-content/projects/1/articles` | cURL test + verifica response |
| /docs-update | `/docs-update keyword-research` | Aggiorna docs utente + data model |

## Dettaglio Hooks

| Hook | Evento | Prompt |
|------|--------|--------|
| pre-commit-check | PreToolUse(Bash) quando `git commit` | "Verifica che i file staged rispettino le Golden Rules" |
| post-edit-lint | PostToolUse(Edit\|Write) su `.php` | "Esegui php -l sul file modificato" |
| session-context | SessionStart | "Carica branch, ultimi commit, file modificati" |

## Migrazione

I file attuali in `.claude/agents/` e `.claude/prompts/` verranno:
1. Letti per estrarre la knowledge
2. Incorporati nei nuovi agenti del plugin
3. Archiviati (non cancellati subito) in `.claude/agents-legacy/`

## Piano Implementazione

Vedi: `docs/plans/2026-02-23-ainstein-dev-plugin-plan.md` (da creare con writing-plans)
