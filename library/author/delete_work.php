<?php
require_once __DIR__ . '/../includes/auth.php';
$username = $_SESSION['username'];
$workName = $_GET['work'] ?? '';
$workDir = __DIR__ . "/../book/novel/{$workName}";

// 验证权限
if (empty($workName) || !str_starts_with($workName, "{$username}_")) {
    die("无权操作此作品");
}

// 验证目录存在
if (!is_dir($workDir)) {
    die("作品不存在");
}

// 递归删除目录及内容的函数
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

// 执行删除
if (deleteDirectory($workDir)) {
    // 删除成功，重定向回管理页面
    header("Location: manage.php?delete_success=1");
    exit();
} else {
    die("删除作品时发生错误，请稍后重试");
}
?>
    