<?php
/**
 * categories.php - 管理者用カテゴリ管理ページ
 * 
 * 商品カテゴリの管理（追加、編集、削除）を行うための管理者用ページです。
 * 
 * 主な機能:
 * - カテゴリ一覧の表示
 * - 新規カテゴリの追加
 * - カテゴリの編集（モーダルウィンドウを使用）
 * - カテゴリの削除（関連商品がない場合のみ）
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
include_once "../classes/Category.php";

// 管理者権限チェック - 権限がなければログインページへリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続を作成
$database = new Database();
$db = $database->getConnection();

// カテゴリオブジェクトを初期化
$category = new Category($db);

// カテゴリ追加処理 - フォーム送信時
if(isset($_POST['add_category'])) {
    // POSTデータからカテゴリ情報を取得
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    // カテゴリをデータベースに追加
    $query = "INSERT INTO categories SET name = ?, description = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $description);
    
    if($stmt->execute()) {
        $success_message = "カテゴリを追加しました。";
    } else {
        $error_message = "カテゴリの追加に失敗しました。";
    }
}

// カテゴリ編集処理
if(isset($_POST['edit_category'])) {
    // POSTデータから更新情報を取得
    $id = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    // カテゴリをデータベースで更新
    $query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $name);
    $stmt->bindParam(2, $description);
    $stmt->bindParam(3, $id);
    
    if($stmt->execute()) {
        $success_message = "カテゴリを更新しました。";
    } else {
        $error_message = "カテゴリの更新に失敗しました。";
    }
}

// カテゴリ削除処理
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 関連商品があるかチェック - 関連商品がある場合は削除できない
    $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(1, $id);
    $check_stmt->execute();
    $count_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if($count_result['count'] > 0) {
        $error_message = "このカテゴリには商品が関連付けられているため削除できません。";
    } else {
        // 関連商品がなければ削除実行
        $query = "DELETE FROM categories WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        
        if($stmt->execute()) {
            $success_message = "カテゴリを削除しました。";
        } else {
            $error_message = "カテゴリの削除に失敗しました。";
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
            <h2 class="mt-4">カテゴリ管理</h2>
            
            <!-- 成功/エラーメッセージがあれば表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- カテゴリ追加フォーム -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>新しいカテゴリを追加</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">カテゴリ名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">説明</label>
                                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">カテゴリを追加</button>
                    </form>
                </div>
            </div>
            
            <!-- カテゴリ一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5>カテゴリ一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>カテゴリ名</th>
                                    <th>説明</th>
                                    <th>商品数</th>
                                    <th>作成日</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // カテゴリ一覧を取得して表示
                                $stmt = $category->read();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // カテゴリごとの商品数を取得
                                    $count_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
                                    $count_stmt = $db->prepare($count_query);
                                    $count_stmt->bindParam(1, $row['id']);
                                    $count_stmt->execute();
                                    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                                        <td><?php echo $count_result['product_count']; ?>件</td>
                                        <td>
                                            <?php 
                                            // 作成日の表示形式を整形
                                            if(isset($row['created']) && !empty($row['created']) && $row['created'] != '0000-00-00 00:00:00') {
                                                echo date('Y-m-d', strtotime($row['created']));
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <!-- 編集ボタン - モーダル表示用 -->
                                            <button class="btn btn-sm btn-primary" data-toggle="modal" 
                                                    data-target="#editModal" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($row['description'] ?? ''); ?>">
                                                編集
                                            </button>
                                            
                                            <!-- 削除ボタン -->
                                            <a href="categories.php?delete=1&id=<?php echo $row['id']; ?>" 
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

<!-- 編集用モーダルウィンドウ -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">カテゴリ編集</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="modal-category-id">
                    <div class="form-group">
                        <label for="modal-name">カテゴリ名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="modal-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="modal-description">説明</label>
                        <textarea class="form-control" id="modal-description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- モーダルウィンドウの制御用JavaScript -->
<script>
$(document).ready(function() {
    // カテゴリ編集モーダルのイベント処理
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var description = button.data('description');
        
        var modal = $(this);
        modal.find('#modal-category-id').val(id);
        modal.find('#modal-name').val(name);
        modal.find('#modal-description').val(description);
    });
});
</script>

<?php include_once "templates/footer.php"; ?>