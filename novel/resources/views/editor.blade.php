<?php
// 授权用户名和密码
$AUTHORIZED_USER = 'root';
$AUTHORIZED_PASS = 'lvlinhan1021'; // 可以设置为任意密码

if (
    !isset($_SERVER['PHP_AUTH_USER']) || 
    !isset($_SERVER['PHP_AUTH_PW']) || 
    $_SERVER['PHP_AUTH_USER'] !== $AUTHORIZED_USER || 
    $_SERVER['PHP_AUTH_PW'] !== $AUTHORIZED_PASS
) {
    header('WWW-Authenticate: Basic realm="Secure Editor"');
    header('HTTP/1.0 401 Unauthorized');
    echo '未授权访问';
    exit;
}

// 到这里才是实际功能代码
echo "<h2>欢迎，root 用户！</h2>";
// 然后是文件浏览、编辑器的内容……
$path = isset($_GET['path']) ? $_GET['path'] : '.';

if (isset($_POST['save']) && isset($_POST['file'])) {
    file_put_contents($_POST['file'], $_POST['content']);
    echo "<p style='color:green'>保存成功！</p>";
}

$files = scandir($path);
$current = realpath($path);

echo "<h2>当前目录：$current</h2>";
echo "<ul>";

foreach ($files as $file) {
    $fullpath = realpath($path . '/' . $file);
    if (is_file($fullpath)) {
        echo "<li><a href='?edit=$fullpath'>" . htmlspecialchars($file) . "</a></li>";
    } elseif ($file !== '.' && $file !== '..') {
        echo "<li><a href='?path=$fullpath'>📁 " . htmlspecialchars($file) . "</a></li>";
    }
}
echo "</ul>";

if (isset($_GET['edit'])) {
    $file = $_GET['edit'];
    if (is_file($file)) {
        $content = htmlspecialchars(file_get_contents($file));
        echo "<h3>正在编辑：" . htmlspecialchars($file) . "</h3>";
        echo "<form method='POST'>
                <input type='hidden' name='file' value='" . htmlspecialchars($file) . "'>
                <textarea name='content' style='width:100%; height:400px;'>$content</textarea><br>
                <input type='submit' name='save' value='保存'>
              </form>";
    } else {
        echo "无法编辑该文件。";
    }
}
?>