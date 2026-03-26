<?php
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit('Forbidden');
}
include '../config.php';

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT id, content, images, music FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if ($post) {
    echo json_encode($post);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>