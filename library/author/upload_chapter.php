<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'];
$workName = $_GET['work'] ?? '';
$workDir = __DIR__ . "/../book/novel/{$workName}";

// 验证作品所有权
if (!str_starts_with($workName, "{$username}_")) {
    die("无权限操作此作品");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['chapter_file'])) {
    $chapterTitle = trim($_POST['chapter_title']);
    $safeTitle = preg_replace('/[^\p{Han}a-zA-Z0-9_・]/u', '', $chapterTitle);
    $chapterPath = "{$workDir}/chapters/{$safeTitle}.txt";
    
    // 处理文本上传
    if ($_FILES['chapter_file']['type'] === 'text/plain') {
        move_uploaded_file($_FILES['chapter_file']['tmp_name'], $chapterPath);
        header("Location: manage.php?success=1");
        exit();
    } else {
        $error = "请上传TXT格式的文本文件";
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>上传新章节 - <?= $workName ?></h2>
<form method="POST" enctype="multipart/form-data" class="upload-form">
    <div class="form-group">
        <label>章节标题：</label>
        <input type="text" name="chapter_title" required>
    </div>
    <div class="form-group">
        <label>章节内容（TXT文件）：</label>
        <input type="file" name="chapter_file" accept=".txt" required>
    </div>
    <?php if (isset($error)): ?>
    <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">上传章节</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>