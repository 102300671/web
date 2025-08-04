<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workTitle = trim($_POST['work_title']);
    $description = trim($_POST['description']);
    $safeTitle = preg_replace('/[^\p{Han}a-zA-Z0-9_・]/u', '', $workTitle);
    $workDir = __DIR__ . "/../book/novel/{$username}_{$safeTitle}";
    
    // 检查目录是否已存在
    if (file_exists($workDir)) {
        $error = "作品已存在，请更换标题";
    } else {
        // 创建作品目录和章节目录
        if (mkdir($workDir, 0755, true) && mkdir($workDir . '/chapters', 0755, true)) {
            // 创建简介文件
            $introContent = "<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
\$pageTitle = '作品简介';
\$workTitle = '{$workTitle}';
\$author = '{$username}';
\$description = <<<EOD
{$description}
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

            file_put_contents($workDir . '/intro.php', $introContent);
            
            // 生成与现有格式一致的index.php内容
            $indexContent = "<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
// 配置  
\$linesPerPage = 40;  
\$chapterDir = __DIR__ . '/chapters';  

require_once ROOT_DIR . '/includes/chapter.php';
  
// 获取当前章节和页码（支持记忆）  
// 使用作品特定的Cookie名称，避免不同作品间的进度冲突
\$cookiePrefix = '{$username}_{$safeTitle}_';

if (isset(\$_GET['chapter'])) {  
    \$chapterIndex = intval(\$_GET['chapter']);  
    setcookie(\$cookiePrefix . 'last_chapter', \$chapterIndex, time() + 3600*24*30);  
} elseif (isset(\$_COOKIE[\$cookiePrefix . 'last_chapter'])) {  
    \$chapterIndex = intval(\$_COOKIE[\$cookiePrefix . 'last_chapter']);  
} else {  
    \$chapterIndex = 0;  
}  

if (isset(\$_GET['page'])) {  
    \$page = intval(\$_GET['page']);  
    setcookie(\$cookiePrefix . 'last_page', \$page, time() + 3600*24*30);  
} elseif (isset(\$_COOKIE[\$cookiePrefix . 'last_page'])) {  
    \$page = intval(\$_COOKIE[\$cookiePrefix . 'last_page']);  
} else {  
    \$page = 1;  
}  

// 边界检查  
if (\$chapterIndex < 0) \$chapterIndex = 0;  
if (\$chapterIndex >= count(\$files)) \$chapterIndex = count(\$files) - 1;  

// 当前章节文件  
\$currentFile = !empty(\$files) ? \$files[\$chapterIndex] : null;  

// 读取文件  
\$lines = [];
\$totalLines = 0;
if (\$currentFile && file_exists(\$currentFile)) {
    \$lines = file(\$currentFile, FILE_IGNORE_NEW_LINES);  
    \$totalLines = count(\$lines);  
}
\$totalPages = max(1, ceil(\$totalLines / \$linesPerPage));  

// 页码检查  
if (\$page < 1) \$page = 1;  
if (\$page > \$totalPages) \$page = \$totalPages;  

// 当前页内容  
\$startLine = (\$page - 1) * \$linesPerPage;  
\$currentLines = array_slice(\$lines, \$startLine, \$linesPerPage);  
\$currentLines = array_map('htmlspecialchars', \$currentLines);  

// 当前章节标题  
\$chapterTitle = !empty(\$chapterTitles) ? \$chapterTitles[\$chapterIndex] : '暂无章节';  

/**  
 * 生成导航栏HTML  
 */  
require_once ROOT_DIR . '/includes/navigation.php';
?>  

<!DOCTYPE html>  
<html>  
<head>  
    <meta charset=\"UTF-8\">  
    <title><?= htmlspecialchars(\$chapterTitle) ?> - <?= htmlspecialchars('{$workTitle}') ?></title>  
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">  
    <link rel=\"stylesheet\" href=\"/css/style.css\">  
</head>  
<body>  
    <header>  
        <h1><?= htmlspecialchars('{$workTitle}') ?></h1>  
    </header>  
    <main>  
        <?php if (!empty(\$files)): ?>
            <h2><?= htmlspecialchars(\$chapterTitle) ?></h2>  
            <?= generateNavigation(\$chapterIndex, count(\$files), \$page, \$totalPages) ?>  
            <pre><?= implode(\"\n\", \$currentLines) ?></pre>  
            <?= generateNavigation(\$chapterIndex, count(\$files), \$page, \$totalPages) ?>  
        <?php else: ?>
            <div class='no-content'>
                <p>该作品尚未上传任何章节</p>
                <?php if ('{$username}' === \$_SESSION['username']): ?>
                    <p><a href='/author/upload_chapter.php?work=<?= \"{$username}_{$safeTitle}\" ?>'>上传第一章</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class=\"directory\">  
            <?php require_once ROOT_DIR . '/includes/directory.php'; ?>
        </div>  
    </main>  
    <?php require_once ROOT_DIR . '/includes/footer.php'; ?>
</body>  
</html>";

            // 写入阅读页文件
            file_put_contents($workDir . '/index.php', $indexContent);
            
            header("Location: manage.php?success=1");
            exit();
        } else {
            $error = "创建目录失败，请检查权限";
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>创建新作品</h2>
<?php if (isset($error)): ?>
    <div class="alert error"><?= $error ?></div>
<?php endif; ?>
<form method="POST" class="work-form">
    <div class="form-group">
        <label>作品标题：</label>
        <input type="text" name="work_title" required>
    </div>
    <div class="form-group">
        <label>作品简介：</label>
        <textarea name="description" rows="6" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">创建作品</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>