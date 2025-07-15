<?php
require 'vendor/autoload.php';
require 'config.php';

// 配置数据库
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$email = trim($_POST['email'] ?? '');
$email_code = trim($_POST['email_code'] ?? '');

// 检查必填
if (!$username || !$password) {
    exit('用户名和密码不能为空');
}

// 验证用户名长度
if (strlen($username) < MIN_USERNAME_LEN || strlen($username) > MAX_USERNAME_LEN) {
    exit("用户名长度必须在" . MIN_USERNAME_LEN . "-" . MAX_USERNAME_LEN . "个字符之间");
}

// 验证密码长度
if (strlen($password) < MIN_PASSWORD_LEN) {
    exit("密码长度不能少于" . MIN_PASSWORD_LEN . "个字符");
}

// 检查用户名是否存在
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    exit('用户名已存在');
}

// 如果填写了邮箱
if ($email) {
    // 验证邮箱格式
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit('邮箱格式不正确');
    }
    
    // 邮箱是否被注册
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        exit('该邮箱已被注册');
    }

    // 检查验证码
    $stmt = $pdo->prepare("SELECT code, expires_at FROM email_verification WHERE email = ? AND type = 'register'");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) {
        exit('请先发送验证码');
    }
    if (time() > strtotime($row['expires_at'])) {
        exit('验证码已过期');
    }
    if ($email_code !== $row['code']) {
        exit('验证码错误');
    }
}

// 存储密码哈希
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// 插入用户
$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
$stmt->execute([$username, $password_hash, $email ?: null]);

// 删除验证码
if ($email) {
    $stmt = $pdo->prepare("DELETE FROM email_verification WHERE email = ? AND type = 'register'");
    $stmt->execute([$email]);
}

header('Location: login.php');
exit('注册成功，请登录');
?>