<?php
/**
 * Scheda operativa di un intervento (report web). Variabili: $a (riga ar_actions), $basePath, $run, $csrf, $canEdit, $briefCost.
 * Usato nel report e come HTML di risposta dell'AJAX "Genera scheda".
 */
$brief = is_string($a['brief'] ?? null) ? (json_decode($a['brief'], true) ?: null) : null;
$outlets = is_string($a['suggested_outlets'] ?? null) ? (json_decode($a['suggested_outlets'], true) ?: []) : [];
$channelLabel = ['own_site' => 'Sito proprietario', 'external' => 'Siti esterni', 'both' => 'Sito proprietario + esterni'];
$channelClass = ['own_site' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300', 'external' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300', 'both' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'];
$requestLabel = ['removal' => 'Rimozione della pagina', 'deindex' => 'Deindicizzazione', 'update' => 'Aggiornamento del contenuto'];
$briefUrl = url("{$basePath}/runs/{$run['id']}/actions/{$a['id']}/brief");
$btn = fn(string $label, bool $primary) => '<button type="button" @click="generateBrief(' . (int) $a['id'] . ', $event, ' . ($brief ? 'true' : 'false') . ')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium transition-colors '
    . ($primary ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700') . '">'
    . '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>' . $label . '</button>';
?>
<div class="mt-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-700" data-brief-url="<?= e($briefUrl) ?>" id="brief-<?= (int) $a['id'] ?>">
<?php if (!$brief): ?>
    <?php if (!empty($a['brief_error'])): ?>
    <p class="text-xs text-red-600 dark:text-red-400 mb-1">Generazione non riuscita: <?= e($a['brief_error']) ?></p>
    <?php endif; ?>
    <?php if ($canEdit): ?>
    <div class="flex items-center gap-2">
        <?= $btn(!empty($a['brief_error']) ? 'Riprova' : 'Genera scheda', true) ?>
        <span class="text-xs text-slate-400"><?= rtrim(rtrim(number_format((float) $briefCost, 1, ',', ''), '0'), ',') ?> credit<?= (float) $briefCost == 1 ? 'o' : 'i' ?> · 20-40 s · decide dove pubblicare e scrive il brief</span>
    </div>
    <?php else: ?>
    <p class="text-xs text-slate-400">Scheda operativa non ancora generata.</p>
    <?php endif; ?>
<?php else: ?>
    <?php if ($brief['kind'] === 'content'): ?>
    <div class="flex flex-wrap items-center gap-2 mb-1">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Dove:</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $channelClass[$a['channel']] ?? '' ?>"><?= e($channelLabel[$a['channel']] ?? $a['channel']) ?></span>
        <?php foreach ($outlets as $o): ?><a href="https://<?= e($o) ?>" target="_blank" rel="noopener" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"><?= e($o) ?></a><?php endforeach; ?>
    </div>
    <?php if ($a['channel_rationale']): ?><p class="text-xs text-slate-600 dark:text-slate-300 mb-2"><?= e($a['channel_rationale']) ?></p><?php endif; ?>
    <div class="text-xs space-y-1.5 text-slate-700 dark:text-slate-200">
        <p><span class="font-medium">Titolo proposto:</span> <?= e($brief['title']) ?></p>
        <?php if ($brief['angle']): ?><p><span class="font-medium">Taglio:</span> <?= e($brief['angle']) ?></p><?php endif; ?>
        <div><span class="font-medium">Punti da coprire:</span><ul class="list-disc ml-4 mt-0.5"><?php foreach ($brief['points'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul></div>
        <?php if ($brief['facts']): ?><div><span class="font-medium">Fatti da citare:</span><ul class="list-disc ml-4 mt-0.5"><?php foreach ($brief['facts'] as $f): ?><li><?= e($f['fact']) ?><?php if ($f['source']): ?> <span class="text-slate-400">(fonte: <?= e($f['source']) ?>)</span><?php endif; ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($brief['avoid']): ?><div><span class="font-medium">Da evitare:</span><ul class="list-disc ml-4 mt-0.5"><?php foreach ($brief['avoid'] as $v): ?><li><?= e($v) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <p><span class="font-medium">Lunghezza:</span> <?= $brief['length_words'] ? (int) $brief['length_words'] . ' parole' : 'a discrezione' ?> · <span class="font-medium">Lingua:</span> <?= e(strtoupper($brief['language'])) ?></p>
        <?php if ($brief['own_site_note']): ?><p><span class="font-medium">Sul sito ufficiale:</span> <?= e($brief['own_site_note']) ?></p><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="text-xs space-y-1.5 text-slate-700 dark:text-slate-200">
        <p><span class="font-medium">A chi scrivere:</span> <?= e($brief['recipient']) ?></p>
        <p><span class="font-medium">Cosa chiedere:</span> <?= e($requestLabel[$brief['request']] ?? $brief['request']) ?></p>
        <p><span class="font-medium">Su quale base:</span> <?= e($brief['basis']) ?></p>
        <?php if ($brief['pages']): ?><div><span class="font-medium">Pagine:</span><ul class="ml-4 mt-0.5 list-disc"><?php foreach ($brief['pages'] as $u): ?><li><a href="<?= e($u) ?>" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline break-all"><?= e(mb_strimwidth($u, 0, 90, '…')) ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if ($brief['fallback']): ?><p><span class="font-medium">Se rifiutano:</span> <?= e($brief['fallback']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="mt-2 flex items-center gap-2 text-[11px] text-slate-400">
        Scheda generata il <?= date('d/m/Y H:i', strtotime((string) $a['brief_generated_at'])) ?> con <?= e((string) $a['brief_model']) ?>
        <?php if ($canEdit): ?>· <?= $btn('Rigenera', false) ?><?php endif; ?>
    </div>
<?php endif; ?>
</div>
