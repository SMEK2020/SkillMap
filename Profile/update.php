<?php
session_start(); // ✅ Only once at the top

include '../db/db.php'; // PDO connection

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

// ======= Fetch User (with error handling) =======
$user_id = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id LIMIT 1");
    $stmt->execute(['id'=>$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user ID is in session but not found in DB
        session_destroy();
        header("Location: ../Auth/login.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("User Fetch Error: " . $e->getMessage());
    die("Database error. Please try again later.");
}

// ======= Default Profile Photo =======
// Initialize session photo variable using data from DB, falling back to a default
if(!isset($_SESSION['profile_photo'])) {
    // Check if $user is available and has 'profile_photo', otherwise use default placeholder
    $_SESSION['profile_photo'] = $user['profile_photo'] ?? 'placeholder-image-person-jpg.jpg';
}
// Use the session variable for display
$photo = $_SESSION['profile_photo'];

// ======= Upload Profile Photo =======
if(isset($_POST['upload_photo']) && isset($_FILES['profilePhoto'])) {
    $file = $_FILES['profilePhoto'];
    $targetDir = "../Image/";
    $fileName = time() . '_' . basename($file['name']);
    $targetFile = $targetDir . $fileName;
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if(in_array($ext, ['jpg','jpeg','png'])) {
        if(move_uploaded_file($file['tmp_name'], $targetFile)) {
            $_SESSION['profile_photo'] = $fileName;
            try {
                $stmt = $conn->prepare("UPDATE users SET profile_photo=:photo WHERE user_id=:id");
                $stmt->execute(['photo'=>$fileName,'id'=>$user_id]);
            } catch (PDOException $e) {
                error_log("Photo Update Error: " . $e->getMessage());
            }
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            $upload_error = "File upload failed! Check permissions.";
        }
    } else {
        $upload_error = "Only JPG, JPEG, PNG allowed!";
    }
}

// ======= Remove Photo =======
if(isset($_POST['remove_photo'])) {
    $default_photo = 'placeholder-image-person-jpg.jpg';
    $_SESSION['profile_photo'] = $default_photo;
    try {
        $stmt = $conn->prepare("UPDATE users SET profile_photo=:photo WHERE user_id=:id");
        $stmt->execute(['photo'=>$default_photo,'id'=>$user_id]);
    } catch (PDOException $e) {
        error_log("Photo Remove Error: " . $e->getMessage());
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- CORRECTED QUERIES (using your schema names) ---

// ======= Fetch Skills (from user_skills table) =======
try {
    // CORRECTED: Assuming your skills table is named 'user_skills' from previous context
    $stmt = $conn->prepare("SELECT skill_id AS id, skill_name FROM user_skills WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Skills Fetch Error: " . $e->getMessage());
    $skills = [];
}


// ======= Fetch Experiences (from user_experiences table) =======
try {
    // CORRECTED: Assuming your experiences table is named 'user_experiences'
    // Mapped exp_id to id and exp_title to title for consistency with form/view
    // NOTE: 'organization', 'start_date', and 'end_date' are assumed columns in user_experiences
    $stmt = $conn->prepare("SELECT exp_id AS id, exp_title AS title, organization, start_date, end_date FROM user_experiences WHERE user_id = :id ORDER BY start_date DESC");
    $stmt->execute(['id' => $user_id]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Experiences Fetch Error: " . $e->getMessage());
    $experiences = [];
}

// ======= Fetch Interests (Assumed table career_interests) =======
try {
    // Assuming 'id' is the primary key and 'role_title' is the main interest field
    $stmt = $conn->prepare("SELECT id, role_title, field, focus_area, goal FROM career_interests WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $interests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Interests Fetch Error: " . $e->getMessage());
    $interests = [];
}

// ======= Fetch CV (from user_cv table) =======
try {
    $stmt = $conn->prepare("SELECT cv_id AS id, cv_text FROM user_cv WHERE user_id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $cv = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("CV Fetch Error: " . $e->getMessage());
    $cv = ['id' => 0, 'cv_text' => '']; // Default to avoid errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Profile</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>
body { background-color:#F8FAFC; font-family:'Inter',sans-serif; }
.card { border-radius:10px; border:1px solid #DCECFD; box-shadow:0 4px 10px rgba(37,99,235,0.1); background:#fff; }
.card-header { background-color:#DCECFD; color:#2563EB; font-weight:600; font-size:1.1rem; border-bottom:2px solid #2563EB; }
.btn-primary-custom { background-color:#2563EB; border-color:#2563EB; color:white; }
.btn-primary-custom:hover { background-color:#1E40AF; }
.btn-success, .btn-warning, .btn-outline-danger { display:flex; align-items:center; }
.profile-photo-container { text-align:center; margin:15px 0 10px 0; }
.profile-photo { width:120px; height:120px; border-radius:50%; object-fit:cover; border:4px solid #DCECFD; box-shadow:0 2px 8px rgba(0,0,0,0.15); }
.action-btn { margin-left:5px; }
</style>
</head>
<body>
<div class="container mt-4 mb-5">

<div class="d-flex justify-content-between align-items-center mb-4">
<h2 style="color:#1E3A8A;"><i class="fa-solid fa-user-pen"></i> Update Profile</h2>
<a href="view.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-id-card"></i> Personal Information</div>
    <div class="card-body">
        <div class="profile-photo-container">
            <img src="../Image/<?= htmlspecialchars($photo) ?>" alt="Profile Photo" class="profile-photo mb-3">
        </div>
        <form method="post" enctype="multipart/form-data" class="text-center mb-3">
            <input type="file" name="profilePhoto" class="form-control form-control-sm mb-2" style="max-width:250px;margin:0 auto;">
            <div class="d-flex justify-content-center gap-2">
                <button type="submit" name="upload_photo" class="btn btn-success btn-sm"><i class="fa-solid fa-upload me-1"></i> Update Photo</button>
                <?php if ($photo !== 'placeholder-image-person-jpg.jpg'): ?>
                    <button type="submit" name="remove_photo" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash me-1"></i> Remove</button>
                <?php endif; ?>
            </div>
        </form>
        <?php if(isset($upload_error)): ?>
            <div class="text-danger text-center mb-3"><?= htmlspecialchars($upload_error) ?></div>
        <?php endif; ?>
        <form method="post" action="update_process.php">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($user['location'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">Educational Level</label>
                    <select name="edu_level" class="form-select">
                        <?php
                        $levels = ['Undergraduate','Graduate','Postgraduate'];
                        foreach($levels as $level){
                            $sel = (($user['edu_level'] ?? '')==$level)?'selected':''; 
                            echo "<option $sel>$level</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Experience Level</label>
                    <select name="experience_level" class="form-select">
                        <?php
                        $exp_levels = ['Fresher','Junior','Mid','Senior'];
                        foreach($exp_levels as $exp){
                            $sel = (($user['experience_level'] ?? '')==$exp)?'selected':''; 
                            echo "<option $sel>$exp</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Preferred Track</label>
                    <select name="preferred_track" class="form-select">
                        <?php
                        $tracks = ['Web Development','AI & Data Science','Cybersecurity','Software Engineering'];
                        foreach($tracks as $track){
                            $sel = (($user['preferred_track'] ?? '')==$track)?'selected':''; 
                            echo "<option $sel>$track</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary-custom mt-3"><i class="fa-solid fa-save"></i> Update Info</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-code"></i> Skills</div>
    <div class="card-body">
        <form method="post" action="update_skill.php" class="mb-3">
            <div class="input-group">
                <input type="text" name="skill" class="form-control" placeholder="Add new skill">
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i> Add</button>
            </div>
        </form>
        <ul class="list-group">
    <?php foreach(($skills ?? []) as $skill): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($skill['skill_name']) ?>
            <div class="d-flex gap-2">
                <a href="edit_skill.php?id=<?= $skill['id'] ?>" class="btn btn-warning btn-sm">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                </a>
                <a href="delete_skill.php?id=<?= $skill['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this skill?');">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                </a>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-briefcase"></i> Experiences</div>
    <div class="card-body">
        <form method="post" action="update_experience.php" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
                <div class="col-md-3"><input type="text" name="organization" class="form-control" placeholder="Organization"></div>
                <div class="col-md-3"><input type="date" name="from_date" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to_date" class="form-control"></div>
            </div>
            <button type="submit" class="btn btn-success mt-2"><i class="fa-solid fa-plus me-1"></i> Add</button>
        </form>
       <ul class="list-group">
    <?php foreach(($experiences ?? []) as $exp): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <?php
                // Mapping: $exp['title'] (exp_title) is used. Null coalescing ensures no errors if dates are missing.
                $start = $exp['start_date'] ? date("M Y", strtotime($exp['start_date'])) : '-';
                $end   = $exp['end_date'] ? date("M Y", strtotime($exp['end_date'])) : 'Present'; // Common convention
                echo htmlspecialchars(($exp['title'] ?? 'N/A').' - '.($exp['organization'] ?? 'N/A')." ($start - $end)");
            ?>
            <div class="d-flex gap-2">
                <a href="edit_experience.php?id=<?= $exp['id'] ?>" class="btn btn-warning btn-sm">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                </a>
                <a href="delete_experience.php?id=<?= $exp['id'] ?>" 
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('Are you sure you want to delete this experience?');">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                </a>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-lightbulb"></i> Career Interests</div>
    <div class="card-body">
        <form method="post" action="update_interest.php" class="mb-3">
            <input type="text" name="title" class="form-control mb-2" placeholder="Role / Title" required>
            <input type="text" name="field" class="form-control mb-2" placeholder="Field / Domain" required>
            <input type="text" name="focus_area" class="form-control mb-2" placeholder="Focus Area" required>
            <textarea name="goal" class="form-control mb-2" rows="3" placeholder="Career Goal" required></textarea>
            <button type="submit" name="add_interest" class="btn btn-success">
                <i class="fa-solid fa-plus me-1"></i> Add
            </button>
        </form>

<ul class="list-group">
    <?php foreach(($interests ?? []) as $int): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <?= htmlspecialchars($int['role_title'].' - '.$int['field'].' ('.$int['focus_area'].')') ?>
            
            <div class="d-flex gap-2">
                <a href="edit_interest.php?edit_id=<?= $int['id'] ?>" class="btn btn-warning btn-sm">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                </a>    
                <a href="Delete_interest.php?delete_id=<?= $int['id'] ?>" 
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('Are you sure you want to delete this interest?');">
                   <i class="fa-solid fa-trash me-1"></i> Delete
                </a>
            </div>
        </li>
    <?php endforeach; ?>
</ul>


    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><i class="fa-solid fa-file-lines"></i> Paste CV / Notes</div>
    <div class="card-body">
        <form method="post" action="update_cv.php">
            <textarea name="cv_text" class="form-control mb-2" rows="6"><?= htmlspecialchars($cv['cv_text'] ?? '') ?></textarea>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-save me-1"></i> Save</button>
                <a href="delete_cv.php?id=<?= $cv['id'] ?? 0 ?>" class="btn btn-outline-danger"><i class="fa-solid fa-trash me-1"></i> Clear</a>
            </div>
        </form>
    </div>
</div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>