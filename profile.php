<?php
session_start();
include_once "config/database.php";
include_once "classes/User.php";

// 未ログインならログインページへ
if(!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = 'profile.php';
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->getUser();

// プロフィール更新処理
if(isset($_POST['update_profile'])) {
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    
    if($user->updateProfile()) {
        $_SESSION['username'] = $user->username;
        $success_message = "プロフィールを更新しました。";
    } else {
        $error_message = "プロフィールの更新に失敗しました。";
    }
}

// パスワード変更処理
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password !== $confirm_password) {
        $password_error = "新しいパスワードと確認用パスワードが一致しません。";
    } else {
        if($user->updatePassword($current_password, $new_password)) {
            $password_success = "パスワードを変更しました。";
        } else {
            $password_error = "現在のパスワードが正しくありません。";
        }
    }
}

include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>マイプロフィール</h2>
    
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action active">プロフィール</a>
                <a href="orders.php" class="list-group-item list-group-item-action">注文履歴</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">お気に入り</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">ログアウト</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">プロフィール情報</div>
                <div class="card-body">
                    <?php if(isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="username">ユーザー名</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo $user->username; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">メールアドレス</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user->email; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="created">登録日</label>
                            <input type="text" class="form-control" id="created" value="<?php echo date('Y年n月j日', strtotime($user->created)); ?>" readonly>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">更新する</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">パスワード変更</div>
                <div class="card-body">
                    <?php if(isset($password_success)): ?>
                    <div class="alert alert-success"><?php echo $password_success; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($password_error)): ?>
                    <div class="alert alert-danger"><?php echo $password_error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="current_password">現在のパスワード</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">新しいパスワード</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">新しいパスワード（確認）</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">パスワードを変更</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>