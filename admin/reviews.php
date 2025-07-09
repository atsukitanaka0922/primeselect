<?php
/**
 * reviews.php - レビュー管理ページ（管理者用）
 * 
 * 商品レビューの一覧表示、詳細確認、削除などの管理機能を提供します。
 * レビュー統計情報も表示します。
 * 
 * 主な機能:
 * - レビュー一覧の表示
 * - レビューの詳細表示（モーダル）
 * - レビューの削除
 * - レビュー統計情報の表示
 * 
 * @package PrimeSelect
 * @subpackage Admin
 * @author Prime Select Team
 * @version 1.1
 */

// セッション開始
session_start();

// 必要なファイルのインクルード
include_once "../config/database.php";
include_once "../classes/Review.php";

// 管理者権限チェック - 権限がない場合はログインページにリダイレクト
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

// データベース接続
$database = new Database();
$db = $database->getConnection();

// Review クラスのインスタンス化
$review = new Review($db);

/**
 * レビュー削除処理
 * GETリクエストでdeleteパラメータとidが指定された場合に実行
 */
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $review_id = $_GET['id'];
    
    // レビューを削除
    $query = "DELETE FROM reviews WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $review_id);
    
    if($stmt->execute()) {
        $success_message = "レビューを削除しました。";
    } else {
        $error_message = "レビューの削除に失敗しました。";
    }
}

