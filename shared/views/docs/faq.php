<?php
// Numeri letti dalle impostazioni vere, mai scritti a mano
$_faqFmt = fn(float $n): string => rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
$_faqCr = fn(float $n): string => $_faqFmt($n) . ($n == 1 ? ' credito' : ' crediti');
$_faqBonus = \Core\Credits::signupBonus();
$_faqArticle = \Core\Credits::getCost('serp_extraction', 'ai-content', 3)
    + \Core\Credits::getCost('content_scrape', 'ai-content', 1)
    + \Core\Credits::getCost('brief_generation', 'ai-content', 3)
    + \Core\Credits::getCost('article_generation', 'ai-content', 10)
    + \Core\Credits::getCost('cover_image_generation', 'ai-content', 3);
$_faqWords = (int) \Core\ModuleLoader::getSetting('ai-content', 'default_word_count', 1500);
$_faqCrawl = \Core\Credits::getCost('crawl_per_page', 'seo-audit', 0.1);
$_faqActionPlan = \Core\Credits::getCost('action_plan_generate', 'seo-audit', 15);
$_faqRank = \Core\Credits::getCost('rank_check', 'seo-tracking', 1);
$_faqGeo = \Core\Credits::getCost('collect_response', 'ai-reputation', 1);
$_faqGeoBrief = \Core\Credits::getCost('action_brief', 'ai-reputation', 1);
$_faqLink = 'text-primary-600 dark:text-primary-400 hover:underline';
$_faqStrong = 'text-slate-900 dark:text-white';

