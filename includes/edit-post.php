<?php
// Disable caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

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

// 获取说说ID
$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($postId <= 0) {
    header('Location: ../index.php');
    exit;
}

// 获取说说数据
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    header('Location: ../index.php');
    exit;
}

// 解析现有图片
$existingImages = json_decode($post['images'], true) ?: [];
$uploadedImages = [];
$externalImages = [];

foreach ($existingImages as $img) {
    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
        $externalImages[] = $img;
    } else {
        $uploadedImages[] = $img;
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('handlePostUpdate')) {
        $error_msg = "系统错误：更新函数未加载";
    } else {
        $result = handlePostUpdate($conn, $postId);

        if ($result === true) {
            ob_end_clean();
            header('Location: ../index.php');
            exit;
        } else {
            $error_msg = "保存失败：" . ($result === false ? "未知错误" : $result);
        }
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
    <title>编辑说说 - <?php echo htmlspecialchars($site_title); ?></title>

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

/* 给"取消"链接添加左边距 */
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

        /* 置顶选项样式 */
        .pin-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
        }

        .pin-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.2s;
            background: #fafafa;
        }

        .pin-option:hover {
            border-color: #07c160;
            background: #f0fff4;
        }

        .pin-option input[type="radio"] {
            margin-right: 6px;
            cursor: pointer;
        }

        .pin-label {
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }

        .pin-option input[type="radio"]:checked + .pin-label {
            color: #07c160;
            font-weight: 600;
        }

        .pin-option:has(input[type="radio"]:checked) {
            border-color: #07c160;
            background: #e6f7ed;
        }

        .pin-slot-1 { color: #ff6b6b; }
        .pin-slot-2 { color: #4ecdc4; }
        .pin-slot-3 { color: #45b7d1; }

        .pin-option:has(input[value="1"]:checked) {
            border-color: #ff6b6b;
            background: #fff0f0;
        }

        .pin-option:has(input[value="2"]:checked) {
            border-color: #4ecdc4;
            background: #f0fffe;
        }

        .pin-option:has(input[value="3"]:checked) {
            border-color: #45b7d1;
            background: #f0f9ff;
        }

        .pin-hint {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
            font-style: italic;
        }

        /* 深色模式下的置顶选项样式 */
        body.dark-mode .pin-option {
            border-color: #555;
            background: #3d3d3d;
        }

        body.dark-mode .pin-option:hover {
            border-color: #07c160;
            background: #1e3a2f;
        }

        body.dark-mode .pin-label {
            color: #e0e0e0;
        }

        body.dark-mode .pin-option:has(input[type="radio"]:checked) {
            background: #2d4a3e;
        }

        body.dark-mode .pin-option:has(input[value="1"]:checked) {
            background: #4a3e3e;
            border-color: #ff6b6b;
        }

        body.dark-mode .pin-option:has(input[value="2"]:checked) {
            background: #3e4a4a;
            border-color: #4ecdc4;
        }

        body.dark-mode .pin-option:has(input[value="3"]:checked) {
            background: #3e4a5a;
            border-color: #45b7d1;
        }

        body.dark-mode .pin-hint {
            color: #888;
        }

        /* 标记选项样式 */
        .mark-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
        }

        .mark-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.2s;
            background: #fafafa;
        }

        .mark-option:hover {
            border-color: #ff9500;
            background: #fff8f0;
        }

        .mark-option input[type="radio"] {
            margin-right: 6px;
            cursor: pointer;
        }

        .mark-label {
            font-size: 14px;
            color: #555;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .mark-option:has(input[type="radio"]:checked) {
            border-color: #ff9500;
            background: #fff0e0;
        }

        .mark-option:has(input[type="radio"]:checked) .mark-label {
            color: #ff9500;
            font-weight: 600;
        }

        /* 标记栏位颜色 */
        .mark-slot-1 { color: #ff6b6b; }
        .mark-slot-2 { color: #4ecdc4; }
        .mark-slot-3 { color: #45b7d1; }

        .mark-option:has(input[value="1"]:checked) {
            border-color: #ff6b6b;
            background: #fff0f0;
        }

        .mark-option:has(input[value="2"]:checked) {
            border-color: #4ecdc4;
            background: #f0fffe;
        }

        .mark-option:has(input[value="3"]:checked) {
            border-color: #45b7d1;
            background: #f0f9ff;
        }

        .mark-hint {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
            font-style: italic;
        }

        /* 外链图片样式 */
        .external-image-section {
            margin-bottom: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px dashed #ddd;
        }

        .external-image-input-wrapper {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }

        .external-image-input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }

        .external-image-input:focus {
            border-color: #07c160;
        }

        .add-external-btn {
            padding: 10px 16px;
            background: #07c160;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            white-space: nowrap;
        }

        .add-external-btn:hover {
            background: #06b359;
        }

        .add-external-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .external-image-hint {
            font-size: 12px;
            color: #999;
            font-style: italic;
        }

        /* 深色模式下的标记选项样式 */
        body.dark-mode .mark-option {
            border-color: #555;
            background: #3d3d3d;
        }

        body.dark-mode .mark-option:hover {
            border-color: #ff9500;
            background: #3d3520;
        }

        body.dark-mode .mark-label {
            color: #e0e0e0;
        }

        body.dark-mode .mark-option:has(input[type="radio"]:checked) {
            background: #4a3e2e;
            border-color: #ff9500;
        }

        body.dark-mode .mark-option:has(input[value="1"]:checked) {
            background: #4a3e3e;
            border-color: #ff6b6b;
        }

        body.dark-mode .mark-option:has(input[value="2"]:checked) {
            background: #3e4a4a;
            border-color: #4ecdc4;
        }

        body.dark-mode .mark-option:has(input[value="3"]:checked) {
            background: #3e4a5a;
            border-color: #45b7d1;
        }

        body.dark-mode .mark-hint {
            color: #888;
        }

        /* 深色模式下的外链图片样式 */
        body.dark-mode .external-image-section {
            background: #3d3d3d;
            border-color: #555;
        }

        body.dark-mode .external-image-input {
            background: #2d2d2d;
            border-color: #555;
            color: #e0e0e0;
        }

        body.dark-mode .external-image-input::placeholder {
            color: #888;
        }

        /* 移动端适配 */
        @media (max-width: 576px) {
            .ad-options {
                gap: 8px;
            }

            .ad-option {
                padding: 10px 8px;
                flex: 1;
                min-width: calc(50% - 4px);
                justify-content: center;
            }

            .ad-label {
                font-size: 13px;
                white-space: nowrap;
            }

            .ad-option input[type="radio"] {
                margin-right: 4px;
            }

            .external-image-input-wrapper {
                flex-direction: column;
            }

            .add-external-btn {
                width: 100%;
            }
        }

        /* 深色模式下的广告选项样式 */
        body.dark-mode .ad-option {
            border-color: #555;
            background: #3d3d3d;
        }

        body.dark-mode .ad-option:hover {
            border-color: #ff9500;
            background: #3d3520;
        }

        body.dark-mode .ad-label {
            color: #e0e0e0;
        }

        body.dark-mode .ad-option:has(input[type="checkbox"]:checked) {
            background: #4a3e2e;
            border-color: #ff9500;
        }

        body.dark-mode .ad-hint {
            color: #888;
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
            <h2>编辑说说</h2>
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
                <textarea name="content" placeholder="这一刻的想法..." required><?php echo htmlspecialchars($post['content']); ?></textarea>

               <textarea name="music" placeholder="地址：浙江"  required  class="form-control music-input"><?php echo htmlspecialchars($post['music'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>配图 (最多9张)</label>

                <!-- 外链图片输入 -->
                <div class="external-image-section">
                    <div class="external-image-input-wrapper">
                        <input type="text" id="externalImageUrl" placeholder="输入图片外链地址 (https://...)" class="external-image-input">
                        <button type="button" class="add-external-btn" onclick="addExternalImage()">添加外链</button>
                    </div>
                    <div class="external-image-hint">* 支持 jpg, png, gif, webp 格式的外链图片</div>
                </div>

                <div class="image-upload-container" id="imageContainer">
                    <!-- 预览图将在这里生成 -->
                    <div class="add-btn-wrapper" id="addBtn" onclick="triggerUpload()">
                        +
                    </div>
                </div>

                <div class="status-text" id="imageCount">已选择 0 张 (已有 0 张, 新上传 0 张, 外链 0 张)</div>

                <!-- 真实的文件输入框，用于最终提交 -->
                <input type="file" name="images[]" id="imageUpload" accept="image/*">

                <!-- 隐藏字段，存储外链图片 URL -->
                <input type="hidden" name="external_images" id="externalImagesInput" value="">

                <!-- 隐藏字段，存储要删除的图片 -->
                <input type="hidden" name="deleted_images" id="deletedImagesInput" value="">
            </div>

            <!-- 置顶选项 -->
            <div class="form-group">
                <label>置顶设置</label>
                <div class="pin-options">
                    <label class="pin-option">
                        <input type="radio" name="is_pinned" value="0" <?php echo ($post['is_pinned'] ?? 0) == 0 ? 'checked' : ''; ?>>
                        <span class="pin-label">不置顶</span>
                    </label>
                    <label class="pin-option">
                        <input type="radio" name="is_pinned" value="1" <?php echo ($post['is_pinned'] ?? 0) == 1 ? 'checked' : ''; ?>>
                        <span class="pin-label pin-slot-1">置顶栏位 1</span>
                    </label>
                    <label class="pin-option">
                        <input type="radio" name="is_pinned" value="2" <?php echo ($post['is_pinned'] ?? 0) == 2 ? 'checked' : ''; ?>>
                        <span class="pin-label pin-slot-2">置顶栏位 2</span>
                    </label>
                    <label class="pin-option">
                        <input type="radio" name="is_pinned" value="3" <?php echo ($post['is_pinned'] ?? 0) == 3 ? 'checked' : ''; ?>>
                        <span class="pin-label pin-slot-3">置顶栏位 3</span>
                    </label>
                </div>
                <div class="pin-hint">* 置顶的说说会显示在最前面，三个栏位可分别设置不同的置顶内容</div>
            </div>

            <!-- 标记选项 -->
            <div class="form-group">
                <label>广告设置</label>
                <div class="mark-options">
                    <label class="mark-option">
                        <input type="radio" name="is_marked" value="0" <?php echo ($post['is_marked'] ?? 0) == 0 ? 'checked' : ''; ?>>
                        <span class="mark-label">不是广告</span>
                    </label>
                    <label class="mark-option">
                        <input type="radio" name="is_marked" value="1" <?php echo ($post['is_marked'] ?? 0) == 1 ? 'checked' : ''; ?>>
                        <span class="mark-label mark-slot-1">广告栏位 1</span>
                    </label>
                    <label class="mark-option">
                        <input type="radio" name="is_marked" value="2" <?php echo ($post['is_marked'] ?? 0) == 2 ? 'checked' : ''; ?>>
                        <span class="mark-label mark-slot-2">广告栏位 2</span>
                    </label>
                    <label class="mark-option">
                        <input type="radio" name="is_marked" value="3" <?php echo ($post['is_marked'] ?? 0) == 3 ? 'checked' : ''; ?>>
                        <span class="mark-label mark-slot-3">广告栏位 3</span>
                    </label>
                </div>
                <div class="mark-hint">* 广告内容会显示广告标识，三个栏位可分别设置不同的广告内容</div>
            </div>

           <!-- 建议给包裹这两个元素的父容器加一个类，例如 class="action-bar" -->
<div class="action-bar">
    <button type="submit" class="btn-submit" id="submitBtn">保存修改</button>
    <a href="../index.php" style="text-align: center;" class="btn-submit">取消</a>
</div>
        </form>
    </div>
</div>

<script>
    const MAX_IMAGES = 9;
    let selectedFiles = []; // 存储新上传的 File 对象
    let externalImages = <?php echo json_encode($externalImages); ?>; // 现有外链图片
    let uploadedImages = <?php echo json_encode($uploadedImages); ?>; // 现有上传图片
    let deletedImages = []; // 要删除的图片

    // 初始化渲染
    document.addEventListener('DOMContentLoaded', function() {
        renderPreviews();
    });

    // 1. 触发选择
    function triggerUpload() {
        const totalImages = selectedFiles.length + externalImages.length + uploadedImages.length;
        if (totalImages >= MAX_IMAGES) {
            alert('最多只能添加9张图片');
            return;
        }
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

            const totalImages = selectedFiles.length + externalImages.length + uploadedImages.length;
            if (totalImages < MAX_IMAGES) {
                selectedFiles.push(file);
                renderPreviews();
            } else {
                alert('最多只能添加9张图片');
            }
        }
        // 重置 input，允许重复选择同一文件名
        input.value = '';
    }

    // 3. 添加外链图片
    function addExternalImage() {
        const urlInput = document.getElementById('externalImageUrl');
        const url = urlInput.value.trim();

        if (!url) {
            alert('请输入图片链接');
            return;
        }

        // 验证 URL 格式
        if (!url.match(/^https?:\/\/.+/i)) {
            alert('请输入有效的图片链接 (以 http:// 或 https:// 开头)');
            return;
        }

        // 验证图片格式
        const validExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'];
        const hasValidExtension = validExtensions.some(ext => url.toLowerCase().includes(ext));

        if (!hasValidExtension && !url.match(/\.(jpg|jpeg|png|gif|webp|bmp)(\?.*)?$/i)) {
            alert('请确保链接是有效的图片格式 (jpg, png, gif, webp 等)');
        }

        const totalImages = selectedFiles.length + externalImages.length + uploadedImages.length;
        if (totalImages >= MAX_IMAGES) {
            alert('最多只能添加9张图片');
            return;
        }

        // 检查是否重复
        if (externalImages.includes(url)) {
            alert('该图片链接已添加');
            return;
        }

        externalImages.push(url);
        urlInput.value = '';
        renderPreviews();
    }

    // 4. 渲染预览
    function renderPreviews() {
        const container = document.getElementById('imageContainer');
        const addBtn = document.getElementById('addBtn');
        const countDiv = document.getElementById('imageCount');

        // 暂时移除添加按钮
        container.removeChild(addBtn);
        container.innerHTML = '';

        // 渲染外链图片
        externalImages.forEach((url, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'preview-item';

            const img = document.createElement('img');
            img.src = url;
            img.onerror = function() {
                this.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="%23f0f0f0"/><text x="50" y="50" text-anchor="middle" fill="%23999">图片加载失败</text></svg>';
            };

            // 外链标记
            const externalBadge = document.createElement('div');
            externalBadge.className = 'external-badge';
            externalBadge.innerHTML = '&#128279;';
            externalBadge.style.cssText = 'position:absolute;top:2px;left:2px;background:rgba(7,193,96,0.9);color:white;padding:2px 4px;border-radius:3px;font-size:10px;';

            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = function() {
                removeExternalImage(index);
            };

            itemDiv.appendChild(img);
            itemDiv.appendChild(externalBadge);
            itemDiv.appendChild(removeBtn);
            container.appendChild(itemDiv);
        });

        // 渲染已上传的图片
        uploadedImages.forEach((url, index) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'preview-item';

            const img = document.createElement('img');
            img.src = '../' + url;
            img.onerror = function() {
                this.src = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="%23f0f0f0"/><text x="50" y="50" text-anchor="middle" fill="%23999">图片加载失败</text></svg>';
            };

            // 已上传标记
            const localBadge = document.createElement('div');
            localBadge.className = 'local-badge';
            localBadge.innerHTML = '&#128190;';
            localBadge.style.cssText = 'position:absolute;top:2px;left:2px;background:rgba(74,144,226,0.9);color:white;padding:2px 4px;border-radius:3px;font-size:10px;';

            const removeBtn = document.createElement('div');
            removeBtn.className = 'remove-btn';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = function() {
                removeUploadedImage(index);
            };

            itemDiv.appendChild(img);
            itemDiv.appendChild(localBadge);
            itemDiv.appendChild(removeBtn);
            container.appendChild(itemDiv);
        });

        // 渲染新上传的图片
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'preview-item';

                const img = document.createElement('img');
                img.src = e.target.result;

                // 新上传标记
                const newBadge = document.createElement('div');
                newBadge.className = 'new-badge';
                newBadge.innerHTML = '&#127381;';
                newBadge.style.cssText = 'position:absolute;top:2px;left:2px;background:rgba(255,149,0,0.9);color:white;padding:2px 4px;border-radius:3px;font-size:10px;';

                const removeBtn = document.createElement('div');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() {
                    removeImage(index);
                };

                itemDiv.appendChild(img);
                itemDiv.appendChild(newBadge);
                itemDiv.appendChild(removeBtn);
                container.appendChild(itemDiv);
            };
            reader.readAsDataURL(file);
        });

        // 加回添加按钮
        container.appendChild(addBtn);

        // 更新状态
        const newUploadCount = selectedFiles.length;
        const externalCount = externalImages.length;
        const uploadedCount = uploadedImages.length;
        const totalCount = newUploadCount + externalCount + uploadedCount;
        countDiv.textContent = `已选择 ${totalCount} 张 (已有 ${uploadedCount} 张, 新上传 ${newUploadCount} 张, 外链 ${externalCount} 张)`;

        // 更新隐藏字段
        document.getElementById('externalImagesInput').value = JSON.stringify(externalImages);
        document.getElementById('deletedImagesInput').value = JSON.stringify(deletedImages);

        if (totalCount >= MAX_IMAGES) {
            addBtn.classList.add('disabled');
            addBtn.innerHTML = '&#10003;';
        } else {
            addBtn.classList.remove('disabled');
            addBtn.innerHTML = '+';
        }
    }

    // 5. 删除新上传的图片
    function removeImage(index) {
        selectedFiles.splice(index, 1);
        renderPreviews();
    }

    // 6. 删除外链图片
    function removeExternalImage(index) {
        externalImages.splice(index, 1);
        renderPreviews();
    }

    // 7. 删除已上传的图片
    function removeUploadedImage(index) {
        const removedUrl = uploadedImages[index];
        deletedImages.push(removedUrl);
        uploadedImages.splice(index, 1);
        renderPreviews();
    }

    // 8. 【核心修复】拦截表单提交
    document.getElementById('postForm').addEventListener('submit', function(e) {
        // 防止默认提交，我们要手动构建 FormData
        e.preventDefault();

        const form = this;
        const submitBtn = document.getElementById('submitBtn');

        // 禁用按钮防止重复提交
        submitBtn.disabled = true;
        submitBtn.textContent = '保存中...';

        // 创建 DataTransfer 对象
        const dataTransfer = new DataTransfer();

        // 将所有新选中的文件放入 DataTransfer
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });

        // 将 files 列表赋值给隐藏的 input 元素
        const fileInput = document.getElementById('imageUpload');
        fileInput.files = dataTransfer.files;

        // 更新隐藏字段
        document.getElementById('externalImagesInput').value = JSON.stringify(externalImages);
        document.getElementById('deletedImagesInput').value = JSON.stringify(deletedImages);

        // 提交表单
        form.submit();
    });

    // 绑定 input 事件监听
    document.getElementById('imageUpload').addEventListener('change', function() {
        handleFileSelect(this);
    });

    // 外链图片输入框回车事件
    document.getElementById('externalImageUrl').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addExternalImage();
        }
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
