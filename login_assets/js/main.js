(() => {
  const $ = (s, p = document) => p.querySelector(s);
  const $$ = (s, p = document) => [...p.querySelectorAll(s)];

  // Mobile menu
  const menuButton = $('.menu-button');
  const mobileMenu = $('.mobile-menu');
  menuButton?.addEventListener('click', () => {
    const open = mobileMenu.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(open));
    mobileMenu.setAttribute('aria-hidden', String(!open));
  });
  $$('.mobile-menu a').forEach(a => a.addEventListener('click', () => mobileMenu.classList.remove('open')));

  // Reveal animation
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.13 });
  $$('.reveal').forEach(el => revealObserver.observe(el));

  // Counters
  const countObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      $$('[data-count]', entry.target).forEach(el => {
        const target = Number(el.dataset.count);
        const decimals = Number(el.dataset.decimals || 0);
        const start = performance.now();
        const duration = 1500;
        const tick = now => {
          const p = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - p, 3);
          el.textContent = (target * eased).toFixed(decimals);
          if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
      });
      countObserver.unobserve(entry.target);
    });
  }, { threshold: .4 });
  $$('.metrics-bar, .outcomes, .solutions-kpi-strip, .industry-kpi-strip, .products-kpi-strip, .impact-kpi-strip, .impact-number-grid').forEach(section => countObserver.observe(section));

  // Product console content switcher
  const productData = {
    mobility: ['MOBILITY INTELLIGENCE', '96.6%', '1,254', '23'],
    vision: ['VISION AI CONTROL', '98.2%', '846', '12'],
    hospitality: ['HOSPITALITY OPERATIONS', '94.8%', '3,208', '31'],
    enterprise: ['ENTERPRISE COMMAND', '97.4%', '2,764', '18']
  };
  $$('.product-tabs button').forEach(btn => btn.addEventListener('click', () => {
    $$('.product-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const [status, efficiency, assets, events] = productData[btn.dataset.product];
    $('#consoleStatus').textContent = status;
    $('#efficiencyValue').textContent = efficiency;
    $('#assetValue').textContent = assets;
    $('#eventValue').textContent = events;
    $('.product-console').animate([{ opacity: .65, transform: 'scale(.985)' }, { opacity: 1, transform: 'scale(1)' }], { duration: 420 });
  }));

  // Subtle 3D tilt
  $$('[data-tilt]').forEach(card => {
    card.addEventListener('mousemove', e => {
      if (innerWidth < 900) return;
      const r = card.getBoundingClientRect();
      const x = (e.clientX - r.left) / r.width - .5;
      const y = (e.clientY - r.top) / r.height - .5;
      card.style.transform = `perspective(1100px) rotateY(${x * 3}deg) rotateX(${y * -3}deg)`;
    });
    card.addEventListener('mouseleave', () => card.style.transform = '');
  });

  // Cursor glow
  const glow = $('.cursor-glow');
  window.addEventListener('pointermove', e => {
    if (glow) glow.style.transform = `translate(${e.clientX - 210}px, ${e.clientY - 210}px)`;
  }, { passive: true });

  // Video placeholder modal
  const modal = $('.video-modal');
  $('[data-open-video]')?.addEventListener('click', () => { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); });
  $('.video-modal>button')?.addEventListener('click', () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); });
  modal?.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });

  // Demo form (front-end only)
  $('#contactForm')?.addEventListener('submit', e => {
    e.preventDefault();
    $('#formMessage').textContent = 'Thank you. Connect this form to your PHP mail/API endpoint before publishing.';
    e.target.reset();
  });
  $('#year').textContent = new Date().getFullYear();

  // Lightweight animated network background
  const canvas = $('#networkCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let w, h, points = [];
  const resize = () => {
    w = canvas.width = innerWidth * devicePixelRatio;
    h = canvas.height = innerHeight * devicePixelRatio;
    canvas.style.width = `${innerWidth}px`;
    canvas.style.height = `${innerHeight}px`;
    const count = Math.min(65, Math.floor(innerWidth / 22));
    points = Array.from({ length: count }, () => ({ x: Math.random() * w, y: Math.random() * h, vx: (Math.random() - .5) * .18 * devicePixelRatio, vy: (Math.random() - .5) * .18 * devicePixelRatio }));
  };
  const draw = () => {
    ctx.clearRect(0, 0, w, h);
    for (const p of points) {
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0 || p.x > w) p.vx *= -1;
      if (p.y < 0 || p.y > h) p.vy *= -1;
      ctx.fillStyle = 'rgba(237,28,36,.55)';
      ctx.beginPath(); ctx.arc(p.x, p.y, 1.3 * devicePixelRatio, 0, Math.PI * 2); ctx.fill();
    }
    for (let i = 0; i < points.length; i++) for (let j = i + 1; j < points.length; j++) {
      const dx = points[i].x - points[j].x, dy = points[i].y - points[j].y;
      const d = Math.hypot(dx, dy), limit = 150 * devicePixelRatio;
      if (d < limit) {
        ctx.strokeStyle = `rgba(237,28,36,${(1 - d / limit) * .15})`;
        ctx.beginPath(); ctx.moveTo(points[i].x, points[i].y); ctx.lineTo(points[j].x, points[j].y); ctx.stroke();
      }
    }
    requestAnimationFrame(draw);
  };
  addEventListener('resize', resize); resize(); draw();
})();


