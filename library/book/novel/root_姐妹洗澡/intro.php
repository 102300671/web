<?php
define('ROOT_DIR', realpath(__DIR__ . '/../../../'));
require_once ROOT_DIR . '/includes/auth.php';
$pageTitle = '作品简介';
$workTitle = '姐妹洗澡';
$author = 'root';
$description = <<<EOD
一起洗澡，然后色色
EOD;
$chapterDir = __DIR__ . '/chapters';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>
<h1>作品标题：<?= htmlspecialchars($workTitle) ?></h1>
<p><strong>作者：</strong><?= htmlspecialchars($author) ?></p>
<div class='description'><p><?= $description ?></p></div>
<div class='start'><a href='index.php'>📖 开始阅读</a></div>
<?php include ROOT_DIR . '/includes/chapter.php'; ?>
<?php include ROOT_DIR . '/includes/directory.php'; ?>
<?php include ROOT_DIR . '/includes/footer.php'; ?>