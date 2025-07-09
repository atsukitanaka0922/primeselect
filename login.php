<?php
/**
 * login.php - ユーザーログインページ
 * 
 * ユーザーのログイン処理を行うページです。
 * メールアドレスとパスワードによる認証を実行します。
 * 
 * 機能：
 * - ログインフォーム表示
 * - 認証処理
 * - エラーメッセージ表示
 * - 管理者/一般ユーザーの振り分け
 * - セッション管理
 * - リダイレクト処理
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";
include_once "classes/User.php";

// 既にログイン済みの場合はリダイレクト
if(isset($_SESSION['user_id'])) {
    // 管理者の場合は管理パネルへ、一般ユーザーの場合はトップページへ
    if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// データベース接続
$database = new Database();
$db = $database->getConnection();

// ユーザーオブジェクト
$user = new User($db);

// ログイン処理（フォーム送信時）
if(isset($_POST['login'])) {
    // POSTデータをオブジェクトに設定
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    
    // ログイン認証実行
    $result = $user->login();
    
    if($result) {
        // 認証成功: セッション情報を設定
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['username'] = $result['username'];
        $_SESSION['is_admin'] = $result['is_admin'];  // 管理者フラグ
        
        // 管理者の場合は管理パネルにリダイレクト
        if($result['is_admin'] == 1) {
            header('Location: admin/index.php');
        } else {
            // リダイレクト先がある場合はそちらへ
            if(isset($_SESSION['redirect_to'])) {
                $redirect = $_SESSION['redirect_to'];
                unset($_SESSION['redirect_to']);
                header("Location: $redirect");
            } else {
                header('Location: index.php');
            }
        }
        exit();
    } else {
        // 認証失敗: エラーメッセージを設定
        $error_message = "メールアドレスまたはパスワードが正しくありません。";
    }
}

// ヘッダーテンプレート読み込み
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
                    
                    <!-- デモ用のログイン情報を表示 -->
                    <div class="alert alert-info">
                        <h6>デモ用アカウント:</h6>
                        <strong>管理者:</strong><br>
                        メール: admin@example.com<br>
                        パスワード: admin123<br><br>
                        <strong>一般ユーザー:</strong><br>
                        メール: user@example.com<br>
                        パスワード: user123
                    </div>
                    
                    <!-- ログインフォーム -->
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

<?php
// フッターテンプレート読み込み
include_once "templates/footer.php";
?>