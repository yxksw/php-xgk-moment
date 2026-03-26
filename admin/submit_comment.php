<?php
header('Content-Type: application/json; charset=utf-8');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '无效请求']);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$content = trim($_POST['content'] ?? '');

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

$stmt = $conn->prepare("INSERT INTO comments (post_id, name, email, content) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $post_id, $name, $email, $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '评论失败，请稍后再试']);
}