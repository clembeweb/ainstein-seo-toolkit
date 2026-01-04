# GOOGLE ADS KEYWORD NEGATIVE ANALYZER - Specifiche Tecniche

## Overview

Modulo per analisi automatica dei termini di ricerca Google Ads ed estrazione keyword negative tramite AI, con suddivisione per Ad Group e contesto business dinamico.

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `ads-analyzer` |
| **Prefisso DB** | `ga_` |
| **AI** | AiService centralizzato con prompt dinamico |
| **Crediti** | Per analisi Ad Group |

---

## ARCHITETTURA

```
modules/ads-analyzer/
├── module.json
├── routes.php
├── controllers/
│   ├── DashboardController.php
│   ├── ProjectController.php
│   ├── AnalysisController.php
│   └── ExportController.php
├── models/
│   ├── Project.php
│   ├── AdGroup.php
│   ├── SearchTerm.php
│   ├── NegativeKeyword.php
│   └── BusinessContext.php
├── services/
│   ├── CsvParserService.php
│   ├── KeywordAnalyzerService.php
│   └── NegativeExtractorService.php
├── views/
│   ├── dashboard/
│   │   └── index.php
│   ├── projects/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── show.php
│   ├── analysis/
│   │   ├── upload.php
│   │   ├── context.php
│   │   ├── results.php
│   │   └── category.php
│   └── export/
│       └── index.php
└── assets/
    └── js/
        └── ads-analyzer.js
```

---

## DATABASE SCHEMA

```sql
-- =============================================
-- PROGETTI E CONTESTI
-- =============================================

-- Progetti analisi
CREATE TABLE ga_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Contesto business
    business_context TEXT NOT NULL COMMENT 'Descrizione business per prompt AI',
    
    -- Statistiche aggregate
    total_terms INT DEFAULT 0,
    total_ad_groups INT DEFAULT 0,
    total_negatives_found INT DEFAULT 0,
    
    -- Stato
    status ENUM('draft', 'analyzing', 'completed', 'archived') DEFAULT 'draft',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contesti business salvati (riutilizzabili)
CREATE TABLE ga_saved_contexts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    context TEXT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- DATI IMPORTATI
-- =============================================

-- Ad Group importati
CREATE TABLE ga_ad_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    
    -- Statistiche
    terms_count INT DEFAULT 0,
    zero_ctr_count INT DEFAULT 0,
    wasted_impressions INT DEFAULT 0,
    
    -- Stato analisi
    analysis_status ENUM('pending', 'analyzing', 'completed', 'error') DEFAULT 'pending',
    analyzed_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_status (analysis_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Termini di ricerca importati
CREATE TABLE ga_search_terms (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    
    -- Dati CSV
    term VARCHAR(500) NOT NULL,
    match_type VARCHAR(50) NULL,
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    conversions INT DEFAULT 0,
    conversion_value DECIMAL(10,2) DEFAULT 0,
    
    -- Flag
    is_zero_ctr BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_group_id) REFERENCES ga_ad_groups(id) ON DELETE CASCADE,
    INDEX idx_project (project_id),
    INDEX idx_ad_group (ad_group_id),
    INDEX idx_zero_ctr (is_zero_ctr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- RISULTATI ANALISI
-- =============================================

-- Categorie negative trovate
CREATE TABLE ga_negative_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    
    -- Categoria
    category_key VARCHAR(100) NOT NULL COMMENT 'Es: CONCORRENTI, PRODOTTI_NON_OFFERTI',
    category_name VARCHAR(255) NOT NULL COMMENT 'Nome visualizzato',
    description TEXT NULL,
    
    -- Priorità
    priority ENUM('high', 'medium', 'evaluate') NOT NULL DEFAULT 'medium',
    
    -- Conteggi
    keywords_count INT DEFAULT 0,
    
    -- Ordine visualizzazione
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_group_id) REFERENCES ga_ad_groups(id) ON DELETE CASCADE,
    INDEX idx_ad_group (ad_group_id),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Keyword negative estratte
CREATE TABLE ga_negative_keywords (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    category_id INT NOT NULL,
    
    -- Keyword
    keyword VARCHAR(255) NOT NULL,
    
    -- Selezione utente
    is_selected BOOLEAN DEFAULT TRUE COMMENT 'Selezionata per export',
    
    -- Match type consigliato
    suggested_match_type ENUM('exact', 'phrase', 'broad') DEFAULT 'phrase',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_group_id) REFERENCES ga_ad_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES ga_negative_categories(id) ON DELETE CASCADE,
    INDEX idx_ad_group (ad_group_id),
    INDEX idx_category (category_id),
    INDEX idx_selected (is_selected)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- AI LOGS E CREDITI
-- =============================================

-- Log analisi AI (oltre al log centralizzato)
CREATE TABLE ga_analysis_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    ad_group_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Risultato
    status ENUM('success', 'error') NOT NULL,
    error_message TEXT NULL,
    
    -- Metriche
    terms_analyzed INT DEFAULT 0,
    categories_found INT DEFAULT 0,
    keywords_extracted INT DEFAULT 0,
    
    -- Crediti
    credits_used INT DEFAULT 0,
    
    -- Timing
    duration_ms INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES ga_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (ad_group_id) REFERENCES ga_ad_groups(id) ON DELETE CASCADE,
    INDEX idx_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## SISTEMA CREDITI

```php
const CREDIT_COSTS = [
    'analyze_ad_group' => 2,     // Per ogni Ad Group analizzato
    'bulk_analyze' => 1.5,       // Per Ad Group in analisi bulk (sconto)
    're_analyze' => 2,           // Ri-analisi singolo Ad Group
];

// Stima crediti prima dell'analisi
$adGroupCount = count($adGroups);
$estimatedCredits = $adGroupCount <= 3 
    ? $adGroupCount * 2 
    : $adGroupCount * 1.5;
```

---

## CSV PARSER SERVICE

```php
<?php
// services/CsvParserService.php

namespace Modules\AdsAnalyzer\Services;

class CsvParserService
{
    /**
     * Parse CSV export Google Ads (formato italiano)
     * 
     * Gestisce:
     * - Header con BOM UTF-8
     * - Separatore punto e virgola o virgola
     * - Numeri con virgola decimale italiana
     * - Righe totali da ignorare
     */
    public function parse(string $csvContent): array
    {
        $lines = explode("\n", $csvContent);
        $lines = array_filter($lines, fn($l) => trim($l) !== '');
        
        // Trova riga header
        $headerIdx = $this->findHeaderRow($lines);
        if ($headerIdx === -1) {
            throw new \Exception('Header CSV non trovato. Verifica formato export Google Ads.');
        }
        
        // Determina separatore
        $separator = strpos($lines[$headerIdx], ';') !== false ? ';' : ',';
        
        // Parse header
        $headers = str_getcsv($lines[$headerIdx], $separator);
        $headers = array_map('trim', $headers);
        $headers = array_map(fn($h) => $this->normalizeHeader($h), $headers);
        
        // Mappa colonne
        $colMap = $this->mapColumns($headers);
        
        // Parse dati
        $data = [];
        $adGroups = [];
        
        for ($i = $headerIdx + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            
            // Salta righe totali
            if ($this->isTotalRow($line)) continue;
            
            $row = str_getcsv($line, $separator);
            if (count($row) < 3) continue;
            
            $term = trim($row[$colMap['term']] ?? '');
            $adGroup = trim($row[$colMap['ad_group']] ?? 'Senza Gruppo');
            
            if (empty($term)) continue;
            
            // Parse numeri (formato italiano)
            $clicks = $this->parseNumber($row[$colMap['clicks']] ?? 0);
            $impressions = $this->parseNumber($row[$colMap['impressions']] ?? 0);
            $ctr = $this->parsePercent($row[$colMap['ctr']] ?? 0);
            $cost = $this->parseNumber($row[$colMap['cost']] ?? 0);
            $conversions = $this->parseNumber($row[$colMap['conversions']] ?? 0);
            
            // Raggruppa per Ad Group
            if (!isset($adGroups[$adGroup])) {
                $adGroups[$adGroup] = [
                    'name' => $adGroup,
                    'terms' => [],
                    'stats' => ['total' => 0, 'zero_ctr' => 0, 'wasted_imp' => 0]
                ];
            }
            
            $termData = [
                'term' => $term,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'cost' => $cost,
                'conversions' => $conversions,
                'is_zero_ctr' => $ctr == 0 && $impressions > 0
            ];
            
            $adGroups[$adGroup]['terms'][] = $termData;
            $adGroups[$adGroup]['stats']['total']++;
            
            if ($termData['is_zero_ctr']) {
                $adGroups[$adGroup]['stats']['zero_ctr']++;
                $adGroups[$adGroup]['stats']['wasted_imp'] += $impressions;
            }
        }
        
        return $adGroups;
    }
    
