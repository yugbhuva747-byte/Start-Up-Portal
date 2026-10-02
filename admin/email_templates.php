<?php
/**
 * Admin Module: Automated Email Templates & Delivery Monitor
 * Modern, ultra-premium interface for previewing, testing, and auditing transactional email dispatches.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Email Templates';

init_email_logs_table($db);

$successMsg = '';
$errorMsg = '';

// Sample mock data for realistic preview & test sends
$sampleInvestor = [
    'id' => 10,
    'name' => 'Vikramaditya Singhania',
    'email' => 'investor@venturecapital.com',
    'phone' => '+91 9988776655'
];

$sampleFounder = [
    'id' => 5,
    'name' => 'Aarav Sharma',
    'email' => 'founder@techpulse.io',
    'phone' => '+91 9123456780'
];

$sampleRound = [
    'id' => 1,
    'round_name' => 'Seed Growth Round',
    'target_amount' => 5000000.00,
    'amount_raised' => 3500000.00,
    'valuation' => 25000000.00,
    'company_id' => 1
];

$sampleCompany = [
    'id' => 1,
    'name' => 'TechPulse AI Solutions Pvt Ltd',
    'cin_number' => 'U72900KA2024PTC188291',
    'industry' => 'AI / DeepTech SaaS',
    'stage' => 'Seed',
    'website' => 'https://techpulse.io',
    'logo_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120'
];

$sampleInvestment = [
    'investment_id' => 101,
    'amount' => 500000.00,
    'equity_percent' => 2.00,
    'cert_number' => 'CERT-TEC-2026-891',
    'txn_ref' => 'TXN-ESC-9823412',
    'new_raised' => 3500000.00
];

// Pre-render templates for live preview in iframe
$investorHtml = render_investor_congratulations_email($sampleInvestor, $sampleRound, $sampleCompany, $sampleInvestment);
$founderHtml = render_founder_investment_email($sampleFounder, $sampleInvestor, $sampleRound, $sampleCompany, $sampleInvestment);
$roundSubmittedHtml = render_founder_round_submitted_email($sampleFounder, $sampleCompany, $sampleRound);
$roundApprovedHtml = render_founder_round_status_email($sampleFounder, $sampleCompany, $sampleRound, 'LIVE');

// Handle raw HTML preview in standalone new tab
if (isset($_GET['raw']) && in_array($_GET['raw'], ['investor', 'founder', 'round_submission', 'round_approved'])) {
    $rawType = $_GET['raw'];
    $rawOutput = $investorHtml;
    if ($rawType === 'founder') $rawOutput = $founderHtml;
    elseif ($rawType === 'round_submission') $rawOutput = $roundSubmittedHtml;
    elseif ($rawType === 'round_approved') $rawOutput = $roundApprovedHtml;

    header('Content-Type: text/html; charset=utf-8');
    echo $rawOutput;
    exit;
}

// Handle Test Send Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test_email') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Security validation failed (invalid CSRF token). Please try again.';
    } else {
        $testEmail = trim($_POST['test_email'] ?? '');
        $templateType = $_POST['template_type'] ?? 'investor';
        $testAmount = (float)($_POST['test_amount'] ?? 500000);
        $testCompany = trim($_POST['test_company'] ?? 'TechPulse AI Solutions');
        
        $customCompany = $sampleCompany;
        $customCompany['name'] = $testCompany;

        $customInvestment = $sampleInvestment;
        $customInvestment['amount'] = $testAmount;

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Please provide a valid destination email address.';
        } else {
            if ($templateType === 'investor') {
                $customInvestor = $sampleInvestor;
                $customInvestor['email'] = $testEmail;
                $html = render_investor_congratulations_email($customInvestor, $sampleRound, $customCompany, $customInvestment);
                $subject = "🚀 [TEST] Congratulations! Your investment in {$customCompany['name']} is confirmed (" . format_inr($testAmount) . ")";
                $result = send_system_email($testEmail, $customInvestor['name'], $subject, $html, 'investor_congratulations_test');
            } elseif ($templateType === 'founder') {
                $customFounder = $sampleFounder;
                $customFounder['email'] = $testEmail;
                $html = render_founder_investment_email($customFounder, $sampleInvestor, $sampleRound, $customCompany, $customInvestment);
                $subject = "🎉 [TEST] Investment Alert: {$sampleInvestor['name']} committed " . format_inr($testAmount) . " to {$customCompany['name']}";
                $result = send_system_email($testEmail, $customFounder['name'], $subject, $html, 'founder_investment_alert_test');
            } elseif ($templateType === 'round_submission') {
                $customFounder = $sampleFounder;
                $customFounder['email'] = $testEmail;
                $html = render_founder_round_submitted_email($customFounder, $customCompany, $sampleRound);
                $subject = "🚀 [TEST] Funding Round Application Received: {$customCompany['name']} ({$sampleRound['round_name']})";
                $result = send_system_email($testEmail, $customFounder['name'], $subject, $html, 'founder_round_submitted_test');
            } elseif ($templateType === 'round_approved') {
                $customFounder = $sampleFounder;
                $customFounder['email'] = $testEmail;
                $html = render_founder_round_status_email($customFounder, $customCompany, $sampleRound, 'LIVE');
                $subject = "🎉 [TEST] Your Funding Round is Approved & LIVE for Investors: {$customCompany['name']}";
                $result = send_system_email($testEmail, $customFounder['name'], $subject, $html, 'founder_round_approved_test');
            }

            if ($result['success']) {
                $statusUpper = strtoupper($result['status'] ?? 'SENT');
                $successMsg = "Test email dispatched successfully to <strong>" . htmlspecialchars($testEmail) . "</strong>! (Status: {$statusUpper})";
            } else {
                $errorMsg = "Delivery error: " . htmlspecialchars($result['message'] ?? 'Unknown dispatch error');
            }
        }
    }
}

// Fetch Email Logs & Statistics
$logs = [];
$totalLogsCount = 0;
$sentLogsCount = 0;
$failedLogsCount = 0;

if ($db) {
    try {
        $totalLogsCount = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
        $sentLogsCount = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'sent'")->fetchColumn();
        $failedLogsCount = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'failed'")->fetchColumn();

        $stmt = $db->query("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 50");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$successRate = $totalLogsCount > 0 ? round(($sentLogsCount / $totalLogsCount) * 100, 1) : 100.0;

$activeTab = $_GET['tab'] ?? 'preview';
$activeTemplate = $_GET['template'] ?? 'investor';
if (!in_array($activeTemplate, ['investor', 'founder', 'round_submission', 'round_approved'])) {
    $activeTemplate = 'investor';
}

$templateSubjects = [
    'investor' => "🚀 Congratulations! Your investment in TechPulse AI Solutions is confirmed (" . format_inr(500000) . ")",
    'founder' => "🎉 Investment Alert: Vikramaditya Singhania committed " . format_inr(500000) . " to TechPulse AI Solutions",
    'round_submission' => "🚀 Funding Round Application Received: TechPulse AI Solutions (Seed Growth Round)",
    'round_approved' => "🎉 Your Funding Round is Approved & LIVE for Investors: TechPulse AI Solutions"
];
$activeSubject = $templateSubjects[$activeTemplate] ?? '';

$currentPreviewHtml = $investorHtml;
if ($activeTemplate === 'founder') $currentPreviewHtml = $founderHtml;
elseif ($activeTemplate === 'round_submission') $currentPreviewHtml = $roundSubmittedHtml;
elseif ($activeTemplate === 'round_approved') $currentPreviewHtml = $roundApprovedHtml;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> • <?= APP_NAME ?></title>

    <?php include __DIR__ . '/../includes/admin/head.php'; ?>

    <style>
        .stat-card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease-in-out;
        }
        .dark .stat-card-clean, html.dark .stat-card-clean {
            background: #111827 !important;
            border-color: #1e293b !important;
        }
        .stat-card-clean:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -4px rgba(0, 0, 0, 0.05);
        }
        .template-preview-frame {
            border: 0;
            width: 100%;
            height: 640px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            border-radius: 0 0 1rem 1rem;
        }
        .dark .template-preview-frame, html.dark .template-preview-frame {
            background: #0f172a !important;
        }
        .tab-indicator {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 flex min-h-screen dark:bg-[#0b0f19] dark:text-slate-100 font-sans antialiased">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Admin Navbar -->
        <?php include __DIR__ . '/../includes/admin/navbar.php'; ?>

        <main class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            
            <!-- Modern Header with Live Badge -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200/80 dark:border-blue-800/80 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-xs flex-shrink-0">
                        <i data-lucide="mail-check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Email Templates &amp; Dispatcher
                            </h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                                v2.4 System Engine
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Live preview, responsive testing, and transactional delivery records for Founder and Investor notifications.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <?= strtoupper(MAIL_MAILER) ?> Service Ready
                    </span>
                    <a href="?tab=logs" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                        <span>Delivery Logs (<?= $totalLogsCount ?>)</span>
                    </a>
                </div>
            </div>

            <!-- Flash Notifications -->
            <?php if (!empty($successMsg)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800 flex items-start gap-3 shadow-xs animate-in fade-in">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1"><?= $successMsg ?></div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 dark:bg-rose-950/50 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-800 flex items-start gap-3 shadow-xs animate-in fade-in">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0 mt-0.5"></i>
                    <div class="flex-1"><?= $errorMsg ?></div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800"><i data-lucide="x" class="w-4 h-4"></i></button>
                </div>
            <?php endif; ?>

            <!-- System Mail Driver Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Driver -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Driver Architecture</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i data-lucide="server" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-xl font-black text-slate-900 dark:text-white mt-2"><?= strtoupper(MAIL_MAILER) ?> Direct</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Zero-latency background dispatch</span>
                    </div>
                </div>

                <!-- Sender -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sender Identity</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-2 truncate" title="<?= htmlspecialchars(MAIL_FROM_ADDRESS) ?>">
                        <?= htmlspecialchars(MAIL_FROM_ADDRESS) ?>
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400 truncate">
                        <?= htmlspecialchars(MAIL_FROM_NAME) ?>
                    </div>
                </div>

                <!-- Total Dispatched -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Dispatched</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-2"><?= number_format($totalLogsCount) ?></div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <span class="text-emerald-600 font-semibold"><?= $sentLogsCount ?> sent</span> &bull; <span class="text-rose-600 font-semibold"><?= $failedLogsCount ?> failed</span>
                    </div>
                </div>

                <!-- Delivery Rate -->
                <div class="stat-card-clean dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Delivery Reliability</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2"><?= $successRate ?>%</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Audit trail enabled &amp; archived
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="bg-white dark:bg-slate-900 p-2 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                    <a href="?tab=preview&template=investor" 
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition <?= ($activeTab === 'preview' && $activeTemplate === 'investor') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Investor Confirmation</span>
                    </a>

                    <a href="?tab=preview&template=founder" 
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition <?= ($activeTab === 'preview' && $activeTemplate === 'founder') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <span>Founder Capital Alert</span>
                    </a>

                    <a href="?tab=preview&template=round_submission" 
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition <?= ($activeTab === 'preview' && $activeTemplate === 'round_submission') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i data-lucide="file-plus" class="w-4 h-4"></i>
                        <span>Founder Round Request</span>
                    </a>

                    <a href="?tab=preview&template=round_approved" 
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition <?= ($activeTab === 'preview' && $activeTemplate === 'round_approved') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                        <span>Round Approved &amp; LIVE</span>
                    </a>

                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-1 hidden sm:block"></div>

                    <a href="?tab=logs" 
                       class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition <?= $activeTab === 'logs' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
                        <i data-lucide="list" class="w-4 h-4"></i>
                        <span>Delivery Logs (<?= $totalLogsCount ?>)</span>
                    </a>
                </div>
            </div>

            <?php if ($activeTab === 'preview'): ?>
                <!-- PREVIEW & TEST DISPATCH SECTION -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left: Interactive Preview Frame (2 Columns) -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                            
                            <!-- Browser Chrome Style Header -->
                            <div class="p-3.5 bg-slate-100/80 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-750 flex flex-wrap items-center justify-between gap-3">
                                <!-- macOS Dots & Template Title -->
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <div class="w-3 h-3 rounded-full bg-rose-400"></div>
                                        <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                                        <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate flex items-center gap-2">
                                            <span>Template:</span>
                                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 font-extrabold border border-slate-200 dark:border-slate-700">
                                                <?= ucwords(str_replace('_', ' ', $activeTemplate)) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Controls: Viewports & Action Buttons -->
                                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                                    <!-- Device Switcher -->
                                    <div class="flex items-center bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                                        <button onclick="setViewport('100%')" id="btn-desktop" title="Desktop View" class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-600 text-white shadow-2xs transition">
                                            <i data-lucide="monitor" class="w-3.5 h-3.5 inline mr-1"></i> Desktop
                                        </button>
                                        <button onclick="setViewport('768px')" id="btn-tablet" title="Tablet View" class="px-2.5 py-1 text-[11px] font-bold rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                                            <i data-lucide="tablet" class="w-3.5 h-3.5 inline mr-1"></i> Tablet
                                        </button>
                                        <button onclick="setViewport('420px')" id="btn-mobile" title="Mobile View" class="px-2.5 py-1 text-[11px] font-bold rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                                            <i data-lucide="smartphone" class="w-3.5 h-3.5 inline mr-1"></i> Mobile
                                        </button>
                                    </div>

                                    <!-- Open Raw In New Tab -->
                                    <a href="?raw=<?= htmlspecialchars($activeTemplate) ?>" target="_blank" 
                                       title="Open Clean Fullscreen in New Window"
                                       class="p-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        <span class="hidden sm:inline">Fullscreen</span>
                                    </a>

                                    <!-- Copy HTML Button -->
                                    <button onclick="copyPreviewHtml()" 
                                            id="btn-copy-html"
                                            title="Copy Raw HTML to Clipboard"
                                            class="p-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
                                        <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        <span class="hidden sm:inline">Copy HTML</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Subject Header Bar -->
                            <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border-b border-slate-200/80 dark:border-slate-800 text-xs flex items-center gap-2">
                                <span class="font-bold text-slate-400 uppercase text-[10px] tracking-wider flex-shrink-0">Subject:</span>
                                <div class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                    <?= htmlspecialchars($activeSubject) ?>
                                </div>
                            </div>

                            <!-- Preview IFrame Container -->
                            <div class="bg-slate-100 dark:bg-slate-950 p-4 flex justify-center items-center overflow-x-auto min-h-[660px]">
                                <iframe id="emailFrame" 
                                        srcdoc="<?= htmlspecialchars($currentPreviewHtml) ?>" 
                                        class="template-preview-frame shadow-md border border-slate-200 dark:border-slate-800"
                                        style="max-width: 100%;"></iframe>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Live Test Email Dispatcher Form (1 Column) -->
                    <div class="space-y-5">
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5">
                            <div class="flex items-center gap-2.5 mb-1.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Live Test Dispatcher
                                </h3>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                                Send this template live to any email address to test rendering in Gmail, Outlook, or Apple Mail.
                            </p>

                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="send_test_email">

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Selected Template</label>
                                    <select name="template_type" class="w-full px-3 py-2.5 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                                        <option value="investor" <?= $activeTemplate === 'investor' ? 'selected' : '' ?>>
                                            1. Investor Confirmation (Capital Inflow)
                                        </option>
                                        <option value="founder" <?= $activeTemplate === 'founder' ? 'selected' : '' ?>>
                                            2. Founder Capital Alert (Investment Received)
                                        </option>
                                        <option value="round_submission" <?= $activeTemplate === 'round_submission' ? 'selected' : '' ?>>
                                            3. Founder Round Request (New Application)
                                        </option>
                                        <option value="round_approved" <?= $activeTemplate === 'round_approved' ? 'selected' : '' ?>>
                                            4. Founder Round Approved &amp; LIVE
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Destination Inbox</label>
                                        <button type="button" onclick="document.getElementById('test_email_input').value='<?= htmlspecialchars($user['email'] ?? '') ?>'" class="text-[10.5px] font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            Use My Email
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                            <i data-lucide="mail" class="w-4 h-4"></i>
                                        </div>
                                        <input type="email" id="test_email_input" name="test_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                                               placeholder="you@company.com"
                                               class="w-full pl-9 pr-3 py-2.5 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Amount (₹)</label>
                                        <input type="number" name="test_amount" value="500000" step="50000" required
                                               class="w-full px-3 py-2.5 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Startup Name</label>
                                        <input type="text" name="test_company" value="TechPulse AI Solutions" required
                                               class="w-full px-3 py-2.5 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-700 shadow-md shadow-blue-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    <span>Dispatch Test Email</span>
                                </button>
                            </form>
                        </div>

                        <!-- Automation Workflow Card -->
                        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5 text-xs space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="zap" class="w-4 h-4 text-amber-500"></i>
                                <span>Autonomous Event Pipeline</span>
                            </h4>
                            <div class="space-y-3 text-slate-600 dark:text-slate-400 text-[11px] pt-1">
                                <div class="flex items-start gap-2.5">
                                    <div class="w-5 h-5 rounded-full bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-[10px] flex-shrink-0">1</div>
                                    <div><strong>Founder Submits Round:</strong> Admin receives review alert &amp; Founder gets instant submission acknowledgment.</div>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <div class="w-5 h-5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-[10px] flex-shrink-0">2</div>
                                    <div><strong>Admin Approves Round:</strong> Founder receives "Round Approved &amp; LIVE" celebration notification with public link.</div>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <div class="w-5 h-5 rounded-full bg-purple-50 dark:bg-purple-950 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold text-[10px] flex-shrink-0">3</div>
                                    <div><strong>Investor Commits Capital:</strong> Dual trigger generates certified receipt for backer and live cap table update for founder.</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <!-- DELIVERY LOGS TABLE -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="scroll-text" class="w-4 h-4 text-indigo-600"></i>
                                <span>Automated Dispatch Audit Logs</span>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Historical trail of every email triggered by startup rounds, investor orders, and escrow allocations.
                            </p>
                        </div>

                        <!-- Search Filter -->
                        <div class="relative w-full sm:w-64">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <input type="text" id="logSearchInput" onkeyup="filterLogsTable()" placeholder="Search recipient or subject..." 
                                   class="w-full pl-9 pr-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <?php if (empty($logs)): ?>
                        <div class="p-16 text-center text-xs text-slate-400">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                <i data-lucide="inbox" class="w-6 h-6"></i>
                            </div>
                            <div class="font-bold text-slate-700 dark:text-slate-300 text-sm">No automated emails logged yet</div>
                            <p class="text-slate-500 mt-1">Dispatched emails will automatically be recorded here for regulatory review.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs" id="logsTable">
                                <thead>
                                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 text-[10.5px] uppercase font-bold text-slate-500 tracking-wider">
                                        <th class="py-3 px-4">Log ID</th>
                                        <th class="py-3 px-4">Recipient</th>
                                        <th class="py-3 px-4">Subject</th>
                                        <th class="py-3 px-4">Template Class</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Timestamp</th>
                                        <th class="py-3 px-4 text-right">Inspection</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <?php foreach ($logs as $l): ?>
                                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition log-row">
                                            <td class="py-3 px-4 font-mono font-bold text-slate-400">#<?= $l['id'] ?></td>
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 flex items-center justify-center text-[10px] font-black">
                                                        <?= strtoupper(substr($l['recipient_name'] ?: ($l['recipient_email'] ?: 'U'), 0, 1)) ?>
                                                    </div>
                                                    <span><?= htmlspecialchars($l['recipient_name'] ?: 'Recipient') ?></span>
                                                </div>
                                                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5 ml-8">
                                                    <?= htmlspecialchars($l['recipient_email']) ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 max-w-xs truncate text-slate-700 dark:text-slate-300 font-medium" title="<?= htmlspecialchars($l['subject']) ?>">
                                                <?= htmlspecialchars($l['subject']) ?>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <?php
                                                    $pillClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                                    if (str_contains($l['template_type'], 'investor')) {
                                                        $pillClass = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800';
                                                    } elseif (str_contains($l['template_type'], 'founder')) {
                                                        $pillClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
                                                    }
                                                ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold border <?= $pillClass ?>">
                                                    <?= htmlspecialchars($l['template_type']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 whitespace-nowrap">
                                                <?php if ($l['status'] === 'sent'): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sent
                                                    </span>
                                                <?php elseif ($l['status'] === 'logged'): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Archived
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800" title="<?= htmlspecialchars($l['error_message'] ?? '') ?>">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Failed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-[11px] whitespace-nowrap font-mono">
                                                <?= date('d M Y, h:i A', strtotime($l['sent_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                                <button onclick="viewLoggedEmail(<?= htmlspecialchars(json_encode($l['body_html'])) ?>, <?= htmlspecialchars(json_encode($l['subject'])) ?>, <?= htmlspecialchars(json_encode($l['recipient_email'])) ?>)"
                                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-blue-600 hover:text-white dark:bg-slate-800 dark:hover:bg-blue-600 text-slate-700 dark:text-slate-300 transition">
                                                    Inspect Payload
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- Modal to inspect full email HTML payload -->
    <div id="emailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-850">
                <div class="min-w-0 pr-4">
                    <div class="font-extrabold text-xs text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="mail-search" class="w-4 h-4 text-blue-600"></i>
                        <span>Dispatched Email Audit Inspector</span>
                    </div>
                    <div id="modalSubject" class="text-xs text-slate-600 dark:text-slate-400 truncate mt-0.5 font-medium"></div>
                </div>
                <button onclick="closeEmailModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 p-4 bg-slate-100 dark:bg-slate-950 overflow-y-auto">
                <iframe id="modalFrame" class="w-full h-[580px] rounded-xl border border-slate-200 dark:border-slate-800 bg-white"></iframe>
            </div>
            <div class="p-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                <div id="modalRecipient" class="text-slate-500 font-mono text-[11px]"></div>
                <button onclick="closeEmailModal()" class="px-4 py-1.5 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden element to hold raw HTML for copying -->
    <textarea id="hiddenRawHtml" class="hidden"><?= htmlspecialchars($currentPreviewHtml) ?></textarea>

    <script>
        lucide.createIcons();

        function setViewport(width) {
            const frame = document.getElementById('emailFrame');
            const btnDesk = document.getElementById('btn-desktop');
            const btnTab = document.getElementById('btn-tablet');
            const btnMob = document.getElementById('btn-mobile');

            [btnDesk, btnTab, btnMob].forEach(b => {
                if (!b) return;
                b.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition';
            });

            if (width === '420px') {
                frame.style.maxWidth = '420px';
                btnMob.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-600 text-white shadow-2xs transition';
            } else if (width === '768px') {
                frame.style.maxWidth = '768px';
                btnTab.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-600 text-white shadow-2xs transition';
            } else {
                frame.style.maxWidth = '100%';
                btnDesk.className = 'px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-600 text-white shadow-2xs transition';
            }
        }

        function copyPreviewHtml() {
            const raw = document.getElementById('hiddenRawHtml').value;
            navigator.clipboard.writeText(raw).then(() => {
                const btn = document.getElementById('btn-copy-html');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5 text-emerald-500 inline mr-1"></i> <span class="text-emerald-600 font-bold">Copied!</span>';
                lucide.createIcons();
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }, 2000);
            }).catch(err => {
                alert('Could not copy HTML automatically.');
            });
        }

        function viewLoggedEmail(htmlContent, subject, recipient) {
            const modal = document.getElementById('emailModal');
            const frame = document.getElementById('modalFrame');
            const subjectEl = document.getElementById('modalSubject');
            const recipEl = document.getElementById('modalRecipient');

            if (subjectEl) subjectEl.textContent = subject || 'No Subject';
            if (recipEl) recipEl.textContent = 'To: ' + (recipient || 'N/A');
            frame.srcdoc = htmlContent;
            modal.classList.remove('hidden');
        }

        function closeEmailModal() {
            document.getElementById('emailModal').classList.add('hidden');
        }

        function filterLogsTable() {
            const q = document.getElementById('logSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('.log-row');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = text.includes(q) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
