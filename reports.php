<?php
// pages/reports.php — דוחות וגרפים
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();
renderLayout('דוחות', 'reports');
?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-chart-line"></i>הכנסות לפי חודש</div>
      </div>
      <div class="card-box-body"><canvas id="revChart" height="130"></canvas></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-screwdriver-wrench"></i>התפלגות כרטיסי עבודה</div>
      </div>
      <div class="card-box-body"><canvas id="statusChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-user-gear"></i>מכונאים מובילים (לפי כרטיסים)</div>
      </div>
      <div class="card-box-body"><canvas id="mechChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-box">
      <div class="card-box-header">
        <div class="card-box-title"><i class="fa fa-boxes-stacked"></i>חלקים נמכרים ביותר</div>
      </div>
      <div class="card-box-body"><canvas id="partsChart" height="200"></canvas></div>
    </div>
  </div>
</div>

<?php endLayout(); ?>

<script>
async function loadReports() {
  const [rev, st, mech, parts] = await Promise.all([
    api('reports.php?action=revenue_by_month'),
    api('reports.php?action=jobs_by_status'),
    api('reports.php?action=top_mechanics'),
    api('reports.php?action=top_parts'),
  ]);

  new Chart(document.getElementById('revChart'), {
    type: 'bar',
    data: {
      labels: rev.data.map(r => r.ym),
      datasets: [{ label: 'הכנסות (₪)', data: rev.data.map(r => +r.revenue), backgroundColor: '#f97316', borderRadius: 6 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: st.data.map(s => s.status_name),
      datasets: [{ data: st.data.map(s => +s.cnt), backgroundColor: st.data.map(s => s.color_hex), borderWidth: 2 }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { padding: 12, font: { size: 12 } } } } }
  });

  new Chart(document.getElementById('mechChart'), {
    type: 'bar',
    data: {
      labels: mech.data.map(m => m.full_name),
      datasets: [{ label: 'כרטיסים', data: mech.data.map(m => +m.jobs), backgroundColor: '#2563eb', borderRadius: 6 }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('partsChart'), {
    type: 'bar',
    data: {
      labels: parts.data.map(p => p.part_name),
      datasets: [{ label: 'יחידות', data: parts.data.map(p => +p.qty), backgroundColor: '#16a34a', borderRadius: 6 }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }
  });
}
loadReports();
</script>
