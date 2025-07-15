<?php
require 'vendor/autoload.php';
require 'config.php';

function log_verification_error($email, $type, $error_message) {
    $log_dir = __DIR__ . '/logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $log_file = $log_dir . 'send_code.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] Email: $email, Type: $type, Error: $error_message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 配置数据库
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$email = trim($_POST['email'] ?? '');
if (!$email) {
    exit('邮箱不能为空');
}
// 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('邮箱格式不正确');
}

// 获取场景类型，默认为注册
$type = $_POST['type'] ?? 'register';
if (!in_array($type, ['register', 'login'])) {
    exit('无效的场景类型');
}

// 根据场景类型执行不同的邮箱检查
if ($type === 'register') {
    // 注册场景：检查邮箱是否已被注册
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        exit('该邮箱已被注册');
    }
} else {
    // 登录场景：检查邮箱是否存在
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        exit('该邮箱未注册');
    }
}

// 检查是否已经发送且未过期
$stmt = $pdo->prepare("SELECT expires_at FROM email_verification WHERE email = ? AND type = ?");
$stmt->execute([$email, $type]);
$row = $stmt->fetch();
if ($row && strtotime($row['expires_at']) > time()) {
    exit('验证码已发送，请稍后再试');
}

// 生成验证码
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', time() + CODE_EXPIRE);

// 插入或更新验证码表
$stmt = $pdo->prepare("
    INSERT INTO email_verification (email, code, expires_at, type)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        code = VALUES(code),
        expires_at = VALUES(expires_at)
");
$stmt->execute([$email, $code, $expires_at, $type]);

// 发送邮件
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, '=?UTF-8?B?'.base64_encode(SMTP_FROM_NAME).'?=');
    $mail->CharSet = 'UTF-8';
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = '您的验证码';
    $mail->Body    = "您的验证码是：<b>{$code}</b><br>5分钟内有效。";

    $mail->send();
    echo '验证码已发送';
} catch (Exception $e) {
    $errorMsg = '[' . date('Y-m-d H:i:s') . '] 发送邮件失败: ' . $mail->ErrorInfo . PHP_EOL;
    $timestamp = date('[d/M/Y:H:i:s O]');
    $logMessage = "{$timestamp} [error] {$errorMsg}\n";
    error_log($logMessage, 3, 'logs/send_code.log');    
    $stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ?");
    $stmt->execute([$email]);
    
    $error_message = $mail->ErrorInfo;
    log_verification_error($email, $type, $error_message);
    exit('验证码发送失败: ' . $error_message);
}