<?php

class Validator
{
    private $error_message = [];
    private $pdo; // 追加

    public function __construct($pdo) // 追加
    {
        $this->pdo = $pdo;
    }

    // 呼び出し元で使う


    public function validate(&$data) // ← 引用渡しに変更
    {
        $this->error_message = [];

        // 入力値の前後スペース除去（全角・半角）
        $fieldsToTrim = ['name', 'kana', 'prefecture', 'city_town', 'building', 'tel', 'email'];
        foreach ($fieldsToTrim as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // 半角スペース(\s)・全角スペース(\u3000)を前後から除去
                $data[$field] = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $data[$field]);
            }
        }
        $id = $data['id'] ?? null;

        // 名前
        if (empty($data['name'])) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (preg_match('/[^\p{Han}\p{Hiragana}\p{Katakana}ー゛゜\s　]/u', $data['name'])) { // ここを修正
            $this->error_message['name'] = '入力できるのは漢字・ひらがな・カタカナのみです';
        } elseif (mb_strlen($data['name']) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        }


        // ふりがな
        if (empty($data['kana'])) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/[^\p{Hiragana}ー\x20　]/u', $data['kana'])) { // ここを修正
            $this->error_message['kana'] = 'ひらがなで入力してください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        }


        // 生年月日
        if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
            $this->error_message['birth_date'] = '生年月日に未入力があります';
        } elseif (!$this->isValidDate($data['birth_year'] ?? '', $data['birth_month'] ?? '', $data['birth_day'] ?? '')) {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } else {
            $inputDate = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $data['birth_year'], $data['birth_month'], $data['birth_day']));
            $today = new DateTime('today');
            if ($inputDate === false) {
                $this->error_message['birth_date'] = '生年月日が正しくありません';
            } else {
                $inputDate->setTime(0, 0, 0);
                $today->setTime(0, 0, 0);
                if ($inputDate > $today) {
                    $this->error_message['birth_date'] = '生年月日が正しくありません';
                }
                // $inputDate == $today はOK
            }
        }

        // 郵便番号
        if (empty($data['postal_code'])) {
            $this->error_message['postal_code'] = '郵便番号が入力されていません';
        } elseif (!preg_match('/^[0-9]{3}-[0-9]{4}$/', $data['postal_code'] ?? '')) {
            $this->error_message['postal_code'] = '郵便番号は「000-0000」の形式で入力してください';
        }

        // 住所
        if (empty($data['prefecture']) && empty($data['city_town'])) {
            $this->error_message['address'] = '住所が入力されていません';
        } elseif (empty($data['prefecture'])) {
            $this->error_message['address'] = '都道府県が入力されていません';
        } elseif (empty($data['city_town'])) {
            $this->error_message['address'] = '市区町村・番地以下の住所が入力されていません';
        } elseif (mb_strlen($data['prefecture']) > 10) {
            $this->error_message['address'] = '都道府県は10文字以内で入力してください';
        } elseif (mb_strlen($data['city_town']) > 50 || mb_strlen($data['building']) > 50) {
            $this->error_message['address'] = '市区町村・番地もしくは建物名は50文字以内で入力してください';
        }

        // 電話番号
        if (empty($data['tel'])) {
            // 未入力
            $this->error_message['tel'] = '電話番号が入力されていません';
        } elseif (!preg_match('/^[0-9\-]+$/', $data['tel'])) {
            // 数字とハイフン以外が含まれている
            $this->error_message['tel'] = '電話番号は半角数字をハイフンで区切って入力してください';
        } elseif (preg_match('/^\d{6,}$/', $data['tel'])) {
            // 完全に数字だけで6桁以上（ハイフンなし）
            $this->error_message['tel'] = '電話番号は半角数字をハイフンで区切って入力してください';
        } elseif (preg_match('/\-{2,}/', $data['tel'])) {
            // ハイフンが連続している
            $this->error_message['tel'] = '電話番号は12~13桁で正しく入力してください（例: 090-1234-5678）';
        } elseif (
            !preg_match('/^0\d{1,4}-\d{1,4}-\d{3,4}$/', $data['tel']) ||
            mb_strlen($data['tel']) < 12 ||
            mb_strlen($data['tel']) > 13
        ) {
            // 形式違いや文字数の不一致
            $this->error_message['tel'] = '電話番号は12~13桁で正しく入力してください（例: 090-1234-5678）';
        }

        // メールアドレス
        if (empty($data['email'])) {
            $this->error_message['email'] = 'メールアドレスが入力されていません';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error_message['email'] = '有効なメールアドレスを入力してください';
        } elseif ($this->emailExists($data['email'], $id)) { // ←idを渡す
            $this->error_message['email'] = 'このメールアドレスは既に存在します';
        }

        // 郵便番号・住所の整合性チェック
        if (!empty($data['postal_code']) && !empty($data['prefecture']) && !empty($data['city_town'])) {
            $normalized_postal = str_replace('-', '', $data['postal_code']);
            $address = $this->getAddressByPostalCode($normalized_postal);
            if ($address) {
                // 都道府県チェック
                if ($address['prefecture'] !== $data['prefecture']) {
                    $this->error_message['address'] = '郵便番号と住所が異なります。内容をご確認ください';
                }
                // 市区町村チェック
                elseif (strpos($data['city_town'], $address['city']) === false) {
                    $this->error_message['address'] = '郵便番号と住所が異なります。内容をご確認ください';
                }
            }
            // 郵便番号がDBに存在しない場合はスルー or 別エラー
        }

        // 本人確認書類（表）
        $this->validateDocument('document1', '本人確認書類（表）');
        // 本人確認書類（裏）
        $this->validateDocument('document2', '本人確認書類（裏）');

        return empty($this->error_message);
    }


    // エラーメッセージ取得
    public function getErrors()
    {
        return $this->error_message;
    }

    // 生年月日の日付整合性チェック
    private function isValidDate($year, $month, $day)
    {
        return checkdate((int)$month, (int)$day, (int)$year);
    }

    // メールアドレス重複チェック
    private function emailExists($email, $id = null)
    {
        $sql = "SELECT COUNT(*) FROM user_base WHERE email = :email";
        $params = [':email' => $email];
        if ($id !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = $id;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    // 郵便番号からaddress_masterを検索（引数はハイフンなし）
    private function getAddressByPostalCode($postal_code)
    {
        $sql = "SELECT prefecture, city FROM address_master WHERE postal_code = :postal_code";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':postal_code' => $postal_code]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // 本人確認書類バリデーション
    private function validateDocument($fileKey, $label)
    {
        $allowedTypes = ['image/png', 'image/jpeg'];
        $maxSizeMB = 3; // 最大ファイルサイズ 3MB
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES[$fileKey]['tmp_name']);
            $fileSize = $_FILES[$fileKey]['size'];

            if (!in_array($fileType, $allowedTypes)) {
                $this->error_message[$fileKey] = "{$label}の形式が正しくありません（PNG / JPEG）";
            } elseif ($fileSize > $maxSizeMB * 1024 * 1024) {
                $this->error_message[$fileKey] = "{$label}のファイルサイズが大きすぎます（最大{$maxSizeMB}MB）";
            }
        } else {
            // 必須にしたい場合はここでエラーを追加
            // $this->error_message[$fileKey] = "{$label}を選択してください";
        }
    }
}
