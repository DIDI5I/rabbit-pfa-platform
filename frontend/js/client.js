/* ============================================================
   client.js — Rabbit B2B MRO Platform — Portail Acheteur
   Version optimisée — Utilise les modules partagés
   Dépend de config.js + auth.js + utils.js
   ============================================================ */

let allProducts      = [];
let filteredProducts = [];
let cart             = [];
let currentProduct   = null;
let detailQty        = 1;

document.addEventListener("DOMContentLoaded", async () => {
  const user = await checkAuth("client");
  if (!user) return;
  const initials = getInitials(user.name || "CL", 2);
  setEl("topbar-avatar",initials); setEl("sidebar-avatar",initials);
  if (user.name) setEl("sidebar-username", user.name);
  restoreCart();
  await loadCatalogue();
  await Promise.all([
    loadClientOrders(),
    loadClientNotifications(),
    loadActivePromotions(),   // /catalog/promotions/active
  ]);
});

// ══════════════════════════════════════════════════════════════
// CATALOGUE
// ══════════════════════════════════════════════════════════════
async function loadCatalogue() {
  try {
    // Client catalogue : utiliser /catalog/products (client-safe), jamais /products
    const data = await apiFetchJson("/catalog/products");
    const raw  = unwrap(data);
    // Le client voit uniquement is_active = true
    allProducts = (Array.isArray(raw) ? raw : []).filter(p => p.is_active !== false);
    populateCategoryFilter();
    searchCatalogue();
    applyRestoredCart();
  } catch (err) {
    document.getElementById("catalogue-grid").innerHTML = `
      <div class="catalogue-empty" style="grid-column:1/-1">
        <i class="fas fa-circle-exclamation"></i>
        <p>Impossible de charger le catalogue. ${esc(err?.error||err?.message||"")}</p>
      </div>`;
  }
}

function populateCategoryFilter() {
  const sel = document.getElementById("cat-category");
  if (!sel) return;
  const cats = [...new Set(allProducts.map(p=>p.category).filter(Boolean))].sort();
  sel.innerHTML = `<option value="">Toutes les catégories</option>` +
    cats.map(c => `<option value="${esc(c)}">${categoryLabel(c)}</option>`).join("");
}

function searchCatalogue(searchVal) {
  const search   = (searchVal ?? document.getElementById("cat-search")?.value ?? "").toLowerCase().trim();
  const category = document.getElementById("cat-category")?.value||"";
  const stockFlt = document.getElementById("cat-stock")?.value||"";

  filteredProducts = allProducts.filter(p => {
    const ms = !search||(p.name||"").toLowerCase().includes(search)||(p.sku||"").toLowerCase().includes(search);
    const mc = !category||p.category===category;
    // Catalogue client : utiliser availability_status, pas stock_qty
    const avail = p.availability_status || "AVAILABLE";
    const availKeyMap = { AVAILABLE:"ok", LOW_AVAILABILITY:"low", OUT_OF_STOCK:"out" };
    const mx = !stockFlt || availKeyMap[avail] === stockFlt;
    return ms && mc && mx;
  });

  if (searchVal !== undefined) {
    const top=document.getElementById("topbar-search"), cat=document.getElementById("cat-search");
    if(top&&document.activeElement!==top) top.value=searchVal;
    if(cat&&document.activeElement!==cat) cat.value=searchVal;
  }
  renderCatalogue();
}

function clearSearch() {
  ["cat-search","topbar-search"].forEach(id=>setVal(id,""));
  ["cat-category","cat-stock"].forEach(id=>setVal(id,""));
  searchCatalogue("");
}

