<?php
// create_resume.php - Form for creating/editing resume details
require_once 'config.php';
require_once 'functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to create/edit a resume.', 'danger');
    redirect(BASE_URL . 'login.php');
}

$user_id = $_SESSION['user_id'];
$resume_id = $_GET['resume_id'] ?? null;
$resume_data = []; // Initialize to store fetched data for editing

// Fetch existing resume data if resume_id is provided
if ($resume_id) {
    // Validate that the resume belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ? AND user_id = ?");
    $stmt->execute([$resume_id, $user_id]);
    $resume_data['resume'] = $stmt->fetch();

    if (!$resume_data['resume']) {
        set_flash_message('error', 'Resume not found or you do not have permission to edit it.', 'danger');
        redirect(BASE_URL . 'dashboard.php');
    }

    // Fetch related sections
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $resume_data['profile'] = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare("SELECT * FROM experiences WHERE resume_id = ? ORDER BY start_date DESC");
    $stmt->execute([$resume_id]);
    $resume_data['experiences'] = $stmt->fetchAll() ?: [[]]; // Ensure at least one empty row for forms

    $stmt = $pdo->prepare("SELECT * FROM education WHERE resume_id = ? ORDER BY graduation_date DESC");
    $stmt->execute([$resume_id]);
    $resume_data['education'] = $stmt->fetchAll() ?: [[]];

    $stmt = $pdo->prepare("SELECT * FROM skills WHERE resume_id = ? ORDER BY skill_type, skill_name ASC");
    $stmt->execute([$resume_id]);
    $resume_data['skills'] = $stmt->fetchAll() ?: [[]];

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE resume_id = ? ORDER BY id DESC");
    $stmt->execute([$resume_id]);
    $resume_data['projects'] = $stmt->fetchAll() ?: [[]];

    // For summary, if storing in user_profiles or a generic resume_sections_data table
    // For simplicity, let's assume summary is part of user_profiles or a direct field on resume
    // Example: $resume_data['summary'] = $resume_data['profile']['summary'] ?? '';
    // Or if you added a 'summary' field directly to the 'resumes' table:
    $resume_data['summary'] = $resume_data['resume']['summary'] ?? '';

} else {
    // Initialize empty data for new resume
    $resume_data = [
        'resume' => ['resume_name' => '', 'summary' => ''],
        'profile' => [],
        'experiences' => [[]],
        'education' => [[]],
        'skills' => [[]],
        'projects' => [[]],
    ];
}

include 'includes/header.php';
?>

<h2 class="mb-4"><?php echo $resume_id ? 'Edit Resume' : 'Create New Resume'; ?></h2>

