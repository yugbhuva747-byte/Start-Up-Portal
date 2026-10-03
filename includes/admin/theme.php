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
       1. UNIVERSAL "VAY PORTAL" TYPOGRAPHY & READABILITY SCALE (MEDIUM, CLEAN DESKTOP SCALE)
    ---------------------------------------------------- */
    html {
        font-size: 14px !important;
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
        font-family: "Vay Portal", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }

    /* Base Body Readability & Typography */
    body {
        font-size: 0.875rem;
        line-height: 1.5;
        color: #1e293b;
    }

    /* Standard responsive SaaS container for laptop/desktop screen perfection */
    main:not([id*="messages"]) {
        max-width: 84rem !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: 100% !important;
    }

    /* Small badge & micro label styling - crisp & legible */
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
        font-size: 0.6875rem !important; /* ~9.6px - 10px */
        line-height: 1.35 !important;
        font-weight: 600 !important;
    }

    /* Clean Proportional Medium Scale for Admin Panels */
    .text-xs {
        font-size: 0.75rem !important; /* ~10.5px - 11px */
        line-height: 1.35 !important;
    }

    .text-sm {
        font-size: 0.8125rem !important; /* ~11.5px - 12px */
        line-height: 1.4 !important;
    }

    .text-base {
        font-size: 0.875rem !important; /* ~12.25px - 13px */
        line-height: 1.45 !important;
    }

    .text-lg {
        font-size: 1.05rem !important; /* ~14.7px */
        line-height: 1.35 !important;
        font-weight: 700 !important;
    }

    .text-xl {
        font-size: 1.1875rem !important; /* ~16.6px */
        line-height: 1.3 !important;
        font-weight: 700 !important;
    }

    .text-2xl {
        font-size: 1.375rem !important; /* ~19.2px */
        line-height: 1.25 !important;
        font-weight: 800 !important;
    }

    .text-3xl {
        font-size: 1.625rem !important; /* ~22.7px */
        line-height: 1.2 !important;
        font-weight: 800 !important;
    }

    .text-4xl {
        font-size: 1.875rem !important; /* ~26.2px */
        line-height: 1.15 !important;
        font-weight: 800 !important;
    }

    /* Table headers, cells, and form inputs readability - Clean Enterprise Polish */
    table th {
        font-size: 0.6875rem !important;
        font-weight: 700 !important;
        letter-spacing: 0.05em !important;
        text-transform: uppercase !important;
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
    }

    table td {
        font-size: 0.8125rem !important;
        line-height: 1.45 !important;
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
    }

    input, select, textarea {
        font-size: 0.8125rem !important;
        line-height: 1.4 !important;
    }

    /* Badges, Pills & Action Headers - Strictly single-line everywhere */
    .badge, 
    [class*="rounded-full"], 
    .whitespace-nowrap, 
    [class*="whitespace-nowrap"] {
        white-space: nowrap !important;
        flex-shrink: 0 !important;
        word-break: keep-all !important;
    }

    .card-clean .card-header-actions,
    .card-clean .card-header-actions * {
        white-space: nowrap !important;
        flex-shrink: 0 !important;
    }

    /* ----------------------------------------------------
       BUTTON UTILITIES - CONSISTENT, POLISHED & PROPORTIONAL
    ---------------------------------------------------- */
    .admin-btn-primary,
    a.admin-btn-primary,
    button.admin-btn-primary {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.45rem !important;
        padding: 0.45rem 0.85rem !important;
        border-radius: 0.625rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        color: #ffffff !important;
        background-color: #2563eb !important;
        background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%) !important;
        border: 1px solid #1d4ed8 !important;
        box-shadow: 0 1px 2px 0 rgba(37, 99, 235, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
        cursor: pointer !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .admin-btn-primary:hover,
    a.admin-btn-primary:hover,
    button.admin-btn-primary:hover {
        background-color: #1d4ed8 !important;
        background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%) !important;
        border-color: #1e40af !important;
        box-shadow: 0 3px 10px -1px rgba(37, 99, 235, 0.35) !important;
        transform: translateY(-1px) !important;
        color: #ffffff !important;
    }

    .admin-btn-primary:active,
    a.admin-btn-primary:active,
    button.admin-btn-primary:active {
        transform: translateY(0) scale(0.98) !important;
    }

    .admin-btn-secondary,
    a.admin-btn-secondary,
    button.admin-btn-secondary {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.45rem !important;
        padding: 0.45rem 0.85rem !important;
        border-radius: 0.625rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        color: #334155 !important;
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
        cursor: pointer !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .admin-btn-secondary:hover,
    a.admin-btn-secondary:hover,
    button.admin-btn-secondary:hover {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border-color: #94a3b8 !important;
        box-shadow: 0 2px 8px -1px rgba(0, 0, 0, 0.08) !important;
        transform: translateY(-1px) !important;
    }

    .admin-btn-secondary:active,
    a.admin-btn-secondary:active,
    button.admin-btn-secondary:active {
        transform: translateY(0) scale(0.98) !important;
    }

    html.dark .admin-btn-secondary,
    html.dark a.admin-btn-secondary,
    html.dark button.admin-btn-secondary {
        color: #e2e8f0 !important;
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }

    html.dark .admin-btn-secondary:hover,
    html.dark a.admin-btn-secondary:hover,
    html.dark button.admin-btn-secondary:hover {
        background-color: #334155 !important;
        color: #ffffff !important;
        border-color: #475569 !important;
    }

    .admin-btn-danger,
    a.admin-btn-danger,
    button.admin-btn-danger {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.45rem !important;
        padding: 0.45rem 0.85rem !important;
        border-radius: 0.625rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        color: #ffffff !important;
        background-color: #dc2626 !important;
        background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%) !important;
        border: 1px solid #b91c1c !important;
        box-shadow: 0 1px 2px 0 rgba(220, 38, 38, 0.25) !important;
        cursor: pointer !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .admin-btn-danger:hover,
    a.admin-btn-danger:hover,
    button.admin-btn-danger:hover {
        background-color: #b91c1c !important;
        background: linear-gradient(180deg, #dc2626 0%, #b91c1c 100%) !important;
        border-color: #991b1b !important;
        box-shadow: 0 3px 10px -1px rgba(220, 38, 38, 0.35) !important;
        transform: translateY(-1px) !important;
        color: #ffffff !important;
    }

    .admin-btn-success,
    a.admin-btn-success,
    button.admin-btn-success {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.45rem !important;
        padding: 0.45rem 0.85rem !important;
        border-radius: 0.625rem !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        color: #ffffff !important;
        background-color: #16a34a !important;
        background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%) !important;
        border: 1px solid #15803d !important;
        box-shadow: 0 1px 2px 0 rgba(22, 163, 74, 0.25) !important;
        cursor: pointer !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .admin-btn-success:hover,
    a.admin-btn-success:hover,
    button.admin-btn-success:hover {
        background-color: #15803d !important;
        background: linear-gradient(180deg, #16a34a 0%, #15803d 100%) !important;
        border-color: #166534 !important;
        box-shadow: 0 3px 10px -1px rgba(22, 163, 74, 0.35) !important;
        transform: translateY(-1px) !important;
        color: #ffffff !important;
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

    /* Admin Sidebar Sizing & Layout (16rem / 256px for clean desktop proportions) */
    #main-sidebar,
    aside#main-sidebar {
        width: 16rem !important;
        min-width: 16rem !important;
        max-width: 16rem !important;
        transition: width 0.22s cubic-bezier(0.16, 1, 0.3, 1), transform 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #main-sidebar.is-collapsed,
    aside#main-sidebar.is-collapsed {
        width: 4.5rem !important;
        min-width: 4.5rem !important;
        max-width: 4.5rem !important;
    }

    #main-sidebar nav a {
        font-size: 0.8125rem !important; /* ~11.5px - 12px clean medium */
        line-height: 1.4 !important;
        font-weight: 600 !important;
    }

    #main-sidebar nav a span {
        white-space: nowrap !important;
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

    /* Admin Badge in Dark Mode */
    html.dark #main-sidebar .bg-blue-50:not(a) {
        background-color: rgba(30, 58, 138, 0.4) !important;
        border-color: rgba(59, 130, 246, 0.3) !important;
    }

    html.dark #main-sidebar .text-blue-900 {
        color: #BFDBFE !important; /* High contrast bright blue */
    }

    html.dark #main-sidebar .text-blue-700 {
        color: #93C5FD !important; /* High contrast soft blue */
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

    /* ----------------------------------------------------
       LIGHT MODE (WHITE) SIDEBAR & NAVBAR (DARK MODE OFF ONLY)
    ---------------------------------------------------- */
    html:not(.dark) header,
    html:not(.dark) #admin-navbar,
    html:not(.dark) .admin-navbar,
    html:not(.dark) body > div > header {
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

    html:not(.dark) #main-sidebar .bg-slate-50\/50,
    html:not(.dark) #main-sidebar .bg-slate-50 {
        background-color: #F8FAFC !important;
    }

    /* ----------------------------------------------------
       RESPONSIVE FULL-SCREEN ADMIN CONTENT FIT
    ---------------------------------------------------- */
    body > div > main,
    main#comp-main,
    main#admin-main,
    main[id$="-main"] {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
    }

    /* Active link in dark sidebar */
    html.dark #main-sidebar a.bg-blue-50 {
        background-color: rgba(37, 99, 235, 0.25) !important;
        color: #93C5FD !important;
    }

    html.dark #main-sidebar a.bg-blue-50 i {
        color: #60A5FA !important;
    }

    /* Dark Cards & Surfaces - Complete Coverage for All Admin Pages */
    html.dark .bg-white,
    html.dark .card-clean,
    html.dark .dashboard-card,
    html.dark .stat-card,
    html.dark .stat-card-clean,
    html.dark .table-card-clean,
    html.dark .filter-bar-clean,
    html.dark .section-card-clean,
    html.dark .section-card,
    html.dark .network-row,
    html.dark .metric-card,
    html.dark .table-container,
    html.dark [class*="stat-card"],
    html.dark [class*="table-card"],
    html.dark [class*="filter-bar"],
    html.dark [class*="section-card"],
    html.dark [class*="card-clean"],
    html.dark [class*="dashboard-card"] {
        background-color: #111827 !important;
        background: #111827 !important;
        background-image: none !important;
        border-color: #1E293B !important;
        color: #F8FAFC !important;
    }

    html.dark .card-clean,
    html.dark .stat-card-clean,
    html.dark .table-card-clean,
    html.dark .section-card-clean {
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5) !important;
    }

    /* Sub-card and inset surfaces in Dark Mode */
    html.dark .bg-slate-50,
    html.dark [class*="bg-slate-50"],
    html.dark [class*="bg-gray-50"],
    html.dark [class*="bg-zinc-50"] {
        background-color: #1E293B !important;
        border-color: #334155 !important;
        color: #F8FAFC !important;
    }

    html.dark .bg-slate-100,
    html.dark [class*="bg-slate-100"],
    html.dark [class*="bg-gray-100"] {
        background-color: #1E293B !important;
        border-color: #334155 !important;
        color: #F8FAFC !important;
    }

    /* Dark Borders & Dividers */
    html.dark .border-slate-200,
    html.dark .border-slate-100,
    html.dark .border-slate-50,
    html.dark .border-gray-200,
    html.dark .border-gray-100,
    html.dark [class*="border-slate-100"],
    html.dark [class*="border-slate-200"],
    html.dark [class*="border-gray-100"],
    html.dark [class*="border-gray-200"],
    html.dark .divide-slate-200> :not([hidden])~ :not([hidden]),
    html.dark .divide-slate-100> :not([hidden])~ :not([hidden]),
    html.dark .divide-gray-200> :not([hidden])~ :not([hidden]),
    html.dark .divide-gray-100> :not([hidden])~ :not([hidden]) {
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
    html.dark .text-gray-600,
    html.dark .text-gray-500 {
        color: #CBD5E1 !important;
        font-weight: 500 !important;
    }

    html.dark .text-slate-400,
    html.dark .text-gray-400 {
        color: #94A3B8 !important;
    }

    /* Dark Tables */
    html.dark table {
        border-color: #1E293B !important;
    }

    html.dark table thead,
    html.dark table thead tr,
    html.dark table th {
        background-color: #0F172A !important;
        color: #94A3B8 !important;
        border-color: #1E293B !important;
    }

    html.dark table tbody tr {
        background-color: transparent !important;
        border-color: #1E293B !important;
    }

    html.dark table tbody tr:hover {
        background-color: #1E293B !important;
    }

    html.dark table td {
        border-color: #1E293B !important;
        color: #E2E8F0 !important;
    }

    /* Modals & Dialogs in Dark Mode */
    html.dark [id*="Modal"] > div,
    html.dark .modal-content,
    html.dark [class*="modal"] > div {
        background-color: #111827 !important;
        border-color: #1E293B !important;
        color: #F8FAFC !important;
    }

    /* Inputs, Selects & Forms in Dark Mode */
    html.dark input,
    html.dark select,
    html.dark textarea {
        background-color: #1E293B !important;
        border-color: #334155 !important;
        color: #F8FAFC !important;
    }

    html.dark input::placeholder,
    html.dark textarea::placeholder {
        color: #64748B !important;
    }

    html.dark input:focus,
    html.dark select:focus,
    html.dark textarea:focus {
        border-color: #3B82F6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25) !important;
    }

    html.dark select option,
    html.dark select optgroup {
        background-color: #0F172A !important;
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

    /* Dark Mode Status Badges & Pill Holders */
    html.dark .bg-\[\#faf5ff\],
    html.dark .bg-\[\#f3e8ff\],
    html.dark .bg-purple-50 {
        background-color: rgba(147, 51, 234, 0.2) !important;
        color: #D8B4FE !important;
        border-color: rgba(147, 51, 234, 0.35) !important;
    }
    html.dark .text-\[\#9333ea\],
    html.dark .text-\[\#a855f7\] {
        color: #C084FC !important;
    }

    html.dark .bg-\[\#f0fdf4\],
    html.dark .bg-\[\#dcfce7\],
    html.dark .bg-emerald-50,
    html.dark .bg-green-50 {
        background-color: rgba(22, 163, 74, 0.2) !important;
        color: #4ADE80 !important;
        border-color: rgba(22, 163, 74, 0.35) !important;
    }
    html.dark .text-\[\#16a34a\] {
        color: #4ADE80 !important;
    }

    html.dark .bg-\[\#eff6ff\],
    html.dark .bg-\[\#dbeafe\],
    html.dark .bg-blue-50 {
        background-color: rgba(37, 99, 235, 0.2) !important;
        color: #93C5FD !important;
        border-color: rgba(37, 99, 235, 0.35) !important;
    }
    html.dark .text-\[\#2563eb\] {
        color: #93C5FD !important;
    }

    html.dark .bg-\[\#fefce8\],
    html.dark .bg-\[\#fef9c3\],
    html.dark .bg-amber-50,
    html.dark .bg-yellow-50 {
        background-color: rgba(202, 138, 4, 0.2) !important;
        color: #FCD34D !important;
        border-color: rgba(202, 138, 4, 0.35) !important;
    }
    html.dark .text-\[\#ca8a04\] {
        color: #FCD34D !important;
    }

    html.dark .bg-\[\#fff1f2\],
    html.dark .bg-\[\#ffe4e6\],
    html.dark .bg-rose-50,
    html.dark .bg-red-50 {
        background-color: rgba(244, 63, 94, 0.2) !important;
        color: #FDA4AF !important;
        border-color: rgba(244, 63, 94, 0.35) !important;
    }

    html.dark .bg-indigo-50 {
        background-color: rgba(99, 102, 241, 0.2) !important;
        color: #A5B4FC !important;
        border-color: rgba(99, 102, 241, 0.35) !important;
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

    /* ====================================================
       COMPREHENSIVE DARK MODE HOVER SYSTEM
       Ensures all text & surfaces remain 100% visible on hover
       ==================================================== */

    /* 1. All Light Backgrounds on Hover -> Dark Slate Surfaces */
    html.dark .hover\:bg-slate-50:hover,
    html.dark .hover\:bg-slate-50\/40:hover,
    html.dark .hover\:bg-slate-50\/50:hover,
    html.dark .hover\:bg-slate-50\/60:hover,
    html.dark .hover\:bg-slate-50\/70:hover,
    html.dark .hover\:bg-slate-50\/80:hover,
    html.dark .hover\:bg-slate-50\/90:hover,
    html.dark .hover\:bg-slate-100:hover,
    html.dark .hover\:bg-slate-100\/50:hover,
    html.dark .hover\:bg-slate-100\/70:hover,
    html.dark .hover\:bg-slate-100\/80:hover,
    html.dark .hover\:bg-slate-200:hover,
    html.dark .hover\:bg-gray-50:hover,
    html.dark .hover\:bg-gray-100:hover,
    html.dark .hover\:bg-gray-200:hover,
    html.dark tr.hover\:bg-slate-50\/50:hover,
    html.dark tr.hover\:bg-slate-50\/60:hover,
    html.dark tr.hover\:bg-slate-50\/70:hover,
    html.dark tr.hover\:bg-slate-50:hover,
    html.dark div.hover\:bg-slate-50\/60:hover,
    html.dark div.hover\:bg-slate-50\/70:hover,
    html.dark div.hover\:bg-slate-50\/80:hover,
    html.dark div.hover\:bg-slate-50:hover {
        background-color: #1E293B !important;
        border-color: #334155 !important;
    }

    /* 2. Soft Colored Light Accents on Hover -> Translucent Dark Accents */
    html.dark .hover\:bg-indigo-50:hover,
    html.dark .hover\:bg-blue-50:hover {
        background-color: rgba(37, 99, 235, 0.25) !important;
        border-color: rgba(59, 130, 246, 0.4) !important;
    }

    html.dark .hover\:bg-emerald-50:hover,
    html.dark .hover\:bg-emerald-100:hover {
        background-color: rgba(16, 185, 129, 0.25) !important;
        border-color: rgba(16, 185, 129, 0.4) !important;
    }

    html.dark .hover\:bg-rose-50:hover,
    html.dark .hover\:bg-rose-100:hover {
        background-color: rgba(244, 63, 94, 0.25) !important;
        border-color: rgba(244, 63, 94, 0.4) !important;
    }

    html.dark .hover\:bg-amber-50:hover,
    html.dark .hover\:bg-amber-100:hover {
        background-color: rgba(245, 158, 11, 0.25) !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }

    /* 3. Dark Text Colors on Hover -> Bright White / High Contrast */
    html.dark .hover\:text-slate-900:hover,
    html.dark .hover\:text-slate-800:hover,
    html.dark .hover\:text-slate-700:hover,
    html.dark .hover\:text-slate-600:hover,
    html.dark .hover\:text-slate-500:hover,
    html.dark .hover\:text-gray-900:hover,
    html.dark .hover\:text-gray-800:hover,
    html.dark .hover\:text-gray-700:hover,
    html.dark .hover\:text-gray-600:hover,
    html.dark .hover\:text-black:hover {
        color: #FFFFFF !important;
    }

    /* 4. Indigo / Blue / Colored Text on Hover */
    html.dark .hover\:text-indigo-600:hover,
    html.dark .hover\:text-indigo-700:hover,
    html.dark .hover\:text-blue-600:hover,
    html.dark .hover\:text-blue-700:hover {
        color: #93C5FD !important;
    }

    html.dark .hover\:text-emerald-600:hover,
    html.dark .hover\:text-emerald-700:hover {
        color: #6EE7B7 !important;
    }

    html.dark .hover\:text-rose-600:hover,
    html.dark .hover\:text-rose-700:hover {
        color: #FDA4AF !important;
    }

    html.dark .hover\:text-amber-600:hover,
    html.dark .hover\:text-amber-700:hover {
        color: #FCD34D !important;
    }

    /* 5. Hovering Over Table Rows Keeps All Row Text Crystal Clear */
    html.dark table tbody tr:hover,
    html.dark table tbody tr:hover td {
        background-color: #1E293B !important;
    }

    html.dark table tbody tr:hover td {
        color: #F8FAFC !important;
    }

    html.dark table tbody tr:hover td .text-slate-900,
    html.dark table tbody tr:hover td .text-slate-800,
    html.dark table tbody tr:hover td .text-slate-700 {
        color: #FFFFFF !important;
    }

    html.dark table tbody tr:hover td .text-slate-600,
    html.dark table tbody tr:hover td .text-slate-500,
    html.dark table tbody tr:hover td .text-slate-400 {
        color: #CBD5E1 !important;
    }

    /* 6. Hovering Over List & Audit Trail Divs */
    html.dark div.hover\:bg-slate-50\/60:hover .text-slate-900,
    html.dark div.hover\:bg-slate-50\/60:hover .text-slate-800,
    html.dark div.hover\:bg-slate-50\/70:hover .text-slate-900,
    html.dark div.hover\:bg-slate-50\/70:hover .text-slate-800,
    html.dark div.hover\:bg-slate-50\/80:hover .text-slate-900,
    html.dark div.hover\:bg-slate-50\/80:hover .text-slate-800,
    html.dark div.hover\:bg-slate-50:hover .text-slate-900,
    html.dark div.hover\:bg-slate-50:hover .text-slate-800 {
        color: #FFFFFF !important;
    }

    html.dark div.hover\:bg-slate-50\/60:hover .text-slate-500,
    html.dark div.hover\:bg-slate-50\/60:hover .text-slate-400,
    html.dark div.hover\:bg-slate-50\/70:hover .text-slate-500,
    html.dark div.hover\:bg-slate-50\/70:hover .text-slate-400,
    html.dark div.hover\:bg-slate-50\/80:hover .text-slate-500,
    html.dark div.hover\:bg-slate-50\/80:hover .text-slate-400,
    html.dark div.hover\:bg-slate-50:hover .text-slate-500,
    html.dark div.hover\:bg-slate-50:hover .text-slate-400 {
        color: #CBD5E1 !important;
    }

    /* 7. Action Icons on Hover */
    html.dark .hover\:bg-slate-50:hover i,
    html.dark .hover\:bg-slate-100:hover i {
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
