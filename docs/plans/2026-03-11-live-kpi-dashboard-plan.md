# Live KPI Dashboard — Google Ads Analyzer

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** I bottoni periodo (7/30/90 giorni, Personalizzato) nella dashboard campagne devono mostrare metriche live da Google Ads API, con cache intelligente e fallback a DB.

**Architecture:** Quando l'utente cambia periodo, un AJAX call chiama un nuovo endpoint `live-kpis` che interroga Google Ads API con una singola query GAQL aggregata. Il risultato è cachato 15 minuti per progetto+periodo. Se l'API fallisce (token scaduto, errore), si usa l'ultima sync dal DB come fallback. I KPI nella view si aggiornano via Alpine.js senza page reload.

**Tech Stack:** PHP 8+, Google Ads API v20 REST (GAQL), Alpine.js, Core\Cache (Symfony FilesystemAdapter)

---

## File Structure

| File | Azione | Responsabilità |
|------|--------|----------------|
| `modules/ads-analyzer/services/LiveKpiService.php` | **Creare** | Servizio: query GAQL aggregata → parse metriche → cache → fallback DB |
| `modules/ads-analyzer/controllers/CampaignController.php` | Modificare | Aggiungere metodo `liveKpis()` (endpoint AJAX) |
| `modules/ads-analyzer/routes.php` | Modificare | Aggiungere route GET `live-kpis` |
| `modules/ads-analyzer/views/campaigns/dashboard.php` | Modificare | Sostituire page reload con AJAX fetch + aggiornamento Alpine.js |

---

## Chunk 1: Backend — LiveKpiService + Endpoint

### Task 1: Creare LiveKpiService

**Files:**
- Create: `modules/ads-analyzer/services/LiveKpiService.php`

- [ ] **Step 1: Creare il servizio LiveKpiService**

