<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <div class="flex justify-center">
        <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Ainstein" class="h-10 dark:brightness-0 dark:invert">
    </div>
    <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
        Reimposta Password
    </h2>
    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
        Scegli una nuova password per il tuo account
    </p>
</div>

<div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
    <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <?php if (!empty($error)): ?>
        <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400">
            <?= $error ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($token)): ?>
        <form class="space-y-6" action="<?= \Core\Router::url('/reset-password') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <?php if (!empty($email)): ?>
            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50 text-sm text-slate-600 dark:text-slate-400">
                Account: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($email) ?></strong>
            </div>
            <?php endif; ?>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nuova password
                </label>
                <div class="mt-1">
                    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8"
                           class="block w-full appearance-none rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                           placeholder="Minimo 8 caratteri">
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Conferma password
                </label>
                <div class="mt-1">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8"
                           class="block w-full appearance-none rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                           placeholder="Ripeti la password">
                </div>
            </div>

            <div>
                <button type="submit"
                        class="flex w-full justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Reimposta Password
                </button>
            </div>
        </form>
        <?php else: ?>
        <!-- Token scaduto/invalido - mostra link per richiederne uno nuovo -->
        <div class="text-center">
            <a href="<?= \Core\Router::url('/forgot-password') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Richiedi nuovo link
            </a>
        </div>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="<?= \Core\Router::url('/login') ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                &larr; Torna al login
            </a>
        </div>
    </div>
</div>
