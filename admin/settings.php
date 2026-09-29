<?php
/**
 * Admin Module: Platform Settings & Security Configuration
 * Category/industry taxonomy, 2FA security controls, and security auditing.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Platform Settings';

$categories = [];
$securityEvents = [];
$activeSessions = 0;
$error = '';
$flash = get_flash();

$twoFactorRec = null;
$backupCodesRemaining = 0;
$newlyGeneratedCodes = $_SESSION['new_backup_codes'] ?? null;
unset($_SESSION['new_backup_codes']);

if ($db) {
    // Fetch categories
    $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    
    // Recent security events
    $securityEvents = $db->query("
        SELECT se.*, u.name as user_name, u.email as user_email
        FROM security_events se
        LEFT JOIN users u ON se.user_id = u.id
        ORDER BY se.created_at DESC LIMIT 10
    ")->fetchAll();
    
    // Active sessions count
    $activeSessions = (int)$db->query("SELECT COUNT(*) FROM login_sessions WHERE last_active_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();

    // 2FA Details
    $twoFactorRec = get_2fa_record($user['id']);
    $backupCodesRemaining = get_remaining_backup_codes_count($user['id']);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['form_action'] ?? '';
        
        if ($action === 'generate_backup_codes') {
            $plainCodes = generate_2fa_backup_codes($user['id'], 5);
            $_SESSION['new_backup_codes'] = $plainCodes;
            set_flash('success', '5 new emergency recovery codes generated. Please store them securely!');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }

        if ($action === 'reset_totp_qr') {
            reset_admin_totp($user['id']);
            set_flash('info', 'Mobile Authenticator has been reset. Please scan the new QR code.');
            header('Location: ' . url('auth/setup_2fa.php'));
            exit;
        }

        if ($action === 'toggle_2fa') {
            $newState = (int)($_POST['enable_2fa'] ?? 1);
            $db->prepare("UPDATE two_factor_auth SET is_enabled = ? WHERE user_id = ?")->execute([$newState, $user['id']]);
            log_security_event($user['id'], $newState ? '2FA_ENFORCED' : '2FA_RELAXED', 'high', 'Admin 2FA enforcement policy modified');
            log_audit($user['id'], $newState ? '2FA_POLICY_ENABLE' : '2FA_POLICY_DISABLE', 'two_factor_auth', $user['id']);
            set_flash('success', 'Two-Factor Authentication policy updated.');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }

        if ($action === 'add_category') {
            $catName = trim($_POST['cat_name'] ?? '');
            $catSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $catName));
            $catDesc = trim($_POST['cat_description'] ?? '');
            $catIcon = trim($_POST['cat_icon'] ?? 'layers');
            
            if (empty($catName)) {
                $error = 'Category name is required.';
            } else {
                try {
                    $ins = $db->prepare("INSERT INTO categories (name, slug, description, icon, is_active) VALUES (?, ?, ?, ?, 1)");
                    $ins->execute([$catName, $catSlug, $catDesc, $catIcon]);
                    log_audit($user['id'], 'CREATE_CATEGORY', 'categories', $db->lastInsertId(), "Created category: {$catName}");
                    set_flash('success', "Category \"{$catName}\" created successfully!");
                    header('Location: ' . url('admin/settings.php'));
                    exit;
                } catch (Exception $e) {
                    $error = 'Category already exists or invalid data.';
                }
            }
        }
        
        if ($action === 'toggle_category') {
            $catId = (int)($_POST['cat_id'] ?? 0);
            $newState = (int)($_POST['new_state'] ?? 0);
            $db->prepare("UPDATE categories SET is_active = ? WHERE id = ?")->execute([$newState, $catId]);
            log_audit($user['id'], $newState ? 'ENABLE_CATEGORY' : 'DISABLE_CATEGORY', 'categories', $catId);
            set_flash('success', 'Category status updated.');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }
        
        if ($action === 'delete_category') {
            $catId = (int)($_POST['cat_id'] ?? 0);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
            log_audit($user['id'], 'DELETE_CATEGORY', 'categories', $catId);
            set_flash('success', 'Category removed.');
            header('Location: ' . url('admin/settings.php'));
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
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 text-slate-900 flex min-h-screen">
    
    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Standard Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="settings-main">

            <!-- Alerts -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Platform Settings & Security</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Manage authentication security, platform taxonomy, and access architecture.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="admin-badge badge-neutral">
                        <span class="admin-badge-dot"></span>
                        PHP <?= phpversion() ?> • <?= DB_NAME ?>
                    </span>
                </div>
            </div>

            <!-- Platform Metrics -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Platform Environment</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                            <i data-lucide="server" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-sky text-base">Development</div>
                    <div class="text-[11px] text-slate-500 mt-1"><?= php_uname('s') ?> / Apache</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Active Sessions</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-indigo"><?= $activeSessions ?></div>
                    <div class="text-[11px] text-slate-500 mt-1">Logged-in during last 60m</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Admin 2FA Policy</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value flex items-center gap-1.5 text-sm <?= !empty($twoFactorRec['is_enabled']) ? 'text-emerald-600' : 'text-amber-600' ?>">
                        <span><?= !empty($twoFactorRec['is_enabled']) ? 'Enforced' : 'Optional' ?></span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1"><?= $backupCodesRemaining ?> recovery code<?= $backupCodesRemaining === 1 ? '' : 's' ?> active</div>
                </div>

                <div class="admin-stat-card">
                    <div class="flex items-center justify-between">
                        <span class="admin-stat-label">Industry Categories</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                            <i data-lucide="tags" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="admin-stat-value stat-value-violet"><?= count($categories) ?></div>
                    <div class="text-[11px] text-slate-500 mt-1">Active startup sectors</div>
                </div>
            </div>

            <!-- Two-Factor Authentication Security Card -->
            <div class="admin-card space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-900">Mobile Authenticator (2FA TOTP)</h3>
                                <span class="admin-badge <?= !empty($twoFactorRec['is_enabled']) && is_admin_totp_setup($user['id']) ? 'badge-success' : 'badge-warning' ?>">
                                    <span class="admin-badge-dot"></span>
                                    <?= !empty($twoFactorRec['is_enabled']) && is_admin_totp_setup($user['id']) ? 'Active & Enforced' : 'Setup Required' ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">RFC 6238 time-based verification paired with Google Authenticator or Microsoft Authenticator.</p>
                        </div>
                    </div>

                    <!-- Policy Action Button -->
                    <div>
                        <form method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="toggle_2fa">
                            <input type="hidden" name="enable_2fa" value="<?= !empty($twoFactorRec['is_enabled']) ? '0' : '1' ?>">
                            <button type="submit" class="admin-btn-secondary text-xs">
                                <i data-lucide="<?= !empty($twoFactorRec['is_enabled']) ? 'shield-off' : 'shield' ?>" class="w-3.5 h-3.5 mr-1.5 inline"></i>
                                <span><?= !empty($twoFactorRec['is_enabled']) ? 'Disable 2FA' : 'Enforce 2FA' ?></span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 2FA Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="admin-stat-label">Primary Method</div>
                        <div class="text-xs font-semibold text-slate-800 flex items-center gap-1.5 mt-1">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>TOTP Authenticator</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1"><?= is_admin_totp_setup($user['id']) ? '✓ Paired with device' : '⚠️ Pending setup' ?></div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="admin-stat-label">Recovery Codes</div>
                        <div class="text-xs font-semibold text-slate-800 flex items-center gap-1.5 mt-1">
                            <i data-lucide="key" class="w-3.5 h-3.5 text-slate-600"></i>
                            <span><?= $backupCodesRemaining ?> of 5 Remaining</span>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Single-use emergency access</div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div>
                            <div class="admin-stat-label">Regenerate Codes</div>
                            <div class="text-xs font-semibold text-slate-700 mt-0.5">Create 5 new codes</div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Generating new recovery codes will invalidate any existing unused codes. Continue?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="generate_backup_codes">
                            <button type="submit" class="admin-btn-secondary text-[11px] py-1.5 px-3">
                                <i data-lucide="refresh-cw" class="w-3 h-3 mr-1 inline"></i>
                                Generate
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Newly Generated Codes Banner -->
                <?php if (!empty($newlyGeneratedCodes)): ?>
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-1.5 text-xs font-bold text-amber-900">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                                <span>Save Your Emergency Recovery Codes</span>
                            </div>
                            <button type="button" onclick="copyBackupCodes()" class="admin-btn-secondary text-[10px] py-1 px-2.5 bg-white">
                                <i data-lucide="copy" class="w-3 h-3 mr-1 inline"></i>
                                <span id="copyBackupBtnText">Copy All Codes</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-amber-800 mb-3">Store these single-use codes safely. Each code can be used only once if you lose device access.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2" id="backupCodesContainer">
                            <?php foreach ($newlyGeneratedCodes as $code): ?>
                                <div class="px-3 py-1.5 rounded-lg bg-white border border-amber-200 font-mono text-center text-xs font-bold text-slate-800 shadow-2xs">
                                    <?= htmlspecialchars($code) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Two-Column Section: Taxonomy + Security Monitor -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Category Taxonomy Management -->
                <div class="admin-card space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Industry & Category Taxonomy</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Sectors available for startups during profile creation.</p>
                        </div>
                    </div>

                    <!-- Add Category Form -->
                    <form method="POST" class="p-3 rounded-xl bg-slate-50 border border-slate-100 space-y-2.5">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="add_category">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" name="cat_name" required placeholder="Category name (e.g. CleanTech)" 
                                   class="admin-input">
                            <input type="text" name="cat_icon" placeholder="Icon name (e.g. zap, layers)" value="layers"
                                   class="admin-input">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" name="cat_description" placeholder="Short description (optional)"
                                   class="admin-input flex-1">
                            <button type="submit" class="admin-btn-primary py-2 px-3 flex-shrink-0">
                                <i data-lucide="plus" class="w-3.5 h-3.5 mr-1"></i>
                                <span>Add</span>
                            </button>
                        </div>
                    </form>

                    <!-- Category List -->
                    <?php if (empty($categories)): ?>
                        <div class="py-8 text-center text-xs text-slate-400">No categories defined yet.</div>
                    <?php else: ?>
                        <div class="space-y-1.5 max-h-80 overflow-y-auto pr-1">
                            <?php foreach ($categories as $cat): ?>
                                <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-100 hover:border-slate-200 transition bg-white">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 flex-shrink-0">
                                            <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'layers') ?>" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold text-slate-800 truncate"><?= htmlspecialchars($cat['name']) ?></div>
                                            <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($cat['slug']) ?></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <!-- Toggle Active -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="toggle_category">
                                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                            <input type="hidden" name="new_state" value="<?= $cat['is_active'] ? 0 : 1 ?>">
                                            <button type="submit" class="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" title="<?= $cat['is_active'] ? 'Disable' : 'Enable' ?>">
                                                <i data-lucide="<?= $cat['is_active'] ? 'toggle-right' : 'toggle-left' ?>" class="w-4 h-4 <?= $cat['is_active'] ? 'text-emerald-600' : 'text-slate-400' ?>"></i>
                                            </button>
                                        </form>
                                        <!-- Delete -->
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="delete_category">
                                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Delete">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Security Events Monitor -->
                <div class="admin-card space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Security Event Monitor</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Real-time authentication and access events.</p>
                        </div>
                    </div>

                    <?php if (empty($securityEvents)): ?>
                        <div class="py-10 text-center">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                                <i data-lucide="shield-check" class="w-5 h-5 text-emerald-500"></i>
                            </div>
                            <div class="text-xs font-semibold text-slate-800">All Clear</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">No suspicious security events recorded.</div>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                            <?php foreach ($securityEvents as $se): 
                                $badgeClass = match($se['severity']) {
                                    'critical', 'high' => 'badge-danger',
                                    'medium' => 'badge-warning',
                                    default => 'badge-neutral'
                                };
                            ?>
                                <div class="p-2.5 rounded-lg border border-slate-100 bg-white space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($se['event_type']) ?></span>
                                        <span class="admin-badge <?= $badgeClass ?>">
                                            <span class="admin-badge-dot"></span>
                                            <?= strtoupper($se['severity']) ?>
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        <?= htmlspecialchars($se['user_name'] ?? 'System') ?> • <span class="font-mono"><?= htmlspecialchars($se['ip_address']) ?></span> • <?= date('d M, h:i A', strtotime($se['created_at'])) ?>
                                    </div>
                                    <?php if (!empty($se['details'])): ?>
                                        <div class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars(substr($se['details'], 0, 100)) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Role & Permission Reference Table -->
            <div class="admin-table-container">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Role & Permission Architecture</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Matrix of privileges enforced across platform modules.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Dashboard</th>
                                <th>Company Registry</th>
                                <th>Fundraising</th>
                                <th>Investments</th>
                                <th>Direct Chat</th>
                                <th>Admin Desk</th>
                                <th>Audit Trails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-semibold text-slate-900">Founder</td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><span class="text-slate-300">—</span></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><span class="text-slate-300">—</span></td>
                                <td><span class="text-slate-300">—</span></td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-slate-900">Investor</td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><span class="text-[11px] text-slate-500">View Only</span></td>
                                <td><span class="text-[11px] text-slate-500">View Rounds</span></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><span class="text-slate-300">—</span></td>
                                <td><span class="text-slate-300">—</span></td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-indigo-600">Administrator</td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                                <td><i data-lucide="check" class="w-4 h-4 text-emerald-600"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();

        function copyBackupCodes() {
            const container = document.getElementById('backupCodesContainer');
            if (!container) return;
            const codes = Array.from(container.children).map(el => el.textContent.trim()).join("\n");
            navigator.clipboard.writeText(codes).then(() => {
                const btnText = document.getElementById('copyBackupBtnText');
                if (btnText) {
                    btnText.textContent = 'Copied to Clipboard!';
                    setTimeout(() => { btnText.textContent = 'Copy All Codes'; }, 2500);
                }
            });
        }
    </script>
</body>
</html>
