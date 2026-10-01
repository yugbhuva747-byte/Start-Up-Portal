<?php
/**
 * Founder Module: Funding Rounds & Capital State Machine Studio
 * Visual Thermometers, Valuation Calculators, Ticket Limits & Lifecycle Progress
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Funding Round Management';

$company = null;
$rounds = [];
$error = '';
$flash = get_flash();

if ($db) {
    // Get Company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    if ($company) {
        $rStmt = $db->prepare("SELECT * FROM funding_rounds WHERE company_id = ? ORDER BY created_at DESC");
        $rStmt->execute([$company['id']]);
        $rounds = $rStmt->fetchAll();
    }
}

// Calculate summary totals across rounds
$totalCapitalTargeted = 0;
$totalCapitalRaisedAcrossAll = 0;
foreach ($rounds as $r) {
    $totalCapitalTargeted += (float)$r['target_amount'];
    $totalCapitalRaisedAcrossAll += (float)$r['amount_raised'];
}

// Handle Action POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'create_round' && $company) {
            $roundName = trim($_POST['round_name'] ?? 'Seed Round');
            $targetAmount = (float)($_POST['target_amount'] ?? 5000000);
            $minInvestment = (float)($_POST['min_investment'] ?? 100000);
            $maxInvestment = !empty($_POST['max_investment']) ? (float)$_POST['max_investment'] : null;
            $valuation = (float)($_POST['valuation'] ?? 30000000);
            $equityOffered = (float)($_POST['equity_offered'] ?? 10.0);
            $purpose = trim($_POST['purpose'] ?? '');
            $status = 'UNDER_REVIEW'; // submitted for review immediately

            $ins = $db->prepare("
                INSERT INTO funding_rounds (company_id, round_name, target_amount, min_investment, max_investment, amount_raised, valuation, equity_offered, status, purpose, created_at)
                VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([$company['id'], $roundName, $targetAmount, $minInvestment, $maxInvestment, $valuation, $equityOffered, $status, $purpose]);
            $roundId = $db->lastInsertId();

            log_audit($user['id'], 'CREATE_FUNDING_ROUND', 'funding_rounds', $roundId, "Created {$roundName} with target ₹{$targetAmount}");
            set_flash('success', "Funding round submitted for compliance review!");
            header('Location: ' . url('founder/funding_rounds.php'));
            exit;

        } elseif ($action === 'close_round') {
            $roundId = (int)($_POST['round_id'] ?? 0);
            $upd = $db->prepare("UPDATE funding_rounds SET status = 'CLOSED' WHERE id = ? AND company_id = ?");
            $upd->execute([$roundId, $company['id']]);

            log_audit($user['id'], 'CLOSE_FUNDING_ROUND', 'funding_rounds', $roundId, "Founder manually closed round");
            set_flash('info', 'Funding round marked as CLOSED.');
            header('Location: ' . url('founder/funding_rounds.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?? APP_NAME ?> • <?= APP_NAME ?></title>
    <?php include __DIR__ . '/../includes/founder/head.php'; ?>
    <style>
        html:not(.dark) .hero-capital-banner {
            background: radial-gradient(130% 100% at 0% 0%, #EEF2FF 0%, #F8FAFC 50%, #ECFDF5 100%);
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
        }
        .hero-capital-banner {
            border-radius: 1.5rem;
            position: relative;
        }
        html.dark .hero-capital-banner {
            background: radial-gradient(130% 100% at 0% 0%, #17213A 0%, #0F172A 55%, #111827 100%) !important;
            border: 1px solid #1E293B !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
        }
        .form-input-clean {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 0.75rem;
            color: #0F172A;
            font-size: 0.875rem;
            line-height: 1.4rem;
            transition: all 0.15s ease;
            outline: none;
        }
        .form-input-clean:hover {
            background-color: #F1F5F9;
            border-color: #CBD5E1;
        }
        .form-input-clean:focus {
            background-color: #FFFFFF;
            border-color: #4F46E5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
    </style>
</head>
<body class="bg-[#F4F2EE] dark:bg-[#0B0F19] text-slate-900 dark:text-slate-100 flex min-h-screen antialiased selection:bg-indigo-500 selection:text-white">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
        <!-- Sticky Top Fixed Founder Navbar -->
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <!-- Full-screen Dynamic Main Container -->
        <main class="w-full px-4 sm:px-6 md:px-8 py-6 space-y-6" id="rounds-main">
            
            <!-- Flash Feedback -->
            <?php if ($flash): ?>
                <div class="p-4 rounded-2xl text-sm font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-3">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 flex-shrink-0 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                    <span class="text-xs font-bold uppercase opacity-75">Notice</span>
                </div>
            <?php endif; ?>

            <!-- Capital Command Hero Banner -->
            <div class="hero-capital-banner p-6 sm:p-8 relative overflow-hidden">
                <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-32 -bottom-16 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white shadow-sm shadow-indigo-500/25">
                                <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5"></i>
                                <span>Capital Round Engine</span>
                            </span>
                            <?php if ($company): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/80 border border-slate-200/80 text-slate-700 backdrop-blur-sm">
                                    <i data-lucide="building" class="w-3.5 h-3.5 text-indigo-600"></i>
                                    <span><?= htmlspecialchars($company['name']) ?></span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-[11px] font-mono text-slate-500">CIN: <?= htmlspecialchars($company['cin_number'] ?: 'Verified') ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight">
                            Funding Rounds & Capital Architecture
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-600 max-w-2xl leading-relaxed">
                            Configure capital targets, equity dilution, min/max investor tickets, pre-money valuations, and track investment commitments across live and past rounds.
                        </p>
                    </div>

                    <!-- Action Button -->
                    <div>
                        <button onclick="document.getElementById('new-round-modal').classList.remove('hidden')" 
                                class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center space-x-2">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Create New Funding Round</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4 Capital Overview Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="section-card p-5">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Capital Raised</div>
                    <div class="text-2xl font-black text-emerald-600 mt-1"><?= format_inr($totalCapitalRaisedAcrossAll) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Across all confirmed allotments</div>
                </div>

                <div class="section-card p-5">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Target Capital Across Rounds</div>
                    <div class="text-2xl font-black text-slate-900 mt-1"><?= format_inr($totalCapitalTargeted) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Total aggregated target size</div>
                </div>

                <div class="section-card p-5">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Rounds</div>
                    <div class="text-2xl font-black text-indigo-600 mt-1"><?= count($rounds) ?></div>
                    <div class="text-xs text-indigo-600 font-semibold mt-1">Instrument: Equity & SAFE Notes</div>
                </div>

                <div class="section-card p-5">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Capital Fulfillment</div>
                    <?php 
                    $overallPct = $totalCapitalTargeted > 0 ? round(($totalCapitalRaisedAcrossAll / $totalCapitalTargeted) * 100) : 0;
                    ?>
                    <div class="text-2xl font-black text-slate-900 mt-1"><?= $overallPct ?>%</div>
                    <div class="text-xs text-emerald-600 font-semibold mt-1">Portfolio subscription rate</div>
                </div>
            </div>

            <!-- Rounds List (Not Boring!) -->
            <div class="space-y-4">
                <?php if (empty($rounds)): ?>
                    <div class="section-card p-12 text-center text-slate-400 text-xs">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3.5 border border-indigo-100">
                            <i data-lucide="circle-dollar-sign" class="w-8 h-8"></i>
                        </div>
                        <div class="text-base font-extrabold text-slate-800 mb-1">No Active Funding Rounds</div>
                        <div class="text-xs text-slate-500 max-w-sm mx-auto mb-4">
                            Initialize a funding round to set target capital, valuation, and receive angel investor commitments.
                        </div>
                        <button onclick="document.getElementById('new-round-modal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold inline-flex items-center space-x-1.5 shadow-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Launch First Round</span>
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($rounds as $r): 
                        $pct = $r['target_amount'] > 0 ? round(($r['amount_raised'] / $r['target_amount']) * 100) : 0;
                        $remaining = max(0, $r['target_amount'] - $r['amount_raised']);
                        $postMoney = (float)$r['valuation'] + (float)$r['target_amount'];
                    ?>
                        <div class="section-card p-6 sm:p-7 relative overflow-hidden space-y-5">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($r['round_name']) ?></h2>
                                        <?= render_status_badge($r['status']) ?>
                                        <span class="text-xs text-slate-400 font-mono">ID: #<?= $r['id'] ?></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Pre-money: <strong class="text-slate-800"><?= format_inr($r['valuation']) ?></strong> • 
                                        Post-money: <strong class="text-slate-800"><?= format_inr($postMoney) ?></strong> • 
                                        Equity Offered: <strong class="text-indigo-600"><?= $r['equity_offered'] ?>%</strong>
                                    </p>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <?php if (in_array($r['status'], ['LIVE', 'PARTIALLY_FUNDED'])): ?>
                                        <form action="<?= url('founder/funding_rounds.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to close this funding round?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="close_round">
                                            <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition flex items-center space-x-1.5">
                                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                                <span>Close Round</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Metrics Strip with High Contrast -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-4 rounded-xl bg-slate-50/80 border border-slate-100 text-xs">
                                <div>
                                    <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Target Capital</div>
                                    <div class="font-extrabold text-slate-900 text-sm mt-0.5"><?= format_inr($r['target_amount']) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Committed & Raised</div>
                                    <div class="font-extrabold text-emerald-600 text-sm mt-0.5"><?= format_inr($r['amount_raised']) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Remaining Buffer</div>
                                    <div class="font-extrabold text-indigo-600 text-sm mt-0.5"><?= format_inr($remaining) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400">Allowed Ticket Range</div>
                                    <div class="font-extrabold text-slate-800 text-sm mt-0.5 font-mono">
                                        <?= format_inr($r['min_investment']) ?> – <?= $r['max_investment'] ? format_inr($r['max_investment']) : 'Unlimited' ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Thermometer Progress Bar -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span class="text-slate-500">Capital Subscription Progress</span>
                                    <span class="text-emerald-700 font-extrabold"><?= $pct ?>% Complete</span>
                                </div>
                                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200 p-0.5">
                                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-700" style="width: <?= min(100, $pct) ?>%"></div>
                                </div>
                            </div>

                            <!-- Purpose / Use of Capital -->
                            <div class="text-xs text-slate-700 bg-slate-50/60 p-4 rounded-xl border border-slate-100">
                                <strong class="text-slate-800 block text-[11px] uppercase tracking-wider mb-1 font-bold">Planned Deployment & Milestones:</strong>
                                <p class="leading-relaxed"><?= nl2br(htmlspecialchars($r['purpose'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Create Round Modal -->
            <div id="new-round-modal" class="<?= isset($_GET['action']) && $_GET['action'] === 'new' ? '' : 'hidden' ?> fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white border border-slate-200 shadow-2xl max-w-xl w-full rounded-2xl p-6 sm:p-7 relative space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Configure New Funding Round</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Will be verified by Compliance Admin before syndicated angels can invest.</p>
                        </div>
                        <button onclick="document.getElementById('new-round-modal').classList.add('hidden')" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form action="<?= url('founder/funding_rounds.php') ?>" method="POST" class="space-y-4 text-xs">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="create_round">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Round Classification</label>
                                <select name="round_name" class="form-input-clean text-xs font-semibold cursor-pointer">
                                    <option value="Pre-Seed Round">Pre-Seed Round</option>
                                    <option value="Seed Round" selected>Seed Round</option>
                                    <option value="Bridge Round">Bridge / SAFE Note</option>
                                    <option value="Pre-Series A">Pre-Series A</option>
                                    <option value="Series A">Series A</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Target Capital (₹) *</label>
                                <input type="number" name="target_amount" id="modal-target" required value="5000000" step="100000" oninput="calcPostMoney()"
                                       class="form-input-clean font-semibold text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Pre-Money Valuation (₹)</label>
                                <input type="number" name="valuation" id="modal-pre" required value="40000000" step="500000" oninput="calcPostMoney()"
                                       class="form-input-clean font-semibold text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Equity Diluted (%)</label>
                                <input type="number" name="equity_offered" required value="10.0" step="0.1" min="0.1" max="100"
                                       class="form-input-clean font-semibold text-xs">
                            </div>
                        </div>

                        <!-- Dynamic Post-Money Calculator Callout -->
                        <div class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-xl flex items-center justify-between text-xs">
                            <span class="text-indigo-900 font-medium">Computed Post-Money Valuation:</span>
                            <span id="modal-post-money" class="font-extrabold text-indigo-700 font-mono">₹4.50 Cr</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Minimum Check (₹)</label>
                                <input type="number" name="min_investment" required value="100000" step="25000"
                                       class="form-input-clean font-semibold text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Maximum Check (Optional)</label>
                                <input type="number" name="max_investment" placeholder="e.g. 2500000" step="50000"
                                       class="form-input-clean font-semibold text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1 uppercase tracking-wider">Capital Deployment & Milestones</label>
                            <textarea name="purpose" rows="3" required placeholder="Explain specific hiring, tech infrastructure, or growth targets this round funds..."
                                      class="form-input-clean text-xs leading-relaxed"></textarea>
                        </div>

                        <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-md shadow-indigo-600/20 text-xs">
                            Submit Funding Round for Regulatory Review
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#rounds-main", { duration: 0.35, y: 8, opacity: 0, ease: "power2.out" });

        function calcPostMoney() {
            const target = parseFloat(document.getElementById('modal-target').value) || 0;
            const pre = parseFloat(document.getElementById('modal-pre').value) || 0;
            const post = pre + target;
            const cr = (post / 10000000).toFixed(2);
            document.getElementById('modal-post-money').innerText = `₹${cr} Cr (₹${post.toLocaleString('en-IN')})`;
        }
        calcPostMoney();
    </script>
</body>
</html>
