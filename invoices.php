<?php
// pages/invoices.php — חשבוניות
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
$fromJob = (int)($_GET['from_job'] ?? 0);
renderLayout('חשבוניות', 'invoices');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי מספר חשבונית או לקוח…"/>
  </div>
  <select id="statusFilter" class="form-select" style="max-width:170px" onchange="dt.setFilter('status', this.value)">
    <option value="">כל הסטטוסים</option>
    <option value="open">פתוחה</option>
    <option value="partial">תשלום חלקי</option>
    <option value="paid">שולמה</option>
    <option value="cancelled">בוטלה</option>
  </select>
  <button class="btn btn-primary ms-auto" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>חשבונית חדשה
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('invoice_number')">מספר חשבונית <i class="fa fa-sort"></i></th>
          <th class="no-sort">לקוח</th>
          <th onclick="dt.sort('issue_date')">תאריך <i class="fa fa-sort"></i></th>
          <th onclick="dt.sort('total')">סה״כ <i class="fa fa-sort"></i></th>
          <th class="no-sort">שולם</th>
          <th class="no-sort">יתרה</th>
          <th class="no-sort">סטטוס</th>
          <th class="no-sort" style="width:150px">פעולות</th>
        </tr>
      </thead>
      <tbody id="tbody"></tbody>
    </table>
  </div>
  <div class="card-box-body">
    <div class="pager-row">
      <div class="pager-info" id="info"></div>
      <div class="pager" id="pager"></div>
    </div>
  </div>
</div>

<!-- Modal: יצירה / עריכה -->
<div class="modal fade" id="invModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">חשבונית חדשה</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="invForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="invId"/>
          <input type="hidden" name="job_id" id="invJobId"/>
          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label">לקוח <span class="text-danger">*</span></label>
              <select name="customer_id" id="custSelect" class="form-select" required></select>
            </div>
            <div class="col-md-5">
              <label class="form-label">תאריך הוצאה</label>
              <input type="date" name="issue_date" class="form-control"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">סכום לפני מע״מ (₪)</label>
              <input type="number" name="subtotal" id="subtotal" class="form-control" min="0" step="0.01" value="0" oninput="recalc()"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">הנחה (₪)</label>
              <input type="number" name="discount" id="discount" class="form-control" min="0" step="0.01" value="0" oninput="recalc()"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">שיעור מע״מ (%)</label>
              <input type="number" name="vat_rate" id="vatRate" class="form-control" min="0" step="0.1" value="18" oninput="recalc()"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">סכום ששולם (₪)</label>
              <input type="number" name="paid_amount" class="form-control" min="0" step="0.01" value="0"/>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="w-100 p-2 text-start" style="background:var(--bg);border-radius:8px">
                <div class="d-flex justify-content-between small"><span>מע״מ:</span><span id="calcVat">₪ 0.00</span></div>
                <div class="d-flex justify-content-between fw-bold"><span>סה״כ לתשלום:</span><span id="calcTotal" class="text-primary">₪ 0.00</span></div>
              </div>
            </div>
          </div>
          <div id="jobBadge" class="alert alert-info mt-3 mb-0 small" style="display:none">
            <i class="fa fa-link me-1"></i>חשבונית זו משויכת לכרטיס עבודה <strong id="jobBadgeNum"></strong>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="saveInv()">
            <i class="fa fa-floppy-disk me-2"></i>שמירה
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: תשלום -->
<div class="modal fade" id="payModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">רישום תשלום</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="payInvId"/>
        <p class="mb-2">חשבונית: <strong id="payInvNum"></strong></p>
        <p class="mb-3">יתרה לתשלום: <strong id="payBalance" class="text-danger"></strong></p>
        <label class="form-label">סכום לתשלום (₪)</label>
        <input type="number" id="payAmount" class="form-control" min="0" step="0.01"/>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
        <button type="button" class="btn btn-success" onclick="doPay()">
          <i class="fa fa-check me-2"></i>רישום תשלום
        </button>
      </div>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
const APP = '<?= APP_URL ?>';
const fromJob = <?= $fromJob ?>;
const invStatus = {
  open:      { l: 'פתוחה',       c: '#0d6efd' },
  partial:   { l: 'תשלום חלקי',  c: '#fd7e14' },
  paid:      { l: 'שולמה',       c: '#198754' },
  cancelled: { l: 'בוטלה',       c: '#dc3545' },
};

let customers = [];
async function loadCustomers() {
  const { data } = await api('customers.php?action=options');
  customers = data;
  document.getElementById('custSelect').innerHTML =
    '<option value="">בחר/י לקוח…</option>' +
    customers.map(o => `<option value="${o.id}">${esc(o.full_name)}</option>`).join('');
}

