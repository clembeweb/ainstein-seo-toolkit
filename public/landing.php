<!DOCTYPE html>
<html lang="it" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ainstein - La piattaforma AI per dominare la SEO</title>
    <meta name="description" content="Analisi, contenuti e tracking potenziati dall'intelligenza artificiale. Domina la SEO con Ainstein.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= url('/favicon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= url('/favicon.svg') ?>">
    <meta name="theme-color" content="#006e96">

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#e6f4f8',
                            100: '#cce9f1',
                            200: '#99d3e3',
                            300: '#66bdd5',
                            400: '#33a7c7',
                            500: '#006e96',
                            600: '#005577',
                            700: '#004d69',
                            800: '#003d54',
                            900: '#002e3f',
                            950: '#001f2a',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .gradient-text {
            background: linear-gradient(135deg, #006e96 0%, #00a3d9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #f8fafc 0%, #e6f4f8 50%, #cce9f1 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 110, 150, 0.15);
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-900">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="<?= url('/') ?>" class="flex-shrink-0">
                    <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Ainstein" class="h-8">
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">Funzionalita</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors">Prezzi</a>
                </div>

                <!-- CTA Buttons -->
                <div class="flex items-center gap-3">
                    <a href="<?= url('/login') ?>" class="hidden sm:inline-flex px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors">
                        Accedi
                    </a>
                    <a href="<?= url('/register') ?>" class="inline-flex px-4 py-2 text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors shadow-sm shadow-primary-500/25">
                        Inizia Gratis
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient pt-32 pb-20 lg:pt-40 lg:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <!-- Logo Icon -->
                <div class="fade-in mb-8 flex justify-center">
                    <img src="<?= url('/assets/images/logo-ainstein-square.png') ?>" alt="Ainstein" class="h-24 lg:h-28">
                </div>

                <!-- Headline -->
                <h1 class="fade-in text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-6">
                    La piattaforma AI per<br>
                    <span class="gradient-text">dominare la SEO</span>
                </h1>

                <!-- Subheadline -->
                <p class="fade-in text-lg sm:text-xl text-slate-600 mb-10 max-w-2xl mx-auto">
                    Analisi, contenuti e tracking potenziati dall'intelligenza artificiale
                </p>

                <!-- CTA Buttons -->
                <div class="fade-in flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="<?= url('/register') ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-all shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40">
                        <span>Inizia Gratis</span>
                        <svg class="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    <a href="<?= url('/login') ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-primary-600 bg-white hover:bg-slate-50 border-2 border-primary-200 hover:border-primary-300 rounded-xl transition-all">
                        Accedi
                    </a>
                </div>

                <!-- Trust indicators -->
                <div class="fade-in mt-12 flex flex-wrap items-center justify-center gap-6 text-sm text-slate-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                        </svg>
                        <span>Nessuna carta richiesta</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                        </svg>
                        <span>Setup in 2 minuti</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                        </svg>
                        <span>Crediti gratuiti inclusi</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 lg:py-32 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2 class="fade-in text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                    Tutto quello che ti serve per la SEO
                </h2>
                <p class="fade-in text-lg text-slate-600 max-w-2xl mx-auto">
                    Cinque moduli potenti, un'unica piattaforma. Potenziati dall'intelligenza artificiale.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Feature 1: AI Content Generator -->
                <div class="fade-in card-hover bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center mb-6 shadow-lg shadow-primary-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">AI Content Generator</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Genera articoli SEO-ottimizzati in pochi click. L'AI analizza le SERP e crea contenuti che si posizionano.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-full">GPT-4</span>
                        <span class="px-3 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-full">Claude</span>
                        <span class="px-3 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-full">Multi-lingua</span>
                    </div>
                </div>

                <!-- Feature 2: SEO Audit -->
                <div class="fade-in card-hover bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center mb-6 shadow-lg shadow-emerald-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">SEO Audit</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Analisi tecnica completa del tuo sito. Identifica errori, opportunita e ottieni raccomandazioni AI-powered.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-full">100+ check</span>
                        <span class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-full">Core Web Vitals</span>
                        <span class="px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 rounded-full">Schema.org</span>
                    </div>
                </div>

                <!-- Feature 3: SEO Tracking -->
                <div class="fade-in card-hover bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 flex items-center justify-center mb-6 shadow-lg shadow-violet-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">SEO Tracking</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Monitora posizioni, traffico e conversioni. Integrazione diretta con Google Search Console e Analytics.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium text-violet-700 bg-violet-50 rounded-full">GSC</span>
                        <span class="px-3 py-1 text-xs font-medium text-violet-700 bg-violet-50 rounded-full">GA4</span>
                        <span class="px-3 py-1 text-xs font-medium text-violet-700 bg-violet-50 rounded-full">Report AI</span>
                    </div>
                </div>

                <!-- Feature 4: Internal Links -->
                <div class="fade-in card-hover bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center mb-6 shadow-lg shadow-amber-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Internal Links</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Ottimizza la struttura dei link interni. L'AI suggerisce collegamenti strategici per migliorare il crawling.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium text-amber-700 bg-amber-50 rounded-full">Link Graph</span>
                        <span class="px-3 py-1 text-xs font-medium text-amber-700 bg-amber-50 rounded-full">Suggerimenti AI</span>
                        <span class="px-3 py-1 text-xs font-medium text-amber-700 bg-amber-50 rounded-full">Anchor Text</span>
                    </div>
                </div>

                <!-- Feature 5: Ads Analyzer -->
                <div class="fade-in card-hover bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center mb-6 shadow-lg shadow-rose-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Ads Analyzer</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Identifica keyword negative per Google Ads. Risparmia budget eliminando click non qualificati.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="px-3 py-1 text-xs font-medium text-rose-700 bg-rose-50 rounded-full">Negative KW</span>
                        <span class="px-3 py-1 text-xs font-medium text-rose-700 bg-rose-50 rounded-full">Search Terms</span>
                        <span class="px-3 py-1 text-xs font-medium text-rose-700 bg-rose-50 rounded-full">ROI</span>
                    </div>
                </div>

                <!-- Coming Soon Card -->
                <div class="fade-in bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-8 border border-slate-200 border-dashed flex flex-col items-center justify-center text-center">
                    <div class="w-14 h-14 rounded-xl bg-slate-200 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-400 mb-2">Nuovi moduli</h3>
                    <p class="text-slate-400 text-sm">
                        Stiamo lavorando a nuove funzionalita. Resta sintonizzato!
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 lg:py-32 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <h2 class="fade-in text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                    Prezzi semplici e trasparenti
                </h2>
                <p class="fade-in text-lg text-slate-600 max-w-2xl mx-auto">
                    Inizia gratis, scala quando sei pronto. Nessun costo nascosto.
                </p>
            </div>

            <!-- Pricing Cards -->
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Free Plan -->
                <div class="fade-in bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Starter</h3>
                    <p class="text-slate-500 text-sm mb-6">Per iniziare a esplorare</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-900">Gratis</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            50 crediti AI inclusi
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            1 progetto
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            Audit base (100 URL)
                        </li>
                    </ul>
                    <a href="<?= url('/register') ?>" class="block w-full py-3 text-center font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-xl transition-colors">
                        Inizia Gratis
                    </a>
                </div>

                <!-- Pro Plan -->
                <div class="fade-in bg-white rounded-2xl p-8 border-2 border-primary-500 shadow-xl shadow-primary-500/10 relative">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-primary-500 text-white text-sm font-semibold rounded-full">
                        Popolare
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Pro</h3>
                    <p class="text-slate-500 text-sm mb-6">Per professionisti SEO</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-900">€49</span>
                        <span class="text-slate-500">/mese</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            500 crediti AI/mese
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            10 progetti
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            Audit completo (1000 URL)
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            Report AI illimitati
                        </li>
                    </ul>
                    <a href="<?= url('/register') ?>" class="block w-full py-3 text-center font-semibold text-white bg-primary-500 hover:bg-primary-600 rounded-xl transition-colors shadow-lg shadow-primary-500/25">
                        Prova 14 giorni gratis
                    </a>
                </div>

                <!-- Agency Plan -->
                <div class="fade-in bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">Agency</h3>
                    <p class="text-slate-500 text-sm mb-6">Per team e agenzie</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-slate-900">€149</span>
                        <span class="text-slate-500">/mese</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            2000 crediti AI/mese
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            Progetti illimitati
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            White-label reports
                        </li>
                        <li class="flex items-center gap-3 text-sm text-slate-600">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                            </svg>
                            Supporto prioritario
                        </li>
                    </ul>
                    <a href="<?= url('/register') ?>" class="block w-full py-3 text-center font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-xl transition-colors">
                        Contattaci
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 lg:py-32 bg-gradient-to-br from-primary-600 to-primary-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="fade-in text-3xl sm:text-4xl font-bold text-white mb-6">
                Pronto a dominare la SEO?
            </h2>
            <p class="fade-in text-lg text-primary-100 mb-10 max-w-2xl mx-auto">
                Unisciti a centinaia di professionisti che usano Ainstein per scalare i risultati di ricerca.
            </p>
            <div class="fade-in flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?= url('/register') ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-primary-600 bg-white hover:bg-slate-50 rounded-xl transition-all shadow-lg">
                    Inizia Gratis Ora
                    <svg class="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <img src="<?= url('/assets/images/logo-ainstein-square.png') ?>" alt="Ainstein" class="h-8 w-8 brightness-0 invert opacity-80">
                    <span class="text-lg font-semibold text-white">Ainstein</span>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <a href="#features" class="hover:text-white transition-colors">Funzionalita</a>
                    <a href="#pricing" class="hover:text-white transition-colors">Prezzi</a>
                    <a href="<?= url('/login') ?>" class="hover:text-white transition-colors">Accedi</a>
                    <a href="<?= url('/register') ?>" class="hover:text-white transition-colors">Registrati</a>
                </div>
                <p class="text-sm">
                    © <?= date('Y') ?> Ainstein. Tutti i diritti riservati.
                </p>
            </div>
        </div>
    </footer>

    <!-- Fade-in Animation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        // Stagger the animation
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>
