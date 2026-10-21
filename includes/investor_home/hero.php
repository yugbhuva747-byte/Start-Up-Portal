<?php
/**
 * ============================================================================
 * includes/investor_home/hero.php
 * Investor Hero Section — 2-Column Split Institutional Dealflow Cockpit
 * Unified with NEXORA Royal Blue & Deep Slate brand theme.
 * Focus: Maximum Trust, Verification & Frictionless Investor Sign-up.
 * ============================================================================
 */
?>
<section class="relative pt-28 pb-16 md:pt-32 md:pb-24 overflow-hidden bg-[#F8FAFC]" id="investor-hero">

  <!-- Ambient Luxury Studio Lighting & Structural Blueprint Grid -->
  <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:28px_28px] opacity-70"></div>
  <div class="pointer-events-none absolute -top-40 right-10 w-[700px] h-[500px] bg-blue-500/12 rounded-full blur-[140px]"></div>
  <div class="pointer-events-none absolute top-1/2 -left-40 w-[600px] h-[400px] bg-indigo-500/10 rounded-full blur-[120px]"></div>

  <div class="relative max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- 2-COLUMN ASYMMETRICAL INSTITUTIONAL SPLIT STAGE -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">

      <!-- LEFT COLUMN: Executive Thesis & High-Conviction Access (Cols 7) -->
      <div class="lg:col-span-7 space-y-7 text-left">

        <!-- Live Network Eyebrow Pill -->
        <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs font-bold font-satoshi shadow-2xs">
          <span class="flex h-2 w-2 relative">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-500 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
          </span>
          <span class="uppercase tracking-wider text-[11px]">SEBI &amp; MCA VERIFIED VENTURE PLATFORM &middot; TOP 1.8% ACCEPTANCE</span>
        </div>

        <!-- Sovereign Headline for Capital Allocators -->
        <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-extrabold text-[#0F172A] tracking-[-0.035em] leading-[1.06] font-satoshi">
          Invest in India's Most Promising,
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600">
            Pre-Vetted Startups.
          </span>
        </h1>

        <!-- Executive Subtitle -->
        <p class="text-base sm:text-lg text-slate-600 font-normal leading-relaxed max-w-xl font-satoshi">
          Zero unvetted cold pitches. Every startup on NEXORA has passed strict forensic financial audits, MCA legal verification, and is backed by Tier-1 lead investors. Co-invest from ₹2 Lakhs with 0% platform fees.
        </p>

        <!-- Fast Sector Thesis Pills -->
        <div class="flex flex-wrap items-center gap-2 pt-1 text-xs font-bold font-satoshi text-slate-700">
          <span class="text-slate-400 text-[11px] uppercase tracking-wider font-semibold mr-1">Audited Sectors:</span>
          <span class="px-3 py-1 rounded-full bg-white border border-slate-200/90 shadow-2xs text-blue-700 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span> Enterprise AI &amp; SaaS (9)
          </span>
          <span class="px-3 py-1 rounded-full bg-white border border-slate-200/90 shadow-2xs text-indigo-700 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span> FinTech &amp; B2B (6)
          </span>
          <span class="px-3 py-1 rounded-full bg-white border border-slate-200/90 shadow-2xs text-slate-700 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span> DeepTech &amp; Robotics (5)
          </span>
          <span class="px-3 py-1 rounded-full bg-white border border-slate-200/90 shadow-2xs text-emerald-700 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> CleanTech (4)
          </span>
        </div>

        <!-- 1-Click Fast Investor Access Invitation Form (Royal Blue Theme) -->
        <div class="pt-2">
          <form action="<?= function_exists('url') ? url('auth/register.php') : 'auth/register.php' ?>" method="GET" class="w-full max-w-lg">
            <input type="hidden" name="role" value="investor">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-1.5 sm:p-2 rounded-2xl sm:rounded-full bg-white border-2 border-blue-500/40 shadow-xl shadow-blue-600/12 focus-within:border-blue-600 transition-all">
              <div class="flex items-center gap-3 px-4 py-2 sm:py-0 flex-1">
                <svg class="w-5 h-5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                  <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <input type="email" name="email" required placeholder="Enter work email (e.g. partner@fund.com)" class="w-full text-sm sm:text-[15px] text-slate-800 placeholder-slate-400 bg-transparent focus:outline-none font-satoshi">
              </div>
              <button type="submit" class="px-7 py-3.5 rounded-xl sm:rounded-full text-sm sm:text-[15px] font-bold text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 shadow-md shadow-blue-600/30 font-satoshi transition-all flex items-center justify-center gap-2 shrink-0">
                <span>Access Dealflow Free</span>
                <span class="text-base">&rarr;</span>
              </button>
            </div>
          </form>

          <div class="mt-2 text-xs text-slate-500 font-medium flex items-center gap-1.5">
            <span>Accredited investor already?</span>
            <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="font-bold text-blue-600 hover:text-blue-800 underline transition">
              Sign In to Dealroom Terminal &rarr;
            </a>
          </div>
        </div>

        <!-- Trust Anchors Row -->
        <div class="pt-4 flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-slate-600 font-medium border-t border-slate-200/80">
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="font-bold text-slate-900 font-satoshi">Government MCA Verified</span>
          </div>
          <span class="text-slate-300">&bull;</span>
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="font-bold text-slate-900 font-satoshi">Audited 24-Mo Unit Economics</span>
          </div>
          <span class="text-slate-300">&bull;</span>
          <div class="flex items-center gap-1.5">
            <svg class="w-4 h-4 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <span>0% Broker Fees</span>
          </div>
        </div>

      </div>

      <!-- RIGHT COLUMN: Interactive Live Deal Matrix Terminal (Cols 5) -->
      <div class="lg:col-span-5 relative">

        <!-- Halo glow effect -->
        <div class="pointer-events-none absolute -inset-3 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 rounded-[34px] blur-xl opacity-75"></div>

        <!-- Terminal Outer Window in Obsidian Slate -->
        <div class="relative bg-[#0B1120] border border-slate-800 rounded-[28px] p-5 sm:p-6 text-white shadow-2xl space-y-4">

          <!-- Terminal Top Chrome -->
          <div class="flex items-center justify-between pb-3.5 border-b border-slate-800/80">
            <div class="flex items-center gap-2.5">
              <span class="w-3 h-3 rounded-full bg-rose-500/90"></span>
              <span class="w-3 h-3 rounded-full bg-amber-500/90"></span>
              <span class="w-3 h-3 rounded-full bg-emerald-500/90"></span>
              <span class="text-[11px] font-mono text-slate-400 font-bold ml-1.5">NEXORA.DEALFLOW // LIVE</span>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-emerald-400 text-[10.5px] font-mono font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              24 ACTIVE AUDITED ROUNDS
            </span>
          </div>

          <!-- Featured Verified Deal Card -->
          <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-3.5 hover:border-blue-500/50 transition">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-sm flex items-center justify-center font-satoshi shadow-md">
                  CS
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="text-sm font-bold text-white font-satoshi">CloudScale AI</h4>
                    <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 text-[9.5px] font-mono font-semibold border border-blue-400/30">SEED ROUND</span>
                  </div>
                  <p class="text-[11px] text-slate-400">Autonomous Cloud Spend Optimization &middot; Bengaluru</p>
                </div>
              </div>
              <span class="text-emerald-400 text-xs font-mono font-bold">78% Filled</span>
            </div>

            <!-- Verification Trust Badge Row -->
            <div class="flex items-center gap-2 text-[10.5px] font-mono text-emerald-300 bg-emerald-950/40 p-2 rounded-lg border border-emerald-500/25">
              <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span>MCA Verified &middot; GST P&amp;L Reconciled &middot; Delaware SAFE</span>
            </div>

            <!-- 3-Metrics Grid -->
            <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 text-center font-mono">
              <div>
                <div class="text-[9.5px] text-slate-400 uppercase">ARR Run-Rate</div>
                <div class="text-xs font-bold text-white mt-0.5">₹4.8 Cr</div>
                <div class="text-[8.5px] text-emerald-400">+240% YoY</div>
              </div>
              <div class="border-x border-slate-800">
                <div class="text-[9.5px] text-slate-400 uppercase">Round Size</div>
                <div class="text-xs font-bold text-white mt-0.5">₹6.50 Cr</div>
                <div class="text-[8.5px] text-blue-400">Standard SAFE</div>
              </div>
              <div>
                <div class="text-[9.5px] text-slate-400 uppercase">Min Ticket</div>
                <div class="text-xs font-bold text-emerald-400 mt-0.5">₹5.0 L</div>
                <div class="text-[8.5px] text-slate-400">Lead: Peak XV</div>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-1">
              <div class="flex justify-between text-[10px] text-slate-400 font-mono">
                <span>Committed: ₹5.10 Cr</span>
                <span class="text-emerald-400">₹1.40 Cr Available</span>
              </div>
              <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 via-indigo-500 to-emerald-400 h-full w-[78%] rounded-full"></div>
              </div>
            </div>

            <!-- Action buttons inside terminal -->
            <div class="pt-1 flex items-center gap-2">
              <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>" class="flex-1 py-2 px-3 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold font-satoshi text-center transition flex items-center justify-center gap-1 shadow-md shadow-blue-600/30">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                <span>Inspect Verified Data Room</span>
              </a>
              <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>" class="py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold font-satoshi text-center transition">
                Co-Invest &rarr;
              </a>
            </div>
          </div>

          <!-- Secondary Deal Feed Ticker -->
          <div class="space-y-2 pt-1">
            <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between text-xs">
              <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-black text-[10px] flex items-center justify-center shrink-0">PF</span>
                <div class="min-w-0">
                  <div class="font-bold text-white font-satoshi truncate">PayFlow Matrix &middot; FinTech</div>
                  <div class="text-[10px] text-slate-400 truncate">₹4.2 Cr Target &middot; Lead: Blume Co-Invest</div>
                </div>
              </div>
              <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 text-[10px] font-mono font-bold shrink-0">92% Filled</span>
            </div>

            <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between text-xs">
              <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-7 h-7 rounded-lg bg-slate-800 text-white font-black text-[10px] flex items-center justify-center shrink-0">KR</span>
                <div class="min-w-0">
                  <div class="font-bold text-white font-satoshi truncate">Kinetix Robotics &middot; DeepTech</div>
                  <div class="text-[10px] text-slate-400 truncate">₹8.5 Cr Target &middot; Ex-ISRO Founders</div>
                </div>
              </div>
              <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 text-[10px] font-mono font-bold shrink-0">65% Filled</span>
            </div>
          </div>

          <!-- Live Activity Feed Chip -->
          <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10.5px] text-slate-400 font-mono">
            <span class="flex items-center gap-1.5 text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Peak XV Syndicate committed ₹50L
            </span>
            <span>2 mins ago</span>
          </div>

        </div>

      </div>

    </div>

  </div>

</section>
