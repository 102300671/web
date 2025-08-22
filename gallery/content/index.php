<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /account/login.php");
  exit();
}

require __DIR__ . '/../account/config.php';

// 查询当前用户的头像
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果没上传过头像，用默认头像
$avatar = $user && !empty($user['avatar']) ? $user['avatar'] : '/account/avatars/default-avatar.png';

// 读取图集
$images = json_decode(file_get_contents('images.json'), true);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>图集预览</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <header>
    <h1>图集</h1>
    <div class="header-actions">
      <a href="upload.php" class="action-button">上传图片</a>

      <?php if ($_SESSION['user_id'] == 2): ?>
      <a href="/editor.php" class="action-button edit-button">编辑</a>
      <?php endif; ?>

      <!-- 头像和下拉菜单 -->
      <div class="avatar-wrapper">
        <img src="<?= htmlspecialchars($avatar) ?>" alt="头像" class="avatar" id="avatar">
        <div class="dropdown-menu" id="dropdownMenu">
          <a href="/account/profile.php">个人资料</a>
          <a href="/account/logout.php">退出登录</a>
        </div>
      </div>
    </div>
  </header>

  <div class="gallery">
    <?php foreach ($images as $index => $img): ?>
    <img src="<?= htmlspecialchars($img) ?>" data-index="<?= $index ?>" class="thumbnail">
    <?php endforeach; ?>
  </div>

  <!-- 弹出查看 -->
  <div id="overlay">
    <span id="close">&times;</span>
    <img id="preview" src="">
    <div class="nav">
      <button id="prev">←</button>
      <button id="next">→</button>
    </div>
  </div>

  <script>
    // 头像点击展开/收起菜单
    const avatar = document.getElementById('avatar');
    const dropdownMenu = document.getElementById('dropdownMenu');

    avatar.addEventListener('click', () => {
      dropdownMenu.style.display =
      dropdownMenu.style.display === 'block' ? 'none': 'block';
    });

    // 点击外部区域收起菜单
    document.addEventListener('click', (e) => {
      if (!avatar.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.style.display = 'none';
      }
    });
    const thumbnails = document.querySelectorAll('.thumbnail');
    const overlay = document.getElementById('overlay');
    const preview = document.getElementById('preview');
    const close = document.getElementById('close');
    const next = document.getElementById('next');
    const prev = document.getElementById('prev');

    let currentIndex = 0;
    const images = <?= json_encode($images) ?>;

    thumbnails.forEach((img, idx) => {
      img.addEventListener('click', () => {
        currentIndex = idx;
        preview.src = images[currentIndex];
        overlay.style.display = 'flex';
      });
    });

    close.onclick = () => overlay.style.display = 'none';
    next.onclick = () => {
      currentIndex = (currentIndex + 1) % images.length;
      preview.src = images[currentIndex];
    };
    prev.onclick = () => {
      currentIndex = (currentIndex - 1 + images.length) % images.length;
      preview.src = images[currentIndex];
    };
  </script>
</body>
</html>