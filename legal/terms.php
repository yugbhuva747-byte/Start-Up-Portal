<?php
/**
 * Legal: Terms of Service & Venture Investor Agreement
 * Compliant with Indian Companies Act, 2013 and SEBI (AIF/Angel Fund) Regulations
 */
require_once __DIR__ . '/../config.php';
$pageTitle = 'Terms of Service • ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="text-slate-900 min-h-screen flex flex-col antialiased selection:bg-indigo-600 selection:text-white">

    <!-- Header Navigation -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="<?= url('index.php') ?>" class="flex items-center space-x-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:scale-105 transition">
                    <i data-lucide="shield" class="w-5 h-5"></i>
                </div>
                <span class="font-extrabold text-base tracking-tight text-slate-900"><?= APP_NAME ?> <span class="text-indigo-600">LEGAL</span></span>
            </a>
            <div class="flex items-center space-x-3">
                <a href="<?= url('auth/login.php') ?>" class="text-xs sm:text-sm font-bold text-slate-600 hover:text-indigo-600 transition px-3 py-1.5 rounded-lg">Sign In</a>
                <a href="<?= url('auth/register.php') ?>" class="text-xs sm:text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-xl shadow-xs transition">Create Account</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-14 w-full">
        <!-- Badge & Title -->
        <div class="mb-10 text-center sm:text-left border-b border-slate-200 pb-8">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold uppercase tracking-wider mb-3">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                <span>Statutory Master Agreement</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Terms of Service & Platform Governance</h1>
            <p class="text-slate-500 text-sm sm:text-base mt-2">Last Updated: October 2026 • Effective for all accredited investors, angel syndicates, and registered startup entities.</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-sm space-y-8 text-sm sm:text-base leading-relaxed text-slate-700">
            
            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                    <span>Acceptance of Platform Terms</span>
                </h2>
                <p>
                    By registering for an account, accessing dealflow, deploying commitments, or listing a corporate venture profile on <strong><?= APP_NAME ?></strong>, you irrevocably agree to comply with and be legally bound by these Terms of Service, our Privacy Policy, and applicable Indian financial regulations under SEBI and the Ministry of Corporate Affairs (MCA).
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                    <span>Accredited Investor & Issuer Eligibility</span>
                </h2>
                <p>
                    Participation on the platform is restricted to individuals and institutional entities qualifying as <em>Accredited Investors</em> under SEBI Alternative Investment Funds (AIF) guidelines, or incorporated entities validly registered under the Indian Companies Act, 2013 with valid Corporate Identification Numbers (CIN).
                </p>
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm font-medium">
                    <strong>Notice:</strong> All users are required to undergo statutory identity verification (PAN, Aadhaar via DigiLocker, and bank KYC) before participating in private syndicate dealflow or cap table allotments.
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                    <span>High-Risk Venture Investment Warning</span>
                </h2>
                <p>
                    Investment in early-stage, seed, and growth startups involves substantial capital risk, including complete loss of invested principal, illiquidity, and dilution. Historical startup performance is no guarantee of future returns. <strong><?= APP_NAME ?></strong> does not provide legal, tax, or investment advice.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">4</span>
                    <span>Escrow Ledger & Allotment Governance</span>
                </h2>
                <p>
                    All subscription wires are held in regulated nodal escrow bank accounts until the minimum round target is reached and legal allotment documents (PAS-3 return of allotment, board resolutions, and share certificate issuance) are duly reconciled by platform compliance officers.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">5</span>
                    <span>Confidentiality & Data Room Governance</span>
                </h2>
                <p>
                    Non-public financial models, cap table allocations, pitch decks, and investor correspondence accessed via the Data Room constitute confidential proprietary business secrets. Unauthorized distribution or reproduction will result in immediate termination and legal action.
                </p>
            </section>

        </div>

        <div class="mt-8 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved. Registered Indian Technology & Venture Platform.
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