```php
<?php

namespace Modules\AdsAnalyzer\Services;

use Services\GoogleAdsService;
use Core\Cache;
use Core\Database;
use Core\Logger;
use Modules\AdsAnalyzer\Models\Campaign;
use Modules\AdsAnalyzer\Models\Sync;

/**
 * LiveKpiService — Metriche live da Google Ads API con cache
 *
 * Esegue una singola query GAQL aggregata per ottenere KPI di tutte le campagne
 * in un dato periodo. Risultati cachati 15 min per progetto+periodo.
 * Fallback a ultima sync DB se API non disponibile.
 */
class LiveKpiService
{
    private const CACHE_TTL = 900; // 15 minuti

    private GoogleAdsService $gadsService;
    private int $projectId;

    public function __construct(GoogleAdsService $gadsService, int $projectId)
    {
        $this->gadsService = $gadsService;
        $this->projectId = $projectId;
    }

    /**
     * Ottieni KPI aggregati per il periodo specificato
     *
     * @return array{source: string, clicks: int, impressions: int, cost: float, conversions: float, ctr: float, avg_cpc: float, campaigns: int}
     */
    public function getKpis(string $dateFrom, string $dateTo): array
    {
        $cacheKey = "ga_live_kpis_{$this->projectId}_{$dateFrom}_{$dateTo}";

        // 1. Prova cache
        try {
            $cached = Cache::get($cacheKey, function () use ($dateFrom, $dateTo) {
                // Cache miss → chiama API
                return $this->fetchFromApi($dateFrom, $dateTo);
            }, self::CACHE_TTL);

            return $cached;
        } catch (\Exception $e) {
            Logger::channel('ads')->warning('LiveKpi cache/API failed, fallback to DB', [
                'project_id' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            // 2. Fallback a DB
            return $this->fetchFromDb();
        }
    }

    /**
     * Query GAQL aggregata — una singola chiamata API per tutti i KPI
     */
    private function fetchFromApi(string $dateFrom, string $dateTo): array
    {
        $gaql = "SELECT metrics.clicks, metrics.impressions, metrics.ctr, " .
                "metrics.average_cpc, metrics.cost_micros, " .
                "metrics.conversions, metrics.conversions_value " .
                "FROM campaign " .
                "WHERE segments.date BETWEEN '{$dateFrom}' AND '{$dateTo}' " .
                "AND campaign.status = 'ENABLED'";

        $response = $this->gadsService->searchStream($gaql);

        // Parse e aggrega risultati
        $totals = [
            'clicks' => 0,
            'impressions' => 0,
            'cost' => 0.0,
            'conversions' => 0.0,
            'conversion_value' => 0.0,
            'campaigns' => 0,
        ];

        // searchStream ritorna array con chiave 'results' (o vuoto)
        // La risposta è un array di batch, ciascuno con 'results'
        $results = [];
        if (isset($response['results'])) {
            $results = $response['results'];
        } elseif (is_array($response)) {
            // searchStream: array di batch [{results: [...]}, ...]
            foreach ($response as $batch) {
                if (isset($batch['results'])) {
                    $results = array_merge($results, $batch['results']);
                }
            }
        }

        // Conta campagne uniche e aggrega metriche
        $campaignIds = [];
        foreach ($results as $row) {
            $metrics = $row['metrics'] ?? [];
            $totals['clicks'] += (int)($metrics['clicks'] ?? 0);
            $totals['impressions'] += (int)($metrics['impressions'] ?? 0);
            $totals['cost'] += ((int)($metrics['costMicros'] ?? 0)) / 1_000_000;
            $totals['conversions'] += (float)($metrics['conversions'] ?? 0);
            $totals['conversion_value'] += (float)($metrics['conversionsValue'] ?? 0);

            // Conta campagne uniche
            $cId = $row['campaign']['resourceName'] ?? null;
            if ($cId && !isset($campaignIds[$cId])) {
                $campaignIds[$cId] = true;
            }
        }

        $totals['campaigns'] = count($campaignIds);

        // Calcola metriche derivate
        $ctr = $totals['impressions'] > 0
            ? round(($totals['clicks'] / $totals['impressions']) * 100, 2)
            : 0.0;

        $avgCpc = $totals['clicks'] > 0
            ? round($totals['cost'] / $totals['clicks'], 2)
            : 0.0;

        return [
            'source' => 'api',
            'clicks' => $totals['clicks'],
            'impressions' => $totals['impressions'],
            'cost' => round($totals['cost'], 2),
            'conversions' => round($totals['conversions'], 1),
            'conversion_value' => round($totals['conversion_value'], 2),
            'ctr' => $ctr,
            'avg_cpc' => $avgCpc,
            'campaigns' => $totals['campaigns'],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    /**
     * Fallback: metriche dall'ultima sync completata nel DB
     */
    private function fetchFromDb(): array
    {
        $latestSync = Sync::getLatestByProject($this->projectId);
        if (!$latestSync) {
            return [
                'source' => 'none',
                'clicks' => 0,
                'impressions' => 0,
                'cost' => 0.0,
                'conversions' => 0.0,
                'conversion_value' => 0.0,
                'ctr' => 0.0,
                'avg_cpc' => 0.0,
                'campaigns' => 0,
                'date_from' => null,
                'date_to' => null,
            ];
        }

        $stats = Campaign::getStatsByRun($latestSync['id']);

        return [
            'source' => 'db',
            'clicks' => (int)($stats['total_clicks'] ?? 0),
            'impressions' => (int)($stats['total_impressions'] ?? 0),
            'cost' => round((float)($stats['total_cost'] ?? 0), 2),
            'conversions' => round((float)($stats['total_conversions'] ?? 0), 1),
            'conversion_value' => round((float)($stats['total_value'] ?? 0), 2),
            'ctr' => round((float)($stats['avg_ctr'] ?? 0), 2),
            'avg_cpc' => round((float)($stats['avg_cpc'] ?? 0), 2),
            'campaigns' => (int)($stats['total_campaigns'] ?? 0),
            'date_from' => $latestSync['date_range_start'] ?? null,
            'date_to' => $latestSync['date_range_end'] ?? null,
        ];
    }
}
```

- [ ] **Step 2: Verificare sintassi**

Run: `php -l modules/ads-analyzer/services/LiveKpiService.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add modules/ads-analyzer/services/LiveKpiService.php
git commit -m "feat(ads-analyzer): add LiveKpiService for real-time Google Ads KPIs"
```

---

