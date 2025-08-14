<?php
require 'config.php';

session_start();
function log_message($message, $level = 'INFO', $debugDetails = '') {
    $log_file = __DIR__ . '/../logs/login.log';
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

$loginIdentifier = trim($_POST['login-identifier'] ?? '');
$password = trim($_POST['password'] ?? '');
$emailCode = trim($_POST['email_code'] ?? '');
$loginType = trim($_POST['login-type'] ?? 'password');

// 检查必填字段
if (!$loginIdentifier) {
    log_message('用户名/邮箱为空', 'ERROR', '登录标识符为空');
    exit('用户名/邮箱不能为空');
}

if ($loginType === 'password' && !$password) {
    log_message('密码为空', 'ERROR', '登录类型：密码登录');
    exit('密码不能为空');
}

if ($loginType === 'code' && !$emailCode) {
    log_message('验证码为空', 'ERROR', '登录类型：验证码登录');
    exit('验证码不能为空');
}

// 根据登录类型处理
if ($loginType === 'password') {
    // 密码登录
    // 检查用户是否存在（用户名或邮箱）
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$loginIdentifier, $loginIdentifier]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
    $details = $user ? '密码验证失败' : '用户不存在';
    log_message('密码登录失败', 'ERROR', "登录标识符：$loginIdentifier | $details");
    exit('用户名/邮箱或密码错误');
}
    
    // 登录成功
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    header('Location: /book/index.php');
    exit;
} else {
    // 验证码登录
    // 验证邮箱格式
    if (!filter_var($loginIdentifier, FILTER_VALIDATE_EMAIL)) {
    log_message('邮箱格式无效', 'ERROR', "输入邮箱：$loginIdentifier");
    exit('请输入有效的邮箱地址');
}
    
    // 检查验证码
    $stmt = $pdo->prepare("SELECT code, expires_at FROM email_verification WHERE email = ? AND type = 'login'");
    $stmt->execute([$loginIdentifier]);
    $row = $stmt->fetch();
    
    if (!$row) {
    log_message('验证码记录不存在', 'ERROR', "目标邮箱：$loginIdentifier");
    exit('请先发送验证码');
}
    
    if (time() > strtotime($row['expires_at'])) {
    log_message('验证码过期', 'WARNING', "过期时间：{$row['expires_at']}");
    exit('验证码已过期');
    }
    
    if ($emailCode !== $row['code']) {
    log_message('验证码错误', 'ERROR', "邮箱：$loginIdentifier | 输入验证码：$emailCode");
    exit('验证码错误');
}
    
    // 获取用户信息
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$loginIdentifier]);
    $user = $stmt->fetch();
    
    if (!$user) {
    log_message('用户不存在', 'ERROR', "邮箱：$loginIdentifier");
    exit('用户不存在');
}
    
    // 登录成功
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // 删除已使用的验证码
$stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ? AND type = 'login'");
$stmt->execute([$loginIdentifier]);
log_message('已删除登录验证码记录', 'INFO', "邮箱：$loginIdentifier");
    
    header('Location: /book/index.php');
    exit;
}
?>