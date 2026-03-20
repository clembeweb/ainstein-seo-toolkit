# Ainstein Dev Plugin - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Create a Claude Code plugin `ainstein-dev` with 18 agents, 7 slash commands, 3 hooks, and 2 skills to accelerate Ainstein SEO Toolkit development.

**Architecture:** Single monolithic plugin at `.claude/plugins/ainstein-dev/` with auto-discovered components. Agents use frontmatter YAML for proper Claude Code integration. Hooks use prompt-based approach for context-aware validation. Commands provide quick-access workflows.

**Tech Stack:** Claude Code plugin system (markdown + YAML frontmatter + JSON hooks), Bash scripts for hooks, PHP lint integration.

**Design doc:** `docs/plans/2026-02-23-ainstein-dev-plugin-design.md`

---

## Task 1: Create Plugin Skeleton

**Files:**
- Create: `.claude/plugins/ainstein-dev/.claude-plugin/plugin.json`
- Create: `.claude/plugins/ainstein-dev/SKILL.md`

**Step 1: Create directory structure**

Run:
```bash
mkdir -p .claude/plugins/ainstein-dev/.claude-plugin
mkdir -p .claude/plugins/ainstein-dev/agents
mkdir -p .claude/plugins/ainstein-dev/commands
mkdir -p .claude/plugins/ainstein-dev/hooks/scripts
mkdir -p .claude/plugins/ainstein-dev/skills/ainstein-patterns
mkdir -p .claude/plugins/ainstein-dev/skills/module-knowledge
```

**Step 2: Write plugin.json**

Create `.claude/plugins/ainstein-dev/.claude-plugin/plugin.json`:
```json
{
  "name": "ainstein-dev",
  "version": "1.0.0",
  "description": "Agenti, comandi e hook specializzati per lo sviluppo di Ainstein SEO Toolkit",
  "author": {
    "name": "Ainstein Team"
  }
}
```

**Step 3: Enable plugin in settings**

Add `"ainstein-dev@local": true` to `.claude/settings.json` `enabledPlugins`.

**Step 4: Verify**

Run: `ls -la .claude/plugins/ainstein-dev/.claude-plugin/`
Expected: plugin.json exists

---

## Task 2: Create Orchestrator Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/orchestrator.md`

**Context:** This agent routes requests to the correct specialist. It should trigger on ANY development request and suggest which agent to use.

**Step 1: Write orchestrator agent**

Create `.claude/plugins/ainstein-dev/agents/orchestrator.md` with proper frontmatter:

```markdown
---
name: orchestrator
description: Use this agent when the user asks a general question about Ainstein development, needs help choosing the right approach, or says "help", "come faccio", "quale agente", "da dove comincio". Examples:

<example>
Context: User needs to work on Ainstein but isn't sure where to start
user: "Devo modificare il modulo seo-tracking, da dove comincio?"
assistant: "Let me use the orchestrator to analyze your request and suggest the best approach."
<commentary>
General development question that needs routing to the right specialist agent.
</commentary>
</example>

<example>
Context: User has a vague request about Ainstein
user: "Devo aggiungere una nuova feature"
assistant: "Let me use the orchestrator to understand the scope and route to the right agent."
<commentary>
Vague request needs analysis to determine which specialist agent should handle it.
</commentary>
</example>

model: haiku
color: blue
tools: ["Read", "Glob", "Grep"]
---

You are the Ainstein Development Orchestrator. Your role is to analyze development requests and route them to the correct specialist agent.

**Available Agents:**

| Agent | Trigger Keywords | Purpose |
|-------|-----------------|---------|
| scaffolder | "crea controller/model/view", "nuovo", "genera" | Generate boilerplate code |
| bug-hunter | "bug", "errore", "non funziona", "500" | Systematic debugging |
| compliance-guard | "verifica", "check", "Golden Rules" | Check code compliance |
| deploy-ops | "deploy", "produzione", "rilascio" | Production deployment |
| database-ops | "migration", "schema", "tabella" | Database operations |
| codebase-navigator | "dove si trova", "come funziona" | Explore architecture |
| docs-updater | "aggiorna docs", "documentazione" | Update user docs |
| test-runner | "testa", "verifica endpoint" | Generate and run tests |
| performance-analyzer | "lento", "performance", "ottimizza" | Performance analysis |
| ai-content-specialist | "ai-content", "articoli", "brief" | AI Content module |
| seo-audit-specialist | "seo-audit", "audit seo" | SEO Audit module |
| seo-tracking-specialist | "seo-tracking", "rank", "GSC" | SEO Tracking module |
| keyword-research-specialist | "keyword-research", "keyword" | KR module |
| internal-links-specialist | "internal-links", "link interni" | IL module |
| ads-analyzer-specialist | "ads-analyzer", "Google Ads" | GA module |
| content-creator-specialist | "content-creator", "pagine HTML" | CC module |

**Process:**
1. Read the user's request carefully
2. Identify the primary intent (debug, build, deploy, explore, audit, module-specific)
3. Recommend the most appropriate agent(s)
4. If multiple agents apply, suggest the order of execution
5. Provide a brief explanation of why you chose that agent

**Output Format:**
- Recommended agent: `agent-name`
- Why: brief explanation
- Alternative agents (if applicable)
- Suggested first steps
```

