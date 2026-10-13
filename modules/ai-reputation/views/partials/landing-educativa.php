<?php
// Sezione "Scopri cosa puoi fare" della pagina iniziale (pattern landing del CLAUDE.md, colore indigo).
// Esempi con un'attività inventata (mai dati dei clienti). Prezzi dalle impostazioni del modulo, mai scritti a mano.
$fmt = fn(float $v): string => rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',');
$respCost = (float) \Core\Credits::getCost('collect_response', 'ai-reputation', 1);
$briefCost = (float) \Core\Credits::getCost('action_brief', 'ai-reputation', 1);
$crediti = fn(float $v): string => $fmt($v) . ' credit' . ($v == 1 ? 'o' : 'i');
$exampleRun = (int) round(30 * 2 * $respCost);

$steps = [
    ['Racconta chi sei', 'Crei il progetto con il nome e il sito della tua attività. L\'AI legge il sito e prepara un profilo: cosa fai, dove, per chi. Tu confermi o correggi ogni riga, così il report parte da fatti veri.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
    ['Le domande dei tuoi clienti', 'Il modulo scrive le domande che un cliente farebbe a ChatGPT senza conoscerti, per esempio "dove compro una torta senza glutine a Torino?". Le puoi togliere, modificare o aggiungere. Poi le fa davvero a ChatGPT e Gemini.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>'],
    ['Leggi il report e agisci', 'Vedi in quante risposte ti nominano, chi consigliano al posto tuo e da quali siti prendono le informazioni. Ricevi un piano: articoli da pubblicare, dati da chiarire sul sito, correzioni tecniche. Il PDF lo condividi con chi ti segue il sito.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
];

$cards = [
    ['Audit d\'acquisto', 'Le domande di chi cerca quello che vendi, senza fare il tuo nome. Scopri se le AI ti consigliano o mandano i clienti altrove.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>'],
    ['Audit reputazione', 'Le domande di chi ti conosce già: "è affidabile?", "che recensioni ha?". Vedi cosa rispondono e se dicono cose sbagliate o vecchie.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>'],
    ['I dubbi delle AI', 'Se le AI scrivono "non trovo la partita IVA" o "non è chiaro dove si trova", ricevi i testi pronti da mettere sul sito per toglierli.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ['Controllo tecnico gratis', 'Verifica se il tuo sito blocca i lettori delle AI e se i dati principali (nome, indirizzo, telefono) sono scritti in modo che le AI li capiscano.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'],
    ['Articoli che rispondono', 'Le domande in cui non compari diventano pochi articoli mirati, scritti solo con i fatti che hai confermato. Niente promesse inventate.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'],
    ['Un PDF da condividere', 'Il piano degli interventi in un PDF ordinato: lo giri a chi ti fa il sito, al tuo consulente o lo tieni come lista delle cose da fare.',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'],
];

$faqs = [
    ['Devo essere un esperto SEO?', 'No. Il modulo è pensato anche per chi ha un piccolo sito, un blog o un e-commerce e vuole sapere se le AI lo consigliano. Il report dice cosa fare con parole semplici: quale articolo scrivere, quale dato aggiungere al sito, cosa correggere.'],
    ['Quanto costa?', 'Ogni risposta delle AI costa ' . $crediti($respCost) . '. Per esempio 30 domande fatte a ChatGPT e Gemini sono 60 risposte, cioè ' . $exampleRun . ' crediti. Prima di partire vedi la stima e puoi togliere domande. La scheda con i testi pronti per un intervento costa ' . $crediti($briefCost) . '. Il controllo tecnico è gratis.'],
    ['Quali AI interroga?', 'ChatGPT e Gemini, con la ricerca sul web attiva: le stesse risposte che vedrebbe un tuo cliente. Il report mostra anche i siti che le AI hanno usato come fonte.'],
    ['Che differenza c\'è tra audit d\'acquisto e audit reputazione?', 'L\'audit d\'acquisto fa le domande di chi cerca il tuo servizio senza conoscerti ("dove noleggio una barca a Sorrento?"): misura se ti consigliano. L\'audit reputazione fa le domande di chi ti conosce già ("è affidabile?"): misura cosa dicono di te. Nello stesso progetto puoi fare entrambi e confrontarli.'],
    ['Dopo gli interventi le AI mi consiglieranno?', 'Nessuno può garantirlo: le AI leggono il web e cambiano nel tempo. Contenuti chiari sul tuo sito e informazioni coerenti aumentano le probabilità di comparire. Dopo qualche settimana rilanci l\'audit e confronti i numeri.'],
    ['Funziona anche per una persona?', 'Sì. Al posto di un\'azienda puoi analizzare un professionista o un personaggio pubblico: l\'audit reputazione mostra cosa rispondono le AI su quel nome e separa gli omonimi.'],
];
?>

<!-- ═══════════ SEZIONE EDUCATIVA ═══════════ -->
<div class="relative my-16">
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div class="w-full h-px bg-gradient-to-r from-transparent via-indigo-400/50 to-transparent"></div>
    </div>
    <div class="relative flex justify-center">
        <span class="bg-white dark:bg-slate-900 px-6 text-sm font-medium text-slate-400 dark:text-slate-500 uppercase tracking-wider">Scopri cosa puoi fare</span>
    </div>
</div>

<!-- Hero Educativo -->
<div class="bg-gradient-to-br from-indigo-50/50 via-white to-violet-50/30 dark:from-indigo-950/20 dark:via-slate-900 dark:to-violet-950/10 rounded-2xl border border-indigo-200/50 dark:border-indigo-800/30 p-8 lg:p-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                AI GEO Audit
            </span>
            <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 dark:text-white mt-4">I tuoi clienti chiedono a ChatGPT. Lui chi consiglia?</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300 mt-4 leading-relaxed">Sempre più persone non cercano su Google: chiedono a un'AI "dove compro", "chi chiamo", "cosa prenoto". Se l'AI non ti nomina, per quel cliente non esisti. AI GEO Audit ti fa vedere le risposte vere e ti dice cosa fare per entrarci.</p>
            <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-indigo-500 text-white font-semibold hover:bg-indigo-600 shadow-lg shadow-indigo-500/25 transition-all mt-8">
                Fai il tuo primo audit
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        <!-- Mockup: una risposta di ChatGPT che non nomina l'attività -->
        <div class="transform lg:rotate-1 hover:rotate-0 transition-transform duration-500">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                    </div>
                    <span class="text-xs text-slate-400 dark:text-slate-500 flex-1 text-center">Risposta di ChatGPT</span>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex justify-end">
                        <p class="max-w-[85%] px-3 py-2 rounded-xl rounded-br-sm bg-indigo-500 text-white text-xs">Dove compro una torta di compleanno senza glutine a Torino?</p>
                    </div>
                    <div class="max-w-[90%] px-3 py-2 rounded-xl rounded-bl-sm bg-slate-100 dark:bg-slate-700 text-xs text-slate-700 dark:text-slate-200 space-y-1">
                        <p>Ecco alcune pasticcerie di Torino con torte senza glutine:</p>
                        <p>1. <strong>Dolce Senza</strong>, zona San Salvario</p>
                        <p>2. <strong>Laboratorio Free</strong>, consegna a domicilio</p>
                        <p>3. <strong>Pasticceria Aurora</strong>, su ordinazione</p>
                    </div>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Pasticceria Rossi: non citata</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">ha letto il tuo sito</span>
                    </div>
                </div>
                <div class="px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-t border-indigo-200 dark:border-indigo-800/30 flex items-center justify-between">
                    <span class="text-xs text-indigo-600 dark:text-indigo-300 font-medium">Esempio inventato</span>
                    <span class="text-xs text-indigo-500 dark:text-indigo-400">3 concorrenti consigliati al posto tuo</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Come funziona -->
<div class="mt-20">
    <div class="text-center mb-12">
        <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Come funziona</h3>
        <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Dal tuo sito al piano di lavoro in 3 passaggi, senza competenze tecniche</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <?php foreach ($steps as $i => [$title, $text, $icon]): ?>
        <div class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-indigo-500 text-white flex items-center justify-center text-sm font-bold shadow-md"><?= $i + 1 ?></div>
            <div class="h-12 w-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mt-2 mb-4">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><?= $icon ?></svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-2"><?= e($title) ?></h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed"><?= e($text) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Feature Sections -->
<div class="mt-20 space-y-0">

    <!-- Feature 1: ti conoscono / ti consigliano (testo SX, visual DX, bianco) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Ti conoscono. Ma ti consigliano?</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Sono due cose diverse. Se chiedi a ChatGPT di te per nome, magari ti descrive benissimo. Ma quando un cliente chiede <strong class="text-indigo-600 dark:text-indigo-400">"dove trovo quello che vendi?"</strong>, potrebbe consigliare solo i tuoi concorrenti. Il report mette i due numeri uno accanto all'altro e ti dice anche quando le AI <strong class="text-indigo-600 dark:text-indigo-400">leggono il tuo sito ma consigliano altri</strong>.
                    </p>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Audit d'acquisto · esempio</span>
                        </div>
                        <div class="p-4 grid grid-cols-3 gap-3 text-center">
                            <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-700/40">
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Ti conoscono</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white">79%</p>
                            </div>
                            <div class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Ti consigliano</p>
                                <p class="text-xl font-bold text-red-600 dark:text-red-400">2%</p>
                            </div>
                            <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                                <p class="text-[10px] text-slate-500 dark:text-slate-400">Leggono il tuo sito senza nominarti</p>
                                <p class="text-xl font-bold text-amber-600 dark:text-amber-400">2</p>
                            </div>
                        </div>
                        <p class="px-4 pb-4 text-xs text-slate-500 dark:text-slate-400">Numeri da un audit reale su un'attività turistica: famosa per nome, invisibile a chi cerca il servizio.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: chi consigliano al posto tuo (visual SX, testo DX, slate) -->
    <div class="py-16 bg-slate-50/50 dark:bg-slate-800/30">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="lg:order-2">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Chi consigliano al posto tuo, e perché</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Vedi i nomi che le AI fanno al posto del tuo e i siti da cui prendono le informazioni: portali, guide, recensioni, i siti dei concorrenti. Così capisci <strong class="text-indigo-600 dark:text-indigo-400">dove devi farti trovare</strong>: sul tuo sito, su un portale di settore o su una guida locale.
                    </p>
                </div>
                <div class="lg:order-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Al tuo posto consigliano</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">esempio</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <?php foreach ([['Dolce Senza', 9, 'il suo sito'], ['Laboratorio Free', 6, 'una guida locale'], ['Pasticceria Aurora', 4, 'un portale di recensioni']] as [$n, $c, $src]): ?>
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="text-xs font-medium text-slate-900 dark:text-white flex-1"><?= $n ?></span>
                                <span class="text-[10px] text-slate-400"><?= $c ?> risposte</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">fonte: <?= $src ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 3: cosa pubblicare (testo SX, visual DX, bianco) -->
    <div class="py-16 bg-white dark:bg-slate-900">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Cosa pubblicare, già scritto</h3>
                    <p class="mt-4 text-slate-600 dark:text-slate-300 leading-relaxed">
                        Il report non si ferma ai numeri. Raggruppa le domande in cui non compari in <strong class="text-indigo-600 dark:text-indigo-400">pochi articoli mirati</strong>, ti dice quali dati mancano sul sito (partita IVA, indirizzo, orari) e, per ogni intervento, prepara una scheda con i testi pronti da copiare. Usa solo i fatti che hai confermato tu.
                    </p>
                </div>
                <div>
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Interventi suggeriti</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">esempio</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            <?php foreach ([
                                ['da chiarire', 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300', 'Le AI non trovano la partita IVA: cosa scrivere nel footer'],
                                ['articolo', 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300', 'Torte senza glutine su ordinazione a Torino: come funziona'],
                                ['articolo', 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300', 'Torte di compleanno per bambini con allergie'],
                                ['tecnico', 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300', 'Il sito blocca il lettore di ChatGPT: come sbloccarlo'],
                            ] as [$label, $cls, $title]): ?>
                            <div class="px-4 py-2.5 flex items-center gap-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium whitespace-nowrap <?= $cls ?>"><?= $label ?></span>
                                <span class="text-xs text-slate-900 dark:text-white flex-1 truncate"><?= $title ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-t border-indigo-200 dark:border-indigo-800/30 flex items-center justify-between">
                            <span class="text-xs text-indigo-600 dark:text-indigo-300">Scheda con i testi pronti: <?= $crediti($briefCost) ?></span>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-500 text-white text-xs font-medium">Esporta PDF</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cosa puoi fare -->
    <div class="mt-20">
        <div class="text-center mb-12">
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Cosa puoi fare con AI GEO Audit</h3>
            <p class="mt-3 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Per un negozio, un'attività locale, un blog o un piccolo e-commerce</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($cards as [$title, $text, $icon]): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition-all">
                <div class="h-10 w-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mb-4">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><?= $icon ?></svg>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white mb-2"><?= e($title) ?></h4>
                <p class="text-sm text-slate-500 dark:text-slate-400"><?= e($text) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FAQ -->
    <div class="mt-20" x-data="{ openFaq: null }">
        <div class="text-center mb-12">
            <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white">Domande frequenti</h3>
        </div>
        <div class="max-w-3xl mx-auto space-y-3">
            <?php foreach ($faqs as $i => [$q, $a]): $n = $i + 1; ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <button @click="openFaq === <?= $n ?> ? openFaq = null : openFaq = <?= $n ?>" class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <span class="font-medium text-slate-900 dark:text-white"><?= e($q) ?></span>
                    <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="openFaq === <?= $n ?> && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openFaq === <?= $n ?>" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-6 pb-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed"><?= e($a) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CTA Finale -->
    <div class="mt-20 mb-8">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-500 to-violet-500 p-8 lg:p-12">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4"></div>
            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Scopri cosa rispondono le AI ai tuoi clienti</h3>
                    <p class="mt-2 text-indigo-100">Crea il progetto con il tuo sito: vedi la stima dei crediti prima di partire</p>
                </div>
                <a href="<?= url('/projects/create') ?>" class="inline-flex items-center px-8 py-3 rounded-xl bg-white text-indigo-600 font-semibold hover:bg-indigo-50 shadow-lg transition-all flex-shrink-0">
                    Crea progetto
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