const dt = new DataTable({
  id: 'inv', endpoint: 'invoices.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'issue_date',
  rowTpl: r => {
    const st = invStatus[r.status] || { l: r.status, c: '#6c757d' };
    const balance = +r.total - +r.paid_amount;
    return `<tr>
      <td><strong>${esc(r.invoice_number)}</strong></td>
      <td>${esc(r.customer_name)}</td>
      <td>${fmt.date(r.issue_date)}</td>
      <td><strong>${fmt.currency(r.total)}</strong></td>
      <td>${fmt.currency(r.paid_amount)}</td>
      <td>${balance > 0 ? `<span class="text-danger">${fmt.currency(balance)}</span>` : '<span class="text-success">—</span>'}</td>
      <td><span class="status-badge" style="background:${st.c}22;color:${st.c}">${st.l}</span></td>
      <td>
        <button class="btn-icon" onclick='location.href="${APP}/pages/print_invoice.php?id=${r.id}"' title="הדפסה"><i class="fa fa-print"></i></button>
        ${balance > 0 && r.status !== 'cancelled' ? `<button class="btn-icon" style="color:#16a34a;border-color:#bbf7d0" onclick='openPay(${r.id}, ${JSON.stringify(r.invoice_number)}, ${balance})' title="תשלום"><i class="fa fa-coins"></i></button>` : ''}
        <button class="btn-icon" onclick='editInv(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
        <button class="btn-icon danger" onclick='delInv(${r.id}, ${JSON.stringify(r.invoice_number)})' title="מחיקה"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`;
  }
});

function recalc() {
  const sub = +document.getElementById('subtotal').value || 0;
  const disc = +document.getElementById('discount').value || 0;
  const vatRate = +document.getElementById('vatRate').value || 0;
  const base = Math.max(0, sub - disc);
  const vat = base * vatRate / 100;
  document.getElementById('calcVat').textContent = fmt.currency(vat);
  document.getElementById('calcTotal').textContent = fmt.currency(base + vat);
}

function openCreate() {
  document.getElementById('invForm').reset();
  document.getElementById('invId').value = '';
  document.getElementById('invJobId').value = '';
  document.getElementById('jobBadge').style.display = 'none';
  document.getElementById('invForm').issue_date.value = new Date().toISOString().slice(0, 10);
  document.getElementById('modalTitle').textContent = 'חשבונית חדשה';
  recalc();
  openModal('invModal');
}

async function editInv(id) {
  const { data } = await api('invoices.php?action=get&id=' + id);
  const f = document.getElementById('invForm');
  f.reset();
  f.id.value = data.id;
  f.job_id.value = data.job_id || '';
  f.customer_id.value = data.customer_id;
  f.issue_date.value = data.issue_date;
  f.subtotal.value = data.subtotal;
  f.discount.value = data.discount;
  f.vat_rate.value = data.vat_rate;
  f.paid_amount.value = data.paid_amount;
  if (data.job_number) {
    document.getElementById('jobBadge').style.display = '';
    document.getElementById('jobBadgeNum').textContent = data.job_number;
  } else {
    document.getElementById('jobBadge').style.display = 'none';
  }
  document.getElementById('modalTitle').textContent = 'עריכת חשבונית ' + data.invoice_number;
  recalc();
  openModal('invModal');
}

async function saveInv() {
  const body = serializeForm('invForm');
  if (!body.customer_id) return showToast('יש לבחור לקוח', 'warning');
  const action = body.id ? 'update' : 'create';
  const res = await api('invoices.php?action=' + action, { method: 'POST', body });
  closeModal('invModal');
  showToast(res.message);
  dt.reload();
}

function delInv(id, num) {
  confirmDelete(`למחוק את החשבונית "${num}"?`, async () => {
    const res = await api('invoices.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}

// ── תשלום ──
function openPay(id, num, balance) {
  document.getElementById('payInvId').value = id;
  document.getElementById('payInvNum').textContent = num;
  document.getElementById('payBalance').textContent = fmt.currency(balance);
  document.getElementById('payAmount').value = balance.toFixed(2);
  openModal('payModal');
}
async function doPay() {
  const id = +document.getElementById('payInvId').value;
  const amount = +document.getElementById('payAmount').value || 0;
  if (amount <= 0) return showToast('יש להזין סכום חיובי', 'warning');
  const res = await api('invoices.php?action=pay', { method: 'POST', body: { id, amount } });
  closeModal('payModal');
  showToast(res.message);
  dt.reload();
}

// ── בנייה אוטומטית מכרטיס עבודה ──
async function initFromJob() {
  if (!fromJob) return;
  const { data } = await api('invoices.php?action=build_from_job&job_id=' + fromJob);
  openCreate();
  const f = document.getElementById('invForm');
  f.job_id.value = fromJob;
  f.customer_id.value = data.customer_id;
  f.subtotal.value = data.subtotal.toFixed(2);
  document.getElementById('jobBadge').style.display = '';
  document.getElementById('jobBadgeNum').textContent = 'מספר ' + fromJob;
  recalc();
}

(async () => { await loadCustomers(); await initFromJob(); })();
</script>
