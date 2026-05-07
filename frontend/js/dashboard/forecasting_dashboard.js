/* global DashboardAPI, RBComponents, RBCharts */
const ForecastingDashboardPage = (() => {
  async function init() {
    ensureView();
    const el = document.getElementById("view-forecasting");
    document.querySelectorAll(".view").forEach(v => v.classList.remove("active"));
    el.classList.add("active");
    const d = await DashboardAPI.getForecasting();
    el.innerHTML = RBComponents.shell("forecasting", renderContent(d), forecastFilters());
    drawCharts(d);
  }
  function ensureView() { if (!document.getElementById("view-forecasting")) { const s=document.createElement("section"); s.id="view-forecasting"; s.className="view"; document.querySelector(".dash-content")?.appendChild(s); } }
  function forecastFilters() { return RBComponents.defaultFilters(`<div class="filter_block"><div class="filter_title">Horizon de prévision</div><select class="rb_select"><option>12 semaines</option></select></div><div class="filter_block"><div class="filter_title">Modèle de prévision</div><div class="rb_card" style="box-shadow:none;padding:12px"><strong>Modèle avancé (ML)</strong><div class="rb_ai_text">Mis à jour le 30/04/2024</div>${RBComponents.badge("Actif","green")}</div></div><div class="filter_block"><div class="filter_title">Qualité globale du modèle</div><div class="rb_card" style="box-shadow:none;text-align:center"><div class="rb_kpi_value">87%</div><div class="rb_ai_text">Très bonne</div><div class="rb_kpi_delta up">↑ 4 pts</div></div></div>`); }
  function renderContent(d) {
    return `${RBComponents.toolbar(["Overview", "Forecasting"])}
      <section class="rb_grid rb_kpi_grid">
        ${RBComponents.kpi({ label:"Précision des prévisions (MAPE)", value:`${String(d.mape).replace('.', ',')}%`, delta:"−1,8 pts", iconName:"fa-crosshairs", color:"purple" })}
        ${RBComponents.kpi({ label:"Demande prédite (12 sem.)", value:RBComponents.formatMoneyCompact(d.predicted_demand_value), delta:"8,4%", iconName:"fa-chart-line", color:"blue", sub:"vs 12 sem. préc." })}
        ${RBComponents.kpi({ label:"Couverture de stock", value:`${d.coverage_days} jours`, delta:"+6 jours", iconName:"fa-cube", color:"green" })}
        ${RBComponents.kpi({ label:"Réapprovisionnements recommandés", value:d.recommended_reorders, delta:RBComponents.formatMoneyCompact(d.recommended_value), trend:"neutral", iconName:"fa-cart-shopping", color:"blue", sub:"" })}
        ${RBComponents.kpi({ label:"Impact niveau de service", value:`${d.service_level}%`, delta:"2,1 pts", iconName:"fa-shield-halved", color:"purple" })}
        ${RBComponents.kpi({ label:"Alertes d’anomalies", value:d.anomaly_count, delta:"−1", trend:"up", iconName:"fa-triangle-exclamation", color:"red" })}
      </section>
      <section class="rb_grid rb_three_cols rb_dashboard_row">
        ${RBComponents.card("Prévision de la demande", `<div class="rb_legend"><span class="rb_legend_item"><span class="rb_dot purple"></span>Réel</span><span class="rb_legend_item"><span class="rb_dot purple"></span>Prévision</span><span class="rb_legend_item"><span class="rb_dot gray"></span>Intervalle de confiance</span></div><div id="forecast_demand_chart" class="rb_chart"></div>`, { action:"", actionLabel:"" })}
        ${RBComponents.card("Prévision par catégorie", `<div id="forecast_category_bars" class="rb_chart"></div>`, { action:"", actionLabel:"Voir le détail par catégorie" })}
        ${RBComponents.card("Saisonnalité", `<div id="forecast_season_chart" class="rb_chart"></div>`, { action:"", actionLabel:"Voir l’analyse de saisonnalité" })}
      </section>
      <section class="rb_grid rb_three_cols rb_dashboard_row">
        ${RBComponents.card("Risque de rupture attendu", `<div class="rb_legend"><span class="rb_legend_item"><span class="rb_dot red"></span>Risque élevé</span><span class="rb_legend_item"><span class="rb_dot orange"></span>Risque moyen</span><span class="rb_legend_item"><span class="rb_dot green"></span>Risque faible</span></div><div id="forecast_risk_bars" class="rb_chart"></div>`, { action:"", actionLabel:"Voir le détail des risques" })}
        ${RBComponents.card("Recommandations de réapprovisionnement", `<div style="display:grid;grid-template-columns:210px 1fr;align-items:center"><div id="forecast_reco_donut" class="rb_chart"></div><div>${[["À commander",14,"purple"],["À suivre",7,"orange"],["Planifiés",4,"green"],["En transit",2,"blue"]].map(x=>`<div style="display:grid;grid-template-columns:12px 1fr auto;gap:10px;margin:14px 0"><span class="rb_dot ${x[2]}"></span><strong>${x[0]}</strong><span>${x[1]}</span></div>`).join("")}</div></div>`, { action:"", actionLabel:"Voir toutes les recommandations" })}
        ${RBComponents.card("Impact des scénarios", `<div style="display:grid;gap:22px;font-size:15px"><div style="display:flex;justify-content:space-between"><span>Scénario actuel (reco.)</span><strong style="color:#6f42f5">96,4%</strong></div><div style="display:flex;justify-content:space-between"><span>Scénario minimal (−20%)</span><strong style="color:#ff7a1a">91,2%</strong></div><div style="display:flex;justify-content:space-between"><span>Scénario maximal (+20%)</span><strong style="color:#34b65a">98,7%</strong></div></div>`, { action:"", actionLabel:"Comparer plus de scénarios" })}
      </section>
      <section class="rb_grid rb_two_cols">
        ${RBComponents.card("Top produits à risque — action requise", renderTopRisk(d.top_risk || []), { action:"", actionLabel:"Voir tous les produits à risque" })}
        ${RBComponents.card("Insights IA", `<div class="rb_ai_item"><div class="rb_kpi_icon green"><i class="fas fa-chart-simple"></i></div><div><strong>La demande des boissons devrait augmenter de 12% en juin.</strong><div class="rb_ai_text">Portée par la saisonnalité et les promotions prévues.</div></div></div><div class="rb_ai_item"><div class="rb_kpi_icon purple"><i class="fas fa-triangle-exclamation"></i></div><div><strong>3 anomalies détectées sur des produits en baisse inhabituelle.</strong><div class="rb_ai_text">Vérifiez les hypothèses ou l’approvisionnement.</div></div></div><div class="rb_ai_item"><div class="rb_kpi_icon green"><i class="fas fa-cube"></i></div><div><strong>Commander 12,46 M€ permettrait de maintenir le niveau de service au-dessus de 95%.</strong></div></div>`, { action:"Router.navigate('intelligence')", actionLabel:"Voir tous les insights IA" })}
      </section>`;
  }
  function renderTopRisk(rows) { return `<table class="rb_table"><thead><tr><th>Produit</th><th>Catégorie</th><th>Demande prévue</th><th>Stock actuel</th><th>Rupture projetée</th><th>Action suggérée</th><th>Priorité</th></tr></thead><tbody>${rows.map(r=>`<tr><td><strong>${r.sku}</strong><br><span style="color:#7b819b">${r.name}</span></td><td>${r.category}</td><td>${r.demand}</td><td>${r.stock}</td><td style="color:${r.gap.includes('-') ? '#f13d52' : '#34b65a'};font-weight:900">${r.gap}</td><td>${RBComponents.badge(r.action,"purple")}</td><td>${RBComponents.badge(r.priority,RBComponents.statusColor(r.priority))}</td></tr>`).join("")}</tbody></table>`; }
  function drawCharts(d) {
    RBCharts.lineChart(document.getElementById("forecast_demand_chart"), d.demand_real, d.demand_forecast, ["Déc.","Janv.","Févr.","Mars","Avr.","Mai","Juin","Juil."]);
    RBCharts.horizontalBars(document.getElementById("forecast_category_bars"), d.category_forecast);
    RBCharts.lineChart(document.getElementById("forecast_season_chart"), [95,110,102,84,88,103,100,126,116,92,110], [108,126,115]);
    RBCharts.verticalBars(document.getElementById("forecast_risk_bars"), d.rupture_risk);
    RBCharts.donutChart(document.getElementById("forecast_reco_donut"), [{value:14},{value:7},{value:4},{value:2}], "27");
  }
  return { init };
})();
window.ForecastingDashboardPage = ForecastingDashboardPage;
