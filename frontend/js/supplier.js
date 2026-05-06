/* ============================================================
   supplier.js — Rabbit B2B MRO Platform — Portail Fournisseur
   Dépend de config.js + auth.js
   Corrections handoff :
   - apiFetchJson retourne data directement
   - unwrap({ message, data })
   - quantity_requested (pas quantity)
   - lead_time_days integer (pas quoted_delay string)
   - supplier_note (pas quote_note)
   - Statut cancelled ajouté
   - checkAuth("fournisseur")
   ============================================================ */

let allRFQs      = [];
let filteredRFQs = [];
let currentRFQ   = null;
let currentActions = [];

const TIMELINE_STEPS = [
  { key:"draft",    label:"Brouillon",   icon:"fa-file"       },
  { key:"open",     label:"Ouverte",     icon:"fa-lock-open"  },
  { key:"quoted",   label:"Devis envoyé",icon:"fa-paper-plane"},
  { key:"accepted", label:"Acceptée",    icon:"fa-check"      },
];
const TERMINAL_NEGATIVE = ["rejected","expired","cancelled"];

// ── Init ──────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", async () => {
  const user = await checkAuth("fournisseur");  // rôle réel backend
  if (!user) return;
  const initials = (user.name||"FN").slice(0,2).toUpperCase();
  setEl("topbar-avatar",initials); setEl("sidebar-avatar",initials);
  if (user.name) setEl("sidebar-username", user.name);
  await loadRFQs();
  loadSupplierNotifications(); // non-bloquant
});

// ══════════════════════════════════════════════════════════════
// CHARGEMENT
// ══════════════════════════════════════════════════════════════
async function loadRFQs() {
  try {
    const data = await apiFetchJson("/rfqs");
    allRFQs = unwrap(data);
    if (!Array.isArray(allRFQs)) allRFQs = [];
    updateKPIs();
    applyFilter();
  } catch (err) {
    showToast("RFQs : " + (err?.error||err?.message));
    renderTable([]);
  }
}

function updateKPIs() {
  const open     = allRFQs.filter(r=>r.status==="open").length;
  const quoted   = allRFQs.filter(r=>r.status==="quoted").length;
  const accepted = allRFQs.filter(r=>r.status==="accepted").length;
  setEl("kpi-open",open); setEl("kpi-quoted",quoted); setEl("kpi-accepted",accepted);
  setBadge("sb-total",   allRFQs.length);
  setBadge("sb-open",    open);
  setBadge("sb-quoted",  quoted);
  setBadge("sb-accepted",accepted);
  const dot = document.getElementById("notif-dot");
  if (dot) dot.style.display = open > 0 ? "block" : "none";
}

function filterByStatus(status) {
  const sel = document.getElementById("status-filter");
  if (sel) sel.value = status;
  applyFilter();
}

function applyFilter() {
  const status = document.getElementById("status-filter")?.value||"";
  filteredRFQs = status ? allRFQs.filter(r=>r.status===status) : [...allRFQs];
  filteredRFQs.sort((a,b)=>{
    const p={open:0,quoted:1,draft:2,accepted:3,rejected:4,expired:5,cancelled:6};
    return (p[a.status]??99)-(p[b.status]??99)||(b.id||0)-(a.id||0);
  });
  renderTable(filteredRFQs);
  const sub = document.getElementById("rfq-sub");
  if (sub) sub.textContent = `${filteredRFQs.length} demande(s)${status ? " · "+rfqStatusLabel(status) : ""}`;
}

