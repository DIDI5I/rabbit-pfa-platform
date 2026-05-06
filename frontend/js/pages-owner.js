/* ============================================================
   pages-owner.js — Rabbit B2B MRO Platform
   Modules de page pour le tableau de bord administrateur.
   Chaque module expose : { init, render }
   ============================================================ */

// ══════════════════════════════════════════════════════════════
// DASHBOARD
// ══════════════════════════════════════════════════════════════
const DashboardPage = (() => {
  let _loaded = false;

  async function init() {
    if (_loaded) return;
    _loaded = true;
    await render();
  }

  async function render() {
    const el = document.getElementById("view-dashboard");
    if (!el) return;

    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header">
          <h1>${L.page.dashboard.title}</h1>
          <p>${L.page.dashboard.sub}</p>
        </div>
        <button class="btn-secondary" onclick="DashboardPage.refresh()">
          <i class="fas fa-rotate"></i> ${L.btn.refresh}
        </button>
      </div>
      <div id="dash-kpis" class="kpi-grid"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5)">
        <div id="dash-alerts-card"></div>
        <div id="dash-intel-card"></div>
      </div>
      <div id="dash-reorder-preview"></div>
    `;

    await Promise.all([
      _loadKPIs(),
      _loadAlerts(),
      _loadIntelPreview(),
    ]);
  }

  async function _loadKPIs() {
    try {
      const data = await dashboardService.summary();
      _renderKPIs(data);
    } catch {
      document.getElementById("dash-kpis").innerHTML =
        `<p style="color:var(--text-3);font-size:13px">${L.errors.load}</p>`;
    }
  }

  function _renderKPIs(d) {
    const kpis = [
      { label: "Produits actifs",      val: d.active_products    ?? d.total_products ?? "—", icon: "fa-box",           cls: "blue"   },
      { label: "Valeur du stock",      val: d.total_stock_value != null ? formatMAD(d.total_stock_value) : "—", icon: "fa-coins", cls: "green"  },
      { label: "Stock bas",            val: d.low_stock_count    ?? "—", icon: "fa-triangle-exclamation", cls: "amber"  },
      { label: "Ruptures de stock",    val: d.out_of_stock_count ?? "—", icon: "fa-circle-xmark",         cls: "red"    },
      { label: "RFQs ouvertes",        val: d.open_rfqs_count    ?? d.open_rfq_count ?? "—", icon: "fa-file-invoice", cls: "purple" },
      { label: "Promotions actives",   val: d.active_promotions  ?? "—", icon: "fa-tag",    cls: "brand"  },
      { label: "Avis en attente",      val: d.pending_reviews    ?? "—", icon: "fa-star",   cls: "amber"  },
      { label: "Notifications",        val: d.unread_notifications ?? "—", icon: "fa-bell", cls: "blue"   },
    ];
    document.getElementById("dash-kpis").innerHTML = kpis.map((k, i) => `
      <div class="kpi-card fade-up delay-${Math.min(i,4)}">
        <div class="kpi-card-top">
          <div class="kpi-icon ${k.cls}"><i class="fas ${k.icon}"></i></div>
        </div>
        <div class="kpi-value">${k.val}</div>
        <div class="kpi-label">${k.label}</div>
      </div>
    `).join("");
  }

  async function _loadAlerts() {
    const el = document.getElementById("dash-alerts-card");
    if (!el) return;
    try {
      const alerts = await inventoryService.alerts();
      const out = alerts.filter(a => (a.current_stock ?? a.stock_qty ?? 0) <= 0);
      const low = alerts.filter(a => {
        const s = a.current_stock ?? a.stock_qty ?? 0;
        return s > 0 && s <= (a.low_stock_threshold ?? 0);
      });
      el.innerHTML = `
        <div class="table-card">
          <div class="table-card-header">
            <div>
              <div class="table-card-title"><i class="fas fa-triangle-exclamation" style="color:var(--red)"></i> Alertes de stock</div>
              <div class="table-card-sub">${alerts.length} produit(s) à attention</div>
            </div>
            <button class="btn-sm" onclick="Router.navigate('inventory')">
              <i class="fas fa-arrow-right"></i> Voir tout
            </button>
          </div>
          <div style="padding:var(--space-3) var(--space-5)">
            ${!alerts.length
              ? `<div class="empty-state"><i class="fas fa-check-circle" style="color:var(--green)"></i><p>Aucune alerte de stock</p></div>`
              : [...out.slice(0,3), ...low.slice(0,3)].map(a => `
                  <div class="alert-card" style="margin-bottom:var(--space-2)">
                    <div class="alert-icon ${(a.current_stock ?? 0) <= 0 ? "out" : "low"}">
                      <i class="fas ${(a.current_stock ?? 0) <= 0 ? "fa-circle-xmark" : "fa-triangle-exclamation"}"></i>
                    </div>
                    <div class="alert-body">
                      <div class="alert-name">${esc(a.name || a.product_name || "—")}</div>
                      <div class="alert-detail">${esc(a.sku || "")} · Stock : ${a.current_stock ?? "—"}</div>
                    </div>
                    ${stockStatusBadge((a.current_stock ?? 0) <= 0 ? "OUT_OF_STOCK" : "LOW_STOCK")}
                  </div>`).join("")
            }
          </div>
        </div>`;
    } catch {
      el.innerHTML = `<div class="table-card"><div style="padding:var(--space-5);color:var(--text-3);font-size:13px">${L.errors.load}</div></div>`;
    }
  }

  async function _loadIntelPreview() {
    const el = document.getElementById("dash-intel-card");
    if (!el) return;
    try {
      const resp = await stockService.reorderRecommendations({ only_recommended: true, limit: 5 });
      const items = resp.items || resp.recommendations || [];
      const summary = resp.summary || {};
      el.innerHTML = `
        <div class="table-card">
          <div class="table-card-header">
            <div>
              <div class="table-card-title"><i class="fas fa-brain" style="color:var(--brand)"></i> Intelligence stock</div>
              <div class="table-card-sub">${summary.critical_count || 0} critique(s) · ${summary.high_count || 0} élevée(s)</div>
            </div>
            <button class="btn-sm" onclick="Router.navigate('intelligence')">
              <i class="fas fa-arrow-right"></i> Voir tout
            </button>
          </div>
          <div style="padding:var(--space-3) var(--space-5)">
            ${!items.length
              ? `<div class="empty-state"><i class="fas fa-check-circle" style="color:var(--green)"></i><p>${L.empty.reorder}</p></div>`
              : items.slice(0,4).map(i => `
                  <div style="display:flex;align-items:center;gap:var(--space-3);padding:9px 0;border-bottom:1px solid var(--border)">
                    <div style="flex:1;min-width:0">
                      <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(i.name || "—")}</div>
                      <div style="font-size:11px;color:var(--text-3)">${esc(i.sku || "")}</div>
                    </div>
                    ${priorityBadge(i.priority)}
                    ${confidenceBadge(i.confidence)}
                  </div>`).join("")
            }
          </div>
        </div>`;
    } catch {
      el.innerHTML = `<div class="table-card"><div style="padding:var(--space-5);color:var(--text-3);font-size:13px">${L.errors.load}</div></div>`;
    }
  }

  async function refresh() {
    _loaded = false;
    await render();
  }

  return { init, render, refresh };
})();

// ══════════════════════════════════════════════════════════════
// PRODUCTS
// ══════════════════════════════════════════════════════════════
const ProductsPage = (() => {
  let _products = [];
  let _filtered = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-products");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header"><h1>${L.page.products.title}</h1><p>${L.page.products.sub}</p></div>
        <button class="btn-primary" onclick="ProductsPage.openCreate()">
          <i class="fas fa-plus"></i> Nouveau produit
        </button>
      </div>
      <div class="filter-bar">
        <div class="filter-search">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" id="prod-search" placeholder="Rechercher par nom ou SKU…" oninput="ProductsPage.search()">
        </div>
        <select class="filter-select" id="prod-cat" onchange="ProductsPage.search()">
          <option value="">Toutes les catégories</option>
          ${Object.entries(L.categories).map(([k,v])=>`<option value="${k}">${v}</option>`).join("")}
        </select>
        <select class="filter-select" id="prod-stock" onchange="ProductsPage.search()">
          <option value="">Tous les stocks</option>
          <option value="OK">Disponible</option>
          <option value="LOW_STOCK">Stock bas</option>
          <option value="OUT_OF_STOCK">Rupture</option>
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div>
            <div class="table-card-title">Catalogue interne</div>
            <div class="table-card-sub" id="prod-sub"></div>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>${L.table.sku}</th>
              <th>${L.table.name}</th>
              <th>${L.table.category}</th>
              <th>Stock</th>
              <th>Seuil</th>
              <th>Valeur unitaire</th>
              <th>Disponibilité</th>
              <th>${L.table.actions}</th>
            </tr></thead>
            <tbody id="prod-tbody">
              <tr><td colspan="8"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _products = await productService.list();
      _filtered = [..._products];
      _renderTable();
    } catch {
      document.getElementById("prod-tbody").innerHTML =
        `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const q   = (document.getElementById("prod-search")?.value || "").toLowerCase();
    const cat = document.getElementById("prod-cat")?.value || "";
    const stk = document.getElementById("prod-stock")?.value || "";

    _filtered = _products.filter(p => {
      const mq  = !q   || (p.name || "").toLowerCase().includes(q) || (p.sku || "").toLowerCase().includes(q);
      const mc  = !cat || p.category === cat;
      const qty = p.current_stock ?? p.stock_qty ?? null;
      const thr = p.low_stock_threshold ?? 0;
      let sk = "";
      if (qty !== null) {
        if (qty <= 0)        sk = "OUT_OF_STOCK";
        else if (qty <= thr) sk = "LOW_STOCK";
        else                  sk = "OK";
      }
      const ms = !stk || sk === stk;
      return mq && mc && ms;
    });
    _renderTable();
  }

  function _renderTable() {
    const sub = document.getElementById("prod-sub");
    if (sub) sub.textContent = `${_filtered.length} produit(s)`;

    const tbody = document.getElementById("prod-tbody");
    if (!tbody) return;

    if (!_filtered.length) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-box-open"></i><p>${L.empty.products}</p></div></td></tr>`;
      return;
    }

    tbody.innerHTML = _filtered.map(p => {
      const qty = p.current_stock ?? p.stock_qty ?? null;
      const thr = p.low_stock_threshold ?? 0;
      return `<tr onclick="ProductsPage.openDetail(${p.id})" style="cursor:pointer">
        <td style="font-family:monospace;font-size:12px;color:var(--text-3)">${esc(p.sku || "—")}</td>
        <td style="font-weight:600">${esc(p.name)}</td>
        <td>${catPill(p.category)}</td>
        <td>
          <div style="display:flex;align-items:center;gap:var(--space-2)">
            ${stockGauge(qty, thr)}
            <span style="font-weight:700">${qty ?? "—"}</span>
          </div>
        </td>
        <td style="color:var(--text-3)">${thr}</td>
        <td>${p.unit_purchase_cost != null ? formatMAD(p.unit_purchase_cost) : "—"}</td>
        <td>${stockBadgeFromProduct(p)}</td>
        <td onclick="event.stopPropagation()">
          <div style="display:flex;gap:var(--space-1)">
            <button class="btn-sm" onclick="ProductsPage.openDetail(${p.id})"><i class="fas fa-eye"></i></button>
            <button class="btn-sm" onclick="ProductsPage.askChatbot(${p.id}, '${esc(p.name)}')"><i class="fas fa-robot"></i></button>
          </div>
        </td>
      </tr>`;
    }).join("");
  }

  function openDetail(id) {
    ProductPanel.open(id);
  }

  function openCreate() {
    showToast("Création produit : module en développement.", "warn");
  }

  function askChatbot(id, name) {
    Chatbot.open();
    setTimeout(() => {
      const input = document.getElementById("chatbot-input");
      if (input) { input.value = `détails du produit ${name}`; input.focus(); }
    }, 300);
  }

  return { init, render, search, openDetail, openCreate, askChatbot };
})();

