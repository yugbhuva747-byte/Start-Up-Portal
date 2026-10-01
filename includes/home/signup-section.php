<?php
/**
 * ============================================================================
 * include/home/signup-section.php
 * ----------------------------------------------------------------------------
 * SECTION 02 — DIRECT ONBOARDING & REGISTRATION PORTAL
 * Matches the exact UI & form fields provided in user's design reference
 * ============================================================================
 */
?>

<style>
  /* ── Pastel Section Wrapper ── */
  .nx-portal-section {
    position: relative;
    background-color: #FAF5FF;
    background-image:
      radial-gradient(at 0% 15%, #EDE9FE 0px, transparent 48%),
      radial-gradient(at 100% 10%, #FCE7F3 0px, transparent 45%),
      radial-gradient(at 50% 60%, #FFF7ED 0px, transparent 45%),
      radial-gradient(at 10% 95%, #ECFDF5 0px, transparent 45%),
      radial-gradient(at 95% 90%, #FEF3C7 0px, transparent 45%);
    color: #1E1B4B;
    overflow: hidden;
    padding-top: clamp(60px, 8vh, 100px);
    padding-bottom: clamp(60px, 8vh, 100px);
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }

  /* Ambient Floating Pastel Orbs */
  .nx-portal-orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(100px);
    pointer-events: none;
    opacity: 0.55;
    animation: floatPortalOrb 18s ease-in-out infinite alternate;
  }

  .nx-portal-orb-1 {
    background: linear-gradient(135deg, #DDD6FE 0%, #FBCFE8 100%);
    animation-duration: 16s;
  }

  .nx-portal-orb-2 {
    background: linear-gradient(135deg, #FED7AA 0%, #FEF08A 100%);
    animation-duration: 20s;
    animation-delay: -5s;
  }

  .nx-portal-orb-3 {
    background: linear-gradient(135deg, #A7F3D0 0%, #BAE6FD 100%);
    animation-duration: 22s;
    animation-delay: -10s;
  }

  @keyframes floatPortalOrb {
    0% { transform: translate(0px, 0px) scale(1); }
    50% { transform: translate(35px, 25px) scale(1.08); }
    100% { transform: translate(-25px, 15px) scale(0.95); }
  }

  /* Frosted Glass Pastel Card */
  .nx-form-card {
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(255, 255, 255, 0.95);
    box-shadow:
      0 25px 60px -15px rgba(124, 58, 237, 0.12),
      0 12px 30px -8px rgba(236, 72, 153, 0.08),
      inset 0 1px 2px rgba(255, 255, 255, 1);
  }

  /* Jewel Gradients */
  .gradient-text-investor-portal {
    background: linear-gradient(135deg, #7C3AED 0%, #4F46E5 50%, #0D9488 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .gradient-text-startup-portal {
    background: linear-gradient(135deg, #EC4899 0%, #F43F5E 50%, #EA580C 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Custom Input Styling matching screenshot */
  .nx-custom-input {
    background: #FFFFFF;
    border: 1.5px solid #E2E8F0;
    color: #1E1B4B;
    font-size: 13.5px;
    border-radius: 14px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-custom-input:hover {
    border-color: #CBD5E1;
  }

  .nx-custom-input:focus {
    background: #FFFFFF;
    border-color: #6366F1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    outline: none;
  }

  /* Purple Submit CTA */
  .nx-custom-submit-btn {
    background: linear-gradient(135deg, #6366F1 0%, #7C3AED 50%, #9333EA 100%);
    background-size: 200% auto;
    transition: all 0.35s ease;
    box-shadow: 0 12px 28px -5px rgba(99, 102, 241, 0.4);
  }

  .nx-custom-submit-btn:hover {
    background-position: right center;
    transform: translateY(-1.5px);
    box-shadow: 0 16px 32px -4px rgba(99, 102, 241, 0.5);
  }

  .nx-custom-submit-btn:active {
    transform: translateY(0);
  }

  /* Stacking Sections Animation CSS */
  #nx-portal-register.nx-portal-section {
    position: sticky;
    top: var(--nx-portal-top, 0px);
    z-index: 1;
    isolation: isolate;
    min-height: 100vh;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-sizing: border-box;
    opacity: 1 !important;
  }

  @media (max-height: 750px) {
    #nx-portal-register.nx-portal-section {
      height: auto;
      min-height: 100svh;
    }
  }

  #nx-portal-register .nx-portal-stage {
    position: relative;
    z-index: 10;
    transform-origin: center top;
    will-change: transform;
    opacity: 1 !important;
  }

  .nx-portal-dim {
    position: absolute;
    inset: 0;
    z-index: 20;
    background-color: #1E1B4B;
    opacity: 0;
    pointer-events: none;
    will-change: opacity;
  }
</style>

<section id="nx-portal-register" class="nx-portal-section relative w-full">

  <!-- Dark veil that fades in as next section covers this one -->
  <div id="nx-portal-dim" class="nx-portal-dim" aria-hidden="true"></div>

  <!-- Ambient Floating Pastel Mesh Orbs -->
  <div class="nx-portal-orb nx-portal-orb-1 w-[550px] h-[550px] top-[-100px] left-[-120px]"></div>
  <div class="nx-portal-orb nx-portal-orb-2 w-[500px] h-[500px] bottom-[-80px] right-[-100px]"></div>
  <div class="nx-portal-orb nx-portal-orb-3 w-[420px] h-[420px] top-[25%] left-[45%]"></div>

  <div class="nx-portal-stage w-full max-w-[1360px] mx-auto px-5 sm:px-8 lg:px-12 relative z-10">

    <!-- 2-Column Split: Showcase (Left) + Exact Registration Form (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

      <!-- ========================================================
           LEFT COLUMN: Ecosystem Credibility & Dynamic Showcase
           ======================================================== -->
      <div class="lg:col-span-5 flex flex-col justify-center max-w-[520px]">

        <!-- Short Role Badge -->
        <div
          class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-purple-100/90 border border-purple-200 text-[11px] font-bold tracking-wide uppercase text-purple-800 mb-3.5 w-fit shadow-xs">
          <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
          <span id="portal-showcase-pill">FOUNDER LAUNCHPAD</span>
        </div>

        <!-- Dynamic Headline for Role -->
        <h2 class="text-[30px] sm:text-[38px] lg:text-[44px] font-extrabold tracking-[-0.035em] leading-[1.14] mb-3.5 text-slate-900"
          id="portal-showcase-heading">
          Raise Direct Growth Capital <span class="gradient-text-startup-portal">Without Middlemen.</span>
        </h2>

        <p class="text-[14.5px] text-slate-600 leading-relaxed mb-6" id="portal-showcase-desc">
          Connect directly with active angels, syndicates, and seed funds. Complete confidentiality, automated cap table sharing &amp; 0% broker fee.
        </p>

        <!-- 3 Feature Cards -->
        <div class="space-y-3" id="portal-showcase-cards">
          <!-- Card 1 -->
          <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-rose-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
            <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 font-bold">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
              </svg>
            </div>
            <div>
              <h4 class="text-[13px] font-bold text-slate-900">Direct Investor Cheques</h4>
              <p class="text-[11.5px] text-slate-600 leading-snug">Present pitch decks directly to check-writing partners, skipping the junior analyst filters.</p>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-orange-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-bold">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <div>
              <h4 class="text-[13px] font-bold text-slate-900">Private &amp; Controlled Deal Room</h4>
              <p class="text-[11.5px] text-slate-600 leading-snug">You approve who inspects your metrics, unit economics, and proprietary tech deck.</p>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-emerald-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <h4 class="text-[13px] font-bold text-slate-900">Zero Commission / No Broker Cut</h4>
              <p class="text-[11.5px] text-slate-600 leading-snug">100% of the capital raised goes straight to your company treasury. Zero success cuts.</p>
            </div>
          </div>
        </div>

        <!-- Social Proof Metrics -->
        <div class="mt-5 flex items-center gap-3 p-3.5 rounded-2xl bg-white/80 border border-purple-100/90 backdrop-blur-xs">
          <div class="flex -space-x-1.5 shrink-0">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-purple-200 text-[10.5px] font-bold text-purple-800 ring-2 ring-white">AK</span>
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-pink-200 text-[10.5px] font-bold text-pink-800 ring-2 ring-white">VR</span>
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-200 text-[10.5px] font-bold text-amber-800 ring-2 ring-white">SM</span>
          </div>
          <p class="text-[12px] text-slate-600 font-medium">
            <span class="font-bold text-slate-900">₹480 Cr+</span> deployed across <span class="font-bold text-slate-900">320+</span> closed rounds.
          </p>
        </div>

      </div>

      <!-- ========================================================
           RIGHT COLUMN: Exact Registration Form matching screenshot
           ======================================================== -->
      <div class="lg:col-span-7 w-full flex justify-center lg:justify-end">
        <div class="nx-form-card w-full max-w-[650px] rounded-3xl p-6 sm:p-7 relative">

          <!-- Header -->
          <div class="mb-4">
            <div class="flex items-center justify-between">
              <h2 class="text-[28px] sm:text-[32px] font-extrabold tracking-tight text-slate-900 leading-tight">
                Create Your Account
              </h2>
              <button type="button" id="portal-btn-demo"
                class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1 rounded-full transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                <span>Auto-fill demo</span>
              </button>
            </div>
            <p class="text-[13px] sm:text-[14px] text-slate-500 mt-1">
              Select your portal role to open your customized registration form.
            </p>
          </div>

          <!-- SELECT PORTAL ACCOUNT TYPE -->
          <div class="mb-4">
            <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-2">
              SELECT PORTAL ACCOUNT TYPE <span class="text-rose-500">*</span>
            </label>

            <div class="grid grid-cols-2 gap-2.5 p-1.5 bg-slate-100/90 rounded-2xl border border-slate-200">
              <!-- Startup Founder Tab -->
              <button type="button" id="portal-tab-startup"
                class="py-3 px-3.5 rounded-xl text-xs sm:text-[13px] font-bold flex items-center space-x-3 transition-all bg-white text-indigo-700 shadow-sm border border-indigo-200 cursor-pointer">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.32l2.47-2.47a1.5 1.5 0 012.122 0l1.414 1.414a1.5 1.5 0 010 2.122l-2.47 2.47a4.493 4.493 0 004.32-1.757m-6.109-6.109a15.09 15.09 0 012.448-2.448" />
                  </svg>
                </div>
                <div class="text-left">
                  <div class="font-bold leading-tight text-slate-900">Startup Founder</div>
                  <div class="text-[10px] font-medium text-indigo-600">Raising Capital</div>
                </div>
              </button>

              <!-- Angel / Investor Tab -->
              <button type="button" id="portal-tab-investor"
                class="py-3 px-3.5 rounded-xl text-xs sm:text-[13px] font-bold flex items-center space-x-3 transition-all text-slate-500 hover:text-slate-800 hover:bg-white/60 cursor-pointer">
                <div class="w-8 h-8 rounded-xl bg-slate-200/70 text-slate-500 flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                  </svg>
                </div>
                <div class="text-left">
                  <div class="font-bold leading-tight">Angel / Investor</div>
                  <div class="text-[10px] font-medium opacity-75">Deploying Capital</div>
                </div>
              </button>
            </div>
          </div>

          <!-- Selected Plan & Priority Pill Banner -->
          <div id="portal-selected-plan-badge"
            class="hidden mb-3.5 p-3 rounded-2xl bg-gradient-to-r from-purple-50 via-pink-50 to-amber-50 border border-purple-200/90 shadow-xs flex items-center justify-between transition-all">
            <div class="flex items-center gap-2.5">
              <div class="w-7 h-7 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-xs" id="portal-plan-badge-icon">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
              </div>
              <div>
                <div class="text-[12px] font-extrabold text-purple-950" id="portal-plan-badge-title">6 Months Dealmaker</div>
                <div class="text-[10.5px] font-semibold text-purple-700" id="portal-plan-badge-sub">Level 3: Featured Dealflow Priority Active</div>
              </div>
            </div>
            <a href="#nx-pricing" class="text-[10.5px] font-bold text-purple-700 hover:text-purple-900 bg-white/80 px-2.5 py-1 rounded-lg border border-purple-200 transition">Change Plan</a>
          </div>

          <!-- Dynamic Alert Box -->
          <div id="portal-auth-alert"
            class="hidden mb-3 p-3 rounded-2xl flex items-center gap-2.5 transition-all">
            <div id="portal-auth-alert-icon"
              class="w-6 h-6 rounded-full flex items-center justify-center text-[12px] font-bold shrink-0">
              ✓
            </div>
            <div class="text-[12px] font-semibold leading-tight" id="portal-auth-alert-text">
              Registration submitted! Setting up your portal...
            </div>
          </div>

          <!-- The Registration Form -->
          <form id="portal-signup-form" class="space-y-3.5" novalidate>
            <input type="hidden" name="role" id="form-role" value="founder">
            <input type="hidden" name="plan_code" id="portal-plan-code" value="free_trial">
            <input type="hidden" name="billing_cycle" id="portal-billing-cycle" value="monthly">

            <!-- FULL LEGAL NAME -->
            <div>
              <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                FULL LEGAL NAME <span class="text-rose-500">*</span>
              </label>
              <div class="relative flex items-center">
                <span class="absolute left-3.5 text-slate-400">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                  </svg>
                </span>
                <input type="text" id="portal-name" name="name" required placeholder="e.g. Aarav Sharma"
                  class="nx-custom-input w-full pl-10 pr-4 py-2.5 text-slate-900 placeholder-slate-400 font-medium">
              </div>
            </div>

            <!-- WORK EMAIL & PHONE NUMBER (2 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  WORK EMAIL <span class="text-rose-500">*</span>
                </label>
                <div class="relative flex items-center">
                  <span class="absolute left-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                  </span>
                  <input type="email" id="portal-email" name="email" required autocomplete="email"
                    placeholder="name@domain.com"
                    class="nx-custom-input w-full pl-10 pr-3.5 py-2.5 text-slate-900 placeholder-slate-400 font-medium">
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  PHONE NUMBER
                </label>
                <div class="relative flex items-center">
                  <span class="absolute left-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                    </svg>
                  </span>
                  <input type="tel" id="portal-phone" name="phone" placeholder="+91 98765 43210"
                    class="nx-custom-input w-full pl-10 pr-3.5 py-2.5 text-slate-900 placeholder-slate-400 font-medium">
                </div>
              </div>
            </div>

            <!-- STARTUP & COMPANY PROFILE CONTAINER (FOUNDER) -->
            <div id="founder-fields" class="space-y-3 p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100">
              <div class="flex items-center space-x-1.5 text-indigo-700 font-bold text-[10.5px] uppercase tracking-wider">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
                <span>STARTUP &amp; COMPANY PROFILE</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                    STARTUP / COMPANY NAME <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative flex items-center">
                    <span class="absolute left-3 text-slate-400">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                      </svg>
                    </span>
                    <input type="text" id="founder-company" name="company_name" placeholder="e.g. TechPulse AI"
                      class="nx-custom-input w-full pl-9 pr-3 py-2 text-xs font-medium placeholder-slate-400">
                  </div>
                </div>

                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                    PRIMARY SECTOR / INDUSTRY
                  </label>
                  <select name="industry" id="founder-industry"
                    class="nx-custom-input w-full px-3 py-2 text-xs font-medium text-slate-800">
                    <option value="AI & Enterprise SaaS">AI &amp; Enterprise SaaS</option>
                    <option value="FinTech & Web3">FinTech &amp; Web3</option>
                    <option value="HealthTech & Bio">HealthTech &amp; Bio</option>
                    <option value="CleanTech & Climate">CleanTech &amp; Climate</option>
                    <option value="Consumer & D2C">Consumer &amp; D2C</option>
                    <option value="Robotics & DeepTech">Robotics &amp; DeepTech</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                    CURRENT VENTURE STAGE
                  </label>
                  <select name="stage" id="founder-stage"
                    class="nx-custom-input w-full px-3 py-2 text-xs font-medium text-slate-800">
                    <option value="Seed">Seed Round (Raising ₹1 Cr - ₹5 Cr)</option>
                    <option value="Pre-Seed">Pre-Seed / Prototype (₹25L - ₹1 Cr)</option>
                    <option value="Pre-Series A">Pre-Series A (₹5 Cr - ₹15 Cr)</option>
                    <option value="Series A+">Series A+ (₹15 Cr+)</option>
                    <option value="Idea">Concept / Early Validation</option>
                  </select>
                </div>

                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                    YOUR DESIGNATION
                  </label>
                  <input type="text" name="designation" id="founder-designation" value="Founder & CEO" placeholder="Founder & CEO"
                    class="nx-custom-input w-full px-3 py-2 text-xs font-medium text-slate-800">
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  ONE-LINE STARTUP ELEVATOR PITCH
                </label>
                <input type="text" name="pitch" id="founder-pitch" placeholder="e.g. AI-driven logistics intelligence optimizing last-mile delivery."
                  class="nx-custom-input w-full px-3 py-2 text-xs font-medium placeholder-slate-400">
              </div>
            </div>

            <!-- INVESTOR PROFILE CONTAINER (ANGEL / INVESTOR) -->
            <div id="investor-fields" class="space-y-3 p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-100 hidden">
              <div class="flex items-center space-x-1.5 text-emerald-700 font-bold text-[10.5px] uppercase tracking-wider">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3" />
                </svg>
                <span>INVESTOR &amp; ACCREDITATION PROFILE</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                    INVESTOR TYPE
                  </label>
                  <select name="investor_type" id="investor-type"
                    class="nx-custom-input w-full px-3 py-2 text-xs font-medium text-slate-800">
                    <option value="Angel Investor">Individual Angel Investor</option>
                    <option value="Syndicate Lead">Angel Syndicate Lead</option>
                    <option value="Venture Capital Partner">Institutional VC Partner</option>
                    <option value="Family Office">Family Office Principal</option>
                    <option value="Corporate VC">Corporate Venture Capital (CVC)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                    TYPICAL CHEQUE SIZE
                  </label>
                  <select name="ticket_range" id="investor-ticket"
                    class="nx-custom-input w-full px-3 py-2 text-xs font-medium text-slate-800">
                    <option value="2-10L">₹2 Lakhs – ₹10 Lakhs</option>
                    <option value="10-50L" selected>₹10 Lakhs – ₹50 Lakhs (Popular)</option>
                    <option value="50L-2Cr">₹50 Lakhs – ₹2 Crores</option>
                    <option value="2Cr+">₹2 Crores+ (Institutional)</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  PRIMARY INVESTMENT FOCUS SECTORS
                </label>
                <input type="text" name="preferred_industries" id="investor-sectors"
                  value="AI & DeepTech, SaaS, FinTech, HealthTech" 
                  placeholder="e.g. AI & DeepTech, SaaS, FinTech"
                  class="nx-custom-input w-full px-3 py-2 text-xs font-medium text-slate-800">
              </div>
            </div>

            <!-- PASSWORD & CONFIRM PASSWORD (2 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  PASSWORD <span class="text-rose-500">*</span>
                </label>
                <div class="relative flex items-center">
                  <span class="absolute left-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                  </span>
                  <input type="password" id="portal-password" name="password" required placeholder="Min 6 characters"
                    autocomplete="new-password"
                    class="nx-custom-input w-full pl-10 pr-9 py-2.5 text-slate-900 placeholder-slate-400 font-medium">
                  <button type="button" id="portal-toggle-pwd1" class="absolute right-2.5 p-1 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  CONFIRM PASSWORD <span class="text-rose-500">*</span>
                </label>
                <div class="relative flex items-center">
                  <input type="password" id="portal-confirm-password" name="password_confirmation" required placeholder="Repeat password"
                    autocomplete="new-password"
                    class="nx-custom-input w-full pl-4 pr-9 py-2.5 text-slate-900 placeholder-slate-400 font-medium">
                  <button type="button" id="portal-toggle-pwd2" class="absolute right-2.5 p-1 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- CITY & COUNTRY (2 Columns) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  CITY
                </label>
                <div class="relative flex items-center">
                  <span class="absolute left-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                  </span>
                  <input type="text" id="portal-city" name="city" value="Bengaluru" required
                    class="nx-custom-input w-full pl-10 pr-3.5 py-2.5 text-slate-900 font-medium">
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 mb-1">
                  COUNTRY
                </label>
                <div class="relative flex items-center">
                  <span class="absolute left-3.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-.778.099-1.533.284-2.253" />
                    </svg>
                  </span>
                  <input type="text" id="portal-country" name="country" value="India" required
                    class="nx-custom-input w-full pl-10 pr-3.5 py-2.5 text-slate-900 font-medium">
                </div>
              </div>
            </div>

            <!-- Terms & Conditions Checkbox -->
            <div class="pt-1">
              <label class="flex items-start space-x-2.5 cursor-pointer group">
                <input type="checkbox" id="portal-terms" name="terms" required checked
                  class="mt-0.5 w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer accent-indigo-600 shrink-0">
                <span class="text-[11px] text-slate-600 leading-snug group-hover:text-slate-800 transition">
                  I agree to the <a href="<?= function_exists('url') ? url('legal/terms.php') : '#' ?>" class="text-indigo-600 hover:text-indigo-800 font-bold underline">Terms of Service</a>, <a href="<?= function_exists('url') ? url('legal/privacy.php') : '#' ?>" class="text-indigo-600 hover:text-indigo-800 font-bold underline">Privacy Policy</a>, and understand the private venture risk disclosures.
                </span>
              </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-1">
              <button type="submit" id="portal-submit-btn"
                class="nx-custom-submit-btn w-full py-3 px-6 text-white text-xs sm:text-sm font-bold rounded-xl shadow-md transition flex items-center justify-center space-x-2 cursor-pointer">
                <span id="portal-submit-text">Complete Founder Registration</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
              </button>
            </div>

            <!-- Sign In Switch -->
            <div class="text-center pt-2 border-t border-slate-100 flex items-center justify-center gap-1.5">
              <span class="text-[12px] text-slate-500">Already have an account?</span>
              <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>"
                class="text-[12px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1">
                <span>Sign In to Portal</span>
                <span aria-hidden="true">&rarr;</span>
              </a>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>

  <!-- JavaScript Interaction & AJAX Submission -->
  <script>
  (function () {
    'use strict';

    const tabInvestor = document.getElementById('portal-tab-investor');
    const tabStartup = document.getElementById('portal-tab-startup');

    const heroTitle = document.getElementById('portal-hero-title');
    const showcasePill = document.getElementById('portal-showcase-pill');
    const showcaseHeading = document.getElementById('portal-showcase-heading');
    const showcaseDesc = document.getElementById('portal-showcase-desc');
    const showcaseCards = document.getElementById('portal-showcase-cards');

    const founderFields = document.getElementById('founder-fields');
    const investorFields = document.getElementById('investor-fields');
    const formRole = document.getElementById('form-role');

    const nameInput = document.getElementById('portal-name');
    const emailInput = document.getElementById('portal-email');
    const phoneInput = document.getElementById('portal-phone');

    const founderCompany = document.getElementById('founder-company');
    const founderIndustry = document.getElementById('founder-industry');
    const founderStage = document.getElementById('founder-stage');
    const founderDesignation = document.getElementById('founder-designation');
    const founderPitch = document.getElementById('founder-pitch');

    const investorType = document.getElementById('investor-type');
    const investorTicket = document.getElementById('investor-ticket');
    const investorSectors = document.getElementById('investor-sectors');

    const passInput = document.getElementById('portal-password');
    const confirmPassInput = document.getElementById('portal-confirm-password');
    const togglePwd1 = document.getElementById('portal-toggle-pwd1');
    const togglePwd2 = document.getElementById('portal-toggle-pwd2');

    const cityInput = document.getElementById('portal-city');
    const countryInput = document.getElementById('portal-country');
    const termsCheckbox = document.getElementById('portal-terms');

    const submitBtn = document.getElementById('portal-submit-btn');
    const submitText = document.getElementById('portal-submit-text');
    const alertBox = document.getElementById('portal-auth-alert');
    const alertText = document.getElementById('portal-auth-alert-text');
    const btnDemo = document.getElementById('portal-btn-demo');
    const form = document.getElementById('portal-signup-form');

    let currentRole = 'founder';

    const investorCardsHtml = `
      <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-purple-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
        <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center shrink-0 font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </div>
        <div>
          <h4 class="text-[13px] font-bold text-slate-900">Pre-Screened Investment Pipeline</h4>
          <p class="text-[11.5px] text-slate-600 leading-snug">Discover high-potential startups matching your sector and ticket size preferences.</p>
        </div>
      </div>
      <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-pink-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
        <div class="w-9 h-9 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center shrink-0 font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h4 class="text-[13px] font-bold text-slate-900">Audited Financials & Cap Tables</h4>
          <p class="text-[11.5px] text-slate-600 leading-snug">Review complete diligence folders, MRR proofs, and ownership structures before committing.</p>
        </div>
      </div>
      <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-emerald-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div>
          <h4 class="text-[13px] font-bold text-slate-900">Co-Invest With Top Angels</h4>
          <p class="text-[11.5px] text-slate-600 leading-snug">Participate in vetted syndicate rounds alongside experienced institutional allocators.</p>
        </div>
      </div>
    `;

    const startupCardsHtml = `
      <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-rose-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
        <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
          </svg>
        </div>
        <div>
          <h4 class="text-[13px] font-bold text-slate-900">Direct Investor Cheques</h4>
          <p class="text-[11.5px] text-slate-600 leading-snug">Present pitch decks directly to check-writing partners, skipping the junior analyst filters.</p>
        </div>
      </div>
      <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-orange-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
        <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div>
          <h4 class="text-[13px] font-bold text-slate-900">Private & Controlled Deal Room</h4>
          <p class="text-[11.5px] text-slate-600 leading-snug">You approve who inspects your metrics, unit economics, and proprietary tech deck.</p>
        </div>
      </div>
      <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-white/85 border border-emerald-100 shadow-xs backdrop-blur-md transition-all hover:-translate-y-0.5">
        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 font-bold">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <div>
          <h4 class="text-[13px] font-bold text-slate-900">Zero Commission / No Broker Cut</h4>
          <p class="text-[11.5px] text-slate-600 leading-snug">100% of the capital raised goes straight to your company treasury. Zero success cuts.</p>
        </div>
      </div>
    `;

    function setRole(role) {
      currentRole = (role === 'investor') ? 'investor' : 'founder';
      if (formRole) formRole.value = currentRole;

      if (currentRole === 'investor') {
        tabInvestor.className = 'py-3 px-3.5 rounded-xl text-xs sm:text-[13px] font-bold flex items-center space-x-3 transition-all bg-white text-indigo-700 shadow-sm border border-indigo-200 cursor-pointer';
        tabStartup.className = 'py-3 px-3.5 rounded-xl text-xs sm:text-[13px] font-bold flex items-center space-x-3 transition-all text-slate-500 hover:text-slate-800 hover:bg-white/60 cursor-pointer';

        if (founderFields) founderFields.classList.add('hidden');
        if (investorFields) investorFields.classList.remove('hidden');

        if (heroTitle) {
          heroTitle.className = 'gradient-text-investor-portal';
          heroTitle.textContent = 'Venture Ecosystem.';
        }
        if (showcasePill) showcasePill.textContent = 'CURATED INVESTOR SUITE';
        if (showcaseHeading) showcaseHeading.innerHTML = 'Co-Invest Alongside India’s <span class="gradient-text-investor-portal">Leading Allocators.</span>';
        if (showcaseDesc) showcaseDesc.textContent = 'Gain immediate access to verified, pre-screened seed & Series A tech startups with audited MRR and diligence cap tables.';
        if (showcaseCards) showcaseCards.innerHTML = investorCardsHtml;

        if (submitText) submitText.textContent = 'Complete Investor Registration';
      } else {
        tabStartup.className = 'py-3 px-3.5 rounded-xl text-xs sm:text-[13px] font-bold flex items-center space-x-3 transition-all bg-white text-indigo-700 shadow-sm border border-indigo-200 cursor-pointer';
        tabInvestor.className = 'py-3 px-3.5 rounded-xl text-xs sm:text-[13px] font-bold flex items-center space-x-3 transition-all text-slate-500 hover:text-slate-800 hover:bg-white/60 cursor-pointer';

        if (investorFields) investorFields.classList.add('hidden');
        if (founderFields) founderFields.classList.remove('hidden');

        if (heroTitle) {
          heroTitle.className = 'gradient-text-startup-portal';
          heroTitle.textContent = 'Growth Platform.';
        }
        if (showcasePill) showcasePill.textContent = 'FOUNDER LAUNCHPAD';
        if (showcaseHeading) showcaseHeading.innerHTML = 'Raise Direct Growth Capital <span class="gradient-text-startup-portal">Without Middlemen.</span>';
        if (showcaseDesc) showcaseDesc.textContent = 'Connect directly with active angels, syndicates, and seed funds. Complete confidentiality, automated cap table sharing & 0% broker fee.';
        if (showcaseCards) showcaseCards.innerHTML = startupCardsHtml;

        if (submitText) submitText.textContent = 'Complete Founder Registration';
      }

      if (alertBox) alertBox.classList.add('hidden');
    }

    if (tabInvestor) tabInvestor.addEventListener('click', () => setRole('investor'));
    if (tabStartup) tabStartup.addEventListener('click', () => setRole('founder'));

    // Auto-fill Demo
    if (btnDemo) {
      btnDemo.addEventListener('click', () => {
        if (currentRole === 'investor') {
          nameInput.value = 'Vikram Singhania';
          emailInput.value = 'vikram.investor@matrixcap.in';
          phoneInput.value = '+91 98234 56789';
          if (investorType) investorType.value = 'Syndicate Lead';
          if (investorTicket) investorTicket.value = '10-50L';
          if (investorSectors) investorSectors.value = 'AI & DeepTech, SaaS, FinTech, HealthTech';
          passInput.value = 'Investor#2026';
          confirmPassInput.value = 'Investor#2026';
        } else {
          nameInput.value = 'Aarav Sharma';
          emailInput.value = 'aarav@techpulse.ai';
          phoneInput.value = '+91 98765 43210';
          if (founderCompany) founderCompany.value = 'TechPulse AI';
          if (founderIndustry) founderIndustry.value = 'AI & Enterprise SaaS';
          if (founderStage) founderStage.value = 'Seed';
          if (founderDesignation) founderDesignation.value = 'Founder & CEO';
          if (founderPitch) founderPitch.value = 'AI-driven logistics intelligence optimizing last-mile delivery.';
          passInput.value = 'Founder#2026';
          confirmPassInput.value = 'Founder#2026';
        }
      });
    }

    // Toggle Password Visibility
    function setupPasswordToggle(buttonId, inputId) {
      const btn = document.getElementById(buttonId);
      const inp = document.getElementById(inputId);
      if (btn && inp) {
        btn.addEventListener('click', () => {
          inp.type = (inp.type === 'password') ? 'text' : 'password';
        });
      }
    }
    setupPasswordToggle('portal-toggle-pwd1', 'portal-password');
    setupPasswordToggle('portal-toggle-pwd2', 'portal-confirm-password');

    function showAlert(message, type = 'success') {
      if (!alertBox || !alertText) return;
      const icon = document.getElementById('portal-auth-alert-icon');
      alertText.textContent = message;
      alertBox.classList.remove('hidden');

      if (type === 'error') {
        alertBox.className = 'mb-3 p-3 rounded-2xl bg-rose-50 border border-rose-200 flex items-center gap-2.5';
        if (icon) {
          icon.className = 'w-6 h-6 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-[12px] font-bold shrink-0';
          icon.textContent = '✕';
        }
        alertText.className = 'text-[12px] text-rose-800 font-semibold leading-tight';
      } else {
        alertBox.className = 'mb-3 p-3 rounded-2xl bg-emerald-50/95 border border-emerald-200 flex items-center gap-2.5';
        if (icon) {
          icon.className = 'w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[12px] font-bold shrink-0';
          icon.textContent = '✓';
        }
        alertText.className = 'text-[12px] text-emerald-800 font-semibold leading-tight';
      }
    }

    // Form Submission
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = nameInput ? nameInput.value.trim() : '';
        const email = emailInput ? emailInput.value.trim() : '';
        const phone = phoneInput ? phoneInput.value.trim() : '';
        const pass = passInput ? passInput.value : '';
        const confirmPass = confirmPassInput ? confirmPassInput.value : '';
        const city = cityInput ? cityInput.value.trim() : 'Bengaluru';
        const country = countryInput ? countryInput.value.trim() : 'India';
        const terms = termsCheckbox ? termsCheckbox.checked : false;

        if (!name) {
          showAlert('Please enter your full legal name.', 'error');
          if (nameInput) nameInput.focus();
          return;
        }
        if (!email || !email.includes('@')) {
          showAlert('Please enter a valid work email address.', 'error');
          if (emailInput) emailInput.focus();
          return;
        }

        if (currentRole === 'founder') {
          const comp = founderCompany ? founderCompany.value.trim() : '';
          if (!comp) {
            showAlert('Please enter your startup / company name.', 'error');
            if (founderCompany) founderCompany.focus();
            return;
          }
        }

        if (!pass || pass.length < 6) {
          showAlert('Password must be at least 6 characters long.', 'error');
          if (passInput) passInput.focus();
          return;
        }
        if (pass !== confirmPass) {
          showAlert('Passwords do not match.', 'error');
          if (confirmPassInput) confirmPassInput.focus();
          return;
        }
        if (!terms) {
          showAlert('Please accept the Terms of Service & Privacy Policy.', 'error');
          return;
        }

        submitBtn.disabled = true;
        const prevText = submitText.textContent;
        submitText.textContent = 'Creating ' + (currentRole === 'investor' ? 'Investor' : 'Founder') + ' Account...';

        const planCodeInput = document.getElementById('portal-plan-code');
        const billingCycleInput = document.getElementById('portal-billing-cycle');

        const payload = {
          name: name,
          email: email,
          phone: phone,
          password: pass,
          password_confirmation: confirmPass,
          role: currentRole,
          city: city,
          country: country,
          plan_code: planCodeInput ? planCodeInput.value : 'free_trial',
          billing_cycle: billingCycleInput ? billingCycleInput.value : 'monthly'
        };

        if (currentRole === 'founder') {
          payload.company_name = founderCompany ? founderCompany.value.trim() : '';
          payload.industry = founderIndustry ? founderIndustry.value : 'AI & Enterprise SaaS';
          payload.stage = founderStage ? founderStage.value : 'Seed';
          payload.designation = founderDesignation ? founderDesignation.value.trim() : 'Founder & CEO';
          payload.pitch = founderPitch ? founderPitch.value.trim() : '';
        } else {
          payload.investor_type = investorType ? investorType.value : 'Angel Investor';
          payload.ticket_range = investorTicket ? investorTicket.value : '10-50L';
          payload.preferred_industries = investorSectors ? investorSectors.value.trim() : 'AI & DeepTech, SaaS, FinTech';
        }

        try {
          const registerEndpoint = '<?= function_exists('url') ? url('api/register.php') : 'api/register.php' ?>';
          const response = await fetch(registerEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
          });

          const data = await response.json();

          if (data && data.success) {
            showAlert(data.message || 'Account created successfully! Redirecting...', 'success');
            submitText.textContent = 'Redirecting to Dashboard...';
            setTimeout(() => {
              window.location.href = data.redirect || (currentRole === 'investor' ? 'investor/dashboard.php' : 'founder/dashboard.php');
            }, 800);
          } else {
            submitBtn.disabled = false;
            submitText.textContent = prevText;
            showAlert(data.error || 'Registration failed. Please try again.', 'error');
          }
        } catch (err) {
          submitBtn.disabled = false;
          submitText.textContent = prevText;
          showAlert('Network or server error. Please try again.', 'error');
        }
      });
    }

    // Global Plan Selection Handler called from Pricing Section
    window.nxSelectPlan = function(planCode, billingCycle, role, planName, priorityName, priorityLevel) {
      if (role) {
        setRole(role);
      }
      const planInput = document.getElementById('portal-plan-code');
      const cycleInput = document.getElementById('portal-billing-cycle');
      const badge = document.getElementById('portal-selected-plan-badge');
      const titleEl = document.getElementById('portal-plan-badge-title');
      const subEl = document.getElementById('portal-plan-badge-sub');

      if (planInput) planInput.value = planCode;
      if (cycleInput) cycleInput.value = billingCycle;

      if (badge && titleEl && subEl) {
        if (planCode === 'free_trial') {
          badge.classList.add('hidden');
        } else {
          titleEl.textContent = planName + ' (' + (billingCycle === 'annually' ? 'Annual' : 'Monthly') + ')';
          subEl.textContent = 'Level ' + priorityLevel + ': ' + priorityName + ' Active';
          badge.classList.remove('hidden');
        }
      }
    };

    // Default to Founder role
    setRole('founder');

    // ── Full-screen sticky + "next section slides over" stacking effect ──
    (function stackEffect() {
      const section = document.getElementById('nx-portal-register');
      if (!section) return;
      const stage = section.querySelector('.nx-portal-stage');
      const dim = document.getElementById('nx-portal-dim');
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      let ticking = false;

      function getNextSection() {
        let el = section.nextElementSibling;
        while (el && (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || el.offsetHeight === 0)) {
          el = el.nextElementSibling;
        }
        return el;
      }

      function setStickyTop() {
        const vh = window.innerHeight;
        const h = section.offsetHeight;
        section.style.setProperty('--nx-portal-top', Math.min(0, vh - h) + 'px');
      }

      function update() {
        ticking = false;
        const hero = document.getElementById('nx-hero') || getNextSection();
        if (!hero || reduce) return;

        const vh = window.innerHeight;
        const top = hero.getBoundingClientRect().top;
        const p = Math.max(0, Math.min(1, 1 - (top / vh)));

        if (stage) {
          stage.style.transform =
            'translateY(' + (-28 * p).toFixed(1) + 'px) scale(' + (1 - 0.06 * p).toFixed(4) + ')';
        }
        if (dim) {
          dim.style.opacity = (0.55 * p).toFixed(3);
        }

        const heroBottom = hero.getBoundingClientRect().bottom;
        if (heroBottom <= 0) {
          section.style.visibility = 'hidden';
        } else {
          section.style.visibility = 'visible';
        }
      }

      function onScroll() {
        if (!ticking) {
          ticking = true;
          requestAnimationFrame(update);
        }
      }

      function refresh() {
        setStickyTop();
        update();
      }

      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', refresh);
      window.addEventListener('load', refresh);
      if ('ResizeObserver' in window) new ResizeObserver(refresh).observe(section);
      refresh();
    })();

  })();
  </script>
</section>