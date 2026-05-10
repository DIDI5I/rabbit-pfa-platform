(function () {
  function money(value) {
    if (value == null || Number.isNaN(Number(value))) return "—";
    return new Intl.NumberFormat("fr-FR", { style:"currency", currency:"EUR", maximumFractionDigits:0 }).format(Number(value));
  }
  function formatMoneyCompact(value) {
    if (value == null || Number.isNaN(Number(value))) return "—";
    const n = Number(value);
    if (Math.abs(n) >= 1000000) return `${(n / 1000000).toLocaleString("fr-FR", { maximumFractionDigits:2 })} M€`;
    if (Math.abs(n) >= 1000) return `${(n / 1000).toLocaleString("fr-FR", { maximumFractionDigits:0 })} k€`;
    return `${n.toLocaleString("fr-FR")} €`;
  }
  function pct(v) { return `${Number(v || 0).toLocaleString("fr-FR", { maximumFractionDigits:1 })}%`; }
  function icon(name) { return `<i class="fas ${name}"></i>`; }
  function kpi({ label, value, delta, trend = "up", iconName = "fa-chart-line", color = "purple", sub = "vs avr. 2024" }) {
    return `<div class="rb_kpi_card">
      <div class="rb_kpi_top"><div class="rb_kpi_icon ${color}">${icon(iconName)}</div><div class="rb_kpi_label">${label}</div></div>
      <div class="rb_kpi_value">${value}</div>
      <div class="rb_kpi_delta ${trend}">${trend === "down" ? "↓" : "↑"} ${delta || ""} <span style="color:#8b90a6;font-weight:700">${sub}</span></div>
    </div>`;
  }
  function card(title, body, options = {}) {
    const sub = options.sub ? `<div class="rb_card_sub">${options.sub}</div>` : "";
    const action = options.action ? `<a class="rb_card_link" href="#" onclick="${options.action};return false;">${options.actionLabel || "Voir le détail"} <i class="fas fa-arrow-right"></i></a>` : "";
    return `<div class="rb_card ${options.className || ""}">
      <div class="rb_card_header"><div><div class="rb_card_title">${title}</div>${sub}</div>${options.right || ""}</div>
      ${body}
      ${action}
    </div>`;
  }
  function badge(label, color = "purple") { return `<span class="rb_badge ${color}">${label}</span>`; }
  function statusColor(status) {
    const s = String(status || "").toLowerCase();
    if (s.includes("rupture") || s.includes("out")) return "red";
    if (s.includes("faible") || s.includes("low") || s.includes("moyen")) return "orange";
    if (s.includes("stock") || s.includes("ok") || s.includes("reçu")) return "green";
    return "purple";
  }
  function shell(active, content, filters = "") {
    const nav = [
      ["dashboard", "fa-house", "Overview", "DashboardPageV2.init()"],
      ["abc_analysis", "fa-chart-simple", "ABC Analysis", "AbcAnalysisPage.init()"],
      ["products", "fa-rectangle-list", "Products", "ProductDashboardPage.init()"],
      ["forecasting", "fa-arrow-trend-up", "Forecasting", "ForecastingDashboardPage.init()"],
      ["ai", "fa-wand-magic-sparkles", "AI Assistant", "Router.navigate('intelligence')"]
    ].map(([key, ic, label, action]) => `<button class="analytics_nav_btn ${active === key ? "active" : ""}" onclick="${action}">${icon(ic)} ${label}</button>`).join("");

    return `<div class="analytics_shell">
      <div class="analytics_header">
        <div class="analytics_brand"><div class="analytics_logo">🐇</div><div class="analytics_brand_name">Rabbit</div></div>
        <div class="analytics_nav">${nav}</div>
        <div class="analytics_header_right">
          <span>FR | EN</span>
          <button class="analytics_icon_btn">${icon("fa-bell")}</button>
          <button class="analytics_icon_btn">${icon("fa-circle-question")}</button>
          <div class="analytics_user"><div class="analytics_avatar"></div><div><div class="analytics_user_name">Diaby F.</div><div class="analytics_user_role">Analyste BI</div></div></div>
        </div>
      </div>
      <div class="analytics_body">
        <aside class="analytics_filters">${filters || defaultFilters()}</aside>
        <main class="analytics_content">${content}</main>
      </div>
    </div>`;
  }
  function defaultFilters(extra = "") {
    return `<div class="filter_block"><div class="filter_title">Période</div><div class="rb_date_range">01/05/2024 <i class="fas fa-arrow-right"></i> 31/05/2024 <i class="fas fa-calendar-days" style="margin-left:auto"></i></div></div>
      <div class="filter_block"><div class="filter_title">Organisation</div><select class="rb_select"><option>Tous les entrepôts</option></select><div style="height:10px"></div><select class="rb_select"><option>Toutes les catégories</option></select></div>
      <div class="filter_block"><div class="filter_title">ABC Class</div>${["A","B","C","Non classé"].map((x,i)=>`<label class="rb_check"><input type="checkbox" ${i<3?"checked":""}> ${x}</label>`).join("")}</div>
      <div class="filter_block"><div class="filter_title">Statut stock</div>${[["green","En stock"],["orange","Faible"],["red","Rupture"],["purple","Surstock"]].map(([c,l])=>`<label class="rb_check"><input type="checkbox"> <span class="rb_dot ${c}"></span>${l}</label>`).join("")}</div>
      ${extra}
      <div class="filter_block"><div class="filter_title">Recherche produit</div><input class="rb_input" placeholder="Référence, nom, SKU..."></div>
      <button class="rb_square_btn" style="width:100%"><i class="fas fa-rotate-left"></i> Réinitialiser les filtres</button>
      <div style="margin-top:28px;font-size:12px;color:#7a8098">Enregistré comme vue<br><strong style="color:#5d2ee6">Vue exécutive (défaut)</strong> ☆</div>`;
  }
  function toolbar(path, backLabel) {
    return `<div class="analytics_toolbar"><div class="rb_breadcrumb">${path.map((p,i)=>`<span class="${i===path.length-1 ? "active" : ""}">${p}</span>${i<path.length-1 ? `<i class="fas fa-chevron-right" style="font-size:10px"></i>` : ""}`).join("")}</div><div class="rb_toolbar_actions">${backLabel ? `<button class="rb_square_btn" onclick="AbcAnalysisPage.init()"><i class="fas fa-arrow-left"></i> ${backLabel}</button>` : ""}<button class="rb_square_btn">01/05/2024 – 31/05/2024 <i class="fas fa-calendar-days"></i></button><button class="rb_square_btn"><i class="fas fa-download"></i> Exporter</button></div></div>`;
  }
  function stackRows(rows) {
    return `<div class="rb_legend"><span class="rb_legend_item"><span class="rb_dot red"></span>Rupture</span><span class="rb_legend_item"><span class="rb_dot orange"></span>Faible</span><span class="rb_legend_item"><span class="rb_dot green"></span>OK</span><span class="rb_legend_item"><span class="rb_dot purple"></span>Surstock</span></div>` + rows.map(r => `<div class="rb_stack_row"><div class="rb_stack_label">${r.label}</div><div class="rb_stack_bar"><div class="rb_stack_seg" style="width:${r.out || 0}%;background:#f13d52">${r.out || 0}%</div><div class="rb_stack_seg" style="width:${r.low || 0}%;background:#ff9f1c">${r.low || 0}%</div><div class="rb_stack_seg" style="width:${r.ok || 0}%;background:#34b65a">${r.ok || 0}%</div><div class="rb_stack_seg" style="width:${r.over || 0}%;background:#6f42f5">${r.over || 0}%</div></div><div class="rb_stack_value">${formatMoneyCompact(r.value)}</div></div>`).join("");
  }
  window.RBComponents = { money, formatMoneyCompact, pct, icon, kpi, card, badge, statusColor, shell, defaultFilters, toolbar, stackRows };
})();
