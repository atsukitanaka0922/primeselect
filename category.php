<?php
/**
 * category.php - カテゴリー別商品表示ページ
 * 
 * 特定のカテゴリーに属する商品を表示するページです。
 * 並べ替え機能とフィルタリング機能を備えています。
 * 
 * 機能:
 * - カテゴリーに基づく商品リスト表示
 * - 商品名、価格、新着順などによる並べ替え
 * - 価格帯によるフィルタリング
 * - パンくずリストによるナビゲーション
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// 必要なファイルのインクルード
include_once "config/database.php";   // データベース接続情報
include_once "classes/Product.php";   // 商品クラス
include_once "classes/Category.php";  // カテゴリークラス

// データベース接続の取得
$database = new Database();
$db = $database->getConnection();

// カテゴリーIDの取得 (URLパラメーターから)
$category_id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// ソートパラメーターの取得
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created';  // デフォルトは作成日順
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';  // デフォルトは降順

// カテゴリーオブジェクトの作成と初期化
$category = new Category($db);
$category->id = $category_id;
$category->readOne();  // カテゴリー情報を取得

// 商品オブジェクトの作成
$product = new Product($db);

/**
 * URLクエリパラメーター操作用ヘルパー関数
 * 指定したキーと値をURLに追加または更新します
 * 
 * @param string $key パラメーターキー
 * @param string $value パラメーター値
 * @param string $url 対象のURL (省略時は現在のURL)
 * @return string 更新後のURL
 */
function add_query_arg($key, $value, $url = null) {
    if ($url === null) {
        $url = $_SERVER['REQUEST_URI'];
    }
    
    // 既存のパラメーターを削除
    $url = preg_replace('/([?&])'.$key.'=[^&]+(&|$)/', '$1', $url);
    
    // 新しいパラメーターを追加
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

// 現在のURLを取得 (ソートパラメーターを除く)
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
if (!empty($_SERVER['QUERY_STRING'])) {
    $query_string = $_SERVER['QUERY_STRING'];
    $query_params = [];
    parse_str($query_string, $query_params);
    
    // sort_byとsort_orderを除去
    unset($query_params['sort_by'], $query_params['sort_order']);
    
    // 他のパラメーターを維持
    if (!empty($query_params)) {
        $current_url .= '?' . http_build_query($query_params);
    }
}

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <!-- パンくずリスト -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">ホーム</a></li>
            <li class="breadcrumb-item"><a href="shop.php">ショップ</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $category->name; ?></li>
        </ol>
    </nav>

    <!-- カテゴリータイトルと説明 -->
    <h2><?php echo $category->name; ?></h2>
    <p><?php echo $category->description; ?></p>

    <div class="row">
        <!-- サイドバー (フィルタリングオプション) -->
        <div class="col-md-3">
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
        
        <!-- メインコンテンツ (商品リスト) -->
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
            
            <!-- 商品リスト -->
            <div class="row">
                <?php
                // ソート機能付きでカテゴリー商品を取得
                $stmt = $product->getByCategory($category_id, $sort_by, $sort_order);
                $num = $stmt->rowCount();
                
                if($num > 0) {
                    // 商品がある場合は各商品を表示
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // 配列から変数を個別に取り出し
                        $id = $row['id'];
                        $name = $row['name'];
                        $description = $row['description'];
                        $price = $row['price'];
                        $image = $row['image'];
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
                    // 商品がない場合はメッセージを表示
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

<?php 
// フッターテンプレートのインクルード
include_once "templates/footer.php"; 
?>