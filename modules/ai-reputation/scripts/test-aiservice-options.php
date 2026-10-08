<?php
// Test senza rete: i modelli 5.5 sono in listino e il payload Anthropic porta output_config.effort.
// Run: php modules/ai-reputation/scripts/test-aiservice-options.php
declare(strict_types=1);
require __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../services/AiService.php';

$fail = 0;
$check = function (string $name, bool $ok) use (&$fail): void {
    echo ($ok ? 'PASS' : 'FAIL') . " {$name}\n";
    if (!$ok) { $fail++; }
};

$models = \Services\AiService::MODELS['anthropic'];
$check('claude-opus-5-5 in listino', isset($models['claude-opus-5-5']) && $models['claude-opus-5-5']['input'] === 0.004 && $models['claude-opus-5-5']['output'] === 0.020);
$check('claude-sonnet-5-5 in listino', isset($models['claude-sonnet-5-5']) && $models['claude-sonnet-5-5']['input'] === 0.002);
$check('claude-haiku-5-5 in listino', isset($models['claude-haiku-5-5']) && $models['claude-haiku-5-5']['output'] === 0.0005);

$p = \Services\AiService::buildAnthropicPayload('claude-opus-5-5', [['role' => 'user', 'content' => 'ciao']], 8192, 'sys', 'high');
$check('payload con effort', ($p['output_config']['effort'] ?? null) === 'high' && $p['model'] === 'claude-opus-5-5' && $p['max_tokens'] === 8192 && $p['system'] === 'sys');
$p2 = \Services\AiService::buildAnthropicPayload('claude-sonnet-4-20250514', [['role' => 'user', 'content' => 'ciao']], 4096, null, null);
$check('payload senza effort e senza system', !isset($p2['output_config']) && !isset($p2['system']));

exit($fail ? 1 : 0);
