(function () {
  const root = document.getElementById("pos");
  if (!root) return;

  const igvRate = Number(root.dataset.igv || "18");
  const cart = new Map();
  const scanForm = document.getElementById("scan-form");
  const scanInput = document.getElementById("scan-input");
  const lines = document.getElementById("ticket-lines");
  const toastEl = document.getElementById("toast");
  const dialog = document.getElementById("pay-dialog");
  const payError = document.getElementById("pay-error");
  const cashBox = document.getElementById("cash-box");
  const paidInput = document.getElementById("paid");
  const changeEl = document.getElementById("change");
  const confirmButton = document.getElementById("pay-confirm");
  const beepButton = document.getElementById("beep-toggle");
  let method = "efectivo";
  let submitting = false;
  let toastTimer = 0;
  let scanner = null;
  let lastCode = "";
  let lastAt = 0;
  let wedge = "";
  let wedgeAt = 0;

  function money(cents) {
    const negative = cents < 0;
    const formatted = (Math.abs(cents) / 100).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    return (negative ? "-" : "") + "S/ " + formatted;
  }

  function norm(value) {
    return value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  function toast(message) {
    toastEl.textContent = message;
    toastEl.classList.remove("hidden");
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () {
      toastEl.classList.add("hidden");
    }, 3200);
  }

  function csrf() {
    const tag = document.querySelector('meta[name="csrf-token"]');
    return tag ? tag.content : "";
  }

  function saveCart() {
    sessionStorage.setItem("midas-cart", JSON.stringify(Array.from(cart.values())));
  }

  function loadCart() {
    try {
      const raw = JSON.parse(sessionStorage.getItem("midas-cart") || "[]");
      if (!Array.isArray(raw)) return;
      raw.forEach(function (item) {
        if (!item || !item.id || item.qty < 1) return;
        cart.set(item.id, item);
      });
    } catch (error) {
      sessionStorage.removeItem("midas-cart");
    }
  }

  function hydrateStock() {
    document.querySelectorAll(".add-product").forEach(function (button) {
      const product = readProduct(button);
      const item = cart.get(product.id);
      if (!item) return;
      item.stock = product.stock;
      item.name = product.name;
      item.price_cents = product.price_cents;
      item.barcode = product.barcode;
      if (item.qty > product.stock) item.qty = product.stock;
      if (item.qty < 1) cart.delete(product.id);
    });
  }

  function readProduct(button) {
    return JSON.parse(button.dataset.product);
  }

  function splitIgv(total) {
    const base = Math.round(total / (1 + igvRate / 100));
    return { base: base, igv: total - base };
  }

  function cartTotal() {
    let total = 0;
    cart.forEach(function (item) {
      total += item.price_cents * item.qty;
    });
    return total;
  }

  function roundButton(label, id, op, text) {
    const button = document.createElement("button");
    button.type = "button";
    button.dataset.id = String(id);
    button.dataset.op = op;
    button.className = "grid h-11 w-11 place-items-center rounded-full border border-line bg-panel text-lg";
    button.setAttribute("aria-label", text);
    button.textContent = label;
    return button;
  }

  function lineElement(item) {
    const row = document.createElement("div");
    row.className = "border-b border-dashed border-line py-3";
    const top = document.createElement("div");
    top.className = "flex items-start justify-between gap-3";
    const name = document.createElement("p");
    name.className = "font-medium leading-snug";
    name.textContent = item.name;
    const amount = document.createElement("p");
    amount.className = "shrink-0 font-medium tabular-nums";
    amount.textContent = money(item.price_cents * item.qty);
    top.append(name, amount);
    const meta = document.createElement("p");
    meta.className = "mt-1 font-mono text-xs text-muted";
    meta.textContent = item.barcode + " · " + money(item.price_cents);
    const controls = document.createElement("div");
    controls.className = "mt-2 flex items-center gap-2";
    const qty = document.createElement("span");
    qty.className = "min-w-8 text-center font-semibold tabular-nums";
    qty.textContent = String(item.qty);
    const remove = document.createElement("button");
    remove.type = "button";
    remove.dataset.id = String(item.id);
    remove.dataset.op = "remove";
    remove.className = "ml-auto min-h-11 px-2 text-sm font-semibold text-wine";
    remove.textContent = "Quitar";
    controls.append(roundButton("−", item.id, "dec", "Quitar uno de " + item.name), qty, roundButton("+", item.id, "inc", "Agregar uno de " + item.name), remove);
    row.append(top, meta, controls);
    return row;
  }

  function renderCart() {
    lines.replaceChildren();
    if (cart.size === 0) {
      const empty = document.createElement("p");
      empty.className = "py-8 text-center text-sm text-muted";
      empty.textContent = "Escanea un producto o tócalo en el catálogo.";
      lines.append(empty);
    } else {
      cart.forEach(function (item) {
        lines.append(lineElement(item));
      });
    }
    const total = cartTotal();
    const tax = splitIgv(total);
    const units = Array.from(cart.values()).reduce(function (sum, item) {
      return sum + item.qty;
    }, 0);
    document.getElementById("ticket-count").textContent = units + (units === 1 ? " producto" : " productos");
    document.getElementById("ticket-subtotal").textContent = money(tax.base);
    document.getElementById("ticket-igv").textContent = money(tax.igv);
    document.getElementById("ticket-igv-label").textContent = "IGV " + igvRate + "%";
    document.getElementById("ticket-total").textContent = money(total);
    const pay = document.getElementById("pay-open");
    pay.disabled = total <= 0;
    pay.textContent = total > 0 ? "Cobrar " + money(total) : "Cobrar";
    document.getElementById("pay-total").textContent = money(total);
    updateChange();
  }

  function addProduct(product, qty) {
    if (!product.active) {
      toast(product.name + " está inactivo.");
      return false;
    }
    if (product.stock < 1) {
      toast(product.name + " está agotado.");
      return false;
    }
    const existing = cart.get(product.id);
    const current = existing ? existing.qty : 0;
    const next = current + qty;
    if (next > product.stock) {
      toast("Solo hay " + product.stock + " de " + product.name + ".");
      return false;
    }
    cart.set(product.id, {
      id: product.id,
      name: product.name,
      barcode: product.barcode,
      price_cents: product.price_cents,
      stock: product.stock,
      qty: next,
    });
    saveCart();
    renderCart();
    toast("Agregado: " + product.name);
    return true;
  }

  async function addByCode(raw) {
    const text = String(raw || "").trim();
    const match = text.match(/^(\d+)\s*[*x×]\s*(.+)$/i);
    const qty = match ? Number(match[1]) : 1;
    const code = (match ? match[2] : text).trim();
    if (!code) return false;
    if (!Number.isInteger(qty) || qty < 1 || qty > 999) {
      toast("Cantidad inválida.");
      return false;
    }
    try {
      const response = await fetch("/api/productos/codigo?code=" + encodeURIComponent(code), {
        headers: { Accept: "application/json" },
      });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        if (!/^\d+$/.test(code)) {
          document.getElementById("catalog-search").value = code;
          filterCatalog();
        }
        toast((data && data.error) || "Producto no encontrado.");
        return false;
      }
      return addProduct(data.product, qty);
    } catch (error) {
      toast("No se pudo consultar el producto.");
      return false;
    }
  }

  function filterCatalog() {
    const query = norm(document.getElementById("catalog-search").value.trim());
    let shown = 0;
    document.querySelectorAll(".add-product").forEach(function (button) {
      const match = query === "" || norm(button.dataset.search || "").includes(query);
      button.classList.toggle("hidden", !match);
      if (match) shown += 1;
    });
    document.getElementById("catalog-empty").classList.toggle("hidden", shown !== 0);
  }

  function beep() {
    if (beepButton.getAttribute("aria-pressed") !== "true") return;
    const Context = window.AudioContext || window.webkitAudioContext;
    if (!Context) return;
    try {
      const audio = new Context();
      const oscillator = audio.createOscillator();
      const gain = audio.createGain();
      oscillator.frequency.value = 880;
      gain.gain.value = 0.04;
      oscillator.connect(gain);
      gain.connect(audio.destination);
      oscillator.start();
      oscillator.stop(audio.currentTime + 0.07);
      oscillator.onended = function () {
        audio.close();
      };
    } catch (error) {
      return;
    }
  }

  function formats() {
    if (typeof Html5QrcodeSupportedFormats === "undefined") return [];
    return ["EAN_13", "EAN_8", "UPC_A", "UPC_E", "CODE_128", "CODE_39", "CODABAR", "ITF", "QR_CODE"]
      .map(function (name) {
        return Html5QrcodeSupportedFormats[name];
      })
      .filter(function (value) {
        return value !== undefined;
      });
  }

  function scannerConfig() {
    return {
      fps: 12,
      qrbox: function (width, height) {
        return {
          width: Math.min(340, Math.floor(width * 0.92)),
          height: Math.min(150, Math.floor(height * 0.4)),
        };
      },
    };
  }

  async function onDetect(text) {
    const code = String(text || "").trim();
    const now = Date.now();
    if (!code || (code === lastCode && now - lastAt < 1300)) return;
    lastCode = code;
    lastAt = now;
    const added = await addByCode(code);
    if (added) beep();
  }

  function showScanner(message) {
    const overlay = document.getElementById("scanner");
    overlay.classList.remove("hidden");
    overlay.classList.add("flex");
    document.getElementById("scanner-status").textContent = message;
  }

  async function closeScanner() {
    const overlay = document.getElementById("scanner");
    if (scanner) {
      try {
        await scanner.stop();
      } catch (error) {
        // The camera may already be stopped.
      }
      try {
        scanner.clear();
      } catch (error) {
        // The preview node is already gone.
      }
      scanner = null;
    }
    overlay.classList.add("hidden");
    overlay.classList.remove("flex");
  }

  async function openScanner() {
    showScanner("Preparando la cámara…");
    if (!window.isSecureContext) {
      document.getElementById("scanner-status").textContent = "El navegador solo abre la cámara en localhost o HTTPS. En esta computadora entra por http://127.0.0.1:8000. Un lector USB sigue funcionando en el campo de código.";
      return;
    }
    if (typeof Html5Qrcode === "undefined") {
      document.getElementById("scanner-status").textContent = "No se cargó el lector. Recarga la página.";
      return;
    }
    try {
      scanner = new Html5Qrcode("reader", {
        formatsToSupport: formats(),
        verbose: false,
        experimentalFeatures: { useBarCodeDetectorIfSupported: true },
      });
      try {
        await scanner.start({ facingMode: "environment" }, scannerConfig(), onDetect, function () {});
      } catch (error) {
        const cameras = await Html5Qrcode.getCameras();
        if (!cameras.length) throw error;
        await scanner.start(cameras[cameras.length - 1].id, scannerConfig(), onDetect, function () {});
      }
      document.getElementById("scanner-status").textContent = "Apunta al código. Al leerlo se agrega una unidad; retíralo y vuelve a apuntar para sumar otra.";
    } catch (error) {
      document.getElementById("scanner-status").textContent = "No se pudo abrir la cámara. Revisa el permiso del navegador o elige una foto del código. El lector USB sigue disponible.";
    }
  }

  function parsePaid(value) {
    const raw = String(value || "").trim().replace(",", ".");
    if (!/^\d+(\.\d{1,2})?$/.test(raw)) return null;
    return Math.round(Number(raw) * 100);
  }

  function updateChange() {
    const total = cartTotal();
    if (method !== "efectivo") {
      cashBox.classList.add("hidden");
      confirmButton.disabled = total <= 0;
      changeEl.textContent = "";
      return;
    }
    cashBox.classList.remove("hidden");
    const paid = parsePaid(paidInput.value);
    if (paid === null) {
      changeEl.textContent = "Ingresa el efectivo recibido.";
      changeEl.className = "mt-3 text-sm font-medium text-muted";
      confirmButton.disabled = true;
      return;
    }
    const diff = paid - total;
    if (diff < 0) {
      changeEl.textContent = "Falta " + money(Math.abs(diff));
      changeEl.className = "mt-3 text-lg font-semibold tabular-nums text-wine";
      confirmButton.disabled = true;
      return;
    }
    changeEl.textContent = "Vuelto " + money(diff);
    changeEl.className = "mt-3 text-lg font-semibold tabular-nums text-moss";
    confirmButton.disabled = total <= 0;
  }

  function openPay() {
    if (cart.size === 0) {
      toast("Agrega productos antes de cobrar.");
      return;
    }
    closeScanner();
    payError.classList.add("hidden");
    if (!paidInput.value) paidInput.value = (cartTotal() / 100).toFixed(2);
    updateChange();
    dialog.showModal();
    if (method === "efectivo") paidInput.focus();
  }

  async function submitSale() {
    if (submitting || confirmButton.disabled) return;
    submitting = true;
    confirmButton.disabled = true;
    const previous = confirmButton.textContent;
    confirmButton.textContent = "Registrando…";
    payError.classList.add("hidden");
    try {
      const response = await fetch("/api/ventas", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-Token": csrf(),
        },
        body: JSON.stringify({
          items: Array.from(cart.values()).map(function (item) {
            return { id: item.id, qty: item.qty };
          }),
          payment_method: method,
          paid: paidInput.value,
          customer_name: document.getElementById("customer-name").value,
          customer_doc: document.getElementById("customer-doc").value,
        }),
      });
      const data = await response.json();
      if (!response.ok || !data.ok) {
        const message = (data && data.error) || "No se pudo cobrar.";
        payError.textContent = message;
        payError.classList.remove("hidden");
        toast(message);
        return;
      }
      sessionStorage.removeItem("midas-cart");
      window.location.assign("/caja?venta=" + data.sale.id);
    } catch (error) {
      payError.textContent = "No se pudo registrar la venta.";
      payError.classList.remove("hidden");
    } finally {
      submitting = false;
      confirmButton.textContent = previous;
      updateChange();
    }
  }

  scanForm.addEventListener("submit", async function (event) {
    event.preventDefault();
    const added = await addByCode(scanInput.value);
    if (added) {
      beep();
      scanInput.value = "";
    } else {
      scanInput.select();
    }
    scanInput.focus();
  });

  document.getElementById("catalog").addEventListener("click", function (event) {
    const button = event.target.closest(".add-product");
    if (!button || button.disabled) return;
    addProduct(readProduct(button), 1);
  });

  lines.addEventListener("click", function (event) {
    const button = event.target.closest("button");
    if (!button) return;
    const item = cart.get(Number(button.dataset.id));
    if (!item) return;
    if (button.dataset.op === "inc") {
      if (item.qty + 1 > item.stock) {
        toast("Solo hay " + item.stock + " de " + item.name + ".");
        return;
      }
      item.qty += 1;
    } else if (button.dataset.op === "dec") {
      item.qty -= 1;
      if (item.qty < 1) cart.delete(item.id);
    } else if (button.dataset.op === "remove") {
      cart.delete(item.id);
    }
    saveCart();
    renderCart();
  });

  document.getElementById("catalog-search").addEventListener("input", filterCatalog);
  document.getElementById("pay-open").addEventListener("click", openPay);
  document.getElementById("pay-close").addEventListener("click", function () {
    dialog.close();
    scanInput.focus();
  });
  document.getElementById("pay-form").addEventListener("submit", function (event) {
    event.preventDefault();
    submitSale();
  });
  document.getElementById("pay-methods").addEventListener("click", function (event) {
    const button = event.target.closest("[data-method]");
    if (!button) return;
    method = button.dataset.method;
    document.querySelectorAll(".pay-method").forEach(function (item) {
      item.setAttribute("aria-pressed", item === button ? "true" : "false");
    });
    updateChange();
  });
  paidInput.addEventListener("input", updateChange);
  document.querySelectorAll("[data-bill]").forEach(function (button) {
    button.addEventListener("click", function () {
      const total = cartTotal();
      const value = button.dataset.bill === "exact" ? total / 100 : Number(button.dataset.bill);
      paidInput.value = value.toFixed(2);
      updateChange();
    });
  });
  document.getElementById("open-scanner").addEventListener("click", openScanner);
  document.getElementById("scanner-close").addEventListener("click", closeScanner);
  document.getElementById("scanner-file-button").addEventListener("click", function () {
    document.getElementById("scanner-file").click();
  });
  document.getElementById("scanner-file").addEventListener("change", async function (event) {
    const file = event.target.files && event.target.files[0];
    event.target.value = "";
    if (!file || typeof Html5Qrcode === "undefined") return;
    try {
      const engine = new Html5Qrcode("reader-file", { formatsToSupport: formats(), verbose: false });
      const text = await engine.scanFile(file, true);
      engine.clear();
      const added = await addByCode(text);
      if (added) beep();
    } catch (error) {
      document.getElementById("scanner-status").textContent = "No se leyó un código en esa imagen.";
    }
  });

  beepButton.addEventListener("click", function () {
    const next = beepButton.getAttribute("aria-pressed") !== "true";
    beepButton.setAttribute("aria-pressed", next ? "true" : "false");
    localStorage.setItem("midas-beep", next ? "1" : "0");
  });
  if (localStorage.getItem("midas-beep") === "0") {
    beepButton.setAttribute("aria-pressed", "false");
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") closeScanner();
    const target = event.target;
    const typingElsewhere = target && (target.tagName === "INPUT" || target.tagName === "TEXTAREA" || target.tagName === "SELECT") && target.id !== "scan-input";
    if (typingElsewhere || (target && target.id === "scan-input")) return;
    if (event.key === "Enter") {
      if (wedge.length >= 3 && Date.now() - wedgeAt < 120) {
        event.preventDefault();
        const code = wedge;
        wedge = "";
        addByCode(code).then(function (added) {
          if (added) beep();
        });
      }
      return;
    }
    if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
      const now = Date.now();
      if (now - wedgeAt > 120) wedge = "";
      wedge += event.key;
      wedgeAt = now;
    }
  });

  document.addEventListener("visibilitychange", function () {
    if (document.hidden) closeScanner();
  });

  loadCart();
  hydrateStock();
  saveCart();
  renderCart();
  scanInput.focus();
})();
