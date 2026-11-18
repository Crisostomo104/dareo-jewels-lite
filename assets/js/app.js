document.addEventListener('submit', function (event) {
  const form = event.target;
  if (!(form instanceof HTMLFormElement)) return;
  const message = form.dataset.confirm;
  if (message && !window.confirm(message)) event.preventDefault();
});

document.addEventListener('click', function (event) {
  const opener = event.target.closest('[data-dialog]');
  if (opener) {
    const dialog = document.getElementById(opener.dataset.dialog);
    if (dialog instanceof HTMLDialogElement) dialog.showModal();
    return;
  }
  const closer = event.target.closest('[data-close]');
  if (closer) {
    const dialog = closer.closest('dialog');
    if (dialog instanceof HTMLDialogElement) dialog.close();
  }
});

document.addEventListener('click', function (event) {
  if (event.target instanceof HTMLDialogElement) event.target.close();
});

const saleProduct = document.getElementById('sale-product');
const saleType = document.getElementById('sale-type');
const salePrice = document.getElementById('sale-price');
const saleDown = document.getElementById('sale-down');
const saleHint = document.getElementById('sale-hint');
const salePer = document.getElementById('sale-per-installment');
const saleCount = document.getElementById('sale-count');

function priceFiftyPercent() {
  const price = Number(salePrice ? salePrice.value : 0);
  if (!isFinite(price) || price <= 0) return null;
  return (Math.ceil(price * 50) / 100).toFixed(2);
}

function syncPerInstallment() {
  if (!salePer) return;
  const price = Number(salePrice ? salePrice.value : 0);
  const down = Number(saleDown ? saleDown.value : 0);
  const count = Number(saleCount ? saleCount.value : 0);
  if (!isFinite(price) || !isFinite(down) || !isFinite(count) || count < 1 || price <= down) {
    salePer.value = '';
    return;
  }
  salePer.value = (Math.ceil((price - down) * 100 / count) / 100).toFixed(2);
}

function suggestPrice() {
  if (!saleProduct || !salePrice || !saleDown) return;
  const option = saleProduct.options[saleProduct.selectedIndex];
  if (!option) return;
  const installment = saleType && saleType.value === 'installment';
  const cents = Number(option.dataset[installment ? 'installment' : 'cash'] || 0);
  salePrice.value = cents ? (cents / 100).toFixed(2) : '';
  if (!installment) {
    saleDown.value = cents ? (cents / 100).toFixed(2) : '0.00';
  } else {
    const half = cents ? (Math.ceil(cents / 2) / 100).toFixed(2) : '0.00';
    saleDown.value = half;
  }
  if (saleHint) {
    saleHint.textContent = cents
      ? (installment
          ? 'Suggested price from current settings. Installment downpayment auto-fills at 50% but is editable — minimum 50% of the agreed price.'
          : 'Suggested price from current settings. Downpayment is recorded as a collection on the sale date.')
      : 'No suggested price yet — set acquisition and direct cost on the product first.';
  }
  syncPerInstallment();
}

const saleCountRow = document.getElementById('installment-count-row');
function syncInstallmentCount() {
  if (saleCountRow && saleType) saleCountRow.hidden = saleType.value !== 'installment';
}
if (saleProduct) saleProduct.addEventListener('change', suggestPrice);
if (saleType) saleType.addEventListener('change', suggestPrice);
if (saleType) saleType.addEventListener('change', syncInstallmentCount);
syncInstallmentCount();
if (salePrice) {
  salePrice.addEventListener('input', function () {
    if (saleType && saleType.value === 'installment') {
      const half = priceFiftyPercent();
      if (half !== null && saleDown) saleDown.value = half;
    }
    syncPerInstallment();
  });
}
if (saleDown) saleDown.addEventListener('input', syncPerInstallment);
if (saleCount) saleCount.addEventListener('input', syncPerInstallment);
suggestPrice();
syncPerInstallment();

document.querySelectorAll('select[data-payment-number]').forEach(function (select) {
  select.addEventListener('change', function () {
    const option = select.options[select.selectedIndex];
    const form = select.closest('form');
    const amount = form ? form.querySelector('input[name=amount]') : null;
    if (option && option.dataset.amount && amount) amount.value = option.dataset.amount;
  });
});

