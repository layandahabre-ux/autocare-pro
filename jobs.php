<?php
// pages/jobs.php — כרטיסי עבודה
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('כרטיסי עבודה', 'jobs');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי מספר כרטיס, רכב או לקוח…"/>
  </div>
  <select id="statusFilter" class="form-select" style="max-width:180px" onchange="dt.setFilter('status_id', this.value)">
    <option value="">כל הסטטוסים</option>
  </select>
  <button class="btn btn-primary ms-auto" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>כרטיס עבודה חדש
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('job_number')">מספר כרטיס <i class="fa fa-sort"></i></th>
          <th class="no-sort">רכב</th>
          <th class="no-sort">לקוח</th>
          <th class="no-sort">מכונאי</th>
          <th class="no-sort">סטטוס</th>
          <th class="no-sort">סה״כ</th>
          <th onclick="dt.sort('opened_at')">נפתח <i class="fa fa-sort"></i></th>
          <th class="no-sort" style="width:140px">פעולות</th>
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

<!-- Modal -->
<div class="modal fade" id="jobModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">כרטיס עבודה חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="jobForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="jobId"/>
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">רכב <span class="text-danger">*</span></label>
              <select name="vehicle_id" id="vehSelect" class="form-select" required></select>
            </div>
            <div class="col-md-4">
              <label class="form-label">מכונאי</label>
              <select name="mechanic_id" id="mechSelect" class="form-select"></select>
            </div>
            <div class="col-md-3">
              <label class="form-label">סטטוס</label>
              <select name="status_id" id="statusSelect" class="form-select"></select>
            </div>
            <div class="col-md-3">
              <label class="form-label">ק״מ בכניסה</label>
              <input type="number" name="mileage_in" class="form-control" min="0"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">שעות עבודה</label>
              <input type="number" name="labor_hours" id="laborHours" class="form-control" min="0" step="0.5" value="0" oninput="recalc()"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">תעריף שעה (₪)</label>
              <input type="number" name="labor_rate" id="laborRate" class="form-control" min="0" step="0.01" value="180" oninput="recalc()"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">עלות עבודה</label>
              <input type="text" id="laborTotal" class="form-control" readonly/>
            </div>
            <div class="col-12">
              <label class="form-label">תיאור התקלה / הטיפול המבוקש</label>
              <textarea name="description" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">אבחון המכונאי</label>
              <textarea name="diagnosis" class="form-control" rows="2"></textarea>
            </div>
          </div>

          <hr class="my-4"/>

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0"><i class="fa fa-boxes-stacked me-2 text-primary"></i>חלקים שנוצלו</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addPartBtn" onclick="addPartLine()">
              <i class="fa fa-plus me-1"></i>הוסף חלק
            </button>
          </div>
          <div id="partsContainer"></div>
          <div id="partsNote" class="text-muted small" style="display:none">
            <i class="fa fa-circle-info me-1"></i>ניתן להוסיף חלקים רק בעת יצירת כרטיס חדש. בכרטיס קיים החלקים נעולים.
          </div>

          <div class="text-start mt-3 p-3" style="background:var(--bg);border-radius:8px">
            <div class="d-flex justify-content-between"><span>עלות עבודה:</span><strong id="sumLabor">₪ 0.00</strong></div>
            <div class="d-flex justify-content-between"><span>עלות חלקים:</span><strong id="sumParts">₪ 0.00</strong></div>
            <div class="d-flex justify-content-between fs-5 mt-1 pt-1" style="border-top:1px solid var(--border)">
              <span>סה״כ:</span><strong id="sumTotal" class="text-primary">₪ 0.00</strong>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="saveJob()">
            <i class="fa fa-floppy-disk me-2"></i>שמירה
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
let vehicles = [], mechanics = [], statuses = [], partOptions = [], partLines = [], editMode = false;

