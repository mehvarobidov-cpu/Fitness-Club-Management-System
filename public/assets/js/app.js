(function () {
  'use strict';

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Reveal animation is progressive enhancement: content stays visible without JavaScript.
  if (!reducedMotion && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('js-motion');
    const observer = new IntersectionObserver((entries, instance) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('in');
        instance.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -24px 0px' });

    document.querySelectorAll('.reveal').forEach((element, index) => {
      element.style.transitionDelay = `${(index % 5) * 55}ms`;
      observer.observe(element);
    });
  } else {
    document.querySelectorAll('.reveal').forEach((element) => element.classList.add('in'));
  }

  // Restrained hover tilt for pointer devices only.
  if (!reducedMotion && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.querySelectorAll('.tilt').forEach((element) => {
      element.addEventListener('pointermove', (event) => {
        const rect = element.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        element.style.transform = `perspective(900px) rotateY(${x * 4}deg) rotateX(${-y * 4}deg) translateY(-2px)`;
      });
      element.addEventListener('pointerleave', () => {
        element.style.transform = '';
      });
    });
  }

  // Count-up metrics only when the target is a valid non-negative integer.
  const animateCount = (element) => {
    const target = Number(element.dataset.count);
    if (!Number.isFinite(target) || target < 0) return;
    if (reducedMotion || target === 0) {
      element.textContent = String(Math.max(0, Math.round(target)));
      return;
    }
    const duration = 650;
    const start = performance.now();
    const from = 0;
    const tick = (now) => {
      const progress = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - progress, 3);
      element.textContent = String(Math.round(from + (target - from) * eased));
      if (progress < 1) window.requestAnimationFrame(tick);
    };
    window.requestAnimationFrame(tick);
  };

  if ('IntersectionObserver' in window && !reducedMotion) {
    const countObserver = new IntersectionObserver((entries, instance) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        animateCount(entry.target);
        instance.unobserve(entry.target);
      });
    }, { threshold: 0.4 });
    document.querySelectorAll('[data-count]').forEach((element) => countObserver.observe(element));
  } else {
    document.querySelectorAll('[data-count]').forEach(animateCount);
  }

  // Generic confirmation hook. Add data-confirm="..." to a destructive form/button.
  document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');
    if (!form) return;
    if (!window.confirm(form.dataset.confirm || 'Амалиётро тасдиқ мекунед?')) {
      event.preventDefault();
    }
  });

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm-action]');
    if (!trigger) return;
    const message = trigger.dataset.confirmAction || 'Амалиётро тасдиқ мекунед?';
    if (!window.confirm(message)) event.preventDefault();
  });

  // Password visibility toggle: <button data-password-toggle="#password">Намоиш</button>
  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');
    if (!toggle) return;
    const field = document.querySelector(toggle.dataset.passwordToggle);
    if (!field || !('type' in field)) return;
    const visible = field.type === 'password';
    field.type = visible ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.textContent = visible ? 'Пинҳон кардан' : 'Намоиш';
  });

  // Optional client-side list filtering for screens that opt in with data-live-filter.
  document.querySelectorAll('[data-live-filter]').forEach((input) => {
    const selector = input.dataset.liveFilter;
    const list = document.querySelector(selector);
    if (!list) return;
    const items = Array.from(list.querySelectorAll('[data-search-text]'));
    const status = input.dataset.filterStatus ? document.querySelector(input.dataset.filterStatus) : null;
    input.addEventListener('input', () => {
      const query = input.value.trim().toLocaleLowerCase();
      let visible = 0;
      items.forEach((item) => {
        const haystack = (item.dataset.searchText || item.textContent).toLocaleLowerCase();
        const match = haystack.includes(query);
        item.hidden = !match;
        if (match) visible += 1;
      });
      if (status) status.textContent = query ? `Натиҷаҳо: ${visible}` : '';
    });
  });

  // Current navigation item can be marked automatically for simple public links.
  const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
  document.querySelectorAll('.nav a[href], .sidebar-link[href]').forEach((link) => {
    try {
      const linkUrl = new URL(link.href, window.location.origin);
      if (linkUrl.pathname.replace(/\/$/, '') === currentPath) {
        link.setAttribute('aria-current', 'page');
      }
    } catch (_) {
      // Ignore malformed optional navigation links.
    }
  });
})();
