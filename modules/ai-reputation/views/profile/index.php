<?php
$currentPage = 'profile';
include __DIR__ . '/../partials/project-nav.php';
$basePath = '/ai-reputation/project/' . $project['id'];
$csrf = csrf_token();
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
$statusChip = fn(string $s) => match ($s) {
    'confirmed' => ['confermata', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'],
    'corrected' => ['corretta', 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300'],
    'rejected' => ['rifiutata · da monitorare', 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'],
    default => ['da confermare', 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'],
};
$hasFacts = $counts['total'] > 0;
?>

<div class="space-y-6" x-data="arProfile()">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Profilo di <?= e($project['subject_name']) ?></h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                La "verità" con cui il judge confronta le risposte delle AI. Conferma riga per riga: ✅ vero · ✏️ correggi · ❌ falso (diventa un errore da monitorare).
            </p>
            <p class="text-xs text-slate-400 mt-1"><?= $counts['confirmed'] + $counts['corrected'] ?> confermate · <?= $counts['proposed'] ?> da confermare · <?= $counts['rejected'] ?> rifiutate</p>
        </div>
        <?php if ($canEdit): ?>
        <div class="flex items-center gap-2">
            <button type="button" @click="generate()" :disabled="busy" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-60 transition-colors whitespace-nowrap">
                <svg x-show="!busy" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                <svg x-show="busy" x-cloak class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                <span x-text="busy ? 'Cerco e leggo le fonti…' : '<?= $hasFacts ? 'Rigenera bozza' : 'Genera il profilo' ?>'"></span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div x-show="message" x-cloak class="rounded-xl px-5 py-3 text-sm" :class="error ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'" x-text="message"></div>

    <?php if (!$hasFacts): ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-10 text-center">
        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessuna riga di profilo</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-lg mx-auto">
            "Genera il profilo" cerca il soggetto online<?= $perplexityOk ? '' : ' (serve la key Perplexity per la ricerca: senza, usa solo il sito ufficiale)' ?>, legge le fonti e propone 15-35 affermazioni da confermare. Circa un minuto.
        </p>
    </div>
    <?php endif; ?>

    <?php foreach ($categories as $cat => $label): ?>
    <?php $rows = $grouped[$cat] ?? []; if (empty($rows)) continue; ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white"><?= e($label) ?> <span class="text-slate-400 font-normal">· <?= count($rows) ?></span></h3>
            <?php if ($cat === 'homonym'): ?><span class="text-xs text-slate-400">"È lui" = nessun omonimo · "È un altro" = omonimo confermato</span><?php endif; ?>
        </div>
        <ul class="divide-y divide-slate-200 dark:divide-slate-700">
            <?php foreach ($rows as $f): ?>
            <?php [$sl, $sc] = $statusChip($f['status']); ?>
            <li class="px-5 py-3 flex flex-wrap items-start gap-3 <?= $f['status'] === 'rejected' ? 'opacity-70' : '' ?>" x-data="{ editing: false }">
                <div class="flex-1 min-w-[16rem] text-sm">
                    <p class="text-slate-900 dark:text-white <?= $f['status'] === 'rejected' ? 'line-through' : '' ?>"><?= e($f['text']) ?></p>
                    <?php if ($f['status'] === 'corrected' && $f['corrected_text']): ?>
                    <p class="text-sky-700 dark:text-sky-300 mt-0.5">→ <?= e($f['corrected_text']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs text-slate-400 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium <?= $sc ?>"><?= $sl ?></span>
                        <?php if (!empty($f['source_url'])): ?> · <a href="<?= e($f['source_url']) ?>" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline"><?= e(\Modules\AiReputation\Services\EngineCollectorService::domainOf($f['source_url']) ?? 'fonte') ?></a><?php endif; ?>
                        <?php if ($f['origin'] === 'run'): ?> · emersa da un run<?php endif; ?>
                    </p>
                    <form x-show="editing" x-cloak method="POST" action="<?= url("{$basePath}/profile/facts/{$f['id']}/correct") ?>" class="mt-2 flex gap-2">
                        <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                        <input type="text" name="corrected_text" value="<?= e($f['corrected_text'] ?? $f['text']) ?>" class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium">Salva</button>
                        <button type="button" @click="editing = false" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs">Annulla</button>
                    </form>
                </div>
                <?php if ($canEdit): ?>
                <div class="flex items-center gap-1">
                    <?php if ($f['category'] === 'homonym'): ?>
                        <?php if ($f['status'] === 'proposed'): ?>
                        <form method="POST" action="<?= url("{$basePath}/profile/facts/{$f['id']}/homonym-yes") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><button type="submit" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white text-xs font-medium hover:bg-slate-700">È lui</button></form>
                        <form method="POST" action="<?= url("{$basePath}/profile/facts/{$f['id']}/homonym-no") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><button type="submit" class="px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700">È un altro</button></form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($f['status'] !== 'confirmed'): ?>
                        <form method="POST" action="<?= url("{$basePath}/profile/facts/{$f['id']}/confirm") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><button type="submit" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30" title="Vero"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg></button></form>
                        <?php endif; ?>
                        <button type="button" @click="editing = !editing" class="p-1.5 rounded-lg text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/30" title="Correggi"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg></button>
                        <?php if ($f['status'] !== 'rejected'): ?>
                        <form method="POST" action="<?= url("{$basePath}/profile/facts/{$f['id']}/reject") ?>"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" title="Falso: errore da monitorare"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button></form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <form method="POST" action="<?= url("{$basePath}/profile/facts/{$f['id']}/delete") ?>" onsubmit="return confirm('Eliminare la riga?')"><input type="hidden" name="_csrf_token" value="<?= $csrf ?>"><button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="Elimina"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg></button></form>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>

    <?php if ($canEdit): ?>
    <form method="POST" action="<?= url("{$basePath}/profile/facts") ?>" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 px-5 py-4 flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
        <select name="category" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            <?php foreach ($categories as $cat => $label): ?><option value="<?= $cat ?>"><?= e($label) ?></option><?php endforeach; ?>
        </select>
        <input type="text" name="text" required minlength="3" placeholder="Aggiungi un'affermazione vera (es. «Ha fondato X nel 2015»)" class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-900/30 text-sm font-medium">Aggiungi</button>
    </form>
    <?php endif; ?>
</div>

<script>
function arProfile() {
    const base = '<?= url($basePath) ?>';
    const csrf = '<?= $csrf ?>';
    return {
        busy: false, message: '', error: false,
        async generate() {
            if (<?= $hasFacts ? 'true' : 'false' ?> && !confirm('Rigenerare la bozza? Le righe AI ancora da confermare vengono sostituite; quelle confermate, corrette o rifiutate restano.')) return;
            this.busy = true; this.message = ''; this.error = false;
            try {
                const fd = new FormData(); fd.append('_csrf_token', csrf);
                const resp = await fetch(base + '/profile/generate', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Errore');
                this.message = 'Bozza pronta: ' + data.facts + ' righe da ' + data.sources + ' fonti (' + data.fetched + ' pagine lette). Ricarico…';
                setTimeout(() => location.reload(), 900);
            } catch (e) { this.error = true; this.message = e.message; this.busy = false; }
        },
    };
}
</script>
