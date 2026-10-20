<?php
/**
 * Founder Module: Investor Interests Management
 * Review investors who have expressed interest in the startup.
 * Allows Founders to view profiles, Accept & Connect (creates/reuses chat), or Decline.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Investor Interest';

$flash = get_flash();
$error = '';

// 1. Fetch all companies associated with this founder
$companies = [];
$companyIds = [];
if ($db) {
    $cStmt = $db->prepare("
        SELECT c.*, cf.designation
        FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ?
        ORDER BY c.name ASC
    ");
    $cStmt->execute([$user['id']]);
    $companies = $cStmt->fetchAll();
    foreach ($companies as $comp) {
        $companyIds[] = (int)$comp['id'];
    }
}

// 2. Fetch all interests for this founder's companies
$interests = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'accepted' => 0,
    'declined' => 0
];

if ($db && !empty($companyIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($companyIds), '?'));
    $query = "
        SELECT ii.*, 
               c.name as company_name, c.logo_url as company_logo, c.industry as company_industry,
               u.id as investor_user_id, u.name as investor_name, u.email as investor_email, u.avatar_url as investor_avatar,
               u.city as investor_city, u.country as investor_country,
               ip.investor_type, ip.experience_years, ip.accreditation_status,
               pref.preferred_industries, pref.min_ticket, pref.max_ticket,
               (
                   SELECT c2.id FROM conversations c2
                   WHERE c2.company_id = ii.company_id 
                     AND ((c2.user_one_id = ? AND c2.user_two_id = ii.investor_id) OR (c2.user_one_id = ii.investor_id AND c2.user_two_id = ?))
                   ORDER BY c2.last_message_at DESC LIMIT 1
               ) as conv_id
        FROM investor_interests ii
        JOIN companies c ON ii.company_id = c.id
        JOIN users u ON ii.investor_id = u.id
        LEFT JOIN investor_profiles ip ON u.id = ip.user_id
        LEFT JOIN investor_preferences pref ON u.id = pref.user_id
        WHERE ii.company_id IN ($inPlaceholders)
        ORDER BY 
            CASE WHEN ii.status = 'pending' THEN 1 WHEN ii.status = 'accepted' THEN 2 ELSE 3 END,
            ii.created_at DESC
    ";

    try {
        $params = array_merge([$user['id'], $user['id']], $companyIds);
        $iStmt = $db->prepare($query);
        $iStmt->execute($params);
        $interests = $iStmt->fetchAll();

        foreach ($interests as $item) {
            $stats['total']++;
            if ($item['status'] === 'pending') $stats['pending']++;
            elseif ($item['status'] === 'accepted') $stats['accepted']++;
            elseif ($item['status'] === 'declined') $stats['declined']++;
        }
    } catch (\Throwable $e) {
        $interests = [];
    }
}

// Active Filter from query
$activeFilter = strtolower($_GET['status'] ?? 'all');
if (!in_array($activeFilter, ['all', 'pending', 'accepted', 'declined'])) {
    $activeFilter = 'all';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        .card-clean {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            transition: all 0.2s ease;
        }

        .dark .card-clean {
            background-color: #111827 !important;
            border-color: #1E293B !important;
        }

        .interest-card:hover {
            border-color: #C7D2FE;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.06);
        }

        .dark .interest-card:hover {
            border-color: #4338CA;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100">

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 space-y-6" id="interests-main">

            <!-- Flash Feedback -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center space-x-3 shadow-xs">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
                <div>
                    <div class="flex items-center space-x-2 mb-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                            <i data-lucide="handshake" class="w-3.5 h-3.5"></i>
                            <span>Discovery & Connection</span>
                        </span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        INVESTOR INTEREST
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Review investors who have expressed interest in your company.
                    </p>
                </div>

                <!-- Platform Notice -->
                <div class="text-[11px] text-slate-500 dark:text-slate-400 max-w-sm bg-white dark:bg-slate-800/60 p-3 rounded-xl border border-slate-200 dark:border-slate-700/80">
                    <span class="font-bold text-slate-700 dark:text-slate-200">Nexora helps startups and investors discover opportunities and connect directly.</span>
                </div>
            </div>

            <!-- Metrics Overview Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="card-clean p-4">
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total Interests</div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white"><?= $stats['total'] ?></div>
                </div>
                <div class="card-clean p-4 border-amber-200 dark:border-amber-900/50 bg-amber-50/30 dark:bg-amber-950/20">
                    <div class="text-[11px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-1 flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block animate-pulse"></span>
                        <span>Pending Review</span>
                    </div>
                    <div class="text-2xl font-black text-amber-700 dark:text-amber-400"><?= $stats['pending'] ?></div>
                </div>
                <div class="card-clean p-4 border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/30 dark:bg-emerald-950/20">
                    <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1 flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span>Accepted & Connected</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-700 dark:text-emerald-400"><?= $stats['accepted'] ?></div>
                </div>
                <div class="card-clean p-4">
                    <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Declined</div>
                    <div class="text-2xl font-black text-slate-600 dark:text-slate-400"><?= $stats['declined'] ?></div>
                </div>
            </div>

            <!-- Status Navigation Tabs -->
            <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
                <a href="<?= url('founder/investor_interests.php?status=all') ?>"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 <?= $activeFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                    <span>All</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $activeFilter === 'all' ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' ?>"><?= $stats['total'] ?></span>
                </a>
                <a href="<?= url('founder/investor_interests.php?status=pending') ?>"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 <?= $activeFilter === 'pending' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                    <span>Pending</span>
                    <?php if ($stats['pending'] > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-500 text-white"><?= $stats['pending'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('founder/investor_interests.php?status=accepted') ?>"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 <?= $activeFilter === 'accepted' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                    <span>Accepted</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $activeFilter === 'accepted' ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' ?>"><?= $stats['accepted'] ?></span>
                </a>
                <a href="<?= url('founder/investor_interests.php?status=declined') ?>"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 <?= $activeFilter === 'declined' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                    <span>Declined</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $activeFilter === 'declined' ? 'bg-indigo-700 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300' ?>"><?= $stats['declined'] ?></span>
                </a>
            </div>

            <!-- Interests Feed -->
            <?php
            $displayedInterests = array_filter($interests, function ($item) use ($activeFilter) {
                if ($activeFilter === 'all') return true;
                return $item['status'] === $activeFilter;
            });
            ?>

            <?php if (empty($displayedInterests)): ?>
                <div class="card-clean p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto">
                        <i data-lucide="inbox" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">No investor interests found</h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1">
                            <?= $activeFilter === 'pending'
                                ? 'You have reviewed all pending investor expressions of interest.'
                                : 'When verified investors discover your company on Nexora and express interest, their profiles and messages will appear here.' ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($displayedInterests as $item):
                        $investorIdEncoded = encode_id($item['investor_user_id']);
                        $interestId = (int)$item['id'];
                        $formattedDate = date('M d, Y, h:i A', strtotime($item['created_at']));
                        $avatarUrl = !empty($item['investor_avatar']) ? $item['investor_avatar'] : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120';
                        $ticketStr = '';
                        if (!empty($item['min_ticket']) || !empty($item['max_ticket'])) {
                            $ticketStr = format_inr((float)$item['min_ticket']) . ' - ' . format_inr((float)$item['max_ticket']);
                        }
                    ?>
                        <div class="card-clean interest-card p-5 sm:p-6 space-y-4" id="interest-row-<?= $interestId ?>">
                            
                            <!-- Card Header: Startup Badge & Status -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-bold text-slate-400">Target Company:</span>
                                    <span class="text-xs font-bold text-[#123B7A] dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-1 rounded-lg">
                                        <?= htmlspecialchars($item['company_name']) ?>
                                    </span>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <span class="text-[11px] text-slate-400">Submitted <?= $formattedDate ?></span>
                                    <?php if ($item['status'] === 'pending'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 flex items-center space-x-1">
                                            <i data-lucide="clock" class="w-3 h-3"></i>
                                            <span>PENDING</span>
                                        </span>
                                    <?php elseif ($item['status'] === 'accepted'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 flex items-center space-x-1">
                                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                                            <span>ACCEPTED</span>
                                        </span>
                                    <?php elseif ($item['status'] === 'declined'): ?>
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 flex items-center space-x-1">
                                            <i data-lucide="slash" class="w-3 h-3"></i>
                                            <span>DECLINED</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Investor Profile Row -->
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div class="flex items-start space-x-4">
                                    <a href="<?= url('investor/view.php?id=' . $investorIdEncoded) ?>" class="flex-shrink-0">
                                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($item['investor_name']) ?>"
                                            class="w-14 h-14 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 shadow-2xs hover:scale-105 transition">
                                    </a>
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="<?= url('investor/view.php?id=' . $investorIdEncoded) ?>"
                                                class="text-base font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                <?= htmlspecialchars($item['investor_name']) ?>
                                            </a>
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                <?= htmlspecialchars($item['investor_type'] ?? 'Angel Investor') ?>
                                            </span>
                                            <?php if (!empty($item['accreditation_status'])): ?>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                                    ✓ Verified
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                                            <?php if (!empty($item['experience_years'])): ?>
                                                <span><?= (int)$item['experience_years'] ?>+ Years Investing</span>
                                                <span>•</span>
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($item['investor_city'] ?: 'Bengaluru') ?>, <?= htmlspecialchars($item['investor_country'] ?: 'India') ?></span>
                                            <?php if ($ticketStr): ?>
                                                <span>•</span>
                                                <span>Typical Check: <strong class="text-slate-700 dark:text-slate-300"><?= $ticketStr ?></strong></span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!empty($item['preferred_industries'])): ?>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                                                Focus: <span class="text-slate-700 dark:text-slate-300 font-medium"><?= htmlspecialchars($item['preferred_industries']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Interest Message Box -->
                            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/60 rounded-xl p-4 space-y-1.5">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center space-x-1.5">
                                    <i data-lucide="message-square" class="w-3.5 h-3.5 text-indigo-500"></i>
                                    <span>Investor's Expression of Interest</span>
                                </div>
                                <div class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed font-normal">
                                    “<?= nl2br(htmlspecialchars($item['message'])) ?>”
                                </div>
                            </div>

                            <!-- Action Bar -->
                            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                <a href="<?= url('investor/view.php?id=' . $investorIdEncoded) ?>"
                                    class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center space-x-1.5">
                                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                                    <span>View Profile</span>
                                </a>

                                <div class="flex items-center space-x-2.5">
                                    <?php if ($item['status'] === 'pending'): ?>
                                        <button type="button"
                                            onclick="openDeclineModal(<?= $interestId ?>, '<?= htmlspecialchars(addslashes($item['investor_name'])) ?>', '<?= htmlspecialchars(addslashes($item['company_name'])) ?>')"
                                            class="px-4 py-2 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/30 hover:bg-rose-100 text-rose-700 dark:text-rose-300 text-xs font-bold transition flex items-center space-x-1.5">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
                                            <span>Decline</span>
                                        </button>

                                        <button type="button"
                                            onclick="acceptInterest(<?= $interestId ?>, this)"
                                            class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Accept &amp; Connect</span>
                                        </button>

                                    <?php elseif ($item['status'] === 'accepted'): ?>
                                        <?php if (!empty($item['conv_id'])): ?>
                                            <a href="<?= url('founder/messages.php?conv=' . hash_id_encode($item['conv_id'])) ?>"
                                                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                                <span>Open Conversation →</span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- ==========================================
         DECLINE CONFIRMATION MODAL
         ========================================== -->
    <div id="decline-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200 space-y-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Decline this investor interest?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Confirmation required</p>
                </div>
            </div>

            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed" id="decline-modal-body">
                The investor will receive a respectful notification and polite update informing them that the startup has decided not to continue at this time. No conversation will be created.
            </p>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="closeDeclineModal()"
                    class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="button" id="btn-confirm-decline" onclick="confirmDecline()"
                    class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition flex items-center space-x-1.5 shadow-sm">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    <span>Decline</span>
                </button>
            </div>
        </div>
    </div>

    <!-- CSRF Token Carrier -->
    <input type="hidden" id="csrf-token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <script>
        lucide.createIcons();
        gsap.from("#interests-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });

        let currentDeclineId = null;

        function openDeclineModal(id, investorName, companyName) {
            currentDeclineId = id;
            const modal = document.getElementById('decline-modal');
            const body = document.getElementById('decline-modal-body');
            if (body) {
                body.textContent = `Are you sure you want to decline the interest from ${investorName} for ${companyName}? They will be sent a polite notification and no conversation will be established.`;
            }
            if (modal) {
                modal.classList.remove('hidden');
                lucide.createIcons();
            }
        }

        function closeDeclineModal() {
            currentDeclineId = null;
            const modal = document.getElementById('decline-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        async function acceptInterest(id, btn) {
            if (!confirm('Accept this expression of interest and open direct communications with the investor?')) {
                return;
            }

            const csrfToken = document.getElementById('csrf-token').value;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-1">⟳</span> Connecting...';

            try {
                const formData = new FormData();
                formData.append('interest_id', id);
                formData.append('action', 'accept');
                formData.append('csrf_token', csrfToken);

                const response = await fetch('<?= url("api/founder/respond-interest.php") ?>', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.success) {
                    btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                    btn.classList.add('bg-emerald-800');
                    btn.innerHTML = '✓ Connected!';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(res.error || 'Failed to accept interest.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            } catch (err) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        }

        async function confirmDecline() {
            if (!currentDeclineId) return;

            const btn = document.getElementById('btn-confirm-decline');
            const csrfToken = document.getElementById('csrf-token').value;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="inline-block animate-spin mr-1">⟳</span> Declining...';

            try {
                const formData = new FormData();
                formData.append('interest_id', currentDeclineId);
                formData.append('action', 'decline');
                formData.append('csrf_token', csrfToken);

                const response = await fetch('<?= url("api/founder/respond-interest.php") ?>', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.success) {
                    closeDeclineModal();
                    window.location.reload();
                } else {
                    alert(res.error || 'Failed to decline interest.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            } catch (err) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        }
    </script>
</body>

</html>
