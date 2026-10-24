<?php
/**
 * NEXORA — BEYOND THE PITCH
 * Replace includes/home/03_dealroom.php with this complete file.
 * HTML + CSS + JS together. No image, Tailwind, VS or GSAP dependency.
 * Keeps url() and founder / investor registration routes.
 * Uses #nx-dealroom to avoid duplicating the opportunities section's ID.
 * Update any navigation link targeting this section to #nx-dealroom.
 * The interactive document preview is illustrative, not a live data room.
 * No financial, legal, security or verification claims are inferred.
 * AJAX cleanup: document.getElementById('nx-dealroom')?.__ndCleanup?.();
 */
$ndEscape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$ndRoute = static function ($path) {
  $value = function_exists('url') ? url($path) : $path;
  if (preg_match('~^[a-z][a-z0-9+.-]*:~i', (string) $value) && !preg_match('~^https?://~i', (string) $value)) return $path;
  return $value;
};
?>

<section class="nd" id="nx-dealroom" data-nd aria-labelledby="nd-title">
  <div class="nd-wrap">
    <div class="nd-topline"><span>03 / THE DEALROOM</span><span>THE DETAILS MAKE THE DIFFERENCE.</span></div>
    <div class="nd-layout">
      <div class="nd-showcase" data-nd-reveal>
        <div class="nd-frame">
          <div class="nd-frame-title"><span>AN INSIDE LOOK</span><span>THE NEXORA WORKSPACE</span></div>
          <div class="nd-window">
            <div class="nd-window-header"><span class="nd-brand"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 18V6l14 12V6M5 12h14"/></svg>NEXORA <span>/ Dealroom</span></span><span class="nd-preview-label">Illustrative preview</span></div>
            <div class="nd-workspace">
              <div class="nd-sidebar" hidden>
                <span class="nd-sidebar-label">EXPLORE THE DETAILS</span>
                <div class="nd-tabs" role="tablist" aria-label="Preview document categories">
                  <button id="nd-tab-company" role="tab" type="button" aria-selected="true" aria-controls="nd-panel-company" tabindex="0" data-nd-tab="0"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 3h7l3 3v11H5zM12 3v4h3M8 10h4M8 13h4"/></svg><span>Company</span><small>01</small></button>
                  <button id="nd-tab-financials" role="tab" type="button" aria-selected="false" aria-controls="nd-panel-financials" tabindex="-1" data-nd-tab="1"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 4v12h12M7 13V9m4 4V6m4 7v-3"/></svg><span>Financials</span><small>02</small></button>
                  <button id="nd-tab-ownership" role="tab" type="button" aria-selected="false" aria-controls="nd-panel-ownership" tabindex="-1" data-nd-tab="2"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="7" y="3" width="6" height="4" rx="1"/><path d="M10 7v3M5 13v-3h10v3"/><rect x="2" y="13" width="6" height="4" rx="1"/><rect x="12" y="13" width="6" height="4" rx="1"/></svg><span>Ownership</span><small>03</small></button>
                </div>
                <div class="nd-sidebar-bottom"><span class="nd-sidebar-mark" aria-hidden="true"></span><span>One business.<br>More perspective.</span></div>
              </div>

              <div class="nd-documents">
                <div class="nd-document" id="nd-panel-company" data-nd-panel>
                  <div class="nd-document-eyebrow"><span>01 / COMPANY</span><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 15 15 5M5 5h10v10"/></svg></div>
                  <h3>The story behind<br>the business.</h3>
                  <p class="nd-document-intro">Start with what the company does, who it serves and why it matters.</p>
                  <div class="nd-document-sheet">
                    <div class="nd-sheet-title"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 3h7l3 3v11H5zM12 3v4h3M8 11h4M8 14h3"/></svg><span>Company overview</span><small>BRIEF</small></div>
                    <dl class="nd-brief"><div><dt>The problem</dt><dd>A need worth solving.</dd></div><div><dt>The solution</dt><dd>A product with a purpose.</dd></div><div><dt>The people</dt><dd>The team behind the ambition.</dd></div></dl>
                  </div>
                  <div class="nd-document-footer"><span class="nd-document-rule" aria-hidden="true"></span><span>Context before conclusions.</span></div>
                </div>

                <div class="nd-document" id="nd-panel-financials" data-nd-panel>
                  <div class="nd-document-eyebrow"><span>02 / FINANCIALS</span><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 15 15 5M5 5h10v10"/></svg></div>
                  <h3>Put the numbers<br>in perspective.</h3>
                  <p class="nd-document-intro">Look at the reporting period, supporting records and assumptions together.</p>
                  <div class="nd-document-sheet">
                    <div class="nd-sheet-title"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 4v12h12M7 13V9m4 4V6m4 7v-3"/></svg><span>Financial context</span><small>REVIEW</small></div>
                    <dl class="nd-brief"><div><dt>Revenue</dt><dd>Sources and reporting period.</dd></div><div><dt>Costs</dt><dd>Operations and key assumptions.</dd></div><div><dt>Evidence</dt><dd>Records behind the figures.</dd></div></dl>
                  </div>
                  <div class="nd-document-footer"><span class="nd-document-rule" aria-hidden="true"></span><span>Figures need a fuller picture.</span></div>
                </div>

                <div class="nd-document" id="nd-panel-ownership" data-nd-panel>
                  <div class="nd-document-eyebrow"><span>03 / OWNERSHIP</span><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 15 15 5M5 5h10v10"/></svg></div>
                  <h3>Understand<br>the structure.</h3>
                  <p class="nd-document-intro">Build a clearer picture of ownership, prior funding and the proposed round.</p>
                  <div class="nd-document-sheet">
                    <div class="nd-sheet-title"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 7h12M6 7v8m4-8v8m4-8v8M3 17h14M3 5l7-3 7 3"/></svg><span>Ownership context</span><small>STRUCTURE</small></div>
                    <dl class="nd-brief"><div><dt>Ownership</dt><dd>Current interests in the business.</dd></div><div><dt>History</dt><dd>Previous funding and changes.</dd></div><div><dt>The round</dt><dd>Terms to understand and discuss.</dd></div></dl>
                  </div>
                  <div class="nd-document-footer"><span class="nd-document-rule" aria-hidden="true"></span><span>More clarity. Better questions.</span></div>
                </div>
              </div>
            </div>
            <div class="nd-window-bottom"><span class="nd-bottom-dot" aria-hidden="true"></span><span>Explore the categories above</span><span class="nd-page-count" data-nd-count>01 / 03</span></div>
          </div>
          <div class="nd-frame-caption"><span aria-hidden="true">↳</span><p>From the headline<br><strong>to the details that matter.</strong></p><svg viewBox="0 0 64 24" fill="none" aria-hidden="true"><path d="M2 12h56m-7-6 7 6-7 6"/></svg></div>
        </div>
      </div>

      <div class="nd-copy">
        <p class="nd-eyebrow" data-nd-reveal><span aria-hidden="true"></span> A CLOSER LOOK AT THE OPPORTUNITY</p>
        <h2 id="nd-title" data-nd-reveal>Beyond the pitch.<br><span>Closer to<br class="nd-title-break"> the business.</span></h2>
        <p class="nd-description" data-nd-reveal>A pitch opens the door. The details move the conversation forward. Bring the company story, financial context and ownership picture into focus.</p>
        <ol class="nd-pillars">
          <li data-nd-reveal><span class="nd-pillar-number">01</span><div><h3>See the whole business.</h3><p>Understand the people, the product and the thinking behind the opportunity.</p></div></li>
          <li data-nd-reveal><span class="nd-pillar-number">02</span><div><h3>Follow the supporting details.</h3><p>Read the available records alongside the headline figures, and note what needs clarification.</p></div></li>
          <li data-nd-reveal><span class="nd-pillar-number">03</span><div><h3>Ask better questions.</h3><p>Turn what you learn into a focused discussion about the business, its plans and the next steps.</p></div></li>
        </ol>
        <div class="nd-actions" data-nd-reveal><a class="nd-primary" href="<?= $ndEscape($ndRoute('auth/register.php?role=investor')) ?>">Explore as an investor<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5"/></svg></a><a class="nd-secondary" href="<?= $ndEscape($ndRoute('auth/register.php?role=founder')) ?>">Introduce your company<span aria-hidden="true">↗</span></a></div>
      </div>
    </div>
    <div class="nd-section-footer"><p>A stronger conversation starts with a clearer understanding.</p><span>THE BUSINESS. THE EVIDENCE. THE BIGGER PICTURE.</span></div>
  </div>
