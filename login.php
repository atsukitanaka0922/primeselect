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

// ログイン処理
if(isset($_POST['login'])) {
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    
    $result = $user->login();
    
    if($result) {
        // セッション情報を設定
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        
        // リダイレクト先がある場合はそちらへ
        if(isset($_SESSION['redirect_to'])) {
            $redirect = $_SESSION['redirect_to'];
            unset($_SESSION['redirect_to']);
            header("Location: $redirect");
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error_message = "メールアドレスまたはパスワードが正しくありません。";
    }
}

include_once "templates/header.php";
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">ログイン</div>
                <div class="card-body">
                    <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <div class="alert alert-warning">
                        <strong>注意:</strong> これは模擬サイトです。実際の個人情報は入力しないでください。
                    </div>
                    <form method="post">
                        <div class="form-group">
                            <label for="email">メールアドレス</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">パスワード</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">ログイン情報を記憶する</label>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary btn-block">ログイン</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>アカウントをお持ちでない方は<a href="register.php">こちら</a>から登録できます。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once "templates/footer.php"; ?>