<?php
// inserthelper.php
include '../db/db.php'; 


// Optional: database থেকে আরো fresh data নিতে চাও
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=:user_id LIMIT 1");
$stmt->execute(['user_id'=>$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Collect form data safely
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $edu_level = $_POST['edu_level'];
    $experience_level = $_POST['experience_level'];
    $preferred_track = $_POST['preferred_track'];

    // Basic validation
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Hash the password before storing
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    // Prepare SQL statement
    $sql = "INSERT INTO users (fullname, email, password, edu_level, experience_level, preferred_track) 
            VALUES (:fullname, :email, :password, :edu_level, :experience_level, :preferred_track)";

    $stmt = $conn->prepare($sql);

    try {
        $stmt->execute([
            ':fullname' => $fullname,
            ':email' => $email,
            ':password' => $password_hashed,
            ':edu_level' => $edu_level,
            ':experience_level' => $experience_level,
            ':preferred_track' => $preferred_track
        ]);

        // Redirect to login page after successful registration
        header("Location: login.php");
        exit;

    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate email
            die("Email already registered.");
        } else {
            die("Error: " . $e->getMessage());
        }
    }
} else {
    die("Invalid request.");
}
?>
