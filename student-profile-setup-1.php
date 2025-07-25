<?php
session_start();
include 'config.php';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a new student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['new_user']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $student_id = filter_var($_POST['student_id'], FILTER_SANITIZE_STRING);
    $institution = filter_var($_POST['institution'], FILTER_SANITIZE_STRING);
    $department = filter_var($_POST['department'], FILTER_SANITIZE_STRING);
    $field_of_interest = filter_var($_POST['field_of_interest'], FILTER_SANITIZE_STRING);
    $level = filter_var($_POST['level'], FILTER_SANITIZE_STRING);
    $skills = !empty($_POST['skills']) ? implode(',', $_POST['skills']) : '';
    $csrf_token = $_POST['csrf_token'];

    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'CSRF validation failed';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Users SET name = ?, student_id = ?, institution = ?, department = ?, field_of_interest = ?, level = ?, skills = ?, profile_setup = TRUE WHERE id = ?");
            $stmt->execute([$name, $student_id, $institution, $department, $field_of_interest, $level, $skills, $_SESSION['user_id']]);
            unset($_SESSION['new_user']);
            $success = 'Profile updated successfully! Redirecting to dashboard...';
            header("Refresh:2;url=dashboard.php");
            exit;
        } catch (PDOException $e) {
            $error = "Profile update failed: " . $e->getMessage();
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en" class="layout-wide customizer-hide" dir="ltr" data-skin="default" data-assets-path="./assets/" data-template="horizontal-menu-template" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>IMS - Student Profile Setup</title>
    <link rel="icon" type="image/x-icon" href="./assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="./assets/vendor/css/core.css" />
    <link rel="stylesheet" href="./assets/css/demo.css" />
    <link rel="stylesheet" href="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="./assets/vendor/css/pages/page-auth.css" />
    <script src="./assets/vendor/js/helpers.js"></script>
    <script src="./assets/vendor/js/template-customizer.js"></script>
    <script src="./assets/js/config.js"></script>
    <style>
        .nav-tabs .nav-link { border: none; }
        .nav-tabs .nav-link.active { background: #e9ecef; border-bottom: 2px solid #007bff; }
        .tab-content { padding: 20px; }
        .authentication-wrapper .card { max-width: 1000px; margin-left: auto; margin-right: auto; width: 100%; }
        @media (min-width: 576px) {
            .authentication-wrapper { padding: 0 2rem; }
            .authentication-inner { max-width: 1000px; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">
                <div class="card mb-3">
                    <div class="card-body text-center pt-4 pb-0">
                        <div class="app-brand justify-content-center">
                            <a href="index.html" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <span class="text-primary">
                                        <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                            <defs><path d="M13.7918663,0.358365126 L3.39788168,7.44174259 C0.566865006,9.69408886 -0.379795268,12.4788597 0.557900856,15.7960551 C0.68998853,16.2305145 1.09562888,17.7872135 3.12357076,19.2293357 C3.8146334,19.7207684 5.32369333,20.3834223 7.65075054,21.2172976 L7.59773219,21.2525164 L2.63468769,24.5493413 C0.445452254,26.3002124 0.0884951797,28.5083815 1.56381646,31.1738486 C2.83770406,32.8170431 5.20850219,33.2640127 7.09180128,32.5391577 C8.347334,32.0559211 11.4559176,30.0011079 16.4175519,26.3747182 C18.0338572,24.4997857 18.6973423,22.4544883 18.4080071,20.2388261 C17.963753,17.5346866 16.1776345,15.5799961 13.0496516,14.3747546 L10.9194936,13.4715819 L18.6192054,7.984237 L13.7918663,0.358365126 Z" id="path-1"></path><path d="M5.47320593,6.00457225 C4.05321814,8.216144 4.36334763,10.0722806 6.40359441,11.5729822 C8.61520715,12.571656 10.0999176,13.2171421 10.8577257,13.5094407 L15.5088241,14.433041 L18.6192054,7.984237 C15.5364148,3.11535317 13.9273018,0.573395879 13.7918663,0.358365126 C13.5790555,0.511491653 10.8061687,2.3935607 5.47320593,6.00457225 Z" id="path-3"></path><path d="M7.50063644,21.2294429 L12.3234468,23.3159332 C14.1688022,24.7579751 14.397098,26.4880487 13.008334,28.506154 C11.6195701,30.5242593 10.3099883,31.790241 9.07958868,32.3040991 C5.78142938,33.4346997 4.13234973,34 4.13234973,34 C4.13234973,34 2.75489982,33.0538207 2.37032616e-14,31.1614621 C-0.55822714,27.8186216 -0.55822714,26.0572515 -4.05231404e-15,25.8773518 C0.83734071,25.6075023 2.77988457,22.8248993 3.3049379,22.52991 C3.65497346,22.3332504 5.05353963,21.8997614 7.50063644,21.2294429 Z" id="path-4"></path><path d="M20.6,7.13333333 L25.6,13.8 C26.2627417,14.6836556 26.0836556,15.9372583 25.2,16.6 C24.8538077,16.8596443 24.4327404,17 24,17 L14,17 C12.8954305,17 12,16.1045695 12,15 C12,14.5672596 12.1403557,14.1461923 12.4,13.8 L17.4,7.13333333 C18.0627417,6.24967773 19.3163444,6.07059163 20.2,6.73333333 C20.3516113,6.84704183 20.4862915,6.981722 20.6,7.13333333 Z" id="path-5"></path></defs><g id="g-app-brand" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Brand-Logo" transform="translate(-27.000000, -15.000000)"><g id="Icon" transform="translate(27.000000, 15.000000)"><g id="Mask" transform="translate(0.000000, 8.000000)"><mask id="mask-2" fill="white"><use xlink:href="#path-1"></use></mask><use fill="currentColor" xlink:href="#path-1"></use><g id="Path-3" mask="url(#mask-2)"><use fill="currentColor" xlink:href="#path-3"></use><use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-3"></use></g><g id="Path-4" mask="url(#mask-2)"><use fill="currentColor" xlink:href="#path-4"></use><use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-4"></use></g></g><g id="Triangle" transform="translate(19.000000, 11.000000) rotate(-300.000000) translate(-19.000000, -11.000000) "><use fill="currentColor" xlink:href="#path-5"></use><use fill-opacity="0.2" fill="#FFFFFF" xlink:href="#path-5"></use></g></g></g></g>
                                        </svg>
                                    </span>
                                </span>
                                <span class="app-brand-text demo text-heading fw-bold">IMS</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="mb-2">Set Up Your Student Profile</h4>
                        <p class="mb-4">Provide details to help us match you with the best internships</p>

                        <form id="formProfileSetup" class="mb-3" method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">Personal Info</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="interests-tab" data-bs-toggle="tab" data-bs-target="#interests" type="button" role="tab">Interests</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="skills-tab" data-bs-toggle="tab" data-bs-target="#skills" type="button" role="tab">Skills</button>
                                </li>
                            </ul>
                            <div class="tab-content p-3">
                                <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required />
                                        <div class="invalid-feedback">Please enter your name.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="student_id" class="form-label">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter your student ID" required />
                                        <div class="invalid-feedback">Please enter your student ID.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="institution" class="form-label">Institution</label>
                                        <input type="text" class="form-control" id="institution" name="institution" placeholder="Enter your institution" required />
                                        <div class="invalid-feedback">Please enter your institution.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department" placeholder="Enter your department" required />
                                        <div class="invalid-feedback">Please enter your department.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="level" class="form-label">Level</label>
                                        <select class="form-select" id="level" name="level" required>
                                            <option value="">Select your level</option>
                                            <option value="100">Level 100</option>
                                            <option value="200">Level 200</option>
                                            <option value="300">Level 300</option>
                                            <option value="400">Level 400</option>
                                            <option value="500">Level 500</option>
                                        </select>
                                        <div class="invalid-feedback">Please select your level.</div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="interests" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="field_of_interest" class="form-label">Field of Interest</label>
                                        <input type="text" class="form-control" id="field_of_interest" name="field_of_interest" placeholder="Enter your field of interest (e.g., Tech, Design)" required />
                                        <div class="invalid-feedback">Please enter a field of interest.</div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="skills" role="tabpanel">
                                    <div class="mb-3">
                                        <label class="form-label">Skills</label>
                                        <div class="form-check" role="group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Project Management"><label class="form-check-label">Project Management</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Time Management"><label class="form-check-label">Time Management</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Team Leadership"><label class="form-check-label">Team Leadership</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Communication"><label class="form-check-label">Communication</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Problem-Solving"><label class="form-check-label">Problem-Solving</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Adaptability"><label class="form-check-label">Adaptability</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Teamwork"><label class="form-check-label">Teamwork</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Work Ethic"><label class="form-check-label">Work Ethic</label></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Data Analysis"><label class="form-check-label">Data Analysis</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Research"><label class="form-check-label">Research</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Report Writing"><label class="form-check-label">Report Writing</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Python"><label class="form-check-label">Python</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Java"><label class="form-check-label">Java</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="JavaScript"><label class="form-check-label">JavaScript</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="C++"><label class="form-check-label">C++</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="SQL"><label class="form-check-label">SQL</label></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Graphic Design"><label class="form-check-label">Graphic Design</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="UI/UX Design"><label class="form-check-label">UI/UX Design</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Adobe Photoshop"><label class="form-check-label">Adobe Photoshop</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Adobe Illustrator"><label class="form-check-label">Adobe Illustrator</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Public Speaking"><label class="form-check-label">Public Speaking</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Written Communication"><label class="form-check-label">Written Communication</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Negotiation"><label class="form-check-label">Negotiation</label></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Microsoft Word"><label class="form-check-label">Microsoft Word</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Microsoft Excel"><label class="form-check-label">Microsoft Excel</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Microsoft PowerPoint"><label class="form-check-label">Microsoft PowerPoint</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Microsoft Outlook"><label class="form-check-label">Microsoft Outlook</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Critical Thinking"><label class="form-check-label">Critical Thinking</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Attention to Detail"><label class="form-check-label">Attention to Detail</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Customer Service"><label class="form-check-label">Customer Service</label></div>
                                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="skills[]" value="Networking"><label class="form-check-label">Networking</label></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback d-block" id="skillsFeedback">Please select at least one skill.</div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary d-grid w-100 mt-3" name="save_profile">Save and Continue</button>
                        </form>
                        <?php if ($success): ?>
                            <div class="alert alert-success mt-3"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="./assets/vendor/libs/jquery/jquery.js"></script>
    <script src="./assets/vendor/libs/popper/popper.js"></script>
    <script src="./assets/vendor/js/bootstrap.js"></script>
    <script src="./assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="./assets/vendor/js/menu.js"></script>
    <script src="./assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="./assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="./assets/js/main.js"></script>
    <script>
        (function() {
            'use strict';
            var form = document.getElementById('formProfileSetup');
            form.addEventListener('submit', function(event) {
                var skills = document.querySelectorAll('input[name="skills[]"]:checked');
                if (skills.length === 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementById('skillsFeedback').style.display = 'block';
                } else {
                    document.getElementById('skillsFeedback').style.display = 'none';
                }
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        })();
    </script>
    <?php ob_end_flush(); ?>