<?php
/**
 * ============================================================================
 * includes/home/hero-suspense.php
 * ----------------------------------------------------------------------------
 * HERO SECTION (GSAP text animation, trust-first)
 *
 * - Aurora gradient + dot-grid background
 * - GSAP: headline words rise in, key phrases get highlighted, trust words
 *   build letter by letter, cards and buttons stagger in
 * - Trust bar with icons (no fixed numbers or promises)
 * - Infinite auto-scrolling marquee: country name + city names
 *   (same list as the Location menu)
 * - No floating pop-up chips
 * - Falls back to a static page if GSAP fails or reduced motion is on
 * ============================================================================
 */
$hubs = [
  ['India', ['Bengaluru', 'Mumbai', 'Delhi NCR', 'GIFT City', 'Hyderabad', 'Chennai', 'Pune', 'Ahmedabad']],
  ['United Arab Emirates', ['Dubai (DIFC)', 'Abu Dhabi (ADGM)', 'Dubai Internet City', 'Sharjah (SRTIP)']],
];
$all_names = [];
foreach ($hubs as $h) {
  $all_names[] = $h[0] . ': ' . implode(', ', $h[1]);
}

$trust = [
  ['Verified', 'Startup Profiles', 'text-slate-900', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
  ['Secure', 'Bank Escrow Payments', 'text-slate-900', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
  ['Clear', 'Digital NDAs & Agreements', 'text-slate-900', 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
  ['Open', 'Transparent Reporting', 'text-emerald-600', 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
];
?>

<style>
  #nx-hero .nxh-bg-image {
    position: absolute;
    inset: 0;
    background-image: url('<?= function_exists('url') ? url('assets/images/hero_venture.jpg') : 'assets/images/hero_venture.jpg' ?>');
    background-position: center 30%;
    background-size: cover;
    background-repeat: no-repeat;
    opacity: 0.090;
    mix-blend-mode: multiply;
    filter: grayscale(100%) contrast(120%);
    -webkit-mask-image: radial-gradient(ellipse 90% 80% at 50% 35%, #000 40%, transparent 100%);
    mask-image: radial-gradient(ellipse 90% 80% at 50% 35%, #000 40%, transparent 100%);
    pointer-events: none;
  }

  #nx-hero .nxh-grid {
    background-image: radial-gradient(#CBD5E1 1px, transparent 1px);
    background-size: 26px 26px;
    -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 100%);
    mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 30%, transparent 100%);
  }

  #nx-hero .nxh-aurora {
    position: absolute;
    inset: -20% -10% auto -10%;
    height: 80%;
    background:
      radial-gradient(40% 50% at 20% 30%, rgba(16, 185, 129, .22), transparent 70%),
      radial-gradient(40% 50% at 80% 20%, rgba(14, 165, 233, .22), transparent 70%),
      radial-gradient(35% 45% at 50% 60%, rgba(99, 102, 241, .14), transparent 70%);
    animation: nxh-aurora 16s ease-in-out infinite alternate;
    pointer-events: none;
  }

  @keyframes nxh-aurora {
    from {
      transform: translateX(-3%) scale(1);
    }

    to {
      transform: translateX(3%) scale(1.08);
    }
  }

  #nx-hero .nxh-pulse {
    animation: nxh-ping 2s ease-out infinite;
  }

  @keyframes nxh-ping {
    0% {
      box-shadow: 0 0 0 0 rgba(16, 185, 129, .5);
    }

    80%,
    100% {
      box-shadow: 0 0 0 9px rgba(16, 185, 129, 0);
    }
  }

  /* GSAP word / letter masks (padding keeps descenders from being clipped) */
  #nx-hero .nxh-w {
    display: inline-block;
    overflow: hidden;
    vertical-align: top;
    padding: .08em .02em .14em;
    margin-bottom: -.14em;
  }

  #nx-hero .nxh-wi {
    display: inline-block;
    will-change: transform;
  }

  #nx-hero .nxh-gradtext {
    background: linear-gradient(90deg, #059669, #0EA5E9, #6366F1, #059669);
    background-size: 250% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    color: transparent;
    animation: nxh-shine 7s linear infinite;
  }

  @keyframes nxh-shine {
    to {
      background-position: 250% center;
    }
  }

  /* Highlight that GSAP draws behind key phrases */
  #nx-hero .nxh-hl {
    background: linear-gradient(#A7F3D0, #A7F3D0) no-repeat 0 92% / var(--hl, 100%) 36%;
    padding: 0 .15em;
    margin: 0 -.15em;
  }

  #nx-hero .nxh-tt {
    display: inline-block;
    overflow: hidden;
    padding-bottom: .12em;
    margin-bottom: -.12em;
  }

  #nx-hero .nxh-ch {
    display: inline-block;
    will-change: transform;
  }

  /* Primary button shimmer */
  #nx-hero .nxh-shimmer {
    position: relative;
    overflow: hidden;
  }

  #nx-hero .nxh-shimmer::after {
    content: "";
    position: absolute;
    top: 0;
    left: -60%;
    width: 40%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .28), transparent);
    transform: skewX(-20deg);
    animation: nxh-sweep 3.5s ease-in-out infinite;
  }

  @keyframes nxh-sweep {

    0%,
    55% {
      left: -60%;
    }

    100% {
      left: 140%;
    }
  }

  #nx-hero .nxh-stat {
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  }

  #nx-hero .nxh-stat:hover {
    transform: translateY(-5px);
    border-color: #A7F3D0;
    box-shadow: 0 18px 34px -18px rgba(5, 150, 105, .35);
  }

  /* ---------- Infinite marquee: country + city names ---------- */
  #nx-hero .nxh-marquee {
    overflow: hidden;
    padding: 1.25rem 0;
    -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent);
    mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent);
  }

  #nx-hero .nxh-track {
    display: flex;
    width: max-content;
    animation: nxh-scroll 85s linear infinite;
  }

  #nx-hero .nxh-marquee:hover .nxh-track {
    animation-play-state: paused;
  }

  #nx-hero .nxh-group {
    display: flex;
    align-items: center;
    flex-shrink: 0;
  }

  #nx-hero .nxh-country {
    flex-shrink: 0;
    white-space: nowrap;
    margin: 0 1rem;
    padding: .55rem 1.1rem;
    border-radius: 9999px;
    background: #0F172A;
    color: #fff;
    font-size: .8rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
  }

  #nx-hero .nxh-city {
    line-height: 1.3;
    padding: 0 2rem;
    white-space: nowrap;
    font-weight: 800;
    letter-spacing: -.02em;
    color: transparent;
    -webkit-text-stroke: 1.5px #CBD5E1;
    transition: color .3s ease, -webkit-text-stroke-color .3s ease;
  }

  #nx-hero .nxh-city:hover {
    color: #0F172A;
    -webkit-text-stroke-color: #0F172A;
  }

  @media (min-width: 640px) {
    #nx-hero .nxh-city {
      padding: 0 3rem;
    }
  }

  @keyframes nxh-scroll {
    to {
      transform: translateX(-50%);
    }
  }

  @media (prefers-reduced-motion: reduce) {

    #nx-hero *,
    #nx-hero *::before,
    #nx-hero *::after {
      animation: none !important;
      transition: none !important;
    }

    #nx-hero .nxh-gradtext {
      color: #059669;
      -webkit-text-fill-color: #059669;
    }
  }
