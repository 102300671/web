<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
$pageTitle = '作品简介';
$workTitle = '依存系青梅养成笔记';
$author = '三谦';
$description = <<<EOD
作品简介<br><br>
双女主+双视角+青梅竹马伪骨+纯爱+现代都市<br><br>

陆依依被温流从小摸到大。<br>
被从小摸到大的陆依依只想问：“你到底负不负责？”<br>
温流玩了15年养成系游戏，还不止！<br>
陆依依只想说“那不是养成，是色诱！”<br><br>

——————————————<br>

陆依依对温流说“我要给你治病”<br>
“什么病？”<br>
“太爱我的病。”温流笑了。<br>
“怎么治？”<br>
“只要我爱你比你爱我更多一点。”<br><br>

——————————————<br>

别人说温流爱陆依依胜过生命，<br>
温流只觉得，在她认识到爱这份感情之前，依依就已经是她的生命。<br><br>

欢迎阅读本作品。您可以点击下面的链接从目录选择章节，或直接开始阅读第一章。
EOD;
$chapterDir = __DIR__ . '/chapters';
?>

<?php include ROOT_DIR . '/includes/header.php'; ?>

<h1>作品标题：<?= htmlspecialchars($workTitle) ?></h1>
<p><strong>作者：</strong><?= htmlspecialchars($author) ?></p>
<div class="description">
    <p><?= $description ?></p>
</div>

<div class="start">
    <a href="index.php">📖 开始阅读</a>
</div>

<?php include ROOT_DIR . '/includes/chapter.php'; ?>

<?php include ROOT_DIR . '/includes/directory.php'; ?>

<?php include ROOT_DIR . '/includes/footer.php'; ?>