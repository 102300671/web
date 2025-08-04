<?php
require_once __DIR__ . '/../includes/auth.php';
$workName = $_GET['work'] ?? '';
$workDir = __DIR__ . "/../book/novel/{$workName}";
$chapterDir = "{$workDir}/chapters";
$chapters = glob("{$chapterDir}/*.txt");

// 处理章节删除
if (isset($_GET['delete_chapter'])) {
    $chapter = basename($_GET['delete_chapter']);
    unlink("{$chapterDir}/{$chapter}");
    header("Location: manage_chapters.php?work={$workName}");
    exit();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>章节管理 - <?= $workName ?></h2>
<a href="upload_chapter.php?work=<?= $workName ?>" class="btn btn-secondary">+ 新增章节</a>

<div class="chapters-list">
    <?php foreach ($chapters as $chapter): ?>
    <div class="chapter-item">
        <span><?= basename($chapter, '.txt') ?></span>
        <div class="chapter-actions">
            <a href="edit_chapter.php?work=<?= $workName ?>&chapter=<?= basename($chapter) ?>">编辑</a>
            <a href="?work=<?= $workName ?>&delete_chapter=<?= basename($chapter) ?>" onclick="return confirm('确定删除？')">删除</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>