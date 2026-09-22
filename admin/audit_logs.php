<?php
/**
 * Admin Module: Platform Security & Immutable Audit Logs
 * Clean White / Light Theme, Small Crisp Typography
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Security & Immutable Audit Trail';

$logs = [];
$filterAction = trim($_GET['action_filter'] ?? '');

if ($db) {
    $query = "
        SELECT al.*, u.name as actor_name, u.email as actor_email, u.role as actor_role
        FROM audit_logs al
        LEFT JOIN users u ON al.actor_user_id = u.id
    ";
    $params = [];
    if (!empty($filterAction)) {
        $query .= " WHERE al.action LIKE ?";
        $params[] = "%{$filterAction}%";
    }
    $query .= " ORDER BY al.created_at DESC LIMIT 100";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Trail • <?= APP_NAME ?></title>
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

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="audit-admin-main">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg md:text-xl font-extrabold text-slate-900 tracking-tight">Security & Immutable Audit Trail</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Full auditability for verification, funding, investment, and account security events.</p>
                </div>
            </div>

            <!-- Filter -->
            <div class="card-clean rounded-2xl p-4">
                <form action="<?= url('admin/audit_logs.php') ?>" method="GET" class="flex items-center space-x-3 text-xs">
                    <div class="flex-1 relative">
                        <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="action_filter" value="<?= htmlspecialchars($filterAction) ?>" placeholder="Filter by event action (e.g., CREATE_FUNDING_ROUND, EXECUTE_INVESTMENT)..."
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-slate-900 text-xs outline-none">
                    </div>
                    <button type="submit" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-xs transition shadow-sm">
                        Filter Trail
                    </button>
                    <?php if (!empty($filterAction)): ?>
                        <a href="<?= url('admin/audit_logs.php') ?>" class="p-2 border border-slate-200 hover:bg-slate-50 rounded-lg text-slate-600 transition" title="Reset filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table -->
            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider font-sans text-[10.5px]">
                                <th class="pb-3 font-semibold">Timestamp</th>
                                <th class="pb-3 font-semibold">Actor</th>
                                <th class="pb-3 font-semibold">Action</th>
                                <th class="pb-3 font-semibold">Entity Type</th>
                                <th class="pb-3 font-semibold">Details</th>
                                <th class="pb-3 font-semibold">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs font-sans text-slate-400">No audit logs matching query found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $l): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3 text-slate-500 text-[11px]"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
                                        <td class="py-3 font-sans">
                                            <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($l['actor_name'] ?? 'System / Anonymous') ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($l['actor_role'] ?? 'Guest') ?></div>
                                        </td>
                                        <td class="py-3 font-bold text-indigo-600 text-xs"><?= htmlspecialchars($l['action']) ?></td>
                                        <td class="py-3 text-slate-700 text-xs"><?= htmlspecialchars($l['entity_type']) ?> #<?= $l['entity_id'] ?></td>
                                        <td class="py-3 text-slate-500 max-w-xs truncate font-sans text-xs" title="<?= htmlspecialchars($l['details'] ?? '') ?>">
                                            <?= htmlspecialchars($l['details'] ?? '—') ?>
                                        </td>
                                        <td class="py-3 text-slate-400 text-xs"><?= htmlspecialchars($l['ip_address']) ?></td>
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
        gsap.from("#audit-admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
