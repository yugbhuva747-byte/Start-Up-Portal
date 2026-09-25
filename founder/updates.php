<?php
/**
 * Founder Module: Startup Investor Updates & Newsfeed
 * Implements Section 17 & 23 (Structured investor update / news feed)
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Investor Updates & Milestones';

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
    }
}

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>
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

    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="updates-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center space-x-2.5">
                        <i data-lucide="newspaper" class="w-6 h-6 text-indigo-600"></i>
                        <span>Investor Updates & Traction Feed</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Share monthly revenue progress, client logos, hiring, and product releases with your investors.</p>
                </div>
                <button onclick="document.getElementById('new-update-box').scrollIntoView({behavior: 'smooth'})" class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition flex items-center space-x-1.5 self-start sm:self-auto">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Publish New Update</span>
                </button>
            </div>

            <!-- New Update Composer Form -->
            <div id="new-update-box" class="card-clean rounded-2xl p-6">
                <div class="flex items-center space-x-2 pb-4 border-b border-slate-100">
                    <i data-lucide="send" class="w-4 h-4 text-indigo-600"></i>
                    <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Publish Stakeholder Update</h2>
                </div>

                <?php if (!$company): ?>
                    <div class="p-6 text-center text-xs text-slate-500">
                        Please set up your company profile first to publish investor updates.
                    </div>
                <?php else: ?>
                    <form action="<?= url('founder/updates.php') ?>" method="POST" class="mt-4 space-y-4 text-xs">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="create_update">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Headline / Title</label>
                                <input type="text" name="title" required placeholder="e.g. Q3 2026 Growth: ARR crossed ₹2.5 Cr with 140% YoY expansion"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Category</label>
                                <select name="category" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <option value="Traction & Revenue">Traction & Revenue</option>
                                    <option value="Milestone">Milestone</option>
                                    <option value="Product Launch">Product Launch</option>
                                    <option value="Team & Hiring">Team & Hiring</option>
                                    <option value="Financials">Financials</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Key Metrics Highlights (Badge String)</label>
                                <input type="text" name="metrics_summary" placeholder="e.g. +140% YoY • ₹2.5 Cr ARR • 98% Net Retention"
                                       class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Audience Visibility</label>
                                <select name="visibility" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <option value="all_investors">All Registered Investors & Discover Page</option>
                                    <option value="portfolio_only">Confirmed Portfolio Investors Only</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Detailed Update Body</label>
                            <textarea name="content" rows="4" required placeholder="Describe key accomplishments, customer wins, product roadmap progress, revenue runway, and areas where investors can help (intros, hiring, advice)..."
                                      class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition leading-relaxed"></textarea>
                        </div>

                        <div class="flex items-center justify-end pt-2">
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center space-x-1.5">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Broadcast Update</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Published Updates List -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                    <i data-lucide="history" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Published Updates Archive (<?= count($updates) ?>)</span>
                </h3>

                <?php if (empty($updates)): ?>
                    <div class="card-clean rounded-2xl p-10 text-center text-xs text-slate-400">
                        No updates published yet. Share your first milestone above!
                    </div>
                <?php else: ?>
                    <?php foreach ($updates as $upd): ?>
                        <div class="card-clean rounded-2xl p-5 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold">
                                            <?= htmlspecialchars($upd['category']) ?>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full <?= $upd['visibility'] === 'portfolio_only' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?> text-[10px] font-semibold">
                                            <?= $upd['visibility'] === 'portfolio_only' ? 'Portfolio Exclusive' : 'Public Discovery' ?>
                                        </span>
                                        <span class="text-[11px] text-slate-400">
                                            <?= date('F d, Y • h:i A', strtotime($upd['created_at'])) ?>
                                        </span>
                                    </div>
                                    <h2 class="text-sm md:text-base font-bold text-slate-900 mt-1"><?= htmlspecialchars($upd['title']) ?></h2>
                                </div>
                                <form action="<?= url('founder/updates.php') ?>" method="POST" onsubmit="return confirm('Delete this update?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="form_action" value="delete_update">
                                    <input type="hidden" name="update_id" value="<?= $upd['id'] ?>">
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Delete Update">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>

                            <?php if (!empty($upd['metrics_summary'])): ?>
                                <div class="px-3.5 py-2 rounded-lg bg-slate-50 border border-slate-200/80 text-xs font-bold text-emerald-700 flex items-center space-x-2">
                                    <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
                                    <span><?= htmlspecialchars($upd['metrics_summary']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">
                                <?= htmlspecialchars($upd['content']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#updates-main", { duration: 0.35, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
