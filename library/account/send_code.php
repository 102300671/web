<?php

require 'vendor/autoload.php';
require 'config.php';

// 确保日志目录存在
$log_dir = __DIR__ . '/logs/';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_message($message, $level = 'INFO', $debugDetails = '') {
    $log_file = __DIR__ . '/logs/send_code.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $log_entry = "[$timestamp] [$level] IP:$ip | $message";
    if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($debugDetails)) {
        $log_entry .= " | $debugDetails";
    }
    $log_entry .= "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // 如果是错误级别，同时输出到PHP错误日志
    if ($level === 'ERROR') {
        error_log($message);
    }
}

// 记录脚本开始执行
log_message("脚本开始执行");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // 配置数据库
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    log_message("数据库连接成功");
} catch (PDOException $e) {
    $errorMsg = "数据库连接失败: " . $e->getMessage();
    log_message($errorMsg, 'ERROR');
    exit('系统维护中，请稍后再试');
}

$email = trim($_POST['email'] ?? '');
if (!$email) {
    log_message("邮箱为空", 'ERROR');
    exit('邮箱不能为空');
}

// 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    log_message("邮箱格式无效: $email", 'ERROR');
    exit('邮箱格式不正确');
}

// 获取场景类型
$type = $_POST['type'] ?? 'register';
if (!in_array($type, ['register', 'login'])) {
    log_message("无效的场景类型: $type", 'ERROR');
    exit('无效的场景类型');
}

// 场景检查
try {
    if ($type === 'register') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            log_message("邮箱已被注册: $email", 'ERROR');
            exit('该邮箱已被注册');
        }
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            log_message("邮箱未注册: $email", 'ERROR');
            exit('该邮箱未注册');
        }
    }
} catch (PDOException $e) {
    $errorMsg = "数据库查询失败: " . $e->getMessage();
    log_message($errorMsg, 'ERROR');
    exit('系统错误，请稍后再试');
}

// 检查验证码发送频率
try {
    $stmt = $pdo->prepare("SELECT expires_at FROM email_verification WHERE email = ? AND type = ?");
    $stmt->execute([$email, $type]);
    $row = $stmt->fetch();
    
    if ($row && strtotime($row['expires_at']) > time()) {
        $timeLeft = strtotime($row['expires_at']) - time();
        log_message("请求过于频繁，剩余冷却时间：{$timeLeft}秒", 'WARNING', "Email: $email");
        exit('验证码已发送，请稍后再试');
    }
} catch (PDOException $e) {
    $errorMsg = "验证码查询失败: " . $e->getMessage();
    log_message($errorMsg, 'ERROR');
    exit('系统错误，请稍后再试');
}

// 生成验证码
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', time() + CODE_EXPIRE);

// 存储验证码
try {
    $stmt = $pdo->prepare("
        INSERT INTO email_verification (email, code, expires_at, type)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            code = VALUES(code),
            expires_at = VALUES(expires_at)
    ");
    $stmt->execute([$email, $code, $expires_at, $type]);
    log_message("验证码生成成功", 'INFO', "Email: $email | Code: $code | 有效期至: $expires_at");
} catch (PDOException $e) {
    $errorMsg = "验证码存储失败: " . $e->getMessage();
    log_message($errorMsg, 'ERROR');
    exit('系统错误，请稍后再试');
}

// 发送邮件
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_ENCRYPT;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, '=?UTF-8?B?'.base64_encode(SMTP_FROM_NAME).'?=');
    $mail->CharSet = 'UTF-8';
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = '您的验证码';
    $mail->Body    = "您的验证码是：<b>{$code}</b><br>有效时间：" . (CODE_EXPIRE/60) . "分钟。";

    $mail->send();
    log_message("邮件发送成功", 'INFO', "Email: $email | Code: $code");
    echo '验证码已发送';
} catch (Exception $e) {
    $errorDetails = "PHPMailer Error [{$e->getCode()}]: {$e->getMessage()}";
    log_message("邮件发送失败", 'ERROR', "Email: $email | Error: $errorDetails");
    
    try {
        $stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ?");
        $stmt->execute([$email]);
        log_message("已清理失败的验证码记录", 'INFO', "Email: $email");
    } catch (PDOException $dbError) {
        log_message("清理验证码记录失败", 'ERROR', $dbError->getMessage());
    }
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        exit("验证码发送失败: $errorDetails");
    } else {
        exit('验证码发送失败，请稍后再试');
    }
}

log_message("脚本执行完成");
?>