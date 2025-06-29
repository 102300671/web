<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents("php://input"), true);

$user_flag = trim($data['flag'] ?? '');
$problem = $data['problem'] ?? '';
$username = $data['username'] ?? '未知用户';

$real_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

$flag_file = __DIR__ . "/code/$problem/flag.php";

if (!file_exists($flag_file)) {
    error_log("[$real_ip] 用户: $username, Problem: $problem, flag 文件不存在");
    echo json_encode(['success' => false]);
    exit;
}

// 载入 flag（要求 flag.php 返回 flag 字符串）
$real_flag = include($flag_file);
$result = ($user_flag === $real_flag);

$log_dir = 'logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}
$log_file = $log_dir . '/flag_submit.log';
$log_time = date('Y-m-d H:i:s');
$log_status = $result ? '正确' : '错误';
$log_line = "$log_time\t$real_ip\t$username\t$problem\t$user_flag\t$log_status\n";
file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);

if ($result) {
    // 尝试写入数据库
    try {
        $db = new mysqli("localhost", "crypto", "crypto", "crypto_db");
        if ($db->connect_error) throw new Exception("数据库连接失败");

        // 查找用户 ID
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'msg' => '用户不存在']);
            exit;
        }
        $stmt->close();

        // 更新状态
        $stmt = $db->prepare("INSERT INTO user_problem_status (user_id, problem, completed)
            VALUES (?, ?, TRUE)
            ON DUPLICATE KEY UPDATE completed = TRUE");
        $stmt->bind_param("is", $user_id, $problem);
        $stmt->execute();
        $stmt->close();
        $db->close();

        echo json_encode(['success' => true, 'msg' => 'flag 正确']);
    } catch (Exception $e) {
        error_log("数据库写入失败: " . $e->getMessage());
        echo json_encode(['success' => true, 'msg' => 'flag 正确，但数据库写入失败']);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'flag错误，请重试。']);
}
?>