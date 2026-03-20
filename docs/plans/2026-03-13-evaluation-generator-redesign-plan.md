# Evaluation Generator Redesign — Piano Implementazione

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fixare il flusso "Genera con AI" + "Applica su Google Ads" nella valutazione campagne, eliminando i mismatch area/azione, aggiungendo persistenza dei fix generati, e garantendo targeting corretto per ad group.

**Architecture:** Il redesign aggiunge `fix_type` al prompt AI (separato da `area`), salva i fix generati in DB (`ga_generated_fixes`), e rende "Genera con AI" per copy disponibile SOLO a livello ad group con targeting esplicito per Google ID. Le extensions restano a livello campagna. Le keyword negative supportano sia campagna che ad group level.

**Tech Stack:** PHP 8+, MySQL, Alpine.js, Google Ads API v18

---

## File Structure

| File | Azione | Responsabilità |
|------|--------|----------------|
| `modules/ads-analyzer/services/CampaignEvaluatorService.php` | Modify | Aggiungere `fix_type` nel prompt AI per ogni issue |
| `modules/ads-analyzer/services/EvaluationGeneratorService.php` | Modify | Usare `fix_type` per decidere cosa generare |
| `modules/ads-analyzer/controllers/CampaignController.php` | Modify | Persistenza fix, targeting per Google ID |
| `modules/ads-analyzer/views/campaigns/evaluation.php` | Modify | UI: genType da fix_type, persistenza, targeting ad group |
| `modules/ads-analyzer/models/GeneratedFix.php` | Create | Model per `ga_generated_fixes` |
| `database/migrations/2026_03_13_generated_fixes.sql` | Create | Migration tabella |

---

## Chunk 1: AI Prompt — Aggiungere fix_type

### Task 1: Aggiornare il prompt AI per includere fix_type

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignEvaluatorService.php:306-402`

Il problema centrale: l'AI classifica un issue con `area` (es. "keywords") ma la raccomandazione è un'azione diversa (es. "riscrivere annunci"). Il `genType` attuale si basa sull'area, non sull'azione.

**Soluzione:** Aggiungere campo `fix_type` opzionale a ogni issue. L'AI indica COSA fare, non solo DOVE è il problema.

- [ ] **Step 1: Aggiornare la struttura JSON nel prompt**

In `CampaignEvaluatorService.php`, nella sezione della struttura JSON (linee ~317-344), modificare la struttura degli issues per campagne e ad group:

```php
// PRIMA (attuale):
"issues": [
    {
      "severity": "high",
      "area": "copy",
      "description": "Descrizione del problema",
      "recommendation": "Azione consigliata specifica"
    }
]

// DOPO:
"issues": [
    {
      "severity": "high",
      "area": "copy",
      "fix_type": "copy",
      "description": "Descrizione del problema",
      "recommendation": "Azione consigliata specifica"
    }
]
```

Stessa modifica per gli ad group issues.

- [ ] **Step 2: Aggiungere le regole per fix_type nelle REGOLE**

Dopo la linea `- area per ad groups: "keywords", "copy", "landing", "performance", "match_type"` (linea 390), aggiungere:

```
- fix_type (OBBLIGATORIO per ogni issue): indica l'AZIONE concreta da compiere per risolvere il problema.
  Valori possibili:
  * "rewrite_ads" — riscrivere copy annunci (headline/description). USA PER: QS basso dovuto a scarsa pertinenza annuncio-keyword, headline generiche, copy non ottimizzato. Applicabile SOLO a livello ad group.
  * "add_negatives" — aggiungere keyword negative. USA PER: traffico non pertinente, spreco budget su query irrilevanti. Applicabile a livello campagna o ad group.
  * "remove_duplicates" — rimuovere keyword duplicate tra ad group. USA PER: cannibalizzazione interna, keyword presenti in più ad group.
  * "add_extensions" — aggiungere estensioni mancanti. USA PER: sitelink/callout/snippet mancanti. Applicabile SOLO a livello campagna.
  * null — nessuna azione automatica possibile (problemi strutturali, budget, landing page, performance generica). L'utente deve agire manualmente.
  REGOLA FONDAMENTALE: fix_type deve corrispondere all'AZIONE, non all'area del problema.
  Esempio: area="keywords" (il problema è sulle keyword) ma fix_type="rewrite_ads" (la soluzione è riscrivere gli annunci per matchare meglio le keyword).
  Esempio: area="performance" (il problema è CTR basso) ma fix_type="rewrite_ads" (la soluzione è migliorare i copy).
  Esempio: area="keywords" (il problema è traffico non pertinente) e fix_type="add_negatives" (la soluzione è aggiungere negative).
