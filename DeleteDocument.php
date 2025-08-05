<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Db.php';
global $pdo;

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
    // ① 該当カラムをNULLに更新
    $sql = "UPDATE user_documents SET {$column_image} = NULL, {$column_name} = NULL WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // ② 4カラムすべてがNULLかチェック
    $checkSql = "SELECT front_image, front_image_name, back_image, back_image_name FROM user_documents WHERE user_id = :user_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (
        $result &&
        is_null($result['front_image']) &&
        is_null($result['front_image_name']) &&
        is_null($result['back_image']) &&
        is_null($result['back_image_name'])
    ) {
        // ③ すべてNULLならDELETE
        $deleteSql = "DELETE FROM user_documents WHERE user_id = :user_id";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $deleteStmt->execute();
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
