<?php
/**
 * Main Landing Page - STARTUP × INVESTOR Portal
 * Clean White / Light Theme, Crisp Small Typography, Professional Look
 */
require_once __DIR__ . '/config.php';

$db = get_db();
$featuredStartups = [];
$totalFundingRaised = 0;
$totalStartups = 0;
$totalInvestors = 0;

if ($db) {
    try {
        $st = $db->query("
            SELECT c.*, fr.target_amount, fr.amount_raised, fr.status as round_status, fr.round_name, fr.valuation
            FROM companies c
            LEFT JOIN funding_rounds fr ON c.id = fr.company_id
            WHERE c.verified_status = 'verified'
            ORDER BY fr.amount_raised DESC
            LIMIT 3
        ");
        $featuredStartups = $st->fetchAll();

        $totalFundingRaised = (float)$db->query("SELECT SUM(amount_raised) FROM funding_rounds")->fetchColumn() ?: 12000000;
        $totalStartups = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn() ?: 12;
        $totalInvestors = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'investor'")->fetchColumn() ?: 45;
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STARTUP × INVESTOR • Verified Early-Stage Deal Flow & Capital</title>
    <meta name="description" content="A secure two-sided startup ecosystem connecting verified founders and accredited investors through structured profiles, DigiLocker verification, and funding workflows.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
        }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .card-clean:hover {
            border-color: #CBD5E1;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.06), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 selection:bg-indigo-100 selection:text-indigo-900 antialiased min-h-screen">

    <!-- Top Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-200 bg-white/95 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="<?= url('index.php') ?>" class="flex items-center space-x-2.5 group">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="text-sm font-extrabold tracking-tight text-slate-900 flex items-center gap-1">
                        STARTUP <span class="text-indigo-600">×</span> INVESTOR
                    </span>
                    <span class="block text-[8.5px] tracking-widest text-slate-400 uppercase font-bold">Venture Platform</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center space-x-7 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <a href="#ecosystem" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i data-lucide="layers" class="w-3 h-3 text-slate-400"></i>
                    <span>Ecosystem</span>
                </a>
                <a href="#discovery" class="hover:text-slate-900 transition flex items-center gap-1">
                    <i data-lucide="compass" class="w-3 h-3 text-slate-400"></i>
                    <span>Live Rounds</span>
                </a>
                <a href="<?= url('setup.php') ?>" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-100">
                    <i data-lucide="database" class="w-3 h-3"></i>
                    <span>DB Diagnostics</span>
                </a>
            </nav>

            <div class="flex items-center space-x-3">
                <?php if (auth_check()): 
                    $u = current_user();
                    $dashUrl = match($u['role']) {
                        'founder' => 'founder/dashboard.php',
                        'investor' => 'investor/dashboard.php',
                        'admin' => 'admin/dashboard.php',
                        default => 'index.php'
                    };
                ?>
                    <a href="<?= url($dashUrl) ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <span>Dashboard (<?= ucfirst($u['role']) ?>)</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= url('auth/login.php') ?>" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition px-2.5 py-1.5">
                        Sign In
                    </a>
                    <a href="<?= url('auth/register.php') ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition flex items-center space-x-1.5">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                        <span>Get Started</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-16 px-6">
        <div class="max-w-4xl mx-auto text-center">
            
            <!-- Verified Pill Badge -->
            <div id="hero-badge" class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white border border-slate-200 text-[11px] font-semibold text-slate-600 mb-6 shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="text-emerald-700 font-bold">MCA & DigiLocker Verified</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500">Two-Sided Venture Infrastructure</span>
            </div>

            <!-- Headline -->
            <h1 id="hero-title" class="text-3xl sm:text-5xl font-black tracking-tight text-slate-900 leading-[1.15] mb-4">
                Where Verified Startups Meet <span class="text-indigo-600">Smart Capital</span>.
            </h1>

            <!-- Subtitle -->
            <p id="hero-desc" class="max-w-xl mx-auto text-xs sm:text-sm text-slate-600 mb-8 leading-relaxed">
                Connect India's verified startup founders with accredited angel syndicates and VCs. Corporate CIN integration, DigiLocker KYC, structured data rooms, and milestone funding escrow.
            </p>

            <!-- Dual Action CTAs -->
            <div id="hero-actions" class="flex flex-col sm:flex-row items-center justify-center gap-3 mb-14">
                <a href="<?= url('auth/register.php?role=founder') ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm flex items-center justify-center space-x-2 transition">
                    <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                    <span>Raise Capital as Founder</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
                <a href="<?= url('auth/register.php?role=investor') ?>" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 font-bold text-xs shadow-sm flex items-center justify-center space-x-2 transition">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i>
                    <span>Discover Startups as Investor</span>
                </a>
            </div>

            <!-- Stats Bar -->
            <div id="hero-stats" class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-3xl mx-auto">
                <div class="card-clean rounded-xl p-4 text-left">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Capital Facilitated</div>
                    <div class="text-lg font-black text-slate-900"><?= format_inr($totalFundingRaised) ?>+</div>
                    <div class="text-[10px] text-emerald-600 font-semibold mt-0.5">Escrow Reconciled</div>
                </div>

                <div class="card-clean rounded-xl p-4 text-left">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Verification Rate</div>
                    <div class="text-lg font-black text-emerald-600">100%</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">MCA & DigiLocker KYC</div>
                </div>

                <div class="card-clean rounded-xl p-4 text-left">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Active Startups</div>
                    <div class="text-lg font-black text-slate-900"><?= $totalStartups ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Live Deal Rooms</div>
                </div>

                <div class="card-clean rounded-xl p-4 text-left">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Investors</div>
                    <div class="text-lg font-black text-slate-900"><?= $totalInvestors ?></div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Angels & Syndicate Leads</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Two-Sided Ecosystem -->
    <section id="ecosystem" class="py-16 px-6 border-t border-slate-200/80 bg-white">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-12">
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-600 mb-1 block">Platform Architecture</span>
                <h2 class="text-2xl font-black text-slate-900">Two Specialized Workspaces</h2>
                <p class="text-slate-500 text-xs mt-1.5 max-w-md mx-auto">Distinct onboarding questions, separate dashboards, strict permissions, and compliance pipelines.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Founder Card -->
                <div class="card-clean rounded-2xl p-7 border border-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 mb-5">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                    <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mb-1">Founder Portal</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Startup Fundraising Hub</h3>
                    <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                        Create structured company master records, launch customizable funding rounds (Seed, Pre-Series A), upload confidential data rooms, and connect with verified angels.
                    </p>
                    <div class="space-y-2 text-xs text-slate-600 mb-6 border-t border-slate-100 pt-4">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Corporate CIN & DigiLocker KYC verification</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Funding state machine (Draft → Live → Funded)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-indigo-600"></i>
                            <span>Encrypted deal room chat with verified investors</span>
                        </div>
                    </div>
                    <a href="<?= url('auth/register.php?role=founder') ?>" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs flex items-center justify-center space-x-1.5 transition">
                        <span>Launch Founder Profile</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>

                <!-- Investor Card -->
                <div class="card-clean rounded-2xl p-7 border border-slate-200">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 mb-5">
                        <i data-lucide="pie-chart" class="w-5 h-5"></i>
                    </div>
                    <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1">Investor Portal</div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Deal Discovery & Portfolio</h3>
                    <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                        Filter high-caliber startups across FinTech, AI/SaaS, HealthTech, review financial models, commit capital within target allocation limits, and track your active portfolio.
                    </p>
                    <div class="space-y-2 text-xs text-slate-600 mb-6 border-t border-slate-100 pt-4">
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Multi-parameter filtering (Sector, Stage, Ticket)</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Automated investment allocation & escrow reference</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Direct portfolio equity % & certificate tracking</span>
                        </div>
                    </div>
                    <a href="<?= url('auth/register.php?role=investor') ?>" class="w-full py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs flex items-center justify-center space-x-1.5 transition">
                        <span>Discover Startup Deal Flow</span>
                        <i data-lucide="arrow-right" class="w-3 h-3"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Startup Spotlight -->
    <section id="discovery" class="py-16 px-6 bg-[#FAFAFB] border-t border-slate-200/80">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest block mb-0.5">Live Deals</span>
                    <h2 class="text-xl font-bold text-slate-900">Featured Verified Startups</h2>
                </div>
                <a href="<?= url('investor/discover.php') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center space-x-1">
                    <span>View All Startups</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <?php if (empty($featuredStartups)): ?>
                    <div class="col-span-3 card-clean rounded-2xl p-8 text-center text-slate-500 text-xs">
                        No active startups currently listed.
                    </div>
                <?php else: ?>
                    <?php foreach ($featuredStartups as $company): 
                        $pct = ($company['target_amount'] ?? 0) > 0 ? round(($company['amount_raised'] / $company['target_amount']) * 100) : 0;
                        $hashId = hash_id_encode($company['id']);
                    ?>
                        <div class="card-clean rounded-2xl p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between mb-3">
                                    <img src="<?= $company['logo_url'] ?: 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=80' ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        <?= htmlspecialchars($company['industry']) ?>
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 mb-1"><?= htmlspecialchars($company['name']) ?></h3>
                                <p class="text-xs text-slate-500 line-clamp-2 mb-4"><?= htmlspecialchars($company['pitch']) ?></p>
                            </div>

                            <div class="pt-3 border-t border-slate-100">
                                <div class="flex justify-between text-[11px] mb-1 font-semibold">
                                    <span class="text-slate-500">Raised <?= format_inr($company['amount_raised'] ?? 0) ?></span>
                                    <span class="text-slate-900 font-bold"><?= $pct ?>%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mb-3">
                                    <div class="h-full bg-indigo-600 rounded-full" style="width: <?= min(100, $pct) ?>%"></div>
                                </div>
                                <a href="<?= url('investor/startup_detail.php?id=' . $hashId) ?>" class="w-full py-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 text-center text-xs font-semibold block transition">
                                    View Data Room →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-slate-200 py-10 px-6 bg-white text-slate-500 text-xs">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 rounded bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                </div>
                <span class="font-bold text-slate-800 text-xs">STARTUP × INVESTOR</span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500 text-[11px]">Two-Sided Platform</span>
            </div>
            <div class="flex items-center space-x-5 text-xs text-slate-600 font-medium">
                <a href="<?= url('auth/login.php') ?>" class="hover:text-slate-900 transition">Sign In</a>
                <a href="<?= url('auth/register.php?role=founder') ?>" class="hover:text-slate-900 transition">Founder Portal</a>
                <a href="<?= url('auth/register.php?role=investor') ?>" class="hover:text-slate-900 transition">Investor Portal</a>
                <a href="<?= url('setup.php') ?>" class="hover:text-slate-900 text-indigo-600 transition">Diagnostics</a>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
        gsap.from("#hero-badge", { y: -15, opacity: 0, duration: 0.5, ease: "power2.out" });
        gsap.from("#hero-title", { y: 20, opacity: 0, duration: 0.6, ease: "power2.out", delay: 0.1 });
        gsap.from("#hero-desc", { y: 15, opacity: 0, duration: 0.5, ease: "power2.out", delay: 0.2 });
        gsap.from("#hero-actions", { y: 15, opacity: 0, duration: 0.5, ease: "power2.out", delay: 0.3 });
        gsap.from("#hero-stats > div", { y: 15, opacity: 0, stagger: 0.08, duration: 0.4, ease: "power2.out", delay: 0.4 });
    </script>
    <?php include_once __DIR__ . '/includes/smooth_scroll.php'; ?>
</body>
</html>
