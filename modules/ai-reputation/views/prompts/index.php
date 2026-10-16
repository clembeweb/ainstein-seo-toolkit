<?php
$currentPage = 'prompts';
include __DIR__ . '/../partials/project-nav.php';
$csrf = csrf_token();
$basePath = '/ai-reputation/project/' . $project['id'];
$canEdit = ($project['access_role'] ?? 'owner') !== 'viewer';
$kindLabel = [
    'nav' => ['Chi è', 'Domande per sapere chi è e dove si trova', 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'],
    'rep' => ['Reputazione', 'È affidabile? Recensioni, problemi, notizie', 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300'],
    'comm' => ['Ti sta valutando', 'Il cliente conosce già il nome e valuta prezzi, servizi, condizioni', 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'],
    'comp' => ['Cerca il servizio', 'Il cliente cerca il servizio senza conoscerti: le AI citano te o i concorrenti?', 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300'],
];
?>
<div class="space-y-6">
    <?php if (!$profileConfirmed): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
        Le domande migliori nascono dal profilo confermato. <a href="<?= url($basePath . '/profile') ?>" class="font-medium underline">Conferma prima il profilo</a>, poi genera le domande.
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Domande</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Le domande che la gente fa alle AI su <?= e($project['subject_name']) ?>. L'AI le scrive dal <a href="<?= url($basePath . '/profile') ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline">profilo confermato</a>, oppure aggiungile a mano o parti dalle 8 base.</p>
            </div>
            <?php if ($canEdit): ?>
            <div class="flex flex-wrap items-center justify-end gap-2" x-data="arPromptGen()">
                <?php foreach (['purchase', 'reputation'] as $set): $genLabel = $set === 'purchase' ? 'Genera domande d&#039;acquisto' : 'Genera domande sulla reputazione'; ?>
                <button type="button" x-show="$store.arAudit.inSet('<?= $set ?>')" @click="generate('<?= $set ?>')" :disabled="busy" class="inline-flex items-center px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 text-sm font-medium transition-colors whitespace-nowrap" title="<?= $set === 'purchase' ? 'Scrive 30 domande che un cliente fa alle AI quando cerca il servizio, dal profilo e dal sito' : 'Scrive 40 domande sulla reputazione dal profilo confermato' ?>">
                    <svg x-show="busy === '<?= $set ?>'" x-cloak class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span x-show="busy !== '<?= $set ?>'"><?= $genLabel ?></span><span x-show="busy === '<?= $set ?>'" x-cloak>Genero…</span>
                </button>
                <?php endforeach; ?>
                <form method="POST" action="<?= url($basePath . '/prompts/seed') ?>" x-show="$store.arAudit.inSet('reputation')">
                    <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-300 dark:hover:bg-indigo-900/30 text-sm font-medium transition-colors whitespace-nowrap">
                        + Domande base
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?= url($basePath . '/audit-type') ?>" class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 flex flex-wrap items-center gap-3">
            <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
            <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Che audit vuoi fare?</span>
            <div class="flex rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden text-sm">
                <?php foreach (['purchase' => ['Acquisto', 'Quando un cliente cerca il servizio, le AI citano te o i concorrenti?'], 'reputation' => ['Reputazione', 'Cosa dicono le AI di te: affidabilità, rischi, dubbi'], 'both' => ['Entrambi', 'Le due serie di domande nello stesso audit']] as $val => [$lbl, $hint]): ?>
                <button type="submit" name="audit_type" value="<?= $val ?>" title="<?= e($hint) ?>" @click.prevent="$store.arAudit.setType('<?= $val ?>')" :disabled="$store.arAudit.saving || !<?= $canEdit ? 'true' : 'false' ?>" class="px-3 py-1.5 transition-colors" :class="$store.arAudit.type === '<?= $val ?>' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-600'"><?= $lbl ?></button>
                <?php endforeach; ?>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400" x-text="$store.arAudit.hint()"></span>
            <span class="text-xs text-indigo-600 dark:text-indigo-400" x-show="$store.arAudit.saving" x-cloak>salvo…</span>
        </form>

        <?php if ($canEdit): ?>
        <form method="POST" action="<?= url($basePath . '/prompts') ?>" x-data="{ addSet: 'purchase' }" class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
            <select name="cluster" class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <?php foreach ($clusters as $key => $label): ?>
                <option value="<?= $key ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="audit_set" :value="$store.arAudit.type === 'both' ? addSet : ($store.arAudit.type === 'purchase' ? 'purchase' : 'reputation')">
            <select x-model="addSet" x-show="$store.arAudit.type === 'both'" x-cloak class="rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
                <option value="purchase">Audit acquisto</option>
                <option value="reputation">Audit reputazione</option>
            </select>
            <input type="text" name="text" required minlength="5" :placeholder="$store.arAudit.type === 'reputation' ? <?= json_encode('Es. ' . $project['subject_name'] . ' è affidabile?', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?> : 'Es. Dove posso … vicino a …?'" class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white text-sm">
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors">Aggiungi</button>
        </form>
        <?php endif; ?>

        <div x-show="$store.arAudit.shownCount() === 0" x-cloak class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">
            Nessuna domanda per l'audit "<span x-text="$store.arAudit.label()"></span>". Clicca "<span x-text="$store.arAudit.inSet('purchase') ? 'Genera domande d\'acquisto' : 'Genera domande sulla reputazione'"></span>" qui sopra.
            <template x-if="$store.arAudit.hiddenCount() > 0"><span><br><span class="text-xs">Ci sono <span x-text="$store.arAudit.hiddenCount()"></span> domande dell'altro tipo di audit: non entrano in questo audit.</span></span></template>
        </div>
        <div x-show="$store.arAudit.shownCount() > 0" x-data="arPromptBulk()">
            <div class="px-5 py-2.5 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                <span><strong class="text-slate-700 dark:text-slate-200" x-text="$store.arAudit.activeCount() + ' domande attive'"></strong> su <span x-text="$store.arAudit.shownCount()"></span>: l'audit usa solo quelle attive. Per una prova veloce selezionane alcune e disattiva le altre.</span>
                <span x-show="$store.arAudit.hiddenCount() > 0" x-cloak><span x-text="$store.arAudit.hiddenCount()"></span> domande dell'altro tipo di audit sono nascoste e non entrano nell'audit.</span>
            </div>
            <?php if ($canEdit): ?>
            <div x-show="selectedIds.length > 0" x-cloak class="px-5 py-2.5 flex flex-wrap items-center gap-2 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800">
                <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300 mr-2"><span x-text="selectedIds.length"></span> selezionate</span>
                <button type="button" @click="bulk('deactivate')" class="px-3 py-1.5 rounded-lg bg-slate-600 text-white text-sm font-medium hover:bg-slate-700">Disattiva</button>
                <button type="button" @click="bulk('activate')" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Attiva</button>
                <button type="button" @click="bulk('only')" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700" title="Attiva le selezionate e disattiva tutte le altre di questo audit">Usa solo queste</button>
                <button type="button" @click="bulk('delete')" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">Elimina</button>
                <button type="button" @click="selectedIds = []" class="px-3 py-1.5 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400">Annulla selezione</button>
            </div>
            <?php endif; ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <?php if ($canEdit): ?>
                            <th class="w-10 px-4 py-3"><input type="checkbox" @change="toggleAll($event)" :checked="selectedIds.length > 0 && selectedIds.length === allIds().length" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500" title="Seleziona tutte"></th>
                            <?php endif; ?>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Domanda</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Attiva</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($prompts as $p): $k = $kindLabel[$p['cluster']] ?? $kindLabel['nav']; ?>
                        <tr x-show="$store.arAudit.inSet('<?= ($p['audit_set'] ?? 'reputation') === 'purchase' ? 'purchase' : 'reputation' ?>')" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 <?= (int) $p['is_active'] ? '' : 'opacity-50' ?>">
                            <?php if ($canEdit): ?>
                            <td class="px-4 py-3"><input type="checkbox" value="<?= (int) $p['id'] ?>" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500"></td>
                            <?php endif; ?>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $k[2] ?>" title="<?= e($k[1]) ?>"><?= $k[0] ?></span></td>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php if (($p['lang'] ?? 'it') === 'en'): ?><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-2 bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300" title="Domanda in inglese">EN</span><?php endif; ?><?php if ((int) ($p['is_leading'] ?? 0) === 1): ?><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-2 bg-slate-200 text-slate-700 dark:bg-slate-600 dark:text-slate-200" title="Nomina già un fatto negativo: nel report sta in un blocco a parte, fuori dal rischio">mirata</span><?php endif; ?><?= e($p['text']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($canEdit): ?>
                                <form method="POST" action="<?= url($basePath . '/prompts/' . $p['id'] . '/toggle') ?>">
                                    <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="text-xs font-medium <?= (int) $p['is_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>" title="Attiva/disattiva">
                                        <?= (int) $p['is_active'] ? 'Sì' : 'No' ?>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-xs font-medium <?= (int) $p['is_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' ?>"><?= (int) $p['is_active'] ? 'Sì' : 'No' ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($canEdit): ?>
                                <form method="POST" action="<?= url($basePath . '/prompts/' . $p['id'] . '/delete') ?>" data-confirm="Eliminare questa domanda?" data-confirm-button="Elimina" data-confirm-destructive="1">
                                    <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="text-slate-400 hover:text-red-600" title="Elimina">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($promptsActive > 0): ?>
    <div class="flex items-center justify-between gap-4 rounded-xl border border-indigo-200 bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-900/20 px-5 py-4">
        <p class="text-sm text-slate-700 dark:text-slate-200">Domande pronte? L'audit si avvia dalla Panoramica: prima vedi quante risposte e quanti crediti.</p>
        <a href="<?= url($basePath) ?>" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 whitespace-nowrap">Vai all'Overview →</a>
    </div>
    <?php endif; ?>
</div>

<?= \Core\View::partial('ai-reputation::partials/audit-store', ['project' => $project, 'prompts' => $prompts, 'auditType' => $auditType, 'runEstimate' => $runEstimate, 'csrf' => $csrf, 'basePath' => $basePath]) ?>

<script>
function arPromptBulk() {
    const base = '<?= url($basePath) ?>';
    const csrf = '<?= $csrf ?>';
    return {
        selectedIds: [],
        allIds() { return this.$store.arAudit.shown().map(p => p.id); },
        toggleAll(ev) { this.selectedIds = ev.target.checked ? [...this.allIds()] : []; },
        async bulk(action) {
            const n = this.selectedIds.length;
            if (action === 'delete' && !(await window.arConfirm('Eliminare ' + n + ' domande? Quelle già usate in un audit vengono solo disattivate, così i report passati restano intatti.', { buttonText: 'Elimina', destructive: true }))) return;
            const fd = new FormData(); fd.append('_csrf_token', csrf); fd.append('action', action);
            this.selectedIds.forEach(id => fd.append('ids[]', id));
            try {
                const resp = await fetch(base + '/prompts/bulk', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Operazione non riuscita');
                location.reload();
            } catch (e) {
                if (window.ainstein && window.ainstein.alert) { window.ainstein.alert(e.message, 'error'); } else { alert(e.message); }
            }
        },
    };
}

function arPromptGen() {
    const base = '<?= url($basePath) ?>';
    const csrf = '<?= $csrf ?>';
    return {
        busy: false,
        async generate(set) {
            this.busy = set;
            try {
                const fd = new FormData(); fd.append('_csrf_token', csrf); fd.append('set', set); fd.append('target', set === 'purchase' ? '30' : '40');
                const resp = await fetch(base + '/prompts/generate', { method: 'POST', body: fd });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Errore');
                location.reload();
            } catch (e) { alert(e.message); this.busy = false; }
        },
    };
}
</script>
