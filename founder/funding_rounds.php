<?php
/**
 * Founder Module: Funding Rounds & Real-Form Capital Architecture
 * Streamlined Essential Form (Core Financials & Valuation Only - No Ticket Clutter)
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

// Calculate Summary Metrics
$totalRounds = count($rounds);
$totalTarget = 0.0;
$totalRaised = 0.0;
$activeRoundsCount = 0;
foreach ($rounds as $r) {
    $totalTarget += (float)$r['target_amount'];
    $totalRaised += (float)$r['amount_raised'];
    if (in_array($r['status'], ['LIVE', 'PARTIALLY_FUNDED'])) {
        $activeRoundsCount++;
    }
}

// Handle Action POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'create_round' && $company) {
            $roundName = trim($_POST['round_name'] ?? 'Seed Round');
            $targetAmount = (float)($_POST['target_amount'] ?? 5000000);
            $valuation = (float)($_POST['valuation'] ?? 35000000);
            $equityOffered = (float)($_POST['equity_offered'] ?? 10.0);
            // Streamlined: Sensible default for min_investment, no ticket size friction for founder
            $minInvestment = !empty($_POST['min_investment']) ? (float)$_POST['min_investment'] : max(25000, round($targetAmount * 0.02));
            $maxInvestment = null;
            $startDate = null;
            $endDate = null;
            $purpose = trim($_POST['purpose'] ?? 'General growth, product development, and operations');
            $status = 'UNDER_REVIEW'; // submitted for review immediately

            if ($targetAmount <= 0) {
                $error = 'Target amount must be greater than zero.';
            } elseif ($valuation <= 0) {
                $error = 'Pre-money valuation must be greater than zero.';
            } elseif ($equityOffered <= 0 || $equityOffered > 100) {
                $error = 'Equity offered must be between 0.1% and 100%.';
            } else {
                $ins = $db->prepare("
                    INSERT INTO funding_rounds (company_id, round_name, target_amount, min_investment, max_investment, amount_raised, valuation, equity_offered, status, start_date, end_date, purpose, created_at)
                    VALUES (?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $ins->execute([$company['id'], $roundName, $targetAmount, $minInvestment, $maxInvestment, $valuation, $equityOffered, $status, $startDate, $endDate, $purpose]);
                $roundId = (int)$db->lastInsertId();

                // Automatically send confirmation email to logged-in Founder & alert Admin
                send_funding_round_submitted_emails($db, $roundId, (int)$user['id']);

                log_audit($user['id'], 'CREATE_FUNDING_ROUND', 'funding_rounds', $roundId, "Created {$roundName} with target ₹{$targetAmount}");
                set_flash('success', "Funding round submitted for compliance approval. Confirmation email dispatched to {$user['email']}.");
                header('Location: ' . url('founder/funding_rounds.php'));
                exit;
            }

        } elseif ($action === 'close_round' && $company) {
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

$isFormMode = (isset($_GET['action']) && $_GET['action'] === 'new') || !empty($error);
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
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .clean-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04);
        }
        .form-input-focus:focus-within {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background-color: #ffffff;
        }
        .preset-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .preset-card.active {
            border-color: #4f46e5;
            background-color: #f5f7ff;
            box-shadow: 0 0 0 1.5px #4f46e5;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-900 flex min-h-screen">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-4 sm:p-6 md:p-8 space-y-6 max-w-6xl w-full mx-auto" id="rounds-main-container">
            
            <?php if ($flash): ?>
                <div class="p-4 rounded-xl text-sm font-bold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' ?> flex items-center justify-between shadow-xs">
                    <div class="flex items-center space-x-2.5">
                        <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle-2' : 'alert-circle' ?>" class="w-5 h-5 <?= $flash['type'] === 'success' ? 'text-emerald-600' : 'text-rose-600' ?> flex-shrink-0"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 rounded-xl text-sm font-bold bg-rose-50 text-rose-800 border border-rose-200 flex items-center space-x-2.5 shadow-xs">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- ========================================== -->
            <!-- VIEW 1: FUNDING ROUNDS DASHBOARD / LIST   -->
            <!-- ========================================== -->
            <div id="rounds-list-view" class="<?= $isFormMode ? 'hidden' : '' ?> space-y-6">
                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Funding Round Management</h1>
                        <p class="text-xs sm:text-sm text-slate-600 mt-0.5 font-medium">Structure capital raises, set pre-money valuations, and track angel commitments.</p>
                    </div>
                    <?php if ($company): ?>
                        <button type="button" onclick="showFormView()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm transition flex items-center justify-center space-x-2 flex-shrink-0">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Launch New Round</span>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Top Portfolio Stats Grid -->
                <?php if (!empty($rounds)): ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 sm:gap-4">
                        <div class="clean-card rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Rounds</span>
                                <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2"><?= $totalRounds ?></div>
                            <div class="text-xs text-slate-500 mt-0.5 font-medium"><?= $activeRoundsCount ?> Active syndications</div>
                        </div>

                        <div class="clean-card rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Target</span>
                                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i data-lucide="target" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2"><?= format_inr($totalTarget) ?></div>
                            <div class="text-xs text-slate-500 mt-0.5 font-medium">Aggregate capital goal</div>
                        </div>

                        <div class="clean-card rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Secured Raised</span>
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-2"><?= format_inr($totalRaised) ?></div>
                            <div class="text-xs text-slate-500 mt-0.5 font-medium"><?= $totalTarget > 0 ? round(($totalRaised / $totalTarget) * 100) : 0 ?>% portfolio closed</div>
                        </div>

                        <div class="clean-card rounded-2xl p-4 sm:p-5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Company Stage</span>
                                <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                                </div>
                            </div>
                            <div class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2"><?= htmlspecialchars($company['stage'] ?? 'Seed') ?></div>
                            <div class="text-xs text-slate-500 mt-0.5 font-medium"><?= htmlspecialchars($company['industry'] ?? 'AI/SaaS') ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Rounds List -->
                <div class="space-y-5">
                    <?php if (empty($rounds)): ?>
                        <div class="clean-card rounded-2xl p-12 text-center">
                            <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="trending-up" class="w-8 h-8"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">No funding rounds initiated yet</h3>
                            <p class="text-sm text-slate-500 max-w-md mx-auto mb-6 leading-relaxed font-medium">Launch a Pre-Seed or Seed round to syndicate capital from accredited angels and venture networks without intermediaries.</p>
                            <?php if ($company): ?>
                                <button type="button" onclick="showFormView()" class="px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition shadow-xs inline-flex items-center space-x-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                    <span>Create Your First Round</span>
                                </button>
                            <?php else: ?>
                                <a href="<?= url('founder/company.php') ?>" class="inline-flex px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition shadow-xs">
                                    Complete Startup Profile First
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rounds as $r): 
                            $pct = $r['target_amount'] > 0 ? round(($r['amount_raised'] / $r['target_amount']) * 100) : 0;
                            $remaining = max(0, $r['target_amount'] - $r['amount_raised']);
                        ?>
                            <div class="clean-card rounded-2xl p-5 sm:p-6 relative hover:shadow-md transition">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h2 class="text-base sm:text-lg font-bold text-slate-900"><?= htmlspecialchars($r['round_name']) ?></h2>
                                            <?= render_status_badge($r['status']) ?>
                                        </div>
                                        <p class="text-xs sm:text-sm text-slate-600 mt-1 flex flex-wrap items-center gap-2.5 font-medium">
                                            <span>Pre-Money: <strong class="text-slate-900 font-bold"><?= format_inr($r['valuation']) ?></strong></span>
                                            <span>•</span>
                                            <span>Equity Pool: <strong class="text-indigo-600 font-bold"><?= $r['equity_offered'] ?>%</strong></span>
                                            <span>•</span>
                                            <span>Created: <?= date('d M Y', strtotime($r['created_at'])) ?></span>
                                        </p>
                                    </div>

                                    <div class="flex items-center space-x-2.5">
                                        <?php if (in_array($r['status'], ['LIVE', 'PARTIALLY_FUNDED'])): ?>
                                            <form action="<?= url('founder/funding_rounds.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to close this funding round?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="form_action" value="close_round">
                                                <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs sm:text-sm font-bold transition">
                                                    Close Round
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Metrics Strip -->
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3.5 mb-4 p-3.5 sm:p-4 rounded-xl bg-slate-50/90 border border-slate-200/80">
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Target Raise</div>
                                        <div class="font-extrabold text-slate-900 text-sm sm:text-base mt-0.5"><?= format_inr($r['target_amount']) ?></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Committed Capital</div>
                                        <div class="font-extrabold text-emerald-600 text-sm sm:text-base mt-0.5"><?= format_inr($r['amount_raised']) ?></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Remaining Gap</div>
                                        <div class="font-extrabold text-indigo-600 text-sm sm:text-base mt-0.5"><?= format_inr($remaining) ?></div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-xs sm:text-sm font-bold">
                                        <span class="text-slate-600">Subscription Progress</span>
                                        <span class="text-emerald-700 font-extrabold"><?= $pct ?>% Filled</span>
                                    </div>
                                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-700" style="width: <?= min(100, $pct) ?>%"></div>
                                    </div>
                                </div>

                                <!-- Purpose / Use of Funds -->
                                <?php if (!empty($r['purpose'])): ?>
                                    <div class="text-xs sm:text-sm text-slate-600 bg-slate-50/60 p-4 rounded-xl border border-slate-100">
                                        <span class="text-slate-900 block text-xs uppercase tracking-wider mb-1 font-bold">Use of Capital:</span>
                                        <?= nl2br(htmlspecialchars($r['purpose'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>


            <!-- ============================================================== -->
            <!-- VIEW 2: STREAMLINED REAL FORM (ESSENTIAL FINANCIAL DETAILS)    -->
            <!-- ============================================================== -->
            <div id="rounds-form-view" class="<?= $isFormMode ? '' : 'hidden' ?> space-y-6">
                
                <!-- Sleek Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                    <div>
                        <a href="javascript:void(0)" onclick="showListView()" class="inline-flex items-center space-x-1.5 text-xs sm:text-sm font-bold text-slate-500 hover:text-indigo-600 transition mb-1.5">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Back to Funding Rounds</span>
                        </a>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Launch New Funding Round</h1>
                        <p class="text-xs sm:text-sm text-slate-600 mt-1 font-medium">Enter your capital goal, valuation, and equity allocation.</p>
                    </div>

                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <button type="button" onclick="showListView()" class="px-5 py-2.5 rounded-xl text-slate-700 hover:text-slate-900 text-sm font-bold border border-slate-200 bg-white hover:bg-slate-50 transition shadow-2xs">
                            Cancel
                        </button>
                        <button type="button" onclick="document.getElementById('real-round-form').requestSubmit()" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-xs transition flex items-center space-x-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Submit for Review</span>
                        </button>
                    </div>
                </div>

                <!-- Stage Selection Presets (Clean, No Ticket Clutter) -->
                <div class="clean-card rounded-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900">Stage Benchmarks</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 font-medium">Click a stage to auto-fill market standard terms, or enter custom terms below.</p>
                        </div>
                        <span class="text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 flex items-center space-x-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Auto-Sync Active</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <button type="button" onclick="applyPreset('Pre-Seed Round', 2500000, 20000000, 12.5, this)" class="preset-card text-left p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 transition relative group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-900 text-sm group-hover:text-indigo-600">Pre-Seed</span>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Angel</span>
                            </div>
                            <div class="text-lg font-black text-slate-900">₹25 Lakhs</div>
                            <div class="text-xs sm:text-sm text-slate-600 font-medium mt-1">Pre-Val: ₹2 Cr • 12.5%</div>
                        </button>

                        <button type="button" onclick="applyPreset('Seed Round', 5000000, 40000000, 11.1, this)" class="preset-card active text-left p-5 rounded-xl border border-indigo-600 bg-indigo-50/60 shadow-xs transition relative group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-indigo-950 text-sm">Seed Round</span>
                                <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded">Popular</span>
                            </div>
                            <div class="text-lg font-black text-indigo-950">₹50 Lakhs</div>
                            <div class="text-xs sm:text-sm text-indigo-900 font-semibold mt-1">Pre-Val: ₹4 Cr • 11.1%</div>
                        </button>

                        <button type="button" onclick="applyPreset('Pre-Series A', 15000000, 100000000, 13.0, this)" class="preset-card text-left p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 transition relative group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-900 text-sm group-hover:text-indigo-600">Pre-Series A</span>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Growth</span>
                            </div>
                            <div class="text-lg font-black text-slate-900">₹1.50 Crore</div>
                            <div class="text-xs sm:text-sm text-slate-600 font-medium mt-1">Pre-Val: ₹10 Cr • 13%</div>
                        </button>

                        <button type="button" onclick="applyPreset('Series A', 40000000, 250000000, 13.7, this)" class="preset-card text-left p-5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50 transition relative group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-bold text-slate-900 text-sm group-hover:text-indigo-600">Series A</span>
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-600 bg-slate-100 px-2 py-0.5 rounded">Scale</span>
                            </div>
                            <div class="text-lg font-black text-slate-900">₹4.00 Crores</div>
                            <div class="text-xs sm:text-sm text-slate-600 font-medium mt-1">Pre-Val: ₹25 Cr • 13.7%</div>
                        </button>
                    </div>
                </div>

                <!-- Streamlined Form Grid (2 Columns: Left Core Inputs, Right Simulator) -->
                <form id="real-round-form" action="<?= url('founder/funding_rounds.php') ?>" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="create_round">

                    <!-- Left Form Cards (8 Columns) -->
                    <div class="lg:col-span-8 space-y-6">
                        
                        <!-- Core Financial Terms Card -->
                        <div class="clean-card rounded-2xl p-6 sm:p-7 space-y-6">
                            <div class="flex items-center space-x-3 pb-3.5 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                    <i data-lucide="layers" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Round Financial Parameters</h3>
                                    <p class="text-xs sm:text-sm text-slate-500 font-medium">Specify your round name, target capital, and valuation terms.</p>
                                </div>
                            </div>

                            <!-- Row 1: Round Name & Target Raise -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-bold text-slate-800 mb-1.5">Round Name / Series *</label>
                                    <select name="round_name" id="form-round-name" class="w-full px-3.5 py-3 bg-slate-50 border border-slate-200 focus:border-indigo-600 focus:bg-white rounded-xl text-sm text-slate-900 font-semibold outline-none transition cursor-pointer">
                                        <option value="Pre-Seed Round">Pre-Seed Round</option>
                                        <option value="Seed Round" selected>Seed Round</option>
                                        <option value="Bridge / SAFE Note">Bridge / SAFE Note</option>
                                        <option value="Pre-Series A">Pre-Series A</option>
                                        <option value="Series A">Series A</option>
                                        <option value="Series B">Series B</option>
                                    </select>
                                    <p class="text-xs text-slate-500 mt-1 font-medium">Displayed on the investor discovery board.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-800 mb-1.5">Target Raise Amount (₹ INR) *</label>
                                    <div class="flex items-center border border-slate-200 form-input-focus rounded-xl bg-slate-50 transition overflow-hidden">
                                        <span class="px-3.5 py-3 bg-slate-100 text-slate-700 font-bold border-r border-slate-200 text-sm select-none">₹</span>
                                        <input type="number" id="input-target-amt" name="target_amount" required value="5000000" step="50000" min="100000"
                                               class="w-full px-3 py-3 bg-transparent text-sm sm:text-base text-slate-900 outline-none font-bold" oninput="recalcRound()">
                                    </div>
                                    <div class="mt-2 flex items-center justify-between gap-2">
                                        <span id="target-amt-words" class="text-xs font-bold text-indigo-700 truncate">₹50,00,000 (Fifty Lakhs)</span>
                                        <div class="flex items-center space-x-1.5 flex-shrink-0">
                                            <button type="button" onclick="setTargetChip(2500000)" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-800 text-xs font-bold transition">25L</button>
                                            <button type="button" onclick="setTargetChip(5000000)" class="px-2.5 py-1 rounded bg-indigo-100 text-indigo-800 text-xs font-bold transition">50L</button>
                                            <button type="button" onclick="setTargetChip(10000000)" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-800 text-xs font-bold transition">1Cr</button>
                                            <button type="button" onclick="setTargetChip(25000000)" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-800 text-xs font-bold transition">2.5Cr</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Valuation & Equity Pool -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-1">
                                <div>
                                    <label class="block text-sm font-bold text-slate-800 mb-1.5">Pre-Money Company Valuation (₹) *</label>
                                    <div class="flex items-center border border-slate-200 form-input-focus rounded-xl bg-slate-50 transition overflow-hidden">
                                        <span class="px-3.5 py-3 bg-slate-100 text-slate-700 font-bold border-r border-slate-200 text-sm select-none">₹</span>
                                        <input type="number" id="input-valuation" name="valuation" required value="40000000" step="500000" min="1000000"
                                               class="w-full px-3 py-3 bg-transparent text-sm sm:text-base text-slate-900 outline-none font-bold" oninput="recalcRound()">
                                    </div>
                                    <div class="mt-2 flex items-center justify-between gap-2">
                                        <span id="valuation-words" class="text-xs font-bold text-emerald-700 truncate">₹4,00,00,000 (Four Crores)</span>
                                        <div class="flex items-center space-x-1.5 flex-shrink-0">
                                            <button type="button" onclick="setValuationMultiplier(4)" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 text-xs font-bold transition">4x</button>
                                            <button type="button" onclick="setValuationMultiplier(8)" class="px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 text-xs font-bold transition">8x</button>
                                            <button type="button" onclick="setValuationMultiplier(12)" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 text-xs font-bold transition">12x</button>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-800 mb-1.5">Equity Pool Offered (%) *</label>
                                    <div class="flex items-center border border-slate-200 form-input-focus rounded-xl bg-slate-50 transition overflow-hidden">
                                        <input type="number" id="input-equity" name="equity_offered" required value="11.1" step="0.1" min="0.1" max="100"
                                               class="w-full px-3.5 py-3 bg-transparent text-sm sm:text-base text-slate-900 outline-none font-bold" oninput="syncEquitySlider(this.value)">
                                        <span class="px-3.5 py-3 bg-slate-100 text-slate-700 font-bold border-l border-slate-200 text-sm select-none">%</span>
                                    </div>
                                    <div class="mt-3 flex items-center space-x-3">
                                        <input type="range" id="slider-equity" min="1" max="40" step="0.1" value="11.1" 
                                               class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600" oninput="syncEquityInput(this.value)">
                                        <span class="text-xs text-slate-500 font-bold whitespace-nowrap">Slider Sync</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Capital Purpose & Milestones Card -->
                        <div class="clean-card rounded-2xl p-6 sm:p-7 space-y-4">
                            <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                    <i data-lucide="compass" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-900 text-base">Use of Capital & Key Milestones</h3>
                                    <p class="text-xs sm:text-sm text-slate-500 font-medium">Briefly outline how the raised capital will be deployed.</p>
                                </div>
                            </div>

                            <div class="border border-slate-200 form-input-focus rounded-xl bg-slate-50 transition p-1">
                                <textarea name="purpose" rows="3" required placeholder="• 50% Product development & engineering&#10;• 30% Go-to-market sales & customer growth&#10;• 20% Working capital runway & operational expansion"
                                          class="w-full p-3 bg-transparent rounded-lg text-sm text-slate-900 outline-none leading-relaxed font-medium placeholder:text-slate-400 resize-none"></textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Unified Financial Simulator Card (4 Columns) -->
                    <div class="lg:col-span-4 sticky top-6 space-y-4">
                        
                        <div class="clean-card rounded-2xl overflow-hidden shadow-sm">
                            <!-- Subtle Top Color Accent -->
                            <div class="h-2 bg-gradient-to-r from-indigo-600 via-indigo-500 to-emerald-500"></div>

                            <div class="p-5 sm:p-6 pb-3 border-b border-slate-100 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Real-Time Engine</span>
                                    </div>
                                    <h3 class="font-extrabold text-slate-900 text-base mt-1">Term Sheet Summary</h3>
                                </div>
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <i data-lucide="calculator" class="w-5 h-5"></i>
                                </div>
                            </div>

                            <div class="p-5 sm:p-6 space-y-4 text-sm">
                                
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 font-medium">Target Raise</span>
                                    <span class="font-black text-slate-900 text-base" id="live-target-disp">₹50,00,000</span>
                                </div>

                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 font-medium">Pre-Money Valuation</span>
                                    <span class="font-black text-slate-900 text-base" id="live-pre-disp">₹4,00,00,000</span>
                                </div>

                                <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-100 flex items-center justify-between">
                                    <span class="text-xs sm:text-sm font-bold text-indigo-900">Post-Money Valuation</span>
                                    <span class="text-base font-black text-indigo-700" id="live-post-disp">₹4,50,00,000</span>
                                </div>

                                <div class="flex justify-between items-center py-1">
                                    <span class="text-slate-600 font-medium">Implied Dilution</span>
                                    <span class="font-black text-emerald-600 text-base" id="live-dilution-disp">11.11%</span>
                                </div>

                                <!-- Visual Ownership Ratio Bar -->
                                <div class="pt-2 border-t border-slate-100 space-y-2">
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-slate-700">Founder: <span id="retained-pct" class="text-indigo-700">88.89%</span></span>
                                        <span class="text-emerald-700">Syndicate: <span id="investor-pct">11.11%</span></span>
                                    </div>
                                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex border border-slate-200">
                                        <div id="bar-founder" class="bg-indigo-600 h-full transition-all duration-300" style="width: 88.89%"></div>
                                        <div id="bar-investor" class="bg-emerald-500 h-full transition-all duration-300" style="width: 11.11%"></div>
                                    </div>
                                </div>

                                <!-- Compliance Badges -->
                                <div class="pt-3 border-t border-slate-100 space-y-2.5 text-xs text-slate-600 font-medium">
                                    <div class="flex items-center space-x-2">
                                        <i data-lucide="check" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                                        <span>Private placement compliance review</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <i data-lucide="check" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
                                        <span>Instant notification email to Founder</span>
                                    </div>
                                </div>

                                <!-- Submit Actions inside Card -->
                                <div class="pt-4 border-t border-slate-100 space-y-2.5">
                                    <button type="submit" class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-xs transition flex items-center justify-center space-x-2">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                        <span>Submit Round for Review</span>
                                    </button>
                                    <button type="button" onclick="showListView()" class="w-full py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                                        Cancel & Return
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>

                </form>
            </div>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#rounds-main-container", { duration: 0.3, y: 6, opacity: 0, ease: "power2.out" });

        function showFormView() {
            document.getElementById('rounds-list-view').classList.add('hidden');
            const formView = document.getElementById('rounds-form-view');
            formView.classList.remove('hidden');
            recalcRound();
            lucide.createIcons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            try {
                history.pushState(null, '', '?action=new');
            } catch(e) {}
        }

        function showListView() {
            document.getElementById('rounds-form-view').classList.add('hidden');
            const listView = document.getElementById('rounds-list-view');
            listView.classList.remove('hidden');
            lucide.createIcons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            try {
                history.pushState(null, '', 'funding_rounds.php');
            } catch(e) {}
        }

        function applyPreset(name, target, val, equity, btnElement) {
            document.getElementById('form-round-name').value = name;
            document.getElementById('input-target-amt').value = target;
            document.getElementById('input-valuation').value = val;
            document.getElementById('input-equity').value = equity;
            document.getElementById('slider-equity').value = equity;

            // Highlight active card
            document.querySelectorAll('.preset-card').forEach(c => {
                c.classList.remove('active', 'border-indigo-600', 'bg-indigo-50/60', 'shadow-xs');
                c.classList.add('border-slate-200', 'bg-white');
            });
            if (btnElement) {
                btnElement.classList.add('active', 'border-indigo-600', 'bg-indigo-50/60', 'shadow-xs');
                btnElement.classList.remove('border-slate-200', 'bg-white');
            }

            recalcRound();
        }

        function setTargetChip(val) {
            document.getElementById('input-target-amt').value = val;
            recalcRound();
        }

        function setValuationMultiplier(mult) {
            const target = parseFloat(document.getElementById('input-target-amt').value) || 0;
            if (target > 0) {
                document.getElementById('input-valuation').value = target * mult;
                recalcRound();
            }
        }

        function syncEquitySlider(val) {
            const num = parseFloat(val) || 0;
            document.getElementById('slider-equity').value = Math.min(40, Math.max(1, num));
            recalcRound();
        }

        function syncEquityInput(val) {
            document.getElementById('input-equity').value = val;
            recalcRound();
        }

        function formatInr(val) {
            return '₹' + Number(val).toLocaleString('en-IN');
        }

        function formatInrWords(val) {
            const num = Number(val);
            if (num >= 10000000) {
                const cr = (num / 10000000).toFixed(2);
                return '₹' + num.toLocaleString('en-IN') + ' (' + cr.replace(/\.00$/, '') + ' Crores)';
            } else if (num >= 100000) {
                const lk = (num / 100000).toFixed(2);
                return '₹' + num.toLocaleString('en-IN') + ' (' + lk.replace(/\.00$/, '') + ' Lakhs)';
            }
            return '₹' + num.toLocaleString('en-IN');
        }

        function recalcRound() {
            const target = parseFloat(document.getElementById('input-target-amt').value) || 0;
            const preval = parseFloat(document.getElementById('input-valuation').value) || 0;
            const postVal = preval + target;

            // Update words labels
            if (document.getElementById('target-amt-words')) {
                document.getElementById('target-amt-words').textContent = formatInrWords(target);
            }
            if (document.getElementById('valuation-words')) {
                document.getElementById('valuation-words').textContent = formatInrWords(preval);
            }

            if (postVal > 0 && target > 0) {
                const dilution = ((target / postVal) * 100).toFixed(2);
                const retained = (100 - parseFloat(dilution)).toFixed(2);

                // Sync equity offered if user hasn't heavily custom overridden
                const equityInput = document.getElementById('input-equity');
                if (equityInput && document.activeElement !== equityInput) {
                    equityInput.value = dilution;
                    document.getElementById('slider-equity').value = Math.min(40, Math.max(1, parseFloat(dilution)));
                }

                document.getElementById('live-target-disp').textContent = formatInr(target);
                document.getElementById('live-pre-disp').textContent = formatInr(preval);
                document.getElementById('live-post-disp').textContent = formatInr(postVal);
                document.getElementById('live-dilution-disp').textContent = dilution + '%';

                document.getElementById('retained-pct').textContent = retained + '%';
                document.getElementById('investor-pct').textContent = dilution + '%';

                document.getElementById('bar-founder').style.width = retained + '%';
                document.getElementById('bar-investor').style.width = dilution + '%';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            recalcRound();
        });
    </script>
</body>
</html>
