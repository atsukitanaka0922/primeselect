<?php
/**
 * profile.php - ユーザープロフィールページ
 * 
 * ログインユーザーのプロフィール情報表示と更新機能を提供します。
 * 基本情報の更新とパスワード変更の両方に対応しています。
 * 
 * 機能:
 * - プロフィール情報（ユーザー名、メールアドレス）の表示と更新
 * - パスワード変更
 * - プロフィール更新成功/失敗メッセージの表示
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "config/database.php";  // データベース接続情報
include_once "classes/User.php";     // ユーザークラス

// 未ログインチェック - ログインしていない場合はログインページへリダイレクト
if(!isset($_SESSION['user_id'])) {
    // プロフィールページのURLをセッションに保存（ログイン後に戻ってこれるように）
    $_SESSION['redirect_to'] = 'profile.php';
    header('Location: login.php');
    exit();
}

// データベース接続の取得
$database = new Database();
$db = $database->getConnection();

// ユーザーオブジェクトの作成と初期化
$user = new User($db);
$user->id = $_SESSION['user_id'];  // ログインユーザーのIDをセット
$user->getUser();  // ユーザー情報を取得

// プロフィール更新処理
if(isset($_POST['update_profile'])) {
    // フォームからの入力値を取得
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    
    // プロフィール更新実行
    if($user->updateProfile()) {
        // 更新成功時: セッションのユーザー名を更新し、成功メッセージを表示
        $_SESSION['username'] = $user->username;
        $success_message = "プロフィールを更新しました。";
    } else {
        // 更新失敗時: エラーメッセージを表示
        $error_message = "プロフィールの更新に失敗しました。";
    }
}

// パスワード変更処理
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 新パスワードと確認用パスワードの一致確認
    if($new_password !== $confirm_password) {
        $password_error = "新しいパスワードと確認用パスワードが一致しません。";
    } else {
        // パスワード更新実行
        if($user->updatePassword($current_password, $new_password)) {
            // 更新成功時: 成功メッセージを表示
            $password_success = "パスワードを変更しました。";
        } else {
            // 更新失敗時: エラーメッセージを表示
            $password_error = "現在のパスワードが正しくありません。";
        }
    }
}

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>マイプロフィール</h2>
    
    <div class="row">
        <!-- サイドバーメニュー -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="profile.php" class="list-group-item list-group-item-action active">プロフィール</a>
                <a href="orders.php" class="list-group-item list-group-item-action">注文履歴</a>
                <a href="wishlist.php" class="list-group-item list-group-item-action">お気に入り</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">ログアウト</a>
            </div>
        </div>
        
        <!-- メインコンテンツ -->
        <div class="col-md-9">
            <!-- プロフィール情報カード -->
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
            
            <!-- パスワード変更カード -->
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

<?php 
// フッターテンプレートのインクルード
include_once "templates/footer.php"; 
?>