<?php
// update_skill.php

include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

// Optional: database থেকে আরো fresh data নিতে চাও
$user_id = $_SESSION['user_id'];
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
    // Handle database error gracefully
    error_log("User Fetch Error: " . $e->getMessage());
    die("Error fetching user data.");
}


// ===== Insert Skill =====
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['skill']) && !empty(trim($_POST['skill']))){
    $skill = trim($_POST['skill']);

    try {
        // --- CORRECTED TABLE NAME: 'skills' changed to 'user_skills' ---
        $stmt = $conn->prepare("INSERT INTO user_skills(user_id, skill_name) VALUES(:user_id, :skill)");
        $stmt->execute([
            'user_id' => $user_id,
            'skill' => $skill
        ]);
        
        // Success: Redirect to the profile update page
        header("Location: update.php");
        exit;

    } catch (PDOException $e) {
        // Handle database insertion error
        error_log("Skill Insertion Error: " . $e->getMessage());
        // You might want to display an error message on update.php later
        $_SESSION['error_message'] = "Could not add skill. Database error.";
        header("Location: update.php");
        exit;
    }
} else {
    // Handle case where skill field is empty
    $_SESSION['error_message'] = "Skill field cannot be empty.";
    header("Location: update.php");
    exit;
}
?>