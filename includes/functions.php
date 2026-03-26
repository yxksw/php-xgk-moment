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
    $images = handleImageUploads();
    $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES);
    $music = $_POST['music'] ?? '';

    $stmt = $conn->prepare("INSERT INTO posts (content, images, music) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $content, $imagesJson, $music);
    
    if ($stmt->execute()) {
       header('Location: ../index.php');
        exit;
    }
    return false;
}
?>