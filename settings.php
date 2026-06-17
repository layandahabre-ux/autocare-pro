<?php
// pages/settings.php — הגדרות מערכת (אדמין בלבד)
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireRole('admin');
renderLayout('הגדרות', 'settings');
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-gear"></i>הגדרות המוסך</div>
      </div>
      <div class="card-box-body">
        <form id="settingsForm" onsubmit="return false">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">שם המוסך</label>
              <input type="text" name="garage_name" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">טלפון</label>
              <input type="tel" name="garage_phone" class="form-control"/>
            </div>
            <div class="col-12">
              <label class="form-label">כתובת</label>
              <input type="text" name="garage_address" class="form-control"/>
            </div>
            <div class="col-md-6">
              <label class="form-label">אימייל</label>
              <input type="email" name="garage_email" class="form-control"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">שיעור מע״מ ברירת מחדל (%)</label>
              <input type="number" name="default_vat" class="form-control" min="0" step="0.1"/>
            </div>
            <div class="col-md-3">
              <label class="form-label">תעריף שעה ברירת מחדל (₪)</label>
              <input type="number" name="default_rate" class="form-control" min="0" step="0.01"/>
            </div>
          </div>
          <div class="mt-4 text-start">
            <button type="button" class="btn btn-primary" onclick="saveSettings()">
              <i class="fa fa-floppy-disk me-2"></i>שמירת הגדרות
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-circle-info"></i>אודות המערכת</div>
      </div>
      <div class="card-box-body">
        <p class="mb-1"><strong>AutoCare Pro</strong> — מערכת ניהול מוסך וטיפולי רכב</p>
        <p class="text-muted mb-0">גרסה <?= APP_VERSION ?> · נבנתה עבור קורס פרויקט בטכנולוגיות מידע</p>
      </div>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
async function loadSettings() {
  const { data } = await api('settings.php?action=get');
  const f = document.getElementById('settingsForm');
  f.garage_name.value = data.garage_name || '';
  f.garage_phone.value = data.garage_phone || '';
  f.garage_address.value = data.garage_address || '';
  f.garage_email.value = data.garage_email || '';
  f.default_vat.value = data.default_vat;
  f.default_rate.value = data.default_rate;
}
loadSettings();

async function saveSettings() {
  const body = serializeForm('settingsForm');
  const res = await api('settings.php?action=update', { method: 'POST', body });
  showToast(res.message);
}
</script>