async function loadAll() {
  const [v, m, s, p] = await Promise.all([
    api('vehicles.php?action=options'),
    api('mechanics.php?action=options'),
    api('jobs.php?action=statuses'),
    api('parts.php?action=options'),
  ]);
  vehicles = v.data; mechanics = m.data; statuses = s.data; partOptions = p.data;

  document.getElementById('vehSelect').innerHTML =
    '<option value="">בחר/י רכב…</option>' +
    vehicles.map(o => `<option value="${o.id}">${esc(o.plate_number)} — ${esc(o.customer_name)}</option>`).join('');
  document.getElementById('mechSelect').innerHTML =
    '<option value="">— לא משויך —</option>' +
    mechanics.map(o => `<option value="${o.id}" data-rate="${o.hourly_rate}">${esc(o.full_name)}</option>`).join('');
  document.getElementById('statusSelect').innerHTML =
    statuses.map(o => `<option value="${o.id}">${esc(o.status_name)}</option>`).join('');
  document.getElementById('statusFilter').innerHTML =
    '<option value="">כל הסטטוסים</option>' +
    statuses.map(o => `<option value="${o.id}">${esc(o.status_name)}</option>`).join('');
}
loadAll();

const dt = new DataTable({
  id: 'job', endpoint: 'jobs.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'opened_at',
  rowTpl: r => {
    const total = (+r.labor_total) + (+r.parts_total);
    return `<tr>
      <td><strong>${esc(r.job_number)}</strong></td>
      <td><span class="badge bg-light text-dark">${esc(r.plate_number)}</span> ${esc(r.model || '')}</td>
      <td>${esc(r.customer_name)}</td>
      <td>${esc(r.mechanic_name || '—')}</td>
      <td><span class="status-badge" style="background:${r.color_hex}22;color:${r.color_hex}">${esc(r.status_name)}</span></td>
      <td><strong>${fmt.currency(total)}</strong></td>
      <td>${fmt.date(r.opened_at)}</td>
      <td>
        <button class="btn-icon" onclick='editJob(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
        <button class="btn-icon" onclick='location.href="${'<?= APP_URL ?>'}/pages/invoices.php?from_job=${r.id}"' title="צור חשבונית"><i class="fa fa-file-invoice"></i></button>
        <button class="btn-icon danger" onclick='delJob(${r.id}, ${JSON.stringify(r.job_number)})' title="מחיקה"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`;
  }
});

// ── חלקים דינמיים ──
function addPartLine(partId = '', qty = 1) {
  const idx = partLines.length;
  partLines.push({ partId, qty });
  const div = document.createElement('div');
  div.className = 'row g-2 mb-2 align-items-end part-line';
  div.dataset.idx = idx;
  div.innerHTML = `
    <div class="col-md-6">
      <select class="form-select form-select-sm part-select" onchange="lineChanged(${idx})">
        <option value="">בחר/י חלק…</option>
        ${partOptions.map(p => `<option value="${p.id}" data-price="${p.sell_price}" data-stock="${p.stock_qty}">${esc(p.part_name)} (מלאי: ${p.stock_qty})</option>`).join('')}
      </select>
    </div>
    <div class="col-md-3">
      <input type="number" class="form-control form-control-sm part-qty" min="1" value="${qty}" oninput="lineChanged(${idx})" placeholder="כמות"/>
    </div>
    <div class="col-md-2"><span class="part-linetotal small">₪ 0.00</span></div>
    <div class="col-md-1"><button type="button" class="btn-icon danger" onclick="removePartLine(${idx})"><i class="fa fa-xmark"></i></button></div>
  `;
  document.getElementById('partsContainer').appendChild(div);
  if (partId) {
    const sel = div.querySelector('.part-select');
    sel.value = partId;
    lineChanged(idx);
  }
}

function removePartLine(idx) {
  const el = document.querySelector(`.part-line[data-idx="${idx}"]`);
  if (el) el.remove();
  partLines[idx] = null;
  recalc();
}

function lineChanged(idx) {
  const el = document.querySelector(`.part-line[data-idx="${idx}"]`);
  if (!el) return;
  const sel = el.querySelector('.part-select');
  const qty = +el.querySelector('.part-qty').value || 0;
  const opt = sel.options[sel.selectedIndex];
  const price = +(opt?.dataset.price || 0);
  const stock = +(opt?.dataset.stock || 0);
  if (qty > stock && sel.value) {
    el.querySelector('.part-qty').classList.add('is-invalid');
  } else {
    el.querySelector('.part-qty').classList.remove('is-invalid');
  }
  el.querySelector('.part-linetotal').textContent = fmt.currency(price * qty);
  partLines[idx] = { partId: sel.value, qty };
  recalc();
}

