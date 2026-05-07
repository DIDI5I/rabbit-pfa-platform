/* ============================================================
   owner.js — Rabbit B2B MRO Platform — Dashboard Administration
   Version optimisée — Utilise les modules partagés
   Dépend de config.js + auth.js + utils.js
   ============================================================ */

let allProducts = [], allRelations = [], allRFQs = [];
let allMovements = [], allSuppliers = [], allAlerts = [];
let allInventory = [], allLots = [];
let currentEditId = null;

// ── Init ──────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", async () => {
  const user = await checkAuth("owner");
  if (!user) return;

  const initials = getInitials(user.name || "AD", 2);

  setEl("topbar-avatar", initials);
  setEl("sidebar-avatar", initials);

  if (user.name) {
    setEl("sidebar-username", user.name);
  }

  await Promise.allSettled([
    loadProducts(),
    loadInventory(),
    loadAlerts(),
    loadRFQs(),
    loadSuppliers(),
    loadRelations(),
    loadNotifications(),
    loadOrders(),
  ]);

  updateKPIs();
  renderAnalyticsDashboard();
  renderStockIntelligencePlaceholder();
});
// ── Navigation ────────────────────────────────────────────────
function showView(name) {
  document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));
  document.getElementById(`view-${name}`)?.classList.add("active");

  const titles = {
    dashboard: "Tableau de bord",
    products: "Produits",
    relations: "Relations produit",
    rfqs: "Demandes de devis",
    alerts: "Alertes stock",
    inventory: "Inventaire",
    movements: "Mouvements",
    suppliers: "Fournisseurs",
    lots: "Lots d'achat",
    notifications: "Notifications",
    orders: "Commandes",
    intelligence: "Stock Intelligence",
    promotions: "Promotions",
    reviews: "Avis clients",
  };

  setEl("topbar-title", titles[name] || name);

  const viewHandlers = {
    dashboard: renderAnalyticsDashboard,
    products: renderProducts,
    relations: renderRelations,
    rfqs: renderRFQs,
    alerts: renderAlerts,
    inventory: renderInventory,
    movements: renderMovements,
    suppliers: renderSuppliers,
    lots: renderLots,
    notifications: renderNotifications,
    orders: renderOrders,
    intelligence: () => {
      renderStockIntelligencePlaceholder();
      loadStockIntelligence();
    },
    promotions: () => loadPromotions(),
    reviews: () => loadReviews("pending"),
  };

  viewHandlers[name]?.();
}

// ── KPIs ──────────────────────────────────────────────────────
function updateKPIs() {
  const active  = allProducts.filter(p => p.is_active !== false).length;
  const pending = allRFQs.filter(r => r.status === "open" || r.status === "quoted").length;
  setEl("kpi-products",  active);
  setEl("kpi-alerts",    allAlerts.length);
  setEl("kpi-rfqs",      pending);
  setEl("kpi-suppliers", allSuppliers.length);
  setBadge("rfq-badge",   pending);
  setBadge("alert-badge", allAlerts.length);
  const dot = document.getElementById("notif-dot");
  if (dot) dot.style.display = (allAlerts.length > 0 || pending > 0) ? "block" : "none";
}


function renderAnalyticsDashboard() {
  const el = document.getElementById("view-dashboard");
  if (!el) return;

  if (window.OwnerOverviewDashboard && typeof window.OwnerOverviewDashboard.render === "function") {
    window.OwnerOverviewDashboard.render(el);
    return;
  }

  el.innerHTML = `
    <div class="page-header">
      <h1>Tableau de bord</h1>
      <p>Vue globale du stock, analyse ABC, performance produit et prévision.</p>
    </div>

    <div class="empty-state">
      <i class="fas fa-chart-line"></i>
      <p>Module dashboard avancé non encore chargé.</p>
    </div>
  `;
}

function renderDashboardWidgets() {
  // Alertes récentes
  const al = document.getElementById("dash-alerts-list");
  if (al) al.innerHTML = allAlerts.length === 0
    ? `<p style="font-size:13px;color:#94a3b8;text-align:center;padding:16px 0">Aucune alerte 🎉</p>`
    : allAlerts.slice(0, 4).map(a => {
        const qty = a.current_stock ?? a.stock_qty ?? 0;
        const isOut = qty <= 0;
        const action = a.recommended_action === "CREATE_RFQ"
          ? `<button class="btn-sm" style="font-size:11px" onclick="showView('rfqs')"><i class="fas fa-file-invoice"></i> RFQ</button>` : "";
        return `<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
          <div>
            <div style="font-weight:600;font-size:13px">${esc(a.name)}</div>
            <div style="font-size:11px;color:#94a3b8">Stock : ${qty} · Seuil : ${a.low_stock_threshold ?? "—"} · Délai réappro : ${a.lead_time_days ? a.lead_time_days+"j" : "—"}</div>
          </div>
          <div style="display:flex;align-items:center;gap:8px">
            ${action}
            <span class="badge ${isOut ? 'badge-out' : 'badge-low'}">${isOut ? 'Rupture' : 'Stock bas'}</span>
          </div>
        </div>`;
      }).join("");

  // RFQs ouvertes
  const rl = document.getElementById("dash-rfqs-list");
  if (rl) {
    const open = allRFQs.filter(r => r.status === "open" || r.status === "quoted").slice(0, 4);
    rl.innerHTML = open.length === 0
      ? `<p style="font-size:13px;color:#94a3b8;text-align:center;padding:16px 0">Aucune RFQ en cours</p>`
      : open.map(r => `<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border)">
          <div>
            <div style="font-weight:600;font-size:13px">${esc(r.component_name || r.product_name || "RFQ #"+r.id)}</div>
            <div style="font-size:11px;color:#94a3b8">${esc(r.supplier_name||"—")} · Qté : ${r.quantity_requested??"—"}</div>
          </div>
          ${rfqBadge(r.status)}
        </div>`).join("");
  }

  renderMovementsTable("dash-movements-tbody", allMovements.slice(0, 10));
}

// ══════════════════════════════════════════════════════════════
// PRODUITS
// ══════════════════════════════════════════════════════════════
async function loadProducts() {
  try {
    const showInactive = document.getElementById("show-inactive")?.checked;
    const data = await apiFetchJson(`/products${showInactive ? "?active_only=false" : ""}`);
    allProducts = unwrap(data);
    if (!Array.isArray(allProducts)) allProducts = [];
    renderProducts();
  } catch (err) { showToast("Produits : " + (err?.error || err?.message || "Erreur")); }
}

