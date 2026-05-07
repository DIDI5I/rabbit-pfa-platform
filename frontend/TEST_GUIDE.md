# 🧪 Guide de test des optimisations

## Tests à effectuer :

### 1. **Test de base - Landing page**
- Ouvrez http://localhost:3001/
- Ouvrez la console du navigateur (F12)
- Les tests automatiques devraient s'exécuter automatiquement
- Vérifiez que vous voyez : "🧪 Test des optimisations Rabbit B2B MRO"

### 2. **Test des fonctions utilitaires**
Dans la console, testez :
```javascript
// Tester getInitials
getInitials("John Doe") // Devrait retourner "JD"

// Tester setEl
setEl("test-element", "Hello World")

// Tester formatDate
formatDate(new Date()) // Devrait retourner une date formatée
```

### 3. **Test des dashboards**
- Allez sur http://localhost:3001/owner.html
- Vérifiez que la page se charge correctement
- Les initiales devraient s'afficher dans la topbar
- Testez la navigation entre les vues

### 4. **Test de performance**
- Ouvrez les outils de développement (F12 → Network)
- Rechargez une page de dashboard
- Vérifiez que les fichiers se chargent correctement :
  - `utils.js` (2.4KB)
  - `config.js` (11KB)
  - `auth.js` (6.7KB)
  - Fichier spécifique au dashboard

### 5. **Test de build**
- Arrêtez le serveur (Ctrl+C)
- Lancez `npm run build`
- Vérifiez que le build réussit sans erreurs
- Les fichiers dans `dist/` devraient être optimisés

## ✅ Résultats attendus :

- ✅ Console affiche les tests réussis
- ✅ Fonctions utilitaires fonctionnent
- ✅ Pages se chargent sans erreurs JavaScript
- ✅ Build fonctionne correctement
- ✅ Tailles de fichiers réduites

## 🔍 Dépannage :

Si vous voyez des erreurs :
- Vérifiez que tous les scripts sont chargés dans l'ordre
- Ouvrez la console réseau pour voir les erreurs 404
- Vérifiez que `utils.js` est bien chargé avant les autres scripts

## 📊 Métriques à vérifier :

- **Temps de chargement initial** : < 2 secondes
- **Taille totale JavaScript** : ~147KB (au lieu de 150KB)
- **Nombre de requêtes** : identique (optimisations côté duplication)
- **Fonctionnalités** : toutes préservées