/**
 * ------------------------------
 * 送信時バリデーション処理
 * ------------------------------
 * 入力項目のバリデーションを行います（送信時）。
 */
function validate() {
    const form = document.forms["edit"];
    let flag = true;

    // 既存のエラー表示をクリア
    removeElementsByClass("error-msg");
    removeClass("error-form");

    // 各項目のバリデーションを実行
    validateName(); if (hasError(form.elements["name"])) flag = false;
    validateKana(); if (hasError(form.elements["kana"])) flag = false;
    validateBirthDate(); if (hasError(form.elements["birth_year"]) ||
        hasError(form.elements["birth_month"]) ||
        hasError(form.elements["birth_day"])) flag = false;
    validatePostalCode(); if (hasError(form.elements["postal_code"])) flag = false;
    validateAddress(); if (hasError(form.elements["prefecture"]) ||
        hasError(form.elements["city_town"]) ||
        hasError(form.elements["building"])) flag = false;
    validateTelField(); if (hasError(form.elements["tel"])) flag = false;
    validateEmailField(); if (hasError(form.elements["email"])) flag = false;
    validateDocument1(); if (hasError(form.elements["document1"])) flag = false;
    validateDocument2(); if (hasError(form.elements["document2"])) flag = false;

    // エラーがなければ送信
    if (flag) {
        form.submit();
    }

    return false;
}

/**
 * ------------------------------
 * 入力時リアルタイムバリデーション設定
 * ------------------------------
 * 入力時に各項目をリアルタイムでチェック
 */
window.addEventListener("DOMContentLoaded", function () {
    const form = document.forms["edit"];
    if (!form) return;

    if (form.elements["name"]) {
        form.elements["name"].addEventListener("input", validateName);
    }
    if (form.elements["kana"]) {
        form.elements["kana"].addEventListener("input", validateKana);
    }
    if (form.elements["birth_year"]) {
        form.elements["birth_year"].addEventListener("change", validateBirthDate);
    }
    if (form.elements["birth_month"]) {
        form.elements["birth_month"].addEventListener("change", validateBirthDate);
    }
    if (form.elements["birth_day"]) {
        form.elements["birth_day"].addEventListener("change", validateBirthDate);
    }
    if (form.elements["postal_code"]) {
        form.elements["postal_code"].addEventListener("input", validatePostalCode);
    }
    if (form.elements["prefecture"]) {
        form.elements["prefecture"].addEventListener("change", validateAddress);
    }
    if (form.elements["city_town"]) {
        form.elements["city_town"].addEventListener("input", validateAddress);
    }
    if (form.elements["building"]) {
        form.elements["building"].addEventListener("input", validateAddress);
    }
    if (form.elements["tel"]) {
        form.elements["tel"].addEventListener("input", validateTelField);
    }
    if (form.elements["email"]) {
        form.elements["email"].addEventListener("input", validateEmailField);
    }
    if (form.elements["document1"]) {
        form.elements["document1"].addEventListener("change", validateDocument1);
    }
    if (form.elements["document2"]) {
        form.elements["document2"].addEventListener("change", validateDocument2);
    }
});

// ==========================
// 各項目のバリデーション関数
// ==========================

/**
 * お名前：必須項目＋文字種チェック
 */
function validateName() {
    const form = document.forms["edit"];
    const field = form.elements["name"];
    removeFieldError(field);
    const val = field.value.trim();

    if (val === "") {
        errorElement(field, "名前が入力されていません");
        return;
    }

    const namePattern = /^[\p{Script=Han}\u3040-\u309F\u30A0-\u30FF\u30FC\u0020\u3000]+$/u;
    if (!namePattern.test(val)) {
        errorElement(field, "入力できるのは漢字・ひらがな・カタカナのみです");
        return;
    }

    if (Array.from(val).length > 20) {
        errorElement(field, "名前は20文字以内で入力してください");
    }
}

/**
 * ふりがな：必須＋ひらがなチェック
 */
