<?php
/**
 * Category.php - カテゴリ管理クラス
 * 
 * 商品カテゴリの管理と操作を行うクラスです。
 * カテゴリの取得や個別カテゴリの情報取得機能を提供します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
class Category {
    // データベース接続とテーブル名
    private $conn;                       // データベース接続オブジェクト
    private $table_name = "categories";  // カテゴリテーブル名
    
    // プロパティ
    public $id;                         // カテゴリID
    public $name;                       // カテゴリ名
    public $description;                // カテゴリの説明
    public $created;                    // 作成日時
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * カテゴリー全取得メソッド
     * 
     * すべてのカテゴリをアルファベット順で取得します。
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
     * 単一カテゴリー取得メソッド
     * 
     * 指定したIDのカテゴリ情報を取得します。
     * 
     * @return boolean 取得成功ならtrue、失敗（該当カテゴリなし）ならfalse
     */
    public function readOne() {
        $query = "SELECT id, name, description FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // プロパティに値をセット
            $this->name = $row['name'];
            $this->description = $row['description'];
            return true;
        }
        
        return false;
    }
    
    /**
     * 改善提案:
     * 
     * 1. カテゴリ作成メソッドの追加
     * 2. カテゴリ更新メソッドの追加
     * 3. カテゴリ削除メソッドの追加（関連商品の処理を含む）
     * 4. カテゴリごとの商品数取得メソッドの追加
     * 5. 親子関係を持つ階層カテゴリの実装
     * 6. カテゴリ画像のサポート追加
     */
}