// ヘッダーテンプレートのインクルード
include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <!-- サイドバー -->
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        
        <!-- メインコンテンツ -->
        <div class="col-md-10">
            <h2 class="mt-4">レビュー管理</h2>
            
            <!-- 成功メッセージ表示 -->
            <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <!-- エラーメッセージ表示 -->
            <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- レビュー統計カード -->
            <div class="row mb-4">
                <!-- 総レビュー数 -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総レビュー数</h5>
                            <h3 class="text-primary">
                                <?php
                                // 総レビュー数を取得
                                $count_query = "SELECT COUNT(*) as count FROM reviews";
                                $count_stmt = $db->prepare($count_query);
                                $count_stmt->execute();
                                $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
                                echo $count_result['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                
                <!-- 平均評価 -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">平均評価</h5>
                            <h3 class="text-success">
                                <?php
                                // 平均評価を取得
                                $avg_query = "SELECT AVG(rating) as avg_rating FROM reviews";
                                $avg_stmt = $db->prepare($avg_query);
                                $avg_stmt->execute();
                                $avg_result = $avg_stmt->fetch(PDO::FETCH_ASSOC);
                                echo round($avg_result['avg_rating'], 1);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                
                <!-- 5つ星レビュー数 -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">5つ星レビュー</h5>
                            <h3 class="text-warning">
                                <?php
                                // 5つ星レビュー数を取得
                                $five_star_query = "SELECT COUNT(*) as count FROM reviews WHERE rating = 5";
                                $five_star_stmt = $db->prepare($five_star_query);
                                $five_star_stmt->execute();
                                $five_star_result = $five_star_stmt->fetch(PDO::FETCH_ASSOC);
                                echo $five_star_result['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                
                <!-- 1つ星レビュー数 -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">1つ星レビュー</h5>
                            <h3 class="text-danger">
                                <?php
                                // 1つ星レビュー数を取得
                                $one_star_query = "SELECT COUNT(*) as count FROM reviews WHERE rating = 1";
                                $one_star_stmt = $db->prepare($one_star_query);
                                $one_star_stmt->execute();
                                $one_star_result = $one_star_stmt->fetch(PDO::FETCH_ASSOC);
                                echo $one_star_result['count'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- レビュー一覧テーブル -->
            <div class="card">
                <div class="card-header">
                    <h5>レビュー一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>商品</th>
                                    <th>ユーザー</th>
                                    <th>評価</th>
                                    <th>コメント</th>
                                    <th>投稿日</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // 全レビューを取得（商品名、ユーザー名などの関連情報も結合）
                                $query = "SELECT r.*, p.name as product_name, p.image, u.username 
                                         FROM reviews r 
                                         LEFT JOIN products p ON r.product_id = p.id 
                                         LEFT JOIN users u ON r.user_id = u.id 
                                         ORDER BY r.created DESC";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    // データのサニタイズ（XSS対策）
                                    $comment = htmlspecialchars($row['comment']);
                                    $username = htmlspecialchars($row['username']);
                                    $product_name = htmlspecialchars($row['product_name']);
                                    $date = date('Y-m-d H:i', strtotime($row['created']));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/images/<?php echo $row['image']; ?>" 
                                                     alt="<?php echo $product_name; ?>" width="40" class="mr-2">
                                                <span><?php echo $product_name; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $username; ?></td>
                                        <td>
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if($i <= $row['rating']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <span class="ml-1">(<?php echo $row['rating']; ?>)</span>
                                        </td>
                                        <td>
                                            <div style="max-width: 200px;">
                                                <?php echo substr($comment, 0, 100); ?>
                                                <?php if(strlen($comment) > 100): ?>...<?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($row['created'])); ?></td>
                                        <td>
                                            <!-- 詳細表示ボタン（モーダル表示） -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-info review-detail-btn" 
                                                    data-toggle="modal" 
                                                    data-target="#reviewModal" 
                                                    data-rating="<?php echo $row['rating']; ?>"
                                                    data-comment="<?php echo $comment; ?>"
                                                    data-username="<?php echo $username; ?>"
                                                    data-product="<?php echo $product_name; ?>"
                                                    data-date="<?php echo $date; ?>">
                                                詳細
                                            </button>
                                            
                                            <!-- 削除ボタン -->
                                            <a href="reviews.php?delete=1&id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('このレビューを削除しますか？')">削除</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- レビュー詳細モーダル -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">レビュー詳細</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>商品名:</strong>
                        <p id="modal-product" class="text-muted mb-3"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>投稿者:</strong>
                        <p id="modal-username" class="text-muted mb-3"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>評価:</strong>
                        <div id="modal-rating" class="mb-3"></div>
                    </div>
                    <div class="col-md-6">
                        <strong>投稿日:</strong>
                        <p id="modal-date" class="text-muted mb-3"></p>
                    </div>
                </div>
                <div class="form-group">
                    <strong>コメント:</strong>
                    <p id="modal-comment" class="mt-2 p-3 bg-light rounded"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery と Bootstrap のJavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// DOM読み込み完了時の処理
$(document).ready(function() {
    console.log('レビュー管理ページが読み込まれました');
    console.log('レビュー詳細ボタンの数:', $('.review-detail-btn').length);
    
    // レビュー詳細モーダルのイベント処理
    $('#reviewModal').on('show.bs.modal', function (event) {
        console.log('レビュー詳細モーダルが開かれました');
        
        // クリックされたボタンからデータ属性を取得
        var button = $(event.relatedTarget);
        var rating = button.attr('data-rating');
        var comment = button.attr('data-comment');
        var username = button.attr('data-username');
        var product = button.attr('data-product');
        var date = button.attr('data-date');
        
        console.log('取得したレビューデータ:', {
            rating: rating,
            comment: comment ? comment.substring(0, 50) + '...' : 'なし',
            username: username,
            product: product,
            date: date
        });
        
        // モーダルに取得したデータを設定
        var modal = $(this);
        modal.find('#modal-product').text(product || '商品名取得エラー');
        modal.find('#modal-username').text(username || 'ユーザー名取得エラー');
        modal.find('#modal-comment').text(comment || 'コメントなし');
        modal.find('#modal-date').text(date || '日付取得エラー');
        
        // 星評価の表示設定
        var stars = '';
        var ratingNum = parseInt(rating) || 0;
        for(var i = 1; i <= 5; i++) {
            if(i <= ratingNum) {
                stars += '<i class="fas fa-star text-warning"></i>';
            } else {
                stars += '<i class="far fa-star text-warning"></i>';
            }
        }
        stars += ' <span class="ml-1">(' + ratingNum + '/5)</span>';
        modal.find('#modal-rating').html(stars);
        
        console.log('モーダルに設定された値を確認:', {
            product: modal.find('#modal-product').text(),
            username: modal.find('#modal-username').text(),
            rating: modal.find('#modal-rating').html(),
            comment: modal.find('#modal-comment').text().substring(0, 50) + '...'
        });
    });
    
    // モーダルが完全に表示された後のイベント
    $('#reviewModal').on('shown.bs.modal', function (event) {
        console.log('レビューモーダルの表示が完了しました');
    });
    
    // レビュー詳細ボタンクリック時のデバッグログ
    $('.review-detail-btn').on('click', function(e) {
        console.log('レビュー詳細ボタンがクリックされました');
        console.log('ボタンのdata属性:', this.dataset);
        console.log('モーダルターゲット:', $(this).attr('data-target'));
    });
    
    // ページ読み込み後のチェック
    setTimeout(function() {
        console.log('Bootstrap modal関数の存在確認:', typeof $('#reviewModal').modal);
        console.log('モーダル要素の存在確認:', $('#reviewModal').length);
        
        // 各ボタンのdata属性をチェック
        $('.review-detail-btn').each(function(index) {
            console.log('ボタン ' + (index + 1) + ' のdata属性:', {
                rating: $(this).attr('data-rating'),
                hasComment: !!$(this).attr('data-comment'),
                hasUsername: !!$(this).attr('data-username'),
                hasProduct: !!$(this).attr('data-product'),
                hasDate: !!$(this).attr('data-date')
            });
        });
    }, 1000);
});
</script>

<?php include_once "templates/footer.php"; ?>