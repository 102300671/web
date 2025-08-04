<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>注册</title>
  <link rel="stylesheet" href="/css/account.css">
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
            <input type="hidden" name="type" value="register">
          <div class="form-group">
            <label for="username">用户名：</label>
            <input type="text" id="username" name="username" required minlength="3" maxlength="20">
            <small>3-20个字符</small>
          </div>
          <div class="form-group">
            <label for="password">密码：</label>
            <div class="password-wrapper">
              <input type="password" id="password" name="password" required minlength="8">
              <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
            </div>
            <small>至少8个字符</small>
          </div>
          <div class="form-group">
            <label for="email">邮箱（可选）：</label>
            <input type="email" id="email" name="email">
          </div>
          <div id="verification-group" style="display: none;">
            <div class="form-group">
              <label for="email_code">验证码：</label>
              <input type="text" id="email_code" name="email_code" maxlength="6">
            </div>
            <button type="button" id="send-code-btn">发送验证码</button>
            <span id="countdown"></span>
          </div>
          <button type="submit">注册</button>
        </form>
        <a href="login.php">已有账号？去登录</a>
    </div>
  </main>
  <script>
    // 显示/隐藏验证码区域
    const emailInput = document.getElementById('email');
    const verificationGroup = document.getElementById('verification-group');
    const emailCodeInput = document.getElementById('email_code');
    const sendCodeBtn = document.getElementById('send-code-btn');
    const countdownSpan = document.getElementById('countdown');
    const statusDiv = document.getElementById('status');

    emailInput.addEventListener('input', function() {
        verificationGroup.style.display = this.value ? 'block' : 'none';
        if (!this.value) {
            emailCodeInput.removeAttribute('required');
        }
    });

    // 发送验证码
    sendCodeBtn.onclick = function() {
        const email = emailInput.value;
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
            body: 'email=' + encodeURIComponent(email)
        })
        .then(res => res.text())
        .then(msg => {
            if (msg === '验证码已发送') {
                showStatus(msg, 'success');
                startCountdown();
                emailCodeInput.setAttribute('required', true);
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
// 密码显示切换功能
function togglePasswordVisibility(inputId) {
  const input = document.getElementById(inputId);
  const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
  input.setAttribute('type', type);
}
  </script>
</body>
</html>