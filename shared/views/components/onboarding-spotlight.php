<?php
/**
 * Onboarding Spotlight Tour - Tour con highlight sugli elementi UI
 * Variabili: $onboardingSteps (array di step), $onboardingModuleName (nome), $onboardingModuleSlug (slug)
 * Ogni step: title, description, icon, tip (opt), selector (CSS selector o null), position (bottom|top|right|left)
 */
if (empty($onboardingSteps) || empty($onboardingModuleSlug)) return;

// Prepara i dati degli step per JavaScript
$jsSteps = array_map(function($step) {
    return [
        'selector' => $step['selector'] ?? null,
        'position' => $step['position'] ?? 'bottom',
    ];
}, $onboardingSteps);
?>

<div x-data="onboardingSpotlight()" x-show="open" x-cloak
     @open-module-tour.window="if ($event.detail.slug === '<?= e($onboardingModuleSlug) ?>') { reopen(); }"
     @keydown.escape.window="if (open) skip()"
     class="fixed inset-0 z-[9998]">

    <!-- Overlay scuro (click per saltare) -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/75 transition-opacity"
         @click="skip()"></div>

    <!-- Cutout (evidenzia elemento) -->
    <div x-show="open && !noTarget"
         class="fixed pointer-events-none rounded-xl transition-all duration-500 ease-in-out"
         :style="'top:'+cutout.top+'px; left:'+cutout.left+'px; width:'+cutout.width+'px; height:'+cutout.height+'px; box-shadow: 0 0 0 9999px rgba(15,23,42,0.75); z-index: 9998;'">
    </div>

    <!-- Tooltip -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         class="fixed z-[9999] w-[360px] max-w-[calc(100vw-32px)]"
         :class="noTarget ? 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2' : ''"
         :style="noTarget ? '' : 'top:'+tip.top+'px; left:'+tip.left+'px;'">

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl ring-1 ring-slate-200 dark:ring-slate-700 overflow-hidden">
            <!-- Header -->
            <div class="px-5 pt-4 pb-0 flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-xs font-semibold text-primary-700 dark:text-primary-300">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                    </svg>
                    <?= e($onboardingModuleName ?? 'Tour') ?>
                </span>
                <button @click="skip()" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    Salta tour
                </button>
            </div>

            <!-- Step Content -->
            <div class="px-5 pt-4 pb-3">
                <?php foreach ($onboardingSteps as $i => $step): ?>
                <div x-show="currentStep === <?= $i ?>" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <!-- Step counter -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary-600 text-white text-xs font-bold"><?= $i + 1 ?></span>
                        <span class="text-xs text-slate-400 dark:text-slate-500">di <?= count($onboardingSteps) ?></span>
                    </div>

                    <!-- Icon -->
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-900/30 mb-3">
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="<?= $step['icon'] ?>" />
                        </svg>
                    </div>

                    <!-- Title -->
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2"><?= e($step['title']) ?></h3>

                    <!-- Description -->
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"><?= e($step['description']) ?></p>

                    <!-- Tip -->
                    <?php if (!empty($step['tip'])): ?>
                    <div class="mt-3 flex items-start gap-2 p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-xs text-blue-700 dark:text-blue-300"><?= e($step['tip']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer -->
            <div class="px-5 pb-5">
                <!-- Progress bar -->
                <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full mb-4 overflow-hidden">
                    <div class="h-full bg-primary-500 rounded-full transition-all duration-500"
                         :style="'width: ' + ((currentStep + 1) / totalSteps * 100) + '%'"></div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-2">
                    <button x-show="currentStep > 0" @click="prev()"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        Indietro
                    </button>
                    <button x-show="currentStep < totalSteps - 1" @click="next()"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 shadow-sm transition-colors">
                        Avanti
                    </button>
                    <button x-show="currentStep === totalSteps - 1" @click="complete()"
                            class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 shadow-sm transition-colors">
                        Inizia!
                    </button>
                </div>
            </div>
        </div>

        <!-- Arrow (freccia verso l'elemento) -->
        <div x-show="!noTarget && tipPosition === 'bottom'" class="absolute -top-2 left-8 w-4 h-4 bg-white dark:bg-slate-800 rotate-45 ring-1 ring-slate-200 dark:ring-slate-700" style="clip-path: polygon(0 0, 100% 0, 0 100%)"></div>
        <div x-show="!noTarget && tipPosition === 'top'" class="absolute -bottom-2 left-8 w-4 h-4 bg-white dark:bg-slate-800 rotate-45 ring-1 ring-slate-200 dark:ring-slate-700" style="clip-path: polygon(100% 0, 100% 100%, 0 100%)"></div>
        <div x-show="!noTarget && tipPosition === 'right'" class="absolute top-8 -left-2 w-4 h-4 bg-white dark:bg-slate-800 rotate-45 ring-1 ring-slate-200 dark:ring-slate-700" style="clip-path: polygon(0 0, 0 100%, 100% 100%)"></div>
        <div x-show="!noTarget && tipPosition === 'left'" class="absolute top-8 -right-2 w-4 h-4 bg-white dark:bg-slate-800 rotate-45 ring-1 ring-slate-200 dark:ring-slate-700" style="clip-path: polygon(0 0, 100% 0, 100% 100%)"></div>
    </div>
</div>

<script>
function onboardingSpotlight() {
    return {
        open: true,
        currentStep: 0,
        totalSteps: <?= count($onboardingSteps) ?>,
        steps: <?= json_encode($jsSteps) ?>,
        cutout: { top: 0, left: 0, width: 0, height: 0 },
        tip: { top: 0, left: 0 },
        tipPosition: 'bottom',
        noTarget: false,
        _resizeTimer: null,

        init() {
            this.$nextTick(() => this.goToStep(0));
            // Ricalcola su resize (debounced)
            window.addEventListener('resize', () => {
                clearTimeout(this._resizeTimer);
                this._resizeTimer = setTimeout(() => {
                    if (this.open) this.goToStep(this.currentStep);
                }, 200);
            });
        },

        reopen() {
            this.open = true;
            this.currentStep = 0;
            this.$nextTick(() => this.goToStep(0));
        },

        goToStep(i) {
            this.currentStep = i;
            const step = this.steps[i];
            if (!step || !step.selector) {
                this.noTarget = true;
                return;
            }

            const el = document.querySelector(step.selector);
            if (!el) {
                this.noTarget = true;
                return;
            }

            this.noTarget = false;

            // Scroll to element
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Delay per permettere lo scroll
            setTimeout(() => {
                this.measureAndPosition(el, step.position || 'bottom');
            }, 450);
        },

        measureAndPosition(el, preferredPos) {
            const rect = el.getBoundingClientRect();
            const pad = 8;

            // Posiziona cutout
            this.cutout = {
                top: rect.top - pad,
                left: rect.left - pad,
                width: rect.width + pad * 2,
                height: rect.height + pad * 2,
            };

            // Calcola posizione tooltip
            const tipW = 360;
            const tipH = 320; // stima altezza tooltip
            const gap = 16;
            const vw = window.innerWidth;
            const vh = window.innerHeight;

            let pos = preferredPos;
            let top, left;

            // Prova posizione preferita, altrimenti fallback
            const positions = [preferredPos, 'bottom', 'top', 'right', 'left'];
            for (const p of positions) {
                if (p === 'bottom' && (rect.bottom + gap + tipH) < vh) {
                    pos = 'bottom';
                    top = rect.bottom + gap;
                    left = Math.max(16, Math.min(rect.left, vw - tipW - 16));
                    break;
                }
                if (p === 'top' && (rect.top - gap - tipH) > 0) {
                    pos = 'top';
                    top = rect.top - gap - tipH;
                    left = Math.max(16, Math.min(rect.left, vw - tipW - 16));
                    break;
                }
                if (p === 'right' && (rect.right + gap + tipW) < vw) {
                    pos = 'right';
                    top = Math.max(16, Math.min(rect.top, vh - tipH - 16));
                    left = rect.right + gap;
                    break;
                }
                if (p === 'left' && (rect.left - gap - tipW) > 0) {
                    pos = 'left';
                    top = Math.max(16, Math.min(rect.top, vh - tipH - 16));
                    left = rect.left - gap - tipW;
                    break;
                }
            }

            // Fallback: centrato sotto
            if (top === undefined) {
                pos = 'bottom';
                top = Math.min(rect.bottom + gap, vh - tipH - 16);
                left = Math.max(16, (vw - tipW) / 2);
            }

            this.tipPosition = pos;
            this.tip = { top, left };
        },

        next() {
            if (this.currentStep < this.totalSteps - 1) {
                this.goToStep(this.currentStep + 1);
            }
        },

        prev() {
            if (this.currentStep > 0) {
                this.goToStep(this.currentStep - 1);
            }
        },

        async complete() {
            await this.markComplete();
            this.open = false;
        },

        async skip() {
            await this.markComplete();
            this.open = false;
        },

        async markComplete() {
            try {
                const formData = new FormData();
                formData.append('_csrf_token', '<?= csrf_token() ?>');
                await fetch('<?= url('/onboarding/' . $onboardingModuleSlug . '/complete') ?>', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                // Ignora errori di rete
            }
        }
    };
}
</script>
