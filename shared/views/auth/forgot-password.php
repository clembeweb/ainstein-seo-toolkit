<div class="sm:mx-auto sm:w-full sm:max-w-md">
    <div class="flex justify-center">
        <svg class="h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
    </div>
    <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
        Password dimenticata
    </h2>
    <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
        Inserisci la tua email per ricevere un link di reset
    </p>
</div>

<div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
    <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <?php if (isset($success)): ?>
        <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form class="space-y-6" action="<?= \Core\Router::url('/forgot-password') ?>" method="POST">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email
                </label>
                <div class="mt-1">
                    <input id="email" name="email" type="email" autocomplete="email" required
                           class="block w-full appearance-none rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                </div>
            </div>

            <div>
                <button type="submit"
                        class="flex w-full justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Invia link di reset
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="<?= \Core\Router::url('/login') ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                &larr; Torna al login
            </a>
        </div>
    </div>
</div>
