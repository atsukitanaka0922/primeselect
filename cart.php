<?php
/**
 * カートページ
 * 
 * ショッピングカートの内容を表示し、商品の追加、削除、数量変更を行います。
 * 
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Cart.php";
include_once "classes/Product.php";

// データベース接続
$database = new Database();
$db = $database->getConnection();

// ユーザーIDがない場合は仮のIDを生成
if(!isset($_SESSION['user_id'])) {
    if(!isset($_SESSION['temp_user_id'])) {
        $_SESSION['temp_user_id'] = uniqid();
    }
    $user_id = $_SESSION['temp_user_id'];
} else {
    $user_id = $_SESSION['user_id'];
}

$cart = new Cart($db);
$product = new Product($db);

// カートへのアクション処理
if(isset($_GET['action'])) {
    if($_GET['action'] == 'add' && isset($_GET['id'])) {
        $product_id = $_GET['id'];
        $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
        $variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : null;
        
        if($cart->addItem($user_id, $product_id, $quantity, $variation_id)) {
            $_SESSION['success_message'] = "商品をカートに追加しました。";
        } else {
            $_SESSION['error_message'] = "商品の追加に失敗しました。";
        }
        
        header('Location: cart.php');
        exit();
    }
    
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
    
    if($_GET['action'] == 'update' && isset($_POST['id']) && isset($_POST['quantity'])) {
        $id = $_POST['id'];
        $quantity = intval($_POST['quantity']);
        
        if($quantity > 0 && $cart->updateQuantity($id, $quantity)) {
            $_SESSION['success_message'] = "数量を更新しました。";
        } else {
            $_SESSION['error_message'] = "数量の更新に失敗しました。";
        }
        
        header('Location: cart.php');
        exit();
    }
}

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
                            <th>小計</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $cart->getItems($user_id);
                        $total = 0;
                        
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            extract($row);
                            
                            // バリエーションがある場合、価格を調整
                            $item_price = $price;
                            if(isset($price_adjustment)) {
                                $item_price += $price_adjustment;
                            }
                            
                            $subtotal = $item_price * $quantity;
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="assets/images/<?php echo $image; ?>" width="50" alt="<?php echo $name; ?>">
                                        <div class="ml-2">
                                            <span><?php echo $name; ?></span>
                                            <?php if(isset($variation_name) && isset($variation_value)): ?>
                                            <div><small class="text-muted"><?php echo $variation_name; ?>: <?php echo $variation_value; ?></small></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>¥<?php echo number_format($item_price); ?></td>
                                <td>
                                    <form method="post" action="cart.php?action=update" class="form-inline">
                                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                                        <input type="number" name="quantity" value="<?php echo $quantity; ?>" min="1" max="99" class="form-control" style="width: 60px;">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary ml-2">更新</button>
                                    </form>
                                </td>
                                <td>¥<?php echo number_format($subtotal); ?></td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> 削除
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                        
                        if($stmt->rowCount() == 0) {
                            echo '<tr><td colspan="5" class="text-center">カートに商品がありません</td></tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>合計</strong></td>
                            <td><strong>¥<?php echo number_format($total); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <a href="shop.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 買い物を続ける
            </a>
        </div>
        <div class="col-md-6 text-right">
            <?php if($stmt->rowCount() > 0): ?>
            <a href="checkout.php" class="btn btn-primary">
                レジに進む <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>