function recalc() {
  const hours = +document.getElementById('laborHours').value || 0;
  const rate  = +document.getElementById('laborRate').value || 0;
  const labor = hours * rate;
  document.getElementById('laborTotal').value = fmt.currency(labor);

  let parts = 0;
  document.querySelectorAll('.part-line').forEach(el => {
    const sel = el.querySelector('.part-select');
    const qty = +el.querySelector('.part-qty').value || 0;
    const opt = sel.options[sel.selectedIndex];
    parts += (+(opt?.dataset.price || 0)) * qty;
  });

  document.getElementById('sumLabor').textContent = fmt.currency(labor);
  document.getElementById('sumParts').textContent = fmt.currency(parts);
  document.getElementById('sumTotal').textContent = fmt.currency(labor + parts);
}

// auto-fill rate from mechanic
document.getElementById('mechSelect').addEventListener('change', function () {
  const opt = this.options[this.selectedIndex];
  const rate = opt?.dataset.rate;
  if (rate && +rate > 0) { document.getElementById('laborRate').value = rate; recalc(); }
});

function openCreate() {
  editMode = false;
  document.getElementById('jobForm').reset();
  document.getElementById('jobId').value = '';
  document.getElementById('partsContainer').innerHTML = '';
  partLines = [];
  document.getElementById('addPartBtn').style.display = '';
  document.getElementById('partsNote').style.display = 'none';
  document.getElementById('laborRate').value = 180;
  document.getElementById('laborHours').value = 0;
  document.getElementById('modalTitle').textContent = 'כרטיס עבודה חדש';
  recalc();
  openModal('jobModal');
}

async function editJob(id) {
  editMode = true;
  const { data } = await api('jobs.php?action=get&id=' + id);
  const f = document.getElementById('jobForm');
  f.reset();
  f.id.value = data.id;
  f.vehicle_id.value = data.vehicle_id;
  f.mechanic_id.value = data.mechanic_id || '';
  f.status_id.value = data.status_id;
  f.mileage_in.value = data.mileage_in || '';
  f.labor_hours.value = data.labor_hours;
  f.labor_rate.value = data.labor_rate;
  f.description.value = data.description || '';
  f.diagnosis.value = data.diagnosis || '';

  // הצגת חלקים קיימים (קריאה בלבד בעריכה)
  document.getElementById('partsContainer').innerHTML = data.parts.length
    ? data.parts.map(p => `<div class="row g-2 mb-2">
        <div class="col-md-6"><input class="form-control form-control-sm" value="${esc(p.part_name)}" readonly/></div>
        <div class="col-md-3"><input class="form-control form-control-sm" value="כמות: ${p.quantity}" readonly/></div>
        <div class="col-md-3"><input class="form-control form-control-sm" value="${fmt.currency(p.unit_price * p.quantity)}" readonly/></div>
      </div>`).join('')
    : '<div class="text-muted small">לא נוצלו חלקים בכרטיס זה.</div>';
  document.getElementById('addPartBtn').style.display = 'none';
  document.getElementById('partsNote').style.display = '';
  partLines = [];

  document.getElementById('modalTitle').textContent = 'עריכת כרטיס ' + data.job_number;
  recalc();
  openModal('jobModal');
}

async function saveJob() {
  const body = serializeForm('jobForm');
  if (!body.vehicle_id) return showToast('יש לבחור רכב', 'warning');

  if (!editMode) {
    body.parts = partLines.filter(l => l && l.partId && +l.qty > 0)
      .map(l => ({ part_id: +l.partId, quantity: +l.qty }));
  }
  const action = body.id ? 'update' : 'create';
  const res = await api('jobs.php?action=' + action, { method: 'POST', body });
  closeModal('jobModal');
  showToast(res.message);
  dt.reload();
}

function delJob(id, num) {
  confirmDelete(`למחוק את כרטיס העבודה "${num}"? החלקים יוחזרו למלאי.`, async () => {
    const res = await api('jobs.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}
</script>
