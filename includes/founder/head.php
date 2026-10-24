<?php
/**
 * Shared Founder Head Component
 * Initializes Tailwind CDN with Class-based Dark Mode
 * Fast, flicker-free theme detection before paint
 * Loads standardized theme tokens, fonts, and micro-animations
 */
?>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<script>
    (function () {

        try {
            var savedTheme = localStorage.getItem('startup_portal_theme') ||
                (document.cookie.match(/startup_portal_theme=([^;]+)/) ? RegExp.$1 : null) ||
                (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) { }
    })();
</script>
<script>
    window.tailwind = {
        config: {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        slate: {
                            750: '#243044',
                            850: '#151F32',
                            950: '#0B1120'
                        }
                    },
                    fontFamily: {
                        sans: ['Satoshi', 'Inter', 'sans-serif'],
                        heading: ['Satoshi', 'Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="<?= function_exists('url') ? url('assets/css/fonts.css') : '../assets/css/fonts.css' ?>">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<meta name="csrf-token" content="<?= function_exists('csrf_token') ? csrf_token() : '' ?>">
<script>window.APP_URL = "<?= function_exists('url') ? rtrim(url('/'), '/') : '' ?>";</script>
<script src="<?= function_exists('url') ? url('assets/js/connect.js') : '../assets/js/connect.js' ?>"></script>
<?php include_once __DIR__ . '/theme.php'; ?>