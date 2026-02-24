<?php
/**
 * Admin Email Template Edit View — Live Preview
 * Two-column layout: form (left) + preview iframe (right)
 */

// Category badge colors
$categoryColors = [
    'auth'         => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'notification' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    'module'       => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    'report'       => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
];
$categoryLabels = [
    'auth'         => 'Autenticazione',
    'notification' => 'Notifiche',
    'module'       => 'Moduli',
    'report'       => 'Report',
];
$catKey   = $template['category'] ?? 'system';
$catColor = $categoryColors[$catKey] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300';
$catLabel = $categoryLabels[$catKey] ?? ucfirst($catKey);
?>

<div x-data="emailEditor(
    <?= e(json_encode($template['subject'])) ?>,
    <?= e(json_encode($template['body_html'])) ?>,
    <?= e(json_encode($template['slug'])) ?>,
    <?= e(json_encode(csrf_token())) ?>,
    <?= e(json_encode(url('/admin/email-templates'))) ?>
)" class="space-y-6">

    <!-- Back link -->
    <div>
        <a href="<?= url('/admin/email-templates') ?>"
           class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Torna alla lista
        </a>
    </div>

    <!-- Two-column grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <!-- LEFT COLUMN: Form -->
        <div class="space-y-6">

            <!-- Template info card (read-only) -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                            <?= e($template['name']) ?>
                        </h2>
                        <?php if (!empty($template['description'])): ?>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                <?= e($template['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $catColor ?>">
                        <?= e($catLabel) ?>
                    </span>
                </div>
                <div class="mt-3 flex items-center gap-4 text-xs text-slate-400 dark:text-slate-500">
                    <span class="font-mono bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded">
                        <?= e($template['slug']) ?>
                    </span>
                    <?php if (!empty($template['updated_at'])): ?>
                        <span>Aggiornato: <?= date('d/m/Y H:i', strtotime($template['updated_at'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit form -->
            <form action="<?= url('/admin/email-templates/' . $template['slug']) ?>" method="POST"
                  class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-6">
                <?= csrf_field() ?>

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Oggetto
                    </label>
                    <input type="text"
                           id="subject"
                           name="subject"
                           x-model="subject"
                           value="<?= e($template['subject']) ?>"
                           placeholder="es. Benvenuto su {{app_name}}"
                           class="block w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>

                <!-- Body HTML -->
                <div>
                    <label for="body_html" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                        Contenuto HTML
                    </label>
                    <textarea id="body_html"
                              name="body_html"
                              x-model="bodyHtml"
                              x-ref="bodyTextarea"
                              rows="20"
                              class="block w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 text-sm font-mono text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-y"><?= e($template['body_html']) ?></textarea>
                </div>

                <!-- Available Variables -->
                <?php if (!empty($availableVars)): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Variabili disponibili
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">
                            Clicca per inserire nel contenuto
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($availableVars as $var): ?>
                                <button type="button"
                                        @click="insertVariable('<?= e($var) ?>')"
                                        class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded text-xs font-mono cursor-pointer hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                                    {{<?= e($var) ?>}}
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Toggle Attivo -->
                <div x-data="{ isActive: <?= $template['is_active'] ? 'true' : 'false' ?> }" class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               :checked="isActive"
                               @change="isActive = !isActive"
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="text-sm text-slate-700 dark:text-slate-300">Template attivo</span>
                </div>

                <!-- Buttons row -->
                <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <!-- Save -->
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Salva modifiche
                    </button>

                    <!-- Send test -->
                    <button type="button"
                            @click="sendTest()"
                            :disabled="sending"
                            class="inline-flex items-center gap-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        <span x-text="sending ? 'Invio in corso...' : 'Invia test'"></span>
                    </button>

                    <!-- Reset default -->
                    <button type="button"
                            @click="resetDefault()"
                            class="inline-flex items-center gap-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.182-3.182"/>
                        </svg>
                        Ripristina default
                    </button>
                </div>
            </form>
        </div>

        <!-- RIGHT COLUMN: Preview -->
        <div class="xl:sticky xl:top-6 xl:self-start space-y-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

                <!-- Preview header -->
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Anteprima</h3>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-medium text-slate-600 dark:text-slate-300">Oggetto:</span>
                        <span x-text="subject" class="ml-1"></span>
                    </div>
                </div>

                <!-- Preview iframe -->
                <div class="bg-slate-50 dark:bg-slate-900 p-4">
                    <iframe x-ref="previewFrame"
                            class="w-full bg-white rounded-xl border border-slate-200 dark:border-slate-700"
                            style="min-height: 600px;"
                            sandbox="allow-same-origin"
                            title="Anteprima email"></iframe>
                </div>

                <!-- Preview note -->
                <div class="px-6 py-3 bg-slate-50 dark:bg-slate-700/30 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                        </svg>
                        L'anteprima si aggiorna automaticamente. Le variabili vengono sostituite con dati di esempio.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function emailEditor(initialSubject, initialBody, slug, csrfToken, baseUrl) {
    return {
        subject: initialSubject,
        bodyHtml: initialBody,
        slug: slug,
        csrfToken: csrfToken,
        baseUrl: baseUrl,
        previewTimeout: null,
        sending: false,

        init() {
            this.updatePreview();
            this.$watch('subject', () => this.debouncedPreview());
            this.$watch('bodyHtml', () => this.debouncedPreview());
        },

        debouncedPreview() {
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(() => this.updatePreview(), 500);
        },

        async updatePreview() {
            const formData = new FormData();
            formData.append('subject', this.subject);
            formData.append('body_html', this.bodyHtml);
            formData.append('_csrf_token', this.csrfToken);

            try {
                const resp = await fetch(this.baseUrl + '/' + this.slug + '/preview', {
                    method: 'POST',
                    body: formData
                });
                if (resp.ok) {
                    const html = await resp.text();
                    const iframe = this.$refs.previewFrame;
                    iframe.srcdoc = html;
                }
            } catch (e) {
                console.error('Preview update failed:', e);
            }
        },

        insertVariable(varName) {
            const textarea = this.$refs.bodyTextarea;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = '{{' + varName + '}}';
            this.bodyHtml = this.bodyHtml.substring(0, start) + text + this.bodyHtml.substring(end);
            this.$nextTick(() => {
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + text.length;
            });
        },

        async sendTest() {
            if (this.sending) return;
            this.sending = true;
            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                const resp = await fetch(this.baseUrl + '/' + this.slug + '/test', {
                    method: 'POST',
                    body: formData
                });
                if (resp.ok) {
                    const data = await resp.json();
                    if (window.ainstein && window.ainstein.toast) {
                        window.ainstein.toast(data.success ? 'success' : 'error', data.message || (data.success ? 'Email di test inviata!' : 'Errore nell\'invio'));
                    } else {
                        alert(data.success ? (data.message || 'Email di test inviata!') : ('Errore: ' + (data.message || 'Invio fallito')));
                    }
                } else {
                    alert('Errore di connessione (HTTP ' + resp.status + ')');
                }
            } catch (e) {
                alert('Errore di connessione');
            }
            this.sending = false;
        },

        async resetDefault() {
            const confirmed = window.ainstein && window.ainstein.confirm
                ? await window.ainstein.confirm('Sei sicuro di voler ripristinare il template originale? Le modifiche attuali saranno perse.', { destructive: true }).then(() => true).catch(() => false)
                : confirm('Sei sicuro di voler ripristinare il template originale? Le modifiche attuali saranno perse.');

            if (!confirmed) return;

            try {
                const formData = new FormData();
                formData.append('_csrf_token', this.csrfToken);
                const resp = await fetch(this.baseUrl + '/' + this.slug + '/reset', {
                    method: 'POST',
                    body: formData
                });
                if (resp.ok) {
                    const data = await resp.json();
                    if (data.success) {
                        this.subject = data.subject;
                        this.bodyHtml = data.body_html;
                        this.updatePreview();
                        if (window.ainstein && window.ainstein.toast) {
                            window.ainstein.toast('success', 'Template ripristinato ai valori predefiniti');
                        } else {
                            alert('Template ripristinato!');
                        }
                    } else {
                        alert('Errore: ' + (data.message || 'Ripristino fallito'));
                    }
                } else {
                    alert('Errore di connessione (HTTP ' + resp.status + ')');
                }
            } catch (e) {
                alert('Errore di connessione');
            }
        }
    };
}
</script>
