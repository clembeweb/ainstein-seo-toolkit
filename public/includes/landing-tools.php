<?php
/**
 * Sezione "Gli strumenti" della home, in due varianti:
 *  - tabs   (default, /):        tre tempi con linguette + pannello
 *  - slider (/landing-b):        card affiancate che scorrono e si aprono col "+" (effetto Semrush)
 * Usa $tile, $icons, $C, $tools, $groups, $bonus definiti in landing5.php. $variant = 'tabs' | 'slider'.
 */
$variant = $variant ?? 'tabs';

$panels = [
    'geo' => [
        'chip' => ['#eef2ff', '#4f46e5'],
        'h3' => 'Cosa rispondono le AI quando un cliente chiede di te.',
        'when' => 'Quando vuoi sapere se ChatGPT e Gemini fanno il tuo nome, o quello di altri.',
        'li' => ['Le domande dei tuoi clienti fatte alle AI senza dire il tuo nome: quante volte ti consigliano e chi consigliano al posto tuo', 'I dubbi che le AI hanno su di te (partita IVA, recensioni, contatti) e i testi pronti per chiarirli sul sito', 'Il piano: cosa pubblicare e cosa correggere, in ordine, con il PDF da girare'],
        'cost' => 'Controllo tecnico gratis. Audit completo: 1 credito a risposta, il totale lo vedi prima di avviarlo.',
        'cta' => 'Provalo con ' . (int) $bonus . ' crediti',
        'mock' => '<div class="bar"><i></i><i></i><i></i><small>AI GEO Audit · esempio</small></div><div class="in">
                <div class="kpis"><div class="kpi"><small>Ti conoscono</small><b>81%</b></div><div class="kpi"><small>Ti consigliano</small><b style="color:#dc2626">6%</b></div><div class="kpi"><small>Usano il tuo sito</small><b style="color:#d97706">3</b></div></div>
                <small style="color:var(--muted)">Al tuo posto consigliano</small>
                <div class="bars"><div><span style="--w:82%;background:#6366f1"></span></div><div><span style="--w:61%;background:#a5b4fc"></span></div><div><span style="--w:44%;background:#c7d2fe"></span></div></div>
                <span class="chip" style="background:#fef3c7;color:#92400e">da chiarire: partita IVA</span><span class="chip" style="background:#ccfbf1;color:#0f766e">articolo: torte senza glutine su ordinazione</span>
            </div>',
    ],
    'audit' => [
        'chip' => ['#d1fae5', '#047857'],
        'h3' => 'Il tuo sito come lo vede Google.',
        'when' => 'Quando non sai da dove iniziare, o il sito è fermo da anni.',
        'li' => ['Tutte le pagine lette dal server, senza installare niente: titoli mancanti, link rotti, pagine nascoste, immagini senza descrizione', 'I problemi in italiano semplice, in ordine di importanza', 'Il piano d\'azione scritto dall\'AI: cosa sistemare prima, quanto è difficile, cosa cambia'],
        'cost' => 'Scansione gratis, fino a 100 pagine. Piano d\'azione: 10 crediti.',
        'cta' => 'Provalo gratis',
        'mock' => '<div class="bar"><i></i><i></i><i></i><small>AI Audit · esempio</small></div><div class="in">
                <div class="kpis"><div class="kpi"><small>Salute</small><b style="color:#059669">78</b></div><div class="kpi"><small>Da sistemare</small><b style="color:#dc2626">3</b></div><div class="kpi"><small>Pagine</small><b>214</b></div></div>
                <small style="color:var(--muted)">Problemi per gravità</small>
                <div class="bars"><div><span style="--w:22%;background:#ef4444"></span></div><div><span style="--w:48%;background:#f59e0b"></span></div><div><span style="--w:88%;background:#10b981"></span></div></div>
            </div>',
    ],
    'kw' => [
        'chip' => ['#ede9fe', '#6d28d9'],
        'h3' => 'Descrivi l\'attività. Ti ritrovi le pagine e gli articoli da fare.',
        'when' => 'Quando devi decidere di cosa parlare sul sito e non vuoi tirare a indovinare.',
        'li' => ['Le ricerche vere della gente con i volumi di Google, raggruppate per argomento', 'La struttura del sito: quali pagine servono, con indirizzo e titolo', 'Il piano editoriale dei prossimi mesi, pronto per AI Writer'],
        'cost' => 'Ricerca: 3 crediti. Struttura del sito: 10. Piano editoriale: 10.',
        'cta' => 'Provalo con ' . (int) $bonus . ' crediti',
        'mock' => '<div class="bar"><i></i><i></i><i></i><small>Struttura del sito · esempio</small></div><div class="in">
                <table><tr><th>Ricerca</th><th>Al mese</th><th>Pagina</th></tr><tr><td>torte senza glutine torino</td><td>1.300</td><td>/senza-glutine/</td></tr><tr><td>torta di compleanno a domicilio</td><td>880</td><td>/compleanni/</td></tr><tr><td>pasticceria vegana torino</td><td>590</td><td>/vegano/</td></tr></table>
            </div>',
    ],
    'writer' => [
        'chip' => ['#fef3c7', '#b45309'],
        'h3' => 'Dal titolo all\'articolo pubblicato.',
        'when' => 'Quando hai il piano e non hai il tempo di scrivere.',
        'li' => ['Guarda chi è primo su Google per quella ricerca e prepara la scaletta', 'Scrive l\'articolo nella tua lingua, con la copertina e i link alle tue pagine', 'Lo manda su WordPress come bozza, anche da solo nei giorni che scegli. Scrive anche titolo e descrizione delle pagine che hai già'],
        'cost' => 'Un articolo completo con copertina: circa 20 crediti. Titolo e descrizione di una pagina: 2.',
        'cta' => 'Provalo con ' . (int) $bonus . ' crediti',
        'mock' => '<div class="bar"><i></i><i></i><i></i><small>AI Writer · esempio</small></div><div class="in">
                <div style="height:110px;border-radius:12px;background:linear-gradient(135deg,#fde68a,#f59e0b)"></div>
                <b style="display:block;margin:12px 0 6px">Torte senza glutine: come ordinarle senza rischi</b>
                <div class="bars"><div><span style="--w:95%;background:#e2e8f0"></span></div><div><span style="--w:80%;background:#e2e8f0"></span></div></div>
                <span class="chip" style="background:#dcfce7;color:#15803d">1.800 parole</span><span class="chip" style="background:#e0f2fe;color:#0369a1">bozza su WordPress</span>
            </div>',
    ],
    'rank' => [
        'chip' => ['#e0f2fe', '#0369a1'],
        'h3' => 'Dove compari, cosa cambia, e perché.',
        'when' => 'Quando hai pubblicato e vuoi vedere se serve a qualcosa.',
        'li' => ['Le posizioni su Google delle ricerche che ti interessano, giorno dopo giorno, in 20 paesi', 'I clic veri da Search Console e le ricerche a un passo dalla prima pagina', 'L\'Andamento: grafici giorno per giorno, da dove arriva il traffico, le visite dalle AI, e una lettura breve scritta dall\'AI'],
        'cost' => 'Posizioni: 1 credito a keyword. Andamento gratis, la lettura dell\'AI 5 crediti.',
        'cta' => 'Provalo con ' . (int) $bonus . ' crediti',
        'mock' => '<div class="bar"><i></i><i></i><i></i><small>Andamento · esempio</small></div><div class="in">
                <svg viewBox="0 0 300 90" style="width:100%"><polyline fill="none" stroke="#0ea5e9" stroke-width="3" stroke-linecap="round" points="0,70 40,62 80,66 120,48 160,40 200,44 240,26 300,18"/></svg>
                <p style="font-size:14px;color:var(--md);margin-top:8px"><b>In breve:</b> le visite da Google crescono del 12%. Il merito è della pagina sulle torte senza glutine, salita dalla posizione 9 alla 3…</p>
            </div>',
    ],
];
$phaseOf = [];
foreach ($groups as [$gLabel, $keys]) { foreach ($keys as $k) { $phaseOf[$k] = $gLabel; } }
?>
<section id="strumenti" style="background:var(--soft)">
    <div class="wrap">
        <div class="eyebrow">Gli strumenti</div>
        <h2>Prima sai dove sei. Poi fai. Poi vedi se funziona.</h2>
        <p class="lead">Cinque strumenti che si passano i dati tra loro. Parti da quello che ti serve oggi.</p>

