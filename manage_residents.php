<?php
// --- Manage Residents Page ---
// This page displays a list of barangay residents, allows searching for residents,
// and provides options to add, edit, or delete resident records.
// It interacts with 'resident_handler.php' for processing actions and 'resident_form.php' for add/edit forms.

// Set the page title, which is used in the header include.
$page_title = 'Manage Residents';
// Include the common header for the page layout and database connection ($link).
require_once 'includes/header.php';

// --- Handle Status Messages from Handler ---
// Check for 'status' GET parameter, typically set by 'resident_handler.php' after an action.
$message = ''; // Initialize an empty message string.
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_add') {
        $message = '<div class="alert alert-success" role="alert">New resident added successfully!</div>';
    } elseif ($_GET['status'] == 'success_update') {
        $message = '<div class="alert alert-success" role="alert">Resident updated successfully!</div>';
    } elseif ($_GET['status'] == 'success_delete') {
        $message = '<div class="alert alert-success" role="alert">Resident deleted successfully!</div>';
    } elseif ($_GET['status'] == 'error') {
        $message = '<div class="alert alert-danger" role="alert">An error occurred. Please try again.</div>';
    }
}

// --- Fetch Residents Data ---
// Get the search query from GET parameter if set, and sanitize it.
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($link, $_GET['search']) : '';

// Get gender filter from GET parameter if set
$gender_filter = isset($_GET['gender']) ? mysqli_real_escape_string($link, $_GET['gender']) : '';

// Get age group filter from GET parameter if set
$age_group_filter = isset($_GET['age_group']) ? mysqli_real_escape_string($link, $_GET['age_group']) : '';

// Base SQL query to select residents with their purok name.
$sql = "SELECT r.id, r.first_name, r.middle_name, r.last_name, r.suffix, r.gender, r.civil_status, r.birthdate, r.contact_number, r.registration_date, r.status, r.date_status_changed, r.status_remarks, p.purok_name 
        FROM residents r 
        LEFT JOIN puroks p ON r.purok_id = p.id";

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];
$types = '';

// If a search query is provided, add conditions for first name, last name, etc.
if (!empty($search_query)) {
    $search_term_like = '%' . $search_query . '%';
    $where_conditions[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR r.contact_number LIKE ?)";
    $params[] = $search_term_like;
    $params[] = $search_term_like;
    $params[] = $search_term_like;
    $types .= 'sss';
}

// If gender filter is provided, add gender condition
if (!empty($gender_filter)) {
    $where_conditions[] = "r.gender = ?";
    $params[] = $gender_filter;
    $types .= 's';
}

// If age group filter is provided, add age condition
if (!empty($age_group_filter)) {
    switch($age_group_filter) {
        case 'child':
            $where_conditions[] = "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 0 AND 12";
            break;
        case 'youth':
            $where_conditions[] = "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 13 AND 24";
            break;
        case 'adult':
            $where_conditions[] = "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 25 AND 59";
            break;
        case 'senior':
            $where_conditions[] = "TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60";
            break;
    }
}

// If there are any WHERE conditions, append them to the SQL query
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Order the results by last name, then first name.
$sql .= " ORDER BY r.last_name ASC, r.first_name ASC";

// Execute the SQL query using prepared statements to prevent SQL injection.
if (!empty($params)) {
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $residents_result = mysqli_stmt_get_result($stmt);
} else {
    $residents_result = mysqli_query($link, $sql);
}

// TODO: Add error handling for mysqli_query if needed.

?>

<!-- Page Header and Add Button -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="dashboard-title">
        <?php 
        echo html_escape($page_title);
        
        // Show active filters in the title
        $title_filters = [];
        if (!empty($gender_filter)) {
            $title_filters[] = ucfirst($gender_filter);
        }
        if (!empty($age_group_filter)) {
            $age_group_names = [
                'child' => 'Children (0-12)',
                'youth' => 'Youth (13-24)', 
                'adult' => 'Adults (25-59)',
                'senior' => 'Seniors (60+)'
            ];
            $title_filters[] = $age_group_names[$age_group_filter];
        }
        if (!empty($title_filters)) {
            echo ' - ' . implode(' & ', $title_filters);
        }
        ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- Link to the form for adding a new resident -->
        <a href="resident_form.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add New Resident
        </a>
    </div>
</div>

<?php echo $message; // Display any success or error messages here ?>

<!-- Active Filters Display -->
<?php if (!empty($gender_filter) || !empty($age_group_filter)): ?>
<div class="mb-3">
    <div class="alert alert-info d-flex align-items-center">
        <i class="fas fa-filter me-2"></i>
        <div>
            <strong>Active Filters:</strong>
            <?php
            $active_filters = [];
            if (!empty($gender_filter)) {
                $active_filters[] = "Gender: " . ucfirst($gender_filter);
            }
            if (!empty($age_group_filter)) {
                $age_group_names = [
                    'child' => 'Children (0-12 years)',
                    'youth' => 'Youth (13-24 years)', 
                    'adult' => 'Adults (25-59 years)',
                    'senior' => 'Seniors (60+ years)'
                ];
                $active_filters[] = "Age Group: " . $age_group_names[$age_group_filter];
            }
            echo implode(' | ', $active_filters);
            ?>
        </div>
        <a href="manage_residents.php" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="fas fa-times"></i> Clear All Filters
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Search Form -->
<form method="GET" action="manage_residents.php" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by name or contact number..." value="<?php echo html_escape(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($_GET['search'])): ?>
            <a href="manage_residents.php" class="btn btn-outline-danger"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Residents List Card -->
