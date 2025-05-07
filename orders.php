<?php
session_start();
include_once "config/database.php";
include_once "classes/Order.php";

// 未ログインならログインページへ
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'orders.php';
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);
$user_id = $_SESSION['user_id'];

include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>注文履歴</h2>
    
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action">プロフィール</a>
                <a href="orders.php" class="list-group-item list-group-item-action active">注文履歴</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">お気に入り</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">ログアウト</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">注文一覧</div>
                <div class="card-body">
                    <?php
                    $stmt = $order->getUserOrders($user_id);
                    
                    if($stmt->rowCount() > 0) {
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>注文番号</th>
                                        <th>注文日</th>
                                        <th>金額</th>
                                        <th>状態</th>
                                        <th>詳細</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        extract($row);
                                        ?>
                                        <tr>
                                            <td><?php echo $id; ?></td>
                                            <td><?php echo date('Y年n月j日', strtotime($created)); ?></td>
                                            <td>¥<?php echo number_format($total_amount); ?></td>
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
                                                        echo '<span class="badge badge-primary">発送済み</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success">配達済み</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-danger">キャンセル済</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="order_detail.php?id=<?php echo $id; ?>" class="btn btn-sm btn-info">詳細</a>
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
                            まだ注文履歴がありません。<a href="shop.php">ショップ</a>で商品を購入してください。
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>