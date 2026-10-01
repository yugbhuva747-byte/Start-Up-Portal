<?php
/**
 * ============================================================================
 * include/home/ecosystem.php
 * ----------------------------------------------------------------------------
 * SECTION 02 — HOW IT WORKS / ECOSYSTEM (COLORFUL PASTEL LUXE EDITION)
 * 
 * 4 Steps in 3D Card Format with Individual Pastel Chromas:
 * 01 — STARTUP     : Soft Lavender (#8B5CF6 / #EDE9FE)
 * 02 — COMPANY     : Blush Rose (#EC4899 / #FCE7F3)
 * 03 — OPPORTUNITY : Sunset Peach (#F59E0B / #FEF3C7)
 * 04 — INVESTOR    : Fresh Mint (#10B981 / #D1FAE5)
 * ============================================================================
 */
?>

<style>
  /* ── Canvas Container with Pastel Aurora Mesh ────────────────── */
  .nx-how {
    padding: clamp(72px, 8vw, 120px) 0;
    background-color: #FAF5FF;
    background-image: 
      radial-gradient(at 15% 15%, #EDE9FE 0px, transparent 50%),
      radial-gradient(at 85% 85%, #FCE7F3 0px, transparent 50%),
      radial-gradient(at 50% 50%, #FFF7ED 0px, transparent 55%);
    position: relative;
    overflow: hidden;
    z-index: 10;
    color: #0F172A;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  .nx-how-wrap {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 clamp(20px, 4vw, 48px);
  }

  /* ── Editorial Header ────────────────────────────────────────── */
  .nx-how-header {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: clamp(40px, 5vw, 64px);
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease, transform 0.6s ease;
  }

  .nx-how-header.nx-in-view {
    opacity: 1;
    transform: translateY(0);
  }

  @media (min-width: 900px) {
    .nx-how-header {
      grid-template-columns: 1.15fr 0.85fr;
      align-items: flex-end;
      gap: 48px;
    }
  }

  /* Eyebrow Label */
  .nx-how-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #7C3AED;
    background: rgba(237, 233, 254, 0.7);
    padding: 5px 12px;
    border-radius: 999px;
    border: 1px solid rgba(196, 181, 253, 0.6);
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(139, 92, 246, 0.08);
  }

  .nx-how-eyebrow-line {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    display: inline-block;
  }

  /* Main 3-Line Heading */
  .nx-how-h2 {
    font-size: clamp(32px, 4.4vw, 54px);
    font-weight: 800;
    line-height: 1.06;
    letter-spacing: -0.035em;
    color: #0F172A;
    margin: 0;
  }

  .nx-how-h2 span {
    display: block;
  }

  .nx-how-h2 .nx-accent-line {
    background: linear-gradient(135deg, #7C3AED 0%, #DB2777 50%, #EA580C 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Short Subtitle */
  .nx-how-sub {
    font-size: clamp(15px, 1.25vw, 17.5px);
    line-height: 1.65;
    color: #475569;
    margin: 0;
    font-weight: 400;
  }

  /* ── Continuous Step Flow Tracker (Desktop) ─────────────────── */
  .nx-track-wrapper {
    position: relative;
    margin-bottom: 32px;
    display: none;
    opacity: 0;
    transition: opacity 0.6s ease;
  }

  .nx-track-wrapper.nx-in-view {
    opacity: 1;
  }

  @media (min-width: 1024px) {
    .nx-track-wrapper {
      display: block;
    }
  }

  .nx-flow-line-base {
    position: absolute;
    top: 50%;
    left: 12.5%;
    width: 75%;
    height: 3px;
    background: #E2E8F0;
    border-radius: 999px;
    transform: translateY(-50%);
    z-index: 1;
    overflow: hidden;
  }

  .nx-flow-line-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 35%, #F59E0B 70%, #10B981 100%);
    border-radius: 999px;
    transition: width 0.35s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-flow-nodes {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    position: relative;
    z-index: 2;
  }

  .nx-flow-node-item {
    display: flex;
    justify-content: center;
  }

  .nx-flow-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background-color: #FFFFFF;
    border: 2.5px solid #CBD5E1;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  }

  .nx-flow-node-item[data-dot="0"].is-active .nx-flow-dot,
  .nx-flow-node-item[data-dot="0"].is-past .nx-flow-dot {
    border-color: #8B5CF6;
    background-color: #8B5CF6;
    box-shadow: 0 0 0 5px rgba(139, 92, 246, 0.25);
    transform: scale(1.25);
  }

  .nx-flow-node-item[data-dot="1"].is-active .nx-flow-dot,
  .nx-flow-node-item[data-dot="1"].is-past .nx-flow-dot {
    border-color: #EC4899;
    background-color: #EC4899;
    box-shadow: 0 0 0 5px rgba(236, 72, 153, 0.25);
    transform: scale(1.25);
  }

  .nx-flow-node-item[data-dot="2"].is-active .nx-flow-dot,
  .nx-flow-node-item[data-dot="2"].is-past .nx-flow-dot {
    border-color: #F59E0B;
    background-color: #F59E0B;
    box-shadow: 0 0 0 5px rgba(245, 158, 11, 0.25);
    transform: scale(1.25);
  }

  .nx-flow-node-item[data-dot="3"].is-active .nx-flow-dot,
  .nx-flow-node-item[data-dot="3"].is-past .nx-flow-dot {
    border-color: #10B981;
    background-color: #10B981;
    box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.25);
    transform: scale(1.25);
  }

  /* ── 3D Cards Grid ───────────────────────────────────────────── */
  .nx-cards-3d-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    perspective: 1200px;
  }

  @media (min-width: 640px) and (max-width: 1023px) {
    .nx-cards-3d-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
  }

  @media (min-width: 1024px) {
    .nx-cards-3d-grid {
      grid-template-columns: repeat(4, 1fr);
      gap: 22px;
    }
  }

  /* ── 3D Card Shell ───────────────────────────────────────────── */
  .nx-card-3d-wrap {
    perspective: 1000px;
    transform-style: preserve-3d;
    cursor: pointer;
    user-select: none;
    opacity: 0;
    transform: translateY(35px) rotateX(8deg);
    transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-card-3d-wrap.nx-entered {
    opacity: 1;
    transform: translateY(0) rotateX(0deg);
  }

  .nx-card-3d {
    position: relative;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1.5px solid rgba(226, 232, 240, 0.9);
    border-radius: 26px;
    padding: clamp(24px, 2.4vw, 32px) clamp(20px, 2vw, 24px);
    box-shadow: 0 10px 30px -10px rgba(124, 58, 237, 0.05), 0 2px 6px rgba(0, 0, 0, 0.02);
    transform-style: preserve-3d;
    transform: rotateX(0deg) rotateY(0deg) translateZ(0px);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.35s ease,
                box-shadow 0.35s ease,
                background-color 0.35s ease,
                opacity 0.35s ease;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  /* Individual Pastel Card Themes */
  /* Step 01: Lavender */
  .nx-card-3d-wrap[data-step="0"] .nx-card-icon-node {
    background: #EDE9FE;
    border-color: #DDD6FE;
    color: #7C3AED;
  }
  .nx-card-3d-wrap[data-step="0"] .nx-card-badge {
    background: #EDE9FE;
    border-color: #DDD6FE;
    color: #6D28D9;
  }
  .nx-card-3d-wrap[data-step="0"].is-active .nx-card-3d {
    border-color: #8B5CF6;
    box-shadow: 0 20px 42px -10px rgba(139, 92, 246, 0.28), 0 0 0 1.5px #8B5CF6;
    background: #FFFFFF;
  }
  .nx-card-3d-wrap[data-step="0"].is-active .nx-3d-foot {
    color: #7C3AED;
    border-top-color: #DDD6FE;
  }
  .nx-card-3d-wrap[data-step="0"].is-active .nx-foot-indicator {
    background-color: #8B5CF6;
    box-shadow: 0 0 10px rgba(139, 92, 246, 0.8);
  }

  /* Step 02: Rose */
  .nx-card-3d-wrap[data-step="1"] .nx-card-icon-node {
    background: #FCE7F3;
    border-color: #FBCFE8;
    color: #DB2777;
  }
  .nx-card-3d-wrap[data-step="1"] .nx-card-badge {
    background: #FCE7F3;
    border-color: #FBCFE8;
    color: #BE185D;
  }
  .nx-card-3d-wrap[data-step="1"].is-active .nx-card-3d {
    border-color: #EC4899;
    box-shadow: 0 20px 42px -10px rgba(236, 72, 153, 0.28), 0 0 0 1.5px #EC4899;
    background: #FFFFFF;
  }
  .nx-card-3d-wrap[data-step="1"].is-active .nx-3d-foot {
    color: #DB2777;
    border-top-color: #FBCFE8;
  }
  .nx-card-3d-wrap[data-step="1"].is-active .nx-foot-indicator {
    background-color: #EC4899;
    box-shadow: 0 0 10px rgba(236, 72, 153, 0.8);
  }

  /* Step 03: Sunset Amber */
  .nx-card-3d-wrap[data-step="2"] .nx-card-icon-node {
    background: #FEF3C7;
    border-color: #FDE68A;
    color: #D97706;
  }
  .nx-card-3d-wrap[data-step="2"] .nx-card-badge {
    background: #FEF3C7;
    border-color: #FDE68A;
    color: #B45309;
  }
  .nx-card-3d-wrap[data-step="2"].is-active .nx-card-3d {
    border-color: #F59E0B;
    box-shadow: 0 20px 42px -10px rgba(245, 158, 11, 0.28), 0 0 0 1.5px #F59E0B;
    background: #FFFFFF;
  }
  .nx-card-3d-wrap[data-step="2"].is-active .nx-3d-foot {
    color: #D97706;
    border-top-color: #FDE68A;
  }
  .nx-card-3d-wrap[data-step="2"].is-active .nx-foot-indicator {
    background-color: #F59E0B;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.8);
  }

  /* Step 04: Mint */
  .nx-card-3d-wrap[data-step="3"] .nx-card-icon-node {
    background: #D1FAE5;
    border-color: #A7F3D0;
    color: #059669;
  }
  .nx-card-3d-wrap[data-step="3"] .nx-card-badge {
    background: #D1FAE5;
    border-color: #A7F3D0;
    color: #047857;
  }
  .nx-card-3d-wrap[data-step="3"].is-active .nx-card-3d {
    border-color: #10B981;
    box-shadow: 0 20px 42px -10px rgba(16, 185, 129, 0.28), 0 0 0 1.5px #10B981;
    background: #FFFFFF;
  }
  .nx-card-3d-wrap[data-step="3"].is-active .nx-3d-foot {
    color: #059669;
    border-top-color: #A7F3D0;
  }
  .nx-card-3d-wrap[data-step="3"].is-active .nx-foot-indicator {
    background-color: #10B981;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.8);
  }

  .nx-card-3d-wrap.is-past .nx-card-3d {
    opacity: 0.85;
  }

  .nx-card-3d-wrap.is-next .nx-card-3d {
    opacity: 0.55;
  }

  .nx-card-3d-wrap:hover .nx-card-3d {
    opacity: 1;
    transform: translateY(-4px);
  }

  /* 3D Dynamic Glare Sheen Layer */
  .nx-card-glare {
    position: absolute;
    inset: 0;
    pointer-events: none;
    border-radius: 26px;
    background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0) 65%);
    opacity: 0;
    transition: opacity 0.3s ease;
    mix-blend-mode: overlay;
    z-index: 10;
  }

  /* ── 3D Floating Layers (translateZ) ─────────────────────────── */
  .nx-3d-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    transform: translateZ(28px);
    transform-style: preserve-3d;
  }

  /* Node Icon Circle */
  .nx-card-icon-node {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    border: 1.5px solid;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transform: translateZ(36px);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
  }

  .nx-card-icon-node svg {
    width: 22px;
    height: 22px;
    transition: color 0.35s ease, transform 0.35s ease;
  }

  .nx-card-3d-wrap.is-active .nx-card-icon-node {
    transform: translateZ(42px) scale(1.08);
  }

  /* Step Pill Badge */
  .nx-card-badge {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid;
    transform: translateZ(24px);
    transition: all 0.3s ease;
  }

  /* 3D Body Content */
  .nx-3d-body {
    transform: translateZ(24px);
    transform-style: preserve-3d;
    margin-bottom: 20px;
  }

  .nx-card-title {
    font-size: clamp(19px, 1.4vw, 22px);
    font-weight: 700;
    letter-spacing: -0.025em;
    color: #0F172A;
    margin: 0 0 10px;
    line-height: 1.25;
    transform: translateZ(30px);
  }

  .nx-card-desc {
    font-size: 13.5px;
    line-height: 1.6;
    color: #475569;
    margin: 0;
    font-weight: 400;
    transform: translateZ(20px);
  }

  /* 3D Bottom Status Foot */
  .nx-3d-foot {
    transform: translateZ(22px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1.5px solid #F1F5F9;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #94A3B8;
    transition: all 0.3s ease;
  }

  .nx-foot-indicator {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: #CBD5E1;
    transition: all 0.3s ease;
  }

  .nx-card-3d-wrap.is-active .nx-foot-indicator {
    transform: scale(1.3);
  }

  /* ── Reduced Motion Accessibility ────────────────────────────── */
  @media (prefers-reduced-motion: reduce) {
    .nx-how-header,
    .nx-track-wrapper,
    .nx-card-3d-wrap,
    .nx-card-3d,
    .nx-card-icon-node,
    .nx-flow-line-fill {
      transform: none !important;
      transition: none !important;
      opacity: 1 !important;
    }
    .nx-card-glare {
      display: none !important;
    }
  }
</style>

<!-- ============================================================
     HOW IT WORKS / ECOSYSTEM SECTION (COLORFUL PASTEL EDITION)
     ============================================================ -->
<section class="nx-how border-t border-purple-100" id="nx-ecosystem" aria-label="How Nexora Works">
  <div class="nx-how-wrap">

    <!-- Editorial Header: Small Label + 3-line Heading + Short Description -->
    <div class="nx-how-header" id="nx-how-header">
      <div>
        <div class="nx-how-eyebrow">
          <span class="nx-how-eyebrow-line" aria-hidden="true"></span>
          <span>HOW IT WORKS</span>
        </div>
        <h2 class="nx-how-h2">
          <span>ONE PLATFORM.</span>
          <span>TWO SIDES.</span>
          <span class="nx-accent-line">ONE OPPORTUNITY.</span>
        </h2>
      </div>

      <div>
        <p class="nx-how-sub">
          Startups showcase what they are building with deep diligence metrics.<br>
          Accredited investors discover high-conviction allocations with zero friction.
        </p>
      </div>
    </div>

    <!-- ── Continuous Horizontal Flow Tracker (Desktop) ─────── -->
    <div class="nx-track-wrapper" id="nx-track-wrapper" aria-hidden="true">
      <div class="nx-flow-line-base">
        <div class="nx-flow-line-fill" id="nx-flow-line-fill"></div>
      </div>
      <div class="nx-flow-nodes">
        <div class="nx-flow-node-item" data-dot="0"><span class="nx-flow-dot"></span></div>
        <div class="nx-flow-node-item" data-dot="1"><span class="nx-flow-dot"></span></div>
        <div class="nx-flow-node-item" data-dot="2"><span class="nx-flow-dot"></span></div>
        <div class="nx-flow-node-item" data-dot="3"><span class="nx-flow-dot"></span></div>
      </div>
    </div>

    <!-- ── 4 Interactive 3D Cards Grid ──────────────────────── -->
    <div class="nx-cards-3d-grid" role="list">

      <!-- 01 — STARTUP -->
      <div class="nx-card-3d-wrap" data-step="0" role="listitem" tabindex="0" aria-label="Step 1: Startup - Create Profile">
        <div class="nx-card-3d">
          <div class="nx-card-glare"></div>

          <!-- Top Row: Floating Icon & Stage Badge -->
          <div class="nx-3d-top">
            <div class="nx-card-icon-node">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                <circle cx="9" cy="10" r="2.5"></circle>
                <path d="M15 8h2"></path>
                <path d="M15 12h2"></path>
                <path d="M6 16h12"></path>
              </svg>
            </div>
            <span class="nx-card-badge">01 &mdash; STARTUP</span>
          </div>

          <!-- Card Body: 3D Pop Title & Description -->
          <div class="nx-3d-body">
            <h3 class="nx-card-title">Create Profile</h3>
            <p class="nx-card-desc">
              Set up your verified company profile and share the fundamental metrics of your startup journey.
            </p>
          </div>

          <!-- Card Foot -->
          <div class="nx-3d-foot">
            <span>STARTUP PRESENCE</span>
            <span class="nx-foot-indicator"></span>
          </div>
        </div>
      </div>

      <!-- 02 — COMPANY -->
      <div class="nx-card-3d-wrap" data-step="1" role="listitem" tabindex="0" aria-label="Step 2: Company - Build Your Story">
        <div class="nx-card-3d">
          <div class="nx-card-glare"></div>

          <!-- Top Row: Floating Icon & Stage Badge -->
          <div class="nx-3d-top">
            <div class="nx-card-icon-node">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2L2 7l10 5 10-5-10-5Z"></path>
                <path d="m2 17 10 5 10-5"></path>
                <path d="m2 12 10 5 10-5"></path>
              </svg>
            </div>
            <span class="nx-card-badge">02 &mdash; COMPANY</span>
          </div>

          <!-- Card Body: 3D Pop Title & Description -->
          <div class="nx-3d-body">
            <h3 class="nx-card-title">Build Your Story</h3>
            <p class="nx-card-desc">
              Showcase verified ARR, cap table readiness, customer growth, and technical breakthroughs.
            </p>
          </div>

          <!-- Card Foot -->
          <div class="nx-3d-foot">
            <span>TRACTION &amp; VISION</span>
            <span class="nx-foot-indicator"></span>
          </div>
        </div>
      </div>

      <!-- 03 — OPPORTUNITY -->
      <div class="nx-card-3d-wrap" data-step="2" role="listitem" tabindex="0" aria-label="Step 3: Opportunity - Show What Matters">
        <div class="nx-card-3d">
          <div class="nx-card-glare"></div>

          <!-- Top Row: Floating Icon & Stage Badge -->
          <div class="nx-3d-top">
            <div class="nx-card-icon-node">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 3v3"></path>
                <path d="M12 18v3"></path>
                <path d="M3 12h3"></path>
                <path d="M18 12h3"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
            <span class="nx-card-badge">03 &mdash; OPPORTUNITY</span>
          </div>

          <!-- Card Body: 3D Pop Title & Description -->
          <div class="nx-3d-body">
            <h3 class="nx-card-title">Show What Matters</h3>
            <p class="nx-card-desc">
              Open allocations to qualified syndicates with verified data rooms and live term sheets.
            </p>
          </div>

          <!-- Card Foot -->
          <div class="nx-3d-foot">
            <span>DISCOVERABLE ROUND</span>
            <span class="nx-foot-indicator"></span>
          </div>
        </div>
      </div>

      <!-- 04 — INVESTOR -->
      <div class="nx-card-3d-wrap" data-step="3" role="listitem" tabindex="0" aria-label="Step 4: Investor - Discover & Connect">
        <div class="nx-card-3d">
          <div class="nx-card-glare"></div>

          <!-- Top Row: Floating Icon & Stage Badge -->
          <div class="nx-3d-top">
            <div class="nx-card-icon-node">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="6" cy="12" r="3.5"></circle>
                <circle cx="18" cy="12" r="3.5"></circle>
                <path d="M9.5 12h5"></path>
                <path d="M12.5 9.5 15 12l-2.5 2.5"></path>
              </svg>
            </div>
            <span class="nx-card-badge">04 &mdash; INVESTOR</span>
          </div>

          <!-- Card Body: 3D Pop Title & Description -->
          <div class="nx-3d-body">
            <h3 class="nx-card-title">Discover &amp; Connect</h3>
            <p class="nx-card-desc">
              Angels and funds access vetted deals, initiate syndicate discussions, and soft-commit seamlessly.
            </p>
          </div>

          <!-- Card Foot -->
          <div class="nx-3d-foot">
            <span>DIRECT ALLOCATION</span>
            <span class="nx-foot-indicator"></span>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

<!-- ============================================================
     3D TILT ENGINE & VIEWPORT-TRIGGERED GSAP SCROLL INTEGRATION
     ============================================================ -->
<script>
  (function () {
    'use strict';

    var isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var cardWraps = document.querySelectorAll('.nx-card-3d-wrap');
    var flowLineFill = document.getElementById('nx-flow-line-fill');
    var flowDots = document.querySelectorAll('.nx-flow-node-item');
    var sectionEl = document.getElementById('nx-ecosystem');
    var headerEl = document.getElementById('nx-how-header');
    var trackWrapper = document.getElementById('nx-track-wrapper');

    var hasEntered = false;
    var currentActive = -1;

    /* ── 1. Set Active Step & Sync ────────────────────────────── */
    function setStep(index, linePercent) {
      currentActive = index;

      cardWraps.forEach(function (wrap, i) {
        wrap.classList.remove('is-active', 'is-past', 'is-next');
        if (index === -1) {
          wrap.classList.add('is-next');
        } else if (i === index) {
          wrap.classList.add('is-active');
        } else if (i < index) {
          wrap.classList.add('is-past');
        } else {
          wrap.classList.add('is-next');
        }
      });

      flowDots.forEach(function (dot, i) {
        dot.classList.remove('is-active', 'is-past', 'is-next');
        if (index === -1) {
          dot.classList.add('is-next');
        } else if (i === index) {
          dot.classList.add('is-active');
        } else if (i < index) {
          dot.classList.add('is-past');
        } else {
          dot.classList.add('is-next');
        }
      });

      if (typeof linePercent !== 'number') {
        if (index <= 0) {
          linePercent = 0;
        } else {
          linePercent = (index / (cardWraps.length - 1)) * 100;
        }
      }
      var clamped = Math.max(0, Math.min(100, linePercent));
      if (flowLineFill) {
        flowLineFill.style.width = clamped + '%';
      }
    }

    /* ── 2. Click & Keyboard Handling ─────────────────────────── */
    cardWraps.forEach(function (wrap) {
      wrap.addEventListener('click', function () {
        var idx = parseInt(wrap.getAttribute('data-step'), 10);
        if (!isNaN(idx)) setStep(idx);
      });
      wrap.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          var idx = parseInt(wrap.getAttribute('data-step'), 10);
          if (!isNaN(idx)) setStep(idx);
        }
      });
    });

    /* ── 3. High-Performance 3D Mouse Tilt & Glare Engine ─────── */
    if (!isReducedMotion) {
      cardWraps.forEach(function (wrap) {
        var card = wrap.querySelector('.nx-card-3d');
        var glare = wrap.querySelector('.nx-card-glare');
        if (!card) return;

        var bounds;
        var rafId = null;

        function onMouseEnter() {
          bounds = wrap.getBoundingClientRect();
          if (glare) glare.style.opacity = '1';
        }

        function onMouseMove(e) {
          if (!bounds) bounds = wrap.getBoundingClientRect();

          var mouseX = e.clientX - bounds.left;
          var mouseY = e.clientY - bounds.top;

          var xPct = mouseX / bounds.width;
          var yPct = mouseY / bounds.height;

          var rotateX = (0.5 - yPct) * 16;
          var rotateY = (xPct - 0.5) * 18;

          if (rafId) cancelAnimationFrame(rafId);
          rafId = requestAnimationFrame(function () {
            card.style.transform = 'perspective(1000px) rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg) translateZ(12px)';
            if (glare) {
              glare.style.background = 'radial-gradient(circle at ' + (xPct * 100).toFixed(1) + '% ' + (yPct * 100).toFixed(1) + '%, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0) 60%)';
            }
          });
        }

        function onMouseLeave() {
          if (rafId) cancelAnimationFrame(rafId);
          card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
          if (glare) glare.style.opacity = '0';
          bounds = null;
        }

        wrap.addEventListener('mouseenter', onMouseEnter);
        wrap.addEventListener('mousemove', onMouseMove);
        wrap.addEventListener('mouseleave', onMouseLeave);
      });
    }

    /* ── 4. Trigger Entrance ONLY when section enters viewport ── */
    function triggerEntrance() {
      if (hasEntered) return;
      hasEntered = true;

      if (headerEl) headerEl.classList.add('nx-in-view');
      if (trackWrapper) trackWrapper.classList.add('nx-in-view');

      cardWraps.forEach(function (wrap, i) {
        setTimeout(function () {
          wrap.classList.add('nx-entered');
        }, i * 110);
      });
    }

    /* ── 5. GSAP ScrollTrigger Sequential Activation ───────────── */
    if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined' && !isReducedMotion && sectionEl) {
      gsap.registerPlugin(ScrollTrigger);

      var prevST = ScrollTrigger.getById('nx-3d-cards-st');
      if (prevST) prevST.kill();

      ScrollTrigger.create({
        id: 'nx-3d-cards-st',
        trigger: sectionEl,
        start: 'top 75%',
        end: 'bottom 20%',
        scrub: 0.35,
        onEnter: function () {
          triggerEntrance();
        },
        onEnterBack: function () {
          triggerEntrance();
        },
        onLeaveBack: function () {
          setStep(-1, 0);
        },
        onUpdate: function (self) {
          triggerEntrance();
          var p = self.progress;

          if (p < 0.05) {
            setStep(0, 0);
            return;
          }

          var activeIdx = 0;
          if (p < 0.28) {
            activeIdx = 0;
          } else if (p < 0.54) {
            activeIdx = 1;
          } else if (p < 0.80) {
            activeIdx = 2;
          } else {
            activeIdx = 3;
          }

          var lineP = 0;
          if (p > 0.05) {
            lineP = (p - 0.05) / 0.80;
            lineP = Math.max(0, Math.min(1, lineP));
          }

          setStep(activeIdx, lineP * 100);
        }
      });
    } else {
      triggerEntrance();
      setStep(0, 0);
    }

  })();
</script>