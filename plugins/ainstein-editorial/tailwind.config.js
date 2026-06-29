/** @type {import('tailwindcss').Config} */
module.exports = {
  // Scansiona il markup del plugin per il tree-shaking delle utility.
  content: [
    './src/**/*.php',
    './assets/js/**/*.js',
  ],
  // Preflight OFF: siamo dentro l'admin WordPress, non vogliamo resettare
  // gli stili nativi di WP. Usiamo solo le nostre utility + componenti .aied-*.
  corePlugins: {
    preflight: false,
  },
  // Il design system deve essere sempre disponibile anche se una classe non
  // è ancora usata nel markup corrente (verrà usata in M2.4+).
  safelist: [
    'aied-wrap', 'aied-title', 'aied-subtitle', 'aied-footer', 'aied-label',
    'aied-card', 'aied-card--active',
    'aied-btn', 'aied-btn-primary', 'aied-btn-secondary', 'aied-btn-ghost',
    'aied-input', 'aied-textarea', 'aied-select',
    'aied-badge', 'aied-badge-success', 'aied-badge-warning', 'aied-badge-danger', 'aied-badge-info',
    'aied-modal', 'aied-modal__panel', 'aied-progress', 'aied-progress__bar',
    'aied-skeleton', 'aied-step', 'aied-step--active', 'aied-tone-card', 'aied-tone-card--selected',
  ],
  theme: {
    extend: {
      colors: {
        // Indigo 500 di Tailwind è già #6366F1 (= --aied-primary del design §12)
        aied: {
          primary: '#6366F1',
          'primary-dark': '#4F46E5',
          'primary-light': '#818CF8',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
};
