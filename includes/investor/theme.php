<?php
/**
 * Investor Theme Controller (Dark & Light Mode Support)
 * Seamless, FOUC-free switching with localStorage & cookie persistence
 * High-legibility contrast, boosted typography, full-screen fluid layout & smooth micro-animations
 * Tailored for High-Trust Institutional Investor Network Experience
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

            if (window.tailwind) {
                window.tailwind.config = window.tailwind.config || {};
                window.tailwind.config.darkMode = 'class';
            }
        } catch (e) { }
    })();
</script>

<!-- Global Investor Dark & Light Theme Styles, Typography Boost & Micro-Animations -->
<style id="investor-theme-stylesheet">
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

    /* ----------------------------------------------------
       1. UNIVERSAL TYPOGRAPHY & READABILITY SCALE (BIG & CRISP)
    ---------------------------------------------------- */
    html {
        font-size: 16.5px !important;
        scroll-behavior: smooth;
    }

    html,
    body,
    button,
    input,
    select,
    textarea,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    a,
    label,
    table,
    th,
    td {
        font-family: "Vay Portal", Sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    body {
        font-size: 1rem;
        line-height: 1.65;
    }

    /* Boost small classes across all investor pages so text is effortlessly readable */
    .text-\[9px\],
    .text-\[9\.5px\],
    .text-\[10px\],
    .text-\[10\.5px\],
    .text-\[11px\] {
        font-size: 0.84rem !important;
        /* ~13.8px */
        line-height: 1.5 !important;
        font-weight: 600 !important;
    }

    .text-xs {
        font-size: 0.925rem !important;
        /* ~15px */
        line-height: 1.55 !important;
    }

    .text-sm {
        font-size: 1.05rem !important;
        /* ~17px */
        line-height: 1.6 !important;
    }

    .text-base {
        font-size: 1.15rem !important;
        /* ~19px */
        line-height: 1.65 !important;
    }

    .text-lg {
        font-size: 1.3rem !important;
        line-height: 1.5 !important;
    }

    .text-xl {
        font-size: 1.5rem !important;
        line-height: 1.4 !important;
    }

    .text-2xl {
        font-size: 1.85rem !important;
        line-height: 1.35 !important;
    }

    .text-3xl {
        font-size: 2.35rem !important;
        line-height: 1.25 !important;
    }

    .text-4xl {
        font-size: 2.85rem !important;
        line-height: 1.2 !important;
    }

    /* ----------------------------------------------------
       2. FULL-SCREEN EXPANSIVE DASHBOARD CONTAINER FIT
    ---------------------------------------------------- */
    main,
    #workspace-main,
    #discover-main,
    #portfolio-main,
    #watchlist-main,
    #deal-room-main,
    #verify-main,
    #profile-main,
    #investor-view-main {
        max-width: 1480px !important;
        width: 100% !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    /* Fixed / Sticky Header Across All Investor Pages */
    header,
    .investor-navbar {
        position: sticky !important;
        top: 0 !important;
        z-index: 50 !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
    }

    /* ----------------------------------------------------
       3. USER MICRO-ANIMATIONS & INTERACTIVE MOTION SUITE
    ---------------------------------------------------- */
    /* Staggered entrance animation for dashboard sections */
    @keyframes investorEntrance {
        0% {
            opacity: 0;
            transform: translateY(14px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    main>section,
    main>div,
    .dashboard-card,
    .metric-card,
    .network-row {
        animation: investorEntrance 0.38s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    main>*:nth-child(1) {
        animation-delay: 0.03s;
    }

    main>*:nth-child(2) {
        animation-delay: 0.06s;
    }

    main>*:nth-child(3) {
        animation-delay: 0.09s;
    }

    main>*:nth-child(4) {
        animation-delay: 0.12s;
    }

    main>*:nth-child(5) {
        animation-delay: 0.15s;
    }

    /* Interactive Hover Elevation for Cards, Rows & Metric Tiles */
    .card-clean,
    .dashboard-card,
    .metric-card,
    .bg-white.border {
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
            box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1),
            border-color 0.22s ease !important;
    }

    .card-clean:hover,
    .dashboard-card:hover,
    .metric-card:hover,
    .bg-white.border:hover {
        transform: translateY(-2px);
    }

    html:not(.dark) .card-clean:hover,
    html:not(.dark) .dashboard-card:hover,
    html:not(.dark) .metric-card:hover,
    html:not(.dark) .bg-white.border:hover {
        border-color: #CBD5E1 !important;
        box-shadow: 0 14px 30px -4px rgba(18, 59, 122, 0.08), 0 4px 12px -2px rgba(0, 0, 0, 0.03) !important;
    }

    /* Silky-smooth color transition when toggling themes */
    html.dark,
    html.dark body,
    body,
    header,
    aside,
    #main-sidebar,
    .network-row,
    .card-clean,
    .dashboard-card,
    input,
    select,
    textarea {
        transition: background-color 0.24s cubic-bezier(0.4, 0, 0.2, 1),
            border-color 0.24s cubic-bezier(0.4, 0, 0.2, 1),
            color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Button click tactile spring */
    button:active,
    a.button:active,
    [type="submit"]:active {
        transform: scale(0.975);
    }

    /* Pulsing Beacon Animation */
    @keyframes pulseBeacon {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.55;
            transform: scale(1.15);
        }
    }

    .pulse-beacon {
        animation: pulseBeacon 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* ----------------------------------------------------
       4. LIGHT THEME (DEFAULT) TOKENS & CONTRAST
    ---------------------------------------------------- */
    :root {
        --inv-primary: #123B7A;
        --inv-navy: #0B1F3A;
        --inv-secondary: #315F9F;
        --inv-light-blue: #EAF2FF;
        --inv-bg: #F4F2EE;
        --inv-card: #FFFFFF;
        --inv-text: #111827;
        --inv-muted: #374151;
        --inv-border: #E4E8EF;
    }

    html:not(.dark) body {
        background-color: #F4F2EE !important;
        color: #111827 !important;
    }

    /* Boost secondary text contrast */
    .text-\[\#667085\],
    .text-slate-500 {
        color: #374151 !important;
    }

    /* Subtle row hover */
    .network-row {
        background: #FFFFFF;
        border-bottom: 1px solid #E4E8EF;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .network-row:hover {
        background: #F8FAFD;
    }

    /* ----------------------------------------------------
       5. DARK THEME TOKENS, SURFACES & INSTITUTIONAL GLOW
    ---------------------------------------------------- */
    html.dark {
        --inv-primary: #3B82F6;
        --inv-navy: #F8FAFC;
        --inv-secondary: #60A5FA;
        --inv-light-blue: rgba(59, 130, 246, 0.18);
        --inv-bg: #0B0F19;
        --inv-card: #111827;
        --inv-text: #F3F4F6;
        --inv-muted: #9CA3AF;
        --inv-border: #1E293B;
        color-scheme: dark;
    }

    html.dark,
    html.dark body {
        background-color: #0B0F19 !important;
        color: #F3F4F6 !important;
    }

    /* Dark Page Background Overrides */
    html.dark .bg-\[\#FAFBFD\],
    html.dark .bg-\[\#FAFAFB\],
    html.dark .bg-\[\#F8FAFC\],
    html.dark .bg-slate-50 {
        background-color: #0B0F19 !important;
    }

    /* ----------------------------------------------------
       LIGHT MODE (WHITE) SIDEBAR & NAVBAR (DARK MODE OFF ONLY)
    ---------------------------------------------------- */
    html:not(.dark) .investor-navbar,
    html:not(.dark) header {
        background-color: #FFFFFF !important;
        background: #FFFFFF !important;
        border-color: #E2E8F0 !important;
    }

    html:not(.dark) #main-sidebar,
    html:not(.dark) aside#main-sidebar {
        background-color: #FFFFFF !important;
        background: #FFFFFF !important;
        border-color: #E2E8F0 !important;
    }

    html:not(.dark) #main-sidebar .bg-\[\#FAFBFD\],
    html:not(.dark) #main-sidebar .bg-slate-50 {
        background-color: #F8FAFC !important;
    }

    /* Dark Header */
    html.dark header {
        background-color: #0F172A !important;
        border-color: #1E293B !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.55) !important;
    }

    /* Dark Sidebar */
    html.dark #main-sidebar {
        background-color: #0F172A !important;
        border-color: #1E293B !important;
    }

    html.dark #main-sidebar .bg-\[\#FAFBFD\],
    html.dark #main-sidebar .border-t,
    html.dark #main-sidebar .border-b,
    html.dark #main-sidebar .border-r {
        border-color: #1E293B !important;
    }

    html.dark #main-sidebar .text-\[\#0B1F3A\] {
        color: #F8FAFC !important;
    }

    html.dark #main-sidebar .text-\[\#4B5563\],
    html.dark #main-sidebar .text-\[\#667085\] {
        color: #94A3B8 !important;
    }

    html.dark #main-sidebar a:hover {
        background-color: #1E293B !important;
        color: #FFFFFF !important;
    }

    html.dark #main-sidebar a.bg-\[\#EAF2FF\] {
        background-color: rgba(30, 58, 138, 0.45) !important;
        color: #93C5FD !important;
    }

    html.dark #main-sidebar a.bg-\[\#EAF2FF\] i {
        color: #93C5FD !important;
    }

    /* Dark Cards & Surfaces */
    html.dark .bg-white,
    html.dark .network-row,
    html.dark .card-clean,
    html.dark .dashboard-card,
    html.dark .metric-card {
        background-color: #111827 !important;
        border-color: #1E293B !important;
    }

    html.dark .network-row:hover {
        background-color: #1E293B !important;
    }

    html.dark .card-clean:hover,
    html.dark .dashboard-card:hover,
    html.dark .metric-card:hover,
    html.dark .bg-white.border:hover {
        border-color: rgba(59, 130, 246, 0.45) !important;
        box-shadow: 0 14px 34px -4px rgba(0, 0, 0, 0.65), 0 0 18px -2px rgba(59, 130, 246, 0.22) !important;
    }

    /* Borders & Dividers */
    html.dark .border-\[\#E4E8EF\],
    html.dark .border-slate-200,
    html.dark .border-slate-100,
    html.dark .divide-\[\#E4E8EF\]> :not([hidden])~ :not([hidden]),
    html.dark .divide-slate-200> :not([hidden])~ :not([hidden]) {
        border-color: #1E293B !important;
    }

    /* Dark Text Typography */
    html.dark .text-\[\#0B1F3A\],
    html.dark .text-\[\#111827\],
    html.dark .text-slate-900,
    html.dark .text-slate-800 {
        color: #F8FAFC !important;
    }

    html.dark .text-\[\#4B5563\],
    html.dark .text-\[\#667085\],
    html.dark .text-slate-500,
    html.dark .text-slate-600 {
        color: #94A3B8 !important;
    }

    /* Soft Blue Badges & Accent Fills */
    html.dark .bg-\[\#EAF2FF\],
    html.dark .bg-\[\#EAF2FF\]\/40,
    html.dark .bg-\[\#EAF2FF\]\/50,
    html.dark .bg-\[\#EAF2FF\]\/60,
    html.dark .bg-\[\#EAF2FF\]\/70,
    html.dark .bg-\[\#EAF2FF\]\/80,
    html.dark .bg-\[\#EAF2FF\]\/90,
    html.dark .bg-blue-50 {
        background-color: rgba(30, 58, 138, 0.38) !important;
        color: #93C5FD !important;
    }

    html.dark .text-\[\#123B7A\] {
        color: #60A5FA !important;
    }

    /* Inputs, Selects & Forms in Dark Mode */
    html.dark input,
    html.dark select,
    html.dark textarea {
        background-color: #1E293B !important;
        border-color: #334155 !important;
        color: #F8FAFC !important;
    }

    html.dark input:focus,
    html.dark select:focus,
    html.dark textarea:focus {
        border-color: #3B82F6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
    }

    html.dark input::placeholder,
    html.dark textarea::placeholder {
        color: #64748B !important;
    }

    /* Dropdowns & Popups */
    html.dark #investor-notif-menu,
    html.dark #investor-sections-menu {
        background-color: #0F172A !important;
        border-color: #1E293B !important;
        box-shadow: 0 14px 36px -4px rgba(0, 0, 0, 0.75) !important;
    }

    /* Theme Toggle Button Micro-Animation */
    #investor-theme-toggle-btn {
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
            background-color 0.2s ease,
            border-color 0.2s ease;
    }

    #investor-theme-toggle-btn:hover {
        transform: scale(1.08) rotate(8deg);
    }

    #investor-theme-toggle-btn:active {
        transform: scale(0.92);
    }

    /* ====================================================
       UNIVERSAL DARK MODE HOVER OVERRIDES (GLITCH-FREE)
       ==================================================== */
    /* Prevent light background flash on hover in dark mode */
    html.dark .hover\:bg-\[\#FAFBFD\]:hover,
    html.dark .hover\:bg-\[\#FAFAFB\]:hover,
    html.dark .hover\:bg-\[\#F8FAFC\]:hover,
    html.dark .hover\:bg-slate-50:hover,
    html.dark .hover\:bg-gray-50:hover {
        background-color: #1E293B !important;
    }

    html.dark .hover\:bg-slate-100:hover,
    html.dark .hover\:bg-gray-100:hover {
        background-color: #334155 !important;
    }

    html.dark .hover\:bg-slate-200:hover,
    html.dark .hover\:bg-gray-200:hover {
        background-color: #475569 !important;
    }

    html.dark .hover\:bg-white:hover {
        background-color: #1E293B !important;
        border-color: #38BDF8 !important;
    }

    html.dark .hover\:bg-\[\#EAF2FF\]:hover,
    html.dark .hover\:bg-blue-50:hover {
        background-color: rgba(30, 58, 138, 0.45) !important;
        color: #93C5FD !important;
    }

    /* Prevent dark black text on hover in dark mode */
    html.dark .hover\:text-\[\#0B1F3A\]:hover,
    html.dark .hover\:text-\[\#111827\]:hover,
    html.dark .hover\:text-slate-900:hover,
    html.dark .hover\:text-slate-800:hover,
    html.dark .hover\:text-gray-900:hover,
    html.dark .hover\:text-gray-800:hover,
    html.dark .hover\:text-black:hover {
        color: #FFFFFF !important;
    }

    html.dark .hover\:text-\[\#123B7A\]:hover {
        color: #60A5FA !important;
    }

    html.dark .hover\:text-\[\#0A66C2\]:hover {
        color: #38BDF8 !important;
    }

    /* Interactive borders on hover in dark mode */
    html.dark .hover\:border-\[\#E4E8EF\]:hover,
    html.dark .hover\:border-slate-200:hover,
    html.dark .hover\:border-slate-300:hover {
        border-color: #475569 !important;
    }

    html.dark .hover\:border-\[\#123B7A\]:hover,
    html.dark .hover\:border-\[\#0A66C2\]:hover {
        border-color: #38BDF8 !important;
    }

    /* Smooth transition for all interactive hover elements */
    button,
    a,
    select,
    input,
    .network-row,
    .card-clean,
    .linkedin-card,
    article {
        transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
    }
</style>