# AI SEO Content Generator - Specifiche Modulo

## Overview

Modulo per generare articoli SEO-ottimizzati partendo da keyword, analisi SERP, scraping competitor e generazione AI.

**Directory:** `modules/ai-content/`

---

## Architettura Modulo

```
modules/ai-content/
├── module.json
├── routes.php
├── controllers/
│   ├── DashboardController.php
│   ├── KeywordController.php
│   ├── SerpController.php
│   ├── ArticleController.php
│   └── WordPressController.php
├── models/
│   ├── Keyword.php
│   ├── SerpResult.php
│   ├── Source.php
│   ├── Article.php
│   └── WpSite.php
├── views/
│   ├── dashboard.php
│   ├── keywords/
│   │   ├── index.php
│   │   └── serp-results.php
│   ├── articles/
│   │   ├── index.php
│   │   ├── show.php
│   │   ├── editor.php
│   │   └── progress.php
│   └── wordpress/
│       ├── sites.php
│       └── publish-modal.php
└── services/
    ├── SerpApiService.php
    ├── ContentScraperService.php
    ├── BriefBuilderService.php
    └── ArticleGeneratorService.php
```

---

## Database Schema

```sql
-- Keywords salvate
CREATE TABLE aic_keywords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    keyword VARCHAR(255) NOT NULL,
    language VARCHAR(10) DEFAULT 'it',
    location VARCHAR(50) DEFAULT 'Italy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- Risultati SERP estratti
CREATE TABLE aic_serp_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    keyword_id INT NOT NULL,
    position INT NOT NULL,
    title VARCHAR(500),
    url VARCHAR(2000) NOT NULL,
    snippet TEXT,
    domain VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (keyword_id) REFERENCES aic_keywords(id) ON DELETE CASCADE,
    INDEX idx_keyword (keyword_id)
);

-- People Also Ask
CREATE TABLE aic_paa_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    keyword_id INT NOT NULL,
    question TEXT NOT NULL,
    snippet TEXT,
    position INT,
    FOREIGN KEY (keyword_id) REFERENCES aic_keywords(id) ON DELETE CASCADE
);

-- Fonti selezionate per articolo (SERP + custom)
CREATE TABLE aic_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    url VARCHAR(2000) NOT NULL,
    title VARCHAR(500),
    content_extracted LONGTEXT,
    word_count INT,
    is_custom BOOLEAN DEFAULT FALSE,
    scrape_status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    scraped_at TIMESTAMP NULL,
    FOREIGN KEY (article_id) REFERENCES aic_articles(id) ON DELETE CASCADE
);

-- Articoli generati
CREATE TABLE aic_articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    keyword_id INT NOT NULL,
    title VARCHAR(500),
    content LONGTEXT,
    meta_description VARCHAR(320),
    word_count INT,
    status ENUM('draft', 'generating', 'ready', 'published', 'failed') DEFAULT 'draft',
    brief_data JSON,
    ai_model VARCHAR(50),
    generation_time_ms INT,
    credits_used INT DEFAULT 0,
    published_at TIMESTAMP NULL,
    wp_site_id INT NULL,
    wp_post_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES aic_keywords(id) ON DELETE CASCADE,
    FOREIGN KEY (wp_site_id) REFERENCES aic_wp_sites(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
);

-- Siti WordPress collegati
CREATE TABLE aic_wp_sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    api_key VARCHAR(64) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    categories_cache JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_site (user_id, url)
);

-- Log pubblicazioni WP
CREATE TABLE aic_wp_publish_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    article_id INT NOT NULL,
    wp_site_id INT NOT NULL,
    wp_post_id INT,
    status ENUM('success', 'failed') NOT NULL,
    response_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES aic_articles(id) ON DELETE CASCADE
);
```

---

## module.json

