# PROMPT CLAUDE CODE: Piano d'Azione AI - SEO Audit

**Data:** 2026-01-19  
**Modulo:** `seo-audit`  
**Feature:** Piano d'Azione AI (AI Fix Generator evoluto)  
**Priorità:** 🔴 Alta (MVP Differenziante)

---

## 1. VISION

**Non siamo un tool. Siamo un consulente SEO AI.**

| Competitor | Ainstein |
|------------|----------|
| Lista 47 errori per tipo | Raggruppa per PAGINA |
| "Hai problemi" | "Risolvi 5 pagine, guadagni +33 punti" |
| Export CSV grezzo | To-Do List step-by-step |
| Dati da interpretare | Fix PRONTI da copiare |
| Zero priorità | Ordinato per IMPATTO reale |
| Nessun tracking | Checkbox "Fatto" + progresso % |

---

## 2. DATABASE

### 2.1 Tabella Piano d'Azione
```sql
CREATE TABLE IF NOT EXISTS sa_action_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    session_id INT NULL,
    
    -- Metriche piano
    total_pages INT DEFAULT 0,
    total_fixes INT DEFAULT 0,
    fixes_completed INT DEFAULT 0,
    health_current INT DEFAULT 0,
    health_expected INT DEFAULT 0,
    estimated_time_minutes INT DEFAULT 0,
    
    -- Stato
    status ENUM('generating', 'ready', 'in_progress', 'completed') DEFAULT 'generating',
    
    -- Meta
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_session (project_id, session_id)
);
```

### 2.2 Tabella Fix per Pagina
```sql
CREATE TABLE IF NOT EXISTS sa_page_fixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    project_id INT NOT NULL,
    page_id INT NOT NULL,
    issue_id INT NOT NULL,
    
    -- Fix generato
    fix_code TEXT NULL,
    fix_explanation TEXT NOT NULL,
    
    -- Metriche
    priority TINYINT DEFAULT 5,
    difficulty ENUM('facile', 'medio', 'difficile') DEFAULT 'medio',
    time_estimate_minutes INT DEFAULT 5,
    impact_points INT DEFAULT 1,
    step_order TINYINT DEFAULT 1,
    
    -- Stato
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    
    -- Meta
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plan_id) REFERENCES sa_action_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES sa_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (page_id) REFERENCES sa_pages(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES sa_issues(id) ON DELETE CASCADE,
    INDEX idx_plan_page (plan_id, page_id),
    INDEX idx_completed (plan_id, is_completed)
);
```

---

## 3. ARCHITETTURA
```
┌─────────────────┐     ┌────────────────────┐     ┌─────────────────┐
│   sa_issues     │────▶│  ActionPlanService │────▶│ sa_action_plans │
│   sa_pages      │     │      (NUOVO)       │     │  sa_page_fixes  │
└─────────────────┘     └────────────────────┘     └─────────────────┘
                               │
                               ▼
                        ┌──────────────────┐
                        │    AiService     │
                        │  (centralizzato) │
                        └──────────────────┘
```

---

## 4. SERVICE

**Path:** `modules/seo-audit/services/ActionPlanService.php`
```php
<?php
namespace Modules\SeoAudit\Services;

use Services\AiService;
use Core\Database;
use Core\Credits;

class ActionPlanService
{
    private AiService $ai;
    private string $moduleSlug = 'seo-audit';
    
    public function __construct()
    {
        $this->ai = new AiService($this->moduleSlug);
    }
    
    /**
     * Genera piano d'azione completo per un progetto
     * Raggruppa issue per pagina, ordina per impatto
     */
    public function generatePlan(int $projectId, int $sessionId, int $userId): array;
    
    /**
     * Recupera piano esistente con fix raggruppati per pagina
     */
    public function getPlan(int $projectId): ?array;
    
    /**
     * Recupera fix di una pagina specifica
     */
    public function getPageFixes(int $planId, int $pageId): array;
    
    /**
     * Toggle completamento singolo fix
     * Ricalcola progresso piano
     */
    public function toggleFixComplete(int $fixId): array;
    
    /**
     * Calcola impatto potenziale per pagina
     * Quanti punti health score guadagni risolvendo tutto
     */
    private function calculatePageImpact(array $issues): int;
    
    /**
     * Raggruppa issue per pagina, ordina per impatto
     */
    private function groupIssuesByPage(array $issues, array $pages): array;
    
    /**
     * Costruisce prompt AI per generare fix
     */
    private function buildPrompt(array $pageGroup, array $context): string;
    
    /**
     * Ricalcola stats piano dopo toggle
     */
    private function recalculatePlanStats(int $planId): void;
}
```