    private function findHeaderRow(array $lines): int
    {
        $keywords = ['termine di ricerca', 'search term', 'query'];
        
        foreach ($lines as $idx => $line) {
            $lower = strtolower($line);
            foreach ($keywords as $kw) {
                if (strpos($lower, $kw) !== false) {
                    return $idx;
                }
            }
        }
        
        return -1;
    }
    
    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^\w\s]/', '', $header);
        return $header;
    }
    
    private function mapColumns(array $headers): array
    {
        $map = [
            'term' => 0,
            'ad_group' => 1,
            'clicks' => 2,
            'impressions' => 3,
            'ctr' => 4,
            'cost' => 5,
            'conversions' => 6
        ];
        
        foreach ($headers as $idx => $h) {
            if (strpos($h, 'termine') !== false || strpos($h, 'search term') !== false) {
                $map['term'] = $idx;
            } elseif (strpos($h, 'gruppo') !== false || strpos($h, 'ad group') !== false) {
                $map['ad_group'] = $idx;
            } elseif ($h === 'clic' || $h === 'clicks') {
                $map['clicks'] = $idx;
            } elseif (strpos($h, 'impression') !== false) {
                $map['impressions'] = $idx;
            } elseif ($h === 'ctr') {
                $map['ctr'] = $idx;
            } elseif (strpos($h, 'costo') !== false || strpos($h, 'cost') !== false) {
                $map['cost'] = $idx;
            } elseif (strpos($h, 'conversion') !== false) {
                $map['conversions'] = $idx;
            }
        }
        
        return $map;
    }
    
    private function parseNumber($value): float
    {
        if (is_numeric($value)) return (float) $value;
        
        $value = str_replace(['€', '$', ' '], '', $value);
        $value = str_replace('.', '', $value);  // Migliaia
        $value = str_replace(',', '.', $value); // Decimali
        
        return (float) $value;
    }
    
    private function parsePercent($value): float
    {
        $value = str_replace(['%', ' '], '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value / 100;
    }
    
    private function isTotalRow(string $line): bool
    {
        $lower = strtolower($line);
        return strpos($lower, 'totale') !== false || strpos($lower, 'total') !== false;
    }
}
```

---

## KEYWORD ANALYZER SERVICE (AI)

```php
<?php
// services/KeywordAnalyzerService.php

namespace Modules\AdsAnalyzer\Services;

require_once __DIR__ . '/../../../services/AiService.php';

class KeywordAnalyzerService
{
    private \Services\AiService $aiService;
    
    public function __construct()
    {
        // OBBLIGATORIO: passa module_slug per logging
        $this->aiService = new \Services\AiService('ads-analyzer');
    }
    
    /**
     * Analizza termini di un Ad Group
     */
    public function analyzeAdGroup(
        int $userId,
        string $businessContext,
        array $terms,
        int $maxTerms = 300
    ): array {
        // Prepara termini per prompt (limita a maxTerms)
        $termsForPrompt = array_slice($terms, 0, $maxTerms);
        $termsSummary = array_map(
            fn($t) => "{$t['term']} | {$t['clicks']} clic | {$t['impressions']} imp",
            $termsForPrompt
        );
        $termsText = implode("\n", $termsSummary);
        
        // Costruisci prompt
        $prompt = $this->buildPrompt($businessContext, $termsText);
        
        // Chiama AI
        $response = $this->aiService->analyze(
            $userId,
            $prompt,
            '', // Content vuoto, tutto nel prompt
            'ads-analyzer'
        );
        
        if (isset($response['error'])) {
            throw new \Exception($response['message'] ?? 'Errore AI');
        }
        
        // Parse risposta JSON
        return $this->parseResponse($response['result']);
    }
    
    /**
     * Costruisce prompt dinamico basato su contesto business
     */
    private function buildPrompt(string $businessContext, string $terms): string
    {
        return <<<PROMPT
Sei un esperto Google Ads. Analizza i termini di ricerca e identifica keyword negative da escludere per questa campagna.

CONTESTO BUSINESS:
{$businessContext}

TERMINI DI RICERCA (formato: termine | click | impressioni):
{$terms}

ISTRUZIONI:
1. Analizza ATTENTAMENTE il contesto business per capire cosa vende/promuove il cliente
2. Identifica termini di ricerca NON PERTINENTI rispetto all'offerta
3. Raggruppa le keyword negative in categorie logiche per questo specifico business
4. Assegna priorità: "high" (escludi subito), "medium" (probabilmente da escludere), "evaluate" (valuta caso per caso)

Rispondi SOLO con un JSON valido (senza markdown, senza backtick, senza testo prima o dopo) con questa struttura:
{
  "stats": { 
    "total_terms": numero_termini_analizzati, 
    "zero_ctr_terms": numero_termini_con_ctr_zero, 
    "wasted_impressions": impressioni_sprecate_stimate 
  },
  "categories": {
    "NOME_CATEGORIA_1": { 
      "priority": "high|medium|evaluate", 
      "description": "Breve descrizione della categoria", 
      "keywords": ["keyword1", "keyword2", "keyword3"] 
    },
    "NOME_CATEGORIA_2": { ... }
  }
}

REGOLE PER LE CATEGORIE:
- Crea 5-12 categorie pertinenti al business (non usare categorie predefinite)
- Esempi categorie comuni: CONCORRENTI, PRODOTTI_NON_OFFERTI, INTENTO_INFORMATIVO, NAVIGAZIONALI, BRAND_ALTRI, LOCALITA, LINGUE_STRANIERE
- Adatta i nomi delle categorie al contesto specifico
- Estrai SOLO keyword singole o frasi brevi (max 3 parole), non i termini interi
- Identifica pattern ricorrenti nei termini non pertinenti
PROMPT;
    }
    
    /**
     * Parse risposta AI in JSON
     */
    private function parseResponse(string $text): array
    {
        // Rimuovi eventuali markdown
        $jsonStr = preg_replace('/```json\s*/i', '', $text);
        $jsonStr = preg_replace('/```\s*/', '', $jsonStr);
        
        // Estrai JSON
        $firstBrace = strpos($jsonStr, '{');
        $lastBrace = strrpos($jsonStr, '}');
        
        if ($firstBrace === false || $lastBrace === false) {
            throw new \Exception('Nessun JSON trovato nella risposta AI');
        }
        
        $jsonStr = substr($jsonStr, $firstBrace, $lastBrace - $firstBrace + 1);
        
        $result = json_decode($jsonStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON non valido: ' . json_last_error_msg());
        }
        
        return $result;
    }
}
```

---

## CONTROLLER PRINCIPALE

```php
<?php
// controllers/AnalysisController.php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Credits;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\SearchTerm;
use Modules\AdsAnalyzer\Models\NegativeCategory;
use Modules\AdsAnalyzer\Models\NegativeKeyword;
use Modules\AdsAnalyzer\Services\CsvParserService;
use Modules\AdsAnalyzer\Services\KeywordAnalyzerService;

class AnalysisController
{
    private CsvParserService $csvParser;
    private KeywordAnalyzerService $analyzer;
    
    public function __construct()
    {
        $this->csvParser = new CsvParserService();
        $this->analyzer = new KeywordAnalyzerService();
    }
    
    /**
     * Step 1: Upload CSV
     */
    public function upload(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            redirect('/ads-analyzer', 'Progetto non trovato', 'error');
        }
        
