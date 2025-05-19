<?php
/**
 * stock_management.php - 在庫管理ページ（管理者用）
 * 
 * 商品在庫の表示、調整、在庫履歴の管理などを行うための管理者用ページです。
 * 商品ごとの在庫状況の確認や在庫の増減操作、履歴の確認ができます。
 * 
 * 主な機能:
 * - 在庫一覧の表示
 * - 在庫の追加・減少処理
 * - 在庫変更履歴の表示
 * - 在庫調整モーダル
 * 
 * @package PrimeSelect
 * @subpackage Admin
 * @author Prime Select Team
 * @version 1.2
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "../config/database.php";
include_once "../classes/Product.php";

// 管理者権限チェック - 権限がない場合はログインページにリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続
$database = new Database();
$db = $database->getConnection();

// Product クラスのインスタンス化
$product = new Product($db);

// デバッグ情報を保存する配列
$debug_info = [];

/**
 * 在庫更新処理
 * 在庫変更フォームが送信された場合に実行されます。
 */
if(isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $variation_id = !empty($_POST['variation_id']) ? $_POST['variation_id'] : null;
    $quantity_change = intval($_POST['quantity_change']);
    $reason = $_POST['reason'];
    
    // 現在の在庫を確認
    $current_stock = $product->checkStock($product_id, $variation_id)['stock'];
    $new_stock = $current_stock + $quantity_change;
    
    // 在庫が負の値にならないかチェック
    if($new_stock < 0) {
        $error_message = "在庫が不足します。現在の在庫: " . $current_stock . "個";
    } else {
        // 在庫を更新
        if($product->updateStock($product_id, $variation_id, $quantity_change, $reason)) {
            $success_message = "在庫を更新しました。新しい在庫: " . $new_stock . "個";
        } else {
            $error_message = "在庫の更新に失敗しました。";
        }
    }
}

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<style>
/* 在庫管理ページ用のカスタムスタイル */
.stock-low {
    color: #dc3545;
    font-weight: bold;
}

.stock-medium {
    color: #ffc107;
    font-weight: bold;
}

.stock-high {
    color: #28a745;
    font-weight: bold;
}

.stock-out {
    color: #6c757d;
    font-style: italic;
}

.is-invalid {
    border-color: #dc3545 !important;
}

#stockModal .form-group {
    margin-bottom: 1rem;
}

#stockModal .alert {
    margin-top: 1rem;
}
</style>

