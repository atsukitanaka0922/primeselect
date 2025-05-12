<?php
/**
 * 注文管理ページ（管理者用）
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "../config/database.php";
include_once "../classes/Order.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);

// ステータス更新処理
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    if($order->updateStatus($order_id, $new_status)) {
        $success_message = "注文ステータスを更新しました。";
    } else {
        $error_message = "ステータスの更新に失敗しました。";
    }
}

// 注文キャンセル処理
if(isset($_GET['cancel']) && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    if($order->restoreStockOnCancel($order_id)) {
        $success_message = "注文をキャンセルしました。在庫も復元されました。";
    } else {
        $error_message = "注文のキャンセルに失敗しました。";
    }
}

include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <h2 class="mt-4">注文管理</h2>
            
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 注文統計 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総注文数</h5>
                            <h3 class="text-primary"><?php echo $order->count(); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">保留中</h5>
                            <h3 class="text-warning">
                                <?php 
                                $stmt = $order->getAllOrders('pending');
                                echo $stmt->rowCount();
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">処理中</h5>
                            <h3 class="text-info">
                                <?php 
                                $stmt = $order->getAllOrders('processing');
                                echo $stmt->rowCount();
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">配達完了</h5>
                            <h3 class="text-success">
                                <?php 
                                $stmt = $order->getAllOrders('delivered');
                                echo $stmt->rowCount();
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 注文一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5>注文一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>注文ID</th>
                                    <th>顧客名</th>
                                    <th>注文日</th>
                                    <th>金額</th>
                                    <th>支払方法</th>
                                    <th>状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $order->getAllOrders();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    extract($row);
                                    ?>
                                    <tr>
                                        <td>#<?php echo $id; ?></td>
                                        <td><?php echo $username; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($created)); ?></td>
                                        <td>¥<?php echo number_format($total_amount); ?></td>
                                        <td>
                                            <?php
                                            switch($payment_method) {
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
                                        <td>
                                            <?php
                                            switch($status) {
                                                case 'pending':
                                                    echo '<span class="badge badge-warning">保留中</span>';
                                                    break;
                                                case 'processing':
                                                    echo '<span class="badge badge-info">処理中</span>';
                                                    break;
                                                case 'shipped':
                                                    echo '<span class="badge badge-primary">発送済</span>';
                                                    break;
                                                case 'delivered':
                                                    echo '<span class="badge badge-success">配達完了</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="badge badge-danger">キャンセル</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-primary" data-toggle="modal" 
                                                        data-target="#statusModal" 
                                                        data-order-id="<?php echo $id; ?>"
                                                        data-current-status="<?php echo $status; ?>">
                                                    状態変更
                                                </button>
                                                <a href="order_detail.php?id=<?php echo $id; ?>" class="btn btn-sm btn-info">詳細</a>
                                                <?php if($status == 'pending' || $status == 'processing'): ?>
                                                <a href="orders.php?cancel=1&id=<?php echo $id; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('この注文をキャンセルしますか？在庫も復元されます。')">キャンセル</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ステータス変更モーダル -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">注文ステータス変更</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modal-order-id">
                    <div class="form-group">
                        <label for="status">新しいステータス</label>
                        <select class="form-control" name="status" id="modal-status" required>
                            <option value="pending">保留中</option>
                            <option value="processing">処理中</option>
                            <option value="shipped">発送済</option>
                            <option value="delivered">配達完了</option>
                            <option value="cancelled">キャンセル</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_status" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#statusModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var orderId = button.data('order-id');
    var currentStatus = button.data('current-status');
    
    var modal = $(this);
    modal.find('#modal-order-id').val(orderId);
    modal.find('#modal-status').val(currentStatus);
});
</script>

<?php include_once "templates/footer.php"; ?>