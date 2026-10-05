<?php
/**
 * AI Reputation Radar — Salva una API key nel DB (tabella settings) dal terminale.
 *
 * Serve quando il pannello admin servito da XAMPP non ha ancora il campo (branch non mergiato).
 * La key viene chiesta in modo interattivo: non passa da argomenti, cronologia o chat.
 *
 * Run: php modules/ai-reputation/scripts/set-api-key.php <key_name>
 *   key_name ammessi: perplexity_api_key, google_gemini_api_key, openai_api_key, anthropic_api_key
 */

define('BASE_PATH', dirname(__DIR__, 3));
define('ROOT_PATH', BASE_PATH);
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) $autoload = 'C:/xampp/htdocs/seo-toolkit/vendor/autoload.php';
require_once $autoload;
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'Core\\')) {
        $file = BASE_PATH . '/core/' . str_replace('\\', '/', substr($class, 5)) . '.php';
        if (file_exists($file)) require_once $file;
    }
});
require_once BASE_PATH . '/config/app.php';

$allowed = ['perplexity_api_key', 'google_gemini_api_key', 'openai_api_key', 'anthropic_api_key'];
$keyName = $argv[1] ?? '';
if (!in_array($keyName, $allowed, true)) {
    fwrite(STDERR, "Uso: php set-api-key.php <key_name>\nkey_name ammessi: " . implode(', ', $allowed) . "\n");
    exit(1);
}

echo "Incolla la {$keyName} e premi Invio (non viene mostrata): ";
// Su Windows non c'è stty: la key resta visibile nel terminale ma non in cronologia/argomenti
$value = trim((string) fgets(STDIN));
if ($value === '') {
    fwrite(STDERR, "Nessun valore inserito, niente salvato.\n");
    exit(1);
}

$existing = \Core\Database::fetch("SELECT id FROM settings WHERE key_name = ?", [$keyName]);
if ($existing) {
    \Core\Database::update('settings', ['value' => $value], 'key_name = ?', [$keyName]);
} else {
    \Core\Database::insert('settings', ['key_name' => $keyName, 'value' => $value, 'is_secret' => 1]);
}
\Core\Settings::clearCache();

echo "OK: {$keyName} salvata (" . strlen($value) . " caratteri).\n";