/* ---------- Product picker with thumbnails for New sale ---------- */
(function () {
  if (!saleProduct) return;
  const wrap = document.querySelector('[data-product-picker]');
  if (!wrap) return;
  const toggle = wrap.querySelector('[data-picker-toggle]');
  const menu = wrap.querySelector('[data-picker-menu]');
  const search = wrap.querySelector('[data-picker-search]');
  const list = wrap.querySelector('[data-picker-options]');
  const thumb = wrap.querySelector('[data-picker-thumb]');
  const label = wrap.querySelector('[data-picker-name]');
  if (!toggle || !menu || !list) return;

  function thumbMarkup(src) {
    return src ? '<img src="' + src + '" alt="">' : '<span aria-hidden="true">◇</span>';
  }

  function selectOption(option) {
    thumb.innerHTML = thumbMarkup(option.dataset.thumb || '');
    label.textContent = option.textContent;
  }

  function filterList(query) {
    const q = (query || '').trim().toLowerCase();
    Array.from(list.children).forEach(function (item) {
      item.hidden = q !== '' && item.textContent.toLowerCase().indexOf(q) === -1;
    });
  }

  function closeMenu() {
    menu.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
  }

  function openMenu() {
    menu.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    if (search) { search.value = ''; filterList(''); search.focus(); }
  }

  Array.from(saleProduct.options).forEach(function (option) {
    if (!option.value) return;
    const item = document.createElement('button');
    item.type = 'button';
    item.className = 'picker-option';
    item.setAttribute('role', 'option');
    item.dataset.value = option.value;
    item.innerHTML = '<span class="picker-option-thumb">' + thumbMarkup(option.dataset.thumb || '') +
      '</span><span class="picker-option-text"><strong>' + option.textContent + '</strong><small>' +
      (option.dataset.sub || '') + '</small></span>';
    item.addEventListener('click', function () {
      saleProduct.value = option.value;
      saleProduct.dispatchEvent(new Event('change', { bubbles: true }));
      selectOption(option);
      closeMenu();
    });
    list.appendChild(item);
  });

  toggle.addEventListener('click', function () { menu.hidden ? openMenu() : closeMenu(); });
  if (search) search.addEventListener('input', function () { filterList(search.value); });
  document.addEventListener('click', function (event) { if (!wrap.contains(event.target)) closeMenu(); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeMenu(); });
  saleProduct.addEventListener('invalid', openMenu);
  const current = saleProduct.options[saleProduct.selectedIndex];
  if (current && current.value) selectOption(current);
})();

/* ---------- Menu toggles ---------- */
document.addEventListener('click', function (event) {
  const menu = event.target.closest('[data-menu-toggle]');
  if (menu) {
    const nav = document.getElementById('public-nav');
    if (nav) {
      nav.classList.toggle('open');
      menu.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false');
    }
    return;
  }
  const sidebarBtn = event.target.closest('[data-sidebar-toggle]');
  if (sidebarBtn) {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
      const open = sidebar.classList.toggle('open');
      document.body.classList.toggle('sidebar-open', open);
    }
  }
  const navLink = event.target.closest('.public-nav a');
  if (navLink) {
    const nav = document.getElementById('public-nav');
    const menu = document.querySelector('[data-menu-toggle]');
    if (nav) nav.classList.remove('open');
    if (menu) menu.setAttribute('aria-expanded', 'false');
  }
});

/* ---------- Close open menus when clicking outside or pressing Escape ---------- */
document.addEventListener('click', function (event) {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar && sidebar.classList.contains('open')
      && !sidebar.contains(event.target)
      && !event.target.closest('[data-sidebar-toggle]')) {
    sidebar.classList.remove('open');
    document.body.classList.remove('sidebar-open');
  }
  const nav = document.getElementById('public-nav');
  if (nav && nav.classList.contains('open')
      && !nav.contains(event.target)
      && !event.target.closest('[data-menu-toggle]')) {
    nav.classList.remove('open');
    const menu = document.querySelector('[data-menu-toggle]');
    if (menu) menu.setAttribute('aria-expanded', 'false');
  }
});
document.addEventListener('keydown', function (event) {
  if (event.key !== 'Escape') return;
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) { sidebar.classList.remove('open'); document.body.classList.remove('sidebar-open'); }
  const nav = document.getElementById('public-nav');
  const menu = document.querySelector('[data-menu-toggle]');
  if (nav) nav.classList.remove('open');
  if (menu) menu.setAttribute('aria-expanded', 'false');
});

