<?php
// auth/forgot.php — בקשת איפוס סיסמה
require_once __DIR__ . '/../config/auth.php';
startSession();

$message = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $user  = Database::fetchOne("SELECT id FROM tbl_users WHERE email = ? AND is_active = 1", [$email]);

    if ($user) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // שעה אחת
        Database::query(
            "UPDATE tbl_users SET reset_token = ?, reset_expires = ? WHERE id = ?",
            [$token, $expires, $user['id']]
        );
        // בסביבת ייצור היה נשלח אימייל. כאן מוצג הקישור ישירות לצורך הדגמה.
        $resetLink = APP_URL . '/auth/reset.php?token=' . $token;
    }
    // הודעה אחידה כדי לא לחשוף אילו אימיילים קיימים
    $message = 'אם הכתובת קיימת במערכת, נשלח אליה קישור לאיפוס הסיסמה.';
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>שכחתי סיסמה — <?= APP_NAME ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.rtl.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet"/>
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <i class="fa fa-key"></i>
      <h1>איפוס סיסמה</h1>
      <p>הזינו את האימייל שלכם</p>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-info d-flex align-items-center gap-2">
        <i class="fa fa-circle-info"></i><span><?= htmlspecialchars($message) ?></span>
      </div>
      <?php if ($resetLink): ?>
        <div class="alert alert-warning small">
          <strong>מצב הדגמה:</strong> בסביבת ייצור הקישור נשלח באימייל. לצורך בדיקה:
          <a href="<?= htmlspecialchars($resetLink) ?>">לחצו כאן לאיפוס</a>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">אימייל</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa fa-envelope"></i></span>
          <input type="email" name="email" class="form-control" required autofocus/>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fa fa-paper-plane me-2"></i>שלח קישור איפוס
      </button>
    </form>

    <div class="auth-links">
      <a href="<?= APP_URL ?>/auth/login.php">חזרה לכניסה</a>
    </div>
  </div>
</body>
</html>
