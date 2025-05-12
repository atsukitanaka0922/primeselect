<?php
/**
 * カートクラス
 * 
 * ショッピングカートの管理と操作を行うクラス
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Cart {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "cart";
    
    /**
     * コンストラクタ
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * カートに商品を追加
     * 
     * @param string $user_id ユーザーID
     * @param int $product_id 商品ID
     * @param int $quantity 数量
     * @param int|null $variation_id バリエーションID
     * @return boolean 追加成功ならtrue
     */
    public function addItem($user_id, $product_id, $quantity = 1, $variation_id = null) {
        // 既存のカートアイテムをチェック
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = ? AND product_id = ?";
        
        // バリエーションIDがある場合は条件に追加
        if($variation_id) {
            $query .= " AND variation_id = ?";
        } else {
            $query .= " AND variation_id IS NULL";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $product_id);
        
        if($variation_id) {
            $stmt->bindParam(3, $variation_id);
        }
        
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
            $query = "INSERT INTO " . $this->table_name . " 
                    SET user_id = ?, product_id = ?, quantity = ?, variation_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $product_id);
            $stmt->bindParam(3, $quantity);
            $stmt->bindParam(4, $variation_id);
            
            if($stmt->execute()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * カートの中身を取得
     * 
     * @param string $user_id ユーザーID
     * @return PDOStatement 結果セット
     */
    public function getItems($user_id) {
        $query = "SELECT c.id, c.product_id, c.quantity, c.variation_id, 
                    p.name, p.price, p.image, 
                    pv.variation_name, pv.variation_value, pv.price_adjustment
                FROM " . $this->table_name . " c 
                LEFT JOIN products p ON c.product_id = p.id 
                LEFT JOIN product_variations pv ON c.variation_id = pv.id
                WHERE c.user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * カートアイテム削除
     * 
     * @param int $id カートアイテムID
     * @return boolean 削除成功ならtrue
     */
    public function removeItem($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
    
    /**
     * カート数量更新
     * 
     * @param int $id カートアイテムID
     * @param int $quantity 新しい数量
     * @return boolean 更新成功ならtrue
     */
    public function updateQuantity($id, $quantity) {
        $query = "UPDATE " . $this->table_name . " SET quantity = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $quantity);
        $stmt->bindParam(2, $id);
        return $stmt->execute();
    }
    
    /**
     * カート内全アイテム削除
     * 
     * @param string $user_id ユーザーID
     * @return boolean 削除成功ならtrue
     */
    public function clear($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        return $stmt->execute();
    }
}
?>