/* ---------- Language toggle (no reload) ---------- */
(function () {
  const button = document.querySelector('[data-lang-toggle]');
  if (!button) return;
  let tagalog = localStorage.getItem('dareo-lang') === 'fil';
  function apply() {
    document.documentElement.lang = tagalog ? 'fil' : 'en';
    button.textContent = tagalog ? 'English' : 'Tagalog';
    document.querySelectorAll('[data-en]').forEach(function (el) {
      el.textContent = tagalog ? (el.dataset.tl || el.textContent) : (el.dataset.en || el.textContent);
    });
    const search = document.querySelector('[data-search]');
    if (search) search.placeholder = tagalog ? 'Maghanap ng alahas' : 'Search jewelry';
    const allOption = document.querySelector('[data-category] option');
    if (allOption) allOption.textContent = tagalog ? 'Lahat ng kategorya' : 'All categories';
    document.querySelectorAll('[data-empty]').forEach(function (el) {
      el.textContent = tagalog ? 'Walang tumutugma sa hinahanap mo.' : 'No pieces match your search.';
    });
    window.dispatchEvent(new CustomEvent('langchange', { detail: { tagalog: tagalog } }));
  }
  button.addEventListener('click', function () {
    tagalog = !tagalog;
    localStorage.setItem('dareo-lang', tagalog ? 'fil' : 'en');
    apply();
  });
  apply();
})();

/* ---------- Live catalog filter + 10-per-page pagination (no reload) ---------- */
(function () {
  const grid = document.querySelector('[data-products]');
  if (!grid) return;
  const cards = Array.from(grid.querySelectorAll('.product-card'));
  const search = document.querySelector('[data-search]');
  const category = document.querySelector('[data-category]');
  const pager = document.querySelector('[data-pagination]');
  const empty = document.querySelector('[data-empty]');
  const count = document.querySelector('[data-result-count]');
  const perPage = 10;
  let page = 1;

  function tagalog() { return localStorage.getItem('dareo-lang') === 'fil'; }
  function matches() {
    const term = (search ? search.value : '').trim().toLowerCase();
    const cat = category ? category.value : '';
    return cards.filter(function (card) {
      const nameOk = !term || (card.dataset.name || '').includes(term);
      const catOk = !cat || card.dataset.category === cat;
      return nameOk && catOk;
    });
  }
  function render() {
    const list = matches();
    const pages = Math.max(1, Math.ceil(list.length / perPage));
    page = Math.min(Math.max(1, page), pages);
    cards.forEach(function (card) { card.hidden = true; });
    list.slice((page - 1) * perPage, page * perPage).forEach(function (card) { card.hidden = false; });
    if (empty) empty.hidden = list.length !== 0;
    if (count) {
      const word = tagalog() ? (list.length === 1 ? 'piraso' : 'mga piraso') : (list.length === 1 ? 'piece' : 'pieces');
      count.textContent = list.length + ' ' + word;
    }
    if (!pager) return;
    pager.innerHTML = '';
    if (pages <= 1) {
      const span = document.createElement('span');
      span.className = 'muted';
      span.textContent = list.length + (tagalog() ? ' item' : ' items');
      pager.appendChild(span);
    } else {
      const add = function (label, target, current, disabled) {
        const el = document.createElement(disabled ? 'span' : 'button');
        el.className = 'page-num' + (current ? ' current' : '');
        el.textContent = label;
        if (!disabled) { el.type = 'button'; el.dataset.page = String(target); }
        else el.setAttribute('aria-disabled', 'true');
        pager.appendChild(el);
      };
      add('←', page - 1, false, page === 1);
      const from = Math.max(1, page - 2);
      const to = Math.min(pages, from + 4);
      for (let i = from; i <= to; i++) add(String(i), i, i === page, false);
      add('→', page + 1, false, page === pages);
    }
    if (window.history && window.history.replaceState) {
      const params = new URLSearchParams(window.location.search);
      if (search && search.value) params.set('q', search.value); else params.delete('q');
      if (category && category.value) params.set('category', category.value); else params.delete('category');
      if (page > 1) params.set('page', String(page)); else params.delete('page');
      window.history.replaceState(null, '', window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash);
    }
  }
  if (search) search.addEventListener('input', function () { page = 1; render(); });
  if (category) category.addEventListener('change', function () { page = 1; render(); });
  if (pager) pager.addEventListener('click', function (event) {
    const button = event.target.closest('[data-page]');
    if (!button) return;
    page = Number(button.dataset.page);
    render();
    window.scrollTo({ top: grid.offsetTop - 120, behavior: 'smooth' });
  });
  window.addEventListener('langchange', render);
  render();
})();

