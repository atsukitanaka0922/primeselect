<?php
session_start();
include_once "config/database.php";
include_once "classes/Order.php";

// 未ログインならログインページへ
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if(!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);
$order->read($order_id);

// 自分の注文かどうか確認
if($order->user_id != $_SESSION['user_id']) {
    header('Location: orders.php');
    exit();
}

// 注文ステータスが保留中または処理中の場合のみキャンセル可能
if($order->status == 'pending' || $order->status == 'processing') {
    // キャンセルステータスに更新（この例では削除）
    if($order->updateStatus($order_id, 'cancelled')) {
        $_SESSION['success_message'] = "注文を正常にキャンセルしました。";
    } else {
        $_SESSION['error_message'] = "注文のキャンセルに失敗しました。";
    }
} else {
    $_SESSION['error_message'] = "この注文はキャンセルできません。";
}

// 注文一覧ページにリダイレクト
header('Location: orders.php');
exit();
?>