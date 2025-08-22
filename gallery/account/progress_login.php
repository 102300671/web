<?php
session_start();
require_once 'config.php';

define('LOG_FILE', __DIR__ . '/../logs/login.log');

// 日志记录函数
function log_message($message, $level = 'INFO') {
    $time = date('[Y-m-d H:i:s]');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $log = "$time [$level] [$ip] $message";

    if (DEBUG_MODE) {
        $log .= " | UA: $ua";
    }

    file_put_contents(LOG_FILE, $log . PHP_EOL, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        log_message("登录失败：缺少账号或密码", 'WARNING');
        die('请输入账号和密码');
    }

    try {
        // 判断是 QQ 还是群名
        $isQQ = preg_match('/^[1-9][0-9]{4,}$/', $identifier);
        $field = $isQQ ? 'qq' : 'group_name';

        if (DEBUG_MODE) {
            log_message("尝试登录：通过字段 $field 值 $identifier", 'DEBUG');
        } else {
            log_message("尝试登录：$field=$identifier", 'INFO');
        }

        // 查询用户
        $stmt = $pdo->prepare("SELECT * FROM users WHERE $field = ?");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();

        // 用户不存在
        if (!$user) {
            log_message("登录失败：用户不存在 ($field=$identifier)", 'NOTICE');
            echo '用户不存在';
            exit;
        }

        // 密码验证
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['qq'] = $user['qq'];
            $_SESSION['group_name'] = $user['group_name'];
            log_message("登录成功：user_id={$user['id']}，qq={$user['qq']}", 'INFO');
            header('Location: /content/index.php');
            exit;
        } else {
            log_message("登录失败：密码错误 ($field=$identifier)", 'NOTICE');
            echo '密码错误';
        }

    } catch (PDOException $e) {
        log_message("数据库异常：" . $e->getMessage(), 'ERROR');
        if (DEBUG_MODE) {
            echo '登录失败：' . htmlspecialchars($e->getMessage());
        } else {
            echo '登录失败，请稍后再试';
        }
    }
} else {
    log_message("非法请求方式：" . $_SERVER['REQUEST_METHOD'], 'WARNING');
    header('Location: login.php');
    exit;
}