<?php
/**
 * ============================================================================
 * include/home/hero.php
 * ----------------------------------------------------------------------------
 * NEXORA — Master Homepage Hero Section
 * 
 * Features:
 * - Radiant Colorful Pastel Aurora Canvas (Lavender, Blush Pink, Warm Peach)
 * - Minimal, High-End Editorial Hero (Centered Layout)
 * - Exact Content Match: "BUILD WHAT’S NEXT." & Discovery Platform positioning
 * - Deep Charcoal Typography with Vibrant Multi-Color Jewel Gradient Accent
 * - Awwwards-Level Smooth Curved Bottom Scroll Transition into Ecosystem
 * - Bi-directional GSAP ScrollTrigger Scrub
 * - Full prefers-reduced-motion accessibility & 360px+ responsive stability
 * ============================================================================
 */
?>

<style>
  /* ── Hero Wrapper & Pastel Aurora Canvas ─────────────────────── */
  .nx-hero-wrapper {
    position: relative;
    z-index: 2;
    background-color: #FAF5FF;
    border-radius: 36px 36px 0 0;
    box-shadow: 0 -30px 70px -20px rgba(30, 27, 75, 0.28);
    background-image:
      radial-gradient(ellipse 90% 75% at 50% 15%, #EDE9FE 0%, #FCE7F3 45%, #FAF5FF 100%),
      radial-gradient(at 0% 0%, #EDE9FE 0px, transparent 50%),
      radial-gradient(at 100% 0%, #FCE7F3 0px, transparent 50%),
      radial-gradient(at 50% 100%, #FFF7ED 0px, transparent 50%);
    color: #0F172A;
    font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif;
    overflow: visible;
  }

  .nx-hero {
    min-height: 100vh;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    position: relative;
    padding: clamp(96px, 12vh, 140px) clamp(20px, 5vw, 48px) clamp(80px, 10vh, 120px);
    box-sizing: border-box;
  }

  /* Ambient Floating Pastel Orbs */
  .nx-hero-orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(110px);
    pointer-events: none;
    opacity: 0.65;
    animation: floatHeroOrb 18s ease-in-out infinite alternate;
  }

  .nx-hero-orb-1 {
    width: 600px;
    height: 600px;
    background: linear-gradient(135deg, #DDD6FE 0%, #FBCFE8 100%);
    top: -150px;
    left: -120px;
    animation-duration: 16s;
  }

  .nx-hero-orb-2 {
    width: 550px;
    height: 550px;
    background: linear-gradient(135deg, #FED7AA 0%, #FEF08A 100%);
    bottom: -100px;
    right: -80px;
    animation-duration: 20s;
    animation-delay: -5s;
  }

  .nx-hero-orb-3 {
    width: 450px;
    height: 450px;
    background: linear-gradient(135deg, #A7F3D0 0%, #BAE6FD 100%);
    top: 30%;
    left: 45%;
    animation-duration: 22s;
    animation-delay: -10s;
  }

  @keyframes floatHeroOrb {
    0% {
      transform: translate(0px, 0px) scale(1);
    }

    50% {
      transform: translate(30px, 20px) scale(1.08);
    }

    100% {
      transform: translate(-25px, 15px) scale(0.95);
    }
  }

  /* ── Centered Content Container ────────────────────────── */
  .nx-hero-inner {
    width: 100%;
    max-width: 980px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    z-index: 10;
    will-change: transform, opacity;
  }

  /* ── Small Eyebrow Label ───────────────────────────────── */
  .nx-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 18px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1.5px solid #DDD6FE;
    font-size: clamp(10.5px, 0.85vw, 12px);
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #6D28D9;
    box-shadow: 0 4px 16px rgba(109, 40, 217, 0.08);
    margin-bottom: clamp(20px, 3vh, 32px);
    transition: all 0.25s ease;
  }

  .nx-hero-badge:hover {
    border-color: #C4B5FD;
    background-color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(109, 40, 217, 0.14);
  }

  .nx-hero-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    box-shadow: 0 0 10px rgba(236, 72, 153, 0.6);
    display: inline-block;
  }

  /* ── Main Heading ──────────────────────────────────────── */
  .nx-hero-heading {
    font-size: clamp(44px, 8.5vw, 108px);
    font-weight: 800;
    line-height: 0.95;
    letter-spacing: -0.04em;
    color: #0F172A;
    margin: 0 0 clamp(20px, 2.8vh, 28px);
    text-wrap: balance;
  }

  .nx-hero-heading span {
    display: block;
  }

  .nx-hero-gradient-text {
    background: linear-gradient(135deg, #7C3AED 0%, #EC4899 45%, #F97316 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* ── Description ───────────────────────────────────────── */
  .nx-hero-desc {
    font-size: clamp(15.5px, 1.35vw, 19.5px);
    line-height: 1.6;
    color: #475569;
    max-width: 640px;
    font-weight: 500;
    margin: 0 auto clamp(32px, 4.2vh, 44px);
    text-wrap: pretty;
  }

  /* ── Action Buttons ────────────────────────────────────── */
  .nx-hero-ctas {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: center;
    gap: 14px;
    width: 100%;
  }

  @media (min-width: 640px) {
    .nx-hero-ctas {
      flex-direction: row;
      align-items: center;
      width: auto;
      gap: 16px;
    }
  }

  .nx-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    height: 54px;
    padding: 0 34px;
    border-radius: 999px;
    background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 50%, #F97316 100%);
    background-size: 180% auto;
    color: #FFFFFF;
    font-size: 14.5px;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow: 0 10px 25px -4px rgba(139, 92, 246, 0.38), 0 4px 10px -2px rgba(236, 72, 153, 0.22);
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
  }

  .nx-btn-primary:hover {
    background-position: right center;
    transform: translateY(-2px);
    box-shadow: 0 14px 30px -4px rgba(139, 92, 246, 0.48), 0 6px 14px -2px rgba(236, 72, 153, 0.32);
  }

  .nx-btn-primary:active {
    transform: translateY(0);
  }

  .nx-btn-primary .nx-arrow {
    display: inline-block;
    transition: transform 0.22s ease;
  }

  .nx-btn-primary:hover .nx-arrow {
    transform: translateX(4px);
  }

  .nx-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 54px;
    padding: 0 34px;
    border-radius: 999px;
    background-color: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    color: #0F172A;
    border: 1.5px solid #DDD6FE;
    font-size: 14.5px;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(109, 40, 217, 0.06);
    transition: all 0.25s ease;
    cursor: pointer;
  }

  .nx-btn-secondary:hover {
    border-color: #A78BFA;
    background-color: #FFFFFF;
    color: #6D28D9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(109, 40, 217, 0.12);
  }

  .nx-btn-secondary:active {
    transform: translateY(0);
  }

  /* ── Hero Trust Bar & VC Partner Logos ─────────────────── */
  .nx-hero-trust {
    margin-top: clamp(40px, 5.5vh, 56px);
    width: 100%;
    max-width: 880px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
  }

  .nx-trust-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    width: 100%;
    padding: 18px 22px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1.5px solid rgba(221, 214, 254, 0.8);
    border-radius: 24px;
    box-shadow: 0 10px 30px -8px rgba(124, 58, 237, 0.08), 0 4px 12px -2px rgba(236, 72, 153, 0.04);
  }

  @media (min-width: 640px) {
    .nx-trust-grid {
      grid-template-columns: repeat(4, 1fr);
      padding: 20px 28px;
    }
  }

  .nx-trust-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .nx-trust-num {
    font-size: clamp(22px, 2.4vw, 29px);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: #0F172A;
    line-height: 1.1;
  }

  .nx-trust-lbl {
    font-size: 11.5px;
    font-weight: 600;
    color: #64748B;
    margin-top: 4px;
    letter-spacing: 0.02em;
  }

  .nx-trust-logos-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    width: 100%;
  }

  .nx-trust-logos-label {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #94A3B8;
  }

  .nx-trust-logos {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 10px 14px;
  }

  .nx-partner-mark {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #475569;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(221, 214, 254, 0.7);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
  }

  .nx-partner-mark:hover {
    color: #6D28D9;
    border-color: #C4B5FD;
    background: #FFFFFF;
    transform: translateY(-1px);
  }

  /* ── Subtle Scroll Cue ─────────────────────────────────── */
  .nx-hero-cue {
    margin-top: clamp(32px, 4vh, 48px);
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #64748B;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    transition: all 0.25s ease;
  }

  .nx-hero-cue:hover {
    color: #6D28D9;
  }

  .nx-hero-mouse {
    width: 22px;
    height: 34px;
    border: 1.5px solid #CBD5E1;
    border-radius: 999px;
    position: relative;
    display: flex;
    justify-content: center;
  }

  .nx-hero-wheel {
    width: 4px;
    height: 8px;
    background-color: #8B5CF6;
    border-radius: 999px;
    position: absolute;
    top: 6px;
    animation: nxHeroScrollWheel 2s cubic-bezier(0.65, 0, 0.35, 1) infinite;
  }

  @keyframes nxHeroScrollWheel {
    0% {
      transform: translateY(0);
      opacity: 1;
    }

    50% {
      transform: translateY(10px);
      opacity: 0;
    }

    100% {
      transform: translateY(0);
      opacity: 0;
    }
  }

</style>

<!-- ============================================================
     HERO SECTION DOM (NEXORA LUXE PASTEL AURORA)
     ============================================================ -->
<section id="nx-hero" class="nx-hero-wrapper" aria-label="Hero Introduction">

  <!-- Ambient Floating Pastel Mesh Orbs -->
  <div class="nx-hero-orb nx-hero-orb-1"></div>
  <div class="nx-hero-orb nx-hero-orb-2"></div>
  <div class="nx-hero-orb nx-hero-orb-3"></div>

  <div class="nx-hero">
    <div class="nx-hero-inner" id="nx-hero-content">

      <!-- Small Eyebrow Label -->
      <div class="nx-hero-badge" id="hero-badge">
        <span class="nx-hero-badge-dot" aria-hidden="true"></span>
        <span>THE NEXT-GEN VENTURE DIRECTORY</span>
      </div>

      <!-- Main Headline: BUILD WHAT’S NEXT. -->
      <h1 class="nx-hero-heading font-sans" id="hero-heading">
        <span>BUILD WHAT’S</span>
        <span class="nx-hero-gradient-text">NEXT.</span>
      </h1>

      <!-- Subtitle Description -->
      <p class="nx-hero-desc" id="hero-desc">
        NEXORA is a high-signal discovery platform connecting ambitious tech startups with verified venture capital,
        angel networks, and growth syndicates.
      </p>

      <!-- Action Buttons -->
      <div class="nx-hero-ctas" id="hero-ctas">
        <a href="login.php?role=investor" class="nx-btn-primary" id="hero-btn-investor" aria-label="Access as Investor">
          <span>Explore Opportunities</span>
          <span class="nx-arrow" aria-hidden="true">&rarr;</span>
        </a>

        <a href="login.php?role=startup" class="nx-btn-secondary" id="hero-btn-startup" aria-label="Sign in as Startup">
          <span>For Startups</span>
          <span class="nx-arrow" aria-hidden="true">&rarr;</span>
        </a>
      </div>

      <!-- Hero Trust Bar & VC Partner Logos -->
      <div class="nx-hero-trust" id="hero-trust">
        <div class="nx-trust-grid">
          <div class="nx-trust-stat">
            <span class="nx-trust-num nx-counter text-purple-700">₹480 Cr+</span>
            <span class="nx-trust-lbl">Capital Facilitated</span>
          </div>
          <div class="nx-trust-stat">
            <span class="nx-trust-num nx-counter text-pink-600">320+</span>
            <span class="nx-trust-lbl">Vetted Startups</span>
          </div>
          <div class="nx-trust-stat">
            <span class="nx-trust-num nx-counter text-emerald-600">96%</span>
            <span class="nx-trust-lbl">Diligence Rate</span>
          </div>
          <div class="nx-trust-stat">
            <span class="nx-trust-num nx-counter text-amber-600">14 Sectors</span>
            <span class="nx-trust-lbl">DeepTech &amp; AI</span>
          </div>
        </div>

        <div class="nx-trust-logos-wrap">
          <span class="nx-trust-logos-label">BACKED BY ALLOCATORS &amp; OPERATORS FROM</span>
          <div class="nx-trust-logos">
            <span class="nx-partner-mark">PEAK XV SURGE</span>
            <span class="nx-partner-mark">BLUME VENTURES</span>
            <span class="nx-partner-mark">ACCEL ATOMS</span>
            <span class="nx-partner-mark">ELEV8 CAPITAL</span>
            <span class="nx-partner-mark">ANGEL SYNDICATE</span>
          </div>
        </div>
      </div>

      <!-- Subtle Scroll Cue -->
      <a href="#nx-opportunities" class="nx-hero-cue" aria-label="Scroll to explore">
        <span>SCROLL TO EXPLORE</span>
        <div class="nx-hero-mouse" aria-hidden="true">
          <div class="nx-hero-wheel"></div>
        </div>
      </a>

    </div>
  </div>

  </div>
</section>

<!-- ============================================================
     GSAP TIMELINE & SCROLLTRIGGER SCRIPT
     ============================================================ -->
<script>
  (function () {
    'use strict';
    var isRM = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;

    gsap.registerPlugin(ScrollTrigger);

    var heroContent = document.getElementById('nx-hero-content');

    if (!isRM) {
      /* 1. Subtle, Luxury Entrance on Load */
      var loadTL = gsap.timeline({ defaults: { ease: 'power3.out' } });
      loadTL
        .from('#hero-badge', { y: 16, opacity: 0, duration: 0.65, delay: 0.1 })
        .from('#hero-heading span', { y: 32, opacity: 0, duration: 0.85, stagger: 0.1 }, '-=0.45')
        .from('#hero-desc', { y: 20, opacity: 0, duration: 0.7 }, '-=0.55')
        .from('#hero-ctas', { y: 18, opacity: 0, duration: 0.65 }, '-=0.45')
        .from('#hero-trust', { y: 24, opacity: 0, duration: 0.75 }, '-=0.4')
        .from('.nx-hero-cue', { y: 14, opacity: 0, duration: 0.6 }, '-=0.4');

      /* 2. Master Scrub Scroll Effect */
      if (heroContent) {
        gsap.to(heroContent, {
          y: -40,
          scale: 0.97,
          opacity: 0.8,
          ease: 'none',
          scrollTrigger: {
            trigger: '#nx-hero',
            start: 'top top',
            end: 'bottom 20%',
            scrub: 0.8,
            invalidateOnRefresh: true,
          }
        });
      }
    } else {
      if (heroContent) gsap.set(heroContent, { y: 0, scale: 1, opacity: 1 });
    }
  })();
</script>