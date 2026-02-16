<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Torna alle keyword
        </a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Aggiungi Keyword</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Aggiungi keyword da monitorare per <strong><?= e($project['name']) ?></strong>
        </p>
    </div>

    <!-- Manual Input Form -->
    <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/store') ?>" method="POST" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
        <div class="p-6 space-y-6">
            <!-- Keywords Input -->
            <div>
                <label for="keywords" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Keyword <span class="text-red-500">*</span>
                </label>
                <textarea name="keywords" id="keywords" rows="8" required
                          class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                          placeholder="scarpe running uomo&#10;migliori scarpe da corsa&#10;scarpe nike running"></textarea>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Una keyword per riga</p>
            </div>

            <!-- Location -->
            <div>
                <label for="location_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Location <span class="text-red-500">*</span>
                </label>
                <select name="location_code" id="location_code" required
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <?php foreach ($locations ?? [] as $loc): ?>
                    <option value="<?= e($loc['country_code']) ?>" <?= ($loc['country_code'] === 'IT') ? 'selected' : '' ?>>
                        <?= e($loc['name']) ?> (<?= e($loc['country_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                    <?php if (empty($locations)): ?>
                    <option value="IT" selected>Italia (IT)</option>
                    <?php endif; ?>
                </select>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Paese per verifica posizionamento e volumi di ricerca</p>
            </div>

            <!-- Group -->
            <div>
                <label for="group_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                    Gruppo
                </label>
                <div class="flex gap-2">
                    <select name="group_name_select" id="group_name_select" onchange="updateGroupInput(this)"
                            class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Nessun gruppo</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?= e($group['group_name']) ?>"><?= e($group['group_name']) ?> (<?= $group['count'] ?>)</option>
                        <?php endforeach; ?>
                        <option value="__new__">+ Nuovo gruppo...</option>
                    </select>
                    <input type="text" name="group_name" id="group_name"
                           class="flex-1 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 hidden"
                           placeholder="Nome nuovo gruppo">
                </div>
            </div>

            <!-- Track -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_tracked" value="1" checked
                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500">
                <div>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">Traccia posizioni</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Monitora giornalmente le variazioni di posizione</span>
                </div>
            </label>

            <!-- Auto-fetch dati -->
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="auto_fetch" id="auto_fetch" value="1" checked
                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500"
                       onchange="updateCostPreview()">
                <div>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">Recupera dati automaticamente</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">Ottieni volume, CPC e competition per ogni keyword (0.5 crediti/kw)</span>
                </div>
            </label>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 rounded-b-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span id="keywordCount" class="text-sm text-slate-600 dark:text-slate-400">0 keyword</span>
                    <span id="costPreview" class="text-sm text-slate-500 dark:text-slate-500 ml-1 hidden">
                        <span class="text-slate-400 dark:text-slate-600">|</span>
                        <svg class="w-4 h-4 inline-block ml-1 -mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="costValue">0</span> crediti
                    </span>
                </div>
                <div class="flex gap-3">
                    <a href="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords') ?>" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                        Annulla
                    </a>
                    <button type="submit" class="px-6 py-2 rounded-lg bg-primary-600 text-white font-medium hover:bg-primary-700 transition-colors">
                        Aggiungi Keyword
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- CSV Import -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="font-medium text-slate-900 dark:text-white">Importa da CSV</h2>
        </div>
        <form action="<?= url('/seo-tracking/project/' . $project['id'] . '/keywords/import') ?>" method="POST" enctype="multipart/form-data" class="p-6">
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="file" name="csv_file" accept=".csv" required
                           class="w-full text-sm text-slate-500 dark:text-slate-400
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0
                                  file:text-sm file:font-medium
                                  file:bg-primary-50 file:text-primary-700
                                  dark:file:bg-primary-900/50 dark:file:text-primary-300
                                  hover:file:bg-primary-100 dark:hover:file:bg-primary-900/70">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Importa
                </button>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Formato CSV: keyword, gruppo (opzionale), target_url (opzionale)
            </p>
        </form>
    </div>

    <!-- Tips -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex gap-3">
            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Suggerimenti</h4>
                <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Le keyword vengono anche scoperte automaticamente durante la sincronizzazione GSC</li>
                    <li>• Usa i gruppi per organizzare keyword per categoria o intento</li>
                    <li>• Le keyword tracciate vengono monitorate con storico posizioni giornaliero</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function updateGroupInput(select) {
    const textInput = document.getElementById('group_name');
    if (select.value === '__new__') {
        textInput.classList.remove('hidden');
        textInput.focus();
    } else {
        textInput.classList.add('hidden');
        textInput.value = select.value;
    }
}

function countKeywords() {
    const textarea = document.getElementById('keywords');
    const lines = textarea.value.split('\n').filter(line => line.trim() !== '');
    return lines.length;
}

function calculateCost(count) {
    if (count === 0) return 0;
    // 0.5 crediti/kw, 0.3 se 10+ keyword
    const costPerKeyword = count >= 10 ? 0.3 : 0.5;
    return (count * costPerKeyword).toFixed(1);
}

function updateCostPreview() {
    const count = countKeywords();
    const autoFetch = document.getElementById('auto_fetch').checked;

    // Update keyword count
    const countEl = document.getElementById('keywordCount');
    countEl.textContent = count + ' keyword' + (count !== 1 ? '' : '');

    // Update cost preview
    const costPreviewEl = document.getElementById('costPreview');
    const costValueEl = document.getElementById('costValue');

    if (autoFetch && count > 0) {
        const cost = calculateCost(count);
        costValueEl.textContent = cost;
        costPreviewEl.classList.remove('hidden');
    } else {
        costPreviewEl.classList.add('hidden');
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('keywords');

    // Update on input (with debounce for performance)
    let debounceTimer;
    textarea.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(updateCostPreview, 150);
    });

    // Initial update
    updateCostPreview();
});
</script>
