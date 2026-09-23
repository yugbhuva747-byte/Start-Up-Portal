<?php
/**
 * Founder Module: Main Dashboard
 * Enhanced with investor activity feed, funding analytics, notifications, and quick stats
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Founder Command Center';

$company = null;
$activeRound = null;
$allRounds = [];
$recentOrders = [];
$recentMessages = [];
$recentNotifs = [];
$pendingDocs = 0;
$stats = [
    'total_raised' => 0,
    'investors_count' => 0,
    'round_progress' => 0,
    'target_amount' => 0,
    'total_rounds' => 0,
    'total_views' => 0,
    'blog_count' => 0
];

if ($db) {
    // 1. Get Company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    if ($company) {
        // 2. All Funding Rounds
        $frStmt = $db->prepare("SELECT * FROM funding_rounds WHERE company_id = ? ORDER BY created_at DESC");
        $frStmt->execute([$company['id']]);
        $allRounds = $frStmt->fetchAll();
        $stats['total_rounds'] = count($allRounds);
        
        // Active/latest round
        $activeRound = !empty($allRounds) ? $allRounds[0] : null;

        // Calculate total raised across ALL rounds
        foreach ($allRounds as $r) {
            $stats['total_raised'] += (float)$r['amount_raised'];
        }

        if ($activeRound) {
            $stats['target_amount'] = (float)$activeRound['target_amount'];
            $stats['round_progress'] = $activeRound['target_amount'] > 0 
                ? round(($activeRound['amount_raised'] / $activeRound['target_amount']) * 100) 
                : 0;

            // 3. Recent Investment Orders for active round
            $ordStmt = $db->prepare("
                SELECT io.*, u.name as investor_name, u.avatar_url, u.email as investor_email, 
                       ip.investor_type, ip.experience_years
                FROM investment_orders io
                JOIN users u ON io.investor_user_id = u.id
                LEFT JOIN investor_profiles ip ON u.id = ip.user_id
                WHERE io.funding_round_id = ?
                ORDER BY io.created_at DESC LIMIT 8
            ");
            $ordStmt->execute([$activeRound['id']]);
            $recentOrders = $ordStmt->fetchAll();
            $stats['investors_count'] = count($recentOrders);
        }

        // 4. Total unique investors across all rounds
        $allInvStmt = $db->prepare("
            SELECT COUNT(DISTINCT io.investor_user_id) 
            FROM investment_orders io 
            JOIN funding_rounds fr ON io.funding_round_id = fr.id 
            WHERE fr.company_id = ?
        ");
        $allInvStmt->execute([$company['id']]);
        $stats['investors_count'] = (int)$allInvStmt->fetchColumn();

        // 5. Blog count
        $blogStmt = $db->prepare("SELECT COUNT(*) FROM company_blogs WHERE company_id = ?");
        $blogStmt->execute([$company['id']]);
        $stats['blog_count'] = (int)$blogStmt->fetchColumn();

        // 6. Pending documents
        $docStmt = $db->prepare("SELECT COUNT(*) FROM verification_documents WHERE user_id = ? AND status = 'pending'");
        $docStmt->execute([$user['id']]);
        $pendingDocs = (int)$docStmt->fetchColumn();
    }

    // 7. Recent messages
    $msgStmt = $db->prepare("
        SELECT m.message_text, m.created_at, u.name as sender_name, u.avatar_url as sender_avatar
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        JOIN users u ON m.sender_user_id = u.id
        WHERE (c.user_one_id = ? OR c.user_two_id = ?) AND m.sender_user_id != ?
        ORDER BY m.created_at DESC LIMIT 4
    ");
    $msgStmt->execute([$user['id'], $user['id'], $user['id']]);
    $recentMessages = $msgStmt->fetchAll();

    // 8. Recent notifications
    $nStmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $nStmt->execute([$user['id']]);
    $recentNotifs = $nStmt->fetchAll();
}

$progress = get_profile_progress($user['id'], 'founder');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Founder Dashboard • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FAFAFB; color: #0F172A; }
        .card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03); }
        .progress-ring { transition: stroke-dashoffset 1.2s ease; }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="dashboard-content">
            
            <!-- Flash notification -->
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Live Platform Announcements & Regulatory Bulletins -->
            <?php include __DIR__ . '/../includes/announcement_banner.php'; ?>

            <!-- Welcome Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-1.5">
                        <span>Welcome, <?= htmlspecialchars($user['name']) ?></span>
                    </h1>
                   
                </div>
                <div class="flex items-center space-x-2">
                    <a href="<?= url('founder/cap_table.php') ?>" class="px-3.5 py-2 rounded-lg bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="pie-chart" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Cap Table</span>
                    </a>
                    <a href="<?= url('founder/view.php') ?>" class="px-3.5 py-2 rounded-lg bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>View Public Profile</span>
                    </a>
                    <a href="<?= url('founder/funding_rounds.php?action=new') ?>" class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>Launch Round</span>
                    </a>
                </div>
            </div>

            <!-- Profile Incomplete Alert -->
            <?php if (!$progress['is_complete']): ?>
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start space-x-2.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <div class="text-xs font-bold text-amber-900">Profile Readiness: <?= $progress['percentage'] ?>% Complete</div>
                            <div class="text-[11px] text-amber-700 mt-0.5">Missing: <?= implode(', ', $progress['missing']) ?>. Complete KYC to attract verified investors.</div>
                        </div>
                    </div>
                    <a href="<?= url('founder/verification.php') ?>" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs whitespace-nowrap transition flex items-center space-x-1">
                        <span>Verify Now</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="stats-grid">
                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Total Raised</span>
                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="wallet" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= format_inr($stats['total_raised']) ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Across <?= $stats['total_rounds'] ?> round<?= $stats['total_rounds'] !== 1 ? 's' : '' ?></div>
                </div>

                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Round Status</span>
                        <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="activity" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="mt-1">
                        <?= $activeRound ? render_status_badge($activeRound['status']) : '<span class="text-xs text-slate-400 font-semibold">No active round</span>' ?>
                    </div>
                    <div class="text-[10.5px] text-slate-500 mt-1.5 truncate"><?= $activeRound ? htmlspecialchars($activeRound['round_name']) : 'Create round to raise' ?></div>
                </div>

                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">Active Investors</span>
                        <div class="p-1.5 rounded-lg bg-purple-50 text-purple-600"><i data-lucide="users" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="text-xl font-black text-slate-900"><?= $stats['investors_count'] ?></div>
                    <div class="text-[10.5px] text-slate-500 mt-0.5">Unique investors committed</div>
                </div>

                <div class="card-clean rounded-xl p-5">
                    <div class="flex items-center justify-between text-slate-400 mb-1.5">
                        <span class="text-[10.5px] font-bold uppercase tracking-wider text-slate-500">KYC Status</span>
                        <div class="p-1.5 rounded-lg bg-teal-50 text-teal-600"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i></div>
                    </div>
                    <div class="mt-1">
                        <?= $company ? render_status_badge($company['verified_status']) : render_status_badge('UNVERIFIED') ?>
                    </div>
                    <div class="text-[10.5px] text-slate-500 mt-1.5">
                        <?php if ($pendingDocs > 0): ?>
                            <span class="text-amber-600 font-semibold"><?= $pendingDocs ?> document<?= $pendingDocs > 1 ? 's' : '' ?> pending review</span>
                        <?php else: ?>
                            MCA & DigiLocker Gateway
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Active Round Live Progress Bar Card -->
            <?php if ($activeRound): ?>
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                        <div>
                            <div class="flex items-center space-x-2.5">
                                <h2 class="text-base font-bold text-slate-900"><?= htmlspecialchars($activeRound['round_name']) ?></h2>
                                <?= render_status_badge($activeRound['status']) ?>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Valuation: <strong class="text-slate-800"><?= format_inr($activeRound['valuation']) ?></strong> • Equity Offered: <strong class="text-slate-800"><?= $activeRound['equity_offered'] ?>%</strong></p>
                        </div>
                        <div class="text-right">
                            <span class="text-xl font-black text-emerald-600"><?= $stats['round_progress'] ?>%</span>
                            <span class="text-[11px] text-slate-500 block">funded of <?= format_inr($activeRound['target_amount']) ?></span>
                        </div>
                    </div>

                    <!-- Progress bar with milestone markers -->
                    <div class="relative w-full mb-2.5">
                        <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                            <div class="h-full rounded-full transition-all duration-1000 <?= $stats['round_progress'] >= 100 ? 'bg-emerald-500' : ($stats['round_progress'] >= 50 ? 'bg-indigo-600' : 'bg-indigo-500') ?>" 
                                 style="width: <?= min(100, $stats['round_progress']) ?>%"></div>
                        </div>
                        <!-- Milestone markers -->
                        <div class="absolute top-0 left-1/4 w-px h-2.5 bg-slate-300 opacity-50"></div>
                        <div class="absolute top-0 left-1/2 w-px h-2.5 bg-slate-300 opacity-50"></div>
                        <div class="absolute top-0 left-3/4 w-px h-2.5 bg-slate-300 opacity-50"></div>
                    </div>

                    <div class="flex justify-between text-xs text-slate-600 font-medium">
                        <span>Raised: <strong class="text-slate-900"><?= format_inr($activeRound['amount_raised']) ?></strong></span>
                        <span>Min ticket: <strong class="text-slate-900"><?= format_inr($activeRound['min_investment']) ?></strong></span>
                        <span>Remaining: <strong class="text-slate-900"><?= format_inr(max(0, $activeRound['target_amount'] - $activeRound['amount_raised'])) ?></strong></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Three Column: Investor Activity + Messages + Notifications -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Recent Investment Commitments -->
                <div class="lg:col-span-2 card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="handshake" class="w-4 h-4 text-indigo-600"></i>
                            <span>Investor Activity Feed</span>
                        </h3>
                        <a href="<?= url('founder/funding_rounds.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">View All →</a>
                    </div>

                    <?php if (empty($recentOrders)): ?>
                        <div class="py-10 text-center text-slate-400 text-xs">
                            <i data-lucide="inbox" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                            <div class="font-bold text-slate-700 mb-0.5">No investor commitments yet</div>
                            <div class="text-[11px]">Once accredited investors review your pitch, investments will appear here.</div>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10px]">
                                        <th class="pb-2.5 font-semibold">Investor</th>
                                        <th class="pb-2.5 font-semibold">Type</th>
                                        <th class="pb-2.5 font-semibold">Amount</th>
                                        <th class="pb-2.5 font-semibold">Status</th>
                                        <th class="pb-2.5 font-semibold">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($recentOrders as $ord): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3">
                                                <a href="<?= url('investor/view.php?id=' . encode_id($ord['investor_user_id'])) ?>" class="flex items-center space-x-2 hover:text-indigo-600 transition group" title="View Investor Profile">
                                                    <img src="<?= $ord['avatar_url'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-6 h-6 rounded-full object-cover border border-slate-200 group-hover:ring-1 group-hover:ring-indigo-500 transition">
                                                    <div>
                                                        <span class="font-bold text-slate-800 group-hover:text-indigo-600 transition"><?= htmlspecialchars($ord['investor_name']) ?></span>
                                                        <?php if (!empty($ord['experience_years'])): ?>
                                                            <span class="text-[10px] text-slate-400 block"><?= $ord['experience_years'] ?>+ yrs exp.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </a>
                                            </td>
                                            <td class="py-3 text-slate-500 text-[11px]"><?= htmlspecialchars($ord['investor_type'] ?? 'Angel') ?></td>
                                            <td class="py-3 font-bold text-slate-900"><?= format_inr($ord['amount']) ?></td>
                                            <td class="py-3"><?= render_status_badge($ord['status']) ?></td>
                                            <td class="py-3 text-slate-400 text-[11px]"><?= date('d M Y', strtotime($ord['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Messages + Quick Actions -->
                <div class="space-y-4">
                    
                    <!-- Recent Messages -->
                    <div class="card-clean rounded-2xl p-5">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center justify-between">
                            <span class="flex items-center space-x-1.5">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Recent Messages</span>
                            </span>
                            <a href="<?= url('founder/messages.php') ?>" class="text-indigo-600 font-semibold hover:text-indigo-700">All →</a>
                        </h3>
                        <?php if (empty($recentMessages)): ?>
                            <p class="text-[11px] text-slate-400 py-3 text-center">No messages yet</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($recentMessages as $msg): ?>
                                    <a href="<?= url('founder/messages.php') ?>" class="flex items-start space-x-2 p-2 rounded-lg hover:bg-slate-50 transition">
                                        <img src="<?= $msg['sender_avatar'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80' ?>" class="w-6 h-6 rounded-full object-cover border border-slate-200 flex-shrink-0 mt-0.5">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[11px] font-bold text-slate-800 truncate"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                            <div class="text-[10px] text-slate-500 truncate"><?= htmlspecialchars(substr($msg['message_text'], 0, 60)) ?></div>
                                        </div>
                                        <span class="text-[9px] text-slate-400 flex-shrink-0"><?= date('d M', strtotime($msg['created_at'])) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Founder Tools -->
                    <div class="card-clean rounded-2xl p-5">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3 flex items-center space-x-1.5">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-500"></i>
                            <span>Quick Tools</span>
                        </h3>
                        <div class="space-y-1.5 text-xs">
                            <a href="<?= url('founder/company.php') ?>" class="p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200/80 flex items-center justify-between transition">
                                <span class="text-slate-700 font-medium">Update Pitch Deck & Data Room</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                            </a>
                            <a href="<?= url('founder/blogs.php') ?>" class="p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200/80 flex items-center justify-between transition">
                                <span class="text-slate-700 font-medium">Manage Company Blog (<?= $stats['blog_count'] ?>)</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                            </a>
                            <a href="<?= url('founder/verification.php') ?>" class="p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200/80 flex items-center justify-between transition">
                                <span class="text-slate-700 font-medium">DigiLocker Verification</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                            </a>
                            <a href="<?= url('founder/updates.php') ?>" class="p-2.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200/80 flex items-center justify-between transition">
                                <span class="text-slate-700 font-medium">Post Investor Update</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Compliance Disclaimer -->
                    <div class="p-4 rounded-2xl bg-white border border-slate-200 text-[11px] text-slate-500 leading-relaxed shadow-sm">
                        <div class="font-bold text-slate-800 mb-1 flex items-center space-x-1">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Regulatory Safeguards</span>
                        </div>
                        Funds committed are maintained in escrow compliance workflows subject to MCA and legal instrument execution.
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <?php if (!empty($recentNotifs)): ?>
                <div class="card-clean rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                            <i data-lucide="bell" class="w-3.5 h-3.5 text-amber-500"></i>
                            <span>Latest Notifications</span>
                        </h3>
                        <a href="<?= url('notifications.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">View All →</a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php foreach ($recentNotifs as $n): ?>
                            <div class="flex items-start space-x-2.5 p-2.5 rounded-lg hover:bg-slate-50 transition border border-slate-100">
                                <div class="w-7 h-7 rounded-lg bg-<?= $n['type'] === 'success' ? 'emerald' : ($n['type'] === 'warning' ? 'amber' : 'indigo') ?>-50 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="<?= $n['type'] === 'success' ? 'check-circle' : ($n['type'] === 'warning' ? 'alert-triangle' : 'bell') ?>" class="w-3.5 h-3.5 text-<?= $n['type'] === 'success' ? 'emerald' : ($n['type'] === 'warning' ? 'amber' : 'indigo') ?>-600"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-[11px] font-bold text-slate-800 truncate"><?= htmlspecialchars($n['title']) ?></div>
                                    <div class="text-[10px] text-slate-500 truncate"><?= htmlspecialchars(substr($n['message'], 0, 80)) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#dashboard-content > *", { duration: 0.4, y: 12, opacity: 0, stagger: 0.06, ease: "power2.out" });
    </script>
</body>
</html>
