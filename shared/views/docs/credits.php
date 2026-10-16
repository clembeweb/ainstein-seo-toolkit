<?php
// Tutti i costi e i piani sono letti dalle impostazioni vere (Admin > Moduli, Admin > Piani): mai numeri scritti a mano
$_crFmt = fn(float $n): string => rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
$_crBadge = function (?float $n, string $suffix = '') use ($_crFmt): string {
    if ($n === null) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">Indicato prima di partire</span>';
    }
    if ($n <= 0) {
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">Gratis</span>';
    }
    $color = $n <= 1 ? 'blue' : ($n <= 3 ? 'amber' : 'purple');
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' . $color . '-100 dark:bg-' . $color . '-900/30 text-' . $color . '-700 dark:text-' . $color . '-300 whitespace-nowrap">'
        . $_crFmt($n) . ' cr' . $suffix . '</span>';
};

$_crBonus = \Core\Credits::signupBonus();

// AI Writer
$_crSerp = \Core\Credits::getCost('serp_extraction', 'ai-content', 3);
$_crScrape = \Core\Credits::getCost('content_scrape', 'ai-content', 1);
$_crBrief = \Core\Credits::getCost('brief_generation', 'ai-content', 3);
$_crArticle = \Core\Credits::getCost('article_generation', 'ai-content', 10);
$_crCover = \Core\Credits::getCost('cover_image_generation', 'ai-content', 3);
$_crMetaScrape = \Core\Credits::getCost('meta_scrape', 'ai-content', 1);
$_crMetaGen = \Core\Credits::getCost('meta_generate', 'ai-content', 2);
$_crArticleTotal = $_crSerp + $_crScrape + $_crBrief + $_crArticle + $_crCover;

// AI Keywords
$_crKrResearch = (float) \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_ai_clustering', 3);
$_crKrArchitecture = (float) \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_ai_architecture', 5);
$_crKrEditorial = (float) \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_editorial_plan', 5);

// AI Audit
$_crCrawl = \Core\Credits::getCost('crawl_per_page', 'seo-audit', 0.1);
$_crAuditReport = (float) \Core\ModuleLoader::getSetting('seo-audit', 'cost_unified_report', 15);

// AI Rank
$_crRank = \Core\Credits::getCost('rank_check', 'seo-tracking', 1);
$_crGscFull = \Core\Credits::getCost('gsc_full_sync', 'seo-tracking', 10);
$_crPage = \Core\Credits::getCost('page_analysis', 'seo-tracking', 3);
$_crSituation = \Core\Credits::getCost('situation_report', 'seo-tracking', 5);

// AI GEO Audit
$_crGeoResponse = \Core\Credits::getCost('collect_response', 'ai-reputation', 1);
$_crGeoReanalyze = \Core\Credits::getCost('reanalyze_response', 'ai-reputation', 0.5);
$_crGeoBrief = \Core\Credits::getCost('action_brief', 'ai-reputation', 1);

