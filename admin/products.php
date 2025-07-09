<?php
/**
 * admin/products.php - 管理者用商品管理ページ
 * 
 * 商品の追加、編集、削除などの管理を行うページです。
 * 
 * 主な機能:
 * - 商品一覧の表示
 * - 新規商品の追加
 * - 商品情報の編集
 * - 商品の削除
 * - 受注生産商品の管理
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "../config/database.php";
include_once "../classes/Product.php";
include_once "../classes/Category.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続の初期化
$database = new Database();
$db = $database->getConnection();

// 商品とカテゴリオブジェクトの初期化
$product = new Product($db);
$category = new Category($db);

// 商品追加処理
if(isset($_POST['add_product'])) {
    // フォームデータの取得
    $product->name = $_POST['name'];
    $product->description = $_POST['description'];
    $product->price = $_POST['price'];
    $product->category_id = $_POST['category_id'];
    $product->stock = $_POST['stock'];
    $product->is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $product->preorder_period = $_POST['preorder_period'];
    
    // 在庫数の処理
    if(empty($product->stock) || $product->stock == '') {
        $product->stock = 0;
    }
    
    // 受注生産商品の場合は在庫を強制的に0にする
    if($product->is_preorder == 1) {
        $product->stock = 0;
    }
    
    // 画像アップロード処理
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = '../assets/images/' . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $product->image = $new_filename;
            }
        }
    }
    
    // 商品の作成
    if($product->create()) {
        $success_message = "商品を追加しました。";
    } else {
        $error_message = "商品の追加に失敗しました。";
    }
}

// 商品削除処理
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if($product->delete($product_id)) {
        $success_message = "商品を削除しました。";
    } else {
        $error_message = "商品の削除に失敗しました。";
    }
}

// ヘッダーのインクルード
include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <!-- 左側のサイドバー -->
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- メインコンテンツエリア -->
        <div class="col-md-10">
            <h2 class="mt-4">商品管理</h2>
            
            <!-- 成功・エラーメッセージ表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 商品追加フォーム -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>新しい商品を追加</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <!-- 左側のフォーム要素 -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">商品名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="description">商品説明</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="price">価格 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            
                            <!-- 右側のフォーム要素 -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id">カテゴリ <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_id" name="category_id" required>
                                        <option value="">選択してください</option>
                                        <?php
                                        // カテゴリ一覧を取得してプルダウンメニューに表示
                                        $stmt = $category->read();
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="stock">在庫数</label>
                                    <input type="number" class="form-control" id="stock" name="stock" value="0" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="image">商品画像</label>
                                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_preorder" name="is_preorder">
                                        <label class="form-check-label" for="is_preorder">
                                            受注生産商品
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="preorder_period">受注生産期間</label>
                                    <input type="text" class="form-control" id="preorder_period" name="preorder_period" placeholder="例: 約2週間">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_product" class="btn btn-primary">商品を追加</button>
                    </form>
                </div>
            </div>
            
            <!-- 商品一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5>商品一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>画像</th>
                                    <th>商品名</th>
                                    <th>価格</th>
                                    <th>カテゴリ</th>
                                    <th>在庫</th>
                                    <th>種別</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 商品一覧を取得して表示
                                $stmt = $product->read();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // データを抽出
                                    extract($row);
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/<?php echo $image; ?>" alt="<?php echo htmlspecialchars($name); ?>" width="50">
                                        </td>
                                        <td><?php echo htmlspecialchars($name); ?></td>
                                        <td>¥<?php echo number_format($price); ?></td>
                                        <td><?php echo htmlspecialchars($category_name); ?></td>
                                        <td>
                                            <?php 
                                            // 在庫情報の表示
                                            $stock_info = $product->checkStock($id);
                                            if($product->getPreorderInfo($id)['is_preorder']) {
                                                echo '<span class="badge badge-info">受注生産</span>';
                                            } else {
                                                echo $stock_info['stock'] . '個';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            // 商品タイプの表示
                                            $preorder_info = $product->getPreorderInfo($id);
                                            if($preorder_info['is_preorder']) {
                                                echo '<span class="badge badge-warning">受注生産</span>';
                                            } else {
                                                echo '<span class="badge badge-success">通常商品</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <!-- 操作ボタン -->
                                            <a href="product_edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">編集</a>
                                            <a href="products.php?delete=1&id=<?php echo $id; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('本当に削除しますか？')">削除</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * 受注生産チェックボックスの制御
 * 受注生産商品の場合は在庫フィールドを無効化する
 */
document.addEventListener('DOMContentLoaded', function() {
    const isPreorderCheckbox = document.getElementById('is_preorder');
    const stockField = document.getElementById('stock');
    const preorderPeriodField = document.getElementById('preorder_period');
    
    // チェックボックス変更時の処理
    isPreorderCheckbox.addEventListener('change', function() {
        if(this.checked) {
            // 受注生産商品の場合は在庫を0に固定して無効化
            stockField.value = 0;
            stockField.disabled = true;
            preorderPeriodField.required = true;
        } else {
            // 通常商品の場合は在庫を編集可能に
            stockField.disabled = false;
            preorderPeriodField.required = false;
        }
    });
    
    // フォーム送信時の処理
    document.querySelector('form').addEventListener('submit', function(e) {
        // 在庫数が空の場合は0を設定
        if(!stockField.value || stockField.value === '') {
            stockField.value = 0;
        }
        
        // 受注生産商品の場合は在庫を強制的に0にする
        if(isPreorderCheckbox.checked) {
            stockField.value = 0;
        }
    });
});
</script>

<?php include_once "templates/footer.php"; ?>