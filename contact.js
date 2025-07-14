/**
 * 入力項目のバリデーションを行います（送信時）。
 */
function validate() {
    var flag = true;

    removeElementsByClass("error");
    removeClass("error-form");

    // 全項目をチェック（送信時）
    validateName(); if (hasError(document.edit.name)) flag = false;
    validateKana(); if (hasError(document.edit.kana)) flag = false;
    validateBirthDate(); if (hasError(document.edit.birth_year) ||
        hasError(document.edit.birth_month) ||
        hasError(document.edit.birth_day)) flag = false;
    validatePostalCode(); if (hasError(document.edit.postal_code)) flag = false;
    validateAddress(); if (hasError(document.edit.prefecture) ||
        hasError(document.edit.city_town) ||
        hasError(document.edit.building)) flag = false;
    validateTelField(); if (hasError(document.edit.tel)) flag = false;
    validateEmailField(); if (hasError(document.edit.email)) flag = false;
    validateDocument1(); if (hasError(document.edit.document1)) flag = false;
    validateDocument2(); if (hasError(document.edit.document2)) flag = false;

    if (flag) {
        document.edit.submit();
    }

    return false;
}

/**
 * 入力時に各項目をリアルタイムでチェック
 */
window.addEventListener("DOMContentLoaded", function () {
    const form = document.edit;

    form.name.addEventListener("input", validateName);
    form.kana.addEventListener("input", validateKana);
    form.birth_year.addEventListener("change", validateBirthDate);
    form.birth_month.addEventListener("change", validateBirthDate);
    form.birth_day.addEventListener("change", validateBirthDate);
    form.postal_code.addEventListener("input", validatePostalCode);
    form.prefecture.addEventListener("change", validateAddress);
    form.city_town.addEventListener("input", validateAddress);
    form.building.addEventListener("input", validateAddress);
    form.tel.addEventListener("input", validateTelField);
    form.email.addEventListener("input", validateEmailField);
    form.document1.addEventListener("change", validateDocument1);
    form.document2.addEventListener("change", validateDocument2);
});

// ==========================
// 各項目のバリデーション関数
// ==========================

// お名前：必須
function validateName() {
    removeFieldError(document.edit.name);
    const field = document.edit.name;
    const val = field.value.trim();

    // 1. 空チェック
    if (val === "") {
        errorElement(field, "名前が入力されていません");
        return;
    }

    // 2. 使用可能文字チェック（漢字・ひらがな・カタカナ・スペース）
    const namePattern = /^[\p{Script=Han}\u3040-\u309F\u30A0-\u30FF\u30FC\u0020\u3000]+$/u;
    if (!namePattern.test(val)) {
        errorElement(field, "入力できるのは漢字・ひらがな・カタカナのみです");
        return;
    }

    // 3. 最大文字数：20文字以内（全角対応）
    if (Array.from(val).length > 20) {
        errorElement(field, "名前は20文字以内で入力してください");
    }
}

// ふりがな：必須＋ひらがなチェック
function validateKana() {
    removeFieldError(document.edit.kana);
    const field = document.edit.kana;
    const val = field.value.trim();

    // 1. 空欄チェック
    if (val === "") {
        errorElement(field, "ふりがなが入力されていません");
        return;
    }

    // 2. ひらがな・スペース・長音符以外の文字が含まれていないか
    const invalidChars = /[^ぁ-んー\u0020\u3000]/u; // 半角・全角スペース
    if (invalidChars.test(val)) {
        errorElement(field, "ひらがなで入力してください");
        return;
    }

    // 3. 最大文字数（20文字）チェック（全角対応）
    if (Array.from(val).length > 20) {
        errorElement(field, "ふりがなは20文字以内で入力してください");
    }
}

