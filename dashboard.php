<?php
// dashboard.php — לוח בקרה ראשי
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();
renderLayout('לוח בקרה', 'dashboard');
?>

<div class="kpi-grid" id="kpiGrid">
  <!-- ייטען דרך JS -->
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-chart-line"></i>הכנסות — 6 חודשים אחרונים</div>
      </div>
      <div class="card-box-body">
        <canvas id="revenueChart" height="110"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-screwdriver-wrench"></i>כרטיסי עבודה לפי סטטוס</div>
      </div>
      <div class="card-box-body">
        <canvas id="statusChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-calendar-check"></i>תורים קרובים</div>
        <a href="<?= APP_URL ?>/pages/appointments.php" class="btn btn-sm btn-outline-primary">לכל היומן</a>
      </div>
      <div class="card-box-body p-0">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="no-sort">מועד</th>
                <th class="no-sort">לקוח</th>
                <th class="no-sort">רכב</th>
                <th class="no-sort">סיבה</th>
              </tr>
            </thead>
            <tbody id="upcomingBody">
              <tr><td colspan="4" class="empty-row"><i class="fa fa-spinner fa-spin"></i><div>טוען…</div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-user-gear"></i>מכונאים מובילים</div>
      </div>
      <div class="card-box-body p-0">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th class="no-sort">מכונאי</th>
                <th class="no-sort">כרטיסים</th>
                <th class="no-sort">שעות</th>
              </tr>
            </thead>
            <tbody id="topMechBody">
              <tr><td colspan="3" class="empty-row"><i class="fa fa-spinner fa-spin"></i><div>טוען…</div></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
const statusColors = {scheduled:'#0d6efd', arrived:'#fd7e14', done:'#198754', cancelled:'#dc3545', no_show:'#6c757d'};
const statusLabels = {scheduled:'נקבע', arrived:'הגיע', done:'הושלם', cancelled:'בוטל', no_show:'לא הופיע'};

async function loadDashboard() {
  // KPIs
  const { data: k } = await api('reports.php?action=kpis');
  document.getElementById('kpiGrid').innerHTML = `
    ${kpiCard('fa-screwdriver-wrench', k.open_jobs, 'כרטיסי עבודה פתוחים', '#f97316', '#fff2e8')}
    ${kpiCard('fa-calendar-day', k.today_appts, 'תורים היום', '#2563eb', '#eff6ff')}
    ${kpiCard('fa-sack-dollar', fmt.currency(k.month_revenue), 'הכנסות החודש', '#16a34a', '#f0fdf4')}
    ${kpiCard('fa-hourglass-half', fmt.currency(k.open_balance), 'יתרות לגבייה', '#d97706', '#fffbeb')}
    ${kpiCard('fa-triangle-exclamation', k.low_stock, 'חלקים במלאי נמוך', '#dc2626', '#fef2f2')}
    ${kpiCard('fa-users', k.customers, 'לקוחות רשומים', '#7c3aed', '#f5f3ff')}
  `;

  // Revenue chart
  const { data: rev } = await api('reports.php?action=revenue_by_month');
  new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
      labels: rev.map(r => r.ym),
      datasets: [{
        label: 'הכנסות (₪)',
        data: rev.map(r => +r.revenue),
        borderColor: '#f97316',
        backgroundColor: 'rgba(249,115,22,.12)',
        fill: true, tension: .35, borderWidth: 2.5,
        pointBackgroundColor: '#f97316', pointRadius: 4,
      }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });

  // Status doughnut
  const { data: st } = await api('reports.php?action=jobs_by_status');
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: st.map(s => s.status_name),
      datasets: [{ data: st.map(s => +s.cnt), backgroundColor: st.map(s => s.color_hex), borderWidth: 2 }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 12 } } } } }
  });

  // Upcoming appointments
  const { data: up } = await api('appointments.php?action=upcoming');
  document.getElementById('upcomingBody').innerHTML = up.length
    ? up.map(a => `<tr>
        <td><strong>${fmt.datetime(a.scheduled_at)}</strong></td>
        <td>${esc(a.customer_name)}</td>
        <td><span class="badge bg-light text-dark">${esc(a.plate_number)}</span></td>
        <td>${esc(a.reason || '—')}</td>
      </tr>`).join('')
    : `<tr><td colspan="4" class="empty-row"><i class="fa fa-calendar-xmark"></i><div>אין תורים קרובים</div></td></tr>`;

  // Top mechanics
  const { data: tm } = await api('reports.php?action=top_mechanics');
  document.getElementById('topMechBody').innerHTML = tm.length
    ? tm.map(m => `<tr>
        <td><strong>${esc(m.full_name)}</strong></td>
        <td>${fmt.number(m.jobs)}</td>
        <td>${fmt.number(m.hours)}</td>
      </tr>`).join('')
    : `<tr><td colspan="3" class="empty-row"><i class="fa fa-inbox"></i><div>אין נתונים</div></td></tr>`;
}

function kpiCard(icon, value, label, color, soft) {
  return `<div class="kpi-card" style="--kpi-color:${color};--kpi-soft:${soft}">
    <div class="kpi-icon"><i class="fa ${icon}"></i></div>
    <div class="kpi-value">${value}</div>
    <div class="kpi-label">${label}</div>
  </div>`;
}

loadDashboard();
</script>
