/* ============================================================
   abc_dashboard.js — Rabbit ABC Analytics Dashboard
   Uses:
   - AnalyticsShared
   - AnalyticsLayout
   - backend GET /dashboard/stock/abc
   ============================================================ */

window.RabbitABCDashboard = {
  render(root, abc = {}) {
    const A = abc?.classes?.A || {};
    const B = abc?.classes?.B || {};
    const C = abc?.classes?.C || {};
    const global = abc?.global || {};
    const charts = abc?.charts || {};

    const valueRows = charts.value_by_class || [];
    const countRows = charts.product_count_by_class || [];
    const riskRows = charts.risk_by_class || [];

    root.innerHTML = AnalyticsLayout.page({
      current: "Analyse ABC",
      period: "Données ABC actuelles",
      body: `
        <div class="abc-top-cards">
            ${this.renderHeaderCard("A", A, "badge-a")}
            ${this.renderHeaderCard("B", B, "badge-b")}
            ${this.renderHeaderCard("C", C, "badge-c")}

            <div class="abc-header-card">
            <div class="abc-class-badge badge-n">Σ</div>
            <div class="abc-card-label">Produits totaux</div>
            <div class="abc-card-skus">${global.total_products ?? "—"}</div>
            <div class="abc-card-pct">Toutes classes confondues</div>
            <div class="abc-card-delta kpi-sub neutral">Inventaire analysé</div>
            </div>

            <div class="abc-header-card">
            <div class="kpi-label" style="margin-bottom:6px">Valeur totale du stock</div>
            <div class="abc-card-skus" style="font-size:1.4rem">
                ${AnalyticsShared.fmt.currency(global.total_inventory_value)}
            </div>
            <div class="abc-card-delta kpi-sub neutral" style="margin-top:4px">
                Base ABC
            </div>
            </div>

            <div class="abc-header-card">
            <div class="kpi-label" style="margin-bottom:6px">Concentration</div>
            <div class="abc-card-skus" style="font-size:1.4rem">
                ${global.gini_coefficient ?? "—"}
            </div>
            <div class="abc-card-delta kpi-sub warning" style="margin-top:4px">
                ${AnalyticsShared.esc(global.gini_label || "Indice Gini")}
            </div>
            </div>
        </div>

        <div class="abc-middle-row">
            ${this.renderParetoPanel()}
            ${this.renderRiskPanel()}
            ${this.renderActionsPanel(global, A)}
        </div>

        <div class="abc-bottom-row">
            ${this.renderSynthesisPanel(global, { A, B, C })}
            ${this.renderMatrixPanel(riskRows)}
        </div>
        `,
    });

    setTimeout(() => {
      this.renderCharts(valueRows, countRows, riskRows);
    }, 50);
  },

  renderHeaderCard(cls, data, badgeClass) {
    return `
      <div class="abc-header-card">
        <div class="abc-class-badge ${badgeClass}">${cls}</div>
        <div class="abc-card-label">Classe ${cls}</div>
        <div class="abc-card-skus">${data.product_count ?? "—"}</div>
        <div style="font-size:0.68rem;color:var(--text-muted);margin-top:1px">Produits</div>
        <div class="abc-card-pct" style="margin-top:4px">
          ${data.value_share_percent ?? "—"}% de la valeur stock
        </div>
        <div class="abc-card-delta kpi-sub neutral">
          ${AnalyticsShared.fmt.currency(data.inventory_value)}
        </div>
      </div>
    `;
  },

  renderParetoPanel() {
    return AnalyticsLayout.panel(
      "Contribution ABC par valeur",
      `
        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:10px">
          Courbe de Pareto basée sur la valeur d'inventaire par classe.
        </div>

        <div id="abc-pareto-chart"></div>

        <div style="margin-top:10px">
          <a href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadProducts()" class="panel-link">
            Voir le détail par produit ${AnalyticsShared.arrowRightIcon()}
          </a>
        </div>
      `
    );
  },

  renderRiskPanel() {
    return AnalyticsLayout.panel(
      "Risque de stock par classe ABC",
      `
        <div style="display:flex;gap:12px;font-size:0.68rem;color:var(--text-muted);margin-bottom:12px;flex-wrap:wrap">
          <span style="display:flex;align-items:center;gap:4px">
            <span style="width:8px;height:8px;background:var(--ok);border-radius:2px;display:inline-block"></span>
            En stock
          </span>
          <span style="display:flex;align-items:center;gap:4px">
            <span style="width:8px;height:8px;background:var(--low);border-radius:2px;display:inline-block"></span>
            Faible
          </span>
          <span style="display:flex;align-items:center;gap:4px">
            <span style="width:8px;height:8px;background:var(--rupture);border-radius:2px;display:inline-block"></span>
            Rupture
          </span>
        </div>

        <div id="abc-risk-chart"></div>

        <div style="margin-top:10px">
          <a href="javascript:void(0)" onclick="showView('inventory')" class="panel-link">
            Voir le détail des risques ${AnalyticsShared.arrowRightIcon()}
          </a>
        </div>
      `
    );
  },

  renderSynthesisPanel(global, classes) {
    const rows = [
      this.renderSynthesisRow("A", classes.A, "badge-a"),
      this.renderSynthesisRow("B", classes.B, "badge-b"),
      this.renderSynthesisRow("C", classes.C, "badge-c"),
      `
        <tr style="font-weight:700;border-top:2px solid var(--border)">
          <td>Total</td>
          <td>${global.total_products ?? "—"}</td>
          <td>${AnalyticsShared.fmt.currency(global.total_inventory_value)}</td>
          <td>100%</td>
          <td>—</td>
          <td>—</td>
        </tr>
      `,
    ];

    return AnalyticsLayout.panel(
      "Synthèse des classes ABC",
      AnalyticsLayout.table(
        ["Classe", "# Produits", "Valeur stock", "% Valeur", "Stock moyen", "Risque"],
        rows,
        "Aucune donnée ABC."
      )
    );
  },

  renderSynthesisRow(cls, data, badgeClass) {
    const risk = Number(data.low_stock_count ?? 0) + Number(data.out_of_stock_count ?? 0);

    return `
      <tr>
        <td>
          <div class="abc-class-badge ${badgeClass}" style="width:24px;height:24px;font-size:0.75rem;display:inline-flex">
            ${cls}
          </div>
        </td>
        <td style="font-weight:600">${data.product_count ?? "—"}</td>
        <td style="font-family:var(--font-mono);font-size:0.75rem">
          ${AnalyticsShared.fmt.currency(data.inventory_value)}
        </td>
        <td>${data.value_share_percent ?? "—"}%</td>
        <td>${data.avg_current_stock ?? "—"}</td>
        <td>
          <span class="chip ${risk > 0 ? "chip-low" : "chip-ok"}">${risk}</span>
        </td>
      </tr>
    `;
  },

  renderMatrixPanel(riskRows) {
    const rows = [
      this.renderRiskMatrixRow("A", riskRows),
      this.renderRiskMatrixRow("B", riskRows),
      this.renderRiskMatrixRow("C", riskRows),
    ];

    return AnalyticsLayout.panel(
      "Matrice classe vs statut stock",
      AnalyticsLayout.table(
        ["Classe", "OK estimé", "Faible", "Rupture", "Total risque"],
        rows,
        "Aucune donnée risque."
      )
    );
  },

  renderRiskMatrixRow(cls, riskRows) {
    const row = riskRows.find((r) => r.class === cls) || {};
    const low = Number(row.low_stock_count ?? 0);
    const out = Number(row.out_of_stock_count ?? 0);
    const totalRisk = low + out;
    const ok = totalRisk > 0 ? "À surveiller" : "OK";

    return `
      <tr>
        <td style="font-weight:700">${cls}</td>
        <td>${ok}</td>
        <td>
          <span class="chip ${low > 0 ? "chip-low" : "chip-ok"}">${low}</span>
        </td>
        <td>
          <span class="chip ${out > 0 ? "chip-rupture" : "chip-ok"}">${out}</span>
        </td>
        <td>
          <span class="chip ${totalRisk > 0 ? "chip-high" : "chip-ok"}">${totalRisk}</span>
        </td>
      </tr>
    `;
  },

  renderActionsPanel(global, classA) {
    return AnalyticsLayout.panel(
      "Actions recommandées par classe",
      `
        ${this.renderActionCard("A", "badge-a", "Sécuriser les produits critiques", "Surveiller les ruptures, les stocks faibles et les délais fournisseurs.")}
        ${this.renderActionCard("B", "badge-b", "Optimiser le niveau de stock", "Contrôler régulièrement les références importantes sans surcharger l'inventaire.")}
        ${this.renderActionCard("C", "badge-c", "Simplifier la gestion", "Identifier les produits à faible impact et rationaliser si nécessaire.")}

        <div style="margin-top:var(--space-md);border-top:1px solid var(--border);padding-top:var(--space-md)">
          <div style="font-size:0.78rem;font-weight:600;color:var(--text-primary);margin-bottom:8px">
            Insights IA
          </div>

          <div style="display:flex;gap:6px;font-size:0.72rem;margin-bottom:6px;line-height:1.4">
            <span>⚠️</span>
            <span style="color:var(--text-secondary)">
              La classe A représente ${global.a_value_share_percent ?? classA.value_share_percent ?? "—"}% de la valeur. Priorité à la disponibilité.
            </span>
          </div>

          <div style="display:flex;gap:6px;font-size:0.72rem;margin-bottom:6px;line-height:1.4">
            <span>📦</span>
            <span style="color:var(--text-secondary)">
              Les ruptures de classe C peuvent être nombreuses mais moins coûteuses individuellement.
            </span>
          </div>

          <a href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadAI()" class="panel-link">
            Voir plus d'insights IA ${AnalyticsShared.arrowRightIcon()}
          </a>
        </div>
      `
    );
  },

  renderActionCard(cls, badgeClass, main, sub) {
    return `
      <div class="action-card">
        <div class="action-card-cls ${badgeClass}">${cls}</div>
        <div class="action-card-body">
          <div class="main">${AnalyticsShared.esc(main)}</div>
          <div class="sub">${AnalyticsShared.esc(sub)}</div>
          <a href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadProducts()" class="action-card-link">
            Voir les produits ${cls} →
          </a>
        </div>
      </div>
    `;
  },

  renderCharts(valueRows, countRows, riskRows) {
    const paretoEl = document.getElementById("abc-pareto-chart");
    const riskEl = document.getElementById("abc-risk-chart");

    let cumulative = 0;

    const paretoData = valueRows.map((row) => {
      cumulative += Number(row.value_share_percent ?? 0);

      return {
        label: row.class,
        value: Number(row.value ?? 0),
        cumulative: Math.min(100, Number(cumulative.toFixed(1))),
      };
    });

    AnalyticsShared.renderParetoChart(paretoEl, paretoData, {
    width: Math.max(320, paretoEl?.clientWidth || 460),
    height: 200,
    });

    const valueByClass = {};
    valueRows.forEach((row) => {
      valueByClass[row.class] = Number(row.value ?? 0);
    });

    const riskData = ["A", "B", "C"].map((cls) => {
      const row = riskRows.find((r) => r.class === cls) || {};
      const low = Number(row.low_stock_count ?? 0);
      const rupture = Number(row.out_of_stock_count ?? 0);
      const ok = Math.max(0, 100 - low * 10 - rupture * 10);

      return {
        class: cls,
        rupture: rupture * 10,
        low: low * 10,
        ok,
        over: 0,
        value: valueByClass[cls] ?? 0,
      };
    });

    AnalyticsShared.renderRiskByClass(riskEl, riskData, {
    width: Math.max(320, riskEl?.clientWidth || 460),
    height: 120,
    });
  },
};