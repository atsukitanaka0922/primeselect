<?php
/**
 * preorders.php - 予約注文管理ページ（管理者用）
 * 
 * 受注生産商品に対する予約注文を管理するための管理者用ページです。
 * 予約注文一覧の表示、ステータス更新、詳細確認などの機能を提供します。
 * 
 * 主な機能:
 * - 予約注文一覧の表示
 * - 予約注文ステータスの更新
 * - 予約注文のキャンセル
 * - 受注生産商品の一覧表示
 * - 統計情報の表示
 * 
 * @package PrimeSelect
 * @subpackage Admin
 * @author Prime Select Team
 * @version 1.1
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "../config/database.php";
include_once "../classes/Preorder.php";
include_once "../classes/Product.php";

// 管理者権限チェック - 権限がない場合はログインページにリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続の確立
$database = new Database();
$db = $database->getConnection();

// 予約注文と商品クラスのインスタンス化
$preorder = new Preorder($db);
$product = new Product($db);

// デバッグ情報を保存する配列
$debug_info = [];

/**
 * 予約注文ステータス更新処理
 * POST リクエストから予約注文ID、新しいステータス、配送予定日を受け取り、
 * データベースを更新します。
 */
if(isset($_POST['update_preorder_status'])) {
    $preorder_id = intval($_POST['preorder_id']);
    $new_status = $_POST['status'];
    $estimated_delivery = $_POST['estimated_delivery'] ?? null;
    
    // デバッグ情報の記録
    $debug_info[] = "受信データ - 予約注文ID: {$preorder_id}, 新ステータス: {$new_status}, 配送予定日: {$estimated_delivery}";
    
    // 有効なステータスかチェック
    $valid_statuses = ['pending', 'confirmed', 'production', 'shipped', 'delivered', 'cancelled'];
    if(in_array($new_status, $valid_statuses) && $preorder_id > 0) {
        
        try {
            // ステータス更新の実行
            if($preorder->updateStatus($preorder_id, $new_status)) {
                // 配送予定日の更新（設定されている場合のみ）
                if($estimated_delivery) {
                    $preorder->updateEstimatedDelivery($preorder_id, $estimated_delivery);
                }
                
                $debug_info[] = "ステータス更新成功";
                $success_message = "予約注文ステータスを更新しました。";
                
                // 成功後にリダイレクト（更新パラメータ付き）
                header("Location: preorders.php?updated=1");
                exit();
            } else {
                $debug_info[] = "ステータス更新失敗";
                $error_message = "ステータスの更新に失敗しました。";
            }
        } catch(Exception $e) {
            $debug_info[] = "例外発生: " . $e->getMessage();
            $error_message = "エラーが発生しました: " . $e->getMessage();
        }
    } else {
        $debug_info[] = "無効なデータ - ステータス: {$new_status}, 予約注文ID: {$preorder_id}";
        $error_message = "無効なデータです。";
    }
}

// 更新成功メッセージの表示（リダイレクト後）
if(isset($_GET['updated']) && $_GET['updated'] == 1) {
    $success_message = "予約注文ステータスが正常に更新されました。";
}

/**
 * 予約注文キャンセル処理
 * キャンセル要求を受け取り、指定された予約注文のステータスを
 * 'cancelled'に更新します。
 */
if(isset($_GET['cancel']) && isset($_GET['id'])) {
    $preorder_id = intval($_GET['id']);
    if($preorder->updateStatus($preorder_id, 'cancelled')) {
        $success_message = "予約注文をキャンセルしました。";
        header("Location: preorders.php");
        exit();
    } else {
        $error_message = "予約注文のキャンセルに失敗しました。";
    }
}

