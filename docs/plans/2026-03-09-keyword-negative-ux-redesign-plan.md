# Keyword Negative UX Redesign - Piano di Implementazione

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Trasformare la sezione Keyword Negative da strumento inutilizzabile a ciclo iterativo di pulizia: Analizza → Applica → Verifica → Ripeti.

**Architecture:** Single-page Alpine.js con 3 stati (vuoto, risultati analisi, post-applicazione). L'AI analizza tutti i termini, suggerisce negative con livello (campagna/ad_group) e match type. Confronto automatico tra analisi successive.

**Tech Stack:** PHP 8+, Alpine.js, Tailwind CSS, AJAX lungo pattern, Google Ads API v20 REST

**Design document:** `docs/plans/2026-03-09-keyword-negative-ux-redesign-design.md`

---

### Task 1: Fix dati — Popolare campaign_name e ad_group_name nei search terms

**Files:**
- Modify: `modules/ads-analyzer/services/CampaignSyncService.php:656-670`
- Modify: `modules/ads-analyzer/models/SearchTerm.php:9-30`

Il sync service ha `campaign_name` e `ad_group_name` disponibili in `$agg` (linee 622-624) ma non li passa a `SearchTerm::create()`.

**Step 1: Aggiornare SearchTerm::create() per accettare campaign_name e ad_group_name**

In `modules/ads-analyzer/models/SearchTerm.php`, aggiungere i campi al record:

```php
public static function create(array $data): int
{
    $record = [
        'project_id' => $data['project_id'],
        'ad_group_id' => $data['ad_group_id'],
        'term' => $data['term'],
        'match_type' => $data['match_type'] ?? null,
        'clicks' => $data['clicks'] ?? 0,
        'impressions' => $data['impressions'] ?? 0,
        'ctr' => $data['ctr'] ?? 0,
        'cost' => $data['cost'] ?? 0,
        'conversions' => $data['conversions'] ?? 0,
        'conversion_value' => $data['conversion_value'] ?? 0,
        'is_zero_ctr' => (int)($data['is_zero_ctr'] ?? 0)
    ];

    if (isset($data['sync_id']) || isset($data['run_id'])) {
        $record['sync_id'] = $data['sync_id'] ?? $data['run_id'];
    }
    if (isset($data['campaign_name'])) {
        $record['campaign_name'] = $data['campaign_name'];
    }
    if (isset($data['ad_group_name'])) {
        $record['ad_group_name'] = $data['ad_group_name'];
    }

    return Database::insert('ga_search_terms', $record);
}
```

**Step 2: Passare campaign_name e ad_group_name in CampaignSyncService::syncSearchTerms()**

In `modules/ads-analyzer/services/CampaignSyncService.php` linea 656, aggiungere i 2 campi:

```php
SearchTerm::create([
    'project_id' => $this->projectId,
    'sync_id' => $this->syncId,
    'ad_group_id' => $localAdGroupId,
    'term' => $agg['term'],
    'match_type' => null,
    'clicks' => $clicks,
    'impressions' => $impressions,
    'ctr' => round($ctr, 2),
    'cost' => round($cost, 2),
    'conversions' => round($agg['conversions'], 2),
    'conversion_value' => round($agg['conversion_value'], 2),
    'is_zero_ctr' => $isZeroCtr,
    'campaign_name' => $agg['campaign_name'] ?? null,
    'ad_group_name' => $agg['ad_group_name'] ?? null,
]);
```

**Step 3: Backfill dati esistenti — aggiornare search terms della sync 7 dal DB**

Eseguire SQL per aggiornare i search terms esistenti con i nomi da `ga_campaign_ad_groups`:

```sql
UPDATE ga_search_terms st
JOIN ga_ad_groups ag ON st.ad_group_id = ag.id
SET st.ad_group_name = ag.name
WHERE st.ad_group_name IS NULL AND ag.name IS NOT NULL;

-- Per campaign_name, recuperare dalla tabella campagne
UPDATE ga_search_terms st
JOIN ga_ad_groups ag ON st.ad_group_id = ag.id
JOIN ga_campaign_ad_groups cag ON cag.ad_group_name = ag.name AND cag.sync_id = st.sync_id
SET st.campaign_name = cag.campaign_name
WHERE st.campaign_name IS NULL AND cag.campaign_name IS NOT NULL;
```

**Step 4: Verificare con PHP -l e query DB**

```bash
php -l modules/ads-analyzer/models/SearchTerm.php
php -l modules/ads-analyzer/services/CampaignSyncService.php
```

Verificare: `SELECT term, campaign_name, ad_group_name FROM ga_search_terms WHERE project_id = 15 LIMIT 5;` — i campi devono essere valorizzati.