function renderCatalogue() {
  const grid  = document.getElementById("catalogue-grid");
  const label = document.getElementById("results-label");
  if (!grid) return;
  if (label) label.textContent = filteredProducts.length > 0 ? `${filteredProducts.length} produit(s) trouvé(s)` : "";

  if (!filteredProducts.length) {
    grid.innerHTML = `<div class="catalogue-empty" style="grid-column:1/-1"><i class="fas fa-box-open"></i><p>Aucun produit ne correspond à votre recherche.</p></div>`;
    return;
  }

  grid.innerHTML = filteredProducts.map(p => {
    const status = localStockStatus(p);
    const isOut  = status.key === "out";
    const imgHtml = p.image_url
      ? `<img class="product-card-img" src="${esc(p.image_url)}" alt="${esc(p.name)}" loading="lazy">`
      : `<div class="product-card-img-placeholder">📦</div>`;
    return `<div class="product-card" onclick="openDetail(${p.id})">
        <div style="position:relative">
          ${imgHtml}
          <span class="stock-badge ${status.cls}" style="position:absolute;top:10px;right:10px">
            <i class="fas fa-circle" style="font-size:6px"></i>${status.label}
          </span>
        </div>
        <div class="product-card-body">
          <div class="product-card-name">${esc(p.name)}</div>
          ${p.sku?`<div class="product-card-sku" style="font-family:monospace">${esc(p.sku)}</div>`:""}
          ${p.category?`<div><span class="product-card-cat">${categoryLabel(p.category)}</span></div>`:""}
          <div class="product-card-price">${p.price!=null?formatMAD(p.price):"Sur devis"}</div>
        </div>
        <div class="product-card-footer" onclick="event.stopPropagation()">
          <button class="btn-detail" onclick="openDetail(${p.id})"><i class="fas fa-info-circle"></i> Détail</button>
          <button class="btn-add-cart" onclick="addToCart(${p.id})" ${isOut?"disabled":""}>
            <i class="fas fa-cart-plus"></i>${isOut?"Indisponible":"Ajouter"}
          </button>
        </div>
      </div>`;
  }).join("");
}

// ══════════════════════════════════════════════════════════════
// DÉTAIL PRODUIT
// Appelle /stock/{id} pour stock_status et current_stock exacts
// ══════════════════════════════════════════════════════════════
async function openDetail(productId) {
  try {
    const p = allProducts.find(x => x.id === productId);
    if (!p) return;
  currentProduct = p; detailQty = 1;
  setEl("detail-qty","1"); setEl("detail-title", p.name);
  const addBtn = document.getElementById("detail-add-btn");
  const ls = localStockStatus(p);
  if (addBtn) addBtn.disabled = ls.key === "out";

  const body = document.getElementById("detail-body");
  body.innerHTML = `
    ${p.image_url?`<img class="detail-img" src="${esc(p.image_url)}" alt="${esc(p.name)}">`:`<div class="detail-img-placeholder">📦</div>`}
    <div class="detail-section">
      <div class="detail-section-title">Informations</div>
      <div class="detail-meta-grid">
        <div><div class="detail-meta-label">Catégorie</div><div class="detail-meta-val">${categoryLabel(p.category)}</div></div>
        <div><div class="detail-meta-label">SKU</div><div class="detail-meta-val" style="font-family:monospace;font-size:12px">${esc(p.sku||"—")}</div></div>
        <div><div class="detail-meta-label">Prix unitaire HT</div>
             <div class="detail-meta-val" style="color:var(--blue);font-size:16px;font-weight:800">${p.price!=null?formatMAD(p.price):"Sur devis"}</div></div>
        <div><div class="detail-meta-label">Unité</div><div class="detail-meta-val">${esc(p.unit_of_measure||"—")}</div></div>
        <div style="grid-column:1/-1">
          <div class="detail-meta-label">Disponibilité</div>
          <div class="detail-meta-val" id="detail-stock-badge">
            ${stockBadgeFromProduct(p)}
            <span id="detail-stock-qty" style="font-size:12px;color:#94a3b8;margin-left:8px">
              ${p.stock_qty!=null?`(stock : ${p.stock_qty} ${p.unit_of_measure||""})`:""}</span>
          </div>
        </div>
      </div>
      ${p.description?`<div style="margin-top:14px;font-size:13px;color:#64748b;line-height:1.6">${esc(p.description)}</div>`:""}
    </div>
    <div class="detail-section" id="relations-section">
      <div class="detail-section-title">Relations produit</div>
      <div id="relations-loader" style="text-align:center;padding:16px 0;color:#94a3b8;font-size:13px">
        <i class="fas fa-spinner fa-spin"></i> Chargement…
      </div>
      <div id="relations-list"></div>
    </div>
    <div id="product-reviews-section" style="padding:0 24px"></div>
  `;

  document.getElementById("detail-overlay").classList.add("open");
  document.getElementById("detail-panel").classList.add("open");

    await Promise.all([
      loadDetailStock(p.id, addBtn),
      loadProductRelations(p.id),
      loadProductReviews(p.id),
    ]);
  } catch (err) {
    showToast("Erreur chargement detail: " + formatApiError(err), "error");
  }
}

