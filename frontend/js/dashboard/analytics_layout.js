/* ============================================================
   analytics_layout.js — Shared layout helpers for analytics tabs
   ============================================================ */

window.AnalyticsLayout = {
  page({ current, body, period = "Période actuelle" }) {
    return `
      <div class="analytics-v2">
        <div class="analytics-page-header">
          <nav class="breadcrumb">
            <a href="javascript:void(0)" onclick="window.OwnerOverviewDashboard.loadOverview()">Vue globale</a>
            <span class="sep">›</span>
            <span class="current">${AnalyticsShared.esc(current)}</span>
          </nav>

          <div class="toolbar">
            <button class="date-range-btn" type="button">${AnalyticsShared.esc(period)}</button>
            <button class="export-btn" type="button" onclick="window.print()">Exporter</button>
          </div>
        </div>

        <div class="analytics-page-body">
          ${body}
        </div>
      </div>
    `;
  },

  panel(title, content, extra = "") {
    return `
      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">${AnalyticsShared.esc(title)}</span>
          ${extra}
        </div>
        ${content}
      </div>
    `;
  },

  kpi(label, value, sub = "", cls = "neutral", icon = "") {
    return `
      <div class="kpi-card">
        <div class="kpi-label">
          ${icon ? `<span class="kpi-icon">${AnalyticsShared.esc(icon)}</span>` : ""}
          ${AnalyticsShared.esc(label)}
        </div>
        <div class="kpi-value">${value ?? "—"}</div>
        <div class="kpi-sub ${cls}">${AnalyticsShared.esc(sub || "")}</div>
      </div>
    `;
  },

  table(headers, rows, emptyMessage = "Aucune donnée.") {
    return `
      <div class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              ${headers.map(h => `<th>${AnalyticsShared.esc(h)}</th>`).join("")}
            </tr>
          </thead>
          <tbody>
            ${rows && rows.length ? rows.join("") : `<tr><td colspan="${headers.length}">${AnalyticsShared.esc(emptyMessage)}</td></tr>`}
          </tbody>
        </table>
      </div>
    `;
  },

  empty(message = "Données indisponibles.") {
    return `
      <div class="panel">
        <div class="empty-state">
          <i class="fas fa-circle-exclamation"></i>
          <p>${AnalyticsShared.esc(message)}</p>
        </div>
      </div>
    `;
  }
};