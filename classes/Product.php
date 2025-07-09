<?php
/**
 * Product.php - 商品管理クラス
 * 
 * 商品情報の管理と操作を行うクラスです。
 * 商品の登録、取得、検索、在庫管理など多様な機能を提供します。
 * 
 * 特徴:
 * - 通常商品と受注生産商品の両方に対応
 * - 商品バリエーション（サイズ、色など）の管理
 * - 在庫管理とログ記録
 * - カテゴリー別商品取得
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.2
 */
class Product {
    // データベース接続とテーブル名
    private $conn;                     // データベース接続オブジェクト
    private $table_name = "products";  // 商品テーブル名
    
    // 商品プロパティ
    public $id;                        // 商品ID
    public $name;                      // 商品名
    public $description;               // 商品説明
    public $price;                     // 価格
    public $category_id;               // カテゴリID
    public $image;                     // メイン画像ファイル名
    public $stock;                     // 在庫数
    public $created;                   // 登録日時
    public $category_name;             // カテゴリ名（結合用）
    public $is_preorder;               // 受注生産フラグ
    public $preorder_period;           // 受注生産期間
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * 新しい商品を作成
     * 
     * 商品情報をデータベースに登録します。
     * 受注生産商品の場合は在庫を0に設定します。
     * 
     * @return boolean 作成成功ならtrue、失敗ならfalse
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
        
        // 入力値のサニタイズ（XSS対策）
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
        
        // パラメータをバインド
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
     * 商品とその関連情報（画像、バリエーション、在庫ログ）を削除します。
     * 整合性を保つためにトランザクションを使用します。
     * 
     * @param int $id 削除する商品ID
     * @return boolean 削除成功ならtrue、失敗ならfalse
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
            
            // すべて成功したらコミット
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            // エラーが発生した場合はロールバック
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * 商品を更新
     * 
     * 商品情報を更新します。画像が指定された場合のみ画像も更新します。
     * 
     * @return boolean 更新成功ならtrue、失敗ならfalse
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
        
        // 入力値のサニタイズ
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->stock = htmlspecialchars(strip_tags($this->stock));
        $this->is_preorder = htmlspecialchars(strip_tags($this->is_preorder));
        $this->preorder_period = htmlspecialchars(strip_tags($this->preorder_period));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // パラメータをバインド
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
     * すべての商品を取得します。カテゴリ名も含まれます。
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
     * 指定したIDの商品情報を取得します。
     * 
     * @return boolean 取得成功ならtrue、失敗ならfalse
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
            // プロパティに値をセット
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
     * 名前または説明にキーワードを含む商品を検索します。
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
        
        // ワイルドカード検索のためにキーワードを加工
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
     * 指定したカテゴリの商品を取得します。
     * 名前、価格、登録日などでソートできます。
     * 
     * @param int $category_id カテゴリID
     * @param string $sort_by ソート列
     * @param string $sort_order ソート順序（ASC/DESC）
     * @return PDOStatement 結果セット
     */
    public function getByCategory($category_id, $sort_by = 'created', $sort_order = 'DESC') {
        $valid_sort_columns = ['name', 'price', 'created'];
        $valid_sort_orders = ['ASC', 'DESC'];
        
        // 入力値のバリデーション（SQLインジェクション対策）
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
     * 指定した価格範囲内の商品を取得します。
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
     * ページネーション機能を持ち、ソート可能な商品一覧を取得します。
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
     * 商品一覧のページネーション表示用のデータを取得します。
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
     * 商品テーブルの総レコード数を取得します。
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
     * 指定した商品のすべての画像を取得します。
     * メイン画像が先頭になるようにソートされます。
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
     * 指定した商品のメイン画像を取得します。
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
     * 指定した商品のすべてのバリエーションを取得します。
     * サイズ、色などのバリエーションがあります。
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
     * 指定したバリエーションIDの情報を取得します。
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
     * 指定した商品のバリエーションを名前ごとにグループ化して取得します。
     * （例: 「サイズ」でグループ化したS/M/Lなど）
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
        
        // extract()は使用せず、直接配列にアクセスする（より安全な方法）
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $variations[$row['variation_name']][] = $row;
        }
        
