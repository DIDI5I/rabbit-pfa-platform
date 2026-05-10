(function () {
  const ns = "http://www.w3.org/2000/svg";
  function svgEl(name, attrs = {}) {
    const el = document.createElementNS(ns, name);
    Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, String(v)));
    return el;
  }
  function clear(container) { if (container) container.innerHTML = ""; }
  function createSvg(container, width = 620, height = 250) {
    clear(container);
    const svg = svgEl("svg", { viewBox: `0 0 ${width} ${height}`, role: "img" });
    container.appendChild(svg);
    return svg;
  }
  function linePath(values, x0, y0, w, h) {
    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const span = Math.max(max - min, 1);
    return values.map((v, i) => {
      const x = x0 + (i / Math.max(values.length - 1, 1)) * w;
      const y = y0 + h - ((v - min) / span) * h;
      return `${i === 0 ? "M" : "L"}${x.toFixed(1)} ${y.toFixed(1)}`;
    }).join(" ");
  }
  function drawGrid(svg, x0, y0, w, h, rows = 4) {
    for (let i = 0; i <= rows; i++) {
      const y = y0 + (h / rows) * i;
      svg.appendChild(svgEl("line", { x1:x0, y1:y, x2:x0+w, y2:y, stroke:"#e8ebf4", "stroke-width":1 }));
    }
  }
  function lineChart(container, real = [], forecast = [], labels = []) {
    const svg = createSvg(container, 620, 240);
    const x0 = 40, y0 = 25, w = 545, h = 155;
    drawGrid(svg, x0, y0, w, h);
    const all = real.concat(forecast);
    const combinedReal = real;
    const fStart = real.length ? real[real.length - 1] : forecast[0];
    const combinedForecast = [fStart].concat(forecast);
    svg.appendChild(svgEl("path", { d: linePath(all, x0, y0, w, h), fill:"none", stroke:"transparent" }));
    const max = Math.max(...all, 1), min = Math.min(...all, 0), span = Math.max(max-min,1);
    const xFor = (idx, total) => x0 + (idx / Math.max(total - 1, 1)) * w;
    const yFor = (v) => y0 + h - ((v - min) / span) * h;
    const realPath = combinedReal.map((v,i)=>`${i===0?"M":"L"}${xFor(i, all.length).toFixed(1)} ${yFor(v).toFixed(1)}`).join(" ");
    svg.appendChild(svgEl("path", { d: realPath, fill:"none", stroke:"#6f42f5", "stroke-width":3, "stroke-linecap":"round", "stroke-linejoin":"round" }));
    if (forecast.length) {
      const startIdx = Math.max(real.length - 1, 0);
      const fPath = combinedForecast.map((v,i)=>`${i===0?"M":"L"}${xFor(startIdx+i, all.length).toFixed(1)} ${yFor(v).toFixed(1)}`).join(" ");
      svg.appendChild(svgEl("path", { d: fPath, fill:"none", stroke:"#6f42f5", "stroke-width":3, "stroke-dasharray":"7 7", "stroke-linecap":"round", "stroke-linejoin":"round" }));
    }
    const defaultLabels = labels.length ? labels : ["Déc.","Janv.","Févr.","Mars","Avr.","Mai","Juin","Juil.","Août","Sept.","Oct."];
    defaultLabels.slice(0, 8).forEach((l,i)=> {
      const x = x0 + (i / 7) * w;
      svg.appendChild(svgEl("text", { x, y:220, fill: i===5 ? "#5d2ee6" : "#7b819b", "font-size":12, "font-weight": i===5 ? 800 : 600, "text-anchor":"middle" })).textContent = l;
    });
  }
  function donutChart(container, parts, centerText = "") {
    const svg = createSvg(container, 260, 230);
    const cx = 100, cy = 110, r = 68, sw = 34;
    const colors = ["#34b65a", "#ff9f1c", "#f13d52", "#6f42f5", "#2f80ed"];
    const total = parts.reduce((a,p)=>a + Math.max(Number(p.value || p.percent || 0),0), 0) || 1;
    let offset = -90;
    parts.forEach((p, i) => {
      const val = Math.max(Number(p.value || p.percent || 0), 0);
      const angle = val / total * 360;
      const dash = `${(angle / 360) * 2 * Math.PI * r} ${2 * Math.PI * r}`;
      const circle = svgEl("circle", { cx, cy, r, fill:"none", stroke:colors[i % colors.length], "stroke-width":sw, "stroke-dasharray":dash, "stroke-linecap":"butt", transform:`rotate(${offset} ${cx} ${cy})` });
      svg.appendChild(circle);
      offset += angle;
    });
    svg.appendChild(svgEl("circle", { cx, cy, r:r - sw/2, fill:"#fff" }));
    const t1 = svgEl("text", { x:cx, y:cy-2, "text-anchor":"middle", fill:"#262a45", "font-size":20, "font-weight":800 });
    t1.textContent = centerText;
    svg.appendChild(t1);
    const t2 = svgEl("text", { x:cx, y:cy+20, "text-anchor":"middle", fill:"#7b819b", "font-size":12, "font-weight":700 });
    t2.textContent = "Total";
    svg.appendChild(t2);
  }
  function barLinePareto(container, items) {
    const svg = createSvg(container, 620, 270);
    const x0=45,y0=25,w=520,h=165;
    drawGrid(svg,x0,y0,w,h);
    const max = Math.max(...items.map(i=>i.value), 1);
    const colors = ["#6f42f5", "#ff9f1c", "#34b65a", "#aab0c4"];
    const bw = 55, gap = 70;
    let line = "";
    items.forEach((it,i)=>{
      const x = x0 + 45 + i * (bw + gap);
      const bh = it.value / max * h;
      svg.appendChild(svgEl("rect", { x, y:y0+h-bh, width:bw, height:bh, rx:5, fill:colors[i%colors.length] }));
      svg.appendChild(svgEl("text", { x:x+bw/2, y:y0+h+28, fill:"#343852", "font-size":13, "font-weight":900, "text-anchor":"middle" })).textContent = it.label;
      svg.appendChild(svgEl("text", { x:x+bw/2, y:y0+h-bh-8, fill:"#343852", "font-size":12, "font-weight":800, "text-anchor":"middle" })).textContent = window.RBComponents.formatMoneyCompact(it.value);
      const px = x + bw/2;
      const py = y0 + h - (it.cumulative / 100) * h;
      line += `${i===0?"M":"L"}${px} ${py} `;
      svg.appendChild(svgEl("circle", { cx:px, cy:py, r:4, fill:"#5d2ee6" }));
      svg.appendChild(svgEl("text", { x:px+8, y:py-8, fill:"#5d2ee6", "font-size":11, "font-weight":900 })).textContent = `${it.cumulative}%`;
    });
    svg.appendChild(svgEl("path", { d:line, fill:"none", stroke:"#5d2ee6", "stroke-width":3 }));
  }
  function horizontalBars(container, items) {
    const svg = createSvg(container, 360, 220);
    const max = Math.max(...items.map(i=>i.value),1);
    items.forEach((it,i)=>{
      const y = 20 + i * 28;
      svg.appendChild(svgEl("text", { x:0, y:y+12, fill:"#626986", "font-size":12, "font-weight":700 })).textContent = it.label;
      svg.appendChild(svgEl("rect", { x:90, y:y+3, width:160, height:8, rx:4, fill:"#eeeafc" }));
      svg.appendChild(svgEl("rect", { x:90, y:y+3, width:160 * it.value / max, height:8, rx:4, fill:"#6f42f5" }));
      svg.appendChild(svgEl("text", { x:270, y:y+12, fill:"#626986", "font-size":12, "font-weight":800 })).textContent = window.RBComponents.formatMoneyCompact(it.value);
    });
  }
  function verticalBars(container, values) {
    const svg = createSvg(container, 420, 210);
    const x0=35,y0=20,w=350,h=135;
    drawGrid(svg,x0,y0,w,h,4);
    const max = Math.max(...values,1);
    values.forEach((v,i)=>{
      const bw=18, gap=11, x=x0+15+i*(bw+gap), bh=v/max*h;
      const color = v > 25 ? "#f13d52" : v > 18 ? "#ff9f1c" : "#34b65a";
      svg.appendChild(svgEl("rect", { x, y:y0+h-bh, width:bw, height:bh, rx:3, fill:color }));
      svg.appendChild(svgEl("text", { x:x+bw/2, y:185, fill:"#7b819b", "font-size":10, "text-anchor":"middle" })).textContent = `S${18+i}`;
    });
  }

  window.RBCharts = { lineChart, donutChart, barLinePareto, horizontalBars, verticalBars };
})();
