<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Base'; ?></title>

    <?php /* for header and footer */ ?>
    <?php /* <!-- Google Material Symbols --> */ ?>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>


    <?php /* <!-- Bootstrap CSS --> */ ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"  rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
<link rel="shortcut icon" href="root/img/logo.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="root/css/style.css">
    <?php if(isset($additional_links)) { echo $additional_links; } ?>
</head>
<body>
    <?php include "root/header.php"; ?>
    <?php if(isset($content)) { echo $content; } ?>
    <?php include "root/footer.php"; ?>


    <!-- Bootstrap JS -->
    <script src="root/js/base.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>