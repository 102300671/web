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
    <title>crypto</title>
    <link rel="stylesheet" href="/css/problem.css">
</head>
<body>
    <header>
        <h1>RSA</h1>
    </header>
    <main>
        <a href="rsa_1.py" download="rsa_1.py">rsa_1附件</a>
        <form id="flagForm">
            <input type="text" name="flag" id="flagInput" placeholder="flag{}">
            <input type="submit" value="提交">
        </form>
        <p id="result"></p>
        <button id="showOutput">显示 rsa_1参数 内容</button>
        <pre id="outputContent" style="background:#f5f5f5;padding:10px;"></pre>
        <script>
            document.getElementById('flagForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const flag = document.getElementById('flagInput').value;
                const username = "<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>";
                fetch('/api/check_flag', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({flag: flag, problem: 'crypto/rsa/rsa_1', username: username})
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('result').textContent = '验证成功！';
                        document.getElementById('result').style.color = 'green';
                    } else {
                        document.getElementById('result').textContent = 'flag错误，请重试。';
                        document.getElementById('result').style.color = 'red';
                    }
                    document.getElementById('flagInput').value = '';
                });
            });
            document.getElementById('showOutput').addEventListener('click', function() {
                fetch('output.txt')
                    .then(res => res.text())
                    .then(text => {
                        document.getElementById('outputContent').textContent = text;
                    })
                    .catch(() => {
                        document.getElementById('outputContent').textContent = '无法读取文件内容。';
                    });
            });
        </script>
    </main>
</body>
</html>