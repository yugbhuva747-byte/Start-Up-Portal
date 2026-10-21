<?php
/**
 * ============================================================================
 * includes/founder_home/growth-pillars.php
 * Founder Growth Flywheel — Asymmetrical Bento Architecture
 * ============================================================================
 */
?>
<section class="py-20 md:py-28 bg-white border-t border-slate-200/80 relative overflow-hidden" id="founder-flywheel">

  <div class="max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- Section Header -->
    <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs font-bold uppercase tracking-wider font-satoshi">
        <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-2.5 5-2.5"></path><path d="M15 12v5s3.03-.55 4.5-2c1.63-1.62 2.5-5 2.5-5"></path></svg>
        <span>Why Founders Register on NEXORA</span>
      </div>
      <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 tracking-[-0.035em] leading-tight font-satoshi">
        Why 1,400+ Founders Register Here Instead of Raising Alone
      </h2>
      <p class="text-base sm:text-lg text-slate-500 font-normal leading-relaxed font-satoshi">
        Listing your company is 100% free. Gain direct partner introductions, protect your confidential pitch deck with automated NDAs, and unlock $150K in cloud credits on sign-up.
      </p>
    </div>

    <!-- ==================================================================== -->
    <!-- ASYMMETRICAL BENTO SHOWCASE (Row 1: 7 Cols + 5 Cols)                  -->
    <!-- ==================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 items-stretch">

      <!-- BENTO 1: Direct Partner Terminal with Interactive Sector Tabs (Cols 7) -->
      <div class="lg:col-span-7 bg-[#F8FAFC] border border-slate-200/90 rounded-[28px] p-7 sm:p-9 flex flex-col justify-between hover:border-blue-500 hover:shadow-xl hover:shadow-blue-500/10 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/25">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-bold font-satoshi">Zero Gatekeepers</span>
          </div>

          <h3 class="text-2xl font-bold text-slate-900 mb-2 font-satoshi">
            Direct Cheque-Writer Terminal
          </h3>
          <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6 font-satoshi">
            Pitch directly to managing partners, verified angel syndicates, and family offices with live capital allocation power. No junior analyst screens.
          </p>

          <!-- Interactive Category Filter Chips -->
          <div class="flex items-center gap-2 flex-wrap mb-4 text-xs font-bold font-satoshi">
            <button type="button" onclick="filterPillarInvestors('all', this)" class="pillar-tab-btn active px-3 py-1.5 rounded-lg bg-blue-600 text-white shadow-xs transition">
              All Sectors (450+)
            </button>
            <button type="button" onclick="filterPillarInvestors('saas', this)" class="pillar-tab-btn px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition">
              AI &amp; SaaS
            </button>
            <button type="button" onclick="filterPillarInvestors('fintech', this)" class="pillar-tab-btn px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition">
              FinTech
            </button>
            <button type="button" onclick="filterPillarInvestors('consumer', this)" class="pillar-tab-btn px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition">
              Consumer / D2C
            </button>
          </div>
        </div>

        <!-- Dynamic Allocator Cards Stack -->
        <div class="space-y-2.5 pt-2" id="pillarInvestorsContainer">
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0">RK</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Rohit Kapoor</div>
                <div class="text-[10.5px] text-slate-400 truncate">Partner @ Peak XV Syndicate &middot; Lead Cheques</div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">₹2 Cr - ₹10 Cr</span>
              <span class="text-emerald-600 text-[10.5px] font-bold hidden sm:inline">&bull; Active Mandate</span>
            </div>
          </div>

          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0">AM</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Ananya Mehta</div>
                <div class="text-[10.5px] text-slate-400 truncate">Fintech Angel Lead &middot; 32 Early-Stage Bets</div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">₹50L - ₹2.5 Cr</span>
              <span class="text-emerald-600 text-[10.5px] font-bold hidden sm:inline">&bull; Active Mandate</span>
            </div>
          </div>
        </div>
      </div>

      <!-- BENTO 2: Smart Vault & Watermarked Data Room (Cols 5) -->
      <div class="lg:col-span-5 bg-[#F8FAFC] border border-slate-200/90 rounded-[28px] p-7 sm:p-9 flex flex-col justify-between hover:border-indigo-500 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-md shadow-indigo-600/25">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold font-satoshi">Bank-Grade Legal</span>
          </div>

          <h3 class="text-2xl font-bold text-slate-900 mb-2 font-satoshi">
            Automated Smart Data Room
          </h3>
          <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6 font-satoshi">
            One-click watermarked pitch decks with dynamic investor IP tracking, auto-executed digital NDAs, and standardized Delaware &amp; SEBI SAFE cap tables.
          </p>
        </div>

        <!-- Files Visual Showcase -->
        <div class="space-y-2.5 p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs">
          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center shrink-0">PDF</span>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-800 truncate font-satoshi">PitchDeck_v4_Confidential.pdf</div>
                <div class="text-[10px] text-slate-400">Dynamic Viewer Watermarking Active</div>
              </div>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60 shrink-0">Auto-NDA</span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-100">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0">XLS</span>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-800 truncate font-satoshi">SAFE_CapTable_Audited.xlsx</div>
                <div class="text-[10px] text-slate-400">Standardized Equity &middot; Zero Debt</div>
              </div>
            </div>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shrink-0">SEBI Clean</span>
          </div>

          <div class="pt-1 flex items-center justify-between text-[11px] text-slate-500 font-medium">
            <span class="flex items-center gap-1.5 font-bold text-emerald-700">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> 14 Partners currently reading
            </span>
            <span>94% Read Rate</span>
          </div>
        </div>
      </div>

    </div>

    <!-- ==================================================================== -->
    <!-- ASYMMETRICAL BENTO SHOWCASE (Row 2: 5 Cols + 7 Cols)                  -->
    <!-- ==================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">

      <!-- BENTO 3: 18-Day Diligence Speedometer vs Traditional VC (Cols 5) -->
      <div class="lg:col-span-5 bg-[#F8FAFC] border border-slate-200/90 rounded-[28px] p-7 sm:p-9 flex flex-col justify-between hover:border-emerald-500 hover:shadow-xl hover:shadow-emerald-500/10 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-md shadow-emerald-600/25">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                <polyline points="17 6 23 6 23 12"></polyline>
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold font-satoshi">6x Speedup</span>
          </div>

          <h3 class="text-2xl font-bold text-slate-900 mb-2 font-satoshi">
            18-Day Diligence Sprint
          </h3>
          <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6 font-satoshi">
            Pre-audited metrics give lead investors instant conviction. Transform 6 months of endless ghosting into wired capital in under 3 weeks.
          </p>
        </div>

        <!-- Comparative Progress Gauge -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-4">
          <div class="space-y-1.5">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-400">
              <span>Traditional VC Process</span>
              <span class="text-rose-600 font-bold">180 Days (6 Months)</span>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
              <div class="bg-rose-400 h-full w-full rounded-full"></div>
            </div>
            <div class="text-[10px] text-slate-400">Junior screenings, endless deck requests, partner committees</div>
          </div>

          <div class="space-y-1.5 pt-1 border-t border-slate-100">
            <div class="flex items-center justify-between text-xs font-bold text-slate-800">
              <span class="flex items-center gap-1.5 text-emerald-700 font-satoshi">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> NEXORA Fast-Track Sprint
              </span>
              <span class="text-emerald-600 font-extrabold font-mono">18 Days to Wire</span>
            </div>
            <div class="w-full bg-emerald-100/70 h-3 rounded-full overflow-hidden p-0.5">
              <div class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full w-[22%] rounded-full"></div>
            </div>
            <div class="text-[10px] text-emerald-700 font-semibold font-satoshi">162 Days Saved &middot; Zero Product Disruption</div>
          </div>
        </div>
      </div>

      <!-- BENTO 4: $150K Zero-Equity Founder Stack (Cols 7) -->
      <div class="lg:col-span-7 bg-[#F8FAFC] border border-slate-200/90 rounded-[28px] p-7 sm:p-9 flex flex-col justify-between hover:border-amber-500 hover:shadow-xl hover:shadow-amber-500/10 transition-all duration-300">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-600 text-white flex items-center justify-center shadow-md shadow-amber-600/25">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="7"></circle>
                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-bold font-satoshi">Runway Boost</span>
          </div>

          <h3 class="text-2xl font-bold text-slate-900 mb-2 font-satoshi">
            $150,000+ Zero-Equity Stack
          </h3>
          <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-6 font-satoshi">
            Instantly extend your operational runway from Day 1. Pre-approved founder infrastructure credits across the world's leading tech providers.
          </p>
        </div>

        <!-- 4-Tile Interactive Perks Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs text-center space-y-1">
            <div class="text-xs font-black text-slate-900 font-satoshi">AWS Activate</div>
            <div class="text-sm font-extrabold text-amber-600 font-mono">$100,000</div>
            <div class="text-[9.5px] text-slate-400">Cloud Credits</div>
          </div>

          <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs text-center space-y-1">
            <div class="text-xs font-black text-slate-900 font-satoshi">Stripe Atlas</div>
            <div class="text-sm font-extrabold text-blue-600 font-mono">$20,000</div>
            <div class="text-[9.5px] text-slate-400">Free Processing</div>
          </div>

          <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs text-center space-y-1">
            <div class="text-xs font-black text-slate-900 font-satoshi">OpenAI API</div>
            <div class="text-sm font-extrabold text-emerald-600 font-mono">$2,500</div>
            <div class="text-[9.5px] text-slate-400">Model Credits</div>
          </div>

          <div class="p-3.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs text-center space-y-1">
            <div class="text-xs font-black text-slate-900 font-satoshi">Google Cloud</div>
            <div class="text-sm font-extrabold text-indigo-600 font-mono">$25,000</div>
            <div class="text-[9.5px] text-slate-400">Infra Credits</div>
          </div>
        </div>
      </div>

    </div>

    <!-- Founder Conversion Accelerator Strip -->
    <div class="mt-12 p-5 sm:p-6 rounded-[22px] bg-gradient-to-r from-slate-900 via-[#0F172A] to-slate-900 border border-slate-800 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
      <div class="flex items-center gap-3.5 text-center sm:text-left">
        <div class="w-11 h-11 rounded-xl bg-blue-600/20 border border-blue-400/30 text-blue-400 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path>
            <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
            <path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-2.5 5-2.5"></path>
            <path d="M15 12v5s3.03-.55 4.5-2c1.63-1.62 2.5-5 2.5-5"></path>
          </svg>
        </div>
        <div>
          <div class="text-sm sm:text-base font-bold font-satoshi text-white">Join 1,400+ Founders Raising on Clean Terms</div>
          <div class="text-xs text-slate-400 font-medium">Free dossier listing &middot; 0% equity cut &middot; 450+ verified cheque-writers online</div>
        </div>
      </div>
      <a href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>"
        class="w-full sm:w-auto px-7 py-3 rounded-full bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-600/30 hover:shadow-lg transition-all font-satoshi flex items-center justify-center gap-2 shrink-0">
        <span>Register Startup Free</span>
        <span class="text-base">&rarr;</span>
      </a>
    </div>

  </div>

  <!-- Micro-script for Category Switching in Pillar 1 -->
  <script>
    function filterPillarInvestors(sector, btn) {
      document.querySelectorAll('.pillar-tab-btn').forEach(b => {
        b.className = 'pillar-tab-btn px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition';
      });
      btn.className = 'pillar-tab-btn active px-3 py-1.5 rounded-lg bg-blue-600 text-white shadow-xs transition';

      const container = document.getElementById('pillarInvestorsContainer');
      if (!container) return;

      if (sector === 'saas') {
        container.innerHTML = `
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-black text-xs flex items-center justify-center shrink-0">BV</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Blume Ventures (SaaS Fund)</div>
                <div class="text-[10.5px] text-slate-400 truncate">Enterprise AI &middot; Seed &amp; Pre-Series A</div>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 shrink-0">₹3 Cr - ₹12 Cr</span>
          </div>
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0">SA</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">SaaS Syndicate Lead</div>
                <div class="text-[10.5px] text-slate-400 truncate">24 Global SaaS Unicorn Backers</div>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60 shrink-0">₹1 Cr - ₹5 Cr</span>
          </div>
        `;
      } else if (sector === 'fintech') {
        container.innerHTML = `
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-emerald-600 text-white font-black text-xs flex items-center justify-center shrink-0">AM</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Ananya Mehta (Fintech Angel)</div>
                <div class="text-[10.5px] text-slate-400 truncate">Payments &amp; Neo-banking Lead</div>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60 shrink-0">₹50L - ₹2.5 Cr</span>
          </div>
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-black text-xs flex items-center justify-center shrink-0">QP</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">QED Partners Syndicate</div>
                <div class="text-[10.5px] text-slate-400 truncate">Lending &amp; Infrastructure Mandate</div>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 shrink-0">₹4 Cr - ₹15 Cr</span>
          </div>
        `;
      } else if (sector === 'consumer') {
        container.innerHTML = `
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-amber-600 text-white font-black text-xs flex items-center justify-center shrink-0">FS</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Fireside Ventures Network</div>
                <div class="text-[10.5px] text-slate-400 truncate">D2C &amp; Omnichannel Brands</div>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60 shrink-0">₹2 Cr - ₹8 Cr</span>
          </div>
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-rose-600 text-white font-black text-xs flex items-center justify-center shrink-0">CA</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Consumer Angel Guild</div>
                <div class="text-[10.5px] text-slate-400 truncate">18 D2C Founders Backing Seed</div>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200/60 shrink-0">₹25L - ₹1.5 Cr</span>
          </div>
        `;
      } else {
        container.innerHTML = `
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0">RK</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Rohit Kapoor</div>
                <div class="text-[10.5px] text-slate-400 truncate">Partner @ Peak XV Syndicate &middot; Lead Cheques</div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">₹2 Cr - ₹10 Cr</span>
              <span class="text-emerald-600 text-[10.5px] font-bold hidden sm:inline">&bull; Active Mandate</span>
            </div>
          </div>
          <div class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between animate-fadeIn">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-xs flex items-center justify-center shrink-0">AM</div>
              <div class="min-w-0">
                <div class="text-xs font-bold text-slate-900 leading-tight truncate">Ananya Mehta</div>
                <div class="text-[10.5px] text-slate-400 truncate">Fintech Angel Lead &middot; 32 Early-Stage Bets</div>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/60">₹50L - ₹2.5 Cr</span>
              <span class="text-emerald-600 text-[10.5px] font-bold hidden sm:inline">&bull; Active Mandate</span>
            </div>
          </div>
        `;
      }
    }
  </script>

</section>