**Step 5: Commit**

```bash
git add modules/ads-analyzer/models/SearchTerm.php modules/ads-analyzer/services/CampaignSyncService.php
git commit -m "fix(ads-analyzer): populate campaign_name and ad_group_name in search terms during sync"
```

---

### Task 2: Migrazione DB — Nuove colonne per livello suggerito

**Files:**
- Create: `modules/ads-analyzer/database/migrations/012_negative_kw_level.sql`

**Step 1: Creare migrazione SQL**

```sql
-- Colonne per il livello di applicazione suggerito dall'AI
ALTER TABLE ga_negative_keywords
    ADD COLUMN suggested_level ENUM('campaign', 'ad_group') DEFAULT 'campaign' AFTER suggested_match_type,
    ADD COLUMN suggested_campaign_resource VARCHAR(255) NULL AFTER suggested_level,
    ADD COLUMN suggested_ad_group_resource VARCHAR(255) NULL AFTER suggested_campaign_resource;
```

**Step 2: Eseguire in locale**

```bash
mysql -u root seo_toolkit < modules/ads-analyzer/database/migrations/012_negative_kw_level.sql
```

**Step 3: Verificare**

```bash
mysql -u root seo_toolkit -e "DESCRIBE ga_negative_keywords;"
```

I campi `suggested_level`, `suggested_campaign_resource`, `suggested_ad_group_resource` devono apparire.

**Step 4: Commit**

```bash
git add modules/ads-analyzer/database/migrations/012_negative_kw_level.sql
git commit -m "feat(ads-analyzer): add suggested_level columns to ga_negative_keywords"
```

---

### Task 3: Aggiornare prompt AI — Struttura campagne e livello suggerito

**Files:**
- Modify: `modules/ads-analyzer/services/KeywordAnalyzerService.php`

L'AI deve ricevere la struttura completa campagne → ad groups e per ogni keyword suggerire il livello di applicazione.

**Step 1: Aggiornare la signature di analyzeAdGroup()**

Aggiungere un parametro `$campaignStructure` che descrive la struttura campagne/ad groups del progetto:

```php
public function analyzeAdGroup(
    int $userId,
    string $businessContext,
    array $terms,
    int $maxTerms = 300,
    string $campaignStructure = ''
): array {
```

**Step 2: Aggiornare buildPrompt() con la struttura campagne e il livello**

```php
private function buildPrompt(string $businessContext, string $terms, string $campaignStructure = ''): string
{
    $structureBlock = '';
    if (!empty($campaignStructure)) {
        $structureBlock = "\n\nSTRUTTURA CAMPAGNE E AD GROUP:\n{$campaignStructure}\n";
    }

    return <<<PROMPT
Sei un esperto Google Ads. Analizza i termini di ricerca e identifica keyword negative da escludere.

CONTESTO BUSINESS:
{$businessContext}
{$structureBlock}
TERMINI DI RICERCA (formato: termine | click | impressioni | costo | campagna > ad group):
{$terms}

ISTRUZIONI:
1. Analizza ATTENTAMENTE il contesto business per capire cosa vende/promuove il cliente
2. Identifica termini di ricerca NON PERTINENTI rispetto all'offerta
3. Raggruppa le keyword negative in categorie logiche per questo specifico business
4. Assegna priorita: "high" (escludi subito), "medium" (probabilmente da escludere), "evaluate" (valuta caso per caso)
5. Per ogni keyword, decidi il LIVELLO di applicazione:
   - "campaign" se il termine e irrilevante per TUTTE le campagne/ad group (es. termine completamente fuori target)
   - "ad_group" se il termine e irrilevante solo per ALCUNI ad group ma potrebbe essere valido per altri
6. Suggerisci il match_type piu appropriato:
   - "exact" per termini molto specifici che devono essere bloccati esattamente come sono
   - "phrase" per pattern che devono essere bloccati come frase (default)
   - "broad" per concetti ampi che devono essere bloccati in qualsiasi combinazione

Rispondi SOLO con un JSON valido (senza markdown, senza backtick, senza testo prima o dopo):
{
  "summary": "Breve riepilogo dell'analisi (1-2 frasi)",
  "stats": {
    "total_terms": numero_termini_analizzati,
    "zero_ctr_terms": numero_termini_con_ctr_zero,
    "wasted_impressions": impressioni_sprecate_stimate,
    "estimated_waste_cost": costo_stimato_spreco
  },
  "categories": {
    "NOME_CATEGORIA_1": {
      "priority": "high|medium|evaluate",
      "description": "Breve descrizione della categoria",
      "keywords": [
        {
          "text": "keyword1",
          "match_type": "exact|phrase|broad",
          "level": "campaign|ad_group",
          "target_name": "nome campagna o ad group dove applicare (se ad_group)"
        }
      ]
    }
  }
}

REGOLE PER LE CATEGORIE:
- Crea 5-12 categorie pertinenti al business
- Adatta i nomi delle categorie al contesto specifico
- Estrai SOLO keyword singole o frasi brevi (max 3 parole)
- Identifica pattern ricorrenti nei termini non pertinenti
- IMPORTANTE: per il campo "level", considera che una keyword negativa a livello campagna blocca il termine su TUTTI gli ad group. Usa "ad_group" quando il termine potrebbe essere valido per un altro ad group della stessa campagna.
PROMPT;
}
```

