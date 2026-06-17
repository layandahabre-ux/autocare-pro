<?php
// includes/layout.php — מבנה משותף: סרגל צד + סרגל עליון

function renderLayout(string $pageTitle, string $activeMenu = ''): void
{
    $user     = currentUser();
    $role     = $user['role'] ?? 'receptionist';
    $fullName = htmlspecialchars($user['full_name'] ?? '');
    $initial  = mb_strtoupper(mb_substr($fullName, 0, 1));

    // roles: admin / mechanic / receptionist
    $menus = [
        ['id' => 'dashboard',    'icon' => 'fa-gauge-high',     'label' => 'לוח בקרה',      'url' => '/dashboard.php'],
        ['id' => 'appointments', 'icon' => 'fa-calendar-check', 'label' => 'יומן תורים',    'url' => '/pages/appointments.php'],
        ['id' => 'jobs',         'icon' => 'fa-screwdriver-wrench', 'label' => 'כרטיסי עבודה', 'url' => '/pages/jobs.php'],
        ['id' => 'vehicles',     'icon' => 'fa-car',            'label' => 'רכבים',         'url' => '/pages/vehicles.php'],
        ['id' => 'customers',    'icon' => 'fa-users',          'label' => 'לקוחות',        'url' => '/pages/customers.php'],
        ['id' => 'mechanics',    'icon' => 'fa-user-gear',      'label' => 'מכונאים',       'url' => '/pages/mechanics.php'],
        ['id' => 'parts',        'icon' => 'fa-boxes-stacked',  'label' => 'מלאי חלקים',    'url' => '/pages/parts.php'],
        ['id' => 'invoices',     'icon' => 'fa-file-invoice',   'label' => 'חשבוניות',      'url' => '/pages/invoices.php'],
        ['id' => 'reports',      'icon' => 'fa-chart-line',     'label' => 'דוחות',         'url' => '/pages/reports.php'],
        ['id' => 'settings',     'icon' => 'fa-gear',           'label' => 'הגדרות',        'url' => '/pages/settings.php', 'roles' => ['admin']],
    ];

    $sections = [
        'dashboard'    => 'ראשי',
        'appointments' => 'תפעול',
        'parts'        => 'מלאי',
        'invoices'     => 'כספים',
        'reports'      => 'ניתוח',
        'settings'     => 'מערכת',
    ];

    $roleLabel = match ($role) {
        'admin'        => 'מנהל',
        'mechanic'     => 'מכונאי',
        'receptionist' => 'פקיד קבלה',
        default        => 'משתמש',
    };
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<meta name="app-url" content="<?= APP_URL ?>"/>
<title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.rtl.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet"/>
</head>
<body>

<div class="sidebar-overlay d-xl-none" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name">AUTO<span>CARE</span></div>
    <div class="brand-tagline">Garage Management</div>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($menus as $m):
        if (isset($m['roles']) && !in_array($role, $m['roles'], true)) continue;
        if (isset($sections[$m['id']])): ?>
            <div class="nav-section"><?= $sections[$m['id']] ?></div>
        <?php endif; ?>
        <a href="<?= APP_URL . $m['url'] ?>" class="nav-item<?= $activeMenu === $m['id'] ? ' active' : '' ?>">
            <i class="fa <?= $m['icon'] ?> nav-icon"></i>
            <span><?= $m['label'] ?></span>
        </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= $initial ?></div>
      <div class="user-meta">
        <div class="user-name"><?= $fullName ?></div>
        <div class="user-role"><?= $roleLabel ?></div>
      </div>
      <a href="<?= APP_URL ?>/auth/logout.php" class="btn-logout" title="יציאה">
        <i class="fa fa-right-from-bracket"></i>
      </a>
    </div>
  </div>
</aside>

<div class="main-wrapper">
  <header class="topbar">
    <button class="mobile-toggle d-xl-none" onclick="toggleSidebar()">
      <i class="fa fa-bars"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
    <div class="topbar-actions">
      <span class="badge-date"><i class="fa fa-calendar-day me-1"></i><?= date('d/m/Y') ?></span>
    </div>
  </header>
  <main class="page-body">
<?php
}

function endLayout(): void
{ ?>
  </main>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
<?php } ?>
