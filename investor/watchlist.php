<?php
/**
 * Investor Module: Watchlist & Saved Startups
 * User-friendly, clean, and modern tracking interface
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
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_watchlist') {
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
    <?php include __DIR__ . '/../includes/investor/head.php'; ?>
</head>

<body class="bg-slate-50 text-slate-900 flex min-h-screen dark:bg-[#0B0F19] dark:text-slate-100 antialiased selection:bg-blue-600 selection:text-white">

    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6" id="watchlist-main">

            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-xs sm:text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800/60' ?> flex items-center space-x-2.5 shadow-xs">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header Banner -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="text-xs font-bold text-blue-600 dark:text-blue-400 tracking-wider uppercase mb-1.5 flex items-center gap-2">
                        <span>Portfolio Tracking</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span class="text-slate-500 dark:text-slate-400 font-semibold"><?= count($watchlist) ?> Shortlisted Deals</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Saved Watchlist & Pipeline
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        Startups, pitch decks, and live syndicate rounds you are actively monitoring and evaluating.
                    </p>
                </div>
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <a href="<?= url('investor/discover.php') ?>"
                        class="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                        <i data-lucide="compass" class="w-4 h-4"></i>
                        <span>Discover More Deals</span>
                    </a>
                </div>
            </div>

            <!-- Saved Companies List -->
            <?php if (empty($watchlist)): ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl p-12 text-center text-xs text-slate-500 dark:text-slate-400 shadow-xs">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="bookmark" class="w-7 h-7"></i>
                    </div>
                    <div class="text-base font-extrabold text-slate-900 dark:text-white mb-1">Your saved list is empty</div>
                    <p class="max-w-md mx-auto text-slate-500 dark:text-slate-400 leading-relaxed">
                        Bookmark interesting ventures while exploring the startup directory to monitor their progress, metrics, and deal rooms right here.
                    </p>
                    <a href="<?= url('investor/discover.php') ?>"
                        class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Explore Startups</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden shadow-xs">
                    <?php foreach ($watchlist as $s):
                        $pct = ($s['target_amount'] ?? 0) > 0 ? round(($s['amount_raised'] / $s['target_amount']) * 100) : 0;
                        $hashId = hash_id_encode($s['id']);
                    ?>
                        <div class="p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <!-- Startup Identity & Pitch -->
                            <div class="flex items-start space-x-4 min-w-0 flex-1">
                                <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100' ?>"
                                    class="w-12 h-12 rounded-2xl object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0 shadow-2xs">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                            class="text-base font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition truncate">
                                            <?= htmlspecialchars($s['name']) ?>
                                        </a>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            <?= htmlspecialchars($s['industry']) ?>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300">
                                            <?= htmlspecialchars($s['stage']) ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 mt-1 leading-relaxed">
                                        <?= htmlspecialchars($s['pitch']) ?>
                                    </p>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-2">
                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                        <span>Saved on <?= date('d M Y', strtotime($s['saved_at'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Metrics & Actions -->
                            <div class="flex items-center justify-between md:justify-end gap-5 text-xs flex-shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-slate-800">
                                <?php if (!empty($s['target_amount'])): ?>
                                    <div class="text-left md:text-right">
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Target</span>
                                        <span class="font-extrabold text-slate-900 dark:text-white"><?= format_inr($s['target_amount']) ?></span>
                                    </div>
                                    <div class="text-left md:text-right">
                                        <span class="text-[10px] text-slate-400 uppercase font-semibold block">Raised</span>
                                        <span class="font-bold text-blue-600 dark:text-blue-400"><?= $pct ?>%</span>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center space-x-2">
                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>"
                                        class="px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-bold text-xs transition">
                                        Open Deal Room →
                                    </a>

                                    <a href="<?= url('company.php?id=' . $hashId) ?>" target="_blank"
                                        class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition"
                                        title="Public Dossier">
                                        <i data-lucide="external-link" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Remove bookmark form -->
                                    <form action="<?= url('investor/watchlist.php') ?>" method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="remove_watchlist">
                                        <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                        <button type="submit" title="Remove from Watchlist"
                                            class="p-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-amber-500 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/50 transition">
                                            <i data-lucide="bookmark-minus" class="w-4 h-4"></i>
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
        if (window.gsap) {
            gsap.from("#watchlist-main", { duration: 0.4, y: 8, opacity: 0, ease: "power2.out" });
        }
    </script>
</body>

</html>