<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: /account/login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// 获取用户资料
$stmt = $pdo->prepare("SELECT qq, group_name, avatar, password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  exit("用户不存在");
}

$message = "";

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $qq = trim($_POST['qq']);
  $group_name = trim($_POST['group_name']);
  $avatar_path = $user['avatar'];

  // 头像上传处理
  if (!empty($_FILES['avatar']['name'])) {
    $file = $_FILES['avatar'];
    if ($file['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg',
        'jpeg',
        'png',
        'gif'];
      if (in_array($ext, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
        $upload_dir = __DIR__ . "/../uploads/avatars/";
        if (!is_dir($upload_dir)) {
          mkdir($upload_dir, 0777, true);
        }
        $new_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
        $target = $upload_dir . $new_name;
        if (move_uploaded_file($file['tmp_name'], $target)) {
          $avatar_path = "/uploads/avatars/" . $new_name;
        }
      } else {
        $message = "头像格式不正确或大小超过2MB。";
      }
    }
  }

  // 如果填写了密码，验证后更新
  $password_sql = "";
  $params = [$qq,
    $group_name,
    $avatar_path,
    $user_id];

  if (!empty($_POST['current_password']) || !empty($_POST['new_password']) || !empty($_POST['confirm_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!password_verify($current_password, $user['password'])) {
      $message = "当前密码不正确。";
    } elseif (strlen($new_password) < 8) {
      $message = "新密码至少8个字符。";
    } elseif ($new_password !== $confirm_password) {
      $message = "两次输入的新密码不一致。";
    } else {
      $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
      $password_sql = ", password = ?";
      $params = [$qq,
        $group_name,
        $avatar_path,
        $hashedPassword,
        $user_id];
    }
  }

  if (empty($message)) {
    // 更新数据库
    $sql = "UPDATE users SET qq = ?, group_name = ?, avatar = ? $password_sql WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
      $message = "资料已更新成功！";
      // 更新本地数据
      $user['qq'] = $qq;
      $user['group_name'] = $group_name;
      $user['avatar'] = $avatar_path;
      if (!empty($hashedPassword)) {
        $user['password'] = $hashedPassword;
      }
    } else {
      $message = "更新失败，请重试。";
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

      <form method="post" enctype="multipart/form-data">
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
            src="<?= htmlspecialchars($user['avatar'] ?: '/assets/images/default-avatar.png') ?>"
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
  </script>
</body>
</html>