<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
// 配置  
$linesPerPage = 40;  
$chapterDir = __DIR__ . '/chapters';  

require_once ROOT_DIR . '/includes/chapter.php';
  
// 获取当前章节和页码（支持记忆）  
// 使用作品特定的Cookie名称，避免不同作品间的进度冲突
$cookiePrefix = 'root_姐妹洗澡_';

if (isset($_GET['chapter'])) {  
    $chapterIndex = intval($_GET['chapter']);  
    setcookie($cookiePrefix . 'last_chapter', $chapterIndex, time() + 3600*24*30);  
} elseif (isset($_COOKIE[$cookiePrefix . 'last_chapter'])) {  
    $chapterIndex = intval($_COOKIE[$cookiePrefix . 'last_chapter']);  
} else {  
    $chapterIndex = 0;  
}  

if (isset($_GET['page'])) {  
    $page = intval($_GET['page']);  
    setcookie($cookiePrefix . 'last_page', $page, time() + 3600*24*30);  
} elseif (isset($_COOKIE[$cookiePrefix . 'last_page'])) {  
    $page = intval($_COOKIE[$cookiePrefix . 'last_page']);  
} else {  
    $page = 1;  
}  

// 边界检查  
if ($chapterIndex < 0) $chapterIndex = 0;  
if ($chapterIndex >= count($files)) $chapterIndex = count($files) - 1;  

// 当前章节文件  
$currentFile = !empty($files) ? $files[$chapterIndex] : null;  

// 读取文件  
$lines = [];
$totalLines = 0;
if ($currentFile && file_exists($currentFile)) {
    $lines = file($currentFile, FILE_IGNORE_NEW_LINES);  
    $totalLines = count($lines);  
}
$totalPages = max(1, ceil($totalLines / $linesPerPage));  

// 页码检查  
if ($page < 1) $page = 1;  
if ($page > $totalPages) $page = $totalPages;  

// 当前页内容  
$startLine = ($page - 1) * $linesPerPage;  
$currentLines = array_slice($lines, $startLine, $linesPerPage);  
$currentLines = array_map('htmlspecialchars', $currentLines);  

// 当前章节标题  
$chapterTitle = !empty($chapterTitles) ? $chapterTitles[$chapterIndex] : '暂无章节';  

/**  
 * 生成导航栏HTML  
 */  
require_once ROOT_DIR . '/includes/navigation.php';
?>  

<!DOCTYPE html>  
<html>  
<head>  
    <meta charset="UTF-8">  
    <title><?= htmlspecialchars($chapterTitle) ?> - <?= htmlspecialchars('姐妹洗澡') ?></title>  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <link rel="stylesheet" href="/css/style.css">  
</head>  
<body>  
    <header>  
        <h1><?= htmlspecialchars('姐妹洗澡') ?></h1>  
    </header>  
    <main>  
        <?php if (!empty($files)): ?>
            <h2><?= htmlspecialchars($chapterTitle) ?></h2>  
            <?= generateNavigation($chapterIndex, count($files), $page, $totalPages) ?>  
            <pre><?= implode("
", $currentLines) ?></pre>  
            <?= generateNavigation($chapterIndex, count($files), $page, $totalPages) ?>  
        <?php else: ?>
            <div class='no-content'>
                <p>该作品尚未上传任何章节</p>
                <?php if ('root' === $_SESSION['username']): ?>
                    <p><a href='/author/upload_chapter.php?work=<?= "root_姐妹洗澡" ?>'>上传第一章</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="directory">  
            <?php require_once ROOT_DIR . '/includes/directory.php'; ?>
        </div>  
    </main>  
    <?php require_once ROOT_DIR . '/includes/footer.php'; ?>
</body>  
</html>