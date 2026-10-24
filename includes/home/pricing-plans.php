<?php
/**
 * ============================================================================
 * includes/home/pricing-plans.php
 * ----------------------------------------------------------------------------
 * SIMPLE, NEXT-LEVEL & 100% FULLY RESPONSIVE PRICING SECTION
 *
 * - Mobile-first design: Flawless on 320px mobile screens up to 4K monitors
 * - Founder Plan: ₹3,500/month (Keep 100% of your raise)
 * - Investor Pass: ₹2,500/month (Direct verified deal flow)
 * - Highlights: 0% Commission • Bank Escrow • 1-Click Cancel
 * ============================================================================
 */
$founder_url = function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder';
?>

<section id="nx-pricing" class="relative w-full py-14 sm:py-20 lg:py-28 bg-[#F8FAFC] border-t border-slate-200/80 overflow-hidden">

  <!-- Subtle ambient glow background -->
  <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[720px] max-w-full h-[360px] bg-gradient-to-b from-blue-100/50 via-emerald-100/30 to-transparent blur-3xl opacity-60"></div>
  </div>

  <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Header: Super Simple & Next-Level -->
    <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14 lg:mb-16">
      
      <!-- Pill Badge -->
      <div class="inline-flex items-center gap-2 px-3.5 sm:px-4 py-1 sm:py-1.5 rounded-full bg-white border border-slate-200 shadow-xs mb-4 sm:mb-5">
        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
        <span class="text-xs sm:text-sm font-semibold text-slate-700 tracking-wide">0% Commission • Transparent Monthly Pass</span>
      </div>

      <!-- Main Title: Crystal Clear in 3 Seconds -->
      <h2 class="text-2xl sm:text-4xl md:text-[46px] font-black tracking-tight leading-[1.18] sm:leading-[1.14] text-slate-900 font-sans">
        Simple flat pricing.
        <span class="block mt-1 sm:mt-1.5 text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700">
          Keep 100% of every Rupee you raise.
        </span>
      </h2>

      <!-- Plain English Subtitle -->
      <p class="mt-3 sm:mt-4 text-sm sm:text-base md:text-lg text-slate-600 leading-relaxed font-normal max-w-xl mx-auto">
        No hidden broker cuts. No percentage deductions from your funding. Just a simple monthly seat to connect, chat, and close deals directly.
      </p>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 items-stretch pt-2">

      <!-- ================= CARD 1: FOUNDER PLAN ================= -->
      <div class="relative rounded-2xl sm:rounded-3xl border-2 border-slate-900 bg-white p-5 sm:p-7 md:p-9 shadow-xl flex flex-col justify-between transition-all duration-300 hover:shadow-2xl">
        
        <!-- Top Floating Tag -->
        <span class="absolute -top-3.5 left-5 sm:left-9 px-3 sm:px-4 py-0.5 sm:py-1 rounded-full bg-slate-900 text-white text-[10px] sm:text-[11px] font-bold tracking-wider uppercase shadow-sm">
          FOR STARTUPS &amp; FOUNDERS
        </span>

        <div>
          <!-- Plan Header -->
          <div class="flex items-start gap-3.5 sm:gap-4 mb-4 sm:mb-5">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-xs">
              <!-- Rocket Icon -->
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.58-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
              </svg>
            </div>
            <div class="min-w-0">
              <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 tracking-tight truncate">Founder Raise Pass</h3>
              <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">Everything you need to showcase, connect &amp; get funded</p>
            </div>
          </div>

          <!-- Price Display -->
          <div class="flex flex-wrap sm:flex-nowrap items-baseline justify-between gap-2 pb-5 sm:pb-6 border-b border-slate-100 mt-2">
            <div class="flex items-baseline">
              <span class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight font-sans">₹3,500</span>
              <span class="text-slate-500 font-semibold text-xs sm:text-base ml-1.5 sm:ml-2">/ month</span>
            </div>
            <span class="px-2.5 sm:px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] sm:text-xs font-bold font-mono tracking-tight shrink-0">
              0% Success Fee
            </span>
          </div>

          <!-- Clear, Short, Easy Bullet Points -->
          <ul class="py-5 sm:py-6 space-y-3.5 sm:space-y-4 text-xs sm:text-sm text-slate-700 leading-normal sm:leading-relaxed">
            
            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Live Startup Discovery:</strong> Profile displayed directly to active angels &amp; family offices.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Verified Legal Trust Badge:</strong> Official green badge confirming MCA &amp; DPIIT company checks.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Protected Pitch &amp; Data Room:</strong> Share decks and cap tables safely with automated 1-click digital NDAs.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Direct In-Platform Dealroom:</strong> Chat 1-on-1 with investors, answer questions &amp; receive term sheets.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Keep 100% of Your Funding:</strong> 0% commission. If you raise ₹50 Lakhs, you get ₹50 Lakhs.
              </div>
            </li>

          </ul>
        </div>

        <!-- Action Button & Easy Subtext -->
        <div class="pt-2">
          <a href="<?= $founder_url ?>"
            class="group w-full py-3.5 sm:py-4 px-5 sm:px-6 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-center text-sm sm:text-base transition duration-200 shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer">
            <span>Get Started as Founder</span>
            <svg class="w-4 h-4 text-emerald-400 transition-transform group-hover:translate-x-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </a>
          <p class="text-center text-xs text-slate-500 font-medium mt-2.5 sm:mt-3">
            Pause or cancel anytime • No lock-in period
          </p>
        </div>

      </div>


      <!-- ================= CARD 2: INVESTOR PLAN ================= -->
      <div class="relative rounded-2xl sm:rounded-3xl border border-slate-200 bg-white p-5 sm:p-7 md:p-9 shadow-md flex flex-col justify-between transition-all duration-300 hover:shadow-xl hover:border-slate-300">
        
        <!-- Top Floating Tag -->
        <span class="absolute -top-3.5 left-5 sm:left-9 px-3 sm:px-4 py-0.5 sm:py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-[10px] sm:text-[11px] font-bold tracking-wider uppercase shadow-2xs">
          FOR ANGELS &amp; SYNDICATES
        </span>

        <div>
          <!-- Plan Header -->
          <div class="flex items-start gap-3.5 sm:gap-4 mb-4 sm:mb-5">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0 shadow-xs">
              <!-- Wallet Icon -->
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <div class="min-w-0">
              <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 tracking-tight truncate">Investor All-Access Pass</h3>
              <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">Direct allocations in high-growth, verified startups</p>
            </div>
          </div>

          <!-- Price Display -->
          <div class="flex flex-wrap sm:flex-nowrap items-baseline justify-between gap-2 pb-5 sm:pb-6 border-b border-slate-100 mt-2">
            <div class="flex items-baseline">
              <span class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight font-sans">₹2,500</span>
              <span class="text-slate-500 font-semibold text-xs sm:text-base ml-1.5 sm:ml-2">/ month</span>
            </div>
            <span class="px-2.5 sm:px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[11px] sm:text-xs font-bold font-mono tracking-tight shrink-0">
              Direct Allocations
            </span>
          </div>

          <!-- Clear, Short, Easy Bullet Points -->
          <ul class="py-5 sm:py-6 space-y-3.5 sm:space-y-4 text-xs sm:text-sm text-slate-700 leading-normal sm:leading-relaxed">
            
            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Vetted Startup Deal Flow:</strong> Browse pre-screened companies across SaaS, D2C, FinTech &amp; AI.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Audited Financials &amp; GST:</strong> Inspect verified cap tables, monthly revenue, and unit metrics.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Direct Founder Access:</strong> 1-click digital NDAs, private chat channels &amp; pitch scheduling.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Safe Bank Escrow Payouts:</strong> Protected custody routing with clear MCA SAFE share agreements.
              </div>
            </li>

            <li class="flex items-start gap-2.5 sm:gap-3">
              <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div>
                <strong class="font-bold text-slate-900">Zero Syndicate Markups:</strong> No extra platform surcharges on your investment amount.
              </div>
            </li>

          </ul>
        </div>

        <!-- Action Button & Easy Subtext -->
        <div class="pt-2">
          <button type="button" onclick="openSignUpChoiceModal()"
            class="group w-full py-3.5 sm:py-4 px-5 sm:px-6 rounded-xl bg-white hover:bg-slate-50 text-slate-900 border-2 border-slate-200 hover:border-slate-300 font-bold text-center text-sm sm:text-base transition duration-200 shadow-xs hover:shadow-md flex items-center justify-center gap-2 cursor-pointer">
            <span>Get Started as Investor</span>
            <svg class="w-4 h-4 text-slate-500 transition-transform group-hover:translate-x-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </button>
          <p class="text-center text-xs text-slate-500 font-medium mt-2.5 sm:mt-3">
            Includes verified investor badge &amp; priority deal alerts
          </p>
        </div>

      </div>

    </div>

    <!-- Bottom Feature Pills Strip: Next Level, Super Easy & Responsive -->
    <div class="mt-8 sm:mt-12 max-w-5xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
      
      <div class="rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 p-3.5 sm:p-4 shadow-xs flex items-center gap-3">
        <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100 font-bold text-xs sm:text-sm">
          0%
        </span>
        <div class="min-w-0">
          <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">Zero Commission</h4>
          <p class="text-[11px] sm:text-xs text-slate-500 truncate">100% of raise stays in your company</p>
        </div>
      </div>

      <div class="rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 p-3.5 sm:p-4 shadow-xs flex items-center gap-3">
        <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </span>
        <div class="min-w-0">
          <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">Bank Escrow Protection</h4>
          <p class="text-[11px] sm:text-xs text-slate-500 truncate">Safe release on valid share issue</p>
        </div>
      </div>

      <div class="rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 p-3.5 sm:p-4 shadow-xs flex items-center gap-3">
        <span class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0 border border-slate-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </span>
        <div class="min-w-0">
          <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate">Cancel in 1-Click</h4>
          <p class="text-[11px] sm:text-xs text-slate-500 truncate">No long lock-ins. Stop anytime</p>
        </div>
      </div>

    </div>

  </div>

</section>
