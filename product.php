<?php
/**
 * product.php - 商品詳細ページ
 * 
 * 単一商品の詳細情報を表示するページです。
 * 通常商品と受注生産商品の両方に対応し、商品バリエーション、在庫状況、画像ギャラリー、
 * レビュー機能なども備えています。
 * 
 * 主な機能:
 * - 商品基本情報の表示
 * - 商品画像ギャラリー表示
 * - 商品バリエーション（サイズ、色など）の選択
 * - 在庫状況のリアルタイム表示
 * - 受注生産商品の情報表示
 * - レビュー表示と投稿
 * - 関連商品の表示
 * - お気に入り登録機能
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/Product.php";
include_once "classes/Review.php";
include_once "classes/Wishlist.php";

// データベース接続の初期化
$database = new Database();
$db = $database->getConnection();

// 商品IDの取得（URLパラメータから）
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// 商品オブジェクトの初期化
$product = new Product($db);
$product->id = $id;
$product->readOne(); // 商品情報を取得

// 商品画像の取得
$product_images = $product->getProductImages($id);

// 商品バリエーションの取得（グループ化）
$variations = $product->getGroupedVariations($id);

// 受注生産情報の取得
$preorder_info = $product->getPreorderInfo($id);

// 在庫情報の取得
$stock_info = $product->checkStock($id);

// レビューオブジェクトの初期化
$review = new Review($db);

// お気に入りオブジェクトの初期化
$wishlist = new Wishlist($db);
$in_wishlist = false;

// ログイン済みユーザーの場合、お気に入り状態をチェック
if(isset($_SESSION['user_id'])) {
    $wishlist->user_id = $_SESSION['user_id'];
    $wishlist->product_id = $id;
    $in_wishlist = $wishlist->isInWishlist();
}

// レビュー投稿処理
if(isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {
    $review->product_id = $id;
    $review->user_id = $_SESSION['user_id'];
    $review->rating = $_POST['rating'];
    $review->comment = $_POST['comment'];
    
    if($review->create()) {
        $success_message = "レビューが投稿されました。";
    } else {
        $error_message = "レビューの投稿に失敗しました。";
    }
}

// 平均評価と件数取得
$average_rating = $review->getAverageRating($id);
$review_count = $review->getReviewCount($id);

// ヘッダーのインクルード
include_once "templates/header.php";
?>

<style>
/* 商品ページ用のスタイル */
.product-badge-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

.badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.stock-status .badge {
    font-size: 0.9rem;
}
</style>

