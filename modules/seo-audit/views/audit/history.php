<?php
/**
 * Storico Scansioni - SEO Audit
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Storico Scansioni</h1>
            <p class="text-slate-600 dark:text-slate-400"><?= e($project['name']) ?></p>
        </div>
        <a href="<?= url('/seo-audit/project/' . $project['id'] . '/dashboard') ?>"
           class="text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Torna alla Dashboard
        </a>
    </div>

    <!-- Grafico Trend -->
    <?php if (count($sessions) > 1): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Trend Health Score</h3>
        <div class="flex items-end gap-2 h-40">
            <?php
            $maxScore = 100;
            $reversed = array_reverse(array_slice($sessions, 0, 10));
            foreach ($reversed as $s):
                $score = $s['health_score'] ?? 0;
                $height = max(5, ($score / $maxScore) * 100);
                if ($score >= 70) {
                    $color = 'bg-emerald-500';
                } elseif ($score >= 40) {
                    $color = 'bg-yellow-500';
                } else {
                    $color = 'bg-red-500';
                }
            ?>
            <div class="flex flex-col items-center flex-1 group">
                <div class="w-full <?= $color ?> rounded-t transition-all group-hover:opacity-80"
                     style="height: <?= $height ?>%"
                     title="<?= date('d/m/Y H:i', strtotime($s['created_at'])) ?>"></div>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 mt-2"><?= $score ?></span>
                <span class="text-xs text-slate-500 dark:text-slate-400"><?= date('d/m', strtotime($s['created_at'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabella Sessioni -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-slate-700 dark:text-slate-300">Data</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-slate-700 dark:text-slate-300">Stato</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300">Pagine</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300">Health</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300">Critical</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300">Warning</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-slate-700 dark:text-slate-300">Notice</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($sessions as $i => $session): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                <?= date('d/m/Y H:i', strtotime($session['created_at'])) ?>
                            </div>
                            <?php if ($session['completed_at']): ?>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <?php
                                $duration = strtotime($session['completed_at']) - strtotime($session['created_at']);
                                if ($duration < 60) {
                                    echo $duration . ' sec';
                                } else {
                                    echo round($duration / 60) . ' min';
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            $statusConfig = [
                                'completed' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-800 dark:text-emerald-300'],
                                'running' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-800 dark:text-blue-300'],
                                'failed' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-300'],
                                'stopped' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-800 dark:text-slate-300'],
                                'stopping' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300'],
                            ];
                            $cfg = $statusConfig[$session['status']] ?? $statusConfig['stopped'];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?= $cfg['bg'] ?> <?= $cfg['text'] ?>">
                                <?= ucfirst($session['status']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm text-slate-700 dark:text-slate-300">
                                <?= $session['pages_crawled'] ?>/<?= $session['pages_found'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($session['health_score'] > 0): ?>
                            <?php
                            $score = $session['health_score'];
                            if ($score >= 70) {
                                $scoreBg = 'bg-emerald-100 dark:bg-emerald-900/30';
                                $scoreText = 'text-emerald-700 dark:text-emerald-300';
                            } elseif ($score >= 40) {
                                $scoreBg = 'bg-yellow-100 dark:bg-yellow-900/30';
                                $scoreText = 'text-yellow-700 dark:text-yellow-300';
                            } else {
                                $scoreBg = 'bg-red-100 dark:bg-red-900/30';
                                $scoreText = 'text-red-700 dark:text-red-300';
                            }
                            ?>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold <?= $scoreBg ?> <?= $scoreText ?>">
                                <?= $score ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($session['critical_count'] > 0): ?>
                            <span class="text-red-600 dark:text-red-400 font-medium"><?= $session['critical_count'] ?></span>
                            <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-500">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($session['warning_count'] > 0): ?>
                            <span class="text-yellow-600 dark:text-yellow-400 font-medium"><?= $session['warning_count'] ?></span>
                            <?php else: ?>
                            <span class="text-slate-400 dark:text-slate-500">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-slate-600 dark:text-slate-400"><?= $session['notice_count'] ?? 0 ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($sessions)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500 dark:text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Nessuna scansione effettuata
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legenda -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Legenda Health Score</h4>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span class="text-slate-600 dark:text-slate-400">70-100: Ottimo</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span class="text-slate-600 dark:text-slate-400">40-69: Da migliorare</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span class="text-slate-600 dark:text-slate-400">0-39: Critico</span>
            </div>
        </div>
    </div>
</div>