**Step 3: Aggiornare la chiamata a buildPrompt in analyzeAdGroup()**

```php
// Dentro analyzeAdGroup(), cambiare:
$prompt = $this->buildPrompt($businessContext, $termsText);
// In:
$prompt = $this->buildPrompt($businessContext, $termsText, $campaignStructure);
```

E aggiornare il formato dei termini per includere campagna, ad group e costo:

```php
$termsSummary = array_map(
    fn($t) => "{$t['term']} | {$t['clicks']} clic | {$t['impressions']} imp | €" . round((float)($t['cost'] ?? 0), 2) . " | " . ($t['campaign_name'] ?? '?') . " > " . ($t['ad_group_name'] ?? '?'),
    $termsForPrompt
);
```

**Step 4: Aggiornare parseResponse() per il nuovo formato keyword**

La risposta ora ha keyword come oggetti (non stringhe). `parseResponse()` rimane uguale (parse JSON generico), ma il controller `saveAnalysisResults()` deve gestire il nuovo formato. Questo sarà nella Task 4.

**Step 5: Verificare e commit**

```bash
php -l modules/ads-analyzer/services/KeywordAnalyzerService.php
git add modules/ads-analyzer/services/KeywordAnalyzerService.php
git commit -m "feat(ads-analyzer): enrich AI prompt with campaign structure and suggested level"
```

---

### Task 4: Aggiornare controller — Contesto automatico e salvataggio livello

**Files:**
- Modify: `modules/ads-analyzer/controllers/SearchTermAnalysisController.php`

**Step 1: Aggiornare analyze() — contesto automatico senza form obbligatorio**

Rimuovere il requisito di `business_context` minimo 20 caratteri. Il contesto viene costruito automaticamente dalle landing page, nomi campagne e keyword attive.

In `analyze()` (linea 358), sostituire il blocco validazione:

```php
// Contesto business: opzionale dall'utente, arricchito automaticamente
$manualContext = trim($_POST['business_context'] ?? '');

// Costruisci contesto automatico dalla struttura campagne
$autoContext = $this->buildAutoContext($projectId, $syncId);

// Combina: manuale + auto
$businessContext = $manualContext;
if (!empty($autoContext)) {
    $businessContext = (!empty($manualContext) ? $manualContext . "\n\n" : '') . $autoContext;
}

if (strlen($businessContext) < 20) {
    ob_end_clean();
    echo json_encode(['error' => 'Impossibile generare contesto automatico. Aggiungi una descrizione del business.']);
    exit;
}
```

**Step 2: Creare metodo buildAutoContext()**

Nuovo metodo privato che costruisce il contesto dalle informazioni disponibili:

