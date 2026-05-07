/* ============================================================
   config.js — Rabbit B2B MRO Platform
   Source de vérité : rabbit_frontend_api_handoff.md
   ============================================================ */

const API_BASE_URL = "http://localhost:8888";
const USE_MOCK_DATA = false;

function apiUrl(path) {
  return `${API_BASE_URL.replace(/\/$/, "")}/${String(path).replace(/^\//, "")}`;
}
// ── apiFetchJson : retourne la réponse backend complète { message, data } ─
// Utiliser unwrap() / unwrapItems() pour extraire les données.
async function apiFetchJson(path, options = {}) {
  const { headers = {}, body, ...rest } = options;

  const isFormData = body instanceof FormData;

  const response = await fetch(apiUrl(path), {
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

// ── apiFetchFormData : pour upload image, pas de Content-Type ─
async function apiFetchFormData(path, formData, method = "POST") {
  const response = await fetch(apiUrl(path), {
    method,
    credentials: "include",
    body: formData,
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) throw data || { error: `Erreur ${response.status}` };

  return data;
}

// ── unwrap : extrait .data de { message, data } ───────────────
// Gère aussi les anciens endpoints qui retournent un tableau direct.
function unwrap(response, fallback = null) {
  if (!response) return fallback;
  if (Object.prototype.hasOwnProperty.call(response, "data")) {
    return response.data ?? fallback;
  }
  return response;
}

// ── unwrapItems : extrait un tableau depuis une liste paginée ─
function unwrapItems(response) {
  const data = unwrap(response, {});
  if (Array.isArray(data)) return data;
  if (Array.isArray(data.items)) return data.items;
  return [];
}

// ── formatApiError : message lisible depuis erreur backend ────
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
    c = document.createElement("div"); c.id = "toast-container";
    Object.assign(c.style, { position:"fixed", bottom:"24px", right:"24px", zIndex:"9999", display:"flex", flexDirection:"column", gap:"8px" });
    document.body.appendChild(c);
  }
  const col = { error:["#fef2f2","#fecaca","#b91c1c"], success:["#f0fdf4","#bbf7d0","#15803d"], warn:["#fffbeb","#fde68a","#92400e"] }[type] || ["#fef2f2","#fecaca","#b91c1c"];
  const t = document.createElement("div");
  Object.assign(t.style, { padding:"12px 16px", background:col[0], border:`1px solid ${col[1]}`, borderRadius:"10px", fontSize:"13px", color:col[2], fontWeight:"600", fontFamily:"Manrope,sans-serif", boxShadow:"0 4px 12px rgba(0,0,0,.08)", maxWidth:"340px" });
  t.textContent = message; c.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

// ══════════════════════════════════════════════════════════════
// CONSTANTES MÉTIER
// ══════════════════════════════════════════════════════════════

// Catégories composant — table backend : components
const COMPONENT_CATEGORIES = {
  assembly:     "Assemblage",
  sub_assembly: "Sous-assemblage",
  component:    "Composant",
  raw_material: "Matière première",
};

// Statuts stock — retournés par /stock/{id} et /inventory
const STOCK_STATUS = {
  OK:           { label:"Disponible",       cls:"stock-ok"  },
  LOW_STOCK:    { label:"Stock bas",        cls:"stock-low" },
  OUT_OF_STOCK: { label:"Rupture de stock", cls:"stock-out" },
};

// Statuts RFQ — inclus "cancelled" (handoff section 4)
const RFQ_STATUS = {
  draft:     { label:"Brouillon",  cls:"badge-draft"    },
  open:      { label:"Ouverte",    cls:"badge-open"     },
  quoted:    { label:"Devis reçu", cls:"badge-quoted"   },
  accepted:  { label:"Acceptée",   cls:"badge-accepted" },
  rejected:  { label:"Rejetée",    cls:"badge-rejected" },
  expired:   { label:"Expirée",    cls:"badge-expired"  },
  cancelled: { label:"Annulée",    cls:"badge-expired"  },
};

// Statuts lots d'achat
const PURCHASE_LOT_STATUS = {
  draft:     "Brouillon",
  finalized: "Finalisé",
  cancelled: "Annulé",
};

// ── Disponibilité catalogue client (/catalog/products) ────────
const AVAILABILITY_LABELS = {
  AVAILABLE:        "Disponible",
  LOW_AVAILABILITY: "Disponibilité limitée",
  OUT_OF_STOCK:     "Rupture de stock",
};

// ── Stock intelligence — priorité, confiance, modèles ─────────
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
  croston_sba:                  "Croston/SBA",
  regression_trend:             "Tendance linéaire",
  seasonal_index:               "Indice saisonnier",
};

// ── Statuts promotions (calculés côté backend) ────────────────
const PROMOTION_STATUS_LABELS = {
  ACTIVE:    "Active",
  UPCOMING:  "À venir",
  EXPIRED:   "Expirée",
  DISABLED:  "Désactivée",
};

// ── Types de relation produit (alignés avec backend /dependencies)
const RELATION_TYPES = {
  technical_structure:    "Structure technique",
  replacement_part:       "Pièce de remplacement",
  compatible_alternative: "Alternative compatible",
  spare_part:             "Pièce de rechange",
  accessory:              "Accessoire",
  related_product:        "Produit associé",
};

// Labels sections recommandations (/products/{id}/recommendations)
const RECOMMENDATION_SECTION_LABELS = {
  explicit_relations:     "Relations explicites",
  compatible_alternatives:"Alternatives compatibles",
  accessories:            "Accessoires",
  spare_parts:            "Pièces de rechange",
  same_category:          "Même catégorie",
  same_supplier:          "Même fournisseur",
};

// Statuts commandes
const ORDER_STATUS = {
  pending:    { label: "En attente",    cls: "badge-draft"    },
  processing: { label: "En traitement", cls: "badge-open"     },
  shipped:    { label: "Expédiée",      cls: "badge-quoted"   },
  delivered:  { label: "Livrée",        cls: "badge-accepted" },
  cancelled:  { label: "Annulée",       cls: "badge-rejected" },
};

// Types de notifications
const NOTIFICATION_TYPE_LABELS = {
  RFQ_ASSIGNED:                   "RFQ assignée",
  RFQ_QUOTED:                     "Devis reçu",
  RFQ_ACCEPTED_BY_OWNER:          "Devis accepté",
  RFQ_REJECTED_BY_OWNER:          "Devis rejeté",
  PURCHASE_LOT_NEEDS_FINALIZATION:"Lot d'achat à finaliser",
  PURCHASE_LOT_FINALIZED:         "Lot d'achat finalisé",
  LOW_STOCK_ALERT:                "Stock faible",
  OUT_OF_STOCK_ALERT:             "Rupture de stock",
  ORDER_CREATED:                  "Nouvelle commande",
  ORDER_STATUS_CHANGED:           "Statut de commande modifié",
};

// Raisons de mouvement de stock
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

// ══════════════════════════════════════════════════════════════
// HELPERS UI PARTAGÉS
// ══════════════════════════════════════════════════════════════

function esc(str) {
  if (str == null) return "";
  return String(str).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function categoryLabel(cat) {
  return COMPONENT_CATEGORIES[cat] || esc(cat) || "—";
}

// Badge stock depuis stock_status (/stock/{id} ou /inventory)
function stockBadgeFromStatus(status) {
  const s = STOCK_STATUS[status] || STOCK_STATUS.OK;
  return `<span class="stock-badge ${s.cls}"><i class="fas fa-circle" style="font-size:6px"></i>${s.label}</span>`;
}

// Badge stock calculé localement (fallback depuis liste produits)
// Règle spec : const realStock = product.current_stock ?? product.stock_qty ?? 0
function stockBadgeFromProduct(p) {
  const qty = p.current_stock ?? p.stock_qty ?? null;
  const thr = p.low_stock_threshold ?? 0;
  let key = "OK";
  if (qty !== null && qty <= 0)    key = "OUT_OF_STOCK";
  else if (qty !== null && qty <= thr) key = "LOW_STOCK";
  return stockBadgeFromStatus(key);
}

function phantomBadge() {
  return `<span style="display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;background:#f1f5f9;color:#64748b;margin-left:6px">fantôme</span>`;
}

function rfqBadge(status) {
  const s = RFQ_STATUS[status];
  if (!s) return `<span class="badge badge-draft">${esc(status)}</span>`;
  return `<span class="badge ${s.cls}">${s.label}</span>`;
}

function formatMAD(val) {
  if (val == null || val === "") return "—";
  return Number(val).toLocaleString("fr-MA") + " MAD";
}

function formatDate(str) {
  if (!str) return "—";
  return new Date(str).toLocaleDateString("fr-FR");
}
