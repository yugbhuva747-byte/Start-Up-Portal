<?php
/**
 * Admin Module: Automated Email Templates & Delivery Monitor
 * Allows previewing the Investor and Founder email templates, testing live dispatches, and reviewing logs.
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('admin');
$db = get_db();
$pageTitle = 'Automated Email Templates & Dispatch Monitor';

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
            } else {
                $customFounder = $sampleFounder;
                $customFounder['email'] = $testEmail;
                $html = render_founder_investment_email($customFounder, $sampleInvestor, $sampleRound, $customCompany, $customInvestment);
                $subject = "🎉 [TEST] Investment Alert: {$sampleInvestor['name']} committed " . format_inr($testAmount) . " to {$customCompany['name']}";
                $result = send_system_email($testEmail, $customFounder['name'], $subject, $html, 'founder_investment_alert_test');
            }

            if ($result['success']) {
                $successMsg = "Test email processed! Status: <strong>" . strtoupper($result['status']) . "</strong>. Saved to archive & database.";
            } else {
                $errorMsg = "Email processing note: " . htmlspecialchars($result['message']);
            }
        }
    }
}

// Fetch Recent Email Logs
$logs = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM email_logs ORDER BY sent_at DESC LIMIT 30");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Pre-render templates for live preview in iframe
$investorHtml = render_investor_congratulations_email($sampleInvestor, $sampleRound, $sampleCompany, $sampleInvestment);
$founderHtml = render_founder_investment_email($sampleFounder, $sampleInvestor, $sampleRound, $sampleCompany, $sampleInvestment);

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">

    <!-- Admin Sidebar -->
    <?php include __DIR__ . '/../includes/admin/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-20">
            <div>
                <h1 class="text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="mail-check" class="w-5 h-5 text-indigo-600"></i>
                    <span>Automated Investment Email Templates</span>
                </h1>
                <p class="text-xs text-slate-500">Live preview, test dispatch, and automated delivery records for Investor & Founder notifications</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                    Automated Direct Dispatch Active
                </span>
            </div>
        </header>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-7xl w-full mx-auto">
            
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="card-clean rounded-xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mail Driver</div>
                    <div class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
                        <i data-lucide="send" class="w-4 h-4 text-indigo-600"></i>
                        <span><?= strtoupper(MAIL_MAILER) ?> Delivery</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Automatic direct trigger upon funding</div>
                </div>

                <div class="card-clean rounded-xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sender Address</div>
                    <div class="text-sm font-extrabold text-slate-800 truncate" title="<?= htmlspecialchars(MAIL_FROM_ADDRESS) ?>">
                        <?= htmlspecialchars(MAIL_FROM_ADDRESS) ?>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1"><?= htmlspecialchars(MAIL_FROM_NAME) ?></div>
                </div>

                <div class="card-clean rounded-xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Investor Trigger</div>
                    <div class="text-sm font-extrabold text-emerald-600 flex items-center gap-1.5">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Congratulations Template</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Sent instantly to backer's inbox</div>
                </div>

                <div class="card-clean rounded-xl p-4">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Founder Trigger</div>
                    <div class="text-sm font-extrabold text-indigo-600 flex items-center gap-1.5">
                        <i data-lucide="bell-ring" class="w-4 h-4"></i>
                        <span>Investment Received Alert</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1">Sent directly to all company founders</div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex items-center space-x-2 border-b border-slate-200 pb-3">
                <a href="?tab=preview&template=investor" 
                   class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 <?= ($activeTab === 'preview' && $activeTemplate === 'investor') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <i data-lucide="award" class="w-3.5 h-3.5"></i>
                    <span>Template 1: Investor Congratulations</span>
                </a>

                <a href="?tab=preview&template=founder" 
                   class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 <?= ($activeTab === 'preview' && $activeTemplate === 'founder') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <i data-lucide="bell" class="w-3.5 h-3.5"></i>
                    <span>Template 2: Founder Capital Alert</span>
                </a>

                <a href="?tab=logs" 
                   class="px-4 py-2 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 <?= $activeTab === 'logs' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <i data-lucide="list" class="w-3.5 h-3.5"></i>
                    <span>Delivery Logs & Archives (<?= count($logs) ?>)</span>
                </a>
            </div>

            <?php if ($activeTab === 'preview'): ?>
                <!-- PREVIEW & TEST SEND SECTION -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left: Interactive Preview Frame (2 Columns) -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="card-clean rounded-2xl p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-100 mb-3">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <?php if ($activeTemplate === 'investor'): ?>
                                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                            <span>Previewing: Investor Congratulations Template</span>
                                        <?php else: ?>
                                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                            <span>Previewing: Founder Investment Alert Template</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        Subject: <?= $activeTemplate === 'investor' 
                                            ? "🚀 Congratulations! Your investment in TechPulse AI Solutions is confirmed (₹5,00,000)" 
                                            : "🎉 Investment Alert: Vikramaditya Singhania committed ₹5,00,000 to TechPulse AI Solutions" ?>
                                    </div>
                                </div>

                                <!-- Viewport Toggle -->
                                <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-lg">
                                    <button onclick="setViewport('100%')" id="btn-desktop" class="px-2.5 py-1 text-[11px] font-bold rounded bg-white text-slate-800 shadow-xs">
                                        Desktop
                                    </button>
                                    <button onclick="setViewport('420px')" id="btn-mobile" class="px-2.5 py-1 text-[11px] font-bold rounded text-slate-600 hover:text-slate-900">
                                        Mobile
                                    </button>
                                </div>
                            </div>

                            <!-- Preview IFrame Container -->
                            <div class="bg-slate-900/5 p-4 rounded-xl flex justify-center items-center overflow-x-auto min-h-[620px]">
                                <iframe id="emailFrame" 
                                        srcdoc="<?= htmlspecialchars($activeTemplate === 'investor' ? $investorHtml : $founderHtml) ?>" 
                                        class="w-full transition-all duration-300 rounded-xl shadow-lg border border-slate-200"
                                        style="height: 680px; max-width: 100%; background: #ffffff;"></iframe>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Live Test Email Dispatcher Form (1 Column) -->
                    <div class="space-y-4">
                        <div class="card-clean rounded-2xl p-5">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-1 flex items-center gap-1.5">
                                <i data-lucide="send" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Send Live Test Email</span>
                            </h3>
                            <p class="text-xs text-slate-500 mb-4">
                                Test auto-dispatching this template to your personal inbox right now.
                            </p>

                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="send_test_email">

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Choose Template</label>
                                    <select name="template_type" class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 bg-slate-50 font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="investor" <?= $activeTemplate === 'investor' ? 'selected' : '' ?>>
                                            Investor Congratulations Template
                                        </option>
                                        <option value="founder" <?= $activeTemplate === 'founder' ? 'selected' : '' ?>>
                                            Founder Funding Alert Template
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Send To Email Address</label>
                                    <input type="email" name="test_email" value="<?= htmlspecialchars($user['email']) ?>" required
                                           placeholder="youremail@example.com"
                                           class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div class="text-[10px] text-slate-400 mt-1">Defaults to your logged-in email</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Sample Investment Amount (₹)</label>
                                    <input type="number" name="test_amount" value="500000" step="50000" required
                                           class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Startup Name</label>
                                    <input type="text" name="test_company" value="TechPulse AI Solutions" required
                                           class="w-full text-xs rounded-lg border border-slate-200 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <button type="submit" 
                                        class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm shadow-indigo-600/20 transition flex items-center justify-center space-x-1.5">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    <span>Dispatch Test Email Now</span>
                                </button>
                            </form>
                        </div>

                        <!-- Template Anatomy Guide -->
                        <div class="card-clean rounded-2xl p-5 text-xs space-y-3">
                            <h4 class="font-extrabold text-slate-800 flex items-center gap-1.5">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                                <span>Automation Workflow</span>
                            </h4>
                            <div class="text-slate-600 space-y-2">
                                <div class="flex items-start gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] flex-shrink-0">1</span>
                                    <span>Investor commits capital on <code class="text-[11px] bg-slate-100 px-1 py-0.5 rounded">investor/invest.php</code>.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] flex-shrink-0">2</span>
                                    <span>Transaction is committed atomically into escrow.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] flex-shrink-0">3</span>
                                    <span><strong>Investor Email:</strong> Congratulatory receipt with equity %, certificate # & transaction ID.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] flex-shrink-0">4</span>
                                    <span><strong>Founder Email:</strong> Direct alert with backer contact details, capital amount & cap table link.</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <?php else: ?>
                <!-- DELIVERY LOGS TABLE -->
                <div class="card-clean rounded-2xl overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Automated Dispatch Logs</h3>
                            <p class="text-xs text-slate-500">Every investment email triggered by the platform is archived here</p>
                        </div>
                    </div>

                    <?php if (empty($logs)): ?>
                        <div class="p-12 text-center text-xs text-slate-400">
                            <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                            <div>No automated emails logged yet. They will appear here once investments take place or you run a test send!</div>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100 text-[10.5px]">
                                    <tr>
                                        <th class="py-3 px-4">ID</th>
                                        <th class="py-3 px-4">Recipient</th>
                                        <th class="py-3 px-4">Subject</th>
                                        <th class="py-3 px-4">Type</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Date & Time</th>
                                        <th class="py-3 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($logs as $l): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-4 font-mono font-bold text-slate-500">#<?= $l['id'] ?></td>
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-800"><?= htmlspecialchars($l['recipient_name'] ?: 'Recipient') ?></div>
                                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($l['recipient_email']) ?></div>
                                            </td>
                                            <td class="py-3 px-4 max-w-xs truncate text-slate-700 font-semibold" title="<?= htmlspecialchars($l['subject']) ?>">
                                                <?= htmlspecialchars($l['subject']) ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold <?= str_contains($l['template_type'], 'investor') ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                                                    <?= htmlspecialchars($l['template_type']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($l['status'] === 'sent'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <i data-lucide="check" class="w-3 h-3 mr-1"></i> SENT
                                                    </span>
                                                <?php elseif ($l['status'] === 'logged'): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                        <i data-lucide="archive" class="w-3 h-3 mr-1"></i> ARCHIVED
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="<?= htmlspecialchars($l['error_message'] ?? '') ?>">
                                                        <i data-lucide="x" class="w-3 h-3 mr-1"></i> FAILED
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-slate-500 text-[11px] whitespace-nowrap">
                                                <?= date('d M Y, h:i A', strtotime($l['sent_at'])) ?>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <button onclick="viewLoggedEmail(<?= htmlspecialchars(json_encode($l['body_html'])) ?>)"
                                                        class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
                                                    Inspect HTML
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
    <div id="emailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden border border-slate-200">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="font-bold text-sm text-slate-800">Dispatched HTML Email Preview</div>
                <button onclick="closeEmailModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="flex-1 p-4 bg-slate-100 overflow-y-auto">
                <iframe id="modalFrame" class="w-full h-[600px] rounded-xl border border-slate-200 bg-white"></iframe>
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
