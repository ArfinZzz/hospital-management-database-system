<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM `USER` WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['user_id'];
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid email or password.';
}

$pageTitle = 'Login';
include 'includes/header.php';
?>
<h1>EmergencyLink Login</h1>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="simple-form">
    <label>Email <input type="email" name="email" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn primary" type="submit">Login</button>
</form>
<p>New user? <a href="register.php">Register here</a>.</p>
<?php include 'includes/footer.php'; ?>
