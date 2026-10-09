<?php
$legalTitle = 'Privacy Policy';
$legalSubtitle = 'Come Ainstein raccoglie, usa e protegge i tuoi dati personali, ai sensi del Regolamento (UE) 2016/679 (GDPR).';
include __DIR__ . '/_legal-head.php';
?>

<div class="max-w-none space-y-8">

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">1. Titolare del trattamento</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Il titolare del trattamento è <strong><?= htmlspecialchars($legalOwner['name']) ?></strong>,
            <?= htmlspecialchars($legalOwner['address']) ?>, P.IVA <?= htmlspecialchars($legalOwner['vat']) ?>.
            Per qualsiasi richiesta relativa ai tuoi dati puoi scrivere a
            <a href="mailto:<?= $legalOwner['email'] ?>" class="text-primary-600 dark:text-primary-400 hover:underline"><?= $legalOwner['email'] ?></a>.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">2. Quali dati raccogliamo</h2>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed">
            <li><strong>Dati di registrazione:</strong> nome, indirizzo email e password (conservata in forma cifrata). Se accedi con Google riceviamo da Google nome, email, identificativo e immagine del profilo.</li>
            <li><strong>Dati dei progetti:</strong> i siti web, le parole chiave, i testi e le informazioni che inserisci nella piattaforma per usare gli strumenti.</li>
            <li><strong>Dati da servizi collegati:</strong> se decidi di collegare Google Search Console, Google Analytics o Google Ads, riceviamo i dati di quei servizi relativi ai siti che scegli tu. Puoi scollegarli in qualsiasi momento.</li>
            <li><strong>Dati di utilizzo:</strong> operazioni eseguite, crediti consumati, data e ora degli accessi, indirizzo IP, tipo di browser. Servono per far funzionare il servizio, prevenire abusi e migliorarlo.</li>
            <li><strong>Dati di pagamento:</strong> se acquisti un piano, il pagamento è gestito da un fornitore esterno. Non conserviamo i dati della tua carta.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">3. Perché li usiamo e su quale base giuridica</h2>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed">
            <li><strong>Fornire il servizio</strong> (creare l'account, eseguire le analisi, gestire i crediti): esecuzione del contratto.</li>
            <li><strong>Inviarti email di servizio</strong> (benvenuto, reset password, esito delle operazioni, inviti ai progetti): esecuzione del contratto. Le notifiche non essenziali si disattivano dal profilo.</li>
            <li><strong>Sicurezza e prevenzione abusi</strong> (limiti di accesso, log tecnici): legittimo interesse.</li>
            <li><strong>Obblighi di legge</strong> (fatturazione, contabilità): obbligo legale.</li>
        </ul>
        <p class="mt-3 text-slate-600 dark:text-slate-400 leading-relaxed">Non vendiamo i tuoi dati e non li usiamo per pubblicità di terzi.</p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">4. Intelligenza artificiale e fornitori esterni</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Molti strumenti di Ainstein usano modelli di intelligenza artificiale di fornitori esterni. I contenuti che invii a questi strumenti
            (ad esempio il testo di una pagina, una lista di parole chiave, il nome di un brand) vengono trasmessi al fornitore per essere elaborati
            e restituire il risultato. Non inviare dati personali di terzi o informazioni riservate che non vuoi far elaborare.
        </p>
        <p class="mt-3 text-slate-600 dark:text-slate-400 leading-relaxed">I fornitori che possono trattare dati per nostro conto sono:</p>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed mt-2">
            <li><strong>Anthropic</strong> e <strong>OpenAI</strong> (USA): elaborazione dei contenuti tramite modelli di intelligenza artificiale.</li>
            <li><strong>Google</strong> (Google LLC / Google Ireland): accesso con Google e, su tua scelta, Search Console, Analytics e Google Ads.</li>
            <li><strong>Fornitori di dati SEO</strong> (ad esempio DataForSEO, SerpApi, Keywords Everywhere): verifica delle posizioni e dei volumi di ricerca delle parole chiave che inserisci.</li>
            <li><strong>Contabo GmbH</strong> (Germania, UE): hosting dei server e dei database.</li>
            <li><strong>Fornitore di invio email</strong>: recapito delle email di servizio.</li>
        </ul>
        <p class="mt-3 text-slate-600 dark:text-slate-400 leading-relaxed">
            Per i fornitori con sede fuori dall'Unione Europea il trasferimento avviene sulla base delle Clausole Contrattuali Standard
            approvate dalla Commissione Europea o del Data Privacy Framework UE-USA.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">5. Per quanto tempo conserviamo i dati</h2>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed">
            <li><strong>Account e progetti:</strong> finché l'account è attivo. Puoi chiedere la cancellazione in qualsiasi momento.</li>
            <li><strong>Log tecnici e di utilizzo:</strong> periodi limitati, con pulizia automatica periodica.</li>
            <li><strong>Dati di fatturazione:</strong> 10 anni, come richiesto dalla legge italiana.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">6. I tuoi diritti</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Hai diritto di accedere ai tuoi dati, correggerli, chiederne la cancellazione o la limitazione, opporti al trattamento,
            ottenerli in formato portabile e revocare il consenso dove previsto. Per esercitarli scrivi a
            <a href="mailto:<?= $legalOwner['email'] ?>" class="text-primary-600 dark:text-primary-400 hover:underline"><?= $legalOwner['email'] ?></a>.
            Hai inoltre il diritto di presentare reclamo al Garante per la protezione dei dati personali
            (<a href="https://www.garanteprivacy.it" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 hover:underline">garanteprivacy.it</a>).
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">7. Sicurezza</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Le connessioni sono protette da HTTPS, le password sono salvate solo in forma cifrata, i token di accesso ai servizi Google
            sono conservati in modo protetto e l'accesso ai server è limitato. Nessun sistema è sicuro al 100%: in caso di violazione
            che comporti un rischio per i tuoi diritti ti informeremo come previsto dalla legge.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">8. Cookie</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Ainstein usa solo cookie tecnici necessari al funzionamento. I dettagli sono nella
            <a href="<?= url('/docs/cookies') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Cookie Policy</a>.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">9. Modifiche</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Possiamo aggiornare questa informativa. Le modifiche rilevanti vengono comunicate via email o con un avviso nella piattaforma.
            La data in cima alla pagina indica la versione in vigore.
        </p>
    </section>

</div>
