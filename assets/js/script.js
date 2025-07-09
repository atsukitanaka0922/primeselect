/**
 * assets/js/script.js - Prime Select ECサイト用JavaScript
 * 
 * サイト全体で使用する汎用的なJavaScript機能を提供します。
 * ドロップダウンメニュー制御、数量操作、画像プレビュー、
 * フォームバリデーション、カート処理など様々な機能を実装しています。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// ドロップダウンメニューの動作設定
$(document).ready(function() {
    $('.dropdown-toggle').dropdown();
});

/**
 * 商品数量の増減
 * 商品の購入数量などを増減するUIコントロール用関数
 * 
 * @param {string} inputId 対象の入力要素ID
 * @param {number} increment 増減値（+1 または -1）
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
 * ファイル選択時に画像をプレビュー表示
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
 * 入力フォームのバリデーションチェック
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
 * 数量変更に応じて小計と合計を動的に更新
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
 * すべての小計を合算して合計金額を更新
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
 * 選択した支払い方法に応じてフォームの表示/非表示を切り替え
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
 * サムネイル画像クリック時にメイン画像を切り替え
 * 
 * @param {string} imageFile 画像ファイル名
 * @param {string} productName 商品名（alt属性用）
 */
function changeMainImage(imageFile, productName) {
    document.getElementById('mainImage').src = 'assets/images/' + imageFile;
    document.getElementById('mainImage').alt = productName;
}

// ページ読み込み時の初期化処理
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

    // 商品詳細ページのバリエーション選択時の処理
    $('.variation-select').on('change', function() {
        // ここに商品バリエーション選択時の処理を追加
        // 価格の更新や在庫状況の表示などが可能
    });

    // 商品カードのホバーエフェクト
    $('.card').hover(
        function() {
            $(this).addClass('shadow-sm');
        },
        function() {
            $(this).removeClass('shadow-sm');
        }
    );
});