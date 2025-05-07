<?php
session_start();
include_once "config/database.php";
include_once "classes/Wishlist.php";
include_once "classes/Product.php";

// 未ログインならログインページへ
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'wishlist.php';
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$wishlist = new Wishlist($db);

// アクション処理
if(isset($_GET['action'])) {
    if($_GET['action'] == 'add' && isset($_GET['id'])) {
        $wishlist->user_id = $user_id;
        $wishlist->product_id = $_GET['id'];
        
        if($wishlist->add()) {
            $_SESSION['success_message'] = "商品をお気に入りに追加しました。";
        } else {
            $_SESSION['error_message'] = "お気に入りへの追加に失敗しました。";
        }
        
        // 元のページにリダイレクト
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'wishlist.php';
        header("Location: $redirect");
        exit();
    }
    
    if($_GET['action'] == 'remove' && isset($_GET['id'])) {
        $wishlist->user_id = $user_id;
        $wishlist->product_id = $_GET['id'];
        
        if($wishlist->remove()) {
            $_SESSION['success_message'] = "商品をお気に入りから削除しました。";
        } else {
            $_SESSION['error_message'] = "お気に入りからの削除に失敗しました。";
        }
        
        // 元のページにリダイレクト
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'wishlist.php';
        header("Location: $redirect");
        exit();
    }
}

include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>お気に入り</h2>
    
    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <?php
        $stmt = $wishlist->getUserWishlist($user_id);
        $num = $stmt->rowCount();
        
        if($num > 0) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img class="card-img-top" src="assets/images/<?php echo $image; ?>" alt="<?php echo $name; ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $name; ?></h5>
                            <h6 class="card-price">¥<?php echo number_format($price); ?></h6>
                            <div class="btn-group" role="group">
                                <a href="product.php?id=<?php echo $product_id; ?>" class="btn btn-primary">詳細を見る</a>
                                <a href="cart.php?action=add&id=<?php echo $product_id; ?>" class="btn btn-success">カートに追加</a>
                                <a href="wishlist.php?action=remove&id=<?php echo $product_id; ?>" class="btn btn-danger">
                                    <i class="fas fa-heart-broken"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="col-12">
                <div class="alert alert-info">
                    お気に入りに商品がありません。<a href="shop.php">ショップを探索</a>して商品を追加してください。
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>