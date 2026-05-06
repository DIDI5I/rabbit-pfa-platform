/* ============================================================
   config.js — Rabbit B2B MRO Platform
   Source de vérité : rabbit_frontend_api_handoff.md
   IMPORTANT: Ne jamais modifier les fonctions de parsing.
   Toute logique métier (unwrap, formatApiError, constantes)
   est définie ici une seule fois.
   ============================================================ */

const API_BASE_URL = "http://localhost:8888";

// ── apiFetchJson ──────────────────────────────────────────────
async function apiFetchJson(path, options = {}) {
  const { headers = {}, body, ...rest } = options;
  const isFormData = body instanceof FormData;
  const response = await fetch(`${API_BASE_URL}${path}`, {
    credentials: "include",
    headers: {
      ...(isFormData ? {} : { "Content-Type": "application/json" }),
      "Accept": "application/json",
      ...headers,
    },
    ...(body !== undefined ? { body } : {}),
    ...rest,
  });
  const data = await response.json().catch(() => null);
  if (!response.ok) throw data || { error: `Erreur ${response.status}` };
  return data;
}

// ── apiFetchFormData ──────────────────────────────────────────
async function apiFetchFormData(path, formData, method = "POST") {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method, credentials: "include", body: formData,
  });
  const data = await response.json().catch(() => null);
  if (!response.ok) throw data || { error: `Erreur ${response.status}` };
  return data;
}

// ── unwrap ────────────────────────────────────────────────────
function unwrap(response, fallback = null) {
  if (!response) return fallback;
  if (Object.prototype.hasOwnProperty.call(response, "data")) {
    return response.data ?? fallback;
  }
  return response;
}

// ── unwrapItems ───────────────────────────────────────────────
function unwrapItems(response) {
  const data = unwrap(response, {});
  if (Array.isArray(data)) return data;
  if (Array.isArray(data.items)) return data.items;
  return [];
}

// ── unwrapPagination ──────────────────────────────────────────
function unwrapPagination(response) {
  const data = unwrap(response, {});
  return data.pagination || null;
}

