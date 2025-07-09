<?php
/**
 * includes/admin_auth.php
 * 
 * 管理者権限のチェックや認証に関する共通関数を提供します。
 * 管理者ページで使用される権限チェック機能を一元管理します。
 * 
 * @package PrimeSelect
 * @author Prime Select Team
 * @version 1.0
 */

/**
 * 管理者権限チェック関数
 * 
 * ユーザーが管理者権限を持っているかチェックし、
 * 権限がない場合はログインページにリダイレクトします。
 * 
 * @param boolean $redirect_on_fail 失敗時にリダイレクトするかどうか
 * @return boolean 管理者ならtrue、そうでなければfalse
 */
function checkAdminAuth($redirect_on_fail = true) {
    // セッションにユーザーIDがセットされているか、かつ管理者フラグが1かチェック
    if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
        // リダイレクトフラグがtrueの場合は、ログインページにリダイレクト
        if($redirect_on_fail) {
            // 管理者でない場合はログインページへリダイレクト
            header('Location: ../login.php');
            exit(); // 処理を終了
        }
        return false; // 管理者でない
    }
    return true; // 管理者である
}

/**
 * 一般ユーザー権限チェック関数
 * 
 * ユーザーが一般ユーザーであるかチェックします。
 * 管理者サイドバー表示を防止するために使用されます。
 * 
 * @return boolean 一般ユーザーならtrue、そうでなければfalse
 */
function isRegularUser() {
    // セッションにユーザーIDが設定されており、かつ管理者フラグが1でないことを確認
    return isset($_SESSION['user_id']) && $_SESSION['is_admin'] != 1;
}

/**
 * 管理者権限チェック関数（サイドバー表示用）
 * 
 * ユーザーが管理者であるかチェックします。
 * 管理者サイドバーの表示判定に使用されます。
 * 
 * @return boolean 管理者ならtrue、そうでなければfalse
 */
function isAdmin() {
    // セッションにユーザーIDが設定されており、かつ管理者フラグが1であることを確認
    return isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 1;
}

/**
 * 改善提案:
 * 
 * 1. ロールベースのアクセス制御（RBAC）を実装して、より詳細な権限管理を実現
 * 2. 複数の管理者レベル（スーパー管理者、商品管理者、注文管理者など）を設定
 * 3. 権限チェック失敗時に元のURLを保存し、ログイン後にリダイレクトする機能
 * 4. 管理者アクション履歴のログ機能
 * 5. セッションのセキュリティ強化（HTTPSのみ、HttpOnlyフラグの設定など）
 */