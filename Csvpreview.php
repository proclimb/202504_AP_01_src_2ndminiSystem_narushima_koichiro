<?php
// Csvpreview.php
// ──────────────────────────────────────────
// 日本郵便「住所の郵便番号 (UTF-8)」CSV の
// 最初の10行だけをプレビューする
// ──────────────────────────────────────────

require_once 'Db.php'; // ※Db.php で PDO 接続 ($pdo) を行っている前提

// CSV ファイルのパス（環境に合わせて変更）
$csvDir = __DIR__ . '/csv';
$csvFile = $csvDir . '/update.csv';

// 1) ファイル存在チェック
if (! file_exists($csvFile)) {
    // 修正箇所: CSVファイルが見つからない場合、HTML全体を表示
?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <title>CSV プレビュー</title>
        <link rel="stylesheet" href="style_new.css">
    </head>

    <body>
        <div>
            <h1>mini System</h1>
        </div>
        <div>
            <h2>CSV プレビュー</h2>
        </div>
        <div>
            <form>
                <h1 class="contact-title">CSVファイルが見つかりません</h1>
                <div style="text-align: center; margin-top: 50px;">
                    <p style="color:red; font-size: 1.2em;"><?= htmlspecialchars($csvFile, ENT_QUOTES) ?></p>
                    <a href="index.php">
                        <button type="button">TOPに戻る</button>
                    </a>
                </div>
            </form>
        </div>
    </body>

    </html>
<?php
    exit;
}

// 2) fopen/fgetcsv/fclose でパースした結果を配列に格納
//  ※ 全行ではなく、最初の10行のみを読み込む
$dataRows = [];
$previewLimit = 10;
$rowCount = 0;

if (($handle = fopen($csvFile, 'r')) !== false) {
    while (($row = fgetcsv($handle)) !== false && $rowCount < $previewLimit) {
        $dataRows[] = $row;
        $rowCount++;
    }
    fclose($handle);
} else {
    // 修正箇所: CSVをオープンできない場合もHTML全体を表示
?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <title>CSV プレビュー</title>
        <link rel="stylesheet" href="style_new.css">
    </head>

    <body>
        <div>
            <h1>mini System</h1>
        </div>
        <div>
            <h2>CSV プレビュー</h2>
        </div>
        <div style="text-align: center; margin-top: 50px;">
            <p style="color:red; font-size: 1.2em;">CSV をオープンできませんでした。</p>
            <a href="index.php" class="csv-btn-cancel" style="margin-top: 20px;">TOPに戻る</a>
        </div>
    </body>

    </html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>CSV プレビュー</title>
    <link rel="stylesheet" href="style_new.css">
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>CSV プレビュー</h2>
    </div>

    <h2>CSV パース結果 (最初の10行)</h2>
    <table class="common-table">
        <tr>
            <th>郵便番号 (7桁)</th>
            <th>都道府県 (漢字)</th>
            <th>市区町村 (漢字)</th>
            <th>町域 (漢字)</th>
        </tr>
        <?php foreach ($dataRows as $row): ?>
            <?php
            // 日本郵便 CSVのインデックス6,7,8をチェック
            if (count($row) < 9) {
                continue;
            }
            $postal = htmlspecialchars(trim($row[2]), ENT_QUOTES);
            $pref   = htmlspecialchars(trim($row[6]), ENT_QUOTES);
            $city   = htmlspecialchars(trim($row[7]), ENT_QUOTES);
            $town   = htmlspecialchars(trim($row[8]), ENT_QUOTES);
            ?>
            <tr>
                <td><?= $postal ?></td>
                <td><?= $pref ?></td>
                <td><?= $city ?></td>
                <td><?= $town ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <a href="Csvimport.php" class="csv-btn">インポート開始</a>
    <a href="index.php" class="csv-btn-cancel">キャンセル</a>
</body>

</html>