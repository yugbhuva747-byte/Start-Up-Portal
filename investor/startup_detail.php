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
    $isSaved = (bool)$wStmt->fetch();

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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="deal-room-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Breadcrumb Navigation -->
            <div class="flex items-center space-x-2 text-xs text-slate-400">
                <a href="<?= url('investor/discover.php') ?>" class="hover:text-slate-800 transition flex items-center space-x-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Discovery</span>
                </a>
                <span>/</span>
                <span class="text-slate-700 font-semibold"><?= htmlspecialchars($company['name']) ?></span>
            </div>

            <!-- Hero Company Header -->
            <div class="card-clean rounded-2xl p-6 relative">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start space-x-4">
                        <img src="<?= $company['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=160' ?>" class="w-14 h-14 rounded-xl object-cover border border-slate-200">
                        <div>
                            <div class="flex items-center space-x-2.5">
                                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"><?= htmlspecialchars($company['name']) ?></h1>
                                <?= render_status_badge($company['verified_status']) ?>
                            </div>
                            <div class="flex items-center space-x-2 text-xs text-slate-500 mt-0.5">
                                <span class="text-indigo-600 font-bold"><?= htmlspecialchars($company['industry']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($company['stage']) ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars($company['city']) ?>, <?= htmlspecialchars($company['country']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2.5">
                        <!-- Message Founder Button -->
                        <?php if (!empty($founders)): ?>
                            <a href="<?= url('investor/messages.php?founder=' . hash_id_encode($founders[0]['id']) . '&company=' . $hashId) ?>" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center space-x-1.5">
                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                <span>Message Founder</span>
                            </a>
                        <?php endif; ?>

                        <!-- Invest Now Action -->
                        <?php if ($activeRound): ?>
                            <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                                <span>Participate / Invest</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-600">
                    <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider block mb-0.5">Elevator Pitch:</span>
                    <div class="text-slate-700 font-medium"><?= htmlspecialchars($company['pitch']) ?></div>
                </div>
            </div>

            <!-- Two Column Layout: Round Details & Pitch Description -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Main Information (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Business Overview -->
                    <div class="card-clean rounded-2xl p-6">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="info" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>About the Company & Market Opportunity</span>
                        </h2>
                        <div class="text-xs text-slate-600 leading-relaxed whitespace-pre-line space-y-1.5">
                            <?= nl2br(htmlspecialchars($company['description'])) ?>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-5 pt-4 border-t border-slate-100 text-xs">
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Business Model</span>
                                <span class="text-slate-800 font-semibold"><?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Corporate CIN</span>
                                <span class="text-slate-800 font-mono font-semibold"><?= htmlspecialchars($company['cin_number'] ?? 'Verified') ?></span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[10px] uppercase font-bold tracking-wider block">Team Size</span>
                                <span class="text-slate-800 font-semibold"><?= htmlspecialchars($company['employee_count'] ?? '10') ?> Members</span>
                            </div>
                        </div>
                    </div>

                    <!-- Founding Team -->
                    <div class="card-clean rounded-2xl p-6">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="users" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Leadership & Founding Team</span>
                        </h2>

                        <div class="space-y-3">
                            <?php foreach ($founders as $f): 
                                $fId = $f['user_id'] ?? $f['id'] ?? 0;
                            ?>
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-start space-x-3.5 hover:bg-slate-100/70 transition group">
                                    <a href="<?= url('founder/view.php?id=' . encode_id($fId)) ?>" title="View Full Founder Profile">
                                        <img src="<?= $f['avatar_url'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200 group-hover:ring-2 group-hover:ring-indigo-500 transition">
                                    </a>
                                    <div class="flex-1 text-xs">
                                        <div class="flex items-center justify-between">
                                            <a href="<?= url('founder/view.php?id=' . encode_id($fId)) ?>" class="font-bold text-slate-900 text-xs hover:text-indigo-600 transition flex items-center space-x-1.5">
                                                <span><?= htmlspecialchars($f['name']) ?></span>
                                                <i data-lucide="arrow-up-right" class="w-3 h-3 text-slate-400 group-hover:text-indigo-600 transition"></i>
                                            </a>
                                            <div class="flex items-center space-x-2">
                                                <a href="<?= url('founder/view.php?id=' . encode_id($fId)) ?>" class="text-[10.5px] text-indigo-600 hover:text-indigo-800 font-semibold">
                                                    View Profile
                                                </a>
                                                <?php if (!empty($f['linkedin_url'])): ?>
                                                    <span class="text-slate-300">•</span>
                                                    <a href="<?= htmlspecialchars($f['linkedin_url']) ?>" target="_blank" class="text-slate-500 hover:text-slate-800 font-semibold flex items-center space-x-0.5 text-[10.5px]">
                                                        <span>LinkedIn</span>
                                                        <i data-lucide="external-link" class="w-2.5 h-2.5"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-indigo-600 font-semibold text-[10px] uppercase tracking-wider mb-1"><?= htmlspecialchars($f['designation'] ?? 'Founder') ?></div>
                                        <div class="text-slate-500 leading-relaxed text-[11px]"><?= htmlspecialchars($f['bio'] ?? 'Experienced startup builder.') ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Company Traction & Founder Updates Feed -->
                    <div class="card-clean rounded-2xl p-6" id="updates">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="newspaper" class="w-3.5 h-3.5 text-emerald-600"></i>
                                <span>Traction Updates & Founder Announcements</span>
                            </h2>
                            <span class="text-[10.5px] font-semibold text-slate-400"><?= count($updates) ?> update<?= count($updates) === 1 ? '' : 's' ?></span>
                        </div>

                        <div class="mt-4 space-y-3.5 text-xs">
                            <?php if (empty($updates)): ?>
                                <div class="py-6 text-center text-slate-400">No public milestones posted yet.</div>
                            <?php else: ?>
                                <?php foreach ($updates as $upd): ?>
                                    <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/70 space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                                    <?= htmlspecialchars($upd['category']) ?>
                                                </span>
                                                <h3 class="font-bold text-slate-900"><?= htmlspecialchars($upd['title']) ?></h3>
                                            </div>
                                            <span class="text-[10px] text-slate-400 font-medium">
                                                <?= date('M d, Y', strtotime($upd['created_at'])) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($upd['metrics_summary'])): ?>
                                            <div class="text-[11px] font-semibold text-indigo-700 flex items-center space-x-1.5">
                                                <i data-lucide="trending-up" class="w-3 h-3 text-indigo-600"></i>
                                                <span><?= htmlspecialchars($upd['metrics_summary']) ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <p class="text-slate-600 text-[11.5px] leading-relaxed whitespace-pre-line"><?= htmlspecialchars($upd['content']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Company Stories, Trust Media & Blogs -->
                    <div class="card-clean rounded-2xl p-6" id="blogs">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="book-open" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Company Stories & Trust Media (<?= count($companyBlogs) ?>)</span>
                            </h2>
                            <span class="text-[10.5px] font-semibold text-slate-400">Authentic proof & founder articles</span>
                        </div>

                        <div class="mt-4">
                            <?php if (empty($companyBlogs)): ?>
                                <div class="py-6 text-center text-slate-400 text-xs">
                                    The founder has not published long-form stories or proof articles yet.
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php foreach ($companyBlogs as $cb): 
                                        $cbCover = str_starts_with($cb['cover_image'], 'http') ? $cb['cover_image'] : url($cb['cover_image']);
                                        $cbUrl = url('blog.php?id=' . $cb['id']);
                                    ?>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden bg-white hover:border-indigo-300 transition flex flex-col group">
                                            <div class="h-36 overflow-hidden bg-slate-100 relative">
                                                <img src="<?= htmlspecialchars($cbCover) ?>" alt="<?= htmlspecialchars($cb['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-white/95 text-indigo-700 shadow-sm border border-white">
                                                    <?= htmlspecialchars($cb['category']) ?>
                                                </span>
                                            </div>
                                            <div class="p-3.5 flex-1 flex flex-col justify-between text-xs">
                                                <div>
                                                    <div class="text-[10px] text-slate-400 mb-1">
                                                        <?= date('M d, Y', strtotime($cb['published_at'])) ?> • <?= $cb['read_time_minutes'] ?> min read
                                                    </div>
                                                    <h3 class="font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-indigo-600 transition"><?= htmlspecialchars($cb['title']) ?></h3>
                                                    <p class="text-slate-500 text-[11px] mt-1 line-clamp-2"><?= htmlspecialchars($cb['summary']) ?></p>
                                                </div>
                                                <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between">
                                                    <a href="<?= $cbUrl ?>" target="_blank" class="text-indigo-600 hover:text-indigo-700 font-bold text-[11px] flex items-center space-x-1">
                                                        <span>Read Story</span>
                                                        <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                                    </a>
                                                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($cb['title'] . ' ' . $cbUrl) ?>" target="_blank" class="p-1.5 rounded-lg hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 transition" title="Share via WhatsApp">
                                                        <i data-lucide="share-2" class="w-3.5 h-3.5"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Gated Data Room Documents -->
                    <div class="card-clean rounded-2xl p-6">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="file-lock" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Confidential Data Room & Diligence Files</span>
                        </h2>

                        <div class="divide-y divide-slate-100 text-xs">
                            <?php if (empty($documents)): ?>
                                <div class="py-6 text-center text-slate-400 text-xs">No additional data room documents attached.</div>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                    <div class="py-3 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($doc['title']) ?></div>
                                                <div class="text-[10px] text-slate-500"><?= htmlspecialchars($doc['document_type']) ?> • <?= htmlspecialchars($doc['file_size']) ?></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-1.5">
                                            <a href="<?= url($doc['file_path']) ?>" target="_blank" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="View in New Tab">
                                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                                <span>View</span>
                                            </a>
                                            <a href="<?= url('download.php?id=' . $doc['id'] . '&type=company') ?>" class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="Download Document">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                                <span>Download</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Live Round Sidebar (1 col) -->
                <div class="space-y-4">
                    <?php if ($activeRound): 
                        $pct = $activeRound['target_amount'] > 0 ? round(($activeRound['amount_raised'] / $activeRound['target_amount']) * 100) : 0;
                        $remaining = max(0, $activeRound['target_amount'] - $activeRound['amount_raised']);
                    ?>
                        <div class="card-clean rounded-2xl p-6 relative">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ACTIVE ROUND
                                </span>
                                <?= render_status_badge($activeRound['status']) ?>
                            </div>

                            <h3 class="text-base font-bold text-slate-900 mb-0.5"><?= htmlspecialchars($activeRound['round_name']) ?></h3>
                            <div class="text-xl font-black text-emerald-600 mb-3"><?= format_inr($activeRound['amount_raised']) ?> <span class="text-xs text-slate-400 font-normal">raised</span></div>

                            <!-- Progress Bar -->
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mb-1.5 border border-slate-200">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-slate-500 font-semibold mb-4">
                                <span><?= $pct ?>% funded</span>
                                <span>Target: <?= format_inr($activeRound['target_amount']) ?></span>
                            </div>

                            <div class="space-y-2 text-xs mb-5 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Pre-Money Valuation:</span>
                                    <span class="font-bold text-slate-800"><?= format_inr($activeRound['valuation']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Equity Offered:</span>
                                    <span class="font-bold text-slate-800"><?= $activeRound['equity_offered'] ?>%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Min. Investment Ticket:</span>
                                    <span class="font-bold text-emerald-700"><?= format_inr($activeRound['min_investment']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Remaining Gap:</span>
                                    <span class="font-bold text-slate-800"><?= format_inr($remaining) ?></span>
                                </div>
                            </div>

                            <a href="<?= url('investor/invest.php?round=' . hash_id_encode($activeRound['id'])) ?>" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm flex items-center justify-center space-x-1.5 transition">
                                <span>Commit Investment (Escrow)</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="card-clean rounded-2xl p-6 text-center text-xs text-slate-400">
                            No open funding round at this moment. You can message the founder directly to express preliminary syndicate interest.
                        </div>
                    <?php endif; ?>

                    <!-- Compliance Safeguards -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-500 leading-relaxed">
                        <div class="font-bold text-slate-800 mb-1 flex items-center space-x-1.5">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>SEBI Diligence Standard</span>
                        </div>
                        This opportunity is verified against MCA MCA-21 registry filings. All investment commitments are executed through structured escrow mechanics.
                    </div>
                </div>

            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#deal-room-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
