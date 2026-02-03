# API Logs - Sistema di Logging Chiamate API

> Documentazione per il sistema centralizzato di logging delle chiamate API esterne.
> Ultimo aggiornamento: 2026-02-03

---

## Overview

Il sistema API Logs traccia tutte le chiamate verso API esterne (DataForSEO, SerpAPI, Serper.dev, Google APIs) per:
- **Debugging**: Visualizzare request/response completi
- **Monitoring**: Statistiche chiamate, errori, rate limits
- **Cost tracking**: Monitorare costi API per provider
- **Audit**: Storico completo delle operazioni

---

## Database Schema

### Tabella `api_logs`

| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| `id` | INT | Primary key |
| `user_id` | INT | ID utente (nullable per cron) |
| `module_slug` | VARCHAR(50) | Modulo che ha fatto la chiamata |
| `provider` | VARCHAR(50) | Nome provider API |
| `endpoint` | VARCHAR(255) | Endpoint chiamato |
| `method` | VARCHAR(10) | Metodo HTTP (GET/POST) |
| `request_payload` | LONGTEXT | JSON request (max 50KB) |
| `response_payload` | LONGTEXT | JSON response (max 50KB) |
| `response_code` | INT | HTTP status code |
| `duration_ms` | INT | Durata chiamata in ms |
| `cost` | DECIMAL(10,6) | Costo USD della chiamata |
| `credits_used` | DECIMAL(10,2) | Crediti piattaforma usati |
| `status` | ENUM | success, error, rate_limited |
| `error_message` | TEXT | Messaggio errore |
| `context` | VARCHAR(500) | Info diagnostiche |
| `ip_address` | VARCHAR(45) | IP richiedente |
| `created_at` | TIMESTAMP | Data/ora chiamata |

### Indici
- `idx_user` - Query per utente
- `idx_provider` - Filtro per provider
- `idx_module` - Filtro per modulo
- `idx_status` - Filtro per stato
- `idx_created` - Ordinamento temporale
- `idx_provider_created` - Report per provider

---

## Provider Supportati

| Provider | Slug | Utilizzo |
|----------|------|----------|
| DataForSEO | `dataforseo` | SERP ranking, keyword volumes, CPC |
| SerpAPI | `serpapi` | SERP ranking (fallback) |
| Serper.dev | `serper` | SERP ranking (fallback) |
| Google Search Console | `google_gsc` | Performance data, coverage |
| Google OAuth | `google_oauth` | Token refresh |
| Google Analytics 4 | `google_ga4` | Traffic data |

---

## ApiLoggerService

### Ubicazione
`services/ApiLoggerService.php`

### Metodo Principale

```php
public static function log(
    string $provider,      // Nome provider (es: 'dataforseo')
    string $endpoint,      // Endpoint (es: '/serp/google/organic/live')
    array $request,        // Payload richiesta
    ?array $response,      // Payload risposta (null se errore)
    int $httpCode,         // HTTP status code
    float $startTime,      // microtime(true) prima della chiamata
    array $options = []    // Opzioni aggiuntive
): void
```

### Opzioni Disponibili

| Opzione | Tipo | Default | Descrizione |
|---------|------|---------|-------------|
| `user_id` | int | Auth::id() | ID utente |
| `module` | string | 'unknown' | Slug modulo |
| `method` | string | 'POST' | Metodo HTTP |
| `cost` | float | 0 | Costo USD |
| `credits` | float | 0 | Crediti usati |
| `context` | string | null | Info diagnostiche |
| `error` | string | null | Messaggio errore custom |

### Metodi Helper

```php
// Per successi semplici
ApiLoggerService::logSuccess($provider, $endpoint, $request, $response,
    $httpCode, $startTime, $module, $cost, $context);

// Per errori
ApiLoggerService::logError($provider, $endpoint, $request, $response,
    $httpCode, $startTime, $module, $errorMessage, $context);

// Statistiche
$stats = ApiLoggerService::getStats($hours = 24, $days = 30);
```

---

## Pattern di Utilizzo

### Esempio Base

