/* ═══════════════════════════════════════════════════════════════
   RABBIT — Product Detail Page JS Patch
   Load AFTER client.js.
   This overrides product detail functions without redeclaring config.js globals.
   ═══════════════════════════════════════════════════════════════ */

(function () {
  "use strict";

  const CLIENT_RELATION_SECTION_CONFIG = [
    {
      key: "replacement_part",
      label: "Pièces de remplacement",
      icon: "🔧",
    },
    {
      key: "spare_part",
      label: "Pièces de rechange",
      icon: "⚙️",
    },
    {
      key: "compatible_alternative",
      label: "Alternatives compatibles",
      icon: "🔄",
    },
    {
      key: "related_product",
      label: "Produits associés",
      icon: "📦",
    },
    {
      key: "accessory",
      label: "Accessoires",
      icon: "🔩",
    },
  ];

  function safeEsc(value) {
    if (typeof esc === "function") return esc(value);
    if (value == null) return "";
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function safeUnwrap(response, fallback = null) {
    if (typeof unwrap === "function") return unwrap(response, fallback);
    if (!response) return fallback;
    if (Object.prototype.hasOwnProperty.call(response, "data")) {
      return response.data ?? fallback;
    }
    return response;
  }

  function productIdFrom(productOrId) {
    if (productOrId && typeof productOrId === "object") {
      return productOrId.id ?? productOrId.product_id ?? productOrId.component_id;
    }

    return productOrId;
  }

  window.productImageUrl = function productImageUrl(productOrId, suffix = "") {
    const id = productIdFrom(productOrId);

    if (!id) {
      return "/product-images/default.png";
    }

    return `/product-images/${id}${suffix || ""}.png`;
  };

  window.productImageFallback = function productImageFallback(img) {
    if (!img) return;

    if (!img.dataset.fallback) {
      img.dataset.fallback = "1";
      img.src = "/product-images/default.png";
    }
  };

  function buildStars(rating, max = 5) {
    const filled = Math.round(Number(rating) || 0);
    let html = `<span class="detail-stars">`;

    for (let i = 1; i <= max; i++) {
      if (i <= filled) {
        html += `
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
          </svg>
        `;
      } else {
        html += `
          <svg class="empty" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
          </svg>
        `;
      }
    }

    html += `</span>`;
    return html;
  }

  function availDotClass(status) {
    if (status === "AVAILABLE") return "available";
    if (status === "LOW_AVAILABILITY") return "low";
    return "out";
  }

  function availLabel(status) {
    if (status === "AVAILABLE") return "Disponible";
    if (status === "LOW_AVAILABILITY") return "Disponibilité limitée";
    return "Rupture de stock";
  }

  function detailCategoryLabel(category) {
    if (typeof categoryLabel === "function") {
      return categoryLabel(category);
    }

    const map = {
      assembly: "Assemblage",
      sub_assembly: "Sous-assemblage",
      component: "Composant",
      raw_material: "Matière première",
      consumable: "Consommable",
      tool: "Outil",
      service: "Service",
      other: "Autre",
    };

    return map[category] || category || "—";
  }

  window.setDetailImage = function setDetailImage(btn, src) {
    const img = document.querySelector(".detail-main-img-wrap img");

    if (img) {
      img.style.opacity = "0";

      setTimeout(() => {
        img.src = src;
        img.style.opacity = "1";
      }, 120);
    }

    document.querySelectorAll(".detail-thumb-btn").forEach((button) => {
      button.classList.remove("active");
    });

    if (btn) {
      btn.classList.add("active");
    }
  };

  window.openDetail = async function openDetail(productId) {
    const container = document.querySelector(".dash-content");
    if (!container) return;

    if (typeof setEl === "function") {
      setEl("topbar-title", "Détail produit");
    }

    container.innerHTML = `
      <button class="detail-back-btn" onclick="backToCatalogue()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15,18 9,12 15,6"></polyline>
        </svg>
        Retour au catalogue
      </button>

      <div style="color:#a0a0be;font-size:14px;padding:40px 0;text-align:center;">
        Chargement du produit…
      </div>
    `;

    try {
      const productRes = await apiFetchJson(`/catalog/products/${productId}`);
      const product = safeUnwrap(productRes, {});

      const [promoResult, ratingResult, reviewsResult] = await Promise.allSettled([
        apiFetchJson(`/catalog/products/${productId}/promotions`),
        apiFetchJson(`/catalog/products/${productId}/rating-summary`),
        apiFetchJson(`/catalog/products/${productId}/reviews`),
      ]);

      const promoData =
        promoResult.status === "fulfilled"
          ? safeUnwrap(promoResult.value, { items: [] })
          : { items: [] };

      const ratingData =
        ratingResult.status === "fulfilled"
          ? safeUnwrap(ratingResult.value, {})
          : {};

      const reviewsData =
        reviewsResult.status === "fulfilled"
          ? safeUnwrap(reviewsResult.value, { items: [] })
          : { items: [] };

      const avgRating = ratingData.average_rating;
      const reviewCount = ratingData.review_count || 0;
      const reviews = Array.isArray(reviewsData.items) ? reviewsData.items : [];
      const promos = Array.isArray(promoData.items) ? promoData.items : [];

      const mainImg = window.productImageUrl(product.id, "");
      const imgB = window.productImageUrl(product.id, "b");
      const imgC = window.productImageUrl(product.id, "c");

      const ratingHtml =
        avgRating != null
          ? `
            ${buildStars(avgRating)}
            <span class="detail-rating-text">${Number(avgRating).toFixed(1)} · ${reviewCount} avis</span>
          `
          : `
            ${buildStars(0)}
            <span class="detail-rating-text">Aucun avis</span>
          `;

      const dotClass = availDotClass(product.availability_status);
      const availability = availLabel(product.availability_status);

      const promoHtml = promos.length
        ? `
          <div class="detail-promo-banner">
            <span class="promo-tag">PROMO</span>
            <span>
              ${safeEsc(promos[0].title)}
              ${
                promos[0].discount_type === "percentage"
                  ? ` — ${safeEsc(promos[0].discount_value)}% de remise`
                  : ""
              }
            </span>
          </div>
        `
        : "";

      const isUnavailable = product.availability_status === "OUT_OF_STOCK";

      const heroHtml = `
        <div class="detail-hero">
          <div class="detail-gallery">
            <div class="detail-main-img-wrap">
              <img
                src="${safeEsc(mainImg)}"
                alt="${safeEsc(product.name)}"
                onerror="productImageFallback(this)"
                style="transition:opacity .2s"
              >
            </div>

            <div class="detail-thumbnails">
              <button class="detail-thumb-btn active" type="button" onclick="setDetailImage(this, '${safeEsc(mainImg)}')">
                <img src="${safeEsc(mainImg)}" alt="Vue principale" onerror="productImageFallback(this)">
              </button>

              <button class="detail-thumb-btn" type="button" onclick="setDetailImage(this, '${safeEsc(imgB)}')">
                <img src="${safeEsc(imgB)}" alt="Vue B" onerror="this.closest('.detail-thumb-btn').style.display='none'">
              </button>

              <button class="detail-thumb-btn" type="button" onclick="setDetailImage(this, '${safeEsc(imgC)}')">
                <img src="${safeEsc(imgC)}" alt="Vue C" onerror="this.closest('.detail-thumb-btn').style.display='none'">
              </button>
            </div>
          </div>

          <div class="detail-info">
            <div class="detail-category-badge">
              ${safeEsc(detailCategoryLabel(product.category))}
            </div>

            <h1 class="detail-title">${safeEsc(product.name)}</h1>

            <div class="detail-sku">SKU : ${safeEsc(product.sku || "—")}</div>

            <div class="detail-rating-row">
              ${ratingHtml}
            </div>

            <p class="detail-description">
              ${safeEsc(product.description || "Aucune description disponible.")}
            </p>

            ${promoHtml}

            <div class="detail-specs">
              <div class="detail-spec-card">
                <span class="detail-spec-label">Disponibilité</span>
                <span class="detail-spec-value">
                  <span class="avail-dot ${dotClass}"></span>
                  ${availability}
                </span>
              </div>

              <div class="detail-spec-card">
                <span class="detail-spec-label">Unité</span>
                <span class="detail-spec-value">${safeEsc(product.unit_of_measure || "—")}</span>
              </div>

              <div class="detail-spec-card">
                <span class="detail-spec-label">Prix</span>
                <span class="detail-spec-value price">Sur devis</span>
              </div>

              <div class="detail-spec-card">
                <span class="detail-spec-label">Statut catalogue</span>
                <span class="detail-spec-value">${product.is_active ? "✓ Actif" : "✗ Inactif"}</span>
              </div>
            </div>

            <div class="detail-cta-row">
              <div class="detail-qty">
                <button
                  type="button"
                  onclick="(function(){var i=document.getElementById('detail-qty-inp');i.value=Math.max(1,+i.value-1);})()"
                >−</button>

                <input type="number" id="detail-qty-inp" value="1" min="1">

                <button
                  type="button"
                  onclick="(function(){var i=document.getElementById('detail-qty-inp');i.value=+i.value+1;})()"
                >+</button>
              </div>

              <button
                class="detail-cta-primary"
                ${isUnavailable ? "disabled" : ""}
                onclick="(function(){var q=+document.getElementById('detail-qty-inp').value;if(typeof addToCartWithQty==='function')addToCartWithQty(${Number(product.id)}, q);})()"
              >
                ${isUnavailable ? "Indisponible" : "Ajouter au panier"}
              </button>

              <button
                class="detail-cta-secondary"
                ${isUnavailable ? "disabled" : ""}
                onclick="(function(){var q=+document.getElementById('detail-qty-inp').value;if(typeof addToCartWithQty==='function')addToCartWithQty(${Number(product.id)}, q); if(typeof toggleCart==='function')toggleCart();})()"
              >
                Demander un devis
              </button>
            </div>

            <div class="detail-trust-row">
              <div class="detail-trust-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="1" y="3" width="15" height="13" rx="1"></rect>
                  <polygon points="16,8 20,8 23,11 23,16 16,16 16,8"></polygon>
                  <circle cx="5.5" cy="18.5" r="2.5"></circle>
                  <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                Livraison selon devis fournisseur
              </div>

              <div class="detail-trust-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="1,4 1,10 7,10"></polyline>
                  <path d="M3.51,15a9,9 0,1,0,.49-3.27"></path>
                </svg>
                Retour/échange selon conditions commerciales
              </div>

              <div class="detail-trust-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12,22s8-4,8-10V5l-8-3-8,3v7c0,6,8,10,8,10z"></path>
                </svg>
                Produit industriel vérifié
              </div>

              <div class="detail-trust-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21,15a2,2 0 0,1-2,2H7l-4,4V5a2,2 0 0,1 2-2h14a2,2 0 0,1 2,2z"></path>
                </svg>
                Réponse RFQ par fournisseur
              </div>
            </div>
          </div>
        </div>
      `;

      const reviewsHtml = window.renderProductReviews(reviews, ratingData);

      container.innerHTML = `
        <button class="detail-back-btn" onclick="backToCatalogue()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15,18 9,12 15,6"></polyline>
          </svg>
          Retour au catalogue
        </button>

        ${heroHtml}

        <div id="detail-relations-placeholder" class="detail-relations">
          <div style="color:#a0a0be;font-size:13px;padding:10px 0;">
            Chargement des produits liés…
          </div>
        </div>

        ${reviewsHtml}
      `;

      window.loadProductRelations(productId);
    } catch (err) {
      container.innerHTML = `
        <button class="detail-back-btn" onclick="backToCatalogue()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15,18 9,12 15,6"></polyline>
          </svg>
          Retour au catalogue
        </button>

        <div style="color:#ef4444;padding:32px;text-align:center;font-size:14px;">
          Erreur lors du chargement du produit.<br>
          <small>${safeEsc(err.message || "")}</small>
        </div>
      `;
    }
  };

  window.loadProductRelations = async function loadProductRelations(productId) {
    const placeholder = document.getElementById("detail-relations-placeholder");
    if (!placeholder) return;

    try {
      const relRes = await apiFetchJson(`/catalog/products/${productId}/relations`);
      const relData = safeUnwrap(relRes, { items: [] });

      const html = window.renderRelationCarousels(relData);
      placeholder.innerHTML = html || "";
    } catch (err) {
      console.warn("Product relations unavailable:", err);
      placeholder.innerHTML = "";
    }
  };

window.renderRelationCarousels = function renderRelationCarousels(payload) {
  const items = Array.isArray(payload?.items) ? payload.items : [];

  if (!items.length) return "";

  const grouped = {};

  items.forEach((relation) => {
    const key = relation.relation_type || "related_product";

    if (!grouped[key]) {
      grouped[key] = [];
    }

    grouped[key].push(relation);
  });

  let html = "";

  CLIENT_RELATION_SECTION_CONFIG.forEach((section, sectionIndex) => {
    const relations = grouped[section.key];

    if (!relations || !relations.length) return;

    const cards = relations.map((relation) => {
      const product = relation.product || {};
      const productId = product.id;
      const imgSrc = window.productImageUrl(productId, "");
      const dotCls = availDotClass(product.availability_status);
      const avLbl = availLabel(product.availability_status);

      return `
        <div class="relation-card" onclick="openDetail(${Number(productId)})" title="${safeEsc(product.name || "")}">
          <img
            class="relation-card-img"
            src="${safeEsc(imgSrc)}"
            alt="${safeEsc(product.name || "Produit lié")}"
            onerror="productImageFallback(this)"
          >

          <div class="relation-card-name">${safeEsc(product.name || "Produit lié")}</div>
          <div class="relation-card-sku">${safeEsc(product.sku || "")}</div>

          <div class="relation-card-avail">
            <span class="avail-dot ${dotCls}"></span>
            <span>${avLbl}</span>
          </div>
        </div>
      `;
    }).join("");

    /*
      Infinite glide logic:
      - one belt is repeated many times
      - second belt is identical
      - track translates by -50%
      - reset is invisible
    */
    const repeatCount = Math.max(8, Math.ceil(14 / relations.length));
    const belt = Array.from({ length: repeatCount }, () => cards).join("");

    const directionClass = sectionIndex % 2 === 0
      ? "marquee-left"
      : "marquee-right";

    html += `
      <div class="detail-relation-section">
        <div class="detail-relation-heading">
          <h3 class="detail-section-title">${section.icon} ${section.label}</h3>
          <span>${relations.length} produit(s)</span>
        </div>

        <div class="relation-carousel-wrap">
          <div class="relation-carousel-track ${directionClass}">
            <div class="relation-carousel-belt">
              ${belt}
            </div>

            <div class="relation-carousel-belt" aria-hidden="true">
              ${belt}
            </div>
          </div>
        </div>
      </div>
    `;
  });

  return html ? `<div class="detail-relations">${html}</div>` : "";
};
  window.renderProductReviews = function renderProductReviews(reviews = [], summary = {}) {
    const avg = summary?.average_rating;
    const count = summary?.review_count || reviews.length || 0;

    const emptyState = `
      <div class="detail-reviews-empty">
        <div>
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M8 15s1.5 2 4 2 4-2 4-2"></path>
            <line x1="9" y1="9" x2="9.01" y2="9"></line>
            <line x1="15" y1="9" x2="15.01" y2="9"></line>
          </svg>
        </div>
        Aucun avis pour ce produit.
      </div>
    `;

    let reviewItemsHtml = "";

    if (reviews.length) {
      reviewItemsHtml = reviews.map((review) => `
        <div class="review-item">
          <div class="review-item-header">
            <span class="review-item-author">${safeEsc(review.reviewer_name || "Anonyme")}</span>
            <span class="review-item-date">
              ${review.created_at && typeof formatDate === "function" ? formatDate(review.created_at) : ""}
            </span>
          </div>

          <div>${buildStars(review.rating || 0)}</div>

          ${review.comment ? `<p class="review-item-body">${safeEsc(review.comment)}</p>` : ""}
        </div>
      `).join("");
    }

    return `
      <div class="detail-reviews">
        <div class="detail-reviews-header">
          <h3 class="detail-section-title" style="margin:0">⭐ Avis clients</h3>
        </div>

        ${
          count === 0
            ? emptyState
            : `
              <div class="detail-reviews-summary">
                <div class="detail-reviews-big-score">
                  ${avg != null ? Number(avg).toFixed(1) : "—"}
                </div>

                <div class="detail-reviews-meta">
                  ${buildStars(avg || 0)}
                  <span style="font-size:12px;color:#a0a0be;font-weight:600;">
                    ${count} avis
                  </span>
                </div>
              </div>

              ${reviewItemsHtml}
            `
        }
      </div>
    `;
  };
})();