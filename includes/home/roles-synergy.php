<?php
/**
 * ============================================================================
 * includes/home/roles-synergy.php  (v4 - simplified investor side)
 * ----------------------------------------------------------------------------
 * PART 1 - INVESTORS : left = content + CTA, right = verified startup preview
 *                      (swipeable cards, chart draws) + register CTA.
 *                      NO amount slider, NO return/stake calculation.
 * PART 2 - FOUNDERS  : left = 4 clickable steps + CTA,
 *                      right = animated deal scenes that change with each step
 *                      (auto-plays, pauses on hover, click any step to jump)
 * - Respects prefers-reduced-motion, works without JS (static state)
 * ============================================================================
 */
$founder_url = function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder';
?>

<style>
  #nx-roles {
    --ease: cubic-bezier(.2, .7, .2, 1);
  }

  /* ---------- Shared animation primitives (only active when JS adds .nxr-js) ---------- */
  @keyframes nxr-draw {
    to {
      stroke-dashoffset: 0;
    }
  }

  @keyframes nxr-fade {
    to {
      opacity: 1;
    }
  }

  @keyframes nxr-fadeout {
    to {
      opacity: 0;
    }
  }

  @keyframes nxr-pop {
    0% {
      transform: scale(0);
    }

    70% {
      transform: scale(1.18);
    }

    100% {
      transform: scale(1);
    }
  }

  @keyframes nxr-float {
    from {
      transform: translateY(0);
    }

    to {
      transform: translateY(-7px);
    }
  }

  @keyframes nxr-ping {
    0% {
      transform: scale(1);
      opacity: .6;
    }

    100% {
      transform: scale(3.2);
      opacity: 0;
    }
  }

  @keyframes nxr-ring {
    0% {
      transform: scale(1);
      opacity: .7;
    }

    100% {
      transform: scale(1.9);
      opacity: 0;
    }
  }

  @keyframes nxr-scan {
    from {
      transform: translateY(-30px);
    }

    to {
      transform: translateY(250px);
    }
  }

  @keyframes nxr-flow {
    to {
      stroke-dashoffset: -20;
    }
  }

  @keyframes nxr-travel {
    0% {
      transform: translate(0, 0);
      opacity: 0;
    }

    15% {
      opacity: 1;
    }

    85% {
      opacity: 1;
    }

    100% {
      transform: translate(var(--tx), var(--ty));
      opacity: 0;
    }
  }

  @keyframes nxr-bar {
    to {
      transform: scaleX(1);
    }
  }

  @keyframes nxr-open {
    to {
      transform: translateY(-7px);
    }
  }

  @keyframes nxr-prog {
    from {
      transform: scaleX(0);
    }

    to {
      transform: scaleX(1);
    }
  }

  .nxr-pop,
  .nxr-dot,
  .nxr-bar,
  .nxr-ring,
  .nxr-ping,
  .nxr-shackle {
    transform-box: fill-box;
    transform-origin: center;
  }

  .nxr-bar {
    transform-origin: left center;
  }

  /* Hidden start states */
  .nxr-js .nxr-rise {
    opacity: 0;
    transform: translateY(12px);
  }

  .nxr-js .nxr-line,
  .nxr-js .nxr-drawp {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
  }

  .nxr-js .nxr-area,
  .nxr-js .nxr-fadein,
  .nxr-js .nxr-coin,
  .nxr-js .nxr-ping,
  .nxr-js .nxr-ring {
    opacity: 0;
  }

  .nxr-js .nxr-pop,
  .nxr-js .nxr-dot {
    transform: scale(0);
  }

  .nxr-js .nxr-bar {
    transform: scaleX(0);
  }

  /* Triggered by: investors block (.is-in) or founder scene (.is-active / .is-leaving) */
  .nxr-js .is-in .nxr-rise {
    opacity: 1;
    transform: none;
    transition: opacity .4s ease calc(var(--d, 0) * 1ms), transform .4s var(--ease) calc(var(--d, 0) * 1ms);
  }

  .nxr-js .is-in .nxr-line {
    animation: nxr-draw 1.1s cubic-bezier(.65, 0, .35, 1) .4s forwards;
  }

  .nxr-js .is-in .nxr-area {
    animation: nxr-fade .6s ease 1.1s forwards;
  }

  .nxr-js .is-in .nxr-dot {
    animation: nxr-pop .35s var(--ease) 1.4s forwards;
  }

  .nxr-js .is-in .nxr-ping {
    animation: nxr-ping 1.6s ease-out 1.7s infinite;
  }

  .nxr-js .is-in .nxr-float {
    animation: nxr-float 4s ease-in-out var(--fd, 0s) infinite alternate;
  }

  .nxr-js :is(.is-in, .is-active, .is-leaving) .nxr-drawp {
    animation: nxr-draw .5s ease calc(var(--d, 0) * 1ms) forwards;
  }

  .nxr-js :is(.is-in, .is-active, .is-leaving) .nxr-pop {
    animation: nxr-pop .35s var(--ease) calc(var(--d, 0) * 1ms) forwards;
  }

  .nxr-js :is(.is-in, .is-active, .is-leaving) .nxr-fadein {
    animation: nxr-fade .35s ease calc(var(--d, 0) * 1ms) forwards;
  }

  /* ---------- Founder scenes ---------- */
  .nxr-scene {
    opacity: 0;
    transition: opacity .25s ease;
    pointer-events: none;
  }

  .nxr-scene.is-active {
    opacity: 1;
  }

  .nxr-js .is-active .nxr-scan {
    animation: nxr-scan 1.4s ease-in-out infinite alternate;
  }

  .nxr-js .is-active .nxr-flow {
    stroke-dasharray: 5 5;
    animation: nxr-flow .5s linear infinite;
  }

  .nxr-js .is-active .nxr-ring {
    animation: nxr-ring 1.3s ease-out infinite;
  }

  .nxr-js .is-active .nxr-coin {
    animation: nxr-travel .9s ease-in-out calc(var(--d, 0) * 1ms) 2 both;
  }

  .nxr-js .is-active .nxr-bar {
    animation: nxr-bar 1.2s ease-out .6s forwards;
  }

  .nxr-js .is-active .nxr-redact {
    animation: nxr-fadeout .3s ease 1.8s forwards;
  }

  .nxr-js .is-active .nxr-lock {
    animation: nxr-fadeout .3s ease 1.9s forwards;
  }

  .nxr-js .is-active .nxr-shackle {
    animation: nxr-open .3s ease 1.5s forwards;
  }

  .nxr-js .is-leaving .nxr-scan,
  .nxr-js .is-leaving .nxr-flow,
  .nxr-js .is-leaving .nxr-ring {
    animation-play-state: running;
  }

  .nxr-flow {
    stroke-dasharray: 5 5;
  }

  /* ---------- Founder steps list ---------- */
  .nxr-steps {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .nxr-fstep {
    position: relative;
    display: grid;
    grid-template-columns: 40px 1fr;
    column-gap: 1rem;
    width: 100%;
    text-align: left;
    background: transparent;
    border: 0;
    padding: 0 0 1.25rem;
    cursor: pointer;
  }

  .nxr-fstep:focus-visible {
    outline: 2px solid #2563EB;
    outline-offset: 4px;
    border-radius: 12px;
  }

  .nxr-steps li:last-child .nxr-fstep {
    padding-bottom: 0;
  }

  .nxr-fstep::before {
    content: "";
    position: absolute;
    left: 19px;
    top: 40px;
    bottom: 0;
    width: 2px;
    background: #E2E8F0;
  }

  .nxr-steps li:last-child .nxr-fstep::before {
    display: none;
  }

  .nxr-fnode {
    width: 40px;
    height: 40px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: .9rem;
    background: #fff;
    color: #94A3B8;
    border: 2px solid #E2E8F0;
    transition: all .35s var(--ease);
    position: relative;
    z-index: 1;
  }

  .nxr-fstep.is-open .nxr-fnode {
    background: #2563EB;
    border-color: #2563EB;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(37, 99, 235, .15);
  }

  .nxr-ftitle {
    display: block;
    font-size: 1.15rem;
    font-weight: 700;
    color: #475569;
    padding-top: .45rem;
    transition: color .3s ease;
  }

  .nxr-fstep.is-open .nxr-ftitle {
    color: #0F172A;
  }

  .nxr-fdesc {
    display: grid;
    grid-template-rows: 1fr;
    transition: grid-template-rows .3s var(--ease), opacity .3s ease;
  }

  .nxr-fdesc>span {
    overflow: hidden;
    display: block;
  }

  .nxr-js .nxr-fstep:not(.is-open) .nxr-fdesc {
    grid-template-rows: 0fr;
    opacity: 0;
  }

  .nxr-ftext {
    display: block;
    margin-top: .45rem;
    font-size: 1rem;
    line-height: 1.65;
    color: #334155;
  }

  .nxr-fchip {
    display: inline-block;
    margin-top: .65rem;
    font-size: .85rem;
    font-weight: 600;
    color: #1D4ED8;
    background: #EFF6FF;
    border-radius: 9999px;
    padding: .3rem .8rem;
  }

  .nxr-prog {
    display: none;
    margin-top: .9rem;
    height: 3px;
    border-radius: 3px;
    background: #E2E8F0;
    overflow: hidden;
  }

  .nxr-prog>i {
    display: block;
    height: 100%;
    width: 100%;
    background: #2563EB;
    transform: scaleX(0);
    transform-origin: left;
  }

  .nxr-js .nxr-fstep.is-open .nxr-prog {
    display: block;
  }

  .nxr-js .nxr-fstep.is-open .nxr-prog>i {
    animation: nxr-prog 3.2s linear forwards;
  }

  .nxr-scene,
  .nxr-float {
    will-change: opacity, transform;
  }

  .nxr-fwrap.is-paused *,
  .nxr-fwrap.is-paused *::before {
    animation-play-state: paused !important;
  }

  /* ---------- Reduced motion ---------- */
  @media (prefers-reduced-motion: reduce) {

    #nx-roles *,
    #nx-roles *::before,
    #nx-roles *::after {
      animation: none !important;
      transition: none !important;
    }
  }

  /* ---------- Investor startup preview (no amounts) ---------- */
  .nxd {
    max-width: 440px;
    margin-inline: auto;
    width: 100%;
  }

  .nxd-live {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background: #10B981;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, .5);
    animation: nxd-live 1.8s ease-out infinite;
  }

  @keyframes nxd-live {
    70% {
      box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
    }

    100% {
      box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
  }

  .nxd-dot {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background: #CBD5E1;
    border: 0;
    padding: 0;
    cursor: pointer;
    transition: width .2s var(--ease), background .2s ease;
  }

  .nxd-dot.is-on {
    width: 24px;
    background: #059669;
  }

  .nxd-dot:focus-visible {
    outline: 2px solid #059669;
    outline-offset: 3px;
  }

  .nxd-stage {
    position: relative;
    height: 300px;
    margin-bottom: 46px;
  }

  .nxd-card {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    padding: 1.25rem;
    border-radius: 22px;
    background: #fff;
    border: 1px solid #E2E8F0;
    box-shadow: 0 24px 50px -24px rgba(15, 23, 42, .35);
    transition: transform .3s var(--ease), opacity .25s ease;
    will-change: transform;
  }

  .nxd-card[data-pos="0"] {
    transform: none;
    opacity: 1;
    z-index: 3;
  }

  .nxd-card[data-pos="1"] {
    transform: translateY(22px) scale(.95);
    opacity: .95;
    z-index: 2;
  }

  .nxd-card[data-pos="2"] {
    transform: translateY(44px) scale(.9);
    opacity: .85;
    z-index: 1;
  }

  .nxd-card.nxd-out {
    transform: translateX(115%) rotate(8deg) !important;
    opacity: 0 !important;
    z-index: 4 !important;
    transition: transform .22s ease-in, opacity .22s ease-in;
  }

  .nxd-card.nxd-snap {
    transition: none !important;
  }

  .nxd-logo {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    color: #fff;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .nxd-verified {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .8rem;
    font-weight: 700;
    color: #047857;
    background: #D1FAE5;
    border-radius: 9999px;
    padding: .3rem .7rem;
  }

  .nxd-metric {
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    border-radius: 12px;
    padding: .65rem .75rem;
  }

  .nxd-metric p {
    font-size: .8rem;
    font-weight: 600;
    color: #64748B;
  }

  .nxd-metric b {
    display: block;
    margin-top: .15rem;
    font-size: 1.25rem;
    font-weight: 800;
    color: #0F172A;
  }

  .nxd-spark {
    display: block;
    width: 100%;
    height: 64px;
    margin-top: .9rem;
  }

  .nxd-sline {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
  }

  .nxd-card[data-pos="0"] .nxd-sline {
    animation: nxr-draw .6s cubic-bezier(.65, 0, .35, 1) .05s forwards;
  }

  .nxd-panel {
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    border-radius: 22px;
    padding: 1.25rem;
  }

  @media (max-width: 480px) {
    .nxd-stage {
      height: 310px;
    }
    .nxd-card {
      padding: 1rem;
    }
    .nxd-metric {
      padding: .45rem .3rem;
      text-align: center;
    }
    .nxd-metric p {
      font-size: .7rem;
    }
    .nxd-metric b {
      font-size: 1.05rem;
    }
    .nxr-ftitle {
      font-size: 1rem;
    }
    .nxr-ftext {
      font-size: .875rem;
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .nxd-sline {
      stroke-dashoffset: 0;
    }
  }
</style>

<section id="nx-roles"
  class="py-20 sm:py-28 lg:py-32 bg-white text-[#0F172A] relative overflow-hidden border-b border-slate-200/80">
  <div class="max-w-[1200px] mx-auto px-5 sm:px-6 lg:px-8">

    <!-- Section Header -->
    <div class="text-center max-w-3xl mx-auto mb-16 sm:mb-20 lg:mb-24">
      <span class="inline-flex items-center gap-2 rounded-full bg-slate-900 text-white text-xs sm:text-sm font-semibold px-4 py-1.5 mb-4">
        How the marketplace works
      </span>
      <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold tracking-[-0.035em] text-slate-900 leading-[1.18] sm:leading-tight">
        One platform for the people who build<br class="hidden sm:inline" />
        <span class="text-emerald-600">and the people who back them.</span>
      </h2>
      <p class="mt-4 sm:mt-5 text-base sm:text-lg md:text-xl text-slate-600 leading-relaxed max-w-2xl mx-auto">
        NEXORA gives investors verified deals and gives founders a clear, fast path to a closed round, with the legal
        work and escrow already built in.
      </p>
    </div>

    <!-- ==========================================================
         PART 1: INVESTORS  (content left, startup preview right)
         ========================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">

      <!-- Content -->
      <div>
        <span
          class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 text-xs sm:text-sm font-semibold px-3.5 py-1.5 border border-emerald-100">
          <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
          For investors
        </span>

        <h3 class="mt-5 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight leading-[1.2]">
          Invest with confidence.<br />
          Start with any amount.
        </h3>

        <p class="text-slate-600 text-base sm:text-lg mt-4 leading-relaxed max-w-lg">
          Get direct access to vetted, revenue-generating startups without high net-worth barriers.
          Register and invest with an amount that suits you.
        </p>

        <ul class="mt-8 space-y-5 sm:space-y-6">
          <li class="flex items-start gap-3.5 sm:gap-4">
            <span
              class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <div>
              <h4 class="text-base sm:text-[17px] font-bold text-slate-900">Verified financial metrics</h4>
              <p class="text-sm sm:text-base text-slate-600 mt-1 leading-relaxed">Audited ARR, gross margin, CAC and retention, visible before you commit a rupee.</p>
            </div>
          </li>
          <li class="flex items-start gap-3.5 sm:gap-4">
            <span
              class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <div>
              <h4 class="text-base sm:text-[17px] font-bold text-slate-900">Any amount works</h4>
              <p class="text-sm sm:text-base text-slate-600 mt-1 leading-relaxed">There is no fixed or minimum amount. Invest what you are comfortable with.</p>
            </div>
          </li>
          <li class="flex items-start gap-3.5 sm:gap-4">
            <span
              class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <div>
              <h4 class="text-base sm:text-[17px] font-bold text-slate-900">Legal and escrow built in</h4>
              <p class="text-sm sm:text-base text-slate-600 mt-1 leading-relaxed">Digital NDAs, automated MCA SAFE agreements and bank escrow, all inside the platform.</p>
            </div>
          </li>
          <li class="flex items-start gap-3.5 sm:gap-4">
            <span
              class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <div>
              <h4 class="text-base sm:text-[17px] font-bold text-slate-900">Simple signup</h4>
              <p class="text-sm sm:text-base text-slate-600 mt-1 leading-relaxed">Register in minutes and browse verified startups at your own pace.</p>
            </div>
          </li>
        </ul>

        <div class="mt-9 sm:mt-10">
          <button type="button" onclick="openSignUpChoiceModal()"
            class="group w-full sm:w-auto px-7 sm:px-8 py-3.5 sm:py-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm sm:text-base transition-colors duration-200 shadow-sm inline-flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
            <span>Register to get started</span>
            <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-emerald-400 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Investor: simple startup preview + register CTA -->
      <?php
      $deals = [
        ['name' => 'Stealth SaaS Co.', 'tag' => 'B2B Fintech · Seed', 'ini' => 'S', 'bg' => '#0F172A', 'arr' => '3.2x', 'gm' => '78%', 'ret' => '94%', 'pts' => [8, 10, 9, 13, 15, 14, 19, 22, 24, 29, 33, 38]],
        ['name' => 'GreenLoop Logistics', 'tag' => 'Climate · Seed', 'ini' => 'G', 'bg' => '#047857', 'arr' => '2.6x', 'gm' => '41%', 'ret' => '89%', 'pts' => [12, 13, 16, 15, 19, 21, 24, 23, 28, 31, 34, 37]],
        ['name' => 'Nirmaan Health', 'tag' => 'HealthTech · Seed', 'ini' => 'N', 'bg' => '#1D4ED8', 'arr' => '4.1x', 'gm' => '66%', 'ret' => '91%', 'pts' => [4, 6, 7, 9, 8, 12, 14, 17, 19, 22, 26, 30]],
      ];
      if (!function_exists('nxd_path')) {
        function nxd_path($pts)
        {
          $max = max($pts);
          $min = min($pts);
          $n = count($pts);
          $d = '';
          foreach ($pts as $i => $v) {
            $x = round($i * (300 / ($n - 1)), 1);
            $y = round(52 - (($v - $min) / max(1, ($max - $min))) * 44, 1);
            $d .= ($i ? ' L' : 'M') . $x . ' ' . $y;
          }
          return $d;
        }
      }
      ?>
      <div data-nxr-observe class="nxd" data-deck>

        <div class="nxr-rise flex items-center justify-between mb-4" style="--d:0">
          <span class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-600">
            <span class="nxd-live"></span> Verified startups · sample preview
          </span>
          <div class="flex items-center gap-2" role="tablist" aria-label="Choose a startup">
            <?php foreach ($deals as $i => $d): ?>
              <button type="button" class="nxd-dot" data-dot="<?= $i ?>"
                aria-label="Show <?= htmlspecialchars($d['name'], ENT_QUOTES) ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="nxd-stage nxr-rise" style="--d:100">
          <?php foreach ($deals as $i => $d):
            $line = nxd_path($d['pts']);
            ?>
            <article class="nxd-card" data-card data-pos="<?= $i ?>">
              <header class="flex items-center gap-3">
                <span class="nxd-logo" style="background:<?= $d['bg'] ?>"><?= $d['ini'] ?></span>
                <div class="min-w-0 flex-1">
                  <h4 class="text-base sm:text-lg font-bold text-slate-900 truncate">
                    <?= htmlspecialchars($d['name'], ENT_QUOTES) ?>
                  </h4>
                  <p class="text-xs sm:text-sm text-slate-500 font-medium"><?= htmlspecialchars($d['tag'], ENT_QUOTES) ?></p>
                </div>
                <span class="nxd-verified">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  Verified
                </span>
              </header>

              <div class="grid grid-cols-3 gap-2.5 mt-4">
                <div class="nxd-metric">
                  <p>YoY growth</p><b><?= $d['arr'] ?></b>
                </div>
                <div class="nxd-metric">
                  <p>Gross margin</p><b><?= $d['gm'] ?></b>
                </div>
                <div class="nxd-metric">
                  <p>Retention</p><b><?= $d['ret'] ?></b>
                </div>
              </div>

              <svg class="nxd-spark" viewBox="0 0 300 60" preserveAspectRatio="none" aria-hidden="true">
                <defs>
                  <linearGradient id="nxdG<?= $i ?>" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#10B981" stop-opacity=".28" />
                    <stop offset="1" stop-color="#10B981" stop-opacity="0" />
                  </linearGradient>
                </defs>
                <path d="<?= $line ?> L300 60 L0 60 Z" fill="url(#nxdG<?= $i ?>)" />
                <path class="nxd-sline" pathLength="1" d="<?= $line ?>" fill="none" stroke="#059669" stroke-width="2.5"
                  stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
              </svg>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- Register panel -->
        <div class="nxd-panel nxr-rise" style="--d:250">
          <h4 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">Invest what you are comfortable with</h4>
          <p class="text-sm sm:text-base text-slate-600 mt-2 leading-relaxed">
            There is no fixed or minimum amount. Choose the amount that works for you and back verified
            startups. Register to start exploring deals.
          </p>
          <button type="button" onclick="openSignUpChoiceModal()"
            class="group mt-4 w-full px-6 py-3.5 sm:py-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm sm:text-base transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 inline-flex items-center justify-center gap-2">
            <span>Create your free account</span>
            <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-white transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </button>
          <p class="mt-2.5 text-xs text-slate-500 text-center font-medium">Sample startups are shown for illustration only.</p>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="my-24 sm:my-28 flex items-center gap-4" aria-hidden="true">
      <div class="h-px flex-1 bg-slate-200"></div>
      <div class="w-2 h-2 rounded-full bg-slate-300"></div>
      <div class="h-px flex-1 bg-slate-200"></div>
    </div>

    <!-- ==========================================================
         PART 2: FOUNDERS  (steps left, animated scenes right)
         ========================================================== -->
    <div class="nxr-fwrap grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center" data-nxr-founders>

      <!-- Content -->
      <div>
        <span
          class="inline-flex items-center gap-2 rounded-full bg-blue-50 text-blue-700 text-xs sm:text-sm font-semibold px-3.5 py-1.5 border border-blue-100">
          <span class="w-2 h-2 rounded-full bg-blue-500"></span>
          For startup founders · Seed to Series A
        </span>
        <h3 class="mt-5 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight leading-[1.2]">
          Raise capital on your terms.<br class="hidden sm:inline" />
          Close in as little as 14 days.
        </h3>
        <p class="text-slate-600 text-base sm:text-lg mt-4 leading-relaxed max-w-lg">
          Skip the endless pitch meetings. Here is exactly how a round works on NEXORA, from signing up to money in your
          account.
        </p>

        <ol class="nxr-steps mt-8 sm:mt-9">
          <li>
            <button type="button" class="nxr-fstep is-open" data-fstep aria-current="true">
              <span class="nxr-fnode">1</span>
              <span>
                <span class="nxr-ftitle">Create and verify your profile</span>
                <span class="nxr-fdesc"><span>
                    <span class="nxr-ftext">Add your deck, financials and team. Our team checks the numbers so investors
                      can trust them.</span>
                    <span class="nxr-fchip">Takes 1 to 2 days</span>
                    <span class="nxr-prog"><i></i></span>
                  </span></span>
              </span>
            </button>
          </li>
          <li>
            <button type="button" class="nxr-fstep" data-fstep>
              <span class="nxr-fnode">2</span>
              <span>
                <span class="nxr-ftitle">Go live, stay anonymous</span>
                <span class="nxr-fdesc"><span>
                    <span class="nxr-ftext">Investors see only an anonymous teaser. Your name and deck unlock only after
                      they sign a digital NDA.</span>
                    <span class="nxr-fchip">You approve every viewer</span>
                    <span class="nxr-prog"><i></i></span>
                  </span></span>
              </span>
            </button>
          </li>
          <li>
            <button type="button" class="nxr-fstep" data-fstep>
              <span class="nxr-fnode">3</span>
              <span>
                <span class="nxr-ftitle">Talk to interested investors</span>
                <span class="nxr-fdesc"><span>
                    <span class="nxr-ftext">Your dealroom reaches 1,850+ angels, micro-syndicates and family offices.
                      Answer questions and agree on terms in one place.</span>
                    <span class="nxr-fchip">No cold emailing</span>
                    <span class="nxr-prog"><i></i></span>
                  </span></span>
              </span>
            </button>
          </li>
          <li>
            <button type="button" class="nxr-fstep" data-fstep>
              <span class="nxr-fnode">4</span>
              <span>
                <span class="nxr-ftitle">Sign, receive funds, close</span>
                <span class="nxr-fdesc"><span>
                    <span class="nxr-ftext">Everyone signs a standard MCA SAFE note. Funds move through bank escrow and
                      all backers appear as one SPV line on your cap table.</span>
                    <span class="nxr-fchip">As little as 14 days</span>
                    <span class="nxr-prog"><i></i></span>
                  </span></span>
              </span>
            </button>
          </li>
        </ol>

        <div class="mt-9 flex flex-col sm:flex-row sm:items-center gap-4">
          <a href="<?= htmlspecialchars($founder_url, ENT_QUOTES) ?>"
            class="group inline-flex items-center justify-center gap-2 px-7 sm:px-8 py-3.5 sm:py-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm sm:text-base transition-colors duration-200 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
            <span>List your startup to raise</span>
            <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-blue-200 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
          </a>
          <p class="text-sm sm:text-base text-slate-600">Listing is free to start. You only move forward when you are comfortable.
          </p>
        </div>
      </div>

      <!-- Founder animated scenes -->
      <div>
        <svg viewBox="0 0 560 480" class="w-full h-auto" role="img"
          aria-label="Animated illustration of the founder journey: profile verification, anonymous listing with NDA, investor conversations, and escrow funding into one SPV line">
          <defs>
            <filter id="nxrFShadow" x="-20%" y="-20%" width="140%" height="150%">
              <feDropShadow dx="0" dy="10" stdDeviation="14" flood-color="#0F172A" flood-opacity="0.12" />
            </filter>
            <linearGradient id="nxrScanG" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0" stop-color="#3B82F6" stop-opacity="0" />
              <stop offset="1" stop-color="#3B82F6" stop-opacity="0.35" />
            </linearGradient>
            <clipPath id="nxrDocClip">
              <rect x="90" y="122" width="180" height="250" rx="12" />
            </clipPath>
            <symbol id="nxrPerson" viewBox="0 0 24 24">
              <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2" />
              <path d="M4 21 c0-4.5 3.5-7 8-7 s8 2.5 8 7" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" />
            </symbol>
          </defs>

          <!-- Backdrop -->
          <rect x="10" y="10" width="540" height="460" rx="36" fill="#EFF6FF" />
          <circle cx="480" cy="70" r="90" fill="#DBEAFE" opacity="0.7" />
          <circle cx="70" cy="420" r="70" fill="#DBEAFE" opacity="0.6" />

          <!-- Window frame -->
          <g filter="url(#nxrFShadow)">
            <rect x="60" y="56" width="440" height="368" rx="20" fill="#FFFFFF" />
          </g>
          <circle cx="84" cy="76" r="4" fill="#E2E8F0" />
          <circle cx="100" cy="76" r="4" fill="#E2E8F0" />
          <circle cx="116" cy="76" r="4" fill="#E2E8F0" />
          <line x1="60" y1="96" x2="500" y2="96" stroke="#F1F5F9" />

          <!-- ============ SCENE 1: Profile review ============ -->
          <g class="nxr-scene" data-scene>
            <text x="140" y="80" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14" font-weight="700"
              fill="#334155">Profile review</text>

            <rect x="90" y="122" width="180" height="250" rx="12" fill="#F8FAFC" stroke="#E2E8F0" />
            <rect x="106" y="138" width="60" height="10" rx="5" fill="#CBD5E1" />
            <rect x="106" y="164" width="148" height="8" rx="4" fill="#E2E8F0" />
            <rect x="106" y="182" width="120" height="8" rx="4" fill="#E2E8F0" />
            <rect x="106" y="200" width="148" height="8" rx="4" fill="#E2E8F0" />
            <rect x="106" y="222" width="148" height="70" rx="8" fill="#EFF6FF" />
            <path d="M114 280 L144 262 L174 270 L204 248 L234 252 L246 240" fill="none" stroke="#2563EB"
              stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
            <rect x="106" y="306" width="148" height="8" rx="4" fill="#E2E8F0" />
            <rect x="106" y="324" width="100" height="8" rx="4" fill="#E2E8F0" />
            <rect x="106" y="342" width="130" height="8" rx="4" fill="#E2E8F0" />
            <g clip-path="url(#nxrDocClip)">
              <g class="nxr-scan">
                <rect x="90" y="122" width="180" height="30" fill="url(#nxrScanG)" />
                <rect x="90" y="150" width="180" height="3" fill="#3B82F6" />
              </g>
            </g>

            <g>
              <rect x="300" y="132" width="180" height="36" rx="10" fill="#F8FAFC" />
              <circle class="nxr-pop" style="--d:385" cx="318" cy="150" r="11" fill="#2563EB" />
              <path class="nxr-drawp" pathLength="1" style="--d:468" d="M313 150 L317 154 L323 146" fill="none"
                stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
              <text class="nxr-fadein" style="--d:385" x="338" y="155"
                font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="600" fill="#0F172A">Pitch
                deck</text>

              <rect x="300" y="180" width="180" height="36" rx="10" fill="#F8FAFC" />
              <circle class="nxr-pop" style="--d:715" cx="318" cy="198" r="11" fill="#2563EB" />
              <path class="nxr-drawp" pathLength="1" style="--d:798" d="M313 198 L317 202 L323 194" fill="none"
                stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
              <text class="nxr-fadein" style="--d:715" x="338" y="203"
                font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="600"
                fill="#0F172A">Financials</text>

              <rect x="300" y="228" width="180" height="36" rx="10" fill="#F8FAFC" />
              <circle class="nxr-pop" style="--d:1045" cx="318" cy="246" r="11" fill="#2563EB" />
              <path class="nxr-drawp" pathLength="1" style="--d:1128" d="M313 246 L317 250 L323 242" fill="none"
                stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
              <text class="nxr-fadein" style="--d:1045" x="338" y="251"
                font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="600" fill="#0F172A">Team
                details</text>
            </g>

            <g class="nxr-pop" style="--d:1430">
              <rect x="300" y="292" width="180" height="64" rx="16" fill="#DBEAFE" />
              <path d="M326 308 L342 314 L342 326 C342 334 336 339 326 343 C316 339 310 334 310 326 L310 314 Z"
                transform="translate(4 -1)" fill="#2563EB" />
              <path d="M320 326 L325 331 L334 320" transform="translate(4 -1)" fill="none" stroke="#fff"
                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
              <text x="360" y="320" font-family="ui-sans-serif, system-ui, sans-serif" font-size="13" font-weight="700"
                fill="#1E3A8A">Verified by NEXORA</text>
              <text x="360" y="337" font-family="ui-sans-serif, system-ui, sans-serif" font-size="12" font-weight="500"
                fill="#2563EB">Ready for investors</text>
            </g>
          </g>

          <!-- ============ SCENE 2: Anonymous listing + NDA ============ -->
          <g class="nxr-scene" data-scene>
            <text x="140" y="80" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14" font-weight="700"
              fill="#334155">Anonymous listing</text>

            <rect x="90" y="122" width="200" height="250" rx="14" fill="#F8FAFC" stroke="#E2E8F0" />
            <circle cx="126" cy="158" r="18" fill="#2563EB" />
            <text x="154" y="156" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14.5" font-weight="700"
              fill="#0F172A">Stealth SaaS Co.</text>
            <text x="154" y="174" font-family="ui-sans-serif, system-ui, sans-serif" font-size="12" font-weight="500" fill="#64748B">B2B
              Fintech · Seed</text>

            <rect x="106" y="210" width="168" height="40" rx="8" fill="#fff" stroke="#E2E8F0" />
            <text x="118" y="235" font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="600"
              fill="#0F172A">ARR · ₹2.4 Cr</text>
            <rect x="106" y="262" width="168" height="40" rx="8" fill="#fff" stroke="#E2E8F0" />
            <text x="118" y="287" font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="600"
              fill="#0F172A">Gross margin · 78%</text>
            <rect x="106" y="314" width="168" height="40" rx="8" fill="#fff" stroke="#E2E8F0" />
            <text x="118" y="339" font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="600"
              fill="#0F172A">Pitch deck · view</text>

            <!-- Redaction layer -->
            <g class="nxr-redact">
              <rect x="150" y="142" width="124" height="40" rx="6" fill="#CBD5E1" />
              <rect x="106" y="210" width="168" height="40" rx="8" fill="#CBD5E1" />
              <rect x="106" y="262" width="168" height="40" rx="8" fill="#CBD5E1" />
              <rect x="106" y="314" width="168" height="40" rx="8" fill="#CBD5E1" />
            </g>
            <!-- Lock -->
            <g class="nxr-lock">
              <rect x="171" y="244" width="38" height="30" rx="7" fill="#1E3A8A" />
              <path class="nxr-shackle" d="M178 244 V236 a12 12 0 0 1 24 0 V244" fill="none" stroke="#1E3A8A"
                stroke-width="4" stroke-linecap="round" />
              <circle cx="190" cy="259" r="3.5" fill="#fff" />
            </g>

            <!-- Investor + NDA -->
            <g class="nxr-pop" style="--d:165">
              <circle cx="390" cy="156" r="24" fill="#DBEAFE" />
              <use href="#nxrPerson" x="378" y="144" width="24" height="24" color="#2563EB" />
            </g>
            <text class="nxr-fadein" style="--d:220" x="390" y="196" text-anchor="middle"
              font-family="ui-sans-serif, system-ui, sans-serif" font-size="12.5" font-weight="600" fill="#334155">Investor</text>
            <line class="nxr-flow" x1="390" y1="204" x2="390" y2="224" stroke="#93C5FD" stroke-width="2" />

            <g class="nxr-pop" style="--d:495">
              <rect x="320" y="228" width="140" height="92" rx="12" fill="#fff" stroke="#BFDBFE" stroke-width="1.5" />
              <text x="336" y="252" font-family="ui-sans-serif, system-ui, sans-serif" font-size="13.5" font-weight="700"
                fill="#0F172A">Digital NDA</text>
              <rect x="336" y="262" width="108" height="6" rx="3" fill="#E2E8F0" />
              <rect x="336" y="274" width="80" height="6" rx="3" fill="#E2E8F0" />
            </g>
            <path class="nxr-drawp" pathLength="1" style="--d:825" d="M336 306 q10 -16 18 -2 t18 -4 t20 2" fill="none"
              stroke="#2563EB" stroke-width="2.2" stroke-linecap="round" />
            <g class="nxr-pop" style="--d:1265">
              <circle cx="452" cy="236" r="11" fill="#10B981" />
              <path d="M446.5 236 L450.5 240 L457.5 232" fill="none" stroke="#fff" stroke-width="2.2"
                stroke-linecap="round" stroke-linejoin="round" />
            </g>
            <g class="nxr-pop" style="--d:1705">
              <rect x="326" y="334" width="138" height="30" rx="15" fill="#D1FAE5" />
              <text x="395" y="353" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif"
                font-size="12" font-weight="700" fill="#047857">Approved by you</text>
            </g>
          </g>

          <!-- ============ SCENE 3: Dealroom with investors ============ -->
          <g class="nxr-scene" data-scene>
            <text x="140" y="80" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14" font-weight="700"
              fill="#334155">Your dealroom</text>

            <!-- connectors -->
            <line class="nxr-flow" x1="280" y1="236" x2="280" y2="141" stroke="#93C5FD" stroke-width="2" />
            <line class="nxr-flow" x1="280" y1="236" x2="410" y2="188" stroke="#93C5FD" stroke-width="2" />
            <line class="nxr-flow" x1="280" y1="236" x2="410" y2="284" stroke="#93C5FD" stroke-width="2" />
            <line class="nxr-flow" x1="280" y1="236" x2="280" y2="331" stroke="#93C5FD" stroke-width="2" />
            <line class="nxr-flow" x1="280" y1="236" x2="150" y2="284" stroke="#93C5FD" stroke-width="2" />
            <line class="nxr-flow" x1="280" y1="236" x2="150" y2="188" stroke="#93C5FD" stroke-width="2" />

            <!-- center -->
            <circle class="nxr-ring" cx="280" cy="236" r="40" fill="none" stroke="#60A5FA" stroke-width="2" />
            <circle cx="280" cy="236" r="40" fill="#2563EB" />
            <text x="280" y="243" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif" font-size="17"
              font-weight="800" fill="#fff">You</text>

            <!-- investors -->
            <g class="nxr-pop" style="--d:220">
              <circle cx="280" cy="141" r="22" fill="#DBEAFE" stroke="#fff" stroke-width="3" />
              <use href="#nxrPerson" x="268" y="129" width="24" height="24" color="#2563EB" />
            </g>
            <g class="nxr-pop" style="--d:358">
              <circle cx="410" cy="188" r="22" fill="#D1FAE5" stroke="#fff" stroke-width="3" />
              <use href="#nxrPerson" x="398" y="176" width="24" height="24" color="#059669" />
            </g>
            <g class="nxr-pop" style="--d:495">
              <circle cx="410" cy="284" r="22" fill="#DBEAFE" stroke="#fff" stroke-width="3" />
              <use href="#nxrPerson" x="398" y="272" width="24" height="24" color="#2563EB" />
            </g>
            <g class="nxr-pop" style="--d:632">
              <circle cx="280" cy="331" r="22" fill="#D1FAE5" stroke="#fff" stroke-width="3" />
              <use href="#nxrPerson" x="268" y="319" width="24" height="24" color="#059669" />
            </g>
            <g class="nxr-pop" style="--d:770">
              <circle cx="150" cy="284" r="22" fill="#DBEAFE" stroke="#fff" stroke-width="3" />
              <use href="#nxrPerson" x="138" y="272" width="24" height="24" color="#2563EB" />
            </g>
            <g class="nxr-pop" style="--d:908">
              <circle cx="150" cy="188" r="22" fill="#D1FAE5" stroke="#fff" stroke-width="3" />
              <use href="#nxrPerson" x="138" y="176" width="24" height="24" color="#059669" />
            </g>

            <!-- chat bubbles -->
            <g class="nxr-pop" style="--d:1320">
              <rect x="340" y="106" width="156" height="38" rx="12" fill="#fff" stroke="#BFDBFE" stroke-width="1.5" />
              <text x="352" y="129" font-family="ui-sans-serif, system-ui, sans-serif" font-size="12"
                fill="#0F172A">Can we see the cap table?</text>
            </g>
            <g class="nxr-pop" style="--d:1815">
              <rect x="325" y="350" width="165" height="38" rx="12" fill="#2563EB" />
              <text x="337" y="373" font-family="ui-sans-serif, system-ui, sans-serif" font-size="12"
                fill="#fff">Sure, shared in dealroom</text>
            </g>

            <text class="nxr-fadein" style="--d:1045" x="170" y="405" text-anchor="middle"
              font-family="ui-sans-serif, system-ui, sans-serif" font-size="13" font-weight="600" fill="#334155">Reach
              1,850+ investors</text>
          </g>

          <!-- ============ SCENE 4: Funds and closing ============ -->
          <g class="nxr-scene" data-scene>
            <text x="140" y="80" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14" font-weight="700"
              fill="#334155">Funds and closing</text>

            <!-- investors pledging -->
            <g class="nxr-pop" style="--d:110">
              <rect x="80" y="128" width="108" height="46" rx="12" fill="#F8FAFC" stroke="#E2E8F0" /><text x="94"
                y="146" font-family="ui-sans-serif, system-ui, sans-serif" font-size="11.5" fill="#64748B">Investor
                A</text><text x="94" y="165" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14"
                font-weight="800" fill="#0F172A">₹5 L</text>
            </g>
            <g class="nxr-pop" style="--d:220">
              <rect x="80" y="194" width="108" height="46" rx="12" fill="#F8FAFC" stroke="#E2E8F0" /><text x="94"
                y="212" font-family="ui-sans-serif, system-ui, sans-serif" font-size="11.5" fill="#64748B">Investor
                B</text><text x="94" y="231" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14"
                font-weight="800" fill="#0F172A">₹10 L</text>
            </g>
            <g class="nxr-pop" style="--d:330">
              <rect x="80" y="260" width="108" height="46" rx="12" fill="#F8FAFC" stroke="#E2E8F0" /><text x="94"
                y="278" font-family="ui-sans-serif, system-ui, sans-serif" font-size="11.5" fill="#64748B">Investor
                C</text><text x="94" y="297" font-family="ui-sans-serif, system-ui, sans-serif" font-size="14"
                font-weight="800" fill="#0F172A">₹25 L</text>
            </g>

            <!-- flows to escrow -->
            <path class="nxr-flow" d="M188 152 L246 232" fill="none" stroke="#93C5FD" stroke-width="2" />
            <path class="nxr-flow" d="M188 218 L246 238" fill="none" stroke="#93C5FD" stroke-width="2" />
            <path class="nxr-flow" d="M188 284 L246 246" fill="none" stroke="#93C5FD" stroke-width="2" />
            <circle class="nxr-coin" style="--d:495;--tx:58px;--ty:80px" cx="188" cy="152" r="5" fill="#10B981" />
            <circle class="nxr-coin" style="--d:605;--tx:58px;--ty:20px" cx="188" cy="218" r="5" fill="#10B981" />
            <circle class="nxr-coin" style="--d:715;--tx:58px;--ty:-38px" cx="188" cy="284" r="5" fill="#10B981" />

            <!-- escrow -->
            <g class="nxr-pop" style="--d:440">
              <circle cx="280" cy="240" r="34" fill="#D1FAE5" stroke="#34D399" stroke-width="2" />
              <path d="M280 222 L296 228 V240 C296 249 290 254 280 258 C270 254 264 249 264 240 V228 Z"
                fill="#10B981" />
              <path d="M272 240 L278 246 L289 234" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"
                stroke-linejoin="round" />
              <text x="280" y="293" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif"
                font-size="12.5" font-weight="700" fill="#047857">Bank escrow</text>
            </g>

            <!-- to SPV -->
            <line class="nxr-flow" x1="314" y1="240" x2="380" y2="240" stroke="#93C5FD" stroke-width="2" />
            <circle class="nxr-coin" style="--d:990;--tx:62px;--ty:0px" cx="316" cy="240" r="5" fill="#2563EB" />
            <g class="nxr-pop" style="--d:825">
              <rect x="380" y="210" width="108" height="58" rx="14" fill="#2563EB" />
              <text x="434" y="234" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif"
                font-size="13.5" font-weight="800" fill="#fff">1 SPV line</text>
              <text x="434" y="252" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif"
                font-size="11" fill="#DBEAFE">on your cap table</text>
            </g>

            <!-- funding progress -->
            <text x="84" y="334" font-family="ui-sans-serif, system-ui, sans-serif" font-size="12.5" font-weight="700"
              fill="#334155">Funds received</text>
            <text class="nxr-fadein" style="--d:1760" x="480" y="334" text-anchor="end"
              font-family="ui-sans-serif, system-ui, sans-serif" font-size="12.5" font-weight="700" fill="#2563EB">₹40 L
              pooled</text>
            <rect x="84" y="344" width="396" height="12" rx="6" fill="#E2E8F0" />
            <rect class="nxr-bar" x="84" y="344" width="396" height="12" rx="6" fill="#2563EB" />

            <g class="nxr-pop" style="--d:1870">
              <rect x="246" y="376" width="238" height="30" rx="15" fill="#D1FAE5" />
              <text x="365" y="395" text-anchor="middle" font-family="ui-sans-serif, system-ui, sans-serif"
                font-size="12" font-weight="700" fill="#047857">Closed in as little as 14 days</text>
            </g>
          </g>
        </svg>
      </div>
    </div>

  </div>
</section>

<script>
  (function () {
    var root = document.getElementById('nx-roles');
    if (!root) return;

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var hasIO = 'IntersectionObserver' in window;

    /* ---------- Count-up for metrics (only runs on elements with data-count) ---------- */
    function fmt(n, dec) {
      return dec ? n.toFixed(dec) : Math.round(n).toLocaleString('en-IN');
    }
    function countUp(el) {
      var end = parseFloat(el.getAttribute('data-count'));
      var dec = parseInt(el.getAttribute('data-dec') || '0', 10);
      var pre = el.getAttribute('data-prefix') || '';
      var suf = el.getAttribute('data-suffix') || '';
      if (isNaN(end)) return;
      var start = null, dur = 1000;
      function tick(ts) {
        if (start === null) start = ts;
        var p = Math.min((ts - start) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = pre + fmt(end * eased, dec) + suf;
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    }

    var inv = root.querySelector('[data-nxr-observe]');
    var founders = root.querySelector('[data-nxr-founders]');
    var steps = Array.prototype.slice.call(root.querySelectorAll('[data-fstep]'));
    var scenes = Array.prototype.slice.call(root.querySelectorAll('[data-scene]'));

    /* Animations off: leave everything in its final, readable state and still let steps switch scenes */
    if (reduce || !hasIO) {
      if (scenes.length) scenes[0].classList.add('is-active');
      bindSteps(false);
      return;
    }

    root.classList.add('nxr-js');

    /* ---------- Investor visual ---------- */
    var ioInv = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('is-in');
          en.target.querySelectorAll('[data-count]').forEach(function (el) {
            setTimeout(function () { countUp(el); }, 150);
          });
          ioInv.unobserve(en.target);
        }
      });
    }, { threshold: 0.3 });
    if (inv) ioInv.observe(inv);

    /* ---------- Founder steps + scenes ---------- */
    var cur = -1;
    var started = false;

    function go(i) {
      if (i === cur) {
        // restart current step: progress bar + scene animations
        steps[i].classList.remove('is-open');
        scenes[i].classList.remove('is-active');
        void steps[i].offsetWidth;
      } else if (cur >= 0) {
        var prev = scenes[cur];
        prev.classList.remove('is-active');
        prev.classList.add('is-leaving');
        setTimeout(function () { prev.classList.remove('is-leaving'); }, 300);
      }
      steps.forEach(function (s, k) {
        s.classList.toggle('is-open', k === i);
        if (k === i) s.setAttribute('aria-current', 'true'); else s.removeAttribute('aria-current');
      });
      scenes[i].classList.add('is-active');
      cur = i;
    }

    function bindSteps(auto) {
      steps.forEach(function (s, k) {
        s.addEventListener('click', function () {
          if (!auto) {
            steps.forEach(function (x, j) { x.classList.toggle('is-open', j === k); });
            scenes.forEach(function (sc, j) { sc.classList.toggle('is-active', j === k); });
            return;
          }
          started = true;
          go(k);
        });
      });
    }

    if (!founders || !steps.length || scenes.length !== steps.length) return;

    bindSteps(true);

    // Progress bar finishing = move to the next step
    founders.addEventListener('animationend', function (e) {
      if (e.animationName === 'nxr-prog') go((cur + 1) % steps.length);
    });

    // Pause on hover / keyboard focus
    function pause(on) { founders.classList.toggle('is-paused', on); }
    var hovering = false, visible = false;
    function sync() { pause(hovering || !visible); }
    founders.addEventListener('mouseenter', function () { hovering = true; sync(); });
    founders.addEventListener('mouseleave', function () { hovering = false; sync(); });
    founders.addEventListener('focusin', function () { hovering = true; sync(); });
    founders.addEventListener('focusout', function () { hovering = false; sync(); });

    var ioF = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        visible = en.isIntersecting;
        if (visible && !started) { started = true; go(0); }
        sync();
      });
    }, { threshold: 0.35 });
    ioF.observe(founders);

    // Before first view: keep step 1 open but paused
    founders.classList.add('is-paused');
  })();
