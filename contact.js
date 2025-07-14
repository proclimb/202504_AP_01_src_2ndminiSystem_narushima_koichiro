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
    validatePostalCode(); if (hasError(document.edit.postal_code)) flag = false;
    validatePrefecture(); if (hasError(document.edit.prefecture)) flag = false;
    validateCityTown(); if (hasError(document.edit.city_town)) flag = false;
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
    form.postal_code.addEventListener("input", validatePostalCode);
    form.prefecture.addEventListener("change", validatePrefecture);
    form.city_town.addEventListener("input", validateCityTown);
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
    if (document.edit.name.value.trim() === "") {
        errorElement(document.edit.name, "名前を入力してください。");
    }
}

// ふりがな：必須＋ひらがなチェック
function validateKana() {
    removeFieldError(document.edit.kana);
    const val = document.edit.kana.value;
    if (val.trim() === "") {
        errorElement(document.edit.kana, "ふりがなを入力してください。");
    } else if (!validateKanaFormat(val)) {
        errorElement(document.edit.kana, "ふりがなはひらがなで入力してください。");
    }
}

// 郵便番号：必須＋形式チェック
function validatePostalCode() {
    removeFieldError(document.edit.postal_code);
    const val = document.edit.postal_code.value;
    if (val.trim() === "") {
        errorElement(document.edit.postal_code, "郵便番号を入力してください。");
    } else if (!/^\d{3}-\d{4}$/.test(val)) {
        errorElement(document.edit.postal_code, "郵便番号の形式が不正です（例: 123-4567）");
    }
}

// 都道府県：必須
function validatePrefecture() {
    removeFieldError(document.edit.prefecture);
    if (document.edit.prefecture.value.trim() === "") {
        errorElement(document.edit.prefecture, "都道府県を選択してください。");
    }
}

// 市区町村：必須
function validateCityTown() {
    removeFieldError(document.edit.city_town);
    if (document.edit.city_town.value.trim() === "") {
        errorElement(document.edit.city_town, "市区町村を入力してください。");
    }
}

// 電話番号：必須＋形式チェック
function validateTelField() {
    removeFieldError(document.edit.tel);
    const val = document.edit.tel.value;
    if (val.trim() === "") {
        errorElement(document.edit.tel, "電話番号を入力してください。");
    } else if (!validateTel(val)) {
        errorElement(document.edit.tel, "電話番号の形式が不正です（例: 090-1234-5678）");
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

// 本人確認書類（表）：形式チェック
function validateDocument1() {
    removeFieldError(document.edit.document1);
    const file = document.edit.document1.files[0];
    if (file && !["image/png", "image/jpeg"].includes(file.type)) {
        errorElement(document.edit.document1, "PNGまたはJPEG形式の画像をアップロードしてください。");
    }
    // TODO: ファイルサイズのチェック（最大5MBなど）
}

// 本人確認書類（裏）：形式チェック
function validateDocument2() {
    removeFieldError(document.edit.document2);
    const file = document.edit.document2.files[0];
    if (file && !["image/png", "image/jpeg"].includes(file.type)) {
        errorElement(document.edit.document2, "PNGまたはJPEG形式の画像をアップロードしてください。");
    }
    // TODO: ファイルサイズのチェック（最大5MBなど）
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
    return /^[ぁ-んー]+$/.test(val);
}
