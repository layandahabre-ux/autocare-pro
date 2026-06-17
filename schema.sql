-- =====================================================================
--  AutoCare Pro  —  מערכת ניהול מוסך וטיפולי רכב
--  סכמת בסיס נתונים מלאה  +  נתוני דמו (seed)
--  PHP 8.1+  ·  MySQL 8  ·  utf8mb4
--  מרצה: ד"ר איאד סולימאן  ·  החוג למערכות מידע
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS garage_db;
CREATE DATABASE garage_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE garage_db;

-- =====================================================================
--  1. משתמשי המערכת
-- =====================================================================
CREATE TABLE tbl_users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(60)  NOT NULL UNIQUE,
    full_name       VARCHAR(120) NOT NULL,
    email           VARCHAR(160) NOT NULL UNIQUE,
    phone           VARCHAR(30),
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin','mechanic','receptionist') NOT NULL DEFAULT 'receptionist',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    reset_token     VARCHAR(64)  DEFAULT NULL,
    reset_expires   DATETIME     DEFAULT NULL,
    last_login      DATETIME     DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB;

-- לוג ניסיונות התחברות כושלים (אבטחה)
CREATE TABLE tbl_login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ip_address   VARCHAR(45),
    machine_name VARCHAR(120),
    attempt_user VARCHAR(60),
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempt_time (attempted_at)
) ENGINE=InnoDB;

-- =====================================================================
--  2. טבלאות עזר (lookup)
-- =====================================================================

