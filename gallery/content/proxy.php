<?php
// gallery/proxy.php
if (!isset($_GET['url'])) {
    http_response_code(400);
    exit('Missing URL');
}

$url = $_GET['url'];

// 安全性检查：只允许特定图床，如 pixiv
if (!preg_match('/^https:\/\/i\.pximg\.net\//', $url)) {
    http_response_code(403);
    exit('Forbidden');
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Referer: https://www.pixiv.net/' // 关键：绕过防盗链
]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 可视情况开启/关闭

$data = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($data === false) {
    http_response_code(500);
    exit('Failed to fetch image');
}

header("Content-Type: $contentType");
header("Cache-Control: max-age=86400");
echo $data;
