<?php
// --- View Official Details Page ---
// This page displays comprehensive information about a specific barangay official

$page_title = 'View Official Details';
require_once 'includes/header.php';

// Check if official ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_officials.php?status=error_missing_id");
    exit;
}

$official_id = mysqli_real_escape_string($link, $_GET['id']);

// Fetch comprehensive official information
$sql = "SELECT * FROM officials WHERE id = '$official_id'";
$result = mysqli_query($link, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: manage_officials.php?status=error_notfound");
    exit;
}

$official = mysqli_fetch_assoc($result);

// Calculate term duration if dates are available
$term_duration = '';
if (!empty($official['term_start_date']) && !empty($official['term_end_date'])) {
    $start_date = new DateTime($official['term_start_date']);
    $end_date = new DateTime($official['term_end_date']);
    $interval = $start_date->diff($end_date);
    $term_duration = $interval->format('%y years, %m months, %d days');
}

// Determine if term is active
$term_status = 'Unknown';
$term_status_class = 'bg-secondary';
if (!empty($official['term_start_date']) && !empty($official['term_end_date'])) {
    $today = new DateTime();
    $start_date = new DateTime($official['term_start_date']);
    $end_date = new DateTime($official['term_end_date']);
    
    if ($today < $start_date) {
        $term_status = 'Future';
        $term_status_class = 'bg-info';
    } elseif ($today > $end_date) {
        $term_status = 'Expired';
        $term_status_class = 'bg-danger';
    } else {
        $term_status = 'Active';
        $term_status_class = 'bg-success';
    }
}

// Get position category and icon
function getPositionDetails($position) {
    $position_lower = strtolower($position);
    
    if (strpos($position_lower, 'captain') !== false || strpos($position_lower, 'punong') !== false) {
        return ['category' => 'Executive', 'icon' => 'fa-crown', 'color' => 'text-warning'];
    } elseif (strpos($position_lower, 'secretary') !== false) {
        return ['category' => 'Administrative', 'icon' => 'fa-file-alt', 'color' => 'text-info'];
    } elseif (strpos($position_lower, 'treasurer') !== false) {
        return ['category' => 'Financial', 'icon' => 'fa-coins', 'color' => 'text-success'];
    } elseif (strpos($position_lower, 'kagawad') !== false) {
        return ['category' => 'Council Member', 'icon' => 'fa-users', 'color' => 'text-primary'];
    } elseif (strpos($position_lower, 'sk') !== false) {
        return ['category' => 'Youth Council', 'icon' => 'fa-graduation-cap', 'color' => 'text-warning'];
    } elseif (strpos($position_lower, 'tanod') !== false) {
        return ['category' => 'Security', 'icon' => 'fa-shield-alt', 'color' => 'text-danger'];
    } else {
        return ['category' => 'Other', 'icon' => 'fa-user-tie', 'color' => 'text-secondary'];
    }
}

$position_details = getPositionDetails($official['position']);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">
        <i class="fas fa-user-tie me-2"></i><?php echo html_escape($page_title); ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="official_form.php?action=edit&id=<?php echo html_escape($official['id']); ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit Official
            </a>
            <a href="manage_officials.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>
</div>

