<?php
// delete_skill.php

session_start();
// --- Duplicate session_start() removed ---
include '../db/db.php'; // PDO connection

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ===== Delete Skill Handling =====
if(isset($_GET['id'])){ 
    
    $delete_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($delete_id === false || $delete_id <= 0) {
        $_SESSION['error_message'] = "Invalid skill ID for deletion.";
        header("Location: update.php");
        exit;
    }

    try {
        // --- CORRECTED TABLE NAME: 'skills' changed to 'user_skills' ---
        // --- Mapped skill_id to id for consistency ---
        // SECURITY: user_id must be checked to ensure user deletes only their own data
        $stmt = $conn->prepare("DELETE FROM user_skills WHERE skill_id=:id AND user_id=:user_id");
        
        $stmt->execute([
            'id' => $delete_id,
            'user_id' => $user_id
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success_message'] = "Skill deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Skill not found or access denied.";
        }

        header("Location: update.php"); // Delete হয়ে গেলে main page এ redirect
        exit;

    } catch (PDOException $e) {
        error_log("Skill Deletion Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during skill deletion.";
        header("Location: update.php");
        exit;
    }

} else {
    $_SESSION['error_message'] = "No skill ID specified for deletion.";
    header("Location: update.php");
    exit;
}
?>