# AINSTEIN - Istruzioni Claude Code

> Questo file viene caricato automaticamente ad ogni sessione.
> Ultimo aggiornamento: 2026-01-29

---

## CONTESTO PROGETTO

| Aspetto | Dettaglio |
|---------|-----------|
| **Nome** | Ainstein SEO Toolkit |
| **Directory locale** | `C:\laragon\www\seo-toolkit` |
| **Produzione** | https://ainstein.it |
| **Stack** | PHP 8+, MySQL, Tailwind CSS, Alpine.js, HTMX |
| **AI Provider** | Claude API (Anthropic) + OpenAI fallback |
| **Lingua UI** | Italiano (sempre) |
| **Database** | MySQL `seo_toolkit` (locale) / `dbj0xoiwysdlk1` (prod) |

---

## GOLDEN RULES (INVIOLABILI)

```
1. AiService SEMPRE centralizzato    → new AiService('modulo-slug')
2. Icone SOLO Heroicons SVG          → MAI Lucide, MAI FontAwesome
3. Lingua UI ITALIANO                → Tutti i testi visibili
4. Prefisso DB per modulo            → aic_, sa_, st_, il_, ga_, cc_
5. API Keys in Database              → MAI in .env, MAI hardcoded
6. Routes pattern standard           → /modulo/projects/{id}/sezione
7. Prepared statements SEMPRE        → MAI concatenare SQL
8. CSRF token su form POST           → csrf_token() in ogni form
9. ai-content è il reference         → Copia pattern da lì
10. Database::reconnect()            → Prima di salvare dopo AI call
11. OAuth GSC pattern seo-tracking   → GoogleOAuthService centralizzato
```

---

## STATO MODULI

| Modulo | Slug | Prefisso DB | Stato | Note |
|--------|------|-------------|-------|------|
| AI Content Generator | `ai-content` | `aic_` | 100% | Reference pattern, scheduling per-keyword |
| SEO Audit | `seo-audit` | `sa_` | 100% | Action Plan AI completato |
| Google Ads Analyzer | `ads-analyzer` | `ga_` | 100% | Completo |
| Internal Links | `internal-links` | `il_` | 85% | Manca AI Suggester |
| SEO Tracking | `seo-tracking` | `st_` | 90% | GA4 rimosso, Rank Check, Page Analyzer, Position Compare |
| Content Creator | `content-creator` | `cc_` | 0% | Da implementare |

---

## DOCS DI RIFERIMENTO

**Prima di modificare qualsiasi cosa, leggi i file pertinenti in `/mnt/project/`:**

| Quando | Leggi |
|--------|-------|
| Sempre | `GOLDEN-RULES.md` |
| Nuovo modulo | `PLATFORM_OVERVIEW.md`, `MODULE_NAVIGATION.md` |
| Import URL | `IMPORT_STANDARDS.md` |
| Integrazione AI | `AI_SERVICE_STANDARDS.md` |
| Sistema crediti | `CREDITS-SYSTEM.md` |
| Modulo specifico | `specs/[modulo].md` o `AGENT-[MODULO].md` |
| Deploy | `DEPLOY.md`, `DEPLOY-VERIFY-AINSTEIN.md` |

---

## STRUTTURA PROGETTO

```
seo-toolkit/
├── core/                    # Framework (Router, Database, Auth, Credits)
├── services/                # Servizi CONDIVISI
│   ├── AiService.php        # Claude API - USARE SEMPRE
│   ├── ScraperService.php   # HTTP + DOM
│   ├── GoogleOAuthService.php
│   ├── SitemapService.php
│   ├── CsvImportService.php
│   └── DataForSeoService.php # Rank check API
├── modules/
│   ├── ai-content/          # REFERENCE per nuovi moduli
│   ├── seo-audit/
│   ├── ads-analyzer/
│   ├── internal-links/
│   └── seo-tracking/
├── shared/views/
│   ├── layout.php
│   └── components/
│       ├── nav-items.php    # Sidebar con accordion
│       └── import-tabs.php  # Componente import URL
└── config/
```

---

## COMANDI FREQUENTI

### SSH Produzione
```bash
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
cd ~/www/ainstein.it/public_html
```

### Git Deploy
```bash
# Locale
git add .
git commit -m "tipo: descrizione"
git push origin main

# Produzione (da SSH)
git pull origin main
```

### Verifica Sintassi PHP
```bash
php -l path/to/file.php
```

### Test Connessione DB (locale)
```bash
mysql -u root seo_toolkit -e "SHOW TABLES;"
```

---

## PATTERN DI SVILUPPO

### Nuovo Controller
```php
<?php
namespace Modules\NomeModulo\Controllers;

use Core\View;
use Core\Auth;
use Core\Middleware;

class NomeController
{
    public function index()
    {
        Middleware::auth();
        $user = Auth::user();
        
        // logica...
        
        View::render('nome-modulo::vista', [
            'data' => $data,
            'modules' => \Core\ModuleLoader::getActiveModules()
        ]);
    }
}
```

### Chiamata AI
```php
use Services\AiService;

$ai = new AiService('nome-modulo'); // SEMPRE specificare modulo

if (!$ai->isConfigured()) {
    return ['error' => true, 'message' => 'AI non configurata'];
}

$result = $ai->analyze($userId, $prompt, $content, 'nome-modulo');

Database::reconnect(); // PRIMA di salvare
$model->save($data);
```

### Consumo Crediti
```php
use Core\Credits;

$cost = Credits::getCost('operazione', 'nome-modulo');

if (!Credits::hasEnough($userId, $cost)) {
    return ['error' => "Crediti insufficienti. Richiesti: {$cost}"];
}

// Esegui operazione...

Credits::consume($userId, $cost, 'operazione', 'nome-modulo');
```

---

## ICONE HEROICONS (più usate)

```html
<!-- Check -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
</svg>

<!-- X/Close -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
</svg>

<!-- Plus -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
</svg>

<!-- Search -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
</svg>

<!-- Download -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
</svg>

<!-- Refresh -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
</svg>

<!-- Trash -->
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
</svg>
```

---

## CHECKLIST PRE-COMMIT

```
[ ] Nessun curl diretto per API AI
[ ] Nessuna icona Lucide/FontAwesome  
[ ] Tutti i testi UI in italiano
[ ] Tabelle con prefisso modulo corretto
[ ] Nessuna API key in file
[ ] Query SQL con prepared statements
[ ] CSRF token su form POST
[ ] Database::reconnect() dopo chiamate lunghe
[ ] php -l su file modificati
```

---

## WORKFLOW CONSIGLIATO

1. **Leggi docs pertinenti** prima di iniziare
2. **Un task alla volta** - fermati per conferma
3. **Verifica sintassi** dopo ogni modifica PHP
4. **Test manuale** in browser prima di commit
5. **Commit atomici** con messaggi descrittivi

---

## TROUBLESHOOTING RAPIDO

| Problema | Causa probabile | Fix |
|----------|-----------------|-----|
| 500 Error | Sintassi PHP | `php -l file.php` |
| "MySQL gone away" | Connessione scaduta | `Database::reconnect()` |
| Sidebar non appare | `$modules` non passato | Aggiungi a View::render() |
| Icone non visibili | Lucide invece di Heroicons | Sostituisci con SVG |
| Crediti non scalano | `Credits::consume()` mancante | Aggiungi dopo operazione |
| "Database is limited" | Limite SiteGround temporaneo | Attendi qualche minuto e riprova |

---

*File generato per Claude Code - Non modificare manualmente senza aggiornare la data*
