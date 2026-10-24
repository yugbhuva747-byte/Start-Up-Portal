<?php
/**
 * NEXORA / ONE PLATFORM. TWO WAYS FORWARD.
 * Replace includes/home/02_how_it_works.php with this whole file.
 * Scoped HTML + CSS + JavaScript. No Tailwind, GSAP or VS dependency.
 * Two audience journeys; accessible tabs; restrained, once-only motion.
 * Without JavaScript, both journeys remain readable with working CTA links.
 * Existing url() routes are preserved. No implied approval, funding or timeline guarantee.
 * AJAX cleanup: document.getElementById('nx-how')?.__npCleanup?.();
 */
$npEscape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$npRoute = static function ($path) {
    $value = function_exists('url') ? url($path) : $path;
    if (preg_match('~^[a-z][a-z0-9+.-]*:~i', (string) $value) && !preg_match('~^https?://~i', (string) $value)) return $path;
    return $value;
};
$npJourneys = [
    'founder' => [
        'label' => 'For founders',
        'cta' => 'Introduce your company',
        'footer_title' => 'Your next chapter starts with your story.',
        'footer_copy' => 'Create a founder account and put the essentials in place.',
        'prepare_title' => 'A little preparation goes a long way.',
        'prepare_copy' => 'Have these ready as you build your company profile.',
        'prepare' => ['A concise pitch deck', 'Recent traction figures', 'Your fundraising goal'],
        'steps' => [
            [
                'kicker' => 'MAKE IT CLEAR', 'title' => 'Tell the story.<br>Show the substance.',
                'copy' => 'Introduce the problem, your team, and traction to give investors a clear starting point.',
                'detail' => 'Company story · Team · Traction', 'visual' => 'profile',
                'art_title' => 'Your company, clearly.', 'art_sub' => 'THE FOUNDER BRIEF',
                'art_tags' => ['The problem', 'The solution', 'The progress'],
            ],
            [
                'kicker' => 'LOOK FOR ALIGNMENT', 'title' => 'Find shared<br>direction.',
                'copy' => 'Connect with investors aligned with your sector, stage, and ambition.',
                'detail' => 'Sector · Stage · Shared ambition', 'visual' => 'alignment',
                'art_title' => 'A common point of view.', 'art_sub' => 'THE RIGHT CONTEXT',
                'art_tags' => ['Your business', 'Investor focus', 'Shared direction'],
            ],
            [
                'kicker' => 'BUILD THE RELATIONSHIP', 'title' => 'Make the connection.<br>Move with purpose.',
                'copy' => 'Discuss your plans, answer questions, and move forward with purpose.',
                'detail' => 'Introductions · Questions · Next steps', 'visual' => 'conversation',
                'art_title' => 'Let’s explore the fit.', 'art_sub' => 'A CONVERSATION STARTER',
                'art_tags' => ['Share the context', 'Ask the right questions', 'Agree on next steps'],
            ],
        ],
    ],
    'investor' => [
        'label' => 'For investors',
        'intro' => 'Find the businesses that deserve a closer look.',
        'cta' => 'Create your investor profile',
        'footer_title' => 'A considered decision begins with context.',
        'footer_copy' => 'Create an investor account and start with what matters to you.',
        'prepare_title' => 'Start with a clear investment focus.',
        'prepare_copy' => 'Bring a sense of the businesses you want to understand.',
        'prepare' => ['Sectors of interest', 'Preferred company stages', 'Your investment approach'],
        'steps' => [
            [
                'kicker' => 'SET YOUR DIRECTION', 'title' => 'Define your focus.<br>Find your starting point.',
                'copy' => 'Define your sector, stage, and focus to find opportunities that fit.',
                'detail' => 'Interests · Stage · Approach', 'visual' => 'profile',
                'art_title' => 'Your perspective, defined.', 'art_sub' => 'THE INVESTOR BRIEF',
                'art_tags' => ['Sector interests', 'Company stage', 'Investment approach'],
            ],
            [
                'kicker' => 'BUILD YOUR UNDERSTANDING', 'title' => 'Look beyond<br>the pitch.',
                'copy' => 'Review the team, business model, and progress beyond the pitch.',
                'detail' => 'People · Business model · Evidence', 'visual' => 'alignment',
                'art_title' => 'Connect the important details.', 'art_sub' => 'A MORE COMPLETE PICTURE',
                'art_tags' => ['Company story', 'Your perspective', 'Questions to explore'],
            ],
            [
                'kicker' => 'TAKE THE NEXT STEP', 'title' => 'Connect with<br>intention.',
                'copy' => 'Connect directly with founders to explore mutual fit and diligence.',
                'detail' => 'Dialogue · Mutual fit · Further review', 'visual' => 'conversation',
                'art_title' => 'A question worth asking.', 'art_sub' => 'THE NEXT CONVERSATION',
                'art_tags' => ['Understand the ambition', 'Discuss the details', 'Consider the next step'],
            ],
        ],
    ],
];
?>

