<?php
/**
 * Admin Module: Platform Settings & Security Configuration


 */
if (!headers_sent()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
}

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

// ---------------------------------------------------------------------------
// Handle POST actions FIRST (so page data below is always fresh)
// ---------------------------------------------------------------------------
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
            $newState = (int) ($_POST['enable_2fa'] ?? 1);
            $db->prepare("UPDATE two_factor_auth SET is_enabled = ? WHERE user_id = ?")->execute([$newState, $user['id']]);
            log_security_event($user['id'], $newState ? '2FA_ENFORCED' : '2FA_RELAXED', 'high', 'Admin 2FA enforcement policy modified');
            log_audit($user['id'], $newState ? '2FA_POLICY_ENABLE' : '2FA_POLICY_DISABLE', 'two_factor_auth', $user['id']);
            set_flash('success', 'Two-Factor Authentication policy updated.');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }

        if ($action === 'add_category') {
            $catName = trim($_POST['cat_name'] ?? '');
            $catSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $catName), '-'));
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
            $catId = (int) ($_POST['cat_id'] ?? 0);
            $newState = (int) ($_POST['new_state'] ?? 0);
            $db->prepare("UPDATE categories SET is_active = ? WHERE id = ?")->execute([$newState, $catId]);
            log_audit($user['id'], $newState ? 'ENABLE_CATEGORY' : 'DISABLE_CATEGORY', 'categories', $catId);
            set_flash('success', 'Category status updated.');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }

        if ($action === 'delete_category') {
            $catId = (int) ($_POST['cat_id'] ?? 0);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
            log_audit($user['id'], 'DELETE_CATEGORY', 'categories', $catId);
            set_flash('success', 'Category removed.');
            header('Location: ' . url('admin/settings.php'));
            exit;
        }
    }
}


