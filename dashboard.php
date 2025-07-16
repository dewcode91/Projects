<?php
// dashboard.php - User dashboard to view and manage resumes
require_once 'config.php';
require_once 'functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to access the dashboard.', 'danger');
    redirect(BASE_URL . 'login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch user's resumes
$stmt = $pdo->prepare("SELECT id, resume_name, created_at FROM resumes WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$resumes = $stmt->fetchAll();

include 'includes/header.php';
?>

<h2 class="mb-4">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
<p>Here you can manage your resumes.</p>

<div class="d-grid gap-2 mb-4">
    <a href="<?php echo BASE_URL; ?>create_resume.php" class="btn btn-success btn-lg">
        <i class="bi bi-plus-circle"></i> Create New Resume
    </a>
</div>

<?php if (empty($resumes)): ?>
    <div class="alert alert-info">You haven't created any resumes yet. Click the button above to get started!</div>
<?php else: ?>
    <h3>Your Resumes</h3>
    <div class="row">
        <?php foreach ($resumes as $resume): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($resume['resume_name']); ?></h5>
                        <p class="card-text">Created: <?php echo date('F j, Y', strtotime($resume['created_at'])); ?></p>
                        <a href="<?php echo BASE_URL; ?>create_resume.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="<?php echo BASE_URL; ?>generate_pdf.php?resume_id=<?php echo $resume['id']; ?>" class="btn btn-sm btn-info" target="_blank">View PDF</a>
                        <a href="#" class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $resume['id']; ?>)">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function confirmDelete(resumeId) {
    if (confirm('Are you sure you want to delete this resume? This action cannot be undone.')) {
        window.location.href = '<?php echo BASE_URL; ?>delete_resume.php?resume_id=' + resumeId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>