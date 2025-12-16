<?php
include '../db/db.php'; 
session_start();// PDO connection
if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id");
$stmt->execute(['id'=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// Pagination setup
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$location = $_GET['location'] ?? '';
$level = $_GET['level'] ?? '';

// Base query
$sql = "SELECT * FROM jobs WHERE 1=1";
$params = [];

// Filters
if ($search) {
    $sql .= " AND (job_title LIKE :search OR company_name LIKE :search OR required_skills LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($type) {
    $sql .= " AND job_type = :type";
    $params[':type'] = $type;
}

if ($location) {
    $sql .= " AND location = :location";
    $params[':location'] = $location;
}

if ($level) {
    $sql .= " AND exp_level = :level";
    $params[':level'] = $level;
}

// Count total jobs for pagination
$countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$stmtCount = $conn->prepare($countSql);
$stmtCount->execute($params);
$totalJobs = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalJobs / $limit);

// Add pagination
$sql .= " ORDER BY created_at DESC LIMIT :offset, :limit";
$params[':offset'] = $offset;
$params[':limit'] = $limit;

$stmt = $conn->prepare($sql);

// Bind parameters manually for LIMIT and OFFSET (PDO requires INT)
foreach ($params as $key => &$val) {
    if ($key == ':limit' || $key == ':offset') {
        $stmt->bindParam($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindParam($key, $val);
    }
}

$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct locations, job types, experience levels
$locations = $conn->query("SELECT DISTINCT location FROM jobs ORDER BY location ASC")->fetchAll(PDO::FETCH_COLUMN);
$job_types = $conn->query("SELECT DISTINCT job_type FROM jobs ORDER BY job_type ASC")->fetchAll(PDO::FETCH_COLUMN);
$exp_levels = $conn->query("SELECT DISTINCT exp_level FROM jobs ORDER BY exp_level ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Opportunities Hub</title>
    <link rel="stylesheet" href="../CSS/jobs.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
    <style>
        body { background: linear-gradient(135deg, #DCECFD 0%, #bedbfaff 100%); }
        /* Navbar */
        .navbar-brand { font-weight: 600; }
        /* Filters */
        .filter-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; margin-bottom:20px; }
        .filter-btn { background: linear-gradient(135deg, #4f46e5, #3b82f6); color:white; border:none; height:46px; font-size:1em; font-weight:600; border-radius:10px; cursor:pointer; transition:all 0.25s ease; width:100%; box-shadow:0 4px 10px rgba(59,130,246,0.2); }
        .filter-btn:hover { background: linear-gradient(135deg, #4338ca, #2563eb); transform: translateY(-2px); box-shadow: 0 6px 14px rgba(59,130,246,0.35);}
        .filter-btn:active { transform: translateY(0); box-shadow: 0 2px 6px rgba(59,130,246,0.25);}
        /* Job Cards */
        .jobs-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap:20px; }
        .job-card { background:#fff; border-radius:10px; padding:15px; box-shadow:0 2px 6px rgba(0,0,0,0.1); transition:all 0.25s ease; }
        .job-card:hover { transform:translateY(-3px); box-shadow:0 4px 12px rgba(0,0,0,0.15); }
        .job-header .job-title { font-weight:600; font-size:1.1rem; }
        .job-meta .badge { margin-right:5px; font-size:0.8rem; }
        .skills-label { font-weight:500; margin-bottom:5px; font-size:0.9rem; }
        .skill-tag { display:inline-block; background:#DCECFD; color:#2563EB; padding:4px 8px; border-radius:6px; margin:2px; font-size:0.85rem; }
        .description { margin-top:8px; font-size:0.9rem; color:#555; }
        /* Pagination */
        .pagination { margin:20px 0; text-align:center; }
        .pagination a { margin:0 5px; padding:5px 10px; background:#f0f0f0; text-decoration:none; color:#333; border-radius:5px; }
        .pagination a.active { background:#007bff; color:#fff; }
        /* No results */
        .no-results { text-align:center; padding:80px 20px; color:#333; font-size:1.2em; font-weight:600; grid-column:1/-1; background: rgba(255,255,255,0.95); border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.15); backdrop-filter:blur(8px); display:flex; flex-direction:column; align-items:center; justify-content:center; }
        .no-results .icon { font-size:60px; color:#4f46e5; margin-bottom:20px; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);} }
        .no-results p { font-size:1.3em; margin-bottom:10px; color:#111; }
        .no-results span { color:#555; font-size:0.95em; }
        /* Footer */
        .footer { background:#2563EB; color:#fff; text-align:center; padding:15px 0; margin-top:30px; border-radius:10px; }
        .job-meta {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px; /* spacing between badges */
}

.job-meta .badge {
    font-size: 0.75rem; 
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px; /* gap between icon and text */
    color: #fff;
}

.location-badge { background-color: #3b82f6; } /* blue */
.type-badge { background-color: #10b981; }     /* green */
.level-badge { background-color: #f59e0b; }    /* orange */
.pagination {
    display: flex;
    justify-content: center; /* Center horizontally */
    align-items: center;     /* Align vertically */
    margin: 20px 0;
    flex-wrap: wrap;         /* Wrap if too many pages */
    gap: 6px;                /* spacing between page links */
}
.pagination a {
    padding: 6px 12px;
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s ease;
}
.pagination a.active {
    background: #3b82f6;
    color: #fff;
}
.pagination a:hover:not(.active) {
    background: #dbeafe;
    color: #1e40af;
}

    </style>
</head>
<body>

<!-- Navbar Start -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand text-primary" href="#">SkillMap-AI</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
       <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="../dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Jobs</a></li>
        <li class="nav-item"><a class="nav-link" href="../courses/courses.php">Resources</a></li>
       
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-primary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= htmlspecialchars($user['fullname']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="../Profile/update.php">Edit Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../Auth/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div style="height:70px;"></div>
<!-- Navbar End -->

<div class="container">
    <div class="filters">
        <form method="GET">
            <div class="filter-grid">
                <div class="filter-group search-box">
                    <label>Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search jobs, companies, skills...">
                </div>
                <div class="filter-group">
                    <label>Job Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <?php foreach ($job_types as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $type == $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Location</label>
                    <select name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $location == $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Experience Level</label>
                    <select name="level">
                        <option value="">All Levels</option>
                        <?php foreach ($exp_levels as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $level == $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group filter-submit">
                    <label style="visibility:hidden;">Filter</label>
                    <button type="submit" class="filter-btn">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="jobs-grid">
        <?php if (count($jobs) == 0): ?>
            <div class="no-results">
                <div class="icon">🔍</div>
                <p>No jobs found</p>
                <span>Try adjusting your filters or search keywords.</span>
            </div>
        <?php else: ?>
            <?php foreach ($jobs as $row): ?>
                <div class="job-card">
                    <div class="job-header">
                        <div class="job-title"><?= htmlspecialchars($row['job_title']) ?></div>
                        <div class="company"><?= htmlspecialchars($row['company_name']) ?></div>
                    </div>
                   <div class="job-meta">
    <span class="badge location-badge">📍 <?= htmlspecialchars($row['location']) ?></span>
    <span class="badge type-badge"><?= htmlspecialchars($row['job_type']) ?></span>
    <span class="badge level-badge"><?= htmlspecialchars($row['exp_level']) ?></span>
</div>

                    <div class="skills">
                        <div class="skills-label">Required Skills:</div>
                        <div class="skill-tags">
                            <?php foreach (array_slice(explode(',', $row['required_skills']),0,4) as $skill): ?>
                                <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="description"><?= nl2br(htmlspecialchars($row['description'])) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&location=<?= urlencode($location) ?>&level=<?= urlencode($level) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    &copy; <?= date('Y') ?> SkillMap-AI. All Rights Reserved.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
