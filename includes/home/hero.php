<?php
/**
 * ============================================================================
 * includes/home/hero.php
 * ----------------------------------------------------------------------------
 * NEXORA — Sovereign Venture Terminal Hero Section
 * 
 * Design Standards:
 * - Clean, Clear, High-Conviction Value Proposition
 * - Silicon Valley & DIFC Venture Architecture (Executive Dealroom Showcase)
 * - Pure Light Slate Canvas with Ambient Luminous Studio Glow
 * - Interactive Dealroom Preview with Tabular Metrics & Verified Badges
 * - Monochromatic Trust Architecture with Institutional Proof Points
 * ============================================================================
 */
?>

<style>
  /* ── Hero Canvas & Studio Lighting ─────────────────────────── */
  .nx-hero-wrapper {
    position: relative;
    z-index: 2;
    background-color: #F8FAFC;
    background-image:
      radial-gradient(ellipse 80% 50% at 50% -2%, rgba(219, 234, 254, 0.75) 0%, transparent 65%),
      radial-gradient(ellipse 60% 40% at 90% 20%, rgba(224, 231, 255, 0.5) 0%, transparent 60%),
      radial-gradient(ellipse 50% 35% at 10% 30%, rgba(209, 250, 229, 0.35) 0%, transparent 55%),
      linear-gradient(180deg, #F8FAFC 0%, #FFFFFF 100%);
    color: #0F172A;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    overflow: hidden;
  }

  /* Subtle High-Tech Blueprint Grid Texture */
  .nx-hero-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background-size: 40px 40px;
    background-image: 
      linear-gradient(to right, rgba(15, 23, 42, 0.025) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(15, 23, 42, 0.025) 1px, transparent 1px);
    mask-image: radial-gradient(ellipse 70% 60% at 50% 35%, #000 30%, transparent 80%);
    -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 35%, #000 30%, transparent 80%);
    pointer-events: none;
    z-index: 1;
  }

  .nx-hero {
    min-height: 100vh;
    min-height: 100svh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 2;
    padding: clamp(96px, 12vh, 128px) clamp(20px, 4vw, 64px) clamp(44px, 5vh, 68px);
    box-sizing: border-box;
  }

  /* Ambient Mesh Luminescence */
  .nx-hero-orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(140px);
    pointer-events: none;
    opacity: 0.55;
    animation: nxFloatOrb 22s ease-in-out infinite alternate;
  }

  .nx-hero-orb-1 {
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(191, 219, 254, 0.6) 0%, transparent 70%);
    top: -150px;
    left: -100px;
  }

  .nx-hero-orb-2 {
    width: 540px;
    height: 540px;
    background: radial-gradient(circle, rgba(224, 231, 255, 0.5) 0%, transparent 70%);
    bottom: -100px;
    right: -80px;
    animation-delay: -7s;
  }

  @keyframes nxFloatOrb {
    0% { transform: translate3d(0, 0, 0) scale(1); }
    50% { transform: translate3d(30px, 20px, 0) scale(1.06); }
    100% { transform: translate3d(-25px, 15px, 0) scale(0.96); }
  }

  /* ── Main 2-Column Stage ───────────────────────────────────── */
  .nx-hero-stage {
    width: 100%;
    max-width: 1360px;
    margin: 0 auto;
    position: relative;
    z-index: 10;
  }

  .nx-hero-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 40px;
    align-items: center;
  }

  @media (min-width: 1024px) {
    .nx-hero-grid {
      grid-template-columns: 1.1fr 0.9fr;
      gap: 56px;
    }
  }

  @media (min-width: 1280px) {
    .nx-hero-grid {
      grid-template-columns: 1.15fr 0.85fr;
      gap: 68px;
    }
  }

  /* ── Eyebrow Badge ─────────────────────────────────────────── */
  .nx-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 7px 18px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(203, 213, 225, 0.85);
    font-size: 12px;
    color: #1E293B;
    box-shadow: 0 2px 14px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
    margin-bottom: 22px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-hero-badge:hover {
    border-color: #93C5FD;
    box-shadow: 0 6px 22px rgba(37, 99, 235, 0.1);
    transform: translateY(-1px);
  }

  .nx-live-pulse {
    position: relative;
    width: 8px;
    height: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .nx-live-pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: #059669;
  }

  .nx-live-pulse-ring {
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: rgba(5, 150, 105, 0.28);
    animation: nxPulseAnim 2s cubic-bezier(0, 0, 0.2, 1) infinite;
  }

  @keyframes nxPulseAnim {
    0% { transform: scale(0.8); opacity: 0.85; }
    100% { transform: scale(2.4); opacity: 0; }
  }

  /* ── Main Heading ──────────────────────────────────────────── */
  .nx-hero-heading {
    font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
    font-size: clamp(36px, 4.5vw, 64px);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.04em;
    color: #0F172A;
    margin: 0 0 20px;
    text-wrap: balance;
  }

  .nx-hero-gradient-text {
    background: linear-gradient(135deg, #0F172A 0%, #1D4ED8 45%, #2563EB 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
  }

  /* ── Narrative Subtitle ────────────────────────────────────── */
  .nx-hero-desc {
    font-size: clamp(15.5px, 1.15vw, 17.5px);
    line-height: 1.68;
    color: #475569;
    max-width: 620px;
    font-weight: 400;
    margin: 0 0 32px;
    text-wrap: pretty;
  }

  /* ── Action Buttons ────────────────────────────────────────── */
  .nx-hero-ctas {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 14px;
    width: 100%;
    margin-bottom: 26px;
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
    height: 52px;
    padding: 0 28px;
    border-radius: 999px;
    background: #0F172A;
    color: #FFFFFF;
    font-size: 14.5px;
    font-weight: 600;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid #1E293B;
    cursor: pointer;
  }

  .nx-btn-primary:hover {
    background: #1E293B;
    transform: translateY(-2px);
    box-shadow: 0 16px 34px -4px rgba(15, 23, 42, 0.35);
  }

  .nx-btn-badge {
    padding: 2px 8px;
    background: rgba(37, 99, 235, 0.25);
    border: 1px solid rgba(147, 197, 253, 0.35);
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    color: #93C5FD;
    letter-spacing: 0.02em;
  }

  .nx-btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    height: 52px;
    padding: 0 28px;
    border-radius: 999px;
    background-color: #FFFFFF;
    color: #0F172A;
    border: 1.5px solid #CBD5E1;
    font-size: 14.5px;
    font-weight: 600;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
  }

  .nx-btn-secondary:hover {
    border-color: #93C5FD;
    color: #1D4ED8;
    background-color: #F8FAFC;
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(37, 99, 235, 0.08);
  }

  /* Micro Guarantee Strip */
  .nx-hero-guarantee {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 12.5px;
    font-weight: 500;
    color: #64748B;
    flex-wrap: wrap;
  }

  .nx-guarantee-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #334155;
  }

  /* ── Right-Side Sovereign Venture Terminal ──────────────────── */
  .nx-terminal-wrap {
    position: relative;
    width: 100%;
    max-width: 560px;
    margin: 0 auto;
  }

  .nx-terminal-halo {
    position: absolute;
    inset: -24px;
    border-radius: 44px;
    background: radial-gradient(circle at 50% 50%, rgba(37, 99, 235, 0.2), rgba(5, 150, 105, 0.1) 60%, transparent 80%);
    filter: blur(32px);
    z-index: -1;
    pointer-events: none;
  }

  .nx-terminal-window {
    position: relative;
    border-radius: 24px;
    background: #0B1120;
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow:
      0 28px 75px -15px rgba(15, 23, 42, 0.4),
      0 14px 34px -8px rgba(37, 99, 235, 0.22),
      inset 0 1px 0 rgba(255, 255, 255, 0.2);
    overflow: hidden;
    transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.5s ease;
  }

  .nx-terminal-window:hover {
    transform: translateY(-4px);
    box-shadow:
      0 36px 85px -15px rgba(15, 23, 42, 0.48),
      0 18px 44px -8px rgba(37, 99, 235, 0.28);
  }

  /* Terminal Top Bar */
  .nx-terminal-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 44px;
    padding: 0 18px;
    background: rgba(15, 23, 42, 0.96);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .nx-term-dots {
    display: flex;
    align-items: center;
    gap: 7px;
  }

  .nx-term-dot {
    width: 9.5px;
    height: 9.5px;
    border-radius: 50%;
  }

  .nx-term-dot-red { background: #EF4444; opacity: 0.85; }
  .nx-term-dot-yellow { background: #F59E0B; opacity: 0.85; }
  .nx-term-dot-green { background: #10B981; opacity: 0.85; }

  .nx-term-title {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    color: #94A3B8;
    letter-spacing: 0.05em;
  }

  .nx-term-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px;
    border-radius: 999px;
    background: rgba(5, 150, 105, 0.18);
    border: 1px solid rgba(5, 150, 105, 0.35);
    color: #34D399;
    font-size: 10.5px;
    font-weight: 600;
    font-family: ui-monospace, monospace;
  }

  /* Terminal Dealroom Showcase Content */
  .nx-terminal-body {
    padding: 18px;
    background: #0B1120;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  /* Dealroom Card Header */
  .nx-deal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .nx-deal-brand {
    display: flex;
    align-items: center;
    gap: 11px;
  }

  .nx-deal-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
    border: 1px solid rgba(255, 255, 255, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    color: #60A5FA;
    font-family: 'Plus Jakarta Sans', sans-serif;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .nx-deal-meta h4 {
    margin: 0;
    font-size: 14.5px;
    font-weight: 700;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'Plus Jakarta Sans', sans-serif;
  }

  .nx-deal-meta p {
    margin: 2px 0 0;
    font-size: 11.5px;
    color: #94A3B8;
  }

  .nx-deal-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.04em;
    background: rgba(37, 99, 235, 0.2);
    border: 1px solid rgba(96, 165, 250, 0.35);
    color: #93C5FD;
    text-transform: uppercase;
  }

  /* Image Viewport */
  .nx-terminal-viewport {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 14px;
    overflow: hidden;
    background: #020617;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }

  .nx-terminal-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-terminal-window:hover .nx-terminal-img {
    transform: scale(1.04);
  }

  .nx-img-gradient-scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(11, 17, 32, 0.1) 0%, rgba(11, 17, 32, 0.85) 100%);
    pointer-events: none;
  }

  /* Floating Live Activity Pill inside Viewport */
  .nx-live-pill {
    position: absolute;
    top: 12px;
    left: 12px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.88);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.16);
    font-size: 11px;
    font-weight: 500;
    color: #E2E8F0;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
  }

  .nx-live-pill-tag {
    color: #34D399;
    font-weight: 700;
  }

  /* Tabular Metrics Grid */
  .nx-deal-metrics-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 12px 14px;
    background: rgba(15, 23, 42, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
  }

  .nx-deal-metric {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  .nx-deal-metric:not(:last-child) {
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    padding-right: 8px;
  }

  .nx-deal-m-lbl {
    font-size: 10px;
    font-weight: 500;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .nx-deal-m-val {
    font-size: 14.5px;
    font-weight: 800;
    color: #F8FAFC;
    font-family: 'Plus Jakarta Sans', monospace;
    margin-top: 2px;
  }

  .nx-deal-m-growth {
    font-size: 9.5px;
    font-weight: 600;
    color: #34D399;
    margin-top: 1px;
  }

  /* Live Allocation Progress Bar */
  .nx-alloc-bar-wrap {
    display: flex;
    flex-direction: column;
    gap: 7px;
  }

  .nx-alloc-labels {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
  }

  .nx-alloc-title {
    color: #CBD5E1;
    font-weight: 600;
  }

  .nx-alloc-stat {
    color: #60A5FA;
    font-family: ui-monospace, monospace;
    font-weight: 700;
  }

  .nx-progress-track {
    width: 100%;
    height: 7px;
    background: rgba(30, 41, 59, 0.8);
    border-radius: 999px;
    overflow: hidden;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.06);
  }

  .nx-progress-fill {
    width: 84%;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #2563EB 0%, #38BDF8 60%, #10B981 100%);
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.5);
    position: relative;
  }

  .nx-progress-fill::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: nxShimmer 2.4s infinite;
  }

  @keyframes nxShimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }

  /* Lead Backers Strip */
  .nx-deal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 6px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
  }

  .nx-lead-backers {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #94A3B8;
  }

  .nx-backer-pills {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .nx-backer-pill {
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 10px;
    color: #E2E8F0;
    font-weight: 600;
  }

  .nx-diligence-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: #38BDF8;
    text-decoration: none;
    transition: color 0.2s;
  }

  .nx-diligence-btn:hover {
    color: #93C5FD;
  }

  /* Floating Telemetry Badges */
  .nx-float-badge {
    position: absolute;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 14px;
    z-index: 20;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    pointer-events: none;
  }

  .nx-float-badge-1 {
    top: -16px;
    left: -20px;
    background: rgba(255, 255, 255, 0.94);
    border: 1px solid rgba(226, 232, 240, 0.95);
    color: #0F172A;
    padding: 8px 14px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    display: flex;
    align-items: center;
    gap: 9px;
  }

  .nx-float-badge-2 {
    bottom: -16px;
    right: -16px;
    background: rgba(15, 23, 42, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #FFFFFF;
    padding: 9px 15px;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.35);
    display: flex;
    align-items: center;
    gap: 9px;
  }

  @media (max-width: 640px) {
    .nx-float-badge {
      display: none;
    }
  }

  /* ── Bottom Proof Architecture Grid ────────────────────────── */
  .nx-hero-trust-section {
    margin-top: clamp(48px, 6vh, 64px);
    width: 100%;
    max-width: 1360px;
    margin-left: auto;
    margin-right: auto;
    position: relative;
    z-index: 10;
  }

  .nx-trust-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    width: 100%;
    padding: 24px 28px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 24px;
    box-shadow: 0 8px 30px -6px rgba(15, 23, 42, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  }

  @media (min-width: 768px) {
    .nx-trust-grid {
      grid-template-columns: repeat(4, 1fr);
      padding: 26px 36px;
      gap: 20px;
    }
  }

  .nx-trust-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
  }

  @media (min-width: 768px) {
    .nx-trust-stat:not(:last-child)::after {
      content: '';
      position: absolute;
      right: -10px;
      top: 15%;
      height: 70%;
      width: 1px;
      background: #E2E8F0;
    }
  }

  .nx-trust-num {
    font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
    font-size: clamp(24px, 2.4vw, 32px);
    font-weight: 800;
    letter-spacing: -0.035em;
    line-height: 1.15;
    color: #0F172A;
    font-variant-numeric: tabular-nums;
  }

  .nx-trust-lbl {
    font-size: 12px;
    font-weight: 500;
    color: #64748B;
    margin-top: 4px;
    letter-spacing: -0.01em;
  }

  .nx-trust-logos-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    width: 100%;
    margin-top: 26px;
  }

  .nx-trust-logos-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
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
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #475569;
    padding: 7px 16px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(226, 232, 240, 0.85);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .nx-partner-mark:hover {
    color: #1D4ED8;
    border-color: #93C5FD;
    background: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
  }

  /* ── Scroll Cue ────────────────────────────────────────────── */
  .nx-hero-cue {
    margin-top: 28px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #64748B;
    text-decoration: none;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.14em;
    transition: all 0.25s ease;
  }

  .nx-hero-cue:hover {
    color: #1D4ED8;
  }

  .nx-hero-mouse {
    width: 20px;
    height: 32px;
    border: 1.5px solid #CBD5E1;
    border-radius: 999px;
    position: relative;
    display: flex;
    justify-content: center;
  }

  .nx-hero-wheel {
    width: 3.5px;
    height: 7px;
    background-color: #2563EB;
    border-radius: 999px;
    position: absolute;
    top: 5px;
    animation: nxHeroScrollWheel 2s cubic-bezier(0.65, 0, 0.35, 1) infinite;
  }

  @keyframes nxHeroScrollWheel {
    0% { transform: translateY(0); opacity: 1; }
    50% { transform: translateY(9px); opacity: 0; }
    100% { transform: translateY(0); opacity: 0; }
  }
