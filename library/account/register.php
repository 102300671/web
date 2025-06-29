<?php
// 启动会话用于存储错误信息
session_start();

// 数据库配置 - 请根据实际情况修改
$servername = "localhost";
$dbname = "library_db";

// 连接数据库
$conn = new mysqli($servername, 'library', 'library', $dbname);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $errors = [];

    // 验证表单数据
    if (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = "用户名长度必须在3-20个字符之间";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "请输入有效的邮箱地址";
    }
    if (strlen($password) < 6) {
        $errors[] = "密码长度不能少于6个字符";
    }
    if ($password !== $confirm_password) {
        $errors[] = "两次输入的密码不一致";
    }

    // 检查用户名和邮箱是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "用户名或邮箱已被注册";
    }
    $stmt->close();

    // 如果没有错误，则插入用户数据
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION["success"] = "注册成功，请登录";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "注册失败: " . $conn->error;
        }
        $stmt->close();
    }

    // 存储错误信息并返回注册页面
    $_SESSION["errors"] = $errors;
    }

    // 显示注册表单
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>依依家的猫窝-注册</title>
        <link rel="stylesheet" href="/css/account.css">
    </head>
    <body>
        <header>
            <h1>依依家的猫窝</h1>
        </header>
        <main>
            <div class="container">
                <h2>用户注册</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="username">用户名:</label>
                        <input type="text" id="username" name="username" required minlength="3" maxlength="20" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">邮箱:</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">密码:</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">👁️</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认密码:</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">👁️</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="register">注册</button>
                    </div>
                    <p>已有账号？<a href="login.php">立即登录</a></p>
                </form>
            </div>
        </main>
        <footer>
            <p>&copy; 2025 依依家的猫窝</p>
        </footer>
    </body>
    </html>
    <?php

$conn->close();
?>

<script>
function togglePasswordVisibility(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleBtn = passwordField.parentElement.querySelector('.toggle-password');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleBtn.textContent = '隐藏密码';
    } else {
        passwordField.type = 'password';
        toggleBtn.textContent = '显示密码';
    }
}
</script>