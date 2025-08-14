<?php
require 'vendor/autoload.php';
require 'config.php';

function log_message($message, $level = 'INFO', $debugDetails = '') {
    $log_file = __DIR__ . '/../logs/register.log';
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

// 配置数据库
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$email = trim($_POST['email'] ?? '');
$email_code = trim($_POST['email_code'] ?? '');

// 检查必填
if (!$username || !$password) {
    log_message('注册必填字段缺失', 'ERROR', "用户名：$username | 密码：" . ($password ? '存在' : '缺失') . "");
    exit('用户名和密码不能为空');
}

// 验证用户名长度
if (strlen($username) < MIN_USERNAME_LEN || strlen($username) > MAX_USERNAME_LEN) {
    log_message('用户名长度无效', 'ERROR', "用户名：$username | 实际长度：" . strlen($username) . "");
    exit("用户名长度必须在" . MIN_USERNAME_LEN . "-" . MAX_USERNAME_LEN . "个字符之间");
}

// 验证密码长度
if (strlen($password) < MIN_PASSWORD_LEN) {
    log_message('密码长度过短', 'ERROR', "用户名：$username | 实际长度：" . strlen($password) . "");
    exit("密码长度不能少于" . MIN_PASSWORD_LEN . "个字符");
}

// 检查用户名是否存在
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    log_message('用户名已存在', 'ERROR', "冲突用户名：$username");
    exit('用户名已存在');
}

// 如果填写了邮箱
if ($email) {
    // 验证邮箱格式
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    log_message('邮箱格式无效', 'ERROR', "输入邮箱：$email");
    exit('邮箱格式不正确');
}
    
    // 邮箱是否被注册
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    log_message('邮箱已被注册', 'ERROR', "冲突邮箱：$email");
    exit('该邮箱已被注册');
}

    // 检查验证码
$stmt = $pdo->prepare("SELECT code, expires_at FROM email_verification WHERE email = ? AND type = 'register'");
$stmt->execute([$email]);
$row = $stmt->fetch();
if (!$row) {
    log_message('验证码记录不存在', 'ERROR', "目标邮箱：$email");
    exit('请先发送验证码');
}
    if (time() > strtotime($row['expires_at'])) {
    log_message('验证码过期', 'WARNING', "邮箱：$email | 过期时间：{$row['expires_at']}");
    exit('验证码已过期');
}
    if ($email_code !== $row['code']) {
    log_message('验证码错误', 'ERROR', "邮箱：$email | 输入验证码：$email_code");
    exit('验证码错误');
}
}

// 存储密码哈希
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// 插入用户
$stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
$stmt->execute([$username, $password_hash, $email ?: null]);
log_message('用户注册成功', 'INFO', "用户名：$username | 邮箱：$email");

// 删除验证码
if ($email) {
    $stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ? AND type = 'register'");
    $stmt->execute([$email]);
    log_message('已删除注册验证码记录', 'INFO', "邮箱：$email");
}

header('Location: login.php');
exit('注册成功，请登录');
?>