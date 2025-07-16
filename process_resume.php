<?php
// process_resume.php - Handles saving resume data to database
require_once 'config.php';
require_once 'functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to save resume data.', 'danger');
    redirect(BASE_URL . 'login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $resume_id = $_POST['resume_id'] ?? null;

    $resume_name = trim($_POST['resume_name'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $profile_data = $_POST['profile'] ?? [];
    $experiences = $_POST['experiences'] ?? [];
    $education = $_POST['education'] ?? [];
    $skills = $_POST['skills'] ?? [];
    $projects = $_POST['projects'] ?? [];

    $pdo->beginTransaction(); // Start transaction for atomicity

    try {
        // 1. Save/Update Resume Main Entry
        if ($resume_id) {
            // Update existing resume
            $stmt = $pdo->prepare("UPDATE resumes SET resume_name = ?, summary = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$resume_name, $summary, $resume_id, $user_id]);
        } else {
            // Create new resume
            $stmt = $pdo->prepare("INSERT INTO resumes (user_id, resume_name, summary) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $resume_name, $summary]);
            $resume_id = $pdo->lastInsertId();
        }

        // 2. Save/Update User Profile (ensure it exists or create one)
        $profile_full_name = $profile_data['full_name'] ?? '';
        $profile_email = $profile_data['email'] ?? '';
        $profile_phone = $profile_data['phone'] ?? '';
        $profile_linkedin_url = $profile_data['linkedin_url'] ?? '';
        $profile_github_url = $profile_data['github_url'] ?? '';
        $profile_website_url = $profile_data['website_url'] ?? '';
        $profile_address = $profile_data['address'] ?? '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() > 0) {
            // Update existing profile
            $stmt = $pdo->prepare("UPDATE user_profiles SET full_name = ?, email = ?, phone = ?, linkedin_url = ?, github_url = ?, website_url = ?, address = ? WHERE user_id = ?");
            $stmt->execute([
                $profile_full_name, $profile_email, $profile_phone,
                $profile_linkedin_url, $profile_github_url, $profile_website_url, $profile_address, $user_id
            ]);
        } else {
            // Insert new profile
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, full_name, email, phone, linkedin_url, github_url, website_url, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id, $profile_full_name, $profile_email, $profile_phone,
                $profile_linkedin_url, $profile_github_url, $profile_website_url, $profile_address
            ]);
        }

        // 3. Save/Update Work Experiences
        // Delete old experiences first to handle removals
        $stmt = $pdo->prepare("DELETE FROM experiences WHERE resume_id = ?");
        $stmt->execute([$resume_id]);
        foreach ($experiences as $exp) {
            // Only insert if company name is not empty
            if (!empty(trim($exp['company_name']))) {
                $stmt = $pdo->prepare("INSERT INTO experiences (resume_id, company_name, job_title, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $resume_id,
                    trim($exp['company_name']),
                    trim($exp['job_title']),
                    empty(trim($exp['start_date'])) ? null : trim($exp['start_date']),
                    trim($exp['end_date']),
                    trim($exp['description'])
                ]);
            }
        }

        // 4. Save/Update Education
        $stmt = $pdo->prepare("DELETE FROM education WHERE resume_id = ?");
        $stmt->execute([$resume_id]);
        foreach ($education as $edu) {
            if (!empty(trim($edu['institution']))) {
                $stmt = $pdo->prepare("INSERT INTO education (resume_id, institution, degree, major, graduation_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $resume_id,
                    trim($edu['institution']),
                    trim($edu['degree']),
                    trim($edu['major']),
                    empty(trim($edu['graduation_date'])) ? null : trim($edu['graduation_date'])
                ]);
            }
        }

        // 5. Save/Update Skills
        $stmt = $pdo->prepare("DELETE FROM skills WHERE resume_id = ?");
        $stmt->execute([$resume_id]);
        foreach ($skills as $skill) {
            if (!empty(trim($skill['skill_name']))) {
                $stmt = $pdo->prepare("INSERT INTO skills (resume_id, skill_name, skill_type) VALUES (?, ?, ?)");
                $stmt->execute([
                    $resume_id,
                    trim($skill['skill_name']),
                    trim($skill['skill_type'])
                ]);
            }
        }

        // 6. Save/Update Projects
        $stmt = $pdo->prepare("DELETE FROM projects WHERE resume_id = ?");
        $stmt->execute([$resume_id]);
        foreach ($projects as $project) {
            if (!empty(trim($project['project_title']))) {
                $stmt = $pdo->prepare("INSERT INTO projects (resume_id, project_title, project_url, technologies, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $resume_id,
                    trim($project['project_title']),
                    trim($project['project_url']),
                    trim($project['technologies']),
                    trim($project['description'])
                ]);
            }
        }


        $pdo->commit(); // Commit transaction
        set_flash_message('success', 'Resume saved successfully!');
        redirect(BASE_URL . 'dashboard.php');

    } catch (PDOException $e) {
        $pdo->rollBack(); // Rollback on error
        set_flash_message('error', 'Error saving resume: ' . $e->getMessage(), 'danger');
        // Redirect back to the form, possibly with existing data (complex in plain PHP without a framework)
        // For simplicity, we just redirect to dashboard
        redirect(BASE_URL . 'dashboard.php');
    }

} else {
    // Not a POST request, redirect to create_resume page
    set_flash_message('info', 'Invalid request.', 'warning');
    redirect(BASE_URL . 'create_resume.php');
}
?>