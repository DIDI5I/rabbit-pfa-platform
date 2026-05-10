/* ============================================================
   client.js — Rabbit B2B MRO Platform — Portail Acheteur
   Clean base file.
   Product detail override should be loaded from:
   js/product_detail_patch.js AFTER this file.
   ============================================================ */

let allProducts = [];
let filteredProducts = [];
let cart = [];
let currentProduct = null;
let detailQty = 1;

let clientOrders = [];
let clientNotifications = [];
let activePromotions = [];

document.addEventListener("DOMContentLoaded", async () => {
  console.log("client.js loaded");

  try {
    restoreCart();
    await loadCatalogue();
    await loadActivePromotions();
  } catch (err) {
    console.error("Initial catalogue load failed:", err);
  }

  try {
    const user = await checkAuth("client");

    if (user) {
      const initials = getInitials(user.name || "CL", 2);

      setEl("topbar-avatar", initials);
      setEl("sidebar-avatar", initials);

      if (user.name) {
        setEl("sidebar-username", user.name);
      }

      applyRestoredCart();
      await loadClientNotifications();
    }
  } catch (err) {
    console.warn("Client auth unavailable, catalogue still loaded:", err);
  }
});

/* ============================================================
   RESPONSE HELPERS
   ============================================================ */

function responsePayload(response, fallback = {}) {
  if (!response) return fallback;
  if (Object.prototype.hasOwnProperty.call(response, "data")) {
    return response.data ?? fallback;
  }
  return response;
}

function responseItems(response) {
  const payload = responsePayload(response, []);

  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload.items)) return payload.items;
  if (Array.isArray(payload.products)) return payload.products;
  if (Array.isArray(payload.data)) return payload.data;

  return [];
}

/* ============================================================
   CATALOGUE
   ============================================================ */

async function loadCatalogue() {
  const grid = document.getElementById("catalogue-grid");

  if (!grid) return;

  grid.innerHTML = `
    <div class="catalogue-empty" style="grid-column:1/-1">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Chargement du catalogue…</p>
    </div>
  `;

  try {
    const response = await apiFetchJson("/catalog/products", {
      method: "GET",
    });

    const payload = responsePayload(response, {});
    const items = responseItems(response);

    allProducts = items.filter((p) => p.is_active !== false);

    populateCategoryFilter();
    searchCatalogue();
    applyRestoredCart();

    const label = document.getElementById("results-label");
    if (label) {
      const total = payload?.pagination?.total ?? allProducts.length;
      label.textContent = `${total} produit(s) trouvé(s)`;
    }
  } catch (err) {
    console.error("Catalogue load failed:", err);

    grid.innerHTML = `
      <div class="catalogue-empty" style="grid-column:1/-1">
        <i class="fas fa-circle-exclamation"></i>
        <p>Impossible de charger le catalogue.</p>
        <small>${esc(formatApiError(err))}</small>
      </div>
    `;
  }
}

function populateCategoryFilter() {
  const sel = document.getElementById("cat-category");
  if (!sel) return;

  const current = sel.value || "";
  const cats = [...new Set(allProducts.map((p) => p.category).filter(Boolean))].sort();

  sel.innerHTML = `
    <option value="">Toutes les catégories</option>
    ${cats.map((c) => `
      <option value="${esc(c)}" ${c === current ? "selected" : ""}>
        ${categoryLabel(c)}
      </option>
    `).join("")}
  `;
}