</style>

<section id="nx-hero"
  class="relative w-full overflow-hidden bg-gradient-to-b from-[#F1F5F9] via-[#F8FAFC] to-white text-[#0F172A] pt-28 pb-14 sm:pt-36 sm:pb-16 md:pt-40 md:pb-20 border-b border-slate-200/80">

  <!-- Subtle Trust Ambient Background Image Overlay -->
  <div class="nxh-bg-image" aria-hidden="true"></div>
  <div class="nxh-aurora" aria-hidden="true"></div>
  <div class="nxh-grid absolute inset-0" aria-hidden="true"></div>

  <div class="relative max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Top Badge -->
    <div class="flex justify-center mb-5 sm:mb-6">
      <div data-nxh="badge"
        class="inline-flex items-center gap-2 px-3.5 sm:px-5 py-1.5 sm:py-2 rounded-full bg-white/95 backdrop-blur border border-slate-200/90 shadow-sm max-w-full">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 nxh-pulse shrink-0"></span>
        <span class="text-xs sm:text-sm font-semibold text-slate-700 tracking-wide truncate">A Trusted Startup Investment Marketplace</span>
      </div>
    </div>

    <!-- Headline + description -->
    <div class="text-center max-w-4xl mx-auto mb-7 sm:mb-8">
      <h1 data-nxh="title"
        class="text-2xl sm:text-5xl md:text-6xl lg:text-[72px] font-extrabold tracking-[-0.035em] leading-[1.18] sm:leading-[1.08] text-slate-900 font-sans">
        A trusted place to <span data-grad>fund &amp; back</span> startups.
      </h1>
      <p data-nxh="para"
        class="mt-4 sm:mt-6 text-sm sm:text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed font-normal">
        Raise capital from <strong class="nxh-hl text-slate-900 font-semibold">verified investors</strong>, or co-invest
        in
        vetted, high-growth companies with <strong class="nxh-hl text-slate-900 font-semibold">an amount that suits
          you</strong>. Every step is protected by clear legal agreements and bank escrow.
      </p>
    </div>

    <!-- Dual Action Buttons -->
    <div
      class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 mb-12 sm:mb-16 w-full max-w-lg sm:max-w-none mx-auto">

      <button data-nxh="btn" type="button" onclick="openSignUpChoiceModal()"
        class="nxh-shimmer group w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 hover:from-slate-800 hover:to-slate-700 text-white font-bold text-sm sm:text-base transition-all duration-200 shadow-lg shadow-slate-900/25 hover:shadow-xl hover:-translate-y-0.5 flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
        <span>Find Startups to Back</span>
        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400 transition-transform group-hover:translate-x-1 shrink-0" fill="none"
          viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
      </button>

      <a data-nxh="btn"
        href="<?= function_exists('url') ? url('auth/register.php?role=founder') : 'auth/register.php?role=founder' ?>"
        class="group w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 rounded-xl bg-white hover:bg-slate-50 text-slate-900 font-bold text-sm sm:text-base border border-slate-300 hover:border-emerald-300 transition-all duration-200 shadow-sm hover:shadow-lg hover:-translate-y-0.5 flex items-center justify-center gap-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">
        <span>Raise Capital for Your Startup</span>
        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-500 transition-transform group-hover:translate-x-1 shrink-0" fill="none"
          viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
      </a>

    </div>

    <!-- Trust Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 max-w-4xl mx-auto mb-10 sm:mb-14 text-center">
      <?php foreach ($trust as $t): ?>
        <div data-nxh="card"
          class="nxh-stat rounded-xl sm:rounded-2xl bg-white/90 backdrop-blur border border-slate-200/90 py-3.5 sm:py-6 px-2.5 sm:px-4 shadow-sm flex flex-col items-center justify-center">
          <span
            class="mb-2 sm:mb-3 w-8 h-8 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
              aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="<?= $t[3] ?>" />
            </svg>
          </span>
          <span data-nxh="word"
            class="text-lg sm:text-2xl lg:text-3xl font-extrabold <?= $t[2] ?> font-sans tracking-tight block"><?= htmlspecialchars($t[0], ENT_QUOTES) ?></span>
          <span
            class="text-[11px] sm:text-sm text-slate-600 font-medium mt-0.5 sm:mt-1.5 block leading-tight sm:leading-snug"><?= htmlspecialchars($t[1], ENT_QUOTES) ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Startup Hubs label -->
    <div data-nxh="label" class="text-center px-4">
      <span class="text-xs sm:text-sm font-mono font-semibold uppercase tracking-wider text-slate-600 block">
        BUILT FOR FOUNDERS &amp; INVESTORS ACROSS LEADING STARTUP HUBS
      </span>
    </div>

  </div>

  <!-- Infinite auto-scrolling marquee: country name + cities -->
  <div data-nxh="marquee" class="relative mt-6 nxh-marquee"
    aria-label="Startup hubs. <?= htmlspecialchars(implode('. ', $all_names), ENT_QUOTES) ?>">
    <div class="nxh-track">
      <?php for ($g = 0; $g < 2; $g++): ?>
        <div class="nxh-group" <?= $g ? 'aria-hidden="true"' : '' ?>>
          <?php foreach ($hubs as $h): ?>
            <span class="nxh-country"><?= htmlspecialchars($h[0], ENT_QUOTES) ?></span>
            <?php foreach ($h[1] as $city): ?>
              <span
                class="nxh-city text-4xl sm:text-6xl lg:text-7xl select-none"><?= htmlspecialchars($city, ENT_QUOTES) ?></span>
              <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-emerald-400 shrink-0"></span>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>

