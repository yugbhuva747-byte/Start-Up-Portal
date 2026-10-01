<?php
/**
 * ============================================================================
 * include/home/pricing.php
 * ----------------------------------------------------------------------------
 * SECTION — TWO-SIDED TRANSPARENT VENTURE PRICING (PASTEL LUXE EDITION)
 * 2 Audiences: Startup Founders & Angel Investors / VCs
 * 4 Tiers per Audience: Free Trial, 1 Month, 6 Months, 1 Year
 * Dual Controls: Role Switcher (Founder / Investor) + Billing Switcher (Monthly / Annual)
 * ============================================================================
 */
?>

<style>
  /* ── Canvas Container with Pastel Aurora Mesh ────────────────── */
  .nx-pricing-section {
    padding: clamp(72px, 8vw, 120px) 0;
    background-color: #FAF5FF;
    background-image: 
      radial-gradient(at 10% 20%, #EDE9FE 0px, transparent 48%),
      radial-gradient(at 90% 15%, #FCE7F3 0px, transparent 45%),
      radial-gradient(at 50% 65%, #FFF7ED 0px, transparent 45%),
      radial-gradient(at 85% 90%, #ECFDF5 0px, transparent 45%);
    position: relative;
    overflow: hidden;
    z-index: 50;
    color: #0F172A;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  .nx-pricing-wrap {
    max-width: 1360px;
    margin: 0 auto;
    padding: 0 clamp(20px, 4vw, 48px);
    position: relative;
    z-index: 2;
  }

  /* Frosted Glass Cards */
  .nx-price-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1.5px solid rgba(255, 255, 255, 0.95);
    box-shadow: 
      0 20px 50px -15px rgba(124, 58, 237, 0.08),
      0 10px 25px -5px rgba(236, 72, 153, 0.05),
      inset 0 1px 1px rgba(255, 255, 255, 0.95);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-price-card:hover {
    transform: translateY(-6px);
    box-shadow: 
      0 28px 60px -12px rgba(124, 58, 237, 0.16),
      0 16px 32px -8px rgba(236, 72, 153, 0.1),
      inset 0 1px 1px rgba(255, 255, 255, 1);
  }

  /* Featured / Most Popular Highlight Card */
  .nx-price-card-popular {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 245, 255, 0.95) 100%);
    border: 2px solid #8B5CF6;
    box-shadow: 
      0 30px 70px -15px rgba(139, 92, 246, 0.25),
      0 15px 35px -8px rgba(236, 72, 153, 0.15),
      inset 0 1px 2px rgba(255, 255, 255, 1);
    position: relative;
  }

  .nx-price-card-popular:hover {
    transform: translateY(-8px);
    box-shadow: 
      0 35px 80px -15px rgba(139, 92, 246, 0.32),
      0 20px 40px -8px rgba(236, 72, 153, 0.2);
  }

  /* Jewel Gradient Texts */
  .gradient-text-pricing {
    background: linear-gradient(135deg, #7C3AED 0%, #EC4899 50%, #F59E0B 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Pill Switchers */
  .nx-pill-container {
    background: rgba(241, 245, 249, 0.95);
    border: 1px solid #E2E8F0;
    backdrop-filter: blur(12px);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.04);
  }

  .nx-pill-btn {
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-pill-btn.active {
    background: #FFFFFF;
    color: #4F46E5;
    box-shadow: 0 4px 16px -2px rgba(99, 102, 241, 0.22), 0 2px 4px rgba(0, 0, 0, 0.04);
    border-color: #DDD6FE;
  }

  /* Feature Checklist Check Icons */
  .nx-feature-check {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
</style>

<section id="nx-pricing" class="nx-pricing-section w-full">
  <div class="nx-pricing-wrap">

    <!-- Section Header -->
    <div class="text-center max-w-[840px] mx-auto mb-10 sm:mb-14 nx-text-reveal">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-gradient-to-r from-purple-100/90 via-pink-100/90 to-amber-100/90 border border-purple-200/80 text-[11.5px] font-bold uppercase tracking-wider text-purple-900 mb-3.5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
        <span>TRANSPARENT VENTURE PRICING &middot; 0% BROKER COMMISSION</span>
      </div>

      <h2 class="text-[32px] sm:text-[44px] lg:text-[48px] font-extrabold tracking-[-0.035em] text-slate-900 leading-[1.14]">
        Predictable Plans for <span class="gradient-text-pricing" id="nx-pricing-dynamic-title">Founders &amp; Investors.</span>
      </h2>
      <p class="text-[15px] sm:text-[16.5px] text-slate-600 mt-3 max-w-[700px] mx-auto leading-relaxed" id="nx-pricing-dynamic-desc">
        Select your role below to view tailored plans. From early-stage startup deal rooms to institutional angel syndication, choose your runway pace.
      </p>

      <!-- Dual Controls: 1. Role Switcher (Founder / Investor) -->
      <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
        
        <!-- Role Toggle (Founders vs Investors) -->
        <div class="nx-pill-container p-1.5 rounded-2xl flex items-center gap-1 shadow-sm">
          <button type="button" id="nx-pricing-role-founder" class="nx-pill-btn active px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.32l2.47-2.47a1.5 1.5 0 012.122 0l1.414 1.414a1.5 1.5 0 010 2.122l-2.47 2.47a4.493 4.493 0 004.32-1.757m-6.109-6.109a15.09 15.09 0 012.448-2.448" />
            </svg>
            <span>For Startup Founders</span>
          </button>
          
          <button type="button" id="nx-pricing-role-investor" class="nx-pill-btn px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-600 hover:text-slate-900 flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>For Angel Investors / VCs</span>
          </button>
        </div>

        <!-- 2. Billing Toggle (Monthly vs Annual) -->
        <div class="nx-pill-container p-1.5 rounded-2xl flex items-center gap-1 shadow-sm">
          <button type="button" id="nx-billing-monthly-btn" class="nx-pill-btn active px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-600 cursor-pointer">
            Monthly
          </button>
          
          <button type="button" id="nx-billing-annual-btn" class="nx-pill-btn px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-600 cursor-pointer flex items-center gap-1.5">
            <span>Annually</span>
            <span class="px-2 py-0.5 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-[10px] font-extrabold uppercase tracking-wide">
              Save 40%
            </span>
          </button>
        </div>

      </div>
    </div>

    <!-- 4 Tiers Pricing Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-6 items-stretch nx-reveal" style="isolation: isolate;">

      <!-- ========================================================
           TIER 1: Free Trial (14 Days)
           ======================================================== -->
      <div class="nx-price-card rounded-3xl p-6 sm:p-7 flex flex-col justify-between relative border border-slate-200">
        <div>
          <!-- Plan Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600" id="tier1-icon">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-600 text-[11px] font-bold uppercase tracking-wider" id="tier1-badge">
              14-Day Trial
            </span>
          </div>

          <h3 class="text-[20px] font-extrabold text-slate-900 leading-snug" id="tier1-name">Free Trial</h3>
          <p class="text-[12.5px] text-slate-500 mt-1 min-h-[38px]" id="tier1-desc">
            Test drive deal rooms &amp; verified investor network with zero commitment.
          </p>

          <!-- Price Display -->
          <div class="my-6 pt-4 border-t border-slate-100">
            <div class="flex items-baseline gap-1">
              <span class="text-[36px] font-extrabold text-slate-900 tracking-tight">₹0</span>
              <span class="text-[13px] text-slate-500 font-medium">/ 14 days</span>
            </div>
            <div class="text-[11px] text-emerald-600 font-bold mt-1">
              No credit card required
            </div>
          </div>

          <!-- Feature List -->
          <div class="space-y-3 mb-8" id="tier1-features">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">INCLUDED IN TRIAL:</div>
            
            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
              <span>1 Live Pitch Deck Listing</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
              <span>Basic Cap Table Viewer</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
              <span>Public Syndicate Directory</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
              <span>Standard Community Support</span>
            </div>
          </div>
        </div>

        <!-- CTA Button -->
        <div>
          <button type="button" id="tier1-cta-btn" data-plan="free_trial" onclick="window.nxOpenPlanModal('free_trial')" class="nx-plan-cta-btn w-full py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs sm:text-sm font-bold transition flex items-center justify-center gap-1.5 cursor-pointer select-none">
            <span id="tier1-cta" class="pointer-events-none">Start Free Trial</span>
            <span aria-hidden="true" class="pointer-events-none">&rarr;</span>
          </button>
        </div>
      </div>

      <!-- ========================================================
           TIER 2: 1 Month Plan
           ======================================================== -->
      <div class="nx-price-card rounded-3xl p-6 sm:p-7 flex flex-col justify-between relative border border-purple-100">
        <div>
          <!-- Plan Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-2xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600" id="tier2-icon">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-[11px] font-bold uppercase tracking-wider" id="tier2-badge">
              Flexible Monthly
            </span>
          </div>

          <h3 class="text-[20px] font-extrabold text-slate-900 leading-snug" id="tier2-name">1 Month Plan</h3>
          <p class="text-[12.5px] text-slate-500 mt-1 min-h-[38px]" id="tier2-desc">
            Perfect for startups currently actively running a seed fundraise sprint.
          </p>

          <!-- Price Display -->
          <div class="my-6 pt-4 border-t border-slate-100">
            <div class="flex items-baseline gap-1">
              <span class="text-[36px] font-extrabold text-slate-900 tracking-tight" id="tier2-price">₹2,499</span>
              <span class="text-[13px] text-slate-500 font-medium">/ month</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1" id="tier2-subtext">
              Billed monthly &middot; Cancel anytime
            </div>
          </div>

          <!-- Feature List -->
          <div class="space-y-3 mb-8" id="tier2-features">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">EVERYTHING IN TRIAL, PLUS:</div>
            
            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
              <span>3 Active Deal Rooms</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
              <span>Direct Founder-Investor DMs</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
              <span>DigiLocker KYC Badge</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
              <span>Real-Time Pitch Deck Analytics</span>
            </div>
          </div>
        </div>

        <!-- CTA Button -->
        <div>
          <button type="button" id="tier2-cta-btn" data-plan="1_month" onclick="window.nxOpenPlanModal('1_month')" class="nx-plan-cta-btn w-full py-3 px-4 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-purple-600/20 transition flex items-center justify-center gap-1.5 cursor-pointer select-none">
            <span id="tier2-cta" class="pointer-events-none">Choose 1 Month</span>
            <span aria-hidden="true" class="pointer-events-none">&rarr;</span>
          </button>
        </div>
      </div>

      <!-- ========================================================
           TIER 3: 6 Months Plan (MOST POPULAR)
           ======================================================== -->
      <div class="nx-price-card-popular rounded-3xl p-6 sm:p-7 flex flex-col justify-between relative transform lg:-translate-y-2" style="z-index: 1;">
        <!-- Most Popular Floating Pill -->
        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-3.5 py-1 rounded-full bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 text-white text-[11px] font-extrabold uppercase tracking-wider shadow-md flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
          </svg>
          <span>MOST POPULAR</span>
        </div>

        <div>
          <!-- Plan Header -->
          <div class="flex items-center justify-between mb-4 mt-2">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center shadow-sm" id="tier3-icon">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-purple-100 border border-purple-300 text-purple-800 text-[11px] font-bold uppercase tracking-wider" id="tier3-badge">
              Save 33%
            </span>
          </div>

          <h3 class="text-[20px] font-extrabold text-slate-900 leading-snug" id="tier3-name">6 Months Plan</h3>
          <p class="text-[12.5px] text-slate-600 mt-1 min-h-[38px]" id="tier3-desc">
            Comprehensive runway for closing Seed to Pre-Series A funding rounds.
          </p>

          <!-- Price Display -->
          <div class="my-6 pt-4 border-t border-purple-100">
            <div class="flex items-baseline gap-1">
              <span class="text-[36px] font-extrabold text-purple-900 tracking-tight" id="tier3-price">₹1,666</span>
              <span class="text-[13px] text-slate-600 font-medium">/ mo</span>
            </div>
            <div class="text-[11px] text-purple-700 font-bold mt-1" id="tier3-subtext">
              ₹9,999 billed for 6 months
            </div>
          </div>

          <!-- Feature List -->
          <div class="space-y-3 mb-8" id="tier3-features">
            <div class="text-[11px] font-bold uppercase tracking-wider text-purple-900 mb-2">EVERYTHING IN 1 MONTH, PLUS:</div>
            
            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
              <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
              <span>Unlimited Active Deal Rooms</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
              <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
              <span>Featured Dealflow Placement</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
              <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
              <span>Direct WhatsApp Warm Intros</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
              <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
              <span>SEBI &amp; MCA Legal SAFE Templates</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
              <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
              <span>Dedicated Venture Scout Manager</span>
            </div>
          </div>
        </div>

        <!-- CTA Button -->
        <div>
          <button type="button" id="tier3-cta-btn" data-plan="6_months" onclick="window.nxOpenPlanModal('6_months')" class="nx-plan-cta-btn w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white text-xs sm:text-sm font-extrabold shadow-lg shadow-purple-600/30 transition flex items-center justify-center gap-1.5 cursor-pointer select-none">
            <span id="tier3-cta" class="pointer-events-none">Get 6-Month Pass</span>
            <span aria-hidden="true" class="pointer-events-none">&rarr;</span>
          </button>
        </div>
      </div>

      <!-- ========================================================
           TIER 4: 1 Year Plan (Annual Pro)
           ======================================================== -->
      <div class="nx-price-card rounded-3xl p-6 sm:p-7 flex flex-col justify-between relative border border-pink-100" style="z-index: 3;">
        <div>
          <!-- Plan Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-2xl bg-pink-50 border border-pink-100 flex items-center justify-center text-pink-600" id="tier4-icon">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <span class="px-3 py-1 rounded-full bg-pink-50 border border-pink-200 text-pink-700 text-[11px] font-bold uppercase tracking-wider" id="tier4-badge">
              Save 40%
            </span>
          </div>

          <h3 class="text-[20px] font-extrabold text-slate-900 leading-snug" id="tier4-name">1 Year Plan</h3>
          <p class="text-[12.5px] text-slate-500 mt-1 min-h-[38px]" id="tier4-desc">
            Full annual suite for high-growth serial founders and venture teams.
          </p>

          <!-- Price Display -->
          <div class="my-6 pt-4 border-t border-slate-100">
            <div class="flex items-baseline gap-1">
              <span class="text-[36px] font-extrabold text-slate-900 tracking-tight" id="tier4-price">₹1,499</span>
              <span class="text-[13px] text-slate-500 font-medium">/ mo</span>
            </div>
            <div class="text-[11px] text-pink-600 font-bold mt-1" id="tier4-subtext">
              ₹17,999 billed annually
            </div>
          </div>

          <!-- Feature List -->
          <div class="space-y-3 mb-8" id="tier4-features">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">FULL ENTERPRISE SUITE:</div>
            
            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
              <span>All 6-Month Growth Features</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
              <span>SPV Pooling &amp; Syndicate Lead Tools</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
              <span>White-Label LP Data Room</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
              <span>Full Platform REST API Access</span>
            </div>

            <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
              <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
              <span>Dedicated Partner Success Director</span>
            </div>
          </div>
        </div>

        <!-- CTA Button -->
        <div style="position: relative; z-index: 10;">
          <button type="button" id="tier4-cta-btn" data-plan="1_year"
            style="position: relative; z-index: 10; pointer-events: all; cursor: pointer;"
            onclick="event.stopPropagation(); window.nxOpenPlanModal('1_year');"
            class="nx-plan-cta-btn w-full py-3 px-4 rounded-xl bg-pink-600 hover:bg-pink-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-pink-600/20 transition flex items-center justify-center gap-1.5 cursor-pointer select-none">
            <span id="tier4-cta" class="pointer-events-none">Unlock 1 Year Pro</span>
            <span aria-hidden="true" class="pointer-events-none">&rarr;</span>
          </button>
        </div>
      </div>

    </div>

    <!-- Assurance & Trust Strip -->
    <div class="mt-14 p-6 rounded-3xl bg-white/70 border border-purple-100/90 shadow-xs backdrop-blur-md flex flex-wrap items-center justify-around gap-6 text-slate-600 text-xs sm:text-sm font-medium">
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <span>100% Secure 256-bit Razorpay / Stripe Payments</span>
      </div>
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <span>Instant Deal Room &amp; Dealflow Activation</span>
      </div>
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-full bg-pink-100 text-pink-700 flex items-center justify-center font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <span>GST Compliant Invoicing &amp; Tax Deductible</span>
      </div>
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <span>0% Success Cuts or Hidden Fees</span>
      </div>
    </div>

    <!-- ========================================================
         MODAL: Plan Priority & Checkout Confirmation
         ======================================================== -->
    <div id="nx-plan-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 bg-slate-950/75 backdrop-blur-md transition-all">
      <div class="relative w-full max-w-[560px] bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-purple-100 overflow-hidden transform transition-all max-h-[92vh] overflow-y-auto">
        
        <!-- Floating Close Button -->
        <button type="button" id="nx-plan-modal-close" onclick="window.nxClosePlanModal()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-900 flex items-center justify-center font-bold text-sm transition cursor-pointer z-10">
          ✕
        </button>

        <!-- Plan Header -->
        <div class="flex items-center gap-3.5 mb-4 pr-8">
          <div id="modal-plan-icon" class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 text-white flex items-center justify-center text-xl shadow-md shrink-0">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
          </div>
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10.5px] font-extrabold uppercase tracking-wide" id="modal-plan-pill">
              Level 4 Priority Granted
            </div>
            <h3 class="text-[21px] sm:text-[23px] font-extrabold text-slate-900 leading-tight mt-0.5" id="modal-plan-title">
              1 Year Scale Pro
            </h3>
          </div>
        </div>

        <!-- Price & Cycle Breakdown -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 mb-4 flex items-center justify-between">
          <div>
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">TOTAL BILLING</div>
            <div class="text-[24px] font-extrabold text-slate-900 leading-none mt-1" id="modal-plan-price">₹17,999</div>
          </div>
          <div class="text-right">
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold" id="modal-plan-cycle">
              1 Year Pass
            </span>
            <div class="text-[11px] text-slate-500 mt-1" id="modal-plan-period">₹1,499 / month</div>
          </div>
        </div>

        <!-- Action Alert Box -->
        <div id="modal-alert-box" class="hidden mb-3.5 p-3 rounded-xl text-xs font-bold"></div>

        <?php if (function_exists('auth_check') && auth_check()): ?>
          <!-- LOGGED-IN USERS: Direct Plan Purchase / Upgrade -->
          <div class="space-y-4">
            <!-- Priority Perks Breakdown -->
            <div class="mb-3">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                YOUR PRIORITY PRIVILEGES:
              </div>
              <div class="space-y-2 text-[12px] text-slate-700 font-medium max-h-[140px] overflow-y-auto pr-1" id="modal-plan-perks">
                <!-- Dynamic Perks inserted here -->
              </div>
            </div>

            <button type="button" id="modal-confirm-action-btn" class="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white font-extrabold text-sm shadow-lg shadow-purple-600/30 transition flex items-center justify-center gap-2 cursor-pointer">
              <span>⚡ Confirm Plan &amp; Activate Priority</span>
              <span aria-hidden="true">&rarr;</span>
            </button>
          </div>
        <?php else: ?>
          <!-- GUEST / VISITOR: Instant Checkout & Quick Registration -->
          <form id="modal-checkout-form" class="space-y-3.5">
            <input type="hidden" id="modal-form-plan-code" name="plan_code" value="1_year">
            <input type="hidden" id="modal-form-billing-cycle" name="billing_cycle" value="monthly">
            <input type="hidden" id="modal-form-role" name="role" value="founder">

            <div class="text-[11px] font-bold uppercase tracking-wider text-purple-900 mb-1 flex items-center gap-1.5">
              <span>⚡ INSTANT ACCOUNT CREATION &amp; PLAN ACTIVATION</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  FULL NAME <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="modal-input-name" name="name" required placeholder="e.g. John Doe"
                  class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500">
              </div>

              <div>
                <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  EMAIL ADDRESS <span class="text-rose-500">*</span>
                </label>
                <input type="email" id="modal-input-email" name="email" required placeholder="name@company.com"
                  class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  PASSWORD <span class="text-rose-500">*</span>
                </label>
                <input type="password" id="modal-input-password" name="password" required placeholder="Min 6 characters" autocomplete="new-password"
                  class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500">
              </div>

              <div id="modal-founder-org-wrap">
                <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-700 mb-1" id="modal-label-org">
                  STARTUP / COMPANY NAME <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="modal-input-org" name="company_name" placeholder="e.g. NextGen AI Labs"
                  class="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500">
              </div>
            </div>

            <div class="pt-1.5 space-y-2">
              <button type="submit" id="modal-submit-btn" class="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white font-extrabold text-xs sm:text-sm shadow-lg shadow-purple-600/30 transition flex items-center justify-center gap-2 cursor-pointer">
                <span id="modal-submit-text">⚡ Complete Purchase &amp; Activate Plan</span>
                <span aria-hidden="true">&rarr;</span>
              </button>

              <button type="button" id="modal-scroll-to-register-btn" class="w-full py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                <span>📝 Or Fill Full Landing Page Form (Scroll Up)</span>
              </button>
            </div>
          </form>
        <?php endif; ?>

        <p class="text-center text-[10px] text-slate-500 font-medium mt-3">
          🔒 256-bit encrypted checkout &middot; 0% broker fee &middot; Instant priority activation
        </p>

      </div>
    </div>

  </div>
</section>

<!-- Dual Role & Billing Switcher Logic -->
<script>
(function() {
  const roleFounderBtn = document.getElementById('nx-pricing-role-founder');
  const roleInvestorBtn = document.getElementById('nx-pricing-role-investor');

  const monthlyBtn = document.getElementById('nx-billing-monthly-btn');
  const annualBtn = document.getElementById('nx-billing-annual-btn');

  const dynTitle = document.getElementById('nx-pricing-dynamic-title');
  const dynDesc = document.getElementById('nx-pricing-dynamic-desc');

  // Tier Elements
  const t1Name = document.getElementById('tier1-name');
  const t1Desc = document.getElementById('tier1-desc');
  const t1Features = document.getElementById('tier1-features');
  const t1Cta = document.getElementById('tier1-cta');

  const t2Name = document.getElementById('tier2-name');
  const t2Desc = document.getElementById('tier2-desc');
  const t2Price = document.getElementById('tier2-price');
  const t2Subtext = document.getElementById('tier2-subtext');
  const t2Features = document.getElementById('tier2-features');
  const t2Cta = document.getElementById('tier2-cta');

  const t3Name = document.getElementById('tier3-name');
  const t3Desc = document.getElementById('tier3-desc');
  const t3Price = document.getElementById('tier3-price');
  const t3Subtext = document.getElementById('tier3-subtext');
  const t3Features = document.getElementById('tier3-features');
  const t3Cta = document.getElementById('tier3-cta');

  const t4Name = document.getElementById('tier4-name');
  const t4Desc = document.getElementById('tier4-desc');
  const t4Price = document.getElementById('tier4-price');
  const t4Subtext = document.getElementById('tier4-subtext');
  const t4Features = document.getElementById('tier4-features');
  const t4Cta = document.getElementById('tier4-cta');

  // Modal Elements
  const modal = document.getElementById('nx-plan-modal');
  const modalClose = document.getElementById('nx-plan-modal-close');
  const modalTitle = document.getElementById('modal-plan-title');
  const modalPill = document.getElementById('modal-plan-pill');
  const modalPrice = document.getElementById('modal-plan-price');
  const modalCycle = document.getElementById('modal-plan-cycle');
  const modalPeriod = document.getElementById('modal-plan-period');
  const modalPerks = document.getElementById('modal-plan-perks');
  const modalAlert = document.getElementById('modal-alert-box');

  const modalForm = document.getElementById('modal-checkout-form');
  const modalSubmitBtn = document.getElementById('modal-submit-btn');
  const modalSubmitText = document.getElementById('modal-submit-text');
  const modalScrollBtn = document.getElementById('modal-scroll-to-register-btn');
  const modalConfirmActionBtn = document.getElementById('modal-confirm-action-btn');

  const modalFormPlanCode = document.getElementById('modal-form-plan-code');
  const modalFormBillingCycle = document.getElementById('modal-form-billing-cycle');
  const modalFormRole = document.getElementById('modal-form-role');
  const modalLabelOrg = document.getElementById('modal-label-org');
  const modalInputOrg = document.getElementById('modal-input-org');

  const isLoggedIn = <?= (function_exists('auth_check') && auth_check()) ? 'true' : 'false' ?>;

  let currentRole = 'founder';
  let isAnnual = false;
  let selectedPlanCode = '1_year';

  const founderData = {
    title: 'Startup Founders Raising Capital.',
    desc: 'Transparent pricing with 0% broker commission. Connect directly with check-writing angels, manage diligence rooms, and close your round faster.',
    t1: {
      code: 'free_trial',
      name: '14-Day Free Trial',
      priorityLevel: 1,
      priorityName: 'Standard Access',
      desc: 'Test drive deal rooms & verified investor network with zero commitment.',
      price: '₹0',
      period: 'No credit card required',
      cta: 'Start Free Trial',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">INCLUDED IN TRIAL:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>1 Live Pitch Deck Listing</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>Basic Cap Table Viewer</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>Public Syndicate Directory</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>Standard Community Support</span>
        </div>
      `
    },
    t2: {
      code: '1_month',
      name: '1 Month Sprint',
      priorityLevel: 2,
      priorityName: 'Verified Fast-Track',
      desc: 'Perfect for startups actively running a fast seed fundraise sprint.',
      priceMonthly: '₹2,499',
      subtextMonthly: 'Billed monthly · Cancel anytime',
      priceAnnual: '₹1,999',
      subtextAnnual: '₹23,988 billed annually (Save 20%)',
      cta: 'Choose 1 Month',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">EVERYTHING IN TRIAL, PLUS:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>3 Active Deal Rooms</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>Direct Founder-Investor DMs</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>DigiLocker KYC Badge</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>Real-Time Pitch Deck Analytics</span>
        </div>
      `
    },
    t3: {
      code: '6_months',
      name: '6 Months Dealmaker',
      priorityLevel: 3,
      priorityName: 'Featured Dealflow Priority',
      desc: 'Comprehensive runway for closing Seed to Pre-Series A funding rounds.',
      priceMonthly: '₹1,666',
      subtextMonthly: '₹9,999 billed for 6 months',
      priceAnnual: '₹1,499',
      subtextAnnual: '₹8,994 billed for 6 months (Save 33%)',
      cta: 'Get 6-Month Pass',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-purple-900 mb-2">EVERYTHING IN 1 MONTH, PLUS:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Top Featured Dealflow Placement</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Unlimited Active Deal Rooms</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Direct WhatsApp Warm Intros</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>SEBI &amp; MCA Legal SAFE Templates</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Dedicated Venture Scout Manager</span>
        </div>
      `
    },
    t4: {
      code: '1_year',
      name: '1 Year Scale Pro',
      priorityLevel: 4,
      priorityName: 'VIP Spotlight Pro',
      desc: 'Full annual suite for high-growth serial founders and scaleups.',
      priceMonthly: '₹1,499',
      subtextMonthly: '₹17,999 billed annually',
      priceAnnual: '₹1,249',
      subtextAnnual: '₹14,988 billed annually (Save 40%)',
      cta: 'Unlock 1 Year Pro',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">FULL ENTERPRISE SUITE:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>#1 Top Spotlight Placement &amp; Gold Badge</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>SPV Pooling &amp; Syndicate Tools</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>White-Label LP Data Room</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>Full Platform REST API &amp; Webhooks</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>Dedicated Partner Success Director</span>
        </div>
      `
    }
  };

  const investorData = {
    title: 'Accredited Angels & Institutional VCs.',
    desc: 'Gain instant access to verified, pre-screened seed & Series A dealflow with audited financials, cap tables, and seamless co-investment syndication.',
    t1: {
      code: 'free_trial',
      name: '14-Day Explorer Pass',
      priorityLevel: 1,
      priorityName: 'Explorer Access',
      desc: 'Browse curated deal flow summaries and public venture directories.',
      price: '₹0',
      period: 'No commitment',
      cta: 'Start Free Trial',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">INCLUDED IN PASS:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>Browse 300+ Verified Deals</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>High-Level Pitch Summaries</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-emerald-100 text-emerald-700 text-[11px] font-bold">✓</span>
          <span>Weekly Dealflow Newsletter</span>
        </div>
      `
    },
    t2: {
      code: '1_month',
      name: '1 Month Active Angel',
      priorityLevel: 2,
      priorityName: 'Verified Angel',
      desc: 'Direct access to confidential diligence rooms and verified founders.',
      priceMonthly: '₹3,499',
      subtextMonthly: 'Billed monthly · Cancel anytime',
      priceAnnual: '₹2,799',
      subtextAnnual: '₹33,588 billed annually (Save 20%)',
      cta: 'Choose 1 Month',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">EVERYTHING IN EXPLORER, PLUS:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>Audited MRR &amp; Cap Tables</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>Direct Founder 1-on-1 Messaging</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>Full Pitch Deck Downloads</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-purple-100 text-purple-700 text-[11px] font-bold">✓</span>
          <span>Co-Invest From ₹2 Lakhs</span>
        </div>
      `
    },
    t3: {
      code: '6_months',
      name: '6 Months Syndicate Lead',
      priorityLevel: 3,
      priorityName: 'Syndicate Priority',
      desc: 'For active angel syndicates and super-angels leading rounds.',
      priceMonthly: '₹2,499',
      subtextMonthly: '₹14,999 billed for 6 months',
      priceAnnual: '₹1,999',
      subtextAnnual: '₹11,994 billed for 6 months (Save 33%)',
      cta: 'Get 6-Month Pass',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-purple-900 mb-2">EVERYTHING IN ANGEL, PLUS:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Lead Syndicates &amp; SPV Pooling</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Priority Allocation in Hot Rounds</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Direct WhatsApp Founder Connect</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Automated Carry &amp; Distribution CRM</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-800 font-medium">
          <span class="nx-feature-check bg-purple-600 text-white text-[11px] font-bold">✓</span>
          <span>Dedicated Venture Scout</span>
        </div>
      `
    },
    t4: {
      code: '1_year',
      name: '1 Year Institutional Suite',
      priorityLevel: 4,
      priorityName: 'Institutional VIP',
      desc: 'Full platform pipeline for VCs, Family Offices & Accelerators.',
      priceMonthly: '₹1,999',
      subtextMonthly: '₹23,999 billed annually',
      priceAnnual: '₹1,699',
      subtextAnnual: '₹20,388 billed annually (Save 40%)',
      cta: 'Unlock 1 Year Pro',
      features: `
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">INSTITUTIONAL SUITE:</div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>Custom Institutional Research Reports</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>White-Label LP Deal Portal</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>Full Dealflow API &amp; Webhooks</span>
        </div>
        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-700">
          <span class="nx-feature-check bg-pink-100 text-pink-700 text-[11px] font-bold">✓</span>
          <span>Dedicated Partner Relationship Manager</span>
        </div>
      `
    }
  };

  function updatePricingUI() {
    const data = (currentRole === 'investor') ? investorData : founderData;

    if (dynTitle) dynTitle.textContent = data.title;
    if (dynDesc) dynDesc.textContent = data.desc;

    // Tier 1
    if (t1Name) t1Name.textContent = data.t1.name;
    if (t1Desc) t1Desc.textContent = data.t1.desc;
    if (t1Cta) t1Cta.textContent = data.t1.cta;
    if (t1Features) t1Features.innerHTML = data.t1.features;

    // Tier 2
    if (t2Name) t2Name.textContent = data.t2.name;
    if (t2Desc) t2Desc.textContent = data.t2.desc;
    if (t2Price) t2Price.textContent = isAnnual ? data.t2.priceAnnual : data.t2.priceMonthly;
    if (t2Subtext) t2Subtext.textContent = isAnnual ? data.t2.subtextAnnual : data.t2.subtextMonthly;
    if (t2Cta) t2Cta.textContent = data.t2.cta;
    if (t2Features) t2Features.innerHTML = data.t2.features;

    // Tier 3
    if (t3Name) t3Name.textContent = data.t3.name;
    if (t3Desc) t3Desc.textContent = data.t3.desc;
    if (t3Price) t3Price.textContent = isAnnual ? data.t3.priceAnnual : data.t3.priceMonthly;
    if (t3Subtext) t3Subtext.textContent = isAnnual ? data.t3.subtextAnnual : data.t3.subtextMonthly;
    if (t3Cta) t3Cta.textContent = data.t3.cta;
    if (t3Features) t3Features.innerHTML = data.t3.features;

    // Tier 4
    if (t4Name) t4Name.textContent = data.t4.name;
    if (t4Desc) t4Desc.textContent = data.t4.desc;
    if (t4Price) t4Price.textContent = isAnnual ? data.t4.priceAnnual : data.t4.priceMonthly;
    if (t4Subtext) t4Subtext.textContent = isAnnual ? data.t4.subtextAnnual : data.t4.subtextMonthly;
    if (t4Cta) t4Cta.textContent = data.t4.cta;
    if (t4Features) t4Features.innerHTML = data.t4.features;

    // Sync modal role labels if present
    if (modalFormRole) modalFormRole.value = currentRole;
    if (modalLabelOrg) {
      modalLabelOrg.innerHTML = currentRole === 'investor' ? 'INVESTOR FIRM / SYNDICATE' : 'STARTUP / COMPANY NAME <span class="text-rose-500">*</span>';
    }
    if (modalInputOrg) {
      modalInputOrg.placeholder = currentRole === 'investor' ? 'e.g. Nexus Angel Syndicate' : 'e.g. NextGen AI Labs';
    }
  }

  // Role Buttons Event Listeners
  if (roleFounderBtn) {
    roleFounderBtn.addEventListener('click', () => {
      currentRole = 'founder';
      roleFounderBtn.classList.add('active');
      if (roleInvestorBtn) roleInvestorBtn.classList.remove('active');
      updatePricingUI();
    });
  }

  if (roleInvestorBtn) {
    roleInvestorBtn.addEventListener('click', () => {
      currentRole = 'investor';
      roleInvestorBtn.classList.add('active');
      if (roleFounderBtn) roleFounderBtn.classList.remove('active');
      updatePricingUI();
    });
  }

  // Billing Buttons Event Listeners
  if (monthlyBtn) {
    monthlyBtn.addEventListener('click', () => {
      isAnnual = false;
      monthlyBtn.classList.add('active');
      if (annualBtn) annualBtn.classList.remove('active');
      updatePricingUI();
    });
  }

  if (annualBtn) {
    annualBtn.addEventListener('click', () => {
      isAnnual = true;
      annualBtn.classList.add('active');
      if (monthlyBtn) monthlyBtn.classList.remove('active');
      updatePricingUI();
    });
  }

  // Open Plan Modal Function (Global & Local)
  window.nxOpenPlanModal = function(planCode) {
    const modalEl = document.getElementById('nx-plan-modal');
    if (modalEl && modalEl.parentElement !== document.body) {
      document.body.appendChild(modalEl);
    }

    selectedPlanCode = planCode || '1_year';
    const data = (currentRole === 'investor') ? investorData : founderData;
    let planObj = data.t4;
    if (selectedPlanCode === 'free_trial') planObj = data.t1;
    else if (selectedPlanCode === '1_month') planObj = data.t2;
    else if (selectedPlanCode === '6_months') planObj = data.t3;
    else if (selectedPlanCode === '1_year') planObj = data.t4;

    if (modalTitle) modalTitle.textContent = planObj.name;
    if (modalPill) modalPill.textContent = 'Level ' + planObj.priorityLevel + ' Priority: ' + planObj.priorityName;
    
    if (modalPrice) {
      if (selectedPlanCode === 'free_trial') {
        modalPrice.textContent = '₹0';
      } else if (selectedPlanCode === '1_month') {
        modalPrice.textContent = isAnnual ? '₹23,988' : (planObj.priceMonthly || '₹2,499');
      } else if (selectedPlanCode === '6_months') {
        modalPrice.textContent = isAnnual ? '₹8,994' : '₹9,999';
      } else if (selectedPlanCode === '1_year') {
        modalPrice.textContent = isAnnual ? (planObj.subtextAnnual ? planObj.subtextAnnual.split(' ')[0] : '₹14,988') : (planObj.subtextMonthly ? planObj.subtextMonthly.split(' ')[0] : '₹17,999');
      }
    }

    if (modalCycle) {
      modalCycle.textContent = (selectedPlanCode === 'free_trial') ? '14-Day Pass' : (isAnnual ? 'Annual Billing' : 'Monthly Billing');
    }

    if (modalPeriod) {
      modalPeriod.textContent = (selectedPlanCode === 'free_trial') ? 'Zero commitment' : (isAnnual ? (planObj.priceAnnual + ' / mo') : (planObj.priceMonthly + ' / mo'));
    }

    if (modalPerks) {
      modalPerks.innerHTML = planObj.features;
    }

    if (modalFormPlanCode) modalFormPlanCode.value = selectedPlanCode;
    if (modalFormBillingCycle) modalFormBillingCycle.value = isAnnual ? 'annually' : 'monthly';
    if (modalFormRole) modalFormRole.value = currentRole;

    if (modalSubmitText) {
      modalSubmitText.textContent = '⚡ Complete Purchase & Activate ' + planObj.name;
    }

    if (modalAlert) {
      modalAlert.classList.add('hidden');
    }

    if (modalEl) {
      modalEl.classList.remove('hidden');
      modalEl.style.display = 'flex';
      modalEl.style.zIndex = '999999';
      document.body.style.overflow = 'hidden';
    }
  };

  window.nxClosePlanModal = function() {
    const modalEl = document.getElementById('nx-plan-modal');
    if (modalEl) {
      modalEl.classList.add('hidden');
      modalEl.style.display = 'none';
      document.body.style.overflow = '';
    }
  };

  if (modalClose) {
    modalClose.addEventListener('click', window.nxClosePlanModal);
  }

  const modalEl = document.getElementById('nx-plan-modal');
  if (modalEl) {
    modalEl.addEventListener('click', (e) => {
      if (e.target === modalEl) window.nxClosePlanModal();
    });
  }

  // Global event delegation for all plan CTA buttons
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.nx-plan-cta-btn');
    if (btn) {
      e.preventDefault();
      const planCode = btn.getAttribute('data-plan') || '1_year';
      window.nxOpenPlanModal(planCode);
    }
  });

  // Action Button inside Modal (Logged-In Users)
  if (modalConfirmActionBtn) {
    modalConfirmActionBtn.addEventListener('click', async () => {
      modalConfirmActionBtn.disabled = true;
      const prevText = modalConfirmActionBtn.innerHTML;
      modalConfirmActionBtn.innerHTML = '<span>⚡ Activating Priority Plan...</span>';

      try {
        const res = await fetch('<?= function_exists('url') ? url('api/subscribe.php') : 'api/subscribe.php' ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            plan_code: selectedPlanCode,
            billing_cycle: isAnnual ? 'annually' : 'monthly'
          })
        });

        const json = await res.json();
        if (json && json.success) {
          modalAlert.className = 'mb-3.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold leading-tight flex items-center gap-2';
          modalAlert.innerHTML = '<span>✓</span> <span>' + (json.message || 'Plan activated successfully!') + '</span>';
          modalAlert.classList.remove('hidden');

          setTimeout(() => {
            window.location.href = json.redirect || (currentRole === 'investor' ? 'investor/dashboard.php' : 'founder/dashboard.php');
          }, 800);
        } else {
          modalConfirmActionBtn.disabled = false;
          modalConfirmActionBtn.innerHTML = prevText;
          modalAlert.className = 'mb-3.5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold leading-tight flex items-center gap-2';
          modalAlert.innerHTML = '<span>✕</span> <span>' + (json.error || 'Activation failed.') + '</span>';
          modalAlert.classList.remove('hidden');
        }
      } catch (err) {
        modalConfirmActionBtn.disabled = false;
        modalConfirmActionBtn.innerHTML = prevText;
        modalAlert.className = 'mb-3.5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold leading-tight flex items-center gap-2';
        modalAlert.innerHTML = '<span>✕</span> <span>Network error. Please try again.</span>';
        modalAlert.classList.remove('hidden');
      }
    });
  }

  // Instant Checkout Form inside Modal (Guest Users)
  if (modalForm) {
    modalForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      modalSubmitBtn.disabled = true;
      const prevText = modalSubmitBtn.innerHTML;
      modalSubmitBtn.innerHTML = '<span>⚡ Processing Plan Activation...</span>';

      const formData = new FormData(modalForm);
      const payload = {};
      formData.forEach((value, key) => { payload[key] = value; });

      try {
        const res = await fetch('<?= function_exists('url') ? url('api/register.php') : 'api/register.php' ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (json && json.success) {
          modalAlert.className = 'mb-3.5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold leading-tight flex items-center gap-2';
          modalAlert.innerHTML = '<span>✓</span> <span>' + (json.message || 'Account created & Priority plan activated!') + '</span>';
          modalAlert.classList.remove('hidden');

          setTimeout(() => {
            window.location.href = json.redirect || (currentRole === 'investor' ? 'investor/dashboard.php' : 'founder/dashboard.php');
          }, 800);
        } else {
          modalSubmitBtn.disabled = false;
          modalSubmitBtn.innerHTML = prevText;
          modalAlert.className = 'mb-3.5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold leading-tight flex items-center gap-2';
          modalAlert.innerHTML = '<span>✕</span> <span>' + (json.error || 'Registration failed. Please try again.') + '</span>';
          modalAlert.classList.remove('hidden');
        }
      } catch (err) {
        modalSubmitBtn.disabled = false;
        modalSubmitBtn.innerHTML = prevText;
        modalAlert.className = 'mb-3.5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold leading-tight flex items-center gap-2';
        modalAlert.innerHTML = '<span>✕</span> <span>Network error. Please try again.</span>';
        modalAlert.classList.remove('hidden');
      }
    });
  }

  // Scroll to full landing page register form button
  if (modalScrollBtn) {
    modalScrollBtn.addEventListener('click', () => {
      window.nxClosePlanModal();
      const data = (currentRole === 'investor') ? investorData : founderData;
      let planObj = data.t4;
      if (selectedPlanCode === 'free_trial') planObj = data.t1;
      else if (selectedPlanCode === '1_month') planObj = data.t2;
      else if (selectedPlanCode === '6_months') planObj = data.t3;
      else if (selectedPlanCode === '1_year') planObj = data.t4;

      if (typeof window.nxSelectPlan === 'function') {
        window.nxSelectPlan(selectedPlanCode, isAnnual ? 'annually' : 'monthly', currentRole, planObj.name, planObj.priorityName, planObj.priorityLevel);
      }

      const registerSection = document.getElementById('nx-portal-register');
      if (registerSection) {
        registerSection.style.visibility = 'visible';
        registerSection.style.opacity = '1';
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // Initialize UI
  updatePricingUI();

  // ── CRITICAL: Direct binding for Tier 4 (1 Year Pro) button ──────────────
  // Belt-and-suspenders: directly bind to avoid any z-index / stacking issue.
  function bindTier4Btn() {
    const t4Btn = document.getElementById('tier4-cta-btn');
    if (t4Btn && !t4Btn._nxBound) {
      t4Btn._nxBound = true;
      t4Btn.style.cssText += '; position: relative !important; z-index: 100 !important; pointer-events: all !important; cursor: pointer !important;';
      t4Btn.addEventListener('click', function(e) {
        e.stopImmediatePropagation();
        window.nxOpenPlanModal('1_year');
      }, true); // capture phase — fires before any other handler
    }
  }
  bindTier4Btn();
  // Also re-bind on any DOM change (e.g. role/billing switch re-renders)
  setTimeout(bindTier4Btn, 500);
  setTimeout(bindTier4Btn, 1500);

})();
</script>

