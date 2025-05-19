<?php
/**
 * search.php - 商品検索結果ページ
 * 
 * キーワード検索に基づいた商品を表示するページです。
 * ユーザーの検索クエリに合致する商品を表示します。
 * 
 * 機能:
 * - キーワードによる商品検索
 * - 検索結果の表示
 * - 検索結果が0件の場合のメッセージ表示
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";  // データベース接続情報
include_once "classes/Product.php";  // 商品クラス

// データベース接続の取得
$database = new Database();
$db = $database->getConnection();

// 商品オブジェクトの作成
$product = new Product($db);

// 検索キーワードの取得 (GETパラメータから)
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <!-- 検索結果タイトル -->
    <h2>検索結果: "<?php echo htmlspecialchars($keyword); ?>"</h2>
    
    <?php if(empty($keyword)): ?>
    <!-- 検索キーワードが空の場合の警告メッセージ -->
    <div class="alert alert-warning">
        検索キーワードを入力してください。
    </div>
    <?php else: ?>
    
    <!-- 検索結果商品リスト -->
    <div class="row">
        <?php
        // 検索実行
        $stmt = $product->search($keyword);
        $num = $stmt->rowCount();
        
        if($num > 0) {
            // 検索結果がある場合は商品を表示
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
            // 検索結果が0件の場合のメッセージ
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
    
    <!-- トップページに戻るボタン -->
    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary">トップページに戻る</a>
    </div>
</div>

<?php 
// フッターテンプレートのインクルード
include_once "templates/footer.php"; 
?>