<div class="container-fluid">
    <div class="row">
        <!-- サイドバー -->
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- メインコンテンツ -->
        <div class="col-md-10">
            <h2 class="mt-4">在庫管理</h2>
            
            <!-- 成功メッセージ表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <!-- エラーメッセージ表示 -->
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 在庫一覧テーブル -->
            <div class="card">
                <div class="card-header">商品在庫一覧</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>商品名</th>
                                    <th>バリエーション</th>
                                    <th>現在在庫</th>
                                    <th>在庫状況</th>
                                    <th>在庫調整</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 商品一覧を取得
                                $stmt = $product->read();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $product_id = $row['id'];
                                    $product_name = $row['name'];
                                    
                                    // 受注生産商品かどうかチェック
                                    $preorder_info = $product->getPreorderInfo($product_id);
                                    
                                    // 受注生産商品でない場合のみ表示（受注生産品は在庫管理対象外）
                                    if(!$preorder_info['is_preorder']) {
                                        // バリエーションを取得
                                        $variations = $product->getProductVariations($product_id);
                                        $variation_data = [];
                                        while($var = $variations->fetch(PDO::FETCH_ASSOC)) {
                                            $variation_data[] = $var;
                                        }
                                        
                                        // バリエーションがない場合は基本在庫を表示
                                        if(empty($variation_data)) {
                                            $main_stock = $row['stock'];
                                            $stock_status = $product->getStockStatus($main_stock);
                                            $badge_class = $stock_status == 'out_of_stock' ? 'danger' : ($stock_status == 'low_stock' ? 'warning' : 'success');
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product_name); ?></td>
                                                <td>-</td>
                                                <td><?php echo $main_stock; ?>個</td>
                                                <td><span class="badge badge-<?php echo $badge_class; ?>"><?php echo ucfirst($stock_status); ?></span></td>
                                                <td>
                                                    <!-- 在庫調整ボタン - モーダル表示 -->
                                                    <button type="button" 
                                                            class="btn btn-sm btn-primary stock-adjust-btn" 
                                                            data-toggle="modal" 
                                                            data-target="#stockModal" 
                                                            data-product-id="<?php echo $product_id; ?>"
                                                            data-variation-id=""
                                                            data-product-name="<?php echo htmlspecialchars($product_name); ?>"
                                                            data-variation-name=""
                                                            data-current-stock="<?php echo $main_stock; ?>">
                                                        在庫調整
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php
                                        } else {
                                            // バリエーションがある場合は各バリエーションの在庫を表示
                                            foreach($variation_data as $var) {
                                                $stock_status = $product->getStockStatus($var['stock']);
                                                $badge_class = $stock_status == 'out_of_stock' ? 'danger' : ($stock_status == 'low_stock' ? 'warning' : 'success');
                                                $variation_display = $var['variation_name'] . ': ' . $var['variation_value'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product_name); ?></td>
                                                    <td><?php echo htmlspecialchars($variation_display); ?></td>
                                                    <td><?php echo $var['stock']; ?>個</td>
                                                    <td><span class="badge badge-<?php echo $badge_class; ?>"><?php echo ucfirst($stock_status); ?></span></td>
                                                    <td>
                                                        <!-- 在庫調整ボタン - モーダル表示 -->
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary stock-adjust-btn" 
                                                                data-toggle="modal" 
                                                                data-target="#stockModal" 
                                                                data-product-id="<?php echo $product_id; ?>"
                                                                data-variation-id="<?php echo $var['id']; ?>"
                                                                data-product-name="<?php echo htmlspecialchars($product_name); ?>"
                                                                data-variation-name="<?php echo htmlspecialchars($variation_display); ?>"
                                                                data-current-stock="<?php echo $var['stock']; ?>">
                                                            在庫調整
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 在庫変更ログ -->
            <div class="card mt-4">
                <div class="card-header">在庫変更ログ</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>日時</th>
                                    <th>商品</th>
                                    <th>バリエーション</th>
                                    <th>種別</th>
                                    <th>数量</th>
                                    <th>理由</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 在庫変更ログを取得（最新20件）
                                $query = "SELECT psl.*, p.name as product_name, 
                                                 pv.variation_name, pv.variation_value 
                                        FROM product_stock_logs psl 
                                        LEFT JOIN products p ON psl.product_id = p.id 
                                        LEFT JOIN product_variations pv ON psl.variation_id = pv.id 
                                        ORDER BY psl.created DESC 
                                        LIMIT 20";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                while($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($log['created'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['product_name']); ?></td>
                                        <td><?php echo $log['variation_name'] ? htmlspecialchars($log['variation_name'] . ': ' . $log['variation_value']) : '-'; ?></td>
                                        <td>
                                            <?php
                                            // 在庫変更種別のバッジ表示
                                            switch($log['type']) {
                                                case 'in':
                                                    echo '<span class="badge badge-success">入庫</span>';
                                                    break;
                                                case 'out':
                                                    echo '<span class="badge badge-danger">出庫</span>';
                                                    break;
                                                case 'adjust':
                                                    echo '<span class="badge badge-warning">調整</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $log['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($log['reason']); ?></td>
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

<!-- 在庫調整モーダル -->
<div class="modal fade" id="stockModal" tabindex="-1" role="dialog" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalLabel">在庫調整</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- 非表示の入力フィールド - 商品IDとバリエーションID -->
                    <input type="hidden" name="product_id" id="modal-product-id">
                    <input type="hidden" name="variation_id" id="modal-variation-id">
                    
                    <div class="form-group">
                        <label>商品名</label>
                        <p id="modal-product-name" class="form-control-plaintext"></p>
                    </div>
                    <div class="form-group">
                        <label>バリエーション</label>
                        <p id="modal-variation-name" class="form-control-plaintext"></p>
                    </div>
                    <div class="form-group">
                        <label for="current_stock">現在の在庫数</label>
                        <input type="text" class="form-control" id="current_stock" readonly>
                    </div>
                    <div class="form-group">
                        <label for="quantity_change">在庫変更数 <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantity_change" name="quantity_change" required>
                        <small class="form-text text-muted">正数で入庫、負数で出庫</small>
                    </div>
                    <div class="form-group">
                        <label for="new_stock">変更後の在庫数</label>
                        <input type="text" class="form-control" id="new_stock" readonly>
                    </div>
                    <div class="form-group">
                        <label for="reason">理由 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="reason" name="reason" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_stock" class="btn btn-primary">在庫更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Libraries の読み込み -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- 在庫調整モーダル用JavaScript -->
<script>
// ページが完全に読み込まれた後に実行
document.addEventListener('DOMContentLoaded', function() {
    console.log('jQuery loaded successfully');
    console.log('在庫管理ページが読み込まれました');
    
    // 在庫調整モーダルのイベント処理
    $('#stockModal').on('show.bs.modal', function (event) {
        console.log('モーダルが開かれました');
        
        var button = $(event.relatedTarget);
        console.log('クリックされたボタン:', button);
        
        // data属性を取得
        var productId = button.attr('data-product-id');
        var variationId = button.attr('data-variation-id');
        var productName = button.attr('data-product-name');
        var variationName = button.attr('data-variation-name');
        var currentStock = button.attr('data-current-stock');
        
        // デバッグ情報をコンソールに出力
        console.log('取得したデータ:', {
            productId: productId,
            variationId: variationId,
            productName: productName,
            variationName: variationName,
            currentStock: currentStock
        });
        
        var modal = $(this);
        
        // 値を設定
        modal.find('#modal-product-id').val(productId || '');
        modal.find('#modal-variation-id').val(variationId || '');
        modal.find('#modal-product-name').text(productName || '商品名が取得できませんでした');
        modal.find('#modal-variation-name').text(variationName || '-');
        modal.find('#current_stock').val(currentStock || '0');
        modal.find('#quantity_change').val('').removeClass('is-invalid');
        modal.find('#new_stock').val(currentStock || '0').removeClass('is-invalid');
        modal.find('#reason').val('');
        
        // 在庫変更数入力時のイベントリスナーをクリア
        $('#quantity_change').off('input.stock');
        
        // 在庫変更数入力時に新在庫数を計算
        $('#quantity_change').on('input.stock', function() {
            var change = parseInt($(this).val()) || 0;
            var newStock = parseInt(currentStock || 0) + change;
            $('#new_stock').val(newStock);
            
            // 在庫が負の値になる場合は警告
            if(newStock < 0) {
                $('#new_stock').addClass('is-invalid');
                $(this).addClass('is-invalid');
            } else {
                $('#new_stock').removeClass('is-invalid');
                $(this).removeClass('is-invalid');
            }
        });
    });

    // モーダルクローズ時のクリーンアップ
    $('#stockModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
    });

    // フォーム送信時のバリデーション
    $('#stockModal').on('submit', 'form', function(e) {
        var newStock = parseInt($('#new_stock').val());
        var change = parseInt($('#quantity_change').val());
        var reason = $('#reason').val().trim();
        
        console.log('フォーム送信データ:', {
            newStock: newStock,
            change: change,
            reason: reason
        });
        
        // 入力値のチェック
        if(isNaN(change) || change === 0) {
            e.preventDefault();
            alert('在庫変更数を入力してください。');
            $('#quantity_change').focus();
            return false;
        }
        
        if(!reason) {
            e.preventDefault();
            alert('理由を入力してください。');
            $('#reason').focus();
            return false;
        }
        
        if(newStock < 0) {
            e.preventDefault();
            alert('在庫が負の値になります。在庫変更数を確認してください。');
            $('#quantity_change').focus();
            return false;
        }
        
        // 確認メッセージ
        var productName = $('#modal-product-name').text();
        var variationName = $('#modal-variation-name').text();
        var currentStock = $('#current_stock').val();
        
        var message = '在庫を ' + change + ' 変更しますか？\n\n';
        message += '商品: ' + productName + '\n';
        if(variationName !== '-') {
            message += 'バリエーション: ' + variationName + '\n';
        }
        message += '現在在庫: ' + currentStock + '個\n';
        message += '変更後在庫: ' + newStock + '個\n';
        message += '理由: ' + reason;
        
        if(!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // ページ読み込み後にボタンの確認
    setTimeout(function() {
        console.log('ページ内の在庫調整ボタン数:', $('.stock-adjust-btn').length);
        $('.stock-adjust-btn').each(function(index) {
            console.log('ボタン ' + index + ':', {
                productId: $(this).attr('data-product-id'),
                productName: $(this).attr('data-product-name'),
                currentStock: $(this).attr('data-current-stock')
            });
        });
    }, 1000);
});
</script>

<?php include_once "templates/footer.php"; ?>