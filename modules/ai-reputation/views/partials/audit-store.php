<?php
/**
 * Store Alpine `arAudit` condiviso tra Overview e scheda Domande: tipo di audit scelto, stima del prossimo audit,
 * conteggio delle domande attive. La scelta del tipo si salva in sottofondo.
 * Variabili: $project, $prompts, $auditType, $runEstimate, $csrf, $basePath
 */
// Solo le domande dell'audit scelto: quelle dell'altro tipo non entrano nell'audit e confondono (feedback Clemente 2026-10-13)
$promptsMeta = array_map(fn($p) => ['id' => (string) $p['id'], 'set' => ($p['audit_set'] ?? 'reputation') === 'purchase' ? 'purchase' : 'reputation', 'active' => (int) $p['is_active'] === 1], $prompts);
?>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('arAudit', {
        type: <?= json_encode($auditType) ?>,
        est: <?= json_encode($runEstimate) ?>,
        prompts: <?= json_encode($promptsMeta, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        saving: false,
        sets() { return this.type === 'both' ? ['purchase', 'reputation'] : [this.type === 'purchase' ? 'purchase' : 'reputation']; },
        inSet(set) { return this.sets().includes(set); },
        shown() { return this.prompts.filter(p => this.inSet(p.set)); },
        shownCount() { return this.shown().length; },
        activeCount() { return this.shown().filter(p => p.active).length; },
        hiddenCount() { return this.prompts.length - this.shownCount(); },
        label() { return { purchase: 'Acquisto', reputation: 'Reputazione', both: 'Acquisto + Reputazione' }[this.type] || this.type; },
        hint() {
            const h = { purchase: 'Domande di chi cerca il servizio, quasi tutte senza il tuo nome.', reputation: 'Domande su di te: affidabilità, recensioni, notizie.', both: 'Acquisto e reputazione insieme: l’audit costa di più.' };
            return (h[this.type] || '') + ' L’audit usa solo le domande di questo tipo.';
        },
        fmt(n) { return String(Math.round(n * 10) / 10).replace('.', ','); },
        async setType(val) {
            if (val === this.type || this.saving) return;
            const prev = this.type;
            this.type = val; this.saving = true;
            try {
                const fd = new FormData(); fd.append('_csrf_token', <?= json_encode($csrf) ?>); fd.append('audit_type', val); fd.append('ajax', '1');
                const resp = await fetch(<?= json_encode(url($basePath . '/audit-type')) ?>, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
                if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
                const data = await resp.json();
                if (!data.success) throw new Error(data.error || 'Non salvato');
                this.est = data.estimate;
            } catch (e) {
                this.type = prev;
                if (window.ainstein && window.ainstein.alert) { window.ainstein.alert('Tipo di audit non salvato: ' + e.message, 'error'); } else { alert(e.message); }
            } finally { this.saving = false; }
        },
    });
});
</script>
