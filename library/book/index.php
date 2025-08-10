<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'] ?? '';  

$novelDir = __DIR__ . '/novel';
$works = [];

if (is_dir($novelDir)) {
    $dirHandle = opendir($novelDir);
    while (($folder = readdir($dirHandle)) !== false) {
        if ($folder === '.' || $folder === '..') continue;
        
        $workPath = $novelDir . '/' . $folder;
        $introFile = $workPath . '/intro.php';
        $coverFile = $workPath . '/cover.jpg';
        
        if (is_dir($workPath) && file_exists($introFile)) {
            $nameParts = explode('_', $folder, 2);
            $author = $nameParts[0] ?? '未知作者';
            $workTitle = $nameParts[1] ?? $folder;
            
            $works[] = [
                'title' => $workTitle,
                'author' => $author,
                'introUrl' => "novel/{$folder}/intro.php",
                'coverUrl' => file_exists($coverFile) ? "novel/{$folder}/cover.jpg" : null
            ];
        }
    }
    closedir($dirHandle);
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>依依家的猫窝 - 小说首页</title>
    <link rel="stylesheet" href="/css/book.css" />
</head>
<body>
<header>
    <div class="logo" onclick="location.href='/book'">依依家的猫窝</div>
    <nav>
      <a href="/book">主页</a>
      <a href="/author/manage.php">作品管理</a>
    </nav>
</header>
<main>
    <div class="layout-toggle">
        <span>显示方式：</span>
        <button id="btnList" class="active">列表式</button>
        <button id="btnCard">卡片式</button>
    </div>
    <section id="workSection" class="list-layout">
        <ul class="work-list">
            <?php if (!empty($works)): ?>
                <?php foreach ($works as $work): ?>
                <li onclick="location.href='<?= htmlspecialchars($work['introUrl']) ?>'">
                    <?php if ($work['coverUrl']): ?>
                        <img src="<?= htmlspecialchars($work['coverUrl']) ?>" alt="封面" loading="lazy" />
                    <?php else: ?>
                        <div class="no-cover">暂无封面</div>
                    <?php endif; ?>
                    <div class="info">
                        <a href="<?= htmlspecialchars($work['introUrl']) ?>"><?= htmlspecialchars($work['title']) ?></a>
                        <div class="author">作者: <?= htmlspecialchars($work['author']) ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>暂无作品，敬请期待...</li>
            <?php endif; ?>
        </ul>
    </section>
</main>
<footer>
    <p>&copy; 2025 依依家的猫窝</p>
</footer>

<script>
    const btnList = document.getElementById('btnList');
    const btnCard = document.getElementById('btnCard');
    const workSection = document.getElementById('workSection');

    btnList.onclick = () => {
        workSection.classList.remove('card-layout');
        workSection.classList.add('list-layout');
        btnList.classList.add('active');
        btnCard.classList.remove('active');
    };
    btnCard.onclick = () => {
        workSection.classList.remove('list-layout');
        workSection.classList.add('card-layout');
        btnCard.classList.add('active');
        btnList.classList.remove('active');
    };
</script>
</body>
</html>