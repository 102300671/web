<?php
// 配置参数
session_start();

// 检查登录状态
if (!isset($_SESSION['user_id'])) {
  header("Location: /account/login.php");
  exit();
}

// 检查权限
if ($_SESSION['username'] != 'root') {
  header("Location: /code/index.php");
  exit();
}

// 安全配置
$BASE_DIR = __DIR__;
$ALLOWED_EXTENSIONS = ['php', 'html', 'css', 'js', 'txt', 'md'];
$SITE_BASE_URL = 'http://localhost:8081';

// 安全函数：验证路径是否在允许的根目录内
function is_safe_path($path, $base) {
  if (empty($path)) return false;
  $realPath = realpath($path);
  $realBase = realpath($base);
  return $realPath !== false && strpos($realPath, $realBase) === 0;
}

// 生成文件访问URL
function get_file_url($filePath, $baseDir, $siteUrl) {
  $realFile = realpath($filePath);
  $realBase = realpath($baseDir);
  if (!$realFile || !$realBase || strpos($realFile, $realBase) !== 0) {
    return false;
  }
  $relativePath = substr($realFile, strlen($realBase));
  $urlPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
  return rtrim($siteUrl, '/') . $urlPath;
}

// 初始化路径
$path = isset($_GET['path']) ? $_GET['path'] : $BASE_DIR;
if (!is_safe_path($path, $BASE_DIR)) {
  die("<div class='container'><div class='message error'>安全警告：禁止访问外部目录</div></div>");
}

// 处理文件保存
$saveMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'], $_POST['file'], $_POST['content'])) {
  $targetFile = $_POST['file'];
  if (is_safe_path($targetFile, $BASE_DIR) && is_file($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    if (in_array($ext, $ALLOWED_EXTENSIONS)) {
      if (is_writable($targetFile)) {
        if (file_put_contents($targetFile, $_POST['content']) !== false) {
          $saveMsg = "<div class='message success'>文件保存成功！</div>";
        } else {
          $saveMsg = "<div class='message error'>保存失败：写入文件时出错</div>";
        }
      } else {
        $saveMsg = "<div class='message error'>保存失败：文件不可写</div>";
      }
    } else {
      $saveMsg = "<div class='message error'>禁止编辑该类型文件</div>";
    }
  } else {
    $saveMsg = "<div class='message error'>无效的文件路径</div>";
  }
}

// 获取目录列表
$currentPath = realpath($path);
if (!is_dir($currentPath)) {
  $currentPath = $BASE_DIR;
}

$files = @scandir($currentPath);
if ($files === false) {
  die("<div class='container'><div class='message error'>无法读取目录：权限不足</div></div>");
}

// 面包屑导航
function get_breadcrumbs($currentPath, $baseDir) {
  $breadcrumbs = [];
  $relativePath = str_replace(realpath($baseDir), '', $currentPath);
  $parts = array_filter(explode(DIRECTORY_SEPARATOR, $relativePath));

  $accumulatedPath = $baseDir;
  $breadcrumbs[] = [
    'name' => '根目录',
    'path' => $baseDir
  ];

  foreach ($parts as $part) {
    $accumulatedPath .= DIRECTORY_SEPARATOR . $part;
    $breadcrumbs[] = [
      'name' => $part,
      'path' => $accumulatedPath
    ];
  }

  return $breadcrumbs;
}

$breadcrumbs = get_breadcrumbs($currentPath, $BASE_DIR);

// 处理新建文件夹
if (isset($_POST['new_folder']) && isset($_POST['folder_name'])) {
  $folderName = trim($_POST['folder_name']);
  $newFolderPath = $currentPath . DIRECTORY_SEPARATOR . $folderName;

  if (empty($folderName)) {
    $saveMsg = "<div class='message error'>文件夹名称不能为空</div>";
  } elseif (file_exists($newFolderPath)) {
    $saveMsg = "<div class='message error'>文件夹已存在</div>";
  } elseif (!is_safe_path($newFolderPath, $BASE_DIR)) {
    $saveMsg = "<div class='message error'>非法路径</div>";
  } elseif (mkdir($newFolderPath, 0755)) {
    $saveMsg = "<div class='message success'>文件夹创建成功</div>";
  } else {
    $saveMsg = "<div class='message error'>文件夹创建失败</div>";
  }
}