---

## 5. PROMPT AI
```
Sei un consulente SEO italiano esperto. Genera un PIANO D'AZIONE con fix PRONTI ALL'USO.

CONTESTO SITO:
- URL base: {base_url}
- Settore: {industry}
- Health Score attuale: {current_health}

PAGINA DA OTTIMIZZARE:
- URL: {page_url}
- Title attuale: {current_title}
- Meta Description attuale: {current_meta}
- H1 attuale: {current_h1}
- Contenuto (snippet): {content_snippet}

PROBLEMI RILEVATI SU QUESTA PAGINA:
{issues_json}

ISTRUZIONI:
1. Genera fix come STEP NUMERATI (Step 1, Step 2, Step 3...)
2. Ogni fix = codice/testo PRONTO DA COPIARE
3. Considera COERENZA tra fix (es. title e H1 devono essere allineati)
4. Spiegazione MAX 2 frasi in italiano
5. Priorità 1-10 basata su impatto SEO reale
6. Limiti: Title max 60 char, Description max 155 char
7. Stima tempo realistico per ogni fix

OUTPUT JSON:
{
  "page_url": "/esempio",
  "page_impact_points": 12,
  "total_time_minutes": 15,
  "fixes": [
    {
      "step": 1,
      "issue_id": 123,
      "issue_type": "title_too_long",
      "fix_code": "<title>Titolo Ottimizzato - Brand</title>",
      "fix_explanation": "Ridotto da 75 a 58 caratteri mantenendo keyword principale.",
      "priority": 9,
      "difficulty": "facile",
      "time_estimate_minutes": 2,
      "impact_points": 5
    },
    {
      "step": 2,
      "issue_id": 124,
      "issue_type": "missing_meta_description",
      "fix_code": "<meta name=\"description\" content=\"Descrizione ottimizzata con CTA e keyword. Scopri come...\">",
      "fix_explanation": "Aggiunta meta description con CTA e keyword target.",
      "priority": 8,
      "difficulty": "facile",
      "time_estimate_minutes": 3,
      "impact_points": 4
    }
  ]
}
```

---

## 6. CONTROLLER

**Path:** `modules/seo-audit/controllers/ActionPlanController.php`
```php
<?php
namespace Modules\SeoAudit\Controllers;

class ActionPlanController
{
    /**
     * Vista principale Piano d'Azione
     * Se piano esiste: mostra con progresso
     * Se non esiste: CTA per generare
     */
    public function index($projectId);
    
    /**
     * Genera piano (POST AJAX)
     * Verifica crediti, chiama service, ritorna JSON
     */
    public function generate($projectId);
    
    /**
     * Toggle fix completato (POST AJAX)
     * Ritorna nuovo stato + progresso aggiornato
     */
    public function toggleFix($projectId, $fixId);
    
    /**
     * Export To-Do List (GET)
     * Markdown o HTML per dev/cliente
     */
    public function export($projectId);
}
```

---

## 7. ROUTES

Aggiungi in `routes.php` dopo sezione AI Analysis:
```php
// Piano d'Azione AI
Router::get('/seo-audit/project/{id}/action-plan', 'ActionPlanController@index');
Router::post('/seo-audit/project/{id}/action-plan/generate', 'ActionPlanController@generate');
Router::post('/seo-audit/project/{id}/fix/{fixId}/toggle', 'ActionPlanController@toggleFix');
Router::get('/seo-audit/project/{id}/action-plan/export', 'ActionPlanController@export');
```

---

## 8. VIEW

**Path:** `modules/seo-audit/views/audit/action-plan.php`

