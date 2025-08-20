<?php
// Csvimport.php
// ──────────────────────────────────────────
// 日本郵便「住所の郵便番号 (UTF-8)」CSV を
// address_master テーブルに分割処理を経て取り込む。
// ──────────────────────────────────────────

require_once 'Db.php'; // PDO接続 ($pdo) を行うファイル

// CSVファイルと一時フォルダのパス設定
$csvDir   = __DIR__ . '/csv';
$csvFile  = $csvDir . '/update.csv';
$tempDir  = $csvDir . '/chunks';
$chunkSize = 5000; // 1ファイルあたりの行数

// ファイル存在チェック
if (!file_exists($csvFile)) {
    echo "<p style='color:red;'>CSV ファイルが見つかりません: {$csvFile}</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 一時フォルダが存在しない場合は作成
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// CSVファイルを分割処理
$handle = fopen($csvFile, 'r');
$fileIndex = 1;
$rowCount = 0;
$outHandle = fopen("{$tempDir}/chunk_{$fileIndex}.csv", 'w');

while (($row = fgetcsv($handle)) !== false) {
    fputcsv($outHandle, $row);
    $rowCount++;

    // chunkSizeに達したら新しいファイルを作成
    if ($rowCount >= $chunkSize) {
        fclose($outHandle);
        $fileIndex++;
        $rowCount = 0;
        $outHandle = fopen("{$tempDir}/chunk_{$fileIndex}.csv", 'w');
    }
}
fclose($handle);
fclose($outHandle);

// データベースへの取り込み処理開始
try {
    $pdo->beginTransaction();

    // address_master テーブルを初期化（全件削除）
    $pdo->exec("TRUNCATE TABLE address_master");

    // INSERT用のプリペアドステートメントを準備
    $insertSql = "
        INSERT INTO address_master
            (postal_code, prefecture, city, town, updated_at)
        VALUES
            (:postal_code, :prefecture, :city, :town, NOW())
    ";
    $stmt = $pdo->prepare($insertSql);

    $totalCount = 0; // 全体の処理件数

    // 分割されたCSVファイルを順次処理
    $chunkFiles = glob("{$tempDir}/chunk_*.csv");
    foreach ($chunkFiles as $chunkFile) {
        $handle = fopen($chunkFile, 'r');

        while (($row = fgetcsv($handle)) !== false) {
            // カラム数チェック（最低限9列必要）
            if (count($row) < 9) continue;

            // 必要なカラムを取得
            $postal = trim($row[2]);
            $pref   = trim($row[6]);
            $city   = trim($row[7]);
            $town   = trim($row[8]);

            // 郵便番号が7桁でない場合はスキップ
            if ($postal === '' || mb_strlen($postal) !== 7) continue;

            // データをバインドして実行
            $stmt->bindValue(':postal_code', $postal, PDO::PARAM_STR);
            $stmt->bindValue(':prefecture',   $pref,   PDO::PARAM_STR);
            $stmt->bindValue(':city',         $city,   PDO::PARAM_STR);
            $stmt->bindValue(':town',         $town,   PDO::PARAM_STR);
            $stmt->execute();

            $totalCount++;
        }

        fclose($handle);
    }

    // コミット
    $pdo->commit();
} catch (Exception $e) {
    // エラー発生時はロールバック
    $pdo->rollBack();
    echo "<p style='color:red;'>CSV 取込中にエラーが発生しました: "
        . htmlspecialchars($e->getMessage(), ENT_QUOTES)
        . "</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 一時ファイルと元CSVの削除処理
foreach ($chunkFiles as $file) {
    unlink($file);
}
rmdir($tempDir);

if (file_exists($csvFile)) {
    if (!unlink($csvFile)) {
        error_log("Failed to delete CSV file: {$csvFile}");
        echo "<p style='color:red;'>CSVファイルの削除に失敗しました。</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>CSV 取込完了</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>CSV取込完了</h2>
    </div>
    <div>
        <h1>CSV取込完了</h1>
        <p>住所マスタを更新しました。（処理件数：<?= $totalCount ?>件）</p>
        <a href="index.php">
            <button type="button">TOPに戻る</button>
        </a>
    </div>
</body>

</html>