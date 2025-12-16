<?php
// update_experience.php

include '../db/db.php'; // PDO connection
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Optional: database থেকে fresh user data নিতে চাও (Keep for robustness, though not strictly required for this file's main task)
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:user_id LIMIT 1");
    $stmt->execute(['user_id'=>$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: ../Auth/login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("User Fetch Error: " . $e->getMessage());
}


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title']) && !empty(trim($_POST['title']))) {
    
    // --- Data Sanitization ---
    $title = filter_var(trim($_POST['title']), FILTER_SANITIZE_STRING);
    $organization = filter_var(trim($_POST['organization'] ?? ''), FILTER_SANITIZE_STRING);
    // Assuming date inputs are of type 'date' or 'month' (YYYY-MM-DD or YYYY-MM)
    $from_date_input = $_POST['from_date'] ?? null; 
    $to_date_input = $_POST['to_date'] ?? null;

    // --- Date Formatting ---
    // Ensure date is in YYYY-MM-DD format for database. If input is YYYY-MM, append '-01'.
    $start_date = $from_date_input ? date('Y-m-d', strtotime($from_date_input)) : null;
    $end_date   = $to_date_input ? date('Y-m-d', strtotime($to_date_input)) : null;

    // --- Database Insertion ---
    try {
        // --- CORRECTED TABLE NAME: 'experiences' changed to 'user_experiences' ---
        // --- CORRECTED COLUMN NAME: 'title' changed to 'exp_title' (as per your schema) ---
        $stmt = $conn->prepare("INSERT INTO user_experiences (user_id, exp_title, organization, start_date, end_date) 
                                 VALUES (:uid, :exp_title, :org, :start, :end)");

        $stmt->execute([
            'uid' => $user_id,
            'exp_title' => $title, // Using sanitised $title here
            'org' => $organization,
            'start' => $start_date,
            'end' => $end_date
        ]);
        
        $_SESSION['exp_success'] = "Experience added successfully!";
        
    } catch(PDOException $e) {
        error_log("Experience Insertion Error: " . $e->getMessage());
        $_SESSION['exp_error'] = "Error adding experience: Database error.";
    }

} else {
    // Validation error
     $_SESSION['exp_error'] = "Experience title cannot be empty.";
}

header("Location: update.php"); // Redirect back to profile update page
exit();