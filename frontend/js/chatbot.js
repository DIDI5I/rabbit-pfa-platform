/* ============================================================
   chatbot.js — Rabbit B2B MRO Platform
   Widget chatbot flottant : tiroir + messages + rendu réponse.
   Respecte le contrat de réponse backend chatbot.
   ============================================================ */

const Chatbot = (() => {
  let _isOpen     = false;
  let _loading    = false;
  let _quickPrompts = [];

  // ── Injecte le HTML du widget ──────────────────────────────
  function mount(quickPrompts = []) {
    _quickPrompts = quickPrompts;
    if (document.getElementById("chatbot-fab")) return; // déjà monté

    document.body.insertAdjacentHTML("beforeend", `
      <!-- FAB -->
      <button class="chatbot-fab" id="chatbot-fab" title="${L.chatbot.title}">
        <i class="fas fa-comment-dots"></i>
        <span class="chatbot-fab-badge" id="chatbot-fab-badge"></span>
      </button>

      <!-- Overlay -->
      <div class="chatbot-overlay" id="chatbot-overlay"></div>

      <!-- Drawer -->
      <div class="chatbot-drawer" id="chatbot-drawer">
        <div class="chatbot-header">
          <div class="chatbot-header-icon"><i class="fas fa-robot"></i></div>
          <div class="chatbot-header-text">
            <div class="chatbot-header-title">${L.chatbot.title}</div>
            <div class="chatbot-header-sub">${L.chatbot.subtitle}</div>
          </div>
          <button class="chatbot-close-btn" id="chatbot-close">
            <i class="fas fa-xmark"></i>
          </button>
        </div>

        <div class="chatbot-messages" id="chatbot-messages">
          <div class="chatbot-welcome">
            <div class="chatbot-welcome-icon"><i class="fas fa-robot"></i></div>
            <div class="chatbot-welcome-title">${L.chatbot.welcome}</div>
            <div class="chatbot-welcome-sub">${L.chatbot.welcomeSub}</div>
          </div>
        </div>

        <div class="chatbot-quick-bar" id="chatbot-quick-bar"></div>

        <div class="chatbot-input-area">
          <textarea
            class="chatbot-input"
            id="chatbot-input"
            placeholder="${L.chatbot.placeholder}"
            rows="1"
          ></textarea>
          <button class="chatbot-send-btn" id="chatbot-send">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      </div>
    `);

    _bindEvents();
    _renderQuickBar();
  }

  // ── Events ──────────────────────────────────────────────────
  function _bindEvents() {
    document.getElementById("chatbot-fab").addEventListener("click", toggle);
    document.getElementById("chatbot-overlay").addEventListener("click", close);
    document.getElementById("chatbot-close").addEventListener("click", close);
    document.getElementById("chatbot-send").addEventListener("click", _handleSend);

    const input = document.getElementById("chatbot-input");
    input.addEventListener("keydown", e => {
      if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); _handleSend(); }
    });
    input.addEventListener("input", () => {
      input.style.height = "auto";
      input.style.height = Math.min(input.scrollHeight, 120) + "px";
    });
  }

  // ── Quick bar ────────────────────────────────────────────────
  function _renderQuickBar() {
    const bar = document.getElementById("chatbot-quick-bar");
    if (!bar || !_quickPrompts.length) return;
    bar.innerHTML = _quickPrompts.map(p =>
      `<button class="chatbot-quick-btn" data-prompt="${esc(p.message)}">${esc(p.label)}</button>`
    ).join("");
    bar.addEventListener("click", e => {
      const btn = e.target.closest(".chatbot-quick-btn");
      if (btn) _send(btn.dataset.prompt);
    });
  }

  // ── Toggle / open / close ────────────────────────────────────
  function toggle() { _isOpen ? close() : open(); }

  function open() {
    _isOpen = true;
    document.getElementById("chatbot-drawer").classList.add("open");
    document.getElementById("chatbot-overlay").classList.add("open");
    document.getElementById("chatbot-input").focus();
  }

  function close() {
    _isOpen = false;
    document.getElementById("chatbot-drawer").classList.remove("open");
    document.getElementById("chatbot-overlay").classList.remove("open");
  }

  // ── Send ─────────────────────────────────────────────────────
  function _handleSend() {
    const input = document.getElementById("chatbot-input");
    const msg   = input.value.trim();
    if (!msg || _loading) return;
    input.value = "";
    input.style.height = "auto";
    _send(msg);
  }

  async function _send(message) {
    if (_loading) return;
    _loading = true;

    _appendUserMessage(message);
    _showTyping();
    _setSendDisabled(true);

    try {
      const response = await chatbotService.ask(message);
      _hideTyping();
      _renderBotResponse(response);
    } catch (err) {
      _hideTyping();
      _appendErrorMessage(err?.error || err?.message || L.chatbot.errorGeneric);
    } finally {
      _loading = false;
      _setSendDisabled(false);
    }
  }

  // ── Rendu messages ───────────────────────────────────────────
  function _appendUserMessage(text) {
    const el = document.createElement("div");
    el.className = "chatbot-msg user fade-up";
    el.innerHTML = `
      <div class="chatbot-bubble">${esc(text)}</div>
      <div class="chatbot-msg-meta">${formatRelativeDate(new Date().toISOString())}</div>
    `;
    _append(el);
  }

  function _appendErrorMessage(text) {
    const el = document.createElement("div");
    el.className = "chatbot-msg bot fail fade-up";
    el.innerHTML = `<div class="chatbot-bubble"><i class="fas fa-circle-exclamation"></i> ${esc(text)}</div>`;
    _append(el);
  }

  function _renderBotResponse(r) {
    // r = data from chatbot endpoint (already unwrapped)
    const isPermDenied = r.operation_type === "unsupported" ||
                         (r.answer || "").toLowerCase().includes("permission");

    const el = document.createElement("div");
    el.className = `chatbot-msg bot fade-up${isPermDenied ? " denied" : ""}`;

    let html = "";

    // ── Réponse principale ─────────────────────────────────
    html += `<div class="chatbot-bubble">${_formatAnswer(r.answer || L.chatbot.noAnswer)}</div>`;

    // ── Intent / confiance strip ───────────────────────────
    if (r.intent || r.confidence || r.operation_type) {
      html += `<div class="chatbot-intent-strip">`;
      if (r.intent)         html += `<span class="chatbot-intent-tag"><i class="fas fa-tag"></i> ${esc(r.intent)}</span>`;
      if (r.confidence)     html += confidenceBadge(r.confidence);
      if (r.operation_type) html += `<span class="chatbot-op-tag">${esc(r.operation_type)}</span>`;
      html += `</div>`;
    }

    // ── Aperçu des résultats ───────────────────────────────
    if (Array.isArray(r.items_preview) && r.items_preview.length) {
      html += `<div style="margin-top:var(--space-3)">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);margin-bottom:var(--space-2)">${L.chatbot.resultsPreview}</div>
        <div class="chatbot-items-preview">
          ${r.items_preview.map(_renderPreviewItem).join("")}
        </div>
      </div>`;
    }

    // ── Résumé ─────────────────────────────────────────────
    if (r.summary && typeof r.summary === "object" && Object.keys(r.summary).length) {
      html += _renderSummary(r.summary);
    }

    // ── Afficher plus ──────────────────────────────────────
    if (r.result_meta?.has_more) {
      html += `<button class="chatbot-suggestion-btn" style="margin-top:var(--space-2)" data-show-more="1">
        <i class="fas fa-chevron-down"></i> ${L.chatbot.showMore}
      </button>`;
    }

    // ── Limites ────────────────────────────────────────────
    if (Array.isArray(r.limitations) && r.limitations.length) {
      html += `<div style="margin-top:var(--space-2);padding:8px 10px;background:var(--amber-light);border-radius:var(--r-md);border:1px solid #FDE68A">
        <div style="font-size:10px;font-weight:700;color:var(--amber);margin-bottom:4px;text-transform:uppercase">
          <i class="fas fa-triangle-exclamation"></i> ${L.chatbot.limitations}
        </div>
        ${r.limitations.map(l => `<div style="font-size:12px;color:#78350F">${esc(l)}</div>`).join("")}
      </div>`;
    }

    // ── Actions suggérées ──────────────────────────────────
    if (Array.isArray(r.suggested_actions) && r.suggested_actions.length) {
      html += `<div class="chatbot-suggestions" style="margin-top:var(--space-2)">
        ${r.suggested_actions.map(a =>
          `<button class="chatbot-suggestion-btn" data-prompt="${esc(a)}">${esc(a)}</button>`
        ).join("")}
      </div>`;
    }

    // ── Sources ────────────────────────────────────────────
    if (Array.isArray(r.sources) && r.sources.length) {
      html += `<div class="chatbot-sources" style="margin-top:var(--space-2)">
        <div style="font-size:10px;font-weight:700;color:var(--text-3);margin-bottom:4px;text-transform:uppercase">
          ${L.chatbot.sources}
        </div>
        ${r.sources.map(s => {
          const icon = s.status === "used"    ? "fa-circle-check"    :
                       s.status === "denied"  ? "fa-circle-xmark"    :
                       s.status === "failed"  ? "fa-circle-exclamation" :
                                               "fa-circle-minus";
          const col  = s.status === "used"   ? "var(--green)" :
                       s.status === "denied" ? "var(--red)"   : "var(--text-3)";
          return `<div class="chatbot-source-item" style="color:${col}">
            <i class="fas ${icon}"></i>
            <span>${esc(s.tool)}</span>
            <span style="color:var(--text-4)">— ${esc(s.status)}</span>
          </div>`;
        }).join("")}
      </div>`;
    }

    // ── Timestamp ──────────────────────────────────────────
    html += `<div class="chatbot-msg-meta">${formatRelativeDate(new Date().toISOString())}</div>`;

    el.innerHTML = html;

    // ── Bind suggestion/show-more buttons ─────────────────
    el.querySelectorAll("[data-prompt]").forEach(btn => {
      btn.addEventListener("click", () => _send(btn.dataset.prompt));
    });
    el.querySelectorAll("[data-show-more]").forEach(btn => {
      btn.addEventListener("click", () => {
        btn.remove();
        _send("show more");
      });
    });

    // ── Navigation ─────────────────────────────────────────
    if (r.navigation?.target_page) {
      const nav = r.navigation;
      // Navigation interne (SPA)
      const hash = _pageToHash(nav.target_page);
      if (hash) {
        Router.navigate(hash);
      } else {
        window.location.href = nav.target_page;
      }
      showNavBubble(
        nav.previous_page_link || "",
        nav.bubble_text || L.chatbot.navBubbleDefault,
        nav.bubble_duration_ms || 4000
      );
    }

    _append(el);
  }

  // ── Helpers de rendu ─────────────────────────────────────────
  function _formatAnswer(text) {
    // Convertit les sauts de ligne en <br>
    return esc(text).replace(/\n/g, "<br>");
  }

  function _renderPreviewItem(item) {
    // Rendu générique d'un item de prévisualisation
    const name = item.name || item.product_name || item.component_name || item.title || "—";
    const sub  = item.sku  || item.status || item.category || "";
    const val  = item.current_stock != null ? `${item.current_stock} unités`
               : item.quoted_price  != null ? formatMAD(item.quoted_price)
               : item.price         != null ? formatMAD(item.price)
               : "";

    return `<div style="display:flex;align-items:center;gap:var(--space-2);
                         padding:8px 10px;background:var(--bg);border-radius:var(--r-md);
                         border:1px solid var(--border);margin-bottom:4px">
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(name)}</div>
        ${sub ? `<div style="font-size:11px;color:var(--text-3)">${esc(sub)}</div>` : ""}
      </div>
      ${val ? `<div style="font-size:12px;font-weight:700;color:var(--blue);flex-shrink:0">${esc(val)}</div>` : ""}
    </div>`;
  }

  function _renderSummary(summary) {
    const entries = Object.entries(summary).slice(0, 6);
    if (!entries.length) return "";
    return `<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:var(--space-2)">
      ${entries.map(([k, v]) => `
        <div style="background:var(--bg);border-radius:var(--r-md);padding:8px 10px;border:1px solid var(--border)">
          <div style="font-size:10px;color:var(--text-3);text-transform:uppercase;letter-spacing:.04em">${esc(k)}</div>
          <div style="font-size:13px;font-weight:700;margin-top:2px">${esc(String(v))}</div>
        </div>`).join("")}
    </div>`;
  }

  // ── Typing indicator ─────────────────────────────────────────
  function _showTyping() {
    const el = document.createElement("div");
    el.className = "chatbot-msg bot";
    el.id = "chatbot-typing";
    el.innerHTML = `<div class="chatbot-typing"><span></span><span></span><span></span></div>
                    <div class="chatbot-msg-meta">${L.chatbot.typing}</div>`;
    _append(el);
  }

  function _hideTyping() {
    document.getElementById("chatbot-typing")?.remove();
  }

  // ── Utils ────────────────────────────────────────────────────
  function _append(el) {
    const container = document.getElementById("chatbot-messages");
    // Supprime le welcome si présent
    container.querySelector(".chatbot-welcome")?.remove();
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
  }

  function _setSendDisabled(val) {
    const btn = document.getElementById("chatbot-send");
    if (btn) btn.disabled = val;
  }

  function _pageToHash(targetPage) {
    // Mappe /owner/inventory → "inventory" pour le routeur SPA
    const map = {
      "/owner/inventory":         "inventory",
      "/owner/dashboard":         "dashboard",
      "/owner/products":          "products",
      "/owner/rfqs":              "rfqs",
      "/owner/purchase-lots":     "lots",
      "/owner/promotions":        "promotions",
      "/owner/reviews":           "reviews",
      "/owner/notifications":     "notifications",
      "/owner/intelligence":      "intelligence",
      "/owner/procurement":       "procurement",
      "/owner/suppliers":         "suppliers",
      "/owner/movements":         "movements",
    };
    return map[targetPage] || null;
  }

  // ── API publique ─────────────────────────────────────────────
  return { mount, toggle, open, close };
})();
