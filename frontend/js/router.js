/* ============================================================
   router.js — Rabbit B2B MRO Platform
   Routeur SPA basé sur le hash (#view).
   Gère : affichage de vue, titre topbar, état sidebar,
          chargement paresseux des données de page.
   ============================================================ */

const Router = (() => {
  let _routes    = {};   // { viewId: { title, sub, onEnter } }
  let _current   = null;
  let _container = null;

  // ── Enregistre les routes ────────────────────────────────────
  function register(viewId, { title, sub, onEnter }) {
    _routes[viewId] = { title, sub, onEnter };
  }

  // ── Init ─────────────────────────────────────────────────────
  function init(containerSelector = ".dash-content") {
    _container = document.querySelector(containerSelector);
    window.addEventListener("hashchange", _handleHash);
    _handleHash();
  }

  // ── Navigue vers un viewId ───────────────────────────────────
  function navigate(viewId) {
    window.location.hash = viewId;
  }

  // ── Handler interne ──────────────────────────────────────────
  function _handleHash() {
    const hash   = window.location.hash.replace("#", "") || "dashboard";
    const viewId = _routes[hash] ? hash : "dashboard";
    _activate(viewId);
  }

  function _activate(viewId) {
    if (_current === viewId) return;
    _current = viewId;

    // Masque toutes les vues
    document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));

    // Affiche la vue cible
    const target = document.getElementById(`view-${viewId}`);
    if (target) target.classList.add("active");

    // Met à jour le topbar
    const route = _routes[viewId] || {};
    const titleEl = document.getElementById("topbar-title");
    const subEl   = document.getElementById("topbar-sub");
    if (titleEl) titleEl.textContent = route.title || "";
    if (subEl)   subEl.textContent   = route.sub   || "";

    // Met à jour la sidebar
    document.querySelectorAll(".sidebar-item").forEach(el => {
      el.classList.toggle("active", el.dataset.view === viewId);
    });

    // Callback de page
    if (route.onEnter) route.onEnter();
  }

  // ── Lien sidebar ─────────────────────────────────────────────
  function bindSidebar() {
    document.querySelectorAll(".sidebar-item[data-view]").forEach(el => {
      el.addEventListener("click", () => navigate(el.dataset.view));
    });
  }

  return { register, init, navigate, bindSidebar };
})();
