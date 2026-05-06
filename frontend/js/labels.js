/* ============================================================
   labels.js — Rabbit B2B MRO Platform
   Toutes les étiquettes françaises de l'interface.
   Le backend reste en anglais. Ce fichier traduit uniquement
   les valeurs affichées à l'utilisateur.
   ============================================================ */

const L = {

  // ── Rôles ─────────────────────────────────────────────────
  roles: {
    owner:     "Administrateur",
    client:    "Acheteur",
    supplier:  "Fournisseur",
    fournisseur: "Fournisseur",
    guest:     "Visiteur",
  },

  // ── Navigation / Sidebar ───────────────────────────────────
  nav: {
    dashboard:         "Tableau de bord",
    catalogue:         "Catalogue",
    products:          "Produits",
    inventory:         "Inventaire",
    stock_intelligence:"Intelligence stock",
    procurement:       "Approvisionnement",
    rfqs:              "Demandes de devis",
    purchase_lots:     "Lots d'achat",
    promotions:        "Promotions",
    reviews:           "Avis clients",
    notifications:     "Notifications",
    chatbot:           "Assistant IA",
    settings:          "Paramètres",
    logout:            "Déconnexion",
    suppliers:         "Fournisseurs",
    orders:            "Commandes",
    movements:         "Mouvements de stock",
  },

  // ── Sidebar sections ───────────────────────────────────────
  sidebarSections: {
    main:        "Principal",
    stock:       "Stock & Inventaire",
    procurement: "Approvisionnement",
    catalogue:   "Catalogue",
    operations:  "Opérations",
    account:     "Compte",
  },

  // ── Catégories produit ─────────────────────────────────────
  categories: {
    assembly:     "Assemblage",
    sub_assembly: "Sous-assemblage",
    component:    "Composant",
    raw_material: "Matière première",
  },

  // ── Disponibilité / Stock status ───────────────────────────
  stockStatus: {
    OK:           "Disponible",
    LOW_STOCK:    "Stock bas",
    OUT_OF_STOCK: "Rupture de stock",
    AVAILABLE:        "Disponible",
    LOW_AVAILABILITY: "Disponibilité limitée",
    // OUT_OF_STOCK same
  },

  // ── Priorité ───────────────────────────────────────────────
  priority: {
    CRITICAL: "Critique",
    HIGH:     "Élevée",
    MEDIUM:   "Moyenne",
    LOW:      "Faible",
    NONE:     "Aucune",
  },

  // ── Confiance ──────────────────────────────────────────────
  confidence: {
    HIGH:          "Haute confiance",
    MEDIUM:        "Confiance moyenne",
    LOW:           "Faible confiance",
    ESTIMATE_ONLY: "Estimation seulement",
  },

  // ── Modèle de prévision ────────────────────────────────────
  forecastModel: {
    criticality_only:             "Criticité uniquement",
    threshold_only:               "Seuil uniquement",
    moving_average:               "Moyenne mobile",
    simple_exponential_smoothing: "Lissage exponentiel",
    croston_sba:                  "Croston / SBA",
    regression_trend:             "Tendance linéaire",
    seasonal_index:               "Indice saisonnier",
  },

  // ── Statuts RFQ ────────────────────────────────────────────
  rfqStatus: {
    draft:     "Brouillon",
    open:      "Ouverte",
    quoted:    "Devis reçu",
    accepted:  "Acceptée",
    rejected:  "Rejetée",
    expired:   "Expirée",
    cancelled: "Annulée",
  },

  // ── Statuts lot d'achat ────────────────────────────────────
  lotStatus: {
    draft:     "Brouillon",
    finalized: "Finalisé",
    cancelled: "Annulé",
  },

  // ── Statuts commande ───────────────────────────────────────
  orderStatus: {
    pending:    "En attente",
    processing: "En traitement",
    shipped:    "Expédiée",
    delivered:  "Livrée",
    cancelled:  "Annulée",
  },

  // ── Statuts promotion ──────────────────────────────────────
  promotionStatus: {
    ACTIVE:   "Active",
    UPCOMING: "À venir",
    EXPIRED:  "Expirée",
    DISABLED: "Désactivée",
  },

  // ── Statuts avis ──────────────────────────────────────────
  reviewStatus: {
    pending:  "En attente",
    approved: "Approuvé",
    rejected: "Rejeté",
  },

  // ── Types de notification ──────────────────────────────────
  notifType: {
    RFQ_ASSIGNED:                    "RFQ assignée",
    RFQ_QUOTED:                      "Devis reçu",
    RFQ_ACCEPTED_BY_OWNER:           "Devis accepté",
    RFQ_REJECTED_BY_OWNER:           "Devis rejeté",
    PURCHASE_LOT_NEEDS_FINALIZATION: "Lot à finaliser",
    PURCHASE_LOT_FINALIZED:          "Lot finalisé",
    LOW_STOCK_ALERT:                 "Stock faible",
    OUT_OF_STOCK_ALERT:              "Rupture de stock",
    ORDER_CREATED:                   "Nouvelle commande",
    ORDER_STATUS_CHANGED:            "Commande mise à jour",
  },

  // ── Raisons de mouvement de stock ─────────────────────────
  movementReason: {
    INITIAL_STOCK:           "Stock initial",
    RFQ_ACCEPTED:            "RFQ acceptée",
    PURCHASE_RECEIVED:       "Réception fournisseur",
    SALE:                    "Vente client",
    MANUAL_ADJUSTMENT:       "Ajustement manuel",
    RETURN:                  "Retour",
    DAMAGED:                 "Endommagé",
    CANCELLED_ORDER_RESTORE: "Restauration commande annulée",
  },

  // ── Types de relation produit ──────────────────────────────
  relationType: {
    technical_structure:    "Structure technique",
    replacement_part:       "Pièce de remplacement",
    compatible_alternative: "Alternative compatible",
    spare_part:             "Pièce de rechange",
    accessory:              "Accessoire",
    related_product:        "Produit associé",
  },

  // ── Sections recommandations ───────────────────────────────
  recSection: {
    explicit_relations:      "Relations explicites",
    compatible_alternatives: "Alternatives compatibles",
    accessories:             "Accessoires",
    spare_parts:             "Pièces de rechange",
    same_category:           "Même catégorie",
    same_supplier:           "Même fournisseur",
  },

  // ── Types de mouvement ─────────────────────────────────────
  movType: {
    in:  "Entrée",
    out: "Sortie",
  },

  // ── Chatbot UI ─────────────────────────────────────────────
  chatbot: {
    title:             "Assistant Rabbit",
    subtitle:          "Alimenté par l'IA backend",
    placeholder:       "Posez une question à Rabbit…",
    send:              "Envoyer",
    showMore:          "Afficher plus",
    quickHelp:         "Aide",
    quickCatalogue:    "Catalogue",
    quickPromotions:   "Promotions",
    quickInventory:    "Inventaire",
    quickReorder:      "Réappros",
    quickAlerts:       "Alertes stock",
    limitations:       "Limites",
    sources:           "Sources",
    suggestedActions:  "Actions suggérées",
    typing:            "Rabbit réfléchit…",
    welcome:           "Bonjour ! Je suis l'assistant Rabbit.",
    welcomeSub:        "Posez-moi une question sur votre stock, vos produits ou vos commandes.",
    errorGeneric:      "Une erreur est survenue. Veuillez réessayer.",
    permissionDenied:  "Vous n'avez pas accès à cette information.",
    intentLabel:       "Intention",
    confidenceLabel:   "Confiance",
    roleLabel:         "Rôle",
    operationLabel:    "Opération",
    sourceUsed:        "utilisé",
    sourceSkipped:     "ignoré",
    sourceDenied:      "refusé",
    navBubbleDefault:  "Retour à la page précédente",
    resultsPreview:    "Aperçu des résultats",
    noAnswer:          "Aucune réponse disponible.",
  },

  // ── États vides ────────────────────────────────────────────
  empty: {
    products:       "Aucun produit trouvé.",
    inventory:      "Aucun élément dans l'inventaire.",
    rfqs:           "Aucune demande de devis.",
    lots:           "Aucun lot d'achat.",
    promotions:     "Aucune promotion active.",
    reviews:        "Aucun avis client.",
    notifications:  "Aucune notification.",
    movements:      "Aucun mouvement de stock.",
    suppliers:      "Aucun fournisseur.",
    orders:         "Aucune commande.",
    alerts:         "Aucune alerte de stock.",
    reorder:        "Aucune recommandation de réapprovisionnement.",
    catalogue:      "Aucun produit dans le catalogue.",
    search:         "Aucun résultat pour cette recherche.",
  },

  // ── Erreurs ────────────────────────────────────────────────
  errors: {
    load:       "Impossible de charger les données.",
    save:       "Enregistrement échoué.",
    delete:     "Suppression échouée.",
    auth:       "Session expirée. Veuillez vous reconnecter.",
    forbidden:  "Accès refusé.",
    notFound:   "Ressource introuvable.",
    generic:    "Une erreur inattendue est survenue.",
    required:   "Ce champ est requis.",
    invalid:    "Valeur invalide.",
    network:    "Erreur réseau. Vérifiez votre connexion.",
  },

  // ── Boutons génériques ─────────────────────────────────────
  btn: {
    save:       "Enregistrer",
    cancel:     "Annuler",
    delete:     "Supprimer",
    edit:       "Modifier",
    create:     "Créer",
    view:       "Voir",
    close:      "Fermer",
    confirm:    "Confirmer",
    back:       "Retour",
    search:     "Rechercher",
    filter:     "Filtrer",
    reset:      "Réinitialiser",
    export:     "Exporter",
    refresh:    "Actualiser",
    add:        "Ajouter",
    remove:     "Retirer",
    approve:    "Approuver",
    reject:     "Rejeter",
    markRead:   "Marquer comme lu",
    markAllRead:"Tout marquer comme lu",
    showMore:   "Voir plus",
    showLess:   "Voir moins",
    details:    "Détails",
    actions:    "Actions",
    send:       "Envoyer",
    respond:    "Répondre",
    finalize:   "Finaliser",
  },

  // ── Entêtes de table communs ───────────────────────────────
  table: {
    id:          "#",
    name:        "Nom",
    sku:         "SKU",
    category:    "Catégorie",
    status:      "Statut",
    quantity:    "Quantité",
    price:       "Prix",
    date:        "Date",
    supplier:    "Fournisseur",
    product:     "Produit",
    stock:       "Stock",
    threshold:   "Seuil",
    value:       "Valeur",
    priority:    "Priorité",
    confidence:  "Confiance",
    type:        "Type",
    reason:      "Raison",
    createdAt:   "Créé le",
    updatedAt:   "Modifié le",
    actions:     "Actions",
    rating:      "Note",
    user:        "Utilisateur",
    total:       "Total",
    reference:   "Référence",
  },

  // ── Labels de page ─────────────────────────────────────────
  page: {
    dashboard:    { title: "Tableau de bord",     sub: "Vue d'ensemble de votre activité" },
    catalogue:    { title: "Catalogue",            sub: "Parcourir les produits disponibles" },
    products:     { title: "Produits",             sub: "Gérer le catalogue interne" },
    inventory:    { title: "Inventaire",           sub: "Niveaux de stock et alertes" },
    intelligence: { title: "Intelligence stock",   sub: "Recommandations de réapprovisionnement" },
    procurement:  { title: "Approvisionnement",    sub: "Lots d'achat et historique" },
    rfqs:         { title: "Demandes de devis",    sub: "Gérer les RFQs fournisseurs" },
    lots:         { title: "Lots d'achat",         sub: "Historique d'achat et coûts" },
    promotions:   { title: "Promotions",           sub: "Gérer les offres commerciales" },
    reviews:      { title: "Avis clients",         sub: "Modérer les avis produits" },
    notifications:{ title: "Notifications",        sub: "Alertes et messages du système" },
    suppliers:    { title: "Fournisseurs",          sub: "Annuaire des fournisseurs" },
    movements:    { title: "Mouvements de stock",  sub: "Historique des entrées et sorties" },
    orders:       { title: "Commandes",            sub: "Suivi des commandes clients" },
    chatbot:      { title: "Assistant IA",         sub: "Intelligence assistée par backend" },
    settings:     { title: "Paramètres",           sub: "Configuration du compte" },
  },

  // ── Login ──────────────────────────────────────────────────
  login: {
    title:       "Connexion",
    subtitle:    "Accédez à votre espace Rabbit",
    email:       "Adresse e-mail",
    password:    "Mot de passe",
    submit:      "Se connecter",
    loading:     "Connexion en cours…",
    error:       "Identifiants incorrects. Veuillez réessayer.",
    forgot:      "Mot de passe oublié ?",
    noAccount:   "Pas encore de compte ?",
    register:    "S'inscrire",
  },
};

// ── Helper : label avec fallback ───────────────────────────────
function lbl(map, key, fallback) {
  return map[key] || fallback || key || "—";
}
