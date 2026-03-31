<?php
// functions.php

// 获取设置项
function getSetting($conn, $name) {
    $stmt = $conn->prepare("SELECT value FROM settings WHERE name = ?");
    if (!$stmt) return '';
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['value'] ?? '';
}

// 处理图片上传
function handleImageUploads() {
    $images = [];
    $maxImages = 9;
    
    // 【关键修改】
    // __DIR__ 是当前文件 (functions.php) 的绝对路径: /.../includes
    // dirname(__DIR__) 是上一级目录 (网站根目录): /.../
    // 拼接后得到网站根目录下的 upload 文件夹绝对路径
    $baseDir = dirname(__DIR__); 
    $uploadDir = $baseDir . '/upload/';
    
    // 确保目录存在 (现在会检查根目录下的 upload)
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            // 检查数量限制
            if (count($images) >= $maxImages) break;

            if ($_FILES['images']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $ext = strtolower($ext);
                
                // 允许的类型
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $filename = uniqid('img_') . '.' . $ext;
                    
                    // 目标物理路径 (绝对路径)
                    $targetPath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $targetPath)) {
                        // 【重要】存入数组的路径必须是相对网站根目录的 URL 路径
                        // 这样前端才能通过 https://域名/upload/filename.jpg 访问
                        $images[] = 'upload/' . $filename;
                    } else {
                        // 如果移动失败，记录错误以便调试
                        error_log("Failed to move file to: " . $targetPath);
                    }
                }
            }
        }
    }
    return $images;
}

// 处理发表说说
function handlePostSubmission($conn) {
    if (!isset($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['content'])) {
        return false;
    }

    $content = trim($_POST['content']);
    
    // 处理上传的图片
    $uploadedImages = handleImageUploads();
    
    // 处理外链图片
    $externalImages = [];
    if (!empty($_POST['external_images'])) {
        $externalImages = json_decode($_POST['external_images'], true) ?: [];
        // 验证外链图片 URL
        $externalImages = array_filter($externalImages, function($url) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        });
    }
    
    // 合并上传的图片和外链图片
    $allImages = array_merge($uploadedImages, $externalImages);
    
    // 限制最多 9 张图片
    $allImages = array_slice($allImages, 0, 9);
    
    $imagesJson = json_encode($allImages, JSON_UNESCAPED_SLASHES);
    $music = $_POST['music'] ?? '';
    $isPinned = isset($_POST['is_pinned']) ? intval($_POST['is_pinned']) : 0;
    $isMarked = isset($_POST['is_marked']) ? intval($_POST['is_marked']) : 0;
    
    // 确保置顶值在有效范围内 (0-3)
    $isPinned = max(0, min(3, $isPinned));
    // 确保标记值在有效范围内 (0-3)
    $isMarked = max(0, min(3, $isMarked));

    $stmt = $conn->prepare("INSERT INTO posts (content, images, music, is_pinned, is_marked) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $content, $imagesJson, $music, $isPinned, $isMarked);
    
    if ($stmt->execute()) {
       header('Location: ../index.php');
        exit;
    }
    return false;
}

// 处理编辑说说
function handlePostUpdate($conn, $postId) {
    if (!isset($_SESSION['admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['content'])) {
        return false;
    }

    // 获取原有说说数据
    $stmt = $conn->prepare("SELECT images FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingPost = $result->fetch_assoc();

    if (!$existingPost) {
        return "说说不存在";
    }

    $content = trim($_POST['content']);

    // 处理新上传的图片
    $newUploadedImages = handleImageUploads();

    // 处理外链图片
    $externalImages = [];
    if (!empty($_POST['external_images'])) {
        $externalImages = json_decode($_POST['external_images'], true) ?: [];
        // 验证外链图片 URL
        $externalImages = array_filter($externalImages, function($url) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false;
        });
    }

    // 获取保留的已上传图片
    $existingImages = json_decode($existingPost['images'], true) ?: [];
    $deletedImages = [];
    if (!empty($_POST['deleted_images'])) {
        $deletedImages = json_decode($_POST['deleted_images'], true) ?: [];
    }

    // 过滤掉被删除的图片
    $keptImages = array_filter($existingImages, function($img) use ($deletedImages) {
        return !in_array($img, $deletedImages);
    });

    // 删除物理文件
    $baseDir = dirname(__DIR__);
    foreach ($deletedImages as $deletedImg) {
        // 只删除本地文件，不删除外链图片
        if (strpos($deletedImg, 'http://') !== 0 && strpos($deletedImg, 'https://') !== 0) {
            $filePath = $baseDir . '/' . $deletedImg;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    // 合并所有图片：保留的 + 新上传的 + 外链的
    $allImages = array_merge(array_values($keptImages), $newUploadedImages, $externalImages);

    // 限制最多 9 张图片
    $allImages = array_slice($allImages, 0, 9);

    $imagesJson = json_encode($allImages, JSON_UNESCAPED_SLASHES);
    $music = $_POST['music'] ?? '';
    $isPinned = isset($_POST['is_pinned']) ? intval($_POST['is_pinned']) : 0;
    $isMarked = isset($_POST['is_marked']) ? intval($_POST['is_marked']) : 0;

    // 确保置顶值在有效范围内 (0-3)
    $isPinned = max(0, min(3, $isPinned));
    // 确保标记值在有效范围内 (0-3)
    $isMarked = max(0, min(3, $isMarked));

    $stmt = $conn->prepare("UPDATE posts SET content = ?, images = ?, music = ?, is_pinned = ?, is_marked = ? WHERE id = ?");
    $stmt->bind_param("sssiii", $content, $imagesJson, $music, $isPinned, $isMarked, $postId);

    if ($stmt->execute()) {
        return true;
    }
    return "更新失败: " . $stmt->error;
}
?>