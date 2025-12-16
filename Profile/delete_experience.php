<?php
// delete_experience.php

include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$delete_id = $_GET['id'] ?? 0;

// ===== Delete Experience Handling (Validation and Security) =====
$delete_id = filter_var($delete_id, FILTER_VALIDATE_INT);

if ($delete_id === false || $delete_id <= 0) {
    $_SESSION['exp_error'] = "Invalid Experience ID provided for deletion.";
    header("Location: update.php");
    exit;
}

try {
    // --- CORRECTED TABLE NAME: 'experiences' changed to 'user_experiences' ---
    // --- Mapped exp_id to id for consistency ---
    // SECURITY: user_id must be checked to ensure user deletes only their own data
    $stmt = $conn->prepare("DELETE FROM user_experiences WHERE exp_id=:id AND user_id=:user_id");
    
    $stmt->execute([
        'id' => $delete_id,
        'user_id' => $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['exp_success'] = "Experience deleted successfully.";
    } else {
        $_SESSION['exp_error'] = "Experience not found or access denied.";
    }

} catch (PDOException $e) {
    error_log("Experience Deletion Error: " . $e->getMessage());
    $_SESSION['exp_error'] = "Database error during experience deletion.";
}

header("Location: update.php");
exit;
?>