function searchCatalogue(searchVal) {
  const search = (searchVal ?? document.getElementById("cat-search")?.value ?? "")
    .toLowerCase()
    .trim();

  const category = document.getElementById("cat-category")?.value || "";
  const stockFlt = document.getElementById("cat-stock")?.value || "";

  filteredProducts = allProducts.filter((p) => {
    const matchesSearch =
      !search ||
      (p.name || "").toLowerCase().includes(search) ||
      (p.sku || "").toLowerCase().includes(search);

    const matchesCategory = !category || p.category === category;

    const avail = p.availability_status || "AVAILABLE";
    const availKeyMap = {
      AVAILABLE: "ok",
      LOW_AVAILABILITY: "low",
      OUT_OF_STOCK: "out",
    };

    const matchesStock = !stockFlt || availKeyMap[avail] === stockFlt;

    return matchesSearch && matchesCategory && matchesStock;
  });

  if (searchVal !== undefined) {
    const top = document.getElementById("topbar-search");
    const cat = document.getElementById("cat-search");

    if (top && document.activeElement !== top) top.value = searchVal;
    if (cat && document.activeElement !== cat) cat.value = searchVal;
  }

  renderCatalogue();
}

function clearSearch() {
  setValSafe("cat-search", "");
  setValSafe("topbar-search", "");
  setValSafe("cat-category", "");
  setValSafe("cat-stock", "");
  searchCatalogue("");
}

function renderCatalogue() {
  const grid = document.getElementById("catalogue-grid");
  const label = document.getElementById("results-label");

  if (!grid) return;

  if (label) {
    label.textContent = filteredProducts.length > 0
      ? `${filteredProducts.length} produit(s) trouvé(s)`
      : "";
  }

  if (!filteredProducts.length) {
    grid.innerHTML = `
      <div class="catalogue-empty" style="grid-column:1/-1">
        <i class="fas fa-box-open"></i>
        <p>Aucun produit ne correspond à votre recherche.</p>
      </div>
    `;
    return;
  }

  grid.innerHTML = filteredProducts.map((p) => {
    const status = localStockStatus(p);
    const isOut = status.key === "out";
    const promoted = isProductPromoted(p);

    const imgHtml = `
      <div class="product-card-img-wrap"
           onmouseenter="startProductImageHover(this)"
           onmouseleave="stopProductImageHover(this)">
        <img
          class="product-card-img"
          src="${esc(productImageUrl(p))}"
          data-img-main="${esc(productImageUrl(p))}"
          data-img-b="${esc(productImageUrl(p, "b"))}"
          data-img-c="${esc(productImageUrl(p, "c"))}"
          data-img-index="0"
          alt="${esc(p.name)}"
          loading="lazy"
          onerror="productImageFallback(this)"
        >
      </div>
    `;

    return `
      <div class="product-card" onclick="openDetail(${Number(p.id)})">
        <div style="position:relative">
          ${imgHtml}

          ${
            promoted
              ? `<span class="catalogue-promo-sticker">PROMO</span>`
              : ""
          }

          <span class="stock-badge ${status.cls}" style="position:absolute;top:10px;right:10px">
            <i class="fas fa-circle" style="font-size:6px"></i>
            ${status.label}
          </span>
        </div>

        <div class="product-card-body">
          <div class="product-card-name">${esc(p.name)}</div>

          ${
            p.sku
              ? `<div class="product-card-sku" style="font-family:monospace">${esc(p.sku)}</div>`
              : ""
          }

          ${
            p.category
              ? `<div><span class="product-card-cat">${categoryLabel(p.category)}</span></div>`
              : ""
          }
        </div>

        <div class="product-card-footer" onclick="event.stopPropagation()">
          <button
            class="btn-add-cart"
            type="button"
            onclick="addToCart(${Number(p.id)})"
            ${isOut ? "disabled" : ""}
          >
            <i class="fas fa-cart-plus"></i>
            ${isOut ? "Indisponible" : "Ajouter"}
          </button>
        </div>
      </div>
    `;
  }).join("");
}
/* ============================================================
   IMAGE HELPERS
   These may be overridden by product_detail_patch.js
   ============================================================ */
