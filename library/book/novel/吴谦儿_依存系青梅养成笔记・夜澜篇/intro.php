<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
$pageTitle = '作品简介';
$workTitle = '依存系青梅养成笔记・夜澜篇';
$author = '吴谦儿';
$description = <<<EOD
作品简介<br><br>
从记事起，陆依依的人生就没离开过温流的指尖。<br>
是洗澡时会被细细擦拭每寸肌肤的亲昵，<br>
是被窝里抵着后背睡熟的温度，<br>
是十五年来从未间断的、带着侵略性的 “触碰”—— 温流总说这是 “养成”，<br><br>
陆依依却红着脸咬牙：“明明是色诱！”<br>

——————————————<br>

当青梅竹马的情谊在日复一日的 “特殊对待” 里发酵变质，<br>
当 “伪骨姐妹” 的界限被深夜失控的吻撕碎，<br>
陆依依终于忍不住堵住温流的去路，<br>
眼底带着委屈又倔强的水汽：“你摸了我这么多年，到底负不负责？”<br>
温流低笑，指尖习惯性划过她的腰线，<br>
语气是势在必得的纵容：“你想要我怎么负责？”<br>
后来陆依依捧着温流的脸，<br>
一本正经宣告：“我要给你治病。”<br>
“哦？什么病？”<br>
“太爱我的病。”<br>

——————————————<br><br>

温流笑得更沉，将人圈进怀里：“那得让你爱我更多一点才行。”<br>
旁人都说温流把陆依依宠成了无法无天的小月亮，<br>
只有温流自己知道，在她懂得 “爱” 的定义之前，<br>
陆依依就已经是她赖以生存的光。<br>
这场始于 “养成” 的羁绊，<br>
早在十五年前她第一次牵起那个软乎乎的小手时，<br>
就注定了要用余生来续写 ——<br>
毕竟，她玩的从来不是游戏，是刻进骨血的独占。<br><br>
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