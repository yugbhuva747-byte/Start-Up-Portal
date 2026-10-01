<?php
/**
 * ============================================================================
 * NEXORA — Where Startups Meet Investors
 * Master Homepage / Index Page
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Base path
$basePath = __DIR__;

// ------------------------------------------------------------
// Layout: Header
// ------------------------------------------------------------
require_once $basePath . '/includes/layout/header.php';
?>

<main id="nx-page" class="nx-page relative w-full overflow-x-clip bg-[#FAF5FF] text-[#0F172A]">

    <!-- 01 & 02. Stacking Sections (Signup pinned at top, Hero stacks over it on scroll) -->
    <div id="nx-stack-wrapper" class="relative w-full">
      <?php
      require_once $basePath . '/includes/home/signup-section.php';
      require_once $basePath . '/includes/home/hero.php';
      ?>
    </div>

    <?php
    // ------------------------------------------------------------
    // 03. Investment Opportunities — Live Infinite Auto-Scroll Dealflow
    // (Hook visitor immediately with tangible companies, rounds & ARR)
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/opportunities.php';

    // ------------------------------------------------------------
    // 03. Ecosystem — How It Works (2 Sides, 4 Interactive 3D Steps)
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/ecosystem.php';

    // ------------------------------------------------------------
    // 04. For Investors — Institutional Diligence Dossier & 3D Terminal
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/investors.php';
    

    // ------------------------------------------------------------
    // 07. Transparent Venture Pricing — 4 Tiers & Monthly/Annual Toggle
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/pricing.php';


    // ------------------------------------------------------------
    // 05. For Startups — 3D Interactive Presentation Stage & Raise Flow
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/startups-raise.php';

    // ------------------------------------------------------------
    // 06. Learning & Academy — Full-Screen Dark Cinematic Knowledge Hub
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/learning.php';

   

    // ------------------------------------------------------------
    // 08. Final CTA — High Conversion Closing Action
    // ------------------------------------------------------------
    require_once $basePath . '/includes/home/final-cta.php';
    ?>

</main>

<?php
// ------------------------------------------------------------
// Layout: Footer
// ------------------------------------------------------------
require_once $basePath . '/includes/layout/footer.php';
?>

<!-- ============================================================
     NEXORA — GLOBAL PAGE MOTION ENGINE
     ============================================================ -->
<script>
    document.addEventListener('DOMContentLoaded', () => {

        /* 1. Respect Reduced Motion */
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /* 2. Check GSAP & ScrollTrigger Availability */
        if (reduceMotion || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
            // Failsafe: Ensure everything is immediately visible if animation is disabled/missing
            document.querySelectorAll('.nx-reveal, .nx-text-reveal, .nx-image-reveal').forEach(el => {
                el.style.opacity = '1';
                el.style.transform = 'none';
            });
            return;
        }

        gsap.registerPlugin(ScrollTrigger);

        /* 3. Global Section / Card Reveal (.nx-reveal) */
        const revealElements = gsap.utils.toArray('.nx-reveal');
        revealElements.forEach((element) => {
            gsap.fromTo(
                element,
                { y: 35, opacity: 0 },
                {
                    y: 0,
                    opacity: 1,
                    duration: 0.85,
                    ease: 'power3.out',
                    clearProps: 'transform,opacity',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 88%',
                        once: true
                    }
                }
            );
        });

        /* 4. Global Text & Headline Reveal (.nx-text-reveal) */
        const textElements = gsap.utils.toArray('.nx-text-reveal');
        textElements.forEach((element) => {
            gsap.fromTo(
                element,
                { y: 25, opacity: 0 },
                {
                    y: 0,
                    opacity: 1,
                    duration: 0.8,
                    ease: 'power3.out',
                    clearProps: 'transform,opacity',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 90%',
                        once: true
                    }
                }
            );
        });

        /* 5. Smooth Visual Reveal (.nx-image-reveal) */
        const imageElements = gsap.utils.toArray('.nx-image-reveal');
        imageElements.forEach((element) => {
            gsap.fromTo(
                element,
                { clipPath: 'inset(8% 4% 8% 4%)', scale: 1.03, opacity: 0 },
                {
                    clipPath: 'inset(0% 0% 0% 0%)',
                    scale: 1,
                    opacity: 1,
                    duration: 1,
                    ease: 'power3.inOut',
                    clearProps: 'clip-path,transform,opacity',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 85%',
                        once: true
                    }
                }
            );
        });

        /* 6. Subtle Parallax for Marked Elements ([data-parallax]) */
        const parallaxElements = gsap.utils.toArray('[data-parallax]');
        parallaxElements.forEach((element) => {
            const speed = parseFloat(element.dataset.parallax || '0.12');
            gsap.to(element, {
                yPercent: speed * -20,
                ease: 'none',
                scrollTrigger: {
                    trigger: element.closest('section') || element,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 1
                }
            });
        });

        /* 7. Bulletproof Smooth Anchor Navigation (Lenis + Native fallback) */
        document.querySelectorAll('a[href^="#"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                const href = link.getAttribute('href');
                if (!href || href === '#' || href.length < 2) return;

                // Strip '#' safely to find target element
                const rawId = href.slice(1);
                const target = document.getElementById(rawId) || document.querySelector(`[name="${rawId}"]`);

                if (!target) return;

                event.preventDefault();

                const header = document.querySelector('#nx-header-bar, #nx-header, header');
                const headerHeight = header ? header.offsetHeight : 0;

                // Priority A: Smooth via Lenis if loaded in header
                if (window.__lenis) {
                    window.__lenis.scrollTo(target, {
                        offset: -headerHeight,
                        duration: 1.2
                    });
                    return;
                }

                // Priority B: Native window smooth scroll
                const targetPosition = target.getBoundingClientRect().top + window.scrollY - headerHeight;
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            });
        });

        /* 8. Global Dynamic Stats Counter Engine (.nx-counter) */
        function runCounter(el) {
            if (el.dataset.counted === 'true') return;
            el.dataset.counted = 'true';

            const raw = el.innerText.trim();
            const match = raw.match(/^([^0-9.]*)([0-9]+(?:\.[0-9]+)?)(.*)$/);
            if (!match) return;

            const prefix = match[1] || '';
            const targetVal = parseFloat(match[2]);
            const suffix = match[3] || '';
            const isDecimal = match[2].includes('.');
            const decimals = isDecimal ? match[2].split('.')[1].length : 0;

            const counterObj = { val: 0 };
            gsap.to(counterObj, {
                val: targetVal,
                duration: 1.4,
                ease: 'power2.out',
                onUpdate: () => {
                    el.innerText = prefix + (decimals > 0 ? counterObj.val.toFixed(decimals) : Math.round(counterObj.val)) + suffix;
                },
                onComplete: () => {
                    el.innerText = raw;
                }
            });
        }

        const counterElements = gsap.utils.toArray('.nx-counter');
        counterElements.forEach((el) => {
            ScrollTrigger.create({
                trigger: el,
                start: 'top 94%',
                once: true,
                onEnter: () => runCounter(el)
            });
        });

        /* 9. Precise ScrollTrigger Refresh after Assets & Fonts Load */
        window.addEventListener('load', () => {
            requestAnimationFrame(() => {
                ScrollTrigger.refresh();
            });
        });

    });
</script>
