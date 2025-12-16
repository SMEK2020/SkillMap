<?php
// delete_interest.php

include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ===== Delete Career Interest Handling =====
if (isset($_GET['delete_id'])) {
    
    // Validate ID and ensure it is an integer
    $id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        $_SESSION['error_message'] = "Invalid Interest ID provided for deletion.";
        header("Location: update.php");
        exit;
    }

    try {
        // Security check: Delete only if id matches and belongs to the user_id
        $stmt = $conn->prepare("DELETE FROM career_interests WHERE id=:id AND user_id=:user_id");
        
        $stmt->execute([
            'id' => $id,
            'user_id' => $user_id
        ]);
        
        // Check if any row was actually deleted
        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Career Interest deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Interest not found or access denied.";
        }
        
    } catch (PDOException $e) {
        error_log("Interest Deletion Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during interest deletion.";
    }

} else {
    // If delete_id parameter is missing
    $_SESSION['error_message'] = "Missing Career Interest ID for deletion.";
}

header("Location: update.php");
exit;
?>