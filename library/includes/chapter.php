<?php
// 支持的文件格式
$supportedFormats = ['txt', 'md', 'html', 'doc', 'docx'];

// 自动扫描章节文件
$files = [];
foreach ($supportedFormats as $format) {
    $formatFiles = glob($chapterDir . '/*.' . $format);
    if ($formatFiles) {
        $files = array_merge($files, $formatFiles);
    }
}

if (empty($files)) {
    die('没有找到章节文件');
}

// 按文件名排序
natcasesort($files);
$files = array_values($files);

// 生成章节标题（去掉路径和扩展名）
$chapterTitles = array_map(function($f) {
    return htmlspecialchars(pathinfo($f, PATHINFO_FILENAME));
}, $files);

/**
 * 读取并处理不同格式的章节内容
 * @param string $filePath 文件路径
 * @return array 处理后的内容行数组
 */
function readChapterContent($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if (!file_exists($filePath)) {
        return [];
    }
    
    // 读取文件内容
    $content = file_get_contents($filePath);
    
    // 根据文件格式处理内容
    switch ($ext) {
        case 'md':
            // 增强的Markdown解析
            // 标题
            $content = preg_replace('/^# (.*)$/m', '<h2>$1</h2>', $content);
            $content = preg_replace('/^## (.*)$/m', '<h3>$1</h3>', $content);
            $content = preg_replace('/^### (.*)$/m', '<h4>$1</h4>', $content);
            
            // 文本格式化
            $content = preg_replace('/\*\*(.*)\*\*/', '<strong>$1</strong>', $content);
            $content = preg_replace('/\*(.*)\*/', '<em>$1</em>', $content);
            
            // 列表
            $content = preg_replace('/^\* (.*)$/m', '<li>$1</li>', $content);
            $content = preg_replace('/^\d\. (.*)$/m', '<li>$1</li>', $content);
            // 将连续的列表项包装在ul或ol标签中
            $content = preg_replace('/(<li>.*<\/li>)(?!\s*<li>)/s', '<ul>$0</ul>', $content);
            
            // 链接
            $content = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $content);
            
            // 段落（简单处理，将非标签行包装在p标签中）
            $content = preg_replace('/^(?!<h[2-4]>|<ul>|<li>)(.*)$/m', '<p>$1</p>', $content);
            
            $lines = explode("\n", $content);
            break;
        
        case 'html':
            // 对于HTML文件，直接按行读取
            $lines = file($filePath, FILE_IGNORE_NEW_LINES);
            break;
        
        case 'docx':
            // 尝试用纯PHP解析DOCX文件（DOCX是ZIP文件包含XML）
            try {
                // 检查PHP是否支持ZIP扩展
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive;
                    if ($zip->open($filePath) === true) {
                        // 读取DOCX中的主要内容文件
                        $content = $zip->getFromName('word/document.xml');
                        $zip->close();
                        
                        // 去除XML标签，保留文本内容
                        $content = strip_tags($content);
                        // 处理特殊字符
                        $content = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $lines = explode("\n", $content);
                    } else {
                        $lines = ['无法打开DOCX文件，请检查文件是否损坏。'];
                    }
                } else {
                    $lines = ['PHP未启用ZIP扩展，无法解析DOCX文件。'];
                }
            } catch (Exception $e) {
                $lines = ['解析DOCX文件时出错：' . $e->getMessage()];
            }
            break;
            
        case 'doc':
            // DOC文件格式更复杂，这里提供提示
            $lines = [
                '当前服务器环境无法直接解析DOC文件。',
                '建议：1. 将DOC文件转换为DOCX格式后重新上传',
                '      2. 或复制文件内容到TXT格式后上传'
            ];
            break;
            
        case 'txt':
        default:
            // 对于TXT或其他未指定格式，按行读取
            $lines = file($filePath, FILE_IGNORE_NEW_LINES);
            break;
    }
    
    return $lines;
}

?>
