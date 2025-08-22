<?php
require 'config.php';

define('LOG_FILE', __DIR__ . '/../logs/register.log');

// 日志记录函数
function log_message($message, $level = 'INFO') {
    $time = date('[Y-m-d H:i:s]');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $log = "$time [$level] [$ip] $message";
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $log .= " | UA: $ua";
    }
    file_put_contents(LOG_FILE, $log . PHP_EOL, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qq = trim($_POST['qq'] ?? '');
    $group_name = trim($_POST['group_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $avatarPath = null;

    log_message("注册请求提交：QQ=$qq, 群名=$group_name");

    // 验证输入格式
    if (!preg_match('/^[1-9][0-9]{4,}$/', $qq)) {
        log_message("注册失败：无效的QQ号格式 ($qq)", 'WARNING');
        exit('无效的QQ号格式');
    }
    if (empty($group_name) || strlen($group_name) > 20) {
        log_message("注册失败：群名不合法 ($group_name)", 'WARNING');
        exit('群内名称不能为空且最多20字');
    }
    if (strlen($password) < 8) {
        log_message("注册失败：密码长度不足", 'WARNING');
        exit('密码长度至少为8个字符');
    }

    // 检查是否已存在
    $stmt = $pdo->prepare('SELECT id FROM users WHERE qq = ?');
    $stmt->execute([$qq]);
    if ($stmt->fetch()) {
        log_message("注册失败：QQ已存在 ($qq)", 'WARNING');
        exit('该QQ号已注册');
    }

    // 处理头像上传
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $fileType = mime_content_type($_FILES['avatar']['tmp_name']);
        $fileSize = $_FILES['avatar']['size'];

        if (!array_key_exists($fileType, $allowed)) {
            log_message("注册失败：头像格式不支持 ($fileType)", 'WARNING');
            exit('不支持的图片格式');
        }
        if ($fileSize > 2 * 1024 * 1024) {
            log_message("注册失败：头像文件过大 ($fileSize bytes)", 'WARNING');
            exit('头像大小不能超过2MB');
        }

        $ext = $allowed[$fileType];
        $avatarDir = __DIR__ . '/avatars/';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0777, true);
        }

        $fileName = uniqid('avatar_') . '.' . $ext;
        $targetPath = $avatarDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $avatarPath = '/account/avatars/' . $fileName;
            log_message("头像上传成功: $avatarPath");
        } else {
            log_message("注册失败：头像上传失败", 'ERROR');
            exit('头像上传失败');
        }
    }

    // 密码哈希
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 写入数据库
    $stmt = $pdo->prepare('INSERT INTO users (qq, group_name, password, avatar) VALUES (?, ?, ?, ?)');
    if ($stmt->execute([$qq, $group_name, $hashedPassword, $avatarPath])) {
        log_message("注册成功：QQ=$qq, 群名=$group_name, avatar=$avatarPath", 'INFO');
        header('Location: login.php');
        exit;
    } else {
        log_message("注册失败：数据库写入失败", 'ERROR');
        echo '注册失败，请稍后再试';
    }
} else {
    log_message("非法请求方式：" . $_SERVER['REQUEST_METHOD'], 'WARNING');
    echo '无效请求';
}