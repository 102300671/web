<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'];
$workName = $_GET['work'] ?? '';
$workDir = __DIR__ . "/../book/novel/{$workName}";

// 验证作品所有权
if (!str_starts_with($workName, "{$username}_")) {
    die("无权限操作此作品");
}

// 支持的文件格式
$supportedFormats = ['txt', 'md', 'html', 'doc', 'docx'];
$allowedMimeTypes = ['text/plain', 'text/markdown', 'text/html', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['chapter_file'])) {
    $chapterTitle = trim($_POST['chapter_title']);
    $safeTitle = preg_replace('/[^\p{Han}a-zA-Z0-9_・]/u', '', $chapterTitle);
    
    // 获取文件扩展名
    $fileExt = strtolower(pathinfo($_FILES['chapter_file']['name'], PATHINFO_EXTENSION));
    
    // 验证文件格式
    if (!in_array($fileExt, $supportedFormats) || !in_array($_FILES['chapter_file']['type'], $allowedMimeTypes)) {
        $error = "请上传支持的文件格式: ".implode(", ", $supportedFormats);
    } else {
        // 保存文件时保留原扩展名
        $chapterPath = "{$workDir}/chapters/{$safeTitle}.{$fileExt}";
        
        // 处理文件上传
        if (move_uploaded_file($_FILES['chapter_file']['tmp_name'], $chapterPath)) {
            header("Location: manage.php?success=1");
            exit();
        } else {
            $error = "文件上传失败，请重试";
        }
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
        <label>章节内容（支持格式：TXT, MD, HTML, DOC, DOCX）：</label>
        <input type="file" name="chapter_file" accept=".txt,.md,.html,.doc,.docx" required>
    </div>
    <?php if (isset($error)): ?>
    <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">上传章节</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>