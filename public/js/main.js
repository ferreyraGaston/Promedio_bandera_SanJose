// Navbar móvil accesible con hamburguesa
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#mainnav');
  if (!btn || !nav) return;

  const close = () => { nav.classList.remove('show'); btn.setAttribute('aria-expanded','false'); };
  const open  = () => { nav.classList.add('show');    btn.setAttribute('aria-expanded','true');  };

  btn.addEventListener('click', () => {
    nav.classList.contains('show') ? close() : open();
  });

  // Cierra al elegir una opción (solo cuando está desplegado)
  nav.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => { if (nav.classList.contains('show')) close(); });
  });

  // Cerrar con ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && nav.classList.contains('show')) close();
  });

  // Si se agranda a desktop, asegura el estado cerrado
  const mq = window.matchMedia('(min-width: 901px)');
  mq.addEventListener('change', () => close());
});
