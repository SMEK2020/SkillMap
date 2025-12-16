<?php
// photo_process.php (Assumed file name)

session_start(); // ✅ Only once at the top

include '../db/db.php'; // PDO connection

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Optional: database থেকে fresh data নিতে চাও
try {
    $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE user_id=:user_id LIMIT 1");
    $stmt->execute(['user_id'=>$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: ../Auth/login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("User Fetch Error: " . $e->getMessage());
    $_SESSION['photo_error'] = "Error loading user data.";
    header("Location: update.php");
    exit;
}


// --- Upload / Update Photo ---
if(isset($_POST['upload_photo']) && isset($_FILES['profilePhoto'])){
    $file = $_FILES['profilePhoto'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['photo_error'] = "File upload failed with error code: " . $file['error'];
        header("Location: update.php");
        exit;
    }

    $filename = $file['name'];
    $tmpname = $file['tmp_name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png'];

    if(in_array($ext, $allowed)){
        // Create a unique file name based on user ID and timestamp (more secure)
        $newName = "user_".$user_id."_".time().".".$ext; 
        $destination = "../Image/".$newName;
        
        if(move_uploaded_file($tmpname, $destination)){
            try {
                // --- CORRECTED COLUMN NAME: 'photo' changed to 'profile_photo' ---
                $stmt = $conn->prepare("UPDATE users SET profile_photo=:photo WHERE user_id=:id");
                $stmt->execute(['photo'=>$newName, 'id'=>$user_id]);

                // Update session variable immediately (if used in update.php)
                $_SESSION['profile_photo'] = $newName; 
                $_SESSION['photo_success'] = "Profile photo updated successfully.";

            } catch (PDOException $e) {
                error_log("DB Update Error: " . $e->getMessage());
                $_SESSION['photo_error'] = "Database update failed.";
            }

        } else {
            $_SESSION['photo_error'] = "File move failed! Check folder permissions.";
        }
    } else {
        $_SESSION['photo_error'] = "Only JPG, JPEG, PNG formats are allowed.";
    }

    header("Location: update.php");
    exit;
}

// --- Remove Photo ---
if(isset($_POST['remove_photo'])){
    try {
        // Get existing photo file name
        $existing_photo = $user['profile_photo'] ?? null;

        // Delete photo file if it exists and is not the placeholder
        if ($existing_photo && $existing_photo !== 'placeholder-image-person-jpg.jpg') {
            $file_path = "../Image/".$existing_photo;
            if(file_exists($file_path) && !unlink($file_path)){
                // Handle deletion failure but proceed with DB update
                error_log("Failed to delete old file: " . $file_path);
            }
        }
        
        // Reset to default photo name in DB
        $default_photo = 'placeholder-image-person-jpg.jpg';
        // --- CORRECTED COLUMN NAME: 'photo' changed to 'profile_photo' ---
        $stmt = $conn->prepare("UPDATE users SET profile_photo=:photo WHERE user_id=:id");
        $stmt->execute(['photo'=>$default_photo, 'id'=>$user_id]);

        // Update session variable
        $_SESSION['profile_photo'] = $default_photo;
        $_SESSION['photo_success'] = "Profile photo removed.";

    } catch (PDOException $e) {
        error_log("Photo Remove DB Error: " . $e->getMessage());
        $_SESSION['photo_error'] = "Error resetting photo in database.";
    }

    header("Location: update.php");
    exit;
}
?>