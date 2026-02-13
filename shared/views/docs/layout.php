<?php
// Variables available: $title, $content, $currentPage
$currentPage = $currentPage ?? '';
$title = $title ?? 'Documentazione - Ainstein';

// Branding dinamico
$_docsBrandPrimary = \Core\Settings::get('brand_color_primary', '#006e96');
$_docsBrandSecondary = \Core\Settings::get('brand_color_secondary', '#004d69');
$_docsBrandAccent = \Core\Settings::get('brand_color_accent', '#00a3d9');
$_docsBrandFont = \Core\Settings::get('brand_font', 'Inter');
$_docsBrandFavicon = \Core\Settings::get('brand_favicon', '') ?: 'favicon.svg';
$_docsPrimaryShades = \Core\BrandingHelper::generateShades($_docsBrandPrimary);
$_docsSecondaryShades = \Core\BrandingHelper::generateShades($_docsBrandSecondary);
$_docsAccentShades = \Core\BrandingHelper::generateShades($_docsBrandAccent);

$navItems = [
    ['slug' => 'getting-started', 'label' => 'Primi Passi', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
    ['slug' => 'ai-content', 'label' => 'AI Content Generator', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'],
    ['slug' => 'seo-audit', 'label' => 'SEO Audit', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ['slug' => 'seo-tracking', 'label' => 'SEO Tracking', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>'],
    ['slug' => 'keyword-research', 'label' => 'Keyword Research', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>'],
    ['slug' => 'internal-links', 'label' => 'Internal Links', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>'],
    ['slug' => 'ads-analyzer', 'label' => 'Google Ads Analyzer', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>'],
    ['slug' => 'separator', 'label' => '', 'icon' => ''],
    ['slug' => 'credits', 'label' => 'Sistema Crediti', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
    ['slug' => 'faq', 'label' => 'FAQ', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
];
?>
<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>

    <!-- Favicon -->
    <?php $__docsFavExt = pathinfo($_docsBrandFavicon, PATHINFO_EXTENSION); ?>
    <link rel="icon" type="image/<?= $__docsFavExt === 'svg' ? 'svg+xml' : ($__docsFavExt === 'ico' ? 'x-icon' : 'png') ?>" href="<?= url('/' . $_docsBrandFavicon) ?>">
    <link rel="apple-touch-icon" href="<?= url('/' . $_docsBrandFavicon) ?>">
    <meta name="theme-color" content="<?= e($_docsBrandPrimary) ?>">

    <!-- Tailwind CSS with Typography plugin -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['<?= e($_docsBrandFont) ?>', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            <?php foreach ($_docsPrimaryShades as $shade => $hex): ?>
                            <?= $shade ?>: '<?= $hex ?>',
                            <?php endforeach; ?>
                        },
                        secondary: {
                            <?php foreach ($_docsSecondaryShades as $shade => $hex): ?>
                            <?= $shade ?>: '<?= $hex ?>',
                            <?php endforeach; ?>
                        },
                        accent: {
                            <?php foreach ($_docsAccentShades as $shade => $hex): ?>
                            <?= $shade ?>: '<?= $hex ?>',
                            <?php endforeach; ?>
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($_docsBrandFont) ?>:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        [x-cloak] { display: none !important; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        /* Smooth scroll */
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900 font-sans"
      x-data="{
          darkMode: localStorage.getItem('darkMode') === 'true',
          sidebarOpen: false
      }"
      x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))"
      :class="{ 'dark': darkMode }">

    <div class="min-h-full flex flex-col">
        <!-- Top Header -->
        <header class="sticky top-0 z-40 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Left: Logo + hamburger -->
                    <div class="flex items-center gap-4">
                        <!-- Mobile hamburger -->
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                            </svg>
                        </button>

                        <!-- Logo -->
                        <a href="<?= url('/') ?>" class="flex items-center gap-2">
                            <img src="<?= url('/assets/images/logo-ainstein-orizzontal.png') ?>" alt="Ainstein" class="h-8 dark:brightness-0 dark:invert">
                        </a>
                    </div>

                    <!-- Right: Nav links -->
                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="<?= url('/') ?>" class="hidden sm:inline-flex text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Home</a>
                        <a href="<?= url('/docs') ?>" class="hidden sm:inline-flex text-sm font-medium text-primary-600 dark:text-primary-400">Documentazione</a>

                        <!-- Dark mode toggle -->
                        <button @click="darkMode = !darkMode" class="p-2 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </button>

                        <a href="<?= url('/login') ?>" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Accedi</a>
                        <a href="<?= url('/register') ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-500 hover:bg-primary-600 rounded-lg transition-colors shadow-sm">Registrati</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Body: sidebar + content -->
        <div class="flex-1 flex">
            <!-- Mobile sidebar backdrop -->
            <div x-show="sidebarOpen" x-cloak
                 class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                 @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>

            <!-- Sidebar -->
            <aside class="fixed inset-y-16 left-0 z-30 w-72 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 overflow-y-auto transform transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-auto lg:w-64 xl:w-72 lg:shrink-0"
                   :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <nav class="p-4 lg:p-6 space-y-1">
                    <p class="px-3 mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Documentazione</p>

                    <?php foreach ($navItems as $item): ?>
                        <?php if ($item['slug'] === 'separator'): ?>
                            <div class="my-3 border-t border-slate-200 dark:border-slate-700"></div>
                        <?php else: ?>
                            <a href="<?= url('/docs/' . $item['slug']) ?>"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentPage === $item['slug'] ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' ?>"
                               @click="sidebarOpen = false">
                                <svg class="w-5 h-5 shrink-0 <?= $currentPage === $item['slug'] ? 'text-primary-500 dark:text-primary-400' : 'text-slate-400 dark:text-slate-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <?= $item['icon'] ?>
                                </svg>
                                <?= e($item['label']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <!-- Main content area -->
            <main class="flex-1 min-w-0 bg-slate-50 dark:bg-slate-900">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
                    <?= $content ?>
                </div>
            </main>
        </div>

        <!-- Footer -->
        <footer class="border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">&copy; 2026 Ainstein - Tutti i diritti riservati</p>
            </div>
        </footer>
    </div>

</body>
</html>
