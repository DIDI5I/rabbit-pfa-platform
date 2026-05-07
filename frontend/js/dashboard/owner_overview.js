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
      <div class="rb_grid rb_kpi_grid rb_dashboard_row">
        ${this.kpi("Valeur totale du stock", this.moneyMAD(totalValue), "fa-coins", "purple", `${activeProducts} produits actifs`, "neutral")}
        ${this.kpi("Produits à risque", riskProducts, "fa-triangle-exclamation", "orange", `${lowStock} faibles · ${outStock} ruptures`, "down")}
        ${this.kpi("Mouvements stock", general.total_stock_movements ?? "—", "fa-arrows-up-down", "blue", `${general.stock_movements_this_month ?? 0} ce mois`, "neutral")}
        ${this.kpi("Lots d'achat", general.total_purchase_lots ?? "—", "fa-cube", "green", `${general.draft_purchase_lots ?? 0} brouillon(s)`, "up")}
        ${this.kpi("Santé stock", health, "fa-shield-halved", health === "CRITICAL" ? "red" : "green", `${general.stock_ok_count ?? 0} OK / ${totalProducts} produits`, "neutral")}
        ${this.kpi("Réappro recommandé", recommendedCount, "fa-cart-shopping", "purple", estimatedReorderValue ? this.moneyMAD(estimatedReorderValue) : "Aucune valeur estimée", criticalCount > 0 ? "down" : "up")}
      </div>

      <div class="rb_grid rb_three_cols rb_dashboard_row">
        ${this.stockRiskCard(abc)}
        ${this.stockStatusCard(general)}
        ${this.aiCard(forecast)}
      </div>

      <div class="rb_grid rb_three_cols rb_dashboard_row">
        ${this.urgentProductsCard(forecast)}
        ${this.abcSummaryCard(abc)}
        ${this.pipelineCard(general)}
      </div>

      <div class="rb_grid rb_two_cols rb_dashboard_row">
        ${this.movementSummaryCard(general)}
        ${this.topPerformanceCard(performance)}
      </div>
    `;
  },

  kpi(label, value, icon, color, delta, trend) {
    return `
      <div class="rb_kpi_card">
        <div class="rb_kpi_top">
          <div class="rb_kpi_icon ${color}">
            <i class="fas ${icon}"></i>
          </div>
          <div class="rb_kpi_label">${this.safe(label)}</div>
        </div>
        <div class="rb_kpi_value">${value ?? "—"}</div>
        <div class="rb_kpi_delta ${trend || "neutral"}">${this.safe(delta || "")}</div>
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
      .slice(0, 5);

    return `
      <div class="rb_card">
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
      <div class="rb_card">
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
    const global = abc.global || {};
    const classes = abc.classes || {};
    const valueRows = abc?.charts?.value_by_class || [];
    const countRows = abc?.charts?.product_count_by_class || [];

    el.innerHTML = `
      <div class="rb_grid rb_kpi_grid four rb_dashboard_row">
        ${this.kpi("Classe A", classes.A?.product_count ?? "—", "fa-a", "purple", `${global.a_value_share_percent ?? classes.A?.value_share_percent ?? "—"}% de la valeur`, "up")}
        ${this.kpi("Classe B", classes.B?.product_count ?? "—", "fa-b", "orange", `${global.b_value_share_percent ?? classes.B?.value_share_percent ?? "—"}% de la valeur`, "neutral")}
        ${this.kpi("Classe C", classes.C?.product_count ?? "—", "fa-c", "green", `${global.c_value_share_percent ?? classes.C?.value_share_percent ?? "—"}% de la valeur`, "down")}
        ${this.kpi("Concentration", global.gini_coefficient ?? "—", "fa-chart-pie", "blue", global.gini_label || "Indice Gini", "neutral")}
      </div>

      <div class="rb_grid rb_two_cols rb_dashboard_row">
        ${this.abcValueCard(valueRows)}
        ${this.abcCountCard(countRows)}
      </div>

      <div class="rb_grid rb_two_cols rb_dashboard_row">
        ${this.stockRiskCard(abc)}
        ${this.abcProductsTable(classes)}
      </div>
    `;
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

    const performance = await this.fetchSafe("/dashboard/stock/products-performance?period_days=360", {});
    const products = this.uniqueById(performance.products || []);
    const sorted = products.slice().sort((a, b) => Number(b.stock_value ?? 0) - Number(a.stock_value ?? 0));
    const selected = sorted[0] || null;

    el.innerHTML = `
      ${
        selected
          ? this.productHead(selected)
          : this.renderUnavailable("Aucun produit disponible.")
      }

      <div class="rb_grid rb_two_cols rb_dashboard_row" style="margin-top:18px">
        ${this.productPerformanceTable(sorted)}
        ${this.productFlowCard(sorted)}
      </div>
    `;
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

    el.innerHTML = `
      <div class="rb_grid rb_kpi_grid rb_dashboard_row">
        ${this.kpi("Produits analysés", summary.total_products ?? "—", "fa-boxes-stacked", "purple", `${forecast.period_days ?? "—"} jours analysés`, "neutral")}
        ${this.kpi("Réappro recommandé", summary.recommended_count ?? 0, "fa-cart-shopping", "blue", `${this.moneyMAD(summary.estimated_reorder_value)} estimés`, "up")}
        ${this.kpi("Critiques", summary.critical_count ?? 0, "fa-circle-exclamation", "red", "Priorité immédiate", "down")}
        ${this.kpi("Priorité élevée", summary.high_count ?? 0, "fa-triangle-exclamation", "orange", "À surveiller", "down")}
        ${this.kpi("Confiance haute", summary.by_confidence?.HIGH ?? 0, "fa-crosshairs", "green", "Modèles fiables", "up")}
        ${this.kpi("Estimation seule", summary.estimate_only_count ?? 0, "fa-database", "blue", "Historique limité", "neutral")}
      </div>

      <div class="rb_grid rb_three_cols rb_dashboard_row">
        ${this.forecastRecommendationsTable(recommended)}
        ${this.forecastModelCard(summary)}
        ${this.forecastQualityCard(summary)}
      </div>

      <div class="rb_grid rb_two_cols rb_dashboard_row">
        ${this.forecastAllItemsTable(items)}
        ${this.aiCard(forecast)}
      </div>
    `;
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