// 处理新建文件
if (isset($_POST['new_file']) && isset($_POST['file_name'])) {
  $fileName = trim($_POST['file_name']);
  $newFilePath = $currentPath . DIRECTORY_SEPARATOR . $fileName;

  if (empty($fileName)) {
    $saveMsg = "<div class='message error'>文件名不能为空</div>";
  } elseif (file_exists($newFilePath)) {
    $saveMsg = "<div class='message error'>文件已存在</div>";
  } elseif (!is_safe_path($newFilePath, $BASE_DIR)) {
    $saveMsg = "<div class='message error'>非法路径</div>";
  } else {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXTENSIONS)) {
      $saveMsg = "<div class='message error'>不支持创建该类型文件</div>";
    } elseif (file_put_contents($newFilePath, '') !== false) {
      $saveMsg = "<div class='message success'>文件创建成功</div>";
    } else {
      $saveMsg = "<div class='message error'>文件创建失败</div>";
    }
  }
}

// 处理删除操作
if (isset($_GET['delete'])) {
  $deletePath = $_GET['delete'];
  if (is_safe_path($deletePath, $BASE_DIR)) {
    if (is_dir($deletePath)) {
      if (count(scandir($deletePath)) == 2) {
        // 空目录
        if (rmdir($deletePath)) {
          $saveMsg = "<div class='message success'>目录删除成功</div>";
        } else {
          $saveMsg = "<div class='message error'>目录删除失败</div>";
        }
      } else {
        $saveMsg = "<div class='message error'>目录非空，不能删除</div>";
      }
    } elseif (is_file($deletePath)) {
      if (unlink($deletePath)) {
        $saveMsg = "<div class='message success'>文件删除成功</div>";
      } else {
        $saveMsg = "<div class='message error'>文件删除失败</div>";
      }
    }
  } else {
    $saveMsg = "<div class='message error'>非法操作</div>";
  }
  // 刷新页面避免重复删除
  header("Location: ?path=" . urlencode($currentPath));
  exit();
}

