<?php
/**
 * 管理者用商品追加ページ
 * 
 * 新規商品の追加フォーム
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "../config/database.php";
include_once "../classes/Product.php";
include_once "../classes/Category.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$product = new Product($db);
$category = new Category($db);

// 商品追加処理
if(isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $stock = $_POST['stock'];
    $is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $preorder_period = $_POST['preorder_period'];
    
    // 画像アップロード処理
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $image_name = uniqid() . '.' . $filetype;
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image_name);
        } else {
            $error_message = "許可されていないファイル形式です。";
        }
    }
    
    if(!isset($error_message)) {
        // 商品をデータベースに追加
        $query = "INSERT INTO products 
                 (name, description, price, category_id, image, stock, is_preorder, preorder_period) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $price);
        $stmt->bindParam(4, $category_id);
        $stmt->bindParam(5, $image_name);
        $stmt->bindParam(6, $stock);
        $stmt->bindParam(7, $is_preorder);
        $stmt->bindParam(8, $preorder_period);
        
        if($stmt->execute()) {
            $product_id = $db->lastInsertId();
            
            // メイン画像として登録
            if($image_name) {
                $img_query = "INSERT INTO product_images (product_id, image_file, is_main) VALUES (?, ?, 1)";
                $img_stmt = $db->prepare($img_query);
                $img_stmt->bindParam(1, $product_id);
                $img_stmt->bindParam(2, $image_name);
                $img_stmt->execute();
            }
            
            header('Location: products.php?success=added');
            exit();
        } else {
            $error_message = "商品の追加に失敗しました。";
        }
    }
}

include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <h2 class="mt-4">商品追加</h2>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">商品名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category_id">カテゴリ <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_id" name="category_id" required>
                                        <option value="">カテゴリを選択</option>
                                        <?php
                                        $categories = $category->read();
                                        while($cat = $categories->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $cat['id'] . '">' . $cat['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="price">価格 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="stock">在庫数 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="image">商品画像</label>
                                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                                    <small class="form-text text-muted">JPG, PNG, GIF形式をサポート</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">商品説明 <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_preorder" name="is_preorder">
                                        <label class="form-check-label" for="is_preorder">
                                            受注生産商品
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group" id="preorder_period_group" style="display: none;">
                                    <label for="preorder_period">受注生産期間</label>
                                    <input type="text" class="form-control" id="preorder_period" name="preorder_period" 
                                           placeholder="例: 約2週間">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="add_product" class="btn btn-success">
                                <i class="fas fa-save"></i> 商品を追加
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> 戻る
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 受注生産チェックボックスの制御
document.getElementById('is_preorder').addEventListener('change', function() {
    const preorderGroup = document.getElementById('preorder_period_group');
    const stockInput = document.getElementById('stock');
    
    if(this.checked) {
        preorderGroup.style.display = 'block';
        stockInput.value = 0;
        stockInput.readOnly = true;
    } else {
        preorderGroup.style.display = 'none';
        stockInput.readOnly = false;
    }
});
</script>

<?php include_once "templates/footer.php"; ?>