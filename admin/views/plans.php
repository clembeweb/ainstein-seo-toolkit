<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Gestione Piani</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura i piani di abbonamento e i crediti mensili</p>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($plans as $plan): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border <?= $plan['slug'] === 'pro' ? 'border-primary-500 ring-2 ring-primary-500' : 'border-slate-200 dark:border-slate-700' ?> overflow-hidden flex flex-col">
            <?php if ($plan['slug'] === 'pro'): ?>
            <div class="bg-primary-500 text-white text-center text-xs font-semibold py-1">Piu popolare</div>
            <?php endif; ?>

            <div class="p-6 flex-1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?= e($plan['name']) ?></h3>
                    <?php if ($plan['is_active']): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">Attivo</span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Inattivo</span>
                    <?php endif; ?>
                </div>

                <div class="mb-6">
                    <span class="text-4xl font-bold text-slate-900 dark:text-white">&euro;<?= number_format($plan['price_monthly'], 0) ?></span>
                    <span class="text-slate-500 dark:text-slate-400">/mese</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        &euro;<?= number_format($plan['price_yearly'], 0) ?>/anno
                    </p>
                </div>

                <div class="flex items-center gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg mb-6">
                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                    </svg>
                    <span class="text-sm font-semibold text-amber-700 dark:text-amber-400"><?= number_format($plan['credits_monthly']) ?> crediti/mese</span>
                </div>

                <form action="<?= url('/admin/plans/' . $plan['id']) ?>" method="POST" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nome</label>
                        <input type="text" name="name" value="<?= e($plan['name']) ?>"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Crediti/mese</label>
                        <input type="number" name="credits_monthly" value="<?= $plan['credits_monthly'] ?>"
                               class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Prezzo/mese</label>
                            <input type="number" step="0.01" name="price_monthly" value="<?= $plan['price_monthly'] ?>"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Prezzo/anno</label>
                            <input type="number" step="0.01" name="price_yearly" value="<?= $plan['price_yearly'] ?>"
                                   class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-1.5 px-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" <?= $plan['is_active'] ? 'checked' : '' ?>
                               class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Piano attivo</span>
                    </label>

                    <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        Salva
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-medium">Nota sui piani</p>
                <p class="mt-1">I piani sono predisposti per l'integrazione con Stripe. Una volta configurate le API keys in Impostazioni, gli utenti potranno sottoscrivere abbonamenti direttamente dalla piattaforma.</p>
            </div>
        </div>
    </div>
</div>
