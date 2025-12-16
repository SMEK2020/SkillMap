<?php
session_start();
include '../db/db.php';

// ✅ User authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | skillMap-AI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
/* ===================== BASE STYLE ===================== */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #f6f7fb;
    color: #333;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ===================== LAYOUT ===================== */
.main-wrapper {
    display: flex;
    flex: 1;
    min-height: calc(100vh - 180px);
}

/* ===================== SIDEBAR ===================== */
.sidebar {
    width: 250px;
    background: #ffffff;
    border-right: 1px solid #e3e6ef;
    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
}
.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #eee;
}
.sidebar-header img {
    width: 75px;
    border-radius: 50%;
    margin-bottom: 5px;
}
.sidebar-nav {
    padding: 20px 15px;
}
.sidebar-nav .nav-item {
    margin-bottom: 12px;
}
.sidebar-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 15px;
    color: #444;
    font-weight: 500;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
}
.sidebar-nav .nav-link:hover, 
.sidebar-nav .nav-link.active {
    background: linear-gradient(90deg, #2563EB 0%, #3B82F6 100%);
    color: #fff;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3);
}

/* ===================== MAIN CONTENT ===================== */
.main-content {
    flex: 1;
    padding: 40px;
    background: #f9fafc;
}
.main-content h4 {
    font-weight: 600;
    color: #222;
    margin-bottom: 10px;
}
.main-content p {
    color: #555;
    line-height: 1.7;
}

/* ===================== FOOTER ===================== */
footer {
    background: linear-gradient(90deg, #2563EB 0%, #3B82F6 100%);
    color: #fff;
    text-align: center;
    padding: 40px 0 15px 0;
    margin-top: auto;
}
.footer-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    text-align: left;
    padding: 20px;
}
.footer-content {
    flex: 1;
    min-width: 260px;
    margin: 15px;
}
.footer-content h3 {
    border-bottom: 2px solid rgba(255, 255, 255, 0.6);
    display: inline-block;
    margin-bottom: 12px;
    font-size: 1.1rem;
}
.footer-content p, .footer-content a {
    color: #f1f1f1;
    font-size: 14px;
    text-decoration: none;
}
.footer-social-icons a {
    margin-right: 10px;
    color: #fff;
    font-size: 18px;
    transition: 0.3s;
}
.footer-social-icons a:hover {
    opacity: 0.8;
}
.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.3);
    margin-top: 10px;
    font-size: 13px;
    padding-top: 10px;
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #ddd;
    }
    .main-wrapper {
        flex-direction: column;
    }
    .main-content {
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="main-wrapper">

    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar">
        <header class="sidebar-header">
            <img src="root/img/logo.jpeg" alt="Logo">
            <h6 class="fw-semibold mt-2">SkillMap AI</h6>
        </header>

        <nav class="sidebar-nav">
            <ul class="nav-list list-unstyled">
                <li class="nav-item"><a href="#" class="nav-link active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-briefcase"></i> Jobs</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-book"></i> Resources</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- ========== MAIN CONTENT ========== -->
    <div class="main-content">
        <h4>Welcome, <?= htmlspecialchars($user['fullname']) ?></h4>
        <p>Here’s your personalized dashboard — explore career insights, skill analytics, and opportunities crafted just for you.</p>

        <div class="card mt-4 p-4 shadow-sm border-0">
            <h5 class="fw-semibold text-primary mb-3">Quick Overview</h5>
            <ul>
                <li>Recent Skill Updates</li>
                <li>Job Recommendations</li>
                <li>Learning Progress Tracker</li>
            </ul>
        </div>
    </div>

</div>

<!-- ========== FOOTER ========== -->
<footer>
    <div class="footer-container">

        <div class="footer-content">
            <h3>Contact Us</h3>
            <p><i class="fa-solid fa-phone"></i> +8801304946141</p>
            <p><i class="fa-solid fa-envelope"></i> support@skillMapai.com</p>
            <p><i class="fa-solid fa-location-dot"></i> Dhaka, Bangladesh</p>
        </div>

        <div class="footer-content middle">
            <h3>About Us</h3>
            <p>Empowering students and young professionals to discover relevant jobs, enhance skills, and plan their learning journey.</p>
            <div class="footer-social-icons">
                <a href="#"><i class="fa-brands fa-facebook"></i></a>
                <a href="#"><i class="fa-brands fa-linkedin"></i></a>
                <a href="#"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>

        <div class="footer-content right">
            <h3>Developer Info</h3>
            <p><i class="fa-solid fa-user"></i> Abdullah Al Mahmud</p>
            <p><i class="fa-solid fa-user"></i> Sazid Mahmud Emon</p>
            <p><i class="fa-solid fa-user"></i> Md. Hasibul Hasan</p>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2025 skillMap-ai | All rights reserved</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
