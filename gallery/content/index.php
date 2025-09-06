<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /account/login.php");
  exit();
}

require __DIR__ . '/../account/config.php';

// 查询头像
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$avatar = $user && !empty($user['avatar']) ? $user['avatar'] : '/account/avatars/default-avatar.png';

// 读取数据
$images = json_decode(file_get_contents('images.json'), true) ?: [];
$videos = json_decode(file_get_contents('videos.json'), true) ?: [];
$iframes = json_decode(file_get_contents('iframes.json'), true) ?: [];

// 判断类型
function getCategory($filename) {
  $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
  return $ext === 'gif' ? 'gif' : 'image';
}

// 构建 gallery 数组
$galleryItems = [];
foreach ($images as $img) $galleryItems[] = ['src'=>$img,'type'=>getCategory($img)];
foreach ($videos as $vid) $galleryItems[] = ['src'=>$vid,'type'=>'video'];
foreach ($iframes as $ifr) $galleryItems[] = ['src'=>$ifr,'type'=>'iframe'];
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
      <a href="upload.php" class="action-button">上传媒体</a>
      <?php if ($_SESSION['qq'] == 2193807541): ?>
      <a href="/editor.php" class="action-button edit-button">编辑</a>
      <?php endif; ?>
      <div class="avatar-wrapper">
        <img src="<?=htmlspecialchars($avatar) ?>" alt="头像" class="avatar" id="avatar">
        <div class="dropdown-menu" id="dropdownMenu">
          <a href="/account/profile.php">个人资料</a>
          <a href="/account/logout.php">退出登录</a>
        </div>
      </div>
    </div>
  </header>

  <!-- 分类栏 -->
  <div class="filter-bar">
    <button class="filter-button active" data-type="all">全部</button>
    <button class="filter-button" data-type="image">图片</button>
    <button class="filter-button" data-type="gif">动图</button>
    <button class="filter-button" data-type="video">视频</button>
    <button class="filter-button" data-type="iframe">iframe</button>
  </div>

  <!-- 图集 -->
  <div class="gallery">
    <?php foreach ($galleryItems as $index => $item): ?>
      <?php if ($item['type'] === 'video'): ?>
        <video class="thumbnail" data-index="<?=$index?>" data-type="video" preload="metadata">
          <source src="<?=htmlspecialchars($item['src'])?>" type="video/mp4">
        </video>
      <?php elseif ($item['type'] === 'iframe'): ?>
        <div class="thumbnail iframe-thumb" data-index="<?=$index?>" data-type="iframe">
          <span>▶</span> iframe
        </div>
      <?php else: ?>
        <img class="thumbnail" data-index="<?=$index?>" data-type="<?=$item['type']?>" src="<?=htmlspecialchars($item['src'])?>">
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- 弹窗 -->
  <div id="overlay">
    <span id="close">&times;</span>
    <img id="preview" src="" style="display:none;">
    <video id="previewVideo" controls style="display:none; max-height:80vh; max-width:90vw;"></video>
    <iframe id="previewIframe" style="display:none; max-height:80vh; max-width:90vw;" frameborder="0" allowfullscreen></iframe>
    <div class="nav">
      <button id="prev">←</button>
      <button id="next">→</button>
    </div>
    <button id="fullscreenBtn" style="position:absolute; bottom:20px; right:20px; z-index:1010; padding:10px 15px; font-size:1em; border:none; border-radius:5px; cursor:pointer; background:rgba(255,255,255,0.7);">全屏</button>
  </div>

  <script>
    const avatar = document.getElementById('avatar');
    const dropdownMenu = document.getElementById('dropdownMenu');
    avatar.addEventListener('click', ()=> {
      dropdownMenu.style.display = dropdownMenu.style.display === 'block'?'none': 'block';
    });
    document.addEventListener('click', e=> {
      if (!avatar.contains(e.target)&&!dropdownMenu.contains(e.target)) {
        dropdownMenu.style.display = 'none';
      }});

    const thumbnails = document.querySelectorAll('.thumbnail');
    const overlay = document.getElementById('overlay');
    const preview = document.getElementById('preview');
    const previewVideo = document.getElementById('previewVideo');
    const previewIframe = document.getElementById('previewIframe');
    const close = document.getElementById('close');
    const next = document.getElementById('next');
    const prev = document.getElementById('prev');
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    let currentIndex = 0;
    const galleryItems = <?=json_encode($galleryItems)?>;

    // 点击 gallery 弹窗
    thumbnails.forEach((el, idx)=> {
      el.addEventListener('click', ()=> {
        currentIndex = idx;
        showItem(galleryItems[currentIndex]);
        overlay.style.display = 'flex';
      });
    });

    // 显示项目
    function showItem(item) {
      if (item.type === 'video') {
        preview.style.display = 'none';
        previewIframe.style.display = 'none';
        previewVideo.style.display = 'block';
        previewVideo.src = item.src;
        previewVideo.play();
        fullscreenBtn.style.display = 'inline-block';
      } else if (item.type === 'iframe') {
        preview.style.display = 'none';
        previewVideo.pause();
        previewVideo.style.display = 'none';
        previewVideo.src = '';
        previewIframe.style.display = 'block';
        
        // 检查是否是完整的iframe代码
        if (item.src.includes('<iframe')) {
          // 处理完整的iframe代码
          // 创建一个临时div来解析iframe代码
          const tempDiv = document.createElement('div');
          tempDiv.innerHTML = item.src;
          const iframeElement = tempDiv.querySelector('iframe');
          if (iframeElement) {
            previewIframe.src = iframeElement.src;
            // 复制其他属性
            Array.from(iframeElement.attributes).forEach(attr => {
              if (attr.name !== 'src' && attr.name !== 'id') {
                previewIframe.setAttribute(attr.name, attr.value);
              }
            });
          }
        } else {
          // 只是iframe URL
          previewIframe.src = item.src;
        }
        
        fullscreenBtn.style.display = 'none';
      } else {
        previewVideo.pause();
        previewVideo.style.display = 'none';
        previewVideo.src = '';
        previewIframe.style.display = 'none';
        previewIframe.src = '';
        preview.style.display = 'block';
        preview.src = item.src;
        fullscreenBtn.style.display = 'none';
      }
    }

    // 导航
    next.onclick = ()=> {
      currentIndex = (currentIndex+1)%galleryItems.length;
      showItem(galleryItems[currentIndex]);
    };
    prev.onclick = ()=> {
      currentIndex = (currentIndex-1+galleryItems.length)%galleryItems.length;
      showItem(galleryItems[currentIndex]);
    };

    // 关闭弹窗
    close.onclick = ()=> {
      overlay.style.display = 'none';
      previewVideo.pause();
      previewVideo.src = '';
      previewIframe.src = '';
    };

    // 全屏按钮
    fullscreenBtn.onclick = ()=> {
      if (previewVideo.requestFullscreen) previewVideo.requestFullscreen();
      else if (previewVideo.webkitRequestFullscreen) previewVideo.webkitRequestFullscreen();
      else if (previewVideo.msRequestFullscreen) previewVideo.msRequestFullscreen();
    };

    // 分类筛选
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(btn => {
      btn.addEventListener('click', ()=> {
        filterButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const type = btn.dataset.type;
        thumbnails.forEach(el => {
          if (type === 'all' || el.dataset.type === type) el.style.display = 'inline-block';
          else el.style.display = 'none';
        });
      });
    });
  </script>
</body>
</html>