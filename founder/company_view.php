<?php
/**
 * Founder Module: Public Company Profile & Deal Room
 * Accessible by Founders, Investors, and Admins
 * Features: Diligence room, pitch, metrics, founders, and Angel Co-Investment CTA
 */
require_once __DIR__ . '/../config.php';
$currentUser = require_auth();
$db = get_db();

// 1. Resolve Company ID
$hashId = $_GET['id'] ?? '';
$companyId = decode_id($hashId);

if ($companyId <= 0 && is_numeric($hashId)) {
    $companyId = (int)$hashId;
}

if ($companyId <= 0) {
    set_flash('error', 'Company profile not specified or invalid.');
    $fallbackUrl = ($currentUser['role'] ?? '') === 'investor' ? url('investor/discover.php') : url('founder/feed.php');
    header('Location: ' . $fallbackUrl);
    exit;
}

$company = null;
$founders = [];
$activeRound = null;
$documents = [];
$isMyCompany = false;
$existingInterest = null;
$flash = get_flash();

if ($db) {
    // Fetch Company
    $cStmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $cStmt->execute([$companyId]);
    $company = $cStmt->fetch();

    if (!$company) {
        set_flash('error', 'Company not found.');
        $fallbackUrl = ($currentUser['role'] ?? '') === 'investor' ? url('investor/discover.php') : url('founder/feed.php');
        header('Location: ' . $fallbackUrl);
        exit;
    }

    // Fetch Founders
    $fStmt = $db->prepare("
        SELECT u.*, cf.designation, cf.equity_percent, cf.is_signatory,
               fp.bio, fp.linkedin_url
        FROM company_founders cf
        JOIN users u ON cf.user_id = u.id
        LEFT JOIN founder_profiles fp ON u.id = fp.user_id
        WHERE cf.company_id = ?
    ");
    $fStmt->execute([$companyId]);
    $founders = $fStmt->fetchAll();

    // Check if current user is one of the founders
    foreach ($founders as $f) {
        if ($f['id'] == $currentUser['id']) {
            $isMyCompany = true;
            break;
        }
    }

    // Fetch Active Funding Round
    $rStmt = $db->prepare("
        SELECT * FROM funding_rounds 
        WHERE company_id = ? AND status IN ('LIVE', 'PARTIALLY_FUNDED', 'APPROVED') 
        ORDER BY created_at DESC LIMIT 1
    ");
    $rStmt->execute([$companyId]);
    $activeRound = $rStmt->fetch();

    // Fetch Public Documents
    $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
    $dStmt->execute([$companyId]);
    $documents = $dStmt->fetchAll();

    // Check existing angel interest
    $iStmt = $db->prepare("SELECT * FROM investor_interests WHERE investor_id = ? AND company_id = ? LIMIT 1");
    $iStmt->execute([$currentUser['id'], $companyId]);
    $existingInterest = $iStmt->fetch();
}

// Handle Angel Investment / Express Interest Submission (Investors Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'express_angel_interest') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token.');
        header('Location: ' . url('founder/company_view.php?id=' . encode_id($companyId)));
        exit;
    }

    if (($currentUser['role'] ?? '') !== 'investor') {
        set_flash('error', 'Only verified investors can submit investment interests.');
        header('Location: ' . url('founder/company_view.php?id=' . encode_id($companyId)));
        exit;
    }

    if ($isMyCompany) {
        set_flash('error', 'You cannot invest in your own company.');
        header('Location: ' . url('founder/company_view.php?id=' . encode_id($companyId)));
        exit;
    }

    $amount = (float)($_POST['amount'] ?? 100000);
    $ticketType = trim($_POST['investment_type'] ?? 'Direct Angel');
    $note = trim($_POST['message'] ?? '');

    $fullMessage = "Proposed Ticket: ₹" . number_format($amount) . " (" . htmlspecialchars($ticketType) . ")\nStrategic Note: " . $note;

    try {
        if ($existingInterest) {
            $upd = $db->prepare("UPDATE investor_interests SET message = ?, status = 'pending', updated_at = NOW() WHERE id = ?");
            $upd->execute([$fullMessage, $existingInterest['id']]);
        } else {
            $ins = $db->prepare("
                INSERT INTO investor_interests (investor_id, company_id, message, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $ins->execute([$currentUser['id'], $companyId, $fullMessage]);
        }

        // Notify founders of the target company
        foreach ($founders as $f) {
            send_notification(
                $f['id'],
                "Founder Angel Interest from " . $currentUser['name'],
                "Founder {$currentUser['name']} expressed angel investment interest of ₹" . number_format($amount) . " in {$company['name']}.",
                "success",
                "founder/investor_interests.php"
            );
        }

        log_audit($currentUser['id'], 'FOUNDER_ANGEL_INTEREST', 'companies', $companyId, "Founder expressed angel interest of ₹{$amount}");
        set_flash('success', "Angel investment interest of ₹" . number_format($amount) . " sent to {$company['name']} leadership team!");
    } catch (Exception $e) {
        set_flash('error', 'Failed to submit interest: ' . $e->getMessage());
    }

    header('Location: ' . url('founder/company_view.php?id=' . encode_id($companyId)));
    exit;
}

$pageTitle = htmlspecialchars($company['name']) . ' • Startup Deal Room';
$compInitials = strtoupper(substr($company['name'] ?? 'ST', 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
</head>
<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Sidebar Navigation -->
    <?php 
    if ($currentUser['role'] === 'founder') {
        include __DIR__ . '/../includes/founder/sidebar.php';
    } elseif ($currentUser['role'] === 'investor') {
        include __DIR__ . '/../includes/investor/sidebar.php';
    } else {
        include __DIR__ . '/../includes/admin/sidebar.php';
    }
    ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        if ($currentUser['role'] === 'founder') {
            include __DIR__ . '/../includes/founder/navbar.php';
        } elseif ($currentUser['role'] === 'investor') {
            include __DIR__ . '/../includes/investor/navbar.php';
        } else {
            include __DIR__ . '/../includes/admin/navbar.php';
        }
        ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6">
            
            <!-- Flash Message -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800' ?> flex items-center space-x-2.5 shadow-sm">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Breadcrumb Navigation -->
            <div class="flex items-center justify-between text-sm text-slate-500 font-medium">
                <div class="flex items-center space-x-2">
                    <?php if (($currentUser['role'] ?? '') === 'investor'): ?>
                        <a href="<?= url('investor/discover.php') ?>" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Discover Startups</span>
                        </a>
                    <?php elseif (($currentUser['role'] ?? '') === 'admin'): ?>
                        <a href="<?= url('admin/companies.php') ?>" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Companies Registry</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('founder/feed.php') ?>" class="hover:text-slate-900 dark:hover:text-white transition flex items-center space-x-1.5 font-bold text-indigo-600 dark:text-indigo-400">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Founder Network</span>
                        </a>
                    <?php endif; ?>
                    <span>/</span>
                    <span class="text-slate-900 dark:text-white font-bold truncate max-w-xs"><?= htmlspecialchars($company['name']) ?></span>
                </div>

                <div class="flex items-center gap-2">
                    <?php if ($isMyCompany): ?>
                        <a href="<?= url('founder/company.php') ?>" class="px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-bold text-xs hover:bg-indigo-100 transition flex items-center gap-1.5">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Edit My Company</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- HERO COVER BANNER & COMPANY IDENTITY -->
            <!-- ======================================================== -->
            <div class="rounded-3xl overflow-hidden relative shadow-sm border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111827]">
                
                <!-- LinkedIn Cover Banner -->
                <div class="h-44 sm:h-60 <?= !empty($company['banner_url']) ? 'bg-slate-900' : 'bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900' ?> relative overflow-hidden">
                    <?php if (!empty($company['banner_url'])): ?>
                        <img src="<?= htmlspecialchars(get_company_banner_url($company['banner_url'])) ?>" 
                             alt="Cover Banner" 
                             class="w-full h-full object-cover object-center">
                    <?php else: ?>
                        <div class="w-full h-full opacity-20 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:20px_20px]"></div>
                    <?php endif; ?>

                    <div class="absolute top-4 right-4 flex items-center gap-2">
                        <span class="px-3.5 py-1.5 rounded-xl bg-black/60 backdrop-blur-md text-white text-xs font-bold border border-white/20 flex items-center gap-1.5 shadow-md">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>Verified Company Page</span>
                        </span>
                    </div>
                </div>

                <!-- Company Details & Overlapping Logo Section -->
                <div class="px-6 sm:px-10 pb-8 sm:pb-9 pt-0 relative">
                    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 sm:gap-8 border-b border-slate-100 dark:border-slate-800/80 pb-7 sm:pb-8">
                        
                        <!-- Left: Overlapping Logo & Identity -->
                        <div class="flex flex-col sm:flex-row sm:items-end gap-6">
                            
                            <!-- Logo Box -->
                            <div class="relative -mt-16 sm:-mt-22 flex-shrink-0">
                                <div class="w-32 h-32 sm:w-36 sm:h-36 rounded-2xl p-1 bg-white dark:bg-[#111827] border-4 border-white dark:border-[#111827] shadow-xl overflow-hidden flex items-center justify-center relative">
                                    <?php if (!empty($company['logo_url'])): ?>
                                        <img src="<?= htmlspecialchars(get_company_logo_url($company['logo_url'])) ?>" 
                                             alt="<?= htmlspecialchars($company['name']) ?>" 
                                             class="w-full h-full object-cover object-center rounded-xl">
                                    <?php else: ?>
                                        <div class="w-full h-full rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white font-extrabold text-3xl sm:text-4xl flex items-center justify-center shadow-inner">
                                            <?= htmlspecialchars($compInitials) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Headline & Name Info -->
                            <div class="pt-2 sm:pt-0 space-y-3 min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                                        <?= htmlspecialchars($company['name']) ?>
                                    </h1>
                                    <?php if (!empty($company['legal_name'])): ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs sm:text-sm px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold border border-slate-200/80 dark:border-slate-700/80 shadow-2xs">
                                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <?= htmlspecialchars($company['legal_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Pitch / Tagline -->
                                <p class="text-sm sm:text-base text-slate-700 dark:text-slate-200 font-normal leading-relaxed max-w-3xl">
                                    <?= htmlspecialchars($company['pitch'] ?? 'High-potential technology startup registered on the portal.') ?>
                                </p>

                                <!-- Modern Icon Badges -->
                                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                    <?php if (!empty($company['industry'])): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-50/90 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-semibold border border-indigo-200/80 dark:border-indigo-800/80 text-xs sm:text-sm shadow-2xs">
                                            <i data-lucide="briefcase" class="w-4 h-4 text-indigo-500"></i>
                                            <span><?= htmlspecialchars($company['industry']) ?></span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($company['stage'])): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold border border-amber-200/80 dark:border-amber-800/80 text-xs sm:text-sm shadow-2xs">
                                            <i data-lucide="trending-up" class="w-4 h-4 text-amber-500"></i>
                                            <span><?= htmlspecialchars($company['stage']) ?> Stage</span>
                                        </span>
                                    <?php endif; ?>

                                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-semibold border border-slate-200/80 dark:border-slate-700/80 text-xs sm:text-sm shadow-2xs">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-rose-500"></i>
                                        <span><?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?><?= !empty($company['country']) ? ', ' . htmlspecialchars($company['country']) : '' ?></span>
                                    </span>

                                    <?php if (!empty($company['employee_count'])): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 font-semibold border border-slate-200/80 dark:border-slate-700/80 text-xs sm:text-sm shadow-2xs">
                                            <i data-lucide="users" class="w-4 h-4 text-cyan-500"></i>
                                            <span><?= htmlspecialchars($company['employee_count']) ?>+ Team</span>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($company['website'])): ?>
                                        <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" rel="noopener noreferrer" 
                                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100/90 dark:hover:bg-blue-900/50 font-semibold border border-blue-200/80 dark:border-blue-800/70 text-xs sm:text-sm transition shadow-2xs">
                                            <i data-lucide="globe" class="w-4 h-4 text-blue-500"></i>
                                            <span><?= htmlspecialchars(preg_replace('#^https?://#', '', rtrim($company['website'], '/'))) ?></span>
                                            <i data-lucide="external-link" class="w-3.5 h-3.5 text-blue-400"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions: Investor CTA & Message -->
                        <div class="lg:w-80 flex flex-col justify-between gap-3 flex-shrink-0">
                            <?php if (!$isMyCompany): ?>
                                <?php if (($currentUser['role'] ?? '') === 'investor' && !empty($activeRound['id'])): ?>
                                    <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>"
                                       class="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-sm shadow-lg shadow-emerald-600/25 transition active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                                        <i data-lucide="zap" class="w-5 h-5 text-amber-300"></i>
                                        <span>Invest in Live Round</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (($currentUser['role'] ?? '') === 'investor'): ?>
                                    <button type="button" onclick="openAngelInvestModal()"
                                            class="w-full py-3 px-5 rounded-2xl <?= (!empty($activeRound['id'])) ? 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700' : 'bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/25' ?> font-extrabold text-xs sm:text-sm transition active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                                        <i data-lucide="badge-dollar-sign" class="w-4.5 h-4.5 text-amber-400"></i>
                                        <span><?= $existingInterest ? 'Update Investment Interest' : 'Express Direct / Angel Interest' ?></span>
                                    </button>
                                <?php endif; ?>
                                
                                <?php 
                                $firstFounderHash = !empty($founders[0]['id']) ? hash_id_encode($founders[0]['id']) : '';
                                $teamMsgUrl = (($currentUser['role'] ?? '') === 'investor')
                                    ? ($firstFounderHash ? url('investor/messages.php?founder=' . $firstFounderHash . '&company=' . encode_id($companyId)) : url('investor/messages.php'))
                                    : url('founder/messages.php');
                                ?>
                                <?= render_company_connect_button((int)$companyId, [
                                    'name' => $company['name'],
                                    'logo' => get_company_logo_url($company['logo_url'] ?? ''),
                                    'button_text' => 'Connect with Company',
                                    'class' => 'w-full py-2.5 px-4 justify-center shadow-xs'
                                ]) ?>

                                <a href="<?= $teamMsgUrl ?>" 
                                   class="w-full py-2.5 px-4 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-bold text-xs transition flex items-center justify-center gap-2 shadow-2xs">
                                    <i data-lucide="message-square" class="w-4 h-4 text-indigo-500"></i>
                                    <span>Message Founder Team</span>
                                </a>
                            <?php else: ?>
                                <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 text-center">
                                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 block mb-1">Your Startup Company</span>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400">This is how your venture appears to investors and syndicate partners.</p>
                                </div>
                            <?php endif; ?>

                            <?php if ($existingInterest && ($currentUser['role'] ?? '') === 'investor'): ?>
                                <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2 font-medium">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                                    <span>You submitted investment interest on <?= date('M d, Y', strtotime($existingInterest['created_at'])) ?> (Status: <strong><?= ucfirst($existingInterest['status']) ?></strong>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Live Funding Round Banner (If Active) -->
                    <?php if ($activeRound): ?>
                        <div class="mt-6 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-amber-500/10 via-indigo-500/10 to-transparent border border-amber-500/30 dark:border-amber-500/20 flex flex-col md:flex-row md:items-center justify-between gap-5">
                            <div class="space-y-1.5">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500 text-slate-900 font-black text-xs uppercase tracking-wider">
                                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                    <span>Live Funding Round Open</span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($activeRound['round_name'] ?? 'Series Seed Round') ?></h3>
                                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300">
                                    Target: <strong>₹<?= number_format($activeRound['target_amount'] ?? 0) ?></strong> • 
                                    Raised: <strong class="text-emerald-600 dark:text-emerald-400">₹<?= number_format($activeRound['amount_raised'] ?? 0) ?></strong> • 
                                    Min Angel Ticket: <strong>₹<?= number_format($activeRound['min_ticket_size'] ?? 50000) ?></strong>
                                </p>
                            </div>
                            <?php if (!$isMyCompany && ($currentUser['role'] ?? '') === 'investor'): ?>
                                <?php if (!empty($activeRound['id'])): ?>
                                    <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition shadow-sm active:scale-95 flex-shrink-0 inline-flex items-center gap-1.5">
                                        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                        <span>Participate in Round</span>
                                    </a>
                                <?php else: ?>
                                    <button type="button" onclick="openAngelInvestModal()" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs uppercase tracking-wider transition shadow-sm cursor-pointer active:scale-95 flex-shrink-0">
                                        Participate in Round
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- 2-COLUMN CONTENT: DETAILS & FOUNDERS -->
            <!-- ======================================================== -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left 2 Cols: Story, Problem/Solution, Documents -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- About & Elevator Pitch -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5 text-indigo-600"></i>
                            <span>Executive Summary & Traction</span>
                        </h2>
                        
                        <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-normal">
                            <?= nl2br(htmlspecialchars($company['description'] ?? 'No long-form description published yet.')) ?>
                        </p>

                        <?php if (!empty($company['problem'])): ?>
                            <div class="p-4 rounded-2xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-900/50 space-y-1">
                                <h4 class="text-xs font-bold text-rose-800 dark:text-rose-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-rose-500"></i>
                                    The Core Problem
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed"><?= nl2br(htmlspecialchars($company['problem'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($company['solution'])): ?>
                            <div class="p-4 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50 space-y-1">
                                <h4 class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    Proprietary Solution
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed"><?= nl2br(htmlspecialchars($company['solution'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Verified Pitch Deck & Data Room Preview -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="folder-lock" class="w-5 h-5 text-indigo-600"></i>
                                <span>Verified Pitch Deck & Due Diligence Room</span>
                            </h2>
                            <span class="text-xs text-slate-500 font-bold"><?= count($documents) ?> Documents</span>
                        </div>

                        <?php if (empty($documents)): ?>
                            <div class="py-8 text-center text-slate-400 text-xs">
                                No public diligence documents added to this startup's room yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($documents as $doc): 
                                    $docName = !empty($doc['title']) ? $doc['title'] : (!empty($doc['document_name']) ? $doc['document_name'] : 'Pitch Document');
                                    $docCat = !empty($doc['document_type']) ? $doc['document_type'] : (!empty($doc['category']) ? $doc['category'] : 'Pitch Deck');
                                    $docDate = !empty($doc['uploaded_at']) ? date('M d, Y', strtotime($doc['uploaded_at'])) : 'Verified';
                                ?>
                                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                                                <i data-lucide="file-check" class="w-5 h-5"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($docName) ?></div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400"><?= htmlspecialchars($docCat) ?> • <?= $docDate ?></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <a href="<?= url('download.php?id=' . encode_id($doc['id']) . '&type=company') ?>" class="text-[11px] px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800 flex items-center gap-1 transition shadow-2xs">
                                                <i data-lucide="download" class="w-3 h-3"></i> View
                                            </a>
                                            <span class="text-[10.5px] px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800 hidden sm:flex items-center gap-1">
                                                <i data-lucide="shield" class="w-3 h-3"></i> Verified
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right 1 Col: Leadership Team & Angel Syndicate Quick Box -->
                <div class="space-y-6">
                    
                    <!-- Founders & Leadership Team -->
                    <div class="rounded-3xl p-6 sm:p-7 bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="users" class="w-5 h-5 text-indigo-600"></i>
                            <span>Founders &amp; Leadership</span>
                        </h2>

                        <div class="space-y-4">
                            <?php foreach ($founders as $f): ?>
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 space-y-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full overflow-hidden shrink-0 flex-shrink-0 border-2 border-white dark:border-slate-700 shadow-xs" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px;">
                                            <img src="<?= htmlspecialchars(get_avatar_url($f['avatar_url'] ?? '')) ?>" 
                                                 alt="<?= htmlspecialchars($f['name']) ?>" 
                                                 class="w-full h-full object-cover object-center rounded-full"
                                                 style="width: 48px; height: 48px; object-fit: cover;">
                                        </div>
                                        
                                        <div class="min-w-0 flex-1">
                                            <a href="<?= url('founder/view.php?id=' . encode_id($f['id'])) ?>" class="text-sm font-extrabold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition block truncate">
                                                <?= htmlspecialchars($f['name']) ?>
                                            </a>
                                            <div class="text-xs text-indigo-600 dark:text-indigo-400 font-bold truncate">
                                                <?= htmlspecialchars($f['designation'] ?? 'Founder') ?>
                                            </div>
                                            <div class="text-[11px] text-slate-500 truncate">
                                                <?= htmlspecialchars($f['city'] ?? 'India') ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($f['bio'])): ?>
                                        <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2">
                                            <?= htmlspecialchars($f['bio']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="pt-2.5 border-t border-slate-200/70 dark:border-slate-700/70 flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <div class="flex items-center gap-2">
                                            <a href="<?= url('founder/view.php?id=' . encode_id($f['id'])) ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold flex items-center gap-1 text-xs">
                                                <span>Profile</span>
                                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                            </a>

                                            <?php if (!empty($f['linkedin_url'])): ?>
                                                <a href="<?= htmlspecialchars($f['linkedin_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-blue-600 transition" title="LinkedIn">
                                                    <i data-lucide="linkedin" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($f['id'] != $currentUser['id']): ?>
                                            <div>
                                                <?= render_connect_button((int)$f['id'], [
                                                    'name' => $f['name'],
                                                    'headline' => ($f['designation'] ?? 'Founder') . ' at ' . $company['name'],
                                                    'avatar' => get_avatar_url($f['avatar_url'] ?? ''),
                                                    'class' => 'text-[11px] py-1 px-2.5'
                                                ]) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Synergy / Collaboration Card -->
                    <?php if (($currentUser['role'] ?? '') === 'investor'): ?>
                        <div class="rounded-3xl p-6 bg-gradient-to-br from-indigo-900 to-slate-900 text-white shadow-md space-y-3 relative overflow-hidden">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-amber-300">
                                <i data-lucide="handshake" class="w-5 h-5"></i>
                            </div>
                            <h3 class="text-base font-bold text-white">Angel & Syndicate Deal Room</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Review confidential diligence docs, traction metrics, and submit strategic direct co-investment tickets.
                            </p>
                            <?php if (!$isMyCompany): ?>
                                <button type="button" onclick="openAngelInvestModal()" class="w-full py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-extrabold text-xs uppercase tracking-wider transition cursor-pointer shadow-sm active:scale-95">
                                    Express Angel Interest
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-3xl p-6 bg-gradient-to-br from-indigo-900 to-slate-900 text-white shadow-md space-y-3 relative overflow-hidden">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-indigo-300">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <h3 class="text-base font-bold text-white">Peer Founder Collaboration</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Connect with peer founders, exchange startup insights, and explore distribution or technical partnerships.
                            </p>
                            <?php if (!$isMyCompany): ?>
                                <a href="<?= $teamMsgUrl ?>" class="w-full py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white border border-white/20 font-extrabold text-xs uppercase tracking-wider transition cursor-pointer shadow-xs active:scale-95 flex items-center justify-center gap-1.5">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                    <span>Connect with Founders</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php if (($currentUser['role'] ?? '') === 'investor'): ?>
    <!-- ======================================================== -->
    <!-- ANGEL INVEST / EXPRESS INTEREST MODAL (Investors Only) -->
    <!-- ======================================================== -->
    <div id="angel-invest-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full border border-slate-200 dark:border-slate-800 shadow-2xl p-6 sm:p-7 space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 flex items-center justify-center font-bold">
                        <i data-lucide="coins" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Angel Co-Investment Ticket</h3>
                        <p class="text-xs text-slate-500">Back <?= htmlspecialchars($company['name']) ?> as a strategic angel founder</p>
                    </div>
                </div>
                <button type="button" onclick="closeAngelInvestModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="<?= url('founder/company_view.php?id=' . encode_id($companyId)) ?>" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="express_angel_interest">
                <input type="hidden" name="company_id" value="<?= $company['id'] ?>">

                <!-- Ticket Size Options -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Select Proposed Ticket Size (₹)</label>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ([50000, 100000, 250000, 500000, 1000000, 2500000] as $amt): ?>
                            <button type="button" onclick="setTicketAmount(<?= $amt ?>)" class="ticket-btn py-2 px-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-500 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition text-center cursor-pointer">
                                ₹<?= $amt >= 100000 ? ($amt / 100000) . ' Lakh' . ($amt > 100000 ? 's' : '') : ($amt / 1000) . 'k' ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-2.5">
                        <input type="number" name="amount" id="angel-ticket-input" value="100000" min="10000" step="10000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-black bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white" placeholder="Custom Amount in INR">
                    </div>
                </div>

                <!-- Investment Type -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Syndicate Role / Type</label>
                    <select name="investment_type" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm font-semibold bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                        <option value="Direct Angel Ticket">Direct Angel Ticket (Cash + Equity)</option>
                        <option value="Syndicate Co-Investment">Syndicate Co-Investment (Lead / Follower)</option>
                        <option value="Strategic Founder Advisory + Capital">Strategic Founder Advisory + Capital</option>
                    </select>
                </div>

                <!-- Note to Founder -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Note to <?= htmlspecialchars($company['name']) ?> Leadership</label>
                    <textarea name="message" rows="3" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200" placeholder="e.g. Love what you are building! Would like to participate in this round and help with distribution..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeAngelInvestModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md transition active:scale-95 cursor-pointer">
                        Send Angel Interest
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openAngelInvestModal() {
            document.getElementById('angel-invest-modal')?.classList.remove('hidden');
        }
        function closeAngelInvestModal() {
            document.getElementById('angel-invest-modal')?.classList.add('hidden');
        }
        function setTicketAmount(amt) {
            const input = document.getElementById('angel-ticket-input');
            if (input) input.value = amt;
        }
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAngelInvestModal(); });
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    </script>
    <script src="<?= url('assets/js/connect.js') ?>"></script>
</body>
</html>
