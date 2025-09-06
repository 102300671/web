<?php
require_once __DIR__ . '/../includes/auth.php';

$username = $_SESSION['username'];
$workName = $_GET['work'] ?? '';
$workDir  = __DIR__ . "/../book/novel/{$workName}";

// 权限验证
if (!str_starts_with($workName, "{$username}_")) {
    die("无权操作此作品");
}

// 原始标题
$originalTitle = preg_replace("/^{$username}_/", '', $workName);

// 检查 intro.php
$introFile = $workDir . '/intro.php';
if (!file_exists($introFile)) {
    die("作品信息文件不存在");
}

// 解析 intro.php 获取原信息
ob_start();
include $introFile;
ob_end_clean(); // intro.php 应该定义 $workTitle, $description
$coverPath = $workDir . '/cover.jpg';

// 处理表单
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newTitle       = trim($_POST['work_title']);
    $newDescription = trim($_POST['description']);
    $safeNewTitle   = preg_replace('/[^\p{Han}a-zA-Z0-9_・]/u', '', $newTitle);

    if ($safeNewTitle === '') {
        die("标题不能为空或仅含非法字符");
    }

    $newWorkDir = __DIR__ . "/../book/novel/{$username}_{$safeNewTitle}";

    // 标题修改时重命名目录
    if ($safeNewTitle !== $originalTitle) {
        if (!rename($workDir, $newWorkDir)) {
            die("重命名作品目录失败");
        }
        $workDir  = $newWorkDir;
        $workName = "{$username}_{$safeNewTitle}";
    }

    // 更新封面
    if (!empty($_FILES['cover']['tmp_name'])) {
        $coverTmp  = $_FILES['cover']['tmp_name'];
        $coverDst  = $workDir . '/cover.jpg';
        $mimeType  = mime_content_type($coverTmp);
        $allowed   = ['image/jpeg', 'image/png'];

        if (in_array($mimeType, $allowed)) {
            move_uploaded_file($coverTmp, $coverDst);
        } else {
            $error = "封面格式仅支持 JPG 和 PNG";
        }
    }

    if (!isset($error)) {
        // 写入新的 intro.php
        $updatedIntro = <<<PHP
<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';

\$pageTitle  = '作品简介';
\$workTitle  = '{$safeNewTitle}';
\$author     = '{$username}';
\$description = <<<EOD
{$newDescription}
EOD;

\$chapterDir = __DIR__ . '/chapters';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>
<h1>作品标题：<?= htmlspecialchars(\$workTitle) ?></h1>
<p><strong>作者：</strong><?= htmlspecialchars(\$author) ?></p>
<div class='description'><?= nl2br(htmlspecialchars(\$description)) ?></div>
<div class='start'><a href='index.php'>📖 开始阅读</a></div>

<?php
// 检查是否存在支持的章节文件
\$supportedFormats = ['txt', 'md', 'html', 'doc', 'docx'];
\$hasChapters = false;
if (is_dir(\$chapterDir)) {
    foreach (\$supportedFormats as \$format) {
        if (count(glob(\$chapterDir . "/*." . \$format)) > 0) {
            \$hasChapters = true;
            break;
        }
    }
}
if (\$hasChapters) {
    include ROOT_DIR . '/includes/chapter.php';
    include ROOT_DIR . '/includes/directory.php';
} else {
    echo "<p>暂无章节内容</p>";
}
?>
<?php include ROOT_DIR . '/includes/footer.php'; ?>
PHP;

        file_put_contents($workDir . '/intro.php', $updatedIntro);
        header("Location: manage.php?success=1");
        exit();
    }
}
?>

<?php include ROOT_DIR . '/includes/header.php'; ?>
<h2>编辑作品信息</h2>

<form method="POST" class="work-form" enctype="multipart/form-data">
  <div class="form-group">
    <label>作品标题：</label>
    <input type="text" name="work_title" value="<?= htmlspecialchars($workTitle) ?>" required>
  </div>

  <div class="form-group">
    <label>作品简介：</label>
    <textarea name="description" rows="6" required><?= htmlspecialchars($description) ?></textarea>
  </div>

  <div class="form-group">
    <label>当前封面：</label><br>
    <?php if (file_exists($coverPath)): ?>
      <img src="/book/novel/<?= htmlspecialchars($workName) ?>/cover.jpg" alt="封面" style="max-width:200px;max-height:300px;border:1px solid #ccc;border-radius:4px;">
    <?php else: ?>
      <p>暂无封面</p>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>更新封面（JPG/PNG，选填）：</label>
    <input type="file" name="cover" accept=".jpg,.jpeg,.png">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">保存修改</button>
    <a href="manage.php" class="btn btn-secondary">取消</a>
  </div>
</form>

<?php include ROOT_DIR . '/includes/footer.php'; ?>