### 8.1 Header (sempre visibile)
```
┌─────────────────────────────────────────────────────────────────────────┐
│  📋 Piano d'Azione AI                              [Esporta To-Do List] │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │    45       │  │    78       │  │   +33       │  │   2h 30m    │    │
│  │   Score     │  │   Score     │  │   Punti     │  │   Tempo     │    │
│  │  Attuale    │  │  Atteso     │  │   Guadagno  │  │   Stimato   │    │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘    │
│                                                                         │
│  Progresso: ████████░░░░░░░░░░░░░░░░░░░░░░░░░░  25% (12/47 fix)        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Stato: Piano Non Esiste
```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│                    📋 Nessun Piano Generato                             │
│                                                                         │
│     Analizzeremo 47 problemi su 12 pagine e genereremo                 │
│     fix specifici pronti da copiare, ordinati per impatto.             │
│                                                                         │
│     Costo stimato: ~15 crediti                                          │
│                                                                         │
│                  [ 🚀 Genera Piano d'Azione ]                           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.3 Stato: Piano Esiste (Lista Pagine)
```
┌─────────────────────────────────────────────────────────────────────────┐
│  Pagine ordinate per impatto (risolvile in questo ordine)              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │ 📄 /prodotti/categoria-principale                         [▼]    │ │
│  │ ─────────────────────────────────────────────────────────────────│ │
│  │ 🔴 4 problemi  │  +12 punti se risolvi  │  ~15 min  │  1/4 ✓    │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │ 📄 /chi-siamo                                             [▼]    │ │
│  │ ─────────────────────────────────────────────────────────────────│ │
│  │ 🔴 3 problemi  │  +8 punti se risolvi   │  ~10 min  │  0/3 ✓    │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────────┐ │
│  │ 📄 /blog/articolo-esempio                                 [▼]    │ │
│  │ ─────────────────────────────────────────────────────────────────│ │
│  │ 🟡 2 problemi  │  +5 punti se risolvi   │  ~8 min   │  2/2 ✓    │ │
│  └───────────────────────────────────────────────────────────────────┘ │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 8.4 Card Pagina Espansa (Alpine.js x-show)
```
┌───────────────────────────────────────────────────────────────────────┐
│ 📄 /prodotti/categoria-principale                             [▲]    │
│ ─────────────────────────────────────────────────────────────────────│
│ 🔴 4 problemi  │  +12 punti se risolvi  │  ~15 min  │  1/4 ✓        │
├───────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ☑️ STEP 1 - Title troppo lungo                                       │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │ <title>Prodotti Categoria - Qualità Italiana | Brand</title>   │ │
│  │                                                        [Copia] │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│  Ridotto da 78 a 55 caratteri. Keyword principale mantenuta.         │
│  ⏱️ 2 min  │  💪 Facile  │  📈 +4 punti                              │
│                                                                       │
│  ☐ STEP 2 - Meta description mancante                                │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │ <meta name="description" content="Scopri la nostra selezione   │ │
│  │ di prodotti di categoria. Qualità italiana, spedizione         │ │
│  │ gratuita. Oltre 200 articoli disponibili.">            [Copia] │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│  Aggiunta meta con CTA e keyword. 152 caratteri ottimali.            │
│  ⏱️ 3 min  │  💪 Facile  │  📈 +3 punti                              │
│                                                                       │
│  ☐ STEP 3 - H1 mancante                                              │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │ <h1>Prodotti Categoria: La Migliore Selezione Italiana</h1>    │ │
│  │                                                        [Copia] │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│  H1 coerente con title. Include keyword e value proposition.         │
│  ⏱️ 2 min  │  💪 Facile  │  📈 +3 punti                              │
│                                                                       │
│  ☐ STEP 4 - 3 immagini senza alt                                     │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │ alt="Prodotto esempio categoria - vista frontale"              │ │
│  │ alt="Dettaglio materiale prodotto categoria"                   │ │
│  │ alt="Prodotto categoria in uso - applicazione pratica" [Copia] │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│  Alt text descrittivi con keyword contestuali.                       │
│  ⏱️ 5 min  │  💪 Facile  │  📈 +2 punti                              │
│                                                                       │
└───────────────────────────────────────────────────────────────────────┘
```

---

## 9. INTERAZIONI JS
```javascript
// Toggle checkbox fix (AJAX, no reload)
async function toggleFix(fixId) {
    const response = await fetch(`/seo-audit/project/${projectId}/fix/${fixId}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken }
    });
    const data = await response.json();
    
    // Aggiorna checkbox
    // Aggiorna contatore pagina (1/4 → 2/4)
    // Aggiorna progress bar globale
    // Aggiorna score atteso se cambiato
}

// Copia codice fix
function copyFix(code) {
    navigator.clipboard.writeText(code);
    showToast('Copiato negli appunti!');
}

// Espandi/chiudi card pagina (Alpine.js)
x-data="{ open: false }"
@click="open = !open"
x-show="open"
x-transition
```

---

## 10. CREDITI

| Azione | Costo | Note |
|--------|-------|------|
| `action_plan_generate` | 15 | Piano completo (tutte le pagine) |
| `action_plan_regenerate` | 10 | Rigenera piano esistente |

Aggiungere in `module.json`:
```json
"credits": {
  "action_plan_generate": {
    "cost": 15,
    "description": "Generazione Piano d'Azione AI completo"
  }
}
```

---

## 11. SIDEBAR

In `nav-items.php`, aggiungi sotto "Issues":
```php
<?= navSubLink(
    "/seo-audit/project/{$projectId}/action-plan",
    'Piano d\'Azione',
    '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
    $currentPath
) ?>
```

---

## 12. EXPORT TO-DO

**Output Markdown:**
```markdown
# Piano d'Azione SEO - example.com
Generato: 19 Gennaio 2026
Health Score: 45 → 78 (+33 punti)
Tempo stimato: 2h 30m

---

## Pagina 1: /prodotti/categoria-principale
Impatto: +12 punti | Tempo: ~15 min | Priorità: ALTA

### Step 1: Title troppo lungo
**Fix:**
<title>Prodotti Categoria - Qualità Italiana | Brand</title>

**Note:** Ridotto da 78 a 55 caratteri.

---

### Step 2: Meta description mancante
**Fix:**
<meta name="description" content="Scopri la nostra selezione...">

**Note:** 152 caratteri con CTA.

---

## Pagina 2: /chi-siamo
...
```

---

## 13. ORDINE IMPLEMENTAZIONE

1. [ ] Migration SQL (2 tabelle) → esegui subito
2. [ ] ActionPlanService.php → service completo
3. [ ] ActionPlanController.php → controller
4. [ ] Routes in routes.php
5. [ ] View action-plan.php (con Alpine.js)
6. [ ] JavaScript (toggle, copia, expand)
7. [ ] Link in sidebar
8. [ ] Export To-Do
9. [ ] Crediti in module.json
10. [ ] Test completo

---

## 14. STIMA EFFORT

| Componente | Tempo |
|------------|-------|
| Migration + esecuzione | 15 min |
| ActionPlanService.php | 60 min |
| ActionPlanController.php | 30 min |
| Routes | 5 min |
| View action-plan.php | 60 min |
| JavaScript interazioni | 30 min |
| Sidebar + module.json | 10 min |
| Export To-Do | 20 min |
| Test + debug | 30 min |
| **TOTALE** | **~4.5 ore** |

---

## 15. CHECKLIST

### Pre-sviluppo
- [ ] Letto GOLDEN-RULES.md
- [ ] Letto AI_SERVICE_STANDARDS.md
- [ ] Letto seo-audit.md
- [ ] Backup database

### Sviluppo
- [ ] Migration SQL creata ed eseguita
- [ ] ActionPlanService.php implementato
- [ ] ActionPlanController.php implementato
- [ ] Routes aggiunte
- [ ] View action-plan.php creata
- [ ] JavaScript funzionante
- [ ] Sidebar aggiornata
- [ ] Export implementato
- [ ] module.json aggiornato

### Test
- [ ] Pagina /action-plan carica
- [ ] CTA "Genera Piano" funziona
- [ ] Crediti verificati e scalati
- [ ] Pagine ordinate per impatto
- [ ] Card si espande/chiude
- [ ] Checkbox toggle via AJAX
- [ ] Progress bar si aggiorna
- [ ] Copia codice funziona
- [ ] Export genera file valido

### Post-sviluppo
- [ ] Aggiornare AINSTEIN-STATUS.md
- [ ] Aggiornare ROADMAP.md (task completato)
- [ ] Commit con messaggio descrittivo

---

## 16. EDGE CASES

| Caso | Gestione |
|------|----------|
| Nessuna issue | Messaggio "Nessun problema. Health Score: 100!" |
| Piano già esiste | Mostra esistente + opzione "Rigenera" |
| AI timeout | Retry con batch più piccolo (5 pagine alla volta) |
| Crediti insufficienti | Blocca + messaggio + link acquista |
| Sessione crawl vecchia | Avvisa "Piano basato su crawl del X. Ricrawla per aggiornare." |

---

## 17. VINCOLI GOLDEN RULES

- [ ] AiService('seo-audit') - mai curl diretto
- [ ] Database::reconnect() dopo chiamate AI lunghe
- [ ] UI in italiano
- [ ] Heroicons SVG inline (no Lucide)
- [ ] CSRF su tutti i POST
- [ ] Prepared statements SQL
- [ ] Credits::getCost() per costi dinamici
- [ ] Credits::consume() dopo successo

---

Procedi FASE per FASE. Fermati dopo ogni fase per conferma.