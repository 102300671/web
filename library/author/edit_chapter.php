<?php
require_once __DIR__ . '/../includes/auth.php';
$workName = $_GET['work'] ?? '';
$chapterName = $_GET['chapter'] ?? '';
$chapterPath = __DIR__ . "/../book/novel/{$workName}/chapters/{$chapterName}";

// 获取文件扩展名
$ext = strtolower(pathinfo($chapterName, PATHINFO_EXTENSION));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 对于DOC和DOCX文件，不直接保存编辑内容
    if (in_array($ext, ['doc', 'docx'])) {
        // 这里可以添加处理逻辑，比如提示用户这些文件格式不适合直接编辑
        $error = "DOC和DOCX文件不适合直接编辑。请先转换为TXT、MD或HTML格式再进行编辑。";
    } else {
        file_put_contents($chapterPath, $_POST['content']);
        header("Location: manage_chapters.php?work={$workName}");
        exit();
    }
}

// 根据文件格式处理内容
if (in_array($ext, ['doc', 'docx'])) {
    // 对于DOC和DOCX文件，不显示实际内容，而是显示提示信息
    $content = "此文件为{$ext}格式，不适合直接编辑。\n建议：1. 将文件转换为TXT、MD或HTML格式后重新上传\n      2. 或下载文件在本地编辑后重新上传";
    $readOnly = true;
} else {
    // 对于其他格式，正常显示内容
    $content = htmlspecialchars(file_get_contents($chapterPath));
    $readOnly = false;
}

$chapterTitle = pathinfo($chapterName, PATHINFO_FILENAME);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>编辑章节 - <?= $chapterTitle ?></h2>
<?php if (isset($error)): ?>
<div class="error"><?= $error ?></div>
<?php endif; ?>
<form method="POST" class="edit-form">
    <textarea name="content" rows="20" class="chapter-editor"<?= $readOnly ? ' readonly' : '' ?>><?= $content ?></textarea>
    <?php if (!$readOnly): ?>
    <button type="submit" class="btn btn-primary">保存修改</button>
    <?php endif; ?>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>