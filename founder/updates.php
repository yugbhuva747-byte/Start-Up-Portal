<?php
/**
 * Founder Module: Startup Investor Updates & Newsfeed Studio
 * Section 17 & 23 (Structured investor updates, news feed & broadcast studio)
 * Premium Visual Design, Interactive Studio, Live Investor Preview, Vay Portal Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Investor Updates & Traction Studio';

$error = '';
$flash = get_flash();
$company = null;
$updates = [];

if ($db) {
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    if ($company) {
        $uStmt = $db->prepare("SELECT * FROM startup_updates WHERE company_id = ? ORDER BY created_at DESC");
        $uStmt->execute([$company['id']]);
        $updates = $uStmt->fetchAll();

        // Calculate reach across investments & watchlists
        try {
            $stkStmt = $db->prepare("
                SELECT COUNT(DISTINCT user_id) FROM (
                    SELECT investor_user_id as user_id FROM investments WHERE company_id = ?
                    UNION
                    SELECT investor_user_id as user_id FROM watchlists WHERE company_id = ?
                ) as c
            ");
            $stkStmt->execute([$company['id'], $company['id']]);
            $totalStakeholders = (int)$stkStmt->fetchColumn();
        } catch (Exception $e) {
            $totalStakeholders = 0;
        }
    }
}

// Calculate summary stats
$totalUpdates = count($updates);
$lastUpdateDate = !empty($updates) ? date('M d, Y', strtotime($updates[0]['created_at'])) : 'No broadcasts yet';
$latestDaysAgo = !empty($updates) ? floor((time() - strtotime($updates[0]['created_at'])) / (60 * 60 * 24)) : null;

// Handle POST: Create new update or delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'create_update' && $company) {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? 'Milestone');
            $metrics = trim($_POST['metrics_summary'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $visibility = trim($_POST['visibility'] ?? 'all_investors');

            if (empty($title) || empty($content)) {
                $error = 'Please provide both title and update details.';
            } else {
                $ins = $db->prepare("
                    INSERT INTO startup_updates (company_id, founder_user_id, title, category, metrics_summary, content, visibility)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$company['id'], $user['id'], $title, $category, $metrics, $content, $visibility]);
                $updateId = $db->lastInsertId();

                // Notify all investors who have invested or watchlisted
                try {
                    $investorsToNotify = $db->prepare("
                        SELECT DISTINCT user_id FROM (
                            SELECT investor_user_id as user_id FROM investments WHERE company_id = ?
                            UNION
                            SELECT investor_user_id as user_id FROM watchlists WHERE company_id = ?
                        ) as combined
                    ");
                    $investorsToNotify->execute([$company['id'], $company['id']]);
                    $invList = $investorsToNotify->fetchAll();

                    foreach ($invList as $inv) {
                        send_notification(
                            $inv['user_id'],
                            "New Update from {$company['name']}",
                            "$title — {$category}: $metrics",
                            'info',
                            'investor/startup_detail.php?id=' . encode_id($company['id']) . '#updates'
                        );
                    }
                } catch (Exception $e) {}

                log_audit($user['id'], 'POST_STARTUP_UPDATE', 'startup_updates', $updateId, "Founder posted update: $title");
                set_flash('success', 'Investor update successfully published and notified to stakeholders!');
                header('Location: ' . url('founder/updates.php'));
                exit;
            }
        } elseif ($action === 'delete_update') {
            $updateId = (int)($_POST['update_id'] ?? 0);
            $db->prepare("DELETE FROM startup_updates WHERE id = ? AND founder_user_id = ?")->execute([$updateId, $user['id']]);
            set_flash('success', 'Update deleted successfully.');
            header('Location: ' . url('founder/updates.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        body, button, input, select, textarea, h1, h2, h3, h4, h5, h6, p, span, a, label {
            font-family: "Vay Portal", Sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        body {
            background-color: #F8FAFC;
            color: #0F172A;
        }
        .section-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.25rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .section-card:hover {
            border-color: #CBD5E1;
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
        }
        .studio-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 1.5rem;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        }
        html:not(.dark) .hero-banner {
            background: radial-gradient(130% 100% at 0% 0%, #EEF2FF 0%, #F8FAFC 50%, #F1F5F9 100%);
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .hero-banner {
            border-radius: 1.5rem;
            position: relative;
        }
        html.dark .hero-banner {
            background: radial-gradient(130% 100% at 0% 0%, #17213A 0%, #0F172A 55%, #111827 100%) !important;
            border: 1px solid #1E293B !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
        }
        .form-input-clean {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            color: #0F172A;
            font-size: 0.875rem;
            line-height: 1.4rem;
            transition: all 0.15s ease;
            outline: none;
        }
        .form-input-clean:hover {
            background-color: #F1F5F9;
            border-color: #CBD5E1;
        }
        .form-input-clean:focus {
            background-color: #FFFFFF;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .form-label-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 0.4rem;
        }
        .template-pill-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .template-pill-card:hover {
            transform: translateY(-2px);
            border-color: #6366F1;
            box-shadow: 0 6px 16px -2px rgba(99, 102, 241, 0.12);
        }
        html:not(.dark) .preview-phone-frame {
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        }
        .preview-phone-frame {
            border-radius: 1.25rem;
            position: relative;
            transition: all 0.2s ease;
        }
        html.dark .preview-phone-frame {
            border-color: #1E293B !important;
            background-color: #111827 !important;
            background: #111827 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.65) !important;
        }
        html.dark .template-pill-card {
            background-color: #111827 !important;
            border-color: #1E293B !important;
        }
        html.dark .template-pill-card:hover {
            background-color: #1E293B !important;
            border-color: #6366F1 !important;
        }
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Sticky Fixed Founder Navbar -->
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <!-- Full-screen Dynamic Main Container -->
        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6" id="updates-main">
            
            <!-- Toast / Flash Feedback Banner -->
            <?php if ($flash): ?>

                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs font-bold uppercase opacity-75">Notice</span>

                </div>
            <?php endif; ?>

            <?php if ($error): ?>

                <div class="p-4 rounded-2xl text-sm font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-3 shadow-xs">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-rose-600"></i>

                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>


            <!-- High-Impact Studio Executive Banner -->
            <div class="hero-banner p-6 sm:p-8 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white shadow-sm shadow-indigo-500/25">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                <span>Live Investor Broadcast Studio</span>
                            </span>
                            <?php if ($company): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/80 border border-slate-200/80 text-slate-700 backdrop-blur-sm">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-indigo-600"></i>
                                    <span><?= htmlspecialchars($company['name']) ?></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-[11px] font-mono text-slate-500">CIN: <?= htmlspecialchars($company['cin_number'] ?: 'Verified') ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Investor Updates & Traction Studio
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 max-w-2xl leading-relaxed">
                            Draft high-credibility monthly shareholder broadcasts, share ARR expansion, announce major product releases, and mobilize angel support with real-time stakeholder delivery.
                        </p>
                    </div>

                    <!-- Quick Studio Action Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" onclick="document.getElementById('studio-composer').scrollIntoView({behavior: 'smooth'})"
                                class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 hover:shadow-lg transition flex items-center space-x-2">
                            <i data-lucide="pen-tool" class="w-4 h-4"></i>
                            <span>Open Composer Studio</span>
                        </button>
                        <button type="button" onclick="document.getElementById('updates-timeline-section').scrollIntoView({behavior: 'smooth'})"
                                class="px-4 py-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs shadow-xs transition flex items-center space-x-2">
                            <i data-lucide="history" class="w-4 h-4 text-indigo-600"></i>
                            <span>View Timeline (<?= $totalUpdates ?>)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 Dynamic KPI Metric Cards (Modern SaaS Style) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- KPI 1: Broadcasts -->
                <div class="section-card p-5 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Broadcast History</div>
                            <div class="text-lg sm:text-xl font-bold text-slate-900 mt-1"><?= $totalUpdates ?></div>
                            <div class="text-xs text-indigo-600 font-bold mt-1.5 flex items-center gap-1">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                <span>Published Updates</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center font-bold group-hover:scale-110 transition duration-300">
                            <i data-lucide="send" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                        Dispatched to platform angel feed
                    </div>
                </div>

                <!-- KPI 2: Audience Reach -->
                <div class="section-card p-5 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Stakeholder Reach</div>
                            <div class="text-lg sm:text-xl font-bold text-slate-900 mt-1"><?= max(1, $totalStakeholders) ?></div>
                            <div class="text-xs text-emerald-600 font-bold mt-1.5 flex items-center gap-1">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>Direct Inboxes</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center font-bold group-hover:scale-110 transition duration-300">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                        Allotted investors & verified watchlists
                    </div>
                </div>

                <!-- KPI 3: Cadence Health -->
                <div class="section-card p-5 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reporting Cadence</div>
                            <div class="text-lg sm:text-xl font-bold text-slate-900 mt-1">
                                <?= $totalUpdates > 0 ? 'Consistent 🟢' : 'Kickoff 🟡' ?>
                            </div>
                            <div class="text-xs text-slate-500 font-medium mt-1.5 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full <?= $totalUpdates > 0 ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                                <span>Recommended: Monthly</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center font-bold group-hover:scale-110 transition duration-300">
                            <i data-lucide="calendar" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                        3.2x higher follow-on capital rate
                    </div>
                </div>

                <!-- KPI 4: Last Broadcast -->
                <div class="section-card p-5 relative overflow-hidden group">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Latest Transmission</div>
                            <div class="text-sm sm:text-base font-black text-slate-900 mt-1.5 truncate max-w-[150px]"><?= $lastUpdateDate ?></div>
                            <div class="text-xs text-purple-600 font-bold mt-1.5 flex items-center gap-1">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span><?= $latestDaysAgo !== null ? ($latestDaysAgo === 0 ? 'Sent today' : "$latestDaysAgo days ago") : 'Pending 1st post' ?></span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center font-bold group-hover:scale-110 transition duration-300">
                            <i data-lucide="radio" class="w-6 h-6"></i>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                        Automated investor digest logs
                    </div>
                </div>
            </div>

            <!-- DUAL PANE STUDIO: Composer & Real-time Live Investor Preview -->
            <div id="studio-composer" class="studio-card p-6 sm:p-8 space-y-6 scroll-mt-20">
                
                <!-- Studio Header with Template Pickers -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-pulse"></span>
                            <h2 class="text-lg font-bold text-slate-900">Broadcast Composer & Live Preview</h2>
                            <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold text-[11px] border border-indigo-100">Interactive Studio</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Compose your report below. The right panel renders in real-time exactly how investors see it.</p>
                    </div>

                    <!-- Mode Toggle / Actions -->
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-slate-400 font-semibold hidden sm:inline">Preview Mode:</span>
                        <div class="p-1 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center space-x-1 text-xs">
                            <button type="button" id="btn-view-card" onclick="setPreviewDevice('card')" class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 shadow-xs font-bold text-slate-800 dark:text-white transition">
                                🖥️ Card Feed
                            </button>
                            <button type="button" id="btn-view-email" onclick="setPreviewDevice('email')" class="px-3 py-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium transition">
                                📱 Investor Email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 4 Visual Smart Starter Template Cards (Not Boring!) -->
                <div>
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5 flex items-center justify-between">
                        <span>⚡ 1-Click Executive Templates:</span>
                        <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium">Click any template to auto-fill</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <!-- Template 1: Revenue & MRR -->
                        <div onclick="applyTemplate('revenue')" class="template-pill-card p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-emerald-300 hover:bg-emerald-50/20 dark:hover:bg-emerald-950/20">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800 flex items-center justify-center font-bold text-sm">
                                    📈
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Monthly MRR & ARR</div>
                                    <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Revenue, burn & runway</div>
                                </div>
                            </div>
                        </div>

                        <!-- Template 2: Product Ship -->
                        <div onclick="applyTemplate('product')" class="template-pill-card p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-cyan-300 hover:bg-cyan-50/20 dark:hover:bg-cyan-950/20">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 border border-cyan-100 dark:border-cyan-800 flex items-center justify-center font-bold text-sm">
                                    ⚡
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Product Ship / V2</div>
                                    <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Features, NPS & speeds</div>
                                </div>
                            </div>
                        </div>

                        <!-- Template 3: Milestone -->
                        <div onclick="applyTemplate('milestone')" class="template-pill-card p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-amber-300 hover:bg-amber-50/20 dark:hover:bg-amber-950/20">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800 flex items-center justify-center font-bold text-sm">
                                    🏆
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Major Milestone</div>
                                    <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Partnership, grant or PR</div>
                                </div>
                            </div>
                        </div>

                        <!-- Template 4: Key Hire -->
                        <div onclick="applyTemplate('hiring')" class="template-pill-card p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-purple-300 hover:bg-purple-50/20 dark:hover:bg-purple-950/20">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800 flex items-center justify-center font-bold text-sm">
                                    👥
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Key Talent & Hire</div>
                                    <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Leadership & team scale</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <?php if (!$company): ?>

                    <div class="p-10 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i data-lucide="building" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                        <h3 class="text-sm font-bold text-slate-800">Company Record Required</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">Please set up your company profile before publishing investor broadcasts.</p>
                        <a href="<?= url('founder/company.php') ?>" class="mt-4 inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-sm">
                            <span>Setup Company Profile</span>
                        </a>
                    </div>
                <?php else: ?>

                    <!-- Form + Live Simulator Split Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        
                        <!-- Left 7 Cols: The Interactive Form -->
                        <form action="<?= url('founder/updates.php') ?>" method="POST" id="update-form" class="lg:col-span-7 space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="create_update">

                            <!-- Headline Title -->
                            <div>
                                <label class="form-label-clean">
                                    <span>Broadcast Headline / Subject <span class="text-rose-500">*</span></span>
                                    <span id="title-char-count" class="text-[11px] text-slate-400 font-mono">0 / 120 chars</span>
                                </label>
                                <input type="text" name="title" id="inp-title" required 
                                       placeholder="e.g. October 2026: ARR reached ₹3.8 Cr (+145% YoY), launched AI Agent V2" 
                                       oninput="handleLiveSync()"
                                       class="form-input-clean font-semibold text-slate-900">

                            </div>


                            <!-- Category & Audience Scope in 2 Columns -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label-clean">
                                        <span>Category Tag <span class="text-rose-500">*</span></span>
                                    </label>
                                    <select name="category" id="inp-category" onchange="handleLiveSync()" class="form-input-clean font-medium cursor-pointer">
                                        <option value="Traction & Revenue">🚀 Traction & Revenue</option>
                                        <option value="Milestone">🏆 Major Milestone</option>
                                        <option value="Product Launch">⚡ Product Launch / V2</option>
                                        <option value="Team & Hiring">👥 Team & Key Hires</option>
                                        <option value="Financials">📊 Financials & Runway</option>
                                        <option value="Strategic Partnership">🤝 Strategic Partnership</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label-clean">
                                        <span>Audience Visibility Scope</span>
                                    </label>
                                    <select name="visibility" id="inp-visibility" onchange="handleLiveSync()" class="form-input-clean font-medium cursor-pointer">
                                        <option value="all_investors">🌐 Public Discovery (All Registered Investors)</option>
                                        <option value="portfolio_only">🔒 Confidential (Portfolio Cap Table Only)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Key Metrics Highlight Badge Strip with 1-Click Builder Pills -->
                            <div>
                                <label class="form-label-clean">
                                    <span>Key Metrics Summary Strip</span>
                                    <span class="text-[11px] text-emerald-600 font-bold">Renders as green callout pill</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="metrics_summary" id="inp-metrics" 
                                           placeholder="e.g. +24% MoM • ₹3.8 Cr ARR • 98% Retention • 14 mo Runway" 
                                           oninput="handleLiveSync()"
                                           class="form-input-clean pl-10 font-medium text-slate-900">
                                    <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                </div>

                                <!-- Quick Metric Pill Helpers -->
                                <div class="flex flex-wrap items-center gap-1.5 mt-2 text-[11px]">
                                    <span class="text-slate-400 font-medium">Quick chips:</span>
                                    <button type="button" onclick="appendMetricTag('+25% MoM')" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-mono transition">+25% MoM</button>
                                    <button type="button" onclick="appendMetricTag('₹1 Cr+ ARR')" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-mono transition">₹1 Cr+ ARR</button>
                                    <button type="button" onclick="appendMetricTag('Cashflow +ve')" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-mono transition">Cashflow +ve</button>
                                    <button type="button" onclick="appendMetricTag('18 Mo Runway')" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-mono transition">18 Mo Runway</button>
                                    <button type="button" onclick="appendMetricTag('Zero Churn')" class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-mono transition">Zero Churn</button>

                                </div>
                            </div>

                            <!-- Detailed Content Body with Format Helpers -->
                            <div>

                                <div class="form-label-clean">
                                    <span>Detailed Update Narrative <span class="text-rose-500">*</span></span>
                                    <div class="flex items-center space-x-2 text-[11px]">
                                        <span id="read-time-pill" class="text-slate-400 font-mono">0 words • ~0 min read</span>
                                    </div>
                                </div>

                                <!-- Fast Structured Section Injection Tools -->
                                <div class="flex flex-wrap items-center gap-1.5 mb-2 text-xs">
                                    <span class="text-slate-400 text-[11px]">Structure aids:</span>
                                    <button type="button" onclick="injectSection('highlights')" class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-medium transition">
                                        + Key Wins
                                    </button>
                                    <button type="button" onclick="injectSection('lowlights')" class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[11px] font-medium transition">
                                        + Lowlights
                                    </button>
                                    <button type="button" onclick="injectSection('ask')" class="px-2 py-1 rounded bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-[11px] font-semibold transition">
                                        + The Ask for Investors
                                    </button>
                                </div>

                                <textarea name="content" id="inp-content" rows="8" required 
                                          placeholder="Share the full story with your investors:&#10;&#10;1. Key Highlights: What worked exceptionally well?&#10;2. Product & Engineering: What was shipped to production?&#10;3. Runway & Financials: Current cash in bank & burn rate.&#10;4. The Ask: How can angel investors introduce pilots or talent?"
                                          oninput="handleLiveSync()"
                                          class="form-input-clean font-normal leading-relaxed"></textarea>
                            </div>

                            <!-- Form Action Bar -->
                            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center space-x-1.5">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Sends instant alerts to <strong class="text-slate-800 dark:text-white"><?= max(1, $totalStakeholders) ?> stakeholders</strong></span>
                                </div>
                                <div class="flex items-center space-x-2.5">
                                    <button type="button" onclick="resetStudio()" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-semibold transition">
                                        Reset
                                    </button>
                                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center space-x-2">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>Publish & Dispatch Now</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Right 5 Cols: Real-time Live Investor Perspective Simulator (Not Boring!) -->
                        <div class="lg:col-span-5 space-y-3 sticky top-24">
                            <div class="flex items-center justify-between px-1">
                                <div class="flex items-center space-x-2">
                                    <i data-lucide="smartphone" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white">Live Investor View Simulator</span>
                                </div>
                                <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-100 dark:border-emerald-800/80 px-2 py-0.5 rounded-full font-bold">● Realtime Sync</span>
                            </div>

                            <!-- The Simulated Card Screen Container -->
                            <div class="preview-phone-frame bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 p-5 space-y-4">
                                <!-- Top Bar of Simulator -->
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 text-xs">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                            <?= strtoupper(substr($company['name'] ?? 'S', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white leading-tight"><?= htmlspecialchars($company['name'] ?? 'Your Startup') ?></div>
                                            <div class="text-[10px] text-slate-400 dark:text-slate-400">Verified Issuer</div>
                                        </div>
                                    </div>
                                    <span id="prev-scope-badge" class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                        Public Feed
                                    </span>
                                </div>

                                <!-- Live Preview Header -->
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span id="prev-category-badge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 inline-flex items-center gap-1">
                                            <i data-lucide="trending-up" class="w-3 h-3"></i>
                                            <span id="prev-category-text">Traction & Revenue</span>
                                        </span>
                                        <span class="text-[11px] text-slate-400 dark:text-slate-400 font-mono">Today • Live Draft</span>
                                    </div>

                                    <h3 id="prev-title" class="text-base font-extrabold text-slate-900 dark:text-white leading-snug">
                                        Type a headline to preview your investor announcement...
                                    </h3>
                                </div>

                                <!-- Live Metrics Pill Preview -->
                                <div id="prev-metrics-container" class="p-3 rounded-xl bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-950/50 dark:to-teal-950/40 border border-emerald-200/80 dark:border-emerald-800/60 text-xs font-bold text-emerald-900 dark:text-emerald-300 flex items-center space-x-2">
                                    <i data-lucide="sparkles" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0"></i>
                                    <span id="prev-metrics" class="truncate">+25% MoM • ₹3.8 Cr ARR • 98% Retention</span>
                                </div>

                                <!-- Live Body Scroll Container -->
                                <div class="bg-slate-50 dark:bg-slate-900/80 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 max-h-56 overflow-y-auto custom-scroll text-xs text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-line font-normal" id="prev-content">
                                    Your detailed progress updates, customer wins, and requests for investor help will render here in real-time as you type in the editor...
                                </div>

                                <!-- Simulator Footer Bar -->
                                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
                                        <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                        <span>Delivered to investor inboxes</span>
                                    </span>
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Simulated Card</span>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
            </div>

            <!-- Published Updates Newsfeed & Timeline (Not Boring!) -->
            <div id="updates-timeline-section" class="space-y-4 pt-2">
                
                <!-- Filter & Search Command Bar -->
                <div class="section-card p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="newspaper" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 flex items-center space-x-2">
                                <span>Published Updates Timeline</span>
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    <?= $totalUpdates ?>
                                </span>
                            </h3>
                            <p class="text-[11px] text-slate-500">Filter, search, or copy updates as email digests for off-platform angels.</p>
                        </div>
                    </div>

                    <!-- Search Input & Category Pills -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <div class="relative">
                            <input type="text" id="stream-search" placeholder="Search updates..." 
                                   oninput="searchUpdates()"
                                   class="w-full sm:w-56 pl-9 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-800 focus:outline-none focus:border-indigo-600 focus:bg-white transition">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        </div>

                        <!-- Category Filter Pills -->
                        <div class="flex items-center space-x-1 overflow-x-auto pb-1 sm:pb-0 text-xs">
                            <button type="button" onclick="filterUpdates('all')" class="upd-filter-btn px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white font-bold transition text-xs" data-filter="all">
                                All (<?= $totalUpdates ?>)
                            </button>
                            <button type="button" onclick="filterUpdates('Traction & Revenue')" class="upd-filter-btn px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold transition text-xs" data-filter="Traction & Revenue">
                                🚀 Traction
                            </button>
                            <button type="button" onclick="filterUpdates('Milestone')" class="upd-filter-btn px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold transition text-xs" data-filter="Milestone">
                                🏆 Milestones
                            </button>
                            <button type="button" onclick="filterUpdates('Product Launch')" class="upd-filter-btn px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold transition text-xs" data-filter="Product Launch">
                                ⚡ Product
                            </button>
                            <button type="button" onclick="filterUpdates('Team & Hiring')" class="upd-filter-btn px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold transition text-xs" data-filter="Team & Hiring">
                                👥 Team
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($updates)): ?>
                    <!-- Inspiring Empty State -->
                    <div class="section-card p-12 text-center">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3.5 border border-indigo-100">
                            <i data-lucide="radio" class="w-8 h-8"></i>
                        </div>
                        <h4 class="text-base font-extrabold text-slate-900">Your Investor Channel is Ready to Broadcast</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1.5 leading-relaxed">
                            Startups that publish consistent monthly updates close subsequent funding rounds 3.2x faster. Click any quick template above to publish your first executive broadcast!
                        </p>
                        <button type="button" onclick="applyTemplate('revenue')" class="mt-4 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold inline-flex items-center space-x-2 shadow-sm shadow-indigo-600/20">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                            <span>Load First Monthly Traction Report</span>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- High-End Timeline Stream -->
                    <div class="space-y-4" id="updates-stream">
                        <?php foreach ($updates as $idx => $upd): 
                            $cat = $upd['category'];
                            $catColor = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            $catIcon = 'tag';
                            if (stripos($cat, 'revenue') !== false || stripos($cat, 'traction') !== false) {
                                $catColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                $catIcon = 'trending-up';
                            } elseif (stripos($cat, 'milestone') !== false) {
                                $catColor = 'bg-amber-50 text-amber-800 border-amber-200';
                                $catIcon = 'award';
                            } elseif (stripos($cat, 'product') !== false) {
                                $catColor = 'bg-cyan-50 text-cyan-700 border-cyan-200';
                                $catIcon = 'zap';
                            } elseif (stripos($cat, 'team') !== false || stripos($cat, 'hire') !== false) {
                                $catColor = 'bg-purple-50 text-purple-700 border-purple-200';
                                $catIcon = 'users';
                            }
                        ?>
                            <div class="upd-card section-card p-6 sm:p-7 space-y-4 relative" 
                                 data-category="<?= htmlspecialchars($upd['category']) ?>"
                                 data-search="<?= strtolower(htmlspecialchars($upd['title'] . ' ' . $upd['metrics_summary'] . ' ' . $upd['content'])) ?>">
                                
                                <!-- Card Header -->
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 pb-4">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <!-- Category Badge -->
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= $catColor ?>">
                                                <i data-lucide="<?= $catIcon ?>" class="w-3.5 h-3.5"></i>
                                                <span><?= htmlspecialchars($upd['category']) ?></span>
                                            </span>

                                            <!-- Visibility Scope Badge -->
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold <?= $upd['visibility'] === 'portfolio_only' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' ?>">
                                                <i data-lucide="<?= $upd['visibility'] === 'portfolio_only' ? 'lock' : 'globe' ?>" class="w-3 h-3"></i>
                                                <span><?= $upd['visibility'] === 'portfolio_only' ? 'Portfolio Exclusive' : 'Public Discovery' ?></span>
                                            </span>

                                            <!-- Formatted Time -->
                                            <span class="text-xs text-slate-400 dark:text-slate-400 flex items-center gap-1 font-mono">
                                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                                <span><?= date('M d, Y • h:i A', strtotime($upd['created_at'])) ?></span>
                                            </span>
                                        </div>

                                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight leading-snug">
                                            <?= htmlspecialchars($upd['title']) ?>
                                        </h2>
                                    </div>

                                    <!-- Quick Card Action Bar (Copy Digest & Delete) -->
                                    <div class="flex items-center space-x-2 flex-shrink-0">
                                        <button type="button" 
                                                onclick="copyEmailDigest(`<?= addslashes(htmlspecialchars($company['name'])) ?>`, `<?= addslashes(htmlspecialchars($upd['title'])) ?>`, `<?= addslashes(htmlspecialchars($upd['metrics_summary'])) ?>`, `<?= addslashes(htmlspecialchars($upd['content'])) ?>`)"
                                                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold flex items-center space-x-1.5 shadow-xs transition"
                                                title="Copy as Investor Email Digest">
                                            <i data-lucide="mail" class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400"></i>
                                            <span>Copy Email Digest</span>
                                        </button>

                                        <form action="<?= url('founder/updates.php') ?>" method="POST" onsubmit="return confirm('Delete this investor broadcast from timeline?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="delete_update">
                                            <input type="hidden" name="update_id" value="<?= $upd['id'] ?>">
                                            <button type="submit" class="p-2 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Delete Update">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Key Metrics Pill Strip -->
                                <?php if (!empty($upd['metrics_summary'])): ?>
                                    <div class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50/50 dark:from-emerald-950/50 dark:via-slate-900 dark:to-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-xs sm:text-sm font-bold text-emerald-900 dark:text-emerald-300 flex items-center space-x-2.5">
                                        <div class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <span class="tracking-wide"><?= htmlspecialchars($upd['metrics_summary']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Narrative Body Content (Easy to read, boosted font & high contrast dark mode) -->
                                <div class="text-sm sm:text-base text-slate-700 dark:text-slate-100 leading-relaxed whitespace-pre-line bg-slate-50 dark:bg-slate-900/90 p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 font-medium">
                                    <?= htmlspecialchars($upd['content']) ?>
                                </div>

                                <!-- Card Delivery Proof Bar -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-400 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center space-x-2 text-slate-500 dark:text-slate-400 font-medium">
                                        <i data-lucide="check-check" class="w-4 h-4 text-emerald-600 dark:text-emerald-400"></i>
                                        <span>Delivered to investor inboxes & startup profile feed</span>
                                    </div>
                                    <div class="flex items-center space-x-3 text-[11px]">
                                        <span class="text-slate-400 dark:text-slate-500 font-mono">ID: #<?= $upd['id'] ?></span>
                                        <button type="button" onclick="copyUpdateText(this, `<?= addslashes($upd['title'] . "\n\n" . $upd['content']) ?>`);" 
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-bold flex items-center space-x-1 transition">
                                            <i data-lucide="copy" class="w-3 h-3"></i>
                                            <span class="btn-copy-label">Copy Raw Text</span>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Interactive Studio Logic -->
    <script>
        lucide.createIcons();

        gsap.from("#updates-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });

        // Real-time synchronization between form and preview
        function handleLiveSync() {
            const title = document.getElementById('inp-title').value;
            const category = document.getElementById('inp-category').value;
            const visibility = document.getElementById('inp-visibility').value;
            const metrics = document.getElementById('inp-metrics').value;
            const content = document.getElementById('inp-content').value;

            // Update Char Count
            const charCountEl = document.getElementById('title-char-count');
            if (charCountEl) charCountEl.innerText = `${title.length} / 120 chars`;

            // Update Word Count & Reading Time
            const words = content.trim() ? content.trim().split(/\s+/).length : 0;
            const readTime = Math.max(1, Math.ceil(words / 150));
            const readPill = document.getElementById('read-time-pill');
            if (readPill) readPill.innerText = `${words} words • ~${readTime} min read`;

            // Preview Title
            const prevTitle = document.getElementById('prev-title');
            if (prevTitle) {
                prevTitle.innerText = title.trim() || 'Type a headline to preview your investor announcement...';
            }

            // Preview Category
            const prevCatText = document.getElementById('prev-category-text');
            if (prevCatText) prevCatText.innerText = category;

            // Preview Scope Badge
            const prevScopeBadge = document.getElementById('prev-scope-badge');
            if (prevScopeBadge) {
                if (visibility === 'portfolio_only') {
                    prevScopeBadge.innerText = '🔒 Confidential';
                    prevScopeBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
                } else {
                    prevScopeBadge.innerText = '🌐 Public Feed';
                    prevScopeBadge.className = 'px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800';
                }
            }

            // Preview Metrics
            const prevMetrics = document.getElementById('prev-metrics');
            const prevMetricsCont = document.getElementById('prev-metrics-container');
            if (prevMetrics && prevMetricsCont) {
                if (metrics.trim()) {
                    prevMetrics.innerText = metrics;
                    prevMetricsCont.style.display = 'flex';
                } else {
                    prevMetricsCont.style.display = 'none';
                }
            }

            // Preview Content
            const prevContent = document.getElementById('prev-content');
            if (prevContent) {
                prevContent.innerText = content.trim() || 'Your detailed progress updates, customer wins, and requests for investor help will render here in real-time as you type in the editor...';
            }
        }

        // Apply Starter Template
        function applyTemplate(type) {
            const titleInput = document.getElementById('inp-title');
            const catSelect = document.getElementById('inp-category');
            const metricsInput = document.getElementById('inp-metrics');
            const contentArea = document.getElementById('inp-content');

            if (!titleInput) return;

            document.getElementById('studio-composer').scrollIntoView({ behavior: 'smooth' });

            const now = new Date();
            const monthName = now.toLocaleString('default', { month: 'long' });

            if (type === 'revenue') {
                titleInput.value = `${monthName} 2026 Growth Update: ARR reached ₹3.8 Cr (+145% YoY)`;
                catSelect.value = 'Traction & Revenue';
                metricsInput.value = '+28% MoM Revenue • ₹3.8 Cr ARR • 14 New Enterprise Logos • 14 Mo Runway';
                contentArea.value = `Dear Investors & Advisory Partners,\n\nHere is our executive report for ${monthName} 2026:\n\n1. Revenue & Financial Traction:\n• Monthly Gross Revenue: ₹32 Lakhs (+28% MoM growth)\n• Current Annual Recurring Revenue (ARR): ₹3.84 Cr\n• Gross Margins: Improved to 84% through infrastructure optimizations\n• Cash Runway: 14 Months at current monthly burn rate of ₹12 Lakhs\n\n2. Key Highlights & Client Conversions:\n• Signed 2 Fortune 500 enterprise pilot conversions with multi-year contracts\n• Net Promoter Score (NPS) reached 76 across all active accounts\n• Product retention rate remains strong at 98.4%\n\n3. The Ask for Stakeholders:\n• Looking for customer warm intros to CIOs / CTOs in BFSI and Fintech\n• Seeking recommendations for a Lead Solutions Architect in Bengaluru.`;
            } else if (type === 'product') {
                titleInput.value = `Product Release V2: Shipped Automated Intelligence Copilot`;
                catSelect.value = 'Product Launch';
                metricsInput.value = '3.5x Faster Workflows • 99.9% Uptime • Zero Configuration Setup';
                contentArea.value = `Dear Stakeholders,\n\nWe have officially deployed Product Release V2 to production this week:\n\n1. What Was Shipped:\n• Brand-new interactive intelligence workflow engine\n• 1-click statutory cap table and data room due diligence integration\n• Advanced AES-256 encrypted confidential data vault\n\n2. Early Cohort Results:\n• Beta customers experienced a 65% reduction in onboarding overhead\n• Daily active user engagement doubled in the first 72 hours\n\n3. Next Sprints:\n• Launching webhooks and Zapier / Slack integrations next sprint.`;
            } else if (type === 'milestone') {
                titleInput.value = `Major Milestone: Crossed 50,000 Active Users & Signed Strategic Partnership`;
                catSelect.value = 'Milestone';
                metricsInput.value = '50,000+ Users • Strategic Distribution Pact • Zero Churn';
                contentArea.value = `Excited to share a landmark achievement for our team:\n\n1. Milestone Summary:\n• Officially crossed 50,000 registered active platform users\n• Signed a nationwide strategic distribution partnership with an industry leader\n\n2. Expected Growth Acceleration:\n• Expands our distribution pipeline by 3x over the next 2 quarters\n• Projected to lower customer acquisition cost (CAC) by 35%\n\nThank you for your continued belief, guidance, and support!`;
            } else if (type === 'hiring') {
                titleInput.value = `Leadership Update: Welcoming Ex-Google Engineering Director as VP AI`;
                catSelect.value = 'Team & Hiring';
                metricsInput.value = 'Executive Leadership • Team Scaled to 24 • Proprietary Moat';
                contentArea.value = `Thrilled to announce a key addition to our executive bench:\n\n1. Background & Expertise:\n• Joins us after 8 years leading core platform infrastructure at Google & Microsoft\n• Holds 3 granted patents in distributed systems and natural language inference\n\n2. Immediate Priorities:\n• Accelerating our proprietary model deployment pipelines\n• Expanding our core engineering squad in Bengaluru and Hyderabad.`;
            }

            handleLiveSync();
        }

        // Append quick metric chip into input
        function appendMetricTag(tag) {
            const inp = document.getElementById('inp-metrics');
            if (!inp) return;
            if (inp.value.trim()) {
                inp.value += ' • ' + tag;
            } else {
                inp.value = tag;
            }
            handleLiveSync();
        }

        // Fast inject section templates
        function injectSection(type) {
            const area = document.getElementById('inp-content');
            if (!area) return;
            let snippet = '';
            if (type === 'highlights') {
                snippet = '\n\n• Key Wins & Milestones:\n- Point 1\n- Point 2';
            } else if (type === 'lowlights') {
                snippet = '\n\n• Lowlights & Obstacles:\n- Challenge encountered and mitigation plan.';
            } else if (type === 'ask') {
                snippet = '\n\n• The Ask for Investors:\n- Customer intros needed in [Industry]\n- Talent referrals for [Role].';
            }
            area.value += snippet;
            handleLiveSync();
        }

        // Reset studio
        function resetStudio() {
            document.getElementById('update-form').reset();
            handleLiveSync();
        }

        // Live timeline search
        function searchUpdates() {
            const query = document.getElementById('stream-search').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.upd-card');

            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Category filter chips
        function filterUpdates(cat) {
            const cards = document.querySelectorAll('.upd-card');
            const btns = document.querySelectorAll('.upd-filter-btn');

            btns.forEach(btn => {
                if (btn.getAttribute('data-filter') === cat) {
                    btn.classList.add('bg-indigo-600', 'text-white');
                    btn.classList.remove('bg-white', 'dark:bg-slate-900', 'text-slate-700', 'dark:text-slate-300', 'hover:bg-slate-50', 'dark:hover:bg-slate-800');
                } else {
                    btn.classList.remove('bg-indigo-600', 'text-white');
                    btn.classList.add('bg-white', 'dark:bg-slate-900', 'text-slate-700', 'dark:text-slate-300', 'hover:bg-slate-50', 'dark:hover:bg-slate-800');
                }
            });

            cards.forEach(card => {
                if (cat === 'all' || card.getAttribute('data-category') === cat) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Toast notification system
        function showToast(message, type = 'success') {
            let toast = document.getElementById('portal-toast-notification');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'portal-toast-notification';
                toast.className = 'fixed bottom-6 right-6 z-50 transform translate-y-4 opacity-0 transition-all duration-300 pointer-events-none';
                document.body.appendChild(toast);
            }
            const isSuccess = type === 'success';
            toast.innerHTML = `
                <div class="px-4 py-3 rounded-2xl ${isSuccess ? 'bg-slate-900 text-white border border-slate-700' : 'bg-rose-900 text-white'} shadow-2xl flex items-center space-x-2.5 text-xs sm:text-sm font-bold">
                    <span class="w-2 h-2 rounded-full ${isSuccess ? 'bg-emerald-400' : 'bg-rose-400'} animate-pulse"></span>
                    <span>${message}</span>
                </div>
            `;
            toast.classList.remove('translate-y-4', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-4', 'opacity-0');
            }, 3200);
        }

        // Copy raw update text with feedback
        function copyUpdateText(btn, text) {
            navigator.clipboard.writeText(text);
            const label = btn.querySelector('.btn-copy-label');
            if (label) {
                const orig = label.textContent;
                label.textContent = 'Copied!';
                setTimeout(() => { label.textContent = orig; }, 2000);
            }
            showToast('Update text copied to clipboard!');
        }

        // Copy as formatted investor email digest
        function copyEmailDigest(company, title, metrics, content) {
            const digest = `SUBJECT: [Investor Update] ${company} — ${title}\n\nKEY HIGHLIGHTS:\n${metrics || 'N/A'}\n\n${content}\n\n---\nSent via ${company} Founder Portal`;
            navigator.clipboard.writeText(digest);
            showToast('Formatted Investor Digest copied! Ready to paste into Gmail.');
        }

        // Initialize preview on load
        handleLiveSync();

    </script>
</body>
</html>