```

- [ ] **Step 3: Aggiornare anche campaign_suggestions**

Nella sezione `campaign_suggestions` del prompt (linee ~286-293), aggiungere `fix_type` anche ai suggerimenti:

```
Ogni suggerimento: area, priority (high/medium/low), suggestion (testo specifico), expected_impact (impatto stimato), fix_type (stessi valori degli issues, o null se non automatizzabile).
```

E nella struttura JSON (linee ~368-374):
```json
"campaign_suggestions": [
    {
      "area": "Struttura Campagne",
      "priority": "high",
      "suggestion": "Suggerimento concreto e azionabile",
      "expected_impact": "Impatto stimato quantificato",
      "fix_type": "rewrite_ads|add_negatives|remove_duplicates|add_extensions|null"
    }
]
```

- [ ] **Step 4: Lint check**

```bash
php -l modules/ads-analyzer/services/CampaignEvaluatorService.php
```

- [ ] **Step 5: Commit**

```bash
git add modules/ads-analyzer/services/CampaignEvaluatorService.php
git commit -m "feat(ads-analyzer): add fix_type field to AI evaluation prompt"
```

---

## Chunk 2: Persistenza — Tabella e Model

### Task 2: Creare tabella ga_generated_fixes

**Files:**
- Create: `database/migrations/2026_03_13_generated_fixes.sql`

- [ ] **Step 1: Creare la migration SQL**

```sql
CREATE TABLE IF NOT EXISTS ga_generated_fixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    fix_type ENUM('rewrite_ads', 'add_negatives', 'remove_duplicates', 'add_extensions') NOT NULL,
    scope_level ENUM('campaign', 'ad_group') NOT NULL,
    campaign_name VARCHAR(255) DEFAULT NULL,
    ad_group_name VARCHAR(255) DEFAULT NULL,
    ad_group_id_google VARCHAR(50) DEFAULT NULL,
    campaign_id_google VARCHAR(50) DEFAULT NULL,
    issue_description TEXT DEFAULT NULL,
    recommendation TEXT DEFAULT NULL,
    ai_response JSON DEFAULT NULL,
    display_text TEXT DEFAULT NULL,
    status ENUM('generated', 'applied', 'failed') NOT NULL DEFAULT 'generated',
    applied_at DATETIME DEFAULT NULL,
    apply_result JSON DEFAULT NULL,
    credits_used DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evaluation (evaluation_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Eseguire la migration in locale**

```bash
mysql -u root seo_toolkit < database/migrations/2026_03_13_generated_fixes.sql
```

### Task 3: Creare il Model GeneratedFix

**Files:**
- Create: `modules/ads-analyzer/models/GeneratedFix.php`

- [ ] **Step 1: Creare il model**

```php
<?php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class GeneratedFix
{
    public static function create(array $data): int
    {
        return Database::insert('ga_generated_fixes', [
            'evaluation_id' => $data['evaluation_id'],
            'project_id' => $data['project_id'],
            'user_id' => $data['user_id'],
            'fix_type' => $data['fix_type'],
            'scope_level' => $data['scope_level'],
            'campaign_name' => $data['campaign_name'] ?? null,
            'ad_group_name' => $data['ad_group_name'] ?? null,
            'ad_group_id_google' => $data['ad_group_id_google'] ?? null,
            'campaign_id_google' => $data['campaign_id_google'] ?? null,
            'issue_description' => $data['issue_description'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
            'ai_response' => isset($data['ai_response']) ? json_encode($data['ai_response'], JSON_UNESCAPED_UNICODE) : null,
            'display_text' => $data['display_text'] ?? null,
            'credits_used' => $data['credits_used'] ?? 0,
        ]);
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM ga_generated_fixes WHERE id = ?",
            [$id]
        );
    }

    public static function getByEvaluation(int $evaluationId): array
    {
        return Database::fetchAll(
            "SELECT * FROM ga_generated_fixes WHERE evaluation_id = ? ORDER BY created_at DESC",
            [$evaluationId]
        );
    }

    public static function markApplied(int $id, array $result): bool
    {
        return Database::update('ga_generated_fixes', [
            'status' => 'applied',
            'applied_at' => date('Y-m-d H:i:s'),
            'apply_result' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ], 'id = ?', [$id]) > 0;
    }

    public static function markFailed(int $id, string $error): bool
    {
        return Database::update('ga_generated_fixes', [
            'status' => 'failed',
            'apply_result' => json_encode(['error' => $error], JSON_UNESCAPED_UNICODE),
        ], 'id = ?', [$id]) > 0;
    }
}
```

- [ ] **Step 2: Lint check**

```bash
php -l modules/ads-analyzer/models/GeneratedFix.php
```

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_03_13_generated_fixes.sql modules/ads-analyzer/models/GeneratedFix.php
git commit -m "feat(ads-analyzer): add ga_generated_fixes table and model"
```

---

## Chunk 3: Generator Service — Usare fix_type

### Task 4: Aggiornare EvaluationGeneratorService per fix_type

**Files:**
- Modify: `modules/ads-analyzer/services/EvaluationGeneratorService.php`

Attualmente il service usa `$type` = "copy"|"keywords"|"extensions" per decidere quale prompt builder chiamare. Dobbiamo mappare i nuovi fix_type ai prompt builder giusti.

- [ ] **Step 1: Aggiornare il metodo generate()**

Modificare il match in `generate()` (linea 33-37):

```php
public function generate(int $userId, string $type, array $context, array $data): array
{
    $prompt = match ($type) {
        'rewrite_ads'       => $this->buildCopyPrompt($context, $data),
        'add_negatives'     => $this->buildNegativesPrompt($context, $data),
        'remove_duplicates' => $this->buildDuplicatesPrompt($context, $data),
        'add_extensions'    => $this->buildExtensionsPrompt($context, $data),
        // Backwards compatibility
        'copy'              => $this->buildCopyPrompt($context, $data),
        'keywords'          => $this->buildNegativesPrompt($context, $data),
        'extensions'        => $this->buildExtensionsPrompt($context, $data),
        default => throw new \Exception('Tipo generazione non supportato: ' . $type),
    };

    // ... resto invariato
}
```

- [ ] **Step 2: Separare buildKeywordsPrompt in due metodi**

Rinominare `buildKeywordsPrompt` in `buildNegativesPrompt` (la parte negativa) e `buildDuplicatesPrompt` (la parte duplicati). Rimuovere la logica `$isDuplicate` dal singolo metodo:

Il metodo `buildNegativesPrompt()` è la parte da linea 309 a 343 (prompt keyword negative, SENZA la logica duplicati).

Il metodo `buildDuplicatesPrompt()` è la parte da linea 248 a 307 (prompt duplicati).

- [ ] **Step 3: Lint check**

```bash
php -l modules/ads-analyzer/services/EvaluationGeneratorService.php
```

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/services/EvaluationGeneratorService.php
git commit -m "refactor(ads-analyzer): split generator by fix_type instead of area"
```

---

## Chunk 4: Controller — Persistenza e Targeting

### Task 5: Aggiornare generateFix() con persistenza e fix_type

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php:776-870`

- [ ] **Step 1: Aggiornare allowedTypes**

Cambiare linea 814:

```php
// PRIMA:
$allowedTypes = ['extensions', 'copy', 'keywords'];

// DOPO:
$allowedTypes = ['rewrite_ads', 'add_negatives', 'remove_duplicates', 'add_extensions',
                 'copy', 'keywords', 'extensions']; // backwards compat
```

- [ ] **Step 2: Aggiungere require del model e salvataggio in DB**

Dopo la generazione AI (dopo linea 851 `$result = $service->generate(...)`), aggiungere il salvataggio:

```php
require_once __DIR__ . '/../models/GeneratedFix.php';
$fixId = \Modules\AdsAnalyzer\Models\GeneratedFix::create([
    'evaluation_id' => $evalId,
    'project_id' => $projectId,
    'user_id' => $user['id'],
    'fix_type' => $type,
    'scope_level' => !empty($context['ad_group_name']) ? 'ad_group' : 'campaign',
    'campaign_name' => $context['campaign_name'] ?? null,
    'ad_group_name' => $context['ad_group_name'] ?? null,
    'ad_group_id_google' => $context['ad_group_id_google'] ?? null,
    'campaign_id_google' => $context['campaign_id_google'] ?? null,
    'issue_description' => $context['issue'] ?? $context['suggestion'] ?? null,
    'recommendation' => $context['recommendation'] ?? $context['expected_impact'] ?? null,
    'ai_response' => $result,
    'display_text' => self::formatFixForDisplay($type, $result),
    'credits_used' => $fixCost,
]);
```

E nella risposta JSON aggiungere `fix_id`:

```php
echo json_encode([
    'success' => true,
    'type' => $type,
    'fix_id' => $fixId,
    'data' => $result,
    'content' => self::formatFixForDisplay($type, $result),
]);
```

### Task 6: Aggiornare applyToGoogleAds() — targeting per Google ID

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php:997-1212`

- [ ] **Step 1: Accettare fix_id e ad_group_id_google dal POST**

```php
$fixId = (int)($_POST['fix_id'] ?? 0);
$adGroupIdGoogle = $_POST['ad_group_id_google'] ?? '';
```

- [ ] **Step 2: Per copy (rewrite_ads), usare ad_group_id_google esplicito**

Sostituire la logica "primo ad group trovato" (linee 1118-1129) con:

```php
} elseif (str_contains($type, 'copy') || $type === 'rewrite_ads') {
    $headlines = array_map(fn($h) => ['text' => $h], $data['headlines'] ?? []);
    $descriptions = array_map(fn($d) => ['text' => $d], $data['descriptions'] ?? []);

    if (empty($headlines) || empty($descriptions)) {
        ob_end_clean();
        echo json_encode(['error' => 'Headline o description mancanti']);
        exit;
    }

    // Usa ad_group_id_google esplicito dal context
    if (empty($adGroupIdGoogle)) {
        ob_end_clean();
        echo json_encode(['error' => 'Seleziona un gruppo annunci prima di applicare i copy']);
        exit;
    }

    $adGroupResource = "customers/{$customerId}/adGroups/{$adGroupIdGoogle}";

    // Trova URL finale dagli annunci dell'ad group
    $finalUrl = '';
    $latestSync = Sync::getLatestByProject($projectId);
    if ($latestSync) {
        $ads = Ad::getByRun($latestSync['id']);
        foreach ($ads as $ad) {
            if (($ad['ad_group_id_google'] ?? '') === $adGroupIdGoogle && !empty($ad['final_url'])) {
                $finalUrl = $ad['final_url'];
                break;
            }
        }
    }

    $gads->mutateAdGroupAds([/* ... stessa struttura RSA ... */]);
    // ...
}
```

- [ ] **Step 3: Per keyword duplicate removal, usare ad_group_id_google**

Nella sezione `remove_duplicates` (linee 1157-1188), aggiungere filtro per campaign_id alla GAQL query:

```php
$gaql = "SELECT ad_group_criterion.resource_name, ad_group_criterion.keyword.text, " .
        "ad_group.name, ad_group.id " .
        "FROM ad_group_criterion " .
        "WHERE ad_group_criterion.keyword.text = '{$escapedKw}' " .
        "AND ad_group_criterion.type = 'KEYWORD' " .
        "AND ad_group_criterion.status != 'REMOVED' " .
        "AND ad_group_criterion.negative = FALSE";
```

E nel match usare `ad_group.id` (Google ID) invece del nome:

```php
$agId = (string)($row['adGroup']['id'] ?? '');
// removeFrom contiene ad_group_id_google, non nomi
if (in_array($agId, $removeFromIds) && !empty($resourceName)) {
```

- [ ] **Step 4: Aggiornare GeneratedFix status dopo apply**

```php
if ($fixId > 0) {
    require_once __DIR__ . '/../models/GeneratedFix.php';
    \Modules\AdsAnalyzer\Models\GeneratedFix::markApplied($fixId, [
        'applied' => $applied,
        'details' => implode(', ', $details),
    ]);
}
```

- [ ] **Step 5: Aggiornare formatFixForDisplay per i nuovi tipi**

Mappare `rewrite_ads` → stessa logica di `copy`, `add_negatives` → stessa logica di `keywords`, etc.

- [ ] **Step 6: Lint check**

```bash
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 7: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): persistent fixes, fix_type routing, Google ID targeting"
```

---

## Chunk 5: View — UI con fix_type e persistenza

### Task 7: Caricare fix esistenti e passarli alla view

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php` (metodo showEvaluation)

- [ ] **Step 1: Caricare i fix salvati dal DB**

Nel metodo che renderizza la pagina evaluation, aggiungere:

```php
require_once __DIR__ . '/../models/GeneratedFix.php';
$savedFixes = \Modules\AdsAnalyzer\Models\GeneratedFix::getByEvaluation($evalId);
```

E passarli alla view:

```php
'savedFixes' => $savedFixes,
```

### Task 8: Aggiornare la view evaluation.php

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/evaluation.php`

- [ ] **Step 1: Usare fix_type invece di area per genType**

Per le issue a livello campagna (linee ~1269-1284), cambiare:

```php
// PRIMA:
$genType = in_array($area, $generatableAreas) ? $area : null;

// DOPO:
$fixType = $issue['fix_type'] ?? null;
$genType = in_array($fixType, ['rewrite_ads', 'add_negatives', 'remove_duplicates', 'add_extensions']) ? $fixType : null;
```

Stessa modifica per gli ad group issues (linee ~1303-1330).

Per i campaign_suggestions (linee ~1061-1065), cambiare:

```php
// PRIMA:
$csGenType = null;
if (str_contains($csAreaLower, 'copy') || str_contains($csAreaLower, 'annunci')) $csGenType = 'copy';
elseif (str_contains($csAreaLower, 'keyword')) $csGenType = 'keywords';
elseif (str_contains($csAreaLower, 'estension') || str_contains($csAreaLower, 'extension')) $csGenType = 'extensions';

// DOPO:
$csGenType = $cs['fix_type'] ?? null;
if (!in_array($csGenType, ['rewrite_ads', 'add_negatives', 'remove_duplicates', 'add_extensions'])) {
    $csGenType = null;
}
```

- [ ] **Step 2: Rimuovere "Genera con AI" per copy a livello campagna**

Per le issue a livello campagna, `rewrite_ads` NON deve apparire se non c'è un ad group context. Aggiungere nella logica di genType per campaign issues:

```php
// A livello campagna, rewrite_ads non è applicabile (serve un ad group specifico)
if ($fixType === 'rewrite_ads') $genType = null;
```

- [ ] **Step 3: Passare ad_group_id_google nel context**

Nella costruzione dei dati Alpine per ad group issues, aggiungere il Google ID dell'ad group:

```php
// Nel PHP che costruisce $agIssues:
'adGroupIdGoogle' => $ag['ad_group_id_google'] ?? '',
```

E nel template HTML del bottone (per ad group issues):

```javascript
// Nel @click del bottone "Genera con AI" per ad group:
generateFix(agIss.genType, {
    issue: agIss.description,
    recommendation: agIss.recommendation || '',
    campaign_name: campaignsData[selectedCampaign].name,
    ad_group_name: ag.name,
    ad_group_id_google: ag.adGroupIdGoogle
}, agIss.genKey)
```

- [ ] **Step 4: Pre-popolare generators con fix salvati**

Nel blocco Alpine.js `evaluationDashboard()`, dopo `generators: {}`, aggiungere:

```javascript
// Pre-carica fix salvati dal DB
savedFixes: <?= json_encode(array_map(function($fix) {
    return [
        'id' => $fix['id'],
        'fix_type' => $fix['fix_type'],
        'scope_level' => $fix['scope_level'],
        'campaign_name' => $fix['campaign_name'],
        'ad_group_name' => $fix['ad_group_name'],
        'data' => json_decode($fix['ai_response'] ?? '{}', true),
        'content' => $fix['display_text'],
        'status' => $fix['status'],
        'ad_group_id_google' => $fix['ad_group_id_google'],
    ];
}, $savedFixes ?? []), JSON_UNESCAPED_UNICODE) ?>,
```

E nella funzione `init()` del componente, ripristinare i generators per i fix salvati:

```javascript
init() {
    // Ripristina fix generati precedentemente
    this.savedFixes.forEach(fix => {
        // Cerca la chiave giusta basata su campaign/ad_group/issue
        // Per ora usa un mapping semplice: fix_id come chiave
        const key = 'saved_' + fix.id;
        this.generators[key] = {
            loading: false,
            result: fix.content,
            type: fix.fix_type,
            data: fix.data,
            error: null,
            copied: false,
            applied: fix.status === 'applied',
            fixId: fix.id,
        };
    });
},
```

- [ ] **Step 5: Aggiornare generateFix() JS per salvare fix_id nella risposta**

```javascript
async generateFix(type, context, key) {
    this.generators[key] = { loading: true, result: null, error: null, copied: false };

    try {
        const formData = new FormData();
        formData.append('_csrf_token', this.csrfToken);
        formData.append('type', type);
        formData.append('context', JSON.stringify(context));

        const resp = await fetch(this.generateUrl, { method: 'POST', body: formData });
        if (!resp.ok) { /* ... error handling ... */ }
        const data = await resp.json();
        if (data.error) throw new Error(data.error);

        this.generators[key] = {
            loading: false,
            result: data.content || '',
            type: data.type || '',
            data: data.data || null,
            error: null,
            copied: false,
            fixId: data.fix_id || null,  // ← NUOVO: salva l'ID del fix
            adGroupIdGoogle: context.ad_group_id_google || null,  // ← NUOVO
        };
    } catch (e) {
        this.generators[key] = { loading: false, result: null, type: '', data: null, error: e.message, copied: false };
    }
},
```

- [ ] **Step 6: Aggiornare executeApply() JS per passare fix_id e ad_group_id_google**

```javascript
async executeApply() {
    // ...
    const formData = new FormData();
    formData.append('_csrf_token', this.csrfToken);
    formData.append('type', gen.type);
    formData.append('data', JSON.stringify(gen.data));
    if (gen.fixId) formData.append('fix_id', gen.fixId);
    if (gen.adGroupIdGoogle) formData.append('ad_group_id_google', gen.adGroupIdGoogle);
    // ...
}
```

- [ ] **Step 7: Aggiornare testi UI per i nuovi fix_type**

Nel modal di conferma applicazione, aggiornare le descrizioni:

```javascript
const typeDescriptions = {
    'rewrite_ads': 'Verrà creato un nuovo annuncio RSA in stato PAUSED nel gruppo annunci selezionato. Potrai attivarlo da Google Ads.',
    'add_negatives': isDuplicateRemoval ? '...' : 'Verranno aggiunte keyword negative alla campagna.',
    'remove_duplicates': 'Verranno RIMOSSE le keyword duplicate dai gruppi annunci indicati. Operazione non reversibile.',
    'add_extensions': 'Verranno aggiunte le estensioni generate alla campagna.',
    // Backwards compat
    'copy': 'Verrà creato un nuovo annuncio RSA in stato PAUSED.',
    'keywords': 'Verranno aggiunte keyword negative alla campagna.',
    'extensions': 'Verranno aggiunte le estensioni generate alla campagna.',
};
```

- [ ] **Step 8: Aggiungere ad_group_id_google ai dati Alpine degli ad group**

Nella costruzione PHP dei dati adGroups (linee ~1286-1325), aggiungere:

```php
return [
    'name' => $ag['ad_group_name'] ?? 'Gruppo',
    'adGroupIdGoogle' => $ag['ad_group_id_google'] ?? '',  // ← NUOVO
    // ... resto invariato
];
```

Questo richiede che `ad_group_id_google` sia presente nei dati AI. Verificare che il prompt lo includa o estrarlo dai dati sync.

**NOTA:** L'AI response non contiene `ad_group_id_google`. Deve essere estratto dai dati sync e iniettato nel mapping PHP. Nel controller che renderizza la evaluation, fare un lookup:

```php
// Dopo aver caricato $aiResponse
$syncId = $evaluation['sync_id'];
$adGroupsFromSync = CampaignAdGroup::getByRun($syncId);
$agIdMap = [];
foreach ($adGroupsFromSync as $ag) {
    $agIdMap[$ag['ad_group_name']] = $ag['ad_group_id_google'] ?? '';
}
// Passare $agIdMap alla view
```

Nella view, iniettare nell'ad group data:

```php
'adGroupIdGoogle' => $agIdMap[$ag['ad_group_name'] ?? ''] ?? '',
```

- [ ] **Step 9: Lint check e test manuale**

```bash
php -l modules/ads-analyzer/views/campaigns/evaluation.php
php -l modules/ads-analyzer/controllers/CampaignController.php
```

- [ ] **Step 10: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/evaluation.php modules/ads-analyzer/controllers/CampaignController.php
git commit -m "feat(ads-analyzer): UI fix_type, persistent fixes, ad group targeting"
```

---

## Chunk 6: Deploy e Migration Produzione

### Task 9: Deploy completo

- [ ] **Step 1: Push**

```bash
git push origin main
```

- [ ] **Step 2: Deploy su Hetzner**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && git pull origin main"
```

- [ ] **Step 3: Eseguire migration in produzione**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "cd /var/www/ainstein.it/public_html && mysql -u ainstein -pAinstein_DB_2026\!Secure ainstein_seo < database/migrations/2026_03_13_generated_fixes.sql"
```

- [ ] **Step 4: Verificare tabella creata**

```bash
ssh -i ~/.ssh/ainstein_hetzner ainstein@91.99.20.247 "mysql -u ainstein -pAinstein_DB_2026\!Secure ainstein_seo -e 'DESCRIBE ga_generated_fixes;'"
```

- [ ] **Step 5: Test — Lanciare nuova valutazione su progetto 18 (amevista)**

1. Andare su https://ainstein.it/ads-analyzer/projects/18/campaign-dashboard
2. Selezionare 2-3 campagne e lanciare valutazione AI
3. Verificare che gli issues abbiano `fix_type` corretto
4. Verificare che "Genera con AI" per ad group issues con area="keywords" ma fix_type="rewrite_ads" generi COPY, non keyword negative
5. Verificare che i fix generati persistano dopo reload pagina
6. Verificare che "Applica su Google Ads" per copy usi l'ad group corretto

---

## Riepilogo Cambiamenti

| Prima | Dopo |
|-------|------|
| genType basato su `area` dell'issue | genType basato su `fix_type` (azione da compiere) |
| area="keywords" → genera sempre negative | area="keywords" + fix_type="rewrite_ads" → genera copy |
| Fix generati persi al reload | Fix salvati in `ga_generated_fixes`, ricaricati al load |
| Copy applicato al primo ad group trovato | Copy applicato all'ad group specifico (Google ID) |
| "Genera con AI" per copy a livello campagna (senza ad group) | Solo a livello ad group per copy |
| Keyword duplicate rimosse per nome | Rimosse per Google ID |
| Nessun audit trail delle applicazioni | Stato applied/failed salvato con dettagli |
