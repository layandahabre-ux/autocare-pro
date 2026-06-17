<?php
// pages/print_invoice.php — חשבונית להדפסה
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);

$inv = Database::fetchOne(
  "SELECT i.*, c.full_name AS customer_name, c.phone, c.address, c.city, c.id_number, j.job_number
   FROM tbl_invoice i
   JOIN tbl_customer c ON c.id = i.customer_id
   LEFT JOIN tbl_job j ON j.id = i.job_id
   WHERE i.id = ?", [$id]
);
if (!$inv) {
  http_response_code(404);
  die('<div style="font-family:sans-serif;direction:rtl;text-align:center;margin-top:80px"><h1>404</h1><p>החשבונית לא נמצאה.</p></div>');
}

$settings = Database::fetchOne("SELECT * FROM tbl_settings WHERE id = 1") ?: [];

// שורות (אם משויך כרטיס עבודה)
$parts = [];
$laborHours = 0;
$laborRate = 0;
if ($inv['job_id']) {
  $parts = Database::fetchAll(
    "SELECT p.part_name, jp.quantity, jp.unit_price, (jp.quantity * jp.unit_price) AS line_total
     FROM tbl_job_part jp JOIN tbl_part p ON p.id = jp.part_id
     WHERE jp.job_id = ?", [$inv['job_id']]
  );
  $job = Database::fetchOne("SELECT labor_hours, labor_rate FROM tbl_job WHERE id = ?", [$inv['job_id']]);
  $laborHours = $job['labor_hours'] ?? 0;
  $laborRate  = $job['labor_rate'] ?? 0;
}

