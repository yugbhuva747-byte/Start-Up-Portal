<?php
/**
 * ============================================================================
 * include/layout/header.php
 * ----------------------------------------------------------------------------
 * Clean, Minimal, Premium Top Navigation for NEXORA (Startup × Investor Platform)
 * 
 * Tech: HTML5, Tailwind CSS, Poppins, GSAP 3, ScrollTrigger, Lenis Smooth Scroll
 * NO THREE.JS / NO WEBGL / NO PINK/GREEN GRADIENTS
 * ============================================================================
 */
?>
<!DOCTYPE html>
<!-- scroll-smooth removed: Lenis (JS-driven smoothing) already owns scroll behavior;
     keeping the native CSS scroll-behavior:smooth alongside it made the two systems
     fight each other on every anchor click, which is what caused the jerky jumps. -->
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>NEXORA — Where Startups Meet Investors</title>
  <meta name="description"
    content="Discover ambitious companies and investment opportunities in one trusted, premium platform. Built for India's emerging innovators and allocators.">

  <!-- ===================== FONTS ===================== -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- ===================== TAILWIND (CDN) ===================== -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'nx-bg': '#FAFAF8',
            'nx-white': '#FFFFFF',
            'nx-black': '#111111',
            'nx-grey': '#686868',
            'nx-light-grey': '#9A9A94',
            'nx-border': '#E8E8E3',
            'nx-green': '#173C9F',
            'nx-dark-green': '#0E2970',
            'nx-blue': '#173C9F',
            'nx-dark-blue': '#0E2970',
          },
          fontFamily: {
            sans: ['Poppins', 'sans-serif'],
          },
        },
      },
    };
  </script>

  <!-- ===================== GSAP + SCROLLTRIGGER ===================== -->
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>

  <!-- ===================== LENIS SMOOTH SCROLL ===================== -->
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>

  <style>
    /* ===================== DESIGN TOKENS ===================== */
    :root {
      --nx-bg: #FAFAF8;
      --nx-white: #FFFFFF;
      --nx-black: #111111;
      --nx-grey: #686868;
      --nx-border: #E8E8E3;
      --nx-green: #173C9F;
      --nx-dark-green: #0E2970;
      --nx-blue: #173C9F;
      --nx-dark-blue: #0E2970;
    }

    html,
    body {
      background-color: var(--nx-bg);
      color: var(--nx-black);
      font-family: 'Poppins', sans-serif;
      overflow-x: clip;
    }

    ::selection {
      background: var(--nx-green);
      color: #FFFFFF;
    }

    /* ===================== FLOATING ROUNDED NAVBAR ===================== */
    #nx-header-bar {
      position: fixed;
      top: 24px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 32px);
      max-width: 1320px;
      height: 64px;
      z-index: 100;
      background-color: rgba(255,255,255,.78);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(232,232,227,.6);
      border-radius: 999px;
      box-shadow: 0 4px 24px -6px rgba(17,17,17,.06);
      transition: background-color 0.35s ease, border-color 0.35s ease, box-shadow 0.35s ease, top 0.35s ease;
    }

    #nx-header-bar.nx-scrolled {
      top: 14px;
      background-color: rgba(255,255,255,.88);
      border-color: rgba(232,232,227,.8);
      box-shadow: 0 6px 28px -6px rgba(17,17,17,.08);
    }

    @media (max-width: 768px) {
      #nx-header-bar {
        top: 16px;
        width: calc(100% - 24px);
      }
      #nx-header-bar.nx-scrolled {
        top: 10px;
      }
    }

    /* Centered glass pill nav container */
    .nx-nav-pill {
      background: rgba(255,255,255,.72);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(232,232,227,.7);
      border-radius: 999px;
      padding: 4px 6px;
      display: flex;
      align-items: center;
      gap: 2px;
      box-shadow: 0 2px 12px -3px rgba(17,17,17,.04);
    }

    .nx-nav-link {
      position: relative;
      font-size: 13.5px;
      font-weight: 500;
      color: var(--nx-grey);
      text-decoration: none;
      padding: 8px 16px;
      border-radius: 999px;
      transition: color 0.25s ease, background-color 0.3s ease, transform 0.2s ease;
    }

    .nx-nav-link:hover {
      color: var(--nx-black);
      background-color: rgba(17,17,17,.05);
      transform: translateY(-1px);
    }

    .nx-nav-link.is-active {
      color: var(--nx-black);
      font-weight: 600;
      background-color: var(--nx-bg);
      box-shadow: 0 1px 4px rgba(17,17,17,.06), 0 0 0 1px rgba(232,232,227,.5);
    }

    .nx-nav-link.is-active::after {
      content: '';
      position: absolute;
      bottom: 6px;
      left: 50%;
      transform: translateX(-50%);
      width: 4px;
      height: 4px;
      border-radius: 50%;
      background: var(--nx-green);
    }

    /* ===================== PREMIUM BUTTON (#173C9F) ===================== */
    .nx-nav-btn {
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #1C46BA 0%, #173C9F 60%, #112F82 100%);
      background-color: #173C9F;
      color: #FFFFFF;
      border: 1px solid rgba(255, 255, 255, 0.22);
      font-size: 13.5px;
      font-weight: 600;
      letter-spacing: -0.01em;
      padding: 10px 24px;
      border-radius: 999px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      box-shadow: 0 4px 16px rgba(23, 60, 159, 0.32);
      transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                  box-shadow 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                  border-color 0.25s ease,
                  background 0.3s ease;
      will-change: transform, box-shadow;
    }

    /* Ambient Shimmer Sweep Animation on Hover */
    .nx-nav-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -90%;
      width: 65%;
      height: 100%;
      background: linear-gradient(120deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.32) 50%, rgba(255, 255, 255, 0) 100%);
      transform: skewX(-24deg);
      transition: left 0.65s cubic-bezier(0.16, 1, 0.3, 1);
      pointer-events: none;
    }

    .nx-nav-btn:hover {
      transform: translateY(-2px) scale(1.02);
      box-shadow: 0 8px 26px rgba(23, 60, 159, 0.48), 0 0 0 1px rgba(255, 255, 255, 0.35) inset;
      border-color: rgba(255, 255, 255, 0.45);
    }

    .nx-nav-btn:hover::before {
      left: 140%;
    }

    .nx-nav-btn:active {
      transform: translateY(0) scale(0.97);
      box-shadow: 0 2px 10px rgba(23, 60, 159, 0.3);
    }

    /* Arrow Micro-Interaction */
    .nx-nav-btn .nx-btn-arrow {
      display: inline-block;
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .nx-nav-btn:hover .nx-btn-arrow {
      transform: translateX(4px);
    }

    /* Mobile Drawer Menu */
    #nx-mobile-drawer {
      position: fixed;
      inset: 0;
      z-index: 99;
      background-color: var(--nx-bg);
      transform: translateY(-100%);
      transition: transform 0.5s cubic-bezier(0.77, 0, 0.175, 1);
    }

    #nx-mobile-drawer.is-open {
      transform: translateY(0);
    }

    /* Hamburger Toggle Icon */
    #nx-burger-icon {
      width: 24px;
      height: 18px;
      position: relative;
      cursor: pointer;
    }

    #nx-burger-icon span {
      position: absolute;
      left: 0;
      right: 0;
      height: 2px;
      background: var(--nx-black);
      border-radius: 2px;
      transition: transform 0.3s ease, opacity 0.3s ease, top 0.3s ease;
    }

    #nx-burger-icon span:nth-child(1) { top: 0; }
    #nx-burger-icon span:nth-child(2) { top: 8px; }
    #nx-burger-icon span:nth-child(3) { top: 16px; }

    #nx-burger-icon.is-open span:nth-child(1) {
      top: 8px;
      transform: rotate(45deg);
    }

    #nx-burger-icon.is-open span:nth-child(2) {
      opacity: 0;
    }

    #nx-burger-icon.is-open span:nth-child(3) {
      top: 8px;
      transform: rotate(-45deg);
    }

    @media (prefers-reduced-motion: reduce) {
      * { transition-duration: 0.001ms !important; }
    }
  </style>
