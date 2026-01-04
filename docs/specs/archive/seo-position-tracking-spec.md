# SEO Position Tracking Module - Specifiche Tecniche

## Overview

Modulo per monitoraggio posizionamento keyword, traffico organico e revenue con dati GSC + GA4 e analisi AI automatizzate.

| Aspetto | Dettaglio |
|---------|-----------|
| **Slug** | `seo-tracking` |
| **Prefisso DB** | `st_` |
| **Integrazioni** | Google Search Console (OAuth2), GA4 (Service Account) |
| **AI** | Report automatici, insights on-demand, alert intelligenti |

---

## ARCHITETTURA

```
modules/seo-tracking/
├── module.json
├── routes.php
├── controllers/
│   ├── ProjectController.php
│   ├── DashboardController.php
│   ├── KeywordController.php
│   ├── GscController.php
│   ├── Ga4Controller.php
│   ├── AlertController.php
│   ├── ReportController.php
│   └── AiController.php
├── models/
│   ├── Project.php
│   ├── Keyword.php
│   ├── KeywordTracking.php
│   ├── GscConnection.php
│   ├── GscData.php
│   ├── Ga4Connection.php
│   ├── Ga4Data.php
│   ├── Alert.php
│   ├── AlertLog.php
│   └── AiReport.php
├── services/
│   ├── GscService.php
│   ├── Ga4Service.php
│   ├── KeywordMatcher.php
│   ├── RevenueAttributor.php
│   ├── AlertService.php
│   ├── AiAnalyzer.php
│   └── EmailService.php
├── views/
│   ├── projects/
│   │   ├── index.php
│   │   ├── create.php
│   │   └── settings.php
│   ├── dashboard/
│   │   ├── index.php
│   │   ├── keywords.php
│   │   ├── pages.php
│   │   └── revenue.php
│   ├── keywords/
│   │   ├── index.php
│   │   ├── tracked.php
│   │   ├── add.php
│   │   └── detail.php
│   ├── alerts/
│   │   ├── index.php
│   │   ├── settings.php
│   │   └── history.php
│   ├── reports/
│   │   ├── index.php
│   │   ├── weekly.php
│   │   ├── monthly.php
│   │   └── custom.php
│   └── connections/
│       ├── gsc.php
│       └── ga4.php
└── cron/
    ├── daily-sync.php
    ├── weekly-report.php
    └── monthly-report.php
```

---

## DATABASE SCHEMA

```sql
-- =============================================
-- PROGETTI E CONNESSIONI
-- =============================================

-- Progetti tracking
CREATE TABLE st_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(500) NOT NULL,
    
    -- Stato connessioni
    gsc_connected BOOLEAN DEFAULT FALSE,
    ga4_connected BOOLEAN DEFAULT FALSE,
    
    -- Impostazioni sync
    sync_enabled BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    sync_status ENUM('idle', 'running', 'completed', 'error') DEFAULT 'idle',
    
    -- Impostazioni report AI
    ai_reports_enabled BOOLEAN DEFAULT TRUE,
    weekly_report_day TINYINT DEFAULT 1 COMMENT '1=Lunedì, 7=Domenica',
    weekly_report_time TIME DEFAULT '08:00:00',
    monthly_report_day TINYINT DEFAULT 1 COMMENT 'Giorno del mese',
    
    -- Email notifiche
    notification_emails JSON NULL COMMENT 'Array di email per report/alert',
    
    -- Storico
    data_retention_months INT DEFAULT 16,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_sync (sync_enabled, last_sync_at)
) ENGINE=InnoDB;

-- Connessione GSC (OAuth2)
CREATE TABLE st_gsc_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    
    -- OAuth tokens
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_expires_at TIMESTAMP NOT NULL,
    
    -- Proprietà selezionata
    property_url VARCHAR(500) NOT NULL,
    property_type ENUM('URL_PREFIX', 'DOMAIN') NOT NULL,
    
    -- Stato
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    last_error TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Connessione GA4 (Service Account)
CREATE TABLE st_ga4_connections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    
    -- Service Account (JSON criptato)
    service_account_json TEXT NOT NULL COMMENT 'JSON criptato',
    
    -- Property GA4
    property_id VARCHAR(50) NOT NULL COMMENT 'es: 123456789',
    property_name VARCHAR(255) NULL,
    
    -- Stato
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    last_error TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- KEYWORD TRACKING
-- =============================================

-- Keyword monitorate (lista utente)
CREATE TABLE st_keywords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    keyword VARCHAR(500) NOT NULL,
    
    -- Categorizzazione
    keyword_group VARCHAR(100) NULL COMMENT 'Gruppo personalizzato',
    is_brand BOOLEAN DEFAULT FALSE,
    target_url VARCHAR(2000) NULL COMMENT 'URL target atteso',
    
    -- Priorità
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    
    -- Alert specifici
    alert_position_change INT DEFAULT 5 COMMENT 'Alert se variazione >= N posizioni',
    alert_enabled BOOLEAN DEFAULT TRUE,
    
    -- Ultima posizione nota (cache)
    last_position DECIMAL(5,2) NULL,
    last_clicks INT NULL,
    last_impressions INT NULL,
    last_ctr DECIMAL(5,4) NULL,
    last_updated_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_project_keyword (project_id, keyword(255)),
    INDEX idx_group (project_id, keyword_group),
    INDEX idx_priority (project_id, priority)
) ENGINE=InnoDB;

-- Dati storici GSC (tutte le query)
CREATE TABLE st_gsc_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    -- Dimensioni
    date DATE NOT NULL,
    query VARCHAR(500) NOT NULL,
    page VARCHAR(2000) NOT NULL,
    country VARCHAR(10) NOT NULL DEFAULT 'all',
    device ENUM('DESKTOP', 'MOBILE', 'TABLET', 'ALL') DEFAULT 'ALL',
    
    -- Metriche
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    ctr DECIMAL(5,4) DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    
    -- Link a keyword tracciata (se esiste)
    keyword_id INT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE SET NULL,
    
    UNIQUE KEY uk_data_point (project_id, date, query(255), page(255), country, device),
    INDEX idx_project_date (project_id, date),
    INDEX idx_project_query (project_id, query(255)),
    INDEX idx_project_page (project_id, page(255)),
    INDEX idx_keyword (keyword_id, date)
) ENGINE=InnoDB;

-- Snapshot giornaliero posizioni keyword tracciate
CREATE TABLE st_keyword_positions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    keyword_id INT NOT NULL,
    date DATE NOT NULL,
    
    -- Posizione aggregata (media pesata per impressions)
    avg_position DECIMAL(5,2) NOT NULL,
    best_position DECIMAL(5,2) NULL,
    
    -- Metriche aggregate
    total_clicks INT DEFAULT 0,
    total_impressions INT DEFAULT 0,
    avg_ctr DECIMAL(5,4) DEFAULT 0,
    
    -- Variazioni vs giorno precedente
    position_change DECIMAL(5,2) NULL COMMENT 'Positivo = miglioramento',
    clicks_change INT NULL,
    impressions_change INT NULL,
    
    -- Top landing pages per questa keyword
    top_pages JSON NULL COMMENT '[{url, clicks, position}]',
    
    -- Country breakdown
    country_data JSON NULL COMMENT '{IT: {clicks, position}, US: {...}}',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE CASCADE,
    
    UNIQUE KEY uk_keyword_date (keyword_id, date),
    INDEX idx_project_date (project_id, date)
) ENGINE=InnoDB;

-- =============================================
-- GA4 DATA
-- =============================================

-- Dati GA4 per landing page
CREATE TABLE st_ga4_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    -- Dimensioni
    date DATE NOT NULL,
    landing_page VARCHAR(2000) NOT NULL,
    source_medium VARCHAR(255) DEFAULT 'google / organic',
    country VARCHAR(10) NULL,
    device_category VARCHAR(50) NULL,
    
    -- Metriche traffico
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    
    -- Metriche engagement
    avg_session_duration DECIMAL(10,2) DEFAULT 0 COMMENT 'Secondi',
    bounce_rate DECIMAL(5,4) DEFAULT 0,
    engagement_rate DECIMAL(5,4) DEFAULT 0,
    
    -- Metriche e-commerce
    add_to_carts INT DEFAULT 0,
    begin_checkouts INT DEFAULT 0,
    purchases INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    
    UNIQUE KEY uk_ga4_data (project_id, date, landing_page(255), source_medium, country, device_category),
    INDEX idx_project_date (project_id, date),
    INDEX idx_project_page (project_id, landing_page(255))
) ENGINE=InnoDB;

-- Aggregati giornalieri GA4 (organico totale)
CREATE TABLE st_ga4_daily (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    date DATE NOT NULL,
    
    -- Traffico
    sessions INT DEFAULT 0,
    users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    
    -- Engagement
    avg_session_duration DECIMAL(10,2) DEFAULT 0,
    bounce_rate DECIMAL(5,4) DEFAULT 0,
    engagement_rate DECIMAL(5,4) DEFAULT 0,
    
    -- E-commerce
    add_to_carts INT DEFAULT 0,
    begin_checkouts INT DEFAULT 0,
    purchases INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,
    
    -- Variazioni vs giorno precedente
    sessions_change INT NULL,
    revenue_change DECIMAL(12,2) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    
    UNIQUE KEY uk_project_date (project_id, date),
    INDEX idx_date (date)
) ENGINE=InnoDB;

-- =============================================
-- KEYWORD-REVENUE ATTRIBUTION
-- =============================================

-- Attribuzione revenue per keyword
CREATE TABLE st_keyword_revenue (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    keyword_id INT NULL COMMENT 'NULL = keyword non tracciata',
    
    date DATE NOT NULL,
    query VARCHAR(500) NOT NULL,
    landing_page VARCHAR(2000) NOT NULL,
    
    -- Dati GSC
    clicks INT DEFAULT 0,
    impressions INT DEFAULT 0,
    position DECIMAL(5,2) DEFAULT 0,
    
    -- Dati GA4 attribuiti
    sessions INT DEFAULT 0,
    revenue DECIMAL(12,2) DEFAULT 0,
    purchases INT DEFAULT 0,
    add_to_carts INT DEFAULT 0,
    
    -- Metriche calcolate
    revenue_per_click DECIMAL(10,4) NULL,
    conversion_rate DECIMAL(5,4) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE SET NULL,
    
    INDEX idx_project_date (project_id, date),
    INDEX idx_keyword (keyword_id, date),
    INDEX idx_revenue (project_id, date, revenue DESC)
) ENGINE=InnoDB;

-- =============================================
-- ALERT SYSTEM
-- =============================================

-- Configurazione alert per progetto
CREATE TABLE st_alert_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL UNIQUE,
    
    -- Alert posizione
    position_alert_enabled BOOLEAN DEFAULT TRUE,
    position_threshold INT DEFAULT 5 COMMENT 'Alert se variazione >= N',
    position_alert_keywords ENUM('all', 'tracked', 'high_priority') DEFAULT 'tracked',
    
    -- Alert traffico
    traffic_alert_enabled BOOLEAN DEFAULT TRUE,
    traffic_drop_threshold INT DEFAULT 20 COMMENT 'Alert se calo >= N%',
    
    -- Alert revenue
    revenue_alert_enabled BOOLEAN DEFAULT TRUE,
    revenue_drop_threshold INT DEFAULT 20 COMMENT 'Alert se calo >= N%',
    
    -- Alert anomalie AI
    anomaly_alert_enabled BOOLEAN DEFAULT TRUE,
    
    -- Notifiche
    email_enabled BOOLEAN DEFAULT TRUE,
    email_frequency ENUM('immediate', 'daily_digest', 'weekly_digest') DEFAULT 'daily_digest',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Log alert generati
CREATE TABLE st_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    -- Tipo e severità
    alert_type ENUM(
        'position_drop',
        'position_gain',
        'traffic_drop',
        'traffic_spike',
        'revenue_drop',
        'revenue_spike',
        'keyword_lost',
        'keyword_new',
        'anomaly'
    ) NOT NULL,
    severity ENUM('critical', 'warning', 'info') NOT NULL,
    
    -- Dettagli
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    
    -- Contesto
    keyword_id INT NULL,
    query VARCHAR(500) NULL,
    page VARCHAR(2000) NULL,
    
    -- Valori
    previous_value DECIMAL(12,2) NULL,
    current_value DECIMAL(12,2) NULL,
    change_percent DECIMAL(5,2) NULL,
    
    -- AI insight (opzionale)
    ai_analysis TEXT NULL,
    ai_suggestion TEXT NULL,
    
    -- Stato
    status ENUM('new', 'read', 'actioned', 'dismissed') DEFAULT 'new',
    read_at TIMESTAMP NULL,
    
    -- Email
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES st_keywords(id) ON DELETE SET NULL,
    
    INDEX idx_project_status (project_id, status),
    INDEX idx_project_type (project_id, alert_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =============================================
-- AI REPORTS
-- =============================================

-- Report AI generati
CREATE TABLE st_ai_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    -- Tipo report
    report_type ENUM(
        'weekly_digest',
        'monthly_executive',
        'keyword_analysis',
        'revenue_attribution',
        'anomaly_detection',
        'custom'
    ) NOT NULL,
    
    -- Periodo analizzato
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    
    -- Contenuto
    title VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL COMMENT 'Executive summary breve',
    content LONGTEXT NOT NULL COMMENT 'Report completo markdown/HTML',
    
    -- Metriche incluse (per reference)
    metrics_snapshot JSON NULL COMMENT 'Snapshot dati al momento generazione',
    
    -- Costi
    credits_used INT NOT NULL,
    
    -- Email
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    email_recipients JSON NULL,
    
    -- Scheduling
    is_scheduled BOOLEAN DEFAULT FALSE COMMENT 'TRUE se generato da cron',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    
    INDEX idx_project_type (project_id, report_type),
    INDEX idx_project_date (project_id, created_at)
) ENGINE=InnoDB;

-- =============================================
-- SYNC LOG
-- =============================================

-- Log sync dati
CREATE TABLE st_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    
    sync_type ENUM('gsc', 'ga4', 'full') NOT NULL,
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    
    -- Risultati
    records_fetched INT DEFAULT 0,
    records_inserted INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    
    -- Stato
    status ENUM('running', 'completed', 'failed', 'partial') NOT NULL,
    error_message TEXT NULL,
    
    -- Timing
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    duration_seconds INT NULL,
    
    FOREIGN KEY (project_id) REFERENCES st_projects(id) ON DELETE CASCADE,
    
    INDEX idx_project (project_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;
```

