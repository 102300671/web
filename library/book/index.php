<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'] ?? '';  

// 定义小说目录路径
$novelDir = __DIR__ . '/novel';

// 存储所有作品信息的数组
$works = [];

// 扫描小说目录中的所有作品文件夹
if (is_dir($novelDir)) {
    $dirHandle = opendir($novelDir);
    
    while (($folder = readdir($dirHandle)) !== false) {
        // 跳过当前目录(.)和上级目录(..)
        if ($folder === '.' || $folder === '..') {
            continue;
        }
        
        $workPath = $novelDir . '/' . $folder;
        $introFile = $workPath . '/intro.php';
        
        // 检查是否是目录且包含intro.php文件
        if (is_dir($workPath) && file_exists($introFile)) {
            // 提取作者名和作品名（从文件夹名解析）
            $nameParts = explode('_', $folder, 2);
            $author = $nameParts[0] ?? '未知作者';
            $workTitle = $nameParts[1] ?? $folder;
            
            // 存储作品信息
            $works[] = [
                'title' => $workTitle,
                'author' => $author,
                'introUrl' => "novel/{$folder}/intro.php"
            ];
        }
    }
    closedir($dirHandle);
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>依依家的猫窝</title>
    <link rel="stylesheet" href="/css/book.css">
</head>
<body>
    <header>
        <h1>依依家的猫窝</h1>
    </header>
    <main>
        <section>
            <h2>作者神力</h2>
            <ul>
                <?php if (!empty($works)): ?>
                    <?php foreach ($works as $work): ?>
                    <li>
                        <a href="<?= htmlspecialchars($work['introUrl']) ?>">
                            <?= htmlspecialchars($work['title']) ?>
                        </a>
                        <span class="author">作者: <?= htmlspecialchars($work['author']) ?></span>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>暂无作品，敬请期待...</li>
                <?php endif; ?>
                
                <?php if ($username === 'root'): ?>
                <li><a href="/editor.php">编辑</a></li>
                <?php endif; ?>
                
                <li><a href="/author/manage.php">管理我的作品</a></li>
            </ul>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 依依家的猫窝</p>
    </footer>
</body>
</html>