// ══════════════════════════════════════════════════════════════
// TABLE
// ══════════════════════════════════════════════════════════════
function renderTable(list) {
  const tbody = document.getElementById("rfq-tbody"); if(!tbody) return;
  if (!list.length) {
    tbody.innerHTML=`<tr><td colspan="7" style="text-align:center;padding:48px 0;color:#94a3b8">
      <div style="font-size:28px;margin-bottom:8px;opacity:.3">📋</div><div style="font-size:13px">Aucune demande de devis</div></td></tr>`;
    return;
  }
  tbody.innerHTML = list.map(r => {
    const isOpen = r.status==="open";
    const action = isOpen
      ? `<button class="btn-primary" style="font-size:11px;padding:6px 12px" onclick="event.stopPropagation();openDetail(${r.id})"><i class="fas fa-pen"></i> Répondre</button>`
      : `<button class="btn-sm" style="font-size:11px" onclick="event.stopPropagation();openDetail(${r.id})"><i class="fas fa-eye"></i> Voir</button>`;
    return `<tr onclick="openDetail(${r.id})">
      <td style="font-size:12px;font-weight:700;color:#94a3b8">#${r.id}</td>
      <td>
        <div style="font-weight:600;font-size:13px">${esc(r.component_name||r.product_name||"—")}</div>
        ${r.component_sku||r.product_sku?`<div style="font-size:11px;color:#94a3b8;font-family:monospace">${esc(r.component_sku||r.product_sku)}</div>`:""}
      </td>
      <td style="font-weight:600">${r.quantity_requested??"—"}</td>
      <td style="font-size:13px;color:#64748b">${r.lead_time_days!=null?`${r.lead_time_days} j`:"—"}</td>
      <td>${r.quoted_price!=null?`<span style="font-weight:700;color:var(--green)">${formatMAD(r.quoted_price)}</span>`:`<span style="color:#94a3b8;font-size:12px">—</span>`}</td>
      <td>${rfqBadge(r.status)}</td>
      <td onclick="event.stopPropagation()">${action}</td>
    </tr>`;
  }).join("");
}

// ══════════════════════════════════════════════════════════════
// PANNEAU DÉTAIL
// ══════════════════════════════════════════════════════════════
async function openDetail(rfqId) {
  const r = allRFQs.find(x=>x.id===rfqId); if(!r) return;
  currentRFQ = r;
  setEl("detail-ref", `RFQ #${r.id}`);
  document.getElementById("detail-status-badge").innerHTML = rfqBadge(r.status);
  renderTimeline(r.status);
  currentActions = await fetchActions(rfqId);
  renderDetailBody(r);
  document.getElementById("detail-overlay").classList.add("open");
  document.getElementById("detail-panel").classList.add("open");
}

async function fetchActions(rfqId) {
  try {
    const data = await apiFetchJson(`/rfqs/${rfqId}/actions`);
    const a    = unwrap(data);
    return Array.isArray(a) ? a : [];
  } catch {
    const r = allRFQs.find(x=>x.id===rfqId);
    if (!r) return [];
    if (r.status==="open")   return ["quote"];
    if (r.status==="quoted") return ["requote"];
    return [];
  }
}

