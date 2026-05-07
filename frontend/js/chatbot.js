/* ============================================================
   chatbot.js — Rabbit frontend chatbot widget
   Calls POST /chatbot/ask
   Uses chatbot.css class system.
   ============================================================ */

const RabbitChatbot = {
  isOpen: false,
  isSending: false,

  init() {
    this.fab = document.getElementById("chatbot-fab");
    this.badge = document.getElementById("chatbot-fab-badge");
    this.overlay = document.getElementById("chatbot-overlay");
    this.drawer = document.getElementById("chatbot-drawer");
    this.closeBtn = document.getElementById("chatbot-close-btn");
    this.form = document.getElementById("chatbot-form");
    this.input = document.getElementById("chatbot-input");
    this.sendBtn = document.getElementById("chatbot-send-btn");
    this.messages = document.getElementById("chatbot-messages");

    if (!this.fab || !this.drawer || !this.form || !this.input || !this.messages) {
      return;
    }

    this.fab.addEventListener("click", () => this.toggle());
    this.overlay?.addEventListener("click", () => this.close());
    this.closeBtn?.addEventListener("click", () => this.close());

    this.form.addEventListener("submit", async (event) => {
      event.preventDefault();
      await this.sendCurrentMessage();
    });

    this.input.addEventListener("keydown", async (event) => {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        await this.sendCurrentMessage();
      }
    });

    this.input.addEventListener("input", () => this.autoResizeInput());

    document.querySelectorAll(".chatbot-quick-btn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const prompt = btn.dataset.prompt;
        if (!prompt) return;

        this.input.value = prompt;
        this.autoResizeInput();
        await this.sendCurrentMessage();
      });
    });
  },

  toggle() {
    this.isOpen ? this.close() : this.open();
  },

  open() {
    this.isOpen = true;
    this.drawer.classList.add("open");
    this.overlay?.classList.add("open");

    if (this.badge) {
      this.badge.style.display = "none";
    }

    setTimeout(() => this.input?.focus(), 60);
  },

  close() {
    this.isOpen = false;
    this.drawer.classList.remove("open");
    this.overlay?.classList.remove("open");
  },

  async sendCurrentMessage() {
    const message = this.input.value.trim();

    if (!message || this.isSending) {
      return;
    }

    this.clearWelcome();
    this.input.value = "";
    this.autoResizeInput();

    this.addMessage(message, "user");

    await this.askBackend(message);
  },

  async askBackend(message) {
    this.isSending = true;
    this.setFormDisabled(true);

    const typingId = this.addTypingMessage();

    try {
      const response = await apiFetchJson("/chatbot/ask", {
        method: "POST",
        body: JSON.stringify({ message }),
      });

      const payload = response?.data ?? response;
      const html = this.renderBotResponse(payload);

      this.replaceMessageHtml(typingId, html, "bot");

      this.bindSuggestedActions();

      if (payload?.result_meta?.ai) {
        console.log("CHATBOT AI META:", payload.result_meta.ai);
      }
    } catch (err) {
      console.error("Chatbot error:", err);
      this.replaceMessageHtml(
        typingId,
        this.escapeHtml(this.formatError(err)),
        "fail"
      );
    } finally {
      this.isSending = false;
      this.setFormDisabled(false);
      this.input.focus();
    }
  },

  clearWelcome() {
    const welcome = this.messages.querySelector(".chatbot-welcome");
    if (welcome) welcome.remove();
  },

  addTypingMessage() {
    const id = this.makeId();

    const wrapper = document.createElement("div");
    wrapper.id = id;
    wrapper.className = "chatbot-msg bot";
    wrapper.innerHTML = `
      <div class="chatbot-typing">
        <span></span>
        <span></span>
        <span></span>
      </div>
    `;

    this.messages.appendChild(wrapper);
    this.scrollToBottom();

    return id;
  },

  addMessage(text, type) {
    const id = this.makeId();

    const wrapper = document.createElement("div");
    wrapper.id = id;
    wrapper.className = `chatbot-msg ${type}`;

    wrapper.innerHTML = `
      <div class="chatbot-bubble">${this.escapeHtml(text)}</div>
      <div class="chatbot-msg-meta">
        <i class="fas ${type === "user" ? "fa-user" : "fa-robot"}"></i>
        <span>${type === "user" ? "Vous" : "Rabbit"}</span>
      </div>
    `;

    this.messages.appendChild(wrapper);
    this.scrollToBottom();

    return id;
  },

  replaceMessageHtml(id, html, type) {
    const wrapper = document.getElementById(id);
    if (!wrapper) return;

    const normalizedType = type === "fail" ? "bot fail" : type;

    wrapper.className = `chatbot-msg ${normalizedType}`;
    wrapper.innerHTML = `
      <div class="chatbot-bubble">${html}</div>
      <div class="chatbot-msg-meta">
        <i class="fas ${type === "fail" ? "fa-triangle-exclamation" : "fa-robot"}"></i>
        <span>${type === "fail" ? "Erreur" : "Rabbit"}</span>
      </div>
    `;

    this.scrollToBottom();
  },

  renderBotResponse(payload) {
    const answer = payload?.answer || "Réponse reçue.";
    const intent = payload?.intent || null;
    const confidence = payload?.confidence || null;
    const role = payload?.role || null;
    const operation = payload?.operation_type || null;
    const summary = payload?.summary || null;
    const items = Array.isArray(payload?.items_preview) ? payload.items_preview : [];
    const sources = Array.isArray(payload?.sources) ? payload.sources : [];
    const limitations = Array.isArray(payload?.limitations) ? payload.limitations : [];
    const actions = Array.isArray(payload?.suggested_actions) ? payload.suggested_actions : [];

    return `
      <div>${this.escapeHtml(answer)}</div>

      ${
        intent || confidence || role || operation
          ? `<div class="chatbot-intent-strip">
              ${intent ? `<span class="chatbot-intent-tag">${this.escapeHtml(intent)}</span>` : ""}
              ${confidence ? `<span class="chatbot-op-tag">Confiance: ${this.escapeHtml(confidence)}</span>` : ""}
              ${role ? `<span class="chatbot-op-tag">Rôle: ${this.escapeHtml(role)}</span>` : ""}
              ${operation ? `<span class="chatbot-op-tag">${this.escapeHtml(operation)}</span>` : ""}
            </div>`
          : ""
      }

      ${summary ? this.renderSummary(summary) : ""}

      ${items.length ? this.renderItemsPreview(items) : ""}

      ${sources.length ? this.renderSources(sources) : ""}

      ${limitations.length ? this.renderLimitations(limitations) : ""}

      ${
        actions.length
          ? `<div class="chatbot-suggestions">
              ${actions.map((action) => `
                <button class="chatbot-suggestion-btn" type="button" data-prompt="${this.escapeHtml(action)}">
                  ${this.escapeHtml(action)}
                </button>
              `).join("")}
            </div>`
          : ""
      }
    `;
  },

  renderSummary(summary) {
    const rows = Object.entries(summary)
      .filter(([_, value]) => value !== null && typeof value !== "object")
      .map(([key, value]) => `
        <div class="chatbot-source-item">
          <i class="fas fa-chart-simple"></i>
          <span>${this.escapeHtml(this.labelize(key))}: <strong>${this.escapeHtml(String(value))}</strong></span>
        </div>
      `)
      .join("");

    return `
      <div class="chatbot-sources">
        ${rows}
      </div>
    `;
  },

  renderItemsPreview(items) {
    return `
      <div class="chatbot-sources">
        ${items.map((item) => {
          const title =
            item.name ||
            item.product_name ||
            item.client_name ||
            item.sku ||
            `Commande #${item.order_id || item.id || item.product_id || "—"}`;

          const meta = [
            item.status ? `Statut: ${item.status}` : null,
            item.total_amount != null ? `Total: ${item.total_amount} MAD` : null,
            item.current_stock != null ? `Stock: ${item.current_stock}` : null,
            item.low_stock_threshold != null ? `Seuil: ${item.low_stock_threshold}` : null,
            item.recommended_reorder_quantity != null ? `Qté: ${item.recommended_reorder_quantity}` : null,
            item.estimated_reorder_value != null ? `Valeur: ${item.estimated_reorder_value} MAD` : null,
            item.priority ? `Priorité: ${item.priority}` : null,
            item.confidence ? `Confiance: ${item.confidence}` : null,
          ].filter(Boolean).join(" · ");

          return `
            <div class="chatbot-source-item">
              <i class="fas fa-circle-dot"></i>
              <span><strong>${this.escapeHtml(title)}</strong>${meta ? ` — ${this.escapeHtml(meta)}` : ""}</span>
            </div>
          `;
        }).join("")}
      </div>
    `;
  },

  renderSources(sources) {
    return `
      <div class="chatbot-sources">
        ${sources.map((source) => `
          <div class="chatbot-source-item">
            <i class="fas fa-database"></i>
            <span>Source: ${this.escapeHtml(source.tool || "outil")} · ${this.escapeHtml(source.status || "utilisé")}</span>
          </div>
        `).join("")}
      </div>
    `;
  },

  renderLimitations(limitations) {
    return `
      <div class="chatbot-sources">
        ${limitations.map((limitation) => `
          <div class="chatbot-source-item">
            <i class="fas fa-circle-exclamation"></i>
            <span>Limite: ${this.escapeHtml(limitation)}</span>
          </div>
        `).join("")}
      </div>
    `;
  },

  bindSuggestedActions() {
    this.messages.querySelectorAll(".chatbot-suggestion-btn").forEach((btn) => {
      if (btn.dataset.bound === "1") return;

      btn.dataset.bound = "1";
      btn.addEventListener("click", async () => {
        const prompt = btn.dataset.prompt;
        if (!prompt) return;

        this.input.value = prompt;
        this.autoResizeInput();
        await this.sendCurrentMessage();
      });
    });
  },

  setFormDisabled(disabled) {
    this.input.disabled = disabled;
    if (this.sendBtn) this.sendBtn.disabled = disabled;
  },

  autoResizeInput() {
    if (!this.input) return;

    this.input.style.height = "auto";
    this.input.style.height = `${Math.min(this.input.scrollHeight, 120)}px`;
  },

  scrollToBottom() {
    this.messages.scrollTop = this.messages.scrollHeight;
  },

  makeId() {
    return `chatbot_msg_${Date.now()}_${Math.random().toString(16).slice(2)}`;
  },

  labelize(key) {
    const labels = {
      total: "Total",
      pending: "En attente",
      processing: "En traitement",
      shipped: "Expédiées",
      delivered: "Livrées",
      cancelled: "Annulées",
      total_amount: "Montant total",
      shown: "Affichées",
      shown_this_response: "Affichées ici",
      recommended_count: "Recommandées",
      critical_count: "Critiques",
      high_count: "Priorité élevée",
      medium_count: "Priorité moyenne",
      low_count: "Priorité basse",
      estimated_reorder_value: "Valeur estimée",
      total_products: "Produits totaux",
      estimate_only_count: "Estimations seules",
    };

    return labels[key] || key.replaceAll("_", " ");
  },

  escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  },

  formatError(err) {
    if (!err) return "Erreur inconnue.";

    if (typeof err === "string") return err;

    if (err.errors && typeof err.errors === "object") {
      return Object.entries(err.errors)
        .flatMap(([field, messages]) => {
          const list = Array.isArray(messages) ? messages : [messages];
          return list.map((msg) => `${field}: ${msg}`);
        })
        .join("\n");
    }

    if (err.fields && typeof err.fields === "object") {
      return Object.entries(err.fields)
        .flatMap(([field, messages]) => {
          const list = Array.isArray(messages) ? messages : [messages];
          return list.map((msg) => `${field}: ${msg}`);
        })
        .join("\n");
    }

    return err.message || err.error || "Impossible de contacter l'assistant.";
  },
};

document.addEventListener("DOMContentLoaded", () => {
  RabbitChatbot.init();
});