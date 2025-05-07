<?php
include_once "config/database.php";
include_once "classes/Product.php";
include_once "classes/Category.php";

// データベース接続
$database = new Database();
$db = $database->getConnection();

// カテゴリID取得
$category_id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// ソートパラメータの取得
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// カテゴリオブジェクト
$category = new Category($db);
$category->id = $category_id;
$category->readOne();

// 商品オブジェクト
$product = new Product($db);

// URLクエリパラメータ操作用ヘルパー関数
function add_query_arg($key, $value, $url = null) {
    if ($url === null) {
        $url = $_SERVER['REQUEST_URI'];
    }
    
    $url = preg_replace('/([?&])'.$key.'=[^&]+(&|$)/', '$1', $url);
    
    if (strpos($url, '?') !== false) {
        if (substr($url, -1) !== '&') {
            $url .= '&';
        }
        $url .= $key.'='.$value;
    } else {
        $url .= '?'.$key.'='.$value;
    }
    
    return rtrim($url, '&');
}

// 現在のURLを取得
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
if (!empty($_SERVER['QUERY_STRING'])) {
    $query_string = $_SERVER['QUERY_STRING'];
    $query_params = [];
    parse_str($query_string, $query_params);
    
    // sort_byとsort_orderを除去
    unset($query_params['sort_by'], $query_params['sort_order']);
    
    if (!empty($query_params)) {
        $current_url .= '?' . http_build_query($query_params);
    }
}

include_once "templates/header.php";
?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">ホーム</a></li>
            <li class="breadcrumb-item"><a href="shop.php">ショップ</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $category->name; ?></li>
        </ol>
    </nav>

    <h2><?php echo $category->name; ?></h2>
    <p><?php echo $category->description; ?></p>

    <div class="row">
        <div class="col-md-3">
            <!-- サイドバーフィルタリングオプション -->
            <div class="card mb-4">
                <div class="card-header">価格帯</div>
                <div class="card-body">
                    <form action="shop.php" method="get">
                        <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                        <div class="form-group">
                            <label for="min_price">最低価格</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" value="0">
                        </div>
                        <div class="form-group">
                            <label for="max_price">最高価格</label>
                            <input type="number" class="form-control" id="max_price" name="max_price" value="100000">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">フィルタ</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- ソートオプション -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <span>並び替え:</span>
                    <div class="btn-group ml-2">
                        <a href="<?php echo add_query_arg('sort_by', 'name', $current_url); ?>&sort_order=ASC" class="btn btn-sm btn-outline-secondary <?php echo ($sort_by == 'name' && $sort_order == 'ASC') ? 'active' : ''; ?>">名前順</a>
                        <a href="<?php echo add_query_arg('sort_by', 'price', $current_url); ?>&sort_order=ASC" class="btn btn-sm btn-outline-secondary <?php echo ($sort_by == 'price' && $sort_order == 'ASC') ? 'active' : ''; ?>">価格が安い順</a>
                        <a href="<?php echo add_query_arg('sort_by', 'price', $current_url); ?>&sort_order=DESC" class="btn btn-sm btn-outline-secondary <?php echo ($sort_by == 'price' && $sort_order == 'DESC') ? 'active' : ''; ?>">価格が高い順</a>
                        <a href="<?php echo add_query_arg('sort_by', 'created', $current_url); ?>&sort_order=DESC" class="btn btn-sm btn-outline-secondary <?php echo ($sort_by == 'created' && $sort_order == 'DESC') ? 'active' : ''; ?>">新着順</a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <?php
                // ソート機能付きでカテゴリ商品を取得
                $stmt = $product->getByCategory($category_id, $sort_by, $sort_order);
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
                            このカテゴリには商品がありません。
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>