// 生年月日：入力と妥当性のチェック
function validateBirthDate() {
    removeFieldError(document.edit.birth_year);
    removeFieldError(document.edit.birth_month);
    removeFieldError(document.edit.birth_day);

    const year = document.edit.birth_year.value.trim();
    const month = document.edit.birth_month.value.trim();
    const day = document.edit.birth_day.value.trim();

    // 1. 未入力チェック
    if (!year || !month || !day) {
        errorElement(document.edit.birth_year, "生年月日が入力されていません");
        return;
    }

    const y = parseInt(year, 10);
    const m = parseInt(month, 10);
    const d = parseInt(day, 10);

    // 2. 日付の妥当性チェック
    const inputDate = new Date(y, m - 1, d); // 月は0始まり
    if (
        inputDate.getFullYear() !== y ||
        inputDate.getMonth() + 1 !== m ||
        inputDate.getDate() !== d
    ) {
        errorElement(document.edit.birth_year, "生年月日が正しくありません");
        return;
    }

    // 3. 未来日チェック
    const today = new Date();
    inputDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);

    if (inputDate > today) {
        errorElement(document.edit.birth_year, "生年月日が正しくありません");
    }
}


// 郵便番号：必須＋形式チェック
function validatePostalCode() {
    removeFieldError(document.edit.postal_code);
    const val = document.edit.postal_code.value;
    if (val.trim() === "") {
        errorElement(document.edit.postal_code, "郵便番号が入力されていません");
    } else if (!/^\d{3}-\d{4}$/.test(val)) {
        errorElement(document.edit.postal_code, "郵便番号は「000-0000」の形式で入力してください");
    }
}

function validateAddress() {
    const prefecture = document.edit.prefecture;
    const cityTown = document.edit.city_town;
    const building = document.edit.building;

    removeFieldError(prefecture);
    removeFieldError(cityTown);
    removeFieldError(building);

    const preVal = prefecture.value.trim();
    const cityVal = cityTown.value.trim();
    const buildVal = building.value.trim();

    // 両方空
    if (preVal === "" && cityVal === "") {
        errorElement(prefecture, "住所が入力されていません");
        return;
    }

    // 都道府県だけ空
    if (preVal === "") {
        errorElement(prefecture, "都道府県が入力されていません");
    } else if (preVal.length > 10) {
        errorElement(prefecture, "都道府県は10文字以内で入力してください");
    }

    // 市区町村だけ空
    if (cityVal === "") {
        errorElement(cityTown, "市区町村・番地以下の住所が入力されていません");
    } else if (cityVal.length > 50) {
        errorElement(cityTown, "市区町村・番地は50文字以内で入力してください");
    }

    // 建物名は任意だが50文字以内制限あり
    if (buildVal.length > 50) {
        errorElement(building, "建物名は50文字以内で入力してください");
    }
}


// 電話番号：必須＋形式チェック
function validateTelField() {
    removeFieldError(document.edit.tel);
    const field = document.edit.tel;
    const val = field.value.trim();

    // ① 未入力
    if (val === "") {
        errorElement(field, "電話番号が入力されていません");
        return;
    }

    // ② 数字とハイフン以外が含まれている
    if (!/^[0-9\-]+$/.test(val)) {
        errorElement(field, "電話番号は半角数字をハイフンで区切って入力してください");
        return;
    }

    // ③ 数字だけで6桁以上 → ハイフンがない完全な数値列
    if (/^\d{6,}$/.test(val)) {
        errorElement(field, "電話番号は半角数字をハイフンで区切って入力してください");
        return;
    }

    // ④ ハイフンが2つ以上連続
    if (/\-{2,}/.test(val)) {
        errorElement(field, "電話番号は12~13桁で正しく入力してください（例: 090-1234-5678）");
        return;
    }

    // ⑤ 電話番号の形式が適切か + 長さチェック（12～13文字）
    const format = /^0\d{1,4}-\d{1,4}-\d{3,4}$/;
    if (!format.test(val) || val.length < 12 || val.length > 13) {
        errorElement(field, "電話番号は12~13桁で正しく入力してください（例: 090-1234-5678）");
        return;
    }
}

// メールアドレス：必須＋形式チェック
function validateEmailField() {
    removeFieldError(document.edit.email);
    const val = document.edit.email.value;
    if (val.trim() === "") {
        errorElement(document.edit.email, "メールアドレスを入力してください。");
    } else if (!validateMail(val)) {
        errorElement(document.edit.email, "メールアドレスの形式が不正です。");
    }
}