// /stock/{id} → current_stock et stock_status exacts
async function loadDetailStock(productId, addBtn) {
  try {
    // /catalog/products/{id} retourne availability_status (AVAILABLE/LOW_AVAILABILITY/OUT_OF_STOCK)
    // Ne pas afficher stock_qty — le catalogue client-safe ne l'expose pas
    const data = await apiFetchJson(`/catalog/products/${productId}`);
    const p    = unwrap(data);
    const badgeEl = document.getElementById("detail-stock-badge");
    const qtyEl   = document.getElementById("detail-stock-qty");
    const avail   = p.availability_status || "AVAILABLE";

    const availMap = {
      AVAILABLE:        { cls:"stock-ok",  label:"Disponible",           disabled:false },
      LOW_AVAILABILITY: { cls:"stock-low", label:"Disponibilité limitée",disabled:false },
      OUT_OF_STOCK:     { cls:"stock-out", label:"Rupture de stock",     disabled:true  },
    };
    const a = availMap[avail] || availMap.AVAILABLE;
    if (badgeEl) badgeEl.innerHTML = `<span class="stock-badge ${a.cls}"><i class="fas fa-circle" style="font-size:6px"></i>${a.label}</span>`;
    if (qtyEl)   qtyEl.textContent = ""; // ne pas afficher stock_qty au client
    if (addBtn)  addBtn.disabled   = a.disabled;
    if (currentProduct?.id === productId) currentProduct._availability = avail;
  } catch {}
}

// /products/{id}/dependencies → relations produit
async function loadProductRelations(productId) {
  // Utilise /recommendations pour grouper automatiquement les produits associés.
  // Fallback sur /dependencies si recommendations n'est pas disponible.
  try {
    // Recommandations via /catalog/products/{id}/relations (catalogue client-safe)
    const data = await apiFetchJson(`/catalog/products/${productId}/relations`);
    const groups = unwrap(data);
    if (groups && typeof groups === "object" && !Array.isArray(groups)) {
      renderDetailRecommendations(groups);
      return;
    }
  } catch {}
  // Fallback : dépendances brutes
  try {
    const data = await apiFetchJson(`/products/${productId}/dependencies`);
    const rels = unwrapItems(data);
    renderDetailRelations(rels);
  } catch { renderDetailRelations([]); }
}

