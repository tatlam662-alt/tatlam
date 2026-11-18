(function () {
  'use strict';

  var dialog;
  var title;
  var message;
  var icon;
  var confirmButton;
  var cancelButton;
  var activeResolve = null;
  var previousFocus = null;

  function buildDialog() {
    if (dialog) return;

    dialog = document.createElement('div');
    dialog.className = 'pd-dialog';
    dialog.hidden = true;
    dialog.innerHTML = '<div class="pd-dialog-backdrop" data-pd-dialog-cancel></div>' +
      '<section class="pd-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="pd-dialog-title" aria-describedby="pd-dialog-message">' +
        '<div class="pd-dialog-heading"><span class="pd-dialog-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>' +
        '<div><span class="pd-dialog-eyebrow">PANDASHOP</span><h2 id="pd-dialog-title"></h2></div>' +
        '<button type="button" class="pd-dialog-close" aria-label="Đóng" data-pd-dialog-cancel>&times;</button></div>' +
        '<p class="pd-dialog-message" id="pd-dialog-message"></p>' +
        '<div class="pd-dialog-actions"><button type="button" class="pd-dialog-button pd-dialog-secondary" data-pd-dialog-cancel></button>' +
        '<button type="button" class="pd-dialog-button pd-dialog-primary" data-pd-dialog-confirm></button></div>' +
      '</section>';
    document.body.appendChild(dialog);

    title = dialog.querySelector('#pd-dialog-title');
    message = dialog.querySelector('#pd-dialog-message');
    icon = dialog.querySelector('.pd-dialog-icon i');
    confirmButton = dialog.querySelector('[data-pd-dialog-confirm]');
    cancelButton = dialog.querySelector('.pd-dialog-secondary');

    dialog.addEventListener('click', function (event) {
      if (event.target.closest('[data-pd-dialog-confirm]')) closeDialog(true);
      else if (event.target.closest('[data-pd-dialog-cancel]')) closeDialog(false);
    });
    document.addEventListener('keydown', function (event) {
      if (dialog.hidden) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        closeDialog(false);
      } else if (event.key === 'Tab') {
        event.preventDefault();
        if (cancelButton.hidden) confirmButton.focus();
        else (document.activeElement === confirmButton ? cancelButton : confirmButton).focus();
      }
    });
  }

  function closeDialog(result) {
    if (!dialog || dialog.hidden) return;
    dialog.hidden = true;
    document.body.classList.remove('pd-dialog-open');
    if (activeResolve) {
      activeResolve(result);
      activeResolve = null;
    }
    if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
  }

  function showDialog(text, options, isConfirm) {
    buildDialog();
    if (activeResolve) closeDialog(false);

    options = options || {};
    previousFocus = document.activeElement;
    title.textContent = options.title || (isConfirm ? 'Xác nhận thao tác' : (options.variant === 'success' ? 'Thành công' : 'Thông báo'));
    message.textContent = String(text || '');
    dialog.classList.toggle('is-danger', !!options.danger || options.variant === 'error');
    dialog.classList.toggle('is-success', options.variant === 'success');
    icon.className = (options.danger || options.variant === 'error') ? 'fa-solid fa-triangle-exclamation' : (options.variant === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-info');
    confirmButton.textContent = options.confirmText || (isConfirm ? 'Xác nhận' : 'Đã hiểu');
    cancelButton.textContent = options.cancelText || 'Để sau';
    cancelButton.hidden = !isConfirm;
    dialog.hidden = false;
    document.body.classList.add('pd-dialog-open');

    return new Promise(function (resolve) {
      activeResolve = resolve;
      confirmButton.focus();
    });
  }

  window.pdConfirm = function (text, options) {
    return showDialog(text, options, true);
  };

  window.pdAlert = function (text, options) {
    return showDialog(text, options, false);
  };

  // Legacy alert() calls are redirected through the PanDaShop dialog globally.
  // New confirmations should use pdConfirm(), which supports async confirmation.
  var browserAlert = window.alert;
  window.alert = function (text) {
    if (typeof window.pdAlert === 'function') window.pdAlert(text);
    else browserAlert.call(window, text);
  };
})();
