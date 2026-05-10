# 🐰 Rabbit B2B MRO Platform - Documentation Technique

## 📋 Vue d'ensemble

**Rabbit** est une plateforme B2B (Business-to-Business) spécialisée dans la gestion des stocks et achats industriels pour le secteur MRO (Maintenance, Réparation, Exploitation). La plateforme connecte les acheteurs industriels avec les fournisseurs pour optimiser la chaîne d'approvisionnement.

### 🎯 Objectifs métier
- **Gestion intelligente des stocks** : Suivi en temps réel des niveaux de stock
- **Optimisation des achats** : Demandes de devis (RFQ) automatisées
- **Connexion acheteurs-fournisseurs** : Écosystème digitalisé
- **Réduction des coûts** : Automatisation des processus d'approvisionnement

---

## 🏗️ Architecture Technique

### Technologies utilisées
- **Frontend** : HTML5, CSS3, JavaScript ES6+
- **Build** : Vite.js avec optimisation d'images
- **Styling** : CSS Variables, Flexbox/Grid, Responsive Design
- **Backend** : API REST (non inclus dans ce repo)

### Structure du projet
```
rabbit-b2b-mro/
├── 📁 css/                 # Feuilles de style
│   ├── variables.css       # Variables CSS globales
│   ├── landing.css         # Page d'accueil
│   ├── auth.css            # Pages d'authentification
│   ├── dashboard.css       # Dashboards utilisateurs
│   └── images.css          # Optimisation des images
├── 📁 js/                  # JavaScript modulaire
│   ├── config.js           # Configuration API
│   ├── auth.js             # Authentification
│   ├── utils.js            # Fonctions utilitaires
│   ├── image-optimizer.js  # Optimisation images
│   ├── dashboard-shared.js # Classes communes dashboards
│   ├── owner.js            # Dashboard administrateur
│   ├── client.js           # Dashboard acheteur
│   └── supplier.js         # Dashboard fournisseur
├── 📁 public/              # Assets statiques
│   ├── logo.svg            # Logo de la plateforme
│   └── favicon.svg         # Favicon
├── 📄 *.html               # Pages de l'application
├── vite.config.js          # Configuration build
└── package.json            # Dépendances
```

---

## 📄 Pages de l'application

### 1. **Page d'accueil** (`landing.html`)
**URL** : `/` ou `/landing.html`
**Rôle** : Présentation de la plateforme et conversion leads

#### Sections principales :
- **Navbar** : Logo, navigation, CTA "Commencer"
- **Hero** : Message principal + badge "Nouvelle plateforme"
- **Features** : Grille de fonctionnalités (6 cards)
- **Pricing** : 3 plans tarifaires (Starter/Pro/Enterprise)
- **Contact** : Formulaire de contact + informations
- **Footer** : Liens légaux + réseaux sociaux

#### Fonctionnalités interactives :
- Navigation smooth scroll
- Animations CSS au scroll
- Formulaire de contact
- Boutons CTA vers inscription

#### Métadonnées SEO :
- Titre optimisé : "Rabbit — Plateforme B2B MRO"
- Meta description complète
- Open Graph pour partage Facebook/LinkedIn
- Structured Data (JSON-LD) pour rich snippets

### 2. **Authentification** (`login.html`, `signup.html`, `owner-login.html`)

#### Pages d'authentification :
- **`login.html`** : Connexion standard (client/fournisseur)
- **`signup.html`** : Inscription nouvelle entreprise
- **`owner-login.html`** : Accès administrateur/plateforme

#### Fonctionnalités communes :
- **Design** : Layout 2 colonnes (formulaire + branding)
- **Validation** : Email/password requis
- **Feedback** : Messages d'erreur en temps réel
- **Redirection** : Vers dashboard approprié selon rôle

#### Rôles utilisateurs :
- **`client`** : Acheteur industriel
- **`fournisseur`** : Fournisseur de composants
- **`owner`** : Administrateur plateforme

### 3. **Dashboards utilisateurs**

#### A. **Dashboard Client** (`client.html`)
**Rôles** : `client`
**URL** : `/client.html`

**Sections :**
- **Catalogue** : Produits disponibles avec filtres
- **Panier** : Gestion des articles sélectionnés
- **Commandes** : Historique des achats
- **Notifications** : Alertes et messages

**Actions principales :**
- Recherche et filtrage produits
- Ajout/suppression du panier
- Soumission RFQ (demande de devis)
- Suivi des commandes

#### B. **Dashboard Fournisseur** (`supplier.html`)
**Rôles** : `fournisseur`
**URL** : `/supplier.html`

**Sections :**
- **RFQ reçues** : Demandes de devis à traiter
- **Offres soumises** : Suivi des propositions
- **Produits** : Catalogue fournisseur
- **Statistiques** : KPIs (ouvert/coté/accepté)

