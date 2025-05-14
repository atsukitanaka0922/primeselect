<?php
session_start();
include_once "../config/database.php";
include_once "../classes/User.php";
include_once "../classes/Product.php";
include_once "../classes/Order.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$order = new Order($db);
$user = new User($db);

// 各種統計データ取得
$total_products = $product->count();
$total_orders = $order->count();
$total_users = $user->count();
$recent_orders = $order->getRecent();

include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <h2 class="mt-4">ダッシュボード</h2>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">総商品数</h5>
                            <h2><?php echo $total_products; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">総注文数</h5>
                            <h2><?php echo $total_orders; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">総会員数</h5>
                            <h2><?php echo $total_users; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    最近の注文
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>注文ID</th>
                                <th>ユーザー</th>
                                <th>金額</th>
                                <th>状態</th>
                                <th>日付</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent_orders->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>¥<?php echo number_format($row['total_amount']); ?></td>
                                <td>
                                    <?php if($row['status'] == 'pending'): ?>
                                    <span class="badge badge-warning">保留中</span>
                                    <?php elseif($row['status'] == 'processing'): ?>
                                    <span class="badge badge-info">処理中</span>
                                    <?php elseif($row['status'] == 'shipped'): ?>
                                    <span class="badge badge-primary">発送済</span>
                                    <?php elseif($row['status'] == 'delivered'): ?>
                                    <span class="badge badge-success">配達済</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['created'])); ?></td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">詳細</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>