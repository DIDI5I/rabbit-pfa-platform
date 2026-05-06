/* ============================================================
   formatters.js — Rabbit B2B MRO Platform
   Fonctions de formatage pour l'affichage.
   Utilise L (labels.js) pour les étiquettes françaises.
   ============================================================ */

// ── Monnaie ────────────────────────────────────────────────────
function formatMAD(val, fallback = "—") {
  if (val == null || val === "") return fallback;
  return Number(val).toLocaleString("fr-MA", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD";
}

function formatNumber(val, fallback = "—") {
  if (val == null) return fallback;
  return Number(val).toLocaleString("fr-FR");
}

// ── Dates ──────────────────────────────────────────────────────
function formatDate(str, fallback = "—") {
  if (!str) return fallback;
  return new Date(str).toLocaleDateString("fr-FR");
}

function formatDatetime(str, fallback = "—") {
  if (!str) return fallback;
  return new Date(str).toLocaleString("fr-FR");
}

function formatRelativeDate(str) {
  if (!str) return "—";
  const diff = Date.now() - new Date(str).getTime();
  const m = Math.floor(diff / 60000);
  if (m < 1)   return "À l'instant";
  if (m < 60)  return `Il y a ${m} min`;
  const h = Math.floor(m / 60);
  if (h < 24)  return `Il y a ${h} h`;
  const d = Math.floor(h / 24);
  if (d < 7)   return `Il y a ${d} j`;
  return formatDate(str);
}

// ── Catégorie ──────────────────────────────────────────────────
function categoryLabel(cat) {
  return L.categories[cat] || esc(cat) || "—";
}

// ── Stock status badge HTML ─────────────────────────────────────
function stockStatusBadge(status) {
  const map = {
    OK:           { cls: "stock-ok",  label: L.stockStatus.OK },
    LOW_STOCK:    { cls: "stock-low", label: L.stockStatus.LOW_STOCK },
    OUT_OF_STOCK: { cls: "stock-out", label: L.stockStatus.OUT_OF_STOCK },
  };
  const s = map[status] || map.OK;
  return `<span class="stock-badge ${s.cls}"><i class="fas fa-circle" style="font-size:5px"></i>${s.label}</span>`;
}

// ── Disponibilité catalogue ────────────────────────────────────
function availabilityBadge(avail) {
  const map = {
    AVAILABLE:        { cls: "stock-ok",  label: L.stockStatus.AVAILABLE },
    LOW_AVAILABILITY: { cls: "stock-low", label: L.stockStatus.LOW_AVAILABILITY },
    OUT_OF_STOCK:     { cls: "stock-out", label: L.stockStatus.OUT_OF_STOCK },
  };
  const s = map[avail] || map.AVAILABLE;
  return `<span class="stock-badge ${s.cls}"><i class="fas fa-circle" style="font-size:5px"></i>${s.label}</span>`;
}

// ── Stock depuis produit (calcul local) ────────────────────────
function stockBadgeFromProduct(p) {
  const qty = p.current_stock ?? p.stock_qty ?? null;
  const thr = p.low_stock_threshold ?? 0;
  let key = "OK";
  if (qty !== null && qty <= 0)        key = "OUT_OF_STOCK";
  else if (qty !== null && qty <= thr) key = "LOW_STOCK";
  return stockStatusBadge(key);
}

// ── Jauge de stock ─────────────────────────────────────────────
function stockGauge(qty, thr) {
  if (qty == null) return "";
  const max = Math.max((qty || 0) * 1.5, (thr || 0) * 3, 10);
  const pct = Math.min(100, Math.round(((qty || 0) / max) * 100));
  const col = qty <= 0 ? "var(--red)" : qty <= (thr || 0) ? "var(--amber)" : "var(--green)";
  return `<div class="stock-gauge"><div class="stock-gauge-fill" style="width:${pct}%;background:${col}"></div></div>`;
}

// ── RFQ badge ──────────────────────────────────────────────────
const RFQ_BADGE_MAP = {
  draft:     { cls: "badge-draft",    label: "" },
  open:      { cls: "badge-open",     label: "" },
  quoted:    { cls: "badge-quoted",   label: "" },
  accepted:  { cls: "badge-accepted", label: "" },
  rejected:  { cls: "badge-rejected", label: "" },
  expired:   { cls: "badge-expired",  label: "" },
  cancelled: { cls: "badge-expired",  label: "" },
};

function rfqBadge(status) {
  const label = L.rfqStatus[status] || esc(status);
  const map   = RFQ_BADGE_MAP[status] || { cls: "badge-draft" };
  return `<span class="badge ${map.cls}">${label}</span>`;
}

// ── Priority badge ─────────────────────────────────────────────
function priorityBadge(priority) {
  const label = L.priority[priority] || esc(priority) || "—";
  return `<span class="priority-badge priority-${priority}">${label}</span>`;
}

// ── Confidence badge ───────────────────────────────────────────
function confidenceBadge(confidence) {
  const label = L.confidence[confidence] || esc(confidence) || "—";
  return `<span class="confidence-badge confidence-${confidence}">${label}</span>`;
}

// ── Lot status ─────────────────────────────────────────────────
function lotStatusBadge(status) {
  const map = {
    draft:     { cls: "badge-draft",    label: L.lotStatus.draft },
    finalized: { cls: "badge-accepted", label: L.lotStatus.finalized },
    cancelled: { cls: "badge-rejected", label: L.lotStatus.cancelled },
  };
  const s = map[status] || map.draft;
  return `<span class="badge ${s.cls}">${s.label}</span>`;
}

// ── Promotion status badge ─────────────────────────────────────
function promotionStatusBadge(status) {
  const map = {
    ACTIVE:   { cls: "badge-accepted", label: L.promotionStatus.ACTIVE },
    UPCOMING: { cls: "badge-open",     label: L.promotionStatus.UPCOMING },
    EXPIRED:  { cls: "badge-expired",  label: L.promotionStatus.EXPIRED },
    DISABLED: { cls: "badge-draft",    label: L.promotionStatus.DISABLED },
  };
  const s = map[status] || map.DISABLED;
  return `<span class="badge ${s.cls}">${s.label}</span>`;
}

// ── Review status badge ────────────────────────────────────────
function reviewStatusBadge(status) {
  const map = {
    pending:  { cls: "badge-quoted",   label: L.reviewStatus.pending },
    approved: { cls: "badge-accepted", label: L.reviewStatus.approved },
    rejected: { cls: "badge-rejected", label: L.reviewStatus.rejected },
  };
  const s = map[status] || map.pending;
  return `<span class="badge ${s.cls}">${s.label}</span>`;
}

// ── Order status badge ─────────────────────────────────────────
function orderStatusBadge(status) {
  const map = {
    pending:    { cls: "badge-draft",    label: L.orderStatus.pending },
    processing: { cls: "badge-open",     label: L.orderStatus.processing },
    shipped:    { cls: "badge-quoted",   label: L.orderStatus.shipped },
    delivered:  { cls: "badge-accepted", label: L.orderStatus.delivered },
    cancelled:  { cls: "badge-rejected", label: L.orderStatus.cancelled },
  };
  const s = map[status] || map.pending;
  return `<span class="badge ${s.cls}">${s.label}</span>`;
}

// ── Movement type ──────────────────────────────────────────────
function movementTypeBadge(type) {
  if (type === "in")  return `<span class="mov-in"><i class="fas fa-arrow-down-to-bracket"></i> ${L.movType.in}</span>`;
  if (type === "out") return `<span class="mov-out"><i class="fas fa-arrow-up-from-bracket"></i> ${L.movType.out}</span>`;
  return esc(type);
}

// ── Stars ──────────────────────────────────────────────────────
function renderStars(rating, max = 5) {
  const r = Math.round(Number(rating) || 0);
  return Array.from({ length: max }, (_, i) =>
    `<i class="fas fa-star" style="color:${i < r ? "#F59E0B" : "#E2E8F0"};font-size:13px"></i>`
  ).join("");
}

// ── Phantom badge ──────────────────────────────────────────────
function phantomBadge() {
  return `<span style="display:inline-block;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;background:var(--surface-2);color:var(--text-3);margin-left:5px">fantôme</span>`;
}

// ── Cat pill ───────────────────────────────────────────────────
function catPill(cat) {
  return `<span class="cat-pill">${categoryLabel(cat)}</span>`;
}

// ── Discount label ─────────────────────────────────────────────
function discountLabel(type, value) {
  if (type === "percentage") return `-${value}%`;
  if (value != null)         return `-${formatMAD(value)}`;
  return "—";
}

// ── Forecast model label ───────────────────────────────────────
function modelLabel(model) {
  return L.forecastModel[model] || esc(model) || "—";
}

// ── Data quality flags ─────────────────────────────────────────
function dataFlagPills(flags) {
  if (!Array.isArray(flags) || !flags.length) return "";
  const nice = {
    lumpy_demand_detected:            "Demande groupée",
    outlier_excluded:                 "Pic exclu",
    sparse_demand_history:            "Historique sparse",
    no_demand_history:                "Aucun historique",
    new_product:                      "Nouveau produit",
    lead_time_estimated:              "Délai estimé",
    no_threshold_set:                 "Seuil manquant",
    corrupted_movement_excluded:      "Mouvement corrompu",
    insufficient_history_for_seasonality: "Saisonnalité insuffisante",
    declining_demand_trend:           "Tendance déclin",
  };
  return flags.map(f =>
    `<span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:var(--r-pill);background:var(--surface-2);color:var(--text-2);border:1px solid var(--border);margin:2px 2px 0 0">${nice[f] || esc(f)}</span>`
  ).join("");
}

// ── Reason codes ───────────────────────────────────────────────
function reasonCodePills(codes) {
  if (!Array.isArray(codes) || !codes.length) return "";
  const nice = {
    stock_below_reorder_point:     "Sous seuil ROP",
    critical_spare_min_stock:      "Pièce critique min",
    ved_vital_override:            "VED Vital",
    no_demand_but_low_stock:       "Stock faible sans demande",
    abc_a_class:                   "Classe ABC-A",
    zero_stock_critical_item:      "Rupture critique",
    outlier_demand_event_flagged:  "Pic anormal détecté",
    high_lead_time_risk:           "Délai long",
    demand_declining_possible_obsolescence: "Possible obsolescence",
  };
  return codes.map(c =>
    `<span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:var(--r-pill);background:var(--brand-light);color:var(--brand);border:1px solid var(--brand-mid);margin:2px 2px 0 0">${nice[c] || esc(c)}</span>`
  ).join("");
}
