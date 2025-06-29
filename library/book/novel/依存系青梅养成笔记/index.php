<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}
?>
<?php
// 配置
$linesPerPage = 40;
$chapterDir = __DIR__ . '/chapters';

// 自动扫描章节文件
$files = glob($chapterDir . '/*.txt');
if (!$files) {
    die('没有找到章节文件');
}

// 按文件名排序
natcasesort($files);
$files = array_values($files);

// 生成章节标题（去掉路径和扩展名）
$chapterTitles = array_map(function($f) {
    return htmlspecialchars(basename($f, '.txt'));
}, $files);

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
function generateNavigation($chapterIndex, $totalChapters, $page, $totalPages) {
    $html = '<div class="nav">';
    
    // 上一章
    if ($chapterIndex > 0) {
        $html .= '<a href="?chapter=' . ($chapterIndex - 1) . '&page=1">&laquo; 上一章</a>';
    }
    
    // 上一页
    if ($page > 1) {
        $html .= '<a href="?chapter=' . $chapterIndex . '&page=' . ($page - 1) . '">&lt; 上一页</a>';
    }
    
    $html .= '<strong>第 ' . $page . ' / ' . $totalPages . ' 页</strong>';
    
    // 下一页
    if ($page < $totalPages) {
        $html .= '<a href="?chapter=' . $chapterIndex . '&page=' . ($page + 1) . '">下一页 &gt;</a>';
    }
    
    // 下一章
    if ($chapterIndex < $totalChapters - 1) {
        $html .= '<a href="?chapter=' . ($chapterIndex + 1) . '&page=1">下一章 &raquo;</a>';
    }
    
    $html .= '<a href="intro.php">首页</a>';
    $html .= '</div>';
    
    return $html;
}
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
        <h1>依存系青梅养成笔记</h1>
    </header>
    <main>
        <h2><?php echo $chapterTitle; ?></h2>
        <?php echo generateNavigation($chapterIndex, count($files), $page, $totalPages); ?>
        <pre><?php echo implode("\n", $currentLines); ?></pre>
        <?php echo generateNavigation($chapterIndex, count($files), $page, $totalPages); ?>
        <div class="directory">
            <strong>目录：</strong>
            <?php foreach ($chapterTitles as $i => $title): ?>
                <a href="?chapter=<?php echo $i; ?>&page=1"><?php echo $title; ?></a>
            <?php endforeach; ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 依依家的猫窝</p>
    </footer>
</body>
</html>