// ── formatApiError ────────────────────────────────────────────
function formatApiError(err) {
  if (!err) return "Erreur inconnue.";
  if (err.fields && typeof err.fields === "object") {
    return Object.entries(err.fields)
      .flatMap(([field, messages]) =>
        (Array.isArray(messages) ? messages : [messages]).map(msg => `${field}: ${msg}`)
      )
      .join("\n");
  }
  if (err.error)   return err.error;
  if (err.message) return err.message;
  return "Erreur inconnue.";
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(message, type = "error") {
  let c = document.getElementById("toast-container");
  if (!c) {
    c = document.createElement("div");
    c.id = "toast-container";
    document.body.appendChild(c);
  }
  const t = document.createElement("div");
  t.className = `toast toast-${type}`;
  t.textContent = message;
  c.appendChild(t);
  setTimeout(() => t.remove(), 4200);
}

// ── esc ───────────────────────────────────────────────────────
function esc(str) {
  if (str == null) return "";
  return String(str)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;")
    .replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

// ══════════════════════════════════════════════════════════════
// CONSTANTES MÉTIER — alignées backend, ne pas modifier
// ══════════════════════════════════════════════════════════════

const COMPONENT_CATEGORIES = {
  assembly:     "Assemblage",
  sub_assembly: "Sous-assemblage",
  component:    "Composant",
  raw_material: "Matière première",
};

const STOCK_STATUS = {
  OK:           { label: "Disponible",       cls: "stock-ok"  },
  LOW_STOCK:    { label: "Stock bas",        cls: "stock-low" },
  OUT_OF_STOCK: { label: "Rupture de stock", cls: "stock-out" },
};

const RFQ_STATUS = {
  draft:     { label: "Brouillon",  cls: "badge-draft"    },
  open:      { label: "Ouverte",    cls: "badge-open"     },
  quoted:    { label: "Devis reçu", cls: "badge-quoted"   },
  accepted:  { label: "Acceptée",   cls: "badge-accepted" },
  rejected:  { label: "Rejetée",    cls: "badge-rejected" },
  expired:   { label: "Expirée",    cls: "badge-expired"  },
  cancelled: { label: "Annulée",    cls: "badge-expired"  },
};

const PURCHASE_LOT_STATUS = {
  draft:     "Brouillon",
  finalized: "Finalisé",
  cancelled: "Annulé",
};

const AVAILABILITY_LABELS = {
  AVAILABLE:        "Disponible",
  LOW_AVAILABILITY: "Disponibilité limitée",
  OUT_OF_STOCK:     "Rupture de stock",
};

const PRIORITY_LABELS = {
  CRITICAL: "Critique",
  HIGH:     "Élevée",
  MEDIUM:   "Moyenne",
  LOW:      "Faible",
  NONE:     "Aucune",
};

const CONFIDENCE_LABELS = {
  HIGH:          "Haute confiance",
  MEDIUM:        "Confiance moyenne",
  LOW:           "Faible confiance",
  ESTIMATE_ONLY: "Estimation seulement",
};

const MODEL_LABELS = {
  criticality_only:             "Criticité uniquement",
  threshold_only:               "Seuil uniquement",
  moving_average:               "Moyenne mobile",
  simple_exponential_smoothing: "Lissage exponentiel simple",
  croston_sba:                  "Croston / SBA",
  regression_trend:             "Tendance linéaire",
  seasonal_index:               "Indice saisonnier",
};

const PROMOTION_STATUS_LABELS = {
  ACTIVE:   "Active",
  UPCOMING: "À venir",
  EXPIRED:  "Expirée",
  DISABLED: "Désactivée",
};

const RELATION_TYPES = {
  technical_structure:    "Structure technique",
  replacement_part:       "Pièce de remplacement",
  compatible_alternative: "Alternative compatible",
  spare_part:             "Pièce de rechange",
  accessory:              "Accessoire",
  related_product:        "Produit associé",
};

const RECOMMENDATION_SECTION_LABELS = {
  explicit_relations:      "Relations explicites",
  compatible_alternatives: "Alternatives compatibles",
  accessories:             "Accessoires",
  spare_parts:             "Pièces de rechange",
  same_category:           "Même catégorie",
  same_supplier:           "Même fournisseur",
};

const ORDER_STATUS = {
  pending:    { label: "En attente",    cls: "badge-draft"    },
  processing: { label: "En traitement", cls: "badge-open"     },
  shipped:    { label: "Expédiée",      cls: "badge-quoted"   },
  delivered:  { label: "Livrée",        cls: "badge-accepted" },
  cancelled:  { label: "Annulée",       cls: "badge-rejected" },
};

const NOTIFICATION_TYPE_LABELS = {
  RFQ_ASSIGNED:                    "RFQ assignée",
  RFQ_QUOTED:                      "Devis reçu",
  RFQ_ACCEPTED_BY_OWNER:           "Devis accepté",
  RFQ_REJECTED_BY_OWNER:           "Devis rejeté",
  PURCHASE_LOT_NEEDS_FINALIZATION: "Lot d'achat à finaliser",
  PURCHASE_LOT_FINALIZED:          "Lot d'achat finalisé",
  LOW_STOCK_ALERT:                 "Stock faible",
  OUT_OF_STOCK_ALERT:              "Rupture de stock",
  ORDER_CREATED:                   "Nouvelle commande",
  ORDER_STATUS_CHANGED:            "Statut de commande modifié",
};

const MOVEMENT_REASONS = {
  INITIAL_STOCK:           "Stock initial",
  RFQ_ACCEPTED:            "RFQ acceptée",
  PURCHASE_RECEIVED:       "Réception fournisseur",
  SALE:                    "Vente client",
  MANUAL_ADJUSTMENT:       "Ajustement manuel",
  RETURN:                  "Retour",
  DAMAGED:                 "Stock endommagé",
  CANCELLED_ORDER_RESTORE: "Restauration après annulation de commande",
};

// ── Shared UI helpers ─────────────────────────────────────────

function categoryLabel(cat) {
  return COMPONENT_CATEGORIES[cat] || esc(cat) || "—";
}

function stockBadgeFromStatus(status) {
  const s = STOCK_STATUS[status] || STOCK_STATUS.OK;
  return `<span class="stock-badge ${s.cls}"><i class="fas fa-circle" style="font-size:5px"></i>${s.label}</span>`;
}

function stockBadgeFromProduct(p) {
  const qty = p.current_stock ?? p.stock_qty ?? null;
  const thr = p.low_stock_threshold ?? 0;
  let key = "OK";
  if (qty !== null && qty <= 0)    key = "OUT_OF_STOCK";
  else if (qty !== null && qty <= thr) key = "LOW_STOCK";
  return stockBadgeFromStatus(key);
}

function rfqBadge(status) {
  const s = RFQ_STATUS[status];
  if (!s) return `<span class="badge badge-draft">${esc(status)}</span>`;
  return `<span class="badge ${s.cls}">${s.label}</span>`;
}

function priorityBadge(priority) {
  const label = PRIORITY_LABELS[priority] || priority || "—";
  return `<span class="priority-badge priority-${priority}">${label}</span>`;
}

function confidenceBadge(confidence) {
  const label = CONFIDENCE_LABELS[confidence] || confidence || "—";
  return `<span class="confidence-badge confidence-${confidence}">${label}</span>`;
}

function stockGauge(qty, thr) {
  if (qty == null) return "";
  const max = Math.max(qty * 1.5, thr * 3, 10);
  const pct = Math.min(100, Math.round((qty / max) * 100));
  const col = qty <= 0 ? "var(--red)" : qty <= thr ? "var(--amber)" : "var(--green)";
  return `<div class="stock-gauge"><div class="stock-gauge-fill" style="width:${pct}%;background:${col}"></div></div>`;
}

function phantomBadge() {
  return `<span style="display:inline-block;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;background:var(--surface-2);color:var(--text-3);margin-left:5px">fantôme</span>`;
}

function formatMAD(val) {
  if (val == null || val === "") return "—";
  return Number(val).toLocaleString("fr-MA") + " MAD";
}

function formatDate(str) {
  if (!str) return "—";
  return new Date(str).toLocaleDateString("fr-FR");
}

function setEl(id, val)  { const e = document.getElementById(id); if (e) e.textContent = val; }
function setVal(id, val) { const e = document.getElementById(id); if (e) e.value = val; }
function setBadge(id, n) {
  const e = document.getElementById(id);
  if (!e) return;
  e.textContent = n;
  e.style.display = n > 0 ? "flex" : "none";
}

function emptyRow(cols, icon, text) {
  return `<tr><td colspan="${cols}">
    <div class="empty-state">
      <i class="fas ${icon}"></i>
      <p>${text}</p>
    </div>
  </td></tr>`;
}

// ── Navigation bubble (chatbot contract) ──────────────────────
function showNavBubble(previousPageLink, bubbleText, durationMs = 4000) {
  const existing = document.getElementById("nav-bubble");
  if (existing) existing.remove();

  const bubble = document.createElement("a");
  bubble.id = "nav-bubble";
  bubble.className = "nav-bubble";
  bubble.href = previousPageLink || "#";
  bubble.innerHTML = `<i class="fas fa-arrow-left"></i> ${esc(bubbleText || "Retour à la page précédente")}`;
  document.body.appendChild(bubble);

  setTimeout(() => {
    bubble.style.transition = "opacity .3s";
    bubble.style.opacity = "0";
    setTimeout(() => bubble.remove(), 320);
  }, durationMs);
}