        View::render('ads-analyzer', 'analysis/upload', [
            'project' => $project,
            'pageTitle' => 'Carica CSV - ' . $project['name']
        ]);
    }
    
    /**
     * Step 1: Process CSV upload
     */
    public function processUpload(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'Errore upload file'], 400);
        }
        
        try {
            $csvContent = file_get_contents($_FILES['csv_file']['tmp_name']);
            $adGroups = $this->csvParser->parse($csvContent);
            
            if (empty($adGroups)) {
                jsonResponse(['error' => 'Nessun termine trovato nel CSV'], 400);
            }
            
            // Pulisci dati precedenti
            AdGroup::deleteByProject($projectId);
            
            // Salva Ad Group e termini
            $totalTerms = 0;
            foreach ($adGroups as $name => $data) {
                $adGroupId = AdGroup::create([
                    'project_id' => $projectId,
                    'name' => $name,
                    'terms_count' => $data['stats']['total'],
                    'zero_ctr_count' => $data['stats']['zero_ctr'],
                    'wasted_impressions' => $data['stats']['wasted_imp']
                ]);
                
                foreach ($data['terms'] as $term) {
                    SearchTerm::create([
                        'project_id' => $projectId,
                        'ad_group_id' => $adGroupId,
                        'term' => $term['term'],
                        'clicks' => $term['clicks'],
                        'impressions' => $term['impressions'],
                        'ctr' => $term['ctr'],
                        'cost' => $term['cost'],
                        'conversions' => $term['conversions'],
                        'is_zero_ctr' => $term['is_zero_ctr']
                    ]);
                }
                
                $totalTerms += $data['stats']['total'];
            }
            
            // Aggiorna progetto
            Project::update($projectId, [
                'total_terms' => $totalTerms,
                'total_ad_groups' => count($adGroups),
                'status' => 'draft'
            ]);
            
            jsonResponse([
                'success' => true,
                'ad_groups' => count($adGroups),
                'total_terms' => $totalTerms,
                'redirect' => url("/ads-analyzer/projects/{$projectId}/context")
            ]);
            
        } catch (\Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Step 2: Contesto business
     */
    public function context(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            redirect('/ads-analyzer', 'Progetto non trovato', 'error');
        }
        
        $adGroups = AdGroup::getByProject($projectId);
        $savedContexts = \Modules\AdsAnalyzer\Models\BusinessContext::getByUser($user['id']);
        
        // Stima crediti
        $adGroupCount = count($adGroups);
        $estimatedCredits = $adGroupCount <= 3 ? $adGroupCount * 2 : ceil($adGroupCount * 1.5);
        
        View::render('ads-analyzer', 'analysis/context', [
            'project' => $project,
            'adGroups' => $adGroups,
            'savedContexts' => $savedContexts,
            'estimatedCredits' => $estimatedCredits,
            'userCredits' => Credits::getBalance($user['id']),
            'pageTitle' => 'Contesto Business - ' . $project['name']
        ]);
    }
    
    /**
     * Step 3: Esegui analisi AI
     */
    public function analyze(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }
        
        $businessContext = $_POST['business_context'] ?? '';
        
        if (strlen($businessContext) < 30) {
            jsonResponse(['error' => 'Il contesto business deve essere almeno 30 caratteri'], 400);
        }
        
        // Salva contesto nel progetto
        Project::update($projectId, ['business_context' => $businessContext]);
        
        // Verifica crediti
        $adGroups = AdGroup::getByProject($projectId);
        $adGroupCount = count($adGroups);
        $creditsNeeded = $adGroupCount <= 3 ? $adGroupCount * 2 : ceil($adGroupCount * 1.5);
        
        if (!Credits::hasEnough($user['id'], $creditsNeeded)) {
            jsonResponse(['error' => "Crediti insufficienti. Necessari: {$creditsNeeded}"], 400);
        }
        
        // Avvia analisi
        Project::update($projectId, ['status' => 'analyzing']);
        
        $results = [];
        $totalNegatives = 0;
        $errors = [];
        
        foreach ($adGroups as $adGroup) {
            try {
                AdGroup::update($adGroup['id'], ['analysis_status' => 'analyzing']);
                
                // Prendi termini
                $terms = SearchTerm::getByAdGroup($adGroup['id']);
                
                // Analizza con AI
                $analysis = $this->analyzer->analyzeAdGroup(
                    $user['id'],
                    $businessContext,
                    $terms
                );
                
                // Salva categorie e keyword
                $this->saveAnalysisResults($projectId, $adGroup['id'], $analysis);
                
                $keywordsCount = 0;
                foreach ($analysis['categories'] ?? [] as $cat) {
                    $keywordsCount += count($cat['keywords'] ?? []);
                }
                $totalNegatives += $keywordsCount;
                
                AdGroup::update($adGroup['id'], [
                    'analysis_status' => 'completed',
                    'analyzed_at' => date('Y-m-d H:i:s')
                ]);
                
                $results[$adGroup['name']] = [
                    'success' => true,
                    'categories' => count($analysis['categories'] ?? []),
                    'keywords' => $keywordsCount
                ];
                
                // Consuma crediti
                $creditCost = $adGroupCount <= 3 ? 2 : 1.5;
                Credits::consume($user['id'], $creditCost, "Analisi Ad Group: {$adGroup['name']}", 'ads-analyzer');
                
            } catch (\Exception $e) {
                AdGroup::update($adGroup['id'], ['analysis_status' => 'error']);
                $errors[] = "{$adGroup['name']}: {$e->getMessage()}";
                $results[$adGroup['name']] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        // Aggiorna progetto
        Project::update($projectId, [
            'status' => empty($errors) ? 'completed' : 'completed',
            'total_negatives_found' => $totalNegatives
        ]);
        
        jsonResponse([
            'success' => true,
            'results' => $results,
            'total_negatives' => $totalNegatives,
            'errors' => $errors,
            'redirect' => url("/ads-analyzer/projects/{$projectId}/results")
        ]);
    }
    
    /**
     * Step 4: Risultati
     */
    public function results(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            redirect('/ads-analyzer', 'Progetto non trovato', 'error');
        }
        
        $adGroups = AdGroup::getByProject($projectId);
        
        // Carica categorie e keyword per ogni Ad Group
        $analysisData = [];
        foreach ($adGroups as $adGroup) {
            $categories = NegativeCategory::getByAdGroup($adGroup['id']);
            foreach ($categories as &$cat) {
                $cat['keywords'] = NegativeKeyword::getByCategory($cat['id']);
            }
            $analysisData[$adGroup['id']] = [
                'ad_group' => $adGroup,
                'categories' => $categories
            ];
        }
        
        View::render('ads-analyzer', 'analysis/results', [
            'project' => $project,
            'adGroups' => $adGroups,
            'analysisData' => $analysisData,
            'pageTitle' => 'Risultati - ' . $project['name']
        ]);
    }
    
    /**
     * Toggle selezione keyword
     */
    public function toggleKeyword(int $projectId, int $keywordId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
        }
        
        $keyword = NegativeKeyword::find($keywordId);
        if (!$keyword || $keyword['project_id'] != $projectId) {
            jsonResponse(['error' => 'Keyword non trovata'], 404);
        }
        
        $newValue = !$keyword['is_selected'];
        NegativeKeyword::update($keywordId, ['is_selected' => $newValue]);
        
        jsonResponse(['success' => true, 'is_selected' => $newValue]);
    }
    
    /**
     * Bulk toggle categoria
     */
    public function toggleCategory(int $projectId, int $categoryId, string $action): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            jsonResponse(['error' => 'Non autorizzato'], 403);
        }
        
        $newValue = match($action) {
            'select_all' => true,
            'deselect_all' => false,
            'invert' => null, // Gestito separatamente
            default => null
        };
        
        if ($action === 'invert') {
            NegativeKeyword::invertByCategory($categoryId);
        } else {
            NegativeKeyword::updateByCategory($categoryId, ['is_selected' => $newValue]);
        }
        
        jsonResponse(['success' => true]);
    }
    
    /**
     * Salva risultati analisi in DB
     */
    private function saveAnalysisResults(int $projectId, int $adGroupId, array $analysis): void
    {
        // Pulisci categorie precedenti
        NegativeCategory::deleteByAdGroup($adGroupId);
        
        $sortOrder = 0;
        foreach ($analysis['categories'] ?? [] as $key => $data) {
            $categoryId = NegativeCategory::create([
                'project_id' => $projectId,
                'ad_group_id' => $adGroupId,
                'category_key' => $key,
                'category_name' => $this->formatCategoryName($key),
                'description' => $data['description'] ?? '',
                'priority' => $data['priority'] ?? 'medium',
                'keywords_count' => count($data['keywords'] ?? []),
                'sort_order' => $sortOrder++
            ]);
            
            foreach ($data['keywords'] ?? [] as $keyword) {
                NegativeKeyword::create([
                    'project_id' => $projectId,
                    'ad_group_id' => $adGroupId,
                    'category_id' => $categoryId,
                    'keyword' => $keyword,
                    'is_selected' => $data['priority'] !== 'evaluate' // Auto-select high e medium
                ]);
            }
        }
    }
    
    private function formatCategoryName(string $key): string
    {
        return ucwords(str_replace('_', ' ', strtolower($key)));
    }
}
```

---

## EXPORT CONTROLLER

```php
<?php
// controllers/ExportController.php

