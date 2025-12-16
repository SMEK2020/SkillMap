<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkillMap Registration</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/register.css">


</head>
<body>

<div class="wrapper">



    <!-- Bubble background -->
    <ul class="colorlib-bubbles">
        <li></li><li></li><li></li><li></li><li></li>
        <li></li><li></li><li></li><li></li><li></li>
    </ul>

    <!-- Registration Form -->
    <div class="main-register">
    <?php
if (isset($_GET['error']) && $_GET['error'] === 'not_registered') {
    echo "<p style='color:red;'>You are not registered yet, please register first.</p>";
}
?>
        <h1>Registration</h1>

        <form action="inserthelper.php" method="POST" class="signup-form">

<!-- Row 1 -->
<div class="form-row">
  <input type="text" name="fullname" placeholder="Full Name" required>
  <input type="email" name="email" placeholder="Email" required>
</div>

<!-- Row 2 -->
<div class="form-row">
  <input type="password" name="password" placeholder="Password" required minlength="8" maxlength="20"
         oninvalid="this.setCustomValidity('Password must be between 8 and 20 characters')"
         oninput="this.setCustomValidity('')">
  <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8" maxlength="20"
         oninvalid="this.setCustomValidity('Confirm Password must be between 8 and 20 characters')"
         oninput="this.setCustomValidity('')">
</div>


<!-- Row 3 -->
<div class="form-row">
  <select name="edu_level" required>
    <option value="">Education Level</option>
    <option value="SSC">SSC</option>
    <option value="HSC">HSC</option>
    <option value="Bachelor">Bachelor</option>
    <option value="Masters">Masters</option>
  </select>

  <select name="experience_level" required>
    <option value="">Experience Level</option>
    <option value="Fresher">Fresher</option>
    <option value="Junior">Junior</option>
    <option value="Mid">Mid</option>
    <option value="Senior">Senior</option>
  </select>

  <select name="preferred_track" required>
    <option value="">Preferred Track</option>
    <option value="Web Development">Web Development</option>
    <option value="Data Science">Data Science</option>
    <option value="Design">Design</option>
    <option value="Marketing">Marketing</option>
  </select>
</div>

<!-- Terms -->
<div class="terms">
  <label><input type="checkbox" name="terms" required> I agree to the Terms & Conditions</label>
</div>

<input type="submit" value="SIGN UP" class="btn-submit">
<p>Already have an account? <a href="login.php">Login here</a></p>
</form>

    </div>

</div>

</body>
</html>
