<script>
// Keyword Research - Table Helpers (shared)
const KR_INTENT_COLORS = {
    informational: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    transactional: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    commercial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    navigational: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
};
const KR_INTENT_DEFAULT = 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';

const KR_COMP_COLORS = {
    low: 'text-emerald-600 dark:text-emerald-400',
    medium: 'text-amber-600 dark:text-amber-400',
    high: 'text-red-600 dark:text-red-400',
};
const KR_COMP_DEFAULT = 'text-slate-400';

function krIntentClass(intent) {
    return KR_INTENT_COLORS[(intent || '').toLowerCase()] || KR_INTENT_DEFAULT;
}

function krCompClass(level) {
    return KR_COMP_COLORS[(level || '').toLowerCase()] || KR_COMP_DEFAULT;
}

function krSortIcon(field, currentField, currentDir) {
    if (currentField !== field) {
        return `<svg class="w-3 h-3 ml-1 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>`;
    }
    if (currentDir === 'asc') {
        return `<svg class="w-3 h-3 ml-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>`;
    }
    return `<svg class="w-3 h-3 ml-1 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>`;
}

function krSortArray(arr, field, dir) {
    return [...arr].sort((a, b) => {
        let va = a[field], vb = b[field];
        if (va == null) va = '';
        if (vb == null) vb = '';
        if (typeof va === 'string' && typeof vb === 'string') {
            va = va.toLowerCase();
            vb = vb.toLowerCase();
        }
        if (va < vb) return dir === 'asc' ? -1 : 1;
        if (va > vb) return dir === 'asc' ? 1 : -1;
        return 0;
    });
}

function clusterCard(keywords) {
    return {
        expanded: false,
        keywords: keywords,
        sortField: 'volume',
        sortDir: 'desc',
        toggleSort(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = (field === 'text' || field === 'intent' || field === 'competition_level') ? 'asc' : 'desc';
            }
        },
        get sortedKeywords() {
            return krSortArray(this.keywords, this.sortField, this.sortDir);
        }
    };
}

// Pagination helper - genera array numeri pagina con ellipsis (current ± 2)
function krPageNumbers(currentPage, totalPages) {
    const pages = [];
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(totalPages, currentPage + 2);
    if (start > 1) {
        pages.push(1);
        if (start > 2) pages.push('...');
    }
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    if (end < totalPages) {
        if (end < totalPages - 1) pages.push('...');
        pages.push(totalPages);
    }
    return pages;
}

function architectureTable(clusters) {
    return {
        clusters: clusters,
        sortField: null,
        sortDir: 'asc',
        toggleSort(field) {
            if (field === '_index') {
                this.sortField = null;
                this.sortDir = 'asc';
                return;
            }
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = (field === 'name' || field === 'intent') ? 'asc' : 'desc';
            }
        },
        get sortedClusters() {
            if (!this.sortField) return this.clusters;
            return krSortArray(this.clusters, this.sortField, this.sortDir);
        }
    };
}
</script>
