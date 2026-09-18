<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Health &amp; Safety Healthcheck</title>
<?php wp_head(); ?>
</head>
<body>
<main class="acorn-hc acorn-hc__report acorn-hc__report--executive" data-resend-url="<?php echo esc_url($resendUrl); ?>">
<header class="acorn-hc__report-hero">
<?php if($report['branding']['logo_url']):?><img class="acorn-hc__report-logo" src="<?php echo esc_url($report['branding']['logo_url']);?>" alt="Acorn Safety Services"><?php endif;?>
<p class="acorn-hc__eyebrow">Acorn Safety Services</p>
<h1>Your Health &amp; Safety Healthcheck</h1>
<p class="acorn-hc__report-intro">A practical snapshot of what's working, what needs attention and what to do next.</p>
<p><strong><?php echo esc_html($report['meta']['company']); ?></strong> | <?php echo esc_html($report['meta']['assessment_date']); ?></p>
</header>

<section>
<div class="acorn-hc__section-heading">
<div><p class="acorn-hc__eyebrow">Executive summary</p><h2>Your Healthcheck at a glance</h2></div>
<div class="acorn-hc__report-links"><a href="<?php echo esc_url(trailingslashit($reportUrl).'pdf/'); ?>">Download PDF</a><button class="acorn-hc__secondary" type="button" data-action="resend-report">Resend email</button></div>
</div>
<p class="acorn-hc__report-lead"><?php echo esc_html($report['executive']['summary_text']); ?></p>
<div class="acorn-hc__report-counts">
<div><strong><?php echo (int)$report['summary']['priority_count']; ?></strong><span><?php echo esc_html($report['executive']['priority_label']); ?></span></div>
<div><strong><?php echo (int)$report['summary']['review_count']; ?></strong><span><?php echo esc_html($report['executive']['review_label']); ?></span></div>
<div><strong><?php echo (int)$report['summary']['addressed_count']; ?></strong><span><?php echo esc_html($report['executive']['addressed_label']); ?></span></div>
</div>
<div class="acorn-hc__pillar-grid">
<?php foreach($report['pillars'] as $pillar):?>
<div class="acorn-hc__pillar-card"><strong><?php echo esc_html($pillar['label']);?></strong><span class="acorn-hc__status acorn-hc__status--<?php echo esc_attr($pillar['status']);?>"><?php echo esc_html($pillar['display_status']);?></span></div>
<?php endforeach;?>
</div>
<p data-resend-status role="status" aria-live="polite"></p>
</section>

<section>
<p class="acorn-hc__eyebrow">Deal with these first</p>
<h2>Your priority action plan</h2>
<?php if(!$report['priority']):?><p>No priority actions were identified from your responses.</p><?php endif;?>
<div class="acorn-hc__priority-list">
<?php foreach($report['priority'] as $index=>$item):?>
<article class="acorn-hc__priority-card">
<div class="acorn-hc__priority-number"><?php echo (int)$index+1;?></div>
<div>
<h3><?php echo esc_html($item['display_heading']);?></h3>
<p><strong>What we found:</strong> <?php echo esc_html($item['display_identified']);?></p>
<p><strong>What to do next:</strong> <?php echo esc_html($item['display_action']);?></p>
<?php if(!empty($item['display_why'])):?><p class="acorn-hc__muted"><strong>Why it matters:</strong> <?php echo esc_html($item['display_why']);?></p><?php endif;?>
</div>
</article>
<?php endforeach;?>
</div>
</section>

<section>
<p class="acorn-hc__eyebrow">Worth checking</p>
<h2>Other things worth reviewing</h2>
<p>These are not shown as priority actions, but your answers suggest they are worth checking or confirming.</p>
<div class="acorn-hc__review-list">
<?php if(!$report['review']):?><p>No additional review items were identified.</p><?php endif;?>
<?php foreach($report['review'] as $item):?>
<article><h3><?php echo esc_html($item['display_heading']);?></h3><p><?php echo esc_html($item['display_action']);?></p></article>
<?php endforeach;?>
</div>
</section>

<section>
<p class="acorn-hc__eyebrow">Positive findings</p>
<h2>What you're already doing well</h2>
<p>Your answers did not identify an obvious gap in these areas.</p>
<ul class="acorn-hc__positive-list">
<?php foreach($report['addressed'] as $item):?><li><?php echo esc_html($item['display_heading']);?></li><?php endforeach;?>
</ul>
</section>

<section class="acorn-hc__support acorn-hc__support--executive">
<h2><?php echo esc_html($report['support']['heading']); ?></h2>
<p><?php echo esc_html($report['support']['body']); ?></p>
<a class="acorn-hc__primary" href="<?php echo esc_url($report['support']['cta_url']); ?>"><?php echo esc_html($report['support']['cta_label']); ?></a>
</section>

<p class="acorn-hc__report-disclaimer"><?php echo esc_html($report['disclaimer']); ?></p>
<footer><p><a href="<?php echo esc_url($report['branding']['website']); ?>">Acorn Safety Services</a> | <?php echo esc_html($report['branding']['phone']); ?><?php if($report['privacy_policy_url']):?> | <a href="<?php echo esc_url($report['privacy_policy_url']); ?>">Privacy policy</a><?php endif;?></p></footer>
</main>
<?php wp_footer(); ?>
</body>
</html>
