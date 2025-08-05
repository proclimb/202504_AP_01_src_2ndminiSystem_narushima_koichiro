<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Db.php'; // Db.php を読み込む（PDOインスタンスが生成される）

global $pdo; // Db.php で生成された $pdo を使えるようにする

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$type = $data['type'] ?? null;

if (!$user_id || !in_array($type, ['front', 'back'])) {
    echo json_encode(['success' => false, 'error' => '不正なリクエスト']);
    exit;
}

$column_image = $type === 'front' ? 'front_image' : 'back_image';
$column_name  = $type === 'front' ? 'front_image_name' : 'back_image_name';

try {
    $sql = "UPDATE user_documents SET {$column_image} = NULL, {$column_name} = NULL WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
