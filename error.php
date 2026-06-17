<?php
// pages/error.php — עמוד שגיאה מותאם (404 / 500)
require_once __DIR__ . '/../config/database.php';
$code = (int)($_GET['code'] ?? 404);
$messages = [
  404 => ['העמוד לא נמצא', 'העמוד שחיפשתם אינו קיים או הוסר.'],
  403 => ['גישה נדחתה', 'אין לכם הרשאה לצפות בעמוד זה.'],
  500 => ['שגיאת שרת', 'אירעה תקלה בשרת. נסו שוב מאוחר יותר.'],
];
[$title, $desc] = $messages[$code] ?? $messages[404];
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $code ?> — <?= htmlspecialchars($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;600;800&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: "Heebo", sans-serif; min-height: 100vh; display: grid; place-items: center;
         background: linear-gradient(135deg, #16202e, #1d2a3a); color: #fff; text-align: center; padding: 20px; }
  .box { max-width: 460px; }
  .code { font-size: 110px; font-weight: 800; color: #f97316; line-height: 1; }
  .icon { font-size: 46px; color: #f97316; margin-bottom: 18px; }
  h1 { font-size: 28px; margin-bottom: 10px; }
  p { color: #aab4c2; font-size: 16px; margin-bottom: 28px; }
  .btn { display: inline-flex; align-items: center; gap: 8px; background: #f97316; color: #fff;
         padding: 12px 26px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: .15s; }
  .btn:hover { background: #ea580c; }
</style>
</head>
<body>
  <div class="box">
    <div class="icon"><i class="fa fa-car-burst"></i></div>
    <div class="code"><?= $code ?></div>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($desc) ?></p>
    <a href="<?= APP_URL ?>/dashboard.php" class="btn">
      <i class="fa fa-house"></i>חזרה ללוח הבקרה
    </a>
  </div>
</body>
</html>
