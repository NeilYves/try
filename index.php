<?php 
$page_title = 'Dashboard'; // Set the page title for the header
require_once 'includes/header.php'; // Include the header which starts the session

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
    header("location: login.php");
    exit;
}

// Database queries remain the same as they are specific to the dashboard

// Fetch Total Population
// Fetch Total Residents
$total_residents_query = "SELECT COUNT(id) AS total FROM residents";
$total_residents_result = mysqli_query($link, $total_residents_query);
if (!$total_residents_result) {
    error_log("Database query error: " . mysqli_error($link) . " in query: " . $total_residents_query);
}
$total_residents_row = mysqli_fetch_assoc($total_residents_result);
$total_residents = $total_residents_row['total'] ?? 0;

// Fetch Total Officials
$total_officials_query = "SELECT COUNT(id) AS total FROM officials";
$total_officials_result = mysqli_query($link, $total_officials_query);
if (!$total_officials_result) {
    error_log("Database query error: " . mysqli_error($link) . " in query: " . $total_officials_query);
}
$total_officials_row = mysqli_fetch_assoc($total_officials_result);
$total_officials = $total_officials_row['total'] ?? 0;

// Calculate Total Population (Residents + Officials)
$total_population = $total_residents + $total_officials;

// Debugging - For troubleshooting only, remove in production
// error_log("DEBUG: Residents: {$total_residents}, Officials: {$total_officials}, Total: {$total_population}");

// Fetch Male Population
$male_population_result = mysqli_query($link, "SELECT COUNT(id) AS total_male FROM residents WHERE gender = 'Male'");
$male_population_row = mysqli_fetch_assoc($male_population_result);
$male_population = $male_population_row['total_male'] ?? 0;

// Fetch Female Population
$female_population_result = mysqli_query($link, "SELECT COUNT(id) AS total_female FROM residents WHERE gender = 'Female'");
$female_population_row = mysqli_fetch_assoc($female_population_result);
$female_population = $female_population_row['total_female'] ?? 0;

// Fetch Age Demographics
// Child: 0-12 years
$child_population_result = mysqli_query($link, "SELECT COUNT(id) AS total_child FROM residents WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 0 AND 12");
$child_population_row = mysqli_fetch_assoc($child_population_result);
$child_population = $child_population_row['total_child'] ?? 0;

// Youth: 13-24 years
$youth_population_result = mysqli_query($link, "SELECT COUNT(id) AS total_youth FROM residents WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 13 AND 24");
$youth_population_row = mysqli_fetch_assoc($youth_population_result);
$youth_population = $youth_population_row['total_youth'] ?? 0;

// Adult: 25-59 years
$adult_population_result = mysqli_query($link, "SELECT COUNT(id) AS total_adult FROM residents WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 25 AND 59");
$adult_population_row = mysqli_fetch_assoc($adult_population_result);
$adult_population = $adult_population_row['total_adult'] ?? 0;

// Senior: 60+ years
$senior_population_result = mysqli_query($link, "SELECT COUNT(id) AS total_senior FROM residents WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60");
$senior_population_row = mysqli_fetch_assoc($senior_population_result);
$senior_population = $senior_population_row['total_senior'] ?? 0;

// Fetch System Settings
$all_settings_query = "SELECT setting_key, setting_value FROM system_settings";
$all_settings_result = mysqli_query($link, $all_settings_query);

