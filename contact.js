/**
 * 入力項目のバリデーションを行います。
 */
function validate() {

    // エラーフラグの初期化（true: エラーなし、false: エラーあり）
    var flag = true;

    // 既存のエラーメッセージとスタイルをリセット
    removeElementsByClass("error");
    removeClass("error-form");

    // お名前：必須
    if (document.edit.name.value == "") {
        errorElement(document.edit.name, "名前を入力してください。");
        flag = false;
    }

    // ふりがな：必須＋ひらがなチェック
    if (document.edit.kana.value == "") {
        errorElement(document.edit.kana, "ふりがなを入力してください。");
        flag = false;
    } else {
        if (!validateKana(document.edit.kana.value)) {
            errorElement(document.edit.kana, "ふりがなはひらがなで入力してください。");
            flag = false;
        }
    }

    // 郵便番号：必須＋形式チェック
    if (document.edit.postal_code.value === "") {
        errorElement(document.edit.postal_code, "郵便番号を入力してください。");
        flag = false;
    } else if (!/^\d{3}-\d{4}$/.test(document.edit.postal_code.value)) {
        errorElement(document.edit.postal_code, "郵便番号の形式が不正です（例: 123-4567）");
        flag = false;
    }

    // 住所：都道府県・市区町村
    if (document.edit.prefecture.value === "") {
        errorElement(document.edit.prefecture, "都道府県を選択してください。");
        flag = false;
    }
    if (document.edit.city_town.value === "") {
        errorElement(document.edit.city_town, "市区町村を入力してください。");
        flag = false;
    }

    // TODO: 建物名（building）の入力チェック（任意）

    // 電話番号：必須＋形式チェック
    if (document.edit.tel.value == "") {
        errorElement(document.edit.tel, "電話番号を入力してください。");
        flag = false;
    } else {
        if (!validateTel(document.edit.tel.value)) {
            errorElement(document.edit.tel, "電話番号の形式が不正です（例: 090-1234-5678）");
            flag = false;
        }
    }

    // メールアドレス：必須＋形式チェック
    if (document.edit.email.value == "") {
        errorElement(document.edit.email, "メールアドレスを入力してください。");
        flag = false;
    } else {
        if (!validateMail(document.edit.email.value)) {
            errorElement(document.edit.email, "メールアドレスの形式が不正です。");
            flag = false;
        }
    }

    // 本人確認書類（表）：形式チェック
    var fileInput1 = document.edit.document1;
    if (fileInput1 && fileInput1.files.length > 0) {
        var file1 = fileInput1.files[0];
        var type1 = file1.type;
        if (type1 !== "image/png" && type1 !== "image/jpeg") {
            errorElement(fileInput1, "PNGまたはJPEG形式の画像をアップロードしてください。");
            flag = false;
        }
        // TODO: ファイルサイズのチェック（最大5MBなど）
    }

    // 本人確認書類（裏）：形式チェック
    var fileInput2 = document.edit.document2;
    if (fileInput2 && fileInput2.files.length > 0) {
        var file2 = fileInput2.files[0];
        var type2 = file2.type;
        if (type2 !== "image/png" && type2 !== "image/jpeg") {
            errorElement(fileInput2, "PNGまたはJPEG形式の画像をアップロードしてください。");
            flag = false;
        }
        // TODO: ファイルサイズのチェック（最大5MBなど）
    }

    // TODO: 生年月日の入力チェックを追加する（format: YYYY-MM-DD）

    // エラーがなければ送信
    if (flag) {
        document.edit.submit();
    }

    return false;
}

/**
 * 指定項目にエラーメッセージを表示し、スタイルを適用します。
 * @param {*} form 対象の入力項目
 * @param {*} msg 表示するエラーメッセージ
 */
var errorElement = function (form, msg) {
    form.className = "error-form";
    var newElement = document.createElement("div");
    newElement.className = "error";
    var newText = document.createTextNode(msg);
    newElement.appendChild(newText);
    form.parentNode.insertBefore(newElement, form.nextSibling);
}

/**
 * 指定されたクラス名の要素をすべて削除します（エラーメッセージの削除）。
 * @param {*} className 対象のクラス名
 */
var removeElementsByClass = function (className) {
    var elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        elements[0].parentNode.removeChild(elements[0]);
    }
}

/**
 * 指定クラスを持つすべての要素からクラスを除去します（エラースタイルの削除）。
 * @param {*} className 対象のクラス名
 */
var removeClass = function (className) {
    var elements = document.getElementsByClassName(className);
    while (elements.length > 0) {
        elements[0].className = "";
    }
}

/**
 * メールアドレスの形式チェック。
 * @param {*} val チェック対象文字列
 * @returns true: 有効な形式, false: 無効な形式
 */
var validateMail = function (val) {
    return /^[A-Za-z0-9]{1}[A-Za-z0-9_.-]*@[A-Za-z0-9_.-]+\.[A-Za-z0-9]+$/.test(val);
}

/**
 * 電話番号の形式チェック（例: 090-1234-5678）。
 * @param {*} val チェック対象文字列
 * @returns true: 有効な形式, false: 無効な形式
 */
var validateTel = function (val) {
    return /^[0-9]{2,4}-[0-9]{2,4}-[0-9]{3,4}$/.test(val);
}

/**
 * ひらがなの形式チェック。
 * @param {*} val チェック対象文字列
 * @returns true: ひらがなのみ, false: その他の文字を含む
 */
var validateKana = function (val) {
    return /^[ぁ-んー]+$/.test(val);
}