```php
private function buildAutoContext(int $projectId, int $syncId): string
{
    $parts = [];

    // 1. Struttura campagne → ad groups
    $campaigns = Database::fetchAll(
        "SELECT DISTINCT campaign_name, campaign_id_google FROM ga_campaigns WHERE project_id = ? AND sync_id = ? ORDER BY campaign_name",
        [$projectId, $syncId]
    );

    $adGroupsByCampaign = [];
    $campaignAdGroups = Database::fetchAll(
        "SELECT campaign_name, ad_group_name, ad_group_id_google FROM ga_campaign_ad_groups WHERE project_id = ? AND sync_id = ? ORDER BY campaign_name, ad_group_name",
        [$projectId, $syncId]
    );
    foreach ($campaignAdGroups as $cag) {
        $adGroupsByCampaign[$cag['campaign_name']][] = $cag['ad_group_name'];
    }

    if (!empty($adGroupsByCampaign)) {
        $structure = "STRUTTURA ACCOUNT:\n";
        foreach ($adGroupsByCampaign as $campName => $agNames) {
            $structure .= "- Campagna: {$campName}\n";
            foreach ($agNames as $agName) {
                $structure .= "  - Ad Group: {$agName}\n";
            }
        }
        $parts[] = $structure;
    }

    // 2. Landing URL dagli annunci
    $landingUrls = Database::fetchAll(
        "SELECT DISTINCT ad_group_name, final_url FROM ga_ads WHERE project_id = ? AND sync_id = ? AND final_url IS NOT NULL AND final_url != '' ORDER BY ad_group_name",
        [$projectId, $syncId]
    );
    if (!empty($landingUrls)) {
        $urlSection = "LANDING PAGE PER AD GROUP:\n";
        foreach ($landingUrls as $lu) {
            $urlSection .= "- {$lu['ad_group_name']}: {$lu['final_url']}\n";
        }
        $parts[] = $urlSection;
    }

    // 3. Keyword attive
    $activeKeywords = Database::fetchAll(
        "SELECT campaign_name, ad_group_name, keyword_text, match_type FROM ga_ad_group_keywords WHERE project_id = ? AND sync_id = ? AND keyword_status = 'ENABLED' ORDER BY campaign_name, ad_group_name LIMIT 100",
        [$projectId, $syncId]
    );
    if (!empty($activeKeywords)) {
        $kwSection = "KEYWORD ATTIVE (max 100):\n";
        foreach ($activeKeywords as $kw) {
            $kwSection .= "- [{$kw['match_type']}] {$kw['keyword_text']} ({$kw['campaign_name']} > {$kw['ad_group_name']})\n";
        }
        $parts[] = $kwSection;
    }

    // 4. Contesti landing estratti (se disponibili da ga_ad_groups)
    $adGroupsWithContext = AdGroup::getByProject($projectId);
    $contexts = array_filter($adGroupsWithContext, fn($ag) => !empty($ag['extracted_context']));
    if (!empty($contexts)) {
        $ctxSection = "CONTESTO LANDING PAGE (estratto da AI):\n";
        foreach ($contexts as $ag) {
            $ctxSection .= "- {$ag['name']}: {$ag['extracted_context']}\n";
        }
        $parts[] = $ctxSection;
    }

    return implode("\n", $parts);
}
```

**Step 3: Costruire campaignStructure per il prompt AI**

Nella parte del loop `analyze()`, passare la struttura campagne al service:

```php
// Prima del loop degli ad groups (dopo la creazione dell'Analysis)
$campaignStructure = $this->buildCampaignStructureForPrompt($projectId, $syncId);

// Nel loop, aggiornare la chiamata:
$aiResult = $analyzer->analyzeAdGroup(
    $user['id'],
    $agContext,
    $terms,
    300,
    $campaignStructure
);
```

Nuovo metodo helper:

```php
private function buildCampaignStructureForPrompt(int $projectId, int $syncId): string
{
    $campaignAdGroups = Database::fetchAll(
        "SELECT campaign_name, ad_group_name, ad_group_id_google, campaign_id_google FROM ga_campaign_ad_groups WHERE project_id = ? AND sync_id = ? ORDER BY campaign_name, ad_group_name",
        [$projectId, $syncId]
    );

    $structure = [];
    foreach ($campaignAdGroups as $cag) {
        $structure[$cag['campaign_name']][] = $cag['ad_group_name'];
    }

    $lines = [];
    foreach ($structure as $campName => $agNames) {
        $lines[] = "Campagna '{$campName}': Ad Groups = " . implode(', ', $agNames);
    }
    return implode("\n", $lines);
}
```

**Step 4: Aggiornare saveAnalysisResults() per il nuovo formato keyword**

Il prompt ora restituisce keyword come oggetti con `text`, `match_type`, `level`, `target_name`. Aggiornare il salvataggio:

```php
private function saveAnalysisResults(int $projectId, int $adGroupId, array $analysis, int $analysisId): void
{
    // Mappa nomi campagna/ad group a resource names
    $campaignResources = $this->getCampaignResourceMap($projectId);
    $adGroupResources = $this->getAdGroupResourceMap($projectId);

    $sortOrder = 0;
    foreach ($analysis['categories'] ?? [] as $key => $data) {
        $categoryId = NegativeCategory::create([
            'project_id' => $projectId,
            'ad_group_id' => $adGroupId,
            'analysis_id' => $analysisId,
            'category_key' => $key,
            'category_name' => $this->formatCategoryName($key),
            'description' => $data['description'] ?? '',
            'priority' => $data['priority'] ?? 'medium',
            'keywords_count' => count($data['keywords'] ?? []),
            'sort_order' => $sortOrder++,
        ]);

        foreach ($data['keywords'] ?? [] as $keyword) {
            // Supporta sia formato vecchio (stringa) che nuovo (oggetto)
            if (is_string($keyword)) {
                NegativeKeyword::create([
                    'project_id' => $projectId,
                    'ad_group_id' => $adGroupId,
                    'analysis_id' => $analysisId,
                    'category_id' => $categoryId,
                    'keyword' => $keyword,
                    'is_selected' => ($data['priority'] ?? 'medium') !== 'evaluate',
                ]);
            } else {
                $level = $keyword['level'] ?? 'campaign';
                $targetName = $keyword['target_name'] ?? '';

                NegativeKeyword::create([
                    'project_id' => $projectId,
                    'ad_group_id' => $adGroupId,
                    'analysis_id' => $analysisId,
                    'category_id' => $categoryId,
                    'keyword' => $keyword['text'] ?? $keyword['keyword'] ?? '',
                    'is_selected' => ($data['priority'] ?? 'medium') !== 'evaluate',
                    'suggested_match_type' => $keyword['match_type'] ?? 'phrase',
                    'suggested_level' => $level,
                    'suggested_campaign_resource' => ($level === 'campaign') ? ($campaignResources[$targetName] ?? null) : null,
                    'suggested_ad_group_resource' => ($level === 'ad_group') ? ($adGroupResources[$targetName] ?? null) : null,
                ]);
            }
        }
    }
}
```

Nuovi metodi helper per le resource map:

```php
private function getCampaignResourceMap(int $projectId): array
{
    $project = Project::find($projectId);
    $customerId = $project['google_ads_customer_id'] ?? '';
    $campaigns = Database::fetchAll(
        "SELECT campaign_name, campaign_id_google FROM ga_campaigns WHERE project_id = ? ORDER BY id DESC",
        [$projectId]
    );
    $map = [];
    foreach ($campaigns as $c) {
        if (!empty($c['campaign_id_google']) && !empty($customerId)) {
            $map[$c['campaign_name']] = "customers/{$customerId}/campaigns/{$c['campaign_id_google']}";
        }
    }
    return $map;
}

private function getAdGroupResourceMap(int $projectId): array
{
    $project = Project::find($projectId);
    $customerId = $project['google_ads_customer_id'] ?? '';
    $adGroups = Database::fetchAll(
        "SELECT ad_group_name, ad_group_id_google FROM ga_campaign_ad_groups WHERE project_id = ? ORDER BY id DESC",
        [$projectId]
    );
    $map = [];
    foreach ($adGroups as $ag) {
        if (!empty($ag['ad_group_id_google']) && !empty($customerId)) {
            $map[$ag['ad_group_name']] = "customers/{$customerId}/adGroups/{$ag['ad_group_id_google']}";
        }
    }
    return $map;
}
```

**Step 5: Aggiornare NegativeKeyword::create() per i nuovi campi**

In `modules/ads-analyzer/models/NegativeKeyword.php`:

```php
public static function create(array $data): int
{
    $record = [
        'project_id' => $data['project_id'],
        'ad_group_id' => $data['ad_group_id'],
        'analysis_id' => $data['analysis_id'] ?? null,
        'category_id' => $data['category_id'],
        'keyword' => $data['keyword'],
        'is_selected' => $data['is_selected'] ?? true,
        'suggested_match_type' => $data['suggested_match_type'] ?? 'phrase',
        'suggested_level' => $data['suggested_level'] ?? 'campaign',
        'suggested_campaign_resource' => $data['suggested_campaign_resource'] ?? null,
        'suggested_ad_group_resource' => $data['suggested_ad_group_resource'] ?? null,
    ];

    return Database::insert('ga_negative_keywords', $record);
}
```

**Step 6: Verificare e commit**

```bash
php -l modules/ads-analyzer/controllers/SearchTermAnalysisController.php
php -l modules/ads-analyzer/models/NegativeKeyword.php
git add modules/ads-analyzer/controllers/SearchTermAnalysisController.php modules/ads-analyzer/models/NegativeKeyword.php
git commit -m "feat(ads-analyzer): auto-context, campaign structure in AI prompt, suggested level"
```

---

### Task 5: Aggiornare applyNegativeKeywords() — Applicazione per livello suggerito

**Files:**
- Modify: `modules/ads-analyzer/controllers/SearchTermAnalysisController.php:854-988`

L'utente non sceglie più il livello manualmente. Il controller raggruppa le keyword per `suggested_level` e applica di conseguenza.

**Step 1: Riscrivere applyNegativeKeywords()**