// ヘッダーテンプレートのインクルード
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
            <h2 class="mt-4">予約注文管理</h2>
            
            <!-- デバッグ情報表示（開発時のみ表示） -->
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
            
            <!-- 成功メッセージ表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- エラーメッセージ表示 -->
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- 予約注文統計情報 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総予約注文数</h5>
                            <h3 class="text-primary">
                                <?php 
                                try {
                                    echo $preorder->count();
                                } catch(Exception $e) {
                                    echo "0"; // エラー時のフォールバック
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
                                    echo $preorder->countByStatus('pending');
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
                            <h5 class="card-title">製作中</h5>
                            <h3 class="text-info">
                                <?php 
                                try {
                                    echo $preorder->countByStatus('production');
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
                            <h5 class="card-title">配送完了</h5>
                            <h3 class="text-success">
                                <?php 
                                try {
                                    echo $preorder->countByStatus('delivered');
                                } catch(Exception $e) {
                                    echo "0";
                                }
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 予約注文一覧テーブル -->
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
                                try {
                                    // 全予約注文を取得
                                    $stmt = $preorder->readAll();
                                    if($stmt) {
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            // 安全な変数抽出
                                            $id = $row['id'] ?? 0;
                                            $username = $row['username'] ?? 'N/A';
                                            $product_name = $row['product_name'] ?? 'N/A';
                                            $image = $row['image'] ?? 'no-image.jpg';
                                            $variation_name = $row['variation_name'] ?? null;
                                            $variation_value = $row['variation_value'] ?? null;
                                            $quantity = $row['quantity'] ?? 0;
                                            $created = $row['created'] ?? '1970-01-01 00:00:00';
                                            $estimated_delivery = $row['estimated_delivery'] ?? null;
                                            $status = $row['status'] ?? 'pending';
                                            ?>
                                            <tr>
                                                <td>#<?php echo $id; ?></td>
                                                <td><?php echo htmlspecialchars($username); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if($image): ?>
                                                            <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product_name); ?>" width="40" class="mr-2">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($product_name); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($variation_name && $variation_value): ?>
                                                        <?php echo htmlspecialchars($variation_name); ?>: <?php echo htmlspecialchars($variation_value); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $quantity; ?>個</td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($created)); ?></td>
                                                <td>
                                                    <?php if($estimated_delivery): ?>
                                                        <?php echo date('Y-m-d', strtotime($estimated_delivery)); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">未設定</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // ステータス表示用のバッジスタイル
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
                                                            echo '<span class="badge badge-light">' . htmlspecialchars($status) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- ステータス変更ボタン -->
                                                        <button class="btn btn-sm btn-primary" 
                                                                data-toggle="modal" 
                                                                data-target="#statusModal" 
                                                                data-preorder-id="<?php echo $id; ?>"
                                                                data-current-status="<?php echo $status; ?>"
                                                                data-estimated-delivery="<?php echo $estimated_delivery; ?>">
                                                            状態変更
                                                        </button>
                                                        <!-- 詳細表示ボタン -->
                                                        <a href="preorder_detail.php?id=<?php echo $id; ?>" class="btn btn-sm btn-info">詳細</a>
                                                        
                                                        <!-- キャンセルボタン（受付中または確定状態の場合のみ表示） -->
                                                        <?php if($status == 'pending' || $status == 'confirmed'): ?>
                                                        <a href="preorders.php?cancel=1&id=<?php echo $id; ?>" class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('この予約注文をキャンセルしますか？')">キャンセル</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                } catch(Exception $e) {
                                    echo '<tr><td colspan="9" class="text-center text-danger">データ取得エラー: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 受注生産商品一覧 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>受注生産商品一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>商品ID</th>
                                    <th>商品名</th>
                                    <th>カテゴリ</th>
                                    <th>価格</th>
                                    <th>制作期間</th>
                                    <th>予約注文数</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    // 受注生産商品を取得
                                    $query = "SELECT p.*, c.name as category_name,
                                                     (SELECT COUNT(*) FROM preorders pr WHERE pr.product_id = p.id) as preorder_count
                                              FROM products p 
                                              LEFT JOIN categories c ON p.category_id = c.id 
                                              WHERE p.is_preorder = 1 
                                              ORDER BY p.id DESC";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['category_name'] ?? '未分類'); ?></td>
                                            <td>¥<?php echo number_format($row['price']); ?></td>
                                            <td><?php echo htmlspecialchars($row['preorder_period'] ?? '-'); ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $row['preorder_count']; ?>件</span>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } catch(Exception $e) {
                                    echo '<tr><td colspan="6" class="text-center text-danger">データ取得エラー: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
                        <input type="text" class="form-control" id="current-status" readonly>
                    </div>
                    <div class="form-group">
                        <label for="modal-status">新しいステータス</label>
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
                        <label for="modal-estimated-delivery">配送予定日（オプション）</label>
                        <input type="date" class="form-control" name="estimated_delivery" id="modal-estimated-delivery">
                        <small class="form-text text-muted">製作中以降のステータスで設定することを推奨します</small>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <strong>注意:</strong> ステータス変更は予約注文の進捗を反映し、お客様にも通知されます。
                        </small>
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

<!-- JavaScript for the modal functionality -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - preorders.php');
    
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
        console.log('jQuery loaded - preorders.php');
        
        // 予約注文ステータス変更モーダルのイベント処理
        $('#statusModal').on('show.bs.modal', function (event) {
            console.log('Preorder modal show event triggered');
            
            var button = $(event.relatedTarget);
            var preorderId = button.data('preorder-id');
            var currentStatus = button.data('current-status');
            var estimatedDelivery = button.data('estimated-delivery');
            
            console.log('Preorder modal data:', {
                preorderId: preorderId, 
                currentStatus: currentStatus,
                estimatedDelivery: estimatedDelivery
            });
            
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
            
            var statusText = statusTexts[currentStatus] || currentStatus;
            modal.find('#current-status').val(statusText);
            console.log('Preorder status text set to:', statusText);
        });

        // フォーム送信前の確認
        $('#statusModal form').on('submit', function(e) {
            console.log('Preorder form submit event');
            
            var newStatus = $('#modal-status').val();
            console.log('Selected preorder status:', newStatus);
            
            if (!newStatus) {
                e.preventDefault();
                alert('新しいステータスを選択してください。');
                return false;
            }
            
            var preorderId = $('#modal-preorder-id').val();
            var currentStatus = $('#current-status').val();
            var newStatusText = $('#modal-status option:selected').text();
            var estimatedDelivery = $('#modal-estimated-delivery').val();
            
            console.log('Preorder form data:', {
                preorderId: preorderId,
                currentStatus: currentStatus,
                newStatus: newStatus,
                newStatusText: newStatusText,
                estimatedDelivery: estimatedDelivery
            });
            
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
            
            console.log('Preorder form submission allowed');
        });
    });
});
</script>

<?php include_once "templates/footer.php"; ?>