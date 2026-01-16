<?php
/**
 * Test Sistema Crediti
 * Verifica che Credits::getCost() legga correttamente dai module settings
 * e che Credits::consume() scali i crediti utente
 *
 * Esegui da browser: /tests/test-credits-system.php
 */

require_once __DIR__ . '/../public/index.php';

use Core\Auth;
use Core\Credits;
use Core\Database;

// Verifica autenticazione
$user = Auth::user();
if (!$user) {
    die("ERROR: Devi essere loggato per eseguire questo test");
}

echo "<pre style='font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; margin: 0;'>";
echo "===========================================\n";
echo "   TEST SISTEMA CREDITI - SEO TOOLKIT\n";
echo "===========================================\n\n";

// 1. Info utente
echo "<span style='color: #569cd6;'>1. INFO UTENTE</span>\n";
echo "   User ID: {$user['id']}\n";
echo "   Nome: {$user['name']}\n";
echo "   Email: {$user['email']}\n";

$balanceBefore = Credits::getBalance($user['id']);
echo "   <span style='color: #4ec9b0;'>Crediti attuali: {$balanceBefore}</span>\n\n";

// 2. Test Credits::getCost() per ogni modulo
echo "<span style='color: #569cd6;'>2. TEST Credits::getCost() - LETTURA COSTI</span>\n\n";

$testCosts = [
    ['operation' => 'serp_extraction', 'module' => 'ai-content', 'expected' => 3],
    ['operation' => 'content_scrape', 'module' => 'ai-content', 'expected' => 1],
    ['operation' => 'brief_generation', 'module' => 'ai-content', 'expected' => 5],
    ['operation' => 'article_generation', 'module' => 'ai-content', 'expected' => 10],
    ['operation' => 'gsc_full_sync', 'module' => 'seo-tracking', 'expected' => 10],
    ['operation' => 'quick_wins', 'module' => 'seo-tracking', 'expected' => 2],
    ['operation' => 'context_extraction', 'module' => 'ads-analyzer', 'expected' => 3],
    ['operation' => 'ai_overview', 'module' => 'seo-audit', 'expected' => 15],
    ['operation' => 'ai_category', 'module' => 'seo-audit', 'expected' => 3],
];

echo "   <span style='color: #ce9178;'>Operazione</span>                    <span style='color: #ce9178;'>Modulo</span>          <span style='color: #ce9178;'>Costo</span>   <span style='color: #ce9178;'>Default</span>  <span style='color: #ce9178;'>Status</span>\n";
echo "   " . str_repeat("-", 75) . "\n";

foreach ($testCosts as $test) {
    $cost = Credits::getCost($test['operation'], $test['module']);
    $status = ($cost > 0) ? "<span style='color: #6a9955;'>OK</span>" : "<span style='color: #f44747;'>WARN</span>";
    $match = ($cost == $test['expected']) ? "" : " <span style='color: #dcdcaa;'>(modificato)</span>";

    printf("   %-28s %-15s %-7s %-8s %s%s\n",
        $test['operation'],
        $test['module'],
        $cost,
        $test['expected'],
        $status,
        $match
    );
}

// 3. Verifica lettura da modules.settings
echo "\n<span style='color: #569cd6;'>3. VERIFICA DATABASE - modules.settings</span>\n\n";

$modules = Database::fetchAll("SELECT id, slug, name, settings FROM modules WHERE is_active = 1");

foreach ($modules as $module) {
    echo "   <span style='color: #4ec9b0;'>{$module['name']}</span> ({$module['slug']})\n";

    if (!empty($module['settings'])) {
        $settings = json_decode($module['settings'], true);
        $costSettings = array_filter($settings ?? [], fn($k) => str_starts_with($k, 'cost_'), ARRAY_FILTER_USE_KEY);

        if (!empty($costSettings)) {
            foreach ($costSettings as $key => $value) {
                echo "      - {$key}: {$value}\n";
            }
        } else {
            echo "      <span style='color: #808080;'>(nessun costo configurato - usa defaults)</span>\n";
        }
    } else {
        echo "      <span style='color: #808080;'>(settings vuoti - usa defaults)</span>\n";
    }
}

// 4. Test consumo crediti (solo se richiesto)
echo "\n<span style='color: #569cd6;'>4. TEST CONSUMO CREDITI</span>\n";

