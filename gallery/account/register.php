<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>注册</title>
  <link rel="stylesheet" href="/assets/css/account.css">
</head>
<body>
  <header>
    <h1>依依家的猫窝</h1>
  </header>

  <main>
    <div class="container">
      <h2>注册</h2>
      <div id="status"></div>

      <form method="post" action="progress_register.php">
        <!-- QQ号 -->
        <div class="form-group">
          <label for="qq">QQ号：</label>
          <input type="text" id="qq" name="qq" required pattern="^[1-9][0-9]{4,}$" title="请输入有效的QQ号">
          <small>请输入5位以上纯数字的QQ号</small>
        </div>

        <!-- 群内名称 -->
        <div class="form-group">
          <label for="group_name">群内名称：</label>
          <input type="text" id="group_name" name="group_name" required maxlength="20">
          <small>请输入你在QQ群中使用的昵称（最多20字）</small>
        </div>

        <!-- 密码 -->
        <div class="form-group">
          <label for="password">密码：</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" required minlength="8">
            <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
          </div>
          <small>至少8个字符</small>
        </div>

        <button type="submit">注册</button>
      </form>
      <a href="login.php">已有账号？去登录</a>
    </div>
  </main>

  <footer>
    &copy; <?php echo date('Y'); ?> 依依家的猫窝
  </footer>

  <script>
    function togglePasswordVisibility(inputId) {
      const input = document.getElementById(inputId);
      const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', type);
    }
  </script>
</body>
</html>