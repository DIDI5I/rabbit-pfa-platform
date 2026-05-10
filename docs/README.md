# Rabbit B2B MRO Platform

Plateforme B2B pour la gestion des stocks et achats de maintenance, réparation et exploitation (MRO).

## 🚀 Démarrage rapide

### Installation
```bash
npm install
```

### Développement
```bash
npm run dev
```
Ouvre [http://localhost:3000](http://localhost:3000) dans votre navigateur.

### Build de production
```bash
npm run build
```
Les fichiers optimisés seront générés dans le dossier `dist/`.

### Prévisualisation
```bash
npm run preview
```

## 📁 Structure du projet

```
├── index.html          # Point d'entrée principal
├── landing.html        # Page d'accueil
├── login.html          # Connexion utilisateurs
├── signup.html         # Inscription
├── owner-login.html    # Connexion propriétaire
├── client.html         # Dashboard acheteur
├── owner.html          # Dashboard propriétaire
├── supplier.html       # Dashboard fournisseur
├── css/                # Styles CSS
├── js/                 # Scripts JavaScript
├── dist/               # Build de production (généré)
├── vite.config.js      # Configuration Vite
├── package.json        # Dépendances
└── README.md           # Documentation
```

## 🛠️ Technologies utilisées

- **Vite** : Build tool et dev server ultra-rapide
- **HTML5** : Structure des pages
- **CSS3** : Styles et responsive design
- **JavaScript ES6+** : Interactivité
- **Font Awesome** : Icônes

## 📦 Scripts disponibles

- `npm run dev` : Lance le serveur de développement avec HMR
- `npm run build` : Construit les assets optimisés (minification, bundling)
- `npm run preview` : Prévisualise la version de production

## ⚡ Optimisations de performance

Le build process avec Vite apporte :
- **Minification** : CSS et HTML compressés
- **Bundling** : Regroupement intelligent des ressources
- **Cache busting** : Hash des fichiers pour éviter la mise en cache
- **Tree shaking** : Suppression du code inutilisé
- **Compression gzip** : Réduction de la taille de transfert

## 🎯 Fonctionnalités

- Gestion intelligente des stocks
- Connexion acheteurs/fournisseurs
- Analyses et rapports avancés
- Interface responsive
- Sécurité et conformité

## 📞 Support

Pour toute question, contactez l'équipe Rabbit à contact@rabbit-mro.com.