<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="index.php" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="./assets/img/favicon/favicon.ico" alt="IMS Logo" width="30">
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">IMS</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>
    <div class="menu-inner-shadow"></div>
    <ul class="menu-inner py-1">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
            <li class="menu-item <?php echo $current_page === 'admin_dashboard.php' ? 'active' : ''; ?>">
                <a href="admin_dashboard.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div>Dashboard</div>
                </a>
            </li>
            <li class="menu-item <?php echo $current_page === 'add_user.php' ? 'active' : ''; ?>">
                <a href="add_user.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user-plus"></i>
                    <div>Add New User</div>
                </a>
            </li>
            <li class="menu-item <?php echo $current_page === 'user_management.php' ? 'active' : ''; ?>">
                <a href="user_management.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-group"></i>
                    <div>User Management</div>
                </a>
            </li>
            <li class="menu-item <?php echo in_array($current_page, ['internship_management.php', 'internship_guidelines.php', 'internship_timelines.php']) ? 'active open' : ''; ?>">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-briefcase"></i>
                    <div>Internship Management</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item <?php echo $current_page === 'internship_management.php' ? 'active' : ''; ?>">
                        <a href="internship_management.php" class="menu-link">
                            <div>Internships</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $current_page === 'internship_guidelines.php' ? 'active' : ''; ?>">
                        <a href="internship_guidelines.php" class="menu-link">
                            <div>Guidelines</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $current_page === 'internship_timelines.php' ? 'active' : ''; ?>">
                        <a href="internship_timelines.php" class="menu-link">
                            <div>Timelines</div>
                        </a>
                    </li>
                </ul>
            </li>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Student'): ?>
            <li class="menu-item <?php echo $current_page === 'student_dashboard.php' ? 'active' : ''; ?>">
                <a href="student_dashboard.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div>Dashboard</div>
                </a>
            </li>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Lecturer'): ?>
            <li class="menu-item <?php echo $current_page === 'lecturer_dashboard.php' ? 'active' : ''; ?>">
                <a href="lecturer_dashboard.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div>Dashboard</div>
                </a>
            </li>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'Employer'): ?>
            <li class="menu-item <?php echo $current_page === 'employer_dashboard.php' ? 'active' : ''; ?>">
                <a href="employer_dashboard.php" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div>Dashboard</div>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>