<section class="np" id="nx-how" data-np aria-labelledby="np-heading">
  <div class="np-wrap">
    <div class="np-topline"><span>02 / HOW NEXORA WORKS</span><span>CONTEXT. ALIGNMENT. CONVERSATION.</span></div>
    <header class="np-header">
      <h2 id="np-heading">One platform.<br><span>Two ways forward.</span></h2>
      <p>Building a business or exploring your next investment?<br class="np-desktop-break"> Start with a clear path from introduction to connection.</p>
    </header>

    <div class="np-switch-row">
      <div class="np-tabs" role="tablist" aria-label="Choose your NEXORA journey" hidden>
        <button id="np-tab-founder" type="button" role="tab" aria-controls="np-founder" aria-selected="true" tabindex="0" data-np-tab="founder"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 16V7l6-3 6 3v9H4ZM7 16v-4h6v4M7 8h.01M13 8h.01"/></svg>For founders</button>
        <button id="np-tab-investor" type="button" role="tab" aria-controls="np-investor" aria-selected="false" tabindex="-1" data-np-tab="investor"><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 15V9m6 6V5m6 10V2M2 18h16"/></svg>For investors</button>
      </div>
      <p class="np-switch-hint"><span aria-hidden="true">↳</span> A different perspective. The same purpose.</p>
    </div>

    <?php foreach ($npJourneys as $role => $journey): ?>
      <div class="np-journey" id="np-<?= $role ?>" data-np-journey="<?= $role ?>">
      
        <ol class="np-steps" aria-label="<?= $npEscape($journey['label']) ?>: three steps">
          <?php foreach ($journey['steps'] as $index => $step): ?>
            <li class="np-step" style="--np-order:<?= $index ?>">
              <div class="np-step-top"><span class="np-step-no"><?= sprintf('%02d', $index + 1) ?></span><span class="np-step-kicker"><?= $npEscape($step['kicker']) ?></span><?php if ($index < 2): ?><svg class="np-next" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3 10h13m-5-5 5 5-5 5"/></svg><?php endif; ?></div>
              <div class="np-illustration np-illustration-<?= $step['visual'] ?>" aria-hidden="true">
                <?php if ($step['visual'] === 'profile'): ?>
                  <div class="np-document"><div class="np-document-head"><span><?= $npEscape($step['art_sub']) ?></span><svg viewBox="0 0 20 20" fill="none"><path d="M4 15V5l12 10V5"/></svg></div><p><?= $npEscape($step['art_title']) ?></p><div class="np-document-rule"></div><div class="np-document-rows"><?php foreach ($step['art_tags'] as $tag): ?><span><i></i><?= $npEscape($tag) ?></span><?php endforeach; ?></div></div><div class="np-document-label"><span>01</span> A clear starting point</div>
                <?php elseif ($step['visual'] === 'alignment'): ?>
                  <div class="np-alignment-label"><?= $npEscape($step['art_sub']) ?></div><svg class="np-circles" viewBox="0 0 300 130" fill="none"><circle cx="111" cy="65" r="54" fill="#e6edf9" stroke="#afc2e6"/><circle cx="189" cy="65" r="54" fill="#f9fbfe" fill-opacity=".7" stroke="#afc2e6"/><path class="np-connection" d="M106 65h88m-7-7 7 7-7 7" stroke="#4469b0" stroke-width="1.5"/><circle cx="111" cy="65" r="4" fill="#4b6fb3"/></svg><div class="np-alignment-names"><span><?= $npEscape($step['art_tags'][0]) ?></span><span><?= $npEscape($step['art_tags'][1]) ?></span></div><div class="np-shared"><span></span><?= $npEscape($step['art_tags'][2]) ?></div>
                <?php else: ?>
                  <div class="np-conversation-head"><span><?= $npEscape($step['art_sub']) ?></span><span>↗</span></div><div class="np-message"><span class="np-avatar">N</span><div><p><?= $npEscape($step['art_title']) ?></p><span><?= $npEscape($step['art_tags'][0]) ?></span></div></div><div class="np-message np-message-reply"><div><p><?= $npEscape($step['art_tags'][1]) ?></p><span><?= $npEscape($step['art_tags'][2]) ?></span></div><span class="np-avatar np-avatar-outline">↗</span></div><div class="np-conversation-rule"></div>
                <?php endif; ?>
              </div>
              <h4><?= implode('<br>', array_map($npEscape, explode('<br>', $step['title']))) ?></h4>
              <p class="np-step-copy"><?= $npEscape($step['copy']) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>

        <div class="np-preparation">
          <div class="np-preparation-title"><span class="np-prepare-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M8 5H5v16h14V5h-3M8 3h8v4H8V3ZM8 12h8M8 16h5"/></svg></span><div><h4><?= $npEscape($journey['prepare_title']) ?></h4><p><?= $npEscape($journey['prepare_copy']) ?></p></div></div>
          <ul aria-label="What to prepare"><?php foreach ($journey['prepare'] as $item): ?><li><span aria-hidden="true"></span><?= $npEscape($item) ?></li><?php endforeach; ?></ul>
        </div>
        <div class="np-start"><div><h4><?= $npEscape($journey['footer_title']) ?></h4><p><?= $npEscape($journey['footer_copy']) ?></p></div><a href="<?= $npEscape($npRoute('auth/register.php?role=' . $role)) ?>"><?= $npEscape($journey['cta']) ?><svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12M11 5l5 5-5 5"/></svg></a></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<style>
