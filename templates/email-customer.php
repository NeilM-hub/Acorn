<h1>Your Acorn Health &amp; Safety Healthcheck</h1>
<p>Your result identified <?php echo (int) $data['summary']['priority_count']; ?> priority actions and <?php echo (int) $data['summary']['review_count']; ?> areas to review.</p>
<p><a href="<?php echo esc_url($reportUrl); ?>">View your secure full report</a></p>
<p><?php echo esc_html($data['disclaimer']); ?></p>
