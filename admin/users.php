<?php
/**
 * ユーザー管理ページ（管理者用）
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "../config/database.php";
include_once "../classes/User.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

// 管理者権限の更新処理
if(isset($_POST['update_admin_status'])) {
    $user_id = intval($_POST['user_id']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    // 自分自身の権限を変更しようとした場合の確認
    if($user_id == $_SESSION['user_id'] && $is_admin == 0) {
        $error_message = "自分自身の管理者権限を削除することはできません。";
    } else {
        try {
            if($user->updateAdminStatus($user_id, $is_admin)) {
                $success_message = "ユーザーの権限を更新しました。";
                header("Location: users.php");
                exit();
            } else {
                $error_message = "権限の更新に失敗しました。";
            }
        } catch(Exception $e) {
            $error_message = "権限の更新中にエラーが発生しました: " . $e->getMessage();
        }
    }
}

// ユーザー削除処理
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    if($user->delete($user_id)) {
        $success_message = "ユーザーを削除しました。";
        header("Location: users.php");
        exit();
    } else {
        $error_message = "ユーザーの削除に失敗しました。管理者は削除できません。";
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
            <h2 class="mt-4">ユーザー管理</h2>
            
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- 統計カード -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総ユーザー数</h5>
                            <h3 class="text-primary"><?php echo $user->count(); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">管理者数</h5>
                            <h3 class="text-warning"><?php echo $user->countAdmins(); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ユーザー一覧 -->
            <div class="card">
                <div class="card-header">
                    <h5>ユーザー一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ユーザー名</th>
                                    <th>メールアドレス</th>
                                    <th>権限</th>
                                    <th>登録日</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $user->readAll();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <?php if($row['is_admin']): ?>
                                                <span class="badge badge-danger">管理者</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">一般ユーザー</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($row['created'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-toggle="modal" 
                                                    data-target="#permissionModal" 
                                                    data-user-id="<?php echo $row['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                                    data-is-admin="<?php echo $row['is_admin']; ?>">
                                                権限変更
                                            </button>
                                            <?php if(!$row['is_admin']): ?>
                                            <a href="users.php?delete=1&id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('本当に削除しますか？')">削除</a>
                                            <?php endif; ?>
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

<!-- 権限変更モーダル -->
<div class="modal fade" id="permissionModal" tabindex="-1" role="dialog" aria-labelledby="permissionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionModalLabel">ユーザー権限変更</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="modal-user-id">
                    <div class="form-group">
                        <label>ユーザー名</label>
                        <p id="modal-username" class="form-control-plaintext"></p>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-is-admin" name="is_admin">
                            <label class="form-check-label" for="modal-is-admin">
                                管理者権限を付与する
                            </label>
                        </div>
                        <small class="form-text text-muted">管理者は全ての管理機能にアクセスできます。</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_admin_status" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 権限変更モーダル
    $('#permissionModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var userId = button.data('user-id');
        var username = button.data('username');
        var isAdmin = button.data('is-admin');
        
        var modal = $(this);
        modal.find('#modal-user-id').val(userId);
        modal.find('#modal-username').text(username);
        modal.find('#modal-is-admin').prop('checked', isAdmin == 1 || isAdmin == '1');
    });
});
</script>

<?php include_once "templates/footer.php"; ?>