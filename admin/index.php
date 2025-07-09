<?php
/**
 * admin/index.php - 管理者ダッシュボード
 * 
 * 管理者が利用するダッシュボードページです。
 * 売上、注文、ユーザー数などの統計情報と最近の注文を表示します。
 * 
 * 主な機能:
 * - 販売統計の概要表示
 * - 最近の注文一覧表示
 * - 管理メニューへのアクセス
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "../config/database.php";
include_once "../classes/User.php";
include_once "../classes/Product.php";
include_once "../classes/Order.php";

// 管理者権限チェック - 一般ユーザーのアクセス防止
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続の初期化
$database = new Database();
$db = $database->getConnection();

// 各種クラスのインスタンス化
$product = new Product($db);
$order = new Order($db);
$user = new User($db);

// 各種統計データ取得
$total_products = $product->count();                // 総商品数
$total_orders = $order->count();                    // 総注文数
$total_users = $user->count();                      // 総ユーザー数
$recent_orders = $order->getRecent();              // 最近の注文

// ヘッダーのインクルード
include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <!-- 左側のサイドバー -->
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- メインコンテンツエリア -->
        <div class="col-md-10">
            <h2 class="mt-4">ダッシュボード</h2>
            
            <!-- 統計カード -->
            <div class="row mt-4">
                <!-- 総商品数 -->
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">総商品数</h5>
                            <h2><?php echo $total_products; ?></h2>
                        </div>
                    </div>
                </div>
                
                <!-- 総注文数 -->
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">総注文数</h5>
                            <h2><?php echo $total_orders; ?></h2>
                        </div>
                    </div>
                </div>
                
                <!-- 総会員数 -->
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">総会員数</h5>
                            <h2><?php echo $total_users; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 最近の注文一覧 -->
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
                            <?php
                            // 最近の注文を表示
                            while($row = $recent_orders->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td>¥<?php echo number_format($row['total_amount']); ?></td>
                                <td>
                                    <?php
                                    // 注文ステータスに応じてバッジを表示
                                    switch($row['status']) {
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
                                            echo '<span class="badge badge-success">配達済</span>';
                                            break;
                                        case 'cancelled':
                                            echo '<span class="badge badge-danger">キャンセル済</span>';
                                            break;
                                    }
                                    ?>
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