<?php if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 3)); ?>
<?php
$mobile_notificari_necitate_count = 0;
if (!empty($_SESSION['user_id']) && isset($pdo)) {
    require_once APP_ROOT . '/includes/notificari_helper.php';
    $mobile_notificari_necitate_count = notificari_count_necitate($pdo, (int)$_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(get_platform_name()); ?> - Asociația Nevăzătorilor Bihor</title>
    <link href="/css/tailwind.css?v=<?php echo @filemtime(APP_ROOT . '/css/tailwind.css') ?: '1'; ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.344.0"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Contrast ridicat pentru accesibilitate WCAG 2.1 AA */
        .focus-visible:focus { outline: 2px solid #f59e0b; outline-offset: 2px; }
    </style>
    <script>
        // Verifică preferința salvată sau folosește tema sistemului
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <script src="/js/theme-toggle.js"></script>
    <script src="/js/form-validation.js?v=7"></script>
    <script src="/js/mobile-navigation.js"></script>
    <script src="/js/accessibility-enhancements.js?v=2"></script>
    <script src="/js/form-ux-enhancements.js?v=3"></script>
    <style>
        /* Mobile optimizations */
        @media (max-width: 1023px) {
            /* Previne zoom pe input focus (iOS) */
            input[type="text"],
            input[type="email"],
            input[type="tel"],
            input[type="password"],
            input[type="number"],
            input[type="date"],
            input[type="search"],
            textarea,
            select {
                font-size: 16px !important;
            }

            /* Touch targets minim 44x44px (exclus shortcut-urile din dashboard) */
            button:not([class*="aspect-square"]),
            a:not([class*="aspect-square"]),
            input[type="submit"],
            input[type="button"] {
                min-height: 44px;
            }

            /* Modals full-screen pe mobile - arată ca o pagină nouă */
            dialog {
                max-width: 100% !important;
                max-height: 100% !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                border: none !important;
            }
            dialog > div {
                min-height: 100vh;
            }

            /* Tabele cu scroll indicator */
            .table-wrapper {
                position: relative;
            }

            /* Sidebar overlay */
            #mobile-sidebar-overlay {
                backdrop-filter: blur(2px);
            }
        }

        /* Touch optimization */
        * {
            -webkit-tap-highlight-color: transparent;
        }

        button, a, input[type="submit"], input[type="button"] {
            touch-action: manipulation;
        }

        /* Screen reader only - pentru skip links și anunțuri */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        .sr-only:focus,
        .sr-only:active,
        .focus\:not-sr-only:focus {
            position: static;
            width: auto;
            height: auto;
            padding: inherit;
            margin: inherit;
            overflow: visible;
            clip: auto;
            white-space: normal;
        }

        /* Aliniere titluri secțiuni la stânga în Dashboard și Setări */
        #main-content h2.text-lg.font-semibold,
        #main-content h3.text-lg.font-semibold {
            text-align: left !important;
        }

        /* Aliniere la stânga pentru toate celulele din tabele */
        table td {
            text-align: left !important;
        }
        table td a {
            text-align: left !important;
            display: inline-block;
        }

        /* Culori corecte pentru label-uri și iconuri în modul întunecat */
        label, .label {
            color: rgb(15 23 42) !important; /* slate-900 */
        }
        .dark label, .dark .label {
            color: rgb(255 255 255) !important; /* white */
        }

        /* Iconuri calendar și alte iconuri să fie vizibile în modul întunecat */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(0);
            opacity: 1;
        }
        .dark input[type="date"]::-webkit-calendar-picker-indicator,
        .dark input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 1;
        }

        /* Asigură contrast corect pentru toate textele */
        .text-slate-900 {
            color: rgb(15 23 42);
        }
        .dark .text-slate-900 {
            color: rgb(255 255 255);
        }

        /* Iconuri Lucide să fie vizibile */
        i[data-lucide] {
            color: inherit;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 flex h-screen overflow-hidden">
<!-- Top bar mobile: notificari stanga, logo centru, meniu dreapta -->
<div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-slate-900 dark:bg-slate-800 h-16 shadow-lg">
    <div class="relative h-full flex items-center justify-center px-3">
        <a href="/notificari"
           id="mobile-notifications-link"
           class="absolute left-3 inline-flex items-center justify-center w-11 h-11 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 <?php echo $mobile_notificari_necitate_count > 0 ? 'bg-violet-600 text-white hover:bg-violet-700' : 'bg-slate-800 text-white hover:bg-slate-700'; ?>"
           aria-label="<?php echo $mobile_notificari_necitate_count > 0 ? 'Notificări: ' . (int)$mobile_notificari_necitate_count . ' necitite' : 'Notificări'; ?>">
            <i data-lucide="bell" class="w-5 h-5" aria-hidden="true"></i>
            <?php if ($mobile_notificari_necitate_count > 0): ?>
                <span class="absolute -top-1 -right-1 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-red-600 text-[10px] leading-[1.15rem] font-bold text-white text-center" aria-live="polite" aria-atomic="true">
                    <?php echo (int)$mobile_notificari_necitate_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="/dashboard" class="inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" aria-label="Dashboard">
            <img src="<?php echo defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''; ?>" alt="Logo platformă" class="h-12 w-auto object-contain">
        </a>

        <button id="mobile-menu-btn"
                type="button"
                class="absolute right-3 inline-flex items-center justify-center w-11 h-11 rounded-lg bg-slate-800 text-white hover:bg-slate-700 transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900"
                aria-label="Deschide meniul principal"
                aria-controls="navigation"
                aria-expanded="false">
            <i data-lucide="menu" class="w-5 h-5" aria-hidden="true"></i>
        </button>
    </div>
</div>