<!-- Global Modals System - Alert & Confirm -->
<div x-data="globalModals()" x-cloak>

    <!-- Alert Modal -->
    <div x-show="alertVisible" class="fixed inset-0 z-[60] overflow-y-auto" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="alertVisible"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 dark:bg-slate-900/80"></div>

        <!-- Panel -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="alertVisible"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-sm w-full p-6 ring-1 ring-slate-200 dark:ring-slate-700">

                <!-- Icon -->
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full mb-4"
                     :class="{
                        'bg-red-100 dark:bg-red-900/30': alertType === 'error',
                        'bg-emerald-100 dark:bg-emerald-900/30': alertType === 'success',
                        'bg-amber-100 dark:bg-amber-900/30': alertType === 'warning',
                        'bg-blue-100 dark:bg-blue-900/30': alertType === 'info'
                     }">
                    <!-- Error icon -->
                    <template x-if="alertType === 'error'">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    <!-- Success icon -->
                    <template x-if="alertType === 'success'">
                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <!-- Warning icon -->
                    <template x-if="alertType === 'warning'">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                    <!-- Info icon -->
                    <template x-if="alertType === 'info'">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </template>
                </div>

                <!-- Message -->
                <p class="text-center text-sm text-slate-700 dark:text-slate-300" x-text="alertMessage"></p>

                <!-- Button -->
                <div class="mt-5">
                    <button @click="closeAlert()"
                            class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                            :class="{
                                'bg-red-600 hover:bg-red-500 focus:ring-red-500': alertType === 'error',
                                'bg-emerald-600 hover:bg-emerald-500 focus:ring-emerald-500': alertType === 'success',
                                'bg-amber-600 hover:bg-amber-500 focus:ring-amber-500': alertType === 'warning',
                                'bg-primary-600 hover:bg-primary-500 focus:ring-primary-500': alertType === 'info'
                            }">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div x-show="confirmVisible" class="fixed inset-0 z-[60] overflow-y-auto" aria-modal="true">
        <!-- Backdrop -->
        <div x-show="confirmVisible"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 dark:bg-slate-900/80"></div>

        <!-- Panel -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="confirmVisible"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-sm w-full p-6 ring-1 ring-slate-200 dark:ring-slate-700">

                <!-- Icon -->
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full mb-4"
                     :class="confirmDestructive ? 'bg-red-100 dark:bg-red-900/30' : 'bg-primary-100 dark:bg-primary-900/30'">
                    <template x-if="confirmDestructive">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    <template x-if="!confirmDestructive">
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                        </svg>
                    </template>
                </div>

                <!-- Message -->
                <p class="text-center text-sm text-slate-700 dark:text-slate-300" x-text="confirmMessage"></p>

                <!-- Buttons -->
                <div class="mt-5 flex gap-3">
                    <button @click="rejectConfirm()"
                            class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                        Annulla
                    </button>
                    <button @click="resolveConfirm()"
                            class="flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                            :class="confirmDestructive
                                ? 'bg-red-600 hover:bg-red-500 focus:ring-red-500'
                                : 'bg-primary-600 hover:bg-primary-500 focus:ring-primary-500'"
                            x-text="confirmButtonText">
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global Ainstein namespace
    window.ainstein = window.ainstein || {};

    function globalModals() {
        return {
            // Alert state
            alertVisible: false,
            alertMessage: '',
            alertType: 'info',
            alertResolve: null,

            // Confirm state
            confirmVisible: false,
            confirmMessage: '',
            confirmDestructive: false,
            confirmButtonText: 'Conferma',
            confirmResolveFunc: null,
            confirmRejectFunc: null,

            init() {
                // Expose global functions
                window.ainstein.alert = (message, type = 'error') => {
                    return new Promise((resolve) => {
                        this.alertMessage = message;
                        this.alertType = type;
                        this.alertResolve = resolve;
                        this.alertVisible = true;
                    });
                };

                window.ainstein.confirm = (message, options = {}) => {
                    return new Promise((resolve, reject) => {
                        this.confirmMessage = message;
                        this.confirmDestructive = options.destructive || false;
                        this.confirmButtonText = options.buttonText || (options.destructive ? 'Elimina' : 'Conferma');
                        this.confirmResolveFunc = resolve;
                        this.confirmRejectFunc = reject;
                        this.confirmVisible = true;
                    });
                };

                window.ainstein.toast = (message, type = 'success') => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message, type }
                    }));
                };
            },

            closeAlert() {
                this.alertVisible = false;
                if (this.alertResolve) this.alertResolve();
            },

            resolveConfirm() {
                this.confirmVisible = false;
                if (this.confirmResolveFunc) this.confirmResolveFunc();
            },

            rejectConfirm() {
                this.confirmVisible = false;
                if (this.confirmRejectFunc) this.confirmRejectFunc();
            }
        };
    }
</script>
