<?php
/**
 * admin/templates/sidebar.php - 管理者用サイドバーテンプレート
 * 
 * 管理画面のサイドバー（左側ナビゲーション）を定義します。
 * すべての管理ページで使用される管理メニューを表示します。
 * 
 * 含まれる機能:
 * - 管理者メニュー一覧（ダッシュボード、商品管理、注文管理など）
 * - 現在のページをアクティブ表示
 * - ユーザーサイトに戻るリンク
 * - ログアウトリンク
 * 
 * セキュリティ機能:
 * - 管理者チェック（管理者でない場合は非表示）
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

// セッションチェック - 管理者でない場合は非表示
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    return; // 管理者でなければ何も表示せずに終了
}

// 現在のページ名を取得（アクティブメニュー表示用）
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- 管理者サイドバー -->
<div class="list-group">
    <!-- ダッシュボードリンク -->
    <a href="index.php" class="list-group-item list-group-item-action <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> ダッシュボード
    </a>
    
    <!-- 商品管理リンク -->
    <a href="products.php" class="list-group-item list-group-item-action <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
        <i class="fas fa-box"></i> 商品管理
    </a>
    
    <!-- カテゴリ管理リンク -->
    <a href="categories.php" class="list-group-item list-group-item-action <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
        <i class="fas fa-tags"></i> カテゴリ管理
    </a>
    
    <!-- 注文管理リンク -->
    <a href="orders.php" class="list-group-item list-group-item-action <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i> 注文管理
    </a>
    
    <!-- 予約注文管理リンク - 受注生産商品用 -->
    <a href="preorders.php" class="list-group-item list-group-item-action <?php echo $current_page == 'preorders.php' ? 'active' : ''; ?>">
        <i class="fas fa-calendar-alt"></i> 予約注文管理
    </a>
    
    <!-- 在庫管理リンク -->
    <a href="stock_management.php" class="list-group-item list-group-item-action <?php echo $current_page == 'stock_management.php' ? 'active' : ''; ?>">
        <i class="fas fa-warehouse"></i> 在庫管理
    </a>
    
    <!-- ユーザー管理リンク -->
    <a href="users.php" class="list-group-item list-group-item-action <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> ユーザー管理
    </a>
    
    <!-- レビュー管理リンク -->
    <a href="reviews.php" class="list-group-item list-group-item-action <?php echo $current_page == 'reviews.php' ? 'active' : ''; ?>">
        <i class="fas fa-star"></i> レビュー管理
    </a>
    
    <!-- レポートリンク - 売上統計など -->
    <a href="reports.php" class="list-group-item list-group-item-action <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> レポート
    </a>
    
    <!-- 区切り線 -->
    <div class="dropdown-divider"></div>
    
    <!-- サイトに戻るリンク -->
    <a href="../index.php" class="list-group-item list-group-item-action">
        <i class="fas fa-home"></i> サイトに戻る
    </a>
    
    <!-- ログアウトリンク -->
    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt"></i> ログアウト
    </a>
</div>