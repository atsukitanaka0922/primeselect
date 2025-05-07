<?php
/**
 * カテゴリクラス
 * 
 * 商品カテゴリの管理と操作を行うクラス
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Category {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "categories";
    
    // プロパティ
    public $id;
    public $name;
    public $description;
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
     * カテゴリー全取得
     * 
     * @return PDOStatement 結果セット
     */
    public function read() {
        $query = "SELECT id, name, description FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 単一カテゴリー取得
     * 
     * @return boolean 取得成功ならtrue
     */
    public function readOne() {
        $query = "SELECT id, name, description FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            return true;
        }
        
        return false;
    }
}
?>