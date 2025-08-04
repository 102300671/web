<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'];
$workName = $_GET['work'] ?? '';
$workDir = __DIR__ . "/../book/novel/{$workName}";

// 验证权限（确保是作品所有者）
if (!str_starts_with($workName, "{$username}_")) {
    die("无权操作此作品");
}

// 提取原始作品名（去掉用户名前缀）
$originalTitle = preg_replace("/^{$username}_/", '', $workName);

// 读取当前作品信息
$introFile = $workDir . '/intro.php';
if (!file_exists($introFile)) {
    die("作品信息文件不存在");
}

// 解析intro.php获取当前信息
ob_start();
include $introFile;
ob_end_clean();
// 上面的include会生成$workTitle, $description等变量

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newTitle = trim($_POST['work_title']);
    $newDescription = trim($_POST['description']);
    $safeNewTitle = preg_replace('/[^\p{Han}a-zA-Z0-9_・]/u', '', $newTitle);
    $newWorkDir = __DIR__ . "/../novel/{$username}_{$safeNewTitle}";
    
    // 如果标题改变，需要重命名目录
    if ($safeNewTitle !== $originalTitle) {
        rename($workDir, $newWorkDir);
        $workDir = $newWorkDir;
        $workName = "{$username}_{$safeNewTitle}";
    }
    
    // 更新简介文件
    $updatedIntro = "<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
\$pageTitle = '作品简介';
\$workTitle = '{$newTitle}';
\$author = '{$username}';
\$description = <<<EOD
{$newDescription}
EOD;
\$chapterDir = __DIR__ . '/chapters';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>
<h1>作品标题：<?= htmlspecialchars(\$workTitle) ?></h1>
<p><strong>作者：</strong><?= htmlspecialchars(\$author) ?></p>
<div class='description'><p><?= \$description ?></p></div>
<div class='start'><a href='index.php'>📖 开始阅读</a></div>
<?php include ROOT_DIR . '/includes/chapter.php'; ?>
<?php include ROOT_DIR . '/includes/directory.php'; ?>
<?php include ROOT_DIR . '/includes/footer.php'; ?>";

    file_put_contents($workDir . '/intro.php', $updatedIntro);
    
    header("Location: manage.php?success=1");
    exit();
}
?>

<?php include ROOT_DIR . '/includes/header.php'; ?>

<h2>编辑作品信息</h2>

<form method="POST" class="work-form">
    <div class="form-group">
        <label>作品标题：</label>
        <input type="text" name="work_title" value="<?= htmlspecialchars($workTitle) ?>" required>
    </div>
    
    <div class="form-group">
        <label>作品简介：</label>
        <textarea name="description" rows="6" required><?= htmlspecialchars($description) ?></textarea>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">保存修改</button>
        <a href="manage.php" class="btn btn-secondary">取消</a>
    </div>
</form>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
