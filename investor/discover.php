<?php
/**
 * Investor Module: Startup Discovery & Structured Search
 * Clean White / Light Theme, Small Crisp Typography, Professional Look
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Discover Startups & Deal Flow';

$search = trim($_GET['q'] ?? '');
$industry = trim($_GET['industry'] ?? '');
$stage = trim($_GET['stage'] ?? '');
$location = trim($_GET['location'] ?? '');
$minFunding = (float)($_GET['min_funding'] ?? 0);

$startups = [];
$watchlistIds = [];

if ($db) {
    $wlStmt = $db->prepare("SELECT company_id FROM watchlists WHERE investor_user_id = ?");
    $wlStmt->execute([$user['id']]);
    $watchlistIds = $wlStmt->fetchAll(PDO::FETCH_COLUMN);

    $query = "
        SELECT c.*, 
               fr.id as round_id, fr.round_name, fr.target_amount, fr.amount_raised, fr.min_investment, fr.valuation, fr.equity_offered, fr.status as round_status,
               u.name as founder_name, u.avatar_url as founder_avatar
        FROM companies c
        LEFT JOIN funding_rounds fr ON c.id = fr.company_id
        LEFT JOIN company_founders cf ON c.id = cf.company_id
        LEFT JOIN users u ON cf.user_id = u.id
        WHERE c.verified_status = 'verified'
    ";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (c.name LIKE ? OR c.pitch LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
        $sTerm = "%{$search}%";
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
    }

    if (!empty($industry)) {
        $query .= " AND c.industry = ?";
        $params[] = $industry;
    }

    if (!empty($stage)) {
        $query .= " AND c.stage = ?";
        $params[] = $stage;
    }

    if (!empty($location)) {
        $query .= " AND (c.city LIKE ? OR c.state LIKE ?)";
        $params[] = "%{$location}%";
        $params[] = "%{$location}%";
    }

    if ($minFunding > 0) {
        $query .= " AND fr.target_amount >= ?";
        $params[] = $minFunding;
    }

    $query .= " ORDER BY fr.amount_raised DESC, c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $startups = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_watchlist') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $compHash = $_POST['company_id'] ?? '';
        $targetCompId = hash_id_decode($compHash);
        if ($targetCompId > 0) {
            $chk = $db->prepare("SELECT id FROM watchlists WHERE investor_user_id = ? AND company_id = ?");
            $chk->execute([$user['id'], $targetCompId]);
            if ($chk->fetch()) {
                $db->prepare("DELETE FROM watchlists WHERE investor_user_id = ? AND company_id = ?")->execute([$user['id'], $targetCompId]);
            } else {
                $db->prepare("INSERT INTO watchlists (investor_user_id, company_id) VALUES (?, ?)")->execute([$user['id'], $targetCompId]);
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
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
    <title>Discover Startups • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
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

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="discover-main">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Discover Early-Stage Deal Flow</h1>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">Search MCA & DigiLocker verified startups raising active capital.</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card-clean rounded-2xl p-4">
                <form action="<?= url('investor/discover.php') ?>" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-2.5 text-xs">
                    
                    <div class="md:col-span-2 relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search startups, keywords, founders..."
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-emerald-600 transition">
                    </div>

                    <div>
                        <select name="industry" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 outline-none focus:bg-white focus:border-emerald-600 transition">
                            <option value="">All Sectors</option>
                            <?php foreach (['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech'] as $ind): ?>
                                <option value="<?= $ind ?>" <?= $industry === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <select name="stage" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-700 outline-none focus:bg-white focus:border-emerald-600 transition">
                            <option value="">All Stages</option>
                            <?php foreach (['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A'] as $stg): ?>
                                <option value="<?= $stg ?>" <?= $stage === $stg ? 'selected' : '' ?>><?= $stg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center space-x-1.5">
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm transition flex items-center justify-center space-x-1">
                            <i data-lucide="filter" class="w-3 h-3"></i>
                            <span>Filter</span>
                        </button>
                        <?php if (!empty($search) || !empty($industry) || !empty($stage)): ?>
                            <a href="<?= url('investor/discover.php') ?>" title="Reset" class="p-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Startups Grid -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-slate-500 font-semibold">Showing <strong class="text-slate-800"><?= count($startups) ?></strong> verified opportunities</span>
                </div>

                <?php if (empty($startups)): ?>
                    <div class="card-clean rounded-2xl p-8 text-center text-slate-400 text-xs">
                        <i data-lucide="search-x" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                        <div class="text-xs font-bold text-slate-700 mb-0.5">No startups match your filters</div>
                        <div class="text-[11px]">Try clearing some search filters.</div>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5" id="deal-cards">
                        <?php foreach ($startups as $s): 
                            $pct = ($s['target_amount'] ?? 0) > 0 ? round(($s['amount_raised'] / $s['target_amount']) * 100) : 0;
                            $hashId = hash_id_encode($s['id']);
                            $isSaved = in_array($s['id'], $watchlistIds);
                        ?>
                            <div class="card-clean rounded-2xl p-5 flex flex-col justify-between relative group">
                                
                                <form action="<?= url('investor/discover.php') ?>" method="POST" class="absolute top-4 right-4 z-10">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_watchlist">
                                    <input type="hidden" name="company_id" value="<?= $hashId ?>">
                                    <button type="submit" class="p-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 transition <?= $isSaved ? 'text-amber-500' : 'text-slate-400 hover:text-slate-700' ?>">
                                        <i data-lucide="bookmark" class="w-3.5 h-3.5 <?= $isSaved ? 'fill-amber-500' : '' ?>"></i>
                                    </button>
                                </form>

                                <div>
                                    <div class="flex items-start space-x-2.5 mb-3 pr-8">
                                        <img src="<?= $s['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200 flex-shrink-0">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($s['name']) ?></h3>
                                            <div class="flex items-center space-x-1 mt-0.5">
                                                <span class="px-2 py-0.5 rounded-full text-[9.5px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                    <?= htmlspecialchars($s['industry']) ?>
                                                </span>
                                                <span class="px-2 py-0.5 rounded-full text-[9.5px] font-semibold bg-slate-100 text-slate-600">
                                                    <?= htmlspecialchars($s['stage']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="text-xs text-slate-500 line-clamp-2 mb-3 leading-relaxed"><?= htmlspecialchars($s['pitch']) ?></p>

                                    <div class="text-[10.5px] text-slate-400 flex items-center space-x-1.5 mb-3">
                                        <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
                                        <span><?= htmlspecialchars($s['city']) ?>, <?= htmlspecialchars($s['country']) ?></span>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-slate-100">
                                    <?php if (!empty($s['target_amount'])): ?>
                                        <div class="flex justify-between text-[11px] mb-1 font-semibold">
                                            <span class="text-slate-500">Raised: <?= format_inr($s['amount_raised']) ?></span>
                                            <span class="text-emerald-700 font-bold"><?= $pct ?>%</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mb-3 border border-slate-200">
                                            <div class="h-full bg-emerald-600 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                        </div>
                                        <div class="flex items-center justify-between text-[10.5px] text-slate-500 mb-3">
                                            <span>Min: <strong class="text-slate-800"><?= format_inr($s['min_investment']) ?></strong></span>
                                            <span>Val: <strong class="text-slate-800"><?= format_inr($s['valuation']) ?></strong></span>
                                        </div>
                                    <?php endif; ?>

                                    <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="w-full py-2 rounded-lg bg-slate-50 hover:bg-emerald-600 hover:text-white border border-slate-200 text-center text-xs font-semibold text-slate-800 block transition">
                                        Review Deal Terms →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#discover-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
