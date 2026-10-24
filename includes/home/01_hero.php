<?php
/**
 * NEXORA — THE RIGHT CONNECTION
 * Replace includes/home/01_hero.php with this entire file.
 * Self-contained HTML + CSS + JS. No Tailwind / GSAP / VS dependency.
 * Preserves url() routing and the investor / founder registration destinations.
 * $nxHeroHeaderOffset: space for an overlapping fixed header; default 80px.
 * Set to 0 if your header already occupies space in normal document flow.
 * The company and figures below are an explicitly illustrative preview.
 * Optional real data: pass $nxHeroCompany and set $nxHeroIsDemo = false.
 * No financial data or platform verification is inferred by this section.
 * AJAX cleanup: document.getElementById('nx-hero')?.__nhCleanup?.();
 */
$nxHeroHeaderOffset = max(0, (int) ($nxHeroHeaderOffset ?? 80));
$nxHeroCompany = $nxHeroCompany ?? [];
$nxHeroIsDemo = $nxHeroIsDemo ?? true;
$nxHeroExample = [
  'name' => 'Aether Robotics',
  'initials' => 'AR',
  'sector' => 'Industrial automation',
  'description' => 'Autonomous robotics for the next generation of industrial operations.',
  'stage' => 'Seed round',
  'target' => '$2.4M',
  'mrr' => '$184K',
  'committed' => '$1.92M',
  'filled' => 80,
];
$nxHeroBlank = array_fill_keys(array_keys($nxHeroExample), 'Not disclosed');
$nxHeroBlank['initials'] = 'NX';
$nxHeroBlank['name'] = 'Company profile';
$nxHeroBlank['description'] = 'Explore the company profile for further information.';
$nxHeroBlank['filled'] = null;
$nxHeroData = array_replace($nxHeroIsDemo ? $nxHeroExample : $nxHeroBlank, $nxHeroCompany);
$nhEscape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$nhRoute = static function ($path) {
  $result = function_exists('url') ? url($path) : $path;
  // Only local routes or HTTP(S) destinations are valid navigation targets.
  if (preg_match('~^[a-z][a-z0-9+.-]*:~i', (string) $result) && !preg_match('~^https?://~i', (string) $result)) return $path;
  return $result;
};
$nhInvestorUrl = $nhRoute('auth/register.php?role=investor');
$nhFounderUrl = $nhRoute('auth/register.php?role=founder');
$nhLoginUrl = $nhRoute('auth/login.php?role=investor');
$nhPercent = is_numeric($nxHeroData['filled']) ? max(0, min(100, (float) $nxHeroData['filled'])) : null;
$nhPercentLabel = $nhPercent !== null ? rtrim(rtrim(number_format($nhPercent, 1, '.', ''), '0'), '.') : '';
?>

