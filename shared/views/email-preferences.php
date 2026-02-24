<div class="sm:mx-auto sm:w-full sm:max-w-lg">
    <!-- Logo / Brand -->
    <div class="flex flex-col items-center">
        <svg class="h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span class="mt-2 text-lg font-bold text-slate-900 dark:text-white">Ainstein</span>
    </div>

    <h1 class="mt-6 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
        Preferenze Email
    </h1>
    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
        Scegli per quali notifiche ricevere email da Ainstein.
    </p>
</div>

<div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
    <div class="bg-white dark:bg-slate-800 py-8 px-6 shadow-lg rounded-xl sm:px-10">

        <?php if (!empty($error)): ?>
        <!-- Token invalido -->
        <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        </div>
        <div class="text-center">
            <a href="<?= \Core\Router::url('/login') ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
                Vai al login
            </a>
        </div>
        <?php else: ?>

        <?php if (!empty($success)): ?>
        <!-- Messaggio successo -->
        <div class="mb-6 p-4 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form preferenze -->
        <form action="<?= \Core\Router::url('/email/preferences') ?>" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <?= csrf_field() ?>

            <div class="space-y-1">
                <?php foreach ($preferences as $type => $pref): ?>
                <label for="email_<?= $type ?>" class="flex items-center justify-between py-3 px-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors">
                    <div class="flex-1 pr-4">
                        <span class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($pref['label']) ?></span>
                    </div>
                    <div class="relative flex-shrink-0">
                        <input type="checkbox"
                               name="email_<?= $type ?>"
                               id="email_<?= $type ?>"
                               value="1"
                               <?= $pref['email_enabled'] ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="mt-8">
                <button type="submit"
                        class="flex w-full justify-center rounded-md border border-transparent bg-primary-600 py-2.5 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                    Salva preferenze
                </button>
            </div>
        </form>

        <?php endif; ?>
    </div>

    <!-- Footer -->
    <p class="mt-8 text-center text-xs text-gray-500 dark:text-gray-500">
        &copy; <?= date('Y') ?> Ainstein. Tutti i diritti riservati.
    </p>
</div>
