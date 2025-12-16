<?php
// update_cv.php

include '../db/db.php'; // PDO connection
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// Data Sanitization: CV text filter kora hocche
$cv_text = filter_var($_POST['cv_text'] ?? '', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES); 

// Error/Success messages storage
$message_key = 'cv_success';
$message_value = "CV/Notes saved successfully.";

try {
    // 1. Check if CV entry already exists for the user
    $stmt = $conn->prepare("SELECT cv_id FROM user_cv WHERE user_id=:id LIMIT 1");
    $stmt->execute(['id'=>$user_id]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if($exists){
        // 2. UPDATE existing entry
        $stmt = $conn->prepare("UPDATE user_cv SET cv_text=:cv WHERE user_id=:id");
        $stmt->execute(['cv'=>$cv_text, 'id'=>$user_id]);
        $message_value = "CV/Notes updated successfully.";
    } else {
        // 3. INSERT new entry
        $stmt = $conn->prepare("INSERT INTO user_cv(user_id, cv_text) VALUES(:id, :cv)");
        $stmt->execute(['id'=>$user_id, 'cv'=>$cv_text]);
        $message_value = "CV/Notes inserted successfully.";
    }

    $_SESSION[$message_key] = $message_value;

} catch (PDOException $e) {
    error_log("CV Update/Insert Error: " . $e->getMessage());
    $_SESSION['cv_error'] = "Error saving CV/Notes: Database operation failed.";
}

header("Location: update.php");
exit();
?>