const productGrid = document.querySelector('#productGrid');
if (productGrid) {
  const cards = [...productGrid.querySelectorAll('.product-card')];
  const tabs = [...document.querySelectorAll('.product-tabs button')];
  const searchInput = document.querySelector('#productSearch');
  let activeFilter = 'all';
  const applyProductFilters = () => {
    const query = (searchInput?.value || '').trim().toLowerCase();
    cards.forEach(card => {
      const categoryMatch = activeFilter === 'all' || card.dataset.category === activeFilter;
      const searchMatch = !query || (card.dataset.search || '').includes(query);
      card.classList.toggle('is-hidden', !(categoryMatch && searchMatch));
    });
  };
  tabs.forEach(tab => tab.addEventListener('click', () => {
    tabs.forEach(btn => btn.classList.remove('active'));
    tab.classList.add('active');
    activeFilter = tab.dataset.filter;
    applyProductFilters();
  }));
  searchInput?.addEventListener('input', applyProductFilters);
}


// Technology motion activation
const technologyMotionItems = document.querySelectorAll(
  '.technology-capability-grid article, .technology-enabler-grid article, .tech-node'
);
if (technologyMotionItems.length) {
  const technologyMotionObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) entry.target.classList.add('motion-active');
    });
  }, { threshold: 0.2 });

  technologyMotionItems.forEach((item, index) => {
    item.style.setProperty('--motion-delay', `${index * 90}ms`);
    technologyMotionObserver.observe(item);
  });
}


// Login portal platform selector
const platformCards = [...document.querySelectorAll('.platform-card')];
const selectedTitle = document.querySelector('#selectedPlatformTitle');
const selectedSubtitle = document.querySelector('#selectedPlatformSubtitle');
const loginPanelIcon = document.querySelector('.login-panel-icon');
const platformSearch = document.querySelector('#platformSearch');

if (platformCards.length) {
  platformCards.forEach(card => {
    card.addEventListener('click', () => {
      platformCards.forEach(item => item.classList.remove('active'));
      card.classList.add('active');

      if (selectedTitle) selectedTitle.textContent = card.dataset.title || '';
      if (selectedSubtitle) selectedSubtitle.textContent = card.dataset.subtitle || '';
      if (loginPanelIcon) loginPanelIcon.textContent = card.querySelector('i')?.textContent || '▣';

      document.querySelector('#loginPanel')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  });

  platformSearch?.addEventListener('input', () => {
    const query = platformSearch.value.trim().toLowerCase();
    platformCards.forEach(card => {
      const text = `${card.dataset.title || ''} ${card.dataset.subtitle || ''}`.toLowerCase();
      card.hidden = query && !text.includes(query);
    });
  });
}

const passwordToggle = document.querySelector('.password-toggle');
const loginPassword = document.querySelector('#loginPassword');
passwordToggle?.addEventListener('click', () => {
  if (!loginPassword) return;
  const show = loginPassword.type === 'password';
  loginPassword.type = show ? 'text' : 'password';
  passwordToggle.textContent = show ? '◉' : '◎';
  passwordToggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
