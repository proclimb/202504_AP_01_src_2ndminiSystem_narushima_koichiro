<?php
// Csvpreview.php
// ──────────────────────────────────────────
// 「utf_ken_all.csv」ファイルをアップロードし、import_queue に登録
// 最初の10行をプレビュー表示し、インポートへ進む
// ──────────────────────────────────────────

require_once 'Db.php'; // PDO接続 ($pdo)

$csvDir = __DIR__ . '/csv';
$error = '';
$queueId = null;
$csvFile = null;
$previewFilename = null;

// キャンセル処理（ファイル削除）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    if (!empty($_POST['filename'])) {
        $fileToDelete = $csvDir . '/' . basename($_POST['filename']);
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// アップロード処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvUpload'])) {
    $uploadTmp  = $_FILES['csvUpload']['tmp_name'];
    $uploadName = basename($_FILES['csvUpload']['name']);
    $csvFile = $csvDir . '/' . $uploadName;

    if ($uploadName !== 'utf_ken_all.csv') {
        $error = 'ファイル名は「utf_ken_all.csv」にしてください';
    } elseif (pathinfo($uploadName, PATHINFO_EXTENSION) !== 'csv') {
        $error = 'CSVファイルのみアップロード可能です';
    } elseif (is_uploaded_file($uploadTmp)) {
        if (!is_dir($csvDir)) {
            mkdir($csvDir, 0777, true);
        }

        if (!move_uploaded_file($uploadTmp, $csvFile)) {
            $error = 'ファイルのアップロードに失敗しました';
        } else {
            // import_queue に登録
            $stmt = $pdo->prepare("INSERT INTO import_queue (filename, status, created_at)
                                   VALUES (:filename, 'pending', NOW())");
            $stmt->execute([':filename' => $uploadName]);
            $queueId = $pdo->lastInsertId();

            // プレビュー表示へリダイレクト
            header("Location: Csvpreview.php?queue_id=" . $queueId);
            exit;
        }
    } else {
        $error = 'ファイルのアップロードに失敗しました';
    }
}

// queue_id 取得（プレビュー用）
if (isset($_GET['queue_id'])) {
    $queueId = intval($_GET['queue_id']);

    // DBから filename を取得
    $stmt = $pdo->prepare("SELECT filename FROM import_queue WHERE id = :id");
    $stmt->execute([':id' => $queueId]);
    $fileRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $previewFilename = $fileRow['filename'] ?? null;
    if ($previewFilename) {
        $csvFile = $csvDir . '/' . $previewFilename;
    }
}

// ▼ 最新更新日時をDBから取得してフォーマット
$latestFormatted = 'データなし';
try {
    $stmt = $pdo->query("SELECT MAX(completed_at) AS latest FROM import_queue");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($row['latest'])) {
        $latestFormatted = date("Y年n月j日 G時i分", strtotime($row['latest']));
    }
} catch (PDOException $e) {
    $latestFormatted = "取得エラー";
}

// ファイル存在チェック
if (!$csvFile || !file_exists($csvFile)) {
    // ファイル未アップロード時の画面表示
?>
    <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <title>住所マスタ更新</title>
        <link rel="stylesheet" href="style_new.css">
    </head>

    <body>
        <div>
            <h1>mini System</h1>
        </div>
        <div>
            <h2>住所マスタ更新</h2>
        </div>

        <div>
            <?php if ($error): ?>
                <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <h1>郵便番号CSVデータのアップロード</h1>
                <p>現在登録されているデータは
                    <?= htmlspecialchars($latestFormatted, ENT_QUOTES, 'UTF-8') ?>
                    にアップロードしています。<br>
                    データを更新するには新しい「utf_ken_all.csv」をアップロードしてください。
                </p>
                <p>最新のデータは以下のリンクからダウンロードすることができます。</p>
                <label>最新データ<span>リンク</span></label>
                <a href="https://www.post.japanpost.jp/zipcode/dl/utf-zip.html" target="_blank"
                    style="position: relative; top: 6px;">
                    郵便局｜住所の郵便番号（1レコード1行、UTF-8形式）（CSV形式）
                </a>

                <div style="margin-top: 30px;">
                    <input type="file" name="csvUpload" accept=".csv" required>
                    <button type="submit">アップロード</button>
                </div>
            </form>
        </div>

        <div style="margin-top: 30px;">
            <a href="index.php"><button type="button">TOPに戻る</button></a>
        </div>
    </body>

    </html>
<?php
    exit;
}

// CSVプレビュー処理（最初の10行）
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
    echo "<p style='color:red;'>CSVファイルを開けませんでした。</p>";
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

    <h3>ファイル名: <?= htmlspecialchars($previewFilename, ENT_QUOTES) ?></h3>
    <h3>最初の10行のプレビュー</h3>

    <table class="common-table">
        <tr>
            <th>郵便番号</th>
            <th>都道府県</th>
            <th>市区町村</th>
            <th>町域</th>
        </tr>
        <?php foreach ($dataRows as $row): ?>
            <?php
            if (count($row) < 9) continue;
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

    <div class="button-container" style="margin-top: 30px;">
        <form method="GET" action="Csvimport.php">
            <input type="hidden" name="queue_id" value="<?= htmlspecialchars($queueId) ?>">
            <button type="submit" class="csv-btn">インポート開始</button>
        </form>

        <form method="POST">
            <input type="hidden" name="filename" value="<?= htmlspecialchars($previewFilename) ?>">
            <button type="submit" name="cancel" value="1" class="csv-btn-cancel">キャンセル</button>
        </form>
    </div>
</body>

</html>