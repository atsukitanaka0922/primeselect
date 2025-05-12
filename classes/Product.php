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
    public $created;
    public $category_name;
    
    /**
     * コンストラクタ
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 全商品取得
     * 
     * @return PDOStatement 商品一覧の結果セット
     */
    public function read() {
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
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
        $query = "SELECT p.id, p.name, p.description, p.price, p.category_id, p.image, p.created, c.name as category_name 
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
     * 在庫レベルをチェック
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @return array 在庫情報
     */
    public function checkStock($product_id, $variation_id = null) {
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
        
        $stock = $row['stock'] ?? 0;
        
        return [
            'stock' => $stock,
            'is_available' => $stock > 0,
            'status' => $this->getStockStatus($stock)
        ];
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
     * 在庫を更新（トランザクション修正版）
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量（正数：入庫、負数：出庫）
     * @param string $reason 理由
     * @return boolean 更新成功ならtrue
     */
    public function updateStock($product_id, $variation_id, $quantity, $reason = '') {
        // 既にトランザクションが開始されているかチェック
        $transaction_started = false;
        if (!$this->conn->inTransaction()) {
            $this->conn->beginTransaction();
            $transaction_started = true;
        }
        
        try {
            // 現在の在庫を確認
            $current_stock = $this->checkStock($product_id, $variation_id)['stock'];
            $new_stock = $current_stock + $quantity;
            
            if ($new_stock < 0) {
                throw new Exception('在庫が不足しています');
            }
            
            // 在庫を更新
            if ($variation_id) {
                $query = "UPDATE product_variations SET stock = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $new_stock);
                $stmt->bindParam(2, $variation_id);
            } else {
                $query = "UPDATE products SET stock = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $new_stock);
                $stmt->bindParam(2, $product_id);
            }
            
            $stmt->execute();
            
            // 在庫ログを記録
            $this->logStockChange($product_id, $variation_id, $quantity, $reason);
            
            // 自分でトランザクションを開始した場合のみコミット
            if ($transaction_started) {
                $this->conn->commit();
            }
            return true;
        } catch (Exception $e) {
            // 自分でトランザクションを開始した場合のみロールバック
            if ($transaction_started) {
                $this->conn->rollback();
            }
            return false;
        }
    }
    
    /**
     * 在庫変更ログを記録
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量
     * @param string $reason 理由
     */
    private function logStockChange($product_id, $variation_id, $quantity, $reason = '') {
        $type = $quantity > 0 ? 'in' : ($quantity < 0 ? 'out' : 'adjust');
        
        $query = "INSERT INTO product_stock_logs 
                  SET product_id = ?, variation_id = ?, type = ?, quantity = ?, reason = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->bindParam(2, $variation_id);
        $stmt->bindParam(3, $type);
        $stmt->bindParam(4, abs($quantity));
        $stmt->bindParam(5, $reason);
        $stmt->execute();
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