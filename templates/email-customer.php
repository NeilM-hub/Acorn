<div style="font-family:Arial,sans-serif;max-width:640px;margin:0 auto;color:#17221f;line-height:1.5">
  <p style="margin:0 0 8px;color:#60756d;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em">Acorn Safety Services</p>
  <h1 style="margin:0 0 12px;color:#155c4a;font-size:28px">Your Health &amp; Safety Healthcheck</h1>
  <p style="margin:0 0 22px;color:#40544d">Your Healthcheck is ready. Here is your result at a glance:</p>
  <table role="presentation" style="width:100%;border-collapse:separate;border-spacing:8px 0;margin:0 -8px 22px">
    <tr>
      <td style="width:33.33%;padding:14px;border:1px solid #dce6e2;border-radius:8px;background:#f7fbf9;text-align:center">
        <strong style="display:block;color:#155c4a;font-size:24px"><?php echo (int) $data['summary']['priority_count']; ?></strong>
        <span style="font-size:13px;color:#40544d">Priority actions</span>
      </td>
      <td style="width:33.33%;padding:14px;border:1px solid #dce6e2;border-radius:8px;background:#f7fbf9;text-align:center">
        <strong style="display:block;color:#155c4a;font-size:24px"><?php echo (int) $data['summary']['review_count']; ?></strong>
        <span style="font-size:13px;color:#40544d">Reviews recommended</span>
      </td>
      <td style="width:33.33%;padding:14px;border:1px solid #dce6e2;border-radius:8px;background:#f7fbf9;text-align:center">
        <strong style="display:block;color:#155c4a;font-size:24px"><?php echo (int) $data['summary']['addressed_count']; ?></strong>
        <span style="font-size:13px;color:#40544d">Areas looking good</span>
      </td>
    </tr>
  </table>
  <h2 style="margin:0 0 8px;color:#155c4a;font-size:20px">Your Healthcheck at a glance</h2>
  <p style="margin:0 0 22px;color:#40544d"><?php echo esc_html($data['executive']['summary_text']); ?></p>
  <p style="margin:0 0 24px">
    <a href="<?php echo esc_url($reportUrl); ?>" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#155c4a;color:#ffffff;text-decoration:none;font-weight:700">View your secure report</a>
  </p>
  <p style="margin:0;color:#687772;font-size:12px"><?php echo esc_html($data['disclaimer']); ?></p>
</div>