<!-- Official Details Card -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Complete Official Information
                </h5>
            </div>
            <div class="card-body">
                <!-- Personal Information Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-user me-2"></i>Personal Information
                        </h6>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <strong>Full Name:</strong><br>
                        <span class="fs-4"><?php echo html_escape($official['fullname']); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Official ID:</strong><br>
                        <code class="fs-6"><?php echo html_escape($official['id']); ?></code>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <strong>Position & Title:</strong><br>
                        <span class="fs-5">
                            <i class="fas <?php echo $position_details['icon']; ?> <?php echo $position_details['color']; ?> me-2"></i>
                            <?php echo html_escape($official['position']); ?>
                        </span>
                        <br><small class="text-muted"><?php echo $position_details['category']; ?></small>
                    </div>
                    <div class="col-md-4">
                        <strong>Display Order:</strong><br>
                        <span class="badge bg-info fs-6"><?php echo html_escape($official['display_order'] ?? 'Not set'); ?></span>
                        <br><small class="text-muted">Position in organizational chart</small>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="row mb-4 mt-4">
                    <div class="col-12">
                        <h6 class="text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-address-card me-2"></i>Contact Information
                        </h6>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Contact Number:</strong><br>
                        <?php if (!empty($official['contact_number'])): ?>
                            <i class="fas fa-phone me-2"></i><?php echo html_escape($official['contact_number']); ?>
                        <?php else: ?>
                            <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Email Address:</strong><br>
                        <?php if (!empty($official['email'])): ?>
                            <i class="fas fa-envelope me-2"></i><?php echo html_escape($official['email']); ?>
                        <?php else: ?>
                            <span class="text-muted">Not provided</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Address:</strong><br>
                        <?php echo !empty($official['address']) ? html_escape($official['address']) : '<span class="text-muted">Not specified</span>'; ?>
                    </div>
                </div>

                <!-- Term Information Section -->
                <div class="row mb-4 mt-4">
                    <div class="col-12">
                        <h6 class="text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>Term Information
                        </h6>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Term Start Date:</strong><br>
                        <?php if (!empty($official['term_start_date'])): ?>
                            <?php echo html_escape(date('F j, Y', strtotime($official['term_start_date']))); ?>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Term End Date:</strong><br>
                        <?php if (!empty($official['term_end_date'])): ?>
                            <?php echo html_escape(date('F j, Y', strtotime($official['term_end_date']))); ?>
                        <?php else: ?>
                            <span class="text-muted">Not specified</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Term Status:</strong><br>
                        <span class="badge <?php echo $term_status_class; ?> fs-6">
                            <?php echo $term_status; ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($term_duration)): ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Term Duration:</strong><br>
                        <span class="text-info"><?php echo html_escape($term_duration); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Information Section -->
                <?php if (!empty($official['committee_assignment']) || !empty($official['education']) || !empty($official['previous_experience'])): ?>
                <div class="row mb-4 mt-4">
                    <div class="col-12">
                        <h6 class="text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-briefcase me-2"></i>Additional Information
                        </h6>
                    </div>
                </div>

                <div class="row mb-3">
                    <?php if (!empty($official['committee_assignment'])): ?>
                    <div class="col-md-6">
                        <strong>Committee Assignment:</strong><br>
                        <?php echo html_escape($official['committee_assignment']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($official['education'])): ?>
                    <div class="col-md-6">
                        <strong>Educational Background:</strong><br>
                        <?php echo html_escape($official['education']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($official['previous_experience'])): ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Previous Experience:</strong><br>
                        <?php echo nl2br(html_escape($official['previous_experience'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photo and Quick Actions Sidebar -->
    <div class="col-lg-4">
        <!-- Official Photo -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-camera me-2"></i>Official Photo
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="official-photo-display mb-3">
                    <img src="<?php echo !empty($official['image_path']) && file_exists($official['image_path']) ? html_escape($official['image_path']) : 'assets/images/default-avatar.png'; ?>" 
                         alt="<?php echo html_escape($official['fullname']); ?>" 
                         class="img-fluid rounded-circle shadow" 
                         style="width: 200px; height: 200px; object-fit: cover; border: 4px solid #007bff;">
                </div>
                <p class="mb-0"><strong><?php echo html_escape($official['fullname']); ?></strong></p>
                <p class="text-muted"><?php echo html_escape($official['position']); ?></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="official_form.php?action=edit&id=<?php echo html_escape($official['id']); ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit Information
                    </a>
                    <a href="manage_officials.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Officials List
                    </a>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info me-2"></i>System Info
                </h5>
            </div>
            <div class="card-body">
                <p><strong>Created:</strong><br>
                <?php if (!empty($official['created_at'])): ?>
                    <?php echo html_escape(date('F j, Y \a\t g:i A', strtotime($official['created_at']))); ?>
                <?php else: ?>
                    <span class="text-muted">Not recorded</span>
                <?php endif; ?>
                </p>
                
                <p><strong>Last Updated:</strong><br>
                <?php if (!empty($official['updated_at'])): ?>
                    <?php echo html_escape(date('F j, Y \a\t g:i A', strtotime($official['updated_at']))); ?>
                <?php else: ?>
                    <span class="text-muted">Not recorded</span>
                <?php endif; ?>
                </p>
                
                <p><strong>Official ID:</strong><br>
                <code><?php echo html_escape($official['id']); ?></code></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 