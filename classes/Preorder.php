<?php
/**
 * Preorder.php - 予約注文管理クラス
 * 
 * 受注生産商品の予約注文を管理するクラスです。
 * 予約注文の作成、状態管理、取得などの機能を提供します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.2
 */
class Preorder {
    // データベース接続とテーブル名
    private $conn;                        // データベース接続オブジェクト
    private $table_name = "preorders";    // 予約注文テーブル名
    
    // プロパティ
    public $id;                           // 予約注文ID
    public $user_id;                      // ユーザーID
    public $product_id;                   // 商品ID
    public $variation_id;                 // バリエーションID
    public $quantity;                     // 数量
    public $estimated_delivery;           // 配送予定日
    public $status;                       // 状態
    public $created;                      // 作成日時
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 予約注文作成メソッド
     * 
     * 新しい予約注文をデータベースに登録します。
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
     * ユーザーの予約注文一覧取得メソッド
     * 
     * 指定されたユーザーの予約注文を取得します。
     * 
     * @param int $user_id ユーザーID
     * @return PDOStatement 結果セット
     */
    public function getUserPreorders($user_id) {
        // クエリを修正：product_nameカラム名を正しく設定
        $query = "SELECT p.*, 
                         pr.name as product_name, 
                         pr.image, 
                         pv.variation_name, 
                         pv.variation_value 
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
     * 予約注文状態更新メソッド
     * 
     * 予約注文のステータスを更新します。
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
     * 予約注文詳細取得メソッド
     * 
     * 指定された予約注文の詳細情報を取得します。
     * 
     * @param int $preorder_id 予約注文ID
     * @return array|false 予約注文詳細
     */
    public function read($preorder_id) {
        $query = "SELECT p.*, 
                         pr.name as product_name, 
                         pr.image, 
                         pr.price,
                         pv.variation_name, 
                         pv.variation_value, 
                         pv.price_adjustment,
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
     * 予約注文総数取得メソッド（管理用）
     * 
     * 予約注文の総数を取得します。
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
     * 最近の予約注文取得メソッド（管理用）
     * 
     * 最近の予約注文を取得します。
     * 
     * @param int $limit 取得件数
     * @return PDOStatement 結果セット
     */
    public function getRecent($limit = 10) {
        $query = "SELECT p.*, 
                         pr.name as product_name, 
                         u.username 
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
     * 管理者用の全予約注文取得メソッド
     * 
     * すべての予約注文を取得します。
     * 
     * @return PDOStatement 結果セット
     */
    public function readAll() {
        $query = "SELECT p.*, 
                         pr.name as product_name, 
                         pr.image, 
                         u.username,
                         pv.variation_name, 
                         pv.variation_value 
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
     * 予約注文のキャンセルメソッド
     * 
     * 指定された予約注文をキャンセルします。
     * ユーザー自身の予約注文のみキャンセル可能です。
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
     * ステータス別予約注文数取得メソッド
     * 
     * 指定されたステータスの予約注文数を取得します。
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
     * 配送予定日の更新メソッド
     * 
     * 予約注文の配送予定日を更新します。
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
    
    /**
     * 商品別予約注文取得メソッド
     * 
     * 指定した商品に関連する予約注文を取得します。
     * 
     * @param int $product_id 商品ID
     * @return PDOStatement 結果セット
     */
    public function getProductPreorders($product_id) {
        $query = "SELECT p.*, u.username, 
                         pv.variation_name, pv.variation_value 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN users u ON p.user_id = u.id 
                  LEFT JOIN product_variations pv ON p.variation_id = pv.id 
                  WHERE p.product_id = ? 
                  ORDER BY p.created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * ステータス更新履歴の記録メソッド
     * 
     * 予約注文のステータス変更履歴を記録します。
     * 
     * @param int $preorder_id 予約注文ID
     * @param string $old_status 旧ステータス
     * @param string $new_status 新ステータス
     * @param int $user_id 変更したユーザーID
     * @return boolean 記録成功ならtrue
     */
    public function logStatusChange($preorder_id, $old_status, $new_status, $user_id) {
        $query = "INSERT INTO preorder_status_logs 
                  SET preorder_id = ?, 
                      old_status = ?, 
                      new_status = ?, 
                      changed_by = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $preorder_id);
        $stmt->bindParam(2, $old_status);
        $stmt->bindParam(3, $new_status);
        $stmt->bindParam(4, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * 配送予定日の計算メソッド
     * 
     * 受注生産期間から配送予定日を計算します。
     * 
     * @param string $preorder_period 受注生産期間の説明
     * @return string 配送予定日（Y-m-d形式）
     */
    public function calculateDeliveryDate($preorder_period) {
        // 期間の数値部分を抽出（"約4-6週間"から4と6を取得）
        preg_match('/(\d+)[-〜~]?(\d+)?[週間]*/', $preorder_period, $matches);
        
        if(isset($matches[1])) {
            // 範囲がある場合は最大値を使用
            $weeks = isset($matches[2]) ? max($matches[1], $matches[2]) : $matches[1];
            
            // 現在の日付から指定週数後を計算
            $delivery_date = date('Y-m-d', strtotime("+{$weeks} weeks"));
            return $delivery_date;
        }
        
        // デフォルト：4週間後
        return date('Y-m-d', strtotime('+4 weeks'));
    }
    
    /**
     * 指定期間の予約注文取得メソッド
     * 
     * 指定された期間内の予約注文を取得します。
     * 
     * @param string $start_date 開始日
     * @param string $end_date 終了日
     * @return PDOStatement 結果セット
     */
    public function getPreordersByPeriod($start_date, $end_date) {
        $query = "SELECT p.*, 
                         pr.name as product_name, 
                         u.username 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN products pr ON p.product_id = pr.id 
                  LEFT JOIN users u ON p.user_id = u.id 
                  WHERE p.created BETWEEN ? AND ? 
                  ORDER BY p.created DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $start_date);
        $stmt->bindParam(2, $end_date);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 改善提案:
     * 
     * 1. 予約注文のメール通知システム（ステータス変更時）
     * 2. 予約注文のキャンセル期限設定
     * 3. 配送予定日の計算ロジックの強化（休日を考慮）
     * 4. 予約注文のバッチ処理（自動ステータス更新）
     * 5. 支払い状況の追跡機能
     * 6. 部分納品対応機能
     */
}