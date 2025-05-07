<?php
session_start();

// セッション変数をすべて削除
$_SESSION = array();

// セッションクッキーを削除
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// セッションを破棄
session_destroy();

// ログインページにリダイレクト
header('Location: login.php');
exit();
?>