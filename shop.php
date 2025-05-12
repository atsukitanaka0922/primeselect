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

<style>
.card-img-wrapper {
    position: relative;
    overflow: hidden;
}

.stock-status {
    font-size: 0.875rem;
}

.preorder-info {
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
}

.card-img-top {
    height: 200px;
    object-fit: cover;
}
</style>

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
                        
                        // 受注生産情報と在庫情報を取得
                        $preorder_info = $product->getPreorderInfo($id);
                        $stock_info = $product->checkStock($id);
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-img-wrapper position-relative">
                                    <img class="card-img-top" src="assets/images/<?php echo $image; ?>" alt="<?php echo $name; ?>">
                                    
                                    <?php
                                    // 受注生産商品の場合はバッジを表示
                                    if($preorder_info['is_preorder']): ?>
                                    <div class="position-absolute" style="top: 10px; right: 10px;">
                                        <span class="badge badge-warning">受注生産</span>
                                    </div>
                                    <?php else:
                                        // 通常商品の場合は在庫状況を確認
                                        if(!$stock_info['is_available']): ?>
                                    <div class="position-absolute" style="top: 10px; right: 10px;">
                                        <span class="badge badge-danger">在庫切れ</span>
                                    </div>
                                    <?php elseif($stock_info['status'] == 'low_stock'): ?>
                                    <div class="position-absolute" style="top: 10px; right: 10px;">
                                        <span class="badge badge-warning">残り少ない</span>
                                    </div>
                                    <?php endif;
                                    endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $name; ?></h5>
                                    <p class="card-text"><?php echo substr($description, 0, 100) . '...'; ?></p>
                                    <h6 class="card-price">¥<?php echo number_format($price); ?></h6>
                                    
                                    <!-- 在庫状況表示 -->
                                    <?php if(!$preorder_info['is_preorder']): ?>
                                    <div class="stock-status mb-2">
                                        <?php switch($stock_info['status']):
                                            case 'in_stock': ?>
                                                <small class="text-success"><i class="fas fa-check-circle"></i> 在庫あり</small>
                                            <?php break;
                                            case 'low_stock': ?>
                                                <small class="text-warning"><i class="fas fa-exclamation-triangle"></i> 残り少ない</small>
                                            <?php break;
                                            case 'out_of_stock': ?>
                                                <small class="text-danger"><i class="fas fa-times-circle"></i> 在庫切れ</small>
                                            <?php break;
                                        endswitch; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="preorder-info mb-2">
                                        <small class="text-info"><i class="fas fa-calendar-alt"></i> 受注生産 (<?php echo $preorder_info['preorder_period']; ?>)</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group" role="group">
                                        <a href="product.php?id=<?php echo $id; ?>" class="btn btn-primary">詳細を見る</a>
                                        
                                        <?php if($preorder_info['is_preorder']): ?>
                                            <a href="product.php?id=<?php echo $id; ?>" class="btn btn-warning">予約注文</a>
                                        <?php elseif($stock_info['is_available']): ?>
                                            <a href="cart.php?action=add&id=<?php echo $id; ?>" class="btn btn-success">カートに追加</a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>在庫切れ</button>
                                        <?php endif; ?>
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