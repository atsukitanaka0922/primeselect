<?php
/**
 * logout.php - ログアウト処理ページ
 * 
 * ユーザーのログアウト処理を行い、セッションを破棄します。
 * 処理後はログインページにリダイレクトします。
 * 
 * 機能:
 * - セッション変数の削除
 * - セッションクッキーの削除
 * - セッションの破棄
 * - ログインページへのリダイレクト
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始（セッション変数にアクセスするため）
session_start();

// セッション変数をすべて削除（ユーザーID、ユーザー名などの情報を削除）
$_SESSION = array();

// セッションクッキーを削除
// Note: これはクライアント側のセッションクッキーを削除するために重要
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// セッションを破棄
session_destroy();

// ログインページにリダイレクト
header('Location: login.php');
exit();
?>