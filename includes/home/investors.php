<?php
/**
 * ============================================================================
 * include/home/investors.php
 * ----------------------------------------------------------------------------
 * SECTION 05 — FOR INVESTORS (COLORFUL PASTEL LUXE EDITION)
 * 
 * Bulletproof Visibility + Instant Clarity + Frosted Pastel Glass
 * Soft Violet (#EDE9FE), Blush Rose (#FCE7F3), Warm Peach (#FFF7ED)
 * ============================================================================
 */
?>

<style>
  /* ── Canvas Container with Dynamic Top Clip-Path & Pastel Aurora ── */
  #nx-investors {
    background-color: #FAF5FF !important;
    color: #0F172A !important;
    position: relative;
    z-index: 20;
    overflow: hidden;
    padding: 85px 0 110px;
    font-family: 'Poppins', sans-serif;
    margin-top: 0;
    will-change: clip-path, transform;
    transform-origin: top center;
    clip-path: inset(0% 0% 0% 0% round 50px 50px 0 0);
  }
  @media (min-width: 768px) {
    #nx-investors {
      padding: 110px 0 130px;
      margin-top: 0;
      clip-path: inset(0% 0% 0% 0% round 120px 120px 0 0);
    }
  }

  /* Ambient light orbs in colorful pastels */
  .nx-inv-glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(140px);
    pointer-events: none;
    opacity: 0.45;
  }
  .nx-inv-glow-1 {
    width: 600px;
    height: 600px;
    background: #EDE9FE;
    top: -200px;
    left: -150px;
  }
  .nx-inv-glow-2 {
    width: 550px;
    height: 550px;
    background: #FCE7F3;
    bottom: -150px;
    right: -100px;
  }
  .nx-inv-glow-3 {
    width: 450px;
    height: 450px;
    background: #FEF3C7;
    top: 40%;
    left: 40%;
    opacity: 0.35;
  }

  /* Grid mesh background texture */
  .nx-inv-grid-bg {
    position: absolute;
    inset: 0;
    background-image: 
      linear-gradient(rgba(124, 58, 237, 0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(124, 58, 237, 0.04) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(ellipse at center, rgba(0,0,0,0.8) 0%, transparent 75%);
    -webkit-mask-image: radial-gradient(ellipse at center, rgba(0,0,0,0.8) 0%, transparent 75%);
    pointer-events: none;
  }

  /* ── Frosted Pastel Step Cards ───────────────────────────────── */
  .nx-inv-card {
    background: rgba(255, 255, 255, 0.9);
    border: 1.5px solid rgba(221, 214, 254, 0.8);
    border-radius: 26px;
    padding: 32px 28px;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), 
                border-color 0.35s ease, 
                box-shadow 0.35s ease,
                background 0.35s ease;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    min-height: 410px;
    box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.08);
  }

  .nx-inv-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #8B5CF6, #EC4899, #F59E0B);
    opacity: 0;
    transition: opacity 0.35s ease;
  }

  .nx-inv-card:hover {
    background: #FFFFFF;
    border-color: #A78BFA;
    transform: translateY(-8px);
    box-shadow: 0 25px 50px -12px rgba(124, 58, 237, 0.22);
  }
  .nx-inv-card:hover::before {
    opacity: 1;
  }

  /* Watermark step number */
  .nx-inv-card-num {
    position: absolute;
    top: 18px;
    right: 22px;
    font-size: 76px;
    font-weight: 900;
    line-height: 1;
    color: rgba(139, 92, 246, 0.07);
    pointer-events: none;
    font-family: 'Poppins', sans-serif;
    user-select: none;
    transition: color 0.35s ease;
  }
  .nx-inv-card:hover .nx-inv-card-num {
    color: rgba(139, 92, 246, 0.15);
  }

  /* Tag pill styles */
  .nx-inv-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 9999px;
    background: #F3E8FF;
    border: 1px solid #DDD6FE;
    color: #6D28D9;
    transition: all 0.25s ease;
  }
  .nx-inv-chip:hover {
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    border-color: transparent;
    transform: translateY(-1px);
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
  }

  /* Flow connecting beam (Desktop) */
  .nx-inv-connector {
    position: relative;
    height: 3px;
    background: #E2E8F0;
    border-radius: 999px;
    overflow: hidden;
    margin: 36px 0 48px;
  }
  .nx-inv-connector::after {
    content: '';
    position: absolute;
    top: 0;
    left: -25%;
    width: 25%;
    height: 100%;
    background: linear-gradient(90deg, transparent, #8B5CF6, #EC4899, #F59E0B, transparent);
    animation: nxInvPulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  }
  @keyframes nxInvPulse {
    0% { left: -25%; }
    100% { left: 100%; }
  }

  /* Verification Item Row */
  .nx-v-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-radius: 14px;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    margin-bottom: 8px;
  }

  /* Signal Beam (Founder <-> VC) */
  .nx-signal-track {
    flex: 1;
    position: relative;
    height: 4px;
    background: #E2E8F0;
    border-radius: 999px;
    overflow: hidden;
  }
  .nx-signal-beam {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, #8B5CF6, #EC4899, #F59E0B);
    animation: nxSignalFlow 2.2s ease-in-out infinite;
  }
  @keyframes nxSignalFlow {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }

  /* CTAs with Colorful Pastel Gradients */
  .nx-inv-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 15px 32px;
    border-radius: 16px;
    background: linear-gradient(135deg, #7C3AED 0%, #DB2777 50%, #EA580C 100%);
    color: #FFFFFF !important;
    font-weight: 700;
    font-size: 15px;
    box-shadow: 0 10px 25px -5px rgba(219, 39, 119, 0.35);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    text-decoration: none;
  }
  .nx-inv-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px -5px rgba(219, 39, 119, 0.5);
    filter: brightness(1.05);
    color: #FFFFFF !important;
  }

  .nx-inv-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 15px 28px;
    border-radius: 16px;
    background: #FFFFFF;
    border: 1.5px solid #DDD6FE;
    color: #6D28D9 !important;
    font-weight: 700;
    font-size: 15px;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.06);
  }
  .nx-inv-btn-secondary:hover {
    background: #FAF5FF;
    border-color: #C4B5FD;
    color: #5B21B6 !important;
    transform: translateY(-2px);
  }

  #nx-investors h2,
  #nx-investors h3 {
    color: #0F172A !important;
  }
  #nx-investors p {
    color: #475569;
  }
