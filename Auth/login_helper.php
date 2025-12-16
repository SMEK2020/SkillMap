<?php
session_start();
require_once('../db/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = $conn->prepare("SELECT user_id, fullname, email, password FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['fullname'];
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Invalid password
            header("Location: login.php?error=invalid_pass");
            exit;
        }
    } else {
        // User not registered -> redirect to registration page
        header("Location: register.php?error=not_registered");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
