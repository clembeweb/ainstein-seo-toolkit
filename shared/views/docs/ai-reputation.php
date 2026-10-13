<!-- AI GEO Audit -->
<div class="space-y-12">

    <!-- H1 -->
    <div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">AI GEO Audit</h1>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Cosa dicono ChatGPT, Gemini e le altre AI di te, perché, e cosa fare</p>
    </div>

    <!-- Cos'e -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Cos'è
        </h2>
        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                Sempre più persone chiedono alle AI "a chi mi rivolgo per…" o "questa azienda è affidabile?". L'AI GEO Audit pone alle AI le domande che fanno i tuoi clienti, legge le risposte e le fonti citate, e ti dice tre cose: <strong>se ti citano</strong> (o citano i concorrenti), <strong>cosa dicono di te</strong> (dubbi, rischi, fatti sbagliati) e <strong>cosa fare</strong>: un piano con i testi pronti da pubblicare, i dati strutturati da incollare nel sito e, se serve, le richieste di rimozione. Il Controllo tecnico, gratuito, verifica anche se i crawler delle AI riescono a leggere il tuo sito.
            </p>
        </div>
    </section>

    <!-- Quick Start -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Quick Start
        </h2>
        <div class="space-y-4">
            <?php
            $steps = [
                ['Crea il progetto e di\' chi stai monitorando', 'Dal progetto globale attiva l\'AI GEO Audit. Nella Overview controlla il nome (come lo cercherebbe un cliente), la città e se è un\'azienda o una persona: sono i dati con cui interroghiamo le AI.'],
                ['Genera e conferma il profilo', 'In "Profilo" clicca "Genera il profilo": l\'AI raccoglie i fatti su di te dal sito e dal web e li propone riga per riga. Confermali, correggili o rifiutali: sono la verità con cui vengono giudicate le risposte delle AI.'],
                ['Scegli l\'audit e genera le domande', 'Acquisto: domande di chi cerca il tuo servizio senza conoscerti ("tour privato a Capri da Castellammare"). Reputazione: domande su di te ("è affidabile?"). Oppure entrambi. "Genera domande" le scrive dal profilo; puoi aggiungerne, disattivarne, usarne solo alcune per una prova.'],
                ['Avvia il run', 'Vedi prima quante risposte verranno raccolte e quanti crediti costa. Ogni domanda viene posta a ogni AI configurata (ChatGPT e Gemini); ogni risposta viene letta e giudicata. Puoi seguire l\'avanzamento in diretta.'],
                ['Leggi il report e il piano', 'In cima: quanto ti citano, il rischio reputazione, chi citano al posto tuo. Poi gli interventi: i dubbi delle AI con la causa trovata sul tuo sito, gli articoli da pubblicare, le fonti negative. Per ogni intervento "Genera scheda" scrive i testi pronti; "Esporta PDF" dà il piano al cliente.'],
            ];
            foreach ($steps as $i => [$t, $d]): ?>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold"><?= $i + 1 ?></div>
                <div>
                    <h3 class="font-medium text-slate-900 dark:text-white"><?= htmlspecialchars($t) ?></h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400"><?= htmlspecialchars($d) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Funzionalita -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
            Funzionalità principali
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            $features = [
                ['blue', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'Due audit: Acquisto e Reputazione', 'Le domande di chi cerca il servizio (ti citano o citano i concorrenti?) e le domande su di te (affidabilità, rischi). Scegli uno dei due o entrambi; le domande le scrive l\'AI dal profilo.'],
                ['green', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Risposte vere, giudicate una per una', 'Ogni risposta di ChatGPT e Gemini viene letta: ti cita? in che posizione? è positiva, neutra o negativa? quali fonti usa? Lo stesso modello giudica tutte le risposte, così i run sono confrontabili.'],
                ['amber', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'I dubbi delle AI, con la causa', 'Se un\'AI dice "non trovo la partita IVA" o "lo stesso telefono compare su più siti", il piano lo riporta come dubbio da chiarire e il Controllo tecnico dice perché: il dato manca, è nel testo ma non nei dati strutturati, oppure il crawler non entra.'],
                ['purple', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', 'Controllo tecnico gratuito', 'Entriamo nel tuo sito con i crawler veri delle AI (GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, Bingbot): vediamo i blocchi del server e della CDN, non solo il robots.txt. Più dati strutturati, P.IVA, indirizzo, telefoni, recensioni, sitemap e llms.txt.'],
                ['rose', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'Piano e schede con i testi pronti', 'Per ogni intervento "Genera scheda" scrive cosa pubblicare e dove: piè di pagina, Chi siamo, Contatti, Prenotazione, con i dati mancanti segnati [DA COMPLETARE], il JSON-LD da incollare e una checklist fuori dal sito. Gli articoli hanno brief, fatti citabili e fonti.'],
                ['indigo', 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'PDF per il cliente', 'Il piano degli interventi si esporta in PDF: contenuti da pubblicare, siti da contattare, schede operative. Senza nomi di modelli né costi interni.'],
            ];
            foreach ($features as [$c, $icon, $t, $d]): ?>
            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-<?= $c ?>-50 dark:bg-<?= $c ?>-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-<?= $c ?>-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>"/></svg>
                    </div>
                    <h3 class="font-medium text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($t) ?></h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400"><?= htmlspecialchars($d) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Costi in crediti -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Costi in crediti
        </h2>
        <?php
        // Dai costi veri (impostazioni del modulo), mai scritti a mano
        $fmt = fn(float $n) => rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
        $cr = fn(float $n) => $n <= 0 ? 'Gratuito' : $fmt($n) . ' credit' . ($n == 1 ? 'o' : 'i');
        $rows = [
            ['Controllo tecnico del sito', 0, 'emerald'],
            ['Generazione del profilo e delle domande', (float) \Core\Credits::getCost('ai_analysis_medium', null, 1), 'blue'],
            ['Run: per ogni risposta raccolta e analizzata (domanda × AI)', (float) \Core\Credits::getCost('collect_response', 'ai-reputation', 1), 'blue'],
            ['Rianalizza: per ogni risposta rigiudicata', (float) \Core\Credits::getCost('reanalyze_response', 'ai-reputation', 0.5), 'blue'],
            ['Scheda operativa di un intervento', (float) \Core\Credits::getCost('action_brief', 'ai-reputation', 1), 'purple'],
            ['Report, PDF, Controllo tecnico', 0, 'emerald'],
        ];
        ?>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80">
                        <th class="text-left px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">Operazione</th>
                        <th class="text-center px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">Crediti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php foreach ($rows as [$label, $cost, $c]): ?>
                    <tr class="bg-white dark:bg-slate-800">
                        <td class="px-5 py-3 text-slate-700 dark:text-slate-300"><?= htmlspecialchars($label) ?></td>
                        <td class="px-5 py-3 text-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $c ?>-100 text-<?= $c ?>-700 dark:bg-<?= $c ?>-900/40 dark:text-<?= $c ?>-300"><?= $cr($cost) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Esempio: 30 domande d'acquisto × 2 AI = 60 risposte = <?= $fmt(60 * (float) \Core\Credits::getCost('collect_response', 'ai-reputation', 1)) ?> crediti. Prima di "Avvia run" vedi sempre risposte e crediti. Per una prova piccola seleziona alcune domande e usa "Usa solo queste".
        </p>
    </section>

    <!-- Suggerimenti -->
    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
            <svg class="w-5 h-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
            Suggerimenti
        </h2>
        <div class="space-y-4">
            <?php
            $tips = [
                ['blue', 'Parti dal Controllo tecnico', 'È gratuito e dura pochi secondi: se un crawler è bloccato o mancano P.IVA e indirizzo, lo sai prima di spendere crediti nel run.'],
                ['blue', 'Conferma il profilo con cura', 'Le righe confermate sono la verità con cui giudichiamo le AI: una riga sbagliata produce verdetti sbagliati. "Conferma tutte" va bene solo dopo averle lette.'],
                ['blue', 'Per un\'azienda inizia con l\'audit d\'acquisto', 'Dice se, quando un cliente cerca il tuo servizio, le AI fanno il tuo nome o quello dei concorrenti. La reputazione aggiungila dopo, o solo se hai un passato da gestire.'],
                ['emerald', 'Dopo le correzioni, rilancia', 'Pubblicati i testi della scheda, un nuovo run sulle stesse domande mostra se i dubbi sono spariti. Tieni attive solo le domande coinvolte per spendere meno.'],
            ];
            foreach ($tips as [$c, $t, $d]): ?>
            <div class="flex items-start gap-4 p-5 rounded-xl bg-<?= $c ?>-50 dark:bg-<?= $c ?>-900/20 border border-<?= $c ?>-200 dark:border-<?= $c ?>-800/50">
                <div class="shrink-0 w-8 h-8 rounded-lg bg-<?= $c ?>-100 dark:bg-<?= $c ?>-900/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-<?= $c ?>-600 dark:text-<?= $c ?>-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="font-medium text-<?= $c ?>-900 dark:text-<?= $c ?>-200 text-sm"><?= htmlspecialchars($t) ?></h3>
                    <p class="mt-1 text-sm text-<?= $c ?>-800 dark:text-<?= $c ?>-300/80"><?= htmlspecialchars($d) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Navigation -->
    <div class="flex items-center justify-between pt-8 border-t border-slate-200 dark:border-slate-700">
        <a href="<?= url('/docs/keyword-research') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            AI Keywords
        </a>
        <a href="<?= url('/docs/credits') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
            Sistema Crediti
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</div>
