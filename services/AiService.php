<?php

namespace Services;

use Core\Credits;
use Core\Database;
use Core\Settings;

/**
 * AiService - Multi-Provider AI Service with Fallback and Logging
 *
 * Supports Anthropic (Claude) and OpenAI (GPT) with automatic fallback
 * and comprehensive logging to ai_logs table.
 */
class AiService
{
    /**
     * Available models with pricing (USD per 1K tokens)
     */
    public const MODELS = [
        'anthropic' => [
            'claude-sonnet-4-20250514' => ['name' => 'Claude Sonnet 4', 'input' => 0.003, 'output' => 0.015],
            'claude-opus-4-20250514' => ['name' => 'Claude Opus 4', 'input' => 0.015, 'output' => 0.075],
            'claude-3-5-sonnet-20241022' => ['name' => 'Claude 3.5 Sonnet', 'input' => 0.003, 'output' => 0.015],
            'claude-3-5-haiku-20241022' => ['name' => 'Claude 3.5 Haiku', 'input' => 0.0008, 'output' => 0.004],
        ],
        'openai' => [
            'gpt-4o' => ['name' => 'GPT-4o', 'input' => 0.005, 'output' => 0.015],
            'gpt-4o-mini' => ['name' => 'GPT-4o Mini', 'input' => 0.00015, 'output' => 0.0006],
            'gpt-4-turbo' => ['name' => 'GPT-4 Turbo', 'input' => 0.01, 'output' => 0.03],
        ],
    ];

    /**
     * Provider labels for UI
     */
    public const PROVIDERS = [
        'anthropic' => 'Anthropic (Claude)',
        'openai' => 'OpenAI (GPT)',
    ];

    private string $provider;
    private string $model;
    private string $apiKey;
    private bool $fallbackEnabled;
    private string $moduleSlug;

    public function __construct(?string $moduleSlug = null)
    {
        $this->moduleSlug = $moduleSlug ?? 'unknown';
        $this->loadSettings();
        $this->loadModuleOverrides();
    }

    /**
     * Load global settings from database
     */
    private function loadSettings(): void
    {
        // Try Settings class first, fallback to direct DB query
        try {
            $this->provider = Settings::get('ai_provider', 'anthropic');
            $this->model = Settings::get('ai_model', 'claude-sonnet-4-20250514');
            $this->fallbackEnabled = (bool) Settings::get('ai_fallback_enabled', '1');

            // Get API key based on provider
            if ($this->provider === 'openai') {
                $this->apiKey = Settings::get('openai_api_key', '');
            } else {
                $this->apiKey = Settings::get('anthropic_api_key', '');
            }
        } catch (\Exception $e) {
            // Fallback to direct database query
            $this->provider = $this->getSettingDirect('ai_provider', 'anthropic');
            $this->model = $this->getSettingDirect('ai_model', 'claude-sonnet-4-20250514');
            $this->fallbackEnabled = (bool) $this->getSettingDirect('ai_fallback_enabled', '1');

            if ($this->provider === 'openai') {
                $this->apiKey = $this->getSettingDirect('openai_api_key', '');
            } else {
                $this->apiKey = $this->getSettingDirect('anthropic_api_key', '');
            }
        }
    }

    /**
     * Load module-specific AI overrides from module settings
     * Priority: Module setting > Global setting
     */
    private function loadModuleOverrides(): void
    {
        if ($this->moduleSlug === 'unknown' || empty($this->moduleSlug)) {
            return;
        }

        try {
            $moduleProvider = \Core\ModuleLoader::getSetting($this->moduleSlug, 'ai_provider', 'global');

            if ($moduleProvider !== 'global' && !empty($moduleProvider)) {
                // Override provider
                $this->provider = $moduleProvider;

                // Override model if set
                $moduleModel = \Core\ModuleLoader::getSetting($this->moduleSlug, 'ai_model', 'global');
                if ($moduleModel !== 'global' && !empty($moduleModel)) {
                    $this->model = $moduleModel;
                } else {
                    // Provider changed but model not set - use default for provider
                    $this->model = $this->getDefaultModelForProvider($moduleProvider);
                }

                // Update API key for the new provider
                $this->apiKey = $this->getApiKeyForProvider($this->provider);
            }

            // Override fallback setting
            $moduleFallback = \Core\ModuleLoader::getSetting($this->moduleSlug, 'ai_fallback_enabled', 'global');
            if ($moduleFallback !== 'global' && $moduleFallback !== null) {
                $this->fallbackEnabled = (bool) $moduleFallback;
            }
        } catch (\Exception $e) {
            // Silently fail - use global settings
            error_log("AiService: Failed to load module overrides for {$this->moduleSlug}: " . $e->getMessage());
        }
    }

