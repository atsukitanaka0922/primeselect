<?php
/**
 * Database.php - データベース接続クラス
 * 
 * アプリケーション全体で使用されるデータベース接続を管理するクラス。
 * シングルトンパターンではなく、必要に応じて接続を作成する設計になっています。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */
class Database {
    // データベース接続情報
    private $host = "localhost";         // データベースホスト
    private $db_name = "primeselect";    // データベース名
    private $username = "root";          // ユーザー名
    private $password = "";              // パスワード
    public $conn;                       // データベース接続オブジェクト
    
    /**
     * データベース接続を取得するメソッド
     * 
     * PDOを使用してデータベースへの接続を確立します。
     * エラー処理も含まれており、接続に失敗した場合はエラーメッセージを表示します。
     * 
     * @return PDO|null データベース接続オブジェクト、接続失敗時はnull
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            // PDOを使用してMySQLデータベースに接続
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            
            // 文字セットをUTF-8に設定（多言語サポート用）
            $this->conn->exec("set names utf8");
            
            // 以下の設定も検討可能:
            // $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            // 接続エラーの処理 - エラーメッセージを表示
            // 本番環境では、詳細なエラーをログに記録し、ユーザーにはジェネリックなメッセージを表示すべき
            echo "Connection error: " . $exception->getMessage();
            
            // 改善案: エラーログに記録し、ユーザーフレンドリーなメッセージを表示
            // error_log("Database connection error: " . $exception->getMessage());
            // echo "データベースへの接続に問題が発生しました。管理者にお問い合わせください。";
        }
        
        return $this->conn;
    }
    
    /**
     * 改善提案:
     * 
     * 1. 設定を外部ファイルに保存し、環境ごとに切り替え可能にする
     * 2. トランザクション管理用のヘルパーメソッドを追加
     * 3. 接続のクローズを明示的に行うメソッドを追加
     * 4. プリペアドステートメントのキャッシュを有効にする設定の追加
     * 5. 接続プーリングの実装（高負荷向け）
     */
}