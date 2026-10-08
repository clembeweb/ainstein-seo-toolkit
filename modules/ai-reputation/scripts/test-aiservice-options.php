<?php
// Test senza rete: modelli 5.5 in listino, output_config.effort solo sui modelli che lo supportano,
// estrazione del testo dalle risposte Messages API (blocchi thinking ignorati).
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

$check('modello Anthropic di default invariato (primo del listino)', array_key_first(\Services\AiService::MODELS['anthropic']) === 'claude-sonnet-4-20250514');
$p3 = \Services\AiService::buildAnthropicPayload('claude-opus-5-5', [['role' => 'user', 'content' => 'ciao']], 4096, null, 'ultra');
$check('effort non valido ignorato', !isset($p3['output_config']));

// Effort inviato solo ai modelli che lo supportano (Claude 4.5+ e 5.x)
$msg = [['role' => 'user', 'content' => 'ciao']];
$p4 = \Services\AiService::buildAnthropicPayload('claude-sonnet-4-20250514', $msg, 4096, null, 'high');
$check('effort ignorato su claude-sonnet-4-20250514', !isset($p4['output_config']));
$p5 = \Services\AiService::buildAnthropicPayload('claude-opus-5-5', $msg, 4096, null, 'high');
$check('effort high su claude-opus-5-5', ($p5['output_config']['effort'] ?? null) === 'high');
$p6 = \Services\AiService::buildAnthropicPayload('claude-sonnet-4-6', $msg, 4096, null, 'high');
$check('effort presente su claude-sonnet-4-6', ($p6['output_config']['effort'] ?? null) === 'high');
$check('effort ignorato su claude-3-5-haiku-20241022', !isset(\Services\AiService::buildAnthropicPayload('claude-3-5-haiku-20241022', $msg, 4096, null, 'high')['output_config']));
$check('effort ignorato su claude-opus-4-20250514', !isset(\Services\AiService::buildAnthropicPayload('claude-opus-4-20250514', $msg, 4096, null, 'high')['output_config']));

// Estrazione testo dalle risposte Messages API
$x = fn(array $r): string => \Services\AiService::extractAnthropicText($r);
$check('estrazione: solo testo', $x(['content' => [['type' => 'text', 'text' => 'ciao']]]) === 'ciao');
$check('estrazione: thinking + testo -> solo testo', $x(['content' => [
    ['type' => 'thinking', 'thinking' => 'ragiono...', 'signature' => 'abc'],
    ['type' => 'redacted_thinking', 'data' => 'xyz'],
    ['type' => 'text', 'text' => '{"ok":true}'],
]]) === '{"ok":true}');
$check('estrazione: due blocchi testo uniti', $x(['content' => [['type' => 'text', 'text' => 'uno'], ['type' => 'text', 'text' => 'due']]]) === "uno\ndue");
$check('estrazione: nessun blocco testo -> stringa vuota', $x(['content' => [['type' => 'thinking', 'thinking' => 'x']]]) === '' && $x([]) === '');

exit($fail ? 1 : 0);
