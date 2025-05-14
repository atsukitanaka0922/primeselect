<?php
/**
 * ユーザークラス（修正版）
 * 
 * ユーザー情報の管理と操作を行うクラス
 * 管理者フラグの対応を含む
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
    public $is_admin;
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
     * ログイン認証（管理者フラグ対応）
     * 
     * @return array|false 認証成功時はユーザー情報、失敗時はfalse
     */
    public function login() {
        // is_adminフィールドも取得するように修正
        $query = "SELECT id, username, password, is_admin FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($this->password, $row['password'])) {
                // is_adminフィールドも返すように修正
                return [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'is_admin' => $row['is_admin']
                ];
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
        $query = "SELECT id, username, email, is_admin, created FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->is_admin = $row['is_admin'];
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
    
    /**
     * 管理者ユーザー数取得
     * 
     * @return int 管理者ユーザー数
     */
    public function countAdmins() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_admin = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    
    /**
     * 全ユーザー取得（管理者用）
     * 
     * @return PDOStatement 結果セット
     */
    public function readAll() {
        $query = "SELECT id, username, email, is_admin, created FROM " . $this->table_name . " ORDER BY created DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * ユーザーの管理者権限変更（デバッグログ付き修正版）
     * 
     * @param int $user_id ユーザーID
     * @param int $is_admin 管理者フラグ（0または1）
     * @return boolean 更新成功ならtrue
     */
    public function updateAdminStatus($user_id, $is_admin) {
        // 入力値の検証
        if (!is_numeric($user_id)) {
            error_log("Invalid user_id: $user_id");
            return false;
        }
        
        $is_admin = intval($is_admin);
        error_log("Updating admin status: user_id=$user_id, is_admin=$is_admin");
        
        $query = "UPDATE " . $this->table_name . " SET is_admin = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $is_admin);
        $stmt->bindParam(2, $user_id);
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Admin status update failed: " . implode(", ", $stmt->errorInfo()));
        } else {
            error_log("Admin status update successful");
        }
        
        return $success;
    }
    
    /**
     * ユーザー削除（管理者用）
     * 
     * @param int $user_id ユーザーID
     * @return boolean 削除成功ならtrue
     */
    public function delete($user_id) {
        // 削除しようとするのが管理者かチェック
        $query = "SELECT is_admin FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 管理者は削除できない
        if($row && $row['is_admin'] == 1) {
            return false;
        }
        
        // ユーザー削除
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * 最近のユーザー取得（管理者用）
     * 
     * @param int $limit 取得件数
     * @return PDOStatement 結果セット
     */
    public function getRecentUsers($limit = 10) {
        $query = "SELECT id, username, email, is_admin, created FROM " . $this->table_name . " 
                ORDER BY created DESC LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * 管理者権限チェック
     * 
     * @param int $user_id ユーザーID
     * @return boolean 管理者ならtrue
     */
    public function isAdmin($user_id) {
        $query = "SELECT is_admin FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($row && $row['is_admin'] == 1);
    }
    
    /**
     * メールアドレスの重複チェック
     * 
     * @param string $email メールアドレス
     * @param int $user_id 除外するユーザーID（編集時）
     * @return boolean 重複している場合はtrue
     */
    public function emailExists($email, $user_id = null) {
        if($user_id) {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $email);
            $stmt->bindParam(2, $user_id);
        } else {
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $email);
        }
        
        $stmt->execute();
        
        return ($stmt->rowCount() > 0);
    }
}
?>