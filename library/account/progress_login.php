<?php
require 'config.php';

session_start();

// 配置数据库
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$loginIdentifier = trim($_POST['login-identifier'] ?? '');
$password = trim($_POST['password'] ?? '');
$emailCode = trim($_POST['email_code'] ?? '');
$loginType = trim($_POST['login-type'] ?? 'password');

// 检查必填字段
if (!$loginIdentifier) {
    exit('用户名/邮箱不能为空');
}

if ($loginType === 'password' && !$password) {
    exit('密码不能为空');
}

if ($loginType === 'code' && !$emailCode) {
    exit('验证码不能为空');
}

// 根据登录类型处理
if ($loginType === 'password') {
    // 密码登录
    // 检查用户是否存在（用户名或邮箱）
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$loginIdentifier, $loginIdentifier]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
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
        exit('请输入有效的邮箱地址');
    }
    
    // 检查验证码
    $stmt = $pdo->prepare("SELECT code, expires_at FROM email_verification WHERE email = ? AND type = 'login'");
    $stmt->execute([$loginIdentifier]);
    $row = $stmt->fetch();
    
    if (!$row) {
        exit('请先发送验证码');
    }
    
    if (time() > strtotime($row['expires_at'])) {
        exit('验证码已过期');
    }
    
    if ($emailCode !== $row['code']) {
        exit('验证码错误');
    }
    
    // 获取用户信息
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$loginIdentifier]);
    $user = $stmt->fetch();
    
    if (!$user) {
        exit('用户不存在');
    }
    
    // 登录成功
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // 删除已使用的验证码
    $stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ? AND type = 'login'");
    $stmt->execute([$loginIdentifier]);
    
    header('Location: /book/index.php');
    exit;
}
?>