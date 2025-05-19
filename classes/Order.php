<?php
/**
 * Order.php - 注文管理クラス
 * 
 * ECサイトの注文処理と管理を担当するクラスです。
 * 注文の作成、ステータス更新、取得機能などを提供します。
 * トランザクション処理を用いて、注文と在庫操作の整合性を保証します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.4
 */
class Order {
    // データベース接続とテーブル名
    private $conn;                   // データベース接続オブジェクト
    private $table_name = "orders";  // 注文テーブル名
    
    // プロパティ
    public $id;                      // 注文ID
    public $user_id;                 // ユーザーID
    public $total_amount;            // 合計金額
    public $shipping_address;        // 配送先住所
    public $payment_method;          // 支払い方法
    public $status;                  // 注文状態
    public $created;                 // 注文日時
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 注文作成メソッド
     * 
     * カートアイテムから注文を作成し、在庫を減少させます。
     * トランザクション処理により、注文と在庫の整合性を保ちます。
     * 
     * @return int|false 作成成功時は注文ID、失敗時はfalse
     */
    public function create() {
        // 必要なクラスの確実な読み込み
        if (!class_exists('Cart')) {
            include_once "classes/Cart.php";
        }
        if (!class_exists('Product')) {
            include_once "classes/Product.php";
        }
        
        $cart = new Cart($this->conn);
        $product = new Product($this->conn);
        $items = $cart->getItems($this->user_id);
        $total = 0;
        
        // 在庫チェックと計算
        $items_data = [];
        $preorder_items = []; // 受注生産商品のリスト
        
        while($row = $items->fetch(PDO::FETCH_ASSOC)) {
            // 受注生産商品かどうかチェック
            $preorder_info = $product->getPreorderInfo($row['product_id']);
            
            // 受注生産商品でない場合のみ在庫確認
            if(!$preorder_info['is_preorder']) {
                $stock_info = $product->checkStock($row['product_id'], $row['variation_id']);
                
                if (!$stock_info['is_available'] || $stock_info['stock'] < $row['quantity']) {
                    throw new Exception('在庫が不足している商品があります: ' . $row['name']);
                }
            } else {
                // 受注生産商品はpreorder_itemsに追加
                $preorder_items[] = array_merge($row, $preorder_info);
            }
            
            // バリエーションがある場合、価格を調整
            $item_price = $row['price'];
            if(isset($row['price_adjustment'])) {
                $item_price += $row['price_adjustment'];
            }
            
            $subtotal = $item_price * $row['quantity'];
            $total += $subtotal;
            
            $items_data[] = $row;
        }
        
        if(empty($items_data)) {
            return false;  // カートが空
        }
        
        $this->total_amount = $total;
        
        // トランザクション開始
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
        }
        
        try {
            // 注文の作成
            $query = "INSERT INTO " . $this->table_name . " 
                    SET user_id = :user_id, 
                        total_amount = :total_amount, 
                        shipping_address = :shipping_address, 
                        payment_method = :payment_method";
            
            $stmt = $this->conn->prepare($query);
            
            // サニタイズ
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
            $this->shipping_address = htmlspecialchars(strip_tags($this->shipping_address));
            $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
            
            // バインド
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":total_amount", $this->total_amount);
            $stmt->bindParam(":shipping_address", $this->shipping_address);
            $stmt->bindParam(":payment_method", $this->payment_method);
            
