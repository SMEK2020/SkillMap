<?php
// edit_interest.php

include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ===== 1. Fetch single interest for editing (Validation) =====

// Check if edit_id is set and is a valid integer
if(!isset($_GET['edit_id']) || !($edit_id = filter_var($_GET['edit_id'], FILTER_VALIDATE_INT))){
    $_SESSION['error_message'] = "Invalid access or missing interest ID.";
    header("Location: update.php"); 
    exit;
}

try {
    // Fetch existing interest data, ensuring it belongs to the logged-in user
    $stmt = $conn->prepare("SELECT * FROM career_interests WHERE id=:id AND user_id=:user_id LIMIT 1");
    $stmt->execute(['id'=>$edit_id, 'user_id'=>$user_id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$editData){
        $_SESSION['error_message'] = "Career Interest not found or access denied!";
        header("Location: update.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Interest Fetch Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error while fetching interest data.";
    header("Location: update.php");
    exit;
}


// ===== 2. Handle form submission (Update) =====
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_interest'])){
    
    // --- Data Sanitization and Validation ---
    $role_title = filter_var(trim($_POST['title'] ?? ''), FILTER_SANITIZE_STRING);
    $field = filter_var(trim($_POST['field'] ?? ''), FILTER_SANITIZE_STRING);
    $focus_area = filter_var(trim($_POST['focus_area'] ?? ''), FILTER_SANITIZE_STRING);
    $goal = filter_var(trim($_POST['goal'] ?? ''), FILTER_SANITIZE_STRING);

    if (empty($role_title) || empty($field) || empty($goal)) {
        $_SESSION['error_message'] = "All fields are required to update career interest.";
        header("Location: edit_interest.php?edit_id=" . $edit_id);
        exit;
    }

    try {
        // Update database, ensuring user_id is checked
        $stmt = $conn->prepare("UPDATE career_interests 
                                 SET role_title=:role_title, field=:field, focus_area=:focus_area, goal=:goal
                                 WHERE id=:id AND user_id=:user_id");
        $stmt->execute([
            'role_title'=>$role_title,
            'field'=>$field,
            'focus_area'=>$focus_area,
            'goal'=>$goal,
            'id'=>$edit_id,
            'user_id'=>$user_id
        ]);
        
        $_SESSION['success_message'] = "Career interest updated successfully!";
        header("Location: update.php");
        exit;

    } catch (PDOException $e) {
        error_log("Interest Update Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error during interest update.";
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
<title>Edit Career Interest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background-color:#F8FAFC; font-family:'Inter',sans-serif; }
.card { border-radius:10px; border:1px solid #DCECFD; box-shadow:0 4px 10px rgba(37,99,235,0.1); background:#fff; }
.card-header { background-color:#DCECFD; color:#2563EB; font-weight:600; font-size:1.1rem; border-bottom:2px solid #2563EB; }
.btn-primary-custom { background-color:#2563EB; border-color:#2563EB; color:white; }
.btn-primary-custom:hover { background-color:#1E40AF; }
.form-control { border-radius:6px; }
</style>
</head>
<body>

<div class="container mt-4">

<?php 
if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger mb-3" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    </div>
<?php endif; 
if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success mb-3" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>


<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-lightbulb"></i> Edit Career Interest</div>
    <div class="card-body">
        <form method="post">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label visually-hidden">Role / Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Role / Title" value="<?= htmlspecialchars($editData['role_title']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label visually-hidden">Field / Domain</label>
                    <input type="text" name="field" class="form-control" placeholder="Field / Domain" value="<?= htmlspecialchars($editData['field']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label visually-hidden">Focus Area</label>
                    <input type="text" name="focus_area" class="form-control" placeholder="Focus Area" value="<?= htmlspecialchars($editData['focus_area']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label visually-hidden">Career Goal</label>
                    <textarea name="goal" class="form-control" rows="1" placeholder="Career Goal" required><?= htmlspecialchars($editData['goal']) ?></textarea>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" name="update_interest" class="btn btn-primary-custom">
                    <i class="fa-solid fa-save me-1"></i> Update
                </button>
                <a href="update.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Cancel</a>
            </div>
        </form>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>