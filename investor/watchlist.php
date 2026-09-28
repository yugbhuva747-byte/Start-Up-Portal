<?php
/**
 * Investor Module: Watchlist & Saved Startups
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Watchlist & Tracked Startups';

$watchlist = [];
$flash = get_flash();

if ($db) {
    $stmt = $db->prepare("
        SELECT c.*, 
               fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment, fr.valuation, fr.status as round_status,
               w.created_at as saved_at
        FROM watchlists w
        JOIN companies c ON w.company_id = c.id
        LEFT JOIN funding_rounds fr ON c.id = fr.company_id
        WHERE w.investor_user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $watchlist = $stmt->fetchAll();
}

// Remove from watchlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_watchlist') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $compId = hash_id_decode($_POST['company_id'] ?? '');
        if ($compId > 0) {
            $db->prepare("DELETE FROM watchlists WHERE investor_user_id = ? AND company_id = ?")->execute([$user['id'], $compId]);
            set_flash('info', 'Startup removed from watchlist.');
            header('Location: ' . url('investor/watchlist.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Watchlist • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

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

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-4 sm:p-6 md:p-8 lg:p-10 space-y-8 sm:space-y-10 w-full mx-auto" id="watchlist-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-[#EAF2FF] dark:bg-blue-950/40 text-[#123B7A] dark:text-blue-300 border-[#123B7A]/20' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200' ?> flex items-center space-x-2.5 shadow-2xs">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Identity Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-[#E4E8EF] dark:border-slate-800 pb-6">
                <div>
                    <div class="text-xs font-bold text-[#123B7A] dark:text-blue-400 tracking-wider uppercase mb-1 flex items-center gap-2">
                        <span>Portfolio Tracking</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 pulse-beacon"></span>
                        <span class="text-[#667085] dark:text-slate-400 font-semibold">Shortlisted Deals</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-[#0B1F3A] dark:text-white tracking-tight">Saved Companies & Deals</h1>
                    <p class="text-sm sm:text-base text-[#667085] dark:text-slate-300 mt-1.5">Startups and live syndicates you are actively monitoring and evaluating.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="<?= url('investor/discover.php') ?>"
                        class="px-5 py-3 rounded-xl bg-[#123B7A] hover:bg-[#0B1F3A] dark:bg-blue-600 dark:hover:bg-blue-700 text-white text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Discover More Deals</span>
                    </a>
                </div>
            </div>

            <!-- Saved Companies List (Horizontal Rows, Minimal Borders, Strong Typography) -->
            <?php if (empty($watchlist)): ?>
                <div class="bg-white border border-[#E4E8EF] rounded-xl p-12 text-center text-xs text-[#667085]">
                    <div class="w-12 h-12 rounded-full bg-[#FAFBFD] border border-[#E4E8EF] text-[#667085] flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="bookmark" class="w-5 h-5"></i>
                    </div>
                    <div class="text-sm font-bold text-[#0B1F3A] mb-1">Your saved list is empty</div>
                    <p class="max-w-md mx-auto text-[#667085] leading-relaxed">
                        Pin interesting deals while browsing the discovery marketplace to evaluate their metrics, traction, and data room here.
                    </p>
                    <a href="<?= url('investor/discover.php') ?>" class="inline-block mt-4 text-[#123B7A] font-bold hover:underline">
                        Explore Startups →
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white border border-[#E4E8EF] rounded-xl divide-y divide-[#E4E8EF]">
                    <?php foreach ($watchlist as $s):
                        $pct = ($s['target_amount'] ?? 0) > 0 ? round(($s['amount_raised'] / $s['target_amount']) * 100) : 0;
                        $hashId = hash_id_encode($s['id']);
                    ?>
                        <div class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-[#FAFBFD] transition">
                            <!-- Startup Identity & Pitch -->
                            <div class="flex items-start space-x-4 min-w-0 flex-1">
                                <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>"
                                    class="w-12 h-12 rounded-xl object-cover border border-[#E4E8EF] flex-shrink-0">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                            class="text-sm font-bold text-[#0B1F3A] hover:text-[#123B7A] transition truncate">
                                            <?= htmlspecialchars($s['name']) ?>
                                        </a>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBFD] text-[#667085] border border-[#E4E8EF]">
                                            <?= htmlspecialchars($s['industry']) ?>
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-[#FAFBFD] text-[#667085] border border-[#E4E8EF]">
                                            <?= htmlspecialchars($s['stage']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#667085] line-clamp-1 mt-1 leading-relaxed">
                                        <?= htmlspecialchars($s['pitch']) ?>
                                    </p>
                                    <div class="text-[10.5px] text-[#667085] mt-1">
                                        <span>Saved on <?= date('d M Y', strtotime($s['saved_at'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Metrics & Actions -->
                            <div class="flex items-center justify-between md:justify-end gap-6 text-xs flex-shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-[#E4E8EF]">
                                <?php if (!empty($s['target_amount'])): ?>
                                    <div class="text-left md:text-right">
                                        <span class="text-[10px] text-[#667085] block">Target</span>
                                        <span class="font-bold text-[#111827]"><?= format_inr($s['target_amount']) ?></span>
                                    </div>
                                    <div class="text-left md:text-right">
                                        <span class="text-[10px] text-[#667085] block">Raised</span>
                                        <span class="font-bold text-[#123B7A]"><?= $pct ?>%</span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center space-x-2">
                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                        class="px-4 py-2 rounded-lg bg-[#FAFBFD] hover:bg-[#EAF2FF] text-[#123B7A] hover:text-[#0B1F3A] border border-[#E4E8EF] font-bold text-xs transition">
                                        Open Deal Room →
                                    </a>

                                    <!-- Remove bookmark form -->
                                    <form action="<?= url('investor/watchlist.php') ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="remove_watchlist">
                                        <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                        <button type="submit" title="Remove from Saved"
                                            class="p-2 rounded-lg bg-[#FAFBFD] hover:bg-rose-50 text-amber-500 hover:text-rose-600 border border-[#E4E8EF] transition">
                                            <i data-lucide="bookmark" class="w-3.5 h-3.5 fill-amber-500"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#watchlist-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>