function renderDetailRecommendations(items) {
  // /catalog/products/{id}/relations retourne un tableau plat
  // groupé par relation_type visible au client
  const loader = document.getElementById("relations-loader");
  const list   = document.getElementById("relations-list");
  if (loader) loader.style.display = "none";
  if (!list) return;

  // Si objet (groupé), convertir en tableau plat
  let flat = [];
  if (Array.isArray(items)) {
    flat = items;
  } else if (items && typeof items === "object") {
    Object.entries(items).forEach(([, arr]) => { if (Array.isArray(arr)) flat.push(...arr); });
  }

  if (!flat.length) {
    list.innerHTML = `<p style="font-size:13px;color:#94a3b8;padding:8px 0">Aucune relation produit.</p>`;
    return;
  }

  // Grouper par type de relation
  const grouped = {};
  flat.forEach(r => {
    const type = RELATION_TYPES[r.relation_type] || RECOMMENDATION_SECTION_LABELS[r.section] || esc(r.relation_type) || "Associé";
    if (!grouped[type]) grouped[type] = [];
    grouped[type].push(r);
  });

  list.innerHTML = Object.entries(grouped).map(([type, rels]) => `
    <div style="margin-bottom:16px">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:8px">${esc(type)}</div>
      ${rels.map(r => {
        const prod = allProducts.find(x => x.id === (r.id || r.related_product_id || r.child_id));
        const name = r.name || r.related_product_name || r.child_name || "—";
        const sku  = r.sku  || r.related_product_sku  || r.child_sku  || "";
        return `<div class="relation-item" ${prod ? `onclick="openDetail(${prod.id})" style="cursor:pointer"` : ""}>
          <div class="relation-item-icon"><i class="fas fa-link"></i></div>
          <div style="flex:1">
            <div class="relation-item-name">${esc(name)}</div>
            <div class="relation-item-meta" style="font-family:monospace">${esc(sku)}</div>
          </div>
          ${prod ? `<i class="fas fa-chevron-right" style="color:#94a3b8;font-size:12px"></i>` : ""}
        </div>`;
      }).join("")}
    </div>`).join("");
}

function renderDetailRelations(rels) {
  const loader = document.getElementById("relations-loader");
  const list   = document.getElementById("relations-list");
  if (loader) loader.style.display = "none";
  if (!list) return;

  if (!rels.length) { list.innerHTML = `<p style="font-size:13px;color:#94a3b8;padding:8px 0">Aucune relation produit.</p>`; return; }

  const grouped = {};
  rels.forEach(r => {
    const type = RELATION_TYPES[r.relation_type] || r.relation_type || "Associé";
    if (!grouped[type]) grouped[type] = [];
    grouped[type].push(r);
  });

  const icons = {
    "Structure interne":"fa-layer-group","Pièces de remplacement":"fa-wrench",
    "Pièces compatibles":"fa-puzzle-piece","Alternatives compatibles":"fa-shuffle",
    "Pièces de rechange":"fa-toolbox","Accessoires":"fa-star","Produits associés":"fa-link",
  };

  list.innerHTML = Object.entries(grouped).map(([type, items]) => `
    <div style="margin-bottom:16px">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:8px">${esc(type)}</div>
      ${items.map(r => {
        const icon    = icons[type]||"fa-link";
        const prod    = allProducts.find(x => x.id === r.child_id);
        const phantom = r.is_phantom ? phantomBadge() : "";
        const catLbl  = r.child_category ? categoryLabel(r.child_category) : "";
        return `<div class="relation-item" ${prod?`onclick="openDetail(${prod.id})" style="cursor:pointer"`:""}>
          <div class="relation-item-icon"><i class="fas ${icon}"></i></div>
          <div style="flex:1">
            <div class="relation-item-name">${esc(r.child_name||"Produit #"+r.child_id)}${phantom}</div>
            <div class="relation-item-meta">
              ${r.child_sku?`<span style="font-family:monospace">${esc(r.child_sku)}</span>`:""}
              ${catLbl?` · <span style="color:#8b5cf6;font-weight:700">${catLbl}</span>`:""}
              ${r.qty_required!=null?` · Réf. : ${r.qty_required} ${esc(r.uom||"")}` : ""}
              ${r.notes?`<br><em style="color:#94a3b8">${esc(r.notes)}</em>`:""}
            </div>
          </div>
          ${prod?`<i class="fas fa-chevron-right" style="color:#94a3b8;font-size:12px"></i>`:""}
        </div>`;
      }).join("")}
    </div>`).join("");
}

function closeDetail() {
  document.getElementById("detail-overlay").classList.remove("open");
  document.getElementById("detail-panel").classList.remove("open");
}

function changeDetailQty(delta) {
  detailQty = Math.max(1, detailQty + delta);
  setEl("detail-qty", String(detailQty));
}

function addFromDetail() {
  if (!currentProduct) return;
  addToCartWithQty(currentProduct.id, detailQty);
  closeDetail();
}

