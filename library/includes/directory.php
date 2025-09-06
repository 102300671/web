<h2>📚 目录</h2>
<div class="directory">
  <?php foreach ($chapterTitles as $i => $title): ?>
  <a href="index.php?chapter=<?php echo $i; ?>&page=1"><?php echo $title; ?></a>
  <?php endforeach; ?>

</div>