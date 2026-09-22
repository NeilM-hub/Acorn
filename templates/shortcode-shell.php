<section class="acorn-hc" id="acorn-healthcheck" data-rest="<?php echo esc_url(rest_url('acorn-healthcheck/v1')); ?>" data-report-base="<?php echo esc_url(home_url('/healthcheck/report/')); ?>" data-landing="<?php echo esc_attr((string) wp_json_encode($landingContent)); ?>" data-logo="<?php echo esc_url((string) $healthcheckSettings['report_logo_url']); ?>" data-audit-url="<?php echo esc_url((string) $healthcheckSettings['audit_cta_url']); ?>">
 <div class="acorn-hc__app" aria-live="polite">
  <div class="acorn-hc__hero">
   <span class="acorn-hc__eyebrow">Free Health &amp; Safety Healthcheck</span>
   <h1>Find the gaps.<br>Know what to do next.</h1>
   <p class="acorn-hc__hero-copy">Answer a few straightforward questions and get a practical snapshot of what looks good, what may need attention and what to do next.</p>
   <div class="acorn-hc__topic-chips"><span>Health &amp; Safety</span><span>Fire Safety</span><span>Legionella</span><span>Asbestos</span></div>
   <div class="acorn-hc__benefits"><span>✓ Around 3–4 minutes</span><span>✓ No account needed</span><span>✓ Personalised action plan</span><span>✓ Downloadable report</span></div>
   <p class="acorn-hc__notice">This is an indicative self-assessment based on the information you provide. It is not a formal audit, legal advice or confirmation of compliance.</p>
   <button class="acorn-hc__primary acorn-hc__hero-cta" type="button" data-action="start">Start my Healthcheck</button>
  </div>
 </div>
 <noscript>JavaScript is required to complete this Healthcheck.</noscript>
</section>
