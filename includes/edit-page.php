<?php
// 【关键修复 1】开启输出缓冲
ob_start();

session_start();

// 引入配置
include '../config.php';
include '../includes/functions.php'; 

// 权限检查
if (!isset($_SESSION['admin'])) {
    header('Location: ../index.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 调试：如果没有任何文件和内容，可能是前端没传过来
    // if (empty($_FILES['images']) && empty($_POST['content'])) { ... }

    $result = handlePostSubmission($conn);
    
    if ($result === true) {
        ob_end_clean(); 
        header('Location: ../index.php');
        exit;
    } else {
        $error_msg = "发表失败：" . ($result === false ? "未知错误" : $result);
    }
}

// 获取设置
$site_title = getSetting($conn, 'site_title');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>发表说说 - <?php echo htmlspecialchars($site_title); ?></title>

    <style>
        body { background-color: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .container { width: 100%; max-width: 600px; margin-top: 20px; padding: 15px; box-sizing: border-box; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; }
        .header { display: flex; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .back-btn { text-decoration: none; color: #666; font-size: 14px; display: flex; align-items: center; margin-right: 15px; cursor: pointer; }
        .back-icon { width: 20px; height: 20px; margin-right: 5px; }
        h2 { margin: 0; font-size: 18px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 14px; }
        
        textarea { width: 100%; min-height: 150px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; line-height: 1.5; resize: vertical; box-sizing: border-box; font-family: inherit; outline: none; }
        textarea:focus { border-color: #07c160; }
        
        
        
        
        
        /* 地址通用输入框基础样式 */
.form-control {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    color: #333;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
    transition: all 0.2s;
    font-family: inherit;
}

/* 聚焦效果：蓝色边框 + 轻微阴影 */
.form-control:focus {
    outline: none;
    border-color: #4a90e2;
    box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
}

/* 占位符颜色 */
.form-control::placeholder {
    color: #bbb;
}

/* --- 地址输入框专属样式 --- */
.music-input {
    min-height: 44px; /* 固定一个舒适的高度，类似单行输入框 */
    max-height: 30px; /* 如果名字特别长允许稍微撑开一点 */
    resize: none;     /* 禁止用户手动拖拽大小，保持界面整洁 */
    line-height: 24px;
    padding-right: 15px;
}
      /* 地址通用输入框基础样式结束 */    
        
        

        /* 图片上传区域样式 */
        .image-upload-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .preview-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #eee;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .remove-btn {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }

        .add-btn-wrapper {
            width: 80px;
            height: 80px;
            border: 1px dashed #ccc;
            border-radius: 6px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            background-color: #fafafa;
            transition: all 0.2s;
            color: #999;
            font-size: 24px;
            font-weight: bold;
        }
        .add-btn-wrapper:hover {
            border-color: #07c160;
            color: #07c160;
            background-color: #f0fff4;
        }
        .add-btn-wrapper.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f5f5f5;
            border-color: #eee;
            color: #ccc;
        }

        #imageUpload { display: none; }
        .status-text { width: 100%; font-size: 12px; color: #999; margin-top: 8px; text-align: right; }
        .btn-submit { width: 100%; padding: 14px; background-color: #07c160; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .btn-submit:disabled { background-color: #ccc; cursor: not-allowed; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #999; text-decoration: none; font-size: 14px; }
        
        .debug-info { background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 12px; word-break: break-all; }
        
        
        
        
        .action-bar {
    display: flex;             /* 启用 Flex 布局 */
    justify-content: space-between; /* 关键：两端对齐（左一个，右一个） */
    align-items: center;       /* 垂直居中（可选，防止高度不一致） */
    margin-top: 20px;          /* 可选：增加一点上边距 */
    
}

/* 给“取消”链接添加左边距 */
.btn-cancel { 
    /* 注意：你原来的 HTML 中这个类名写成了 btn-submit，建议改回 btn-cancel 以便区分 */
    margin-left: 15px; 
}

/* 或者，如果你不想改类名，可以直接针对第二个元素 */
.action-bar a {
    margin-left: 15px;
    text-decoration: none;
}

/* 明暗模式切换按钮 */
.theme-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #4a90e2;
    color: #fff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: all 0.3s ease;
}
.theme-toggle:hover {
    transform: scale(1.1);
    background: #357abd;
}
.theme-toggle svg {
    width: 24px;
    height: 24px;
}

/* 深色模式样式 */
body.dark-mode {
    background-color: #1a1a1a;
}
body.dark-mode .card {
    background: #2d2d2d;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
body.dark-mode h2 {
    color: #e0e0e0;
}
body.dark-mode .back-btn {
    color: #b0b0b0;
}
body.dark-mode .form-group label {
    color: #e0e0e0;
}
body.dark-mode textarea,
body.dark-mode .form-control {
    background: #3d3d3d;
    color: #e0e0e0;
    border-color: #555;
}
body.dark-mode textarea:focus,
body.dark-mode .form-control:focus {
    border-color: #4a90e2;
}
body.dark-mode textarea::placeholder,
body.dark-mode .form-control::placeholder {
    color: #888;
}
body.dark-mode .add-btn-wrapper {
    background-color: #3d3d3d;
    border-color: #555;
    color: #888;
}
body.dark-mode .add-btn-wrapper:hover {
    border-color: #07c160;
    background-color: #1e3a2f;
}
body.dark-mode .add-btn-wrapper.disabled {
    background-color: #2d2d2d;
    border-color: #444;
    color: #666;
}
body.dark-mode .status-text {
    color: #888;
}
body.dark-mode .preview-item {
    border-color: #444;
}
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <a href="../index.php" class="back-btn">
                <svg class="back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                返回主页
            </a>
            <h2>发表说说</h2>
        </div>

        <?php if (isset($error_msg)): ?>
            <div style="color:red; margin-bottom:15px; text-align:center; border:1px solid red; padding:10px; border-radius:5px;">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- 移除 onsubmit，改用 JS 监听 -->
        <form id="postForm" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>此刻想法</label>
                <textarea name="content" placeholder="这一刻的想法..." required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                
               <textarea name="music" placeholder="地址：浙江"  required  class="form-control music-input"><?php echo isset($_POST['music']) ? htmlspecialchars($_POST['music']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label>配图 (最多9张)</label>
                
                <div class="image-upload-container" id="imageContainer">
                    <!-- 预览图将在这里生成 -->
                    <div class="add-btn-wrapper" id="addBtn" onclick="triggerUpload()">
                        +
                    </div>
                </div>
                
                <div class="status-text" id="imageCount">已选择 0 张</div>

                <!-- 真实的文件输入框，用于最终提交 -->
                <input type="file" name="images[]" id="imageUpload" accept="image/*">
            </div>

           <!-- 建议给包裹这两个元素的父容器加一个类，例如 class="action-bar" -->
<div class="action-bar">
    <button type="submit" class="btn-submit" id="submitBtn">立即发表</button>
    <a href="../index.php" style="text-align: center;" class="btn-submit">取消</a>
</div>
        </form>
    </div>
</div>

<script>
    const MAX_IMAGES = 9;
    let selectedFiles = []; // 存储 File 对象

    // 1. 触发选择
    function triggerUpload() {
        if (selectedFiles.length >= MAX_IMAGES) return;
        document.getElementById('imageUpload').click();
    }

    // 2. 处理文件选择 (追加模式)
    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            if (!file.type.match('image.*')) {
                alert('请选择图片文件');
                return;
            }

            if (selectedFiles.length < MAX_IMAGES) {
                selectedFiles.push(file);
                renderPreviews();
            } else {
                alert('最多只能上传9张图片');
            }
        }
        // 重置 input，允许重复选择同一文件名（如果需要）
        input.value = ''; 
    }

    // 3. 渲染预览
    function renderPreviews() {
        const container = document.getElementById('imageContainer');
        const addBtn = document.getElementById('addBtn');
        const countDiv = document.getElementById('imageCount');
        
        // 暂时移除添加按钮
        container.removeChild(addBtn);
        container.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                
                const removeBtn = document.createElement('div');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() {
                    removeImage(index);
                };
                
                itemDiv.appendChild(img);
                itemDiv.appendChild(removeBtn);
                container.appendChild(itemDiv);
            };
            reader.readAsDataURL(file);
        });

        // 加回添加按钮
        container.appendChild(addBtn);

        // 更新状态
        const count = selectedFiles.length;
        countDiv.textContent = `已选择 ${count} 张 / 最多 ${MAX_IMAGES} 张`;

        if (count >= MAX_IMAGES) {
            addBtn.classList.add('disabled');
            addBtn.innerHTML = '✓';
        } else {
            addBtn.classList.remove('disabled');
            addBtn.innerHTML = '+';
        }
    }

    // 4. 删除图片
    function removeImage(index) {
        selectedFiles.splice(index, 1);
        renderPreviews();
    }

    // 5. 【核心修复】拦截表单提交，将 JS 数组中的文件注入到 input 中
    document.getElementById('postForm').addEventListener('submit', function(e) {
        if (selectedFiles.length === 0) {
            // 如果没有图片，允许直接提交（只发文字）
            return; 
        }

        // 防止默认提交，我们要手动构建 FormData
        e.preventDefault();

        const form = this;
        const submitBtn = document.getElementById('submitBtn');
        
        // 禁用按钮防止重复提交
        submitBtn.disabled = true;
        submitBtn.textContent = '发表中...';

        // 创建 DataTransfer 对象 (现代浏览器支持)
        const dataTransfer = new DataTransfer();
        
        // 将所有选中的文件放入 DataTransfer
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });

        // 将 files 列表赋值给隐藏的 input 元素
        // 这样后端 $_FILES['images'] 就能接收到了
        const fileInput = document.getElementById('imageUpload');
        fileInput.files = dataTransfer.files;

        // 现在使用 fetch 或 原生表单提交均可
        // 为了兼容性和简单性，我们这里重新触发一次原生提交
        // 因为 input.files 已经被我们修改了，浏览器会带着新文件提交
        form.submit();
    });

    // 绑定 input 事件监听
    document.getElementById('imageUpload').addEventListener('change', function() {
        handleFileSelect(this);
    });