// [categoria, icona, [[domanda, risposta HTML], ...]]
$_faqSections = [
    ['Generali', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', [
        ['Cos\'è Ainstein?',
            'Ainstein è una piattaforma che ti aiuta a farti trovare su Google e nelle risposte delle AI. Ha 5 moduli: <strong class="' . $_faqStrong . '">AI Writer</strong> scrive articoli e meta tag; <strong class="' . $_faqStrong . '">AI Keywords</strong> trova le parole che cercano i clienti; <strong class="' . $_faqStrong . '">AI Audit</strong> controlla il sito e trova i problemi tecnici; <strong class="' . $_faqStrong . '">AI Rank</strong> segue le tue posizioni su Google; <strong class="' . $_faqStrong . '">AI GEO Audit</strong> ti dice se ChatGPT e Gemini consigliano la tua attività e cosa fare per farti citare.'],
        ['Devo essere un esperto di SEO?',
            'No. Ainstein è pensato anche per chi ha una piccola attività, un blog o un piccolo negozio online. Ogni modulo ti guida passo per passo e ti spiega cosa fare in parole semplici. Se non sai da dove partire, inizia dalla scansione dell\'AI Audit e dal Controllo tecnico dell\'AI GEO Audit.'],
        ['Serve un account?',
            'Sì. La registrazione è gratuita, non chiede la carta di credito e ti dà <strong class="' . $_faqStrong . '">' . $_faqBonus . ' crediti in regalo</strong>, una volta sola, per provare i moduli. Puoi creare l\'account dalla pagina <a href="' . url('/register') . '" class="' . $_faqLink . '">Registrazione</a>.'],
        ['In che lingua è la piattaforma?',
            'L\'interfaccia è in italiano. AI Writer può scrivere articoli anche in inglese, tedesco, francese e spagnolo: la lingua si sceglie nelle impostazioni del progetto.'],
        ['Che intelligenza artificiale usa?',
            'Ainstein usa soprattutto Claude di Anthropic per analizzare e scrivere. L\'AI GEO Audit fa invece le domande direttamente a ChatGPT e Gemini, per vedere cosa rispondono davvero ai tuoi clienti.'],
    ]],
    ['Crediti e piani', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', [
        ['Posso provare gratis?',
            'Sì: all\'iscrizione ricevi ' . $_faqBonus . ' crediti in regalo, una volta sola. Non c\'è un piano gratuito e non c\'è una ricarica mensile gratuita. Per capire cosa puoi fare con i crediti di benvenuto guarda la pagina <a href="' . url('/docs/credits') . '" class="' . $_faqLink . '">Sistema Crediti</a>.'],
        ['Come attivo un piano o aggiungo crediti?',
            'Per ora non c\'è il pagamento online. Scrivi a <a href="mailto:info@ainstein.it" class="' . $_faqLink . '">info@ainstein.it</a> dicendo quale piano ti interessa: lo attiviamo noi a mano sul tuo account. I piani e i prezzi sono nella pagina <a href="' . url('/docs/credits') . '" class="' . $_faqLink . '">Sistema Crediti</a>.'],
        ['Quanto costa un articolo?',
            'Un articolo completo con copertina costa circa <strong class="' . $_faqStrong . '">' . $_faqCr($_faqArticle) . '</strong>: lettura dei risultati Google, lettura di una pagina concorrente, scaletta, articolo e immagine. Prima di ogni passaggio vedi il costo.'],
        ['Cosa è gratis?',
            'Guardare i risultati, consultare i report ed esportare non costa crediti. Sono gratuiti anche ' . ($_faqCrawl <= 0 ? 'la scansione del sito con l\'AI Audit, ' : '') . 'il Controllo tecnico dell\'AI GEO Audit e il Controllo veloce di AI Keywords.'],
    ]],
    ['AI Writer', 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', [
        ['Quanto è lungo un articolo?',
            'Di solito intorno alle ' . number_format($_faqWords, 0, ',', '.') . ' parole. La lunghezza dipende dall\'argomento e dalla scaletta: prima di scrivere puoi rivedere la scaletta, togliere o aggiungere sezioni.'],
        ['Posso pubblicare direttamente su WordPress?',
            'Sì. Collega il tuo sito WordPress dalla sezione WordPress del progetto: l\'articolo viene inviato con titolo, testo formattato, meta description e immagine di copertina.'],
        ['L\'AI copia i contenuti degli altri siti?',
            'No. L\'AI legge le pagine che sono già su Google per capire cosa cerca chi fa quella ricerca e quali argomenti trattare, ma scrive un testo <strong class="' . $_faqStrong . '">nuovo</strong>. Rileggi sempre l\'articolo prima di pubblicarlo e aggiungi la tua esperienza: è quello che lo rende davvero tuo.'],
    ]],
    ['AI Keywords', 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', [
        ['Cos\'è una keyword e perché mi serve?',
            'Una keyword (parola chiave) è quello che una persona scrive su Google, per esempio "idraulico Napoli" o "scarpe da trail donna". AI Keywords ti mostra quali ricerche fanno i tuoi clienti e quante volte al mese, così scrivi pagine e articoli su cose che le persone cercano davvero.'],
        ['Che differenza c\'è tra Ricerca guidata, Architettura sito e Piano editoriale?',
            'La <strong class="' . $_faqStrong . '">Ricerca guidata</strong> raccoglie le keyword e le raggruppa per argomento. L\'<strong class="' . $_faqStrong . '">Architettura sito</strong> ti propone quali pagine creare (utile per un sito nuovo o da rifare). Il <strong class="' . $_faqStrong . '">Piano editoriale</strong> ti dice quali articoli scrivere e in che ordine. Il <strong class="' . $_faqStrong . '">Controllo veloce</strong> controlla una singola parola, gratis.'],
    ]],
    ['AI Audit', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', [
        ['Cosa controlla l\'AI Audit?',
            'Visita il sito pagina per pagina, come fa Google, e cerca i problemi tecnici: titoli e descrizioni mancanti o doppi, link rotti, immagini senza testo alternativo, pagine che Google non può indicizzare. Ti dà un punteggio di salute e ordina i problemi per gravità, così sai cosa sistemare prima.'],
        ['Quanto costa l\'AI Audit?',
            ($_faqCrawl <= 0
                ? 'La scansione del sito è <strong class="' . $_faqStrong . '">gratuita</strong>.'
                : 'La scansione costa ' . $_faqCr($_faqCrawl) . ' per pagina.')
            . ' Il Piano d\'Azione AI, che trasforma i problemi in interventi concreti pagina per pagina, costa ' . $_faqCr($_faqActionPlan) . '.'],
    ]],
    ['AI Rank', 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z', [
        ['Cosa fa AI Rank?',
            'Ti dice in che posizione sei su Google per le parole chiave che scegli e come cambia nel tempo. Ogni controllo che lanci tu costa ' . $_faqCr($_faqRank) . ' per keyword. Puoi aggiungere le keyword a mano, da file CSV o prenderle da Google Search Console.'],
        ['Come funziona il collegamento a Google Search Console?',
            'Ti colleghi con il tuo account Google in modo sicuro (OAuth) e scegli il sito. Ainstein importa le ricerche per cui il sito compare su Google, con clic, visualizzazioni e posizione media. Search Console è gratuita: se non l\'hai ancora attivata per il tuo sito, conviene farlo.'],
    ]],
    ['AI GEO Audit', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', [
        ['A cosa serve l\'AI GEO Audit?',
            'Sempre più persone chiedono a ChatGPT e Gemini "a chi mi rivolgo per..." invece di cercare su Google. L\'AI GEO Audit fa alle AI le domande che farebbero i tuoi clienti e ti mostra se consigliano te, chi consigliano al posto tuo e quali siti usano come fonte. Poi ti dà un piano: articoli da pubblicare, dati da chiarire sul sito, correzioni tecniche. Il piano si esporta in PDF.'],
        ['Che differenza c\'è tra audit d\'acquisto e audit di reputazione?',
            'L\'<strong class="' . $_faqStrong . '">audit d\'acquisto</strong> usa le domande di chi cerca il tuo servizio e non ti conosce ancora: le AI fanno il tuo nome o quello dei concorrenti? L\'<strong class="' . $_faqStrong . '">audit di reputazione</strong> usa le domande su di te: sei affidabile, ci sono dubbi o informazioni sbagliate? Per un\'attività di solito conviene partire dall\'audit d\'acquisto.'],
        ['Quanto costa l\'AI GEO Audit?',
            'Il <strong class="' . $_faqStrong . '">Controllo tecnico</strong>, che verifica se le AI riescono a leggere il tuo sito, è gratuito. Ogni risposta raccolta (una domanda fatta a una AI) costa ' . $_faqCr($_faqGeo) . ': per esempio 30 domande a ChatGPT e Gemini sono 60 risposte, cioè ' . $_faqCr(60 * $_faqGeo) . '. La scheda operativa di un intervento costa ' . $_faqCr($_faqGeoBrief) . '. Prima di avviare vedi sempre il totale.'],
    ]],
    ['Condivisione e collaborazione', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', [
        ['Come condivido un progetto con un collaboratore?',
            'Dalla dashboard del progetto vai su <strong class="' . $_faqStrong . '">Impostazioni &rarr; Condivisione</strong>. Inserisci l\'email della persona, scegli il ruolo (Editor o Visualizzatore) e i moduli a cui avrà accesso. Se è già registrata riceve una notifica nell\'applicazione, altrimenti un\'email di invito.'],
        ['Dove vedo i progetti condivisi con me?',
            'Nella pagina <strong class="' . $_faqStrong . '">Progetti</strong> (con il badge "condiviso") e nella pagina di ogni modulo a cui hai accesso. Ci lavori come sui tuoi progetti, in base al ruolo che ti è stato dato.'],
        ['Chi paga i crediti nei progetti condivisi?',
            'Sempre il <strong class="' . $_faqStrong . '">proprietario</strong> del progetto. I collaboratori non usano i propri crediti quando lavorano su un progetto condiviso con loro.'],
    ]],
    ['Tecnico', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z', [
        ['I miei dati sono al sicuro?',
            'Le comunicazioni passano su connessione HTTPS cifrata, le password sono salvate in forma protetta e i dati dei tuoi progetti li vedi solo tu e le persone con cui li condividi. Per i dettagli leggi la <a href="' . url('/docs/privacy') . '" class="' . $_faqLink . '">Privacy</a>.'],
        ['Posso esportare i miei dati?',
            'Sì. Puoi esportare in CSV le keyword di AI Keywords, i problemi trovati dall\'AI Audit e i dati di AI Rank. Il piano dell\'AI GEO Audit si scarica in PDF. Gli articoli di AI Writer li copi o li pubblichi su WordPress.'],
    ]],
];
?>
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
        Le risposte alle domande più comuni su Ainstein. Se non trovi quello che cerchi, scrivici a <a href="mailto:info@ainstein.it" class="text-primary-600 dark:text-primary-400 hover:underline">info@ainstein.it</a>.
    </p>
