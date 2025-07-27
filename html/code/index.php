<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}
$username = $_SESSION['username'] ?? '';  
?>

<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ctf</title>
    <link rel="stylesheet" href="/css/code.css">
</head>
<body>
    <header>
        <h1>CTF</h1>
    </header>
    <main>
      <ul>
        <li><a href="crypto/index.php">rsa</a></li>
        <li><a href="web/index.php">web</a></li>
        <?php if ($username === 'root'): ?>
        <li><a href="/editor.php">编辑</a></li>
        <?php endif; ?>
      </ul>
    </main>
</body>
</html>