```json
{
    "name": "AI SEO Content Generator",
    "slug": "ai-content",
    "version": "1.0.0",
    "description": "Genera articoli SEO-ottimizzati con AI partendo da analisi SERP",
    "icon": "pencil-square",
    "color": "purple",
    "author": "SEO Toolkit",
    "routes_prefix": "/ai-content",
    "menu": [
        {
            "label": "Dashboard",
            "route": "/ai-content",
            "icon": "home"
        },
        {
            "label": "Keywords",
            "route": "/ai-content/keywords",
            "icon": "magnifying-glass"
        },
        {
            "label": "Articoli",
            "route": "/ai-content/articles",
            "icon": "document-text"
        },
        {
            "label": "Siti WordPress",
            "route": "/ai-content/wordpress",
            "icon": "globe-alt"
        }
    ],
    "credits": {
        "serp_extraction": 3,
        "scrape_per_url": 1,
        "article_generation": 10,
        "wp_publish": 0
    },
    "settings": {
        "serpapi_key": {
            "type": "password",
            "label": "SerpAPI Key",
            "admin_only": true
        },
        "default_language": {
            "type": "select",
            "label": "Lingua default",
            "options": ["it", "en", "es", "de", "fr"],
            "default": "it"
        },
        "default_word_count": {
            "type": "number",
            "label": "Parole target articolo",
            "default": 1500
        },
        "max_sources": {
            "type": "number",
            "label": "Max fonti per articolo",
            "default": 6
        }
    }
}
```

---

## Routes (routes.php)

```php
<?php

use Modules\AiContent\Controllers\DashboardController;
use Modules\AiContent\Controllers\KeywordController;
use Modules\AiContent\Controllers\SerpController;
use Modules\AiContent\Controllers\ArticleController;
use Modules\AiContent\Controllers\WordPressController;

return [
    // Dashboard
    'GET /ai-content' => [DashboardController::class, 'index'],
    
    // Keywords
    'GET /ai-content/keywords' => [KeywordController::class, 'index'],
    'POST /ai-content/keywords' => [KeywordController::class, 'store'],
    'DELETE /ai-content/keywords/{id}' => [KeywordController::class, 'delete'],
    
    // SERP Analysis
    'POST /ai-content/keywords/{id}/serp' => [SerpController::class, 'extract'],
    'GET /ai-content/keywords/{id}/serp' => [SerpController::class, 'show'],
    
    // Articles
    'GET /ai-content/articles' => [ArticleController::class, 'index'],
    'GET /ai-content/articles/{id}' => [ArticleController::class, 'show'],
    'POST /ai-content/articles/generate' => [ArticleController::class, 'generate'],
    'GET /ai-content/articles/{id}/progress' => [ArticleController::class, 'progress'],
    'PUT /ai-content/articles/{id}' => [ArticleController::class, 'update'],
    'DELETE /ai-content/articles/{id}' => [ArticleController::class, 'delete'],
    'POST /ai-content/articles/{id}/regenerate' => [ArticleController::class, 'regenerate'],
    
    // WordPress
    'GET /ai-content/wordpress' => [WordPressController::class, 'index'],
    'POST /ai-content/wordpress/sites' => [WordPressController::class, 'addSite'],
    'DELETE /ai-content/wordpress/sites/{id}' => [WordPressController::class, 'removeSite'],
    'POST /ai-content/wordpress/sites/{id}/sync' => [WordPressController::class, 'syncCategories'],
    'POST /ai-content/articles/{id}/publish' => [WordPressController::class, 'publish'],
    'GET /ai-content/wordpress/sites/{id}/categories' => [WordPressController::class, 'getCategories'],
];
```

---

## Services

### 1. SerpApiService.php

