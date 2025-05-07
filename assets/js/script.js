/**
 * Prime Select ECサイト用JavaScript
 * 
 * サイト全体で使用する汎用的なJavaScript機能を提供します
 */

// ドロップダウンメニューの動作
$(document).ready(function() {
    $('.dropdown-toggle').dropdown();
});

/**
 * 商品数量の増減
 * 
 * @param {string} inputId 対象の入力要素ID
 * @param {number} increment 増減値
 */
function updateQuantity(inputId, increment) {
    var input = document.getElementById(inputId);
    var currentValue = parseInt(input.value);
    var newValue = currentValue + increment;
    
    // 1〜99の範囲内で数量を制限
    if (newValue >= 1 && newValue <= 99) {
        input.value = newValue;
    }
}

/**
 * 画像プレビュー（管理画面用）
 * 
 * @param {Element} input ファイル入力要素
 */
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result).show();
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * フォームバリデーション
 * 
 * @return {boolean} バリデーションが成功すればtrue
 */
function validateForm() {
    var isValid = true;
    
    // 必須項目のチェック
    $('.required').each(function() {
        if ($(this).val() === '') {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // メールアドレスの形式チェック
    var emailField = $('#email');
    if (emailField.length) {
        var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
        if (!emailPattern.test(emailField.val())) {
            emailField.addClass('is-invalid');
            isValid = false;
        }
    }
    
    return isValid;
}

/**
 * カート内の商品数量変更時の小計更新
 * 
 * @param {Element} inputElement 数量入力要素
 * @param {number} price 商品単価
 */
function updateSubtotal(inputElement, price) {
    var quantity = parseInt(inputElement.value);
    var subtotal = quantity * price;
    var subtotalElement = inputElement.closest('tr').querySelector('.subtotal');
    
    if (subtotalElement) {
        subtotalElement.textContent = '¥' + subtotal.toLocaleString();
    }
    
    updateTotal();
}

/**
 * カート内の合計金額更新
 */
function updateTotal() {
    var total = 0;
    var subtotalElements = document.querySelectorAll('.subtotal');
    
    subtotalElements.forEach(function(element) {
        var value = element.textContent.replace(/[^\d]/g, '');
        total += parseInt(value);
    });
    
    var totalElement = document.getElementById('cartTotal');
    if (totalElement) {
        totalElement.textContent = '¥' + total.toLocaleString();
    }
}

/**
 * 支払い方法選択時のフォーム切替
 */
function togglePaymentForm() {
    var paymentMethod = $('input[name="payment_method"]:checked').val();
    
    if (paymentMethod === 'credit_card') {
        $('#creditCardForm').show();
    } else {
        $('#creditCardForm').hide();
    }
}

/**
 * 商品詳細ページでのメイン画像切り替え
 * 
 * @param {string} imageFile 画像ファイル名
 * @param {string} productName 商品名
 */
function changeMainImage(imageFile, productName) {
    document.getElementById('mainImage').src = 'assets/images/' + imageFile;
    document.getElementById('mainImage').alt = productName;
}

// ページ読み込み時の初期化
$(document).ready(function() {
    // 支払い方法のラジオボタンの変更を監視
    $('input[name="payment_method"]').change(function() {
        togglePaymentForm();
    });
    
    // 初期表示時の設定
    togglePaymentForm();
    
    // フォーム送信時のバリデーション
    $('form').submit(function(event) {
        if (!validateForm()) {
            event.preventDefault();
        }
    });
});