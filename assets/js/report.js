const report = document.querySelector('.acorn-hc__report[data-resend-url]');

if (report) {
  const button = report.querySelector('[data-action="resend-report"]');
  const status = report.querySelector('[data-resend-status]');

  if (button && status) {
    button.addEventListener('click', async () => {
      if (button.disabled) return;

      button.disabled = true;
      status.textContent = 'Sending your report email…';

      try {
        const response = await fetch(report.dataset.resendUrl, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
        });
        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
          throw new Error(body.message || 'Your report could not be resent right now.');
        }

        status.textContent = body.message || 'Your report email has been resent.';
      } catch (error) {
        status.textContent = error instanceof Error
          ? error.message
          : 'Your report could not be resent right now.';
      } finally {
        button.disabled = false;
      }
    });
  }
}
