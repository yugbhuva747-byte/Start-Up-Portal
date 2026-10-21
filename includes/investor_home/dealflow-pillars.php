<?php
/**
 * ============================================================================
 * includes/investor_home/dealflow-pillars.php
 * Institutional Dealflow Matrix — Financial Tear-Sheet Directory & Live Data Room
 * Unified with NEXORA Royal Blue & Deep Slate brand theme.
 * Focus: High Trust, Verified Companies & Meaningful Institutional Transparency.
 * ============================================================================
 */
?>
<section class="py-20 md:py-28 bg-white border-t border-slate-200/80 relative overflow-hidden" id="investor-matrix">

  <div class="max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- Section Header: Institutional Trust Tone -->
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
      <div class="space-y-3 max-w-2xl">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-100/70 border-2 border-blue-300 text-blue-900 text-xs font-black font-satoshi uppercase tracking-wider">
          <svg class="w-4 h-4 text-blue-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
          </svg>
          <span>Curated Global Dealflow Directory</span>
        </div>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-950 tracking-[-0.04em] leading-tight font-satoshi">
          Curated Global Startups Raising Right Now
        </h2>
        <p class="text-base sm:text-lg text-slate-800 font-bold leading-relaxed font-satoshi">
          Out of 2,400+ applications across Silicon Valley, London, Singapore, Dubai, and India, only 43 passed our cross-border diligence shield. Explore live rounds with audited traction, verified founders, and Tier-1 co-investors.
        </p>
      </div>

      <!-- Live Pipeline Counter Badge -->
      <div class="p-4 sm:p-5 rounded-2xl bg-[#F8FAFC] border-2 border-slate-300 text-right shrink-0 shadow-md">
        <div class="text-xs font-bold text-slate-600 font-satoshi uppercase tracking-wider">Global Diligence Volume</div>
        <div class="text-2xl sm:text-3xl font-black text-slate-950 font-mono">$18.5M / ₹150Cr</div>
        <div class="text-xs text-blue-700 font-extrabold mt-1 flex items-center justify-end gap-1.5">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> 24 Cross-Border Rounds Active
        </div>
      </div>
    </div>

    <!-- Multi-Axis Filter & Search Controls Bar -->
    <div class="bg-white p-5 sm:p-6 rounded-2xl border-2 border-slate-300 mb-8 space-y-4 shadow-sm">
      <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
        
        <!-- Sector Pills Filter (Wrapping cleanly with bold text) -->
        <div class="flex items-center gap-2 flex-wrap text-xs sm:text-sm font-black font-satoshi">
          <button type="button" onclick="filterDealMatrix('all', this)" class="deal-filter-btn active px-4 py-2.5 rounded-xl bg-blue-600 text-white font-black shadow-sm transition shrink-0">
            All Verified Rounds (<span id="count-all">6</span>)
          </button>
          <button type="button" onclick="filterDealMatrix('saas', this)" class="deal-filter-btn px-4 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-slate-900 font-extrabold hover:text-blue-700 hover:border-blue-500 hover:bg-white transition shrink-0">
            Enterprise AI &amp; SaaS (<span id="count-saas">2</span>)
          </button>
          <button type="button" onclick="filterDealMatrix('fintech', this)" class="deal-filter-btn px-4 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-slate-900 font-extrabold hover:text-blue-700 hover:border-blue-500 hover:bg-white transition shrink-0">
            FinTech &amp; B2B (<span id="count-fintech">1</span>)
          </button>
          <button type="button" onclick="filterDealMatrix('deeptech', this)" class="deal-filter-btn px-4 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-slate-900 font-extrabold hover:text-blue-700 hover:border-blue-500 hover:bg-white transition shrink-0">
            DeepTech &amp; Robotics (<span id="count-deeptech">1</span>)
          </button>
          <button type="button" onclick="filterDealMatrix('climate', this)" class="deal-filter-btn px-4 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-slate-900 font-extrabold hover:text-blue-700 hover:border-blue-500 hover:bg-white transition shrink-0">
            CleanTech &amp; EV (<span id="count-climate">1</span>)
          </button>
          <button type="button" onclick="filterDealMatrix('healthtech', this)" class="deal-filter-btn px-4 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-slate-900 font-extrabold hover:text-blue-700 hover:border-blue-500 hover:bg-white transition shrink-0">
            HealthTech AI (<span id="count-healthtech">1</span>)
          </button>
        </div>

        <!-- Search & Cheque Size Selectors -->
        <div class="flex items-center gap-3">
          <div class="relative flex-1 sm:w-64">
            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="dealSearchInput" oninput="applyDealFilters()" placeholder="Search startup name or thesis..." class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-xs sm:text-sm font-bold text-slate-950 placeholder:text-slate-500 placeholder:font-semibold focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
          </div>

          <select id="dealTicketFilter" onchange="applyDealFilters()" class="px-3.5 py-2.5 rounded-xl bg-slate-50 border-2 border-slate-300 text-xs sm:text-sm font-black text-slate-950 focus:outline-none focus:border-blue-600 focus:bg-white">
            <option value="all">Any Ticket Size</option>
            <option value="under5">Min Cheque &le; ₹5 Lakhs</option>
            <option value="under10">Min Cheque &le; ₹10 Lakhs</option>
            <option value="10plus">Min Cheque &gt; ₹10 Lakhs</option>
          </select>
        </div>

      </div>

      <!-- Live Results Status Line -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs sm:text-sm font-black text-slate-800 border-t-2 border-slate-200 pt-3.5 gap-2">
        <span id="dealResultsCount" class="text-slate-900 font-black">Showing 6 of 6 verified rounds</span>
        <span class="text-emerald-700 font-black flex items-center gap-1.5">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
          100% MCA, Delaware SEC &amp; DigiLocker Checked
        </span>
      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- 6 FINANCIAL TEAR-SHEET CARDS GRID                                    -->
    <!-- ==================================================================== -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8" id="dealMatrixGrid">

      <!-- TEAR SHEET 1: CloudScale AI -->
      <div class="deal-card bg-white border-2 border-slate-300 rounded-[28px] p-6 flex flex-col justify-between hover:border-blue-600 hover:shadow-xl hover:shadow-blue-900/10 transition-all duration-300 space-y-5"
           data-category="saas" data-ticket="5" data-name="CloudScale AI" data-dealid="cs">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white font-black text-base flex items-center justify-center font-satoshi shadow-md shadow-blue-600/30">
              CS
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-3 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-950 border border-blue-300 font-satoshi">Enterprise SaaS</span>
              <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-emerald-100 text-emerald-950 border border-emerald-300">78% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-950 font-satoshi tracking-tight">CloudScale AI</h3>
            <p class="text-xs sm:text-sm font-bold text-slate-700 mt-2 leading-relaxed font-satoshi">
              Autonomous enterprise cloud spend optimization engine with multi-cloud Kubernetes telemetry.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border-2 border-slate-200 flex items-center gap-2 text-xs font-mono font-black text-slate-900">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">MCA Incorporation &amp; GST P&amp;L Reconciled</span>
          </div>

          <!-- Micro-Tabs: Financials vs Diligence -->
          <div class="mt-4 flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-satoshi font-black">
            <button type="button" onclick="switchCardTab(this, 'fin-cs')" class="card-tab-btn active flex-1 py-1.5 rounded-lg text-center bg-white text-blue-700 shadow-xs font-black transition">Key Financials</button>
            <button type="button" onclick="switchCardTab(this, 'cap-cs')" class="card-tab-btn flex-1 py-1.5 rounded-lg text-center text-slate-600 hover:text-slate-950 font-extrabold transition">Round Terms</button>
          </div>

          <!-- Tab Pane: Financials -->
          <div id="fin-cs" class="card-tab-pane mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">ARR Run-Rate</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">₹4.8 Cr</div>
              <div class="text-[10px] text-emerald-700 font-black">+240% YoY</div>
            </div>
            <div class="border-x-2 border-slate-200">
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Gross Margin</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">82%</div>
              <div class="text-[10px] font-black text-slate-700">Pure SaaS</div>
            </div>
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Net Retention</div>
              <div class="text-base sm:text-lg font-black text-blue-700 mt-0.5">114%</div>
              <div class="text-[10px] font-black text-slate-700">US Enterprise</div>
            </div>
          </div>

          <!-- Tab Pane: Terms -->
          <div id="cap-cs" class="card-tab-pane hidden mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2 text-xs font-satoshi">
            <div class="flex justify-between font-bold text-slate-700"><span>Round Size:</span> <span class="font-black text-slate-950 font-mono text-sm">₹6.50 Cr</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Instrument:</span> <span class="font-black text-blue-800">Delaware Post-Money SAFE</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Lead Co-Investor:</span> <span class="font-black text-indigo-900">Peak XV Syndicate</span></div>
          </div>

          <!-- Deal Terms Summary -->
          <div class="mt-4 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Lead Co-Investor:</span>
              <span class="font-black text-blue-900">Peak XV Syndicate</span>
            </div>
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Minimum Cheque:</span>
              <span class="font-black text-emerald-800 font-mono text-sm sm:text-base">₹5.00 Lakhs</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-3.5 space-y-1.5">
            <div class="flex justify-between text-xs font-black font-mono text-slate-800">
              <span>Committed: ₹5.10 Cr</span>
              <span class="text-emerald-700 font-black">₹1.40 Cr Left</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full w-[78%] rounded-full"></div>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t-2 border-slate-100 flex items-center gap-2">
          <button type="button" onclick="openDealPreviewModal('cs')"
                  class="flex-1 py-3 px-3 rounded-xl text-center text-xs sm:text-sm font-black text-slate-950 bg-slate-50 hover:bg-blue-600 hover:text-white border-2 border-slate-300 hover:border-blue-600 font-satoshi transition-all flex items-center justify-center gap-1.5 shadow-2xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Unlock Data Room</span>
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="py-3 px-5 rounded-xl text-center text-xs sm:text-sm font-black text-white bg-blue-600 hover:bg-blue-700 font-satoshi transition shadow-md shadow-blue-600/30">
            Co-Invest
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 2: PayFlow Matrix -->
      <div class="deal-card bg-white border-2 border-slate-300 rounded-[28px] p-6 flex flex-col justify-between hover:border-indigo-600 hover:shadow-xl hover:shadow-indigo-900/10 transition-all duration-300 space-y-5"
           data-category="fintech" data-ticket="2" data-name="PayFlow Matrix" data-dealid="pf">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white font-black text-base flex items-center justify-center font-satoshi shadow-md shadow-indigo-600/30">
              PF
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-3 py-1 rounded-lg text-xs font-black bg-indigo-100 text-indigo-950 border border-indigo-300 font-satoshi">FinTech</span>
              <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-emerald-100 text-emerald-950 border border-emerald-300">92% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-950 font-satoshi tracking-tight">PayFlow Matrix</h3>
            <p class="text-xs sm:text-sm font-bold text-slate-700 mt-2 leading-relaxed font-satoshi">
              Cross-border B2B digital escrow and real-time trade settlement rails across India, UAE, and SEA.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border-2 border-slate-200 flex items-center gap-2 text-xs font-mono font-black text-slate-900">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">DigiLocker KYC &middot; Clean Delaware SAFE</span>
          </div>

          <!-- Micro-Tabs -->
          <div class="mt-4 flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-satoshi font-black">
            <button type="button" onclick="switchCardTab(this, 'fin-pf')" class="card-tab-btn active flex-1 py-1.5 rounded-lg text-center bg-white text-indigo-700 shadow-xs font-black transition">Key Financials</button>
            <button type="button" onclick="switchCardTab(this, 'cap-pf')" class="card-tab-btn flex-1 py-1.5 rounded-lg text-center text-slate-600 hover:text-slate-950 font-extrabold transition">Round Terms</button>
          </div>

          <!-- Tab Pane: Financials -->
          <div id="fin-pf" class="card-tab-pane mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Monthly GMV</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">₹120 Cr</div>
              <div class="text-[10px] text-emerald-700 font-black">+18% MoM</div>
            </div>
            <div class="border-x-2 border-slate-200">
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Take Rate</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">0.65%</div>
              <div class="text-[10px] font-black text-slate-700">High Volume</div>
            </div>
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Net Revenue</div>
              <div class="text-base sm:text-lg font-black text-indigo-700 mt-0.5">₹9.3 Cr</div>
              <div class="text-[10px] font-black text-slate-700">ARR Run-Rate</div>
            </div>
          </div>

          <!-- Tab Pane: Terms -->
          <div id="cap-pf" class="card-tab-pane hidden mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2 text-xs font-satoshi">
            <div class="flex justify-between font-bold text-slate-700"><span>Round Size:</span> <span class="font-black text-slate-950 font-mono text-sm">₹4.20 Cr</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Instrument:</span> <span class="font-black text-indigo-800">Delaware SAFE Note</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Lead Co-Investor:</span> <span class="font-black text-indigo-900">Blume Co-Invest</span></div>
          </div>

          <!-- Deal Terms Summary -->
          <div class="mt-4 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Lead Co-Investor:</span>
              <span class="font-black text-indigo-900">Blume Co-Invest</span>
            </div>
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Minimum Cheque:</span>
              <span class="font-black text-emerald-800 font-mono text-sm sm:text-base">₹2.00 Lakhs</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-3.5 space-y-1.5">
            <div class="flex justify-between text-xs font-black font-mono text-slate-800">
              <span>Committed: ₹3.86 Cr</span>
              <span class="text-emerald-700 font-black">₹34 Lakhs Left</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-gradient-to-r from-indigo-600 to-purple-600 h-full w-[92%] rounded-full"></div>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t-2 border-slate-100 flex items-center gap-2">
          <button type="button" onclick="openDealPreviewModal('pf')"
                  class="flex-1 py-3 px-3 rounded-xl text-center text-xs sm:text-sm font-black text-slate-950 bg-slate-50 hover:bg-indigo-600 hover:text-white border-2 border-slate-300 hover:border-indigo-600 font-satoshi transition-all flex items-center justify-center gap-1.5 shadow-2xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Unlock Data Room</span>
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="py-3 px-5 rounded-xl text-center text-xs sm:text-sm font-black text-white bg-indigo-600 hover:bg-indigo-700 font-satoshi transition shadow-md shadow-indigo-600/30">
            Co-Invest
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 3: Kinetix Robotics -->
      <div class="deal-card bg-white border-2 border-slate-300 rounded-[28px] p-6 flex flex-col justify-between hover:border-slate-950 hover:shadow-xl hover:shadow-slate-950/10 transition-all duration-300 space-y-5"
           data-category="deeptech" data-ticket="10" data-name="Kinetix Robotics" data-dealid="kr">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-slate-950 text-white font-black text-base flex items-center justify-center font-satoshi shadow-md">
              KR
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-3 py-1 rounded-lg text-xs font-black bg-slate-200 text-slate-950 border border-slate-400 font-satoshi">DeepTech</span>
              <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-blue-100 text-blue-950 border border-blue-300">65% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-950 font-satoshi tracking-tight">Kinetix Robotics</h3>
            <p class="text-xs sm:text-sm font-bold text-slate-700 mt-2 leading-relaxed font-satoshi">
              Ex-ISRO engineering team deploying autonomous mobile robotic sorting for tier-1 supply chains.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border-2 border-slate-200 flex items-center gap-2 text-xs font-mono font-black text-slate-900">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">4 Core Patents Granted &middot; Tier-1 Contracts</span>
          </div>

          <!-- Micro-Tabs -->
          <div class="mt-4 flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-satoshi font-black">
            <button type="button" onclick="switchCardTab(this, 'fin-kr')" class="card-tab-btn active flex-1 py-1.5 rounded-lg text-center bg-white text-slate-950 shadow-xs font-black transition">Key Financials</button>
            <button type="button" onclick="switchCardTab(this, 'cap-kr')" class="card-tab-btn flex-1 py-1.5 rounded-lg text-center text-slate-600 hover:text-slate-950 font-extrabold transition">Round Terms</button>
          </div>

          <!-- Tab Pane: Financials -->
          <div id="fin-kr" class="card-tab-pane mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Deployed Units</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">142 Bots</div>
              <div class="text-[10px] text-emerald-700 font-black">In Production</div>
            </div>
            <div class="border-x-2 border-slate-200">
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Order Pipeline</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">₹18.5 Cr</div>
              <div class="text-[10px] font-black text-slate-700">Under Contract</div>
            </div>
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Patents Filed</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">4 Granted</div>
              <div class="text-[10px] font-black text-slate-700">Core IP Safe</div>
            </div>
          </div>

          <!-- Tab Pane: Terms -->
          <div id="cap-kr" class="card-tab-pane hidden mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2 text-xs font-satoshi">
            <div class="flex justify-between font-bold text-slate-700"><span>Round Size:</span> <span class="font-black text-slate-950 font-mono text-sm">₹8.50 Cr</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Instrument:</span> <span class="font-black text-slate-950">SEBI CCPS Equity</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Lead Co-Investor:</span> <span class="font-black text-slate-950">DeepTech Guild Lead</span></div>
          </div>

          <!-- Deal Terms Summary -->
          <div class="mt-4 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Lead Co-Investor:</span>
              <span class="font-black text-slate-950">DeepTech Guild Lead</span>
            </div>
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Minimum Cheque:</span>
              <span class="font-black text-emerald-800 font-mono text-sm sm:text-base">₹10.00 Lakhs</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-3.5 space-y-1.5">
            <div class="flex justify-between text-xs font-black font-mono text-slate-800">
              <span>Committed: ₹5.52 Cr</span>
              <span class="text-blue-700 font-black">₹2.98 Cr Left</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-gradient-to-r from-slate-700 to-slate-950 h-full w-[65%] rounded-full"></div>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t-2 border-slate-100 flex items-center gap-2">
          <button type="button" onclick="openDealPreviewModal('kr')"
                  class="flex-1 py-3 px-3 rounded-xl text-center text-xs sm:text-sm font-black text-slate-950 bg-slate-50 hover:bg-slate-950 hover:text-white border-2 border-slate-300 hover:border-slate-950 font-satoshi transition-all flex items-center justify-center gap-1.5 shadow-2xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Unlock Data Room</span>
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="py-3 px-5 rounded-xl text-center text-xs sm:text-sm font-black text-white bg-slate-950 hover:bg-slate-900 font-satoshi transition shadow-md">
            Co-Invest
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 4: VoltPulse Mobility (CleanTech / EV) -->
      <div class="deal-card bg-white border-2 border-slate-300 rounded-[28px] p-6 flex flex-col justify-between hover:border-emerald-600 hover:shadow-xl hover:shadow-emerald-900/10 transition-all duration-300 space-y-5"
           data-category="climate" data-ticket="2.5" data-name="VoltPulse Mobility" data-dealid="vp">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white font-black text-base flex items-center justify-center font-satoshi shadow-md shadow-emerald-600/30">
              VP
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-3 py-1 rounded-lg text-xs font-black bg-emerald-100 text-emerald-950 border border-emerald-300 font-satoshi">CleanTech &middot; EV</span>
              <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-emerald-100 text-emerald-950 border border-emerald-300">84% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-950 font-satoshi tracking-tight">VoltPulse Mobility</h3>
            <p class="text-xs sm:text-sm font-bold text-slate-700 mt-2 leading-relaxed font-satoshi">
              Smart battery swapping infrastructure network with predictive battery health AI and carbon credits.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border-2 border-slate-200 flex items-center gap-2 text-xs font-mono font-black text-slate-900">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">3 Patents &middot; ARAI Certified &middot; FAME II Safe</span>
          </div>

          <!-- Micro-Tabs -->
          <div class="mt-4 flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-satoshi font-black">
            <button type="button" onclick="switchCardTab(this, 'fin-vp')" class="card-tab-btn active flex-1 py-1.5 rounded-lg text-center bg-white text-emerald-700 shadow-xs font-black transition">Key Financials</button>
            <button type="button" onclick="switchCardTab(this, 'cap-vp')" class="card-tab-btn flex-1 py-1.5 rounded-lg text-center text-slate-600 hover:text-slate-950 font-extrabold transition">Round Terms</button>
          </div>

          <!-- Tab Pane: Financials -->
          <div id="fin-vp" class="card-tab-pane mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Swaps / Day</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">3,200+</div>
              <div class="text-[10px] text-emerald-700 font-black">94 Hubs</div>
            </div>
            <div class="border-x-2 border-slate-200">
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">ARR Run-Rate</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">₹7.2 Cr</div>
              <div class="text-[10px] font-black text-slate-700">+190% YoY</div>
            </div>
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Unit Payback</div>
              <div class="text-base sm:text-lg font-black text-emerald-700 mt-0.5">11 Mo</div>
              <div class="text-[10px] font-black text-slate-700">Hardware Positive</div>
            </div>
          </div>

          <!-- Tab Pane: Terms -->
          <div id="cap-vp" class="card-tab-pane hidden mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2 text-xs font-satoshi">
            <div class="flex justify-between font-bold text-slate-700"><span>Round Size:</span> <span class="font-black text-slate-950 font-mono text-sm">₹5.00 Cr</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Instrument:</span> <span class="font-black text-emerald-800">SEBI CCPS Equity</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Lead Co-Investor:</span> <span class="font-black text-emerald-900">Climate Angels Collective</span></div>
          </div>

          <!-- Deal Terms Summary -->
          <div class="mt-4 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Lead Co-Investor:</span>
              <span class="font-black text-emerald-900">Climate Angels</span>
            </div>
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Minimum Cheque:</span>
              <span class="font-black text-emerald-800 font-mono text-sm sm:text-base">₹2.50 Lakhs</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-3.5 space-y-1.5">
            <div class="flex justify-between text-xs font-black font-mono text-slate-800">
              <span>Committed: ₹4.20 Cr</span>
              <span class="text-emerald-700 font-black">₹80 Lakhs Left</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-gradient-to-r from-emerald-600 to-teal-700 h-full w-[84%] rounded-full"></div>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t-2 border-slate-100 flex items-center gap-2">
          <button type="button" onclick="openDealPreviewModal('vp')"
                  class="flex-1 py-3 px-3 rounded-xl text-center text-xs sm:text-sm font-black text-slate-950 bg-slate-50 hover:bg-emerald-600 hover:text-white border-2 border-slate-300 hover:border-emerald-600 font-satoshi transition-all flex items-center justify-center gap-1.5 shadow-2xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Unlock Data Room</span>
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="py-3 px-5 rounded-xl text-center text-xs sm:text-sm font-black text-white bg-emerald-600 hover:bg-emerald-700 font-satoshi transition shadow-md shadow-emerald-600/30">
            Co-Invest
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 5: AuraGenix Health (HealthTech AI) -->
      <div class="deal-card bg-white border-2 border-slate-300 rounded-[28px] p-6 flex flex-col justify-between hover:border-cyan-600 hover:shadow-xl hover:shadow-cyan-900/10 transition-all duration-300 space-y-5"
           data-category="healthtech" data-ticket="5" data-name="AuraGenix Health" data-dealid="ag">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-cyan-700 text-white font-black text-base flex items-center justify-center font-satoshi shadow-md shadow-cyan-700/30">
              AG
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-3 py-1 rounded-lg text-xs font-black bg-cyan-100 text-cyan-950 border border-cyan-300 font-satoshi">HealthTech</span>
              <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-emerald-100 text-emerald-950 border border-emerald-300">60% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-950 font-satoshi tracking-tight">AuraGenix Health</h3>
            <p class="text-xs sm:text-sm font-bold text-slate-700 mt-2 leading-relaxed font-satoshi">
              AI-assisted pathology and real-time genomic screening software deployed in 18 apex hospital chains.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border-2 border-slate-200 flex items-center gap-2 text-xs font-mono font-black text-slate-900">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">CDSCO Approved &middot; HIPAA Certified &middot; US Patent Pending</span>
          </div>

          <!-- Micro-Tabs -->
          <div class="mt-4 flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-satoshi font-black">
            <button type="button" onclick="switchCardTab(this, 'fin-ag')" class="card-tab-btn active flex-1 py-1.5 rounded-lg text-center bg-white text-cyan-700 shadow-xs font-black transition">Key Financials</button>
            <button type="button" onclick="switchCardTab(this, 'cap-ag')" class="card-tab-btn flex-1 py-1.5 rounded-lg text-center text-slate-600 hover:text-slate-950 font-extrabold transition">Round Terms</button>
          </div>

          <!-- Tab Pane: Financials -->
          <div id="fin-ag" class="card-tab-pane mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Hospital Tie-ups</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">18 Chains</div>
              <div class="text-[10px] text-cyan-800 font-black">Annual Contracts</div>
            </div>
            <div class="border-x-2 border-slate-200">
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">ARR Run-Rate</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">₹3.6 Cr</div>
              <div class="text-[10px] font-black text-slate-700">88% Gross Margin</div>
            </div>
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Clinical Accuracy</div>
              <div class="text-base sm:text-lg font-black text-emerald-700 mt-0.5">99.1%</div>
              <div class="text-[10px] font-black text-slate-700">Peer Reviewed</div>
            </div>
          </div>

          <!-- Tab Pane: Terms -->
          <div id="cap-ag" class="card-tab-pane hidden mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2 text-xs font-satoshi">
            <div class="flex justify-between font-bold text-slate-700"><span>Round Size:</span> <span class="font-black text-slate-950 font-mono text-sm">₹4.50 Cr</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Instrument:</span> <span class="font-black text-cyan-800">Delaware SAFE Note</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Lead Co-Investor:</span> <span class="font-black text-cyan-900">BioVentures Syndicate</span></div>
          </div>

          <!-- Deal Terms Summary -->
          <div class="mt-4 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Lead Co-Investor:</span>
              <span class="font-black text-cyan-900">BioVentures Syndicate</span>
            </div>
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Minimum Cheque:</span>
              <span class="font-black text-emerald-800 font-mono text-sm sm:text-base">₹5.00 Lakhs</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-3.5 space-y-1.5">
            <div class="flex justify-between text-xs font-black font-mono text-slate-800">
              <span>Committed: ₹2.70 Cr</span>
              <span class="text-cyan-800 font-black">₹1.80 Cr Left</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-gradient-to-r from-cyan-600 to-blue-700 h-full w-[60%] rounded-full"></div>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t-2 border-slate-100 flex items-center gap-2">
          <button type="button" onclick="openDealPreviewModal('ag')"
                  class="flex-1 py-3 px-3 rounded-xl text-center text-xs sm:text-sm font-black text-slate-950 bg-slate-50 hover:bg-cyan-600 hover:text-white border-2 border-slate-300 hover:border-cyan-600 font-satoshi transition-all flex items-center justify-center gap-1.5 shadow-2xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Unlock Data Room</span>
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="py-3 px-5 rounded-xl text-center text-xs sm:text-sm font-black text-white bg-cyan-700 hover:bg-cyan-800 font-satoshi transition shadow-md shadow-cyan-700/30">
            Co-Invest
          </a>
        </div>
      </div>

      <!-- TEAR SHEET 6: Synthetix Cyber (Enterprise Security / SaaS) -->
      <div class="deal-card bg-white border-2 border-slate-300 rounded-[28px] p-6 flex flex-col justify-between hover:border-blue-700 hover:shadow-xl hover:shadow-blue-900/10 transition-all duration-300 space-y-5"
           data-category="saas" data-ticket="3" data-name="Synthetix Cyber" data-dealid="sc">
        <div>
          <!-- Header with Badges -->
          <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-700 text-white font-black text-base flex items-center justify-center font-satoshi shadow-md shadow-blue-700/30">
              SC
            </div>
            <div class="flex items-center gap-1.5">
              <span class="px-3 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-950 border border-blue-300 font-satoshi">Cybersecurity</span>
              <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-black bg-emerald-100 text-emerald-950 border border-emerald-300">88% FILLED</span>
            </div>
          </div>

          <div>
            <h3 class="text-2xl font-black text-slate-950 font-satoshi tracking-tight">Synthetix Cyber</h3>
            <p class="text-xs sm:text-sm font-bold text-slate-700 mt-2 leading-relaxed font-satoshi">
              Zero-trust identity access and automated prompt injection defense for LLMs deployed in financial institutions.
            </p>
          </div>

          <!-- Trust Shield Stamp -->
          <div class="mt-3.5 p-3 rounded-xl bg-slate-50 border-2 border-slate-200 flex items-center gap-2 text-xs font-mono font-black text-slate-900">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="truncate">SOC2 Type II Certified &middot; ISO 27001 &middot; MCA Clean</span>
          </div>

          <!-- Micro-Tabs -->
          <div class="mt-4 flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200 text-xs font-satoshi font-black">
            <button type="button" onclick="switchCardTab(this, 'fin-sc')" class="card-tab-btn active flex-1 py-1.5 rounded-lg text-center bg-white text-blue-700 shadow-xs font-black transition">Key Financials</button>
            <button type="button" onclick="switchCardTab(this, 'cap-sc')" class="card-tab-btn flex-1 py-1.5 rounded-lg text-center text-slate-600 hover:text-slate-950 font-extrabold transition">Round Terms</button>
          </div>

          <!-- Tab Pane: Financials -->
          <div id="fin-sc" class="card-tab-pane mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 grid grid-cols-3 gap-2 text-center font-mono">
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Enterprise Clients</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">42 Logos</div>
              <div class="text-[10px] text-emerald-700 font-black">0% Churn</div>
            </div>
            <div class="border-x-2 border-slate-200">
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">ARR Run-Rate</div>
              <div class="text-base sm:text-lg font-black text-slate-950 mt-0.5">₹5.4 Cr</div>
              <div class="text-[10px] font-black text-slate-700">+210% YoY</div>
            </div>
            <div>
              <div class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Gross Margin</div>
              <div class="text-base sm:text-lg font-black text-blue-700 mt-0.5">85%</div>
              <div class="text-[10px] font-black text-slate-700">Pure Software</div>
            </div>
          </div>

          <!-- Tab Pane: Terms -->
          <div id="cap-sc" class="card-tab-pane hidden mt-2.5 p-3.5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2 text-xs font-satoshi">
            <div class="flex justify-between font-bold text-slate-700"><span>Round Size:</span> <span class="font-black text-slate-950 font-mono text-sm">₹5.50 Cr</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Instrument:</span> <span class="font-black text-blue-800">Delaware SAFE Note</span></div>
            <div class="flex justify-between font-bold text-slate-700"><span>Lead Co-Investor:</span> <span class="font-black text-indigo-900">Chiratae Angel Collective</span></div>
          </div>

          <!-- Deal Terms Summary -->
          <div class="mt-4 space-y-2 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Lead Co-Investor:</span>
              <span class="font-black text-blue-900">Chiratae Angels</span>
            </div>
            <div class="flex items-center justify-between font-bold text-slate-700">
              <span>Minimum Cheque:</span>
              <span class="font-black text-emerald-800 font-mono text-sm sm:text-base">₹3.00 Lakhs</span>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-3.5 space-y-1.5">
            <div class="flex justify-between text-xs font-black font-mono text-slate-800">
              <span>Committed: ₹4.84 Cr</span>
              <span class="text-emerald-700 font-black">₹66 Lakhs Left</span>
            </div>
            <div class="w-full bg-slate-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-gradient-to-r from-blue-700 to-indigo-700 h-full w-[88%] rounded-full"></div>
            </div>
          </div>
        </div>

        <!-- Action Trigger -->
        <div class="pt-4 border-t-2 border-slate-100 flex items-center gap-2">
          <button type="button" onclick="openDealPreviewModal('sc')"
                  class="flex-1 py-3 px-3 rounded-xl text-center text-xs sm:text-sm font-black text-slate-950 bg-slate-50 hover:bg-blue-600 hover:text-white border-2 border-slate-300 hover:border-blue-600 font-satoshi transition-all flex items-center justify-center gap-1.5 shadow-2xs">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <span>Unlock Data Room</span>
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="py-3 px-5 rounded-xl text-center text-xs sm:text-sm font-black text-white bg-blue-700 hover:bg-blue-800 font-satoshi transition shadow-md shadow-blue-700/30">
            Co-Invest
          </a>
        </div>
      </div>

    </div>

    <!-- Institutional Unlock Banner (Drives Registration in Royal Blue Theme) -->
    <div class="mt-14 p-6 sm:p-8 rounded-[28px] bg-gradient-to-r from-slate-900 via-[#0F172A] to-blue-950 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl border border-slate-800">
      <div class="flex items-center gap-4 text-center md:text-left">
        <div class="w-12 h-12 rounded-2xl bg-blue-600/30 border border-blue-400/40 text-blue-300 flex items-center justify-center shrink-0 shadow-md">
          <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <div>
          <div class="text-base sm:text-lg font-black font-satoshi text-white tracking-tight">21 More Verified Rounds Awaiting Accredited Investors</div>
          <div class="text-xs text-slate-300 font-semibold mt-0.5">Free instant accreditation &middot; Full cap tables, financial models &amp; founder calls</div>
        </div>
      </div>

      <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
        class="shrink-0 px-8 py-3.5 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs sm:text-sm font-satoshi transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-600/40">
        <span>Request Accredited Access</span>
        <span class="text-base font-black">&rarr;</span>
      </a>
    </div>

  </div>

  <!-- ==================================================================== -->
  <!-- AUDITED DATA ROOM PREVIEW MODAL                                      -->
  <!-- ==================================================================== -->
  <div id="dealPreviewModal" class="fixed inset-0 z-50 hidden bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl bg-white border border-slate-300 rounded-[28px] shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-200">
      
      <!-- Modal Header -->
      <div class="p-6 bg-[#0B1120] text-white flex items-center justify-between border-b border-slate-800">
        <div class="flex items-center gap-3">
          <div id="modalLogo" class="w-12 h-12 rounded-xl bg-blue-600 text-white font-black text-sm flex items-center justify-center font-satoshi shadow-md">
            CS
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 id="modalName" class="text-lg font-black font-satoshi text-white tracking-tight">CloudScale AI</h3>
              <span id="modalSector" class="px-2.5 py-0.5 rounded-md bg-blue-500/25 text-blue-200 text-[11px] font-mono font-bold border border-blue-400/40">Enterprise SaaS</span>
            </div>
            <p id="modalTagline" class="text-xs text-slate-300 font-semibold mt-0.5">Autonomous Cloud Spend Optimization &middot; Bengaluru</p>
          </div>
        </div>
        <button type="button" onclick="closeDealPreviewModal()" class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-6 overflow-y-auto space-y-6 text-slate-900 font-satoshi text-xs">
        
        <!-- Diligence Seal Bar -->
        <div class="p-3.5 rounded-xl bg-emerald-50 border-2 border-emerald-300 text-emerald-950 flex items-center justify-between font-mono">
          <span class="flex items-center gap-2 text-xs font-black">
            <svg class="w-4 h-4 text-emerald-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span>NEXORA DILIGENCE PASSED: 98.4 / 100 Trust Score</span>
          </span>
          <span class="text-[10.5px] font-bold text-emerald-800">CIN: U72900KA2023PTC174829</span>
        </div>

        <!-- Financial Summary Grid -->
        <div>
          <h4 class="text-xs uppercase font-black text-slate-800 tracking-wider mb-2 font-mono">Audited Unit Economics (24-Month Forensic P&amp;L)</h4>
          <div class="grid grid-cols-4 gap-2.5 p-3.5 rounded-xl bg-[#F8FAFC] border-2 border-slate-300 text-center font-mono">
            <div>
              <div class="text-[10px] font-bold text-slate-600 uppercase">ARR Run-Rate</div>
              <div id="modalArr" class="text-sm font-black text-slate-950 mt-0.5">₹4.8 Cr</div>
              <div class="text-[9px] text-emerald-700 font-black">+240% YoY</div>
            </div>
            <div class="border-l border-slate-300">
              <div class="text-[10px] font-bold text-slate-600 uppercase">Gross Margin</div>
              <div id="modalMargin" class="text-sm font-black text-slate-950 mt-0.5">82%</div>
              <div class="text-[9px] text-slate-700 font-bold">Pure SaaS</div>
            </div>
            <div class="border-l border-slate-300">
              <div class="text-[10px] font-bold text-slate-600 uppercase">Burn Multiple</div>
              <div class="text-sm font-black text-slate-950 mt-0.5">0.68x</div>
              <div class="text-[9px] text-emerald-700 font-black">High Efficiency</div>
            </div>
            <div class="border-l border-slate-300">
              <div class="text-[10px] font-bold text-slate-600 uppercase">Runway</div>
              <div class="text-sm font-black text-slate-950 mt-0.5">18+ Mo</div>
              <div class="text-[9px] text-slate-700 font-bold">Pre-Round</div>
            </div>
          </div>
        </div>

        <!-- Deal Terms & Legal Standard -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-3.5 rounded-xl border-2 border-slate-300 bg-white space-y-2">
            <span class="text-[10.5px] font-mono uppercase text-slate-700 font-black">Syndicate Round Terms</span>
            <div class="flex justify-between font-bold text-slate-800"><span>Target Round Size:</span> <strong id="modalTarget" class="font-mono font-black text-slate-950">₹6.50 Cr</strong></div>
            <div class="flex justify-between font-bold text-slate-800"><span>Minimum Cheque:</span> <strong id="modalMinCheque" class="font-mono font-black text-emerald-700">₹5.00 Lakhs</strong></div>
            <div class="flex justify-between font-bold text-slate-800"><span>Legal Instrument:</span> <strong id="modalInstrument" class="font-black text-blue-800">Delaware Post-Money SAFE</strong></div>
          </div>

          <div class="p-3.5 rounded-xl border-2 border-slate-300 bg-white space-y-2">
            <span class="text-[10.5px] font-mono uppercase text-slate-700 font-black">Institutional Co-Investors</span>
            <div class="flex justify-between font-bold text-slate-800"><span>Lead Syndicate:</span> <strong id="modalLead" class="font-black text-slate-950">Peak XV Syndicate</strong></div>
            <div class="flex justify-between font-bold text-slate-800"><span>Nominee Structure:</span> <strong class="font-bold text-slate-900">SEBI / Delaware SPV (Clean)</strong></div>
            <div class="flex justify-between font-bold text-slate-800"><span>Brokerage Fee:</span> <strong class="font-black text-emerald-700">0% Platform Drag</strong></div>
          </div>
        </div>

        <!-- Diligence Verification Checklist -->
        <div>
          <h4 class="text-xs uppercase font-black text-slate-800 tracking-wider mb-2 font-mono">Diligence Vault Checklist</h4>
          <div class="space-y-2">
            <div class="flex items-center justify-between p-2.5 rounded-lg bg-[#F8FAFC] border-2 border-slate-300 text-slate-900 font-bold">
              <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Ministry of Corporate Affairs (MCA) DIN &amp; RoC Filings</span>
              <span class="px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-900 text-[10px] font-mono font-black border border-emerald-300">PASSED</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-lg bg-[#F8FAFC] border-2 border-slate-300 text-slate-900 font-bold">
              <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Founder Identity &amp; DigiLocker Aadhaar/PAN Match</span>
              <span class="px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-900 text-[10px] font-mono font-black border border-emerald-300">VERIFIED</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-lg bg-[#F8FAFC] border-2 border-slate-300 text-slate-900 font-bold">
              <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> GST Filings Reconciled with Bank Statement Cash Flow</span>
              <span class="px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-900 text-[10px] font-mono font-black border border-emerald-300">MATCHED 100%</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-lg bg-[#F8FAFC] border-2 border-slate-300 text-slate-900 font-bold">
              <span class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Delaware / SEBI Standardized SAFE (Zero Toxic Liquidation Prefs)</span>
              <span class="px-2.5 py-0.5 rounded bg-emerald-100 text-emerald-900 text-[10px] font-mono font-black border border-emerald-300">CLEAN INSTRUMENT</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="p-4 sm:p-5 bg-slate-100 border-t border-slate-300 flex flex-col sm:flex-row items-center justify-between gap-3">
        <span class="text-xs text-slate-800 font-bold">Full raw data room, cap table &amp; founder calls unlocked upon accreditation.</span>
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
          <button type="button" onclick="closeDealPreviewModal()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-400 text-slate-900 font-extrabold hover:bg-slate-200 transition">
            Close
          </button>
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
             class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold transition flex items-center justify-center gap-2 shadow-md shadow-blue-600/30">
            <span>Unlock Full Vault</span>
            <span>&rarr;</span>
          </a>
        </div>
      </div>

    </div>
  </div>

  <!-- Dynamic Filter, Search & Modal Script -->
  <script>
    let activeCategory = 'all';

    function filterDealMatrix(category, btn) {
      activeCategory = category;
      if (btn) {
        document.querySelectorAll('.deal-filter-btn').forEach(b => {
          b.className = 'deal-filter-btn px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition shrink-0';
        });
        btn.className = 'deal-filter-btn active px-4 py-2 rounded-xl bg-blue-600 text-white shadow-xs transition shrink-0';
      }
      applyDealFilters();
    }

    function applyDealFilters() {
      const searchVal = (document.getElementById('dealSearchInput')?.value || '').toLowerCase().trim();
      const ticketVal = document.getElementById('dealTicketFilter')?.value || 'all';
      const cards = document.querySelectorAll('.deal-card');
      let visibleCount = 0;

      cards.forEach(card => {
        const cat = card.getAttribute('data-category');
        const ticket = parseFloat(card.getAttribute('data-ticket') || '0');
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const cardText = card.innerText.toLowerCase();

        let matchCat = (activeCategory === 'all' || cat === activeCategory);
        let matchSearch = (!searchVal || name.includes(searchVal) || cardText.includes(searchVal));
        let matchTicket = true;

        if (ticketVal === 'under5') {
          matchTicket = ticket <= 5;
        } else if (ticketVal === 'under10') {
          matchTicket = ticket <= 10;
        } else if (ticketVal === '10plus') {
          matchTicket = ticket >= 10;
        }

        if (matchCat && matchSearch && matchTicket) {
          card.style.display = 'flex';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      const countEl = document.getElementById('dealResultsCount');
      if (countEl) {
        countEl.textContent = `Showing ${visibleCount} of ${cards.length} verified rounds`;
      }
    }

    function switchCardTab(btn, paneId) {
      const parent = btn.closest('.deal-card');
      if (!parent) return;

      parent.querySelectorAll('.card-tab-btn').forEach(b => {
        b.className = 'card-tab-btn flex-1 py-1 rounded-lg text-center text-slate-500 hover:text-slate-800 transition';
      });
      btn.className = 'card-tab-btn active flex-1 py-1 rounded-lg text-center bg-blue-50 text-blue-700 transition';

      parent.querySelectorAll('.card-tab-pane').forEach(p => p.classList.add('hidden'));
      const activePane = parent.querySelector('#' + paneId);
      if (activePane) activePane.classList.remove('hidden');
    }

    // Modal Details Dictionary
    const dealModalData = {
      'cs': {
        name: 'CloudScale AI', sector: 'Enterprise SaaS', logo: 'CS',
        tagline: 'Autonomous enterprise cloud spend optimization engine with multi-cloud Kubernetes telemetry · Bengaluru',
        arr: '₹4.8 Cr', margin: '82%', target: '₹6.50 Cr', minCheque: '₹5.00 Lakhs',
        instrument: 'Delaware Post-Money SAFE', lead: 'Peak XV Syndicate'
      },
      'pf': {
        name: 'PayFlow Matrix', sector: 'FinTech', logo: 'PF',
        tagline: 'Cross-border B2B digital escrow and real-time trade settlement rails across India, UAE, and SEA · Mumbai',
        arr: '₹9.3 Cr', margin: '0.65% Take', target: '₹4.20 Cr', minCheque: '₹2.00 Lakhs',
        instrument: 'Delaware SAFE Note', lead: 'Blume Co-Invest'
      },
      'kr': {
        name: 'Kinetix Robotics', sector: 'DeepTech', logo: 'KR',
        tagline: 'Autonomous mobile robotic sorting for tier-1 supply chains with 4 core granted patents · Hyderabad',
        arr: '142 Bots', margin: '₹18.5 Cr Pipe', target: '₹8.50 Cr', minCheque: '₹10.00 Lakhs',
        instrument: 'SEBI CCPS Growth Equity', lead: 'DeepTech Guild Lead'
      },
      'vp': {
        name: 'VoltPulse Mobility', sector: 'CleanTech & EV', logo: 'VP',
        tagline: 'Smart battery swapping infrastructure network with predictive battery health AI · Pune',
        arr: '₹7.2 Cr', margin: '94 Hubs', target: '₹5.00 Cr', minCheque: '₹2.50 Lakhs',
        instrument: 'SEBI CCPS Equity', lead: 'Climate Angels Collective'
      },
      'ag': {
        name: 'AuraGenix Health', sector: 'HealthTech AI', logo: 'AG',
        tagline: 'AI-assisted pathology and real-time genomic screening software deployed in 18 apex hospital chains · Chennai',
        arr: '₹3.6 Cr', margin: '88%', target: '₹4.50 Cr', minCheque: '₹5.00 Lakhs',
        instrument: 'Delaware SAFE Note', lead: 'BioVentures Syndicate'
      },
      'sc': {
        name: 'Synthetix Cyber', sector: 'Cybersecurity', logo: 'SC',
        tagline: 'Zero-trust identity access and automated prompt injection defense for LLMs deployed in financial institutions · Gurugram',
        arr: '₹5.4 Cr', margin: '85%', target: '₹5.50 Cr', minCheque: '₹3.00 Lakhs',
        instrument: 'Delaware SAFE Note', lead: 'Chiratae Angel Collective'
      }
    };

    function openDealPreviewModal(dealId) {
      const data = dealModalData[dealId] || dealModalData['cs'];
      document.getElementById('modalLogo').textContent = data.logo;
      document.getElementById('modalName').textContent = data.name;
      document.getElementById('modalSector').textContent = data.sector;
      document.getElementById('modalTagline').textContent = data.tagline;
      document.getElementById('modalArr').textContent = data.arr;
      document.getElementById('modalMargin').textContent = data.margin;
      document.getElementById('modalTarget').textContent = data.target;
      document.getElementById('modalMinCheque').textContent = data.minCheque;
      document.getElementById('modalInstrument').textContent = data.instrument;
      document.getElementById('modalLead').textContent = data.lead;

      document.getElementById('dealPreviewModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeDealPreviewModal() {
      document.getElementById('dealPreviewModal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    // Close on backdrop click
    document.getElementById('dealPreviewModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'dealPreviewModal') {
        closeDealPreviewModal();
      }
    });
  </script>

</section>
