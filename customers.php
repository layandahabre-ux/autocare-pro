<?php
// pages/customers.php — ניהול לקוחות
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('לקוחות', 'customers');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי שם, טלפון או ת״ז…"/>
  </div>
  <button class="btn btn-primary" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>לקוח חדש
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('full_name')">שם מלא <i class="fa fa-sort"></i></th>
          <th class="no-sort">ת״ז / ח״פ</th>
          <th class="no-sort">טלפון</th>
          <th class="no-sort">אימייל</th>
          <th onclick="dt.sort('city')">עיר <i class="fa fa-sort"></i></th>
          <th class="no-sort">רכבים</th>
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

<!-- Modal -->
<div class="modal fade" id="custModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">לקוח חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="custForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="custId"/>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">שם מלא <span class="text-danger">*</span></label>
              <input type="text" name="full_name" class="form-control" required/>
            </div>
            <div class="col-md-6">
              <label class="form-label">ת״ז / ח״פ</label>
              <input type="text" name="id_number" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">טלפון <span class="text-danger">*</span></label>
              <input type="tel" name="phone" class="form-control" required/>
            </div>
            <div class="col-md-6">
              <label class="form-label">אימייל</label>
              <input type="email" name="email" class="form-control"/>
            </div>
            <div class="col-md-8">
              <label class="form-label">כתובת</label>
              <input type="text" name="address" class="form-control"/>
            </div>
            <div class="col-md-4">
              <label class="form-label">עיר</label>
              <input type="text" name="city" class="form-control"/>
            </div>
            <div class="col-12">
              <label class="form-label">הערות</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="saveCust()">
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
  id: 'cust', endpoint: 'customers.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'id',
  rowTpl: r => `<tr>
    <td><strong>${esc(r.full_name)}</strong></td>
    <td>${esc(r.id_number || '—')}</td>
    <td><a href="tel:${esc(r.phone)}">${esc(r.phone)}</a></td>
    <td>${esc(r.email || '—')}</td>
    <td>${esc(r.city || '—')}</td>
    <td><span class="badge bg-light text-dark">${r.vehicle_count}</span></td>
    <td>
      <button class="btn-icon" onclick='editCust(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
      <button class="btn-icon danger" onclick='delCust(${r.id}, ${JSON.stringify(r.full_name)})' title="מחיקה"><i class="fa fa-trash"></i></button>
    </td>
  </tr>`
});

function openCreate() {
  document.getElementById('custForm').reset();
  document.getElementById('custId').value = '';
  document.getElementById('modalTitle').textContent = 'לקוח חדש';
  openModal('custModal');
}

async function editCust(id) {
  const { data } = await api('customers.php?action=get&id=' + id);
  const f = document.getElementById('custForm');
  f.reset();
  f.id.value = data.id; f.full_name.value = data.full_name;
  f.id_number.value = data.id_number || ''; f.phone.value = data.phone;
  f.email.value = data.email || ''; f.address.value = data.address || '';
  f.city.value = data.city || ''; f.notes.value = data.notes || '';
  document.getElementById('modalTitle').textContent = 'עריכת לקוח';
  openModal('custModal');
}

async function saveCust() {
  const body = serializeForm('custForm');
  if (!body.full_name || !body.phone) return showToast('שם וטלפון הם שדות חובה', 'warning');
  const action = body.id ? 'update' : 'create';
  const res = await api('customers.php?action=' + action, { method: 'POST', body });
  closeModal('custModal');
  showToast(res.message);
  dt.reload();
}

function delCust(id, name) {
  confirmDelete(`למחוק את הלקוח "${name}"?`, async () => {
    const res = await api('customers.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}
</script>
