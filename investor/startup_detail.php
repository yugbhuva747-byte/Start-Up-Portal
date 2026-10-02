<?php
/**
 * Investor Module: Startup Deep-Dive & Diligence Deal Room
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Startup Deal Room';

$hashId = $_GET['id'] ?? '';
$companyId = hash_id_decode($hashId);

if ($companyId === 0) {
    header('Location: ' . url('investor/discover.php'));
    exit;
}

$company = null;
$founders = [];
$activeRound = null;
$documents = [];
$isSaved = false;

if ($db) {
    // 1. Fetch Company
    $cStmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
    $cStmt->execute([$companyId]);
    $company = $cStmt->fetch();

    if (!$company) {
        header('Location: ' . url('investor/discover.php'));
        exit;
    }

    // 2. Fetch Founders
    $fStmt = $db->prepare("
        SELECT u.*, u.id as user_id, cf.designation, cf.equity_percent, fp.bio, fp.linkedin_url
        FROM company_founders cf
        JOIN users u ON cf.user_id = u.id
        LEFT JOIN founder_profiles fp ON u.id = fp.user_id
        WHERE cf.company_id = ?
    ");
    $fStmt->execute([$companyId]);
    $founders = $fStmt->fetchAll();

    // 3. Fetch Active Funding Round
    $rStmt = $db->prepare("SELECT * FROM funding_rounds WHERE company_id = ? AND status IN ('LIVE', 'PARTIALLY_FUNDED', 'APPROVED') ORDER BY created_at DESC LIMIT 1");
    $rStmt->execute([$companyId]);
    $activeRound = $rStmt->fetch();

    // 4. Fetch Documents
    $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
    $dStmt->execute([$companyId]);
    $documents = $dStmt->fetchAll();

    // 5. Check Watchlist
    $wStmt = $db->prepare("SELECT id FROM watchlists WHERE investor_user_id = ? AND company_id = ?");
    $wStmt->execute([$user['id'], $companyId]);
    $isSaved = (bool) $wStmt->fetch();

    // 6. Fetch Founder Updates & Milestones
    $uStmt = $db->prepare("SELECT * FROM startup_updates WHERE company_id = ? ORDER BY created_at DESC");
    $uStmt->execute([$companyId]);
    $updates = $uStmt->fetchAll();

    // 7. Fetch Company Blogs & Trust Stories
    $bStmt = $db->prepare("SELECT * FROM company_blogs WHERE company_id = ? AND is_published = 1 ORDER BY published_at DESC");
    $bStmt->execute([$companyId]);
    $companyBlogs = $bStmt->fetchAll();
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company['name']) ?> • Deal Room • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
    <style>
        body {
            font-family: "Vay Portal", Sans-serif;
        }

        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>

<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="deal-room-main">

            <?php if ($flash): ?>
                <div
                    class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300 border-[#123B7A]/20' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200' ?> flex items-center space-x-2.5 shadow-2xs">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Navigation Breadcrumb -->
            <div class="flex items-center space-x-2 text-xs text-[#667085]">
                <a href="<?= url('investor/discover.php') ?>"
                    class="hover:text-[#123B7A] transition flex items-center space-x-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Deal Discovery</span>
                </a>
                <span>/</span>
                <span class="text-[#111827] font-bold"><?= htmlspecialchars($company['name']) ?></span>
            </div>

            <!-- ==========================================
                 TOP: STARTUP PROFILE HEADER
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] pb-8">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                    <div class="flex items-start space-x-5">
                        <img src="<?= $company['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=200' ?>"
                            class="w-20 h-20 rounded-2xl object-cover border border-[#E4E8EF] flex-shrink-0">
                        <div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <h1 class="text-lg sm:text-xl font-bold text-[#0B1F3A] dark:text-white tracking-tight">
                                    <?= htmlspecialchars($company['name']) ?>
                                </h1>
                                <?= render_status_badge($company['verified_status']) ?>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-[#667085] mt-1.5">
                                <span
                                    class="font-bold text-[#123B7A]"><?= htmlspecialchars($company['industry']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($company['stage']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($company['city']) ?>,
                                    <?= htmlspecialchars($company['country']) ?></span>
                            </div>

                            <p class="text-xs sm:text-sm text-[#111827] mt-3 max-w-2xl font-medium leading-relaxed">
                                <?= htmlspecialchars($company['pitch']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Primary Actions -->
                    <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                        <?php if (!empty($founders)): ?>
                            <a href="<?= url('investor/messages.php?founder=' . hash_id_encode($founders[0]['id']) . '&company=' . $hashId) ?>"
                                class="px-4 py-2.5 rounded-lg bg-white hover:bg-[#FAFBFD] border border-[#E4E8EF] text-[#111827] text-xs font-bold transition flex items-center space-x-2">
                                <i data-lucide="message-circle" class="w-4 h-4 text-[#667085]"></i>
                                <span>Message Founder</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($activeRound): ?>
                            <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>"
                                class="px-5 py-2.5 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center space-x-2 shadow-sm">
                                <i data-lucide="zap" class="w-4 h-4"></i>
                                <span>Participate / Invest</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- ==========================================
                 SECTION: ABOUT & MARKET OPPORTUNITY
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] pb-8 space-y-3">
                <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                    About Company & Opportunity
                </div>
                <div class="text-xs sm:text-sm text-[#111827] leading-relaxed whitespace-pre-line max-w-4xl">
                    <?= nl2br(htmlspecialchars($company['description'])) ?>
                </div>
            </section>

            <!-- ==========================================
                 SECTION: COMPANY INFORMATION (Horizontal Rows)
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] pb-8 space-y-4">
                <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                    Company Information
                </div>

                <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF] text-xs">
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-[#667085] font-medium sm:w-1/3">Corporate CIN / Registry</span>
                        <span
                            class="font-mono font-bold text-[#111827]"><?= htmlspecialchars($company['cin_number'] ?? 'Verified MCA Entity') ?></span>
                    </div>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-[#667085] font-medium sm:w-1/3">Business Model</span>
                        <span
                            class="font-semibold text-[#111827]"><?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS / Enterprise') ?></span>
                    </div>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-[#667085] font-medium sm:w-1/3">Team Size</span>
                        <span
                            class="font-semibold text-[#111827]"><?= htmlspecialchars($company['employee_count'] ?? '10') ?>
                            Full-time Members</span>
                    </div>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-[#667085] font-medium sm:w-1/3">Headquarters Location</span>
                        <span class="font-semibold text-[#111827]"><?= htmlspecialchars($company['city']) ?>,
                            <?= htmlspecialchars($company['state'] ?? '') ?>
                            <?= htmlspecialchars($company['country']) ?></span>
                    </div>
                    <?php 
                        $compWebsite = !empty($company['website']) ? $company['website'] : ($company['website_url'] ?? '');
                        if (!empty($compWebsite)): 
                    ?>
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <span class="text-[#667085] font-medium sm:w-1/3">Official Website</span>
                            <a href="<?= htmlspecialchars($compWebsite) ?>" target="_blank"
                                class="text-[#123B7A] font-bold hover:underline flex items-center gap-1">
                                <span><?= htmlspecialchars($compWebsite) ?></span>
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- ==========================================
                 SECTION: ACTIVE FUNDING ROUND & SYNDICATE TERMS
                 ========================================== -->
            <?php if ($activeRound):
                $pct = $activeRound['target_amount'] > 0 ? round(($activeRound['amount_raised'] / $activeRound['target_amount']) * 100) : 0;
                $remaining = max(0, $activeRound['target_amount'] - $activeRound['amount_raised']);
                ?>
                <section class="border-b border-[#E4E8EF] pb-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">Active Funding Round
                            </div>
                            <h2 class="text-lg font-bold text-[#0B1F3A]"><?= htmlspecialchars($activeRound['round_name']) ?>
                            </h2>
                        </div>
                        <?= render_status_badge($activeRound['status']) ?>
                    </div>

                    <div class="bg-white border border-[#E4E8EF] rounded-xl p-6 sm:p-8 space-y-6">
                        <div>
                            <div class="flex justify-between text-xs mb-2 font-bold">
                                <span class="text-[#667085]">Raised <?= format_inr($activeRound['amount_raised']) ?> of
                                    <?= format_inr($activeRound['target_amount']) ?></span>
                                <span class="text-[#123B7A]"><?= $pct ?>% Committed</span>
                            </div>
                            <div class="w-full h-2.5 bg-[#E4E8EF] rounded-full overflow-hidden">
                                <div class="h-full bg-[#123B7A] rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-4 border-t border-[#E4E8EF] text-xs">
                            <div>
                                <span
                                    class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Pre-Money
                                    Valuation</span>
                                <span
                                    class="font-extrabold text-[#0B1F3A] text-sm"><?= format_inr($activeRound['valuation']) ?></span>
                            </div>
                            <div>
                                <span
                                    class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Equity
                                    Offered</span>
                                <span
                                    class="font-extrabold text-[#123B7A] text-sm"><?= $activeRound['equity_offered'] ?>%</span>
                            </div>
                            <div>
                                <span
                                    class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Min.
                                    Check Size</span>
                                <span
                                    class="font-extrabold text-[#0B1F3A] text-sm"><?= format_inr($activeRound['min_investment']) ?></span>
                            </div>
                            <div>
                                <span
                                    class="text-[10.5px] font-bold text-[#667085] uppercase tracking-wider block mb-1">Remaining
                                    Open Gap</span>
                                <span class="font-extrabold text-[#0B1F3A] text-sm"><?= format_inr($remaining) ?></span>
                            </div>
                        </div>

                        <div
                            class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-[#E4E8EF]">
                            <div class="text-xs text-[#667085] flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-[#123B7A]"></i>
                                <span>All commitments escrowed under SEBI Angel Network regulations.</span>
                            </div>
                            <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>"
                                class="w-full sm:w-auto px-6 py-3 bg-[#123B7A] hover:bg-[#0B1F3A] text-white font-bold text-xs rounded-lg transition shadow-sm text-center">
                                Commit Capital to Round →
                            </a>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ==========================================
                 SECTION: LEADERSHIP & FOUNDING TEAM
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] pb-8 space-y-4">
                <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                    Leadership & Founding Team (<?= count($founders) ?>)
                </div>

                <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF]">
                    <?php foreach ($founders as $f):
                        $fId = $f['user_id'] ?? $f['id'] ?? 0;
                        ?>
                        <div
                            class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-[#FAFBFD] transition">
                            <div class="flex items-start space-x-4 min-w-0">
                                <a href="<?= url('founder/view.php?id=' . encode_id($fId)) ?>">
                                    <img src="<?= $f['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' ?>"
                                        class="w-12 h-12 rounded-full object-cover border border-[#E4E8EF] flex-shrink-0">
                                </a>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= url('founder/view.php?id=' . encode_id($fId)) ?>"
                                            class="text-sm font-bold text-[#0B1F3A] hover:text-[#123B7A] transition truncate">
                                            <?= htmlspecialchars($f['name']) ?>
                                        </a>
                                        <span
                                            class="text-[10px] font-semibold text-[#123B7A] px-2 py-0.5 rounded bg-[#EAF2FF]">
                                            <?= htmlspecialchars($f['designation'] ?? 'Founder & CEO') ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#667085] mt-1 leading-relaxed max-w-xl">
                                        <?= htmlspecialchars($f['bio'] ?? 'Experienced technology operator.') ?>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3 text-xs flex-shrink-0">
                                <a href="<?= url('founder/view.php?id=' . encode_id($fId)) ?>"
                                    class="text-xs font-bold text-[#123B7A] hover:underline">
                                    View Full Profile
                                </a>
                                <?php if (!empty($f['linkedin_url'])): ?>
                                    <span class="text-[#E4E8EF]">•</span>
                                    <a href="<?= htmlspecialchars($f['linkedin_url']) ?>" target="_blank"
                                        class="text-xs font-bold text-[#667085] hover:text-[#111827] flex items-center gap-1">
                                        <span>LinkedIn</span>
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ==========================================
                 SECTION: TRACTION UPDATES & MILESTONES
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] pb-8 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                        Traction & Milestone History (<?= count($updates) ?>)
                    </div>
                </div>

                <?php if (empty($updates)): ?>
                    <div class="py-6 text-center text-xs text-[#667085] bg-white border border-[#E4E8EF] rounded-xl">
                        No public traction milestones published yet.
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF]">
                        <?php foreach ($updates as $upd): ?>
                            <div class="p-4 sm:p-5 space-y-2 hover:bg-[#FAFBFD] transition text-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#EAF2FF] text-[#123B7A]">
                                            <?= htmlspecialchars($upd['category']) ?>
                                        </span>
                                        <h3 class="font-bold text-[#0B1F3A] text-sm"><?= htmlspecialchars($upd['title']) ?></h3>
                                    </div>
                                    <span class="text-[11px] text-[#667085]">
                                        <?= date('M d, Y', strtotime($upd['created_at'])) ?>
                                    </span>
                                </div>

                                <?php if (!empty($upd['metrics_summary'])): ?>
                                    <div class="text-xs font-bold text-[#123B7A] flex items-center space-x-1.5">
                                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                                        <span><?= htmlspecialchars($upd['metrics_summary']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <p class="text-[#667085] leading-relaxed whitespace-pre-line max-w-3xl">
                                    <?= htmlspecialchars($upd['content']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ==========================================
                 SECTION: CONFIDENTIAL DATA ROOM & FILES
                 ========================================== -->
            <section class="border-b border-[#E4E8EF] pb-8 space-y-4">
                <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                    Confidential Due Diligence Room
                </div>

                <?php if (empty($documents)): ?>
                    <div class="py-6 text-center text-xs text-[#667085] bg-white border border-[#E4E8EF] rounded-xl">
                        No supplementary diligence files uploaded. You may request documents via direct founder message.
                    </div>
                <?php else: ?>
                    <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF]">
                        <?php foreach ($documents as $doc): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-[#FAFBFD] transition text-xs">
                                <div class="flex items-center space-x-3.5 min-w-0">
                                    <div class="p-2.5 rounded-lg bg-[#EAF2FF] text-[#123B7A]">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-[#0B1F3A] truncate"><?= htmlspecialchars($doc['title']) ?>
                                        </div>
                                        <div class="text-[10.5px] text-[#667085]">
                                            <?= htmlspecialchars($doc['document_type']) ?> •
                                            <?= htmlspecialchars($doc['file_size']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 flex-shrink-0">
                                    <a href="<?= url($doc['file_path']) ?>" target="_blank"
                                        class="px-3 py-1.5 rounded-lg border border-[#E4E8EF] bg-white hover:bg-[#FAFBFD] text-[#111827] font-semibold flex items-center space-x-1.5 transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>View</span>
                                    </a>
                                    <a href="<?= url('download.php?id=' . $doc['id'] . '&type=company') ?>"
                                        class="px-3 py-1.5 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] text-white font-semibold flex items-center space-x-1.5 transition">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span>Download</span>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ==========================================
                 SECTION: STORIES & BLOGS
                 ========================================== -->
            <?php if (!empty($companyBlogs)): ?>
                <section class="space-y-4">
                    <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                        Company Stories & Media
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <?php foreach ($companyBlogs as $cb):
                            $cbCover = str_starts_with($cb['cover_image'], 'http') ? $cb['cover_image'] : url($cb['cover_image']);
                            $cbUrl = url('blog.php?id=' . $cb['id']);
                            ?>
                            <div
                                class="bg-white border border-[#E4E8EF] rounded-xl overflow-hidden hover:border-[#123B7A]/40 transition flex flex-col group">
                                <div class="h-40 overflow-hidden bg-slate-100 relative">
                                    <img src="<?= htmlspecialchars($cbCover) ?>" alt="<?= htmlspecialchars($cb['title']) ?>"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    <span
                                        class="absolute top-3 left-3 px-2 py-0.5 rounded text-[10px] font-bold bg-white text-[#123B7A] shadow-sm">
                                        <?= htmlspecialchars($cb['category']) ?>
                                    </span>
                                </div>
                                <div class="p-5 flex-1 flex flex-col justify-between text-xs">
                                    <div>
                                        <div class="text-[10px] text-[#667085] mb-1">
                                            <?= date('M d, Y', strtotime($cb['published_at'])) ?> •
                                            <?= $cb['read_time_minutes'] ?> min read
                                        </div>
                                        <h3
                                            class="font-bold text-[#0B1F3A] text-sm group-hover:text-[#123B7A] transition leading-snug line-clamp-2">
                                            <?= htmlspecialchars($cb['title']) ?>
                                        </h3>
                                        <p class="text-[#667085] mt-1.5 line-clamp-2 leading-relaxed">
                                            <?= htmlspecialchars($cb['summary']) ?>
                                        </p>
                                    </div>
                                    <div class="pt-4 mt-4 border-t border-[#E4E8EF] flex items-center justify-between">
                                        <a href="<?= $cbUrl ?>" target="_blank"
                                            class="text-[#123B7A] font-bold hover:underline flex items-center gap-1">
                                            <span>Read Story</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#deal-room-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>

</html>