<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}
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
  </header>
  <div class="gallery">
    <?php foreach ($images as $index => $img): ?>
      <img src="<?= htmlspecialchars($img) ?>" data-index="<?= $index ?>" class="thumbnail">
    <?php endforeach; ?>
  </div>

  <!-- 弹出查看 -->
  <div id="overlay" style="display: none">
    <span id="close">&times;</span>
    <img id="preview" src="">
    <div class="nav">
      <button id="prev">←</button>
      <button id="next">→</button>
    </div>
  </div>
  <a href="upload.php">上传图片</a>
  <?php if ($_SESSION['user_id'] == 2): ?>
  <a href="/editor.php">编辑</a>
  <?php endif; ?>
  <script>
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
