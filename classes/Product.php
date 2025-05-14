<?php
/**
 * 商品クラス
 * 
 * 商品情報の管理と操作を行うクラス
 * 在庫管理とトランザクション修正版
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Product {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "products";
    
    // プロパティ
    public $id;
    public $name;
    public $description;
    public $price;
    public $category_id;
    public $image;
    public $stock;
    public $created;
    public $category_name;
    public $is_preorder;
    public $preorder_period;
    
    /**
     * コンストラクタ
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 新しい商品を作成
     * 
     * @return boolean 作成成功ならtrue
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET name = :name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    image = :image,
                    stock = :stock,
                    is_preorder = :is_preorder,
                    preorder_period = :preorder_period";
        
        $stmt = $this->conn->prepare($query);
        
        // サニタイズ
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->image = htmlspecialchars(strip_tags($this->image));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->is_preorder = htmlspecialchars(strip_tags($this->is_preorder));
        $this->preorder_period = htmlspecialchars(strip_tags($this->preorder_period));
        
        // 受注生産商品の場合は在庫を0にする
        if($this->is_preorder == 1) {
            $this->stock = 0;
        }
        
        // 空文字列の場合はNULLに変換
        if(empty($this->image)) {
            $this->image = null;
        }
        if(empty($this->category_id)) {
            $this->category_id = null;
        }
        if(empty($this->preorder_period)) {
            $this->preorder_period = null;
        }
        
        // バインド
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":image", $this->image);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":is_preorder", $this->is_preorder);
        $stmt->bindParam(":preorder_period", $this->preorder_period);
        
        if($stmt->execute()) {
            $product_id = $this->conn->lastInsertId();
            
            // メイン画像を追加（画像がアップロードされた場合のみ）
            if($this->image) {
                $query_image = "INSERT INTO product_images SET product_id = ?, image_file = ?, is_main = 1";
                $stmt_image = $this->conn->prepare($query_image);
                $stmt_image->bindParam(1, $product_id);
                $stmt_image->bindParam(2, $this->image);
                $stmt_image->execute();
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 商品を削除
     * 
     * @param int $id 削除する商品ID
     * @return boolean 削除成功ならtrue
     */
    public function delete($id) {
        // 商品画像も削除するためにトランザクションを使用
        $this->conn->beginTransaction();
        
        try {
            // まず商品画像を削除
            $query = "DELETE FROM product_images WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            // 商品バリエーションを削除
            $query = "DELETE FROM product_variations WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            // 在庫ログを削除
            $query = "DELETE FROM product_stock_logs WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            // 最後に商品本体を削除
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * 商品を更新
     * 
     * @return boolean 更新成功ならtrue
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET name = :name, 
                    description = :description, 
                    price = :price, 
                    category_id = :category_id, 
                    stock = :stock,
                    is_preorder = :is_preorder,
                    preorder_period = :preorder_period";
        
        // 画像が更新される場合
        if(!empty($this->image)) {
            $query .= ", image = :image";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // サニタイズ
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->is_preorder = htmlspecialchars(strip_tags($this->is_preorder));
        $this->preorder_period = htmlspecialchars(strip_tags($this->preorder_period));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // バインド
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":stock", $this->stock);
        $stmt->bindParam(":is_preorder", $this->is_preorder);
        $stmt->bindParam(":preorder_period", $this->preorder_period);
        $stmt->bindParam(":id", $this->id);
        
        if(!empty($this->image)) {
            $this->image = htmlspecialchars(strip_tags($this->image));
            $stmt->bindParam(":image", $this->image);
        }
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 全商品取得
     * 
     * @return PDOStatement 商品一覧の結果セット
     */
    public function read() {
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.stock, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    /**
     * 単一商品取得
     * 
     * @return boolean 取得成功ならtrue
     */
    public function readOne() {
        $query = "SELECT p.id, p.name, p.description, p.price, p.category_id, p.image, p.stock, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->price = $row['price'];
            $this->description = $row['description'];
            $this->category_id = $row['category_id'] ?? null;
            $this->image = $row['image'];
            $this->stock = $row['stock'];
            $this->category_name = $row['category_name'] ?? '未分類';
            return true;
        }
        
        return false;
    }
    
    /**
     * 商品検索メソッド
     * 
     * @param string $keyword 検索キーワード
     * @return PDOStatement 検索結果
     */
    public function search($keyword) {
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.name LIKE ? OR p.description LIKE ? 
                ORDER BY p.created DESC";
        
        $keyword = "%{$keyword}%";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $keyword);
        $stmt->bindParam(2, $keyword);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * カテゴリ別商品取得（ソート機能付き）
     * 
     * @param int $category_id カテゴリID
     * @param string $sort_by ソート列
     * @param string $sort_order ソート順序
     * @return PDOStatement 結果セット
     */
    public function getByCategory($category_id, $sort_by = 'created', $sort_order = 'DESC') {
        $valid_sort_columns = ['name', 'price', 'created'];
        $valid_sort_orders = ['ASC', 'DESC'];
        
        // 入力値のバリデーション
        if (!in_array($sort_by, $valid_sort_columns)) {
            $sort_by = 'created';  // デフォルトに戻す
        }
        
        if (!in_array($sort_order, $valid_sort_orders)) {
            $sort_order = 'DESC';  // デフォルトに戻す
        }
        
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? 
                ORDER BY p.{$sort_by} {$sort_order}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 価格範囲で商品取得（ソート機能付き）
     * 
     * @param float $min_price 最低価格
     * @param float $max_price 最高価格
     * @param string $sort_by ソート列
     * @param string $sort_order ソート順序
     * @return PDOStatement 結果セット
     */
    public function getByPriceRange($min_price, $max_price, $sort_by = 'created', $sort_order = 'DESC') {
        $valid_sort_columns = ['name', 'price', 'created'];
        $valid_sort_orders = ['ASC', 'DESC'];
        
        // 入力値のバリデーション
        if (!in_array($sort_by, $valid_sort_columns)) {
            $sort_by = 'created';  // デフォルトに戻す
        }
        
        if (!in_array($sort_order, $valid_sort_orders)) {
            $sort_order = 'DESC';  // デフォルトに戻す
        }
        
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.price BETWEEN ? AND ? 
                ORDER BY p.{$sort_by} {$sort_order}";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $min_price);
        $stmt->bindParam(2, $max_price);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * ソート機能付き商品読み込み
     * 
     * @param int $from_record_num 開始レコード番号
     * @param int $records_per_page 1ページあたりのレコード数
     * @param string $sort_by ソート列
     * @param string $sort_order ソート順序
     * @return PDOStatement 結果セット
     */
    public function readWithSorting($from_record_num, $records_per_page, $sort_by = 'created', $sort_order = 'DESC') {
        $valid_sort_columns = ['name', 'price', 'created'];
        $valid_sort_orders = ['ASC', 'DESC'];
        
        // 入力値のバリデーション
        if (!in_array($sort_by, $valid_sort_columns)) {
            $sort_by = 'created';  // デフォルトに戻す
        }
        
        if (!in_array($sort_order, $valid_sort_orders)) {
            $sort_order = 'DESC';  // デフォルトに戻す
        }
        
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.{$sort_by} {$sort_order} 
                LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * ページネーション用メソッド
     * 
     * @param int $from_record_num 開始レコード番号
     * @param int $records_per_page 1ページあたりのレコード数
     * @return PDOStatement 結果セット
     */
    public function readPaging($from_record_num, $records_per_page) {
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created DESC 
                LIMIT ?, ?";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 商品の総数取得
     * 
     * @return int 総商品数
     */
    public function count() {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }
    
    /**
     * 商品の全画像取得
     * 
     * @param int $product_id 商品ID
     * @return PDOStatement 結果セット
     */
    public function getProductImages($product_id) {
        $query = "SELECT * FROM product_images 
                WHERE product_id = ? 
                ORDER BY is_main DESC, id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * メイン画像取得
     * 
     * @param int $product_id 商品ID
     * @return string|null 画像ファイル名または null
     */
    public function getMainImage($product_id) {
        $query = "SELECT image_file FROM product_images 
                WHERE product_id = ? AND is_main = 1 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['image_file'];
        }
        
        return null;
    }

    /**
     * 商品のバリエーションを取得
     * 
     * @param int $product_id 商品ID
     * @return PDOStatement 結果セット
     */
    public function getProductVariations($product_id) {
        $query = "SELECT * FROM product_variations 
                WHERE product_id = ? 
                ORDER BY variation_name, price_adjustment";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt;
    }

    /**
     * 商品バリエーションを取得（ID指定）
     * 
     * @param int $variation_id バリエーションID
     * @return array|null バリエーション情報
     */
    public function getVariationById($variation_id) {
        $query = "SELECT * FROM product_variations WHERE id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $variation_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    /**
     * 関連情報を含む商品バリエーションを取得
     * 
     * @param int $product_id 商品ID
     * @return array バリエーションの配列（名前ごとにグループ化）
     */
    public function getGroupedVariations($product_id) {
        $variations = [];
        
        $query = "SELECT * FROM product_variations 
                WHERE product_id = ? 
                ORDER BY variation_name, price_adjustment";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $variations[$row['variation_name']][] = $row;
        }
        
        return $variations;
    }
    
    /**
     * 在庫レベルをチェック（デバッグ情報付き）
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @return array 在庫情報
     */
    public function checkStock($product_id, $variation_id = null) {
        try {
            if ($variation_id) {
                $query = "SELECT stock FROM product_variations WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $variation_id);
            } else {
                $query = "SELECT stock FROM products WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $product_id);
            }
            
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stock = $row ? intval($row['stock']) : 0;
            
            return [
                'stock' => $stock,
                'is_available' => $stock > 0,
                'status' => $this->getStockStatus($stock)
            ];
        } catch (Exception $e) {
            error_log("Stock check error: " . $e->getMessage());
            return [
                'stock' => 0,
                'is_available' => false,
                'status' => 'out_of_stock'
            ];
        }
    }
    
    /**
     * 在庫ステータスを取得
     * 
     * @param int $stock 在庫数
     * @return string 在庫ステータス
     */
    public function getStockStatus($stock) {
        if ($stock <= 0) {
            return 'out_of_stock';
        } elseif ($stock <= 5) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }
    
    /**
     * 在庫を更新（修正版）
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量
     * @param string $reason 理由
     * @return boolean 更新成功ならtrue
     */
    public function updateStock($product_id, $variation_id, $quantity, $reason = '') {
        // 入力値検証
        if(empty($reason)) {
            error_log("Stock update failed: Reason is required");
            return false;
        }
        
        // トランザクション開始
        $this->conn->beginTransaction();
        
        try {
            // 現在の在庫を確認
            if ($variation_id) {
                $query = "SELECT stock FROM product_variations WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $variation_id, PDO::PARAM_INT);
            } else {
                $query = "SELECT stock FROM products WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $product_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                throw new Exception("商品または商品バリエーションが見つかりません (Product ID: $product_id, Variation ID: $variation_id)");
            }
            
            $current_stock = intval($row['stock']);
            $new_stock = $current_stock + intval($quantity);
            
            // 在庫が負にならないかチェック
            if ($new_stock < 0) {
                throw new Exception("在庫が不足しています。現在の在庫: {$current_stock}個、変更数: {$quantity}個");
            }
            
            // 在庫を更新
            if ($variation_id) {
                $update_query = "UPDATE product_variations SET stock = ? WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(1, $new_stock, PDO::PARAM_INT);
                $update_stmt->bindParam(2, $variation_id, PDO::PARAM_INT);
            } else {
                $update_query = "UPDATE products SET stock = ? WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(1, $new_stock, PDO::PARAM_INT);
                $update_stmt->bindParam(2, $product_id, PDO::PARAM_INT);
            }
            
            if (!$update_stmt->execute()) {
                throw new Exception("在庫更新クエリの実行に失敗しました");
            }
            
            // 在庫ログを記録
            $log_result = $this->logStockChange($product_id, $variation_id, $quantity, $reason);
            if (!$log_result) {
                error_log("Stock log creation failed, but continuing...");
            }
            
            $this->conn->commit();
            
            error_log("Stock updated successfully: Product ID: $product_id, Variation ID: $variation_id, Change: $quantity, New Stock: $new_stock");
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Stock update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 在庫変更ログを記録（修正版）
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量
     * @param string $reason 理由
     * @return boolean 記録成功ならtrue
     */
    private function logStockChange($product_id, $variation_id, $quantity, $reason = '') {
        $type = $quantity > 0 ? 'in' : ($quantity < 0 ? 'out' : 'adjust');
        
        $query = "INSERT INTO product_stock_logs 
                  SET product_id = ?, variation_id = ?, type = ?, quantity = ?, reason = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id, PDO::PARAM_INT);
        
        // variation_id が null の場合は null をバインド
        if ($variation_id) {
            $stmt->bindParam(2, $variation_id, PDO::PARAM_INT);
        } else {
            $stmt->bindParam(2, $variation_id, PDO::PARAM_NULL);
        }
        
        $stmt->bindParam(3, $type);
        $stmt->bindParam(4, abs($quantity), PDO::PARAM_INT);
        $stmt->bindParam(5, $reason);
        
        return $stmt->execute();
    }
    
    /**
     * 受注生産商品かどうかを確認
     * 
     * @param int $product_id 商品ID
     * @return array 受注生産情報
     */
    public function getPreorderInfo($product_id) {
        $query = "SELECT is_preorder, preorder_period FROM products WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'is_preorder' => $row['is_preorder'] ?? 0,
            'preorder_period' => $row['preorder_period'] ?? null
        ];
    }
}
?>