function isProductPromoted(product = {}) {
  if (!Array.isArray(activePromotions) || !activePromotions.length) {
    return false;
  }

  const text = [
    product.name,
    product.sku,
    product.description,
    product.category,
  ].join(" ").toLowerCase();

  /*
    Demo rule based on current backend active promotion:
    "Hydraulic Components Spring Offer"
  */
  return (
    text.includes("hyd") ||
    text.includes("hydraulique") ||
    text.includes("hydraulic")
  );
}
function productImageBaseId(product = {}) {
  return product.id ?? product.product_id ?? product.component_id;
}

function productImageUrl(productOrId = {}, suffix = "") {
  const id = typeof productOrId === "object"
    ? productImageBaseId(productOrId)
    : productOrId;

  if (!id) {
    return "/product-images/default.png";
  }

  return `/product-images/${id}${suffix || ""}.png`;
}

function productImageFallback(img) {
  if (!img) return;

  if (!img.dataset.fallback) {
    img.dataset.fallback = "1";
    img.src = "/product-images/default.png";
  }
}

function startProductImageHover(wrapper) {
  const img = wrapper.querySelector("img");
  if (!img) return;

  stopProductImageHover(wrapper);

  const images = [
    img.dataset.imgMain,
    img.dataset.imgB,
    img.dataset.imgC,
  ].filter(Boolean);

  if (images.length <= 1) return;

  wrapper._hoverInterval = setInterval(() => {
    let index = Number(img.dataset.imgIndex || 0);
    index = (index + 1) % images.length;

    img.dataset.imgIndex = String(index);
    img.src = images[index];
  }, 650);
}

function stopProductImageHover(wrapper) {
  const img = wrapper.querySelector("img");

  if (wrapper._hoverInterval) {
    clearInterval(wrapper._hoverInterval);
    wrapper._hoverInterval = null;
  }

  if (img) {
    img.dataset.imgIndex = "0";
    img.src = img.dataset.imgMain;
  }
}

/* ============================================================
   BASIC PRODUCT DETAIL FALLBACK
   product_detail_patch.js should override openDetail().
   ============================================================ */

async function openDetail(productId) {
  const product = allProducts.find((p) => Number(p.id) === Number(productId));

  if (!product) {
    showToast("Produit introuvable.", "warn");
    return;
  }

  currentProduct = product;
  detailQty = 1;

  const content = document.querySelector(".dash-content");
  if (!content) return;

  setEl("topbar-title", "Détail produit");

  content.innerHTML = `
    <button class="detail-back-btn" onclick="backToCatalogue()">
      ← Retour au catalogue
    </button>

    <div class="product-detail-page">
      <section class="product-detail-hero">
        <div class="product-gallery">
          <div class="product-main-image">
            <img src="${esc(productImageUrl(product))}" alt="${esc(product.name)}" onerror="productImageFallback(this)">
          </div>
        </div>

        <div class="product-detail-info">
          <div class="product-detail-category">${categoryLabel(product.category)}</div>
          <h1>${esc(product.name)}</h1>
          <div class="product-detail-sku">${esc(product.sku || "—")}</div>
          <p class="product-detail-description">${esc(product.description || "")}</p>

          <div class="product-detail-meta-grid">
            <div class="product-detail-meta">
              <span>Disponibilité</span>
              <strong>${localStockStatus(product).label}</strong>
            </div>

            <div class="product-detail-meta">
              <span>Unité</span>
              <strong>${esc(product.unit_of_measure || "—")}</strong>
            </div>

            <div class="product-detail-meta">
              <span>Prix</span>
              <strong>Sur devis</strong>
            </div>

            <div class="product-detail-meta">
              <span>Statut</span>
              <strong>${product.is_active ? "Actif" : "Inactif"}</strong>
            </div>
          </div>

          <button class="btn-add-cart" onclick="addToCart(${Number(product.id)})">
            Ajouter au panier
          </button>
        </div>
      </section>
    </div>
  `;
}