function validateKana() {
    const form = document.forms["edit"];
    const field = form.elements["kana"];
    removeFieldError(field);
    const val = field.value.trim();

    if (val === "") {
        errorElement(field, "ふりがなが入力されていません");
        return;
    }

    const invalidChars = /[^ぁ-んー\u0020\u3000]/u;
    if (invalidChars.test(val)) {
        errorElement(field, "ひらがなで入力してください");
        return;
    }

    if (Array.from(val).length > 20) {
        errorElement(field, "ふりがなは20文字以内で入力してください");
    }
}

/**
 * 生年月日：必須＋日付妥当性チェック
 */
function validateBirthDate() {
    const form = document.forms["edit"];
    const yearField = form.elements["birth_year"];
    const monthField = form.elements["birth_month"];
    const dayField = form.elements["birth_day"];

    removeFieldError(yearField);
    removeFieldError(monthField);
    removeFieldError(dayField);

    const year = yearField.value.trim();
    const month = monthField.value.trim();
    const day = dayField.value.trim();

    if (!year || !month || !day) {
        errorElement2(yearField, "生年月日が入力されていません");
        return;
    }

    const y = parseInt(year, 10);
    const m = parseInt(month, 10);
    const d = parseInt(day, 10);
    const inputDate = new Date(y, m - 1, d);

    if (inputDate.getFullYear() !== y || inputDate.getMonth() + 1 !== m || inputDate.getDate() !== d) {
        errorElement2(yearField, "生年月日が正しくありません");
        return;
    }

    const today = new Date();
    inputDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);

    if (inputDate > today) {
        errorElement2(yearField, "生年月日が正しくありません");
    }
}

/**
 * 郵便番号：必須＋形式チェック
 */
function validatePostalCode() {
    const form = document.forms["edit"];
    const field = form.elements["postal_code"];
    removeFieldError(field);
    const val = field.value.trim();

    if (val === "") {
        errorElement2(field, "郵便番号が入力されていません");
    } else if (!/^\d{3}-\d{4}$/.test(val)) {
        errorElement2(field, "郵便番号は「000-0000」の形式で入力してください");
    }
}

/**
 * 住所：都道府県・市区町村・建物名のチェック
 */
function validateAddress() {
    const form = document.forms["edit"];
    const prefecture = form.elements["prefecture"];
    const cityTown = form.elements["city_town"];
    const building = form.elements["building"];

    removeFieldError(prefecture);
    removeFieldError(cityTown);
    removeFieldError(building);

    const preVal = prefecture.value.trim();
    const cityVal = cityTown.value.trim();
    const buildVal = building.value.trim();

    if (preVal === "" && cityVal === "") {
        errorElement(building, "住所が入力されていません");
        return;
    }

    if (preVal === "") {
        errorElement(building, "都道府県が入力されていません");
        return;
    } else if (preVal.length > 10) {
        errorElement(building, "都道府県は10文字以内で入力してください");
        return;
    }

    if (cityVal === "") {
        errorElement(building, "市区町村・番地以下の住所が入力されていません");
        return;
    } else if (cityVal.length > 50) {
        errorElement(building, "市区町村・番地は50文字以内で入力してください");
        return;
    }

    if (buildVal.length > 50) {
        errorElement(building, "建物名は50文字以内で入力してください");
    }
}

/**
 * 電話番号：必須＋形式チェック
 */
function validateTelField() {
    const form = document.forms["edit"];
    const field = form.elements["tel"];
    removeFieldError(field);
    const val = field.value.trim();

    if (val === "") {
        errorElement(field, "電話番号が入力されていません");
        return;
    }

    if (!/^[0-9\-]+$/.test(val)) {
        errorElement(field, "電話番号は半角数字をハイフンで区切って入力してください");
        return;
    }

    if (/^\d{6,}$/.test(val)) {
        errorElement(field, "電話番号は半角数字をハイフンで区切って入力してください");
        return;
    }

    if (/\-{2,}/.test(val)) {
        errorElement(field, "電話番号は12~13桁で正しく入力してください（例: 090-1234-5678）");
        return;
    }

    const format = /^0\d{1,4}-\d{1,4}-\d{3,4}$/;
    if (!format.test(val) || val.length < 12 || val.length > 13) {
        errorElement(field, "電話番号は12~13桁で正しく入力してください（例: 090-1234-5678）");
    }
}

