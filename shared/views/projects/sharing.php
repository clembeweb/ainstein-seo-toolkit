<?php
/**
 * Sharing Settings View
 * Gestione condivisione progetto — solo owner
 *
 * Variables:
 *   $project      — project array (id, name, color, access_role)
 *   $members      — array of project members
 *   $invitations  — array of pending email invitations
 *   $activeModules — array of active modules in the project
 */

// Module color map for badges
$moduleColorMap = [
    'amber'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'blue'    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'purple'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    'rose'    => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    'cyan'    => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
    'orange'  => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
];
?>

<div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <a href="<?= url('/projects') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Progetti</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="<?= url('/projects/' . $project['id']) ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"><?= htmlspecialchars($project['name']) ?></a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 dark:text-white font-medium">Condivisione</span>
    </nav>

    <div class="max-w-4xl" x-data="sharingManager()">
        <!-- Main Card -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-200 dark:border-slate-700 px-5">
                <a href="<?= url('/projects/' . $project['id'] . '/settings') ?>"
                   class="px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 border-b-2 border-transparent">
                    Generale
                </a>
                <a href="<?= url('/projects/' . $project['id'] . '/sharing') ?>"
                   class="px-4 py-3 text-sm font-medium text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400">
                    Condivisione
                </a>
            </div>

            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Condivisione progetto</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gestisci i membri e gli inviti per <strong><?= htmlspecialchars($project['name']) ?></strong>.</p>
            </div>

            <!-- Invite Form -->
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-4">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Invita un membro
                    </span>
                </h2>

                <form method="POST" action="<?= url('/projects/' . $project['id'] . '/sharing/invite') ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Email -->
                        <div class="sm:col-span-2">
                            <label for="invite_email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" id="invite_email" required
                                   placeholder="nome@esempio.it"
                                   class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="invite_role" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Ruolo
                            </label>
                            <select name="role" id="invite_role"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                <option value="editor">Editor</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                    </div>

                    <!-- Module Selection -->
                    <?php if (!empty($activeModules)): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Moduli accessibili
                        </label>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($activeModules as $mod): ?>
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors">
                                <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($mod['slug']) ?>" checked
                                       class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <span class="w-2 h-2 rounded-full" style="background-color: <?= htmlspecialchars($mod['color'] ?? '#3B82F6') ?>"></span>
                                <span class="text-sm text-slate-700 dark:text-slate-300"><?= htmlspecialchars($mod['label']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Seleziona i moduli a cui il membro avra accesso.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Submit -->
                    <div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Invia Invito
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Members Table -->
        <div class="mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Membri del progetto
                    </span>
                </h2>
            </div>

            <?php if (empty($members)): ?>
            <!-- Empty State -->
            <div class="p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-400 dark:text-slate-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-slate-500 dark:text-slate-400 text-sm">Nessun membro. Invita qualcuno per collaborare.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Utente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ruolo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Moduli</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stato</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($members as $member): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <!-- User -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($member['user_avatar'])): ?>
                                    <img src="<?= htmlspecialchars($member['user_avatar']) ?>" alt="" class="w-8 h-8 rounded-full object-cover">
                                    <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                            <?= strtoupper(mb_substr($member['user_name'] ?? $member['user_email'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($member['user_name'] ?? 'Utente') ?></div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400"><?= htmlspecialchars($member['user_email']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="px-4 py-3">
                                <?php if ($member['role'] === 'editor'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    Editor
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-600 dark:text-slate-300">
                                    Viewer
                                </span>
                                <?php endif; ?>
                            </td>

                            <!-- Modules -->
                            <td class="px-4 py-3">
                                <?php
                                $memberModules = $member['module_slugs'] ?? [];
                                if (!empty($memberModules)):
                                    foreach ($memberModules as $slug):
                                        // Find module info
                                        $modInfo = null;
                                        foreach ($activeModules as $am) {
                                            if ($am['slug'] === $slug) { $modInfo = $am; break; }
                                        }
                                ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 mr-1 mb-1">
                                    <?php if ($modInfo): ?>
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($modInfo['color'] ?? '#3B82F6') ?>"></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($modInfo['label'] ?? $slug) ?>
                                </span>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                <span class="text-xs text-slate-400">Tutti</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3">
                                <?php if (!empty($member['accepted_at'])): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Attivo
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    In attesa
                                </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            @click="openEditModal(<?= $member['user_id'] ?>, '<?= htmlspecialchars($member['role']) ?>', <?= htmlspecialchars(json_encode($member['module_slugs'] ?? [])) ?>)"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Modifica
                                    </button>

                                    <form method="POST" action="<?= url('/projects/' . $project['id'] . '/sharing/remove/' . $member['user_id']) ?>"
                                          x-data @submit.prevent="if(confirm('Sei sicuro di voler rimuovere questo membro dal progetto?')) $el.submit()">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Rimuovi
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pending Invitations -->
        <?php if (!empty($invitations)): ?>
        <div class="mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Inviti in sospeso
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            <?= count($invitations) ?>
                        </span>
                    </span>
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-700/50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ruolo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Invitato da</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Scadenza</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($invitations as $invite): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-slate-900 dark:text-white"><?= htmlspecialchars($invite['email']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($invite['role'] === 'editor'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    Editor
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-600 dark:text-slate-300">
                                    Viewer
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                                <?= htmlspecialchars($invite['invited_by_name'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php
                                $expiresAt = strtotime($invite['expires_at'] ?? '');
                                $isExpired = $expiresAt && $expiresAt < time();
                                ?>
                                <?php if ($isExpired): ?>
                                <span class="text-xs text-red-500 dark:text-red-400 font-medium">Scaduto</span>
                                <?php elseif ($expiresAt): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= date('d/m/Y H:i', $expiresAt) ?></span>
                                <?php else: ?>
                                <span class="text-xs text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="<?= url('/projects/' . $project['id'] . '/sharing/cancel-invite/' . $invite['id']) ?>"
                                      x-data @submit.prevent="if(confirm('Sei sicuro di voler annullare questo invito?')) $el.submit()">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Annulla
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Member Modal -->
        <div x-show="editModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="editModal = false"></div>

            <!-- Modal Content -->
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-md"
                 @click.away="editModal = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Modifica membro</h3>
                    <button @click="editModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form :action="'<?= url('/projects/' . $project['id'] . '/sharing/update/') ?>' + editUserId" method="POST" class="p-5 space-y-4">
                    <?= csrf_field() ?>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Ruolo</label>
                        <select name="role" x-model="editRole"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                            <option value="editor">Editor</option>
                            <option value="viewer">Viewer</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <strong>Editor</strong>: puo modificare i dati. <strong>Viewer</strong>: solo lettura.
                        </p>
                    </div>

                    <!-- Modules -->
                    <?php if (!empty($activeModules)): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Moduli accessibili</label>
                        <div class="space-y-2">
                            <?php foreach ($activeModules as $mod): ?>
                            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer transition-colors">
                                <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($mod['slug']) ?>"
                                       :checked="editModules.includes('<?= htmlspecialchars($mod['slug']) ?>')"
                                       class="rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                                <span class="w-2 h-2 rounded-full" style="background-color: <?= htmlspecialchars($mod['color'] ?? '#3B82F6') ?>"></span>
                                <span class="text-sm text-slate-700 dark:text-slate-300"><?= htmlspecialchars($mod['label']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="editModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                            Annulla
                        </button>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function sharingManager() {
    return {
        editModal: false,
        editUserId: null,
        editRole: 'editor',
        editModules: [],

        openEditModal(userId, role, modules) {
            this.editUserId = userId;
            this.editRole = role;
            this.editModules = Array.isArray(modules) ? modules : [];
            this.editModal = true;
        }
    };
}
</script>