// ══════════════════════════════════════════════════════════════
// PANIER
// ══════════════════════════════════════════════════════════════
function addToCart(productId)            { addToCartWithQty(productId, 1); }
function addToCartWithQty(productId, qty) {
  const p = allProducts.find(x => x.id === productId);
  if (!p) return;
  const ex = cart.find(i => i.product.id === productId);
  if (ex) ex.qty += qty; else cart.push({ product:p, qty });
  saveCart(); updateCartUI();
  showToast(`${p.name} ajouté (×${qty}).`, "success");
}
function removeFromCart(id) { cart = cart.filter(i => i.product.id !== id); saveCart(); updateCartUI(); }
function changeCartQty(id, delta) {
  const item = cart.find(x => x.product.id === id); if(!item) return;
  item.qty = Math.max(1, item.qty + delta); saveCart(); updateCartUI();
}

function toggleCart() {
  const panel=document.getElementById("cart-panel"), ov=document.getElementById("cart-overlay");
  const open = panel.classList.toggle("open");
  ov.classList.toggle("open", open);
  if (open) renderCartItems();
}

function renderCartItems() {
  const c = document.getElementById("cart-items"); if(!c) return;
  if (!cart.length) { c.innerHTML=`<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Votre panier est vide</p></div>`; return; }
  c.innerHTML = cart.map(({product:p,qty}) => {
    const img = p.image_url
      ? `<img class="cart-item-img" src="${esc(p.image_url)}" alt="${esc(p.name)}">`
      : `<div class="cart-item-img-ph">📦</div>`;
    return `<div class="cart-item">${img}
      <div class="cart-item-info">
        <div class="cart-item-name">${esc(p.name)}</div>
        <div class="cart-item-price">${p.price!=null?formatMAD(p.price*qty):"Sur devis"}</div>
        <div class="cart-item-qty">
          <button class="cart-item-qty-btn" onclick="changeCartQty(${p.id},-1)">−</button>
          <span style="font-weight:700;font-size:13px">${qty}</span>
          <button class="cart-item-qty-btn" onclick="changeCartQty(${p.id},1)">+</button>
        </div>
      </div>
      <button class="cart-item-remove" onclick="removeFromCart(${p.id})"><i class="fas fa-xmark"></i></button>
    </div>`;
  }).join("");
}

function updateCartUI() {
  const n = cart.reduce((s,i)=>s+i.qty,0);
  const t = cart.reduce((s,i)=>s+(i.product.price||0)*i.qty,0);
  const ce=document.getElementById("cart-count"); if(ce){ce.textContent=n;ce.style.display=n>0?"flex":"none";}
  const sb=document.getElementById("cart-sidebar-badge"); if(sb){sb.textContent=n;sb.style.display=n>0?"flex":"none";}
  const te=document.getElementById("cart-total"); if(te) te.textContent=t.toLocaleString("fr-MA");
  if(document.getElementById("cart-panel")?.classList.contains("open")) renderCartItems();
}

function saveCart()    { try { sessionStorage.setItem("rabbit_cart", JSON.stringify(cart.map(i=>({id:i.product.id,qty:i.qty})))); } catch{} }
function restoreCart() { try { const r=sessionStorage.getItem("rabbit_cart"); if(r) sessionStorage._cartTemp=JSON.parse(r); } catch{} }
function applyRestoredCart() {
  const s=sessionStorage._cartTemp; if(!s||!Array.isArray(s)) return;
  s.forEach(({id,qty})=>{ const p=allProducts.find(x=>x.id===id); if(p) cart.push({product:p,qty}); });
  delete sessionStorage._cartTemp; updateCartUI();
}