---

## GOOGLE SEARCH CONSOLE SERVICE

```php
<?php
// services/GscService.php

class GscService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    
    public function __construct()
    {
        $this->clientId = Settings::get('gsc_client_id');
        $this->clientSecret = Settings::get('gsc_client_secret');
        $this->redirectUri = Settings::get('gsc_redirect_uri');
    }
    
    /**
     * URL per OAuth consent
     */
    public function getAuthUrl(int $projectId): string
    {
        $state = base64_encode(json_encode([
            'project_id' => $projectId,
            'module' => 'seo-tracking'
        ]));
        
        return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state
        ]);
    }
    
    /**
     * Scambia authorization code per tokens
     */
    public function exchangeCode(string $code): array
    {
        $response = $this->httpPost('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ]);
        
        return $response;
    }
    
    /**
     * Refresh token se scaduto
     */
    public function refreshTokenIfNeeded(GscConnection $connection): string
    {
        if ($connection->token_expires_at > time() + 300) {
            return $connection->access_token;
        }
        
        $response = $this->httpPost('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token'
        ]);
        
        $connection->update([
            'access_token' => $response['access_token'],
            'token_expires_at' => time() + $response['expires_in']
        ]);
        
        return $response['access_token'];
    }
    
    /**
     * Lista proprietà disponibili
     */
    public function listProperties(string $accessToken): array
    {
        return $this->httpGet(
            'https://www.googleapis.com/webmasters/v3/sites',
            $accessToken
        );
    }
    
    /**
     * Fetch dati performance (principale)
     */
    public function fetchPerformanceData(
        int $projectId,
        string $startDate,
        string $endDate,
        array $dimensions = ['date', 'query', 'page', 'country', 'device']
    ): array {
        $connection = GscConnection::findByProject($projectId);
        $accessToken = $this->refreshTokenIfNeeded($connection);
        
        $allRows = [];
        $startRow = 0;
        $rowLimit = 25000;
        
        do {
            $response = $this->httpPost(
                "https://www.googleapis.com/webmasters/v3/sites/" . 
                urlencode($connection->property_url) . "/searchAnalytics/query",
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'dimensions' => $dimensions,
                    'rowLimit' => $rowLimit,
                    'startRow' => $startRow,
                    'dataState' => 'final'
                ],
                $accessToken
            );
            
            $rows = $response['rows'] ?? [];
            $allRows = array_merge($allRows, $rows);
            $startRow += $rowLimit;
            
        } while (count($rows) === $rowLimit);
        
        return $allRows;
    }
    
    /**
     * Sync giornaliero completo
     */
    public function syncDailyData(int $projectId): array
    {
        $project = Project::find($projectId);
        $connection = GscConnection::findByProject($projectId);
        
        if (!$connection || !$connection->is_active) {
            throw new Exception('GSC non connesso');
        }
        
        // Ultimi 3 giorni (dati GSC hanno delay 2-3 giorni)
        $endDate = date('Y-m-d', strtotime('-2 days'));
        $startDate = date('Y-m-d', strtotime('-4 days'));
        
        $rows = $this->fetchPerformanceData($projectId, $startDate, $endDate);
        
        $inserted = 0;
        $updated = 0;
        
        foreach ($rows as $row) {
            $data = [
                'project_id' => $projectId,
                'date' => $row['keys'][0],
                'query' => $row['keys'][1],
                'page' => $row['keys'][2],
                'country' => $row['keys'][3] ?? 'all',
                'device' => $row['keys'][4] ?? 'ALL',
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'ctr' => $row['ctr'],
                'position' => $row['position']
            ];
            
            $result = GscData::upsert($data);
            $result === 'inserted' ? $inserted++ : $updated++;
        }
        
        // Match con keyword tracciate
        $this->matchTrackedKeywords($projectId);
        
        return [
            'rows_processed' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated
        ];
    }
    
    /**
     * Sync storico completo (16 mesi)
     */
    public function syncFullHistory(int $projectId): array
    {
        $endDate = date('Y-m-d', strtotime('-2 days'));
        $startDate = date('Y-m-d', strtotime('-16 months'));
        
        $totalRows = 0;
        
        // Processa per mese per evitare timeout
        $currentStart = $startDate;
        while ($currentStart < $endDate) {
            $currentEnd = min(
                date('Y-m-d', strtotime($currentStart . ' +1 month')),
                $endDate
            );
            
            $rows = $this->fetchPerformanceData($projectId, $currentStart, $currentEnd);
            
            foreach ($rows as $row) {
                GscData::upsert([
                    'project_id' => $projectId,
                    'date' => $row['keys'][0],
                    'query' => $row['keys'][1],
                    'page' => $row['keys'][2],
                    'country' => $row['keys'][3] ?? 'all',
                    'device' => $row['keys'][4] ?? 'ALL',
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'ctr' => $row['ctr'],
                    'position' => $row['position']
                ]);
            }
            
            $totalRows += count($rows);
            $currentStart = $currentEnd;
            
            // Yield progress per SSE
            yield [
                'progress' => (strtotime($currentStart) - strtotime($startDate)) / 
                             (strtotime($endDate) - strtotime($startDate)) * 100,
                'rows' => $totalRows
            ];
        }
        
        $this->matchTrackedKeywords($projectId);
        
        return ['total_rows' => $totalRows];
    }
    
    /**
     * Match query GSC con keyword tracciate
     */
    private function matchTrackedKeywords(int $projectId): void
    {
        $keywords = Keyword::getByProject($projectId);
        
        foreach ($keywords as $keyword) {
            // Update keyword_id su gsc_data dove query matcha
            GscData::updateKeywordMatch($projectId, $keyword->id, $keyword->keyword);
            
            // Aggiorna cache su keyword
            $latestData = GscData::getLatestForKeyword($keyword->id);
            if ($latestData) {
                $keyword->update([
                    'last_position' => $latestData['avg_position'],
                    'last_clicks' => $latestData['total_clicks'],
                    'last_impressions' => $latestData['total_impressions'],
                    'last_ctr' => $latestData['avg_ctr'],
                    'last_updated_at' => now()
                ]);
            }
        }
    }
    
    private function httpPost(string $url, array $data, ?string $accessToken = null): array
    {
        $headers = ['Content-Type: application/json'];
        if ($accessToken) {
            $headers[] = "Authorization: Bearer {$accessToken}";
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function httpGet(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$accessToken}"]
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

---

## GA4 SERVICE (Service Account)

```php
<?php
// services/Ga4Service.php

