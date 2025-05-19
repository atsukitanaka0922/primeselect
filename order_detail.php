<?php
/**
 * order_detail.php - 注文詳細ページ
 * 
 * ユーザーの個別注文の詳細情報を表示するページです。
 * 注文商品、配送先情報、支払い情報などを表示します。
 * 
 * 主な機能:
 * - 注文基本情報の表示
 * - 注文商品の一覧表示
 * - 注文状況の表示
 * - 注文キャンセル機能（一部条件下）
 * - お問い合わせへのリンク
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Order.php";

// 未ログインならログインページへリダイレクト
if(!isset($_SESSION['user_id'])) {
    // リダイレクト先をセッションに保存（ログイン後に戻ってこれるように）
    $_SESSION['redirect_to'] = 'orders.php';
    header('Location: login.php');
    exit();
}

// データベース接続の初期化
$database = new Database();
$db = $database->getConnection();

// 注文オブジェクトの初期化
$order = new Order($db);

// 注文IDの取得（URLパラメータから）
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// 注文情報の取得
$order->read($id);

// 注文がユーザーのものでない場合はリダイレクト（セキュリティ対策）
if($order->user_id != $_SESSION['user_id']) {
    header('Location: orders.php');
    exit();
}

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <!-- パンくずリスト -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">ホーム</a></li>
            <li class="breadcrumb-item"><a href="orders.php">注文履歴</a></li>
            <li class="breadcrumb-item active" aria-current="page">注文 #<?php echo $order->id; ?></li>
        </ol>
    </nav>
    
    <h2>注文詳細</h2>
    
    <div class="row">
        <!-- 左側：注文情報と商品一覧 -->
        <div class="col-md-8">
            <!-- 注文基本情報 -->
            <div class="card mb-4">
                <div class="card-header">注文情報</div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>注文番号:</th>
                            <td><?php echo $order->id; ?></td>
                        </tr>
                        <tr>
                            <th>注文日:</th>
                            <td><?php echo date('Y年n月j日', strtotime($order->created)); ?></td>
                        </tr>
                        <tr>
                            <th>注文状況:</th>
                            <td>
                                <?php
                                // 注文ステータスに応じて異なるバッジを表示
                                switch($order->status) {
                                    case 'pending':
                                        echo '<span class="badge badge-warning">保留中</span>';
                                        break;
                                    case 'processing':
                                        echo '<span class="badge badge-info">処理中</span>';
                                        break;
                                    case 'shipped':
                                        echo '<span class="badge badge-primary">発送済み</span>';
                                        break;
                                    case 'delivered':
                                        echo '<span class="badge badge-success">配達済み</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="badge badge-danger">キャンセル済み</span>';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>お支払い方法:</th>
                            <td>
                                <?php
                                // 支払い方法の表示
                                switch($order->payment_method) {
                                    case 'credit_card':
                                        echo 'クレジットカード';
                                        break;
                                    case 'bank_transfer':
                                        echo '銀行振込';
                                        break;
                                    case 'cod':
                                        echo '代金引換';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>配送先住所:</th>
                            <td><?php echo nl2br($order->shipping_address); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 注文商品一覧 -->
            <div class="card">
                <div class="card-header">注文商品</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>商品</th>
                                    <th>価格</th>
                                    <th>数量</th>
                                    <th>小計</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 注文商品を取得
                                $items = $order->getOrderItems($order->id);
                                $total_verification = 0; // 合計金額の検証用変数
                                
                                while($row = $items->fetch(PDO::FETCH_ASSOC)) {
                                    // データの抽出
                                    extract($row);
                                    $subtotal = $price * $quantity;
                                    $total_verification += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="assets/images/<?php echo $image; ?>" width="50" alt="<?php echo $name; ?>">
                                                <div class="ml-2">
                                                    <span><?php echo $name; ?></span>
                                                    <?php if(isset($variation_name) && isset($variation_value)): ?>
                                                    <div><small class="text-muted"><?php echo $variation_name; ?>: <?php echo $variation_value; ?></small></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>¥<?php echo number_format($price); ?></td>
                                        <td><?php echo $quantity; ?></td>
                                        <td>¥<?php echo number_format($subtotal); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <td colspan="3" class="text-right"><strong>合計</strong></td>
                                    <td><strong>¥<?php echo number_format($order->total_amount); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 右側：アクションボタンと情報 -->
        <div class="col-md-4">
            <!-- アクションボタン -->
            <div class="card mb-4">
                <div class="card-header">アクション</div>
                <div class="card-body">
                    <a href="orders.php" class="btn btn-secondary btn-block">
                        注文一覧に戻る
                    </a>
                    
                    <?php
                    // 保留中または処理中の注文のみキャンセル可能
                    if($order->status == 'pending' || $order->status == 'processing'): 
                    ?>
                    <a href="cancel_order.php?id=<?php echo $order->id; ?>" 
                       class="btn btn-danger btn-block mt-2" 
                       onclick="return confirm('この注文をキャンセルしますか？');">
                        注文をキャンセル
                    </a>
                    <?php endif; ?>
                    
                    <!-- PDF出力機能（実装あれば） -->
                    <a href="generate_pdf.php?id=<?php echo $order->id; ?>" class="btn btn-info btn-block mt-2">
                        注文をPDFでダウンロード
                    </a>
                </div>
            </div>
            
            <!-- お問い合わせ情報 -->
            <div class="card">
                <div class="card-header">お問い合わせ</div>
                <div class="card-body">
                    <p>ご注文について何かございましたら、お気軽にお問い合わせください。</p>
                    <a href="contact.php?order_id=<?php echo $order->id; ?>" class="btn btn-primary btn-block">
                        お問い合わせ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>