<?php
/**
 * ============================================================================
 * includes/home/conversion-cta.php  (v4 - trust-first, no amounts or promises)
 * ----------------------------------------------------------------------------
 * PART 1 - HOW IT WORKS
 *   Left  (sticky on desktop): heading, Investor / Founder toggle, summary card
 *   Right : vertical timeline. The line fills as you scroll and each step
 *           lights up when you reach it. All content is always fully visible.
 * PART 2 - CLOSING CTA
 *   Split dark banner: message + buttons on the left, trust points on the right
 * - No fixed amounts, timelines, counts or guarantees anywhere in this section
 * - Respects prefers-reduced-motion, keyboard accessible tabs
 * ============================================================================
 */
$founder_url = function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder';

$icons = [
  'phone' => '<rect x="9" y="3" width="14" height="26" rx="3.5" /><path class="nxh2-draw" pathLength="60" d="M13 15 L15.5 17.5 L20 12.5" /><path d="M14 25 H18" />',
  'lock' => '<rect x="5" y="14" width="22" height="14" rx="3.5" /><path d="M10 14 V10 a6 6 0 0 1 12 0 V14" /><path class="nxh2-draw" pathLength="60" d="M16 19 V23" />',
  'chat' => '<path d="M5 7 a3 3 0 0 1 3-3 h16 a3 3 0 0 1 3 3 v12 a3 3 0 0 1-3 3 H14 l-6 6 v-6 H8 a3 3 0 0 1-3-3 Z" /><path class="nxh2-draw" pathLength="60" d="M10 12 H22 M10 16.5 H17" />',
  'shield' => '<path d="M16 3 L27 7 V15 C27 21.5 22.5 26.5 16 29 C9.5 26.5 5 21.5 5 15 V7 Z" /><path class="nxh2-draw" pathLength="60" d="M11 16 L15 20 L22 12" />',
];

$tracks = [
  'investor' => [
    ['Create your account', 'Sign up with a mobile OTP and complete a short profile. You decide how much you are comfortable investing.', 'Quick and simple', 'phone'],
    ['Browse verified startups', 'Every listing is confidential. Sign a digital NDA to unlock the full financial model and metrics.', 'Protected by digital NDA', 'lock'],
    ['Talk to founders and review', 'Chat in the secure dealroom. Check verified ARR, unit economics, churn and the cap table before you decide.', 'Private dealroom', 'chat'],
    ['Invest and get your certificate', 'Sign a digital MCA SAFE and pay through bank escrow. Your share certificate is issued once the round closes.', 'Protected by bank escrow', 'shield'],
  ],
  'founder' => [
    ['Create your profile', 'Sign up with a mobile OTP, then upload your pitch deck, financials and team details for verification.', 'Numbers verified for investors', 'phone'],
    ['Go live, stay anonymous', 'Investors see an anonymous teaser only. Your name and deck unlock after they sign a digital NDA.', 'You approve every viewer', 'lock'],
    ['Talk to interested investors', 'Your dealroom connects you with angels, micro-syndicates and family offices. Answer questions and agree terms in one place.', 'No cold emailing', 'chat'],
    ['Sign, get funded, close', 'Everyone signs a standard MCA SAFE. Funds move through bank escrow and all backers appear as one SPV line on your cap table.', 'Funds held safely in escrow', 'shield'],
  ],
];

