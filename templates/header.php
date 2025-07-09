<?php
/**
 * templates/header.php - ヘッダーテンプレート
 * 
 * すべてのページで使用される共通のヘッダー部分を定義します。
 * HTML開始タグ、head要素、ナビゲーションバーなどが含まれます。
 * 
 * 機能：
 * - HTMLドキュメント開始部分
 * - メタタグ（文字コード、ビューポートなど）
 * - CSS読み込み（Bootstrap、Font Awesome、カスタムCSS）
 * - サイト名とロゴ表示
 * - ナビゲーションメニュー
 * - カテゴリドロップダウン
 * - 検索フォーム
 * - ユーザーメニュー（ログイン状態で変化）
 * - カート内アイテム数表示
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Select</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- カスタムCSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <!-- ナビゲーションバー -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <!-- ロゴとサイト名 -->
                <a class="navbar-brand" href="index.php">
                    <img src="assets/images/logo.jpg" alt="Prime Select" height="30" class="d-inline-block align-top mr-2">
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <!-- メインナビゲーション -->
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">ホーム</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="shop.php">商品一覧</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                カテゴリ
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <?php
                                // カテゴリ一覧取得
                                // データベース接続とCategoryクラスの読み込みが確実に行われるようにする
                                if(!isset($db) || !$db) {
                                    include_once "config/database.php";
                                    $database = new Database();
                                    $db = $database->getConnection();
                                }
                                
                                if(!class_exists('Category')) {
                                    include_once "classes/Category.php";
                                }
                                
                                $category = new Category($db);
                                $stmt = $category->read();
                                
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<a class="dropdown-item" href="category.php?id=' . $row['id'] . '">' . $row['name'] . '</a>';
                                }
                                ?>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php">お問い合わせ</a>
                        </li>
                    </ul>
                    
                    <!-- 検索フォーム -->
                    <form class="form-inline my-2 my-lg-0 mr-3" action="search.php" method="get">
                        <input class="form-control mr-sm-2" type="search" name="keyword" placeholder="商品検索" aria-label="Search">
                        <button class="btn btn-outline-light my-2 my-sm-0" type="submit">検索</button>
                    </form>
                    
                    <!-- ユーザーメニュー -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> カート
                                <?php
                                // カート内アイテム数表示
                                if(isset($_SESSION['user_id']) || isset($_SESSION['temp_user_id'])) {
                                    if(isset($db)) {
                                        if(!class_exists('Cart')) {
                                            include_once "classes/Cart.php";
                                        }
                                        
                                        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['temp_user_id'];
                                        $cart = new Cart($db);
                                        $stmt = $cart->getItems($user_id);
                                        $count = $stmt->rowCount();
                                        
                                        if($count > 0) {
                                            echo '<span class="badge badge-pill badge-danger">' . $count . '</span>';
                                        }
                                    }
                                }
                                ?>
                            </a>
                        </li>
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- ログイン済みユーザーメニュー -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <i class="fas fa-user"></i> マイページ
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" href="profile.php">プロフィール</a>
                                <a class="dropdown-item" href="orders.php">注文履歴</a>
                                <a class="dropdown-item" href="wishlist.php">お気に入り</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">ログアウト</a>
                            </div>
                        </li>
                        <?php else: ?>
                        <!-- 未ログインユーザーメニュー -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">ログイン</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">会員登録</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main>
    <!-- ここからページコンテンツ開始 -->