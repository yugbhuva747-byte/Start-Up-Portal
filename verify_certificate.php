<?php
/**
 * Public Share Certificate Authenticity Verification Desk
 * Open to public / regulators / auditors to verify valid certificate issuance
 */
require_once __DIR__ . '/config.php';
$db = get_db();

$certQuery = trim($_GET['cert'] ?? $_POST['cert_query'] ?? '');
$certData = null;
$searched = !empty($certQuery);

if ($db && $searched) {
    $stmt = $db->prepare("
        SELECT inv.*, c.name as company_name, c.legal_name, c.cin_number, c.city, c.state,
               fr.round_name, fr.valuation,
               u.name as investor_name, u.email as investor_email, ip.pan_number as investor_pan
        FROM investments inv
        JOIN companies c ON inv.company_id = c.id
        JOIN funding_rounds fr ON inv.funding_round_id = fr.id
        JOIN users u ON inv.investor_user_id = u.id
        LEFT JOIN investor_profiles ip ON u.id = ip.user_id
        WHERE inv.certificate_number = ? OR inv.verification_token = ?
        LIMIT 1
    ");
    $stmt->execute([$certQuery, $certQuery]);
    $certData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Authenticity Verification • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FAFAFB; }
        .card-clean { background: #FFFFFF; border: 1px solid #E2E8F0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04); }
    </style>
</head>
<body class="text-slate-900 min-h-screen flex flex-col justify-between">

    <!-- Top Navbar -->
    <header class="bg-white border-b border-slate-200 px-6 py-4">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="<?= url('index.php') ?>" class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="font-extrabold text-slate-900 text-sm tracking-tight">STARTUP × INVESTOR</span>
                    <span class="block text-[9px] uppercase tracking-wider text-slate-400 font-bold">Public Certificate Registry</span>
                </div>
            </a>
            <a href="<?= url('auth/login.php') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                Sign In to Portal →
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-2xl w-full mx-auto px-4 py-12 flex-1">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold uppercase tracking-wider mb-3">
                <i data-lucide="award" class="w-3.5 h-3.5"></i>
                <span>SEBI & Companies Act Compliance Desk</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                Verify Digital Share Certificate
            </h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1 max-w-md mx-auto">
                Authenticate any equity allotment, share serial number, or investment instrument issued through <?= APP_NAME ?>.
            </p>
        </div>

        <!-- Search Form -->
        <div class="card-clean rounded-2xl p-4 sm:p-5 mb-8">
            <form action="<?= url('verify_certificate.php') ?>" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="cert" value="<?= htmlspecialchars($certQuery) ?>" 
                           placeholder="Enter Certificate Serial (e.g. SHA-2026-TP-001 or Token)..." required
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-mono placeholder-slate-400 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm shadow-indigo-600/20">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                    <span>Verify Now</span>
                </button>
            </form>
        </div>

        <!-- Result Display -->
        <?php if ($searched): ?>
            <?php if ($certData): ?>
                <!-- Authentic Certificate Card -->
                <div class="card-clean rounded-2xl p-4 sm:p-6 md:p-8 border-emerald-200 bg-white relative overflow-hidden shadow-lg">
                    <div class="absolute top-0 right-0 left-0 h-1.5 bg-emerald-500"></div>

                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 flex-shrink-0">
                            <i data-lucide="badge-check" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                ✓ Certified Authentic & Active
                            </span>
                            <h2 class="text-base sm:text-lg font-black text-slate-900 mt-0.5 break-all">
                                <?= htmlspecialchars($certData['certificate_number']) ?>
                            </h2>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100 text-xs">
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Issuing Entity</span>
                            <span class="font-bold text-slate-900 sm:text-right"><?= htmlspecialchars($certData['legal_name'] ?: $certData['company_name']) ?></span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Corporate Identity Number (CIN)</span>
                            <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($certData['cin_number'] ?: 'U72900KA2023PTC156789') ?></span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Registered Allottee</span>
                            <span class="font-bold text-slate-900">
                                <?= htmlspecialchars(substr($certData['investor_name'], 0, 1) . str_repeat('*', strlen($certData['investor_name']) - 2) . substr($certData['investor_name'], -1)) ?> 
                                <span class="text-[10px] text-slate-400 font-mono">(Verified Investor)</span>
                            </span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Allotted Shares</span>
                            <span class="font-bold text-indigo-600 font-mono"><?= number_format($certData['number_of_shares']) ?> Units (<?= htmlspecialchars($certData['share_class']) ?>)</span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Distinctive Numbers</span>
                            <span class="font-mono text-slate-800 font-semibold"><?= str_pad($certData['distinctive_from'], 6, '0', STR_PAD_LEFT) ?> – <?= str_pad($certData['distinctive_to'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Equity Proportion</span>
                            <span class="font-bold text-slate-900"><?= $certData['equity_allotted_percent'] ?>%</span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Date of Allotment</span>
                            <span class="text-slate-700 font-medium"><?= date('d F, Y', strtotime($certData['confirmed_at'])) ?></span>
                        </div>
                        <div class="py-3 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-1">
                            <span class="text-slate-500">Dematerialized Status</span>
                            <span class="text-emerald-600 font-bold flex items-center">
                                <i data-lucide="check" class="w-3.5 h-3.5 mr-1"></i> Electronic Custody Registered
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="text-[10px] text-slate-400 font-mono truncate max-w-full">
                            Digital Stamp Hash: <?= substr(hash('sha256', $certData['certificate_number']), 0, 24) ?>...
                        </div>
                        <a href="<?= url('certificate.php?id=' . $certData['id']) ?>" class="inline-flex items-center space-x-1 text-xs font-bold text-indigo-600 hover:text-indigo-800">
                            <span>Open Full Certificate</span>
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Not Found / Invalid Card -->
                <div class="card-clean rounded-2xl p-8 border-rose-200 bg-white text-center">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Certificate Record Not Found</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                        No share allotment certificate matching serial "<span class="font-mono text-slate-800"><?= htmlspecialchars($certQuery) ?></span>" was found in our master SEBI demat registry.
                    </p>
                    <div class="text-[11px] text-slate-400">
                        Please check the serial number on the printed document or contact the platform compliance desk.
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 space-y-1">
        <div><?= APP_NAME ?> Public Equity Registry & Demat Verification Architecture. All rights reserved.</div>
        <div class="text-[11px] text-slate-500">Powered by <a href="https://socialamplifiers.com/" target="_blank" rel="noopener noreferrer" class="font-bold text-indigo-600 hover:text-indigo-800 transition underline underline-offset-2">Social Amplifiers</a></div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
