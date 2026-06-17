<?php
// pages/mechanics.php — ניהול מכונאים
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('מכונאים', 'mechanics');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי שם או התמחות…"/>
  </div>
  <button class="btn btn-primary" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>מכונאי חדש
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('full_name')">שם מלא <i class="fa fa-sort"></i></th>
          <th class="no-sort">טלפון</th>
          <th onclick="dt.sort('specialty')">התמחות <i class="fa fa-sort"></i></th>
          <th onclick="dt.sort('hourly_rate')">תעריף שעה <i class="fa fa-sort"></i></th>
          <th class="no-sort">סטטוס</th>
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

<div class="modal fade" id="mechModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">מכונאי חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="mechForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="mechId"/>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">שם מלא <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" required/>
            </div>
            <div class="col-md-6">
              <label class="form-label">טלפון</label>
              <input type="tel" name="phone" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">התמחות</label>
              <input type="text" name="specialty" class="form-control" placeholder="מנוע, חשמל, פחחות…"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">תעריף שעה (₪)</label>
              <input type="number" name="hourly_rate" class="form-control" min="0" step="0.01"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">תאריך תחילת עבודה</label>
              <input type="date" name="hire_date" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">תאריך עזיבה (אם רלוונטי)</label>
              <input type="date" name="quit_date" class="form-control"/>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="saveMech()">
            <i class="fa fa-floppy-disk me-2"></i>שמירה
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
const dt = new DataTable({
  id: 'mech', endpoint: 'mechanics.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'id',
  rowTpl: r => {
    const active = !r.quit_date;
    return `<tr>
      <td><strong>${esc(r.full_name)}</strong></td>
      <td>${esc(r.phone || '—')}</td>
      <td>${esc(r.specialty || '—')}</td>
      <td>${fmt.currency(r.hourly_rate)}</td>
      <td><span class="status-badge" style="background:${active ? '#f0fdf4' : '#fef2f2'};color:${active ? '#16a34a' : '#dc2626'}">${active ? 'פעיל' : 'עזב'}</span></td>
      <td><span class="badge bg-light text-dark">${r.job_count}</span></td>
      <td>
        <button class="btn-icon" onclick='editMech(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
        <button class="btn-icon danger" onclick='delMech(${r.id}, ${JSON.stringify(r.full_name)})' title="מחיקה"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`;
  }
});

function openCreate() {
  document.getElementById('mechForm').reset();
  document.getElementById('mechId').value = '';
  document.getElementById('modalTitle').textContent = 'מכונאי חדש';
  openModal('mechModal');
}

async function editMech(id) {
  const { data } = await api('mechanics.php?action=get&id=' + id);
  const f = document.getElementById('mechForm');
  f.reset();
  f.id.value = data.id; f.full_name.value = data.full_name;
  f.phone.value = data.phone || ''; f.specialty.value = data.specialty || '';
  f.hourly_rate.value = data.hourly_rate || 0;
  f.hire_date.value = data.hire_date || ''; f.quit_date.value = data.quit_date || '';
  document.getElementById('modalTitle').textContent = 'עריכת מכונאי';
  openModal('mechModal');
}

async function saveMech() {
  const body = serializeForm('mechForm');
  if (!body.full_name) return showToast('שם הוא שדה חובה', 'warning');
  const action = body.id ? 'update' : 'create';
  const res = await api('mechanics.php?action=' + action, { method: 'POST', body });
  closeModal('mechModal');
  showToast(res.message);
  dt.reload();
}

function delMech(id, name) {
  confirmDelete(`למחוק את המכונאי "${name}"?`, async () => {
    const res = await api('mechanics.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}
</script>
