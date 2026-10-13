<?php
/**
 * "Attiva su un progetto esistente" — da mettere accanto a "Nuovo Progetto" nella pagina iniziale di ogni modulo
 * (richiesta di Clemente, 2026-10-13): oggi da lì si può solo creare un progetto nuovo.
 *
 * Variabili:
 *   $moduleSlug - slug del modulo (chiave di GlobalProject::MODULE_CONFIG)
 *   $userId     - utente corrente (si elencano i suoi progetti, solo quelli di cui è proprietario)
 *   $color      - colore Tailwind del modulo per il bottone (default: primary)
 *
 * Elenca i progetti globali in cui il modulo (o, per i moduli tipizzati, almeno un tipo) non è ancora attivo
 * e invia lo stesso POST /projects/{id}/activate-module usato dalla dashboard del progetto.
 */
$moduleSlug = $moduleSlug ?? '';
$userId = (int) ($userId ?? 0);
$color = $color ?? 'primary';
if ($moduleSlug === '' || $userId <= 0) {
    return;
}
$gp = new \Core\Models\GlobalProject();
$moduleTypes = $gp->getModuleTypes($moduleSlug); // [] se il modulo non ha tipi
$typeKeys = array_keys($moduleTypes);
$choices = [];
foreach ($gp->allByUser($userId) as $p) {
    if (($p['status'] ?? 'active') === 'archived') {
        continue;
    }
    $activeTypes = [];
    foreach ($gp->getActiveModules((int) $p['id']) as $m) {
        if ($m['slug'] === $moduleSlug) {
            $activeTypes[] = (string) ($m['type'] ?? '');
        }
    }
    if ($moduleTypes) {
        $free = array_values(array_diff($typeKeys, $activeTypes));
        if (!$free) {
            continue; // tutti i tipi già attivi
        }
    } elseif ($activeTypes) {
        continue; // modulo già attivo
    }
    $choices[] = [
        'id' => (int) $p['id'],
        'name' => (string) $p['name'],
        'domain' => (string) ($p['domain'] ?? ''),
        'active_types' => $activeTypes,
    ];
}
if (!$choices) {
    return; // niente progetti dove attivarlo: resta solo "Nuovo Progetto"
}
$uid = 'mae' . substr(md5($moduleSlug), 0, 6);
$actionBase = url('/projects');
?>
<div x-data="{ open: false, pid: '', type: '<?= e($typeKeys[0] ?? '') ?>', typeKeys: <?= e(json_encode($typeKeys)) ?>, choices: <?= e(json_encode($choices, JSON_UNESCAPED_UNICODE)) ?>,
               activeTypes() { const c = this.choices.find(x => String(x.id) === String(this.pid)); return c ? c.active_types : []; },
               pickFreeType() { if (this.activeTypes().includes(this.type)) { this.type = this.typeKeys.find(k => !this.activeTypes().includes(k)) || this.type; } } }"
     class="relative" @keydown.escape.window="open = false" @click.outside="open = false">
    <button type="button" @click="open = !open"
            class="inline-flex items-center px-4 py-2 rounded-lg border border-<?= $color ?>-300 dark:border-<?= $color ?>-700 text-<?= $color ?>-700 dark:text-<?= $color ?>-300 font-medium hover:bg-<?= $color ?>-50 dark:hover:bg-<?= $color ?>-900/30 transition-colors whitespace-nowrap"
            title="Usa un progetto che hai già invece di crearne uno nuovo">
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
        Attiva su un progetto
    </button>
    <div x-show="open" x-cloak x-transition
         class="absolute right-0 z-30 mt-2 w-80 rounded-xl bg-white dark:bg-slate-800 shadow-lg border border-slate-200 dark:border-slate-700 p-4">
        <form method="POST" :action="'<?= $actionBase ?>/' + pid + '/activate-module'" @submit="if (!pid) { $event.preventDefault(); }">
            <?= csrf_field() ?>
            <input type="hidden" name="module" value="<?= e($moduleSlug) ?>">
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Progetto</label>
            <select x-model="pid" @change="pickFreeType()" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <option value="">Scegli un progetto…</option>
                <?php foreach ($choices as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?><?= $c['domain'] !== '' ? ' · ' . e($c['domain']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($moduleTypes): ?>
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mt-3 mb-1">Cosa vuoi fare</label>
            <div class="space-y-1.5">
                <?php foreach ($moduleTypes as $key => $t): ?>
                <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200" :class="activeTypes().includes('<?= e($key) ?>') ? 'opacity-50' : 'cursor-pointer'">
                    <input type="radio" name="type" value="<?= e($key) ?>" x-model="type" :disabled="activeTypes().includes('<?= e($key) ?>')" class="mt-0.5 border-slate-300 dark:border-slate-600 text-<?= $color ?>-600 focus:ring-<?= $color ?>-500">
                    <span><span class="font-medium"><?= e($t['label']) ?></span><span class="block text-xs text-slate-500 dark:text-slate-400"><?= e($t['description'] ?? '') ?></span><span x-show="activeTypes().includes('<?= e($key) ?>')" x-cloak class="block text-xs text-slate-400">già attivo su questo progetto</span></span>
                </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <button type="submit" :disabled="!pid<?= $moduleTypes ? " || activeTypes().includes(type)" : '' ?>" class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-<?= $color ?>-600 text-white text-sm font-medium hover:bg-<?= $color ?>-700 disabled:opacity-50 transition-colors">Attiva</button>
        </form>
    </div>
</div>
