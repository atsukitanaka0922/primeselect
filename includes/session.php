<?php
function sec_session_start() {
    $session_name = 'secure_session';
    $secure = true; // HTTPSの場合はtrue
    $httponly = true;
    
    if(ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session");
        exit();
    }
    
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params(
        $cookieParams["lifetime"],
        $cookieParams["path"],
        $cookieParams["domain"],
        $secure,
        $httponly
    );
    
    session_name($session_name);
    session_start();
    session_regenerate_id();
}

function check_login_status() {
    if(isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

function redirect_if_not_logged_in() {
    if(!check_login_status()) {
        // 現在のURLを保存
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}
?>