**Step 2: Verify syntax**

Ensure YAML frontmatter is valid (--- delimiters, proper indentation).

---

## Task 3: Create Scaffolder Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/scaffolder.md`

**Context:** This is one of the most important agents. It generates boilerplate code following ALL Ainstein patterns (Golden Rules, CSRF, ob_start, return View::render, etc.).

**Step 1: Write scaffolder agent**

Key patterns to embed in the agent:
- Controller: `return View::render()` (Golden Rule #21), `Middleware::auth()`, proper namespace
- Model: prefix DB tables, prepared statements, allowlist for sort
- View: Tailwind + dark mode, Heroicons only, Italian UI, shared table components
- Route: `return $controller->method()` for GET, project-scoped paths
- Migration: prefix tables, ENUM types, indexes, timestamps
- CSRF: `_csrf_token` field name
- AJAX lungo: `ob_start()`, `ignore_user_abort(true)`, `set_time_limit(0)`
- SSE: `session_write_close()`, polling fallback, `Database::reconnect()`

The agent should ask what to scaffold (controller/model/view/route/migration/full-module) and for which module, then generate code with ALL patterns correct.

Tools: `["Read", "Write", "Edit", "Bash", "Glob", "Grep"]`
Model: `inherit`
Color: `green`

---

## Task 4: Create Bug Hunter Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/bug-hunter.md`

**Context:** Evolved from existing `.claude/agents/bug-hunter.md`. Adds proper frontmatter and the 10+ real bug patterns from production.

**Step 1: Read existing bug-hunter for knowledge extraction**

Read: `.claude/agents/bug-hunter.md`

**Step 2: Write new bug-hunter agent**

Include all existing patterns PLUS:
- `Database::reconnect()` mancante
- Query senza user_id/project_id (IDOR)
- ENUM mismatch codice vs DB
- Route senza project_id
- AJAX senza error handling
- CSRF `csrf_token` vs `_csrf_token`
- `ob_start()` mancante in AJAX lungo
- SSE `ignore_user_abort(true)` mancante
- `getModuleSetting()` non esiste
- `return View::render()` mancante (pagina vuota)
- `jsonResponse()` in operazione lunga

Tools: `["Read", "Edit", "Glob", "Grep", "Bash"]`
Model: `inherit`
Color: `red`

---

## Task 5: Create Compliance Guard Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/compliance-guard.md`

**Context:** Verifies all 21 Golden Rules. Evolved from `.claude/agents/compliance-checker.md`.

**Step 1: Read existing compliance-checker**

Read: `.claude/agents/compliance-checker.md`

**Step 2: Write compliance-guard agent**

Must check ALL 21 Golden Rules:
1. AiService centralizzato (no curl diretto)
2. Heroicons only (no Lucide, no FontAwesome)
3. UI in italiano
4. Prefisso DB per modulo
5. API Keys in DB (no .env, no hardcoded)
6. Routes pattern standard
7. Prepared statements
8. CSRF `_csrf_token`
9. ai-content come reference
10. `Database::reconnect()` dopo AI
11. OAuth GSC pattern
12. ScraperService per scraping
13. Background jobs per operazioni lunghe
14. ApiLoggerService per API
15. `ignore_user_abort(true)` in SSE/AJAX
16. `ModuleLoader::getSetting()` (non getModuleSetting)
17. `ob_start()` in AJAX lunghi
18. Docs aggiornate
19. Link project-scoped
20. Tabelle standard CSS
21. `return View::render()`

Tools: `["Read", "Glob", "Grep", "Bash"]`
Model: `inherit`
Color: `yellow`

---

## Task 6: Create Deploy Ops Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/deploy-ops.md`

**Step 1: Read existing deploy-master**

Read: `.claude/agents/deploy-master.md`

**Step 2: Write deploy-ops agent**

Include:
- Pre-deploy checklist (php -l, git status, no API keys, no secrets)
- SSH credentials and paths (from CLAUDE.md)
- Migration execution pattern
- Post-deploy verification
- Rollback procedures
- Cron job format for SiteGround

Tools: `["Read", "Bash", "Glob"]`
Model: `inherit`
Color: `cyan`

---

## Task 7: Create Database Ops Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/database-ops.md`

**Step 1: Read existing database-doctor**

Read: `.claude/agents/database-doctor.md`

**Step 2: Write database-ops agent**

Include:
- All module prefixes (aic_, sa_, st_, il_, ga_, cc_, kr_)
- Migration template with UP/DOWN
- ENUM validation pattern
- Index suggestions
- Orphaned record detection
- Production DB credentials
- `Database::reconnect()` patterns

Tools: `["Read", "Write", "Bash", "Glob", "Grep"]`
Model: `inherit`
Color: `magenta`

---

## Task 8: Create Codebase Navigator Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/codebase-navigator.md`

**Step 1: Write codebase-navigator agent**

Purpose: Explore and explain Ainstein architecture. Knows project structure, module patterns, service locations.

Include:
- Full project directory map
- Key file locations per module
- Pattern explanations (SSE, AJAX lungo, credits, AI integration)
- How to trace a request from route to view

Tools: `["Read", "Glob", "Grep"]`
Model: `haiku`
Color: `blue`

---

## Task 9: Create Docs Updater Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/docs-updater.md`

**Step 1: Write docs-updater agent**

Purpose: Automatically update user documentation and data model after development (Golden Rule #18).

Include:
- File locations: `shared/views/docs/{slug}.php`, `docs/data-model.html`
- Page structure template (Cos'e, Quick Start, Funzionalita, Costi, Suggerimenti)
- Data model format (Mermaid.js erDiagram, details collapsibili)
- Module color mapping (amber/emerald/blue/purple/cyan/rose)
- Route registration (`$validPages` in `public/index.php`)
- Sidebar links (`$navItems` in layout)

Tools: `["Read", "Write", "Edit", "Glob", "Grep"]`
Model: `inherit`
Color: `green`

---

## Task 10: Create Session Briefer Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/session-briefer.md`

**Step 1: Write session-briefer agent**

Purpose: Quick briefing on project state at session start.

Reports:
- Current git branch and last 3 commits
- Files modified (git status)
- Which module was last worked on
- Any stuck jobs or errors in logs
- Active cron status

Tools: `["Read", "Bash", "Glob", "Grep"]`
Model: `haiku`
Color: `cyan`

---

## Task 11: Create 7 Module Specialist Agents

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/ai-content-specialist.md`
- Create: `.claude/plugins/ainstein-dev/agents/seo-audit-specialist.md`
- Create: `.claude/plugins/ainstein-dev/agents/seo-tracking-specialist.md`
- Create: `.claude/plugins/ainstein-dev/agents/keyword-research-specialist.md`
- Create: `.claude/plugins/ainstein-dev/agents/internal-links-specialist.md`
- Create: `.claude/plugins/ainstein-dev/agents/ads-analyzer-specialist.md`
- Create: `.claude/plugins/ainstein-dev/agents/content-creator-specialist.md`

**Step 1: Read module structures**

For each module, read:
- `modules/{slug}/module.json` - settings, features
- `modules/{slug}/controllers/` - available controllers
- `modules/{slug}/models/` - data models
- `modules/{slug}/database/schema.sql` - tables
- `modules/{slug}/cron/` - automation (if exists)

**Step 2: Write each specialist agent**

Each specialist agent follows this template:

```markdown
---
name: {slug}-specialist
description: Use this agent when working on the {name} module ({slug}). Triggers on "{slug}", "{keywords}". Examples: ...
model: inherit
color: {color}
tools: ["Read", "Write", "Edit", "Glob", "Grep", "Bash"]
---

You are the {Name} Module Specialist for Ainstein SEO Toolkit.

**Module Overview:**
- Slug: {slug}
- DB Prefix: {prefix}_
- Status: {status}
- Key tables: {tables}

**File Map:**
- Controllers: modules/{slug}/controllers/
- Models: modules/{slug}/models/
- Views: modules/{slug}/views/
- Routes: public/index.php (section {slug})
- Cron: modules/{slug}/cron/ (if applicable)

**Key Patterns:**
{module-specific patterns}

**Automation:**
{cron jobs, dispatchers, SSE endpoints}

**Common Issues:**
{module-specific bugs and fixes}
```

Colors per module:
- ai-content: `yellow` (amber)
- seo-audit: `green` (emerald)
- seo-tracking: `blue`
- keyword-research: `magenta` (purple)
- internal-links: `cyan`
- ads-analyzer: `red` (rose)
- content-creator: `green`

---

## Task 12: Create Test Runner Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/test-runner.md`

**Step 1: Write test-runner agent**

Purpose: Generate and execute tests for Ainstein endpoints.

Include test patterns for:
- cURL commands for GET/POST endpoints
- CSRF token extraction and usage
- Cookie-based authentication
- SSE endpoint verification
- AJAX response validation (JSON structure)
- HTTP status code checks
- PHP syntax verification (`php -l`)
- Database state verification queries

Tools: `["Read", "Bash", "Glob", "Grep"]`
Model: `inherit`
Color: `green`

---

## Task 13: Create Performance Analyzer Agent

**Files:**
- Create: `.claude/plugins/ainstein-dev/agents/performance-analyzer.md`

**Step 1: Write performance-analyzer agent**

Purpose: Find and fix performance issues.

Analysis areas:
- SQL queries: N+1 detection, missing indexes, full table scans
- PHP: memory leaks, unnecessary loops, large arrays
- Frontend: oversized Tailwind, unnecessary Alpine.js reactivity
- API: excessive calls, missing cache
- SSE/AJAX: timeout issues, connection management

Tools: `["Read", "Glob", "Grep", "Bash"]`
Model: `inherit`
Color: `yellow`

---

## Task 14: Create Slash Commands (7)

**Files:**
- Create: `.claude/plugins/ainstein-dev/commands/scaffold.md`
- Create: `.claude/plugins/ainstein-dev/commands/audit.md`
- Create: `.claude/plugins/ainstein-dev/commands/deploy.md`
- Create: `.claude/plugins/ainstein-dev/commands/check.md`
- Create: `.claude/plugins/ainstein-dev/commands/status.md`
- Create: `.claude/plugins/ainstein-dev/commands/test.md`
- Create: `.claude/plugins/ainstein-dev/commands/docs-update.md`

**Step 1: Write /scaffold command**

```markdown
---
description: Generate Ainstein boilerplate (controller, model, view, route, migration)
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
---

Generate boilerplate code for Ainstein SEO Toolkit following ALL conventions.

## Usage
`/scaffold [type] [module] [name]`

Types: controller, model, view, route, migration, full

## Process
1. Ask what to generate if not specified
2. Read existing module code for patterns
3. Generate code following Golden Rules
4. Run php -l to verify syntax
5. Report what was created
```

**Step 2: Write /audit command**

Quick module audit - checks compliance, finds gaps.

**Step 3: Write /deploy command**

Interactive deploy wizard with pre-checks.

**Step 4: Write /check command**

Fast Golden Rules check on modified files (git diff).

**Step 5: Write /status command**

System overview: modules, cron, API health.

**Step 6: Write /test command**

Generate and run tests for endpoints.

**Step 7: Write /docs-update command**

Update user docs and data model for a module.

---

## Task 15: Create Hooks (3)

**Files:**
- Create: `.claude/plugins/ainstein-dev/hooks/hooks.json`
- Create: `.claude/plugins/ainstein-dev/hooks/scripts/php-lint.sh`

**Step 1: Write hooks.json**

```json
{
  "description": "Ainstein development automation hooks",
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Edit|Write",
        "hooks": [
          {
            "type": "command",
            "command": "bash ${CLAUDE_PLUGIN_ROOT}/hooks/scripts/php-lint.sh",
            "timeout": 10
          }
        ]
      }
    ],
    "PreToolUse": [
      {
        "matcher": "Bash",
        "hooks": [
          {
            "type": "prompt",
            "prompt": "Check if this Bash command contains 'git commit'. If it does, verify: 1) Are there any PHP files in the staged changes that might violate Golden Rules? Check for: Lucide icons, hardcoded API keys, missing CSRF _csrf_token, missing return View::render(). 2) If violations found, return 'deny' with explanation. 3) If clean, return 'approve'. Only check for git commit commands, approve everything else immediately.",
            "timeout": 30
          }
        ]
      }
    ],
    "SessionStart": [
      {
        "matcher": "*",
        "hooks": [
          {
            "type": "command",
            "command": "bash ${CLAUDE_PLUGIN_ROOT}/hooks/scripts/session-context.sh",
            "timeout": 15
          }
        ]
      }
    ]
  }
}
```

**Step 2: Write php-lint.sh**

```bash
#!/bin/bash
# Post-edit PHP lint check
input=$(cat)
file_path=$(echo "$input" | jq -r '.tool_input.file_path // empty')

if [[ -z "$file_path" ]] || [[ "$file_path" != *.php ]]; then
  exit 0
fi

if [[ -f "$file_path" ]]; then
  result=$(php -l "$file_path" 2>&1)
  if [[ $? -ne 0 ]]; then
    echo "$result" >&2
    exit 2
  fi
fi
exit 0
```

**Step 3: Write session-context.sh**

```bash
#!/bin/bash
# SessionStart context loader
cd "$CLAUDE_PROJECT_DIR" 2>/dev/null || exit 0

echo "=== Ainstein Dev Session Context ==="
echo "Branch: $(git branch --show-current 2>/dev/null)"
echo "Last 3 commits:"
git log --oneline -3 2>/dev/null
echo ""
echo "Modified files:"
git status --short 2>/dev/null | head -15
echo ""
echo "Module status:"
if command -v php &>/dev/null; then
  php -r "echo 'PHP: ' . PHP_VERSION . PHP_EOL;" 2>/dev/null
fi
```

**Step 4: Make scripts executable**

Run: `chmod +x .claude/plugins/ainstein-dev/hooks/scripts/*.sh`

---

## Task 16: Create Skills (2)

**Files:**
- Create: `.claude/plugins/ainstein-dev/skills/ainstein-patterns/SKILL.md`
- Create: `.claude/plugins/ainstein-dev/skills/module-knowledge/SKILL.md`

**Step 1: Write ainstein-patterns skill**

SKILL.md with all critical patterns:
- SSE pattern (full code)
- AJAX lungo pattern (full code)
- CSRF token usage
- AiService integration
- Credits consumption
- ScraperService usage
- Background job pattern
- Table CSS standards

**Step 2: Write module-knowledge skill**

SKILL.md with module knowledge base:
- All 7 modules: slug, prefix, tables, controllers, key files
- Cross-module relationships (KR export to AIC, SERP cache sharing)
- Cron job inventory
- API provider cascade

---

## Task 17: Archive Old Agents

**Files:**
- Move: `.claude/agents/*.md` to `.claude/agents-legacy/`
- Move: `.claude/prompts/*.md` to `.claude/prompts-legacy/`

**Step 1: Create legacy directories**

```bash
mkdir -p .claude/agents-legacy .claude/prompts-legacy
```

**Step 2: Move old files**

```bash
mv .claude/agents/*.md .claude/agents-legacy/
mv .claude/prompts/*.md .claude/prompts-legacy/
```

**Step 3: Update README**

Update `.claude/README.md` to reference new plugin structure.

---

## Task 18: Validate Plugin

**Step 1: Check plugin structure**

Verify all files exist:
```bash
find .claude/plugins/ainstein-dev -type f | sort
```

**Step 2: Validate JSON files**

```bash
cat .claude/plugins/ainstein-dev/.claude-plugin/plugin.json | python -m json.tool
cat .claude/plugins/ainstein-dev/hooks/hooks.json | python -m json.tool
```

**Step 3: Verify agent frontmatter**

Check each agent has `---` delimiters with name, description, model, color.

**Step 4: Verify commands frontmatter**

Check each command has `---` delimiters with description and allowed-tools.

**Step 5: Test**

Restart Claude Code and verify:
- Plugin loads without errors
- Agents appear in available agents list
- Commands appear in slash commands
- Hooks execute on edit/commit/session start

---

## Implementation Order

Tasks are grouped for efficient parallel execution:

**Phase 1 - Foundation (Tasks 1-2):** Plugin skeleton + orchestrator
**Phase 2 - Core Agents (Tasks 3-10):** All 9 transversal agents in parallel
**Phase 3 - Module Specialists (Task 11):** 7 module specialists (can parallelize)
**Phase 4 - Quality Agents (Tasks 12-13):** Test runner + performance analyzer
**Phase 5 - Commands (Task 14):** All 7 slash commands
**Phase 6 - Hooks & Skills (Tasks 15-16):** Hooks + skills
**Phase 7 - Cleanup (Tasks 17-18):** Archive old, validate new

**Estimated total: 18 tasks, ~30 files to create**
