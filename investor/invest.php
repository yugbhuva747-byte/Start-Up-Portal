<?php
/**
 * Investor Module: Investment Commitment & Escrow Workflow
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('investor');
$db = get_db();
$pageTitle = 'Investment Commitment & Escrow';

$roundHash = $_GET['round'] ?? '';
$roundId = hash_id_decode($roundHash);

if ($roundId === 0) {
    header('Location: ' . url('investor/discover.php'));
    exit;
}

$round = null;
$company = null;
$error = '';
$success = false;
$transactionRef = '';
$equityAllotted = 0;

if ($db) {
    // 1. Fetch round & company
    $rStmt = $db->prepare("
        SELECT fr.*, c.name as company_name, c.cin_number, c.industry, c.logo_url, c.id as comp_id
        FROM funding_rounds fr
        JOIN companies c ON fr.company_id = c.id
        WHERE fr.id = ?
    ");
    $rStmt->execute([$roundId]);
    $round = $rStmt->fetch();

    if (!$round || !in_array($round['status'], ['LIVE', 'PARTIALLY_FUNDED'])) {
        set_flash('error', 'This funding round is not currently accepting investment commitments.');
        header('Location: ' . url('investor/discover.php'));
        exit;
    }
}

// Handle Investment Order Submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $termsAccepted = isset($_POST['terms_accepted']);
        $remainingGap = max(0, $round['target_amount'] - $round['amount_raised']);

        if (!$termsAccepted) {
            $error = 'You must review and accept the Risk Disclosure & Escrow Terms.';
        } elseif ($amount < $round['min_investment']) {
            $error = "Minimum investment commitment for this round is " . format_inr($round['min_investment']) . ".";
        } elseif (!empty($round['max_investment']) && $amount > $round['max_investment']) {
            $error = "Maximum investment ticket size is " . format_inr($round['max_investment']) . ".";
        } elseif ($amount > $remainingGap) {
            $error = "Investment amount exceeds the remaining round capacity (" . format_inr($remainingGap) . ").";
        } else {
            // Process Investment Transaction (Atomically)
            try {
                $db->beginTransaction();

                // 1. Create Investment Order
                $ioStmt = $db->prepare("
                    INSERT INTO investment_orders (funding_round_id, investor_user_id, amount, status, terms_accepted, created_at)
                    VALUES (?, ?, ?, 'CONFIRMED', 1, NOW())
                ");
                $ioStmt->execute([$roundId, $user['id'], $amount]);
                $orderId = $db->lastInsertId();

                // 2. Calculate Equity Allotted
                // Formula: (Amount / Valuation) * 100
                $equityPercent = $round['valuation'] > 0 ? round(($amount / $round['valuation']) * 100, 3) : 0.00;
                $certNumber = 'CERT-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $round['company_name']), 0, 3)) . '-' . date('Y') . '-' . rand(100, 999);

                // 3. Create Confirmed Investment Record
                $invStmt = $db->prepare("
                    INSERT INTO investments (order_id, funding_round_id, investor_user_id, company_id, amount_invested, equity_allotted_percent, certificate_number, confirmed_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $invStmt->execute([$orderId, $roundId, $user['id'], $round['company_id'], $amount, $equityPercent, $certNumber]);
                $investmentId = $db->lastInsertId();

                // 4. Create Transaction / Escrow Record
                $txnRef = 'TXN-ESC-' . rand(1000000, 9999999);
                $txStmt = $db->prepare("
                    INSERT INTO transactions (investment_id, order_id, transaction_ref, payment_mode, amount, status, created_at)
                    VALUES (?, ?, ?, 'Escrow Wire / UPI', ?, 'SUCCESS', NOW())
                ");
                $txStmt->execute([$investmentId, $orderId, $txnRef, $amount]);

                // 5. Update Funding Round amount_raised & State Machine
                $newRaised = $round['amount_raised'] + $amount;
                $newStatus = ($newRaised >= $round['target_amount']) ? 'FULLY_FUNDED' : 'PARTIALLY_FUNDED';

                $updRound = $db->prepare("UPDATE funding_rounds SET amount_raised = ?, status = ? WHERE id = ?");
                $updRound->execute([$newRaised, $newStatus, $roundId]);

                // 6. Notify Founders of this company
                $fQuery = $db->prepare("SELECT user_id FROM company_founders WHERE company_id = ?");
                $fQuery->execute([$round['company_id']]);
                $foundersList = $fQuery->fetchAll();
                foreach ($foundersList as $f) {
                    send_notification($f['user_id'], 'New Investment Confirmed!', "{$user['name']} committed " . format_inr($amount) . " to your {$round['round_name']}.", 'success', 'founder/funding_rounds.php');
                }

                // Notify Investor
                send_notification($user['id'], 'Investment Order Confirmed', "Your commitment of " . format_inr($amount) . " in {$round['company_name']} was successfully processed into escrow.", 'success', 'investor/portfolio.php');

                // 7. Immutable Audit Trail Log
                log_audit($user['id'], 'EXECUTE_INVESTMENT', 'investments', $investmentId, "Investor committed " . format_inr($amount) . " into {$round['company_name']} round. Ref: {$txnRef}");

                $db->commit();

                // 8. Automatically send Direct Emails to Investor & Founder(s)
                $emailDispatch = send_investment_automated_emails(
                    $db,
                    (int)$investmentId,
                    (int)$roundId,
                    (int)$user['id'],
                    (float)$amount,
                    (float)$equityPercent,
                    (string)$certNumber,
                    (string)$txnRef
                );

                $success = true;
                $transactionRef = $txnRef;
                $equityAllotted = $equityPercent;

            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to execute investment transaction: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commit Capital • <?= htmlspecialchars($round['company_name']) ?> • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
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
    
    <!-- Investor Sidebar -->
    <?php include __DIR__ . '/../includes/investor/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/investor/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-3xl w-full mx-auto" id="invest-main">
            
            <!-- Breadcrumb -->
            <div class="flex items-center space-x-2 text-xs text-slate-400">
                <a href="<?= url('investor/startup_detail.php?id=' . hash_id_encode($round['company_id'])) ?>" class="hover:text-slate-800 transition flex items-center space-x-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Deal Room</span>
                </a>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Investment Commitment</span>
            </div>

            <?php if ($success): ?>
                <!-- SUCCESS CONFIRMATION VIEW -->
                <div class="card-clean rounded-2xl p-8 md:p-10 text-center relative">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="check-circle" class="w-8 h-8"></i>
                    </div>

                    <h1 class="text-xl md:text-2xl font-black text-slate-900 mb-1.5">Investment Successfully Confirmed!</h1>
                    <p class="text-xs text-slate-500 max-w-md mx-auto mb-6">
                        Your capital commitment of <strong class="text-emerald-700"><?= format_inr($_POST['amount']) ?></strong> into <strong class="text-slate-800"><?= htmlspecialchars($round['company_name']) ?></strong> is recorded in escrow.
                    </p>

                    <div class="max-w-md mx-auto p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs text-left space-y-2 mb-6">
                        <div class="flex justify-between py-1 border-b border-slate-200/60">
                            <span class="text-slate-500">Transaction Reference:</span>
                            <span class="font-mono font-bold text-slate-900"><?= htmlspecialchars($transactionRef) ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-200/60">
                            <span class="text-slate-500">Target Company:</span>
                            <span class="font-bold text-slate-900"><?= htmlspecialchars($round['company_name']) ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-200/60">
                            <span class="text-slate-500">Allotted Equity Estimate:</span>
                            <span class="font-bold text-emerald-700"><?= $equityAllotted ?>%</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-200/60">
                            <span class="text-slate-500">Escrow Status:</span>
                            <span class="text-emerald-700 font-semibold flex items-center space-x-1">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                <span>Secured in Escrow</span>
                            </span>
                        </div>
                        <div class="flex justify-between py-1 pt-1.5">
                            <span class="text-slate-500 flex items-center space-x-1">
                                <i data-lucide="mail-check" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Email Confirmations:</span>
                            </span>
                            <span class="text-indigo-700 font-bold flex items-center space-x-1">
                                <span>Sent to You & Founders</span>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="<?= url('investor/portfolio.php') ?>" class="w-full sm:w-auto px-4 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-sm transition">
                            View in Portfolio →
                        </a>
                        <a href="<?= url('investor/discover.php') ?>" class="w-full sm:w-auto px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                            Explore More Deals
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <!-- INVESTMENT ENTRY FORM -->
                <div class="card-clean rounded-2xl p-6">
                    
                    <div class="flex items-center space-x-3.5 mb-5 pb-5 border-b border-slate-100">
                        <img src="<?= $round['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=120' ?>" class="w-12 h-12 rounded-xl object-cover border border-slate-200">
                        <div>
                            <h1 class="text-base font-bold text-slate-900"><?= htmlspecialchars($round['company_name']) ?></h1>
                            <div class="text-[11px] text-slate-500"><?= htmlspecialchars($round['round_name']) ?> • Pre-money Valuation: <strong class="text-slate-800"><?= format_inr($round['valuation']) ?></strong></div>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="mb-5 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                            <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('investor/invest.php?round=' . $roundHash) ?>" method="POST" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Min. Ticket</span>
                                <span class="font-bold text-slate-900 text-xs"><?= format_inr($round['min_investment']) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Target</span>
                                <span class="font-bold text-slate-900 text-xs"><?= format_inr($round['target_amount']) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Raised</span>
                                <span class="font-bold text-emerald-600 text-xs"><?= format_inr($round['amount_raised']) ?></span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Remaining</span>
                                <span class="font-bold text-indigo-600 text-xs"><?= format_inr(max(0, $round['target_amount'] - $round['amount_raised'])) ?></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                Investment Commitment Amount (₹) *
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">₹</span>
                                <input type="number" id="invest-amount" name="amount" required 
                                       min="<?= $round['min_investment'] ?>" 
                                       max="<?= max(0, $round['target_amount'] - $round['amount_raised']) ?>" 
                                       step="25000" 
                                       value="<?= $round['min_investment'] ?>"
                                       oninput="calcEstimate()"
                                       class="w-full pl-8 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-sm font-bold text-slate-900 outline-none transition">
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1.5 flex items-center justify-between">
                                <span>Estimated Equity Allocation: <strong id="equity-est" class="text-indigo-600 font-bold">0.00%</strong></span>
                                <span class="text-slate-400">Based on <?= format_inr($round['valuation']) ?> valuation</span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <label class="flex items-start space-x-2.5 cursor-pointer">
                                <input type="checkbox" name="terms_accepted" required class="mt-0.5 w-3.5 h-3.5 rounded text-indigo-600 border-slate-300">
                                <span class="text-[11px] text-slate-500 leading-relaxed">
                                    I confirm that I am an accredited investor, have completed my KYC, and understand startup investments carry substantial illiquidity and capital risk. Funds will be deposited into the regulatory escrow account pending final instrument allotment.
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <span>Confirm & Authorize Escrow Commitment</span>
                        </button>
                    </form>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#invest-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });

        const valuation = <?= (float)$round['valuation'] ?>;
        function calcEstimate() {
            const input = document.getElementById('invest-amount');
            const amt = parseFloat(input ? input.value : 0) || 0;
            if (valuation > 0) {
                const eq = ((amt / valuation) * 100).toFixed(3);
                document.getElementById('equity-est').innerText = eq + '%';
            }
        }
        calcEstimate();
    </script>
</body>
</html>
