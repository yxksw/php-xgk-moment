<?php
/**
 * 搜索接口 - 搜索朋友圈说说内容
 * 
 * 接口地址: /search.php
 * 请求方式: GET
 * 参数: keyword (搜索关键词)
 * 返回格式: JSON
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 引入配置文件
include 'config.php';
include 'includes/functions.php';

// 获取搜索关键词
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if (empty($keyword)) {
    echo json_encode([
        'code' => 400,
        'message' => '请输入搜索关键词',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 使用预处理语句防止 SQL 注入
    $searchTerm = "%{$keyword}%";
    $stmt = $conn->prepare("SELECT * FROM posts WHERE content LIKE ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($post = $result->fetch_assoc()) {
        // 解析图片
        $images = json_decode($post['images'], true) ?: [];
        
        $posts[] = [
            'id' => intval($post['id']),
            'content' => $post['content'],
            'created_at' => $post['created_at'],
            'formatted_date' => date('Y年m月d日', strtotime($post['created_at'])),
            'images_count' => count($images),
            'is_pinned' => intval($post['is_pinned'] ?? 0),
            'is_ad' => intval($post['is_ad'] ?? 0) > 0
        ];
    }
    
    echo json_encode([
        'code' => 200,
        'message' => 'success',
        'data' => $posts
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '搜索出错: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