<div class="card officials-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Residents List</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive"> <!-- Ensures table is scrollable on small screens -->
            <table class="table table-hover"> <!-- Hover effect for table rows -->
                <thead class="table-light"> <!-- Light background for table header -->
                    <tr>
                        <th>ID</th>
                        <th>Fullname</th>
                        <th>Gender</th>
                        <th>Civil Status</th>
                        <th>Age</th>
                        <th>Purok</th>
                        <th>Status</th>
                        <th>Contact</th>
                        <th>Registration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php // Check if there are any residents to display
                    if ($residents_result && mysqli_num_rows($residents_result) > 0): ?>
                        <?php // Loop through each resident record and display it in a table row
                        while($resident = mysqli_fetch_assoc($residents_result)): ?>
                            <tr>
                                <!-- Display resident data, escaping all output to prevent XSS -->
                                <td><?php echo html_escape($resident['id']); ?></td>
                                <td>
                                    <strong>
                                        <?php 
                                        // Assemble the full name from parts
                                        $fullname_parts = [$resident['first_name'], $resident['middle_name'], $resident['last_name'], $resident['suffix']];
                                        echo html_escape(implode(' ', array_filter($fullname_parts))); 
                                        ?>
                                    </strong>
                                </td>
                                <td>
                                    <i class="fas <?php echo ($resident['gender'] == 'Male') ? 'fa-male text-primary' : (($resident['gender'] == 'Female') ? 'fa-female text-danger' : 'fa-user text-muted'); ?>"></i>
                                    <?php echo html_escape($resident['gender']); ?>
                                </td>
                                <td><?php echo html_escape($resident['civil_status']); ?></td>
                                <!-- Calculate and display age -->
                                <td><?php 
                                    if (!empty($resident['birthdate'])) {
                                        $age = date_diff(date_create($resident['birthdate']), date_create('today'))->y;
                                        echo html_escape($age . ' years');
                                        echo '<br><small class="text-muted">' . html_escape(date('M d, Y', strtotime($resident['birthdate']))) . '</small>';
                                    } else {
                                        echo 'N/A';
                                    }
                                ?></td>
                                <!-- Display Purok with badge -->
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo html_escape($resident['purok_name'] ?? 'Unassigned'); ?>
                                    </span>
                                </td>
                                <!-- Display Status with appropriate badge -->
                                <td>
                                    <span class="badge <?php 
                                        echo match($resident['status']) {
                                            'Active' => 'bg-success',
                                            'Deceased' => 'bg-dark',
                                            'Moved Out' => 'bg-warning',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo html_escape($resident['status']); ?>
                                    </span>
                                    <?php if (!empty($resident['date_status_changed']) && $resident['status'] != 'Active'): ?>
                                        <br><small class="text-muted">
                                            Changed: <?php echo date("M j, Y", strtotime($resident['date_status_changed'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <!-- Contact -->
                                <td>
                                    <?php if (!empty($resident['contact_number'])): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo html_escape($resident['contact_number']); ?>
                                    <?php endif; ?>
                                </td>
                                <!-- Registration Date -->
                                <td><?php echo html_escape(date('M d, Y', strtotime($resident['registration_date']))); ?></td>
                                <td>
                                    <!-- Actions buttons in button group -->
                                    <div class="btn-group btn-group-sm">
                                        <!-- View Info button: links to view_resident.php with resident ID -->
                                        <a href="view_resident.php?id=<?php echo html_escape($resident['id']); ?>" class="btn btn-outline-info" title="View Complete Information">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <!-- Edit button: links to resident_form.php with action=edit and resident ID -->
                                        <a href="resident_form.php?action=edit&id=<?php echo html_escape($resident['id']); ?>" class="btn btn-outline-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- Delete button: links to resident_handler.php with action=delete and resident ID -->
                                        <a href="resident_handler.php?action=delete&id=<?php echo html_escape($resident['id']); ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Delete" 
                                           onclick="return confirm('Are you sure you want to delete this resident? This action cannot be undone.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: // If no residents are found (or search yields no results) ?>
                        <tr><td colspan="9" class="text-center">No residents found<?php echo !empty($search_query) ? ' matching your search' : ''; // Tailor message if search was active ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Enhanced Table Footer with Actions -->
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted">
                        <?php 
                        $total_count = mysqli_num_rows($residents_result);
                        echo "Showing $total_count resident" . ($total_count != 1 ? 's' : '');
                        if (!empty($search_query) || !empty($gender_filter) || !empty($age_group_filter)) {
                            echo " (filtered)";
                        }
                        ?>
                    </small>
                </div>
                <div class="btn-group btn-group-sm">
                    <a href="purok_details.php" class="btn btn-outline-primary">
                        <i class="fas fa-map-marker-alt me-1"></i>View by Purok
                    </a>
                    <a href="resident_form.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Add New
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>How to Use Residents Management
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Quick Actions:</h6>
                        <ul class="mb-0">
                            <li><strong><i class="fas fa-eye text-info"></i> View Info:</strong> See complete resident details including all personal, contact, and household information</li>
                            <li><strong><i class="fas fa-edit text-warning"></i> Edit:</strong> Modify resident information</li>
                            <li><strong><i class="fas fa-trash text-danger"></i> Delete:</strong> Remove resident record (permanent)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Features:</h6>
                        <ul class="mb-0">
                            <li><strong>Search:</strong> Find residents by name, address, or email</li>
                            <li><strong>Status Badges:</strong> Color-coded Active/Deceased/Moved Out status</li>
                            <li><strong>Purok System:</strong> Organized by barangay subdivisions</li>
                            <li><strong>Age Calculation:</strong> Automatic age calculation from birthdate</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php // Include the common footer for the page.
require_once 'includes/footer.php'; 
?>
