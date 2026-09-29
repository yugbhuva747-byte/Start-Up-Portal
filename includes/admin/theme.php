<?php
/**
 * Global Admin Theme Controller (Dark & Light Mode Support)
 * Standardized for "Vay Portal", Sans-serif typography
 * High-legibility font scaling with instant theme switching & persistence
 */
?>
<!-- Theme Initialization Script (Instant paint, prevents flash of unstyled content) -->
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

<!-- Global Admin "Vay Portal" Typography & Dark Mode Suite -->
<style id="admin-vay-portal-theme">
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

    @font-face {
        font-family: "Vay Portal";
        src: local('Vay Portal - Regular'), local('Vay Portal'), local('Plus Jakarta Sans'), local('Inter'), sans-serif;
        font-display: swap;
    }

    /* ----------------------------------------------------
       1. UNIVERSAL "VAY PORTAL" TYPOGRAPHY & READABILITY SCALE
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
    td,
    li,
    dt,
    dd,
    div {
        font-family: "Vay Portal", Sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* Base Body Readability & Typography Boost */
    body {
        font-size: 1.05rem !important;
        line-height: 1.65 !important;
    }

    /* Boost ultra-small Tailwind text classes across all admin pages for easy reading */
    .text-\[7px\],
    .text-\[7\.5px\],
    .text-\[8px\],
    .text-\[8\.5px\],
    .text-\[9px\],
    .text-\[9\.5px\],
    .text-\[10px\],
    .text-\[10\.5px\],
    .text-\[11px\],
    .text-\[11\.5px\] {
        font-size: 0.875rem !important; /* ~14px */
        line-height: 1.5 !important;
        font-weight: 600 !important;
    }

    .text-xs {
        font-size: 0.95rem !important; /* ~15.2px */
        line-height: 1.55 !important;
    }

    .text-sm {
        font-size: 1.075rem !important; /* ~17.2px */
        line-height: 1.6 !important;
    }

    .text-base {
        font-size: 1.175rem !important; /* ~18.8px */
        line-height: 1.65 !important;
    }

    .text-lg {
        font-size: 1.35rem !important; /* ~21.6px */
        line-height: 1.5 !important;
        font-weight: 700 !important;
    }

    .text-xl {
        font-size: 1.6rem !important; /* ~25.6px */
        line-height: 1.4 !important;
        font-weight: 800 !important;
    }

    .text-2xl {
        font-size: 1.95rem !important; /* ~31.2px */
        line-height: 1.35 !important;
        font-weight: 800 !important;
    }

    .text-3xl {
        font-size: 2.45rem !important; /* ~39.2px */
        line-height: 1.25 !important;
        font-weight: 900 !important;
    }

    .text-4xl {
        font-size: 2.95rem !important;
        line-height: 1.2 !important;
        font-weight: 900 !important;
    }

    /* Table headers, cells, and form inputs readability */
    table th {
        font-size: 0.925rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.025em;
    }

    table td {
        font-size: 0.975rem !important;
        line-height: 1.55 !important;
    }

    input, select, textarea {
        font-size: 1rem !important;
    }

    /* Badges & Pills */
    .badge, [class*="rounded-full"] {
        letter-spacing: 0.02em;
        font-weight: 600;
    }

    /* ----------------------------------------------------
       2. SMOOTH COLOR TRANSITIONS WHEN SWITCHING THEMES
    ---------------------------------------------------- */
    html.dark,
    html.dark body,
    body,
    header,
    aside,
    #main-sidebar,
    .card-clean,
    table,
    th,
    td,
    input,
    select,
    textarea {
        transition: background-color 0.24s cubic-bezier(0.4, 0, 0.2, 1),
                    border-color 0.24s cubic-bezier(0.4, 0, 0.2, 1),
                    color 0.2s cubic-bezier(0.4, 0, 0.2, 1),
                    box-shadow 0.24s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ----------------------------------------------------
       3. DARK THEME TOKENS & SURFACE OVERRIDES
    ---------------------------------------------------- */
    html.dark {
        color-scheme: dark;
    }

    html.dark,
    html.dark body {
        background-color: #0B0F19 !important;
        color: #F8FAFC !important;
    }

    /* Page background overrides */
    html.dark .bg-\[\#F4F2EE\],
    html.dark .bg-\[\#FAFBFD\],
    html.dark .bg-\[\#FAFAFB\],
    html.dark .bg-\[\#F8FAFC\],
    html.dark .bg-slate-50,
    html.dark .bg-slate-100 {
        background-color: #0B0F19 !important;
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

    html.dark #main-sidebar .border-t,
    html.dark #main-sidebar .border-b,
    html.dark #main-sidebar .border-r {
        border-color: #1E293B !important;
    }

    html.dark #main-sidebar .bg-slate-50\/50,
    html.dark #main-sidebar .bg-slate-50 {
        background-color: #0F172A !important;
    }

    html.dark #main-sidebar .text-slate-900,
    html.dark #main-sidebar .text-slate-800 {
        color: #F8FAFC !important;
    }

    html.dark #main-sidebar .text-slate-600,
    html.dark #main-sidebar .text-slate-400 {
        color: #94A3B8 !important;
    }

    html.dark #main-sidebar a:hover {
        background-color: #1E293B !important;
        color: #FFFFFF !important;
    }

    /* ----------------------------------------------------
       FIXED PINNED ADMIN NAVBAR
    ---------------------------------------------------- */
    #admin-navbar,
    header.admin-navbar,
    header.sticky.top-0,
    body > div > header {
        position: sticky !important;
        top: 0 !important;
        z-index: 40 !important;
        width: 100% !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
    }

    /* Active link in dark sidebar */
    html.dark #main-sidebar a.bg-blue-50 {
        background-color: rgba(37, 99, 235, 0.25) !important;
        color: #93C5FD !important;
    }

    html.dark #main-sidebar a.bg-blue-50 i {
        color: #60A5FA !important;
    }

    /* Dark Cards & Surfaces */
    html.dark .bg-white,
    html.dark .card-clean,
    html.dark .dashboard-card,
    html.dark .stat-card {
        background-color: #111827 !important;
        border-color: #1E293B !important;
    }

    html.dark .card-clean {
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
    }

    /* Dark Borders & Dividers */
    html.dark .border-slate-200,
    html.dark .border-slate-100,
    html.dark .border-gray-200,
    html.dark .divide-slate-200> :not([hidden])~ :not([hidden]),
    html.dark .divide-slate-100> :not([hidden])~ :not([hidden]) {
        border-color: #1E293B !important;
    }

    /* Dark Text Typography */
    html.dark .text-slate-900,
    html.dark .text-slate-800,
    html.dark .text-slate-700,
    html.dark .text-gray-900,
    html.dark .text-gray-800 {
        color: #F8FAFC !important;
    }

    html.dark .text-slate-600,
    html.dark .text-slate-500,
    html.dark .text-slate-400,
    html.dark .text-gray-600,
    html.dark .text-gray-500 {
        color: #94A3B8 !important;
    }

    /* Dark Tables */
    html.dark table {
        border-color: #1E293B !important;
    }

    html.dark table thead,
    html.dark table th {
        background-color: #0F172A !important;
        color: #94A3B8 !important;
        border-color: #1E293B !important;
    }

    html.dark table tbody tr {
        border-color: #1E293B !important;
    }

    html.dark table tbody tr:hover {
        background-color: #1E293B !important;
    }

    html.dark table td {
        border-color: #1E293B !important;
        color: #E2E8F0 !important;
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
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
    }

    html.dark select option,
    html.dark select optgroup {
        background-color: #1E293B !important;
        color: #F8FAFC !important;
    }

    /* Custom Sleek Scrollbar in Dark Mode */
    html.dark ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    html.dark ::-webkit-scrollbar-track {
        background: #0B0F19;
    }

    html.dark ::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 4px;
    }

    html.dark ::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }

    /* Dark Mode Status Badges & Pills */
    html.dark .bg-blue-50 {
        background-color: rgba(37, 99, 235, 0.2) !important;
        color: #93C5FD !important;
        border-color: rgba(59, 130, 246, 0.35) !important;
    }

    html.dark .bg-emerald-50 {
        background-color: rgba(16, 185, 129, 0.18) !important;
        color: #6EE7B7 !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }

    html.dark .bg-amber-50 {
        background-color: rgba(245, 158, 11, 0.18) !important;
        color: #FCD34D !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }

    html.dark .bg-rose-50 {
        background-color: rgba(244, 63, 94, 0.18) !important;
        color: #FDA4AF !important;
        border-color: rgba(244, 63, 94, 0.35) !important;
    }

    html.dark .bg-blue-50 {
        background-color: rgba(59, 130, 246, 0.18) !important;
        color: #93C5FD !important;
        border-color: rgba(59, 130, 246, 0.35) !important;
    }

    /* Theme Toggle Button Micro-Animation */
    #admin-theme-toggle-btn {
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
                    background-color 0.2s ease,
                    border-color 0.2s ease;
    }

    #admin-theme-toggle-btn:hover {
        transform: scale(1.08) rotate(8deg);
    }

    #admin-theme-toggle-btn:active {
        transform: scale(0.92);
    }

    /* Dark Mode Hover Overrides to prevent light flash */
    html.dark .hover\:bg-slate-50:hover,
    html.dark .hover\:bg-slate-100:hover,
    html.dark .hover\:bg-gray-50:hover,
    html.dark .hover\:bg-gray-100:hover {
        background-color: #1E293B !important;
    }

    html.dark .hover\:text-slate-900:hover,
    html.dark .hover\:text-slate-800:hover,
    html.dark .hover\:text-gray-900:hover {
        color: #FFFFFF !important;
    }
