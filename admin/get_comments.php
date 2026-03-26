<?php
// 确保这一行是文件的第一行，前面不能有空格、回车、BOM！
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

include '../config.php';

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
        $avatar = "https://q1.qlogo.cn/g?b=qq&nk={$m[1]}&s=40";
    } else {
        $hash = md5(strtolower(trim($email)));
        $avatar = "https://www.gravatar.com/avatar/{$hash}?d=mp&s=40";
    }
    $comments[] = [
        'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
        'content' => nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8')),
        'avatar' => $avatar
    ];
}

echo json_encode(['comments' => $comments], JSON_UNESCAPED_UNICODE);
exit;