namespace Modules\AdsAnalyzer\Controllers;

use Core\Auth;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;
use Modules\AdsAnalyzer\Models\NegativeKeyword;

class ExportController
{
    /**
     * Export keyword selezionate per Ad Group
     */
    public function exportAdGroup(int $projectId, int $adGroupId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        $adGroup = AdGroup::find($adGroupId);
        
        if (!$project || !$adGroup || $adGroup['project_id'] != $projectId) {
            http_response_code(404);
            exit('Non trovato');
        }
        
        $keywords = NegativeKeyword::getSelectedByAdGroup($adGroupId);
        
        $this->downloadCsv(
            $keywords,
            "negative-keywords-{$adGroup['name']}.csv"
        );
    }
    
    /**
     * Export tutte le keyword selezionate (tutti gli Ad Group)
     */
    public function exportAll(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            http_response_code(404);
            exit('Non trovato');
        }
        
        $keywords = NegativeKeyword::getSelectedByProject($projectId);
        
        $this->downloadCsv(
            $keywords,
            "negative-keywords-{$project['name']}.csv",
            true // Include colonna Ad Group
        );
    }
    
    /**
     * Export per Google Ads Editor (formato bulk)
     */
    public function exportGoogleAdsEditor(int $projectId): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            http_response_code(404);
            exit('Non trovato');
        }
        
        $keywords = NegativeKeyword::getSelectedByProjectWithAdGroup($projectId);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="google-ads-editor-import.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header Google Ads Editor
        fputcsv($output, ['Campaign', 'Ad Group', 'Keyword', 'Match Type', 'Status']);
        
        foreach ($keywords as $kw) {
            fputcsv($output, [
                '', // Campaign vuoto = usa Ad Group level
                $kw['ad_group_name'],
                $kw['keyword'],
                'Phrase', // Default phrase match
                'Enabled'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Copia in formato testo semplice (per incolla rapido)
     */
    public function copyText(int $projectId, ?int $adGroupId = null): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $projectId);
        
        if (!$project) {
            jsonResponse(['error' => 'Non trovato'], 404);
        }
        
        if ($adGroupId) {
            $keywords = NegativeKeyword::getSelectedByAdGroup($adGroupId);
        } else {
            $keywords = NegativeKeyword::getSelectedByProject($projectId);
        }
        
        $text = implode("\n", array_column($keywords, 'keyword'));
        
        jsonResponse(['success' => true, 'text' => $text, 'count' => count($keywords)]);
    }
    
    private function downloadCsv(array $keywords, string $filename, bool $includeAdGroup = false): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        $header = $includeAdGroup 
            ? ['Keyword', 'Ad Group', 'Categoria', 'Priorità']
            : ['Keyword', 'Categoria', 'Priorità'];
        fputcsv($output, $header);
        
        foreach ($keywords as $kw) {
            $row = $includeAdGroup
                ? [$kw['keyword'], $kw['ad_group_name'] ?? '', $kw['category_name'] ?? '', $kw['priority'] ?? '']
                : [$kw['keyword'], $kw['category_name'] ?? '', $kw['priority'] ?? ''];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
```

---

## ROUTES

```php
<?php
// routes.php

$router->group(['prefix' => '/ads-analyzer', 'middleware' => ['auth', 'module:ads-analyzer']], function($router) {
    
    // Dashboard modulo (lista progetti)
    $router->get('/', 'DashboardController@index');
    
    // =============================================
    // PROGETTI CRUD
    // =============================================
    $router->get('/projects', 'ProjectController@index');
    $router->get('/projects/create', 'ProjectController@create');
    $router->post('/projects/store', 'ProjectController@store');
    $router->get('/projects/{id}', 'ProjectController@show');
    $router->get('/projects/{id}/edit', 'ProjectController@edit');
    $router->post('/projects/{id}/update', 'ProjectController@update');
    $router->post('/projects/{id}/delete', 'ProjectController@destroy');
    $router->post('/projects/{id}/duplicate', 'ProjectController@duplicate');
    $router->post('/projects/{id}/toggle-archive', 'ProjectController@toggleArchive');
    
    // =============================================
    // ANALISI FLOW (per singolo progetto)
    // =============================================
    $router->get('/projects/{id}/upload', 'AnalysisController@upload');
    $router->post('/projects/{id}/upload', 'AnalysisController@processUpload');
    $router->get('/projects/{id}/context', 'AnalysisController@context');
    $router->post('/projects/{id}/analyze', 'AnalysisController@analyze');
    $router->get('/projects/{id}/results', 'AnalysisController@results');
    
    // Gestione selezioni keyword
    $router->post('/projects/{id}/keywords/{keywordId}/toggle', 'AnalysisController@toggleKeyword');
    $router->post('/projects/{id}/categories/{categoryId}/{action}', 'AnalysisController@toggleCategory');
    
    // =============================================
    // EXPORT
    // =============================================
    $router->get('/projects/{id}/export/csv', 'ExportController@exportAll');
    $router->get('/projects/{id}/export/ad-group/{adGroupId}', 'ExportController@exportAdGroup');
    $router->get('/projects/{id}/export/google-ads-editor', 'ExportController@exportGoogleAdsEditor');
    $router->post('/projects/{id}/copy-text', 'ExportController@copyText');
    $router->post('/projects/{id}/copy-text/{adGroupId}', 'ExportController@copyText');
    
    // =============================================
    // CONTESTI BUSINESS SALVATI
    // =============================================
    $router->get('/contexts', 'ContextController@index');
    $router->post('/contexts/save', 'ContextController@save');
    $router->post('/contexts/{id}/delete', 'ContextController@destroy');
    
    // =============================================
    // SETTINGS MODULO (per utente)
    // =============================================
    $router->get('/settings', 'SettingsController@index');
    $router->post('/settings', 'SettingsController@update');
});
```

---

## MODULE.JSON

```json
{
    "name": "Google Ads Analyzer",
    "slug": "ads-analyzer",
    "version": "1.0.0",
    "description": "Analisi termini di ricerca Google Ads ed estrazione keyword negative con AI",
    "icon": "chart-bar-square",
    "menu_order": 25,
    "requires": {
        "php": ">=8.0",
        "services": ["ai"]
    },
    "credits": {
        "analyze_ad_group": {
            "cost": 2,
            "description": "Analisi singolo Ad Group"
        },
        "bulk_analyze": {
            "cost": 1.5,
            "description": "Analisi Ad Group in bulk (4+ gruppi)"
        }
    },
    "settings": {
        "max_terms_per_analysis": {
            "type": "number",
            "label": "Termini max per analisi AI",
            "default": 300,
            "min": 50,
            "max": 500,
            "description": "Limite termini inviati all'AI per singola analisi (maggiore = più preciso ma più costoso)",
            "admin_only": false
        },
        "auto_select_high_priority": {
            "type": "checkbox",
            "label": "Auto-seleziona keyword priorità Alta",
            "default": true,
            "description": "Seleziona automaticamente le keyword con priorità 'high' per l'export",
            "admin_only": false
        },
        "auto_select_medium_priority": {
            "type": "checkbox",
            "label": "Auto-seleziona keyword priorità Media",
            "default": true,
            "description": "Seleziona automaticamente le keyword con priorità 'medium' per l'export",
            "admin_only": false
        },
        "default_match_type": {
            "type": "select",
            "label": "Match type predefinito export",
            "default": "phrase",
            "options": {
                "exact": "Corrispondenza esatta [keyword]",
                "phrase": "Corrispondenza a frase \"keyword\"",
                "broad": "Corrispondenza generica"
            },
            "description": "Match type usato nell'export per Google Ads Editor",
            "admin_only": false
        }
    },
    "admin_settings": {
        "credits_single_analysis": {
            "type": "number",
            "label": "Crediti per analisi singola",
            "default": 2,
            "min": 1,
            "max": 10,
            "description": "Crediti consumati per analizzare un singolo Ad Group"
        },
        "credits_bulk_analysis": {
            "type": "number",
            "label": "Crediti per analisi bulk",
            "default": 1.5,
            "min": 0.5,
            "max": 5,
            "description": "Crediti consumati per Ad Group in analisi bulk (4+ gruppi)"
        },
        "bulk_threshold": {
            "type": "number",
            "label": "Soglia per sconto bulk",
            "default": 4,
            "min": 2,
            "max": 10,
            "description": "Numero minimo Ad Group per applicare tariffa bulk"
        }
    },
    "routes_prefix": "/ads-analyzer"
}
```

---

## REGISTRAZIONE MODULO (Admin)

### Query INSERT per tabella modules

```sql
-- Registra il modulo nella piattaforma
INSERT INTO modules (
    name,
    slug,
    version,
    description,
    icon,
    menu_order,
    is_active,
    settings,
    created_at
) VALUES (
    'Google Ads Analyzer',
    'ads-analyzer',
    '1.0.0',
    'Analisi termini di ricerca Google Ads ed estrazione keyword negative con AI',
    'chart-bar-square',
    25,
    1,
    '{"max_terms_per_analysis":300,"auto_select_high_priority":true,"auto_select_medium_priority":true,"default_match_type":"phrase"}',
    NOW()
);

-- Registra i costi crediti
INSERT INTO module_credits (module_slug, action_key, cost, description) VALUES
('ads-analyzer', 'analyze_ad_group', 2.00, 'Analisi singolo Ad Group'),
('ads-analyzer', 'bulk_analyze', 1.50, 'Analisi Ad Group in bulk (4+ gruppi)');
```

### Verifica registrazione

```sql
-- Controlla che il modulo sia registrato
SELECT * FROM modules WHERE slug = 'ads-analyzer';

-- Controlla crediti configurati
SELECT * FROM module_credits WHERE module_slug = 'ads-analyzer';
```

---

## PAGINA SETTINGS MODULO (icona ⚙️)

Quando l'admin clicca sull'icona ⚙️ del modulo, mostra form con:

```
┌─────────────────────────────────────────────────────────────────┐
│ ⚙️ Impostazioni - Google Ads Analyzer                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  === IMPOSTAZIONI UTENTE ===                                   │
│                                                                 │
│  Termini max per analisi AI:                                   │
│  ┌─────────┐                                                   │
│  │   300   │  (50-500)                                         │
│  └─────────┘                                                   │
│  ℹ️ Limite termini inviati all'AI per singola analisi          │
│                                                                 │
│  ☑️ Auto-seleziona keyword priorità Alta                       │
│  ☑️ Auto-seleziona keyword priorità Media                      │
│                                                                 │
│  Match type predefinito export:                                │
│  ┌──────────────────────────────┐                              │
│  │ Corrispondenza a frase   ▼  │                              │
│  └──────────────────────────────┘                              │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  === IMPOSTAZIONI ADMIN (solo admin) ===                       │
│                                                                 │
│  Crediti per analisi singola:                                  │
│  ┌─────────┐                                                   │
│  │    2    │  crediti                                          │
│  └─────────┘                                                   │
│                                                                 │
│  Crediti per analisi bulk:                                     │
│  ┌─────────┐                                                   │
│  │   1.5   │  crediti                                          │
│  └─────────┘                                                   │
│                                                                 │
│  Soglia per sconto bulk:                                       │
│  ┌─────────┐                                                   │
│  │    4    │  Ad Group minimi                                  │
│  └─────────┘                                                   │
│                                                                 │
│                                    [Annulla] [Salva Impostazioni]│
└─────────────────────────────────────────────────────────────────┘
```

---

## UI - VISTE PRINCIPALI

### Upload CSV (views/analysis/upload.php)

```
┌─────────────────────────────────────────────────────────────────┐
│ [← Progetti]  Nome Progetto                                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📁 CARICA CSV TERMINI DI RICERCA                              │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                                                         │   │
│  │     Trascina qui il file CSV                           │   │
│  │     oppure clicca per selezionare                      │   │
│  │                                                         │   │
│  │     Formati supportati: CSV export Google Ads          │   │
│  │                                                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ℹ️ Come esportare da Google Ads:                              │
│  1. Vai su Report → Termini di ricerca                         │
│  2. Seleziona periodo e filtri                                 │
│  3. Clicca Scarica → CSV                                       │
│                                                                 │
│                                         [Carica e Continua →]  │
└─────────────────────────────────────────────────────────────────┘
```

### Contesto Business (views/analysis/context.php)

```
┌─────────────────────────────────────────────────────────────────┐
│ [← Upload]  Nome Progetto                        Step 2 di 3   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📊 RIEPILOGO IMPORT                                           │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                      │
│  │   3      │  │  4.521   │  │  2.847   │                      │
│  │ Ad Group │  │ Termini  │  │ CTR 0%   │                      │
│  └──────────┘  └──────────┘  └──────────┘                      │
│                                                                 │
│  Ad Group trovati:                                              │
│  • Pegaso (2.886 termini)                                      │
│  • San Raffaele (606 termini)                                  │
│  • Mercatorum (507 termini)                                    │
│                                                                 │
│  ────────────────────────────────────────────────────────────  │
│                                                                 │
│  📋 CONTESTO BUSINESS *                                        │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Descrivi cosa vendi/promuovi, il target, cosa NON      │   │
│  │ offri. Più dettagli dai, migliore sarà l'analisi.     │   │
│  │                                                         │   │
│  │ Esempio: E-commerce scarpe running. Vendiamo solo      │   │
│  │ scarpe da corsa uomo/donna. NON vendiamo: scarpe da    │   │
│  │ calcio, abbigliamento, accessori...                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  💾 Contesti salvati: [▼ Seleziona...] [Carica] [Salva Nuovo]  │
│                                                                 │
│  ────────────────────────────────────────────────────────────  │
│                                                                 │
│  💰 Crediti stimati: 6 (3 Ad Group × 2)                        │
│  Crediti disponibili: 150                                      │
│                                                                 │
│                                         [🚀 Avvia Analisi AI]  │
└─────────────────────────────────────────────────────────────────┘
```

### Risultati (views/analysis/results.php)

```
┌─────────────────────────────────────────────────────────────────┐
│ [← Progetti]  Nome Progetto                   [💾 Esporta ▼]   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📊 STATISTICHE GLOBALI                                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │   156    │  │   4.521  │  │   2.847  │  │  12.450  │       │
│  │ Negative │  │ Termini  │  │ CTR 0%   │  │ Imp.Sprec│       │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘       │
│                                                                 │
│  [Pegaso (68)] [San Raffaele (45)] [Mercatorum (43)]  ← Tabs   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ CONCORRENTI                           🔴 Alta  (12/15) │   │
│  │ Università e brand concorrenti                          │   │
│  │ [Tutti] [Nessuno] [Inverti]                            │   │
│  │ ──────────────────────────────────────────────────────  │   │
│  │ ☑ ecampus            ☑ unimarconi                      │   │
│  │ ☑ unicusano          ☑ unifortunato                    │   │
│  │ ☑ uninettuno         ☐ cepu (valutare)                 │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ CORSI_NON_OFFERTI                     🔴 Alta  (8/8)   │   │
│  │ Corsi non disponibili online                            │   │
│  │ [Tutti] [Nessuno] [Inverti]                            │   │
│  │ ──────────────────────────────────────────────────────  │   │
│  │ ☑ medicina           ☑ infermieristica                 │   │
│  │ ☑ veterinaria        ☑ odontoiatria                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ... altre categorie ...                                       │
│                                                                 │
│  ────────────────────────────────────────────────────────────  │
│                                                                 │
│  [📋 Copia KW Pegaso (52)]  [📋 Copia TUTTE (135)]            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## FLOW OPERATIVO

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   UPLOAD    │ ──▶ │  CONTESTO   │ ──▶ │  ANALISI    │ ──▶ │  RISULTATI  │
│    CSV      │     │  BUSINESS   │     │     AI      │     │  + EXPORT   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │                   │
       ▼                   ▼                   ▼                   ▼
  Parse CSV          Validazione         Per ogni            Selezione
  Raggruppa per      contesto ≥30        Ad Group:           checkbox
  Ad Group           caratteri           - Chiama AI         Export CSV
  Salva in DB        Stima crediti       - Parse JSON        Google Ads
                                         - Salva risultati    Editor
```

---

## CHECKLIST IMPLEMENTAZIONE

### Fase 1 - Setup Base (~20 min)
- [ ] Struttura cartelle modulo
- [ ] module.json
- [ ] routes.php
- [ ] Tabelle database (esegui SQL)

### Fase 2 - Models (~15 min)
- [ ] Project.php
- [ ] AdGroup.php
- [ ] SearchTerm.php
- [ ] NegativeCategory.php
- [ ] NegativeKeyword.php
- [ ] BusinessContext.php

### Fase 3 - Services (~25 min)
- [ ] CsvParserService.php
- [ ] KeywordAnalyzerService.php (con AiService)

### Fase 4 - Controllers (~30 min)
- [ ] DashboardController.php
- [ ] ProjectController.php (CRUD)
- [ ] AnalysisController.php (flow principale)
- [ ] ExportController.php

### Fase 5 - Views (~40 min)
- [ ] dashboard/index.php
- [ ] projects/index.php, create.php, show.php
- [ ] analysis/upload.php
- [ ] analysis/context.php
- [ ] analysis/results.php (con checkbox Alpine.js)

### Fase 6 - Test (~15 min)
- [ ] Upload CSV vari formati
- [ ] Analisi con AI
- [ ] Selezione/deselezione keyword
- [ ] Export CSV e Google Ads Editor
- [ ] Test crediti

---

## NOTE IMPLEMENTAZIONE

1. **AiService obbligatorio**: Mai curl diretto, sempre `new AiService('ads-analyzer')`
2. **Heroicons**: Usare SVG inline, mai Lucide
3. **UI italiano**: Tutti i testi in italiano
4. **Crediti**: Verifica disponibilità PRIMA di avviare analisi
5. **CSV parsing robusto**: Gestire formato italiano (virgola decimale, punto e virgola separatore)
6. **JSON parsing AI**: Gestire markdown e testo extra nella risposta
7. **Checkbox con Alpine.js**: Gestire stato selezione client-side + sync server
8. **Export multipli**: CSV semplice, per Ad Group, formato Google Ads Editor

---

## INTEGRAZIONE AI LOGS

Tutte le chiamate AI del modulo vengono loggate automaticamente nella sezione **Admin → AI Logs**.

### Badge Modulo

Il modulo appare con badge **`ads-analyzer`** (colore: `bg-amber-100 text-amber-800`).

```php
// Nel dropdown filtro "Modulo" apparirà:
// - Tutti
// - ai-content
// - internal-links
// - seo-audit
// - seo-tracking
// - ads-analyzer  ← NUOVO
```

### Struttura Log Entry

Ogni chiamata AI genera una riga con:

| Campo | Valore Esempio |
|-------|----------------|
| DATA/ORA | 02/01/2026 16:19:42 |
| MODULO | `ads-analyzer` (badge amber) |
| PROVIDER | Anthropic |
| MODELLO | claude-sonnet-4-202... |
| TOKENS | 3,450 |
| DURATA | 8,234ms |
| COSTO | $0.0287 |
| STATUS | Success / Error |
| AZIONI | 👁 (visualizza dettagli) |

### Payload Loggato (tabella ai_logs)

```sql
-- Struttura record in ai_logs
INSERT INTO ai_logs (
    user_id,
    module_slug,
    provider,
    model,
    
    -- Payload Request
    request_payload,
    
    -- Payload Response  
    response_payload,
    
    -- Metriche
    input_tokens,
    output_tokens,
    total_tokens,
    duration_ms,
    cost_usd,
    
    -- Stato
    status,
    error_message,
    
    created_at
) VALUES (
    1,
    'ads-analyzer',
    'anthropic',
    'claude-sonnet-4-20250514',
    
    -- Request (JSON)
    '{
        "action": "analyze_ad_group",
        "ad_group_id": 123,
        "ad_group_name": "Pegaso",
        "project_id": 45,
        "terms_count": 286,
        "business_context": "Promuoviamo corsi universitari online...",
        "prompt_preview": "Sei un esperto Google Ads. Analizza i termini..."
    }',
    
    -- Response (JSON)
    '{
        "stats": {
            "total_terms": 286,
            "zero_ctr_terms": 180,
            "wasted_impressions": 4520
        },
        "categories_count": 8,
        "keywords_extracted": 67,
        "categories": ["CONCORRENTI", "CORSI_NON_OFFERTI", "INTENTO_INFORMATIVO", ...]
    }',
    
    1245,      -- input_tokens
    2205,      -- output_tokens
    3450,      -- total_tokens
    8234,      -- duration_ms
    0.0287,    -- cost_usd
    'success',
    NULL,
    NOW()
);
```

### Dettaglio Log (click su 👁)

Quando admin clicca sull'icona 👁, mostra modal con:

```
┌─────────────────────────────────────────────────────────────────┐
│ Dettaglio Chiamata AI                                    [✕]   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  INFORMAZIONI GENERALI                                         │
│  ─────────────────────────────────────────────────────────────  │
│  Modulo:     ads-analyzer                                      │
│  Azione:     analyze_ad_group                                  │
│  Provider:   Anthropic                                         │
│  Modello:    claude-sonnet-4-20250514                          │
│  Data/Ora:   02/01/2026 16:19:42                               │
│  Durata:     8.234s                                            │
│  Status:     ✅ Success                                        │
│                                                                 │
│  METRICHE TOKEN                                                │
│  ─────────────────────────────────────────────────────────────  │
│  Input:      1,245 tokens                                      │
│  Output:     2,205 tokens                                      │
│  Totale:     3,450 tokens                                      │
│  Costo:      $0.0287                                           │
│                                                                 │
│  REQUEST PAYLOAD                                               │
│  ─────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ {                                                       │   │
│  │   "action": "analyze_ad_group",                        │   │
│  │   "ad_group_id": 123,                                  │   │
│  │   "ad_group_name": "Pegaso",                           │   │
│  │   "project_id": 45,                                    │   │
│  │   "terms_count": 286,                                  │   │
│  │   "business_context": "Promuoviamo corsi...",         │   │
│  │   "prompt_preview": "Sei un esperto Google Ads..."    │   │
│  │ }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  RESPONSE PAYLOAD                                              │
│  ─────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ {                                                       │   │
│  │   "stats": {                                           │   │
│  │     "total_terms": 286,                                │   │
│  │     "zero_ctr_terms": 180,                             │   │
│  │     "wasted_impressions": 4520                         │   │
│  │   },                                                    │   │
│  │   "categories_count": 8,                               │   │
│  │   "keywords_extracted": 67,                            │   │
│  │   "categories": [                                      │   │
│  │     "CONCORRENTI",                                     │   │
│  │     "CORSI_NON_OFFERTI",                               │   │
│  │     "INTENTO_INFORMATIVO",                             │   │
│  │     ...                                                │   │
│  │   ]                                                     │   │
│  │ }                                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│                                              [Chiudi] [📋 Copia]│
└─────────────────────────────────────────────────────────────────┘
```

### Implementazione in KeywordAnalyzerService

```php
<?php
// services/KeywordAnalyzerService.php