.np{--np-ink:#182435;--np-muted:#5e6d82;--np-blue:#2f57d5;--np-line:#e3e9f0;padding:88px 0 45px;background:#fff;color:var(--np-ink);border-bottom:1px solid var(--np-line);font-family:'Satoshi',Inter,Arial,sans-serif;-webkit-font-smoothing:antialiased}
.np *,.np *::before,.np *::after{box-sizing:border-box}.np p,.np h2,.np h3,.np h4{margin:0}.np a{color:inherit;text-decoration:none}.np button{font:inherit}.np svg{display:block;flex-shrink:0}.np [hidden]{display:none!important}.np-wrap{width:min(1240px,calc(100% - 80px));margin-inline:auto}
.np-topline{display:flex;align-items:center;justify-content:space-between;gap:20px;padding-bottom:18px;border-bottom:1px solid var(--np-line);font-size:12px;letter-spacing:.12em;line-height:1.6;color:#64748b;font-weight:600}.np-topline>span:first-child{color:#2f57d5;font-weight:700}.np-header{display:flex;align-items:flex-end;justify-content:space-between;gap:50px;padding:35px 0 30px}.np h2{font-size:clamp(40px,4.5vw,62px);font-weight:600;line-height:1.1;letter-spacing:-.05em}.np h2>span{color:#64748b;font-weight:500}.np-header>p{max-width:390px;font-size:16px;line-height:1.75;color:var(--np-muted);padding-bottom:5px;text-wrap:pretty}
.np-switch-row{display:flex;justify-content:space-between;align-items:center;gap:25px;padding:12px 0 33px}.np-tabs{display:inline-flex;gap:4px;padding:5px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px}.np-tabs button{display:flex;align-items:center;justify-content:center;gap:9px;background:none;border:1px solid transparent;border-radius:7px;cursor:pointer;font-size:14px;font-weight:600;padding:11px 22px;color:#64748b;line-height:1.3;transition:color .2s,background .2s,border-color .2s,box-shadow .2s}.np-tabs svg{width:18px;height:18px;stroke:currentColor;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}.np-tabs button[aria-selected=true]{background:#fff;color:#2f57d5;border-color:#cbd5e1;box-shadow:0 2px 6px #243b5d12}.np-tabs button:hover{color:#2f57d5}.np-switch-hint{font-size:13px;line-height:1.6;color:#64748b;font-weight:500;display:flex;align-items:center;gap:11px}.np-switch-hint>span{font-size:22px;color:#2f57d5}
.np-journey+.np-journey{margin-top:50px}.np.is-enhanced .np-journey+.np-journey{margin-top:0}.np-journey-intro{display:flex;justify-content:space-between;gap:20px;align-items:center;padding-bottom:28px}.np-journey-intro h3{font-size:18px;font-weight:700}.np.is-enhanced .np-journey-intro h3{display:none}.np-journey-intro>p{font-size:16px;color:#334155;font-weight:500;line-height:1.6}.np-journey-intro>span{font-size:11px;font-weight:700;letter-spacing:.12em;color:#64748b;white-space:nowrap}
.np-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:38px;list-style:none;padding:0;margin:0}.np-step{min-width:0;position:relative}.np-step-top{display:flex;align-items:center;gap:12px;padding:0 0 19px;position:relative}.np-step-no{font-size:14px;line-height:1;font-weight:700;color:#2f57d5;width:36px;height:36px;border:1.5px solid #cbd5e1;border-radius:50%;display:grid;place-items:center;background:#fff;font-variant-numeric:tabular-nums;flex-shrink:0}.np-step-kicker{font-size:11px;letter-spacing:.12em;color:#64748b;font-weight:700}.np-next{margin-left:auto;width:18px;height:18px;stroke:#94a3b8;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}.np-step-top::after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:linear-gradient(90deg,#94a3b8,#e2e8f0)}
.np-illustration{height:193px;position:relative;overflow:hidden;background:#f8fafc;margin:18px 0 25px;border:1px solid #e2e8f0;border-radius:8px;user-select:none}.np-document{position:absolute;left:10%;right:12%;top:18px;padding:15px 18px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;box-shadow:0 8px 18px -12px #2c477e50;transform:rotate(-3deg)}.np-document-head{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:10px;font-weight:700;letter-spacing:.1em;color:#64748b}.np-document-head svg{width:16px;height:16px;stroke:#2f57d5;stroke-width:1.5}.np-document>p{font-size:14px;line-height:1.4;letter-spacing:-.02em;margin-top:8px;color:#1e293b;font-weight:600}.np-document-rule{height:1px;background:#e2e8f0;margin:10px 0}.np-document-rows{display:flex;flex-direction:column;gap:7px}.np-document-rows>span{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:550;line-height:1.3;color:#475569}.np-document-rows i{display:block;width:5px;height:5px;background:#2f57d5;border-radius:1px}.np-document-label{display:flex;align-items:center;gap:8px;position:absolute;right:6%;bottom:15px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;font-size:11px;font-weight:600;line-height:1.4;color:#1d4ed8;padding:8px 12px;transform:rotate(2deg)}.np-document-label>span{font-size:10px;font-weight:700;color:#2563eb;padding-right:6px;border-right:1px solid #bfdbfe}
.np-alignment-label{position:absolute;top:15px;left:0;right:0;text-align:center;font-size:10px;font-weight:700;letter-spacing:.12em;color:#64748b}.np-circles{position:absolute;width:83%;height:118px;top:25px;left:8.5%;overflow:visible}.np-alignment-names{position:absolute;top:140px;left:8%;right:8%;display:flex;justify-content:space-around;gap:15px;text-align:center;font-size:11px;font-weight:600;color:#475569}.np-shared{position:absolute;bottom:10px;left:0;right:0;display:flex;justify-content:center;align-items:center;gap:6px;font-size:11px;font-weight:600;color:#1e293b}.np-shared>span{width:5px;height:5px;border-radius:50%;background:#2f57d5}
.np-conversation-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:15px 18px 0;font-size:10px;font-weight:700;letter-spacing:.12em;color:#64748b}.np-conversation-head>span:last-child{font-size:16px;color:#2f57d5}.np-message{display:flex;align-items:center;gap:10px;position:absolute;left:6%;right:14%;top:46px}.np-avatar{height:28px;width:28px;display:grid;place-items:center;border-radius:50%;background:#eff6ff;color:#2f57d5;font-size:12px;font-weight:700;flex-shrink:0;border:1px solid #bfdbfe}.np-message>div{padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;flex:1;box-shadow:0 3px 8px #24416b08;min-width:0}.np-message p{font-size:11.5px;font-weight:600;color:#1e293b;line-height:1.4}.np-message>div>span{display:block;font-size:10px;font-weight:500;color:#64748b;margin-top:2px;line-height:1.4}.np-message-reply{top:108px;left:14%;right:6%}.np-message-reply>div{background:#f8fafc;border-color:#cbd5e1}.np-avatar-outline{background:#fff;font-size:13px;border-color:#cbd5e1}.np-conversation-rule{position:absolute;height:1px;width:28px;background:#cbd5e1;bottom:12px;left:calc(50% - 14px)}
.np-step h4{font-size:clamp(23px,2.2vw,30px);font-weight:650;line-height:1.24;letter-spacing:-.04em;color:#0f172a}.np-step-copy{font-size:15px;line-height:1.75;color:var(--np-muted);margin-top:14px!important;text-wrap:pretty}
.np-preparation{margin-top:37px;padding:24px 28px;display:flex;align-items:center;justify-content:space-between;gap:30px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px}.np-preparation-title{display:flex;align-items:center;gap:14px}.np-prepare-icon{display:grid;place-items:center;height:42px;width:42px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;flex-shrink:0}.np-prepare-icon svg{width:22px;height:22px;stroke:#2f57d5;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}.np-preparation h4{font-size:15px;line-height:1.5;font-weight:650;letter-spacing:-.015em;color:#0f172a}.np-preparation p{font-size:13px;line-height:1.6;color:#64748b;margin-top:4px}.np-preparation ul{display:flex;align-items:center;gap:14px 26px;flex-wrap:wrap;list-style:none;padding:0;margin:0;max-width:55%}.np-preparation li{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:550;line-height:1.5;color:#334155}.np-preparation li>span{width:6px;height:6px;border:1.5px solid #2f57d5;transform:rotate(45deg);flex-shrink:0}
.np-start{display:flex;align-items:center;justify-content:space-between;gap:30px;padding-top:28px}.np-start h4{font-size:22px;line-height:1.4;font-weight:650;letter-spacing:-.03em;color:#0f172a}.np-start p{font-size:14px;line-height:1.7;color:#64748b;margin-top:4px}.np-start>a{display:inline-flex;align-items:center;justify-content:space-between;gap:20px;background:var(--np-blue);color:#fff;padding:15px 24px;border:1px solid var(--np-blue);border-radius:8px;font-size:14px;line-height:1.4;font-weight:600;flex-shrink:0;transition:background .2s,transform .2s}.np-start>a>svg{width:18px;height:18px;stroke:currentColor;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round;transition:transform .2s}.np-start>a:hover{background:#2449bd;transform:translateY(-2px)}.np-start>a:hover>svg{transform:translateX(2px)}.np :is(a,button,[role=tabpanel]):focus-visible{outline:2px solid var(--np-blue);outline-offset:5px}
@media(max-width:1100px){.np-wrap{width:calc(100% - 64px)}.np-steps{gap:25px}.np-step-kicker{font-size:10px}.np-step-top{gap:9px}.np-step h4{font-size:24px}.np-step-copy{font-size:14px;line-height:1.7}.np-document{left:8%;right:8%;padding-inline:14px}.np-document>p{font-size:13px}.np-preparation{padding:22px;gap:24px}.np-preparation ul{max-width:48%;gap:12px 18px}.np-header{gap:35px}.np-header>p{font-size:14px;max-width:320px}}
@media(max-width:850px){.np{padding-top:65px}.np-header{align-items:flex-start;flex-direction:column;gap:20px;padding-block:28px 24px}.np-header>p{max-width:440px;font-size:14px}.np h2{font-size:46px}.np-desktop-break{display:none}.np-switch-hint{font-size:12px}.np-switch-row{gap:18px;padding-bottom:26px}.np-tabs button{font-size:13px;padding:10px 16px}.np-journey-intro>p{font-size:14px}.np-journey-intro>span{font-size:10px}.np-steps{gap:20px}.np-step h4{font-size:22px}.np-step-kicker{max-width:140px;line-height:1.5;font-size:10px}.np-step-top{min-height:50px}.np-next{width:16px;height:16px}.np-illustration{height:172px;margin-bottom:21px}.np-document{top:18px;padding:12px}.np-document>p{font-size:12px}.np-document-head{font-size:9px}.np-document-rows>span{font-size:10px}.np-document-label{font-size:10px;bottom:10px;right:5%;padding:6px 10px}.np-alignment-label{font-size:9px}.np-circles{top:20px;height:105px;width:94%;left:3%}.np-alignment-names{top:125px;font-size:10px}.np-shared{font-size:10px}.np-conversation-head{padding:12px 14px 0;font-size:9px}.np-message{left:5%;right:8%;gap:8px;top:40px}.np-message-reply{top:98px;left:9%;right:5%}.np-avatar{width:24px;height:24px;font-size:10px}.np-message>div{padding:8px 12px}.np-message p{font-size:10px}.np-message>div>span{font-size:9px}.np-conversation-rule{bottom:10px}.np-preparation{align-items:flex-start;flex-direction:column;gap:18px}.np-preparation ul{max-width:none;padding-left:0}.np-start h4{font-size:18px}.np-start>a{font-size:13px;padding:14px 20px;gap:18px}}
@media(max-width:650px){.np{padding:48px 0 30px}.np-wrap{width:calc(100% - 40px)}.np-topline{font-size:10px;letter-spacing:.08em;gap:15px;align-items:flex-start;padding-bottom:15px}.np-topline>span:last-child{max-width:160px;text-align:right;font-size:10px}.np-header{padding-block:25px 24px;gap:18px}.np h2{font-size:clamp(36px,9vw,48px)}.np-header>p{font-size:14px;line-height:1.75;max-width:360px}.np-switch-row{align-items:flex-start;flex-direction:column;gap:14px;padding-top:0;padding-bottom:25px}.np-tabs{width:100%;max-width:360px;gap:4px}.np-tabs button{flex:1;font-size:13px;padding:12px 10px;gap:8px}.np-tabs svg{width:16px;height:16px}.np-switch-hint{font-size:12px;gap:8px}.np-switch-hint>span{font-size:18px}.np-journey-intro{padding-bottom:22px;align-items:flex-start;flex-direction:column;gap:8px}.np-journey-intro>p{font-size:14px;max-width:340px;line-height:1.6}.np-journey-intro>span{display:none}.np-steps{grid-template-columns:1fr;gap:30px}.np-step{display:grid;grid-template-columns:1fr;min-width:0}.np-step-top{padding-bottom:14px;min-height:0;gap:11px}.np-step-no{width:32px;height:32px;font-size:12px}.np-step-kicker{font-size:11px;max-width:none;letter-spacing:.1em}.np-next{width:16px;height:16px;transform:rotate(90deg)}.np-illustration{height:178px;margin:16px 0 21px}.np-document{left:12%;right:12%;top:16px;padding:12px 16px}.np-document-head{font-size:9px}.np-document>p{font-size:13px}.np-document-rows>span{font-size:11px}.np-document-label{font-size:10px;bottom:12px;right:8%;padding:7px 10px}.np-alignment-label{font-size:9px}.np-circles{width:80%;left:10%;height:114px;top:18px}.np-alignment-names{top:128px;font-size:10px}.np-shared{font-size:10px}.np-conversation-head{font-size:9px;padding:14px 18px 0}.np-message{left:8%;right:12%;gap:8px;top:42px}.np-message-reply{left:14%;right:8%;top:102px}.np-avatar{width:26px;height:26px;font-size:11px}.np-message>div{padding:10px 14px}.np-message p{font-size:11px}.np-message>div>span{font-size:10px}.np-step h4{font-size:26px;line-height:1.2}.np-step-copy{font-size:14px;line-height:1.75;margin-top:12px!important;max-width:440px}.np-preparation{margin-top:28px;padding:20px 18px;gap:16px}.np-preparation-title{gap:12px;align-items:flex-start}.np-preparation h4{font-size:14px}.np-preparation p{font-size:12px}.np-preparation ul{padding-left:0;flex-direction:column;align-items:flex-start;gap:10px}.np-preparation li{font-size:12px}.np-start{flex-direction:column;align-items:flex-start;gap:18px;padding-top:24px}.np-start h4{font-size:20px;max-width:320px}.np-start p{font-size:13px;max-width:330px}.np-start>a{font-size:13px;padding:14px 20px;gap:20px}}
@media(max-width:359px){.np-wrap{width:calc(100% - 32px)}.np-document{left:8%;right:8%}.np h2{font-size:34px}.np-step h4{font-size:24px}.np-message{left:5%;right:8%}.np-message-reply{left:10%;right:5%}.np-tabs button{font-size:12px;gap:6px}}
@media(prefers-reduced-motion:reduce){.np *,.np *::before,.np *::after{animation:none!important;transition:none!important}.np-start>a:hover{transform:none}}
</style>

<script>
(() => {
    'use strict';
    const root = document.querySelector('[data-np]');
    if (!root) return;
    root.__npCleanup?.();
    const tabs = [...root.querySelectorAll('[data-np-tab]')];
    const panels = [...root.querySelectorAll('[data-np-journey]')];
    const tablist = root.querySelector('.np-tabs');
    const reduce = matchMedia('(prefers-reduced-motion: reduce)');
    const listeners = [], animations = new Set();
    let active = 0, dead = false, observer = null;
    const on = (el, type, callback) => {el.addEventListener(type, callback);listeners.push(() => el.removeEventListener(type,callback));};
    function motion(el, frames, options) {
        if (!el || dead || reduce.matches || typeof el.animate !== 'function') return;
        const animation = el.animate(frames, options);
        animations.add(animation);
        animation.onfinish = () => animations.delete(animation);
        animation.oncancel = () => animations.delete(animation);
    }
    function stopMotion() {animations.forEach(animation=>animation.cancel());animations.clear();}
    function reveal(el, delay=0) {
        motion(el,[{opacity:0,transform:'translateY(13px)'},{opacity:1,transform:'translateY(0)'}],{duration:620,delay,easing:'cubic-bezier(.16,1,.3,1)',fill:'backwards'});
    }
    function select(index, focus=false, animate=true) {
        if(dead)return;
        stopMotion();active=index;
        tabs.forEach((tab,i)=>{
            tab.setAttribute('aria-selected',String(i===index));tab.tabIndex=i===index?0:-1;
            panels[i].hidden=i!==index;
        });
        if(focus)tabs[index].focus();
        if(animate){
            reveal(panels[index].querySelector('.np-journey-intro'));
            // Animate only visible steps. The observer handles below-the-fold content.
            [...panels[index].querySelectorAll('.np-step')].forEach((step,i)=>{
                const rect=step.getBoundingClientRect();
                if(rect.top<innerHeight && rect.bottom>0){observer?.unobserve(step);reveal(step,i*60);}
            });
        }
    }
    panels.forEach((panel,i)=>{panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby',tabs[i].id);panel.tabIndex=0;});
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
    on(reduce,'change',stopMotion);
    if(typeof IntersectionObserver==='function'){
        observer=new IntersectionObserver(entries=>{
            entries.forEach(entry=>{
                if(!entry.isIntersecting || entry.target.closest('[data-np-journey]')?.hidden)return;
                reveal(entry.target);
                // A single line-draw explains the alignment visual; it never loops.
                const line=entry.target.querySelector('.np-connection');
                if(line)motion(line,[{strokeDasharray:'120',strokeDashoffset:120},{strokeDasharray:'120',strokeDashoffset:0}],{duration:750,delay:170,easing:'ease-out'});
                observer.unobserve(entry.target);
            });
        },{threshold:.12});
        [root.querySelector('.np-header'),...root.querySelectorAll('.np-step')].forEach(el=>observer.observe(el));
    }
    root.__npCleanup=()=>{
        dead=true;observer?.disconnect();stopMotion();listeners.forEach(fn=>fn());
        panels.forEach(panel=>{panel.hidden=false;panel.removeAttribute('role');panel.removeAttribute('aria-labelledby');panel.removeAttribute('tabindex');});
        root.classList.remove('is-enhanced');tablist.hidden=true;delete root.__npCleanup;
    };
})();
</script>
