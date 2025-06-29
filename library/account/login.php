<?php
// 启动会话用于用户认证和消息存储
session_start();

// 数据库配置 - 与注册文件保持一致
$servername = "localhost";
$db_username = "library";
$db_password = "library";
$dbname = "library_db";

// 连接数据库
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 处理登录表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $errors = [];

    // 验证表单数据
    if (empty($username)) {
        $errors[] = "请输入用户名";
    }
    if (empty($password)) {
        $errors[] = "请输入密码";
    }

    // 如果验证通过，查询用户
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        // 检查用户是否存在
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id, $db_username, $db_password);
            $stmt->fetch();

            // 验证密码
            if (password_verify($password, $db_password)) {
                // 密码正确，设置会话变量
                $_SESSION["user_id"] = $id;
                $_SESSION["username"] = $db_username;
                $_SESSION["logged_in"] = true;

                // 重定向到图书馆主页或仪表板
                header("Location: /book/index.php");
                exit();
            } else {
                $errors[] = "用户名或密码不正确";
            }
        } else {
            $errors[] = "用户名或密码不正确";
        }
        $stmt->close();
    }

    // 存储错误信息并返回登录页面
    $_SESSION["errors"] = $errors;
    header("Location: login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>依依家的猫窝-登录</title>
    <link rel="stylesheet" href="/css/account.css">
</head>
<body>
    <header>
        <h1>依依家的猫窝</h1>
    </header>
    <main>
        <div class="container">
            <h2>用户登录</h2>
            <?php if (isset($_SESSION['errors'])): ?>
            <div class="error-messages">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <p><?php echo $_SESSION['success']; ?></p>
                <?php unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="username">用户名:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">密码:</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
                    </div>
                </div>
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
                <div class="form-group">
                    <button type="submit" name="login">登录</button>
                </div>
                <p>没有账号？<a href="register.php">立即注册</a></p>
            </form>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 依依家的猫窝</p>
    </footer>
</body>
</html>