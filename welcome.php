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
        html, body {
            height: 100vh;
            max-height: 100vh;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        /* On very small mobile screens allow scroll if viewport is extremely short */
        @media (max-height: 600px), (max-width: 767px) {
            html, body {
                height: auto;
                max-height: none;
                overflow-y: auto;
            }
        }

        body {
            font-family: 'Satoshi', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            background-color: #FFFFFF;
            background-image: radial-gradient(circle at 50% -10%, rgba(224, 242, 254, 0.55) 0%, rgba(241, 245, 249, 0.35) 45%, rgba(255, 255, 255, 0) 75%);
            background-repeat: no-repeat;
            color: #0F172A;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .role-card {
            background-color: #FFFFFF;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .role-card:hover {
            border-color: #2563EB;
            box-shadow: 0 16px 32px -8px rgba(37, 99, 235, 0.18), 0 4px 12px -2px rgba(0, 0, 0, 0.04);
            transform: translateY(-3px);
        }

        .role-card:hover .arrow-icon {
            transform: translateX(4px);
            color: #2563EB;
        }

        .role-card:hover .illustration-box {
            transform: scale(1.02);
        }

        .role-card:active {
            transform: translateY(-1px) scale(0.995);
        }
    </style>
</head>
<body class="h-full flex flex-col justify-between selection:bg-blue-100 selection:text-blue-900">

    <!-- Top Minimalist Brand Bar -->
    <header class="w-full px-5 sm:px-10 lg:px-14 py-3 sm:py-4 flex items-center justify-between shrink-0">
        <a href="<?= url('index.php?choose=1') ?>" class="inline-flex items-center gap-2 group" aria-label="NEXORA Home">
            <span class="text-2xl font-black tracking-tight text-[#0F172A] font-satoshi">
                NEXORA
            </span>
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shadow-[0_0_10px_rgba(37,99,235,0.7)] animate-pulse"></span>
        </a>

        <!-- Right: Log In Button for Existing Clients / Members -->
        <div>
            <?php if (function_exists('auth_check') && auth_check()): 
                $u = current_user();
                $dash = $u['role'] === 'founder' ? url('founder/dashboard.php') : ($u['role'] === 'investor' ? url('investor/dashboard.php') : url('admin/dashboard.php'));
            ?>
                <a href="<?= $dash ?>" 
                   class="group inline-flex items-center gap-2 px-5 py-2 rounded-full text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all font-satoshi">
                    <span>Dashboard (<?= htmlspecialchars(ucfirst($u['role'])) ?>)</span>
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
            <?php else: ?>
                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline text-xs sm:text-sm text-slate-500 font-medium">Already have an account?</span>
                    <a href="<?= url('auth/login.php') ?>" 
                       class="group inline-flex items-center justify-center gap-2 px-5 py-2 rounded-full text-xs sm:text-sm font-black text-white bg-slate-900 hover:bg-blue-600 shadow-sm hover:shadow-md transition-all font-satoshi tracking-wide">
                        <span>Log In</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Center Hero (100% Full-Screen Viewport Fit, Zero Scroll) -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 sm:px-6 py-2 sm:py-3 max-w-5xl w-full mx-auto">
        
        <!-- Heading & Prompt -->
        <div class="text-center max-w-xl mx-auto mb-3 sm:mb-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200/80 mb-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                <span>Select Your Experience</span>
            </span>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-[#0F172A] tracking-[-0.03em] leading-tight font-satoshi">
                Welcome to NEXORA
            </h1>
            <p class="text-xs sm:text-sm md:text-base text-slate-500 font-medium mt-1 tracking-[-0.01em]">
                Choose how you want to experience NEXORA, or sign in to your portal
            </p>
        </div>

        <!-- 2 Original Side-by-Side Cards: Investor and Founder -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 w-full max-w-3xl mx-auto">
            
            <!-- Card 1: Investor -->
            <a href="<?= url('index.php?role=investor') ?>" 
               class="role-card bg-white border border-slate-200/90 rounded-[24px] p-5 sm:p-6 flex flex-col items-center text-center cursor-pointer group no-underline text-inherit shadow-[0_4px_16px_rgba(0,0,0,0.02)] justify-between"
               aria-label="I am an Investor">
                
                <!-- Illustration Box -->
                <div class="illustration-box w-full h-28 sm:h-32 rounded-[18px] bg-gradient-to-br from-[#FAFCFF] via-[#F0F6FF] to-[#F8FAFC] border border-slate-100 flex items-center justify-center relative overflow-hidden transition-all duration-300">
                    <svg class="w-16 h-16 sm:w-20 sm:h-20 text-slate-800 transition-transform duration-300 group-hover:scale-105" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="34" cy="24" r="11" stroke="#0F172A" />
                        <path d="M 14 62 C 14 46, 22 41, 34 41 C 41 41, 47 43, 50 47" stroke="#0F172A" />
                        <rect x="42" y="47" width="26" height="19" rx="3.5" stroke="#2563EB" fill="rgba(37, 99, 235, 0.08)" />
                        <path d="M 50 47 V 43 C 50 41.5, 60 41.5, 60 43 V 47" stroke="#2563EB" />
                        <line x1="55" y1="54" x2="55" y2="58" stroke="#2563EB" />
                    </svg>
                </div>

                <!-- Card Content -->
                <div class="mt-4 flex flex-col items-center w-full">
                    <span class="text-[10.5px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md mb-1.5 border border-emerald-200/70">Capital Allocator</span>
                    <h2 class="text-xl sm:text-2xl font-black text-[#0F172A] flex items-center gap-1 group-hover:text-blue-600 transition-colors font-satoshi">
                        <span>Investor</span>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-600 transition-all group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal leading-relaxed">
                        Discover curated dealflow, review pitch decks, and deploy capital
                    </p>
                    <span class="mt-3.5 w-full py-2.5 rounded-xl text-xs sm:text-sm font-bold text-emerald-700 bg-emerald-50 border border-emerald-200/80 group-hover:bg-emerald-600 group-hover:text-white transition-all text-center flex items-center justify-center gap-1.5 font-satoshi shadow-2xs">
                        <span>Explore as Investor</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </a>

            <!-- Card 2: Founder -->
            <a href="<?= url('index.php?role=founder') ?>" 
               class="role-card bg-white border border-slate-200/90 rounded-[24px] p-5 sm:p-6 flex flex-col items-center text-center cursor-pointer group no-underline text-inherit shadow-[0_4px_16px_rgba(0,0,0,0.02)] justify-between"
               aria-label="I am a Founder">
                
                <!-- Illustration Box -->
                <div class="illustration-box w-full h-28 sm:h-32 rounded-[18px] bg-gradient-to-br from-[#FAFCFF] via-[#F3F5FF] to-[#F8FAFC] border border-slate-100 flex items-center justify-center relative overflow-hidden transition-all duration-300">
                    <svg class="w-16 h-16 sm:w-20 sm:h-20 text-slate-800 transition-transform duration-300 group-hover:scale-105" viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="40" cy="24" r="11" stroke="#0F172A" />
                        <path d="M 18 62 C 18 47, 26 41, 40 41 C 54 41, 62 47, 62 62" stroke="#0F172A" />
                        <polygon points="30,55 33,42 47,42 50,55" stroke="#2563EB" fill="rgba(37, 99, 235, 0.08)" />
                        <line x1="24" y1="58" x2="56" y2="58" stroke="#2563EB" />
                    </svg>
                </div>

                <!-- Card Content -->
                <div class="mt-4 flex flex-col items-center w-full">
                    <span class="text-[10.5px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-md mb-1.5 border border-blue-200/70">Startup Founder</span>
                    <h2 class="text-xl sm:text-2xl font-black text-[#0F172A] flex items-center gap-1 group-hover:text-blue-600 transition-colors font-satoshi">
                        <span>Founder</span>
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-600 transition-all group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-normal leading-relaxed">
                        Showcase your venture, raise funding, and scale your business
                    </p>
                    <span class="mt-3.5 w-full py-2.5 rounded-xl text-xs sm:text-sm font-bold text-blue-700 bg-blue-50 border border-blue-200/80 group-hover:bg-blue-600 group-hover:text-white transition-all text-center flex items-center justify-center gap-1.5 font-satoshi shadow-2xs">
                        <span>Explore as Founder</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                        </svg>
                    </span>
                </div>
            </a>

        </div>

        <!-- Big Font Prominent Login Banner at Bottom (Single Unified Login with Email Role Detection) -->
        <div class="mt-4 sm:mt-5 w-full max-w-3xl mx-auto">
            <div class="px-5 sm:px-6 py-3 rounded-2xl bg-white/95 backdrop-blur-sm border-2 border-slate-200/90 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 hover:border-blue-500 transition-all">
                <div class="flex items-center gap-3.5 text-center sm:text-left">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50/80 text-blue-600 flex items-center justify-center shrink-0 border border-blue-200/90 shadow-xs">
                        <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="7.5" cy="15.5" r="4.5"/>
                            <path d="m21 3-9.5 9.5"/>
                            <path d="m15.5 7.5 3 3"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-base sm:text-lg font-black text-slate-900 font-satoshi leading-tight flex items-center gap-2">
                            <span>Already registered on NEXORA?</span>
                            <span class="hidden sm:inline-flex items-center gap-1.5 text-[10.5px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200/90 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Auto-Detects Role</span>
                            </span>
                        </div>
                        <div class="text-xs sm:text-sm text-slate-500 font-semibold mt-0.5">
                            Single login for Founders &amp; Investors with real-time email role detection.
                        </div>
                    </div>
                </div>
                <a href="<?= url('auth/login.php') ?>" 
                   class="group shrink-0 px-6 py-2.5 rounded-xl text-sm sm:text-base font-black text-white bg-slate-900 hover:bg-blue-600 shadow-md hover:shadow-lg transition-all font-satoshi flex items-center gap-2 whitespace-nowrap">
                    <span>Log In Now</span>
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

    </main>

    <!-- Bottom Minimal Footer Buffer -->
    <footer class="py-2 text-center text-[11px] text-slate-400 shrink-0">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved. &bull; SEBI &amp; MCA Diligence Ecosystem
    </footer>

</body>
</html>
