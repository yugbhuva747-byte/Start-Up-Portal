<?php
/**
 * ============================================================================
 * include/home/final-cta.php
 * ----------------------------------------------------------------------------
 * SECTION 09 — FINAL CALL TO ACTION (COLORFUL PASTEL AURORA EDITION)
 *
 * Radiant pastel sunset & aurora mesh closing banner:
 * Background: #FAF5FF with Soft Lavender (#EDE9FE), Blush Pink (#FCE7F3), Warm Peach (#FFF7ED)
 * Text: #0F172A (Deep Slate)
 * Accent: Vivid Jewel Gradient (Purple -> Pink -> Amber)
 * ============================================================================
 */
?>
<section id="nx-final-cta" class="py-24 sm:py-32 lg:py-40 bg-[#FAF5FF] text-[#0F172A] relative z-20 overflow-hidden border-t border-purple-100" aria-label="Get Started with Nexora">

  <!-- Ambient Pastel Mesh Glows -->
  <div class="absolute inset-0 pointer-events-none overflow-hidden">
    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[800px] h-[500px] rounded-full bg-gradient-to-r from-purple-200/60 via-pink-200/50 to-amber-100/60 blur-[130px]"></div>
    <div class="absolute -bottom-24 left-1/4 w-[500px] h-[400px] rounded-full bg-purple-200/50 blur-[120px]"></div>
    <div class="absolute -bottom-24 right-1/4 w-[500px] h-[400px] rounded-full bg-pink-200/50 blur-[120px]"></div>
  </div>

  <div class="max-w-[1440px] mx-auto px-6 lg:px-14 relative z-10 text-center">

    <!-- Eyebrow Pill -->
    <div class="inline-flex items-center gap-2 text-[12px] font-mono font-bold tracking-[0.18em] uppercase text-purple-900 bg-purple-100/90 border border-purple-200 px-4 py-1.5 rounded-full mb-8 sm:mb-10 nx-reveal shadow-xs">
      <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
      <span>NEXORA ECOSYSTEM &middot; 2026</span>
    </div>

    <!-- Main Closing Headline -->
    <h2 class="text-[46px] sm:text-[72px] lg:text-[98px] xl:text-[108px] font-extrabold leading-[0.92] tracking-[-0.045em] text-slate-900 mx-auto mb-6 sm:mb-8 font-sans nx-text-reveal">
      WHAT&rsquo;S NEXT<br>
      <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 bg-clip-text text-transparent">STARTS HERE.</span>
    </h2>

    <!-- Supporting Text -->
    <p class="text-[17px] sm:text-[20px] lg:text-[22px] leading-relaxed text-slate-600 max-w-[580px] mx-auto mb-10 sm:mb-12 font-normal nx-text-reveal">
      Whether you&rsquo;re scaling the next breakthrough company or allocating into high-conviction dealflow, step into Nexora.
    </p>

    <!-- Dual Action Buttons -->
    <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-5 nx-reveal">
      <a href="login.php?role=investor"
        class="h-[56px] sm:h-[58px] px-8 sm:px-10 rounded-full bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 text-white text-[15px] font-bold inline-flex items-center gap-2.5 shadow-lg shadow-pink-500/25 hover:shadow-pink-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 group">
        <span>Explore Opportunities</span>
        <span class="text-base transition-transform duration-200 group-hover:translate-x-1.5" aria-hidden="true">&rarr;</span>
      </a>
      <a href="login.php?role=startup"
        class="h-[56px] sm:h-[58px] px-8 sm:px-10 rounded-full bg-white text-purple-700 border-2 border-purple-200 hover:border-purple-300 hover:bg-purple-50 text-[15px] font-bold inline-flex items-center gap-2.5 transition-all duration-300 shadow-xs hover:scale-[1.02] active:scale-[0.98] group">
        <span>Raise Capital</span>
        <span class="text-base text-purple-500 transition-transform duration-200 group-hover:translate-x-1.5" aria-hidden="true">&rarr;</span>
      </a>
    </div>

    <!-- Trust Stats -->
    <div class="mt-14 sm:mt-16 pt-8 border-t border-purple-200/70 flex flex-wrap items-center justify-center gap-6 sm:gap-12 text-[12px] sm:text-[13px] font-mono text-slate-500 nx-reveal">
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
        <span><strong class="text-slate-900 font-bold">$42M+</strong> Capital Deployed</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-pink-500"></span>
        <span><strong class="text-slate-900 font-bold">180+</strong> Vetted Startups</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
        <span><strong class="text-slate-900 font-bold">2,400+</strong> Active Allocators</span>
      </div>
    </div>

  </div>

</section>