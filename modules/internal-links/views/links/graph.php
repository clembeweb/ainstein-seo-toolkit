<!-- Link Graph Visualization -->
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="<?= url("/internal-links/project/{$project['id']}/links") ?>" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 mb-2 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Torna ai Link
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Grafico Link</h1>
            <p class="mt-1 text-slate-500 dark:text-slate-400">
                Rappresentazione visiva della struttura dei link interni
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="resetZoom()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                Reimposta Vista
            </button>
        </div>
    </div>

    <!-- Legend -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <span class="font-medium text-slate-700 dark:text-slate-300">Legenda:</span>
            <span class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-primary-500"></span>
                Nodo = URL
            </span>
            <span class="flex items-center gap-2">
                <span class="w-8 h-0.5 bg-slate-400"></span>
                Linea link
            </span>
            <span class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-primary-500 opacity-80"></span>
                Piu link in entrata = piu grande
            </span>
            <span class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full bg-red-500"></span>
                Orfano (nessun link in entrata)
            </span>
        </div>
    </div>

    <!-- Graph Container -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div id="graph-container" style="height: 600px; width: 100%;"></div>
    </div>

    <!-- Node Info Panel -->
    <div id="node-info" class="hidden bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Dettagli Nodo</h3>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-slate-500">URL</label>
                <p id="node-url" class="font-medium text-slate-900 dark:text-white break-all"></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-slate-500">Link in Entrata</label>
                    <p id="node-incoming" class="text-2xl font-bold text-green-600"></p>
                </div>
                <div>
                    <label class="text-sm text-slate-500">Link in Uscita</label>
                    <p id="node-outgoing" class="text-2xl font-bold text-blue-600"></p>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <a id="node-link" href="#" target="_blank" class="inline-flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700">
                Apri URL
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </div>

    <?php if (empty($graphData['nodes'])): ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 text-center">
        <svg class="w-12 h-12 text-amber-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Nessun Dato per il Grafico</h3>
        <p class="text-amber-700 dark:text-amber-300 mt-2">
            Esegui prima lo scraping degli URL per visualizzare il grafico dei link.
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Vis.js Network -->
<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<script>
const graphData = <?= json_encode($graphData ?? ['nodes' => [], 'edges' => []]) ?>;

let network = null;

function initGraph() {
    if (!graphData.nodes || graphData.nodes.length === 0) return;

    const container = document.getElementById('graph-container');
    const isDark = document.documentElement.classList.contains('dark');

    // Prepare nodes
    const nodes = new vis.DataSet(graphData.nodes.map(node => {
        const isOrphan = node.incoming === 0;
        const size = Math.max(10, Math.min(50, 10 + node.incoming * 2));

        return {
            id: node.id,
            label: node.label,
            title: `${node.url}\nIn entrata: ${node.incoming} | In uscita: ${node.outgoing}`,
            size: size,
            color: {
                background: isOrphan ? '#ef4444' : '#3b82f6',
                border: isOrphan ? '#dc2626' : '#2563eb',
                highlight: {
                    background: isOrphan ? '#f87171' : '#60a5fa',
                    border: isOrphan ? '#ef4444' : '#3b82f6'
                }
            },
            font: {
                color: isDark ? '#e2e8f0' : '#334155',
                size: 10
            },
            url: node.url,
            incoming: node.incoming,
            outgoing: node.outgoing
        };
    }));

    // Prepare edges
    const edges = new vis.DataSet(graphData.edges.map((edge, index) => ({
        id: index,
        from: edge.from,
        to: edge.to,
        arrows: 'to',
        color: {
            color: isDark ? '#475569' : '#cbd5e1',
            highlight: '#3b82f6'
        },
        width: edge.score ? Math.max(1, edge.score / 3) : 1
    })));

    // Network options
    const options = {
        nodes: {
            shape: 'dot',
            scaling: {
                min: 10,
                max: 50
            },
            font: {
                face: 'Inter, system-ui, sans-serif'
            }
        },
        edges: {
            smooth: {
                type: 'continuous',
                roundness: 0.5
            },
            arrows: {
                to: {
                    enabled: true,
                    scaleFactor: 0.5
                }
            }
        },
        physics: {
            enabled: true,
            solver: 'forceAtlas2Based',
            forceAtlas2Based: {
                gravitationalConstant: -50,
                centralGravity: 0.01,
                springLength: 100,
                springConstant: 0.08
            },
            stabilization: {
                enabled: true,
                iterations: 200
            }
        },
        interaction: {
            hover: true,
            tooltipDelay: 200,
            zoomView: true,
            dragView: true
        }
    };

    // Create network
    network = new vis.Network(container, { nodes, edges }, options);

    // Event handlers
    network.on('click', function(params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            const node = nodes.get(nodeId);
            showNodeInfo(node);
        } else {
            hideNodeInfo();
        }
    });

    network.on('stabilizationIterationsDone', function() {
        network.setOptions({ physics: { enabled: false } });
    });
}

function showNodeInfo(node) {
    const panel = document.getElementById('node-info');
    document.getElementById('node-url').textContent = node.url;
    document.getElementById('node-incoming').textContent = node.incoming;
    document.getElementById('node-outgoing').textContent = node.outgoing;
    document.getElementById('node-link').href = node.url;
    panel.classList.remove('hidden');
}

function hideNodeInfo() {
    document.getElementById('node-info').classList.add('hidden');
}

function resetZoom() {
    if (network) {
        network.fit({
            animation: {
                duration: 500,
                easingFunction: 'easeInOutQuad'
            }
        });
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', initGraph);
</script>