/* ---------- Auto-apply report month (no View button) ---------- */
document.querySelectorAll('[data-auto-submit]').forEach(function (field) {
  field.addEventListener('change', function () {
    if (field.form) field.form.requestSubmit();
  });
});

/* ---------- Admin product filters: auto-apply on status change ---------- */
(function () {
  const select = document.querySelector('[data-admin-filter-submit]');
  if (!select) return;
  select.addEventListener('change', function () { select.form.requestSubmit(); });
})();

/* ---------- Auto SKU + image preview on Add Product ---------- */
(function () {
  const form = document.querySelector('[data-product-form]');
  if (!form) return;
  const name = form.querySelector('[data-name-input]');
  const sku = document.getElementById('sku-field');
  const editing = !!form.querySelector('input[name="id"]');
  if (name && sku && !editing) {
    const next = sku.value;
    sku.dataset.next = next;
    sku.value = '';
    sku.placeholder = next;
    name.addEventListener('input', function () { sku.value = name.value.trim() ? next : ''; });
  }
  const input = form.querySelector('[data-image-input]');
  const stage = form.querySelector('[data-preview-stage]');
  if (input && stage) {
    const grid = stage.querySelector('[data-preview-grid]');
    const emptyState = stage.querySelector('[data-preview-empty]');
    const hasExisting = grid && grid.children.length > 0;
    if (emptyState) emptyState.hidden = hasExisting;
    if (grid) { grid.hidden = !hasExisting; grid.dataset.count = String(grid.children.length); }
    input.addEventListener('change', function () {
      Array.from(input.files || []).slice(0, 6).forEach(function (file) {
        const figure = document.createElement('figure');
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = file.name;
        const caption = document.createElement('figcaption');
        caption.textContent = 'New';
        figure.appendChild(img); figure.appendChild(caption); grid.appendChild(figure);
      });
      grid.dataset.count = String(grid.children.length);
      if (emptyState) emptyState.hidden = true;
      grid.hidden = false;
    });
  }
})();

/* ---------- Live date and time in the admin topbar ---------- */
(function () {
  var el = document.getElementById('admin-clock');
  if (!el) return;
  function refresh() {
    var d = new Date();
    var text = new Intl.DateTimeFormat('en-PH', {
      timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric',
      year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
    }).format(d);
    el.textContent = text;
    el.setAttribute('datetime', d.toISOString());
  }
  refresh();
  setInterval(refresh, 30000);
})();

/* ---------- Hidden admin access from the public logo ---------- */
(function () {
  var brand = document.querySelector('.public-site .brand[data-admin-shortcut]');
  if (!brand) return;
  var adminUrl = brand.getAttribute('data-admin-url') || 'admin';
  var timer = null;
  var startX = 0;
  var startY = 0;

  brand.addEventListener('click', function (event) {
    if (event.ctrlKey || event.metaKey) {
      event.preventDefault();
      window.location.href = adminUrl;
    }
  });

  function cancelLongPress() {
    if (timer !== null) {
      clearTimeout(timer);
      timer = null;
    }
  }

  brand.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'mouse') return;
    startX = event.clientX;
    startY = event.clientY;
    timer = setTimeout(function () {
      timer = null;
      window.location.href = adminUrl;
    }, 700);
  });
  brand.addEventListener('pointerup', cancelLongPress);
  brand.addEventListener('pointercancel', cancelLongPress);
  brand.addEventListener('pointermove', function (event) {
    if (timer !== null && (Math.abs(event.clientX - startX) > 10 || Math.abs(event.clientY - startY) > 10)) {
      cancelLongPress();
    }
  });
  brand.addEventListener('contextmenu', function (event) {
    if (timer !== null) event.preventDefault();
  });
})();
