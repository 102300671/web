<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PHP处理逻辑
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    $conn = new mysqli('localhost', 'crypto', 'crypto', 'crypto_db');
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error = "注册失败，用户名可能已存在";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册</title>
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
        <h2>注册</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>
        <form method="post">
            用户名：<input type="text" name="username" required><br>
            密码：<input type="password" id="password" name="password" required><br>
            <input type="checkbox" id="showPassword" onclick="togglePasswordVisibility()"> 显示密码<br>
            <button type="submit">注册</button>
        </form>
        <a href="login.php">已有账号？去登录</a>
    </div>
</body>
</html>