<?php
/**
 * Prime Select - ECサイトのメインページ
 * 
 * メインページでは最新の商品一覧を表示します。
 * 
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Product.php";
include_once "classes/Category.php";

// データベース接続を作成
$database = new Database();
$db = $database->getConnection();

// 商品オブジェクトの作成と商品データの取得
$product = new Product($db);
$stmt = $product->read();

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <div class="row">
        <!-- サイドバー -->
        <div class="col-md-3">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- 商品一覧 -->
        <div class="col-md-9">
            <div class="row">
                <?php
                // 商品データをループして表示
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    // メイン画像を取得
                    $main_image = $product->getMainImage($id) ?? $image;
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img class="card-img-top" src="assets/images/<?php echo $main_image; ?>" alt="<?php echo $name; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $name; ?></h5>
                                <p class="card-text"><?php echo substr($description, 0, 100) . '...'; ?></p>
                                <h6 class="card-price">¥<?php echo number_format($price); ?></h6>
                                <a href="product.php?id=<?php echo $id; ?>" class="btn btn-primary">詳細を見る</a>
                                <a href="cart.php?action=add&id=<?php echo $id; ?>" class="btn btn-success">カートに追加</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php 
// フッターテンプレートのインクルード
include_once "templates/footer.php"; 
?>