class KeywordAnalyzerService
{
    private \Services\AiService $aiService;
    
    public function __construct()
    {
        // OBBLIGATORIO: passa module_slug per logging automatico
        $this->aiService = new \Services\AiService('ads-analyzer');
    }
    
    public function analyzeAdGroup(
        int $userId,
        int $projectId,
        int $adGroupId,
        string $adGroupName,
        string $businessContext,
        array $terms
    ): array {
        
        // Prepara prompt
        $prompt = $this->buildPrompt($businessContext, $terms);
        
        // Prepara metadata per logging
        $requestMeta = [
            'action' => 'analyze_ad_group',
            'ad_group_id' => $adGroupId,
            'ad_group_name' => $adGroupName,
            'project_id' => $projectId,
            'terms_count' => count($terms),
            'business_context' => mb_substr($businessContext, 0, 200) . '...',
            'prompt_preview' => mb_substr($prompt, 0, 100) . '...'
        ];
        
        // Chiama AI con logging automatico
        $response = $this->aiService->complete([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ], $userId, $requestMeta);
        
        // Parse e prepara response meta per log
        $result = $this->parseResponse($response['content'][0]['text']);
        
        $responseMeta = [
            'stats' => $result['stats'] ?? [],
            'categories_count' => count($result['categories'] ?? []),
            'keywords_extracted' => $this->countKeywords($result['categories'] ?? []),
            'categories' => array_keys($result['categories'] ?? [])
        ];
        
        // Aggiorna log con response meta
        $this->aiService->updateLogResponse($responseMeta);
        
        return $result;
    }
    