</style>

<!-- ============================================================
     HERO SECTION DOM (NEXORA SOVEREIGN VENTURE TERMINAL)
     ============================================================ -->
<section id="nx-hero" class="nx-hero-wrapper" aria-label="Hero Introduction">

  <!-- Ambient Floating Luminous Mesh Orbs -->
  <div class="nx-hero-orb nx-hero-orb-1" aria-hidden="true"></div>
  <div class="nx-hero-orb nx-hero-orb-2" aria-hidden="true"></div>

  <div class="nx-hero">
    
    <!-- ── 2-Column Split Stage ──────────────────────────────── -->
    <div class="nx-hero-stage" id="nx-hero-content">
      <div class="nx-hero-grid">

        <!-- Column 1 (Left): Value Proposition & CTAs -->
        <div class="text-left" id="hero-left-col">

          <!-- Trust Eyebrow Badge -->
          <div class="nx-hero-badge" id="hero-badge">
            <span class="nx-live-pulse" aria-hidden="true">
              <span class="nx-live-pulse-ring"></span>
              <span class="nx-live-pulse-dot"></span>
            </span>
            <span class="font-semibold text-slate-800 tracking-wide text-[11.5px]">INDIA &middot; UAE VENTURE SYNDICATE</span>
            <span class="text-slate-300 font-light">&middot;</span>
            <span class="text-blue-700 font-bold tracking-wider uppercase text-[11px]">$180M+ PIPELINE</span>
          </div>

          <!-- Main Headline -->
          <h1 class="nx-hero-heading" id="hero-heading">
            <span>Where Exceptional Founders</span><br>
            <span class="nx-hero-gradient-text">Meet Institutional Capital.</span>
          </h1>

          <!-- Subtitle Description -->
          <p class="nx-hero-desc" id="hero-desc">
            NEXORA is the premier private dealroom connecting audited startups with accredited angel syndicates and Tier-1 VCs across India and the UAE. Direct cap table access, 100% verified diligence, and zero broker commissions.
          </p>

          <!-- Action Buttons -->
          <div class="nx-hero-ctas" id="hero-ctas">
            <a href="auth/login.php?role=investor" class="nx-btn-primary" id="hero-btn-investor" aria-label="Explore Curated Dealflow">
              <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
              <span>Explore Curated Dealflow</span>
              <span class="nx-btn-badge">24 Deals</span>
              <span class="text-slate-400 text-sm ml-0.5" aria-hidden="true">&rarr;</span>
            </a>

            <a href="auth/login.php?role=startup" class="nx-btn-secondary" id="hero-btn-startup" aria-label="Apply to Raise Venture Capital">
              <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span>Apply to Raise Capital</span>
              <span class="text-slate-400 text-sm ml-0.5" aria-hidden="true">&rarr;</span>
            </a>
          </div>

          <!-- Micro Guarantees Strip -->
          <div class="nx-hero-guarantee" id="hero-guarantee">
            <div class="nx-guarantee-item">
              <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <span>100% Free for Founders</span>
            </div>
            <span class="text-slate-300">&bull;</span>
            <div class="nx-guarantee-item">
              <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <span>0% Broker Fees</span>
            </div>
            <span class="text-slate-300">&bull;</span>
            <div class="nx-guarantee-item">
              <svg class="w-4 h-4 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <span>MCA &amp; DigiLocker Verified</span>
            </div>
          </div>

        </div>

        <!-- Column 2 (Right): Interactive Sovereign Venture Terminal Frame -->
        <div class="nx-terminal-wrap" id="hero-right-col">
          <div class="nx-terminal-halo"></div>

          
          
          <div class="nx-terminal-window">
            
            <!-- Terminal Header Chrome -->
            <div class="nx-terminal-bar">
              <div class="nx-term-dots">
                <span class="nx-term-dot nx-term-dot-red"></span>
                <span class="nx-term-dot nx-term-dot-yellow"></span>
                <span class="nx-term-dot nx-term-dot-green"></span>
              </div>
              <span class="nx-term-title">nexora.dealroom/v2.4/live</span>
              <span class="nx-term-status">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                LIVE DEALROOM
              </span>
            </div>

            <!-- Terminal Showcase Body -->
            <div class="nx-terminal-body">

              <!-- Active Deal Header -->
              <div class="nx-deal-header">
                <div class="nx-deal-brand">
                  <div class="nx-deal-logo">KR</div>
                  <div class="nx-deal-meta">
                    <h4>
                      <span>Kinetix Robotics</span>
                      <svg class="w-3.5 h-3.5 text-blue-400 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    </h4>
                    <p>Industrial Autonomous Fleet &middot; Series A</p>
                  </div>
                </div>
                <div class="nx-deal-badge">84% ALLOCATED</div>
              </div>

              <!-- Terminal Image Viewport with Live Activity Pill -->
              <div class="nx-terminal-viewport">
                <img 
                  src="assets/images/hero_venture.jpg" 
                  alt="Founders and investors reviewing audited venture allocation dealflow" 
                  class="nx-terminal-img"
                  width="800"
                  height="450"
                  loading="eager"
                />
                <div class="nx-img-gradient-scrim"></div>

                <!-- Floating Live Toast Pill inside image -->
                <div class="nx-live-pill">
                  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                  <span><span class="nx-live-pill-tag">Just Committed:</span> $50,000 by Blume Syndicate</span>
                </div>
              </div>

              <!-- Tabular Deal Metrics Grid -->
              <div class="nx-deal-metrics-grid">
                <div class="nx-deal-metric">
                  <span class="nx-deal-m-lbl">Target Round</span>
                  <span class="nx-deal-m-val">$2.5M</span>
                  <span class="nx-deal-m-growth text-slate-400">Equity Round</span>
                </div>
                <div class="nx-deal-metric">
                  <span class="nx-deal-m-lbl">Committed</span>
                  <span class="nx-deal-m-val text-blue-400">$2.1M</span>
                  <span class="nx-deal-m-growth">84% Filled</span>
                </div>
                <div class="nx-deal-metric">
                  <span class="nx-deal-m-lbl">Audited ARR</span>
                  <span class="nx-deal-m-val">$1.8M</span>
                  <span class="nx-deal-m-growth">+3.4x YoY</span>
                </div>
              </div>

              <!-- Live Syndicate Allocation Progress -->
              <div class="nx-alloc-bar-wrap">
                <div class="nx-alloc-labels">
                  <span class="nx-alloc-title">Syndicate Allocation Pool</span>
                  <span class="nx-alloc-stat">$400,000 Remaining</span>
                </div>
                <div class="nx-progress-track">
                  <div class="nx-progress-fill"></div>
                </div>
              </div>

              <!-- Deal Footer / Diligence Action -->
              <div class="nx-deal-footer">
                <div class="nx-lead-backers">
                  <span>Lead Allocators:</span>
                  <div class="nx-backer-pills">
                    <span class="nx-backer-pill">Peak XV</span>
                    <span class="nx-backer-pill">Blume</span>
                  </div>
                </div>
                <a href="auth/login.php?role=investor" class="nx-diligence-btn">
                  <span>View Due Diligence</span>
                  <span aria-hidden="true">&rarr;</span>
                </a>
              </div>

            </div>

          </div>
        </div>

      </div>
    </div>

    <!-- ── Bottom Trust Architecture Proof Grid ──────────────── -->
    <div class="nx-hero-trust-section" id="hero-trust">
      <div class="nx-trust-grid">
        <div class="nx-trust-stat">
          <span class="nx-trust-num">$180M+</span>
          <span class="nx-trust-lbl">Syndicate Capital Pipeline</span>
        </div>
        <div class="nx-trust-stat">
          <span class="nx-trust-num">500+</span>
          <span class="nx-trust-lbl">Audited Private Dealrooms</span>
        </div>
        <div class="nx-trust-stat">
          <span class="nx-trust-num">1,400+</span>
          <span class="nx-trust-lbl">Accredited Angels &amp; VCs</span>
        </div>
        <div class="nx-trust-stat">
          <span class="nx-trust-num">14 Days</span>
          <span class="nx-trust-lbl">Median Term Sheet Speed</span>
        </div>
      </div>

      <div class="nx-trust-logos-wrap">
        <span class="nx-trust-logos-label">CO-INVESTORS &amp; ALLOCATORS FROM TOP VENTURE FUNDS</span>
        <div class="nx-trust-logos">
          <span class="nx-partner-mark">PEAK XV SURGE</span>
          <span class="nx-partner-mark">BLUME VENTURES</span>
          <span class="nx-partner-mark">ACCEL ATOMS</span>
          <span class="nx-partner-mark">ELEV8 CAPITAL</span>
          <span class="nx-partner-mark">SPECIALE INVEST</span>
          <span class="nx-partner-mark">DIFC INNOVATION HUB</span>
        </div>
      </div>

      <!-- Subtle Scroll Cue -->
      <div class="text-center">
        <a href="#nx-opportunities" class="nx-hero-cue" aria-label="Scroll to explore live opportunities">
          <span>EXPLORE VERIFIED VENTURES</span>
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

    var leftCol = document.getElementById('hero-left-col');
    var rightCol = document.getElementById('hero-right-col');
    var trustBar = document.getElementById('hero-trust');

    if (!isRM) {
      /* Smooth Entrance on Page Load */
      var loadTL = gsap.timeline({ defaults: { ease: 'power3.out' } });
      loadTL
        .from('#hero-badge', { y: 16, opacity: 0, duration: 0.65, delay: 0.08 })
        .from('#hero-heading span', { y: 26, opacity: 0, duration: 0.75, stagger: 0.08 }, '-=0.45')
        .from('#hero-desc', { y: 18, opacity: 0, duration: 0.65 }, '-=0.5')
        .from('#hero-ctas', { y: 16, opacity: 0, duration: 0.6 }, '-=0.45')
        .from('#hero-guarantee', { y: 12, opacity: 0, duration: 0.55 }, '-=0.4')
        .from('#hero-right-col', { y: 28, opacity: 0, scale: 0.97, duration: 0.85 }, '-=0.65')
        .from('#hero-trust', { y: 20, opacity: 0, duration: 0.7 }, '-=0.4');

      /* Master Scrub Scroll Effect */
      var heroStage = document.getElementById('nx-hero-content');
      if (heroStage) {
        gsap.to(heroStage, {
          y: -32,
          scale: 0.985,
          opacity: 0.92,
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
      if (leftCol) gsap.set(leftCol, { opacity: 1, y: 0 });
      if (rightCol) gsap.set(rightCol, { opacity: 1, y: 0, scale: 1 });
    }
  })();
</script>