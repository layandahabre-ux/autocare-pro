<?php
// auth/reset.php — איפוס סיסמה באמצעות token
require_once __DIR__ . '/../config/auth.php';
startSession();

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$error = '';
$done  = false;

$user = $token
    ? Database::fetchOne(
        "SELECT id FROM tbl_users WHERE reset_token = ? AND reset_expires > NOW()",
        [$token]
      )
    : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) {
        $error = 'הקישור פג תוקף או אינו תקין. בקשו קישור חדש.';
    } else {
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';
        if (strlen($pass) < 8) {
            $error = 'הסיסמה חייבת להכיל לפחות 8 תווים.';
        } elseif ($pass !== $pass2) {
            $error = 'הסיסמאות אינן תואמות.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            Database::query(
                "UPDATE tbl_users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?",
                [$hash, $user['id']]
            );
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>איפוס סיסמה — <?= APP_NAME ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.rtl.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet"/>
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <i class="fa fa-lock-open"></i>
      <h1>סיסמה חדשה</h1>
    </div>

    <?php if ($done): ?>
      <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="fa fa-circle-check"></i><span>הסיסמה עודכנה בהצלחה!</span>
      </div>
      <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary w-100">למסך הכניסה</a>
    <?php elseif (!$user): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="fa fa-circle-exclamation"></i><span>הקישור פג תוקף או אינו תקין.</span>
      </div>
      <a href="<?= APP_URL ?>/auth/forgot.php" class="btn btn-primary w-100">בקשו קישור חדש</a>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
          <i class="fa fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"/>
        <div class="mb-3">
          <label class="form-label">סיסמה חדשה</label>
          <input type="password" name="password" class="form-control" required minlength="8"/>
        </div>
        <div class="mb-3">
          <label class="form-label">אימות סיסמה</label>
          <input type="password" name="password2" class="form-control" required minlength="8"/>
        </div>
        <button type="submit" class="btn btn-primary w-100">עדכן סיסמה</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
