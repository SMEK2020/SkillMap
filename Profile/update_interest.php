<?php
// update_interest.php

include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Optional: database থেকে fresh data নিতে চাও
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


// ===== 1. Career Interests Update Handling (edit_interest.php থেকে POST হলে) =====
if(isset($_POST['update_interest'])){
    
    // Data Sanitization
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $role_title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $field = filter_var($_POST['field'], FILTER_SANITIZE_STRING);
    $focus_area = filter_var($_POST['focus_area'], FILTER_SANITIZE_STRING);
    $goal = filter_var($_POST['goal'], FILTER_SANITIZE_STRING);

    if ($id === false || empty($role_title)) {
        $_SESSION['error_message'] = "Invalid data provided for update.";
        header("Location: update.php");
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE career_interests 
                                SET role_title=:role_title, field=:field, focus_area=:focus_area, goal=:goal 
                                WHERE id=:id AND user_id=:user_id"); // Added user_id check for security
        $stmt->execute([
            'role_title'=>$role_title,
            'field'=>$field,
            'focus_area'=>$focus_area,
            'goal'=>$goal,
            'id'=>$id,
            'user_id'=>$user_id
        ]);
        
        $_SESSION['success_message'] = "Career interest updated successfully.";
        // Redirecting to update.php after action
        header("Location: update.php"); 
        exit;

    } catch (PDOException $e) {
        error_log("Interest Update Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error updating career interest.";
        header("Location: update.php");
        exit;
    }
}

// ===== 2. Career Interests Insert Handling (update.php থেকে POST হলে) =====
if(isset($_POST['add_interest'])){
    
    // Data Sanitization
    $role_title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $field = filter_var($_POST['field'], FILTER_SANITIZE_STRING);
    $focus_area = filter_var($_POST['focus_area'], FILTER_SANITIZE_STRING);
    $goal = filter_var($_POST['goal'], FILTER_SANITIZE_STRING);

    if (empty($role_title)) {
        $_SESSION['error_message'] = "Role title is required.";
        header("Location: update.php");
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO career_interests(user_id, role_title, field, focus_area, goal) 
                                VALUES(:user_id, :role_title, :field, :focus_area, :goal)");
        $stmt->execute([
            'user_id'=>$user_id,
            'role_title'=>$role_title,
            'field'=>$field,
            'focus_area'=>$focus_area,
            'goal'=>$goal
        ]);
        
        $_SESSION['success_message'] = "Career interest added successfully.";
        header("Location: update.php");
        exit;

    } catch (PDOException $e) {
        error_log("Interest Insert Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error adding career interest.";
        header("Location: update.php");
        exit;
    }
}


// ===== 3. Delete handling (Delete_interest.php থেকে GET হলে) =====
// NOTE: Deletion should ideally be handled via a dedicated delete file or a POST request for better security.
if(isset($_GET['delete_id'])){
    $delete_id = filter_var($_GET['delete_id'], FILTER_VALIDATE_INT);
    
    if ($delete_id === false) {
        $_SESSION['error_message'] = "Invalid delete request.";
        header("Location: update.php");
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM career_interests WHERE id=:id AND user_id=:user_id"); // Added user_id check
        $stmt->execute([
            'id'=>$delete_id,
            'user_id'=>$user_id
        ]);

        $_SESSION['success_message'] = "Career interest deleted successfully.";
        header("Location: update.php");
        exit;
    } catch (PDOException $e) {
        error_log("Interest Delete Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error deleting career interest.";
        header("Location: update.php");
        exit;
    }
}


// ===== 4. For editing, fetch single interest (edit_interest.php page-er jonno) =====
$editData = null;
if(isset($_GET['edit_id'])){
    $edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT);

    if ($edit_id !== false) {
        try {
            $stmt = $conn->prepare("SELECT * FROM career_interests WHERE id=:id AND user_id=:user_id"); // Added user_id check
            $stmt->execute([
                'id'=>$edit_id,
                'user_id'=>$user_id
            ]);
            $editData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$editData) {
                $_SESSION['error_message'] = "Interest not found or access denied.";
                header("Location: update.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Interest Fetch Error: " . $e->getMessage());
            $_SESSION['error_message'] = "Error fetching interest data for editing.";
            header("Location: update.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid ID for editing.";
        header("Location: update.php");
        exit;
    }
}

// If this file is intended to display the edit form, the HTML part goes here. 
// Since no HTML was provided, only the PHP logic is cleaned up.
?>