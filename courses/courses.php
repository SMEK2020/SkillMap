<?php
include '../db/db.php'; 
session_start(); // Start session

if(!isset($_SESSION['user_id'])){
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:id");
$stmt->execute(['id'=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Function to generate placeholder image (same as original)
function getResourceImage($row) {
    if (!empty($row['image_url'])) return htmlspecialchars($row['image_url']);
    $platformColors = [
        'Coursera'=>['2A73CC','1E5B9C'], 'Udemy'=>['A435F0','7C1FD6'],
        'edX'=>['02262B','0A4A5C'], 'Khan Academy'=>['14BF96','0D8B6E'],
        'LinkedIn Learning'=>['0077B5','004E7A'], 'Pluralsight'=>['F15B2A','C4451F'],
        'FreeCodeCamp'=>['0A0A23','1B1B32'], 'YouTube'=>['FF0000','CC0000'],
        'Skillshare'=>['00FF84','00CC6A'], 'Codecademy'=>['1F4287','162E5C']
    ];
    $platform = $row['platform'];
    if(isset($platformColors[$platform])){
        $color1=$platformColors[$platform][0]; $color2=$platformColors[$platform][1];
    } else { $color1='667eea'; $color2='764ba2'; }
    return "https://placehold.co/400x250/{$color1}/{$color2}?text=" . urlencode($platform);
}

// Pagination setup
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page-1)*$limit;

// Filters
$search = $_GET['search'] ?? '';
$platform = $_GET['platform'] ?? '';
$cost = $_GET['cost'] ?? '';

// Base query
$sql="SELECT * FROM courses WHERE 1=1";
$params=[];

if($search){ $sql.=" AND (course_title LIKE :search OR platform LIKE :search OR related_skills LIKE :search)"; $params[':search']="%$search%"; }
if($platform){ $sql.=" AND platform = :platform"; $params[':platform']=$platform; }
if($cost){ $sql.=" AND cost_type = :cost"; $params[':cost']=$cost; }

// Count total
$countSql=str_replace("SELECT *","SELECT COUNT(*) as total",$sql);
$stmtCount=$conn->prepare($countSql);
$stmtCount->execute($params);
$totalResources=$stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages=ceil($totalResources/$limit);

// Add LIMIT
$sql.=" ORDER BY course_id DESC LIMIT :offset, :limit";
$params[':offset']=$offset; $params[':limit']=$limit;
$stmt=$conn->prepare($sql);
foreach($params as $key=>&$val){
    if($key==':limit' || $key==':offset') $stmt->bindParam($key,$val,PDO::PARAM_INT);
    else $stmt->bindParam($key,$val);
}
$stmt->execute();
$resources=$stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct platforms
$platStmt=$conn->prepare("SELECT DISTINCT platform FROM courses ORDER BY platform ASC");
$platStmt->execute();
$platforms=$platStmt->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Learning Resources Hub</title>
<link rel="stylesheet" href="../CSS/courses.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
<style>
/* Same styling as your provided course page */
body { background: linear-gradient(135deg,#DCECFD 0%,#bedbfaff 100%); }
.navbar-brand{font-weight:600;}
.filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-bottom:20px;}
.filter-btn{background:linear-gradient(135deg,#4f46e5,#3b82f6);color:white;border:none;height:46px;font-size:1em;font-weight:600;border-radius:10px;cursor:pointer;transition:all 0.25s ease;width:100%;box-shadow:0 4px 10px rgba(59,130,246,0.2);}
.filter-btn:hover{background:linear-gradient(135deg,#4338ca,#2563eb);transform: translateY(-2px);box-shadow:0 6px 14px rgba(59,130,246,0.35);}
.resources-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
.resource-card{background:#fff;border-radius:10px;padding:15px;box-shadow:0 2px 6px rgba(0,0,0,0.1);transition:all 0.25s ease;}
.resource-card:hover{transform:translateY(-3px);box-shadow:0 4px 12px rgba(0,0,0,0.15);}
.resource-header .resource-title{font-weight:600;font-size:1.1rem;}
.platform-name{font-size:0.9rem;color:#555;margin-top:4px;}
.skills-label{font-weight:500;margin-bottom:5px;font-size:0.9rem;}
.skill-tag{display:inline-block;background:#DCECFD;color:#2563EB;padding:4px 8px;border-radius:6px;margin:2px;font-size:0.85rem;}
.visit-btn{display:block;margin-top:10px;text-decoration:none;color:white;background:#3b82f6;text-align:center;padding:8px 0;border-radius:8px;font-weight:500;transition:all 0.2s ease;}
.visit-btn:hover{background:#2563eb;}
.resource-meta{margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;}
.resource-meta .badge{font-size:0.75rem;font-weight:500;padding:4px 8px;border-radius:8px;display:inline-flex;align-items:center;gap:4px;color:#fff;}
.cost-badge.Free{background:#10b981;}
.cost-badge.Paid{background:#f59e0b;}
.pagination{display:flex;justify-content:center;align-items:center;margin:20px 0;flex-wrap:wrap;gap:6px;}
.pagination a{padding:6px 12px;background:#f0f0f0;color:#333;text-decoration:none;border-radius:6px;transition: all 0.2s ease;}
.pagination a.active{background:#3b82f6;color:#fff;}
.pagination a:hover:not(.active){background:#dbeafe;color:#1e40af;}
.no-results{text-align:center;padding:80px 20px;color:#333;font-size:1.2em;font-weight:600;grid-column:1/-1;background: rgba(255,255,255,0.95);border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.15);backdrop-filter:blur(8px);display:flex;flex-direction:column;align-items:center;justify-content:center;}
.no-results .icon{font-size:60px;color:#4f46e5;margin-bottom:20px;animation: float 3s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.no-results p{font-size:1.3em;margin-bottom:10px;color:#111;}
.no-results span{color:#555;font-size:0.95em;}
.footer{background:#2563EB;color:#fff;text-align:center;padding:15px 0;margin-top:30px;border-radius:10px;}
.resource-image-container {
    width: 100%;
    height: 180px; /* fixed height for card image */
    overflow: hidden;
    border-radius: 12px;
    position: relative;
}

.resource-image {
    width: 100%;
    height: 100%;
    object-fit: cover; /* ensures image covers the container */
    transition: transform 0.4s ease;
}

.resource-card:hover .resource-image {
    transform: scale(1.08);
}

/* Optional gradient overlay for better text readability */
.resource-image-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 50%;
    background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
    pointer-events: none;
}


</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
  <div class="container">
    <a class="navbar-brand text-primary" href="#">SkillMap-AI</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
       <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="../dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Resources</a></li>
        <li class="nav-item"><a class="nav-link" href="../jobs/jobs.php">Jobs</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-primary" href="#" data-bs-toggle="dropdown">
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

<div class="container">
    <div class="filters">
        <form method="GET">
            <div class="filter-grid">
                <div class="filter-group search-box">
                    <label>Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search courses, platforms, skills...">
                </div>
                <div class="filter-group">
                    <label>Platform</label>
                    <select name="platform">
                        <option value="">All Platforms</option>
                        <?php foreach ($platforms as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $platform==$opt?'selected':'' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Cost</label>
                    <select name="cost">
                        <option value="">All</option>
                        <option value="Free" <?= $cost=='Free'?'selected':'' ?>>Free</option>
                        <option value="Paid" <?= $cost=='Paid'?'selected':'' ?>>Paid</option>
                    </select>
                </div>
                <div class="filter-group filter-submit">
                    <label style="visibility:hidden;">Filter</label>
                    <button type="submit" class="filter-btn">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="resources-grid">
        <?php if(count($resources)==0): ?>
            <div class="no-results">
                <div class="icon">🔍</div>
                <p>No resources found</p>
                <span>Try adjusting your filters or search keywords.</span>
            </div>
        <?php else: ?>
            <?php foreach ($resources as $row): ?>
                    <div class="resource-card">
                        <div class="resource-image-container">
                            <img src="<?= getResourceImage($row) ?>" 
                                 alt="<?= htmlspecialchars($row['course_title']) ?>" 
                                 class="resource-image"
                                 loading="lazy">
                        </div>
                        
                        <div class="resource-header">
                            <div class="resource-title"><?= htmlspecialchars($row['course_title']) ?></div>
                            <div class="platform-name"><?= htmlspecialchars($row['platform']) ?></div>
                        </div>
                        <div class="resource-meta">
                            <span class="badge cost-badge <?= strtolower($row['cost_type']) ?>">
                                <?= $row['cost_type'] == 'Free' ? '🎉' : '💳' ?> <?= htmlspecialchars($row['cost_type']) ?>
                            </span>
                        </div>
                        <div class="skills">
                            <div class="skills-label">Related Skills:</div>
                            <div class="skill-tags">
                                <?php foreach (array_slice(array_map('trim', explode(',', $row['related_skills'])), 0, 5) as $skill): ?>
                                    <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <a href="<?= htmlspecialchars($row['course_url']) ?>" target="_blank" class="visit-btn">
                            Visit Course →
                        </a>
                    </div>
                <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if($totalPages>1): ?>
    <div class="pagination">
        <?php for($i=1;$i<=$totalPages;$i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&platform=<?= urlencode($platform) ?>&cost=<?= urlencode($cost) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div class="footer">
    &copy; <?= date('Y') ?> SkillMap-AI. All Rights Reserved.
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
