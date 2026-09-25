<?php
/**
 * Admin Module: Funding Round Review & Approvals
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Funding Round Compliance Review';

$rounds = [];
$error = '';
$flash = get_flash();

if ($db) {
    $stmt = $db->query("
        SELECT fr.*, c.name as company_name, c.cin_number, c.industry, c.stage, u.name as founder_name
        FROM funding_rounds fr
        JOIN companies c ON fr.company_id = c.id
        LEFT JOIN company_founders cf ON c.id = cf.company_id
        LEFT JOIN users u ON cf.user_id = u.id
        ORDER BY FIELD(fr.status, 'SUBMITTED', 'UNDER_REVIEW', 'APPROVED', 'LIVE', 'PARTIALLY_FUNDED', 'FULLY_FUNDED', 'CLOSED', 'REJECTED'), fr.created_at DESC
    ");
    $rounds = $stmt->fetchAll();
}

// Handle Status Change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $roundId = (int)($_POST['round_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';

        if ($roundId > 0 && in_array($newStatus, ['LIVE', 'APPROVED', 'REJECTED', 'CLOSED'])) {
            $db->prepare("UPDATE funding_rounds SET status = ? WHERE id = ?")->execute([$newStatus, $roundId]);
            log_audit($user['id'], 'UPDATE_ROUND_STATUS', 'funding_rounds', $roundId, "Admin updated status to {$newStatus}");
            set_flash('success', "Funding round status updated to {$newStatus}.");
            header('Location: ' . url('admin/funding_review.php'));
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
    <title>Funding Approvals • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="rounds-admin-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-medium border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?>">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div>
                <h1 class="text-lg md:text-xl font-extrabold text-slate-900 tracking-tight">Funding Round Approvals</h1>
                <p class="text-xs text-slate-500 mt-0.5">Review valuation justifications, capitalization table limits, and approve rounds for discovery.</p>
            </div>

            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider text-[10.5px]">
                                <th class="pb-3 font-semibold">Startup / Entity</th>
                                <th class="pb-3 font-semibold">Round Name</th>
                                <th class="pb-3 font-semibold">Target Capital</th>
                                <th class="pb-3 font-semibold">Pre-Money Val.</th>
                                <th class="pb-3 font-semibold">Equity</th>
                                <th class="pb-3 font-semibold">Status</th>
                                <th class="pb-3 font-semibold text-right">Approval Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($rounds)): ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs text-slate-400">No funding rounds currently awaiting review.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rounds as $r): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5">
                                            <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($r['company_name']) ?></div>
                                            <div class="text-[11px] text-slate-500"><?= htmlspecialchars($r['industry']) ?> • <?= htmlspecialchars($r['stage']) ?></div>
                                        </td>
                                        <td class="py-3.5 text-slate-700 font-semibold text-xs"><?= htmlspecialchars($r['round_name']) ?></td>
                                        <td class="py-3.5 font-bold text-slate-900 text-xs"><?= format_inr($r['target_amount']) ?></td>
                                        <td class="py-3.5 text-slate-600 text-xs"><?= format_inr($r['valuation']) ?></td>
                                        <td class="py-3.5 font-bold text-emerald-600 text-xs"><?= $r['equity_offered'] ?>%</td>
                                        <td class="py-3.5"><?= render_status_badge($r['status']) ?></td>
                                        <td class="py-3.5 text-right space-x-1.5">
                                            <?php if (in_array($r['status'], ['SUBMITTED', 'UNDER_REVIEW'])): ?>
                                                <form action="<?= url('admin/funding_review.php') ?>" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="new_status" value="LIVE">
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition shadow-sm">
                                                        Approve & Publish
                                                    </button>
                                                </form>
                                                <form action="<?= url('admin/funding_review.php') ?>" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="new_status" value="REJECTED">
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px] transition">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-slate-400 text-[11px]">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#rounds-admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
