// Optimisation des images et assets
class ImageOptimizer {
  constructor() {
    this.init();
  }

  init() {
    this.setupLazyLoading();
    this.setupImageOptimization();
    this.setupWebPConversion();
  }

  // Lazy loading des images
  setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.add('loaded');
          observer.unobserve(img);
        }
      });
    });

    images.forEach(img => imageObserver.observe(img));
  }

  // Optimisation des images existantes
  setupImageOptimization() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
      // Ajouter loading="lazy" si pas déjà présent
      if (!img.hasAttribute('loading')) {
        img.setAttribute('loading', 'lazy');
      }

      // Ajouter alt si manquant
      if (!img.hasAttribute('alt')) {
        img.setAttribute('alt', 'Image');
      }

      // Optimisation des dimensions
      if (!img.hasAttribute('width') && !img.hasAttribute('height')) {
        img.addEventListener('load', () => {
          if (img.naturalWidth && img.naturalHeight) {
            img.setAttribute('width', img.naturalWidth);
            img.setAttribute('height', img.naturalHeight);
          }
        });
      }
    });
  }

  // Support WebP avec fallback
  setupWebPConversion() {
    const supportsWebP = () => {
      const canvas = document.createElement('canvas');
      canvas.width = 1;
      canvas.height = 1;
      return canvas.toDataURL('image/webp').indexOf('webp') > -1;
    };

    if (supportsWebP()) {
      document.documentElement.classList.add('webp-supported');
    }
  }

  // Préchargement des images critiques
  preloadCriticalImages() {
    const criticalImages = document.querySelectorAll('img[data-critical]');
    criticalImages.forEach(img => {
      const link = document.createElement('link');
      link.rel = 'preload';
      link.as = 'image';
      link.href = img.dataset.src || img.src;
      document.head.appendChild(link);
    });
  }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
  new ImageOptimizer();
});

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
  module.exports = ImageOptimizer;
}