```php
public function applyNegativeKeywords(int $projectId): void
{
    \Core\Middleware::auth();
    $user = Auth::user();
    $project = Project::findAccessible($user['id'], $projectId);

    if (!$project) {
        http_response_code(404);
        echo json_encode(['error' => 'Progetto non trovato']);
        exit;
    }

    $role = $project['access_role'] ?? 'owner';
    if ($role === 'viewer') {
        http_response_code(403);
        echo json_encode(['error' => 'Non hai i permessi per applicare modifiche']);
        exit;
    }

    $customerId = $project['google_ads_customer_id'] ?? '';
    if (empty($customerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Account Google Ads non connesso. Vai nelle impostazioni per collegarlo.']);
        exit;
    }

    ignore_user_abort(true);
    set_time_limit(0);
    ob_start();
    header('Content-Type: application/json');
    session_write_close();

    $keywordIds = $_POST['keyword_ids'] ?? [];

    if (empty($keywordIds) || !is_array($keywordIds)) {
        ob_end_clean();
        echo json_encode(['error' => 'Nessuna keyword selezionata']);
        exit;
    }

    $keywords = NegativeKeyword::findByIds($keywordIds);

    if (empty($keywords)) {
        ob_end_clean();
        echo json_encode(['error' => 'Nessuna keyword trovata']);
        exit;
    }

    try {
        $loginCustomerId = $project['login_customer_id'] ?? '';
        $gads = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);

        // Raggruppa per livello suggerito
        $campaignOps = [];
        $adGroupOps = [];

        // Fallback: se non ci sono resource names, usa la prima campagna disponibile
        $defaultCampaignResource = $this->getDefaultCampaignResource($projectId, $customerId);

        foreach ($keywords as $kw) {
            $matchType = strtoupper($kw['suggested_match_type'] ?? 'PHRASE');
            if (!in_array($matchType, ['EXACT', 'PHRASE', 'BROAD'])) {
                $matchType = 'PHRASE';
            }

            $level = $kw['suggested_level'] ?? 'campaign';

            if ($level === 'ad_group' && !empty($kw['suggested_ad_group_resource'])) {
                $adGroupOps[] = [
                    'create' => [
                        'adGroup' => $kw['suggested_ad_group_resource'],
                        'negative' => true,
                        'keyword' => [
                            'text' => $kw['keyword'],
                            'matchType' => $matchType,
                        ],
                    ],
                ];
            } else {
                // Campagna: usa resource suggerita o default
                $campaignResource = $kw['suggested_campaign_resource'] ?? $defaultCampaignResource;
                if (!empty($campaignResource)) {
                    $campaignOps[] = [
                        'create' => [
                            'campaign' => $campaignResource,
                            'negative' => true,
                            'keyword' => [
                                'text' => $kw['keyword'],
                                'matchType' => $matchType,
                            ],
                        ],
                    ];
                }
            }
        }

        $appliedCount = 0;

        if (!empty($campaignOps)) {
            $gads->mutateCampaignCriteria($campaignOps);
            $appliedCount += count($campaignOps);
        }

        Database::reconnect();

        if (!empty($adGroupOps)) {
            $gads->mutateAdGroupCriteria($adGroupOps);
            $appliedCount += count($adGroupOps);
        }

        Database::reconnect();

        NegativeKeyword::markAsApplied($keywordIds);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'applied' => $appliedCount,
            'campaign_level' => count($campaignOps),
            'ad_group_level' => count($adGroupOps),
            'message' => $appliedCount . ' negative keywords applicate su Google Ads (' . count($campaignOps) . ' a livello campagna, ' . count($adGroupOps) . ' a livello ad group)',
        ]);
        exit;

    } catch (\Exception $e) {
        Database::reconnect();
        Logger::channel('api')->error('applyNegativeKeywords error', [
            'project_id' => $projectId,
            'error' => $e->getMessage(),
        ]);

        ob_end_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Errore Google Ads API: ' . $e->getMessage()]);
        exit;
    }
}

private function getDefaultCampaignResource(int $projectId, string $customerId): string
{
    $campaign = Database::fetch(
        "SELECT campaign_id_google FROM ga_campaigns WHERE project_id = ? AND campaign_id_google IS NOT NULL ORDER BY id DESC LIMIT 1",
        [$projectId]
    );
    if ($campaign && !empty($campaign['campaign_id_google'])) {
        return "customers/{$customerId}/campaigns/{$campaign['campaign_id_google']}";
    }
    return '';
}
```

**Step 2: Verificare e commit**

```bash
php -l modules/ads-analyzer/controllers/SearchTermAnalysisController.php
git add modules/ads-analyzer/controllers/SearchTermAnalysisController.php
git commit -m "feat(ads-analyzer): apply negatives by AI-suggested level (campaign/ad_group)"
```

---

### Task 6: Aggiornare index() e getResults() — Dati per la nuova UI

**Files:**
- Modify: `modules/ads-analyzer/controllers/SearchTermAnalysisController.php`

