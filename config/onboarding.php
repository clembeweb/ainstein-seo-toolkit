<?php

/**
 * Configurazione step onboarding per la piattaforma Ainstein
 * Ogni step ha: title, description, icon (Heroicons path), tip, selector (CSS), position (bottom|top|right|left)
 * selector = null → tooltip centrato senza highlight (fallback)
 */

return [

    // ============================================================
    // WELCOME GLOBALE - Prima volta sulla piattaforma
    // ============================================================
    'welcome' => [
        'name' => 'Benvenuto su Ainstein',
        'steps' => [
            [
                'title' => 'Benvenuto su Ainstein',
                'description' => 'La tua suite SEO all-in-one potenziata dall\'intelligenza artificiale. Analizza, ottimizza e genera contenuti per il tuo sito web.',
                'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
                'tip' => 'Naviga tra i moduli usando il menu laterale',
                'selector' => '[data-tour-welcome="header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'I tuoi strumenti SEO',
                'description' => '6 strumenti professionali: genera contenuti AI, audit SEO, tracking posizioni, keyword research, link interni e analisi Google Ads.',
                'icon' => 'M11.42 15.17l-5.1-3.57m5.1 3.57l5.1-3.57m-5.1 3.57V21m5.1-6.83L21 9.34m0 0L15.52 5.1 11.42 2 7.32 5.1 1.82 9.34m19.18 0L21 15.17m-19.18-5.83L1.82 15.17',
                'tip' => 'Ogni modulo ha un tour dedicato che puoi rivedere dal menu laterale',
                'selector' => '[data-tour-welcome="modules"]',
                'position' => 'top',
            ],
            [
                'title' => 'Sistema crediti',
                'description' => 'Ogni operazione AI e analisi avanzata consuma crediti. Il saldo e visibile nella barra in alto. Navigazione, creazione progetti e import sono gratuiti.',
                'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
                'tip' => 'Il Quick Check delle keyword e la navigazione sono sempre gratuiti',
                'selector' => '[data-tour-welcome="credits"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Pronto per iniziare!',
                'description' => 'Scegli un modulo dal menu laterale per iniziare. Ogni modulo ha il suo tour guidato che ti accompagna passo passo.',
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
        'name' => 'AI Content Generator',
        'steps' => [
            [
                'title' => 'Genera contenuti SEO con AI',
                'description' => 'Crea articoli ottimizzati per i motori di ricerca partendo da una keyword. L\'AI analizza la SERP, genera un brief strategico e scrive un articolo completo.',
                'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10',
                'tip' => 'Scegli una delle 3 modalita qui sotto per iniziare',
                'selector' => '[data-tour="aic-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => '3 modalita di lavoro',
                'description' => 'Manuale: controllo totale con wizard 4 step. Automatico: elaborazione in bulk con coda. Meta Tags: ottimizzazione title e description di pagine esistenti.',
                'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
                'tip' => 'Per iniziare rapidamente, usa la modalita Manuale',
                'selector' => '[data-tour="aic-modes"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Azioni rapide',
                'description' => 'Gestisci i job in corso, configura WordPress e crea nuovi progetti con i bottoni in alto a destra.',
                'icon' => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z',
                'tip' => 'Collega WordPress per pubblicare direttamente dalla piattaforma',
                'selector' => '[data-tour="aic-quickactions"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Statistiche globali',
                'description' => 'Monitora le tue keyword, articoli generati e pubblicati. Le stats si aggiornano in tempo reale su tutti i progetti.',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'tip' => 'Le stats si aggiornano in tempo reale',
                'selector' => '[data-tour="aic-stats"]',
                'position' => 'top',
            ],
            [
                'title' => 'Costi operazioni',
                'description' => 'Articolo manuale/auto: ~15 crediti. Meta tags: ~3 crediti/pagina. Estrazione SERP: 2 crediti. Brief AI: 3 crediti. Immagine copertina: 5 crediti.',
                'icon' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
                'tip' => 'Il Quick Check delle keyword e sempre gratuito',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // SEO AUDIT
    // ============================================================
    'seo-audit' => [
        'name' => 'SEO Audit',
        'steps' => [
            [
                'title' => 'Analizza il tuo sito',
                'description' => 'Scansiona il tuo sito web per trovare problemi SEO: meta tag mancanti, heading duplicati, immagini senza alt, link rotti e molto altro.',
                'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
                'tip' => 'Crea il tuo primo audit inserendo l\'URL del sito',
                'selector' => '[data-tour="sa-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea un nuovo audit',
                'description' => 'Clicca su "Nuovo Audit" per iniziare. Scegli tra 3 preset: Veloce (100 pagine), Bilanciato (500 pagine) o Completo (2000 pagine).',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Inizia con il preset Veloce per un\'analisi rapida',
                'selector' => '[data-tour="sa-newaudit"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Health Score',
                'description' => 'Il punteggio da 0 a 100 indica la salute SEO del sito. I problemi sono organizzati per categoria: Meta Tag, Heading, Immagini, Link, Contenuto e Schema.',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'tip' => 'Clicca su ogni categoria per vedere i dettagli dei problemi trovati',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Action Plan AI',
                'description' => 'L\'AI analizza i problemi e crea un piano d\'azione prioritizzato. Per ogni fix vedi: difficolta stimata, impatto sul punteggio e tempo necessario.',
                'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z',
                'tip' => 'Spunta i fix completati per tracciare il progresso',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Google Search Console',
                'description' => 'Collega Google Search Console per dati reali: click, impressioni, CTR e posizione media. I dati vengono sincronizzati automaticamente.',
                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'tip' => 'La connessione GSC e opzionale ma consigliata per dati completi',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // SEO TRACKING
    // ============================================================
    'seo-tracking' => [
        'name' => 'SEO Tracking',
        'steps' => [
            [
                'title' => 'Monitora le tue posizioni',
                'description' => 'Traccia il ranking delle tue keyword su Google nel tempo. Visualizza trend, variazioni e confronta periodi per capire l\'andamento SEO.',
                'icon' => 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
                'tip' => 'Crea un progetto inserendo il dominio del tuo sito',
                'selector' => '[data-tour="st-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea un nuovo progetto',
                'description' => 'Clicca su "Nuovo Progetto" per aggiungere un sito. Potrai poi collegare Google Search Console per dati reali di click, impressioni e CTR.',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Vai nelle impostazioni del progetto per collegare GSC',
                'selector' => '[data-tour="st-newproject"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Aggiungi keyword',
                'description' => 'Inserisci le keyword manualmente o importa un CSV. Organizzale in gruppi tematici per un\'analisi piu strutturata.',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'Puoi aggiungere keyword in bulk incollando una lista',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Rank Check',
                'description' => 'Verifica le posizioni reali su Google (Desktop e Mobile). Il sistema controlla la SERP in tempo reale e salva lo storico.',
                'icon' => 'M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21l3.75-3.75',
                'tip' => 'Costa 1 credito per keyword verificata',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Alert e Report AI',
                'description' => 'Configura alert automatici per variazioni di posizione. Genera report AI con insight strategici e raccomandazioni.',
                'icon' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
                'tip' => 'Gli alert ti avvisano quando una keyword perde posizioni',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // KEYWORD RESEARCH
    // ============================================================
    'keyword-research' => [
        'name' => 'Keyword Research',
        'steps' => [
            [
                'title' => 'Ricerca keyword intelligente',
                'description' => 'Espandi le tue seed keyword con dati reali di volume, CPC e competition. L\'AI raggruppa le keyword in cluster semantici con intent e note strategiche.',
                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
                'tip' => 'Scegli una delle 3 modalita qui sotto per iniziare',
                'selector' => '[data-tour="kr-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Research Guidata',
                'description' => 'Inserisci da 1 a 5 seed keyword. Il sistema le espande in centinaia di keyword correlate, poi l\'AI le raggruppa in cluster semantici.',
                'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
                'tip' => 'Costa 2 crediti (< 100 keyword) o 5 crediti (> 100 keyword)',
                'selector' => '[data-tour="kr-guided"]',
                'position' => 'right',
            ],
            [
                'title' => 'Architettura Sito',
                'description' => 'Progetta la struttura del tuo sito basandoti su volumi di ricerca reali. L\'AI suggerisce nomi pagine, H1 e URL per ogni cluster.',
                'icon' => 'M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z',
                'tip' => 'Costa 5 crediti fissi, ideale per pianificare un nuovo sito',
                'selector' => '[data-tour="kr-architecture"]',
                'position' => 'left',
            ],
            [
                'title' => 'Piano Editoriale',
                'description' => 'Genera un piano editoriale mensile basato su keyword reali e analisi competitor SERP. L\'AI suggerisce articoli distribuiti per categoria e mese.',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'tip' => 'Costa 5 crediti. Esporta direttamente in AI Content!',
                'selector' => '[data-tour="kr-editorial"]',
                'position' => 'left',
            ],
            [
                'title' => 'Quick Check',
                'description' => 'Verifica istantanea di una singola keyword: volume, CPC, competition e keyword correlate. Nessun progetto necessario.',
                'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
                'tip' => 'Completamente gratuito! Nessun credito necessario',
                'selector' => '[data-tour="kr-quickcheck"]',
                'position' => 'right',
            ],
        ],
    ],

    // ============================================================
    // INTERNAL LINKS
    // ============================================================
    'internal-links' => [
        'name' => 'Internal Links',
        'steps' => [
            [
                'title' => 'Ottimizza i link interni',
                'description' => 'Analizza la struttura di linking interno del tuo sito. Trova link rotti, pagine orfane e opportunita di miglioramento per il link juice.',
                'icon' => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
                'tip' => 'Crea un progetto con l\'URL base del tuo sito',
                'selector' => '[data-tour="il-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea un nuovo progetto',
                'description' => 'Clicca su "Nuovo Progetto" per aggiungere un sito. Poi importa gli URL da sitemap, CSV o manualmente e avvia lo scraping.',
                'icon' => 'M12 4.5v15m7.5-7.5h-15',
                'tip' => 'L\'import da sitemap e il metodo piu veloce',
                'selector' => '[data-tour="il-newproject"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Analisi AI',
                'description' => 'L\'AI valuta ogni link con un punteggio di rilevanza (1-10) e qualita dell\'anchor text. Classifica il flusso del link juice.',
                'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z',
                'tip' => 'Costa 2 crediti per batch di link analizzati',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Pagine orfane',
                'description' => 'Identifica le pagine che non ricevono nessun link interno. Queste pagine sono "invisibili" per i motori di ricerca.',
                'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
                'tip' => 'Aggiungi link interni verso le pagine orfane per migliorare l\'indicizzazione',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Report e snapshot',
                'description' => 'Consulta report su anchor text, distribuzione juice e pagine orfane. Crea snapshot per confrontare la struttura nel tempo.',
                'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
                'tip' => 'Gli snapshot sono utili per misurare i miglioramenti nel tempo',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],

    // ============================================================
    // ADS ANALYZER
    // ============================================================
    'ads-analyzer' => [
        'name' => 'Ads Analyzer',
        'steps' => [
            [
                'title' => 'Ottimizza le campagne Google Ads',
                'description' => 'Analizza i search terms delle tue campagne e trova automaticamente le keyword negative che sprecano budget pubblicitario.',
                'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'tip' => 'Esporta i search terms da Google Ads come CSV',
                'selector' => '[data-tour="aa-header"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Crea un progetto',
                'description' => 'Crea un nuovo progetto e carica il CSV dei search terms. Il sistema identifica automaticamente gli Ad Group e i termini di ricerca.',
                'icon' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5',
                'tip' => 'In Google Ads vai su Keyword > Search Terms > Download',
                'selector' => '[data-tour="aa-newproject"]',
                'position' => 'bottom',
            ],
            [
                'title' => 'Contesto business',
                'description' => 'Descrivi la tua attivita per aiutare l\'AI a capire quali keyword sono irrilevanti. Puoi anche estrarre il contesto dalle landing page.',
                'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                'tip' => 'Piu dettagliato e il contesto, migliori saranno i risultati',
                'selector' => null,
                'position' => 'bottom',
            ],
            [
                'title' => 'Risultati e export',
                'description' => 'L\'AI categorizza le keyword negative per priorita. Seleziona quelle da escludere e esporta in formato Google Ads Editor.',
                'icon' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3',
                'tip' => 'Usa il formato Google Ads Editor per importare direttamente le negative',
                'selector' => null,
                'position' => 'bottom',
            ],
        ],
    ],
];
