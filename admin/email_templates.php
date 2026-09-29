<?php
/**
 * Admin Module: Automated Email Templates & Delivery Monitor
 * Modern, clean interface for previewing, testing, and reviewing email dispatches.
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

// Handle Test Send Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test_email') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid security token.';
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
                $successMsg = "Test email dispatched successfully! Status: " . strtoupper($result['status']) . ".";
            } else {
                $errorMsg = "Email processing notice: " . htmlspecialchars($result['message']);
            }
        }
    }
}

// Fetch Recent Email Logs
$logs = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 40");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Pre-render templates for live preview in iframe
$investorHtml = render_investor_congratulations_email($sampleInvestor, $sampleRound, $sampleCompany, $sampleInvestment);
$founderHtml = render_founder_investment_email($sampleFounder, $sampleInvestor, $sampleRound, $sampleCompany, $sampleInvestment);
$roundSubmittedHtml = render_founder_round_submitted_email($sampleFounder, $sampleCompany, $sampleRound);
$roundApprovedHtml = render_founder_round_status_email($sampleFounder, $sampleCompany, $sampleRound, 'LIVE');

$activeTab = $_GET['tab'] ?? 'preview';
$activeTemplate = $_GET['template'] ?? 'investor';
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

        <main class="p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto">
            
            <!-- Page Title Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="admin-page-icon">
                        <i data-lucide="mail-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Automated Email Templates</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Preview transactional notifications, dispatch test payloads, and inspect logs.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="admin-badge badge-success">
                        <span class="admin-badge-dot"></span>
                        <?= strtoupper(MAIL_MAILER) ?> Service Active
                    </span>
                </div>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                    <div><?= $successMsg ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="p-4 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 flex-shrink-0"></i>
                    <div><?= $errorMsg ?></div>
                </div>
            <?php endif; ?>

            <!-- System Mail Driver Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="admin-stat-card">
                    <div class="admin-stat-label">Mail Driver</div>
                    <div class="admin-stat-value stat-value-indigo flex items-center gap-1.5 text-base">
                        <i data-lucide="send" class="w-4 h-4 text-indigo-600"></i>
                        <span><?= strtoupper(MAIL_MAILER) ?> Direct</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Instant trigger upon funding</div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-label">Sender Address</div>
                    <div class="admin-stat-value stat-value-sky text-sm truncate" title="<?= htmlspecialchars(MAIL_FROM_ADDRESS) ?>">
                        <?= htmlspecialchars(MAIL_FROM_ADDRESS) ?>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1"><?= htmlspecialchars(MAIL_FROM_NAME) ?></div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-label">Investor Notification</div>
                    <div class="admin-stat-value stat-value-emerald flex items-center gap-1.5 text-sm">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Investment Confirmed</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Receipt & share allocation</div>
                </div>

                <div class="admin-stat-card">
                    <div class="admin-stat-label">Founder Notification</div>
                    <div class="admin-stat-value stat-value-violet flex items-center gap-1.5 text-sm">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        <span>Capital Alert</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Backer details & updated cap table</div>
                </div>
            </div>

            <!-- Segmented Filter Bar -->
            <div class="admin-filter-bar">
                <div class="flex items-center gap-1.5 overflow-x-auto py-1">
                    <a href="?tab=preview&template=investor" 
                       class="admin-filter-pill <?= ($activeTab === 'preview' && $activeTemplate === 'investor') ? 'active' : '' ?>">
                        <i data-lucide="award" class="w-3.5 h-3.5 mr-1.5 inline"></i>
                        Investor Confirmation
                    </a>

                    <a href="?tab=preview&template=founder" 
                       class="admin-filter-pill <?= ($activeTab === 'preview' && $activeTemplate === 'founder') ? 'active' : '' ?>">
                        <i data-lucide="bell" class="w-3.5 h-3.5 mr-1.5 inline"></i>
                        Founder Capital Alert
                    </a>

                    <a href="?tab=preview&template=round_submission" 
                       class="admin-filter-pill <?= ($activeTab === 'preview' && $activeTemplate === 'round_submission') ? 'active' : '' ?>">
                        <i data-lucide="file-plus" class="w-3.5 h-3.5 mr-1.5 inline"></i>
                        Founder Round Request
                    </a>

                    <a href="?tab=preview&template=round_approved" 
                       class="admin-filter-pill <?= ($activeTab === 'preview' && $activeTemplate === 'round_approved') ? 'active' : '' ?>">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 mr-1.5 inline"></i>
                        Round Approved & LIVE
                    </a>

                    <a href="?tab=logs" 
                       class="admin-filter-pill <?= $activeTab === 'logs' ? 'active' : '' ?>">
                        <i data-lucide="list" class="w-3.5 h-3.5 mr-1.5 inline"></i>
                        Delivery Logs (<?= count($logs) ?>)
                    </a>
                </div>
            </div>

            <?php if ($activeTab === 'preview'): ?>
                <!-- PREVIEW & TEST DISPATCH SECTION -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left: Interactive Preview Frame (2 Columns) -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="admin-card">
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-3">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                                        <?php if ($activeTemplate === 'investor'): ?>
                                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                            <span>Investor Confirmation Template</span>
                                        <?php elseif ($activeTemplate === 'founder'): ?>
                                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                            <span>Founder Capital Alert Template</span>
                                        <?php elseif ($activeTemplate === 'round_submission'): ?>
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            <span>Founder Funding Round Request Template</span>
                                        <?php else: ?>
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>Round Approved & LIVE Template</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Subject: <?php
                                            if ($activeTemplate === 'investor') {
                                                echo "🚀 Congratulations! Your investment in TechPulse AI Solutions is confirmed (₹5,00,000)";
                                            } elseif ($activeTemplate === 'founder') {
                                                echo "🎉 Investment Alert: Vikramaditya Singhania committed ₹5,00,000 to TechPulse AI Solutions";
                                            } elseif ($activeTemplate === 'round_submission') {
                                                echo "🚀 Funding Round Application Received: TechPulse AI Solutions (Seed Growth Round)";
                                            } else {
                                                echo "🎉 Your Funding Round is Approved & LIVE for Investors: TechPulse AI Solutions";
                                            }
                                        ?>
                                    </div>
                                </div>

                                <!-- Viewport Toggle -->
                                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                                    <button onclick="setViewport('100%')" id="btn-desktop" class="px-2.5 py-1 text-[11px] font-bold rounded bg-white text-slate-800 shadow-xs">
                                        Desktop
                                    </button>
                                    <button onclick="setViewport('420px')" id="btn-mobile" class="px-2.5 py-1 text-[11px] font-bold rounded text-slate-600 hover:text-slate-900">
                                        Mobile
                                    </button>
                                </div>
                            </div>

                            <!-- Preview IFrame Container -->
                            <?php
                                $currentPreviewHtml = $investorHtml;
                                if ($activeTemplate === 'founder') $currentPreviewHtml = $founderHtml;
                                elseif ($activeTemplate === 'round_submission') $currentPreviewHtml = $roundSubmittedHtml;
                                elseif ($activeTemplate === 'round_approved') $currentPreviewHtml = $roundApprovedHtml;
                            ?>
                            <div class="bg-slate-50 p-3 rounded-xl flex justify-center items-center overflow-x-auto min-h-[580px] border border-slate-100">
                                <iframe id="emailFrame" 
                                        srcdoc="<?= htmlspecialchars($currentPreviewHtml) ?>" 
                                        class="w-full transition-all duration-300 rounded-xl shadow-xs border border-slate-200"
                                        style="height: 640px; max-width: 100%; background: #ffffff;"></iframe>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Live Test Email Dispatcher Form (1 Column) -->
                    <div class="space-y-4">
                        <div class="admin-card">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-1.5">
                                <i data-lucide="send" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Send Live Test Email</span>
                            </h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Test auto-dispatching any dynamic template to your personal inbox.
                            </p>

                            <form method="POST" class="space-y-3.5">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="send_test_email">

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Choose Template</label>
                                    <select name="template_type" class="admin-input">
                                        <option value="investor" <?= $activeTemplate === 'investor' ? 'selected' : '' ?>>
                                            1. Investor Confirmation (Capital Inflow)
                                        </option>
                                        <option value="founder" <?= $activeTemplate === 'founder' ? 'selected' : '' ?>>
                                            2. Founder Capital Alert (Investment Received)
                                        </option>
                                        <option value="round_submission" <?= $activeTemplate === 'round_submission' ? 'selected' : '' ?>>
                                            3. Founder Round Request (When Founder Wants Fund)
                                        </option>
                                        <option value="round_approved" <?= $activeTemplate === 'round_approved' ? 'selected' : '' ?>>
                                            4. Founder Round Approved & LIVE
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Email</label>
                                    <input type="email" name="test_email" value="<?= htmlspecialchars($user['email']) ?>" required
                                           placeholder="youremail@example.com"
                                           class="admin-input">
                                    <div class="text-[10px] text-slate-400 mt-1">Pre-filled with your current admin email</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sample Investment (₹)</label>
                                    <input type="number" name="test_amount" value="500000" step="50000" required
                                           class="admin-input">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Company / Startup Name</label>
                                    <input type="text" name="test_company" value="TechPulse AI Solutions" required
                                           class="admin-input">
                                </div>

                                <button type="submit" class="admin-btn-primary w-full justify-center py-2.5">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5 mr-1.5"></i>
                                    <span>Dispatch Test Email</span>
                                </button>
                            </form>
                        </div>

                        <!-- Template Anatomy Guide -->
                        <div class="admin-card text-xs space-y-3">
                            <h4 class="font-bold text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                                <span>Automation Workflow</span>
                            </h4>
                            <div class="text-slate-600 space-y-2.5 text-[11px]">
                                <div class="flex items-start gap-2">
                                    <span class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[9px] flex-shrink-0 text-slate-600">1</span>
                                    <span>Investor commits capital on platform.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[9px] flex-shrink-0 text-slate-600">2</span>
                                    <span>Transaction is committed atomically into escrow ledger.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[9px] flex-shrink-0 text-slate-600">3</span>
                                    <span><strong>Investor Receipt:</strong> Contains equity %, cert # & transaction ID.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[9px] flex-shrink-0 text-slate-600">4</span>
                                    <span><strong>Founder Alert:</strong> Contains backer contact details & cap table link.</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <!-- DELIVERY LOGS TABLE -->
                <div class="admin-table-container">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Automated Dispatch Logs</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Every investment email triggered by the platform is archived here.</p>
                        </div>
                    </div>

                    <?php if (empty($logs)): ?>
                        <div class="p-12 text-center text-xs text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                            <div>No automated emails logged yet.</div>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Recipient</th>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Timestamp</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $l): ?>
                                        <tr>
                                            <td class="font-mono text-slate-500">#<?= $l['id'] ?></td>
                                            <td>
                                                <div class="font-semibold text-slate-800"><?= htmlspecialchars($l['recipient_name'] ?: 'Recipient') ?></div>
                                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($l['recipient_email']) ?></div>
                                            </td>
                                            <td class="max-w-xs truncate text-slate-700 font-medium" title="<?= htmlspecialchars($l['subject']) ?>">
                                                <?= htmlspecialchars($l['subject']) ?>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?= str_contains($l['template_type'], 'investor') ? 'badge-primary' : 'badge-success' ?>">
                                                    <?= htmlspecialchars($l['template_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($l['status'] === 'sent'): ?>
                                                    <span class="admin-badge badge-success">
                                                        <span class="admin-badge-dot"></span> Sent
                                                    </span>
                                                <?php elseif ($l['status'] === 'logged'): ?>
                                                    <span class="admin-badge badge-neutral">
                                                        <span class="admin-badge-dot"></span> Archived
                                                    </span>
                                                <?php else: ?>
                                                    <span class="admin-badge badge-danger" title="<?= htmlspecialchars($l['error_message'] ?? '') ?>">
                                                        <span class="admin-badge-dot"></span> Failed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-slate-500 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y, h:i A', strtotime($l['sent_at'])) ?>
                                            </td>
                                            <td class="text-right">
                                                <button onclick="viewLoggedEmail(<?= htmlspecialchars(json_encode($l['body_html'])) ?>)"
                                                        class="admin-btn-secondary text-[11px] py-1 px-2.5">
                                                    Inspect
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

    <!-- Modal to inspect full email HTML -->
    <div id="emailModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-xl overflow-hidden border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="font-bold text-xs text-slate-800 uppercase tracking-wider">Dispatched Email Preview</div>
                <button onclick="closeEmailModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="flex-1 p-4 bg-slate-50 overflow-y-auto">
                <iframe id="modalFrame" class="w-full h-[580px] rounded-xl border border-slate-200 bg-white"></iframe>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function setViewport(width) {
            const frame = document.getElementById('emailFrame');
            const btnDesk = document.getElementById('btn-desktop');
            const btnMob = document.getElementById('btn-mobile');

            if (width === '420px') {
                frame.style.maxWidth = '420px';
                btnMob.className = 'px-2.5 py-1 text-[11px] font-bold rounded bg-white text-slate-800 shadow-xs';
                btnDesk.className = 'px-2.5 py-1 text-[11px] font-bold rounded text-slate-600 hover:text-slate-900';
            } else {
                frame.style.maxWidth = '100%';
                btnDesk.className = 'px-2.5 py-1 text-[11px] font-bold rounded bg-white text-slate-800 shadow-xs';
                btnMob.className = 'px-2.5 py-1 text-[11px] font-bold rounded text-slate-600 hover:text-slate-900';
            }
        }

        function viewLoggedEmail(htmlContent) {
            const modal = document.getElementById('emailModal');
            const frame = document.getElementById('modalFrame');
            frame.srcdoc = htmlContent;
            modal.classList.remove('hidden');
        }

        function closeEmailModal() {
            document.getElementById('emailModal').classList.add('hidden');
        }
    </script>
</body>
</html>
