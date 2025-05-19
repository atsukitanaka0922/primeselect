<?php
/**
 * includes/session.php - セッション管理機能
 * 
 * セキュアなセッション管理を提供する共通ファイルです。
 * セッションのセキュリティ設定、開始、チェック、リダイレクト機能を提供します。
 * 
 * 機能:
 * - セキュアなセッションの開始
 * - ログイン状態の確認
 * - 未ログイン時のリダイレクト処理
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

/**
 * セキュアなセッションを開始する関数
 * 
 * セッションのセキュリティを高める設定を行い、セッションを開始します。
 * セッションハイジャック対策として、セッションIDの再生成も行います。
 */
function sec_session_start() {
    // セッション名をセキュアなものに変更
    $session_name = 'secure_session';
    
    // セキュリティ設定
    $secure = true;  // HTTPSの場合はtrue（本番環境では常にtrue推奨）
    $httponly = true; // JavaScriptからのクッキー値へのアクセスを防ぐ
    
    // セッションをクッキーのみで使用するよう設定
    if(ini_set('session.use_only_cookies', 1) === FALSE) {
        // 設定に失敗した場合はエラーページにリダイレクト
        header("Location: ../error.php?err=Could not initiate a safe session");
        exit();
    }
    
    // 既存のクッキーパラメータを取得
    $cookieParams = session_get_cookie_params();
    
    // セッションクッキーのパラメータを設定
    session_set_cookie_params(
        $cookieParams["lifetime"], // ライフタイム
        $cookieParams["path"],     // パス
        $cookieParams["domain"],   // ドメイン
        $secure,                   // セキュア接続のみ
        $httponly                  // HTTPのみ（JavaScriptからアクセス不可）
    );
    
    // セッション名を設定
    session_name($session_name);
    
    // セッションを開始
    session_start();
    
    // セッションIDを再生成（セッションハイジャック対策）
    session_regenerate_id();
}

/**
 * ログイン状態をチェックする関数
 * 
 * ユーザーがログインしているかどうかを確認します。
 * セッション変数に user_id が存在すればログイン済みと判断します。
 * 
 * @return boolean ログインしていればtrue、していなければfalse
 */
function check_login_status() {
    if(isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

/**
 * ログインしていなければログインページにリダイレクトする関数
 * 
 * 未ログイン状態の場合、現在のURLをセッションに保存してからログインページにリダイレクトします。
 * ログイン後に元のページに戻れるようにするための機能です。
 */
function redirect_if_not_logged_in() {
    if(!check_login_status()) {
        // 現在のURLをセッションに保存
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        
        // ログインページにリダイレクト
        header('Location: login.php');
        exit();
    }
}

/**
 * 改善提案:
 * 
 * 1. Cross-Site Request Forgery (CSRF) 対策の追加
 * 2. セッションタイムアウト機能の実装
 * 3. 管理者権限のチェック機能の追加
 * 4. 二段階認証との連携機能
 * 5. ログイン試行回数制限との連携
 */