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
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        body { font-family: "Vay Portal", Sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#F4F2EE] text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="audit-admin-main">
            
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
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-900 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 rounded-xl text-slate-900 dark:text-slate-100 text-xs outline-none">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-xs flex items-center space-x-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Filter Trail</span>
                    </button>
                    <?php if (!empty($filterAction)): ?>
                        <a href="<?= url('admin/audit_logs.php') ?>" class="p-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl text-slate-600 transition" title="Reset filter">
                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Card -->
            <div class="card-clean rounded-2xl p-5 md:p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center space-x-2">
                        <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Immutable Activity Log Records</h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                            <?= count($logs) ?> Events
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400">Click any row or 'Details' button to inspect full payload</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-400 uppercase tracking-wider font-sans text-[10.5px]">
                                <th class="pb-3 font-semibold">Timestamp</th>
                                <th class="pb-3 font-semibold">Actor</th>
                                <th class="pb-3 font-semibold">Action</th>
                                <th class="pb-3 font-semibold">Entity</th>
                                <th class="pb-3 font-semibold">Details Summary</th>
                                <th class="pb-3 font-semibold">IP Address</th>
                                <th class="pb-3 font-semibold text-right">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-xs font-sans text-slate-400">No audit logs matching query found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $idx => $l): ?>
                                    <tr class="hover:bg-slate-50/60 transition cursor-pointer group" onclick="toggleAuditRow('row-detail-<?= $idx ?>', this)">
                                        <td class="py-3 text-slate-500 text-[11px] whitespace-nowrap"><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></td>
                                        <td class="py-3 font-sans">
                                            <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($l['actor_name'] ?? 'System / Anonymous') ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($l['actor_role'] ?? 'Guest') ?></div>
                                        </td>
                                        <td class="py-3 font-bold text-blue-600 text-xs whitespace-nowrap"><?= htmlspecialchars($l['action']) ?></td>
                                        <td class="py-3 text-slate-700 text-xs whitespace-nowrap"><?= htmlspecialchars($l['entity_type']) ?> #<?= $l['entity_id'] ?></td>
                                        <td class="py-3 text-slate-500 max-w-xs truncate font-sans text-xs" title="<?= htmlspecialchars($l['details'] ?? '') ?>">
                                            <?= htmlspecialchars($l['details'] ?? '—') ?>
                                        </td>
                                        <td class="py-3 text-slate-400 text-xs whitespace-nowrap"><?= htmlspecialchars($l['ip_address']) ?></td>
                                        <td class="py-3 text-right">
                                            <button type="button" class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 font-sans font-semibold text-[11px] transition inline-flex items-center space-x-1">
                                                <span>More</span>
                                                <i data-lucide="chevron-down" data-row-chevron class="w-3 h-3 transition-transform duration-200"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Full Details Drawer Row -->
                                    <tr id="row-detail-<?= $idx ?>" class="hidden bg-slate-50/60 dark:bg-slate-800/40">
                                        <td colspan="7" class="p-4 border-b border-slate-100 dark:border-slate-800">
                                            <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-sans space-y-2">
                                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                                    <div class="text-xs font-bold text-slate-900 dark:text-slate-100 flex items-center space-x-2">
                                                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 text-[10px] font-mono"><?= htmlspecialchars($l['action']) ?></span>
                                                        <span>Full Immutable Event Record</span>
                                                    </div>
                                                    <span class="text-[11px] text-slate-400">Timestamp: <?= htmlspecialchars($l['created_at']) ?></span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs pt-1">
                                                    <div>
                                                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Initiated By</span>
                                                        <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($l['actor_name'] ?? 'System') ?> (<?= htmlspecialchars($l['actor_role'] ?? 'N/A') ?>)</span>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Target Entity</span>
                                                        <span class="font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($l['entity_type']) ?> ID: #<?= htmlspecialchars($l['entity_id']) ?></span>
                                                    </div>
                                                    <div>
                                                        <span class="text-slate-400 text-[10.5px] uppercase font-bold block">Network IP Address</span>
                                                        <span class="font-mono text-slate-700 dark:text-slate-300"><?= htmlspecialchars($l['ip_address']) ?></span>
                                                    </div>
                                                </div>
                                                <div class="pt-2">
                                                    <span class="text-slate-400 text-[10.5px] uppercase font-bold block mb-1">Payload & Context Details:</span>
                                                    <div class="font-mono text-[11px] p-3 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 break-all border border-slate-200 dark:border-slate-700">
                                                        <?= htmlspecialchars($l['details'] ?? 'No detailed payload recorded.') ?>
                                                    </div>
                                                </div>
                                            </div>
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
        gsap.from("#audit-admin-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });

        function toggleAuditRow(detailRowId, triggerRow) {
            const detailRow = document.getElementById(detailRowId);
            if (!detailRow) return;
            const isHidden = detailRow.classList.contains('hidden');
            if (isHidden) {
                detailRow.classList.remove('hidden');
                if (triggerRow) {
                    const icon = triggerRow.querySelector('[data-row-chevron]');
                    if (icon) icon.classList.add('rotate-180');
                }
            } else {
                detailRow.classList.add('hidden');
                if (triggerRow) {
                    const icon = triggerRow.querySelector('[data-row-chevron]');
                    if (icon) icon.classList.remove('rotate-180');
                }
            }
        }
    </script>
</body>
</html>
