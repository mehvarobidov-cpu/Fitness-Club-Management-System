const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: .12 });
document.querySelectorAll('.reveal').forEach((el, i) => { el.style.transitionDelay = (i % 6) * 70 + 'ms'; io.observe(el); });
document.querySelectorAll('.tilt').forEach(el => {
  el.addEventListener('mousemove', e => { const r = el.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5; el.style.transform = `perspective(700px) rotateY(${x * 10}deg) rotateX(${-y * 10}deg) translateY(-4px)`; });
  el.addEventListener('mouseleave', () => el.style.transform = '');
});
document.querySelectorAll('[data-count]').forEach(el => { const n = +el.dataset.count; let i = 0; const t = setInterval(() => { i += Math.max(1, Math.ceil(n / 20)); if (i >= n) { i = n; clearInterval(t); } el.textContent = i; }, 30); });
