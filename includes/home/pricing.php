<?php
/**
 * ============================================================================
 * NEXORA — TRANSPARENT VENTURE PRICING
 * File: includes/home/pricing.php
 * ----------------------------------------------------------------------------
 * Two-Sided Flat Seat Venture Pricing:
 * 1. For Builders & Teams — Founder Basic Plan (₹3,500 / mo)
 * 2. For Backers & Angels — Investor Pass (₹2,500 / mo)
 * 
 * Features:
 * - 0% Commission & No Secret Broker Retainers
 * - Self-contained HTML + CSS + JS (No dependencies)
 * - Accessible, responsive, aligned with NEXORA design language
 * ============================================================================
 */

$nxpEscape = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$nxpRoute = static function ($path) {
  $v = function_exists('url') ? url($path) : $path;
  return preg_match('~^[a-z][a-z0-9+.-]*:~i', (string) $v) && !preg_match('~^https?://~i', (string) $v) ? $path : $v;
};
?>

<section class="nxp" id="nx-pricing" data-nxp aria-labelledby="nxp-title">
  <div class="nxp-wrap">

    <!-- Top Line / Section Tag -->
    <div class="nxp-topline">
      <span>05 / FAIR VENTURE PRICING</span>
      <span>100% DIRECT CAPITAL · ZERO COMMISSIONS · FULL TRANSPARENCY</span>
    </div>

    <!-- Header Section -->
    <header class="nxp-header">
      <!-- Top Indicator Badge -->
      <div class="nxp-badge-wrap">
        <span class="nxp-badge">
          <span class="nxp-badge-dot" aria-hidden="true"></span>
          Direct Capital · 0% Success Fee
        </span>
      </div>

      <!-- Main Headline: Distinct, Premium & Clear -->
      <h2 id="nxp-title">
        Keep 100% of your round.<br class="nxp-br-desktop">
        Direct access at <span class="nxp-highlight">a flat monthly seat.</span>
      </h2>

      <!-- Subtitle Description: Fast & Scannable -->
     
    </header>

    <!-- 2 Types of Pricing Cards Grid -->
    <div class="nxp-grid">

      <!-- ============================================================ -->
      <!-- CARD 01: FOR BUILDERS & TEAMS (FOUNDER BASIC PLAN) -->
      <!-- ============================================================ -->
      <article class="nxp-card nxp-card-founder" data-plan="founder">
        <!-- Floating Role Anchor Pill on Top Edge -->
        <div class="nxp-card-badge nxp-badge-navy">
          FOR BUILDERS &amp; TEAMS
        </div>

        <div class="nxp-card-inner">
          <!-- Card Header: Icon + Titles -->
          <div class="nxp-card-head">
            <div class="nxp-icon-box nxp-icon-founder" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z">
                </path>
                <path
                  d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z">
                </path>
                <path d="M9 12H4s.55-3.03 2-4.5c1.62-1.63 5-2 5-2"></path>
                <path d="M15 9v5s3.03-.55 4.5-2c1.63-1.62 2-5 2-5"></path>
              </svg>
            </div>
            <div class="nxp-head-meta">
              <h3 class="nxp-plan-name">Founder Basic Plan</h3>
              <p class="nxp-plan-desc">For early ideas, startups, and growing projects</p>
            </div>
          </div>

          <!-- Price & Rate Pill Row -->
          <div class="nxp-price-row">
            <div class="nxp-price-display">
              <span class="nxp-currency">₹</span>
              <span class="nxp-amount">3,500</span>
              <span class="nxp-interval">/ month</span>
            </div>
            <div class="nxp-rate-pill nxp-pill-emerald">
              0% Success Fee
            </div>
          </div>

          <!-- Feature Bullets (Checkmarks) — Concise & Scannable -->
          <ul class="nxp-features" aria-label="Founder Basic Plan features">
            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Live Project Showcase</strong> — Open discovery for verified backers
              </div>
            </li>

            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Verified Founder Badge</strong> — Fast legal &amp; entity trust checks
              </div>
            </li>

            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Direct Inquiries</strong> — Unfiltered conversations with angels
              </div>
            </li>

            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Protected Data Room</strong> — Share decks &amp; metrics under NDA
              </div>
            </li>
          </ul>

          <!-- CTA Button -->
          <div class="nxp-action-wrap">
            <a href="<?= $nxpEscape($nxpRoute('auth/register.php?role=founder&plan=basic')) ?>"
              class="nxp-btn nxp-btn-solid">
              <span>Get Started as Founder</span>
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </a>
            <p class="nxp-microcopy font-mono">Cancel anytime · No lock-in</p>
          </div>
        </div>
      </article>

      <!-- ============================================================ -->
      <!-- CARD 02: FOR BACKERS & ANGELS (INVESTOR PASS) -->
      <!-- ============================================================ -->
      <article class="nxp-card nxp-card-investor" data-plan="investor">
        <!-- Floating Role Anchor Pill on Top Edge -->
        <div class="nxp-card-badge nxp-badge-slate">
          FOR BACKERS &amp; ANGELS
        </div>

        <div class="nxp-card-inner">
          <!-- Card Header: Icon + Titles -->
          <div class="nxp-card-head">
            <div class="nxp-icon-box nxp-icon-investor" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
              </svg>
            </div>
            <div class="nxp-head-meta">
              <h3 class="nxp-plan-name">Investor Pass</h3>
              <p class="nxp-plan-desc">For angels, syndicates, and active allocators</p>
            </div>
          </div>

          <!-- Price & Rate Pill Row -->
          <div class="nxp-price-row">
            <div class="nxp-price-display">
              <span class="nxp-currency">₹</span>
              <span class="nxp-amount">2,500</span>
              <span class="nxp-interval">/ month</span>
            </div>
            <div class="nxp-rate-pill nxp-pill-blue">
              Direct Allocations
            </div>
          </div>

          <!-- Feature Bullets (Checkmarks) — Concise & Scannable -->
          <ul class="nxp-features" aria-label="Investor Pass features">
            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Full Deal Room Access</strong> — Browse vetted startups across all stages
              </div>
            </li>

            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Audited Numbers</strong> — Direct visibility into revenue &amp; cap tables
              </div>
            </li>

            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Direct Founder Chat</strong> — Instant private channels &amp; introductions
              </div>
            </li>

            <li>
              <span class="nxp-check" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
              </span>
              <div class="nxp-feat-text">
                <strong>Escrow Custody</strong> — Safe transaction routing &amp; share allocations
              </div>
            </li>
          </ul>

          <!-- CTA Button -->
          <div class="nxp-action-wrap">
            <a href="<?= $nxpEscape($nxpRoute('auth/register.php?role=investor&plan=pass')) ?>"
              class="nxp-btn nxp-btn-outline">
              <span>Get Started as Investor</span>
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M4 10h12m-5-5 5 5-5 5" />
              </svg>
            </a>
            <p class="nxp-microcopy font-mono">SEBI accredited badge · Deal alerts</p>
          </div>
        </div>
      </article>

    </div>

    <!-- Bottom Trust Guarantee Bar -->
    <div class="nxp-guarantee-bar">
      <div class="nxp-guar-content">
        <div class="nxp-guar-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            <path d="m9 12 2 2 4-4"></path>
          </svg>
        </div>
        <div class="nxp-guar-text">
          <strong>No Hidden Deductions:</strong> 100% of the capital you raise stays in your company.
        </div>
      </div>
      <div class="nxp-guar-action">
        <span class="nxp-guar-chip font-mono">Cancel Anytime in 1-Click</span>
      </div>
    </div>

  </div>
