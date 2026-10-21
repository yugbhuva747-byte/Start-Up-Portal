<?php
/**
 * ============================================================================
 * includes/investor_home/syndicate-cta.php
 * Institutional Syndicate Performance Ledger, Interactive Angel ROI Calculator,
 * FAQ Accordion & Grand Executive Access Desk
 * Unified with NEXORA Royal Blue & Deep Slate brand theme.
 * Focus: High Trust, Institutional Conviction & Frictionless Conversion.
 * ============================================================================
 */
?>
<section class="py-20 md:py-28 bg-white border-t border-slate-200/80 relative overflow-hidden" id="investor-syndicate-desk">

  <div class="max-w-[1440px] mx-auto px-6 lg:px-10">
    <!-- ==================================================================== -->
    <!-- 04. INVESTOR FAQ & ACCORDION SEARCH                                  -->
    <!-- ==================================================================== -->
    <div class="mb-20 space-y-8" id="investor-faq">
      <div class="text-center max-w-2xl mx-auto space-y-3">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200/80 text-blue-700 text-xs font-bold uppercase tracking-wider font-satoshi">
          <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          <span>Investor FAQ &middot; Transparency First</span>
        </div>
        <h3 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-[-0.03em] font-satoshi">
          Frequently Answered Investor Questions
        </h3>
        <p class="text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
          Clear, upfront answers on fees, minimum cheque sizes, compliance, and cap table rights.
        </p>

        <!-- Quick FAQ Search Input -->
        <div class="max-w-md mx-auto pt-2">
          <div class="relative">
            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="faqSearchInput" oninput="filterFaqQuestions()" placeholder="Search questions (fees, SAFE notes, minimums...)"
                   class="w-full pl-10 pr-4 py-2.5 rounded-full bg-white border-2 border-slate-300 text-xs sm:text-sm font-semibold text-slate-900 placeholder:text-slate-500 placeholder:font-medium focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20">
          </div>
        </div>
      </div>

      <!-- Accordion Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-5xl mx-auto" id="faqAccordionContainer">

        <!-- FAQ Item 1 -->
        <div class="faq-item p-6 rounded-2xl bg-white border-2 border-slate-300 hover:border-blue-500 shadow-sm transition-all duration-200">
          <button type="button" onclick="toggleFaqAccordion(this)" class="w-full flex items-center justify-between text-left gap-3.5 focus:outline-none">
            <span class="text-base sm:text-lg font-black text-slate-950 font-satoshi flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-blue-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs">1</span>
              <span>Is there a membership or platform subscription fee?</span>
            </span>
            <svg class="faq-icon w-5 h-5 text-slate-600 shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-answer hidden mt-4 pt-4 border-t-2 border-slate-100 text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
            <strong class="text-slate-950 font-black">Zero Platform Fees for Accredited Investors.</strong> Unlike traditional angel networks that charge ₹50,000–₹1,50,000 annual membership dues, NEXORA is completely free for verified investors. 100% of your cheque goes directly to the portfolio company with zero brokerage or carry fee drag.
          </div>
        </div>

        <!-- FAQ Item 2 -->
        <div class="faq-item p-6 rounded-2xl bg-white border-2 border-slate-300 hover:border-blue-500 shadow-sm transition-all duration-200">
          <button type="button" onclick="toggleFaqAccordion(this)" class="w-full flex items-center justify-between text-left gap-3.5 focus:outline-none">
            <span class="text-base sm:text-lg font-black text-slate-950 font-satoshi flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-indigo-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs">2</span>
              <span>What are the minimum cheque sizes per deal?</span>
            </span>
            <svg class="faq-icon w-5 h-5 text-slate-600 shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-answer hidden mt-4 pt-4 border-t-2 border-slate-100 text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
            <strong class="text-slate-950 font-black">Syndicate tickets start from $2,500 / ₹2 Lakhs to ₹5 Lakhs.</strong> Lead angel and institutional direct rounds range from $25,000 up to $2M+. You can choose to co-invest a micro-allocation alongside lead institutional venture funds or anchor the round.
          </div>
        </div>

        <!-- FAQ Item 3 -->
        <div class="faq-item p-6 rounded-2xl bg-white border-2 border-slate-300 hover:border-blue-500 shadow-sm transition-all duration-200">
          <button type="button" onclick="toggleFaqAccordion(this)" class="w-full flex items-center justify-between text-left gap-3.5 focus:outline-none">
            <span class="text-base sm:text-lg font-black text-slate-950 font-satoshi flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-blue-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs">3</span>
              <span>How are startups screened before entering the dealroom?</span>
            </span>
            <svg class="faq-icon w-5 h-5 text-slate-600 shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-answer hidden mt-4 pt-4 border-t-2 border-slate-100 text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
            Our 4-stage diligence rejects 98.2% of cold applications. We verify MCA and Delaware legal incorporation, independently audit 24-month bank statements against GST/tax filings, confirm founder identity, and enforce Delaware/SEBI SAFE standard cap tables.
          </div>
        </div>

        <!-- FAQ Item 4 -->
        <div class="faq-item p-6 rounded-2xl bg-white border-2 border-slate-300 hover:border-blue-500 shadow-sm transition-all duration-200">
          <button type="button" onclick="toggleFaqAccordion(this)" class="w-full flex items-center justify-between text-left gap-3.5 focus:outline-none">
            <span class="text-base sm:text-lg font-black text-slate-950 font-satoshi flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-slate-900 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs">4</span>
              <span>How is shareholding, tax compliance &amp; governance handled?</span>
            </span>
            <svg class="faq-icon w-5 h-5 text-slate-600 shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-answer hidden mt-4 pt-4 border-t-2 border-slate-100 text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
            Syndicate tickets aggregate under a clean nominee structure to avoid cap table crowding. Investors receive cryptographically signed digital certificates, Section 56 tax documents, and automated quarterly financial updates pushed straight to their terminal.
          </div>
        </div>

        <!-- FAQ Item 5 -->
        <div class="faq-item p-6 rounded-2xl bg-white border-2 border-slate-300 hover:border-blue-500 shadow-sm transition-all duration-200">
          <button type="button" onclick="toggleFaqAccordion(this)" class="w-full flex items-center justify-between text-left gap-3.5 focus:outline-none">
            <span class="text-base sm:text-lg font-black text-slate-950 font-satoshi flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs">5</span>
              <span>What is the exit timeframe and secondary liquidity option?</span>
            </span>
            <svg class="faq-icon w-5 h-5 text-slate-600 shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-answer hidden mt-4 pt-4 border-t-2 border-slate-100 text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
            Early-stage venture rounds typically hold a 3–7 year realization horizon. NEXORA also enables semi-annual private secondary liquidity windows where verified angels can sell allocations to incoming growth-stage institutional funds.
          </div>
        </div>

        <!-- FAQ Item 6 -->
        <div class="faq-item p-6 rounded-2xl bg-white border-2 border-slate-300 hover:border-blue-500 shadow-sm transition-all duration-200">
          <button type="button" onclick="toggleFaqAccordion(this)" class="w-full flex items-center justify-between text-left gap-3.5 focus:outline-none">
            <span class="text-base sm:text-lg font-black text-slate-950 font-satoshi flex items-center gap-3">
              <span class="w-8 h-8 rounded-xl bg-cyan-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs">6</span>
              <span>Can international or NRI investors co-invest?</span>
            </span>
            <svg class="faq-icon w-5 h-5 text-slate-600 shrink-0 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="faq-answer hidden mt-4 pt-4 border-t-2 border-slate-100 text-sm sm:text-base text-slate-800 font-semibold leading-relaxed font-satoshi">
            Yes. NEXORA supports both US/Singapore Delaware flip SAFE investments as well as direct FDI / NRE / NRO inward remittance under automated RBI reporting compliance.
          </div>
        </div>

      </div>
    </div>

    <!-- ==================================================================== -->
    <!-- 05. GRAND OBSIDIAN EXECUTIVE ACCESS LAUNCHPAD                        -->
    <!-- ==================================================================== -->
    <div class="relative bg-gradient-to-br from-[#0A0F1D] via-[#0F172A] to-[#1E293B] border border-slate-800 rounded-[32px] p-8 sm:p-14 lg:p-16 text-center text-white overflow-hidden shadow-2xl shadow-slate-950/20">

      <!-- Ambient Luxury Glows -->
      <div class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[750px] h-[350px] bg-blue-500/25 rounded-full blur-[130px]"></div>

      <div class="relative max-w-2xl mx-auto space-y-6">

        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/15 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-wider font-satoshi">
          <svg class="w-3.5 h-3.5 text-blue-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
          <span>Batch 2026 Private Syndicate Allocation Open</span>
        </span>

        <h2 class="text-3xl sm:text-4xl lg:text-[46px] font-black tracking-[-0.03em] leading-[1.1] text-white font-satoshi">
          Stop Relying on Cold Inboxes.<br class="hidden sm:inline" />
          Deploy Capital with Conviction.
        </h2>

        <p class="text-slate-200 text-base sm:text-lg font-semibold leading-relaxed font-satoshi">
          Join 450+ verified angels, family offices, and institutional partners backing the world's top 1.8% founders on clean, standardized SAFE terms across the US, UK, Singapore, UAE, and India.
        </p>

        <!-- CTA Action Buttons (Royal Blue Theme) -->
        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
          <a href="<?= function_exists('url') ? url('auth/register.php?role=investor') : 'auth/register.php?role=investor' ?>"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-9 py-4 rounded-full text-base font-extrabold text-white bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-xl shadow-blue-500/40 hover:shadow-2xl hover:shadow-blue-500/50 hover:-translate-y-0.5 transition-all font-satoshi">
            <span>Request Accredited Investor Access</span>
            <span class="text-lg font-black">&rarr;</span>
          </a>

          <a href="<?= function_exists('url') ? url('founder-home.php') : 'founder-home.php' ?>"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-4 rounded-full text-base font-bold text-white bg-white/15 hover:bg-white/20 border border-white/25 transition-all font-satoshi">
            <span>Switch to Founder Portal (⇄)</span>
          </a>
        </div>

        <p class="text-xs text-slate-300 pt-2 font-bold">
          Free accredited access &middot; 0% broker fee &middot; Automated mutual NDA protection
        </p>

      </div>

    </div>

  </div>

  <!-- FAQ Accordion Script -->
  <script>
    function toggleFaqAccordion(btn) {
      const parent = btn.closest('.faq-item');
      if (!parent) return;
      const answer = parent.querySelector('.faq-answer');
      const icon = parent.querySelector('.faq-icon');

      const isHidden = answer.classList.contains('hidden');
      if (isHidden) {
        answer.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(180deg)';
        parent.classList.add('border-blue-600', 'shadow-md');
        parent.classList.remove('border-slate-300');
      } else {
        answer.classList.add('hidden');
        if (icon) icon.style.transform = 'rotate(0deg)';
        parent.classList.remove('border-blue-600', 'shadow-md');
        parent.classList.add('border-slate-300');
      }
    }

    function filterFaqQuestions() {
      const query = (document.getElementById('faqSearchInput')?.value || '').toLowerCase().trim();
      const items = document.querySelectorAll('.faq-item');
      items.forEach(item => {
        const text = item.innerText.toLowerCase();
        if (!query || text.includes(query)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    }
  </script>

</section>
