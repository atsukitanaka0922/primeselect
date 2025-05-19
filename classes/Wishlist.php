<?php
/**
 * Wishlist.php - お気に入り管理クラス
 * 
 * ユーザーのお気に入り商品の管理と操作を行うクラスです。
 * お気に入り追加、削除、一覧表示などの機能を提供します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
class Wishlist {
    // データベース接続とテーブル名
    private $conn;                     // データベース接続オブジェクト
    private $table_name = "wishlist";  // お気に入りテーブル名
    
    // プロパティ
    public $id;                        // お気に入りID
    public $user_id;                   // ユーザーID
    public $product_id;                // 商品ID
    public $created;                   // 登録日時
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * お気に入り追加メソッド
     * 
     * ユーザーのお気に入りに商品を追加します。
     * 既に追加済みの場合は何もしません。
     * 
     * @return boolean 追加成功ならtrue、既に追加済みの場合もtrue
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
     * お気に入り削除メソッド
     * 
     * ユーザーのお気に入りから商品を削除します。
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
     * お気に入り一覧取得メソッド
     * 
     * ユーザーのお気に入り商品リストを取得します。
     * 商品情報も含めて取得します。
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
     * お気に入り確認メソッド
     * 
     * 指定された商品がユーザーのお気に入りに登録されているか確認します。
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
    
    /**
     * ユーザーのお気に入り数取得
     * 
     * ユーザーのお気に入り商品数を取得します。
     * 
     * @param int $user_id ユーザーID
     * @return int お気に入り商品数
     */
    public function countUserWishlist($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['count'];
    }
    
    /**
     * 全ユーザーのお気に入り数取得（商品別）
     * 
     * 商品ごとのお気に入り登録数を取得します。（管理画面用）
     * 
     * @param int $product_id 商品ID
     * @return int お気に入り登録数
     */
    public function countProductWishlist($product_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['count'];
    }
    
    /**
     * 人気のお気に入り商品取得
     * 
     * 最もお気に入り登録されている商品を取得します。
     * 
     * @param int $limit 取得件数
     * @return PDOStatement 結果セット
     */
    public function getPopularWishlisted($limit = 10) {
        $query = "SELECT w.product_id, COUNT(*) as count, 
                       p.name, p.price, p.image
                FROM " . $this->table_name . " w 
                LEFT JOIN products p ON w.product_id = p.id 
                GROUP BY w.product_id 
                ORDER BY count DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 全お気に入り削除
     * 
     * ユーザーの全お気に入りを削除します。
     * アカウント削除時などに使用します。
     * 
     * @param int $user_id ユーザーID
     * @return boolean 削除成功ならtrue
     */
    public function removeAllByUser($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * お気に入りから商品一括削除
     * 
     * 指定された商品を全ユーザーのお気に入りから削除します。
     * 商品削除時などに使用します。
     * 
     * @param int $product_id 商品ID
     * @return boolean 削除成功ならtrue
     */
    public function removeAllByProduct($product_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE product_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        
        return $stmt->execute();
    }
    
    /**
     * お気に入り商品のカートへの追加
     * 
     * お気に入り商品をカートに追加します。
     * 
     * @param int $wishlist_id お気に入りID
     * @param string $user_id カートのユーザーID
     * @param int $quantity 数量
     * @return boolean 追加成功ならtrue
     */
    public function addToCart($wishlist_id, $user_id, $quantity = 1) {
        // お気に入り情報を取得
        $query = "SELECT product_id FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $wishlist_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $product_id = $row['product_id'];
            
            // Cartクラスがない場合は読み込み
            if(!class_exists('Cart')) {
                include_once "classes/Cart.php";
            }
            
            // カートに追加
            $cart = new Cart($this->conn);
            return $cart->addItem($user_id, $product_id, $quantity);
        }
        
        return false;
    }
    
    /**
     * 改善提案:
     * 
     * 1. お気に入り商品の通知機能（値下げ、在庫復活など）
     * 2. 共有可能なお気に入りリスト機能
     * 3. お気に入りの並び替え・グループ分け機能
     * 4. お気に入りの有効期限設定
     * 5. お気に入りに追加された数のカウンター表示
     */
}