<?php
// Costi letti dalle impostazioni vere (Admin > Moduli), mai scritti a mano
$_moFmt = fn(float $n): string => rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
$_moArticle = \Core\Credits::getCost('serp_extraction', 'ai-content', 3)
    + \Core\Credits::getCost('content_scrape', 'ai-content', 1)
    + \Core\Credits::getCost('brief_generation', 'ai-content', 3)
    + \Core\Credits::getCost('article_generation', 'ai-content', 10)
    + \Core\Credits::getCost('cover_image_generation', 'ai-content', 3);
$_moCrawl = \Core\Credits::getCost('crawl_per_page', 'seo-audit', 0.1);
$_moRank = \Core\Credits::getCost('rank_check', 'seo-tracking', 1);
$_moKr = [
    (float) \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_ai_clustering', 3),
    (float) \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_ai_architecture', 5),
    (float) \Core\ModuleLoader::getSetting('keyword-research', 'cost_kr_editorial_plan', 5),
];
$_moKrMin = min($_moKr);
$_moKrMax = max($_moKr);
$_moGeo = \Core\Credits::getCost('collect_response', 'ai-reputation', 1);

$_moRows = [
    [
        'name' => 'AI Writer', 'color' => 'amber',
        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'what' => 'Scrive articoli per il blog e i meta tag (title e description) delle pagine',
        'when' => 'Devi pubblicare articoli ma non hai tempo di scriverli da zero',
        'cost' => '~' . $_moFmt($_moArticle) . ' cr/articolo',
    ],
    [
        'name' => 'AI Audit', 'color' => 'emerald',
        'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'what' => 'Scansiona il sito e trova i problemi tecnici che frenano Google',
        'when' => 'Vuoi sapere se il sito ha errori e cosa sistemare per primo',
        'cost' => $_moCrawl <= 0 ? 'Scansione gratis' : $_moFmt($_moCrawl) . ' cr/pagina',
    ],
    [
        'name' => 'AI Rank', 'color' => 'blue',
        'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        'what' => 'Segue la posizione delle tue parole chiave su Google e legge Search Console',
        'when' => 'Vuoi vedere se stai salendo o scendendo su Google nel tempo',
        'cost' => $_moFmt($_moRank) . ' cr/keyword',
    ],
    [
        'name' => 'AI Keywords', 'color' => 'purple',
        'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        'what' => 'Trova le parole che cercano i clienti, le raggruppa e propone pagine e piano editoriale',
        'when' => 'Non sai di cosa scrivere o quali pagine servono al sito',
        'cost' => ($_moKrMin == $_moKrMax ? $_moFmt($_moKrMin) : $_moFmt($_moKrMin) . '-' . $_moFmt($_moKrMax)) . ' cr/ricerca',
    ],
    [
        'name' => 'AI GEO Audit', 'color' => 'indigo',
        'icon' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'what' => 'Chiede a ChatGPT e Gemini quello che chiedono i tuoi clienti e ti dice se consigliano te o altri',
        'when' => 'Vuoi sapere cosa dicono le AI della tua attività e come farti citare',
        'cost' => $_moFmt($_moGeo) . ' cr/risposta',
    ],
];
?>
<div class="space-y-12">

    <!-- Header -->
    <div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Panoramica Moduli</h1>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">I 5 moduli di Ainstein a confronto: cosa fanno, quando usarli e quanto costano</p>
    </div>

    <!-- Intro -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Quale modulo ti serve?
        </h2>
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                Ainstein ha 5 moduli. Servono a farti trovare da chi cerca quello che offri: su Google e nelle risposte di ChatGPT e Gemini. Ogni modulo funziona da solo, ma insieme coprono tutto il lavoro: capire cosa cercano i clienti, scrivere i contenuti, sistemare il sito e controllare i risultati. Vanno bene anche per una piccola attività, un blog o un piccolo negozio online.
            </p>
        </div>
    </section>

    <!-- Tabella comparativa -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Confronto rapido
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80">
                        <th class="text-left px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">Modulo</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">Cosa fa</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">Quando usarlo</th>
                        <th class="text-center px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">Costo indicativo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($_moRows as $_r): $_c = $_r['color']; ?>
                    <tr class="bg-white dark:bg-slate-800">
                        <td class="px-5 py-3 font-medium text-slate-900 dark:text-white whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-<?= $_c ?>-50 dark:bg-<?= $_c ?>-900/30 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-<?= $_c ?>-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_r['icon'] ?>"/>
                                    </svg>
                                </div>
                                <?= $_r['name'] ?>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($_r['what']) ?></td>
                        <td class="px-5 py-3 text-slate-600 dark:text-slate-400"><?= htmlspecialchars($_r['when']) ?></td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $_c ?>-100 text-<?= $_c ?>-700 dark:bg-<?= $_c ?>-900/40 dark:text-<?= $_c ?>-300 whitespace-nowrap"><?= htmlspecialchars($_r['cost']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Sono i costi attuali. Prima di ogni operazione a pagamento Ainstein ti mostra quanti crediti servono. Il dettaglio completo è nella pagina <a href="<?= url('/docs/credits') ?>" class="underline hover:no-underline">Sistema Crediti</a>.
        </p>
    </section>

    <!-- Dettaglio per modulo -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Dettaglio moduli
        </h2>
        <?php
        $_moDetails = [
            [
                'slug' => 'ai-content', 'name' => 'AI Writer', 'color' => 'amber', 'icon' => $_moRows[0]['icon'],
                'text' => 'Gli dai una parola chiave e lui fa il resto: guarda i primi risultati su Google, legge le pagine dei concorrenti, prepara una scaletta (che puoi modificare) e scrive l\'articolo. Se vuoi crea anche l\'immagine di copertina e pubblica su WordPress. Scrive anche title e description per le pagine che hai già, e può programmare articoli in automatico.',
                'tags' => ['Articoli', 'Scaletta modificabile', 'Meta tag', 'Copertina', 'WordPress', 'Articoli programmati'],
            ],
            [
                'slug' => 'seo-audit', 'name' => 'AI Audit', 'color' => 'emerald', 'icon' => $_moRows[1]['icon'],
                'text' => 'Visita il sito come fa Google e controlla ogni pagina: titoli e descrizioni mancanti, link rotti, immagini senza testo alternativo, pagine lente o che Google non può indicizzare. Ti dà un punteggio di salute e una lista di problemi ordinata per gravità. Il Report AI legge tutta la scansione e ti dice cosa correggere prima, con il codice pronto.',
                'tags' => ['Scansione del sito', 'Punteggio di salute', 'Problemi per gravità', 'Report AI', 'Storico scansioni'],
            ],
            [
                'slug' => 'seo-tracking', 'name' => 'AI Rank', 'color' => 'blue', 'icon' => $_moRows[2]['icon'],
                'text' => 'Controlla in che posizione sei su Google per le parole chiave che ti interessano e ti mostra come cambia nel tempo. Collegando Google Search Console vedi anche clic e visualizzazioni reali. I Quick Wins ti indicano le parole chiave vicine alla prima pagina, dove basta poco per salire; l\'analisi pagina ti dice cosa migliorare in una pagina precisa.',
                'tags' => ['Posizioni su Google', 'Search Console', 'Quick Wins', 'Analisi pagina', 'Gruppi di keyword', 'Avvisi'],
            ],
            [
                'slug' => 'keyword-research', 'name' => 'AI Keywords', 'color' => 'purple', 'icon' => $_moRows[3]['icon'],
                'text' => 'Ti aiuta a capire cosa cercano davvero i clienti. Con la Ricerca guidata parti da poche parole e ottieni gruppi di keyword con i volumi di ricerca. L\'Architettura sito ti propone quali pagine creare. Il Piano editoriale ti dice quali articoli scrivere e in che ordine. Il Controllo veloce verifica una singola parola chiave, gratis.',
                'tags' => ['Ricerca guidata', 'Architettura sito', 'Piano editoriale', 'Controllo veloce gratuito'],
            ],
            [
                'slug' => 'ai-reputation', 'name' => 'AI GEO Audit', 'color' => 'indigo', 'icon' => $_moRows[4]['icon'],
                'text' => 'Sempre più persone chiedono a ChatGPT e Gemini "a chi mi rivolgo per...". Questo modulo fa alle AI le domande che farebbero i tuoi clienti e ti mostra se consigliano te, chi consigliano al posto tuo e quali siti usano come fonte. Poi ti dà un piano: articoli da pubblicare, dati da chiarire sul sito, correzioni tecniche. Puoi fare l\'audit d\'acquisto (chi cerca il tuo servizio) e quello di reputazione (cosa dicono di te). Il Controllo tecnico è gratuito e il piano si esporta in PDF.',
                'tags' => ['Audit d\'acquisto', 'Audit di reputazione', 'Fonti citate', 'Piano d\'azione', 'Controllo tecnico gratuito', 'PDF'],
            ],
        ];
        ?>
        <div class="space-y-4">
            <?php foreach ($_moDetails as $_d): $_c = $_d['color']; ?>
            <div class="rounded-xl border border-<?= $_c ?>-200 dark:border-<?= $_c ?>-800/50 bg-white dark:bg-slate-800 overflow-hidden">
                <div class="px-5 py-4 bg-<?= $_c ?>-50 dark:bg-<?= $_c ?>-900/20 border-b border-<?= $_c ?>-200 dark:border-<?= $_c ?>-800/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-<?= $_c ?>-100 dark:bg-<?= $_c ?>-900/40 flex items-center justify-center">
                            <svg class="w-5 h-5 text-<?= $_c ?>-600 dark:text-<?= $_c ?>-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_d['icon'] ?>"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-<?= $_c ?>-900 dark:text-<?= $_c ?>-200"><?= $_d['name'] ?></h3>
                    </div>
                    <a href="<?= url('/docs/' . $_d['slug']) ?>" class="text-xs font-medium text-<?= $_c ?>-600 dark:text-<?= $_c ?>-400 hover:underline">Guida completa &rarr;</a>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <p class="text-sm text-slate-700 dark:text-slate-300"><?= htmlspecialchars($_d['text']) ?></p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($_d['tags'] as $_t): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-<?= $_c ?>-50 dark:bg-<?= $_c ?>-900/20 text-<?= $_c ?>-700 dark:text-<?= $_c ?>-300 border border-<?= $_c ?>-200 dark:border-<?= $_c ?>-800/50"><?= htmlspecialchars($_t) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Workflow consigliato -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            Un percorso possibile
        </h2>
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-700 dark:text-slate-300 text-sm mb-4">Non devi usarli tutti. Se vuoi un ordine da seguire, questo funziona:</p>
            <?php
            $_moSteps = [
                ['emerald', 'AI Audit', 'Controlla che il sito non abbia errori tecnici gravi'],
                ['purple', 'AI Keywords', 'Scopri cosa cercano i tuoi clienti'],
                ['amber', 'AI Writer', 'Scrivi i contenuti per quelle ricerche'],
                ['blue', 'AI Rank', 'Guarda nel tempo se stai salendo su Google'],
                ['indigo', 'AI GEO Audit', 'Verifica se ChatGPT e Gemini ti consigliano e cosa fare per farti citare'],
            ];
            ?>
            <div class="space-y-4">
                <?php foreach ($_moSteps as $_i => [$_c, $_n, $_t]): ?>
                <div class="flex items-start gap-3">
                    <span class="shrink-0 w-7 h-7 rounded-full bg-<?= $_c ?>-500 text-white text-xs font-bold flex items-center justify-center"><?= $_i + 1 ?></span>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?= $_n ?></p>
                        <p class="text-sm text-slate-600 dark:text-slate-400"><?= htmlspecialchars($_t) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Tips -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            Suggerimenti
        </h2>
        <div class="space-y-3">
            <div class="flex items-start gap-4 p-5 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-blue-900 dark:text-blue-200 text-sm">Inizia con un progetto</h3>
                    <p class="mt-1 text-sm text-blue-800 dark:text-blue-300/80">Ogni modulo lavora dentro un progetto, cioè un sito. Crea il primo progetto da "Progetti" e attiva lì i moduli che ti servono.</p>
                </div>
            </div>
            <div class="flex items-start gap-4 p-5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-medium text-amber-900 dark:text-amber-200 text-sm">Tieni d'occhio i crediti</h3>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/80">Le operazioni con l'AI consumano crediti. Il costo lo vedi prima di partire e il saldo è sempre nella barra in alto. <a href="<?= url('/docs/credits') ?>" class="underline hover:no-underline">Come funzionano i crediti</a>.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Navigation -->
    <div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
        <a href="<?= url('/docs/getting-started') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Primi Passi
        </a>
        <a href="<?= url('/docs/ai-content') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
            AI Writer
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

</div>