</head>

<body class="bg-nx-bg text-nx-black antialiased selection:bg-nx-green selection:text-white">

  <!-- ===================== HEADER ===================== -->
  <header id="nx-header-bar" role="banner">
    <div class="max-w-[1440px] h-full mx-auto px-6 lg:px-14 flex items-center justify-between">

      <!-- Left: Logo placeholder NEXORA -->
      <a href="<?= function_exists('url') ? url('index.php') : 'index.php' ?>" class="flex items-center gap-2 nx-hoverable group" aria-label="NEXORA — Return to Home">
        <span class="font-bold text-[19px] tracking-[-0.03em] text-nx-black font-sans">NEXORA</span>
        <span class="w-1.5 h-1.5 rounded-full bg-nx-green transition-transform duration-300 group-hover:scale-125"></span>
      </a>

      <!-- Center: Navigation Links (Rounded Pill Container) -->
      <nav class="hidden md:flex items-center nx-nav-pill" aria-label="Main Navigation">
        <a href="#nx-hero" class="nx-nav-link is-active nx-hoverable">Home</a>
        <a href="#nx-opportunities" class="nx-nav-link nx-hoverable">Opportunities</a>
        <a href="#nx-ecosystem" class="nx-nav-link nx-hoverable">How It Works</a>
        <a href="#nx-investors" class="nx-nav-link nx-hoverable">Investors</a>
        <a href="#nx-startups-raise" class="nx-nav-link nx-hoverable">Startups</a>
        <a href="#nx-pricing" class="nx-nav-link nx-hoverable">Pricing</a>
        <a href="#nx-learning" class="nx-nav-link nx-hoverable">Learn</a>
      </nav>

      <!-- Right: Action Button -->
      <div class="hidden md:flex items-center gap-2">
        <?php if (function_exists('auth_check') && auth_check()): 
          $u = current_user();
          $dashUrl = $u['role'] === 'founder' ? url('founder/dashboard.php') : ($u['role'] === 'investor' ? url('investor/dashboard.php') : url('admin/dashboard.php'));
        ?>
          <a href="<?= $dashUrl ?>" class="nx-nav-btn nx-hoverable">
            <span>Dashboard (<?= htmlspecialchars(ucfirst($u['role'])) ?>)</span>
            <span class="nx-btn-arrow" aria-hidden="true">&rarr;</span>
          </a>
        <?php else: ?>
          <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="nx-nav-btn nx-hoverable">
            <span>Login / Portal</span>
            <span class="nx-btn-arrow" aria-hidden="true">&rarr;</span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Mobile Hamburger Button -->
      <button id="nx-mobile-toggle" class="md:hidden p-2 nx-hoverable focus:outline-none" aria-label="Toggle Navigation Menu" aria-expanded="false" aria-controls="nx-mobile-drawer">
        <div id="nx-burger-icon">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </button>

    </div>
  </header>

  <!-- ===================== MOBILE FULLSCREEN DRAWER ===================== -->
  <div id="nx-mobile-drawer" role="dialog" aria-modal="true" aria-label="Mobile Navigation Menu" inert>
    <div class="h-full flex flex-col justify-between px-8 pt-28 pb-10">
      <nav class="flex flex-col gap-5" aria-label="Mobile Navigation Links">
        <a href="#nx-hero" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-black flex items-center justify-between">
          <span>Home</span>
          <span class="w-2 h-2 rounded-full bg-nx-green"></span>
        </a>
        <a href="#nx-opportunities" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Opportunities</a>
        <a href="#nx-ecosystem" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">How It Works</a>
        <a href="#nx-investors" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Investors</a>
        <a href="#nx-startups-raise" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Startups</a>
        <a href="#nx-pricing" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Pricing</a>
        <a href="#nx-learning" class="text-[28px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Learn</a>
      </nav>

      <div class="flex flex-col gap-4 pt-6 border-t border-nx-border">
        <?php if (function_exists('auth_check') && auth_check()): 
          $u = current_user();
          $dashUrl = $u['role'] === 'founder' ? url('founder/dashboard.php') : ($u['role'] === 'investor' ? url('investor/dashboard.php') : url('admin/dashboard.php'));
        ?>
          <a href="<?= $dashUrl ?>" class="nx-nav-btn text-center justify-center py-3.5 text-[15px]">
            <span>Dashboard (<?= htmlspecialchars(ucfirst($u['role'])) ?>)</span>
            <span class="nx-btn-arrow" aria-hidden="true">&rarr;</span>
          </a>
        <?php else: ?>
          <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="nx-nav-btn text-center justify-center py-3.5 text-[15px]">
            <span>Login / Portal</span>
            <span class="nx-btn-arrow" aria-hidden="true">&rarr;</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Header Scripts -->
  <script>
    (function () {
      'use strict';

      const headerBar = document.getElementById('nx-header-bar');
      const mobileToggle = document.getElementById('nx-mobile-toggle');
      const burgerIcon = document.getElementById('nx-burger-icon');
      const mobileDrawer = document.getElementById('nx-mobile-drawer');

      const isFinePointer = window.matchMedia('(pointer: fine)').matches;
      const isTouch = window.matchMedia('(hover: none)').matches;
      const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      // 1. Header scroll blur state
      function checkScroll() {
        if (window.scrollY > 30) {
          headerBar.classList.add('nx-scrolled');
        } else {
          headerBar.classList.remove('nx-scrolled');
        }
      }
      window.addEventListener('scroll', checkScroll, { passive: true });
      checkScroll();

      // 2. Mobile drawer toggle & auto-close on link click
      let isDrawerOpen = false;
      if (mobileToggle && mobileDrawer && burgerIcon) {
        function closeDrawer() {
          if (!isDrawerOpen) return;
          isDrawerOpen = false;
          burgerIcon.classList.remove('is-open');
          mobileDrawer.classList.remove('is-open');
          mobileToggle.setAttribute('aria-expanded', 'false');
          mobileDrawer.setAttribute('inert', '');
          document.body.style.overflow = '';
        }

        mobileToggle.addEventListener('click', () => {
          isDrawerOpen = !isDrawerOpen;
          burgerIcon.classList.toggle('is-open', isDrawerOpen);
          mobileDrawer.classList.toggle('is-open', isDrawerOpen);
          mobileToggle.setAttribute('aria-expanded', String(isDrawerOpen));

          if (isDrawerOpen) {
            mobileDrawer.removeAttribute('inert');
            document.body.style.overflow = 'hidden';
          } else {
            mobileDrawer.setAttribute('inert', '');
            document.body.style.overflow = '';
          }
        });

        // Close drawer immediately when any link inside it is clicked
        mobileDrawer.querySelectorAll('a').forEach((link) => {
          link.addEventListener('click', closeDrawer);
        });
      }

      // 3. Desktop active pill highlight
      const navLinks = document.querySelectorAll('.nx-nav-pill .nx-nav-link');
      navLinks.forEach((link) => {
        link.addEventListener('click', () => {
          navLinks.forEach((l) => l.classList.remove('is-active'));
          link.classList.add('is-active');
        });
      });



      // 4. Lenis Smooth Scroll Setup
      // NOTE: Lenis must be driven by exactly ONE raf loop. Previously this ran
      // BOTH a manual requestAnimationFrame loop AND gsap.ticker at the same time,
      // which double-drove lenis.raf() every frame and caused the stutter/jank.
      // Fixed: gsap.ticker drives it when GSAP is present (recommended pairing,
      // keeps ScrollTrigger perfectly in sync), otherwise fall back to a single
      // native raf loop.
      if (typeof Lenis !== 'undefined' && !prefersReducedMotion && !isTouch) {
        const lenis = new Lenis({
          duration: 1.05,
          smoothWheel: true,
          easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        });

        // Expose globally so other scripts (e.g. anchor-link navigation) can
        // route scroll requests through Lenis instead of fighting it with
        // native window.scrollTo().
        window.__lenis = lenis;

        if (window.gsap && window.ScrollTrigger) {
          lenis.on('scroll', ScrollTrigger.update);
          gsap.ticker.add((time) => lenis.raf(time * 1000));
          gsap.ticker.lagSmoothing(0);
        } else {
          function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
          }
          requestAnimationFrame(raf);
        }
      }
    })();
  </script>