<?php
/**
 * 予約注文クラス（修正版）
 * 
 * 受注生産商品の予約注文を管理するクラス
 * updateStatusメソッドにデバッグログとエラーハンドリングを追加
 * 
 * @author Prime Select Team
 * @version 1.1
 */
class Preorder {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "preorders";
    
    // プロパティ
    public $id;
    public $user_id;
    public $product_id;
    public $variation_id;
    public $quantity;
    public $estimated_delivery;
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
     * 予約注文作成
     * 
     * @return boolean 作成成功ならtrue
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET user_id = :user_id, 
                    product_id = :product_id, 
                    variation_id = :variation_id, 
                    quantity = :quantity, 
                    estimated_delivery = :estimated_delivery";
        
        $stmt = $this->conn->prepare($query);
        
        // サニタイズ
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->quantity = htmlspecialchars(strip_tags($this->quantity));
        
        // バインド
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":variation_id", $this->variation_id);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":estimated_delivery", $this->estimated_delivery);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * ユーザーの予約注文一覧取得
     * 
     * @param int $user_id ユーザーID
     * @return PDOStatement 結果セット
     */
    public function getUserPreorders($user_id) {
        $query = "SELECT p.*, pr.name as product_name, pr.image, 
                         pv.variation_name, pv.variation_value 
                FROM " . $this->table_name . " p 
                LEFT JOIN products pr ON p.product_id = pr.id 
                LEFT JOIN product_variations pv ON p.variation_id = pv.id 
                WHERE p.user_id = ? 
                ORDER BY p.created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 予約注文状態更新（デバッグログ付き修正版）
     * 
     * @param int $preorder_id 予約注文ID
     * @param string $status 新しいステータス
     * @return boolean 更新成功ならtrue
     */
    public function updateStatus($preorder_id, $status) {
        // 入力値の検証
        if (!is_numeric($preorder_id) || empty($status)) {
            error_log("Preorder updateStatus - Invalid input: preorder_id=$preorder_id, status=$status");
            return false;
        }
        
        // 有効なステータス値の確認
        $valid_statuses = ['pending', 'confirmed', 'production', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            error_log("Preorder updateStatus - Invalid status: $status");
            return false;
        }
        
        error_log("Preorder updateStatus - Updating preorder status: preorder_id=$preorder_id, status=$status");
        
        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->bindParam(2, $preorder_id);
        
        $success = $stmt->execute();
        
        if (!$success) {
            $error_info = $stmt->errorInfo();
            error_log("Preorder updateStatus - Update failed: " . implode(", ", $error_info));
        } else {
            error_log("Preorder updateStatus - Update successful");
        }
        
        return $success;
    }
    
    /**
     * 予約注文詳細取得
     * 
     * @param int $preorder_id 予約注文ID
     * @return array|false 予約注文詳細
     */
    public function read($preorder_id) {
        $query = "SELECT p.*, pr.name as product_name, pr.image, pr.price,
                         pv.variation_name, pv.variation_value, pv.price_adjustment,
                         u.username as customer_name
                FROM " . $this->table_name . " p 
                LEFT JOIN products pr ON p.product_id = pr.id 
                LEFT JOIN product_variations pv ON p.variation_id = pv.id 
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $preorder_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * 予約注文総数取得（管理用）
     * 
     * @return int 予約注文総数
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * 最近の予約注文取得（管理用）
     * 
     * @param int $limit 取得件数
     * @return PDOStatement 結果セット
     */
    public function getRecent($limit = 10) {
        $query = "SELECT p.*, pr.name as product_name, u.username 
                FROM " . $this->table_name . " p 
                LEFT JOIN products pr ON p.product_id = pr.id 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.created DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 管理者用の全予約注文取得
     * 
     * @return PDOStatement 結果セット
     */
    public function readAll() {
        $query = "SELECT p.*, pr.name as product_name, pr.image, u.username,
                         pv.variation_name, pv.variation_value 
                FROM " . $this->table_name . " p 
                LEFT JOIN products pr ON p.product_id = pr.id 
                LEFT JOIN product_variations pv ON p.variation_id = pv.id 
                LEFT JOIN users u ON p.user_id = u.id 
                ORDER BY p.created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 予約注文のキャンセル
     * 
     * @param int $preorder_id 予約注文ID
     * @param int $user_id ユーザーID（権限確認用）
     * @return boolean キャンセル成功ならtrue
     */
    public function cancel($preorder_id, $user_id) {
        // 自分の予約注文かどうか確認
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $preorder_id);
        $stmt->bindParam(2, $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 予約受付中または確定状態の場合のみキャンセル可能
            if($row['status'] == 'pending' || $row['status'] == 'confirmed') {
                return $this->updateStatus($preorder_id, 'cancelled');
            }
        }
        
        return false;
    }
    
    /**
     * ステータス別予約注文数取得
     * 
     * @param string $status ステータス
     * @return int 指定ステータスの予約注文数
     */
    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
    
    /**
     * 配送予定日の更新
     * 
     * @param int $preorder_id 予約注文ID
     * @param string $estimated_delivery 配送予定日
     * @return boolean 更新成功ならtrue
     */
    public function updateEstimatedDelivery($preorder_id, $estimated_delivery) {
        $query = "UPDATE " . $this->table_name . " SET estimated_delivery = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $estimated_delivery);
        $stmt->bindParam(2, $preorder_id);
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Preorder estimated delivery update failed: " . implode(", ", $stmt->errorInfo()));
        }
        
        return $success;
    }
}
?>