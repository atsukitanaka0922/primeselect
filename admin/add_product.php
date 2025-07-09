<?php
/**
 * add_product.php - 管理者用商品追加ページ
 * 
 * 管理者が新規商品を追加するためのフォームと処理を提供します。
 * 商品基本情報、画像アップロード、受注生産設定などの機能があります。
 * 
 * 主な機能:
 * - 商品情報入力フォーム表示
 * - 画像アップロード処理
 * - カテゴリ一覧表示
 * - 受注生産商品の設定処理
 * 
 * @package PrimeSelect
 * @subpackage Admin
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルを読み込み
include_once "../config/database.php";
include_once "../classes/Product.php";
include_once "../classes/Category.php";

// 管理者権限チェック - 権限がなければログインページへリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続を作成
$database = new Database();
$db = $database->getConnection();

// 製品とカテゴリオブジェクトを初期化
$product = new Product($db);
$category = new Category($db);

// 商品追加処理 - フォーム送信時
if(isset($_POST['add_product'])) {
    // POSTデータから商品情報を取得
    $product->name = $_POST['name'];
    $product->description = $_POST['description'];
    $product->price = $_POST['price'];
    $product->category_id = $_POST['category_id'];
    $product->stock = $_POST['stock'];
    $product->is_preorder = isset($_POST['is_preorder']) ? 1 : 0; // チェックボックスの状態を取得
    $product->preorder_period = $_POST['preorder_period'];
    
    // 画像アップロード処理
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // 許可する画像形式を定義
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // 画像形式をチェック
        if(in_array(strtolower($filetype), $allowed)) {
            // 一意のファイル名を生成して保存
            $image_name = uniqid() . '.' . $filetype;
            move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/' . $image_name);
        } else {
            $error_message = "許可されていないファイル形式です。";
        }
    }
    
    // エラーがなければ商品を作成
    if(!isset($error_message)) {
        // 画像をセット
        $product->image = $image_name;
        
        // 商品をデータベースに追加
        if($product->create()) {
            $success_message = "商品を追加しました。";
        } else {
            $error_message = "商品の追加に失敗しました。";
        }
    }
}

// ヘッダーテンプレートを読み込み
include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <!-- サイドバー -->
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- メインコンテンツ -->
        <div class="col-md-10">
            <h2 class="mt-4">商品追加</h2>
            
            <!-- エラーメッセージがあれば表示 -->
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 商品追加フォーム -->
            <div class="card">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <!-- 左側のフォーム項目 -->
                            <div class="col-md-6">
                                <!-- 商品名 -->
                                <div class="form-group">
                                    <label for="name">商品名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <!-- カテゴリ選択 -->
                                <div class="form-group">
                                    <label for="category_id">カテゴリ <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_id" name="category_id" required>
                                        <option value="">カテゴリを選択</option>
                                        <?php
                                        // カテゴリ一覧を取得して表示
                                        $categories = $category->read();
                                        while($cat = $categories->fetch(PDO::FETCH_ASSOC)) {
                                            echo '<option value="' . $cat['id'] . '">' . $cat['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <!-- 価格 -->
                                <div class="form-group">
                                    <label for="price">価格 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" min="0" required>
                                </div>
                                
                                <!-- 在庫数 -->
                                <div class="form-group">
                                    <label for="stock">在庫数 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                </div>
                                
                                <!-- 商品画像 -->
                                <div class="form-group">
                                    <label for="image">商品画像</label>
                                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                                    <small class="form-text text-muted">JPG, PNG, GIF形式をサポート</small>
                                </div>
                            </div>
                            
                            <!-- 右側のフォーム項目 -->
                            <div class="col-md-6">
                                <!-- 商品説明 -->
                                <div class="form-group">
                                    <label for="description">商品説明 <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="6" required></textarea>
                                </div>
                                
                                <!-- 受注生産商品チェックボックス -->
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_preorder" name="is_preorder">
                                        <label class="form-check-label" for="is_preorder">
                                            受注生産商品
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- 受注生産期間（初期は非表示） -->
                                <div class="form-group" id="preorder_period_group" style="display: none;">
                                    <label for="preorder_period">受注生産期間</label>
                                    <input type="text" class="form-control" id="preorder_period" name="preorder_period" 
                                           placeholder="例: 約2週間">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 送信ボタン -->
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

<!-- 受注生産チェックボックスのJS制御 -->
<script>
// 受注生産チェックボックスの変更イベントを監視
document.getElementById('is_preorder').addEventListener('change', function() {
    const preorderGroup = document.getElementById('preorder_period_group');
    const stockInput = document.getElementById('stock');
    
    // チェックボックスの状態に応じて表示/非表示と入力制限を切り替え
    if(this.checked) {
        // 受注生産の場合は期間を表示し、在庫を0に固定
        preorderGroup.style.display = 'block';
        stockInput.value = 0;
        stockInput.readOnly = true;
    } else {
        // 通常商品の場合は期間を非表示にし、在庫入力を許可
        preorderGroup.style.display = 'none';
        stockInput.readOnly = false;
    }
});
</script>

<?php include_once "templates/footer.php"; ?>