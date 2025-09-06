<?php
header('Content-Type: application/json');
session_start();

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支持POST']);
    exit;
}

// 仅在 multipart/form-data 类型且 $_FILES 为空时，才认为是文件过大
// 但如果是iframe链接上传或其他URL上传请求，则跳过此检查
if (empty($_FILES) && !isset($_POST['iframe_urls']) && !isset($_POST['image_urls']) && !isset($_POST['video_urls'])) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'multipart/form-data') !== false) {
        echo json_encode([
            'error' => '上传的文件可能超过了PHP配置的大小限制',
            'details' => 'post_max_size可能设置得太小，请检查PHP配置并增加该值',
            'php_info' => [
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ]
        ]);
        exit;
    }
}

$groupName = $_SESSION['group_name'] ?? '未知用户';
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');

$logFile = __DIR__ . '/../logs/upload_log.json';
$imageFile = __DIR__ . '/images.json';
$videoFile = __DIR__ . '/videos.json';
$iframeFile = __DIR__ . '/iframes.json';

$imageDir = __DIR__ . '/../assets/image/';
$videoDir = __DIR__ . '/../assets/video/';

!is_dir($imageDir) && mkdir($imageDir, 0755, true);
!is_dir($videoDir) && mkdir($videoDir, 0755, true);

// 工具函数
function updateJson($file, $url) {
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $data[] = $url;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function logUpload($file, $data) {
    $logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $logs[] = $data;
    file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

// ======================== URL/iframe 上传 ========================

// 图片链接上传
if (isset($_POST['image_urls'])) {
    $urls = $_POST['image_urls'];
    $valid = $invalid = [];
    foreach ($urls as $u) {
        $u = trim($u);
        if ($u !== '') {
            if (filter_var($u, FILTER_VALIDATE_URL)) {
                updateJson($imageFile, $u);
                logUpload($logFile, [
                    'type' => 'image_url',
                    'content' => $u,
                    'group_name' => $groupName,
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $valid[] = $u;
            } else $invalid[] = $u;
        }
    }
    if ($valid) {
        echo json_encode(['success' => count($valid) . '个图片链接上传成功' . ($invalid ? ', ' . count($invalid) . '个无效' : '')]);
    } else {
        echo json_encode(['error' => '未提供有效图片URL']);
    }
    exit;
}

// 视频链接上传
if (isset($_POST['video_urls'])) {
    $urls = $_POST['video_urls'];
    $valid = $invalid = [];
    foreach ($urls as $u) {
        $u = trim($u);
        if ($u !== '') {
            if (filter_var($u, FILTER_VALIDATE_URL)) {
                updateJson($videoFile, $u);
                logUpload($logFile, [
                    'type' => 'video_url',
                    'content' => $u,
                    'group_name' => $groupName,
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $valid[] = $u;
            } else $invalid[] = $u;
        }
    }
    if ($valid) {
        echo json_encode(['success' => count($valid) . '个视频链接上传成功' . ($invalid ? ', ' . count($invalid) . '个无效' : '')]);
    } else {
        echo json_encode(['error' => '未提供有效视频URL']);
    }
    exit;
}

// iframe 链接上传
if (isset($_POST['iframe_urls'])) {
    $urls = $_POST['iframe_urls'];
    $valid = $invalid = [];
    foreach ($urls as $u) {
        $u = trim($u);
        if ($u !== '') {
            // 检查是否是完整的iframe代码
            if (stripos($u, '<iframe') !== false) {
                // 保存完整的iframe代码
                updateJson($iframeFile, $u);
                logUpload($logFile, [
                    'type' => 'iframe_code',
                    'content' => $u,
                    'group_name' => $groupName,
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $valid[] = $u;
            } else if (filter_var($u, FILTER_VALIDATE_URL)) {
                // 保存iframe URL
                updateJson($iframeFile, $u);
                logUpload($logFile, [
                    'type' => 'iframe_url',
                    'content' => $u,
                    'group_name' => $groupName,
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $valid[] = $u;
            } else $invalid[] = $u;
        }
    }
    if ($valid) {
        echo json_encode(['success' => count($valid) . '个 iframe 链接上传成功' . ($invalid ? ', ' . count($invalid) . '个无效' : '')]);
    } else {
        echo json_encode(['error' => '未提供有效 iframe URL 或代码']);
    }
    exit;
}

// ======================== 图片上传 ========================
if (isset($_FILES['image'])) {
    $files = $_FILES['image'];
    $uploaded = $errors = [];
    $uploadTarget = $_POST['upload_target'] ?? 'local';

    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $tmpFile = $files['tmp_name'][$i];

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "$fileName: 上传错误";
            continue;
        }
        if (!is_uploaded_file($tmpFile)) {
            $errors[] = "$fileName: 非法上传";
            continue;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = "$fileName: 不支持的文件类型";
            continue;
        }

        $filename = 'Image_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $imageDir . $filename;

        if (move_uploaded_file($tmpFile, $target)) {
            $url = '/assets/image/' . $filename;
            updateJson($imageFile, $url);
            logUpload($logFile, [
                'type' => 'file',
                'content' => $url,
                'group_name' => $groupName,
                'ip' => $ip,
                'timestamp' => $timestamp
            ]);
            $uploaded[] = $url;
        } else {
            $errors[] = "$fileName: 上传失败";
        }
    }

    if ($uploaded) echo json_encode(['success' => count($uploaded) . '个图片上传成功', 'urls' => $uploaded, 'errors' => $errors]);
    else echo json_encode(['error' => '图片上传失败', 'details' => $errors]);
    exit;
}

// ======================== 视频上传 ========================
if (isset($_FILES['video'])) {
    $files = $_FILES['video'];
    $uploaded = $errors = [];

    logUpload($logFile, [
        'type' => 'video_upload_request',
        'content' => '接收到视频上传请求',
        'file_count' => count($files['name']),
        'group_name' => $groupName,
        'ip' => $ip,
        'timestamp' => $timestamp
    ]);

    $allowed = ['mp4','webm','ogg'];

    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $tmpFile = $files['tmp_name'][$i];

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "$fileName: 上传错误(错误代码: {$files['error'][$i]})";
            continue;
        }
        if (!is_uploaded_file($tmpFile)) {
            $errors[] = "$fileName: 非法上传";
            continue;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "$fileName: 不支持的视频类型($ext)";
            continue;
        }

        $filename = 'Video_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $videoDir . $filename;

        if (move_uploaded_file($tmpFile, $target)) {
            $url = '/assets/video/' . $filename;
            updateJson($videoFile, $url);
            logUpload($logFile, [
                'type' => 'video',
                'content' => $url,
                'group_name' => $groupName,
                'ip' => $ip,
                'timestamp' => $timestamp
            ]);
            $uploaded[] = $url;
        } else {
            $errors[] = "$fileName: 上传失败(移动文件失败)";
        }
    }

    if ($uploaded) echo json_encode(['success' => count($uploaded) . '个视频上传成功', 'urls' => $uploaded, 'errors' => $errors]);
    else echo json_encode(['error' => '视频上传失败', 'details' => $errors]);
    exit;
}

// 未提供任何数据
echo json_encode(['error' => '未提供数据']);
?>