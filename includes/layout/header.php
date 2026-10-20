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

  <!-- ===================== LOCAL & WEB FONTS ===================== -->
  <link rel="preload" href="assets/fonts/Satoshi-Regular.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/Satoshi-Medium.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="assets/fonts/NibPro-LightItalic.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/css/fonts.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- ===================== TAILWIND (CDN) ===================== -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'nx-bg': '#F8FAFC',
            'nx-white': '#FFFFFF',
            'nx-black': '#0F172A',
            'nx-grey': '#475569',
            'nx-light-grey': '#94A3B8',
            'nx-border': '#E2E8F0',
            'nx-blue': '#2563EB',
            'nx-dark-blue': '#1D4ED8',
            'nx-emerald': '#059669',
            'nx-navy': '#0A0F1D',
          },
          fontFamily: {
            sans: ['Satoshi', 'Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
            heading: ['Satoshi', 'Plus Jakarta Sans', 'Inter', 'sans-serif'],
            satoshi: ['Satoshi', 'Inter', 'sans-serif'],
            nib: ['Nib Pro', 'Georgia', 'serif'],
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
    /* ===================== LUXURY DESIGN TOKENS ===================== */
    :root {
      --nx-bg: #F8FAFC;
      --nx-white: #FFFFFF;
      --nx-black: #0F172A;
      --nx-grey: #475569;
      --nx-border: #E2E8F0;
      --nx-blue: #2563EB;
      --nx-dark-blue: #1D4ED8;
      --nx-emerald: #059669;
      --nx-navy: #0A0F1D;
    }

    html,
    body {
      background-color: var(--nx-bg);
      color: var(--nx-black);
      font-family: 'Satoshi', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      letter-spacing: -0.011em;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      overflow-x: clip;
    }

    h1, h2, h3, h4, .font-heading {
      font-family: 'Satoshi', 'Plus Jakarta Sans', 'Inter', sans-serif;
      letter-spacing: -0.035em;
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
      background-color: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(226, 232, 240, 0.85);
      border-radius: 999px;
      box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.06);
      transition: background-color 0.35s ease, border-color 0.35s ease, box-shadow 0.35s ease, top 0.35s ease;
    }

    #nx-header-bar.nx-scrolled {
      top: 14px;
      background-color: rgba(255, 255, 255, 0.95);
      border-color: rgba(203, 213, 225, 0.9);
      box-shadow: 0 8px 30px -4px rgba(15, 23, 42, 0.1);
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
      background: rgba(248, 250, 252, 0.8);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border: 1px solid rgba(226, 232, 240, 0.9);
      border-radius: 999px;
      padding: 4px 6px;
      display: flex;
      align-items: center;
      gap: 2px;
      box-shadow: 0 2px 10px -2px rgba(15, 23, 42, 0.04);
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
      background-color: rgba(15, 23, 42, 0.05);
      transform: translateY(-1px);
    }

    .nx-nav-link.is-active {
      color: var(--nx-black);
      font-weight: 600;
      background-color: #FFFFFF;
      box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(226, 232, 240, 0.9);
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
      background: var(--nx-blue);
    }

    /* ===================== LOCATION MEGA DROPDOWN ===================== */
    .nx-dropdown-wrap {
      position: relative;
      display: inline-flex;
      align-items: center;
    }

    .nx-dropdown-menu {
      position: absolute;
      top: calc(100% + 14px);
      left: -16px;
      width: 590px;
      max-width: 92vw;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(226, 232, 240, 0.95);
      border-radius: 24px;
      padding: 16px 18px;
      box-shadow: 0 24px 60px -12px rgba(15, 23, 42, 0.14), 0 0 0 1px rgba(255, 255, 255, 0.9) inset;
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transform: translateY(8px);
      transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.22s;
      z-index: 250;
    }

    /* Invisible hover bridge */
    .nx-dropdown-menu::before {
      content: '';
      position: absolute;
      top: -14px;
      left: 0;
      right: 0;
      height: 14px;
    }

    /* Top pointer notch */
    .nx-dropdown-menu::after {
      content: '';
      position: absolute;
      top: -6px;
      left: 38px;
      transform: rotate(45deg);
      width: 12px;
      height: 12px;
      background: rgba(255, 255, 255, 0.98);
      border-top: 1px solid rgba(226, 232, 240, 0.95);
      border-left: 1px solid rgba(226, 232, 240, 0.95);
    }

    .nx-dropdown-wrap:hover .nx-dropdown-menu,
    .nx-dropdown-wrap.is-open .nx-dropdown-menu {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      transform: translateY(0);
    }

    .nx-dropdown-wrap:hover .nx-caret,
    .nx-dropdown-wrap.is-open .nx-caret {
      transform: rotate(180deg);
      color: var(--nx-blue);
    }

    /* ===================== LUXURY BUTTON (SAPPHIRE COBALT) ===================== */
    .nx-nav-btn {
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #1E40AF 0%, #2563EB 55%, #1D4ED8 100%);
      background-color: #2563EB;
      color: #FFFFFF;
      border: 1px solid rgba(255, 255, 255, 0.25);
      font-size: 13.5px;
      font-weight: 600;
      letter-spacing: -0.01em;
      padding: 10px 24px;
      border-radius: 999px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      box-shadow: 0 4px 18px rgba(37, 99, 235, 0.32);
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
      pointer-events: none;
    }

    #nx-mobile-drawer.is-open {
      transform: translateY(0);
      pointer-events: auto;
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
        <span class="font-bold text-[19px] tracking-[-0.03em] text-slate-900 font-sans">NEXORA</span>
        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 shadow-[0_0_8px_rgba(37,99,235,0.6)] transition-transform duration-300 group-hover:scale-125"></span>
      </a>

      <!-- Center: Navigation Links (Rounded Pill Container) -->
      <nav class="hidden md:flex items-center nx-nav-pill" aria-label="Main Navigation">
        <!-- 1. Location (Mega Dropdown for India & UAE Mega Cities) -->
        <div class="nx-dropdown-wrap" id="nx-location-menu-wrap">
          <button type="button" 
                  id="nx-location-btn" 
                  class="nx-nav-link nx-hoverable flex items-center gap-1.5 focus:outline-none cursor-pointer"
                  aria-expanded="false" 
                  aria-haspopup="true">
            <svg class="w-3.5 h-3.5 text-nx-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span>Location</span>
            <svg class="nx-caret w-3 h-3 text-nx-grey transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>

          <!-- Dropdown Mega Menu -->
          <div class="nx-dropdown-menu" role="menu" aria-label="Location Submenu">
            <!-- Header bar -->
            <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-slate-200">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] font-mono font-bold tracking-wider uppercase text-slate-500">Global Innovation Corridors</span>
              </div>
              <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200/80">India &middot; UAE Deals</span>
            </div>

            <!-- Two-Column Grid: India & UAE -->
            <div class="grid grid-cols-2 gap-3.5">
              
              <!-- Column 1: India Mega Cities -->
              <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-3">
                <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-200/60">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[17px]">🇮🇳</span>
                    <div>
                      <div class="text-[12.5px] font-bold text-slate-900 leading-tight">India</div>
                      <div class="text-[10px] text-slate-500">8 Mega Hubs</div>
                    </div>
                  </div>
                  <span class="text-[9px] font-mono font-bold uppercase text-emerald-700 bg-emerald-100/80 px-1.5 py-0.5 rounded">Active</span>
                </div>

                <div class="grid grid-cols-2 gap-1 text-[12px]">
                  <a href="#nx-opportunities" data-city="Mumbai" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Mumbai</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Delhi NCR" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Delhi NCR</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Bengaluru" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Bengaluru</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Hyderabad" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Hyderabad</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Chennai" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Chennai</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Ahmedabad" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Ahmedabad</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Pune" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Pune</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Kolkata" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <span class="font-medium text-slate-700 group-hover:text-blue-700">Kolkata</span>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                </div>
              </div>

              <!-- Column 2: UAE Mega Cities -->
              <div class="bg-slate-50/70 border border-slate-200/80 rounded-2xl p-3">
                <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-200/60">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[17px]">🇦🇪</span>
                    <div>
                      <div class="text-[12.5px] font-bold text-slate-900 leading-tight">UAE</div>
                      <div class="text-[10px] text-slate-500">Mega Financial Hubs</div>
                    </div>
                  </div>
                  <span class="text-[9px] font-mono font-bold uppercase text-blue-700 bg-blue-100/80 px-1.5 py-0.5 rounded">Fastlane</span>
                </div>

                <div class="space-y-1 text-[12px]">
                  <a href="#nx-opportunities" data-city="Dubai" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <div>
                      <div class="font-medium text-slate-700 group-hover:text-blue-700">Dubai</div>
                      <div class="text-[10px] text-slate-400">DIFC &middot; Venture &amp; Tech Capital</div>
                    </div>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Abu Dhabi" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <div>
                      <div class="font-medium text-slate-700 group-hover:text-blue-700">Abu Dhabi</div>
                      <div class="text-[10px] text-slate-400">Hub71 &middot; Sovereign Scale</div>
                    </div>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Sharjah" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <div>
                      <div class="font-medium text-slate-700 group-hover:text-blue-700">Sharjah</div>
                      <div class="text-[10px] text-slate-400">SRTIP &middot; Creative &amp; Research</div>
                    </div>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                  <a href="#nx-opportunities" data-city="Ajman" class="nx-city-link group flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-white/95 hover:shadow-xs transition-all">
                    <div>
                      <div class="font-medium text-slate-700 group-hover:text-blue-700">Ajman</div>
                      <div class="text-[10px] text-slate-400">Logistics &amp; Trade Corridor</div>
                    </div>
                    <span class="text-[10px] text-slate-400 group-hover:text-blue-600 opacity-0 group-hover:opacity-100">&rarr;</span>
                  </a>
                </div>
              </div>

            </div>

            <!-- Footer Strip -->
            <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500">
              <span>Direct dealflow between India &amp; UAE venture corridors</span>
              <a href="#nx-opportunities" class="font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1 group/all">
                <span>View All Hub Deals</span>
                <span class="transition-transform group-hover/all:translate-x-0.5">&rarr;</span>
              </a>
            </div>
          </div>
        </div>

        <!-- 2. Investor -->
        <a href="#nx-investors" class="nx-nav-link nx-hoverable">Investor</a>

        <!-- 3. Founder -->
        <a href="#nx-startups-raise" class="nx-nav-link nx-hoverable">Founder</a>

        <!-- 4. Pricing -->
        <a href="#nx-pricing" class="nx-nav-link nx-hoverable">Pricing</a>

        <!-- 5. About -->
        <a href="#nx-about" class="nx-nav-link nx-hoverable">About</a>
      </nav>

      <!-- Right: Action Button -->
      <div class="hidden md:flex items-center gap-2.5">
        <?php 
          $activePersona = $_SESSION['portal_role'] ?? $_COOKIE['portal_role'] ?? 'investor';
        ?>
        <a href="<?= function_exists('url') ? url('index.php?choose=1') : 'index.php?choose=1' ?>" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white/90 hover:bg-slate-100 border border-slate-200 shadow-2xs transition-all"
           title="Click to change your experience between Investor and Founder">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>Role: <?= htmlspecialchars(ucfirst($activePersona)) ?></span>
          <span class="text-[10px] text-slate-400">⇄</span>
        </a>

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
    <div class="h-full flex flex-col justify-between px-8 pt-24 pb-8 overflow-y-auto">
      <nav class="flex flex-col gap-4" aria-label="Mobile Navigation Links">
        <!-- 1. Location (Expandable Accordion) -->
        <div class="border-b border-nx-border/60 pb-3">
          <button type="button" id="nx-mobile-loc-btn" class="w-full flex items-center justify-between text-[24px] font-semibold tracking-[-0.02em] text-nx-black">
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5 text-nx-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span>Location</span>
            </span>
            <svg id="nx-mobile-loc-caret" class="w-5 h-5 text-nx-grey transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>

          <!-- Collapsible City Options -->
          <div id="nx-mobile-loc-content" class="hidden pt-3 pl-1 space-y-3.5">
            <!-- India -->
            <div>
              <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <span>🇮🇳</span>
                <span>India (Mega Cities)</span>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <a href="#nx-opportunities" data-city="Mumbai" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Mumbai</a>
                <a href="#nx-opportunities" data-city="Delhi NCR" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Delhi NCR</a>
                <a href="#nx-opportunities" data-city="Bengaluru" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Bengaluru</a>
                <a href="#nx-opportunities" data-city="Hyderabad" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Hyderabad</a>
                <a href="#nx-opportunities" data-city="Chennai" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Chennai</a>
                <a href="#nx-opportunities" data-city="Ahmedabad" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Ahmedabad</a>
                <a href="#nx-opportunities" data-city="Pune" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Pune</a>
                <a href="#nx-opportunities" data-city="Kolkata" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Kolkata</a>
              </div>
            </div>

            <!-- UAE -->
            <div>
              <div class="text-[11.5px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <span>🇦🇪</span>
                <span>UAE (Mega Cities)</span>
              </div>
              <div class="flex flex-wrap gap-1.5">
                <a href="#nx-opportunities" data-city="Dubai" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Dubai</a>
                <a href="#nx-opportunities" data-city="Abu Dhabi" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Abu Dhabi</a>
                <a href="#nx-opportunities" data-city="Sharjah" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Sharjah</a>
                <a href="#nx-opportunities" data-city="Ajman" class="nx-mobile-city-link text-[12.5px] px-3 py-1 rounded-full bg-slate-100 text-slate-800 border border-slate-200 font-semibold hover:bg-blue-50 hover:text-blue-700">Ajman</a>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Investor -->
        <a href="#nx-investors" class="text-[24px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Investor</a>

        <!-- 3. Founder -->
        <a href="#nx-startups-raise" class="text-[24px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Founder</a>

        <!-- 4. Pricing -->
        <a href="#nx-pricing" class="text-[24px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">Pricing</a>

        <!-- 5. About -->
        <a href="#nx-about" class="text-[24px] font-semibold tracking-[-0.02em] text-nx-grey hover:text-nx-black transition-colors">About</a>
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

      // 4. Location Dropdown Toggle & Outside Click (Desktop)
      const locWrap = document.getElementById('nx-location-menu-wrap');
      const locBtn = document.getElementById('nx-location-btn');
      if (locWrap && locBtn) {
        locBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          const isOpen = locWrap.classList.toggle('is-open');
          locBtn.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', (e) => {
          if (!locWrap.contains(e.target)) {
            locWrap.classList.remove('is-open');
            locBtn.setAttribute('aria-expanded', 'false');
          }
        });

        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && locWrap.classList.contains('is-open')) {
            locWrap.classList.remove('is-open');
            locBtn.setAttribute('aria-expanded', 'false');
          }
        });
      }

      // 5. Location Accordion Toggle (Mobile)
      const mobileLocBtn = document.getElementById('nx-mobile-loc-btn');
      const mobileLocContent = document.getElementById('nx-mobile-loc-content');
      const mobileLocCaret = document.getElementById('nx-mobile-loc-caret');
      if (mobileLocBtn && mobileLocContent) {
        mobileLocBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          const isHidden = mobileLocContent.classList.toggle('hidden');
          if (mobileLocCaret) {
            mobileLocCaret.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
          }
        });
      }

      // 6. City links click handling (Desktop & Mobile)
      document.querySelectorAll('.nx-city-link, .nx-mobile-city-link').forEach((link) => {
        link.addEventListener('click', () => {
          if (locWrap) {
            locWrap.classList.remove('is-open');
            if (locBtn) locBtn.setAttribute('aria-expanded', 'false');
          }
          const city = link.getAttribute('data-city');
          if (window.nxFilterCity && city) {
            window.nxFilterCity(city);
          }
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