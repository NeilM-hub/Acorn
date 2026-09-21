<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page{margin:18mm 17mm 18mm}
*{box-sizing:border-box}
body{font-family:DejaVu Sans,sans-serif;color:#1c2932;font-size:9.4pt;line-height:1.48;margin:0}
h1,h2,h3,p{margin-top:0}
h1{font-size:30pt;line-height:1.04;color:#084e87;margin:0 0 13px;letter-spacing:-.4px}
h2{font-size:18pt;line-height:1.15;color:#084e87;margin:0 0 10px}
h3{font-size:11.8pt;line-height:1.28;color:#163a55;margin:0 0 6px}
a{color:inherit}
.footer{position:fixed;bottom:-10mm;left:0;right:0;padding-top:4px;border-top:1px solid #d9e4ed;color:#71808c;font-size:7.2pt}
.cover{page-break-after:always;position:relative;min-height:245mm;padding:0}
.cover-hero{margin:-18mm -17mm 0;padding:18mm 17mm 19mm;background:#084e87;color:#fff}
.cover-logo-wrap{display:inline-block;padding:7px 9px;border-radius:8px;background:#fff;margin-bottom:20mm}
.cover-logo{display:block;max-width:190px;max-height:70px;margin:0}
.cover-kicker{font-size:8.5pt;text-transform:uppercase;letter-spacing:1.35px;color:#d8ebf8;font-weight:bold;margin-bottom:11px}
.cover-hero h1{max-width:460px;color:#fff;font-size:33pt;line-height:1.02;margin:0 0 14px;letter-spacing:-.5px}
.cover-subtitle{max-width:470px;font-size:12.5pt;line-height:1.55;color:#edf6fb;margin-bottom:18px}
.cover-authority{display:inline-block;padding:7px 10px;border:1px solid #6ea0c3;border-radius:14px;background:#0d5f9f;color:#fff;font-size:8.8pt;font-weight:bold}
.cover-body{padding:18mm 7mm 0}
.cover-complete{font-size:12pt;color:#084e87;font-weight:bold;margin-bottom:7px}
.cover-next{font-size:19pt;line-height:1.2;color:#173f5b;font-weight:bold;margin-bottom:23mm}
.cover-meta{padding-top:14px;border-top:1px solid #d9e4ed}
.cover-company{font-size:15pt;color:#183b55;font-weight:bold;margin-bottom:7px}
.meta{color:#647887;margin-bottom:4px}
.prepared{margin-top:14px;color:#084e87;font-weight:bold}
.section{margin-bottom:19px}
.section-heading{margin-bottom:11px;page-break-inside:avoid;page-break-after:avoid}
.section-intro{page-break-inside:avoid;page-break-after:avoid;margin-bottom:8px}
.eyebrow{font-size:7.4pt;text-transform:uppercase;letter-spacing:1px;color:#60788a;font-weight:bold;margin-bottom:5px;page-break-after:avoid}
.lead{font-size:10.5pt;line-height:1.55;color:#445c6d;margin-bottom:16px}
.counts{width:100%;border-collapse:separate;border-spacing:7px 0;margin:0 -7px 16px}
.counts td{width:33.33%;border:1px solid #d9e4ed;border-radius:10px;padding:14px 11px;background:#f5f9fc;vertical-align:top}
.count-number{display:block;font-size:25pt;font-weight:bold;color:#084e87;line-height:1}
.count-label{display:block;margin-top:5px;font-size:8.7pt;color:#465f71}
.top-actions-title{font-size:11pt;font-weight:bold;color:#183b55;margin:17px 0 8px}
.top-actions{width:100%;border-collapse:collapse;margin-bottom:16px}
.top-actions td{padding:8px 9px;border-bottom:1px solid #dfe8ef;vertical-align:middle}
.top-actions td:first-child{width:28px;padding-left:0}
.action-number{display:inline-block;width:22px;height:22px;line-height:22px;text-align:center;border-radius:50%;background:#084e87;color:#fff;font-weight:bold;font-size:8pt}
.action-name{font-weight:bold;color:#233d50}
.pillars{width:100%;border-collapse:separate;border-spacing:7px;margin:0 -7px 9px}
.pillars td{width:50%;border:1px solid #d9e4ed;border-radius:9px;padding:11px;vertical-align:top}
.pillar-name{font-weight:bold;font-size:10.5pt;margin-bottom:5px;color:#263d4d}
.status{display:inline-block;border-radius:12px;padding:3px 8px;font-size:7.6pt;font-weight:bold}
.status-priority{background:#fde9e7;color:#8d271f}
.status-review{background:#fff3d8;color:#76500c}
.status-addressed{background:#e8f2fb;color:#084e87}
.status-not_assessed{background:#eef1f0;color:#596762}
.priority-card{page-break-inside:avoid;border:1px solid #d7e3ec;border-radius:10px;margin:0 0 9px;background:#fff}
.priority-head{padding:9px 11px 7px;background:#f6fafe;border-bottom:1px solid #d7e3ec}
.priority-label{font-size:7.1pt;text-transform:uppercase;letter-spacing:.8px;color:#a3362c;font-weight:bold;margin-bottom:3px}
.priority-body{padding:9px 11px}
.info-block{margin:0 0 6px}
.info-label{display:block;font-size:7.6pt;text-transform:uppercase;letter-spacing:.55px;color:#60788a;font-weight:bold;margin-bottom:2px}
.info-label.next{color:#084e87}
.info-text{margin:0;color:#344e61}
.good-box{margin-top:7px;padding:7px 9px;border-left:3px solid #084e87;background:#f2f8fd;border-radius:4px}
.help-box{margin-top:7px;padding:7px 9px;border:1px solid #d6e5ef;background:#fbfdff;border-radius:5px;color:#344e61}
.help-box strong{color:#084e87}
.action-meta{width:100%;border-collapse:collapse;margin-top:7px;border-top:1px solid #e2eaf0}
.action-meta td{padding:6px 7px 0 0;color:#536b7c;font-size:7.8pt;vertical-align:top}
.action-meta strong{color:#29465b}
.review-grid{width:100%;border-collapse:separate;border-spacing:7px;margin:0 -7px}
.review-grid td{width:50%;padding:11px;border:1px solid #d9e4ed;border-radius:9px;background:#fbfcfd;vertical-align:top}
.review-grid h3{font-size:10pt}
.review-grid p{margin-bottom:0;color:#465f71}
.next-steps{width:100%;border-collapse:separate;border-spacing:7px;margin:0 -7px 10px}
.next-steps td{width:50%;padding:10px;border:1px solid #d9e4ed;border-radius:8px;background:#fbfdff;vertical-align:top}
.next-step-number{display:inline-block;width:18px;height:18px;line-height:18px;text-align:center;border-radius:50%;background:#084e87;color:#fff;font-size:7pt;font-weight:bold;margin-right:5px}
.next-steps strong{color:#1c4059}
.next-steps p{margin:5px 0 0;color:#465f71}
.positive-table{width:100%;border-collapse:separate;border-spacing:7px;margin:0 -7px}
.positive-table td{width:50%;padding:9px 10px;background:#f3f8fc;border:1px solid #e2ebf1;border-radius:7px;vertical-align:top;color:#334d60}
.tick{color:#084e87;font-weight:bold;margin-right:5px}
.support-options{width:100%;border-collapse:separate;border-spacing:7px;margin:8px -7px}
.support-options td{width:50%;padding:9px 10px;border:1px solid rgba(255,255,255,.35);border-radius:7px;vertical-align:top}
.support-options strong{display:block;color:#fff;margin-bottom:3px}
.support-options p{margin:0;color:#eef6fb}
.support{page-break-before:always;page-break-inside:avoid;min-height:218mm;margin:0;padding:24px 20px;border-radius:10px;background:#084e87;color:#fff}
.support--full-page{min-height:218mm}
.authority-box{margin:10px 0;padding:10px;border:1px solid rgba(255,255,255,.35);border-radius:7px;background:#0b5b95}
.authority-box h3{color:#fff;margin-bottom:5px}
.authority-box p{color:#edf6fb;margin-bottom:7px}
.authority-list{width:100%;border-collapse:separate;border-spacing:6px;margin:0 -6px}
.authority-list td{width:33.33%;padding:8px;border:1px solid rgba(255,255,255,.25);border-radius:6px;vertical-align:top}
.authority-list strong{display:block;color:#fff;margin-bottom:3px}
.authority-list p{margin:0;color:#edf6fb;font-size:8pt}
.support h2{color:#fff;margin-bottom:8px;font-size:19pt;line-height:1.15}
.support p{margin-bottom:8px}
.support .cta{font-weight:bold;margin-top:11px}
.support .cta a{display:inline-block;color:#084e87;background:#fff;padding:7px 10px;border-radius:5px;text-decoration:none;margin-right:7px;margin-bottom:5px}
.support .cta a.secondary{color:#fff;background:transparent;border:1px solid rgba(255,255,255,.65)}
.disclaimer{margin-top:14px;padding-top:9px;border-top:1px solid #dce6ed;color:#6e7e89;font-size:7.5pt}
</style>
</head>
<body>
<div class="footer"><?php echo esc_html($report['branding']['pdf_footer']); ?></div>

<section class="cover">
<div class="cover-hero">
<?php if($report['branding']['logo_data']):?><span class="cover-logo-wrap"><img class="cover-logo" src="<?php echo esc_attr($report['branding']['logo_data']);?>" alt="Acorn Safety Services"></span><?php endif;?>
<div class="cover-kicker">Your Acorn Safety Healthcheck</div>
<h1>Your Health &amp; Safety<br>Healthcheck</h1>
<p class="cover-subtitle">A practical snapshot of what's working, what needs attention and what to do next.</p>
<div class="cover-authority">Specialist guidance across Health &amp; Safety, Fire Safety, Legionella and Asbestos.</div>
</div>
<div class="cover-body">
<p class="cover-complete">Your Healthcheck is complete.</p>
<p class="cover-next">Now turn the findings into action.</p>
<div class="cover-meta">
<p class="cover-company"><?php echo esc_html($report['meta']['company']);?></p>
<p class="meta">Assessment date: <?php echo esc_html($report['meta']['assessment_date']);?></p>
<p class="prepared">Prepared by Acorn Safety Services</p>
<p class="meta"><?php echo esc_html($report['branding']['phone']);?> &nbsp;|&nbsp; <?php echo esc_html($report['branding']['website']);?></p>
</div>
</div>
</section>

<section class="section">
<div class="section-heading">
<div class="eyebrow">Executive summary</div>
<h2>Your Healthcheck at a glance</h2>
</div>
<p class="lead"><?php echo esc_html($report['executive']['summary_text']);?></p>

<table class="counts"><tr>
<td><span class="count-number"><?php echo (int)$report['summary']['priority_count'];?></span><span class="count-label"><?php echo esc_html($report['executive']['priority_label']);?></span></td>
<td><span class="count-number"><?php echo (int)$report['summary']['review_count'];?></span><span class="count-label"><?php echo esc_html($report['executive']['review_label']);?></span></td>
<td><span class="count-number"><?php echo (int)$report['summary']['addressed_count'];?></span><span class="count-label"><?php echo esc_html($report['executive']['addressed_label']);?></span></td>
</tr></table>

<?php if(!empty($report['executive']['top_actions'])):?>
<div class="top-actions-title">What needs your attention first</div>
<table class="top-actions">
<?php foreach(($report['executive']['top_actions'] ?? []) as $index=>$item):?><tr>
<td><span class="action-number"><?php echo (int)$index+1;?></span></td>
<td><span class="action-name"><?php echo esc_html($item['display_heading']);?></span></td>
<td style="text-align:right"><span class="status status-<?php echo esc_attr($item['finding_status']);?>"><?php echo esc_html($item['finding_status']==='priority'?'Priority action':'Review recommended');?></span></td>
</tr><?php endforeach;?>
</table>
<?php endif;?>

<table class="pillars">
<?php foreach(array_chunk($report['pillars'],2) as $row):?><tr>
<?php foreach($row as $pillar):?><td<?php echo count($report['pillars'])===1?' class="pillar-wide" colspan="2"':'';?>>
<div class="pillar-name"><?php echo esc_html($pillar['label']);?></div>
<span class="status status-<?php echo esc_attr($pillar['status']);?>"><?php echo esc_html($pillar['display_status']);?></span>
</td><?php endforeach;?>
</tr><?php endforeach;?>
</table>
<p class="disclaimer"><?php echo esc_html($report['disclaimer']);?></p>
</section>

<section class="section">
<div class="section-intro">
<div class="eyebrow">Deal with these first</div>
<h2>Your priority action plan</h2>
</div>
<?php if(!$report['priority']):?><p class="lead">No priority actions were identified from your responses.</p><?php endif;?>
<?php foreach($report['priority'] as $index=>$item):?>
<article class="priority-card">
<div class="priority-head">
<div class="priority-label">Priority <?php echo (int)$index+1;?></div>
<h3><?php echo esc_html($item['display_heading']);?></h3>
</div>
<div class="priority-body">
<div class="info-block"><span class="info-label">What we found</span><p class="info-text"><?php echo esc_html($item['display_identified']);?></p></div>
<div class="info-block"><span class="info-label next">Do this next</span><p class="info-text"><?php echo esc_html($item['display_action']);?></p></div>
<?php if(!empty($item['display_why'])):?><div class="info-block"><span class="info-label">Why it matters</span><p class="info-text"><?php echo esc_html($item['display_why']);?></p></div><?php endif;?>
<?php if(!empty($item['display_good_looks'])):?><div class="good-box"><strong>What good looks like:</strong> <?php echo esc_html($item['display_good_looks']);?></div><?php endif;?>
<?php if(!empty($item['display_acorn_help'])):?><div class="help-box"><strong>How Acorn can help:</strong> <?php echo esc_html($item['display_acorn_help']);?></div><?php endif;?>
<table class="action-meta"><tr><td><strong>Suggested owner:</strong> <?php echo esc_html($item['display_owner']);?></td><td><strong>Suggested priority:</strong> <?php echo esc_html($item['display_priority']);?></td></tr></table>
</div>
</article>
<?php endforeach;?>
</section>

<section class="section">
<div class="section-intro">
<div class="eyebrow">Worth checking</div>
<h2>Other things worth reviewing</h2>
<p class="lead">These are not priority actions, but your answers suggest they are worth checking or confirming.</p>
</div>
<?php if(!$report['review']):?><p>No additional review items were identified.</p><?php else:?>
<table class="review-grid">
<?php foreach(array_chunk($report['review'],2) as $row):?><tr>
<?php foreach($row as $item):?><td>
<h3><?php echo esc_html($item['display_heading']);?></h3>
<p><?php echo esc_html($item['display_action']);?></p>
</td><?php endforeach;?>
<?php if(count($row)===1):?><td></td><?php endif;?>
</tr><?php endforeach;?>
</table>
<?php endif;?>
</section>

<?php if(!empty($report['next_steps'])):?><section class="section">
<div class="section-intro">
<div class="eyebrow">Practical next steps</div>
<h2>What should you do next?</h2>
<p class="lead">Use this report as a working action plan rather than a one-off checklist.</p>
</div>
<table class="next-steps">
<?php foreach(array_chunk($report['next_steps'],2) as $row):?><tr>
<?php foreach($row as $step):?><td>
<div><span class="next-step-number"><?php echo (int)(array_search($step,$report['next_steps'],true)+1);?></span><strong><?php echo esc_html($step['title']);?></strong></div>
<p><?php echo esc_html($step['body']);?></p>
</td><?php endforeach;?>
<?php if(count($row)===1):?><td></td><?php endif;?>
</tr><?php endforeach;?>
</table>
</section><?php endif;?>

<section class="section">
<div class="section-intro">
<div class="eyebrow">Positive findings</div>
<h2>What you're already doing well</h2>
<p class="lead">Your answers did not identify an obvious gap in these areas.</p>
</div>
<?php if(!$report['addressed']):?><p>No positive findings were recorded in this assessment.</p><?php else:?>
<table class="positive-table">
<?php foreach(array_chunk($report['addressed'],2) as $row):?><tr>
<?php foreach($row as $item):?><td><span class="tick">&#10003;</span><?php echo esc_html($item['display_heading']);?></td><?php endforeach;?>
<?php if(count($row)===1):?><td></td><?php endif;?>
</tr><?php endforeach;?>
</table>
<?php endif;?>

<div class="support support--full-page">
<div class="eyebrow" style="color:#d6eafa">How Acorn Safety Services can help</div>
<h2><?php echo esc_html($report['support']['heading']);?></h2>
<p><?php echo esc_html($report['support']['body']);?></p>
<?php if(!empty($report['support']['authority_heading'])):?><div class="authority-box">
<h3><?php echo esc_html($report['support']['authority_heading']);?></h3>
<?php if(!empty($report['support']['authority_intro'])):?><p><?php echo esc_html($report['support']['authority_intro']);?></p><?php endif;?>
<?php if(!empty($report['support']['authority_points'])):?><table class="authority-list"><tr>
<?php foreach($report['support']['authority_points'] as $point):?><td><strong><?php echo esc_html($point['title']);?></strong><p><?php echo esc_html($point['body']);?></p></td><?php endforeach;?>
</tr></table><?php endif;?>
</div><?php endif;?>
<?php if(!empty($report['support']['options'] ?? [])):?><table class="support-options">
<?php foreach(array_chunk(($report['support']['options'] ?? []),2) as $row):?><tr>
<?php foreach($row as $option):?><td><strong><?php echo esc_html($option['label']);?></strong><p><?php echo esc_html($option['body']);?></p></td><?php endforeach;?>
<?php if(count($row)===1):?><td></td><?php endif;?>
</tr><?php endforeach;?>
</table><?php endif;?>
<p class="cta">
<a href="<?php echo esc_url($report['support']['cta_url']);?>"><?php echo esc_html($report['support']['cta_label']);?></a>
<?php if(!empty($report['support']['secondary_cta_url']) && !empty($report['support']['secondary_cta_label'])):?><a class="secondary" href="<?php echo esc_url($report['support']['secondary_cta_url']);?>"><?php echo esc_html($report['support']['secondary_cta_label']);?></a><?php endif;?>
</p>
<p><?php echo esc_html($report['branding']['phone']);?> &nbsp;|&nbsp; <?php echo esc_html($report['branding']['website']);?></p>
</div>

<p class="disclaimer"><?php echo esc_html($report['disclaimer']);?></p>
</section>
</body>
</html>
