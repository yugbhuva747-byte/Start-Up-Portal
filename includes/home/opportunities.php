<?php
/**
 * ============================================================================
 * include/home/opportunities.php
 * ----------------------------------------------------------------------------
 * SECTION 03 — INVESTMENT OPPORTUNITIES
 *
 * Heading: OPPORTUNITIES WORTH DISCOVERING.
 * Background: #FFFFFF / #FAFAF8
 * Layout: Horizontal Infinite Auto-Scroll Cards (Marquee / Smooth Carousel)
 * Features:
 * - Unique Dynamic GSAP Clip-Path Viewport Expansion on Scroll Scrub
 * - Staggered polygon clip-path entrance reveal on cards
 * - Geometric architectural clip-path badges and metrics containers
 * - Seamless 360° Infinite Continuous Horizontal Scroll (Infinite Time)
 * - Pause on hover for effortless reading and interaction
 * - Drag/touch scroll support with manual Prev/Next navigation controls
 * - High-end editorial card architecture with metrics, verified tags & dark blue accents
 * - Fully responsive (Desktop, Tablet, Mobile) & respects prefers-reduced-motion
 * ============================================================================
 */

$opportunities = [
  [
    'name' => 'Kinetix Robotics',
    'logo_initial' => 'KR',
    'sector' => 'Industrial Robotics',
    'subsector' => 'Autonomous Logistics · AI Fleet Sync',
    'stage' => 'Series A',
    'location' => 'Bengaluru',
    'image' => 'assets/images/opportunities/kinetix-robotics.jpg',
    'founders' => 'Ex-ISRO · IISc Robotics Lab',
    'backer' => 'Peak XV Surge',
    'desc' => 'Building autonomous fleet coordination systems for high-throughput supply chains and e-commerce distribution centers across South Asia.',
    'target' => '₹8.5 Cr',
    'arr' => '₹6.2 Cr',
    'booked' => '82% Booked',
    'booked_pct' => 82,
    'badge' => 'FEATURED DEAL',
    'link' => '/opportunities/kinetix-robotics'
  ],
  [
    'name' => 'GreenLeaf Health',
    'logo_initial' => 'GL',
    'sector' => 'HealthTech',
    'subsector' => 'Point-of-Care Diagnostics · Pathology',
    'stage' => 'Seed Round',
    'location' => 'Pune',
    'image' => 'assets/images/opportunities/greenleaf-health.jpg',
    'founders' => 'AIIMS Clinicians · IIT-B Bio',
    'backer' => 'Blume Ventures',
    'desc' => 'Affordable preventive diagnostic devices and automated remote pathology access designed for emerging tier-2 & tier-3 urban corridors.',
    'target' => '₹2.5 Cr',
    'arr' => '₹1.4 Cr',
    'booked' => '65% Booked',
    'booked_pct' => 65,
    'badge' => 'DILIGENCE READY',
    'link' => '/opportunities/greenleaf-health'
  ],
  [
    'name' => 'Aerovex Mobility',
    'logo_initial' => 'AM',
    'sector' => 'CleanTech',
    'subsector' => 'Heavy Electric Powertrains · Fleet Tech',
    'stage' => 'Pre-Series A',
    'location' => 'Chennai',
    'image' => 'assets/images/opportunities/aerovex-mobility.jpg',
    'founders' => 'Ex-Tata Motors EV Team',
    'backer' => 'Elev8 Capital',
    'desc' => 'Next-generation high-efficiency electric commercial powertrains built for demanding multi-shift freight delivery and industrial haulage.',
    'target' => '₹4.0 Cr',
    'arr' => '₹3.1 Cr',
    'booked' => '90% Booked',
    'booked_pct' => 90,
    'badge' => 'FINAL ALLOCATION',
    'link' => '/opportunities/aerovex-mobility'
  ],
  [
    'name' => 'NovaGrid Energy',
    'logo_initial' => 'NG',
    'sector' => 'ClimateTech',
    'subsector' => 'Grid-Scale Storage · Smart Metering',
    'stage' => 'Series A',
    'location' => 'Ahmedabad',
    'image' => 'assets/images/opportunities/novagrid-energy.jpg',
    'founders' => 'Ex-Adani Green · IIT-D Power',
    'backer' => 'Institutional Lead',
    'desc' => 'Decentralized battery energy storage systems with algorithmic power arbitrating for commercial microgrids and green data centers.',
    'target' => '₹12.0 Cr',
    'arr' => '₹9.5 Cr',
    'booked' => '74% Booked',
    'booked_pct' => 74,
    'badge' => 'INSTITUTIONAL LEAD',
    'link' => '/opportunities/novagrid-energy'
  ],
  [
    'name' => 'Veloce Photonics',
    'logo_initial' => 'VP',
    'sector' => 'DeepTech',
    'subsector' => 'Silicon Optical Chips · AI Interconnects',
    'stage' => 'Seed Round',
    'location' => 'Hyderabad',
    'image' => 'assets/images/opportunities/veloce-photonics.jpg',
    'founders' => 'Ex-Intel Photonics · PhD Caltech',
    'backer' => 'Accel Atoms',
    'desc' => 'Ultra-low latency silicon optical interconnects cutting GPU compute cluster latency by 80% with proprietary micro-ring resonators.',
    'target' => '₹6.0 Cr',
    'arr' => '₹2.8 Cr',
    'booked' => '88% Booked',
    'booked_pct' => 88,
    'badge' => '8 PATENTS GRANTED',
    'link' => '/opportunities/veloce-photonics'
  ],
  [
    'name' => 'AgroPulse AI',
    'logo_initial' => 'AP',
    'sector' => 'AgriTech',
    'subsector' => 'Satellite Hyperspectral · Yield Forensics',
    'stage' => 'Pre-Series A',
    'location' => 'Chandigarh',
    'image' => 'assets/images/opportunities/agropulse-ai.jpg',
    'founders' => 'ISRO Remote Sensing Fellows',
    'backer' => 'Kalaari Capital',
    'desc' => 'Sub-meter satellite radar data combined with farm IoT telemetry for real-time crop resilience and institutional underwriting.',
    'target' => '₹3.5 Cr',
    'arr' => '₹2.2 Cr',
    'booked' => '70% Booked',
    'booked_pct' => 70,
    'badge' => 'COMMERCIAL PILOT',
    'link' => '/opportunities/agropulse-ai'
  ]
];
?>

