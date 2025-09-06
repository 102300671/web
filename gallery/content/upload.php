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
  <title>上传媒体</title>
  <link rel="stylesheet" href="/assets/css/upload.css">
</head>
<body>
  <header>
    <h1>上传媒体</h1>
  </header>

  <!-- 图片链接上传 -->
  <form id="image-url-form" class="upload-form">
    <h3>添加图床链接</h3>
    <div id="image-url-container">
      <input type="url" name="image_url[]" placeholder="https://example.com/image.jpg" required>
    </div>
    <button type="button" id="add-image-url">添加更多链接</button>
    <button type="submit">提交链接</button>
  </form>

  <!-- 视频链接上传 -->
  <form id="video-url-form" class="upload-form">
    <h3>添加视频链接</h3>
    <div id="video-url-container">
      <input type="url" name="video_url[]" placeholder="https://example.com/video.mp4" required>
    </div>
    <button type="button" id="add-video-url">添加更多链接</button>
    <button type="submit">提交链接</button>
  </form>

  <!-- 图片上传 -->
  <form id="file-form" class="upload-form" enctype="multipart/form-data">
    <h3>上传本地图片</h3>
    <div class="upload-target">
      <label for="upload_target">上传目标：</label>
      <select id="upload_target" name="upload_target" required>
        <option value="local">本地存储</option>
        <option value="pixhost">Pixhost.to</option>
      </select>
    </div>
    <input type="file" name="image[]" accept="image/*" multiple required>
    <button type="submit">上传图片</button>
  </form>

  <!-- 视频上传 -->
  <form id="video-form" class="upload-form" enctype="multipart/form-data">
    <h3>上传本地视频</h3>
    <div class="upload-target">
      <label for="video_target">上传目标：</label>
      <select id="video_target" name="upload_target" required>
        <option value="local">本地存储</option>
      </select>
    </div>
    <input type="file" name="video[]" accept="video/mp4,video/webm,video/ogg" multiple required>
    <button type="submit">上传视频</button>
  </form>

  <!-- iframe 链接上传 -->
  <form id="iframe-url-form" class="upload-form">
    <h3>添加 iframe 代码或链接</h3>
    <div id="iframe-url-container">
      <input type="text" name="iframe_url[]" placeholder="https://player.bilibili.com/xxx 或粘贴完整的iframe代码" required>
      <p class="form-hint">您可以输入单个iframe URL或完整的iframe嵌入代码（如B站视频、Kapwing等）</p>
    </div>
    <button type="button" id="add-iframe-url">添加更多</button>
    <button type="submit">提交</button>
  </form>

  <div id="result"></div>
  <a href="index.php">返回图集</a>

  <script>
    const resultEl = document.getElementById('result');

    document.querySelectorAll('.upload-form').forEach(form => {
      form.addEventListener('submit', async e => {
        e.preventDefault();
        resultEl.innerHTML = '';
        resultEl.classList.remove('error');

        let formData = new FormData();

        if (form.id === 'image-url-form') {
          const urls = Array.from(form.querySelectorAll('input[name="image_url[]"]'))
            .map(i => i.value.trim()).filter(Boolean);
          if (!urls.length) return alert('请至少输入一个图片URL');
          urls.forEach(u => formData.append('image_urls[]', u));
        }
        else if (form.id === 'video-url-form') {
          const urls = Array.from(form.querySelectorAll('input[name="video_url[]"]'))
            .map(i => i.value.trim()).filter(Boolean);
          if (!urls.length) return alert('请至少输入一个视频URL');
          urls.forEach(u => formData.append('video_urls[]', u));
        }
        else if (form.id === 'iframe-url-form') {
          const urls = Array.from(form.querySelectorAll('input[name="iframe_url[]"]'))
            .map(i => i.value.trim()).filter(Boolean);
          if (!urls.length) return alert('请至少输入一个iframe URL');
          urls.forEach(u => formData.append('iframe_urls[]', u));
        }
        else {
          formData = new FormData(form); // 文件上传直接取 formData
        }

        try {
          const res = await fetch('progress_upload.php', { method: 'POST', body: formData });
          const result = await res.json();

          if (result.success) {
            resultEl.innerHTML = `✅ ${result.success}`;
            setTimeout(() => location.reload(), 1500);
          } else {
            resultEl.innerHTML = `❌ ${result.error || '未知错误'}`;
            resultEl.classList.add('error');
          }
        } catch (err) {
          resultEl.innerHTML = `❌ 请求失败: ${err.message}`;
          resultEl.classList.add('error');
        }
      });
    });

    // 动态添加输入框
    const addInput = (btnId, containerId, placeholder, inputType = 'url') => {
      document.getElementById(btnId).onclick = () => {
        const c = document.getElementById(containerId);
        c.insertAdjacentHTML('beforeend', `<input type="${inputType}" name="${containerId.replace('-container','')}_url[]" placeholder="${placeholder}" required>`);
      };
    };

    addInput('add-image-url', 'image-url-container', 'https://example.com/image.jpg');
    addInput('add-video-url', 'video-url-container', 'https://example.com/video.mp4');
    addInput('add-iframe-url', 'iframe-url-container', 'https://player.bilibili.com/xxx 或粘贴完整的iframe代码', 'text');
  </script>
</body>
</html>