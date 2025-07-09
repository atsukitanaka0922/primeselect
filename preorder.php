<?php
/**
 * preorder.php - 予約注文一覧ページ
 * 
 * ユーザーの予約注文（受注生産商品）の履歴を表示するためのページです。
 * 商品の製作状況や配送予定日が確認できます。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();
include_once "config/database.php";
include_once "classes/Preorder.php";

// 未ログインの場合はログインページにリダイレクト
if(!isset($_SESSION['user_id'])) {
    // リダイレクト先を保存してからログインページへ
    $_SESSION['redirect_to'] = 'preorders.php';
    header('Location: login.php');
    exit();
}

// データベース接続
$database = new Database();
$db = $database->getConnection();

// 予約注文オブジェクトを初期化
$preorder = new Preorder($db);
$user_id = $_SESSION['user_id'];

// ヘッダーテンプレートを読み込み
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>予約注文履歴</h2>
    
    <div class="row">
        <!-- サイドバーナビゲーション -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action">プロフィール</a>
                <a href="orders.php" class="list-group-item list-group-item-action">注文履歴</a>
                <a href="preorders.php" class="list-group-item list-group-item-action active">予約注文履歴</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">お気に入り</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">ログアウト</a>
            </div>
        </div>
        
        <!-- メインコンテンツ -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">予約注文一覧</div>
                <div class="card-body">
                    <?php
                    // ユーザーの予約注文を取得
                    $stmt = $preorder->getUserPreorders($user_id);
                    
                    // 予約注文が存在する場合
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
                                    // 各予約注文を表示
                                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        // 配列の要素を変数として展開
                                        extract($row);
                                        ?>
                                        <tr>
                                            <td>
                                                <!-- 商品画像と名前を表示 -->
                                                <div class="d-flex align-items-center">
                                                    <img src="assets/images/<?php echo htmlspecialchars($image); ?>" width="50" alt="<?php echo htmlspecialchars($product_name); ?>">
                                                    <div class="ml-2">
                                                        <span><?php echo htmlspecialchars($product_name); ?></span>
                                                        <?php if(isset($variation_name) && isset($variation_value)): ?>
                                                        <!-- バリエーション情報を表示 -->
                                                        <div><small class="text-muted"><?php echo htmlspecialchars($variation_name); ?>: <?php echo htmlspecialchars($variation_value); ?></small></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo intval($quantity); ?>個</td>
                                            <td><?php echo date('Y年n月j日', strtotime($created)); ?></td>
                                            <td><?php echo isset($estimated_delivery) ? date('Y年n月j日', strtotime($estimated_delivery)) : '未定'; ?></td>
                                            <td>
                                                <?php
                                                // ステータスに応じたバッジを表示
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
                                                        echo '<span class="badge badge-secondary">発送済み</span>';
                                                        break;
                                                    case 'delivered':
                                                        echo '<span class="badge badge-success">配達完了</span>';
                                                        break;
                                                    case 'cancelled':
                                                        echo '<span class="badge badge-danger">キャンセル済</span>';
                                                        break;
                                                    default:
                                                        echo '<span class="badge badge-light">不明</span>';
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
                        // 予約注文がない場合のメッセージ
                        ?>
                        <div class="alert alert-info">
                            予約注文履歴がありません。<a href="shop.php">ショップ</a>で受注生産商品を探してみてください。
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            
            <!-- 受注生産についての説明セクション -->
            <div class="card mt-4">
                <div class="card-header">受注生産商品について</div>
                <div class="card-body">
                    <p>受注生産商品とは、ご注文をいただいてから製作を開始する商品です。以下の点にご注意ください：</p>
                    <ul>
                        <li>配送までに通常の商品よりもお時間をいただきます（商品ごとに製作期間が異なります）</li>
                        <li>オーダーメイドの特性上、キャンセルや返品が制限される場合があります</li>
                        <li>製作状況は随時更新され、進捗状況をこのページでご確認いただけます</li>
                        <li>配送予定日は状況により前後する場合があります</li>
                    </ul>
                    <p>ご不明な点がございましたら、<a href="contact.php">お問い合わせフォーム</a>よりお気軽にご連絡ください。</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// フッターテンプレートを読み込み
include_once "templates/footer.php"; 
?>

<?php
/**
 * 改善提案:
 * 
 * 1. 予約注文の詳細ページの追加（製作進捗のより詳細な情報）
 * 2. 予約商品のキャンセル機能（一定の期間内であればキャンセル可能に）
 * 3. 予約状況の通知機能（メールやプッシュ通知）
 * 4. カスタマイズオプションの追加入力フォーム
 * 5. 進捗状況のビジュアル表示（進行バーなど）
 * 6. 期限超過時のアラート表示
 * 7. 予約注文の特典や割引情報の表示
 */