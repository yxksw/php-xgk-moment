<?php
/**
 * JSON API 接口 - 获取说说列表
 * 
 * 接口地址: /api.php
 * 请求方式: GET
 * 返回格式: JSON
 * 
 * 可选参数:
 * - page: 页码 (默认: 1)
 * - limit: 每页数量 (默认: 10, 最大: 50)
 * - id: 指定说说ID (可选)
 * 
 * 返回示例:
 * {
 *   "code": 200,
 *   "message": "success",
 *   "data": {
 *     "total": 100,
 *     "page": 1,
 *     "limit": 10,
 *     "total_pages": 10,
 *     "posts": [
 *       {
 *         "id": 1,
 *         "content": "说说内容",
 *         "author": {
 *           "name": "作者名称",
 *           "avatar": "头像URL"
 *         },
 *         "created_at": "2024-01-01 12:00:00",
 *         "formatted_date": "2024年01月01日",
 *         "images": ["图片1.jpg", "图片2.jpg"],
 *         "music": "音乐链接",
 *         "comments_count": 5
 *       }
 *     ]
 *   }
 * }
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 引入配置文件
include 'config.php';
include 'includes/functions.php';

// 获取请求参数
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
$postId = isset($_GET['id']) ? intval($_GET['id']) : null;

// 获取站点设置
$site_title = getSetting($conn, 'site_title');
$friend_name = getSetting($conn, 'friend_name');
$friend_avatar = getSetting($conn, 'friend_avatar');

// 构建基础URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$base_url = $protocol . $_SERVER['HTTP_HOST'];

try {
    // 如果指定了ID，查询单条说说
    if ($postId) {
        $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(404);
            echo json_encode([
                'code' => 404,
                'message' => '说说不存在',
                'data' => null
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        
        $post = $result->fetch_assoc();
        $postData = formatPostData($post, $friend_name, $friend_avatar, $base_url, $conn);
        
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => $postData
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // 获取总数
    $totalResult = $conn->query("SELECT COUNT(*) as total FROM posts");
    $total = $totalResult->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    
    // 获取说说列表（按置顶排序，置顶级别高的在前）
    $stmt = $conn->prepare("SELECT * FROM posts ORDER BY is_pinned DESC, created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($post = $result->fetch_assoc()) {
        $posts[] = formatPostData($post, $friend_name, $friend_avatar, $base_url, $conn);
    }
    
    // 返回JSON数据
    $response = [
        'code' => 200,
        'message' => 'success',
        'data' => [
            'site_info' => [
                'title' => $site_title,
                'author' => $friend_name,
                'author_avatar' => $friend_avatar ? $base_url . '/' . $friend_avatar : null
            ],
            'pagination' => [
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'posts' => $posts
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误: ' . $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 格式化说说数据
 */
function formatPostData($post, $authorName, $authorAvatar, $baseUrl, $conn) {
    // 解析图片
    $images = json_decode($post['images'], true) ?: [];
    $fullImages = [];
    foreach ($images as $img) {
        // 确保图片URL是完整的
        if (strpos($img, 'http') === 0) {
            $fullImages[] = $img;
        } else {
            $fullImages[] = $baseUrl . '/' . ltrim($img, '/');
        }
    }
    
    // 获取评论数量
    $commentCount = 0;
    $commentStmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
    if ($commentStmt) {
        $commentStmt->bind_param("i", $post['id']);
        $commentStmt->execute();
        $commentResult = $commentStmt->get_result();
        $commentCount = $commentResult->fetch_assoc()['count'] ?? 0;
    }
    
    return [
        'id' => intval($post['id']),
        'content' => $post['content'],
        'author' => [
            'name' => $authorName ?: '匿名',
            'avatar' => $authorAvatar ? (strpos($authorAvatar, 'http') === 0 ? $authorAvatar : $baseUrl . '/' . ltrim($authorAvatar, '/')) : null
        ],
        'created_at' => $post['created_at'],
        'formatted_date' => date('Y年m月d日', strtotime($post['created_at'])),
        'timestamp' => strtotime($post['created_at']),
        'images' => $fullImages,
        'images_count' => count($fullImages),
        'music' => $post['music'] ?: null,
        'comments_count' => intval($commentCount),
        'is_pinned' => intval($post['is_pinned'] ?? 0),
        'pinned_slot' => intval($post['is_pinned'] ?? 0) > 0 ? intval($post['is_pinned']) : null,
        'is_marked' => intval($post['is_marked'] ?? 0) > 0,
        'marked_slot' => intval($post['is_marked'] ?? 0) > 0 ? intval($post['is_marked']) : null,
        'url' => $baseUrl . '/?id=' . $post['id']
    ];
}
