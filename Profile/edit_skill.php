<?php
// edit_skill.php

include '../db/db.php'; // PDO connection
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;
$id = filter_var($id, FILTER_VALIDATE_INT); // ID validation

if ($id === false || $id <= 0) {
    $_SESSION['error_message'] = "Invalid skill ID provided.";
    header("Location: update.php");
    exit;
}

// ===== 1. Fetch existing skill (using user_skills table and skill_id) =====
try {
    // --- CORRECTED TABLE NAME: 'skills' changed to 'user_skills' ---
    // --- Mapped skill_id to id for consistency ---
    $stmt = $conn->prepare("SELECT skill_id AS id, skill_name FROM user_skills WHERE skill_id=:id AND user_id=:user_id");
    $stmt->execute(['id'=>$id, 'user_id'=>$user_id]); // Added user_id check
    $skillData = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$skillData){
        $_SESSION['error_message'] = "Skill not found or access denied!";
        header("Location: update.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Skill Fetch Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error while fetching skill.";
    header("Location: update.php");
    exit;
}


// ===== 2. Handle form submission (Update) =====
if($_SERVER['REQUEST_METHOD']=='POST'){
    
    $skill = filter_var($_POST['skill'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($skill)) {
        $_SESSION['error_message'] = "Skill name cannot be empty.";
        header("Location: edit_skill.php?id=" . $id);
        exit;
    }

    try {
        // --- CORRECTED TABLE NAME: 'skills' changed to 'user_skills' ---
        // --- CORRECTED COLUMN NAME: 'skill' changed to 'skill_name' ---
        $stmt = $conn->prepare("UPDATE user_skills SET skill_name=:skill_name WHERE skill_id=:id AND user_id=:user_id");
        $stmt->execute([
            'skill_name'=>$skill, 
            'id'=>$id,
            'user_id'=>$user_id // Added user_id check for security
        ]);

        $_SESSION['success_message'] = "Skill updated successfully!";
        header("Location: update.php");
        exit;
    } catch (PDOException $e) {
        error_log("Skill Update Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during skill update.";
        header("Location: update.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Skill</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background-color: #f0f4f8;
    font-family: 'Inter', sans-serif;
}
.card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.card-header {
    background-color: #2563EB; /* Adjusted color to match update.php primary color */
    color: #fff;
    font-weight: 600;
    font-size: 1.2rem;
    border-radius: 12px 12px 0 0;
}
.btn-primary-custom {
    background-color: #2563EB;
    border-color: #2563EB;
}
.btn-primary-custom:hover {
    background-color: #1E40AF; /* Darker hover state */
}
</style>
</head>
<body>

<div class="container my-5">
    <?php 
    // Display Session Messages (Success/Error)
    if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger mx-auto mb-3" style="max-width: 500px;">
            <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; 
    // $skillData will always be present here due to the check above
    ?>
    <div class="card mx-auto" style="max-width: 500px;">
        <div class="card-header d-flex align-items-center">
            <i class="fa-solid fa-code me-2"></i> Update Skill
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Skill Name</label>
                    <input type="text" name="skill" class="form-control" value="<?= htmlspecialchars($skillData['skill_name']) ?>" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom"><i class="fa-solid fa-save me-1"></i> Update</button>
                    <a href="update.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>