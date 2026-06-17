<?php
// auth/register.php — הרשמת משתמש חדש
require_once __DIR__ . '/../config/auth.php';
startSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if ($fullName === '' || $username === '' || $email === '' || $pass === '') {
        $error = 'יש למלא את כל שדות החובה.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'כתובת אימייל אינה תקינה.';
    } elseif (strlen($pass) < 8) {
        $error = 'הסיסמה חייבת להכיל לפחות 8 תווים.';
    } elseif ($pass !== $pass2) {
        $error = 'הסיסמאות אינן תואמות.';
    } elseif (Database::fetchOne("SELECT id FROM tbl_users WHERE username = ? OR email = ?", [$username, $email])) {
        $error = 'שם המשתמש או האימייל כבר קיימים במערכת.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        Database::query(
            "INSERT INTO tbl_users (username, full_name, email, phone, password_hash, role)
             VALUES (?, ?, ?, ?, ?, 'receptionist')",
            [$username, $fullName, $email, $phone, $hash]
        );
        $success = 'המשתמש נוצר בהצלחה! ניתן להתחבר כעת.';
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>הרשמה — <?= APP_NAME ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.rtl.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet"/>
</head>
<body class="auth-page">
  <div class="auth-card" style="max-width:480px">
    <div class="auth-brand">
      <i class="fa fa-user-plus"></i>
      <h1>הרשמה</h1>
      <p>יצירת משתמש חדש במערכת</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="fa fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success d-flex align-items-center gap-2">
        <i class="fa fa-circle-check"></i><span><?= htmlspecialchars($success) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">שם מלא <span class="text-danger">*</span></label>
        <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"/>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">שם משתמש <span class="text-danger">*</span></label>
          <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">טלפון</label>
          <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"/>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">אימייל <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
      </div>
      <div class="mb-3">
        <label class="form-label">סיסמה <span class="text-danger">*</span></label>
        <input type="password" name="password" id="pwd" class="form-control" required oninput="checkStrength()"/>
        <div class="pwd-meter mt-2"><div id="pwdBar"></div></div>
        <small id="pwdText" class="text-muted">לפחות 8 תווים</small>
      </div>
      <div class="mb-3">
        <label class="form-label">אימות סיסמה <span class="text-danger">*</span></label>
        <input type="password" name="password2" class="form-control" required/>
      </div>
      <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fa fa-user-plus me-2"></i>צור משתמש
      </button>
    </form>

    <div class="auth-links">
      <a href="<?= APP_URL ?>/auth/login.php">חזרה לכניסה</a>
    </div>
  </div>

<script>
function checkStrength(){
  const v=document.getElementById('pwd').value;
  let s=0;
  if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++;
  const bar=document.getElementById('pwdBar'), txt=document.getElementById('pwdText');
  const w=['0%','25%','50%','75%','100%'][s];
  const c=['#dc3545','#dc3545','#fd7e14','#ffc107','#198754'][s];
  const t=['','חלשה מאוד','חלשה','בינונית','חזקה'][s];
  bar.style.width=w; bar.style.background=c; txt.textContent=t||'לפחות 8 תווים';
}
</script>
</body>
</html>
