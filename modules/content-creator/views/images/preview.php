<?php include __DIR__ . '/../partials/project-nav.php'; ?>

<?php
$approveUrl = url("/content-creator/projects/{$project['id']}/images/{$image['id']}/approve");
$rejectUrl = url("/content-creator/projects/{$project['id']}/images/{$image['id']}/reject");
$regenerateUrl = url("/content-creator/projects/{$project['id']}/images/{$image['id']}/regenerate");
$csrfTokenVal = csrf_token();

$statusConfig = [
    'pending' => ['label' => 'In attesa', 'color' => 'slate'],
    'source_acquired' => ['label' => 'Foto OK', 'color' => 'blue'],
    'generated' => ['label' => 'Generata', 'color' => 'violet'],
    'approved' => ['label' => 'Approvata', 'color' => 'emerald'],
    'published' => ['label' => 'Pubblicata', 'color' => 'teal'],
    'error' => ['label' => 'Errore', 'color' => 'red'],
];
$sc = $statusConfig[$image['status']] ?? $statusConfig['pending'];
?>

<div x-data="{
    lightboxOpen: false,
    lightboxSrc: '',
    regenerating: false,

    openLightbox(src) { this.lightboxSrc = src; this.lightboxOpen = true; },
    closeLightbox() { this.lightboxOpen = false; this.lightboxSrc = ''; },

    async approveVariant(variantId) {
        try {
            const fd = new FormData();
            fd.append('_csrf_token', '<?= $csrfTokenVal ?>');
            fd.append('variant_id', variantId);
            const resp = await fetch('<?= $approveUrl ?>', { method: 'POST', body: fd });
            if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
            const data = await resp.json();
            if (data.success) location.reload();
            else alert(data.message || 'Errore');
        } catch (e) { alert('Errore: ' + e.message); }
    },

    async rejectVariant(variantId) {
        try {
            const fd = new FormData();
            fd.append('_csrf_token', '<?= $csrfTokenVal ?>');
            fd.append('variant_id', variantId);
            const resp = await fetch('<?= $rejectUrl ?>', { method: 'POST', body: fd });
            if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
            const data = await resp.json();
            if (data.success) location.reload();
            else alert(data.message || 'Errore');
        } catch (e) { alert('Errore: ' + e.message); }
    },

    async regenerate(variantId = null) {
        if (!confirm(variantId ? 'Rigenerare questa variante?' : 'Rigenerare tutte le varianti?')) return;
        this.regenerating = true;
        try {
            const fd = new FormData();
            fd.append('_csrf_token', '<?= $csrfTokenVal ?>');
            if (variantId) fd.append('variant_id', variantId);
            const resp = await fetch('<?= $regenerateUrl ?>', { method: 'POST', body: fd });
            if (!resp.ok) throw new Error('Errore server (' + resp.status + ')');
            const data = await resp.json();
            if (data.success) location.reload();
            else alert(data.message || 'Errore');
        } catch (e) {
            alert('Errore: ' + e.message);
        } finally {
            this.regenerating = false;
        }
    }
}">

    <!-- Back link + header -->
    <div class="mb-6">
        <a href="<?= url("/content-creator/projects/{$project['id']}/images") ?>"
           class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
            &larr; Torna alla lista
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white"><?= e($image['product_name']) ?></h2>
                <div class="flex items-center gap-3 mt-2">
                    <?php if (!empty($image['sku'])): ?>
                    <span class="text-sm text-slate-500 dark:text-slate-400">SKU: <?= e($image['sku']) ?></span>
                    <?php endif; ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $sc['color'] ?>-100 text-<?= $sc['color'] ?>-800 dark:bg-<?= $sc['color'] ?>-900/30 dark:text-<?= $sc['color'] ?>-400">
                        <?= $sc['label'] ?>
                    </span>
                    <?php
                    $catLabels = ['fashion' => 'Fashion', 'home' => 'Home', 'custom' => 'Custom'];
                    ?>
                    <span class="text-xs text-slate-400"><?= $catLabels[$image['category']] ?? $image['category'] ?></span>
                </div>
                <?php if (!empty($image['error_message'])): ?>
                <div class="mt-2 text-sm text-red-600 dark:text-red-400"><?= e($image['error_message']) ?></div>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <button @click="regenerate()" :disabled="regenerating"
                        class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Rigenera Tutte
                </button>
            </div>
        </div>
    </div>

    <!-- Source + Variants Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Source Image -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
                <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Immagine Originale</h3>
                <?php if (!empty($image['source_image_path'])): ?>
                <img src="<?= url('/content-creator/images/serve/source/' . basename($image['source_image_path'])) ?>"
                     class="w-full rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                     @click="openLightbox('<?= url('/content-creator/images/serve/source/' . basename($image['source_image_path'])) ?>')"
                     alt="<?= e($image['product_name']) ?>">
                <?php else: ?>
                <div class="w-full aspect-square bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                    <svg class="w-16 h-16 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <?php endif; ?>
                <?php if (!empty($image['product_url'])): ?>
                <a href="<?= e($image['product_url']) ?>" target="_blank" class="block mt-2 text-xs text-slate-500 dark:text-slate-400 hover:text-orange-600 truncate">
                    <?= e($image['product_url']) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Variants Grid -->
        <div class="lg:col-span-2">
            <?php if (empty($variants)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-violet-300 dark:text-violet-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Nessuna variante generata. Avvia la generazione dalla lista immagini.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <?php foreach ($variants as $v): ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <?php if (!empty($v['image_path'])): ?>
                    <img src="<?= url('/content-creator/images/serve/generated/' . basename($v['image_path'])) ?>"
                         class="w-full aspect-square object-cover cursor-pointer hover:opacity-90 transition-opacity"
                         @click="openLightbox('<?= url('/content-creator/images/serve/generated/' . basename($v['image_path'])) ?>')"
                         alt="Variante <?= $v['variant_number'] ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Variante <?= $v['variant_number'] ?></span>
                            <?php
                            // Derive status from is_approved/is_pushed flags
                            // is_approved: 0=pending/rejected, 1=approved; is_pushed: 0=no, 1=yes
                            if ($v['is_pushed']) {
                                $vStatus = 'pushed';
                            } elseif ($v['is_approved']) {
                                $vStatus = 'approved';
                            } else {
                                $vStatus = 'pending';
                            }
                            $vStatusColors = ['pending' => 'slate', 'approved' => 'emerald', 'rejected' => 'red', 'pushed' => 'teal'];
                            $vStatusLabels = ['pending' => 'In attesa', 'approved' => 'Approvata', 'rejected' => 'Rifiutata', 'pushed' => 'Pubblicata'];
                            $vsc = $vStatusColors[$vStatus] ?? 'slate';
                            $vsl = $vStatusLabels[$vStatus] ?? $vStatus;
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $vsc ?>-100 text-<?= $vsc ?>-800 dark:bg-<?= $vsc ?>-900/30 dark:text-<?= $vsc ?>-400">
                                <?= $vsl ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($vStatus !== 'approved' && $vStatus !== 'pushed'): ?>
                            <button @click="approveVariant(<?= $v['id'] ?>)"
                                    class="flex-1 px-2 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition-colors">
                                Approva
                            </button>
                            <?php endif; ?>
                            <?php if ($vStatus === 'approved'): ?>
                            <button @click="rejectVariant(<?= $v['id'] ?>)"
                                    class="flex-1 px-2 py-1.5 rounded-lg bg-red-600 text-white text-xs font-medium hover:bg-red-700 transition-colors">
                                Rifiuta
                            </button>
                            <?php endif; ?>
                            <button @click="regenerate(<?= $v['id'] ?>)" :disabled="regenerating"
                                    class="px-2 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors"
                                    title="Rigenera">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Preset Override Section -->
    <div class="mt-6 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-4">Impostazioni generazione (override per questo prodotto)</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Categoria</label>
                <span class="text-sm text-slate-900 dark:text-white"><?= $catLabels[$settings['scene_type'] ?? $image['category']] ?? $image['category'] ?></span>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Genere</label>
                <span class="text-sm text-slate-900 dark:text-white"><?= ucfirst($settings['gender'] ?? 'woman') ?></span>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Sfondo</label>
                <span class="text-sm text-slate-900 dark:text-white"><?= ucfirst(str_replace('_', ' ', $settings['background'] ?? 'studio_white')) ?></span>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Stile foto</label>
                <span class="text-sm text-slate-900 dark:text-white"><?= ucfirst($settings['photo_style'] ?? 'professional') ?></span>
            </div>
        </div>
        <?php if (!empty($settings['custom_prompt'])): ?>
        <div class="mt-3">
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Prompt custom</label>
            <p class="text-sm text-slate-900 dark:text-white"><?= e($settings['custom_prompt']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Lightbox -->
    <div x-show="lightboxOpen" x-cloak @click.self="closeLightbox()" @keydown.escape.window="closeLightbox()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80" x-transition>
        <img :src="lightboxSrc" class="max-w-5xl max-h-[90vh] object-contain rounded-lg shadow-2xl">
        <button @click="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-slate-300 transition-colors">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

</div>
