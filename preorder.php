<?php
/**
 * 予約注文一覧ページ
 * 
 * ユーザーの予約注文履歴を表示します
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>予約注文履歴</h2>
    
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action">プロフィール</a>
                <a href="orders.php" class="list-group-item list-group-item-action">注文履歴</a>
                <a href="preorders.php" class="list-group-item list-group-item-action active">予約注文履歴</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">お気に入り</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">ログアウト</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">予約注文一覧</div>
                <div class="card-body">
                    <?php
                    $stmt = $preorder->getUserPreorders($user_id);
                    
                    if($stmt->rowCount() > 0) {
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>商品</th>
                                        <th>数量</th>
                                        <th>予約日</th>
                                        <th>配送予定日</th>
                                        <th>状態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        extract($row);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="assets/images/<?php echo $image; ?>" width="50" alt="<?php echo $product_name; ?>">
                                                    <div class="ml-2">
                                                        <span><?php echo $product_name; ?></span>
                                                        <?php if(isset($variation_name) && isset($variation_value)): ?>
                                                        <div><small class="text-muted"><?php echo $variation_name; ?>: <?php echo $variation_value; ?></small></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $quantity; ?>個</td>
                                            <td><?php echo date('Y年n月j日', strtotime($created)); ?></td>
                                            <td><?php echo date('Y年n月j日', strtotime($estimated_delivery)); ?></td>
                                            <td>
                                                <?php
                                                switch($status) {
                                                    case 'pending':
                                                        echo '<span class="badge badge-warning">予約受付</span>';
                                                        break;
                                                    case 'confirmed':
                                                        echo '<span class="badge badge-info">予約確定</span>';
                                                        break;
                                                    case 'production':
                                                        echo '<span class="badge badge-primary">生産中</span>';
                                                        break;
                                                    case 'shipped':
                                                        echo '<span class="badge badge-success">発送済み</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success">配達完了</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-danger">キャンセル済</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="alert alert-info">
                            予約注文履歴がありません。<a href="shop.php">ショップ</a>で受注生産商品を探してみてください。
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>once "config/database.php";
include_once "classes/Preorder.php";

// 未ログインならログインページへ
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'preorders.php';
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$preorder = new Preorder($db);
$user_id = $_SESSION['user_id'];

include_