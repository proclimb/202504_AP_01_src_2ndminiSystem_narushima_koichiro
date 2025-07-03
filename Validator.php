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
    public function validate($data)
    {
        $this->error_message = [];

        // 名前
        if (empty($data['name'])) {
            $this->error_message['name'] = '名前が入力されていません';
        } elseif (!preg_match('/^[\p{Han}ぁ-んァ-ンー\x20　]+$/u', $data['name'])) {
            $this->error_message['name'] = '入力できるのは漢字・ひらがな・カタカナのみです';
        } elseif (mb_strlen($data['name']) > 20) {
            $this->error_message['name'] = '名前は20文字以内で入力してください';
        }

        // ふりがな
        if (empty($data['kana'])) {
            $this->error_message['kana'] = 'ふりがなが入力されていません';
        } elseif (preg_match('/[^ぁ-んー]/u', $data['kana'])) {
        } elseif (preg_match('/[^ぁ-んー\x20　]/u', $data['kana'])) {
            $this->error_message['kana'] = 'ひらがなで入力してください';
        } elseif (mb_strlen($data['kana']) > 20) {
            $this->error_message['kana'] = 'ふりがなは20文字以内で入力してください';
        }


        // 生年月日
        if (empty($data['birth_year']) || empty($data['birth_month']) || empty($data['birth_day'])) {
            $this->error_message['birth_date'] = '生年月日が入力されていません';
        } elseif (!$this->isValidDate($data['birth_year'] ?? '', $data['birth_month'] ?? '', $data['birth_day'] ?? '')) {
            $this->error_message['birth_date'] = '生年月日が正しくありません';
        } else {
            $inputDate = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $data['birth_year'], $data['birth_month'], $data['birth_day']));
            $today = new DateTime('today');

            if ($inputDate > $today) {
                $this->error_message['birth_date'] = '生年月日が正しくありません';
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
        } elseif ($this->emailExists($data['email'])) { // 追加
            $this->error_message['email'] = 'このメールアドレスは既に存在します';
        }

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
    private function emailExists($email)
    {
        $sql = "SELECT COUNT(*) FROM user_base WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
}