</section>

<!-- ===================================================================== -->
<!-- STYLES (Self-Contained, Pristine NEXORA Standard)                     -->
<!-- ===================================================================== -->
<style>
  .nxp {
    --nxp-ink: #0F172A;
    --nxp-navy: #182435;
    --nxp-muted: #5E6D82;
    --nxp-blue: #2563EB;
    --nxp-blue-dark: #1D4ED8;
    --nxp-line: #E2E8F0;
    --nxp-bg-subtle: #F8FAFC;

    position: relative;
    background-color: #F8FAFC;
    background-image:
      radial-gradient(at 15% 15%, rgba(219, 234, 254, 0.45) 0px, transparent 50%),
      radial-gradient(at 85% 10%, rgba(224, 231, 255, 0.4) 0px, transparent 45%),
      radial-gradient(at 50% 90%, rgba(241, 245, 249, 0.8) 0px, transparent 55%);
    color: var(--nxp-ink);
    padding: 85px 0 65px;
    font-family: 'Satoshi', Inter, -apple-system, BlinkMacSystemFont, sans-serif;
    border-bottom: 1px solid var(--nxp-line);
    -webkit-font-smoothing: antialiased;
  }

  .nxp *,
  .nxp *::before,
  .nxp *::after {
    box-sizing: border-box;
  }

  .nxp p,
  .nxp h2,
  .nxp h3,
  .nxp h4 {
    margin: 0;
  }

  .nxp a {
    color: inherit;
    text-decoration: none;
  }

  .nxp svg {
    display: block;
    flex-shrink: 0;
  }

  .nxp button {
    font: inherit;
    cursor: pointer;
  }

  .nxp-wrap {
    width: min(1200px, calc(100% - 64px));
    margin-inline: auto;
  }

  /* ── Section Topline (04 / FAIR VENTURE PRICING) ── */
  .nxp-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--nxp-line);
    font-size: 12px;
    letter-spacing: .12em;
    line-height: 1.6;
    color: #64748B;
    font-weight: 600;
  }

  .nxp-topline>span:first-child {
    color: var(--nxp-blue);
    font-weight: 700;
  }

  /* ── Section Header ── */
  .nxp-header {
    text-align: center;
    padding: 42px 0 46px;
    max-width: 860px;
    margin-inline: auto;
  }

  .nxp-badge-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
  }

  .nxp-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid #CBD5E1;
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
  }

  .nxp-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10B981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
  }

  .nxp-header h2 {
    font-size: clamp(34px, 4vw, 52px);
    font-weight: 700;
    line-height: 1.16;
    letter-spacing: -0.04em;
    color: #0F172A;
  }

  .nxp-highlight {
    color: #1E40AF;
    font-weight: 750;
    position: relative;
    display: inline-block;
  }

  .nxp-lead {
    font-size: 16px;
    line-height: 1.7;
    color: var(--nxp-muted);
    max-width: 660px;
    margin: 18px auto 0;
    font-weight: 450;
  }

  /* ── The 2 Pricing Cards Grid ── */
  .nxp-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
    margin-top: 15px;
  }

  .nxp-card {
    position: relative;
    background: #FFFFFF;
    border-radius: 20px;
    border: 1.5px solid #E2E8F0;
    box-shadow: 0 14px 34px -10px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.3s ease;
  }

  .nxp-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.12), 0 8px 16px -4px rgba(37, 99, 235, 0.06);
    border-color: #CBD5E1;
  }

  /* Floating Anchor Badge on Top Right */
  .nxp-card-badge {
    position: absolute;
    top: -13px;
    right: 28px;
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    z-index: 5;
    box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
  }

  .nxp-badge-navy {
    background: #0F172A;
    color: #FFFFFF;
    border: 1px solid #1E293B;
  }

  .nxp-badge-slate {
    background: #F1F5F9;
    color: #475569;
    border: 1px solid #CBD5E1;
  }

  /* Card Content Inner */
  .nxp-card-inner {
    padding: 38px 36px 34px;
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  /* Head: Icon + Titles */
  .nxp-card-head {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 22px;
  }

  .nxp-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
  }

  .nxp-icon-founder {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #1D4ED8;
  }

  .nxp-icon-investor {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #047857;
  }

  .nxp-icon-box svg {
    width: 22px;
    height: 22px;
  }

  .nxp-head-meta {
    min-width: 0;
  }

  .nxp-plan-name {
    font-size: 21px;
    font-weight: 700;
    color: #0F172A;
    letter-spacing: -0.02em;
    line-height: 1.25;
  }

  .nxp-plan-desc {
    font-size: 13.5px;
    color: #64748B;
    margin-top: 4px;
    line-height: 1.4;
  }

  /* Price Row */
  .nxp-price-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 24px;
    border-bottom: 1px solid #F1F5F9;
    margin-bottom: 24px;
  }

  .nxp-price-display {
    display: flex;
    align-items: baseline;
    color: #0F172A;
  }

  .nxp-currency {
    font-size: 26px;
    font-weight: 700;
    margin-right: 2px;
  }

  .nxp-amount {
    font-size: 40px;
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1;
  }

  .nxp-interval {
    font-size: 14.5px;
    font-weight: 500;
    color: #64748B;
    margin-left: 6px;
  }

  /* Rate Pill */
  .nxp-rate-pill {
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.03em;
    padding: 5px 12px;
    border-radius: 6px;
    line-height: 1.3;
    white-space: nowrap;
  }

  .nxp-pill-emerald {
    background: #ECFDF5;
    color: #047857;
    border: 1px solid #A7F3D0;
  }

  .nxp-pill-blue {
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #BFDBFE;
  }

  /* Feature List */
  .nxp-features {
    list-style: none;
    padding: 0;
    margin: 0 0 32px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    flex-grow: 1;
  }

  .nxp-features li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 13.5px;
    line-height: 1.6;
    color: #334155;
  }

  .nxp-check {
    width: 19px;
    height: 19px;
    border-radius: 50%;
    background: #EFF6FF;
    color: #2563EB;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .nxp-check svg {
    width: 14px;
    height: 14px;
  }

  .nxp-feat-text strong {
    color: #0F172A;
    font-weight: 650;
  }

  /* Action Area */
  .nxp-action-wrap {
    margin-top: auto;
  }

  .nxp-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 22px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 650;
    text-align: center;
    transition: all 0.22s ease;
  }

  .nxp-btn svg {
    width: 16px;
    height: 16px;
    transition: transform 0.2s ease;
  }

  .nxp-btn:hover svg {
    transform: translateX(3px);
  }

  .nxp-btn-solid {
    background: #1D4ED8;
    color: #FFFFFF;
    border: 1px solid #1D4ED8;
    box-shadow: 0 4px 14px rgba(29, 78, 216, 0.25);
  }

  .nxp-btn-solid:hover {
    background: #1E40AF;
    box-shadow: 0 6px 20px rgba(29, 78, 216, 0.35);
    transform: translateY(-1px);
  }

  .nxp-btn-outline {
    background: #FFFFFF;
    color: #0F172A;
    border: 1.5px solid #CBD5E1;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
  }

  .nxp-btn-outline:hover {
    background: #F8FAFC;
    border-color: #0F172A;
    transform: translateY(-1px);
  }

  .nxp-microcopy {
    text-align: center;
    font-size: 11.5px;
    color: #64748B;
    margin-top: 12px;
    letter-spacing: 0.02em;
  }

  /* ── Guarantee Bar (Trust Banner) ── */
  .nxp-guarantee-bar {
    margin-top: 36px;
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
  }

  .nxp-guar-content {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .nxp-guar-icon {
    width: 28px;
    height: 28px;
    color: #1D4ED8;
    flex-shrink: 0;
  }

  .nxp-guar-icon svg {
    width: 100%;
    height: 100%;
  }

  .nxp-guar-text {
    font-size: 13.5px;
    line-height: 1.5;
    color: #334155;
  }

  .nxp-guar-text strong {
    color: #0F172A;
    font-weight: 700;
  }

  .nxp-guar-chip {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 6px;
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
  }

  /* ── Responsive Queries ── */
  @media (max-width: 960px) {
    .nxp-grid {
      grid-template-columns: 1fr;
      gap: 34px;
      max-width: 580px;
      margin-inline: auto;
    }

    .nxp-header h2 {
      font-size: 36px;
    }

    .nxp-br-desktop {
      display: none;
    }
  }

  @media (max-width: 680px) {
    .nxp {
      padding: 60px 0 45px;
    }

    .nxp-wrap {
      width: calc(100% - 36px);
    }

    .nxp-header h2 {
      font-size: 28px;
    }

    .nxp-lead {
      font-size: 14.5px;
    }

    .nxp-card-inner {
      padding: 30px 22px 24px;
    }

    .nxp-card-badge {
      right: 18px;
      font-size: 9.5px;
      padding: 4px 10px;
    }

    .nxp-plan-name {
      font-size: 18px;
    }

    .nxp-amount {
      font-size: 32px;
    }

    .nxp-currency {
      font-size: 22px;
    }

    .nxp-rate-pill {
      font-size: 10.5px;
      padding: 4px 8px;
    }

    .nxp-guarantee-bar {
      flex-direction: column;
      align-items: flex-start;
      gap: 14px;
      padding: 16px 18px;
    }

    .nxp-guar-chip {
      font-size: 11px;
    }
  }
</style>