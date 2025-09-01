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

  <!-- URL 上传 -->
  <form id="url-form" class="upload-form">
    <h3>添加图床链接</h3>
    <div id="url-container">
      <input type="url" name="url[]" placeholder="https://example.com/image.jpg" required>
    </div>
    <button type="button" id="add-url">添加更多链接</button>
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

  <div id="result"></div>
  <a href="index.php">返回图集</a>

  <script>
    document.querySelectorAll('.upload-form').forEach(form=>{
      form.addEventListener('submit', async e=>{
        e.preventDefault();
        let formData = new FormData(form);

        // URL 表单处理
        if(form.id==='url-form'){
          const urls = Array.from(form.querySelectorAll('input[name="url[]"]'))
            .map(i=>i.value.trim()).filter(Boolean);
          if(urls.length===0){ alert('请至少输入一个URL'); return; }
          formData = new FormData();
          urls.forEach(u=>formData.append('urls[]',u));
        }

        const res = await fetch('progress_upload.php',{method:'POST',body:formData});
        const result = await res.json();
        const resultEl = document.getElementById('result');
        resultEl.innerHTML = result.success?`✅ ${result.success}`:`❌ ${result.error||'未知错误'}`;
        if(result.error) resultEl.classList.add('error'); else{ resultEl.classList.remove('error'); setTimeout(()=>location.reload(),1500);}
      });
    });

    document.getElementById('add-url').addEventListener('click',()=>{
      const container = document.getElementById('url-container');
      const input = document.createElement('input');
      input.type='url'; input.name='url[]'; input.placeholder='https://example.com/image.jpg'; input.required=true;
      container.appendChild(input);
    });
  </script>
</body>
</html>