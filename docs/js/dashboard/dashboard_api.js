/* global apiFetchJson */
(function () {
  function unwrap(resp) {
    if (!resp) return resp;
    if (resp.data !== undefined) return resp.data;
    if (resp.items !== undefined) return resp.items;
    return resp;
  }

  async function getJson(path, fallback) {
    try {
      if (typeof apiFetchJson !== "function") throw new Error("apiFetchJson unavailable");
      const resp = await apiFetchJson(path);
      return unwrap(resp);
    } catch (err) {
      console.warn("Dashboard fallback used for", path, err?.message || err);
      return structuredClone(fallback);
    }
  }

  const mock = {
    general: {
      total_products: 717,
      active_products: 693,
      total_inventory_value: 18730000,
      low_stock_count: 142,
      out_of_stock_count: 59,
      stock_ok_count: 425,
      open_rfqs_count: 27,
      active_purchase_lots: 56,
      service_level: 96.4,
      forecast_accuracy: 89.1,
      stock_status_value: { ok: 11110000, low: 3500000, out: 1540000, over: 2580000 },
      demand_series: [2.2,2.0,2.9,1.6,2.4,1.2,2.5,1.9,1.1,1.4,2.7,2.0,2.8,2.5,3.2,2.4],
      forecast_series: [2.8,3.1,2.7,2.9,3.4],
      urgent_products: [
        { sku:"RAB-100245", name:"Capteur de pression X10", abc_class:"A", status:"Rupture", risk:"Critique", value:95420, days_without_stock:12 },
        { sku:"RAB-200874", name:"Module électronique M5", abc_class:"A", status:"Faible", risk:"Élevé", value:68310, days_without_stock:7 },
        { sku:"RAB-300112", name:"Joint torique 45mm", abc_class:"B", status:"Faible", risk:"Élevé", value:22880, days_without_stock:6 },
        { sku:"RAB-100777", name:"Pompe hydraulique P2", abc_class:"A", status:"Rupture", risk:"Critique", value:112450, days_without_stock:15 },
        { sku:"RAB-400555", name:"Filtre à air F9", abc_class:"C", status:"Faible", risk:"Moyen", value:9740, days_without_stock:5 }
      ],
      procurement: [
        { name:"RFQs ouverts", count:27, value:6350000, icon:"fa-file-lines" },
        { name:"Commandes confirmées", count:34, value:4820000, icon:"fa-cube" },
        { name:"En transit", count:18, value:2310000, icon:"fa-truck" },
        { name:"Réception à venir", count:12, value:1080000, icon:"fa-circle-check" }
      ]
    },
    abc: {
      class_cards: [
        { class_name:"A", products:142, value_share:59.3, stock_value:11110000, delta:2.1 },
        { class_name:"B", products:210, value_share:27.8, stock_value:5210000, delta:-0.6 },
        { class_name:"C", products:341, value_share:11.4, stock_value:2130000, delta:-1.5 },
        { class_name:"Non classé", products:24, value_share:1.5, stock_value:280000, delta:0.2 }
      ],
      total_stock_value:18730000,
      average_criticality:2.8,
      pareto: [
        { label:"A", value:11110000, cumulative:59.3 },
        { label:"B", value:5210000, cumulative:87.1 },
        { label:"C", value:2130000, cumulative:98.5 },
        { label:"Non classé", value:280000, cumulative:100 }
      ],
      risk_by_class: [
        { label:"A", ok:57, low:20, out:8, over:15, value:11110000 },
        { label:"B", ok:49, low:23, out:10, over:18, value:5210000 },
        { label:"C", ok:36, low:28, out:15, over:21, value:2130000 }
      ],
      summary_rows: [
        { class_name:"A", skus:142, stock_value:11110000, value_share:59.3, annual_revenue:23480000, rotation:2.11, risk:2.3 },
        { class_name:"B", skus:210, stock_value:5210000, value_share:27.8, annual_revenue:9320000, rotation:1.79, risk:2.8 },
        { class_name:"C", skus:341, stock_value:2130000, value_share:11.4, annual_revenue:2810000, rotation:.92, risk:3.6 },
        { class_name:"Non classé", skus:24, stock_value:280000, value_share:1.5, annual_revenue:370000, rotation:.45, risk:2.9 }
      ]
    },
    product: {
      id: 1,
      name:"Chaise ergonomique Pro+",
      sku:"RAB-100234",
      category:"Mobilier de bureau",
      abc_class:"A",
      supplier:"OfficeMax Europe",
      current_stock:954,
      reorder_threshold:300,
      stock_value:9420000,
      service_level:96.4,
      status:"En stock",
      stock_series:[700,680,1000,760,560,720,610,390,420,360,560,880,690,920,850,1100,780],
      forecast_series:[850,1030,930,1080,1120],
      cost_structure:[
        { label:"Achat", value:16.80, percent:61.2 },
        { label:"Transport", value:4.20, percent:15.3 },
        { label:"Douanes & taxes", value:2.90, percent:10.6 },
        { label:"Stockage", value:1.80, percent:6.6 },
        { label:"Autres", value:1.75, percent:6.3 }
      ],
      bom:[
        { name:"Assise ergonomique", sku:"RAB-20001", qty:"1 u." },
        { name:"Dossier mesh", sku:"RAB-20002", qty:"1 u." },
        { name:"Accoudoir réglable", sku:"RAB-20003", qty:"2 u." }
      ],
      movements:[
        { type:"in", label:"Entrée de stock", ref:"Lot LOT-240501", qty:"+120 u.", date:"01/05/2024, 09:24" },
        { type:"out", label:"Sortie de stock", ref:"Commande SO-14522", qty:"-35 u.", date:"30/04/2024, 16:18" },
        { type:"in", label:"Ajustement", ref:"Ajustement inventaire", qty:"+15 u.", date:"29/04/2024, 11:02" },
        { type:"out", label:"Sortie de stock", ref:"Commande SO-14501", qty:"-20 u.", date:"28/04/2024, 14:37" }
      ]
    },
    forecasting: {
      mape:13.2,
      predicted_demand_value:18730000,
      coverage_days:58,
      recommended_reorders:27,
      recommended_value:12460000,
      service_level:96.4,
      anomaly_count:3,
      demand_real:[1.9,2.5,1.1,2.2,1.4,2.6,2.3,3.4,2.1],
      demand_forecast:[2.7,2.8,2.5,2.3,2.6,3.1,2.9,3.6,3.8,3.4],
      category_forecast:[
        { label:"Boissons", value:5420000 },
        { label:"Snacks", value:3910000 },
        { label:"Épicerie", value:3180000 },
        { label:"Hygiène", value:2450000 },
        { label:"Maison", value:1840000 },
        { label:"Bébé", value:1040000 },
        { label:"Autres", value:890000 }
      ],
      rupture_risk:[23,35,34,31,24,21,15,13,12,11,10,9],
      top_risk:[
        { sku:"RAB-100236", name:"Eau minérale 1,5L", category:"Boissons", demand:"1,42 M€", stock:"78 k€", gap:"-312 k€", action:"Commander 420 k€", priority:"Élevée" },
        { sku:"RAB-300112", name:"Céréales Choco 500g", category:"Épicerie", demand:"628 k€", stock:"124 k€", gap:"-96 k€", action:"Commander 150 k€", priority:"Élevée" },
        { sku:"RAB-200874", name:"Biscuits fourrés 300g", category:"Snacks", demand:"512 k€", stock:"168 k€", gap:"-48 k€", action:"Commander 80 k€", priority:"Moyenne" },
        { sku:"RAB-401077", name:"Lessive liquide 2L", category:"Hygiène", demand:"486 k€", stock:"210 k€", gap:"-12 k€", action:"À suivre", priority:"Moyenne" }
      ]
    }
  };

  window.DashboardAPI = {
    mock,
    getGeneral: () => getJson("/dashboard/stock/general", mock.general),
    getProductsPerformance: () => getJson("/dashboard/stock/products-performance?period_days=360", []),
    getAbc: () => getJson("/dashboard/stock/abc", mock.abc),
    getForecasting: () => getJson("/stock/forecast/dashboard", mock.forecasting),
    getProductDetail: (id) => getJson(`/dashboard/stock/products-performance?product_id=${id || 1}&period_days=360`, mock.product)
  };
})();
