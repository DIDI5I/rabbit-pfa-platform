// dashboard-shared.js — Fonctionnalités communes aux dashboards
// Réduction de duplication entre owner.js, client.js, supplier.js

/**
 * Classe de base pour les dashboards
 */
class DashboardBase {
  constructor(role) {
    this.role = role;
    this.user = null;
    this.initials = '';
  }

  /**
   * Initialise le dashboard après authentification
   */
  async init() {
    this.user = await checkAuth(this.role);
    if (!this.user) return false;

    this.initials = getInitials(this.user.name || this.role.toUpperCase().slice(0, 2));
    this.setupUserInterface();
    return true;
  }

  /**
   * Configure l'interface utilisateur commune
   */
  setupUserInterface() {
    setEl("topbar-avatar", this.initials);
    setEl("sidebar-avatar", this.initials);
    if (this.user.name) setEl("sidebar-username", this.user.name);
  }

  /**
   * Change de vue dans le dashboard
   * @param {string} viewName - Nom de la vue
   */
  showView(viewName) {
    // Cache toutes les vues
    document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));
    // Affiche la vue demandée
    const viewEl = getEl(`view-${viewName}`);
    if (viewEl) {
      viewEl.classList.add("active");
      // Met à jour le titre si nécessaire
      this.updateTopbarTitle(viewName);
    }
  }

  /**
   * Met à jour le titre de la topbar
   * @param {string} viewName - Nom de la vue
   */
  updateTopbarTitle(viewName) {
    const titles = this.getViewTitles();
    setEl("topbar-title", titles[viewName] || viewName);
  }

  /**
   * Retourne les titres des vues (à surcharger)
   */
  getViewTitles() {
    return {};
  }

  /**
   * Charge les données communes
   */
  async loadCommonData() {
    // À implémenter dans les classes dérivées
  }
}

/**
 * Gestionnaire de modales partagé
 */
class ModalManager {
  static open(modalId) {
    const modal = getEl(modalId);
    if (modal) {
      modal.style.display = 'flex';
      modal.classList.add('active');
    }
  }

  static close(modalId) {
    const modal = getEl(modalId);
    if (modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
  }

  static closeAll() {
    document.querySelectorAll('.modal').forEach(modal => {
      modal.style.display = 'none';
      modal.classList.remove('active');
    });
  }
}

/**
 * Gestionnaire de formulaires partagé
 */
class FormManager {
  static getFormData(formId) {
    const form = getEl(formId);
    if (!form) return {};

    const data = {};
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
      if (input.type === 'checkbox') {
        data[input.name] = input.checked;
      } else if (input.type === 'file') {
        data[input.name] = input.files[0];
      } else {
        data[input.name] = input.value;
      }
    });
    return data;
  }

  static setFormData(formId, data) {
    const form = getEl(formId);
    if (!form || !data) return;

    Object.keys(data).forEach(key => {
      const input = form.querySelector(`[name="${key}"]`);
      if (input) {
        if (input.type === 'checkbox') {
          input.checked = data[key];
        } else {
          input.value = data[key];
        }
      }
    });
  }

  static resetForm(formId) {
    const form = getEl(formId);
    if (form) form.reset();
  }

  static validateForm(formId) {
    const form = getEl(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
      if (!input.value.trim()) {
        input.classList.add('error');
        isValid = false;
      } else {
        input.classList.remove('error');
      }
    });

    return isValid;
  }
}

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { DashboardBase, ModalManager, FormManager };
}