    /**
     * Direct database query for settings (fallback)
     */
    private function getSettingDirect(string $key, string $default = ''): string
    {
        $result = Database::fetch("SELECT value FROM settings WHERE key_name = ?", [$key]);
        return $result['value'] ?? $default;
    }

    /**
     * Set module slug (for logging)
     */
    public function setModule(string $moduleSlug): self
    {
        $this->moduleSlug = $moduleSlug;
        return $this;
    }

    /**
     * Simple analysis with prompt and content
     * RETROCOMPATIBLE - Same signature as before
     */
    public function analyze(int $userId, string $prompt, string $content, ?string $moduleSlug = null): array
    {
        if ($moduleSlug) {
            $this->moduleSlug = $moduleSlug;
        }

        if (!$this->isConfigured()) {
            return ['error' => true, 'message' => 'API Key AI non configurata'];
        }

        // Calculate size and cost
        $tokenEstimate = $this->estimateTokens($prompt . $content);
        $costType = $this->getCostType($tokenEstimate);
        $cost = Credits::getCost($costType);

        // Check credits
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti',
                'credits_required' => $cost,
            ];
        }

        // Build messages
        $messages = [
            ['role' => 'user', 'content' => $prompt . "\n\n" . $content],
        ];

        // Call API with fallback
        $result = $this->executeWithFallback($userId, $messages, 4096, null, $cost);

        if (isset($result['error'])) {
            return $result;
        }

        // Consume credits
        Credits::consume($userId, $cost, $costType, $this->moduleSlug, [
            'tokens_estimate' => $tokenEstimate,
            'prompt_length' => strlen($prompt),
            'content_length' => strlen($content),
        ]);

        return [
            'success' => true,
            'result' => $result['content'],
            'credits_used' => $cost,
        ];
    }

    /**
     * Flexible API call with custom messages and options
     * RETROCOMPATIBLE - Same signature as before
     */
    public function complete(int $userId, array $messages, array $options = [], ?string $moduleSlug = null): array
    {
        if ($moduleSlug) {
            $this->moduleSlug = $moduleSlug;
        }

        if (!$this->isConfigured()) {
            return ['error' => true, 'message' => 'API Key AI non configurata'];
        }

        $maxTokens = $options['max_tokens'] ?? 4096;
        $model = $options['model'] ?? $this->model;
        $system = $options['system'] ?? null;

        // Calculate content size for cost
        $contentSize = 0;
        foreach ($messages as $msg) {
            $contentSize += strlen($msg['content'] ?? '');
        }
        if ($system) {
            $contentSize += strlen($system);
        }

        $tokenEstimate = $this->estimateTokens(str_repeat('x', $contentSize));
        $costType = $this->getCostType($tokenEstimate);
        $cost = Credits::getCost($costType);

        // Check credits
        if (!Credits::hasEnough($userId, $cost)) {
            return [
                'error' => true,
                'message' => 'Crediti insufficienti',
                'credits_required' => $cost,
            ];
        }

        // Call API with fallback
        $result = $this->executeWithFallback($userId, $messages, $maxTokens, $system, $cost, $model);

        if (isset($result['error'])) {
            return $result;
        }

        // Consume credits
        Credits::consume($userId, $cost, $costType, $this->moduleSlug, [
            'tokens_estimate' => $tokenEstimate,
            'model' => $result['model_used'] ?? $model,
        ]);

        return [
            'success' => true,
            'result' => $result['content'],
            'credits_used' => $cost,
        ];
    }

    /**
     * Analyze with system prompt (convenience method)
     * RETROCOMPATIBLE - Same signature as before
     */
    public function analyzeWithSystem(int $userId, string $systemPrompt, string $userPrompt, ?string $moduleSlug = null): array
    {
        return $this->complete($userId, [
            ['role' => 'user', 'content' => $userPrompt],
        ], [
            'system' => $systemPrompt,
        ], $moduleSlug);
    }

    /**
     * Execute API call with fallback logic
     */
    private function executeWithFallback(int $userId, array $messages, int $maxTokens, ?string $system, float $creditsUsed, ?string $modelOverride = null): array
    {
        $startTime = microtime(true);
        $originalProvider = $this->provider;
        $originalModel = $modelOverride ?? $this->model;

        // Determine provider from model if model is overridden
        $currentProvider = $this->provider;
        if ($modelOverride) {
            $currentProvider = $this->getProviderForModel($modelOverride) ?? $this->provider;
        }

        $requestPayload = [
            'messages' => $messages,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'model' => $originalModel,
        ];

        // Attempt 1: Primary provider
        try {
            $result = $this->callProvider($currentProvider, $originalModel, $messages, $maxTokens, $system);

            $this->logCall(
                $userId,
                $this->moduleSlug,
                $currentProvider,
                $originalModel,
                $requestPayload,
                $result,
                'success',
                null,
                null,
                $startTime,
                $creditsUsed
            );

            return [
                'content' => $result['content'],
                'model_used' => $originalModel,
                'provider_used' => $currentProvider,
            ];

        } catch (\Exception $e) {
            $primaryError = $e->getMessage();

            // Attempt 2: Fallback provider (if enabled)
            if ($this->fallbackEnabled) {
                $fallbackProvider = $currentProvider === 'anthropic' ? 'openai' : 'anthropic';
                $fallbackKey = $this->getApiKeyForProvider($fallbackProvider);

                if (!empty($fallbackKey)) {
                    try {
                        $fallbackModel = $this->getDefaultModelForProvider($fallbackProvider);
                        $result = $this->callProvider($fallbackProvider, $fallbackModel, $messages, $maxTokens, $system);

                        $this->logCall(
                            $userId,
                            $this->moduleSlug,
                            $fallbackProvider,
                            $fallbackModel,
                            $requestPayload,
                            $result,
                            'fallback',
                            null,
                            $originalProvider,
                            $startTime,
                            $creditsUsed
                        );

                        return [
                            'content' => $result['content'],
                            'model_used' => $fallbackModel,
                            'provider_used' => $fallbackProvider,
                            'fallback' => true,
                        ];

                    } catch (\Exception $fallbackEx) {
                        // Both providers failed
                        $this->logCall(
                            $userId,
                            $this->moduleSlug,
                            $fallbackProvider,
                            $fallbackModel ?? 'unknown',
                            $requestPayload,
                            null,
                            'error',
                            "Primary: {$primaryError} | Fallback: {$fallbackEx->getMessage()}",
                            $originalProvider,
                            $startTime,
                            0
                        );

                        return [
                            'error' => true,
                            'message' => "Errore provider primario ({$originalProvider}): {$primaryError}. Errore fallback ({$fallbackProvider}): {$fallbackEx->getMessage()}",
                        ];
                    }
                }
            }

            // No fallback or fallback not available
            $this->logCall(
                $userId,
                $this->moduleSlug,
                $currentProvider,
                $originalModel,
                $requestPayload,
                null,
                'error',
                $primaryError,
                null,
                $startTime,
                0
            );

            return [
                'error' => true,
                'message' => $primaryError,
            ];
        }
    }

    /**
     * Call specific provider API
     */
    private function callProvider(string $provider, string $model, array $messages, int $maxTokens, ?string $system = null): array
    {
        $apiKey = $this->getApiKeyForProvider($provider);

        if (empty($apiKey)) {
            throw new \Exception("API Key per {$provider} non configurata");
        }

        if ($provider === 'openai') {
            return $this->callOpenAI($apiKey, $model, $messages, $maxTokens, $system);
        } else {
            return $this->callAnthropic($apiKey, $model, $messages, $maxTokens, $system);
        }
    }

    /**
     * Call Anthropic Claude API
     */
    private function callAnthropic(string $apiKey, string $model, array $messages, int $maxTokens, ?string $system = null): array
    {
        // Sanitize messages content for valid UTF-8
        $messages = $this->sanitizeMessagesUtf8($messages);

        $data = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];

        if ($system) {
            $data['system'] = $this->sanitizeStringUtf8($system);
        }

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($jsonData === false) {
            $jsonError = json_last_error_msg();
            throw new \Exception("Errore encoding JSON per Anthropic: {$jsonError}. Verificare che il contenuto non contenga caratteri non validi.");
        }

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("Errore connessione Anthropic: {$curlError}");
        }

        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            $errorMsg = $error['error']['message'] ?? "Errore API Anthropic (HTTP {$httpCode})";
            throw new \Exception($errorMsg);
        }

        $result = json_decode($response, true);

        return [
            'content' => $result['content'][0]['text'] ?? '',
            'tokens_input' => $result['usage']['input_tokens'] ?? 0,
            'tokens_output' => $result['usage']['output_tokens'] ?? 0,
            'raw_response' => $result,
        ];
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI(string $apiKey, string $model, array $messages, int $maxTokens, ?string $system = null): array
    {
        // Sanitize messages content for valid UTF-8
        $messages = $this->sanitizeMessagesUtf8($messages);

        // Convert to OpenAI format - prepend system message
        $openaiMessages = [];

        if ($system) {
            $openaiMessages[] = [
                'role' => 'system',
                'content' => $this->sanitizeStringUtf8($system),
            ];
        }

        foreach ($messages as $msg) {
            $openaiMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $data = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $openaiMessages,
        ];

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($jsonData === false) {
            $jsonError = json_last_error_msg();
            throw new \Exception("Errore encoding JSON per OpenAI: {$jsonError}. Verificare che il contenuto non contenga caratteri non validi.");
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("Errore connessione OpenAI: {$curlError}");
        }

        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            $errorMsg = $error['error']['message'] ?? "Errore API OpenAI (HTTP {$httpCode})";
            throw new \Exception($errorMsg);
        }

        $result = json_decode($response, true);

        return [
            'content' => $result['choices'][0]['message']['content'] ?? '',
            'tokens_input' => $result['usage']['prompt_tokens'] ?? 0,
            'tokens_output' => $result['usage']['completion_tokens'] ?? 0,
            'raw_response' => $result,
        ];
    }

    /**
     * Log API call to ai_logs table
     */
    private function logCall(
        int $userId,
        string $moduleSlug,
        string $provider,
        string $model,
        array $requestPayload,
        ?array $responsePayload,
        string $status,
        ?string $errorMessage,
        ?string $fallbackFrom,
        float $startTime,
        float $creditsUsed
    ): void {
        try {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $tokensInput = $responsePayload['tokens_input'] ?? 0;
            $tokensOutput = $responsePayload['tokens_output'] ?? 0;

            $estimatedCost = $this->calculateCost($provider, $model, $tokensInput, $tokensOutput);

            // Clean request payload for storage (remove very long content)
            $cleanRequest = $requestPayload;
            if (isset($cleanRequest['messages'])) {
                foreach ($cleanRequest['messages'] as &$msg) {
                    if (strlen($msg['content'] ?? '') > 10000) {
                        $msg['content'] = substr($msg['content'], 0, 10000) . '... [TRUNCATED]';
                    }
                }
            }

            Database::insert('ai_logs', [
                'user_id' => $userId ?: null,
                'module_slug' => $moduleSlug,
                'provider' => $provider,
                'model' => $model,
                'request_payload' => json_encode($cleanRequest, JSON_UNESCAPED_UNICODE),
                'response_payload' => $responsePayload ? json_encode($responsePayload, JSON_UNESCAPED_UNICODE) : null,
                'tokens_input' => $tokensInput,
                'tokens_output' => $tokensOutput,
                'duration_ms' => $durationMs,
                'status' => $status,
                'error_message' => $errorMessage,
                'fallback_from' => $fallbackFrom,
                'estimated_cost' => $estimatedCost,
                'credits_used' => (int) $creditsUsed,
            ]);
        } catch (\Exception $e) {
            // Silently fail logging - don't break the main flow
            error_log("AiService::logCall failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate estimated cost in USD
     */
    private function calculateCost(string $provider, string $model, int $tokensInput, int $tokensOutput): float
    {
        $modelInfo = self::MODELS[$provider][$model] ?? null;

        if (!$modelInfo) {
            return 0;
        }

        $inputCost = ($tokensInput / 1000) * $modelInfo['input'];
        $outputCost = ($tokensOutput / 1000) * $modelInfo['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get API key for specific provider
     */
    private function getApiKeyForProvider(string $provider): string
    {
        if ($provider === 'openai') {
            try {
                return Settings::get('openai_api_key', '');
            } catch (\Exception $e) {
                return $this->getSettingDirect('openai_api_key', '');
            }
        } else {
            try {
                return Settings::get('anthropic_api_key', '');
            } catch (\Exception $e) {
                return $this->getSettingDirect('anthropic_api_key', '');
            }
        }
    }

    /**
     * Get default model for provider
     */
    private function getDefaultModelForProvider(string $provider): string
    {
        $models = self::MODELS[$provider] ?? [];
        return array_key_first($models) ?? '';
    }

    /**
     * Get provider for a specific model
     */
    private function getProviderForModel(string $model): ?string
    {
        foreach (self::MODELS as $provider => $models) {
            if (isset($models[$model])) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * Estimate tokens from text
     */
    private function estimateTokens(string $text): int
    {
        // Rough estimate: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get cost type based on token count
     */
    private function getCostType(int $tokens): string
    {
        if ($tokens < 1000) {
            return 'ai_analysis_small';
        } elseif ($tokens < 5000) {
            return 'ai_analysis_medium';
        }
        return 'ai_analysis_large';
    }

    /**
     * Check if service is configured
     * RETROCOMPATIBLE - Same signature as before
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get current API key
     * RETROCOMPATIBLE - Same signature as before
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get current model
     * RETROCOMPATIBLE - Same signature as before
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get current provider
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Check if fallback is enabled
     */
    public function isFallbackEnabled(): bool
    {
        return $this->fallbackEnabled;
    }

    // ========================================
    // STATIC METHODS FOR ADMIN UI
    // ========================================

    /**
     * Get all available providers
     */
    public static function getProviders(): array
    {
        return self::PROVIDERS;
    }

    /**
     * Get all available models formatted for select
     */
    public static function getAvailableModels(): array
    {
        $result = [];

        foreach (self::MODELS as $provider => $models) {
            $providerLabel = self::PROVIDERS[$provider] ?? $provider;
            $result[$provider] = [
                'label' => $providerLabel,
                'models' => [],
            ];

            foreach ($models as $modelId => $modelInfo) {
                $result[$provider]['models'][$modelId] = [
                    'name' => $modelInfo['name'],
                    'input_cost' => $modelInfo['input'],
                    'output_cost' => $modelInfo['output'],
                    'label' => "{$modelInfo['name']} (in: \${$modelInfo['input']}/1K, out: \${$modelInfo['output']}/1K)",
                ];
            }
        }

        return $result;
    }

    /**
     * Get models for a specific provider (for AJAX)
     */
    public static function getModelsForProvider(string $provider): array
    {
        $models = self::MODELS[$provider] ?? [];
        $result = [];

        foreach ($models as $modelId => $modelInfo) {
            $result[$modelId] = "{$modelInfo['name']} (in: \${$modelInfo['input']}/1K, out: \${$modelInfo['output']}/1K)";
        }

        return $result;
    }

    /**
     * Check if a specific provider is configured
     */
    public static function isProviderConfigured(string $provider): bool
    {
        try {
            $key = $provider === 'openai' ? 'openai_api_key' : 'anthropic_api_key';
            $value = Settings::get($key, '');
            return !empty($value);
        } catch (\Exception $e) {
            $key = $provider === 'openai' ? 'openai_api_key' : 'anthropic_api_key';
            $result = Database::fetch("SELECT value FROM settings WHERE key_name = ?", [$key]);
            return !empty($result['value']);
        }
    }

    /**
     * Get module-specific AI configuration with global context
     * Used by admin UI to display effective configuration
     */
    public static function getModuleAiConfig(string $moduleSlug): array
    {
        $globalProvider = Settings::get('ai_provider', 'anthropic');
        $globalModel = Settings::get('ai_model', 'claude-sonnet-4-20250514');
        $globalFallback = (bool) Settings::get('ai_fallback_enabled', '1');

        $moduleProvider = \Core\ModuleLoader::getSetting($moduleSlug, 'ai_provider', 'global');
        $moduleModel = \Core\ModuleLoader::getSetting($moduleSlug, 'ai_model', 'global');
        $moduleFallback = \Core\ModuleLoader::getSetting($moduleSlug, 'ai_fallback_enabled', 'global');

        // Calculate effective values
        $effectiveProvider = ($moduleProvider !== 'global' && !empty($moduleProvider)) ? $moduleProvider : $globalProvider;
        $effectiveModel = ($moduleModel !== 'global' && !empty($moduleModel)) ? $moduleModel : $globalModel;
        $effectiveFallback = ($moduleFallback !== 'global' && $moduleFallback !== null) ? (bool) $moduleFallback : $globalFallback;

        return [
            'global' => [
                'provider' => $globalProvider,
                'provider_label' => self::PROVIDERS[$globalProvider] ?? $globalProvider,
                'model' => $globalModel,
                'model_label' => self::MODELS[$globalProvider][$globalModel]['name'] ?? $globalModel,
                'fallback' => $globalFallback,
            ],
            'module' => [
                'provider' => $moduleProvider ?? 'global',
                'model' => $moduleModel ?? 'global',
                'fallback' => $moduleFallback ?? 'global',
            ],
            'effective' => [
                'provider' => $effectiveProvider,
                'provider_label' => self::PROVIDERS[$effectiveProvider] ?? $effectiveProvider,
                'model' => $effectiveModel,
                'model_label' => self::MODELS[$effectiveProvider][$effectiveModel]['name'] ?? $effectiveModel,
                'fallback' => $effectiveFallback,
            ],
        ];
    }

    /**
     * Get configuration status for admin UI
     */
    public static function getConfigurationStatus(): array
    {
        return [
            'anthropic' => [
                'configured' => self::isProviderConfigured('anthropic'),
                'label' => self::PROVIDERS['anthropic'],
            ],
            'openai' => [
                'configured' => self::isProviderConfigured('openai'),
                'label' => self::PROVIDERS['openai'],
            ],
        ];
    }

    // ========================================
    // UTF-8 SANITIZATION HELPERS
    // ========================================

    /**
     * Sanitize array of messages for valid UTF-8
     */
    private function sanitizeMessagesUtf8(array $messages): array
    {
        foreach ($messages as &$msg) {
            if (isset($msg['content'])) {
                $msg['content'] = $this->sanitizeStringUtf8($msg['content']);
            }
        }
        return $messages;
    }

    /**
     * Sanitize a string for valid UTF-8 encoding
     * Removes invalid UTF-8 sequences that would cause json_encode to fail
     */
    private function sanitizeStringUtf8(string $text): string
    {
        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Convert to UTF-8 if not already valid
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to detect encoding and convert
            $detected = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $text = mb_convert_encoding($text, 'UTF-8', $detected);
            } else {
                // Force UTF-8 by filtering invalid sequences
                $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
            }
        }

        // Remove any remaining invalid UTF-8 sequences (control characters except tabs, newlines)
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Remove non-printable unicode characters (except newlines, tabs, etc)
        $text = preg_replace('/[^\PC\s]/u', '', $text);

        return $text;
    }
}
