<?php
/**
 * User.php - ユーザー管理クラス
 * 
 * ユーザーアカウントの管理と操作を行うクラス。
 * 登録、認証、プロフィール更新などの機能を提供します。
 * 管理者権限の管理機能も含まれています。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
class User {
    // データベース接続とテーブル名
    private $conn;                      // データベース接続オブジェクト
    private $table_name = "users";      // ユーザーテーブル名
    
    // ユーザープロパティ
    public $id;                         // ユーザーID
    public $username;                   // ユーザー名
    public $email;                      // メールアドレス
    public $password;                   // パスワード（ハッシュ化前の平文）
    public $is_admin;                   // 管理者権限フラグ
    public $created;                    // アカウント作成日時
    
    /**
     * コンストラクタ - データベース接続を初期化
     * 
     * @param PDO $db データベース接続オブジェクト
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * ユーザー登録メソッド
     * 
     * 新規ユーザーをデータベースに登録します。
     * パスワードはBCRYPTでハッシュ化されます。
     * 
     * @return boolean 登録成功ならtrue、失敗ならfalse
     */
    public function create() {
        // INSERTクエリの準備
        $query = "INSERT INTO " . $this->table_name . " SET username=:username, email=:email, password=:password";
        $stmt = $this->conn->prepare($query);
        
        // 入力値のサニタイズ（XSS対策）
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // パスワードをBCRYPTでハッシュ化（セキュリティ強化）
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // パラメータのバインド
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        
        // クエリを実行し、結果を返す
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * ログイン認証メソッド
     * 
     * メールアドレスとパスワードでユーザーを認証します。
     * 管理者フラグも取得して、管理者かどうかを判定します。
     * 
     * @return array|false 認証成功時はユーザー情報、失敗時はfalse
     */
    public function login() {
        // ユーザー情報取得のSQLクエリ
        $query = "SELECT id, username, password, is_admin FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        // ユーザーが存在するか確認
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // パスワードを検証（ハッシュ化されたパスワードと平文パスワードの両方に対応）
            if(password_verify($this->password, $row['password']) || $this->password === $row['password']) {
                // 認証成功：ユーザー情報を返す
                return [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'is_admin' => $row['is_admin'] // 管理者フラグ
                ];
            }
        }
        
        // 認証失敗
        return false;
    }
    
    /**
     * ユーザープロフィール更新メソッド
     * 
     * ユーザー名とメールアドレスを更新します。
     * 
     * @return boolean 更新成功ならtrue、失敗ならfalse
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
        
        // パラメータをバインド
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);
        
        // クエリ実行
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * パスワード変更メソッド
     * 
     * 現在のパスワードを確認してから、新しいパスワードに更新します。
     * 
     * @param string $current_password 現在のパスワード
     * @param string $new_password 新しいパスワード
     * @return boolean 変更成功ならtrue、失敗ならfalse
     */
    public function updatePassword($current_password, $new_password) {
        // 現在のパスワードを確認
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 現在のパスワードが一致するか検証
        if(!password_verify($current_password, $row['password'])) {
            return false;  // 現在のパスワードが一致しない
        }
        
        // 新しいパスワードでアップデート
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // パスワードハッシュ化
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // パラメータをバインド
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);
        
        // クエリ実行
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * ユーザープロフィール取得メソッド
     * 
     * ユーザーIDに基づいてプロフィール情報を取得します。
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
     * ユーザー数取得メソッド
     * 
     * 全ユーザー数を取得します。（管理画面用）
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
     * 管理者ユーザー数取得メソッド
     * 
     * 管理者権限を持つユーザー数を取得します。（管理画面用）
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
     * 全ユーザー取得メソッド（管理者用）
     * 
     * すべてのユーザー情報を取得します。
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
     * ユーザーの管理者権限変更メソッド
     * 
     * ユーザーの管理者権限を変更します。
     * 
     * @param int $user_id ユーザーID
     * @param int $is_admin 管理者フラグ（0または1）
     * @return boolean 更新成功ならtrue、失敗ならfalse
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
     * ユーザー削除メソッド（管理者用）
     * 
     * ユーザーを削除します。管理者は削除できません。
     * 
     * @param int $user_id ユーザーID
     * @return boolean 削除成功ならtrue、失敗ならfalse
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
     * 最近のユーザー取得メソッド（管理者用）
     * 
     * 最近登録されたユーザーを取得します。
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
     * 管理者権限チェックメソッド
     * 
     * ユーザーが管理者権限を持っているかチェックします。
     * 
     * @param int $user_id ユーザーID
     * @return boolean 管理者ならtrue、そうでなければfalse
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
     * メールアドレスの重複チェックメソッド
     * 
     * 指定されたメールアドレスが既に使用されているかチェックします。
     * 
     * @param string $email メールアドレス
     * @param int $user_id 除外するユーザーID（編集時）
     * @return boolean 重複している場合はtrue、そうでなければfalse
     */
    public function emailExists($email, $user_id = null) {
        if($user_id) {
            // 編集時は自分自身を除外してチェック
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $email);
            $stmt->bindParam(2, $user_id);
        } else {
            // 新規登録時
            $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $email);
        }
        
        $stmt->execute();
        
        // 件数が0より大きければ重複あり
        return ($stmt->rowCount() > 0);
    }
    
    /**
     * 改善提案:
     * 
     * 1. パスワードポリシーの実装（複雑さチェック）
     * 2. 二段階認証の実装
     * 3. ログイン試行回数制限機能
     * 4. パスワードリセット機能の追加
     * 5. アカウント削除時の関連データ処理の追加
     * 6. ソーシャルログイン連携機能
     */
}