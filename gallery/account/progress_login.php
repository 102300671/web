<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        die('请输入账号和密码');
    }

    try {
        // 自动判断是QQ号还是群内名称
        $isQQ = preg_match('/^[1-9][0-9]{4,}$/', $identifier);
        $field = $isQQ ? 'qq' : 'group_name';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE $field = ?");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // 登录成功
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['qq'] = $user['qq'];
            $_SESSION['group_name'] = $user['group_name'];
            header('Location: /content/index.php');
            exit;
        } else {
            echo '账号或密码错误';
        }
    } catch (PDOException $e) {
        echo '登录失败：' . htmlspecialchars($e->getMessage());
    }
} else {
    header('Location: login.php');
    exit;
}