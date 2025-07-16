<?php
// generate_pdf.php - Generates a PDF version of the resume with ONE-PAGE optimization
require_once 'config.php';
require_once 'functions.php';
require_once 'vendor/autoload.php'; // Autoload Composer dependencies (Dompdf)

use Dompdf\Dompdf;
use Dompdf\Options;

// Ensure user is logged in
if (!is_logged_in()) {
    set_flash_message('error', 'You must be logged in to view resumes.', 'danger');
    redirect(BASE_URL . 'login.php');
}

$resume_id = $_GET['resume_id'] ?? null;
if (!$resume_id) {
    set_flash_message('error', 'No resume ID provided.', 'danger');
    redirect(BASE_URL . 'dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Fetch resume data, ensuring it belongs to the user
$stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ? AND user_id = ?");
$stmt->execute([$resume_id, $user_id]);
$resume = $stmt->fetch();

if (!$resume) {
    set_flash_message('error', 'Resume not found or you do not have permission to view it.', 'danger');
    redirect(BASE_URL . 'dashboard.php');
}

// Fetch related sections
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$profile = $stmt->fetch() ?: [];

$stmt = $pdo->prepare("SELECT * FROM experiences WHERE resume_id = ? ORDER BY start_date DESC");
$stmt->execute([$resume_id]);
$experiences = $stmt->fetchAll() ?: [];

$stmt = $pdo->prepare("SELECT * FROM education WHERE resume_id = ? ORDER BY graduation_date DESC");
$stmt->execute([$resume_id]);
$education = $stmt->fetchAll() ?: [];

$stmt = $pdo->prepare("SELECT * FROM skills WHERE resume_id = ? ORDER BY skill_type, skill_name ASC");
$stmt->execute([$resume_id]);
$skills = $stmt->fetchAll() ?: [];

$stmt = $pdo->prepare("SELECT * FROM projects WHERE resume_id = ? ORDER BY id DESC");
$stmt->execute([$resume_id]);
$projects = $stmt->fetchAll() ?: [];


// --- Start building the HTML for the PDF ---
ob_start(); // Start output buffering
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($resume['resume_name'] ?? 'Resume'); ?></title>
    <style>
        /* Base styles for maximum compactness */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.2; /* Very tight line height */
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 9.5pt; /* Smaller base font size */
        }
        /* Define page margins for Dompdf */
        @page {
            margin: 0.6in; /* Even smaller margins for more content space */
        }
        .container {
            /* No explicit width or padding here, @page handles overall margins */
        }
        h1, h2, h3, h4 {
            color: #0056b3;
            margin-bottom: 2px; /* Minimal margin below headings */
            margin-top: 0;
            padding: 0;
        }
        h1 {
            text-align: center;
            font-size: 18pt; /* Reduced size for main name */
            margin-bottom: 6px;
        }
        h2 {
            font-size: 13pt; /* Smaller section headings */
            border-bottom: 1.5px solid #0056b3; /* Thinner border */
            padding-bottom: 2px;
            margin-top: 12px; /* Less space above sections */
            margin-bottom: 8px;
        }
        h3 {
            font-size: 10.5pt; /* Smaller sub-headings */
            margin-top: 6px;
            margin-bottom: 2px;
        }
        .header-contact {
            text-align: center;
            margin-bottom: 12px;
            font-size: 8.5pt; /* Very small font for contact details */
        }
        .header-contact p {
            margin: 0; /* No margin between contact lines */
            display: inline-block; /* Allow items to sit next to each other */
            margin-right: 8px; /* Spacing between contact info items */
        }
        .header-contact p:last-child {
            margin-right: 0;
        }
        .section {
            margin-bottom: 12px; /* Less space between major sections */
        }
        /* Crucial adjustments for section items (Experience, Education, Projects) */
        .section-item {
            margin-bottom: 8px; /* Less space between individual items */
            position: relative; /* Positioning context for dates */
            padding-right: 110px; /* Make space for dates */
            box-sizing: border-box; /* Include padding in element's total width */
        }
        .section-item:last-child {
            margin-bottom: 0;
        }
        .job-title, .degree, .project-title {
            font-weight: bold;
            margin-bottom: 2px;
            line-height: 1.1; /* Very tight line height for titles */
            display: block;
        }
        .company-name, .institution {
            font-style: italic;
            color: #555;
            margin-bottom: 2px;
            display: block;
            font-size: 9pt; /* Slightly smaller */
        }
        /* Dates positioning */
        .dates {
            position: absolute;
            top: 0;
            right: 0;
            width: 100px; /* Reduced width for dates */
            text-align: right;
            color: #666;
            font-size: 8.5pt; /* Smaller font for dates */
            line-height: 1.1; /* Match line-height of main content */
        }
        ul {
            list-style: disc;
            margin-left: 12px; /* Even less indentation */
            padding: 0;
            margin-top: 2px; /* Minimal space above lists */
        }
        ul li {
            margin-bottom: 1px; /* Very tight bullet points */
            line-height: 1.15; /* Tighter line height for list items */
            font-size: 9.2pt; /* Slightly smaller for description text */
        }
        .skills-category {
            font-weight: bold;
            margin-top: 6px;
            margin-bottom: 2px;
            font-size: 9.5pt;
        }
        .skills-list-inline {
            list-style: none;
            padding: 0;
            margin: 0;
            line-height: 1.2;
        }
        .skills-list-inline li {
            display: inline;
            margin-right: 4px; /* Tighter spacing between skills */
            font-size: 9.2pt;
        }
        a {
            color: #0056b3;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        /* No longer needed with absolute positioning for dates */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($profile['full_name'] ?? $_SESSION['user_name']); ?></h1>
        <div class="header-contact">
            <?php if (!empty($profile['address'])): ?>
                <p><?php echo htmlspecialchars($profile['address']); ?></p>
            <?php endif; ?>
            <?php if (!empty($profile['email'])): ?>
                <p>Email: <?php echo htmlspecialchars($profile['email']); ?></p>
            <?php endif; ?>
            <?php if (!empty($profile['phone'])): ?>
                <p>Phone: <?php echo htmlspecialchars($profile['phone']); ?></p>
            <?php endif; ?>
            <?php if (!empty($profile['linkedin_url'])): ?>
                <p>LinkedIn: <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>"><?php echo htmlspecialchars($profile['linkedin_url']); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($profile['github_url'])): ?>
                <p>GitHub: <a href="<?php echo htmlspecialchars($profile['github_url']); ?>"><?php echo htmlspecialchars($profile['github_url']); ?></a></p>
            <?php endif; ?>
            <?php if (!empty($profile['website_url'])): ?>
                <p>Website: <a href="<?php echo htmlspecialchars($profile['website_url']); ?>"><?php echo htmlspecialchars($profile['website_url']); ?></a></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($resume['summary'])): ?>
            <div class="section">
                <h2>Summary</h2>
                <p style="margin-top: 2px;"><?php echo nl2br(htmlspecialchars($resume['summary'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($experiences)): ?>
            <div class="section">
                <h2>Work Experience</h2>
                <?php foreach ($experiences as $exp): ?>
                    <div class="section-item">
                        <p class="dates"><?php echo htmlspecialchars($exp['start_date'] . ' - ' . $exp['end_date']); ?></p>
                        <p class="job-title"><?php echo htmlspecialchars($exp['job_title']); ?></p>
                        <p class="company-name"><?php echo htmlspecialchars($exp['company_name']); ?></p>
                        <?php if (!empty($exp['description'])): ?>
                            <ul>
                                <?php
                                $responsibilities = explode("\n", $exp['description']);
                                foreach ($responsibilities as $res) {
                                    $res = trim($res);
                                    if (!empty($res)) {
                                        echo '<li>' . htmlspecialchars($res) . '</li>';
                                    }
                                }
                                ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($education)): ?>
            <div class="section">
                <h2>Education</h2>
                <?php foreach ($education as $edu): ?>
                    <div class="section-item">
                        <p class="dates"><?php echo htmlspecialchars($edu['graduation_date']); ?></p>
                        <p class="degree"><?php echo htmlspecialchars($edu['degree']); ?><?php echo !empty($edu['major']) ? ' in ' . htmlspecialchars($edu['major']) : ''; ?></p>
                        <p class="institution"><?php echo htmlspecialchars($edu['institution']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($skills)): ?>
            <div class="section">
                <h2>Skills</h2>
                <?php
                // Group skills by type
                $groupedSkills = [];
                foreach ($skills as $skill) {
                    $type = !empty($skill['skill_type']) ? htmlspecialchars($skill['skill_type']) : 'Technical Skills'; // Default type if not specified
                    $groupedSkills[$type][] = htmlspecialchars($skill['skill_name']);
                }
                ?>
                <?php foreach ($groupedSkills as $type => $skillNames): ?>
                    <p class="skills-category"><?php echo $type; ?>:</p>
                    <ul class="skills-list-inline">
                        <li><?php echo implode(', ', $skillNames); ?></li>
                    </ul>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($projects)): ?>
            <div class="section">
                <h2>Projects</h2>
                <?php foreach ($projects as $proj): ?>
                    <div class="section-item">
                        <p class="project-title">
                            <?php echo htmlspecialchars($proj['project_title']); ?>
                            <?php if (!empty($proj['project_url'])): ?>
                                <small>(<a href="<?php echo htmlspecialchars($proj['project_url']); ?>"><?php echo htmlspecialchars($proj['project_url']); ?></a>)</small>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($proj['technologies'])): ?>
                            <p style="margin-bottom: 2px;"><strong>Technologies:</strong> <?php echo htmlspecialchars($proj['technologies']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($proj['description'])): ?>
                            <ul>
                                <?php
                                $project_details = explode("\n", $proj['description']);
                                foreach ($project_details as $detail) {
                                    $detail = trim($detail);
                                    if (!empty($detail)) {
                                        echo '<li>' . htmlspecialchars($detail) . '</li>';
                                    }
                                }
                                ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
<?php
$html = ob_get_clean(); // Get the buffered HTML content

// Instantiate Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
// Consider setting a default font if Arial is not rendering correctly or if you need specific Unicode chars
// $options->set('defaultFont', 'DejaVu Sans'); // You might need to install DejaVu fonts for this

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream(
    str_replace(' ', '_', $resume['resume_name'] ?? 'resume') . '.pdf',
    array("Attachment" => false) // Set to true to force download, false to open in browser
);
exit();
?>