<section class="nh" id="nx-hero" data-nh aria-labelledby="nh-title" style="--nh-header:<?= $nxHeroHeaderOffset ?>px">
  <div class="nh-wrap">
    <div class="nh-layout">
      <div class="nh-copy">
        <p class="nh-eyebrow" data-nh-reveal><span aria-hidden="true"></span> FOR BUILDERS. FOR BACKERS.</p>
        <h1 id="nh-title"><span data-nh-reveal>Big ideas.</span><span data-nh-reveal>Meet the right</span><span class="nh-title-accent" data-nh-reveal>capital<span class="nh-title-dot">.</span></span></h1>
        <p class="nh-description" data-nh-reveal>A focused space for ambitious founders and thoughtful investors. Introduce your business, explore opportunities, and start a meaningful conversation.</p>
        <div class="nh-actions" data-nh-reveal>
          <a class="nh-button nh-button-primary" href="<?= $nhEscape($nhInvestorUrl) ?>">Join as an investor <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg></a>
          <a class="nh-button nh-button-secondary" href="<?= $nhEscape($nhFounderUrl) ?>">Apply as a founder <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 15 15 5M5 5h10v10"/></svg></a>
        </div>
        <p class="nh-signin" data-nh-reveal>Already part of NEXORA? <a href="<?= $nhEscape($nhLoginUrl) ?>">Sign in <span aria-hidden="true">↗</span></a></p>
        <div class="nh-copy-foot" data-nh-reveal><span class="nh-small-symbol" aria-hidden="true"><i></i><i></i><i></i></span><p>A clearer picture.<br><strong>A more considered connection.</strong></p></div>
      </div>

      <div class="nh-showcase" data-nh-reveal>
        <div class="nh-showcase-top"><span>THE OPPORTUNITY, AT A GLANCE</span><span aria-hidden="true">01 / NEXORA</span></div>
        <div class="nh-workspace">
          <div class="nh-workspace-lines" aria-hidden="true"><i></i><i></i><i></i></div>
          <div class="nh-side-label" aria-hidden="true">IDEAS MEET POSSIBILITY</div>
          <article class="nh-profile" aria-labelledby="nh-company-name">
            <div class="nh-profile-top"><span class="nh-document-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 18V6l14 12V6M5 12h14"/></svg></span><span><?= $nxHeroIsDemo ? 'ILLUSTRATIVE PROFILE' : 'COMPANY PROFILE' ?></span><span class="nh-document-no" aria-hidden="true">NX — 001</span></div>
            <div class="nh-company">
              <span class="nh-monogram" aria-hidden="true"><?= $nhEscape($nxHeroData['initials']) ?></span>
              <div><p><?= $nhEscape($nxHeroData['sector']) ?></p><h2 id="nh-company-name"><?= $nhEscape($nxHeroData['name']) ?></h2></div>
            </div>

            <div class="nh-tabs" role="tablist" aria-label="Company preview" hidden>
              <button id="nh-tab-overview" type="button" role="tab" aria-controls="nh-overview" aria-selected="true" tabindex="0" data-nh-tab="0">The business</button>
              <button id="nh-tab-round" type="button" role="tab" aria-controls="nh-round" aria-selected="false" tabindex="-1" data-nh-tab="1">The round</button>
            </div>

            <div class="nh-panels">
              <div class="nh-panel" id="nh-overview" data-nh-panel>
                <p class="nh-company-description"><?= $nhEscape($nxHeroData['description']) ?></p>
                <dl class="nh-summary-metrics"><div><dt>Target raise</dt><dd><?= $nhEscape($nxHeroData['target']) ?></dd></div><div><dt>Funding stage</dt><dd class="nh-stage-value"><?= $nhEscape($nxHeroData['stage']) ?></dd></div></dl>
                <div class="nh-overview-note"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 3.5h7l3 3v10H5zM12 3.5v3h3M8 10h4M8 13h4"/></svg><span>Get to know the business behind the numbers.</span></div>
              </div>
              <div class="nh-panel" id="nh-round" data-nh-panel>
                <dl class="nh-round-metrics"><div><dt>Monthly revenue</dt><dd><?= $nhEscape($nxHeroData['mrr']) ?></dd></div><div><dt>Committed</dt><dd><?= $nhEscape($nxHeroData['committed']) ?></dd></div></dl>
                <div class="nh-round-progress"><div><span>Allocation progress</span><strong><?= $nhPercent !== null ? $nhEscape($nhPercentLabel) . '%' : 'Not disclosed' ?></strong></div>
                  <?php if ($nhPercent !== null): ?><div class="nh-track" role="meter" aria-label="Round allocation" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $nhEscape($nhPercentLabel) ?>"><i style="width:<?= $nhEscape($nhPercentLabel) ?>%"></i></div><?php else: ?><div class="nh-track" aria-hidden="true"></div><?php endif; ?>
                  <p>Target round <span><?= $nhEscape($nxHeroData['target']) ?></span></p>
                </div>
                <p class="nh-round-note"><?= $nxHeroIsDemo ? 'Sample figures for this interface preview.' : 'Review company documents for dates, terms and supporting details.' ?></p>
              </div>
            </div>

            <div class="nh-profile-bottom"><span>Start with the details.</span><a href="<?= $nhEscape($nhLoginUrl) ?>" aria-label="Sign in to explore opportunities">Explore opportunities <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 15 15 5M5 5h10v10"/></svg></a></div>
          </article>
          <div class="nh-workspace-caption"><span class="nh-caption-line" aria-hidden="true"></span><p>More context.<br><strong>Better conversations.</strong></p><svg class="nh-connection-mark" viewBox="0 0 62 32" fill="none" aria-hidden="true"><circle cx="17" cy="16" r="13"/><circle cx="43" cy="16" r="13"/><path d="M25 16h10m-4-4 4 4-4 4"/></svg></div>
        </div>
      </div>
    </div>

    <div class="nh-bottom">
      <p class="nh-bottom-label">A SIMPLE PATH<br><strong>TO THE RIGHT CONVERSATION.</strong></p>
      <ol class="nh-steps">
        <li><span>01</span><div><h3>Discover</h3><p>Find a business that interests you.</p></div></li>
        <li><span>02</span><div><h3>Understand</h3><p>Explore the story and the numbers.</p></div></li>
        <li><span>03</span><div><h3>Connect</h3><p>Start a focused conversation.</p></div></li>
      </ol>
    </div>
  </div>
