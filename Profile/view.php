<?php
// Ensure this path is correct
include '../db/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- 1. Fetch user info ---
// ASSUMES 'phone' and 'location' columns have been added to the 'users' table.
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id");
    $stmt->execute(['id'=>$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // Clear session and redirect if user data is missing
        session_destroy();
        header("Location: ../Auth/login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("User Info Fetch Error: " . $e->getMessage());
    $user = []; // Default to empty array
}


// --- 2. Fetch skills ---
// Corrected to use the 'user_skills' table name from your schema
try {
    $stmt = $conn->prepare("SELECT * FROM user_skills WHERE user_id=:id");
    $stmt->execute(['id'=>$user_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Skills Fetch Error: " . $e->getMessage());
    $skills = []; 
}


// --- 3. Fetch experiences ---
// Assuming 'user_experiences' table now has 'organization', 'start_date', and 'end_date'
// Your schema only has 'exp_title' and 'description'. I will fetch what is available
// and assume placeholders for the missing fields ('organization', 'start_date', 'end_date')
try {
    // Note: 'organization', 'start_date', 'end_date' are assumed to be added to user_experiences
    $stmt = $conn->prepare("SELECT exp_title AS title, description, organization, start_date, end_date FROM user_experiences WHERE user_id=:id ORDER BY start_date DESC");
    $stmt->execute(['id'=>$user_id]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Experiences Fetch Error: " . $e->getMessage());
    $experiences = []; 
}


// --- 4. Fetch career interests ---
// ASSUMES a 'career_interests' table exists with columns: role_title, field, focus_area, goal
try {
    $stmt = $conn->prepare("SELECT role_title, field, focus_area, goal FROM career_interests WHERE user_id=:id ORDER BY id DESC");
    $stmt->execute(['id'=>$user_id]);
    $interests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Interests Fetch Error: " . $e->getMessage());
    $interests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($user['fullname'] ?? 'User') ?> Profile</title>
<link rel="stylesheet" href="../CSS/profile.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
<style>
/* ... (Your original inline styles remain here) ... */
.fixed-sidebar { position: sticky; top: 20px; padding:15px; border:1px solid #DCECFD; border-radius:10px; background:#fff; }
.profile-photo img { width:100px; height:100px; object-fit:cover; border-radius:50%; }
.skill-badge { display:inline-block; padding:5px 10px; background:#DCECFD; color:#2563EB; border-radius:6px; margin:2px; }
.faculty-attributes { margin-bottom:50px; }
.career-item { padding:15px; border:1px solid #DCECFD; border-radius:10px; background:#fff; box-shadow:0 2px 6px rgba(37,99,235,0.1); margin-top:15px; }
body { background-color: #F8FAFC; }
.navbar-brand { font-weight: 600; }
.footer { background:#2563EB; color:#fff; text-align:center; padding:15px 0; margin-top:30px; border-radius:10px; }
.colorlib-bubbles { position:absolute; top:0; left:0; width:100%; height:100%; z-index:-1; overflow:hidden; }
.colorlib-bubbles li { position:absolute; list-style:none; display:block; width:40px; height:40px; background:rgba(37,99,235,0.2); bottom:-160px; animation:bubble 25s infinite; border-radius:50%; }
@keyframes bubble { 0% { transform: translateY(0) rotate(0deg); opacity:1; } 100% { transform: translateY(-700px) rotate(360deg); opacity:0; } }
/* Ensure tab content is hidden correctly */
.tab-content .tab-pane { display: none; }
.tab-content .tab-pane.active { display: block; }

</style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand text-primary" href="#">SkillMap-AI</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarProfile" aria-controls="navbarProfile" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarProfile">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="../dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="../jobs/jobs.php">Jobs</a></li>
        <li class="nav-item"><a class="nav-link" href="../courses/courses.php">Resources</a></li>
        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-primary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= htmlspecialchars($user['fullname'] ?? 'User') ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="update.php">Edit Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../Auth/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div style="height:70px;"></div>
<ul class="colorlib-bubbles">
  <?php for($i=0;$i<10;$i++): ?><li></li><?php endfor; ?>
</ul>

<div class="container" style="margin-top:10px;">
    <div class="row">
        <div class="col-lg-3" id="faculty-image-bio">
            <div class="fixed-sidebar text-center ">
                <div class="profile-photo">
                    <img src="../Image/<?= htmlspecialchars($user['profile_photo'] ?? 'default.png') ?>" alt="Profile Photo">
                </div>
                <h3 id="faculty-name">
                    <a href="#" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($user['fullname'] ?? 'User') ?></a>
                </h3>
                <div style="margin-top:10px; display:flex; flex-direction:column; gap:6px; font-size:0.95rem; color:#374151;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-regular fa-envelope" style="color:#2563EB;"></i>
                        <span><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-mobile-screen" style="color:#2563EB;"></i>
                        <span><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-location-dot" style="color:#2563EB;"></i>
                        <span><?= htmlspecialchars($user['location'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-9" id="faculty-details">
            <div style="margin-left:20px; margin-top:20px; display:flex; align-items:center;">
                
                <ul class="nav nav-tabs" id="myTab" role="tablist" style="margin-bottom:30px; flex:1;">
                    <li class="nav-item" role="presentation"><a class="nav-link active" data-bs-toggle="tab" data-bs-target="#skills-section" type="button" role="tab" aria-controls="skills-section" aria-selected="true">Skills</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" data-bs-target="#experiences-section" type="button" role="tab" aria-controls="experiences-section" aria-selected="false">Experiences</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" data-bs-target="#interests-section" type="button" role="tab" aria-controls="interests-section" aria-selected="false">Career Interests</a></li>
                </ul>
                
            </div>

            <div class="tab-content" id="nav-tabContent" style="margin-left:20px;">
                
                <div id="skills-section" class="faculty-attributes tab-pane fade show active" role="tabpanel" aria-labelledby="skills-tab" style="scroll-margin-top:110px;">
                    <div style="border-bottom:1px solid #2563EB; box-shadow:-5px 0px #2563EB;">
                        <h3 style="margin-left:8px;">Skills</h3>
                    </div>
                    <div class="skills-list" style="padding:10px;">
                        <?php if (!empty($skills)): ?>
                            <?php foreach($skills as $skill): ?>
                                <span class="skill-badge"><?= htmlspecialchars($skill['skill_name']) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mt-2">No skills added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="experiences-section" class="faculty-attributes tab-pane fade" role="tabpanel" aria-labelledby="experiences-tab" style="margin-top:70px; scroll-margin-top:110px;">
                    <div style="border-bottom:1px solid #2563EB; box-shadow:-5px 0px #2563EB;">
                        <h3 style="margin-left:8px;">Experiences</h3>
                    </div>
                    <?php if (!empty($experiences)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped" style="margin-top:15px;">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Organization</th>
                                        <th>From Date</th>
                                        <th>To Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($experiences as $exp): 
                                        // Handle potential null/empty dates
                                        $start = !empty($exp['start_date']) ? date("M Y", strtotime($exp['start_date'])) : '-';
                                        $end = !empty($exp['end_date']) ? date("M Y", strtotime($exp['end_date'])) : 'Present'; // Assuming NULL end_date means 'Present'
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($exp['title'] ?? $exp['exp_title'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($exp['organization'] ?? 'N/A') ?></td>
                                        <td><?= $start ?></td>
                                        <td><?= $end ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mt-2">No work or project experiences added yet.</p>
                    <?php endif; ?>
                </div>

                <div id="interests-section" class="faculty-attributes tab-pane fade" role="tabpanel" aria-labelledby="interests-tab" style="margin-top:70px; scroll-margin-top:110px;">
                    <div style="border-bottom:1px solid #2563EB; box-shadow:-5px 0px #2563EB;">
                        <h3 style="margin-left:8px; color:#1E3A8A;">Career Interests</h3>
                    </div>
                    <?php if (!empty($interests)): ?>
                        <?php foreach($interests as $int): ?>
                        <div class="career-item">
                            <h5 style="color:#2563EB; margin:0 0 5px 0;"><?= htmlspecialchars($int['role_title']) ?></h5>
                            <p style="margin:0;"><strong>Field / Domain:</strong> <?= htmlspecialchars($int['field']) ?></p>
                            <p style="margin:0;"><strong>Focus Area:</strong> <?= htmlspecialchars($int['focus_area']) ?></p>
                            <p style="margin:0;"><strong>Career Goal:</strong> <?= htmlspecialchars($int['goal']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mt-2">No career interests defined yet.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="footer" style="background-color:#2563EB;"> &copy; <?= date('Y') ?> SkillMap-AI. All Rights Reserved.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple script to handle hash change if needed for direct links, 
        // but Bootstrap's JS will handle the tab switching automatically.
        // This is usually for better history/URL handling.
        var triggerTabList = [].slice.call(document.querySelectorAll('#myTab a'))
        triggerTabList.forEach(function (triggerEl) {
          var tabTrigger = new bootstrap.Tab(triggerEl)

          triggerEl.addEventListener('click', function (event) {
            event.preventDefault()
            tabTrigger.show()
          })
        })
    });
</script>
</body>
</html>