<?php
// Ensure this path is correct and db.php connects to the correct database
require_once('db/db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Database Fetching with Error Handling ---

// ✅ User info
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user ID is in session but not found in DB
        session_destroy();
        header("Location: ../Auth/login.php");
        exit;
    }

} catch (PDOException $e) {
    // Log the error and display a friendly message
    error_log("User Info Fetch Error: " . $e->getMessage());
    $user = []; // Default empty array to prevent issues later
}


// ✅ Skills (This is the section where the error occurred - ensure 'skills' table exists!)
try {
    // Make sure the table name 'skills' is correct in your database.
    // If your table is named 'user_skills', change 'skills' below.
    $skillStmt = $conn->prepare("SELECT skill_name FROM skills WHERE user_id = :id");
    $skillStmt->execute([':id' => $user_id]); // <-- The original error was on this line
    $skills = $skillStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Skills Fetch Error: " . $e->getMessage());
    $skills = []; // Default to empty array
}


// Fetch user skills & preferred track
$preferred_track = $user['preferred_track'] ?? '';
$user_skills = $skills;

// Fetch all jobs and calculate matching
try {
    $all_jobs = $conn->query("SELECT * FROM jobs")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Jobs Fetch Error: " . $e->getMessage());
    $all_jobs = [];
}
$recommended_jobs = [];
foreach($all_jobs as $job){
    $job_skills = array_map('trim', explode(',', $job['required_skills'] ?? ''));
    $matching_skills = array_intersect($user_skills, $job_skills);
    if(!empty($matching_skills)){
        $job['matching_skills'] = $matching_skills;
        $recommended_jobs[] = $job;
    }
}

// Fetch all courses and calculate matching
try {
    $all_courses = $conn->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Courses Fetch Error: " . $e->getMessage());
    $all_courses = [];
}
$recommended_courses = [];
foreach($all_courses as $course){
    $course_skills = array_map('trim', explode(',', $course['related_skills'] ?? ''));
    $matches = array_intersect($user_skills, $course_skills);
    if(!empty($matches)){
        $course['matching_skills'] = $matches;
        $recommended_courses[] = $course;
    }
}

ob_start();
?>

<div class="wrapper">
  <ul class="colorlib-bubbles">
    <?php for($i=0;$i<10;$i++): ?><li></li><?php endfor; ?>
  </ul>

  <div class="main-content mt-lg-0 pt-5 pt-lg-0 p-4 mt-2">

    <div class="card shadow-sm mb-4 border-0">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center text-center">

        <div class="profile-photo flex-fill" style="max-width: 150px;">
          <img src="Image/<?= htmlspecialchars($user['profile_photo'] ?? 'default.png') ?>" 
                alt="Profile Photo" 
                style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        </div>

        <div class="flex-fill mx-3 d-flex flex-column align-items-center">
          <h5 class="fw-semibold mb-1">Welcome back, 
            <span class="text-primary"><?= htmlspecialchars($user['fullname'] ?? 'User') ?></span>
          </h5>
          <p class="text-muted mb-2">Keep exploring, keep learning, and keep growing.</p>

          <div class="d-flex flex-wrap justify-content-center gap-1">
            <?php if (!empty($skills)): ?>
              <?php foreach ($skills as $skill): ?>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($skill) ?></span>
              <?php endforeach; ?>
            <?php else: ?>
                <span class="text-muted small">No skills added yet.</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex-fill d-flex flex-column align-items-center" style="max-width: 200px;">
          <h6 class="fw-semibold mb-1">🎓 Education</h6>
          <p class="text-muted mb-0 small">
            <?= htmlspecialchars($user['edu_level'] ?? 'N/A') ?>
          </p>
        </div>

      </div>
    </div>

    <div class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="section-title">Recommended Jobs</h5>
        <a href="jobs/jobs.php" class="btn btn-outline-primary btn-sm">View All</a>
      </div>

      <div class="row g-3 equal-row">
        <?php if (!empty($recommended_jobs)): ?>
          <?php foreach (array_slice($recommended_jobs, 0, 6) as $job): // Limit to 6 for dashboard view ?>
            <div class="col-md-6 col-lg-4">
              <div class="card job-card p-3 shadow-sm border-0">
                <div>
                  <h6 class="fw-semibold mb-1"><?= htmlspecialchars($job['job_title'] ?? '') ?></h6>
                  <p class="text-muted small mb-1"><?= htmlspecialchars($job['company_name'] ?? '') ?></p>
                  <?php if (!empty($job['required_skills'])): ?>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                          <span class="badge bg-light text-dark border"><?= htmlspecialchars(trim($skill)) ?></span>
                        <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($job['matching_skills'])): ?>
                    <div class="text-success small mt-1">
                      Matches: <?= implode(', ', array_map('htmlspecialchars', $job['matching_skills'])) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                  <small class="text-muted">
                    <?= htmlspecialchars($job['location'] ?? 'N/A') ?> • <?= htmlspecialchars($job['job_type'] ?? 'N/A') ?>
                  </small>
                  <button class="btn btn-sm btn-primary">Apply</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-center w-100">No recommended jobs at the moment.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="section-title">Recommended Learning Resources</h5>
        <a href="courses/courses.php" class="btn btn-outline-primary btn-sm">View All</a>
      </div>

      <div class="row g-3 equal-row">
        <?php if (!empty($recommended_courses)): ?>
          <?php foreach (array_slice($recommended_courses, 0, 6) as $course): // Limit to 6 for dashboard view ?>
            <div class="col-md-6 col-lg-4">
              <div class="card resource-card p-3 shadow-sm border-0">
                <div>
                  <h6 class="fw-semibold"><?= htmlspecialchars($course['course_title'] ?? '') ?></h6>
                  <p class="text-muted small mb-1">
                    <?= htmlspecialchars($course['platform'] ?? 'Unknown') ?> • 
                    <?= htmlspecialchars($course['cost_type'] ?? 'Free') ?>
                  </p>
                  <?php if (!empty($course['related_skills'])): ?>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                      <?php foreach (explode(',', $course['related_skills']) as $skill): ?>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars(trim($skill)) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($course['matching_skills'])): ?>
                    <div class="text-success small mt-1">
                      Matches: <?= implode(', ', array_map('htmlspecialchars', $course['matching_skills'])) ?>
                    </div>
                  <?php endif; ?>
                </div>
                <a href="<?= htmlspecialchars($course['course_url'] ?? '#') ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                  Start Learning
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-center w-100">No recommended learning resources at the moment.</p>
        <?php endif; ?>
      </div>
    </div>

  </div> </div> <?php
$content = ob_get_clean();
// Make sure 'base.php' exists and handles the final output
include 'base.php';
?>