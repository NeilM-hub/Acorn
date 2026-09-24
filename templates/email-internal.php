<div style="font-family:Arial,sans-serif;max-width:760px;margin:0 auto;color:#1c2932;line-height:1.5">
  <div style="padding:18px 20px;border-radius:10px;background:#084e87;color:#fff;margin-bottom:22px">
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#d9ebf8">New Healthcheck lead</p>
    <h1 style="margin:0;font-size:28px;line-height:1.15"><?php echo esc_html($data['contact']['company']); ?></h1>
  </div>

  <h2 style="margin:0 0 10px;color:#084e87">Contact details</h2>
  <table role="presentation" style="width:100%;border-collapse:collapse;margin:0 0 22px">
    <tr><td style="padding:6px 0;width:180px;color:#60788a;font-weight:700">Name</td><td style="padding:6px 0"><?php echo esc_html(trim($data['contact']['first_name'].' '.$data['contact']['last_name'])); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Work email</td><td style="padding:6px 0"><?php echo esc_html($data['contact']['email']); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Telephone</td><td style="padding:6px 0"><?php echo esc_html($data['contact']['telephone'] ?: 'Not provided'); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Postcode</td><td style="padding:6px 0"><?php echo esc_html($data['contact']['postcode'] ?: 'Not provided'); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Employee band</td><td style="padding:6px 0"><?php echo esc_html($data['assessment']['employee_band'] ?: 'Not recorded'); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Jurisdiction</td><td style="padding:6px 0"><?php echo esc_html(ucwords(str_replace('_',' ',(string)$data['assessment']['jurisdiction']))); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Completed</td><td style="padding:6px 0"><?php echo esc_html((string)$data['assessment']['completed_at']); ?></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Free audit requested</td><td style="padding:6px 0"><strong><?php echo $data['contact']['audit_requested'] ? 'Yes' : 'No'; ?></strong></td></tr>
    <tr><td style="padding:6px 0;color:#60788a;font-weight:700">Marketing consent</td><td style="padding:6px 0"><?php echo $data['contact']['marketing_consent'] ? 'Yes' : 'No'; ?></td></tr>
  </table>

  <h2 style="margin:0 0 10px;color:#084e87">Healthcheck result</h2>
  <table role="presentation" style="width:100%;border-collapse:separate;border-spacing:8px 0;margin:0 -8px 22px">
    <tr>
      <td style="width:33.33%;padding:14px;border:1px solid #d9e4ed;border-radius:9px;background:#f5f9fc;text-align:center">
        <strong style="display:block;color:#084e87;font-size:26px"><?php echo (int)$data['report']['summary']['priority_count']; ?></strong>
        <span style="font-size:13px;color:#465f71"><?php echo esc_html($data['report']['executive']['priority_label']); ?></span>
      </td>
      <td style="width:33.33%;padding:14px;border:1px solid #d9e4ed;border-radius:9px;background:#f5f9fc;text-align:center">
        <strong style="display:block;color:#084e87;font-size:26px"><?php echo (int)$data['report']['summary']['review_count']; ?></strong>
        <span style="font-size:13px;color:#465f71"><?php echo esc_html($data['report']['executive']['review_label']); ?></span>
      </td>
      <td style="width:33.33%;padding:14px;border:1px solid #d9e4ed;border-radius:9px;background:#f5f9fc;text-align:center">
        <strong style="display:block;color:#084e87;font-size:26px"><?php echo (int)$data['report']['summary']['addressed_count']; ?></strong>
        <span style="font-size:13px;color:#465f71"><?php echo esc_html($data['report']['executive']['addressed_label']); ?></span>
      </td>
    </tr>
  </table>

  <?php if(!empty($data['report']['executive']['top_actions'])):?>
  <h2 style="margin:0 0 10px;color:#084e87">Needs attention first</h2>
  <ol style="margin:0 0 22px;padding-left:22px">
    <?php foreach($data['report']['executive']['top_actions'] as $item):?>
      <li style="margin:0 0 8px"><strong><?php echo esc_html($item['display_heading']); ?></strong><br><span style="color:#526979"><?php echo esc_html($item['display_action']); ?></span></li>
    <?php endforeach;?>
  </ol>
  <?php endif;?>

  <div style="padding:16px 18px;border-left:4px solid #084e87;border-radius:8px;background:#f3f8fc;margin:0 0 22px">
    <p style="margin:0 0 5px;color:#084e87;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em">Recommended follow-up</p>
    <p style="margin:0;color:#344e61">Review the attached Healthcheck before contacting this prospect so the conversation can focus on the issues they have already identified and the support Acorn can provide.</p>
  </div>

  <p style="margin:0 0 20px">
    <a href="<?php echo esc_url($data['admin_url']); ?>" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#084e87;color:#fff;text-decoration:none;font-weight:700">View assessment in WordPress</a>
  </p>

  <p style="margin:0;color:#6b7d89;font-size:12px">The customer's PDF Healthcheck report is attached to this email when PDF generation succeeds.</p>
</div>
