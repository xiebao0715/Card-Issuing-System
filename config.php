<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'yh');
define('DB_USER', 'yh');
define('DB_PASS', 'P6BzTRKTPFnFFZ5P');
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ADMIN_PASS_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
define('SESSION_LIFETIME', 7200);

function getSiteName() {
    static $name = null;
    if ($name === null) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
            $stmt->execute(['site_name']);
            $name = $stmt->fetchColumn() ?: '卡密商城';
        } catch (Exception $e) {
            $name = '卡密商城';
        }
    }
    return $name;
}

function getSiteLogo() {
    static $logo = null;
    if ($logo === null) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
            $stmt->execute(['site_logo']);
            $logo = $stmt->fetchColumn() ?: '';
        } catch (Exception $e) {
            $logo = '';
        }
    }
    return $logo;
}

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

date_default_timezone_set('Asia/Shanghai');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $db = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);
        } catch (PDOException $e) {
            if (php_sapi_name() !== 'cli') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['code' => 1, 'msg' => '数据库连接失败：' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }
    return $db;
}

function initDB() {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_id INT DEFAULT 0,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        fake_sold INT DEFAULT 0,
        status TINYINT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS cards (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        content TEXT NOT NULL,
        used TINYINT DEFAULT 0,
        order_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_no VARCHAR(64) UNIQUE NOT NULL,
        product_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        total_price DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(32) NOT NULL,
        status VARCHAR(32) DEFAULT 'pending',
        card_content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        `key` VARCHAR(128) PRIMARY KEY,
        `value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("CREATE TABLE IF NOT EXISTS email_codes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        code VARCHAR(16) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $defaults = [
        'site_name' => '卡密商城',
        'site_logo' => '',
        'announcement' => '欢迎使用卡密商城！',
        'wechat_qrcode' => '',
        'alipay_qrcode' => '',
        'service_wechat' => '',
        'service_qq' => '',
        'service_wechat_qrcode' => '',
        'service_qq_qrcode' => '',
        'marquee_1_text' => '商品须知',
        'marquee_1_color' => '#e74c3c',
        'marquee_2_text' => '充值流程',
        'marquee_2_color' => '#4a6cf7',
        'notice_content' => '<h3>商品须知</h3><p>1. 所有商品均为虚拟商品，购买后不可退换。</p><p>2. 请在购买前确认商品信息无误。</p><p>3. 卡密请在有效期内使用，过期作废。</p>',
        'recharge_content' => '<h3>充值流程</h3><p>1. 选择需要充值的商品。</p><p>2. 填写收件邮箱和购买数量。</p><p>3. 选择支付方式完成支付。</p><p>4. 等待系统自动发货或管理员手动发货。</p><p>5. 收到卡密后按照使用说明操作。</p>',
        'faq' => '1. 如何购买？\n选择商品，填写邮箱，完成支付即可。\n\n2. 如何获取卡密？\n付款后管理员会尽快发货，卡密将发送至您的邮箱。\n\n3. 如何查询订单？\n使用下单邮箱或订单号在订单查询页面查询。',
        'card_usage' => '1. 复制卡密\n2. 打开对应平台/软件\n3. 在兑换/激活页面粘贴卡密\n4. 确认激活即可使用',
        'refund_policy' => '虚拟商品一经发货概不退换，请确认需求后再购买。',
        'smtp_host' => '',
        'smtp_port' => '465',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_from' => '',
        'smtp_ssl' => '1',
        'admin_password' => ADMIN_PASS_HASH,
    ];
    foreach ($defaults as $k => $v) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = ?");
        $stmt->execute([$k]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
            $stmt->execute([$k, $v]);
        }
    }

    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    try {
        $db->exec("ALTER TABLE products ADD COLUMN fake_sold INT DEFAULT 0");
    } catch (Exception $e) {}
}

function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn() ?: '';
}

function setSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    return $stmt->execute([$key, $value]);
}

function generateOrderNo() {
    return 'ORD' . date('YmdHis') . mt_rand(1000, 9999);
}

function getProductStock($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
    $stmt->execute([$productId]);
    return (int)$stmt->fetchColumn();
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        jsonOutput(['code' => 401, 'msg' => '未登录或登录已过期'], 401);
    }
}

function sendMail($to, $subject, $body) {
    $smtpHost = getSetting('smtp_host');
    if (empty($smtpHost)) {
        return false;
    }
    $smtpPort = getSetting('smtp_port');
    $smtpUser = getSetting('smtp_user');
    $smtpPass = getSetting('smtp_pass');
    $smtpFrom = getSetting('smtp_from');
    $smtpSsl = getSetting('smtp_ssl') === '1';

    $headers = "From: $smtpFrom\r\n";
    $headers .= "Reply-To: $smtpFrom\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

    $proto = $smtpSsl ? 'ssl://' : '';
    $socket = @fsockopen($proto . $smtpHost, $smtpPort, $errno, $errstr, 10);
    if (!$socket) return false;

    function smtpRead($socket) {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $data;
    }
    function smtpSend($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
        return smtpRead($socket);
    }

    smtpRead($socket);
    smtpSend($socket, "EHLO localhost");
    if ($smtpSsl) {
        smtpSend($socket, "STARTTLS");
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        smtpSend($socket, "EHLO localhost");
    }
    smtpSend($socket, "AUTH LOGIN");
    smtpSend($socket, base64_encode($smtpUser));
    smtpSend($socket, base64_encode($smtpPass));
    smtpSend($socket, "MAIL FROM:<$smtpFrom>");
    smtpSend($socket, "RCPT TO:<$to>");
    smtpSend($socket, "DATA");
    fwrite($socket, "Subject: $subject\r\n$headers\r\n$body\r\n.\r\n");
    smtpRead($socket);
    smtpSend($socket, "QUIT");
    fclose($socket);
    return true;
}

function sendVerificationCode($email) {
    $db = getDB();
    $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 300);

    $stmt = $db->prepare("DELETE FROM email_codes WHERE email = ?");
    $stmt->execute([$email]);

    $stmt = $db->prepare("INSERT INTO email_codes (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $code, $expiresAt]);

    $body = "<h2>验证码</h2><p>您的验证码是：<strong style='font-size:24px;color:#e74c3c;'>$code</strong></p><p>5分钟内有效，请勿泄露。</p>";
    return sendMail($email, '邮箱验证码', $body);
}

function verifyCode($email, $code) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM email_codes WHERE email = ? AND code = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email, $code]);
    if ($stmt->fetch()) {
        $stmt = $db->prepare("UPDATE email_codes SET used = 1 WHERE email = ? AND code = ?");
        $stmt->execute([$email, $code]);
        return true;
    }
    return false;
}

function sendOrderNotification($email, $orderNo, $cardContent, $productTitle) {
    $body = "<h2>订单发货通知</h2>";
    $body .= "<p>商品：<strong>$productTitle</strong></p>";
    $body .= "<p>订单号：<strong>$orderNo</strong></p>";
    $body .= "<p>卡密内容：</p>";
    $body .= "<pre style='background:#f5f5f5;padding:15px;border-radius:5px;'>" . htmlspecialchars($cardContent) . "</pre>";
    $body .= "<p>请妥善保管您的卡密信息。</p>";
    return sendMail($email, "订单 $orderNo 已发货", $body);
}

function jsonOutput($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