function backToCatalogue() {
  const content = document.querySelector(".dash-content");
  if (!content) return;

  setEl("topbar-title", "Catalogue MRO");

  content.innerHTML = `
    <div class="page-header">
      <h1>Catalogue MRO</h1>
      <p>Recherchez et commandez vos pièces industrielles · filtre actif : <code>is_active = true</code></p>
    </div>

    <div class="filter-bar">
      <div class="filter-search">
        <i class="fas fa-search"></i>
        <input type="text" id="cat-search" placeholder="Nom, SKU…" oninput="searchCatalogue(this.value)">
      </div>

      <select class="filter-select" id="cat-category" onchange="searchCatalogue()">
        <option value="">Toutes les catégories</option>
      </select>

      <select class="filter-select" id="cat-stock" onchange="searchCatalogue()">
        <option value="">Tous les stocks</option>
        <option value="ok">Disponible</option>
        <option value="low">Stock bas</option>
        <option value="out">Rupture</option>
      </select>
    </div>

    <div id="results-label" style="font-size:13px;color:#94a3b8;margin-bottom:16px"></div>

    <div id="promotions-container" style="margin-bottom:16px"></div>

    <div id="catalogue-grid" class="catalogue-grid"></div>
  `;

  populateCategoryFilter();
  renderPromotions();
  searchCatalogue();
  applyRestoredCart();
}

function closeDetail() {
  backToCatalogue();
}

function changeDetailQty(delta) {
  detailQty = Math.max(1, detailQty + delta);
  setEl("detail-qty", String(detailQty));
}

function addFromDetail() {
  if (!currentProduct) return;
  addToCartWithQty(currentProduct.id, detailQty);
}

/* ============================================================
   CART
   ============================================================ */

function addToCart(productId) {
  addToCartWithQty(productId, 1);
}

function addToCartWithQty(productId, qty) {
  const p = allProducts.find((x) => Number(x.id) === Number(productId));
  if (!p) return;

  const status = localStockStatus(p);
  if (status.key === "out") {
    showToast("Produit indisponible.", "warn");
    return;
  }

  const existing = cart.find((i) => Number(i.product.id) === Number(productId));

  if (existing) {
    existing.qty += Number(qty) || 1;
  } else {
    cart.push({ product: p, qty: Number(qty) || 1 });
  }

  saveCart();
  updateCartUI();

  showToast(`${p.name} ajouté (×${Number(qty) || 1}).`, "success");
}

function removeFromCart(id) {
  cart = cart.filter((i) => Number(i.product.id) !== Number(id));
  saveCart();
  updateCartUI();
}

function changeCartQty(id, delta) {
  const item = cart.find((x) => Number(x.product.id) === Number(id));
  if (!item) return;

  item.qty = Math.max(1, item.qty + delta);

  saveCart();
  updateCartUI();
}

function toggleCart() {
  const panel = document.getElementById("cart-panel");
  const overlay = document.getElementById("cart-overlay");

  if (!panel || !overlay) return;

  const open = panel.classList.toggle("open");

  overlay.classList.toggle("open", open);

  if (open) {
    renderCartItems();
  }
}

function renderCartItems() {
  const c = document.getElementById("cart-items");
  if (!c) return;

  if (!cart.length) {
    c.innerHTML = `
      <div class="cart-empty">
        <i class="fas fa-shopping-cart"></i>
        <p>Votre panier est vide</p>
      </div>
    `;
    return;
  }

  c.innerHTML = cart.map(({ product: p, qty }) => {
    const img = `
      <img
        class="cart-item-img"
        src="${esc(productImageUrl(p))}"
        alt="${esc(p.name)}"
        onerror="productImageFallback(this)"
      >
    `;

    return `
      <div class="cart-item">
        ${img}

        <div class="cart-item-info">
          <div class="cart-item-name">${esc(p.name)}</div>
          <div class="cart-item-price">${p.price != null ? formatMAD(Number(p.price) * qty) : "Sur devis"}</div>

          <div class="cart-item-qty">
            <button class="cart-item-qty-btn" type="button" onclick="changeCartQty(${Number(p.id)}, -1)">−</button>
            <span style="font-weight:700;font-size:13px">${qty}</span>
            <button class="cart-item-qty-btn" type="button" onclick="changeCartQty(${Number(p.id)}, 1)">+</button>
          </div>
        </div>

        <button class="cart-item-remove" type="button" onclick="removeFromCart(${Number(p.id)})">
          <i class="fas fa-xmark"></i>
        </button>
      </div>
    `;
  }).join("");
}

