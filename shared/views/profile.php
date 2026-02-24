<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Il tuo profilo</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gestisci le informazioni del tuo account</p>
    </div>

    <!-- Informazioni profilo -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Informazioni personali</h3>

            <form action="<?= \Core\Router::url('/profile') ?>" method="POST" class="mt-6 space-y-6">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div class="sm:col-span-3">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 shadow-sm dark:text-gray-300 sm:text-sm cursor-not-allowed">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">L'email non puo essere modificata</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        Salva modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cambio password -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Cambia password</h3>

            <form action="<?= \Core\Router::url('/profile/password') ?>" method="POST" class="mt-6 space-y-6">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password attuale</label>
                        <input type="password" name="current_password" id="current_password" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div class="sm:col-span-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nuova password</label>
                        <input type="password" name="new_password" id="new_password" required minlength="8"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>

                    <div class="sm:col-span-4">
                        <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conferma nuova password</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" required
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        Aggiorna password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Riepilogo crediti -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Crediti</h3>
            <div class="mt-4">
                <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Saldo attuale</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white"><?= number_format((float)$user['credits'], 1) ?></dd>
                    </div>
                    <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Piano</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($plan['name'] ?? 'Free') ?></dd>
                    </div>
                    <div class="px-4 py-5 bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden sm:p-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Ruolo</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white capitalize"><?= htmlspecialchars($user['role']) ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Preferenze Notifiche Email -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Preferenze Notifiche Email</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Scegli per quali notifiche ricevere anche un'email. Le notifiche in-app sono sempre attive.</p>

            <form action="<?= \Core\Router::url('/profile/notification-preferences') ?>" method="POST" class="mt-6">
                <?= csrf_field() ?>

                <div class="space-y-4">
                    <?php foreach ($notificationPrefs as $type => $pref): ?>
                    <label class="flex items-center justify-between py-2 cursor-pointer">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($pref['label']) ?></span>
                        <div class="relative">
                            <input type="checkbox" name="notif_<?= $type ?>" id="notif_<?= $type ?>" value="1"
                                   <?= $pref['email_enabled'] ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-primary-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        Salva preferenze
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
