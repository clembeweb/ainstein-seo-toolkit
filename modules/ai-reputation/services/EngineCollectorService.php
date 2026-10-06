<?php

namespace Modules\AiReputation\Services;

use Core\Settings;
use Core\ModuleLoader;
use Services\ApiLoggerService;

/**
 * EngineCollectorService - pone una domanda a un engine AI con ricerca web e
 * restituisce una risposta normalizzata (testo + citazioni + fonti lette + costi).
 *
 * ADR-002: FUORI da AiService, nessun fallback tra engine. Gli engine sono l'oggetto misurato.
 * Formati verificati empiricamente il 2026-10-05 (docs/test-empirici/).
 */
class EngineCollectorService
{
    private const SLUG = 'ai-reputation';
    private const TIMEOUT = 180;

    /**
     * @param string $engine openai|gemini|perplexity|anthropic
     * @param string $prompt domanda
     * @param array  $opts   ['cluster' => nav|rep|comm|comp, 'user_id' => int, 'context' => string]
     * @return array risposta normalizzata (vedi normalize())
     */
    public function ask(string $engine, string $prompt, array $opts = []): array
    {
        $t0 = microtime(true);
        try {
            $result = match ($engine) {
                'openai' => $this->askOpenAi($prompt, $opts),
                'gemini' => $this->askGemini($prompt, $opts),
                'perplexity' => $this->askPerplexity($prompt, $opts),
                'anthropic' => $this->askAnthropic($prompt, $opts),
                default => throw new \InvalidArgumentException("Engine sconosciuto: {$engine}"),
            };
        } catch (\Throwable $e) {
            $result = ['status' => 'error', 'error_message' => $e->getMessage(), 'raw' => null];
        }
        $result['engine'] = $engine;
        $result['latency_ms'] = (int) round((microtime(true) - $t0) * 1000);
        return $this->normalize($result);
    }

    public static function isConfigured(string $engine): bool
    {
        return !empty(Settings::get(self::keyName($engine)));
    }

    private static function keyName(string $engine): string
    {
        return match ($engine) {
            'openai' => 'openai_api_key',
            'gemini' => 'google_gemini_api_key',
            'perplexity' => 'perplexity_api_key',
            'anthropic' => 'anthropic_api_key',
            default => '',
        };
    }

    private function requireKey(string $engine): string
    {
        $key = (string) Settings::get(self::keyName($engine));
        if ($key === '') {
            throw new \RuntimeException("API key {$engine} non configurata (admin settings)");
        }
        return $key;
    }

    private function setting(string $key, mixed $default): mixed
    {
        return ModuleLoader::getSetting(self::SLUG, $key, $default);
    }

    private function contextSize(array $opts): string
    {
        // nav/rep: contesto ridotto (meno token), comm/comp: medio
        return in_array($opts['cluster'] ?? 'nav', ['nav', 'rep'], true) ? 'low' : 'medium';
    }

    // =============================================
    // OPENAI - Responses API + web_search
    // =============================================
    private function askOpenAi(string $prompt, array $opts): array
    {
        $apiKey = $this->requireKey('openai');
        $model = (string) $this->setting('engine_openai_model', 'gpt-5-mini');
        $maxToolCalls = (int) $this->setting('engine_openai_max_tool_calls', 3);

        $body = [
            'model' => $model,
            'tools' => [[
                'type' => 'web_search',
                'search_context_size' => $this->contextSize($opts),
                'user_location' => ['type' => 'approximate', 'country' => 'IT'],
            ]],
            'include' => ['web_search_call.action.sources'],
            'input' => $prompt,
        ];
        if ($maxToolCalls > 0) {
            $body['max_tool_calls'] = $maxToolCalls;
        }

        [$json, $http, $raw, $t0] = $this->post('https://api.openai.com/v1/responses', $body, [
            'Authorization: Bearer ' . $apiKey,
        ]);
        $this->log('openai', '/v1/responses', $body, $json, $http, $t0, $opts);
        if ($http >= 400 || !is_array($json)) {
            return $this->apiError($http, $json, $raw);
        }

        $text = '';
        $citations = [];
        $sources = [];
        $queries = [];
        $searchCount = 0;
        foreach ($json['output'] ?? [] as $item) {
            $type = $item['type'] ?? '';
            if ($type === 'web_search_call') {
                $searchCount++;
                if (!empty($item['action']['query'])) {
                    $queries[] = $item['action']['query'];
                }
                foreach ($item['action']['sources'] ?? [] as $s) {
                    if (!empty($s['url'])) {
                        $sources[] = ['url' => $this->cleanUrl($s['url']), 'title' => null];
                    }
                }
            }
            if ($type === 'message') {
                foreach ($item['content'] ?? [] as $c) {
                    $text .= $c['text'] ?? '';
                    foreach ($c['annotations'] ?? [] as $a) {
                        if (($a['type'] ?? '') === 'url_citation' && !empty($a['url'])) {
                            $citations[] = ['url' => $this->cleanUrl($a['url']), 'title' => $a['title'] ?? null];
                        }
                    }
                }
            }
        }
        $usage = $json['usage'] ?? [];
        $in = (int) ($usage['input_tokens'] ?? 0);
        $out = (int) ($usage['output_tokens'] ?? 0);
        $cost = $searchCount * ((float) $this->setting('price_openai_search_per_1k', 10) / 1000)
            + $in * ((float) $this->setting('price_openai_in_per_m', 0.25) / 1e6)
            + $out * ((float) $this->setting('price_openai_out_per_m', 2) / 1e6);

        return [
            'status' => 'ok',
            'model' => $json['model'] ?? $model,
            'text' => $text,
            'citations' => $citations,
            'sources_read' => $sources,
            'queries' => $queries,
            'search_count' => $searchCount,
            'tokens_in' => $in,
            'tokens_out' => $out,
            'cost' => $cost,
            'cost_is_real' => 0,
            'raw' => $json,
        ];
    }

