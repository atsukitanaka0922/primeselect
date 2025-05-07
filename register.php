<?php
session_start();
include_once "config/database.php";
include_once "classes/User.php";

// 既にログイン済みの場合はリダイレクト
if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

// 登録処理
if(isset($_POST['register'])) {
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    
    // パスワード確認
    if($_POST['password'] !== $_POST['confirm_password']) {
        $error_message = "パスワードと確認用パスワードが一致しません。";
    } else {
        // ユーザー作成
        if($user->create()) {
            // 自動ログイン
            $_SESSION['user_id'] = $db->lastInsertId();
            $_SESSION['username'] = $user->username;
            
            // リダイレクト
            header('Location: index.php?new_account=1');
            exit();
        } else {
            $error_message = "ユーザー登録に失敗しました。このメールアドレスは既に使用されている可能性があります。";
        }
    }
}

include_once "templates/header.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                    <div class="alert alert-warning">
                        <strong>注意:</strong> これは模擬サイトです。実際の個人情報は入力しないでください。
                    </div>
                <div class="card-header">会員登録</div>
                <div class="card-body">
                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="username">ユーザー名</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="email">メールアドレス</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">パスワード</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">パスワード（確認用）</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary btn-block">登録する</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>既にアカウントをお持ちの方は<a href="login.php">こちら</a>からログインできます。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>