**Actions principales :**
- Consultation RFQ
- Soumission d'offres (prix, délai, conditions)
- Gestion du catalogue produits
- Suivi des performances

#### C. **Dashboard Administrateur** (`owner.html`)
**Rôles** : `owner`
**URL** : `/owner.html`

**Sections :**
- **Tableau de bord** : KPIs globaux
- **Produits** : Gestion du catalogue global
- **Relations** : Associations produit-fournisseur
- **RFQ** : Supervision des demandes
- **Alertes stock** : Gestion des ruptures
- **Inventaire** : Stocks globaux
- **Fournisseurs** : Gestion des comptes
- **Commandes** : Suivi global

**Actions principales :**
- Configuration produits et relations
- Supervision des RFQ
- Gestion des alertes stock
- Administration utilisateurs

---

## 🔧 Architecture JavaScript

### Modules principaux

#### 1. **`config.js`** - Configuration API
```javascript
const API_BASE_URL = "http://localhost/rabbit/backend/api";
const USE_MOCK_DATA = false;

// Fonctions utilitaires API
async function apiFetchJson(path, options)
function unwrap(response, fallback)
function unwrapItems(response)
function formatApiError(err)
function showToast(message, type)
```

#### 2. **`auth.js`** - Gestion authentification
```javascript
const ROLE_PAGES = {
  owner: "owner.html",
  client: "client.html",
  fournisseur: "supplier.html"
};

async function login()
async function logout()
async function checkAuth(requiredRole)
function redirectToRolePage(user)
```

#### 3. **`utils.js`** - Fonctions utilitaires communes
```javascript
function setEl(id, val)           // Définit textContent
function getEl(id)               // Récupère élément
function esc(str)                // Échappe HTML
function formatNumber(num)       // Format français
function formatDate(date)        // Format date française
function getInitials(name)       // Génère initiales
```

#### 4. **`dashboard-shared.js`** - Classes communes
```javascript
class DashboardBase {
  constructor(role)
  async init()
  setupUserInterface()
  showView(viewName)
}

class ModalManager {
  static open(modalId)
  static close(modalId)
}

class FormManager {
  static getFormData(formId)
  static setFormData(formId, data)
}
```

### Gestion d'état

#### Variables globales par dashboard :
```javascript
// owner.js
let allProducts = [], allRelations = [], allRFQs = [];
let allMovements = [], allSuppliers = [], allAlerts = [];

// client.js
let allProducts = [], filteredProducts = [], cart = [];

// supplier.js
let allRFQs = [], currentRFQ = null;
```

---

## 🎨 Système de design

### Variables CSS (`css/variables.css`)
```css
:root {
  /* Couleurs */
  --dark: #050a15;           /* Fond principal */
  --dark-2: #0f172a;         /* Sidebar */
  --accent: #5b5ef4;         /* Bleu principal */
  --accent-h: #4f52e3;       /* Hover */

  /* Typographie */
  --font-primary: 'Manrope', sans-serif;
  --font-heading: 'DM Serif Display', serif;

  /* Espacement */
  --sidebar-w: 280px;
  --radius: 12px;
  --shadow: 0 4px 12px rgba(0,0,0,0.08);
}
```

### Composants réutilisables
- **Boutons** : `.btn-primary`, `.btn-secondary`, `.btn-ghost`
- **Formulaires** : `.form-group`, `.form-input`, `.form-error`
- **Cartes** : `.card`, `.card-header`, `.card-body`
- **Modales** : `.modal`, `.modal-content`, `.modal-overlay`

---

## 🔌 API Backend - Endpoints utilisés

### Authentification
```
POST /login
Body: { email, password }
Response: { user: { id, name, email, role } }

POST /logout
Response: { message: "Déconnexion réussie" }
```

### Catalogue (Client)
```
GET /catalog/products
Response: [{ id, name, description, category, stock_qty, is_active }]

GET /catalog/promotions/active
Response: [{ id, product_id, discount_percent, valid_until }]
```

### Produits (Admin)
```
GET /products
Response: [{ id, name, description, category, components: [] }]

POST /products
Body: { name, description, category, component_ids }

PUT /products/{id}
DELETE /products/{id}
```

### RFQ (Demandes de devis)
```
GET /rfqs
Response: [{ id, client_id, product_id, quantity_requested, status }]

POST /rfqs
Body: { product_id, quantity_requested, lead_time_days, notes }

PUT /rfqs/{id}/quote
Body: { supplier_id, unit_price, total_price, lead_time_days, conditions }
```

