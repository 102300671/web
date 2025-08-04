<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>登录</title>
  <link rel="stylesheet" href="/assets/css/account.css">
</head>
<body>
  <header>
    <h1>依依家的猫窝</h1>
  </header>

  <main>
    <div class="container">
      <h2>登录</h2>
      <div id="status"></div>

      <form method="post" action="progress_login.php">
        <!-- 统一输入框 -->
        <div class="form-group">
          <label for="identifier">QQ号/群内名称：</label>
          <input type="text" id="identifier" name="identifier" required>
          <small>输入注册的QQ号或你在群里的昵称</small>
        </div>

        <!-- 密码 -->
        <div class="form-group">
          <label for="password">密码：</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" required minlength="8">
            <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
          </div>
        </div>

        <button type="submit">登录</button>
      </form>
      <a href="register.php">没有账号？去注册</a>
    </div>
  </main>

  <footer>
    &copy; <?php echo date('Y'); ?> 依依家的猫窝
  </footer>

  <script>
    function togglePasswordVisibility(inputId) {
      const input = document.getElementById(inputId);
      input.type = input.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>