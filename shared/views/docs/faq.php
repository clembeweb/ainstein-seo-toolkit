<!-- Breadcrumb -->
<nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8">
    <a href="<?= url('/docs') ?>" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Documentazione</a>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-slate-900 dark:text-white font-medium">FAQ</span>
</nav>

<!-- Header -->
<div class="mb-10">
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">Domande Frequenti</h1>
    <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
        Trova risposte alle domande piu comuni su Ainstein. Se non trovi quello che cerchi, contattaci a <a href="mailto:supporto@ainstein.it" class="text-primary-600 dark:text-primary-400 hover:underline">supporto@ainstein.it</a>.
    </p>
</div>

<div x-data="{ open: null }">

    <!-- Categoria: Generali -->
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Generali
        </h2>

        <div class="space-y-3">
            <!-- FAQ 1 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 1 ? open = null : open = 1"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Cos'è Ainstein?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 1 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 1" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Ainstein è una piattaforma SEO completa potenziata dall'intelligenza artificiale. Offre 7 moduli integrati che coprono ogni aspetto della strategia SEO: dalla generazione di contenuti ottimizzati (AI Content Generator) all'audit tecnico del sito (SEO Audit), dal monitoraggio delle posizioni (SEO Tracking) alla ricerca keyword (Keyword Research), dall'analisi dei link interni (Internal Links), all'ottimizzazione delle campagne Google Ads (Ads Analyzer), fino alla creazione di contenuti HTML per pagine esistenti (Content Creator).
                    </div>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 2 ? open = null : open = 2"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">È necessario un account per usare Ainstein?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 2 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 2" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Si, è necessario creare un account per accedere a tutte le funzionalità della piattaforma. La registrazione è completamente gratuita e include 50 crediti omaggio per iniziare a esplorare i moduli. Non è richiesta alcuna carta di credito per la registrazione. Puoi creare il tuo account dalla pagina <a href="<?= url('/register') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Registrazione</a>.
                    </div>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 3 ? open = null : open = 3"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">In che lingua è disponibile la piattaforma?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 3 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 3" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        L'interfaccia di Ainstein è interamente in italiano. Tuttavia, la generazione di contenuti tramite AI Content Generator supporta qualsiasi lingua: puoi configurare la lingua desiderata nelle impostazioni del progetto. L'analisi SEO e il monitoraggio funzionano indipendentemente dalla lingua del sito analizzato.
                    </div>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 4 ? open = null : open = 4"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Come funziona l'intelligenza artificiale?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 4 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 4" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Ainstein utilizza Claude AI di Anthropic come motore principale per l'analisi e la generazione di contenuti. L'intelligenza artificiale viene impiegata per analizzare la SERP, generare articoli SEO ottimizzati, creare brief strategici, identificare problemi tecnici, suggerire ottimizzazioni e molto altro. Ogni operazione AI è progettata con prompt specifici per il contesto SEO, garantendo risultati accurati e professionali.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categoria: Crediti e Abbonamenti -->
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Crediti e Abbonamenti
        </h2>

        <div class="space-y-3">
            <!-- FAQ 5 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 5 ? open = null : open = 5"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Quanto costano i crediti?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 5 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 5" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Il costo dei crediti dipende dal piano di abbonamento scelto. Sono disponibili piani mensili e annuali con diversi livelli di crediti inclusi. I piani annuali offrono un risparmio significativo rispetto ai piani mensili. Per i dettagli aggiornati, consulta la nostra pagina prezzi.
                    </div>
                </div>
            </div>

            <!-- FAQ 6 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 6 ? open = null : open = 6"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">I crediti scadono?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 6 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 6" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        I crediti inclusi nel piano di abbonamento si rinnovano mensilmente: il saldo viene ricaricato all'inizio di ogni periodo di fatturazione. I crediti bonus, come quelli ricevuti alla registrazione o assegnati dall'amministratore, <strong class="text-slate-900 dark:text-white">non scadono mai</strong> e restano disponibili fino al loro utilizzo.
                    </div>
                </div>
            </div>

            <!-- FAQ 7 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 7 ? open = null : open = 7"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Posso provare gratis?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 7 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 7" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Assolutamente si! Alla registrazione ricevi 50 crediti gratuiti, sufficienti per provare tutti i moduli della piattaforma. Puoi ad esempio generare un articolo completo, eseguire un audit del sito, o analizzare una campagna Google Ads. Non è richiesta alcuna carta di credito. Per informazioni sui costi di ogni operazione, consulta la pagina <a href="<?= url('/docs/credits') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Sistema Crediti</a>.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categoria: AI Content -->
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            AI Content Generator
        </h2>

        <div class="space-y-3">
            <!-- FAQ 8 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 8 ? open = null : open = 8"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Quanto è lungo un articolo generato?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 8 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 8" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        La lunghezza tipica di un articolo generato va da 1.500 a 3.000 parole, a seconda della complessità dell'argomento e delle impostazioni del progetto. Puoi configurare la lunghezza desiderata nelle impostazioni del modulo. L'AI si adatta automaticamente per coprire l'argomento in modo esaustivo, seguendo la struttura del brief strategico generato dall'analisi SERP.
                    </div>
                </div>
            </div>

            <!-- FAQ 9 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 9 ? open = null : open = 9"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Posso pubblicare direttamente su WordPress?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 9 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 9" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Si, Ainstein supporta l'integrazione con WordPress. Una volta generato l'articolo, puoi pubblicarlo direttamente sul tuo sito WordPress configurando le credenziali API nelle impostazioni del progetto. L'articolo viene inviato completo di titolo, contenuto formattato in HTML, meta description e immagine di copertina.
                    </div>
                </div>
            </div>

            <!-- FAQ 10 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 10 ? open = null : open = 10"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">L'AI copia contenuti da altri siti?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 10 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 10" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        No, assolutamente no. L'AI analizza i contenuti presenti nella SERP per comprendere l'intento di ricerca, la struttura degli argomenti e gli aspetti da coprire, ma genera sempre contenuti <strong class="text-slate-900 dark:text-white">completamente originali</strong>. Il brief strategico guida la generazione fornendo indicazioni su temi e struttura, ma il testo prodotto è unico e non contiene copie o parafrasi da fonti esistenti.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categoria: Content Creator -->
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            Content Creator
        </h2>

        <div class="space-y-3">
            <!-- FAQ 15 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 15 ? open = null : open = 15"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Qual è la differenza tra Content Creator e AI Content Generator?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 15 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 15" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Content Creator genera il body HTML di pagine esistenti (prodotti, categorie, servizi), mentre AI Content Generator crea articoli blog completi da zero con analisi SERP, brief strategico e immagine di copertina.
                    </div>
                </div>
            </div>

            <!-- FAQ 16 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 16 ? open = null : open = 16"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Quali CMS sono supportati?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 16 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 16" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        WordPress (tramite plugin seo-toolkit-connector), Shopify (API nativa), PrestaShop (plugin), Magento 2 (estensione) e qualsiasi CMS tramite API personalizzata. Per WordPress, PrestaShop e Magento è necessario installare il plugin dedicato.
                    </div>
                </div>
            </div>

            <!-- FAQ 17 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 17 ? open = null : open = 17"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Posso importare keyword da Keyword Research?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 17 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 17" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Sì, se hai usato la modalità "Architettura Sito" nel modulo Keyword Research, puoi inviare i risultati direttamente al Content Creator. Le keyword principali, secondarie e l'intent vengono trasferiti automaticamente.
                    </div>
                </div>
            </div>

            <!-- FAQ 18 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 18 ? open = null : open = 18"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Quanto costa generare contenuti?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 18 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 18" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Lo scraping opzionale della pagina costa 1 credito, la generazione AI del contenuto costa 3 crediti per URL. L'esportazione CSV e il push al CMS sono gratuiti.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categoria: SEO Tracking -->
    <section class="mb-10">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            SEO Tracking
        </h2>

        <div class="space-y-3">
            <!-- FAQ 11 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 11 ? open = null : open = 11"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Quante keyword posso monitorare?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 11 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 11" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Non c'è un limite al numero di keyword che puoi monitorare per progetto. Puoi importare keyword manualmente, da file CSV, dalla sitemap del sito, oppure sincronizzarle automaticamente da Google Search Console. Il modulo traccia automaticamente le variazioni di posizione, il traffico stimato e le tendenze nel tempo per ogni keyword.
                    </div>
                </div>
            </div>

            <!-- FAQ 12 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 12 ? open = null : open = 12"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Come funziona l'integrazione Google Search Console?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 12 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 12" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        L'integrazione con Google Search Console avviene tramite connessione OAuth2 sicura. Una volta collegato il tuo account Google, Ainstein può importare automaticamente le keyword per cui il tuo sito appare nei risultati di ricerca, insieme a dati su impressioni, clic, CTR e posizione media. La sincronizzazione puo essere eseguita manualmente o programmata automaticamente per mantere i dati aggiornati.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categoria: Tecnico -->
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Tecnico
        </h2>

        <div class="space-y-3">
            <!-- FAQ 13 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 13 ? open = null : open = 13"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">I miei dati sono al sicuro?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 13 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 13" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Si, la sicurezza dei tuoi dati è una priorità assoluta. Tutte le comunicazioni avvengono tramite connessione HTTPS crittografata. Le password sono protette con hashing sicuro, le chiavi API sono conservate in modo protetto nel database e i dati dei tuoi progetti sono accessibili solo dal tuo account. La piattaforma è conforme al GDPR per la protezione dei dati personali.
                    </div>
                </div>
            </div>

            <!-- FAQ 14 -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === 14 ? open = null : open = 14"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">Posso esportare i miei dati?</span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === 14 }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === 14" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        Si, tutti i moduli di Ainstein supportano l'esportazione dei dati in formato CSV. Puoi esportare keyword, risultati di audit, report di tracking, analisi delle campagne e qualsiasi altro dato generato dalla piattaforma. Gli articoli generati possono essere scaricati in formato HTML o pubblicati direttamente su WordPress. I tuoi dati ti appartengono sempre.
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- CTA Supporto -->
<div class="mt-12 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-center">
    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Non hai trovato la risposta?</h2>
    <p class="text-slate-600 dark:text-slate-400 mb-6">Il nostro team di supporto è pronto ad aiutarti. Scrivici e ti risponderemo il prima possibile.</p>
    <a href="mailto:supporto@ainstein.it" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors shadow-sm">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        Contatta il supporto
    </a>
</div>