<?php
/**
 * お気に入りクラス
 * 
 * ユーザーのお気に入り商品の管理と操作を行うクラス
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Wishlist {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "wishlist";
    
    // プロパティ
    public $id;
    public $user_id;
    public $product_id;
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
     * お気に入り追加
     * 
     * @return boolean 追加成功ならtrue
     */
    public function add() {
        // すでに追加されているか確認
        $check_query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->user_id);
        $check_stmt->bindParam(2, $this->product_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            return true;  // すでに追加済み
        }
        
        // お気に入りに追加
        $query = "INSERT INTO " . $this->table_name . " SET user_id = ?, product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->product_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * お気に入り削除
     * 
     * @return boolean 削除成功ならtrue
     */
    public function remove() {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->product_id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * お気に入り一覧取得
     * 
     * @param int $user_id ユーザーID
     * @return PDOStatement 結果セット
     */
    public function getUserWishlist($user_id) {
        $query = "SELECT w.*, p.name, p.price, p.image 
                FROM " . $this->table_name . " w 
                LEFT JOIN products p ON w.product_id = p.id 
                WHERE w.user_id = ? 
                ORDER BY w.created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * お気に入り確認
     * 
     * @return boolean お気に入りに入っていればtrue
     */
    public function isInWishlist() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->product_id);
        $stmt->execute();
        
        return ($stmt->rowCount() > 0);
    }
}
?>