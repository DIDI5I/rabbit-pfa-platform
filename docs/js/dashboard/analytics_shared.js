/* ============================================================
   analytics_shared.js — Scoped dashboard helpers for Rabbit analytics
   Adapted from uploaded shared.js.
   Does NOT include renderTopnav/renderToolbar.
   ============================================================ */

const AnalyticsShared = {
  fmt: {
    currency(v, decimals = 0) {
      if (v == null || v === "—") return "—";
      const n = Number(v);
      if (!Number.isFinite(n)) return "—";

      if (n >= 1_000_000) {
        return `${(n / 1_000_000).toFixed(2).replace(".", ",")} M MAD`;
      }

      if (n >= 1_000) {
        return `${(n / 1_000).toFixed(decimals).replace(".", ",")} k MAD`;
      }

      return `${n.toLocaleString("fr-FR")} MAD`;
    },

    number(v) {
      if (v == null || v === "—") return "—";
      const n = Number(v);
      if (!Number.isFinite(n)) return "—";
      return n.toLocaleString("fr-FR");
    },

    pct(v, decimals = 1) {
      if (v == null || v === "—") return "—";
      const n = Number(v);
      if (!Number.isFinite(n)) return "—";
      return `${n.toFixed(decimals).replace(".", ",")} %`;
    },

    days(v) {
      if (v == null || v === "—") return "—";
      const n = Number(v);
      if (!Number.isFinite(n)) return "—";
      return `${Math.round(n)} j`;
    },

    date(s) {
      if (!s) return "—";
      return new Date(s).toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });
    },
  },

  esc(value) {
    const div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  },

  statusChip(status) {
    const map = {
      OK: ["chip-ok", "En stock"],
      LOW_STOCK: ["chip-low", "Faible"],
      OUT_OF_STOCK: ["chip-rupture", "Rupture"],
    };

    const [cls, label] = map[status] || ["chip-none", status || "—"];
    return `<span class="chip ${cls}">${this.esc(label)}</span>`;
  },

  priorityChip(priority) {
    const map = {
      CRITICAL: ["chip-critical", "Critique"],
      HIGH: ["chip-high", "Élevé"],
      MEDIUM: ["chip-medium", "Moyen"],
      LOW: ["chip-none", "Faible"],
      NONE: ["chip-none", "OK"],
    };

    const [cls, label] = map[priority] || ["chip-none", priority || "—"];
    return `<span class="chip ${cls}">${this.esc(label)}</span>`;
  },

  abcChip(cls) {
    const normalized = (cls || "none").toLowerCase();
    return `<span class="chip chip-${normalized}">${this.esc(cls || "—")}</span>`;
  },

  confidenceChip(confidence) {
    const map = {
      HIGH: ["chip-ok", "Haute"],
      MEDIUM: ["chip-low", "Moyenne"],
      LOW: ["chip-rupture", "Faible"],
      ESTIMATE_ONLY: ["chip-none", "Estimé"],
    };

    const [cls, label] = map[confidence] || ["chip-none", confidence || "—"];
    return `<span class="chip ${cls}">${this.esc(label)}</span>`;
  },

  renderDonut(container, segments, centerText, size = 120) {
    if (!container) return;

    const total = segments.reduce((sum, seg) => sum + Number(seg.value || 0), 0);

    if (total <= 0) {
      container.innerHTML = `<div class="empty-state"><p>Aucune donnée.</p></div>`;
      return;
    }

    const r = size / 2 - 10;
    const cx = size / 2;
    const cy = size / 2;
    const stroke = size / 6;

    let angle = -Math.PI / 2;

    const paths = segments.map((seg) => {
      const ratio = Number(seg.value || 0) / total;
      const start = angle;
      const end = angle + ratio * 2 * Math.PI;
      angle = end;

      const x1 = cx + r * Math.cos(start);
      const y1 = cy + r * Math.sin(start);
      const x2 = cx + r * Math.cos(end);
      const y2 = cy + r * Math.sin(end);
      const large = ratio > 0.5 ? 1 : 0;

      const d = `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2}`;

      return `
        <path
          d="${d}"
          fill="none"
          stroke="${seg.color}"
          stroke-width="${stroke}"
          stroke-linecap="round"
        />
      `;
    }).join("");

    container.innerHTML = `
      <div class="donut-wrap" style="width:${size}px;height:${size}px">
        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
          <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="var(--border)" stroke-width="${stroke}"/>
          ${paths}
        </svg>
        <div class="donut-center">
          <div class="val">${this.esc(centerText?.val ?? "—")}</div>
          <div class="lbl">${this.esc(centerText?.lbl ?? "")}</div>
        </div>
      </div>
    `;
  },

  renderSparkline(container, data, opts = {}) {
    if (!container) return;

    const {
      width = 300,
      height = 80,
      color = "var(--primary-light)",
      areaColor = null,
      showDots = false,
      forecast = null,
    } = opts;

    if (!data || data.length === 0) {
      container.innerHTML = "";
      return;
    }

    const values = data.map((d) => Number(d.v ?? d.value ?? 0));
    const minV = Math.min(...values);
    const maxV = Math.max(...values);
    const range = maxV - minV || 1;

    const xScale = (i) => (i / Math.max(1, data.length - 1)) * width;
    const yScale = (v) => height - ((v - minV) / range) * height * 0.85 - height * 0.05;

    const allPts = values.map((v, i) => `${xScale(i)},${yScale(v)}`).join(" ");

    let areaPath = "";

    if (areaColor) {
      areaPath = `
        <polygon
          points="${allPts} ${xScale(data.length - 1)},${height} 0,${height}"
          fill="${areaColor}"
          opacity="0.4"
        />
      `;
    }

    let forecastLine = "";

    if (forecast != null && forecast < data.length) {
      const fx = xScale(forecast);

      const fPts = values
        .slice(forecast)
        .map((v, i) => `${xScale(forecast + i)},${yScale(v)}`)
        .join(" ");

      forecastLine = `
        <line x1="${fx}" y1="0" x2="${fx}" y2="${height}" stroke="var(--text-muted)" stroke-width="1" stroke-dasharray="3,3"/>
        <polyline points="${fPts}" fill="none" stroke="${color}" stroke-width="1.5" stroke-dasharray="4,3" opacity="0.8"/>
      `;
    }

    const solidEnd = forecast != null ? forecast : data.length - 1;

    const solidPts = values
      .slice(0, solidEnd + 1)
      .map((v, i) => `${xScale(i)},${yScale(v)}`)
      .join(" ");

    const dots = showDots
      ? values.map((v, i) => `
          <circle cx="${xScale(i)}" cy="${yScale(v)}" r="2.5" fill="${color}" opacity="0.8"/>
        `).join("")
      : "";

    container.innerHTML = `
      <svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}" preserveAspectRatio="none" class="sparkline">
        ${areaPath}
        ${forecastLine}
        <polyline points="${solidPts}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
        ${dots}
      </svg>
    `;
  },

  renderParetoChart(container, data, opts = {}) {
    if (!container) return;

    const { width = 400, height = 200 } = opts;

    if (!data || data.length === 0) {
      container.innerHTML = `<div class="empty-state"><p>Aucune donnée Pareto.</p></div>`;
      return;
    }

    const maxVal = Math.max(...data.map((d) => Number(d.value || 0)), 1);
    const barW = (width - 40) / data.length - 6;
    const colors = ["var(--class-a)", "var(--class-b)", "var(--class-c)", "var(--class-none)"];

    const bars = data.map((d, i) => {
      const value = Number(d.value || 0);
      const bh = (value / maxVal) * (height - 40);
      const bx = 30 + i * ((width - 40) / data.length) + 3;
      const by = height - 25 - bh;

      return `
        <rect x="${bx}" y="${by}" width="${barW}" height="${bh}" rx="3" fill="${colors[i] || colors[2]}"/>
        <text x="${bx + barW / 2}" y="${height - 10}" text-anchor="middle" font-size="10" fill="var(--text-secondary)">
          ${this.esc(d.label)}
        </text>
        <text x="${bx + barW / 2}" y="${by - 4}" text-anchor="middle" font-size="9" fill="var(--text-muted)">
          ${this.fmt.currency(value).replace(" MAD", "")}
        </text>
      `;
    }).join("");

    const cumPts = data.map((d, i) => {
      const cx = 30 + (i + 0.5) * ((width - 40) / data.length);
      const cy = height - 25 - ((Number(d.cumulative || 0) / 100) * (height - 40));
      return `${cx},${cy}`;
    }).join(" ");

    const cumLabels = data.map((d, i) => {
      const cx = 30 + (i + 0.5) * ((width - 40) / data.length);
      const cy = height - 25 - ((Number(d.cumulative || 0) / 100) * (height - 40));

      return `
        <text x="${cx}" y="${cy - 6}" text-anchor="middle" font-size="8" fill="var(--primary-light)">
          ${this.esc(d.cumulative)}%
        </text>
      `;
    }).join("");

    container.innerHTML = `
      <svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
        ${bars}
        <polyline points="${cumPts}" fill="none" stroke="var(--primary-light)" stroke-width="1.5"/>
        ${cumLabels}
      </svg>
    `;
  },

  renderRiskByClass(container, data, opts = {}) {
    if (!container) return;

    const { width = 400, height = 130 } = opts;

    if (!data || data.length === 0) {
      container.innerHTML = `<div class="empty-state"><p>Aucune donnée risque.</p></div>`;
      return;
    }

    const colors = {
      rupture: "var(--rupture)",
      low: "var(--low)",
      ok: "var(--ok)",
      over: "var(--overstock)",
    };

    const rowH = (height - 20) / data.length;
    const barAreaW = width - 100;

    const rows = data.map((row, i) => {
      const y = 10 + i * rowH;
      const total = Number(row.rupture || 0) + Number(row.low || 0) + Number(row.ok || 0) + Number(row.over || 0) || 1;

      const segments = [
        { key: "rupture", value: Number(row.rupture || 0) },
        { key: "low", value: Number(row.low || 0) },
        { key: "ok", value: Number(row.ok || 0) },
        { key: "over", value: Number(row.over || 0) },
      ];

      let xOffset = 85;

      const rects = segments.map((seg) => {
        const sw = (seg.value / total) * barAreaW;

        const rect = `
          <rect
            x="${xOffset}"
            y="${y + 4}"
            width="${sw}"
            height="${rowH - 12}"
            rx="2"
            fill="${colors[seg.key]}"
            opacity="0.85"
          />
        `;

        const label = sw > 18
          ? `
            <text
              x="${xOffset + sw / 2}"
              y="${y + rowH / 2 + 1}"
              text-anchor="middle"
              font-size="8"
              fill="white"
              font-weight="600"
            >
              ${Math.round((seg.value / total) * 100)}%
            </text>
          `
          : "";

        xOffset += sw + 1;

        return rect + label;
      }).join("");

      const classColor =
        row.class === "A"
          ? "var(--class-a)"
          : row.class === "B"
            ? "var(--class-b)"
            : "var(--class-c)";

      return `
        <text x="8" y="${y + rowH / 2 + 1}" font-size="11" font-weight="700" fill="${classColor}">
          ${this.esc(row.class)}
        </text>
        <text x="24" y="${y + rowH / 2 + 1}" font-size="9" fill="var(--text-muted)">
          ${this.fmt.currency(row.value || 0)}
        </text>
        ${rects}
      `;
    }).join("");

    container.innerHTML = `
      <svg width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
        ${rows}
      </svg>
    `;
  },

  arrowRightIcon() {
    return `
      <svg viewBox="0 0 12 12" fill="currentColor" style="width:12px;height:12px">
        <path d="M2 6h8M7 3l3 3-3 3" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round"/>
      </svg>
    `;
  },
};

window.AnalyticsShared = AnalyticsShared;