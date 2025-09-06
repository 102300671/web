<?php
require_once __DIR__ . '/../includes/auth.php';

$username = $_SESSION['username'] ?? null;
if (!$username) {
    die('未登录，无法创建作品');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workTitle = trim($_POST['work_title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($workTitle === '') {
        $error = "作品标题不能为空";
    } else {
        // 过滤标题，只保留中文、英文、数字、下划线、・
        $safeTitle = preg_replace('/[^\p{Han}a-zA-Z0-9_・]/u', '', $workTitle);
        if ($safeTitle === '') {
            $error = "作品标题包含非法字符或为空，请修改";
        }
    }

    if (!isset($error)) {
        $workDir = __DIR__ . "/../book/novel/{$username}_{$safeTitle}";

        if (file_exists($workDir)) {
            $error = "作品已存在，请更换标题";
        } else {
            // 先创建作品根目录
            if (!mkdir($workDir, 0755, true)) {
                $error = "创建作品目录失败，请检查权限";
            } else {
                // 再创建章节目录
                if (!mkdir($workDir . '/chapters', 0755, true)) {
                    $error = "创建章节目录失败，请检查权限";
                }
            }
        }
    }

    if (!isset($error)) {
        // 处理封面上传
        if (!empty($_FILES['cover']['tmp_name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $coverTmp = $_FILES['cover']['tmp_name'];
            $coverDst = $workDir . '/cover.jpg';
            $allowedTypes = ['image/jpeg', 'image/png'];

            if (in_array($_FILES['cover']['type'], $allowedTypes)) {
                if (!move_uploaded_file($coverTmp, $coverDst)) {
                    $error = "封面上传失败，请重试";
                }
            } else {
                $error = "封面格式仅支持 JPG 和 PNG";
            }
        }
    }

    if (!isset($error)) {
        // 创建简介文件 intro.php
        $introContent = <<<PHP
<?php
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

<?php
// 检查是否存在支持的章节文件
\$supportedFormats = ['txt', 'md', 'html', 'doc', 'docx'];
\$hasChapters = false;
if (is_dir(\$chapterDir)) {
    foreach (\$supportedFormats as \$format) {
        if (count(glob(\$chapterDir . "/*.". \$format)) > 0) {
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

        if (file_put_contents($workDir . '/intro.php', $introContent) === false) {
            $error = "创建简介文件失败，请检查权限";
        }
    }

    if (!isset($error)) {
        // 创建阅读页 index.php
        $indexContent = <<<PHP
<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';

// 配置
\$linesPerPage = 40;
\$chapterDir = __DIR__ . '/chapters';

require_once ROOT_DIR . '/includes/chapter.php';

// 获取当前章节和页码（支持记忆）
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
if (isset(\$files) && \$chapterIndex >= count(\$files)) \$chapterIndex = count(\$files) - 1;

// 当前章节文件
\$currentFile = !empty(\$files) ? \$files[\$chapterIndex] : null;

// 读取文件
    \$lines = [];
    \$totalLines = 0;
    if (\$currentFile && file_exists(\$currentFile)) {
        \$lines = readChapterContent(\$currentFile);
        \$totalLines = count(\$lines);
    }
\$totalPages = max(1, ceil(\$totalLines / \$linesPerPage));

// 页码检查
if (\$page < 1) \$page = 1;
if (\$page > \$totalPages) \$page = \$totalPages;

// 当前页内容
\$startLine = (\$page - 1) * \$linesPerPage;
\$currentLines = array_slice(\$lines, \$startLine, \$linesPerPage);

// 只对TXT文件应用HTML转义，保留HTML和Markdown的原始格式
\$ext = strtolower(pathinfo(\$currentFile, PATHINFO_EXTENSION));
if (\$ext === 'txt') {
    \$currentLines = array_map('htmlspecialchars', \$currentLines);
}

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
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(\$chapterTitle) ?> - <?= htmlspecialchars('{$workTitle}') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="logo" onclick="location.href='/book'">依依家的猫窝</div>
        <nav><a href="/book">主页</a></nav>
        <h1><?= htmlspecialchars('{$workTitle}') ?></h1>
    </header>
    <main>
        <?php if (!empty(\$files)): ?>
            <h2><?= htmlspecialchars(\$chapterTitle) ?></h2>
            <?= generateNavigation(\$chapterIndex, count(\$files), \$page, \$totalPages) ?>
            <div class="chapter-content">
                <!-- 对于HTML内容，直接输出；对于其他内容，使用<pre>标签保持格式 -->
                <?php 
                    \$ext = strtolower(pathinfo(\$currentFile, PATHINFO_EXTENSION));
                    if (\$ext === 'html') {
                        echo implode("\n", \$currentLines);
                    } else {
                        echo '<pre>' . implode("\n", \$currentLines) . '</pre>';
                    }
                ?>
            </div>
            <?= generateNavigation(\$chapterIndex, count(\$files), \$page, \$totalPages) ?>
        <?php else: ?>
            <div class='no-content'>
                <p>该作品尚未上传任何章节</p>
                <?php if ('{$username}' === \$_SESSION['username']): ?>
                    <p><a href='/author/upload_chapter.php?work=<?= "{$username}_{$safeTitle}" ?>'>上传第一章</a></p>
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
PHP;

        if (file_put_contents($workDir . '/index.php', $indexContent) === false) {
            $error = "创建阅读页文件失败，请检查权限";
        }
    }

    if (!isset($error)) {
        header("Location: manage.php?success=1");
        exit();
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>创建新作品</h2>
<?php if (isset($error)): ?>
<div class="alert error">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="work-form">
    <div class="form-group">
        <label>作品标题：</label>
        <input type="text" name="work_title" required>
    </div>
    <div class="form-group">
        <label>封面上传（JPG/PNG，选填）：</label>
        <input type="file" name="cover" accept=".jpg,.jpeg,.png">
    </div>
    <div class="form-group">
        <label>作品简介：</label>
        <textarea name="description" rows="6" required></textarea>
    </div>
    <button type="submit" class="btn btn-primary">创建作品</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>