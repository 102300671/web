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
    // 方式2：多文件上传处理（支持本地和pixhost）
    $files = $_FILES['image'];
    $uploadedFiles = [];
    $errors = [];
    $uploadTarget = $_POST['upload_target'] ?? 'local';
    $uploadDir = __DIR__ . '/assets/image/'; // 本地上传目录（绝对路径）
    !is_dir($uploadDir) && mkdir($uploadDir, 0755, true); // 确保目录存在

    // 处理多文件上传
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $tmpFilePath = $files['tmp_name'][$i];
        
        // 检查文件上传状态
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "{$fileName}: 上传错误（错误码：{$files['error'][$i]}）";
            continue;
        }
        
        // 验证是否为合法上传文件
        if (!is_uploaded_file($tmpFilePath)) {
            $errors[] = "{$fileName}: 非法文件上传";
            continue;
        }

        if ($uploadTarget === 'pixhost') {
            // 上传到pixhost.to（修正直链提取逻辑）
            try {
                // 预验证文件类型（仅支持gif/png/jpeg）
                $mimeType = mime_content_type($tmpFilePath);
                $allowedMime = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($mimeType, $allowedMime)) {
                    $errors[] = "{$fileName}: 不支持的文件类型（仅支持JPG/PNG/GIF）";
                    continue;
                }

                // 预验证文件大小（最大10MB）
                $fileSize = filesize($tmpFilePath);
                if ($fileSize > 10 * 1024 * 1024) {
                    $errors[] = "{$fileName}: 文件大小超过限制（最大10MB）";
                    continue;
                }

                // 准备API请求参数
                $postData = [
                    'img' => new CURLFile($tmpFilePath, $mimeType, $fileName),
                    'content_type' => 0, // 0=安全内容，1=敏感内容
                    'max_th_size' => 500 // 缩略图最大尺寸（可选，150-500）
                ];

                // 发送请求到Pixhost API
                $ch = curl_init('https://api.pixhost.to/images');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: multipart/form-data; charset=utf-8'
                    ],
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT => 30
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                // 处理CURL错误
                if ($curlErr) {
                    $errors[] = "{$fileName}: 网络请求失败（{$curlErr}）";
                    continue;
                }

                // 解析响应
                $result = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "{$fileName}: 响应解析失败（无效JSON）";
                    continue;
                }

                // 提取并转换为图片直链
                if ($httpCode === 200 && !empty($result['show_url'])) {
                    // 核心逻辑：将show_url转换为直链
                    $showUrl = $result['show_url'];
                    $directUrl = str_replace(
                        'https://pixhost.to/show',
                        'https://img1.pixhost.to/images',
                        $showUrl
                    );

                    // 验证直链格式（可选，增强稳定性）
                    if (filter_var($directUrl, FILTER_VALIDATE_URL)) {
                        // 保存直链并记录日志
                        updateJson($jsonFile, $directUrl);
                        logUpload($logFile, [
                            'type' => 'pixhost',
                            'content' => $directUrl,
                            'group_name' => $groupName ?? '未分组',
                            'ip' => $ip,
                            'timestamp' => $timestamp
                        ]);
                        $uploadedFiles[] = $directUrl;
                    } else {
                        $errors[] = "{$fileName}: 直链转换失败（无效URL）";
                    }
                } else {
                    // 错误处理（基于API错误码）
                    $errorMsg = match ($httpCode) {
                        413 => '文件大小超过10MB限制',
                        414 => '不支持的文件格式（仅支持GIF/PNG/JPG）',
                        400 => '请求参数错误',
                        500 => '服务器内部错误，请稍后重试',
                        default => $result['error'] ?? "未知错误（状态码：{$httpCode}）"
                    };
                    $errors[] = "{$fileName}: 上传失败（{$errorMsg}）";
                }
            } catch (Exception $e) {
                $errors[] = "{$fileName}: 处理异常（{$e->getMessage()}）";
            }
        } else {
            // 本地上传逻辑（保持不变，略作优化）
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowedExt)) {
                $errors[] = "{$fileName}: 不支持的文件类型（仅允许" . implode(',', $allowedExt) . "）";
                continue;
            }

            $filename = 'Image_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($tmpFilePath, $targetPath)) {
                $localUrl = '/assets/image/' . $filename;
                updateJson($jsonFile, $localUrl);
                logUpload($logFile, [
                    'type' => 'file',
                    'content' => $localUrl,
                    'group_name' => $groupName ?? '未分组',
                    'ip' => $ip,
                    'timestamp' => $timestamp
                ]);
                $uploadedFiles[] = $localUrl;
            } else {
                $errors[] = "{$fileName}: 本地文件移动失败（目录不可写？）";
            }
        }
    }

    // 输出结果
    if (!empty($uploadedFiles)) {
        echo json_encode([
            'success' => count($uploadedFiles) . '个文件上传成功',
            'urls' => $uploadedFiles,
            'errors' => $errors // 附带错误信息，方便调试
        ]);
    } else {
        echo json_encode([
            'error' => '所有文件上传失败',
            'details' => $errors
        ]);
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

    // 检查是否为pixiv图床链接（保持原有防盗链处理）
    if (preg_match('#^https://i\.pximg\.net/#', $newUrl)) {
        $encodedUrl = urlencode($newUrl);
        $newUrl = "/gallery/content/proxy.php?url=$encodedUrl";
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
    // 添加 JSON_UNESCAPED_UNICODE 参数，保留原始字符
    file_put_contents(
        $logFile, 
        json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
}
?>