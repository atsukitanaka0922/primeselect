<?php
/**
 * Cart.php - ショッピングカート管理クラス
 * 
 * ショッピングカートの管理と操作を行うクラスです。
 * カートへの商品追加、削除、数量変更などの機能を提供します。
 * バリエーション対応、在庫管理も含まれています。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
class Cart {
    // データベース接続とテーブル名
    private $conn;                   // データベース接続オブジェクト
    private $table_name = "cart";    // カートテーブル名
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * カートに商品を追加
     * 
     * ユーザーのカートに商品を追加します。
     * 既に同じ商品がカートにある場合は数量を加算します。
     * 
     * @param string $user_id ユーザーID（セッションID）
     * @param int $product_id 商品ID
     * @param int $quantity 数量（デフォルト1）
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
     * ユーザーのカート内容を取得します。
     * 商品情報とバリエーション情報も結合して取得します。
     * 
     * @param string $user_id ユーザーID（セッションID）
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
     * カートから指定アイテムを削除します。
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
     * カート内の商品数量を更新します。
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
     * ユーザーのカート内をすべて空にします。
     * 注文完了時などに使用します。
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
    
    /**
     * カート内アイテム数取得
     * 
     * カート内の商品アイテム数を取得します。
     * 
     * @param string $user_id ユーザーID
     * @return int アイテム数
     */
    public function getItemCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$row['count'];
    }
    
    /**
     * カート内合計金額取得
     * 
     * カート内の商品の合計金額を計算します。
     * バリエーションの価格調整も考慮します。
     * 
     * @param string $user_id ユーザーID
     * @return float 合計金額
     */
    public function getTotalAmount($user_id) {
        $items = $this->getItems($user_id);
        $total = 0;
        
        while($row = $items->fetch(PDO::FETCH_ASSOC)) {
            $price = $row['price'];
            
            // バリエーションがある場合は価格調整を適用
            if(isset($row['price_adjustment'])) {
                $price += $row['price_adjustment'];
            }
            
            $total += $price * $row['quantity'];
        }
        
        return $total;
    }
    
    /**
     * カートアイテム取得 (ID指定)
     * 
     * 指定されたカートアイテムIDの情報を取得します。
     * 
     * @param int $cart_id カートアイテムID
     * @return array|false カートアイテム情報
     */
    public function getCartItem($cart_id) {
        $query = "SELECT c.*, p.name, p.price, p.image, 
                     pv.variation_name, pv.variation_value, pv.price_adjustment
                 FROM " . $this->table_name . " c 
                 LEFT JOIN products p ON c.product_id = p.id 
                 LEFT JOIN product_variations pv ON c.variation_id = pv.id
                 WHERE c.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cart_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * カートアイテム有無チェック
     * 
     * 指定された商品がカートに存在するかチェックします。
     * 
     * @param string $user_id ユーザーID
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @return boolean 存在すればtrue
     */
    public function isInCart($user_id, $product_id, $variation_id = null) {
        $query = "SELECT id FROM " . $this->table_name . " 
                WHERE user_id = ? AND product_id = ?";
        
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
        
        return ($stmt->rowCount() > 0);
    }
    
    /**
     * ユーザーのカートをマージ
     * 
     * 未ログイン時のカートとログイン後のカートをマージします。
     * セッションID(temp_user_id)からユーザーID(user_id)へ移行します。
     * 
     * @param string $temp_user_id 仮ユーザーID（セッションID）
     * @param int $user_id 実ユーザーID
     * @return boolean マージ成功ならtrue
     */
    public function mergeCart($temp_user_id, $user_id) {
        // 仮ユーザーIDのカート内容を取得
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $temp_user_id);
        $stmt->execute();
        
        // トランザクション開始
        $this->conn->beginTransaction();
        
        try {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // 既存カートに同じ商品があるか確認
                $exists = $this->isInCart($user_id, $row['product_id'], $row['variation_id']);
                
                if($exists) {
                    // 既に存在する場合は数量を加算
                    $this->addItem($user_id, $row['product_id'], $row['quantity'], $row['variation_id']);
                } else {
                    // 新規の場合はそのまま追加
                    $insert_query = "INSERT INTO " . $this->table_name . " 
                                  SET user_id = ?, 
                                      product_id = ?, 
                                      quantity = ?, 
                                      variation_id = ?";
                    
                    $insert_stmt = $this->conn->prepare($insert_query);
                    $insert_stmt->bindParam(1, $user_id);
                    $insert_stmt->bindParam(2, $row['product_id']);
                    $insert_stmt->bindParam(3, $row['quantity']);
                    $insert_stmt->bindParam(4, $row['variation_id']);
                    $insert_stmt->execute();
                }
            }
            
            // 仮ユーザーのカートを削除
            $this->clear($temp_user_id);
            
            // コミット
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // エラー時はロールバック
            $this->conn->rollback();
            error_log("Cart merge error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 改善提案:
     * 
     * 1. カート有効期限の設定（長期間放置されたカートアイテムの自動削除）
     * 2. 関連商品のレコメンド機能
     * 3. クーポンコード適用機能
     * 4. セッションとDBの両方でカート情報を管理（パフォーマンス向上）
     * 5. カート内容の一時保存機能
     * 6. 在庫不足時の自動通知
     */
}