</style>

<!-- Theme Controller JavaScript Engine -->
<script>
    function toggleAdminTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        const themeName = isDark ? 'dark' : 'light';
        try {
            localStorage.setItem('startup_portal_theme', themeName);
            document.cookie = "startup_portal_theme=" + themeName + ";path=/;max-age=31536000";
        } catch (e) {}
        syncAdminThemeIcons(isDark);
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: themeName } }));
    }

    function syncAdminThemeIcons(isDark) {
        const sun = document.getElementById('admin-theme-sun-wrap');
        const moon = document.getElementById('admin-theme-moon-wrap');
        const btn = document.getElementById('admin-theme-toggle-btn');
        if (sun && moon) {
            if (isDark) {
                sun.classList.remove('hidden');
                moon.classList.add('hidden');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Light Theme');
                    btn.setAttribute('aria-label', 'Switch to Light Theme');
                }
            } else {
                sun.classList.add('hidden');
                moon.classList.remove('hidden');
                if (btn) {
                    btn.setAttribute('title', 'Switch to Dark Theme');
                    btn.setAttribute('aria-label', 'Switch to Dark Theme');
                }
            }
        }
    }

    // Sync icons upon initial document readiness
    document.addEventListener('DOMContentLoaded', function () {
        const isDark = document.documentElement.classList.contains('dark');
        syncAdminThemeIcons(isDark);
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });

    // Listen for theme change events dispatched from anywhere
    window.addEventListener('themeChanged', function (e) {
        const isDark = e.detail && e.detail.theme === 'dark';
        syncAdminThemeIcons(isDark);
    });
</script>
