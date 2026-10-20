<?php
/**
 * ============================================================================
 * NEXORA — Role Selection / Welcome Onboarding Screen
 * Clean, modern Upwork-inspired Persona Selector for First-Time Visitors
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Handle Role Selection via GET or POST
$selectedRole = $_GET['select_role'] ?? $_POST['role'] ?? null;
if ($selectedRole && in_array($selectedRole, ['investor', 'founder'], true)) {
    $_SESSION['portal_role'] = $selectedRole;
    setcookie('portal_role', $selectedRole, time() + (86400 * 30), '/');
    header('Location: ' . url('index.php?role=' . urlencode($selectedRole)));
    exit;
}

$pageTitle = 'Welcome to ' . APP_NAME . ' • Choose Your Experience';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Satoshi Typography System -->
    <link rel="stylesheet" href="<?= url('assets/css/fonts.css') ?>">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Satoshi', 'Inter', '-apple-system', 'sans-serif'],
                        satoshi: ['Satoshi', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        'nx-green': '#10B981',
                        'nx-dark': '#0F172A',
                    }
                }
            }
        };
    </script>

    <style>
        body {
            font-family: 'Satoshi', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            background-color: #F8FAFC;
            background-image: radial-gradient(circle at 50% -10%, rgba(37, 99, 235, 0.12) 0%, rgba(59, 130, 246, 0.05) 45%, rgba(248, 250, 252, 0) 75%);
            background-repeat: no-repeat;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .role-card {
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .role-card:hover {
            border-color: #2563EB;
            box-shadow: 0 22px 42px -12px rgba(37, 99, 235, 0.2), 0 8px 16px -8px rgba(15, 23, 42, 0.04);
            transform: translateY(-5px);
        }

        .role-card:hover .arrow-icon {
            transform: translateX(5px);
            color: #2563EB;
        }

        .role-card:hover .illustration-box {
            transform: scale(1.02);
            box-shadow: 0 12px 24px -8px rgba(37, 99, 235, 0.18);
        }

        .role-card:active {
            transform: translateY(-1px) scale(0.995);
        }
    </style>
</head>
<body class="min-h-full flex flex-col justify-between selection:bg-blue-100 selection:text-blue-900">

    <!-- Top Minimalist Brand Bar -->
    <header class="w-full px-6 sm:px-12 md:px-16 py-6 sm:py-8 flex items-center justify-between">
        <a href="<?= url('index.php?choose=1') ?>" class="inline-flex items-center gap-2 group" aria-label="NEXORA Home">
            <span class="text-2xl sm:text-[26px] font-black tracking-tight text-[#0F172A] font-satoshi">
                NEXORA
            </span>
            <span class="w-2 h-2 rounded-full bg-blue-600 shadow-[0_0_10px_rgba(37,99,235,0.6)]"></span>
        </a>

        <!-- Fast Skip / Direct Explore -->
        <a href="<?= url('index.php?role=investor') ?>" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-blue-600 transition-colors">
            <span>Explore Portal</span>
            <span>&rarr;</span>
        </a>
    </header>

    <!-- Main Content Center Hero -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 sm:px-6 py-6 sm:py-10">
        
        <!-- Heading & Prompt -->
        <div class="text-center max-w-xl mx-auto mb-8 sm:mb-12">
            <h1 class="text-3xl sm:text-4xl md:text-[46px] font-extrabold text-[#0F172A] tracking-[-0.035em] leading-tight font-satoshi">
                Welcome to NEXORA
            </h1>
            <p class="text-base sm:text-[18px] text-slate-500 font-medium mt-3 tracking-[-0.01em]">
                Which describes you best?
            </p>
        </div>

        <!-- Role Cards Grid -->
        <div class="flex flex-col sm:flex-row gap-6 sm:gap-8 justify-center items-center w-full max-w-3xl mx-auto">
            
            <!-- Card 1: Investor -->
            <a href="<?= url('index.php?role=investor') ?>" 
               class="role-card w-full sm:w-[320px] bg-white border border-[#E2E8F0] rounded-[28px] p-6 sm:p-7 flex flex-col items-center text-center cursor-pointer group no-underline text-inherit shadow-xs"
               aria-label="I am an Investor">
                
                <!-- Signature Sapphire/Blue Gradient Illustration Box -->
                <div class="illustration-box w-full aspect-square rounded-[22px] bg-gradient-to-br from-[#EFF6FF] via-[#DBEAFE] to-[#EEF2FF] border border-blue-100/80 flex items-center justify-center relative overflow-hidden transition-all duration-300">
                    <!-- Clean Line-Art Person with Briefcase -->
                    <svg class="w-28 h-28 text-slate-800 transition-transform duration-300 group-hover:scale-105" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <!-- Head -->
                        <circle cx="34" cy="24" r="11" stroke="#0F172A" />
                        <!-- Shoulders / Torso -->
                        <path d="M 14 62 C 14 46, 22 41, 34 41 C 41 41, 47 43, 50 47" stroke="#0F172A" />
                        <!-- Briefcase Container (NEXORA Blue Accent) -->
                        <rect x="42" y="47" width="26" height="19" rx="3.5" stroke="#2563EB" fill="rgba(37, 99, 235, 0.08)" />
                        <!-- Briefcase Handle -->
                        <path d="M 50 47 V 43 C 50 41.5, 60 41.5, 60 43 V 47" stroke="#2563EB" />
                        <!-- Briefcase Clasp Line -->
                        <line x1="55" y1="54" x2="55" y2="58" stroke="#2563EB" />
                    </svg>
                </div>

                <!-- Card Content -->
                <div class="mt-6 flex flex-col items-center">
                    <h2 class="text-xl sm:text-[22px] font-bold text-[#0F172A] flex items-center gap-1.5 group-hover:text-blue-600 transition-colors">
                        <span>Investor</span>
                        <span class="arrow-icon transition-transform duration-200 text-slate-400 group-hover:text-blue-600">&rarr;</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-1.5 font-normal leading-relaxed">
                        Discover deals and deploy capital
                    </p>
                </div>
            </a>

            <!-- Card 2: Founder -->
            <a href="<?= url('index.php?role=founder') ?>" 
               class="role-card w-full sm:w-[320px] bg-white border border-[#E2E8F0] rounded-[28px] p-6 sm:p-7 flex flex-col items-center text-center cursor-pointer group no-underline text-inherit shadow-xs"
               aria-label="I am a Founder">
                
                <!-- Signature Indigo/Cobalt Gradient Illustration Box -->
                <div class="illustration-box w-full aspect-square rounded-[22px] bg-gradient-to-br from-[#EEF2FF] via-[#E0E7FF] to-[#EFF6FF] border border-indigo-100/80 flex items-center justify-center relative overflow-hidden transition-all duration-300">
                    <!-- Clean Line-Art Person with Laptop -->
                    <svg class="w-28 h-28 text-slate-800 transition-transform duration-300 group-hover:scale-105" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <!-- Head -->
                        <circle cx="40" cy="24" r="11" stroke="#0F172A" />
                        <!-- Shoulders / Torso -->
                        <path d="M 18 62 C 18 47, 26 41, 40 41 C 54 41, 62 47, 62 62" stroke="#0F172A" />
                        <!-- Laptop Screen (NEXORA Blue Accent) -->
                        <polygon points="30,55 33,42 47,42 50,55" stroke="#2563EB" fill="rgba(37, 99, 235, 0.08)" />
                        <!-- Laptop Base -->
                        <line x1="24" y1="58" x2="56" y2="58" stroke="#2563EB" />
                    </svg>
                </div>

                <!-- Card Content -->
                <div class="mt-6 flex flex-col items-center">
                    <h2 class="text-xl sm:text-[22px] font-bold text-[#0F172A] flex items-center gap-1.5 group-hover:text-blue-600 transition-colors">
                        <span>Founder</span>
                        <span class="arrow-icon transition-transform duration-200 text-slate-400 group-hover:text-blue-600">&rarr;</span>
                    </h2>
                    <p class="text-sm text-slate-500 mt-1.5 font-normal leading-relaxed">
                        Raise capital and scale your startup
                    </p>
                </div>
            </a>

        </div>

        <!-- Bottom Log In Link -->
        <div class="text-center text-[15px] text-slate-500 mt-12 sm:mt-16">
            <span>Already have an account? </span>
            <a href="<?= url('auth/login.php') ?>" class="text-blue-600 font-semibold hover:text-blue-700 hover:underline transition-colors">
                Log in
            </a>
        </div>

    </main>

    <!-- Bottom Spacing / Footer Buffer -->
    <footer class="py-4 text-center text-xs text-slate-400">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.
    </footer>

</body>
</html>
