<?php
$pageTitle = 'Vender';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-4 sm:mb-5 flex flex-col gap-3">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Punto de venta</h1>
        <p class="muted mt-1 text-sm sm:text-base">Escanea, busca o escribe el código</p>
    </div>
    <div class="grid grid-cols-2 gap-2 sm:flex sm:gap-2">
        <button type="button" id="btn-scan" class="btn-primary touch-target px-4 py-3 text-sm">
            Abrir cámara
        </button>
        <button type="button" id="btn-clear" class="btn-ghost touch-target px-4 py-3 text-sm">
            Vaciar carrito
        </button>
    </div>
</div>

<div id="scan-panel" class="hidden mb-4 panel p-3 sm:p-4">
    <div class="flex items-center justify-between mb-3 gap-2">
        <h2 class="font-semibold text-sm sm:text-base">Lector de barras</h2>
        <button type="button" id="btn-stop-scan" class="touch-target px-3 rounded-lg text-sm muted">Cerrar</button>
    </div>
    <div class="scan-frame w-full max-w-xl mx-auto">
        <div id="reader" class="w-full"></div>
    </div>
    <p class="text-center text-xs muted mt-3 px-2">Apunta al código. En el celular permite el acceso a la cámara.</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-5">
    <div class="xl:col-span-3 space-y-3 sm:space-y-4 min-w-0">
        <div class="panel p-3 sm:p-4">
            <div class="flex flex-col gap-2">
                <input id="barcode-input" type="text" inputmode="search" enterkeyhint="done"
                       placeholder="Código o nombre del producto…"
                       class="field mono" autocomplete="off" autocorrect="off" spellcheck="false">
                <button type="button" id="btn-add-code" class="btn-ghost touch-target w-full sm:w-auto px-5 py-3 text-sm font-medium">
                    Agregar
                </button>
            </div>
            <div id="search-results" class="mt-3 space-y-1 max-h-56 overflow-y-auto hidden"></div>
            <p id="pos-msg" class="mt-2 text-sm muted"></p>
        </div>

        <div class="panel overflow-hidden min-w-0">
            <div class="px-3 sm:px-4 py-3 border-b divider flex justify-between items-center">
                <h2 class="font-semibold">Carrito</h2>
                <span id="cart-count" class="text-xs muted">0 ítems</span>
            </div>
            <div id="cart-body" class="divide-y divider">
                <div id="cart-empty" class="px-4 py-10 text-center muted text-sm">
                    Escanea o busca un producto
                </div>
            </div>
        </div>
    </div>

    <div class="xl:col-span-2 min-w-0">
        <div class="panel p-4 sm:p-5 space-y-4 pos-checkout-bar">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Cobrar</h2>
                <span id="total-label" class="mono text-xl font-bold text-accent">S/ 0.00</span>
            </div>

            <label class="block text-xs muted">Cliente
                <input id="customer" type="text" value="Cliente general" class="field mt-1">
            </label>

            <label class="block text-xs muted">Método de pago
                <select id="payment-method" class="field mt-1">
                    <option value="efectivo">Efectivo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </label>

            <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs muted">Descuento
                    <input id="discount" type="number" min="0" step="0.01" value="0" inputmode="decimal" class="field mt-1 mono">
                </label>
                <div id="cash-fields">
                    <label class="block text-xs muted">Recibido
                        <input id="amount-paid" type="number" min="0" step="0.01" value="0" inputmode="decimal" class="field mt-1 mono">
                    </label>
                </div>
            </div>

            <div class="surface p-3 sm:p-4 space-y-2 text-sm">
                <div class="flex justify-between muted">
                    <span>Subtotal</span>
                    <span id="subtotal-label" class="mono">S/ 0.00</span>
                </div>
                <div class="flex justify-between muted">
                    <span>Descuento</span>
                    <span id="discount-label" class="mono">S/ 0.00</span>
                </div>
                <div class="flex justify-between muted">
                    <span>Vuelto</span>
                    <span id="change-label" class="mono text-success">S/ 0.00</span>
                </div>
            </div>

            <button type="button" id="btn-checkout" class="btn-primary touch-target w-full py-4 text-base font-bold">
                Generar boleta
            </button>
            <p id="checkout-error" class="text-sm text-danger hidden"></p>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(() => {
    const cart = new Map();
    let scanner = null;
    let lastScan = '';
    let lastScanAt = 0;

    const els = {
        barcode: document.getElementById('barcode-input'),
        results: document.getElementById('search-results'),
        msg: document.getElementById('pos-msg'),
        cartBody: document.getElementById('cart-body'),
        cartCount: document.getElementById('cart-count'),
        subtotal: document.getElementById('subtotal-label'),
        discountLabel: document.getElementById('discount-label'),
        total: document.getElementById('total-label'),
        change: document.getElementById('change-label'),
        discount: document.getElementById('discount'),
        amountPaid: document.getElementById('amount-paid'),
        payment: document.getElementById('payment-method'),
        cashFields: document.getElementById('cash-fields'),
        customer: document.getElementById('customer'),
        checkoutErr: document.getElementById('checkout-error'),
        scanPanel: document.getElementById('scan-panel'),
    };

    function setMsg(text, ok = true) {
        els.msg.textContent = text || '';
        els.msg.className = 'mt-2 text-sm ' + (ok ? 'text-success' : 'text-danger');
    }

    function totals() {
        let sub = 0;
        for (const item of cart.values()) sub += item.price * item.qty;
        const disc = Math.min(Math.max(0, parseFloat(els.discount.value) || 0), sub);
        const total = sub - disc;
        const paid = parseFloat(els.amountPaid.value) || 0;
        const change = els.payment.value === 'efectivo' ? Math.max(0, paid - total) : 0;
        return { sub, disc, total, paid, change };
    }

    function renderCart() {
        els.cartBody.innerHTML = '';

        if (cart.size === 0) {
            els.cartBody.innerHTML = '<div id="cart-empty" class="px-4 py-10 text-center muted text-sm">Escanea o busca un producto</div>';
            els.cartCount.textContent = '0 ítems';
            renderTotals();
            return;
        }

        let count = 0;
        for (const item of cart.values()) {
            count += item.qty;
            const row = document.createElement('div');
            row.className = 'cart-row px-3 sm:px-4 py-3';
            row.innerHTML = `
                <div class="flex items-start justify-between gap-3 min-w-0">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-sm leading-snug truncate">${escapeHtml(item.name)}</p>
                        <p class="text-[11px] mono muted mt-0.5 truncate">${escapeHtml(item.barcode)} · stock ${item.stock}</p>
                        <p class="text-xs muted mt-1 mono">${Midas.formatMoney(item.price)} c/u</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="mono text-sm font-semibold">${Midas.formatMoney(item.price * item.qty)}</p>
                        <button type="button" data-rm="${item.id}" class="touch-target mt-1 text-danger text-xs px-1">Quitar</button>
                    </div>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <button type="button" data-dec="${item.id}" class="btn-ghost touch-target w-11 h-11 text-lg">−</button>
                    <span class="w-10 text-center mono text-base">${item.qty}</span>
                    <button type="button" data-inc="${item.id}" class="btn-ghost touch-target w-11 h-11 text-lg">+</button>
                </div>`;
            els.cartBody.appendChild(row);
        }
        els.cartCount.textContent = count + (count === 1 ? ' ítem' : ' ítems');
        renderTotals();
    }

    function renderTotals() {
        const t = totals();
        els.subtotal.textContent = Midas.formatMoney(t.sub);
        els.discountLabel.textContent = Midas.formatMoney(t.disc);
        els.total.textContent = Midas.formatMoney(t.total);
        els.change.textContent = Midas.formatMoney(t.change);
        els.cashFields.classList.toggle('hidden', els.payment.value !== 'efectivo');
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    async function fetchByBarcode(code) {
        const res = await fetch('api/product_by_barcode.php?barcode=' + encodeURIComponent(code));
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'No encontrado');
        return data.product;
    }

    function addProduct(product, qty = 1) {
        const existing = cart.get(product.id);
        const nextQty = (existing?.qty || 0) + qty;
        if (nextQty > product.stock) {
            setMsg('Stock insuficiente para ' + product.name, false);
            return false;
        }
        cart.set(product.id, { ...product, qty: nextQty });
        Midas.beep();
        setMsg('Agregado: ' + product.name);
        renderCart();
        return true;
    }

    async function addByCode(code) {
        code = String(code || '').trim();
        if (!code) return;
        try {
            const product = await fetchByBarcode(code);
            addProduct(product);
            els.barcode.value = '';
            els.results.classList.add('hidden');
        } catch (e) {
            setMsg(e.message, false);
        }
    }

    async function search(q) {
        const res = await fetch('api/search_products.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        if (!data.ok) return;
        els.results.innerHTML = '';
        if (!data.products.length) {
            els.results.classList.add('hidden');
            return;
        }
        els.results.classList.remove('hidden');
        data.products.forEach(p => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-left px-3 py-3 min-h-11 rounded-lg row-hover text-sm flex justify-between gap-3';
            btn.innerHTML = `<span class="min-w-0"><span class="block truncate">${escapeHtml(p.name)}</span>
                             <span class="text-xs muted mono">${escapeHtml(p.barcode)}</span></span>
                             <span class="mono text-accent shrink-0">${Midas.formatMoney(p.price)}</span>`;
            btn.addEventListener('click', () => {
                addProduct(p);
                els.barcode.value = '';
                els.results.classList.add('hidden');
            });
            els.results.appendChild(btn);
        });
    }

    let searchTimer;
    els.barcode.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = els.barcode.value.trim();
        if (q.length < 2) {
            els.results.classList.add('hidden');
            return;
        }
        searchTimer = setTimeout(() => search(q), 220);
    });

    els.barcode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addByCode(els.barcode.value);
        }
    });

    document.getElementById('btn-add-code').addEventListener('click', () => addByCode(els.barcode.value));
    document.getElementById('btn-clear').addEventListener('click', () => {
        cart.clear();
        renderCart();
        setMsg('Carrito vacío');
    });

    els.cartBody.addEventListener('click', (e) => {
        const t = e.target.closest('[data-inc],[data-dec],[data-rm]');
        if (!t) return;
        if (t.dataset.inc) {
            const id = +t.dataset.inc;
            const item = cart.get(id);
            if (item && item.qty < item.stock) { item.qty++; renderCart(); }
            else setMsg('Stock insuficiente', false);
        }
        if (t.dataset.dec) {
            const id = +t.dataset.dec;
            const item = cart.get(id);
            if (!item) return;
            item.qty--;
            if (item.qty <= 0) cart.delete(id);
            renderCart();
        }
        if (t.dataset.rm) {
            cart.delete(+t.dataset.rm);
            renderCart();
        }
    });

    [els.discount, els.amountPaid, els.payment].forEach(el => el.addEventListener('input', renderTotals));
    els.payment.addEventListener('change', renderTotals);

    async function startScanner() {
        if (!window.Html5Qrcode) {
            setMsg('No se pudo cargar el lector de cámara', false);
            return;
        }
        els.scanPanel.classList.remove('hidden');
        els.scanPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        scanner = new Html5Qrcode('reader');
        const boxSize = Math.min(280, Math.floor(window.innerWidth * 0.75));
        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: boxSize, height: Math.floor(boxSize * 0.45) }, aspectRatio: 1.777 },
                (decoded) => {
                    const now = Date.now();
                    if (decoded === lastScan && now - lastScanAt < 1800) return;
                    lastScan = decoded;
                    lastScanAt = now;
                    addByCode(decoded);
                },
                () => {}
            );
        } catch (err) {
            setMsg('No se pudo acceder a la cámara: ' + err, false);
            els.scanPanel.classList.add('hidden');
        }
    }

    async function stopScanner() {
        if (scanner) {
            try { await scanner.stop(); scanner.clear(); } catch (_) {}
            scanner = null;
        }
        els.scanPanel.classList.add('hidden');
    }

    document.getElementById('btn-scan').addEventListener('click', startScanner);
    document.getElementById('btn-stop-scan').addEventListener('click', stopScanner);

    document.getElementById('btn-checkout').addEventListener('click', async () => {
        els.checkoutErr.classList.add('hidden');
        if (cart.size === 0) {
            els.checkoutErr.textContent = 'El carrito está vacío';
            els.checkoutErr.classList.remove('hidden');
            return;
        }
        const t = totals();
        if (els.payment.value === 'efectivo' && t.paid < t.total) {
            els.checkoutErr.textContent = 'El monto recibido es menor al total';
            els.checkoutErr.classList.remove('hidden');
            return;
        }

        const payload = {
            items: [...cart.values()].map(i => ({ id: i.id, qty: i.qty })),
            payment_method: els.payment.value,
            amount_paid: t.paid,
            discount: t.disc,
            customer_name: els.customer.value,
        };

        const btn = document.getElementById('btn-checkout');
        btn.disabled = true;
        btn.textContent = 'Procesando…';

        try {
            const res = await fetch('api/create_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Error al cobrar');
            await stopScanner();
            window.location.href = data.redirect;
        } catch (e) {
            els.checkoutErr.textContent = e.message;
            els.checkoutErr.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Generar boleta';
        }
    });

    renderCart();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