    // =============================================
    // GEMINI - generateContent + Grounding with Google Search
    // =============================================
    private function askGemini(string $prompt, array $opts): array
    {
        $apiKey = $this->requireKey('gemini');
        $model = (string) $this->setting('engine_gemini_model', 'gemini-3.8-flash');

        $body = [
            'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'tools' => [['googleSearch' => (object) []]],
        ];
        [$json, $http, $raw, $t0] = $this->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
            $body,
            ['x-goog-api-key: ' . $apiKey]
        );
        $this->log('google_gemini', "/v1beta/models/{$model}:generateContent", $body, $json, $http, $t0, $opts);
        if ($http >= 400 || !is_array($json)) {
            return $this->apiError($http, $json, $raw);
        }

        $cand = $json['candidates'][0] ?? [];
        $text = '';
        foreach ($cand['content']['parts'] ?? [] as $part) {
            $text .= $part['text'] ?? '';
        }
        $gm = $cand['groundingMetadata'] ?? [];
        $queries = $gm['webSearchQueries'] ?? [];
        $chunks = [];
        foreach ($gm['groundingChunks'] ?? [] as $i => $gc) {
            $w = $gc['web'] ?? [];
            if (empty($w['uri'])) {
                continue;
            }
            $chunks[$i] = [
                'url' => $this->resolveRedirect($w['uri']) ?? $w['uri'],
                'title' => $w['title'] ?? null,
                'domain' => $w['domain'] ?? null,
            ];
        }
        $citedIdx = [];
        foreach ($gm['groundingSupports'] ?? [] as $gs) {
            foreach ($gs['groundingChunkIndices'] ?? [] as $ci) {
                $citedIdx[$ci] = true;
            }
        }
        $citations = [];
        foreach ($chunks as $i => $c) {
            if (isset($citedIdx[$i])) {
                $citations[] = $c;
            }
        }
        $usage = $json['usageMetadata'] ?? [];
        $in = (int) ($usage['promptTokenCount'] ?? 0);
        $out = (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0);
        $searchCount = count($queries) > 0 ? 1 : 0; // fatturazione per richiesta grounded
        $cost = $searchCount * ((float) $this->setting('price_gemini_search_per_1k', 14) / 1000)
            + $in * ((float) $this->setting('price_gemini_in_per_m', 0.75) / 1e6)
            + $out * ((float) $this->setting('price_gemini_out_per_m', 3.75) / 1e6);

