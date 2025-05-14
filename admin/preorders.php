<?php
/**
 * 予約注文管理ページ（管理者用）
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "../config/database.php";
include_once "../classes/Preorder.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$preorder = new Preorder($db);

// ステータス更新処理
if(isset($_POST['update_preorder_status'])) {
    $preorder_id = $_POST['preorder_id'];
    $new_status = $_POST['status'];
    $estimated_delivery = $_POST['estimated_delivery'] ?? null;
    
    if($preorder->updateStatus($preorder_id, $new_status)) {
        // 配送予定日の更新も行う場合
        if($estimated_delivery) {
            $query = "UPDATE preorders SET estimated_delivery = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $estimated_delivery);
            $stmt->bindParam(2, $preorder_id);
            $stmt->execute();
        }
        
        $success_message = "予約注文ステータスを更新しました。";
    } else {
        $error_message = "ステータスの更新に失敗しました。";
    }
}

// 予約注文キャンセル処理
if(isset($_GET['cancel']) && isset($_GET['id'])) {
    $preorder_id = $_GET['id'];
    if($preorder->updateStatus($preorder_id, 'cancelled')) {
        $success_message = "予約注文をキャンセルしました。";
    } else {
        $error_message = "予約注文のキャンセルに失敗しました。";
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
            <h2 class="mt-4">予約注文管理</h2>
            
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
            
            <!-- 予約注文統計 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総予約注文数</h5>
                            <h3 class="text-primary"><?php echo $preorder->count(); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">保留中</h5>
                            <h3 class="text-warning">
                                <?php 
                                $stmt = $db->prepare("SELECT COUNT(*) as count FROM preorders WHERE status = 'pending'");
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">製作中</h5>
                            <h3 class="text-info">
                                <?php 
                                $stmt = $db->prepare("SELECT COUNT(*) as count FROM preorders WHERE status = 'production'");
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">配送完了</h5>
                            <h3 class="text-success">
                                <?php 
                                $stmt = $db->prepare("SELECT COUNT(*) as count FROM preorders WHERE status = 'delivered'");
                                $stmt->execute();
                                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $row['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 予約注文一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5>予約注文一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>予約注文ID</th>
                                    <th>顧客名</th>
                                    <th>商品</th>
                                    <th>バリエーション</th>
                                    <th>数量</th>
                                    <th>注文日</th>
                                    <th>配送予定</th>
                                    <th>状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $preorder->readAll();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    extract($row);
                                    ?>
                                    <tr>
                                        <td>#<?php echo $id; ?></td>
                                        <td><?php echo $username; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/images/<?php echo $image; ?>" alt="<?php echo $product_name; ?>" width="40" class="mr-2">
                                                <?php echo $product_name; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($variation_name && $variation_value): ?>
                                                <?php echo $variation_name; ?>: <?php echo $variation_value; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $quantity; ?>個</td>
                                        <td><?php echo date('Y-m-d', strtotime($created)); ?></td>
                                        <td>
                                            <?php if($estimated_delivery): ?>
                                                <?php echo date('Y-m-d', strtotime($estimated_delivery)); ?>
                                            <?php else: ?>
                                                <span class="text-muted">未設定</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            switch($status) {
                                                case 'pending':
                                                    echo '<span class="badge badge-warning">受付中</span>';
                                                    break;
                                                case 'confirmed':
                                                    echo '<span class="badge badge-info">確定</span>';
                                                    break;
                                                case 'production':
                                                    echo '<span class="badge badge-primary">製作中</span>';
                                                    break;
                                                case 'shipped':
                                                    echo '<span class="badge badge-secondary">発送済</span>';
                                                    break;
                                                case 'delivered':
                                                    echo '<span class="badge badge-success">配送完了</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="badge badge-danger">キャンセル</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge badge-light">不明</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-primary" data-toggle="modal" 
                                                        data-target="#statusModal" 
                                                        data-preorder-id="<?php echo $id; ?>"
                                                        data-current-status="<?php echo $status; ?>"
                                                        data-estimated-delivery="<?php echo $estimated_delivery; ?>">
                                                    状態変更
                                                </button>
                                                <a href="preorder_detail.php?id=<?php echo $id; ?>" class="btn btn-sm btn-info">詳細</a>
                                                <?php if($status == 'pending' || $status == 'confirmed'): ?>
                                                <a href="preorders.php?cancel=1&id=<?php echo $id; ?>" class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('この予約注文をキャンセルしますか？')">キャンセル</a>
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
            <form method="post" action="preorders.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">予約注文ステータス変更</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="preorder_id" id="modal-preorder-id">
                    <div class="form-group">
                        <label>現在のステータス</label>
                        <input type="text" class="form-control" id="current-preorder-status" readonly>
                    </div>
                    <div class="form-group">
                        <label for="status">新しいステータス</label>
                        <select class="form-control" name="status" id="modal-status" required>
                            <option value="">選択してください</option>
                            <option value="pending">受付中</option>
                            <option value="confirmed">確定</option>
                            <option value="production">製作中</option>
                            <option value="shipped">発送済</option>
                            <option value="delivered">配送完了</option>
                            <option value="cancelled">キャンセル</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estimated_delivery">配送予定日（オプション）</label>
                        <input type="date" class="form-control" name="estimated_delivery" id="modal-estimated-delivery">
                        <small class="form-text text-muted">製作中以降のステータスで設定することを推奨します</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_preorder_status" class="btn btn-primary">ステータスを更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 予約注文ステータス変更モーダルのイベント処理
    $('#statusModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var preorderId = button.data('preorder-id');
        var currentStatus = button.data('current-status');
        var estimatedDelivery = button.data('estimated-delivery');
        
        var modal = $(this);
        modal.find('#modal-preorder-id').val(preorderId);
        modal.find('#modal-status').val('');
        modal.find('#modal-estimated-delivery').val(estimatedDelivery || '');
        
        // 現在のステータスを表示
        var statusTexts = {
            'pending': '受付中',
            'confirmed': '確定',
            'production': '製作中',
            'shipped': '発送済',
            'delivered': '配送完了',
            'cancelled': 'キャンセル'
        };
        modal.find('#current-preorder-status').val(statusTexts[currentStatus] || currentStatus);
    });

    // フォーム送信前の確認
    $('#statusModal form').on('submit', function(e) {
        var newStatus = $('#modal-status').val();
        if (!newStatus) {
            e.preventDefault();
            alert('新しいステータスを選択してください。');
            return false;
        }
        
        var preorderId = $('#modal-preorder-id').val();
        var currentStatus = $('#current-preorder-status').val();
        var newStatusText = $('#modal-status option:selected').text();
        var estimatedDelivery = $('#modal-estimated-delivery').val();
        
        var message = '予約注文 #' + preorderId + ' のステータスを変更しますか？\n\n';
        message += '現在のステータス: ' + currentStatus + '\n';
        message += '新しいステータス: ' + newStatusText;
        if(estimatedDelivery) {
            message += '\n配送予定日: ' + estimatedDelivery;
        }
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include_once "templates/footer.php"; ?>