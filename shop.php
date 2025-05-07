<?php
session_start();
include_once "config/database.php";
include_once "classes/Product.php";
include_once "classes/Category.php";
include_once "includes/paging.php";

// データベース接続
$database = new Database();
$db = $database->getConnection();

// 商品オブジェクト
$product = new Product($db);

// ページネーション設定
$records_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// カテゴリやフィルタリングの取得
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : null;

// ソートパラメータの取得
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

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
    <h2>商品一覧</h2>
    
    <div class="row">
        <div class="col-md-3">
            <!-- サイドバーフィルタリングオプション -->
            <div class="card mb-4">
                <div class="card-header">カテゴリ</div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $category = new Category($db);
                        $stmt = $category->read();
                        
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $active = ($category_id == $row['id']) ? 'active' : '';
                            echo '<li class="list-group-item ' . $active . '"><a href="shop.php?category_id=' . $row['id'] . '">' . $row['name'] . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">価格帯</div>
                <div class="card-body">
                    <form action="shop.php" method="get">
                        <?php if($category_id): ?>
                        <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="min_price">最低価格</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" value="<?php echo $min_price ?? 0; ?>">
                        </div>
                        <div class="form-group">
                            <label for="max_price">最高価格</label>
                            <input type="number" class="form-control" id="max_price" name="max_price" value="<?php echo $max_price ?? 100000; ?>">
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
                <div>
                    <span>表示中: <span id="product-count"></span>件の商品</span>
                </div>
            </div>
            
            <div class="row">
                <?php
                // クエリ実行
                if($category_id && $min_price && $max_price) {
                    // カテゴリと価格範囲でフィルタリング
                    // 実装略（カテゴリとプライスレンジを組み合わせたメソッドが必要）
                    $stmt = $product->getByCategory($category_id, $sort_by, $sort_order);
                } elseif($category_id) {
                    $stmt = $product->getByCategory($category_id, $sort_by, $sort_order);
                } elseif($min_price && $max_price) {
                    $stmt = $product->getByPriceRange($min_price, $max_price, $sort_by, $sort_order);
                } else {
                    // ソート機能を使用
                    $stmt = $product->readWithSorting($from_record_num, $records_per_page, $sort_by, $sort_order);
                }
                
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
                            条件に一致する商品が見つかりませんでした。
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <?php
            // ページネーション表示
            if(!$category_id && !$min_price && !$max_price) {
                $total_rows = $product->count();
                $page_url = "shop.php";
                echo getPaging($page, $total_rows, $records_per_page, $page_url);
            }
            ?>
        </div>
    </div>
</div>

<script>
// 表示されている商品数を更新
document.addEventListener('DOMContentLoaded', function() {
    var productCount = document.querySelectorAll('.card.h-100').length;
    document.getElementById('product-count').textContent = productCount;
});
</script>

<?php include_once "templates/footer.php"; ?>