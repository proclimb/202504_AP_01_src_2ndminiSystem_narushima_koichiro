// edit.js - edit.php専用JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // ゴミ箱アイコンのクリックで削除フラグを切り替え、見た目を変更
    document.querySelectorAll('.delete-icon').forEach(function (icon) {
        icon.addEventListener('click', function (e) {
            e.preventDefault();
            const type = this.getAttribute('data-type');
            const isFront = type === 'front';
            const flagId = isFront ? 'delete_front' : 'delete_back';
            const filenameId = isFront ? 'existing-name1' : 'existing-name2';
            const iconElem = this.querySelector('i');
            const filenameElem = document.getElementById(filenameId);
            const flagInput = document.getElementById(flagId);

            // フラグ切り替え
            if (flagInput.value === '0') {
                flagInput.value = '1';
                // 打消し線・色変更
                if (filenameElem) {
                    filenameElem.style.textDecoration = 'line-through';
                    filenameElem.style.color = '#888';
                }
                if (iconElem) {
                    iconElem.style.color = 'red';
                }
            } else {
                flagInput.value = '0';
                // 元に戻す
                if (filenameElem) {
                    filenameElem.style.textDecoration = '';
                    filenameElem.style.color = '';
                }
                if (iconElem) {
                    iconElem.style.color = '';
                }
            }
        });
    });

});

// 新規画像アップロード時にプレビュー画像を表示するための関数
function handleFileChange(num) {
    const input = document.getElementById('document' + num);
    const filenameSpan = document.getElementById('filename' + num);
    const labelBtn = document.getElementById('filelabel' + num + '-btn');
    const previewImg = document.getElementById('preview' + num);
    const existingName = document.getElementById('existing-name' + num);
    const deleteIcon = document.querySelector('#existing-filename' + num + ' .delete-icon');

    const file = input.files[0];
    if (!file) {
        filenameSpan.textContent = '';
        previewImg.src = '#';
        previewImg.style.display = 'none';
        if (document.getElementById('existing-filename' + num)) {
            labelBtn.textContent = 'ファイルを更新';
        } else {
            labelBtn.textContent = 'ファイルを選択';
        }
        return;
    }

    // プレビュー画像表示
    const reader = new FileReader();
    reader.onload = function (e) {
        previewImg.src = e.target.result;
        previewImg.style.display = 'block';
    };
    reader.readAsDataURL(file);

    // ファイル名表示
    filenameSpan.textContent = file.name;
    // ボタン表示変更
    labelBtn.textContent = 'ファイルを選択';
    // 既存ファイル名に打消し線
    if (existingName) {
        existingName.style.textDecoration = 'line-through';
        existingName.style.color = '#888';
    }
    // ゴミ箱アイコンを非表示
    if (deleteIcon) {
        deleteIcon.style.display = 'none';
    }
    // 既存の新ファイル名表示があれば削除（念のため）
    const oldNewName = document.getElementById('new-name' + num);
    if (oldNewName) {
        oldNewName.remove();
    }
}
