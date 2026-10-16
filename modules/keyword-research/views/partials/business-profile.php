<?php
/**
 * Riquadro "La tua attività": il punto di partenza dei 3 strumenti di AI Keywords.
 * Va incluso dentro l'x-data di un wizard che usa il mixin krProfile() (definito qui sotto) e che espone
 * applySuggestions(s) per riempire parole di partenza, categorie, tipo di sito e target.
 *
 * Variabili: $project, $profileState (BusinessProfileService::state()), $profileHint (cosa ci fa questo strumento).
 */
$profileHint = $profileHint ?? 'Le parole da cercare su Google le proponiamo noi.';
?>
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 space-y-4">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">La tua attività</h2>
            <p x-show="!profile && !editingProfile" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Ci basta sapere cosa fai. <?= e($profileHint) ?>
            </p>
        </div>
        <button type="button" x-show="profile && !editingProfile && !profileBusy" @click="editProfile()"
                class="flex-shrink-0 inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            Modifica
        </button>
    </div>

    <!-- Nessun profilo: da dove partiamo -->
    <div x-show="!profile && !profileBusy && !editingProfile" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <template x-if="geoFacts > 0">
            <button type="button" @click="buildProfile('geo')"
                    class="text-left rounded-xl border-2 border-primary-200 dark:border-primary-800 bg-primary-50/50 dark:bg-primary-900/20 p-4 hover:border-primary-400 transition-colors">
                <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <p class="mt-2 font-medium text-slate-900 dark:text-white">Usa il profilo del GEO Audit</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"><span x-text="geoFacts"></span> informazioni già confermate da te</p>
            </button>
        </template>
        <template x-if="domain">
            <button type="button" @click="buildProfile('site')"
                    class="text-left rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-primary-400 transition-colors">
                <svg class="w-6 h-6 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <p class="mt-2 font-medium text-slate-900 dark:text-white">Leggi il sito</p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 break-all" x-text="domain + ' (home e fino a 4 pagine)'"></p>
            </button>
        </template>
        <button type="button" @click="editProfile()"
                class="text-left rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-primary-400 transition-colors">
            <svg class="w-6 h-6 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <p class="mt-2 font-medium text-slate-900 dark:text-white">Scrivo io</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Cosa fai, dove e per chi, in poche righe</p>
        </button>
    </div>
    <p x-show="!profile && !profileBusy && !editingProfile" class="text-xs text-slate-400">Gratis. Il profilo resta salvato nel progetto e vale per Ricerca keyword, Architettura sito e Piano editoriale.</p>

    <!-- Lavoro in corso -->
    <div x-show="profileBusy" class="flex items-center gap-3 py-4">
        <svg class="w-6 h-6 text-primary-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <p class="text-sm text-slate-600 dark:text-slate-300" x-text="profileBusyText"></p>
    </div>

    <!-- Scrittura / modifica -->
    <div x-show="editingProfile && !profileBusy" class="space-y-3">
        <textarea x-model="profileDraft" rows="6" maxlength="3000"
                  placeholder="Es: Noleggio barche e gommoni a Castellammare di Stabia, con e senza patente. Tour privati con skipper verso Capri, Sorrento e Costiera Amalfitana. Clienti: turisti italiani e stranieri, coppie, famiglie e gruppi."
                  class="block w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-2.5 px-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="buildProfile('text')" :disabled="profileDraft.trim().length < 20"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Salva
            </button>
            <button type="button" @click="editingProfile = false"
                    class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                Annulla
            </button>
            <span class="text-xs text-slate-400 ml-auto" x-show="profile && (geoFacts > 0 || domain)">
                Oppure rifallo
                <template x-if="geoFacts > 0"><button type="button" @click="buildProfile('geo')" class="text-primary-600 dark:text-primary-400 hover:underline">dal GEO Audit</button></template>
                <template x-if="geoFacts > 0 && domain"><span> o </span></template>
                <template x-if="domain"><button type="button" @click="buildProfile('site')" class="text-primary-600 dark:text-primary-400 hover:underline">dal sito</button></template>
            </span>
        </div>
    </div>

    <!-- Profilo pronto -->
    <div x-show="profile && !editingProfile && !profileBusy">
        <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line" :class="profileExpanded ? '' : 'line-clamp-3'" x-text="profile"></p>
        <button type="button" @click="profileExpanded = !profileExpanded" class="mt-1 text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                x-text="profileExpanded ? 'Mostra meno' : 'Leggi tutto'"></button>
    </div>

    <p x-show="profileError" x-text="profileError" class="text-sm text-red-600 dark:text-red-400"></p>
</div>

<script>
if (!window.krProfile) {
    /**
     * Stato e azioni del riquadro "La tua attività". Il wizard che lo usa definisce applySuggestions(s).
     */
    window.krProfile = function (cfg) {
        return {
            profile: cfg.profile || '',
            suggestions: cfg.suggestions || null,
            geoFacts: cfg.geoFacts || 0,
            domain: cfg.domain || '',
            profileUrl: cfg.url,
            profileBusy: false,
            profileBusyText: '',
            profileError: '',
            editingProfile: false,
            profileDraft: '',
            profileExpanded: false,

            profileInit() {
                if (this.profile && this.suggestions) {
                    this.applySuggestions(this.suggestions);
                } else if (this.profile) {
                    this.buildProfile('suggest');
                }
            },

            editProfile() {
                this.profileDraft = this.profile;
                this.profileError = '';
                this.editingProfile = true;
            },

            async buildProfile(source) {
                const texts = {
                    geo: 'Riassumo il profilo del GEO Audit e preparo le parole da cercare...',
                    site: 'Leggo il sito e scrivo il profilo: ci vuole circa mezzo minuto...',
                    text: 'Salvo il profilo e preparo le parole da cercare...',
                    suggest: 'Preparo le parole da cercare partendo dal profilo...',
                };
                this.profileBusy = true;
                this.profileBusyText = texts[source] || '';
                this.profileError = '';

                const formData = new FormData();
                formData.append('_csrf_token', cfg.csrf);
                formData.append('source', source);
                formData.append('text', this.profileDraft);

                try {
                    const resp = await fetch(this.profileUrl, { method: 'POST', body: formData });
                    if (!resp.ok) { throw new Error(`Errore server (${resp.status})`); }
                    const data = await resp.json();
                    if (!data.success) {
                        this.profileError = data.error || 'Qualcosa è andato storto, riprova.';
                        return;
                    }
                    this.profile = data.profile;
                    this.suggestions = data.suggestions;
                    this.editingProfile = false;
                    this.applySuggestions(data.suggestions, true);
                } catch (e) {
                    this.profileError = 'Errore di connessione, riprova.';
                } finally {
                    this.profileBusy = false;
                }
            },
        };
    };
}
</script>
