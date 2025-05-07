<?php
session_start();
include_once "config/database.php";
include_once "classes/Product.php";

// データベース接続
$database = new Database();
$db = $database->getConnection();

// 商品オブジェクト
$product = new Product($db);

// 検索キーワード取得
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>検索結果: "<?php echo htmlspecialchars($keyword); ?>"</h2>
    
    <?php if(empty($keyword)): ?>
    <div class="alert alert-warning">
        検索キーワードを入力してください。
    </div>
    <?php else: ?>
    
    <div class="row">
        <?php
        $stmt = $product->search($keyword);
        $num = $stmt->rowCount();
        
        if($num > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img class="card-img-top" src="assets/images/<?php echo $image; ?>" alt="<?php echo $name; ?>">
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
        } else {
            ?>
            <div class="col-12">
                <div class="alert alert-info">
                    "<?php echo htmlspecialchars($keyword); ?>" に一致する商品が見つかりませんでした。
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary">トップページに戻る</a>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>