function renderDetailBody(r) {
  const body = document.getElementById("detail-body"); if(!body) return;
  const canRespond = currentActions.includes("quote")||currentActions.includes("requote");
  const hasQuote   = r.quoted_price != null;
  const isTerminal = [...TERMINAL_NEGATIVE,"accepted"].includes(r.status);

  body.innerHTML = `
    <div class="detail-section">
      <div class="detail-section-title">Produit demandé</div>
      <div class="detail-meta-grid">
        <div><div class="detail-meta-label">Produit</div><div class="detail-meta-val">${esc(r.component_name||r.product_name||"—")}</div></div>
        <div><div class="detail-meta-label">SKU</div><div class="detail-meta-val" style="font-family:monospace;font-size:12px">${esc(r.component_sku||r.product_sku||"—")}</div></div>
        <div><div class="detail-meta-label">Quantité</div>
             <div class="detail-meta-val" style="font-size:18px;color:var(--blue)">${r.quantity_requested??"—"}</div></div>
        <div><div class="detail-meta-label">Délai souhaité</div><div class="detail-meta-val">Non précisé</div></div>
      </div>
      ${r.notes?`<div style="margin-top:14px;padding:12px;background:#f8fafc;border-radius:10px;font-size:13px;color:#64748b;line-height:1.5"><i class="fas fa-comment-alt" style="margin-right:6px;color:#94a3b8"></i>${esc(r.notes)}</div>`:""}
    </div>

    ${hasQuote?`
    <div class="detail-section">
      <div class="detail-section-title">Votre devis soumis</div>
      <div class="quote-summary">
        <div class="quote-summary-icon">✅</div>
        <div style="flex:1">
          <div class="quote-summary-val">${formatMAD(r.quoted_price)}</div>
          <div class="quote-summary-label">
            ${r.lead_time_days!=null?`Délai : ${r.lead_time_days} jours`:""}
            ${r.supplier_note?` · ${esc(r.supplier_note)}`:""}
          </div>
        </div>
      </div>
    </div>`:""}

    ${canRespond?`
    <div class="response-form">
      <div class="detail-section-title">${hasQuote?"Modifier votre devis":"Envoyer votre devis"}</div>
      <div class="form-group-dash">
        <label>Prix proposé (MAD HT) *</label>
        <input type="number" id="quote-price" placeholder="Ex: 15 000" min="0" step="0.01"
               value="${r.quoted_price??''}">
      </div>
      <div class="form-group-dash">
        <label>Délai de livraison (jours) *</label>
        <input type="number" id="quote-lead-time" placeholder="Ex: 7" min="1"
               value="${r.lead_time_days??''}">
      </div>
      <div class="form-group-dash">
        <label>Note fournisseur (optionnel)</label>
        <textarea id="quote-supplier-note" rows="3"
                  placeholder="Conditions, disponibilité partielle…">${esc(r.supplier_note||"")}</textarea>
      </div>
      <div class="response-actions">
        <button class="btn-primary" onclick="submitQuote()">
          <i class="fas fa-paper-plane"></i>${hasQuote?"Mettre à jour":"Envoyer le devis"}
        </button>
        <button class="btn-sm" onclick="closeDetail()">Annuler</button>
      </div>
    </div>`:""}

    ${isTerminal?`
    <div class="detail-section">
      <div style="text-align:center;padding:20px 0;color:#94a3b8">
        <div style="font-size:32px;margin-bottom:8px">
          ${r.status==="accepted"?"🎉":r.status==="rejected"?"❌":"⏰"}
        </div>
        <div style="font-size:14px;font-weight:600">
          ${r.status==="accepted"?"Devis accepté — un lot d'achat a été créé"
           :r.status==="rejected"?"Devis non retenu"
           :r.status==="cancelled"?"Demande annulée"
           :"Demande expirée"}
        </div>
      </div>
    </div>`:""}
  `;
}

function renderTimeline(currentStatus) {
  const container = document.getElementById("rfq-timeline"); if(!container) return;
  const isNeg = TERMINAL_NEGATIVE.includes(currentStatus);
  const idx   = TIMELINE_STEPS.findIndex(s=>s.key===currentStatus);

  container.innerHTML = TIMELINE_STEPS.map((step,i) => {
    let dc="", lc="";
    if (isNeg) { dc=lc = i<3?"done":"fail"; }
    else { dc=lc = i<idx?"done":i===idx?"active":""; }
    const icon = dc==="done"?`<i class="fas fa-check" style="font-size:9px"></i>`
                :dc==="active"?`<i class="fas fa-circle" style="font-size:8px"></i>`
                :dc==="fail"?`<i class="fas fa-xmark" style="font-size:9px"></i>`:"";
    return `<div class="timeline-step">
      <div class="timeline-dot ${dc}">${icon}</div>
      <div class="timeline-label ${lc}">${step.label}</div>
    </div>`;
  }).join("") + (isNeg?`
    <div class="timeline-step">
      <div class="timeline-dot fail"><i class="fas fa-xmark" style="font-size:9px"></i></div>
      <div class="timeline-label fail">${{rejected:"Rejetée",expired:"Expirée",cancelled:"Annulée"}[currentStatus]||currentStatus}</div>
    </div>`:"");
}

function closeDetail() {
  document.getElementById("detail-overlay").classList.remove("open");
  document.getElementById("detail-panel").classList.remove("open");
  currentRFQ=null; currentActions=[];
}

