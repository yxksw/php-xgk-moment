<?php
/**
 * 搜索接口 - 搜索朋友圈说说内容（优化版）
 * 
 * 接口地址: /search.php
 * 请求方式: GET
 * 参数: 
 *   - keyword (搜索关键词)
 *   - page (页码，默认1)
 *   - limit (每页数量，默认20，最大50)
 * 返回格式: JSON
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 引入配置文件
include 'config.php';
include 'includes/functions.php';

// 获取搜索参数
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

if (empty($keyword)) {
    echo json_encode([
        'code' => 400,
        'message' => '请输入搜索关键词',
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 限制关键词长度
if (mb_strlen($keyword) > 100) {
    $keyword = mb_substr($keyword, 0, 100);
}

try {
    // 生成缓存键
    $cacheKey = 'search_' . md5($keyword . '_' . $page . '_' . $limit);
    $cacheFile = 'cache/' . $cacheKey . '.json';
    $cacheTime = 300; // 缓存5分钟
    
    // 检查缓存
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cached = file_get_contents($cacheFile);
        if ($cached !== false) {
            echo $cached;
            exit;
        }
    }
    
    // 确保缓存目录存在
    if (!is_dir('cache')) {
        mkdir('cache', 0755, true);
    }
    
    // 优化：只选择需要的字段，避免 SELECT *
    $searchTerm = "%{$keyword}%";
    
    // 获取总数（使用覆盖索引优化）
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE content LIKE ?");
    $countStmt->bind_param("s", $searchTerm);
    $countStmt->execute();
    $totalResult = $countStmt->get_result()->fetch_assoc();
    $total = intval($totalResult['total']);
    
    // 获取分页数据
    $stmt = $conn->prepare("SELECT id, content, images, created_at, is_pinned, is_marked 
                           FROM posts 
                           WHERE content LIKE ? 
                           ORDER BY created_at DESC 
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $searchTerm, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($post = $result->fetch_assoc()) {
        // 优化：只解析图片数量，不解析完整图片数组
        $imagesCount = 0;
        if (!empty($post['images'])) {
            $images = json_decode($post['images'], true);
            $imagesCount = is_array($images) ? count($images) : 0;
        }
        
        // 优化：截断过长的内容
        $content = $post['content'];
        $isTruncated = false;
        if (mb_strlen($content) > 200) {
            $content = mb_substr($content, 0, 200) . '...';
            $isTruncated = true;
        }
        
        $posts[] = [
            'id' => intval($post['id']),
            'content' => $content,
            'full_content' => $isTruncated ? $post['content'] : null,
            'created_at' => $post['created_at'],
            'formatted_date' => date('Y年m月d日', strtotime($post['created_at'])),
            'images_count' => $imagesCount,
            'is_pinned' => intval($post['is_pinned'] ?? 0),
            'is_marked' => intval($post['is_marked'] ?? 0)
        ];
    }
    
    $response = [
        'code' => 200,
        'message' => 'success',
        'data' => [
            'posts' => $posts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit),
                'has_more' => ($page * $limit) < $total
            ],
            'keyword' => $keyword
        ]
    ];
    
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    // 保存缓存
    file_put_contents($cacheFile, $jsonResponse, LOCK_EX);
    
    // 清理旧缓存文件（保留最近100个）
    cleanupOldCache('cache/', 100);
    
    echo $jsonResponse;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'message' => '搜索出错: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 清理旧缓存文件
 */
function cleanupOldCache($cacheDir, $keepCount = 100) {
    $files = glob($cacheDir . '*.json');
    if (count($files) > $keepCount * 2) {
        // 按修改时间排序
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // 删除旧文件
        $filesToDelete = array_slice($files, $keepCount);
        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
?>
