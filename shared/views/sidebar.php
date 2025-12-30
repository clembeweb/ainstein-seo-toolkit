<!-- Mobile sidebar -->
<div x-show="sidebarOpen" x-cloak
     class="fixed inset-y-0 left-0 z-50 w-64 lg:hidden"
     x-transition:enter="transform transition ease-in-out duration-300"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transform transition ease-in-out duration-300"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full">
    <div class="flex h-full flex-col bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700">
        <!-- Mobile header -->
        <div class="flex h-16 items-center justify-between px-4 border-b border-slate-200 dark:border-slate-700">
            <a href="<?= url('/dashboard') ?>" class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-slate-900 dark:text-white">SEO Toolkit</span>
            </a>
            <button @click="sidebarOpen = false" class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Mobile nav content (same as desktop) -->
        <nav class="flex-1 overflow-y-auto p-4">
            <?php include __DIR__ . '/components/nav-items.php'; ?>
        </nav>
    </div>
</div>

<!-- Desktop sidebar -->
<div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
    <div class="flex grow flex-col bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700">
        <!-- Logo -->
        <div class="flex h-16 shrink-0 items-center px-6 border-b border-slate-200 dark:border-slate-700">
            <a href="<?= url('/dashboard') ?>" class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-sm">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-slate-900 dark:text-white">SEO Toolkit</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex flex-1 flex-col overflow-y-auto p-4">
            <?php include __DIR__ . '/components/nav-items.php'; ?>
        </nav>

        <!-- User info footer -->
        <div class="shrink-0 border-t border-slate-200 dark:border-slate-700 p-4">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-sm font-semibold">
                    <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?= e($user['name'] ?? 'Utente') ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($user['email']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
