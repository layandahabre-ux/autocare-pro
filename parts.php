<?php
// pages/parts.php — מלאי חלקי חילוף
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('מלאי חלקים', 'parts');
?>

<div class="toolbar">
  <div class="search-box">
    <i class="fa fa-magnifying-glass"></i>
    <input type="text" id="search" placeholder="חיפוש לפי שם או מק״ט…"/>
  </div>
  <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
    <input class="form-check-input" type="checkbox" id="lowFilter" onchange="dt.setFilter('low', this.checked ? '1' : '')"/>
    <label class="form-check-label" for="lowFilter">מלאי נמוך בלבד</label>
  </div>
  <button class="btn btn-primary ms-auto" onclick="openCreate()">
    <i class="fa fa-plus me-2"></i>חלק חדש
  </button>
</div>

<div class="card-box">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th onclick="dt.sort('part_number')">מק״ט <i class="fa fa-sort"></i></th>
          <th onclick="dt.sort('part_name')">שם החלק <i class="fa fa-sort"></i></th>
          <th class="no-sort">קטגוריה</th>
          <th class="no-sort">מחיר עלות</th>
          <th onclick="dt.sort('sell_price')">מחיר מכירה <i class="fa fa-sort"></i></th>
          <th onclick="dt.sort('stock_qty')">מלאי <i class="fa fa-sort"></i></th>
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

<div class="modal fade" id="partModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">חלק חדש</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="partForm" onsubmit="return false">
        <div class="modal-body">
          <input type="hidden" name="id" id="partId"/>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">מק״ט <span class="text-danger">*</span></label>
              <input type="text" name="part_number" class="form-control" required/>
            </div>
            <div class="col-md-6">
              <label class="form-label">קטגוריה</label>
              <select name="category_id" id="catSelect" class="form-select"></select>
            </div>
            <div class="col-12">
              <label class="form-label">שם החלק <span class="text-danger">*</span></label>
              <input type="text" name="part_name" class="form-control" required/>
            </div>
            <div class="col-md-6">
              <label class="form-label">מחיר עלות (₪)</label>
              <input type="number" name="cost_price" class="form-control" min="0" step="0.01"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">מחיר מכירה (₪)</label>
              <input type="number" name="sell_price" class="form-control" min="0" step="0.01"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">כמות במלאי</label>
              <input type="number" name="stock_qty" class="form-control" min="0"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">מלאי מינימום להתראה</label>
              <input type="number" name="min_stock" class="form-control" min="0" value="2"/>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">ביטול</button>
          <button type="button" class="btn btn-primary" onclick="savePart()">
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
  id: 'part', endpoint: 'parts.php', searchId: 'search',
  tbodyId: 'tbody', pagerId: 'pager', infoId: 'info', defaultSort: 'id',
  rowTpl: r => {
    const low = +r.stock_qty <= +r.min_stock;
    const stockBadge = low
      ? `<span class="status-badge" style="background:#fef2f2;color:#dc2626">${r.stock_qty} <i class="fa fa-triangle-exclamation"></i></span>`
      : `<span class="badge bg-light text-dark">${r.stock_qty}</span>`;
    return `<tr>
      <td><code>${esc(r.part_number)}</code></td>
      <td><strong>${esc(r.part_name)}</strong></td>
      <td>${esc(r.category_name || '—')}</td>
      <td>${fmt.currency(r.cost_price)}</td>
      <td>${fmt.currency(r.sell_price)}</td>
      <td>${stockBadge}</td>
      <td>
        <button class="btn-icon" onclick='editPart(${r.id})' title="עריכה"><i class="fa fa-pen"></i></button>
        <button class="btn-icon danger" onclick='delPart(${r.id}, ${JSON.stringify(r.part_name)})' title="מחיקה"><i class="fa fa-trash"></i></button>
      </td>
    </tr>`;
  }
});

let cats = [];
async function loadCats() {
  const { data } = await api('parts.php?action=categories');
  cats = data;
  document.getElementById('catSelect').innerHTML =
    '<option value="">— ללא —</option>' + cats.map(c => `<option value="${c.id}">${esc(c.category_name)}</option>`).join('');
}
loadCats();

function openCreate() {
  document.getElementById('partForm').reset();
  document.getElementById('partId').value = '';
  document.getElementById('modalTitle').textContent = 'חלק חדש';
  openModal('partModal');
}

async function editPart(id) {
  const { data } = await api('parts.php?action=get&id=' + id);
  const f = document.getElementById('partForm');
  f.reset();
  f.id.value = data.id; f.part_number.value = data.part_number;
  f.category_id.value = data.category_id || ''; f.part_name.value = data.part_name;
  f.cost_price.value = data.cost_price; f.sell_price.value = data.sell_price;
  f.stock_qty.value = data.stock_qty; f.min_stock.value = data.min_stock;
  document.getElementById('modalTitle').textContent = 'עריכת חלק';
  openModal('partModal');
}

async function savePart() {
  const body = serializeForm('partForm');
  if (!body.part_number || !body.part_name) return showToast('מק״ט ושם החלק הם שדות חובה', 'warning');
  const action = body.id ? 'update' : 'create';
  const res = await api('parts.php?action=' + action, { method: 'POST', body });
  closeModal('partModal');
  showToast(res.message);
  dt.reload();
}

function delPart(id, name) {
  confirmDelete(`למחוק את החלק "${name}"?`, async () => {
    const res = await api('parts.php?action=delete', { method: 'POST', body: { id } });
    showToast(res.message);
    dt.reload();
  });
}
</script>