```php
<?php

namespace Modules\AiContent\Services;

class SerpApiService
{
    private string $apiKey;
    
    public function __construct()
    {
        $this->apiKey = getSetting('ai-content', 'serpapi_key');
    }
    
    /**
     * Estrae risultati SERP + PAA per una keyword
     * @return array{organic: array, paa: array, related: array}
     */
    public function search(string $keyword, string $language = 'it', string $location = 'Italy'): array
    {
        $params = [
            'q' => $keyword,
            'location' => $location,
            'hl' => $language,
            'gl' => strtolower(substr($location, 0, 2)),
            'google_domain' => $this->getGoogleDomain($language),
            'num' => 10,
            'api_key' => $this->apiKey
        ];
        
        $url = 'https://serpapi.com/search.json?' . http_build_query($params);
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        return [
            'organic' => $this->parseOrganicResults($data['organic_results'] ?? []),
            'paa' => $this->parsePaaResults($data['related_questions'] ?? []),
            'related' => $data['related_searches'] ?? []
        ];
    }
    
    private function parseOrganicResults(array $results): array
    {
        return array_map(function($item, $index) {
            return [
                'position' => $index + 1,
                'title' => $item['title'] ?? '',
                'url' => $item['link'] ?? '',
                'snippet' => $item['snippet'] ?? '',
                'domain' => parse_url($item['link'] ?? '', PHP_URL_HOST)
            ];
        }, $results, array_keys($results));
    }
    
    private function parsePaaResults(array $results): array
    {
        return array_map(function($item, $index) {
            return [
                'position' => $index + 1,
                'question' => $item['question'] ?? '',
                'snippet' => $item['snippet'] ?? ''
            ];
        }, $results, array_keys($results));
    }
    
    private function getGoogleDomain(string $lang): string
    {
        $domains = [
            'it' => 'google.it',
            'en' => 'google.com',
            'es' => 'google.es',
            'de' => 'google.de',
            'fr' => 'google.fr'
        ];
        return $domains[$lang] ?? 'google.com';
    }
}
```

### 2. ContentScraperService.php

```php
<?php

namespace Modules\AiContent\Services;

use Services\ScraperService;

class ContentScraperService
{
    private ScraperService $scraper;
    
    public function __construct()
    {
        $this->scraper = new ScraperService();
    }
    
    /**
     * Estrae contenuto principale da URL (esclude header, footer, sidebar)
     */
    public function extractContent(string $url): array
    {
        try {
            $html = $this->scraper->fetch($url);
            
            // Rimuovi elementi non-content
            $html = $this->removeNonContent($html);
            
            // Estrai testo principale
            $content = $this->extractMainContent($html);
            
            // Estrai headings
            $headings = $this->extractHeadings($html);
            
            return [
                'success' => true,
                'content' => $content,
                'headings' => $headings,
                'word_count' => str_word_count($content)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function removeNonContent(string $html): string
    {
        // Rimuovi script, style, nav, header, footer, sidebar, ads
        $patterns = [
            '/<script\b[^>]*>.*?<\/script>/is',
            '/<style\b[^>]*>.*?<\/style>/is',
            '/<nav\b[^>]*>.*?<\/nav>/is',
            '/<header\b[^>]*>.*?<\/header>/is',
            '/<footer\b[^>]*>.*?<\/footer>/is',
            '/<aside\b[^>]*>.*?<\/aside>/is',
            '/<div[^>]*class="[^"]*(?:sidebar|widget|ad|banner|menu|nav)[^"]*"[^>]*>.*?<\/div>/is',
            '/<!--.*?-->/s'
        ];
        
        return preg_replace($patterns, '', $html);
    }
    
    private function extractMainContent(string $html): string
    {
        // Cerca contenitori comuni di contenuto
        $selectors = ['article', 'main', '.content', '.post-content', '.entry-content', '#content'];
        
        foreach ($selectors as $selector) {
            // Implementazione semplificata - in produzione usare DomCrawler
            if (preg_match('/<' . preg_quote($selector) . '[^>]*>(.*?)<\/' . $selector . '>/is', $html, $match)) {
                return strip_tags($match[1]);
            }
        }
        
        // Fallback: estrai tutto il body
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $match)) {
            return strip_tags($match[1]);
        }
        
        return strip_tags($html);
    }
    
    private function extractHeadings(string $html): array
    {
        $headings = [];
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $headings[] = [
                'level' => (int)$match[1],
                'text' => strip_tags($match[2])
            ];
        }
        
        return $headings;
    }
}
```

### 3. BriefBuilderService.php

