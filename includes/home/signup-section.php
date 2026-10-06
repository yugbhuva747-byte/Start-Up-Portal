<?php
/**
 * ============================================================================
 * includes/home/signup-section.php
 * ----------------------------------------------------------------------------
 * SECTION 02 — DIRECT ONBOARDING & REGISTRATION PORTAL
 * Multi-Step Format: 3 Clean, Lightweight Steps
 * Step 1: Account & Role
 * Step 2: Startup / Investor Profile
 * Step 3: Security & Confirmation
 * ============================================================================
 */
?>

<style>
  /* Google Font Inter */
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap');

  /* Section wrapper */
  .nx-portal-section {
    position: relative;
    background: linear-gradient(135deg, #faf5ff 0%, #fdf2f8 30%, #f0fdf4 70%, #f0f9ff 100%);
    color: #0f172a;
    overflow: hidden;
    padding-top: clamp(108px, 13vh, 136px);
    padding-bottom: clamp(48px, 6vh, 72px);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  /* Ambient mesh orbs */
  .nx-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
    opacity: 0.55;
    animation: nxOrbFloat 22s ease-in-out infinite alternate;
  }
  .nx-orb-1 { background: radial-gradient(circle, #e9d5ff, #fbcfe8); animation-duration: 18s; }
  .nx-orb-2 { background: radial-gradient(circle, #fed7aa, #fef08a); animation-duration: 24s; animation-delay: -8s; }
  .nx-orb-3 { background: radial-gradient(circle, #a7f3d0, #bae6fd); animation-duration: 20s; animation-delay: -14s; }

  @keyframes nxOrbFloat {
    0%   { transform: translate(0, 0) scale(1); }
    50%  { transform: translate(25px, 18px) scale(1.06); }
    100% { transform: translate(-18px, 10px) scale(0.95); }
  }

  @keyframes nxPulseDot {
    0%, 100% { transform: scale(1); opacity: 1; }
    50%      { transform: scale(1.6); opacity: 0.5; }
  }

  /* Left showcase feature cards */
  .nx-feat-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 13px 16px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 4px 16px -4px rgba(99, 102, 241, 0.08);
    backdrop-filter: blur(10px);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .nx-feat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px -4px rgba(99, 102, 241, 0.14);
  }
  .nx-feat-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  /* Form Glass Card */
  .nx-glass-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    box-shadow:
      0 24px 60px -15px rgba(30, 27, 75, 0.12),
      0 8px 24px -6px rgba(0, 0, 0, 0.04),
      0 0 0 1px rgba(255, 255, 255, 0.8) inset;
  }

  /* ── STEP INDICATOR BAR ── */
  .nx-step-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 18px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
  }
  .nx-step-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    opacity: 0.55;
    transition: all 0.2s ease;
    user-select: none;
  }
  .nx-step-item.active {
    opacity: 1;
  }
  .nx-step-item.completed {
    opacity: 1;
  }
  .nx-step-circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    background: #e2e8f0;
    color: #64748b;
    border: 1.5px solid #cbd5e1;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }
  .nx-step-item.active .nx-step-circle {
    background: #4f46e5;
    color: #ffffff;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
  }
  .nx-step-item.completed .nx-step-circle {
    background: #10b981;
    color: #ffffff;
    border-color: #10b981;
  }
  .nx-step-meta {
    display: flex;
    flex-direction: column;
    text-align: left;
  }
  .nx-step-label {
    font-size: 11.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.15;
  }
  .nx-step-sub {
    font-size: 10px;
    font-weight: 500;
    color: #64748b;
  }
  .nx-step-line {
    flex: 1;
    height: 3px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
    position: relative;
    min-width: 14px;
  }
  .nx-step-fill {
    height: 100%;
    width: 0%;
    background: #10b981;
    transition: width 0.3s ease;
  }

  /* ── STEP PANELS ── */
  .nx-step-panel {
    display: none;
  }
  .nx-step-panel.active {
    display: flex;
    flex-direction: column;
    gap: 13px;
    animation: nxStepFade 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }
  @keyframes nxStepFade {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Role Switcher Tabs */
  .nx-tab-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 5px;
    background: #f1f5f9;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
  }
  .nx-tab-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    border: 1.5px solid transparent;
    cursor: pointer;
    font-family: inherit;
    text-align: left;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    background: transparent;
  }
  .nx-tab-btn.nx-tab-active {
    background: #ffffff;
    border-color: #818cf8;
    box-shadow: 0 3px 10px -2px rgba(99, 102, 241, 0.2);
  }
  .nx-tab-icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
  }
  .nx-tab-active .nx-tab-icon {
    background: #eef2ff;
    color: #4f46e5;
  }
  .nx-tab-btn:not(.nx-tab-active) .nx-tab-icon {
    background: #e2e8f0;
    color: #64748b;
  }

  /* High Readability Form Labels */
  .nx-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 5px;
    line-height: 1.35;
    letter-spacing: -0.01em;
  }
  .nx-req {
    color: #ef4444;
    margin-left: 2px;
    font-weight: 700;
  }
  .nx-label-hint {
    font-size: 11px;
    font-weight: 500;
    color: #64748b;
  }

  /* Form Inputs - Clean, High Contrast & Easy to Read */
  .nx-input {
    width: 100%;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 11px;
    color: #0f172a;
    font-size: 14px;
    font-family: inherit;
    font-weight: 500;
    padding: 10px 14px;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    box-sizing: border-box;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  }
  .nx-input:hover {
    border-color: #94a3b8;
  }
  .nx-input:focus {
    border-color: #6366f1;
    background: #ffffff;
    box-shadow: 0 0 0 3.5px rgba(99, 102, 241, 0.18);
  }
  .nx-input::placeholder {
    color: #94a3b8;
    font-weight: 400;
    font-size: 13.5px;
  }

  select.nx-input {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 15px 15px;
    padding-right: 36px !important;
    cursor: pointer;
  }

  /* Input wrapper with icons */
  .nx-input-wrap {
    position: relative;
    width: 100%;
  }
  .nx-input-wrap .nx-icon-left {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    pointer-events: none;
    display: flex;
    align-items: center;
    z-index: 2;
  }
  .nx-input-wrap .nx-input-pl {
    padding-left: 38px !important;
  }
  .nx-input-wrap .nx-input-pr {
    padding-right: 38px !important;
  }
  .nx-input-wrap .nx-input-plr {
    padding-left: 38px !important;
    padding-right: 38px !important;
  }
  .nx-input-wrap .nx-pwd-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    cursor: pointer;
    padding: 6px;
    border: none;
    background: none;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: color 0.18s, background-color 0.18s;
    z-index: 2;
  }
  .nx-input-wrap .nx-pwd-toggle:hover {
    color: #1e293b;
    background: #f1f5f9;
  }

  /* Buttons & Action Bar */
  .nx-actions-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 4px;
  }
  .nx-btn-back {
    padding: 11px 16px;
    border-radius: 12px;
    border: 1.5px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.18s;
  }
  .nx-btn-back:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
  }
  .nx-btn-next {
    flex: 1;
    padding: 12px 20px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 6px 18px -4px rgba(79, 70, 229, 0.4);
    transition: all 0.2s;
  }
  .nx-btn-next:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 10px 22px -4px rgba(79, 70, 229, 0.52);
  }

  .nx-submit-btn {
    flex: 1;
    padding: 12px 20px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-family: inherit;
    font-size: 14.5px;
    font-weight: 700;
    color: #ffffff;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
    background-size: 180% auto;
    box-shadow: 0 8px 22px -5px rgba(79, 70, 229, 0.45);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
  }
  .nx-submit-btn:hover {
    background-position: right center;
    transform: translateY(-1.5px);
    box-shadow: 0 12px 26px -4px rgba(79, 70, 229, 0.55);
  }
  .nx-submit-btn:disabled {
    opacity: 0.72;
    cursor: not-allowed;
    transform: none;
  }

  /* Gradient text */
  .nx-grad-startup {
    background: linear-gradient(135deg, #db2777 0%, #e11d48 50%, #ea580c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .nx-grad-investor {
    background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 50%, #0d9488 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* Social proof bar */
  .nx-proof-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 16px;
    backdrop-filter: blur(8px);
  }

  /* Alert box */
  .nx-alert {
    display: none;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    margin-bottom: 12px;
  }
  .nx-alert.show { display: flex; }
  .nx-alert-success { background: #f0fdf4; border: 1.5px solid #86efac; }
  .nx-alert-error   { background: #fff1f2; border: 1.5px solid #fca5a5; }
  .nx-alert-icon {
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800; flex-shrink: 0;
  }
  .nx-alert-success .nx-alert-icon { background: #dcfce7; color: #16a34a; }
  .nx-alert-error   .nx-alert-icon { background: #fee2e2; color: #dc2626; }
  .nx-alert-msg { font-size: 12.5px; font-weight: 600; line-height: 1.4; }
  .nx-alert-success .nx-alert-msg { color: #166534; }
  .nx-alert-error   .nx-alert-msg { color: #991b1b; }

  /* Plan badge */
  .nx-plan-badge {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    background: linear-gradient(135deg, #faf5ff, #fdf4ff, #fff7ed);
    border: 1.5px solid #ddd6fe;
    border-radius: 12px;
    margin-bottom: 12px;
    box-shadow: 0 2px 10px -3px rgba(124, 58, 237, 0.12);
  }
  .nx-plan-badge.show { display: flex; }

  /* Sticky stack effect */
  #nx-portal-register.nx-portal-section {
    position: sticky;
    top: var(--nx-portal-top, 0px);
    z-index: 1;
    isolation: isolate;
    min-height: 100vh;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    box-sizing: border-box;
    padding-top: clamp(108px, 13vh, 136px) !important;
    padding-bottom: clamp(48px, 6vh, 72px) !important;
  }
  #nx-portal-register .nx-portal-stage {
    position: relative;
    z-index: 10;
    transform-origin: center top;
    will-change: transform;
    width: 100%;
  }
  .nx-portal-dim {
    position: absolute;
    inset: 0;
    z-index: 20;
    background: #1a1540;
    opacity: 0;
    pointer-events: none;
    will-change: opacity;
  }

  /* Responsive Grid */
  .nx-portal-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 32px;
    align-items: center;
  }
  @media (min-width: 1024px) {
    .nx-portal-grid {
      grid-template-columns: 5fr 7fr;
      gap: 44px;
    }
  }
  @media (max-width: 640px) {
    #nx-portal-register.nx-portal-section {
      padding-top: 100px !important;
      padding-bottom: 36px !important;
    }
    .nx-portal-grid > .nx-left-col { display: none; }
    .nx-portal-grid > .nx-right-col { justify-content: center; }
    .nx-glass-card { border-radius: 20px; padding: 20px 16px !important; }
    .nx-two-col { grid-template-columns: 1fr !important; }
    .nx-step-desc, .nx-step-sub { display: none; }
  }
  @media (max-height: 760px) {
    #nx-portal-register.nx-portal-section { min-height: auto; padding-top: 100px !important; padding-bottom: 40px !important; }
  }
</style>

<section id="nx-portal-register" class="nx-portal-section w-full">

  <div class="nx-portal-dim" id="nx-portal-dim" aria-hidden="true"></div>

  <!-- Ambient orbs -->
  <div class="nx-orb nx-orb-1" style="width:520px;height:520px;top:-100px;left:-120px;"></div>
  <div class="nx-orb nx-orb-2" style="width:460px;height:460px;bottom:-80px;right:-90px;"></div>
  <div class="nx-orb nx-orb-3" style="width:380px;height:380px;top:32%;left:40%;"></div>

  <div class="nx-portal-stage w-full max-w-[1300px] mx-auto px-4 sm:px-6 lg:px-10 relative z-10">

    <div class="nx-portal-grid">

      <!-- ══════════════════════════════════════════
           LEFT COLUMN — Platform Value Showcase
           ══════════════════════════════════════════ -->
      <div class="nx-left-col" style="max-width:480px;">

        <!-- Role badge pill -->
        <div style="display:inline-flex;align-items:center;gap:8px;
                    padding:6px 14px 6px 10px;border-radius:999px;
                    background:rgba(124,58,237,0.1);border:1px solid rgba(124,58,237,0.25);
                    font-size:11px;font-weight:700;letter-spacing:0.06em;
                    text-transform:uppercase;color:#6d28d9;margin-bottom:14px;">
          <span style="width:8px;height:8px;border-radius:50%;background:#7c3aed;
                       animation:nxPulseDot 1.5s ease-in-out infinite;"></span>
          <span id="portal-showcase-pill">FOUNDER LAUNCHPAD</span>
        </div>

        <!-- Main headline -->
        <h2 id="portal-showcase-heading"
            style="font-size:clamp(28px,3.6vw,42px);font-weight:900;letter-spacing:-0.03em;
                   line-height:1.2;color:#0f172a;margin:0 0 14px;padding-top:2px;">
          Raise Direct Growth Capital
          <span class="nx-grad-startup">Without Middlemen.</span>
        </h2>

        <p id="portal-showcase-desc"
           style="font-size:14.5px;color:#475569;line-height:1.6;margin:0 0 20px;font-weight:400;">
          Connect directly with active angels, syndicates, and seed funds.
          Complete confidentiality, automated cap table sharing &amp; 0% broker fee.
        </p>

        <!-- Feature cards -->
        <div id="portal-showcase-cards" style="display:flex;flex-direction:column;gap:10px;">
          <div class="nx-feat-card">
            <div class="nx-feat-icon" style="background:#fee2e2;">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
            </div>
            <div>
              <h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Direct Investor Cheques</h4>
              <p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">Present pitch decks directly to check-writing partners, skipping the junior analyst filters.</p>
            </div>
          </div>

          <div class="nx-feat-card">
            <div class="nx-feat-icon" style="background:#fef9c3;">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
            <div>
              <h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Private &amp; Controlled Deal Room</h4>
              <p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">You approve who inspects your metrics, unit economics, and proprietary tech deck.</p>
            </div>
          </div>

          <div class="nx-feat-card">
            <div class="nx-feat-icon" style="background:#d1fae5;">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
            </div>
            <div>
              <h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Zero Commission / No Broker Cut</h4>
              <p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">100% of the capital raised goes straight to your company treasury. Zero success cuts.</p>
            </div>
          </div>
        </div>

        <!-- Social proof bar -->
        <div class="nx-proof-bar" style="margin-top:16px;">
          <div style="display:flex;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:#ede9fe;color:#6d28d9;font-size:10.5px;font-weight:800;border:2.5px solid #fff;margin-right:-6px;z-index:3;">AK</span>
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:#fce7f3;color:#be185d;font-size:10.5px;font-weight:800;border:2.5px solid #fff;margin-right:-6px;z-index:2;">VR</span>
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:#fef3c7;color:#b45309;font-size:10.5px;font-weight:800;border:2.5px solid #fff;z-index:1;">SM</span>
          </div>
          <p style="font-size:12px;color:#475569;margin:0;font-weight:500;padding-left:6px;">
            <strong style="color:#0f172a;font-weight:700;">&#8377;480 Cr+</strong> deployed across
            <strong style="color:#0f172a;font-weight:700;">320+</strong> closed rounds.
          </p>
        </div>

      </div>

      <!-- ══════════════════════════════════════════
           RIGHT COLUMN — Multi-Step Registration Card
           ══════════════════════════════════════════ -->
      <div class="nx-right-col" style="width:100%;display:flex;justify-content:flex-end;">
        <div class="nx-glass-card" style="width:100%;max-width:580px;padding:22px 26px;">

          <!-- Card Header -->
          <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;">
            <div>
              <h2 style="font-size:clamp(20px,2.4vw,25px);font-weight:800;color:#0f172a;
                         letter-spacing:-0.025em;line-height:1.2;margin:0 0 3px;padding-top:2px;">
                Create Your Account
              </h2>
              <p style="font-size:13px;color:#64748b;margin:0;line-height:1.4;">
                Complete simple 3 steps to set up your dedicated portal.
              </p>
            </div>

            <!-- Auto Fill Demo Button -->
            <button type="button" id="portal-btn-demo"
              style="flex-shrink:0;margin-left:12px;
                     display:inline-flex;align-items:center;gap:5px;
                     padding:6px 12px;border-radius:999px;
                     background:#eef2ff;border:1.5px solid #c7d2fe;
                     color:#4f46e5;font-size:11px;font-weight:700;
                     cursor:pointer;font-family:inherit;
                     transition:all 0.18s;white-space:nowrap;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
              </svg>
              Auto-fill demo
            </button>
          </div>

          <!-- Step Progress Bar -->
          <div class="nx-step-bar" id="portal-step-bar">
            <!-- Step 1 tab -->
            <div class="nx-step-item active" data-step="1" id="step-tab-1">
              <div class="nx-step-circle" id="step-circle-1">1</div>
              <div class="nx-step-meta">
                <span class="nx-step-label">Basic Info</span>
                <span class="nx-step-sub">Name &amp; Role</span>
              </div>
            </div>

            <!-- Progress line 1 -->
            <div class="nx-step-line" id="step-line-1">
              <div class="nx-step-fill" id="step-fill-1"></div>
            </div>

            <!-- Step 2 tab -->
            <div class="nx-step-item" data-step="2" id="step-tab-2">
              <div class="nx-step-circle" id="step-circle-2">2</div>
              <div class="nx-step-meta">
                <span class="nx-step-label" id="step-label-2">Venture</span>
                <span class="nx-step-sub">Profile</span>
              </div>
            </div>

            <!-- Progress line 2 -->
            <div class="nx-step-line" id="step-line-2">
              <div class="nx-step-fill" id="step-fill-2"></div>
            </div>

            <!-- Step 3 tab -->
            <div class="nx-step-item" data-step="3" id="step-tab-3">
              <div class="nx-step-circle" id="step-circle-3">3</div>
              <div class="nx-step-meta">
                <span class="nx-step-label">Security</span>
                <span class="nx-step-sub">Finish</span>
              </div>
            </div>
          </div>

          <!-- Plan badge (when arrived from pricing tier) -->
          <div id="portal-selected-plan-badge" class="nx-plan-badge">
            <div style="display:flex;align-items:center;gap:10px;">
              <div id="portal-plan-badge-icon" style="width:30px;height:30px;border-radius:9px;background:#7c3aed;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
              </div>
              <div>
                <div id="portal-plan-badge-title" style="font-size:12px;font-weight:800;color:#4c1d95;">6 Months Dealmaker</div>
                <div id="portal-plan-badge-sub" style="font-size:10.5px;font-weight:600;color:#6d28d9;">Level 3: Featured Dealflow Priority Active</div>
              </div>
            </div>
            <a href="#nx-pricing" style="font-size:10.5px;font-weight:700;color:#6d28d9;text-decoration:none;
               background:#ffffff;padding:4px 9px;border-radius:8px;
               border:1px solid #ddd6fe;">Change</a>
          </div>

          <!-- Alert notification -->
          <div id="portal-auth-alert" class="nx-alert">
            <div id="portal-auth-alert-icon" class="nx-alert-icon">&#10003;</div>
            <div id="portal-auth-alert-text" class="nx-alert-msg">Registration submitted! Setting up your portal...</div>
          </div>

          <!-- ── MULTI-STEP FORM ── -->
          <form id="portal-signup-form" novalidate>
            <input type="hidden" name="role" id="form-role" value="founder">
            <input type="hidden" name="plan_code" id="portal-plan-code" value="free_trial">
            <input type="hidden" name="billing_cycle" id="portal-billing-cycle" value="monthly">

            <!-- ──────────────────────────────────────────
                 STEP 1: Basic Info & Role Selection
                 ────────────────────────────────────────── -->
            <div class="nx-step-panel active" id="panel-step-1">

              <!-- Role Selector -->
              <div>
                <label class="nx-label">
                  <span>Account Type <span class="nx-req">*</span></span>
                  <span class="nx-label-hint">Select your role</span>
                </label>
                <div class="nx-tab-wrap">
                  <button type="button" id="portal-tab-startup" class="nx-tab-btn nx-tab-active">
                    <div class="nx-tab-icon">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.32l2.47-2.47a1.5 1.5 0 012.122 0l1.414 1.414a1.5 1.5 0 010 2.122l-2.47 2.47a4.493 4.493 0 004.32-1.757m-6.109-6.109a15.09 15.09 0 012.448-2.448"/>
                      </svg>
                    </div>
                    <div>
                      <div style="font-size:13px;font-weight:700;color:#0f172a;line-height:1.2;">Startup Founder</div>
                      <div style="font-size:10.5px;font-weight:600;color:#4f46e5;margin-top:2px;">Raising Capital</div>
                    </div>
                  </button>

                  <button type="button" id="portal-tab-investor" class="nx-tab-btn">
                    <div class="nx-tab-icon">
                      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                      </svg>
                    </div>
                    <div>
                      <div style="font-size:13px;font-weight:700;color:#475569;line-height:1.2;">Angel / Investor</div>
                      <div style="font-size:10.5px;font-weight:600;color:#64748b;margin-top:2px;">Deploying Capital</div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Full Name -->
              <div>
                <label class="nx-label" for="portal-name">
                  <span>Full Legal Name <span class="nx-req">*</span></span>
                </label>
                <div class="nx-input-wrap">
                  <span class="nx-icon-left">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                  </span>
                  <input type="text" id="portal-name" name="name" required
                         placeholder="e.g. Aarav Sharma" class="nx-input nx-input-pl">
                </div>
              </div>

              <!-- Work Email + Phone -->
              <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:12px;" class="nx-two-col">
                <div>
                  <label class="nx-label" for="portal-email">
                    <span>Work Email <span class="nx-req">*</span></span>
                  </label>
                  <div class="nx-input-wrap">
                    <span class="nx-icon-left">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                      </svg>
                    </span>
                    <input type="email" id="portal-email" name="email" required
                           autocomplete="email" placeholder="name@company.com"
                           class="nx-input nx-input-pl">
                  </div>
                </div>

                <div>
                  <label class="nx-label" for="portal-phone">
                    <span>Phone Number</span>
                    <span class="nx-label-hint">Optional</span>
                  </label>
                  <div class="nx-input-wrap">
                    <span class="nx-icon-left">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                      </svg>
                    </span>
                    <input type="tel" id="portal-phone" name="phone"
                           placeholder="+91 98765 43210" class="nx-input nx-input-pl">
                  </div>
                </div>
              </div>

              <!-- Step 1 Action -->
              <div class="nx-actions-row">
                <button type="button" id="btn-next-step1" class="nx-btn-next">
                  <span>Continue to Profile Details</span>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                  </svg>
                </button>
              </div>

            </div>

            <!-- ──────────────────────────────────────────
                 STEP 2: Venture / Investor Profile Details
                 ────────────────────────────────────────── -->
            <div class="nx-step-panel" id="panel-step-2">

              <!-- FOUNDER FIELDS -->
              <div id="founder-fields">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;" class="nx-two-col">
                  <div>
                    <label class="nx-label" for="founder-company">
                      <span>Startup / Company Name <span class="nx-req">*</span></span>
                    </label>
                    <div class="nx-input-wrap">
                      <span class="nx-icon-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/>
                        </svg>
                      </span>
                      <input type="text" id="founder-company" name="company_name"
                             placeholder="e.g. TechPulse AI"
                             class="nx-input nx-input-pl">
                    </div>
                  </div>

                  <div>
                    <label class="nx-label" for="founder-industry">
                      <span>Primary Industry</span>
                    </label>
                    <select id="founder-industry" name="industry" class="nx-input">
                      <option value="AI &amp; Enterprise SaaS">AI &amp; Enterprise SaaS</option>
                      <option value="FinTech &amp; Web3">FinTech &amp; Web3</option>
                      <option value="HealthTech &amp; Bio">HealthTech &amp; Bio</option>
                      <option value="CleanTech &amp; Climate">CleanTech &amp; Climate</option>
                      <option value="Consumer &amp; D2C">Consumer &amp; D2C</option>
                      <option value="Robotics &amp; DeepTech">Robotics &amp; DeepTech</option>
                    </select>
                  </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;" class="nx-two-col">
                  <div>
                    <label class="nx-label" for="founder-stage">
                      <span>Venture Stage</span>
                    </label>
                    <select id="founder-stage" name="stage" class="nx-input">
                      <option value="Seed">Seed Round (&#8377;1 Cr &#8211; &#8377;5 Cr)</option>
                      <option value="Pre-Seed">Pre-Seed / Prototype (&#8377;25L &#8211; &#8377;1 Cr)</option>
                      <option value="Pre-Series A">Pre-Series A (&#8377;5 Cr &#8211; &#8377;15 Cr)</option>
                      <option value="Series A+">Series A+ (&#8377;15 Cr+)</option>
                      <option value="Idea">Concept / Early Validation</option>
                    </select>
                  </div>

                  <div>
                    <label class="nx-label" for="founder-designation">
                      <span>Your Designation</span>
                    </label>
                    <input type="text" id="founder-designation" name="designation"
                           value="Founder &amp; CEO" placeholder="e.g. Founder &amp; CEO"
                           class="nx-input">
                  </div>
                </div>

                <div>
                  <label class="nx-label" for="founder-pitch">
                    <span>One-Line Elevator Pitch</span>
                    <span class="nx-label-hint">Max 120 chars</span>
                  </label>
                  <input type="text" id="founder-pitch" name="pitch"
                         placeholder="e.g. AI-driven logistics intelligence optimizing last-mile delivery."
                         class="nx-input">
                </div>
              </div>

              <!-- INVESTOR FIELDS -->
              <div id="investor-fields" style="display:none;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;" class="nx-two-col">
                  <div>
                    <label class="nx-label" for="investor-type">
                      <span>Investor Type</span>
                    </label>
                    <select id="investor-type" name="investor_type" class="nx-input">
                      <option value="Angel Investor">Individual Angel Investor</option>
                      <option value="Syndicate Lead">Angel Syndicate Lead</option>
                      <option value="Venture Capital Partner">Institutional VC Partner</option>
                      <option value="Family Office">Family Office Principal</option>
                      <option value="Corporate VC">Corporate Venture Capital (CVC)</option>
                    </select>
                  </div>

                  <div>
                    <label class="nx-label" for="investor-ticket">
                      <span>Cheque Size</span>
                    </label>
                    <select id="investor-ticket" name="ticket_range" class="nx-input">
                      <option value="2-10L">&#8377;2 Lakhs &#8211; &#8377;10 Lakhs</option>
                      <option value="10-50L" selected>&#8377;10 Lakhs &#8211; &#8377;50 Lakhs (Popular)</option>
                      <option value="50L-2Cr">&#8377;50 Lakhs &#8211; &#8377;2 Crores</option>
                      <option value="2Cr+">&#8377;2 Crores+ (Institutional)</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="nx-label" for="investor-sectors">
                    <span>Preferred Focus Sectors</span>
                  </label>
                  <input type="text" id="investor-sectors" name="preferred_industries"
                         value="AI &amp; DeepTech, SaaS, FinTech, HealthTech"
                         placeholder="e.g. AI &amp; DeepTech, SaaS, FinTech"
                         class="nx-input">
                </div>
              </div>

              <!-- Step 2 Actions -->
              <div class="nx-actions-row">
                <button type="button" id="btn-back-step2" class="nx-btn-back">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                  </svg>
                  <span>Back</span>
                </button>
                <button type="button" id="btn-next-step2" class="nx-btn-next">
                  <span>Continue to Security</span>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                  </svg>
                </button>
              </div>

            </div>

            <!-- ──────────────────────────────────────────
                 STEP 3: Security & Verification
                 ────────────────────────────────────────── -->
            <div class="nx-step-panel" id="panel-step-3">

              <!-- Password + Confirm Password -->
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="nx-two-col">
                <div>
                  <label class="nx-label" for="portal-password">
                    <span>Password <span class="nx-req">*</span></span>
                    <span class="nx-label-hint">Min 6 chars</span>
                  </label>
                  <div class="nx-input-wrap">
                    <span class="nx-icon-left">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                      </svg>
                    </span>
                    <input type="password" id="portal-password" name="password" required
                           autocomplete="new-password" placeholder="Create password"
                           class="nx-input nx-input-plr">
                    <button type="button" id="portal-toggle-pwd1" class="nx-pwd-toggle" title="Show password" aria-label="Toggle password visibility">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <div>
                  <label class="nx-label" for="portal-confirm-password">
                    <span>Confirm Password <span class="nx-req">*</span></span>
                  </label>
                  <div class="nx-input-wrap">
                    <input type="password" id="portal-confirm-password" name="password_confirmation" required
                           autocomplete="new-password" placeholder="Re-enter password"
                           class="nx-input nx-input-pr">
                    <button type="button" id="portal-toggle-pwd2" class="nx-pwd-toggle" title="Show password" aria-label="Toggle confirm password visibility">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- City + Country -->
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="nx-two-col">
                <div>
                  <label class="nx-label" for="portal-city">
                    <span>City</span>
                  </label>
                  <div class="nx-input-wrap">
                    <span class="nx-icon-left">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                      </svg>
                    </span>
                    <input type="text" id="portal-city" name="city" value="Bengaluru"
                           placeholder="e.g. Bengaluru" class="nx-input nx-input-pl">
                  </div>
                </div>

                <div>
                  <label class="nx-label" for="portal-country">
                    <span>Country</span>
                  </label>
                  <div class="nx-input-wrap">
                    <span class="nx-icon-left">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253"/>
                      </svg>
                    </span>
                    <input type="text" id="portal-country" name="country" value="India"
                           placeholder="e.g. India" class="nx-input nx-input-pl">
                  </div>
                </div>
              </div>

              <!-- Terms Checkbox -->
              <label style="display:flex;align-items:flex-start;gap:9px;cursor:pointer;margin-top:2px;">
                <input type="checkbox" id="portal-terms" name="terms" required checked
                       style="width:16px;height:16px;margin-top:2px;accent-color:#4f46e5;flex-shrink:0;cursor:pointer;">
                <span style="font-size:12px;color:#475569;line-height:1.5;">
                  I agree to the
                  <a href="<?= function_exists('url') ? url('legal/terms.php') : '#' ?>"
                     style="color:#4f46e5;font-weight:600;text-decoration:underline;">Terms of Service</a>,
                  <a href="<?= function_exists('url') ? url('legal/privacy.php') : '#' ?>"
                     style="color:#4f46e5;font-weight:600;text-decoration:underline;">Privacy Policy</a>,
                  and confidentiality covenants.
                </span>
              </label>

              <!-- Step 3 Actions (Submit) -->
              <div class="nx-actions-row">
                <button type="button" id="btn-back-step3" class="nx-btn-back">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                  </svg>
                  <span>Back</span>
                </button>
                <button type="submit" id="portal-submit-btn" class="nx-submit-btn">
                  <span id="portal-submit-text">Complete Founder Registration</span>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                  </svg>
                </button>
              </div>

            </div>

            <!-- Sign in redirect -->
            <div style="text-align:center;padding-top:10px;margin-top:10px;border-top:1px solid #f1f5f9;">
              <span style="font-size:12.5px;color:#64748b;">Already have an account?</span>
              <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>"
                 style="font-size:12.5px;font-weight:700;color:#4f46e5;text-decoration:none;margin-left:4px;
                        display:inline-flex;align-items:center;gap:3px;">
                Sign In to Portal <span>&#8594;</span>
              </a>
            </div>

          </form>
        </div>
      </div>

    </div>
  </div>

  <script>
  (function () {
    'use strict';
    var $ = function(id){ return document.getElementById(id); };

    var tabStartup      = $('portal-tab-startup');
    var tabInvestor     = $('portal-tab-investor');
    var founderFields   = $('founder-fields');
    var investorFields  = $('investor-fields');
    var formRole        = $('form-role');
    var nameInput       = $('portal-name');
    var emailInput      = $('portal-email');
    var phoneInput      = $('portal-phone');
    var founderCompany  = $('founder-company');
    var founderIndustry = $('founder-industry');
    var founderStage    = $('founder-stage');
    var founderDesig    = $('founder-designation');
    var founderPitch    = $('founder-pitch');
    var investorType    = $('investor-type');
    var investorTicket  = $('investor-ticket');
    var investorSectors = $('investor-sectors');
    var passInput       = $('portal-password');
    var cpassInput      = $('portal-confirm-password');
    var cityInput       = $('portal-city');
    var countryInput    = $('portal-country');
    var termsCheck      = $('portal-terms');
    var submitBtn       = $('portal-submit-btn');
    var submitText      = $('portal-submit-text');
    var alertBox        = $('portal-auth-alert');
    var alertIcon       = $('portal-auth-alert-icon');
    var alertMsg        = $('portal-auth-alert-text');
    var btnDemo         = $('portal-btn-demo');
    var form            = $('portal-signup-form');
    var showcasePill    = $('portal-showcase-pill');
    var showcaseHead    = $('portal-showcase-heading');
    var showcaseDesc    = $('portal-showcase-desc');
    var showcaseCards   = $('portal-showcase-cards');
    var step2Label      = $('step-label-2');

    /* Step elements */
    var panel1          = $('panel-step-1');
    var panel2          = $('panel-step-2');
    var panel3          = $('panel-step-3');
    var tabStep1        = $('step-tab-1');
    var tabStep2        = $('step-tab-2');
    var tabStep3        = $('step-tab-3');
    var circle1         = $('step-circle-1');
    var circle2         = $('step-circle-2');
    var circle3         = $('step-circle-3');
    var fill1           = $('step-fill-1');
    var fill2           = $('step-fill-2');
    var btnNext1        = $('btn-next-step1');
    var btnNext2        = $('btn-next-step2');
    var btnBack2        = $('btn-back-step2');
    var btnBack3        = $('btn-back-step3');

    var currentStep = 1;
    var currentRole = 'founder';

    var investorCards = '<div class="nx-feat-card"><div class="nx-feat-icon" style="background:#ede9fe;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></div><div><h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Pre-Screened Investment Pipeline</h4><p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">Discover high-potential startups matching your sector and ticket size preferences.</p></div></div>'
      + '<div class="nx-feat-card"><div class="nx-feat-icon" style="background:#fce7f3;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#be185d" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div><div><h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Audited Financials &amp; Cap Tables</h4><p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">Review complete diligence folders, MRR proofs, and ownership structures before committing.</p></div></div>'
      + '<div class="nx-feat-card"><div class="nx-feat-icon" style="background:#d1fae5;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div><div><h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Co-Invest With Top Angels</h4><p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">Participate in vetted syndicate rounds alongside experienced institutional allocators.</p></div></div>';

    var startupCards = '<div class="nx-feat-card"><div class="nx-feat-icon" style="background:#fee2e2;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg></div><div><h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Direct Investor Cheques</h4><p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">Present pitch decks directly to check-writing partners, skipping the junior analyst filters.</p></div></div>'
      + '<div class="nx-feat-card"><div class="nx-feat-icon" style="background:#fef9c3;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div><div><h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Private &amp; Controlled Deal Room</h4><p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">You approve who inspects your metrics, unit economics, and proprietary tech deck.</p></div></div>'
      + '<div class="nx-feat-card"><div class="nx-feat-icon" style="background:#d1fae5;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div><div><h4 style="font-size:13px;font-weight:700;color:#0f172a;margin:0 0 2px;">Zero Commission / No Broker Cut</h4><p style="font-size:12px;color:#64748b;line-height:1.45;margin:0;">100% of the capital raised goes straight to your company treasury. Zero success cuts.</p></div></div>';

    /* ── STEP NAVIGATION CONTROLLER ── */
    function goToStep(step) {
      currentStep = step;
      hideAlert();

      /* Panels toggle */
      if (panel1) panel1.classList[step === 1 ? 'add' : 'remove']('active');
      if (panel2) panel2.classList[step === 2 ? 'add' : 'remove']('active');
      if (panel3) panel3.classList[step === 3 ? 'add' : 'remove']('active');

      /* Indicator 1 */
      if (tabStep1) {
        tabStep1.classList[step === 1 ? 'add' : 'remove']('active');
        tabStep1.classList[step > 1 ? 'add' : 'remove']('completed');
        if (circle1) circle1.innerHTML = (step > 1) ? '&#10003;' : '1';
      }

      /* Progress track 1 */
      if (fill1) fill1.style.width = (step >= 2) ? '100%' : '0%';

      /* Indicator 2 */
      if (tabStep2) {
        tabStep2.classList[step === 2 ? 'add' : 'remove']('active');
        tabStep2.classList[step > 2 ? 'add' : 'remove']('completed');
        if (circle2) circle2.innerHTML = (step > 2) ? '&#10003;' : '2';
      }

      /* Progress track 2 */
      if (fill2) fill2.style.width = (step >= 3) ? '100%' : '0%';

      /* Indicator 3 */
      if (tabStep3) {
        tabStep3.classList[step === 3 ? 'add' : 'remove']('active');
        if (circle3) circle3.innerHTML = '3';
      }
    }

    /* Step 1 Validation */
    function validateStep1() {
      var name = nameInput ? nameInput.value.trim() : '';
      var email = emailInput ? emailInput.value.trim() : '';
      if (!name) {
        showAlert('Please enter your full legal name.', 'error');
        if (nameInput) nameInput.focus();
        return false;
      }
      if (!email || !email.includes('@') || !email.includes('.')) {
        showAlert('Please enter a valid work email address.', 'error');
        if (emailInput) emailInput.focus();
        return false;
      }
      return true;
    }

    /* Step 2 Validation */
    function validateStep2() {
      if (currentRole === 'founder') {
        var comp = founderCompany ? founderCompany.value.trim() : '';
        if (!comp) {
          showAlert('Please enter your startup / company name.', 'error');
          if (founderCompany) founderCompany.focus();
          return false;
        }
      }
      return true;
    }

    /* Next & Back handlers */
    if (btnNext1) {
      btnNext1.addEventListener('click', function() {
        if (validateStep1()) goToStep(2);
      });
    }

    if (btnNext2) {
      btnNext2.addEventListener('click', function() {
        if (validateStep2()) goToStep(3);
      });
    }

    if (btnBack2) {
      btnBack2.addEventListener('click', function() {
        goToStep(1);
      });
    }

    if (btnBack3) {
      btnBack3.addEventListener('click', function() {
        goToStep(2);
      });
    }

    /* Clickable step tabs */
    if (tabStep1) tabStep1.addEventListener('click', function() { goToStep(1); });
    if (tabStep2) tabStep2.addEventListener('click', function() { if (validateStep1()) goToStep(2); });
    if (tabStep3) tabStep3.addEventListener('click', function() { if (validateStep1() && validateStep2()) goToStep(3); });

    /* Role switching */
    function setRole(role) {
      currentRole = role;
      if (formRole) formRole.value = role;
      var isF = (role === 'founder');

      /* tab active states */
      if (tabStartup)  tabStartup.classList[isF ? 'add' : 'remove']('nx-tab-active');
      if (tabInvestor) tabInvestor.classList[isF ? 'remove' : 'add']('nx-tab-active');

      /* field panels */
      if (founderFields)  founderFields.style.display  = isF ? 'block' : 'none';
      if (investorFields) investorFields.style.display = isF ? 'none'  : 'block';

      /* Step 2 indicator subtitle */
      if (step2Label) step2Label.textContent = isF ? 'Venture' : 'Investor';

      /* left column */
      if (showcasePill) showcasePill.textContent = isF ? 'FOUNDER LAUNCHPAD' : 'INVESTOR PORTAL';
      if (showcaseHead) showcaseHead.innerHTML   = isF
        ? 'Raise Direct Growth Capital <span class="nx-grad-startup">Without Middlemen.</span>'
        : 'Deploy Capital Into <span class="nx-grad-investor">Vetted Dealflow.</span>';
      if (showcaseDesc) showcaseDesc.textContent = isF
        ? 'Connect directly with active angels, syndicates, and seed funds. Complete confidentiality, automated cap table sharing & 0% broker fee.'
        : 'Access a curated pipeline of pre-screened startups. Full diligence folders, co-invest opportunities, and zero sourcing overhead.';
      if (showcaseCards) showcaseCards.innerHTML = isF ? startupCards : investorCards;

      /* submit label */
      if (submitText) submitText.textContent = isF
        ? 'Complete Founder Registration' : 'Complete Investor Registration';

      hideAlert();
    }

    if (tabStartup)  tabStartup.addEventListener('click',  function(){ setRole('founder'); });
    if (tabInvestor) tabInvestor.addEventListener('click', function(){ setRole('investor'); });

    /* Auto-fill demo */
    if (btnDemo) {
      btnDemo.addEventListener('click', function() {
        if (currentRole === 'investor') {
          if (nameInput)       nameInput.value       = 'Vikram Singhania';
          if (emailInput)      emailInput.value      = 'vikram.investor@matrixcap.in';
          if (phoneInput)      phoneInput.value      = '+91 98234 56789';
          if (investorType)    investorType.value    = 'Syndicate Lead';
          if (investorTicket)  investorTicket.value  = '10-50L';
          if (investorSectors) investorSectors.value = 'AI & DeepTech, SaaS, FinTech, HealthTech';
          if (passInput)       passInput.value       = 'Investor#2026';
          if (cpassInput)      cpassInput.value      = 'Investor#2026';
        } else {
          if (nameInput)       nameInput.value       = 'Aarav Sharma';
          if (emailInput)      emailInput.value      = 'aarav@techpulse.ai';
          if (phoneInput)      phoneInput.value      = '+91 98765 43210';
          if (founderCompany)  founderCompany.value  = 'TechPulse AI';
          if (founderIndustry) founderIndustry.value = 'AI & Enterprise SaaS';
          if (founderStage)    founderStage.value    = 'Seed';
          if (founderDesig)    founderDesig.value    = 'Founder & CEO';
          if (founderPitch)    founderPitch.value    = 'AI-driven logistics intelligence optimizing last-mile delivery.';
          if (passInput)       passInput.value       = 'Founder#2026';
          if (cpassInput)      cpassInput.value      = 'Founder#2026';
        }
        showAlert('Demo data filled! You can inspect each step or proceed to submit.', 'success');
      });
    }

    /* Password toggles */
    function setupToggle(btnId, inputId) {
      var b = $(btnId), inp = $(inputId);
      if (b && inp) b.addEventListener('click', function(){ inp.type = inp.type === 'password' ? 'text' : 'password'; });
    }
    setupToggle('portal-toggle-pwd1', 'portal-password');
    setupToggle('portal-toggle-pwd2', 'portal-confirm-password');

    /* Alert helpers */
    function showAlert(msg, type) {
      if (!alertBox) return;
      alertBox.className = 'nx-alert show ' + (type === 'error' ? 'nx-alert-error' : 'nx-alert-success');
      if (alertIcon) alertIcon.textContent = type === 'error' ? '\u2715' : '\u2713';
      if (alertMsg)  alertMsg.textContent  = msg;
    }
    function hideAlert() { if (alertBox) alertBox.className = 'nx-alert'; }

    /* Final Form submit */
    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var name    = nameInput    ? nameInput.value.trim()    : '';
        var email   = emailInput   ? emailInput.value.trim()   : '';
        var phone   = phoneInput   ? phoneInput.value.trim()   : '';
        var pass    = passInput    ? passInput.value            : '';
        var cpass   = cpassInput   ? cpassInput.value           : '';
        var city    = cityInput    ? cityInput.value.trim()     : 'Bengaluru';
        var country = countryInput ? countryInput.value.trim()  : 'India';
        var terms   = termsCheck   ? termsCheck.checked         : false;

        if (!name) {
          goToStep(1);
          showAlert('Please enter your full legal name.', 'error');
          if (nameInput) nameInput.focus();
          return;
        }
        if (!email || !email.includes('@')) {
          goToStep(1);
          showAlert('Please enter a valid work email.', 'error');
          if (emailInput) emailInput.focus();
          return;
        }
        if (currentRole === 'founder') {
          var comp = founderCompany ? founderCompany.value.trim() : '';
          if (!comp) {
            goToStep(2);
            showAlert('Please enter your startup / company name.', 'error');
            if (founderCompany) founderCompany.focus();
            return;
          }
        }
        if (!pass || pass.length < 6) {
          goToStep(3);
          showAlert('Password must be at least 6 characters.', 'error');
          if (passInput) passInput.focus();
          return;
        }
        if (pass !== cpass) {
          goToStep(3);
          showAlert('Passwords do not match.', 'error');
          if (cpassInput) cpassInput.focus();
          return;
        }
        if (!terms) {
          goToStep(3);
          showAlert('Please accept the Terms of Service & Privacy Policy.', 'error');
          return;
        }

        submitBtn.disabled = true;
        var prevLabel = submitText ? submitText.textContent : '';
        if (submitText) submitText.textContent = 'Creating ' + (currentRole === 'investor' ? 'Investor' : 'Founder') + ' Account...';

        var payload = {
          name: name, email: email, phone: phone,
          password: pass, password_confirmation: cpass,
          role: currentRole, city: city, country: country,
          plan_code:     ($('portal-plan-code')     ? $('portal-plan-code').value     : 'free_trial'),
          billing_cycle: ($('portal-billing-cycle') ? $('portal-billing-cycle').value : 'monthly'),
          csrf_token: '<?= function_exists("csrf_token") ? csrf_token() : "" ?>'
        };

        if (currentRole === 'founder') {
          payload.company_name = founderCompany  ? founderCompany.value.trim()  : '';
          payload.industry     = founderIndustry ? founderIndustry.value         : 'AI & Enterprise SaaS';
          payload.stage        = founderStage    ? founderStage.value            : 'Seed';
          payload.designation  = founderDesig    ? founderDesig.value.trim()     : 'Founder & CEO';
          payload.pitch        = founderPitch    ? founderPitch.value.trim()     : '';
        } else {
          payload.investor_type        = investorType    ? investorType.value           : 'Angel Investor';
          payload.ticket_range         = investorTicket  ? investorTicket.value         : '10-50L';
          payload.preferred_industries = investorSectors ? investorSectors.value.trim() : 'AI & DeepTech, SaaS, FinTech';
        }

        var ep = '<?= function_exists("url") ? url("api/register.php") : "api/register.php" ?>';
        fetch(ep, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                     'X-CSRF-Token': '<?= function_exists("csrf_token") ? csrf_token() : "" ?>' },
          body: JSON.stringify(payload)
        })
        .then(function(res){ return res.json(); })
        .then(function(data) {
          if (data && data.success) {
            showAlert(data.message || 'Account created! Redirecting to your portal...', 'success');
            if (submitText) submitText.textContent = 'Redirecting to Dashboard...';
            setTimeout(function(){
              window.location.href = data.redirect || (currentRole === 'investor' ? 'investor/dashboard.php' : 'founder/dashboard.php');
            }, 900);
          } else {
            submitBtn.disabled = false;
            if (submitText) submitText.textContent = prevLabel;
            showAlert(data.error || 'Registration failed. Please try again.', 'error');
          }
        })
        .catch(function() {
          submitBtn.disabled = false;
          if (submitText) submitText.textContent = prevLabel;
          showAlert('Network or server error. Please try again.', 'error');
        });
      });
    }

    /* Global plan selection handler */
    window.nxSelectPlan = function(planCode, billingCycle, role, planName, priorityName, priorityLevel) {
      if (role) setRole(role);
      var sec = $('nx-portal-register');
      if (sec) { sec.style.visibility = 'visible'; sec.style.opacity = '1'; }
      var pIn = $('portal-plan-code'), cIn = $('portal-billing-cycle');
      var badge = $('portal-selected-plan-badge'), tEl = $('portal-plan-badge-title'), sEl = $('portal-plan-badge-sub');
      if (pIn) pIn.value = planCode;
      if (cIn) cIn.value = billingCycle;
      if (badge && tEl && sEl) {
        if (planCode === 'free_trial') { badge.classList.remove('show'); }
        else {
          tEl.textContent = planName + ' (' + (billingCycle === 'annually' ? 'Annual' : 'Monthly') + ')';
          sEl.textContent = 'Level ' + priorityLevel + ': ' + priorityName + ' Active';
          badge.classList.add('show');
        }
      }
    };

    setRole('founder');
    goToStep(1);

    /* Sticky stack scroll effect */
    (function stackFX() {
      var section = $('nx-portal-register');
      if (!section) return;
      var stage   = section.querySelector('.nx-portal-stage');
      var dim     = $('nx-portal-dim');
      var reduce  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var ticking = false;

      function getNext() {
        var el = section.nextElementSibling;
        while (el && (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || el.offsetHeight === 0)) el = el.nextElementSibling;
        return el;
      }
      function setStickyTop() {
        var vh = window.innerHeight, h = section.offsetHeight;
        section.style.setProperty('--nx-portal-top', Math.min(0, vh - h) + 'px');
      }
      function update() {
        ticking = false;
        var next = $('nx-hero') || getNext();
        if (!next || reduce) return;
        var vh = window.innerHeight, top = next.getBoundingClientRect().top;
        var p  = Math.max(0, Math.min(1, 1 - (top / vh)));
        if (stage) stage.style.transform = 'translateY(' + (-26 * p).toFixed(1) + 'px) scale(' + (1 - 0.055 * p).toFixed(4) + ')';
        if (dim)   dim.style.opacity = (0.50 * p).toFixed(3);
        section.style.visibility = next.getBoundingClientRect().bottom <= 0 ? 'hidden' : 'visible';
      }
      function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }
      function refresh()  { setStickyTop(); update(); }

      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', refresh);
      window.addEventListener('load',   refresh);
      if ('ResizeObserver' in window) new ResizeObserver(refresh).observe(section);
      refresh();
    })();

  })();
  </script>

</section>