function renderProducts() {
  const tbody = document.getElementById("products-tbody");
  if (!tbody) return;
  const sub = document.getElementById("products-count-sub");
  if (sub) sub.textContent = `${allProducts.length} produit(s)`;
  if (!allProducts.length) { tbody.innerHTML = emptyRow(8, "📦", "Aucun produit"); return; }

  tbody.innerHTML = allProducts.map(p => {
    // current_stock vient de /inventory, stock_qty est legacy
    const qty = p.current_stock ?? p.stock_qty ?? null;
    const thr = p.low_stock_threshold ?? 0;
    const badge = p.stock_status ? stockBadgeFromStatus(p.stock_status) : stockBadgeFromProduct(p);
    return `<tr>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          ${p.image_url
            ? `<img src="${esc(p.image_url)}" style="width:36px;height:36px;border-radius:8px;object-fit:cover">`
            : `<div style="width:36px;height:36px;border-radius:8px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:16px">📦</div>`}
          <div>
            <div style="font-weight:600;font-size:13px">${esc(p.name)}</div>
            <div style="font-size:11px;color:#94a3b8;font-family:monospace">${esc(p.sku||"—")}</div>
          </div>
        </div>
      </td>
      <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:12px;background:#ede9fe;color:#6d28d9">${categoryLabel(p.category)}</span></td>
      <td>${formatMAD(p.price)}</td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-weight:700">${qty??"—"}</span>
          ${p.unit_of_measure ? `<span style="font-size:11px;color:#94a3b8">${esc(p.unit_of_measure)}</span>` : ""}
          ${stockGauge(qty, thr)}
        </div>
      </td>
      <td>${badge}</td>
      <td><span style="font-size:12px;font-weight:600;color:${p.is_active!==false?'var(--green)':'#94a3b8'}">${p.is_active!==false?"Actif":"Inactif"}</span></td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn-sm" onclick="editProduct(${p.id})" title="Modifier"><i class="fas fa-pen"></i></button>
          <button class="btn-sm" onclick="showProductRelations(${p.id},'${esc(p.name)}')" title="Relations"><i class="fas fa-diagram-project"></i></button>
          <button class="btn-sm" onclick="openStockPanel(${p.id})" title="Stock"><i class="fas fa-boxes-stacked"></i></button>
        </div>
      </td>
    </tr>`;
  }).join("");
}

function openProductModal() {
  currentEditId = null; setEl("modal-title","Ajouter un produit");
  clearProductForm(); document.getElementById("product-modal").style.display = "flex";
}

function editProduct(id) {
  const p = allProducts.find(x => x.id === id);
  if (!p) return;
  currentEditId = id; setEl("modal-title","Modifier le produit");
  setVal("p-name", p.name||""); setVal("p-category", p.category||"");
  setVal("p-stock", p.stock_qty??""); setVal("p-threshold", p.low_stock_threshold??"");
  setVal("p-price", p.price??""); setVal("p-sku", p.sku||"");
  setVal("p-uom",   p.unit_of_measure||""); setVal("p-desc", p.description||"");
  const a = document.getElementById("p-active"); if (a) a.checked = p.is_active !== false;
  const prev = document.getElementById("img-preview"), ph = document.getElementById("upload-placeholder");
  if (p.image_url) { if(prev){prev.src=p.image_url;prev.style.display="block"} if(ph) ph.style.display="none"; }
  else { if(prev) prev.style.display="none"; if(ph) ph.style.display="flex"; }
  document.getElementById("product-modal").style.display = "flex";
}

function closeProductModal() {
  document.getElementById("product-modal").style.display = "none";
  clearProductForm(); currentEditId = null;
}

function clearProductForm() {
  ["p-name","p-category","p-stock","p-threshold","p-price","p-sku","p-uom","p-desc"].forEach(id => setVal(id,""));
  const a = document.getElementById("p-active"); if (a) a.checked = true;
  const prev = document.getElementById("img-preview"); if(prev){prev.src="";prev.style.display="none";}
  const ph = document.getElementById("upload-placeholder"); if(ph) ph.style.display="flex";
}

function previewImage(input) {
  if (!input.files?.[0]) return;
  const r = new FileReader();
  r.onload = e => { const prev=document.getElementById("img-preview"); prev.src=e.target.result; prev.style.display="block"; document.getElementById("upload-placeholder").style.display="none"; };
  r.readAsDataURL(input.files[0]);
}

async function saveProduct() {
  const name = document.getElementById("p-name")?.value.trim();
  const category = document.getElementById("p-category")?.value.trim();
  if (!name || !category) { showToast("Nom et catégorie requis.", "warn"); return; }
  const imageFile = document.getElementById("product-image-file")?.files?.[0];
  const endpoint  = currentEditId ? `/products/${currentEditId}` : "/products";
  try {
    if (imageFile) {
      const fd = new FormData();
      fd.append("name", name); fd.append("category", category);
      fd.append("stock_qty",           document.getElementById("p-stock")?.value||0);
      fd.append("low_stock_threshold", document.getElementById("p-threshold")?.value||3);
      fd.append("price",               document.getElementById("p-price")?.value||0);
      fd.append("sku",                 document.getElementById("p-sku")?.value||"");
      fd.append("unit_of_measure",     document.getElementById("p-uom")?.value||"");
      fd.append("description",         document.getElementById("p-desc")?.value||"");
      fd.append("is_active",           document.getElementById("p-active")?.checked ? 1 : 0);
      fd.append("image", imageFile);
      await apiFetchFormData(endpoint, fd, "POST");
    } else {
      await apiFetchJson(endpoint, { method: currentEditId ? "PATCH" : "POST", body: JSON.stringify({
        name, category,
        stock_qty:           parseInt(document.getElementById("p-stock")?.value)||0,
        low_stock_threshold: parseInt(document.getElementById("p-threshold")?.value)||3,
        price:               parseFloat(document.getElementById("p-price")?.value)||0,
        sku:                 document.getElementById("p-sku")?.value.trim(),
        unit_of_measure:     document.getElementById("p-uom")?.value.trim(),
        description:         document.getElementById("p-desc")?.value.trim(),
        is_active:           document.getElementById("p-active")?.checked,
      })});
    }
    showToast(currentEditId ? "Produit mis à jour." : "Produit créé.", "success");
    closeProductModal(); await loadProducts(); updateKPIs();
  } catch (err) { showToast("Erreur : " + (err?.error||err?.message)); }
}

