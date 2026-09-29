<?php
/**
 * Admin Module: Platform Security & Immutable Audit Logs
 * Clean, Minimalist Immutable Audit Trail
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Security Audit Trail';

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
</head>
<body class="bg-[#F8FAFC] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl w-full mx-auto" id="audit-admin-main">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="history" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Security Audit Trail
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Immutable administrative audit log for verification, funding, investment, and access events.</p>
                    </div>
                </div>
                <div class="text-xs text-slate-400 font-medium">
                    Showing latest <span class="font-bold text-indigo-600"><?= count($logs) ?></span> events
                </div>
            </div>

            <!-- Filter -->
            <div class="admin-card p-3 sm:p-4">
                <form action="<?= url('admin/audit_logs.php') ?>" method="GET" class="flex flex-col sm:flex-row items-center gap-3 text-xs">
                    <div class="admin-search-wrapper w-full flex-1">
                        <i data-lucide="search"></i>
                        <input type="text" name="action_filter" value="<?= htmlspecialchars($filterAction) ?>" placeholder="Filter event action (e.g. UPDATE_USER_STATUS, VERIFY_COMPANY)..."
                               class="admin-input w-full">
                    </div>
                    <div class="flex items-center space-x-2 w-full sm:w-auto">
                        <button type="submit" class="admin-btn-primary">
                            Filter
                        </button>
                        <?php if (!empty($filterAction)): ?>
                            <a href="<?= url('admin/audit_logs.php') ?>" class="admin-btn-secondary" title="Reset filter">
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="admin-table-container">
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Actor</th>
                                <th>Action</th>
                                <th>Target Entity</th>
                                <th>Details</th>
                                <th class="text-right">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">No audit logs matching query found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td class="text-slate-500 text-xs whitespace-nowrap">
                                            <div><?= date('d M Y', strtotime($l['created_at'])) ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= date('H:i:s', strtotime($l['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($l['actor_name'] ?? 'System') ?></div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= ucfirst($l['actor_role'] ?? 'Service') ?></div>
                                        </td>
                                        <td>
                                            <span class="admin-badge admin-badge-primary text-[10px] font-mono">
                                                <?= htmlspecialchars($l['action']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-medium text-slate-800 text-xs"><?= htmlspecialchars($l['entity_type'] ?? '—') ?></div>
                                            <?php if ($l['entity_id']): ?>
                                                <div class="text-[10px] text-slate-400 font-mono">#<?= $l['entity_id'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="max-w-xs truncate text-slate-600 text-xs" title="<?= htmlspecialchars($l['details'] ?? '') ?>">
                                            <?= htmlspecialchars($l['details'] ?? '—') ?>
                                        </td>
                                        <td class="text-right font-mono text-slate-400 text-xs whitespace-nowrap">
                                            <?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?>
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
        gsap.from("#audit-admin-main", { duration: 0.3, y: 8, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
