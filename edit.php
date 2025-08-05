<?php

/**
 * 更新・削除画面
 * update.phpで行っていたDBへの書き込み処理をedit.phpに統合した
 */

// =============================
// 必要なクラスの読み込み
// =============================
require_once 'Db.php';
require_once 'User.php';
require_once 'Validator.php';
require_once 'Address.php';
require_once 'FileBlobHelper.php';

// =============================
// 変数の初期化
// =============================
$error_message = [];
$old = [];

// =============================
// POSTリクエスト時の処理（更新処理）
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validator($pdo);
    $postData = $_POST;

    // 生年月日を分解してValidator用にセット
    if (!empty($postData['birth_date'])) {
        $dateParts = explode('-', $postData['birth_date']);
        if (count($dateParts) === 3) {
            $postData['birth_year'] = $dateParts[0];
            $postData['birth_month'] = $dateParts[1];
            $postData['birth_day'] = $dateParts[2];
        }
    }

    // 性別情報のセット
    if (isset($postData['gender_flag'])) {
        $postData['gender'] = $postData['gender_flag'];
    }

    // バリデーションチェック
    if ($validator->validate($postData)) {
        try {
            $pdo->beginTransaction();

            $id = $postData['id'];

            // ユーザー情報の配列作成
            $userData = [
                'name'         => $postData['name'],
                'kana'         => $postData['kana'],
                'gender_flag'  => $postData['gender_flag'],
                'tel'          => $postData['tel'],
                'email'        => $postData['email'],
            ];

            // 住所情報の配列作成
            $addressData = [
                'user_id'      => $id,
                'postal_code'  => $postData['postal_code'],
                'prefecture'   => $postData['prefecture'],
                'city_town'    => $postData['city_town'],
                'building'     => $postData['building'],
            ];

            // ユーザー情報の更新
            $user = new User($pdo);
            $user->update($id, $userData);

            // 住所情報の更新
            $address = new UserAddress($pdo);
            $address->updateByUserId($addressData);

            // 本人確認書類（ファイル）のアップロード処理
            $blobs = FileBlobHelper::getMultipleBlobs(
                $_FILES['document1'] ?? null,
                $_FILES['document2'] ?? null
            );

            if ($blobs !== null) {
                $expiresAt = null;
                $user->saveDocument(
                    $id,
                    $blobs['front'] ?? null,
                    $blobs['back'] ?? null,
                    $expiresAt,
                    $blobs['front_image_name'] ?? null,
                    $blobs['back_image_name'] ?? null
                );
            }

            // コミット・リダイレクト
            $pdo->commit();
            header('Location: update.php');
            exit();
        } catch (Exception $e) {
            // エラー時のロールバック・エラーメッセージ格納
            $pdo->rollBack();
            $error_message[] = '更新に失敗しました: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES);
            $old = $postData;
        }
    } else {
        // バリデーションエラー時の処理
        $error_message = $validator->getErrors();
        $old = $postData;
    }
} else {
    // =============================
    // GETリクエスト時の処理（初期表示）
    // =============================
    $id = $_GET['id'];
    $user = new User($pdo);
    $old = $user->findById($id);

    // 表（front）の最新ファイル名
    $stmt = $pdo->prepare("SELECT front_image_name FROM user_documents WHERE user_id = :user_id AND front_image_name IS NOT NULL ORDER BY created_at DESC LIMIT 1");
    $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $docFront = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($docFront && !empty($docFront['front_image_name'])) {
        $old['front_image_name'] = $docFront['front_image_name'];
    }

    // 裏（back）の最新ファイル名
    $stmt = $pdo->prepare("SELECT back_image_name FROM user_documents WHERE user_id = :user_id AND back_image_name IS NOT NULL ORDER BY created_at DESC LIMIT 1");
    $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $docBack = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($docBack && !empty($docBack['back_image_name'])) {
        $old['back_image_name'] = $docBack['back_image_name'];
    }

    // 初期表示時にもバリデーション（エラー表示用）
    $validator = new Validator($pdo);
    $validator->validate($old);
    $error_message = $validator->getErrors();
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>mini System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_new.css">
    <script src="postalcodesearch.js"></script>
    <script src="contact.js" defer></script>
</head>

<body>
    <div>
        <h1>mini System</h1>
    </div>
    <div>
        <h2>更新・削除画面</h2>
    </div>
    <div>
        <form method="post" name="edit" enctype="multipart/form-data" onsubmit="return validate();">
            <input type="hidden" name="id" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
            <h1 class="contact-title">更新内容入力</h1>
            <p>更新内容をご入力の上、「更新」ボタンをクリックしてください。</p>
            <p>削除する場合は「削除」ボタンをクリックしてください。</p>
            <div>
                <div>
                    <label>お名前<span>必須</span></label>
                    <input
                        type="text"
                        name="name"
                        placeholder="例）山田太郎"
                        value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                    <?php if (isset($error_message['name'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($error_message['name']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label>ふりがな<span>必須</span></label>
                    <input
                        type="text"
                        name="kana"
                        placeholder="例）やまだたろう"
                        value="<?= htmlspecialchars($old['kana'] ?? '') ?>">
                    <?php if (isset($error_message['kana'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($error_message['kana']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label>性別<span>必須</span></label>
                    <?php $gender = $old['gender_flag'] ?? '1'; ?>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender_flag"
                            value='1'
                            <?= $gender == '1' ? 'checked' : '' ?>>男性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender_flag"
                            value='2'
                            <?= $gender == '2' ? 'checked' : '' ?>>女性</label>
                    <label class="gender">
                        <input
                            type="radio"
                            name="gender_flag"
                            value='3'
                            <?= $gender == '3' ? 'checked' : '' ?>>その他</label>
                </div>
                <div>
                    <label>生年月日<span>必須</span></label>
                    <?php
                    $formatted_date = '';
                    if (!empty($old['birth_date'])) {
                        $date = DateTime::createFromFormat('Y-m-d', $old['birth_date']);
                        if ($date) {
                            $formatted_date = $date->format('Y年n月j日');
                        }
                    }
                    ?>
                    <!-- 表示専用フィールド（readonly） -->
                    <input
                        type="text"
                        value="<?= htmlspecialchars($formatted_date) ?>"
                        readonly
                        class="readonly-field">
                    <!-- 実際に送信されるデータ用のhiddenフィールド -->
                    <input
                        type="hidden"
                        name="birth_date"
                        value="<?= htmlspecialchars($old['birth_date'] ?? '') ?>">
                </div>
                <div>
                    <label>郵便番号<span>必須</span></label>
                    <div class="postal-row">
                        <input
                            class="half-width"
                            type="text"
                            name="postal_code"
                            id="postal_code"
                            placeholder="例）100-0001"
                            value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>">
                        <button type="button"
                            class="postal-code-search"
                            id="searchAddressBtn">住所検索</button>
                    </div>
                    <?php if (isset($error_message['postal_code'])) : ?>
                        <div class="error-msg2"><?= htmlspecialchars($error_message['postal_code']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label>住所<span>必須</span></label>
                    <input
                        type="text"
                        name="prefecture"
                        id="prefecture"
                        placeholder="都道府県"
                        value="<?= htmlspecialchars($old['prefecture'] ?? '') ?>">
                    <input
                        type="text"
                        name="city_town"
                        id="city_town"
                        placeholder="市区町村・番地"
                        value="<?= htmlspecialchars($old['city_town'] ?? '') ?>">
                    <input
                        type="text"
                        name="building"
                        placeholder="建物名・部屋番号  **省略可**"
                        value="<?= htmlspecialchars($old['building'] ?? '') ?>">
                    <?php if (isset($error_message['address'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($error_message['address']) ?></div>
                    <?php endif; ?>
                    <div id="address-error-container"></div>
                </div>
                <div>
                    <label>電話番号<span>必須</span></label>
                    <input
                        type="text"
                        name="tel"
                        placeholder="例）000-000-0000"
                        value="<?= htmlspecialchars($old['tel'] ?? '') ?>">
                    <?php if (isset($error_message['tel'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($error_message['tel']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label>メールアドレス<span>必須</span></label>
                    <input
                        type="text"
                        name="email"
                        placeholder="例）guest@example.com"
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (isset($error_message['email'])) : ?>
                        <div class="error-msg"><?= htmlspecialchars($error_message['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <label>本人確認書類（表）</label>
                    <input
                        type="file"
                        name="document1"
                        id="document1"
                        accept="image/png, image/jpeg, image/jpg"
                        onchange="handleFileChange(1)"
                        style="display:none;">
                    <button type="button" id="filelabel1-btn" class="file-select-btn" onclick="document.getElementById('document1').click();">
                        <?= !empty($old['front_image_name']) ? 'ファイルを更新' : 'ファイルを選択' ?>
                    </button>
                    <span id="filename1" class="filename-display"></span>
                    <span id="existing-filename1">
                        <?php if (!empty($old['front_image_name'])): ?>
                            <a href="Showdocument.php?user_id=<?= urlencode($old['id']) ?>&type=front" target="_blank">
                                <?= htmlspecialchars($old['front_image_name']) ?>
                            </a>
                            <a href="#" class="delete-icon" title="削除（未実装）">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        <?php else: ?>
                            <span class="unregistered">現在は未登録</span>
                        <?php endif; ?>
                    </span>
                    <div class="preview-container">
                        <img id="preview1" src="#" alt="プレビュー画像１" style="display: none; max-width: 200px; margin-top: 8px;">
                        <?php if (isset($error_message['document1'])) : ?>
                            <div class="error-msg"><?= htmlspecialchars($error_message['document1']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label>本人確認書類（裏）</label>
                    <input
                        type="file"
                        name="document2"
                        id="document2"
                        accept="image/png, image/jpeg, image/jpg"
                        onchange="handleFileChange(2)"
                        style="display:none;">
                    <button type="button" id="filelabel2-btn" class="file-select-btn" onclick="document.getElementById('document2').click();">
                        <?= !empty($old['back_image_name']) ? 'ファイルを更新' : 'ファイルを選択' ?>
                    </button>
                    <span id="filename2" class="filename-display"></span>
                    <span id="existing-filename2">
                        <?php if (!empty($old['back_image_name'])): ?>
                            <a href="Showdocument.php?user_id=<?= urlencode($old['id']) ?>&type=back" target="_blank">
                                <?= htmlspecialchars($old['back_image_name']) ?>
                            </a>
                            <a href="#" class="delete-icon" title="削除（未実装）">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        <?php else: ?>
                            <span class="unregistered">現在は未登録</span>
                        <?php endif; ?>
                    </span>
                    <div class="preview-container">
                        <img id="preview2" src="#" alt="プレビュー画像２" style="display: none; max-width: 200px; margin-top: 8px;">
                        <?php if (isset($error_message['document2'])) : ?>
                            <div class="error-msg"><?= htmlspecialchars($error_message['document2']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </div>
    <button type="submit">更新</button>
    <a href="dashboard.php"><button type="button">ダッシュボードに戻る</button></a>
    </form>
    <form action="delete.php" method="post" name="delete">
        <input type="hidden" name="id" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
        <button type="submit">削除</button>
    </form>
    </div>

    <script>
        function handleFileChange(num) {
            var input = document.getElementById('document' + num);
            var filenameSpan = document.getElementById('filename' + num);
            var labelBtn = document.getElementById('filelabel' + num + '-btn');
            var previewImg = document.getElementById('preview' + num);

            if (input.files.length > 0) {
                var file = input.files[0];
                filenameSpan.textContent = file.name;
                labelBtn.textContent = 'ファイルを選択';

                // プレビュー画像表示処理を追加
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                filenameSpan.textContent = '';
                previewImg.src = '#';
                previewImg.style.display = 'none';

                if (document.getElementById('existing-filename' + num)) {
                    labelBtn.textContent = 'ファイルを更新';
                } else {
                    labelBtn.textContent = 'ファイルを選択';
                }
            }
        }
    </script>

</body>

</html>