</script>

<!-- 明暗模式切换按钮 -->
<button class="theme-toggle" id="themeToggle" title="切换明暗模式">
    <svg id="lightIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="none" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12a4 4 0 1 0 8 0a4 4 0 1 0-8 0m-5 0h1m8-9v1m8 8h1m-9 8v1M5.6 5.6l.7.7m12.1-.7l-.7.7m0 11.4l.7.7m-12.1-.7l-.7.7"/></svg>
    <svg id="darkIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="display:none;"><g fill="none" stroke="#ffffff" stroke-width="2"><path d="M20.958 15.325c.204-.486-.379-.9-.868-.684a7.7 7.7 0 0 1-3.101.648c-4.185 0-7.577-3.324-7.577-7.425a7.3 7.3 0 0 1 1.134-3.91c.284-.448-.057-1.068-.577-.936C5.96 4.041 3 7.613 3 11.862C3 16.909 7.175 21 12.326 21c3.9 0 7.24-2.345 8.632-5.675Z"/><path d="M15.611 3.103c-.53-.354-1.162.278-.809.808l.63.945a2.33 2.33 0 0 1 0 2.588l-.63.945c-.353.53.28 1.162.81.808l.944-.63a2.33 2.33 0 0 1 2.588 0l.945.63c.53.354 1.162-.278.808-.808l-.63-.945a2.33 2.33 0 0 1 0-2.588l.63-.945c.354-.53-.278-1.162-.809-.808l-.944.63a2.33 2.33 0 0 1-2.588 0z"/></g></svg>
</button>

<script>
    // 明暗模式切换功能
    (function() {
        const themeToggle = document.getElementById('themeToggle');
        const lightIcon = document.getElementById('lightIcon');
        const darkIcon = document.getElementById('darkIcon');
        const body = document.body;
        
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        function applyTheme(theme) {
            if (theme === 'dark') {
                body.classList.add('dark-mode');
                lightIcon.style.display = 'none';
                darkIcon.style.display = 'block';
            } else {
                body.classList.remove('dark-mode');
                lightIcon.style.display = 'block';
                darkIcon.style.display = 'none';
            }
        }
        
        applyTheme(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const isDark = body.classList.contains('dark-mode');
            const newTheme = isDark ? 'light' : 'dark';
            
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
    })();
</script>

</body>
</html>