// ══════════════════════════════════════════════════════════════
// SOUMETTRE UN DEVIS — PATCH /rfqs/{id}/quote
// Corps : { quoted_price, lead_time_days, supplier_note }
// ══════════════════════════════════════════════════════════════
async function submitQuote() {
  if (!currentRFQ) return;
  const price    = parseFloat(document.getElementById("quote-price")?.value);
  const leadTime = parseInt(document.getElementById("quote-lead-time")?.value);
  const note     = document.getElementById("quote-supplier-note")?.value.trim();

  if (!price || price <= 0)   { showToast("Veuillez saisir un prix valide.", "warn"); return; }
  if (!leadTime || leadTime<1){ showToast("Veuillez saisir un délai en jours.", "warn"); return; }

  const btn = document.querySelector(".response-actions .btn-primary");
  if (btn) { btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Envoi…'; }

  try {
    await apiFetchJson(`/rfqs/${currentRFQ.id}/quote`, {
      method: "PATCH",
      body: JSON.stringify({
        quoted_price:  price,
        lead_time_days: leadTime,   // ← champ correct
        supplier_note: note||null,  // ← champ correct
      }),
    });
    showToast("Devis envoyé avec succès.", "success");

    // Mise à jour locale
    const idx = allRFQs.findIndex(r=>r.id===currentRFQ.id);
    if (idx!==-1) {
      allRFQs[idx] = { ...allRFQs[idx], status:"quoted", quoted_price:price, lead_time_days:leadTime, supplier_note:note };
      currentRFQ = allRFQs[idx];
    }
    closeDetail(); updateKPIs(); applyFilter();
  } catch (err) {
    showToast("Erreur : " + (err?.error||err?.message));
  } finally {
    if (btn) { btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Envoyer le devis'; }
  }
}

// ══════════════════════════════════════════════════════════════
// UTILITAIRES
// ══════════════════════════════════════════════════════════════
function rfqStatusLabel(status) {
  return RFQ_STATUS[status]?.label || status;
}

function setEl(id,val) { const e=document.getElementById(id); if(e) e.textContent=val; }
function setBadge(id,n){ const e=document.getElementById(id); if(!e)return; e.textContent=n; e.style.display=n>0?"flex":"none"; }

// ══════════════════════════════════════════════════════════════
// NOTIFICATIONS FOURNISSEUR — uniquement ses notifications
// Types pertinents : RFQ_ASSIGNED, RFQ_ACCEPTED_BY_OWNER, RFQ_REJECTED_BY_OWNER
// ══════════════════════════════════════════════════════════════
let supplierNotifications = [];

async function loadSupplierNotifications() {
  try {
    const data = await apiFetchJson("/notifications");
    supplierNotifications = unwrapItems(data);
    const unread = supplierNotifications.filter(n => !n.read_at).length;
    const dot = document.getElementById("notif-dot");
    if (dot) dot.style.display = unread > 0 ? "block" : "none";
  } catch {}
}

async function loadAndRenderSupplierNotifications() {
  await loadSupplierNotifications();
  const list = document.getElementById("supplier-notif-list");
  if (!list) return;

  if (!supplierNotifications.length) {
    list.innerHTML = `<p style="text-align:center;padding:40px 0;color:#94a3b8;font-size:13px">Aucune notification</p>`;
    return;
  }

  list.innerHTML = supplierNotifications.map(n => {
    const label    = NOTIFICATION_TYPE_LABELS[n.type] || esc(n.type) || "Notification";
    const isUnread = !n.read_at;
    return `
      <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 0;
                  border-bottom:1px solid var(--border)">
        <div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;margin-top:5px;
                    background:${isUnread ? 'var(--green)' : '#e2e8f0'}"></div>
        <div style="flex:1">
          <div style="font-weight:${isUnread ? '700' : '500'};font-size:13px">${label}</div>
          <div style="font-size:12px;color:#64748b;margin-top:2px">${esc(n.message || n.body || "—")}</div>
          <div style="font-size:11px;color:#94a3b8;margin-top:4px">${formatDate(n.created_at)}</div>
        </div>
        ${isUnread ? `<button class="btn-sm" style="font-size:11px;flex-shrink:0"
          onclick="markSupplierNotifRead(${n.id})"><i class="fas fa-check"></i></button>` : ""}
      </div>`;
  }).join("");
}

async function markSupplierNotifRead(id) {
  try {
    await apiFetchJson(`/notifications/${id}/read`, { method: "PATCH" });
    await loadAndRenderSupplierNotifications();
  } catch {}
}