-- יצרני רכב
CREATE TABLE tbl_car_make (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    make_name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- מצב כרטיס עבודה
CREATE TABLE tbl_job_status (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    status_code VARCHAR(20) NOT NULL UNIQUE,
    status_name VARCHAR(40) NOT NULL,
    color_hex   VARCHAR(7)  NOT NULL DEFAULT '#6c757d'
) ENGINE=InnoDB;

-- קטגוריות חלקי חילוף
CREATE TABLE tbl_part_category (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =====================================================================
--  3. ישויות ליבה: לקוחות, רכבים, מכונאים
-- =====================================================================

CREATE TABLE tbl_customer (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120) NOT NULL,
    id_number     VARCHAR(20),                 -- ת"ז / ח.פ
    phone         VARCHAR(30)  NOT NULL,
    email         VARCHAR(160),
    address       VARCHAR(200),
    city          VARCHAR(80),
    notes         TEXT,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cust_name  (full_name),
    INDEX idx_cust_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE tbl_vehicle (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    plate_number    VARCHAR(15)  NOT NULL UNIQUE,   -- מספר רישוי
    make_id         INT,
    model           VARCHAR(60),
    year            SMALLINT,
    color           VARCHAR(30),
    vin             VARCHAR(40),                     -- מספר שלדה
    engine_type     ENUM('petrol','diesel','hybrid','electric') DEFAULT 'petrol',
    current_mileage INT DEFAULT 0,                   -- ק"מ נוכחי
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicle_customer FOREIGN KEY (customer_id) REFERENCES tbl_customer(id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_make     FOREIGN KEY (make_id)     REFERENCES tbl_car_make(id) ON DELETE SET NULL,
    INDEX idx_vehicle_plate (plate_number),
    INDEX idx_vehicle_cust  (customer_id)
) ENGINE=InnoDB;

CREATE TABLE tbl_mechanic (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT DEFAULT NULL,                  -- קישור אופציונלי למשתמש מערכת
    full_name    VARCHAR(120) NOT NULL,
    phone        VARCHAR(30),
    specialty    VARCHAR(80),                        -- התמחות: חשמל, מנוע, פחחות...
    hourly_rate  DECIMAL(8,2) NOT NULL DEFAULT 0,    -- תעריף שעת עבודה
    hire_date    DATE,
    quit_date    DATE DEFAULT NULL,                  -- NULL = פעיל
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mechanic_user FOREIGN KEY (user_id) REFERENCES tbl_users(id) ON DELETE SET NULL,
    INDEX idx_mechanic_active (quit_date)
) ENGINE=InnoDB;

-- =====================================================================
--  4. מלאי חלקי חילוף
-- =====================================================================
CREATE TABLE tbl_part (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    category_id   INT,
    part_number   VARCHAR(40) NOT NULL UNIQUE,       -- מק"ט
    part_name     VARCHAR(120) NOT NULL,
    cost_price    DECIMAL(10,2) NOT NULL DEFAULT 0,  -- מחיר עלות
    sell_price    DECIMAL(10,2) NOT NULL DEFAULT 0,  -- מחיר מכירה
    stock_qty     INT NOT NULL DEFAULT 0,            -- כמות במלאי
    min_stock     INT NOT NULL DEFAULT 2,            -- מלאי מינימום להתראה
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_part_category FOREIGN KEY (category_id) REFERENCES tbl_part_category(id) ON DELETE SET NULL,
    INDEX idx_part_number (part_number),
    INDEX idx_part_stock  (stock_qty)
) ENGINE=InnoDB;

-- =====================================================================
--  5. תורים / פגישות (יומן)
-- =====================================================================
CREATE TABLE tbl_appointment (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id    INT NOT NULL,
    mechanic_id   INT DEFAULT NULL,
    scheduled_at  DATETIME NOT NULL,
    duration_min  INT NOT NULL DEFAULT 60,
    reason        VARCHAR(200),
    status        ENUM('scheduled','arrived','done','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appt_vehicle  FOREIGN KEY (vehicle_id)  REFERENCES tbl_vehicle(id)  ON DELETE CASCADE,
    CONSTRAINT fk_appt_mechanic FOREIGN KEY (mechanic_id) REFERENCES tbl_mechanic(id) ON DELETE SET NULL,
    INDEX idx_appt_time (scheduled_at)
) ENGINE=InnoDB;

-- =====================================================================
--  6. כרטיס עבודה (Job Card) — הישות המרכזית
-- =====================================================================
CREATE TABLE tbl_job (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    job_number      VARCHAR(20) NOT NULL UNIQUE,      -- מספר כרטיס עבודה
    vehicle_id      INT NOT NULL,
    mechanic_id     INT DEFAULT NULL,
    status_id       INT NOT NULL,
    mileage_in      INT,                               -- ק"מ בכניסה
    description     TEXT,                              -- תיאור התקלה / הטיפול המבוקש
    diagnosis       TEXT,                              -- אבחון המכונאי
    labor_hours     DECIMAL(6,2) NOT NULL DEFAULT 0,   -- שעות עבודה
    labor_rate      DECIMAL(8,2) NOT NULL DEFAULT 0,   -- תעריף שעה בכרטיס זה
    opened_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at       DATETIME DEFAULT NULL,
    CONSTRAINT fk_job_vehicle  FOREIGN KEY (vehicle_id)  REFERENCES tbl_vehicle(id)   ON DELETE CASCADE,
    CONSTRAINT fk_job_mechanic FOREIGN KEY (mechanic_id) REFERENCES tbl_mechanic(id)  ON DELETE SET NULL,
    CONSTRAINT fk_job_status   FOREIGN KEY (status_id)   REFERENCES tbl_job_status(id),
    INDEX idx_job_vehicle (vehicle_id),
    INDEX idx_job_status  (status_id),
    INDEX idx_job_opened  (opened_at)
) ENGINE=InnoDB;

-- פריטי חלקים שנוצלו בכרטיס עבודה (קשר רבים-לרבים: job × part)
CREATE TABLE tbl_job_part (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    job_id      INT NOT NULL,
    part_id     INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL DEFAULT 0,      -- מחיר ליחידה בעת השימוש
    CONSTRAINT fk_jobpart_job  FOREIGN KEY (job_id)  REFERENCES tbl_job(id)  ON DELETE CASCADE,
    CONSTRAINT fk_jobpart_part FOREIGN KEY (part_id) REFERENCES tbl_part(id),
    INDEX idx_jobpart_job (job_id)
) ENGINE=InnoDB;

-- =====================================================================
--  7. חשבוניות
-- =====================================================================
CREATE TABLE tbl_invoice (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(20) NOT NULL UNIQUE,
    job_id          INT DEFAULT NULL,
    customer_id     INT NOT NULL,
    issue_date      DATE NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0,   -- לפני מע"מ
    vat_rate        DECIMAL(5,2)  NOT NULL DEFAULT 18,
    vat_amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0,   -- סה"כ לתשלום
    paid_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('open','paid','partial','cancelled') NOT NULL DEFAULT 'open',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoice_job      FOREIGN KEY (job_id)      REFERENCES tbl_job(id)      ON DELETE SET NULL,
    CONSTRAINT fk_invoice_customer FOREIGN KEY (customer_id) REFERENCES tbl_customer(id) ON DELETE CASCADE,
    INDEX idx_invoice_cust   (customer_id),
    INDEX idx_invoice_status (status),
    INDEX idx_invoice_date   (issue_date)
) ENGINE=InnoDB;

-- =====================================================================
--  8. הגדרות מערכת (שורה אחת)
-- =====================================================================
CREATE TABLE tbl_settings (
    id              INT PRIMARY KEY DEFAULT 1,
    garage_name     VARCHAR(120) NOT NULL DEFAULT 'AutoCare Pro',
    garage_phone    VARCHAR(30),
    garage_address  VARCHAR(200),
    garage_email    VARCHAR(160),
    default_vat     DECIMAL(5,2) NOT NULL DEFAULT 18,
    default_rate    DECIMAL(8,2) NOT NULL DEFAULT 180,   -- תעריף שעת עבודה ברירת מחדל
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_one_row CHECK (id = 1)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  נתוני דמו  (SEED)
-- =====================================================================

-- משתמשים. הסיסמה לכל המשתמשים: Aa123456!
-- (hash תקין של password_hash עם PASSWORD_DEFAULT)
INSERT INTO tbl_users (username, full_name, email, phone, password_hash, role) VALUES
('admin',   'הדיל הייב',    'heebhadeel4@gmail.com', '',           '$2b$10$48wV2U3yu8d5SoAvkNNJruBgR7BSddIC9NDNUKe6yBgSpN4eLAP82', 'admin'),
('liyan',   'ליאן דחאברה',  'Layandahabre@gmail.com','0525742332', '$2b$10$48wV2U3yu8d5SoAvkNNJruBgR7BSddIC9NDNUKe6yBgSpN4eLAP82', 'receptionist'),
('mechanic','יוסי לוי',     'yossi@autocare.local',  '0501112233', '$2b$10$48wV2U3yu8d5SoAvkNNJruBgR7BSddIC9NDNUKe6yBgSpN4eLAP82', 'mechanic');

-- יצרני רכב
INSERT INTO tbl_car_make (make_name) VALUES
('טויוטה'),('יונדאי'),('קיה'),('מאזדה'),('סקודה'),('פולקסווגן'),('מיצובישי'),('פורד'),('יגואר'),('טסלה');

-- מצבי כרטיס עבודה
INSERT INTO tbl_job_status (status_code, status_name, color_hex) VALUES
('open',        'פתוח',       '#0d6efd'),
('in_progress', 'בטיפול',     '#fd7e14'),
('waiting_part','ממתין לחלק',  '#6f42c1'),
('done',        'הושלם',      '#198754'),
('delivered',   'נמסר ללקוח',  '#20c997'),
('cancelled',   'בוטל',       '#dc3545');

-- קטגוריות חלקים
INSERT INTO tbl_part_category (category_name) VALUES
('מנוע'),('בלמים'),('חשמל'),('מערכת קירור'),('מתלים'),('שמנים ונוזלים'),('צמיגים'),('כללי');

-- לקוחות
INSERT INTO tbl_customer (full_name, id_number, phone, email, city) VALUES
('דניאל כהן',  '305512348', '0541234567', 'daniel@example.com', 'חיפה'),
('מאיה לוי',   '208891234', '0529876543', 'maya@example.com',   'תל אביב'),
('אחמד זועבי', '301122334', '0507654321', 'ahmad@example.com',  'נצרת'),
('שירה מזרחי', '212233445', '0531239876', NULL,                 'באר שבע'),
('רון אבידן',  '044556677', '0548887766', 'ron@example.com',    'ירושלים');

-- רכבים
INSERT INTO tbl_vehicle (customer_id, plate_number, make_id, model, year, color, engine_type, current_mileage) VALUES
(1, '12-345-67', 1, 'קורולה',   2019, 'לבן',   'hybrid', 84210),
(1, '88-221-55', 2, 'i20',      2021, 'אפור',  'petrol', 31050),
(2, '23-456-78', 5, 'אוקטביה',  2020, 'שחור',  'diesel', 96400),
(3, '77-654-32', 3, 'ספורטאז׳', 2018, 'כחול',  'petrol',122300),
(4, '55-112-99', 4, 'מאזדה 3',  2022, 'אדום',  'petrol', 18900),
(5, '10-200-30',10, 'מודל 3',    2023, 'לבן',   'electric',12450);

-- מכונאים
INSERT INTO tbl_mechanic (user_id, full_name, phone, specialty, hourly_rate, hire_date) VALUES
(3,   'יוסי לוי',   '0501112233', 'מנוע וגיר',  180.00, '2021-03-01'),
(NULL,'סאמר חורי',  '0526667788', 'חשמל רכב',   190.00, '2022-07-15'),
(NULL,'איתי שטרן',  '0539998877', 'פחחות וצבע', 170.00, '2020-01-10');

-- חלקי חילוף
INSERT INTO tbl_part (category_id, part_number, part_name, cost_price, sell_price, stock_qty, min_stock) VALUES
(6, 'OIL-5W30-4L', 'שמן מנוע 5W30 (4 ליטר)',      90.00, 160.00, 25, 5),
(6, 'OIL-FILTER',  'מסנן שמן',                     22.00,  55.00, 40, 8),
(1, 'AIR-FILTER',  'מסנן אוויר',                   30.00,  75.00, 18, 5),
(2, 'BRK-PAD-FR',  'רפידות בלם קדמיות',           120.00, 260.00, 12, 4),
(2, 'BRK-DISC',    'דיסק בלם',                    180.00, 380.00,  6, 4),
(3, 'BAT-60AH',    'מצבר 60 אמפר',                280.00, 520.00,  4, 2),
(4, 'COOLANT-4L',  'נוזל קירור (4 ליטר)',          45.00,  95.00, 10, 3),
(5, 'SHOCK-FR',    'בולם זעזועים קדמי',           210.00, 440.00,  3, 2),
(7, 'TIRE-195-65', 'צמיג 195/65R15',              210.00, 360.00, 16, 6),
(3, 'WIPER-SET',   'סט מגבים',                     40.00,  90.00, 22, 5);

-- כרטיסי עבודה
INSERT INTO tbl_job (job_number, vehicle_id, mechanic_id, status_id, mileage_in, description, diagnosis, labor_hours, labor_rate, opened_at, closed_at) VALUES
('JOB-2026-0001', 1, 1, 4, 84210, 'טיפול 80,000 ק"מ', 'הוחלפו שמן, מסננים ובדיקת בלמים', 2.0, 180, '2026-05-12 09:00:00', '2026-05-12 12:30:00'),
('JOB-2026-0002', 3, 1, 5, 96400, 'רעש בבלמים קדמיים', 'הוחלפו רפידות ודיסקים קדמיים',   3.5, 180, '2026-05-20 10:15:00', '2026-05-20 16:00:00'),
('JOB-2026-0003', 4, 2, 2,122300, 'מצבר חלש, לא מניע בבוקר', 'נדרשת החלפת מצבר',          1.0, 190, '2026-06-02 08:30:00', NULL),
('JOB-2026-0004', 5, 3, 1, 18900, 'בדיקת מתלים וטיפול שגרתי', NULL,                       0.0, 170, '2026-06-06 11:00:00', NULL);

-- חלקים בכרטיסי עבודה
INSERT INTO tbl_job_part (job_id, part_id, quantity, unit_price) VALUES
(1, 1, 1, 160.00),
(1, 2, 1, 55.00),
(1, 3, 1, 75.00),
(2, 4, 1, 260.00),
(2, 5, 2, 380.00),
(3, 6, 1, 520.00);

-- חשבוניות
INSERT INTO tbl_invoice (invoice_number, job_id, customer_id, issue_date, subtotal, vat_rate, vat_amount, discount, total, paid_amount, status) VALUES
('INV-2026-0001', 1, 1, '2026-05-12', 650.00, 18, 117.00, 0,   767.00, 767.00, 'paid'),
('INV-2026-0002', 2, 2, '2026-05-20',1650.00, 18, 297.00, 50, 1897.00, 1000.00,'partial');

-- תורים
INSERT INTO tbl_appointment (vehicle_id, mechanic_id, scheduled_at, duration_min, reason, status) VALUES
(2, 1, '2026-06-10 09:00:00', 90,  'טיפול תקופתי', 'scheduled'),
(6, 2, '2026-06-10 11:30:00', 60,  'בדיקת מערכת חשמל', 'scheduled'),
(4, 3, '2026-06-11 14:00:00', 120, 'יישור פחחות', 'scheduled');

-- הגדרות
INSERT INTO tbl_settings (id, garage_name, garage_phone, garage_address, garage_email, default_vat, default_rate) VALUES
(1, 'AutoCare Pro', '04-8000000', 'רחוב התעשייה 5, חיפה', 'info@autocare.local', 18.00, 180.00);

-- =====================================================================
--  סוף הסכמה
-- =====================================================================
