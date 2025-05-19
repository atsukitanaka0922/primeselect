<?php
/**
 * index.php - Prime Select ECサイトのメインページ
 * 
 * メインページでは特集商品、おすすめ商品、プロモーションバナーなどを表示します。
 * サイトのエントリーポイントとなる重要なページです。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始（すべてのページで必要）
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";      // データベース接続クラス
include_once "classes/Product.php";      // 商品クラス
include_once "classes/Category.php";     // カテゴリクラス

// データベース接続を作成
$database = new Database();
$db = $database->getConnection();

// 商品オブジェクトの作成と商品データの取得
$product = new Product($db);
$stmt = $product->read();  // すべての商品を取得

// ヘッダーテンプレートのインクルード（ナビゲーションバーなど）
include_once "templates/header.php";
?>

<div class="container mt-4">
    <!-- プロモーションバナー - サイトのメイン広告エリア -->
    <div class="jumbotron jumbotron-fluid promo-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="display-4">夏の大セール開催中!</h2>
                    <p class="lead">全商品が最大30%オフ！期間限定のスペシャル価格をお見逃しなく。</p>
                    <a href="shop.php" class="btn btn-primary btn-lg">今すぐチェック</a>
                </div>
                <div class="col-md-6 text-right">
                    <img src="assets/images/sale_banner.jpg" alt="Summer Sale" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- 特集商品バナー - ユーザーを誘導するためのカテゴリ別バナー -->
    <div class="row mb-4">
        <!-- 新着商品 -->
        <div class="col-md-4 mb-3">
            <div class="card featured-product-card">
                <div class="card-body text-center">
                    <img src="assets/images/featured_item1.jpg" alt="新着商品" class="img-fluid mb-3">
                    <h5>新着商品</h5>
                    <p>最新のアイテムをチェック</p>
                    <a href="shop.php?sort_by=created&sort_order=DESC" class="btn btn-outline-primary">詳細を見る</a>
                </div>
            </div>
        </div>
        <!-- 人気商品 -->
        <div class="col-md-4 mb-3">
            <div class="card featured-product-card">
                <div class="card-body text-center">
                    <img src="assets/images/featured_item2.jpg" alt="人気商品" class="img-fluid mb-3">
                    <h5>人気商品</h5>
                    <p>みんなが選んだベストセラー</p>
                    <a href="shop.php?sort_by=rating&sort_order=DESC" class="btn btn-outline-primary">詳細を見る</a>
                </div>
            </div>
        </div>
        <!-- 限定商品 -->
        <div class="col-md-4 mb-3">
            <div class="card featured-product-card">
                <div class="card-body text-center">
                    <img src="assets/images/featured_item3.jpg" alt="限定商品" class="img-fluid mb-3">
                    <h5>限定商品</h5>
                    <p>数量限定の特別アイテム</p>
                    <a href="shop.php?category_id=3" class="btn btn-outline-primary">詳細を見る</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- サイドバー - カテゴリリストと人気商品表示 -->
        <div class="col-md-3">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- 商品一覧 - おすすめ商品を表示 -->
        <div class="col-md-9">
            <h3 class="mb-4">おすすめ商品</h3>
            <div class="row">
                <?php
                // 商品データをループして表示
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // extract()を安全に使用するために配列のキーを検証すべき
                    // ここでは、extract()を使わずに直接配列アクセスする方が安全
                    $id = $row['id'];
                    $name = $row['name'];
                    $description = $row['description'];
                    $price = $row['price'];
                    $image = $row['image'];
                    
                    // メイン画像を取得（利用可能な場合）
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
// フッターテンプレートのインクルード（共通フッター情報とJavaScript）
include_once "templates/footer.php"; 
?>