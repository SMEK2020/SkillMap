<?php
include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

// Optional: database থেকে আরো fresh data নিতে চাও
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:user_id LIMIT 1");
$stmt->execute(['user_id'=>$user_id]);
$user = $stmt<?php
// delete_cv.php

include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$delete_id = $_GET['id'] ?? 0;

// ===== Delete CV/Notes Handling (Validation and Security) =====
$delete_id = filter_var($delete_id, FILTER_VALIDATE_INT);

if ($delete_id === false || $delete_id <= 0) {
    $_SESSION['cv_error'] = "Invalid CV ID provided for deletion.";
    header("Location: update.php");
    exit;
}

try {
    // SECURITY: user_id must be checked to ensure user deletes only their own data
    // --- Mapped cv_id to id for consistency ---
    $stmt = $conn->prepare("DELETE FROM user_cv WHERE cv_id=:id AND user_id=:user_id");
    
    $stmt->execute([
        'id' => $delete_id,
        'user_id' => $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['cv_success'] = "CV/Notes entry deleted successfully.";
    } else {
        $_SESSION['cv_error'] = "CV entry not found or access denied.";
    }

} catch (PDOException $e) {
    error_log("CV Deletion Error: " . $e->getMessage());
    $_SESSION['cv_error'] = "Database error during CV deletion.";
}

header("Location: update.php");
exit;
?>->fetch(PDO::FETCH_ASSOC);
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("DELETE FROM user_cv WHERE id=:id");
$stmt->execute(['id'=>$id]);
header("Location: update.php");
?>