```php
<?php

namespace Modules\AiContent\Services;

class BriefBuilderService
{
    /**
     * Costruisce brief completo per AI da keyword + fonti scrappate
     */
    public function build(array $keyword, array $serpResults, array $paaQuestions, array $scrapedSources): array
    {
        // Analizza struttura headings dai competitor
        $headingsAnalysis = $this->analyzeHeadings($scrapedSources);
        
        // Estrai entità e termini ricorrenti
        $entities = $this->extractEntities($scrapedSources);
        
        // Calcola word count medio competitor
        $avgWordCount = $this->calculateAvgWordCount($scrapedSources);
        
        return [
            'keyword' => $keyword['keyword'],
            'language' => $keyword['language'],
            'search_intent' => $this->detectIntent($keyword['keyword'], $serpResults),
            'serp_titles' => array_column($serpResults, 'title'),
            'paa_questions' => array_column($paaQuestions, 'question'),
            'competitor_headings' => $headingsAnalysis,
            'key_entities' => $entities,
            'avg_word_count' => $avgWordCount,
            'sources_summary' => $this->summarizeSources($scrapedSources)
        ];
    }
    
    private function analyzeHeadings(array $sources): array
    {
        $allHeadings = [];
        foreach ($sources as $source) {
            if (!empty($source['headings'])) {
                $allHeadings = array_merge($allHeadings, $source['headings']);
            }
        }
        
        // Raggruppa per frequenza
        $frequency = [];
        foreach ($allHeadings as $h) {
            $normalized = strtolower(trim($h['text']));
            if (!isset($frequency[$normalized])) {
                $frequency[$normalized] = ['text' => $h['text'], 'level' => $h['level'], 'count' => 0];
            }
            $frequency[$normalized]['count']++;
        }
        
        // Ordina per frequenza
        usort($frequency, fn($a, $b) => $b['count'] - $a['count']);
        
        return array_slice($frequency, 0, 15);
    }
    
    private function extractEntities(array $sources): array
    {
        $allText = implode(' ', array_column($sources, 'content'));
        
        // Estrai termini frequenti (2-3 parole)
        $words = str_word_count(strtolower($allText), 1, 'àèéìòù');
        $frequency = array_count_values($words);
        
        // Filtra stopwords e termini troppo comuni
        $stopwords = ['il', 'la', 'di', 'che', 'e', 'per', 'un', 'una', 'in', 'con', 'non', 'sono', 'the', 'and', 'to', 'of', 'a', 'is'];
        $frequency = array_filter($frequency, fn($count, $word) => 
            $count >= 3 && strlen($word) > 3 && !in_array($word, $stopwords), 
            ARRAY_FILTER_USE_BOTH
        );
        
        arsort($frequency);
        return array_slice($frequency, 0, 20, true);
    }
    
    private function calculateAvgWordCount(array $sources): int
    {
        $counts = array_filter(array_column($sources, 'word_count'));
        return count($counts) > 0 ? (int)(array_sum($counts) / count($counts)) : 1500;
    }
    
    private function detectIntent(string $keyword, array $serpResults): string
    {
        $keyword = strtolower($keyword);
        
        // Pattern-based intent detection
        if (preg_match('/\b(come|how|perché|why|cosa|what|quando|when|guida|tutorial)\b/i', $keyword)) {
            return 'informational';
        }
        if (preg_match('/\b(migliori|best|top|confronto|vs|review|recensione)\b/i', $keyword)) {
            return 'commercial';
        }
        if (preg_match('/\b(compra|buy|prezzo|price|offerta|sconto)\b/i', $keyword)) {
            return 'transactional';
        }
        
        return 'informational';
    }
    
    private function summarizeSources(array $sources): array
    {
        return array_map(function($s) {
            return [
                'url' => $s['url'],
                'word_count' => $s['word_count'] ?? 0,
                'excerpt' => substr($s['content'] ?? '', 0, 500) . '...'
            ];
        }, $sources);
    }
}
```

### 4. ArticleGeneratorService.php

