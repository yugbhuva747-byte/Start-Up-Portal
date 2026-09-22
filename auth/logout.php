<?php
/**
 * Authentication: Logout
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    $u = current_user();
    if ($u) {
        log_audit($u['id'], 'USER_LOGOUT', 'users', $u['id'], 'User logged out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

header('Location: ' . url('auth/login.php'));
exit;
