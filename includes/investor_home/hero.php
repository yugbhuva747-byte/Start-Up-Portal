<?php
/**
 * ============================================================================
 * includes/investor_home/hero.php
 * Global Investor Hero v3 — centered layout, no right column.
 * Flow: Headline -> email CTA -> trust row -> animated stats -> two-row
 * auto-scrolling verified-deal marquee.
 * NOTE: stats and deal cards below are sample numbers; replace with real data.
 * ============================================================================
 */
$nxDeals = [
  ['CS', 'CloudScale AI', 'Seed', 'Delaware C-Corp · SF & Bengaluru', '$1.2M ARR', '+240% YoY', 78, '$5,000', 'from-blue-600 to-indigo-600'],
  ['PF', 'PayFlow Matrix', 'Seed', 'Singapore Pte Ltd · London', '$15M GMV / mo', '+18% MoM', 92, '$2,500', 'from-indigo-600 to-purple-600'],
  ['SC', 'Synthetix Cyber', 'Pre-Series A', 'UK & Delaware · Gurugram', '$720K ARR', '+210% YoY', 88, '$4,000', 'from-cyan-600 to-blue-700'],
  ['AG', 'AgriLoop', 'Seed', 'India Pvt Ltd · Dubai', '$540K ARR', '+160% YoY', 64, '$2,500', 'from-emerald-600 to-teal-600'],
  ['MN', 'MedNode Health', 'Pre-Seed', 'Delaware C-Corp · Bengaluru', '$310K ARR', '+190% YoY', 51, '$3,000', 'from-rose-500 to-orange-500'],
  ['LX', 'LexiGrid', 'Seed', 'UK Ltd · Singapore', '$890K ARR', '+135% YoY', 83, '$5,000', 'from-violet-600 to-fuchsia-600'],
];
$nxRows = [$nxDeals, array_reverse($nxDeals)];
?>
<style>
  @keyframes nxUp {
    from {
      opacity: 0;
      transform: translateY(24px)
    }

    to {
      opacity: 1;
      transform: none
    }
  }

  @keyframes nxMarquee {
    to {
      transform: translateX(-50%)
    }
  }

  @keyframes nxFill {
    from {
      width: 0
    }
  }

  @keyframes nxDrift {

    0%,
    100% {
      transform: translate(0, 0)
    }

    50% {
      transform: translate(30px, -24px)
    }
  }

  .nx-in {
    opacity: 0;
    animation: nxUp .85s cubic-bezier(.2, .8, .2, 1) forwards;
  }

  .nx-track {
    display: flex;
    gap: 16px;
    width: max-content;
    animation: nxMarquee 48s linear infinite;
  }

  .nx-row:hover .nx-track {
    animation-play-state: paused;
  }

  .nx-track.rev {
    animation-direction: reverse;
    animation-duration: 56s;
  }

  .nx-fillbar {
    animation: nxFill 1.6s cubic-bezier(.2, .8, .2, 1) both;
  }

  .nx-orb {
    animation: nxDrift 14s ease-in-out infinite;
  }

  .nx-mask {
    -webkit-mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent);
    mask-image: linear-gradient(90deg, transparent, #000 10%, #000 90%, transparent);
  }

  @media (prefers-reduced-motion: reduce) {
    .nx-in {
      opacity: 1;
      animation: none
    }

    .nx-track,
    .nx-orb,
    .nx-fillbar {
      animation: none
    }
  }
</style>

