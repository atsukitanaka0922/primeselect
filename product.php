<?php
session_start();
include_once "config/database.php";
include_once "classes/Product.php";
include_once "classes/Review.php";
include_once "classes/Wishlist.php";

// データベース接続
$database = new Database();
$db = $database->getConnection();

// 商品ID取得
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// 商品オブジェクト
$product = new Product($db);
$product->id = $id;
$product->readOne();

// 商品画像取得
$product_images = $product->getProductImages($id);

// レビューオブジェクト
$review = new Review($db);

// お気に入りオブジェクト
$wishlist = new Wishlist($db);
$in_wishlist = false;

if(isset($_SESSION['user_id'])) {
    $wishlist->user_id = $_SESSION['user_id'];
    $wishlist->product_id = $id;
    $in_wishlist = $wishlist->isInWishlist();
}

// レビュー送信処理
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

include_once "templates/header.php";
?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">ホーム</a></li>
            <li class="breadcrumb-item"><a href="shop.php">ショップ</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $product->name; ?></li>
        </ol>
    </nav>
    
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <!-- メイン画像表示 -->
            <div class="product-main-image mb-3">
                <img id="mainImage" src="assets/images/<?php echo $product->image; ?>" class="img-fluid" alt="<?php echo $product->name; ?>">
            </div>
            
            <!-- サムネイル画像表示 -->
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
        <div class="col-md-6">
            <h2><?php echo $product->name; ?></h2>
            
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
            
            <p class="h4 text-danger mb-4">¥<?php echo number_format($product->price); ?></p>
            
            <p><?php echo $product->description; ?></p>
            
            <form action="cart.php?action=add&id=<?php echo $product->id; ?>" method="post" class="mb-4">
                <div class="form-group">
                    <label for="quantity">数量</label>
                    <select class="form-control" id="quantity" name="quantity" style="width: 100px;">
                        <?php for($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-success btn-lg">カートに追加</button>
                    
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
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="mt-4">
                <h5>商品情報</h5>
                <ul class="list-unstyled">
                    <li><strong>カテゴリ:</strong> <?php echo $product->category_name; ?></li>
                    <li><strong>商品コード:</strong> PROD-<?php echo $product->id; ?></li>
                    <li><strong>在庫状況:</strong> <span class="text-success">在庫あり</span></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- レビューセクション -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>カスタマーレビュー</h3>
            <hr>
            
            <!-- レビュー投稿フォーム -->
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
                // カテゴリが同じ商品を表示（プレースホルダー）
                $related_products = $product->getByCategory($product->category_id);
                $count = 0;
                
                while($row = $related_products->fetch(PDO::FETCH_ASSOC)) {
                    // 現在の商品は除外
                    if($row['id'] == $product->id) continue;
                    
                    // 最大4件まで表示
                    if($count >= 4) break;
                    
                    extract($row);
                    
                    // メイン画像を取得
                    $main_image = $product->getMainImage($id) ?? $image;
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <img class="card-img-top" src="assets/images/<?php echo $main_image; ?>" alt="<?php echo $name; ?>">
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

<!-- JavaScript for image switching -->
<script>
function changeMainImage(imageFile, productName) {
    document.getElementById('mainImage').src = 'assets/images/' + imageFile;
    document.getElementById('mainImage').alt = productName;
}
</script>

<?php include_once "templates/footer.php"; ?>