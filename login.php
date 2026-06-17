<?php
// auth/login.php — מסך כניסה למערכת
require_once __DIR__ . '/../config/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'יש למלא שם משתמש וסיסמה.';
    } else {
        $user = Database::fetchOne(
            "SELECT * FROM tbl_users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$login, $login]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = [
                'id'        => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
            ];
            Database::query("UPDATE tbl_users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        } else {
            logDeniedAccess($login);
            $error = 'שם משתמש או סיסמה שגויים.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>כניסה — <?= APP_NAME ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.rtl.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet"/>
</head>
<body class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <i class="fa fa-car-wrench"></i>
      <h1>AUTO<span>CARE</span></h1>
      <p><?= APP_TITLE ?></p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2">
        <i class="fa fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">שם משתמש או אימייל</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa fa-user"></i></span>
          <input type="text" name="login" class="form-control" required autofocus value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"/>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">סיסמה</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa fa-lock"></i></span>
          <input type="password" name="password" id="pwd" class="form-control" required/>
          <span class="input-group-text" style="cursor:pointer" onclick="togglePwd()"><i class="fa fa-eye" id="pwdIcon"></i></span>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fa fa-right-to-bracket me-2"></i>כניסה
      </button>
    </form>

    <div class="auth-links">
      <a href="<?= APP_URL ?>/auth/forgot.php">שכחתי סיסמה</a>
      <span>·</span>
      <a href="<?= APP_URL ?>/auth/register.php">יצירת משתמש חדש</a>
    </div>

    <div class="auth-demo">
      <strong>גישת דמו:</strong> admin / Aa123456!
    </div>
  </div>

<script>
function togglePwd(){
  const p=document.getElementById('pwd'), i=document.getElementById('pwdIcon');
  if(p.type==='password'){p.type='text';i.className='fa fa-eye-slash';}
  else{p.type='password';i.className='fa fa-eye';}
}
</script>
</body>
</html>
