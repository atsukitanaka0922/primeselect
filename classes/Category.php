<?php
class Category {
    private $conn;
    private $table_name = "categories";
    
    public $id;
    public $name;
    public $description;
    public $created;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // カテゴリー全取得
    public function read() {
        $query = "SELECT id, name, description FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // 単一カテゴリー取得
    public function readOne() {
        $query = "SELECT id, name, description FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
        }
    }
}
?>