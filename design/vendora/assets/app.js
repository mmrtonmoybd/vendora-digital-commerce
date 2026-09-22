/* ==========================================================================
   Vendora — front-end behaviour (Alpine.js stores).
   In Laravel this maps to Livewire components:
     $store.buy   -> <livewire:buy-now-modal />
     $store.cart  -> <livewire:cart /> (session cart)
     $store.user  -> auth()->user() (+ wallet balance)
   Demo data lives in assets/data.js (window.SITE).
   Demo toggles: add ?guest=1 to any URL to view as a guest, ?auth=1 to log back in.
   ========================================================================== */
(function () {
  const S = window.SITE;
  const store = {
    get(k, d) { try { const v = localStorage.getItem('vd_' + k); return v === null ? d : JSON.parse(v); } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem('vd_' + k, JSON.stringify(v)); } catch (e) {} },
  };

  const money = (n) => S.currency + ' ' + Math.round(n).toLocaleString('en-US');
  window.money = money;

  function couponDiscount(code, subtotal) {
    const c = S.coupons[(code || '').trim().toUpperCase()];
    if (!c) return { ok: false, amount: 0, msg: 'Invalid or expired coupon.' };
    if (subtotal < (c.min || 0)) return { ok: false, amount: 0, msg: 'Minimum order ' + money(c.min) + ' for this coupon.' };
    const amt = c.type === 'percent' ? subtotal * c.value / 100 : c.value;
    return { ok: true, amount: Math.min(amt, subtotal), msg: c.label };
  }
  window.couponDiscount = couponDiscount;

  function orderNo() { return 'VD-' + Date.now().toString().slice(-7); }

  // Referral: ?ref=CODE -> cookie (30 days). Laravel: middleware Cookie::queue('ref', ...)
  const ref = new URLSearchParams(location.search).get('ref');
  if (ref) document.cookie = 'ref=' + encodeURIComponent(ref) + ';path=/;max-age=' + 60 * 60 * 24 * 30;
  window.readCookie = (n) => (document.cookie.match('(^|;)\\s*' + n + '=([^;]*)') || [])[2] || '';

  document.addEventListener('alpine:init', () => {
    const qs = new URLSearchParams(location.search);
    if (qs.has('guest')) store.set('auth', false);
    if (qs.has('auth')) store.set('auth', true);

    Alpine.store('user', {
      loggedIn: store.get('auth', true),
      name: S.demoUser.name,
      email: S.demoUser.email,
      balance: S.demoUser.balance,
      logout() { this.loggedIn = false; store.set('auth', false); },
      login() { this.loggedIn = true; store.set('auth', true); },
    });

    Alpine.store('cart', {
      items: store.get('cart', []),
      save() { store.set('cart', this.items); },
      product(slug) { return S.products[slug]; },
      add(slug, qty, fields, email) {
        const p = S.products[slug];
        const line = this.items.find((i) => i.slug === slug && JSON.stringify(i.fields) === JSON.stringify(fields || {}));
        if (line) line.qty = Math.min(line.qty + qty, p.max, p.stock);
        else this.items.push({ slug, qty, fields: fields || {}, email: email || '' });
        this.save();
        Alpine.store('toast').show(p.name + ' added to cart');
      },
      setQty(i, q) { const p = S.products[this.items[i].slug]; this.items[i].qty = Math.max(p.min, Math.min(q, p.max, p.stock)); this.save(); },
      remove(i) { this.items.splice(i, 1); this.save(); },
      clear() { this.items = []; this.save(); },
      get count() { return this.items.reduce((a, i) => a + i.qty, 0); },
      get subtotal() { return this.items.reduce((a, i) => a + S.products[i.slug].price * i.qty, 0); },
    });

    Alpine.store('toast', {
      msg: '', on: false, t: null,
      show(m) { this.msg = m; this.on = true; clearTimeout(this.t); this.t = setTimeout(() => (this.on = false), 2600); },
    });

    // ---- Buy Now modal (Livewire: BuyNowModal) ----
    Alpine.store('buy', {
      show: false, p: null, email: '', qty: 1, fields: {}, coupon: '', couponMsg: '', couponOk: false, discount: 0,
      payment: 'bkash', error: '',
      open(slug) {
        const p = S.products[slug];
        if (!p || p.stock < 1) return;
        const u = Alpine.store('user');
        this.p = p; this.qty = p.min; this.fields = {}; this.coupon = ''; this.couponMsg = ''; this.couponOk = false; this.discount = 0; this.error = '';
        p.fields.forEach((f) => (this.fields[f.key] = f.options ? f.options[0] : ''));
        this.email = u.loggedIn ? u.email : '';
        this.payment = u.loggedIn && u.balance >= p.price * p.min ? 'wallet' : 'bkash';
        this.show = true; document.body.style.overflow = 'hidden';
      },
      close() { this.show = false; document.body.style.overflow = ''; },
      get maxQty() { return this.p ? Math.min(this.p.max, this.p.stock) : 1; },
      inc() { this.qty = Math.min(this.qty + 1, this.maxQty); this.recalc(); },
      dec() { this.qty = Math.max(this.qty - 1, this.p.min); this.recalc(); },
      clampQty() { this.qty = Math.max(this.p.min, Math.min(parseInt(this.qty) || this.p.min, this.maxQty)); this.recalc(); },
      get subtotal() { return this.p ? this.p.price * this.qty : 0; },
      get total() { return Math.max(0, this.subtotal - this.discount); },
      applyCoupon() {
        const r = couponDiscount(this.coupon, this.subtotal);
        this.couponOk = r.ok; this.couponMsg = r.msg; this.discount = r.amount;
      },
      recalc() { if (this.couponOk) this.applyCoupon(); },
      walletDisabled() { const u = Alpine.store('user'); return !u.loggedIn || u.balance < this.total; },
      method() { return S.payments.find((m) => m.key === this.payment); },
      validate() {
        if (!/^\S+@\S+\.\S+$/.test(this.email)) return 'Enter a valid delivery email.';
        for (const f of this.p.fields) if (f.required && !String(this.fields[f.key] || '').trim()) return f.label + ' is required.';
        if (this.payment === 'wallet' && this.walletDisabled()) return 'Wallet balance is not enough.';
        return '';
      },
      addToCart() { this.error = this.validate(); if (this.error && !this.error.startsWith('Wallet')) return; Alpine.store('cart').add(this.p.slug, this.qty, { ...this.fields }, this.email); this.close(); },
      checkout() {
        this.error = this.validate(); if (this.error) return;
        const q = new URLSearchParams({ order: orderNo(), amount: this.total, method: this.payment, product: this.p.name, qty: this.qty });
        location.href = 'order-success.html?' + q.toString();
      },
    });
  });

  // ---- Checkout page component (Livewire: CheckoutPage) ----
  window.checkoutPage = function () {
    return {
      email: '', coupon: '', couponMsg: '', couponOk: false, discount: 0, payment: 'bkash',
      sender: '', trx: '', shot: '', error: '',
      init() {
        const u = Alpine.store('user');
        this.email = u.loggedIn ? u.email : '';
        if (!this.walletDisabled()) this.payment = 'wallet';
      },
      get subtotal() { return Alpine.store('cart').subtotal; },
      get total() { return Math.max(0, this.subtotal - this.discount); },
      applyCoupon() { const r = couponDiscount(this.coupon, this.subtotal); this.couponOk = r.ok; this.couponMsg = r.msg; this.discount = r.amount; },
      walletDisabled() { const u = Alpine.store('user'); return !u.loggedIn || u.balance < this.total; },
      method() { return S.payments.find((m) => m.key === this.payment); },
      place() {
        const m = this.method();
        if (!Alpine.store('cart').items.length) return (this.error = 'Your cart is empty.');
        if (!/^\S+@\S+\.\S+$/.test(this.email)) return (this.error = 'Enter a valid delivery email.');
        if (m.type === 'wallet' && this.walletDisabled()) return (this.error = 'Wallet balance is not enough.');
        if (m.type === 'manual' && (!/^01\d{9}$/.test(this.sender) || this.trx.trim().length < 6)) return (this.error = 'Enter your sender number (01XXXXXXXXX) and transaction ID.');
        const q = new URLSearchParams({ order: orderNo(), amount: this.total, method: this.payment, paid: m.type === 'manual' ? 'submitted' : (m.type === 'wallet' ? 'wallet' : '') });
        Alpine.store('cart').clear();
        location.href = 'order-success.html?' + q.toString();
      },
    };
  };

  // ---- Pay / order success page ----
  window.payPage = function () {
    const q = new URLSearchParams(location.search);
    return {
      order: q.get('order') || 'VD-1048213',
      amount: +(q.get('amount') || 1450),
      m: S.payments.find((x) => x.key === (q.get('method') || 'bkash')) || S.payments[1],
      status: q.get('paid') || '',
      sender: '', trx: '', error: '',
      submit() {
        if (!/^01\d{9}$/.test(this.sender) || this.trx.trim().length < 6) return (this.error = 'Enter your sender number (01XXXXXXXXX) and transaction ID.');
        this.error = ''; this.status = 'submitted';
      },
    };
  };

  // ---- Search page ----
  window.searchPage = function () {
    const q = new URLSearchParams(location.search).get('q') || '';
    return {
      q,
      get results() {
        const s = this.q.trim().toLowerCase();
        const all = Object.values(S.products);
        return s ? all.filter((p) => (p.name + ' ' + p.category + ' ' + p.short).toLowerCase().includes(s)) : all;
      },
    };
  };

  window.copyText = (t) => { try { navigator.clipboard.writeText(t); Alpine.store('toast').show('Copied: ' + t); } catch (e) {} };
})();