$db_settings = [];
if ($all_settings_result) {
    while ($row = mysqli_fetch_assoc($all_settings_result)) {
        $db_settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Initialize settings with defaults
$settings = [
    'barangay_name' => !empty($db_settings['barangay_name']) ? $db_settings['barangay_name'] : 'Barangay Name Not Set',
    'logo_path' => !empty($db_settings['barangay_logo_path']) ? $db_settings['barangay_logo_path'] : 'assets/images/default_logo.png', // Default logo
    'barangay_seal_text' => !empty($db_settings['barangay_seal_text']) ? $db_settings['barangay_seal_text'] : 'OFFICIAL SEAL',
    'municipality_seal_text' => !empty($db_settings['municipality_seal_text']) ? $db_settings['municipality_seal_text'] : 'MUNICIPALITY SEAL'
];

// Construct full address
$address_parts = [];
if (!empty($db_settings['barangay_address_line1'])) {
    $address_parts[] = $db_settings['barangay_address_line1'];
}
if (!empty($db_settings['barangay_address_line2'])) { // This key might contain city, province as per your SQL dump
    $address_parts[] = $db_settings['barangay_address_line2'];
}
// You might have other keys like 'barangay_city', 'barangay_province' if you decide to separate them later
// For now, using barangay_address_line1 and barangay_address_line2

$settings['full_address'] = !empty($address_parts) ? implode(', ', $address_parts) : 'Address Not Set';

// Final check for logo path to ensure it's not empty and defaults if necessary
if (empty($settings['logo_path'])) {
    $settings['logo_path'] = 'assets/images/default_logo.png';
}
// error_log('Fetched settings: ' . print_r($settings, true)); // For debugging

// Fetch Barangay Officials
$officials_result = mysqli_query($link, "SELECT fullname, position FROM officials ORDER BY display_order ASC, fullname ASC LIMIT 5");

// Fetch Recent Activities
$activities_result = mysqli_query($link, "SELECT activity_description, activity_type, timestamp FROM activities ORDER BY timestamp DESC LIMIT 5");

// Fetch Announcements
$announcements_result = mysqli_query($link, "SELECT id, title, content, event_date, publish_date FROM announcements WHERE is_active = 1 ORDER BY publish_date DESC LIMIT 3");

// Fetch system settings for display
$settings_data = [];
$settings_query = "SELECT setting_key, setting_value FROM system_settings";
$settings_result = mysqli_query($link, $settings_query);
if ($settings_result) {
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $settings_data[$row['setting_key']] = $row['setting_value'];
    }
}

// Format full address
$address_line1 = $settings_data['barangay_address_line1'] ?? '';
$address_line2 = $settings_data['barangay_address_line2'] ?? '';
$full_address = trim($address_line1 . (!empty($address_line1) && !empty($address_line2) ? ', ' : '') . $address_line2);

?>
<!-- The HTML body, sidebar, and start of main content are now in header.php -->
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="dashboard-title">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="fas fa-calendar-alt"></i> Today
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo html_escape($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo html_escape($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <!-- Barangay Info Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card barangay-info-card">
                            <div class="card-body d-flex align-items-center">
                                <img src="<?php echo !empty($settings_data['barangay_logo_path']) && file_exists($settings_data['barangay_logo_path']) ? html_escape($settings_data['barangay_logo_path']) : 'images/barangay-logo.png'; ?>" 
                                     alt="<?php echo html_escape($settings_data['barangay_name'] ?? 'Barangay'); ?> Seal" 
                                     class="brgy-logo me-4" style="max-width: 70px; max-height: 70px; object-fit: contain; border-radius: 50%;">
                                <div>
                                    <h4 class="brgy-name"><?php echo html_escape($settings_data['barangay_name'] ?? 'Barangay Management System'); ?></h4>
                                    <p class="brgy-location"><?php echo html_escape($full_address); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-number"><?php echo html_escape($total_population); ?></div>
                            <div class="stat-label">Total Population</div>
                            <a href="manage_residents.php" class="stat-link">View Details</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-male"></i></div>
                            <div class="stat-number"><?php echo html_escape($male_population); ?></div>
                            <div class="stat-label">Male Population</div>
                            <a href="manage_residents.php?gender=Male" class="stat-link">View Details</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-female"></i></div>
                            <div class="stat-number"><?php echo html_escape($female_population); ?></div>
                            <div class="stat-label">Female Population</div>
                            <a href="manage_residents.php?gender=Female" class="stat-link">View Details</a>
                        </div>
                    </div>
                </div>

                <!-- Age Demographics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-baby"></i></div>
                            <div class="stat-number"><?php echo html_escape($child_population); ?></div>
                            <div class="stat-label">Child Population (0-12)</div>
                            <a href="manage_residents.php?age_group=child" class="stat-link">View Details</a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                            <div class="stat-number"><?php echo html_escape($youth_population); ?></div>
                            <div class="stat-label">Youth Population (13-24)</div>
                            <a href="manage_residents.php?age_group=youth" class="stat-link">View Details</a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                            <div class="stat-number"><?php echo html_escape($adult_population); ?></div>
                            <div class="stat-label">Adult Population (25-59)</div>
                            <a href="manage_residents.php?age_group=adult" class="stat-link">View Details</a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="simple-stat-box">
                            <div class="stat-icon"><i class="fas fa-walking"></i></div>
                            <div class="stat-number"><?php echo html_escape($senior_population); ?></div>
                            <div class="stat-label">Senior Population (60+)</div>
                            <a href="manage_residents.php?age_group=senior" class="stat-link">View Details</a>
                        </div>
                    </div>
                </div>

                <!-- Population Distribution Summary -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-4">
                        <div class="card demographics-summary-card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Population Demographics Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-3">Gender Distribution</h6>
                                        <div class="progress-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-sm font-weight-bold">Male</span>
                                                <span class="text-sm"><?php echo $male_population; ?> (<?php echo $total_residents > 0 ? number_format(($male_population / $total_residents) * 100, 1) : '0'; ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-male" role="progressbar" style="width: <?php echo $total_residents > 0 ? ($male_population / $total_residents) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_residents > 0 ? ($male_population / $total_residents) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="progress-item mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-sm font-weight-bold">Female</span>
                                                <span class="text-sm"><?php echo $female_population; ?> (<?php echo $total_residents > 0 ? number_format(($female_population / $total_residents) * 100, 1) : '0'; ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-female" role="progressbar" style="width: <?php echo $total_residents > 0 ? ($female_population / $total_residents) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_residents > 0 ? ($female_population / $total_residents) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-3">Age Distribution</h6>
                                        <div class="progress-item mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-sm font-weight-bold">Children (0-12)</span>
                                                <span class="text-sm"><?php echo $child_population; ?> (<?php echo $total_residents > 0 ? number_format(($child_population / $total_residents) * 100, 1) : '0'; ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-child" role="progressbar" style="width: <?php echo $total_residents > 0 ? ($child_population / $total_residents) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_residents > 0 ? ($child_population / $total_residents) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="progress-item mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-sm font-weight-bold">Youth (13-24)</span>
                                                <span class="text-sm"><?php echo $youth_population; ?> (<?php echo $total_residents > 0 ? number_format(($youth_population / $total_residents) * 100, 1) : '0'; ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-youth" role="progressbar" style="width: <?php echo $total_residents > 0 ? ($youth_population / $total_residents) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_residents > 0 ? ($youth_population / $total_residents) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="progress-item mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-sm font-weight-bold">Adults (25-59)</span>
                                                <span class="text-sm"><?php echo $adult_population; ?> (<?php echo $total_residents > 0 ? number_format(($adult_population / $total_residents) * 100, 1) : '0'; ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-adult" role="progressbar" style="width: <?php echo $total_residents > 0 ? ($adult_population / $total_residents) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_residents > 0 ? ($adult_population / $total_residents) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="progress-item mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-sm font-weight-bold">Seniors (60+)</span>
                                                <span class="text-sm"><?php echo $senior_population; ?> (<?php echo $total_residents > 0 ? number_format(($senior_population / $total_residents) * 100, 1) : '0'; ?>%)</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar progress-bar-senior" role="progressbar" style="width: <?php echo $total_residents > 0 ? ($senior_population / $total_residents) * 100 : 0; ?>%" aria-valuenow="<?php echo $total_residents > 0 ? ($senior_population / $total_residents) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <div class="card quick-actions-card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt me-2"></i>
                                    Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <a href="resident_form.php?action=add" class="btn btn-primary btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>
                                        Add New Resident
                                    </a>
                                    <a href="issue_certificate_form.php" class="btn btn-success btn-lg">
                                        <i class="fas fa-certificate me-2"></i>
                                        Issue Certificate
                                    </a>
                                    <a href="sms_blast.php" class="btn btn-warning btn-lg">
                                        <i class="fas fa-bullhorn me-2"></i>
                                        Send Announcement
                                    </a>
                                    <a href="manage_officials.php" class="btn btn-info btn-lg">
                                        <i class="fas fa-users-cog me-2"></i>
                                        Manage Officials
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Officials Organizational Chart -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card officials-org-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-sitemap me-2"></i>
                                        Barangay Officials Organizational Chart
                                    </h5>
                                    <a href="manage_officials.php" class="btn btn-sm btn-outline-light">
                                        <i class="fas fa-cog me-1"></i>Manage Officials
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Professional Organizational Chart Template -->
                                <div class="professional-org-chart">
                                    <!-- Header with Barangay Name and Logos -->
                                    <div class="org-header">
                                        <div class="header-content">
                                            <div class="logo-left">
                                                <img src="<?php echo !empty($settings_data['barangay_logo_path']) && file_exists($settings_data['barangay_logo_path']) ? html_escape($settings_data['barangay_logo_path']) : 'images/barangay-logo.png'; ?>" 
                                                     alt="Barangay Seal" class="barangay-seal">
                                                <div class="seal-text"><?php echo strtoupper(html_escape($settings_data['barangay_seal_text'] ?? 'OFFICIAL SEAL')); ?></div>
                                            </div>
                                            <div class="header-title">
                                                <h2 class="barangay-title"><?php echo strtoupper(html_escape($settings_data['barangay_name'] ?? 'BARANGAY MANAGEMENT SYSTEM')); ?></h2>
                                                <h4 class="barangay-location"><?php echo strtoupper(html_escape($full_address)); ?></h4>
                                            </div>
                                            <div class="logo-right">
                                                <img src="<?php echo !empty($settings_data['municipality_logo_path']) && file_exists($settings_data['municipality_logo_path']) ? html_escape($settings_data['municipality_logo_path']) : (!empty($settings_data['barangay_logo_path']) && file_exists($settings_data['barangay_logo_path']) ? html_escape($settings_data['barangay_logo_path']) : 'images/barangay-logo.png'); ?>" 
                                                     alt="Municipality Seal" class="municipality-seal">
                                                <div class="seal-text"><?php echo strtoupper(html_escape($settings_data['municipality_seal_text'] ?? 'MUNICIPALITY SEAL')); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php
                                    // Re-fetch officials for organizational chart
                                    $org_officials_result = mysqli_query($link, "SELECT * FROM officials ORDER BY display_order ASC, fullname ASC");
                                    
                                    $captain = null;
                                    $secretary = null;
                                    $treasurer = null;
                                    $council_members = [];
                                    
                                    // Organize officials by position type
                                    if ($org_officials_result) {
                                        while($official = mysqli_fetch_assoc($org_officials_result)) {
                                            $position_lower = strtolower($official['position']);
                                            
                                            // Categorize officials based on their position
                                            if (strpos($position_lower, 'captain') !== false || strpos($position_lower, 'punong') !== false) {
                                                $captain = $official;
                                            } elseif (strpos($position_lower, 'secretary') !== false) {
                                                $secretary = $official;
                                            } elseif (strpos($position_lower, 'treasurer') !== false) {
                                                $treasurer = $official;
                                            } else {
                                                // All other officials go to council members (Kagawads, SK, Tanods, etc.)
                                                $council_members[] = $official;
                                            }
                                        }
                                    }
                                    ?>

                                    <!-- Barangay Captain (Top Level) -->
                                    <?php if ($captain): ?>
                                    <div class="captain-section">
                                        <div class="professional-official-card captain-position">
                                            <div class="official-photo-frame">
                                                <img src="<?php echo !empty($captain['image_path']) && file_exists($captain['image_path']) ? html_escape($captain['image_path']) : 'assets/images/default-avatar.png'; ?>" 
                                                     alt="<?php echo html_escape($captain['fullname']); ?>" class="official-photo">
                                                <div class="official-frame-border"></div>
                                            </div>
                                            <div class="official-details">
                                                <h3 class="official-name"><?php echo strtoupper(html_escape($captain['fullname'])); ?></h3>
                                                <h4 class="official-position">Punong Barangay</h4>
                                                <?php if (!empty($captain['contact_number'])): ?>
                                                <p class="official-contact">📱 <?php echo html_escape($captain['contact_number']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Secretary and Treasurer (Second Row) -->
                                    <?php if ($secretary || $treasurer): ?>
                                    <div class="executive-section">
                                        <div class="row">
                                            <?php if ($secretary): ?>
                                            <div class="col-md-6">
                                                <div class="professional-official-card secretary-position">
                                                    <div class="official-photo-frame">
                                                        <img src="<?php echo !empty($secretary['image_path']) && file_exists($secretary['image_path']) ? html_escape($secretary['image_path']) : 'assets/images/default-avatar.png'; ?>" 
                                                             alt="<?php echo html_escape($secretary['fullname']); ?>" class="official-photo">
                                                        <div class="official-frame-border"></div>
                                                    </div>
                                                    <div class="official-details">
                                                        <h3 class="official-name"><?php echo strtoupper(html_escape($secretary['fullname'])); ?></h3>
                                                        <h4 class="official-position">Barangay Secretary</h4>
                                                        <?php if (!empty($secretary['contact_number'])): ?>
                                                        <p class="official-contact">📱 <?php echo html_escape($secretary['contact_number']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($treasurer): ?>
                                            <div class="col-md-6">
                                                <div class="professional-official-card treasurer-position">
                                                    <div class="official-photo-frame">
                                                        <img src="<?php echo !empty($treasurer['image_path']) && file_exists($treasurer['image_path']) ? html_escape($treasurer['image_path']) : 'assets/images/default-avatar.png'; ?>" 
                                                             alt="<?php echo html_escape($treasurer['fullname']); ?>" class="official-photo">
                                                        <div class="official-frame-border"></div>
                                                    </div>
                                                    <div class="official-details">
                                                        <h3 class="official-name"><?php echo strtoupper(html_escape($treasurer['fullname'])); ?></h3>
                                                        <h4 class="official-position">Barangay Treasurer</h4>
                                                        <?php if (!empty($treasurer['contact_number'])): ?>
                                                        <p class="official-contact">📱 <?php echo html_escape($treasurer['contact_number']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Council Members Grid (Kagawads, SK, etc.) -->
                                    <?php if (!empty($council_members)): ?>
                                    <div class="council-section">
                                        <div class="row">
                                            <?php 
                                            $chunks = array_chunk($council_members, 3); // Group into rows of 3
                                            foreach ($chunks as $row): ?>
                                                <?php foreach ($row as $member): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="professional-official-card council-position">
                                                        <div class="official-photo-frame">
                                                            <img src="<?php echo !empty($member['image_path']) && file_exists($member['image_path']) ? html_escape($member['image_path']) : 'assets/images/default-avatar.png'; ?>" 
                                                                 alt="<?php echo html_escape($member['fullname']); ?>" class="official-photo">
                                                            <div class="official-frame-border"></div>
                                                        </div>
                                                        <div class="official-details">
                                                            <h3 class="official-name"><?php echo strtoupper(html_escape($member['fullname'])); ?></h3>
                                                            <h4 class="official-position"><?php echo html_escape($member['position']); ?></h4>
                                                            <?php if (!empty($member['contact_number'])): ?>
                                                            <p class="official-contact">📱 <?php echo html_escape($member['contact_number']); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <?php
                                                            // Get committee assignment for display
                                                            $position_lower = strtolower($member['position']);
                                                            $committee = '';
                                                            if (strpos($position_lower, 'sk') !== false) {
                                                                $committee = '🎓 Sangguniang Kabataan';
                                                            } elseif (strpos($position_lower, 'tanod') !== false) {
                                                                $committee = '🛡️ Barangay Security';
                                                            } elseif (strpos($position_lower, 'health') !== false) {
                                                                $committee = '🏥 Comm. on Health';
                                                            } elseif (strpos($position_lower, 'education') !== false) {
                                                                $committee = '📚 Comm. on Education';
                                                            } elseif (strpos($position_lower, 'agriculture') !== false) {
                                                                $committee = '🌾 Comm. on Agriculture';
                                                            } elseif (strpos($position_lower, 'infrastructure') !== false) {
                                                                $committee = '🏗️ Comm. on Infrastructure';
                                                            } elseif (strpos($position_lower, 'peace') !== false) {
                                                                $committee = '☮️ Comm. on Peace & Order';
                                                            } elseif (strpos($position_lower, 'environment') !== false) {
                                                                $committee = '🌱 Comm. on Environment';
                                                            } elseif (strpos($position_lower, 'finance') !== false) {
                                                                $committee = '💰 Comm. on Finance';
                                                            } elseif (strpos($position_lower, 'kagawad') !== false) {
                                                                $committee = '👥 Barangay Council';
                                                            }
                                                            ?>
                                                            
                                                            <?php if ($committee): ?>
                                                            <div class="committee-info">
                                                                <small><?php echo $committee; ?></small>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Add Official Message if no officials exist -->
                                    <?php if (!$captain && !$secretary && !$treasurer && empty($council_members)): ?>
                                    <div class="no-officials-message">
                                        <div class="text-center py-5">
                                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No officials added yet</h5>
                                            <p class="text-muted">Start building your barangay organizational chart</p>
                                            <a href="official_form.php?action=add" class="btn btn-primary btn-lg">
                                                <i class="fas fa-plus me-2"></i>Add First Official
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Announcements and Activities -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush activity-list">
                                    <?php if (mysqli_num_rows($activities_result) > 0): ?>
                                        <?php while($activity = mysqli_fetch_assoc($activities_result)): ?>
                                            <li class="list-group-item">
                                                <div class="d-flex">
                                                    <div class="activity-icon 
                                                        <?php 
                                                            // Basic icon styling based on type - can be expanded
                                                            if (stripos($activity['activity_type'], 'New') !== false) echo 'bg-primary';
                                                            elseif (stripos($activity['activity_type'], 'Update') !== false) echo 'bg-warning';
                                                            elseif (stripos($activity['activity_type'], 'Delete') !== false) echo 'bg-danger';
                                                            elseif (stripos($activity['activity_type'], 'Issue') !== false) echo 'bg-success';
                                                            else echo 'bg-info'; 
                                                        ?>">
                                                        <i class="fas 
                                                        <?php 
                                                            if (stripos($activity['activity_type'], 'Resident') !== false) echo 'fa-user-plus';
                                                            elseif (stripos($activity['activity_type'], 'Certificate') !== false) echo 'fa-file-alt';
                                                            elseif (stripos($activity['activity_type'], 'SMS') !== false) echo 'fa-sms';
                                                            else echo 'fa-bell'; // Default icon
                                                        ?>"></i>
                                                    </div>
                                                    <div class="ms-3">
                                                        <h6 class="mb-1"><?php echo html_escape($activity['activity_description']); ?></h6>
                                                        <small class="text-muted"><?php echo date("F j, Y, g:i a", strtotime(html_escape($activity['timestamp']))); ?></small>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-center">No recent activities.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="card-footer text-center">
                                <a href="view_activities.php" class="btn btn-sm btn-outline-primary">View All Activities</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 announcements-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bullhorn me-2"></i>Announcements & Agenda
                                    </h5>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-dark fw-bold" onclick="openAddAnnouncementModal()">
                                            <i class="fas fa-plus me-1"></i>Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="announcements-container">
                                <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                                    <?php while($announcement = mysqli_fetch_assoc($announcements_result)): ?>
                                            <div class="enhanced-announcement-item mb-3" data-announcement-id="<?php echo $announcement['id']; ?>">
                                                <div class="announcement-header">
                                            <h6 class="announcement-title"><?php echo html_escape($announcement['title']); ?></h6>
                                                    <div class="announcement-actions">
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div class="announcement-details">
                                                    <div class="announcement-meta">
                                            <?php if (!empty($announcement['event_date'])): ?>
                                                        <div class="meta-item when-item">
                                                            <i class="far fa-calendar-alt me-1"></i>
                                                            <strong>When:</strong> <?php echo date("F j, Y, g:i A", strtotime(html_escape($announcement['event_date']))); ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- For now, we'll use the content field, but this can be enhanced with separate fields -->
                                                        <div class="meta-item what-item">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            <strong>What:</strong>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="announcement-content">
                                                        <?php echo nl2br(html_escape($announcement['content'])); ?>
                                                    </div>
                                                    
                                                    <div class="announcement-footer">
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Posted: <?php echo date("F j, Y", strtotime(html_escape($announcement['publish_date']))); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (mysqli_num_rows($announcements_result) > 1): ?>
                                            <hr class="announcement-divider">
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="no-announcements text-center py-4">
                                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No current announcements.</p>
                                            <button class="btn btn-primary" onclick="openAddAnnouncementModal()">
                                                <i class="fas fa-plus me-2"></i>Create First Announcement
                                            </button>
                                        </div>
                                <?php endif; ?>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Global Loading Overlay -->
    <div id="globalLoadingOverlay" class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAnnouncementModalLabel">
                        <i class="fas fa-bullhorn me-2"></i>Add New Announcement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="announcementForm" method="POST" action="announcement_handler.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="announcementTitle" class="form-label">
                                    <i class="fas fa-heading me-1"></i>Title *
                                </label>
                                <input type="text" class="form-control" id="announcementTitle" name="title" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="eventDate" class="form-label">
                                    <i class="far fa-calendar-alt me-1"></i>When (Event Date & Time)
                                </label>
                                <input type="datetime-local" class="form-control" id="eventDate" name="event_date">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="eventLocation" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Where (Location)
                                </label>
                                <input type="text" class="form-control" id="eventLocation" name="location" placeholder="e.g., Barangay Hall">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label for="announcementContent" class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>What (Description) *
                                </label>
                                <textarea class="form-control" id="announcementContent" name="content" rows="4" required
                                    placeholder="Describe the announcement or event details..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Enhanced Dashboard JavaScript
    
    // Open Add Announcement Modal
    function openAddAnnouncementModal() {
        const modal = new bootstrap.Modal(document.getElementById('addAnnouncementModal'));
        modal.show();
    }
    
    // Delete Announcement
    function deleteAnnouncement(announcementId) {
        if (confirm('Are you sure you want to delete this announcement?')) {
            // Create form to submit delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'announcement_handler.php';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'announcement_id';
            idInput.value = announcementId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Enhanced counting animation for dashboard numbers
    function animateCounters() {
        const counters = document.querySelectorAll('.count-number');
        
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.getAttribute('data-target'));
                    
                    let count = 0;
                    const increment = target / 100;
                    const timer = setInterval(() => {
                        count += increment;
                        if (count >= target) {
                            counter.textContent = target;
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.floor(count);
                        }
                    }, 20);
                    
                    observer.unobserve(counter);
                }
            });
        }, observerOptions);
        
        counters.forEach(counter => {
            observer.observe(counter);
        });
    }
    
    // Enhanced card animations
    function enhanceCardAnimations() {
        const cards = document.querySelectorAll('.stat-card, .official-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-12px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    }
    
    // Initialize dashboard enhancements
    document.addEventListener('DOMContentLoaded', function() {
        animateCounters();
        enhanceCardAnimations();
        
        // Add ripple effect to buttons
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.5);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    });
    
    // CSS Animation for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>

<?php 
require_once 'includes/footer.php'; // Include the footer
// The closing of body, html tags, and script includes are now in footer.php
// The mysqli_close($link) is also handled in footer.php
?>
