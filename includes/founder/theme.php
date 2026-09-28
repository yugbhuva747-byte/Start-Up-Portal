<?php
/**
 * Founder Theme Controller (Dark & Light Mode Support)
 * Seamless, FOUC-free switching with localStorage & cookie persistence
 * High-legibility contrast & smooth micro-animations
 * Standardized for "Vay Portal", Sans-serif typography
 */
?>
<!-- Theme Initialization Script (Prevents Flash of Unstyled Content) -->
<script>
    (function () {
        try {
            const savedTheme = localStorage.getItem('startup_portal_theme') || 
                (document.cookie.match(/startup_portal_theme=([^;]+)/) ? RegExp.$1 : null) ||
                (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Tell Tailwind CDN about class-based dark mode
            if (window.tailwind) {
                window.tailwind.config = window.tailwind.config || {};
                window.tailwind.config.darkMode = 'class';
            }
        } catch (e) {}
    })();
</script>

<!-- Global Founder Dark & Light Theme Styles & Micro-Animations -->
<style id="founder-theme-stylesheet">
    /* ----------------------------------------------------
       1. UNIVERSAL TYPOGRAPHY & SMOOTH TRANSITIONS
    ---------------------------------------------------- */
    html, body, button, input, select, textarea, h1, h2, h3, h4, h5, h6, p, span, a, label, table, th, td {
        font-family: "Vay Portal", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Silky-smooth color transition when toggling themes */
    html.dark, html.dark body, body,
    .founder-navbar, #main-sidebar, 
    .card-clean, .section-card, .studio-card, .cap-card,
    .form-input-clean, input, select, textarea, 
    header, aside, .hero-cap-banner, .hero-capital-banner {
        transition: background-color 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                    border-color 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                    color 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ----------------------------------------------------
       2. MODERN MICRO-ANIMATIONS & INTERACTION SUITE
    ---------------------------------------------------- */
    /* Staggered Entrance Animation for Page Sections & Cards */
    @keyframes founderFadeInUp {
        0% {
            opacity: 0;
            transform: translateY(14px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Applied to main content modules with subtle staggered timing */
    main > div, main > section, 
    .hero-cap-banner, .hero-capital-banner, .hero-banner {
        animation: founderFadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    main > div:nth-child(1) { animation-delay: 0.03s; }
    main > div:nth-child(2) { animation-delay: 0.07s; }
    main > div:nth-child(3) { animation-delay: 0.11s; }
    main > div:nth-child(4) { animation-delay: 0.15s; }
    main > div:nth-child(5) { animation-delay: 0.19s; }

    /* Card Micro-Hover Elevation & Rim Glow */
    .section-card, .card-clean, .studio-card, .cap-card {
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                    box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                    border-color 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                    background-color 0.25s ease !important;
    }
    .section-card:hover, .card-clean:hover, .studio-card:hover, .cap-card:hover {
        transform: translateY(-2px);
    }
    html:not(.dark) .section-card:hover, 
    html:not(.dark) .card-clean:hover {
        border-color: #CBD5E1 !important;
        box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.07), 0 2px 6px -1px rgba(15, 23, 42, 0.03) !important;
    }
    html.dark .section-card:hover, 
    html.dark .card-clean:hover {
        border-color: rgba(99, 102, 241, 0.45) !important;
        box-shadow: 0 12px 30px -4px rgba(0, 0, 0, 0.6), 0 0 15px -2px rgba(99, 102, 241, 0.2) !important;
    }

    /* Spring Action on Button Click */
    button:active, a.button:active, [type="submit"]:active {
        transform: scale(0.975);
    }

    /* Theme Switcher Button Icon Spin Animation */
    #founder-theme-toggle-btn {
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), 
                    background-color 0.2s ease, 
                    border-color 0.2s ease;
    }
    #founder-theme-toggle-btn:hover {
        transform: scale(1.08) rotate(8deg);
    }
    #founder-theme-toggle-btn:active {
        transform: scale(0.92);
    }
    #theme-sun-icon, #theme-moon-icon {
        transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
    }

    /* Pulse Glow for Live Status Badges */
    @keyframes badgePulseGlow {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.82;
            transform: scale(1.08);
        }
    }
    .animate-pulse {
        animation: badgePulseGlow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite !important;
    }

    /* ----------------------------------------------------
       3. LIGHT THEME (Default) Styles
    ---------------------------------------------------- */
    html:not(.dark) body {
        background-color: #F8FAFC !important;
        color: #0F172A !important;
    }
    html:not(.dark) .founder-navbar {
        background-color: rgba(255, 255, 255, 0.96) !important;
        border-color: #E2E8F0 !important;
    }
    html:not(.dark) #main-sidebar {
        background-color: #FFFFFF !important;
        border-color: #E2E8F0 !important;
    }

    /* ----------------------------------------------------
       4. DARK THEME PALETTE & SURFACES
    ---------------------------------------------------- */
    html.dark, html.dark body {
        background-color: #0B0F19 !important;
        color: #FFFFFF !important;
        color-scheme: dark;
    }

    /* Target hardcoded tailwind bg classes */
    html.dark .bg-\[\#F8FAFC\],
    html.dark .bg-\[\#FAFAFB\],
    html.dark .bg-\[\#F9FAFB\],
    html.dark .bg-\[\#FAFAFA\] {
        background-color: #0B0F19 !important;
    }

    /* Dark Navbar */
    html.dark .founder-navbar {
        background-color: rgba(15, 23, 42, 0.95) !important;
        border-color: #1E293B !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
    }

    /* Dark Sidebar */
    html.dark #main-sidebar {
        background-color: #0F172A !important;
        border-color: #1E293B !important;
    }
    html.dark #main-sidebar a {
        color: #CBD5E1 !important;
    }
    html.dark #main-sidebar a:hover {
        color: #FFFFFF !important;
        background-color: #1E293B !important;
    }
    html.dark #main-sidebar a.bg-indigo-50,
    html.dark #main-sidebar a[class*="text-indigo-700"] {
        background-color: rgba(99, 102, 241, 0.2) !important;
        color: #A5B4FC !important;
    }
    html.dark #main-sidebar a.bg-indigo-50 i,
    html.dark #main-sidebar a[class*="text-indigo-700"] i {
        color: #A5B4FC !important;
    }
    html.dark #main-sidebar .bg-slate-50,
    html.dark #main-sidebar .bg-slate-50\/70 {
        background-color: #131C2E !important;
        border-color: #1E293B !important;
    }
    html.dark #main-sidebar .border-t,
    html.dark #main-sidebar .border-r {
        border-color: #1E293B !important;
    }

    /* Dark Cards & Surfaces */
    html.dark .card-clean,
    html.dark .section-card,
    html.dark .studio-card,
    html.dark .cap-card,
    html.dark .bg-white {
        background-color: #111827 !important;
        border-color: #1E293B !important;
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.35) !important;
    }

    /* Secondary surfaces and subtle container fills */
    html.dark .bg-slate-50,
    html.dark .bg-slate-50\/50,
    html.dark .bg-slate-50\/70,
    html.dark .bg-slate-50\/80 {
        background-color: #0F172A !important;
    }
    html.dark .bg-slate-100 {
        background-color: #1E293B !important;
    }
    html.dark .bg-slate-200 {
        background-color: #334155 !important;
    }

    /* Translucent pills (e.g. bg-white/80, bg-white/95) */
    html.dark .bg-white\/80,
    html.dark .bg-white\/90,
    html.dark .bg-white\/95 {
        background-color: rgba(17, 24, 39, 0.88) !important;
        border-color: #1E293B !important;
        color: #FFFFFF !important;
    }

    /* Alternate row / stake fills */
    html.dark .bg-indigo-50\/20,
    html.dark .bg-indigo-50\/25,
    html.dark .bg-indigo-50\/60,
    html.dark .bg-indigo-50\/70 {
        background-color: rgba(99, 102, 241, 0.12) !important;
        border-color: rgba(99, 102, 241, 0.25) !important;
    }
    html.dark .bg-emerald-50\/20,
    html.dark .bg-emerald-50\/25,
    html.dark .bg-emerald-50\/60,
    html.dark .bg-emerald-50\/70 {
        background-color: rgba(16, 185, 129, 0.12) !important;
        border-color: rgba(16, 185, 129, 0.25) !important;
    }
    html.dark .bg-amber-50\/20,
    html.dark .bg-amber-50\/25,
    html.dark .bg-amber-50\/60,
    html.dark .bg-amber-50\/70,
    html.dark .bg-amber-50\/80,
    html.dark .bg-amber-50\/90 {
        background-color: rgba(245, 158, 11, 0.12) !important;
        border-color: rgba(245, 158, 11, 0.28) !important;
    }
    html.dark .bg-emerald-50\/80,
    html.dark .bg-emerald-50\/90 {
        background-color: rgba(16, 185, 129, 0.12) !important;
        border-color: rgba(16, 185, 129, 0.28) !important;
    }
    html.dark .bg-rose-50\/80,
    html.dark .bg-rose-50\/90 {
        background-color: rgba(244, 63, 94, 0.12) !important;
        border-color: rgba(244, 63, 94, 0.28) !important;
    }

    /* Globally Neutralize Light Gradient Stops in Dark Mode */
    html.dark .via-white {
        --tw-gradient-stops: var(--tw-gradient-from), #111827 var(--tw-gradient-via-position, 50%), var(--tw-gradient-to) !important;
    }
    html.dark .from-white {
        --tw-gradient-from: #111827 var(--tw-gradient-from-position) !important;
        --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
    }
    html.dark .to-white {
        --tw-gradient-to: #111827 var(--tw-gradient-to-position) !important;
    }

    /* Announcement Banners Dark Mode Overrides */
    html.dark #platform-announcement-container aside {
        background-color: #111827 !important;
        border-color: #1E293B !important;
        box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.6) !important;
    }
    html.dark #platform-announcement-container aside[class*="amber"],
    html.dark #platform-announcement-container aside.border-amber-200\/90 {
        background-image: linear-gradient(90deg, rgba(245, 158, 11, 0.16) 0%, #111827 50%, rgba(245, 158, 11, 0.05) 100%) !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }
    html.dark #platform-announcement-container aside[class*="emerald"],
    html.dark #platform-announcement-container aside.border-emerald-200\/90 {
        background-image: linear-gradient(90deg, rgba(16, 185, 129, 0.16) 0%, #111827 50%, rgba(16, 185, 129, 0.05) 100%) !important;
        border-color: rgba(16, 185, 129, 0.4) !important;
    }
    html.dark #platform-announcement-container aside[class*="rose"],
    html.dark #platform-announcement-container aside.border-rose-200\/90 {
        background-image: linear-gradient(90deg, rgba(244, 63, 94, 0.16) 0%, #111827 50%, rgba(244, 63, 94, 0.05) 100%) !important;
        border-color: rgba(244, 63, 94, 0.4) !important;
    }
    html.dark #platform-announcement-container aside[class*="indigo"],
    html.dark #platform-announcement-container aside.border-indigo-200\/90 {
        background-image: linear-gradient(90deg, rgba(99, 102, 241, 0.16) 0%, #111827 50%, rgba(99, 102, 241, 0.05) 100%) !important;
        border-color: rgba(99, 102, 241, 0.4) !important;
    }
    html.dark #platform-announcement-container h3 {
        color: #FFFFFF !important;
    }
    html.dark #platform-announcement-container p {
        color: #E2E8F0 !important;
    }
    html.dark #platform-announcement-container span.bg-white\/70,
    html.dark #platform-announcement-container span[class*="bg-white"] {
        background-color: rgba(30, 41, 59, 0.85) !important;
        border-color: #334155 !important;
        color: #CBD5E1 !important;
    }
    html.dark #platform-announcement-container span[class*="bg-white"] i {
        color: #94A3B8 !important;
    }
    html.dark #platform-announcement-container button {
        color: #94A3B8 !important;
    }
    html.dark #platform-announcement-container button:hover {
        background-color: rgba(255, 255, 255, 0.12) !important;
        color: #FFFFFF !important;
    }

    /* Investor Simulator & Preview Frames */
    .preview-phone-frame {
        transition: background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }
    html.dark .preview-phone-frame {
        background-color: #111827 !important;
        background: #111827 !important;
        border-color: #1E293B !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6) !important;
        color: #F8FAFC !important;
    }
    html.dark .preview-phone-frame h3,
    html.dark .preview-phone-frame #prev-title {
        color: #FFFFFF !important;
    }
    html.dark .preview-phone-frame #prev-content {
        background-color: #0F172A !important;
        border-color: #1E293B !important;
        color: #E2E8F0 !important;
    }
    html.dark .preview-phone-frame .border-slate-100 {
        border-color: #1E293B !important;
    }
    html.dark #prev-scope-badge {
        background-color: rgba(99, 102, 241, 0.18) !important;
        color: #A5B4FC !important;
        border-color: rgba(99, 102, 241, 0.35) !important;
    }
    html.dark #prev-category-badge {
        background-color: rgba(16, 185, 129, 0.18) !important;
        color: #6EE7B7 !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }

    /* Universal Dark Styles for Hero Banners */
    html.dark .hero-banner,
    html.dark .hero-profile-banner,
    html.dark .hero-trust-banner,
    html.dark .hero-capital-banner,
    html.dark .hero-cap-banner,
    html.dark [class*="hero-"] {
        background: radial-gradient(130% 100% at 0% 0%, #17213A 0%, #0F172A 55%, #111827 100%) !important;
        border-color: #1E293B !important;
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
        color: #F8FAFC !important;
    }
    html.dark .hero-banner h1,
    html.dark .hero-profile-banner h1,
    html.dark .hero-trust-banner h1,
    html.dark .hero-capital-banner h1,
    html.dark .hero-cap-banner h1,
    html.dark [class*="hero-"] h1 {
        color: #FFFFFF !important;
    }
    html.dark .hero-banner p,
    html.dark .hero-profile-banner p,
    html.dark .hero-trust-banner p,
    html.dark .hero-capital-banner p,
    html.dark .hero-cap-banner p,
    html.dark [class*="hero-"] p {
        color: #94A3B8 !important;
    }
    html.dark .hero-profile-banner img {
        border-color: #1E293B !important;
    }
    html.dark .hero-banner .bg-white,
    html.dark .hero-profile-banner .bg-white,
    html.dark .hero-trust-banner .bg-white,
    html.dark .hero-capital-banner .bg-white,
    html.dark .hero-cap-banner .bg-white {
        background-color: #111827 !important;
        border-color: #1E293B !important;
        color: #F8FAFC !important;
    }
    html.dark .hero-banner .bg-white\/80,
    html.dark .hero-profile-banner .bg-white\/80,
    html.dark .hero-trust-banner .bg-white\/80,
    html.dark .hero-capital-banner .bg-white\/80,
    html.dark .hero-cap-banner .bg-white\/80 {
        background-color: rgba(17, 24, 39, 0.85) !important;
        border-color: #1E293B !important;
        color: #F8FAFC !important;
    }

    /* Hover States for Dark Mode */
    html.dark .hover\:bg-slate-50:hover,
    html.dark .hover\:bg-slate-50\/80:hover {
        background-color: #1E293B !important;
    }
    html.dark .hover\:bg-slate-100:hover,
    html.dark .hover\:bg-slate-100\/70:hover {
        background-color: #273549 !important;
    }
    html.dark .hover\:text-slate-900:hover {
        color: #FFFFFF !important;
    }

    /* Dark Borders */
    html.dark .border-slate-100,
    html.dark .border-slate-200,
    html.dark .border-slate-200\/80,
    html.dark .border-slate-300 {
        border-color: #1E293B !important;
    }
    html.dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
    html.dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
        border-color: #1E293B !important;
    }

    /* ----------------------------------------------------
       5. ULTRA-CRISP HIGH-LEGIBILITY DARK TEXT RULES (FIXED)
    ---------------------------------------------------- */
    /* All Headings -> Pure Crystal White */
    html.dark h1, 
    html.dark h2, 
    html.dark h3, 
    html.dark h4, 
    html.dark h5, 
    html.dark h6,
    html.dark strong, 
    html.dark b,
    html.dark th {
        color: #FFFFFF !important;
    }

    /* All 900 / 950 deep dark classes -> Pure Crystal White */
    html.dark .text-slate-950,
    html.dark .text-slate-900,
    html.dark .text-gray-950,
    html.dark .text-gray-900,
    html.dark .text-zinc-950,
    html.dark .text-zinc-900,
    html.dark .text-neutral-950,
    html.dark .text-neutral-900,
    html.dark .text-black {
        color: #FFFFFF !important;
    }

    /* 800 classes -> Very Bright Off-White */
    html.dark .text-slate-800,
    html.dark .text-gray-800,
    html.dark .text-zinc-800,
    html.dark .text-neutral-800 {
        color: #F8FAFC !important;
    }

    /* 700 classes -> High-Contrast Light Slate */
    html.dark .text-slate-700,
    html.dark .text-gray-700,
    html.dark .text-zinc-700,
    html.dark .text-neutral-700 {
        color: #E2E8F0 !important;
    }

    /* 600 / 500 / 400 classes -> Crisp Silver Slate */
    html.dark .text-slate-600,
    html.dark .text-gray-600 {
        color: #CBD5E1 !important;
    }
    html.dark .text-slate-500,
    html.dark .text-gray-500 {
        color: #94A3B8 !important;
    }
    html.dark .text-slate-400,
    html.dark .text-gray-400 {
        color: #94A3B8 !important;
    }
    html.dark .text-slate-300,
    html.dark .text-gray-300 {
        color: #CBD5E1 !important;
    }

    /* Colored 900 & 950 texts (previously dark near-black on cards) -> Radiant Luminous Colors! */
    html.dark .text-indigo-950,
    html.dark .text-indigo-900,
    html.dark .text-indigo-800 {
        color: #C7D2FE !important; /* Radiant soft lavender */
    }
    html.dark .text-emerald-950,
    html.dark .text-emerald-900,
    html.dark .text-emerald-800 {
        color: #A7F3D0 !important; /* Radiant soft mint */
    }
    html.dark .text-amber-950,
    html.dark .text-amber-900,
    html.dark .text-amber-800 {
        color: #FDE68A !important; /* Radiant warm amber */
    }
    html.dark .text-rose-950,
    html.dark .text-rose-900,
    html.dark .text-rose-800 {
        color: #FECDD3 !important; /* Radiant soft coral */
    }
    html.dark .text-purple-950,
    html.dark .text-purple-900,
    html.dark .text-purple-800 {
        color: #E9D5FF !important; /* Radiant lilac */
    }
    html.dark .text-blue-950,
    html.dark .text-blue-900,
    html.dark .text-blue-800 {
        color: #BAE6FD !important; /* Radiant sky blue */
    }

    /* Radiant Vibrant Metrics & Stake Accents */
    html.dark .text-indigo-600 {
        color: #818CF8 !important;
    }
    html.dark .text-emerald-600 {
        color: #34D399 !important;
    }
    html.dark .text-amber-600 {
        color: #FBBF24 !important;
    }
    html.dark .text-rose-600 {
        color: #F87171 !important;
    }

    /* Paragraphs and Body Text */
    html.dark p {
        color: #CBD5E1;
    }
    html.dark label {
        color: #E2E8F0 !important;
    }
    html.dark .form-hint {
        color: #94A3B8 !important;
    }

    /* Secondary / White Buttons & Action Pills */
    html.dark button.bg-white,
    html.dark a.bg-white {
        background-color: #131C2E !important;
        color: #F8FAFC !important;
        border-color: #334155 !important;
    }
    html.dark button.bg-white:hover,
    html.dark a.bg-white:hover {
        background-color: #1E293B !important;
        color: #FFFFFF !important;
        border-color: #6366F1 !important;
    }

    /* ----------------------------------------------------
       6. BADGES, STATUS PILLS & HERO BANNERS
    ---------------------------------------------------- */
    html.dark .bg-emerald-50,
    html.dark .bg-emerald-100 {
        background-color: rgba(16, 185, 129, 0.16) !important;
        color: #34D399 !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }
    html.dark .bg-amber-50,
    html.dark .bg-amber-100 {
        background-color: rgba(245, 158, 11, 0.16) !important;
        color: #FBBF24 !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    html.dark .bg-rose-50,
    html.dark .bg-rose-100 {
        background-color: rgba(244, 63, 94, 0.16) !important;
        color: #FB7185 !important;
        border-color: rgba(244, 63, 94, 0.3) !important;
    }
    html.dark .bg-indigo-50,
    html.dark .bg-indigo-100 {
        background-color: rgba(99, 102, 241, 0.16) !important;
        color: #818CF8 !important;
        border-color: rgba(99, 102, 241, 0.3) !important;
    }
    html.dark .bg-purple-50,
    html.dark .bg-purple-100 {
        background-color: rgba(168, 85, 247, 0.16) !important;
        color: #C084FC !important;
        border-color: rgba(168, 85, 247, 0.3) !important;
    }
    html.dark .bg-blue-50,
    html.dark .bg-blue-100 {
        background-color: rgba(59, 130, 246, 0.16) !important;
        color: #60A5FA !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }

    /* Dark Hero Banners */
    html.dark .hero-cap-banner,
    html.dark .hero-capital-banner,
    html.dark .hero-banner {
        background: radial-gradient(130% 100% at 0% 0%, #1E1B4B 0%, #0F172A 50%, #171131 100%) !important;
        border-color: #312E81 !important;
    }
    html.dark .hero-cap-banner h1,
    html.dark .hero-capital-banner h1,
    html.dark .hero-banner h1 {
        color: #FFFFFF !important;
    }
    html.dark .hero-cap-banner p,
    html.dark .hero-capital-banner p,
    html.dark .hero-banner p {
        color: #CBD5E1 !important;
    }

    /* ----------------------------------------------------
       7. FORMS, SELECTS, INPUTS & MODALS
    ---------------------------------------------------- */
    html.dark .form-input-clean,
    html.dark input[type="text"],
    html.dark input[type="email"],
    html.dark input[type="password"],
    html.dark input[type="number"],
    html.dark input[type="url"],
    html.dark input[type="search"],
    html.dark input[type="date"],
    html.dark select,
    html.dark textarea {
        background-color: #0B0F19 !important;
        border-color: #334155 !important;
        color: #FFFFFF !important;
    }
    html.dark input::placeholder,
    html.dark textarea::placeholder {
        color: #94A3B8 !important;
    }
    html.dark select option {
        background-color: #111827 !important;
        color: #FFFFFF !important;
    }
    html.dark .form-input-clean:hover,
    html.dark input:hover,
    html.dark select:hover,
    html.dark textarea:hover {
        background-color: #0F172A !important;
        border-color: #475569 !important;
    }
    html.dark .form-input-clean:focus,
    html.dark input:focus,
    html.dark select:focus,
    html.dark textarea:focus {
        background-color: #0B0F19 !important;
        border-color: #6366F1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.28) !important;
    }

    /* Dark Table Rows & Cells */
    html.dark table thead th {
        background-color: #0F172A !important;
        color: #94A3B8 !important;
        border-color: #1E293B !important;
    }
    html.dark table tbody td {
        border-color: #1E293B !important;
        color: #E2E8F0 !important;
    }
    html.dark table tbody tr {
        border-color: #1E293B !important;
    }
    html.dark table tbody tr:hover {
        background-color: rgba(30, 41, 59, 0.45) !important;
    }
    html.dark table tbody tr td .text-slate-900 {
        color: #FFFFFF !important;
    }
    html.dark table tbody tr td .text-slate-700 {
        color: #E2E8F0 !important;
    }
    html.dark table tbody tr td .text-slate-600 {
        color: #CBD5E1 !important;
    }
    html.dark table tbody tr td .text-slate-400 {
        color: #94A3B8 !important;
    }

    /* Modals & Dropdowns */
    html.dark #notif-menu,
    html.dark .modal-content,
    html.dark [id*="modal"] .bg-white,
    html.dark [id*="Modal"] .bg-white {
        background-color: #111827 !important;
        border-color: #1E293B !important;
        color: #FFFFFF !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.75) !important;
    }

    /* Dark Theme Switcher Button Glow */
    #founder-theme-toggle-btn {
        position: relative;
        overflow: hidden;
    }
    html.dark #founder-theme-toggle-btn {
        background-color: #1E293B !important;
        border-color: #334155 !important;
        color: #FFFFFF !important;
    }
    html.dark #founder-theme-toggle-btn:hover {
        background-color: #334155 !important;
        box-shadow: 0 0 14px rgba(251, 191, 36, 0.35);
    }
    html:not(.dark) #founder-theme-toggle-btn:hover {
        box-shadow: 0 0 14px rgba(99, 102, 241, 0.25);
    }

    /* Dark Scrollbar */
    html.dark ::-webkit-scrollbar-thumb {
        background: #334155 !important;
    }
    html.dark ::-webkit-scrollbar-thumb:hover {
        background: #475569 !important;
    }

    /* Dilution Range Slider */
    html.dark input[type="range"] {
        accent-color: #6366F1;
    }
</style>
