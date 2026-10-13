<?php

/**
 * Configurazione step onboarding per la piattaforma Ainstein
 * Ogni step ha: title, description, icon (Heroicons path), tip, selector (CSS), position (bottom|top|right|left)
 * selector = null → tooltip centrato senza highlight (fallback)
 * Prezzi: letti dalle impostazioni (Credits::getCost / ModuleLoader::getSetting), mai scritti a mano.
 */

$cr = function (float $v): string {
    $n = rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',');
    return $n . ' credit' . ($v == 1 ? 'o' : 'i');
};
$cost = fn(string $op, string $module, float $default) => $cr((float) \Core\Credits::getCost($op, $module, $default));
$krCost = fn(string $key, float $default) => $cr((float) (\Core\ModuleLoader::getSetting('keyword-research', $key) ?? $default));
$articleTotal = (float) \Core\Credits::getCost('serp_extraction', 'ai-content', 3) + (float) \Core\Credits::getCost('content_scrape', 'ai-content', 1)
    + (float) \Core\Credits::getCost('brief_generation', 'ai-content', 3) + (float) \Core\Credits::getCost('article_generation', 'ai-content', 10)
    + (float) \Core\Credits::getCost('cover_image_generation', 'ai-content', 3);

return [

    // ============================================================
    // WELCOME GLOBALE - Prima volta sulla piattaforma
    // ============================================================
    'welcome' => [
        'name' => 'Benvenuto su Ainstein',
        'steps' => [
            [
                'title' => 'Benvenuto su Ainstein',
                'description' => 'Strumenti con l\'intelligenza artificiale per far trovare il tuo sito su Google e nelle risposte di ChatGPT. Non serve essere esperti SEO: ogni strumento ti dice cosa fare.',
                'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
                'tip' => 'Naviga tra i moduli usando il menu laterale',
                'selector' => '[data-tour-welcome="header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'I tuoi 5 strumenti',
                'description' => 'AI Audit controlla il sito, AI Keywords trova cosa cerca la gente, AI Writer scrive gli articoli, AI Rank segue le posizioni su Google, AI GEO Audit ti dice se ChatGPT e Gemini ti consigliano.',
                'icon' => 'M11.42 15.17l-5.1-3.57m5.1 3.57l5.1-3.57m-5.1 3.57V21m5.1-6.83L21 9.34m0 0L15.52 5.1 11.42 2 7.32 5.1 1.82 9.34m19.18 0L21 15.17m-19.18-5.83L1.82 15.17',
                'tip' => 'Ogni modulo ha un tour dedicato che puoi rivedere dal menu laterale',
                'selector' => '[data-tour-welcome="modules"]',
                'position' => 'top',
            ],
            [
                'title' => 'Sistema crediti',
                'description' => 'Le operazioni con l\'AI consumano crediti: il saldo è nella barra in alto e il costo lo vedi prima di partire. Creare progetti, la scansione dell\'AI Audit e il Quick Check delle keyword sono gratis.',
                'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
                'tip' => 'Ti servono più crediti? Scrivi a info@ainstein.it',
                'selector' => '[data-tour-welcome="credits"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Pronto per iniziare!',
                'description' => 'Consiglio: crea un progetto con il tuo sito e parti da AI Audit, che è gratis. Ogni strumento ha il suo tour guidato.',
                'icon' => 'M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
                'tip' => 'Puoi rivedere i tour in qualsiasi momento cliccando ? nel menu',
                'selector' => '[data-tour-welcome="sidebar"]',
                'position' => 'right',
            ],
        ],
    ],

    // ============================================================
    // AI CONTENT GENERATOR
    // ============================================================
    'ai-content' => [
        'name' => 'AI Writer',
        'steps' => [
            [
                'title' => 'Articoli per il tuo blog',
                'description' => 'Parti da una ricerca che fanno i tuoi clienti su Google. L\'AI guarda chi compare nei primi risultati, prepara una scaletta e scrive l\'articolo completo, con copertina.',
                'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
                'tip' => 'Scegli una delle 2 modalità qui sotto per iniziare',
                'selector' => '[data-tour="aic-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => '2 modalità di lavoro',
                'description' => 'Articoli manuali: scegli tu la ricerca e controlli ogni passaggio. Articoli automatici: metti in coda le ricerche e gli articoli escono da soli nei giorni scelti. Per riscrivere title e description delle pagine esistenti attiva il tipo Meta tag sul progetto.',
                'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
                'tip' => 'Per il primo articolo usa la modalità manuale',
                'selector' => '[data-tour="aic-modes"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Azioni rapide',
                'description' => 'Collega il tuo sito WordPress per pubblicare gli articoli direttamente, oppure crea un nuovo progetto.',
                'icon' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z',
                'tip' => 'Collega WordPress per pubblicare direttamente dalla piattaforma',
                'selector' => '[data-tour="aic-quickactions"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Statistiche globali',
                'description' => 'Quante ricerche hai in lista, quanti articoli sono stati scritti e quanti pubblicati, su tutti i progetti.',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'tip' => 'I numeri si aggiornano a ogni articolo',
                'selector' => '[data-tour="aic-stats"]',
                'position' => 'top',
            ],
            [
                'title' => 'Costi operazioni',
                'description' => 'Un articolo completo con copertina costa ' . $cr($articleTotal) . ' (ricerca su Google ' . $cost('serp_extraction', 'ai-content', 3) . ', lettura delle pagine ' . $cost('content_scrape', 'ai-content', 1) . ', scaletta ' . $cost('brief_generation', 'ai-content', 3) . ', articolo ' . $cost('article_generation', 'ai-content', 10) . ', copertina ' . $cost('cover_image_generation', 'ai-content', 3) . '). Meta tag: ' . $cost('meta_generate', 'ai-content', 2) . ' a pagina.',
                'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
                'tip' => 'Il costo esatto lo vedi sempre prima di confermare',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // SEO AUDIT
    // ============================================================
    'seo-audit' => [
        'name' => 'AI Audit',
        'steps' => [
            [
                'title' => 'Analizza il tuo sito',
                'description' => 'Controlla il tuo sito come lo vede Google: titoli mancanti o doppi, immagini senza descrizione, link rotti, pagine nascoste e molto altro. La scansione è gratis.',
                'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
                'tip' => 'Crea il primo progetto con l\'indirizzo del tuo sito',
                'selector' => '[data-tour="sa-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea un nuovo progetto',
                'description' => 'Clicca su "Nuovo Progetto" per iniziare. Poi scegli quanto a fondo scansionare: Veloce (100 pagine), Bilanciato (500) o Completo (2000).',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Per il primo controllo basta Veloce',
                'selector' => '[data-tour="sa-newaudit"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Punteggio di salute',
                'description' => 'Un voto da 0 a 100 sulla salute del sito. I problemi sono divisi per categoria (titoli, intestazioni, immagini, link, contenuti, dati strutturati) e ordinati per gravità.',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'tip' => 'Clicca su ogni categoria per vedere i dettagli dei problemi trovati',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Piano d\'azione AI',
                'description' => 'L\'AI legge i problemi trovati e ti dà la lista delle cose da sistemare, dalla più importante. Per ognuna vedi difficoltà, impatto e tempo stimato. Costa ' . $cost('action_plan_generate', 'seo-audit', 15) . '.',
                'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
                'tip' => 'Spunta i fix completati per tracciare il progresso',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'E poi?',
                'description' => 'Sistemati i problemi, rilancia la scansione e guarda il punteggio salire. Per seguire le posizioni su Google e i click reali usa AI Rank.',
                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'tip' => 'Puoi rilanciare la scansione quando vuoi, è gratis',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // SEO TRACKING
    // ============================================================
    'seo-tracking' => [
        'name' => 'AI Rank',
        'steps' => [
            [
                'title' => 'Monitora le tue posizioni',
                'description' => 'Segui dove compare il tuo sito su Google per le ricerche che contano, e come cambia nel tempo.',
                'icon' => 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
                'tip' => 'Crea un progetto inserendo il dominio del tuo sito',
                'selector' => '[data-tour="st-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea un nuovo progetto',
                'description' => 'Clicca su "Nuovo Progetto" per aggiungere il tuo sito. Se colleghi Google Search Console vedi anche click e impressioni reali.',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Vai nelle impostazioni del progetto per collegare GSC',
                'selector' => '[data-tour="st-newproject"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Aggiungi keyword',
                'description' => 'Scrivi le ricerche da seguire, incolla una lista o importa un CSV. Puoi raggrupparle per argomento.',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Con Search Console collegato le trovi già pronte',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Controllo posizioni',
                'description' => 'Controlla la posizione vera su Google, da computer e da telefono, e salva lo storico per vedere i miglioramenti.',
                'icon' => 'M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21l3.75-3.75',
                'tip' => 'Costa 1 credito per keyword verificata',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Quick Wins',
                'description' => 'La lista gratuita delle ricerche in cui sei vicino alla prima pagina: lì basta poco per salire. Su ogni pagina puoi chiedere all\'AI cosa migliorare (' . $cost('page_analysis', 'seo-tracking', 2) . ').',
                'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
                'tip' => 'Parti da qui: sono i miglioramenti più veloci',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Punto della situazione',
                'description' => 'Collega Google Analytics e Search Console: l\'AI confronta il periodo con quello precedente e con l\'anno prima e ti spiega da dove arriva il traffico, cosa cresce, cosa cala e cosa fare. Costa ' . $cost('situation_report', 'seo-tracking', 5) . '.',
                'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',
                'tip' => 'Lo trovi nella scheda "Punto della situazione" del progetto',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // KEYWORD RESEARCH
    // ============================================================
    'keyword-research' => [
        'name' => 'AI Keywords',
        'steps' => [
            [
                'title' => 'Ricerca keyword intelligente',
                'description' => 'Scopri cosa cerca la gente nel tuo settore, quante volte al mese, e trasformalo in pagine e articoli da scrivere.',
                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'tip' => 'Scegli una delle modalità qui sotto per iniziare',
                'selector' => '[data-tour="kr-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Research Guidata',
                'description' => 'Scrivi da 1 a 5 parole di partenza: il sistema trova centinaia di ricerche collegate e l\'AI le raggruppa per argomento.',
                'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
                'tip' => 'Costa ' . $krCost('cost_kr_ai_clustering', 3),
                'selector' => '[data-tour="kr-guided"]',
                'position' => 'right',
            ],
            [
                'title' => 'Architettura Sito',
                'description' => 'Progetta le pagine del sito partendo da cosa cerca la gente: l\'AI propone nome, titolo e indirizzo di ogni pagina.',
                'icon' => 'M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z',
                'tip' => 'Costa ' . $krCost('cost_kr_ai_architecture', 5) . ', ideale per un sito nuovo o da rifare',
                'selector' => '[data-tour="kr-architecture"]',
                'position' => 'left',
            ],
            [
                'title' => 'Piano Editoriale',
                'description' => 'Un calendario di articoli mese per mese, basato su ricerche reali. Ogni articolo indica la pagina del sito a cui collegarsi.',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'tip' => 'Costa ' . $krCost('cost_kr_editorial_plan', 5) . '. Poi passi gli articoli ad AI Writer',
                'selector' => '[data-tour="kr-editorial"]',
                'position' => 'left',
            ],
            [
                'title' => 'Quick Check',
                'description' => 'Controllo veloce di una sola ricerca: quante volte la cercano, quanto è contesa e le ricerche simili. Non serve un progetto.',
                'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
                'tip' => 'Gratis',
                'selector' => '[data-tour="kr-quickcheck"]',
                'position' => 'right',
            ],
        ],
    ],

    // ============================================================
    // AI GEO AUDIT
    // ============================================================
    'ai-reputation' => [
        'name' => 'AI GEO Audit',
        'steps' => [
            [
                'title' => 'Ti consigliano ChatGPT e Gemini?',
                'description' => 'Sempre più clienti chiedono alle AI dove comprare o chi chiamare. AI GEO Audit fa a ChatGPT e Gemini le domande dei tuoi clienti e ti dice se ti nominano e chi consigliano al posto tuo.',
                'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
                'tip' => 'Non serve essere esperti SEO',
                'selector' => '[data-tour="ar-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea il progetto',
                'description' => 'Clicca su "Nuovo Progetto", inserisci il nome e il sito della tua attività, poi attiva AI GEO Audit. L\'AI legge il sito e prepara il profilo: tu confermi o correggi ogni riga.',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Hai già un progetto? Usa "Attiva su un progetto"',
                'selector' => '[data-tour="ar-newproject"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Le domande e il run',
                'description' => 'Il modulo scrive le domande dei tuoi clienti e tu puoi modificarle. Prima di partire vedi quanti crediti servono: ogni risposta delle AI costa ' . $cost('collect_response', 'ai-reputation', 1) . '.',
                'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
                'tip' => 'Il controllo tecnico del sito è gratis',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Il report e il piano',
                'description' => 'Vedi in quante risposte ti nominano, chi consigliano al posto tuo e da quali siti prendono le informazioni. Poi il piano: articoli da pubblicare, dati da chiarire sul sito, correzioni tecniche. Lo esporti in PDF.',
                'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
                'tip' => 'Per ogni intervento puoi avere i testi pronti (' . $cost('action_brief', 'ai-reputation', 1) . ')',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

];
