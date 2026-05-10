// utils.js — Fonctions utilitaires communes
// Version optimisée pour réduire la duplication de code

/**
 * Définit le textContent d'un élément par ID
 * @param {string} id - ID de l'élément
 * @param {string} val - Valeur à définir
 */
function setEl(id, val) {
  const e = document.getElementById(id);
  if (e) e.textContent = val;
}

/**
 * Récupère un élément par ID
 * @param {string} id - ID de l'élément
 * @returns {HTMLElement|null}
 */
function getEl(id) {
  return document.getElementById(id);
}

/**
 * Échappe les caractères HTML pour éviter les attaques XSS
 * @param {string} str - Chaîne à échapper
 * @returns {string}
 */
function esc(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

/**
 * Formate un nombre avec séparateur de milliers
 * @param {number} num - Nombre à formater
 * @returns {string}
 */
function formatNumber(num) {
  return new Intl.NumberFormat('fr-FR').format(num);
}

/**
 * Formate une date en français
 * @param {string|Date} date - Date à formater
 * @returns {string}
 */
function formatDate(date) {
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(new Date(date));
}

/**
 * Génère les initiales d'un nom
 * @param {string} name - Nom complet
 * @param {number} length - Longueur des initiales (défaut: 2)
 * @returns {string}
 */
function getInitials(name, length = 2) {
  return (name || '').slice(0, length).toUpperCase();
}

/**
 * Toggle une classe CSS sur un élément
 * @param {string} id - ID de l'élément
 * @param {string} className - Classe à toggler
 */
function toggleClass(id, className) {
  const el = getEl(id);
  if (el) el.classList.toggle(className);
}

/**
 * Cache un élément
 * @param {string} id - ID de l'élément
 */
function hideEl(id) {
  const el = getEl(id);
  if (el) el.style.display = 'none';
}

/**
 * Affiche un élément
 * @param {string} id - ID de l'élément
 */
function showEl(id) {
  const el = getEl(id);
  if (el) el.style.display = '';
}

// utils.js — Fonctions utilitaires communes
// Version optimisée pour réduire la duplication de code

function setEl(id, val) {
  const e = document.getElementById(id);
  if (e) e.textContent = val;
}

function getEl(id) {
  return document.getElementById(id);
}

function setVal(id, val) {
  const e = document.getElementById(id);
  if (e) e.value = val;
}

function setBadge(id, n) {
  const e = document.getElementById(id);
  if (!e) return;

  e.textContent = n;
  e.style.display = Number(n) > 0 ? "inline-flex" : "none";
}

function esc(str) {
  if (str == null) return "";
  const div = document.createElement("div");
  div.textContent = String(str);
  return div.innerHTML;
}

function formatNumber(num) {
  if (num == null || num === "") return "—";
  return new Intl.NumberFormat("fr-FR").format(Number(num));
}

function formatDate(date) {
  if (!date) return "—";

  const parsed = new Date(date);
  if (Number.isNaN(parsed.getTime())) return "—";

  return new Intl.DateTimeFormat("fr-FR", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(parsed);
}

function getInitials(name, length = 2) {
  return (name || "")
    .trim()
    .slice(0, length)
    .toUpperCase();
}

function toggleClass(id, className) {
  const el = getEl(id);
  if (el) el.classList.toggle(className);
}

function hideEl(id) {
  const el = getEl(id);
  if (el) el.style.display = "none";
}

function showEl(id) {
  const el = getEl(id);
  if (el) el.style.display = "";
}

if (typeof module !== "undefined" && module.exports) {
  module.exports = {
    setEl,
    getEl,
    setVal,
    setBadge,
    esc,
    formatNumber,
    formatDate,
    getInitials,
    toggleClass,
    hideEl,
    showEl,
  };
}

// Export pour utilisation modulaire
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { setEl, getEl, esc, formatNumber, formatDate, getInitials, toggleClass, hideEl, showEl };
}