function updateCartUI() {
  const n = cart.reduce((sum, item) => sum + item.qty, 0);
  const t = cart.reduce((sum, item) => sum + (Number(item.product.price || 0) * item.qty), 0);

  const countEl = document.getElementById("cart-count");
  if (countEl) {
    countEl.textContent = n;
    countEl.style.display = n > 0 ? "flex" : "none";
  }

  const sidebarBadge = document.getElementById("cart-sidebar-badge");
  if (sidebarBadge) {
    sidebarBadge.textContent = n;
    sidebarBadge.style.display = n > 0 ? "flex" : "none";
  }

  const totalEl = document.getElementById("cart-total");
  if (totalEl) {
    totalEl.textContent = t.toLocaleString("fr-MA");
  }

  if (document.getElementById("cart-panel")?.classList.contains("open")) {
    renderCartItems();
  }
}

function saveCart() {
  try {
    sessionStorage.setItem(
      "rabbit_cart",
      JSON.stringify(cart.map((i) => ({ id: i.product.id, qty: i.qty })))
    );
  } catch {}
}

function restoreCart() {
  try {
    const raw = sessionStorage.getItem("rabbit_cart");
    if (raw) {
      sessionStorage._cartTemp = JSON.parse(raw);
    }
  } catch {}
}

function applyRestoredCart() {
  const restored = sessionStorage._cartTemp;

  if (!restored || !Array.isArray(restored)) {
    updateCartUI();
    return;
  }

  cart = [];

  restored.forEach(({ id, qty }) => {
    const p = allProducts.find((x) => Number(x.id) === Number(id));
    if (p) {
      cart.push({ product: p, qty: Number(qty) || 1 });
    }
  });

  delete sessionStorage._cartTemp;

  updateCartUI();
}

