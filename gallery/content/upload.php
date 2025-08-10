<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /account/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8">
  <title>图片上传</title>
  <link rel="stylesheet" href="/assets/css/upload.css">
</head>
<body>
  <header>
    <h1>图片上传</h1>
  </header>
  
  <!-- 图床链接链接上传 -->
  <form id="url-form" class="upload-form">
    <h3>添加图床链接</h3>
    <div id="url-container">
      <input type="url" name="url[]" placeholder="https://example.com/image.jpg" required>
    </div>
    <button type="button" id="add-url">添加更多链接</button>
    <button type="submit">提交链接</button>
  </form>

  <!-- 本地文件上传（新增pixhost选项） -->
  <form id="file-form" class="upload-form" enctype="multipart/form-data">
    <h3>上传本地图片</h3>
    <!-- 新增上传目标选择器 -->
    <div class="upload-target">
      <label for="upload_target">上传目标：</label>
      <select id="upload_target" name="upload_target" required>
        <option value="local">本地存储</option>
        <option value="pixhost">pixhost.to（推荐）</option>
      </select>
    </div>
    <input type="file" name="image[]" accept="image/*" multiple required>
    <button type="submit">上传图片</button>
  </form>

  <!-- 结果提示 -->
  <div id="result"></div>
  <a href="index.php">返回图集</a>

  <script>
    // 使用AJAX处理表单提交
    document.querySelectorAll('.upload-form').forEach(form => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let formData;
        
        if (form.id === 'url-form') {
          // 处理多URL提交
          const urlInputs = form.querySelectorAll('input[name="url[]"]');
          const urls = Array.from(urlInputs).map(input => input.value.trim()).filter(Boolean);
          
          if (urls.length === 0) {
            alert('请至少输入一个URL');
            return;
          }
          
          formData = new FormData();
          urls.forEach(url => formData.append('urls[]', url));
        } else {
          // 文件表单使用默认FormData（自动包含upload_target参数）
          formData = new FormData(form);
        }
        
        const response = await fetch('progress_upload.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        const resultEl = document.getElementById('result');
        resultEl.innerHTML = result.success ? 
          `✅ ${result.success}` : 
          `❌ ${result.error || '未知错误'}`;
        
        // 添加错误样式类
        if (result.error) {
          resultEl.classList.add('error');
        } else {
          resultEl.classList.remove('error');
          // 成功后刷新页面显示新图片
          setTimeout(() => location.reload(), 1500);
        }
      });
    });

    // 添加更多URL输入框
    document.getElementById('add-url').addEventListener('click', () => {
      const container = document.getElementById('url-container');
      const input = document.createElement('input');
      input.type = 'url';
      input.name = 'url[]';
      input.placeholder = 'https://example.com/image.jpg';
      input.required = true;
      container.appendChild(input);
    });
  </script>
</body>
</html>