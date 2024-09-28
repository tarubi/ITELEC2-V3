<?php
    include_once 'config/settings-configuration.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
  

    <div class="container">
        <div class="form-wrapper">
            <h2>Sign In</h2>
            <form action="dashboard/admin/authentication/admin-class.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token ?>" >
                <input type="email" name="email" placeholder="Enter Email" required> <br>
                <input type="password" name="password" placeholder="Password" required> <br>
               <button type="submit" name="btn-signin">Sign In</button>
            </form>
        </div>

        <div class="form-wrapper">
            <h2>Register</h2>
            <form action="dashboard/admin/authentication/admin-class.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token ?>" >
                <input type="text" name="username" placeholder="Enter Username" required> <br>
                <input type="email" name="email" placeholder="Enter Email" required> <br>
                <input type="password" name="password" placeholder="Password" required> <br>
               <button type="submit" name="btn-signup">Sign Up</button>
            </form>
        </div>
    </div>

</body>
</html>