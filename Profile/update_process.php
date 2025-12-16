<?php
// update_process.php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

// Security: Use absolute path for reliable inclusion
include __DIR__ . '/../db/db.php'; 

$user_id = $_SESSION['user_id'];

// Initial fetch is not strictly necessary for update, but kept for context/error checking
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:user_id LIMIT 1");
    $stmt->execute(['user_id'=>$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Should not happen if session is valid, but good practice
        session_destroy();
        header("Location: ../Auth/login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Initial User Fetch Error: " . $e->getMessage());
    $_SESSION['update_error'] = "Database error during profile load.";
    header("Location: update.php");
    exit;
}


if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Data Sanitization and Validation (Mandatory for security)
    $fullname = filter_var($_POST['fullname'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $location = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
    $edu_level = filter_var($_POST['edu_level'], FILTER_SANITIZE_STRING);
    $experience_level = filter_var($_POST['experience_level'], FILTER_SANITIZE_STRING);
    $preferred_track = filter_var($_POST['preferred_track'], FILTER_SANITIZE_STRING);

    try {
        // --- DATABASE UPDATE ---
        // Table name 'users' is correct as per your schema
        $stmt = $conn->prepare("UPDATE users SET 
                                fullname=:fullname, 
                                email=:email, 
                                phone=:phone, 
                                location=:location, 
                                edu_level=:edu_level, 
                                experience_level=:experience_level, 
                                preferred_track=:preferred_track 
                                WHERE user_id=:id");
        
        $stmt->execute([
            'fullname' => $fullname,
            'email' => $email,
            'phone' => $phone,
            'location' => $location,
            'edu_level' => $edu_level,
            'experience_level' => $experience_level,
            'preferred_track' => $preferred_track,
            'id' => $user_id
        ]);

        $_SESSION['update_success'] = "Profile updated successfully!";
        header("Location: update.php");
        exit;

    } catch (PDOException $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        // Check for specific unique constraint violation (e.g., email)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
             $_SESSION['update_error'] = "Error: The email address is already registered.";
        } else {
             $_SESSION['update_error'] = "An error occurred while updating your profile.";
        }
        header("Location: update.php");
        exit;
    }
} else {
    // If accessed via GET instead of POST
    header("Location: update.php");
    exit;
}
?>