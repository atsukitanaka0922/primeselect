<?php
/**
 * ユーザークラス
 * 
 * ユーザー情報の管理と操作を行うクラス
 * 
 * @author Prime Select Team
 * @version 1.0
 */
class User {
    // データベース接続とテーブル名
    private $conn;
    private $table_name = "users";
    
    // プロパティ
    public $id;
    public $username;
    public $email;
    public $password;
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
     * ユーザー登録
     * 
     * @return boolean 登録成功ならtrue
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET username=:username, email=:email, password=:password";
        $stmt = $this->conn->prepare($query);
        
        // サニタイズ
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // パスワードハッシュ化
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * ログイン認証
     * 
     * @return array|false 認証成功時はユーザー情報、失敗時はfalse
     */
    
    public function login() {
        $query = "SELECT id, username, password FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }
    
    /**
     * ユーザープロフィール更新
     * 
     * @return boolean 更新成功ならtrue
     */
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                SET username = :username, 
                    email = :email 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // サニタイズ
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // バインド
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * パスワード変更
     * 
     * @param string $current_password 現在のパスワード
     * @param string $new_password 新しいパスワード
     * @return boolean 変更成功ならtrue
     */
    public function updatePassword($current_password, $new_password) {
        // 現在のパスワードを確認
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!password_verify($current_password, $row['password'])) {
            return false;  // 現在のパスワードが一致しない
        }
        
        // 新しいパスワードでアップデート
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // パスワードハッシュ化
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // バインド
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * ユーザープロフィール取得
     */
    public function getUser() {
        $query = "SELECT id, username, email, created FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->created = $row['created'];
        }
    }
    
    /**
     * ユーザー数取得
     * 
     * @return int ユーザー数
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>