</section>

<style>
.nh{--nh-ink:#182435;--nh-blue:#2f57d5;--nh-muted:#758093;--nh-line:#e0e6ee;position:relative;isolation:isolate;padding:calc(var(--nh-header,80px) + 55px) 0 0;background:#f8fafc;color:var(--nh-ink);border-bottom:1px solid var(--nh-line);font-family:'Satoshi',Inter,Arial,sans-serif;-webkit-font-smoothing:antialiased}
.nh *,.nh *::before,.nh *::after{box-sizing:border-box}.nh p,.nh h1,.nh h2,.nh h3,.nh dl,.nh dd{margin:0}.nh a{color:inherit;text-decoration:none}.nh button{font:inherit}.nh svg{display:block;flex-shrink:0}.nh [hidden]{display:none!important}.nh-wrap{width:min(1240px,calc(100% - 80px));margin-inline:auto}
.nh-layout{display:grid;grid-template-columns:1.12fr 1fr;gap:clamp(35px,5vw,78px);align-items:center;padding-bottom:62px}.nh-copy{min-width:0}.nh-eyebrow{display:flex;align-items:center;gap:10px;color:#475569;font-size:12px;font-weight:700;letter-spacing:.15em;line-height:1.5;margin-bottom:24px!important}.nh-eyebrow>span{width:22px;height:2.5px;background:var(--nh-blue)}
.nh h1{font-size:clamp(52px,6.2vw,86px);font-weight:600;letter-spacing:-.055em;line-height:1.04}.nh h1>span{display:block}.nh h1 .nh-title-accent{color:var(--nh-blue);font-weight:600;padding-bottom:.08em}.nh-title-dot{color:#93a8df}
.nh-description{max-width:480px;color:#586577;font-size:17px;line-height:1.75;margin-top:24px!important;text-wrap:pretty}.nh-actions{display:flex;gap:14px;margin-top:32px;align-items:stretch;flex-wrap:wrap}.nh-button{display:inline-flex;align-items:center;justify-content:center;gap:14px;padding:15px 24px;border:1px solid transparent;border-radius:8px;font-size:14px;font-weight:650;line-height:1.4;transition:transform .2s,background .2s,border-color .2s}.nh-button svg{width:18px;height:18px;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;transition:transform .2s}.nh-button-primary{background:var(--nh-blue);color:#fff!important;box-shadow:0 4px 14px #2f57d525}.nh-button-secondary{background:transparent;border-color:#d9e0eb}.nh-button:hover{transform:translateY(-2px)}.nh-button-primary:hover{background:#2447b6}.nh-button-secondary:hover{background:#fff;border-color:#b9c7dc}.nh-button:hover svg{transform:translateX(2px)}.nh-signin{display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:13px;line-height:1.6;color:#64748b;margin-top:18px!important}.nh-signin a{color:#2f57d5;font-weight:600;display:inline-flex;gap:4px}.nh-signin a:hover{text-decoration:underline;text-underline-offset:3px}
.nh-copy-foot{display:flex;align-items:center;gap:14px;margin-top:38px}.nh-copy-foot>p{font-size:12px;line-height:1.6;color:#64748b}.nh-copy-foot strong{font-weight:600;color:#334155}.nh-small-symbol{display:flex;align-items:center;width:34px;height:29px;position:relative;flex-shrink:0}.nh-small-symbol>i{position:absolute;width:15px;height:22px;border:1px solid #9bafd6;transform:skewY(-25deg);left:0;top:6px}.nh-small-symbol>i:nth-child(2){left:9px;top:3px;border-color:#6b88bf}.nh-small-symbol>i:nth-child(3){left:18px;top:0;border-color:#355c9c}
.nh-showcase{min-width:0;position:relative}.nh-showcase-top{display:flex;justify-content:space-between;gap:14px;font-size:11px;font-weight:600;line-height:1.5;letter-spacing:.1em;color:#64748b;padding:0 2px 14px}.nh-showcase-top>span:last-child{font-size:11px;font-weight:600;letter-spacing:.07em;color:#64748b}
.nh-workspace{position:relative;padding:34px 27px 0;background:#dbe5f1;border:1px solid #cbd7e6;border-radius:6px;overflow:hidden}.nh-workspace-lines{position:absolute;inset:0;pointer-events:none;overflow:hidden}.nh-workspace-lines>i{position:absolute;width:420px;height:420px;border:1px solid #c5d3e6;border-radius:50%;left:35%;top:-160px}.nh-workspace-lines>i:nth-child(2){width:330px;height:330px;left:44%;top:-115px}.nh-workspace-lines>i:nth-child(3){width:240px;height:240px;left:53%;top:-70px}.nh-side-label{position:absolute;left:8px;top:80px;writing-mode:vertical-rl;transform:rotate(180deg);font-size:10px;font-weight:600;color:#94a3b8;letter-spacing:.17em}
.nh-profile{position:relative;border:1px solid #dfe6ef;border-radius:12px;background:#fff;box-shadow:0 16px 28px -20px #233b6850;overflow:hidden}.nh-profile-top{display:flex;align-items:center;gap:8px;padding:15px 23px;border-bottom:1px solid #edf0f5;font-size:11px;font-weight:650;letter-spacing:.08em;color:#64748b}.nh-document-mark{width:20px;height:20px;color:var(--nh-blue);margin-right:2px}.nh-document-mark svg{width:20px;height:20px;stroke:currentColor;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}.nh-document-no{margin-left:auto;color:#64748b;font-size:11px;font-weight:600;letter-spacing:.06em;white-space:nowrap}
.nh-company{display:flex;gap:14px;align-items:center;padding:24px 23px 20px}.nh-monogram{display:grid;place-items:center;width:52px;height:56px;flex-shrink:0;background:#eef2fc;border:1px solid #e3eafa;border-radius:10px;color:#2f57d5;font-size:16px;font-weight:700;letter-spacing:-.04em}.nh-company>div{min-width:0}.nh-company p{font-size:12px;font-weight:600;color:#64748b;line-height:1.5;margin-bottom:4px;letter-spacing:.02em}.nh-company h2{font-size:26px;font-weight:700;line-height:1.2;letter-spacing:-.03em;color:#0f172a;overflow-wrap:anywhere}
.nh-tabs{margin:0 23px;display:flex;border-bottom:1px solid #e9edf3;gap:24px}.nh-tabs button{position:relative;background:none;border:0;cursor:pointer;color:#64748b;padding:0 0 11px;font-size:13px;line-height:1.5;font-weight:600}.nh-tabs button::after{content:'';position:absolute;height:2.5px;background:var(--nh-blue);bottom:-1px;left:0;right:0;transform:scaleX(0);transform-origin:left;transition:transform .2s}.nh-tabs button[aria-selected=true]{color:#2f57d5}.nh-tabs button[aria-selected=true]::after{transform:scaleX(1)}.nh-tabs button:hover{color:var(--nh-blue)}
.nh-panels{padding:22px 23px 20px}.nh.is-enhanced .nh-panel{min-height:190px}.nh-panel+.nh-panel{margin-top:24px;border-top:1px solid #edf0f5;padding-top:24px}.nh.is-enhanced .nh-panel+.nh-panel{margin:0;border:0;padding:0}.nh-company-description{font-size:14px;line-height:1.7;color:#475569;max-width:360px}.nh-summary-metrics{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:22px!important}.nh-summary-metrics dt,.nh-round-metrics dt{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;line-height:1.5;color:#64748b;margin-bottom:6px}.nh-summary-metrics dd{font-size:32px;line-height:1.2;letter-spacing:-.055em;font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums;overflow-wrap:anywhere}.nh-summary-metrics .nh-stage-value{font-size:18px;font-weight:600;letter-spacing:-.025em;padding-top:8px;color:#1e293b}.nh-overview-note{display:flex;align-items:flex-start;gap:8px;font-size:12px;font-weight:500;color:#64748b;line-height:1.5;margin-top:22px}.nh-overview-note svg{width:16px;height:16px;stroke:currentColor;stroke-width:1.4}
.nh-round-metrics{display:grid;grid-template-columns:1fr 1fr;gap:20px}.nh-round-metrics dd{font-size:28px;font-weight:700;letter-spacing:-.05em;line-height:1.2;color:#0f172a;overflow-wrap:anywhere;font-variant-numeric:tabular-nums}.nh-round-progress{margin-top:22px}.nh-round-progress>div:first-child{display:flex;justify-content:space-between;align-items:center;gap:12px;font-size:12px;font-weight:500;color:#475569}.nh-round-progress strong{font-size:13px;font-weight:700;color:#2f57d5;font-variant-numeric:tabular-nums}.nh-track{height:5px;background:#edf1f8;border-radius:4px;overflow:hidden;margin-top:10px}.nh-track i{display:block;height:100%;border-radius:inherit;background:#2f57d5;transform-origin:left}.nh-round-progress>p{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#64748b;line-height:1.5;margin-top:8px}.nh-round-progress>p>span{color:#1e293b;font-weight:600}.nh-round-note{font-size:11.5px;line-height:1.5;color:#64748b;margin-top:20px!important}
.nh-profile-bottom{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:15px 23px;border-top:1px solid #edf0f5;background:#fcfdff;font-size:12px;font-weight:500;color:#64748b}.nh-profile-bottom a{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#2f57d5}.nh-profile-bottom a>svg{width:16px;height:16px;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;transition:transform .2s}.nh-profile-bottom a:hover>svg{transform:translate(2px,-2px)}
.nh-workspace-caption{position:relative;display:flex;gap:12px;align-items:center;padding:22px 2px}.nh-caption-line{height:1.5px;width:24px;background:#aebfda;flex-shrink:0}.nh-workspace-caption p{font-size:12px;line-height:1.6;color:#64748b}.nh-workspace-caption strong{font-weight:600;color:#334155}.nh-connection-mark{width:55px;height:30px;margin-left:auto;stroke:#9bafd0;stroke-width:1}.nh-connection-mark path{stroke:#2f57d5;stroke-width:1.4}
.nh-bottom{display:grid;grid-template-columns:1fr 3fr;gap:35px;border-top:1px solid var(--nh-line);padding:30px 0 32px}.nh-bottom-label{font-size:11px;font-weight:650;letter-spacing:.08em;line-height:1.8;color:#64748b}.nh-bottom-label strong{font-weight:700;color:#1e293b}.nh-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;list-style:none;padding:0;margin:0}.nh-steps li{display:flex;align-items:flex-start;gap:14px}.nh-steps li>span{font-size:13px;font-weight:700;color:#2f57d5;margin-top:2px;font-variant-numeric:tabular-nums}.nh-steps h3{font-size:14px;font-weight:700;line-height:1.5;letter-spacing:-.01em;color:#0f172a}.nh-steps p{font-size:12.5px;line-height:1.6;margin-top:4px;color:#64748b}.nh :is(a,button,[role=tabpanel]):focus-visible{outline:2px solid var(--nh-blue);outline-offset:5px}
@media(min-width:1440px){.nh{padding-top:calc(var(--nh-header,80px) + 70px)}.nh-layout{padding-bottom:74px}}
@media(max-width:1100px){.nh-wrap{width:calc(100% - 64px)}.nh-layout{gap:35px;grid-template-columns:1.05fr 1fr}.nh h1{font-size:clamp(46px,5.4vw,70px)}.nh-description{font-size:15px}.nh-button{padding:14px 18px;font-size:13px;gap:12px}.nh-actions{gap:10px}.nh-workspace{padding:26px 20px 0}.nh-profile-top{padding-inline:19px}.nh-company{padding-inline:19px}.nh-company h2{font-size:24px}.nh-tabs{margin-inline:19px}.nh-panels{padding-inline:19px}.nh-profile-bottom{padding-inline:19px;gap:9px}.nh-profile-bottom>span{font-size:11px}.nh-profile-bottom>a{font-size:12px;gap:6px}.nh-copy-foot{margin-top:28px}.nh-bottom{grid-template-columns:.9fr 3fr;gap:20px}.nh-steps{gap:20px}}
@media(max-width:850px){.nh{padding-top:calc(var(--nh-header,80px) + 35px)}.nh-layout{grid-template-columns:1fr;gap:38px;padding-bottom:40px}.nh h1{font-size:clamp(48px,8vw,72px)}.nh h1>span:nth-child(2){display:inline}.nh h1 .nh-title-accent{display:inline}.nh h1>span:nth-child(2)::after{content:' '}.nh-description{max-width:490px;font-size:16px}.nh-copy-foot{display:none}.nh-showcase{max-width:560px;width:100%;justify-self:center}.nh-workspace{padding:28px 32px 0}.nh-company h2{font-size:26px}.nh-panels{padding-top:23px}.nh.is-enhanced .nh-panel{min-height:177px}.nh-profile-top,.nh-company,.nh-panels,.nh-profile-bottom{padding-inline:27px}.nh-tabs{margin-inline:27px}.nh-profile-bottom>span{font-size:12px}.nh-profile-bottom>a{font-size:13px}.nh-bottom{grid-template-columns:1fr;gap:19px;padding-block:23px}.nh-bottom-label br{display:none}.nh-bottom-label strong{margin-left:6px}.nh-steps{gap:20px}.nh-steps li{gap:12px}}
@media(max-width:550px){.nh-wrap{width:calc(100% - 40px)}.nh{padding-top:calc(var(--nh-header,80px) + 26px)}.nh-eyebrow{font-size:11px;letter-spacing:.12em;margin-bottom:20px!important}.nh h1{font-size:clamp(43px,10.4vw,58px);line-height:1.06}.nh h1>span:nth-child(2),.nh h1 .nh-title-accent{display:block}.nh h1>span:nth-child(2)::after{content:none}.nh-description{font-size:15px;line-height:1.75;max-width:355px;margin-top:20px!important}.nh-actions{display:grid;grid-template-columns:1fr;gap:10px;margin-top:24px}.nh-button{justify-content:space-between;font-size:14px;padding:15px 20px}.nh-signin{font-size:12px;margin-top:15px!important}.nh-layout{gap:32px;padding-bottom:31px}.nh-showcase-top{font-size:10px;letter-spacing:.08em;padding-bottom:11px}.nh-showcase-top>span:last-child{font-size:10px}.nh-workspace{padding:20px 14px 0}.nh-side-label{display:none}.nh-profile-top{padding:12px 16px;font-size:10px;gap:6px}.nh-document-no{font-size:10px}.nh-company{padding:20px 16px 19px;gap:12px}.nh-monogram{width:44px;height:48px;font-size:13px}.nh-company h2{font-size:22px}.nh-company p{font-size:11px}.nh-tabs{margin-inline:16px;gap:20px}.nh-tabs button{font-size:12px}.nh-panels{padding:19px 16px}.nh.is-enhanced .nh-panel{min-height:181px}.nh-company-description{font-size:13px}.nh-summary-metrics{gap:15px;margin-top:20px!important}.nh-summary-metrics dd{font-size:28px}.nh-summary-metrics .nh-stage-value{font-size:16px}.nh-overview-note{font-size:11px;margin-top:20px}.nh-round-metrics dd{font-size:24px}.nh-round-progress{margin-top:22px}.nh-profile-bottom{padding:13px 16px;gap:8px}.nh-profile-bottom>span{font-size:11px}.nh-profile-bottom>a{font-size:12px;gap:5px}.nh-profile-bottom a>svg{width:15px}.nh-workspace-caption{padding:17px 3px;gap:10px}.nh-workspace-caption p{font-size:11px}.nh-connection-mark{width:46px}.nh-bottom{gap:18px;padding-block:20px 24px}.nh-bottom-label{font-size:10px;letter-spacing:.07em}.nh-steps{grid-template-columns:1fr;gap:16px}.nh-steps li{gap:14px}.nh-steps li>div{display:grid;grid-template-columns:85px 1fr;gap:8px;align-items:baseline;flex:1}.nh-steps h3{font-size:13px}.nh-steps p{font-size:12px;margin:0}.nh-steps li>span{font-size:11px}}
@media(max-width:359px){.nh-wrap{width:calc(100% - 32px)}.nh-workspace{padding-inline:10px}.nh-profile-top,.nh-company,.nh-panels,.nh-profile-bottom{padding-inline:12px}.nh-tabs{margin-inline:12px}.nh-company h2{font-size:20px}.nh-profile-bottom{align-items:flex-start;flex-direction:column;gap:7px}.nh-steps li>div{grid-template-columns:75px 1fr;gap:6px}.nh-company-description{font-size:12px}}
@media(prefers-reduced-motion:reduce){.nh *,.nh *::before,.nh *::after{animation:none!important;transition:none!important}.nh-button:hover{transform:none}}
</style>

<script>
(() => {
  'use strict';
  const root = document.querySelector('[data-nh]');
  if (!root) return;
  root.__nhCleanup?.();
  const tabs = [...root.querySelectorAll('[data-nh-tab]')];
  const panels = [...root.querySelectorAll('[data-nh-panel]')];
  const tablist = root.querySelector('.nh-tabs');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const listeners = [], animations = [];
  let active = 0, observer = null, dead = false;
  const on = (el, type, fn) => {el.addEventListener(type,fn);listeners.push(()=>el.removeEventListener(type,fn));};
  function cancelAnimations() {animations.forEach(a=>a.cancel());animations.length=0;}
  function animate(el, frames, options) {
    if (!el || reduced.matches || typeof el.animate !== 'function') return;
    animations.push(el.animate(frames, options));
  }
  function select(index, moveFocus=false, motion=true) {
    if(dead)return;
    active=index;
    tabs.forEach((tab,i)=>{
      tab.setAttribute('aria-selected',String(i===index));
      tab.tabIndex=i===index?0:-1;
      panels[i].hidden=i!==index;
    });
    if(moveFocus)tabs[index].focus();
    if(motion){
      // Cancel only the previous panel animations, never an in-progress hero entrance.
      panels.forEach(panel=>panel.getAnimations?.().forEach(a=>a.cancel()));
      animate(panels[index],[{opacity:0,transform:'translateY(6px)'},{opacity:1,transform:'translateY(0)'}],{duration:300,easing:'cubic-bezier(.2,.7,.2,1)'});
      if(index===1){
        const bar=panels[index].querySelector('.nh-track i');
        bar?.getAnimations?.().forEach(a=>a.cancel());
        animate(bar,[{transform:'scaleX(0)'},{transform:'scaleX(1)'}],{duration:650,easing:'cubic-bezier(.16,1,.3,1)'});
      }
    }
  }
  panels.forEach((panel,i)=>{
    panel.setAttribute('role','tabpanel');
    panel.setAttribute('aria-labelledby',tabs[i].id);
    panel.tabIndex=0;
  });
  tabs.forEach((tab,i)=>{
    on(tab,'click',()=>{if(active!==i)select(i);});
    on(tab,'keydown',event=>{
      let next=null;
      if(event.key==='ArrowRight')next=(i+1)%tabs.length;
      if(event.key==='ArrowLeft')next=(i+tabs.length-1)%tabs.length;
      if(event.key==='Home')next=0;
      if(event.key==='End')next=tabs.length-1;
      if(next!==null){event.preventDefault();select(next,true);}
    });
  });
  root.classList.add('is-enhanced');tablist.hidden=false;select(0,false,false);
  on(reduced,'change',cancelAnimations);
  function enter() {
    if(dead||reduced.matches)return;
    [...root.querySelectorAll('[data-nh-reveal]')].forEach((el,i)=>{
      animate(el,[{opacity:0,transform:'translateY(15px)'},{opacity:1,transform:'translateY(0)'}],{duration:750,delay:Math.min(i*65,320),easing:'cubic-bezier(.16,1,.3,1)',fill:'backwards'});
    });
  }
  if(typeof IntersectionObserver==='function'){
    observer=new IntersectionObserver(entries=>{
      if(entries.some(entry=>entry.isIntersecting)){enter();observer.disconnect();}
    },{threshold:.08});
    observer.observe(root);
  }else enter();
  root.__nhCleanup=()=>{
    dead=true;observer?.disconnect();cancelAnimations();listeners.forEach(fn=>fn());
    root.classList.remove('is-enhanced');tablist.hidden=true;
    panels.forEach(panel=>{panel.hidden=false;panel.removeAttribute('role');panel.removeAttribute('aria-labelledby');panel.removeAttribute('tabindex');});
    delete root.__nhCleanup;
  };
})();
</script>