/**
 * メールアドレス：必須＋形式チェック
 */
function validateEmailField() {
    const form = document.forms["edit"];
    const field = form.elements["email"];
    removeFieldError(field);
    const val = field.value.trim();

    if (val === "") {
        errorElement(field, "メールアドレスが入力されていません");
        return;
    }

    const emailPattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/;
    if (!emailPattern.test(val)) {
        errorElement(field, "有効なメールアドレスを入力してください");
    }
}

/**
 * 本人確認書類（表）：必須＋拡張子＋サイズチェック
 */
function validateDocument1() {
    const form = document.forms["edit"];
    const field = form.elements["document1"];
    removeFieldError(field);

    const file = field.files[0];
    if (!file) {
        errorElement(field, "本人確認書類（表）を選択してください。");
        return;
    }

    const fileName = file.name.toLowerCase();
    const maxSize = 3 * 1024 * 1024;

    if (!(/\.(jpg|jpeg|png)$/i).test(fileName)) {
        errorElement(field, "ファイルの拡張子は JPG、JPEG、PNG のいずれかにしてください。");
    } else if (file.size > maxSize) {
        errorElement(field, "ファイルサイズは3MB以下にしてください。");
    }
}

/**
 * 本人確認書類（裏）：必須＋拡張子＋サイズチェック
 */
function validateDocument2() {
    const form = document.forms["edit"];
    const field = form.elements["document2"];
    removeFieldError(field);

    const file = field.files[0];
    if (!file) {
        errorElement(field, "本人確認書類（裏）を選択してください。");
        return;
    }

    const fileName = file.name.toLowerCase();
    const maxSize = 3 * 1024 * 1024;

    if (!(/\.(jpg|jpeg|png)$/i).test(fileName)) {
        errorElement(field, "ファイルの拡張子は JPG、JPEG、PNG のいずれかにしてください。");
    } else if (file.size > maxSize) {
        errorElement(field, "ファイルサイズは3MB以下にしてください。");
    }
}

// ==========================
// ユーティリティ関数群
// ==========================

function errorElement(target, msg) {
    removeFieldError(target); // 先に既存メッセージを削除
    target.classList.add("error-form");
    const newElement = document.createElement("div");
    newElement.className = "error-msg";
    newElement.textContent = msg;
    target.parentNode.insertBefore(newElement, target.nextSibling);
}

function errorElement2(target, msg) {
    removeFieldError(target); // 対象だけ削除
    target.classList.add("error-form");

    const newElement = document.createElement("div");
    newElement.className = `error-msg2 error-msg2-${target.name}`; // ←個別クラスを付加
    newElement.textContent = msg;

    const grandParent = target.parentNode?.parentNode;
    if (grandParent) {
        grandParent.appendChild(newElement);
    }
}

function removeElementsByClass(className) {
    const elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        elements[0].parentNode.removeChild(elements[0]);
    }
}

function removeClass(className) {
    const elements = document.getElementsByClassName(className);
    for (let i = 0; i < elements.length; i++) {
        elements[i].classList.remove(className);
    }
}

function removeFieldError(field) {
    field.classList.remove("error-form");

    // .error-msg（兄弟）の削除
    let next = field.nextSibling;
    while (next && next.nodeType !== 1) {
        next = next.nextSibling;
    }
    if (next && next.classList.contains("error-msg")) {
        next.remove();
    }

    // .error-msg2 のうち、field に対応する全要素を削除
    const grandParent = field.parentNode?.parentNode;
    if (grandParent) {
        const msgs = grandParent.querySelectorAll(`.error-msg2-${field.name}`);
        msgs.forEach(msg => msg.remove());
    }
}

function hasError(field) {
    return field.classList.contains("error-form");
}