        return $variations;
    }
    
    /**
     * 在庫レベルをチェック
     * 
     * 商品またはバリエーションの在庫状況を確認します。
     * 在庫数、在庫の有無、在庫状態（在庫あり、残りわずか、在庫切れ）を返します。
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @return array 在庫情報
     */
    public function checkStock($product_id, $variation_id = null) {
        if ($variation_id) {
            // バリエーションがある場合はバリエーションの在庫を確認
            $query = "SELECT stock FROM product_variations WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $variation_id);
        } else {
            // バリエーションがない場合は商品自体の在庫を確認
            $query = "SELECT stock FROM products WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $product_id);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stock = $row['stock'] ?? 0;
        
        return [
            'stock' => $stock,                       // 在庫数
            'is_available' => $stock > 0,            // 在庫の有無
            'status' => $this->getStockStatus($stock) // 在庫ステータス
        ];
    }
    
    /**
     * 在庫ステータスを取得
     * 
     * 在庫数に基づいて在庫ステータスを返します。
     * - 在庫切れ (out_of_stock): 在庫が0以下
     * - 残りわずか (low_stock): 在庫が5以下
     * - 在庫あり (in_stock): 在庫が6以上
     * 
     * @param int $stock 在庫数
     * @return string 在庫ステータス
     */
    public function getStockStatus($stock) {
        if ($stock <= 0) {
            return 'out_of_stock';  // 在庫切れ
        } elseif ($stock <= 5) {
            return 'low_stock';     // 残りわずか
        } else {
            return 'in_stock';      // 在庫あり
        }
    }
    
    /**
     * 在庫を更新（トランザクション対応版）
     * 
     * 商品またはバリエーションの在庫を更新し、在庫ログを記録します。
     * 整合性を保つためにトランザクションを使用します。
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量（正数：入庫、負数：出庫）
     * @param string $reason 理由
     * @return boolean 更新成功ならtrue、失敗ならfalse
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
            $stock_info = $this->checkStock($product_id, $variation_id);
            $current_stock = $stock_info['stock'];
            $new_stock = $current_stock + $quantity;
            
            // 在庫がマイナスになる場合はエラー
            if ($new_stock < 0) {
                throw new Exception('在庫が不足しています');
            }
            
            // 在庫を更新
            if ($variation_id) {
                // バリエーションの在庫を更新
                $query = "UPDATE product_variations SET stock = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $new_stock);
                $stmt->bindParam(2, $variation_id);
            } else {
                // 商品自体の在庫を更新
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
     * 在庫変更の履歴を記録します。
     * タイプ（入庫/出庫/調整）、数量、理由を保存します。
     * 
     * @param int $product_id 商品ID
     * @param int|null $variation_id バリエーションID
     * @param int $quantity 変更数量
     * @param string $reason 理由
     */
    private function logStockChange($product_id, $variation_id, $quantity, $reason = '') {
        $type = $quantity > 0 ? 'in' : ($quantity < 0 ? 'out' : 'adjust');
        $abs_quantity = abs($quantity); // 関数の戻り値を変数に格納（より安全）
        
        $query = "INSERT INTO product_stock_logs 
                  SET product_id = ?, variation_id = ?, type = ?, quantity = ?, reason = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->bindParam(2, $variation_id);
        $stmt->bindParam(3, $type);
        $stmt->bindParam(4, $abs_quantity); // 変数を使用（より安全）
        $stmt->bindParam(5, $reason);
        $stmt->execute();
    }
    
    /**
     * 受注生産商品かどうかを確認
     * 
     * 指定した商品が受注生産商品かどうかを確認し、
     * 受注生産商品の場合は受注生産期間も取得します。
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
            'is_preorder' => $row['is_preorder'] ?? 0,         // 受注生産フラグ
            'preorder_period' => $row['preorder_period'] ?? null  // 受注生産期間
        ];
    }
    
    /**
     * 改善提案:
     * 
     * 1. リレーショナルデータ取得の効率化（JOINの最適化）
     * 2. 複数商品の一括操作機能（バッチ処理）
     * 3. 商品の階層カテゴリ対応（親子カテゴリ）
     * 4. 商品の公開・非公開フラグの追加
     * 5. 商品の割引・セール機能の実装
     * 6. 商品のタグ付け機能
     * 7. キャッシュ機能の導入（パフォーマンス向上）
     * 8. フルテキスト検索機能の強化
     * 9. 商品の評価・レビューとの連携強化
     * 10. 関連商品・おすすめ商品のアルゴリズム実装
     */
}