</script>

<script>
  /* ---------- Investor startup preview (swipeable cards, no amounts) ---------- */
  (function () {
    var deck = document.querySelector('[data-deck]');
    if (!deck) return;

    var cards = Array.prototype.slice.call(deck.querySelectorAll('[data-card]'));
    var dots = Array.prototype.slice.call(deck.querySelectorAll('[data-dot]'));
    var n = cards.length, cur = 0, hover = false, visible = false, timer = null;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function render(leaving) {
      cards.forEach(function (c, i) {
        var pos = (i - cur + n) % n;
        c.dataset.pos = pos;
        c.setAttribute('aria-hidden', pos === 0 ? 'false' : 'true');
        if (pos === 0) c.removeAttribute('inert'); else c.setAttribute('inert', '');
      });
      dots.forEach(function (d, i) {
        d.classList.toggle('is-on', i === cur);
        d.setAttribute('aria-pressed', i === cur ? 'true' : 'false');
      });
      if (leaving) {
        leaving.classList.add('nxd-out');
        setTimeout(function () {
          leaving.classList.add('nxd-snap');
          leaving.classList.remove('nxd-out');
          void leaving.offsetWidth;
          leaving.classList.remove('nxd-snap');
        }, 240);
      }
    }

    function go(i) {
      if (i === cur) return;
      var leaving = cards[cur];
      cur = (i + n) % n;
      render(leaving);
    }

    function start() {
      stop();
      if (reduce || !visible || hover) return;
      timer = setInterval(function () { go(cur + 1); }, 2600);
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    dots.forEach(function (d, i) {
      d.addEventListener('click', function () { go(i); start(); });
    });
    deck.addEventListener('mouseenter', function () { hover = true; stop(); });
    deck.addEventListener('mouseleave', function () { hover = false; start(); });
    deck.addEventListener('focusin', function () { hover = true; stop(); });
    deck.addEventListener('focusout', function () { hover = false; start(); });

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (es) {
        visible = es[0].isIntersecting;
        start();
      }, { threshold: 0.3 }).observe(deck);
    }

    render(null);
  })();
</script>