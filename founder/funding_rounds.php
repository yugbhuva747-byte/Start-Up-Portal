<?php
/**
 * Founder Module: Funding Rounds & State Machine
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funding Rounds • <?= APP_NAME ?></title>
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
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="rounds-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Funding Round Management</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Configure target capital, valuation, ticket sizes, and track round progress state machine.</p>
                </div>
                <button onclick="document.getElementById('new-round-modal').classList.remove('hidden')" class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Create New Round</span>
                </button>
            </div>

            <!-- Rounds List -->
            <div class="space-y-4">
                <?php if (empty($rounds)): ?>
                    <div class="card-clean rounded-2xl p-10 text-center text-slate-400 text-xs">
                        <i data-lucide="circle-dollar-sign" class="w-10 h-10 text-slate-300 mx-auto mb-2.5"></i>
                        <div class="text-xs font-bold text-slate-800 mb-1">No funding rounds initiated</div>
                        <div class="text-[11px] text-slate-500">Create a funding round to begin receiving angel and syndicate capital commitments.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($rounds as $r): 
                        $pct = $r['target_amount'] > 0 ? round(($r['amount_raised'] / $r['target_amount']) * 100) : 0;
                        $remaining = max(0, $r['target_amount'] - $r['amount_raised']);
                    ?>
                        <div class="card-clean rounded-2xl p-6 relative">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-5">
                                <div>
                                    <div class="flex items-center space-x-2.5">
                                        <h2 class="text-base font-bold text-slate-900"><?= htmlspecialchars($r['round_name']) ?></h2>
                                        <?= render_status_badge($r['status']) ?>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Pre-money Valuation: <strong class="text-slate-800"><?= format_inr($r['valuation']) ?></strong> • Equity: <strong class="text-slate-800"><?= $r['equity_offered'] ?>%</strong>
                                    </p>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <?php if (in_array($r['status'], ['LIVE', 'PARTIALLY_FUNDED'])): ?>
                                        <form action="<?= url('founder/funding_rounds.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to close this funding round?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="form_action" value="close_round">
                                            <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold transition">
                                                Close Round
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Metrics Strip -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5 p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Target Raise</div>
                                    <div class="font-bold text-slate-900 text-xs mt-0.5"><?= format_inr($r['target_amount']) ?></div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Amount Raised</div>
                                    <div class="font-bold text-emerald-600 text-xs mt-0.5"><?= format_inr($r['amount_raised']) ?></div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Remaining Gap</div>
                                    <div class="font-bold text-indigo-600 text-xs mt-0.5"><?= format_inr($remaining) ?></div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Ticket Limits</div>
                                    <div class="font-bold text-slate-800 text-xs mt-0.5"><?= format_inr($r['min_investment']) ?> – <?= $r['max_investment'] ? format_inr($r['max_investment']) : 'No cap' ?></div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-1.5 mb-4">
                                <div class="flex justify-between text-[11px] font-semibold">
                                    <span class="text-slate-500">Round Completion</span>
                                    <span class="text-emerald-700 font-bold"><?= $pct ?>%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-700" style="width: <?= min(100, $pct) ?>%"></div>
                                </div>
                            </div>

                            <!-- Purpose / Use of Funds -->
                            <div class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100">
                                <strong class="text-slate-800 block text-[11px] uppercase tracking-wider mb-1 font-bold">Use of Capital:</strong>
                                <?= nl2br(htmlspecialchars($r['purpose'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Create Round Modal -->
            <div id="new-round-modal" class="<?= isset($_GET['action']) && $_GET['action'] === 'new' ? '' : 'hidden' ?> fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white border border-slate-200 shadow-xl max-w-xl w-full rounded-2xl p-6 relative">
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Launch New Funding Round</h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">Round will be reviewed by Compliance Admin before opening to investors.</p>
                        </div>
                        <button onclick="document.getElementById('new-round-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <form action="<?= url('founder/funding_rounds.php') ?>" method="POST" class="space-y-4 text-xs">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="form_action" value="create_round">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Round Name</label>
                                <select name="round_name" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                                    <option value="Pre-Seed Round">Pre-Seed Round</option>
                                    <option value="Seed Round" selected>Seed Round</option>
                                    <option value="Bridge Round">Bridge / SAFE Note</option>
                                    <option value="Pre-Series A">Pre-Series A</option>
                                    <option value="Series A">Series A</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Target Amount (₹) *</label>
                                <input type="number" name="target_amount" required value="5000000" step="100000"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Pre-Money Valuation (₹)</label>
                                <input type="number" name="valuation" required value="40000000" step="500000"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Equity Offered (%)</label>
                                <input type="number" name="equity_offered" required value="10.0" step="0.1" min="0.1" max="100"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Min. Ticket Size (₹)</label>
                                <input type="number" name="min_investment" required value="100000" step="25000"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Max. Ticket (Optional)</label>
                                <input type="number" name="max_investment" placeholder="e.g. 2500000" step="50000"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Use of Funds & Milestones</label>
                            <textarea name="purpose" rows="3" required placeholder="Explain specific hiring, tech infrastructure, or growth targets this round funds..."
                                      class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm text-xs">
                            Submit Funding Round for Review
                        </button>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#rounds-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
