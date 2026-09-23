<?php
/**
 * Admin Module: Platform Settings & Security Configuration
 * Category/industry CRUD, security overview, platform config
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Platform Settings & Security';

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
            set_flash('success', '5 new emergency backup recovery codes generated. Please store them securely!');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }

        if ($action === 'reset_totp_qr') {
            reset_admin_totp($user['id']);
            set_flash('info', 'Mobile Authenticator has been reset. Please scan the new QR code with your mobile app.');
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

// Reload categories after POST
if ($db && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Already loaded above
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings & Security • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean { background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.03); }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto" id="settings-main">

            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Platform Settings & Security</h1>
                <p class="text-xs text-slate-500 mt-0.5">Manage industry categories, platform configuration, and security monitoring.</p>
            </div>

            <!-- Platform Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="card-clean rounded-xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Platform</div>
                    <div class="text-sm font-bold text-slate-900"><?= APP_NAME ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Phase 1 — Active</div>
                </div>
                <div class="card-clean rounded-xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Environment</div>
                    <div class="text-sm font-bold text-emerald-600">Development</div>
                    <div class="text-[10px] text-slate-500 mt-0.5"><?= php_uname('s') ?> / PHP <?= phpversion() ?></div>
                </div>
                <div class="card-clean rounded-xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Active Sessions</div>
                    <div class="text-sm font-bold text-indigo-600"><?= $activeSessions ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">In last 60 minutes</div>
                </div>
                <div class="card-clean rounded-xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Database</div>
                    <div class="text-sm font-bold text-slate-900"><?= DB_NAME ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5"><?= DB_HOST ?>:<?= DB_PORT ?></div>
                </div>
                <div class="card-clean rounded-xl p-4">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Admin 2FA Status</div>
                    <div class="text-sm font-bold text-emerald-600 flex items-center space-x-1.5">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                        <span><?= !empty($twoFactorRec['is_enabled']) ? 'Enforced' : 'Optional' ?></span>
                    </div>
                    <div class="text-[10px] text-slate-500 mt-0.5"><?= $backupCodesRemaining ?> recovery code<?= $backupCodesRemaining === 1 ? '' : 's' ?> active</div>
                </div>
            </div>

            <!-- Two-Factor Authentication (2FA) Administration Card -->
            <div class="card-clean rounded-2xl p-6 border-indigo-100 bg-gradient-to-br from-white via-indigo-50/20 to-white">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                    <div class="flex items-start space-x-3.5">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm shadow-indigo-600/20">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-sm font-bold text-slate-900">Mobile Authenticator (2FA) Security Control</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?= !empty($twoFactorRec['is_enabled']) && is_admin_totp_setup($user['id']) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                    <?= !empty($twoFactorRec['is_enabled']) && is_admin_totp_setup($user['id']) ? 'Active & Enforced' : 'Setup Required' ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Time-based One-Time Password (TOTP RFC 6238) paired with Google Authenticator / Microsoft Authenticator on your mobile phone.</p>
                        </div>
                    </div>

                    <!-- Policy Toggle & Re-scan QR -->
                    <div class="flex items-center space-x-2">
                        <form method="POST" class="inline-flex items-center">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="reset_totp_qr">
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-semibold transition flex items-center space-x-1.5" title="Re-scan QR code on a new phone">
                                <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                <span>Re-scan QR Code</span>
                            </button>
                        </form>

                        <form method="POST" class="inline-flex items-center">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="toggle_2fa">
                            <input type="hidden" name="enable_2fa" value="<?= !empty($twoFactorRec['is_enabled']) ? '0' : '1' ?>">
                            <button type="submit" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition flex items-center space-x-1.5 <?= !empty($twoFactorRec['is_enabled']) ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700' ?>">
                                <i data-lucide="<?= !empty($twoFactorRec['is_enabled']) ? 'shield-off' : 'shield' ?>" class="w-3.5 h-3.5"></i>
                                <span><?= !empty($twoFactorRec['is_enabled']) ? 'Disable 2FA' : 'Enforce 2FA' ?></span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 2FA Details & Recovery Codes Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-5">
                    <!-- Method -->
                    <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Primary Method</div>
                        <div class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                            <i data-lucide="smartphone" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Google / Microsoft Authenticator</span>
                        </div>
                        <div class="text-[10px] text-slate-500 mt-1"><?= is_admin_totp_setup($user['id']) ? '✓ Mobile Phone Linked' : '⚠️ Pending QR Scan' ?> • 30s interval</div>
                    </div>

                    <!-- Recovery Status -->
                    <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Emergency Recovery</div>
                        <div class="text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                            <i data-lucide="key" class="w-3.5 h-3.5 text-purple-600"></i>
                            <span><?= $backupCodesRemaining ?> of 5 Codes Remaining</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1">Single-use emergency recovery codes</div>
                    </div>

                    <!-- Generate Codes Button -->
                    <div class="p-3.5 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Backup Codes</div>
                            <div class="text-[11px] font-semibold text-slate-700">Regenerate 5 new codes</div>
                        </div>
                        <form method="POST" class="inline" onsubmit="return confirm('Generating new backup codes will invalidate any existing unused codes. Proceed?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="generate_backup_codes">
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold shadow-xs transition flex items-center space-x-1">
                                <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                                <span>Generate</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Newly Generated Codes Banner (Shown when generated) -->
                <?php if (!empty($newlyGeneratedCodes)): ?>
                    <div class="mt-4 p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-1.5 text-xs font-bold text-amber-900">
                                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                                <span>Save Your Emergency Recovery Codes</span>
                            </div>
                            <button type="button" onclick="copyBackupCodes()" class="px-2 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 text-[10px] font-bold transition flex items-center space-x-1">
                                <i data-lucide="copy" class="w-3 h-3"></i>
                                <span id="copyBackupBtnText">Copy All Codes</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-amber-700 mb-3">Store these single-use codes safely. Each code can be used only once if you cannot access your 6-digit OTP code.</p>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2" id="backupCodesContainer">
                            <?php foreach ($newlyGeneratedCodes as $code): ?>
                                <div class="px-3 py-1.5 rounded-lg bg-white border border-amber-200 font-mono text-center text-xs font-bold tracking-wider text-slate-800 shadow-2xs">
                                    <?= htmlspecialchars($code) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Category Management -->
                <div class="card-clean rounded-2xl p-6">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="tags" class="w-3.5 h-3.5 text-indigo-600"></i>
                        <span>Industry & Category Taxonomy</span>
                    </h3>

                    <!-- Add Category Form -->
                    <form method="POST" class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-100">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="add_category">
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" name="cat_name" required placeholder="Category name" 
                                   class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs placeholder-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600/10 outline-none">
                            <input type="text" name="cat_icon" placeholder="Icon (e.g. layers)" value="layers"
                                   class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs placeholder-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600/10 outline-none">
                        </div>
                        <div class="flex items-center space-x-2">
                            <input type="text" name="cat_description" placeholder="Description (optional)"
                                   class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs placeholder-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600/10 outline-none">
                            <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition flex items-center space-x-1">
                                <i data-lucide="plus" class="w-3 h-3"></i>
                                <span>Add</span>
                            </button>
                        </div>
                    </form>

                    <!-- Category List -->
                    <?php if (empty($categories)): ?>
                        <div class="py-6 text-center text-xs text-slate-400">No categories defined yet.</div>
                    <?php else: ?>
                        <div class="space-y-1.5 max-h-80 overflow-y-auto">
                            <?php foreach ($categories as $cat): ?>
                                <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 transition border border-slate-100 group">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'layers') ?>" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-800"><?= htmlspecialchars($cat['name']) ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($cat['slug']) ?></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-1.5 opacity-0 group-hover:opacity-100 transition">
                                        <!-- Toggle Active -->
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="toggle_category">
                                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                            <input type="hidden" name="new_state" value="<?= $cat['is_active'] ? 0 : 1 ?>">
                                            <button type="submit" class="p-1.5 rounded-lg <?= $cat['is_active'] ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100' ?> transition" title="<?= $cat['is_active'] ? 'Disable' : 'Enable' ?>">
                                                <i data-lucide="<?= $cat['is_active'] ? 'toggle-right' : 'toggle-left' ?>" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        <!-- Delete -->
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this category?')">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="delete_category">
                                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition" title="Delete">
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
                <div class="card-clean rounded-2xl p-6">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                        <i data-lucide="shield-alert" class="w-3.5 h-3.5 text-rose-500"></i>
                        <span>Security Event Monitor</span>
                    </h3>

                    <?php if (empty($securityEvents)): ?>
                        <div class="py-6 text-center">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-emerald-50 flex items-center justify-center">
                                <i data-lucide="shield-check" class="w-6 h-6 text-emerald-500"></i>
                            </div>
                            <div class="text-xs font-bold text-slate-800 mb-0.5">All Clear</div>
                            <div class="text-[11px] text-slate-400">No security events recorded yet.</div>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            <?php foreach ($securityEvents as $se): 
                                $sevColor = match($se['severity']) {
                                    'critical' => 'rose', 'high' => 'orange', 'medium' => 'amber', default => 'slate'
                                };
                            ?>
                                <div class="flex items-start space-x-2.5 p-2.5 rounded-lg border border-slate-100 hover:bg-slate-50 transition">
                                    <div class="w-6 h-6 rounded-full bg-<?= $sevColor ?>-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="w-2 h-2 rounded-full bg-<?= $sevColor ?>-500"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold text-slate-800"><?= htmlspecialchars($se['event_type']) ?></span>
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-<?= $sevColor ?>-50 text-<?= $sevColor ?>-600 border border-<?= $sevColor ?>-200"><?= strtoupper($se['severity']) ?></span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-0.5">
                                            <?= htmlspecialchars($se['user_name'] ?? 'Unknown') ?> • <?= htmlspecialchars($se['ip_address']) ?> • <?= date('d M H:i', strtotime($se['created_at'])) ?>
                                        </div>
                                        <?php if (!empty($se['details'])): ?>
                                            <div class="text-[10px] text-slate-400 mt-0.5 truncate"><?= htmlspecialchars(substr($se['details'], 0, 100)) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Role Overview -->
            <div class="card-clean rounded-2xl p-6">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center space-x-1.5">
                    <i data-lucide="key" class="w-3.5 h-3.5 text-purple-600"></i>
                    <span>Role & Permission Architecture</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="pb-2.5">Role</th>
                                <th class="pb-2.5">Dashboard</th>
                                <th class="pb-2.5">Company Mgmt</th>
                                <th class="pb-2.5">Funding</th>
                                <th class="pb-2.5">Investment</th>
                                <th class="pb-2.5">Chat</th>
                                <th class="pb-2.5">Admin Panel</th>
                                <th class="pb-2.5">Audit Access</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-3 font-bold text-indigo-600">Founder</td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                            </tr>
                            <tr>
                                <td class="py-3 font-bold text-purple-600">Investor</td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                <td class="py-3 text-[10px] text-slate-500">View Only</td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                            </tr>
                            <tr>
                                <td class="py-3 font-bold text-rose-600">Admin</td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                                <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#settings-main > *", { duration: 0.5, y: 15, opacity: 0, stagger: 0.08, ease: "power2.out" });

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
