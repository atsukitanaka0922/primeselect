<?php
session_start();
include_once "config/database.php";
include_once "classes/Order.php";

// 未ログインならログインページへ
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'orders.php';
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);

// 注文ID取得
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// 注文情報取得
$order->read($id);

// 注文がユーザーのものでない場合はリダイレクト
if($order->user_id != $_SESSION['user_id']) {
    header('Location: orders.php');
    exit();
}

include_once "templates/header.php";
?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">ホーム</a></li>
            <li class="breadcrumb-item"><a href="orders.php">注文履歴</a></li>
            <li class="breadcrumb-item active" aria-current="page">注文 #<?php echo $order->id; ?></li>
        </ol>
    </nav>
    
    <h2>注文詳細</h2>
    
    <div class="row">
        <div class="col-md-8">
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
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>お支払い方法:</th>
                            <td>
                                <?php
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
                                $items = $order->getOrderItems($order->id);
                                
                                while($row = $items->fetch(PDO::FETCH_ASSOC)) {
                                    extract($row);
                                    $subtotal = $price * $quantity;
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="assets/images/<?php echo $image; ?>" width="50" alt="<?php echo $name; ?>">
                                            <?php echo $name; ?>
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
                                <tr>
                                    <td colspan="3" class="text-right"><strong>合計</strong></td>
                                    <td><strong>¥<?php echo number_format($order->total_amount); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">アクション</div>
                <div class="card-body">
                    <a href="orders.php" class="btn btn-secondary btn-block">注文一覧に戻る</a>
                    
                    <?php if($order->status == 'pending' || $order->status == 'processing'): ?>
                    <a href="cancel_order.php?id=<?php echo $order->id; ?>" class="btn btn-danger btn-block" onclick="return confirm('本当にこの注文をキャンセルしますか？');">注文をキャンセル</a>
                    <?php endif; ?>

                    <a href="generate_pdf.php?id=<?php echo $order->id; ?>" class="btn btn-info btn-block">注文をPDFでダウンロード</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">お問い合わせ</div>
                <div class="card-body">
                    <p>ご注文について何かございましたら、お気軽にお問い合わせください。</p>
                    <a href="contact.php?order_id=<?php echo $order->id; ?>" class="btn btn-primary btn-block">お問い合わせ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>