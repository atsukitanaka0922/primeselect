<?php
class Cart {
    private $conn;
    private $table_name = "cart";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // カートに商品を追加
    public function addItem($user_id, $product_id, $quantity = 1) {
        // 既存のカートアイテムをチェック
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $product_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // 既に存在する場合は数量を更新
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $quantity;
            
            $query = "UPDATE " . $this->table_name . " SET quantity = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $new_quantity);
            $stmt->bindParam(2, $row['id']);
            if($stmt->execute()) {
                return true;
            }
        } else {
            // 新規アイテムを追加
            $query = "INSERT INTO " . $this->table_name . " SET user_id = ?, product_id = ?, quantity = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $product_id);
            $stmt->bindParam(3, $quantity);
            if($stmt->execute()) {
                return true;
            }
        }
        return false;
    }
    
    // カートの中身を取得
    public function getItems($user_id) {
        $query = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image 
                FROM " . $this->table_name . " c 
                LEFT JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }
    
    // カートアイテム削除
    public function removeItem($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
    
    // カート数量更新
    public function updateQuantity($id, $quantity) {
        $query = "UPDATE " . $this->table_name . " SET quantity = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $quantity);
        $stmt->bindParam(2, $id);
        return $stmt->execute();
    }
    
    // カート内全アイテム削除
    public function clear($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        return $stmt->execute();
    }
}
?>