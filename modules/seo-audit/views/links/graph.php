<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$basePath = '/seo-audit/project/' . ($project['id'] ?? 0) . '/links';
?>

<!-- Sub-navigation -->
<div class="flex items-center gap-2 mb-6">
    <a href="<?= url($basePath) ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Panoramica</a>
    <a href="<?= url($basePath . '/orphans') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Pagine Orfane</a>
    <a href="<?= url($basePath . '/anchors') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">Anchor Text</a>
    <a href="<?= url($basePath . '/graph') ?>" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">Grafo</a>
</div>

<?php if (empty($graphData['nodes'])): ?>
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
    </svg>
    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Nessun dato disponibile</h3>
    <p class="text-slate-500 dark:text-slate-400">Avvia una scansione per visualizzare il grafo dei link.</p>
</div>
<?php else: ?>

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Grafo Link Interni</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= count($graphData['nodes']) ?> pagine, <?= count($graphData['edges']) ?> collegamenti</p>
        </div>
        <div class="text-xs text-slate-500 dark:text-slate-400">
            Trascina per spostare, scroll per zoom
        </div>
    </div>
    <div id="link-graph" style="height: 600px; width: 100%;"></div>
</div>

<!-- Vis.js CDN -->
<link href="https://unpkg.com/vis-network@9.1.6/dist/dist/vis-network.min.css" rel="stylesheet" />
<script src="https://unpkg.com/vis-network@9.1.6/dist/vis-network.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('link-graph');
    const isDark = document.documentElement.classList.contains('dark');

    const nodes = new vis.DataSet(<?= json_encode($graphData['nodes']) ?>);
    const edges = new vis.DataSet(<?= json_encode($graphData['edges']) ?>);

    const options = {
        nodes: {
            shape: 'dot',
            font: {
                size: 12,
                color: isDark ? '#e2e8f0' : '#334155',
            },
            color: {
                background: isDark ? '#6366f1' : '#818cf8',
                border: isDark ? '#4f46e5' : '#6366f1',
                highlight: {
                    background: '#f59e0b',
                    border: '#d97706',
                },
            },
            scaling: {
                min: 10,
                max: 40,
            },
        },
        edges: {
            color: {
                color: isDark ? '#475569' : '#cbd5e1',
                highlight: '#f59e0b',
            },
            arrows: { to: { enabled: true, scaleFactor: 0.5 } },
            smooth: { type: 'curvedCW', roundness: 0.2 },
        },
        physics: {
            stabilization: { iterations: 150 },
            barnesHut: {
                gravitationalConstant: -3000,
                springLength: 150,
                springConstant: 0.04,
            },
        },
        interaction: {
            hover: true,
            tooltipDelay: 200,
        },
    };

    new vis.Network(container, { nodes, edges }, options);
});
</script>

<?php endif; ?>
