<?php
/**
 * Universal Smooth Scroll Engine (Lenis + Momentum Scroll + Smooth CSS)
 * Enables buttery-smooth inertia scrolling across the full web portal.
 */
?>
<!-- Smooth Scroll Styles -->
<style>
    html {
        scroll-behavior: smooth !important;
    }
    html.lenis, html.lenis body {
        height: auto;
    }
    .lenis.lenis-smooth {
        scroll-behavior: auto !important;
    }
    .lenis.lenis-smooth [data-lenis-prevent] {
        overscroll-behavior: contain;
    }
    .lenis.lenis-stopped {
        overflow: hidden;
    }
    .lenis.lenis-smooth iframe {
        pointer-events: none;
    }

    /* Custom Ultra-Smooth Modern Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
        transition: background 0.2s ease;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Inner scroll containers smooth scrolling */
    .overflow-y-auto, .overflow-auto, main {
        scroll-behavior: smooth !important;
        -webkit-overflow-scrolling: touch;
    }
</style>

<!-- Lenis Smooth Scrolling Engine -->
<script src="<?= url('assets/js/lenis.min.js') ?>"></script>
<script>
    (function () {
        if (typeof Lenis !== 'undefined') {
            // Initialize Lenis for window
            const lenis = new Lenis({
                duration: 1.15,
                easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // smooth exponential deceleration
                orientation: 'vertical',
                gestureOrientation: 'vertical',
                smoothWheel: true,
                wheelMultiplier: 0.92,
                touchMultiplier: 1.5,
                smoothTouch: false,
                infinite: false,
            });

            window.__lenis = lenis;

            function raf(time) {
                lenis.raf(time);
                requestAnimationFrame(raf);
            }
            requestAnimationFrame(raf);

            // Connect GSAP ticker to Lenis if GSAP is loaded
            if (typeof gsap !== 'undefined') {
                gsap.ticker.add((time) => {
                    lenis.raf(time * 1000);
                });
                gsap.ticker.lagSmoothing(0);
            }

            // Smooth scroll for all hash links (#updates, #blogs, #overview, etc.)
            document.addEventListener('click', (e) => {
                const anchor = e.target.closest('a[href^="#"]');
                if (anchor) {
                    const href = anchor.getAttribute('href');
                    if (href && href.length > 1 && !href.startsWith('#!')) {
                        const targetEl = document.querySelector(href);
                        if (targetEl) {
                            e.preventDefault();
                            lenis.scrollTo(targetEl, { offset: -70, duration: 1.2 });
                        }
                    }
                }
            });
        }

        // Apply smooth scrolling to all inner scrollable areas
        document.addEventListener('DOMContentLoaded', () => {
            const containers = document.querySelectorAll('.overflow-y-auto, .overflow-auto');
            containers.forEach(el => {
                el.style.scrollBehavior = 'smooth';
            });
        });
    })();
</script>
