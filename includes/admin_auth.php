<?php
// includes/admin_auth.php を作成
// 管理者ページで使用する権限チェック関数

/**
 * 管理者権限チェック
 * 
 * @param boolean $redirect_on_fail 失敗時にリダイレクトするか
 * @return boolean 管理者ならtrue、そうでなければfalse
 */
function checkAdminAuth($redirect_on_fail = true) {
    if(!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
        if($redirect_on_fail) {
            // 管理者でない場合はログインページへリダイレクト
            header('Location: ../login.php');
            exit();
        }
        return false;
    }
    return true;
}

/**
 * ユーザー権限チェック（管理者サイドバー表示防止用）
 * 
 * @return boolean 一般ユーザーならtrue
 */
function isRegularUser() {
    return isset($_SESSION['user_id']) && $_SESSION['is_admin'] != 1;
}

/**
 * 管理者権限チェック（サイドバー表示用）
 * 
 * @return boolean 管理者ならtrue
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 1;
}
?>