    private function countKeywords(array $categories): int
    {
        $count = 0;
        foreach ($categories as $cat) {
            $count += count($cat['keywords'] ?? []);
        }
        return $count;
    }
}
```

---

## SIDEBAR INTEGRATION

### Icona Menu (Heroicons - chart-bar-square)

```html
<!-- SVG Heroicons chart-bar-square -->
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
</svg>
```

### Posizione nel Menu

```
MODULI
├── AI SEO Content Generator
├── Internal Links Analyzer  
├── SEO Audit
├── SEO Position Tracking
└── Google Ads Analyzer        ← menu_order: 25

AMMINISTRAZIONE
├── Overview
├── Utenti
├── Piani
├── Moduli
├── Impostazioni
└── AI Logs
```

### Codice Sidebar Entry

```php
// shared/views/sidebar.php - il modulo viene caricato automaticamente da ModuleLoader
// basandosi su module.json

// Verifica che il modulo sia attivo
if ($module['is_active'] && $module['slug'] === 'ads-analyzer') {
    echo '<a href="' . url('/ads-analyzer') . '" class="nav-link">';
    echo '<svg>...</svg>'; // chart-bar-square
    echo '<span>Google Ads Analyzer</span>';
    echo '</a>';
}
```

---

## MIDDLEWARE E SICUREZZA

### Routes con Middleware Auth

```php
<?php
// routes.php