// ══════════════════════════════════════════════════════════════
// INVENTORY
// ══════════════════════════════════════════════════════════════
const InventoryPage = (() => {
  let _items    = [];
  let _filtered = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-inventory");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header"><h1>${L.page.inventory.title}</h1><p>${L.page.inventory.sub}</p></div>
      </div>
      <div id="inv-alerts-row" style="margin-bottom:var(--space-5)"></div>
      <div class="filter-bar">
        <div class="filter-search">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" id="inv-search" placeholder="Rechercher…" oninput="InventoryPage.search()">
        </div>
        <select class="filter-select" id="inv-status" onchange="InventoryPage.search()">
          <option value="">Tous les statuts</option>
          <option value="OK">Disponible</option>
          <option value="LOW_STOCK">Stock bas</option>
          <option value="OUT_OF_STOCK">Rupture</option>
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Niveaux de stock</div>
          <div class="table-card-sub" id="inv-sub"></div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>SKU</th><th>Produit</th><th>Catégorie</th>
              <th>Stock actuel</th><th>Seuil</th><th>Jauge</th>
              <th>Valeur stock</th><th>Statut</th><th>Actions</th>
            </tr></thead>
            <tbody id="inv-tbody">
              <tr><td colspan="9"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await Promise.all([_loadAlerts(), _loadInventory()]);
  }

  async function _loadAlerts() {
    const el = document.getElementById("inv-alerts-row");
    if (!el) return;
    try {
      const alerts = await inventoryService.alerts();
      if (!alerts.length) { el.innerHTML = ""; return; }
      const out = alerts.filter(a => (a.current_stock ?? 0) <= 0);
      const low = alerts.filter(a => { const s = a.current_stock ?? 0; return s > 0 && s <= (a.low_stock_threshold ?? 0); });
      el.innerHTML = `<div class="alert-grid">
        ${[...out, ...low].slice(0, 6).map(a => {
          const isOut = (a.current_stock ?? 0) <= 0;
          return `<div class="alert-card">
            <div class="alert-icon ${isOut ? "out" : "low"}">
              <i class="fas ${isOut ? "fa-circle-xmark" : "fa-triangle-exclamation"}"></i>
            </div>
            <div class="alert-body">
              <div class="alert-name">${esc(a.name || "—")}</div>
              <div class="alert-detail">Stock : <strong>${a.current_stock ?? "—"}</strong> / Seuil : ${a.low_stock_threshold ?? "—"}</div>
            </div>
            ${stockStatusBadge(isOut ? "OUT_OF_STOCK" : "LOW_STOCK")}
          </div>`;
        }).join("")}
      </div>`;
    } catch {}
  }

  async function _loadInventory() {
    try {
      const data = await inventoryService.summary();
      _items = Array.isArray(data) ? data : (data.items || []);
      _filtered = [..._items];
      _renderTable();
    } catch {
      document.getElementById("inv-tbody").innerHTML =
        `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const q = (document.getElementById("inv-search")?.value || "").toLowerCase();
    const s = document.getElementById("inv-status")?.value || "";
    _filtered = _items.filter(p => {
      const mq = !q || (p.name || "").toLowerCase().includes(q) || (p.sku || "").toLowerCase().includes(q);
      const qty = p.current_stock ?? p.stock_qty ?? null;
      const thr = p.low_stock_threshold ?? 0;
      let key = "";
      if (qty !== null) { if (qty <= 0) key = "OUT_OF_STOCK"; else if (qty <= thr) key = "LOW_STOCK"; else key = "OK"; }
      return mq && (!s || key === s);
    });
    _renderTable();
  }

  function _renderTable() {
    const sub = document.getElementById("inv-sub");
    if (sub) sub.textContent = `${_filtered.length} produit(s)`;
    const tbody = document.getElementById("inv-tbody");
    if (!tbody) return;
    if (!_filtered.length) {
      tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-warehouse"></i><p>${L.empty.inventory}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = _filtered.map(p => {
      const qty  = p.current_stock ?? p.stock_qty ?? null;
      const thr  = p.low_stock_threshold ?? 0;
      const cost = p.unit_purchase_cost ?? p.unit_cost ?? null;
      const val  = qty != null && cost != null ? formatMAD(qty * cost) : "—";
      return `<tr>
        <td style="font-family:monospace;font-size:12px;color:var(--text-3)">${esc(p.sku || "—")}</td>
        <td style="font-weight:600">${esc(p.name || "—")}</td>
        <td>${catPill(p.category)}</td>
        <td style="font-weight:700;font-size:14px">${qty ?? "—"}</td>
        <td style="color:var(--text-3)">${thr}</td>
        <td>${stockGauge(qty, thr)}</td>
        <td style="color:var(--blue);font-weight:700">${val}</td>
        <td>${stockBadgeFromProduct(p)}</td>
        <td>
          <div style="display:flex;gap:var(--space-1)">
            <button class="btn-sm" onclick="ProductPanel.open(${p.id})" title="Détails"><i class="fas fa-eye"></i></button>
            <button class="btn-sm" onclick="MovementsPanel.open(${p.id})" title="Mouvements"><i class="fas fa-chart-line"></i></button>
          </div>
        </td>
      </tr>`;
    }).join("");
  }

  return { init, render, search };
})();

// ══════════════════════════════════════════════════════════════
// STOCK INTELLIGENCE
// ══════════════════════════════════════════════════════════════
const IntelligencePage = (() => {
  let _data      = {};
  let _filter    = "all";
  let _loaded    = false;

  async function init() {
    if (_loaded) return;
    _loaded = true;
    await render();
  }

  async function render() {
    const el = document.getElementById("view-intelligence");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header"><h1>${L.page.intelligence.title}</h1><p>${L.page.intelligence.sub}</p></div>
        <div style="display:flex;gap:var(--space-2);align-items:center">
          <select class="filter-select" id="intel-filter" onchange="IntelligencePage.applyFilter()">
            <option value="all">Tous les produits</option>
            <option value="recommended">Recommandés seulement</option>
            <option value="CRITICAL">Critique uniquement</option>
            <option value="HIGH">Élevée uniquement</option>
            <option value="MEDIUM">Moyenne uniquement</option>
            <option value="LOW">Faible uniquement</option>
          </select>
          <button class="btn-secondary" onclick="IntelligencePage.refresh()">
            <i class="fas fa-rotate"></i>
          </button>
        </div>
      </div>
      <div id="intel-kpis" class="kpi-grid"></div>
      <div class="table-card">
        <div class="table-card-header">
          <div>
            <div class="table-card-title">Recommandations de réapprovisionnement</div>
            <div class="table-card-sub" id="intel-sub"></div>
          </div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>SKU</th><th>Produit</th><th>Stock actuel</th>
              <th>Point de commande</th><th>Stock sécurité</th>
              <th>Priorité</th><th>Confiance</th><th>Modèle</th>
              <th>Flags</th><th>Recommandation</th>
            </tr></thead>
            <tbody id="intel-tbody">
              <tr><td colspan="10"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _data = await stockService.reorderRecommendations();
      _renderKPIs();
      applyFilter();
    } catch {
      document.getElementById("intel-tbody").innerHTML =
        `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function _renderKPIs() {
    const s = _data.summary || {};
    const kpis = [
      { label: "Critique",          val: s.critical_count      ?? 0, cls: "red",    icon: "fa-circle-exclamation" },
      { label: "Élevée",            val: s.high_count          ?? 0, cls: "amber",  icon: "fa-triangle-exclamation" },
      { label: "Moyenne",           val: s.medium_count        ?? 0, cls: "blue",   icon: "fa-circle-info" },
      { label: "Faible",            val: s.low_count           ?? 0, cls: "green",  icon: "fa-circle-check" },
      { label: "Estimation seule",  val: s.estimate_only_count ?? 0, cls: "purple", icon: "fa-question-circle" },
      { label: "Recommandés",       val: s.recommended_count   ?? 0, cls: "brand",  icon: "fa-cart-shopping" },
    ];
    document.getElementById("intel-kpis").innerHTML = kpis.map((k, i) => `
      <div class="kpi-card fade-up delay-${Math.min(i,4)}">
        <div class="kpi-card-top"><div class="kpi-icon ${k.cls}"><i class="fas ${k.icon}"></i></div></div>
        <div class="kpi-value">${k.val}</div>
        <div class="kpi-label">${k.label}</div>
      </div>`).join("");
  }

  function applyFilter() {
    _filter = document.getElementById("intel-filter")?.value || "all";
    let items = _data.items || _data.recommendations || [];
    if (_filter === "recommended") items = items.filter(i => i.recommendation);
    else if (_filter !== "all")   items = items.filter(i => i.priority === _filter);
    _renderTable(items);
  }

  function _renderTable(items) {
    const sub = document.getElementById("intel-sub");
    if (sub) sub.textContent = `${items.length} produit(s)`;
    const tbody = document.getElementById("intel-tbody");
    if (!tbody) return;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-brain"></i><p>${L.empty.reorder}</p></div></td></tr>`;
      return;
    }
    // Sort: CRITICAL first
    const order = { CRITICAL:0, HIGH:1, MEDIUM:2, LOW:3, NONE:4 };
    items = [...items].sort((a,b) => (order[a.priority]??9) - (order[b.priority]??9));

    tbody.innerHTML = items.map(i => {
      const recommended = i.recommendation;
      const rowStyle = i.priority === "CRITICAL" ? "background:rgba(220,38,38,.04)" :
                       i.priority === "HIGH"     ? "background:rgba(217,119,6,.04)" : "";
      return `<tr style="${rowStyle}">
        <td style="font-family:monospace;font-size:12px;color:var(--text-3)">${esc(i.sku || "—")}</td>
        <td>
          <div style="font-weight:600;font-size:13px">${esc(i.name || "—")}</div>
          ${i.category ? catPill(i.category) : ""}
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:var(--space-2)">
            ${stockGauge(i.current_stock, i.low_stock_threshold)}
            <strong>${i.current_stock ?? "—"}</strong>
          </div>
        </td>
        <td style="font-weight:600">${i.reorder_point ?? "—"}</td>
        <td>${i.safety_stock ?? "—"}</td>
        <td>${priorityBadge(i.priority)}</td>
        <td>${confidenceBadge(i.confidence)}</td>
        <td style="font-size:11px;color:var(--text-3)">${modelLabel(i.calculation_mode)}</td>
        <td style="max-width:180px">
          ${dataFlagPills(i.data_quality_flags)}
          ${reasonCodePills(i.reason_codes)}
        </td>
        <td>
          ${recommended
            ? `<span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:var(--red)"><i class="fas fa-arrow-up"></i> Réapprovisionner</span>`
            : `<span style="font-size:12px;color:var(--green)"><i class="fas fa-check"></i> OK</span>`}
        </td>
      </tr>`;
    }).join("");
  }

  async function refresh() {
    _loaded = false;
    await render();
  }

  return { init, render, applyFilter, refresh };
})();

// ══════════════════════════════════════════════════════════════
// RFQs
// ══════════════════════════════════════════════════════════════
const RfqsPage = (() => {
  let _rfqs = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-rfqs");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header"><h1>${L.page.rfqs.title}</h1><p>${L.page.rfqs.sub}</p></div>
      </div>
      <div class="filter-bar">
        <div class="filter-search">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" id="rfq-search" placeholder="Rechercher…" oninput="RfqsPage.search()">
        </div>
        <select class="filter-select" id="rfq-status-filter" onchange="RfqsPage.search()">
          <option value="">Tous les statuts</option>
          ${Object.entries(L.rfqStatus).map(([k,v])=>`<option value="${k}">${v}</option>`).join("")}
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Demandes de devis</div>
          <div class="table-card-sub" id="rfq-sub"></div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>#</th><th>Produit</th><th>Fournisseur</th>
              <th>Qté demandée</th><th>Prix proposé</th><th>Délai</th>
              <th>Statut</th><th>Date</th><th>Actions</th>
            </tr></thead>
            <tbody id="rfq-tbody">
              <tr><td colspan="9"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _rfqs = await rfqService.list();
      _render(_rfqs);
    } catch {
      document.getElementById("rfq-tbody").innerHTML =
        `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const q = (document.getElementById("rfq-search")?.value || "").toLowerCase();
    const s = document.getElementById("rfq-status-filter")?.value || "";
    const filtered = _rfqs.filter(r =>
      (!q || (r.component_name || r.product_name || "").toLowerCase().includes(q) ||
             (r.supplier_name || "").toLowerCase().includes(q)) &&
      (!s || r.status === s)
    );
    _render(filtered);
  }

  function _render(list) {
    const sub = document.getElementById("rfq-sub");
    if (sub) sub.textContent = `${list.length} demande(s)`;
    const tbody = document.getElementById("rfq-tbody");
    if (!tbody) return;
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-file-invoice"></i><p>${L.empty.rfqs}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = list.map(r => `<tr onclick="RfqsPage.openDetail(${r.id})" style="cursor:pointer">
      <td style="font-size:12px;font-weight:700;color:var(--text-3)">#${r.id}</td>
      <td>
        <div style="font-weight:600;font-size:13px">${esc(r.component_name || r.product_name || "—")}</div>
        <div style="font-size:11px;font-family:monospace;color:var(--text-3)">${esc(r.component_sku || r.product_sku || "")}</div>
      </td>
      <td>${esc(r.supplier_name || "—")}</td>
      <td style="font-weight:700">${r.quantity_requested ?? "—"}</td>
      <td>${r.quoted_price != null ? `<span style="font-weight:700;color:var(--green)">${formatMAD(r.quoted_price)}</span>` : `<span style="color:var(--text-3)">—</span>`}</td>
      <td>${r.lead_time_days != null ? `${r.lead_time_days} j` : "—"}</td>
      <td>${rfqBadge(r.status)}</td>
      <td style="font-size:12px;color:var(--text-3)">${formatDate(r.created_at)}</td>
      <td onclick="event.stopPropagation()">
        <button class="btn-sm" onclick="RfqsPage.openDetail(${r.id})"><i class="fas fa-eye"></i></button>
      </td>
    </tr>`).join("");
  }

  function openDetail(id) {
    RfqPanel.open(id, _rfqs.find(r => r.id === id));
  }

  return { init, render, search, openDetail };
})();

// ══════════════════════════════════════════════════════════════
// PURCHASE LOTS
// ══════════════════════════════════════════════════════════════
const LotsPage = (() => {
  let _lots = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-lots");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header"><h1>${L.page.lots.title}</h1><p>${L.page.lots.sub}</p></div>
      <div class="filter-bar">
        <div class="filter-search">
          <i class="fas fa-magnifying-glass"></i>
          <input type="text" id="lots-search" placeholder="Rechercher…" oninput="LotsPage.search()">
        </div>
        <select class="filter-select" id="lots-status" onchange="LotsPage.search()">
          <option value="">Tous les statuts</option>
          ${Object.entries(L.lotStatus).map(([k,v])=>`<option value="${k}">${v}</option>`).join("")}
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Lots d'achat</div>
          <div class="table-card-sub" id="lots-sub"></div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>#</th><th>Produit</th><th>Fournisseur</th>
              <th>Qté reçue</th><th>Coût unitaire</th><th>Coût total</th>
              <th>Statut</th><th>Date</th><th>Actions</th>
            </tr></thead>
            <tbody id="lots-tbody">
              <tr><td colspan="9"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _lots = await purchaseLotService.list();
      _render(_lots);
    } catch {
      document.getElementById("lots-tbody").innerHTML =
        `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const q = (document.getElementById("lots-search")?.value || "").toLowerCase();
    const s = document.getElementById("lots-status")?.value || "";
    _render(_lots.filter(l =>
      (!q || (l.component_name || l.product_name || "").toLowerCase().includes(q) ||
             (l.supplier_name || "").toLowerCase().includes(q)) &&
      (!s || l.status === s)
    ));
  }

  function _render(list) {
    const sub = document.getElementById("lots-sub");
    if (sub) sub.textContent = `${list.length} lot(s)`;
    const tbody = document.getElementById("lots-tbody");
    if (!tbody) return;
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-pallet"></i><p>${L.empty.lots}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = list.map(l => `<tr>
      <td style="font-size:12px;font-weight:700;color:var(--text-3)">#${l.id}</td>
      <td style="font-weight:600">${esc(l.component_name || l.product_name || "—")}</td>
      <td>${esc(l.supplier_name || "—")}</td>
      <td style="font-weight:700">${l.quantity_received ?? l.quantity ?? "—"}</td>
      <td>${l.unit_purchase_cost != null ? formatMAD(l.unit_purchase_cost) : "—"}</td>
      <td style="font-weight:700;color:var(--blue)">${l.total_purchase_cost != null ? formatMAD(l.total_purchase_cost) : "—"}</td>
      <td>${lotStatusBadge(l.status)}</td>
      <td style="font-size:12px;color:var(--text-3)">${formatDate(l.purchase_date || l.created_at)}</td>
      <td>
        <button class="btn-sm" onclick="LotPanel.open(${l.id})"><i class="fas fa-eye"></i></button>
      </td>
    </tr>`).join("");
  }

  return { init, render, search };
})();

// ══════════════════════════════════════════════════════════════
// PROMOTIONS
// ══════════════════════════════════════════════════════════════
const PromotionsPage = (() => {
  let _promos = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-promotions");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header"><h1>${L.page.promotions.title}</h1><p>${L.page.promotions.sub}</p></div>
        <button class="btn-primary" disabled title="Disponible prochainement">
          <i class="fas fa-plus"></i> Nouvelle promotion
        </button>
      </div>
      <div class="filter-bar">
        <select class="filter-select" id="promo-status" onchange="PromotionsPage.search()">
          <option value="">Tous les statuts</option>
          ${Object.entries(L.promotionStatus).map(([k,v])=>`<option value="${k}">${v}</option>`).join("")}
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Promotions</div>
          <div class="table-card-sub" id="promo-sub"></div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>#</th><th>Titre</th><th>Remise</th>
              <th>Début</th><th>Fin</th><th>Statut</th><th>Actions</th>
            </tr></thead>
            <tbody id="promo-tbody">
              <tr><td colspan="7"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _promos = await promotionService.list();
      _render(_promos);
    } catch {
      document.getElementById("promo-tbody").innerHTML =
        `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const s = document.getElementById("promo-status")?.value || "";
    _render(s ? _promos.filter(p => p.status === s) : _promos);
  }

  function _render(list) {
    const sub = document.getElementById("promo-sub");
    if (sub) sub.textContent = `${list.length} promotion(s)`;
    const tbody = document.getElementById("promo-tbody");
    if (!tbody) return;
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-tag"></i><p>${L.empty.promotions}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = list.map(p => `<tr>
      <td style="font-size:12px;color:var(--text-3)">#${p.id}</td>
      <td>
        <div style="font-weight:600">${esc(p.title || "—")}</div>
        ${p.description ? `<div style="font-size:12px;color:var(--text-3)">${esc(p.description.slice(0,60))}…</div>` : ""}
      </td>
      <td>
        <span style="font-weight:700;color:var(--red)">
          ${discountLabel(p.discount_type, p.discount_value)}
        </span>
      </td>
      <td style="font-size:12px">${formatDate(p.starts_at)}</td>
      <td style="font-size:12px">${formatDate(p.ends_at)}</td>
      <td>${promotionStatusBadge(p.status)}</td>
      <td><button class="btn-sm" disabled><i class="fas fa-eye"></i> Voir</button></td>
    </tr>`).join("");
  }

  return { init, render, search };
})();

// ══════════════════════════════════════════════════════════════
// REVIEWS
// ══════════════════════════════════════════════════════════════
const ReviewsPage = (() => {
  let _reviews = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-reviews");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header"><h1>${L.page.reviews.title}</h1><p>${L.page.reviews.sub}</p></div>
      <div class="filter-bar">
        <select class="filter-select" id="rev-status" onchange="ReviewsPage.search()">
          <option value="">Tous les statuts</option>
          ${Object.entries(L.reviewStatus).map(([k,v])=>`<option value="${k}">${v}</option>`).join("")}
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Avis clients</div>
          <div class="table-card-sub" id="rev-sub"></div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>#</th><th>Produit</th><th>Utilisateur</th>
              <th>Note</th><th>Commentaire</th><th>Statut</th><th>Date</th><th>Actions</th>
            </tr></thead>
            <tbody id="rev-tbody">
              <tr><td colspan="8"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _reviews = await reviewService.list();
      _render(_reviews);
    } catch {
      document.getElementById("rev-tbody").innerHTML =
        `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const s = document.getElementById("rev-status")?.value || "";
    _render(s ? _reviews.filter(r => r.status === s) : _reviews);
  }

  function _render(list) {
    const sub = document.getElementById("rev-sub");
    if (sub) sub.textContent = `${list.length} avis`;
    const tbody = document.getElementById("rev-tbody");
    if (!tbody) return;
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-star"></i><p>${L.empty.reviews}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = list.map(r => `<tr>
      <td style="font-size:12px;color:var(--text-3)">#${r.id}</td>
      <td style="font-weight:600">${esc(r.product_name || r.component_name || "—")}</td>
      <td style="font-size:12px">${esc(r.user_name || r.client_name || "—")}</td>
      <td>${renderStars(r.rating)}</td>
      <td style="max-width:200px;font-size:12px;color:var(--text-2)">
        ${r.comment ? esc(r.comment.slice(0, 80)) + (r.comment.length > 80 ? "…" : "") : "—"}
      </td>
      <td>${reviewStatusBadge(r.status)}</td>
      <td style="font-size:12px;color:var(--text-3)">${formatDate(r.created_at)}</td>
      <td>
        ${r.status === "pending" ? `
          <div style="display:flex;gap:4px">
            <button class="btn-sm btn-success" onclick="ReviewsPage.moderate(${r.id},'approved')"><i class="fas fa-check"></i></button>
            <button class="btn-sm btn-danger"  onclick="ReviewsPage.moderate(${r.id},'rejected')"><i class="fas fa-xmark"></i></button>
          </div>` : `<span style="font-size:12px;color:var(--text-3)">Traité</span>`}
      </td>
    </tr>`).join("");
  }

  async function moderate(id, status) {
    try {
      await reviewService.updateStatus(id, status);
      showToast(`Avis ${status === "approved" ? "approuvé" : "rejeté"}.`, "success");
      await _load();
    } catch (err) {
      showToast(formatApiError(err));
    }
  }

  return { init, render, search, moderate };
})();

// ══════════════════════════════════════════════════════════════
// NOTIFICATIONS
// ══════════════════════════════════════════════════════════════
const NotificationsPage = (() => {
  let _notifs = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-notifications");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header-row">
        <div class="page-header"><h1>${L.page.notifications.title}</h1><p>${L.page.notifications.sub}</p></div>
        <button class="btn-secondary" onclick="NotificationsPage.markAll()">
          <i class="fas fa-check-double"></i> ${L.btn.markAllRead}
        </button>
      </div>
      <div id="notif-list" style="display:flex;flex-direction:column;gap:var(--space-2)">
        <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _notifs = await notificationService.list();
      _render();
    } catch {
      document.getElementById("notif-list").innerHTML =
        `<div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div>`;
    }
  }

  function _render() {
    const el = document.getElementById("notif-list");
    if (!el) return;
    if (!_notifs.length) {
      el.innerHTML = `<div class="empty-state"><i class="fas fa-bell-slash"></i><p>${L.empty.notifications}</p></div>`;
      return;
    }
    el.innerHTML = _notifs.map(n => {
      const unread = !n.read_at;
      const type   = L.notifType[n.type] || esc(n.type) || "Notification";
      return `<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);
                           padding:var(--space-4) var(--space-5);display:flex;align-items:flex-start;
                           gap:var(--space-4);${unread ? "border-left:3px solid var(--brand)" : ""}">
        <div style="width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:4px;
                    background:${unread ? "var(--brand)" : "var(--border)"}"></div>
        <div style="flex:1">
          <div style="font-weight:${unread ? "700" : "500"};font-size:13px">${type}</div>
          <div style="font-size:12px;color:var(--text-2);margin-top:3px">${esc(n.message || n.body || "—")}</div>
          <div style="font-size:11px;color:var(--text-3);margin-top:4px">${formatRelativeDate(n.created_at)}</div>
        </div>
        ${unread ? `<button class="btn-sm" onclick="NotificationsPage.markOne(${n.id})"><i class="fas fa-check"></i></button>` : ""}
      </div>`;
    }).join("");
  }

  async function markOne(id) {
    try {
      await notificationService.markRead(id);
      await _load();
      _updateTopbarDot();
    } catch (err) { showToast(formatApiError(err)); }
  }

  async function markAll() {
    try {
      await notificationService.markAllRead();
      showToast(L.btn.markAllRead + " ✓", "success");
      await _load();
      _updateTopbarDot();
    } catch (err) { showToast(formatApiError(err)); }
  }

  function _updateTopbarDot() {
    const unread = _notifs.filter(n => !n.read_at).length;
    document.querySelectorAll(".notif-dot").forEach(d => d.style.display = unread > 0 ? "block" : "none");
  }

  return { init, render, markOne, markAll };
})();

// ══════════════════════════════════════════════════════════════
// MOVEMENTS
// ══════════════════════════════════════════════════════════════
const MovementsPage = (() => {
  let _movements = [];

  async function init() { await render(); }

  async function render() {
    const el = document.getElementById("view-movements");
    if (!el) return;
    el.innerHTML = `
      <div class="page-header"><h1>${L.page.movements.title}</h1><p>${L.page.movements.sub}</p></div>
      <div class="filter-bar">
        <select class="filter-select" id="mov-type" onchange="MovementsPage.search()">
          <option value="">Entrées et sorties</option>
          <option value="in">Entrées</option>
          <option value="out">Sorties</option>
        </select>
        <select class="filter-select" id="mov-reason" onchange="MovementsPage.search()">
          <option value="">Toutes les raisons</option>
          ${Object.entries(L.movementReason).map(([k,v])=>`<option value="${k}">${v}</option>`).join("")}
        </select>
      </div>
      <div class="table-card">
        <div class="table-card-header">
          <div class="table-card-title">Mouvements de stock</div>
          <div class="table-card-sub" id="mov-sub"></div>
        </div>
        <div style="overflow-x:auto">
          <table>
            <thead><tr>
              <th>#</th><th>Produit</th><th>Type</th>
              <th>Quantité</th><th>Raison</th><th>Référence</th><th>Par</th><th>Date</th>
            </tr></thead>
            <tbody id="mov-tbody">
              <tr><td colspan="8"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
    await _load();
  }

  async function _load() {
    try {
      _movements = await stockService.movements();
      _render(_movements);
    } catch {
      document.getElementById("mov-tbody").innerHTML =
        `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-circle-exclamation"></i><p>${L.errors.load}</p></div></td></tr>`;
    }
  }

  function search() {
    const type   = document.getElementById("mov-type")?.value   || "";
    const reason = document.getElementById("mov-reason")?.value || "";
    _render(_movements.filter(m =>
      (!type   || m.type === type) &&
      (!reason || m.reason === reason || m.movement_reason === reason)
    ));
  }

  function _render(list) {
    const sub = document.getElementById("mov-sub");
    if (sub) sub.textContent = `${list.length} mouvement(s)`;
    const tbody = document.getElementById("mov-tbody");
    if (!tbody) return;
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><i class="fas fa-chart-line"></i><p>${L.empty.movements}</p></div></td></tr>`;
      return;
    }
    tbody.innerHTML = list.map(m => {
      const reason = m.movement_reason || m.reason || "";
      const ref    = m.reference_type ? `${m.reference_type} #${m.reference_id || ""}` : "—";
      return `<tr>
        <td style="font-size:12px;color:var(--text-3)">#${m.id}</td>
        <td style="font-weight:600;font-size:13px">${esc(m.component_name || m.product_name || "—")}</td>
        <td>${movementTypeBadge(m.type)}</td>
        <td style="font-weight:700;font-size:14px;${m.type === "in" ? "color:var(--green)" : "color:var(--red)"}">${m.type === "in" ? "+" : "−"}${m.quantity ?? "—"}</td>
        <td style="font-size:12px">${L.movementReason[reason] || esc(reason) || "—"}</td>
        <td style="font-size:12px;color:var(--text-3)">${esc(ref)}</td>
        <td style="font-size:12px">${esc(m.created_by_name || m.user_name || "—")}</td>
        <td style="font-size:12px;color:var(--text-3)">${formatDatetime(m.created_at)}</td>
      </tr>`;
    }).join("");
  }

  return { init, render, search };
})();