            if($stmt->execute()) {
                $order_id = $this->conn->lastInsertId();
                
                // 各商品の処理
                foreach($items_data as $item) {
                    // バリエーションがある場合、価格を調整
                    $item_price = $item['price'];
                    if(isset($item['price_adjustment'])) {
                        $item_price += $item['price_adjustment'];
                    }
                    
                    // 注文アイテムとして追加
                    $this->addOrderItem($order_id, $item['product_id'], $item['quantity'], $item_price, $item['variation_id']);
                    
                    // 受注生産商品の場合は予約注文も作成
                    $preorder_info = $product->getPreorderInfo($item['product_id']);
                    if($preorder_info['is_preorder']) {
                        $this->createPreorder($order_id, $item, $preorder_info);
                    } else {
                        // 通常商品は在庫を減らす
                        $this->updateStockWithoutTransaction($product, $item['product_id'], $item['variation_id'], -$item['quantity'], '注文による出庫 #' . $order_id);
                    }
                }
                
                $this->conn->commit();
                return $order_id;
            }
        } catch(Exception $e) {
            // トランザクションがアクティブな場合のみロールバック
            if ($this->conn->inTransaction()) {
                $this->conn->rollback();
            }
            throw $e;
        }
        
        return false;
    }
    
    /**
     * 予約注文作成メソッド
     * 
     * 受注生産商品の予約注文を作成します。
     * 
     * @param int $order_id 注文ID
     * @param array $item カートアイテム
     * @param array $preorder_info 受注生産情報
     * @return boolean 作成成功ならtrue
     */
    private function createPreorder($order_id, $item, $preorder_info) {
        // 配送予定日を計算（受注生産期間を基に）
        $preorder_period = $preorder_info['preorder_period'];
        $estimated_delivery = $this->calculateEstimatedDelivery($preorder_period);
        
        $query = "INSERT INTO preorders 
                  SET user_id = ?, 
                      product_id = ?, 
                      variation_id = ?, 
                      quantity = ?, 
                      estimated_delivery = ?, 
                      status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $item['product_id']);
        $stmt->bindParam(3, $item['variation_id']);
        $stmt->bindParam(4, $item['quantity']);
        $stmt->bindParam(5, $estimated_delivery);
        
        return $stmt->execute();
    }
    
    /**
     * 受注生産期間から配送予定日を計算
     * 
     * 受注生産期間の記述から配送予定日を算出します。
     * 
     * @param string $preorder_period 受注生産期間（例: "約4-6週間"）
     * @return string 配送予定日（Y-m-d形式）
     */
    private function calculateEstimatedDelivery($preorder_period) {
        // 受注生産期間のパターンマッチング
        preg_match('/(\d+)[-〜~]?(\d+)?[週]*/', $preorder_period, $matches);
        
        if(isset($matches[1])) {
            // 最大週数を取得（範囲がある場合は最大値、ない場合は指定値）
            $weeks = isset($matches[2]) ? max($matches[1], $matches[2]) : $matches[1];
            
            // 現在日から指定週数後を計算
            $delivery_date = date('Y-m-d', strtotime("+{$weeks} weeks"));
        } else {
            // パターンにマッチしない場合は4週間後をデフォルト
            $delivery_date = date('Y-m-d', strtotime('+4 weeks'));
        }
        
        return $delivery_date;
    }
    
    /**
     * トランザクションなしで在庫を更新
     * 
     * すでに開始されているトランザクション内で在庫を更新します。
     * 
     * @param Product $product Productオブジェクト
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量
     * @param string $reason 理由
     * @return boolean 更新成功ならtrue
     */
    private function updateStockWithoutTransaction($product, $product_id, $variation_id, $quantity, $reason = '') {
        // 現在の在庫を確認
        $current_stock = $product->checkStock($product_id, $variation_id)['stock'];
        $new_stock = $current_stock + $quantity;
        
        if ($new_stock < 0) {
            throw new Exception('在庫が不足しています');
        }
        
        // 在庫を更新
        if ($variation_id) {
            $query = "UPDATE product_variations SET stock = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $new_stock);
            $stmt->bindParam(2, $variation_id);
        } else {
            $query = "UPDATE products SET stock = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $new_stock);
            $stmt->bindParam(2, $product_id);
        }
        
        $stmt->execute();
        
        // 在庫ログを記録
        $type = $quantity > 0 ? 'in' : ($quantity < 0 ? 'out' : 'adjust');
        
        $query = "INSERT INTO product_stock_logs 
                  SET product_id = ?, variation_id = ?, type = ?, quantity = ?, reason = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->bindParam(2, $variation_id);
        $stmt->bindParam(3, $type);
        $stmt->bindParam(4, abs($quantity));
        $stmt->bindParam(5, $reason);
        $stmt->execute();
        
        return true;
    }
    
    /**
     * 注文アイテム追加メソッド
     * 
     * 注文に商品を追加します。
     * 
     * @param int $order_id 注文ID
     * @param int $product_id 商品ID
     * @param int $quantity 数量
     * @param float $price 価格
     * @param int|null $variation_id バリエーションID
     * @return boolean 追加成功ならtrue
     */
    public function addOrderItem($order_id, $product_id, $quantity, $price, $variation_id = null) {
        $query = "INSERT INTO order_items 
                SET order_id = :order_id, 
                    product_id = :product_id, 
                    quantity = :quantity, 
                    price = :price, 
                    variation_id = :variation_id";
        
        $stmt = $this->conn->prepare($query);
        
        // バインド
        $stmt->bindParam(":order_id", $order_id);
        $stmt->bindParam(":product_id", $product_id);
        $stmt->bindParam(":quantity", $quantity);
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":variation_id", $variation_id);
        
        return $stmt->execute();
    }
    
    /**
     * 注文情報取得メソッド
     * 
     * 注文IDに基づいて注文情報を取得します。
     * 
     * @param int $id 注文ID
     * @return boolean 取得成功ならtrue
     */
    public function read($id) {
        $query = "SELECT o.*, u.username, u.email 
                FROM " . $this->table_name . " o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->total_amount = $row['total_amount'];
            $this->shipping_address = $row['shipping_address'];
            $this->payment_method = $row['payment_method'];
            $this->status = $row['status'];
            $this->created = $row['created'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 注文アイテム取得メソッド
     * 
     * 注文に含まれる商品を取得します。
     * 
     * @param int $order_id 注文ID
     * @return PDOStatement 結果セット
     */
    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.name, p.image, pv.variation_name, pv.variation_value 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                LEFT JOIN product_variations pv ON oi.variation_id = pv.id
                WHERE oi.order_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $order_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 注文状態更新メソッド
     * 
     * 注文のステータスを更新します。
     * 
     * @param int $order_id 注文ID
     * @param string $status 新しいステータス
     * @return boolean 更新成功ならtrue
     */
    public function updateStatus($order_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $order_id);
        
        return $stmt->execute();
    }
    
    /**
     * ユーザーの注文履歴取得メソッド
     * 
     * 指定されたユーザーの注文履歴を取得します。
     * 
     * @param int $user_id ユーザーID
     * @return PDOStatement 結果セット
     */
    public function getUserOrders($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = ? 
                ORDER BY created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 注文数取得メソッド（管理パネル用）
     * 
     * 全注文数を取得します。
     * 
     * @return int 注文数
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * 最近の注文取得メソッド（管理パネル用）
     * 
     * 最近の注文を取得します。
     * 
     * @param int $limit 取得件数
     * @return PDOStatement 結果セット
     */
    public function getRecent($limit = 10) {
        $query = "SELECT o.*, u.username 
                FROM " . $this->table_name . " o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 管理者用の全注文取得メソッド
     * 
     * 全注文情報または特定ステータスの注文を取得します。
     * 
     * @param string $status 注文ステータス（オプション）
     * @return PDOStatement 結果セット
     */
    public function getAllOrders($status = null) {
        if($status) {
            $query = "SELECT o.*, u.username, u.email 
                    FROM " . $this->table_name . " o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    WHERE o.status = ? 
                    ORDER BY o.created DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $status);
        } else {
            $query = "SELECT o.*, u.username, u.email 
                    FROM " . $this->table_name . " o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    ORDER BY o.created DESC";
            
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * 注文キャンセル時の在庫復元メソッド
     * 
     * 注文をキャンセルし、在庫を元に戻します。
     * 
     * @param int $order_id 注文ID
     * @return boolean 復元成功ならtrue
     */
    public function restoreStockOnCancel($order_id) {
        // 確実にProductクラスを読み込み
        if (!class_exists('Product')) {
            // 管理者画面からの場合のパス調整
            if (file_exists('../classes/Product.php')) {
                include_once '../classes/Product.php';
            } elseif (file_exists('classes/Product.php')) {
                include_once 'classes/Product.php';
            } else {
                // 絶対パスでの読み込み（最後の手段）
                $base_path = dirname(__DIR__);
                include_once $base_path . '/classes/Product.php';
            }
        }
        
        $transaction_started = false;
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
            $transaction_started = true;
        }
        
        try {
            // 注文アイテムを取得
            $query = "SELECT * FROM order_items WHERE order_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $order_id);
            $stmt->execute();
            
            $product = new Product($this->conn);
            
            while($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // 受注生産商品かどうかチェック
                $preorder_info = $product->getPreorderInfo($item['product_id']);
                
                // 受注生産商品でない場合のみ在庫を戻す
                if(!$preorder_info['is_preorder']) {
                    $this->updateStockWithoutTransaction($product, $item['product_id'], $item['variation_id'], $item['quantity'], '注文キャンセルによる返在庫 #' . $order_id);
                } else {
                    // 受注生産商品の場合は予約注文をキャンセル
                    $cancel_preorder_query = "UPDATE preorders SET status = 'cancelled' 
                                            WHERE product_id = ? AND user_id = ? AND status != 'cancelled'";
                    $cancel_stmt = $this->conn->prepare($cancel_preorder_query);
                    $cancel_stmt->bindParam(1, $item['product_id']);
                    $cancel_stmt->bindParam(2, $this->user_id);
                    $cancel_stmt->execute();
                }
            }
            
            // 注文ステータスをキャンセルに更新
            $this->updateStatus($order_id, 'cancelled');
            
            if ($transaction_started) {
                $this->conn->commit();
            }
            return true;
        } catch(Exception $e) {
            if ($transaction_started) {
                $this->conn->rollback();
            }
            // エラーログに記録
            error_log("Order cancel error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 売上統計取得メソッド
     * 
     * 期間ごとの売上統計を取得します。
     * 
     * @param string $period 期間（day, month, year）
     * @return PDOStatement 結果セット
     */
    public function getSalesStatistics($period = 'month') {
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
        
        $query = "SELECT DATE_FORMAT(created, ?) as period, 
                         COUNT(*) as order_count, 
                         SUM(total_amount) as total_sales
                  FROM " . $this->table_name . " 
                  WHERE status NOT IN ('cancelled')
                  GROUP BY period 
                  ORDER BY period DESC 
                  LIMIT 12";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $format);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 改善提案:
     * 
     * 1. 注文ステータス変更時の通知機能
     * 2. 注文履歴のエクスポート機能
     * 3. 日付範囲での注文検索機能
     * 4. キャンセル期限の設定
     * 5. 複数配送先対応
     * 6. 注文分割機能
     */
}