async function checkout() {
  if (!cart.length) {
    showToast("Votre panier est vide.", "warn");
    return;
  }

  const btn = document.querySelector(".cart-checkout");

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Envoi…`;
  }

  let ok = 0;
  let fail = 0;

  for (const { product: p, qty } of cart) {
    try {
      await apiFetchJson("/rfqs", {
        method: "POST",
        body: JSON.stringify({
          product_id: p.id,
          quantity_requested: qty,
          notes: `Demande portail acheteur — ${new Date().toLocaleDateString("fr-FR")}`,
        }),
      });

      ok++;
    } catch (err) {
      console.error("RFQ checkout item failed:", err);
      fail++;
    }
  }

  if (btn) {
    btn.disabled = false;
    btn.innerHTML = `<i class="fas fa-file-invoice"></i> Demander un devis (RFQ)`;
  }

  if (ok > 0) {
    cart = [];
    saveCart();
    updateCartUI();
    toggleCart();
    showToast(`${ok} demande(s) de devis créée(s).`, "success");
  }

  if (fail > 0) {
    showToast(`${fail} produit(s) non envoyé(s). Réessayez.`, "warn");
  }
}

/* ============================================================
   CLIENT ORDERS
   ============================================================ */

async function loadClientOrders() {
  try {
    const response = await apiFetchJson("/orders", {
      method: "GET",
    });

    clientOrders = responseItems(response);
    renderClientOrders();
  } catch (err) {
    console.warn("Orders unavailable:", err);
    clientOrders = [];
    renderClientOrders();
  }
}

function renderClientOrders() {
  const tbody = document.getElementById("client-orders-tbody");
  if (!tbody) return;

  const sub = document.getElementById("client-orders-sub");
  if (sub) {
    sub.textContent = `${clientOrders.length} commande(s)`;
  }

  if (!clientOrders.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="4" style="text-align:center;padding:40px 0;color:#94a3b8">
          <div style="font-size:28px;margin-bottom:8px">📋</div>
          <div style="font-size:13px">Aucune commande</div>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = clientOrders.map((o) => {
    const s = ORDER_STATUS[o.status] || {
      label: esc(o.status || "—"),
      cls: "badge-draft",
    };

    return `
      <tr>
        <td style="font-size:12px;font-weight:700;color:#94a3b8">
          #${esc(o.id || o.order_id || "—")}
        </td>
        <td style="font-weight:600;font-size:13px">
          ${formatMAD(o.total_amount || 0)}
        </td>
        <td>
          <span class="badge ${s.cls}">${s.label}</span>
        </td>
        <td style="font-size:12px;color:#94a3b8">
          ${formatDate(o.created_at)}
        </td>
      </tr>
    `;
  }).join("");
}

async function createClientOrder(items, shippingAddress) {
  if (!items || !items.length) {
    showToast("Panier vide.", "warn");
    return;
  }

  try {
    await apiFetchJson("/orders", {
      method: "POST",
      body: JSON.stringify({
        shipping_address: shippingAddress,
        items: items.map((i) => ({
          component_id: i.product.id,
          quantity: i.qty,
          unit_price: i.product.price || 0,
        })),
      }),
    });

    showToast("Commande créée avec succès.", "success");
    await loadClientOrders();
  } catch (err) {
    showToast(formatApiError(err), "error");
  }
}

/* ============================================================
   NOTIFICATIONS
   ============================================================ */

async function loadClientNotifications() {
  try {
    const response = await apiFetchJson("/notifications", {
      method: "GET",
    });

    clientNotifications = responseItems(response);
    updateClientNotifBadge();
  } catch (err) {
    console.warn("Notifications unavailable:", err);
    clientNotifications = [];
    updateClientNotifBadge();
  }
}

async function loadAndRenderClientNotifications() {
  try {
    await loadClientNotifications();

    const list = document.getElementById("client-notif-list");
    if (!list) return;

    if (!clientNotifications.length) {
      list.innerHTML = `<p class="empty-centered">Aucune notification</p>`;
      return;
    }

    list.innerHTML = clientNotifications.map((n) => {
      const label = NOTIFICATION_TYPE_LABELS[n.type] || esc(n.type) || "Notification";
      const isUnread = !n.read_at;

      return `
        <div class="notif-item ${isUnread ? "unread" : ""}">
          <div class="notif-dot-indicator ${isUnread ? "unread" : "read"}"></div>

          <div class="notif-content">
            <div class="notif-title ${isUnread ? "unread" : "read"}">
              ${label}
            </div>
            <div class="notif-body">${esc(n.message || n.body || "—")}</div>
            <div class="notif-time">${formatDate(n.created_at)}</div>
          </div>

          ${
            isUnread
              ? `
                <button class="btn-sm" style="font-size:11px;flex-shrink:0" type="button" onclick="markClientNotifRead(${Number(n.id)})">
                  <i class="fas fa-check"></i>
                </button>
              `
              : ""
          }
        </div>
      `;
    }).join("");
  } catch (err) {
    const list = document.getElementById("client-notif-list");
    if (list) {
      list.innerHTML = `<p class="empty-centered">Erreur chargement notifications</p>`;
    }

    showToast(formatApiError(err), "error");
  }
}

async function markClientNotifRead(id) {
  try {
    await apiFetchJson(`/notifications/${id}/read`, {
      method: "PATCH",
    });

    await loadAndRenderClientNotifications();
  } catch (err) {
    console.warn("Mark notification read failed:", err);
  }
}

async function markAllClientNotifsRead() {
  try {
    await apiFetchJson("/notifications/read-all", {
      method: "PATCH",
    });

    await loadAndRenderClientNotifications();
    showToast("Toutes les notifications marquées comme lues.", "success");
  } catch (err) {
    showToast(formatApiError(err), "error");
  }
}

function updateClientNotifBadge() {
  const unread = clientNotifications.filter((n) => !n.read_at).length;
  const dot = document.getElementById("notif-dot");

  if (dot) {
    dot.style.display = unread > 0 ? "block" : "none";
  }
}

/* ============================================================
   PROMOTIONS
   ============================================================ */

async function loadActivePromotions() {
  try {
    const response = await apiFetchJson("/catalog/promotions/active", {
      method: "GET",
    });

    activePromotions = responseItems(response);
    renderPromotions();
  } catch (err) {
    console.warn("Promotions unavailable:", err);
    activePromotions = [];
    renderPromotions();
  }
}

function renderPromotions() {
  const container = document.getElementById("promotions-container");
  if (!container) return;

  if (!activePromotions.length) {
    container.innerHTML = "";
    return;
  }

  container.innerHTML = activePromotions.map((p) => {
    const discount = p.discount_type === "percentage"
      ? `-${p.discount_value}%`
      : `-${formatMAD(p.discount_value)}`;

    return `
      <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;border-radius:12px;padding:16px;margin-bottom:12px">
        <div style="display:flex;align-items:center;justify-content:space-between">
          <div style="font-weight:700;font-size:14px">${esc(p.title)}</div>
          <span style="background:#ea580c;color:#fff;font-weight:700;font-size:12px;padding:3px 10px;border-radius:20px">
            ${discount}
          </span>
        </div>

        ${
          p.description
            ? `<div style="font-size:12px;color:#64748b;margin-top:4px">${esc(p.description)}</div>`
            : ""
        }

        <div style="font-size:11px;color:#94a3b8;margin-top:6px">
          ${p.ends_at ? `Valable jusqu'au ${formatDate(p.ends_at)}` : ""}
        </div>
      </div>
    `;
  }).join("");
}

/* ============================================================
   SIDE PANELS
   ============================================================ */

function showClientOrdersPanel() {
  const overlay = document.getElementById("orders-overlay");
  const panel = document.getElementById("orders-panel");

  if (overlay) {
    overlay.classList.add("open");
    overlay.style.display = "block";
  }

  if (panel) {
    panel.classList.add("open");
  }

  loadClientOrders();
}

function closeClientOrdersPanel() {
  const overlay = document.getElementById("orders-overlay");
  const panel = document.getElementById("orders-panel");

  if (overlay) {
    overlay.classList.remove("open");
    overlay.style.display = "none";
  }

  if (panel) {
    panel.classList.remove("open");
  }
}

function showClientNotifPanel() {
  const overlay = document.getElementById("notif-overlay");
  const panel = document.getElementById("notif-panel");

  if (overlay) {
    overlay.classList.add("open");
    overlay.style.display = "block";
  }

  if (panel) {
    panel.classList.add("open");
  }

  loadAndRenderClientNotifications();
}

function closeClientNotifPanel() {
  const overlay = document.getElementById("notif-overlay");
  const panel = document.getElementById("notif-panel");

  if (overlay) {
    overlay.classList.remove("open");
    overlay.style.display = "none";
  }

  if (panel) {
    panel.classList.remove("open");
  }
}

/* ============================================================
   LOCAL HELPERS
   ============================================================ */

function localStockStatus(p = {}) {
  const avail = p._availability || p.availability_status;

  const map = {
    AVAILABLE: { key: "ok", cls: "stock-ok", label: "Disponible" },
    LOW_AVAILABILITY: { key: "low", cls: "stock-low", label: "Disponibilité limitée" },
    OUT_OF_STOCK: { key: "out", cls: "stock-out", label: "Rupture de stock" },
  };

  return map[avail] || map.AVAILABLE;
}

function setValSafe(id, value) {
  const el = document.getElementById(id);
  if (el) el.value = value;
}
