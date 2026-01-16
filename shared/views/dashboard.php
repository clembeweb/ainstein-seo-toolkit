<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Benvenuto, <?= htmlspecialchars($user['name'] ?? 'Utente') ?>!</p>
        </div>
        <?php if (isset($_GET['upgrade'])): ?>
        <a href="#plans" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
            Aumenta crediti
        </a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Crediti disponibili -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Crediti disponibili</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= number_format((float)($user['credits'] ?? 0), 1) ?>
            </dd>
        </div>

        <!-- Crediti usati oggi -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Usati oggi</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= number_format($usageToday ?? 0, 1) ?>
            </dd>
        </div>

        <!-- Crediti usati questo mese -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Usati questo mese</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= number_format($usageMonth ?? 0, 1) ?>
            </dd>
        </div>

        <!-- Progetti -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">I tuoi progetti</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                <?= $projectsCount ?? 0 ?>
            </dd>
        </div>
    </div>

    <!-- Moduli disponibili -->
    <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Moduli disponibili</h2>
        <?php if (empty($modules)): ?>
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nessun modulo attivo</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">I moduli SEO saranno disponibili presto.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($modules as $module): ?>
            <a href="<?= \Core\Router::url('/' . $module['slug']) ?>" class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary-400 hover:ring-1 hover:ring-primary-400">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($module['name']) ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate"><?= htmlspecialchars($module['description'] ?? '') ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Utilizzo recente -->
    <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Utilizzo recente</h2>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <?php if (empty($recentUsage)): ?>
            <div class="text-center py-8">
                <p class="text-sm text-gray-500 dark:text-gray-400">Nessuna attivita recente</p>
            </div>
            <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Azione</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modulo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Crediti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recentUsage as $usage): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($usage['action']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($usage['module_slug'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= number_format($usage['credits_used'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= date('d/m/Y H:i', strtotime($usage['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
