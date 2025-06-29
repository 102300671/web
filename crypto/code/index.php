<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
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
      </ul>
    </main>
</body>
</html>