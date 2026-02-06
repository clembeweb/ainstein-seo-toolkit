# Sistema Crediti - SEO Toolkit

> **Ultimo aggiornamento:** 2026-01-12
> **Versione:** 2.0 (Configurabile da Admin)

---

## PANORAMICA

Il sistema crediti gestisce il consumo di risorse per operazioni costose (AI, API esterne, scraping). Ogni utente ha un saldo crediti che viene scalato ad ogni operazione.

### Architettura

```
┌─────────────────────────────────────────────────────────────┐
│                      ADMIN PANEL                            │
│  /admin/modules/{id}/settings → Configura costi per modulo  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    modules.settings                         │
│              (JSON column in DB)                            │
│  {"cost_serp_extraction": 3, "cost_article_generation": 10} │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Credits::getCost()                        │
│  Priorità: 1. modules.settings → 2. settings → 3. config   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Credits::consume()                        │
│  → Scala users.credits                                      │
│  → Log in credit_transactions                               │
│  → Log in usage_log                                         │
└─────────────────────────────────────────────────────────────┘
```

---

## DATABASE

### Tabelle

| Tabella | Descrizione |
|---------|-------------|
| `users.credits` | Saldo crediti utente (DECIMAL) |
| `credit_transactions` | Storico tutte le transazioni |
| `usage_log` | Log dettagliato utilizzo per modulo/azione |
| `modules.settings` | JSON con costi configurati per modulo |
| `settings` | Costi globali (fallback) |

### Schema credit_transactions

```sql
CREATE TABLE credit_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,      -- Positivo = aggiunta, Negativo = consumo
    type ENUM('usage', 'purchase', 'bonus', 'refund', 'admin_adjustment'),
    description VARCHAR(255),
    admin_id INT NULL,                   -- Se modificato da admin
    balance_after DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Schema usage_log

```sql
CREATE TABLE usage_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    module_slug VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    credits_used DECIMAL(10,2) NOT NULL,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## CLASSE Credits (core/Credits.php)

### Metodi Principali

```php
// Ottieni saldo utente
Credits::getBalance(int $userId): float

// Verifica se ha crediti sufficienti
Credits::hasEnough(int $userId, float $amount): bool

// Consuma crediti (scala saldo + crea log)
Credits::consume(
    int $userId,
    float $amount,
    string $action,           // Nome operazione (es. 'serp_extraction')
    ?string $moduleSlug,      // Slug modulo (es. 'ai-content')
    array $metadata = []      // Dati aggiuntivi per log
): bool

// Aggiungi crediti (admin/acquisto)
Credits::add(
    int $userId,
    float $amount,
    string $type,             // 'purchase', 'bonus', 'refund', 'admin_adjustment'
    string $description,
    ?int $adminId = null
): void

// Ottieni costo operazione (NUOVO v2.0)
Credits::getCost(string $operation, ?string $moduleSlug = null): float
```

### getCost() - Priorità Lookup

```php
public static function getCost(string $operation, ?string $moduleSlug = null): float
{
    // 1. Se specificato modulo, cerca in modules.settings JSON
    if ($moduleSlug) {
        $module = Database::fetch(
            "SELECT settings FROM modules WHERE slug = ?",
            [$moduleSlug]
        );
        if ($module && !empty($module['settings'])) {
            $settings = json_decode($module['settings'], true);
            $key = 'cost_' . $operation;
            if (isset($settings[$key])) {
                return (float) $settings[$key];
            }
        }
    }

    // 2. Cerca in tabella settings (formato cost_X)
    $setting = Database::fetch(
        "SELECT value FROM settings WHERE key_name = ?",
        ['cost_' . $operation]
    );
    if ($setting) {
        return (float) $setting['value'];
    }

    // 3. Fallback a config/app.php
    $config = require __DIR__ . '/../config/app.php';
    return (float) ($config['credit_costs'][$operation] ?? 1);
}
```

---

## CONFIGURAZIONE COSTI

### Via Admin Panel (Raccomandato)

1. Vai a `/admin/modules`
2. Clicca su "Impostazioni" del modulo desiderato
3. Modifica i campi "Costo X" (tutti con `admin_only: true`)
4. Salva

I costi vengono salvati in `modules.settings` come JSON.

### Via module.json (Defaults)

Ogni modulo definisce i costi di default nel suo `module.json`:

```json
{
    "settings": {
        "cost_serp_extraction": {
            "type": "number",
            "label": "Costo Estrazione SERP",
            "description": "Crediti per analisi SERP",
            "default": 3,
            "min": 0,
            "step": 0.1,
            "admin_only": true
        }
    }
}
```

---

## COSTI PER MODULO

### AI Content (`ai-content`)

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| Estrazione SERP | `cost_serp_extraction` | 3 | Analisi SERP via SerpAPI |
| Scraping fonte | `cost_content_scrape` | 1 | Per ogni URL scrappato |
| Generazione brief | `cost_brief_generation` | 5 | Costruzione brief AI |
| Generazione articolo | `cost_article_generation` | 10 | Creazione articolo completo |
| Immagine di copertina | `cost_cover_image_generation` | 3 | Generazione cover image via DALL-E 3 (opzionale) |