// 本人確認書類（表）：必須＋拡張子＋サイズチェック
function validateDocument1() {
    removeFieldError(document.edit.document1);
    const file = document.edit.document1.files[0];

    if (!file) {
        errorElement(document.edit.document1, "本人確認書類（表）を選択してください。");
        return;
    }

    const fileName = file.name.toLowerCase();
    const maxSize = 3 * 1024 * 1024; // 3MB

    if (!(/\.(jpg|jpeg|png)$/i).test(fileName)) {
        errorElement(document.edit.document1, "ファイルの拡張子は JPG、JPEG、PNG のいずれかにしてください。");
    } else if (file.size > maxSize) {
        errorElement(document.edit.document1, "ファイルサイズは3MB以下にしてください。");
    }
}

// 本人確認書類（裏）：必須＋拡張子＋サイズチェック
function validateDocument2() {
    removeFieldError(document.edit.document2);
    const file = document.edit.document2.files[0];

    if (!file) {
        errorElement(document.edit.document2, "本人確認書類（裏）を選択してください。");
        return;
    }

    const fileName = file.name.toLowerCase();
    const maxSize = 3 * 1024 * 1024; // 3MB

    if (!(/\.(jpg|jpeg|png)$/i).test(fileName)) {
        errorElement(document.edit.document2, "ファイルの拡張子は JPG、JPEG、PNG のいずれかにしてください。");
    } else if (file.size > maxSize) {
        errorElement(document.edit.document2, "ファイルサイズは3MB以下にしてください。");
    }
}


// ==========================
// ユーティリティ関数群
// ==========================

/**
 * 指定項目にエラーメッセージを表示し、スタイルを適用します。
 * @param {*} form 対象の入力項目
 * @param {*} msg 表示するエラーメッセージ
 */
function errorElement(form, msg) {
    form.classList.add("error-form");
    const newElement = document.createElement("div");
    newElement.className = "error";
    newElement.textContent = msg;
    form.parentNode.insertBefore(newElement, form.nextSibling);
}

/**
 * 指定されたクラス名の要素をすべて削除します（エラーメッセージの削除）。
 * @param {*} className 対象のクラス名
 */
function removeElementsByClass(className) {
    const elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        elements[0].parentNode.removeChild(elements[0]);
    }
}

/**
 * 指定クラスを持つすべての要素からクラスを除去します（エラースタイルの削除）。
 * @param {*} className 対象のクラス名
 */
function removeClass(className) {
    const elements = document.getElementsByClassName(className);
    for (let i = 0; i < elements.length; i++) {
        elements[i].classList.remove(className);
    }
}

/**
 * 特定のフィールドだけエラーメッセージとスタイルを削除
 */
function removeFieldError(field) {
    field.classList.remove("error-form");
    const next = field.nextSibling;
    if (next && next.className === "error") {
        next.remove();
    }
}

/**
 * 指定フィールドにエラーがあるかどうか
 */
function hasError(field) {
    return field.classList.contains("error-form");
}

/**
 * メールアドレスの形式チェック。
 * @param {*} val チェック対象文字列
 * @returns true: 有効な形式, false: 無効な形式
 */
function validateMail(val) {
    return /^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@[A-Za-z0-9_.-]+\.[A-Za-z0-9]+$/.test(val);
}

/**
 * 電話番号の形式チェック（例: 090-1234-5678）。
 * @param {*} val チェック対象文字列
 * @returns true: 有効な形式, false: 無効な形式
 */
function validateTel(val) {
    return /^[0-9]{2,4}-[0-9]{2,4}-[0-9]{3,4}$/.test(val);
}

/**
 * ひらがなの形式チェック。
 * @param {*} val チェック対象文字列
 * @returns true: ひらがなのみ, false: その他の文字を含む
 */
function validateKanaFormat(val) {
    return /^[ぁ-んー\s\u3000]+$/.test(val);
}