// Tutte le routes richiedono autenticazione
$router->group(['prefix' => '/ads-analyzer', 'middleware' => ['auth', 'module:ads-analyzer']], function($router) {
    
    // Dashboard modulo
    $router->get('/', 'DashboardController@index');
    
    // ... resto delle routes ...
});
```

### Middleware module:ads-analyzer

```php
<?php
// core/Middleware/ModuleMiddleware.php

class ModuleMiddleware
{
    public function handle(string $moduleSlug): bool
    {
        // Verifica che il modulo sia attivo
        $module = Module::findBySlug($moduleSlug);
        
        if (!$module || !$module['is_active']) {
            $_SESSION['flash_error'] = 'Modulo non disponibile';
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        return true;
    }
}
```

### CSRF Protection su Form POST

```php
// Tutti i form POST devono includere token CSRF
<form method="POST" action="...">
    <?= csrf_field() ?>
    <!-- campi form -->
</form>

// Nel controller, validare:
public function store(): void
{
    // Verifica CSRF (automatico se usi il middleware)
    if (!verify_csrf_token($_POST['_token'] ?? '')) {
        abort(403, 'Token CSRF non valido');
    }
    
    // ... logica ...
}
```

---

## MODELS BASE (Codice PHP)

### Project.php

```php
<?php
// models/Project.php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class Project
{
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO ga_projects (user_id, name, description, business_context, status)
            VALUES (:user_id, :name, :description, :business_context, :status)
        ");
        
        $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'business_context' => $data['business_context'] ?? '',
            'status' => $data['status'] ?? 'draft'
        ]);
        
        return $db->lastInsertId();
    }
    
    public static function findByUserAndId(int $userId, int $id): ?array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM ga_projects 
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    public static function getAllByUser(int $userId): array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM ga_projects 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "UPDATE ga_projects SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return $db->prepare($sql)->execute($params);
    }
    
    public static function delete(int $id): bool
    {
        $db = Database::getInstance();
        return $db->prepare("DELETE FROM ga_projects WHERE id = :id")->execute(['id' => $id]);
    }
}
```

### AdGroup.php

```php
<?php
// models/AdGroup.php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class AdGroup
{
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO ga_ad_groups (project_id, name, terms_count, zero_ctr_count, wasted_impressions)
            VALUES (:project_id, :name, :terms_count, :zero_ctr_count, :wasted_impressions)
        ");
        
        $stmt->execute([
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'terms_count' => $data['terms_count'] ?? 0,
            'zero_ctr_count' => $data['zero_ctr_count'] ?? 0,
            'wasted_impressions' => $data['wasted_impressions'] ?? 0
        ]);
        
        return $db->lastInsertId();
    }
    
    public static function getByProject(int $projectId): array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM ga_ad_groups 
            WHERE project_id = :project_id 
            ORDER BY name ASC
        ");
        
        $stmt->execute(['project_id' => $projectId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function find(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM ga_ad_groups WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    
    public static function update(int $id, array $data): bool
    {
        $db = Database::getInstance();
        
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "UPDATE ga_ad_groups SET " . implode(', ', $fields) . " WHERE id = :id";
        
        return $db->prepare($sql)->execute($params);
    }
    
    public static function deleteByProject(int $projectId): bool
    {
        $db = Database::getInstance();
        return $db->prepare("DELETE FROM ga_ad_groups WHERE project_id = :project_id")
            ->execute(['project_id' => $projectId]);
    }
}
```

### NegativeKeyword.php

```php
<?php
// models/NegativeKeyword.php

namespace Modules\AdsAnalyzer\Models;

use Core\Database;

class NegativeKeyword
{
    public static function create(array $data): int
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            INSERT INTO ga_negative_keywords 
            (project_id, ad_group_id, category_id, keyword, is_selected, suggested_match_type)
            VALUES (:project_id, :ad_group_id, :category_id, :keyword, :is_selected, :suggested_match_type)
        ");
        
        $stmt->execute([
            'project_id' => $data['project_id'],
            'ad_group_id' => $data['ad_group_id'],
            'category_id' => $data['category_id'],
            'keyword' => $data['keyword'],
            'is_selected' => $data['is_selected'] ?? true,
            'suggested_match_type' => $data['suggested_match_type'] ?? 'phrase'
        ]);
        
        return $db->lastInsertId();
    }
    
    public static function getByCategory(int $categoryId): array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT * FROM ga_negative_keywords 
            WHERE category_id = :category_id 
            ORDER BY keyword ASC
        ");
        
        $stmt->execute(['category_id' => $categoryId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function getSelectedByAdGroup(int $adGroupId): array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT nk.*, nc.category_name, nc.priority
            FROM ga_negative_keywords nk
            JOIN ga_negative_categories nc ON nk.category_id = nc.id
            WHERE nk.ad_group_id = :ad_group_id AND nk.is_selected = 1
            ORDER BY nc.priority DESC, nk.keyword ASC
        ");
        
        $stmt->execute(['ad_group_id' => $adGroupId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function getSelectedByProject(int $projectId): array
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("
            SELECT nk.*, nc.category_name, nc.priority, ag.name as ad_group_name
            FROM ga_negative_keywords nk
            JOIN ga_negative_categories nc ON nk.category_id = nc.id
            JOIN ga_ad_groups ag ON nk.ad_group_id = ag.id
            WHERE nk.project_id = :project_id AND nk.is_selected = 1
            ORDER BY ag.name, nc.priority DESC, nk.keyword ASC
        ");
        
        $stmt->execute(['project_id' => $projectId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public static function toggleSelection(int $id): bool
    {
        $db = Database::getInstance();
        
        return $db->prepare("
            UPDATE ga_negative_keywords 
            SET is_selected = NOT is_selected 
            WHERE id = :id
        ")->execute(['id' => $id]);
    }
    
    public static function updateByCategory(int $categoryId, array $data): bool
    {
        $db = Database::getInstance();
        
        $fields = [];
        $params = ['category_id' => $categoryId];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "UPDATE ga_negative_keywords SET " . implode(', ', $fields) . " WHERE category_id = :category_id";
        
        return $db->prepare($sql)->execute($params);
    }
}
```

---

## ERROR HANDLING

### Gestione Errori AI

```php
<?php
// Nel controller AnalysisController.php

public function analyze(int $projectId): void
{
    try {
        // ... codice analisi ...
        
        $result = $this->analyzer->analyzeAdGroup(/* params */);
        
        if (isset($result['error'])) {
            throw new \Exception($result['error']);
        }
        
    } catch (\Services\AiException $e) {
        // Errore specifico AI (rate limit, token exceeded, etc.)
        $this->logError($projectId, 'ai_error', $e->getMessage());
        
        jsonResponse([
            'error' => 'Errore durante l\'analisi AI',
            'details' => $e->getMessage(),
            'retry' => $e->isRetryable()
        ], 500);
        
    } catch (\Exception $e) {
        // Errore generico
        $this->logError($projectId, 'general_error', $e->getMessage());
        
        jsonResponse([
            'error' => 'Si è verificato un errore',
            'details' => $e->getMessage()
        ], 500);
    }
}

private function logError(int $projectId, string $type, string $message): void
{
    // Log in tabella ga_analysis_log
    \Modules\AdsAnalyzer\Models\AnalysisLog::create([
        'project_id' => $projectId,
        'ad_group_id' => 0,
        'user_id' => Auth::id(),
        'status' => 'error',
        'error_message' => "[{$type}] {$message}"
    ]);
}
```

### Validazione Input

```php
<?php
// services/ValidationService.php

class ValidationService
{
    public static function validateBusinessContext(string $context): array
    {
        $errors = [];
        
        if (strlen($context) < 30) {
            $errors[] = 'Il contesto business deve essere almeno 30 caratteri';
        }
        
        if (strlen($context) > 2000) {
            $errors[] = 'Il contesto business non può superare 2000 caratteri';
        }
        
        return $errors;
    }
    
    public static function validateCsvFile(array $file): array
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Errore durante l\'upload del file';
            return $errors;
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            $errors[] = 'Il file deve essere in formato CSV';
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            $errors[] = 'Il file non può superare 10MB';
        }
        
        return $errors;
    }
}
```

---

## PROJECT CONTROLLER (CRUD)

```php
<?php
// controllers/ProjectController.php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Core\Database;
use Modules\AdsAnalyzer\Models\Project;
use Modules\AdsAnalyzer\Models\AdGroup;

class ProjectController
{
    /**
     * Lista tutti i progetti dell'utente
     */
    public function index(): void
    {
        $user = Auth::user();
        $projects = Project::getAllByUser($user['id']);
        
        View::render('ads-analyzer', 'projects/index', [
            'projects' => $projects,
            'pageTitle' => 'I Miei Progetti'
        ]);
    }
    
    /**
     * Form creazione nuovo progetto
     */
    public function create(): void
    {
        $user = Auth::user();
        
        // Carica contesti salvati per dropdown
        $savedContexts = \Modules\AdsAnalyzer\Models\BusinessContext::getByUser($user['id']);
        
        View::render('ads-analyzer', 'projects/create', [
            'savedContexts' => $savedContexts,
            'pageTitle' => 'Nuovo Progetto'
        ]);
    }
    
    /**
     * Salva nuovo progetto
     */
    public function store(): void
    {
        $user = Auth::user();
        
        // Validazione CSRF
        if (!verify_csrf_token($_POST['_token'] ?? '')) {
            abort(403, 'Token CSRF non valido');
        }
        
        // Validazione input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Il nome del progetto è obbligatorio';
            header('Location: ' . url('/ads-analyzer/projects/create'));
            exit;
        }
        
        if (strlen($name) > 255) {
            $_SESSION['flash_error'] = 'Il nome non può superare 255 caratteri';
            header('Location: ' . url('/ads-analyzer/projects/create'));
            exit;
        }
        
        // Crea progetto
        $projectId = Project::create([
            'user_id' => $user['id'],
            'name' => $name,
            'description' => $description,
            'status' => 'draft'
        ]);
        
        $_SESSION['flash_success'] = 'Progetto creato con successo';
        header('Location: ' . url("/ads-analyzer/projects/{$projectId}/upload"));
        exit;
    }
    
    /**
     * Visualizza dettaglio progetto
     */
    public function show(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);
        
        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }
        
        // Carica Ad Group con statistiche
        $adGroups = AdGroup::getByProject($id);
        
        View::render('ads-analyzer', 'projects/show', [
            'project' => $project,
            'adGroups' => $adGroups,
            'pageTitle' => $project['name']
        ]);
    }
    
    /**
     * Form modifica progetto
     */
    public function edit(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);
        
        if (!$project) {
            $_SESSION['flash_error'] = 'Progetto non trovato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }
        
        $savedContexts = \Modules\AdsAnalyzer\Models\BusinessContext::getByUser($user['id']);
        
        View::render('ads-analyzer', 'projects/edit', [
            'project' => $project,
            'savedContexts' => $savedContexts,
            'pageTitle' => 'Modifica: ' . $project['name']
        ]);
    }
    
    /**
     * Aggiorna progetto
     */
    public function update(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);
        
        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }
        
        // Validazione CSRF
        if (!verify_csrf_token($_POST['_token'] ?? '')) {
            abort(403, 'Token CSRF non valido');
        }
        
        // Validazione
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $_SESSION['flash_error'] = 'Il nome del progetto è obbligatorio';
            header('Location: ' . url("/ads-analyzer/projects/{$id}/edit"));
            exit;
        }
        
        // Aggiorna
        Project::update($id, [
            'name' => $name,
            'description' => $description
        ]);
        
        $_SESSION['flash_success'] = 'Progetto aggiornato';
        header('Location: ' . url("/ads-analyzer/projects/{$id}"));
        exit;
    }
    
    /**
     * Elimina progetto (soft o hard)
     */
    public function destroy(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);
        
        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }
        
        // Verifica CSRF (per form POST) o conferma (per AJAX)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['_token'] ?? '')) {
                abort(403, 'Token CSRF non valido');
            }
        }
        
        // Elimina progetto (CASCADE elimina anche ad_groups, search_terms, etc.)
        Project::delete($id);
        
        // Risposta basata sul tipo di richiesta
        if ($this->isAjax()) {
            jsonResponse(['success' => true, 'message' => 'Progetto eliminato']);
        } else {
            $_SESSION['flash_success'] = 'Progetto eliminato';
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }
    }
    
    /**
     * Duplica progetto (solo struttura, senza dati)
     */
    public function duplicate(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);
        
        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }
        
        // Crea copia
        $newProjectId = Project::create([
            'user_id' => $user['id'],
            'name' => $project['name'] . ' (copia)',
            'description' => $project['description'],
            'business_context' => $project['business_context'],
            'status' => 'draft'
        ]);
        
        $_SESSION['flash_success'] = 'Progetto duplicato';
        header('Location: ' . url("/ads-analyzer/projects/{$newProjectId}"));
        exit;
    }
    
    /**
     * Archivia/Ripristina progetto
     */
    public function toggleArchive(int $id): void
    {
        $user = Auth::user();
        $project = Project::findByUserAndId($user['id'], $id);
        
        if (!$project) {
            jsonResponse(['error' => 'Progetto non trovato'], 404);
        }
        
        $newStatus = $project['status'] === 'archived' ? 'completed' : 'archived';
        
        Project::update($id, ['status' => $newStatus]);
        
        $message = $newStatus === 'archived' ? 'Progetto archiviato' : 'Progetto ripristinato';
        
        if ($this->isAjax()) {
            jsonResponse(['success' => true, 'status' => $newStatus, 'message' => $message]);
        } else {
            $_SESSION['flash_success'] = $message;
            header('Location: ' . url('/ads-analyzer'));
            exit;
        }
    }
    
    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
