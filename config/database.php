<?php
/**
 * データベース接続クラス
 * 
 * アプリケーション全体で使用するデータベース接続を管理します。
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class Database {
    // データベース接続情報
    private $host = "localhost";
    private $db_name = "ecommerce_db";
    private $username = "root";
    private $password = "";
    public $conn;
    
    /**
     * データベース接続を取得する
     * 
     * @return PDO|null データベース接続オブジェクト、接続失敗時はnull
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // PDOを使用してデータベースに接続
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // 接続エラーの処理
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>