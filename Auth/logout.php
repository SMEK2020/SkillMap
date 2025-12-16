<?php
session_start();

// সব session variables clear করা
$_SESSION = [];

// session cookie delete করা (optional, তবে ভালো security জন্য)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// session destroy করা
session_destroy();

// login page-এ redirect
header("Location: login.php");
exit;