// ---------------------------------------------------------------------------
// Load page data
// ---------------------------------------------------------------------------
if ($db) {
    // Categories
    $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

    // Recent security events
    $securityEvents = $db->query("
        SELECT se.*, u.name as user_name, u.email as user_email
        FROM security_events se
        LEFT JOIN users u ON se.user_id = u.id
        ORDER BY se.created_at DESC LIMIT 10
    ")->fetchAll();

    // Active sessions count
    $activeSessions = (int) $db->query("SELECT COUNT(*) FROM login_sessions WHERE last_active_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();

    // 2FA details
    $twoFactorRec = get_2fa_record($user['id']);
    $backupCodesRemaining = get_remaining_backup_codes_count($user['id']);
}

$is2faEnabled = !empty($twoFactorRec['is_enabled']);
$isTotpSetup = $db ? is_admin_totp_setup($user['id']) : false;
$flashType = $flash['type'] ?? '';
$flashClasses = match ($flashType) {
    'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'info' => 'bg-blue-50 text-blue-700 border-blue-200',
    default => 'bg-rose-50 text-rose-700 border-rose-200',
};

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Settings & Security • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/admin/head.php'; ?>
    <style>
        .stat-card-clean {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .stat-card-clean, html.dark .stat-card-clean {
            background: #111827 !important;
            border-color: #1e293b !important;
        }

        .card-clean {
            background: #fff;
            border: 1px solid #E2E8F0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
        }
        .dark .card-clean, html.dark .card-clean {
            background: #111827 !important;
            border-color: #1e293b !important;
        }

        /* FIX: never let a card stay faded if the animation script fails */
        #settings-main>* {
            opacity: 1;
        }

        #settings-main.js-anim>* {
            will-change: transform, opacity;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6" id="settings-main">


            <!-- Alerts -->
            <?php if ($flash): ?>

                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flashClasses ?> flex items-center space-x-2">
                    <i data-lucide="<?= $flashType === 'success' ? 'check-circle' : ($flashType === 'info' ? 'info' : 'alert-circle') ?>"
                        class="w-3.5 h-3.5 flex-shrink-0"></i>

                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>

                <div
                    class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>

                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>


            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200/80 dark:border-indigo-800/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-xs flex-shrink-0">
                        <i data-lucide="settings-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Platform Settings &amp; Security
                            </h1>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                System Master
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Industry taxonomy categories, two-factor authentication policy, and infrastructure monitoring.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="<?= url('admin/audit_logs.php') ?>" class="admin-btn-secondary">
                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                        <span>Audit Trail</span>
                    </a>
                </div>
            </div>

            <!-- Platform Metrics (Exactly 4 Distinct KPIs) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Environment</span>
                        <div class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                            <i data-lucide="server" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black text-slate-900 dark:text-white mt-1">Development</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= php_uname('s') ?> / Apache Server</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active Sessions</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1"><?= $activeSessions ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Logged-in during last 60m</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Database Engine</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="database" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black text-slate-900 dark:text-white mt-1"><?= DB_NAME ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= DB_HOST ?>:<?= DB_PORT ?> • MySQL</div>
                </div>

                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Admin 2FA Security</span>
                        <div class="w-9 h-9 rounded-xl <?= $is2faEnabled ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' ?> flex items-center justify-center">
                            <i data-lucide="<?= $is2faEnabled ? 'shield-check' : 'shield-alert' ?>" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black <?= $is2faEnabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' ?> mt-1 flex items-center gap-1.5">
                        <span><?= $is2faEnabled ? 'Enforced' : 'Optional' ?></span>
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= $backupCodesRemaining ?> recovery code<?= $backupCodesRemaining === 1 ? '' : 's' ?> active</div>
                </div>
            </div>

            <!-- Two-Factor Authentication (2FA) Administration Card -->
            <!-- FIX: removed transition-all / duration-300 (it fought the GSAP animation and left the card faded) -->
            <div class="card-clean rounded-2xl p-6 border-blue-100 dark:border-slate-800">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800 cursor-pointer select-none group"
                    onclick="toggleCollapsibleCard('section-2fa-body', this)"
                    title="Click to expand/collapse 2FA details">
                    <div class="flex items-start space-x-3.5 min-w-0 pr-2">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-600/20">
                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mobile Authenticator (2FA)
                                    Security Control</h3>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider whitespace-nowrap flex-shrink-0 <?= $is2faEnabled && $isTotpSetup ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800' ?>">
                                    <?= $is2faEnabled && $isTotpSetup ? 'Active & Enforced' : 'Setup Required' ?>
                                </span>
                            </div>
                            <!-- FIX: line-clamp instead of truncate so text is never cut off badly -->
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2 md:line-clamp-1">
                                Time-based One-Time Password (TOTP RFC 6238) paired with Google Authenticator /
                                Microsoft Authenticator on your mobile phone.</p>
                        </div>
                    </div>

                    <!-- Policy Toggle & Section Arrow -->
                    <div class="flex items-center space-x-2.5 flex-shrink-0 self-start md:self-auto">
                        <form method="POST" class="inline-flex items-center" onclick="event.stopPropagation()">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="toggle_2fa">
                            <input type="hidden" name="enable_2fa" value="<?= $is2faEnabled ? '0' : '1' ?>">
                            <button type="submit"
                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition flex items-center space-x-1.5 whitespace-nowrap <?= $is2faEnabled ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' : 'bg-blue-600 border-blue-600 text-white hover:bg-blue-700 shadow-xs' ?>">
                                <i data-lucide="<?= $is2faEnabled ? 'shield-off' : 'shield' ?>" class="w-3.5 h-3.5"></i>
                                <span><?= $is2faEnabled ? 'Disable 2FA' : 'Enforce 2FA' ?></span>

                            </button>
                        </form>
                        <div
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <i data-lucide="chevron-down" data-chevron
                                class="w-4 h-4 transition-transform duration-300"></i>
                        </div>
                    </div>
                </div>


                <div id="section-2fa-body">
                    <!-- 2FA Details & Recovery Codes Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-5">
                        <!-- Method -->
                        <div
                            class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Primary
                                Method</div>
                            <div
                                class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                                <i data-lucide="smartphone" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span>Google / Microsoft Authenticator</span>
                            </div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                                <?= $isTotpSetup ? '✓ Mobile Phone Linked' : '⚠️ Pending QR Scan' ?> • 30s interval
                            </div>
                        </div>

                        <!-- Recovery Status -->
                        <div
                            class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Emergency
                                Recovery</div>
                            <div
                                class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center space-x-1.5">
                                <i data-lucide="key" class="w-3.5 h-3.5 text-blue-600"></i>
                                <span><?= $backupCodesRemaining ?> of 5 Codes Remaining</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">Single-use emergency recovery codes</div>
                        </div>

                        <!-- Generate Codes Button -->
                        <div
                            class="p-3.5 rounded-xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Backup
                                    Codes</div>
                                <div class="text-[11px] font-semibold text-slate-700 dark:text-slate-300">Regenerate 5
                                    new codes</div>
                            </div>
                            <form method="POST" class="inline"
                                onsubmit="return confirm('Generating new backup codes will invalidate any existing unused codes. Proceed?')">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="form_action" value="generate_backup_codes">
                                <button type="submit"
                                    class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold shadow-xs transition flex items-center space-x-1">
                                    <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                                    <span>Generate</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Newly Generated Codes Banner -->
                    <?php if (!empty($newlyGeneratedCodes)): ?>
                        <div
                            class="mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                            <div class="flex items-center justify-between mb-2">
                                <div
                                    class="flex items-center space-x-1.5 text-xs font-bold text-amber-900 dark:text-amber-200">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                                    <span>Save Your Emergency Recovery Codes</span>
                                </div>
                                <button type="button" onclick="copyBackupCodes()"
                                    class="px-2 py-1 rounded bg-amber-100 dark:bg-amber-900/60 hover:bg-amber-200 text-amber-800 dark:text-amber-200 text-[10px] font-bold transition flex items-center space-x-1">
                                    <i data-lucide="copy" class="w-3 h-3"></i>
                                    <span id="copyBackupBtnText">Copy All Codes</span>
                                </button>
                            </div>
                            <p class="text-[11px] text-amber-700 dark:text-amber-300 mb-3">Store these single-use codes
                                safely. Each code can be used only once if you cannot access your 6-digit OTP code.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2" id="backupCodesContainer">
                                <?php foreach ($newlyGeneratedCodes as $code): ?>
                                    <div
                                        class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-800 font-mono text-center text-xs font-bold tracking-wider text-slate-800 dark:text-slate-100 shadow-2xs">
                                        <?= htmlspecialchars($code) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Category Management & Security Event Monitor Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <!-- Category Management Collapsible Card -->
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800 cursor-pointer select-none group"
                        onclick="toggleCollapsibleCard('section-categories-body', this)"
                        title="Click to expand/collapse categories">
                        <div class="flex items-center space-x-2.5 min-w-0 mr-2 flex-1">
                            <div
                                class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="tags" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider truncate"
                                    title="Industry & Category Taxonomy">
                                    Categories & Taxonomy
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Classification tags for venture
                                    deals</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0 card-header-actions"
                            style="white-space: nowrap !important; flex-shrink: 0 !important;">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800"
                                style="white-space: nowrap !important; flex-shrink: 0 !important;">
                                <?= count($categories) ?> Active
                            </span>
                            <button type="button"
                                onclick="event.stopPropagation(); toggleCollapsibleCard('form-add-cat');"
                                class="inline-flex items-center space-x-1 text-[11px] text-blue-600 hover:text-blue-700 font-bold px-2 py-1 rounded-lg hover:bg-blue-50 dark:hover:bg-slate-800 transition"
                                style="white-space: nowrap !important; flex-shrink: 0 !important;">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add New</span>
                            </button>
                            <div
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0">
                                <i data-lucide="chevron-down" data-chevron
                                    class="w-4 h-4 transition-transform duration-300"></i>
                            </div>
                        </div>
                    </div>

                    <div id="section-categories-body">
                        <!-- Add Category Form (Collapsible) -->
                        <form method="POST" id="form-add-cat"
                            class="hidden mb-4 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="add_category">
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="cat_name" required placeholder="Category name (e.g. Fintech)"
                                    class="px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs placeholder-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600/10 outline-none">
                                <input type="text" name="cat_icon" placeholder="Icon (e.g. layers, cpu)" value="layers"
                                    class="px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs placeholder-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600/10 outline-none">
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="text" name="cat_description" placeholder="Description (optional)"
                                    class="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs placeholder-slate-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600/10 outline-none">
                                <button type="submit"
                                    class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition flex items-center space-x-1 shadow-xs">
                                    <i data-lucide="plus" class="w-3 h-3"></i>
                                    <span>Save</span>
                                </button>
                            </div>

                        </form>

                        <!-- Category List -->
                        <?php if (empty($categories)): ?>
                            <div class="py-6 text-center text-xs text-slate-400">No categories defined yet.</div>
                        <?php else: ?>
                            <div class="space-y-1.5" id="category-list-container">
                                <?php foreach ($categories as $idx => $cat): ?>
                                    <div
                                        class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50/80 transition border border-slate-100 dark:border-slate-800/80 group <?= $idx >= 4 ? 'cat-extra-item hidden' : '' ?>">
                                        <div class="flex items-center space-x-2.5">
                                            <div
                                                class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-blue-600">
                                                <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'layers') ?>"
                                                    class="w-3.5 h-3.5"></i>
                                            </div>
                                            <div>
                                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                    <?= htmlspecialchars($cat['name']) ?></div>
                                                <div class="text-[10px] text-slate-400"><?= htmlspecialchars($cat['slug']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="flex items-center space-x-1.5 opacity-80 group-hover:opacity-100 transition">
                                            <!-- Toggle Active -->
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="toggle_category">
                                                <input type="hidden" name="cat_id" value="<?= (int) $cat['id'] ?>">
                                                <input type="hidden" name="new_state" value="<?= $cat['is_active'] ? 0 : 1 ?>">
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg <?= $cat['is_active'] ? 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40' : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?> transition"
                                                    title="<?= $cat['is_active'] ? 'Disable' : 'Enable' ?>">
                                                    <i data-lucide="<?= $cat['is_active'] ? 'toggle-right' : 'toggle-left' ?>"
                                                        class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                            <!-- Delete -->
                                            <form method="POST" class="inline"
                                                onsubmit="return confirm('Delete this category?')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="delete_category">
                                                <input type="hidden" name="cat_id" value="<?= (int) $cat['id'] ?>">
                                                <button type="submit"
                                                    class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 transition"
                                                    title="Delete">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($categories) > 4): ?>
                                <button type="button" id="btn-toggle-cats"
                                    onclick="toggleListItems('cat-extra-item', 'btn-toggle-cats', 'Show All Categories (+<?= count($categories) - 4 ?>)', 'Show Less')"
                                    class="w-full mt-3 py-2 px-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-slate-800/50 text-blue-600 dark:text-blue-400 font-semibold text-xs transition flex items-center justify-center space-x-1.5">
                                    <i data-lucide="chevrons-down" class="w-3.5 h-3.5"></i>
                                    <span>Show All Categories (+<?= count($categories) - 4 ?>)</span>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Security Events Monitor Collapsible Card -->
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800 cursor-pointer select-none group"
                        onclick="toggleCollapsibleCard('section-events-body', this)"
                        title="Click to expand/collapse security events">
                        <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                            <div
                                class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-500 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <h3
                                    class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider truncate">
                                    Security Event Monitor
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">Automated intrusion & risk
                                    telemetry</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0 card-header-actions"
                            style="white-space: nowrap !important; flex-shrink: 0 !important;">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800"
                                style="white-space: nowrap !important; flex-shrink: 0 !important;">
                                <?= count($securityEvents) ?> Tracked
                            </span>
                            <div
                                class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0">
                                <i data-lucide="chevron-down" data-chevron
                                    class="w-4 h-4 transition-transform duration-300"></i>
                            </div>

                        </div>
                    </div>


                    <div id="section-events-body">
                        <?php if (empty($securityEvents)): ?>
                            <div class="py-8 text-center">
                                <div
                                    class="w-10 h-10 mx-auto mb-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center">
                                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-500"></i>
                                </div>
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mb-0.5">All Clear</div>
                                <div class="text-[11px] text-slate-400">No security events or anomalies recorded.</div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2" id="security-events-container">
                                <?php foreach ($securityEvents as $idx => $se):
                                    $sevColor = match ($se['severity']) {
                                        'critical' => 'rose', 'high' => 'orange', 'medium' => 'amber', default => 'slate'
                                    };
                                    ?>
                                    <div
                                        class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50/80 transition <?= $idx >= 4 ? 'event-extra-item hidden' : '' ?>">
                                        <div class="flex items-start space-x-2.5 cursor-pointer"
                                            onclick="toggleEventDetail('event-detail-<?= $idx ?>', this)"
                                            title="Click to view event details">
                                            <div
                                                class="w-6 h-6 rounded-full bg-<?= $sevColor ?>-50 dark:bg-<?= $sevColor ?>-950/40 flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <span class="w-2 h-2 rounded-full bg-<?= $sevColor ?>-500"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span
                                                        class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate"><?= htmlspecialchars($se['event_type']) ?></span>
                                                    <span
                                                        class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-<?= $sevColor ?>-50 dark:bg-<?= $sevColor ?>-950/40 text-<?= $sevColor ?>-600 dark:text-<?= $sevColor ?>-400 border border-<?= $sevColor ?>-200 dark:border-<?= $sevColor ?>-800"><?= strtoupper(htmlspecialchars($se['severity'])) ?></span>
                                                </div>
                                                <div
                                                    class="text-[10px] text-slate-500 mt-0.5 flex items-center justify-between gap-2">
                                                    <span class="truncate"><?= htmlspecialchars($se['user_name'] ?? 'System') ?>
                                                        • <?= htmlspecialchars($se['ip_address'] ?? '-') ?> •
                                                        <?= date('d M H:i', strtotime($se['created_at'])) ?></span>
                                                    <span
                                                        class="text-blue-600 font-semibold text-[10px] hover:underline flex items-center space-x-0.5 flex-shrink-0">
                                                        <span>Inspect</span>
                                                        <i data-lucide="chevron-down"
                                                            class="w-3 h-3 transition-transform duration-200"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Expandable Event Detail -->
                                        <div id="event-detail-<?= $idx ?>"
                                            class="hidden mt-2 p-2 rounded-lg bg-slate-50/80 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-[10.5px]">
                                            <div
                                                class="font-mono text-[10px] text-slate-600 dark:text-slate-300 break-all bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-800">
                                                <?= htmlspecialchars($se['details'] ?? 'No extra payload') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (count($securityEvents) > 4): ?>
                                <button type="button" id="btn-toggle-events"
                                    onclick="toggleListItems('event-extra-item', 'btn-toggle-events', 'Show More Events (+<?= count($securityEvents) - 4 ?>)', 'Show Less')"
                                    class="w-full mt-3 py-2 px-3 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-slate-800/50 text-blue-600 dark:text-blue-400 font-semibold text-xs transition flex items-center justify-center space-x-1.5">
                                    <i data-lucide="chevrons-down" class="w-3.5 h-3.5"></i>
                                    <span>Show More Events (+<?= count($securityEvents) - 4 ?>)</span>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>

            </div>


            <!-- Role & Permission Architecture Collapsible Card -->
            <!-- FIX: removed transition-all / duration-300 here too -->
            <div class="card-clean rounded-2xl p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 cursor-pointer select-none group"
                    onclick="toggleCollapsibleCard('section-roles-body', this)"
                    title="Click to expand/collapse roles matrix">
                    <div class="flex items-center space-x-2.5 min-w-0 pr-2">
                        <div
                            class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="key" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h3
                                class="text-xs font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider truncate">
                                Role & Permission Architecture
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">Role-based access matrix for Founders,
                                Investors, and Admins</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 flex-shrink-0 card-header-actions"
                        style="white-space: nowrap !important; flex-shrink: 0 !important;">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"
                            style="white-space: nowrap !important; flex-shrink: 0 !important;">
                            RBAC Active
                        </span>
                        <div
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 group-hover:text-slate-700 dark:group-hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition flex-shrink-0">
                            <i data-lucide="chevron-down" data-chevron
                                class="w-4 h-4 transition-transform duration-300"></i>
                        </div>
                    </div>
                </div>

                <div id="section-roles-body" class="pt-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
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
                                    <td class="py-3 font-bold text-blue-600">Founder</td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                    <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                </tr>
                                <tr>
                                    <td class="py-3 font-bold text-blue-600">Investor</td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                    <td class="py-3 text-[10px] text-slate-500">View Only</td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                    <td class="py-3"><i data-lucide="x" class="w-3.5 h-3.5 text-slate-300"></i></td>
                                </tr>
                                <tr>
                                    <td class="py-3 font-bold text-rose-600">Admin</td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                    <td class="py-3"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script>

        // Render icons first
        if (window.lucide) lucide.createIcons();

        // FIX: fromTo() forces the final state to opacity 1, and clearProps removes
        // the inline styles afterwards, so no card can stay faded.
        (function () {
            const targets = document.querySelectorAll('#settings-main > *');
            if (!window.gsap || !targets.length) {
                targets.forEach(el => { el.style.opacity = 1; el.style.transform = 'none'; });
                return;
            }
            gsap.fromTo(targets,
                { y: 15, opacity: 0 },
                {
                    y: 0,
                    opacity: 1,
                    duration: 0.5,
                    stagger: 0.08,
                    ease: 'power2.out',
                    clearProps: 'opacity,transform,will-change',
                    onComplete: () => targets.forEach(el => { el.style.opacity = ''; el.style.transform = ''; })
                }
            );

            // Safety net: if anything goes wrong, force everything visible after 2 seconds
            setTimeout(() => {
                document.querySelectorAll('#settings-main > *').forEach(el => {
                    if (getComputedStyle(el).opacity < 1) {
                        el.style.opacity = 1;
                        el.style.transform = 'none';
                    }
                });
            }, 2000);
        })();

        // Universal Section Toggle
        function toggleCollapsibleCard(contentId, headerEl) {
            const content = document.getElementById(contentId);
            if (!content) return;
            const isHidden = content.classList.contains('hidden');
            content.classList.toggle('hidden', !isHidden);
            if (headerEl) {
                const icon = headerEl.querySelector('[data-chevron]');
                if (icon) icon.classList.toggle('rotate-180', !isHidden);
            }
        }

        // Show More / Show Less Items
        function toggleListItems(itemClass, btnId, moreText, lessText) {
            const items = document.querySelectorAll('.' + itemClass);
            const btn = document.getElementById(btnId);
            if (!items.length || !btn) return;
            const isExpanded = !items[0].classList.contains('hidden');
            items.forEach(el => el.classList.toggle('hidden', isExpanded));
            btn.innerHTML = isExpanded
                ? `<i data-lucide="chevrons-down" class="w-3.5 h-3.5"></i><span>${moreText}</span>`
                : `<i data-lucide="chevrons-up" class="w-3.5 h-3.5"></i><span>${lessText}</span>`;
            if (window.lucide) lucide.createIcons();
        }

        // Event Detail Expansion
        function toggleEventDetail(detailId, rowEl) {
            const detailBox = document.getElementById(detailId);
            if (!detailBox) return;
            const isHidden = detailBox.classList.contains('hidden');
            detailBox.classList.toggle('hidden', !isHidden);
            if (rowEl) {
                const arrow = rowEl.querySelector('svg.lucide-chevron-down');
                if (arrow) arrow.classList.toggle('rotate-180', isHidden);
            }
        }


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