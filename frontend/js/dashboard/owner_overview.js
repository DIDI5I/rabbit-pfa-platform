/* ============================================================
   owner_overview.js — Rabbit Owner Analytics Dashboard
   Uses owner_analytics.css rb_* class structure
   Embedded inside owner.html existing sidebar/topbar.
   Interface labels are French. Backend field names stay English.
   ============================================================ */

window.OwnerOverviewDashboard = {
  async render(root) {
    root.innerHTML = `
      <section class="analytics_shell">
        <div class="analytics_body">
          <main class="analytics_content">
            ${this.renderToolbar("Vue globale")}

            <div id="analytics_main">
              ${this.renderLoading()}
            </div>

            <div class="rb_footer_hint">
              <i class="fas fa-circle-info"></i>
              Données calculées depuis les endpoints dashboard et stock intelligence.
            </div>
          </main>
        </div>
      </section>
    `;

    this.bindNav(root);
    await this.loadOverview();
  },

  renderToolbar(active) {
    return `
      <div class="analytics_toolbar analytics_toolbar_embedded">
        <div>
          <div class="rb_breadcrumb">
            <span>Tableau de bord</span>
            <i class="fas fa-chevron-right"></i>
            <span class="active" id="analytics_breadcrumb_active">${active}</span>
          </div>

          <div class="analytics_embedded_nav">
            <button class="analytics_nav_btn active" data-view="overview" type="button">Vue globale</button>
            <button class="analytics_nav_btn" data-view="abc" type="button">Analyse ABC</button>
            <button class="analytics_nav_btn" data-view="products" type="button">Produits</button>
            <button class="analytics_nav_btn" data-view="forecasting" type="button">Prévision</button>
            <button class="analytics_nav_btn" data-view="ai" type="button">Assistant IA</button>
          </div>
        </div>

        <div class="rb_toolbar_actions">
          <div class="rb_date_range">
            <span>Période dynamique</span>
            <i class="fas fa-calendar-days"></i>
          </div>
          <button class="rb_square_btn" type="button" onclick="window.print()">
            <i class="fas fa-download"></i>
            Exporter
          </button>
        </div>
      </div>
    `;
  },

  bindNav(root) {
    root.querySelectorAll(".analytics_nav_btn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        root.querySelectorAll(".analytics_nav_btn").forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");

        const view = btn.dataset.view;

        if (view === "overview") await this.loadOverview();
        if (view === "abc") await this.loadABC();
        if (view === "products") await this.loadProducts();
        if (view === "forecasting") await this.loadForecasting();
        if (view === "ai") await this.loadAI();
      });
    });
  },

  setBreadcrumb(label) {
    const el = document.getElementById("analytics_breadcrumb_active");
    if (el) el.textContent = label;
  },

  renderLoading() {
    return `
      <div class="rb_card">
        <div class="empty-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Chargement du dashboard...</p>
        </div>
      </div>
    `;
  },

  renderUnavailable(message = "Données indisponibles.") {
    return `
      <div class="rb_card">
        <div class="empty-state">
          <i class="fas fa-circle-exclamation"></i>
          <p>${this.safe(message)}</p>
        </div>
      </div>
    `;
  },

  async fetchSafe(path, fallback = {}) {
    try {
      const response = await apiFetchJson(path);
      return unwrap(response, fallback);
    } catch (err) {
      console.warn(`Endpoint unavailable: ${path}`, err);
      return fallback;
    }
  },

  async loadOverview() {
  this.setBreadcrumb("Vue globale");

  const el = document.getElementById("analytics_main");
  if (!el) return;

  el.innerHTML = this.renderLoading();

  const [general, abc, performance, forecast] = await Promise.all([
    this.fetchSafe("/dashboard/stock/general", {}),
    this.fetchSafe("/dashboard/stock/abc", {}),
    this.fetchSafe("/dashboard/stock/products-performance?period_days=360", {}),
    this.fetchSafe("/stock/intelligence/reorder-recommendations", {}),
  ]);

  const totalValue = general.total_inventory_value ?? general.total_stock_value;
  const totalProducts = general.total_products ?? "—";
  const activeProducts = general.active_products ?? "—";
  const lowStock = Number(general.low_stock_count ?? 0);
  const outStock = Number(general.out_of_stock_count ?? 0);
  const riskProducts = lowStock + outStock;
  const health = general.stock_health_summary ?? "—";

  const forecastSummary = forecast.summary || {};
  const recommendedCount = forecastSummary.recommended_count ?? 0;
  const criticalCount = forecastSummary.critical_count ?? 0;
  const estimatedReorderValue = forecastSummary.estimated_reorder_value ?? null;

el.innerHTML = `
  <div class="analytics-v2 overview-v2">
    <div class="analytics-page-body">

      <div class="overview-compact-kpis">
        ${this.compactKPI("Valeur stock", this.moneyMAD(totalValue), "fa-coins", "purple", `${activeProducts} actifs`)}
        ${this.compactKPI("À risque", riskProducts, "fa-triangle-exclamation", "orange", `${lowStock} faibles · ${outStock} ruptures`)}
        ${this.compactKPI("Mouvements", general.total_stock_movements ?? "—", "fa-arrows-up-down", "blue", `${general.stock_movements_this_month ?? 0} ce mois`)}
        ${this.compactKPI("Lots achat", general.total_purchase_lots ?? "—", "fa-cube", "green", `${general.draft_purchase_lots ?? 0} brouillon`)}
        ${this.compactKPI("Santé", health, "fa-shield-halved", health === "CRITICAL" ? "red" : "green", `${general.stock_ok_count ?? 0}/${totalProducts} OK`)}
        ${this.compactKPI("Réappro", recommendedCount, "fa-cart-shopping", "purple", estimatedReorderValue ? this.moneyMAD(estimatedReorderValue) : "—")}
        ${this.compactKPI("Critiques", criticalCount, "fa-circle-exclamation", "red", "priorité immédiate")}
      </div>

      <div class="overview-main-grid">
        ${this.stockRiskCard(abc)}
        ${this.stockStatusCard(general)}
      </div>

      <div class="overview-bottom-grid">
        ${this.urgentProductsCard(forecast)}
        ${this.abcSummaryCard(abc)}
        ${this.pipelineCard(general)}
      </div>

    </div>
  </div>
`;
},
compactKPI(label, value, icon, color, sub = "") {
  return `
    <div class="overview-compact-kpi">
      <div class="overview-compact-icon ${color}">
        <i class="fas ${icon}"></i>
      </div>

      <div class="overview-compact-body">
        <div class="overview-compact-label">${this.safe(label)}</div>
        <div class="overview-compact-value">${value ?? "—"}</div>
        <div class="overview-compact-sub">${this.safe(sub || "")}</div>
      </div>
    </div>
  `;
},
  stockRiskCard(abc = {}) {
    const riskRows = abc?.charts?.risk_by_class || [];
    const valueRows = abc?.charts?.value_by_class || [];

    const buildRow = (className) => {
      const risk = riskRows.find((r) => r.class === className) || {};
      const value = valueRows.find((r) => r.class === className) || {};

      const low = Number(risk.low_stock_count ?? 0);
      const out = Number(risk.out_of_stock_count ?? 0);

      const rupturePct = Math.min(100, out * 10);
      const lowPct = Math.min(100 - rupturePct, low * 10);
      const okPct = Math.max(0, 100 - rupturePct - lowPct);

      return this.stackRow(className, [rupturePct, lowPct, okPct, 0], this.moneyMAD(value.value));
    };

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Risque de stock par classe ABC</div>
            <div class="rb_card_sub">Ruptures et stocks faibles par classe</div>
          </div>
        </div>

        <div class="rb_legend">
          <span class="rb_legend_item"><span class="rb_dot red"></span> Rupture</span>
          <span class="rb_legend_item"><span class="rb_dot orange"></span> Faible</span>
          <span class="rb_legend_item"><span class="rb_dot green"></span> OK</span>
        </div>

        ${buildRow("A")}
        ${buildRow("B")}
        ${buildRow("C")}

        <a class="rb_card_link" href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadABC()">
          Voir l'analyse ABC <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  stackRow(label, values, amount) {
    const rupture = Number(values[0] ?? 0);
    const faible = Number(values[1] ?? 0);
    const ok = Number(values[2] ?? 0);
    const surstock = Number(values[3] ?? 0);

    return `
      <div class="rb_stack_row">
        <div class="rb_stack_label">${this.safe(label)}</div>
        <div class="rb_stack_bar">
          <div class="rb_stack_seg" style="width:${rupture}%;background:var(--rb_red)">${rupture ? rupture + "%" : ""}</div>
          <div class="rb_stack_seg" style="width:${faible}%;background:var(--rb_orange)">${faible ? faible + "%" : ""}</div>
          <div class="rb_stack_seg" style="width:${ok}%;background:var(--rb_green)">${ok ? ok + "%" : ""}</div>
          <div class="rb_stack_seg" style="width:${surstock}%;background:var(--rb_violet)">${surstock ? surstock + "%" : ""}</div>
        </div>
        <div class="rb_stack_value">${amount || "—"}</div>
      </div>
    `;
  },

  stockStatusCard(general = {}) {
    const ok = Number(general.stock_ok_count ?? 0);
    const low = Number(general.low_stock_count ?? 0);
    const out = Number(general.out_of_stock_count ?? 0);
    const total = Math.max(1, ok + low + out);

    const okPct = Math.round((ok / total) * 100);
    const lowPct = Math.round((low / total) * 100);
    const outPct = Math.round((out / total) * 100);

    const circumference = 452;
    const okDash = Math.round((okPct / 100) * circumference);
    const lowDash = Math.round((lowPct / 100) * circumference);
    const outDash = Math.round((outPct / 100) * circumference);

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Statut des stocks</div>
            <div class="rb_card_sub">Répartition des références</div>
          </div>
        </div>

        <div class="rb_grid rb_two_cols">
          <div class="rb_chart">
            <svg viewBox="0 0 230 230" role="img" aria-label="Statut des stocks">
              <circle cx="115" cy="115" r="72" fill="none" stroke="#edf0f6" stroke-width="34"/>
              <circle cx="115" cy="115" r="72" fill="none" stroke="var(--rb_green)" stroke-width="34"
                      stroke-dasharray="${okDash} ${circumference}" transform="rotate(-90 115 115)"/>
              <circle cx="115" cy="115" r="72" fill="none" stroke="var(--rb_orange)" stroke-width="34"
                      stroke-dasharray="${lowDash} ${circumference}" stroke-dashoffset="-${okDash}" transform="rotate(-90 115 115)"/>
              <circle cx="115" cy="115" r="72" fill="none" stroke="var(--rb_red)" stroke-width="34"
                      stroke-dasharray="${outDash} ${circumference}" stroke-dashoffset="-${okDash + lowDash}" transform="rotate(-90 115 115)"/>
              <text x="115" y="108" text-anchor="middle" font-size="20" font-weight="900" fill="#292b45">${general.total_products ?? total}</text>
              <text x="115" y="132" text-anchor="middle" font-size="12" fill="#777d96">Produits</text>
            </svg>
          </div>

          <div>
            ${this.statusLine("green", "En stock", `${okPct}%`, ok)}
            ${this.statusLine("orange", "Stock faible", `${lowPct}%`, low)}
            ${this.statusLine("red", "Rupture", `${outPct}%`, out)}
            <div style="height:10px"></div>
            <div class="rb_badge ${general.stock_health_summary === "CRITICAL" ? "red" : "green"}">
              Santé : ${this.safe(general.stock_health_summary || "—")}
            </div>
          </div>
        </div>

        <a class="rb_card_link" href="javascript:void(0)" onclick="showView('inventory')">
          Explorer l'inventaire <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  statusLine(color, label, value, count = null) {
    return `
      <div class="rb_legend_item" style="justify-content:space-between;margin:13px 0">
        <span><span class="rb_dot ${color}"></span> ${this.safe(label)}</span>
        <strong>${this.safe(value)}${count !== null ? ` · ${count}` : ""}</strong>
      </div>
    `;
  },

  urgentProductsCard(forecast = {}) {
    const items = Array.isArray(forecast.items) ? forecast.items : [];
    const urgent = items
      .filter((i) => i.recommendation === true || ["CRITICAL", "HIGH"].includes(i.priority))
      .slice(0, 3);

    return `
      <div class="rb_card overview-scroll-card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Produits urgents</div>
            <div class="rb_card_sub">${urgent.length} produit(s) à réapprovisionner</div>
          </div>
          <span class="rb_badge red">Prioritaire</span>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Priorité</th>
              <th>Stock</th>
              <th>Qté</th>
              <th>Valeur</th>
            </tr>
          </thead>
          <tbody>
            ${
              urgent.length
                ? urgent.map((i) => `
                  <tr>
                    <td>
                      <strong>${this.safe(i.name || "—")}</strong>
                      <div style="font-size:11px;color:#8a90a8">${this.safe(i.sku || "")}</div>
                    </td>
                    <td><span class="rb_badge ${i.priority === "CRITICAL" ? "red" : "orange"}">${this.safe(i.priority)}</span></td>
                    <td>${i.current_stock ?? "—"}</td>
                    <td>${i.recommended_reorder_quantity ?? "—"}</td>
                    <td>${this.moneyMAD(i.estimated_reorder_value)}</td>
                  </tr>
                `).join("")
                : `<tr><td colspan="5">Aucune recommandation urgente.</td></tr>`
            }
          </tbody>
        </table>

        <a class="rb_card_link" href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadForecasting()">
          Voir toutes les recommandations <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  abcSummaryCard(abc = {}) {
    const global = abc?.global || {};
    const classes = abc?.classes || {};

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Synthèse ABC</div>
            <div class="rb_card_sub">${this.safe(global.gini_label || "Concentration de valeur")}</div>
          </div>
          <span class="rb_badge purple">Gini ${global.gini_coefficient ?? "—"}</span>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Classe</th>
              <th>Produits</th>
              <th>Valeur</th>
              <th>% valeur</th>
              <th>Risque</th>
            </tr>
          </thead>
          <tbody>
            ${["A", "B", "C"].map((cls) => {
              const c = classes[cls] || {};
              const risk = Number(c.low_stock_count ?? 0) + Number(c.out_of_stock_count ?? 0);

              return `
                <tr>
                  <td><strong>${cls}</strong></td>
                  <td>${c.product_count ?? "—"}</td>
                  <td>${this.moneyMAD(c.inventory_value)}</td>
                  <td>${c.value_share_percent ?? "—"}%</td>
                  <td><span class="rb_badge ${risk > 0 ? "orange" : "green"}">${risk}</span></td>
                </tr>
              `;
            }).join("")}
          </tbody>
        </table>

        <a class="rb_card_link" href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadABC()">
          Détails ABC <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  pipelineCard(general = {}) {
    return `
      <div class="rb_card overview-scroll-card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Pipeline d'approvisionnement</div>
            <div class="rb_card_sub">Lots d'achat et finalisation</div>
          </div>
        </div>

        ${this.pipelineItem("fa-file-invoice", "Lots totaux", general.total_purchase_lots ?? "—", "Tous statuts")}
        ${this.pipelineItem("fa-pen", "Brouillons", general.draft_purchase_lots ?? "—", "À finaliser")}
        ${this.pipelineItem("fa-circle-check", "Finalisés", general.finalized_purchase_lots ?? "—", "Stock reçu")}
        ${this.pipelineItem("fa-ban", "Annulés", general.cancelled_purchase_lots ?? "—", "Non actifs")}

        <div style="border:1px solid #d7cdfb;border-radius:13px;padding:12px;margin-top:12px">
          <div style="display:flex;justify-content:space-between;font-weight:900">
            <span>Mouvements ce mois</span>
            <span>${general.stock_movements_this_month ?? 0}</span>
          </div>
          <div class="rb_progress" style="margin-top:10px">
            <span style="width:${Math.min(100, Number(general.stock_movements_this_month ?? 0) * 4)}%"></span>
          </div>
        </div>

        <a class="rb_card_link" href="javascript:void(0)" onclick="showView('lots')">
          Voir les lots d'achat <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  pipelineItem(icon, label, count, value) {
    return `
      <div class="rb_pipeline_item">
        <i class="fas ${icon}"></i>
        <div class="rb_pipeline_name">${this.safe(label)}</div>
        <div class="rb_pipeline_count">${count}</div>
        <div class="rb_pipeline_value">${this.safe(value)}</div>
        <i class="fas fa-chevron-right" style="color:#9da3b8"></i>
      </div>
    `;
  },

  movementSummaryCard(general = {}) {
    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Mouvements de stock</div>
            <div class="rb_card_sub">Entrées / sorties globales</div>
          </div>
        </div>

        <div class="rb_grid rb_three_cols">
          ${this.miniStat("Entrées", general.stock_in_movements ?? 0, "green")}
          ${this.miniStat("Sorties", general.stock_out_movements ?? 0, "red")}
          ${this.miniStat("Ce mois", general.stock_movements_this_month ?? 0, "purple")}
        </div>

        <div style="height:16px"></div>

        <div class="rb_grid rb_two_cols">
          ${this.miniStat("Entrées ce mois", general.stock_in_movements_this_month ?? 0, "green")}
          ${this.miniStat("Sorties ce mois", general.stock_out_movements_this_month ?? 0, "orange")}
        </div>

        <a class="rb_card_link" href="javascript:void(0)" onclick="showView('movements')">
          Voir les mouvements <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  miniStat(label, value, color) {
    return `
      <div style="background:#fafbff;border:1px solid #edf0f6;border-radius:14px;padding:14px">
        <div class="rb_badge ${color}" style="margin-bottom:10px">${this.safe(label)}</div>
        <div style="font-size:24px;font-weight:900;color:#343852">${value}</div>
      </div>
    `;
  },

  topPerformanceCard(performance = {}) {
    const products = this.uniqueById(performance.products || []);
    const sorted = products
      .slice()
      .sort((a, b) => Number(b.stock_value ?? 0) - Number(a.stock_value ?? 0))
      .slice(0, 6);

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Top valeur stock</div>
            <div class="rb_card_sub">Produits avec plus forte valeur immobilisée</div>
          </div>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Stock</th>
              <th>Valeur</th>
              <th>Rotation</th>
            </tr>
          </thead>
          <tbody>
            ${
              sorted.length
                ? sorted.map((p) => `
                  <tr>
                    <td>
                      <strong>${this.safe(p.name || "—")}</strong>
                      <div style="font-size:11px;color:#8a90a8">${this.safe(p.sku || "")}</div>
                    </td>
                    <td>${p.current_stock ?? "—"}</td>
                    <td>${this.moneyMAD(p.stock_value)}</td>
                    <td>${p.stock_rotation_rate ?? "—"}</td>
                  </tr>
                `).join("")
                : `<tr><td colspan="4">Aucune donnée performance.</td></tr>`
            }
          </tbody>
        </table>

        <a class="rb_card_link" href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadProducts()">
          Voir la performance produit <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    `;
  },

  aiCard(forecast = {}) {
    const summary = forecast.summary || {};

    return `
      <div class="rb_card rb_ai_card">
        <div class="rb_ai_head">
          <span><i class="fas fa-wand-magic-sparkles"></i> Assistant IA</span>
          <span>${summary.recommended_count ?? 0}</span>
        </div>
        <div class="rb_ai_body">
          ${this.aiItem("fa-triangle-exclamation", `${summary.critical_count ?? 0} produits critiques`, "Réapprovisionnement prioritaire requis.", "Maintenant", "red")}
          ${this.aiItem("fa-cart-shopping", `${summary.recommended_count ?? 0} recommandations`, `${this.moneyMAD(summary.estimated_reorder_value)} estimés.`, "Stock", "purple")}
          ${this.aiItem("fa-chart-simple", `${summary.high_count ?? 0} priorité élevée`, "À vérifier dans la vue prévision.", "Prévision", "orange")}
          ${this.aiItem("fa-database", `${summary.estimate_only_count ?? 0} estimations faibles`, "Historique insuffisant pour certains produits.", "Qualité", "blue")}
        </div>
      </div>
    `;
  },

  aiItem(icon, title, text, time, color) {
    const colorMap = {
      red: ["#ffe6e9", "var(--rb_red)"],
      green: ["#e7f8ec", "var(--rb_green)"],
      blue: ["#e8f2ff", "var(--rb_blue)"],
      orange: ["#fff0d9", "var(--rb_orange)"],
      purple: ["var(--rb_purple_100)", "var(--rb_purple_700)"],
    };

    const [bg, fg] = colorMap[color] || colorMap.purple;

    return `
      <div class="rb_ai_item">
        <div class="rb_ai_icon" style="background:${bg};color:${fg}">
          <i class="fas ${icon}"></i>
        </div>
        <div>
          <strong>${this.safe(title)}</strong>
          <div class="rb_ai_text">${this.safe(text)}</div>
        </div>
        <div class="rb_ai_time">${this.safe(time)}</div>
      </div>
    `;
  },

 async loadABC() {
  this.setBreadcrumb("Analyse ABC");

  const el = document.getElementById("analytics_main");
  if (!el) return;

  el.innerHTML = this.renderLoading();

  const abc = await this.fetchSafe("/dashboard/stock/abc", {});

  window.RabbitABCDashboard.render(el, abc);
},


  abcValueCard(rows = []) {
    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Valeur par classe ABC</div>
            <div class="rb_card_sub">Part de valeur d'inventaire</div>
          </div>
        </div>

        ${rows.map((r) => `
          <div style="display:grid;grid-template-columns:80px 1fr 90px;gap:12px;align-items:center;margin:14px 0">
            <strong>${this.safe(r.label || r.class)}</strong>
            <div class="rb_progress">
              <span style="width:${Number(r.value_share_percent ?? 0)}%"></span>
            </div>
            <strong style="font-size:12px">${r.value_share_percent ?? "—"}%</strong>
          </div>
        `).join("")}

        <table class="rb_table">
          <thead><tr><th>Classe</th><th>Valeur</th><th>Part</th></tr></thead>
          <tbody>
            ${rows.map((r) => `
              <tr>
                <td>${this.safe(r.label || r.class)}</td>
                <td>${this.moneyMAD(r.value)}</td>
                <td>${r.value_share_percent ?? "—"}%</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    `;
  },

  abcCountCard(rows = []) {
    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Nombre de produits par classe</div>
            <div class="rb_card_sub">Distribution des références</div>
          </div>
        </div>

        <table class="rb_table">
          <thead><tr><th>Classe</th><th>Produits</th></tr></thead>
          <tbody>
            ${rows.map((r) => `
              <tr>
                <td><strong>${this.safe(r.label || r.class)}</strong></td>
                <td>${r.value ?? "—"}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>

        <div class="rb_chart">
          <svg viewBox="0 0 520 180">
            ${rows.map((r, index) => {
              const max = Math.max(...rows.map((x) => Number(x.value || 0)), 1);
              const h = Math.round((Number(r.value || 0) / max) * 120);
              const x = 90 + index * 120;
              const y = 150 - h;
              const color = index === 0 ? "var(--rb_purple_600)" : index === 1 ? "var(--rb_orange)" : "var(--rb_green)";
              return `
                <rect x="${x}" y="${y}" width="60" height="${h}" rx="8" fill="${color}"></rect>
                <text x="${x + 30}" y="170" text-anchor="middle" font-size="12" fill="#626986">${this.safe(r.class)}</text>
                <text x="${x + 30}" y="${y - 8}" text-anchor="middle" font-size="12" font-weight="900" fill="#343852">${r.value}</text>
              `;
            }).join("")}
          </svg>
        </div>
      </div>
    `;
  },

  abcProductsTable(classes = {}) {
    const products = this.uniqueById([
      ...(classes.A?.products || []),
      ...(classes.B?.products || []),
      ...(classes.C?.products || []),
    ])
      .sort((a, b) => Number(b.stock_value ?? 0) - Number(a.stock_value ?? 0))
      .slice(0, 8);

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Produits dominants ABC</div>
            <div class="rb_card_sub">Top références par valeur stock</div>
          </div>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Classe</th>
              <th>Stock</th>
              <th>Valeur</th>
            </tr>
          </thead>
          <tbody>
            ${products.map((p) => `
              <tr>
                <td>
                  <strong>${this.safe(p.name || "—")}</strong>
                  <div style="font-size:11px;color:#8a90a8">${this.safe(p.sku || "")}</div>
                </td>
                <td><span class="rb_badge purple">${this.safe(p.abc_class || "—")}</span></td>
                <td>${p.current_stock ?? "—"}</td>
                <td>${this.moneyMAD(p.stock_value)}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    `;
  },

  async loadProducts() {
  this.setBreadcrumb("Produits");

  const el = document.getElementById("analytics_main");
  if (!el) return;

  el.innerHTML = this.renderLoading();

  const periodDays = this.productPeriodDays || 360;
const performance = await this.fetchSafe(`/dashboard/stock/products-performance?period_days=${periodDays}`, {});
  const products = this.uniqueById(performance.products || []);

  const sortedByValue = products
    .slice()
    .sort((a, b) => Number(b.stock_value ?? 0) - Number(a.stock_value ?? 0));
    const selectedId = this.selectedProductId || sortedByValue[0]?.id || sortedByValue[0]?.product_id || null;

  const selected =
  sortedByValue.find((p) => String(p.id ?? p.product_id) === String(selectedId)) ||
  sortedByValue[0] ||
  null;
  
  if (!selected) {
    el.innerHTML = this.renderUnavailable("Aucune donnée produit disponible.");
    return;
  }

  const lowStockProducts = products.filter((p) => p.stock_status === "LOW_STOCK");
  const outOfStockProducts = products.filter((p) => p.stock_status === "OUT_OF_STOCK");
  const okProducts = products.filter((p) => p.stock_status === "OK");

  const totalStockValue = products.reduce((sum, p) => sum + Number(p.stock_value ?? 0), 0);
  const totalMovements = products.reduce((sum, p) => sum + Number(p.movement_count ?? 0), 0);
  const totalOutQty = products.reduce((sum, p) => sum + Number(p.stock_out_quantity ?? 0), 0);

  el.innerHTML = `
    <div class="analytics-v2">
      <div class="analytics-page-header">
        <nav class="breadcrumb">
          <a href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadOverview()">Vue globale</a>
          <span class="sep">›</span>
          <span class="current">Produits</span>
        </nav>

        <div class="toolbar product-toolbar">
          ${this.renderProductToolbarSelector(sortedByValue, selected)}

          <select class="date-range-btn product-period-select" id="product-period-select" onchange="window.OwnerOverviewDashboard.changeProductPeriod(this.value)">
            <option value="30" ${String(this.productPeriodDays || 360) === "30" ? "selected" : ""}>30 jours</option>
            <option value="90" ${String(this.productPeriodDays || 360) === "90" ? "selected" : ""}>90 jours</option>
            <option value="180" ${String(this.productPeriodDays || 360) === "180" ? "selected" : ""}>180 jours</option>
            <option value="360" ${String(this.productPeriodDays || 360) === "360" ? "selected" : ""}>360 jours</option>
          </select>

          <button class="export-btn" type="button" onclick="window.print()">Exporter</button>
        </div>
      </div>

      <div class="analytics-page-body">
       
        ${this.renderProductHeader(selected)}

        ${this.renderProductRecommendationChips(selected)}

        <div class="product-main-row">
          ${this.renderProductStockEvolution(selected, products)}
          ${this.renderProductValueStructure(selected)}
          ${this.renderSelectedProductRiskPanel(selected)}
        </div>

        <div class="product-bottom-row">
          ${this.renderProductMovementPanel(products)}
          ${this.renderProductStockRiskPanel(products)}
          ${this.renderProductPipelinePanel(products)}
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Indicateurs clés</span>
          </div>

          <div class="product-kpi-row">
            ${this.renderProductSmallKPI("Produits analysés", products.length, "Vue globale", "neutral")}
            ${this.renderProductSmallKPI("Valeur de stock", AnalyticsShared.fmt.currency(totalStockValue), "Total immobilisé", "up")}
            ${this.renderProductSmallKPI("Mouvements", totalMovements, "Entrées / sorties", "neutral")}
            ${this.renderProductSmallKPI("Sorties totales", totalOutQty, "Quantité consommée", "down")}
            ${this.renderProductSmallKPI("Produits à risque", lowStockProducts.length + outOfStockProducts.length, `${lowStockProducts.length} faibles · ${outOfStockProducts.length} ruptures`, "warning")}
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">Performance détaillée des produits</span>
          </div>

          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Produit</th>
                  <th>Catégorie</th>
                  <th>Stock</th>
                  <th>Valeur stock</th>
                  <th>Rotation</th>
                  <th>Couverture</th>
                  <th>Flux stock</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                ${this.renderProductPerformanceRows(sortedByValue)}
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  `;

  setTimeout(() => {
    this.renderProductCharts(selected, products);
  }, 50);
},
renderSelectedProductRiskPanel(product = {}) {
  const status = String(product.stock_status || "OK").toUpperCase();

  const currentStock = Number(product.current_stock ?? 0);
  const threshold = Number(product.low_stock_threshold ?? product.reorder_point ?? 0);
  const averageStock = Number(product.average_stock ?? 0);
  const coverageDays = Number(product.stock_coverage_days ?? 0);

  let riskPct = 0;

  if (status === "OUT_OF_STOCK") {
    riskPct = 100;
  } else if (status === "LOW_STOCK") {
    riskPct = 65;
  } else if (threshold > 0 && currentStock <= threshold) {
    riskPct = 65;
  } else if (coverageDays > 0 && coverageDays < 15) {
    riskPct = 45;
  } else if (averageStock > 0 && currentStock < averageStock * 0.5) {
    riskPct = 35;
  } else {
    riskPct = 10;
  }

  const okPct = Math.max(0, 100 - riskPct);
  const lowPct = status === "LOW_STOCK" ? riskPct : riskPct > 0 && riskPct < 100 ? riskPct : 0;
  const outPct = status === "OUT_OF_STOCK" ? 100 : 0;

  return `
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Risque du produit sélectionné</span>
      </div>

      <div class="legend">
        <div class="legend-item">
          <span class="legend-dot" style="background:var(--ok)"></span>
          <span class="legend-label">Niveau sain estimé</span>
          <span class="legend-value">${this.formatDecimal(okPct)}%</span>
        </div>

        <div class="legend-item">
          <span class="legend-dot" style="background:var(--low)"></span>
          <span class="legend-label">Risque stock faible</span>
          <span class="legend-value">${this.formatDecimal(lowPct)}%</span>
        </div>

        <div class="legend-item">
          <span class="legend-dot" style="background:var(--rupture)"></span>
          <span class="legend-label">Risque rupture</span>
          <span class="legend-value">${this.formatDecimal(outPct)}%</span>
        </div>
      </div>

      <div style="margin-top:14px">
        <div class="stacked-bar">
          <div class="seg" style="width:${okPct}%;background:var(--ok)"></div>
          <div class="seg" style="width:${lowPct}%;background:var(--low)"></div>
          <div class="seg" style="width:${outPct}%;background:var(--rupture)"></div>
        </div>
      </div>

      <div style="margin-top:14px;font-size:0.76rem;color:var(--text-secondary);line-height:1.45">
        Stock actuel : <strong>${currentStock}</strong><br>
        Seuil / point de réappro. : <strong>${threshold || "—"}</strong><br>
        Couverture : <strong>${coverageDays ? this.formatDecimal(coverageDays) + " j" : "—"}</strong>
      </div>

      <div style="margin-top:12px">
        ${AnalyticsShared.statusChip(product.stock_status)}
      </div>
    </div>
  `;
},
renderProductToolbarSelector(products = [], selected = {}) {
  return `
    <select class="date-range-btn product-analysis-toolbar-select" id="product-analysis-select" onchange="window.OwnerOverviewDashboard.changeAnalysedProduct(this.value)">
      ${products.map((p) => {
        const id = p.id ?? p.product_id;
        const selectedId = selected.id ?? selected.product_id;

        return `
          <option value="${id}" ${String(id) === String(selectedId) ? "selected" : ""}>
            ${AnalyticsShared.esc(p.name || "Produit")} — ${AnalyticsShared.esc(p.sku || "SKU")}
          </option>
        `;
      }).join("")}
    </select>
  `;
},

async changeAnalysedProduct(productId) {
  this.selectedProductId = productId;
  await this.loadProducts();
},

async changeProductPeriod(periodDays) {
  this.productPeriodDays = Number(periodDays) || 360;
  await this.loadProducts();
},
renderProductSelector(products = [], selected = {}) {
  return `
    <div class="panel product-selector-panel">
      <div class="panel-header">
        <span class="panel-title">Produit analysé</span>
      </div>

      <select class="filter-select" id="product-analysis-select" onchange="window.OwnerOverviewDashboard.changeAnalysedProduct(this.value)">
        ${products.map((p) => {
          const id = p.id ?? p.product_id;
          const selectedId = selected.id ?? selected.product_id;

          return `
            <option value="${id}" ${String(id) === String(selectedId) ? "selected" : ""}>
              ${AnalyticsShared.esc(p.name || "Produit")} — ${AnalyticsShared.esc(p.sku || "SKU")}
            </option>
          `;
        }).join("")}
      </select>
    </div>
  `;
},

async changeAnalysedProduct(productId) {
  this.selectedProductId = productId;
  await this.loadProducts();
},
renderProductHeader(product) {
  return `
    <div class="product-header-card">
      <div class="product-image">📦</div>

      <div class="product-identity">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
          <h2>${AnalyticsShared.esc(product.name || "Produit")}</h2>
        </div>

        <div class="product-meta-row">
          <div class="product-meta-item">
            <span class="lbl">SKU</span>
            <span class="val" style="font-family:var(--font-mono)">
              ${AnalyticsShared.esc(product.sku || "—")}
            </span>
          </div>

          <div class="product-meta-item">
            <span class="lbl">Catégorie</span>
            <span class="val">${AnalyticsShared.esc(this.productCategoryLabel(product.category))}</span>
          </div>

          <div class="product-meta-item">
            <span class="lbl">Stock actuel</span>
            <span class="val">${product.current_stock ?? "—"} u.</span>
          </div>

          <div class="product-meta-item">
            <span class="lbl">Stock moyen</span>
            <span class="val">${this.formatDecimal(product.average_stock)} u.</span>
          </div>

          <div class="product-meta-item">
            <span class="lbl">Mouvements</span>
            <span class="val">${product.movement_count ?? "—"}</span>
          </div>

          <div class="product-meta-item">
            <span class="lbl">Sorties</span>
            <span class="val">${product.stock_out_quantity ?? "—"} u.</span>
          </div>
        </div>
      </div>

      <div class="product-header-right">
        ${AnalyticsShared.statusChip(product.stock_status)}

        <div>
          <div class="stock-lbl">Valeur de stock</div>
          <div class="stock-val">${AnalyticsShared.fmt.currency(product.stock_value)}</div>
        </div>

        <div>
          <div class="stock-lbl">Rotation</div>
          <div class="service-rate">${this.formatDecimal(product.stock_rotation_rate)}</div>
        </div>
      </div>
    </div>
  `;
},

renderProductRecommendationChips(product) {
  const status = String(product.stock_status || "OK").toUpperCase();
  const coverageDays = Number(product.stock_coverage_days ?? 0);
  const rotation = Number(product.stock_rotation_rate ?? 0);

  const stockChip =
    status === "OUT_OF_STOCK"
      ? {
          cls: "warn",
          icon: "⚠️",
          title: "Rupture",
          sub: "Réapprovisionnement prioritaire",
        }
      : status === "LOW_STOCK"
        ? {
            cls: "warn",
            icon: "⚠️",
            title: "Stock faible",
            sub: "Surveiller le seuil",
          }
        : {
            cls: "ok",
            icon: "✅",
            title: "Stock OK",
            sub: "Niveau disponible",
          };

  const coverageChip =
    coverageDays > 0
      ? {
          cls: coverageDays < 15 ? "warn" : "info",
          icon: "📆",
          title: "Couverture",
          sub: `${this.formatDecimal(coverageDays)} jours`,
        }
      : {
          cls: "warn",
          icon: "📆",
          title: "Couverture",
          sub: "Non calculée",
        };

  const rotationChip =
    rotation > 0
      ? {
          cls: "info",
          icon: "📈",
          title: "Rotation",
          sub: `${this.formatDecimal(rotation)} x/an`,
        }
      : {
          cls: "warn",
          icon: "📉",
          title: "Rotation",
          sub: "Faible ou nulle",
        };

  const chips = [stockChip, coverageChip, rotationChip];

  return `
    <div class="reco-chips">
      ${chips.map((chip) => `
        <div class="reco-chip ${chip.cls}">
          <span class="reco-icon">${chip.icon}</span>
          <div class="reco-text">
            <div class="title">${AnalyticsShared.esc(chip.title)}</div>
            <div class="sub">${AnalyticsShared.esc(chip.sub)}</div>
          </div>
        </div>
      `).join("")}
    </div>
  `;
},

renderProductStockEvolution(product, products) {
  return `
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Évolution du stock</span>
        <div class="tab-row">
          <button class="tab-btn active" type="button">360 j</button>
        </div>
      </div>

      <div style="display:flex;gap:12px;font-size:0.65rem;color:var(--text-muted);margin-bottom:8px">
        <span style="display:flex;align-items:center;gap:4px">
          <span style="width:14px;height:2px;background:var(--primary-light);display:inline-block"></span>
          Stock estimé
        </span>
        <span style="display:flex;align-items:center;gap:4px">
          <span style="width:14px;height:1px;border-top:2px dashed var(--primary-light);display:inline-block;height:0"></span>
          Projection
        </span>
      </div>

      <div style="font-size:0.68rem;color:var(--text-muted);text-align:right;margin-bottom:4px">(unités)</div>

      <div id="product-stock-sparkline"></div>

      <div style="display:flex;justify-content:space-between;font-size:0.62rem;color:var(--text-muted);margin-top:4px">
        <span>Début</span>
        <span>Milieu</span>
        <span style="color:var(--primary);font-weight:600">Actuel</span>
        <span>Projection</span>
      </div>
    </div>
  `;
},

renderProductValueStructure(product) {
  const stockValue = Number(product.stock_value ?? 0);
  const currentStock = Number(product.current_stock ?? 0);
  const averageStock = Number(product.average_stock ?? 0);
  const outQty = Number(product.stock_out_quantity ?? 0);
  const inQty = Number(product.stock_in_quantity ?? 0);

  return `
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Structure stock / valeur</span>
      </div>

      <div style="display:flex;align-items:center;gap:var(--space-lg)">
        <div id="product-value-donut" style="flex-shrink:0"></div>

        <div class="legend">
          <div class="legend-item">
            <span class="legend-dot" style="background:var(--primary-light)"></span>
            <span class="legend-label">Valeur stock</span>
            <span class="legend-value">${AnalyticsShared.fmt.currency(stockValue)}</span>
          </div>

          <div class="legend-item">
            <span class="legend-dot" style="background:var(--ok)"></span>
            <span class="legend-label">Stock actuel</span>
            <span class="legend-value">${currentStock}</span>
          </div>

          <div class="legend-item">
            <span class="legend-dot" style="background:var(--info)"></span>
            <span class="legend-label">Stock moyen</span>
            <span class="legend-value">${this.formatDecimal(averageStock)}</span>
          </div>

          <div class="legend-item">
            <span class="legend-dot" style="background:var(--rupture)"></span>
            <span class="legend-label">Sorties</span>
            <span class="legend-value">${outQty}</span>
          </div>

          <div class="legend-item">
            <span class="legend-dot" style="background:var(--low)"></span>
            <span class="legend-label">Entrées</span>
            <span class="legend-value">${inQty}</span>
          </div>
        </div>
      </div>
    </div>
  `;
},

renderProductClassificationPanel(products = []) {
  const ok = products.filter((p) => p.stock_status === "OK").length;
  const low = products.filter((p) => p.stock_status === "LOW_STOCK").length;
  const out = products.filter((p) => p.stock_status === "OUT_OF_STOCK").length;
  const total = products.length || 1;
  const riskClass = this.getPortfolioRiskClass(low, out, total);
  return `
    <div class="panel portfolio-status-panel ${riskClass}">
      <div class="panel-header">
        <span class="panel-title">Statut du portefeuille</span>
      </div>

      <div class="legend">
        <div class="legend-item">
          <span class="legend-dot" style="background:var(--ok)"></span>
          <span class="legend-label">En stock</span>
          <span class="legend-value">${ok}</span>
          <span class="legend-pct">${this.formatDecimal((ok / total) * 100)}%</span>
        </div>

        <div class="legend-item">
          <span class="legend-dot" style="background:var(--low)"></span>
          <span class="legend-label">Stock faible</span>
          <span class="legend-value">${low}</span>
          <span class="legend-pct">${this.formatDecimal((low / total) * 100)}%</span>
        </div>

        <div class="legend-item">
          <span class="legend-dot" style="background:var(--rupture)"></span>
          <span class="legend-label">Rupture</span>
          <span class="legend-value">${out}</span>
          <span class="legend-pct">${this.formatDecimal((out / total) * 100)}%</span>
        </div>
      </div>

      <div style="margin-top:14px">
        <div class="stacked-bar">
          <div class="seg" style="width:${(ok / total) * 100}%;background:var(--ok)"></div>
          <div class="seg" style="width:${(low / total) * 100}%;background:var(--low)"></div>
          <div class="seg" style="width:${(out / total) * 100}%;background:var(--rupture)"></div>
        </div>
      </div>

      <div style="margin-top:12px">
        <a href="javascript:void(0)" onclick="showView('inventory')" class="panel-link">
          Voir l'inventaire ${AnalyticsShared.arrowRightIcon()}
        </a>
      </div>
    </div>
  `;
},

renderProductMovementPanel(products = []) {
  const sorted = products
    .slice()
    .sort((a, b) => Number(b.movement_count ?? 0) - Number(a.movement_count ?? 0))
    .slice(0, 5);

  return `
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Mouvements récents / actifs</span>
      </div>

      ${sorted.map((p) => `
        <div class="movement-item">
          <span class="movement-dot" style="background:${p.stock_status === "OUT_OF_STOCK" ? "var(--rupture)" : p.stock_status === "LOW_STOCK" ? "var(--low)" : "var(--ok)"}"></span>

          <div style="flex:1">
            <div style="font-weight:500;font-size:0.73rem">
              ${AnalyticsShared.esc(p.name || "—")}
            </div>
            <div style="font-size:0.65rem;color:var(--text-muted)">
              ${AnalyticsShared.esc(p.sku || "")}
            </div>
          </div>

          <div style="text-align:right">
            <div style="font-weight:700;font-family:var(--font-mono);font-size:0.73rem">
              ${p.movement_count ?? 0}
            </div>
            <div style="font-size:0.62rem;color:var(--text-muted)">
              mouvements
            </div>
          </div>
        </div>
      `).join("")}

      <div style="margin-top:8px">
        <a href="javascript:void(0)" onclick="showView('movements')" class="panel-link">
          Voir tous les mouvements ${AnalyticsShared.arrowRightIcon()}
        </a>
      </div>
    </div>
  `;
},

renderProductStockRiskPanel(products = []) {
  const risky = products
    .filter((p) => p.stock_status === "LOW_STOCK" || p.stock_status === "OUT_OF_STOCK")
    .sort((a, b) => Number(b.stock_value ?? 0) - Number(a.stock_value ?? 0))
    .slice(0, 6);

  return `
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Produits à risque</span>
      </div>

      <table class="data-table" style="font-size:0.72rem">
        <thead>
          <tr>
            <th>Produit</th>
            <th>Stock</th>
            <th>Valeur</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody>
          ${
            risky.length
              ? risky.map((p) => `
                <tr>
                  <td>
                    <div class="product-name-cell">
                      <div class="product-thumb">${AnalyticsShared.esc((p.sku || "SKU").slice(0, 3))}</div>
                      <div class="product-meta">
                        <span class="name">${AnalyticsShared.esc(p.name || "—")}</span>
                        <span class="sku">${AnalyticsShared.esc(p.sku || "")}</span>
                      </div>
                    </div>
                  </td>
                  <td>${p.current_stock ?? "—"}</td>
                  <td>${AnalyticsShared.fmt.currency(p.stock_value)}</td>
                  <td>${AnalyticsShared.statusChip(p.stock_status)}</td>
                </tr>
              `).join("")
              : `<tr><td colspan="4">Aucun produit à risque.</td></tr>`
          }
        </tbody>
      </table>

      <div style="margin-top:8px">
        <a href="javascript:void(0)" onclick="showView('alerts')" class="panel-link">
          Voir les alertes stock ${AnalyticsShared.arrowRightIcon()}
        </a>
      </div>
    </div>
  `;
},

renderProductPipelinePanel(products = []) {
  const topValue = products
    .slice()
    .sort((a, b) => Number(b.stock_value ?? 0) - Number(a.stock_value ?? 0))
    .slice(0, 4);

  return `
    <div class="panel">
      <div class="panel-header">
        <span class="panel-title">Pipeline produits / achats</span>
      </div>

      <div class="pipeline-list">
        ${topValue.map((p) => `
          <div class="pipeline-item">
            <div class="pipeline-item-left">
              <div class="product-thumb">${AnalyticsShared.esc((p.sku || "SKU").slice(0, 3))}</div>
              <div>
                <div class="pipeline-item-label">${AnalyticsShared.esc(p.name || "—")}</div>
                <div style="font-size:0.65rem;color:var(--text-muted)">
                  ${AnalyticsShared.esc(p.sku || "")}
                </div>
              </div>
            </div>

            <div class="pipeline-item-right">
              <span class="pipeline-count">${p.current_stock ?? "—"} u.</span>
              <span class="pipeline-amount">${AnalyticsShared.fmt.currency(p.stock_value)}</span>
            </div>
          </div>
        `).join("")}
      </div>

      <div class="pipeline-progress">
        <div class="pipeline-progress-bar" style="width:72%"></div>
      </div>

      <div style="margin-top:8px">
        <a href="javascript:void(0)" onclick="showView('lots')" class="panel-link">
          Voir les lots d'achat ${AnalyticsShared.arrowRightIcon()}
        </a>
      </div>
    </div>
  `;
},

renderProductSmallKPI(label, value, sub, cls = "neutral") {
  return `
    <div class="kpi-card" style="padding:var(--space-md)">
      <div class="kpi-label">${AnalyticsShared.esc(label)}</div>
      <div class="kpi-value" style="font-size:1.3rem">${value ?? "—"}</div>
      <div class="kpi-sub ${cls}">${AnalyticsShared.esc(sub || "")}</div>
    </div>
  `;
},

renderProductPerformanceRows(products = []) {
  if (!products.length) {
    return `<tr><td colspan="8">Aucun produit à afficher.</td></tr>`;
  }

  return products.slice(0, 20).map((p) => `
    <tr>
      <td>
        <div class="product-name-cell">
          <div class="product-thumb">${AnalyticsShared.esc((p.sku || "SKU").slice(0, 3))}</div>
          <div class="product-meta">
            <span class="name">${AnalyticsShared.esc(p.name || "—")}</span>
            <span class="sku">${AnalyticsShared.esc(p.sku || "")}</span>
          </div>
        </div>
      </td>
      <td>${AnalyticsShared.esc(this.productCategoryLabel(p.category))}</td>
      <td>${p.current_stock ?? "—"}</td>
      <td>${AnalyticsShared.fmt.currency(p.stock_value)}</td>
      <td>${this.formatDecimal(p.stock_rotation_rate)}</td>
      <td>${AnalyticsShared.fmt.days(p.stock_coverage_days)}</td>
      <td>${AnalyticsShared.fmt.days(p.stock_flow_time_days)}</td>
      <td>${AnalyticsShared.statusChip(p.stock_status)}</td>
    </tr>
  `).join("");
},

renderProductCharts(product, products = []) {
  const sparklineEl = document.getElementById("product-stock-sparkline");
  const donutEl = document.getElementById("product-value-donut");

  if (sparklineEl) {
    const current = Number(product.current_stock ?? 0);
    const average = Number(product.average_stock ?? current);

    const values = [
      average * 1.2,
      average * 1.05,
      average * 0.96,
      average * 1.1,
      average,
      average * 0.92,
      current,
      current * 1.02,
      current * 1.05,
      current * 1.08,
    ].map((v) => ({ v: Math.max(0, v) }));

    AnalyticsShared.renderSparkline(sparklineEl, values, {
      width: 380,
      height: 90,
      color: "var(--primary-light)",
      areaColor: "var(--accent-soft)",
      forecast: 6,
    });
  }

  if (donutEl) {
    const currentStock = Number(product.current_stock ?? 0);
    const averageStock = Number(product.average_stock ?? 0);
    const outQty = Number(product.stock_out_quantity ?? 0);
    const inQty = Number(product.stock_in_quantity ?? 0);

    const donutSegments = [
      { label: "Stock actuel", value: currentStock, color: "var(--ok)" },
      { label: "Stock moyen", value: averageStock, color: "var(--info)" },
      { label: "Sorties", value: outQty, color: "var(--rupture)" },
      { label: "Entrées", value: inQty, color: "var(--low)" },
    ].filter((segment) => Number(segment.value) > 0);

    AnalyticsShared.renderDonut(
      donutEl,
      donutSegments,
      {
        val: AnalyticsShared.fmt.currency(product.stock_value).replace(" MAD", ""),
        lbl: "Valeur",
      },
      110
    );
  }
},

getPortfolioRiskClass(low, out, total) {
  const riskPct = ((Number(low) + Number(out)) / Math.max(1, Number(total))) * 100;

  if (out > 0 || riskPct >= 20) return "danger";
  if (riskPct >= 8) return "warning";
  return "healthy";
},
productCategoryLabel(category) {
  const labels = {
    assembly: "Assemblage",
    sub_assembly: "Sous-assemblage",
    component: "Composant",
    raw_material: "Matière première",
  };

  return labels[category] || category || "—";
},

formatDecimal(value, decimals = 1) {
  if (value == null || value === "—") return "—";

  const n = Number(value);
  if (!Number.isFinite(n)) return "—";

  return n.toFixed(decimals).replace(".", ",");
},

  productHead(product) {
    return `
      <div class="rb_card">
        <div class="rb_product_head">
          <div class="rb_product_img">
            <i class="fas fa-box"></i>
          </div>

          <div>
            <div class="rb_product_title">${this.safe(product.name || "Produit")}</div>
            <div class="rb_meta_grid">
              <div><div class="rb_meta_label">SKU</div><div class="rb_meta_value">${this.safe(product.sku || "—")}</div></div>
              <div><div class="rb_meta_label">Catégorie</div><div class="rb_meta_value">${this.categoryLabel(product.category)}</div></div>
              <div><div class="rb_meta_label">Stock actuel</div><div class="rb_meta_value">${product.current_stock ?? "—"}</div></div>
              <div><div class="rb_meta_label">Stock moyen</div><div class="rb_meta_value">${product.average_stock ?? "—"}</div></div>
              <div><div class="rb_meta_label">Mouvements</div><div class="rb_meta_value">${product.movement_count ?? "—"}</div></div>
            </div>
          </div>

          <div class="rb_product_side">
            <div><div class="rb_meta_label">Statut</div>${this.stockStatusBadge(product.stock_status)}</div>
            <div><div class="rb_meta_label">Valeur stock</div><div class="rb_meta_value">${this.moneyMAD(product.stock_value)}</div></div>
            <div><div class="rb_meta_label">Rotation</div><div class="rb_meta_value">${product.stock_rotation_rate ?? "—"}</div></div>
          </div>
        </div>

        <div class="rb_reco_row">
          <strong>Indicateurs</strong>
          <div class="rb_reco_pill green"><i class="fas fa-cubes-stacked"></i> Stock : ${product.current_stock ?? "—"}</div>
          <div class="rb_reco_pill purple"><i class="fas fa-clock"></i> Couverture : ${this.days(product.stock_coverage_days)}</div>
          <div class="rb_reco_pill blue"><i class="fas fa-arrow-right-arrow-left"></i> Flux : ${this.days(product.stock_flow_time_days)}</div>
        </div>
      </div>
    `;
  },

  productPerformanceTable(products = []) {
    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Performance produit</div>
            <div class="rb_card_sub">Rotation, couverture et valeur stock</div>
          </div>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Stock</th>
              <th>Valeur</th>
              <th>Rotation</th>
              <th>Couverture</th>
            </tr>
          </thead>
          <tbody>
            ${products.slice(0, 12).map((p) => `
              <tr>
                <td>
                  <strong>${this.safe(p.name || "—")}</strong>
                  <div style="font-size:11px;color:#8a90a8">${this.safe(p.sku || "")}</div>
                </td>
                <td>${p.current_stock ?? "—"}</td>
                <td>${this.moneyMAD(p.stock_value)}</td>
                <td>${p.stock_rotation_rate ?? "—"}</td>
                <td>${this.days(p.stock_coverage_days)}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    `;
  },

  productFlowCard(products = []) {
    const topFlow = products
      .slice()
      .sort((a, b) => Number(b.stock_out_quantity ?? 0) - Number(a.stock_out_quantity ?? 0))
      .slice(0, 8);

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Consommation / sorties</div>
            <div class="rb_card_sub">Produits avec le plus de sorties</div>
          </div>
        </div>

        ${topFlow.map((p) => `
          <div style="display:grid;grid-template-columns:1fr 80px 90px;gap:12px;align-items:center;margin:13px 0">
            <span style="font-size:12px;font-weight:800;color:#626986">${this.safe(p.name || "—")}</span>
            <strong>${p.stock_out_quantity ?? 0}</strong>
            <span class="rb_badge ${p.stock_status === "OUT_OF_STOCK" ? "red" : p.stock_status === "LOW_STOCK" ? "orange" : "green"}">${this.safe(p.stock_status || "OK")}</span>
          </div>
        `).join("")}
      </div>
    `;
  },

  async loadForecasting() {
  this.setBreadcrumb("Prévision");

  const el = document.getElementById("analytics_main");
  if (!el) return;

  el.innerHTML = this.renderLoading();

  const forecast = await this.fetchSafe("/stock/intelligence/reorder-recommendations", {});

  const summary = forecast.summary || {};
  const items = Array.isArray(forecast.items) ? forecast.items : [];
  const recommended = items.filter((i) => i.recommendation === true);

  const critical = Number(summary.critical_count ?? 0);
  const high = Number(summary.high_count ?? 0);
  const medium = Number(summary.medium_count ?? 0);
  const recommendedCount = Number(summary.recommended_count ?? recommended.length ?? 0);
  const estimatedValue = summary.estimated_reorder_value ?? 0;

    el.innerHTML = `
    <div class="analytics-v2 forecasting-v2">
      <div class="analytics-page-header">
        <nav class="breadcrumb">
          <a href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadOverview()">Vue globale</a>
          <span class="sep">›</span>
          <span class="current">Prévision</span>
        </nav>

        <div class="toolbar">
          <button class="date-range-btn" type="button">
            Horizon : ${forecast.period_days ?? 30} jours
          </button>
          <button class="export-btn" type="button" onclick="window.print()">Exporter</button>
        </div>
      </div>

      <div class="analytics-page-body">

        <div class="forecast-kpi-row">
          ${this.renderForecastKPI("Produits analysés", summary.total_products ?? items.length, "📦", `${forecast.period_days ?? "—"} jours analysés`, "neutral")}
          ${this.renderForecastKPI("Réappro. recommandé", recommendedCount, "🛒", AnalyticsShared.fmt.currency(estimatedValue), "up")}
          ${this.renderForecastKPI("Critiques", critical, "⚠️", "Priorité immédiate", critical > 0 ? "down" : "neutral")}
          ${this.renderForecastKPI("Priorité élevée", high, "🔥", "À surveiller", high > 0 ? "warning" : "neutral")}
          ${this.renderForecastKPI("Confiance haute", summary.by_confidence?.HIGH ?? 0, "🎯", "Modèles fiables", "up")}
          ${this.renderForecastKPI("Estimations seules", summary.estimate_only_count ?? 0, "🧮", "Historique limité", "neutral")}
        </div>

        <div class="forecast-top-grid">

          <div class="panel forecast-control-panel">
            <div class="panel-header">
              <span class="panel-title">Horizon</span>
            </div>

            <div class="forecast-control-block">
              <div class="filter-section-title">Horizon de prévision</div>
              <select class="filter-select">
                <option>${forecast.period_days ?? 30} jours</option>
                <option>60 jours</option>
                <option>90 jours</option>
              </select>
            </div>

            <div class="forecast-control-block">
              <div class="filter-section-title">Modèle</div>
              <div class="model-status-card forecast-model-compact">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px">
                  <span class="model-name">Stock Intelligence V1</span>
                  <span class="model-active-badge">● Actif</span>
                </div>
                <div class="model-sub">Multi-modèles contrôlés par le backend</div>
              </div>
            </div>

            <div class="forecast-control-block">
              <div class="filter-section-title">Qualité des données</div>
              <div class="forecast-quality-compact">
                ${this.renderForecastQualityBadges(summary)}
              </div>
            </div>

            <div class="forecast-control-block">
              <div class="filter-section-title">Priorités</div>
              <div class="forecast-priority-compact">
                <div class="forecast-mini-line">
                  <span><span class="legend-dot" style="background:var(--rupture)"></span> Critique</span>
                  <strong>${critical}</strong>
                </div>
                <div class="forecast-mini-line">
                  <span><span class="legend-dot" style="background:var(--low)"></span> Élevée</span>
                  <strong>${high}</strong>
                </div>
                <div class="forecast-mini-line">
                  <span><span class="legend-dot" style="background:var(--info)"></span> Moyenne</span>
                  <strong>${medium}</strong>
                </div>
              </div>
            </div>
          </div>

          <div class="panel forecast-demand-panel">
            <div class="panel-header">
              <span class="panel-title">Prévision de la demande</span>
            </div>

            <div style="display:flex;gap:12px;font-size:0.65rem;margin-bottom:8px;flex-wrap:wrap">
              <span style="display:flex;align-items:center;gap:4px;color:var(--text-muted)">
                <span style="width:14px;height:2px;background:var(--primary-light);display:inline-block"></span>
                Historique estimé
              </span>
              <span style="display:flex;align-items:center;gap:4px;color:var(--text-muted)">
                <span style="width:14px;height:1px;border-top:2px dashed var(--primary-light);display:inline-block;height:0"></span>
                Projection
              </span>
            </div>

            <div id="forecast-demand-chart"></div>

            <div style="display:flex;justify-content:space-between;font-size:0.6rem;color:var(--text-muted);margin-top:4px">
              <span>S-6</span>
              <span>S-5</span>
              <span>S-4</span>
              <span>S-3</span>
              <span>S-2</span>
              <span>S-1</span>
              <span style="color:var(--primary);font-weight:600">Actuel</span>
              <span>S+1</span>
              <span>S+2</span>
              <span>S+3</span>
            </div>
          </div>

          <div class="panel forecast-model-panel">
            <div class="panel-header">
              <span class="panel-title">Répartition par modèle</span>
            </div>
            ${this.renderForecastModelBreakdown(summary)}
          </div>

          <div class="panel forecast-reco-panel">
            <div class="panel-header">
              <span class="panel-title">Recommandations</span>
            </div>

            <div class="forecast-reco-inner">
              <div id="forecast-reco-donut" style="flex-shrink:0"></div>
              <div class="legend" id="forecast-reco-legend"></div>
            </div>

            <div style="margin-top:10px">
              <a href="javascript:void(0)" class="panel-link">
                Voir toutes les recommandations ${AnalyticsShared.arrowRightIcon()}
              </a>
            </div>
          </div>

        </div>

        <div class="forecast-second-row">
          <div class="panel">
            <div class="panel-header">
              <span class="panel-title">Risque de rupture attendu</span>
            </div>
            <div class="rupture-chart" id="forecast-risk-bars"></div>
          </div>

          <div class="panel">
            <div class="panel-header">
              <span class="panel-title">Confiance des prévisions</span>
            </div>
            ${this.renderForecastConfidence(summary)}
          </div>

          <div class="panel">
            <div class="panel-header">
              <span class="panel-title">Insights IA</span>
            </div>
            ${this.renderForecastInsights(summary, recommended)}
          </div>
        </div>

        <div class="panel forecast-risk-table-panel">
          <div class="panel-header">
            <span class="panel-title">Top produits à risque — action requise</span>
          </div>

          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Produit</th>
                  <th>Stock actuel</th>
                  <th>Point de réappro.</th>
                  <th>Qté recommandée</th>
                  <th>Valeur estimée</th>
                  <th>Priorité</th>
                  <th>Confiance</th>
                  <th>Modèle</th>
                </tr>
              </thead>
              <tbody>
                ${this.renderForecastRiskRows(items)}
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  `;
  setTimeout(() => {
    this.renderForecastCharts(summary, items);
  }, 50);
},

renderForecastKPI(label, value, icon, sub, cls = "neutral") {
  return `
    <div class="kpi-card">
      <div class="kpi-label">
        <span class="kpi-icon">${AnalyticsShared.esc(icon)}</span>
        ${AnalyticsShared.esc(label)}
      </div>
      <div class="kpi-value">${value ?? "—"}</div>
      <div class="kpi-sub ${cls}">${AnalyticsShared.esc(sub || "")}</div>
    </div>
  `;
},

renderForecastQualityBadges(summary = {}) {
  const flags = summary.data_quality_flags || {};
  const entries = Object.entries(flags);

  if (!entries.length) {
    return `<span class="chip chip-ok">Aucun problème majeur</span>`;
  }

  return `
    <div style="display:flex;flex-direction:column;gap:6px">
      ${entries.map(([flag, count]) => `
        <div class="legend-item">
          <span class="legend-label">${AnalyticsShared.esc(flag)}</span>
          <span class="legend-value">${count}</span>
        </div>
      `).join("")}
    </div>
  `;
},

renderForecastModelBreakdown(summary = {}) {
  const byModel = summary.by_model || {};
  const entries = Object.entries(byModel);

  if (!entries.length) {
    return `<p style="font-size:0.78rem;color:var(--text-muted)">Aucune donnée modèle disponible.</p>`;
  }

  return `
    <div class="legend">
      ${entries.map(([model, count]) => `
        <div class="legend-item">
          <span class="legend-label">${AnalyticsShared.esc(this.modelLabel(model))}</span>
          <span class="legend-value">${count}</span>
        </div>
      `).join("")}
    </div>
  `;
},

renderForecastConfidence(summary = {}) {
  const confidence = summary.by_confidence || {};
  const entries = Object.entries(confidence);

  if (!entries.length) {
    return `<p style="font-size:0.78rem;color:var(--text-muted)">Aucune donnée de confiance.</p>`;
  }

  return `
    <div class="legend">
      ${entries.map(([level, count]) => `
        <div class="legend-item">
          <span class="legend-label">${AnalyticsShared.confidenceChip(level)}</span>
          <span class="legend-value">${count}</span>
        </div>
      `).join("")}
    </div>
  `;
},

renderForecastInsights(summary = {}, recommended = []) {
  const critical = Number(summary.critical_count ?? 0);
  const estimatedValue = summary.estimated_reorder_value ?? 0;
  const estimateOnly = Number(summary.estimate_only_count ?? 0);

  return `
    <div style="display:flex;flex-direction:column;gap:8px;font-size:0.75rem">
      <div style="display:flex;gap:6px;line-height:1.4">
        <span>⚠️</span>
        <span style="color:var(--text-secondary)">
          ${critical} produit(s) critique(s) nécessitent une vérification rapide.
        </span>
      </div>

      <div style="display:flex;gap:6px;line-height:1.4">
        <span>🛒</span>
        <span style="color:var(--text-secondary)">
          Valeur estimée du réapprovisionnement : ${AnalyticsShared.fmt.currency(estimatedValue)}.
        </span>
      </div>

      <div style="display:flex;gap:6px;line-height:1.4">
        <span>🧮</span>
        <span style="color:var(--text-secondary)">
          ${estimateOnly} produit(s) utilisent une estimation simple à cause d'un historique limité.
        </span>
      </div>

      <div style="display:flex;gap:6px;line-height:1.4">
        <span>📦</span>
        <span style="color:var(--text-secondary)">
          ${recommended.length} produit(s) ont une recommandation active.
        </span>
      </div>
    </div>
  `;
},

renderForecastRiskRows(items = []) {
  const sorted = items
    .slice()
    .sort((a, b) => {
      const order = { CRITICAL: 4, HIGH: 3, MEDIUM: 2, LOW: 1, NONE: 0 };
      return (order[b.priority] || 0) - (order[a.priority] || 0);
    })
    .slice(0, 12);

  if (!sorted.length) {
    return `<tr><td colspan="8">Aucun produit à afficher.</td></tr>`;
  }

  return sorted.map((item) => `
    <tr>
      <td>
        <div class="product-name-cell">
          <div class="product-thumb">${AnalyticsShared.esc((item.sku || "SKU").slice(0, 3))}</div>
          <div class="product-meta">
            <span class="name">${AnalyticsShared.esc(item.name || "—")}</span>
            <span class="sku">${AnalyticsShared.esc(item.sku || "")}</span>
          </div>
        </div>
      </td>
      <td>${item.current_stock ?? "—"}</td>
      <td>${item.reorder_point ?? "—"}</td>
      <td>${item.recommended_reorder_quantity ?? "—"}</td>
      <td>${AnalyticsShared.fmt.currency(item.estimated_reorder_value)}</td>
      <td>${AnalyticsShared.priorityChip(item.priority)}</td>
      <td>${AnalyticsShared.confidenceChip(item.confidence)}</td>
      <td>${AnalyticsShared.esc(this.modelLabel(item.selected_model))}</td>
    </tr>
  `).join("");
},

renderForecastCharts(summary = {}, items = []) {
  const demandEl = document.getElementById("forecast-demand-chart");
  const donutEl = document.getElementById("forecast-reco-donut");
  const legendEl = document.getElementById("forecast-reco-legend");
  const riskEl = document.getElementById("forecast-risk-bars");

  const recommended = Number(summary.recommended_count ?? 0);
  const critical = Number(summary.critical_count ?? 0);
  const high = Number(summary.high_count ?? 0);
  const medium = Number(summary.medium_count ?? 0);
  const none = Math.max(0, Number(summary.total_products ?? items.length ?? 0) - recommended);

  const estimatedValue = Number(summary.estimated_reorder_value ?? 0);
  const base = estimatedValue > 0 ? estimatedValue : recommended * 1000;

  const sparkData = [
    base * 0.62,
    base * 0.72,
    base * 0.68,
    base * 0.81,
    base * 0.76,
    base * 0.91,
    base,
    base * 1.06,
    base * 1.1,
    base * 1.14,
  ].map((v) => ({ v }));

  AnalyticsShared.renderSparkline(demandEl, sparkData, {
    width: 520,
    height: 110,
    color: "var(--primary-light)",
    areaColor: "rgba(79,45,140,0.06)",
    forecast: 6,
  });

  const segments = [
    { label: "Critique", value: critical, color: "var(--rupture)" },
    { label: "Élevé", value: high, color: "var(--low)" },
    { label: "Moyen", value: medium, color: "var(--info)" },
    { label: "OK", value: none, color: "var(--ok)" },
  ];

  AnalyticsShared.renderDonut(donutEl, segments, {
    val: recommended,
    lbl: "À commander",
  }, 110);

  if (legendEl) {
    const total = segments.reduce((sum, s) => sum + s.value, 0) || 1;

    legendEl.innerHTML = segments.map((s) => `
      <div class="legend-item">
        <span class="legend-dot" style="background:${s.color}"></span>
        <span class="legend-label">${AnalyticsShared.esc(s.label)}</span>
        <span class="legend-value">${s.value}</span>
        <span class="legend-pct">${((s.value / total) * 100).toFixed(1).replace(".", ",")}%</span>
      </div>
    `).join("");
  }

  if (riskEl) {
    const bars = [
      { week: "S+1", high: critical * 4, med: high * 3, low: 50 },
      { week: "S+2", high: critical * 3, med: high * 2, low: 55 },
      { week: "S+3", high: critical * 2, med: high * 2, low: 60 },
      { week: "S+4", high: critical * 2, med: high, low: 65 },
      { week: "S+5", high: critical, med: medium * 2, low: 70 },
      { week: "S+6", high: critical, med: medium, low: 75 },
    ];

    riskEl.innerHTML = bars.map((bar) => {
      const highHeight = Math.min(60, Math.max(4, bar.high));
      const medHeight = Math.min(40, Math.max(4, bar.med));
      const lowHeight = Math.min(25, Math.max(4, bar.low * 0.2));

      return `
        <div class="rupture-bar-group">
          <div class="rupture-bar" style="height:${highHeight}px;background:var(--rupture)"></div>
          <div class="rupture-bar" style="height:${medHeight}px;background:var(--low)"></div>
          <div class="rupture-bar" style="height:${lowHeight}px;background:var(--ok)"></div>
          <div class="rupture-week">${bar.week}</div>
        </div>
      `;
    }).join("");
  }
},


  forecastRecommendationsTable(items = []) {
    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Réapprovisionnements recommandés</div>
            <div class="rb_card_sub">${items.length} produit(s)</div>
          </div>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Priorité</th>
              <th>Qté</th>
              <th>Valeur</th>
            </tr>
          </thead>
          <tbody>
            ${
              items.length
                ? items.map((i) => `
                  <tr>
                    <td>
                      <strong>${this.safe(i.name || "—")}</strong>
                      <div style="font-size:11px;color:#8a90a8">${this.safe(i.sku || "")}</div>
                    </td>
                    <td><span class="rb_badge ${i.priority === "CRITICAL" ? "red" : "orange"}">${this.safe(i.priority || "—")}</span></td>
                    <td>${i.recommended_reorder_quantity ?? "—"}</td>
                    <td>${this.moneyMAD(i.estimated_reorder_value)}</td>
                  </tr>
                `).join("")
                : `<tr><td colspan="4">Aucune recommandation.</td></tr>`
            }
          </tbody>
        </table>
      </div>
    `;
  },

  forecastModelCard(summary = {}) {
    const byModel = summary.by_model || {};

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Modèles utilisés</div>
            <div class="rb_card_sub">Répartition des méthodes</div>
          </div>
        </div>

        ${Object.entries(byModel).map(([model, count]) => `
          <div style="display:grid;grid-template-columns:1fr 44px;gap:12px;align-items:center;margin:13px 0">
            <span style="font-size:12px;font-weight:800;color:#626986">${this.modelLabel(model)}</span>
            <strong>${count}</strong>
          </div>
        `).join("") || `<p style="font-size:13px;color:#8a90a8">Aucune donnée modèle.</p>`}
      </div>
    `;
  },

  forecastQualityCard(summary = {}) {
    const flags = summary.data_quality_flags || {};
    const confidence = summary.by_confidence || {};

    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Qualité des données</div>
            <div class="rb_card_sub">Confiance et limites</div>
          </div>
        </div>

        <div style="margin-bottom:14px">
          ${Object.entries(confidence).map(([label, count]) => `
            <span class="rb_badge ${label === "HIGH" ? "green" : label === "MEDIUM" ? "purple" : label === "LOW" ? "orange" : "gray"}" style="margin:3px">
              ${this.safe(label)}: ${count}
            </span>
          `).join("")}
        </div>

        <table class="rb_table">
          <thead><tr><th>Flag</th><th>#</th></tr></thead>
          <tbody>
            ${Object.entries(flags).map(([flag, count]) => `
              <tr><td>${this.safe(flag)}</td><td>${count}</td></tr>
            `).join("") || `<tr><td colspan="2">Aucun flag.</td></tr>`}
          </tbody>
        </table>
      </div>
    `;
  },

  forecastAllItemsTable(items = []) {
    return `
      <div class="rb_card">
        <div class="rb_card_header">
          <div>
            <div class="rb_card_title">Tous les signaux de prévision</div>
            <div class="rb_card_sub">Top produits à surveiller</div>
          </div>
        </div>

        <table class="rb_table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Stock</th>
              <th>ROP</th>
              <th>Confiance</th>
              <th>Modèle</th>
            </tr>
          </thead>
          <tbody>
            ${items.slice(0, 10).map((i) => `
              <tr>
                <td>
                  <strong>${this.safe(i.name || "—")}</strong>
                  <div style="font-size:11px;color:#8a90a8">${this.safe(i.sku || "")}</div>
                </td>
                <td>${i.current_stock ?? "—"}</td>
                <td>${i.reorder_point ?? "—"}</td>
                <td><span class="rb_badge ${i.confidence === "HIGH" ? "green" : i.confidence === "MEDIUM" ? "purple" : i.confidence === "LOW" ? "orange" : "gray"}">${this.safe(i.confidence || "—")}</span></td>
                <td>${this.modelLabel(i.selected_model)}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    `;
  },

  async loadAI() {
    this.setBreadcrumb("Assistant IA");

    const el = document.getElementById("analytics_main");
    if (!el) return;

    el.innerHTML = this.renderLoading();

    const forecast = await this.fetchSafe("/stock/intelligence/reorder-recommendations", {});

    el.innerHTML = `
      <div class="rb_grid rb_two_cols rb_dashboard_row">
        ${this.aiCard(forecast)}
        ${this.urgentProductsCard(forecast)}
      </div>
    `;
  },

  uniqueById(items = []) {
    const map = new Map();

    items.forEach((item) => {
      const key = item.id ?? item.product_id ?? `${item.sku}-${item.name}`;
      if (!map.has(key)) {
        map.set(key, item);
      }
    });

    return Array.from(map.values());
  },

  moneyMAD(value) {
    if (value == null || value === "—") return "—";

    const n = Number(value);
    if (!Number.isFinite(n)) return "—";

    if (n >= 1000000) {
      return `${(n / 1000000).toFixed(2).replace(".", ",")} M MAD`;
    }

    return `${n.toLocaleString("fr-FR")} MAD`;
  },

  days(value) {
    if (value == null || value === "—") return "—";
    const n = Number(value);
    if (!Number.isFinite(n)) return "—";
    return `${n.toFixed(1).replace(".", ",")} j`;
  },

  percent(value) {
    if (value == null || value === "—") return "—";
    const n = Number(value);
    if (!Number.isFinite(n)) return "—";
    return `${n.toFixed(1).replace(".", ",")}%`;
  },

  categoryLabel(category) {
    const labels = {
      assembly: "Assemblage",
      sub_assembly: "Sous-assemblage",
      component: "Composant",
      raw_material: "Matière première",
    };

    return labels[category] || this.safe(category || "—");
  },

  stockStatusBadge(status) {
    const normalized = String(status || "OK").toUpperCase();

    if (normalized === "OUT_OF_STOCK") {
      return `<span class="rb_badge red">Rupture</span>`;
    }

    if (normalized === "LOW_STOCK") {
      return `<span class="rb_badge orange">Stock faible</span>`;
    }

    return `<span class="rb_badge green">OK</span>`;
  },

  modelLabel(model) {
    const labels = {
      threshold_only: "Seuil uniquement",
      criticality_only: "Criticité uniquement",
      moving_average: "Moyenne mobile",
      croston_sba: "Croston/SBA",
      simple_exponential_smoothing: "Lissage exponentiel",
    };

    return labels[model] || this.safe(model || "—");
  },

  safe(value) {
    if (value == null) return "";
    return esc(String(value));
  },
};