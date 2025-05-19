<?php
/**
 * checkout.php - チェックアウトページ
 * 
 * 購入手続きを行い、配送情報と支払い方法を入力するページです。
 * トランザクション処理により、注文処理と在庫管理を連携させています。
 * 
 * 主な機能:
 * - 配送先情報の入力
 * - 支払い方法の選択
 * - 注文内容の確認
 * - 注文処理の実行
 * - 受注生産商品の予約処理
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Cart.php";
include_once "classes/Product.php";  // Productクラスを追加
include_once "classes/Order.php";
include_once "classes/Payment.php";

// 未ログインの場合はログインページへリダイレクト
if(!isset($_SESSION['user_id'])) {
    // リダイレクト先をセッションに保存（ログイン後に戻ってこれるように）
    $_SESSION['redirect_to'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// データベース接続の初期化
$database = new Database();
$db = $database->getConnection();

// クラスのインスタンス化
$cart = new Cart($db);
$order = new Order($db);
$payment = new Payment($db);

$user_id = $_SESSION['user_id'];

// 注文処理
if(isset($_POST['place_order'])) {
    try {
        // 既にトランザクションが開始されていたら一度ロールバック
        if($db->inTransaction()) {
            $db->rollback();
        }
        
        // 注文情報を取得
        $order->user_id = $user_id;
        $order->shipping_address = $_POST['address'];
        $order->payment_method = $_POST['payment_method'];
        
        // 注文作成（トランザクション処理を含む）
        $order_id = $order->create();
        
        if($order_id) {
            // 支払い処理
            $payment_success = false;
            
            switch($order->payment_method) {
                case 'credit_card':
                    // クレジットカード決済（デモ用）
                    $card_number = $_POST['card_number'];
                    $card_expiry = $_POST['card_expiry'];
                    $card_cvv = $_POST['card_cvv'];
                    $payment_success = $payment->processCreditCard($order_id, $card_number, $card_expiry, $card_cvv);
                    break;
                    
                case 'bank_transfer':
                    // 銀行振込
                    $payment_success = $payment->processBankTransfer($order_id);
                    break;
                    
                case 'cod':
                    // 代金引換
                    $payment_success = $payment->processCOD($order_id);
                    break;
            }
            
            if($payment_success) {
                // カートをクリア
                $cart->clear($user_id);
                
                // 注文完了ページにリダイレクト
                header('Location: order_complete.php?id=' . $order_id);
                exit();
            } else {
                // 支払い失敗時は注文をキャンセルして在庫を復元
                $order->restoreStockOnCancel($order_id);
                $error_message = "決済処理に失敗しました。";
            }
        } else {
            $error_message = "注文の作成に失敗しました。";
        }
    } catch (Exception $e) {
        $error_message = "エラーが発生しました: " . $e->getMessage();
        
        // トランザクションが残っている場合はロールバック
        if ($db->inTransaction()) {
            $db->rollback();
        }
    }
}

// ヘッダーのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>チェックアウト</h2>
    
    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="alert alert-warning">
        <strong>注意:</strong> これは模擬サイトです。実際のクレジットカード番号、個人情報などを入力しないでください。
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">配送情報</div>
                <div class="card-body">
                    <form method="post">
                        <!-- 顧客情報フォーム -->
                        <div class="form-group">
                            <label for="name">お名前 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">メールアドレス <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">電話番号 <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="address">住所 <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" required></textarea>
                        </div>
                        
                        <!-- 支払い方法選択 -->
                        <div class="form-group">
                            <label>支払い方法 <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                <label class="form-check-label" for="credit_card">クレジットカード</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                <label class="form-check-label" for="bank_transfer">銀行振込</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod">
                                <label class="form-check-label" for="cod">代金引換</label>
                            </div>
                        </div>
                        
                        <!-- クレジットカード情報フォーム - 支払い方法選択によって表示/非表示を切り替え -->
                        <div id="credit_card_form">
                            <div class="form-group">
                                <label for="card_number">カード番号 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="card_expiry">有効期限 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="card_expiry" name="card_expiry" placeholder="MM/YY">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="card_cvv">セキュリティコード <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="card_cvv" name="card_cvv" placeholder="123">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-primary btn-lg">注文を確定する</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">注文概要</div>
                <div class="card-body">
                    <?php
                    // カート内商品を取得して表示
                    $stmt = $cart->getItems($user_id);
                    $total = 0;
                    
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // extract関数でデータを変数に展開
                        extract($row);
                        
                        // バリエーションがある場合、価格を調整
                        $item_price = $price;
                        if(isset($price_adjustment)) {
                            $item_price += $price_adjustment;
                        }
                        
                        $subtotal = $item_price * $quantity;
                        $total += $subtotal;
                        ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                <?php echo $name; ?> x <?php echo $quantity; ?>
                                <?php if(isset($variation_name) && isset($variation_value)): ?>
                                <small class="text-muted d-block"><?php echo $variation_name; ?>: <?php echo $variation_value; ?></small>
                                <?php endif; ?>
                            </span>
                            <span>¥<?php echo number_format($subtotal); ?></span>
                        </div>
                        <?php
                    }
                    ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>合計</strong></span>
                        <span><strong>¥<?php echo number_format($total); ?></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 支払い方法に応じてフォームの表示切替
document.addEventListener('DOMContentLoaded', function() {
    const creditCardRadio = document.getElementById('credit_card');
    const bankTransferRadio = document.getElementById('bank_transfer');
    const codRadio = document.getElementById('cod');
    const creditCardForm = document.getElementById('credit_card_form');
    
    function toggleCreditCardForm() {
        // クレジットカード選択時のみフォームを表示
        if(creditCardRadio.checked) {
            creditCardForm.style.display = 'block';
        } else {
            creditCardForm.style.display = 'none';
        }
    }
    
    // イベントリスナーを設定
    creditCardRadio.addEventListener('change', toggleCreditCardForm);
    bankTransferRadio.addEventListener('change', toggleCreditCardForm);
    codRadio.addEventListener('change', toggleCreditCardForm);
    
    // 初期表示時の設定
    toggleCreditCardForm();
});
</script>

<?php include_once "templates/footer.php"; ?>