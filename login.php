<?php
// login.php - User login
require_once 'config.php';
require_once 'functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . 'dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            set_flash_message('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
            redirect(BASE_URL . 'dashboard.php');
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors), 'danger');
    }
}

include 'includes/header.php';
?>

<h2 class="mb-4">Login</h2>
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
    <p class="mt-3">Don't have an account? <a href="<?php echo BASE_URL; ?>register.php">Register here</a>.</p>
</form>

<?php include 'includes/footer.php'; ?>