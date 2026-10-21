<?php
/**
 * ============================================================================
 * includes/investor_home/investment-steps.php
 * Diligence Pipeline v3 — scroll-driven.
 * LEFT : vertical timeline, 4 stages with their checks (tick off as you scroll)
 * RIGHT: sticky "scoreboard" — funnel 1,000 -> 18, score ring, seal hash, CTA
 * NOTE: funnel counts / scores are sample numbers; replace with real data.
 * ============================================================================
 */
$nxsStages = [
  [
    'name' => 'Legal and KYC',
    'title' => 'We confirm the company and its founders are real',
    'desc' => 'Records checked at Delaware, Singapore ACRA, UK Companies House and Indian MCA.',
    'score' => 98.4,
    'hash' => '9b8f4c21e670d8a1',
    'items' => [
      ['Company filings', 'Active status, no director disqualifications', 'PASSED'],
      ['Founder identity', 'Passport, DigiLocker and AML/PEP screening', 'VERIFIED'],
      ['Litigation search', 'Clean in US, UK and Indian courts', 'CLEAN'],
      ['IP ownership', '100% of IP assigned to the company', 'ASSIGNED'],
    ],
  ],
  [
    'name' => 'Forensic P&L',
    'title' => 'We match the revenue they claim to the money in the bank',
    'desc' => 'Bank statements, tax filings and payment-gateway data are compared line by line for 24 months.',
    'score' => 99.1,
    'hash' => '8a7d3f11c992e4b0',
    'items' => [
      ['Bank inflows', '24 months reconciled by an independent auditor', '100% MATCH'],
      ['GST filings', 'GSTR-1 and GSTR-3B match reported revenue', 'VERIFIED'],
      ['Stripe / Razorpay', 'GMV and net revenue retention checked at source', 'AUTHENTIC'],
      ['Burn and runway', 'Burn under ₹18 Lakhs a month, 18+ months of runway', 'HEALTHY'],
    ],
  ],
  [
    'name' => 'Clean SAFE terms',
    'title' => 'We only list deals with fair, standard terms',
    'desc' => 'Standard post-money YC SAFEs or SEBI CCPS contracts. No toxic clauses.',
    'score' => 97.8,
    'hash' => '3c5e9b72a441f8d9',
    'items' => [
      ['YC post-money SAFE', 'Standard cap and discount, no toxic debt covenants', 'STANDARD'],
      ['Cap table', 'Fully diluted, with a 10% unallocated ESOP pool', 'AUDITED'],
      ['Liquidation terms', 'No senior overhang, no predatory vetoes', 'COMPLIANT'],
      ['Lead investor', 'A Tier-1 fund writes the lead cheque, pari-passu', 'CO-INVEST'],
    ],
  ],
  [
    'name' => 'Tax and governance',
    'title' => 'We keep protecting you after you invest',
    'desc' => 'Tax exemption papers, quarterly reports and a watermarked data room come with every deal.',
    'score' => 99.5,
    'hash' => '7f2a1b94d883c5e1',
    'items' => [
      ['Section 56(2)(viib)', 'DPIIT-recognised entity papers pre-filed', 'EXEMPT'],
      ['Nominee trust SPV', 'Pooled cheques keep the cap table clean', 'SPV ACTIVE'],
      ['Quarterly MIS', 'P&L lands in your dashboard automatically', 'AUTOMATED'],
      ['Data room', 'Every download carries your watermark and NDA', 'SECURED'],
    ],
  ],
];
$nxsFunnel = [['Applied', 1000], ['Legal and KYC', 412], ['Forensic P&L', 96], ['Clean SAFE terms', 41], ['Tax and governance', 18]];
$nxsBarW = [100, 78, 56, 38, 24];
?>
<style>
  @keyframes nxsPop {
    0% {
      transform: scale(.4);
      opacity: 0
    }

    70% {
      transform: scale(1.18)
    }

    100% {
      transform: scale(1);
      opacity: 1
    }
  }

  @keyframes nxsSpin {
    to {
      transform: rotate(360deg)
    }
  }

  .nxs-pop {
    animation: nxsPop .4s cubic-bezier(.2, .9, .3, 1.3) both;
  }

  .nxs-spin {
    animation: nxsSpin .8s linear infinite;
  }

  .nxs-stage {
    opacity: .4;
    transition: opacity .5s;
  }

  .nxs-stage.is-active,
  .nxs-stage.is-past {
    opacity: 1;
  }

  .nxs-card {
    transition: border-color .4s, box-shadow .4s, transform .4s;
  }

  .nxs-stage.is-active .nxs-card {
    border-color: #2563eb;
    box-shadow: 0 24px 50px -24px rgba(37, 99, 235, .45);
    transform: translateX(4px);
  }

  .nxs-node {
    transition: background .4s, color .4s, border-color .4s, transform .4s;
  }

  .nxs-stage.is-active .nxs-node {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    transform: scale(1.12);
    box-shadow: 0 0 0 6px rgba(37, 99, 235, .16);
  }

  .nxs-stage.is-past .nxs-node {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
  }

  .nxs-row .nxs-ico {
    transition: all .3s;
  }

  .nxs-row.scan .nxs-ico {
    background: #dbeafe;
    color: #2563eb;
    border-color: #93c5fd;
  }

  .nxs-row.done .nxs-ico {
    background: #d1fae5;
    color: #059669;
    border-color: #6ee7b7;
  }

  .nxs-row .nxs-chip {
    opacity: 0;
    transform: scale(.8);
    transition: all .3s;
  }

  .nxs-row.done .nxs-chip {
    opacity: 1;
    transform: none;
  }

  .nxs-bar {
    transition: background .5s, border-color .5s, color .5s, transform .4s;
  }

  .nxs-bar.cur {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    transform: scale(1.03);
    box-shadow: 0 10px 24px -10px rgba(37, 99, 235, .7);
  }

  .nxs-bar.pass {
    background: rgba(16, 185, 129, .15);
    border-color: rgba(16, 185, 129, .5);
    color: #6ee7b7;
  }

  .nxs-reveal {
    opacity: 0;
    transform: translateY(22px);
    transition: opacity .8s ease, transform .8s cubic-bezier(.2, .8, .2, 1);
  }

  .nxs-reveal.in {
    opacity: 1;
    transform: none;
  }

  @media (prefers-reduced-motion: reduce) {

    .nxs-pop,
    .nxs-spin {
      animation: none
    }

    .nxs-reveal {
      opacity: 1;
      transform: none;
      transition: none
    }
  }
