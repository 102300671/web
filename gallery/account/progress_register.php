<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qq = trim($_POST['qq'] ?? '');
    $group_name = trim($_POST['group_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $avatarPath = null;

    // 
    if (!preg_match('/^[1-9][0-9]{4,}$/', $qq)) {
        exit('QQ');
    }
    if (empty($group_name) || strlen($group_name) > 20) {
        exit('20');
    }
    if (strlen($password) < 8) {
        exit('8');
    }

    // 
    $stmt = $pdo->prepare('SELECT id FROM users WHERE qq = ?');
    $stmt->execute([$qq]);
    if ($stmt->fetch()) {
        exit('QQ');
    }

    // 
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $fileType = mime_content_type($_FILES['avatar']['tmp_name']);
        $fileSize = $_FILES['avatar']['size'];

        if (!array_key_exists($fileType, $allowed)) {
            exit('');
        }
        if ($fileSize > 2 * 1024 * 1024) { // 2MB 
            exit('2MB');
        }

        $ext = $allowed[$fileType];
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid('avatar_') . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            // 
            $avatarPath = '/account/uploads/avatars/' . $fileName;
        } else {
            exit('');
        }
    }

    // 
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 
    $stmt = $pdo->prepare('INSERT INTO users (qq, group_name, password, avatar) VALUES (?, ?, ?, ?)');
    if ($stmt->execute([$qq, $group_name, $hashedPassword, $avatarPath])) {
        header('Location: login.php');
        exit;
    } else {
        echo '';
    }
} else {
    echo '';
}