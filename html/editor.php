<?php
// 配置参数
session_start();

// 检查登录状态
if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}

// 检查权限
if ($_SESSION['username'] !== 'root') {
    header("Location: /book/index.php");
    exit();
}

$BASE_DIR = __DIR__;
$ALLOWED_EXTENSIONS = ['php', 'html', 'css', 'js', 'txt', 'md'];
$SITE_BASE_URL = 'http://localhost:8081';

// 安全函数：验证路径是否在允许的根目录内
function is_safe_path($path, $base) {
    $realPath = realpath($path);
    $realBase = realpath($base);
    return $realPath !== false && strpos($realPath, $realBase) === 0;
}

// 生成文件访问URL
function get_file_url($filePath, $baseDir, $siteUrl) {
    $realFile = realpath($filePath);
    $realBase = realpath($baseDir);
    if (!$realFile || !$realBase || strpos($realFile, $realBase) !== 0) {
        return false;
    }
    $relativePath = substr($realFile, strlen($realBase));
    $urlPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    return rtrim($siteUrl, '/') . $urlPath;
}

// 初始化路径
$path = isset($_GET['path']) ? $_GET['path'] : $BASE_DIR;
if (!is_safe_path($path, $BASE_DIR)) {
    die("<div class='container'><div class='message error'>安全警告：禁止访问外部目录</div></div>");
}

// 处理文件保存
$saveMsg = '';
if (isset($_POST['save']) && isset($_POST['file']) && isset($_POST['content'])) {
    $targetFile = $_POST['file'];
    if (is_safe_path($targetFile, $BASE_DIR) && is_file($targetFile)) {
        $ext = pathinfo($targetFile, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $ALLOWED_EXTENSIONS)) {
            if (file_put_contents($targetFile, $_POST['content']) !== false) {
                $saveMsg = "<div class='message success'>保存成功！</div>";
            } else {
                $saveMsg = "<div class='message error'>保存失败：权限不足或文件不可写</div>";
            }
        } else {
            $saveMsg = "<div class='message error'>禁止编辑该类型文件</div>";
        }
    } else {
        $saveMsg = "<div class='message error'>无效的文件路径</div>";
    }
}

// 获取目录列表
$currentPath = realpath($path);
if (!is_dir($currentPath)) {
    $currentPath = $BASE_DIR;
}

$files = @scandir($currentPath);
if ($files === false) {
    die("<div class='container'><div class='message error'>无法读取目录：权限不足</div></div>");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安全文件编辑器 - 依依家的猫窝</title>
    <link rel="stylesheet" href="/css/editor.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>欢迎，<?php echo htmlspecialchars($_SESSION['username']); ?> 用户！</h2>
        </div>
        
        <?php echo $saveMsg; ?>
        
        <div class="file-browser">
            <h3>当前目录：<?php echo htmlspecialchars($currentPath); ?></h3>
            <ul class="file-list">
                <?php if ($currentPath !== realpath($BASE_DIR)): ?>
                    <li><a href="?path=<?php echo urlencode(dirname($currentPath)); ?>">🔙 上级目录</a></li>
                <?php endif; ?>
                
                <?php foreach ($files as $file): ?>
                    <?php if ($file === '.' || $file === '..') continue; ?>
                    <?php $fullpath = $currentPath . DIRECTORY_SEPARATOR . $file; ?>
                    <?php $encodedPath = urlencode($fullpath); ?>
                    
                    <?php if (is_dir($fullpath)): ?>
                        <li><a href="?path=<?php echo $encodedPath; ?>">📁 <?php echo htmlspecialchars($file); ?></a></li>
                    <?php elseif (is_file($fullpath)): ?>
                        <?php $ext = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION)); ?>
                        <?php if (in_array($ext, $ALLOWED_EXTENSIONS)): ?>
                            <li><a href="?edit=<?php echo $encodedPath; ?>&path=<?php echo urlencode($currentPath); ?>">📄 <?php echo htmlspecialchars($file); ?></a></li>
                        <?php else: ?>
                            <li>📄 <?php echo htmlspecialchars($file); ?> <small>(不支持编辑)</small></li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php if (isset($_GET['edit'])): ?>
            <?php
            $editFile = $_GET['edit'];
            if (is_safe_path($editFile, $BASE_DIR) && is_file($editFile)) {
                $ext = strtolower(pathinfo($editFile, PATHINFO_EXTENSION));
                if (in_array($ext, $ALLOWED_EXTENSIONS)) {
                    $content = htmlspecialchars(file_get_contents($editFile));
            ?>
            <div class="editor-container">
                <h3>编辑文件：<?php echo htmlspecialchars($editFile); ?></h3>
                
                <?php if (in_array($ext, ['php', 'html'])): ?>
                    <?php $fileUrl = get_file_url($editFile, $BASE_DIR, $SITE_BASE_URL); ?>
                    <?php if ($fileUrl): ?>
                        <p><a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-secondary">🔗 在新窗口预览</a></p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <form method="POST" class="editor-form">
                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($editFile); ?>">
                    <textarea name="content"><?php echo $content; ?></textarea>
                    <div class="editor-actions">
                        <button type="submit" name="save" class="btn btn-primary">保存更改</button>
                        <a href="?path=<?php echo urlencode($currentPath); ?>" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
            <?php
                } else {
                    echo "<div class='message error'>该类型文件不允许编辑</div>";
                }
            } else {
                echo "<div class='message error'>无效的文件路径或无访问权限</div>";
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>