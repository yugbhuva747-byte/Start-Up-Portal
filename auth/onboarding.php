<?php
/**
 * Progressive Onboarding Flow (Role-Tailored)
 */
require_once __DIR__ . '/../config.php';
$user = require_auth();

$db = get_db();
$role = $user['role'];
$message = '';
$error = '';

// Check current progress
$progress = get_profile_progress($user['id'], $role);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        if ($role === 'founder') {
            // Save Founder Profile & Company Information
            $bio = trim($_POST['bio'] ?? '');
            $linkedin = trim($_POST['linkedin_url'] ?? '');
            $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
            $companyName = trim($_POST['company_name'] ?? '');
            $cinNumber = strtoupper(trim($_POST['cin_number'] ?? ''));
            $industry = trim($_POST['industry'] ?? 'AI/SaaS');
            $stage = trim($_POST['stage'] ?? 'Seed');
            $pitch = trim($_POST['pitch'] ?? '');
            $description = trim($_POST['description'] ?? '');

            // 1. Update/Insert Founder Profile
            $fpStmt = $db->prepare("
                INSERT INTO founder_profiles (user_id, bio, linkedin_url, pan_number)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE bio = VALUES(bio), linkedin_url = VALUES(linkedin_url), pan_number = VALUES(pan_number)
            ");
            $fpStmt->execute([$user['id'], $bio, $linkedin, $pan]);

            // 2. Check or Create Company
            $checkComp = $db->prepare("SELECT company_id FROM company_founders WHERE user_id = ? LIMIT 1");
            $checkComp->execute([$user['id']]);
            $existComp = $checkComp->fetch();

            if ($existComp) {
                $compStmt = $db->prepare("
                    UPDATE companies SET name = ?, cin_number = ?, industry = ?, stage = ?, pitch = ?, description = ?
                    WHERE id = ?
                ");
                $compStmt->execute([$companyName, $cinNumber, $industry, $stage, $pitch, $description, $existComp['company_id']]);
                $companyId = $existComp['company_id'];
            } else {
                $compStmt = $db->prepare("
                    INSERT INTO companies (name, cin_number, industry, stage, pitch, description, verified_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $compStmt->execute([$companyName, $cinNumber, $industry, $stage, $pitch, $description]);
                $companyId = $db->lastInsertId();

                $cfStmt = $db->prepare("INSERT INTO company_founders (company_id, user_id, designation, is_signatory) VALUES (?, ?, 'Founder & CEO', 1)");
                $cfStmt->execute([$companyId, $user['id']]);
            }

            // Create initial verification request if not present
            $vrCheck = $db->prepare("SELECT id FROM verification_requests WHERE user_id = ?");
            $vrCheck->execute([$user['id']]);
            if (!$vrCheck->fetch()) {
                $vrStmt = $db->prepare("INSERT INTO verification_requests (user_id, company_id, verification_type, status, provider_name, provider_ref_id) VALUES (?, ?, 'digilocker_kyc', 'pending', 'DigiLocker Online', CONCAT('DL-REQ-', LPAD(FLOOR(RAND()*999999), 6, '0')))");
                $vrStmt->execute([$user['id'], $companyId]);
            }

            log_audit($user['id'], 'UPDATE_ONBOARDING', 'founder_profiles', $user['id'], 'Founder completed onboarding profile');
            set_flash('success', 'Onboarding details saved successfully! You can now access your dashboard.');
            header('Location: ' . url('founder/dashboard.php'));
            exit;

        } elseif ($role === 'investor') {
            $investorType = trim($_POST['investor_type'] ?? 'Angel Investor');
            $experience = (int)($_POST['experience_years'] ?? 3);
            $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
            $industries = trim($_POST['preferred_industries'] ?? '');
            $stages = trim($_POST['preferred_stages'] ?? '');
            $minTicket = (float)($_POST['min_ticket'] ?? 100000);
            $maxTicket = (float)($_POST['max_ticket'] ?? 2500000);
            $thesis = trim($_POST['investment_thesis'] ?? '');

            // Update Investor Profile
            $ipStmt = $db->prepare("
                INSERT INTO investor_profiles (user_id, investor_type, experience_years, pan_number, risk_disclosure_accepted)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE investor_type = VALUES(investor_type), experience_years = VALUES(experience_years), pan_number = VALUES(pan_number)
            ");
            $ipStmt->execute([$user['id'], $investorType, $experience, $pan]);

            // Update Investor Preferences
            $prefStmt = $db->prepare("
                INSERT INTO investor_preferences (user_id, preferred_industries, preferred_stages, min_ticket, max_ticket, investment_thesis)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE preferred_industries = VALUES(preferred_industries), preferred_stages = VALUES(preferred_stages), min_ticket = VALUES(min_ticket), max_ticket = VALUES(max_ticket), investment_thesis = VALUES(investment_thesis)
            ");
            $prefStmt->execute([$user['id'], $industries, $stages, $minTicket, $maxTicket, $thesis]);

            // Create initial verification request if not present
            $vrCheck = $db->prepare("SELECT id FROM verification_requests WHERE user_id = ?");
            $vrCheck->execute([$user['id']]);
            if (!$vrCheck->fetch()) {
                $vrStmt = $db->prepare("INSERT INTO verification_requests (user_id, verification_type, status, provider_name, provider_ref_id) VALUES (?, 'digilocker_kyc', 'pending', 'DigiLocker Online', CONCAT('DL-INV-', LPAD(FLOOR(RAND()*999999), 6, '0')))");
                $vrStmt->execute([$user['id']]);
            }

            log_audit($user['id'], 'UPDATE_ONBOARDING', 'investor_profiles', $user['id'], 'Investor completed onboarding profile');
            set_flash('success', 'Investment preferences updated! Welcome to your discovery dashboard.');
            header('Location: ' . url('investor/dashboard.php'));
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
    <title>Profile Onboarding • <?= APP_NAME ?></title>
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
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen p-4 md:p-8 flex items-center justify-center">
    
    <div class="max-w-2xl w-full" id="onboarding-card">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-white shadow-sm">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Complete Your Onboarding</h1>
                    <p class="text-xs text-slate-500">Step 2 of 2 • Role: <span class="capitalize font-semibold text-indigo-600"><?= htmlspecialchars($role) ?></span></p>
                </div>
            </div>
            <a href="<?= url($role === 'founder' ? 'founder/dashboard.php' : 'investor/dashboard.php') ?>" class="text-xs text-slate-500 hover:text-slate-900 px-3 py-1.5 rounded-lg bg-white border border-slate-200 shadow-sm transition">
                Skip for now →
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-5 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card-clean rounded-2xl p-4 sm:p-6 md:p-8">
            <form action="<?= url('auth/onboarding.php') ?>" method="POST" class="space-y-5 text-xs">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <?php if ($role === 'founder'): ?>
                    <!-- FOUNDER ONBOARDING FIELDS -->
                    <div class="space-y-4">
                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-1.5 border-b border-slate-100 pb-2">
                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                            <span>1. Founder Information</span>
                        </h2>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Founder Professional Bio</label>
                            <textarea name="bio" rows="2" placeholder="Briefly describe your background, domain experience, and previous ventures..."
                                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">LinkedIn Profile URL</label>
                                <input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/username"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Founder PAN Card Number</label>
                                <input type="text" name="pan_number" placeholder="ABCDE1234F" maxlength="10"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 uppercase outline-none transition">
                            </div>
                        </div>

                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-1.5 pt-3 border-b border-slate-100 pb-2">
                            <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
                            <span>2. Startup / Company Master Data</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Company / Legal Name *</label>
                                <input type="text" name="company_name" required placeholder="e.g. NextGen Robotics Pvt Ltd"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Corporate CIN Number *</label>
                                <input type="text" name="cin_number" required placeholder="U72900KA2023PTC123456"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 uppercase outline-none transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Industry / Category</label>
                                <select name="industry" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <option value="AI/SaaS">AI / Enterprise SaaS</option>
                                    <option value="FinTech">FinTech / Payments / InsurTech</option>
                                    <option value="HealthTech">HealthTech / BioTech / MedTech</option>
                                    <option value="CleanTech">CleanTech / Climate & EV</option>
                                    <option value="DeepTech">DeepTech / Robotics / Aerospace</option>
                                    <option value="E-Commerce">D2C & Consumer Commerce</option>
                                    <option value="EdTech">EdTech / Future of Work</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Current Startup Stage</label>
                                <select name="stage" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <option value="Idea / Prototype">Idea / MVP Prototype</option>
                                    <option value="Pre-Seed">Pre-Seed (Early Traction)</option>
                                    <option value="Seed" selected>Seed Stage</option>
                                    <option value="Pre-Series A">Pre-Series A</option>
                                    <option value="Series A">Series A</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">One-Line Pitch *</label>
                            <input type="text" name="pitch" required placeholder="e.g. AI-powered supply chain optimization platform for pharma"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Business Description & Problem Solved</label>
                            <textarea name="description" rows="3" placeholder="Explain your core product, market opportunity, and moat..."
                                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition"></textarea>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- INVESTOR ONBOARDING FIELDS -->
                    <div class="space-y-4">
                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-1.5 border-b border-slate-100 pb-2">
                            <i data-lucide="award" class="w-3.5 h-3.5"></i>
                            <span>1. Investor Persona & Accreditation</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Investor Classification</label>
                                <select name="investor_type" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <option value="Angel Investor">Individual Angel Investor</option>
                                    <option value="Venture Capital Fund">Venture Capital (VC) Partner</option>
                                    <option value="Family Office">Family Office / Single LP</option>
                                    <option value="Syndicate Lead">Angel Syndicate Lead</option>
                                    <option value="Corporate VC">Corporate Venture Capital (CVC)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Investing Experience (Years)</label>
                                <input type="number" name="experience_years" value="4" min="0" max="40"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Tax PAN Number</label>
                            <input type="text" name="pan_number" placeholder="ABCDE1234F" maxlength="10"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 uppercase outline-none transition">
                        </div>

                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-1.5 pt-3 border-b border-slate-100 pb-2">
                            <i data-lucide="target" class="w-3.5 h-3.5"></i>
                            <span>2. Investment Thesis & Ticket Range</span>
                        </h2>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Target Sectors / Industries</label>
                            <input type="text" name="preferred_industries" value="AI/SaaS, FinTech, HealthTech, CleanTech"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Minimum Ticket Size (₹)</label>
                                <input type="number" name="min_ticket" value="250000" step="50000"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Maximum Ticket Size (₹)</label>
                                <input type="number" name="max_ticket" value="5000000" step="100000"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Investment Thesis Summary</label>
                            <textarea name="investment_thesis" rows="3" placeholder="What specific founder profiles, technologies, or market shifts are you excited to back?"
                                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 placeholder-slate-400 outline-none transition"></textarea>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5 text-xs">
                    <span>Save Details & Access Dashboard</span>
                    <i data-lucide="check" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#onboarding-card", {
            duration: 0.5,
            y: 15,
            opacity: 0,
            ease: "power2.out"
        });
    </script>
</body>
</html>
