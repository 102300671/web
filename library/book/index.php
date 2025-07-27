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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>依依家的猫窝</title>
    <link rel="stylesheet" href="/css/book.css">
</head>
<body>
    <header>
        <h1>依依家的猫窝</h1>
    </header>
    <main>
        <section>
            <h2>作者神力</h2>
            <ul>
                <li><a href="novel/依存系青梅养成笔记/intro.php">依存系青梅养成笔记</a></li>
                <?php if ($username === 'root'): ?>
                <li><a href="/editor.php">编辑</a></li>
                <?php endif; ?>
            </ul>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 依依家的猫窝</p>
    </footer>
</body>
</html>