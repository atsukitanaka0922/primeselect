<?php
/**
 * product_edit.php - 商品編集ページ（管理者用）
 * 
 * 商品情報の編集、更新を行う管理者用ページです。
 * 通常商品と受注生産商品の両方に対応しており、商品情報を更新することができます。
 * 
 * 主な機能:
 * - 商品情報の表示と編集
 * - 商品画像のアップロード・変更
 * - 受注生産商品の設定
 * - カテゴリ選択
 * 
 * @package PrimeSelect
 * @subpackage Admin
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルの読み込み
include_once "../config/database.php";
include_once "../classes/Product.php";
include_once "../classes/Category.php";

// 管理者権限チェック - 権限がない場合はログインページにリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// 商品IDが指定されていない場合は商品一覧にリダイレクト
$id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Missing ID.');

// データベース接続
$database = new Database();
$db = $database->getConnection();

// Product と Category クラスのインスタンス化
$product = new Product($db);
$category = new Category($db);

// 商品情報を取得
$product->id = $id;
$product->readOne();

/**
 * 商品更新処理
 * フォームから送信されたデータで商品情報を更新します。
 */
if(isset($_POST['update_product'])) {
    // フォームデータを商品オブジェクトにセット
    $product->name = $_POST['name'];
    $product->description = $_POST['description'];
    $product->price = $_POST['price'];
    $product->category_id = $_POST['category_id'];
    $product->stock = $_POST['stock'];
    $product->is_preorder = isset($_POST['is_preorder']) ? 1 : 0;
    $product->preorder_period = $_POST['preorder_period'];
    
    // 画像アップロード処理
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif']; // 許可する画像形式
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // 許可された画像形式かチェック
        if(in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype; // ユニークなファイル名を生成
            $upload_path = '../assets/images/' . $new_filename;
            
            // ファイルをアップロードディレクトリに移動
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $product->image = $new_filename;
            }
        }
    }
    
    // 商品情報をデータベースに更新
    if($product->update()) {
        $success_message = "商品を更新しました。";
    } else {
        $error_message = "商品の更新に失敗しました。";
    }
}

// ヘッダーテンプレートのインクルード
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
            <h2 class="mt-4">商品編集</h2>
            
            <!-- 成功メッセージ表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <!-- エラーメッセージ表示 -->
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 商品編集フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5>商品情報編集</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <!-- 左カラム：基本情報 -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">商品名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo $product->name; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="description">商品説明</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $product->description; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="price">価格 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo $product->price; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="stock">在庫数</label>
                                    <input type="number" class="form-control" id="stock" name="stock" 
                                           value="<?php echo $product->checkStock($id)['stock']; ?>" min="0">
                                </div>
                            </div>
                            
                            <!-- 右カラム：カテゴリ、画像、受注生産設定 -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id">カテゴリ <span class="text-danger">*</span></label>
                                    <select class="form-control" id="category_id" name="category_id" required>
                                        <option value="">選択してください</option>
                                        <?php
                                        // カテゴリ一覧を取得して選択肢を生成
                                        $stmt = $category->read();
                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            // 現在の商品のカテゴリを選択状態にする
                                            $selected = ($row['id'] == $product->category_id) ? 'selected' : '';
                                            echo '<option value="' . $row['id'] . '" ' . $selected . '>' . $row['name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>現在の画像</label><br>
                                    <img src="../assets/images/<?php echo $product->image; ?>" alt="<?php echo $product->name; ?>" width="150">
                                </div>
                                <div class="form-group">
                                    <label for="image">新しい画像（変更する場合）</label>
                                    <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_preorder" name="is_preorder" 
                                               <?php echo $product->getPreorderInfo($id)['is_preorder'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_preorder">
                                            受注生産商品
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="preorder_period">受注生産期間</label>
                                    <input type="text" class="form-control" id="preorder_period" name="preorder_period" 
                                           value="<?php echo $product->getPreorderInfo($id)['preorder_period']; ?>" 
                                           placeholder="例: 約2週間">
                                </div>
                            </div>
                        </div>
                        <!-- 操作ボタン -->
                        <div class="form-group">
                            <button type="submit" name="update_product" class="btn btn-primary">
                                <i class="fas fa-save"></i> 更新
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

<!-- 受注生産商品のJavaScript処理 -->
<script>
// 受注生産チェックボックスの制御
document.getElementById('is_preorder').addEventListener('change', function() {
    const stockField = document.getElementById('stock');
    const preorderPeriodField = document.getElementById('preorder_period');
    
    if(this.checked) {
        // 受注生産商品の場合は在庫フィールドを0にして編集不可に
        stockField.value = 0;
        stockField.readOnly = true;
        preorderPeriodField.required = true;
    } else {
        // 通常商品の場合は在庫フィールドを編集可能に
        stockField.readOnly = false;
        preorderPeriodField.required = false;
    }
});

// ページ読み込み時の処理
document.addEventListener('DOMContentLoaded', function() {
    // 初期表示時、受注生産商品の場合の表示制御
    if(document.getElementById('is_preorder').checked) {
        document.getElementById('stock').readOnly = true;
        document.getElementById('preorder_period').required = true;
    }
});
</script>

<?php include_once "templates/footer.php"; ?>