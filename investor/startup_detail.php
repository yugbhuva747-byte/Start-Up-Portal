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

    // 7.1 Fetch Company Awards & Trust Badges
    $aStmt = $db->prepare("SELECT * FROM company_awards WHERE company_id = ? ORDER BY is_featured DESC, created_at DESC");
    $aStmt->execute([$companyId]);
    $awards = $aStmt->fetchAll();

    // 8. Check Existing Investor Interest
    $existingInterest = null;
    $connectedConvId = 0;
    if ($user && $user['role'] === 'investor') {
        try {
            $iStmt = $db->prepare("SELECT * FROM investor_interests WHERE investor_id = ? AND company_id = ?");
            $iStmt->execute([$user['id'], $companyId]);
            $existingInterest = $iStmt->fetch();

            if ($existingInterest && $existingInterest['status'] === 'accepted') {
                $convStmt = $db->prepare("
                    SELECT id FROM conversations 
                    WHERE company_id = ? AND (user_one_id = ? OR user_two_id = ?)
                    ORDER BY last_message_at DESC LIMIT 1
                ");
                $convStmt->execute([$companyId, $user['id'], $user['id']]);
                $connectedConvId = (int)$convStmt->fetchColumn();
            }
        } catch (\Throwable $e) {
            // Self-heal table if missing
            $existingInterest = null;
            $connectedConvId = 0;
        }
    }

    // 7.2 Self-heal and handle Private Due Diligence Scorecards & Investor Notes
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `investor_diligence_notes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `investor_id` INT NOT NULL,
                `company_id` INT NOT NULL,
                `score_team` TINYINT NOT NULL DEFAULT 8,
                `score_market` TINYINT NOT NULL DEFAULT 8,
                `score_traction` TINYINT NOT NULL DEFAULT 7,
                `score_defensibility` TINYINT NOT NULL DEFAULT 7,
                `overall_score` DECIMAL(3,1) NOT NULL DEFAULT 7.5,
                `recommendation` ENUM('strong_buy', 'watch', 'pass', 'pending') NOT NULL DEFAULT 'watch',
                `private_notes` TEXT NULL DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_investor_company_diligence` (`investor_id`, `company_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (\Throwable $e) {}

    // Handle Saving Private Due Diligence Scorecard & Notes
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_diligence' && $companyId > 0) {
        if (verify_csrf($_POST['csrf_token'] ?? '')) {
            $scoreTeam = max(1, min(10, (int)($_POST['score_team'] ?? 8)));
            $scoreMarket = max(1, min(10, (int)($_POST['score_market'] ?? 8)));
            $scoreTraction = max(1, min(10, (int)($_POST['score_traction'] ?? 7)));
            $scoreDefensibility = max(1, min(10, (int)($_POST['score_defensibility'] ?? 7)));
            $overallScore = round(($scoreTeam + $scoreMarket + $scoreTraction + $scoreDefensibility) / 4, 1);
            
            $allowedRec = ['strong_buy', 'watch', 'pass', 'pending'];
            $recommendation = in_array($_POST['recommendation'] ?? '', $allowedRec, true) ? $_POST['recommendation'] : 'watch';
            $privateNotes = trim($_POST['private_notes'] ?? '');

            try {
                $upsert = $db->prepare("
                    INSERT INTO investor_diligence_notes 
                        (investor_id, company_id, score_team, score_market, score_traction, score_defensibility, overall_score, recommendation, private_notes, updated_at)
                    VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        score_team = VALUES(score_team),
                        score_market = VALUES(score_market),
                        score_traction = VALUES(score_traction),
                        score_defensibility = VALUES(score_defensibility),
                        overall_score = VALUES(overall_score),
                        recommendation = VALUES(recommendation),
                        private_notes = VALUES(private_notes),
                        updated_at = NOW()
                ");
                $upsert->execute([
                    $user['id'],
                    $companyId,
                    $scoreTeam,
                    $scoreMarket,
                    $scoreTraction,
                    $scoreDefensibility,
                    $overallScore,
                    $recommendation,
                    $privateNotes
                ]);

                log_audit($user['id'], 'SAVE_DILIGENCE_SCORECARD', 'companies', $companyId, "Saved diligence scorecard (Score: {$overallScore}/10, Rec: {$recommendation})");

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Due diligence notes and scorecard saved.',
                        'overall_score' => $overallScore,
                        'updated_at' => date('M d, Y h:i A')
                    ]);
                    exit;
                }

                set_flash('success', 'Private due diligence scorecard & notes saved.');
            } catch (\Throwable $e) {
                set_flash('danger', 'Failed to save due diligence: ' . $e->getMessage());
            }

            header('Location: ' . url('investor/startup_detail.php?id=' . $hashId . '#section-diligence-scorecard'));
            exit;
        }
    }

    $diligence = null;
    try {
        $dnStmt = $db->prepare("SELECT * FROM investor_diligence_notes WHERE investor_id = ? AND company_id = ? LIMIT 1");
        $dnStmt->execute([$user['id'], $companyId]);
        $diligence = $dnStmt->fetch();
    } catch (\Throwable $e) {}

    if (!$diligence) {
        $diligence = [
            'score_team' => 8,
            'score_market' => 8,
            'score_traction' => 7,
            'score_defensibility' => 7,
            'overall_score' => 7.5,
            'recommendation' => 'watch',
            'private_notes' => '',
            'updated_at' => null
        ];
    }
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
            font-family: 'Satoshi', "Vay Portal", sans-serif;
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
                        <?php if ($existingInterest): ?>
                            <?php if ($existingInterest['status'] === 'accepted'): ?>
                                <a href="<?= url('investor/messages.php?conv=' . hash_id_encode($connectedConvId)) ?>"
                                    class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center space-x-2 shadow-sm">
                                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    <span>Connected • Open Conversation →</span>
                                </a>
                            <?php elseif ($existingInterest['status'] === 'pending'): ?>
                                <div class="px-4 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center space-x-2">
                                    <i data-lucide="clock" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                                    <span>Interest Sent • Awaiting Founder Response</span>
                                </div>
                            <?php elseif ($existingInterest['status'] === 'declined'): ?>
                                <div class="px-4 py-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-xs font-semibold flex items-center space-x-2">
                                    <i data-lucide="slash" class="w-4 h-4 text-slate-400"></i>
                                    <span>Interest Declined</span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <button type="button" onclick="openExpressInterestModal()" id="btn-open-express-interest"
                                class="px-5 py-2.5 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center space-x-2 shadow-sm">
                                <i data-lucide="sparkles" class="w-4 h-4 text-amber-300"></i>
                                <span>Express Interest →</span>
                            </button>
                        <?php endif; ?>

                        <a href="#section-diligence-scorecard"
                            class="px-3.5 py-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 dark:hover:bg-amber-900/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold transition flex items-center space-x-1.5"
                            title="Jump to your private due diligence evaluation">
                            <i data-lucide="clipboard-check" class="w-4 h-4 text-amber-600 dark:text-amber-400"></i>
                            <span>My Scorecard (<strong id="top-score-display"><?= number_format($diligence['overall_score'], 1) ?></strong>)</span>
                        </a>

                        <?php if ($activeRound): ?>
                            <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>"
                                class="px-4 py-2.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-[#FAFBFD] dark:hover:bg-slate-700 border border-[#E4E8EF] dark:border-slate-700 text-[#111827] dark:text-slate-100 text-xs font-bold transition flex items-center space-x-2">
                                <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
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
                 SECTION: PRIVATE DUE DILIGENCE SCORECARD & INVESTOR NOTES
                 ========================================== -->
            <section id="section-diligence-scorecard" class="border-b border-[#E4E8EF] dark:border-slate-800 pb-8 scroll-mt-24 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm sm:text-base font-bold text-[#0B1F3A] dark:text-white">
                                    Private Due Diligence Scorecard &amp; Investor Notes
                                </h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800 flex items-center gap-1">
                                    <i data-lucide="lock" class="w-2.5 h-2.5"></i> Private to You
                                </span>
                            </div>
                            <p class="text-[11px] text-[#667085] dark:text-slate-400 mt-0.5">
                                Rate startup metrics across 4 pillars (1–10 scale) and maintain confidential investment memos.
                            </p>
                        </div>
                    </div>

                    <!-- Live Overall Score Badge -->
                    <div class="flex items-center gap-3 self-end sm:self-auto">
                        <div class="text-right">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Overall Rating</div>
                            <div id="badge-score-rating-label" class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                <?= $diligence['overall_score'] >= 8 ? 'High Conviction' : ($diligence['overall_score'] >= 6.5 ? 'Strong Contender' : ($diligence['overall_score'] >= 5 ? 'Moderate / In Review' : 'Needs Verification')) ?>
                            </div>
                        </div>
                        <div class="px-4 py-2 rounded-2xl bg-gradient-to-tr from-[#0B1F3A] to-[#123B7A] dark:from-blue-900 dark:to-blue-700 text-white shadow-xs flex items-center gap-1.5">
                            <span id="badge-overall-score" class="text-lg font-black tracking-tight"><?= number_format($diligence['overall_score'], 1) ?></span>
                            <span class="text-xs text-blue-200 font-semibold">/ 10</span>
                        </div>
                    </div>
                </div>

                <form id="diligence-scorecard-form" action="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" method="POST" class="bg-white dark:bg-slate-900 border border-[#E4E8EF] dark:border-slate-800 rounded-2xl p-6 sm:p-7 shadow-xs space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="save_diligence">

                    <!-- Confidentiality Notice Banner -->
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 flex items-start gap-3 text-xs text-slate-600 dark:text-slate-300">
                        <i data-lucide="shield" class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5"></i>
                        <div class="leading-relaxed">
                            <strong class="font-bold text-slate-800 dark:text-slate-100">Confidential Investor Memo:</strong>
                            These ratings and notes are strictly private to your investor account. Founders, syndicates, and platform administrators cannot see your evaluation or private memos.
                        </div>
                    </div>

                    <!-- 4-Pillar Diligence Scorecard Sliders -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        <!-- Pillar 1: Team & Execution -->
                        <div class="p-4 rounded-xl bg-[#FAFBFD] dark:bg-slate-800/40 border border-[#E4E8EF] dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-[#0B1F3A] dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="users" class="w-3.5 h-3.5 text-blue-600"></i>
                                        <span>Founding Team &amp; Execution</span>
                                    </div>
                                    <p class="text-[10.5px] text-[#667085] dark:text-slate-400 mt-0.5">
                                        Domain expertise, track record, tenacity &amp; completeness
                                    </p>
                                </div>
                                <div class="px-2.5 py-1 rounded-lg bg-blue-100/70 dark:bg-blue-950/80 text-blue-800 dark:text-blue-300 font-extrabold text-xs">
                                    <span id="display-score-team"><?= (int)$diligence['score_team'] ?></span>/10
                                </div>
                            </div>
                            <input type="range" min="1" max="10" step="1" name="score_team" id="input-score-team" value="<?= (int)$diligence['score_team'] ?>"
                                   class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-[#123B7A] dark:accent-blue-500"
                                   oninput="updateScorecardDisplay()">
                            <div class="flex justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                <span>1 (Inexperienced)</span>
                                <span>5 (Solid)</span>
                                <span>10 (Tier-1 Elite)</span>
                            </div>
                        </div>

                        <!-- Pillar 2: Market Opportunity & TAM -->
                        <div class="p-4 rounded-xl bg-[#FAFBFD] dark:bg-slate-800/40 border border-[#E4E8EF] dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-[#0B1F3A] dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>Market Opportunity (TAM)</span>
                                    </div>
                                    <p class="text-[10.5px] text-[#667085] dark:text-slate-400 mt-0.5">
                                        Market growth rate, structural tailwinds &amp; timing
                                    </p>
                                </div>
                                <div class="px-2.5 py-1 rounded-lg bg-emerald-100/70 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 font-extrabold text-xs">
                                    <span id="display-score-market"><?= (int)$diligence['score_market'] ?></span>/10
                                </div>
                            </div>
                            <input type="range" min="1" max="10" step="1" name="score_market" id="input-score-market" value="<?= (int)$diligence['score_market'] ?>"
                                   class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-emerald-600"
                                   oninput="updateScorecardDisplay()">
                            <div class="flex justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                <span>1 (Niche TAM)</span>
                                <span>5 (Viable)</span>
                                <span>10 (Massive $10B+ TAM)</span>
                            </div>
                        </div>

                        <!-- Pillar 3: Product Traction & Unit Economics -->
                        <div class="p-4 rounded-xl bg-[#FAFBFD] dark:bg-slate-800/40 border border-[#E4E8EF] dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-[#0B1F3A] dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="zap" class="w-3.5 h-3.5 text-amber-600"></i>
                                        <span>Product Traction &amp; Unit Economics</span>
                                    </div>
                                    <p class="text-[10.5px] text-[#667085] dark:text-slate-400 mt-0.5">
                                        Revenue growth, user retention, margins &amp; payback
                                    </p>
                                </div>
                                <div class="px-2.5 py-1 rounded-lg bg-amber-100/70 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 font-extrabold text-xs">
                                    <span id="display-score-traction"><?= (int)$diligence['score_traction'] ?></span>/10
                                </div>
                            </div>
                            <input type="range" min="1" max="10" step="1" name="score_traction" id="input-score-traction" value="<?= (int)$diligence['score_traction'] ?>"
                                   class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-amber-600"
                                   oninput="updateScorecardDisplay()">
                            <div class="flex justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                <span>1 (Pre-Revenue)</span>
                                <span>5 (Early Signal)</span>
                                <span>10 (Hyper-Growth PMF)</span>
                            </div>
                        </div>

                        <!-- Pillar 4: Defensibility & Competitive Moat -->
                        <div class="p-4 rounded-xl bg-[#FAFBFD] dark:bg-slate-800/40 border border-[#E4E8EF] dark:border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-[#0B1F3A] dark:text-white flex items-center gap-1.5">
                                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-violet-600"></i>
                                        <span>Defensibility &amp; Competitive Moat</span>
                                    </div>
                                    <p class="text-[10.5px] text-[#667085] dark:text-slate-400 mt-0.5">
                                        Patents, network effects, high switching cost &amp; IP
                                    </p>
                                </div>
                                <div class="px-2.5 py-1 rounded-lg bg-violet-100/70 dark:bg-violet-950/80 text-violet-800 dark:text-violet-300 font-extrabold text-xs">
                                    <span id="display-score-defensibility"><?= (int)$diligence['score_defensibility'] ?></span>/10
                                </div>
                            </div>
                            <input type="range" min="1" max="10" step="1" name="score_defensibility" id="input-score-defensibility" value="<?= (int)$diligence['score_defensibility'] ?>"
                                   class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-violet-600"
                                   oninput="updateScorecardDisplay()">
                            <div class="flex justify-between text-[10px] text-slate-400 font-semibold px-0.5">
                                <span>1 (No Moat)</span>
                                <span>5 (Moderate)</span>
                                <span>10 (Unassailable Barrier)</span>
                            </div>
                        </div>

                    </div>

                    <!-- Investment Verdict / Recommendation -->
                    <div>
                        <label class="block text-xs font-bold text-[#0B1F3A] dark:text-white mb-2">
                            <span>Your Syndicate Verdict / Next Step</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <label class="cursor-pointer">
                                <input type="radio" name="recommendation" value="strong_buy" class="peer hidden" <?= $diligence['recommendation'] === 'strong_buy' ? 'checked' : '' ?>>
                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-950/50 peer-checked:text-emerald-800 dark:peer-checked:text-emerald-300 text-slate-600 dark:text-slate-400 transition text-center hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <div class="text-base mb-0.5">🚀</div>
                                    <div class="text-xs font-bold">Strong Buy</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">High Conviction</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="recommendation" value="watch" class="peer hidden" <?= $diligence['recommendation'] === 'watch' ? 'checked' : '' ?>>
                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:border-blue-600 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-950/50 peer-checked:text-blue-800 dark:peer-checked:text-blue-300 text-slate-600 dark:text-slate-400 transition text-center hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <div class="text-base mb-0.5">👁️</div>
                                    <div class="text-xs font-bold">Watchlist</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Monitor Traction</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="recommendation" value="pending" class="peer hidden" <?= $diligence['recommendation'] === 'pending' ? 'checked' : '' ?>>
                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:border-amber-600 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-950/50 peer-checked:text-amber-800 dark:peer-checked:text-amber-300 text-slate-600 dark:text-slate-400 transition text-center hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <div class="text-base mb-0.5">⏳</div>
                                    <div class="text-xs font-bold">Need Info</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Data Room Pending</div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="recommendation" value="pass" class="peer hidden" <?= $diligence['recommendation'] === 'pass' ? 'checked' : '' ?>>
                                <div class="p-3 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:border-rose-600 peer-checked:bg-rose-50 dark:peer-checked:bg-rose-950/50 peer-checked:text-rose-800 dark:peer-checked:text-rose-300 text-slate-600 dark:text-slate-400 transition text-center hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <div class="text-base mb-0.5">⛔</div>
                                    <div class="text-xs font-bold">Pass</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Out of Thesis / Risk</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Private Notes & Due Diligence Memo -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-[#0B1F3A] dark:text-white flex items-center gap-1.5">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>Confidential Investment Notes &amp; Meeting Takeaways</span>
                            </label>
                            <span class="text-[10.5px] text-slate-400">Markdown &amp; bullets supported</span>
                        </div>
                        <textarea name="private_notes" id="input-private-notes" rows="4"
                                  placeholder="Record key diligence findings: founder background checks, valuation pushbacks, co-investor syndicates, questions for the next partner meeting..."
                                  class="w-full p-3.5 text-xs rounded-xl border border-[#E4E8EF] dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#123B7A] dark:focus:ring-blue-500 leading-relaxed font-sans"><?= htmlspecialchars($diligence['private_notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Action Bar & Toast Feedback -->
                    <div class="pt-4 border-t border-[#E4E8EF] dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-[11px] text-[#667085] dark:text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span id="diligence-save-status">
                                <?= !empty($diligence['updated_at']) ? 'Last saved on ' . date('M d, Y \a\t h:i A', strtotime($diligence['updated_at'])) : 'No notes saved yet' ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                            <button type="button" onclick="clearDiligenceNotes()" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition">
                                Clear Notes
                            </button>
                            <button type="submit" id="btn-save-diligence" class="px-5 py-2.5 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-bold text-xs transition shadow-sm flex items-center justify-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Save Due Diligence</span>
                            </button>
                        </div>
                    </div>
                </form>
            </section>

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
                                    <img src="<?= htmlspecialchars(get_avatar_url($f['avatar_url'] ?? '')) ?>"
                                        alt="<?= htmlspecialchars($f['name']) ?>"
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
            <!-- ==========================================
                 SECTION: AWARDS, ACCREDITATIONS & TRUST BADGES
                 ========================================== -->
            <?php if (!empty($awards)): ?>
                <section class="border-b border-[#E4E8EF] pb-8 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-[11px] font-bold text-[#667085] uppercase tracking-wider">
                                Verified Awards &amp; Trust Credentials (<?= count($awards) ?>)
                            </div>
                            <h2 class="text-lg font-bold text-[#0B1F3A]">Accreditations &amp; Industry Honors</h2>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-xs font-bold border border-amber-200 flex items-center gap-1">
                            <i data-lucide="award" class="w-3.5 h-3.5 text-amber-600"></i>
                            <span>Diligence Verified</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                        <?php foreach ($awards as $aw): 
                            $isPdf = str_ends_with(strtolower($aw['image_url']), '.pdf');
                        ?>
                            <!-- Certificate Photo Wall Frame -->
                            <div class="group relative cursor-pointer"
                                 onclick="<?= $isPdf ? "window.open('" . htmlspecialchars($aw['image_url']) . "', '_blank')" : "openInvestorAwardLightbox('" . htmlspecialchars($aw['image_url']) . "', '" . htmlspecialchars(addslashes($aw['title'] ?? '')) . "', '" . htmlspecialchars(addslashes($aw['issuer'] ?? '')) . "')" ?>"
                                 title="Click to view full certificate">
                                <!-- Outer Frame Molding (Top, Right, Bottom, Left all framed) -->
                                <div class="p-3 rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.25)] border-2 border-amber-600/30 group-hover:border-amber-500/70 transition-all duration-300 group-hover:scale-[1.02] group-hover:shadow-[0_16px_35px_-5px_rgba(0,0,0,0.35)]">
                                    <!-- Inner Matting (passe-partout on all sides) -->
                                    <div class="p-2 sm:p-2.5 rounded-lg bg-[#faf8f5] border border-amber-200/50 shadow-inner">
                                        <!-- Photo Mount Container -->
                                        <div class="relative w-full h-48 sm:h-56 rounded bg-white overflow-hidden flex items-center justify-center border border-slate-300/80 shadow-xs">
                                            <?php if ($isPdf): ?>
                                                <div class="text-center p-3">
                                                    <i data-lucide="file-text" class="w-8 h-8 text-rose-500 mx-auto mb-1"></i>
                                                    <span class="text-xs font-bold text-slate-700 block">Official Certificate</span>
                                                    <span class="block text-[10px] text-[#123B7A] font-semibold mt-0.5">Click to view &rarr;</span>
                                                </div>
                                            <?php else: ?>
                                                <img src="<?= htmlspecialchars($aw['image_url']) ?>" alt="Award Certificate" class="w-full h-full object-contain p-1 group-hover:brightness-105 transition duration-300">
                                                <div class="absolute inset-0 bg-slate-900/20 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                                    <span class="px-2.5 py-1 rounded-full bg-slate-900/90 text-white text-[11px] font-bold shadow-md flex items-center gap-1.5">
                                                        <i data-lucide="maximize-2" class="w-3 h-3 text-amber-400"></i>
                                                        <span>View Photo</span>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

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

    <!-- ==========================================
         EXPRESS INTEREST MODAL
         ========================================== -->
    <div id="express-interest-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 sm:p-7 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
            <button type="button" onclick="closeExpressInterestModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-[#123B7A] dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Express Interest</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Discover and connect directly with the founder</p>
                </div>
            </div>

            <form id="express-interest-form" onsubmit="submitExpressInterest(event)" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <input type="hidden" name="company_id" value="<?= htmlspecialchars($hashId) ?>">

                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-3.5 flex items-center space-x-3">
                    <img src="<?= $company['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>" class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Startup</div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($company['name']) ?></div>
                        <div class="text-xs text-slate-500"><?= htmlspecialchars($company['industry']) ?> • <?= htmlspecialchars($company['stage']) ?></div>
                    </div>
                </div>

                <div>
                    <label for="interest-message" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Why are you interested? <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="interest-message" name="message" rows="4" required minlength="10" maxlength="2000"
                        placeholder="Briefly describe what caught your attention (e.g. market thesis, traction, technology synergies, or potential check size)..."
                        class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-3 focus:ring-2 focus:ring-[#123B7A] focus:border-transparent outline-none transition placeholder-slate-400"></textarea>
                    <div class="flex justify-between items-center mt-1 text-[11px] text-slate-400">
                        <span>Keep the message short and useful.</span>
                        <span id="char-count">0 / 2000</span>
                    </div>
                </div>

                <div class="bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-xl p-3 text-[11px] text-slate-600 dark:text-slate-300 flex items-start space-x-2">
                    <i data-lucide="info" class="w-4 h-4 text-[#123B7A] dark:text-blue-400 flex-shrink-0 mt-0.5"></i>
                    <span><strong>Nexora helps startups and investors discover opportunities and connect directly.</strong> Expressing interest notifies the founder. When accepted, direct conversation unlocks.</span>
                </div>

                <div id="interest-error" class="hidden p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 text-xs font-semibold"></div>
                <div id="interest-success" class="hidden p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-xs font-semibold"></div>

                <div class="flex items-center justify-end space-x-3 pt-2">
                    <button type="button" onclick="closeExpressInterestModal()"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-interest"
                        class="px-5 py-2.5 rounded-lg bg-[#123B7A] hover:bg-[#0B1F3A] text-white text-xs font-bold transition flex items-center space-x-2 shadow-sm disabled:opacity-50">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Express Interest →</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#deal-room-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });

        const modal = document.getElementById('express-interest-modal');
        const messageInput = document.getElementById('interest-message');
        const charCount = document.getElementById('char-count');
        const errorBox = document.getElementById('interest-error');
        const successBox = document.getElementById('interest-success');
        const submitBtn = document.getElementById('btn-submit-interest');

        function openExpressInterestModal() {
            if (modal) {
                modal.classList.remove('hidden');
                if (errorBox) errorBox.classList.add('hidden');
                if (successBox) successBox.classList.add('hidden');
                if (messageInput) {
                    messageInput.focus();
                }
                lucide.createIcons();
            }
        }

        function closeExpressInterestModal() {
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        if (messageInput && charCount) {
            messageInput.addEventListener('input', () => {
                charCount.textContent = `${messageInput.value.length} / 2000`;
            });
        }

        async function submitExpressInterest(e) {
            e.preventDefault();
            if (!messageInput || messageInput.value.trim().length < 10) {
                showError('Please write at least 10 characters explaining why you are interested.');
                return;
            }

            const form = document.getElementById('express-interest-form');
            const formData = new FormData(form);

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Submitting...';
            }
            if (errorBox) errorBox.classList.add('hidden');
            if (successBox) successBox.classList.add('hidden');

            try {
                const response = await fetch('<?= url("api/investor/express-interest.php") ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    if (successBox) {
                        successBox.textContent = result.message || 'Interest sent successfully. The founder has been notified.';
                        successBox.classList.remove('hidden');
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 1400);
                } else {
                    showError(result.error || 'Failed to submit interest.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> <span>Express Interest →</span>';
                        lucide.createIcons();
                    }
                }
            } catch (err) {
                showError('Network error. Please check your connection and try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i> <span>Express Interest →</span>';
                    lucide.createIcons();
                }
            }
        }

        function showError(msg) {
            if (errorBox) {
                errorBox.textContent = msg;
                errorBox.classList.remove('hidden');
            }
        }

        function openInvestorAwardLightbox(src, title, issuer) {
            const modal = document.getElementById('investor-award-lightbox');
            const img = document.getElementById('inv-lightbox-img');
            const titleEl = document.getElementById('inv-lightbox-title');
            const issuerEl = document.getElementById('inv-lightbox-issuer');
            if (modal && img) {
                img.src = src;
                if (titleEl) titleEl.textContent = title || 'Verified Award';
                if (issuerEl) issuerEl.textContent = issuer ? ('Issued by: ' + issuer) : '';
                modal.classList.remove('hidden');
                lucide.createIcons();
            }
        }
        function closeInvestorAwardLightbox() {
            const modal = document.getElementById('investor-award-lightbox');
            if (modal) modal.classList.add('hidden');
        }
        document.addEventListener('keydown', (e) => { 
            if (e.key === 'Escape') closeInvestorAwardLightbox();
        });

        // ==========================================
        // PRIVATE DUE DILIGENCE SCORECARD FUNCTIONS
        // ==========================================
        function updateScorecardDisplay() {
            const teamInput = document.getElementById('input-score-team');
            const marketInput = document.getElementById('input-score-market');
            const tractionInput = document.getElementById('input-score-traction');
            const defInput = document.getElementById('input-score-defensibility');

            const team = teamInput ? parseInt(teamInput.value, 10) || 1 : 1;
            const market = marketInput ? parseInt(marketInput.value, 10) || 1 : 1;
            const traction = tractionInput ? parseInt(tractionInput.value, 10) || 1 : 1;
            const def = defInput ? parseInt(defInput.value, 10) || 1 : 1;

            const dispTeam = document.getElementById('display-score-team');
            const dispMarket = document.getElementById('display-score-market');
            const dispTraction = document.getElementById('display-score-traction');
            const dispDef = document.getElementById('display-score-defensibility');

            if (dispTeam) dispTeam.textContent = team;
            if (dispMarket) dispMarket.textContent = market;
            if (dispTraction) dispTraction.textContent = traction;
            if (dispDef) dispDef.textContent = def;

            const overall = ((team + market + traction + def) / 4).toFixed(1);
            
            const badgeOverall = document.getElementById('badge-overall-score');
            const topScoreDisp = document.getElementById('top-score-display');
            if (badgeOverall) badgeOverall.textContent = overall;
            if (topScoreDisp) topScoreDisp.textContent = overall;

            const labelEl = document.getElementById('badge-score-rating-label');
            if (labelEl) {
                const numericOverall = parseFloat(overall);
                if (numericOverall >= 8.0) {
                    labelEl.textContent = 'High Conviction';
                    labelEl.className = 'text-xs font-bold text-emerald-600 dark:text-emerald-400';
                } else if (numericOverall >= 6.5) {
                    labelEl.textContent = 'Strong Contender';
                    labelEl.className = 'text-xs font-bold text-blue-600 dark:text-blue-400';
                } else if (numericOverall >= 5.0) {
                    labelEl.textContent = 'Moderate / In Review';
                    labelEl.className = 'text-xs font-bold text-amber-600 dark:text-amber-400';
                } else {
                    labelEl.textContent = 'Needs Verification';
                    labelEl.className = 'text-xs font-bold text-rose-600 dark:text-rose-400';
                }
            }
        }

        function clearDiligenceNotes() {
            const notesEl = document.getElementById('input-private-notes');
            if (notesEl && confirm('Clear your confidential notes for this startup? (Click Save afterwards to persist changes)')) {
                notesEl.value = '';
                notesEl.focus();
            }
        }

        // AJAX Due Diligence Form submission
        const diligenceForm = document.getElementById('diligence-scorecard-form');
        if (diligenceForm) {
            diligenceForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = document.getElementById('btn-save-diligence');
                const statusEl = document.getElementById('diligence-save-status');
                const originalBtnHtml = btn ? btn.innerHTML : '';

                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="inline-block animate-spin mr-1.5">&#x27F3;</span> <span>Saving...</span>';
                }

                try {
                    const formData = new FormData(diligenceForm);
                    const response = await fetch(diligenceForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            if (statusEl) {
                                statusEl.textContent = 'Last saved on ' + (data.updated_at || 'Just now');
                            }
                            if (btn) {
                                btn.classList.remove('bg-[#123B7A]', 'dark:bg-blue-600');
                                btn.classList.add('bg-emerald-600');
                                btn.innerHTML = '<i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> <span>Saved!</span>';
                                lucide.createIcons();
                                setTimeout(() => {
                                    btn.disabled = false;
                                    btn.classList.remove('bg-emerald-600');
                                    btn.classList.add('bg-[#123B7A]', 'dark:bg-blue-600');
                                    btn.innerHTML = originalBtnHtml;
                                    lucide.createIcons();
                                }, 2200);
                            }
                            return;
                        }
                    }
                    diligenceForm.submit();
                } catch (err) {
                    diligenceForm.submit();
                }
            });
        }
    </script>

    <!-- Investor Award Lightbox Modal -->
    <div id="investor-award-lightbox"
        class="hidden fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4"
        onclick="if (event.target === this) closeInvestorAwardLightbox();">
        <div class="relative max-w-4xl w-full bg-slate-900 rounded-2xl overflow-hidden shadow-2xl border border-slate-800">
            <div class="flex items-center justify-between p-4 border-b border-slate-800 bg-slate-900/90 text-white">
                <div>
                    <h4 id="inv-lightbox-title" class="font-bold text-base text-white"></h4>
                    <p id="inv-lightbox-issuer" class="text-xs text-amber-400 font-medium"></p>
                </div>
                <button type="button" onclick="closeInvestorAwardLightbox()"
                    class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-2 bg-black flex items-center justify-center min-h-[300px] max-h-[75vh] overflow-hidden">
                <img id="inv-lightbox-img" src="" alt="Award Photo" class="max-h-[72vh] max-w-full object-contain rounded-lg">
            </div>
        </div>
    </div>
</body>

</html>