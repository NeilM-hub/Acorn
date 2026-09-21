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
<p class="acorn-hc__eyebrow">Health &amp; Safety Healthcheck</p>
<h1>Your Health &amp; Safety Healthcheck</h1>
<p class="acorn-hc__report-intro">A practical snapshot of what's working, what needs attention and what to do next.</p>
<div class="acorn-hc__report-meta"><strong><?php echo esc_html($report['meta']['company']); ?></strong><span><?php echo esc_html($report['meta']['assessment_date']); ?></span><span>Prepared by Acorn Safety Services</span></div>
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

<?php if($report['executive']['top_actions']):?>
<div class="acorn-hc__top-actions">
<p class="acorn-hc__eyebrow">What needs your attention first</p>
<?php foreach($report['executive']['top_actions'] as $index=>$item):?>
<article>
<span class="acorn-hc__top-number"><?php echo (int)$index+1;?></span>
<div><h3><?php echo esc_html($item['display_heading']);?></h3><p><?php echo esc_html($item['display_action']);?></p></div>
<span class="acorn-hc__status acorn-hc__status--<?php echo esc_attr($item['finding_status']);?>"><?php echo esc_html($item['finding_status']==='priority'?'Priority action':'Review recommended');?></span>
</article>
<?php endforeach;?>
</div>
<?php endif;?>

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
<div class="acorn-hc__finding-block"><span>What we found</span><p><?php echo esc_html($item['display_identified']);?></p></div>
<div class="acorn-hc__finding-block acorn-hc__finding-block--next"><span>Do this next</span><p><?php echo esc_html($item['display_action']);?></p></div>
<?php if(!empty($item['display_why'])):?><div class="acorn-hc__finding-block"><span>Why it matters</span><p><?php echo esc_html($item['display_why']);?></p></div><?php endif;?>
<?php if(!empty($item['display_good_looks'])):?><div class="acorn-hc__good-box"><strong>What good looks like:</strong> <?php echo esc_html($item['display_good_looks']);?></div><?php endif;?>
<?php if(!empty($item['display_acorn_help'])):?><div class="acorn-hc__help-box"><strong>How Acorn can help</strong><p><?php echo esc_html($item['display_acorn_help']);?></p></div><?php endif;?>
<div class="acorn-hc__action-meta"><span><strong>Suggested owner:</strong> <?php echo esc_html($item['display_owner']);?></span><span><strong>Suggested priority:</strong> <?php echo esc_html($item['display_priority']);?></span></div>
</div>
</article>
<?php endforeach;?>
</div>
</section>

<section>
<p class="acorn-hc__eyebrow">Worth checking</p>
<h2>Other things worth reviewing</h2>
<p>These are not priority actions, but your answers suggest they are worth checking or confirming.</p>
<div class="acorn-hc__review-list">
<?php if(!$report['review']):?><p>No additional review items were identified.</p><?php endif;?>
<?php foreach($report['review'] as $item):?>
<article><h3><?php echo esc_html($item['display_heading']);?></h3><p><?php echo esc_html($item['display_action']);?></p></article>
<?php endforeach;?>
</div>
</section>

<section>
<p class="acorn-hc__eyebrow">Practical next steps</p>
<h2>What should you do next?</h2>
<p>Use this report as a working action plan rather than a one-off checklist.</p>
<div class="acorn-hc__next-steps">
<?php foreach($report['next_steps'] as $index=>$step):?>
<article><span class="acorn-hc__next-step-number"><?php echo (int)$index+1;?></span><div><h3><?php echo esc_html($step['title']);?></h3><p><?php echo esc_html($step['body']);?></p></div></article>
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
<p class="acorn-hc__eyebrow">How Acorn Safety Services can help</p>
<h2><?php echo esc_html($report['support']['heading']); ?></h2>
<p><?php echo esc_html($report['support']['body']); ?></p>
<?php if(!empty($report['support']['options'])):?><div class="acorn-hc__support-options">
<?php foreach($report['support']['options'] as $option):?>
<article><h3><?php echo esc_html($option['label']);?></h3><p><?php echo esc_html($option['body']);?></p></article>
<?php endforeach;?>
</div><?php endif;?>
<div class="acorn-hc__support-actions">
<a class="acorn-hc__primary" href="<?php echo esc_url($report['support']['cta_url']); ?>"><?php echo esc_html($report['support']['cta_label']); ?></a>
<a class="acorn-hc__secondary-link" href="<?php echo esc_url($report['support']['secondary_cta_url']); ?>"><?php echo esc_html($report['support']['secondary_cta_label']); ?></a>
</div>
</section>

<p class="acorn-hc__report-disclaimer"><?php echo esc_html($report['disclaimer']); ?></p>
<footer><p><a href="<?php echo esc_url($report['branding']['website']); ?>">Acorn Safety Services</a> | <?php echo esc_html($report['branding']['phone']); ?><?php if($report['privacy_policy_url']):?> | <a href="<?php echo esc_url($report['privacy_policy_url']); ?>">Privacy policy</a><?php endif;?></p></footer>
</main>
<?php wp_footer(); ?>
</body>
</html>
