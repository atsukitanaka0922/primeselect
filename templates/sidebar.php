<?php
/**
 * サイドバーテンプレート
 * 
 * カテゴリリストと人気商品を表示します。
 */
?>
<!-- カテゴリリスト -->
<div class="card mb-4">
    <div class="card-header">カテゴリ</div>
    <div class="card-body">
        <ul class="list-group">
            <?php
            if(isset($db)) {
                if(!class_exists('Category')) {
                    include_once "classes/Category.php";
                }
                
                $category = new Category($db);
                $stmt = $category->read();
                
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
            if(isset($db)) {
                if(!class_exists('Product')) {
                    include_once "classes/Product.php";
                }
                
                $product = new Product($db);
                $stmt = $product->read();
                $count = 0;
                
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if($count >= 3) break;  // 最大3件表示
                    
                    echo '<a href="product.php?id=' . $row['id'] . '" class="list-group-item list-group-item-action">';
                    echo '<div class="d-flex align-items-center">';
                    echo '<img src="assets/images/' . $row['image'] . '" alt="' . $row['name'] . '" class="mr-3" style="width: 50px;">';
                    echo '<div>';
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