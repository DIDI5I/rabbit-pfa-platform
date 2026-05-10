# Build et déploiement

## Build de production
```bash
npm run build
```

## Déploiement
Le dossier `dist/` contient tous les fichiers optimisés prêts pour le déploiement.

### Options de déploiement :
1. **Serveur web statique** (Apache, Nginx) : Copier le contenu de `dist/` vers le répertoire web
2. **Netlify/Vercel** : Connecter le repo et déployer automatiquement
3. **GitHub Pages** : Utiliser une action GitHub pour déployer `dist/`

### Structure après build :
```
dist/
├── assets/           # CSS/JS optimisés avec hash
├── index.html        # Point d'entrée
├── landing.html      # Pages HTML minifiées
├── login.html
└── ...
```

### Performance :
- ✅ CSS minifié et bundlé
- ✅ HTML minifié
- ✅ Cache busting avec hash
- ✅ Compression gzip activée automatiquement