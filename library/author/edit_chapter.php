<?php
require_once __DIR__ . '/../includes/auth.php';
$workName = $_GET['work'] ?? '';
$chapterName = $_GET['chapter'] ?? '';
$chapterPath = __DIR__ . "/../book/novel/{$workName}/chapters/{$chapterName}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents($chapterPath, $_POST['content']);
    header("Location: manage_chapters.php?work={$workName}");
    exit();
}

$content = htmlspecialchars(file_get_contents($chapterPath));
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>编辑章节 - <?= basename($chapterName, '.txt') ?></h2>
<form method="POST" class="edit-form">
    <textarea name="content" rows="20" class="chapter-editor"><?= $content ?></textarea>
    <button type="submit" class="btn btn-primary">保存修改</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>