</div>

<div x-data="{ open: null }">
    <?php $_faqN = 0; foreach ($_faqSections as $_faqI => [$_faqTitle, $_faqIcon, $_faqItems]): ?>
    <section class="<?= $_faqI === array_key_last($_faqSections) ? 'mb-8' : 'mb-10' ?>">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_faqIcon ?>"/>
            </svg>
            <?= htmlspecialchars($_faqTitle) ?>
        </h2>

        <div class="space-y-3">
            <?php foreach ($_faqItems as [$_faqQ, $_faqA]): $_faqN++; ?>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 overflow-hidden">
                <button @click="open === <?= $_faqN ?> ? open = null : open = <?= $_faqN ?>"
                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($_faqQ) ?></span>
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': open === <?= $_faqN ?> }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === <?= $_faqN ?>" x-cloak x-collapse>
                    <div class="px-5 pb-4 text-sm text-slate-600 dark:text-slate-400">
                        <?= $_faqA ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>

<!-- CTA Supporto -->
<div class="mt-12 p-6 sm:p-8 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-center">
    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Non hai trovato la risposta?</h2>
    <p class="text-slate-600 dark:text-slate-400 mb-6">Scrivici e ti rispondiamo il prima possibile.</p>
    <a href="mailto:info@ainstein.it" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-600 transition-colors shadow-sm">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        Scrivici
    </a>
</div>
