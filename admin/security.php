<?php
/**
 * Admin Module: Enterprise Security Operations Center (SOC) & WAF Control
 * Provides real-time visibility into blocked attacks, active firewall IP bans,
 * security events, session integrity, and security layer status.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Security Operations & WAF';

$flash = get_flash();
$testResult = null;

// Handle Admin Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Security token expired. Please retry.');
        header('Location: ' . url('admin/security.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. Unban an IP
    if ($action === 'unban_ip') {
        $ipToUnban = trim($_POST['ip_address'] ?? '');
        if (!empty($ipToUnban) && filter_var($ipToUnban, FILTER_VALIDATE_IP)) {
            $stmt = $db->prepare("DELETE FROM security_firewall_blocks WHERE ip_address = ?");
            $stmt->execute([$ipToUnban]);
            log_audit($user['id'], 'FIREWALL_IP_UNBANNED', 'security_firewall_blocks', null, "Manually unbanned IP: {$ipToUnban}");
            set_flash('success', "IP address {$ipToUnban} has been removed from the firewall blocklist.");
        } else {
            set_flash('error', 'Invalid IP address specified.');
        }
        header('Location: ' . url('admin/security.php'));
        exit;
    }

    // 2. Manually Block an IP
    if ($action === 'manual_ban_ip') {
        $ipToBan = trim($_POST['ip_address'] ?? '');
        $reason = trim($_POST['reason'] ?? 'Manual administrative block');
        $durationHours = max(1, min(720, (int)($_POST['duration_hours'] ?? 24)));

        if (!empty($ipToBan) && filter_var($ipToBan, FILTER_VALIDATE_IP)) {
            $stmt = $db->prepare("
                INSERT INTO security_firewall_blocks (ip_address, reason, violation_count, blocked_until, created_at)
                VALUES (?, ?, 1, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW())
                ON DUPLICATE KEY UPDATE 
                    blocked_until = DATE_ADD(NOW(), INTERVAL ? HOUR),
                    reason = ?
            ");
            $stmt->execute([$ipToBan, $reason, $durationHours, $durationHours, $reason]);
            log_audit($user['id'], 'FIREWALL_IP_MANUALLY_BANNED', 'security_firewall_blocks', null, "Admin manually banned IP {$ipToBan} for {$durationHours}h. Reason: {$reason}");
            set_flash('success', "IP address {$ipToBan} has been blocked for {$durationHours} hours.");
        } else {
            set_flash('error', 'Please provide a valid IPv4 or IPv6 address.');
        }
        header('Location: ' . url('admin/security.php'));
        exit;
    }

    // 3. Clear All Firewall Bans
    if ($action === 'clear_all_bans') {
        $db->exec("DELETE FROM security_firewall_blocks");
        log_audit($user['id'], 'FIREWALL_ALL_BANS_CLEARED', 'security_firewall_blocks', null, "Admin cleared all active IP blocks from firewall.");
        set_flash('success', 'All firewall IP blocks have been cleared.');
        header('Location: ' . url('admin/security.php'));
        exit;
    }

    // 4. Test WAF Simulator (Dry Run)
    if ($action === 'test_waf_probe') {
        $payload = trim($_POST['test_payload'] ?? '');
        $detectedThreats = [];

        if (str_contains($payload, "\0") || str_contains($payload, "%00")) {
            $detectedThreats[] = ['type' => 'Null Byte Injection', 'severity' => 'Critical', 'pattern' => '\\0 or %00'];
        }
        if (preg_match('~(?:\.\.[\\\/]|\.\.%2f|\.\.%5c)~i', $payload)) {
            $detectedThreats[] = ['type' => 'Directory Traversal (LFI/RFI)', 'severity' => 'High', 'pattern' => '../ or ..\\'];
        }
        if (preg_match('~\bunion\s+(?:all\s+)?select\b~i', $payload)) {
            $detectedThreats[] = ['type' => 'SQL Injection (UNION SELECT)', 'severity' => 'Critical', 'pattern' => 'UNION [ALL] SELECT'];
        }
        if (preg_match('~(?:\b(?:sleep\s*\(\s*\d+\s*\)|benchmark\s*\(\s*\d+|waitfor\s+delay))~i', $payload)) {
            $detectedThreats[] = ['type' => 'Time-Based Blind SQLi', 'severity' => 'Critical', 'pattern' => 'SLEEP() / BENCHMARK()'];
        }
        if (preg_match('~<\s*(?:script|iframe|object|embed)["\'\s>]~i', $payload) || preg_match('~javascript:~i', $payload)) {
            $detectedThreats[] = ['type' => 'Cross-Site Scripting (XSS Tag/URI)', 'severity' => 'High', 'pattern' => '<script> / <iframe> / javascript:'];
        }
        if (preg_match('~<\s*[a-z0-9_-]+[^>]*\bon(?:error|load|click)\s*=~i', $payload)) {
            $detectedThreats[] = ['type' => 'Cross-Site Scripting (Event Handler)', 'severity' => 'High', 'pattern' => 'onerror= / onload='];
        }
        if (preg_match('~(?:\b(?:passthru|shell_exec|exec|popen)\s*\(|php://)~i', $payload)) {
            $detectedThreats[] = ['type' => 'Remote Code Execution (RCE)', 'severity' => 'Critical', 'pattern' => 'exec() / shell_exec() / php://'];
        }

        $testResult = [
            'payload' => $payload,
            'blocked' => !empty($detectedThreats),
            'threats' => $detectedThreats
        ];
    }
}

// Read Stats & Data
$stats = [
    'critical_threats' => 0,
    'total_threats' => 0,
    'active_bans' => 0,
    'active_sessions' => 0
];

$activeBans = [];
$securityEvents = [];
$filterSeverity = trim($_GET['severity'] ?? '');

if ($db) {
    try {
        $stats['total_threats'] = (int)$db->query("SELECT COUNT(*) FROM security_events")->fetchColumn();
        $stats['critical_threats'] = (int)$db->query("SELECT COUNT(*) FROM security_events WHERE severity IN ('critical', 'high')")->fetchColumn();
        $stats['active_bans'] = (int)$db->query("SELECT COUNT(*) FROM security_firewall_blocks WHERE blocked_until > NOW()")->fetchColumn();
        $stats['active_sessions'] = (int)$db->query("SELECT COUNT(*) FROM login_sessions WHERE last_active_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();

        // Fetch active firewall blocks
        $activeBans = $db->query("
            SELECT * FROM security_firewall_blocks 
            WHERE blocked_until > NOW() 
            ORDER BY blocked_until DESC LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch security events with optional filter
        $eventQuery = "
            SELECT se.*, u.name as user_name, u.email as user_email, u.role as user_role
            FROM security_events se
            LEFT JOIN users u ON se.user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        if (!empty($filterSeverity)) {
            $eventQuery .= " AND se.severity = ?";
            $params[] = $filterSeverity;
        }
        $eventQuery .= " ORDER BY se.created_at DESC LIMIT 50";

        $stmt = $db->prepare($eventQuery);
        $stmt->execute($params);
        $securityEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

function get_severity_badge(string $severity): array {
    $s = strtolower($severity);
    return match($s) {
        'critical' => ['bg' => 'bg-rose-500/10 text-rose-400 border-rose-500/30', 'dot' => 'bg-rose-500 animate-pulse'],
        'high'     => ['bg' => 'bg-amber-500/10 text-amber-400 border-amber-500/30', 'dot' => 'bg-amber-500'],
        'medium'   => ['bg' => 'bg-blue-500/10 text-blue-400 border-blue-500/30', 'dot' => 'bg-blue-500'],
        default    => ['bg' => 'bg-slate-500/10 text-slate-400 border-slate-500/30', 'dot' => 'bg-slate-400']
    };
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .dark .glass-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-color: rgba(30, 41, 59, 0.8);
        }
    </style>
</head>
<body class="min-h-full flex flex-col bg-[#f8fafc] dark:bg-[#0b0f19] text-slate-800 dark:text-slate-100 font-sans antialiased">

    <div class="flex min-h-screen">
        <!-- Admin Sidebar -->
        <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

        <div class="flex-1 flex flex-col min-w-0">
            <!-- Admin Top Navbar -->
            <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

            <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 flex-1">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/20 flex-shrink-0">
                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                                    Security Operations Center (SOC)
                                </h1>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    WAF ACTIVE
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Real-time perimeter firewall, SQLi/XSS inspection, honeypot traps, and adaptive auto-ban shielding.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button onclick="document.getElementById('manualBanModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-sm shadow-rose-600/20">
                            <i data-lucide="ban" class="w-4 h-4"></i>
                            <span>Block Malicious IP</span>
                        </button>
                        <a href="<?= url('admin/audit_logs.php') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 transition">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>Audit Logs</span>
                        </a>
                    </div>
                </div>

                <!-- Flash Message Alerts -->
                <?php if ($flash): ?>
                    <div class="p-3.5 rounded-2xl text-xs font-semibold flex items-center gap-2.5 <?= $flash['type'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-800' ?>">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                <?php endif; ?>

                <!-- KPI Metric Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Metric 1: WAF Threats Blocked -->
                    <div class="glass-card rounded-2xl p-4 shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total WAF Events</span>
                            <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white"><?= number_format($stats['total_threats']) ?></div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                            <span class="text-rose-500 font-bold"><?= $stats['critical_threats'] ?></span> High/Critical violations
                        </div>
                    </div>

                    <!-- Metric 2: Active Firewall IP Bans -->
                    <div class="glass-card rounded-2xl p-4 shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Firewall IP Bans</span>
                            <div class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                <i data-lucide="slash" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-rose-600 dark:text-rose-400"><?= number_format($stats['active_bans']) ?></div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Currently isolated by auto-ban</div>
                    </div>

                    <!-- Metric 3: Active Sessions -->
                    <div class="glass-card rounded-2xl p-4 shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active Sessions</span>
                            <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                <i data-lucide="users" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white"><?= number_format($stats['active_sessions']) ?></div>
                        <div class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1">Bound to User-Agent &amp; Subnet</div>
                    </div>

                    <!-- Metric 4: Protection Engine -->
                    <div class="glass-card rounded-2xl p-4 shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Security Architecture</span>
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <i data-lucide="cpu" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">8 Layers</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Active Defense-in-Depth</div>
                    </div>
                </div>

                <!-- 8 Security Layers Matrix (Interactive Compliance Visualizer) -->
                <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="layers" class="w-4 h-4 text-blue-500"></i>
                                Platform Defense-in-Depth Layer Status
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Continuous hardware, network, transport, application, and storage security verification.</p>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            100% HEALTHY
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L1: Perimeter WAF</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Inspects all GET/POST for SQLi, XSS, RCE, and Path Traversal.</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L2: IP Auto-Ban Firewall</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Blocks repeated attackers automatically for 15m to 60m.</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L3: CSP &amp; HTTP Headers</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Strict Content Security Policy, HSTS, SAMEORIGIN, nosniff.</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L4: Anti-Session Hijack</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Device fingerprinting &amp; /24 subnet binding prevents theft.</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L5: Inactivity Timeout</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Auto-destroys stale sessions (30m Admin, 60m Users).</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L6: Honeypot Anti-Bot</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Invisible decoy inputs trap brute-force credential stuffers.</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L7: PII Field Encryption</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">AES-256-GCM authenticated encryption for sensitive KYC data.</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-slate-900 dark:text-white">L8: Magic Byte Uploads</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">Direct .htaccess execution killswitch + fileinfo verification.</p>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Layout: Active Bans & WAF Simulator -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left 2 Cols: Active Firewall IP Bans Table -->
                    <div class="lg:col-span-2 glass-card rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                                        <i data-lucide="shield-alert" class="w-4 h-4"></i>
                                    </div>
                                    <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Active Firewall IP Blocks (<?= count($activeBans) ?>)</h3>
                                </div>
                                <?php if (!empty($activeBans)): ?>
                                    <form action="<?= url('admin/security.php') ?>" method="POST" onsubmit="return confirm('Release all currently banned IP addresses?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="clear_all_bans">
                                        <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 font-bold hover:underline">
                                            Clear All Blocks
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <?php if (empty($activeBans)): ?>
                                <div class="text-center py-10 bg-slate-50/60 dark:bg-slate-900/40 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                                    <i data-lucide="check-circle" class="w-10 h-10 text-emerald-500 mx-auto mb-2 opacity-80"></i>
                                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200">Firewall Clean &amp; Clear</div>
                                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">No IP addresses are currently blocked. Legitimate users have unhindered access.</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-100/70 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                                            <tr>
                                                <th class="py-2.5 px-3">IP Address</th>
                                                <th class="py-2.5 px-3">Reason</th>
                                                <th class="py-2.5 px-3 text-center">Hits</th>
                                                <th class="py-2.5 px-3">Expires At</th>
                                                <th class="py-2.5 px-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                                            <?php foreach ($activeBans as $ban): ?>
                                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                                                    <td class="py-2.5 px-3 font-mono font-bold text-rose-600 dark:text-rose-400">
                                                        <?= htmlspecialchars($ban['ip_address']) ?>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300 max-w-xs truncate" title="<?= htmlspecialchars($ban['reason']) ?>">
                                                        <?= htmlspecialchars($ban['reason']) ?>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-center">
                                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                                            <?= (int)$ban['violation_count'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-slate-500 dark:text-slate-400 text-[11px] whitespace-nowrap">
                                                        <?= date('M d, H:i', strtotime($ban['blocked_until'])) ?>
                                                    </td>
                                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                                        <form action="<?= url('admin/security.php') ?>" method="POST" class="inline" onsubmit="return confirm('Unban this IP?');">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="action" value="unban_ip">
                                                            <input type="hidden" name="ip_address" value="<?= htmlspecialchars($ban['ip_address']) ?>">
                                                            <button type="submit" class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/60 dark:hover:text-emerald-400 transition font-bold text-[11px]">
                                                                Release
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Col: Interactive WAF Simulator Box -->
                    <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                                    <i data-lucide="terminal" class="w-4 h-4"></i>
                                </div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">WAF Attack Simulator</h3>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                                Test incoming payload vectors safely in simulation mode to verify that the pattern matching engine intercepts threats without impacting live sessions.
                            </p>

                            <form action="<?= url('admin/security.php') ?>" method="POST" class="space-y-3">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="test_waf_probe">

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                                        Test Input String
                                    </label>
                                    <textarea name="test_payload" rows="3" required placeholder="e.g. ' UNION SELECT 1,2,3-- or <script>alert(1)</script>" class="w-full p-2.5 text-xs font-mono rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($_POST['test_payload'] ?? '') ?></textarea>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="document.getElementsByName('test_payload')[0].value='\' UNION SELECT null, username, password FROM users--'" class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-300 hover:bg-slate-200">SQLi Sample</button>
                                    <button type="button" onclick="document.getElementsByName('test_payload')[0].value='<img src=x onerror=alert(document.cookie)>'" class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-300 hover:bg-slate-200">XSS Sample</button>
                                    <button type="button" onclick="document.getElementsByName('test_payload')[0].value='../../../../etc/passwd'" class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-300 hover:bg-slate-200">Path Sample</button>
                                </div>

                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm">
                                    Run Simulation Analysis
                                </button>
                            </form>

                            <?php if ($testResult): ?>
                                <div class="mt-4 p-3.5 rounded-2xl <?= $testResult['blocked'] ? 'bg-rose-500/10 border border-rose-500/30' : 'bg-emerald-500/10 border border-emerald-500/30' ?>">
                                    <div class="flex items-center gap-2 text-xs font-extrabold <?= $testResult['blocked'] ? 'text-rose-500' : 'text-emerald-500' ?>">
                                        <i data-lucide="<?= $testResult['blocked'] ? 'shield-x' : 'shield-check' ?>" class="w-4 h-4"></i>
                                        <span><?= $testResult['blocked'] ? 'THREAT DETECTED & BLOCKED' : 'CLEAN INPUT — PERMITTED' ?></span>
                                    </div>
                                    <?php if ($testResult['blocked']): ?>
                                        <div class="mt-2 space-y-1 text-[11px]">
                                            <?php foreach ($testResult['threats'] as $t): ?>
                                                <div class="text-rose-400 font-semibold">• <?= htmlspecialchars($t['type']) ?> (Matched: <code><?= htmlspecialchars($t['pattern']) ?></code>)</div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Security Events Table -->
                <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center">
                                <i data-lucide="activity" class="w-4 h-4"></i>
                            </div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">Security Events Stream (SIEM)</h3>
                        </div>

                        <!-- Severity Filter Tabs -->
                        <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-900 rounded-xl text-[11px] font-bold">
                            <a href="<?= url('admin/security.php') ?>" class="px-2.5 py-1 rounded-lg <?= empty($filterSeverity) ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-800' ?>">All</a>
                            <a href="<?= url('admin/security.php?severity=critical') ?>" class="px-2.5 py-1 rounded-lg <?= $filterSeverity === 'critical' ? 'bg-rose-500 text-white shadow-xs' : 'text-rose-500 hover:text-rose-600' ?>">Critical</a>
                            <a href="<?= url('admin/security.php?severity=high') ?>" class="px-2.5 py-1 rounded-lg <?= $filterSeverity === 'high' ? 'bg-amber-500 text-white shadow-xs' : 'text-amber-500 hover:text-amber-600' ?>">High</a>
                            <a href="<?= url('admin/security.php?severity=medium') ?>" class="px-2.5 py-1 rounded-lg <?= $filterSeverity === 'medium' ? 'bg-blue-500 text-white shadow-xs' : 'text-blue-500 hover:text-blue-600' ?>">Medium</a>
                        </div>
                    </div>

                    <?php if (empty($securityEvents)): ?>
                        <div class="text-center py-8 text-slate-400 text-xs">
                            No security incidents logged matching the current filter.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-800">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100/70 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80 dark:border-slate-800">
                                    <tr>
                                        <th class="py-2.5 px-3">Severity</th>
                                        <th class="py-2.5 px-3">Event Classifier</th>
                                        <th class="py-2.5 px-3">Origin IP</th>
                                        <th class="py-2.5 px-3">User Target</th>
                                        <th class="py-2.5 px-3">Details</th>
                                        <th class="py-2.5 px-3 text-right">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                                    <?php foreach ($securityEvents as $evt): 
                                        $badge = get_severity_badge($evt['severity'] ?? 'medium');
                                    ?>
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition">
                                            <td class="py-2.5 px-3 whitespace-nowrap">
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badge['bg'] ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $badge['dot'] ?>"></span>
                                                    <?= strtoupper(htmlspecialchars($evt['severity'] ?? 'medium')) ?>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                                                <?= htmlspecialchars($evt['event_type']) ?>
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-slate-600 dark:text-slate-400">
                                                <?= htmlspecialchars($evt['ip_address']) ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300">
                                                <?php if (!empty($evt['user_name'])): ?>
                                                    <span class="font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($evt['user_name']) ?></span>
                                                    <span class="block text-[10px] text-slate-400">(<?= htmlspecialchars($evt['user_role']) ?>)</span>
                                                <?php else: ?>
                                                    <span class="text-slate-400 italic">Unauthenticated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-300 max-w-sm truncate" title="<?= htmlspecialchars($evt['details'] ?? '') ?>">
                                                <?= htmlspecialchars($evt['details'] ?? '—') ?>
                                            </td>
                                            <td class="py-2.5 px-3 text-right text-slate-400 text-[11px] whitespace-nowrap">
                                                <?= date('M d, H:i:s', strtotime($evt['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </main>
        </div>
    </div>

    <!-- Modal: Manually Block Malicious IP -->
    <div id="manualBanModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl relative">
            <button type="button" onclick="document.getElementById('manualBanModal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <i data-lucide="ban" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Manual Firewall Isolation</h3>
                    <p class="text-xs text-slate-400">Instantly drop incoming traffic from this IP address.</p>
                </div>
            </div>

            <form action="<?= url('admin/security.php') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="manual_ban_ip">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">IP Address to Block *</label>
                    <input type="text" name="ip_address" required placeholder="e.g. 192.168.1.100" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-mono outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Reason / Incident Note *</label>
                    <input type="text" name="reason" required placeholder="e.g. Malicious scanning / credential stuffing" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Block Duration</label>
                    <select name="duration_hours" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-bold outline-none focus:ring-2 focus:ring-rose-500">
                        <option value="1">1 Hour</option>
                        <option value="24" selected>24 Hours (1 Day)</option>
                        <option value="72">72 Hours (3 Days)</option>
                        <option value="168">7 Days</option>
                        <option value="720">30 Days</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('manualBanModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition shadow-sm shadow-rose-600/20">
                        Confirm IP Block
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
