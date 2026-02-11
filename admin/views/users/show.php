<div class="space-y-6">
    <!-- Header with Breadcrumb -->
    <div>
        <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
            <a href="<?= url('/admin/users') ?>" class="hover:text-slate-700 dark:hover:text-slate-300">Utenti</a>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-slate-900 dark:text-white"><?= e($targetUser['email']) ?></span>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($targetUser['name'] ?? $targetUser['email']) ?></h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- User Info Form -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Informazioni utente</h3>
                </div>
                <form action="<?= url('/admin/users/' . $targetUser['id']) ?>" method="POST" class="p-6 space-y-6">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                            <input type="email" value="<?= e($targetUser['email']) ?>" disabled
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 py-2 px-3 cursor-not-allowed">
                        </div>
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nome</label>
                            <input type="text" name="name" id="name" value="<?= e($targetUser['name'] ?? '') ?>"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ruolo</label>
                            <select name="role" id="role"
                                    class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="user" <?= $targetUser['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $targetUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div x-data="{ isActive: <?= $targetUser['is_active'] ? 'true' : 'false' ?> }">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Stato account</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" :checked="isActive" class="sr-only peer"
                                       @change.prevent="
                                           if (isActive) {
                                               window.ainstein.confirm('Disattivare questo account? L\'utente non potrà più accedere.', {destructive: true})
                                                   .then(() => { isActive = false; })
                                                   .catch(() => { $el.checked = true; });
                                           } else {
                                               isActive = true;
                                           }
                                       ">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-primary-600"></div>
                                <span class="ml-3 text-sm text-slate-700 dark:text-slate-300">Account attivo</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Salva modifiche
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transaction History -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Storico transazioni</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Importo</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Descrizione</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Nessuna transazione</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        <?php
                                        echo match($t['type']) {
                                            'usage' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                            'bonus' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                            'manual' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-600 dark:text-slate-300'
                                        };
                                        ?>">
                                        <?= $t['type'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold <?= $t['amount'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                                    <?= $t['amount'] >= 0 ? '+' : '' ?><?= number_format($t['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-500 dark:text-slate-400"><?= e($t['description'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Credits Card -->
            <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg shadow-sm p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-primary-100 text-sm font-medium">Crediti disponibili</p>
                        <p class="mt-1 text-4xl font-bold"><?= number_format((float)$targetUser['credits'], 1) ?></p>
                    </div>
                    <div class="h-14 w-14 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Add/Remove Credits -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Modifica crediti</h3>
                </div>
                <form action="<?= url('/admin/users/' . $targetUser['id'] . '/credits') ?>" method="POST" class="p-6 space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Quantita</label>
                        <input type="number" name="amount" id="amount" step="0.1" required placeholder="Es: 50 o -10"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Usa numeri negativi per rimuovere</p>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo</label>
                        <select name="type" id="type"
                                class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="manual">Manuale</option>
                            <option value="bonus">Bonus</option>
                            <option value="purchase">Acquisto</option>
                        </select>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Motivo (obbligatorio)</label>
                        <textarea name="description" id="description" rows="2" required placeholder="Descrivi il motivo della modifica..."
                                  class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2 px-3 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        Applica modifica
                    </button>
                </form>
            </div>

            <!-- Account Info -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Info account</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">ID</dt>
                        <dd class="text-sm font-mono text-slate-900 dark:text-white">#<?= $targetUser['id'] ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Registrato</dt>
                        <dd class="text-sm text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($targetUser['created_at'])) ?></dd>
                    </div>
                    <?php if (!empty($targetUser['last_login_at'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Ultimo login</dt>
                        <dd class="text-sm text-slate-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($targetUser['last_login_at'])) ?></dd>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500 dark:text-slate-400">Ultimo aggiornamento</dt>
                        <dd class="text-sm text-slate-900 dark:text-white"><?= $targetUser['updated_at'] ? date('d/m/Y H:i', strtotime($targetUser['updated_at'])) : '-' ?></dd>
                    </div>
                    <?php if (!empty($usageStats)): ?>
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-700">
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">Operazioni (mese)</dt>
                            <dd class="text-sm font-semibold text-slate-900 dark:text-white"><?= number_format($usageStats['total_operations'] ?? 0) ?></dd>
                        </div>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
