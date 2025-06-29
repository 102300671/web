<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
?>
<?php
// 当前目录绝对路径
$baseDir = __DIR__;

// 获取输入
$input = $_POST['cmd'] ?? '';

$output = '';

if ($input) {
    // 先用正则匹配命令格式：&php命令执行& 之间的内容
    $pos = strpos($input, '&');
if ($pos !== false) {
    $cmd = trim(substr($input, $pos + 1));

        // 简单限制：检查命令里是否访问了目录之外的文件路径
        // 禁止 ../ 等跳出当前目录的路径
        if (strpos($cmd, '..') !== false) {
            $output = "Error: Access outside current directory is forbidden.";
        } else {
            // 这里为了演示执行PHP代码，我们用 eval()
            // 实际环境千万不要用eval，这里仅示例漏洞利用方式
            try {
                // eval 返回值的捕获
                ob_start();
                eval($cmd);
                $output = ob_get_clean();
            } catch (Throwable $e) {
                $output = "Execution error: " . $e->getMessage();
            }
        }
    } else {
        // 输入没有特定格式，什么也不做
        $output = "No php command to execute.";
    }
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>请输入</title>
    <link rel="stylesheet" href="/css/problem.css">
  </head>
<body>
  <header>
    <h1>WEB</h1>
  </header>
  <main>
    <form method="post">
    <input type="text" name="cmd" value="<?php echo htmlspecialchars($input); ?>" />
    <button type="submit">提交</button>
    </form>
    <pre><?php echo htmlspecialchars($output); ?></pre>
    <form id="flagForm">
    <input type="text" name="flag" id="flagInput" placeholder="flag{}">
            <input type="submit" value="提交">
    </form>
    <p id="result"></p>
  </main>
  <script>
            document.getElementById('flagForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const flag = document.getElementById('flagInput').value;
                const username = "<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>";
                fetch('/check_flag.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({flag: flag, problem: 'web/ci/ci_1', username: username})
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
        </script>
</body>
</html>