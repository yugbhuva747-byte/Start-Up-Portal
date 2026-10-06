<?php
/**
 * System Maintenance Mode Gateway
 * Dispatched automatically when Platform Maintenance is activated by an Administrator.
 * Only System Administrators have access while this mode is active.
 */
require_once __DIR__ . '/config.php';

$isPreview = isset($_GET['preview']) && $_GET['preview'] === '1' && auth_check() && ($currentUser['role'] ?? '') === 'admin';
$isMaintenanceActive = function_exists('is_maintenance_mode') && is_maintenance_mode();

// If maintenance is OFF and not in preview mode, redirect to portal home
if (!$isMaintenanceActive && !$isPreview) {
    header('Location: ' . url('index.php'), true, 302);
    exit;
}

// Send standard HTTP 503 Service Unavailable header for search engines & browsers
if (!$isPreview) {
    http_response_code(503);
    header('Retry-After: 3600');
}

$info = function_exists('get_maintenance_info') ? get_maintenance_info() : [
    'title' => 'Scheduled Platform Maintenance',
    'message' => 'Our platform is currently undergoing scheduled infrastructure upgrades. We will be back online shortly.',
    'estimated_end' => '',
    'allowed_ips' => ''
];

$currentUser = auth_check() ? current_user() : null;
$isAdmin = $currentUser && ($currentUser['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($info['title']) ?> • <?= APP_NAME ?></title>
    <meta name="description" content="Platform maintenance is currently in progress. We will be back shortly.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #0b0f19;
            color: #f1f5f9;
        }
        .maintenance-glow {
            background: radial-gradient(circle at 50% 30%, rgba(99, 102, 241, 0.15) 0%, rgba(15, 23, 42, 0) 70%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between p-4 sm:p-6 maintenance-glow relative">

    <!-- Admin Bypass Banner (Visible only to authenticated Administrators) -->
    <?php if ($isAdmin): ?>
    <div class="fixed top-0 left-0 right-0 z-50 bg-amber-500/10 border-b border-amber-500/30 backdrop-blur-md px-4 py-2.5 text-xs text-amber-300 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
            </span>
            <span class="font-bold">Administrator Bypass Active</span>
            <span class="hidden sm:inline text-amber-400/80">• You are viewing the maintenance screen as an Admin. Non-admin users are blocked.</span>
        </div>
        <div class="flex items-center space-x-2">
            <a href="<?= url('admin/dashboard.php') ?>" class="px-2.5 py-1 bg-amber-500/20 hover:bg-amber-500/30 text-amber-200 rounded-lg font-bold transition">
                Admin Panel →
            </a>
            <a href="<?= url('admin/settings.php') ?>" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg font-semibold transition">
                Settings
            </a>
        </div>
    </div>
    <div class="h-10"></div>
    <?php endif; ?>

    <!-- Header Logo -->
    <header class="w-full max-w-3xl mx-auto pt-6 pb-2 text-center">
        <div class="inline-flex items-center space-x-2.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                <i data-lucide="zap" class="w-5 h-5"></i>
            </div>
            <div class="text-left">
                <span class="text-base font-black tracking-tight text-white flex items-center gap-1">
                    STARTUP <span class="text-indigo-400">×</span> INVESTOR
                </span>
                <span class="block text-[9px] tracking-widest text-slate-400 uppercase font-bold">Venture Infrastructure</span>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="w-full max-w-2xl mx-auto my-auto py-8 text-center" id="maintenance-card">
        
        <!-- Live Status Pill -->
        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold mb-6">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
            </span>
            <span>Scheduled Maintenance In Progress</span>
        </div>

        <!-- Hero Icon -->
        <div class="relative w-20 h-20 mx-auto mb-6 flex items-center justify-center">
            <div class="absolute inset-0 rounded-2xl bg-indigo-600/20 blur-xl"></div>
            <div class="w-20 h-20 rounded-2xl bg-slate-800/80 border border-slate-700/80 flex items-center justify-center text-indigo-400 shadow-xl relative">
                <i data-lucide="wrench" class="w-9 h-9"></i>
            </div>
        </div>

        <!-- Title & Message -->
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-3">
            <?= htmlspecialchars($info['title'] ?: 'Scheduled Platform Maintenance') ?>
        </h1>

        <div class="text-sm sm:text-base text-slate-300 max-w-lg mx-auto leading-relaxed mb-6 font-normal">
            <?= nl2br(htmlspecialchars($info['message'] ?: 'Our engineers are currently performing scheduled platform maintenance and security updates. We will be back online shortly.')) ?>
        </div>

        <!-- Estimated Completion Box (if provided) -->
        <?php if (!empty($info['estimated_end'])): ?>
        <div class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-slate-800/60 border border-slate-700/70 text-slate-200 text-xs font-medium mb-8 shadow-sm">
            <i data-lucide="clock" class="w-4 h-4 text-indigo-400 flex-shrink-0"></i>
            <span>Estimated Reopening: <strong class="text-white font-bold"><?= htmlspecialchars($info['estimated_end']) ?></strong></span>
        </div>
        <?php endif; ?>

        <!-- Reassurance Feature Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 max-w-xl mx-auto mb-8 text-left">
            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-slate-300">
                <div class="flex items-center space-x-2 mb-1">
                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                    <span class="text-xs font-bold text-white">Escrow Secure</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-normal">All transaction ledgers and investor allotments remain isolated & secured.</p>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-slate-300">
                <div class="flex items-center space-x-2 mb-1">
                    <i data-lucide="database" class="w-4 h-4 text-blue-400"></i>
                    <span class="text-xs font-bold text-white">Data Integrity</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-normal">Real-time cap tables and company verification records are preserved.</p>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-slate-300">
                <div class="flex items-center space-x-2 mb-1">
                    <i data-lucide="zap" class="w-4 h-4 text-indigo-400"></i>
                    <span class="text-xs font-bold text-white">Engine Upgrade</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-normal">Deploying system performance and venture matching optimizations.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button type="button" onclick="window.location.reload()" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/25 transition flex items-center justify-center space-x-2">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                <span>Check System Status</span>
            </button>

            <a href="<?= url('auth/login.php') ?>" class="w-full sm:w-auto px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl border border-slate-700 transition flex items-center justify-center space-x-1.5">
                <i data-lucide="lock" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Administrator Sign In</span>
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="w-full max-w-3xl mx-auto py-4 text-center text-xs text-slate-500 border-t border-slate-800/80">
        <p class="mb-1">
            If you have an urgent inquiry, please reach out to our team at 
            <a href="mailto:<?= defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'support@startupportal.com' ?>" class="text-indigo-400 hover:underline font-semibold">
                <?= defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'support@startupportal.com' ?>
            </a>
        </p>
        <p class="text-[10px] text-slate-600">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. Regulated Venture Allocation &amp; Compliance Protocol.
        </p>
    </footer>

    <script>
        lucide.createIcons();
        gsap.from("#maintenance-card", { duration: 0.5, y: 15, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