$_crTables = [
    [
        'name' => 'AI Writer',
        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'rows' => [
            ['Lettura dei risultati Google per la parola chiave', $_crSerp, ''],
            ['Lettura di una pagina concorrente', $_crScrape, '/pagina'],
            ['Scaletta dell\'articolo (brief)', $_crBrief, ''],
            ['Scrittura dell\'articolo', $_crArticle, ''],
            ['Immagine di copertina (facoltativa)', $_crCover, ''],
            ['Meta tag: lettura della pagina', $_crMetaScrape, '/pagina'],
            ['Meta tag: scrittura di title e description', $_crMetaGen, '/pagina'],
        ],
        'note' => 'Un articolo completo, con una pagina concorrente letta e la copertina, costa circa <strong class="text-slate-700 dark:text-slate-300">' . $_crFmt($_crArticleTotal) . ' crediti</strong>. Ogni pagina concorrente in più aggiunge ' . $_crFmt($_crScrape) . ' cr. La pubblicazione su WordPress non costa crediti.',
    ],
    [
        'name' => 'AI Keywords',
        'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
        'rows' => [
            ['Ricerca guidata (raggruppamento delle keyword con l\'AI)', $_crKrResearch, ''],
            ['Architettura sito', $_crKrArchitecture, ''],
            ['Piano editoriale', $_crKrEditorial, ''],
            ['Controllo veloce di una parola chiave', 0, ''],
        ],
        'note' => null,
    ],
    [
        'name' => 'AI Audit',
        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'rows' => [
            ['Scansione del sito', $_crCrawl, '/pagina'],
            ['Report AI dell\'audit', $_crAuditReport, ''],
            ['Consultare risultati, storico ed export', 0, ''],
        ],
        'note' => null,
    ],
    [
        'name' => 'AI Rank',
        'icon' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
        'rows' => [
            ['Controllo posizioni lanciato da te', $_crRank, '/keyword'],
            ['Aggiornamento volumi di ricerca', null, ''],
            ['Importazione completa da Google Search Console', $_crGscFull, ''],
            ['Elenco Quick Wins', 0, ''],
            ['Analisi di una pagina', $_crPage, ''],
            ['Andamento (Google Analytics + Search Console + AI)', $_crSituation, ''],
        ],
        'note' => null,
    ],
    [
        'name' => 'AI GEO Audit',
        'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'rows' => [
            ['Controllo tecnico del sito', 0, ''],
            ['Ogni risposta raccolta e analizzata (una domanda a una AI)', $_crGeoResponse, '/risposta'],
            ['Rianalizza una risposta già raccolta', $_crGeoReanalyze, '/risposta'],
            ['Scheda operativa di un intervento', $_crGeoBrief, ''],
            ['Report e PDF', 0, ''],
        ],
        'note' => 'Esempio: 30 domande fatte a ChatGPT e Gemini sono 60 risposte, cioè <strong class="text-slate-700 dark:text-slate-300">' . $_crFmt(60 * $_crGeoResponse) . ' crediti</strong>. Prima di avviare vedi sempre quante risposte e quanti crediti servono.',
    ],
];

// Controllo automatico delle posizioni: gratuito, mostrato solo se attivo (Admin > Moduli > AI Rank)
if (\Core\ModuleLoader::getSetting('seo-tracking', 'rank_auto_enabled', false)) {
    array_splice($_crTables[3]['rows'], 1, 0, [['Controllo automatico periodico delle posizioni', 0, '']]);
}

// Piani attivi a pagamento (Admin > Piani)
$_crPlans = [];
try {
    $_crPlans = \Core\Database::fetchAll("SELECT name, price_monthly, credits_monthly FROM plans WHERE is_active = 1 AND price_monthly > 0 ORDER BY price_monthly");
} catch (\Throwable $e) {
    $_crPlans = [];
}
$_crEuro = fn(float $n): string => fmod($n, 1.0) == 0.0 ? number_format($n, 0, ',', '.') : number_format($n, 2, ',', '.');
?>
<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">Sistema Crediti</span>
</nav>

<!-- Header -->
<div class="mb-10">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Sistema Crediti</h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
        In Ainstein si paga a crediti. Le operazioni che usano l'AI o servizi esterni costano un certo numero di crediti. Guardare i risultati, consultare i report ed esportare non costa nulla. Il costo lo vedi sempre prima di partire.
    </p>
</div>