// 处理重命名操作
if (isset($_POST['rename']) && isset($_POST['old_path']) && isset($_POST['new_name'])) {
  $oldPath = $_POST['old_path'];
  $newName = trim($_POST['new_name']);
  $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $newName;

  if (empty($newName)) {
    $saveMsg = "<div class='message error'>名称不能为空</div>";
  } elseif (!is_safe_path($oldPath, $BASE_DIR) || !is_safe_path($newPath, $BASE_DIR)) {
    $saveMsg = "<div class='message error'>非法路径</div>";
  } elseif (file_exists($newPath)) {
    $saveMsg = "<div class='message error'>目标名称已存在</div>";
  } elseif (rename($oldPath, $newPath)) {
    $saveMsg = "<div class='message success'>重命名成功</div>";
    // 如果是编辑的文件被重命名，跳转到新路径
    if (isset($_GET['edit']) && $_GET['edit'] === $oldPath) {
      header("Location: ?edit=" . urlencode($newPath) . "&path=" . urlencode($currentPath));
      exit();
    }
  } else {
    $saveMsg = "<div class='message error'>重命名失败</div>";
  }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>安全文件编辑器 - 依依家的猫窝</title>
  <link rel="stylesheet" href="/css/editor.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h2><i class="fas fa-user-shield"></i> 欢迎，<?php echo htmlspecialchars($_SESSION['username']); ?> 用户！</h2>
      <div class="header-actions">
        <a href="?path=<?php echo urlencode($BASE_DIR); ?>" class="btn btn-secondary"><i class="fas fa-home"></i> 返回根目录</a>
      </div>
    </div>

    <?php echo $saveMsg; ?>

    <div class="breadcrumbs">
      <?php foreach ($breadcrumbs as $index => $crumb): ?>
      <?php if ($index > 0): ?><span class="separator">/</span><?php endif; ?>
      <a href="?path=<?php echo urlencode($crumb['path']); ?>"><?php echo htmlspecialchars($crumb['name']); ?></a>
      <?php endforeach; ?>
    </div>

    <!-- 文件操作工具栏 -->
    <div class="file-actions">
      <button id="newFolderBtn" class="btn btn-action">
        <i class="fas fa-folder-plus"></i> 新建文件夹
      </button>
      <button id="newFileBtn" class="btn btn-action">
        <i class="fas fa-file-alt"></i> 新建文件
      </button>
    </div>

    <!-- 新建文件夹弹窗 -->
    <div id="newFolderModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h3><i class="fas fa-folder-plus"></i> 新建文件夹</h3>
        <form method="POST">
          <input type="hidden" name="path" value="<?php echo htmlspecialchars($currentPath); ?>">
          <div class="form-group">
            <label for="folder_name">文件夹名称：</label>
            <input type="text" id="folder_name" name="folder_name" required>
          </div>
          <div class="form-actions">
            <button type="submit" name="new_folder" class="btn btn-primary">创建</button>
            <button type="button" class="btn btn-secondary close-btn">取消</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 新建文件弹窗 -->
    <div id="newFileModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h3><i class="fas fa-file-alt"></i> 新建文件</h3>
        <form method="POST">
          <input type="hidden" name="path" value="<?php echo htmlspecialchars($currentPath); ?>">
          <div class="form-group">
            <label for="file_name">文件名称：</label>
            <input type="text" id="file_name" name="file_name" required placeholder="例如：index.php">
          </div>
          <div class="form-actions">
            <button type="submit" name="new_file" class="btn btn-primary">创建</button>
            <button type="button" class="btn btn-secondary close-btn">取消</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 重命名弹窗 -->
    <div id="renameModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h3><i class="fas fa-edit"></i> 重命名</h3>
        <form method="POST">
          <input type="hidden" name="path" value="<?php echo htmlspecialchars($currentPath); ?>">
          <input type="hidden" id="old_path" name="old_path" value="">
          <div class="form-group">
            <label for="new_name">新名称：</label>
            <input type="text" id="new_name" name="new_name" required>
          </div>
          <div class="form-actions">
            <button type="submit" name="rename" class="btn btn-primary">确认</button>
            <button type="button" class="btn btn-secondary close-btn">取消</button>
          </div>
        </form>
      </div>
    </div>

    <div class="file-browser">
      <div class="file-browser-header">
        <h3><i class="fas fa-folder-open"></i> 当前目录：<?php echo htmlspecialchars($currentPath); ?></h3>
        <div class="search-box">
          <input type="text" id="fileSearch" placeholder="搜索文件...">
        </div>
      </div>

      <ul class="file-list">
        <?php if ($currentPath !== realpath($BASE_DIR)): ?>
        <li class="parent-dir">
          <a href="?path=<?php echo urlencode(dirname($currentPath)); ?>">
            <i class="fas fa-level-up-alt"></i> 上级目录
          </a>
        </li>
        <?php endif; ?>

        <?php
        // 先显示目录，再显示文件
        $dirs = [];
        $filesList = [];
        foreach ($files as $file) {
          if ($file === '.' || $file === '..') continue;
          $fullpath = $currentPath . DIRECTORY_SEPARATOR . $file;
          if (is_dir($fullpath)) {
            $dirs[] = $file;
          } else {
            $filesList[] = $file;
          }
        }

        // 显示目录
        foreach ($dirs as $file):
        $fullpath = $currentPath . DIRECTORY_SEPARATOR . $file;
        $encodedPath = urlencode($fullpath);
        ?>
        <li class="directory">
          <a href="?path=<?php echo $encodedPath; ?>" data-path="<?php echo rawurlencode($fullpath); ?>">
            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($file); ?>
          </a>
          <div class="file-actions-menu">
            <button class="action-btn rename-btn" data-path="<?php echo rawurlencode($fullpath); ?>" data-name="<?php echo htmlspecialchars($file); ?>">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete-btn" data-path="<?php echo rawurlencode($fullpath); ?>">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </li>
        <?php endforeach; ?>

        <!-- 显示文件 -->
        <?php foreach ($filesList as $file):
        $fullpath = $currentPath . DIRECTORY_SEPARATOR . $file;
        $encodedPath = urlencode($fullpath);
        $ext = strtolower(pathinfo($fullpath, PATHINFO_EXTENSION));
        $fileIcon = get_file_icon($ext);
        ?>
        <li class="file <?php echo in_array($ext, $ALLOWED_EXTENSIONS) ? 'editable' : ''; ?>">
          <?php if (in_array($ext, $ALLOWED_EXTENSIONS)): ?>
          <a href="?edit=<?php echo $encodedPath; ?>&path=<?php echo urlencode($currentPath); ?>" data-path="<?php echo rawurlencode($fullpath); ?>">
            <?php echo $fileIcon; ?> <?php echo htmlspecialchars($file); ?>
          </a>
          <div class="file-actions-menu">
            <button class="action-btn rename-btn" data-path="<?php echo rawurlencode($fullpath); ?>" data-name="<?php echo htmlspecialchars($file); ?>">
              <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn delete-btn" data-path="<?php echo rawurlencode($fullpath); ?>">
              <i class="fas fa-trash"></i>
            </button>
          </div>
          <?php else : ?>
          <span>
            <?php echo $fileIcon; ?> <?php echo htmlspecialchars($file); ?>
            <small>(不支持编辑)</small>
          </span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php if (isset($_GET['edit'])): ?>
    <?php
    $editFile = $_GET['edit'];
    if (is_safe_path($editFile, $BASE_DIR) && is_file($editFile)) {
      $ext = strtolower(pathinfo($editFile, PATHINFO_EXTENSION));
      if (in_array($ext, $ALLOWED_EXTENSIONS)) {
        $content = htmlspecialchars(file_get_contents($editFile));
        ?>
        <div class="editor-container">
          <div class="editor-header">
            <h3><i class="fas fa-edit"></i> 编辑文件：<?php echo htmlspecialchars(basename($editFile)); ?></h3>
            <div class="editor-actions-top">
              <?php if (in_array($ext, ['php', 'html'])): ?>
              <?php $fileUrl = get_file_url($editFile, $BASE_DIR, $SITE_BASE_URL); ?>
              <?php if ($fileUrl): ?>
              <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-secondary">
                <i class="fas fa-external-link-alt"></i> 预览
              </a>
              <?php endif; ?>
              <?php endif; ?>
              <a href="?path=<?php echo urlencode($currentPath); ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> 取消
              </a>
            </div>
          </div>

          <form method="POST" class="editor-form">
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($editFile); ?>">
            <div class="editor-toolbar">
              <button type="button" id="indentBtn" class="tool-btn" title="缩进"><i class="fas fa-indent"></i></button>
              <button type="button" id="commentBtn" class="tool-btn" title="注释"><i class="fas fa-comment"></i></button>
            </div>
            <textarea name="content" id="editor" spellcheck="false"><?php echo $content; ?></textarea>
            <div class="editor-actions">
              <button type="submit" name="save" class="btn btn-primary">
                <i class="fas fa-save"></i> 保存更改
              </button>
              <span class="file-info">
                <?php echo round(filesize($editFile)/1024, 2); ?> KB |
                最后修改: <?php echo date("Y-m-d H:i:s", filemtime($editFile)); ?>
              </span>
            </div>
          </form>
        </div>
        <?php
      } else {
        echo "<div class='message error'>该类型文件不允许编辑</div>";
      }
    } else {
      echo "<div class='message error'>无效的文件路径或无访问权限</div>";
    }
    ?>
    <?php endif; ?>
  </div>

  <script>
    // 文件搜索功能
    document.getElementById('fileSearch').addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const fileItems = document.querySelectorAll('.file-list li');

      fileItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? '': 'none';
      });
    });

    // 编辑器辅助功能
    document.getElementById('indentBtn').addEventListener('click', function() {
      const editor = document.getElementById('editor');
      const startPos = editor.selectionStart;
      const endPos = editor.selectionEnd;
      const selectedText = editor.value.substring(startPos, endPos);
      const indentedText = selectedText.split('\n').map(line => '    ' + line).join('\n');

      editor.setRangeText(indentedText, startPos, endPos);
    });

    document.getElementById('commentBtn').addEventListener('click', function() {
      const editor = document.getElementById('editor');
      const startPos = editor.selectionStart;
      const endPos = editor.selectionEnd;
      const selectedText = editor.value.substring(startPos, endPos);

      // 根据文件扩展名确定注释语法
      const fileExt = '<?php echo $ext ?? ""; ?>';
      let commentedText;

      if (fileExt === 'php' || fileExt === 'html') {
        commentedText = selectedText.split('\n').map(line => '<!-- ' + line + ' -->').join('\n');
      } else if (fileExt === 'js' || fileExt === 'css') {
        commentedText = selectedText.split('\n').map(line => '/* ' + line + ' */').join('\n');
      } else {
        commentedText = selectedText.split('\n').map(line => '# ' + line).join('\n');
      }

      editor.setRangeText(commentedText, startPos, endPos);
    });
    // 为静态文件操作按钮添加事件监听器
    document.addEventListener('DOMContentLoaded', function() {
      // 重命名按钮事件
      document.querySelectorAll('.rename-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const path = this.dataset.path;
          if (path) {
            showRenameModal(path);
          }
        });
      });

      // 删除按钮事件
      document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const path = this.dataset.path;
          if (path && confirm('确定要删除这个项目吗？')) {
            deleteItem(path);
          }
        });
      });
    });

    // 模态框控制
    console.log('Initializing modals...');
    const modals = {
      newFolder: document.getElementById('newFolderModal'),
      newFile: document.getElementById('newFileModal'),
      rename: document.getElementById('renameModal')
    };

    // 打开模态框
