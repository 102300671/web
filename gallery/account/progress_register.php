<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qq = trim($_POST['qq'] ?? '');
    $group_name = trim($_POST['group_name'] ?? '');
    $password = $_POST['password'] ?? '';

    // 验证输入格式
    if (!preg_match('/^[1-9][0-9]{4,}$/', $qq)) {
        exit('无效的QQ号格式');
    }
    if (empty($group_name) || strlen($group_name) > 20) {
        exit('群内名称不能为空且最多20字');
    }
    if (strlen($password) < 8) {
        exit('密码长度至少为8个字符');
    }

    // 检查是否已存在
    $stmt = $pdo->prepare('SELECT id FROM users WHERE qq = ?');
    $stmt->execute([$qq]);
    if ($stmt->fetch()) {
        exit('该QQ号已注册');
    }

    // 密码哈希
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 写入数据库
    $stmt = $pdo->prepare('INSERT INTO users (qq, group_name, password) VALUES (?, ?, ?)');
    if ($stmt->execute([$qq, $group_name, $hashedPassword])) {
        header('Location: login.php');
    } else {
        echo '注册失败，请稍后再试';
    }
} else {
    echo '无效请求';
}