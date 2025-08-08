<?php
// Csvimport.php
// ──────────────────────────────────────────
// プレビューを経て OK が押された場合に呼び出される。
// 日本郵便「住所の郵便番号 (UTF-8)」CSV を
// address_master テーブルに丸ごと取り込む。
// ──────────────────────────────────────────

require_once 'Db.php'; // ※Db.php で PDO 接続 ($pdo) を行っている前提

// 1) CSV ファイルのパス
$csvDir = __DIR__ . '/csv';
$csvFile = $csvDir . '/update.csv';

if (! file_exists($csvFile)) {
    echo "<p style='color:red;'>CSV ファイルが見つかりません: {$csvFile}</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// 2) DB トランザクション開始
try {
    $pdo->beginTransaction();

    // 2-1) address_master を物理削除（全件削除）
    $pdo->exec("TRUNCATE TABLE address_master");

    // 2-2) INSERT 用プリペアドステートメントを準備
    $insertSql = "
        INSERT INTO address_master
            (postal_code, prefecture, city, town, updated_at)
        VALUES
            (:postal_code, :prefecture, :city, :town, NOW())
    ";
    $stmt = $pdo->prepare($insertSql);

    // 2-3) CSVファイルをオープンし、1行ずつ読み込んで処理する
    $rowCount = 0; // 処理件数カウント用
    if (($handle = fopen($csvFile, 'r')) !== false) {

        while (($row = fgetcsv($handle)) !== false) {
            // カラム数チェック
            if (count($row) < 9) {
                continue;
            }

            // 必要なカラムを取得
            $postal  = trim($row[2]);
            $pref    = trim($row[6]);
            $city    = trim($row[7]);
            $town    = trim($row[8]);

            // 郵便番号が7桁でない行はスキップ
            if ($postal === '' || mb_strlen($postal) !== 7) {
                continue;
            }

            // データをバインドして実行
            $stmt->bindValue(':postal_code', $postal, PDO::PARAM_STR);
            $stmt->bindValue(':prefecture',   $pref,   PDO::PARAM_STR);
            $stmt->bindValue(':city',         $city,   PDO::PARAM_STR);
            $stmt->bindValue(':town',         $town,   PDO::PARAM_STR);
            $stmt->execute();

            $rowCount++;
        }

        fclose($handle);
    } else {
        throw new Exception("CSVファイルをオープンできませんでした。");
    }

    // 2-4) コミット
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color:red;'>CSV 取込中にエラーが発生しました: "
        . htmlspecialchars($e->getMessage(), ENT_QUOTES)
        . "</p>";
    echo '<p><a href="index.php">トップに戻る</a></p>';
    exit;
}

// CSVファイルの削除処理
if (file_exists($csvFile)) {
    if (! unlink($csvFile)) {
        // 削除に失敗した場合はログを残すか、画面に出力
        error_log("Failed to delete CSV file: {$csvFile}");
        echo "<p style='color:red;'>ファイルの削除に失敗しました。</p>";
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
        <div>
            <h1>CSV取込完了</h1>
            <p>
                住所マスタを更新しました。（処理件数：<?= $rowCount ?>件）
            </p>
            <a href="index.php">
                <button type="button">TOPに戻る</button>
            </a>
        </div>
    </div>
</body>

</html>