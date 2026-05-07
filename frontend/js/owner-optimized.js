/* ============================================================
   owner.js — Rabbit B2B MRO Platform — Dashboard Administration
   Version optimisée — Utilise les modules partagés
   Dépend de config.js + auth.js + utils.js + dashboard-shared.js
   ============================================================ */

let allProducts = [], allRelations = [], allRFQs = [];
let allMovements = [], allSuppliers = [], allAlerts = [];
let allInventory = [], allLots = [];
let currentEditId = null;

// Instance du dashboard
let dashboard;

// ── Init ──────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", async () => {
  dashboard = new OwnerDashboard();
  const initialized = await dashboard.init();
  if (!initialized) return;

  await Promise.all([
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
  renderDashboardWidgets();
  renderStockIntelligencePlaceholder();
});

// ── Classe OwnerDashboard ─────────────────────────────────────
class OwnerDashboard extends DashboardBase {
  constructor() {
    super('owner');
  }

  getViewTitles() {
    return {
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
    };
  }

  async loadCommonData() {
    await Promise.all([
      loadProducts(),
      loadInventory(),
      loadAlerts(),
      loadRFQs(),
      loadSuppliers(),
      loadRelations(),
      loadNotifications(),
      loadOrders(),
    ]);
  }
}

// ── Fonctions de chargement de données ────────────────────────
// (Le reste du code owner.js original ici, mais en utilisant les utilitaires partagés)

// Fonction utilitaire pour remplacer setEl
function updateElement(id, value) {
  setEl(id, value);
}

// ... (le reste du code owner.js avec les appels à updateElement au lieu de setEl direct)