console.log('New Folder Button:', document.getElementById('newFolderBtn'));
console.log('New File Button:', document.getElementById('newFileBtn'));

document.getElementById('newFolderBtn').addEventListener('click', function(e) {
  e.stopPropagation();
  console.log('Opening new folder modal');
  modals.newFolder.classList.add('active');
  console.log('Modal active class added:', modals.newFolder.classList.contains('active'));
});
document.getElementById('newFileBtn').addEventListener('click', function(e) {
  e.stopPropagation();
  console.log('Opening new file modal');
  modals.newFile.classList.add('active');
  console.log('Modal active class added:', modals.newFile.classList.contains('active'));
});

    // 关闭模态框
    document.querySelectorAll('.close, .close-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        Object.values(modals).forEach(modal => modal.classList.remove('active'));
      });
    });

    // 重命名按钮
    document.querySelectorAll('.rename-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        document.getElementById('old_path').value = decodeURIComponent(btn.dataset.path);
        document.getElementById('new_name').value = btn.dataset.name;
        modals.rename.style.display = 'block';
      });
    });

    // 删除按钮
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (confirm('确定要删除此项吗？此操作不可恢复！')) {
          window.location.href = `?delete=${decodeURIComponent(btn.dataset.path)}&path=<?php echo urlencode($currentPath); ?>`;
        }
      });
    });

    // 点击模态框外部关闭
    window.addEventListener('click', (e) => {
      if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
      }
    });
  </script>
</body>
</html>

<?php
// 获取文件类型对应的图标
function get_file_icon($ext) {
  $icons = [
    'php' => 'fab fa-php',
    'html' => 'fab fa-html5',
    'css' => 'fab fa-css3-alt',
    'js' => 'fab fa-js-square',
    'txt' => 'fas fa-file-alt',
    'md' => 'fas fa-markdown'
  ];

  return isset($icons[$ext])
  ? '<i class="' . $icons[$ext] . '"></i>'
  : '<i class="fas fa-file"></i>';
}
?>