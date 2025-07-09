<?php
/**
 * order_detail.php - 管理者用注文詳細ページ
 * 
 * 注文の詳細情報を表示し、ステータスの更新や配送情報の管理を行うための管理者用ページです。
 * 
 * 主な機能:
 * - 注文基本情報の表示
 * - 注文商品の一覧表示
 * - 顧客情報の表示
 * - 注文ステータスの更新
 * - トラッキング番号の設定
 * - 注文のキャンセル処理
 * 
 * @package PrimeSelect
 * @subpackage Admin
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルを読み込み
include_once "../config/database.php";
include_once "../classes/Order.php";

// 管理者権限チェック - 権限がなければログインページへリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続を作成
$database = new Database();
$db = $database->getConnection();

// 注文オブジェクトを初期化
$order = new Order($db);

// 注文ID取得 - IDがなければエラー表示
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// 注文情報を取得
$order->read($id);

// 注文が存在しない場合はリダイレクト
if(!$order->id) {
    header('Location: orders.php');
    exit();
}

// ステータス更新処理
if(isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $tracking_number = isset($_POST['tracking_number']) ? $_POST['tracking_number'] : '';
    
    // 注文ステータスを更新
    if($order->updateStatus($id, $new_status)) {
        // トラッキング番号がある場合は更新
        if($tracking_number) {
            $query = "UPDATE orders SET tracking_number = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $tracking_number);
            $stmt->bindParam(2, $id);
            $stmt->execute();
        }
        
        $success_message = "注文ステータスを更新しました。";
        // 情報を再読み込み
        $order->read($id);
    } else {
        $error_message = "ステータスの更新に失敗しました。";
    }
}

// 注文キャンセル処理
if(isset($_GET['cancel']) && $_GET['cancel'] == '1') {
    // キャンセル処理と在庫復元を実行
    if($order->restoreStockOnCancel($id)) {
        $success_message = "注文をキャンセルしました。在庫も復元されました。";
        // 情報を再読み込み
        $order->read($id);
    } else {
        $error_message = "注文のキャンセルに失敗しました。";
    }
}

// 支払い情報を取得
$payment_query = "SELECT * FROM payments WHERE order_id = ?";
$payment_stmt = $db->prepare($payment_query);
$payment_stmt->bindParam(1, $id);
$payment_stmt->execute();
$payment_info = $payment_stmt->fetch(PDO::FETCH_ASSOC);

// ヘッダーテンプレートを読み込み
include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <!-- サイドバー -->
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- メインコンテンツ -->
        <div class="col-md-10">
            <!-- パンくずリスト -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">ダッシュボード</a></li>
                    <li class="breadcrumb-item"><a href="orders.php">注文管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">注文 #<?php echo $order->id; ?></li>
                </ol>
            </nav>
            
            <h2 class="mt-4">注文詳細 - #<?php echo $order->id; ?></h2>
            
            <!-- 成功/エラーメッセージがあれば表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- 注文基本情報 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>注文情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th>注文番号:</th>
                                            <td>#<?php echo $order->id; ?></td>
                                        </tr>
                                        <tr>
                                            <th>注文日:</th>
                                            <td><?php echo date('Y年n月j日 H:i', strtotime($order->created)); ?></td>
                                        </tr>
                                        <tr>
                                            <th>注文状況:</th>
                                            <td>
                                                <?php
                                                // 注文ステータスに応じたバッジを表示
                                                switch($order->status) {
                                                    case 'pending':
                                                        echo '<span class="badge badge-warning badge-pill">保留中</span>';
                                                        break;
                                                    case 'processing':
                                                        echo '<span class="badge badge-info badge-pill">処理中</span>';
                                                        break;
                                                    case 'shipped':
                                                        echo '<span class="badge badge-primary badge-pill">発送済み</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success badge-pill">配達済み</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-danger badge-pill">キャンセル済み</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>合計金額:</th>
                                            <td><strong>¥<?php echo number_format($order->total_amount); ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th>お支払い方法:</th>
                                            <td>
                                                <?php
                                                // 支払い方法を表示
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
                                            <th>支払い状況:</th>
                                            <td>
                                                <?php if($payment_info): ?>
                                                    <?php
                                                    // 支払いステータスに応じたバッジを表示
                                                    switch($payment_info['payment_status']) {
                                                        case 'completed':
                                                            echo '<span class="badge badge-success">完了</span>';
                                                            break;
                                                        case 'pending':
                                                            echo '<span class="badge badge-warning">保留中</span>';
                                                            break;
                                                        case 'failed':
                                                            echo '<span class="badge badge-danger">失敗</span>';
                                                            break;
                                                    }
                                                    ?>
                                                    <br><small>取引ID: <?php echo $payment_info['transaction_id']; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">支払い情報なし</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>トラッキング番号:</th>
                                            <td>
                                                <?php
                                                // トラッキング番号があれば表示
                                                $tracking_query = "SELECT tracking_number FROM orders WHERE id = ?";
                                                $tracking_stmt = $db->prepare($tracking_query);
                                                $tracking_stmt->bindParam(1, $id);
                                                $tracking_stmt->execute();
                                                $tracking_info = $tracking_stmt->fetch(PDO::FETCH_ASSOC);
                                                
                                                if($tracking_info && $tracking_info['tracking_number']) {
                                                    echo $tracking_info['tracking_number'];
                                                } else {
                                                    echo '<span class="text-muted">未設定</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 顧客情報 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>顧客情報</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>基本情報</h6>
                                    <?php
                                    // 顧客情報を取得
                                    $customer_query = "SELECT u.* FROM users u JOIN orders o ON u.id = o.user_id WHERE o.id = ?";
                                    $customer_stmt = $db->prepare($customer_query);
                                    $customer_stmt->bindParam(1, $id);
                                    $customer_stmt->execute();
                                    $customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <p><strong>名前:</strong> <?php echo htmlspecialchars($customer['username']); ?></p>
                                    <p><strong>メール:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                                    <p><strong>会員登録日:</strong> <?php echo date('Y年n月j日', strtotime($customer['created'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>配送先住所</h6>
                                    <address>
                                        <?php echo nl2br(htmlspecialchars($order->shipping_address)); ?>
                                    </address>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 注文商品 -->
                    <div class="card">
                        <div class="card-header">
                            <h5>注文商品</h5>
                        </div>
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
                                        // 注文商品を取得して表示
                                        $items = $order->getOrderItems($order->id);
                                        $total_verification = 0;
                                        
                                        while($row = $items->fetch(PDO::FETCH_ASSOC)) {
                                            // 商品情報を抽出
                                            $price = $row['price'];
                                            $quantity = $row['quantity'];
                                            $name = $row['name'];
                                            $image = $row['image'];
                                            $variation_name = isset($row['variation_name']) ? $row['variation_name'] : null;
                                            $variation_value = isset($row['variation_value']) ? $row['variation_value'] : null;
                                            
                                            // 小計を計算
                                            $subtotal = $price * $quantity;
                                            $total_verification += $subtotal;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../assets/images/<?php echo $image; ?>" width="50" alt="<?php echo $name; ?>" class="mr-3">
                                                        <div>
                                                            <span><?php echo htmlspecialchars($name); ?></span>
                                                            <?php if(isset($variation_name) && isset($variation_value)): ?>
                                                            <div><small class="text-muted"><?php echo htmlspecialchars($variation_name); ?>: <?php echo htmlspecialchars($variation_value); ?></small></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>¥<?php echo number_format($price); ?></td>
                                                <td><?php echo $quantity; ?>個</td>
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
                
                <!-- 右側サイドバー -->
                <div class="col-md-4">
                    <!-- ステータス変更 -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>ステータス変更</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="form-group">
                                    <label for="status">新しいステータス</label>
                                    <select class="form-control" name="status" id="status" required>
                                        <option value="">選択してください</option>
                                        <option value="pending" <?php echo $order->status == 'pending' ? 'selected' : ''; ?>>保留中</option>
                                        <option value="processing" <?php echo $order->status == 'processing' ? 'selected' : ''; ?>>処理中</option>
                                        <option value="shipped" <?php echo $order->status == 'shipped' ? 'selected' : ''; ?>>発送済み</option>
                                        <option value="delivered" <?php echo $order->status == 'delivered' ? 'selected' : ''; ?>>配達済み</option>
                                        <option value="cancelled" <?php echo $order->status == 'cancelled' ? 'selected' : ''; ?>>キャンセル</option>
                                    </select>
                                </div>
                                <div class="form-group" id="tracking-group" style="display: none;">
                                    <label for="tracking_number">トラッキング番号（配送時）</label>
                                    <input type="text" class="form-control" name="tracking_number" id="tracking_number" 
                                           value="<?php echo isset($tracking_info['tracking_number']) ? $tracking_info['tracking_number'] : ''; ?>">
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary btn-block">ステータス更新</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- アクション -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>アクション</h5>
                        </div>
                        <div class="card-body">
                            <a href="orders.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left"></i> 注文一覧に戻る
                            </a>
                            
                            <?php if($order->status == 'pending' || $order->status == 'processing'): ?>
                            <a href="order_detail.php?id=<?php echo $order->id; ?>&cancel=1" 
                               class="btn btn-danger btn-block mt-2" 
                               onclick="return confirm('この注文をキャンセルしますか？在庫も復元されます。')">
                                <i class="fas fa-times"></i> 注文をキャンセル
                            </a>
                            <?php endif; ?>
                            
                            <a href="#" class="btn btn-info btn-block mt-2" onclick="window.print()">
                                <i class="fas fa-print"></i> 注文書を印刷
                            </a>
                        </div>
                    </div>
                    
                    <!-- 注文履歴 -->
                    <div class="card">
                        <div class="card-header">
                            <h5>注文履歴</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // 注文履歴を取得（簡易実装）
                            $history_query = "SELECT created FROM orders WHERE id = ?";
                            $history_stmt = $db->prepare($history_query);
                            $history_stmt->bindParam(1, $id);
                            $history_stmt->execute();
                            $history = $history_stmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6>注文確定</h6>
                                        <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($history['created'])); ?></small>
                                    </div>
                                </div>
                                
                                <?php if($payment_info && $payment_info['payment_status'] == 'completed'): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6>決済完了</h6>
                                        <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($payment_info['created'])); ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($order->status == 'shipped' || $order->status == 'delivered'): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6>発送完了</h6>
                                        <small class="text-muted">現在のステータス</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // ステータス変更時のトラッキング番号入力フィールド表示制御
    $('#status').on('change', function() {
        var status = $(this).val();
        var trackingGroup = $('#tracking-group');
        
        // 発送済みまたは配達済みの場合にトラッキング番号入力を表示
        if(status === 'shipped' || status === 'delivered') {
            trackingGroup.show();
            $('#tracking_number').prop('required', true);
        } else {
            trackingGroup.hide();
            $('#tracking_number').prop('required', false);
        }
    });
    
    // 初期表示時の制御
    var currentStatus = $('#status').val();
    if(currentStatus === 'shipped' || currentStatus === 'delivered') {
        $('#tracking-group').show();
    }
    
    // フォーム送信時の確認
    $('form').on('submit', function(e) {
        var status = $('#status').val();
        var statusTexts = {
            'pending': '保留中',
            'processing': '処理中',
            'shipped': '発送済み',
            'delivered': '配達済み',
            'cancelled': 'キャンセル'
        };
        
        if(!status) {
            e.preventDefault();
            alert('ステータスを選択してください。');
            return false;
        }
        
        var message = '注文 #<?php echo $order->id; ?> のステータスを「' + statusTexts[status] + '」に変更しますか？';
        
        if(status === 'cancelled') {
            message += '\n\n注意: キャンセルすると在庫が復元されます。';
        }
        
        if(!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<style>
/* タイムライン用CSS */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #6c757d;
    border: 2px solid #fff;
}

.timeline::before {
    content: '';
    position: absolute;
    left: -30px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item:last-child::after {
    display: none;
}

/* 印刷用CSS */
@media print {
    .card-header, .btn, .alert, nav, .col-md-4 {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php include_once "templates/footer.php"; ?>