```php
<?php

namespace Modules\AiContent\Services;

use Services\AiService;

class ArticleGeneratorService
{
    private AiService $ai;
    
    public function __construct()
    {
        $this->ai = new AiService();
    }
    
    /**
     * Genera articolo HTML completo da brief
     */
    public function generate(array $brief, int $targetWords = 1500): array
    {
        $prompt = $this->buildPrompt($brief, $targetWords);
        
        $startTime = microtime(true);
        
        $response = $this->ai->chat([
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ], [
            'max_tokens' => 8000,
            'temperature' => 0.7
        ]);
        
        $generationTime = (int)((microtime(true) - $startTime) * 1000);
        
        // Parse response
        $content = $response['content'] ?? '';
        
        // Estrai title e meta description se presenti nel formato
        $title = $this->extractTitle($content);
        $metaDescription = $this->extractMetaDescription($content);
        $htmlContent = $this->extractHtmlContent($content);
        
        return [
            'title' => $title,
            'meta_description' => $metaDescription,
            'content' => $htmlContent,
            'word_count' => str_word_count(strip_tags($htmlContent)),
            'generation_time_ms' => $generationTime
        ];
    }
    
    private function buildPrompt(array $brief, int $targetWords): string
    {
        $paaList = implode("\n- ", $brief['paa_questions']);
        $headingsList = implode("\n- ", array_column($brief['competitor_headings'], 'text'));
        $entitiesList = implode(', ', array_keys($brief['key_entities']));
        
        return <<<PROMPT
Sei un esperto SEO copywriter. Scrivi un articolo completo e ottimizzato SEO.

## KEYWORD TARGET
{$brief['keyword']}

## LINGUA
{$brief['language']}

## SEARCH INTENT
{$brief['search_intent']}

## DOMANDE CORRELATE (PAA) - Rispondi a queste nel testo
- {$paaList}

## STRUTTURA SUGGERITA (headings usati dai competitor)
- {$headingsList}

## TERMINI/ENTITÀ DA INCLUDERE
{$entitiesList}

## REQUISITI OUTPUT

1. **TITLE TAG**: Genera un title SEO-ottimizzato (max 60 caratteri) che includa la keyword
2. **META DESCRIPTION**: Genera meta description (max 155 caratteri) con keyword e CTA
3. **CONTENUTO HTML**: 
   - Circa {$targetWords} parole
   - Struttura con H2 e H3 semantici
   - Paragrafi brevi e leggibili
   - Includi le risposte alle domande PAA in modo naturale
   - Usa liste puntate dove appropriato
   - Keyword density naturale (1-2%)
   - NO link esterni
   - NO immagini placeholder

## FORMATO OUTPUT

```title
[Il tuo title qui]
```

```meta
[La tua meta description qui]
```

```html
[Il contenuto HTML completo qui]
```

Scrivi ora l'articolo completo:
PROMPT;
    }
    
    private function extractTitle(string $content): string
    {
        if (preg_match('/```title\s*(.*?)\s*```/s', $content, $match)) {
            return trim($match[1]);
        }
        // Fallback: primo H1
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $match)) {
            return strip_tags($match[1]);
        }
        return '';
    }
    
    private function extractMetaDescription(string $content): string
    {
        if (preg_match('/```meta\s*(.*?)\s*```/s', $content, $match)) {
            return trim($match[1]);
        }
        return '';
    }
    
    private function extractHtmlContent(string $content): string
    {
        if (preg_match('/```html\s*(.*?)\s*```/s', $content, $match)) {
            return trim($match[1]);
        }
        // Fallback: tutto il contenuto
        return $content;
    }
}
```

---

## Plugin WordPress Connector

### File: `seo-toolkit-connector.php`

