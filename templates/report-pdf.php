<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page{margin:28mm 18mm 20mm}
body{font-family:DejaVu Sans,sans-serif;color:#17221f;font-size:9.5pt;line-height:1.42}
h1,h2,h3,p{margin-top:0}
h1{font-size:27pt;line-height:1.08;color:#155c4a;margin-bottom:10px}
h2{font-size:18pt;color:#155c4a;margin:0 0 12px}
h3{font-size:11.5pt;color:#17221f;margin:0 0 5px}
.cover{page-break-after:always;padding-top:58mm}
.cover-kicker{font-size:10pt;text-transform:uppercase;letter-spacing:1.2px;color:#5e746c;margin-bottom:16px}
.cover-subtitle{font-size:14pt;line-height:1.45;max-width:470px;color:#40544d;margin-bottom:34px}
.company{font-size:17pt;font-weight:bold;color:#17221f;margin-bottom:6px}
.meta{color:#5e746c}
.footer{position:fixed;bottom:-13mm;left:0;right:0;font-size:7.5pt;color:#65746f;border-top:1px solid #dce6e2;padding-top:5px}
.page-break{page-break-after:always}
.section-break{page-break-before:always}
.lead{font-size:11pt;line-height:1.55;color:#344b43;margin-bottom:20px}
.counts{width:100%;border-collapse:separate;border-spacing:8px 0;margin:0 -8px 22px}
.counts td{width:33.33%;border:1px solid #dce6e2;border-radius:8px;padding:14px 12px;background:#f7fbf9;vertical-align:top}
.count-number{display:block;font-size:25pt;font-weight:bold;color:#155c4a;line-height:1}
.count-label{display:block;margin-top:5px;font-size:9pt;color:#40544d}
.pillars{width:100%;border-collapse:separate;border-spacing:8px;margin:0 -8px 16px}
.pillars td{width:50%;border:1px solid #dce6e2;border-radius:8px;padding:12px;vertical-align:top}
.pillar-name{font-weight:bold;font-size:11pt;margin-bottom:5px}
.status{display:inline-block;border-radius:12px;padding:3px 8px;font-size:8pt;font-weight:bold}
.status-priority{background:#fde9e7;color:#8d271f}
.status-review{background:#fff3d8;color:#76500c}
.status-addressed{background:#e8f5ef;color:#155c4a}
.status-not_assessed{background:#eef1f0;color:#596762}
.priority-card{page-break-inside:avoid;border:1px solid #dce6e2;border-left:5px solid #b53a31;border-radius:8px;padding:13px 15px;margin:0 0 12px}
.priority-label{font-size:7.5pt;text-transform:uppercase;letter-spacing:.7px;color:#8d271f;font-weight:bold;margin-bottom:5px}
.row-label{font-weight:bold;color:#155c4a}
.review-list{width:100%;border-collapse:collapse}
.review-list td{border-bottom:1px solid #dce6e2;padding:10px 5px;vertical-align:top}
.review-list td:first-child{width:33%;font-weight:bold;padding-left:0}
.review-list td:last-child{padding-right:0;color:#344b43}
.positive-table{width:100%;border-collapse:separate;border-spacing:8px}
.positive-table td{width:50%;background:#f3f8f5;border-radius:7px;padding:10px;vertical-align:top}
.tick{color:#155c4a;font-weight:bold;margin-right:5px}
.support{margin-top:26px;padding:18px;border-radius:9px;background:#155c4a;color:white}
.support h2{color:white;margin-bottom:6px}
.support p{margin-bottom:8px}
.cta{font-weight:bold}.cta a{color:white;text-decoration:none;border-bottom:1px solid #b9d7cc}
.disclaimer{margin-top:20px;padding-top:10px;border-top:1px solid #dce6e2;color:#687772;font-size:8pt}
</style>
</head>
<body>
<div class="footer"><?php echo esc_html($report['branding']['pdf_footer']); ?></div>

<section class="cover">
<?php if($report['branding']['logo_data']):?><img src="<?php echo esc_attr($report['branding']['logo_data']);?>" alt="Acorn Safety Services" style="max-width:190px;max-height:65px;margin-bottom:30px"><?php endif;?>
<div class="cover-kicker">Acorn Safety Services</div>
<h1>Your Health &amp; Safety<br>Healthcheck</h1>
<p class="cover-subtitle">A practical snapshot of what's working, what needs attention and what to do next.</p>
<p class="company"><?php echo esc_html($report['meta']['company']);?></p>
<p class="meta">Self-assessment report | <?php echo esc_html($report['meta']['assessment_date']);?></p>
<p class="meta"><?php echo esc_html($report['branding']['phone']);?> | <?php echo esc_html($report['branding']['website']);?></p>
</section>

<section class="page-break">
<h2>Your Healthcheck at a glance</h2>
<p class="lead"><?php echo esc_html($report['executive']['summary_text']);?></p>

<table class="counts"><tr>
<td><span class="count-number"><?php echo (int)$report['summary']['priority_count'];?></span><span class="count-label"><?php echo esc_html($report['executive']['priority_label']);?></span></td>
<td><span class="count-number"><?php echo (int)$report['summary']['review_count'];?></span><span class="count-label"><?php echo esc_html($report['executive']['review_label']);?></span></td>
<td><span class="count-number"><?php echo (int)$report['summary']['addressed_count'];?></span><span class="count-label"><?php echo esc_html($report['executive']['addressed_label']);?></span></td>
</tr></table>

<table class="pillars">
<?php foreach(array_chunk($report['pillars'],2) as $row):?><tr>
<?php foreach($row as $pillar):?><td>
<div class="pillar-name"><?php echo esc_html($pillar['label']);?></div>
<span class="status status-<?php echo esc_attr($pillar['status']);?>"><?php echo esc_html($pillar['display_status']);?></span>
</td><?php endforeach;?>
<?php if(count($row)===1):?><td></td><?php endif;?>
</tr><?php endforeach;?>
</table>

<p class="disclaimer"><?php echo esc_html($report['disclaimer']);?></p>
</section>

<section>
<h2>Your priority action plan</h2>
<?php if(!$report['priority']):?><p class="lead">No priority actions were identified from your responses.</p><?php endif;?>
<?php foreach($report['priority'] as $index=>$item):?>
<article class="priority-card">
<div class="priority-label">Priority <?php echo (int)$index+1;?></div>
<h3><?php echo esc_html($item['display_heading']);?></h3>
<p><span class="row-label">What we found:</span> <?php echo esc_html($item['display_identified']);?></p>
<p><span class="row-label">What to do next:</span> <?php echo esc_html($item['display_action']);?></p>
<?php if(!empty($item['display_why'])):?><p><span class="row-label">Why it matters:</span> <?php echo esc_html($item['display_why']);?></p><?php endif;?>
</article>
<?php endforeach;?>
</section>

<section class="section-break">
<h2>Other things worth reviewing</h2>
<p class="lead">These are not shown as priority actions, but your answers suggest they are worth checking or confirming.</p>
<?php if(!$report['review']):?><p>No additional review items were identified.</p><?php else:?>
<table class="review-list">
<?php foreach($report['review'] as $item):?><tr>
<td><?php echo esc_html($item['display_heading']);?></td>
<td><?php echo esc_html($item['display_action']);?></td>
</tr><?php endforeach;?>
</table>
<?php endif;?>
</section>

<section class="section-break">
<h2>What you're already doing well</h2>
<p class="lead">Your answers did not identify an obvious gap in these areas.</p>
<?php if(!$report['addressed']):?><p>No positive findings were recorded in this assessment.</p><?php else:?>
<table class="positive-table">
<?php foreach(array_chunk($report['addressed'],2) as $row):?><tr>
<?php foreach($row as $item):?><td><span class="tick">&#10003;</span><?php echo esc_html($item['display_heading']);?></td><?php endforeach;?>
<?php if(count($row)===1):?><td></td><?php endif;?>
</tr><?php endforeach;?>
</table>
<?php endif;?>

<div class="support">
<h2><?php echo esc_html($report['support']['heading']);?></h2>
<p><?php echo esc_html($report['support']['body']);?></p>
<p class="cta"><a href="<?php echo esc_url($report['support']['cta_url']);?>"><?php echo esc_html($report['support']['cta_label']);?></a></p>
<p><?php echo esc_html($report['branding']['phone']);?> | <?php echo esc_html($report['branding']['website']);?></p>
</div>

<p class="disclaimer"><?php echo esc_html($report['disclaimer']);?></p>
</section>
</body>
</html>