### Task 2: Aggiungere endpoint liveKpis nel controller

**Files:**
- Modify: `modules/ads-analyzer/controllers/CampaignController.php`
- Modify: `modules/ads-analyzer/routes.php`

- [ ] **Step 1: Aggiungere metodo liveKpis() al CampaignController**

Aggiungere dopo il metodo `syncStatus()` (circa riga 330). Il metodo segue il pattern AJAX standard del progetto (GR #17, #23, #24):

```php
/**
 * KPI live da Google Ads API (AJAX)
 *
 * Endpoint chiamato dal frontend al cambio periodo.
 * Usa LiveKpiService: API con cache 15min → fallback DB.
 */
public function liveKpis(int $projectId): void
{
    header('Content-Type: application/json');

    try {
        $user = Auth::user();
        $project = Project::findAccessible($user['id'], $projectId);

        if (!$project || ($project['type'] ?? 'negative-kw') !== 'campaign') {
            http_response_code(400);
            echo json_encode(['error' => 'Progetto non valido']);
            exit;
        }

        $customerId = $project['google_ads_customer_id'] ?? '';
        if (empty($customerId)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nessun account Google Ads collegato']);
            exit;
        }

        // Validazione date
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato date non valido']);
            exit;
        }

        // Limite massimo 90 giorni
        $daysDiff = (strtotime($dateTo) - strtotime($dateFrom)) / 86400;
        if ($daysDiff > 90 || $daysDiff < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Intervallo date non valido (max 90 giorni)']);
            exit;
        }

        $loginCustomerId = isset($project['login_customer_id']) ? $project['login_customer_id'] : '';
        $gadsService = new GoogleAdsService($user['id'], $customerId, $loginCustomerId);

        $service = new \Modules\AdsAnalyzer\Services\LiveKpiService($gadsService, $projectId);
        $kpis = $service->getKpis($dateFrom, $dateTo);

        echo json_encode([
            'success' => true,
            'kpis' => $kpis,
        ]);
        exit;

    } catch (\Exception $e) {
        Logger::channel('ads')->error("LiveKPI error", [
            'project_id' => $projectId,
            'error' => $e->getMessage(),
        ]);

        http_response_code(500);
        echo json_encode(['error' => 'Errore nel recupero metriche: ' . $e->getMessage()]);
        exit;
    }
}
```

- [ ] **Step 2: Aggiungere route in routes.php**

Aggiungere **PRIMA** della route `campaigns/{syncId}` (prima di riga 216) per evitare conflitti:

```php
// KPI live da Google Ads API (AJAX)
Router::get('/ads-analyzer/projects/{id}/campaigns/live-kpis', function ($id) {
    Middleware::auth();
    $controller = new CampaignController();
    return $controller->liveKpis((int) $id);
});
```

- [ ] **Step 3: Verificare sintassi**

Run: `php -l modules/ads-analyzer/controllers/CampaignController.php`
Run: `php -l modules/ads-analyzer/routes.php`
Expected: entrambi `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add modules/ads-analyzer/controllers/CampaignController.php modules/ads-analyzer/routes.php
git commit -m "feat(ads-analyzer): add live-kpis AJAX endpoint with cache + DB fallback"
```

---

## Chunk 2: Frontend — Alpine.js AJAX + aggiornamento KPI

### Task 3: Modificare dashboard.php — sostituire page reload con AJAX

**Files:**
- Modify: `modules/ads-analyzer/views/campaigns/dashboard.php`

L'obiettivo è:
1. Al click su bottone periodo → AJAX fetch a `live-kpis` → aggiornare KPI cards senza reload
2. Mostrare stato loading (skeleton) durante fetch
3. Indicatore "LIVE" o "DB" per la fonte dati
4. Mantenere funzionalità sync completa esistente (invariata)

- [ ] **Step 1: Modificare Alpine.js date picker — sostituire window.location con AJAX**

Nella sezione date picker (righe 70-91), sostituire il `x-data` inline con riferimento al `dashboardManager()` centralizzato. Cambiare `setRange()` e `applyCustom()` per chiamare AJAX:

**Vecchio** (riga 76-91):
```javascript
setRange(days) {
    this.dateRange = days;
    this.showCustom = false;
    const to = new Date();
    const from = new Date();
    from.setDate(from.getDate() - days);
    this.dateFrom = from.toISOString().split('T')[0];
    this.dateTo = to.toISOString().split('T')[0];
    window.location.href = `?date_from=${this.dateFrom}&date_to=${this.dateTo}`;
},
applyCustom() {
    if (this.dateFrom && this.dateTo) {
        window.location.href = `?date_from=${this.dateFrom}&date_to=${this.dateTo}`;
    }
}
```

**Nuovo** — spostare il date picker dentro il `dashboardManager()` x-data principale (rimuovere il `x-data` nested dal div date picker). Aggiungere al `dashboardManager()`:

```javascript
function dashboardManager() {
    return {
        // --- Existing properties ---
        autoEvalEnabled: <?= $autoEvalEnabled ? 'true' : 'false' ?>,
        togglingAutoEval: false,
        syncing: false,
        syncMessage: '',
        syncError: false,

        // --- NEW: Live KPI properties ---
        dateRange: '<?= (strtotime($dateTo) - strtotime($dateFrom)) / 86400 ?>',
        dateFrom: '<?= e($dateFrom) ?>',
        dateTo: '<?= e($dateTo) ?>',
        showCustom: false,
        loadingKpis: false,
        kpiSource: '<?= !empty($campaignSyncs) ? "db" : "none" ?>',

        // KPI values (inizializzati da PHP)
        kpiClicks: <?= (int)($latestStats['total_clicks'] ?? 0) ?>,
        kpiImpressions: <?= (int)($latestStats['total_impressions'] ?? 0) ?>,
        kpiCost: <?= round((float)($latestStats['total_cost'] ?? 0), 2) ?>,
        kpiConversions: <?= round((float)($latestStats['total_conversions'] ?? 0), 1) ?>,
        kpiCtr: <?= round((float)($latestStats['avg_ctr'] ?? 0), 2) ?>,
        kpiAvgCpc: <?= round((float)($latestStats['avg_cpc'] ?? 0), 2) ?>,
        kpiCampaigns: <?= $totalCampaigns ?>,

        setRange(days) {
            this.dateRange = days;
            this.showCustom = false;
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - days);
            this.dateFrom = from.toISOString().split('T')[0];
            this.dateTo = to.toISOString().split('T')[0];
            this.fetchLiveKpis();
        },

        applyCustom() {
            if (this.dateFrom && this.dateTo) {
                this.fetchLiveKpis();
            }
        },

        async fetchLiveKpis() {
            this.loadingKpis = true;
            try {
                const url = `<?= url('/ads-analyzer/projects/' . $project['id'] . '/campaigns/live-kpis') ?>?date_from=${this.dateFrom}&date_to=${this.dateTo}`;
                const resp = await fetch(url);

                if (!resp.ok) {
                    const err = await resp.json().catch(() => ({}));
                    throw new Error(err.error || `Errore server (${resp.status})`);
                }

                const data = await resp.json();
                if (data.success && data.kpis) {
                    const k = data.kpis;
                    this.kpiClicks = k.clicks;
                    this.kpiImpressions = k.impressions;
                    this.kpiCost = k.cost;
                    this.kpiConversions = k.conversions;
                    this.kpiCtr = k.ctr;
                    this.kpiAvgCpc = k.avg_cpc;
                    this.kpiCampaigns = k.campaigns;
                    this.kpiSource = k.source; // 'api', 'db', 'none'
                }

                // Aggiorna URL senza reload (per bookmark / sync button)
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('date_from', this.dateFrom);
                newUrl.searchParams.set('date_to', this.dateTo);
                history.replaceState(null, '', newUrl);

            } catch (err) {
                console.error('LiveKPI fetch failed:', err);
                // Non mostrare errore bloccante — i KPI precedenti restano visibili
            } finally {
                this.loadingKpis = false;
            }
        },

        formatNumber(n) {
            return Number(n).toLocaleString('it-IT');
        },
        formatEuro(n) {
            return Number(n).toLocaleString('it-IT', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '€';
        },
        formatDecimal(n) {
            return Number(n).toLocaleString('it-IT', {minimumFractionDigits: 1, maximumFractionDigits: 1});
        },
        formatPercent(n) {
            return Number(n).toFixed(2) + '%';
        },

        // --- Existing methods (toggleAutoEval, startSync) invariati ---
        // ... (mantieni tutto com'è)
    };
}
```

- [ ] **Step 2: Rimuovere x-data nested dal div date picker**

Il div a riga 70-91 che ha `x-data="{dateRange: ..., setRange(days)...}"` va pulito: rimuovere l'intero `x-data` dal div perché ora le proprietà sono nel `dashboardManager()` parent.

**Vecchio** (riga 70):
```html
<div class="flex flex-wrap items-center gap-3"
     x-data="{
         dateRange: '<?= ... ?>',
         dateFrom: '<?= ... ?>',
         ...
     }">
```

**Nuovo**:
```html
<div class="flex flex-wrap items-center gap-3">
```

- [ ] **Step 3: Rendere KPI Cards dinamiche con Alpine.js**

Sostituire i valori hardcoded PHP nelle 4 KPI cards con binding Alpine.js. Le cards attualmente usano `$latestStats['total_clicks']` etc.

Modificare la sezione KPI cards (righe 294-320 circa). Per ogni card, i valori devono leggere da Alpine:

```php
$kpiCards = [
    ['key' => 'total_clicks', 'alpine' => 'kpiClicks', 'label' => 'Click', 'format' => 'number', ...],
    ['key' => 'total_cost', 'alpine' => 'kpiCost', 'label' => 'Costo', 'format' => 'euro', ...],
    ['key' => 'total_conversions', 'alpine' => 'kpiConversions', 'label' => 'Conversioni', 'format' => 'decimal', ...],
    ['key' => 'avg_ctr', 'alpine' => 'kpiCtr', 'label' => 'CTR', 'format' => 'percent', ...],
];
```

Per ciascuna card, il valore visualizzato passa da:

**Vecchio** (circa riga 319):
```html
<p class="text-2xl font-bold text-slate-900 dark:text-white">
    <?php if ($kpi['format'] === 'number'): ?>
        <?= number_format($kpi['value'], 0, ',', '.') ?>
    <?php elseif ($kpi['format'] === 'euro'): ?>
        <?= number_format($kpi['value'], 2, ',', '.') ?>€
    <?php elseif ($kpi['format'] === 'decimal'): ?>
        <?= number_format($kpi['value'], 1, ',', '.') ?>
    <?php elseif ($kpi['format'] === 'percent'): ?>
        <?= number_format($kpi['value'], 2, ',', '.') ?>%
    <?php endif; ?>
</p>
```

**Nuovo** — valore Alpine con skeleton loading:
```html
<p class="text-2xl font-bold text-slate-900 dark:text-white">
    <template x-if="loadingKpis">
        <span class="inline-block h-7 w-16 bg-slate-200 dark:bg-slate-700 rounded animate-pulse"></span>
    </template>
    <template x-if="!loadingKpis">
        <?php if ($kpi['format'] === 'number'): ?>
            <span x-text="formatNumber(<?= $kpi['alpine'] ?>)"></span>
        <?php elseif ($kpi['format'] === 'euro'): ?>
            <span x-text="formatEuro(<?= $kpi['alpine'] ?>)"></span>
        <?php elseif ($kpi['format'] === 'decimal'): ?>
            <span x-text="formatDecimal(<?= $kpi['alpine'] ?>)"></span>
        <?php elseif ($kpi['format'] === 'percent'): ?>
            <span x-text="formatPercent(<?= $kpi['alpine'] ?>)"></span>
        <?php endif; ?>
    </template>
</p>
```

- [ ] **Step 4: Aggiungere indicatore fonte dati (LIVE/DB)**

Dopo i bottoni periodo, aggiungere un piccolo badge che indica se i dati sono live o da DB:

```html
<!-- Data source indicator -->
<span x-show="kpiSource === 'api'" x-cloak
      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
    Live
</span>
<span x-show="kpiSource === 'db'" x-cloak
      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">
    DB
</span>
```

- [ ] **Step 5: Aggiornare sync button — passa date correnti di Alpine**

Il sync button attualmente usa `<?= e($dateFrom) ?>` hardcoded da PHP. Cambiare per usare i valori Alpine correnti, così se l'utente cambia periodo e poi clicca Sincronizza, la sync usa il periodo selezionato:

**Vecchio** (riga 620-621):
```javascript
formData.append('date_from', '<?= e($dateFrom) ?>');
formData.append('date_to', '<?= e($dateTo) ?>');
```

**Nuovo**:
```javascript
formData.append('date_from', this.dateFrom);
formData.append('date_to', this.dateTo);
```

- [ ] **Step 6: Nascondere delta arrows quando dati sono live**

I delta (confronto con sync precedente) non hanno senso con dati live API (non c'è un "sync precedente" per il confronto). Nasconderli quando `kpiSource === 'api'`:

Wrappare il delta display con:
```html
<template x-if="kpiSource !== 'api'">
    <!-- existing delta arrow markup -->
</template>
```

In alternativa, mantenere i delta solo come informazione dell'ultima valutazione — l'utente capisce il contesto. **Scelta: nascondere con `x-show="kpiSource !== 'api'"` perché semplice e reversibile.**

- [ ] **Step 7: Verificare sintassi**

Run: `php -l modules/ads-analyzer/views/campaigns/dashboard.php`
Expected: `No syntax errors detected`

- [ ] **Step 8: Commit**

```bash
git add modules/ads-analyzer/views/campaigns/dashboard.php
git commit -m "feat(ads-analyzer): live KPI dashboard — AJAX period switching with Alpine.js"
```

---

### Task 4: Test manuale end-to-end

- [ ] **Step 1: Test locale in browser**

1. Aprire `http://localhost/seo-toolkit/ads-analyzer/projects/{id}/campaign-dashboard`
2. Verificare che i KPI si carichino normalmente (dati da DB, badge "DB")
3. Cliccare "30 giorni" → deve apparire skeleton loading → poi dati live con badge "Live"
4. Cliccare "7 giorni" → stessi step, dati diversi
5. Cliccare "90 giorni" → stessi step
6. Verificare "Personalizzato" → date custom → "Applica"
7. Verificare che URL si aggiorni senza reload (history.replaceState)
8. Verificare che "Sincronizza" usi le date correnti del periodo selezionato

- [ ] **Step 2: Test fallback — token invalido**

Se non c'è connessione Google Ads o token scaduto, il badge deve mostrare "DB" e i dati dell'ultima sync.

- [ ] **Step 3: Test produzione**

Deploy e verifica su https://ainstein.it con account NOA Wedding Studio già connesso.

- [ ] **Step 4: Commit finale e deploy**

```bash
git add -A
git commit -m "feat(ads-analyzer): complete live KPI dashboard with period switching"
```

---

## Note implementative

### Gestione Cache

La cache key è `ga_live_kpis_{projectId}_{dateFrom}_{dateTo}`, quindi:
- Periodi diversi = cache diverse (corretto)
- Stessa period button cliccata 2 volte in 15 min = cache hit (veloce)
- Dopo una sync, la cache **non** viene invalidata automaticamente — scade naturalmente in 15 min. Se si vuole invalidazione immediata post-sync, aggiungere `Cache::delete($cacheKey)` nel metodo `sync()` del controller (enhancement futuro).

### Parsing risposta searchStream

`GoogleAdsService::searchStream()` chiama `/googleAds:searchStream` che ritorna un array di batch. Ogni batch ha `results: [...]`. La query `FROM campaign WHERE ...` senza `segments.date` in SELECT ritorna metriche già aggregate per campagna nel periodo. Se invece `segments.date` è nel SELECT, ritorna per-giorno. **Noi NON includiamo segments.date nel SELECT** → aggregazione automatica per campagna.

### CTR: calcolato manualmente

Non usiamo `metrics.ctr` dall'API per il totale perché è per-campagna. Il CTR globale è calcolato come `(total_clicks / total_impressions) * 100` per coerenza.

### Deltas e trend storico

Il trend chart (Chart.js) e i delta confronti restano basati sulle sync DB — non ha senso farli live. Il cambio periodo aggiorna solo le 4 KPI cards + il conteggio campagne.
