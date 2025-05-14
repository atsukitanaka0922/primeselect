<?php
/**
 * admin/reviews.php - 修正版
 */

session_start();
include_once "../config/database.php";
include_once "../classes/Review.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$review = new Review($db);

// レビュー削除処理
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $review_id = $_GET['id'];
    
    $query = "DELETE FROM reviews WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $review_id);
    
    if($stmt->execute()) {
        $_SESSION['success_message'] = "レビューを削除しました。";
    } else {
        $_SESSION['error_message'] = "レビューの削除に失敗しました。";
    }
    
    header('Location: reviews.php');
    exit();
}

include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <h2 class="mt-4">レビュー管理</h2>
            
            <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- レビュー統計 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総レビュー数</h5>
                            <h3 class="text-primary">
                                <?php
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
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">平均評価</h5>
                            <h3 class="text-success">
                                <?php
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
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">5つ星レビュー</h5>
                            <h3 class="text-warning">
                                <?php
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
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">1つ星レビュー</h5>
                            <h3 class="text-danger">
                                <?php
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
            
            <!-- レビュー一覧 -->
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
                                $query = "SELECT r.*, p.name as product_name, p.image, u.username 
                                         FROM reviews r 
                                         LEFT JOIN products p ON r.product_id = p.id 
                                         LEFT JOIN users u ON r.user_id = u.id 
                                         ORDER BY r.created DESC";
                                $stmt = $db->prepare($query);
                                $stmt->execute();
                                
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../assets/images/<?php echo $row['image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($row['product_name']); ?>" width="40" class="mr-2">
                                                <span><?php echo htmlspecialchars($row['product_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
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
                                                <?php echo htmlspecialchars(substr($row['comment'], 0, 100)); ?>
                                                <?php if(strlen($row['comment']) > 100): ?>...<?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($row['created'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info review-detail-btn" 
                                                    data-toggle="modal" 
                                                    data-target="#reviewModal" 
                                                    data-rating="<?php echo $row['rating']; ?>"
                                                    data-comment="<?php echo htmlspecialchars($row['comment']); ?>"
                                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                                    data-product="<?php echo htmlspecialchars($row['product_name']); ?>"
                                                    data-date="<?php echo date('Y-m-d H:i', strtotime($row['created'])); ?>">
                                                詳細
                                            </button>
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
                        <p id="modal-product" class="text-muted"></p>
                    </div>
                    <div class="col-md-6">
                        <strong>投稿者:</strong>
                        <p id="modal-username" class="text-muted"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>評価:</strong>
                        <div id="modal-rating"></div>
                    </div>
                    <div class="col-md-6">
                        <strong>投稿日:</strong>
                        <p id="modal-date" class="text-muted"></p>
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

<script>
$(document).ready(function() {
    // レビュー詳細モーダルの修正
    $('#reviewModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var rating = parseInt(button.data('rating'));
        var comment = button.data('comment');
        var username = button.data('username');
        var product = button.data('product');
        var date = button.data('date');
        
        console.log('Review modal data:', {
            rating: rating,
            comment: comment,
            username: username,
            product: product,
            date: date
        });
        
        var modal = $(this);
        modal.find('#modal-product').text(product || '情報なし');
        modal.find('#modal-username').text(username || '情報なし');
        modal.find('#modal-comment').text(comment || 'コメントなし');
        modal.find('#modal-date').text(date || '情報なし');
        
        // 星を表示
        var stars = '';
        for(var i = 1; i <= 5; i++) {
            if(i <= rating) {
                stars += '<i class="fas fa-star text-warning"></i>';
            } else {
                stars += '<i class="far fa-star text-warning"></i>';
            }
        }
        stars += ' (' + rating + ')';
        modal.find('#modal-rating').html(stars);
    });
});
</script>

<?php include_once "templates/footer.php"; ?>