<!-- Crediti di benvenuto -->
<section class="mb-12">
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <svg class="w-8 h-8 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
            </svg>
            <div>
                <h2 class="text-xl font-semibold text-emerald-900 dark:text-emerald-200 mb-2"><?= $_crBonus ?> crediti in regalo all'iscrizione</h2>
                <p class="text-sm text-emerald-800 dark:text-emerald-300">
                    Quando ti registri ricevi <?= $_crBonus ?> crediti, una volta sola, per provare i moduli. Non serve la carta di credito. Non c'è un piano gratuito e non c'è una ricarica mensile gratuita: quando i crediti finiscono puoi attivare uno dei piani qui sotto.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Come funzionano -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Come funzionano
    </h2>

    <div class="space-y-4 ml-10">
        <?php foreach ([
            'Ogni operazione ha un <strong class="text-slate-900 dark:text-white">costo fisso</strong>, che vedi prima di confermare.',
            'I crediti si scalano <strong class="text-slate-900 dark:text-white">quando il lavoro è fatto</strong>.',
            'Se i crediti non bastano, l\'operazione <strong class="text-slate-900 dark:text-white">non parte</strong> e ti viene detto quanti ne servono.',
            'Guardare i risultati, consultare i report ed esportare è <strong class="text-slate-900 dark:text-white">gratis</strong>.',
            'Nei progetti condivisi paga sempre il <strong class="text-slate-900 dark:text-white">proprietario</strong> del progetto, chiunque lanci l\'operazione.',
        ] as $_crPoint): ?>
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-slate-600 dark:text-slate-400"><?= $_crPoint ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Costi per modulo -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        Costi per modulo
    </h2>

    <div class="space-y-8">
        <?php foreach ($_crTables as $_crTable): ?>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="bg-slate-50 dark:bg-slate-800 px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_crTable['icon'] ?>"/>
                </svg>
                <h3 class="font-semibold text-slate-900 dark:text-white"><?= $_crTable['name'] ?></h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Operazione</th>
                            <th class="text-right px-5 py-3 font-medium text-slate-500 dark:text-slate-400">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        <?php foreach ($_crTable['rows'] as [$_crLabel, $_crCost, $_crSuffix]): ?>
                        <tr>
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-300"><?= htmlspecialchars($_crLabel) ?></td>
                            <td class="px-5 py-3 text-right"><?= $_crBadge($_crCost === null ? null : (float) $_crCost, $_crSuffix) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($_crTable['note'])): ?>
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                <?= $_crTable['note'] ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Piani -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        Piani
    </h2>

    <?php if (!empty($_crPlans)): ?>
    <div class="grid grid-cols-1 sm:grid-cols-<?= min(3, count($_crPlans)) ?> gap-4 mb-6">
        <?php foreach ($_crPlans as $_crPlan):
            $_crPlanCredits = (int) $_crPlan['credits_monthly'];
            $_crPlanArticles = $_crArticleTotal > 0 ? (int) floor($_crPlanCredits / $_crArticleTotal) : 0;
        ?>
        <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><?= htmlspecialchars($_crPlan['name']) ?></h3>
            <p class="text-3xl font-bold text-slate-900 dark:text-white mb-1"><?= number_format($_crPlanCredits, 0, ',', '.') ?> cr/mese</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">&euro;<?= $_crEuro((float) $_crPlan['price_monthly']) ?>/mese</p>
            <?php if ($_crPlanArticles > 0): ?>
            <p class="text-xs text-slate-600 dark:text-slate-400">Per esempio, circa <?= number_format($_crPlanArticles, 0, ',', '.') ?> articoli completi con copertina</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-primary-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-primary-800 dark:text-primary-300 mb-1">Come attivare un piano</p>
                <p class="text-sm text-primary-700 dark:text-primary-400">
                    Per ora non c'è il pagamento online. Scrivi a <a href="mailto:info@ainstein.it" class="underline font-medium hover:text-primary-900 dark:hover:text-primary-200">info@ainstein.it</a> dicendo quale piano ti interessa: ti rispondiamo e attiviamo il piano a mano sul tuo account. Allo stesso indirizzo puoi chiedere di cambiare piano o di chiuderlo. Trovi i piani anche nella pagina <a href="<?= url('/pricing') ?>" class="underline font-medium hover:text-primary-900 dark:hover:text-primary-200">Prezzi</a>.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Monitoraggio -->
<section class="mb-8">
    <h2 class="text-2xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
        <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        Dove vedi il saldo
    </h2>

    <div class="space-y-4">
        <div class="flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Nella barra in alto</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Il saldo è sempre visibile in alto, in ogni pagina dell'applicazione. Cliccandoci sopra vai alla pagina dei piani.</p>
            </div>
        </div>

        <div class="flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">Nel Profilo</h3>
                <p class="text-sm text-slate-600 dark:text-slate-400">Nella pagina Profilo trovi il saldo attuale e il piano attivo.</p>
            </div>
        </div>
    </div>

    <!-- Warning crediti bassi -->
    <div class="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">Crediti in esaurimento?</p>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                    Sotto i 10 crediti il saldo in alto diventa arancione, sotto i 3 diventa rosso, e compare il link "Ricarica". Prima di lanciare un'operazione lunga, come un articolo o un audit con molte domande, controlla di avere crediti a sufficienza.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Navigation -->
<div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
    <a href="<?= url('/docs/ai-reputation') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        AI GEO Audit
    </a>
    <a href="<?= url('/docs/faq') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
        FAQ
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
</div>