**Step 1: Aggiornare index() per passare i dati necessari alla nuova UI**

La nuova UI ha bisogno di:
- Ultima sync (non un selettore)
- Ultima analisi completata con risultati inline
- Storico negative applicate
- Confronto con analisi precedente

```php
public function index(int $projectId): string
{
    $ctx = $this->requireCampaignProject($projectId);
    $user = $ctx['user'];
    $project = $ctx['project'];

    // Sync completate con search terms
    $syncs = Sync::getCompletedSyncs($projectId);
    $searchTermSyncs = array_values(array_filter($syncs, fn($s) =>
        (int)($s['search_terms_synced'] ?? 0) > 0
    ));

    $selectedSync = !empty($searchTermSyncs) ? $searchTermSyncs[0] : null;

    // Stats per la sync corrente
    $searchTermStats = [];
    if ($selectedSync) {
        $searchTermStats = SearchTerm::getStatsByRun($selectedSync['id']);
    }

    // Ultima analisi completata
    $analyses = Analysis::getCompletedByProjectWithSync($projectId);
    $latestAnalysis = !empty($analyses) ? $analyses[0] : null;

    // Storico negative applicate
    $appliedCount = 0;
    $lastAppliedDate = null;
    if ($latestAnalysis) {
        $appliedKeywords = Database::fetchAll(
            "SELECT COUNT(*) as cnt, MAX(applied_at) as last_applied FROM ga_negative_keywords WHERE project_id = ? AND applied_at IS NOT NULL",
            [$projectId]
        );
        if (!empty($appliedKeywords[0])) {
            $appliedCount = (int)$appliedKeywords[0]['cnt'];
            $lastAppliedDate = $appliedKeywords[0]['last_applied'];
        }
    }

    // Contesti business salvati
    $savedContexts = BusinessContext::getByUser($user['id']);

    return View::render('ads-analyzer/campaigns/search-terms', [
        'title' => 'Keyword Negative - ' . $project['name'],
        'user' => $user,
        'modules' => ModuleLoader::getUserModules($user['id']),
        'project' => $project,
        'searchTermSyncs' => $searchTermSyncs,
        'selectedSync' => $selectedSync,
        'searchTermStats' => $searchTermStats,
        'analyses' => $analyses,
        'latestAnalysis' => $latestAnalysis,
        'savedContexts' => $savedContexts,
        'appliedCount' => $appliedCount,
        'lastAppliedDate' => $lastAppliedDate,
        'currentPage' => 'search-term-analysis',
        'userCredits' => Credits::getBalance($user['id']),
        'access_role' => $project['access_role'] ?? 'owner',
    ]);
}
```

**Step 2: Aggiornare getResults() per includere suggested_level e risorse**

Nel loop dove si costruiscono i dati keyword (linea 540), aggiungere i nuovi campi:

```php
'keywords' => array_map(fn($kw) => [
    'id' => $kw['id'],
    'keyword' => $kw['keyword'],
    'is_selected' => (bool)$kw['is_selected'],
    'suggested_match_type' => $kw['suggested_match_type'] ?? 'phrase',
    'suggested_level' => $kw['suggested_level'] ?? 'campaign',
    'applied_at' => $kw['applied_at'] ?? null,
], $keywords),
```

**Step 3: Verificare e commit**

```bash
php -l modules/ads-analyzer/controllers/SearchTermAnalysisController.php
git add modules/ads-analyzer/controllers/SearchTermAnalysisController.php
git commit -m "feat(ads-analyzer): update index() and getResults() for new UI data needs"
```

---

