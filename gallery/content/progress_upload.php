<?php
header('Content-Type: application/json');

// 检查权限和上传合法性
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支持POST请求']);
    exit;
}

// 配置文件路径
$jsonFile = __DIR__ . '/images.json';
$uploadDir = __DIR__ . '/../assets/image/';
$logFile = __DIR__ . '/../logs/upload_log.json';

// 获取用户信息
session_start();
$groupName = $_SESSION['group_name'] ?? '未知用户';
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = date('Y-m-d H:i:s');

// 处理两种上传方式
if (isset($_POST['urls'])) {
    // 方式1：多图床链接处理
    $urls = $_POST['urls'];
    $validUrls = [];
    $invalidUrls = [];
    
    foreach ($urls as $url) {
        $url = trim($url);
        if (!empty($url)) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                updateJson($jsonFile, $url);
                logUpload($logFile, [
                    'type' => 'url',
                    'content' => $url,
                    'group_name' => $groupName,
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $validUrls[] = $url;
            } else {
                $invalidUrls[] = $url;
            }
        }
    }
    
    $response = [];
    if (!empty($validUrls)) {
        $response['success'] = count($validUrls) . '个链接添加成功';
        if (!empty($invalidUrls)) {
            $response['success'] .= '，' . count($invalidUrls) . '个无效URL';
        }
    } else {
        $response['error'] = '未提供有效URL或全部URL无效';
    }
    
    echo json_encode($response);
} elseif (isset($_FILES['image'])) {
    // 方式2：多文件上传处理
    $files = $_FILES['image'];
    $uploadedFiles = [];
    $errors = [];
    
    // 处理多文件上传
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = 'Image_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            
            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $localUrl = '/assets/image/' . $filename;
                updateJson($jsonFile, $localUrl);
                logUpload($logFile, [
                    'type' => 'file',
                    'content' => $localUrl,
                    'group_name' => $groupName,
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $uploadedFiles[] = $localUrl;
            } else {
                $errors[] = $files['name'][$i] . ': 文件移动失败';
            }
        } else {
            $errors[] = $files['name'][$i] . ': 上传错误 (' . $files['error'][$i] . ')';
        }
    }
    
    if (!empty($uploadedFiles)) {
        $message = count($uploadedFiles) . '个文件上传成功';
        if (!empty($errors)) {
            $message .= '，' . count($errors) . '个文件上传失败';
        }
        echo json_encode([
            'success' => $message,
            'urls' => $uploadedFiles
        ]);
    } else {
        echo json_encode(['error' => '所有文件上传失败: ' . implode('; ', $errors)]);
    }
} else {
    echo json_encode(['error' => '未提供有效数据']);
}

// 更新JSON文件的辅助函数
function updateJson($file, $newUrl) {
    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: [];
    }
    $data[] = $newUrl;
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// 记录上传日志的函数
function logUpload($logFile, $logData) {
    $logs = [];
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true) ?: [];
    }
    $logs[] = $logData;
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
?>