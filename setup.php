<?php
/**
 * Automated Database Installer & Data Seeder
 * Run via browser (http://localhost/start up portal/setup.php) or CLI (php setup.php)
 */

require_once __DIR__ . '/config.php';

$output = [];
$status = 'pending';

try {
    // 1. Connect without dbname first to ensure database exists
    $dsnNoDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $pdoRoot = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $output[] = "✓ Database `" . DB_NAME . "` created or already exists.";

    // 2. Connect with dbname
    $db = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 3. Read and execute database.sql
    $sqlContent = file_get_contents(__DIR__ . '/database.sql');
    $db->exec($sqlContent);
    $output[] = "✓ All 17 tables created successfully.";

    // 4. Seed Essential Accounts & Demo Data
    $passwordHash = password_hash('password123', PASSWORD_BCRYPT);

    $check = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($check == 0) {
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, password_hash, role, status, is_verified, avatar_url, city, country) VALUES (?, ?, ?, ?, ?, 'active', 1, ?, 'Mumbai', 'India')");
        $stmt->execute(['Portal Compliance Admin', 'admin@portal.com', '+91 9876543210', $passwordHash, 'admin', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80']);
        $adminId = $db->lastInsertId();

        $stmt->execute(['Aarav Sharma', 'founder@techpulse.io', '+91 9123456780', $passwordHash, 'founder', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80']);
        $founder1Id = $db->lastInsertId();

        $stmt->execute(['Priya Patel', 'priya@biozenith.com', '+91 9123456781', $passwordHash, 'founder', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80']);
        $founder2Id = $db->lastInsertId();

        $stmt->execute(['Rohan Mehta', 'rohan@solarispulse.in', '+91 9123456782', $passwordHash, 'founder', 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=150&auto=format&fit=crop&q=80']);
        $founder3Id = $db->lastInsertId();

        $stmt->execute(['Vikramaditya Singhania', 'investor@venturecapital.com', '+91 9988776655', $passwordHash, 'investor', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80']);
        $investor1Id = $db->lastInsertId();

        $stmt->execute(['Ananya Deshmukh', 'ananya@angelsyn.io', '+91 9988776656', $passwordHash, 'investor', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80']);
        $investor2Id = $db->lastInsertId();

        // Founder Profiles
        $fpStmt = $db->prepare("INSERT INTO founder_profiles (user_id, bio, linkedin_url, website_url, pan_number, designation) VALUES (?, ?, ?, ?, ?, ?)");
        $fpStmt->execute([$founder1Id, 'Serial Entrepreneur with 8+ years building enterprise AI and B2B SaaS platforms. Ex-Googler, IIT Bombay alumnus.', 'https://linkedin.com/in/aarav-sharma-demo', 'https://techpulse.io', 'ABCPS1234F', 'Founder & CEO']);
        $fpStmt->execute([$founder2Id, 'Biotechnology researcher and health-tech founder developing non-invasive AI diagnostics tools.', 'https://linkedin.com/in/priya-patel-demo', 'https://biozenith.com', 'BNMPP5678K', 'Co-Founder & CEO']);
        $fpStmt->execute([$founder3Id, 'CleanTech pioneer with patented smart EV battery swapping solutions.', 'https://linkedin.com/in/rohan-mehta-demo', 'https://solarispulse.in', 'XYZPR9012M', 'Founder & CTO']);

        // Investor Profiles & Preferences
        $ipStmt = $db->prepare("INSERT INTO investor_profiles (user_id, investor_type, experience_years, pan_number, accreditation_status, risk_disclosure_accepted) VALUES (?, ?, ?, ?, 'verified', 1)");
        $ipStmt->execute([$investor1Id, 'Angel Syndicate Lead', 7, 'AAAPS7890L']);
        $ipStmt->execute([$investor2Id, 'Venture Capital Partner', 11, 'ZZZPD4321R']);

        $prefStmt = $db->prepare("INSERT INTO investor_preferences (user_id, preferred_industries, preferred_stages, min_ticket, max_ticket, geography, investment_thesis) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $prefStmt->execute([$investor1Id, 'AI/SaaS, FinTech, DeepTech', 'Seed, Pre-Series A', 250000.00, 2500000.00, 'India, Southeast Asia', 'Backing passionate founders solving deep enterprise workflow and financial infrastructure challenges.']);
        $prefStmt->execute([$investor2Id, 'HealthTech, CleanTech, B2B SaaS', 'Seed, Series A', 500000.00, 10000000.00, 'India, US', 'Investing in sustainable technologies, preventive healthcare, and high-margin B2B SaaS platforms.']);

        // Insert Startups
        $compStmt = $db->prepare("INSERT INTO companies (name, legal_name, cin_number, incorporation_date, industry, stage, business_model, pitch, description, target_market, employee_count, website, logo_url, city, state, country, verified_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'verified')");
        
        $compStmt->execute([
            'TechPulse AI', 'TechPulse Intelligence Private Limited', 'U72900KA2023PTC156789', '2023-03-15', 'AI/SaaS', 'Seed', 'B2B Subscription',
            'Autonomous AI copilots for enterprise compliance and contract review.',
            'TechPulse AI empowers legal and compliance teams to automate complex regulatory risk assessments in seconds using proprietary multi-agent LLM pipelines tailored for Indian and global jurisdictions.',
            'Mid to large BFSI and tech enterprises', 14, 'https://techpulse.io',
            'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=150&auto=format&fit=crop&q=80',
            'Bengaluru', 'Karnataka', 'India'
        ]);
        $comp1Id = $db->lastInsertId();

        $compStmt->execute([
            'BioZenith Labs', 'BioZenith Diagnostics Private Limited', 'U24230MH2022PTC189012', '2022-07-20', 'HealthTech', 'Pre-Series A', 'Hardware + Cloud SaaS',
            'Next-generation non-invasive blood biomarker analysis with instant IoT reporting.',
            'BioZenith enables point-of-care rapid testing with micro-spectroscopy devices connected to our cloud-based machine learning diagnostic platform, reducing diagnostic turnaround from days to 3 minutes.',
            'Diagnostic clinics, hospitals, remote health centers', 22, 'https://biozenith.com',
            'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=150&auto=format&fit=crop&q=80',
            'Mumbai', 'Maharashtra', 'India'
        ]);
        $comp2Id = $db->lastInsertId();

        $compStmt->execute([
            'SolarisPulse Mobility', 'SolarisPulse CleanTech Private Limited', 'U34100DL2023PTC199431', '2023-01-10', 'CleanTech', 'Seed', 'Hardware-as-a-Service',
            'Ultra-fast modular solar swapping stations for commercial 2W and 3W EV fleets.',
            'SolarisPulse builds zero-carbon solar-assisted battery swapping hubs optimized for gig-economy delivery fleets, providing 90-second battery swaps with zero grid dependency.',
            'Commercial EV fleet operators and delivery platforms', 18, 'https://solarispulse.in',
            'https://images.unsplash.com/photo-1508873696983-2df5293cb32f?w=150&auto=format&fit=crop&q=80',
            'New Delhi', 'Delhi', 'India'
        ]);
        $comp3Id = $db->lastInsertId();

        // Mapping
        $cfStmt = $db->prepare("INSERT INTO company_founders (company_id, user_id, designation, equity_percent, is_signatory) VALUES (?, ?, ?, ?, 1)");
        $cfStmt->execute([$comp1Id, $founder1Id, 'Founder & CEO', 52.00]);
        $cfStmt->execute([$comp2Id, $founder2Id, 'Co-Founder & CEO', 48.00]);
        $cfStmt->execute([$comp3Id, $founder3Id, 'Founder & CTO', 60.00]);

        // Documents
        $docStmt = $db->prepare("INSERT INTO company_documents (company_id, document_type, title, file_path, file_size, is_verified, access_level) VALUES (?, ?, ?, ?, ?, 1, 'registered_investors')");
        $docStmt->execute([$comp1Id, 'Pitch Deck', 'TechPulse AI - Seed Pitch Deck 2026.pdf', 'uploads/docs/techpulse_deck.pdf', '3.8 MB']);
        $docStmt->execute([$comp1Id, 'Certificate of Incorporation', 'MCA Incorporation Certificate.pdf', 'uploads/docs/techpulse_cin.pdf', '1.2 MB']);
        $docStmt->execute([$comp1Id, 'Financial Model', '5-Year Projections & Unit Economics.xlsx', 'uploads/docs/techpulse_financials.xlsx', '850 KB']);
        
        $docStmt->execute([$comp2Id, 'Pitch Deck', 'BioZenith Labs - Series A Deck.pdf', 'uploads/docs/biozenith_deck.pdf', '5.1 MB']);
        $docStmt->execute([$comp3Id, 'Pitch Deck', 'SolarisPulse Mobility Investor Deck.pdf', 'uploads/docs/solarispulse_deck.pdf', '4.4 MB']);

        // Verification Requests
        $vrStmt = $db->prepare("INSERT INTO verification_requests (user_id, company_id, verification_type, status, provider_name, provider_ref_id, remarks, verified_at) VALUES (?, ?, 'digilocker_kyc', 'verified', 'DigiLocker National Gateway', ?, 'Aadhaar, PAN and MCA CIN matched with 100% confidence.', NOW())");
        $vrStmt->execute([$founder1Id, $comp1Id, 'DL-VER-982145']);
        $vrStmt->execute([$founder2Id, $comp2Id, 'DL-VER-872341']);
        $vrStmt->execute([$investor1Id, null, 'DL-VER-654981']);

        // Funding Rounds
        $frStmt = $db->prepare("INSERT INTO funding_rounds (company_id, round_name, target_amount, min_investment, max_investment, amount_raised, valuation, equity_offered, status, start_date, end_date, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $frStmt->execute([
            $comp1Id, 'Seed Round', 10000000.00, 250000.00, 2500000.00, 5000000.00, 80000000.00, 12.50, 'LIVE',
            date('Y-m-d', strtotime('-15 days')), date('Y-m-d', strtotime('+45 days')),
            'Expansion of engineering team, scaling GPU compute infrastructure, and onboarding 50 enterprise BFSI pilots.'
        ]);
        $round1Id = $db->lastInsertId();

        $frStmt->execute([
            $comp2Id, 'Pre-Series A', 20000000.00, 500000.00, 5000000.00, 7000000.00, 150000000.00, 13.33, 'LIVE',
            date('Y-m-d', strtotime('-10 days')), date('Y-m-d', strtotime('+50 days')),
            'Clinical validation trials across 5 tier-1 hospital chains, ISO 13485 certification, and manufacturing pilot batch of 500 devices.'
        ]);
        $round2Id = $db->lastInsertId();

        $frStmt->execute([
            $comp3Id, 'Seed Round', 15000000.00, 300000.00, 3000000.00, 0.00, 90000000.00, 16.66, 'UNDER_REVIEW',
            date('Y-m-d'), date('Y-m-d', strtotime('+60 days')),
            'Deployment of 20 solar battery swap stations in Delhi NCR and IoT telematics firmware optimization.'
        ]);
        $round3Id = $db->lastInsertId();

        // Investment Orders & Investments
        $ioStmt = $db->prepare("INSERT INTO investment_orders (funding_round_id, investor_user_id, amount, status, terms_accepted) VALUES (?, ?, ?, 'CONFIRMED', 1)");
        $ioStmt->execute([$round1Id, $investor1Id, 2500000.00]);
        $order1Id = $db->lastInsertId();

        $ioStmt->execute([$round1Id, $investor2Id, 2500000.00]);
        $order2Id = $db->lastInsertId();

        $invStmt = $db->prepare("INSERT INTO investments (order_id, funding_round_id, investor_user_id, company_id, amount_invested, equity_allotted_percent, certificate_number, confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $invStmt->execute([$order1Id, $round1Id, $investor1Id, $comp1Id, 2500000.00, 3.125, 'CERT-TP-2026-001']);
        $inv1Id = $db->lastInsertId();

        $invStmt->execute([$order2Id, $round1Id, $investor2Id, $comp1Id, 2500000.00, 3.125, 'CERT-TP-2026-002']);
        $inv2Id = $db->lastInsertId();

        // Transactions
        $txStmt = $db->prepare("INSERT INTO transactions (investment_id, order_id, transaction_ref, payment_mode, amount, status) VALUES (?, ?, ?, 'Escrow Wire / UPI', ?, 'SUCCESS')");
        $txStmt->execute([$inv1Id, $order1Id, 'TXN-ESC-9871234', 2500000.00]);
        $txStmt->execute([$inv2Id, $order2Id, 'TXN-ESC-9871235', 2500000.00]);

        // Watchlists
        $wlStmt = $db->prepare("INSERT INTO watchlists (investor_user_id, company_id) VALUES (?, ?)");
        $wlStmt->execute([$investor1Id, $comp1Id]);
        $wlStmt->execute([$investor1Id, $comp2Id]);
        $wlStmt->execute([$investor2Id, $comp1Id]);

        // Conversations & Messages
        $convStmt = $db->prepare("INSERT INTO conversations (user_one_id, user_two_id, company_id, last_message_at) VALUES (?, ?, ?, NOW())");
        $convStmt->execute([$founder1Id, $investor1Id, $comp1Id]);
        $convId = $db->lastInsertId();

        $msgStmt = $db->prepare("INSERT INTO messages (conversation_id, sender_user_id, message_text, is_read, created_at) VALUES (?, ?, ?, 1, ?)");
        $msgStmt->execute([$convId, $investor1Id, 'Hello Aarav, I reviewed your seed deck and traction in the BFSI sector. Impressive unit economics!', date('Y-m-d H:i:s', strtotime('-2 hours'))]);
        $msgStmt->execute([$convId, $founder1Id, 'Thank you Vikramaditya! We just completed pilot integrations with 3 leading private banks. Happy to answer any questions or share the detailed data room.', date('Y-m-d H:i:s', strtotime('-1 hour'))]);
        $msgStmt->execute([$convId, $investor1Id, 'Sounds excellent. I have confirmed our ₹25L allocation into the round. Looking forward to our scheduled sync next Tuesday.', date('Y-m-d H:i:s', strtotime('-30 minutes'))]);

        // Notifications
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, action_url) VALUES (?, ?, ?, ?, ?)");
        $notifStmt->execute([$founder1Id, 'Investment Confirmed!', 'Vikramaditya Singhania invested ₹25,00,000 in your Seed Round.', 'success', 'founder/funding_rounds.php']);
        $notifStmt->execute([$investor1Id, 'Allocation Allotted', 'Your investment of ₹25,00,000 in TechPulse AI has been confirmed.', 'success', 'investor/portfolio.php']);

        log_audit($founder1Id, 'CREATE_FUNDING_ROUND', 'funding_rounds', $round1Id, 'Founder created Seed Round with target ₹1,00,00,000');
        log_audit($investor1Id, 'SUBMIT_INVESTMENT_ORDER', 'investment_orders', $order1Id, 'Investor submitted order for ₹25,00,000');
        log_audit($adminId, 'VERIFY_COMPANY', 'companies', $comp1Id, 'Compliance Admin approved KYC and CIN verification for TechPulse AI');

        $output[] = "✓ Seed data populated successfully with Admin, Founders, Investors, Startups & Funding Rounds.";
    } else {
        $output[] = "✓ Database is connected and verified. Users and seed records are already initialized.";
    }

    $status = 'success';
} catch (Exception $e) {
    $status = 'error';
    $output[] = "❌ Error: " . $e->getMessage();
}

if (php_sapi_name() === 'cli') {
    echo implode("\n", $output) . "\nStatus: $status\n";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Diagnostics - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex items-center justify-center p-6 selection:bg-indigo-500 selection:text-white">
    <div class="max-w-xl w-full card-clean rounded-2xl p-6 md:p-8 relative" id="setup-card">
        
        <div class="flex items-center space-x-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20">
                <i data-lucide="database" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-base font-extrabold tracking-tight text-slate-900"><?= APP_NAME ?></h1>
                <p class="text-[11px] text-slate-500 font-medium">Database Diagnostics & Environment Readiness</p>
            </div>
        </div>

        <div class="space-y-3 mb-5">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono space-y-1.5">
                <?php foreach ($output as $line): ?>
                    <div class="<?= str_contains($line, '❌') ? 'text-rose-600 font-semibold' : (str_contains($line, '✓') ? 'text-emerald-700 font-medium flex items-center gap-1.5' : 'text-slate-600') ?>">
                        <?= htmlspecialchars($line) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-4 mb-5 text-xs">
                <div class="font-bold text-indigo-900 mb-2.5 flex items-center space-x-1.5 text-[11px]">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-indigo-600"></i>
                    <span>Ready Demo Credentials</span>
                </div>
                <div class="space-y-1.5 text-slate-700">
                    <div class="flex justify-between items-center py-1 border-b border-indigo-100/60">
                        <span class="font-semibold text-emerald-800 text-[11px] flex items-center gap-1.5">
                            <i data-lucide="shield" class="w-3 h-3 text-emerald-600"></i>
                            <span>Admin Portal:</span>
                        </span>
                        <code class="text-[11px] bg-white px-2 py-0.5 rounded border border-slate-200 font-mono text-slate-800">admin@portal.com</code>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-indigo-100/60">
                        <span class="font-semibold text-indigo-800 text-[11px] flex items-center gap-1.5">
                            <i data-lucide="rocket" class="w-3 h-3 text-indigo-600"></i>
                            <span>Founder Portal:</span>
                        </span>
                        <code class="text-[11px] bg-white px-2 py-0.5 rounded border border-slate-200 font-mono text-slate-800">founder@techpulse.io</code>
                    </div>
                    <div class="flex justify-between items-center py-1">
                        <span class="font-semibold text-teal-800 text-[11px] flex items-center gap-1.5">
                            <i data-lucide="trending-up" class="w-3 h-3 text-teal-600"></i>
                            <span>Investor Portal:</span>
                        </span>
                        <code class="text-[11px] bg-white px-2 py-0.5 rounded border border-slate-200 font-mono text-slate-800">investor@venturecapital.com</code>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 mt-2 text-right">Password for all: <code class="font-semibold text-slate-700">password123</code></div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <a href="<?= url('index.php') ?>" class="w-full py-2.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-center text-xs font-semibold transition flex items-center justify-center space-x-1.5">
                    <i data-lucide="home" class="w-3.5 h-3.5"></i>
                    <span>Homepage</span>
                </a>
                <a href="<?= url('auth/login.php') ?>" class="w-full py-2.5 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-center text-xs font-semibold shadow-sm transition flex items-center justify-center space-x-1.5">
                    <span>Sign In to Portal</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        <?php else: ?>
            <button onclick="window.location.reload()" class="w-full py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition flex items-center justify-center space-x-1.5 shadow-sm">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                <span>Retry Connection</span>
            </button>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#setup-card", { duration: 0.4, y: 15, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
