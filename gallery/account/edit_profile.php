<?php
session_start();
require 'config.php';

define('LOG_FILE', __DIR__ . '/../logs/edit_profile.log');

// 日志函数
function log_message($message, $level = 'INFO') {
    $time = date('[Y-m-d H:i:s]');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $log = "$time [$level] [$ip] $message";
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $log .= " | UA: $ua";
    }
    file_put_contents(LOG_FILE, $log . PHP_EOL, FILE_APPEND);
}

if (!isset($_SESSION['user_id'])) {
    log_message("未登录用户尝试访问 edit_profile.php", 'WARNING');
    header("Location: /account/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户资料
$stmt = $pdo->prepare("SELECT qq, group_name, avatar, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    log_message("用户不存在 user_id=$user_id", 'ERROR');
    exit("用户不存在");
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qq = trim($_POST['qq']);
    $group_name = trim($_POST['group_name']);
    $avatar_path = $user['avatar'];

    log_message("用户$user_id 提交修改资料请求 (qq=$qq, group_name=$group_name)", 'INFO');

    // === 头像上传处理 ===
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (in_array($ext, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
                $avatar_dir = __DIR__ . "/avatars/";
                if (!is_dir($avatar_dir)) mkdir($avatar_dir, 0777, true);

                $new_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
                $target = $avatar_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $avatar_path = "/account/avatars/" . $new_name;
                    log_message("用户$user_id 上传新头像成功 -> $avatar_path");

                    // 删除旧头像
                    if (!empty($user['avatar']) && $user['avatar'] !== '/account/avatar/default-avatar.png') {
                        $old_file = $_SERVER['DOCUMENT_ROOT'] . $user['avatar'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                            log_message("用户$user_id 删除旧头像 -> $old_file", 'DEBUG');
                        }
                    }
                } else {
                    $message = "头像上传失败。";
                    log_message("用户$user_id 上传头像失败（move_uploaded_file 失败）", 'ERROR');
                }
            } else {
                $message = "头像格式不正确或大小超过2MB。";
                log_message("用户$user_id 上传头像失败（格式/大小不符）", 'WARNING');
            }
        }
    }

    // === 密码修改 ===
    $password_sql = "";
    $params = [$qq, $group_name, $avatar_path, $user_id];

    if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_password, $user['password'])) {
            $message = "当前密码不正确。";
            log_message("用户$user_id 修改密码失败（当前密码错误）", 'NOTICE');
        } elseif (strlen($new_password) < 8) {
            $message = "新密码至少8个字符。";
            log_message("用户$user_id 修改密码失败（新密码过短）", 'NOTICE');
        } elseif ($new_password !== $confirm_password) {
            $message = "两次输入的新密码不一致。";
            log_message("用户$user_id 修改密码失败（确认密码不一致）", 'NOTICE');
        } else {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = ", password = ?";
            $params = [$qq, $group_name, $avatar_path, $hashedPassword, $user_id];
            log_message("用户$user_id 修改密码成功", 'INFO');
        }
    }

    // === 更新数据库 ===
    if (empty($message)) {
        $sql = "UPDATE users SET qq = ?, group_name = ?, avatar = ? $password_sql WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $message = "资料已更新成功！";
            log_message("用户$user_id 资料更新成功 (qq=$qq, group_name=$group_name)", 'INFO');

            // 更新本地数据
            $user['qq'] = $qq;
            $user['group_name'] = $group_name;
            $user['avatar'] = $avatar_path;
            if (!empty($hashedPassword)) {
                $user['password'] = $hashedPassword;
            }
        } else {
            $message = "更新失败，请重试。";
            log_message("用户$user_id 资料更新失败（SQL执行失败）", 'ERROR');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>修改资料</title>
  <link rel="stylesheet" href="/assets/css/account.css">
</head>
<body>
  <header>
    <h1>修改资料</h1>
    <nav>
      <a href="/content/index.php">主页</a>
    </nav>
  </header>

  <main>
    <div class="container">
      <?php if (!empty($message)): ?>
      <div class="<?= strpos($message, '成功') !== false ? 'success-message' : 'error-message' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
      <?php endif; ?>

      <form id="editForm" method="post" enctype="multipart/form-data">
        <!-- QQ号 -->
        <div class="form-group">
          <label for="qq">QQ号：</label>
          <input type="text" id="qq" name="qq" required
          value="<?= htmlspecialchars($user['qq']) ?>">
        </div>

        <!-- 群内名称 -->
        <div class="form-group">
          <label for="group_name">群内昵称：</label>
          <input type="text" id="group_name" name="group_name" required maxlength="20"
          value="<?= htmlspecialchars($user['group_name']) ?>">
        </div>

        <!-- 头像上传 -->
        <div class="form-group">
          <label for="avatar">头像：</label>
          <input type="file" id="avatar" name="avatar" accept="image/*">
          <div class="avatar-preview">
            <img id="avatarPreview" class="avatar-circle"
            src="<?= htmlspecialchars($user['avatar'] ?: '/account/avatar/default-avatar.png') ?>"
            alt="头像预览">
          </div>
        </div>

        <!-- 修改密码 -->
        <h3>修改密码（可选）</h3>
        <div class="form-group">
          <label for="current_password">当前密码：</label>
          <input type="password" id="current_password" name="current_password">
        </div>
        <div class="form-group">
          <label for="new_password">新密码：</label>
          <input type="password" id="new_password" name="new_password" minlength="8">
        </div>
        <div class="form-group">
          <label for="confirm_password">确认新密码：</label>
          <input type="password" id="confirm_password" name="confirm_password" minlength="8">
        </div>

        <button type="submit">保存修改</button>
      </form>
      <a href="profile.php">返回个人资料</a>
    </div>
  </main>

  <footer>
    &copy; <?= date('Y') ?> 依依家的猫窝
  </footer>

  <script>
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const editForm = document.getElementById('editForm');

    // 原始值
    const originalQQ = "<?= htmlspecialchars($user['qq']) ?>";
    const originalGroupName = "<?= htmlspecialchars($user['group_name']) ?>";
    const originalAvatar = "<?= htmlspecialchars($user['avatar'] ?: '/account/avatar/default-avatar.png') ?>";

    // 头像预览
    avatarInput.addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          avatarPreview.src = e.target.result;
          avatarPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });

    // 提交前确认修改内容
    editForm.addEventListener('submit', function(event) {
      event.preventDefault(); // 阻止默认提交

      const qq = document.getElementById('qq').value.trim();
      const groupName = document.getElementById('group_name').value.trim();
      const avatarChanged = avatarInput.files.length > 0;
      const newPassword = document.getElementById('new_password').value.trim();
      const confirmPassword = document.getElementById('confirm_password').value.trim();

      let changes = [];

      if (qq !== originalQQ) {
        changes.push(`QQ号：原 ${originalQQ} → 修改为 ${qq}`);
      }
      if (groupName !== originalGroupName) {
        changes.push(`群内昵称：原 ${originalGroupName} → 修改为 ${groupName}`);
      }
      if (avatarChanged) {
        changes.push("头像：已更换");
      }
      if (newPassword || confirmPassword) {
        changes.push("密码：已尝试修改");
      }

      if (changes.length === 0) {
        if (!confirm("您未修改任何内容，是否仍要提交？")) return;
      } else {
        const summary = "请确认以下修改内容：\n\n" + changes.join("\n");
        if (!confirm(summary)) return;
      }

      editForm.submit();
    });
  </script>
</body>
</html>