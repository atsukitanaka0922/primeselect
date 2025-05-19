<?php
/**
 * Review.php - 商品レビュークラス
 * 
 * 商品レビューの管理と操作を行うクラスです。
 * ユーザーによる商品レビューの登録、取得、評価の集計機能を提供します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
class Review {
    // データベース接続とテーブル名
    private $conn;                     // データベース接続オブジェクト
    private $table_name = "reviews";   // レビューテーブル名
    
    // プロパティ
    public $id;                        // レビューID
    public $product_id;                // 商品ID
    public $user_id;                   // ユーザーID
    public $rating;                    // 評価（星の数 1-5）
    public $comment;                   // レビューコメント
    public $created;                   // 投稿日時
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * レビュー追加・更新メソッド
     * 
     * 商品に対する新規レビューを追加します。
     * 同じユーザーが同じ商品に既にレビューしていた場合は更新します。
     * 
     * @return boolean 追加/更新成功ならtrue、失敗ならfalse
     */
    public function create() {
        // レビューが既に存在するか確認（一人のユーザーにつき商品1つに1レビュー）
        $check_query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? AND product_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->user_id);
        $check_stmt->bindParam(2, $this->product_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() > 0) {
            // 既存のレビューを更新する場合
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            
            $query = "UPDATE " . $this->table_name . " 
                    SET rating = :rating, comment = :comment 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // 入力値のサニタイズ
            $this->rating = htmlspecialchars(strip_tags($this->rating));
            $this->comment = htmlspecialchars(strip_tags($this->comment));
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            // パラメータをバインド
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
            
            // 入力値のサニタイズ
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->product_id = htmlspecialchars(strip_tags($this->product_id));
            $this->rating = htmlspecialchars(strip_tags($this->rating));
            $this->comment = htmlspecialchars(strip_tags($this->comment));
            
            // パラメータをバインド
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":product_id", $this->product_id);
            $stmt->bindParam(":rating", $this->rating);
            $stmt->bindParam(":comment", $this->comment);
        }
        
        // クエリを実行して結果を返す
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 商品のレビュー取得メソッド
     * 
     * 指定した商品のすべてのレビューを取得します。
     * レビューにはユーザー名も含まれます。
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
     * 商品の平均評価取得メソッド
     * 
     * 指定した商品の平均評価（星の数）を計算します。
     * 
     * @param int $product_id 商品ID
     * @return float 平均評価（小数点第1位までの数値）
     */
    public function getAverageRating($product_id) {
        $query = "SELECT AVG(rating) as average_rating FROM " . $this->table_name . " WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 結果がNULLの場合は0を返す、それ以外は小数点第1位までの数値を返す
        return round($row['average_rating'] ?? 0, 1);
    }
    
    /**
     * レビュー数取得メソッド
     * 
     * 指定した商品のレビュー数を取得します。
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
    
    /**
     * 改善提案:
     * 
     * 1. レビューの投稿日時でのソート機能の追加
     * 2. 役立ったレビューの投票システムの実装
     * 3. レビュー検索機能の追加
     * 4. レビューの写真アップロード機能
     * 5. 不適切なレビューを報告する機能
     * 6. レビューの承認ワークフローの実装（管理者確認）
     */
}