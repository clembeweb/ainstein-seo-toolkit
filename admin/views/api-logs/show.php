<?php
$log = $log ?? [];

// Mappa colori provider
$providerColors = [
    'dataforseo'              => 'text-blue-600 dark:text-blue-400',
    'serpapi'                 => 'text-purple-600 dark:text-purple-400',
    'serper'                  => 'text-green-600 dark:text-green-400',
    'google_gsc'              => 'text-red-600 dark:text-red-400',
    'google_oauth'            => 'text-orange-600 dark:text-orange-400',
    'google_ga4'              => 'text-yellow-600 dark:text-yellow-400',
    'rapidapi_keyword'        => 'text-cyan-600 dark:text-cyan-400',
    'rapidapi_keyword_insight' => 'text-teal-600 dark:text-teal-400',
    'keywordseverywhere'      => 'text-indigo-600 dark:text-indigo-400',
    'openai_dalle'            => 'text-emerald-600 dark:text-emerald-400',
];

// Mappa nomi provider
$providerNames = [
    'dataforseo'              => 'DataForSEO',
    'serpapi'                 => 'SerpAPI',
    'serper'                  => 'Serper.dev',
    'google_gsc'              => 'Google GSC',
    'google_oauth'            => 'Google OAuth',
    'google_ga4'              => 'Google GA4',
    'rapidapi_keyword'        => 'RapidAPI Keyword',
    'rapidapi_keyword_insight' => 'RapidAPI Insight',
    'keywordseverywhere'      => 'Keywords Everywhere',
    'openai_dalle'            => 'OpenAI DALL-E',
];
?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/admin/api-logs') ?>" class="inline-flex items-center text-purple-600 dark:text-purple-400 hover:underline text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alla lista
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">API Log #<?= e($log['id']) ?></h1>
    </div>

    <!-- Info Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Data/Ora</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Provider</div>
                <div class="font-medium mt-1 <?= $providerColors[$log['provider']] ?? 'text-slate-600 dark:text-slate-400' ?>">
                    <?= $providerNames[$log['provider']] ?? e($log['provider']) ?>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Modulo</div>
                <div class="mt-1">
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                        <?= e($log['module_slug']) ?>
                    </span>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Status</div>
                <div class="mt-1">
                    <?php if ($log['status'] === 'success'): ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                            Successo
                        </span>
                    <?php elseif ($log['status'] === 'error'): ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                            Errore
                        </span>
                    <?php else: ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                            Limite
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Method</div>
                <div class="font-mono text-sm text-slate-900 dark:text-white mt-1"><?= e($log['method']) ?></div>
            </div>
            <div class="col-span-2">
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Endpoint</div>
                <div class="font-mono text-sm text-slate-900 dark:text-white mt-1 break-all"><?= e($log['endpoint']) ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">HTTP Code</div>
                <?php
                $httpClass = 'text-green-600 dark:text-green-400';
                if ($log['response_code'] >= 400) {
                    $httpClass = 'text-red-600 dark:text-red-400';
                } elseif ($log['response_code'] >= 300) {
                    $httpClass = 'text-yellow-600 dark:text-yellow-400';
                }
                ?>
                <div class="font-mono text-lg font-bold <?= $httpClass ?> mt-1"><?= $log['response_code'] ?: '-' ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Durata</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1"><?= number_format($log['duration_ms']) ?>ms</div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Costo API</div>
                <div class="font-medium text-purple-600 dark:text-purple-400 mt-1">
                    <?php if ($log['cost'] > 0): ?>
                        $<?= number_format($log['cost'], 6) ?>
                    <?php else: ?>
                        <span class="text-slate-400">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Crediti Usati</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1">
                    <?php if ($log['credits_used'] > 0): ?>
                        <?= number_format($log['credits_used'], 2) ?>
                    <?php else: ?>
                        <span class="text-slate-400">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($log['user_id']): ?>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">User ID</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1">
                    <a href="<?= url('/admin/users/' . $log['user_id']) ?>" class="text-purple-600 dark:text-purple-400 hover:underline">
                        #<?= e($log['user_id']) ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($log['ip_address']): ?>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">IP Address</div>
                <div class="font-mono text-sm text-slate-900 dark:text-white mt-1"><?= e($log['ip_address']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($log['context'])): ?>
        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
            <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Contesto</div>
            <div class="font-mono text-sm text-slate-900 dark:text-white mt-1 bg-slate-50 dark:bg-slate-700/50 rounded p-2"><?= e($log['context']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Error Message -->
    <?php if (!empty($log['error_message'])): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="text-xs text-red-500 dark:text-red-400 uppercase font-medium mb-2">Messaggio Errore</div>
            <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap font-mono"><?= e($log['error_message']) ?></pre>
        </div>
    <?php endif; ?>

    <!-- Request Payload -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center">
            <h3 class="font-medium text-slate-900 dark:text-white">Request Payload</h3>
            <button onclick="copyToClipboard('request-payload')" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">
                Copia
            </button>
        </div>
        <div class="p-4">
            <pre id="request-payload" class="text-xs bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto max-h-96"><?= e(json_encode($log['request_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </div>

    <!-- Response Payload -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center">
            <h3 class="font-medium text-slate-900 dark:text-white">Response Payload</h3>
            <?php if ($log['response_decoded']): ?>
            <button onclick="copyToClipboard('response-payload')" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">
                Copia
            </button>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <?php if ($log['response_decoded']): ?>
                <pre id="response-payload" class="text-xs bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto max-h-96"><?= e(json_encode($log['response_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php else: ?>
                <p class="text-slate-500 dark:text-slate-400 italic">Nessuna risposta (errore prima della risposta API)</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        navigator.clipboard.writeText(element.textContent).then(() => {
            window.ainstein.toast('Copiato negli appunti!', 'success');
        });
    }
}
</script>