### Stock & Inventaire
```
GET /inventory
Response: [{ component_id, current_stock, min_stock, max_stock }]

GET /inventory/alerts
Response: [{ component_id, alert_type, message }]

GET /stock/{componentId}/movements
Response: [{ id, type, quantity, reason, created_at }]
```

### Commandes
```
GET /orders
Response: [{ id, client_id, supplier_id, rfq_id, status, total_amount }]

POST /orders/{id}/status
Body: { status: "confirmed" | "shipped" | "delivered" }
```

### Utilisateurs & Relations
```
GET /suppliers
Response: [{ id, name, email, company, status }]

GET /relations
Response: [{ product_id, supplier_id, is_preferred, lead_time_days }]

POST /relations
Body: { product_id, supplier_id, lead_time_days, pricing_terms }
```

---

## 🔄 Flux utilisateur

### 1. **Première visite** (Lead)
1. **Landing page** → Découverte fonctionnalités
2. **CTA "Commencer"** → `signup.html`
3. **Inscription** → Formulaire entreprise
4. **Email validation** → Activation compte
5. **Redirection** → Dashboard selon rôle

### 2. **Connexion régulière**
1. **login.html** → Saisie credentials
2. **Validation** → Vérification rôle
3. **Redirection** → Dashboard approprié
4. **Session active** → Navigation interne

### 3. **Workflow RFQ (Demande de devis)**
```
Client → Sélection produit → Ajout au panier → Soumission RFQ
    ↓
Admin → Validation RFQ → Assignation fournisseurs
    ↓
Fournisseur → Réception RFQ → Soumission offre
    ↓
Client → Comparaison offres → Sélection fournisseur → Commande
```

### 4. **Gestion stock (Admin)**
```
Admin → Consultation inventaire → Identification ruptures
    ↓
Admin → Création alertes → Notification fournisseurs
    ↓
Fournisseur → Réponse RFQ → Livraison produits
    ↓
Admin → Mise à jour stock → Fermeture alerte
```

---

## 📊 KPIs et métriques

### Dashboard Client
- **Commandes actives** : Nombre de RFQ en cours
- **Panier** : Articles sélectionnés
- **Historique** : Commandes passées

### Dashboard Fournisseur
- **RFQ ouverts** : Demandes à traiter
- **Offres soumises** : Propositions envoyées
- **Taux d'acceptation** : Offres acceptées/total

### Dashboard Admin
- **Produits actifs** : Nombre de produits en catalogue
- **Alertes stock** : Ruptures à traiter
- **RFQ en attente** : Demandes non assignées
- **Fournisseurs actifs** : Comptes validés

---

## 🚀 Déploiement et build

### Configuration Vite (`vite.config.js`)
```javascript
export default defineConfig({
  plugins: [imagemin({ /* optimisation images */ })],
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: {
        landing: 'landing.html',
        login: 'login.html',
        // ... tous les points d'entrée
      }
    }
  }
})
```

### Commandes build
```bash
npm install          # Installation dépendances
npm run dev         # Développement (localhost:3001)
npm run build       # Production (dossier dist/)
```

### Optimisations appliquées
- ✅ **Minification** Terser pour JavaScript
- ✅ **Compression** Gzip/Brotli
- ✅ **Images** optimisées (WebP, compression)
- ✅ **Code splitting** par modules
- ✅ **Lazy loading** des images
- ✅ **Cache** des assets statiques

---

## 🔍 Points d'attention pour le backend

### Cohérence API
1. **Formats de réponse** : `{ message, data }` wrapper
2. **Codes d'erreur** : Gestion uniforme des erreurs
3. **Pagination** : Structure `{ items, total, page }`
4. **Dates** : Format ISO 8601
5. **Rôles** : Respect des valeurs (`client`, `fournisseur`, `owner`)

### Sécurité
1. **CORS** : Configuration pour domaine frontend
2. **Sessions** : Gestion des cookies `httpOnly`
3. **Validation** : Sanitisation des inputs
4. **Rate limiting** : Protection contre les abus

### Performance
1. **Indexation** : Optimisation des requêtes fréquentes
2. **Cache** : Mise en cache des données statiques
3. **Pagination** : Limitation des résultats volumineux
4. **WebSocket** : Notifications temps réel (optionnel)

---

## 📞 Support et maintenance

### Structure modulaire
- **Évolutivité** : Ajout facile de nouvelles fonctionnalités
- **Maintenabilité** : Code organisé et documenté
- **Performance** : Optimisations build intégrées

### Tests recommandés
1. **Fonctionnels** : Tous les workflows utilisateur
2. **Performance** : Temps de chargement < 2s
3. **Responsive** : Adaptation mobile/desktop
4. **Accessibilité** : Conformité WCAG

---

*Documentation générée le 7 mai 2026 - Rabbit B2B MRO Platform v1.0*