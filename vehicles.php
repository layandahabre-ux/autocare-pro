<?php
// pages/vehicles.php — ניהול רכבים
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('רכבים', 'vehicles');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי מספר רישוי, דגם או לקוח…"/>
  </div>
  <button class="btn btn-primary" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>רכב חדש
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('plate_number')">מספר רישוי <i class="fa fa-sort"></i></th>
          <th class="no-sort">יצרן ודגם</th>
          <th onclick="dt.sort('year')">שנה <i class="fa fa-sort"></i></th>
          <th class="no-sort">סוג מנוע</th>
          <th onclick="dt.sort('current_mileage')">ק״מ <i class="fa fa-sort"></i></th>
          <th class="no-sort">בעלים</th>
          <th class="no-sort">כרטיסים</th>
          <th class="no-sort" style="width:110px">פעולות</th>
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

<div class="modal fade" id="vehModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">רכב חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="vehForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="vehId"/>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">לקוח (בעלים) <span class="text-danger">*</span></label>
              <select name="customer_id" id="custSelect" class="form-select" required></select>
            </div>
            <div class="col-md-6">
              <label class="form-label">מספר רישוי <span class="text-danger">*</span></label>
              <input type="text" name="plate_number" class="form-control" required placeholder="12-345-67"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">יצרן</label>
              <select name="make_id" id="makeSelect" class="form-select"></select>
            </div>
            <div class="col-md-4">
              <label class="form-label">דגם</label>
              <input type="text" name="model" class="form-control"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">שנת ייצור</label>
              <input type="number" name="year" class="form-control" min="1980" max="2030"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">צבע</label>
              <input type="text" name="color" class="form-control"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">סוג מנוע</label>
              <select name="engine_type" class="form-select">
                <option value="petrol">בנזין</option>
                <option value="diesel">דיזל</option>
                <option value="hybrid">היברידי</option>
                <option value="electric">חשמלי</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">ק״מ נוכחי</label>
              <input type="number" name="current_mileage" class="form-control" min="0"/>
            </div>
            <div class="col-12">
              <label class="form-label">מספר שלדה (VIN)</label>
              <input type="text" name="vin" class="form-control"/>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="saveVeh()">
            <i class="fa fa-floppy-disk me-2"></i>שמירה
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
const engineLabels = { petrol: 'בנזין', diesel: 'דיזל', hybrid: 'היברידי', electric: 'חשמלי' };

const dt = new DataTable({
  id: 'veh', endpoint: 'vehicles.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'id',
  rowTpl: r => `<tr>
    <td><strong>${esc(r.plate_number)}</strong></td>
    <td>${esc([r.make_name, r.model].filter(Boolean).join(' ')) || '—'}</td>
    <td>${r.year || '—'}</td>
    <td>${engineLabels[r.engine_type] || r.engine_type}</td>
    <td>${fmt.number(r.current_mileage)}</td>
    <td>${esc(r.customer_name)}</td>
    <td><span class="badge bg-light text-dark">${r.job_count}</span></td>
    <td>
      <button class="btn-icon" onclick='editVeh(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
      <button class="btn-icon danger" onclick='delVeh(${r.id}, ${JSON.stringify(r.plate_number)})' title="מחיקה"><i class="fa fa-trash"></i></button>
    </td>
  </tr>`
});

async function loadOptions() {
  const [cust, makes] = await Promise.all([
    api('customers.php?action=options'),
    api('vehicles.php?action=makes'),
  ]);
  document.getElementById('custSelect').innerHTML =
    '<option value="">בחר/י לקוח…</option>' +
    cust.data.map(o => `<option value="${o.id}">${esc(o.full_name)} (${esc(o.phone)})</option>`).join('');
  document.getElementById('makeSelect').innerHTML =
    '<option value="">— ללא —</option>' +
    makes.data.map(o => `<option value="${o.id}">${esc(o.make_name)}</option>`).join('');
}
loadOptions();

function openCreate() {
  document.getElementById('vehForm').reset();
  document.getElementById('vehId').value = '';
  document.getElementById('modalTitle').textContent = 'רכב חדש';
  openModal('vehModal');
}

async function editVeh(id) {
  const { data } = await api('vehicles.php?action=get&id=' + id);
  const f = document.getElementById('vehForm');
  f.reset();
  f.id.value = data.id;
  f.customer_id.value = data.customer_id;
  f.plate_number.value = data.plate_number;
  f.make_id.value = data.make_id || '';
  f.model.value = data.model || '';
  f.year.value = data.year || '';
  f.color.value = data.color || '';
  f.engine_type.value = data.engine_type || 'petrol';
  f.current_mileage.value = data.current_mileage || 0;
  f.vin.value = data.vin || '';
  document.getElementById('modalTitle').textContent = 'עריכת רכב';
  openModal('vehModal');
}

async function saveVeh() {
  const body = serializeForm('vehForm');
  if (!body.customer_id || !body.plate_number) return showToast('לקוח ומספר רישוי הם שדות חובה', 'warning');
  const action = body.id ? 'update' : 'create';
  const res = await api('vehicles.php?action=' + action, { method: 'POST', body });
  closeModal('vehModal');
  showToast(res.message);
  dt.reload();
}

function delVeh(id, plate) {
  confirmDelete(`למחוק את הרכב "${plate}"?`, async () => {
    const res = await api('vehicles.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}
</script>