</style>

<section id="nx-investors" class="w-full border-t border-purple-100" aria-label="For Investors — Direct Dealflow">

  <!-- Ambient Light Backdrops -->
  <div class="nx-inv-glow nx-inv-glow-1"></div>
  <div class="nx-inv-glow nx-inv-glow-2"></div>
  <div class="nx-inv-glow nx-inv-glow-3"></div>
  <div class="nx-inv-grid-bg"></div>

  <div class="max-w-[1320px] mx-auto px-5 sm:px-8 lg:px-12 relative z-10">

    <!-- ═══════════════════════════════════════════════════════════
         01. HEADER: Immediate 1-Glance Positioning
         ═══════════════════════════════════════════════════════════ -->
    <div class="text-center max-w-[820px] mx-auto mb-14 lg:mb-20">

      <!-- Badge Pill -->
      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-100/90 border border-purple-200 text-[11.5px] font-bold tracking-widest uppercase text-purple-900 mb-6 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
        <span>FOR INVESTORS &middot; DIRECT ACCESS</span>
      </div>

      <!-- Main Headline -->
      <h2 class="text-[34px] sm:text-[46px] lg:text-[62px] font-extrabold tracking-[-0.035em] leading-[1.08] mb-5 text-slate-900">
        Look Beyond The Pitch Deck.<br>
        <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 bg-clip-text text-transparent">
          Back India&rsquo;s Next Giants.
        </span>
      </h2>

      <!-- Subtitle -->
      <p class="text-[16px] sm:text-[18px] leading-relaxed text-slate-600 max-w-[660px] mx-auto">
        Verified revenue traction. Clean cap tables. Direct founder connections. Evaluate high-growth ventures with institutional confidence and zero intermediaries.
      </p>

    </div>

    <!-- ═══════════════════════════════════════════════════════════
         02. 3-STEP FLOW: 01 DISCOVER -> 02 VERIFY -> 03 CONNECT
         ═══════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">

      <!-- ── CARD 01: DISCOVER ── -->
      <div class="nx-inv-card">
        <span class="nx-inv-card-num">01</span>

        <div>
          <!-- Icon -->
          <div class="w-[52px] h-[52px] rounded-2xl bg-purple-100 border border-purple-200 flex items-center justify-center mb-6 text-purple-700 shadow-xs">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-[22px] sm:text-[24px] font-bold text-slate-900 tracking-[-0.02em] mb-3">
            01. Curated Dealflow
          </h3>

          <!-- Description -->
          <p class="text-[14px] leading-relaxed text-slate-600 mb-6">
            Filter high-barrier ventures by ARR growth, funding stage, technology moats, and proprietary IP across India&rsquo;s top emerging verticals.
          </p>

          <!-- Interactive Chips -->
          <div class="flex flex-wrap gap-2 mb-6">
            <a href="login.php?role=investor" class="nx-inv-chip no-underline">
              <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span> DeepTech
            </a>
            <a href="login.php?role=investor" class="nx-inv-chip no-underline">AI Infra</a>
            <a href="login.php?role=investor" class="nx-inv-chip no-underline">CleanEnergy</a>
            <a href="login.php?role=investor" class="nx-inv-chip no-underline">Robotics</a>
            <a href="login.php?role=investor" class="nx-inv-chip no-underline">BioDevices</a>
            <a href="login.php?role=investor" class="nx-inv-chip no-underline">+9 More</a>
          </div>
        </div>

        <!-- Footer Action -->
        <a href="login.php?role=investor" class="pt-5 border-t border-purple-100 flex items-center justify-between text-[13.5px] font-bold text-purple-700 hover:text-purple-900 transition-colors group no-underline">
          <span>Explore 250+ Startups</span>
          <span class="text-purple-600 transition-transform duration-200 group-hover:translate-x-1.5">&rarr;</span>
        </a>
      </div>

      <!-- ── CARD 02: VERIFY ── -->
      <div class="nx-inv-card">
        <span class="nx-inv-card-num">02</span>

        <div>
          <!-- Icon -->
          <div class="w-[52px] h-[52px] rounded-2xl bg-pink-100 border border-pink-200 flex items-center justify-center mb-6 text-pink-700 shadow-xs">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-[22px] sm:text-[24px] font-bold text-slate-900 tracking-[-0.02em] mb-3">
            02. Institutional Diligence
          </h3>

          <!-- Description -->
          <p class="text-[14px] leading-relaxed text-slate-600 mb-5">
            Skip preliminary back-and-forth. Access pre-vetted data rooms with verified financial statements and certified audits.
          </p>

          <!-- Verification Checklist -->
          <div class="space-y-2 mb-6">
            <div class="nx-v-row">
              <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-[12.5px] text-slate-700 font-semibold">ARR &amp; Growth Audited</span>
              </div>
              <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">VERIFIED</span>
            </div>

            <div class="nx-v-row">
              <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-[12.5px] text-slate-700 font-semibold">Clean Cap Table &amp; ESOP</span>
              </div>
              <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">VERIFIED</span>
            </div>

            <div class="nx-v-row">
              <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-purple-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-[12.5px] text-slate-700 font-semibold">Patents &amp; IP Protection</span>
              </div>
              <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-purple-100 text-purple-800 border border-purple-200">8 GRANTED</span>
            </div>
          </div>
        </div>

        <!-- Footer Action -->
        <a href="login.php?role=investor" class="pt-5 border-t border-purple-100 flex items-center justify-between text-[13.5px] font-bold text-pink-700 hover:text-pink-900 transition-colors group no-underline">
          <span>Inspect Diligence Vault</span>
          <span class="text-pink-600 transition-transform duration-200 group-hover:translate-x-1.5">&rarr;</span>
        </a>
      </div>

      <!-- ── CARD 03: CONNECT ── -->
      <div class="nx-inv-card">
        <span class="nx-inv-card-num">03</span>

        <div>
          <!-- Icon -->
          <div class="w-[52px] h-[52px] rounded-2xl bg-amber-100 border border-amber-200 flex items-center justify-center mb-6 text-amber-700 shadow-xs">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.193-9.193a4.5 4.5 0 00-6.364 0l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-[22px] sm:text-[24px] font-bold text-slate-900 tracking-[-0.02em] mb-3">
            03. Direct Co-Investment
          </h3>

          <!-- Description -->
          <p class="text-[14px] leading-relaxed text-slate-600 mb-6">
            Direct access to founders for term sheet discussions and syndicate participation. Zero broker fees or middlemen markups.
          </p>

          <!-- Visual Connection Beam -->
          <div class="flex items-center gap-3.5 px-4 py-4 rounded-xl bg-slate-50 border border-purple-100 mb-6">
            <!-- Investor Node -->
            <div class="flex flex-col items-center gap-1 shrink-0">
              <div class="w-10 h-10 rounded-full bg-purple-100 border border-purple-300 flex items-center justify-center text-[12px] font-bold font-mono text-purple-700 shadow-xs">
                VC
              </div>
              <span class="text-[10px] font-mono text-slate-500 font-semibold uppercase">You</span>
            </div>

            <!-- Signal Beam -->
            <div class="nx-signal-track">
              <div class="nx-signal-beam"></div>
            </div>

            <!-- Founder Node -->
            <div class="flex flex-col items-center gap-1 shrink-0">
              <div class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-300 flex items-center justify-center text-[12px] font-bold font-mono text-emerald-800 shadow-xs">
                F
              </div>
              <span class="text-[10px] font-mono text-emerald-700 font-semibold uppercase">Founder</span>
            </div>
          </div>
        </div>

        <!-- Footer Action -->
        <a href="login.php?role=investor" class="pt-5 border-t border-purple-100 flex items-center justify-between text-[13.5px] font-bold text-amber-700 hover:text-amber-900 transition-colors group no-underline">
          <span>Connect With Founders</span>
          <span class="text-amber-600 transition-transform duration-200 group-hover:translate-x-1.5">&rarr;</span>
        </a>
      </div>

    </div>

    <!-- Animated Connector Across Cards (Desktop) -->
    <div class="hidden md:block nx-inv-connector"></div>

    <!-- ═══════════════════════════════════════════════════════════
         03. TRUST METRICS BAR
         ═══════════════════════════════════════════════════════════ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 rounded-3xl bg-white/90 border border-purple-200/80 p-6 sm:p-8 mt-10 md:mt-0 mb-14 shadow-sm backdrop-blur-xl">
      <div class="text-center p-3 border-r border-purple-100 max-md:border-b">
        <div class="text-[28px] sm:text-[38px] font-extrabold text-slate-900 tracking-tight">250+</div>
        <div class="text-[12.5px] font-semibold text-slate-500 mt-1">Verified Startups</div>
      </div>
      <div class="text-center p-3 md:border-r border-purple-100 max-md:border-b">
        <div class="text-[28px] sm:text-[38px] font-extrabold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent tracking-tight">&pound;840Cr+</div>
        <div class="text-[12.5px] font-semibold text-slate-500 mt-1">Capital Discovered</div>
      </div>
      <div class="text-center p-3 border-r border-purple-100">
        <div class="text-[28px] sm:text-[38px] font-extrabold text-slate-900 tracking-tight">14+</div>
        <div class="text-[12.5px] font-semibold text-slate-500 mt-1">DeepTech Verticals</div>
      </div>
      <div class="text-center p-3">
        <div class="text-[28px] sm:text-[38px] font-extrabold text-emerald-600 tracking-tight">100%</div>
        <div class="text-[12.5px] font-semibold text-slate-500 mt-1">Diligence Grade</div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         04. CALL TO ACTION & COMPLIANCE NOTE
         ═══════════════════════════════════════════════════════════ -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-8 border-t border-purple-100">
      <div class="flex flex-wrap items-center gap-4">
        <a href="login.php?role=investor" class="nx-inv-btn-primary">
          <span>Explore Live Opportunities</span>
          <span>&rarr;</span>
        </a>
        <a href="login.php?role=investor" class="nx-inv-btn-secondary">
          <span>How It Works</span>
          <span>&rarr;</span>
        </a>
      </div>

      <div class="flex items-center gap-3 text-slate-600 text-[12.5px] max-w-[460px]">
        <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
        <p class="leading-relaxed">
          Platform for institutional allocators, angel syndicates, and family offices. Zero brokerage fees. Direct diligence.
        </p>
      </div>
    </div>

  </div>

