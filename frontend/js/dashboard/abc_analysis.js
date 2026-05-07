/* global DashboardAPI, RBComponents, RBCharts */
const AbcAnalysisPage = (() => {
  async function init() {
    ensureView();
    const el = document.getElementById("view-abc_analysis");
    document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));
    el.classList.add("active");
    el.innerHTML = RBComponents.shell("abc_analysis", `${RBComponents.toolbar(["Overview", "ABC Analysis"])}<div class="rb_card">Chargement de l'analyse ABC…</div>`);
    const d = await DashboardAPI.getAbc();
    el.innerHTML = RBComponents.shell("abc_analysis", renderContent(d));
    drawCharts(d);
  }
  function ensureView() {
    if (!document.getElementById("view-abc_analysis")) {
      const s = document.createElement("section");
      s.id = "view-abc_analysis"; s.className = "view";
      document.querySelector(".dash-content")?.appendChild(s);
    }
  }
  function renderContent(d) {
    return `${RBComponents.toolbar(["Overview", "ABC Analysis"])}
      <section class="rb_grid rb_kpi_grid">
        ${(d.class_cards || []).map(c => RBComponents.kpi({ label:`Classe ${c.class_name}`, value:c.products, delta:`${c.delta >= 0 ? '+' : ''}${String(c.delta).replace('.', ',')} pt`, trend:c.delta >= 0 ? "up" : "down", iconName:"fa-circle", color:c.class_name === "A" ? "purple" : c.class_name === "B" ? "orange" : c.class_name === "C" ? "green" : "gray", sub:"vs avr. 2024" })).join("")}
        ${RBComponents.kpi({ label:"Valeur totale du stock", value:RBComponents.formatMoneyCompact(d.total_stock_value), delta:"8,4%", iconName:"fa-coins", color:"purple" })}
        ${RBComponents.kpi({ label:"Criticité moyenne", value:`${String(d.average_criticality).replace('.', ',')} / 5`, delta:"−0,2 pt", trend:"up", iconName:"fa-shield-halved", color:"purple" })}
      </section>
      <section class="rb_grid rb_two_cols rb_dashboard_row">
        ${RBComponents.card("Contribution ABC par valeur", `<div class="rb_legend"><span class="rb_legend_item"><span class="rb_dot purple"></span>Valeur cumulée</span><span class="rb_legend_item"><span class="rb_dot orange"></span>Valeur par classe</span></div><div id="abc_pareto_chart" class="rb_chart"></div>`, { action:"ProductDashboardPage.init()", actionLabel:"Voir le détail par produit" })}
        ${RBComponents.card("Risque de stock par classe ABC", RBComponents.stackRows(d.risk_by_class || []), { action:"ProductDashboardPage.init()", actionLabel:"Voir le détail des risques" })}
      </section>
      <section class="rb_grid rb_three_cols">
        ${RBComponents.card("Synthèse des classes ABC", renderSummary(d.summary_rows || []), { action:"ProductDashboardPage.init()", actionLabel:"Voir les produits de classe A" })}
        ${RBComponents.card("Matrice Classe vs Statut de stock", renderHeatmap(), { action:"ProductDashboardPage.init()", actionLabel:"Explorer la matrice en détail" })}
        ${RBComponents.card("Actions recommandées par classe", renderActions(), {})}
      </section>
      <div class="rb_footer_hint"><i class="fas fa-circle-info"></i> Cliquez sur un graphique ou un indicateur pour explorer plus en détail.</div>`;
  }
  function renderSummary(rows) {
    return `<table class="rb_table"><thead><tr><th>Classe</th><th># SKUs</th><th>Valeur stock</th><th>% Valeur</th><th>CA annuel</th><th>Rotation</th><th>Risque</th></tr></thead><tbody>${rows.map(r=>`<tr><td>${RBComponents.badge(r.class_name, r.class_name === "A" ? "purple" : r.class_name === "B" ? "orange" : r.class_name === "C" ? "green" : "gray")}</td><td>${r.skus}</td><td>${RBComponents.formatMoneyCompact(r.stock_value)}</td><td>${RBComponents.pct(r.value_share)}</td><td>${RBComponents.formatMoneyCompact(r.annual_revenue)}</td><td>${String(r.rotation).replace('.', ',')}</td><td>${RBComponents.badge(String(r.risk).replace('.', ','), r.risk >= 3 ? "red" : r.risk >= 2.7 ? "orange" : "green")}</td></tr>`).join("")}<tr><td><strong>Total</strong></td><td><strong>717</strong></td><td><strong>18,73 M€</strong></td><td><strong>100%</strong></td><td><strong>35,98 M€</strong></td><td><strong>1,75</strong></td><td>${RBComponents.badge("2,8","purple")}</td></tr></tbody></table>`;
  }
  function renderHeatmap() {
    const rows = [["A","6,33 M€","2,22 M€","0,89 M€","1,67 M€","11,11 M€"],["B","2,54 M€","1,20 M€","0,54 M€","0,93 M€","5,21 M€"],["C","0,77 M€","0,60 M€","0,32 M€","0,44 M€","2,13 M€"],["Non classé","0,06 M€","0,07 M€","0,05 M€","0,10 M€","0,28 M€"]];
    return `<div class="rb_heatmap"><div></div>${["En stock","Faible","Rupture","Surstock","Total"].map(h=>`<div class="rb_heat_head">${h}</div>`).join("")}${rows.map(r=>`<div class="rb_heat_cell rb_heat_label">${r[0]}</div>${r.slice(1).map((c,i)=>`<div class="rb_heat_cell" style="background:${["#dff5e5","#ffe9c5","#ffd9de","#e7ddff","#f8f9fd"][i]}">${c}</div>`).join("")}`).join("")}</div>`;
  }
  function renderActions() {
    return `<div class="rb_ai_item"><div>${RBComponents.badge("A","purple")}</div><div><strong>Sécuriser la disponibilité des produits critiques.</strong><div class="rb_ai_text">Surveiller les ruptures et les délais fournisseurs.</div></div><div></div></div><div class="rb_ai_item"><div>${RBComponents.badge("B","orange")}</div><div><strong>Optimiser les niveaux de stock.</strong><div class="rb_ai_text">Réduire les surstocks pour améliorer la rotation.</div></div><div></div></div><div class="rb_ai_item"><div>${RBComponents.badge("C","green")}</div><div><strong>Rationaliser le portefeuille.</strong><div class="rb_ai_text">Envisager le délotage ou le regroupement.</div></div><div></div></div><div style="margin-top:18px"><div class="rb_card_title">Insights IA <span style="font-size:11px;color:#7b819b">Bêta</span></div><p style="font-size:13px;color:#626986;line-height:1.7">8,9% de la valeur de classe A est en rupture ou faible. La rotation de la classe C est 51% plus faible que la moyenne.</p><a class="rb_card_link" onclick="Router.navigate('intelligence');return false;">Voir plus d'insights IA <i class="fas fa-arrow-right"></i></a></div>`;
  }
  function drawCharts(d) { RBCharts.barLinePareto(document.getElementById("abc_pareto_chart"), d.pareto || []); }
  return { init };
})();
window.AbcAnalysisPage = AbcAnalysisPage;
