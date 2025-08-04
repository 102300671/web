<?php
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
      
    $html .= '<a href="intro.php">简介</a>';  
    $html .= '</div>';  
      
    return $html;  
}  
?>  
