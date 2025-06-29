<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}
?>
<?php
// 自动读取章节
$chapterDir = __DIR__ . '/chapters';
$files = glob($chapterDir . '/*.txt');
natcasesort($files);
$files = array_values($files);

$chapterTitles = array_map(function($f) {
    return htmlspecialchars(basename($f, '.txt'));
}, $files);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>作品简介</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/intro.css">
</head>
<body>
    <header>
        <h1>依存系青梅养成笔记</h1>
    </header>
    <main>
        <div class="container">
            <h1>作品标题：依存系青梅养成笔记</h1>
            <p><strong>作者：</strong>三谦</p >
            <div class="description">
                <p>
                    作品简介<br>
                
                    双女主+双视角+青梅竹马伪骨+纯爱+现代都市<br>

                    陆依依被温流从小摸到大。<br>

                    被从小摸到大的陆依依只想问：“你到底负不负责？”<br>

                    温流玩了15年养成系游戏，还不止！<br>

                    陆依依只想说“那不是养成，是色诱！”<br>

                    ——————————————<br>

                    陆依依对温流说“我要给你治病”<br>

                    “什么病？”<br>

                    “太爱我的病。”温流笑了。<br>

                    “怎么治？”<br>

                    “只要我爱你比你爱我更多一点。”<br>

                    ——————————————<br>

                    别人说温流爱陆依依胜过生命，<br>

                    温流只觉得，在她认识到爱这份感情之前，依依就已经是她的生命。<br>

                    欢迎阅读本作品。您可以点击下面的链接从目录选择章节，或直接开始阅读第一章。
                </p>
            </div>
            <div class="start">
                <a href="index.php">📖 开始阅读</a >
            </div>
            <h2>📚 目录</h2>
            <div class="directory">
                <?php foreach ($chapterTitles as $i => $title): ?>
                    <a href="index.php?chapter=<?php echo $i; ?>&page=1"><?php echo $title; ?></a >
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 依依家的猫窝</p>
    </footer>
</body>
</html>