class Ga4Service
{
    private array $serviceAccount;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;
    
    /**
     * Inizializza con Service Account JSON
     */
    public function initWithConnection(Ga4Connection $connection): void
    {
        $this->serviceAccount = json_decode(
            decrypt($connection->service_account_json),
            true
        );
    }
    
    /**
     * Genera JWT e ottiene access token
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiry > time() + 60) {
            return $this->accessToken;
        }
        
        $header = base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ]));
        
        $now = time();
        $claims = base64_encode(json_encode([
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ]));
        
        $signature = '';
        openssl_sign(
            "$header.$claims",
            $signature,
            $this->serviceAccount['private_key'],
            'SHA256'
        );
        $signature = base64_encode($signature);
        
        $jwt = "$header.$claims.$signature";
        
        $response = $this->httpPost('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);
        
        $this->accessToken = $response['access_token'];
        $this->tokenExpiry = time() + $response['expires_in'];
        
        return $this->accessToken;
    }
    
    /**
     * Verifica connessione
     */
    public function testConnection(string $propertyId): bool
    {
        try {
            $token = $this->getAccessToken();
            $response = $this->runReport($propertyId, [
                'dateRanges' => [['startDate' => '7daysAgo', 'endDate' => 'today']],
                'metrics' => [['name' => 'sessions']]
            ], $token);
            return isset($response['rows']) || isset($response['rowCount']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Fetch dati organici
     */
    public function fetchOrganicData(
        int $projectId,
        string $startDate,
        string $endDate
    ): array {
        $connection = Ga4Connection::findByProject($projectId);
        $this->initWithConnection($connection);
        
        $token = $this->getAccessToken();
        
        $response = $this->runReport($connection->property_id, [
            'dateRanges' => [[
                'startDate' => $startDate,
                'endDate' => $endDate
            ]],
            'dimensions' => [
                ['name' => 'date'],
                ['name' => 'landingPage'],
                ['name' => 'sessionSourceMedium'],
                ['name' => 'country'],
                ['name' => 'deviceCategory']
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'totalUsers'],
                ['name' => 'newUsers'],
                ['name' => 'averageSessionDuration'],
                ['name' => 'bounceRate'],
                ['name' => 'engagementRate'],
                ['name' => 'addToCarts'],
                ['name' => 'checkouts'],
                ['name' => 'purchases'],
                ['name' => 'purchaseRevenue']
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'sessionSourceMedium',
                    'stringFilter' => [
                        'matchType' => 'EXACT',
                        'value' => 'google / organic'
                    ]
                ]
            ],
            'limit' => 100000
        ], $token);
        
        return $this->parseReportResponse($response);
    }
    
    /**
     * Sync giornaliero
     */
    public function syncDailyData(int $projectId): array
    {
        // GA4 ha delay di ~24-48 ore
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-3 days'));
        
        $rows = $this->fetchOrganicData($projectId, $startDate, $endDate);
        
        $inserted = 0;
        foreach ($rows as $row) {
            $result = Ga4Data::upsert([
                'project_id' => $projectId,
                'date' => $row['date'],
                'landing_page' => $row['landingPage'],
                'source_medium' => $row['sessionSourceMedium'],
                'country' => $row['country'],
                'device_category' => $row['deviceCategory'],
                'sessions' => $row['sessions'],
                'users' => $row['totalUsers'],
                'new_users' => $row['newUsers'],
                'avg_session_duration' => $row['averageSessionDuration'],
                'bounce_rate' => $row['bounceRate'],
                'engagement_rate' => $row['engagementRate'],
                'add_to_carts' => $row['addToCarts'],
                'begin_checkouts' => $row['checkouts'],
                'purchases' => $row['purchases'],
                'revenue' => $row['purchaseRevenue']
            ]);
            if ($result) $inserted++;
        }
        
        // Aggiorna aggregati giornalieri
        $this->updateDailyAggregates($projectId, $startDate, $endDate);
        
        return ['rows_processed' => count($rows), 'inserted' => $inserted];
    }
    
    /**
     * Aggiorna tabella aggregati giornalieri
     */
    private function updateDailyAggregates(int $projectId, string $startDate, string $endDate): void
    {
        $sql = "
            INSERT INTO st_ga4_daily 
                (project_id, date, sessions, users, new_users, avg_session_duration,
                 bounce_rate, engagement_rate, add_to_carts, begin_checkouts, purchases, revenue)
            SELECT 
                project_id,
                date,
                SUM(sessions),
                SUM(users),
                SUM(new_users),
                AVG(avg_session_duration),
                AVG(bounce_rate),
                AVG(engagement_rate),
                SUM(add_to_carts),
                SUM(begin_checkouts),
                SUM(purchases),
                SUM(revenue)
            FROM st_ga4_data
            WHERE project_id = ? AND date BETWEEN ? AND ?
            GROUP BY project_id, date
            ON DUPLICATE KEY UPDATE
                sessions = VALUES(sessions),
                users = VALUES(users),
                new_users = VALUES(new_users),
                avg_session_duration = VALUES(avg_session_duration),
                bounce_rate = VALUES(bounce_rate),
                engagement_rate = VALUES(engagement_rate),
                add_to_carts = VALUES(add_to_carts),
                begin_checkouts = VALUES(begin_checkouts),
                purchases = VALUES(purchases),
                revenue = VALUES(revenue)
        ";
        
        Database::execute($sql, [$projectId, $startDate, $endDate]);
    }
    
    private function runReport(string $propertyId, array $request, string $token): array
    {
        $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer {$token}"
            ]
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    private function parseReportResponse(array $response): array
    {
        $rows = [];
        $dimensionHeaders = array_column($response['dimensionHeaders'] ?? [], 'name');
        $metricHeaders = array_column($response['metricHeaders'] ?? [], 'name');
        
        foreach ($response['rows'] ?? [] as $row) {
            $parsed = [];
            foreach ($row['dimensionValues'] as $i => $dim) {
                $parsed[$dimensionHeaders[$i]] = $dim['value'];
            }
            foreach ($row['metricValues'] as $i => $metric) {
                $parsed[$metricHeaders[$i]] = $metric['value'];
            }
            $rows[] = $parsed;
        }
        
        return $rows;
    }
    
    private function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

---

## REVENUE ATTRIBUTION SERVICE

```php
<?php
// services/RevenueAttributor.php

class RevenueAttributor
{
    /**
     * Calcola attribuzione revenue per keyword
     * Logica: match GSC query → GSC page → GA4 landing_page
     */
    public function calculateAttribution(int $projectId, string $date): void
    {
        // Prendi dati GSC del giorno
        $gscData = GscData::getByProjectDate($projectId, $date);
        
        // Prendi dati GA4 del giorno (già filtrati per organic)
        $ga4Data = Ga4Data::getByProjectDate($projectId, $date);
        
        // Indicizza GA4 per landing page
        $ga4ByPage = [];
        foreach ($ga4Data as $row) {
            $page = $this->normalizePage($row['landing_page']);
            if (!isset($ga4ByPage[$page])) {
                $ga4ByPage[$page] = [
                    'sessions' => 0,
                    'revenue' => 0,
                    'purchases' => 0,
                    'add_to_carts' => 0
                ];
            }
            $ga4ByPage[$page]['sessions'] += $row['sessions'];
            $ga4ByPage[$page]['revenue'] += $row['revenue'];
            $ga4ByPage[$page]['purchases'] += $row['purchases'];
            $ga4ByPage[$page]['add_to_carts'] += $row['add_to_carts'];
        }
        
        // Per ogni query GSC, attribuisci revenue proporzionalmente ai click
        foreach ($gscData as $gsc) {
            $page = $this->normalizePage($gsc['page']);
            $ga4 = $ga4ByPage[$page] ?? null;
            
            if (!$ga4 || $ga4['sessions'] == 0) {
                continue;
            }
            
            // Attribuzione proporzionale: click GSC / sessions GA4
            $attributionRatio = min($gsc['clicks'] / $ga4['sessions'], 1);
            
            $attributedRevenue = $ga4['revenue'] * $attributionRatio;
            $attributedPurchases = round($ga4['purchases'] * $attributionRatio);
            $attributedAddToCarts = round($ga4['add_to_carts'] * $attributionRatio);
            
            KeywordRevenue::upsert([
                'project_id' => $projectId,
                'keyword_id' => $gsc['keyword_id'],
                'date' => $date,
                'query' => $gsc['query'],
                'landing_page' => $gsc['page'],
                'clicks' => $gsc['clicks'],
                'impressions' => $gsc['impressions'],
                'position' => $gsc['position'],
                'sessions' => round($gsc['clicks']), // Approssimazione
                'revenue' => $attributedRevenue,
                'purchases' => $attributedPurchases,
                'add_to_carts' => $attributedAddToCarts,
                'revenue_per_click' => $gsc['clicks'] > 0 ? $attributedRevenue / $gsc['clicks'] : 0,
                'conversion_rate' => $gsc['clicks'] > 0 ? $attributedPurchases / $gsc['clicks'] : 0
            ]);
        }
    }
    
    /**
     * Get top keyword per revenue
     */
    public function getTopKeywordsByRevenue(
        int $projectId, 
        string $startDate, 
        string $endDate, 
        int $limit = 20
    ): array {
        return KeywordRevenue::query()
            ->select([
                'query',
                'keyword_id',
                'SUM(clicks) as total_clicks',
                'SUM(impressions) as total_impressions',
                'AVG(position) as avg_position',
                'SUM(revenue) as total_revenue',
                'SUM(purchases) as total_purchases',
                'SUM(revenue) / NULLIF(SUM(clicks), 0) as revenue_per_click'
            ])
            ->where('project_id', $projectId)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('query', 'keyword_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Normalizza path pagina per matching GSC ↔ GA4
     */
    private function normalizePage(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        
        // Rimuovi trailing slash
        $path = rtrim($path, '/');
        if (empty($path)) $path = '/';
        
        // Rimuovi query string
        return $path;
    }
}
```

---

## ALERT SERVICE

```php
<?php
// services/AlertService.php

class AlertService
{
    private AiService $ai;
    private EmailService $email;
    
    public function __construct()
    {
        $this->ai = new AiService();
        $this->email = new EmailService();
    }
    
    /**
     * Check tutti gli alert per un progetto
     */
    public function checkAlerts(int $projectId): array
    {
        $settings = AlertSettings::findByProject($projectId);
        if (!$settings) return [];
        
        $alerts = [];
        
        if ($settings->position_alert_enabled) {
            $alerts = array_merge($alerts, $this->checkPositionAlerts($projectId, $settings));
        }
        
        if ($settings->traffic_alert_enabled) {
            $alerts = array_merge($alerts, $this->checkTrafficAlerts($projectId, $settings));
        }
        
        if ($settings->revenue_alert_enabled) {
            $alerts = array_merge($alerts, $this->checkRevenueAlerts($projectId, $settings));
        }
        
        // Salva alert
        foreach ($alerts as $alert) {
            Alert::create($alert);
        }
        
        // Invia notifiche se configurato
        if ($settings->email_enabled && !empty($alerts)) {
            $this->sendAlertNotifications($projectId, $alerts, $settings);
        }
        
        return $alerts;
    }
    
    /**
     * Check variazioni posizione keyword
     */
    private function checkPositionAlerts(int $projectId, AlertSettings $settings): array
    {
        $alerts = [];
        $threshold = $settings->position_threshold;
        
        // Prendi keyword da monitorare in base a settings
        $keywords = match($settings->position_alert_keywords) {
            'all' => Keyword::getByProject($projectId),
            'high_priority' => Keyword::getByProject($projectId, ['priority' => 'high']),
            default => Keyword::getByProject($projectId)
        };
        
        foreach ($keywords as $keyword) {
            $positions = KeywordPositions::getLastTwo($keyword->id);
            
            if (count($positions) < 2) continue;
            
            $current = $positions[0];
            $previous = $positions[1];
            $change = $previous->avg_position - $current->avg_position; // Positivo = miglioramento
            
            if (abs($change) >= $threshold) {
                $alertType = $change > 0 ? 'position_gain' : 'position_drop';
                $severity = abs($change) >= 10 ? 'critical' : 'warning';
                
                $alert = [
                    'project_id' => $projectId,
                    'alert_type' => $alertType,
                    'severity' => $severity,
                    'keyword_id' => $keyword->id,
                    'query' => $keyword->keyword,
                    'title' => $alertType === 'position_gain' 
                        ? "📈 Miglioramento: {$keyword->keyword}"
                        : "📉 Calo: {$keyword->keyword}",
                    'message' => sprintf(
                        "La keyword '%s' è %s da posizione %.1f a %.1f (%+.1f)",
                        $keyword->keyword,
                        $alertType === 'position_gain' ? 'salita' : 'scesa',
                        $previous->avg_position,
                        $current->avg_position,
                        $change
                    ),
                    'previous_value' => $previous->avg_position,
                    'current_value' => $current->avg_position,
                    'change_percent' => ($change / $previous->avg_position) * 100
                ];
                
                // Aggiungi AI insight se configurato
                if ($settings->anomaly_alert_enabled) {
                    $alert['ai_analysis'] = $this->generateAlertInsight($projectId, $keyword, $change);
                }
                
                $alerts[] = $alert;
            }
        }
        
        return $alerts;
    }
    
    /**
     * Check variazioni traffico
     */
    private function checkTrafficAlerts(int $projectId, AlertSettings $settings): array
    {
        $alerts = [];
        $threshold = $settings->traffic_drop_threshold;
        
        // Confronta ultimi 7 giorni vs 7 giorni precedenti
        $current = Ga4Daily::getSum($projectId, '-7 days', '-1 day');
        $previous = Ga4Daily::getSum($projectId, '-14 days', '-8 days');
        
        if (!$previous || $previous->sessions == 0) return $alerts;
        
        $changePercent = (($current->sessions - $previous->sessions) / $previous->sessions) * 100;
        
        if (abs($changePercent) >= $threshold) {
            $alertType = $changePercent > 0 ? 'traffic_spike' : 'traffic_drop';
            $severity = abs($changePercent) >= 30 ? 'critical' : 'warning';
            
            $alerts[] = [
                'project_id' => $projectId,
                'alert_type' => $alertType,
                'severity' => $severity,
                'title' => $alertType === 'traffic_spike'
                    ? "📈 Traffico in aumento"
                    : "📉 Calo traffico organico",
                'message' => sprintf(
                    "Sessioni organiche: %s → %s (%+.1f%%) negli ultimi 7 giorni",
                    number_format($previous->sessions),
                    number_format($current->sessions),
                    $changePercent
                ),
                'previous_value' => $previous->sessions,
                'current_value' => $current->sessions,
                'change_percent' => $changePercent
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check variazioni revenue
     */
    private function checkRevenueAlerts(int $projectId, AlertSettings $settings): array
    {
        $alerts = [];
        $threshold = $settings->revenue_drop_threshold;
        
        $current = Ga4Daily::getSum($projectId, '-7 days', '-1 day');
        $previous = Ga4Daily::getSum($projectId, '-14 days', '-8 days');
        
        if (!$previous || $previous->revenue == 0) return $alerts;
        
        $changePercent = (($current->revenue - $previous->revenue) / $previous->revenue) * 100;
        
        if (abs($changePercent) >= $threshold) {
            $alertType = $changePercent > 0 ? 'revenue_spike' : 'revenue_drop';
            $severity = abs($changePercent) >= 30 ? 'critical' : 'warning';
            
            $alerts[] = [
                'project_id' => $projectId,
                'alert_type' => $alertType,
                'severity' => $severity,
                'title' => $alertType === 'revenue_spike'
                    ? "💰 Revenue in aumento"
                    : "⚠️ Calo revenue organico",
                'message' => sprintf(
                    "Revenue organico: €%s → €%s (%+.1f%%) negli ultimi 7 giorni",
                    number_format($previous->revenue, 2),
                    number_format($current->revenue, 2),
                    $changePercent
                ),
                'previous_value' => $previous->revenue,
                'current_value' => $current->revenue,
                'change_percent' => $changePercent
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Genera insight AI per alert
     */
    private function generateAlertInsight(int $projectId, Keyword $keyword, float $positionChange): string
    {
        // Recupera contesto
        $revenueData = KeywordRevenue::getByKeyword($keyword->id, '-30 days', 'today');
        $totalRevenue = array_sum(array_column($revenueData, 'revenue'));
        
        $prompt = <<<PROMPT
        Sei un SEO analyst. Analizza questa variazione di posizionamento:
        
        KEYWORD: {$keyword->keyword}
        VARIAZIONE POSIZIONE: {$positionChange} (positivo = miglioramento)
        REVENUE ULTIMI 30 GIORNI: €{$totalRevenue}
        URL TARGET: {$keyword->target_url}
        
        Fornisci in 2-3 frasi:
        1. Possibile causa della variazione
        2. Impatto stimato sul business
        3. Azione suggerita
        
        Sii conciso e pratico.
        PROMPT;
        
        $response = $this->ai->complete([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 300,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);
        
        return $response['content'][0]['text'];
    }
    
    /**
     * Invia notifiche email
     */
    private function sendAlertNotifications(int $projectId, array $alerts, AlertSettings $settings): void
    {
        $project = Project::find($projectId);
        $emails = $project->notification_emails ?? [];
        
        if (empty($emails)) return;
        
        if ($settings->email_frequency === 'immediate') {
            foreach ($alerts as $alert) {
                $this->email->sendAlertEmail($emails, $project, $alert);
            }
        } else {
            // Per daily/weekly digest, salva e invia in batch
            AlertLog::createBatch($projectId, $alerts);
        }
    }
}
```

---

## AI ANALYZER SERVICE

```php
<?php
// services/AiAnalyzer.php

class AiAnalyzer
{
    private AiService $ai;
    
    // Costi crediti
    const CREDITS = [
        'weekly_digest' => 5,
        'monthly_executive' => 15,
        'keyword_analysis' => 5,
        'revenue_attribution' => 8,
        'anomaly_detection' => 5,
        'custom' => 10
    ];
    
    public function __construct()
    {
        $this->ai = new AiService();
    }
    
    /**
     * Genera Weekly Digest
     */
    public function generateWeeklyDigest(int $projectId): AiReport
    {
        $project = Project::find($projectId);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));
        $prevEndDate = date('Y-m-d', strtotime('-8 days'));
        
        // Raccogli dati
        $data = $this->collectWeeklyData($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        
        $prompt = $this->buildWeeklyDigestPrompt($project, $data);
        
        $response = $this->ai->complete([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 2000,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);
        
        $content = $response['content'][0]['text'];
        
        // Salva report
        $report = AiReport::create([
            'project_id' => $projectId,
            'report_type' => 'weekly_digest',
            'date_from' => $startDate,
            'date_to' => $endDate,
            'title' => "Weekly Digest - " . date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate)),
            'summary' => $this->extractSummary($content),
            'content' => $content,
            'metrics_snapshot' => json_encode($data),
            'credits_used' => self::CREDITS['weekly_digest'],
            'is_scheduled' => true
        ]);
        
        // Scala crediti
        CreditService::consume(
            $project->user_id,
            self::CREDITS['weekly_digest'],
            'weekly_digest',
            'seo-tracking'
        );
        
        return $report;
    }
    
    /**
     * Genera Monthly Executive Report
     */
    public function generateMonthlyExecutive(int $projectId): AiReport
    {
        $project = Project::find($projectId);
        
        // Mese precedente
        $endDate = date('Y-m-t', strtotime('-1 month'));
        $startDate = date('Y-m-01', strtotime('-1 month'));
        
        // Mese ancora prima per confronto
        $prevEndDate = date('Y-m-t', strtotime('-2 months'));
        $prevStartDate = date('Y-m-01', strtotime('-2 months'));
        
        $data = $this->collectMonthlyData($projectId, $startDate, $endDate, $prevStartDate, $prevEndDate);
        
        $prompt = $this->buildMonthlyExecutivePrompt($project, $data);
        
        $response = $this->ai->complete([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4000,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);
        
        $content = $response['content'][0]['text'];
        
        $report = AiReport::create([
            'project_id' => $projectId,
            'report_type' => 'monthly_executive',
            'date_from' => $startDate,
            'date_to' => $endDate,
            'title' => "Monthly Report - " . date('F Y', strtotime($startDate)),
            'summary' => $this->extractSummary($content),
            'content' => $content,
            'metrics_snapshot' => json_encode($data),
            'credits_used' => self::CREDITS['monthly_executive'],
            'is_scheduled' => true
        ]);
        
        CreditService::consume(
            $project->user_id,
            self::CREDITS['monthly_executive'],
            'monthly_executive',
            'seo-tracking'
        );
        
        return $report;
    }
    
    /**
     * Analisi keyword on-demand
     */
    public function analyzeKeywords(int $projectId, ?array $keywordIds = null): AiReport
    {
        $project = Project::find($projectId);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));
        
        $keywords = $keywordIds 
            ? Keyword::whereIn('id', $keywordIds)->get()
            : Keyword::getByProject($projectId);
        
        $data = $this->collectKeywordData($projectId, $keywords, $startDate, $endDate);
        
        $prompt = $this->buildKeywordAnalysisPrompt($project, $data);
        
        $response = $this->ai->complete([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 3000,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);
        
        $content = $response['content'][0]['text'];
        
        $report = AiReport::create([
            'project_id' => $projectId,
            'report_type' => 'keyword_analysis',
            'date_from' => $startDate,
            'date_to' => $endDate,
            'title' => "Keyword Analysis - " . date('d/m/Y'),
            'summary' => $this->extractSummary($content),
            'content' => $content,
            'credits_used' => self::CREDITS['keyword_analysis'],
            'is_scheduled' => false
        ]);
        
        CreditService::consume(
            $project->user_id,
            self::CREDITS['keyword_analysis'],
            'keyword_analysis',
            'seo-tracking'
        );
        
        return $report;
    }
    
    /**
     * Analisi revenue attribution
     */
    public function analyzeRevenueAttribution(int $projectId): AiReport
    {
        $project = Project::find($projectId);
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-30 days'));
        
        $data = $this->collectRevenueData($projectId, $startDate, $endDate);
        
        $prompt = $this->buildRevenueAttributionPrompt($project, $data);
        
        $response = $this->ai->complete([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 3000,
            'messages' => [['role' => 'user', 'content' => $prompt]]
        ]);
        
        $content = $response['content'][0]['text'];
        
        $report = AiReport::create([
            'project_id' => $projectId,
            'report_type' => 'revenue_attribution',
            'date_from' => $startDate,
            'date_to' => $endDate,
            'title' => "Revenue Attribution - " . date('d/m/Y'),
            'summary' => $this->extractSummary($content),
            'content' => $content,
            'credits_used' => self::CREDITS['revenue_attribution'],
            'is_scheduled' => false
        ]);
        
        CreditService::consume(
            $project->user_id,
            self::CREDITS['revenue_attribution'],
            'revenue_attribution',
            'seo-tracking'
        );
        
        return $report;
    }
    
    // ============ PROMPT BUILDERS ============
    
    private function buildWeeklyDigestPrompt(Project $project, array $data): string
    {
        return <<<PROMPT
        Sei un SEO Manager senior. Prepara un WEEKLY DIGEST per il tuo responsabile.
        Il report deve essere chiaro, conciso e orientato all'azione.
        
        PROGETTO: {$project->name}
        DOMINIO: {$project->domain}
        PERIODO: {$data['period']}
        
        === METRICHE TRAFFICO ===
        Sessioni: {$data['traffic']['sessions']} ({$data['traffic']['sessions_change']}% vs settimana prec.)
        Click organici: {$data['traffic']['clicks']} ({$data['traffic']['clicks_change']}%)
        Impressions: {$data['traffic']['impressions']} ({$data['traffic']['impressions_change']}%)
        
        === METRICHE REVENUE ===
        Revenue organico: €{$data['revenue']['total']} ({$data['revenue']['change']}%)
        Transazioni: {$data['revenue']['purchases']}
        AOV: €{$data['revenue']['aov']}
        
        === TOP 10 VARIAZIONI POSIZIONE ===
        MIGLIORAMENTI:
        {$data['position_changes']['improvements']}
        
        PEGGIORAMENTI:
        {$data['position_changes']['declines']}
        
        === TOP 5 KEYWORD PER REVENUE ===
        {$data['top_keywords_revenue']}
        
        === ANOMALIE RILEVATE ===
        {$data['anomalies']}
        
        ---
        
        FORMATO RICHIESTO:
        
        ## 📊 Sintesi Settimanale
        [2-3 frasi riassuntive dell'andamento]
        
        ## ✅ Cosa è andato bene
        [Bullet points con dati specifici]
        
        ## ⚠️ Punti di attenzione
        [Bullet points con dati specifici]
        
        ## 🎯 Azioni consigliate (prioritizzate)
        1. [Azione urgente]
        2. [Azione importante]
        3. [Azione da pianificare]
        
        ## 📈 Keyword da monitorare
        [Lista keyword critiche con motivazione]
        
        ---
        Tono: professionale ma accessibile.
        Lingua: italiano.
        Non usare tecnicismi senza spiegarli.
        PROMPT;
    }
    
    private function buildMonthlyExecutivePrompt(Project $project, array $data): string
    {
        return <<<PROMPT
        Sei un SEO Director. Prepara un MONTHLY EXECUTIVE REPORT per il management.
        Il report deve dare una visione strategica, non solo tattica.
        
        PROGETTO: {$project->name}
        DOMINIO: {$project->domain}
        MESE: {$data['month']}
        
        === OVERVIEW TRAFFICO ===
        Sessioni totali: {$data['traffic']['sessions']} ({$data['traffic']['sessions_change_mom']}% MoM)
        Utenti unici: {$data['traffic']['users']}
        Click GSC: {$data['traffic']['clicks']} ({$data['traffic']['clicks_change_mom']}% MoM)
        Impressions: {$data['traffic']['impressions']}
        CTR medio: {$data['traffic']['avg_ctr']}%
        
        === OVERVIEW REVENUE ===
        Revenue organico: €{$data['revenue']['total']} ({$data['revenue']['change_mom']}% MoM)
        % sul totale e-commerce: {$data['revenue']['organic_share']}%
        Transazioni: {$data['revenue']['purchases']}
        Revenue per sessione: €{$data['revenue']['rps']}
        
        === POSIZIONAMENTO ===
        Keyword in Top 3: {$data['positions']['top3']}
        Keyword in Top 10: {$data['positions']['top10']}
        Keyword in Top 20: {$data['positions']['top20']}
        Variazione media posizione: {$data['positions']['avg_change']}
        
        === TOP 20 KEYWORD PER REVENUE ===
        {$data['top_keywords']}
        
        === KEYWORD WINNER (maggiori miglioramenti) ===
        {$data['winners']}
        
        === KEYWORD LOSER (maggiori peggioramenti) ===
        {$data['losers']}
        
        === TREND ULTIMI 3 MESI ===
        {$data['trend_3m']}
        
        ---
        
        FORMATO RICHIESTO:
        
        ## 📋 Executive Summary
        [Paragrafo di 4-5 frasi per il management. Focus su risultati business.]
        
        ## 📊 Performance del Mese
        ### Traffico Organico
        [Analisi con dati]
        
        ### Revenue Organico
        [Analisi con dati e contesto business]
        
        ### Posizionamento
        [Trend principali]
        
        ## 🏆 Successi del Mese
        [Top 3 risultati positivi con impatto quantificato]
        
        ## 🚨 Criticità e Rischi
        [Problemi identificati con potenziale impatto]
        
        ## 📈 Opportunità
        [2-3 opportunità di crescita identificate dai dati]
        
        ## 🎯 Raccomandazioni Strategiche
        ### Breve termine (prossimo mese)
        1. ...
        2. ...
        
        ### Medio termine (prossimo trimestre)
        1. ...
        2. ...
        
        ## 📅 KPI da Monitorare
        [Metriche chiave da seguire il prossimo mese]
        
        ---
        Tono: executive, orientato ai risultati business.
        Lingua: italiano.
        Evita tecnicismi non necessari.
        Quantifica sempre l'impatto.
        PROMPT;
    }
    
    private function buildKeywordAnalysisPrompt(Project $project, array $data): string
    {
        return <<<PROMPT
        Sei un SEO Specialist. Analizza nel dettaglio le performance delle keyword.
        
        PROGETTO: {$project->name}
        PERIODO: {$data['period']}
        KEYWORD ANALIZZATE: {$data['keyword_count']}
        
        === DISTRIBUZIONE POSIZIONI ===
        {$data['position_distribution']}
        
        === DETTAGLIO KEYWORD ===
        {$data['keywords_detail']}
        
        === CORRELAZIONI KEYWORD-REVENUE ===
        {$data['keyword_revenue_correlation']}
        
        ---
        
        FORMATO RICHIESTO:
        
        ## 📊 Panoramica Posizionamento
        [Analisi distribuzione posizioni]
        
        ## 🌟 Keyword Star (alto valore, buone posizioni)
        [Lista keyword strategiche con raccomandazioni]
        
        ## 🎯 Keyword Opportunity (potenziale inespresso)
        [Keyword con buone impressions ma posizione migliorabile]
        
        ## ⚠️ Keyword a Rischio
        [Keyword in calo da monitorare]
        
        ## 💰 Keyword Revenue Driver
        [Keyword che generano più revenue, analisi]
        
        ## 🔧 Azioni Suggerite
        [Piano d'azione per keyword prioritarie]
        
        Lingua: italiano.
        Sii specifico con numeri e azioni concrete.
        PROMPT;
    }
    
    private function buildRevenueAttributionPrompt(Project $project, array $data): string
    {
        return <<<PROMPT
        Sei un SEO Analyst con focus su e-commerce. Analizza l'attribuzione revenue.
        
        PROGETTO: {$project->name}
        PERIODO: {$data['period']}
        
        === REVENUE TOTALE ORGANICO ===
        Totale: €{$data['total_revenue']}
        Transazioni: {$data['total_purchases']}
        
        === TOP 30 KEYWORD PER REVENUE ===
        {$data['top_keywords_revenue']}
        
        === TOP 20 LANDING PAGE PER REVENUE ===
        {$data['top_pages_revenue']}
        
        === REVENUE PER CATEGORIA KEYWORD ===
        {$data['revenue_by_category']}
        
        === METRICHE CONVERSIONE ===
        {$data['conversion_metrics']}
        
        ---
        
        FORMATO RICHIESTO:
        
        ## 💰 Executive Summary Revenue
        [Sintesi impatto SEO sul revenue]
        
        ## 🔑 Keyword Money-Maker
        [Analisi keyword più profittevoli]
        
        ## 📄 Landing Page Performance
        [Pagine che convertono meglio/peggio]
        
        ## 📈 Opportunità Revenue
        [Dove c'è margine di crescita]
        
        ## 🎯 Raccomandazioni
        [Azioni per massimizzare revenue organico]
        
        Lingua: italiano.
        Focus su impatto business e ROI.
        PROMPT;
    }
    
    // ============ DATA COLLECTORS ============
    
    private function collectWeeklyData(int $projectId, string $start, string $end, string $prevStart, string $prevEnd): array
    {
        // Implementazione raccolta dati...
        return [];
    }
    
    private function collectMonthlyData(int $projectId, string $start, string $end, string $prevStart, string $prevEnd): array
    {
        // Implementazione raccolta dati...
        return [];
    }
    
    private function collectKeywordData(int $projectId, $keywords, string $start, string $end): array
    {
        // Implementazione raccolta dati...
        return [];
    }
    
    private function collectRevenueData(int $projectId, string $start, string $end): array
    {
        // Implementazione raccolta dati...
        return [];
    }
    
    private function extractSummary(string $content): string
    {
        // Estrai prime 2-3 frasi come summary
        $sentences = preg_split('/(?<=[.!?])\s+/', strip_tags($content));
        return implode(' ', array_slice($sentences, 0, 3));
    }
}
```

---

## CRON JOBS

```php
<?php
// cron/daily-sync.php
// Eseguire ogni giorno alle 06:00

require_once __DIR__ . '/../../../public/index.php';

$projects = Project::where('sync_enabled', true)->get();

foreach ($projects as $project) {
    try {
        $project->update(['sync_status' => 'running']);
        
        // Sync GSC
        if ($project->gsc_connected) {
            $gscService = new GscService();
            $gscService->syncDailyData($project->id);
        }
        
        // Sync GA4
        if ($project->ga4_connected) {
            $ga4Service = new Ga4Service();
            $ga4Service->syncDailyData($project->id);
        }
        
        // Revenue attribution
        if ($project->gsc_connected && $project->ga4_connected) {
            $attributor = new RevenueAttributor();
            $dates = [
                date('Y-m-d', strtotime('-3 days')),
                date('Y-m-d', strtotime('-2 days')),
                date('Y-m-d', strtotime('-1 day'))
            ];
            foreach ($dates as $date) {
                $attributor->calculateAttribution($project->id, $date);
            }
        }
        
        // Check alert
        $alertService = new AlertService();
        $alertService->checkAlerts($project->id);
        
        $project->update([
            'sync_status' => 'completed',
            'last_sync_at' => now()
        ]);
        
        SyncLog::create([
            'project_id' => $project->id,
            'sync_type' => 'full',
            'date_from' => date('Y-m-d', strtotime('-4 days')),
            'date_to' => date('Y-m-d', strtotime('-1 day')),
            'status' => 'completed'
        ]);
        
    } catch (Exception $e) {
        $project->update(['sync_status' => 'error']);
        SyncLog::create([
            'project_id' => $project->id,
            'sync_type' => 'full',
            'status' => 'failed',
            'error_message' => $e->getMessage()
        ]);
    }
}
```

```php
<?php
// cron/weekly-report.php
// Eseguire ogni lunedì alle 08:00

require_once __DIR__ . '/../../../public/index.php';

$projects = Project::where('ai_reports_enabled', true)
    ->where('weekly_report_day', date('N')) // 1 = Lunedì
    ->get();

foreach ($projects as $project) {
    try {
        $analyzer = new AiAnalyzer();
        $report = $analyzer->generateWeeklyDigest($project->id);
        
        // Invia email
        if (!empty($project->notification_emails)) {
            $emailService = new EmailService();
            $emailService->sendReport(
                $project->notification_emails,
                $project,
                $report
            );
            
            $report->update([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_recipients' => $project->notification_emails
            ]);
        }
        
    } catch (Exception $e) {
        Log::error("Weekly report failed for project {$project->id}: " . $e->getMessage());
    }
}
```

```php
<?php
// cron/monthly-report.php
// Eseguire il 1° di ogni mese alle 09:00

require_once __DIR__ . '/../../../public/index.php';

$projects = Project::where('ai_reports_enabled', true)
    ->where('monthly_report_day', date('j')) // Giorno del mese
    ->get();

foreach ($projects as $project) {
    try {
        $analyzer = new AiAnalyzer();
        $report = $analyzer->generateMonthlyExecutive($project->id);
        
        if (!empty($project->notification_emails)) {
            $emailService = new EmailService();
            $emailService->sendReport(
                $project->notification_emails,
                $project,
                $report
            );
            
            $report->update([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_recipients' => $project->notification_emails
            ]);
        }
        
    } catch (Exception $e) {
        Log::error("Monthly report failed for project {$project->id}: " . $e->getMessage());
    }
}
```

---

## MODULE.JSON

```json
{
    "name": "SEO Position Tracking",
    "slug": "seo-tracking",
    "version": "1.0.0",
    "description": "Monitoraggio posizionamento keyword, traffico organico e revenue con GSC + GA4 e analisi AI",
    "icon": "chart-bar",
    "menu_order": 15,
    "requires": {
        "php": ">=8.0",
        "services": ["ai"]
    },
    "credits": {
        "gsc_full_sync": {
            "cost": 10,
            "description": "Sync storico completo GSC (16 mesi)"
        },
        "weekly_digest": {
            "cost": 5,
            "description": "Report settimanale AI"
        },
        "monthly_executive": {
            "cost": 15,
            "description": "Report mensile executive AI"
        },
        "keyword_analysis": {
            "cost": 5,
            "description": "Analisi keyword AI on-demand"
        },
        "revenue_attribution": {
            "cost": 8,
            "description": "Analisi revenue attribution AI"
        },
        "anomaly_detection": {
            "cost": 5,
            "description": "Rilevamento anomalie AI"
        },
        "alert_ai_insight": {
            "cost": 1,
            "description": "Insight AI per alert"
        }
    },
    "admin_settings": [
        {
            "key": "gsc_client_id",
            "label": "Google OAuth Client ID",
            "type": "text",
            "admin_only": true
        },
        {
            "key": "gsc_client_secret",
            "label": "Google OAuth Client Secret",
            "type": "password",
            "admin_only": true
        },
        {
            "key": "gsc_redirect_uri",
            "label": "Google OAuth Redirect URI",
            "type": "text",
            "admin_only": true,
            "readonly": true
        }
    ],
    "routes_prefix": "/seo-tracking"
}
```

---

## ROUTES

```php
<?php
// routes.php

$router->group('/seo-tracking', function($router) {
    
    // Dashboard modulo
    $router->get('/', 'ProjectController@index');
    
    // Progetti
    $router->get('/projects/create', 'ProjectController@create');
    $router->post('/projects/store', 'ProjectController@store');
    $router->get('/projects/{id}', 'DashboardController@index');
    $router->get('/projects/{id}/settings', 'ProjectController@settings');
    $router->post('/projects/{id}/settings', 'ProjectController@updateSettings');
    $router->delete('/projects/{id}', 'ProjectController@destroy');
    
    // Connessioni
    $router->get('/projects/{id}/gsc/connect', 'GscController@connect');
    $router->get('/gsc/callback', 'GscController@callback');
    $router->get('/projects/{id}/gsc/properties', 'GscController@properties');
    $router->post('/projects/{id}/gsc/select-property', 'GscController@selectProperty');
    $router->post('/projects/{id}/gsc/sync', 'GscController@sync');
    $router->post('/projects/{id}/gsc/sync-full', 'GscController@syncFull');
    $router->delete('/projects/{id}/gsc/disconnect', 'GscController@disconnect');
    
    $router->get('/projects/{id}/ga4/connect', 'Ga4Controller@connect');
    $router->post('/projects/{id}/ga4/upload-credentials', 'Ga4Controller@uploadCredentials');
    $router->post('/projects/{id}/ga4/select-property', 'Ga4Controller@selectProperty');
    $router->post('/projects/{id}/ga4/sync', 'Ga4Controller@sync');
    $router->delete('/projects/{id}/ga4/disconnect', 'Ga4Controller@disconnect');
    
    // Dashboard e dati
    $router->get('/projects/{id}/dashboard', 'DashboardController@index');
    $router->get('/projects/{id}/keywords', 'DashboardController@keywords');
    $router->get('/projects/{id}/pages', 'DashboardController@pages');
    $router->get('/projects/{id}/revenue', 'DashboardController@revenue');
    
    // Keyword tracking
    $router->get('/projects/{id}/keywords/tracked', 'KeywordController@tracked');
    $router->get('/projects/{id}/keywords/all', 'KeywordController@all');
    $router->get('/projects/{id}/keywords/add', 'KeywordController@add');
    $router->post('/projects/{id}/keywords/store', 'KeywordController@store');
    $router->post('/projects/{id}/keywords/import', 'KeywordController@import');
    $router->get('/projects/{id}/keywords/{keywordId}', 'KeywordController@detail');
    $router->post('/projects/{id}/keywords/{keywordId}/update', 'KeywordController@update');
    $router->delete('/projects/{id}/keywords/{keywordId}', 'KeywordController@destroy');
    
    // Alert
    $router->get('/projects/{id}/alerts', 'AlertController@index');
    $router->get('/projects/{id}/alerts/settings', 'AlertController@settings');
    $router->post('/projects/{id}/alerts/settings', 'AlertController@updateSettings');
    $router->get('/projects/{id}/alerts/history', 'AlertController@history');
    $router->post('/projects/{id}/alerts/{alertId}/read', 'AlertController@markRead');
    $router->post('/projects/{id}/alerts/{alertId}/dismiss', 'AlertController@dismiss');
    
    // Report AI
    $router->get('/projects/{id}/reports', 'ReportController@index');
    $router->get('/projects/{id}/reports/weekly', 'ReportController@weekly');
    $router->get('/projects/{id}/reports/monthly', 'ReportController@monthly');
    $router->post('/projects/{id}/reports/generate/weekly', 'AiController@generateWeekly');
    $router->post('/projects/{id}/reports/generate/monthly', 'AiController@generateMonthly');
    $router->post('/projects/{id}/reports/generate/keywords', 'AiController@generateKeywordAnalysis');
    $router->post('/projects/{id}/reports/generate/revenue', 'AiController@generateRevenueAttribution');
    $router->get('/projects/{id}/reports/{reportId}', 'ReportController@show');
    $router->get('/projects/{id}/reports/{reportId}/download', 'ReportController@download');
    
    // Export
    $router->get('/projects/{id}/export/keywords', 'ExportController@keywords');
    $router->get('/projects/{id}/export/positions', 'ExportController@positions');
    $router->get('/projects/{id}/export/revenue', 'ExportController@revenue');
    
    // API Ajax
    $router->get('/api/projects/{id}/chart/traffic', 'ApiController@trafficChart');
    $router->get('/api/projects/{id}/chart/revenue', 'ApiController@revenueChart');
    $router->get('/api/projects/{id}/chart/positions', 'ApiController@positionsChart');
    $router->get('/api/projects/{id}/sync-status', 'ApiController@syncStatus');
});
```

---

## UI - VIEWS PRINCIPALI

### Dashboard Progetto
```
┌─────────────────────────────────────────────────────────────────┐
│ [← Progetti]  Project Name                    [⚙️ Settings]     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐            │
│  │ 12.5K   │  │ 245K    │  │ €45.2K  │  │ 156     │            │
│  │ Click   │  │ Impr.   │  │ Revenue │  │ KW Top10│            │
│  │ +5.2%   │  │ +12%    │  │ +8.3%   │  │ +12     │            │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘            │
│                                                                 │
│  [📊 Traffic] [🔑 Keywords] [📄 Pages] [💰 Revenue]  ← Tabs     │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │            📈 Grafico Trend (30 giorni)                  │  │
│  │     Click ━━━  Impressions ━━━  Revenue ━━━              │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌─────────────────────────┐  ┌─────────────────────────────┐  │
│  │ 🔥 Top Keyword          │  │ ⚠️ Alert Recenti            │  │
│  │ (by clicks)             │  │                             │  │
│  │ 1. keyword abc  pos 2.3 │  │ 📉 "keyword x" -5 pos       │  │
│  │ 2. keyword def  pos 4.1 │  │ 📈 Traffic +25% weekly      │  │
│  │ 3. keyword ghi  pos 1.8 │  │ 💰 Revenue drop -15%        │  │
│  └─────────────────────────┘  └─────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ 🤖 AI Reports                              [Genera ▼]    │  │
│  │                                                          │  │
│  │ • Weekly Digest (14/01) ............... [📄 Leggi]       │  │
│  │ • Monthly Executive (Gen 2025) ....... [📄 Leggi]        │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Keyword Tracking
```
┌─────────────────────────────────────────────────────────────────┐
│ 🔑 Keyword Tracking                          [+ Aggiungi] [Import]│
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Filtri: [Tutti ▼] [Gruppo ▼] [Priorità ▼]  🔍 Cerca...        │
│                                                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Keyword          │ Pos │ Δ   │ Click │ Impr  │ Rev   │ 🔔 │ │
│  ├──────────────────┼─────┼─────┼───────┼───────┼───────┼────┤ │
│  │ scarpe running   │ 3.2 │ +1.2│ 1,234 │ 45K   │ €5.2K │ ✓  │ │
│  │ scarpe nike      │ 5.4 │ -2.1│ 892   │ 32K   │ €3.8K │ ✓  │ │
│  │ sneakers uomo    │ 8.7 │ +0.3│ 456   │ 28K   │ €2.1K │ ✓  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                 │
│  Showing 1-25 of 156 keywords                     [< 1 2 3 >]  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Dettaglio Keyword
```
┌─────────────────────────────────────────────────────────────────┐
│ [← Keywords]  "scarpe running uomo"              [✏️] [🗑️]      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Gruppo: Running    Priorità: ⭐⭐⭐ Alta    Alert: ✅ Attivo    │
│  Target URL: /scarpe-running-uomo/                              │
│                                                                 │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐            │
│  │ Pos 3.2 │  │ 1,234   │  │ 45,230  │  │ €5,234  │            │
│  │ -0.5 ↑  │  │ Click   │  │ Impr    │  │ Revenue │            │
│  │         │  │ +12%    │  │ +8%     │  │ +15%    │            │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘            │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │         📈 Trend Posizione (90 giorni)                   │  │
│  │    Posizione ━━━                                         │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌─────────────────────────┐  ┌─────────────────────────────┐  │
│  │ 📄 Top Landing Pages    │  │ 🌍 Performance per Country  │  │
│  │                         │  │                             │  │
│  │ /scarpe-running/  62%   │  │ 🇮🇹 IT: pos 2.8 | 890 click │  │
│  │ /running-uomo/    28%   │  │ 🇨🇭 CH: pos 4.2 | 234 click │  │
│  │ /nike-running/    10%   │  │ 🇦🇹 AT: pos 5.1 | 110 click │  │
│  └─────────────────────────┘  └─────────────────────────────┘  │
│                                                                 │
│  [🤖 Analizza con AI]                                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## ADMIN SETTINGS

Sezione in Admin > Impostazioni > SEO Position Tracking:

```
┌─────────────────────────────────────────────────────────────────┐
│ SEO Position Tracking - Configurazione                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  === Google OAuth (per GSC) ===                                │
│                                                                 │
│  Client ID:                                                     │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ xxx.apps.googleusercontent.com                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Client Secret:                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ••••••••••••••••••                                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Redirect URI (da configurare in Google Console):              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ https://tuodominio.com/seo-tracking/gsc/callback   [📋] │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  [ℹ️ Come configurare le credenziali Google]                    │
│                                                                 │
│                                              [Salva Impostazioni]│
└─────────────────────────────────────────────────────────────────┘
```

---

## SISTEMA CREDITI - RIEPILOGO

| Azione | Crediti | Trigger |
|--------|---------|---------|
| Sync storico GSC (16 mesi) | 10 | Una tantum |
| Sync giornaliero | 0 | Automatico (cron) |
| Weekly Digest AI | 5 | Automatico/manuale |
| Monthly Executive AI | 15 | Automatico/manuale |
| Keyword Analysis AI | 5 | Manuale |
| Revenue Attribution AI | 8 | Manuale |
| Anomaly Detection AI | 5 | Manuale |
| AI Insight per Alert | 1 | Per ogni alert |

---

## CHECKLIST IMPLEMENTAZIONE

### Fase 1 - Core
- [ ] Struttura modulo + module.json + routes.php
- [ ] Database schema (tutte le tabelle)
- [ ] Models base
- [ ] CRUD Progetti
- [ ] Integrazione GSC (OAuth + sync)
- [ ] Integrazione GA4 (Service Account + sync)

### Fase 2 - Tracking
- [ ] CRUD Keyword tracking
- [ ] Import keyword (CSV/manual)
- [ ] Calcolo posizioni aggregate
- [ ] Dashboard con grafici
- [ ] Revenue attribution

### Fase 3 - Alert
- [ ] Sistema alert configurabile
- [ ] Check automatici (cron)
- [ ] Notifiche email
- [ ] Storico alert

### Fase 4 - AI
- [ ] Weekly Digest automatico
- [ ] Monthly Executive automatico
- [ ] Analisi on-demand
- [ ] AI insights per alert
- [ ] Invio email report

### Fase 5 - Polish
- [ ] Export CSV/PDF
- [ ] UI responsive
- [ ] Test end-to-end
- [ ] Documentazione utente

---

## NOTE IMPLEMENTAZIONE

1. **OAuth Security**: Token criptati con `openssl_encrypt`
2. **Rate Limits**: GSC max 1200 query/min, GA4 più permissivo
3. **URL Matching**: Normalizzare sempre (trailing slash, www, case)
4. **Timezone**: Tutti i dati in UTC, conversione in UI
5. **Cron**: Eseguire sync nelle ore notturne per non impattare UX
6. **Retention**: Pulire dati oltre 16 mesi automaticamente
7. **Email**: Usare queue per invii massivi