```

---

## DASHBOARD CONTROLLER

```php
<?php
// controllers/DashboardController.php

namespace Modules\AdsAnalyzer\Controllers;

use Core\View;
use Core\Auth;
use Modules\AdsAnalyzer\Models\Project;

class DashboardController
{
    /**
     * Dashboard modulo - lista progetti
     */
    public function index(): void
    {
        $user = Auth::user();
        
        // Prendi tutti i progetti dell'utente
        $projects = Project::getAllByUser($user['id']);
        
        // Statistiche aggregate
        $stats = [
            'total_projects' => count($projects),
            'completed_projects' => count(array_filter($projects, fn($p) => $p['status'] === 'completed')),
            'total_negatives' => array_sum(array_column($projects, 'total_negatives_found')),
            'total_terms_analyzed' => array_sum(array_column($projects, 'total_terms'))
        ];
        
        View::render('ads-analyzer', 'dashboard/index', [
            'projects' => $projects,
            'stats' => $stats,
            'pageTitle' => 'Google Ads Analyzer'
        ]);
    }
}
```

---

## RIFERIMENTI

- **Modulo reference**: `ai-content` per pattern AiService
- **Standard**: `docs/MASTER-STANDARDS.md`
- **Golden Rules**: 10 regole inviolabili
- **Prefisso DB**: `ga_` (Google Ads)
- **Badge AI Logs**: `ads-analyzer` (amber)