### SEO Tracking (`seo-tracking`)

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| Full sync GSC | `cost_gsc_full_sync` | 10 | Sync storico 16 mesi |
| Quick Wins AI | `cost_quick_wins` | 2 | Analisi opportunità |
| Report settimanale | `cost_weekly_digest` | 5 | Digest AI settimanale |
| Report mensile | `cost_monthly_executive` | 15 | Report executive AI |

### Ads Analyzer (`ads-analyzer`)

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| Estrazione contesto | `cost_context_extraction` | 3 | Scraping + AI landing page |
| Analisi Ad Group | `cost_ad_group_analysis` | 2 | Singolo ad group |
| Analisi bulk | `cost_bulk_analysis` | 1.5 | 4+ ad groups |

### SEO Audit (`seo-audit`)

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| AI Overview | `cost_ai_overview` | 15 | Analisi panoramica progetto |
| AI Categoria | `cost_ai_category` | 3 | Analisi singola categoria |
| Sync GSC | `cost_gsc_sync` | 5 | Sincronizzazione GSC |

### Internal Links (`internal-links`)

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| Analisi link | `cost_link_analysis` | 0.5 | Analisi struttura link |
| Suggerimenti AI | `cost_ai_suggestions` | 1 | Suggerimenti AI per link |

### AI Keyword Research (`keyword-research`)

| Operazione | Chiave | Default | Descrizione |
|------------|--------|---------|-------------|
| AI Clustering (< 100 kw) | `cost_kr_ai_clustering` | 2 | Clustering semantico AI |
| AI Clustering (> 100 kw) | `cost_kr_ai_clustering_large` | 5 | Clustering semantico AI (large) |
| Architettura sito AI | `cost_kr_ai_architecture` | 5 | Struttura sito con URL/H1 |
| Quick Check | — | 0 | Gratis (solo API, no AI) |

---

## UTILIZZO NEI CONTROLLER

### Pattern Standard

```php
use Core\Credits;

// 1. Ottieni costo dinamico
$cost = Credits::getCost('serp_extraction', 'ai-content');

// 2. Verifica crediti sufficienti
if (!Credits::hasEnough($user['id'], $cost)) {
    return ['error' => "Crediti insufficienti. Richiesti: {$cost}"];
}

// 3. Esegui operazione...

// 4. Consuma crediti (solo dopo successo!)
Credits::consume(
    $user['id'],
    $cost,
    'serp_extraction',      // action
    'ai-content',           // moduleSlug
    ['keyword' => $keyword] // metadata opzionale
);
```

### Errori Comuni da Evitare

```php
// ❌ SBAGLIATO: Costo hardcoded
$cost = 10;

// ✅ CORRETTO: Costo dinamico
$cost = Credits::getCost('article_generation', 'ai-content');

// ❌ SBAGLIATO: Parametri in ordine errato
Credits::consume($userId, $cost, 'ai-content', 'serp_extraction');

// ✅ CORRETTO: action prima, moduleSlug dopo
Credits::consume($userId, $cost, 'serp_extraction', 'ai-content');

// ❌ SBAGLIATO: Consumare prima dell'operazione
Credits::consume(...);
$result = doExpensiveOperation(); // Potrebbe fallire!

// ✅ CORRETTO: Consumare solo dopo successo
$result = doExpensiveOperation();
if ($result['success']) {
    Credits::consume(...);
}
```

---

## TESTING

### Script di Test

```bash
# Accedi da browser (richiede login)
http://localhost/seo-toolkit/tests/test-credits-system.php

# Con test consumo (scala 0.1 crediti)
http://localhost/seo-toolkit/tests/test-credits-system.php?test_consume=1
```

### Verifica Manuale

```sql
-- Saldo utente
SELECT id, name, credits FROM users WHERE id = ?;

-- Ultime transazioni
SELECT * FROM credit_transactions
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 10;

-- Usage log per modulo
SELECT module_slug, action, SUM(credits_used) as total
FROM usage_log
WHERE user_id = ?
GROUP BY module_slug, action;

-- Costi configurati per modulo
SELECT slug, name, settings
FROM modules
WHERE settings IS NOT NULL;
```

---

## CHANGELOG

### v2.0 (2026-01-12)
- **BREAKING**: Costi ora configurabili da admin panel
- Aggiunto `Credits::getCost($operation, $moduleSlug)`
- Rimossi tutti i costi hardcoded dai controller
- Aggiunta sezione `settings` in ogni `module.json`
- Aggiornato `admin/views/module-settings.php` con supporto `step`

### v1.0 (2025-12)
- Implementazione iniziale
- Costi hardcoded nei controller
- Tabelle `credit_transactions` e `usage_log`
