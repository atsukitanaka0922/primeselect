<?php
/**
 * レポート管理ページ（管理者用）
 * 
 * @author Prime Select Team
 * @version 1.0
 */

session_start();
include_once "../config/database.php";
include_once "../classes/Order.php";
include_once "../classes/Product.php";
include_once "../classes/User.php";

// 管理者権限チェック
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order = new Order($db);
$product = new Product($db);
$user = new User($db);

// 期間設定
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

include_once "templates/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php include_once "templates/sidebar.php"; ?>
        </div>
        <div class="col-md-10">
            <h2 class="mt-4">レポート</h2>
            
            <!-- 期間選択 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>レポート期間設定</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row align-items-end">
                        <div class="col-md-3">
                            <label for="period">期間タイプ</label>
                            <select class="form-control" name="period" id="period">
                                <option value="day" <?php echo $period == 'day' ? 'selected' : ''; ?>>日別</option>
                                <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>月別</option>
                                <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>年別</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date">開始日</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date">終了日</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">更新</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 売上統計 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">総売上</h5>
                            <h3 class="text-success">
                                <?php
                                $sales_query = "SELECT SUM(total_amount) as total_sales 
                                              FROM orders 
                                              WHERE created BETWEEN ? AND ? 
                                              AND status NOT IN ('cancelled')";
                                $sales_stmt = $db->prepare($sales_query);
                                $sales_stmt->bindParam(1, $start_date);
                                $sales_stmt->bindParam(2, $end_date);
                                $sales_stmt->execute();
                                $sales_result = $sales_stmt->fetch(PDO::FETCH_ASSOC);
                                echo '¥' . number_format($sales_result['total_sales'] ?? 0);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">注文数</h5>
                            <h3 class="text-primary">
                                <?php
                                $orders_query = "SELECT COUNT(*) as total_orders 
                                               FROM orders 
                                               WHERE created BETWEEN ? AND ? 
                                               AND status NOT IN ('cancelled')";
                                $orders_stmt = $db->prepare($orders_query);
                                $orders_stmt->bindParam(1, $start_date);
                                $orders_stmt->bindParam(2, $end_date);
                                $orders_stmt->execute();
                                $orders_result = $orders_stmt->fetch(PDO::FETCH_ASSOC);
                                echo $orders_result['total_orders'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">平均注文金額</h5>
                            <h3 class="text-info">
                                <?php
                                $avg_query = "SELECT AVG(total_amount) as avg_order 
                                            FROM orders 
                                            WHERE created BETWEEN ? AND ? 
                                            AND status NOT IN ('cancelled')";
                                $avg_stmt = $db->prepare($avg_query);
                                $avg_stmt->bindParam(1, $start_date);
                                $avg_stmt->bindParam(2, $end_date);
                                $avg_stmt->execute();
                                $avg_result = $avg_stmt->fetch(PDO::FETCH_ASSOC);
                                echo '¥' . number_format($avg_result['avg_order'] ?? 0);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">新規ユーザー数</h5>
                            <h3 class="text-warning">
                                <?php
                                $new_users_query = "SELECT COUNT(*) as new_users 
                                                  FROM users 
                                                  WHERE created BETWEEN ? AND ?";
                                $new_users_stmt = $db->prepare($new_users_query);
                                $new_users_stmt->bindParam(1, $start_date);
                                $new_users_stmt->bindParam(2, $end_date);
                                $new_users_stmt->execute();
                                $new_users_result = $new_users_stmt->fetch(PDO::FETCH_ASSOC);
                                echo $new_users_result['new_users'];
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 売上推移グラフ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>売上推移</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 400px; width: 100%;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- 人気商品ランキング -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>人気商品ランキング（売上数量）</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>順位</th>
                                            <th>商品名</th>
                                            <th>売上数量</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $popular_query = "SELECT p.name, SUM(oi.quantity) as total_quantity 
                                                        FROM order_items oi 
                                                        JOIN orders o ON oi.order_id = o.id 
                                                        JOIN products p ON oi.product_id = p.id 
                                                        WHERE o.created BETWEEN ? AND ? 
                                                        AND o.status NOT IN ('cancelled')
                                                        GROUP BY oi.product_id 
                                                        ORDER BY total_quantity DESC 
                                                        LIMIT 10";
                                        $popular_stmt = $db->prepare($popular_query);
                                        $popular_stmt->bindParam(1, $start_date);
                                        $popular_stmt->bindParam(2, $end_date);
                                        $popular_stmt->execute();
                                        
                                        $rank = 1;
                                        while($row = $popular_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            ?>
                                            <tr>
                                                <td><?php echo $rank++; ?></td>
                                                <td><?php echo $row['name']; ?></td>
                                                <td><?php echo $row['total_quantity']; ?>個</td>
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
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>人気商品ランキング（売上金額）</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>順位</th>
                                            <th>商品名</th>
                                            <th>売上金額</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $revenue_query = "SELECT p.name, SUM(oi.price * oi.quantity) as total_revenue 
                                                        FROM order_items oi 
                                                        JOIN orders o ON oi.order_id = o.id 
                                                        JOIN products p ON oi.product_id = p.id 
                                                        WHERE o.created BETWEEN ? AND ? 
                                                        AND o.status NOT IN ('cancelled')
                                                        GROUP BY oi.product_id 
                                                        ORDER BY total_revenue DESC 
                                                        LIMIT 10";
                                        $revenue_stmt = $db->prepare($revenue_query);
                                        $revenue_stmt->bindParam(1, $start_date);
                                        $revenue_stmt->bindParam(2, $end_date);
                                        $revenue_stmt->execute();
                                        
                                        $rank = 1;
                                        while($row = $revenue_stmt->fetch(PDO::FETCH_ASSOC)) {
                                            ?>
                                            <tr>
                                                <td><?php echo $rank++; ?></td>
                                                <td><?php echo $row['name']; ?></td>
                                                <td>¥<?php echo number_format($row['total_revenue']); ?></td>
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
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 売上推移グラフの作成
<?php
// 売上推移データの取得
switch($period) {
    case 'day':
        $format = '%Y-%m-%d';
        break;
    case 'year':
        $format = '%Y';
        break;
    default:
        $format = '%Y-%m';
}

$chart_query = "SELECT DATE_FORMAT(created, ?) as period, 
                      SUM(total_amount) as total_sales 
               FROM orders 
               WHERE created BETWEEN ? AND ? 
               AND status NOT IN ('cancelled')
               GROUP BY period 
               ORDER BY period";
$chart_stmt = $db->prepare($chart_query);
$chart_stmt->bindParam(1, $format);
$chart_stmt->bindParam(2, $start_date);
$chart_stmt->bindParam(3, $end_date);
$chart_stmt->execute();

$chart_data = [];
$chart_labels = [];
while($row = $chart_stmt->fetch(PDO::FETCH_ASSOC)) {
    $chart_labels[] = $row['period'];
    $chart_data[] = floatval($row['total_sales']);
}
?>

const ctx = document.getElementById('salesChart').getContext('2d');

// 既存のグラフを破棄
if(window.salesChart instanceof Chart) {
    window.salesChart.destroy();
}

window.salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: '売上金額 (¥)',
            data: <?php echo json_encode($chart_data); ?>,
            borderColor: 'rgba(75, 192, 192, 1)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1000
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value, index, values) {
                        return '¥' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '売上: ¥' + context.raw.toLocaleString();
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// ウィンドウリサイズ時の対応
window.addEventListener('resize', function() {
    if(window.salesChart) {
        window.salesChart.resize();
    }
});
</script>

<?php include_once "templates/footer.php"; ?>