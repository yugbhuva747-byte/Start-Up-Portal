<?php
/**
 * ============================================================================
 * includes/founder_home/raise-steps.php
 * Founder 3-Step Journey to Capital — Connected Pipeline Architecture
 * ============================================================================
 */
?>
<section class="py-20 md:py-28 bg-[#F8FAFC] border-t border-slate-200/80 relative overflow-hidden" id="founder-steps">

  <div class="max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- Section Header -->
    <div class="text-center max-w-3xl mx-auto mb-14 space-y-4">
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 border border-indigo-200/80 text-indigo-700 text-xs font-bold uppercase tracking-wider font-satoshi">
        <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
        <span>The 3-Step Capital Pipeline</span>
      </div>
      <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-[-0.035em] leading-tight font-satoshi">
        From Pitch to Bank Wire in 18 Days
      </h2>
      <p class="text-base sm:text-lg text-slate-500 font-normal leading-relaxed font-satoshi">
        Fundraising shouldn't pause your engineering sprints. Three automated milestones streamline the heavy lifting so you keep building.
      </p>
    </div>

    <!-- ==================================================================== -->
    <!-- CONNECTED PIPELINE ROADMAP STEPPER BAR (Desktop Visual Timeline)      -->
    <!-- ==================================================================== -->
    <div class="hidden lg:flex items-center justify-between max-w-4xl mx-auto mb-12 px-6 relative">
      <!-- Background Connecting Line -->
      <div class="absolute left-16 right-16 top-1/2 -translate-y-1/2 h-1 bg-slate-200 rounded-full z-0">
        <div class="h-full bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500 w-full rounded-full"></div>
      </div>

      <!-- Step Node 1 -->
      <div class="relative z-10 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-blue-600 text-white font-black text-sm flex items-center justify-center ring-4 ring-white shadow-md font-satoshi">
          01
        </div>
        <span class="mt-2 text-xs font-bold text-slate-900 font-satoshi">Day 01–02: Dossier Setup</span>
      </div>

      <!-- Step Node 2 -->
      <div class="relative z-10 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white font-black text-sm flex items-center justify-center ring-4 ring-white shadow-md font-satoshi">
          02
        </div>
        <span class="mt-2 text-xs font-bold text-slate-900 font-satoshi">Day 03–12: Mandate Match</span>
      </div>

      <!-- Step Node 3 -->
      <div class="relative z-10 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-black text-sm flex items-center justify-center ring-4 ring-white shadow-md font-satoshi">
          03
        </div>
        <span class="mt-2 text-xs font-bold text-slate-900 font-satoshi">Day 13–18: Wire &amp; Close</span>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- 3-STEP PIPELINE CARDS GRID                                           -->
    <!-- ==================================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">

      <!-- Step 01: Setup Dossier -->
      <div class="relative bg-white border border-slate-200/90 rounded-[28px] p-7 sm:p-8 flex flex-col justify-between shadow-xs hover:shadow-xl hover:border-blue-500 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-5">
            <span class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 text-blue-700 font-black text-xl flex items-center justify-center font-satoshi shadow-2xs">
              01
            </span>
            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200/60 text-xs font-bold font-satoshi">
              Day 01 &middot; 10 Mins Setup
            </span>
          </div>

          <h3 class="text-xl font-bold text-slate-900 mb-2.5 font-satoshi">
            Build Smart Deal Room
          </h3>

          <p class="text-slate-600 text-sm leading-relaxed font-satoshi">
            Upload your pitch deck, cap table, and traction metrics. NEXORA auto-generates a secure, institutional dossier protected by digital NDAs and real-time viewer tracking.
          </p>
        </div>

        <!-- Visual Micro-Component: Upload Status Chip -->
        <div class="mt-6 p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
          <div class="flex items-center justify-between text-[11px] font-bold text-slate-500">
            <span>Dossier Ingestion</span>
            <span class="text-blue-600 font-semibold font-mono">100% Synced</span>
          </div>
          <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-100 text-xs shadow-2xs">
            <span class="font-bold text-slate-800 flex items-center gap-1.5 font-satoshi truncate">
              <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
              PitchDeck_v4_Confidential.pdf
            </span>
            <span class="text-[10px] text-emerald-600 font-bold shrink-0">Uploaded</span>
          </div>
          <div class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-100 text-xs shadow-2xs">
            <span class="font-bold text-slate-800 flex items-center gap-1.5 font-satoshi truncate">
              <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
              SAFE_CapTable_Audited.xlsx
            </span>
            <span class="text-[10px] text-emerald-600 font-bold shrink-0">Synced</span>
          </div>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-semibold text-blue-600">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span>Auto-Watermarked &amp; NDA Bound</span>
        </div>
      </div>

      <!-- Step 02: Mandate Matching -->
      <div class="relative bg-white border border-slate-200/90 rounded-[28px] p-7 sm:p-8 flex flex-col justify-between shadow-xs hover:shadow-xl hover:border-indigo-500 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-5">
            <span class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-700 font-black text-xl flex items-center justify-center font-satoshi shadow-2xs">
              02
            </span>
            <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200/60 text-xs font-bold font-satoshi">
              Day 03–12 &middot; 48h Match
            </span>
          </div>

          <h3 class="text-xl font-bold text-slate-900 mb-2.5 font-satoshi">
            High-Conviction Matching
          </h3>

          <p class="text-slate-600 text-sm leading-relaxed font-satoshi">
            Our routing engine matches your round directly with active cheque-writers whose investment mandate fits your stage and sector. Zero spam, high conversion.
          </p>
        </div>

        <!-- Visual Micro-Component: Matching Radar Chip -->
        <div class="mt-6 p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-200/60 space-y-2">
          <div class="flex items-center justify-between text-[11px] font-bold text-indigo-900">
            <span>Mandate Match Radar</span>
            <span class="px-1.5 py-0.5 rounded bg-indigo-600 text-white text-[9.5px] font-bold">98% Fit Score</span>
          </div>
          <div class="p-2.5 rounded-xl bg-white border border-indigo-100 text-xs space-y-1.5 shadow-2xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-600 font-medium">Alerted Lead Funds</span>
              <span class="font-bold text-slate-900 font-mono">14 Lead VCs</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-600 font-medium">Intro Requests Booked</span>
              <span class="font-bold text-emerald-600 font-mono">4 Calls Scheduled</span>
            </div>
          </div>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-semibold text-indigo-600">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span>Direct Partner Introductions &amp; Meetings</span>
        </div>
      </div>

      <!-- Step 03: Close & Wire -->
      <div class="relative bg-white border border-slate-200/90 rounded-[28px] p-7 sm:p-8 flex flex-col justify-between shadow-xs hover:shadow-xl hover:border-emerald-500 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-5">
            <span class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 font-black text-xl flex items-center justify-center font-satoshi shadow-2xs">
              03
            </span>
            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60 text-xs font-bold font-satoshi">
              Day 13–18 &middot; Wire Closed
            </span>
          </div>

          <h3 class="text-xl font-bold text-slate-900 mb-2.5 font-satoshi">
            Close &amp; Wire Funds
          </h3>

          <p class="text-slate-600 text-sm leading-relaxed font-satoshi">
            Compare competitive term sheets, execute digital SAFE/CCPS agreements, issue cap table shares, and receive wires straight into your company escrow account.
          </p>
        </div>

        <!-- Visual Micro-Component: Wire Settlement Chip -->
        <div class="mt-6 p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-200/60 space-y-2">
          <div class="flex items-center justify-between text-[11px] font-bold text-emerald-900">
            <span>Capital Settlement</span>
            <span class="text-emerald-700 font-bold text-[10px] font-mono">Escrow Disbursed</span>
          </div>
          <div class="p-2.5 rounded-xl bg-white border border-emerald-100 text-xs space-y-1.5 shadow-2xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-600 font-medium">SAFE Agreement</span>
              <span class="font-bold text-slate-900">₹20 Cr Cap &middot; E-Signed</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-600 font-medium">Total Wire Settlement</span>
              <span class="font-bold text-emerald-600 font-mono">₹3.60 Cr Cleared</span>
            </div>
          </div>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center gap-2 text-xs font-semibold text-emerald-600">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span>Instant Escrow Capital Settlement</span>
        </div>
      </div>

    </div>

    <!-- Direct Step 1 Registration Callout (High Conversion Trigger) -->
    <div class="mt-14 p-6 sm:p-8 rounded-[26px] bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white shadow-xl shadow-blue-900/15 flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="space-y-1.5 text-center md:text-left">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-bold font-satoshi">
          <svg class="w-3.5 h-3.5 text-blue-200 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          <span>Step 1 Takes Only 3 Minutes &middot; 100% Free</span>
        </div>
        <h3 class="text-xl sm:text-2xl font-extrabold font-satoshi">
          Ready to kick off your 18-day raise sprint?
        </h3>
        <p class="text-xs sm:text-sm text-blue-100 max-w-xl font-satoshi">
          Register your company, upload your deck to your encrypted smart deal room, and start receiving partner mandate matches within 48 hours.
        </p>
      </div>

      <a href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>"
        class="shrink-0 px-8 py-3.5 rounded-full bg-white text-blue-700 hover:bg-blue-50 font-bold text-sm sm:text-base shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all font-satoshi inline-flex items-center gap-2">
        <span>Start Step 1: Register Startup</span>
        <span class="text-lg">&rarr;</span>
      </a>
    </div>

  </div>

</section>