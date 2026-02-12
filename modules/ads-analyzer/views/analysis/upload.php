<?php $currentPage = 'upload'; include __DIR__ . '/../partials/project-nav.php'; ?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- Script Alternative Banner -->
    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <div class="h-8 w-8 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-medium text-purple-900 dark:text-purple-200">Vuoi automatizzare?</p>
                <p class="text-sm text-purple-700 dark:text-purple-300 mt-0.5">
                    Usa il <strong>Google Ads Script</strong> per inviare automaticamente i dati senza export manuali.
                </p>
            </div>
            <a href="<?= url('/ads-analyzer/projects/' . $project['id'] . '/script') ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/50 hover:bg-purple-200 dark:hover:bg-purple-800/50 rounded-lg transition-colors flex-shrink-0">
                Configura Script
                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6"
         x-data="csvUploader()"
    >
        <form @submit.prevent="uploadFile">
            <?= csrf_field() ?>

            <!-- Drop Zone -->
            <div
                class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                :class="isDragging ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'border-slate-300 dark:border-slate-600'"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
            >
                <template x-if="!file">
                    <div>
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="mt-4 text-lg font-medium text-slate-700 dark:text-slate-300">
                            Trascina qui il file CSV
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            oppure
                            <label class="text-amber-600 hover:text-amber-700 cursor-pointer">
                                clicca per selezionare
                                <input type="file" class="hidden" accept=".csv" @change="handleFileSelect($event)">
                            </label>
                        </p>
                        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                            Formato supportato: CSV export Google Ads (max 10MB)
                        </p>
                    </div>
                </template>

                <template x-if="file">
                    <div>
                        <svg class="mx-auto h-12 w-12 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="mt-4 text-lg font-medium text-slate-700 dark:text-slate-300" x-text="file.name"></p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="formatFileSize(file.size)"></p>
                        <button type="button" @click="file = null" class="mt-2 text-sm text-red-600 hover:text-red-700">
                            Rimuovi
                        </button>
                    </div>
                </template>
            </div>

            <!-- Progress -->
            <div x-show="isUploading" class="mt-4">
                <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-400 mb-1">
                    <span>Caricamento in corso...</span>
                    <span x-text="progress + '%'"></span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                    <div class="bg-amber-600 h-2 rounded-full transition-all" :style="'width: ' + progress + '%'"></div>
                </div>
            </div>

            <!-- Error -->
            <div x-show="error" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-red-700 dark:text-red-300" x-text="error"></p>
                </div>
            </div>

            <!-- Success -->
            <div x-show="success" class="mt-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-emerald-700 dark:text-emerald-300">
                        <p class="font-medium">CSV importato con successo!</p>
                        <p x-text="successMessage"></p>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="<?= url('/ads-analyzer/projects/' . $project['id']) ?>" class="px-4 py-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    Annulla
                </a>
                <button
                    type="submit"
                    :disabled="!file || isUploading"
                    class="px-6 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span x-show="!isUploading">Carica e Continua</span>
                    <span x-show="isUploading">Elaborazione...</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Help -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-6">
        <h3 class="font-medium text-slate-900 dark:text-white mb-3">Come esportare da Google Ads</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li>Vai su <strong>Report > Termini di ricerca</strong></li>
            <li>Seleziona il <strong>periodo</strong> e i <strong>filtri</strong> desiderati</li>
            <li>Clicca <strong>Scarica > CSV</strong></li>
            <li>Carica il file qui sopra</li>
        </ol>
    </div>
</div>

<script>
function csvUploader() {
    return {
        file: null,
        isDragging: false,
        isUploading: false,
        progress: 0,
        error: null,
        success: false,
        successMessage: '',

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.file = files[0];
            }
        },

        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                this.file = files[0];
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        async uploadFile() {
            if (!this.file) return;

            console.log('=== UPLOAD START ===');
            console.log('File:', this.file.name, this.file.size, 'bytes');

            this.isUploading = true;
            this.error = null;
            this.success = false;

            const formData = new FormData();
            formData.append('csv_file', this.file);

            const csrfInput = document.querySelector('input[name="_csrf_token"]');
            console.log('CSRF input found:', !!csrfInput);
            if (csrfInput) {
                console.log('CSRF token:', csrfInput.value.substring(0, 10) + '...');
                formData.append('_csrf_token', csrfInput.value);
            } else {
                console.error('CSRF TOKEN NOT FOUND!');
                this.error = 'Token CSRF non trovato';
                this.isUploading = false;
                return;
            }

            const uploadUrl = '<?= url('/ads-analyzer/projects/' . $project['id'] . '/upload') ?>';
            console.log('Upload URL:', uploadUrl);

            try {
                console.log('Sending fetch request...');
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response headers:', [...response.headers.entries()]);

                // Get raw text first
                const responseText = await response.text();
                console.log('Response text (first 500 chars):', responseText.substring(0, 500));

                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                    console.log('Parsed JSON:', data);
                } catch (jsonErr) {
                    console.error('JSON parse error:', jsonErr);
                    console.error('Full response text:', responseText);
                    this.error = 'Errore server: risposta non valida. Controlla console per dettagli.';
                    this.isUploading = false;
                    return;
                }

                if (data.error) {
                    console.log('Server returned error:', data.error);
                    this.error = data.error;
                    this.isUploading = false;
                    return;
                }

                console.log('=== UPLOAD SUCCESS ===');
                this.success = true;
                this.successMessage = `${data.ad_groups} Ad Group e ${data.total_terms} termini importati`;
                this.progress = 100;

                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);

            } catch (err) {
                console.error('=== FETCH ERROR ===');
                console.error('Error type:', err.constructor.name);
                console.error('Error message:', err.message);
                console.error('Error stack:', err.stack);
                this.error = 'Errore: ' + err.message;
                this.isUploading = false;
            }
        }
    };
}
</script>
