<?php
/**
 * ユーザー管理ページ（管理者用）- 修正版
 * 
 * @author Prime Select Team
 * @version 1.1
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
    $user_id = $_POST['user_id'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    if($user->updateAdminStatus($user_id, $is_admin)) {
        $success_message = "ユーザーの権限を更新しました。";
    } else {
        $error_message = "権限の更新に失敗しました。";
    }
}

// ユーザー削除処理
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    if($user->delete($user_id)) {
        $success_message = "ユーザーを削除しました。";
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
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary permission-btn" 
                                                    data-toggle="modal" 
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
            <form method="post" action="users.php">
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
                        <p id="modal-username" class="form-control-plaintext font-weight-bold"></p>
                    </div>
                    <div class="form-group">
                        <label>現在の権限</label>
                        <p id="modal-current-role" class="form-control-plaintext"></p>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-is-admin" name="is_admin">
                            <label class="form-check-label" for="modal-is-admin">
                                <strong>管理者権限を付与する</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">管理者は全ての管理機能にアクセスできます。</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <button type="submit" name="update_admin_status" class="btn btn-primary">権限を更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQueryと Bootstrap のJavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    console.log('ユーザー管理ページが読み込まれました');
    console.log('権限変更ボタンの数:', $('.permission-btn').length);
    
    // 権限変更モーダルのイベント処理
    $('#permissionModal').on('show.bs.modal', function (event) {
        console.log('権限変更モーダルが開かれました');
        
        var button = $(event.relatedTarget);
        var userId = button.attr('data-user-id');
        var username = button.attr('data-username');
        var isAdmin = button.attr('data-is-admin');
        
        console.log('取得したデータ:', {
            userId: userId,
            username: username,
            isAdmin: isAdmin
        });
        
        var modal = $(this);
        modal.find('#modal-user-id').val(userId);
        modal.find('#modal-username').text(username || 'ユーザー名取得エラー');
        
        // 現在の権限を表示
        var currentRole = (isAdmin == '1') ? '管理者' : '一般ユーザー';
        modal.find('#modal-current-role').text(currentRole);
        
        // チェックボックスの状態を設定
        modal.find('#modal-is-admin').prop('checked', isAdmin == '1');
        
        console.log('モーダルに設定された値:', {
            userId: modal.find('#modal-user-id').val(),
            username: modal.find('#modal-username').text(),
            isAdmin: modal.find('#modal-is-admin').prop('checked')
        });
    });
    
    // モーダルが完全に表示された後のイベント
    $('#permissionModal').on('shown.bs.modal', function (event) {
        console.log('モーダルの表示が完了しました');
    });
    
    // フォーム送信時の確認
    $('#permissionModal form').on('submit', function(e) {
        var userId = $('#modal-user-id').val();
        var username = $('#modal-username').text();
        var isAdmin = $('#modal-is-admin').prop('checked');
        var currentRole = $('#modal-current-role').text();
        var newRole = isAdmin ? '管理者' : '一般ユーザー';
        
        if(currentRole === newRole) {
            e.preventDefault();
            alert('権限に変更がありません。');
            return false;
        }
        
        var message = 'ユーザー「' + username + '」の権限を変更しますか？\n\n';
        message += '現在の権限: ' + currentRole + '\n';
        message += '新しい権限: ' + newRole;
        
        if(!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // ボタンクリック時のデバッグ
    $('.permission-btn').on('click', function() {
        console.log('権限変更ボタンがクリックされました');
        console.log('ボタンのdata属性:', this.dataset);
    });
    
    // ページ読み込み後のチェック
    setTimeout(function() {
        console.log('Bootstrap modal:', $('#permissionModal').modal);
        console.log('モーダル要素存在チェック:', $('#permissionModal').length);
    }, 1000);
});
</script>

<?php include_once "templates/footer.php"; ?>