if (isset($_GET['test_consume']) && $_GET['test_consume'] === '1') {
    $testAmount = 0.1; // Consumo minimo per test

    echo "   Esecuzione test consumo...\n";
    echo "   Crediti PRIMA: {$balanceBefore}\n";

    $result = Credits::consume(
        $user['id'],
        $testAmount,
        'test_credits_system',
        'seo-toolkit',
        ['test' => true, 'timestamp' => date('Y-m-d H:i:s')]
    );

    $balanceAfter = Credits::getBalance($user['id']);
    $diff = $balanceBefore - $balanceAfter;

    echo "   Crediti DOPO: {$balanceAfter}\n";
    echo "   Differenza: {$diff}\n";

    if ($result && $diff == $testAmount) {
        echo "   <span style='color: #6a9955;'>SUCCESSO: Crediti scalati correttamente!</span>\n";
    } elseif (!$result) {
        echo "   <span style='color: #f44747;'>ERRORE: Credits::consume() ha restituito false</span>\n";
        echo "   (Probabilmente crediti insufficienti)\n";
    } else {
        echo "   <span style='color: #f44747;'>ERRORE: Differenza non corrisponde ({$diff} != {$testAmount})</span>\n";
    }

    // Verifica log
    $lastLog = Database::fetch(
        "SELECT * FROM usage_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );

    if ($lastLog && $lastLog['action'] === 'test_credits_system') {
        echo "\n   <span style='color: #6a9955;'>Log usage_log creato:</span>\n";
        echo "      - action: {$lastLog['action']}\n";
        echo "      - credits_used: {$lastLog['credits_used']}\n";
        echo "      - module_slug: {$lastLog['module_slug']}\n";
    }

    $lastTransaction = Database::fetch(
        "SELECT * FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
        [$user['id']]
    );

    if ($lastTransaction && $lastTransaction['description'] === 'test_credits_system') {
        echo "\n   <span style='color: #6a9955;'>Log credit_transactions creato:</span>\n";
        echo "      - type: {$lastTransaction['type']}\n";
        echo "      - amount: {$lastTransaction['amount']}\n";
        echo "      - balance_after: {$lastTransaction['balance_after']}\n";
    }

} else {
    echo "   <span style='color: #dcdcaa;'>Test consumo non eseguito.</span>\n";
    echo "   Per testare il consumo crediti, aggiungi ?test_consume=1 all'URL\n";
    echo "   <a href='?test_consume=1' style='color: #569cd6;'>Clicca qui per eseguire test consumo (scala 0.1 crediti)</a>\n";
}

// 5. Ultime transazioni
echo "\n<span style='color: #569cd6;'>5. ULTIME 5 TRANSAZIONI</span>\n\n";

$transactions = Database::fetchAll(
    "SELECT * FROM credit_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$user['id']]
);

if (empty($transactions)) {
    echo "   <span style='color: #808080;'>(nessuna transazione)</span>\n";
} else {
    echo "   <span style='color: #ce9178;'>Data</span>                  <span style='color: #ce9178;'>Tipo</span>      <span style='color: #ce9178;'>Amount</span>   <span style='color: #ce9178;'>Descrizione</span>\n";
    echo "   " . str_repeat("-", 70) . "\n";

    foreach ($transactions as $t) {
        $amount = $t['amount'] >= 0 ? "+{$t['amount']}" : $t['amount'];
        $color = $t['amount'] >= 0 ? '#6a9955' : '#f44747';
        printf("   %-20s %-10s <span style='color: %s;'>%-8s</span> %s\n",
            $t['created_at'],
            $t['type'],
            $color,
            $amount,
            substr($t['description'], 0, 30)
        );
    }
}

// 6. Ultimo usage log
echo "\n<span style='color: #569cd6;'>6. ULTIMI 5 USAGE LOG</span>\n\n";

$usageLogs = Database::fetchAll(
    "SELECT * FROM usage_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
    [$user['id']]
);

if (empty($usageLogs)) {
    echo "   <span style='color: #808080;'>(nessun log)</span>\n";
} else {
    echo "   <span style='color: #ce9178;'>Data</span>                  <span style='color: #ce9178;'>Modulo</span>          <span style='color: #ce9178;'>Action</span>              <span style='color: #ce9178;'>Credits</span>\n";
    echo "   " . str_repeat("-", 70) . "\n";

    foreach ($usageLogs as $log) {
        printf("   %-20s %-15s %-20s %.2f\n",
            $log['created_at'],
            $log['module_slug'] ?? '-',
            substr($log['action'], 0, 20),
            $log['credits_used']
        );
    }
}

echo "\n===========================================\n";
echo "   TEST COMPLETATO\n";
echo "===========================================\n";
echo "</pre>";
