async function fetchRfqs() {
  const response = await fetch('../backend/api/rfq/list.php', {
    method: 'GET',
    credentials: 'include'
  });

  const data = await response.json();

  if (!response.ok) {
    console.error(data.error || 'Failed to fetch RFQs');
    return [];
  }

  return data.rfqs || [];
}

function renderRfqActions(rfq) {
  const actions = rfq.allowed_actions || {};
  let buttons = '';

  if (actions.open) {
    buttons += `<button onclick="openRfq(${rfq.id})">Open</button>`;
  }

  if (actions.respond) {
    buttons += `<button onclick="respondToRfqPrompt(${rfq.id})">Respond</button>`;
  }

  if (actions.accept) {
    buttons += `<button onclick="acceptRfq(${rfq.id})">Accept</button>`;
  }

  if (actions.reject) {
    buttons += `<button onclick="rejectRfq(${rfq.id})">Reject</button>`;
  }

  if (actions.expire) {
    buttons += `<button onclick="expireRfq(${rfq.id})">Expire</button>`;
  }

  return buttons;
}

function renderRfqs(rfqs) {
  const container = document.getElementById('rfq-list');
  if (!container) return;

  if (!rfqs.length) {
    container.innerHTML = '<p>No RFQs found.</p>';
    return;
  }

  container.innerHTML = rfqs.map(rfq => `
    <div class="rfq-card">
      <h3>RFQ #${rfq.id}</h3>
      <p><strong>Component:</strong> ${rfq.component_name ?? 'N/A'}</p>
      <p><strong>SKU:</strong> ${rfq.sku ?? 'N/A'}</p>
      <p><strong>Status:</strong> ${rfq.status}</p>
      <p><strong>Quantity:</strong> ${rfq.quantity_requested}</p>
      <p><strong>Supplier:</strong> ${rfq.supplier_name ?? 'N/A'}</p>
      <p><strong>Client:</strong> ${rfq.client_name ?? 'N/A'}</p>
      <p><strong>Quoted Price:</strong> ${rfq.quoted_price ?? '—'}</p>
      <p><strong>Decision Note:</strong> ${rfq.decision_note ?? '—'}</p>
      <div class="rfq-actions">
        ${renderRfqActions(rfq)}
      </div>
    </div>
  `).join('');
}

async function loadRfqs() {
  const rfqs = await fetchRfqs();
  renderRfqs(rfqs);
}

async function openRfq(rfqId) {
  await postRfqAction('../backend/api/rfq/open.php', { rfq_id: rfqId });
}

async function acceptRfq(rfqId) {
  const decisionNote = prompt('Approval note:') || '';
  await postRfqAction('../backend/api/rfq/accept.php', {
    rfq_id: rfqId,
    decision_note: decisionNote
  });
}

async function rejectRfq(rfqId) {
  const decisionNote = prompt('Rejection reason:') || '';
  await postRfqAction('../backend/api/rfq/reject.php', {
    rfq_id: rfqId,
    decision_note: decisionNote
  });
}

async function expireRfq(rfqId) {
  const decisionNote = prompt('Expiry note:') || '';
  await postRfqAction('../backend/api/rfq/expire.php', {
    rfq_id: rfqId,
    decision_note: decisionNote
  });
}

async function respondToRfqPrompt(rfqId) {
  const quotedPrice = prompt('Quoted price:');
  if (!quotedPrice) return;

  const leadTimeDays = prompt('Lead time (days):') || '';
  const supplierNote = prompt('Supplier note:') || '';

  await postRfqAction('../backend/api/rfq/respond.php', {
    rfq_id: rfqId,
    quoted_price: parseFloat(quotedPrice),
    lead_time_days: leadTimeDays ? parseInt(leadTimeDays, 10) : null,
    supplier_note: supplierNote
  });
}

async function postRfqAction(url, payload) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (!response.ok) {
      alert(data.error || 'Action failed');
      return;
    }

    alert(data.message || 'Action successful');
    await loadRfqs();
  } catch (error) {
    console.error(error);
    alert('Network or server error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadRfqs();
});