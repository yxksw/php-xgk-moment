<?php
header('Content-Type: application/json; charset=utf-8');
include '../config.php';
include '../includes/mail_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效请求']);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');
$parent_id = (int)($_POST['parent_id'] ?? 0); // 回复的评论ID

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => '缺少说说ID']);
    exit;
}
if (!$name || strlen($name) > 50) {
    echo json_encode(['success' => false, 'message' => '名字不能为空且不超过50字']);
    exit;
}
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
    exit;
}
if (!$content || strlen($content) > 500) {
    echo json_encode(['success' => false, 'message' => '评论内容不能为空且不超过500字']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (post_id, name, email, content, parent_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("isssi", $post_id, $name, $email, $content, $parent_id);

if ($stmt->execute()) {
    $comment_id = $stmt->insert_id;
    
    // 发送邮件通知（异步处理，不阻塞响应）
    if (function_exists('fastcgi_finish_request')) {
        // 对于PHP-FPM，先发送响应再处理邮件
        echo json_encode(['success' => true]);
        fastcgi_finish_request();
        sendMailNotifications($conn, $comment_id, $post_id, $name, $content, $parent_id);
    } else {
        // 普通方式：先发送邮件再返回响应
        sendMailNotifications($conn, $comment_id, $post_id, $name, $content, $parent_id);
        echo json_encode(['success' => true]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '评论失败，请稍后再试']);
}

// 发送邮件通知函数
function sendMailNotifications($conn, $comment_id, $post_id, $name, $content, $parent_id) {
    try {
        $mailNotifier = new MailNotifier($conn);
        
        // 1. 通知博主（有新评论时）
        $mailNotifier->notifyAdminOnComment($comment_id, $post_id, $name, $content);
        
        // 2. 如果是回复评论，通知被回复的用户
        if ($parent_id > 0) {
            $mailNotifier->notifyUserOnReply($parent_id, $comment_id, $name, $content, $post_id);
        }
    } catch (Exception $e) {
        // 记录错误日志但不影响主流程
        $logFile = __DIR__ . '/../logs/mail_error.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [ERROR] 邮件通知发送失败: " . $e->getMessage() . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
