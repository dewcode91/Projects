-----

# PHP Resume Builder

This is a simple, plain PHP application that allows users to create, manage, and generate professional resumes in PDF format. It's designed to be straightforward to set up and use, providing a basic foundation for a web-based resume creation tool.

-----

## Features

  * **User Authentication:** Secure user registration and login system.
  * **Profile Management:** Users can manage their personal contact information.
  * **Dynamic Resume Sections:** Easily add, edit, and delete:
      * Work Experience
      * Education
      * Skills (categorized)
      * Projects
  * **PDF Generation:** Generate a professional-looking PDF resume from the entered data.
  * **One-Page Optimization:** CSS is optimized to help fit typical resume content onto a single page, a highly sought-after feature by recruiters.
  * **Flash Messages:** Provides user feedback for actions (e.g., success, error messages).

-----

## Technologies Used

  * **PHP:** Core server-side logic.
  * **MySQL:** Database for storing user and resume data.
  * **HTML5 & CSS3:** For front-end structure and styling.
  * **Bootstrap 5:** Responsive design framework for a clean UI.
  * **Dompdf:** PHP library for converting HTML to PDF.
  * **Composer:** PHP dependency manager (used for Dompdf).

-----

## Setup Instructions

Follow these steps to get the application up and running on your local machine.

### 1\. Prerequisites

Before you begin, ensure you have the following installed:

  * **Web Server:** Apache or Nginx (e.g., via XAMPP, WAMP, MAMP, or a standalone setup).
  * **PHP:** Version 7.4 or higher (with `mbstring` and `gd` extensions enabled for Dompdf).
  * **MySQL:** Database server.
  * **Composer:** PHP dependency manager.

### 2\. Clone the Repository

```bash
git clone <repository_url>
cd php-resume-builder # or whatever your project folder is named
```

### 3\. Database Setup

1.  Create a new MySQL database (e.g., `resume_builder`).

2.  Import the following SQL schema to create the necessary tables:

    ```sql
    -- users table
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- user_profiles table
    CREATE TABLE user_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        full_name VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        linkedin_url VARCHAR(255),
        github_url VARCHAR(255),
        website_url VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- resumes table
    CREATE TABLE resumes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        resume_name VARCHAR(255) NOT NULL,
        summary TEXT,
        template_id INT, -- For future template selection
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- experiences table
    CREATE TABLE experiences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        resume_id INT NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        job_title VARCHAR(255) NOT NULL,
        start_date DATE,
        end_date VARCHAR(50), -- Allows for 'Present'
        description TEXT,
        FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
    );

    -- education table
    CREATE TABLE education (
        id INT AUTO_INCREMENT PRIMARY KEY,
        resume_id INT NOT NULL,
        institution VARCHAR(255) NOT NULL,
        degree VARCHAR(255) NOT NULL,
        major VARCHAR(255),
        graduation_date DATE,
        FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
    );

    -- skills table
    CREATE TABLE skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        resume_id INT NOT NULL,
        skill_name VARCHAR(100) NOT NULL,
        skill_type VARCHAR(100), -- e.g., 'Programming Languages', 'Tools'
        FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
    );

    -- projects table
    CREATE TABLE projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        resume_id INT NOT NULL,
        project_title VARCHAR(255) NOT NULL,
        project_url VARCHAR(255),
        technologies TEXT,
        description TEXT,
        FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
    );
    ```

### 4\. Configure the Application

1.  Open the `config.php` file in the root directory.

2.  Update the database credentials and `BASE_URL` to match your environment:

    ```php
    <?php
    // config.php

    // Database credentials
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root'); // Your database username
    define('DB_PASS', '');     // Your database password
    define('DB_NAME', 'resume_builder'); // The database name you created

    // Base URL of your application (e.g., http://localhost/resume_app/)
    define('BASE_URL', 'http://localhost/resume_builder/'); // IMPORTANT: Include trailing slash!

    // ... rest of the file
    ```

### 5\. Install Composer Dependencies

Navigate to the project's root directory in your terminal and run Composer to install Dompdf:

```bash
composer install
```

### 6\. Place on Web Server

Place the entire `php-resume-builder` folder (or whatever you named it) into your web server's document root (e.g., `htdocs` for XAMPP/WAMP, `www` for MAMP, or your Nginx/Apache configured root).

### 7\. Access the Application

Open your web browser and go to the `BASE_URL` you defined in `config.php` (e.g., `http://localhost/resume_builder/`).

-----

## Usage

1.  **Register:** Create a new user account.
2.  **Login:** Access your dashboard.
3.  **Create New Resume:** Start building a new resume.
4.  **Fill Details:** Enter your personal information, work experience, education, skills, and projects. The forms allow you to dynamically add multiple entries for experience, education, skills, and projects.
5.  **Generate PDF:** Click the "Generate PDF" button to view and download your resume.

-----

## Contributing

Feel free to fork this repository, make improvements, and submit pull requests.

-----

## License

This project is open-source and available under the [MIT License](LICENSE.md).

-----
