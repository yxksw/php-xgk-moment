<?php
session_start();
include '../config.php';

header('Content-Type: application/json; charset=utf-8');

// 获取参数
$do = isset($_GET['do']) ? $_GET['do'] : '';
$postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$anonymousId = isset($_GET['anonymous_id']) ? $_GET['anonymous_id'] : '';
$author = isset($_GET['author']) ? trim($_GET['author']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if ($postId <= 0) {
    echo json_encode(['success' => false, 'message' => '无效的文章ID']);
    exit;
}

// 如果没有匿名ID，生成一个
if (empty($anonymousId)) {
    $anonymousId = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
}

// 获取点赞数据
if ($do === 'getLikes') {
    // 获取点赞总数
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];

    // 获取点赞用户列表（前3个）
    $stmt = $conn->prepare("SELECT author FROM likes WHERE post_id = ? AND author IS NOT NULL ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $likeUsers = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['author'])) {
            $likeUsers[] = ['author' => $row['author']];
        }
    }

    // 检查当前用户是否已点赞
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND anonymous_id = ?");
    $stmt->bind_param("is", $postId, $anonymousId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isLiked = $result->num_rows > 0;

    echo json_encode([
        'success' => true,
        'likes' => intval($total),
        'isLiked' => $isLiked,
        'likeUsers' => $likeUsers
    ]);
    exit;
}

// 切换点赞状态
if ($do === 'like') {
    // 检查是否已点赞
    $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND anonymous_id = ?");
    $stmt->bind_param("is", $postId, $anonymousId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isLiked = $result->num_rows > 0;

    if ($isLiked) {
        // 取消点赞
        $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND anonymous_id = ?");
        $stmt->bind_param("is", $postId, $anonymousId);
        $stmt->execute();
        $action = 'unliked';
    } else {
        // 添加点赞
        $stmt = $conn->prepare("INSERT INTO likes (post_id, anonymous_id, author, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $postId, $anonymousId, $author, $email);
        $stmt->execute();
        $action = 'liked';
    }

    // 获取最新的点赞数据
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];

    // 获取点赞用户列表
    $stmt = $conn->prepare("SELECT author FROM likes WHERE post_id = ? AND author IS NOT NULL ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $likeUsers = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['author'])) {
            $likeUsers[] = ['author' => $row['author']];
        }
    }

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => intval($total),
        'isLiked' => !$isLiked,
        'likeUsers' => $likeUsers
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => '无效的操作']);
exit;
