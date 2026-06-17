<?php
// pages/appointments.php — יומן תורים
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('יומן תורים', 'appointments');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי רכב, לקוח או סיבה…"/>
  </div>
  <button class="btn btn-primary ms-auto" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>תור חדש
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('scheduled_at')">מועד <i class="fa fa-sort"></i></th>
          <th class="no-sort">רכב</th>
          <th class="no-sort">לקוח</th>
          <th class="no-sort">מכונאי</th>
          <th class="no-sort">סיבה</th>
          <th class="no-sort">משך</th>
          <th class="no-sort">סטטוס</th>
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

<div class="modal fade" id="apptModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">תור חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="apptForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="apptId"/>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">רכב <span class="text-danger">*</span></label>
              <select name="vehicle_id" id="vehSelect" class="form-select" required></select>
            </div>
            <div class="col-md-7">
              <label class="form-label">מועד <span class="text-danger">*</span></label>
              <input type="datetime-local" name="scheduled_at" class="form-control" required/>
            </div>
            <div class="col-md-5">
              <label class="form-label">משך (דקות)</label>
              <input type="number" name="duration_min" class="form-control" min="15" step="15" value="60"/>
            </div>
            <div class="col-12">
              <label class="form-label">מכונאי</label>
              <select name="mechanic_id" id="mechSelect" class="form-select"></select>
            </div>
            <div class="col-12">
              <label class="form-label">סיבת הביקור</label>
              <input type="text" name="reason" class="form-control"/>
            </div>
            <div class="col-12">
              <label class="form-label">סטטוס</label>
              <select name="status" class="form-select">
                <option value="scheduled">נקבע</option>
                <option value="arrived">הגיע</option>
                <option value="done">הושלם</option>
                <option value="cancelled">בוטל</option>
                <option value="no_show">לא הופיע</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="saveAppt()">
            <i class="fa fa-floppy-disk me-2"></i>שמירה
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
const apptStatus = {
  scheduled: { l: 'נקבע',    c: '#0d6efd' },
  arrived:   { l: 'הגיע',    c: '#fd7e14' },
  done:      { l: 'הושלם',   c: '#198754' },
  cancelled: { l: 'בוטל',    c: '#dc3545' },
  no_show:   { l: 'לא הופיע', c: '#6c757d' },
};

let vehicles = [], mechanics = [];
async function loadOptions() {
  const [v, m] = await Promise.all([
    api('vehicles.php?action=options'),
    api('mechanics.php?action=options'),
  ]);
  vehicles = v.data; mechanics = m.data;
  document.getElementById('vehSelect').innerHTML =
    '<option value="">בחר/י רכב…</option>' +
    vehicles.map(o => `<option value="${o.id}">${esc(o.plate_number)} — ${esc(o.customer_name)}</option>`).join('');
  document.getElementById('mechSelect').innerHTML =
    '<option value="">— לא משויך —</option>' +
    mechanics.map(o => `<option value="${o.id}">${esc(o.full_name)}</option>`).join('');
}
loadOptions();

const dt = new DataTable({
  id: 'appt', endpoint: 'appointments.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'scheduled_at',
  rowTpl: r => {
    const st = apptStatus[r.status] || { l: r.status, c: '#6c757d' };
    return `<tr>
      <td><strong>${fmt.datetime(r.scheduled_at)}</strong></td>
      <td><span class="badge bg-light text-dark">${esc(r.plate_number)}</span></td>
      <td>${esc(r.customer_name)}</td>
      <td>${esc(r.mechanic_name || '—')}</td>
      <td>${esc(r.reason || '—')}</td>
      <td>${r.duration_min} ד׳</td>
      <td><span class="status-badge" style="background:${st.c}22;color:${st.c}">${st.l}</span></td>
      <td>
        <button class="btn-icon" onclick='editAppt(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
        <button class="btn-icon danger" onclick='delAppt(${r.id})' title="מחיקה"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`;
  }
});

function openCreate() {
  document.getElementById('apptForm').reset();
  document.getElementById('apptId').value = '';
  document.getElementById('modalTitle').textContent = 'תור חדש';
  openModal('apptModal');
}

async function editAppt(id) {
  const { data } = await api('appointments.php?action=get&id=' + id);
  const f = document.getElementById('apptForm');
  f.reset();
  f.id.value = data.id;
  f.vehicle_id.value = data.vehicle_id;
  f.scheduled_at.value = (data.scheduled_at || '').replace(' ', 'T').slice(0, 16);
  f.duration_min.value = data.duration_min;
  f.mechanic_id.value = data.mechanic_id || '';
  f.reason.value = data.reason || '';
  f.status.value = data.status;
  document.getElementById('modalTitle').textContent = 'עריכת תור';
  openModal('apptModal');
}

async function saveAppt() {
  const body = serializeForm('apptForm');
  if (!body.vehicle_id || !body.scheduled_at) return showToast('רכב ומועד הם שדות חובה', 'warning');
  const action = body.id ? 'update' : 'create';
  const res = await api('appointments.php?action=' + action, { method: 'POST', body });
  closeModal('apptModal');
  showToast(res.message);
  dt.reload();
}

function delAppt(id) {
  confirmDelete('למחוק תור זה?', async () => {
    const res = await api('appointments.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}
</script>
