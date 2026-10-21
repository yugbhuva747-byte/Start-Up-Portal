<?php
/**
 * ============================================================================
 * includes/founder_home/hero.php
 * Founder Hero Section — Cinematic Bento Command Deck & Raise Engine
 * ============================================================================
 */
?>
<section class="relative pt-32 pb-20 md:pt-36 md:pb-24 overflow-hidden bg-[#F8FAFC]" id="founder-hero">

  <!-- Ambient Luxury Glows & High-Tech Blueprint Background Grid -->
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:24px_24px] opacity-70"></div>
  <div class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[1000px] h-[550px] bg-gradient-to-b from-blue-500/18 via-indigo-500/10 to-transparent rounded-full blur-3xl"></div>
  <div class="pointer-events-none absolute top-1/3 -left-40 w-96 h-96 bg-blue-400/12 rounded-full blur-3xl"></div>
  <div class="pointer-events-none absolute top-1/2 -right-40 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl"></div>

  <div class="relative max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- ==================================================================== -->
    <!-- TOP CENTERED COMMAND HEADER: Mega-Headline & High-Conviction Pitch -->
    <!-- ==================================================================== -->
    <div class="text-center max-w-4xl mx-auto space-y-6 mb-12 lg:mb-14">

      <!-- Live Network Eyebrow Pill -->
      <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-blue-50/90 border border-blue-200/80 shadow-xs">
        <span class="flex h-2.5 w-2.5 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-500 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-600"></span>
        </span>
        <span class="text-xs font-bold tracking-wide uppercase text-blue-700 font-satoshi">
          NEXORA VENTURE ENGINE &middot; ₹280 CR+ DEPLOYED &middot; 0% BROKERAGE
        </span>
      </div>

      <!-- Mega Headline with High-Impact Gradient -->
      <h1 class="text-4xl sm:text-5xl lg:text-[64px] font-extrabold text-[#0F172A] tracking-[-0.04em] leading-[1.03] font-satoshi">
        Get Your Startup Funded.<br class="hidden sm:inline" />
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600">
          Connect with 450+ Verified Cheque-Writers.
        </span>
      </h1>

      <!-- Short, powerful, punchy subtitle -->
      <p class="text-lg sm:text-xl text-slate-600 font-normal leading-relaxed max-w-2xl mx-auto font-satoshi">
        Stop wasting 6 months sending cold emails with zero replies. Register your startup in 5 minutes, get discovered by lead angel syndicates and Tier-1 VCs, and close on 0% commission.
      </p>

      <!-- Instant 1-Click Founder Registration Form (High Conversion Engine) -->
      <form action="<?= function_exists('url') ? url('auth/register.php') : 'auth/register.php' ?>" method="GET" class="max-w-xl mx-auto w-full pt-1">
        <input type="hidden" name="role" value="founder">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-1.5 sm:p-2 rounded-2xl sm:rounded-full bg-white border-2 border-blue-500/40 shadow-xl shadow-blue-600/15 focus-within:border-blue-600 transition-all">
          <div class="flex items-center gap-3 px-4 py-2 sm:py-0 flex-1">
            <svg class="w-5 h-5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <input type="email" name="email" required placeholder="Enter work email (e.g. founder@startup.com)" class="w-full text-sm sm:text-[15px] text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none font-satoshi">
          </div>
          <button type="submit" class="px-7 py-3.5 rounded-xl sm:rounded-full text-sm sm:text-[15px] font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 shadow-md shadow-blue-600/30 font-satoshi transition-all flex items-center justify-center gap-2 shrink-0">
            <span>Register Startup Free</span>
            <span class="text-base">&rarr;</span>
          </button>
        </div>
      </form>

      <!-- Existing User Prompt -->
      <div class="text-xs text-slate-500 font-medium">
        Already registered? 
        <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="font-bold text-blue-600 hover:text-blue-800 underline transition">
          Sign In to Portal &rarr;
        </a>
      </div>

      <!-- Social Proof Avatars & Trust Row -->
      <div class="pt-2 flex flex-wrap items-center justify-center gap-y-3 gap-x-8 text-xs text-slate-600 font-medium">
        <div class="flex items-center gap-2">
          <div class="flex -space-x-2">
            <div class="w-7 h-7 rounded-full bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">AK</div>
            <div class="w-7 h-7 rounded-full bg-indigo-600 text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">SJ</div>
            <div class="w-7 h-7 rounded-full bg-emerald-600 text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">RM</div>
            <div class="w-7 h-7 rounded-full bg-slate-900 text-white font-bold text-[9px] flex items-center justify-center ring-2 ring-white">+180</div>
          </div>
          <span class="font-bold text-slate-900 font-satoshi">180+ Rounds Funded</span>
        </div>

        <span class="hidden sm:inline text-slate-300">&bull;</span>
        <span class="text-emerald-700 font-bold flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 100% Free to Apply &middot; 0% Commission
        </span>

        <span class="hidden sm:inline text-slate-300">&bull;</span>
        <span class="text-blue-700 font-bold">
          Avg. 18 Days from Pitch to Wire
        </span>
      </div>

    </div>

    <!-- ==================================================================== -->
    <!-- CINEMATIC BENTO COMMAND CONSOLE (Full-Width Interactive Layout)      -->
    <!-- ==================================================================== -->
    <div class="relative rounded-[32px] p-[1.5px] bg-gradient-to-b from-blue-500/35 via-indigo-500/20 to-slate-300/40 shadow-2xl shadow-blue-950/10">
      <div class="bg-white rounded-[30.5px] p-5 sm:p-7 lg:p-8 space-y-6">

        <!-- Console Chrome Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
              <span class="w-3 h-3 rounded-full bg-rose-500"></span>
              <span class="w-3 h-3 rounded-full bg-amber-500"></span>
              <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
            </div>
            <div class="h-4 w-px bg-slate-200"></div>
            <span class="text-xs font-mono font-bold text-slate-800 tracking-wider">NEXORA // FOUNDER PRIVATE TERMINAL v2.8</span>
          </div>

          <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/80 text-emerald-700 text-xs font-bold font-mono">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              450+ CHEQUE-WRITERS ONLINE
            </span>
            <span class="text-xs font-mono text-slate-400 hidden md:inline">256-BIT ENCRYPTION</span>
          </div>
        </div>

        <!-- Bento 3-Column Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">

          <!-- BENTO COL 1: Live Interactive Raise Simulator (Cols 5) -->
          <div class="lg:col-span-5 bg-gradient-to-br from-[#0F172A] to-slate-900 rounded-[24px] p-6 text-white flex flex-col justify-between shadow-xl relative overflow-hidden space-y-5">
            <!-- Background glow -->
            <div class="pointer-events-none absolute -top-10 -right-10 w-40 h-40 bg-blue-500/25 rounded-full blur-2xl"></div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-mono uppercase tracking-wider text-blue-400 font-bold">ROUND SIMULATOR</span>
                <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 text-[10px] font-mono font-bold border border-blue-400/30">SEED TO SERIES A</span>
              </div>

              <div>
                <div class="text-xs text-slate-400 font-medium">Select Capital Target:</div>
                <div class="flex items-baseline justify-between mt-1">
                  <div id="simDisplayAmount" class="text-3xl font-extrabold font-mono text-white tracking-tight">₹4.00 Cr</div>
                  <span id="simDisplayUSD" class="text-xs font-mono text-slate-400">~$480K USD</span>
                </div>
              </div>

              <!-- Interactive Slider -->
              <div class="space-y-1.5 pt-1">
                <input type="range" min="1" max="10" step="0.5" value="4" id="simSlider"
                       oninput="handleRaiseSimulation(this.value)"
                       class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-blue-500">
                <div class="flex justify-between text-[10px] font-mono text-slate-400">
                  <span>₹1.00 Cr</span>
                  <span>₹5.00 Cr</span>
                  <span>₹10.00 Cr</span>
                </div>
              </div>

              <!-- Live Calculated Outputs -->
              <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-800 text-center">
                <div class="p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60">
                  <div id="simMatchedVCs" class="text-sm font-bold text-emerald-400 font-mono">62 VCs</div>
                  <div class="text-[10px] text-slate-400">Matching Mandates</div>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60">
                  <div id="simEstDilution" class="text-sm font-bold text-cyan-400 font-mono">11.2%</div>
                  <div class="text-[10px] text-slate-400">Target Dilution</div>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60">
                  <div id="simCloseDays" class="text-sm font-bold text-indigo-400 font-mono">16 Days</div>
                  <div class="text-[10px] text-slate-400">Avg. Wire Time</div>
                </div>
              </div>
            </div>

            <!-- Wire Progress & Launch Button -->
            <div class="space-y-3 pt-3 border-t border-slate-800">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">Syndicate Allocation Status</span>
                <span class="text-emerald-400 font-bold font-mono">90% Committed</span>
              </div>
              <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                <div id="simProgressBar" class="h-full rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-400 w-[90%] transition-all duration-300"></div>
              </div>
              <a href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>"
                class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold font-satoshi text-center block transition-all shadow-lg shadow-blue-600/30">
                Launch Deal Room for <span id="simBtnAmount">₹4.00 Cr</span> &rarr;
              </a>
            </div>
          </div>

          <!-- BENTO COL 2: Smart Watermarked Data Room Vault (Cols 4) -->
          <div class="lg:col-span-4 bg-slate-50 border border-slate-200/90 rounded-[24px] p-6 flex flex-col justify-between space-y-4">
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 font-satoshi flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                  <span>Smart Data Room</span>
                </span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200/60">Auto-NDA Active</span>
              </div>
              <p class="text-xs text-slate-500 mb-4">Bank-grade watermarked documents distributed strictly to verified partners upon digital signature.</p>

              <!-- Documents Stack -->
              <div class="space-y-2.5">
                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center justify-between">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-[10px] shrink-0">PDF</span>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-slate-800 truncate font-satoshi">PitchDeck_v4_Confidential.pdf</div>
                      <div class="text-[10px] text-slate-400">Watermarked ID #8492 &middot; 24 Slides</div>
                    </div>
                  </div>
                  <span class="text-[9.5px] px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200/60 shrink-0">94% Read</span>
                </div>

                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center justify-between">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-[10px] shrink-0">XLS</span>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-slate-800 truncate font-satoshi">CapTable_SAFE_Standardized.xlsx</div>
                      <div class="text-[10px] text-slate-400">Delaware &amp; SEBI Compliant Clean Equity</div>
                    </div>
                  </div>
                  <span class="text-[9.5px] px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold border border-blue-200/60 shrink-0">Audited</span>
                </div>

                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs flex items-center justify-between">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-[10px] shrink-0">MCA</span>
                    <div class="min-w-0">
                      <div class="text-xs font-bold text-slate-800 truncate font-satoshi">MCA_Verified_Financial_Ledger.pdf</div>
                      <div class="text-[10px] text-slate-400">DigiLocker &amp; GST Sync &middot; Zero Fraud</div>
                    </div>
                  </div>
                  <span class="text-[9.5px] px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200/60 shrink-0">Verified</span>
                </div>
              </div>
            </div>

            <!-- Real-Time Telemetry Bar -->
            <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200/70 text-emerald-800 flex items-center justify-between text-xs">
              <span class="flex items-center gap-1.5 font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                14 Allocators currently reading
              </span>
              <span class="text-[10.5px] font-semibold text-emerald-700">Avg. 4m 12s on deck</span>
            </div>
          </div>

          <!-- BENTO COL 3: Live Term Sheets & Allocators (Cols 3) -->
          <div class="lg:col-span-3 bg-slate-50 border border-slate-200/90 rounded-[24px] p-6 flex flex-col justify-between space-y-4">
            <div>
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 font-satoshi flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                  <span>Active Term Sheets</span>
                </span>
                <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold">3 Offers</span>
              </div>
              <p class="text-xs text-slate-500 mb-4">Direct partner connections without intermediary fees.</p>

              <!-- Investor Match Mini Cards -->
              <div class="space-y-2.5">
                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs space-y-1">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-900 font-satoshi">Peak XV Surge</span>
                    <span class="text-[10px] font-bold text-emerald-600">₹2.0 Cr Lead</span>
                  </div>
                  <div class="text-[10px] text-slate-400">Term Sheet Executed &middot; 48h SLA</div>
                </div>

                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs space-y-1">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-900 font-satoshi">Blume Syndicate</span>
                    <span class="text-[10px] font-bold text-blue-600">₹1.0 Cr Co-Lead</span>
                  </div>
                  <div class="text-[10px] text-slate-400">Due Diligence Completed</div>
                </div>

                <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-2xs space-y-1">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-900 font-satoshi">Aarav S. (Angel)</span>
                    <span class="text-[10px] font-bold text-indigo-600">₹60L Ticket</span>
                  </div>
                  <div class="text-[10px] text-slate-400">Wired to Escrow</div>
                </div>
              </div>
            </div>

            <div class="text-center pt-2 border-t border-slate-200/70 text-[11px] text-slate-500 font-medium">
              +448 Other Verified VCs in Network
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>

  <!-- Real-Time Venture Activity Ticker Strip (Dynamic Marquee) -->
  <div class="mt-14 pt-6 border-t border-slate-200/70 bg-white/70 backdrop-blur-sm">
    <div class="max-w-[1440px] mx-auto px-6 lg:px-10">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        
        <div class="flex items-center gap-2 shrink-0">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-500 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
          </span>
          <span class="text-xs font-bold uppercase tracking-wider text-slate-800 font-satoshi">Live Round Activity:</span>
        </div>

        <div class="w-full overflow-hidden relative">
          <div class="flex items-center gap-8 text-xs font-semibold text-slate-600 whitespace-nowrap animate-marquee">
            <span class="inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <strong>CloudScale AI</strong> closed <span class="text-blue-700 font-bold">₹6.5 Cr</span> (Seed) from Blume Syndicate &middot; 24m ago
            </span>
            <span class="text-slate-300">&bull;</span>
            <span class="inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <strong>MedFlow Systems</strong> received <span class="text-emerald-700 font-bold">3 Term Sheets</span> &middot; 1h ago
            </span>
            <span class="text-slate-300">&bull;</span>
            <span class="inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <strong>OmniLogistics</strong> Seed Round <span class="text-indigo-700 font-bold">100% Subscribed</span> (₹4 Cr) &middot; 2h ago
            </span>
            <span class="text-slate-300">&bull;</span>
            <span class="inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              <strong>Aarav S.</strong> wired <span class="text-blue-700 font-bold">₹1.25 Cr</span> via Escrow &middot; 3h ago
            </span>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Interactive JavaScript for Console Simulation -->
  <script>
    function handleRaiseSimulation(val) {
      const amount = parseFloat(val);
      const displayAmount = '₹' + amount.toFixed(2) + ' Cr';
      const usdAmount = '~$' + Math.round((amount * 10000000) / 83.5 / 1000) + 'K USD';

      const elDisplay = document.getElementById('simDisplayAmount');
      const elUSD = document.getElementById('simDisplayUSD');
      const elMatched = document.getElementById('simMatchedVCs');
      const elDilution = document.getElementById('simEstDilution');
      const elDays = document.getElementById('simCloseDays');
      const elBtn = document.getElementById('simBtnAmount');

      if (elDisplay) elDisplay.innerText = displayAmount;
      if (elUSD) elUSD.innerText = usdAmount;
      if (elBtn) elBtn.innerText = displayAmount;

      const matchedCount = Math.round(amount * 12 + 14);
      const dilutionRate = (amount * 2.8).toFixed(1) + '%';
      const daysCount = Math.round(12 + (amount * 1.2)) + ' Days';

      if (elMatched) elMatched.innerText = matchedCount + ' VCs';
      if (elDilution) elDilution.innerText = dilutionRate;
      if (elDays) elDays.innerText = daysCount;
    }
  </script>

</section>