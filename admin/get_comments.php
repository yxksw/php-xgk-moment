<?php
// 确保这一行是文件的第一行，前面不能有空格、回车、BOM！
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

include '../config.php';

// 获取博主邮箱
$adminEmail = '';
$stmt = $conn->prepare("SELECT value FROM settings WHERE name = 'admin_email'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $adminEmail = $result->fetch_assoc()['value'] ?? '';
}

if (!isset($_GET['post_id']) || !is_numeric($_GET['post_id'])) {
    echo json_encode(['comments' => []]);
    exit;
}

$post_id = (int)$_GET['post_id'];

$stmt = $conn->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $email = $row['email'];
    if (preg_match('/^(\d+)@qq\.com$/', $email, $m)) {
        $avatar = "https://q1.qlogo.cn/g?b=qq&nk={$m[1]}&s=100";
    } else {
        $hash = md5(strtolower(trim($email)));
        $avatar = "https://www.gravatar.com/avatar/{$hash}?d=mp&s=100";
    }
    
    // 判断是否作者（博主）- 根据邮箱匹配
    $isAuthor = !empty($adminEmail) && strtolower($adminEmail) === strtolower($email);
    
    $comments[] = [
        'id' => intval($row['id']),
        'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
        'content' => nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8')),
        'avatar' => $avatar,
        'created_at' => $row['created_at'],
        'is_author' => $isAuthor
    ];
}

echo json_encode(['comments' => $comments], JSON_UNESCAPED_UNICODE);
exit;