<?php
/**
 * 注文クラス
 * 
 * 注文情報の管理と操作を行うクラス
 * 在庫管理機能を含む（トランザクション修正版）
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Order {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "orders";
    
    // プロパティ
    public $id;
    public $user_id;
    public $total_amount;
    public $shipping_address;
    public $payment_method;
    public $status;
    public $created;
    
    /**
     * コンストラクタ
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 注文作成（在庫更新機能付き・トランザクション修正版）
     * 
     * @return int|false 作成成功時は注文ID、失敗時はfalse
     */
    public function create() {
        // カートから合計金額を計算
        $cart = new Cart($this->conn);
        $product = new Product($this->conn);
        $items = $cart->getItems($this->user_id);
        $total = 0;
        
        // 在庫チェックと計算
        $items_data = [];
        while($row = $items->fetch(PDO::FETCH_ASSOC)) {
            // 受注生産商品かどうかチェック
            $preorder_info = $product->getPreorderInfo($row['product_id']);
            
            // 受注生産商品でない場合のみ在庫確認
            if(!$preorder_info['is_preorder']) {
                $stock_info = $product->checkStock($row['product_id'], $row['variation_id']);
                
                if (!$stock_info['is_available'] || $stock_info['stock'] < $row['quantity']) {
                    throw new Exception('在庫が不足している商品があります: ' . $row['name']);
                }
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
        
        // トランザクション開始（必ず一度だけ開始）
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
                
                // 各商品の在庫を減らす（受注生産商品以外）
                foreach($items_data as $item) {
                    // バリエーションがある場合、価格を調整
                    $item_price = $item['price'];
                    if(isset($item['price_adjustment'])) {
                        $item_price += $item['price_adjustment'];
                    }
                    
                    // 注文アイテムとして追加
                    $this->addOrderItem($order_id, $item['product_id'], $item['quantity'], $item_price, $item['variation_id']);
                    
                    // 受注生産商品でない場合のみ在庫を減らす
                    $preorder_info = $product->getPreorderInfo($item['product_id']);
                    if(!$preorder_info['is_preorder']) {
                        // updateStockメソッドが独自のトランザクションを開始しないよう確認する必要があります
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
     * トランザクションなしで在庫を更新
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
     * 注文アイテム追加
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
     * 注文情報取得
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
     * 注文アイテム取得
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
     * 注文状態更新
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
     * 注文キャンセル時の在庫復元
     * 
     * @param int $order_id 注文ID
     * @return boolean 復元成功ならtrue
     */
    public function restoreStockOnCancel($order_id) {
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
            return false;
        }
    }
    
    /**
     * 注文数取得（管理パネル用）
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
     * 最近の注文取得（管理パネル用）
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
     * ユーザーの注文履歴取得
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
     * 管理者用の全注文取得
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
     * 売上統計取得
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
}
?>