        return [
            'status' => 'ok',
            'model' => $json['modelVersion'] ?? $model,
            'text' => $text,
            'citations' => $citations,
            'sources_read' => array_values($chunks),
            'queries' => $queries,
            'search_count' => count($queries),
            'tokens_in' => $in,
            'tokens_out' => $out,
            'cost' => $cost,
            'cost_is_real' => 0,
            'raw' => $json,
        ];
    }

    // =============================================
    // PERPLEXITY - Agent API /v1/agent (preset fast = ex Sonar)
    // =============================================
    private function askPerplexity(string $prompt, array $opts): array
    {
        $apiKey = $this->requireKey('perplexity');
        $preset = (string) $this->setting('engine_perplexity_preset', 'fast');

        $body = [
            'preset' => $preset,
            'input' => $prompt,
            'tools' => [[
                'type' => 'web_search',
                'search_context_size' => $this->contextSize($opts),
                'user_location' => ['country' => 'IT'],
            ]],
        ];
        [$json, $http, $raw, $t0] = $this->post('https://api.perplexity.ai/v1/agent', $body, [
            'Authorization: Bearer ' . $apiKey,
        ]);
        $this->log('perplexity', '/v1/agent', $body, $json, $http, $t0, $opts);
        if ($http >= 400 || !is_array($json)) {
            return $this->apiError($http, $json, $raw);
        }

        $text = '';
        $results = [];
        $queries = [];
        $annotations = [];
        foreach ($json['output'] ?? [] as $item) {
            $type = $item['type'] ?? '';
            if ($type === 'search_results') {
                $queries = array_merge($queries, $item['queries'] ?? []);
                foreach ($item['results'] ?? [] as $r) {
                    if (empty($r['url'])) {
                        continue;
                    }
                    $results[(int) ($r['id'] ?? 0)] = [
                        'url' => $r['url'],
                        'title' => $r['title'] ?? null,
                        'date' => $r['date'] ?? null,
                        'snippet' => isset($r['snippet']) ? mb_substr($r['snippet'], 0, 500) : null,
                    ];
                }
            }
            if ($type === 'message') {
                foreach ($item['content'] ?? [] as $c) {
                    $text .= $c['text'] ?? '';
                    foreach ($c['annotations'] ?? [] as $a) {
                        if (($a['type'] ?? '') === 'url_citation' && !empty($a['url'])) {
                            $annotations[] = ['url' => $a['url'], 'title' => $a['title'] ?? null];
                        }
                    }
                }
            }
        }
        if ($text === '' && !empty($json['output_text'])) {
            $text = (string) $json['output_text'];
        }
        // Citazioni: annotazioni se ci sono, altrimenti marker inline [n] risolti sugli id dei search_results
        $citations = $annotations;
        if (empty($citations)) {
            preg_match_all('/\[(\d+)\]/', $text, $m);
            foreach (array_unique(array_map('intval', $m[1] ?? [])) as $id) {
                if (isset($results[$id])) {
                    $citations[] = ['url' => $results[$id]['url'], 'title' => $results[$id]['title']];
                }
            }
        }
        $usage = $json['usage'] ?? [];
        $tool = $usage['tool_calls_details'] ?? [];
        $searchCount = (int) ($tool['search_web']['invocation'] ?? $tool['web_search']['invocation'] ?? 0);
        $realCost = $usage['cost']['total_cost'] ?? null;

        return [
            'status' => 'ok',
            'model' => $json['model'] ?? $preset,
            'text' => $text,
            'citations' => $citations,
            'sources_read' => array_values($results),
            'queries' => $queries,
            'search_count' => $searchCount,
            'tokens_in' => (int) ($usage['input_tokens'] ?? 0),
            'tokens_out' => (int) ($usage['output_tokens'] ?? 0),
            'cost' => $realCost !== null ? (float) $realCost : 0.0,
            'cost_is_real' => $realCost !== null ? 1 : 0,
            'raw' => $json,
        ];
    }

    // =============================================
    // ANTHROPIC - Messages + web_search (allowed_callers direct)
    // =============================================
    private function askAnthropic(string $prompt, array $opts): array
    {
        $apiKey = $this->requireKey('anthropic');
        $model = (string) $this->setting('engine_anthropic_model', 'claude-sonnet-5-5');

        $body = [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'tools' => [[
                'type' => 'web_search_20260318',
                'name' => 'web_search',
                'max_uses' => 5,
                'allowed_callers' => ['direct'],
                'user_location' => ['type' => 'approximate', 'country' => 'IT'],
            ]],
        ];
        [$json, $http, $raw, $t0] = $this->post('https://api.anthropic.com/v1/messages', $body, [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ]);
        $this->log('anthropic', '/v1/messages', $body, $json, $http, $t0, $opts);
        if ($http >= 400 || !is_array($json)) {
            return $this->apiError($http, $json, $raw);
        }

        $text = '';
        $citations = [];
        $sources = [];
        $queries = [];
        foreach ($json['content'] ?? [] as $block) {
            $type = $block['type'] ?? '';
            if ($type === 'text') {
                $text .= $block['text'] ?? '';
                foreach ($block['citations'] ?? [] as $c) {
                    if (($c['type'] ?? '') === 'web_search_result_location' && !empty($c['url'])) {
                        $citations[] = ['url' => $c['url'], 'title' => $c['title'] ?? null];
                    }
                }
            }
            if ($type === 'server_tool_use' && !empty($block['input']['query'])) {
                $queries[] = $block['input']['query'];
            }
            if ($type === 'web_search_tool_result') {
                foreach ($block['content'] ?? [] as $r) {
                    if (!empty($r['url'])) {
                        $sources[] = ['url' => $r['url'], 'title' => $r['title'] ?? null];
                    }
                }
            }
        }
        $usage = $json['usage'] ?? [];
        $in = (int) ($usage['input_tokens'] ?? 0);
        $out = (int) ($usage['output_tokens'] ?? 0);
        $searchCount = (int) ($usage['server_tool_use']['web_search_requests'] ?? count($queries));
        $cost = $searchCount * ((float) $this->setting('price_anthropic_search_per_1k', 10) / 1000)
            + $in * ((float) $this->setting('price_anthropic_in_per_m', 2) / 1e6)
            + $out * ((float) $this->setting('price_anthropic_out_per_m', 10) / 1e6);

        return [
            'status' => 'ok',
            'model' => $json['model'] ?? $model,
            'text' => $text,
            'citations' => $citations,
            'sources_read' => $sources,
            'queries' => $queries,
            'search_count' => $searchCount,
            'tokens_in' => $in,
            'tokens_out' => $out,
            'cost' => $cost,
            'cost_is_real' => 0,
            'raw' => $json,
        ];
    }

    // =============================================
    // HELPERS
    // =============================================

    /** @return array [json|null, httpCode, rawBody, startTime] */
    private function post(string $url, array $body, array $headers): array
    {
        $t0 = microtime(true);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => self::TIMEOUT,
        ]);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new \RuntimeException('Errore di rete: ' . $err);
        }
        return [json_decode($raw, true), $http, (string) $raw, $t0];
    }

    private function apiError(int $http, mixed $json, string $raw): array
    {
        $msg = $json['error']['message'] ?? ($json[0]['error']['message'] ?? null);
        if (!$msg) {
            $msg = mb_substr(trim($raw), 0, 300) ?: 'Risposta vuota';
        }
        return ['status' => 'error', 'error_message' => "HTTP {$http}: {$msg}", 'raw' => $json];
    }

    private function log(string $provider, string $endpoint, array $request, mixed $response, int $http, float $t0, array $opts): void
    {
        try {
            ApiLoggerService::log($provider, $endpoint, ['input' => mb_substr(json_encode($request, JSON_UNESCAPED_UNICODE), 0, 2000)], is_array($response) ? $response : null, $http, $t0, [
                'module' => self::SLUG,
                'cost' => 0,
                'context' => $opts['context'] ?? 'collector',
                'user_id' => $opts['user_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // il log non deve mai bloccare la raccolta
        }
    }

    private function cleanUrl(string $url): string
    {
        $clean = preg_replace('/([?&])utm_source=openai(&|$)/', '$1', $url) ?: $url;
        return rtrim($clean, '?&');
    }

    /** Gemini: gli URL sono redirect vertexaisearch → HEAD senza follow restituisce il Location reale */
    private function resolveRedirect(string $url): ?string
    {
        if (!str_contains($url, 'vertexaisearch.cloud.google.com')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 8]);
        curl_exec($ch);
        $loc = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        return $loc ?: null;
    }

    private function normalize(array $r): array
    {
        $citations = [];
        $seen = [];
        foreach ($r['citations'] ?? [] as $c) {
            $url = trim((string) ($c['url'] ?? ''));
            if (!preg_match('#^https?://#i', $url)) {
                continue; // solo link web: mai javascript:, data:, file:
            }
            if ($url === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $citations[] = [
                'url' => $url,
                'title' => self::readableTitle($url, $c['title'] ?? null),
                'domain' => $c['domain'] ?? self::domainOf($url),
            ];
        }
        $sources = [];
        foreach ($r['sources_read'] ?? [] as $s) {
            $url = trim((string) ($s['url'] ?? ''));
            if (!preg_match('#^https?://#i', $url)) {
                continue;
            }
            if ($url === '') {
                continue;
            }
            $sources[] = array_merge($s, ['url' => $url, 'domain' => $s['domain'] ?? self::domainOf($url)]);
        }
        return [
            'engine' => $r['engine'],
            'status' => $r['status'] ?? 'error',
            'model' => $r['model'] ?? null,
            'text' => $r['text'] ?? '',
            'citations' => $citations,
            'sources_read' => $sources,
            'queries' => array_values(array_unique($r['queries'] ?? [])),
            'search_count' => $r['search_count'] ?? null,
            'tokens_in' => $r['tokens_in'] ?? null,
            'tokens_out' => $r['tokens_out'] ?? null,
            'cost' => round((float) ($r['cost'] ?? 0), 6),
            'cost_is_real' => (int) ($r['cost_is_real'] ?? 0),
            'latency_ms' => $r['latency_ms'] ?? null,
            'error_message' => $r['error_message'] ?? null,
            'raw' => $r['raw'] ?? null,
        ];
    }

    /**
     * Titolo leggibile per una fonte. Gemini dà come titolo solo il dominio: in quel caso lo ricava
     * dall'ultimo pezzo dell'URL ("/2025/12/litalia-come-piattaforma-strategica.html" → "Litalia come piattaforma strategica").
     */
    public static function readableTitle(string $url, ?string $title): string
    {
        $title = self::fixMojibake(trim((string) $title));
        $domain = self::domainOf($url) ?? '';
        $isDomainOnly = $title === '' || strtolower(preg_replace('/^www\./', '', $title)) === $domain;
        if (!$isDomainOnly) {
            return $title;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
        $clean = function (string $seg): string {
            $seg = self::fixMojibake(urldecode($seg));
            $seg = preg_replace('/\.(html?|php|aspx?|shtml|pdf|docx?)$/i', '', $seg);
            return preg_replace('/[-_][a-z0-9]*\d[a-z0-9]{5,}$/i', '', $seg) ?? $seg; // id finali tipo -dasj1hmf o -202607241509
        };
        $pretty = function (string $words) use ($domain): string {
            $words = trim(preg_replace('/[-_]+|(?<=[a-z])(?=[A-Z])/', ' ', $words));
            return $domain . ' — ' . mb_strtoupper(mb_substr($words, 0, 1)) . mb_substr($words, 1, 110);
        };
        // 1. ultimo segmento "parlante" (almeno 3 parole)
        for ($i = count($segments) - 1; $i >= 0; $i--) {
            $seg = $clean($segments[$i]);
            if (preg_match('/[a-z]/i', $seg) && substr_count($seg, '-') + substr_count($seg, '_') >= 2 && mb_strlen($seg) >= 12) {
                return $pretty($seg);
            }
        }
        // 2. altrimenti l'ultimo segmento non numerico (es. "beniDia" → "Beni Dia")
        for ($i = count($segments) - 1; $i >= 0; $i--) {
            $seg = $clean($segments[$i]);
            if (preg_match('/[a-z]{3,}/i', $seg) && !in_array(strtolower($seg), ['index', 'home', 'news', 'cs', 'it', 'en', 'amp'], true)) {
                return $pretty($seg);
            }
        }
        return $domain !== '' ? $domain : $url;
    }

    /** Ripara testo UTF-8 letto come Windows-1252 (es. "â€˜ndrangheta" → "‘ndrangheta") */
    private static function fixMojibake(string $s): string
    {
        if ($s === '' || !preg_match('/Ã|â€|Â/u', $s)) {
            return $s;
        }
        // Sostituzione mirata delle sequenze rotte più comuni (il resto del titolo può essere già corretto)
        return strtr($s, [
            'â€˜' => '‘', 'â€™' => '’', 'â€œ' => '“', 'â€' . "\u{009D}" => '”', 'â€“' => '–', 'â€”' => '—', 'â€¦' => '…', 'â€¢' => '•',
            'Ã¨' => 'è', 'Ã©' => 'é', 'Ã ' => 'à', 'Ã²' => 'ò', 'Ã¹' => 'ù', 'Ã¬' => 'ì', 'Ã‰' => 'É', 'Ãˆ' => 'È', 'Â ' => ' ', 'Â«' => '«', 'Â»' => '»',
        ]);
    }

    public static function domainOf(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }
        return strtolower(preg_replace('/^www\./', '', $host));
    }
}