</style>

<section class="py-20 md:py-28 bg-[#F8FAFC] border-t border-slate-200/80 relative" id="investor-diligence">
  <div
    class="pointer-events-none absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:28px_28px] opacity-50">
  </div>

  <div class="relative max-w-[1240px] mx-auto px-6 lg:px-10">

    <!-- Header -->
    <div class="nxs-reveal max-w-3xl mb-14 space-y-4">
      <div
        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-100/80 border border-blue-300 text-blue-950 text-xs font-bold font-satoshi">
        <svg class="w-4 h-4 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>
        How we audit
      </div>
      <h2
        class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-950 tracking-[-0.04em] leading-[1.08] font-satoshi">
        1,000 startups apply. Four audits later, 18 are left.
      </h2>
      <p class="text-base sm:text-lg text-slate-700 font-semibold leading-relaxed font-satoshi max-w-2xl">
        Scroll to follow one company through each stage. The board on the right shows how many make it.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-start">

      <!-- ============ LEFT: TIMELINE ============ -->
      <div class="lg:col-span-7 order-2 lg:order-1">
        <div class="relative pl-14" id="nxsTimeline">
          <div class="absolute left-[18px] top-6 bottom-6 w-1 rounded-full bg-slate-200"></div>
          <div id="nxsFill"
            class="absolute left-[18px] top-6 w-1 rounded-full bg-gradient-to-b from-blue-600 to-emerald-500"
            style="height:0;transition:height .9s cubic-bezier(.2,.8,.2,1)"></div>

          <div class="space-y-10">
            <?php foreach ($nxsStages as $i => $s): ?>
              <article class="nxs-stage relative" data-i="<?= $i ?>" data-score="<?= $s['score'] ?>"
                data-hash="<?= $s['hash'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
                <div
                  class="nxs-node absolute -left-14 top-6 w-10 h-10 rounded-full bg-white border-2 border-slate-300 text-slate-600 font-mono font-black text-sm flex items-center justify-center z-10">
                  <span class="nxs-num"><?= $i + 1 ?></span>
                </div>

                <div class="nxs-card bg-white border-2 border-slate-200 rounded-3xl p-6 sm:p-7 shadow-sm">
                  <div class="text-xs font-bold text-blue-700 font-satoshi">Stage <?= $i + 1 ?> of 4 &middot;
                    <?= htmlspecialchars($s['name']) ?></div>
                  <h3
                    class="mt-1.5 text-xl sm:text-2xl font-black text-slate-950 tracking-tight leading-snug font-satoshi">
                    <?= htmlspecialchars($s['title']) ?></h3>
                  <p class="mt-2 text-sm text-slate-600 font-semibold leading-relaxed font-satoshi">
                    <?= htmlspecialchars($s['desc']) ?></p>

                  <ul class="mt-5 space-y-2.5">
                    <?php foreach ($s['items'] as $it): ?>
                      <li
                        class="nxs-row flex items-center justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center gap-3 min-w-0">
                          <span
                            class="nxs-ico w-7 h-7 rounded-lg bg-white text-slate-300 border border-slate-200 flex items-center justify-center shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                          </span>
                          <div class="min-w-0">
                            <div class="text-[13px] font-black text-slate-900 font-satoshi"><?= htmlspecialchars($it[0]) ?>
                            </div>
                            <div class="text-[11.5px] text-slate-500 font-semibold font-satoshi">
                              <?= htmlspecialchars($it[1]) ?></div>
                          </div>
                        </div>
                        <span
                          class="nxs-chip px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-mono font-black shrink-0 whitespace-nowrap"><?= htmlspecialchars($it[2]) ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ============ RIGHT: STICKY SCOREBOARD ============ -->
      <aside class="lg:col-span-5 order-1 lg:order-2 lg:sticky lg:top-24 nxs-reveal" style="transition-delay:.15s">
        <div class="relative">
          <div
            class="pointer-events-none absolute -inset-2 bg-gradient-to-br from-blue-500/25 via-indigo-500/20 to-cyan-500/20 rounded-[34px] blur-xl">
          </div>
          <div
            class="relative rounded-[28px] bg-gradient-to-br from-[#060B14] via-[#0B1325] to-[#0F1B35] border border-slate-800 p-6 sm:p-7 text-white shadow-2xl">
            <div
              class="absolute top-0 left-6 right-6 h-1 rounded-b bg-gradient-to-r from-blue-500 via-indigo-400 to-cyan-400">
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 text-[11px] font-mono font-bold text-slate-300">
                <span class="relative flex h-2 w-2"><span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span
                    class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span></span>
                Pipeline scoreboard
              </div>
              <span id="nxsStageName" class="text-[11px] font-mono font-bold text-blue-300">Applied</span>
            </div>

            <div class="mt-5 flex items-end gap-3">
              <div id="nxsBig" class="text-6xl font-black font-satoshi tracking-tight leading-none">1,000</div>
              <div class="pb-1.5 text-xs font-semibold text-slate-400 font-satoshi leading-tight">startups still<br>in
                the running</div>
            </div>

            <!-- Funnel bars -->
            <div class="mt-6 space-y-2" id="nxsBars">
              <?php foreach ($nxsFunnel as $k => $f): ?>
                <div class="flex justify-center">
                  <button type="button" data-k="<?= $k ?>" style="width:<?= $nxsBarW[$k] ?>%"
                    class="nxs-bar min-w-[130px] flex items-center justify-between gap-2 px-3 py-2 rounded-lg bg-slate-900 border border-slate-700 text-slate-400 text-[11px] font-mono font-bold cursor-pointer">
                    <span class="truncate"><?= htmlspecialchars($f[0]) ?></span>
                    <span><?= number_format($f[1]) ?></span>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Score + seal -->
            <div class="mt-6 pt-5 border-t border-slate-800 flex items-center gap-4">
              <div class="relative w-[64px] h-[64px] shrink-0">
                <svg viewBox="0 0 72 72" class="w-full h-full -rotate-90">
                  <circle cx="36" cy="36" r="30" fill="none" stroke="#1e293b" stroke-width="6" />
                  <circle id="nxsRing" cx="36" cy="36" r="30" fill="none" stroke="url(#nxsRingG)" stroke-width="6"
                    stroke-linecap="round" stroke-dasharray="188.5" stroke-dashoffset="188.5"
                    style="transition:stroke-dashoffset 1.2s cubic-bezier(.2,.8,.2,1)" />
                  <defs>
                    <linearGradient id="nxsRingG" x1="0" y1="0" x2="1" y2="1">
                      <stop offset="0" stop-color="#60a5fa" />
                      <stop offset="1" stop-color="#34d399" />
                    </linearGradient>
                  </defs>
                </svg>
                <div id="nxsScore"
                  class="absolute inset-0 flex items-center justify-center text-[13px] font-black font-mono">&ndash;
                </div>
              </div>
              <div class="min-w-0">
                <div class="text-[11px] font-mono font-bold text-slate-400">Stage score</div>
                <div class="text-[11px] font-mono text-slate-300 truncate mt-1">Seal: <span id="nxsHash"
                    class="text-blue-400 font-bold">waiting…</span></div>
              </div>
            </div>

            <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
              class="mt-6 w-full py-3.5 rounded-xl text-sm font-black text-white bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 font-satoshi transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-600/40 hover:-translate-y-0.5">
              <span>Open the data room</span><span class="text-base">&rarr;</span>
            </a>
          </div>
        </div>
      </aside>

    </div>
  </div>

  <script>
    (function () {
      const root = document.getElementById('investor-diligence');
      const $ = (id) => document.getElementById(id);
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const stages = [...root.querySelectorAll('.nxs-stage')];
      const bars = [...root.querySelectorAll('.nxs-bar')];
      const COUNTS = [1000, 412, 96, 41, 18];
      const CHECK = '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2"><polyline points="20 6 9 17 4 12"/></svg>';
      const SPIN = '<svg class="w-3.5 h-3.5 nxs-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9"/></svg>';
      const DOT = '<span class="w-1.5 h-1.5 rounded-full bg-current"></span>';

      let active = -2, timers = [], bigVal = 1000, ticking = -1;
      const later = (fn, ms) => timers.push(setTimeout(fn, ms));

      function tween(el, from, to, ms, fmt) {
        if (reduce) { el.textContent = fmt(to); return; }
        const t0 = performance.now();
        (function s(t) { const p = Math.min(1, (t - t0) / ms), e = 1 - Math.pow(1 - p, 3); el.textContent = fmt(Math.round(from + (to - from) * e)); if (p < 1) requestAnimationFrame(s); })(t0);
      }
      function scramble(el, final, ms) {
        if (reduce) { el.textContent = final + '…'; return; }
        const hex = '0123456789abcdef', t0 = performance.now();
        (function s(t) { const p = Math.min(1, (t - t0) / ms), k = Math.floor(final.length * p); let o = final.slice(0, k); for (let i = k; i < final.length; i++) o += hex[Math.floor(Math.random() * 16)]; el.textContent = o + '…'; if (p < 1) requestAnimationFrame(s); })(t0);
      }

      function rowState(row, st) {
        const ico = row.querySelector('.nxs-ico');
        row.classList.toggle('scan', st === 'scan'); row.classList.toggle('done', st === 'done');
        ico.innerHTML = st === 'done' ? CHECK : st === 'scan' ? SPIN : DOT;
        if (st === 'done') { ico.classList.remove('nxs-pop'); void ico.offsetWidth; ico.classList.add('nxs-pop'); }
      }
      const rowsOf = (s) => [...s.querySelectorAll('.nxs-row')];

      function placeFill() {
        const tl = $('nxsTimeline'), fill = $('nxsFill');
        if (active < 0) { fill.style.height = '0'; return; }
        const n = stages[active].querySelector('.nxs-node').getBoundingClientRect(), t = tl.getBoundingClientRect();
        fill.style.height = Math.max(0, n.top + n.height / 2 - t.top - 24) + 'px';
      }

      function setActive(i) {
        if (i === active) return;
        const prev = active; active = i;
        timers.forEach(clearTimeout); timers = [];

        stages.forEach((s, j) => {
          s.classList.toggle('is-active', j === i);
          s.classList.toggle('is-past', j < i);
          s.querySelector('.nxs-num').innerHTML = j < i ? CHECK : (j + 1);
          if (j < i) rowsOf(s).forEach(r => rowState(r, 'done'));
          if (j > i) rowsOf(s).forEach(r => rowState(r, 'idle'));
        });

        // tick the active stage's checks one by one
        if (i >= 0) {
          rowsOf(stages[i]).forEach((r, k) => {
            rowState(r, 'idle');
            const step = reduce ? 0 : 480;
            later(() => rowState(r, 'scan'), 150 + k * step);
            later(() => rowState(r, 'done'), 150 + k * step + step * .8);
          });
        }

        // scoreboard
        bars.forEach((b, k) => { b.classList.toggle('cur', k === i + 1); b.classList.toggle('pass', k <= i); });
        const to = COUNTS[i + 1];
        tween($('nxsBig'), bigVal, to, 900, v => v.toLocaleString('en-US')); bigVal = to;
        $('nxsStageName').textContent = i < 0 ? 'Applied' : stages[i].dataset.name;

        if (i >= 0) {
          const sc = +stages[i].dataset.score;
          $('nxsRing').style.strokeDashoffset = 188.5;
          later(() => { $('nxsRing').style.strokeDashoffset = 188.5 * (1 - sc / 100); }, 80);
          tween($('nxsScore'), 0, sc * 10, 1200, v => (v / 10).toFixed(1));
          scramble($('nxsHash'), stages[i].dataset.hash, 1000);
        } else {
          $('nxsRing').style.strokeDashoffset = 188.5; $('nxsScore').textContent = '–'; $('nxsHash').textContent = 'waiting…';
        }
        placeFill();
      }

      // Which stage sits near the middle of the screen?
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((es) => {
          es.forEach(e => { if (e.isIntersecting) setActive(+e.target.dataset.i); });
        }, { rootMargin: '-38% 0px -50% 0px', threshold: 0 });
        stages.forEach(s => io.observe(s));
      } else { setActive(0); }

      // Click a funnel bar -> jump to its stage
      bars.forEach(b => b.addEventListener('click', () => {
        const k = +b.dataset.k;
        if (k === 0) { root.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' }); return; }
        stages[k - 1].scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
      }));

      // Reveal header + board
      const rv = root.querySelectorAll('.nxs-reveal');
      if ('IntersectionObserver' in window) {
        const io2 = new IntersectionObserver((es) => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io2.unobserve(e.target); } }), { threshold: .15 });
        rv.forEach(r => io2.observe(r));
      } else { rv.forEach(r => r.classList.add('in')); }

      // start state
      bars[0].classList.add('cur');
      window.addEventListener('resize', placeFill);
    })();
  </script>
</section>