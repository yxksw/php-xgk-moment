<?php
// Disable caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header('Content-Type: application/json; charset=utf-8');

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include '../config.php';

$postId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

try {
    // First, get the post to delete associated images
    $stmt = $conn->prepare("SELECT images FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    if (!$post) {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit;
    }
    
    // Delete associated images (only local files, not external URLs)
    $images = json_decode($post['images'], true) ?: [];
    foreach ($images as $img) {
        // Only delete if it's a local file (not starting with http)
        if (!preg_match('/^https?:\/\//i', $img)) {
            $filePath = '../' . $img;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    
    // Delete the post
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->bind_param("i", $postId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
