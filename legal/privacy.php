<?php
/**
 * Legal: Privacy Policy & Data Protection Disclosure
 * Compliant with the Digital Personal Data Protection (DPDP) Act, 2023 & IT Act Rules
 */
require_once __DIR__ . '/../config.php';
$pageTitle = 'Privacy Policy • ' . APP_NAME;
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
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
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
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-3">
                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                <span>DPDP Act 2023 Compliant</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">Privacy Policy & Personal Data Protection</h1>
            <p class="text-slate-500 text-sm sm:text-base mt-2">Last Updated: October 2026 • Transparent, institutional disclosure on KYC custody, cryptography, and investor data privacy.</p>
        </div>

        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-sm space-y-8 text-sm sm:text-base leading-relaxed text-slate-700">
            
            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                    <span>Information We Collect</span>
                </h2>
                <p>
                    We collect personal information necessary for regulatory compliance, investor accreditation, and entity due diligence, including: full legal name, email, phone number, PAN, government identity documents (Aadhaar XML via DigiLocker), corporate incorporation filings, and financial net worth declarations.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                    <span>Use & Legal Basis for Processing</span>
                </h2>
                <p>
                    Your data is processed strictly for: statutory KYC/AML screening, executing legally binding investment allotments, issuing digital share certificates, preventing financial fraud, and ensuring SEBI compliance for angel syndicates.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                    <span>Data Security & Cryptographic Storage</span>
                </h2>
                <p>
                    All identity records, statutory certificates, and sensitive credentials are encrypted at rest using AES-256 and transmitted exclusively over TLS 1.3. We implement strict role-based access control (RBAC), multi-factor authentication (2FA), and immutable audit logs.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">4</span>
                    <span>Your Rights under DPDP Act</span>
                </h2>
                <p>
                    You retain the right to request access to your stored personal data, correct inaccurate records, withdraw consent where permitted by statutory retention rules, and nominate a representative in accordance with the Digital Personal Data Protection Act, 2023.
                </p>
            </section>

        </div>

        <div class="mt-8 text-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved. Data Protection Officer: <code class="font-mono text-slate-600">privacy@portal.com</code>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
