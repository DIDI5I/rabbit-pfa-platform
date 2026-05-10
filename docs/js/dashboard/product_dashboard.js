/* global DashboardAPI, RBComponents, RBCharts */
const ProductDashboardPage = (() => {
  async function init(productId = 1) {
    ensureView();
    const el = document.getElementById("view-product_dashboard");
    document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));
    el.classList.add("active");
    const d = await DashboardAPI.getProductDetail(productId);
    el.innerHTML = RBComponents.shell("products", renderContent(d));
    drawCharts(d);
  }
  function ensureView() {
    if (!document.getElementById("view-product_dashboard")) {
      const s = document.createElement("section"); s.id = "view-product_dashboard"; s.className = "view";
      document.querySelector(".dash-content")?.appendChild(s);
    }
  }
  function renderContent(p) {
    return `${RBComponents.toolbar(["Overview", "ABC Analysis", "Product"], "Retour à l'ABC Analysis")}
      <section class="rb_card" style="padding:0;margin-bottom:18px">
        <div class="rb_product_head">
          <div class="rb_product_img">🪑</div>
          <div><div class="rb_product_title">${p.name}</div><div class="rb_meta_grid">${meta("SKU",p.sku)}${meta("Catégorie",p.category)}${meta("ABC Class",RBComponents.badge(p.abc_class,"purple"))}${meta("Fournisseur principal",p.supplier)}${meta("Stock actuel",`<span style="color:#23a34a">${p.current_stock} u.</span>`)}</div></div>
          <div class="rb_product_side">${meta("Statut",RBComponents.badge(p.status,"green"))}${meta("Valeur de stock",RBComponents.formatMoneyCompact(p.stock_value))}${meta("Taux de service",`${p.service_level}%`)}</div>
        </div>
        <div class="rb_reco_row"><strong style="color:#5d2ee6;font-size:13px">Recommandations</strong><div class="rb_reco_pill green"><i class="fas fa-circle-check"></i><div><strong>Stock OK</strong><div class="rb_ai_text">Niveau de stock confortable</div></div></div><div class="rb_reco_pill purple"><i class="fas fa-chart-simple"></i><div><strong>Prévision stable</strong><div class="rb_ai_text">Demande en légère hausse</div></div></div><div class="rb_reco_pill blue"><i class="fas fa-circle-info"></i><div><strong>Délai fournisseur</strong><div class="rb_ai_text">Surveiller les prochains lots</div></div></div></div>
      </section>
      <section class="rb_grid rb_three_cols rb_dashboard_row">
        ${RBComponents.card("Évolution du stock", `<div class="rb_legend"><span class="rb_legend_item"><span class="rb_dot purple"></span>Stock réel</span><span class="rb_legend_item"><span class="rb_dot purple"></span>Prévision</span></div><div id="product_stock_chart" class="rb_chart"></div>`)}
        ${RBComponents.card("Structure de coût", `<div style="display:grid;grid-template-columns:230px 1fr;align-items:center"><div id="product_cost_donut" class="rb_chart"></div><div>${p.cost_structure.map((x,i)=>`<div style="display:grid;grid-template-columns:12px 1fr auto auto;gap:8px;margin:11px 0;align-items:center"><span class="rb_dot ${["blue","red","orange","red","purple"][i]}"></span><span style="font-size:12px;color:#626986;font-weight:800">${x.label}</span><strong>${x.value.toLocaleString("fr-FR")} €</strong><span>${x.percent}%</span></div>`).join("")}</div></div>`)}
        ${RBComponents.card("Dépendances / Nomenclature (BOM)", `<div style="text-align:right;margin-top:-8px">${RBComponents.badge("5","purple")}</div>${p.bom.map(b=>`<div class="rb_pipeline_item" style="grid-template-columns:34px 1fr auto"><div class="rb_kpi_icon gray"><i class="fas fa-chair"></i></div><div><strong>${b.name}</strong><div class="rb_ai_text">SKU: ${b.sku}</div></div><strong>${b.qty}</strong></div>`).join("")}`, { action:"Router.navigate('products')", actionLabel:"Voir la nomenclature complète" })}
      </section>
      <section class="rb_grid rb_three_cols rb_dashboard_row">
        ${RBComponents.card("Mouvements récents", renderMovements(p.movements), { action:"Router.navigate('movements')", actionLabel:"Voir tous les mouvements" })}
        ${RBComponents.card("Lots d'achat", renderLots(), { action:"Router.navigate('lots')", actionLabel:"Voir tous les lots" })}
        ${RBComponents.card("RFQs / Pipeline fournisseurs", renderRfqs(), { action:"Router.navigate('rfqs')", actionLabel:"Voir tout le pipeline" })}
      </section>
      <section class="rb_grid rb_two_cols">
        ${RBComponents.card("Indicateurs clés", `<div class="rb_grid rb_kpi_grid five" style="margin:0">${[{l:"Rotation du stock",v:"5,6",d:"8,2%"},{l:"Couverture de stock",v:"38 jours",d:"3 jours"},{l:"Valeur de stock",v:"9,42 M€",d:"4,1%"},{l:"Taux de rupture",v:"2,1%",d:"−0,6 pt"},{l:"Demande moyenne",v:"320 u./mois",d:"6,4%"}].map(x=>`<div class="rb_kpi_card" style="min-height:105px"><div class="rb_kpi_label">${x.l}</div><div class="rb_kpi_value" style="font-size:22px;margin-top:18px">${x.v}</div><div class="rb_kpi_delta up">↑ ${x.d}</div></div>`).join("")}</div>`)}
        ${RBComponents.card("Insights IA", `<div class="rb_ai_item"><div>${RBComponents.badge("i","green")}</div><div>La demande devrait augmenter de 12% en juin.</div></div><div class="rb_ai_item"><div>${RBComponents.badge("i","purple")}</div><div>Préparez une commande pour couvrir 6 semaines.</div></div><div class="rb_ai_item"><div>${RBComponents.badge("!","red")}</div><div>Le délai moyen fournisseur s’allonge de 2 jours.</div></div>`, { action:"Router.navigate('intelligence')", actionLabel:"Voir plus d’analyses IA" })}
      </section>`;
  }
  function meta(label, value) { return `<div><div class="rb_meta_label">${label}</div><div class="rb_meta_value">${value}</div></div>`; }
  function renderMovements(items) { return items.map(m=>`<div style="display:grid;grid-template-columns:26px 1fr auto;gap:10px;margin:12px 0"><div class="rb_kpi_icon ${m.type === "in" ? "green" : "red"}" style="width:24px;height:24px;font-size:11px"><i class="fas fa-${m.type === "in" ? "plus" : "minus"}"></i></div><div><strong>${m.label}</strong><div class="rb_ai_text">${m.ref}</div></div><div style="text-align:right"><strong style="color:${m.type === "in" ? "#34b65a" : "#f13d52"}">${m.qty}</strong><div class="rb_ai_text">${m.date}</div></div></div>`).join(""); }
  function renderLots() { return `<table class="rb_table"><thead><tr><th>Lot</th><th>Quantité</th><th>Coût unitaire</th><th>Date</th><th>Statut</th></tr></thead><tbody>${["240501","240417","240330","240215","240101"].map((id,i)=>`<tr><td>LOT-${id}</td><td>${[120,100,80,150,200][i]} u.</td><td>${[27.10,27.45,26.90,26.50,26.20][i].toLocaleString("fr-FR")} €</td><td>${["01/05/2024","17/04/2024","30/03/2024","15/02/2024","01/01/2024"][i]}</td><td>${RBComponents.badge("Reçu","green")}</td></tr>`).join("")}</tbody></table>`; }
  function renderRfqs() { return [["RFQ-25056","OfficeMax Europe","120 u.","6,35 M€","Négociation","orange"],["RFQ-25048","BuroPlus","80 u.","4,12 M€","En attente","gray"],["RFQ-25041","GlobalOffice Ltd.","100 u.","5,28 M€","Reçue","green"]].map(r=>`<div class="rb_pipeline_item" style="grid-template-columns:34px 1fr auto auto"><div class="rb_kpi_icon purple"><i class="fas fa-file-lines"></i></div><div><strong>${r[0]}</strong><div class="rb_ai_text">${r[1]}</div></div><strong>${r[2]}</strong><div>${RBComponents.badge(r[4],r[5])}</div></div>`).join(""); }
  function drawCharts(p) {
    RBCharts.lineChart(document.getElementById("product_stock_chart"), p.stock_series, p.forecast_series);
    RBCharts.donutChart(document.getElementById("product_cost_donut"), p.cost_structure.map(x=>({ value:x.percent })), "27,45 €");
  }
  return { init };
})();
window.ProductDashboardPage = ProductDashboardPage;
