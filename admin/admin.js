// ask before destructive actions (forms carrying data-confirm)
document.addEventListener('submit', (event) => {
  const form = event.target;
  if (form instanceof HTMLFormElement && form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});

// reopen the message the visitor just acted on (mark read/unread redirects to #message-id)
const target = location.hash && document.querySelector(location.hash);
if (target) target.querySelector('details')?.setAttribute('open', '');
