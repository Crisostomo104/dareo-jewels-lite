document.addEventListener('DOMContentLoaded', function () {
  const dialog = document.getElementById('powered-dialog');
  const openers = document.querySelectorAll('[data-powered-open]');
  const closers = dialog ? dialog.querySelectorAll('[data-powered-close]') : [];

  if (!dialog) return;
  openers.forEach(function (button) {
    button.addEventListener('click', function () {
      if (typeof dialog.showModal === 'function') {
        dialog.showModal();
        const close = dialog.querySelector('[data-powered-close]');
        if (close) close.focus();
      }
    });
  });
  closers.forEach(function (button) {
    button.addEventListener('click', function () { dialog.close(); });
  });
  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) dialog.close();
  });
});