<section class="relative pt-28 pb-16 md:pt-36 md:pb-24 overflow-hidden bg-[#F8FAFC]" id="investor-hero">

  <!-- Backdrop -->
  <div
    class="pointer-events-none absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:28px_28px] opacity-70">
  </div>
  <div
    class="nx-orb pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-blue-500/15 rounded-full blur-[150px]">
  </div>
  <div
    class="nx-orb pointer-events-none absolute top-1/3 -right-40 w-[500px] h-[400px] bg-indigo-500/10 rounded-full blur-[130px]"
    style="animation-delay:-6s"></div>

  <div class="relative max-w-[1440px] mx-auto px-6 lg:px-10">

    <!-- ============ CENTERED COPY ============ -->
    <div class="max-w-4xl mx-auto text-center">



      <h1
        class="nx-in mt-6 text-4xl sm:text-5xl lg:text-[68px] font-black text-slate-950 tracking-[-0.04em] leading-[1.04] font-satoshi"
        style="animation-delay:.15s">
        Don't Just Be a Customer.<br class="hidden sm:inline" />
        <span
          class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 font-black">
          Own a Piece of the Next Big Company.
        </span>
      </h1>

      <p class="nx-in mt-6 text-base sm:text-lg md:text-xl font-bold text-slate-800 leading-relaxed max-w-2xl mx-auto font-satoshi"
        style="animation-delay:.28s">
        Every famous company was once a small startup. The people who bought shares early made life-changing wealth. Now
        you don't have to be a billionaire to own real shares in verified, fast-growing companies before they become
        famous.
      </p>

      <!-- Email CTA -->
      <div class="nx-in mt-9" style="animation-delay:.4s">
        <form action="<?= function_exists('url') ? url('auth/register.php') : 'auth/register.php' ?>" method="GET"
          class="w-full max-w-xl mx-auto">
          <input type="hidden" name="role" value="investor">
          <div
            class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-1.5 sm:p-2 rounded-2xl sm:rounded-full bg-white border-2 border-blue-600 shadow-xl shadow-blue-600/20 focus-within:border-blue-700 focus-within:ring-4 focus-within:ring-blue-500/20 transition-all">
            <div class="flex items-center gap-3 px-4 py-2.5 sm:py-0 flex-1">
              <svg class="w-5 h-5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                <polyline points="22,6 12,13 2,6" />
              </svg>
              <input type="email" name="email" required placeholder="Enter your email to see open companies"
                class="w-full text-sm sm:text-base font-bold text-slate-900 placeholder:text-slate-500 placeholder:font-semibold bg-transparent focus:outline-none font-satoshi">
            </div>
            <button type="submit"
              class="px-8 py-3.5 rounded-xl sm:rounded-full text-sm sm:text-base font-black text-white bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 shadow-lg shadow-blue-600/35 hover:-translate-y-0.5 font-satoshi transition-all shrink-0">
              See Growing Companies &rarr;
            </button>
          </div>
        </form>
        <p class="mt-3.5 text-xs sm:text-sm font-bold text-slate-700">
          100% legal company shares &middot; Verified government incorporation &middot;
          Already an investor? <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>"
            class="font-black text-blue-700 hover:text-blue-900 underline">Sign in &rarr;</a>
        </p>
      </div>

      <!-- Trust row -->
      <div
        class="nx-in mt-8 flex flex-wrap justify-center gap-x-6 gap-y-2.5 text-xs sm:text-sm font-black text-slate-900"
        style="animation-delay:.52s">
        <?php foreach ([
          '100% Legal Government Verified Companies',
          'Real Share Ownership in Your Name',
          'Zero Middleman or Broker Fees',
          'Invest Side-by-Side with Top Millionaire Angels'
        ] as $t): ?>
          <span
            class="inline-flex items-center gap-2 bg-white border-2 border-slate-300 px-3.5 py-1.5 rounded-full shadow-2xs">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <span class="font-extrabold text-slate-950 font-satoshi"><?= $t ?></span>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ============ STATS ============ -->
    <div
      class="nx-in mt-14 max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 rounded-2xl bg-white border-2 border-slate-300 shadow-xl shadow-slate-900/5 divide-x divide-y md:divide-y-0 divide-slate-300 overflow-hidden"
      style="animation-delay:.66s">
      <?php
      $stats = [
        [312, '', '+', 'Verified Companies to Own'],
        [48, '$', 'M+', 'Invested by People Like You'],
        [100, '', '%', 'Govt Registered & Audited'],
        [0, '', '%', 'Zero Hidden Fees or Cut'],
      ];
      foreach ($stats as $s): ?>
        <div class="px-5 py-6 text-center bg-white">
          <div class="text-3xl sm:text-4xl font-black text-slate-950 font-satoshi tracking-tight">
            <?= $s[1] ?><span data-count="<?= $s[0] ?>">0</span><?= $s[2] ?>
          </div>
          <div class="mt-1.5 text-xs sm:text-sm font-black text-slate-800 uppercase tracking-wider font-satoshi">
            <?= $s[3] ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ============ DEAL MARQUEE (full width) ============ -->
  <div class="nx-in relative mt-20 space-y-6" style="animation-delay:.8s">

    <!-- Prominent Big Header Section -->
    <div class="max-w-4xl mx-auto px-4 text-center">
      <!-- Live Pill Badge -->
      <div
        class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-emerald-50 border-2 border-emerald-300 text-emerald-900 shadow-sm mb-3.5">
        <span class="relative flex h-3 w-3">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
        </span>
        <span class="text-xs sm:text-sm font-black font-satoshi tracking-wider uppercase">OPEN NOW &middot; LIVE
          ALLOCATION</span>
      </div>

      <!-- Big Headline -->
      <h3 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-950 font-satoshi tracking-tight leading-tight">
        Every Company Below Has Passed Audit &amp; Legal Verification
      </h3>

      <!-- Descriptive Subtext with Global Trust Stamps -->
      <p class="mt-2.5 text-sm sm:text-base md:text-lg font-bold text-slate-800 font-satoshi max-w-2xl mx-auto">
        Zero fake promises. Every single business has real government licenses, verified bank accounts, and checked
        financial records before you put in single rupee or dollar.
      </p>

      <!-- Jurisdiction Pill Badges -->
      <div
        class="mt-4 flex flex-wrap items-center justify-center gap-2 sm:gap-3 text-xs sm:text-sm font-extrabold text-slate-900">
        <span
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white border-2 border-slate-300 shadow-2xs">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="3">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span class="font-bold">United States Registered</span>
        </span>
        <span
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white border-2 border-slate-300 shadow-2xs">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="3">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span class="font-bold">Singapore Registered</span>
        </span>
        <span
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white border-2 border-slate-300 shadow-2xs">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="3">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span class="font-bold">United Kingdom Registered</span>
        </span>
        <span
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white border-2 border-slate-300 shadow-2xs">
          <svg class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="3">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <span class="font-bold">India Govt MCA &amp; Tax Cleared</span>
        </span>
      </div>
    </div>

    <?php foreach ($nxRows as $ri => $row): ?>
      <div class="nx-row nx-mask overflow-hidden">
        <div class="nx-track <?= $ri ? 'rev' : '' ?>">
          <?php for ($copy = 0; $copy < 2; $copy++):
            foreach ($row as $d): ?>
              <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
                class="group w-[310px] shrink-0 p-4 sm:p-5 rounded-2xl bg-white border-2 border-slate-300 shadow-md shadow-slate-900/5 hover:border-blue-600 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300"
                <?= $copy ? 'aria-hidden="true" tabindex="-1"' : '' ?>>
                <div class="flex items-center gap-3">
                  <div
                    class="w-11 h-11 rounded-xl bg-gradient-to-tr <?= $d[8] ?> text-white font-black text-sm flex items-center justify-center font-satoshi shrink-0 shadow-sm">
                    <?= $d[0] ?>
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <h4 class="text-base font-extrabold text-slate-950 font-satoshi truncate"><?= $d[1] ?></h4>
                      <span
                        class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 text-[10px] font-black border border-blue-300 shrink-0 uppercase"><?= $d[2] ?></span>
                    </div>
                    <p class="text-xs font-semibold text-slate-700 truncate mt-0.5"><?= $d[3] ?></p>
                  </div>
                </div>
                <div class="mt-4 flex items-end justify-between pt-3 border-t border-slate-200">
                  <div>
                    <div class="text-lg font-black text-slate-950 font-satoshi"><?= $d[4] ?></div>
                    <div class="text-xs font-bold text-emerald-700"><?= $d[5] ?></div>
                  </div>
                  <div class="text-right">
                    <div class="text-[11px] font-bold text-slate-500 uppercase">Min ticket</div>
                    <div class="text-base font-black text-blue-700 font-satoshi"><?= $d[7] ?></div>
                  </div>
                </div>
                <div class="mt-3.5">
                  <div class="flex justify-between text-xs font-bold text-slate-800 mb-1.5">
                    <span class="font-extrabold text-slate-900"><?= $d[6] ?>% filled</span>
                    <span
                      class="text-blue-700 font-extrabold group-hover:text-blue-900 group-hover:translate-x-0.5 transition flex items-center gap-1">View
                      deal &rarr;</span>
                  </div>
                  <div class="h-2 rounded-full bg-slate-200 overflow-hidden">
                    <div class="nx-fillbar h-full rounded-full bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500"
                      style="width:<?= $d[6] ?>%"></div>
                  </div>
                </div>
              </a>
            <?php endforeach; endfor; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
    // Count-up stats when they scroll into view
    (function () {
      var els = document.querySelectorAll('#investor-hero [data-count]');
      var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      function run(el) {
        var end = +el.dataset.count;
        if (reduce || end === 0) { el.textContent = end; return; }
        var t0 = performance.now();
        (function step(t) {
          var p = Math.min(1, (t - t0) / 1600);
          el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3)));
          if (p < 1) requestAnimationFrame(step);
        })(t0);
      }
      if (!('IntersectionObserver' in window)) { els.forEach(run); return; }
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { if (e.isIntersecting) { run(e.target); io.unobserve(e.target); } });
      }, { threshold: 0.6 });
      els.forEach(function (el) { io.observe(el); });
    })();
  </script>
</section>