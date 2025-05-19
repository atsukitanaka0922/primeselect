<?php
/**
 * cart.php - ショッピングカートページ
 * 
 * カートの内容を表示し、商品の追加、削除、数量変更を行うページです。
 * 在庫チェック機能と受注生産商品対応の機能を備えています。
 * 
 * 主な機能:
 * - カート内商品の表示
 * - 商品の追加・削除・数量変更
 * - 在庫状況のリアルタイムチェック
 * - 受注生産商品の特別処理
 * - 合計金額の計算
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Cart.php";
include_once "classes/Product.php";

// データベース接続の初期化
$database = new Database();
$db = $database->getConnection();

// ユーザーIDの設定（ログイン済みか未ログインかで分岐）
if(!isset($_SESSION['user_id'])) {
    // 未ログインの場合は一時IDを生成
    if(!isset($_SESSION['temp_user_id'])) {
        $_SESSION['temp_user_id'] = uniqid();
    }
    $user_id = $_SESSION['temp_user_id'];
} else {
    // ログイン済みの場合はユーザーIDを使用
    $user_id = $_SESSION['user_id'];
}

// クラスのインスタンス化
$cart = new Cart($db);
$product = new Product($db);

// カートへのアクション処理
if(isset($_GET['action'])) {
    // 商品追加アクション
    if($_GET['action'] == 'add' && isset($_GET['id'])) {
        $product_id = $_GET['id'];
        $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
        $variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : null;
        $is_preorder = isset($_GET['is_preorder']) ? intval($_GET['is_preorder']) : 0;
        
        // 受注生産商品でない場合のみ在庫チェック
        if(!$is_preorder) {
            // 在庫チェック
            $stock_info = $product->checkStock($product_id, $variation_id);
            
            if(!$stock_info['is_available']) {
                $_SESSION['error_message'] = "在庫が不足しています。";
            } elseif($stock_info['stock'] < $quantity) {
                $_SESSION['error_message'] = "在庫が不足しています。在庫数: " . $stock_info['stock'] . "個";
            } else {
                if($cart->addItem($user_id, $product_id, $quantity, $variation_id)) {
                    $_SESSION['success_message'] = "商品をカートに追加しました。";
                } else {
                    $_SESSION['error_message'] = "商品の追加に失敗しました。";
                }
            }
        } else {
            // 受注生産商品の場合は在庫チェックなしで追加
            if($cart->addItem($user_id, $product_id, $quantity, $variation_id)) {
                $_SESSION['success_message'] = "予約商品をカートに追加しました。";
            } else {
                $_SESSION['error_message'] = "商品の追加に失敗しました。";
            }
        }
        
        header('Location: cart.php');
        exit();
    }
    
    // 商品削除アクション
    if($_GET['action'] == 'remove' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        if($cart->removeItem($id)) {
            $_SESSION['success_message'] = "商品をカートから削除しました。";
        } else {
            $_SESSION['error_message'] = "商品の削除に失敗しました。";
        }
        
        header('Location: cart.php');
        exit();
    }
    
    // 数量更新アクション
    if($_GET['action'] == 'update' && isset($_POST['id']) && isset($_POST['quantity'])) {
        $id = $_POST['id'];
        $quantity = intval($_POST['quantity']);
        
        // カートアイテムの詳細を取得
        $cart_items = $cart->getItems($user_id);
        while($item = $cart_items->fetch(PDO::FETCH_ASSOC)) {
            if($item['id'] == $id) {
                // 受注生産品かどうかチェック
                $preorder_info = $product->getPreorderInfo($item['product_id']);
                
                if(!$preorder_info['is_preorder']) {
                    // 在庫チェック
                    $stock_info = $product->checkStock($item['product_id'], $item['variation_id']);
                    
                    if(!$stock_info['is_available']) {
                        $_SESSION['error_message'] = "在庫が不足しています。";
                    } elseif($stock_info['stock'] < $quantity) {
                        $_SESSION['error_message'] = "在庫が不足しています。在庫数: " . $stock_info['stock'] . "個";
                    } else {
                        if($quantity > 0 && $cart->updateQuantity($id, $quantity)) {
                            $_SESSION['success_message'] = "数量を更新しました。";
                        } else {
                            $_SESSION['error_message'] = "数量の更新に失敗しました。";
                        }
                    }
                } else {
                    // 受注生産商品の場合は在庫チェックなしで更新
                    if($quantity > 0 && $cart->updateQuantity($id, $quantity)) {
                        $_SESSION['success_message'] = "数量を更新しました。";
                    } else {
                        $_SESSION['error_message'] = "数量の更新に失敗しました。";
                    }
                }
                break;
            }
        }
        
        header('Location: cart.php');
        exit();
    }
}

// ヘッダーのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>ショッピングカート</h2>
    
    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>商品</th>
                            <th>価格</th>
                            <th>数量</th>
                            <th>在庫状況</th>
                            <th>小計</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // カート内商品を取得
                        $stmt = $cart->getItems($user_id);
                        $total = 0;
                        $has_out_of_stock = false;
                        
                        // カート内の各商品を処理
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // extract関数でデータを変数に展開（危険な可能性があるのでサニタイズが必要）
                            extract($row);
                            
                            // バリエーションがある場合、価格を調整
                            $item_price = $price;
                            if(isset($price_adjustment)) {
                                $item_price += $price_adjustment;
                            }
                            
                            // 小計を計算
                            $subtotal = $item_price * $quantity;
                            
                            // 受注生産商品かどうかチェック
                            $preorder_info = $product->getPreorderInfo($product_id);
                            $is_preorder = $preorder_info['is_preorder'];
                            
                            // 在庫チェック（受注生産商品以外）
                            if(!$is_preorder) {
                                $stock_info = $product->checkStock($product_id, $variation_id);
                                $is_available = $stock_info['is_available'];
                                $current_stock = $stock_info['stock'];
                                
                                if(!$is_available || $current_stock < $quantity) {
                                    $has_out_of_stock = true;
                                } else {
                                    $total += $subtotal;
                                }
                            } else {
                                // 受注生産商品は常に合計に含める
                                $total += $subtotal;
                                $is_available = true;
                                $current_stock = 999; // 仮の大きな値
                            }
                            ?>
                            <tr class="<?php echo (!$is_preorder && (!$is_available || $current_stock < $quantity)) ? 'table-warning' : ''; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/images/<?php echo $image; ?>" width="50" alt="<?php echo $name; ?>">
                                        <div class="ml-2">
                                            <span><?php echo $name; ?></span>
                                            <?php if(isset($variation_name) && isset($variation_value)): ?>
                                            <div><small class="text-muted"><?php echo $variation_name; ?>: <?php echo $variation_value; ?></small></div>
                                            <?php endif; ?>
                                            <?php if($is_preorder): ?>
                                            <div><small class="text-info"><i class="fas fa-calendar-alt"></i> 予約商品</small></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>¥<?php echo number_format($item_price); ?></td>
                                <td>
                                    <form method="post" action="cart.php?action=update" class="form-inline">
                                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                                        <?php if($is_preorder): ?>
                                            <input type="number" name="quantity" value="<?php echo $quantity; ?>" 
                                                   min="1" max="10" class="form-control" style="width: 60px;">
                                        <?php else: ?>
                                            <input type="number" name="quantity" value="<?php echo $quantity; ?>" 
                                                   min="1" max="<?php echo $current_stock; ?>" class="form-control" style="width: 60px;"
                                                   <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary ml-2" 
                                                <?php echo (!$is_preorder && !$is_available) ? 'disabled' : ''; ?>>更新</button>
                                    </form>
                                </td>
                                <td>
                                    <?php if($is_preorder): ?>
                                        <span class="badge badge-info">受注生産</span>
                                    <?php elseif(!$is_available): ?>
                                        <span class="badge badge-danger">在庫切れ</span>
                                    <?php elseif($current_stock < $quantity): ?>
                                        <span class="badge badge-warning">在庫不足 (在庫: <?php echo $current_stock; ?>個)</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">在庫あり</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($is_preorder || ($is_available && $current_stock >= $quantity)): ?>
                                        ¥<?php echo number_format($subtotal); ?>
                                    <?php else: ?>
                                        <span class="text-muted">計算対象外</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> 削除
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        
                        // カートが空の場合
                        if($stmt->rowCount() == 0) {
                            echo '<tr><td colspan="6" class="text-center">カートに商品がありません</td></tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>合計</strong></td>
                            <td><strong>¥<?php echo number_format($total); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <?php if($has_out_of_stock): ?>
            <div class="alert alert-warning">
                <strong>注意:</strong> 在庫切れまたは在庫不足の商品があります。これらの商品は合計に含まれません。
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <a href="shop.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 買い物を続ける
            </a>
        </div>
        <div class="col-md-6 text-right">
            <?php if($stmt->rowCount() > 0 && !$has_out_of_stock): ?>
            <a href="checkout.php" class="btn btn-primary">
                レジに進む <i class="fas fa-arrow-right"></i>
            </a>
            <?php elseif($has_out_of_stock && $total > 0): ?>
            <a href="checkout.php" class="btn btn-primary">
                在庫ある商品で注文する <i class="fas fa-arrow-right"></i>
            </a>
            <?php elseif($has_out_of_stock): ?>
            <div class="text-muted">
                <small>在庫不足商品を解決してからレジに進んでください</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>