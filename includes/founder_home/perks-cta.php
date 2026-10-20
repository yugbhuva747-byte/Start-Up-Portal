<?php
/**
 * ============================================================================
 * includes/founder_home/perks-cta.php
 * Founder Proof Metrics, Perks Overview & Grand Final CTA Section
 * ============================================================================
 */
?>
<section class="py-20 md:py-28 bg-white border-t border-slate-100 relative overflow-hidden" id="founder-cta">

  <div class="max-w-[1400px] mx-auto px-6 lg:px-12">

    <!-- Proof Metrics Stats Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20">

      <div class="p-6 sm:p-8 rounded-[24px] bg-[#F8FAFC] border border-slate-200/80 text-center">
        <div class="text-3xl sm:text-4xl lg:text-5xl font-black text-blue-600 font-satoshi tracking-tight">18 Days</div>
        <div class="text-xs sm:text-sm font-semibold text-slate-700 mt-2 font-satoshi">Avg. Time to Term Sheet</div>
        <div class="text-[11px] text-slate-400 mt-0.5">vs. 6 months traditional</div>
      </div>

      <div class="p-6 sm:p-8 rounded-[24px] bg-[#F8FAFC] border border-slate-200/80 text-center">
        <div class="text-3xl sm:text-4xl lg:text-5xl font-black text-indigo-600 font-satoshi tracking-tight">₹240Cr+
        </div>
        <div class="text-xs sm:text-sm font-semibold text-slate-700 mt-2 font-satoshi">Total Capital Deployed</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Across India &amp; UAE Deals</div>
      </div>

      <div class="p-6 sm:p-8 rounded-[24px] bg-[#F8FAFC] border border-slate-200/80 text-center">
        <div class="text-3xl sm:text-4xl lg:text-5xl font-black text-emerald-600 font-satoshi tracking-tight">84%</div>
        <div class="text-xs sm:text-sm font-semibold text-slate-700 mt-2 font-satoshi">Funding Success Rate</div>
        <div class="text-[11px] text-slate-400 mt-0.5">For accepted founder cohorts</div>
      </div>

      <div class="p-6 sm:p-8 rounded-[24px] bg-[#F8FAFC] border border-slate-200/80 text-center">
        <div class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 font-satoshi tracking-tight">0%</div>
        <div class="text-xs sm:text-sm font-semibold text-slate-700 mt-2 font-satoshi">Brokerage &amp; Hidden Fees</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Keep 100% of your equity</div>
      </div>

    </div>

    <!-- Grand Final CTA Banner Card -->
    <div
      class="relative bg-gradient-to-br from-[#0A0F1D] via-[#0F172A] to-[#1E293B] border border-slate-800 rounded-[32px] p-8 sm:p-14 lg:p-16 text-center text-white overflow-hidden shadow-2xl shadow-slate-950/20">

      <!-- Subtle Ambient Glow Inside Banner -->
      <div
        class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-blue-500/20 rounded-full blur-[120px]">
      </div>
      <div
        class="pointer-events-none absolute -bottom-20 right-10 w-[350px] h-[250px] bg-indigo-500/15 rounded-full blur-[100px]">
      </div>

      <div class="relative max-w-2xl mx-auto space-y-6">

        <span
          class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/15 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-wider font-satoshi">
          🔥 Applications Open for Current Cohort
        </span>

        <h2
          class="text-3xl sm:text-4xl lg:text-[46px] font-extrabold tracking-[-0.035em] leading-tight text-white font-satoshi">
          Ready to Build the Next <br class="hidden sm:inline" />
          Market-Leading Startup?
        </h2>

        <p class="text-slate-300 text-base sm:text-lg font-normal leading-relaxed font-satoshi">
          Join 1,200+ founders raising capital on founder-friendly terms. Create your confidential smart dossier in 10
          minutes and get matched with active cheque-writers.
        </p>

        <!-- CTA Buttons Row -->
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
          <a href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-9 py-4 rounded-full text-base font-bold text-white bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all font-satoshi">
            <span>Apply to Raise Capital</span>
            <span class="text-lg">&rarr;</span>
          </a>

          <a href="<?= function_exists('url') ? url('welcome.php') : 'welcome.php' ?>"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-4 rounded-full text-base font-semibold text-slate-300 hover:text-white bg-white/10 hover:bg-white/15 border border-white/15 transition-all font-satoshi">
            <span>Switch Role (⇄)</span>
          </a>
        </div>

        <!-- Fast Reassurances -->
        <p class="text-xs text-slate-400 pt-2 font-medium">
          Free to apply &middot; Non-disclosure agreement guaranteed &middot; Verified accredited allocators only
        </p>

      </div>

    </div>

  </div>

</section>