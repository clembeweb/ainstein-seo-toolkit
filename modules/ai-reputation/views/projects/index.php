<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Reputation Radar</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cosa dicono ChatGPT, Gemini e le altre AI di una persona o di un'azienda: fonti, rischi, piano d'azione</p>
        </div>
        <div class="mt-4 sm:mt-0">
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
            Crea un progetto con il nome della persona o dell'azienda, poi attiva il modulo AI Reputation Radar dalla dashboard del progetto.
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
