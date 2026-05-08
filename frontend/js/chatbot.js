/* ============================================================
   chatbot.js — Rabbit frontend chatbot widget
   Calls POST /chatbot/ask
   Uses chatbot.css class system.
   ============================================================ */

const RabbitChatbot = {
  isOpen: false,
  isSending: false,
  debugMode: false,

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

    console.log("RabbitChatbot init:", {
      fab: !!this.fab,
      drawer: !!this.drawer,
      overlay: !!this.overlay,
      closeBtn: !!this.closeBtn,
      form: !!this.form,
      input: !!this.input,
      sendBtn: !!this.sendBtn,
      messages: !!this.messages,
    });

    if (!this.fab || !this.drawer || !this.form || !this.input || !this.messages) {
      console.error("Chatbot cannot start: missing required HTML elements.");
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
      const endpoint = this.debugMode ? "/chatbot/ask?debug=1" : "/chatbot/ask";

      const response = await apiFetchJson(endpoint, {
        method: "POST",
        body: JSON.stringify({ message }),
      });

      const payload = response?.data ?? response;

      const html = this.renderBotResponse(payload, {
        debug: this.debugMode,
      });

      this.replaceMessageHtml(typingId, html, "bot");
      this.bindSuggestedActions();

      if (payload?.result_meta?.ai) {
        console.log("CHATBOT AI META:", payload.result_meta.ai);
      }
    } catch (err) {
      console.error("Chatbot error:", err);

      this.replaceMessageHtml(
        typingId,
        `<div class="chatbot-answer-main">${this.escapeHtml(this.formatError(err))}</div>`,
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

  renderBotResponse(payload = {}, options = {}) {
    const debug = options.debug === true;

    const answer = payload?.answer || "Réponse indisponible.";
    const items = Array.isArray(payload?.items_preview) ? payload.items_preview : [];
    const actions = Array.isArray(payload?.suggested_actions) ? payload.suggested_actions : [];
    const summary = payload?.summary || {};
    const meta = payload?.result_meta || {};
    const sources = Array.isArray(payload?.sources) ? payload.sources : [];
    const limitations = Array.isArray(payload?.limitations) ? payload.limitations : [];

    return `
      <div class="chatbot-answer">
        <div class="chatbot-answer-main">
          ${this.escapeHtml(answer)}
        </div>

        ${items.length ? this.renderItemsPreview(items, payload.intent) : ""}

        ${
          actions.length
            ? `
              <div class="chatbot-suggested-actions">
                ${actions.map((action) => `
                  <button class="chatbot-action-chip chatbot-suggestion-btn" type="button" data-prompt="${this.escapeHtml(action)}">
                    ${this.escapeHtml(action)}
                  </button>
                `).join("")}
              </div>
            `
            : ""
        }

        <details class="chatbot-details">
          <summary>Voir les détails</summary>

          <div class="chatbot-details-body">
            <div><strong>Intent:</strong> ${this.escapeHtml(payload.intent || "—")}</div>
            <div><strong>Confiance:</strong> ${this.escapeHtml(payload.confidence || "—")}</div>
            <div><strong>Rôle:</strong> ${this.escapeHtml(payload.role || "—")}</div>
            <div><strong>Opération:</strong> ${this.escapeHtml(payload.operation_type || "—")}</div>
            <div><strong>Total:</strong> ${this.escapeHtml(meta.total ?? summary.total ?? "—")}</div>
            <div><strong>Affichés:</strong> ${this.escapeHtml(meta.shown ?? summary.shown ?? items.length)}</div>
            <div><strong>Plus de résultats:</strong> ${meta.has_more ? "Oui" : "Non"}</div>

            ${
              sources.length
                ? `
                  <div style="margin-top:8px">
                    <strong>Sources:</strong>
                    <ul>
                      ${sources.map((source) => `
                        <li>${this.escapeHtml(source.tool || "source")} — ${this.escapeHtml(source.status || "used")}</li>
                      `).join("")}
                    </ul>
                  </div>
                `
                : ""
            }

            ${
              limitations.length
                ? `
                  <div style="margin-top:8px">
                    <strong>Limites:</strong>
                    <ul>
                      ${limitations.map((limitation) => `
                        <li>${this.escapeHtml(limitation)}</li>
                      `).join("")}
                    </ul>
                  </div>
                `
                : ""
            }
          </div>
        </details>

        ${debug ? this.renderDebugDetails(payload) : ""}
      </div>
    `;
  },

  renderItemsPreview(items, intent = "") {
    return `
      <div class="chatbot-preview">
        <div class="chatbot-preview-title">Aperçu</div>

        ${items.slice(0, 5).map((item) => {
          if (intent === "order_summary" || item.order_id) {
            return `
              <div class="chatbot-preview-item">
                <div>
                  <strong>Commande #${this.escapeHtml(item.order_id || "—")}</strong>
                  <span>${this.escapeHtml(item.client_name || "Client inconnu")}</span>
                </div>
                <div>
                  <span class="chatbot-status">${this.escapeHtml(item.status || "—")}</span>
                  <strong>${this.formatMoney(item.total_amount)}</strong>
                </div>
              </div>
            `;
          }

          if (item.name || item.product_name || item.sku) {
            return `
              <div class="chatbot-preview-item">
                <div>
                  <strong>${this.escapeHtml(item.name || item.product_name || "Produit")}</strong>
                  <span>${this.escapeHtml(item.sku || item.status || "")}</span>
                </div>
                <div>
                  <span>${this.escapeHtml(item.priority || item.confidence || "")}</span>
                  ${
                    item.estimated_reorder_value != null
                      ? `<strong>${this.formatMoney(item.estimated_reorder_value)}</strong>`
                      : item.current_stock != null
                        ? `<strong>Stock: ${this.escapeHtml(item.current_stock)}</strong>`
                        : ""
                  }
                </div>
              </div>
            `;
          }

          return `
            <div class="chatbot-preview-item">
              <div>
                <strong>Élément</strong>
                <span>${this.escapeHtml(JSON.stringify(item))}</span>
              </div>
            </div>
          `;
        }).join("")}
      </div>
    `;
  },

  renderDebugDetails(payload = {}) {
    const ai = payload?.result_meta?.ai;

    if (!ai) return "";

    return `
      <details class="chatbot-debug">
        <summary>Debug IA</summary>

        <div class="chatbot-debug-body">
          <div><strong>Provider:</strong> ${this.escapeHtml(ai.provider || "—")}</div>
          <div><strong>Model:</strong> ${this.escapeHtml(ai.model || "—")}</div>
          <div><strong>Prompt:</strong> ${this.escapeHtml(ai.prompt_version || "—")}</div>
          <div><strong>Validation:</strong> ${ai.validation_passed ? "Validée" : "Échouée"}</div>
          <div><strong>Fallback:</strong> ${this.escapeHtml(ai.fallback_reason || "—")}</div>
          <div><strong>Input chars:</strong> ${this.escapeHtml(ai.input_char_count ?? "—")}</div>
          <div><strong>Output chars:</strong> ${this.escapeHtml(ai.output_char_count ?? "—")}</div>

          ${
            Array.isArray(ai.validation_violations) && ai.validation_violations.length
              ? `
                <div>
                  <strong>Violations:</strong>
                  <ul>
                    ${ai.validation_violations.map((v) => `<li>${this.escapeHtml(v)}</li>`).join("")}
                  </ul>
                </div>
              `
              : ""
          }
        </div>
      </details>
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

    if (this.sendBtn) {
      this.sendBtn.disabled = disabled;
    }
  },

  autoResizeInput() {
    if (!this.input) return;

    this.input.style.height = "auto";
    this.input.style.height = `${Math.min(this.input.scrollHeight, 120)}px`;
  },

  scrollToBottom() {
    if (!this.messages) return;

    this.messages.scrollTop = this.messages.scrollHeight;
  },

  makeId() {
    return `chatbot_msg_${Date.now()}_${Math.random().toString(16).slice(2)}`;
  },

  formatMoney(value) {
    const n = Number(value);

    if (!Number.isFinite(n)) return "—";

    return `${n.toLocaleString("fr-FR")} MAD`;
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

  escapeHtml(value) {
    const div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  },
};

document.addEventListener("DOMContentLoaded", () => {
  RabbitChatbot.init();
});