// Checkout → POST /rfqs avec quantity_requested
async function checkout() {
  if (!cart.length) { showToast("Votre panier est vide.", "warn"); return; }
  const btn = document.querySelector(".cart-checkout");
  if (btn) { btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Envoi…'; }
  let ok=0, fail=0;
  for (const {product:p, qty} of cart) {
    try {
      await apiFetchJson("/rfqs", { method:"POST", body: JSON.stringify({
        product_id:         p.id,
        quantity_requested: qty,   // ← champ correct
        notes: `Demande portail acheteur — ${new Date().toLocaleDateString("fr-FR")}`,
      })});
      ok++;
    } catch { fail++; }
  }
  if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-file-invoice"></i> Demander un devis (RFQ)';}
  if(ok>0) { cart=[]; saveCart(); updateCartUI(); toggleCart(); showToast(`${ok} demande(s) de devis créée(s).`, "success"); }
  if(fail>0) showToast(`${fail} produit(s) non envoyé(s). Réessayez.`);
}

// ══════════════════════════════════════════════════════════════
// UTILITAIRES
// ══════════════════════════════════════════════════════════════
function localStockStatus(p) {
  // Catalogue client : utilise availability_status (AVAILABLE/LOW_AVAILABILITY/OUT_OF_STOCK)
  // Ne jamais afficher stock_qty exact au client
  const avail = p._availability || p.availability_status;
  if (avail) {
    const map = {
      AVAILABLE:        { key:"ok",  cls:"stock-ok",  label:"Disponible" },
      LOW_AVAILABILITY: { key:"low", cls:"stock-low", label:"Disponibilité limitée" },
      OUT_OF_STOCK:     { key:"out", cls:"stock-out", label:"Rupture de stock" },
    };
    return map[avail] || map.AVAILABLE;
  }
  // Fallback si availability_status absent
  return { key:"ok", cls:"stock-ok", label:"Disponible" };
}

// ── Fonctions utilitaires ────────────────────────────────────
// (Supprimées - maintenant dans utils.js : setEl, getEl, esc, formatDate, etc.)

// ══════════════════════════════════════════════════════════════
// COMMANDES CLIENT — le client voit uniquement ses propres commandes
// IMPORTANT : ne pas envoyer client_id — le backend utilise la session
// ══════════════════════════════════════════════════════════════
let clientOrders = [];

async function loadClientOrders() {
  try {
    const data = await apiFetchJson("/orders");
    clientOrders = unwrapItems(data);
    renderClientOrders();
  } catch (err) { /* silencieux si la vue orders n'est pas affichée */ }
}

function renderClientOrders() {
  const tbody = document.getElementById("client-orders-tbody");
  if (!tbody) return;
  const sub = document.getElementById("client-orders-sub");
  if (sub) sub.textContent = `${clientOrders.length} commande(s)`;

  if (!clientOrders.length) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:40px 0;color:#94a3b8"><div style="font-size:28px;margin-bottom:8px">📋</div><div style="font-size:13px">Aucune commande</div></td></tr>`;
    return;
  }
  tbody.innerHTML = clientOrders.map(o => {
    const s = ORDER_STATUS[o.status] || { label: esc(o.status), cls: "badge-draft" };
    return `
      <tr>
        <td style="font-size:12px;font-weight:700;color:#94a3b8">#${o.id}</td>
        <td style="font-weight:600;font-size:13px">${formatMAD(o.total_amount)}</td>
        <td><span class="badge ${s.cls}">${s.label}</span></td>
        <td style="font-size:12px;color:#94a3b8">${formatDate(o.created_at)}</td>
      </tr>`;
  }).join("");
}

// Client crée une commande : ne pas envoyer client_id (session)
async function createClientOrder(items, shippingAddress) {
  if (!items || !items.length) { showToast("Panier vide.", "warn"); return; }
  try {
    await apiFetchJson("/orders", {
      method: "POST",
      body: JSON.stringify({
        shipping_address: shippingAddress,
        items: items.map(i => ({
          component_id: i.product.id,
          quantity:     i.qty,
          unit_price:   i.product.price || 0,
        })),
        // NE PAS inclure client_id — le backend le déduit de la session
      }),
    });
    showToast("Commande créée avec succès.", "success");
    await loadClientOrders();
  } catch (err) { showToast(formatApiError(err)); }
}

