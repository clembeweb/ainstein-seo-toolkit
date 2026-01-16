<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Admin Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Panoramica del sistema e statistiche</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Utenti totali -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Utenti totali</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['total_users']) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    +<?= $stats['users_today'] ?>
                </span>
                <span class="text-slate-500 dark:text-slate-400">oggi</span>
            </div>
        </div>

        <!-- Utenti attivi -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Utenti attivi</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['active_users']) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full" style="width: <?= $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0 ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Crediti usati oggi -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Crediti oggi</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= number_format($stats['credits_today'], 1) ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                Mese: <span class="font-semibold text-slate-700 dark:text-slate-300"><?= number_format($stats['credits_month'], 1) ?></span>
            </p>
        </div>

        <!-- Moduli attivi -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Moduli attivi</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white"><?= $stats['active_modules'] ?>/<?= $stats['total_modules'] ?></p>
                </div>
                <div class="h-12 w-12 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
            <a href="<?= url('/admin/modules') ?>" class="mt-4 inline-flex items-center text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700">
                Gestisci moduli
                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Usage Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Utilizzo crediti (ultimi 7 giorni)</h3>
            <div class="h-64">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <!-- Users Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Nuovi utenti (ultimi 7 giorni)</h3>
            <div class="h-64">
                <canvas id="usersChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Users -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Ultimi utenti registrati</h3>
                <a href="<?= url('/admin/users') ?>" class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700">Vedi tutti</a>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php if (empty($recentUsers)): ?>
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nessun utente registrato</p>
                </div>
                <?php else: ?>
                <?php foreach ($recentUsers as $u): ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-medium">
                            <?= strtoupper(substr($u['name'] ?? $u['email'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($u['name'] ?? '-') ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($u['email']) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' ?>">
                            <?= $u['role'] ?>
                        </span>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?= date('d/m/Y', strtotime($u['created_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Users by Credits -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Top utenti (questo mese)</h3>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php if (empty($topUsers)): ?>
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Nessun utilizzo questo mese</p>
                </div>
                <?php else: ?>
                <?php foreach ($topUsers as $index => $u): ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center h-8 w-8 rounded-full text-sm font-bold <?= $index === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                            <?= $index + 1 ?>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white"><?= e($u['name'] ?? $u['email']) ?></p>
                            <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($u['email']) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= number_format($u['total_used'], 1) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">crediti</p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="<?= url('/admin/users') ?>" class="flex items-center gap-4 p-4 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-700 transition-colors group">
            <div class="h-12 w-12 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-100 dark:group-hover:bg-blue-900/50 transition-colors">
                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">Gestione Utenti</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Visualizza, modifica, gestisci crediti</p>
            </div>
        </a>

        <a href="<?= url('/admin/modules') ?>" class="flex items-center gap-4 p-4 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-700 transition-colors group">
            <div class="h-12 w-12 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center group-hover:bg-purple-100 dark:group-hover:bg-purple-900/50 transition-colors">
                <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">Gestione Moduli</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Attiva/disattiva moduli</p>
            </div>
        </a>

        <a href="<?= url('/admin/settings') ?>" class="flex items-center gap-4 p-4 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-700 transition-colors group">
            <div class="h-12 w-12 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-slate-200 dark:group-hover:bg-slate-600 transition-colors">
                <svg class="h-6 w-6 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-slate-900 dark:text-white">Impostazioni</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">API keys, costi, configurazione</p>
            </div>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    // Usage Chart
    new Chart(document.getElementById('usageChart'), {
        type: 'line',
        data: {
            labels: ['6 giorni fa', '5 giorni fa', '4 giorni fa', '3 giorni fa', '2 giorni fa', 'Ieri', 'Oggi'],
            datasets: [{
                label: 'Crediti utilizzati',
                data: [12, 19, 8, 15, 22, 18, <?= $stats['credits_today'] ?>],
                borderColor: '#006e96',
                backgroundColor: 'rgba(0, 110, 150, 0.1)',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                x: { grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });

    // Users Chart
    new Chart(document.getElementById('usersChart'), {
        type: 'bar',
        data: {
            labels: ['6 giorni fa', '5 giorni fa', '4 giorni fa', '3 giorni fa', '2 giorni fa', 'Ieri', 'Oggi'],
            datasets: [{
                label: 'Nuovi utenti',
                data: [2, 1, 3, 0, 2, 1, <?= $stats['users_today'] ?>],
                backgroundColor: '#10b981',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor, stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });
});
</script>
