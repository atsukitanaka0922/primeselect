<?php
/**
 * 予約注文詳細ページ（管理者用）
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

// 予約注文ID取得
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

$database = new Database();
$db = $database->getConnection();

$preorder = new Preorder($db);
$preorder_detail = $preorder->read($id);

if(!$preorder_detail) {
    header('Location: preorders.php');
    exit();
}

include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">ダッシュボード</a></li>
                    <li class="breadcrumb-item"><a href="preorders.php">予約注文管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">予約注文 #<?php echo $preorder_detail['id']; ?></li>
                </ol>
            </nav>
            
            <h2 class="mt-4">予約注文詳細</h2>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">予約注文情報</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th>予約注文番号:</th>
                                            <td>#<?php echo $preorder_detail['id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>顧客名:</th>
                                            <td><?php echo $preorder_detail['customer_name'] ?? 'N/A'; ?></td>
                                        </tr>
                                        <tr>
                                            <th>作成日:</th>
                                            <td><?php echo date('Y年n月j日', strtotime($preorder_detail['created'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>ステータス:</th>
                                            <td>
                                                <?php
                                                switch($preorder_detail['status']) {
                                                    case 'pending':
                                                        echo '<span class="badge badge-warning badge-pill">受付中</span>';
                                                        break;
                                                    case 'confirmed':
                                                        echo '<span class="badge badge-info badge-pill">確定</span>';
                                                        break;
                                                    case 'production':
                                                        echo '<span class="badge badge-primary badge-pill">製作中</span>';
                                                        break;
                                                    case 'shipped':
                                                        echo '<span class="badge badge-secondary badge-pill">発送済</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success badge-pill">配送完了</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-danger badge-pill">キャンセル</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th>配送予定日:</th>
                                            <td>
                                                <?php if($preorder_detail['estimated_delivery']): ?>
                                                    <?php echo date('Y年n月j日', strtotime($preorder_detail['estimated_delivery'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">未設定</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>数量:</th>
                                            <td><?php echo $preorder_detail['quantity']; ?>個</td>
                                        </tr>
                                        <tr>
                                            <th>合計金額:</th>
                                            <td>
                                                <?php 
                                                $total = $preorder_detail['price'] * $preorder_detail['quantity'];
                                                if(isset($preorder_detail['price_adjustment'])) {
                                                    $total += $preorder_detail['price_adjustment'] * $preorder_detail['quantity'];
                                                }
                                                echo '¥' . number_format($total);
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">商品情報</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="../assets/images/<?php echo $preorder_detail['image']; ?>" 
                                         class="img-fluid" alt="<?php echo $preorder_detail['product_name']; ?>">
                                </div>
                                <div class="col-md-8">
                                    <h5><?php echo $preorder_detail['product_name']; ?></h5>
                                    <?php if($preorder_detail['variation_name'] && $preorder_detail['variation_value']): ?>
                                        <p class="text-muted">
                                            <strong><?php echo $preorder_detail['variation_name']; ?>:</strong> 
                                            <?php echo $preorder_detail['variation_value']; ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="lead">
                                        単価: ¥<?php echo number_format($preorder_detail['price']); ?>
                                        <?php if(isset($preorder_detail['price_adjustment']) && $preorder_detail['price_adjustment'] != 0): ?>
                                            <?php if($preorder_detail['price_adjustment'] > 0): ?>
                                                <span class="text-success">(+¥<?php echo number_format($preorder_detail['price_adjustment']); ?>)</span>
                                            <?php else: ?>
                                                <span class="text-warning">(-¥<?php echo number_format(abs($preorder_detail['price_adjustment'])); ?>)</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-muted">この商品は受注生産商品です</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">アクション</div>
                        <div class="card-body">
                            <button class="btn btn-primary btn-block" data-toggle="modal" 
                                    data-target="#statusModal" 
                                    data-preorder-id="<?php echo $preorder_detail['id']; ?>"
                                    data-current-status="<?php echo $preorder_detail['status']; ?>"
                                    data-estimated-delivery="<?php echo $preorder_detail['estimated_delivery']; ?>">
                                ステータス変更
                            </button>
                            <a href="preorders.php" class="btn btn-secondary btn-block">一覧に戻る</a>
                            
                            <?php if($preorder_detail['status'] == 'pending' || $preorder_detail['status'] == 'confirmed'): ?>
                            <a href="preorders.php?cancel=1&id=<?php echo $preorder_detail['id']; ?>" 
                               class="btn btn-danger btn-block" 
                               onclick="return confirm('この予約注文をキャンセルしますか？')">
                                予約注文をキャンセル
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">履歴</div>
                        <div class="card-body">
                            <?php
                            // 履歴情報を表示（簡易実装）
                            $history_query = "SELECT * FROM preorders WHERE id = ? ORDER BY created DESC";
                            $history_stmt = $db->prepare($history_query);
                            $history_stmt->bindParam(1, $id);
                            $history_stmt->execute();
                            
                            while($history = $history_stmt->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <div class="mb-2">
                                    <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($history['created'])); ?></small><br>
                                    <span class="badge badge-light"><?php echo $history['status']; ?></span>
                                </div>
                                <?php
                                break; // 現在の状態のみ表示（実際の実装では履歴テーブルを用意する）
                            }
                            ?>
                        </div>
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
                        <label for="status">新しいステータス</label>
                        <select class="form-control" name="status" id="modal-status" required>
                            <option value="pending">受付中</option>
                            <option value="confirmed">確定</option>
                            <option value="production">製作中</option>
                            <option value="shipped">発送済</option>
                            <option value="delivered">配送完了</option>
                            <option value="cancelled">キャンセル</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estimated_delivery">配送予定日</label>
                        <input type="date" class="form-control" name="estimated_delivery" id="modal-estimated-delivery">
                        <small class="form-text text-muted">製作中以降のステータスで使用します</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_preorder_status" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#statusModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var preorderId = button.data('preorder-id');
    var currentStatus = button.data('current-status');
    var estimatedDelivery = button.data('estimated-delivery');
    
    var modal = $(this);
    modal.find('#modal-preorder-id').val(preorderId);
    modal.find('#modal-status').val(currentStatus);
    modal.find('#modal-estimated-delivery').val(estimatedDelivery);
});
</script>

<?php include_once "templates/footer.php"; ?>