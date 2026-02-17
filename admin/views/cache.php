<?php
$stats = $stats ?? ['files' => 0, 'size' => 0];
$groups = $groups ?? [];
$logFiles = $logFiles ?? [];

function formatBytes(int $bytes): string {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestione Cache & Log</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Filesystem cache (Symfony Cache) e log applicativi (Monolog)</p>
        </div>
        <form method="POST" action="<?= url('/admin/cache/clear') ?>" onsubmit="return confirm('Svuotare tutta la cache? Le prossime richieste saranno piu lente.')">
            <?= csrf_field() ?>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Svuota tutta la cache
            </button>
        </form>
    </div>

    <!-- Flash message -->
    <?php if (!empty($_SESSION['_flash']['success'])): ?>
        <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200"><?= htmlspecialchars($_SESSION['_flash']['success']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['_flash']['success']); ?>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Cache Files -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">File in cache</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['files'] ?></p>
                </div>
            </div>
        </div>

        <!-- Cache Size -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Dimensione cache</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= formatBytes($stats['size']) ?></p>
                </div>
            </div>
        </div>

        <!-- Log Files -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">File di log</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?= count($logFiles) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cache Keys -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Chiavi Cache</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Stato delle chiavi cache note. TTL = tempo di vita prima del refresh automatico.</p>
        </div>

        <?php foreach ($groups as $groupName => $keys): ?>
        <div class="px-4 py-3 <?= $groupName !== array_key_first($groups) ? 'border-t border-slate-200 dark:border-slate-700' : '' ?>">
            <h3 class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                <?= $groupName === 'core' ? 'Core Framework' : 'Moduli' ?>
            </h3>
            <div class="space-y-2">
                <?php foreach ($keys as $k): ?>
                <div class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-3">
                        <?php if ($k['cached']): ?>
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500" title="In cache"></span>
                        <?php else: ?>
                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-slate-600" title="Non in cache"></span>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($k['label']) ?></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono"><?= htmlspecialchars($k['key']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 dark:text-slate-400">TTL: <?= $k['ttl'] ?></span>
                        <?php if ($k['cached']): ?>
                        <form method="POST" action="<?= url('/admin/cache/clear-key') ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="key" value="<?= htmlspecialchars($k['key']) ?>">
                            <button type="submit" class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium">
                                Invalida
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($groups)): ?>
        <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
            Nessuna chiave cache trovata. La cache si popola automaticamente alla prima richiesta.
        </div>
        <?php endif; ?>
    </div>

    <!-- Log Files -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">File di Log (Monolog)</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Log applicativi in <code class="bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded text-xs">storage/logs/</code>. Rotazione automatica a 14 giorni.</p>
        </div>

        <?php if (!empty($logFiles)): ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">File</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Canale</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dimensione</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ultima modifica</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($logFiles as $log): ?>
                    <?php
                        // Extract channel from filename (e.g., "database-2026-02-17.log" → "database")
                        $channel = preg_replace('/[-_]\d{4}-\d{2}-\d{2}\.log$/', '', $log['name']);
                        $channel = str_replace('.log', '', $channel);
                        $channelColors = [
                            'app' => 'blue', 'database' => 'purple', 'ai' => 'amber',
                            'cron' => 'emerald', 'api' => 'cyan', 'scraping' => 'rose',
                        ];
                        $color = $channelColors[$channel] ?? 'slate';
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white"><?= htmlspecialchars($log['name']) ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $color ?>-100 dark:bg-<?= $color ?>-900/30 text-<?= $color ?>-800 dark:text-<?= $color ?>-300">
                                <?= htmlspecialchars($channel) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= formatBytes($log['size']) ?></td>
                        <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400"><?= date('d/m/Y H:i', $log['modified']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
            Nessun file di log trovato. I log verranno creati automaticamente al primo evento.
        </div>
        <?php endif; ?>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <p class="font-medium mb-1">Come funziona la cache</p>
                <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                    <li><strong>Settings e Moduli</strong>: cachati per 5 minuti, invalidati automaticamente quando modifichi le impostazioni</li>
                    <li><strong>Costi crediti</strong>: cachati per 1 ora (cambiano raramente)</li>
                    <li><strong>Svuota cache</strong>: le prossime 1-2 richieste saranno leggermente piu lente mentre la cache si ricostruisce</li>
                    <li><strong>Log Monolog</strong>: ruotano automaticamente ogni giorno, i file piu vecchi di 14 giorni vengono eliminati</li>
                </ul>
            </div>
        </div>
    </div>
</div>
