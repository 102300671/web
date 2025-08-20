<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $userId = $_SESSION['user_id'];

    // 获取用户信息
    $stmt = $pdo->prepare("SELECT password, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // 删除头像文件
        if (!empty($user['avatar']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $user['avatar'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $user['avatar']);
        }

        // 删除用户记录
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        // 清理 session
        $_SESSION = [];
        session_destroy();

        header("Location: /account/register.php?status=deleted");
        exit();
    } else {
        $error = "密码错误，无法注销账户。";
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>注销账户</title>
  <link rel="stylesheet" href="/assets/css/account.css">
</head>
<body>
  <header><h1>注销账户</h1></header>
  <main>
    <div class="container">
      <h2>确认注销</h2>
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <p style="color:red;">⚠️ 警告：此操作将永久删除您的账户和所有数据，且不可恢复！</p>
      <form method="post">
        <div class="form-group">
          <label for="password">请输入密码确认：</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="delete-btn">确认注销</button>
        <a href="profile.php" class="cancel-btn">取消</a>
      </form>
    </div>
  </main>
</body>
</html>