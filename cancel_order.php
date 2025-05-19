<?php
/**
 * cancel_order.php - 注文キャンセル処理
 * 
 * ユーザーからの注文キャンセル要求を処理し、注文ステータスを更新し、
 * 該当商品の在庫を元に戻します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッション開始
session_start();
include_once "config/database.php";
include_once "classes/Order.php";

// ユーザー認証チェック - 未ログインならログインページへリダイレクト
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 注文IDが指定されているか確認
if(!isset($_GET['id'])) {
    // 注文IDがない場合は注文一覧ページへリダイレクト
    header('Location: orders.php');
    exit();
}

// 注文IDを取得
$order_id = $_GET['id'];

// データベース接続
$database = new Database();
$db = $database->getConnection();

// 注文オブジェクトを初期化
$order = new Order($db);
// 指定IDの注文を読み込み
$order->read($order_id);

// 自分の注文かどうか確認（セキュリティ対策）
if($order->user_id != $_SESSION['user_id']) {
    // 自分の注文でない場合は注文一覧ページへリダイレクト
    header('Location: orders.php');
    exit();
}

// 注文ステータスが保留中または処理中の場合のみキャンセル可能
if($order->status == 'pending' || $order->status == 'processing') {
    // 注文をキャンセルし、在庫を復元
    if($order->updateStatus($order_id, 'cancelled')) {
        // 注文ステータス更新と在庫復元が成功した場合
        $_SESSION['success_message'] = "注文を正常にキャンセルしました。";
    } else {
        // 更新失敗の場合
        $_SESSION['error_message'] = "注文のキャンセルに失敗しました。";
    }
} else {
    // 既に発送済みなど、キャンセル不可の状態の場合
    $_SESSION['error_message'] = "この注文はキャンセルできません。";
}

// 処理完了後、注文一覧ページにリダイレクト
header('Location: orders.php');
exit();

/**
 * 改善提案:
 * 
 * 1. キャンセル理由の選択・入力機能
 * 2. 管理者への通知機能
 * 3. キャンセルポリシーに基づく部分キャンセルや返金計算
 * 4. キャンセル履歴の記録
 * 5. キャンセル条件の詳細チェック（発送日からの経過時間など）
 * 6. 在庫復元処理をトランザクションで保護（既に実装済みのケースあり）
 */