```php
use Services\ApiLoggerService;

public function callExternalApi(string $keyword): array
{
    $startTime = microtime(true);  // PRIMA della chiamata

    $payload = ['keyword' => $keyword];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    // Log chiamata
    ApiLoggerService::log('provider', '/endpoint', $payload, $data, $httpCode, $startTime, [
        'module' => 'mio-modulo',
        'cost' => $data['cost'] ?? 0,
        'context' => "keyword={$keyword}",
        'error' => $error ?: null,
    ]);

    return $data;
}
```

### Gestione Paginazione

```php
// Log solo prima pagina + errori per evitare spam
for ($page = 1; $page <= $maxPages; $page++) {
    $startTime = microtime(true);

    // ... chiamata API ...

    if ($page === 1 || $error || $httpCode !== 200) {
        ApiLoggerService::log('provider', '/search', $payload, $data, $httpCode, $startTime, [
            'module' => 'seo-tracking',
            'context' => "keyword={$keyword}, page={$page}",
        ]);
    }
}
```

### Redazione API Keys

```php
// SEMPRE rimuovere API keys prima del log
$logParams = $params;
$logParams['api_key'] = '[REDACTED]';
$logParams['secret'] = '[REDACTED]';

ApiLoggerService::log('serpapi', '/search', $logParams, $data, $httpCode, $startTime, [...]);
```

---

## Admin Panel

### Routes

| Route | Metodo | Controller | Descrizione |
|-------|--------|------------|-------------|
| `/admin/api-logs` | GET | ApiLogsController@index | Lista paginata |
| `/admin/api-logs/{id}` | GET | ApiLogsController@show | Dettaglio singolo |
| `/admin/api-logs/cleanup` | POST | ApiLogsController@cleanup | Pulizia manuale |

### Filtri Disponibili
- Provider (dropdown)
- Modulo (dropdown)
- Status (success/error/rate_limited)
- Data range
- User ID

### Statistiche Dashboard
- Chiamate ultime 24h
- Errori ultime 24h
- Costo totale 30 giorni
- Breakdown per provider

---

## Cleanup Automatico

### Cron Job
`cron/cleanup-api-logs.php`

### Configurazione
- Retention: 30 giorni
- Batch size: 1000 record per iterazione
- Frequenza consigliata: giornaliera

### Comando Cron
```bash
0 3 * * * php /path/to/cron/cleanup-api-logs.php
```

---

## Integrazione nei Moduli

### Moduli Attualmente Integrati

| Modulo | File | Provider Loggati |
|--------|------|------------------|
| seo-tracking | RankCheckerService.php | dataforseo, serpapi, serper |
| seo-tracking | DataForSeoService.php | dataforseo |

### Checklist Integrazione Nuovo Modulo

1. [ ] Aggiungere `use Services\ApiLoggerService;`
2. [ ] Catturare `$startTime = microtime(true);` prima della chiamata
3. [ ] Chiamare `ApiLoggerService::log()` dopo la risposta
4. [ ] Specificare `module` nelle options
5. [ ] Redact eventuali API keys
6. [ ] Testare che i log appaiano in `/admin/api-logs`

---

## Troubleshooting

| Problema | Causa | Soluzione |
|----------|-------|-----------|
| Log non appare | ApiLoggerService non chiamato | Aggiungere chiamata log() |
| Payload troncato | Risposta > 50KB | Normale, vedi `_truncated` flag |
| user_id NULL | Chiamata da cron | Normale per job automatici |
| Durata 0ms | startTime non catturato | Usare microtime(true) PRIMA |
| Status errato | httpCode non passato | Verificare curl_getinfo() |

---

## Best Practices

1. **Sempre loggare** - Ogni chiamata API esterna deve essere loggata
2. **Context descrittivo** - Includere keyword, page, target per debug
3. **Redact secrets** - Mai loggare API keys o tokens
4. **Paginazione smart** - Solo prima pagina + errori
5. **Costo dinamico** - Estrarre cost dalla risposta API
6. **Error handling** - Loggare anche quando curl fallisce (httpCode = 0)
