<?php
/**
 * admin/templates/header.php - 管理者用ヘッダーテンプレート
 * 
 * 管理画面のヘッダー部分を定義します。
 * すべての管理ページで使用される共通ヘッダーです。
 * 
 * 含まれる内容:
 * - HTML開始タグとメタ情報
 * - CSS読み込み（Bootstrap、FontAwesome、カスタムCSS）
 * - 管理パネルヘッダー（赤色バー）
 * - ナビゲーションバー
 * - 現在のページ名表示
 * 
 * セキュリティ機能:
 * - 管理者権限チェック（未認証は自動的にログインページにリダイレクト）
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.1
 */

// 管理者権限チェック（すべての管理者ページで必要）
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Select - 管理パネル</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome - アイコン -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- カスタムCSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    /* 管理画面専用スタイル */
    .admin-header {
        background-color: #dc3545; /* 管理画面は赤色ヘッダーで識別しやすく */
        color: white;
        padding: 10px 0;
    }
    .modal-content {
        border-radius: 0.375rem;
    }
    .form-control-plaintext {
        border: none;
        padding-left: 0;
    }
    </style>
</head>
<body>
    <!-- 管理パネルヘッダー - 赤色バー -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-cog mr-2"></i>
                    <span class="h5 mb-0">管理パネル</span>
                </div>
                <div class="d-flex align-items-center">
                    <!-- ログインユーザー表示 -->
                    <span class="mr-3">ログイン中: <?php echo $_SESSION['username']; ?></span>
                    <!-- サイトに戻るリンク -->
                    <a href="../index.php" class="text-white mr-3" title="サイトに戻る">
                        <i class="fas fa-home"></i>
                    </a>
                    <!-- ログアウトリンク -->
                    <a href="../logout.php" class="text-white" title="ログアウト">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ナビゲーションバー（ユーザー用とは異なるデザイン） -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-tools mr-2"></i>
                Prime Select 管理
            </a>
            
            <!-- 現在のページ名表示 -->
            <div class="navbar-nav ml-auto">
                <span class="navbar-text">
                    <?php
                    // 現在のページ名を表示（ファイル名に基づく）
                    $current_page = basename($_SERVER['PHP_SELF']);
                    switch($current_page) {
                        case 'index.php':
                            echo 'ダッシュボード';
                            break;
                        case 'products.php':
                            echo '商品管理';
                            break;
                        case 'categories.php':
                            echo 'カテゴリ管理';
                            break;
                        case 'orders.php':
                            echo '注文管理';
                            break;
                        case 'preorders.php':
                            echo '予約注文管理';
                            break;
                        case 'stock_management.php':
                            echo '在庫管理';
                            break;
                        case 'users.php':
                            echo 'ユーザー管理';
                            break;
                        case 'reviews.php':
                            echo 'レビュー管理';
                            break;
                        case 'reports.php':
                            echo 'レポート';
                            break;
                        default:
                            echo '管理パネル';
                    }
                    ?>
                </span>
            </div>
        </div>
    </nav>
    
    <!-- メインコンテンツの開始 -->
    <main class="container-fluid mt-3">