<?php
/**
 * レビュークラス
 * 
 * 商品レビューの管理と操作を行うクラス
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Review {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "reviews";
    
    // プロパティ
    public $id;
    public $product_id;
    public $user_id;
    public $rating;
    public $comment;
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
     * レビュー追加
     * 
     * @return boolean 追加成功ならtrue
     */
    public function create() {
        // レビューが既に存在するか確認
        $check_query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->user_id);
        $check_stmt->bindParam(2, $this->product_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            // 既存のレビューを更新
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            
            $query = "UPDATE " . $this->table_name . " 
                    SET rating = :rating, comment = :comment 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // サニタイズ
            $this->rating = htmlspecialchars(strip_tags($this->rating));
            $this->comment = htmlspecialchars(strip_tags($this->comment));
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            // バインド
            $stmt->bindParam(":rating", $this->rating);
            $stmt->bindParam(":comment", $this->comment);
            $stmt->bindParam(":id", $this->id);
        } else {
            // 新規レビュー作成
            $query = "INSERT INTO " . $this->table_name . " 
                    SET user_id = :user_id, 
                        product_id = :product_id, 
                        rating = :rating, 
                        comment = :comment";
            
            $stmt = $this->conn->prepare($query);
            
            // サニタイズ
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->product_id = htmlspecialchars(strip_tags($this->product_id));
            $this->rating = htmlspecialchars(strip_tags($this->rating));
            $this->comment = htmlspecialchars(strip_tags($this->comment));
            
            // バインド
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":rating", $this->rating);
            $stmt->bindParam(":comment", $this->comment);
        }
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 商品のレビュー取得
     * 
     * @param int $product_id 商品ID
     * @return PDOStatement 結果セット
     */
    public function getProductReviews($product_id) {
        $query = "SELECT r.*, u.username 
                FROM " . $this->table_name . " r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? 
                ORDER BY r.created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 商品の平均評価取得
     * 
     * @param int $product_id 商品ID
     * @return float 平均評価
     */
    public function getAverageRating($product_id) {
        $query = "SELECT AVG(rating) as average_rating FROM " . $this->table_name . " WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return round($row['average_rating'] ?? 0, 1);
    }
    
    /**
     * レビュー数取得
     * 
     * @param int $product_id 商品ID
     * @return int レビュー数
     */
    public function getReviewCount($product_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'];
    }
}
?>