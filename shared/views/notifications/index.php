<?php
/**
 * Pagina completa notifiche con paginazione e filtri.
 *
 * Variabili dal controller:
 *   $notifications - array di notifiche
 *   $total         - int totale notifiche
 *   $page          - int pagina corrente
 *   $perPage       - int per pagina
 *   $filter        - ?string ('unread' o null)
 *   $unreadCount   - int conteggio non lette (opzionale, calcolato se assente)
 */

// Calcola se ci sono notifiche non lette
$hasUnread = false;
if (isset($unreadCount)) {
    $hasUnread = $unreadCount > 0;
} else {
    foreach ($notifications as $n) {
        if (empty($n['read_at'])) {
            $hasUnread = true;
            break;
        }
    }
}

// Color mapping per icon circle
$colorClasses = [
    'blue'    => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    'emerald' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    'amber'   => 'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
    'red'     => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
];

// Icon SVG paths per tipo
$iconPaths = [
    'user-plus'            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>',
    'check-circle'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'x-circle'             => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'exclamation-triangle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    'bell'                 => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifiche</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Le tue notifiche e aggiornamenti</p>
        </div>
        <?php if ($hasUnread): ?>
        <div class="mt-4 sm:mt-0">
            <form method="POST" action="<?= url('/notifications/read-all') ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Segna tutte come lette
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-6 -mb-px">
            <a href="<?= url('/notifications') ?>"
               class="pb-3 px-1 text-sm font-medium transition-colors <?= empty($filter) ? 'text-blue-400 border-b-2 border-blue-400' : 'text-slate-400 hover:text-slate-300' ?>">
                Tutte
            </a>
            <a href="<?= url('/notifications?filter=unread') ?>"
               class="pb-3 px-1 text-sm font-medium transition-colors <?= ($filter ?? '') === 'unread' ? 'text-blue-400 border-b-2 border-blue-400' : 'text-slate-400 hover:text-slate-300' ?>">
                Non lette
            </a>
        </nav>
    </div>

    <!-- Notification list -->
    <?php if (empty($notifications)): ?>
        <?= \Core\View::partial('components/table-empty-state', [
            'icon'    => $iconPaths['bell'],
            'heading' => 'Nessuna notifica',
            'message' => $filter === 'unread'
                ? 'Non hai notifiche non lette. Ottimo lavoro!'
                : 'Non hai ancora ricevuto nessuna notifica. Le notifiche appariranno qui quando ci saranno aggiornamenti.',
        ]) ?>
    <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($notifications as $notification): ?>
                <?php
                    $color = $notification['color'] ?? 'blue';
                    $circleClass = $colorClasses[$color] ?? $colorClasses['blue'];
                    $icon = $notification['icon'] ?? 'bell';
                    $iconPath = $iconPaths[$icon] ?? $iconPaths['bell'];
                    $isUnread = empty($notification['read_at']);
                ?>
                <a href="<?= e($notification['action_url'] ?? '#') ?>" class="block"
                   <?php if ($isUnread): ?>onclick="markNotifRead(<?= (int)$notification['id'] ?>)"<?php endif; ?>>
                    <div class="flex items-start gap-4 p-4 rounded-xl transition-colors
                                <?= $isUnread
                                    ? 'bg-blue-900/10 border-l-4 border-blue-500 hover:bg-blue-900/20'
                                    : 'bg-slate-800/50 hover:bg-slate-700/50' ?>">
                        <!-- Icon circle -->
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center <?= $circleClass ?>">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <?= $iconPath ?>
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm <?= $isUnread ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-700 dark:text-slate-300' ?>">
                                <?= e($notification['title']) ?>
                            </p>
                            <?php if (!empty($notification['body'])): ?>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400 line-clamp-2">
                                <?= e($notification['body']) ?>
                            </p>
                            <?php endif; ?>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                <?= e($notification['time_ago'] ?? '') ?>
                            </p>
                        </div>

                        <!-- Unread dot -->
                        <?php if ($isUnread): ?>
                        <div class="flex-shrink-0 mt-1.5">
                            <span class="block w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total > $perPage): ?>
            <?= \Core\View::partial('components/table-pagination', [
                'pagination' => \Core\Pagination::make($total, $page, $perPage),
                'baseUrl'    => url('/notifications'),
                'filters'    => array_filter([
                    'filter' => $filter,
                ], fn($v) => $v !== null && $v !== ''),
            ]) ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function markNotifRead(id) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content
                  || document.querySelector('input[name="_csrf_token"]')?.value || '';
    const fd = new FormData();
    fd.append('_csrf_token', token);
    navigator.sendBeacon('/notifications/' + id + '/read', fd);
}
</script>
