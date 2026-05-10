// module-loader.js — Chargeur de modules optimisé
// Réduit la taille des bundles en chargeant seulement ce qui est nécessaire

class ModuleLoader {
  constructor() {
    this.loadedModules = new Set();
    this.modules = {
      utils: '/js/utils.js',
      'dashboard-shared': '/js/dashboard-shared.js',
      auth: '/js/auth.js',
      config: '/js/config.js',
      'image-optimizer': '/js/image-optimizer.js'
    };
  }

  /**
   * Charge un module de manière asynchrone
   * @param {string} moduleName - Nom du module
   * @returns {Promise}
   */
  async loadModule(moduleName) {
    if (this.loadedModules.has(moduleName)) {
      return; // Déjà chargé
    }

    const scriptPath = this.modules[moduleName];
    if (!scriptPath) {
      throw new Error(`Module ${moduleName} non trouvé`);
    }

    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = scriptPath;
      script.onload = () => {
        this.loadedModules.add(moduleName);
        resolve();
      };
      script.onerror = () => reject(new Error(`Erreur chargement ${moduleName}`));
      document.head.appendChild(script);
    });
  }

  /**
   * Charge plusieurs modules en parallèle
   * @param {string[]} moduleNames - Liste des noms de modules
   * @returns {Promise}
   */
  async loadModules(moduleNames) {
    const promises = moduleNames.map(name => this.loadModule(name));
    return Promise.all(promises);
  }

  /**
   * Charge les modules selon la page courante
   * @param {string} pageType - Type de page ('landing', 'auth', 'dashboard')
   * @returns {Promise}
   */
  async loadForPage(pageType) {
    const moduleMap = {
      landing: ['config', 'image-optimizer'],
      auth: ['config', 'auth', 'image-optimizer'],
      dashboard: ['config', 'auth', 'utils', 'dashboard-shared', 'image-optimizer']
    };

    const modules = moduleMap[pageType] || ['config'];
    return this.loadModules(modules);
  }
}

// Instance globale
const moduleLoader = new ModuleLoader();

// Fonction utilitaire pour initialiser une page
async function initPage(pageType) {
  try {
    await moduleLoader.loadForPage(pageType);
    console.log(`Modules chargés pour ${pageType}`);
  } catch (error) {
    console.error('Erreur chargement modules:', error);
  }
}

// Auto-détection du type de page
function detectPageType() {
  const path = window.location.pathname;
  if (path.includes('landing')) return 'landing';
  if (path.includes('login') || path.includes('signup') || path.includes('owner-login')) return 'auth';
  if (path.includes('owner') || path.includes('client') || path.includes('supplier')) return 'dashboard';
  return 'landing';
}

// Export
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { ModuleLoader, moduleLoader, initPage, detectPageType };
}