<?php
/**
 * Dashboard SEO Meta Tag
 * Mostra statistiche e azioni rapide per la gestione dei meta tag
 */
?>

<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?= $stats['total'] ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Totale URL</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-amber-600"><?= $stats['pending'] ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Da scrapare</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-blue-600"><?= $stats['scraped'] ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Scrappate</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-purple-600"><?= $stats['generated'] ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Generate</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-emerald-600"><?= $stats['approved'] ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Approvate</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-2xl font-bold text-green-600"><?= $stats['published'] ?></div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Pubblicate</div>
        </div>
    </div>

    <?php if ($stats['errors'] > 0): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-red-700 dark:text-red-300 font-medium"><?= $stats['errors'] ?> URL con errori</span>
            </div>
            <form action="<?= url("/ai-content/projects/{$project['id']}/meta-tags/reset-scrape-errors") ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 underline">
                    Riprova
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Import -->
        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/import") ?>"
           class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-900 transition-colors">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Importa URL</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">WordPress, Sitemap, CSV</p>
                </div>
            </div>
        </a>

        <!-- Scrape -->
        <button type="button"
                onclick="runScrape()"
                <?= $stats['pending'] === 0 ? 'disabled' : '' ?>
                class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group text-left disabled:opacity-50 disabled:cursor-not-allowed">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-900 transition-colors">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Scrape Pagine</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $stats['pending'] ?> in attesa (1 cr/pag)</p>
                </div>
            </div>
        </button>

        <!-- Generate -->
        <button type="button"
                onclick="runGenerate()"
                <?= $stats['scraped'] === 0 ? 'disabled' : '' ?>
                class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group text-left disabled:opacity-50 disabled:cursor-not-allowed">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900 transition-colors">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Genera Meta Tag</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $stats['scraped'] ?> pronte (2 cr/pag)</p>
                </div>
            </div>
        </button>

        <!-- View All -->
        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>"
           class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6 hover:border-primary-500 dark:hover:border-primary-500 transition-colors group">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-slate-200 dark:group-hover:bg-slate-600 transition-colors">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Vedi Tutti</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= $stats['total'] ?> meta tag</p>
                </div>
            </div>
        </a>
    </div>

    <!-- WordPress Status -->
    <?php if ($wpSite): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-slate-900 dark:text-white"><?= e($wpSite['name']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($wpSite['url']) ?></p>
                </div>
            </div>
            <span class="px-2 py-1 text-xs font-medium rounded-full <?= $wpSite['is_active'] ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400' ?>">
                <?= $wpSite['is_active'] ? 'Connesso' : 'Disconnesso' ?>
            </span>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-medium text-amber-700 dark:text-amber-300">Nessun sito WordPress collegato</p>
                <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">
                    Per importare pagine e pubblicare meta tag su WordPress, collega un sito nelle
                    <a href="<?= url("/ai-content/projects/{$project['id']}/settings") ?>" class="underline">impostazioni</a>.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Meta Tags -->
    <?php if (!empty($recent)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-semibold text-slate-900 dark:text-white">Ultimi Meta Tag</h2>
        </div>
        <div class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($recent as $item): ?>
            <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/{$item['id']}") ?>"
                           class="text-sm font-medium text-slate-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block">
                            <?= e($item['original_title'] ?: $item['url']) ?>
                        </a>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?= e($item['url']) ?></p>
                    </div>
                    <span class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full
                        <?php
                        switch ($item['status']) {
                            case 'pending':
                                echo 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400';
                                break;
                            case 'scraped':
                                echo 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300';
                                break;
                            case 'generated':
                                echo 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300';
                                break;
                            case 'approved':
                                echo 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300';
                                break;
                            case 'published':
                                echo 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300';
                                break;
                            case 'error':
                                echo 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300';
                                break;
                        }
                        ?>">
                        <?= ucfirst($item['status']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            <a href="<?= url("/ai-content/projects/{$project['id']}/meta-tags/list") ?>"
               class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                Vedi tutti i meta tag
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Workflow Guide -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-6">
        <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Come funziona</h3>
        <div class="grid md:grid-cols-5 gap-4">
            <div class="text-center">
                <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mx-auto mb-2">
                    <span class="text-blue-600 dark:text-blue-400 font-bold">1</span>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Importa</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">URL da WP/Sitemap/CSV</p>
            </div>
            <div class="text-center">
                <div class="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mx-auto mb-2">
                    <span class="text-amber-600 dark:text-amber-400 font-bold">2</span>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Scrape</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Analizza contenuto</p>
            </div>
            <div class="text-center">
                <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mx-auto mb-2">
                    <span class="text-purple-600 dark:text-purple-400 font-bold">3</span>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Genera</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">AI crea title/desc</p>
            </div>
            <div class="text-center">
                <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mx-auto mb-2">
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">4</span>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Approva</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Rivedi e modifica</p>
            </div>
            <div class="text-center">
                <div class="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center mx-auto mb-2">
                    <span class="text-green-600 dark:text-green-400 font-bold">5</span>
                </div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Pubblica</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Su WordPress</p>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div id="progressModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
        <div class="text-center">
            <div class="animate-spin h-12 w-12 border-4 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"></div>
            <h3 id="progressTitle" class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Elaborazione in corso...</h3>
            <p id="progressMessage" class="text-sm text-slate-500 dark:text-slate-400"></p>
        </div>
    </div>
</div>

<script>
const projectId = <?= $project['id'] ?>;
const csrfToken = '<?= csrf_token() ?>';

function showProgress(title, message) {
    document.getElementById('progressTitle').textContent = title;
    document.getElementById('progressMessage').textContent = message;
    document.getElementById('progressModal').style.display = 'flex';
}

function hideProgress() {
    document.getElementById('progressModal').style.display = 'none';
}

async function runScrape() {
    showProgress('Scraping pagine...', 'Analisi contenuto in corso');

    try {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('batch_size', '10');

        const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/scrape") ?>', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        hideProgress();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Errore: ' + data.error);
        }
    } catch (error) {
        hideProgress();
        alert('Errore di connessione');
    }
}

async function runGenerate() {
    showProgress('Generazione meta tag...', 'AI sta elaborando i contenuti');

    try {
        const formData = new FormData();
        formData.append('_csrf_token', csrfToken);
        formData.append('batch_size', '10');

        const response = await fetch('<?= url("/ai-content/projects/{$project['id']}/meta-tags/generate") ?>', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        hideProgress();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Errore: ' + data.error);
        }
    } catch (error) {
        hideProgress();
        alert('Errore di connessione');
    }
}
</script>