</section>

<!-- ============================================================
     INTERACTION & DYNAMIC TOP CLIP-PATH SCROLL ENGINE (GSAP)
     ============================================================ -->
<script>
(function() {
  'use strict';

  var section = document.getElementById('nx-investors');

  // 1. GUARANTEED VISIBILITY SAFETY CHECK
  if (section) {
    section.style.opacity = '1';
    section.style.visibility = 'visible';
    var cards = section.querySelectorAll('.nx-inv-card');
    cards.forEach(function(c) {
      c.style.opacity = '1';
      c.style.visibility = 'visible';
    });
  }

  // 2. DYNAMIC TOP CLIP-PATH SMOOTH SCROLL ENGINE (GSAP + SCROLLTRIGGER)
  function initInvestorsTopClipPath() {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined' || !section) {
      if (section) {
        section.style.clipPath = 'none';
        section.style.transform = 'none';
      }
      return;
    }

    gsap.registerPlugin(ScrollTrigger);

    var isMobile = window.innerWidth < 768;
    var startRadius = isMobile ? '50px' : '140px';

    gsap.fromTo(section,
      {
        clipPath: 'inset(0% 0% 0% 0% round ' + startRadius + ' ' + startRadius + ' 0px 0px)',
        scale: isMobile ? 0.99 : 0.985,
        transformOrigin: 'top center'
      },
      {
        clipPath: 'inset(0% 0% 0% 0% round 0px 0px 0px 0px)',
        scale: 1,
        ease: 'none',
        scrollTrigger: {
          trigger: section,
          start: 'top bottom',
          end: 'top 12%',
          scrub: 0.9,
          invalidateOnRefresh: true
        }
      }
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initInvestorsTopClipPath);
  } else {
    initInvestorsTopClipPath();
  }

  // 3. 3D Card Hover Physics (Desktop only, passive & smooth)
  var isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
  if (!isTouch && window.innerWidth > 768) {
    var hoverCards = document.querySelectorAll('.nx-inv-card');
    hoverCards.forEach(function(card) {
      card.addEventListener('mousemove', function(e) {
        var rect = card.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        var centerX = rect.width / 2;
        var centerY = rect.height / 2;
        var rotateX = ((y - centerY) / centerY) * -5;
        var rotateY = ((x - centerX) / centerX) * 5;
        card.style.transform = 'perspective(900px) rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg) translateY(-8px)';
      });
      card.addEventListener('mouseleave', function() {
        card.style.transform = '';
      });
    });
  }
})();
</script>