<?php
/**
 * order_complete.php - 注文完了ページ
 * 
 * 注文処理が完了した後に表示されるページです。
 * 注文の概要と確認情報を表示します。
 * 
 * 機能:
 * - 注文完了メッセージの表示
 * - 注文詳細情報の表示（注文番号、注文日、金額、支払方法）
 * - 「ホームに戻る」「注文詳細を見る」などのナビゲーションリンク
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";  // データベース接続情報
include_once "classes/Order.php";    // 注文クラス

// 注文IDがない場合はホームにリダイレクト（直接アクセス防止）
if(!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

// 注文IDを取得
$order_id = $_GET['id'];

// データベース接続の取得
$database = new Database();
$db = $database->getConnection();

// 注文オブジェクトの作成と注文情報の取得
$order = new Order($db);
$order->read($order_id);

// 注文が存在しない場合はホームにリダイレクト
if(!$order->id) {
    header('Location: index.php');
    exit();
}

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <div class="jumbotron text-center">
        <!-- 注文完了メッセージ -->
        <h1 class="display-4 text-success"><i class="fas fa-check-circle"></i> ご注文ありがとうございます！</h1>
        <p class="lead">ご注文を受け付けました。ご注文番号は <strong>#<?php echo $order_id; ?></strong> です。</p>
        <hr class="my-4">
        <p>ご注文の確認メールをお送りしました。お支払いと発送についての詳細はメールをご確認ください。</p>
        
        <!-- 注文概要 -->
        <div class="order-details mt-5">
            <h3>注文概要</h3>
            
            <div class="row mt-4">
                <div class="col-md-6 offset-md-3">
                    <table class="table">
                        <tr>
                            <th>注文番号:</th>
                            <td>#<?php echo $order_id; ?></td>
                        </tr>
                        <tr>
                            <th>注文日:</th>
                            <td><?php echo date('Y年n月j日', strtotime($order->created)); ?></td>
                        </tr>
                        <tr>
                            <th>合計金額:</th>
                            <td>¥<?php echo number_format($order->total_amount); ?></td>
                        </tr>
                        <tr>
                            <th>支払方法:</th>
                            <td>
                                <?php
                                // 支払方法の表示
                                switch($order->payment_method) {
                                    case 'credit_card':
                                        echo 'クレジットカード';
                                        break;
                                    case 'bank_transfer':
                                        echo '銀行振込';
                                        break;
                                    case 'cod':
                                        echo '代金引換';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- ナビゲーションボタン -->
        <p class="mt-5">
            <a href="index.php" class="btn btn-primary btn-lg">ホームに戻る</a>
            <a href="order_detail.php?id=<?php echo $order_id; ?>" class="btn btn-info btn-lg">注文詳細を見る</a>
        </p>
    </div>
</div>

<?php 
// フッターテンプレートのインクルード
include_once "templates/footer.php"; 
?>