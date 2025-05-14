<?php
/**
 * 在庫管理ページ（管理者用）
 * 
 * 商品・バリエーションの在庫確認と更新を行います
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "../config/database.php";
include_once "../classes/Product.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);

// 在庫更新処理
if(isset($_POST['update_stock'])) {
    $product_id = intval($_POST['product_id']);
    $variation_id = !empty($_POST['variation_id']) ? intval($_POST['variation_id']) : null;
    $quantity_change = intval($_POST['quantity_change']);
    $reason = trim($_POST['reason']);
    
    // 入力値検証
    if(empty($reason)) {
        $error_message = "理由を入力してください。";
    } elseif($quantity_change == 0) {
        $error_message = "在庫変更数を入力してください。";
    } else {
        // 現在の在庫を確認
        $current_stock = $product->checkStock($product_id, $variation_id)['stock'];
        $new_stock = $current_stock + $quantity_change;
        
        if($new_stock < 0) {
            $error_message = "在庫が不足します。現在の在庫: " . $current_stock . "個";
        } else {
            // 在庫更新を実行
            try {
                if($product->updateStock($product_id, $variation_id, $quantity_change, $reason)) {
                    $success_message = "在庫を更新しました。新しい在庫: " . $new_stock . "個";
                    header("Location: stock_management.php");
                    exit();
                } else {
                    $error_message = "在庫の更新に失敗しました。";
                }
            } catch(Exception $e) {
                $error_message = "在庫の更新中にエラーが発生しました: " . $e->getMessage();
            }
        }
    }
}

include_once "templates/header.php";
?>

<style>
/* 在庫管理用スタイル */
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
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <h2 class="mt-4">在庫管理</h2>
            
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 在庫一覧 -->
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
                                    $main_stock = $row['stock'];
                                    
                                    // 受注生産品かチェック
                                    $preorder_info = $product->getPreorderInfo($product_id);
                                    if(!$preorder_info['is_preorder']) {
                                        // 基本商品の在庫表示
                                        $stock_status = $product->getStockStatus($main_stock);
                                        $badge_class = $stock_status == 'out_of_stock' ? 'danger' : ($stock_status == 'low_stock' ? 'warning' : 'success');
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product_name); ?></td>
                                            <td>-</td>
                                            <td><?php echo $main_stock; ?>個</td>
                                            <td><span class="badge badge-<?php echo $badge_class; ?>"><?php echo ucfirst($stock_status); ?></span></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary stock-adjust-btn" 
                                                        data-toggle="modal" 
                                                        data-target="#stockModal" 
                                                        data-product-id="<?php echo $product_id; ?>"
                                                        data-variation-id=""
                                                        data-product-name="<?php echo htmlspecialchars($product_name); ?>"
                                                        data-variation-name="-"
                                                        data-current-stock="<?php echo $main_stock; ?>">
                                                    在庫調整
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    
                                    // バリエーションの在庫表示
                                    $variations = $product->getProductVariations($product_id);
                                    while($var = $variations->fetch(PDO::FETCH_ASSOC)) {
                                        if(!$preorder_info['is_preorder']) {
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
                                                    <button class="btn btn-sm btn-primary stock-adjust-btn" 
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
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 在庫ログ -->
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
                                        <td><?php echo htmlspecialchars($log['product_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo $log['variation_name'] ? htmlspecialchars($log['variation_name'] . ': ' . $log['variation_value']) : '-'; ?></td>
                                        <td>
                                            <?php
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
                                        <td><?php echo htmlspecialchars($log['reason'] ?? ''); ?></td>
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
                    <input type="hidden" name="product_id" id="modal-product-id">
                    <input type="hidden" name="variation_id" id="modal-variation-id">
                    
                    <div class="form-group">
                        <label>商品名</label>
                        <p id="modal-product-name" class="form-control-plaintext font-weight-bold"></p>
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
                        <input type="text" class="form-control" id="reason" name="reason" required 
                               placeholder="例：入荷、返品、破損、在庫調整など">
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

<script>
$(document).ready(function() {
    // デバッグ用：モーダル表示前のデータ確認
    $('.stock-adjust-btn').on('click', function() {
        console.log('Button clicked');
        console.log('Product ID:', $(this).data('product-id'));
        console.log('Variation ID:', $(this).data('variation-id'));
        console.log('Product Name:', $(this).data('product-name'));
        console.log('Variation Name:', $(this).data('variation-name'));
        console.log('Current Stock:', $(this).data('current-stock'));
    });
    
    // 在庫調整モーダルのイベント処理
    $('#stockModal').on('show.bs.modal', function (event) {
        console.log('Modal show event triggered');
        
        var button = $(event.relatedTarget);
        var modal = $(this);
        
        // データ属性から値を取得
        var productId = button.data('product-id');
        var variationId = button.data('variation-id') || '';
        var productName = button.data('product-name') || '商品名が取得できません';
        var variationName = button.data('variation-name') || '-';
        var currentStock = parseInt(button.data('current-stock')) || 0;
        
        console.log('Retrieved data:', {
            productId: productId,
            variationId: variationId,
            productName: productName,
            variationName: variationName,
            currentStock: currentStock
        });
        
        // モーダルのフィールドに値を設定
        modal.find('#modal-product-id').val(productId);
        modal.find('#modal-variation-id').val(variationId);
        modal.find('#modal-product-name').text(productName);
        modal.find('#modal-variation-name').text(variationName);
        modal.find('#current_stock').val(currentStock);
        modal.find('#new_stock').val(currentStock);
        
        // フィールドをリセット
        modal.find('#quantity_change').val('').removeClass('is-invalid');
        modal.find('#reason').val('');
        modal.find('#new_stock').removeClass('is-invalid');
        
        // 在庫変更数入力時の計算
        $('#quantity_change').off('input.stock').on('input.stock', function() {
            var change = parseInt($(this).val()) || 0;
            var newStock = currentStock + change;
            $('#new_stock').val(newStock);
            
            // バリデーション表示
            if(newStock < 0) {
                $('#new_stock').addClass('is-invalid');
                $(this).addClass('is-invalid');
            } else {
                $('#new_stock').removeClass('is-invalid');
                $(this).removeClass('is-invalid');
            }
        });
        
        // デバッグ用：設定後の確認
        setTimeout(function() {
            console.log('Modal fields after setting:');
            console.log('Product Name:', modal.find('#modal-product-name').text());
            console.log('Variation Name:', modal.find('#modal-variation-name').text());
            console.log('Current Stock:', modal.find('#current_stock').val());
        }, 100);
    });

    // モーダルクローズ時のクリーンアップ
    $('#stockModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
    });

    // フォーム送信時のバリデーション
    $('#stockModal form').on('submit', function(e) {
        var newStock = parseInt($('#new_stock').val());
        var change = parseInt($('#quantity_change').val());
        var reason = $('#reason').val().trim();
        
        if(!reason) {
            e.preventDefault();
            alert('理由を入力してください。');
            $('#reason').focus();
            return false;
        }
        
        if(isNaN(change) || change === 0) {
            e.preventDefault();
            alert('在庫変更数を入力してください。');
            $('#quantity_change').focus();
            return false;
        }
        
        if(newStock < 0) {
            e.preventDefault();
            alert('在庫が負の値になります。在庫変更数を確認してください。');
            $('#quantity_change').focus();
            return false;
        }
        
        var message = '在庫を ' + change + ' 変更しますか？\n\n';
        message += '商品: ' + $('#modal-product-name').text() + '\n';
        message += 'バリエーション: ' + $('#modal-variation-name').text() + '\n';
        message += '現在在庫: ' + $('#current_stock').val() + '個\n';
        message += '変更後在庫: ' + newStock + '個\n';
        message += '理由: ' + reason;
        
        if(!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include_once "templates/footer.php"; ?>