document.addEventListener('DOMContentLoaded', () => {
  const field = document.querySelector('.acorn-hc-logo-field');
  if (!field || !window.wp?.media) return;

  const choose = field.querySelector('[data-acorn-logo-choose]');
  const remove = field.querySelector('[data-acorn-logo-remove]');
  const idInput = field.querySelector('[name="report_logo_attachment_id"]');
  const urlInput = field.querySelector('[name="report_logo_url"]');
  const preview = field.querySelector('.acorn-hc-logo-field__preview');

  choose?.addEventListener('click', () => {
    const frame = window.wp.media({
      title: 'Choose Healthcheck logo',
      button: {text: 'Use this logo'},
      library: {type: 'image'},
      multiple: false,
    });

    frame.on('select', () => {
      const attachment = frame.state().get('selection').first()?.toJSON();
      if (!attachment) return;

      idInput.value = String(attachment.id || 0);
      urlInput.value = String(attachment.url || '');
      preview.innerHTML = attachment.url
        ? `<img src="${attachment.url}" alt="Current Healthcheck logo">`
        : '';
    });

    frame.open();
  });

  remove?.addEventListener('click', () => {
    idInput.value = '0';
    urlInput.value = '';
    preview.innerHTML = '';
  });
});
