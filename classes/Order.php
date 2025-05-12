<?php
/**
 * 注文クラス
 * 
 * 注文情報の管理と操作を行うクラス
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
     * 注文作成
     * 
     * @return int|false 作成成功時は注文ID、失敗時はfalse
     */
    public function create() {
        // カートから合計金額を計算
        $cart = new Cart($this->conn);
        $items = $cart->getItems($this->user_id);
        $total = 0;
        
        while($row = $items->fetch(PDO::FETCH_ASSOC)) {
            // バリエーションがある場合、価格を調整
            $item_price = $row['price'];
            if(isset($row['price_adjustment'])) {
                $item_price += $row['price_adjustment'];
            }
            
            $total += $item_price * $row['quantity'];
        }
        
        if($total == 0) {
            return false;  // カートが空
        }
        
        $this->total_amount = $total;
        
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
            return $this->conn->lastInsertId();
        }
        
        return false;
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
}
?>