// ══════════════════════════════════════════════════════════════
// NOTIFICATIONS CLIENT — uniquement ses propres notifications
// ══════════════════════════════════════════════════════════════
let clientNotifications = [];

async function loadClientNotifications() {
  try {
    const data = await apiFetchJson("/notifications");
    clientNotifications = unwrapItems(data);
    updateClientNotifBadge();
  } catch {}
}

async function loadAndRenderClientNotifications() {
  try {
    await loadClientNotifications();
    const list = document.getElementById("client-notif-list");
    if (!list) return;

    if (!clientNotifications.length) {
      list.innerHTML = '<p class="empty-centered">Aucune notification</p>';
      return;
    }

    list.innerHTML = clientNotifications.map(n => {
      const label   = NOTIFICATION_TYPE_LABELS[n.type] || esc(n.type) || "Notification";
      const isUnread = !n.read_at;
      return `
        <div class="notif-item ${isUnread ? 'unread' : ''}">
          <div class="notif-dot-indicator ${isUnread ? 'unread' : 'read'}"></div>
          <div class="notif-content">
            <div class="notif-title ${isUnread ? 'unread' : 'read'}">${label}</div>
            <div class="notif-body">${esc(n.message || n.body || "—")}</div>
            <div class="notif-time">${formatDate(n.created_at)}</div>
          </div>
          ${isUnread ? `<button class="btn-sm" style="font-size:11px;flex-shrink:0"
            onclick="markClientNotifRead(${n.id})"><i class="fas fa-check"></i></button>` : ""}
        </div>`;
    }).join("");
  } catch (err) {
    const list = document.getElementById("client-notif-list");
    if (list) list.innerHTML = '<p class="empty-centered">Erreur chargement notifications</p>';
    showToast(formatApiError(err), "error");
  }
}

async function markClientNotifRead(id) {
  try {
    await apiFetchJson(`/notifications/${id}/read`, { method: "PATCH" });
    await loadAndRenderClientNotifications();
  } catch {}
}

async function markAllClientNotifsRead() {
  try {
    await apiFetchJson("/notifications/read-all", { method: "PATCH" });
    await loadAndRenderClientNotifications();
    showToast("Toutes les notifications marquées comme lues.", "success");
  } catch (err) { showToast(formatApiError(err)); }
}

function updateClientNotifBadge() {
  const unread = clientNotifications.filter(n => !n.read_at).length;
  const dot = document.getElementById("notif-dot");
  if (dot) dot.style.display = unread > 0 ? "block" : "none";
}

// ══════════════════════════════════════════════════════════════
// PROMOTIONS CLIENT — /catalog/promotions/active
// ══════════════════════════════════════════════════════════════
let activePromotions = [];

async function loadActivePromotions() {
  try {
    const data = await apiFetchJson("/catalog/promotions/active");
    activePromotions = unwrapItems(data);
    renderPromotions();
  } catch {}
}

