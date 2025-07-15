<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>登录</title>
  <link rel="stylesheet" href="/css/account.css">
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
        <input type="hidden" name="type" value="login">
        <div class="form-group">
          <label for="login-identifier" id="login-identifier-label">用户名/邮箱：</label>
          <input type="text" id="login-identifier" name="login-identifier" required>
        </div>
        <div class="form-group" id="password-group">
          <label for="password">密码：</label>
          <input type="password" id="password" name="password">
        </div>
        <div id="verification-group" style="display: none;">
          <div class="form-group">
            <label for="email_code">验证码：</label>
            <input type="text" id="email_code" name="email_code" maxlength="6">
          </div>
          <button type="button" id="send-code-btn">发送验证码</button>
          <span id="countdown"></span>
        </div>
        <input type="hidden" id="login-type" name="login-type" value="password">
        <button type="submit">登录</button>
        <button type="button" id="switch-login-type">使用邮箱验证码登录</button>
      </form>
    </div>
  </main>
  <script>
    const loginIdentifierInput = document.getElementById('login-identifier');
    const passwordGroup = document.getElementById('password-group');
    const verificationGroup = document.getElementById('verification-group');
    const sendCodeBtn = document.getElementById('send-code-btn');
    const countdownSpan = document.getElementById('countdown');
    const statusDiv = document.getElementById('status');
    const loginTypeInput = document.getElementById('login-type');
    const switchLoginTypeBtn = document.getElementById('switch-login-type');

    switchLoginTypeBtn.onclick = function() {
      const label = document.getElementById('login-identifier-label');
      if (loginTypeInput.value === 'password') {
        passwordGroup.style.display = 'none';
        verificationGroup.style.display = 'block';
        loginTypeInput.value = 'code';
        switchLoginTypeBtn.textContent = '使用密码登录';
        label.textContent = '邮箱：';
            const email = loginIdentifierInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
              loginIdentifierInput.value = '';
            }
      } else {
        passwordGroup.style.display = 'block';
        verificationGroup.style.display = 'none';
        loginTypeInput.value = 'password';
        switchLoginTypeBtn.textContent = '使用邮箱验证码登录';
        label.textContent = '用户名/邮箱：';
      }
    };

    // 发送验证码
    sendCodeBtn.onclick = function() {
      const email = loginIdentifierInput.value;
      if (!email) {
        showStatus('请先填写邮箱', 'error');
        return;
      }

      // 简单邮箱格式验证
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showStatus('请输入有效的邮箱地址', 'error');
        return;
      }

      sendCodeBtn.disabled = true;
      sendCodeBtn.textContent = '发送中...';

      fetch('send_code.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'email=' + encodeURIComponent(email) + '&type=login'
      })
      .then(res => res.text())
      .then(msg => {
        if (msg === '验证码已发送') {
          showStatus(msg, 'success');
          startCountdown();
        } else {
          showStatus(msg, 'error');
          sendCodeBtn.disabled = false;
          sendCodeBtn.textContent = '发送验证码';
        }
      })
      .catch(err => {
        showStatus('发送失败，请稍后重试', 'error');
        sendCodeBtn.disabled = false;
        sendCodeBtn.textContent = '发送验证码';
      });
    };

    // 显示状态消息
    function showStatus(message, type) {
      statusDiv.textContent = message;
      statusDiv.className = type;
      statusDiv.style.display = 'block';
      setTimeout(() => {
        statusDiv.style.display = 'none';
      }, 3000);
    }

    // 倒计时功能
    function startCountdown() {
      let countdown = 60;
      countdownSpan.textContent = `(${countdown}秒后可重新发送)`;
      const timer = setInterval(() => {
        countdown--;
        countdownSpan.textContent = `(${countdown}秒后可重新发送)`;
        if (countdown <= 0) {
          clearInterval(timer);
          countdownSpan.textContent = '';
          sendCodeBtn.disabled = false;
          sendCodeBtn.textContent = '发送验证码';
        }
      }, 1000);
    }
  </script>
</body>
</html>