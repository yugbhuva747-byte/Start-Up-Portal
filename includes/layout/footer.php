<?php
/**
 * ============================================================================
 * include/layout/footer.php
 * ----------------------------------------------------------------------------
 * High-End Institutional Footer for NEXORA (Startup × Investor Ecosystem)
 * Features:
 *   - Pre-Footer Action Banner with Live Dealflow Metrics & Fast CTAs
 *   - 4-Column Structured Directory with Fast Lane Portals
 *   - Weekly Dealflow Dispatch (Newsletter Subscription with Instant JS)
 *   - Institutional Trust Badges (SOC2, 256-Bit SSL, SEBI Compliance)
 *   - Responsive Floating Chat Widget with Typing Animation & Contextual AI
 * Closes <body> and <html> tags.
 * ============================================================================
 */
?>

<!-- ==========================================================================
     PRE-FOOTER CTA: ACTION & DEALFLOW BANNER
     ========================================================================== -->
<section class="relative z-20 bg-[#FAF5FF] pt-16 pb-12 overflow-hidden border-t border-purple-100" aria-label="Join Platform">
  <!-- Subtle Background Orbs -->
  <div class="pointer-events-none absolute top-0 left-1/3 w-[600px] h-[300px] bg-purple-300/25 rounded-full blur-[130px]"></div>
  <div class="pointer-events-none absolute bottom-0 right-1/4 w-[400px] h-[250px] bg-pink-300/20 rounded-full blur-[110px]"></div>

  <div class="relative max-w-[1440px] mx-auto px-6 lg:px-14">
    <div class="rounded-3xl bg-gradient-to-br from-white/95 via-purple-50/70 to-pink-50/50 border border-purple-200/80 p-8 sm:p-12 lg:p-14 shadow-[0_20px_60px_-15px_rgba(124,58,237,0.12)] relative overflow-hidden backdrop-blur-xl">
      
      <!-- Top Decorative Accent Line -->
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 via-pink-500 to-amber-400"></div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
        <!-- Text & Tagline -->
        <div class="lg:col-span-7">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-purple-100/90 border border-purple-200/80 text-[11.5px] font-mono font-bold uppercase tracking-wider text-purple-900 mb-4 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-gradient-to-r from-purple-600 to-pink-500 animate-pulse"></span>
            ACTIVE DEALFLOW ALLOCATION
          </div>
          <h2 class="text-[30px] sm:text-[40px] lg:text-[44px] font-extrabold text-slate-900 tracking-[-0.03em] leading-[1.12] mb-4 font-sans">
            Build What’s Next in <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 bg-clip-text text-transparent">Indian Private Capital.</span>
          </h2>
          <p class="text-[15.5px] sm:text-[17px] text-slate-600 leading-relaxed max-w-[560px]">
            Whether you are evaluating audited seed rounds or scaling a high-growth company, NEXORA eliminates brokers and connects you directly with decision-makers.
          </p>
        </div>

        <!-- Fast Action Buttons -->
        <div class="lg:col-span-5 flex flex-col sm:flex-row lg:flex-col xl:flex-row gap-4 justify-start lg:justify-end">
          <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="inline-flex items-center justify-center gap-2.5 h-[52px] px-7 rounded-2xl bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white font-semibold text-[15px] shadow-lg shadow-pink-500/25 transition-all hover:scale-[1.02] active:scale-[0.98]">
            <span>Investor Terminal</span>
            <span class="text-[17px]">&rarr;</span>
          </a>

          <a href="#nx-portal-register" class="inline-flex items-center justify-center gap-2.5 h-[52px] px-7 rounded-2xl bg-white hover:bg-purple-50/80 border border-purple-200 text-purple-800 font-semibold text-[15px] shadow-xs transition-all hover:scale-[1.02] active:scale-[0.98]">
            <span>Apply as Founder</span>
            <span class="text-[17px] text-purple-500">&rarr;</span>
          </a>
        </div>
      </div>

      <!-- Live Trust Metrics Strip -->
      <div class="mt-10 pt-8 border-t border-purple-200/70 grid grid-cols-2 sm:grid-cols-4 gap-6 text-center sm:text-left">
        <div>
          <div class="text-[22px] sm:text-[26px] font-extrabold text-slate-900 tracking-tight">₹840Cr+</div>
          <div class="text-[12px] font-medium text-slate-500 mt-0.5">Capital Facilitated</div>
        </div>
        <div>
          <div class="text-[22px] sm:text-[26px] font-extrabold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent tracking-tight">250+</div>
          <div class="text-[12px] font-medium text-slate-500 mt-0.5">Vetted Startups</div>
        </div>
        <div>
          <div class="text-[22px] sm:text-[26px] font-extrabold text-emerald-600 tracking-tight">100%</div>
          <div class="text-[12px] font-medium text-slate-500 mt-0.5">Audited MIS &amp; Cap Tables</div>
        </div>
        <div>
          <div class="text-[22px] sm:text-[26px] font-extrabold text-slate-900 tracking-tight">0%</div>
          <div class="text-[12px] font-medium text-slate-500 mt-0.5">Broker Intermediaries</div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ==========================================================================
     MAIN MASTER FOOTER
     ========================================================================== -->
