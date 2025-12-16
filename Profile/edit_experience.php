<?php
// edit_experience.php

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
    $_SESSION['exp_error'] = "Invalid Experience ID provided.";
    header("Location: update.php");
    exit;
}

// ===== 1. Fetch experience data (using user_experiences table) =====
$data = null;
try {
    // --- CORRECTED TABLE NAME: 'experiences' changed to 'user_experiences' ---
    // --- CORRECTED COLUMN NAME: 'title' changed to 'exp_title' ---
    // Mapping exp_id AS id and exp_title AS title for easier HTML use
    $stmt = $conn->prepare("SELECT exp_id AS id, exp_title AS title, organization, start_date, end_date 
                             FROM user_experiences 
                             WHERE exp_id=:id AND user_id=:user_id LIMIT 1"); // Added user_id check
    $stmt->execute(['id'=>$id, 'user_id'=>$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$data){
        $_SESSION['exp_error'] = "Experience not found or access denied!";
        header("Location: update.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Experience Fetch Error: " . $e->getMessage());
    $_SESSION['exp_error'] = "Database error while fetching experience data.";
    header("Location: update.php");
    exit;
}


// Convert DATE (YYYY-MM-DD) to YYYY-MM for <input type="month">
$from_month = $data['start_date'] ? date('Y-m', strtotime($data['start_date'])) : '';
$to_month = $data['end_date'] ? date('Y-m', strtotime($data['end_date'])) : '';


// ===== 2. Handle form submission (Update) =====
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    // --- Data Sanitization ---
    $title = filter_var(trim($_POST['title'] ?? ''), FILTER_SANITIZE_STRING);
    $organization = filter_var(trim($_POST['organization'] ?? ''), FILTER_SANITIZE_STRING);
    $from_date_input = $_POST['from_date'] ?? null;
    $to_date_input = $_POST['to_date'] ?? null;
    
    // Convert YYYY-MM to YYYY-MM-01 for DATE column
    $start_date = $from_date_input ? $from_date_input . '-01' : null;
    $end_date   = $to_date_input ? $to_date_input . '-01' : null;

    if (empty($title)) {
        $_SESSION['exp_error'] = "Title cannot be empty.";
        header("Location: edit_experience.php?id=" . $id);
        exit;
    }
    
    try {
        // --- CORRECTED TABLE NAME: 'experiences' changed to 'user_experiences' ---
        // --- CORRECTED COLUMN NAME: 'title' changed to 'exp_title' ---
        $stmt = $conn->prepare("UPDATE user_experiences 
             SET exp_title=:title, organization=:organization, start_date=:start_date, end_date=:end_date 
             WHERE exp_id=:id AND user_id=:user_id"); // Added user_id check for security
        
        $stmt->execute([
            'title'=>$title,
            'organization'=>$organization,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'id'=>$id,
            'user_id'=>$user_id
        ]);
        
        $_SESSION['exp_success'] = "Experience updated successfully!";
        header("Location: update.php");
        exit;

    } catch (PDOException $e) {
        error_log("Experience Update Error: " . $e->getMessage());
        $_SESSION['exp_error'] = "Database error during experience update.";
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
<title>Update Experience</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
    /* ... CSS styles remain the same ... */
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
        background-color: #2563EB; /* Adjusted color */
        color: #fff;
        font-weight: 600;
        font-size: 1.2rem;
        border-radius: 12px 12px 0 0;
    }
    .form-label {
        font-weight: 500;
    }
    .btn-primary-custom {
        background-color: #2563EB;
        border-color: #2563EB;
    }
    .btn-primary-custom:hover {
        background-color: #1E40AF;
    }
</style>
</head>
<body>

<div class="container my-5">
    <?php 
    // Display Session Messages
    if(isset($_SESSION['exp_error'])): ?>
        <div class="alert alert-danger mb-3" role="alert">
            <?= htmlspecialchars($_SESSION['exp_error']); unset($_SESSION['exp_error']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="fa-solid fa-briefcase me-2"></i> Update Experience
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($data['title']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Organization</label>
                        <input type="text" name="organization" class="form-control" value="<?= htmlspecialchars($data['organization']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">From</label>
                        <input type="month" name="from_date" class="form-control" value="<?= $from_month ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To</label>
                        <input type="month" name="to_date" class="form-control" value="<?= $to_month ?>" required>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
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