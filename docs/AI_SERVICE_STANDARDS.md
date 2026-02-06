# AI SERVICE - Standard di Integrazione

## Overview

Il sistema SEO Toolkit utilizza `AiService` come servizio centralizzato per tutte le chiamate AI. Supporta multi-provider (Anthropic Claude / OpenAI GPT) con fallback automatico e logging completo.

---

## REGOLA FONDAMENTALE

> **Ogni modulo che usa AI DEVE passare il proprio `module_slug` ad AiService per il logging.**

---

## Istanziazione Corretta

### ✅ CORRETTO - Con module slug
```php
// Nel costruttore del service/controller
$this->aiService = new AiService('nome-modulo');

// Oppure con setter fluent
$this->aiService = new AiService();
$this->aiService->setModule('nome-modulo');
```

### ❌ SBAGLIATO - Senza module slug
```php
// NON FARE - i log non saranno tracciabili per modulo
$this->aiService = new AiService();
$result = $this->aiService->analyze(...);
```

---

## Metodi Disponibili

### 1. `analyze()` - Analisi Semplice
```php
$result = $this->aiService->analyze(
    $userId,           // int - ID utente per crediti
    $prompt,           // string - Istruzione/domanda
    $content,          // string - Contenuto da analizzare
    'nome-modulo'      // string - Slug modulo (opzionale se già impostato)
);

// Response
[
    'success' => true,
    'result' => 'Testo risposta AI...',
    'credits_used' => 5
]
// oppure
[
    'error' => true,
    'message' => 'Descrizione errore'
]
```

### 2. `analyzeWithSystem()` - Con System Prompt
```php
$result = $this->aiService->analyzeWithSystem(
    $userId,
    $systemPrompt,     // string - Ruolo/contesto AI
    $prompt,           // string - Istruzione specifica
    $content,          // string - Contenuto
    'nome-modulo'
);
```

### 3. `complete()` - Chiamata Flessibile
```php
$result = $this->aiService->complete(
    $userId,
    $messages,         // array - Formato chat [['role' => 'user', 'content' => '...']]
    $options,          // array - Opzioni (max_tokens, temperature, etc.)
    'nome-modulo'
);
```

### 4. `isConfigured()` - Verifica Configurazione
```php
if (!$this->aiService->isConfigured()) {
    return ['error' => true, 'message' => 'AI non configurata'];
}
```

---

## Pattern Standard per Services
```php
<?php
namespace Modules\NomeModulo\Services;

use Services\AiService;

class MioAnalysisService
{
    private AiService $aiService;

    public function __construct()
    {
        // ✅ SEMPRE specificare il module slug
        $this->aiService = new AiService('nome-modulo');
    }

    public function analyzeContent(int $userId, string $content): array
    {
        // Verifica configurazione
        if (!$this->aiService->isConfigured()) {
            return [
                'error' => true,
                'message' => 'Servizio AI non configurato. Contatta l\'amministratore.'
            ];
        }

        $prompt = "Analizza il seguente contenuto e fornisci...";

        $result = $this->aiService->analyze($userId, $prompt, $content);

        if (isset($result['error'])) {
            return $result;
        }

        return [
            'success' => true,
            'analysis' => $result['result'],
            'credits_used' => $result['credits_used']
        ];
    }
}
```

---

## Moduli Attualmente Integrati

| Modulo | Slug | File Service | Riga |
|--------|------|--------------|------|
| AI SEO Content Generator | `ai-content` | `services/ArticleGeneratorService.php` | 35 |
| AI SEO Content Generator | `ai-content` | `services/BriefBuilderService.php` | costruttore |
| AI SEO Content Generator | `ai-content` | `services/CoverImageService.php` | buildImagePrompt() (Claude Haiku per prompt DALL-E) |
| SEO Audit | `seo-audit` | `services/AiAnalysisService.php` | 34 |
| AI Keyword Research | `keyword-research` | `controllers/ResearchController.php` | aiAnalyze() |
| AI Keyword Research | `keyword-research` | `controllers/ArchitectureController.php` | aiAnalyze() |

> **Nota:** CoverImageService usa anche OpenAI DALL-E 3 direttamente (`/v1/images/generations`) per generazione immagini.
> La chiamata DALL-E non passa da AiService ma usa l'API key da `Settings::get('openai_api_key')`.
> Il logging avviene tramite `ApiLoggerService::log('openai_dalle', ...)`.

---

## Checklist Nuovo Modulo con AI

Quando crei un nuovo modulo che usa AI:

- [ ] Crea service dedicato (es. `services/MyAnalysisService.php`)
- [ ] Istanzia AiService con module slug: `new AiService('mio-modulo')`
- [ ] Verifica configurazione con `isConfigured()` prima di chiamate
- [ ] Gestisci response error/success
- [ ] Propaga `credits_used` al chiamante
- [ ] Testa che i log appaiano in Admin → AI Logs con modulo corretto

---

## Logging Automatico

Ogni chiamata AI viene automaticamente loggata in `ai_logs` con:

| Campo | Descrizione |
|-------|-------------|
| `module_slug` | Identificativo modulo (per filtri) |
| `provider` | anthropic / openai |
| `model` | Modello utilizzato |
| `tokens_input` | Token prompt |
| `tokens_output` | Token risposta |
| `duration_ms` | Tempo esecuzione |
| `status` | success / error / fallback |
| `estimated_cost` | Costo stimato USD |
| `request_payload` | JSON richiesta (troncato a 10KB) |
| `response_payload` | JSON risposta |

I log sono visibili in **Admin → AI Logs** con filtri per modulo, provider, status e date.

---

## Best Practices

### 1. Gestisci sempre gli errori
```php
$result = $this->aiService->analyze($userId, $prompt, $content);

if (isset($result['error'])) {
    return $this->errorResponse($result['message']);
}
```

### 2. Usa system prompt per contesto
```php
$systemPrompt = "Sei un esperto SEO italiano. Rispondi sempre in italiano.";

$result = $this->aiService->analyzeWithSystem(
    $userId,
    $systemPrompt,
    "Analizza questi meta tag:",
    $metaTagsContent,
    'seo-audit'
);
```

### 3. Limita token per risposte brevi
```php
$result = $this->aiService->complete($userId, $messages, [
    'max_tokens' => 500,  // Per risposte brevi
], 'mio-modulo');
```

### 4. Usa JSON per output strutturati
```php
$prompt = "Analizza e rispondi SOLO in JSON: {\"score\": 0-100, \"issues\": [...]}";
```

---

## Troubleshooting

### "Crediti insufficienti"
- Verifica balance utente in `users.credits`
- Il costo varia per dimensione contenuto (vedi `credits_costs`)

### "API Key non configurata"
- Verifica `ai_settings.api_key` nel DB
- Almeno un provider deve essere configurato

### Log non visibili per modulo
- Verifica che `module_slug` sia passato ad AiService
- Controlla che il modulo esista in `modules.slug`

---

## Changelog

| Data | Versione | Note |
|------|----------|------|
| 2024-12 | 1.0 | Prima release con supporto multi-provider |
| 2025-01 | 1.1 | Aggiunto fallback automatico e logging esteso |
