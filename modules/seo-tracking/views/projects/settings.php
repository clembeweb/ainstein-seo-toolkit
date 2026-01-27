<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/seo-tracking/project/' . $project['id']) ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-2">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Torna alla dashboard
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= e($project['name']) ?></h1>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: GSC Connection -->
        <div class="lg:col-span-1 space-y-6">
            <!-- GSC Connection -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-slate-900 dark:text-white">Search Console</h3>
                            <?php if ($project['gsc_connected']): ?>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">Connesso</span>
                            <?php else: ?>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Non connesso</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="p-5">
                    <?php if ($project['gsc_connected']): ?>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Property:</span>
                            <span class="font-medium text-slate-900 dark:text-white truncate ml-2"><?= e($gscConnection['property_url'] ?? '-') ?></span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Ultimo sync:</span>
                            <span class="font-medium text-slate-900 dark:text-white"><?= isset($gscConnection['last_sync_at']) && $gscConnection['last_sync_at'] ? date('d/m/Y H:i', strtotime($gscConnection['last_sync_at'])) : 'Mai' ?></span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-700/50 rounded-lg p-2">
                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Sync automatico ogni giorno alle 04:00
                        </div>
                        <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/disconnect') ?>" method="POST">
                            <?= csrf_field() ?>
                            <button type="submit" class="w-full px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm">
                                Disconnetti GSC
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/gsc/connect') ?>" class="block w-full px-4 py-2 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700 transition-colors text-sm text-center">
                        Connetti Search Console
                    </a>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Richiede autorizzazione OAuth2 per accedere ai dati GSC.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings Form -->
        <div class="lg:col-span-2">
            <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/settings') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
                <?= csrf_field() ?>
                <!-- General Settings -->
                <div class="p-6 space-y-6">
                    <div>
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Impostazioni generali</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Nome progetto
                                </label>
                                <input type="text" name="name" id="name" value="<?= e($project['name']) ?>" required
                                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>

                            <!-- Dominio -->
                            <div>
                                <label for="domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Dominio <span class="text-red-500">*</span>
                                </label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-500 dark:text-slate-400 text-sm">
                                        https://
                                    </span>
                                    <input type="text" name="domain" id="domain" value="<?= e($project['domain']) ?>" required
                                           class="flex-1 px-3 py-2 rounded-r-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                           placeholder="www.example.com">
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dominio del sito da monitorare (con o senza www)</p>
                            </div>

                            <div>
                                <label for="notification_emails" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Email notifiche
                                </label>
                                <?php
                                $emails = $project['notification_emails'] ? json_decode($project['notification_emails'], true) : [];
                                $emailsStr = implode(', ', $emails);
                                ?>
                                <input type="text" name="notification_emails" id="notification_emails" value="<?= e($emailsStr) ?>"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="email1@example.com, email2@example.com">
                            </div>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="sync_enabled" value="1" <?= $project['sync_enabled'] ? 'checked' : '' ?>
                                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Sincronizzazione automatica abilitata</span>
                            </label>

                            <div>
                                <label for="data_retention_months" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Retention dati (mesi)
                                </label>
                                <select name="data_retention_months" id="data_retention_months"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="0.03" <?= ($project['data_retention_months'] ?? 16) == 0.03 ? 'selected' : '' ?>>1 giorno (test)</option>
                                    <option value="1" <?= ($project['data_retention_months'] ?? 16) == 1 ? 'selected' : '' ?>>1 mese</option>
                                    <option value="3" <?= ($project['data_retention_months'] ?? 16) == 3 ? 'selected' : '' ?>>3 mesi</option>
                                    <option value="6" <?= ($project['data_retention_months'] ?? 16) == 6 ? 'selected' : '' ?>>6 mesi</option>
                                    <option value="12" <?= ($project['data_retention_months'] ?? 16) == 12 ? 'selected' : '' ?>>12 mesi</option>
                                    <option value="16" <?= ($project['data_retention_months'] ?? 16) == 16 ? 'selected' : '' ?>>16 mesi (consigliato)</option>
                                    <option value="24" <?= ($project['data_retention_months'] ?? 16) == 24 ? 'selected' : '' ?>>24 mesi</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- AI Reports -->
                    <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Report AI</h2>

                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="ai_reports_enabled" value="1" <?= $project['ai_reports_enabled'] ? 'checked' : '' ?>
                                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">Abilita generazione report AI</span>
                            </label>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="weekly_report_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Report settimanale
                                    </label>
                                    <select name="weekly_report_day" id="weekly_report_day"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <?php
                                        $days = ['Domenica', 'Lunedi', 'Martedi', 'Mercoledi', 'Giovedi', 'Venerdi', 'Sabato'];
                                        foreach ($days as $i => $day):
                                        ?>
                                        <option value="<?= $i ?>" <?= ($project['weekly_report_day'] ?? 1) == $i ? 'selected' : '' ?>><?= $day ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="weekly_report_time" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                        Ora invio
                                    </label>
                                    <input type="time" name="weekly_report_time" id="weekly_report_time" value="<?= substr($project['weekly_report_time'] ?? '08:00:00', 0, 5) ?>"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>

                            <div>
                                <label for="monthly_report_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Giorno report mensile
                                </label>
                                <select name="monthly_report_day" id="monthly_report_day"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($project['monthly_report_day'] ?? 1) == $i ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Settings -->
                    <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Impostazioni Alert</h2>

                        <div class="space-y-6">
                            <!-- Position Alerts -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="position_alert_enabled" value="1" <?= ($project['alert_settings']['position_alert_enabled'] ?? 0) ? 'checked' : '' ?>
                                           class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Alert variazione posizioni</span>
                                </label>
                                <div class="grid grid-cols-2 gap-4 ml-7">
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Soglia posizioni</label>
                                        <input type="number" name="position_threshold" value="<?= $project['alert_settings']['position_threshold'] ?? 5 ?>" min="1" max="50"
                                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Applica a</label>
                                        <select name="position_alert_keywords"
                                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                            <option value="tracked" <?= ($project['alert_settings']['position_alert_keywords'] ?? 'tracked') == 'tracked' ? 'selected' : '' ?>>Solo keyword tracciate</option>
                                            <option value="all" <?= ($project['alert_settings']['position_alert_keywords'] ?? 'tracked') == 'all' ? 'selected' : '' ?>>Tutte le keyword</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Traffic Alerts (GSC-based) -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="traffic_alert_enabled" value="1" <?= ($project['alert_settings']['traffic_alert_enabled'] ?? 0) ? 'checked' : '' ?>
                                           class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Alert calo traffico (GSC)</span>
                                </label>
                                <div class="ml-7">
                                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Soglia calo (%)</label>
                                    <input type="number" name="traffic_drop_threshold" value="<?= $project['alert_settings']['traffic_drop_threshold'] ?? 20 ?>" min="5" max="90"
                                           class="w-32 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>

                            <!-- Anomaly Alerts -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="anomaly_alert_enabled" value="1" <?= ($project['alert_settings']['anomaly_alert_enabled'] ?? 0) ? 'checked' : '' ?>
                                           class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <div>
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">Rilevamento anomalie AI</span>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Usa AI per rilevare pattern anomali nel traffico</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Email Settings -->
                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="email_enabled" value="1" <?= ($project['alert_settings']['email_enabled'] ?? 0) ? 'checked' : '' ?>
                                           class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">Invia alert via email</span>
                                </label>
                                <div class="ml-7">
                                    <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Frequenza</label>
                                    <select name="email_frequency"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="instant" <?= ($project['alert_settings']['email_frequency'] ?? 'daily_digest') == 'instant' ? 'selected' : '' ?>>Immediata</option>
                                        <option value="daily_digest" <?= ($project['alert_settings']['email_frequency'] ?? 'daily_digest') == 'daily_digest' ? 'selected' : '' ?>>Digest giornaliero</option>
                                        <option value="weekly_digest" <?= ($project['alert_settings']['email_frequency'] ?? 'daily_digest') == 'weekly_digest' ? 'selected' : '' ?>>Digest settimanale</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg flex items-center justify-between">
                    <button type="submit" class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                        Salva Impostazioni
                    </button>
                </div>
            </form>

            <!-- Danger Zone -->
            <div class="mt-6 bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-red-200 dark:border-red-900/50 overflow-hidden">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-red-600 dark:text-red-400 mb-2">Zona pericolosa</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Eliminando il progetto verranno cancellati tutti i dati storici, keyword, report e connessioni. Questa azione non e reversibile.
                    </p>
                    <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('Sei sicuro di voler eliminare questo progetto? Tutti i dati verranno persi.');">
                        <?= csrf_field() ?>

                        <button type="submit" class="px-4 py-2 rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 font-medium hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm">
                            Elimina Progetto
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