### Task 7: Riscrivere la view — Nuova UX "Ciclo di Pulizia"

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/search-terms.php` (riscrittura completa)

Questa è la task più grande. La view viene riscritta completamente con il nuovo flusso.

**Step 1: Scrivere la struttura Alpine.js**

La view ha un unico componente Alpine `searchTermAnalysis(config)` con stati:
- `state`: 'empty' | 'ready' | 'analyzing' | 'results' | 'applied'
- `currentAnalysis`: oggetto con risultati
- `categories`: array di categorie con keyword
- `comparison`: oggetto confronto
- `selectedCount`: conteggio keyword selezionate

**Step 2: Scrivere la view completa**

La view deve seguire il design document (header KPI, CTA analisi, risultati con categorie accordion, barra azioni sticky, modale conferma, storico accordion).

**Layout**:
```
[Header con KPI: termini totali | spreco | negative applicate | ultima sync]
[Se no analisi: CTA grande "Analizza con AI" + campo contesto opzionale]
[Se analisi: Riepilogo + confronto + categorie accordion + barra sticky]
[Storico analisi precedenti]
```

**CSS**: seguire Golden Rule #20 (rounded-xl, px-4 py-3, dark:bg-slate-700/50).

**Icone**: solo Heroicons SVG.

**UI**: tutto in italiano.

La view completa sarà ~600-700 righe. Non la scrivo per intero nel piano — il codice effettivo andrà prodotto durante l'esecuzione seguendo il design document e i pattern CSS/Alpine del progetto.

**Punti chiave della nuova view:**

1. **Header KPI** — 4 card stat (termini, spreco €, negative applicate, ultima sync)
2. **Selettore sync secondario** — dropdown discreto, non prominente
3. **CTA analisi** — bottone grande con contesto opzionale collapsato
4. **Risultati analisi** — riepilogo AI + categorie accordion
5. **Ogni categoria** — nome, priorità badge, livello badge (campagna/ad group), checkbox toggle
6. **Ogni keyword** — checkbox, testo, match type badge, livello badge, impressioni, costo
7. **Confronto** — banner con risolte/ricorrenti/nuove (se analisi precedente esiste)
8. **Barra sticky** — conteggio selezionate + "Applica su Google Ads" + "Esporta CSV"
9. **Modale conferma** — riepilogo per livello, warning, bottone conferma
10. **Post-applicazione** — banner verde + suggerimento attendere 2-3 giorni
11. **Storico** — accordion con analisi passate

**Step 3: Verificare PHP syntax e test browser**

```bash
php -l modules/ads-analyzer/views/campaigns/search-terms.php
```

Aprire `http://localhost/seo-toolkit/ads-analyzer/projects/15/search-term-analysis` e verificare:
- 0 errori console
- KPI visibili con dati reali
- CTA "Analizza" visibile
- Dark mode funzionante

**Step 4: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/search-terms.php
git commit -m "feat(ads-analyzer): rewrite keyword negative UI with iterative cleanup cycle"
```

---

### Task 8: Test end-to-end da browser

**Files:** Nessuna modifica

**Step 1: Verificare caricamento pagina**

Navigare a `http://localhost/seo-toolkit/ads-analyzer/projects/15/search-term-analysis`
- 0 errori console
- KPI mostrano dati reali (863 termini, €766.53 spreco)
- CTA "Analizza con AI" visibile

**Step 2: Testare analisi AI**

Cliccare "Analizza con AI" (con contesto opzionale vuoto — il contesto automatico dovrebbe bastare).
Verificare:
- Loading state visibile
- Risultati appaiono con categorie e keyword
- Ogni keyword mostra livello (campagna/ad group) e match type
- Checkbox funzionano (seleziona/deseleziona)
- Conteggio selezionate si aggiorna

**Step 3: Testare modale applicazione**

Cliccare "Applica su Google Ads":
- Modale appare con riepilogo per livello
- Warning visibile
- Bottone "Applica" abilitato
- **NON cliccare** il bottone di conferma (l'utente ha detto "senza modificare nulla nelle campagne live")
- Verificare solo che il modale si apra correttamente e mostri i dati giusti

**Step 4: Verificare export**

Cliccare "Esporta CSV" — deve scaricare un file CSV con le keyword selezionate.
Cliccare "Google Ads Editor" — deve scaricare un CSV compatibile.

**Step 5: Verificare storico e confronto**

Se esistono analisi precedenti, verificare che:
- Il confronto appaia (risolte/ricorrenti/nuove)
- Lo storico in accordion mostri le analisi passate

**Step 6: Commit finale se tutto OK**

```bash
git add -A
git commit -m "feat(ads-analyzer): complete keyword negative UX redesign with iterative cleanup cycle"
```

---

### Task 9: Fix warning x-collapse

**Files:**
- Modify: `shared/views/layout.php` (se x-collapse non è già incluso)

**Step 1: Verificare se Alpine Collapse plugin è incluso nel layout**

Cercare in `shared/views/layout.php` la riga che include Alpine.js. Aggiungere il plugin collapse se mancante:

```html
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

Il plugin collapse DEVE essere caricato PRIMA di Alpine.js.

**Step 2: Alternativa — Sostituire x-collapse con x-show + transition**

Se si preferisce evitare un plugin aggiuntivo, sostituire tutte le occorrenze di `x-collapse` con:

```html
x-show="condition"
x-transition:enter="transition ease-out duration-200"
x-transition:enter-start="opacity-0 -translate-y-1"
x-transition:enter-end="opacity-100 translate-y-0"
x-transition:leave="transition ease-in duration-150"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 -translate-y-1"
```

**Step 3: Commit**

```bash
git add shared/views/layout.php
git commit -m "fix(layout): add Alpine.js collapse plugin to remove console warnings"
```