</section>

<style>
.nd{--nd-ink:#182435;--nd-blue:#2f57d5;--nd-muted:#5e6d82;--nd-line:#e1e7ef;padding:82px 0 32px;background:#f8fafc;color:var(--nd-ink);border-bottom:1px solid var(--nd-line);font-family:'Satoshi',Inter,Arial,sans-serif;-webkit-font-smoothing:antialiased}
.nd *,.nd *::before,.nd *::after{box-sizing:border-box}.nd p,.nd h2,.nd h3,.nd dl,.nd dd{margin:0}.nd a{color:inherit;text-decoration:none}.nd button{font:inherit}.nd svg{display:block;flex-shrink:0}.nd [hidden]{display:none!important}.nd-wrap{width:min(1240px,calc(100% - 80px));margin-inline:auto}
.nd-topline{display:flex;justify-content:space-between;gap:20px;font-size:12px;letter-spacing:.12em;line-height:1.5;padding-bottom:18px;border-bottom:1px solid var(--nd-line);color:#64748b}.nd-topline>span:first-child{font-weight:700;color:#2f57d5}.nd-layout{display:grid;grid-template-columns:1.15fr 1fr;gap:clamp(36px,5.5vw,80px);align-items:center;padding:46px 0 41px}.nd-copy,.nd-showcase{min-width:0}
.nd-eyebrow{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700;line-height:1.5;letter-spacing:.12em;color:#2f57d5;margin-bottom:20px!important}.nd-eyebrow>span{width:20px;height:2.5px;background:#2f57d5}
.nd h2{font-size:clamp(38px,4.2vw,60px);line-height:1.12;font-weight:600;letter-spacing:-.05em;color:#0f172a}.nd h2>span{font-weight:500;color:#64748b}.nd-description{font-size:16px;line-height:1.75;color:var(--nd-muted);max-width:440px;margin-top:22px!important;text-wrap:pretty}
.nd-pillars{list-style:none;padding:0;margin:28px 0 0;display:flex;flex-direction:column;gap:20px}.nd-pillars li{display:flex;gap:16px;align-items:flex-start}.nd-pillar-number{font-size:13px;font-weight:700;font-variant-numeric:tabular-nums;color:#2f57d5;padding-top:2px;min-width:20px}.nd-pillars h3{font-size:16px;font-weight:650;line-height:1.45;letter-spacing:-.01em;color:#0f172a}.nd-pillars p{font-size:14px;line-height:1.7;margin-top:4px;color:#64748b;max-width:380px}.nd-actions{display:flex;flex-direction:column;align-items:flex-start;gap:18px;margin-top:30px}.nd-primary{display:inline-flex;align-items:center;justify-content:space-between;gap:26px;background:var(--nd-blue);color:#fff!important;border:1px solid var(--nd-blue);border-radius:8px;padding:15px 24px;font-size:14px;font-weight:600;line-height:1.5;transition:background .2s,transform .2s}.nd-primary svg{width:18px;height:18px;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;transition:transform .2s}.nd-primary:hover{background:#2449bb;transform:translateY(-2px)}.nd-primary:hover svg{transform:translateX(2px)}.nd-secondary{display:inline-flex;gap:12px;align-items:center;font-size:13px;font-weight:600;line-height:1.5;color:#475569!important;padding-bottom:4px;border-bottom:1.5px solid #cbd5e1}.nd-secondary>span{font-size:16px}.nd-secondary:hover{color:var(--nd-blue)!important;border-color:var(--nd-blue)}
.nd-frame{position:relative;background:#dbe5f1;border:1px solid #cbd7e6;border-radius:8px;padding:24px 22px 0}.nd-frame-title{display:flex;justify-content:space-between;gap:12px;font-size:11px;font-weight:600;line-height:1.5;letter-spacing:.1em;color:#64748b;margin-bottom:16px}.nd-frame-title>span:first-child{color:#2f57d5}.nd-window{position:relative;background:#fff;border:1px solid #dce5f1;border-radius:12px;box-shadow:0 17px 28px -23px #26446b50;overflow:hidden}.nd-window-header{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 20px;border-bottom:1px solid #eaf0f7}.nd-brand{display:flex;align-items:center;gap:8px;color:#1e293b;font-size:13px;line-height:1.4;font-weight:700;letter-spacing:.02em}.nd-brand svg{width:22px;height:22px;stroke:#2f57d5;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}.nd-brand>span{color:#64748b;font-size:12px;font-weight:500;letter-spacing:0;margin-left:2px}.nd-preview-label{font-size:11px;font-weight:600;line-height:1.5;color:#64748b;border:1px solid #e2e8f0;border-radius:4px;padding:3px 8px;white-space:nowrap}
.nd-workspace{display:block}.nd.is-enhanced .nd-workspace{display:grid;grid-template-columns:142px minmax(0,1fr)}.nd-sidebar{background:#f8fafc;border-right:1px solid #e2e8f0;padding:20px 10px;position:relative}.nd-sidebar-label{display:block;font-size:10px;font-weight:700;letter-spacing:.08em;color:#64748b;padding:0 6px;margin-bottom:12px;line-height:1.5}.nd-tabs{display:flex;flex-direction:column;gap:5px}.nd-tabs button{display:flex;align-items:center;gap:7px;cursor:pointer;padding:10px 8px;color:#64748b;background:none;border:1px solid transparent;border-radius:6px;font-size:13px;font-weight:600;line-height:1.4;text-align:left;transition:background .2s,color .2s,border-color .2s}.nd-tabs button>svg{width:16px;height:16px;stroke:currentColor;stroke-width:1.3;stroke-linecap:round;stroke-linejoin:round}.nd-tabs button>small{font-size:10px;font-weight:700;margin-left:auto;opacity:.7}.nd-tabs button[aria-selected=true]{background:#eff6ff;color:#2f57d5;border-color:#bfdbfe}.nd-tabs button:hover{color:#2f57d5}.nd-sidebar-bottom{position:absolute;bottom:22px;left:14px;right:10px;font-size:11px;font-weight:500;color:#64748b;line-height:1.6}.nd-sidebar-mark{display:block;width:22px;height:22px;border:1.5px solid #cbd5e1;border-radius:50%;position:relative;margin-bottom:8px}.nd-sidebar-mark::after{content:'';position:absolute;inset:4px;border:1.5px solid #2f57d5;border-radius:50%}
.nd-documents{min-width:0;padding:24px 22px 20px}.nd-document+.nd-document{border-top:1px solid #e2e8f0;padding-top:22px;margin-top:22px}.nd.is-enhanced .nd-document+.nd-document{margin:0;padding:0;border:0}.nd.is-enhanced .nd-document{min-height:340px}.nd-document-eyebrow{display:flex;justify-content:space-between;gap:15px;align-items:center;color:#64748b;font-size:11px;font-weight:700;letter-spacing:.08em;line-height:1.5;margin-bottom:14px}.nd-document-eyebrow svg{width:16px;height:16px;stroke:#2f57d5;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}.nd-document h3{font-size:26px;font-weight:650;line-height:1.2;letter-spacing:-.03em;color:#0f172a}.nd-document-intro{font-size:13px;line-height:1.75;color:#64748b;margin-top:10px!important;max-width:300px}.nd-document-sheet{border:1px solid #cbd5e1;background:#fff;border-radius:8px;margin-top:18px;box-shadow:0 4px 10px #284c7c06}.nd-sheet-title{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:650;line-height:1.5;color:#1e293b}.nd-sheet-title svg{width:16px;height:16px;stroke:#2f57d5;stroke-width:1.4;stroke-linejoin:round;stroke-linecap:round}.nd-sheet-title small{font-size:10px;font-weight:700;letter-spacing:.05em;color:#2f57d5;margin-left:auto}.nd-brief{padding:4px 14px 6px}.nd-brief>div{display:grid;grid-template-columns:82px minmax(0,1fr);gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;align-items:start}.nd-brief>div:last-child{border-bottom:0}.nd-brief dt{font-size:11.5px;font-weight:650;line-height:1.5;color:#475569}.nd-brief dd{font-size:12px;line-height:1.5;color:#1e293b;font-weight:500}.nd-document-footer{display:flex;align-items:center;gap:8px;margin-top:20px;font-size:11px;font-weight:600;line-height:1.5;color:#64748b}.nd-document-rule{height:1.5px;width:20px;background:#2f57d5;flex-shrink:0}
.nd-window-bottom{display:flex;align-items:center;gap:8px;padding:14px 20px;border-top:1px solid #e2e8f0;font-size:11px;font-weight:550;color:#64748b;background:#fcfdff}.nd-bottom-dot{width:5px;height:5px;background:#2f57d5;border-radius:50%}.nd-page-count{margin-left:auto;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700;color:#2f57d5}.nd-frame-caption{display:flex;align-items:center;gap:12px;padding:20px 4px 21px}.nd-frame-caption>span{font-size:24px;color:#2f57d5}.nd-frame-caption p{font-size:12px;line-height:1.6;color:#64748b}.nd-frame-caption strong{font-weight:600;color:#1e293b}.nd-frame-caption svg{width:52px;height:24px;margin-left:auto;stroke:#94a3b8;stroke-width:1.4;stroke-linecap:round;stroke-linejoin:round}
.nd-section-footer{display:flex;align-items:center;justify-content:space-between;gap:25px;padding-top:24px;border-top:1px solid var(--nd-line)}.nd-section-footer p{font-size:13px;font-weight:500;line-height:1.6;color:#64748b}.nd-section-footer>span{font-size:11px;font-weight:700;letter-spacing:.1em;line-height:1.6;color:#475569;text-align:right}.nd :is(a,button,[role=tabpanel]):focus-visible{outline:2px solid var(--nd-blue);outline-offset:4px}
@media(min-width:1400px){.nd-layout{gap:80px}.nd-frame{padding:28px 26px 0}.nd.is-enhanced .nd-workspace{grid-template-columns:148px minmax(0,1fr)}.nd-documents{padding:26px}.nd-document h3{font-size:28px}.nd-document-intro{font-size:14px}.nd-tabs button{font-size:13px}.nd-brief dt,.nd-brief dd{font-size:12px}.nd-document-footer{font-size:12px}.nd.is-enhanced .nd-document{min-height:360px}}
@media(max-width:1100px){.nd-wrap{width:calc(100% - 64px)}.nd-layout{gap:36px;grid-template-columns:1.1fr 1fr}.nd-frame{padding:20px 15px 0}.nd.is-enhanced .nd-workspace{grid-template-columns:125px minmax(0,1fr)}.nd-sidebar{padding-inline:6px}.nd-sidebar-label{font-size:9px}.nd-tabs button{gap:5px;padding:9px 6px;font-size:12px}.nd-tabs button>small{display:none}.nd-sidebar-bottom{left:11px;font-size:10px}.nd-documents{padding:20px 18px}.nd-document h3{font-size:23px}.nd-document-intro{font-size:12px}.nd-brief>div{grid-template-columns:70px minmax(0,1fr);gap:7px}.nd-brief dt,.nd-brief dd{font-size:11px}.nd-sheet-title{font-size:11px;padding-inline:10px}.nd-sheet-title small{font-size:9px}.nd.is-enhanced .nd-document{min-height:330px}.nd-description{font-size:15px}.nd-pillars{gap:18px;margin-top:23px}.nd-pillars p{font-size:13px}.nd-pillars h3{font-size:15px}.nd h2{font-size:40px}.nd-eyebrow{font-size:11px}.nd-window-header{padding:14px 16px}.nd-brand{font-size:12px;gap:6px}.nd-brand>span{font-size:11px}.nd-preview-label{font-size:10px;padding:3px 6px}}
@media(max-width:850px){.nd{padding-top:62px}.nd-layout{grid-template-columns:1fr;gap:33px;padding-block:32px}.nd-copy{grid-row:1}.nd h2{font-size:clamp(36px,5.7vw,48px)}.nd-title-break{display:none}.nd-description{max-width:570px;font-size:15px}.nd-pillars{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:21px;margin-top:26px}.nd-pillars li{display:block}.nd-pillar-number{display:block;padding:0;margin-bottom:8px}.nd-pillars h3{font-size:15px}.nd-pillars p{font-size:13px}.nd-actions{flex-direction:row;align-items:center;gap:25px;margin-top:25px;flex-wrap:wrap}.nd-showcase{width:100%;max-width:590px;justify-self:center}.nd-frame{padding:25px 24px 0}.nd.is-enhanced .nd-workspace{grid-template-columns:135px minmax(0,1fr)}.nd-sidebar{padding:22px 10px}.nd-sidebar-label{font-size:10px}.nd-tabs button{font-size:12px;gap:7px;padding:9px 7px}.nd-documents{padding:24px}.nd-document h3{font-size:26px}.nd-document-intro{font-size:13px;max-width:290px}.nd-sheet-title{font-size:12px;padding:12px}.nd-sheet-title small{font-size:10px}.nd-brief{padding-inline:12px}.nd-brief>div{grid-template-columns:75px minmax(0,1fr);gap:10px;padding-block:10px}.nd-brief dt,.nd-brief dd{font-size:11.5px}.nd.is-enhanced .nd-document{min-height:355px}.nd-document-eyebrow{font-size:11px}.nd-document-footer{font-size:11px}.nd-sidebar-bottom{left:17px;font-size:11px}.nd-window-header{padding:15px 20px}.nd-brand{font-size:13px}.nd-brand>span{font-size:12px}.nd-preview-label{font-size:11px}.nd-window-bottom{font-size:11px;padding:13px 19px}.nd-frame-title{font-size:11px}.nd-frame-caption p{font-size:12px}.nd-section-footer{align-items:flex-start;flex-direction:column;gap:9px}.nd-section-footer>span{text-align:left}}
@media(max-width:550px){.nd{padding:47px 0 26px}.nd-wrap{width:calc(100% - 40px)}.nd-topline{font-size:10px;letter-spacing:.08em;gap:15px;align-items:flex-start;padding-bottom:15px}.nd-topline>span:last-child{font-size:10px;max-width:145px;text-align:right}.nd-layout{padding-top:28px;gap:30px}.nd h2{font-size:clamp(33px,8.5vw,44px);line-height:1.12}.nd-eyebrow{font-size:11px;letter-spacing:.09em;margin-bottom:16px!important}.nd-title-break{display:block}.nd-description{font-size:14px;line-height:1.75;margin-top:20px!important}.nd-pillars{display:flex;gap:18px;margin-top:25px}.nd-pillars li{display:flex;gap:14px}.nd-pillar-number{padding-top:2px;margin:0;font-size:12px;min-width:18px}.nd-pillars h3{font-size:15px}.nd-pillars p{font-size:13px}.nd-actions{align-items:flex-start;flex-direction:column;gap:18px;margin-top:25px}.nd-primary{font-size:14px;padding:15px 20px;width:100%}.nd-secondary{font-size:13px}.nd-frame{padding:18px 12px 0}.nd-frame-title{font-size:10px;letter-spacing:.08em;margin-bottom:13px}.nd-window-header{padding:13px 14px}.nd-brand{font-size:12px}.nd-brand>span{font-size:11px;margin-left:2px}.nd-preview-label{font-size:10px;padding:3px 6px}.nd.is-enhanced .nd-workspace{display:block}.nd-sidebar{padding:12px;border-right:0;border-bottom:1px solid #eaf0f7}.nd-sidebar-label,.nd-sidebar-bottom{display:none}.nd-tabs{flex-direction:row;gap:4px}.nd-tabs button{flex:1;justify-content:center;font-size:11px;gap:5px;padding:8px 4px}.nd-tabs button>svg{width:13px;height:13px}.nd-documents{padding:22px 18px}.nd-document h3{font-size:24px}.nd-document-eyebrow{font-size:10px;margin-bottom:12px}.nd-document-intro{font-size:12px;max-width:310px}.nd-document-sheet{margin-top:16px}.nd-sheet-title{font-size:11px;padding:11px 12px}.nd-brief>div{grid-template-columns:70px minmax(0,1fr);gap:8px}.nd-brief dt,.nd-brief dd{font-size:11px}.nd.is-enhanced .nd-document{min-height:337px}.nd-document-footer{font-size:11px;margin-top:16px}.nd-window-bottom{font-size:10px;padding:12px}.nd-frame-caption{padding:17px 2px 18px;gap:10px}.nd-frame-caption p{font-size:11px}.nd-section-footer{padding-top:20px;gap:8px}.nd-section-footer p{font-size:12px}.nd-section-footer>span{font-size:10px;letter-spacing:.08em}}
@media(max-width:359px){.nd-wrap{width:calc(100% - 32px)}.nd-frame{padding-inline:9px}.nd-tabs button{font-size:10px;gap:4px}.nd-documents{padding:20px 14px}.nd-brand>span{display:none}.nd-brief>div{grid-template-columns:60px minmax(0,1fr)}.nd-brief dd{font-size:10px}.nd h2{font-size:33px}}
@media(prefers-reduced-motion:reduce){.nd *,.nd *::before,.nd *::after{animation:none!important;transition:none!important}.nd-primary:hover{transform:none}}
</style>

<script>
(() => {
  'use strict';
  const root=document.querySelector('[data-nd]');
  if(!root)return;
  root.__ndCleanup?.();
  const tabs=[...root.querySelectorAll('[data-nd-tab]')];
  const panels=[...root.querySelectorAll('[data-nd-panel]')];
  const sidebar=root.querySelector('.nd-sidebar'), count=root.querySelector('[data-nd-count]');
  const reduced=matchMedia('(prefers-reduced-motion: reduce)');
  const animations=new Set(), disposers=[];
  let selected=0, dead=false, observer=null;
  const on=(el,event,fn)=>{el.addEventListener(event,fn);disposers.push(()=>el.removeEventListener(event,fn));};
  function animate(el,frames,options){
    if(dead||reduced.matches||!el||typeof el.animate!=='function')return;
    const a=el.animate(frames,options);animations.add(a);
    a.onfinish=a.oncancel=()=>animations.delete(a);
  }
  function stop(){animations.forEach(a=>a.cancel());animations.clear();}
  function select(index,focus=false,motion=true){
    if(dead)return;
    selected=index;
    panels.forEach(panel=>panel.getAnimations?.().forEach(a=>a.cancel()));
    tabs.forEach((tab,i)=>{tab.setAttribute('aria-selected',String(i===index));tab.tabIndex=i===index?0:-1;panels[i].hidden=i!==index;});
    count.textContent=`${String(index+1).padStart(2,'0')} / 03`;
    if(focus)tabs[index].focus();
    if(motion)animate(panels[index],[{opacity:0,transform:'translateY(7px)'},{opacity:1,transform:'translateY(0)'}],{duration:350,easing:'cubic-bezier(.16,1,.3,1)'});
  }
  panels.forEach((panel,i)=>{panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby',tabs[i].id);panel.tabIndex=0;});
  tabs.forEach((tab,i)=>{
    on(tab,'click',()=>{if(selected!==i)select(i);});
    on(tab,'keydown',event=>{
      let next=null;
      if(event.key==='ArrowRight'||event.key==='ArrowDown')next=(i+1)%tabs.length;
      if(event.key==='ArrowLeft'||event.key==='ArrowUp')next=(i+tabs.length-1)%tabs.length;
      if(event.key==='Home')next=0;
      if(event.key==='End')next=tabs.length-1;
      if(next!==null){event.preventDefault();select(next,true);}
    });
  });
  const tablist=root.querySelector('.nd-tabs'), mobile=matchMedia('(max-width:550px)');
  const orient=()=>tablist.setAttribute('aria-orientation',mobile.matches?'horizontal':'vertical');
  on(mobile,'change',orient);orient();
  root.classList.add('is-enhanced');sidebar.hidden=false;select(0,false,false);
  on(reduced,'change',stop);
  if(typeof IntersectionObserver==='function'){
    observer=new IntersectionObserver(entries=>{
      entries.forEach(entry=>{
        if(!entry.isIntersecting)return;
        animate(entry.target,[{opacity:0,transform:'translateY(16px)'},{opacity:1,transform:'translateY(0)'}],{duration:700,easing:'cubic-bezier(.16,1,.3,1)'});
        observer.unobserve(entry.target);
      });
    },{threshold:.12});
    root.querySelectorAll('[data-nd-reveal]').forEach(el=>observer.observe(el));
  }
  root.__ndCleanup=()=>{
    dead=true;observer?.disconnect();stop();disposers.forEach(fn=>fn());
    root.classList.remove('is-enhanced');sidebar.hidden=true;tablist.removeAttribute('aria-orientation');
    panels.forEach(panel=>{panel.hidden=false;panel.removeAttribute('role');panel.removeAttribute('aria-labelledby');panel.removeAttribute('tabindex');});
    count.textContent='01 / 03';delete root.__ndCleanup;
  };
})();
</script>