```php
<?php
/**
 * Plugin Name: SEO Toolkit Connector
 * Description: Connettore per piattaforma SEO Toolkit SaaS
 * Version: 1.0.0
 * Author: SEO Toolkit
 */

if (!defined('ABSPATH')) exit;

class SEOToolkitConnector {
    
    private $option_name = 'seo_toolkit_api_key';
    
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('rest_api_init', [$this, 'registerEndpoints']);
        add_action('admin_init', [$this, 'registerSettings']);
    }
    
    public function addAdminMenu() {
        add_options_page(
            'SEO Toolkit Connector',
            'SEO Toolkit',
            'manage_options',
            'seo-toolkit-connector',
            [$this, 'renderSettingsPage']
        );
    }
    
    public function registerSettings() {
        register_setting('seo_toolkit_settings', $this->option_name);
    }
    
    public function renderSettingsPage() {
        $api_key = get_option($this->option_name);
        if (empty($api_key)) {
            $api_key = $this->generateApiKey();
            update_option($this->option_name, $api_key);
        }
        ?>
        <div class="wrap">
            <h1>SEO Toolkit Connector</h1>
            <div class="card" style="max-width: 600px; padding: 20px;">
                <h2>API Key</h2>
                <p>Usa questa API key nella piattaforma SEO Toolkit per collegare questo sito:</p>
                <code style="display: block; padding: 15px; background: #f0f0f0; font-size: 14px; word-break: break-all;">
                    <?php echo esc_html($api_key); ?>
                </code>
                <p style="margin-top: 15px;">
                    <strong>URL Sito:</strong> <?php echo esc_url(home_url()); ?>
                </p>
                <form method="post" action="" style="margin-top: 20px;">
                    <?php wp_nonce_field('regenerate_api_key'); ?>
                    <input type="submit" name="regenerate_key" class="button" value="Rigenera API Key">
                </form>
            </div>
        </div>
        <?php
        
        if (isset($_POST['regenerate_key']) && wp_verify_nonce($_POST['_wpnonce'], 'regenerate_api_key')) {
            $new_key = $this->generateApiKey();
            update_option($this->option_name, $new_key);
            wp_redirect(admin_url('options-general.php?page=seo-toolkit-connector&regenerated=1'));
            exit;
        }
    }
    
    private function generateApiKey(): string {
        return 'stk_' . bin2hex(random_bytes(24));
    }
    
    public function registerEndpoints() {
        // Verifica connessione
        register_rest_route('seo-toolkit/v1', '/ping', [
            'methods' => 'GET',
            'callback' => [$this, 'ping'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
        
        // Lista categorie
        register_rest_route('seo-toolkit/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'getCategories'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
        
        // Lista tag
        register_rest_route('seo-toolkit/v1', '/tags', [
            'methods' => 'GET',
            'callback' => [$this, 'getTags'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
        
        // Crea/Aggiorna post
        register_rest_route('seo-toolkit/v1', '/posts', [
            'methods' => 'POST',
            'callback' => [$this, 'createPost'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
        
        // Aggiorna post esistente
        register_rest_route('seo-toolkit/v1', '/posts/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'updatePost'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
        
        // Lista post (per rigenerazione)
        register_rest_route('seo-toolkit/v1', '/posts', [
            'methods' => 'GET',
            'callback' => [$this, 'getPosts'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
        
        // Upload media
        register_rest_route('seo-toolkit/v1', '/media', [
            'methods' => 'POST',
            'callback' => [$this, 'uploadMedia'],
            'permission_callback' => [$this, 'verifyApiKey']
        ]);
    }
    
    public function verifyApiKey($request): bool {
        $provided_key = $request->get_header('X-SEO-Toolkit-Key');
        $stored_key = get_option($this->option_name);
        
        return !empty($provided_key) && hash_equals($stored_key, $provided_key);
    }
    
    public function ping(): \WP_REST_Response {
        return new \WP_REST_Response([
            'success' => true,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'wp_version' => get_bloginfo('version')
        ]);
    }
    
    public function getCategories(): \WP_REST_Response {
        $categories = get_categories(['hide_empty' => false]);
        $result = array_map(function($cat) {
            return [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent' => $cat->parent,
                'count' => $cat->count
            ];
        }, $categories);
        
        return new \WP_REST_Response($result);
    }
    
    public function getTags(): \WP_REST_Response {
        $tags = get_tags(['hide_empty' => false]);
        $result = array_map(function($tag) {
            return [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug
            ];
        }, $tags);
        
        return new \WP_REST_Response($result);
    }
    
    public function createPost($request): \WP_REST_Response {
        $params = $request->get_json_params();
        
        $post_data = [
            'post_title' => sanitize_text_field($params['title'] ?? ''),
            'post_content' => wp_kses_post($params['content'] ?? ''),
            'post_status' => $params['status'] ?? 'draft',
            'post_category' => $params['categories'] ?? [],
            'tags_input' => $params['tags'] ?? [],
            'meta_input' => []
        ];
        
        // Meta description (supporto Yoast/RankMath)
        if (!empty($params['meta_description'])) {
            $post_data['meta_input']['_yoast_wpseo_metadesc'] = $params['meta_description'];
            $post_data['meta_input']['rank_math_description'] = $params['meta_description'];
        }
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $post_id->get_error_message()
            ], 400);
        }
        
        // Featured image se presente
        if (!empty($params['featured_image_id'])) {
            set_post_thumbnail($post_id, $params['featured_image_id']);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'edit_url' => admin_url("post.php?post={$post_id}&action=edit")
        ]);
    }
    
    public function updatePost($request): \WP_REST_Response {
        $post_id = (int) $request['id'];
        $params = $request->get_json_params();
        
        $post_data = [
            'ID' => $post_id,
            'post_title' => sanitize_text_field($params['title'] ?? ''),
            'post_content' => wp_kses_post($params['content'] ?? '')
        ];
        
        if (isset($params['status'])) {
            $post_data['post_status'] = $params['status'];
        }
        
        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $result->get_error_message()
            ], 400);
        }
        
        // Aggiorna meta description
        if (!empty($params['meta_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $params['meta_description']);
            update_post_meta($post_id, 'rank_math_description', $params['meta_description']);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id)
        ]);
    }
    
    public function getPosts($request): \WP_REST_Response {
        $args = [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => $request->get_param('per_page') ?? 20,
            'paged' => $request->get_param('page') ?? 1,
            's' => $request->get_param('search') ?? ''
        ];
        
        $query = new \WP_Query($args);
        
        $posts = array_map(function($post) {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'url' => get_permalink($post->ID),
                'date' => $post->post_date,
                'categories' => wp_get_post_categories($post->ID, ['fields' => 'names'])
            ];
        }, $query->posts);
        
        return new \WP_REST_Response([
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ]);
    }
    
    public function uploadMedia($request): \WP_REST_Response {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $files = $request->get_file_params();
        
        if (empty($files['file'])) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'No file uploaded'
            ], 400);
        }
        
        $attachment_id = media_handle_sideload($files['file'], 0);
        
        if (is_wp_error($attachment_id)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => $attachment_id->get_error_message()
            ], 400);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id)
        ]);
    }
}

new SEOToolkitConnector();
```

