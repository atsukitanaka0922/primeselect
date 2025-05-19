<?php
/**
 * orders.php - 管理者用注文管理ページ
 * 
 * 全注文の一覧表示と管理を行うための管理者用ページです。
 * 注文ステータスの変更、詳細確認、キャンセル処理などが可能です。
 * 
 * 主な機能:
 * - 注文一覧の表示と検索
 * - 注文ステータスの一括更新
 * - 注文の詳細確認
 * - 注文のキャンセル処理
 * - 注文統計情報の表示
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

// デバッグ情報の初期化
$debug_info = [];

// ステータス更新処理
if(isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    // デバッグ情報を記録
    $debug_info[] = "受信データ - 注文ID: {$order_id}, 新ステータス: {$new_status}";
    
    // 有効なステータスかチェック
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if(in_array($new_status, $valid_statuses) && $order_id > 0) {
        
        // 直接SQL実行でステータス更新
        try {
            $query = "UPDATE orders SET status = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $new_status, PDO::PARAM_STR);
            $stmt->bindParam(2, $order_id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $affected_rows = $stmt->rowCount();
                $debug_info[] = "SQL実行成功 - 影響行数: " . $affected_rows;
                
                if($affected_rows > 0) {
                    // 更新後のステータスを確認
                    $verify_query = "SELECT status FROM orders WHERE id = ?";
                    $verify_stmt = $db->prepare($verify_query);
                    $verify_stmt->bindParam(1, $order_id, PDO::PARAM_INT);
                    $verify_stmt->execute();
                    $result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if($result) {
                        $debug_info[] = "更新後のステータス: " . $result['status'];
                        $success_message = "注文ステータスを更新しました。";
                        
                        // 成功後にリダイレクト
                        header("Location: orders.php?updated=1");
                        exit();
                    } else {
                        $debug_info[] = "更新確認に失敗";
                        $error_message = "更新の確認に失敗しました。";
                    }
                } else {
                    $debug_info[] = "更新対象が見つかりません（注文ID: {$order_id}）";
                    $error_message = "指定された注文が見つかりません。";
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                $debug_info[] = "SQL実行失敗 - エラー: " . $errorInfo[2];
                $error_message = "ステータスの更新に失敗しました。";
            }
        } catch(PDOException $e) {
            $debug_info[] = "PDO例外: " . $e->getMessage();
            $error_message = "データベースエラーが発生しました。";
        }
    } else {
        $debug_info[] = "無効なデータ - ステータス: {$new_status}, 注文ID: {$order_id}";
        $error_message = "無効なデータです。";
    }
}

// 更新成功メッセージの表示
if(isset($_GET['updated']) && $_GET['updated'] == 1) {
    $success_message = "注文ステータスが正常に更新されました。";
}

// 注文キャンセル処理
if(isset($_GET['cancel']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // キャンセル処理と在庫復元を実行
    if($order->restoreStockOnCancel($order_id)) {
        $success_message = "注文をキャンセルしました。在庫も復元されました。";
        header("Location: orders.php");
        exit();
    } else {
        $error_message = "注文のキャンセルに失敗しました。";
    }
}

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
            <h2 class="mt-4">注文管理</h2>
            
            <!-- デバッグ情報がある場合に表示 -->
            <?php if(!empty($debug_info)): ?>
            <div class="alert alert-info">
                <h6>デバッグ情報:</h6>
                <ul class="mb-0">
                    <?php foreach($debug_info as $info): ?>
                        <li><?php echo htmlspecialchars($info); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
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
            
            <!-- 注文統計カード -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総注文数</h5>
                            <h3 class="text-primary">
                                <?php 
                                try {
                                    echo $order->count();
                                } catch(Exception $e) {
                                    echo "0";
                                }
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">保留中</h5>
                            <h3 class="text-warning">
                                <?php 
                                try {
                                    $stmt = $order->getAllOrders('pending');
                                    echo $stmt ? $stmt->rowCount() : 0;
                                } catch(Exception $e) {
                                    echo "0";
                                }
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
                                try {
                                    $stmt = $order->getAllOrders('processing');
                                    echo $stmt ? $stmt->rowCount() : 0;
                                } catch(Exception $e) {
                                    echo "0";
                                }
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
                                try {
                                    $stmt = $order->getAllOrders('delivered');
                                    echo $stmt ? $stmt->rowCount() : 0;
                                } catch(Exception $e) {
                                    echo "0";
                                }
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 注文一覧テーブル -->
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
                                try {
                                    // 全注文を取得
                                    $stmt = $order->getAllOrders();
                                    if($stmt) {
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            // 安全な変数抽出
                                            $id = $row['id'] ?? 0;
                                            $username = $row['username'] ?? 'N/A';
                                            $created = $row['created'] ?? '1970-01-01 00:00:00';
                                            $total_amount = $row['total_amount'] ?? 0;
                                            $payment_method = $row['payment_method'] ?? '';
                                            $status = $row['status'] ?? 'pending';
                                            ?>
                                            <tr>
                                                <td>#<?php echo $id; ?></td>
                                                <td><?php echo htmlspecialchars($username); ?></td>
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
                                                        default:
                                                            echo htmlspecialchars($payment_method);
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
                                                        default:
                                                            echo '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- 状態変更ボタン - モーダル表示用 -->
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-toggle="modal" 
                                                                data-target="#statusModal" 
                                                                data-order-id="<?php echo $id; ?>"
                                                                data-current-status="<?php echo $status; ?>">
                                                            状態変更
                                                        </button>
                                                        
                                                        <!-- 詳細ボタン -->
                                                        <a href="order_detail.php?id=<?php echo $id; ?>" class="btn btn-sm btn-info">詳細</a>
                                                        
                                                        <!-- キャンセルボタン - 保留中または処理中の場合のみ表示 -->
                                                        <?php if($status == 'pending' || $status == 'processing'): ?>
                                                        <a href="orders.php?cancel=1&id=<?php echo $id; ?>" class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('この注文をキャンセルしますか？在庫も復元されます。')">キャンセル</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                } catch(Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center text-danger">データ取得エラー: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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

<!-- ステータス変更モーダルウィンドウ -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="orders.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">注文ステータス変更</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modal-order-id">
                    <div class="form-group">
                        <label for="current-status">現在のステータス</label>
                        <input type="text" class="form-control" id="current-status" readonly>
                    </div>
                    <div class="form-group">
                        <label for="modal-status">新しいステータス</label>
                        <select class="form-control" name="status" id="modal-status" required>
                            <option value="">選択してください</option>
                            <option value="pending">保留中</option>
                            <option value="processing">処理中</option>
                            <option value="shipped">発送済</option>
                            <option value="delivered">配達完了</option>
                            <option value="cancelled">キャンセル</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>注意:</strong> ステータスを変更すると在庫に影響する場合があります。
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_status" class="btn btn-primary">ステータスを更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery とBootstrap のJS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // jQueryが読み込まれるまで待機
    function waitForJQuery(callback) {
        if (typeof $ !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 100);
        }
    }
    
    waitForJQuery(function() {
        console.log('jQuery loaded');
        
        // ステータス変更モーダルのイベント処理
        $('#statusModal').on('show.bs.modal', function (event) {
            console.log('Modal show event triggered');
            
            var button = $(event.relatedTarget);
            var orderId = button.data('order-id');
            var currentStatus = button.data('current-status');
            
            console.log('Modal data:', {orderId: orderId, currentStatus: currentStatus});
            
            var modal = $(this);
            modal.find('#modal-order-id').val(orderId);
            modal.find('#modal-status').val('');
            
            // 現在のステータスを表示
            var statusTexts = {
                'pending': '保留中',
                'processing': '処理中',
                'shipped': '発送済',
                'delivered': '配達完了',
                'cancelled': 'キャンセル'
            };
            
            var statusText = statusTexts[currentStatus] || currentStatus;
            modal.find('#current-status').val(statusText);
            console.log('Status text set to:', statusText);
        });

        // フォーム送信前の確認
        $('#statusModal form').on('submit', function(e) {
            console.log('Form submit event');
            
            var newStatus = $('#modal-status').val();
            console.log('Selected status:', newStatus);
            
            if (!newStatus) {
                e.preventDefault();
                alert('新しいステータスを選択してください。');
                return false;
            }
            
            var orderId = $('#modal-order-id').val();
            var currentStatus = $('#current-status').val();
            var newStatusText = $('#modal-status option:selected').text();
            
            console.log('Form data:', {
                orderId: orderId,
                currentStatus: currentStatus,
                newStatus: newStatus,
                newStatusText: newStatusText
            });
            
            var message = '注文 #' + orderId + ' のステータスを変更しますか？\n\n';
            message += '現在のステータス: ' + currentStatus + '\n';
            message += '新しいステータス: ' + newStatusText;
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
            
            console.log('Form submission allowed');
        });
    });
});
</script>

<?php include_once "templates/footer.php"; ?>