<?php
// Tour guidato (config/onboarding.php)
$onboardingConfig = require BASE_PATH . '/config/onboarding.php';
$onboardingModuleSlug = 'ai-reputation';
$showTour = !\Core\OnboardingService::isModuleCompleted($user['id'] ?? 0, $onboardingModuleSlug);
if (isset($onboardingConfig[$onboardingModuleSlug])) {
    echo \Core\View::partial('components/onboarding-spotlight', [
        'onboardingSteps' => $onboardingConfig[$onboardingModuleSlug]['steps'],
        'onboardingModuleName' => $onboardingConfig[$onboardingModuleSlug]['name'],
        'onboardingModuleSlug' => $onboardingModuleSlug,
        'onboardingAutoOpen' => $showTour,
    ]);
}
?>
<?php
// Prezzo in pagina dalle impostazioni del modulo (lo stesso che addebita RunController), mai scritto a mano
$arCost = rtrim(rtrim(number_format((float) \Core\Credits::getCost('collect_response', 'ai-reputation', 1), 1, ',', ''), '0'), ',');
?>

<div class="space-y-6">
    <div class="sm:flex sm:items-center sm:justify-between" data-tour="ar-header">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI GEO Audit</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Scopri se ChatGPT e Gemini consigliano la tua attività, chi consigliano al posto tuo e cosa pubblicare</p>
        </div>
        <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-3">
            <?= \Core\View::partial('components/module-activate-existing', ['moduleSlug' => 'ai-reputation', 'userId' => (int) ($user['id'] ?? 0), 'color' => 'indigo']) ?>
            <a href="<?= url('/projects/create') ?>" data-tour="ar-newproject" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuovo Progetto
            </a>
        </div>
    </div>

    <?php if (!empty($projects)): ?>
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
                    <p class="text-xs text-slate-500 dark:text-slate-400">Audit</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xl font-bold text-slate-900 dark:text-white"><?= $project['last_run_at'] ? date('d/m', strtotime($project['last_run_at'])) : '–' ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Ultimo audit</p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Come iniziare (al posto del vecchio banner, decisione Clemente 2026-10-16) -->
    <?= \Core\View::partial('components/module-getting-started', [
        'color' => 'indigo',
        'subtitle' => 'Sapere se ChatGPT e Gemini consigliano la tua attività e cosa fare per farti citare.',
        'open' => empty($projects),
        'steps' => [
            ['title' => 'Conferma chi sei', 'text' => 'Crea il progetto con il sito: l\'AI lo legge e prepara il profilo dell\'attività. Tu lo controlli e lo confermi.'],
            ['title' => 'Fai le domande dei clienti', 'text' => 'Ainstein propone le domande che i clienti fanno alle AI (le cambi come vuoi) e raccoglie le risposte. ' . $arCost . ' credit' . ($arCost === '1' ? 'o' : 'i') . ' a risposta, con la stima prima di partire.'],
            ['title' => 'Leggi cosa fare', 'text' => 'Vedi se ti nominano, chi consigliano al posto tuo e le schede con i testi pronti da pubblicare.'],
        ],
        'ctaText' => empty($projects) ? 'Crea il primo progetto' : null,
        'ctaUrl' => url('/projects/create'),
    ]) ?>
</div>

<?= \Core\View::partial('ai-reputation::partials/landing-educativa') ?>
