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

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="watchlist-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Saved Deal Watchlist</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Keep track of startups preparing to open funding or undergoing clinical/product milestones.</p>
                </div>
                <a href="<?= url('investor/discover.php') ?>" class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center space-x-1.5">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Discover More Startups</span>
                </a>
            </div>

            <?php if (empty($watchlist)): ?>
                <div class="card-clean rounded-2xl p-10 text-center text-slate-400 text-xs">
                    <i data-lucide="bookmark" class="w-10 h-10 text-slate-300 mx-auto mb-2.5"></i>
                    <div class="text-xs font-bold text-slate-800 mb-1">Your watchlist is currently empty</div>
                    <div class="text-[11px] text-slate-500">Click the bookmark icon on any startup card in Discovery to pin it here.</div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($watchlist as $s): 
                        $pct = ($s['target_amount'] ?? 0) > 0 ? round(($s['amount_raised'] / $s['target_amount']) * 100) : 0;
                        $hashId = hash_id_encode($s['id']);
                    ?>
                        <div class="card-clean rounded-2xl p-5 flex flex-col justify-between hover:border-indigo-300 transition duration-200 relative group">
                            
                            <!-- Remove button -->
                            <form action="<?= url('investor/watchlist.php') ?>" method="POST" class="absolute top-4 right-4 z-10">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="remove_watchlist">
                                <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                <button type="submit" title="Remove" class="p-1.5 rounded-lg bg-slate-50 hover:bg-rose-50 text-amber-500 hover:text-rose-600 border border-slate-200 transition">
                                    <i data-lucide="bookmark" class="w-3.5 h-3.5 fill-amber-500"></i>
                                </button>
                            </form>

                            <div>
                                <div class="flex items-start space-x-3 mb-3.5 pr-8">
                                    <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200 flex-shrink-0">
                                    <div class="min-w-0">
                                        <h3 class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($s['name']) ?></h3>
                                        <div class="flex items-center space-x-1.5 mt-0.5">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <?= htmlspecialchars($s['industry']) ?>
                                            </span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                <?= htmlspecialchars($s['stage']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 line-clamp-2 mb-3 leading-relaxed"><?= htmlspecialchars($s['pitch']) ?></p>
                            </div>

                            <div class="pt-3 border-t border-slate-100">
                                <?php if (!empty($s['target_amount'])): ?>
                                    <div class="flex justify-between text-[11px] mb-1 font-semibold">
                                        <span class="text-slate-500">Raised: <?= format_inr($s['amount_raised']) ?></span>
                                        <span class="text-emerald-700 font-bold"><?= $pct ?>%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mb-3 border border-slate-200">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                    </div>
                                <?php endif; ?>

                                <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="w-full py-2 rounded-lg bg-indigo-50 hover:bg-indigo-600 text-center text-xs font-semibold text-indigo-700 hover:text-white block transition duration-150">
                                    Open Diligence Deal Room →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#watchlist-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
