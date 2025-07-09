<?php
/**
 * contact.php - お問い合わせページ
 * 
 * ユーザーからのお問い合わせを受け付けるページです。
 * メッセージフォームの提供と送信処理を行います。
 * 
 * 機能:
 * - お問い合わせフォームの表示
 * - メッセージ送信処理
 * - 注文ID関連のお問い合わせ対応
 * - ログイン情報の自動入力
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();
include_once "config/database.php";

// お問い合わせ送信処理
if(isset($_POST['send_message'])) {
    // フォームからの入力値を取得
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // メール送信処理（実際には適切なPHPMailerなどで実装）
    // 注: このサンプルコードでは実際のメール送信は行わず、成功としています
    $to = "info@example.com";
    $message_body = "名前: {$name}\n";
    $message_body .= "メール: {$email}\n";
    $message_body .= "件名: {$subject}\n\n";
    $message_body .= "メッセージ:\n{$message}";
    
    // デモ用に送信成功としておく
    // 実際の実装では、mail()関数やPHPMailerなどのライブラリを使用して
    // 正しくメール送信し、その結果に基づいて$successを設定する
    $success = true;
    
    if($success) {
        $success_message = "お問い合わせを送信しました。担当者からの返信をお待ちください。";
    } else {
        $error_message = "送信に失敗しました。後ほど再度お試しください。";
    }
}

// 注文IDが渡された場合は件名に設定（注文に関する問い合わせを簡単にするため）
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$default_subject = $order_id ? "注文 #{$order_id} について" : "";

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container mt-5">
    <h2>お問い合わせ</h2>
    
    <div class="row">
        <!-- お問い合わせフォーム -->
        <div class="col-md-8">
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">メッセージを送信</div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="name">お名前 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                value="<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">メールアドレス <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required
                                value="<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">件名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="subject" name="subject" required
                                value="<?php echo $default_subject; ?>">
                        </div>
                        <div class="form-group">
                            <label for="message">メッセージ <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="btn btn-primary">送信する</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- お問い合わせ先情報 -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">お問い合わせ先</div>
                <div class="card-body">
                    <h5>ECサイト</h5>
                    <address>
                        〒123-4567<br>
                        東京都渋谷区〇〇1-2-3<br>
                        <i class="fas fa-phone"></i> 03-1234-5678<br>
                        <i class="fas fa-envelope"></i> info@example.com
                    </address>
                    <hr>
                    <h5>営業時間</h5>
                    <p>平日 10:00 - 18:00</p>
                    <p>土日祝日はお休みです。メールは24時間受け付けております。</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// フッターテンプレートのインクルード
include_once "templates/footer.php"; 
?>