<?php if ($variant === 'slider'): ?>
        <div class="slider-nav" aria-hidden="true"><button type="button" class="arr" data-dir="-1" aria-label="Indietro">←</button><button type="button" class="arr" data-dir="1" aria-label="Avanti">→</button></div>
        <div class="track" id="toolTrack">
            <?php foreach ($panels as $k => $p): ?>
            <article class="tcard" id="c-<?= $k ?>" data-k="<?= $k ?>">
                <div class="tcard-head">
                    <div><small class="phase"><?= $phaseOf[$k] ?></small><span class="chip" style="background:<?= $p['chip'][0] ?>;color:<?= $p['chip'][1] ?>"><?= $tools[$k][0] ?></span><h3><?= $p['h3'] ?></h3></div>
                    <button type="button" class="plus" aria-expanded="false" aria-controls="d-<?= $k ?>" aria-label="Apri <?= htmlspecialchars($tools[$k][0]) ?>"><span></span></button>
                </div>
                <div class="tcard-body">
                    <div class="tcard-text" id="d-<?= $k ?>">
                        <p class="when"><?= $p['when'] ?></p>
                        <ul><?php foreach ($p['li'] as $li): ?><li><?= $li ?></li><?php endforeach; ?></ul>
                        <p class="cost"><?= $p['cost'] ?></p>
                        <div class="acts"><a class="btn btn-blue" href="<?= url('/register') ?>"><?= $p['cta'] ?></a><a class="more" href="<?= url($tools[$k][2]) ?>">Come funziona →</a></div>
                    </div>
                    <div class="mock" aria-hidden="true"><?= $p['mock'] ?></div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <p class="micro" style="text-align:left;margin-top:18px">Apri una card col + per vedere cosa ti ritrovi in mano e quanto costa.</p>