<div class="container mt-5">
    <!-- パンくずリスト -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">ホーム</a></li>
            <li class="breadcrumb-item"><a href="shop.php">ショップ</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $product->name; ?></li>
        </ol>
    </nav>
    
    <!-- 成功・エラーメッセージ表示 -->
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- 商品画像エリア -->
        <div class="col-md-6">
            <!-- メイン画像表示 -->
            <div class="product-main-image mb-3 position-relative">
                <img id="mainImage" src="assets/images/<?php echo $product->image; ?>" class="img-fluid" alt="<?php echo $product->name; ?>">
                
                <!-- 受注生産バッジ -->
                <?php if($preorder_info['is_preorder']): ?>
                <div class="product-badge-overlay">
                    <span class="badge badge-warning badge-lg">受注生産</span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- サムネイル画像表示（複数画像がある場合） -->
            <?php if($product_images->rowCount() > 1): ?>
            <div class="product-thumbnails d-flex">
                <?php
                while($image = $product_images->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="thumbnail-item mr-2">
                    <img src="assets/images/<?php echo $image['image_file']; ?>" class="img-thumbnail" 
                         alt="<?php echo $product->name; ?>" width="80"
                         onclick="changeMainImage('<?php echo $image['image_file']; ?>', '<?php echo $product->name; ?>')">
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 商品情報エリア -->
        <div class="col-md-6">
            <h2><?php echo $product->name; ?></h2>
            
            <!-- 在庫状況表示 -->
            <div class="stock-status mb-3">
                <?php
                if(!$preorder_info['is_preorder']) {
                    switch($stock_info['status']) {
                        case 'in_stock':
                            echo '<span class="badge badge-success">在庫あり</span>';
                            break;
                        case 'low_stock':
                            echo '<span class="badge badge-warning">残り少ない</span>';
                            break;
                        case 'out_of_stock':
                            echo '<span class="badge badge-danger">在庫切れ</span>';
                            break;
                    }
                } else {
                    echo '<span class="badge badge-info">受注生産商品</span>';
                }
                ?>
            </div>
            
            <!-- 評価表示 -->
            <div class="mb-3">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <?php if($i <= $average_rating): ?>
                        <i class="fas fa-star text-warning"></i>
                    <?php elseif($i - 0.5 <= $average_rating): ?>
                        <i class="fas fa-star-half-alt text-warning"></i>
                    <?php else: ?>
                        <i class="far fa-star text-warning"></i>
                    <?php endif; ?>
                <?php endfor; ?>
                <span class="ml-2"><?php echo $average_rating; ?> (<?php echo $review_count; ?>件のレビュー)</span>
            </div>
            
            <!-- 価格表示（バリエーションがある場合は変動） -->
            <div id="product-price-display">
                <p class="h4 text-danger mb-4">¥<?php echo number_format($product->price); ?></p>
            </div>
            
            <p><?php echo $product->description; ?></p>
            
            <!-- 受注生産の説明 -->
            <?php if($preorder_info['is_preorder']): ?>
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> 受注生産商品について</h6>
                <p class="mb-0">この商品は受注生産となります。ご注文いただいてから<?php echo $preorder_info['preorder_period']; ?>でお届け予定です。</p>
            </div>
            <?php endif; ?>
            
            <!-- 商品購入フォーム -->
            <form action="cart.php" method="get" class="mb-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?php echo $product->id; ?>">
                <input type="hidden" name="is_preorder" value="<?php echo $preorder_info['is_preorder'] ? 1 : 0; ?>">
                
                <!-- バリエーション選択肢 -->
                <?php foreach($variations as $variation_name => $variation_options): ?>
                <div class="form-group">
                    <label for="variation_<?php echo $variation_name; ?>"><?php echo $variation_name; ?></label>
                    <select class="form-control variation-select" id="variation_<?php echo $variation_name; ?>" name="variation_id" data-base-price="<?php echo $product->price; ?>" required>
                        <option value="">選択してください</option>
                        <?php foreach($variation_options as $option): ?>
                        <option value="<?php echo $option['id']; ?>" 
                                data-price-adjustment="<?php echo $option['price_adjustment']; ?>"
                                data-stock="<?php echo $option['stock']; ?>">
                            <?php echo $option['variation_value']; ?> 
                            <?php if($option['price_adjustment'] > 0): ?>
                                (+¥<?php echo number_format($option['price_adjustment']); ?>)
                            <?php elseif($option['price_adjustment'] < 0): ?>
                                (-¥<?php echo number_format(abs($option['price_adjustment'])); ?>)
                            <?php endif; ?>
                            <?php if(!$preorder_info['is_preorder']): ?>
                                - 在庫: <?php echo $option['stock']; ?>個
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
                
                <!-- 数量選択 -->
                <div class="form-group">
                    <label for="quantity">数量</label>
                    <select class="form-control" id="quantity" name="quantity" style="width: 100px;">
                        <?php for($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- 在庫状況に基づいたボタン表示 -->
                <div class="btn-group">
                    <?php if($preorder_info['is_preorder']): ?>
                        <!-- 受注生産品の場合はカートに追加ボタンを表示 -->
                        <button type="submit" class="btn btn-warning btn-lg" <?php echo !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i> カートに追加（予約商品）
                        </button>
                    <?php elseif($stock_info['is_available']): ?>
                        <button type="submit" class="btn btn-success btn-lg" id="add-to-cart-btn">
                            <i class="fas fa-shopping-cart"></i> カートに追加
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-lg" disabled>
                            <i class="fas fa-times"></i> 在庫切れ
                        </button>
                    <?php endif; ?>
                    
                    <!-- お気に入りボタン（ログイン済みの場合のみ） -->
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($in_wishlist): ?>
                            <a href="wishlist.php?action=remove&id=<?php echo $product->id; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-danger btn-lg">
                                <i class="fas fa-heart"></i> お気に入りから削除
                            </a>
                        <?php else: ?>
                            <a href="wishlist.php?action=add&id=<?php echo $product->id; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-danger btn-lg">
                                <i class="far fa-heart"></i> お気に入りに追加
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="ml-2">
                            <small class="text-muted">お気に入り機能を使用するにはログインが必要です</small>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- 商品情報 -->
            <div class="mt-4">
                <h5>商品情報</h5>
                <ul class="list-unstyled">
                    <li><strong>カテゴリ:</strong> <?php echo $product->category_name; ?></li>
                    <li><strong>商品コード:</strong> PROD-<?php echo $product->id; ?></li>
                    <li id="stock-status"><strong>在庫状況:</strong> 
                        <span class="<?php echo $stock_info['is_available'] || $preorder_info['is_preorder'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $stock_info['is_available'] || $preorder_info['is_preorder'] ? '購入可能' : '在庫切れ'; ?>
                        </span>
                    </li>
                    <?php if($preorder_info['is_preorder']): ?>
                    <li><strong>納期:</strong> <?php echo $preorder_info['preorder_period']; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- レビューセクション -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>カスタマーレビュー</h3>
            <hr>
            
            <!-- レビュー投稿フォーム（ログイン済みの場合のみ） -->
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="card mb-4">
                <div class="card-header">レビューを投稿する</div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="rating">評価</label>
                            <select class="form-control" id="rating" name="rating" required>
                                <option value="">選択してください</option>
                                <option value="5">★★★★★ (5)</option>
                                <option value="4">★★★★☆ (4)</option>
                                <option value="3">★★★☆☆ (3)</option>
                                <option value="2">★★☆☆☆ (2)</option>
                                <option value="1">★☆☆☆☆ (1)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comment">コメント</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">レビューを投稿</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                レビューを投稿するには<a href="login.php">ログイン</a>してください。
            </div>
            <?php endif; ?>
            
            <!-- レビュー一覧 -->
            <?php
            $stmt = $review->getProductReviews($id);
            
            if($stmt->rowCount() > 0) {
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><?php echo $username; ?></h5>
                                <div>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $rating): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="card-text"><?php echo $comment; ?></p>
                            <p class="card-text"><small class="text-muted">投稿日: <?php echo date('Y年n月j日', strtotime($created)); ?></small></p>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="alert alert-info">
                    まだレビューがありません。最初のレビューを投稿してください。
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    
    <!-- 関連商品 -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>関連商品</h3>
            <hr>
            
            <div class="row">
                <?php
                // カテゴリが同じ商品を取得して表示
                $related_products = $product->getByCategory($product->category_id);
                $count = 0;
                
                while($row = $related_products->fetch(PDO::FETCH_ASSOC)) {
                    // 現在の商品は除外
                    if($row['id'] == $product->id) continue;
                    
                    // 最大4件まで表示
                    if($count >= 4) break;
                    
                    extract($row);
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img class="card-img-top" src="assets/images/<?php echo $image; ?>" alt="<?php echo $name; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $name; ?></h5>
                                <h6 class="card-price">¥<?php echo number_format($price); ?></h6>
                                <a href="product.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">詳細を見る</a>
                            </div>
                        </div>
                    </div>
                    <?php
                    $count++;
                }
                
                if($count == 0) {
                    echo '<div class="col-12"><div class="alert alert-info">関連商品はありません。</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- バリエーション選択のJavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // バリエーション選択時の価格更新処理
    const variationSelects = document.querySelectorAll('.variation-select');
    const priceDisplay = document.getElementById('product-price-display');
    const stockStatus = document.getElementById('stock-status');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    
    // 基本価格を取得
    const basePrice = parseFloat(variationSelects[0]?.dataset.basePrice || 0);
    
    // 変更イベントリスナーを追加
    variationSelects.forEach(select => {
        select.addEventListener('change', updatePrice);
    });
    
    // 価格・在庫表示を更新する関数
    function updatePrice() {
        let totalAdjustment = 0;
        let selectedOption = null;
        let stockLevel = 0;
        
        // 全てのバリエーション選択肢を確認
        variationSelects.forEach(select => {
            if (select.value) {
                selectedOption = select.options[select.selectedIndex];
                totalAdjustment += parseFloat(selectedOption.dataset.priceAdjustment || 0);
                stockLevel = parseInt(selectedOption.dataset.stock || 0);
            }
        });
        
        // 価格を更新
        const adjustedPrice = basePrice + totalAdjustment;
        priceDisplay.innerHTML = `<p class="h4 text-danger mb-4">¥${adjustedPrice.toLocaleString()}</p>`;
        
        // 選択されたオプションがある場合、在庫状態も更新
        if (selectedOption && !<?php echo $preorder_info['is_preorder'] ? 'true' : 'false'; ?>) {
            if (stockLevel > 0) {
                stockStatus.innerHTML = `<strong>在庫状況:</strong> <span class="text-success">在庫あり (${stockLevel}個)</span>`;
                if (addToCartBtn) {
                    addToCartBtn.disabled = false;
                    addToCartBtn.className = 'btn btn-success btn-lg';
                    addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> カートに追加';
                }
            } else {
                stockStatus.innerHTML = `<strong>在庫状況:</strong> <span class="text-danger">在庫切れ</span>`;
                if (addToCartBtn) {
                    addToCartBtn.disabled = true;
                    addToCartBtn.className = 'btn btn-secondary btn-lg';
                    addToCartBtn.innerHTML = '<i class="fas fa-times"></i> 在庫切れ';
                }
            }
        }
    }
});

// 画像切り替えの関数
function changeMainImage(imageFile, productName) {
    document.getElementById('mainImage').src = 'assets/images/' + imageFile;
    document.getElementById('mainImage').alt = productName;
}
</script>

<?php include_once "templates/footer.php"; ?>