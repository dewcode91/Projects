<?php
// delete_resume.php
require_once 'config.php';
require_once 'functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to delete a resume.', 'danger');
    redirect(BASE_URL . 'login.php');
}

if (isset($_GET['resume_id'])) {
    $resume_id = $_GET['resume_id'];
    $user_id = $_SESSION['user_id'];

    $pdo->beginTransaction(); // Start transaction

    try {
        // Delete related data first (due to foreign key constraints if ON DELETE CASCADE is not set)
        // If your tables have ON DELETE CASCADE, deleting from 'resumes' will automatically delete from others.
        // Assuming no CASCADE for explicit deletion control:
        $stmt = $pdo->prepare("DELETE FROM experiences WHERE resume_id = ?");
        $stmt->execute([$resume_id]);

        $stmt = $pdo->prepare("DELETE FROM education WHERE resume_id = ?");
        $stmt->execute([$resume_id]);

        $stmt = $pdo->prepare("DELETE FROM skills WHERE resume_id = ?");
        $stmt->execute([$resume_id]);

        $stmt = $pdo->prepare("DELETE FROM projects WHERE resume_id = ?");
        $stmt->execute([$resume_id]);

        // Finally, delete the resume entry itself, ensuring it belongs to the user
        $stmt = $pdo->prepare("DELETE FROM resumes WHERE id = ? AND user_id = ?");
        $stmt->execute([$resume_id, $user_id]);

        $pdo->commit(); // Commit transaction
        set_flash_message('success', 'Resume deleted successfully!');
    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on error
        set_flash_message('error', 'Error deleting resume: ' . $e->getMessage(), 'danger');
    }
} else {
    set_flash_message('error', 'No resume ID provided for deletion.', 'danger');
}

redirect(BASE_URL . 'dashboard.php');
?>