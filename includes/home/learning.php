<?php
/**
 * ============================================================================
 * include/home/learning.php
 * ----------------------------------------------------------------------------
 * SECTION 07 — LEARNING & ACADEMY (COLORFUL PASTEL CELESTIAL EDITION)
 *
 * Transformed from dark blues into vibrant, attractive pastel aurora aesthetic:
 * - Soft Lavender (#EDE9FE), Blush Rose (#FCE7F3), Warm Peach (#FFF7ED)
 * - Sweeping pastel curves with frosted glow filter
 * - Frosted pearl centerpiece hub with jewel gradient bezel
 * - Glassmorphic track containers with crisp, high-contrast typography
 * - Preserved GSAP entrance & parallax motion
 * ============================================================================
 */
?>

<section id="nx-learning"
  class="relative z-20 w-full min-h-screen py-16 lg:py-24 xl:py-32 bg-[#FAF5FF] text-slate-900 overflow-hidden flex items-center justify-center select-none border-t border-purple-100"
  aria-label="Academy & Insights">

  <!-- ============================================================
       BACKGROUND: PASTEL CURVED HORIZON & AMBIENT AURORA
       ============================================================ -->
  <div class="absolute inset-0 pointer-events-none overflow-hidden">
    <!-- Base Pastel Aurora Gradient -->
    <div class="absolute inset-0 bg-gradient-to-b from-[#FAF5FF] via-[#FDF2F8] to-[#FAF5FF]"></div>

    <!-- Ambient Radial Glows -->
    <div class="absolute -top-32 -left-32 w-[600px] h-[600px] rounded-full bg-[#EDE9FE] blur-[140px] opacity-70"></div>
    <div class="absolute -bottom-32 -right-32 w-[600px] h-[600px] rounded-full bg-[#FCE7F3] blur-[140px] opacity-70"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] rounded-full bg-[#FFF7ED] blur-[150px] opacity-60"></div>

    <!-- Giant Sweeping Curved Crescent / Arc (Left Lobe with Glowing Rim) -->
    <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1920 1080" preserveAspectRatio="none" aria-hidden="true">
      <defs>
        <!-- Left Pastel Lobe Fill -->
        <radialGradient id="nxPastelLeftLobeGrad" cx="22%" cy="52%" r="48%">
          <stop offset="0%" stop-color="#EDE9FE" stop-opacity="0.85" />
          <stop offset="65%" stop-color="#FCE7F3" stop-opacity="0.6" />
          <stop offset="100%" stop-color="#FAF5FF" stop-opacity="0" />
        </radialGradient>

        <!-- Right Upper Curve Gradient -->
        <radialGradient id="nxPastelRightLobeGrad" cx="80%" cy="40%" r="55%">
          <stop offset="0%" stop-color="#FFF7ED" stop-opacity="0.75" />
          <stop offset="70%" stop-color="#FCE7F3" stop-opacity="0.5" />
          <stop offset="100%" stop-color="#FAF5FF" stop-opacity="0" />
        </radialGradient>

        <!-- Glowing Horizon Edge Filter -->
        <filter id="nxPastelGlow" x="-20%" y="-20%" width="140%" height="140%">
          <feGaussianBlur stdDeviation="8" result="blur" />
          <feMerge>
            <feMergeNode in="blur" />
            <feMergeNode in="SourceGraphic" />
          </feMerge>
        </filter>

        <linearGradient id="nxPastelRimLight" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#C084FC" stop-opacity="0.2" />
          <stop offset="50%" stop-color="#F472B6" stop-opacity="0.9" />
          <stop offset="100%" stop-color="#FB923C" stop-opacity="0.3" />
        </linearGradient>
      </defs>

      <!-- Sweeping Left Circular Arc Shape -->
      <path d="M -100,0 
               C 500,0 900,260 920,540 
               C 940,820 540,1080 -100,1080 Z" fill="url(#nxPastelLeftLobeGrad)" />

      <!-- Glowing Curved Rim Light along the Left Crescent Edge -->
      <path d="M 0,20 
               C 560,40 910,280 925,540 
               C 940,800 580,1040 0,1060" fill="none" stroke="url(#nxPastelRimLight)" stroke-width="2.5"
        filter="url(#nxPastelGlow)" opacity="0.8" />

      <!-- Right Organic Shadow / Depth Wave -->
      <path d="M 1920,0 
               C 1420,120 1020,380 980,540 
               C 950,680 1200,960 1920,1080 Z" fill="url(#nxPastelRightLobeGrad)" />

      <!-- Fiber Arcs connecting Center to Right Tracks -->
      <!-- Top Fiber (To Investors) -->
      <path id="nx-wire-investors" 
            d="M 980,520 C 1080,480 1140,240 1220,160" 
            fill="none" 
            stroke="rgba(168, 85, 247, 0.45)" 
            stroke-width="2" />
      
      <!-- Bottom Fiber (To Startups) -->
      <path id="nx-wire-startups" 
            d="M 980,560 C 1080,600 1140,800 1220,860" 
            fill="none" 
            stroke="rgba(236, 72, 153, 0.45)" 
            stroke-width="2" />

      <!-- Glowing Signal Nodes on Fibers -->
      <circle cx="1090" cy="330" r="5" fill="#8B5CF6" filter="url(#nxPastelGlow)" />
      <circle cx="1090" cy="330" r="2.5" fill="#FFFFFF" />

      <circle cx="1090" cy="740" r="5" fill="#EC4899" filter="url(#nxPastelGlow)" />
      <circle cx="1090" cy="740" r="2.5" fill="#FFFFFF" />
    </svg>
  </div>

  <!-- ============================================================
       MAIN CONTENT CONTAINER (WIDE FULL-BLEED COMPOSITION)
       ============================================================ -->
  <div class="relative z-10 w-full max-w-[1780px] mx-auto px-6 sm:px-10 lg:px-16 xl:px-20">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 xl:gap-12 items-center">

      <!-- ============================================================
           LEFT SIDE: EDITORIAL HEADLINE & INTRODUCTION
           ============================================================ -->
      <div class="lg:col-span-5 xl:col-span-4 nx-learning-left-content z-20">

        <!-- Label with vibrant pulsing dot -->
        <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-purple-100 border border-purple-200 text-purple-900 mb-6 sm:mb-8 shadow-xs">
          <span class="w-2.5 h-2.5 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
          <span class="text-[12px] sm:text-[13px] font-mono font-bold tracking-[0.24em] uppercase">
            ACADEMY &middot; INSIGHTS
          </span>
        </div>

        <!-- Big Bold Heading with Jewel Gradient -->
        <h2 class="nx-learning-headline text-[48px] sm:text-[64px] lg:text-[68px] xl:text-[78px] font-extrabold leading-[0.93] tracking-[-0.04em] text-slate-900 mb-6 sm:mb-8">
          LEARN BEFORE<br>
          YOU <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 bg-clip-text text-transparent">MOVE.</span>
        </h2>

        <!-- Subheading -->
        <p class="nx-learning-subheading text-[16px] sm:text-[18px] leading-relaxed text-slate-600 max-w-[440px]">
          Tactical playbooks for capital allocators and high-growth founders.
        </p>

      </div>

      <!-- ============================================================
           CENTER: GLOWING KNOWLEDGE HUB CENTERPIECE (FROSTED PEARL)
           ============================================================ -->
      <div class="lg:col-span-3 xl:col-span-4 flex justify-center items-center py-6 lg:py-0 relative z-20">

        <div class="nx-learning-focal relative w-[260px] h-[260px] sm:w-[310px] sm:h-[310px] xl:w-[360px] xl:h-[360px] rounded-full select-none flex items-center justify-center">

          <!-- Outer Multi-Color Pastel Volumetric Glow -->
          <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-purple-400 via-pink-400 to-amber-300 blur-3xl opacity-40 scale-125 pointer-events-none"></div>

          <!-- Thin Outermost Glowing Orbital Ring -->
          <div class="absolute inset-[-14px] rounded-full border border-purple-300/50 pointer-events-none"></div>

          <!-- Rotating Astrolabe / Orbital Calibration Track -->
          <svg class="nx-learning-dial absolute inset-[-14px] w-[calc(100%+28px)] h-[calc(100%+28px)] pointer-events-none" viewBox="0 0 200 200">
            <!-- Dashed orbit -->
            <circle cx="100" cy="100" r="96" fill="none" stroke="rgba(168, 85, 247, 0.4)" stroke-width="1.4" stroke-dasharray="4 6" />
            <!-- Cardinal tracking tick -->
            <circle cx="100" cy="4" r="3.5" fill="#EC4899" />
            <circle cx="100" cy="4" r="7" fill="#EC4899" opacity="0.3" />
          </svg>

          <!-- Outer Glassmorphic Bezel with Bright Pastel Jewel Rim -->
          <div class="relative w-full h-full rounded-full p-[3px] bg-gradient-to-b from-[#8B5CF6] via-[#EC4899] to-[#F59E0B] shadow-[0_15px_45px_rgba(236,72,153,0.25)] flex items-center justify-center">

            <!-- Frosted Pearl Disc -->
            <div class="w-full h-full rounded-full bg-white/95 backdrop-blur-2xl border border-white/60 p-6 sm:p-8 flex flex-col items-center justify-center text-center relative overflow-hidden shadow-inner">

              <!-- Inner Subtle Radial Sheen -->
              <div class="absolute inset-0 rounded-full bg-[radial-gradient(circle_at_50%_40%,rgba(237,233,254,0.6)_0%,transparent_70%)] pointer-events-none"></div>

              <!-- Minimalist Geometric Open Book Icon with Jewel Gradient Accent -->
              <div class="relative mb-3 sm:mb-4">
                <svg class="w-12 h-12 sm:w-14 sm:h-14 xl:w-16 xl:h-16 text-purple-700 drop-shadow-[0_4px_12px_rgba(139,92,246,0.3)]" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                  <!-- Book Bookmark Ribbon (Rose Accent) -->
                  <path d="M 24 10 L 26 18 L 24 16 L 22 18 Z" fill="#EC4899" />

                  <!-- Left Page -->
                  <path d="M 23 14 C 18 12, 10 13, 6 15 L 6 36 C 10 34, 18 33, 23 35 Z" 
                        fill="rgba(243, 232, 255, 0.95)" stroke="#7C3AED" stroke-width="1.8" stroke-linejoin="round" />
                  <!-- Right Page -->
                  <path d="M 25 14 C 30 12, 38 13, 42 15 L 42 36 C 38 34, 30 33, 25 35 Z" 
                        fill="rgba(252, 231, 243, 0.95)" stroke="#DB2777" stroke-width="1.8" stroke-linejoin="round" />
                  <!-- Center Spine -->
                  <path d="M 24 14 L 24 36" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" />
                </svg>
              </div>

              <!-- Brand Name -->
              <div class="text-[17px] sm:text-[19px] xl:text-[21px] font-extrabold tracking-[0.32em] text-slate-900 uppercase mb-1.5 font-sans">
                N E X O R A
              </div>

              <!-- Tagline -->
              <div class="text-[9px] sm:text-[10px] xl:text-[11px] font-mono font-bold tracking-[0.24em] text-purple-700 uppercase">
                LEARN &middot; GROW &middot; CONNECT
              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- ============================================================
           RIGHT SIDE: TWO VERTICAL EDITORIAL LEARNING TRACKS
           ============================================================ -->
      <div class="lg:col-span-4 xl:col-span-4 flex flex-col space-y-6 xl:space-y-8 z-20">

        <!-- ------------------------------------------------------------
             TRACK 01: FOR INVESTORS (FROSTED PASTEL CARD)
             ------------------------------------------------------------ -->
        <div class="nx-track-group nx-track-investors relative bg-white/90 backdrop-blur-xl border border-purple-200/90 rounded-3xl p-6 sm:p-7 shadow-sm">

          <!-- Track Header with Circular Glass Icon -->
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-purple-100">
            <div class="flex items-center gap-3.5">
              <!-- Circular Glass Icon: Investor Profile Silhouette -->
              <div class="w-11 h-11 rounded-2xl bg-purple-100 border border-purple-200 flex items-center justify-center shrink-0 text-purple-700 shadow-xs">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                  <circle cx="12" cy="7" r="4" />
                </svg>
              </div>

              <div>
                <span class="text-[11px] font-mono font-bold tracking-widest text-purple-700 uppercase block">
                  01 / TRACK
                </span>
                <h3 class="text-[20px] sm:text-[22px] font-extrabold text-slate-900 tracking-[-0.02em] leading-tight">
                  FOR INVESTORS
                </h3>
              </div>
            </div>

            <span class="text-[11px] font-mono font-bold text-purple-800 bg-purple-100 border border-purple-200 px-3 py-1 rounded-full">
              5 Modules
            </span>
          </div>

          <!-- 5 Compact Module Rows -->
          <ul class="space-y-1 mb-4" role="list">
            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-purple-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-purple-400 group-hover/item:text-purple-700 transition-colors shrink-0">
                  01
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Angel &amp; Syndicate Foundations
                </span>
              </div>
              <span class="text-purple-400 group-hover/item:text-purple-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-purple-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-purple-400 group-hover/item:text-purple-700 transition-colors shrink-0">
                  02
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Due Diligence &amp; Unit Economics
                </span>
              </div>
              <span class="text-purple-400 group-hover/item:text-purple-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-purple-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-purple-400 group-hover/item:text-purple-700 transition-colors shrink-0">
                  03
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Cap Tables, Dilution &amp; Investor Rights
                </span>
              </div>
              <span class="text-purple-400 group-hover/item:text-purple-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-purple-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-purple-400 group-hover/item:text-purple-700 transition-colors shrink-0">
                  04
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Early-Stage Valuation Frameworks
                </span>
              </div>
              <span class="text-purple-400 group-hover/item:text-purple-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-purple-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-purple-400 group-hover/item:text-purple-700 transition-colors shrink-0">
                  05
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  SPV Structuring &amp; Deal Execution
                </span>
              </div>
              <span class="text-purple-400 group-hover/item:text-purple-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>
          </ul>

          <!-- Exact CTA -->
          <div class="pt-2">
            <a href="login.php?role=investor"
              class="inline-flex items-center gap-2 text-[14px] font-bold text-purple-700 hover:text-purple-900 transition-colors group/cta">
              <span>Explore Investor Track</span>
              <span class="transition-transform group-hover/cta:translate-x-1.5" aria-hidden="true">&rarr;</span>
            </a>
          </div>

        </div>

        <!-- ------------------------------------------------------------
             TRACK 02: FOR STARTUPS (FROSTED PASTEL CARD)
             ------------------------------------------------------------ -->
        <div class="nx-track-group nx-track-startups relative bg-white/90 backdrop-blur-xl border border-pink-200/90 rounded-3xl p-6 sm:p-7 shadow-sm">

          <!-- Track Header with Circular Glass Icon -->
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-pink-100">
            <div class="flex items-center gap-3.5">
              <!-- Circular Glass Icon: Rocket Launch -->
              <div class="w-11 h-11 rounded-2xl bg-pink-100 border border-pink-200 flex items-center justify-center shrink-0 text-pink-700 shadow-xs">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z" />
                  <path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z" />
                  <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0" />
                  <path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5" />
                </svg>
              </div>

              <div>
                <span class="text-[11px] font-mono font-bold tracking-widest text-pink-700 uppercase block">
                  02 / TRACK
                </span>
                <h3 class="text-[20px] sm:text-[22px] font-extrabold text-slate-900 tracking-[-0.02em] leading-tight">
                  FOR STARTUPS
                </h3>
              </div>
            </div>

            <span class="text-[11px] font-mono font-bold text-pink-800 bg-pink-100 border border-pink-200 px-3 py-1 rounded-full">
              5 Modules
            </span>
          </div>

          <!-- 5 Compact Module Rows -->
          <ul class="space-y-1 mb-4" role="list">
            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-pink-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-pink-400 group-hover/item:text-pink-700 transition-colors shrink-0">
                  01
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Seed-to-Series A Playbook
                </span>
              </div>
              <span class="text-pink-400 group-hover/item:text-pink-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-pink-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-pink-400 group-hover/item:text-pink-700 transition-colors shrink-0">
                  02
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Pitch Deck &amp; Narrative Framing
                </span>
              </div>
              <span class="text-pink-400 group-hover/item:text-pink-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-pink-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-pink-400 group-hover/item:text-pink-700 transition-colors shrink-0">
                  03
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Valuation &amp; Cap Table Modeling
                </span>
              </div>
              <span class="text-pink-400 group-hover/item:text-pink-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-pink-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-pink-400 group-hover/item:text-pink-700 transition-colors shrink-0">
                  04
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Institutional Diligence Readiness
                </span>
              </div>
              <span class="text-pink-400 group-hover/item:text-pink-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>

            <li class="nx-module-row flex items-center justify-between py-2.5 px-2 rounded-xl transition-all duration-200 hover:bg-pink-50 cursor-pointer group/item">
              <div class="flex items-center gap-3">
                <span class="nx-mod-num text-[12px] font-mono font-bold text-pink-400 group-hover/item:text-pink-700 transition-colors shrink-0">
                  05
                </span>
                <span class="nx-mod-text text-[13.5px] sm:text-[14px] text-slate-700 group-hover/item:text-slate-900 font-semibold transition-colors">
                  Term Sheet Negotiation &amp; Close
                </span>
              </div>
              <span class="text-pink-400 group-hover/item:text-pink-700 transition-transform group-hover/item:translate-x-1 text-sm shrink-0" aria-hidden="true">&rarr;</span>
            </li>
          </ul>

          <!-- Exact CTA -->
          <div class="pt-2">
            <a href="login.php?role=startup"
              class="inline-flex items-center gap-2 text-[14px] font-bold text-pink-700 hover:text-pink-900 transition-colors group/cta">
              <span>Explore Founder Track</span>
              <span class="transition-transform group-hover/cta:translate-x-1.5" aria-hidden="true">&rarr;</span>
            </a>
          </div>

        </div>

      </div>

    </div>

  </div>

</section>

<!-- ============================================================
     SCOPED STYLES FOR THE FULL-SCREEN CINEMATIC EXPERIENCE
     ============================================================ -->
<style>
  .nx-learning-headline,
  .nx-learning-subheading,
  .nx-learning-focal,
  .nx-track-investors,
  .nx-track-startups,
  .nx-module-row {
    will-change: transform, opacity;
  }

  /* Astrolabe slow subtle spin */
  @keyframes nxAstrolabeSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  .nx-learning-dial {
    animation: nxAstrolabeSpin 120s linear infinite;
  }
</style>

<!-- ============================================================
     INTERACTION SCRIPT (GSAP + SCROLLTRIGGER)
     ============================================================ -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
      return;
    }

    const section = document.getElementById('nx-learning');
    if (!section) return;

    const tl = gsap.timeline({
      scrollTrigger: {
        trigger: section,
        start: 'top 75%',
        once: true
      }
    });

    // 1. Headline rises smoothly
    tl.fromTo('.nx-learning-headline',
      { y: 40, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.9, ease: 'power3.out' }
    );

    // 2. Centerpiece Hub scales from 0.85 -> 1 with glow
    tl.fromTo('.nx-learning-focal',
      { scale: 0.85, opacity: 0 },
      { scale: 1, opacity: 1, duration: 1.1, ease: 'power2.out' },
      '-=0.65'
    );

    // 3. Subheading reveals
    tl.fromTo('.nx-learning-subheading',
      { y: 20, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.75, ease: 'power3.out' },
      '-=0.75'
    );

    // 4. Connecting fiber wires progressive draw
    const wireInv = document.getElementById('nx-wire-investors');
    const wireSta = document.getElementById('nx-wire-startups');
    if (wireInv && wireSta) {
      const lenInv = wireInv.getTotalLength ? wireInv.getTotalLength() : 350;
      const lenSta = wireSta.getTotalLength ? wireSta.getTotalLength() : 350;
      gsap.fromTo([wireInv, wireSta],
        { strokeDasharray: lenInv, strokeDashoffset: lenInv },
        {
          strokeDashoffset: 0,
          duration: 1.3,
          ease: 'power2.inOut',
          scrollTrigger: {
            trigger: section,
            start: 'top 75%',
            once: true
          }
        }
      );
    }

    // 5. Investor Track & rows
    tl.fromTo('.nx-track-investors',
      { y: 30, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.7, ease: 'power3.out' },
      '-=0.55'
    );

    tl.fromTo('.nx-track-investors .nx-module-row',
      { x: -15, opacity: 0 },
      { x: 0, opacity: 1, duration: 0.4, stagger: 0.05, ease: 'power2.out' },
      '-=0.45'
    );

    // 6. Startup Track & rows
    tl.fromTo('.nx-track-startups',
      { y: 30, opacity: 0 },
      { y: 0, opacity: 1, duration: 0.7, ease: 'power3.out' },
      '-=0.35'
    );

    tl.fromTo('.nx-track-startups .nx-module-row',
      { x: -15, opacity: 0 },
      { x: 0, opacity: 1, duration: 0.4, stagger: 0.05, ease: 'power2.out' },
      '-=0.45'
    );

    // 7. Parallax depth on scroll
    gsap.to('.nx-learning-focal', {
      y: -30,
      ease: 'none',
      scrollTrigger: {
        trigger: section,
        start: 'top bottom',
        end: 'bottom top',
        scrub: 1.2
      }
    });

  });
</script>