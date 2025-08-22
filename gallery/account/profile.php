<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: /account/login.php");
  exit();
}

$stmt = $pdo->prepare("SELECT qq, group_name, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
  exit("用户不存在");
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>个人资料</title>
  <link rel="stylesheet" href="/assets/css/account.css">
</head>
<body>
  <header>
    <h1>个人资料</h1>
    <nav>
      <a href="/content/index.php">主页</a>
    </nav>
  </header>
  <main>
    <div class="profile-container">
      <div class="profile-avatar">
        <img src="<?= htmlspecialchars($user['avatar'] ?: '/account/avatars/default-avatar.png') ?>"
        alt="头像" class="avatar-circle" id="profileAvatar">
      </div>

      <div class="profile-info">
        <p>
          <strong>QQ号：</strong><?= htmlspecialchars($user['qq']) ?>
        </p>
        <p>
          <strong>群内昵称：</strong><?= htmlspecialchars($user['group_name']) ?>
        </p>
      </div>

      <div class="profile-actions">
        <a href="edit_profile.php">修改资料</a>
        <a href="/account/logout.php" class="logout">退出登录</a>
        <a href="/account/delete_account.php" class="delete">注销账户</a>
      </div>
    </div>
  </main>

  <footer>
    &copy; <?= date('Y') ?> 依依家的猫窝
  </footer>

  <!-- 头像弹窗 -->
  <div id="avatarModal">
    <span id="closeModal">&times;</span>
    <img id="modalImg" src="">
  </div>

  <script>
    const avatarImg = document.getElementById('profileAvatar');
    const avatarModal = document.getElementById('avatarModal');
    const modalImg = document.getElementById('modalImg');
    const closeModal = document.getElementById('closeModal');

    avatarImg.addEventListener('click', () => {
      avatarModal.style.display = 'flex';
      modalImg.src = avatarImg.src;
    });

    closeModal.addEventListener('click', () => avatarModal.style.display = 'none');
    avatarModal.addEventListener('click', e => {
      if (e.target === avatarModal) avatarModal.style.display = 'none';
    });
  </script>
</body>
</html>