<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'];
// 获取用户的作品列表
$userWorks = glob(__DIR__ . "/../book/novel/{$username}_*");
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<h2>我的作品管理</h2>

<!-- 创建新作品按钮 -->
<a href="create_work.php" class="btn btn-primary">+ 创建新作品</a>

<!-- 作品列表 -->
<div class="work-list">
    <?php foreach ($userWorks as $work): ?>
    <div class="work-item">
        <h3><?= basename($work) ?></h3>
        <a href="edit_work.php?work=<?= basename($work) ?>">编辑作品信息</a>
        <a href="upload_chapter.php?work=<?= basename($work) ?>">上传新章节</a>
        <a href="manage_chapters.php?work=<?= basename($work) ?>">管理章节</a>
        <a href="delete_work.php?work=<?= basename($work) ?>" class="btn btn-sm btn-danger" 
               onclick="return confirm('确定要删除这个作品吗？所有章节也将被删除，此操作不可恢复！')">删除作品</a>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($userWorks)): ?>
    <p class="no-works">您还没有创建任何作品，点击"创建新作品"开始您的创作之旅吧！</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>