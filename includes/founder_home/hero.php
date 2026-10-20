<?php
/**
 * ============================================================================
 * includes/founder_home/hero.php
 * Founder Hero Section — High-Impact Venture Acceleration Engine
 * ============================================================================
 */
?>
<section class="relative pt-32 pb-20 md:pt-40 md:pb-28 overflow-hidden bg-[#F8FAFC]" id="founder-hero">

  <!-- Ambient Luxury Glows -->
  <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 w-[700px] h-[450px] bg-gradient-to-b from-blue-500/12 via-indigo-500/6 to-transparent rounded-full blur-3xl"></div>
  <div class="pointer-events-none absolute top-40 right-[-100px] w-96 h-96 bg-blue-400/10 rounded-full blur-3xl"></div>
  <div class="pointer-events-none absolute top-60 left-[-100px] w-96 h-96 bg-emerald-400/8 rounded-full blur-3xl"></div>

  <div class="relative max-w-[1400px] mx-auto px-6 lg:px-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-14 items-center">

      <!-- Left Column: High-Conviction Value Proposition (Cols 7) -->
      <div class="lg:col-span-7 space-y-6">

        <!-- Live Founder Badge -->
        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-blue-50/90 border border-blue-200/80 shadow-2xs">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-500 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
          </span>
          <span class="text-xs font-bold tracking-wide uppercase text-blue-700 font-satoshi">
            For Ambitious Founders &middot; ₹240Cr+ Capital Deployed
          </span>
        </div>

        <!-- Main Headline -->
        <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-extrabold text-[#0F172A] tracking-[-0.04em] leading-[1.08] font-satoshi">
          Raise Capital Directly from <br class="hidden sm:inline" />
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700">
            Top Angels &amp; VCs.
          </span>
        </h1>

        <!-- Easy to read, strong subtitle -->
        <p class="text-lg sm:text-xl text-slate-600 font-normal leading-relaxed max-w-2xl font-satoshi">
          Skip endless cold emails, gatekeepers, and broker cuts. NEXORA gives you direct access to 450+ verified angel syndicates, family offices, and tier-1 venture funds actively writing cheques.
        </p>

        <!-- CTA Action Buttons -->
        <div class="pt-3 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
          <a href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>" 
             class="inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-full text-[15px] font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 shadow-lg shadow-blue-600/25 hover:shadow-xl hover:shadow-blue-600/35 hover:-translate-y-0.5 transition-all duration-200 font-satoshi">
            <span>Apply to Raise Capital</span>
            <span class="text-base">&rarr;</span>
          </a>

          <a href="#founder-flywheel" 
             class="inline-flex items-center justify-center gap-2 px-6 py-4 rounded-full text-[15px] font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 shadow-2xs hover:border-slate-300 transition-all font-satoshi">
            <span>How We Grow Your Business</span>
            <span class="text-slate-400">&darr;</span>
          </a>
        </div>

        <!-- Trust Badges Row -->
        <div class="pt-5 flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-slate-500 font-medium">
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Zero Brokerage / Keep 100% Equity
          </span>
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            18 Days Avg. to First Term Sheet
          </span>
          <span class="inline-flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            SEBI &amp; Delaware Standardized
          </span>
        </div>

      </div>

      <!-- Right Column: Interactive "Live Raise Room" Card (Cols 5) -->
      <div class="lg:col-span-5">
        <div class="relative bg-white border border-slate-200/90 rounded-[28px] p-6 sm:p-8 shadow-xl shadow-slate-200/50 space-y-6">

          <!-- Ambient Card Accent -->
          <div class="absolute -top-3 -right-3 px-3 py-1 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-full text-[11px] font-bold uppercase tracking-wider shadow-sm flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
            Live Deal Room
          </div>

          <!-- Startup Header Profile -->
          <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-extrabold text-xl shadow-md shadow-blue-500/20">
              FP
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-slate-900 font-satoshi">FinPulse AI</h3>
                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200/60">B2B SaaS</span>
              </div>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Automated B2B Treasury &middot; Seed Round</p>
            </div>
          </div>

          <!-- Live Capital Raised Tracker -->
          <div class="space-y-2.5">
            <div class="flex items-center justify-between text-xs font-semibold">
              <span class="text-slate-500">Round Progress</span>
              <span class="text-blue-700 font-bold">80% Committed</span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden p-0.5">
              <div class="h-full rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-500 w-[80%] transition-all duration-1000"></div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600">
              <span><strong>₹3.20 Cr</strong> Committed</span>
              <span>Target: <strong>₹4.00 Cr</strong></span>
            </div>
          </div>

          <!-- Incoming Term Sheets Alert -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Incoming Term Sheets</span>
              <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200/60">3 Offers</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">Peak XV Surge</span>
              <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">Blume Ventures</span>
              <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">+1 Angel</span>
            </div>
          </div>

          <!-- Fast Traction Metrics -->
          <div class="grid grid-cols-3 gap-2.5 pt-1 text-center">
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/60">
              <div class="text-[17px] font-extrabold text-slate-900 font-satoshi">₹28L</div>
              <div class="text-[10px] text-slate-500 font-medium mt-0.5">Current MRR</div>
            </div>
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/60">
              <div class="text-[17px] font-extrabold text-emerald-600 font-satoshi">+34%</div>
              <div class="text-[10px] text-slate-500 font-medium mt-0.5">MoM Growth</div>
            </div>
            <div class="p-3 rounded-xl bg-slate-50/70 border border-slate-200/60">
              <div class="text-[17px] font-extrabold text-blue-600 font-satoshi">14 Days</div>
              <div class="text-[10px] text-slate-500 font-medium mt-0.5">Round Closes</div>
            </div>
          </div>

          <!-- Bottom Action Button on Card -->
          <a href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>" 
             class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold text-center block transition-colors font-satoshi">
            Create Your Startup Dossier &rarr;
          </a>

        </div>
      </div>

    </div>
  </div>

</section>
