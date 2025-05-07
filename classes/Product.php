<?php
class Product {
    private $conn;
    private $table_name = "products";
    
    public $id;
    public $name;
    public $description;
    public $price;
    public $category_id;
    public $image;
    public $created;
    public $category_name;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // 全商品取得
    public function read() {
        $query = "SELECT p.id, p.name, p.description, p.price, p.image, p.created, c.name as category_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // 単一商品取得
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
        }
    }
    
    // 商品検索メソッド
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
    
    // カテゴリ別商品取得（ソート機能付き）
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
    
    // 価格範囲で商品取得（ソート機能付き）
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
    
    // ページネーション用メソッド
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
    
    // ソート機能付き商品読み込み
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
    
    // 商品の総数取得
    public function count() {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }
    
    // 商品の全画像取得
    public function getProductImages($product_id) {
        $query = "SELECT * FROM product_images 
                WHERE product_id = ? 
                ORDER BY is_main DESC, id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // メイン画像取得
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
}
?>