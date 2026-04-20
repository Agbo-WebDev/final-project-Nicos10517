<?php
// This is for loading a session and requiring connection to the database
session_start();
require 'db.php';

// Gotta have that handler!
$error = "";

// This is basically the heart of the login, it requests the username entered from the database and sees if it exists and if the passcode matches
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: sandbox.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SQL Sandbox</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="style.css">
</head>

<!-- Styling time!! Sort of, this is the UI of the login interface-->
<body>
    <div class="auth-box">
        <h2>🔐 Log In</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">USERNAME</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">PASSWORD</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">LOG IN</button>
        </form>

        <!--In case you do not have an account, this is the href to register.php which will add a new user to the database-->

        <div class="auth-link">
            No account? <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>