function renderPromotions() {
  const container = document.getElementById("promotions-container");
  if (!container || !activePromotions.length) return;

  container.innerHTML = activePromotions.map(p => {
    const discount = p.discount_type === "percentage"
      ? `-${p.discount_value}%`
      : `-${formatMAD(p.discount_value)}`;
    return `
      <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;border-radius:12px;padding:16px;margin-bottom:12px">
        <div style="display:flex;align-items:center;justify-content:space-between">
          <div style="font-weight:700;font-size:14px">${esc(p.title)}</div>
          <span style="background:#ea580c;color:#fff;font-weight:700;font-size:12px;padding:3px 10px;border-radius:20px">${discount}</span>
        </div>
        ${p.description ? `<div style="font-size:12px;color:#64748b;margin-top:4px">${esc(p.description)}</div>` : ""}
        <div style="font-size:11px;color:#94a3b8;margin-top:6px">
          ${p.ends_at ? `Valable jusqu'au ${formatDate(p.ends_at)}` : ""}
        </div>
      </div>`;
  }).join("");
}

// ══════════════════════════════════════════════════════════════
// AVIS CLIENTS — /catalog/products/{id}/reviews + /rating-summary
// ══════════════════════════════════════════════════════════════
async function loadProductReviews(productId) {
  try {
    const [reviewData, summaryData] = await Promise.all([
      apiFetchJson(`/catalog/products/${productId}/reviews`),
      apiFetchJson(`/catalog/products/${productId}/rating-summary`),
    ]);
    const reviews = unwrapItems(reviewData);
    const summary = unwrap(summaryData);
    renderProductReviews(reviews, summary);
  } catch {}
}

function renderProductReviews(reviews, summary) {
  const el = document.getElementById("product-reviews-section");
  if (!el) return;

  const avg = summary?.average_rating ? Number(summary.average_rating).toFixed(1) : "—";
  const count = summary?.review_count ?? reviews.length;
  const stars = (n) => "★".repeat(Math.round(n)) + "☆".repeat(5 - Math.round(n));

  el.innerHTML = `
    <div style="padding:20px 0;border-top:1px solid var(--border)">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:12px">Avis clients</div>
      ${summary ? `
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
          <span style="font-size:32px;font-weight:800;color:#f59e0b">${avg}</span>
          <div>
            <div style="color:#f59e0b;font-size:18px">${stars(summary.average_rating||0)}</div>
            <div style="font-size:12px;color:#94a3b8">${count} avis</div>
          </div>
        </div>` : ""}
      ${reviews.length === 0
        ? `<p style="font-size:13px;color:#94a3b8">Aucun avis pour ce produit.</p>`
        : reviews.map(r => `
          <div style="padding:12px 0;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
              <span style="color:#f59e0b">${stars(r.rating||0)}</span>
              ${r.title ? `<span style="font-weight:600;font-size:13px">${esc(r.title)}</span>` : ""}
            </div>
            ${r.comment ? `<div style="font-size:13px;color:#475569">${esc(r.comment)}</div>` : ""}
            <div style="font-size:11px;color:#94a3b8;margin-top:4px">${formatDate(r.created_at)}</div>
          </div>`).join("")}
    </div>`;
}

// Soumettre un avis client
// V1 : user_id requis dans le body (backend V1 caveat — sera session plus tard)
async function submitReview(productId, rating, title, comment) {
  const user = getCurrentUser();
  if (!user) { showToast("Vous devez être connecté.", "warn"); return; }
  try {
    await apiFetchJson(`/catalog/products/${productId}/reviews`, {
      method: "POST",
      body: JSON.stringify({
        user_id: user.id,  // V1 : requis, sera déduit de la session en V2
        rating, title, comment,
      }),
    });
    showToast("Avis soumis — en attente de modération.", "success");
    await loadProductReviews(productId);
  } catch (err) { showToast(formatApiError(err)); }
}


// ══════════════════════════════════════════════════════════════
// PANNEAUX LATERAUX CLIENT — fonctions extraites du HTML
// ══════════════════════════════════════════════════════════════

function showClientOrdersPanel() {
  const overlay = document.getElementById('orders-overlay');
  const panel = document.getElementById('orders-panel');
  if (overlay) overlay.classList.add('open');
  if (panel) panel.classList.add('open');
  loadClientOrders();
}

function closeClientOrdersPanel() {
  const overlay = document.getElementById('orders-overlay');
  const panel = document.getElementById('orders-panel');
  if (overlay) overlay.classList.remove('open');
  if (panel) panel.classList.remove('open');
}

function showClientNotifPanel() {
  const overlay = document.getElementById('notif-overlay');
  const panel = document.getElementById('notif-panel');
  if (overlay) overlay.classList.add('open');
  if (panel) panel.classList.add('open');
  loadAndRenderClientNotifications();
}

function closeClientNotifPanel() {
  const overlay = document.getElementById('notif-overlay');
  const panel = document.getElementById('notif-panel');
  if (overlay) overlay.classList.remove('open');
  if (panel) panel.classList.remove('open');
}