<footer class="relative z-20 bg-[#0C0720] text-white pt-16 pb-12 overflow-hidden border-t border-purple-900/40" role="contentinfo">

  <!-- Ambient Glow -->
  <div class="pointer-events-none absolute -top-40 left-1/4 w-[500px] h-[500px] bg-purple-600/15 rounded-full blur-[140px]"></div>
  <div class="pointer-events-none absolute -bottom-20 right-10 w-[400px] h-[400px] bg-pink-600/10 rounded-full blur-[130px]"></div>

  <div class="relative max-w-[1440px] mx-auto px-6 lg:px-14">

    <!-- Top Columns Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 lg:gap-10 pb-14 border-b border-purple-900/30">

      <!-- Brand & Summary Column (Col 4) -->
      <div class="lg:col-span-4">
        <a href="<?= function_exists('url') ? url('index.php') : 'index.php' ?>" class="inline-flex items-center gap-3 mb-5 group" aria-label="NEXORA Home">
          <span class="font-extrabold text-[30px] tracking-[-0.04em] leading-none text-white font-sans">
            NEXORA
          </span>
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-[0_0_14px_3px_rgba(52,211,153,0.5)] transition-transform duration-300 group-hover:scale-125"></span>
        </a>

        <p class="text-[14.5px] leading-relaxed text-purple-200/75 max-w-[360px] mb-6 font-normal">
          India&rsquo;s premier institutional dealflow platform bridging high-growth innovators with verified family offices, angel syndicates, and micro-VCs.
        </p>

        <!-- Location & Status Badge -->
        <div class="space-y-2.5 text-[12px] font-mono text-purple-300/60">
          <div class="flex items-center gap-2 text-purple-200/80">
            <span class="text-pink-400">●</span>
            <span>HUBS: BENGALURU &middot; MUMBAI &middot; DELHI NCR &middot; SINGAPORE</span>
          </div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-purple-950/60 border border-purple-500/20 text-[11px] text-emerald-400">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>ALL SYSTEMS OPERATIONAL &middot; 99.98% UPTIME</span>
          </div>
        </div>
      </div>

      <!-- Quick Directory Navigation (Col 8) -->
      <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-3 gap-8 lg:gap-6">

        <!-- Col 1: FAST LANE PORTALS -->
        <div>
          <span class="flex items-center gap-2 text-[12px] font-mono font-bold tracking-[0.15em] uppercase text-white mb-5">
            <span class="w-3.5 h-px bg-gradient-to-r from-purple-500 to-pink-500"></span>
            Direct Portals
          </span>
          <ul class="space-y-3 text-[14px]" role="list">
            <li>
              <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="text-purple-200/70 hover:text-white transition-colors flex items-center gap-1.5 group">
                <span class="text-pink-400 opacity-80 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">&rarr;</span>
                <span>Investor Login</span>
              </a>
            </li>
            <li>
              <a href="<?= function_exists('url') ? url('auth/login.php') : 'auth/login.php' ?>" class="text-purple-200/70 hover:text-white transition-colors flex items-center gap-1.5 group">
                <span class="text-pink-400 opacity-80 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">&rarr;</span>
                <span>Founder Login</span>
              </a>
            </li>
            <li>
              <a href="#nx-portal-register" class="text-purple-200/70 hover:text-white transition-colors flex items-center gap-1.5 group">
                <span class="text-pink-400 opacity-80 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">&rarr;</span>
                <span>Join Investor Network</span>
              </a>
            </li>
            <li>
              <a href="#nx-portal-register" class="text-purple-200/70 hover:text-white transition-colors flex items-center gap-1.5 group">
                <span class="text-pink-400 opacity-80 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">&rarr;</span>
                <span>Apply for Capital</span>
              </a>
            </li>
            <li>
              <a href="#nx-opportunities" class="text-purple-200/70 hover:text-white transition-colors flex items-center gap-1.5 group">
                <span class="text-pink-400 opacity-80 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all">&rarr;</span>
                <span>Live Deal Pipeline</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Col 2: PLATFORM & DILIGENCE -->
        <div>
          <span class="flex items-center gap-2 text-[12px] font-mono font-bold tracking-[0.15em] uppercase text-white mb-5">
            <span class="w-3.5 h-px bg-gradient-to-r from-purple-500 to-pink-500"></span>
            Platform &amp; Diligence
          </span>
          <ul class="space-y-3 text-[14px]" role="list">
            <li><a href="#nx-ecosystem" class="text-purple-200/70 hover:text-white transition-colors">How It Works</a></li>
            <li><a href="#nx-pricing" class="text-purple-200/70 hover:text-white transition-colors">Venture Pricing Plans</a></li>
            <li><a href="#nx-investors" class="text-purple-200/70 hover:text-white transition-colors">Vetted Cap Tables</a></li>
            <li><a href="#nx-startups-raise" class="text-purple-200/70 hover:text-white transition-colors">Founder Raise Dossier</a></li>
            <li><a href="#nx-learning" class="text-purple-200/70 hover:text-white transition-colors">Regulatory Framework</a></li>
            <li><a href="#nx-faq" class="text-purple-200/70 hover:text-white transition-colors">Frequently Asked Questions</a></li>
          </ul>
        </div>

        <!-- Col 3: DEALFLOW BRIEFING (NEWSLETTER) -->
        <div>
          <span class="flex items-center gap-2 text-[12px] font-mono font-bold tracking-[0.15em] uppercase text-white mb-5">
            <span class="w-3.5 h-px bg-gradient-to-r from-purple-500 to-pink-500"></span>
            Dealflow Dispatch
          </span>
          <p class="text-[13px] text-purple-200/75 leading-relaxed mb-3.5">
            Curated weekly breakdown of top vetted Indian rounds, revenue metrics, and valuation briefs.
          </p>

          <form id="footer-newsletter-form" class="space-y-2.5" novalidate>
            <div class="relative flex items-center">
              <input 
                type="email" 
                id="footer-email" 
                required 
                placeholder="allocator@fund.com" 
                class="w-full h-[44px] pl-3.5 pr-11 rounded-xl bg-purple-950/40 border border-purple-500/25 text-[13.5px] text-white placeholder-purple-300/40 focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/25 transition-all"
              >
              <button 
                type="submit" 
                id="footer-email-btn"
                aria-label="Subscribe to dealflow dispatch" 
                class="absolute right-1.5 w-8 h-8 rounded-lg bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white flex items-center justify-center transition-transform hover:scale-105 active:scale-95 shadow-md shadow-pink-500/20"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
              </button>
            </div>
            <div id="footer-newsletter-msg" class="hidden text-[11.5px] text-emerald-400 font-medium">
              ✓ Subscribed! You will receive our next Tuesday Deal Brief.
            </div>
            <p class="text-[11px] text-purple-300/50 font-mono">
              Zero spam. Unsubscribe anytime.
            </p>
          </form>
        </div>

      </div>

    </div>

    <!-- Institutional Compliance & Trust Badges Strip -->
    <div class="py-6 border-b border-purple-900/30 flex flex-wrap items-center justify-between gap-4 text-[12px] font-mono text-purple-300/60">
      <div class="flex flex-wrap items-center gap-6">
        <span class="flex items-center gap-1.5 text-purple-200/80">
          <svg class="w-4 h-4 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
          256-BIT SSL ENCRYPTION
        </span>
        <span class="flex items-center gap-1.5 text-purple-200/80">
          <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg>
          SOC 2 TYPE II COMPLIANT
        </span>
        <span class="flex items-center gap-1.5 text-purple-200/80">
          <svg class="w-4 h-4 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          ZERO COMMISSION DESK
        </span>
      </div>

      <div class="text-purple-300/60">
        Direct allocations &middot; SEBI / AIF Ecosystem Compliant
      </div>
    </div>

    <!-- Bottom Bar & Legal Notice -->
    <div class="pt-8 flex flex-col md:flex-row md:items-center justify-between gap-6 text-[12.5px] text-purple-300/60">
      <div class="font-medium text-purple-200/75">
        &copy; <?= date('Y') ?> NEXORA Technologies India Pvt. Ltd. All rights reserved.
      </div>

      <!-- Legal Links -->
      <div class="flex items-center gap-6 text-[12.5px]">
        <a href="/privacy" class="hover:text-white transition-colors underline-offset-4 hover:underline">Privacy Policy</a>
        <a href="/terms" class="hover:text-white transition-colors underline-offset-4 hover:underline">Terms of Service</a>
        <a href="/risk-disclosure" class="hover:text-white transition-colors underline-offset-4 hover:underline">Risk Disclosure</a>
        <a href="/security" class="hover:text-white transition-colors underline-offset-4 hover:underline">Security</a>
      </div>
    </div>

    <!-- Risk Disclaimer -->
    <div class="mt-4 pt-4 border-t border-purple-900/20 text-[11px] leading-relaxed text-purple-300/50 max-w-[1000px]">
      Investment opportunities in early-stage ventures and private securities involve substantial risk, including illiquidity and partial or total loss of capital. NEXORA is a technology platform connecting accredited allocators directly with founders and does not offer financial advice, broker-dealer guarantees, or investment solicitations.
    </div>

  </div>
