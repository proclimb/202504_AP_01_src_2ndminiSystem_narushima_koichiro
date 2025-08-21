<?php
// Csvimport.php
// ──────────────────────────────────────────
// import_queue の queue_id を受け取り、CSVをDBにインポート
// ──────────────────────────────────────────

require_once 'Db.php'; // PDO接続 ($pdo) を行う前提

$queueId = isset($_GET['queue_id']) ? intval($_GET['queue_id']) : 0;
if ($queueId <= 0) {
    exit('queue_id が指定されていません');
}

// import_queue から対象レコードを取得
$stmt = $pdo->prepare("SELECT * FROM import_queue WHERE id = ?");
$stmt->execute([$queueId]);
$queue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$queue) {
    exit('指定されたキューが存在しません');
}

$csvFile = __DIR__ . '/csv/' . $queue['filename'];
if (!file_exists($csvFile)) {
    exit('CSVファイルが存在しません');
}

// インポート処理
$insertCount = 0;
$errorCount = 0;
$errors = [];

try {
    $pdo->beginTransaction();
    // 既存データ削除
    $pdo->exec("DELETE FROM address_master");

    if (($handle = fopen($csvFile, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 9) {
                $errorCount++;
                $errors[] = '列数が不足している行をスキップ';
                continue;
            }

            $postal = trim($row[2]);
            $pref   = trim($row[6]);
            $city   = trim($row[7]);
            $town   = trim($row[8]);

            // バリデーション
            if (!preg_match('/^\d{7}$/', $postal)) {
                $errorCount++;
                $errors[] = "郵便番号形式エラー: {$postal}";
                continue;
            }

            // DB登録
            $stmt = $pdo->prepare("
                INSERT INTO address_master (postal_code, prefecture, city, town)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$postal, $pref, $city, $town]);
            $insertCount++;
        }
        fclose($handle);
    }

    // import_queue のステータス更新
    $stmt = $pdo->prepare("UPDATE import_queue SET status = 'completed', completed_at = NOW() WHERE id = ?");
    $stmt->execute([$queueId]);

    $pdo->commit();

    // CSVフォルダの中身を削除
    $files = glob(__DIR__ . '/csv/*.csv');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
} catch (Exception $e) {
    $pdo->rollBack();
    $errors[] = 'インポート中に例外が発生しました: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>CSV インポート結果</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>CSV インポート結果</h2>
    </div>

    <p>インポート件数: <?= $insertCount ?> 件</p>
    <p>スキップ件数: <?= $errorCount ?> 件</p>

    <?php if (!empty($errors)): ?>
        <h3>エラー詳細</h3>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err, ENT_QUOTES) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div style="margin-top: 30px;">
        <a href="index.php"><button type="button">TOPへ戻る</button></a>
    </div>

</body>

</html>