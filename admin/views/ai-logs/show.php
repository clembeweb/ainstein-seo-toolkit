<?php $log = $log ?? []; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/admin/ai-logs') ?>" class="inline-flex items-center text-purple-600 dark:text-purple-400 hover:underline text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alla lista
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">AI Log #<?= e($log['id']) ?></h1>
    </div>

    <!-- Info Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Data/Ora</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></div>
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
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Provider</div>
                <div class="font-medium mt-1 <?= $log['provider'] === 'anthropic' ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' ?>">
                    <?= ucfirst($log['provider']) ?>
                </div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Status</div>
                <div class="mt-1">
                    <?php if ($log['status'] === 'success'): ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                            Success
                        </span>
                    <?php elseif ($log['status'] === 'error'): ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                            Error
                        </span>
                    <?php else: ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300">
                            Fallback da <?= e($log['fallback_from']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Modello</div>
                <div class="font-mono text-sm text-slate-900 dark:text-white mt-1"><?= e($log['model']) ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Tokens Input</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1"><?= number_format($log['tokens_input']) ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Tokens Output</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1"><?= number_format($log['tokens_output']) ?></div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Durata</div>
                <div class="font-medium text-slate-900 dark:text-white mt-1"><?= number_format($log['duration_ms']) ?>ms</div>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">Costo Stimato</div>
                <div class="font-medium text-purple-600 dark:text-purple-400 mt-1">$<?= number_format($log['estimated_cost'], 6) ?></div>
            </div>
        </div>

        <?php if ($log['user_id']): ?>
        <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
            <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-medium">User ID</div>
            <div class="font-medium text-slate-900 dark:text-white mt-1">
                <a href="<?= url('/admin/users/' . $log['user_id']) ?>" class="text-purple-600 dark:text-purple-400 hover:underline">
                    #<?= e($log['user_id']) ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Error Message -->
    <?php if (!empty($log['error_message'])): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="text-xs text-red-500 dark:text-red-400 uppercase font-medium mb-2">Errore</div>
            <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap font-mono"><?= e($log['error_message']) ?></pre>
        </div>
    <?php endif; ?>

    <!-- Request Payload -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
            <h3 class="font-medium text-slate-900 dark:text-white">Request Payload</h3>
        </div>
        <div class="p-4">
            <pre class="text-xs bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto max-h-96"><?= e(json_encode($log['request_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </div>

    <!-- Response Payload -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
            <h3 class="font-medium text-slate-900 dark:text-white">Response Payload</h3>
        </div>
        <div class="p-4">
            <?php if ($log['response_decoded']): ?>
                <pre class="text-xs bg-slate-900 text-green-400 p-4 rounded-lg overflow-x-auto max-h-96"><?= e(json_encode($log['response_decoded'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            <?php else: ?>
                <p class="text-slate-500 dark:text-slate-400 italic">Nessuna risposta (errore prima della risposta API)</p>
            <?php endif; ?>
        </div>
    </div>
</div>
