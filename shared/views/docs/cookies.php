<?php
$legalTitle = 'Cookie Policy';
$legalSubtitle = 'Quali cookie usa Ainstein e perché. In breve: solo quelli tecnici.';
include __DIR__ . '/_legal-head.php';
?>

<div class="max-w-none space-y-8">

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">1. Cosa sono i cookie</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            I cookie sono piccoli file di testo che il sito salva nel tuo browser. Servono a riconoscerti tra una pagina e l'altra
            e a ricordare alcune impostazioni.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">2. Cookie usati da Ainstein</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nome</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Scopo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Durata</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-slate-600 dark:text-slate-400">
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">PHPSESSID</td>
                        <td class="px-4 py-3">Mantiene la sessione di accesso e protegge i moduli da invii fraudolenti (CSRF).</td>
                        <td class="px-4 py-3">Fino alla chiusura del browser</td>
                        <td class="px-4 py-3">Tecnico, necessario</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">remember_token</td>
                        <td class="px-4 py-3">Ti tiene collegato se scegli "Ricordami" al login.</td>
                        <td class="px-4 py-3">30 giorni</td>
                        <td class="px-4 py-3">Tecnico, su tua scelta</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">darkMode (localStorage)</td>
                        <td class="px-4 py-3">Ricorda la preferenza tema chiaro/scuro. Non è un cookie: resta solo nel tuo browser.</td>
                        <td class="px-4 py-3">Finché non lo cancelli</td>
                        <td class="px-4 py-3">Tecnico, preferenza</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">ainstein_consent (localStorage)</td>
                        <td class="px-4 py-3">Ricorda la scelta fatta nel banner sui cookie di analisi. Non è un cookie: resta solo nel tuo browser.</td>
                        <td class="px-4 py-3">Finché non lo cancelli</td>
                        <td class="px-4 py-3">Tecnico, preferenza</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">_ga, _ga_*</td>
                        <td class="px-4 py-3">Google Analytics 4: distinguono i visitatori e le visite per contare quante persone usano Ainstein e quali strumenti funzionano. Impostati <strong>solo se accetti</strong> nel banner.</td>
                        <td class="px-4 py-3">13 mesi</td>
                        <td class="px-4 py-3">Analisi, con consenso</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">3. Cookie di terze parti e profilazione</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Ainstein <strong>non usa cookie di profilazione né pubblicitari</strong>. I cookie tecnici possono essere usati senza consenso
            ai sensi dell'art. 122 del Codice Privacy e delle Linee guida del Garante del 10 giugno 2021.
        </p>
        <p class="mt-3 text-slate-600 dark:text-slate-400 leading-relaxed">
            <strong>Google Analytics 4</strong> (Google Ireland Ltd) ci serve per contare visite, iscrizioni e uso degli strumenti del sito
            ainstein.it. I suoi cookie vengono impostati <strong>solo dopo il tuo consenso</strong> nel banner che compare alla prima visita
            ("Accetta"); con "Solo necessari" Google Analytics riceve soltanto conteggi anonimi senza cookie e senza identificare il tuo
            browser (Consent Mode). Gli indirizzi IP sono troncati da Google, i dati non vengono usati per pubblicità e non li condividiamo
            con altri. Puoi cambiare idea in qualsiasi momento cancellando i dati del sito dal browser: il banner ricompare.
        </p>
        <p class="mt-3 text-slate-600 dark:text-slate-400 leading-relaxed">
            Quando accedi con Google o colleghi un servizio Google, Google può impostare propri cookie sulle sue pagine, regolati dalla
            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 hover:underline">privacy policy di Google</a>.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">4. Come gestire i cookie</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Puoi cancellare o bloccare i cookie dalle impostazioni del browser. Bloccando il cookie di sessione non sarà possibile accedere ad Ainstein.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">5. Titolare</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            <strong><?= htmlspecialchars($legalOwner['name']) ?></strong>, <?= htmlspecialchars($legalOwner['address']) ?>.
            Contatti: <a href="mailto:<?= $legalOwner['email'] ?>" class="text-primary-600 dark:text-primary-400 hover:underline"><?= $legalOwner['email'] ?></a>.
            Maggiori dettagli nella <a href="<?= url('/docs/privacy') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</a>.
        </p>
    </section>

</div>
