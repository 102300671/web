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

// 读取图片/动图图集
$images = json_decode(file_get_contents('images.json'), true);
// 读取视频图集
$videos = json_decode(file_get_contents('videos.json'), true);

// 判断类型
function getCategory($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'gif') return 'gif';
    return 'image';
}

// 构建 gallery 项目数组
$galleryItems = [];
foreach ($images as $img) {
    $galleryItems[] = ['src'=>$img,'type'=>getCategory($img)];
}
foreach ($videos as $vid) {
    $galleryItems[] = ['src'=>$vid,'type'=>'video'];
}
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
        <?php if ($_SESSION['user_id']==2): ?>
        <a href="/editor.php" class="action-button edit-button">编辑</a>
        <?php endif; ?>
        <div class="avatar-wrapper">
            <img src="<?=htmlspecialchars($avatar)?>" alt="头像" class="avatar" id="avatar">
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
</div>

<div class="gallery">
    <?php foreach ($galleryItems as $index=>$item): ?>
        <?php if($item['type']==='video'): ?>
            <video class="thumbnail" data-index="<?=$index?>" data-type="video" preload="metadata" poster="">
                <source src="<?=htmlspecialchars($item['src'])?>" type="video/mp4">
            </video>
        <?php else: ?>
            <img class="thumbnail" data-index="<?=$index?>" data-type="<?=$item['type']?>" src="<?=htmlspecialchars($item['src'])?>">
        <?php endif; ?>
    <?php endforeach; ?>
    <div style="height: 0; padding-bottom: calc(54.05%); position:relative; width: 100%;"><iframe allow="autoplay; gyroscope;" allowfullscreen height="100%" referrerpolicy="strict-origin" src="https://www.kapwing.com/e/68b53f5a69a986521bfc4e91" style="border:0; height:100%; left:0; overflow:hidden; position:absolute; top:0; width:100%" title="Embedded content made on Kapwing" width="100%"></iframe></div>
</div>

<!-- 弹窗 -->
<div id="overlay">
    <span id="close">&times;</span>
    <img id="preview" src="" style="display:none;">
    <video id="previewVideo" controls style="display:none; max-height:80vh; max-width:90vw;"></video>
    <div class="nav">
        <button id="prev">←</button>
        <button id="next">→</button>
    </div>
    <button id="fullscreenBtn" style="position:absolute; bottom:20px; right:20px; z-index:1010; padding:10px 15px; font-size:1em; border:none; border-radius:5px; cursor:pointer; background:rgba(255,255,255,0.7);">全屏</button>
</div>

<script>
const avatar = document.getElementById('avatar');
const dropdownMenu = document.getElementById('dropdownMenu');
avatar.addEventListener('click',()=>{dropdownMenu.style.display = dropdownMenu.style.display==='block'?'none':'block';});
document.addEventListener('click',e=>{if(!avatar.contains(e.target)&&!dropdownMenu.contains(e.target)){dropdownMenu.style.display='none';}});

const thumbnails = document.querySelectorAll('.thumbnail');
const overlay = document.getElementById('overlay');
const preview = document.getElementById('preview');
const previewVideo = document.getElementById('previewVideo');
const close = document.getElementById('close');
const next = document.getElementById('next');
const prev = document.getElementById('prev');
const fullscreenBtn = document.getElementById('fullscreenBtn');

let currentIndex=0;
const galleryItems=<?=json_encode($galleryItems)?>;

// 点击 gallery 弹窗
thumbnails.forEach((el,idx)=>{
    el.addEventListener('click',()=>{
        currentIndex=idx;
        showItem(galleryItems[currentIndex]);
        overlay.style.display='flex';
    });
});

// 显示项目
function showItem(item){
    if(item.type==='video'){
        preview.style.display='none';
        previewVideo.style.display='block';
        previewVideo.src=item.src;
        previewVideo.play();
        fullscreenBtn.style.display='inline-block';
    }else{
        previewVideo.pause();
        previewVideo.style.display='none';
        previewVideo.src='';
        preview.style.display='block';
        preview.src=item.src;
        fullscreenBtn.style.display='none';
    }
}

// 导航
next.onclick=()=>{currentIndex=(currentIndex+1)%galleryItems.length; showItem(galleryItems[currentIndex]);};
prev.onclick=()=>{currentIndex=(currentIndex-1+galleryItems.length)%galleryItems.length; showItem(galleryItems[currentIndex]);};

// 关闭弹窗
close.onclick=()=>{
    overlay.style.display='none';
    previewVideo.pause();
    previewVideo.src='';
};

// 全屏按钮
fullscreenBtn.onclick=()=>{
    if(previewVideo.requestFullscreen) previewVideo.requestFullscreen();
    else if(previewVideo.webkitRequestFullscreen) previewVideo.webkitRequestFullscreen();
    else if(previewVideo.msRequestFullscreen) previewVideo.msRequestFullscreen();
};

// 分类筛选
const filterButtons=document.querySelectorAll('.filter-button');
filterButtons.forEach(btn=>{
    btn.addEventListener('click',()=>{
        filterButtons.forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        const type=btn.dataset.type;
        thumbnails.forEach(el=>{
            if(type==='all'||el.dataset.type===type) el.style.display='inline-block';
            else el.style.display='none';
        });
    });
});
</script>
</body>
</html>