<?php else: ?>
        <div class="groups">
            <?php foreach ($groups as [$gLabel, $keys]): ?>
            <div class="group"><small><?= $gLabel ?></small><div class="tabs" role="tablist">
                <?php foreach ($keys as $k): ?>
                <button class="tab" role="tab" id="tab-<?= $k ?>" aria-controls="p-<?= $k ?>" aria-selected="<?= $k === 'geo' ? 'true' : 'false' ?>" data-t="<?= $k ?>"><?= $tile($C[$k], $icons[$k]) ?><?= $tools[$k][1] ?></button>
                <?php endforeach; ?>
            </div></div>
            <?php endforeach; ?>
        </div>
        <?php foreach ($panels as $k => $p): ?>
        <div class="panel<?= $k === 'geo' ? ' on' : '' ?>" id="p-<?= $k ?>" role="tabpanel" aria-labelledby="tab-<?= $k ?>">
            <div>
                <span class="chip" style="background:<?= $p['chip'][0] ?>;color:<?= $p['chip'][1] ?>"><?= $tools[$k][0] ?></span>
                <h3><?= $p['h3'] ?></h3>
                <p class="when"><?= $p['when'] ?></p>
                <ul><?php foreach ($p['li'] as $li): ?><li><?= $li ?></li><?php endforeach; ?></ul>
                <p class="cost"><?= $p['cost'] ?></p>
                <div class="acts"><a class="btn btn-blue" href="<?= url('/register') ?>"><?= $p['cta'] ?></a><a class="more" href="<?= url($tools[$k][2]) ?>">Come funziona →</a></div>
            </div>
            <div class="mock" aria-hidden="true"><?= $p['mock'] ?></div>
        </div>
        <?php endforeach; ?>
<?php endif; ?>
    </div>
</section>
