<?php
/**
 * 管理者用サイドバーテンプレート
 * 
 * 管理者メニューを表示します。
 */

// セッションチェック - 管理者じゃない場合は非表示
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    return;
}
?>
<!-- 管理者サイドバー -->
<div class="list-group">
    <a href="index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> ダッシュボード
    </a>
    <a href="products.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
        <i class="fas fa-box"></i> 商品管理
    </a>
    <a href="categories.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i class="fas fa-tags"></i> カテゴリ管理
    </a>
    <a href="orders.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i> 注文管理
    </a>
    <a href="preorders.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'preorders.php' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-alt"></i> 予約注文管理
    </a>
    <a href="stock_management.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'stock_management.php' ? 'active' : ''; ?>">
        <i class="fas fa-warehouse"></i> 在庫管理
    </a>
    <a href="users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> ユーザー管理
    </a>
    <a href="reviews.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
        <i class="fas fa-star"></i> レビュー管理
    </a>
    <a href="reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> レポート
    </a>
    <div class="dropdown-divider"></div>
    <a href="../index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-home"></i> サイトに戻る
    </a>
    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt"></i> ログアウト
    </a>
</div>