// Theme toggle (persists for the session via in-page cookie)
function toggleTheme(){
  const html = document.documentElement;
  const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  document.cookie = 'theme=' + next + ';path=/;max-age=31536000';
}
// Apply saved theme on load
(function(){
  const m = document.cookie.match(/(?:^|;\s*)theme=(\w+)/);
  if (m) document.documentElement.setAttribute('data-theme', m[1]);
})();

// Confirm before destructive actions
document.addEventListener('click', e => {
  const el = e.target.closest('[data-confirm]');
  if (el && !confirm(el.getAttribute('data-confirm'))) e.preventDefault();
});

// Auto-dismiss toasts
setTimeout(() => document.querySelectorAll('.toast').forEach(t => {
  t.style.transition = 'opacity .4s'; t.style.opacity = '0';
  setTimeout(() => t.remove(), 400);
}), 4000);
