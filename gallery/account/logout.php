<?php
session_start();

// 清空所有 session 数据
$_SESSION = [];

// 如果使用了 cookie 存储 session，则清除 cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 销毁 session
session_destroy();

// 跳转到登录页面
header("Location: /account/login.php");
exit();