</footer>

<!-- ==========================================================================
     FLOATING CHAT & SUPPORT WIDGET
     ========================================================================== -->
<style>
  #chatWidgetWrap {
    pointer-events: none !important;
  }
  #chatPopBtn {
    pointer-events: auto !important;
  }
  #chatPanel {
    transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #chatPanel.chat-hidden {
    display: none !important;
    opacity: 0 !important;
    transform: translateY(24px) scale(0.95);
    pointer-events: none !important;
  }
  #chatPanel.chat-visible {
    display: flex !important;
    opacity: 1 !important;
    transform: translateY(0) scale(1);
    pointer-events: auto !important;
  }
  #chatTooltip {
    pointer-events: none !important;
  }
  #chatMessages::-webkit-scrollbar {
    width: 4px;
  }
  #chatMessages::-webkit-scrollbar-track {
    background: transparent;
  }
  #chatMessages::-webkit-scrollbar-thumb {
    background: #d8b4fe;
    border-radius: 99px;
  }
  .chat-msg-bot {
    background: #FFFFFF;
    color: #1e293b;
    border: 1px solid #ede9fe;
    border-radius: 4px 18px 18px 18px;
    align-self: flex-start;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.05);
  }
  .chat-msg-user {
    background: linear-gradient(135deg, #7C3AED 0%, #DB2777 50%, #F59E0B 100%);
    color: #FFFFFF;
    border-radius: 18px 18px 4px 18px;
    align-self: flex-end;
    box-shadow: 0 4px 14px rgba(219, 39, 119, 0.25);
  }
  @keyframes msgSlideIn {
    from {
      opacity: 0;
      transform: translateY(8px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  .chat-msg-anim {
    animation: msgSlideIn 0.28s ease forwards;
  }
  .typing-dot {
    animation: typingBounce 1.2s infinite ease-in-out;
  }
  .typing-dot:nth-child(2) {
    animation-delay: 0.15s;
  }
  .typing-dot:nth-child(3) {
    animation-delay: 0.3s;
  }
  @keyframes typingBounce {
    0%, 60%, 100% {
      transform: translateY(0);
      opacity: 0.35;
    }
    30% {
      transform: translateY(-4px);
      opacity: 1;
    }
  }
</style>

<div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3 pointer-events-none" id="chatWidgetWrap">

  <!-- Chat Panel -->
  <div id="chatPanel"
    class="chat-hidden w-[370px] max-sm:w-[calc(100vw-2rem)] max-sm:right-0 max-sm:bottom-0 max-sm:max-h-[85vh] h-[500px] bg-white rounded-3xl max-sm:rounded-b-none shadow-2xl shadow-purple-950/20 border border-purple-100 flex flex-col overflow-hidden">

    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-700 via-pink-600 to-amber-500 px-5 py-4 flex items-center gap-3 shrink-0 shadow-sm">
      <div class="w-10 h-10 rounded-full bg-white/20 border border-white/30 flex items-center justify-center font-bold text-white text-[14px]">
        NX
      </div>
      <div class="flex-1">
        <div class="text-white font-bold text-[15px] leading-tight">NEXORA Support Desk</div>
        <div class="flex items-center gap-1.5 text-purple-100 text-[11.5px] mt-0.5">
          <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block shadow-[0_0_8px_#34d399]"></span> 
          <span>Active &middot; Instant Assistance</span>
        </div>
      </div>
      <button id="chatCloseBtn" class="text-white/80 hover:text-white transition-colors p-1.5 rounded-full hover:bg-white/10" aria-label="Close chat">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Messages Area -->
    <div id="chatMessages" class="flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-3 bg-purple-50/30">
      <!-- Welcome messages added via JS -->
    </div>

    <!-- Quick Replies -->
    <div id="chatQuickReplies" class="px-4 pb-2.5 flex flex-wrap gap-2 shrink-0 bg-purple-50/30">
      <button
        class="quick-reply text-[12px] font-semibold px-3 py-1.5 rounded-full border border-purple-200 text-purple-800 bg-white hover:bg-purple-100/70 transition-colors shadow-2xs"
        data-msg="I have a question about investing">💰 Investing Query</button>
      <button
        class="quick-reply text-[12px] font-semibold px-3 py-1.5 rounded-full border border-purple-200 text-purple-800 bg-white hover:bg-purple-100/70 transition-colors shadow-2xs"
        data-msg="I want to list my startup">🚀 List Startup</button>
      <button
        class="quick-reply text-[12px] font-semibold px-3 py-1.5 rounded-full border border-purple-200 text-purple-800 bg-white hover:bg-purple-100/70 transition-colors shadow-2xs"
        data-msg="I need help with my account">🔑 Account Access</button>
    </div>

    <!-- Input Area -->
    <div class="px-4 py-3 border-t border-purple-100 bg-white flex items-center gap-2 shrink-0">
      <input id="chatInput" type="text" placeholder="Type your message..."
        class="flex-1 bg-purple-50/50 text-slate-800 text-[14px] rounded-full px-4 py-2.5 outline-none focus:ring-2 focus:ring-purple-500 border border-transparent focus:border-purple-300 transition-all placeholder:text-slate-400" />
      <button id="chatSendBtn"
        class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-md shadow-pink-500/25 shrink-0"
        aria-label="Send message">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </div>

  <!-- Tooltip -->
  <div id="chatTooltip"
    class="bg-white text-slate-800 text-[13px] font-medium px-4 py-2.5 rounded-2xl shadow-xl shadow-purple-950/10 border border-purple-100 transition-all duration-300 opacity-0 translate-y-2 pointer-events-none max-w-[220px]">
    💬 Have questions?<br><span class="text-[11.5px] text-purple-600/70 font-normal">Chat with our support team!</span>
  </div>

  <!-- Floating Button -->
  <button id="chatPopBtn"
    class="group relative w-14 h-14 rounded-full bg-gradient-to-br from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white shadow-xl shadow-purple-600/35 flex items-center justify-center transition-all duration-300 hover:scale-105 active:scale-95"
    aria-label="Open chat">
    <span id="chatPulse" class="absolute inset-0 rounded-full bg-pink-400 opacity-40 animate-ping"></span>
    <!-- Chat icon -->
    <svg id="chatIconOpen" xmlns="http://www.w3.org/2000/svg"
      class="w-6 h-6 relative z-10 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24"
      stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round"
        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    <!-- Close icon (hidden) -->
    <svg id="chatIconClose" xmlns="http://www.w3.org/2000/svg"
      class="w-6 h-6 relative z-10 hidden transition-transform duration-300" fill="none" viewBox="0 0 24 24"
      stroke="currentColor" stroke-width="2.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
  </button>
</div>

<!-- ==========================================================================
     FOOTER INTERACTION SCRIPTS
     ========================================================================== -->
<script>
  (function () {
    'use strict';

    // ── Newsletter Subscription Micro-interaction ──────────────────────────
    const newsletterForm = document.getElementById('footer-newsletter-form');
    const newsletterInput = document.getElementById('footer-email');
    const newsletterMsg = document.getElementById('footer-newsletter-msg');
    const newsletterBtn = document.getElementById('footer-email-btn');

    if (newsletterForm && newsletterInput && newsletterMsg) {
      newsletterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = newsletterInput.value.trim();
        if (!email || !email.includes('@')) {
          newsletterInput.focus();
          return;
        }

        newsletterBtn.disabled = true;
        newsletterBtn.innerHTML = '<span class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>';

        setTimeout(function () {
          newsletterBtn.disabled = false;
          newsletterBtn.innerHTML = '✓';
          newsletterMsg.classList.remove('hidden');
          newsletterInput.value = '';
          setTimeout(function () {
            newsletterBtn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>';
          }, 4000);
        }, 500);
      });
    }

    // ── Floating Chat Widget ───────────────────────────────────────────────
    const panel = document.getElementById('chatPanel');
    const popBtn = document.getElementById('chatPopBtn');
    const closeBtn = document.getElementById('chatCloseBtn');
    const tooltip = document.getElementById('chatTooltip');
    const pulse = document.getElementById('chatPulse');
    const iconOpen = document.getElementById('chatIconOpen');
    const iconClose = document.getElementById('chatIconClose');
    const msgArea = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const quickReplies = document.getElementById('chatQuickReplies');
    let isOpen = false;
    let welcomed = false;

    function addMsg(text, type, delay) {
      return new Promise(function (resolve) {
        setTimeout(function () {
          const div = document.createElement('div');
          div.className = (type === 'bot' ? 'chat-msg-bot' : 'chat-msg-user') + ' px-4 py-2.5 text-[13.5px] leading-relaxed max-w-[85%] chat-msg-anim';
          div.textContent = text;
          msgArea.appendChild(div);
          msgArea.scrollTop = msgArea.scrollHeight;
          resolve();
        }, delay || 0);
      });
    }

    function showTyping() {
      const div = document.createElement('div');
      div.id = 'chatTypingIndicator';
      div.className = 'chat-msg-bot px-4 py-3 flex gap-1 items-center max-w-[70px] chat-msg-anim';
      div.innerHTML = '<span class="typing-dot w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span><span class="typing-dot w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span><span class="typing-dot w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span>';
      msgArea.appendChild(div);
      msgArea.scrollTop = msgArea.scrollHeight;
    }

    function hideTyping() {
      const el = document.getElementById('chatTypingIndicator');
      if (el) el.remove();
    }

    async function showWelcome() {
      if (welcomed) return;
      welcomed = true;
      await addMsg('Hello! 👋 Welcome to NEXORA.', 'bot', 250);
      await addMsg('How can we assist you today? Select a quick option below or send your query.', 'bot', 700);
    }

    function openChat() {
      isOpen = true;
      panel.classList.remove('chat-hidden');
      panel.classList.add('chat-visible');
      tooltip.classList.add('opacity-0', 'translate-y-2', 'pointer-events-none');
      tooltip.classList.remove('opacity-100', 'translate-y-0');
      pulse.style.display = 'none';
      iconOpen.classList.add('hidden');
      iconClose.classList.remove('hidden');
      showWelcome();
      setTimeout(function () { input.focus(); }, 400);
    }

    function closeChat() {
      isOpen = false;
      panel.classList.add('chat-hidden');
      panel.classList.remove('chat-visible');
      pulse.style.display = '';
      iconOpen.classList.remove('hidden');
      iconClose.classList.add('hidden');
    }

    if (popBtn) popBtn.addEventListener('click', function () { isOpen ? closeChat() : openChat(); });
    if (closeBtn) closeBtn.addEventListener('click', closeChat);

    function sendMsg() {
      const text = input.value.trim();
      if (!text) return;
      input.value = '';
      addMsg(text, 'user', 0);
      if (quickReplies) quickReplies.style.display = 'none';

      const lower = text.toLowerCase();
      let reply = 'Thank you for reaching out! 🙏 Our team has received your inquiry and will reply shortly. You can also reach us directly at contact@nexora.in.';
      if (lower.includes('invest')) {
        reply = 'Thank you for your investing inquiry! We provide curated, pre-vetted dealflow with verified cap tables. An investment specialist will be in touch shortly.';
      } else if (lower.includes('startup') || lower.includes('list')) {
        reply = 'Excited to hear about your company! You can register directly at home.php#nx-portal-register or email your deck to ventures@nexora.in. Our team reviews all applications within 48 hours.';
      } else if (lower.includes('account') || lower.includes('login') || lower.includes('help')) {
        reply = 'For account verification or access queries, please provide your registered email or reach us at support@nexora.in for immediate assistance.';
      }

      showTyping();
      setTimeout(function () {
        hideTyping();
        addMsg(reply, 'bot', 0);
      }, 850);
    }

    if (sendBtn) sendBtn.addEventListener('click', sendMsg);
    if (input) input.addEventListener('keydown', function (e) { if (e.key === 'Enter') sendMsg(); });

    // Quick replies
    document.querySelectorAll('.quick-reply').forEach(function (btn) {
      btn.addEventListener('click', function () {
        input.value = btn.getAttribute('data-msg');
        sendMsg();
      });
    });

    // Tooltip auto-show
    setTimeout(function () {
      if (!isOpen && tooltip) {
        tooltip.classList.remove('opacity-0', 'translate-y-2');
        tooltip.classList.add('opacity-100', 'translate-y-0');
        setTimeout(function () {
          tooltip.classList.add('opacity-0', 'translate-y-2');
          tooltip.classList.remove('opacity-100', 'translate-y-0');
        }, 5000);
      }
    }, 2000);

    // Hover tooltip
    if (popBtn && tooltip) {
      popBtn.addEventListener('mouseenter', function () {
        if (!isOpen) {
          tooltip.classList.remove('opacity-0', 'translate-y-2');
          tooltip.classList.add('opacity-100', 'translate-y-0');
        }
      });
      popBtn.addEventListener('mouseleave', function () {
        tooltip.classList.add('opacity-0', 'translate-y-2');
        tooltip.classList.remove('opacity-100', 'translate-y-0');
      });
    }
  })();
</script>

</body>
</html>