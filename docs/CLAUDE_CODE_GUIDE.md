# Guida Claude Code - Ainstein SEO Toolkit

Regole e convenzioni per lo sviluppo con Claude Code.

## Regole Fondamentali

### 1. Servizio AI Centralizzato

**SEMPRE** usare `services/AiService.php` per chiamate Claude API.

```php
// CORRETTO
$aiService = new \Services\AiService();
$response = $aiService->analyze($prompt, $context);

// SBAGLIATO - Mai chiamare API direttamente
$client = new HttpClient();
$client->post('https://api.anthropic.com/...');
```

Il servizio gestisce:
- API key da config
- Logging automatico in `ai_logs`
- Conteggio crediti utente
- Gestione errori standardizzata

### 2. Icone - Solo Heroicons SVG

**SEMPRE** usare Heroicons (heroicons.com) come SVG inline.

```php
// CORRETTO - Heroicons SVG
<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
</svg>

// SBAGLIATO - Lucide, FontAwesome, o altre librerie
<i class="lucide-plus"></i>
<i class="fa fa-plus"></i>
```

### 3. UI in Italiano

Tutta l'interfaccia utente deve essere in italiano.

```php
// CORRETTO
<button>Salva Modifiche</button>
<th>Data Creazione</th>
<?php if ($error): ?>
    <p>Si e' verificato un errore</p>
<?php endif; ?>

// SBAGLIATO
<button>Save Changes</button>
<th>Created At</th>
```

Eccezioni ammesse:
- Nomi tecnici (API, URL, SEO, etc.)
- Codice e commenti possono essere in inglese

### 4. Prefissi Database per Modulo

Ogni modulo usa il proprio prefisso per le tabelle.

| Modulo | Prefisso | Esempio |
|--------|----------|---------|
| ai-content | `aic_` | `aic_articles`, `aic_keywords` |
| internal-links | `il_` | `il_projects`, `il_urls` |
| seo-audit | `sa_` | `sa_projects`, `sa_pages` |
| seo-tracking | `st_` | `st_projects`, `st_keywords` |
| core | nessuno | `users`, `settings` |

### 5. Pattern Routes

Struttura URL consistente per tutti i moduli:

```php
// Lista risorse
GET /modulo/risorsa                    // index
GET /modulo/risorsa/create             // form creazione
POST /modulo/risorsa                   // store
GET /modulo/risorsa/{id}               // show
GET /modulo/risorsa/{id}/edit          // form modifica
POST /modulo/risorsa/{id}              // update
POST /modulo/risorsa/{id}/delete       // delete

// Esempio concreto
GET /ai-content/articles               // Lista articoli
GET /ai-content/articles/15            // Dettaglio articolo 15
POST /ai-content/articles/15/delete    // Elimina articolo 15
```

### 6. Struttura Controller

```php
<?php
namespace Modules\NomeModulo\Controllers;

class RisorsaController
{
    public function index()
    {
        // Lista con paginazione
    }

    public function show($id)
    {
        // Dettaglio singolo
    }

    public function create()
    {
        // Form creazione
    }

    public function store()
    {
        // Salva nuovo
    }

    public function edit($id)
    {
        // Form modifica
    }

    public function update($id)
    {
        // Aggiorna esistente
    }

    public function delete($id)
    {
        // Elimina
    }
}
```

### 7. Struttura View

```php
<?php
// Sempre iniziare con variabili dal controller
$pageTitle = $pageTitle ?? 'Titolo Default';
$items = $items ?? [];
?>

<!-- Usare Tailwind CSS -->
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($pageTitle) ?></h1>

    <!-- Contenuto -->
</div>
```

### 8. Escape Output

**SEMPRE** usare `htmlspecialchars()` per output dinamico:

```php
// CORRETTO
<td><?= htmlspecialchars($user->name) ?></td>
<input value="<?= htmlspecialchars($value) ?>">

// SBAGLIATO - XSS vulnerability
<td><?= $user->name ?></td>
```

## Checklist Pre-Commit

Prima di ogni commit, verificare:

- [ ] **AiService:** Tutte le chiamate AI passano per il servizio centralizzato
- [ ] **Icone:** Solo Heroicons SVG, nessun Lucide/FontAwesome
- [ ] **Lingua:** UI completamente in italiano
- [ ] **Database:** Tabelle con prefisso modulo corretto
- [ ] **Routes:** Pattern URL consistenti
- [ ] **Security:** Output escaped con `htmlspecialchars()`
- [ ] **Config:** Nessun valore hardcoded, usare `env()`
- [ ] **Syntax:** `php -l` su tutti i file modificati

## Pattern Comuni

### Paginazione

```php
// Controller
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$items = Model::paginate($perPage, $offset);
$total = Model::count();
$totalPages = ceil($total / $perPage);

// View
<?php if ($totalPages > 1): ?>
<nav class="flex justify-center mt-4">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="px-3 py-1 <?= $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-200' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
```

### Flash Messages

```php
// Set message
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Operazione completata'];

// Display
<?php if ($flash = $_SESSION['flash'] ?? null): ?>
    <?php unset($_SESSION['flash']); ?>
    <div class="p-4 rounded <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
```

### AJAX con HTMX

```php
// Trigger
<button hx-post="/api/action"
        hx-target="#result"
        hx-swap="innerHTML">
    Esegui
</button>

// Target
<div id="result"></div>
```

## File da NON Modificare

Senza esplicita richiesta, non modificare:

- `core/Database.php` - Connessione DB
- `core/Router.php` - Routing
- `core/Auth.php` - Autenticazione
- `config/environment.php` - Loader env
- `public/index.php` - Bootstrap
- `public/.htaccess` - Rewrite rules

## Convenzioni Naming

| Tipo | Convenzione | Esempio |
|------|-------------|---------|
| Classi | PascalCase | `ArticleController` |
| Metodi | camelCase | `getArticleById()` |
| Variabili | camelCase | `$articleList` |
| Costanti | UPPER_SNAKE | `MAX_ITEMS` |
| Tabelle DB | snake_case | `aic_articles` |
| Colonne DB | snake_case | `created_at` |
| File PHP | PascalCase | `ArticleController.php` |
| File view | kebab-case | `article-list.php` |
| Routes | kebab-case | `/ai-content/articles` |

## Debug

### Abilitare Debug Mode

```env
APP_DEBUG=true
```

### Log Errori

```php
error_log("Debug: " . print_r($data, true));
// Output in storage/logs/ o php error log
```

### Dump Variabili

```php
// Solo in sviluppo
if (env('APP_DEBUG')) {
    echo '<pre>' . print_r($var, true) . '</pre>';
    die();
}
```

---

Riferimento rapido durante lo sviluppo con Claude Code.
