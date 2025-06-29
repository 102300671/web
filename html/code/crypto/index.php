<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /accunt/login.php");
    exit();
}

// 连接数据库
$conn = new mysqli('localhost', 'crypto', 'crypto', 'crypto_db');
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 获取用户的题目完成状况
$user_id = $_SESSION['user_id'];
$sql = "SELECT problem, completed FROM user_problem_status WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$problem_status = [];
while ($row = $result->fetch_assoc()) {
    $problem_status[$row['problem']] = $row['completed'];
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSA</title>
    <link rel="stylesheet" href="/css/code.css">
</head>
<body>
    <header>
        <h1>CTF</h1>
    </header>
    <main>
       <ul>
           <li><a href="rsa/rsa_1/index.php">rsa_1 <?php echo isset($problem_status['crypto/rsa/rsa_1']) && $problem_status['crypto/rsa/rsa_1'] ? '(已完成)' : '(未完成)'; ?></a></li>
           <li><a href="rsa/rsa_2/index.php">rsa_2 <?php echo isset($problem_status['crypto/rsa/rsa_2']) && $problem_status['crypto/rsa/rsa_2'] ? '(已完成)' : '(未完成)'; ?></a></li>
       </ul>
    </main>
</body>
</html>