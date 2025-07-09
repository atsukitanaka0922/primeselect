<?php
/**
 * orders.php - 注文履歴・管理ページ
 * 
 * ユーザーの注文履歴を表示するページです。
 * 注文一覧の表示とそれぞれの注文の詳細確認を行うことができます。
 * 
 * 主な機能:
 * - ユーザーの注文履歴一覧表示
 * - 注文ステータスの表示（保留中、処理中、発送済み、配達完了、キャンセル）
 * - 注文詳細へのリンク
 * - ユーザープロフィールメニューの表示
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Order.php";

// ログインチェック - 未ログインの場合はログインページへリダイレクト
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'orders.php'; // ログイン後のリダイレクト先を設定
    header('Location: login.php');
    exit();
}

// データベース接続
$database = new Database();
$db = $database->getConnection();

// Order クラスのインスタンス化
$order = new Order($db);
$user_id = $_SESSION['user_id']; // ログインユーザーのID

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>注文履歴</h2>
    
    <div class="row">
        <!-- サイドバー - ユーザープロフィールメニュー -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action">プロフィール</a>
                <a href="orders.php" class="list-group-item list-group-item-action active">注文履歴</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">お気に入り</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">ログアウト</a>
            </div>
        </div>
        
        <!-- メインコンテンツ - 注文一覧 -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">注文一覧</div>
                <div class="card-body">
                    <?php
                    // ユーザーの注文履歴を取得
                    $stmt = $order->getUserOrders($user_id);
                    
                    // 注文が存在する場合は一覧表示
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
                                    // 注文データのループ処理
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        // 必要なデータを取り出し（extract関数を使用せず明示的に変数に代入）
                                        $id = $row['id'];
                                        $created = $row['created'];
                                        $total_amount = $row['total_amount'];
                                        $status = $row['status'];
                                        ?>
                                        <tr>
                                            <td><?php echo $id; ?></td>
                                            <td><?php echo date('Y年n月j日', strtotime($created)); ?></td>
                                            <td>¥<?php echo number_format($total_amount); ?></td>
                                            <td>
                                                <?php
                                                // 注文ステータスに応じたバッジを表示
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
                                                <!-- 注文詳細ページへのリンク -->
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
                        // 注文がない場合のメッセージ
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