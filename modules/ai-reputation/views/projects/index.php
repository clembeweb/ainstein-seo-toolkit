<?php
// Prezzo in pagina dalle impostazioni del modulo (lo stesso che addebita RunController), mai scritto a mano
$arCost = rtrim(rtrim(number_format((float) \Core\Credits::getCost('collect_response', 'ai-reputation', 1), 1, ',', ''), '0'), ',');
?>
<!-- Hero Value Block -->
<div class="mb-6">
<?= \Core\View::partial('components/dashboard-hero-banner', [
    'title' => 'Scopri se ChatGPT e Gemini consigliano la tua attività',
    'description' => 'Sempre più clienti chiedono alle AI dove comprare, chi chiamare, cosa prenotare. AI GEO Audit fa a ChatGPT e Gemini le domande dei tuoi clienti e ti dice se ti nominano, chi consigliano al posto tuo e cosa pubblicare per entrare nelle risposte. Non serve essere esperti SEO. Costo: ' . $arCost . ' credit' . ($arCost === '1' ? 'o' : 'i') . ' a risposta, con la stima prima di partire.',
    'color' => 'indigo',
    'badge' => 'Come funziona',
    'storageKey' => 'ainstein_hero_ai_reputation',
    'steps' => [
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>', 'title' => 'Chi sei', 'subtitle' => 'Il profilo letto dal tuo sito, lo confermi tu'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>', 'title' => 'Le domande dei clienti', 'subtitle' => 'Poste a ChatGPT e Gemini'],
        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>', 'title' => 'Cosa fare', 'subtitle' => 'Articoli e correzioni pronti'],
    ],
    'ctaText' => 'Inizia con il tuo sito',
    'ctaUrl' => url('/projects/create'),
]) ?>
</div>

<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI GEO Audit</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scopri se ChatGPT e Gemini consigliano la tua attività, chi consigliano al posto tuo e cosa pubblicare</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-3">
            <?= \Core\View::partial('components/module-activate-existing', ['moduleSlug' => 'ai-reputation', 'userId' => (int) ($user['id'] ?? 0), 'color' => 'indigo']) ?>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <?php if (empty($projects)): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
        <div class="mx-auto h-16 w-16 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
        </div>
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun soggetto monitorato</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
            Crea un progetto con il nome della persona o dell'azienda, poi attiva il modulo AI GEO Audit dalla dashboard del progetto.
        </p>
        <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Crea il primo progetto
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project): ?>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-5 border-b border-slate-200 dark:border-slate-700">
                <a href="<?= url('/ai-reputation/project/' . $project['id']) ?>" class="block">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate hover:text-indigo-600 dark:hover:text-indigo-400"><?= e($project['name']) ?></h3>
                </a>
                <p class="text-sm text-slate-500 dark:text-slate-400 truncate mt-1">
                    <?= e($project['subject_name']) ?> · <?= $project['subject_type'] === 'company' ? 'Azienda' : 'Persona' ?>
                </p>
                <div class="mt-2 flex flex-wrap gap-1">
                    <?php foreach ($project['engines'] as $engine): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"><?= e(\Modules\AiReputation\Models\Project::ENGINE_LABELS[$engine] ?? $engine) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="grid grid-cols-3 divide-x divide-slate-200 dark:divide-slate-700">
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-slate-900 dark:text-white"><?= (int) $project['prompts_active'] ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Domande</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400"><?= (int) $project['runs_completed'] ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Run</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-slate-900 dark:text-white"><?= $project['last_run_at'] ? date('d/m', strtotime($project['last_run_at'])) : '–' ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Ultimo run</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?= \Core\View::partial('ai-reputation::partials/landing-educativa') ?>