// ══════════════════════════════════════════════════════════════
// INVENTAIRE — /inventory (source principale du stock réel)
// Retourne : current_stock, stock_status, estimated_stock_value, preferred_supplier
// ══════════════════════════════════════════════════════════════
async function loadInventory() {
  try {
    const data = await apiFetchJson("/inventory");
    allInventory = unwrap(data);
    if (!Array.isArray(allInventory)) allInventory = [];
    renderInventory();
  } catch { allInventory = []; }
}

function renderInventory() {
  const tbody = document.getElementById("inventory-tbody");
  if (!tbody) return;
  const sub = document.getElementById("inventory-count-sub");
  if (sub) sub.textContent = `${allInventory.length} référence(s)`;
  if (!allInventory.length) { tbody.innerHTML = emptyRow(7, "📦", "Inventaire vide"); return; }

  tbody.innerHTML = allInventory.map(p => `
    <tr>
      <td>
        <div style="font-weight:600;font-size:13px">${esc(p.name)}</div>
        <div style="font-size:11px;color:#94a3b8;font-family:monospace">${esc(p.sku||"—")}</div>
      </td>
      <td><span style="font-size:11px;font-weight:700;padding:2px 7px;border-radius:12px;background:#ede9fe;color:#6d28d9">${categoryLabel(p.category)}</span></td>
      <td style="font-weight:700;font-size:15px">${p.current_stock ?? "—"}</td>
      <td>${p.low_stock_threshold ?? "—"}</td>
      <td>${stockBadgeFromStatus(p.stock_status || "OK")}</td>
      <td style="font-size:12px;color:#64748b">${esc(p.preferred_supplier||"—")}</td>
      <td style="font-weight:600">${p.estimated_stock_value != null ? formatMAD(p.estimated_stock_value) : "—"}</td>
    </tr>`).join("");
}

// ══════════════════════════════════════════════════════════════
// ALERTES STOCK — /inventory/alerts (pas /products/alerts)
// ══════════════════════════════════════════════════════════════
async function loadAlerts() {
  try {
    const data = await apiFetchJson("/inventory/alerts");
    allAlerts = unwrap(data);
    if (!Array.isArray(allAlerts)) allAlerts = [];
  } catch {
    // Fallback local depuis inventaire déjà chargé
    allAlerts = allInventory.filter(p => p.stock_status === "LOW_STOCK" || p.stock_status === "OUT_OF_STOCK");
  }
  renderAlerts(); updateKPIs();
}

function renderAlerts() {
  const grid = document.getElementById("alerts-grid");
  if (!grid) return;
  if (!allAlerts.length) {
    grid.innerHTML = `<div style="grid-column:1/-1"><div class="empty-state"><i class="fas fa-check-circle" style="color:var(--green)"></i><p>Tous les stocks sont au-dessus de leur seuil 🎉</p></div></div>`;
    return;
  }
  grid.innerHTML = allAlerts.map(p => {
    const qty = p.current_stock ?? p.stock_qty ?? 0;
    const isOut = p.stock_status === "OUT_OF_STOCK" || qty <= 0;
    const action = p.recommended_action === "CREATE_RFQ"
      ? `<button class="btn-sm" style="font-size:11px;margin-top:6px" onclick="openRFQModalFor(${p.id},'${esc(p.name)}')"><i class="fas fa-file-invoice"></i> Créer RFQ</button>` : "";
    return `<div class="alert-card">
      <div class="alert-icon ${isOut?'out':'low'}"><i class="fas ${isOut?'fa-ban':'fa-triangle-exclamation'}"></i></div>
      <div class="alert-body">
        <div class="alert-product">${esc(p.name)}</div>
        <div class="alert-detail">
          Stock : <strong>${qty}</strong>${p.unit_of_measure ? ` ${esc(p.unit_of_measure)}` : ""}
          · Seuil : ${p.low_stock_threshold??"—"}
          ${p.lead_time_days ? ` · Délai réappro : ${p.lead_time_days}j` : ""}
        </div>
        ${action}
      </div>
      <span class="badge ${isOut?'badge-out':'badge-low'}">${isOut?'Rupture':'Stock bas'}</span>
    </div>`;
  }).join("");
}

// ══════════════════════════════════════════════════════════════
// STOCK DÉTAIL — /stock/{componentId} + /stock/{componentId}/movements
// ══════════════════════════════════════════════════════════════
async function openStockPanel(productId) {
  try {
    const [stockData, movData] = await Promise.all([
      apiFetchJson(`/stock/${productId}`),
      apiFetchJson(`/stock/${productId}/movements`),
    ]);
    const s   = unwrap(stockData);
    const mvs = unwrap(movData);
    if (!Array.isArray(mvs)) return;
    allMovements = mvs;
    // Pour l'instant : afficher dans la vue mouvements
    showView("movements");
    const sub = document.getElementById("movements-count-sub");
    if (sub) sub.textContent = `${mvs.length} mouvement(s) — ${s?.name||"Produit #"+productId} · Stock actuel : ${s?.current_stock??"—"}`;
  } catch (err) { showToast("Stock : " + (err?.error||err?.message)); }
}

// ══════════════════════════════════════════════════════════════
// MOUVEMENTS DE STOCK GLOBAUX (POST /stock/movements pour création manuelle)
// ══════════════════════════════════════════════════════════════
function renderMovements() {
  renderMovementsTable("movements-tbody", allMovements);
  const sub = document.getElementById("movements-count-sub");
  if (sub) sub.textContent = `${allMovements.length} mouvement(s)`;
}

