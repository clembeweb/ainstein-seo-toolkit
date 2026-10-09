<?php
$legalTitle = 'Termini di Servizio';
$legalSubtitle = 'Le regole per usare Ainstein. Registrandoti accetti questi termini.';
include __DIR__ . '/_legal-head.php';
?>

<div class="max-w-none space-y-8">

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">1. Chi siamo e cos'è Ainstein</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Ainstein è una piattaforma online di strumenti per SEO, contenuti e pubblicità, basata su intelligenza artificiale,
            fornita da <strong><?= htmlspecialchars($legalOwner['name']) ?></strong>, <?= htmlspecialchars($legalOwner['address']) ?>,
            P.IVA <?= htmlspecialchars($legalOwner['vat']) ?> ("noi"). Contatti:
            <a href="mailto:<?= $legalOwner['email'] ?>" class="text-primary-600 dark:text-primary-400 hover:underline"><?= $legalOwner['email'] ?></a>.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">2. Account</h2>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed">
            <li>Per usare Ainstein serve un account. Devi avere almeno 18 anni e fornire dati veritieri.</li>
            <li>Sei responsabile della riservatezza della password e di tutto ciò che avviene con il tuo account.</li>
            <li>Un account è personale. Puoi condividere singoli progetti con altri utenti tramite la funzione di invito.</li>
            <li>Puoi chiudere l'account in qualsiasi momento scrivendoci. Possiamo sospendere o chiudere account che violano questi termini.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">3. Crediti, piani e pagamenti</h2>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed">
            <li>Gli strumenti consumano <strong>crediti</strong>. Il costo di ogni operazione è indicato prima dell'esecuzione e nella pagina <a href="<?= url('/docs/credits') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Sistema Crediti</a>.</li>
            <li>Alla registrazione ricevi crediti gratuiti di prova. I crediti non sono convertibili in denaro e non sono trasferibili.</li>
            <li>I piani a pagamento, i prezzi e la durata sono indicati nella pagina <a href="<?= url('/pricing') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Prezzi</a>. I prezzi possono cambiare: le modifiche non toccano il periodo già pagato.</li>
            <li>Se un'operazione fallisce per un nostro errore tecnico, i crediti vengono riaccreditati.</li>
            <li>Non sono previsti rimborsi per crediti già consumati. Per i consumatori restano validi i diritti previsti dal Codice del Consumo.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">4. Uso consentito</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Non puoi usare Ainstein per:</p>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed mt-2">
            <li>analizzare o pubblicare su siti che non ti appartengono o per cui non hai autorizzazione;</li>
            <li>produrre contenuti illegali, diffamatori, ingannevoli o che violano diritti di terzi;</li>
            <li>sovraccaricare il servizio, aggirare i limiti, fare scraping della piattaforma o rivenderne l'accesso;</li>
            <li>inserire dati personali di terzi senza averne il diritto.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">5. Contenuti generati dall'intelligenza artificiale</h2>
        <ul class="list-disc pl-6 space-y-2 text-slate-600 dark:text-slate-400 leading-relaxed">
            <li>I testi, le analisi e i suggerimenti prodotti dagli strumenti sono generati automaticamente e <strong>possono contenere errori, imprecisioni o informazioni non aggiornate</strong>.</li>
            <li>Sei tu a dover verificare i risultati prima di usarli o pubblicarli. Ainstein è un supporto, non sostituisce il giudizio di un professionista.</li>
            <li>I contenuti che generi con i tuoi dati sono tuoi: puoi usarli liberamente. Resti responsabile del loro utilizzo.</li>
            <li>Non garantiamo risultati di posizionamento, traffico o vendite.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">6. Servizi di terzi collegati</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Puoi collegare servizi esterni (Google Search Console, Google Analytics, Google Ads, WordPress e altri CMS). Il loro uso resta regolato
            dai termini di quei servizi. Le operazioni che Ainstein esegue su quei servizi (ad esempio pubblicare un articolo) avvengono su tua
            richiesta esplicita e sotto la tua responsabilità.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">7. Disponibilità del servizio</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Ci impegniamo a mantenere Ainstein disponibile, ma non garantiamo un funzionamento continuo e senza errori. Possiamo sospendere
            il servizio per manutenzione, aggiornamenti o cause esterne (ad esempio indisponibilità dei fornitori di intelligenza artificiale).
            Possiamo modificare, aggiungere o rimuovere strumenti; se una modifica riduce in modo sostanziale il servizio pagato, ti avvisiamo in anticipo.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">8. Limitazione di responsabilità</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Nei limiti consentiti dalla legge, non rispondiamo di danni indiretti, perdita di profitti, perdita di dati o danni derivanti dall'uso
            dei contenuti generati. In ogni caso la nostra responsabilità complessiva è limitata all'importo da te pagato nei 12 mesi precedenti
            l'evento. Nulla in questi termini limita i diritti inderogabili dei consumatori.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">9. Proprietà intellettuale</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            La piattaforma, il marchio Ainstein, il software e la documentazione sono di nostra proprietà. Ti concediamo una licenza
            personale, non esclusiva e revocabile per usare il servizio secondo questi termini.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">10. Privacy</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Il trattamento dei dati personali è descritto nella <a href="<?= url('/docs/privacy') ?>" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</a>, che fa parte di questi termini.
        </p>
    </section>

    <section>
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">11. Modifiche e legge applicabile</h2>
        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
            Possiamo aggiornare questi termini; le modifiche rilevanti vengono comunicate via email o nella piattaforma e si applicano
            dall'uso successivo del servizio. Questi termini sono regolati dalla legge italiana. Per i consumatori è competente il foro
            del luogo di residenza; negli altri casi il foro della sede del titolare.
        </p>
    </section>

</div>
