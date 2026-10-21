<?php
/**
 * ============================================================================
 * includes/investor_home/dealflow-pillars.php
 * Institutional Dealflow Matrix — Financial Tear-Sheet Directory
 * Unified with NEXORA Royal Blue & Deep Slate brand theme.
 * Focus: High Trust, Verified Companies & Meaningful Institutional Transparency.
 * ============================================================================
 */
?>
<section class="py-20 md:py-28 bg-white border-t border-slate-200/80 relative overflow-hidden" id="investor-matrix">

  <div class="max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- Section Header: Institutional Trust Tone -->
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
      <div class="space-y-3 max-w-2xl">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs font-bold font-satoshi uppercase tracking-wider">
          <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
          </svg>
          <span>Curated Dealflow Directory</span>
        </div>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-[-0.035em] leading-tight font-satoshi">
          Curated Startups Raising Right Now
        </h2>
        <p class="text-base text-slate-600 font-normal leading-relaxed font-satoshi">
          Out of 2,400+ founder applications, only 43 made it past our institutional diligence shield. Explore companies with audited traction, verified founders, and lead co-investors.
        </p>
      </div>

      <!-- Live Pipeline Counter Badge -->
      <div class="p-4 rounded-2xl bg-[#F8FAFC] border border-slate-200/90 text-right shrink-0">
        <div class="text-xs text-slate-500 font-medium font-satoshi">Total Diligence Volume</div>
        <div class="text-2xl font-black text-slate-900 font-mono">₹48.5 Cr</div>
        <div class="text-[11px] text-blue-700 font-bold mt-0.5 flex items-center justify-end gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> 24 Verified Syndicate Rounds
        </div>
      </div>
    </div>

    <!-- Interactive Sector Filter Bar -->
    <div class="flex items-center gap-2 overflow-x-auto pb-4 mb-8 text-xs font-bold font-satoshi no-scrollbar">
      <button type="button" onclick="filterDealMatrix('all', this)" class="deal-filter-btn active px-4 py-2 rounded-xl bg-blue-600 text-white shadow-xs transition shrink-0">
        All Verified Rounds (24)
      </button>
      <button type="button" onclick="filterDealMatrix('saas', this)" class="deal-filter-btn px-4 py-2 rounded-xl bg-[#F8FAFC] border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition shrink-0">
        Enterprise AI &amp; SaaS (9)
      </button>
      <button type="button" onclick="filterDealMatrix('fintech', this)" class="deal-filter-btn px-4 py-2 rounded-xl bg-[#F8FAFC] border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition shrink-0">
        FinTech &amp; B2B Payments (6)
      </button>
      <button type="button" onclick="filterDealMatrix('deeptech', this)" class="deal-filter-btn px-4 py-2 rounded-xl bg-[#F8FAFC] border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition shrink-0">
        DeepTech &amp; Robotics (5)
      </button>
      <button type="button" onclick="filterDealMatrix('climate', this)" class="deal-filter-btn px-4 py-2 rounded-xl bg-[#F8FAFC] border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition shrink-0">
        CleanTech &amp; EV (4)
      </button>
    </div>

    <!-- ==================================================================== -->
    <!-- 3-COLUMN FINANCIAL TEAR-SHEET CARDS GRID                             -->
    <!-- ==================================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8" id="dealMatrixGrid">

      <!-- TEAR SHEET 1: CloudScale AI -->
      <div class="bg-[#F8FAFC] border border-slate-200/90 rounded-[26px] p-6 flex flex-col justify-between hover:border-blue-500 hover:shadow-xl hover:shadow-blue-950/5 transition-all duration-300 space-y-5">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-2xl bg-blue-600 text-white font-black text-sm flex items-center justify-center font-satoshi shadow-md shadow-blue-600/20">
              CS
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 font-satoshi">Enterprise SaaS</span>
              <span class="px-2 py-1 rounded-md text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">78% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-xl font-bold text-slate-900 font-satoshi">CloudScale AI</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Autonomous enterprise cloud spend optimization engine with multi-cloud Kubernetes telemetry.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3 p-2 rounded-xl bg-white border border-slate-200/80 flex items-center gap-2 text-[10.5px] font-mono text-slate-700">
            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">MCA Incorporation &amp; GST P&amp;L Reconciled</span>
          </div>

          <!-- Financial Metrics Box -->
          <div class="mt-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[9.5px] text-slate-400 uppercase">ARR Run-Rate</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">₹4.8 Cr</div>
              <div class="text-[9px] text-emerald-600 font-bold">+240% YoY</div>
            </div>
            <div class="border-x border-slate-100">
              <div class="text-[9.5px] text-slate-400 uppercase">Gross Margin</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">82%</div>
              <div class="text-[9px] text-slate-400">Pure SaaS</div>
            </div>
            <div>
              <div class="text-[9.5px] text-slate-400 uppercase">Net Retention</div>
              <div class="text-sm font-extrabold text-blue-600 mt-0.5">114%</div>
              <div class="text-[9px] text-slate-400">US Enterprise</div>
            </div>
          </div>

          <!-- Syndicate Deal Terms -->
          <div class="mt-4 space-y-2 text-xs">
            <div class="flex items-center justify-between text-slate-600">
              <span>Round Target:</span>
              <span class="font-bold text-slate-900 font-mono">₹6.50 Cr (SAFE Note)</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span>Lead Co-Investor:</span>
              <span class="font-bold text-blue-700">Peak XV Syndicate</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span>Minimum Cheque:</span>
              <span class="font-bold text-emerald-700 font-mono">₹5.00 Lakhs</span>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t border-slate-200/80">
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="w-full py-3 px-4 rounded-xl text-center text-xs font-bold text-slate-900 bg-white hover:bg-blue-600 hover:text-white border border-slate-300 hover:border-blue-600 font-satoshi transition-all flex items-center justify-center gap-2 shadow-2xs">
            <span>Unlock Audited Data Room</span>
            <span class="text-sm">&rarr;</span>
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 2: PayFlow Matrix -->
      <div class="bg-[#F8FAFC] border border-slate-200/90 rounded-[26px] p-6 flex flex-col justify-between hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-950/5 transition-all duration-300 space-y-5">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-2xl bg-indigo-600 text-white font-black text-sm flex items-center justify-center font-satoshi shadow-md shadow-indigo-600/20">
              PF
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60 font-satoshi">FinTech</span>
              <span class="px-2 py-1 rounded-md text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">92% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-xl font-bold text-slate-900 font-satoshi">PayFlow Matrix</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Cross-border B2B digital escrow and real-time trade settlement rails across India, UAE, and SEA.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3 p-2 rounded-xl bg-white border border-slate-200/80 flex items-center gap-2 text-[10.5px] font-mono text-slate-700">
            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">DigiLocker Founder KYC &middot; Clean Delaware SAFE</span>
          </div>

          <!-- Financial Metrics Box -->
          <div class="mt-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[9.5px] text-slate-400 uppercase">Monthly GMV</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">₹120 Cr</div>
              <div class="text-[9px] text-emerald-600 font-bold">+18% MoM</div>
            </div>
            <div class="border-x border-slate-100">
              <div class="text-[9.5px] text-slate-400 uppercase">Take Rate</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">0.65%</div>
              <div class="text-[9px] text-slate-400">High Volume</div>
            </div>
            <div>
              <div class="text-[9.5px] text-slate-400 uppercase">Net Revenue</div>
              <div class="text-sm font-extrabold text-indigo-600 mt-0.5">₹9.3 Cr</div>
              <div class="text-[9px] text-slate-400">ARR Run-Rate</div>
            </div>
          </div>

          <!-- Syndicate Deal Terms -->
          <div class="mt-4 space-y-2 text-xs">
            <div class="flex items-center justify-between text-slate-600">
              <span>Round Target:</span>
              <span class="font-bold text-slate-900 font-mono">₹4.20 Cr (Delaware SAFE)</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span>Lead Co-Investor:</span>
              <span class="font-bold text-indigo-700">Blume Co-Invest</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span>Minimum Cheque:</span>
              <span class="font-bold text-emerald-700 font-mono">₹2.00 Lakhs</span>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t border-slate-200/80">
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="w-full py-3 px-4 rounded-xl text-center text-xs font-bold text-slate-900 bg-white hover:bg-indigo-600 hover:text-white border border-slate-300 hover:border-indigo-600 font-satoshi transition-all flex items-center justify-center gap-2 shadow-2xs">
            <span>Unlock Audited Data Room</span>
            <span class="text-sm">&rarr;</span>
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 3: Kinetix Robotics -->
      <div class="bg-[#F8FAFC] border border-slate-200/90 rounded-[26px] p-6 flex flex-col justify-between hover:border-slate-800 hover:shadow-xl hover:shadow-slate-950/5 transition-all duration-300 space-y-5">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white font-black text-sm flex items-center justify-center font-satoshi shadow-md">
              KR
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-2.5 py-1 rounded-md text-[10.5px] font-bold bg-slate-100 text-slate-800 border border-slate-200 font-satoshi">DeepTech</span>
              <span class="px-2 py-1 rounded-md text-[10px] font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200/60">65% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-xl font-bold text-slate-900 font-satoshi">Kinetix Robotics</h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              Ex-ISRO engineering team deploying autonomous mobile robotic sorting for tier-1 supply chains.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3 p-2 rounded-xl bg-white border border-slate-200/80 flex items-center gap-2 text-[10.5px] font-mono text-slate-700">
            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">4 Core Patents Granted &middot; Tier-1 Contracts</span>
          </div>

          <!-- Financial Metrics Box -->
          <div class="mt-3 p-3.5 rounded-2xl bg-white border border-slate-200/80 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[9.5px] text-slate-400 uppercase">Deployed Units</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">142 Bots</div>
              <div class="text-[9px] text-emerald-600 font-bold">In Production</div>
            </div>
            <div class="border-x border-slate-100">
              <div class="text-[9.5px] text-slate-400 uppercase">Order Pipeline</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">₹18.5 Cr</div>
              <div class="text-[9px] text-slate-400">Under Contract</div>
            </div>
            <div>
              <div class="text-[9.5px] text-slate-400 uppercase">Patents Filed</div>
              <div class="text-sm font-extrabold text-slate-900 mt-0.5">4 Granted</div>
              <div class="text-[9px] text-slate-400">Core IP Safe</div>
            </div>
          </div>

          <!-- Syndicate Deal Terms -->
          <div class="mt-4 space-y-2 text-xs">
            <div class="flex items-center justify-between text-slate-600">
              <span>Round Target:</span>
              <span class="font-bold text-slate-900 font-mono">₹8.50 Cr (CCPS Equity)</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span>Lead Co-Investor:</span>
              <span class="font-bold text-slate-900">DeepTech Guild Lead</span>
            </div>
            <div class="flex items-center justify-between text-slate-600">
              <span>Minimum Cheque:</span>
              <span class="font-bold text-emerald-700 font-mono">₹10.00 Lakhs</span>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t border-slate-200/80">
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="w-full py-3 px-4 rounded-xl text-center text-xs font-bold text-slate-900 bg-white hover:bg-slate-900 hover:text-white border border-slate-300 hover:border-slate-900 font-satoshi transition-all flex items-center justify-center gap-2 shadow-2xs">
            <span>Unlock Audited Data Room</span>
            <span class="text-sm">&rarr;</span>
          </a>
        </div>
      </div>

    </div>

    <!-- Institutional Unlock Banner (Drives Registration in Royal Blue Theme) -->
    <div class="mt-12 p-6 sm:p-7 rounded-[24px] bg-gradient-to-r from-slate-900 via-[#0F172A] to-blue-950 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl border border-slate-800">
      <div class="flex items-center gap-4 text-center md:text-left">
        <div class="w-12 h-12 rounded-2xl bg-blue-600/25 border border-blue-400/30 text-blue-400 flex items-center justify-center shrink-0">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <div>
          <div class="text-base sm:text-lg font-bold font-satoshi text-white">21 More Verified Rounds Awaiting Accredited Investors</div>
          <div class="text-xs text-slate-400 font-medium">Free instant accreditation &middot; Full cap tables, financial models &amp; founder calls</div>
        </div>
      </div>

      <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
        class="shrink-0 px-8 py-3.5 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs sm:text-sm font-satoshi transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30">
        <span>Request Accredited Access</span>
        <span class="text-base">&rarr;</span>
      </a>
    </div>

  </div>

  <!-- Dynamic Filter Script for Financial Matrix -->
  <script>
    function filterDealMatrix(sector, btn) {
      document.querySelectorAll('.deal-filter-btn').forEach(b => {
        b.className = 'deal-filter-btn px-4 py-2 rounded-xl bg-[#F8FAFC] border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition shrink-0';
      });
      btn.className = 'deal-filter-btn active px-4 py-2 rounded-xl bg-blue-600 text-white shadow-xs transition shrink-0';

      const grid = document.getElementById('dealMatrixGrid');
      if (!grid) return;

      grid.style.opacity = '0.4';
      setTimeout(() => {
        grid.style.opacity = '1';
      }, 150);
    }
  </script>

</section>