</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
  (function () {
    var root = document.getElementById('nx-hero');
    if (!root || !window.gsap) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    /* Split text nodes into masked words (gradient words keep the gradient) */
    function splitWords(el) {
      var words = [];
      Array.prototype.slice.call(el.childNodes).forEach(function (node) {
        var grad = node.nodeType === 1 && node.hasAttribute('data-grad');
        var frag = document.createDocumentFragment();
        node.textContent.split(/(\s+)/).forEach(function (part) {
          if (!part) return;
          if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
          var w = document.createElement('span'); w.className = 'nxh-w';
          var i = document.createElement('span'); i.className = 'nxh-wi' + (grad ? ' nxh-gradtext' : '');
          i.textContent = part; w.appendChild(i); frag.appendChild(w); words.push(i);
        });
        node.parentNode.replaceChild(frag, node);
      });
      return words;
    }

    /* Split a word into masked letters */
    function splitChars(el) {
      var txt = el.textContent, chars = [];
      el.textContent = '';
      el.classList.add('nxh-tt');
      txt.split('').forEach(function (c) {
        var s = document.createElement('span'); s.className = 'nxh-ch'; s.textContent = c;
        el.appendChild(s); chars.push(s);
      });
      el.setAttribute('aria-label', txt);
      return chars;
    }

    var q = function (n) { return root.querySelectorAll('[data-nxh="' + n + '"]'); };
    var title = root.querySelector('[data-nxh="title"]');
    var words = splitWords(title);
    var hls = root.querySelectorAll('.nxh-hl');
    var chars = [];
    q('word').forEach(function (w) { chars = chars.concat(splitChars(w)); });

    gsap.set(hls, { '--hl': '0%' });

    /* Buttons and cards have CSS transitions that fight GSAP and can leave them invisible.
       Turn transitions off while animating, then restore everything. */
    var fight = Array.prototype.slice.call(q('btn')).concat(Array.prototype.slice.call(q('card')));
    fight.forEach(function (el) { el.style.transition = 'none'; });
    function restore() {
      fight.forEach(function (el) {
        gsap.set(el, { clearProps: 'transform,opacity,visibility' });
        el.style.transition = '';
      });
    }
    setTimeout(restore, 9000); /* safety: buttons are always visible after 9s */

    var tl = gsap.timeline({ defaults: { ease: 'power3.out' }, delay: .15 });
    tl.from(q('badge'), { y: 16, opacity: 0, duration: .6 })
      .from(words, { yPercent: 115, rotate: 4, opacity: 0, duration: .9, stagger: .08 }, '-=.3')
      .from(q('para'), { y: 18, opacity: 0, duration: .7 }, '-=.45')
      .to(hls, { '--hl': '100%', duration: .8, stagger: .3, ease: 'power2.inOut' }, '-=.3')
      .from(q('btn'), { y: 22, opacity: 0, duration: .6, stagger: .1 }, '-=.9')
      .from(q('card'), { y: 30, opacity: 0, duration: .6, stagger: .1 }, '-=.4')
      .from(chars, { yPercent: 110, opacity: 0, duration: .55, stagger: .03 }, '-=.6')
      .from(q('label'), { opacity: 0, y: 10, duration: .6 }, '-=.2')
      .from(q('marquee'), { opacity: 0, y: 24, duration: .8 }, '-=.4')
      .add(restore);
  })();
</script>