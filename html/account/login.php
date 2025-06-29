<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PHP处理逻辑
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    session_start();
    $conn = new mysqli('localhost', 'crypto', 'crypto', 'crypto_db');
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hash);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: /code/index.php");
            exit();
        }
    }
    $error = "登录失败";
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>登录</title>
    <link rel="stylesheet" href="/css/account.css">
    <script>
        function togglePasswordVisibility() {
            var passwordInput = document.getElementById("password");
            var checkbox = document.getElementById("showPassword");
            if (checkbox.checked) {
                passwordInput.type = "text";
            } else {
                passwordInput.type = "password";
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>登录</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <form method="post">
            用户名：<input type="text" name="username" required><br>
            密码：<input type="password" id="password" name="password" required><br>
            <input type="checkbox" id="showPassword" onclick="togglePasswordVisibility()"> 显示密码<br>
            <button type="submit">登录</button>
        </form>
        <a href="register_combined.php">没有账号？去注册</a>
    </div>
</body>
</html>