<?php
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

?>  