---

## Flusso UX Completo

### Step 1: Dashboard
- Statistiche: articoli generati, crediti usati, siti WP collegati
- Quick actions: Nuova keyword, Ultimi articoli

### Step 2: Keywords → Aggiungi keyword
- Input: keyword, lingua, location
- Costo: 0 crediti (solo salvataggio)

### Step 3: Click "Estrai SERP"
- Spinner durante chiamata SerpAPI
- Costo: **3 crediti**
- Output: Tabella top 10 + lista PAA

### Step 4: Seleziona fonti
- Checkbox su risultati SERP (max 5)
- Campo "Aggiungi URL custom"
- Preview URL selezionati

### Step 5: Click "Genera Articolo"
- Progress bar con step:
  1. "Scraping fonte 1/6..." (1 credito × N)
  2. "Costruzione brief..."
  3. "Generazione articolo AI..." (10 crediti)
  4. "Completato!"
- Costo totale mostrato prima di conferma

### Step 6: Visualizza articolo
- Preview HTML renderizzato
- Sidebar: title, meta description, word count
- Azioni: Modifica, Rigenera, Pubblica

### Step 7: Pubblica su WordPress
- Modal: seleziona sito WP
- Seleziona categoria
- Status: bozza/pubblicato
- Conferma → pubblica
- Link diretto a post WP

---

## Costi Crediti Riepilogo

| Azione | Crediti |
|--------|---------|
| Salva keyword | 0 |
| Estrai SERP + PAA | 3 |
| Scraping per URL | 1 |
| Genera articolo AI | 10 |
| Pubblica su WP | 0 |

**Esempio workflow completo:**
- SERP: 3
- Scraping 5 URL: 5  
- Generazione AI: 10
- **Totale: 18 crediti**

---

## Settings Admin

Nel pannello admin, sezione modulo `ai-content`:

| Setting | Tipo | Default |
|---------|------|---------|
| SerpAPI Key | password | - |
| Lingua default | select | it |
| Parole target | number | 1500 |
| Max fonti | number | 6 |
| AI Model | select | claude-sonnet-4-20250514 |
| AI Temperature | range | 0.7 |

---

## Note Implementazione

1. **Scraping asincrono**: Per evitare timeout, usare job queue o processo background
2. **Rate limiting SerpAPI**: Max 100 ricerche/mese su piano gratuito
3. **Cache SERP**: Salvare risultati per evitare ricerche duplicate (TTL 24h)
4. **Retry logic**: Su fallimento scraping, retry 2x con delay
5. **Sanitizzazione HTML**: Pulire output AI prima di salvare
6. **WP Connection test**: Verificare connessione prima di mostrare "Pubblica"