$invStatusLabels = ['open' => 'פתוחה', 'partial' => 'תשלום חלקי', 'paid' => 'שולמה', 'cancelled' => 'בוטלה'];
function money($n) { return '₪ ' . number_format((float)$n, 2); }
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>חשבונית <?= htmlspecialchars($inv['invoice_number']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: "Heebo", sans-serif; color: #1e293b; background: #f1f4f8; padding: 30px; }
  .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 48px; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,.08); }
  .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #f97316; padding-bottom: 22px; margin-bottom: 28px; }
  .brand h1 { font-size: 26px; font-weight: 800; }
  .brand h1 span { color: #f97316; }
  .brand p { color: #64748b; font-size: 13px; margin-top: 4px; }
  .doc-meta { text-align: left; }
  .doc-meta .title { font-size: 22px; font-weight: 800; color: #f97316; }
  .doc-meta .num { font-size: 15px; font-weight: 600; margin-top: 4px; }
  .doc-meta .date { color: #64748b; font-size: 13px; margin-top: 2px; }
  .parties { display: flex; gap: 40px; margin-bottom: 28px; }
  .party { flex: 1; }
  .party .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; font-weight: 700; margin-bottom: 6px; }
  .party .name { font-size: 16px; font-weight: 700; }
  .party .line { font-size: 13.5px; color: #475569; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  th { background: #16202e; color: #fff; padding: 11px 14px; text-align: right; font-size: 13px; font-weight: 600; }
  th:last-child, td:last-child { text-align: left; }
  td { padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  .totals { margin-inline-start: auto; width: 320px; }
  .totals .row { display: flex; justify-content: space-between; padding: 7px 0; font-size: 14.5px; }
  .totals .row.grand { border-top: 2px solid #16202e; margin-top: 6px; padding-top: 12px; font-size: 19px; font-weight: 800; color: #f97316; }
  .status-tag { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
  .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 12.5px; }
  .actions { max-width: 820px; margin: 0 auto 18px; display: flex; gap: 10px; justify-content: flex-end; }
  .btn { padding: 9px 18px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; font-family: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 7px; }
  .btn-print { background: #f97316; color: #fff; }
  .btn-back { background: #e2e8f0; color: #1e293b; }
  @media print {
    body { background: #fff; padding: 0; }
    .sheet { box-shadow: none; border-radius: 0; max-width: 100%; padding: 20px; }
    .actions { display: none; }
  }
</style>
</head>
<body>

<div class="actions">
  <a href="<?= APP_URL ?>/pages/invoices.php" class="btn btn-back">← חזרה</a>
  <button class="btn btn-print" onclick="window.print()">🖨 הדפסה</button>
</div>

<div class="sheet">
  <div class="head">
    <div class="brand">
      <h1>AUTO<span>CARE</span></h1>
      <p><?= htmlspecialchars($settings['garage_name'] ?? 'AutoCare Pro') ?></p>
      <?php if (!empty($settings['garage_address'])): ?><p><?= htmlspecialchars($settings['garage_address']) ?></p><?php endif; ?>
      <?php if (!empty($settings['garage_phone'])): ?><p>טלפון: <?= htmlspecialchars($settings['garage_phone']) ?></p><?php endif; ?>
    </div>
    <div class="doc-meta">
      <div class="title">חשבונית מס</div>
      <div class="num"><?= htmlspecialchars($inv['invoice_number']) ?></div>
      <div class="date">תאריך: <?= date('d/m/Y', strtotime($inv['issue_date'])) ?></div>
      <?php if ($inv['job_number']): ?><div class="date">כרטיס עבודה: <?= htmlspecialchars($inv['job_number']) ?></div><?php endif; ?>
    </div>
  </div>

  <div class="parties">
    <div class="party">
      <div class="label">לכבוד</div>
      <div class="name"><?= htmlspecialchars($inv['customer_name']) ?></div>
      <?php if ($inv['id_number']): ?><div class="line">ת״ז / ח״פ: <?= htmlspecialchars($inv['id_number']) ?></div><?php endif; ?>
      <?php if ($inv['phone']): ?><div class="line">טלפון: <?= htmlspecialchars($inv['phone']) ?></div><?php endif; ?>
      <?php if ($inv['address'] || $inv['city']): ?><div class="line"><?= htmlspecialchars(trim(($inv['address'] ?? '') . ' ' . ($inv['city'] ?? ''))) ?></div><?php endif; ?>
    </div>
    <div class="party" style="text-align:left">
      <div class="label">סטטוס תשלום</div>
      <?php
        $stColors = ['open' => '#0d6efd', 'partial' => '#fd7e14', 'paid' => '#198754', 'cancelled' => '#dc3545'];
        $c = $stColors[$inv['status']] ?? '#6c757d';
      ?>
      <span class="status-tag" style="background:<?= $c ?>22;color:<?= $c ?>">
        <?= $invStatusLabels[$inv['status']] ?? $inv['status'] ?>
      </span>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>תיאור</th>
        <th>כמות</th>
        <th>מחיר יחידה</th>
        <th>סה״כ</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($laborHours > 0): ?>
        <tr>
          <td>עבודה (שעות מקצועיות)</td>
          <td><?= rtrim(rtrim(number_format($laborHours, 2), '0'), '.') ?></td>
          <td><?= money($laborRate) ?></td>
          <td><?= money($laborHours * $laborRate) ?></td>
        </tr>
      <?php endif; ?>
      <?php foreach ($parts as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['part_name']) ?></td>
          <td><?= (int)$p['quantity'] ?></td>
          <td><?= money($p['unit_price']) ?></td>
          <td><?= money($p['line_total']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($laborHours == 0 && empty($parts)): ?>
        <tr>
          <td>שירותי מוסך</td>
          <td>1</td>
          <td><?= money($inv['subtotal']) ?></td>
          <td><?= money($inv['subtotal']) ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="totals">
    <div class="row"><span>סכום ביניים:</span><span><?= money($inv['subtotal']) ?></span></div>
    <?php if ((float)$inv['discount'] > 0): ?>
      <div class="row"><span>הנחה:</span><span>- <?= money($inv['discount']) ?></span></div>
    <?php endif; ?>
    <div class="row"><span>מע״מ (<?= rtrim(rtrim(number_format($inv['vat_rate'], 2), '0'), '.') ?>%):</span><span><?= money($inv['vat_amount']) ?></span></div>
    <div class="row grand"><span>סה״כ לתשלום:</span><span><?= money($inv['total']) ?></span></div>
    <?php if ((float)$inv['paid_amount'] > 0): ?>
      <div class="row" style="color:#16a34a"><span>שולם:</span><span><?= money($inv['paid_amount']) ?></span></div>
      <?php if ((float)$inv['total'] - (float)$inv['paid_amount'] > 0): ?>
        <div class="row" style="color:#dc2626"><span>יתרה:</span><span><?= money($inv['total'] - $inv['paid_amount']) ?></span></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="footer">
    <?= htmlspecialchars($settings['garage_name'] ?? 'AutoCare Pro') ?>
    <?php if (!empty($settings['garage_email'])): ?> · <?= htmlspecialchars($settings['garage_email']) ?><?php endif; ?>
    <br/>תודה שבחרתם בנו!
  </div>
</div>

</body>
</html>
