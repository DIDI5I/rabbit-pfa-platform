/* ============================================================
   services.js — Rabbit B2B MRO Platform
   Couche service : appelle l'API et retourne les données
   déjà déballées (unwrap). Ne contient aucune logique
   d'affichage.
   ============================================================ */

// ── Auth ───────────────────────────────────────────────────────
const authService = {
  async login(email, password) {
    const data = await apiFetchJson("/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    });
    return unwrap(data)?.user || unwrap(data);
  },
  async logout() {
    try { await apiFetchJson("/logout", { method: "POST" }); } catch {}
  },
  async currentUser() {
    const data = await apiFetchJson("/user");
    return unwrap(data);
  },
};

// ── Dashboard ──────────────────────────────────────────────────
const dashboardService = {
  async summary() {
    const data = await apiFetchJson("/dashboard/summary");
    return unwrap(data) || {};
  },
  async stockSummary() {
    // TODO: vérifier endpoint exact
    const data = await apiFetchJson("/stock/summary");
    return unwrap(data) || {};
  },
};

// ── Products (owner) ───────────────────────────────────────────
const productService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/products${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/products/${id}`);
    return unwrap(data);
  },
  async create(payload) {
    const data = await apiFetchJson("/products", {
      method: "POST",
      body: JSON.stringify(payload),
    });
    return unwrap(data);
  },
  async update(id, payload) {
    const data = await apiFetchJson(`/products/${id}`, {
      method: "PATCH",
      body: JSON.stringify(payload),
    });
    return unwrap(data);
  },
  async delete(id) {
    await apiFetchJson(`/products/${id}`, { method: "DELETE" });
  },
  async dependencies(id) {
    const data = await apiFetchJson(`/products/${id}/dependencies`);
    return unwrapItems(data);
  },
  async costRollup(id) {
    const data = await apiFetchJson(`/chatbot/ask`, {
      method: "POST",
      body: JSON.stringify({ message: `cost rollup for product ${id}` }),
    });
    return unwrap(data);
  },
};

// ── Catalogue (client-safe) ────────────────────────────────────
const catalogService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/catalog/products${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/catalog/products/${id}`);
    return unwrap(data);
  },
  async relations(id) {
    const data = await apiFetchJson(`/catalog/products/${id}/relations`);
    return unwrap(data) || [];
  },
  async promotions(id) {
    const data = await apiFetchJson(`/catalog/products/${id}/promotions`);
    return unwrapItems(data);
  },
  async reviews(id) {
    const data = await apiFetchJson(`/catalog/products/${id}/reviews`);
    return unwrapItems(data);
  },
  async ratingSummary(id) {
    const data = await apiFetchJson(`/catalog/products/${id}/rating-summary`);
    return unwrap(data) || {};
  },
  async activePromotions() {
    const data = await apiFetchJson("/catalog/promotions/active");
    return unwrapItems(data);
  },
};

// ── Inventory ──────────────────────────────────────────────────
const inventoryService = {
  async summary(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/inventory${qs ? "?" + qs : ""}`);
    return unwrap(data) || {};
  },
  async alerts() {
    const data = await apiFetchJson("/inventory/alerts");
    return unwrapItems(data);
  },
  async stockForProduct(productId) {
    const data = await apiFetchJson(`/stock/${productId}`);
    return unwrap(data);
  },
};

// ── Stock movements ────────────────────────────────────────────
const stockService = {
  async movements(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/stock/movements${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async movementsForProduct(productId) {
    const data = await apiFetchJson(`/stock/${productId}/movements`);
    return unwrapItems(data);
  },
  async reorderRecommendations(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/stock/intelligence/reorder-recommendations${qs ? "?" + qs : ""}`);
    return unwrap(data) || {};
  },
};

// ── Purchase lots ──────────────────────────────────────────────
const purchaseLotService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/purchase-lots${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/purchase-lots/${id}`);
    return unwrap(data);
  },
  async forProduct(productId) {
    const data = await apiFetchJson(`/purchase-lots/product/${productId}`);
    return unwrapItems(data);
  },
  async finalize(id) {
    const data = await apiFetchJson(`/purchase-lots/${id}/finalize`, { method: "POST" });
    return unwrap(data);
  },
};

// ── RFQs ───────────────────────────────────────────────────────
const rfqService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/rfqs${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/rfqs/${id}`);
    return unwrap(data);
  },
  async actions(id) {
    try {
      const data = await apiFetchJson(`/rfqs/${id}/actions`);
      const a = unwrap(data);
      return Array.isArray(a) ? a : [];
    } catch { return []; }
  },
  async submitQuote(id, payload) {
    const data = await apiFetchJson(`/rfqs/${id}/quote`, {
      method: "PATCH",
      body: JSON.stringify(payload),
    });
    return unwrap(data);
  },
};

// ── Promotions ─────────────────────────────────────────────────
const promotionService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/promotions${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/promotions/${id}`);
    return unwrap(data);
  },
  async products(id) {
    const data = await apiFetchJson(`/promotions/${id}/products`);
    return unwrapItems(data);
  },
};

// ── Reviews ────────────────────────────────────────────────────
const reviewService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/reviews${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/reviews/${id}`);
    return unwrap(data);
  },
  async updateStatus(id, status) {
    const data = await apiFetchJson(`/reviews/${id}/status`, {
      method: "PATCH",
      body: JSON.stringify({ status }),
    });
    return unwrap(data);
  },
};

// ── Notifications ──────────────────────────────────────────────
const notificationService = {
  async list() {
    const data = await apiFetchJson("/notifications");
    return unwrapItems(data);
  },
  async unreadCount() {
    try {
      const items = await notificationService.list();
      return items.filter(n => !n.read_at).length;
    } catch { return 0; }
  },
  async markRead(id) {
    await apiFetchJson(`/notifications/${id}/read`, { method: "PATCH" });
  },
  async markAllRead() {
    await apiFetchJson("/notifications/read-all", { method: "PATCH" });
  },
};

// ── Suppliers ──────────────────────────────────────────────────
const supplierService = {
  async list() {
    const data = await apiFetchJson("/suppliers");
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/suppliers/${id}`);
    return unwrap(data);
  },
};

// ── Orders ─────────────────────────────────────────────────────
const orderService = {
  async list(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const data = await apiFetchJson(`/orders${qs ? "?" + qs : ""}`);
    return unwrapItems(data);
  },
  async get(id) {
    const data = await apiFetchJson(`/orders/${id}`);
    return unwrap(data);
  },
};

// ── Chatbot ────────────────────────────────────────────────────
const chatbotService = {
  async ask(message) {
    const data = await apiFetchJson("/chatbot/ask", {
      method: "POST",
      body: JSON.stringify({ message }),
    });
    return unwrap(data) || {};
  },
};