<form action="<?php echo BASE_URL; ?>process_resume.php" method="POST">
    <?php if ($resume_id): ?>
        <input type="hidden" name="resume_id" value="<?php echo $resume_id; ?>">
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Resume Title</div>
        <div class="card-body">
            <div class="mb-3">
                <label for="resume_name" class="form-label">Resume Name</label>
                <input type="text" class="form-control" id="resume_name" name="resume_name" required
                       value="<?php echo htmlspecialchars($resume_data['resume']['resume_name'] ?? ''); ?>">
                <small class="form-text text-muted">e.g., "Software Engineer Resume", "Marketing Resume"</small>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Personal Information</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="profile[full_name]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['full_name'] ?? $_SESSION['user_name']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="profile[email]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['email'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="profile[phone]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="linkedin" class="form-label">LinkedIn Profile URL</label>
                    <input type="url" class="form-control" id="linkedin" name="profile[linkedin_url]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['linkedin_url'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label for="github" class="form-label">GitHub Profile URL (Optional)</label>
                    <input type="url" class="form-control" id="github" name="profile[github_url]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['github_url'] ?? ''); ?>">
                </div>
                 <div class="col-md-6">
                    <label for="website" class="form-label">Personal Website/Portfolio (Optional)</label>
                    <input type="url" class="form-control" id="website" name="profile[website_url]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['website_url'] ?? ''); ?>">
                </div>
                 <div class="col-12">
                    <label for="address" class="form-label">Address (City, State/Country)</label>
                    <input type="text" class="form-control" id="address" name="profile[address]"
                           value="<?php echo htmlspecialchars($resume_data['profile']['address'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white">Summary / Objective</div>
        <div class="card-body">
            <div class="mb-3">
                <textarea class="form-control" id="summary" name="summary" rows="5"><?php echo htmlspecialchars($resume_data['summary'] ?? ''); ?></textarea>
                <small class="form-text text-muted">A brief overview of your professional goals and qualifications.</small>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            Work Experience
            <button type="button" class="btn btn-sm btn-light" id="addExperience">Add Experience</button>
        </div>
        <div class="card-body" id="experienceContainer">
            <?php foreach ($resume_data['experiences'] as $index => $exp): ?>
                <div class="experience-item p-3 mb-3 border rounded <?php echo $index > 0 ? 'mt-3' : ''; ?>">
                    <h6 class="text-muted">Experience #<span class="experience-index"><?php echo $index + 1; ?></span></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="company_name_<?php echo $index; ?>" class="form-label">Company Name</label>
                            <input type="text" class="form-control" id="company_name_<?php echo $index; ?>" name="experiences[<?php echo $index; ?>][company_name]" value="<?php echo htmlspecialchars($exp['company_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="job_title_<?php echo $index; ?>" class="form-label">Job Title</label>
                            <input type="text" class="form-control" id="job_title_<?php echo $index; ?>" name="experiences[<?php echo $index; ?>][job_title]" value="<?php echo htmlspecialchars($exp['job_title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="start_date_exp_<?php echo $index; ?>" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date_exp_<?php echo $index; ?>" name="experiences[<?php echo $index; ?>][start_date]" value="<?php echo htmlspecialchars($exp['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date_exp_<?php echo $index; ?>" class="form-label">End Date (or 'Present')</label>
                            <input type="text" class="form-control" id="end_date_exp_<?php echo $index; ?>" name="experiences[<?php echo $index; ?>][end_date]" value="<?php echo htmlspecialchars($exp['end_date'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="description_exp_<?php echo $index; ?>" class="form-label">Responsibilities/Achievements (Use bullet points)</label>
                            <textarea class="form-control" id="description_exp_<?php echo $index; ?>" name="experiences[<?php echo $index; ?>][description]" rows="4"><?php echo htmlspecialchars($exp['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Experience</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            Education
            <button type="button" class="btn btn-sm btn-dark" id="addEducation">Add Education</button>
        </div>
        <div class="card-body" id="educationContainer">
            <?php foreach ($resume_data['education'] as $index => $edu): ?>
                <div class="education-item p-3 mb-3 border rounded <?php echo $index > 0 ? 'mt-3' : ''; ?>">
                    <h6 class="text-muted">Education #<span class="education-index"><?php echo $index + 1; ?></span></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="institution_<?php echo $index; ?>" class="form-label">Institution</label>
                            <input type="text" class="form-control" id="institution_<?php echo $index; ?>" name="education[<?php echo $index; ?>][institution]" value="<?php echo htmlspecialchars($edu['institution'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="degree_<?php echo $index; ?>" class="form-label">Degree/Certificate</label>
                            <input type="text" class="form-control" id="degree_<?php echo $index; ?>" name="education[<?php echo $index; ?>][degree]" value="<?php echo htmlspecialchars($edu['degree'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="major_<?php echo $index; ?>" class="form-label">Major/Field of Study</label>
                            <input type="text" class="form-control" id="major_<?php echo $index; ?>" name="education[<?php echo $index; ?>][major]" value="<?php echo htmlspecialchars($edu['major'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="graduation_date_<?php echo $index; ?>" class="form-label">Graduation Date</label>
                            <input type="date" class="form-control" id="graduation_date_<?php echo $index; ?>" name="education[<?php echo $index; ?>][graduation_date]" value="<?php echo htmlspecialchars($edu['graduation_date'] ?? ''); ?>">
                        </div>
                    </div>
                    <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Education</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            Skills
            <button type="button" class="btn btn-sm btn-light" id="addSkill">Add Skill</button>
        </div>
        <div class="card-body" id="skillContainer">
            <?php foreach ($resume_data['skills'] as $index => $skill): ?>
                <div class="skill-item p-3 mb-3 border rounded <?php echo $index > 0 ? 'mt-3' : ''; ?>">
                    <h6 class="text-muted">Skill #<span class="skill-index"><?php echo $index + 1; ?></span></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="skill_name_<?php echo $index; ?>" class="form-label">Skill Name</label>
                            <input type="text" class="form-control" id="skill_name_<?php echo $index; ?>" name="skills[<?php echo $index; ?>][skill_name]" value="<?php echo htmlspecialchars($skill['skill_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="skill_type_<?php echo $index; ?>" class="form-label">Skill Type</label>
                            <input type="text" class="form-control" id="skill_type_<?php echo $index; ?>" name="skills[<?php echo $index; ?>][skill_type]" placeholder="e.g., Programming Languages, Tools, Soft Skills" value="<?php echo htmlspecialchars($skill['skill_type'] ?? ''); ?>">
                        </div>
                    </div>
                    <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Skill</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            Projects (Optional)
            <button type="button" class="btn btn-sm btn-light" id="addProject">Add Project</button>
        </div>
        <div class="card-body" id="projectContainer">
            <?php foreach ($resume_data['projects'] as $index => $proj): ?>
                <div class="project-item p-3 mb-3 border rounded <?php echo $index > 0 ? 'mt-3' : ''; ?>">
                    <h6 class="text-muted">Project #<span class="project-index"><?php echo $index + 1; ?></span></h6>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="project_title_<?php echo $index; ?>" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="project_title_<?php echo $index; ?>" name="projects[<?php echo $index; ?>][project_title]" value="<?php echo htmlspecialchars($proj['project_title'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="project_url_<?php echo $index; ?>" class="form-label">Project URL (Optional)</label>
                            <input type="url" class="form-control" id="project_url_<?php echo $index; ?>" name="projects[<?php echo $index; ?>][project_url]" value="<?php echo htmlspecialchars($proj['project_url'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="technologies_<?php echo $index; ?>" class="form-label">Technologies Used (Comma-separated)</label>
                            <input type="text" class="form-control" id="technologies_<?php echo $index; ?>" name="projects[<?php echo $index; ?>][technologies]" value="<?php echo htmlspecialchars($proj['technologies'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label for="project_description_<?php echo $index; ?>" class="form-label">Description</label>
                            <textarea class="form-control" id="project_description_<?php echo $index; ?>" name="projects[<?php echo $index; ?>][description]" rows="3"><?php echo htmlspecialchars($proj['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <?php if ($index > 0): ?>
                        <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Project</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


    <button type="submit" class="btn btn-primary btn-lg mb-5"><?php echo $resume_id ? 'Update Resume' : 'Save Resume'; ?></button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let experienceIndex = <?php echo count($resume_data['experiences']); ?>;
        let educationIndex = <?php echo count($resume_data['education']); ?>;
        let skillIndex = <?php echo count($resume_data['skills']); ?>;
        let projectIndex = <?php echo count($resume_data['projects']); ?>;

        function updateIndices(containerId, itemClass, indexClass) {
            const container = document.getElementById(containerId);
            const items = container.querySelectorAll(`.${itemClass}`);
            items.forEach((item, i) => {
                // Update item index for display
                const indexSpan = item.querySelector(`.${indexClass}`);
                if (indexSpan) {
                    indexSpan.textContent = i + 1;
                }

                // Update input names
                item.querySelectorAll('[name*="["]').forEach(input => {
                    const originalName = input.name;
                    const newName = originalName.replace(/\[\d+\]/, `[${i}]`);
                    input.name = newName;
                    // Also update IDs if they follow a similar pattern for labels
                    const originalId = input.id;
                    if (originalId) {
                        const newId = originalId.replace(/_\d+/, `_${i}`);
                        input.id = newId;
                        const label = item.querySelector(`label[for="${originalId}"]`);
                        if (label) {
                            label.setAttribute('for', newId);
                        }
                    }
                });
            });
        }

        function setupRemoveButtons(containerId, itemClass) {
            const container = document.getElementById(containerId);
            container.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-item')) {
                    const itemToRemove = event.target.closest(`.${itemClass}`);
                    if (itemToRemove) {
                        itemToRemove.remove();
                        // Re-index after removal
                        if (containerId === 'experienceContainer') experienceIndex--;
                        if (containerId === 'educationContainer') educationIndex--;
                        if (containerId === 'skillContainer') skillIndex--;
                        if (containerId === 'projectContainer') projectIndex--;
                        updateIndices(containerId, itemClass, itemClass.replace('-item', '-index'));
                    }
                }
            });
        }

        // Add Experience
        document.getElementById('addExperience').addEventListener('click', function() {
            const container = document.getElementById('experienceContainer');
            const newIndex = experienceIndex++;
            const newItem = document.createElement('div');
            newItem.classList.add('experience-item', 'p-3', 'mb-3', 'border', 'rounded', 'mt-3');
            newItem.innerHTML = `
                <h6 class="text-muted">Experience #${newIndex + 1}</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="company_name_${newIndex}" class="form-label">Company Name</label>
                        <input type="text" class="form-control" id="company_name_${newIndex}" name="experiences[${newIndex}][company_name]">
                    </div>
                    <div class="col-md-6">
                        <label for="job_title_${newIndex}" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="job_title_${newIndex}" name="experiences[${newIndex}][job_title]">
                    </div>
                    <div class="col-md-6">
                        <label for="start_date_exp_${newIndex}" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date_exp_${newIndex}" name="experiences[${newIndex}][start_date]">
                    </div>
                    <div class="col-md-6">
                        <label for="end_date_exp_${newIndex}" class="form-label">End Date (or 'Present')</label>
                        <input type="text" class="form-control" id="end_date_exp_${newIndex}" name="experiences[${newIndex}][end_date]">
                    </div>
                    <div class="col-12">
                        <label for="description_exp_${newIndex}" class="form-label">Responsibilities/Achievements (Use bullet points)</label>
                        <textarea class="form-control" id="description_exp_${newIndex}" name="experiences[${newIndex}][description]" rows="4"></textarea>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Experience</button>
            `;
            container.appendChild(newItem);
            updateIndices('experienceContainer', 'experience-item', 'experience-index');
        });

        // Add Education
        document.getElementById('addEducation').addEventListener('click', function() {
            const container = document.getElementById('educationContainer');
            const newIndex = educationIndex++;
            const newItem = document.createElement('div');
            newItem.classList.add('education-item', 'p-3', 'mb-3', 'border', 'rounded', 'mt-3');
            newItem.innerHTML = `
                <h6 class="text-muted">Education #${newIndex + 1}</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="institution_${newIndex}" class="form-label">Institution</label>
                        <input type="text" class="form-control" id="institution_${newIndex}" name="education[${newIndex}][institution]">
                    </div>
                    <div class="col-md-6">
                        <label for="degree_${newIndex}" class="form-label">Degree/Certificate</label>
                        <input type="text" class="form-control" id="degree_${newIndex}" name="education[${newIndex}][degree]">
                    </div>
                    <div class="col-md-6">
                        <label for="major_${newIndex}" class="form-label">Major/Field of Study</label>
                        <input type="text" class="form-control" id="major_${newIndex}" name="education[${newIndex}][major]">
                    </div>
                    <div class="col-md-6">
                        <label for="graduation_date_${newIndex}" class="form-label">Graduation Date</label>
                        <input type="date" class="form-control" id="graduation_date_${newIndex}" name="education[${newIndex}][graduation_date]">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Education</button>
            `;
            container.appendChild(newItem);
            updateIndices('educationContainer', 'education-item', 'education-index');
        });

        // Add Skill
        document.getElementById('addSkill').addEventListener('click', function() {
            const container = document.getElementById('skillContainer');
            const newIndex = skillIndex++;
            const newItem = document.createElement('div');
            newItem.classList.add('skill-item', 'p-3', 'mb-3', 'border', 'rounded', 'mt-3');
            newItem.innerHTML = `
                <h6 class="text-muted">Skill #${newIndex + 1}</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="skill_name_${newIndex}" class="form-label">Skill Name</label>
                        <input type="text" class="form-control" id="skill_name_${newIndex}" name="skills[${newIndex}][skill_name]">
                    </div>
                    <div class="col-md-6">
                        <label for="skill_type_${newIndex}" class="form-label">Skill Type</label>
                        <input type="text" class="form-control" id="skill_type_${newIndex}" name="skills[${newIndex}][skill_type]" placeholder="e.g., Programming Languages, Tools, Soft Skills">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Skill</button>
            `;
            container.appendChild(newItem);
            updateIndices('skillContainer', 'skill-item', 'skill-index');
        });

         // Add Project
        document.getElementById('addProject').addEventListener('click', function() {
            const container = document.getElementById('projectContainer');
            const newIndex = projectIndex++;
            const newItem = document.createElement('div');
            newItem.classList.add('project-item', 'p-3', 'mb-3', 'border', 'rounded', 'mt-3');
            newItem.innerHTML = `
                <h6 class="text-muted">Project #${newIndex + 1}</h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="project_title_${newIndex}" class="form-label">Project Title</label>
                        <input type="text" class="form-control" id="project_title_${newIndex}" name="projects[${newIndex}][project_title]">
                    </div>
                    <div class="col-md-4">
                        <label for="project_url_${newIndex}" class="form-label">Project URL (Optional)</label>
                        <input type="url" class="form-control" id="project_url_${newIndex}" name="projects[${newIndex}][project_url]">
                    </div>
                    <div class="col-12">
                        <label for="technologies_${newIndex}" class="form-label">Technologies Used (Comma-separated)</label>
                        <input type="text" class="form-control" id="technologies_${newIndex}" name="projects[${newIndex}][technologies]">
                    </div>
                    <div class="col-12">
                        <label for="project_description_${newIndex}" class="form-label">Description</label>
                        <textarea class="form-control" id="project_description_${newIndex}" name="projects[${newIndex}][description]" rows="3"></textarea>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger mt-3 remove-item">Remove Project</button>
            `;
            container.appendChild(newItem);
            updateIndices('projectContainer', 'project-item', 'project-index');
        });


        // Setup event delegation for remove buttons (since they are dynamically added)
        setupRemoveButtons('experienceContainer', 'experience-item');
        setupRemoveButtons('educationContainer', 'education-item');
        setupRemoveButtons('skillContainer', 'skill-item');
        setupRemoveButtons('projectContainer', 'project-item');

        // Initial re-indexing for loaded items
        updateIndices('experienceContainer', 'experience-item', 'experience-index');
        updateIndices('educationContainer', 'education-item', 'education-index');
        updateIndices('skillContainer', 'skill-item', 'skill-index');
        updateIndices('projectContainer', 'project-item', 'project-index');

    });
</script>

<?php include 'includes/footer.php'; ?>