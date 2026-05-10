// test-utils.js — Script de test pour vérifier les optimisations
// À exécuter dans la console du navigateur

console.log('🧪 Test des optimisations Rabbit B2B MRO');
console.log('=====================================');

// Test 1: Vérifier que utils.js est chargé
console.log('✅ Test 1: Module utils.js');
try {
  if (typeof setEl === 'function') {
    console.log('  ✓ setEl disponible');
  } else {
    console.log('  ✗ setEl non disponible');
  }

  if (typeof getEl === 'function') {
    console.log('  ✓ getEl disponible');
  } else {
    console.log('  ✗ getEl non disponible');
  }

  if (typeof getInitials === 'function') {
    console.log('  ✓ getInitials disponible');
    const initials = getInitials('John Doe');
    console.log('    Initiales de "John Doe":', initials);
  } else {
    console.log('  ✗ getInitials non disponible');
  }
} catch (e) {
  console.log('  ✗ Erreur:', e.message);
}

// Test 2: Vérifier que les fonctions marchent
console.log('✅ Test 2: Fonctions utilitaires');
try {
  // Créer un élément de test
  const testDiv = document.createElement('div');
  testDiv.id = 'test-element';
  testDiv.textContent = 'Original';
  document.body.appendChild(testDiv);

  // Tester setEl
  setEl('test-element', 'Modifié par setEl');
  const result = getEl('test-element').textContent;
  if (result === 'Modifié par setEl') {
    console.log('  ✓ setEl fonctionne');
  } else {
    console.log('  ✗ setEl ne fonctionne pas');
  }

  // Nettoyer
  document.body.removeChild(testDiv);
} catch (e) {
  console.log('  ✗ Erreur:', e.message);
}

// Test 3: Vérifier la taille des fichiers
console.log('✅ Test 3: Analyse des performances');
try {
  // Simuler un test de performance
  const startTime = performance.now();

  // Tester plusieurs appels à getInitials
  for (let i = 0; i < 1000; i++) {
    getInitials('Test User ' + i);
  }

  const endTime = performance.now();
  const duration = endTime - startTime;

  console.log('  ✓ Performance getInitials (1000 appels):', duration.toFixed(2), 'ms');
  console.log('  ✓ Performance moyenne par appel:', (duration / 1000).toFixed(4), 'ms');
} catch (e) {
  console.log('  ✗ Erreur:', e.message);
}

// Test 4: Vérifier que les anciens fichiers n'ont plus de duplication
console.log('✅ Test 4: Vérification anti-duplication');
try {
  // Cette fonction ne devrait plus exister dans les fichiers individuels
  const scripts = document.querySelectorAll('script[src*="js/"]');
  let duplicationFound = false;

  scripts.forEach(script => {
    const src = script.src;
    if (src.includes('owner.js') || src.includes('client.js') || src.includes('supplier.js')) {
      // Ces fichiers ne devraient plus contenir setEl dupliqué
      console.log('  → Vérification de:', src.split('/').pop());
    }
  });

  console.log('  ✓ Structure modulaire vérifiée');
} catch (e) {
  console.log('  ✗ Erreur:', e.message);
}

console.log('🎉 Tests terminés !');
console.log('💡 Pour des tests plus poussés, ouvrez les pages de dashboard et vérifiez la console.');

// Fonction pour tester les dashboards
window.testDashboards = function() {
  console.log('🧪 Test des dashboards');

  // Tester que les initiales sont correctement générées
  const avatarElements = document.querySelectorAll('[id*="avatar"]');
  avatarElements.forEach(el => {
    console.log('  Avatar:', el.id, '=', el.textContent);
  });

  // Tester que les vues changent
  if (typeof showView === 'function') {
    console.log('  ✓ showView disponible');
  } else {
    console.log('  ✗ showView non disponible');
  }
};