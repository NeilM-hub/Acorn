<div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;color:#1c2932;line-height:1.5">
  <?php if(!empty($data['branding']['horizontal_logo_url'])):?><p style="margin:0 0 22px"><img src="<?php echo esc_url($data['branding']['horizontal_logo_url']); ?>" alt="Acorn Safety Services" style="display:block;max-width:260px;max-height:72px"></p><?php else:?><p style="margin:0 0 8px;color:#587184;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em">Acorn Safety Services</p><?php endif;?>
  <h1 style="margin:0 0 12px;color:#084e87;font-size:30px;line-height:1.15">Your Health &amp; Safety Healthcheck</h1>
  <p style="margin:0 0 22px;color:#4b6272;font-size:16px">Your Healthcheck is ready. Here is your result at a glance:</p>

  <table role="presentation" style="width:100%;border-collapse:separate;border-spacing:8px 0;margin:0 -8px 22px">
    <tr>
      <td style="width:33.33%;padding:14px;border:1px solid #d9e4ed;border-radius:9px;background:#f5f9fc;text-align:center">
        <strong style="display:block;color:#084e87;font-size:26px"><?php echo (int) $data['summary']['priority_count']; ?></strong>
        <span style="font-size:13px;color:#465f71"><?php echo esc_html($data['executive']['priority_label']); ?></span>
      </td>
      <td style="width:33.33%;padding:14px;border:1px solid #d9e4ed;border-radius:9px;background:#f5f9fc;text-align:center">
        <strong style="display:block;color:#084e87;font-size:26px"><?php echo (int) $data['summary']['review_count']; ?></strong>
        <span style="font-size:13px;color:#465f71"><?php echo esc_html($data['executive']['review_label']); ?></span>
      </td>
      <td style="width:33.33%;padding:14px;border:1px solid #d9e4ed;border-radius:9px;background:#f5f9fc;text-align:center">
        <strong style="display:block;color:#084e87;font-size:26px"><?php echo (int) $data['summary']['addressed_count']; ?></strong>
        <span style="font-size:13px;color:#465f71"><?php echo esc_html($data['executive']['addressed_label']); ?></span>
      </td>
    </tr>
  </table>

  <?php $firstAction=$data['executive']['top_actions'][0] ?? null; if($firstAction):?>
  <div style="margin:0 0 22px;padding:16px 18px;border:1px solid #cddfea;border-left:4px solid #084e87;border-radius:9px;background:#f4f9fd">
    <p style="margin:0 0 5px;color:#60788a;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em"><?php echo $firstAction['finding_status']==='priority'?'Your first priority':'First area to review'; ?></p>
    <p style="margin:0 0 5px;color:#183b55;font-size:18px;font-weight:700"><?php echo esc_html($firstAction['display_heading']); ?></p>
    <p style="margin:0;color:#4b6272;font-size:14px"><?php echo esc_html($firstAction['display_action']); ?></p>
  </div>
  <?php endif;?>

  <h2 style="margin:0 0 8px;color:#084e87;font-size:20px">Your Healthcheck at a glance</h2>
  <p style="margin:0 0 22px;color:#4b6272"><?php echo esc_html($data['executive']['summary_text']); ?></p>

  <p style="margin:0 0 24px">
    <a href="<?php echo esc_url($reportUrl); ?>" style="display:inline-block;padding:13px 19px;border-radius:8px;background:#084e87;color:#ffffff;text-decoration:none;font-weight:700">View your secure report</a>
  </p>

  <?php if(!empty($attachment)):?><p style="margin:0 0 8px;color:#60788a;font-size:12px">Your PDF report is attached to this email for easy reference.</p><?php endif;?>
  <p style="margin:0;color:#6e7e89;font-size:12px"><?php echo esc_html($data['disclaimer']); ?></p>
</div>