$trust_points = [
  ['Verified startups', 'Every profile is reviewed by our team', 'emerald', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
  ['Protected payments', 'Funds move through bank escrow', 'blue', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
  ['Clear agreements', 'Digital NDAs and standard MCA SAFE', 'emerald', 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
];
?>

<style>
  #nx-how {
    --ease: cubic-bezier(.2, .7, .2, 1);
  }

  /* ---------- Layout ---------- */
  .nxh2-wrap {
    display: grid;
    grid-template-columns: 1fr;
    gap: 3rem;
  }

  @media (min-width: 1024px) {
    .nxh2-wrap {
      grid-template-columns: 5fr 7fr;
      gap: 5rem;
      align-items: start;
    }

    .nxh2-sticky {
      position: sticky;
      top: 7rem;
    }
  }

  /* ---------- Toggle ---------- */
  .nxh2-tabs {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    width: 100%;
    max-width: 360px;
    padding: 4px;
    border-radius: 14px;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
  }

  .nxh2-tab {
    position: relative;
    z-index: 1;
    padding: .65rem .85rem;
    font-size: .875rem;
    font-weight: 700;
    color: #64748B;
    border-radius: 10px;
    cursor: pointer;
    transition: color .25s ease;
    background: transparent;
    border: 0;
    text-align: center;
    white-space: nowrap;
  }

  @media (min-width: 640px) {
    .nxh2-tab {
      padding: .75rem 1.4rem;
      font-size: 1rem;
    }
  }

  .nxh2-tab[aria-selected="true"] {
    color: #0F172A;
  }

  .nxh2-pill {
    position: absolute;
    z-index: 0;
    top: 4px;
    bottom: 4px;
    left: 4px;
    width: calc(50% - 4px);
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .12);
    transition: transform .35s var(--ease);
  }

  .nxh2-tabs[data-active="founder"] .nxh2-pill {
    transform: translateX(100%);
  }

  .nxh2-tab:focus-visible {
    outline: 2px solid #10B981;
    outline-offset: 2px;
  }

  /* ---------- Summary + panels ---------- */
  .nxh2-sum[hidden],
  .nxh2-panel[hidden] {
    display: none;
  }

  .nxh2-sum,
  .nxh2-panel {
    animation: nxh2-in .5s var(--ease) both;
  }

  @keyframes nxh2-in {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: none;
    }
  }

  /* ---------- Timeline (all content always visible) ---------- */
  .nxh2-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .nxh2-step {
    position: relative;
    display: grid;
    grid-template-columns: 44px 1fr;
    column-gap: 1.25rem;
  }

  @media (min-width: 640px) {
    .nxh2-step {
      grid-template-columns: 48px 1fr;
      column-gap: 1.5rem;
    }
  }

  .nxh2-node {
    position: relative;
    z-index: 2;
    width: 44px;
    height: 44px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    background: #fff;
    color: #94A3B8;
    border: 2px solid #E2E8F0;
    transition: background .4s ease, color .4s ease, border-color .4s ease, box-shadow .4s ease, transform .4s var(--ease);
  }

  @media (min-width: 640px) {
    .nxh2-node {
      width: 48px;
      height: 48px;
      font-size: 1.05rem;
    }
  }

  .nxh2-step.is-done .nxh2-node {
    background: #10B981;
    border-color: #10B981;
    color: #fff;
  }

  .nxh2-step.is-active .nxh2-node {
    box-shadow: 0 0 0 6px rgba(16, 185, 129, .18);
    transform: scale(1.08);
  }

  .nxh2-seg {
    position: absolute;
    z-index: 1;
    left: 21px;
    top: 44px;
    bottom: -1.5rem;
    width: 2px;
    background: #E2E8F0;
    border-radius: 2px;
    overflow: hidden;
  }

  .nxh2-seg>i {
    display: block;
    width: 100%;
    height: 100%;
    background: linear-gradient(#10B981, #34D399);
    transform: scaleY(0);
    transform-origin: top;
    will-change: transform;
  }

  .nxh2-card {
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    background: #fff;
    padding: 1.5rem 1.6rem;
    transition: border-color .4s ease, box-shadow .4s ease, transform .4s var(--ease);
  }

  .nxh2-step.is-active .nxh2-card {
    border-color: #6EE7B7;
    box-shadow: 0 20px 44px -22px rgba(16, 185, 129, .55);
    transform: translateX(4px);
  }

  @media (max-width: 480px) {
    .nxh2-step {
      grid-template-columns: 36px 1fr;
      column-gap: .85rem;
    }
    .nxh2-node {
      width: 36px;
      height: 36px;
      font-size: .875rem;
    }
    .nxh2-seg {
      left: 17px;
    }
    .nxh2-card {
      padding: 1.15rem 1rem;
    }
    .nxh2-ico {
      width: 36px;
      height: 36px;
    }
    .nxh2-ico svg {
      width: 20px;
      height: 20px;
    }
  }

  .nxh2-ico {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #F0FDF4;
    color: #047857;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .nxh2-draw {
    stroke-dasharray: 60;
    stroke-dashoffset: 60;
    transition: stroke-dashoffset .9s ease;
  }

  .nxh2-step.is-done .nxh2-draw {
    stroke-dashoffset: 0;
  }

  .nxh2-chip {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    font-size: .85rem;
    font-weight: 600;
    color: #047857;
    background: #ECFDF5;
    border-radius: 9999px;
    padding: .35rem .85rem;
  }

  /* Founder = blue accent */
  .nxh2-founder .nxh2-chip {
    color: #1D4ED8;
    background: #EFF6FF;
  }

  .nxh2-founder .nxh2-ico {
    background: #EFF6FF;
    color: #1D4ED8;
  }

  .nxh2-founder .nxh2-step.is-done .nxh2-node {
    background: #2563EB;
    border-color: #2563EB;
  }

  .nxh2-founder .nxh2-step.is-active .nxh2-node {
    box-shadow: 0 0 0 6px rgba(37, 99, 235, .18);
  }

  .nxh2-founder .nxh2-seg>i {
    background: linear-gradient(#2563EB, #60A5FA);
  }

  .nxh2-founder .nxh2-step.is-active .nxh2-card {
    border-color: #93C5FD;
    box-shadow: 0 20px 44px -22px rgba(37, 99, 235, .5);
  }

  /* ---------- Closing banner ---------- */
  .nxh2-banner {
    position: relative;
    overflow: hidden;
  }

  .nxh-orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(70px);
    pointer-events: none;
  }

  .nxh-orb-a {
    width: 420px;
    height: 420px;
    background: rgba(16, 185, 129, .28);
    top: -160px;
    left: -120px;
    animation: nxh-drift-a 16s ease-in-out infinite alternate;
  }

  .nxh-orb-b {
    width: 380px;
    height: 380px;
    background: rgba(59, 130, 246, .24);
    bottom: -160px;
    right: -100px;
    animation: nxh-drift-b 18s ease-in-out infinite alternate;
  }

  @keyframes nxh-drift-a {
    to {
      transform: translate(90px, 50px) scale(1.12);
    }
  }

  @keyframes nxh-drift-b {
    to {
      transform: translate(-80px, -40px) scale(1.1);
    }
  }

  .nxh-grid {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image: linear-gradient(rgba(255, 255, 255, .05) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .05) 1px, transparent 1px);
    background-size: 44px 44px;
    -webkit-mask-image: radial-gradient(ellipse at 30% 40%, #000 20%, transparent 75%);
    mask-image: radial-gradient(ellipse at 30% 40%, #000 20%, transparent 75%);
  }

  .nxh-shimmer {
    position: relative;
    overflow: hidden;
  }

  .nxh-shimmer::after {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    left: -60%;
    width: 40%;
    background: linear-gradient(100deg, transparent, rgba(255, 255, 255, .55), transparent);
    transform: skewX(-18deg);
    animation: nxh-shine 4.5s ease-in-out 1.5s infinite;
  }

  @keyframes nxh-shine {
    0% {
      left: -60%;
    }

    35% {
      left: 140%;
    }

    100% {
      left: 140%;
    }
  }

  /* Scroll reveal (only hides content once JS is running and the observer is ready) */
  .nxh2-js .nxh2-rise {
    opacity: 0;
    transform: translateY(22px);
    transition: opacity .7s var(--ease) calc(var(--d, 0) * 1ms), transform .7s var(--ease) calc(var(--d, 0) * 1ms);
  }

  .nxh2-js .is-in .nxh2-rise,
  .nxh2-js .nxh2-rise.is-in {
    opacity: 1;
    transform: none;
  }

  .nxh2-stat {
    border: 1px solid rgba(255, 255, 255, .12);
    background: rgba(255, 255, 255, .06);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    border-radius: 20px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  @media (min-width: 1024px) {
    .nxh2-stat:nth-child(2) {
      margin-left: 2rem;
    }

    .nxh2-stat:nth-child(3) {
      margin-left: 4rem;
    }
  }

  @media (prefers-reduced-motion: reduce) {

    #nx-how *,
    #nx-how *::before,
    #nx-how *::after {
      animation: none !important;
      transition: none !important;
    }

    .nxh2-js .nxh2-rise {
      opacity: 1 !important;
      transform: none !important;
    }

    .nxh2-draw {
      stroke-dashoffset: 0 !important;
    }
  }
</style>

<section id="nx-how" class="py-20 sm:py-28 lg:py-32 bg-white text-[#0F172A] relative">
  <div class="max-w-[1200px] mx-auto px-5 sm:px-6 lg:px-8">

    <!-- ==========================================================
         PART 1: HOW IT WORKS  (sticky left + scroll timeline right)
         ========================================================== -->
    <div class="nxh2-wrap">

      <!-- LEFT: heading + toggle + summary -->
      <div class="nxh2-sticky">
        <p class="text-sm sm:text-base font-semibold text-emerald-600 mb-3">How it works</p>
        <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold tracking-[-0.035em] text-slate-900 leading-[1.18] sm:leading-[1.1]">
          Four clear steps from sign-up to closed deal.
        </h2>
        <p class="mt-4 sm:mt-5 text-base sm:text-lg md:text-xl text-slate-600 leading-relaxed max-w-md">
          Pick your side to see exactly what happens next. Both paths are private, transparent and legally documented.
        </p>

        <!-- Toggle -->
        <div class="mt-8">
          <div class="nxh2-tabs" role="tablist" aria-label="Choose your path" data-active="investor" data-tabs>
            <span class="nxh2-pill" aria-hidden="true"></span>
            <button type="button" role="tab" id="nxh-tab-investor" aria-selected="true"
              aria-controls="nxh-panel-investor" class="nxh2-tab" data-track-tab="investor">I am an investor</button>
            <button type="button" role="tab" id="nxh-tab-founder" aria-selected="false"
              aria-controls="nxh-panel-founder" tabindex="-1" class="nxh2-tab" data-track-tab="founder">I am a
              founder</button>
          </div>
        </div>

        <!-- Summary cards (switch with toggle) -->
        <div class="mt-8">
          <div class="nxh2-sum rounded-2xl border border-emerald-200 bg-emerald-50/60 p-6 sm:p-7 max-w-md"
            data-track-summary="investor">
            <p class="text-xs sm:text-sm font-bold text-emerald-700 uppercase tracking-wider">What you get</p>
            <p class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">Verified deals, clear terms</p>
            <p class="mt-2.5 text-sm sm:text-base text-slate-600 leading-relaxed">Back verified startups with legal agreements
              and bank escrow protecting every payment.</p>
            <button type="button" onclick="openSignUpChoiceModal()"
              class="group mt-5 sm:mt-6 inline-flex items-center gap-2 px-6 sm:px-7 py-3.5 sm:py-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm sm:text-base font-bold transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
              <span>Find startups to back</span>
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-emerald-400 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </button>
          </div>

          <div class="nxh2-sum rounded-2xl border border-blue-200 bg-blue-50/60 p-6 sm:p-7 max-w-md"
            data-track-summary="founder" hidden>
            <p class="text-xs sm:text-sm font-bold text-blue-700 uppercase tracking-wider">What you get</p>
            <p class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">A clear path to funding</p>
            <p class="mt-2.5 text-sm sm:text-base text-slate-600 leading-relaxed">No cold emails. Your identity stays private
              until you approve each investor.</p>
            <a href="<?= htmlspecialchars($founder_url, ENT_QUOTES) ?>"
              class="group mt-5 sm:mt-6 inline-flex items-center gap-2 px-6 sm:px-7 py-3.5 sm:py-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm sm:text-base font-bold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
              <span>List your startup to raise</span>
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-blue-200 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- RIGHT: timelines -->
      <div>
        <?php foreach ($tracks as $name => $steps):
          $last = count($steps) - 1; ?>
          <div class="nxh2-panel<?= $name === 'founder' ? ' nxh2-founder' : '' ?>" id="nxh-panel-<?= $name ?>"
            role="tabpanel" aria-labelledby="nxh-tab-<?= $name ?>" data-track-panel="<?= $name ?>" <?= $name === 'founder' ? 'hidden' : '' ?>>
            <ol class="nxh2-steps">
              <?php foreach ($steps as $i => $s): ?>
                <li class="nxh2-step">
                  <div class="nxh2-node"><?= $i + 1 ?></div>
                  <?php if ($i < $last): ?>
                    <div class="nxh2-seg" aria-hidden="true"><i></i></div><?php endif; ?>
                  <div class="nxh2-card">
                    <div class="flex items-start justify-between gap-4">
                      <div>
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                          <?= htmlspecialchars($s[0], ENT_QUOTES) ?>
                        </h3>
                        <p class="mt-2 text-base sm:text-[17px] text-slate-600 leading-relaxed">
                          <?= htmlspecialchars($s[1], ENT_QUOTES) ?>
                        </p>
                      </div>
                      <div class="nxh2-ico" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.2"
                          stroke-linecap="round" stroke-linejoin="round"><?= $icons[$s[3]] ?></svg>
                      </div>
                    </div>
                    <span class="nxh2-chip mt-4"><?= htmlspecialchars($s[2], ENT_QUOTES) ?></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ol>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ==========================================================
         PART 2: CLOSING BANNER  (split layout)
         ========================================================== -->
    <div data-nxh-observe
      class="nxh2-banner mt-24 sm:mt-32 lg:mt-36 rounded-[2rem] bg-slate-900 text-white px-6 py-12 sm:px-12 sm:py-16 lg:px-14 lg:py-20 shadow-2xl shadow-slate-900/20">

      <div class="nxh-orb nxh-orb-a" aria-hidden="true"></div>
      <div class="nxh-orb nxh-orb-b" aria-hidden="true"></div>
      <div class="nxh-grid" aria-hidden="true"></div>

      <div class="relative z-10 grid grid-cols-1 lg:grid-cols-5 gap-12 lg:gap-16 items-center">

        <!-- Left: message -->
        <div class="lg:col-span-3 text-center lg:text-left">
          <h2 class="nxh2-rise text-2xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-[1.18] sm:leading-[1.1]" style="--d:0">
            Ready to get started?
          </h2>
          <p class="nxh2-rise mt-4 sm:mt-5 text-base sm:text-lg md:text-xl text-slate-200 leading-relaxed max-w-xl mx-auto lg:mx-0"
            style="--d:120">
            Join the investors and founders building a more open, transparent and trusted startup marketplace.
          </p>

          <div class="nxh2-rise mt-8 sm:mt-9 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3.5 sm:gap-4"
            style="--d:240">
            <button type="button" onclick="openSignUpChoiceModal()"
              class="nxh-shimmer group w-full sm:w-auto px-7 sm:px-8 py-3.5 sm:py-4 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold text-sm sm:text-base transition-colors duration-200 cursor-pointer inline-flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
              <span>Find startups to back</span>
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-slate-950 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </button>
            <a href="<?= htmlspecialchars($founder_url, ENT_QUOTES) ?>"
              class="group w-full sm:w-auto px-7 sm:px-8 py-3.5 sm:py-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-sm sm:text-base border border-white/20 transition-colors duration-200 inline-flex items-center justify-center gap-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
              <span>List your startup to raise</span>
              <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 text-white/80 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </a>
          </div>

          <ul
            class="nxh2-rise mt-8 flex flex-wrap items-center justify-center lg:justify-start gap-x-6 gap-y-3 text-sm sm:text-base text-slate-200 font-medium"
            style="--d:360">
            <?php foreach (['Verified startup profiles', 'Standard MCA SAFE agreements', 'Payments protected by bank escrow'] as $b): ?>
              <li class="flex items-center gap-2">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <?= $b ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Right: trust stack (no numbers) -->
        <div class="lg:col-span-2 flex flex-col gap-4">
          <?php foreach ($trust_points as $k => $t):
            $tone = $t[2] === 'blue' ? 'bg-blue-400/15 text-blue-300' : 'bg-emerald-400/15 text-emerald-300'; ?>
            <div class="nxh2-stat nxh2-rise" style="--d:<?= 200 + $k * 140 ?>">
              <div class="w-12 h-12 rounded-xl <?= $tone ?> flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                  stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="<?= $t[3] ?>" />
                </svg>
              </div>
              <div>
                <p class="text-lg sm:text-xl font-extrabold leading-tight text-white"><?= htmlspecialchars($t[0], ENT_QUOTES) ?></p>
                <p class="mt-1 text-sm sm:text-base text-slate-300"><?= htmlspecialchars($t[1], ENT_QUOTES) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>

  </div>
</section>

<script>
  (function () {
    var root = document.getElementById('nx-how');
    if (!root) return;

    var tabsWrap = root.querySelector('[data-tabs]');
    var tabs = root.querySelectorAll('[data-track-tab]');
    var panels = root.querySelectorAll('[data-track-panel]');
    var sums = root.querySelectorAll('[data-track-summary]');

    /* ---------- Scroll-linked timeline ---------- */
    var ticking = false;
    function clamp(v) { return Math.max(0, Math.min(1, v)); }

    function updateTimeline() {
      ticking = false;
      var panel = root.querySelector('[data-track-panel]:not([hidden])');
      if (!panel) return;
      var steps = panel.querySelectorAll('.nxh2-step');
      var mid = window.innerHeight * 0.58;
      var centers = [];
      steps.forEach(function (s) {
        var r = s.querySelector('.nxh2-node').getBoundingClientRect();
        centers.push(r.top + r.height / 2);
      });

      var lastDone = -1;
      steps.forEach(function (s, i) {
        var done = centers[i] < mid;
        s.classList.toggle('is-done', done);
        if (done) lastDone = i;
        var seg = s.querySelector('.nxh2-seg > i');
        if (seg && i < centers.length - 1) {
          var p = clamp((mid - centers[i]) / (centers[i + 1] - centers[i]));
          seg.style.transform = 'scaleY(' + p + ')';
        }
      });
      steps.forEach(function (s, i) { s.classList.toggle('is-active', i === lastDone); });
    }

    function requestUpdate() {
      if (!ticking) { ticking = true; requestAnimationFrame(updateTimeline); }
    }
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);

    /* ---------- Investor / Founder toggle ---------- */
    function setTrack(name, focus) {
      tabsWrap.setAttribute('data-active', name);
      tabs.forEach(function (t) {
        var on = t.getAttribute('data-track-tab') === name;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        if (on && focus) t.focus();
      });
      panels.forEach(function (p) { p.hidden = p.getAttribute('data-track-panel') !== name; });
      sums.forEach(function (s) { s.hidden = s.getAttribute('data-track-summary') !== name; });
      requestUpdate();
    }

    tabs.forEach(function (t) {
      t.addEventListener('click', function () { setTrack(t.getAttribute('data-track-tab')); });
      t.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
          e.preventDefault();
          var cur = tabsWrap.getAttribute('data-active');
          setTrack(cur === 'investor' ? 'founder' : 'investor', true);
        }
      });
    });

    /* ---------- Closing banner: scroll reveal ----------
       Content is only hidden once the observer is ready, so without JS
       (or without IntersectionObserver) everything stays visible. */
    var blocks = root.querySelectorAll('[data-nxh-observe]');
    function reveal(b) { b.classList.add('is-in'); }

    if ('IntersectionObserver' in window) {
      root.classList.add('nxh2-js');
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) { reveal(en.target); io.unobserve(en.target); }
        });
      }, { threshold: 0.05, rootMargin: '0px 0px -8% 0px' });
      blocks.forEach(function (b) { io.observe(b); });
    }

    updateTimeline();
  })();
</script>