<?php
/**
 * templates/sidebar.php - フロントエンド用サイドバーテンプレート
 * 
 * ユーザーサイト側のサイドバーを表示します。
 * 主にカテゴリリストと人気商品を表示する機能を持ちます。
 * 
 * 含まれる内容:
 * - カテゴリリスト
 * - 人気商品一覧（最大3件）
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
?>
<!-- カテゴリリスト -->
<div class="card mb-4">
    <div class="card-header">カテゴリ</div>
    <div class="card-body">
        <ul class="list-group">
            <?php
            // カテゴリ表示
            // データベース接続が利用可能かチェック
            if(isset($db)) {
                // Categoryクラスのロード確認
                if(!class_exists('Category')) {
                    include_once "classes/Category.php";
                }
                
                // カテゴリオブジェクト作成とデータ取得
                $category = new Category($db);
                $stmt = $category->read();
                
                // 各カテゴリへのリンクを表示
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<li class="list-group-item"><a href="category.php?id=' . $row['id'] . '">' . $row['name'] . '</a></li>';
                }
            }
            ?>
        </ul>
    </div>
</div>

<!-- 人気商品 -->
<div class="card">
    <div class="card-header">人気商品</div>
    <div class="card-body">
        <div class="list-group">
            <?php
            // 人気商品表示
            if(isset($db)) {
                // Productクラスのロード確認
                if(!class_exists('Product')) {
                    include_once "classes/Product.php";
                }
                
                // 商品データ取得
                $product = new Product($db);
                $stmt = $product->read();
                $count = 0;
                
                // 上位3件の商品を表示
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // 最大3件まで表示
                    if($count >= 3) break;
                    
                    // 商品リンクを出力
                    echo '<a href="product.php?id=' . $row['id'] . '" class="list-group-item list-group-item-action">';
                    echo '<div class="d-flex align-items-center">';
                    // 商品画像
                    echo '<img src="assets/images/' . $row['image'] . '" alt="' . $row['name'] . '" class="mr-3" style="width: 50px;">';
                    echo '<div>';
                    // 商品名と価格
                    echo '<h6 class="mb-0">' . $row['name'] . '</h6>';
                    echo '<small>¥' . number_format($row['price']) . '</small>';
                    echo '</div>';
                    echo '</div>';
                    echo '</a>';
                    
                    $count++;
                }
            }
            ?>
        </div>
    </div>
</div>