<style>
  /* ============================================================
     OPPORTUNITIES INFINITE HORIZONTAL MARQUEE STYLES (PASTEL LUXE)
     ============================================================ */
  .nx-opp-section {
    position: relative;
    z-index: 5;
    background-color: #FAF5FF;
    background-image: 
      radial-gradient(at 0% 0%, #EDE9FE 0px, transparent 50%),
      radial-gradient(at 100% 100%, #FCE7F3 0px, transparent 50%),
      radial-gradient(at 50% 50%, #FFF7ED 0px, transparent 50%);
    overflow: hidden;
  }

  /* Clip-Path Scroll Stage Container: Expands from cinematic capsule to full width */
  #nx-opp-scroll-stage {
    position: relative;
    width: 100%;
    will-change: clip-path, transform, opacity;
    transform-origin: center center;
    border-radius: 36px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1.5px solid rgba(221, 214, 254, 0.6);
    box-shadow: 0 15px 45px rgba(124, 58, 237, 0.06);
  }

  /* Left & Right Soft Ambient Edge Masks for Infinite Feel */
  .nx-opp-mask {
    position: relative;
    width: 100%;
    overflow: hidden;
    mask-image: linear-gradient(to right, transparent 0%, #000 4%, #000 96%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 4%, #000 96%, transparent 100%);
  }

  @media (max-width: 768px) {
    .nx-opp-mask {
      mask-image: linear-gradient(to right, transparent 0%, #000 2%, #000 98%, transparent 100%);
      -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 2%, #000 98%, transparent 100%);
    }
  }

  /* Seamless Infinite Scroll Track */
  .nx-opp-marquee {
    display: flex;
    width: max-content;
    will-change: transform;
    animation: nxMarqueeScroll 42s linear infinite;
  }

  /* Pause seamlessly on hover or touch focus */
  .nx-opp-wrapper:hover .nx-opp-marquee,
  .nx-opp-marquee.is-paused {
    animation-play-state: paused !important;
  }

  @keyframes nxMarqueeScroll {
    0% {
      transform: translate3d(0, 0, 0);
    }
    100% {
      transform: translate3d(-50%, 0, 0);
    }
  }

  /* Unique Clip-Path Elements */
  .nx-opp-clip-badge {
    clip-path: polygon(0 0, 100% 0, calc(100% - 9px) 100%, 0% 100%);
  }

  .nx-opp-clip-pill {
    clip-path: polygon(7px 0, 100% 0, calc(100% - 7px) 100%, 0 100%);
  }

  .nx-opp-clip-box {
    clip-path: polygon(0 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%);
  }

  /* Card Individual Styling with Pastel Glow */
  .nx-opp-card {
    position: relative;
    width: 380px;
    background-color: rgba(255, 255, 255, 0.95);
    border: 1.5px solid rgba(221, 214, 254, 0.7);
    border-radius: 26px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.3s ease,
                box-shadow 0.35s cubic-bezier(0.16, 1, 0.3, 1),
                background-color 0.3s ease;
    cursor: pointer;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.08);
  }

  @media (min-width: 640px) {
    .nx-opp-card {
      width: 420px;
      padding: 24px;
    }
  }

  .nx-opp-img-wrap {
    position: relative;
    width: 100%;
    height: 190px;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 18px;
    background-color: #1E1B4B;
  }

  .nx-opp-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-opp-card:hover .nx-opp-img-wrap img {
    transform: scale(1.06);
  }

  .nx-progress-bar {
    width: 100%;
    height: 6px;
    background-color: #F1F5F9;
    border-radius: 999px;
    overflow: hidden;
  }

  .nx-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #8B5CF6 0%, #EC4899 50%, #F59E0B 100%);
    border-radius: 999px;
    transition: width 0.8s ease;
  }

  /* Top Right Decorative Geometric Notch with Clip-Path */
  .nx-opp-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, transparent 50%, #EC4899 50%);
    opacity: 0.2;
    transition: opacity 0.3s ease, transform 0.3s ease;
    clip-path: polygon(100% 0, 0 100%, 100% 100%);
  }

  .nx-opp-card:hover::before {
    opacity: 0.9;
    transform: scale(1.2);
  }

  .nx-opp-card:hover {
    transform: translateY(-6px);
    background-color: #FFFFFF;
    border-color: #A78BFA;
    box-shadow: 0 20px 45px -10px rgba(124, 58, 237, 0.2),
                0 8px 20px -4px rgba(236, 72, 153, 0.12);
  }

  /* Nav arrow button styling */
  .nx-opp-nav-btn {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    background: #FFFFFF;
    border: 1.5px solid #DDD6FE;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #6D28D9;
    transition: all 0.25s ease;
  }

  .nx-opp-nav-btn:hover {
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    color: #FFFFFF;
    border-color: transparent;
    box-shadow: 0 4px 16px rgba(139, 92, 246, 0.35);
    transform: scale(1.05);
  }

  /* Respect Accessibility Reduced Motion */
  @media (prefers-reduced-motion: reduce) {
    .nx-opp-marquee {
      animation: none !important;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
    }
    .nx-opp-card {
      scroll-snap-align: start;
    }
  }
</style>

<section id="nx-opportunities" class="nx-opp-section py-20 sm:py-28 lg:py-36 border-t border-purple-100" aria-label="Investment Opportunities">
  <div class="max-w-[1440px] mx-auto px-6 lg:px-14">

    <!-- Section Header Row -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12 sm:mb-16 nx-text-reveal">
      <div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-gradient-to-r from-purple-100 via-pink-100 to-amber-100 border border-purple-200 text-[11px] font-bold tracking-wider uppercase text-purple-900 mb-4 shadow-xs">
          <span class="w-1.5 h-1.5 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
          <span>CURATED DEALFLOW &middot; 2026</span>
        </div>
        <h2 class="text-[clamp(34px,4.8vw,68px)] font-extrabold leading-[0.98] tracking-[-0.04em] text-slate-900 mb-4">
          OPPORTUNITIES<br>
          <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 bg-clip-text text-transparent">WORTH DISCOVERING.</span>
        </h2>
        <p class="text-[15.5px] sm:text-[17.5px] text-slate-600 max-w-[560px] leading-relaxed">
          High-conviction venture allocations across robotics, deep tech, clean mobility, and climate infrastructure.
        </p>
      </div>

      <!-- Controls & Header CTA -->
      <div class="flex items-center gap-4 self-start md:self-end">
        <!-- Manual Navigation Buttons -->
        <div class="hidden sm:flex items-center gap-2">
          <button id="nx-opp-prev" class="nx-opp-nav-btn nx-hoverable" aria-label="Scroll left">
            <span class="text-base">&larr;</span>
          </button>
          <button id="nx-opp-next" class="nx-opp-nav-btn nx-hoverable" aria-label="Scroll right">
            <span class="text-base">&rarr;</span>
          </button>
        </div>

        <a href="login.php?role=investor" class="inline-flex items-center gap-2 h-[44px] px-5 rounded-full bg-white border border-purple-200 text-[13.5px] font-bold text-purple-700 hover:bg-purple-50 hover:border-purple-300 transition-all nx-hoverable shadow-xs">
          <span>View all opportunities</span>
          <span aria-hidden="true">&rarr;</span>
        </a>
      </div>
    </div>

  </div>

  <!-- Unique Architectural Scroll-Stage with GSAP Clip-Path Scrub Animation -->
  <div id="nx-opp-scroll-stage" class="w-full py-8 lg:py-12 my-2">
    <!-- Full-Width Horizontal Auto-Scrolling Infinite Arena -->
    <div class="nx-opp-wrapper relative w-full" id="nx-opp-wrapper">
      <div class="nx-opp-mask">
        <div class="nx-opp-marquee py-4 px-4 sm:px-6 gap-6 sm:gap-8" id="nx-opp-marquee">
          
          <?php
          // Loop twice to guarantee a smooth, flawless seamless loop
          for ($repeat = 0; $repeat < 2; $repeat++):
            foreach ($opportunities as $idx => $opp):
          ?>
            <!-- Opportunity Card with Architectural Image & Trust Architecture -->
            <article class="nx-opp-card nx-hoverable group" data-card-id="<?php echo $idx; ?>">
              <div>
                <!-- Top Image Visual Container with Overlay Badges -->
                <div class="nx-opp-img-wrap relative">
                  <img
                    src="<?php echo htmlspecialchars(function_exists('url') ? url($opp['image']) : $opp['image']); ?>"
                    alt="<?php echo htmlspecialchars($opp['name']); ?> — <?php echo htmlspecialchars($opp['sector']); ?>"
                    loading="lazy"
                  />
                  <!-- Gradient Vignette -->
                  <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-black/35 pointer-events-none"></div>

                  <!-- Floating Status & Stage Badges -->
                  <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-2 z-10">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-md text-[10px] font-mono font-bold tracking-wider text-white uppercase border border-white/20">
                      <span class="w-1.5 h-1.5 rounded-full bg-[#60A5FA] animate-pulse"></span>
                      <?php echo htmlspecialchars($opp['stage']); ?> · <?php echo htmlspecialchars($opp['location']); ?>
                    </span>

                    <span class="text-[9.5px] font-mono font-bold uppercase tracking-wider text-white bg-gradient-to-r from-purple-600 to-pink-600 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/25 shadow-xs">
                      <?php echo htmlspecialchars($opp['badge']); ?>
                    </span>
                  </div>

                  <!-- Company Monogram Avatar & Sector Tag -->
                  <div class="absolute bottom-2.5 left-3 z-10 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-white p-0.5 shadow-md flex items-center justify-center border border-purple-100">
                      <span class="w-full h-full rounded-lg bg-gradient-to-tr from-purple-600 to-pink-500 text-white flex items-center justify-center font-mono font-bold text-[10.5px] tracking-wider">
                        <?php echo htmlspecialchars($opp['logo_initial']); ?>
                      </span>
                    </div>
                    <span class="text-[11px] font-mono font-semibold text-white/95 drop-shadow-sm">
                      <?php echo htmlspecialchars($opp['sector']); ?>
                    </span>
                  </div>
                </div>

                <!-- Company Title with Verified Icon -->
                <div class="flex items-center justify-between gap-2 mb-1">
                  <h3 class="text-[22px] sm:text-[23px] font-bold text-slate-900 tracking-[-0.03em] leading-tight group-hover:text-purple-700 transition-colors flex items-center gap-1.5">
                    <span><?php echo htmlspecialchars($opp['name']); ?></span>
                    <svg class="w-4 h-4 text-purple-600 shrink-0" viewBox="0 0 20 20" fill="currentColor" title="Verified Diligence Dossier">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </h3>
                </div>

                <!-- Subsector Tagline -->
                <p class="text-[12px] font-semibold text-slate-500 uppercase tracking-wider mb-3">
                  <?php echo htmlspecialchars($opp['subsector']); ?>
                </p>

                <!-- Trust Meta: Founders & Syndicate Backer -->
                <div class="flex flex-wrap items-center gap-1.5 mb-3.5 text-[10.5px] font-mono">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 font-medium">
                    <span class="text-slate-400">Founders:</span> <?php echo htmlspecialchars($opp['founders']); ?>
                  </span>
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-purple-700 font-bold">
                    <span>⚡ <?php echo htmlspecialchars($opp['backer']); ?></span>
                  </span>
                </div>

                <!-- Description -->
                <p class="text-[13px] leading-relaxed text-slate-600 mb-4 line-clamp-2">
                  <?php echo htmlspecialchars($opp['desc']); ?>
                </p>

                <!-- Financial & Traction Highlights with Angular Cut Box Clip-Path -->
                <div class="nx-opp-clip-box grid grid-cols-3 gap-2 p-3 bg-slate-50/80 border border-purple-100/90 mb-2 rounded-xl">
                  <div>
                    <span class="text-[9.5px] font-mono uppercase tracking-wider text-slate-400 block mb-0.5">Round Target</span>
                    <span class="text-[14.5px] sm:text-[15.5px] font-bold text-slate-900 block nx-counter"><?php echo htmlspecialchars($opp['target']); ?></span>
                  </div>
                  <div>
                    <span class="text-[9.5px] font-mono uppercase tracking-wider text-slate-400 block mb-0.5">ARR Run</span>
                    <span class="text-[14.5px] sm:text-[15.5px] font-bold text-slate-900 block nx-counter"><?php echo htmlspecialchars($opp['arr']); ?></span>
                  </div>
                  <div>
                    <span class="text-[9.5px] font-mono uppercase tracking-wider text-slate-400 block mb-0.5">Allocation</span>
                    <span class="text-[13.5px] sm:text-[14.5px] font-bold text-purple-700 block nx-counter"><?php echo htmlspecialchars($opp['booked']); ?></span>
                  </div>
                </div>

                <!-- Live Round Allocation Progress Bar -->
                <div class="mb-4">
                  <div class="nx-progress-bar">
                    <div class="nx-progress-fill" style="width: <?php echo (int)$opp['booked_pct']; ?>%;"></div>
                  </div>
                </div>
              </div>

              <!-- Card Bottom Bar: Diligence & Link -->
              <div class="pt-3.5 border-t border-purple-100 flex items-center justify-between text-[13px]">
                <span class="inline-flex items-center gap-1.5 text-[10.5px] font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                  <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  VERIFIED DATA ROOM
                </span>

                <a href="login.php?role=investor" class="inline-flex items-center gap-1.5 font-bold text-purple-600 hover:text-purple-800 transition-colors">
                  <span>View Deal</span>
                  <span class="transition-transform duration-200 group-hover:translate-x-1" aria-hidden="true">&rarr;</span>
                </a>
              </div>
            </article>
          <?php
            endforeach;
          endfor;
          ?>

        </div>
      </div>
    </div>
  </div>

  <!-- Micro Footnote -->
  <div class="max-w-[1440px] mx-auto px-6 lg:px-14 mt-6 flex flex-col sm:flex-row items-center justify-between text-[11.5px] font-mono text-[#686868]/80 gap-3">
    <div class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
      <span>REAL-TIME PIPELINE UPDATES · HOVER TO PAUSE</span>
    </div>
    <div class="hidden md:block">
      DRAG OR USE ARROWS TO BROWSE DEALS
    </div>
  </div>
</section>

<!-- ============================================================
     INTERACTION & DYNAMIC CLIP-PATH SCROLL ENGINE (GSAP)
     ============================================================ -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const stage = document.getElementById('nx-opp-scroll-stage');
    const marquee = document.getElementById('nx-opp-marquee');
    const prevBtn = document.getElementById('nx-opp-prev');
    const nextBtn = document.getElementById('nx-opp-next');
    const wrapper = document.getElementById('nx-opp-wrapper');

    /* ----------------------------------------------------------
     * 1. UNIQUE GSAP CLIP-PATH VIEWPORT SCROLL REVEAL (SCRUB)
     * ---------------------------------------------------------- */
    if (!isReducedMotion && typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined' && stage) {
      // Cinematic lens aperture expansion scrubbed to page scroll
      gsap.fromTo(stage,
        {
          clipPath: 'inset(6% 4% 6% 4% round 40px)',
          scale: 0.94,
          opacity: 0.8
        },
        {
          clipPath: 'inset(0% 0% 0% 0% round 0px)',
          scale: 1,
          opacity: 1,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: '#nx-opportunities',
            start: 'top 85%',
            end: 'top 25%',
            scrub: 0.8
          }
        }
      );

      // Staggered clip-path entrance on cards for extra flair
      const cards = gsap.utils.toArray('.nx-opp-card');
      if (cards.length > 0) {
        gsap.fromTo(cards.slice(0, 6),
          {
            clipPath: 'polygon(0% 12%, 100% 0%, 100% 88%, 0% 100%)',
            opacity: 0.4,
            y: 30
          },
          {
            clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)',
            opacity: 1,
            y: 0,
            duration: 1.1,
            stagger: 0.07,
            ease: 'power3.out',
            clearProps: 'clipPath,transform',
            scrollTrigger: {
              trigger: '#nx-opp-scroll-stage',
              start: 'top 82%',
              once: true
            }
          }
        );
      }
    }

    /* ----------------------------------------------------------
     * 2. MANUAL STEPPING BUTTONS & CONTROLS
     * ---------------------------------------------------------- */
    if (!marquee) return;

    let manualOffset = 0;
    const cardStep = 440; // width + gap approx

    if (prevBtn && nextBtn) {
      prevBtn.addEventListener('click', () => {
        marquee.classList.add('is-paused');
        manualOffset += cardStep;
        if (manualOffset > 0) manualOffset = 0;
        marquee.style.transform = `translate3d(${manualOffset}px, 0, 0)`;
        
        // Auto-resume after 4s idle
        clearTimeout(window.__oppResumeTimer);
        window.__oppResumeTimer = setTimeout(() => {
          marquee.classList.remove('is-paused');
          marquee.style.transform = '';
          manualOffset = 0;
        }, 4000);
      });

      nextBtn.addEventListener('click', () => {
        marquee.classList.add('is-paused');
        manualOffset -= cardStep;
        marquee.style.transform = `translate3d(${manualOffset}px, 0, 0)`;

        clearTimeout(window.__oppResumeTimer);
        window.__oppResumeTimer = setTimeout(() => {
          marquee.classList.remove('is-paused');
          marquee.style.transform = '';
          manualOffset = 0;
        }, 4000);
      });
    }

    /* ----------------------------------------------------------
     * 3. TOUCH DRAG SUPPORT
     * ---------------------------------------------------------- */
    let startX = 0;

    wrapper.addEventListener('touchstart', (e) => {
      startX = e.touches[0].pageX;
      marquee.classList.add('is-paused');
    }, { passive: true });

    wrapper.addEventListener('touchmove', (e) => {
      const x = e.touches[0].pageX;
      const diff = x - startX;
      if (Math.abs(diff) > 20) {
        marquee.classList.add('is-paused');
      }
    }, { passive: true });

    wrapper.addEventListener('touchend', () => {
      clearTimeout(window.__oppResumeTimer);
      window.__oppResumeTimer = setTimeout(() => {
        marquee.classList.remove('is-paused');
      }, 3000);
    }, { passive: true });
  });
</script>