function renderMovementsTable(tbodyId, list) {
  const tbody = document.getElementById(tbodyId);
  if (!tbody) return;
  if (!list?.length) { tbody.innerHTML = emptyRow(6, "📭", "Aucun mouvement"); return; }
  tbody.innerHTML = list.map(m => {
    const isIn = m.type === "in";
    // Quantité toujours positive dans stock_movements
    const qty = Math.abs(m.quantity ?? 0);
    return `<tr>
      <td style="font-weight:600;font-size:13px">${esc(m.component_name||"#"+m.component_id)}</td>
      <td class="${isIn?'mov-in':'mov-out'}"><i class="fas fa-arrow-${isIn?'up':'down'}"></i> ${isIn?'Entrée':'Sortie'}</td>
      <td style="font-weight:700">${qty}</td>
      <td style="font-size:12px">${MOVEMENT_REASONS[m.reason]||esc(m.reason)||"—"}</td>
      <td style="font-size:12px;color:#94a3b8">${m.reference_id?`#${m.reference_type||"REF"}-${m.reference_id}`:"—"}</td>
      <td style="font-size:12px;color:#94a3b8">${formatDate(m.created_at)}</td>
    </tr>`;
  }).join("");
}

// ══════════════════════════════════════════════════════════════
// RELATIONS PRODUIT
// RAPPEL : PATCH/DELETE utilisent r.id (dependencies.id), PAS child_id
// ══════════════════════════════════════════════════════════════


async function loadRelations() {
  try {
    // Route /product-relations n'existe pas en backend.
    // Les relations se chargent par produit via /products/{id}/dependencies.
    // Cette fonction initialise le tableau vide ; showProductRelations() charge par produit.
    allRelations = [];
    renderRelations();
  } catch (err) {
    showToast("Erreur chargement relations: " + formatApiError(err), "error");
  }
}

async function showProductRelations(productId, productName) {
  try {
    const data = await apiFetchJson(`/products/${productId}/dependencies`);
    const rels = unwrap(data);
    showView("relations");
    setVal("rel-search", productName);
    renderRelations(Array.isArray(rels) ? rels : []);
  } catch (err) { showToast("Relations : " + (err?.error||err?.message)); }
}

function renderRelations(list) {
  const tbody = document.getElementById("relations-tbody");
  if (!tbody) return;
  const data = list || allRelations;
  const sub = document.getElementById("relations-count-sub");
  if (sub) sub.textContent = `${data.length} relation(s)`;
  if (!data.length) { tbody.innerHTML = emptyRow(8, "🔗", "Aucune relation produit"); return; }

  tbody.innerHTML = data.map(r => {
    const phantom  = r.is_phantom ? phantomBadge() : "";
    const childCat = r.child_category ? categoryLabel(r.child_category) : "—";
    return `<tr>
      <td style="font-weight:600;font-size:13px">${esc(r.parent_name||"#"+r.parent_id)}</td>
      <td><span class="rel-pill">${RELATION_TYPES[r.relation_type]||esc(r.relation_type)}</span></td>
      <td><span style="font-weight:600;font-size:13px">${esc(r.child_name||"#"+r.child_id)}</span>${phantom}</td>
      <td style="font-size:11px;color:#94a3b8;font-family:monospace">${esc(r.child_sku||"—")}</td>
      <td><span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;background:#ede9fe;color:#6d28d9">${childCat}</span></td>
      <td>${r.qty_required!=null ? `${r.qty_required} ${esc(r.uom||"")}` : "—"}</td>
      <td style="font-size:12px;color:#94a3b8;max-width:160px">${r.notes?esc(r.notes):"—"}</td>
      <td>
        <button class="btn-sm" style="color:var(--red)"
                onclick="deleteRelation(${r.parent_id}, ${r.id})"
                title="Supprimer (id relation : ${r.id})">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    </tr>`;
  }).join("");
}

// IMPORTANT : dependencyId = r.id (PAS child_id)
async function deleteRelation(parentId, dependencyId) {
  if (!confirm("Supprimer cette relation produit ?")) return;
  try {
    await apiFetchJson(`/products/${parentId}/dependencies/${dependencyId}`, { method:"DELETE" });
    showToast("Relation supprimée.", "success");
    await loadRelations();
  } catch (err) { showToast("Erreur : " + (err?.error||err?.message)); }
}

function filterRelations(searchVal) {
  const search = (searchVal ?? document.getElementById("rel-search")?.value ?? "").toLowerCase();
  const type   = document.getElementById("rel-type-filter")?.value || "";
  renderRelations(allRelations.filter(r =>
    (!search || (r.parent_name||"").toLowerCase().includes(search) || (r.child_name||"").toLowerCase().includes(search)) &&
    (!type || r.relation_type === type)
  ));
}

// ══════════════════════════════════════════════════════════════
// RFQs
// Champs corrects : quantity_requested, lead_time_days (pas quantity, quoted_delay)
// ══════════════════════════════════════════════════════════════
async function loadRFQs() {
  try {
    const status = document.getElementById("rfq-status-filter")?.value || "";
    const data   = await apiFetchJson(`/rfqs${status ? "?status="+status : ""}`);
    allRFQs = unwrap(data);
    if (!Array.isArray(allRFQs)) allRFQs = [];
    renderRFQs(); updateKPIs();
  } catch (err) { showToast("RFQs : " + (err?.error||err?.message)); }
}

function renderRFQs() {
  const tbody = document.getElementById("rfqs-tbody");
  if (!tbody) return;
  const sub = document.getElementById("rfqs-count-sub");
  if (sub) {
    const p = allRFQs.filter(r => r.status==="open"||r.status==="quoted").length;
    sub.textContent = `${allRFQs.length} RFQ(s) · ${p} en attente`;
  }
  if (!allRFQs.length) { tbody.innerHTML = emptyRow(7, "📋", "Aucune RFQ"); return; }

  tbody.innerHTML = allRFQs.map(r => `
    <tr>
      <td style="font-size:12px;font-weight:700;color:#94a3b8">#${r.id}</td>
      <td style="font-weight:600;font-size:13px">${esc(r.component_name||r.product_name||"—")}</td>
      <td>${esc(r.supplier_name||"—")}</td>
      <td>${r.quantity_requested??"—"}</td>
      <td>${r.quoted_price != null ? formatMAD(r.quoted_price) : "—"}</td>
      <td>${r.lead_time_days != null ? `${r.lead_time_days}j` : "—"}</td>
      <td>${rfqBadge(r.status)}</td>
      <td><div style="display:flex;gap:6px;flex-wrap:wrap">${buildRFQActions(r)}</div></td>
    </tr>`).join("");
}

function buildRFQActions(r) {
  const a = [];
  if (r.status==="draft")
    a.push(`<button class="btn-sm" onclick="rfqAction(${r.id},'open')"><i class="fas fa-lock-open"></i> Ouvrir</button>`);
  if (r.status==="open")
    a.push(`<button class="btn-sm" onclick="rfqAction(${r.id},'expire')"><i class="fas fa-clock"></i> Expirer</button>`);
  if (r.status==="quoted") {
    a.push(`<button class="btn-sm" style="color:var(--green)" onclick="rfqAction(${r.id},'accept')"><i class="fas fa-check"></i> Accepter</button>`);
    a.push(`<button class="btn-sm" style="color:var(--red)"   onclick="rfqAction(${r.id},'reject')"><i class="fas fa-xmark"></i> Rejeter</button>`);
  }
  // Accepter crée un purchase lot draft (pas un mouvement de stock)
  return a.join("") || `<span style="font-size:12px;color:#94a3b8">—</span>`;
}

async function rfqAction(id, action) {
  try {
    await apiFetchJson(`/rfqs/${action}`, { method:"POST", body: JSON.stringify({
      rfq_id: id,
      decision_note: action === "accept" ? "Accepté" : action === "reject" ? "Rejeté" : undefined,
    }) });
    const labels = { open:"ouverte", accept:"acceptée (lot d'achat créé)", reject:"rejetée", expire:"expirée" };
    showToast(`RFQ ${labels[action]||action}.`, "success");
    if (action === "accept") showToast("Un lot d'achat brouillon a été créé. Finalisez-le pour réceptionner le stock.", "warn");
    await loadRFQs();
    if (action === "accept") await loadLots(); // rafraîchir les lots
  } catch (err) { showToast("Erreur : " + (err?.error||err?.message)); }
}

function openRFQModal() {
  const ps = document.getElementById("rfq-product-select");
  const ss = document.getElementById("rfq-supplier-select");
  if (ps) ps.innerHTML = allProducts.map(p => `<option value="${p.id}">${esc(p.name)} (${esc(p.sku||"—")})</option>`).join("");
  if (ss) ss.innerHTML = allSuppliers.length
    ? allSuppliers.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join("")
    : `<option value="">Aucun fournisseur</option>`;
  document.getElementById("rfq-modal").style.display = "flex";
}

function openRFQModalFor(productId, productName) {
  openRFQModal();
  const ps = document.getElementById("rfq-product-select");
  if (ps) ps.value = productId;
}

function closeRFQModal() { document.getElementById("rfq-modal").style.display = "none"; }

async function createRFQ() {
  const productId  = document.getElementById("rfq-product-select")?.value;
  const supplierId = document.getElementById("rfq-supplier-select")?.value;
  if (!productId || !supplierId) { showToast("Produit et fournisseur requis.", "warn"); return; }
  try {
    await apiFetchJson("/rfqs", { method:"POST", body: JSON.stringify({
      product_id:          parseInt(productId),
      supplier_id:         parseInt(supplierId),
      quantity_requested:  parseInt(document.getElementById("rfq-qty")?.value)||1,
      deadline:            document.getElementById("rfq-deadline")?.value.trim(),
      notes:               document.getElementById("rfq-notes")?.value.trim(),
    })});
    showToast("RFQ créée.", "success");
    closeRFQModal(); await loadRFQs();
  } catch (err) { showToast("Erreur : " + (err?.error||err?.message)); }
}

// ══════════════════════════════════════════════════════════════
// LOTS D'ACHAT — /purchase-lots
// Flux : draft → finalize → PURCHASE_RECEIVED stock IN
// ══════════════════════════════════════════════════════════════
async function loadLots() {
  try {
    const data = await apiFetchJson("/purchase-lots");
    allLots = unwrap(data);
    if (!Array.isArray(allLots)) allLots = [];
    renderLots();
  } catch { allLots = []; renderLots(); }
}

function renderLots() {
  const tbody = document.getElementById("lots-tbody");
  if (!tbody) return;
  const sub = document.getElementById("lots-count-sub");
  if (sub) sub.textContent = `${allLots.length} lot(s)`;
  if (!allLots.length) { tbody.innerHTML = emptyRow(7, "📦", "Aucun lot d'achat"); return; }

  tbody.innerHTML = allLots.map(l => {
    const statusLabel = PURCHASE_LOT_STATUS[l.status] || l.status || "—";
    const canFinalize = l.status === "draft";
    return `<tr>
      <td style="font-size:12px;font-weight:700;color:#94a3b8">#${l.id}</td>
      <td>
        <div style="font-weight:600;font-size:13px">${esc(l.component_name||"—")}</div>
        <div style="font-size:11px;color:#94a3b8">${l.reference_type==="rfq"?`via RFQ #${l.reference_id}`:""}</div>
      </td>
      <td>${esc(l.supplier_name||"—")}</td>
      <td style="font-weight:700">${l.quantity_received??"—"}</td>
      <td style="font-weight:600">${formatMAD(l.unit_purchase_cost)}</td>
      <td style="font-weight:700">${formatMAD(l.total_purchase_cost)}</td>
      <td>
        <span class="badge ${l.status==='finalized'?'badge-accepted':l.status==='cancelled'?'badge-rejected':'badge-draft'}">
          ${statusLabel}
        </span>
      </td>
      <td>
        ${canFinalize
          ? `<button class="btn-primary" style="font-size:11px;padding:6px 12px" onclick="finalizeLot(${l.id})">
               <i class="fas fa-check"></i> Finaliser
             </button>`
          : `<span style="font-size:12px;color:#94a3b8">—</span>`}
      </td>
    </tr>`;
  }).join("");
}

async function finalizeLot(lotId) {
  if (!confirm("Finaliser ce lot d'achat ? Un mouvement de stock PURCHASE_RECEIVED sera créé.")) return;
  try {
    // Corps optionnel : coûts supplémentaires
    await apiFetchJson(`/purchase-lots/${lotId}/finalize`, {
      method: "PATCH",
      body: JSON.stringify({ transport_cost:0, customs_cost:0, handling_cost:0, packaging_cost:0, order_preparation_cost:0, other_cost:0 }),
    });
    showToast("Lot finalisé — stock mis à jour.", "success");
    await Promise.all([loadLots(), loadInventory(), loadAlerts()]);
    updateKPIs();
  } catch (err) { showToast("Erreur : " + (err?.error||err?.message)); }
}

// ══════════════════════════════════════════════════════════════
// FOURNISSEURS
// ══════════════════════════════════════════════════════════════
async function loadSuppliers() {
  try {
    const data = await apiFetchJson("/suppliers");
    allSuppliers = unwrap(data);
    if (!Array.isArray(allSuppliers)) allSuppliers = [];
    renderSuppliers(); updateKPIs();
  } catch { allSuppliers = []; }
}

function renderSuppliers() {
  const grid = document.getElementById("suppliers-grid");
  if (!grid) return;
  if (!allSuppliers.length) {
    grid.innerHTML = `<div class="empty-state"><i class="fas fa-truck" style="opacity:.3"></i><p>Aucun fournisseur</p></div>`;
    return;
  }
  const colors = ["var(--blue)","var(--green)","var(--orange)","var(--red)","#8b5cf6"];
  grid.innerHTML = allSuppliers.map((s,i) => {
    const initials = (s.name||"FN").split(" ").map(w=>w[0]).join("").slice(0,2).toUpperCase();
    return `<div class="supplier-card">
      <div class="supplier-avatar" style="background:${colors[i%colors.length]}">${initials}</div>
      <div class="supplier-name">${esc(s.name)}</div>
      <div class="supplier-meta">${esc(s.email||s.contact||"—")}</div>
      ${s.city?`<div class="supplier-meta"><i class="fas fa-location-dot" style="margin-right:4px"></i>${esc(s.city)}</div>`:""}
      <div class="supplier-stats">
        <div><div class="supplier-stat-val">${s.rfq_count??"—"}</div><div class="supplier-stat-lbl">RFQs</div></div>
        <div><div class="supplier-stat-val">${s.accepted_count??"—"}</div><div class="supplier-stat-lbl">Acceptées</div></div>
      </div>
    </div>`;
  }).join("");
}

// ══════════════════════════════════════════════════════════════
// UTILITAIRES
// ══════════════════════════════════════════════════════════════
function stockGauge(qty, thr) {
  if (qty == null) return "";
  const max = Math.max(qty*1.5, thr*3, 10);
  const pct = Math.min(100, Math.round((qty/max)*100));
  const col = qty<=0?"#ef4444":qty<=thr?"#f59e0b":"#22c55e";
  return `<div style="width:70px;height:5px;background:#e2e8f0;border-radius:99px;overflow:hidden">
    <div style="width:${pct}%;height:100%;background:${col};border-radius:99px"></div></div>`;
}

function emptyRow(cols, icon, text) {
  return `<tr><td colspan="${cols}" style="text-align:center;padding:40px 0;color:#94a3b8">
    <div style="font-size:28px;margin-bottom:8px">${icon}</div><div style="font-size:13px">${text}</div></td></tr>`;
}

// ── Fonctions utilitaires ────────────────────────────────────
// (Supprimées - maintenant dans utils.js : setEl, getEl, esc, formatDate, etc.)

// ══════════════════════════════════════════════════════════════
// NOTIFICATIONS — owner voit toutes les notifications
// ══════════════════════════════════════════════════════════════
let allNotifications = [];

async function loadNotifications() {
  try {
    const data = await apiFetchJson("/notifications");
    allNotifications = unwrapItems(data);
    renderNotifications();
    await loadUnreadCount();
  } catch (err) { showToast("Notifications : " + formatApiError(err)); }
}

async function loadUnreadCount() {
  try {
    const data = await apiFetchJson("/notifications/unread-count");
    const count = unwrap(data)?.count ?? unwrap(data) ?? 0;
    setBadge("notif-badge", count);
    const dot = document.getElementById("notif-dot");
    if (dot) dot.style.display = count > 0 ? "block" : "none";
  } catch {}
}

function renderNotifications() {
  const list = document.getElementById("notifications-list");
  if (!list) return;
  const sub = document.getElementById("notifications-count-sub");
  if (sub) sub.textContent = `${allNotifications.length} notification(s)`;

  if (!allNotifications.length) {
    list.innerHTML = `<div style="text-align:center;padding:40px 0;color:#94a3b8"><div style="font-size:28px;margin-bottom:8px">🔔</div><div style="font-size:13px">Aucune notification</div></div>`;
    return;
  }
  list.innerHTML = allNotifications.map(n => {
    const label = NOTIFICATION_TYPE_LABELS[n.type] || esc(n.type) || "Notification";
    const isUnread = !n.read_at;
    return `
      <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 0;border-bottom:1px solid var(--border);${isUnread ? 'background:#f8faff;' : ''}">
        <div style="width:8px;height:8px;border-radius:50%;background:${isUnread ? 'var(--blue)' : '#e2e8f0'};flex-shrink:0;margin-top:6px"></div>
        <div style="flex:1">
          <div style="font-weight:${isUnread ? '700' : '500'};font-size:13px">${label}</div>
          <div style="font-size:12px;color:#64748b;margin-top:2px">${esc(n.message || n.body || "—")}</div>
          <div style="font-size:11px;color:#94a3b8;margin-top:4px">${formatDate(n.created_at)}</div>
        </div>
        ${isUnread ? `<button class="btn-sm" style="font-size:11px" onclick="markNotifRead(${n.id})"><i class="fas fa-check"></i></button>` : ""}
      </div>`;
  }).join("");
}

async function markNotifRead(id) {
  try {
    await apiFetchJson(`/notifications/${id}/read`, { method: "PATCH" });
    await loadNotifications();
  } catch (err) { showToast(formatApiError(err)); }
}

async function markAllNotifsRead() {
  try {
    await apiFetchJson("/notifications/read-all", { method: "PATCH" });
    await loadNotifications();
    showToast("Toutes les notifications marquées comme lues.", "success");
  } catch (err) { showToast(formatApiError(err)); }
}

// ══════════════════════════════════════════════════════════════
// COMMANDES — owner voit toutes les commandes, peut changer le statut
// ══════════════════════════════════════════════════════════════
let allOrders = [];

async function loadOrders() {
  try {
    const data = await apiFetchJson("/orders");
    allOrders = unwrapItems(data);
    renderOrders();
  } catch (err) { showToast("Commandes : " + formatApiError(err)); }
}

function renderOrders() {
  const tbody = document.getElementById("orders-tbody");
  if (!tbody) return;
  const sub = document.getElementById("orders-count-sub");
  if (sub) sub.textContent = `${allOrders.length} commande(s)`;

  if (!allOrders.length) {
    tbody.innerHTML = emptyRow(6, "📋", "Aucune commande");
    return;
  }
  tbody.innerHTML = allOrders.map(o => {
    const s = ORDER_STATUS[o.status] || { label: esc(o.status), cls: "badge-draft" };
    return `
      <tr>
        <td style="font-size:12px;font-weight:700;color:#94a3b8">#${o.id}</td>
        <td style="font-weight:600;font-size:13px">${esc(o.client_name || o.client_email || "—")}</td>
        <td>${formatMAD(o.total_amount)}</td>
        <td style="font-size:12px;color:#64748b">${esc(o.shipping_address || "—")}</td>
        <td><span class="badge ${s.cls}">${s.label}</span></td>
        <td>
          ${o.status !== "delivered" && o.status !== "cancelled"
            ? `<select class="filter-select" style="padding:4px 8px;font-size:11px" onchange="updateOrderStatus(${o.id}, this.value)">
                ${Object.entries(ORDER_STATUS).map(([k,v]) =>
                  `<option value="${k}" ${o.status===k?'selected':''}>${v.label}</option>`
                ).join('')}
               </select>`
            : `<span style="font-size:12px;color:#94a3b8">—</span>`}
        </td>
      </tr>`;
  }).join("");
}

async function updateOrderStatus(orderId, newStatus) {
  try {
    await apiFetchJson(`/orders/${orderId}/status`, {
      method: "PATCH",
      body: JSON.stringify({ status: newStatus }),
    });
    showToast("Statut mis à jour.", "success");
    await loadOrders();
  } catch (err) { showToast(formatApiError(err)); }
}

// ══════════════════════════════════════════════════════════════
// STOCK INTELLIGENCE — placeholder, endpoint pas encore stable
// Ne pas appeler GET /stock/intelligence/reorder-recommendations
// ══════════════════════════════════════════════════════════════
// ══════════════════════════════════════════════════════════════
// STOCK INTELLIGENCE — GET /stock/intelligence/reorder-recommendations
// Owner uniquement. Endpoint stable selon handoff guide.
// Compact par défaut, include_diagnostics=true pour le détail.
// ══════════════════════════════════════════════════════════════
let intelligenceData = null;

async function loadStockIntelligence(onlyRecommended = false) {
  const el = document.getElementById("stock-intelligence-placeholder");
  if (el) el.innerHTML = `<div style="text-align:center;padding:40px 0;color:#94a3b8"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:12px">Chargement de l'intelligence stock…</p></div>`;
  try {
    const qs = onlyRecommended ? "?only_recommended=true" : "";
    const data = await apiFetchJson(`/stock/intelligence/reorder-recommendations${qs}`);
    intelligenceData = unwrap(data);
    renderStockIntelligence(intelligenceData);
  } catch (err) {
    renderStockIntelligencePlaceholder(); // affiche le bouton "Charger l'analyse"
    showToast("Stock Intelligence : " + formatApiError(err));
  }
}

function renderStockIntelligence(d) {
  const el = document.getElementById("stock-intelligence-placeholder");
  if (!el || !d) { renderStockIntelligencePlaceholder(); return; }

  const summary = d.summary || {};
  const items   = Array.isArray(d.items) ? d.items : (Array.isArray(d) ? d : []);

  // KPI cards
  const kpiHtml = `
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px">
      ${[
        ["🔴", "Critiques",     summary.critical_count      ?? 0, "#fee2e2","#b91c1c"],
        ["🟠", "Élevée",        summary.high_count          ?? 0, "#fed7aa","#c2410c"],
        ["🟡", "Moyenne",       summary.medium_count        ?? 0, "#fef9c3","#92400e"],
        ["💰", "Valeur estimée",summary.estimated_reorder_value != null ? formatMAD(summary.estimated_reorder_value) : "—", "#dbeafe","#1d4ed8"],
        ["⚠️", "Faible confiance",summary.estimate_only_count ?? 0, "#f1f5f9","#475569"],
      ].map(([icon,label,val,bg,col]) => `
        <div style="background:${bg};border-radius:12px;padding:16px">
          <div style="font-size:20px;margin-bottom:6px">${icon}</div>
          <div style="font-size:20px;font-weight:800;color:${col}">${val}</div>
          <div style="font-size:12px;color:${col};font-weight:600">${label}</div>
        </div>`).join("")}
    </div>`;

  // Items table
  const critical = items.filter(i => i.priority === "CRITICAL");
  const tableHtml = critical.length === 0 ? "" : `
    <div class="table-card" style="margin-top:0">
      <div class="table-card-header">
        <div><div class="table-card-title">Produits critiques</div><div class="table-card-sub">${critical.length} produit(s) à réapprovisionner en priorité</div></div>
      </div>
      <table><thead><tr><th>Produit</th><th>SKU</th><th>Stock</th><th>Qté recommandée</th><th>Fournisseur</th><th>Délai</th><th>Confiance</th><th>Modèle</th></tr></thead>
      <tbody>${critical.map(i => `
        <tr>
          <td style="font-weight:600;font-size:13px">${esc(i.name||"—")}</td>
          <td style="font-family:monospace;font-size:12px;color:#94a3b8">${esc(i.sku||"—")}</td>
          <td style="font-weight:700;color:${i.current_stock<=0?"#dc2626":"#92400e"}">${i.current_stock??0}</td>
          <td style="font-weight:700;color:var(--blue)">${i.recommended_reorder_quantity??0}</td>
          <td style="font-size:12px">${esc(i.preferred_supplier_name||"—")}</td>
          <td>${i.supplier_lead_time_days != null ? `${i.supplier_lead_time_days}j` : "—"}</td>
          <td><span class="badge ${i.confidence==="HIGH"?"badge-accepted":i.confidence==="MEDIUM"?"badge-quoted":"badge-draft"}">${CONFIDENCE_LABELS[i.confidence]||i.confidence||"—"}</span></td>
          <td style="font-size:11px;color:#6366f1">${MODEL_LABELS[i.selected_model]||i.selected_model||"—"}</td>
        </tr>`).join("")}
      </tbody></table>
    </div>`;

  el.innerHTML = kpiHtml + tableHtml;
}

function renderStockIntelligencePlaceholder() {
  const el = document.getElementById("stock-intelligence-placeholder");
  if (!el) return;
  el.innerHTML = `
    <div style="background:linear-gradient(135deg,#f8faff,#eef2ff);border:1.5px dashed #a5b4fc;border-radius:16px;padding:32px;text-align:center">
      <div style="font-size:36px;margin-bottom:12px">🧠</div>
      <div style="font-weight:700;font-size:15px;color:#4338ca">Stock Intelligence</div>
      <div style="font-size:13px;color:#6366f1;margin-top:6px">Recommandations de réapprovisionnement</div>
      <div style="font-size:12px;color:#94a3b8;margin-top:10px;max-width:360px;margin-left:auto;margin-right:auto">
        Calcul de priorité, confiance, raisons, classe ABC/VED, modèle de prévision.
      </div>
      <button class="btn-primary" style="margin-top:20px" onclick="loadStockIntelligence()">
        <i class="fas fa-brain"></i> Charger l'analyse
      </button>
    </div>`;
}

// ══════════════════════════════════════════════════════════════
// PROMOTIONS — CRUD interne /promotions
// ══════════════════════════════════════════════════════════════
let allPromotions = [];

async function loadPromotions() {
  try {
    const data = await apiFetchJson("/promotions");
    allPromotions = unwrapItems(data);
    renderPromotions();
  } catch (err) { showToast("Promotions : " + formatApiError(err)); }
}

function renderPromotions() {
  const tbody = document.getElementById("promotions-tbody");
  if (!tbody) return;
  const sub = document.getElementById("promotions-count-sub");
  if (sub) sub.textContent = `${allPromotions.length} promotion(s)`;

  if (!allPromotions.length) { tbody.innerHTML = emptyRow(6, "🏷️", "Aucune promotion"); return; }

  const statusBadge = (s) => {
    const map = {
      ACTIVE:   ["badge-accepted","Active"],
      UPCOMING: ["badge-open",   "À venir"],
      EXPIRED:  ["badge-expired","Expirée"],
      DISABLED: ["badge-draft",  "Désactivée"],
    };
    const [cls,label] = map[s] || ["badge-draft", s];
    return `<span class="badge ${cls}">${label}</span>`;
  };

  tbody.innerHTML = allPromotions.map(p => `
    <tr>
      <td style="font-weight:600;font-size:13px">${esc(p.title)}</td>
      <td>${p.discount_type==="percentage" ? `-${p.discount_value}%` : formatMAD(p.discount_value)}</td>
      <td style="font-size:12px;color:#64748b">${formatDate(p.starts_at)}</td>
      <td style="font-size:12px;color:#64748b">${formatDate(p.ends_at)}</td>
      <td>${statusBadge(p.status||"DISABLED")}</td>
      <td>
        <button class="btn-sm" style="color:var(--red)" onclick="deletePromotion(${p.id})" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    </tr>`).join("");
}

async function deletePromotion(id) {
  if (!confirm("Supprimer cette promotion ?")) return;
  try {
    await apiFetchJson(`/promotions/${id}`, { method: "DELETE" });
    showToast("Promotion supprimée.", "success");
    await loadPromotions();
  } catch (err) { showToast(formatApiError(err)); }
}

// ══════════════════════════════════════════════════════════════
// REVIEWS — Modération /reviews
// ══════════════════════════════════════════════════════════════
let allReviews = [];

async function loadReviews(status = "pending") {
  try {
    const qs = status ? `?status=${status}` : "";
    const data = await apiFetchJson(`/reviews${qs}`);
    allReviews = unwrapItems(data);
    renderReviews();
  } catch (err) { showToast("Avis : " + formatApiError(err)); }
}

function renderReviews() {
  const tbody = document.getElementById("reviews-tbody");
  if (!tbody) return;
  const sub = document.getElementById("reviews-count-sub");
  if (sub) sub.textContent = `${allReviews.length} avis`;

  if (!allReviews.length) { tbody.innerHTML = emptyRow(6, "⭐", "Aucun avis"); return; }

  const stars = (n) => "★".repeat(n) + "☆".repeat(5-n);
  tbody.innerHTML = allReviews.map(r => `
    <tr>
      <td style="font-weight:600;font-size:13px">${esc(r.product_name || "#"+r.component_id)}</td>
      <td style="color:#f59e0b">${stars(r.rating||0)}</td>
      <td style="font-size:13px;max-width:200px">${esc(r.comment||"—")}</td>
      <td style="font-size:12px;color:#64748b">${formatDate(r.created_at)}</td>
      <td>
        <span class="badge ${r.status==="approved"?"badge-accepted":r.status==="rejected"?"badge-rejected":"badge-draft"}">
          ${r.status==="approved"?"Approuvé":r.status==="rejected"?"Rejeté":"En attente"}
        </span>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          ${r.status!=="approved"  ? `<button class="btn-sm" style="color:var(--green)" onclick="moderateReview(${r.id},'approved')"><i class="fas fa-check"></i></button>` : ""}
          ${r.status!=="rejected"  ? `<button class="btn-sm" style="color:var(--red)"   onclick="moderateReview(${r.id},'rejected')"><i class="fas fa-xmark"></i></button>` : ""}
          <button class="btn-sm" style="color:#64748b" onclick="moderateReview(${r.id},'pending')" title="Remettre en attente"><i class="fas fa-rotate-left"></i></button>
        </div>
      </td>
    </tr>`).join("");
}

async function moderateReview(id, status) {
  try {
    await apiFetchJson(`/reviews/${id}/status`, {
      method: "PATCH",
      body: JSON.stringify({ status }),
    });
    const labels = { approved:"Approuvé", rejected:"Rejeté", pending:"Remis en attente" };
    showToast(`Avis ${labels[status]||status}.`, "success");
    const filter = document.getElementById("reviews-status-filter")?.value || "pending";
    await loadReviews(filter);
  } catch (err) { showToast(formatApiError(err)); }
}
