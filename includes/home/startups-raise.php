<?php
/**
 * ============================================================================
 * include/home/startups-raise.php
 * ----------------------------------------------------------------------------
 * SECTION 06 — FOR STARTUPS (COLORFUL PASTEL 3D CAROUSEL EDITION)
 *
 * Features:
 * - 2-Second Auto-Rotation Loop
 * - Cinema-grade 3D cubic-bezier curve (Ultra-smooth premium feel)
 * - True 3D Z-Depth Arc with rotationY
 * - Dynamic Center Active Card: Radiant Jewel Pastel Gradient (Violet-Pink-Amber)
 * - Side Cards: Frosted Glass Pastel White with Lavender Border
 * - Responsive & Touch Swipe support
 * ============================================================================
 */
?>

<style>
  .nx-startups-section {
    background-color: #FAF5FF;
    background-image: 
      radial-gradient(at 15% 15%, #EDE9FE 0px, transparent 45%),
      radial-gradient(at 85% 85%, #FCE7F3 0px, transparent 45%),
      radial-gradient(at 50% 50%, #FFF7ED 0px, transparent 50%);
    color: #0F172A;
    font-family: 'Poppins', sans-serif;
    border-top: 1px solid rgba(221, 214, 254, 0.7);
    overflow-x: clip;
    position: relative;
    z-index: 20;
    user-select: none;
  }

  /* 3D Visual Stage with Deep Perspective */
  .nx-carousel-arena {
    perspective: 1800px;
    position: relative;
    width: 100%;
    height: 490px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px 0 35px;
    transform-style: preserve-3d;
  }

  @media (max-width: 1024px) {
    .nx-carousel-arena {
      perspective: 1200px;
      height: 450px;
    }
  }

  @media (max-width: 640px) {
    .nx-carousel-arena {
      perspective: none;
      height: 410px;
      margin: 10px 0 20px;
    }
  }

  /* Card Base Structure */
  .nx-stack-card {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 440px;
    height: 415px;
    border-radius: 28px;
    padding: 30px 28px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    backface-visibility: hidden;
    cursor: pointer;
    will-change: transform, opacity, background-color, box-shadow;
    transform-style: preserve-3d;
    transition: background 0.6s cubic-bezier(0.25, 1, 0.5, 1),
      border-color 0.6s cubic-bezier(0.25, 1, 0.5, 1),
      box-shadow 0.6s cubic-bezier(0.25, 1, 0.5, 1),
      color 0.6s cubic-bezier(0.25, 1, 0.5, 1);
  }

  @media (max-width: 640px) {
    .nx-stack-card {
      width: 88vw;
      height: 385px;
      padding: 24px 20px;
      border-radius: 24px;
    }
  }

  /* ====================================================
     ACTIVE CENTER CARD STATE (RADIANT PASTEL JEWEL)
     ==================================================== */
  .nx-stack-card.is-active {
    background: linear-gradient(135deg, #7C3AED 0%, #DB2777 50%, #EA580C 100%) !important;
    background-color: #7C3AED !important;
    border: 1.5px solid rgba(255, 255, 255, 0.5) !important;
    color: #FFFFFF !important;
    box-shadow: 0 32px 75px -12px rgba(219, 39, 119, 0.42), 0 0 0 1.5px rgba(255, 255, 255, 0.3) !important;
    cursor: default;
    z-index: 35 !important;
  }

  .nx-stack-card.is-active .nx-card-title {
    color: #FFFFFF !important;
  }

  .nx-stack-card.is-active .nx-card-desc {
    color: rgba(255, 255, 255, 0.92) !important;
  }

  .nx-stack-card.is-active .nx-card-header-line {
    border-color: rgba(255, 255, 255, 0.25) !important;
  }

  .nx-stack-card.is-active span.font-mono.tracking-wider {
    color: #FEF08A !important;
  }

  .nx-stack-card.is-active .nx-pulse-node {
    background-color: #FEF08A !important;
    box-shadow: 0 0 10px #FEF08A;
  }

  .nx-stack-card.is-active .nx-card-inner-box {
    background-color: rgba(0, 0, 0, 0.22) !important;
    border-color: rgba(255, 255, 255, 0.25) !important;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
  }

  .nx-stack-card.is-active .nx-inner-title {
    color: #FFFFFF !important;
  }

  .nx-stack-card.is-active .nx-inner-sub {
    color: rgba(255, 255, 255, 0.8) !important;
  }

  .nx-stack-card.is-active .nx-inner-pill {
    background-color: rgba(255, 255, 255, 0.18) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: #FFFFFF !important;
  }

  .nx-stack-card.is-active .nx-status-tag {
    background-color: rgba(255, 255, 255, 0.2) !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
    color: #FFFFFF !important;
  }

  .nx-stack-card.is-active .text-[#173C9F] {
    color: #FEF08A !important;
  }

  .nx-stack-card.is-active svg path[stroke="#173C9F"] {
    stroke: #FEF08A !important;
  }

  .nx-stack-card.is-active svg circle[fill="#173C9F"] {
    fill: #FEF08A !important;
  }

  .nx-stack-card.is-active svg path[stroke="#333333"] {
    stroke: rgba(255, 255, 255, 0.5) !important;
  }

  .nx-stack-card.is-active .bg-[#173C9F] {
    background-color: #FEF08A !important;
    color: #7C3AED !important;
  }

  .nx-stack-card.is-active .bg-[#262626] {
    background-color: rgba(255, 255, 255, 0.25) !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
  }

  .nx-stack-card.is-active .bg-[#111111] {
    background-color: rgba(255, 255, 255, 0.25) !important;
  }

  /* ====================================================
     INACTIVE SIDE CARD STATE (FROSTED PASTEL GLASS)
     ==================================================== */
  .nx-stack-card:not(.is-active) {
    background-color: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1.5px solid rgba(221, 214, 254, 0.8);
    color: #0F172A;
    box-shadow: 0 10px 30px -8px rgba(124, 58, 237, 0.08);
  }

  .nx-stack-card:not(.is-active) .nx-card-title {
    color: #0F172A;
  }

  .nx-stack-card:not(.is-active) .nx-card-desc {
    color: #475569;
  }

  .nx-stack-card:not(.is-active) .nx-card-header-line {
    border-color: rgba(221, 214, 254, 0.6);
  }

  .nx-stack-card:not(.is-active) .nx-card-inner-box {
    background-color: #FAF5FF;
    border-color: rgba(221, 214, 254, 0.7);
  }

  .nx-stack-card:not(.is-active) .nx-inner-title {
    color: #0F172A;
  }

  .nx-stack-card:not(.is-active) .nx-inner-sub {
    color: #64748B;
  }

  .nx-stack-card:not(.is-active) .nx-inner-pill {
    background-color: #FFFFFF;
    border-color: #E2E8F0;
    color: #334155;
  }

  .nx-stack-card:not(.is-active) .nx-status-tag {
    background-color: #EDE9FE;
    border-color: #DDD6FE;
    color: #7C3AED;
  }

  .nx-stack-card:not(.is-active) span.font-mono.tracking-wider {
    color: #7C3AED;
  }

  /* Subtle pulsing active dot */
  @keyframes nxDotPulse {
    0%, 100% {
      transform: scale(1);
      opacity: 0.8;
    }
    50% {
      transform: scale(1.3);
      opacity: 1;
    }
  }

  .nx-pulse-node {
    animation: nxDotPulse 2s ease-in-out infinite;
  }
</style>

<section id="nx-startups-raise" class="nx-startups-section py-20 sm:py-28 lg:py-36" aria-label="For Startups Platform">
  <div class="max-w-[1380px] mx-auto px-4 sm:px-6 md:px-10 lg:px-12">

    <!-- ==================================================== -->
    <!-- SECTION HEADER                                      -->
    <!-- ==================================================== -->
    <div class="max-w-[820px] mx-auto text-center mb-12 sm:mb-16">
      <div
        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-100/90 border border-purple-200 text-[11px] font-bold tracking-wider uppercase text-purple-900 mb-5 shadow-xs">
        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
        <span>FOR STARTUPS</span>
      </div>

      <h2 class="text-[clamp(34px,5.2vw,70px)] font-extrabold tracking-[-0.04em] leading-[1.04] text-slate-900 mb-5">
        YOUR COMPANY.<br>
        <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 bg-clip-text text-transparent">READY TO BE DISCOVERED.</span>
      </h2>

      <p class="text-[16px] sm:text-[18px] leading-relaxed text-slate-600 max-w-[620px] mx-auto font-normal">
        Create a clear company presence, share what you're building, and make it easier for the right capital partners to discover your startup.
      </p>
    </div>

    <!-- ==================================================== -->
    <!-- 3D CAROUSEL ARENA                                   -->
    <!-- ==================================================== -->
    <div class="nx-carousel-arena" id="nx-carousel-arena">

      <!-- CARD 01: PRESENCE -->
      <article class="nx-stack-card" data-index="0" aria-label="Card 1: Create Your Company">
        <div>
          <div class="flex items-center justify-between pb-3.5 border-b nx-card-header-line mb-5">
            <span class="text-[11px] font-mono font-bold tracking-wider text-purple-700 uppercase">01 / PRESENCE</span>
            <span class="nx-status-tag text-[10px] font-mono px-2.5 py-0.5 rounded-full font-semibold border">PROFILE READY</span>
          </div>

          <h3 class="text-[20px] sm:text-[22px] font-bold nx-card-title tracking-tight mb-2.5">
            CREATE YOUR COMPANY
          </h3>

          <p class="text-[13px] sm:text-[13.5px] leading-relaxed nx-card-desc mb-5">
            Build a clear company profile that gives people a better understanding of who you are, what you do, and where you're going.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl nx-card-inner-box border space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <span class="text-[9.5px] uppercase font-mono tracking-wider nx-inner-sub block">COMPANY PROFILE</span>
              <span class="text-[15px] font-bold nx-inner-title">NovaGrid</span>
            </div>
            <div class="text-right">
              <span class="text-[11px] font-medium nx-inner-title block">Clean Energy</span>
              <span class="text-[10px] nx-inner-sub">Ahmedabad</span>
            </div>
          </div>

          <div class="grid grid-cols-4 gap-1.5 pt-2 border-t border-purple-200/50 text-center">
            <div class="py-1 px-1 rounded border nx-inner-pill text-[10px] font-semibold">Company</div>
            <div class="py-1 px-1 rounded border nx-inner-pill text-[10px] font-semibold">Vision</div>
            <div class="py-1 px-1 rounded border nx-inner-pill text-[10px] font-semibold">Team</div>
            <div class="py-1 px-1 rounded border nx-inner-pill text-[10px] font-semibold">Story</div>
          </div>
        </div>
      </article>

      <!-- CARD 02: STORY -->
      <article class="nx-stack-card" data-index="1" aria-label="Card 2: Tell Your Story">
        <div>
          <div class="flex items-center justify-between pb-3.5 border-b nx-card-header-line mb-5">
            <span class="text-[11px] font-mono font-bold tracking-wider text-pink-700 uppercase">02 / STORY</span>
            <span class="nx-status-tag text-[10px] font-mono px-2.5 py-0.5 rounded-full font-semibold border">MILESTONES</span>
          </div>

          <h3 class="text-[20px] sm:text-[22px] font-bold nx-card-title tracking-tight mb-2.5">
            TELL YOUR STORY
          </h3>

          <p class="text-[13px] sm:text-[13.5px] leading-relaxed nx-card-desc mb-5">
            Bring your vision, product, team, and milestones together in one structured company presence.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl nx-card-inner-box border">
          <div class="flex items-center justify-between relative py-1">
            <div class="absolute left-3 right-3 top-3 h-[2px] bg-purple-200/60 z-0"></div>

            <div class="relative z-10 flex flex-col items-center">
              <span class="w-6 h-6 rounded-full bg-purple-600 text-white text-[10px] font-mono flex items-center justify-center font-bold">1</span>
              <span class="text-[10px] font-bold nx-inner-title mt-1.5">IDEA</span>
            </div>

            <div class="relative z-10 flex flex-col items-center">
              <span class="w-6 h-6 rounded-full bg-pink-600 text-white text-[10px] font-mono flex items-center justify-center font-bold">2</span>
              <span class="text-[10px] font-bold nx-inner-title mt-1.5">PRODUCT</span>
            </div>

            <div class="relative z-10 flex flex-col items-center">
              <span class="w-6 h-6 rounded-full bg-amber-500 text-white text-[10px] font-mono flex items-center justify-center font-bold">3</span>
              <span class="text-[10px] font-bold nx-inner-title mt-1.5">TRACTION</span>
            </div>

            <div class="relative z-10 flex flex-col items-center">
              <span class="w-6 h-6 rounded-full bg-white border border-emerald-500 text-emerald-600 text-[10px] font-mono flex items-center justify-center font-bold">4</span>
              <span class="text-[10px] font-bold text-emerald-600 mt-1.5">NEXT</span>
            </div>
          </div>
        </div>
      </article>

      <!-- CARD 03: DISCOVERY (INITIAL HERO CENTER) -->
      <article class="nx-stack-card is-active" data-index="2" aria-label="Card 3: Get Discovered">
        <div>
          <div class="flex items-center justify-between pb-3.5 border-b nx-card-header-line mb-5">
            <span class="text-[11px] font-mono font-bold tracking-wider uppercase">03 / DISCOVERY</span>
            <span
              class="nx-status-tag inline-flex items-center gap-1.5 text-[10.5px] font-mono px-2.5 py-0.5 rounded-full font-semibold border">
              <span class="w-1.5 h-1.5 rounded-full nx-pulse-node"></span>
              PROFILE ACTIVE
            </span>
          </div>

          <h3 class="text-[20px] sm:text-[22px] font-bold nx-card-title tracking-tight mb-2.5">
            GET DISCOVERED
          </h3>

          <p class="text-[13px] sm:text-[13.5px] leading-relaxed nx-card-desc mb-4">
            Make your startup easier to discover by investors exploring new companies, ideas, and opportunities.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl nx-card-inner-box border space-y-2.5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div
                class="w-7 h-7 rounded-lg bg-white/20 text-white border border-white/30 flex items-center justify-center font-mono font-bold text-[10.5px]">
                NG</div>
              <div>
                <span class="text-[12.5px] font-bold nx-inner-title block leading-tight">NovaGrid</span>
                <span class="text-[10px] nx-inner-sub">Clean Energy · Ahmedabad</span>
              </div>
            </div>
            <span class="text-[10px] font-mono uppercase tracking-wider text-amber-200 font-bold">STARTUP</span>
          </div>

          <div class="relative py-1">
            <svg class="w-full h-[24px] overflow-visible" viewBox="0 0 240 24" fill="none">
              <path d="M 15 5 C 80 5, 160 19, 225 19" stroke="rgba(255,255,255,0.4)" stroke-width="1.8" stroke-dasharray="3 3" />
              <path d="M 80 10 L 160 14" stroke="#FEF08A" stroke-width="2" stroke-linecap="round" />
              <circle cx="15" cy="5" r="3.5" fill="#FFFFFF" />
              <circle cx="225" cy="19" r="3.5" fill="#FEF08A" />
            </svg>
          </div>

          <div class="flex items-center justify-between pt-0.5">
            <span class="text-[10px] font-mono uppercase tracking-wider nx-inner-sub">INVESTOR</span>
            <span class="text-[11px] font-medium nx-inner-title">"Exploring startups"</span>
          </div>
        </div>
      </article>

      <!-- CARD 04: INTEREST -->
      <article class="nx-stack-card" data-index="3" aria-label="Card 4: Expressions of Interest">
        <div>
          <div class="flex items-center justify-between pb-3.5 border-b nx-card-header-line mb-5">
            <span class="text-[11px] font-mono font-bold tracking-wider text-amber-700 uppercase">04 / INTEREST</span>
            <span class="nx-status-tag text-[10px] font-mono px-2.5 py-0.5 rounded-full font-semibold border">INBOUND</span>
          </div>

          <h3 class="text-[20px] sm:text-[22px] font-bold nx-card-title tracking-tight mb-2.5">
            EXPRESSIONS OF INTEREST
          </h3>

          <p class="text-[13px] sm:text-[13.5px] leading-relaxed nx-card-desc mb-5">
            Receive interest from people who want to learn more about your company and explore the opportunity.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl nx-card-inner-box border space-y-2">
          <span class="text-[9px] uppercase font-mono tracking-wider nx-inner-sub block">INTEREST RECEIVED</span>
          <div class="flex items-center justify-between p-2.5 rounded-xl border nx-inner-pill">
            <div class="flex items-center gap-2">
              <div
                class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center font-mono text-[9.5px]">
                IN</div>
              <div>
                <span class="text-[11.5px] font-semibold nx-inner-title block">Investor Profile</span>
                <span class="text-[9.5px] nx-inner-sub">"Interested in learning more"</span>
              </div>
            </div>
            <span class="text-[10px] font-medium px-2 py-0.5 rounded border border-purple-200 text-purple-700">View</span>
          </div>
        </div>
      </article>

      <!-- CARD 05: NEXT STEP -->
      <article class="nx-stack-card" data-index="4" aria-label="Card 5: Connect">
        <div>
          <div class="flex items-center justify-between pb-3.5 border-b nx-card-header-line mb-5">
            <span class="text-[11px] font-mono font-bold tracking-wider text-emerald-700 uppercase">05 / NEXT STEP</span>
            <span class="nx-status-tag text-[10px] font-mono px-2.5 py-0.5 rounded-full font-semibold border">CONNECT</span>
          </div>

          <h3 class="text-[20px] sm:text-[22px] font-bold nx-card-title tracking-tight mb-2.5">
            CONNECT
          </h3>

          <p class="text-[13px] sm:text-[13.5px] leading-relaxed nx-card-desc mb-5">
            Continue the conversation directly and decide what happens next on your own terms.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl nx-card-inner-box border">
          <div class="flex items-center justify-between text-center gap-1.5">
            <div class="flex-1 p-2 rounded-xl border nx-inner-pill">
              <span class="text-[9px] font-mono nx-inner-sub block">STAGE</span>
              <span class="text-[11px] font-bold nx-inner-title">STARTUP</span>
            </div>
            <span class="text-[11px] text-purple-600">→</span>
            <div class="flex-1 p-2 rounded-xl border nx-inner-pill">
              <span class="text-[9px] font-mono nx-inner-sub block">DIALOGUE</span>
              <span class="text-[11px] font-bold nx-inner-title">CONNECT</span>
            </div>
            <span class="text-[11px] text-purple-600">→</span>
            <div class="flex-1 p-2 rounded-xl border nx-inner-pill">
              <span class="text-[9px] font-mono nx-inner-sub block">DECIDE</span>
              <span class="text-[11px] font-bold nx-inner-title">NEXT</span>
            </div>
          </div>
        </div>
      </article>

    </div>

    <!-- Indicator Dots (Clickable) -->
    <div class="flex items-center justify-center gap-2 mb-10" id="nx-stack-dots">
      <button class="w-2.5 h-2.5 rounded-full bg-purple-200 transition-all duration-300" data-dot="0"
        aria-label="Go to card 1"></button>
      <button class="w-2.5 h-2.5 rounded-full bg-purple-200 transition-all duration-300" data-dot="1"
        aria-label="Go to card 2"></button>
      <button class="w-8 h-2.5 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-300" data-dot="2"
        aria-label="Go to card 3"></button>
      <button class="w-2.5 h-2.5 rounded-full bg-purple-200 transition-all duration-300" data-dot="3"
        aria-label="Go to card 4"></button>
      <button class="w-2.5 h-2.5 rounded-full bg-purple-200 transition-all duration-300" data-dot="4"
        aria-label="Go to card 5"></button>
    </div>

    <!-- Editorial Progression Strip -->
    <div
      class="max-w-[960px] mx-auto py-5 mb-10 border-y border-purple-200/80 flex items-center justify-center gap-2 sm:gap-4 flex-wrap text-[10.5px] sm:text-[11.5px] font-bold text-slate-500 tracking-widest uppercase">
      <span>BUILD YOUR PRESENCE</span>
      <span class="text-purple-600">→</span>
      <span>SHARE YOUR STORY</span>
      <span class="text-pink-600">→</span>
      <span class="text-purple-900 font-extrabold">GET DISCOVERED</span>
      <span class="text-amber-600">→</span>
      <span>CONNECT</span>
    </div>

    <!-- CTAs with Pastel Colors -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
      <a href="login.php?role=startup"
        class="group h-[52px] sm:h-[56px] px-8 rounded-full bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 text-white font-bold text-[14.5px] sm:text-[15px] inline-flex items-center justify-center gap-2.5 shadow-md shadow-pink-500/25 transition-all duration-200 hover:brightness-105 hover:shadow-lg">
        <span>Create Your Company Profile</span>
        <span class="transition-transform duration-200 group-hover:translate-x-1 text-sm">→</span>
      </a>

      <a href="login.php?role=startup"
        class="group h-[52px] sm:h-[56px] px-8 rounded-full bg-white text-purple-700 border border-purple-200 font-bold text-[14.5px] sm:text-[15px] inline-flex items-center justify-center gap-2 transition-all duration-200 hover:bg-purple-50 hover:border-purple-300 shadow-xs">
        <span>Learn How It Works</span>
        <span class="text-purple-500 transition-transform duration-200 group-hover:translate-x-0.5 text-sm">→</span>
      </a>
    </div>

  </div>
</section>

<!-- ==================================================== -->
<!-- ENGINE: ULTRA-SMOOTH 2s ROTATION ENGINE              -->
<!-- ==================================================== -->
<script>
  (function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
      const arena = document.getElementById('nx-carousel-arena');
      const cards = Array.from(document.querySelectorAll('.nx-stack-card'));
      const dots = Array.from(document.querySelectorAll('#nx-stack-dots button'));
      if (!cards.length || !arena) return;

      const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      let currentIndex = 2; // Default hero: Card 03 (Discovery)
      let autoRotateTimer = null;
      let resumeTimeout = null;

      // Viewport-aware slot coordinates with true 3D Z-Depth Arc
      function computeSlot(offset) {
        const w = window.innerWidth;

        if (w <= 640) {
          if (offset === 0) return { x: 0, z: 0, scale: 1, rotY: 0, opacity: 1, zIndex: 30 };
          if (offset === 1) return { x: 260, z: -80, scale: 0.9, rotY: 0, opacity: 0.25, zIndex: 10 };
          if (offset === -1) return { x: -260, z: -80, scale: 0.9, rotY: 0, opacity: 0.25, zIndex: 10 };
          return { x: offset * 320, z: -150, scale: 0.8, rotY: 0, opacity: 0, zIndex: 0 };
        }

        if (w <= 1024) {
          if (offset === 0) return { x: 0, z: 0, scale: 1, rotY: 0, opacity: 1, zIndex: 30 };
          if (offset === 1) return { x: 205, z: -70, scale: 0.9, rotY: -7, opacity: 0.6, zIndex: 15 };
          if (offset === -1) return { x: -205, z: -70, scale: 0.9, rotY: 7, opacity: 0.6, zIndex: 15 };
          return { x: offset * 300, z: -160, scale: 0.75, rotY: 0, opacity: 0, zIndex: 0 };
        }

        // Desktop 5-card perspective stack with true depth
        if (offset === 0) return { x: 0, z: 40, scale: 1, rotY: 0, opacity: 1, zIndex: 35 };
        if (offset === 1) return { x: 215, z: -50, scale: 0.91, rotY: -9, opacity: 0.78, zIndex: 20 };
        if (offset === -1) return { x: -215, z: -50, scale: 0.91, rotY: 9, opacity: 0.78, zIndex: 20 };
        if (offset === 2) return { x: 385, z: -130, scale: 0.82, rotY: -15, opacity: 0.35, zIndex: 10 };
        if (offset === -2) return { x: -385, z: -130, scale: 0.82, rotY: 15, opacity: 0.35, zIndex: 10 };

        return { x: offset * 300, z: -180, scale: 0.7, rotY: 0, opacity: 0, zIndex: 0 };
      }

      // Render cards position with Ultra-Smooth GSAP Easing
      function updatePositions(animate = true) {
        cards.forEach((card, i) => {
          let offset = i - currentIndex;
          if (offset > 2) offset -= 5;
          if (offset < -2) offset += 5;

          const slot = computeSlot(offset);
          const isCenter = (offset === 0);

          if (isCenter) {
            card.classList.add('is-active');
          } else {
            card.classList.remove('is-active');
          }

          if (typeof gsap !== 'undefined' && animate && !isReducedMotion) {
            gsap.to(card, {
              xPercent: -50,
              yPercent: -50,
              x: slot.x,
              z: slot.z,
              scale: slot.scale,
              rotationY: slot.rotY,
              opacity: slot.opacity,
              zIndex: slot.zIndex,
              duration: 1.05,
              ease: 'power3.inOut',
              overwrite: 'auto'
            });
          } else {
            card.style.transform = `translate(-50%, -50%) translate3d(${slot.x}px, 0, ${slot.z}px) scale(${slot.scale}) rotateY(${slot.rotY}deg)`;
            card.style.opacity = slot.opacity;
            card.style.zIndex = slot.zIndex;
          }
        });

        // Update dots indicator
        dots.forEach((dot, idx) => {
          if (idx === currentIndex) {
            dot.className = 'w-8 h-2.5 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-500';
          } else {
            dot.className = 'w-2.5 h-2.5 rounded-full bg-purple-200 transition-all duration-500';
          }
        });
      }

      /* ------------------------------------------
       * 1. 2-SECOND INFINITE AUTO-ROTATION
       * ------------------------------------------ */
      function nextCard() {
        currentIndex = (currentIndex + 1) % 5;
        updatePositions(true);
      }

      function startAutoRotation() {
        if (isReducedMotion) return;
        stopAutoRotation();
        autoRotateTimer = setInterval(nextCard, 2000);
      }

      function stopAutoRotation() {
        if (autoRotateTimer) {
          clearInterval(autoRotateTimer);
          autoRotateTimer = null;
        }
      }

      function pauseAndScheduleResume() {
        stopAutoRotation();
        if (resumeTimeout) clearTimeout(resumeTimeout);
        resumeTimeout = setTimeout(startAutoRotation, 3000);
      }

      /* ------------------------------------------
       * 2. USER CLICKS & TOUCH OVERRIDES
       * ------------------------------------------ */
      cards.forEach((card, idx) => {
        card.addEventListener('click', () => {
          if (currentIndex !== idx) {
            currentIndex = idx;
            updatePositions(true);
            pauseAndScheduleResume();
          }
        });
      });

      dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => {
          currentIndex = idx;
          updatePositions(true);
          pauseAndScheduleResume();
        });
      });

      let touchStartX = 0;
      arena.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        stopAutoRotation();
      }, { passive: true });

      arena.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].clientX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 40) {
          if (diff > 0) currentIndex = (currentIndex + 1) % 5;
          else currentIndex = (currentIndex - 1 + 5) % 5;
          updatePositions(true);
        }
        pauseAndScheduleResume();
      }, { passive: true });

      window.addEventListener('resize', () => updatePositions(false));

      updatePositions(false);
      startAutoRotation();
    });
  })();
</script>