<?php
// config/database.php — חיבור למסד הנתונים והגדרות כלליות

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // ב-XAMPP ברירת המחדל ריקה
define('DB_NAME', 'garage_db');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME',    'AutoCare Pro');
define('APP_TITLE',   'מערכת ניהול מוסך וטיפולי רכב');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/garage-app');

// שיעור מע"מ במדינת ישראל (ניתן לעדכון בעמוד ההגדרות)
define('DEFAULT_VAT', 18.0);

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['error' => 'החיבור למסד הנתונים נכשל: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
