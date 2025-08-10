<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
// 配置
$linesPerPage = 40;
$chapterDir = __DIR__ . '/chapters';

require_once ROOT_DIR . '/includes/chapter.php';

// 获取当前章节和页码（支持记忆）
if (isset($_GET['chapter'])) {
  $chapterIndex = intval($_GET['chapter']);
  setcookie('last_chapter', $chapterIndex, time() + 3600*24*30);
} elseif (isset($_COOKIE['last_chapter'])) {
  $chapterIndex = intval($_COOKIE['last_chapter']);
} else {
  $chapterIndex = 0;
}

if (isset($_GET['page'])) {
  $page = intval($_GET['page']);
  setcookie('last_page', $page, time() + 3600*24*30);
} elseif (isset($_COOKIE['last_page'])) {
  $page = intval($_COOKIE['last_page']);
} else {
  $page = 1;
}

// 边界检查
if ($chapterIndex < 0) $chapterIndex = 0;
if ($chapterIndex >= count($files)) $chapterIndex = count($files) - 1;

// 当前章节文件
$currentFile = $files[$chapterIndex];

// 读取文件
$lines = file($currentFile, FILE_IGNORE_NEW_LINES);
$totalLines = count($lines);
$totalPages = max(1, ceil($totalLines / $linesPerPage));

// 页码检查
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;

// 当前页内容
$startLine = ($page - 1) * $linesPerPage;
$currentLines = array_slice($lines, $startLine, $linesPerPage);
$currentLines = array_map('htmlspecialchars', $currentLines);

// 当前章节标题
$chapterTitle = $chapterTitles[$chapterIndex];

/**
* 生成导航栏HTML
*
* @param int $chapterIndex 当前章节索引
* @param int $totalChapters 总章节数
* @param int $page 当前页码
* @param int $totalPages 当前章节总页数
* @return string 导航栏HTML代码
*/
require_once ROOT_DIR . '/includes/navigation.php';
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo $chapterTitle; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>
  <header>
    <div class="logo" onclick="location.href='/book'">
      依依家的猫窝
    </div>
    <nav>
      <a href="/book">主页</a>
    </nav>
    <h1>依存系青梅养成笔记</h1>
  </header>
  <main>
    <h2><?php echo $chapterTitle; ?></h2>
    <?php echo generateNavigation($chapterIndex, count($files), $page, $totalPages); ?>
    <pre><?php echo implode("\n", $currentLines); ?></pre>
    <?php echo generateNavigation($chapterIndex, count($files), $page, $totalPages); ?>
    <div class="directory">
      <?php require_once ROOT_DIR . '/includes/directory.php'; ?>
    </div>
  </main>
  <?php include ROOT_DIR . '/includes/footer.php'; ?>
</body>
</html>